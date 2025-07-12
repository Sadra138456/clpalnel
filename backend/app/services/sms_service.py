import requests
import json
from typing import Optional, Dict, Any
from datetime import datetime
from sqlalchemy.orm import Session
from app.core.config import settings
from app.models.sms_archive import SMSArchive
from app.models.reservation import Reservation
from app.models.user import User

class SMSService:
    def __init__(self):
        self.api_key = settings.SMS_API_KEY
        self.api_url = settings.SMS_API_URL
        self.sender = settings.SMS_SENDER
    
    async def send_sms(
        self, 
        db: Session, 
        phone: str, 
        message: str, 
        recipient_name: str,
        sent_by_user_id: int,
        reservation_id: Optional[int] = None,
        sms_type: str = "manual"
    ) -> Dict[str, Any]:
        """Send SMS and save to archive"""
        
        # Clean phone number
        phone = self._clean_phone_number(phone)
        
        # Validate inputs
        if not phone or not message or not recipient_name:
            return {
                "success": False,
                "error": "Phone, message, and recipient name are required"
            }
        
        # Send SMS via provider
        sms_result = await self._send_via_kavenegar(phone, message)
        
        # Save to archive
        archive_record = SMSArchive(
            message=message,
            recipient_phone=phone,
            recipient_name=recipient_name,
            sms_type=sms_type,
            status="sent" if sms_result["success"] else "failed",
            provider_message_id=sms_result.get("message_id"),
            provider_status=sms_result.get("status"),
            cost=sms_result.get("cost"),
            reservation_id=reservation_id,
            sent_by=sent_by_user_id
        )
        
        db.add(archive_record)
        db.commit()
        db.refresh(archive_record)
        
        return {
            "success": sms_result["success"],
            "archive_id": archive_record.id,
            "message_id": sms_result.get("message_id"),
            "error": sms_result.get("error")
        }
    
    async def send_bulk_sms(
        self, 
        db: Session, 
        recipients: list, 
        message: str,
        sent_by_user_id: int,
        sms_type: str = "manual"
    ) -> Dict[str, Any]:
        """Send bulk SMS"""
        results = []
        success_count = 0
        fail_count = 0
        
        for recipient in recipients:
            phone = recipient.get("phone")
            name = recipient.get("name")
            reservation_id = recipient.get("reservation_id")
            
            result = await self.send_sms(
                db=db,
                phone=phone,
                message=message,
                recipient_name=name,
                sent_by_user_id=sent_by_user_id,
                reservation_id=reservation_id,
                sms_type=sms_type
            )
            
            if result["success"]:
                success_count += 1
            else:
                fail_count += 1
            
            results.append({
                "phone": phone,
                "name": name,
                "success": result["success"],
                "error": result.get("error")
            })
        
        return {
            "total": len(recipients),
            "success_count": success_count,
            "fail_count": fail_count,
            "results": results
        }
    
    async def send_reminder_sms(
        self, 
        db: Session, 
        reservation: Reservation,
        template: str,
        sent_by_user_id: int
    ) -> Dict[str, Any]:
        """Send reminder SMS for a reservation"""
        
        # Replace template variables
        message = self._process_template(template, reservation)
        
        result = await self.send_sms(
            db=db,
            phone=reservation.phone_number,
            message=message,
            recipient_name=reservation.owner_name,
            sent_by_user_id=sent_by_user_id,
            reservation_id=reservation.id,
            sms_type="auto_reminder"
        )
        
        # Mark reminder as sent if successful
        if result["success"]:
            reservation.reminder_sent = True
            db.commit()
        
        return result
    
    async def _send_via_kavenegar(self, phone: str, message: str) -> Dict[str, Any]:
        """Send SMS via Kavenegar API"""
        if not self.api_key:
            return {
                "success": False,
                "error": "SMS API key not configured"
            }
        
        url = f"{self.api_url}/{self.api_key}/sms/send.json"
        
        payload = {
            "receptor": phone,
            "message": message,
            "sender": self.sender
        }
        
        try:
            response = requests.post(url, data=payload, timeout=30)
            
            if response.status_code == 200:
                data = response.json()
                if data["return"]["status"] == 200:
                    entry = data["entries"][0]
                    return {
                        "success": True,
                        "message_id": str(entry["messageid"]),
                        "status": entry["status"],
                        "cost": entry.get("cost", 0)
                    }
                else:
                    return {
                        "success": False,
                        "error": f"API Error: {data['return']['message']}"
                    }
            else:
                return {
                    "success": False,
                    "error": f"HTTP Error: {response.status_code}"
                }
        
        except requests.exceptions.RequestException as e:
            return {
                "success": False,
                "error": f"Network Error: {str(e)}"
            }
        except Exception as e:
            return {
                "success": False,
                "error": f"Unexpected Error: {str(e)}"
            }
    
    def _clean_phone_number(self, phone: str) -> str:
        """Clean and format phone number"""
        # Remove any non-digit characters
        phone = ''.join(filter(str.isdigit, phone))
        
        # Handle Iranian mobile numbers
        if phone.startswith("0"):
            phone = "98" + phone[1:]
        elif not phone.startswith("98"):
            phone = "98" + phone
        
        return phone
    
    def _process_template(self, template: str, reservation: Reservation) -> str:
        """Process SMS template with reservation data"""
        replacements = {
            "[نام حیوان]": reservation.pet_name,
            "[نام صاحب]": reservation.owner_name,
            "[تاریخ]": reservation.next_visit_date.strftime("%Y/%m/%d") if reservation.next_visit_date else "",
            "[نوع واکسن]": reservation.vaccine_type,
            "[نژاد]": reservation.breed or "نامشخص",
            "[وزن]": f"{reservation.weight} کیلوگرم" if reservation.weight else "نامشخص",
            "[قیمت]": f"{reservation.price:,} تومان" if reservation.price else "نامشخص"
        }
        
        message = template
        for placeholder, value in replacements.items():
            message = message.replace(placeholder, str(value))
        
        return message
    
    def get_sms_archive(
        self, 
        db: Session, 
        filters: dict,
        skip: int = 0, 
        limit: int = 100
    ) -> tuple:
        """Get SMS archive with filters"""
        query = db.query(SMSArchive)
        
        # Apply filters
        if filters.get("search"):
            search_term = f"%{filters['search']}%"
            query = query.filter(
                SMSArchive.message.ilike(search_term) |
                SMSArchive.recipient_name.ilike(search_term) |
                SMSArchive.recipient_phone.ilike(search_term)
            )
        
        if filters.get("sms_type") and filters["sms_type"] != "all":
            query = query.filter(SMSArchive.sms_type == filters["sms_type"])
        
        if filters.get("status") and filters["status"] != "all":
            query = query.filter(SMSArchive.status == filters["status"])
        
        if filters.get("sent_date_from"):
            query = query.filter(SMSArchive.sent_at >= filters["sent_date_from"])
        
        if filters.get("sent_date_to"):
            query = query.filter(SMSArchive.sent_at <= filters["sent_date_to"])
        
        if filters.get("sent_by"):
            query = query.filter(SMSArchive.sent_by == filters["sent_by"])
        
        # Get total count
        total = query.count()
        
        # Get records with pagination
        records = query.order_by(SMSArchive.sent_at.desc()).offset(skip).limit(limit).all()
        
        return records, total
    
    def get_sms_statistics(self, db: Session) -> Dict[str, int]:
        """Get SMS statistics"""
        today = datetime.now().date()
        
        stats = {
            "total_sent": db.query(SMSArchive).filter(SMSArchive.status == "sent").count(),
            "total_failed": db.query(SMSArchive).filter(SMSArchive.status == "failed").count(),
            "total_pending": db.query(SMSArchive).filter(SMSArchive.status == "pending").count(),
            "total_cost": db.query(SMSArchive).filter(SMSArchive.cost.isnot(None)).with_entities(
                db.func.sum(SMSArchive.cost)
            ).scalar() or 0,
            "today_sent": db.query(SMSArchive).filter(
                SMSArchive.status == "sent",
                db.func.date(SMSArchive.sent_at) == today
            ).count(),
            "week_sent": 0,  # Can be calculated similarly
            "month_sent": 0  # Can be calculated similarly
        }
        
        return stats

# Create instance
sms_service = SMSService()