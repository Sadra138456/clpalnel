from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from sqlalchemy.orm import Session
from typing import Optional
import os
import uuid
from PIL import Image
import io

from app.core.database import get_db
from app.core.config import settings
from app.crud.user import user_crud
from app.schemas.user import User, UserUpdate
from app.api.dependencies import get_current_user, get_current_superuser
from app.models.user import User as UserModel

router = APIRouter()

@router.get("/me", response_model=User)
async def get_current_user_profile(
    current_user: UserModel = Depends(get_current_user)
):
    """Get current user profile"""
    return User.from_orm(current_user)

@router.put("/me", response_model=User)
async def update_current_user(
    user_update: UserUpdate,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_user)
):
    """Update current user profile"""
    updated_user = user_crud.update(
        db=db, 
        user_id=current_user.id, 
        user_in=user_update
    )
    
    if not updated_user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    return User.from_orm(updated_user)

@router.post("/me/upload-avatar", response_model=dict)
async def upload_user_avatar(
    file: UploadFile = File(...),
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_user)
):
    """Upload user avatar"""
    
    # Validate file type
    if not file.content_type.startswith("image/"):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="File must be an image"
        )
    
    # Validate file size (5MB max)
    file_size = 0
    content = await file.read()
    file_size = len(content)
    
    if file_size > settings.MAX_FILE_SIZE:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="File size too large. Maximum 5MB allowed."
        )
    
    # Generate unique filename
    file_extension = os.path.splitext(file.filename)[1].lower()
    if file_extension not in ['.png', '.jpg', '.jpeg', '.gif']:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Invalid file type. Only PNG, JPG, JPEG, GIF allowed."
        )
    
    unique_filename = f"{uuid.uuid4()}{file_extension}"
    file_path = os.path.join(settings.UPLOAD_FOLDER, "profile_pics", unique_filename)
    
    # Create directory if it doesn't exist
    os.makedirs(os.path.dirname(file_path), exist_ok=True)
    
    # Save and resize image
    try:
        with Image.open(io.BytesIO(content)) as image:
            # Convert RGBA to RGB if necessary
            if image.mode in ("RGBA", "P"):
                image = image.convert("RGB")
            
            # Resize to 300x300
            image = image.resize((300, 300), Image.Resampling.LANCZOS)
            image.save(file_path, "JPEG", quality=85)
        
        # Update user profile picture URL
        profile_picture_url = f"/uploads/profile_pics/{unique_filename}"
        user_update = UserUpdate(profile_picture=profile_picture_url)
        
        updated_user = user_crud.update(
            db=db, 
            user_id=current_user.id, 
            user_in=user_update
        )
        
        return {
            "message": "Avatar uploaded successfully",
            "profile_picture_url": profile_picture_url
        }
        
    except Exception as e:
        # Clean up file if something went wrong
        if os.path.exists(file_path):
            os.remove(file_path)
        
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to process image"
        )

@router.get("/", response_model=list[User])
async def get_users(
    skip: int = 0,
    limit: int = 100,
    search: Optional[str] = None,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Get all users (admin only)"""
    if search:
        users = user_crud.search(db=db, query=search, skip=skip, limit=limit)
    else:
        users = user_crud.get_multi(db=db, skip=skip, limit=limit)
    
    return [User.from_orm(user) for user in users]

@router.get("/{user_id}", response_model=User)
async def get_user(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Get specific user (admin only)"""
    user = user_crud.get(db=db, user_id=user_id)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    return User.from_orm(user)

@router.put("/{user_id}", response_model=User)
async def update_user(
    user_id: int,
    user_update: UserUpdate,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Update user (admin only)"""
    updated_user = user_crud.update(
        db=db, 
        user_id=user_id, 
        user_in=user_update
    )
    
    if not updated_user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    return User.from_orm(updated_user)

@router.delete("/{user_id}")
async def delete_user(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Delete user (admin only)"""
    # Prevent self-deletion
    if user_id == current_user.id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Cannot delete your own account"
        )
    
    success = user_crud.delete(db=db, user_id=user_id)
    if not success:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    return {"message": "User deleted successfully"}

@router.post("/{user_id}/toggle-active")
async def toggle_user_active(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Toggle user active status (admin only)"""
    user = user_crud.get(db=db, user_id=user_id)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    # Prevent deactivating self
    if user_id == current_user.id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Cannot deactivate your own account"
        )
    
    user_update = UserUpdate(is_active=not user.is_active)
    updated_user = user_crud.update(
        db=db, 
        user_id=user_id, 
        user_in=user_update
    )
    
    status_text = "activated" if updated_user.is_active else "deactivated"
    return {
        "message": f"User {status_text} successfully",
        "is_active": updated_user.is_active
    }

@router.get("/statistics/overview")
async def get_user_statistics(
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_superuser)
):
    """Get user statistics (admin only)"""
    total_users = len(user_crud.get_multi(db=db, skip=0, limit=1000))  # Quick count
    active_users = len([u for u in user_crud.get_multi(db=db, skip=0, limit=1000) if u.is_active])
    oauth_users = len([u for u in user_crud.get_multi(db=db, skip=0, limit=1000) if u.google_id])
    
    return {
        "total_users": total_users,
        "active_users": active_users,
        "inactive_users": total_users - active_users,
        "oauth_users": oauth_users,
        "regular_users": total_users - oauth_users
    }