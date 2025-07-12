from sqlalchemy.orm import Session
from sqlalchemy import or_, and_, func
from typing import Optional, List, Tuple
from datetime import datetime, timedelta
from app.models.reservation import Reservation
from app.schemas.reservation import ReservationCreate, ReservationUpdate, ReservationFilter

class ReservationCRUD:
    def get(self, db: Session, reservation_id: int) -> Optional[Reservation]:
        """Get reservation by ID"""
        return db.query(Reservation).filter(
            Reservation.id == reservation_id,
            Reservation.is_active == True
        ).first()
    
    def create(self, db: Session, reservation_in: ReservationCreate, created_by: int) -> Reservation:
        """Create new reservation"""
        db_reservation = Reservation(
            **reservation_in.dict(),
            created_by=created_by
        )
        db.add(db_reservation)
        db.commit()
        db.refresh(db_reservation)
        return db_reservation
    
    def update(self, db: Session, reservation_id: int, reservation_in: ReservationUpdate) -> Optional[Reservation]:
        """Update reservation"""
        db_reservation = self.get(db, reservation_id)
        if not db_reservation:
            return None
        
        update_data = reservation_in.dict(exclude_unset=True)
        
        for field, value in update_data.items():
            setattr(db_reservation, field, value)
        
        db.commit()
        db.refresh(db_reservation)
        return db_reservation
    
    def delete(self, db: Session, reservation_id: int) -> bool:
        """Delete reservation (soft delete)"""
        db_reservation = self.get(db, reservation_id)
        if not db_reservation:
            return False
        
        db_reservation.is_active = False
        db.commit()
        return True
    
    def get_multi_filtered(
        self, 
        db: Session, 
        filters: ReservationFilter
    ) -> Tuple[List[Reservation], int]:
        """Get reservations with filters and pagination"""
        query = db.query(Reservation).filter(Reservation.is_active == True)
        
        # Apply filters
        if filters.search:
            search_term = f"%{filters.search}%"
            query = query.filter(
                or_(
                    Reservation.pet_name.ilike(search_term),
                    Reservation.owner_name.ilike(search_term),
                    Reservation.vaccine_type.ilike(search_term),
                    Reservation.notes.ilike(search_term)
                )
            )
        
        if filters.owner_name:
            query = query.filter(Reservation.owner_name.ilike(f"%{filters.owner_name}%"))
        
        if filters.pet_name:
            query = query.filter(Reservation.pet_name.ilike(f"%{filters.pet_name}%"))
        
        if filters.vaccine_type:
            query = query.filter(Reservation.vaccine_type.ilike(f"%{filters.vaccine_type}%"))
        
        if filters.visit_date_from:
            query = query.filter(Reservation.visit_date >= filters.visit_date_from)
        
        if filters.visit_date_to:
            query = query.filter(Reservation.visit_date <= filters.visit_date_to)
        
        if filters.next_visit_date_from:
            query = query.filter(Reservation.next_visit_date >= filters.next_visit_date_from)
        
        if filters.next_visit_date_to:
            query = query.filter(Reservation.next_visit_date <= filters.next_visit_date_to)
        
        if filters.reminder_sent is not None:
            query = query.filter(Reservation.reminder_sent == filters.reminder_sent)
        
        # Get total count
        total = query.count()
        
        # Apply pagination and ordering
        reservations = query.order_by(Reservation.created_at.desc()).offset(
            (filters.page - 1) * filters.size
        ).limit(filters.size).all()
        
        return reservations, total
    
    def get_upcoming_appointments(self, db: Session, days_ahead: int = 7) -> List[Reservation]:
        """Get upcoming appointments within specified days"""
        end_date = datetime.now() + timedelta(days=days_ahead)
        return db.query(Reservation).filter(
            Reservation.is_active == True,
            Reservation.next_visit_date >= datetime.now(),
            Reservation.next_visit_date <= end_date,
            Reservation.reminder_sent == False
        ).all()
    
    def get_pending_reminders(self, db: Session, days_before: int = 1) -> List[Reservation]:
        """Get reservations that need reminder SMS"""
        target_date = datetime.now() + timedelta(days=days_before)
        return db.query(Reservation).filter(
            Reservation.is_active == True,
            Reservation.next_visit_date.isnot(None),
            func.date(Reservation.next_visit_date) == target_date.date(),
            Reservation.reminder_sent == False
        ).all()
    
    def mark_reminder_sent(self, db: Session, reservation_id: int) -> bool:
        """Mark reminder as sent"""
        db_reservation = self.get(db, reservation_id)
        if not db_reservation:
            return False
        
        db_reservation.reminder_sent = True
        db.commit()
        return True
    
    def get_statistics(self, db: Session) -> dict:
        """Get reservation statistics"""
        total_reservations = db.query(Reservation).filter(Reservation.is_active == True).count()
        
        today = datetime.now().date()
        today_reservations = db.query(Reservation).filter(
            Reservation.is_active == True,
            func.date(Reservation.visit_date) == today
        ).count()
        
        # This week
        week_start = today - timedelta(days=today.weekday())
        week_end = week_start + timedelta(days=6)
        week_reservations = db.query(Reservation).filter(
            Reservation.is_active == True,
            func.date(Reservation.visit_date) >= week_start,
            func.date(Reservation.visit_date) <= week_end
        ).count()
        
        # This month
        month_start = today.replace(day=1)
        month_reservations = db.query(Reservation).filter(
            Reservation.is_active == True,
            func.date(Reservation.visit_date) >= month_start
        ).count()
        
        # Pending reminders
        pending_reminders = db.query(Reservation).filter(
            Reservation.is_active == True,
            Reservation.next_visit_date.isnot(None),
            Reservation.next_visit_date >= datetime.now(),
            Reservation.reminder_sent == False
        ).count()
        
        return {
            "total_reservations": total_reservations,
            "today_reservations": today_reservations,
            "week_reservations": week_reservations,
            "month_reservations": month_reservations,
            "pending_reminders": pending_reminders
        }
    
    def get_by_phone(self, db: Session, phone_number: str) -> List[Reservation]:
        """Get reservations by phone number"""
        return db.query(Reservation).filter(
            Reservation.is_active == True,
            Reservation.phone_number == phone_number
        ).order_by(Reservation.created_at.desc()).all()

# Create instance
reservation_crud = ReservationCRUD()