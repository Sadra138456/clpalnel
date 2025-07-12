from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from typing import Optional
import math

from app.core.database import get_db
from app.crud.reservation import reservation_crud
from app.schemas.reservation import (
    ReservationCreate, ReservationUpdate, Reservation, 
    ReservationList, ReservationFilter
)
from app.api.dependencies import get_current_user
from app.models.user import User

router = APIRouter()

@router.post("/", response_model=Reservation)
async def create_reservation(
    reservation_in: ReservationCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Create new reservation"""
    reservation = reservation_crud.create(
        db=db, 
        reservation_in=reservation_in, 
        created_by=current_user.id
    )
    return Reservation.from_orm(reservation)

@router.get("/", response_model=ReservationList)
async def get_reservations(
    search: Optional[str] = Query(None, description="Search term"),
    owner_name: Optional[str] = Query(None, description="Owner name filter"),
    pet_name: Optional[str] = Query(None, description="Pet name filter"),
    vaccine_type: Optional[str] = Query(None, description="Vaccine type filter"),
    reminder_sent: Optional[bool] = Query(None, description="Reminder sent filter"),
    page: int = Query(1, ge=1, description="Page number"),
    size: int = Query(10, ge=1, le=100, description="Page size"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get reservations with filters and pagination"""
    
    filters = ReservationFilter(
        search=search,
        owner_name=owner_name,
        pet_name=pet_name,
        vaccine_type=vaccine_type,
        reminder_sent=reminder_sent,
        page=page,
        size=size
    )
    
    reservations, total = reservation_crud.get_multi_filtered(db=db, filters=filters)
    
    pages = math.ceil(total / size)
    
    return ReservationList(
        reservations=[Reservation.from_orm(r) for r in reservations],
        total=total,
        page=page,
        size=size,
        pages=pages
    )

@router.get("/{reservation_id}", response_model=Reservation)
async def get_reservation(
    reservation_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get specific reservation"""
    reservation = reservation_crud.get(db=db, reservation_id=reservation_id)
    if not reservation:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reservation not found"
        )
    return Reservation.from_orm(reservation)

@router.put("/{reservation_id}", response_model=Reservation)
async def update_reservation(
    reservation_id: int,
    reservation_in: ReservationUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Update reservation"""
    reservation = reservation_crud.update(
        db=db, 
        reservation_id=reservation_id, 
        reservation_in=reservation_in
    )
    if not reservation:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reservation not found"
        )
    return Reservation.from_orm(reservation)

@router.delete("/{reservation_id}")
async def delete_reservation(
    reservation_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Delete reservation"""
    success = reservation_crud.delete(db=db, reservation_id=reservation_id)
    if not success:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reservation not found"
        )
    return {"message": "Reservation deleted successfully"}

@router.get("/upcoming/appointments")
async def get_upcoming_appointments(
    days_ahead: int = Query(7, ge=1, le=30, description="Days ahead to check"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get upcoming appointments"""
    reservations = reservation_crud.get_upcoming_appointments(
        db=db, 
        days_ahead=days_ahead
    )
    return [Reservation.from_orm(r) for r in reservations]

@router.get("/pending/reminders")
async def get_pending_reminders(
    days_before: int = Query(1, ge=1, le=7, description="Days before appointment"),
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get reservations that need reminder SMS"""
    reservations = reservation_crud.get_pending_reminders(
        db=db, 
        days_before=days_before
    )
    return [Reservation.from_orm(r) for r in reservations]

@router.post("/{reservation_id}/mark-reminder-sent")
async def mark_reminder_sent(
    reservation_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Mark reminder as sent"""
    success = reservation_crud.mark_reminder_sent(
        db=db, 
        reservation_id=reservation_id
    )
    if not success:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reservation not found"
        )
    return {"message": "Reminder marked as sent"}

@router.get("/statistics/overview")
async def get_statistics(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Get reservation statistics"""
    stats = reservation_crud.get_statistics(db=db)
    return stats

@router.get("/search/by-phone/{phone_number}")
async def search_by_phone(
    phone_number: str,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    """Search reservations by phone number"""
    reservations = reservation_crud.get_by_phone(
        db=db, 
        phone_number=phone_number
    )
    return [Reservation.from_orm(r) for r in reservations]