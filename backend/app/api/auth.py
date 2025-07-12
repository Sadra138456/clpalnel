from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.core.security import create_access_token
from app.crud.user import user_crud
from app.schemas.user import UserCreate, UserCreateOAuth, Token, User, UserLogin
from app.api.dependencies import get_current_user

router = APIRouter()

@router.post("/login", response_model=Token)
async def login(
    user_credentials: UserLogin,
    db: Session = Depends(get_db)
):
    """Login with email and password"""
    user = user_crud.authenticate(
        db, 
        email=user_credentials.email, 
        password=user_credentials.password
    )
    
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect email or password"
        )
    
    if not user_crud.is_active(user):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Inactive user"
        )
    
    access_token = create_access_token(subject=user.id)
    
    return Token(
        access_token=access_token,
        token_type="bearer",
        user=User.from_orm(user)
    )

@router.post("/register", response_model=Token)
async def register(
    user_in: UserCreate,
    db: Session = Depends(get_db)
):
    """Register new user"""
    # Check if user already exists
    existing_user = user_crud.get_by_email(db, email=user_in.email)
    if existing_user:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Email already registered"
        )
    
    # Create new user
    user = user_crud.create(db, user_in=user_in)
    
    # Create access token
    access_token = create_access_token(subject=user.id)
    
    return Token(
        access_token=access_token,
        token_type="bearer",
        user=User.from_orm(user)
    )

@router.post("/google", response_model=Token)
async def google_auth(
    user_data: UserCreateOAuth,
    db: Session = Depends(get_db)
):
    """Google OAuth authentication"""
    # Check if user exists by Google ID
    user = None
    if user_data.google_id:
        user = user_crud.get_by_google_id(db, google_id=user_data.google_id)
    
    # If not found by Google ID, check by email
    if not user:
        user = user_crud.get_by_email(db, email=user_data.email)
        
        # If user exists with email but no Google ID, update with Google info
        if user:
            user.google_id = user_data.google_id
            user.oauth_provider = "google"
            if user_data.profile_picture:
                user.profile_picture = user_data.profile_picture
            db.commit()
            db.refresh(user)
    
    # If user doesn't exist, create new OAuth user
    if not user:
        user_data.oauth_provider = "google"
        user = user_crud.create_oauth(db, user_in=user_data)
    
    if not user_crud.is_active(user):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Inactive user"
        )
    
    # Create access token
    access_token = create_access_token(subject=user.id)
    
    return Token(
        access_token=access_token,
        token_type="bearer",
        user=User.from_orm(user)
    )

@router.get("/me", response_model=User)
async def get_current_user_info(
    current_user: User = Depends(get_current_user)
):
    """Get current user information"""
    return User.from_orm(current_user)

@router.post("/refresh", response_model=Token)
async def refresh_token(
    current_user: User = Depends(get_current_user)
):
    """Refresh access token"""
    access_token = create_access_token(subject=current_user.id)
    
    return Token(
        access_token=access_token,
        token_type="bearer",
        user=User.from_orm(current_user)
    )

@router.post("/test-token", response_model=User)
async def test_token(
    current_user: User = Depends(get_current_user)
):
    """Test token validity"""
    return User.from_orm(current_user)