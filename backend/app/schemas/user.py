from pydantic import BaseModel, EmailStr, Field
from typing import Optional
from datetime import datetime

# Base User schema
class UserBase(BaseModel):
    email: EmailStr
    full_name: str = Field(..., min_length=2, max_length=255)
    is_active: bool = True
    is_superuser: bool = False

# Schema for creating user
class UserCreate(UserBase):
    password: str = Field(..., min_length=6, max_length=255)

# Schema for OAuth user creation
class UserCreateOAuth(BaseModel):
    email: EmailStr
    full_name: str
    google_id: Optional[str] = None
    oauth_provider: Optional[str] = None
    profile_picture: Optional[str] = None

# Schema for updating user
class UserUpdate(BaseModel):
    email: Optional[EmailStr] = None
    full_name: Optional[str] = Field(None, min_length=2, max_length=255)
    password: Optional[str] = Field(None, min_length=6, max_length=255)
    is_active: Optional[bool] = None
    profile_picture: Optional[str] = None

# Schema for user response
class User(UserBase):
    id: int
    profile_picture: Optional[str] = None
    google_id: Optional[str] = None
    oauth_provider: Optional[str] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True

# Schema for user login
class UserLogin(BaseModel):
    email: EmailStr
    password: str

# Schema for token response
class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: User

# Schema for password reset
class PasswordReset(BaseModel):
    email: EmailStr

class PasswordResetConfirm(BaseModel):
    token: str
    new_password: str = Field(..., min_length=6, max_length=255)