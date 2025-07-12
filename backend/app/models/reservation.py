from sqlalchemy import Column, Integer, String, DateTime, Text, Boolean, ForeignKey, Numeric
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from app.core.database import Base

class Reservation(Base):
    __tablename__ = "reservations"
    
    id = Column(Integer, primary_key=True, index=True)
    
    # Pet information
    pet_name = Column(String(255), nullable=False, index=True)
    breed = Column(String(255), nullable=True)
    weight = Column(Numeric(5, 2), nullable=True)  # kg
    birth_year = Column(Integer, nullable=True)
    
    # Owner information
    owner_name = Column(String(255), nullable=False, index=True)
    phone_number = Column(String(20), nullable=False)
    
    # Appointment information
    visit_date = Column(DateTime, nullable=False, index=True)
    next_visit_date = Column(DateTime, nullable=True, index=True)
    vaccine_type = Column(String(255), nullable=False)
    price = Column(Numeric(10, 0), nullable=True)  # Toman
    
    # Additional information
    notes = Column(Text, nullable=True)
    
    # Status
    is_active = Column(Boolean, default=True)
    reminder_sent = Column(Boolean, default=False)
    
    # Foreign keys
    created_by = Column(Integer, ForeignKey("users.id"), nullable=False)
    
    # Timestamps
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    created_by_user = relationship("User", back_populates="reservations")
    sms_records = relationship("SMSArchive", back_populates="reservation")