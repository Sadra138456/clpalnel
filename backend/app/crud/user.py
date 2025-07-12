from sqlalchemy.orm import Session
from sqlalchemy import or_
from typing import Optional
from app.models.user import User
from app.schemas.user import UserCreate, UserUpdate, UserCreateOAuth
from app.core.security import get_password_hash, verify_password

class UserCRUD:
    def get(self, db: Session, user_id: int) -> Optional[User]:
        """Get user by ID"""
        return db.query(User).filter(User.id == user_id).first()
    
    def get_by_email(self, db: Session, email: str) -> Optional[User]:
        """Get user by email"""
        return db.query(User).filter(User.email == email).first()
    
    def get_by_google_id(self, db: Session, google_id: str) -> Optional[User]:
        """Get user by Google ID"""
        return db.query(User).filter(User.google_id == google_id).first()
    
    def create(self, db: Session, user_in: UserCreate) -> User:
        """Create new user"""
        hashed_password = get_password_hash(user_in.password)
        db_user = User(
            email=user_in.email,
            full_name=user_in.full_name,
            hashed_password=hashed_password,
            is_active=user_in.is_active,
            is_superuser=user_in.is_superuser
        )
        db.add(db_user)
        db.commit()
        db.refresh(db_user)
        return db_user
    
    def create_oauth(self, db: Session, user_in: UserCreateOAuth) -> User:
        """Create OAuth user"""
        db_user = User(
            email=user_in.email,
            full_name=user_in.full_name,
            google_id=user_in.google_id,
            oauth_provider=user_in.oauth_provider,
            profile_picture=user_in.profile_picture,
            is_active=True,
            is_superuser=False
        )
        db.add(db_user)
        db.commit()
        db.refresh(db_user)
        return db_user
    
    def update(self, db: Session, user_id: int, user_in: UserUpdate) -> Optional[User]:
        """Update user"""
        db_user = self.get(db, user_id)
        if not db_user:
            return None
        
        update_data = user_in.dict(exclude_unset=True)
        
        # Hash password if provided
        if "password" in update_data:
            update_data["hashed_password"] = get_password_hash(update_data.pop("password"))
        
        for field, value in update_data.items():
            setattr(db_user, field, value)
        
        db.commit()
        db.refresh(db_user)
        return db_user
    
    def authenticate(self, db: Session, email: str, password: str) -> Optional[User]:
        """Authenticate user"""
        user = self.get_by_email(db, email)
        if not user or not user.hashed_password:
            return None
        if not verify_password(password, user.hashed_password):
            return None
        return user
    
    def is_active(self, user: User) -> bool:
        """Check if user is active"""
        return user.is_active
    
    def is_superuser(self, user: User) -> bool:
        """Check if user is superuser"""
        return user.is_superuser
    
    def delete(self, db: Session, user_id: int) -> bool:
        """Delete user (soft delete by setting is_active=False)"""
        db_user = self.get(db, user_id)
        if not db_user:
            return False
        
        db_user.is_active = False
        db.commit()
        return True
    
    def get_multi(self, db: Session, skip: int = 0, limit: int = 100) -> list[User]:
        """Get multiple users"""
        return db.query(User).filter(User.is_active == True).offset(skip).limit(limit).all()
    
    def search(self, db: Session, query: str, skip: int = 0, limit: int = 100) -> list[User]:
        """Search users by name or email"""
        return db.query(User).filter(
            User.is_active == True,
            or_(
                User.full_name.ilike(f"%{query}%"),
                User.email.ilike(f"%{query}%")
            )
        ).offset(skip).limit(limit).all()

# Create instance
user_crud = UserCRUD()