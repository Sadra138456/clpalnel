from fastapi import FastAPI, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse
import os

from app.core.config import settings
from app.core.database import engine
from app.models import User, Reservation, SMSArchive

# Import API routers
from app.api.auth import router as auth_router
from app.api.users import router as users_router
from app.api.reservations import router as reservations_router
from app.api.sms import router as sms_router

# Create database tables
User.metadata.create_all(bind=engine)
Reservation.metadata.create_all(bind=engine)
SMSArchive.metadata.create_all(bind=engine)

# Create FastAPI app
app = FastAPI(
    title=settings.PROJECT_NAME,
    version=settings.VERSION,
    description="پنل مدیریت کلینیک دامپزشکی پارسیان",
    docs_url="/docs",
    redoc_url="/redoc"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.BACKEND_CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount static files
uploads_dir = "uploads"
if not os.path.exists(uploads_dir):
    os.makedirs(uploads_dir)
    os.makedirs(os.path.join(uploads_dir, "profile_pics"))

app.mount("/uploads", StaticFiles(directory=uploads_dir), name="uploads")

# Include API routers
app.include_router(
    auth_router, 
    prefix=f"{settings.API_V1_STR}/auth", 
    tags=["Authentication"]
)

app.include_router(
    users_router, 
    prefix=f"{settings.API_V1_STR}/users", 
    tags=["Users"]
)

app.include_router(
    reservations_router, 
    prefix=f"{settings.API_V1_STR}/reservations", 
    tags=["Reservations"]
)

app.include_router(
    sms_router, 
    prefix=f"{settings.API_V1_STR}/sms", 
    tags=["SMS"]
)

# Health check endpoint
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": settings.PROJECT_NAME,
        "version": settings.VERSION
    }

# Root endpoint
@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "message": f"Welcome to {settings.PROJECT_NAME} API",
        "version": settings.VERSION,
        "docs": "/docs",
        "redoc": "/redoc"
    }

# Serve frontend files (if they exist)
if os.path.exists("../frontend/dist"):
    app.mount("/", StaticFiles(directory="../frontend/dist", html=True), name="frontend")
    
    @app.get("/app/{path:path}")
    async def serve_frontend():
        return FileResponse("../frontend/dist/index.html")