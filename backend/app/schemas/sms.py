from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime

# Base SMS schema
class SMSBase(BaseModel):
    message: str = Field(..., min_length=1, max_length=2000)
    recipient_phone: str = Field(..., min_length=10, max_length=20)
    recipient_name: str = Field(..., min_length=1, max_length=255)

# Schema for sending SMS
class SMSSend(SMSBase):
    reservation_id: Optional[int] = None
    sms_type: str = Field("manual", regex="^(manual|auto_reminder)$")

# Schema for bulk SMS sending
class SMSBulkSend(BaseModel):
    message: str = Field(..., min_length=1, max_length=2000)
    recipients: list[dict] = Field(..., min_items=1, max_items=100)  # [{phone, name, reservation_id?}]
    sms_type: str = Field("manual", regex="^(manual|auto_reminder)$")

# Schema for SMS response
class SMS(SMSBase):
    id: int
    sms_type: str
    status: str
    provider_message_id: Optional[str] = None
    provider_status: Optional[str] = None
    cost: Optional[int] = None
    reservation_id: Optional[int] = None
    sent_by: int
    sent_at: datetime
    delivered_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True

# Schema for SMS list with pagination
class SMSList(BaseModel):
    sms_records: list[SMS]
    total: int
    page: int
    size: int
    pages: int

# Schema for SMS search/filter
class SMSFilter(BaseModel):
    search: Optional[str] = None  # Search in message or recipient_name
    recipient_phone: Optional[str] = None
    recipient_name: Optional[str] = None
    sms_type: Optional[str] = Field(None, regex="^(manual|auto_reminder|all)$")
    status: Optional[str] = Field(None, regex="^(sent|failed|pending|all)$")
    reservation_id: Optional[int] = None
    sent_by: Optional[int] = None
    sent_date_from: Optional[datetime] = None
    sent_date_to: Optional[datetime] = None
    page: int = Field(1, ge=1)
    size: int = Field(10, ge=1, le=100)

# Schema for SMS statistics
class SMSStats(BaseModel):
    total_sent: int
    total_failed: int
    total_pending: int
    total_cost: int  # Total cost in Rial
    today_sent: int
    week_sent: int
    month_sent: int

# Schema for SMS template
class SMSTemplate(BaseModel):
    template: str = Field(..., min_length=1, max_length=2000)
    variables: list[str] = []  # Available variables like [نام حیوان], [تاریخ], etc.

# Schema for scheduled SMS
class ScheduledSMS(BaseModel):
    reservation_id: int
    pet_name: str
    owner_name: str
    phone_number: str
    next_visit_date: datetime
    days_before: int
    message: str
    scheduled_for: datetime