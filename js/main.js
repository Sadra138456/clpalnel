// API Base URL
const API_BASE = '/api';

// Auth token storage
let authToken = localStorage.getItem('authToken');

// Set auth header for requests
function setAuthHeader(headers = {}) {
    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    return headers;
}

// Make API request with better error handling
async function apiRequest(url, options = {}) {
    const config = {
        headers: {
            'Content-Type': 'application/json',
            ...setAuthHeader(options.headers)
        },
        ...options
    };

    try {
        console.log('🚀 API Request:', API_BASE + url, config);
        
        const response = await fetch(API_BASE + url, config);
        
        console.log('📡 Response Status:', response.status, response.statusText);
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('📝 Raw Response:', responseText);
        
        // Check if response is empty
        if (!responseText || responseText.trim() === '') {
            throw new Error('سرور پاسخ خالی برمی‌گرداند. احتمالاً مشکل در تنظیمات دیتابیس است.');
        }
        
        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('❌ JSON Parse Error:', jsonError);
            console.error('📄 Response Text:', responseText);
            
            // Show helpful error message
            if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                throw new Error('سرور پاسخ HTML برمی‌گرداند! احتمالاً مشکل در API path یا کانفیگ سرور است.');
            } else if (responseText.includes('Connection Error') || responseText.includes('database')) {
                throw new Error('مشکل در اتصال به دیتابیس. لطفاً تنظیمات دیتابیس را بررسی کنید.');
            } else {
                throw new Error('پاسخ سرور JSON معتبر نیست: ' + responseText.substring(0, 200));
            }
        }
        
        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}: ${response.statusText}`);
        }
        
        return data;
    } catch (error) {
        console.error('💥 API Error:', error);
        
        // Show debug info to user
        showNotification(error.message, 'error');
        
        throw error;
    }
}

// Show/hide sections
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId + 'Section').classList.add('active');
    
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
    
    // Update page title
    const titles = {
        dashboard: 'داشبورد',
        reservations: 'رزروها',
        sms: 'ارسال پیام',
        archive: 'آرشیو پیام‌ها',
        settings: 'تنظیمات'
    };
    document.getElementById('pageTitle').textContent = titles[sectionId] || 'داشبورد';
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#4CAF50' : '#f44336'};
        color: white;
        border-radius: 5px;
        z-index: 10000;
        font-family: Vazirmatn, sans-serif;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Login form handler
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if user is already logged in
    if (authToken) {
        verifyToken();
    }
    
    // Login form
    document.getElementById('loginFormElement').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        
        try {
            const response = await apiRequest('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            authToken = response.token;
            localStorage.setItem('authToken', authToken);
            
            showDashboard(response.user);
            showNotification('ورود موفقیت‌آمیز بود!');
            
        } catch (error) {
            showNotification('خطا در ورود: ' + error.message, 'error');
        }
    });
    
    // Register form
    document.getElementById('signupFormElement').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('signupFirstName').value;
        const lastName = document.getElementById('signupLastName').value;
        const email = document.getElementById('signupEmail').value;
        const phone = document.getElementById('signupPhone').value;
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('signupConfirmPassword').value;
        
        if (password !== confirmPassword) {
            showNotification('رمز عبور و تأیید آن باید یکسان باشند', 'error');
            return;
        }
        
        try {
            const response = await apiRequest('/auth/register', {
                method: 'POST',
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email,
                    phone,
                    password
                })
            });
            
            authToken = response.token;
            localStorage.setItem('authToken', authToken);
            
            showDashboard(response.user);
            showNotification('ثبت نام موفقیت‌آمیز بود!');
            
        } catch (error) {
            showNotification('خطا در ثبت نام: ' + error.message, 'error');
        }
    });
    
    // Form switching
    document.getElementById('showSignup').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('loginForm').classList.remove('active');
        document.getElementById('signupForm').classList.add('active');
    });
    
    document.getElementById('showLogin').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('signupForm').classList.remove('active');
        document.getElementById('loginForm').classList.add('active');
    });
    
    // Navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            showSection(section);
        });
    });
    
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // User menu toggle
    document.getElementById('userMenuToggle').addEventListener('click', function() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
});

// Verify token
async function verifyToken() {
    try {
        const response = await apiRequest('/auth/verify-token');
        showDashboard(response.user);
    } catch (error) {
        console.error('Token verification failed:', error);
        logout();
    }
}

// Show dashboard
function showDashboard(user) {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('dashboard').style.display = 'block';
    
    // Update user info
    document.getElementById('userName').textContent = user.first_name + ' ' + user.last_name;
    if (user.avatar) {
        document.getElementById('userAvatar').src = user.avatar;
    }
    
    // Load dashboard data
    loadDashboardData();
}

// Load dashboard data
async function loadDashboardData() {
    try {
        // Load real data from API
        const response = await apiRequest('/reservations/stats');
        
        document.getElementById('totalReservations').textContent = response.total || '0';
        document.getElementById('pendingReservations').textContent = response.pending || '0';
        document.getElementById('confirmedReservations').textContent = response.confirmed || '0';
        document.getElementById('totalSMS').textContent = response.sms_count || '0';
        
        showNotification('داشبورد بارگذاری شد!', 'success');
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        // Show default values if API fails
        document.getElementById('totalReservations').textContent = '0';
        document.getElementById('pendingReservations').textContent = '0';
        document.getElementById('confirmedReservations').textContent = '0';
        document.getElementById('totalSMS').textContent = '0';
        
        showNotification('خطا در بارگذاری اطلاعات داشبورد', 'error');
    }
}

// Logout
function logout() {
    authToken = null;
    localStorage.removeItem('authToken');
    
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('loginPage').style.display = 'block';
    
    // Reset forms
    document.getElementById('loginFormElement').reset();
    document.getElementById('signupFormElement').reset();
    
    showNotification('خروج موفقیت‌آمیز بود!');
}

// Theme switcher (basic implementation)
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            showNotification('تم تغییر یافت!');
        });
    }
});

// Character counter for SMS
document.addEventListener('DOMContentLoaded', function() {
    const smsMessage = document.getElementById('smsMessage');
    const charCount = document.getElementById('charCount');
    
    if (smsMessage && charCount) {
        smsMessage.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});

// SMS form handler
document.addEventListener('DOMContentLoaded', function() {
    const smsForm = document.getElementById('smsForm');
    
    if (smsForm) {
        smsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('smsRecipient').value;
            const message = document.getElementById('smsMessage').value;
            
            try {
                await apiRequest('/sms/send', {
                    method: 'POST',
                    body: JSON.stringify({ phone, message })
                });
                
                showNotification('پیام با موفقیت ارسال شد!');
                smsForm.reset();
                document.getElementById('charCount').textContent = '0';
                
            } catch (error) {
                showNotification('خطا در ارسال پیام: ' + error.message, 'error');
            }
        });
    }
});

console.log('🏥 سیستم رزرو کلینیک دامپزشکی - آماده استفاده!');
console.log('📧 admin@vet.com | 🔑 admin123');