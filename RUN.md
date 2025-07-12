# 🚀 راهنمای اجرای سیستم کلینیک پارسیان

## 📋 پیش‌نیازها

- **Docker & Docker Compose** (روش توصیه شده)
- یا **Python 3.11+** + **Node.js 18+** + **PostgreSQL 15+** + **Redis 7+**

## 🎯 راه‌اندازی سریع (5 دقیقه)

### 1️⃣ کلون پروژه
```bash
git clone <repository-url>
cd parsian-clinic-system
```

### 2️⃣ تنظیم متغیرهای محیطی
```bash
# کپی کردن فایل نمونه
cp backend/.env.example backend/.env

# ویرایش تنظیمات (ضروری!)
nano backend/.env
```

**تنظیمات ضروری در `.env`:**
```env
# امنیت - تغییر دهید!
SECRET_KEY=your-very-long-secret-key-minimum-32-characters
POSTGRES_PASSWORD=your-strong-database-password

# پیامک (اختیاری)
SMS_API_KEY=your-kavenegar-api-key

# دامنه شما
BACKEND_CORS_ORIGINS=["https://yourdomain.com","http://localhost:3000"]
```

### 3️⃣ اجرای سیستم کامل
```bash
# ساخت و اجرای همه سرویس‌ها
docker-compose up -d

# مشاهده وضعیت
docker-compose ps

# مشاهده logs
docker-compose logs -f
```

### 4️⃣ بررسی اجرا
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **API Docs**: http://localhost:8000/docs
- **Database Admin**: http://localhost:8080 (Adminer)

## 🔧 راه‌اندازی Development

### Backend (FastAPI)
```bash
cd backend

# ایجاد virtual environment
python -m venv venv
source venv/bin/activate  # Linux/Mac
# venv\Scripts\activate   # Windows

# نصب dependencies
pip install -r requirements.txt

# اجرای database migrations
alembic upgrade head

# اجرای server
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

### Frontend
```bash
cd frontend

# نصب dependencies
npm install

# اجرای development server
npm run dev

# یا برای production build
npm run build
npm run preview
```

## 🗄️ راه‌اندازی Database

### با Docker (آسان)
```bash
# PostgreSQL و Redis خودکار راه‌اندازی می‌شوند
docker-compose up db redis -d
```

### دستی
```bash
# PostgreSQL
sudo -u postgres createuser parsian_user
sudo -u postgres createdb parsian_clinic -O parsian_user

# Redis
sudo systemctl start redis
```

## 📱 تنظیم SMS (کاوه‌نگار)

1. **ثبت نام در کاوه‌نگار**: https://panel.kavenegar.com
2. **دریافت API Key** از پنل کاربری
3. **تنظیم در `.env`**:
   ```env
   SMS_API_KEY=your-api-key-here
   SMS_SENDER=10004346
   ```

## 🌐 راه‌اندازی Production

### 1️⃣ تنظیم SSL
```bash
# ایجاد پوشه SSL
mkdir ssl
cd ssl

# تولید self-signed certificate (تست)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout privkey.pem -out fullchain.pem

# یا استفاده از Let's Encrypt
certbot certonly --webroot -w /var/www/html -d yourdomain.com
```

### 2️⃣ اجرای Production
```bash
# استفاده از docker-compose production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 3️⃣ تنظیم Nginx (اختیاری)
```nginx
# /etc/nginx/sites-available/parsian-clinic
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location /api/ {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 🔑 حساب پیش‌فرض

بعد از اولین اجرا، یک حساب admin ایجاد کنید:

```bash
# از طریق API
curl -X POST "http://localhost:8000/api/v1/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@parsian.com",
    "password": "admin123456",
    "full_name": "مدیر کلینیک",
    "is_superuser": true
  }'
```

یا وارد شده و از رابط کاربری ثبت نام کنید.

## 🛠️ دستورات مفید

### Docker
```bash
# مشاهده logs
docker-compose logs -f backend
docker-compose logs -f frontend

# ری‌استارت سرویس خاص
docker-compose restart backend

# پاک کردن volumes (حذف دیتابیس!)
docker-compose down -v

# rebuild کردن images
docker-compose build --no-cache
```

### Database
```bash
# ورود به PostgreSQL
docker-compose exec db psql -U postgres -d parsian_clinic

# بک‌آپ دیتابیس
docker-compose exec -T db pg_dump -U postgres parsian_clinic > backup.sql

# ریستور دیتابیس
docker-compose exec -T db psql -U postgres parsian_clinic < backup.sql
```

### Monitoring
```bash
# بررسی سلامت سیستم
curl http://localhost:8000/health

# آمار استفاده منابع
docker stats

# مشاهده processes
docker-compose top
```

## 🐛 عیب‌یابی

### مشکلات رایج

1. **پورت در حال استفاده**
   ```bash
   # بررسی پورت‌های در حال استفاده
   sudo netstat -tlnp | grep :8000
   
   # تغییر پورت در docker-compose.yml
   ports:
     - "8001:8000"  # پورت جدید
   ```

2. **دیتابیس متصل نمی‌شود**
   ```bash
   # بررسی logs دیتابیس
   docker-compose logs db
   
   # تست اتصال
   docker-compose exec backend python -c "
   from app.core.database import engine
   print('Database connection:', engine.connect())
   "
   ```

3. **Frontend لود نمی‌شود**
   ```bash
   # بررسی logs frontend
   docker-compose logs frontend
   
   # rebuild frontend
   docker-compose build frontend
   ```

4. **SMS ارسال نمی‌شود**
   ```bash
   # تست تنظیمات SMS
   curl -X POST "http://localhost:8000/api/v1/sms/test" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

### Performance Tuning

```bash
# افزایش memory برای PostgreSQL
# در docker-compose.yml
environment:
  - POSTGRES_SHARED_BUFFERS=256MB
  - POSTGRES_EFFECTIVE_CACHE_SIZE=1GB

# تنظیم workers برای FastAPI
command: gunicorn app.main:app -w 4 -k uvicorn.workers.UvicornWorker
```

## 📊 Monitoring و Logs

### Logs مفید
```bash
# همه logs
docker-compose logs -f

# فقط errors
docker-compose logs --tail=100 | grep ERROR

# مشاهده real-time
docker-compose logs -f --tail=0
```

### Health Checks
```bash
# Backend health
curl http://localhost:8000/health

# Database check
docker-compose exec db pg_isready

# Redis check
docker-compose exec redis redis-cli ping
```

## 🔄 بک‌آپ و بازیابی

### بک‌آپ کامل
```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/$DATE"

mkdir -p $BACKUP_DIR

# Database backup
docker-compose exec -T db pg_dump -U postgres parsian_clinic > $BACKUP_DIR/database.sql

# Uploads backup
cp -r backend/uploads $BACKUP_DIR/

# Config backup
cp backend/.env $BACKUP_DIR/

echo "Backup completed: $BACKUP_DIR"
```

### بازیابی
```bash
#!/bin/bash
# restore.sh

BACKUP_DIR=$1

# Restore database
docker-compose exec -T db psql -U postgres parsian_clinic < $BACKUP_DIR/database.sql

# Restore uploads
cp -r $BACKUP_DIR/uploads backend/

echo "Restore completed from: $BACKUP_DIR"
```

## 📞 پشتیبانی

- **توسعه‌دهنده**: صدرا - 09307398501
- **مستندات API**: http://localhost:8000/docs
- **GitHub Issues**: [لینک مخزن]

---

**✅ سیستم آماده است! موفق باشید! 🎉**