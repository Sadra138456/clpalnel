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

// Make API request
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
        console.log('📋 Response Headers:', [...response.headers.entries()]);
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('📝 Raw Response:', responseText);
        
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
            } else if (responseText.trim() === '') {
                throw new Error('سرور پاسخ خالی برمی‌گرداند! بررسی کنید که API فایل‌ها درست آپلود شده باشند.');
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
        
        // Show debug info to user in development
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
            showNotification(`Debug: ${error.message}`, 'error');
        } else {
            // Show user-friendly message in production
            if (error.message.includes('Failed to fetch')) {
                showNotification('مشکل در اتصال به سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
            } else {
                showNotification(error.message, 'error');
            }
        }
        
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
    
    // Close user menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-menu')) {
            document.getElementById('userDropdown').style.display = 'none';
        }
    });
    
});

// Verify token
async function verifyToken() {
    try {
        const response = await apiRequest('/auth/verify-token');
        showDashboard(response.user);
    } catch (error) {
        logout();
    }
}

// Show dashboard
function showDashboard(user) {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('dashboard').style.display = 'flex';
    
    // Update user info
    document.getElementById('userName').textContent = user.first_name + ' ' + user.last_name;
    
    // Load dashboard data
    loadDashboardData();
}

// Load dashboard data
async function loadDashboardData() {
    try {
        // Load reservations stats
        const reservationsResponse = await apiRequest('/reservations/statistics');
        
        document.getElementById('totalReservations').textContent = reservationsResponse.total_reservations;
        document.getElementById('pendingReservations').textContent = reservationsResponse.pending_count;
        document.getElementById('confirmedReservations').textContent = reservationsResponse.confirmed_count;
        
        // Load SMS stats
        const smsResponse = await apiRequest('/sms/statistics');
        document.getElementById('totalSMS').textContent = smsResponse.total_messages;
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Logout
function logout() {
    authToken = null;
    localStorage.removeItem('authToken');
    
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('loginPage').style.display = 'block';
    
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