from sqlalchemy import Column, Integer, String, DateTime, Text, Boolean, ForeignKey
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from app.core.database import Base

class SMSArchive(Base):
    __tablename__ = "sms_archive"
    
    id = Column(Integer, primary_key=True, index=True)
    
    # SMS content
    message = Column(Text, nullable=False)
    recipient_phone = Column(String(20), nullable=False, index=True)
    recipient_name = Column(String(255), nullable=False)
    
    # SMS details
    sms_type = Column(String(50), nullable=False, default="manual")  # manual, auto_reminder
    status = Column(String(50), nullable=False, default="sent")  # sent, failed, pending
    
    # External SMS provider details
    provider_message_id = Column(String(255), nullable=True)
    provider_status = Column(String(100), nullable=True)
    cost = Column(Integer, nullable=True)  # در ریال
    
    # Relations
    reservation_id = Column(Integer, ForeignKey("reservations.id"), nullable=True)
    sent_by = Column(Integer, ForeignKey("users.id"), nullable=False)
    
    # Timestamps
    sent_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)
    delivered_at = Column(DateTime(timezone=True), nullable=True)
    
    # Relationships
    reservation = relationship("Reservation", back_populates="sms_records")
    sent_by_user = relationship("User", back_populates="sms_archive")