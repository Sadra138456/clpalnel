from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime
from decimal import Decimal

# Base Reservation schema
class ReservationBase(BaseModel):
    pet_name: str = Field(..., min_length=1, max_length=255)
    breed: Optional[str] = Field(None, max_length=255)
    weight: Optional[Decimal] = Field(None, ge=0, le=999.99)
    birth_year: Optional[int] = Field(None, ge=1900, le=2024)
    owner_name: str = Field(..., min_length=1, max_length=255)
    phone_number: str = Field(..., min_length=10, max_length=20)
    visit_date: datetime
    next_visit_date: Optional[datetime] = None
    vaccine_type: str = Field(..., min_length=1, max_length=255)
    price: Optional[Decimal] = Field(None, ge=0)
    notes: Optional[str] = None

# Schema for creating reservation
class ReservationCreate(ReservationBase):
    pass

# Schema for updating reservation
class ReservationUpdate(BaseModel):
    pet_name: Optional[str] = Field(None, min_length=1, max_length=255)
    breed: Optional[str] = Field(None, max_length=255)
    weight: Optional[Decimal] = Field(None, ge=0, le=999.99)
    birth_year: Optional[int] = Field(None, ge=1900, le=2024)
    owner_name: Optional[str] = Field(None, min_length=1, max_length=255)
    phone_number: Optional[str] = Field(None, min_length=10, max_length=20)
    visit_date: Optional[datetime] = None
    next_visit_date: Optional[datetime] = None
    vaccine_type: Optional[str] = Field(None, min_length=1, max_length=255)
    price: Optional[Decimal] = Field(None, ge=0)
    notes: Optional[str] = None
    is_active: Optional[bool] = None
    reminder_sent: Optional[bool] = None

# Schema for reservation response
class Reservation(ReservationBase):
    id: int
    is_active: bool
    reminder_sent: bool
    created_by: int
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True

# Schema for reservation list with pagination
class ReservationList(BaseModel):
    reservations: list[Reservation]
    total: int
    page: int
    size: int
    pages: int

# Schema for reservation search/filter
class ReservationFilter(BaseModel):
    search: Optional[str] = None
    owner_name: Optional[str] = None
    pet_name: Optional[str] = None
    vaccine_type: Optional[str] = None
    visit_date_from: Optional[datetime] = None
    visit_date_to: Optional[datetime] = None
    next_visit_date_from: Optional[datetime] = None
    next_visit_date_to: Optional[datetime] = None
    is_active: Optional[bool] = True
    reminder_sent: Optional[bool] = None
    page: int = Field(1, ge=1)
    size: int = Field(10, ge=1, le=100)