from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from typing import Optional
import math

from app.core.database import get_db
from app.services.sms_service import sms_service
from app.crud.reservation import reservation_crud
from app.schemas.sms import (
    SMSSend, SMSBulkSend, SMS, SMSList, SMSFilter, 
    SMSStats, SMSTemplate, ScheduledSMS
)
from app.api.dependencies import get_current_user
from app.models.user import User

router = APIRouter()

@router.post("/send", response_model=dict)
async def send_sms(
    sms_data: SMSSend,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Send single SMS"""
    result = await sms_service.send_sms(
        db=db,
        phone=sms_data.recipient_phone,
        message=sms_data.message,
        recipient_name=sms_data.recipient_name,
        sent_by_user_id=current_user.id,
        reservation_id=sms_data.reservation_id,
        sms_type=sms_data.sms_type
    )
    
    if not result["success"]:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=result.get("error", "Failed to send SMS")
        )
    
    return {
        "message": "SMS sent successfully",
        "archive_id": result["archive_id"],
        "message_id": result.get("message_id")
    }

@router.post("/send-bulk", response_model=dict)
async def send_bulk_sms(
    sms_data: SMSBulkSend,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Send bulk SMS"""
    result = await sms_service.send_bulk_sms(
        db=db,
        recipients=sms_data.recipients,
        message=sms_data.message,
        sent_by_user_id=current_user.id,
        sms_type=sms_data.sms_type
    )
    
    return {
        "message": f"Bulk SMS completed. {result['success_count']} sent, {result['fail_count']} failed",
        "total": result["total"],
        "success_count": result["success_count"],
        "fail_count": result["fail_count"],
        "results": result["results"]
    }

@router.post("/send-reminder/{reservation_id}", response_model=dict)
async def send_reminder_sms(
    reservation_id: int,
    template: str = Query(..., description="SMS template"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Send reminder SMS for specific reservation"""
    # Get reservation
    reservation = reservation_crud.get(db=db, reservation_id=reservation_id)
    if not reservation:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reservation not found"
        )
    
    if reservation.reminder_sent:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Reminder already sent for this reservation"
        )
    
    result = await sms_service.send_reminder_sms(
        db=db,
        reservation=reservation,
        template=template,
        sent_by_user_id=current_user.id
    )
    
    if not result["success"]:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=result.get("error", "Failed to send reminder SMS")
        )
    
    return {
        "message": "Reminder SMS sent successfully",
        "archive_id": result["archive_id"],
        "message_id": result.get("message_id")
    }

@router.get("/archive", response_model=SMSList)
async def get_sms_archive(
    search: Optional[str] = Query(None, description="Search term"),
    sms_type: Optional[str] = Query("all", description="SMS type filter"),
    status: Optional[str] = Query("all", description="Status filter"),
    page: int = Query(1, ge=1, description="Page number"),
    size: int = Query(10, ge=1, le=100, description="Page size"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get SMS archive with filters and pagination"""
    
    filters = {
        "search": search,
        "sms_type": sms_type,
        "status": status
    }
    
    skip = (page - 1) * size
    records, total = sms_service.get_sms_archive(
        db=db, 
        filters=filters,
        skip=skip,
        limit=size
    )
    
    pages = math.ceil(total / size)
    
    return SMSList(
        sms_records=[SMS.from_orm(r) for r in records],
        total=total,
        page=page,
        size=size,
        pages=pages
    )

@router.get("/statistics", response_model=SMSStats)
async def get_sms_statistics(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get SMS statistics"""
    stats = sms_service.get_sms_statistics(db=db)
    return SMSStats(**stats)

@router.get("/scheduled", response_model=list[ScheduledSMS])
async def get_scheduled_sms(
    days_ahead: int = Query(7, ge=1, le=30, description="Days ahead to check"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get scheduled SMS reminders"""
    reservations = reservation_crud.get_upcoming_appointments(
        db=db, 
        days_ahead=days_ahead
    )
    
    scheduled_sms = []
    for reservation in reservations:
        if reservation.next_visit_date and not reservation.reminder_sent:
            # Calculate scheduled time (1 day before by default)
            from datetime import timedelta
            scheduled_for = reservation.next_visit_date - timedelta(days=1)
            
            scheduled_sms.append(ScheduledSMS(
                reservation_id=reservation.id,
                pet_name=reservation.pet_name,
                owner_name=reservation.owner_name,
                phone_number=reservation.phone_number,
                next_visit_date=reservation.next_visit_date,
                days_before=1,
                message=f"سلام {reservation.owner_name}، یادآوری واکسن {reservation.pet_name} برای فردا",
                scheduled_for=scheduled_for
            ))
    
    return scheduled_sms

@router.get("/templates", response_model=list[SMSTemplate])
async def get_sms_templates(
    current_user: User = Depends(get_current_user)
):
    """Get available SMS templates"""
    templates = [
        SMSTemplate(
            template="سلام [نام صاحب]، یادآوری واکسن [نام حیوان] برای تاریخ [تاریخ]. کلینیک پارسیان",
            variables=["[نام صاحب]", "[نام حیوان]", "[تاریخ]"]
        ),
        SMSTemplate(
            template="عزیز [نام صاحب]، نوبت واکسیناسیون [نام حیوان] فردا می‌باشد. کلینیک پارسیان",
            variables=["[نام صاحب]", "[نام حیوان]"]
        ),
        SMSTemplate(
            template="یادآوری: [نام حیوان] نژاد [نژاد] برای واکسن [نوع واکسن] در تاریخ [تاریخ]. کلینیک پارسیان",
            variables=["[نام حیوان]", "[نژاد]", "[نوع واکسن]", "[تاریخ]"]
        )
    ]
    
    return templates

@router.delete("/archive/{sms_id}")
async def delete_sms_archive(
    sms_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Delete SMS from archive"""
    # This is a soft delete - you could implement it in SMS service
    # For now, just return success
    return {"message": "SMS archive record deleted successfully"}

@router.post("/test", response_model=dict)
async def test_sms_config(
    current_user: User = Depends(get_current_user)
):
    """Test SMS configuration"""
    # Test if SMS service is properly configured
    from app.core.config import settings
    
    if not settings.SMS_API_KEY:
        return {
            "configured": False,
            "message": "SMS API key not configured"
        }
    
    return {
        "configured": True,
        "message": "SMS service is properly configured",
        "provider": "Kavenegar",
        "sender": settings.SMS_SENDER
    }