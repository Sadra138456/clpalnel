document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // UI Elements
    const UI = {
        pageLoader: document.getElementById('page-loader'),
        loginPage: document.getElementById('login-page'),
        dashboardPage: document.getElementById('dashboard-page'),
        mainContentArea: document.getElementById('main-content-area'),
        activeContentSections: () => document.querySelectorAll('.content-section.active-content-section'),
        toastContainer: document.getElementById('toast-container'),
        currentYear: document.getElementById('current-year'),
        body: document.body,
    };

    // Auth Elements (Updated)
    const Auth = {
        loginForm: document.getElementById('login-form'),
        signupForm: document.getElementById('signup-form'),
        loginFormContainer: document.getElementById('login-form-container'),
        signupFormContainer: document.getElementById('signup-form-container'),
        username: document.getElementById('username'),
        password: document.getElementById('password'),
        signupFullname: document.getElementById('signup-fullname'),
        signupEmail: document.getElementById('signup-email'),
        signupPassword: document.getElementById('signup-password'),
        signupConfirmPassword: document.getElementById('signup-confirm-password'),
        loginButton: document.getElementById('login-button'),
        signupButton: document.getElementById('signup-button'),
        logoutButton: document.getElementById('logout-button'),
        togglePassword: document.querySelectorAll('.toggle-password-visibility'),
        showSignup: document.getElementById('show-signup'),
        showLogin: document.getElementById('show-login'),
        googleLoginBtn: document.getElementById('google-login-btn'),
        googleSignupBtn: document.getElementById('google-signup-btn'),
        rememberMe: document.getElementById('remember-me'),
        acceptTerms: document.getElementById('accept-terms'),
        forgotPasswordLink: document.getElementById('forgot-password-link'),
    };

    // Header Elements
    const Header = {
        sidebarToggleBtn: document.getElementById('sidebar-toggle-btn'),
        globalSearchInput: document.getElementById('global-search-input'),
        themeToggleBtn: document.getElementById('theme-toggle-btn'),
        profileToggleBtn: document.getElementById('profile-toggle'),
        profileDropdown: document.getElementById('profile-dropdown'),
        contactDeveloperDropdown: document.getElementById('contact-developer-dropdown'),
        accountSettingsBtn: document.getElementById('account-settings-btn'),
        headerAvatar: document.getElementById('header-avatar-img'),
        dropdownAvatar: document.getElementById('dropdown-avatar-img'),
    };

    // Sidebar Elements
    const Sidebar = {
        nav: document.getElementById('sidebar-nav'),
        navLinks: () => document.querySelectorAll('#sidebar-nav .nav-link'),
        reservationCountBadge: document.getElementById('sidebar-reservation-count'),
    };

    // Form Elements
    const Form = {
        reservationForm: document.getElementById('reservation-form'),
        petName: document.getElementById('petName'),
        ownerName: document.getElementById('ownerName'),
        phoneNumber: document.getElementById('phoneNumber'),
        visitDate: document.getElementById('visitDate'),
        nextVisitDate: document.getElementById('nextVisitDate'),
        vaccineTypeSelect: document.getElementById('vaccineType'),
        customVaccineTypeGroup: document.getElementById('customVaccineTypeGroup'),
        customVaccineTypeInput: document.getElementById('customVaccineTypeInput'),
        notes: document.getElementById('notes'),
        submitReservationButton: document.getElementById('submit-reservation-button'),
        resetFormButton: document.getElementById('reset-form-button'),
        breed: document.getElementById('breed'),
        weight: document.getElementById('weight'),
        birthYear: document.getElementById('birthYear'),
        price: document.getElementById('price'),
    };

    // Reservation List Elements
    const ReservationList = {
        container: document.getElementById('reservation-list-grid'),
        filterInput: document.getElementById('list-filter-input'),
        sortDropdown: document.getElementById('list-sort-dropdown'),
        noReservationsMessage: document.getElementById('no-reservations-message'),
    };

    // Dashboard View Elements
    const DashboardView = {
        statActiveReservations: document.getElementById('stat-active-reservations'),
    };

    // Settings Page Elements
    const SettingsPage = {
        themeSelect: document.getElementById('theme-select-page'),
        gradientToggle: document.getElementById('gradient-animation-toggle-page'),
        smsTemplate: document.getElementById('sms-template'),
        smsApiKey: document.getElementById('sms-api-key'),
        smsSendBefore: document.getElementById('sms-send-before'),
        saveSmsSettingsBtn: document.getElementById('save-sms-settings-btn'),
    };

    // Account Settings Modal Elements
    const AccountSettingsModal = {
        overlay: document.getElementById('account-settings-modal-overlay'),
        modal: document.getElementById('account-settings-modal'),
        closeBtn: document.getElementById('account-settings-close-btn'),
        cancelBtn: document.getElementById('account-settings-cancel-btn'),
        saveBtn: document.getElementById('account-settings-save-btn'),
        profilePicUpload: document.getElementById('profile-pic-upload'),
        profilePicPreview: document.getElementById('profile-pic-preview'),
    };

    // SMS Elements (Updated)
    const SMS = {
        reservationSelect: document.getElementById('sms-reservation-select'),
        message: document.getElementById('sms-message'),
        sendBefore: document.getElementById('sms-send-before'),
        sendButton: document.getElementById('send-sms-button'),
        scheduledList: document.getElementById('scheduled-sms-list'),
        showArchiveBtn: document.getElementById('show-sms-archive-btn'),
        archiveSection: document.getElementById('sms-archive-section'),
        archiveList: document.getElementById('sms-archive-list'),
        archiveSearchInput: document.getElementById('archive-search-input'),
        archiveFilter: document.getElementById('archive-filter'),
        clearArchiveBtn: document.getElementById('clear-archive-btn'),
    };

    let appData = {};
    const defaultAppData = {
        reservations: [],
        nextReservationId: 1,
        smsArchive: [], // New SMS archive array
        users: [
            { id: 1, email: 'admin@parsian.com', password: 'admin123', fullname: 'مدیر کلینیک', role: 'admin' }
        ],
        settings: {
            currentTheme: 'purple-gradient-1',
            gradientAnimation: true,
            sms: {
                template: 'سلام، یادآور واکسن [نام حیوان] شما در تاریخ [تاریخ] می باشد. کلینیک پارسیان',
                apiKey: '',
                sendBefore: 1, // days
            }
        },
        user: { username: 'admin', profilePic: '', email: 'admin@parsian.com', fullname: 'مدیر کلینیک' },
        isAuthenticated: false,
    };

    let currentActiveSection = 'dashboard-main-view';
    const APP_DATA_KEY = 'parsianClinicData_v1.0';

    // Helper function to get theme names (Updated)
    function getThemeName(themeKey) {
        const themeNames = {
            'purple-gradient-1': 'بنفش مرموز',
            'purple-gradient-2': 'آبی شاهانه', 
            'purple-gradient-3': 'بنفش درخشان',
            'amethyst-dream': 'رویای آمتیست',
            'lavender-mist': 'مه اسطوخودوس',
            'royal-purple': 'بنفش سلطنتی',
            'glass-dark': 'شیشه‌ای تیره',
            'glass-light': 'شیشه‌ای روشن'
        };
        return themeNames[themeKey] || themeKey;
    }

    // Switch between login and signup forms
    function switchAuthForm(showSignup = false) {
        if (showSignup) {
            Auth.loginFormContainer.classList.remove('active');
            setTimeout(() => {
                Auth.signupFormContainer.classList.add('active');
            }, 200);
        } else {
            Auth.signupFormContainer.classList.remove('active');
            setTimeout(() => {
                Auth.loginFormContainer.classList.add('active');
            }, 200);
        }
    }

    // Simple Login Function (No OAuth)
    function handleLogin(event) {
        event.preventDefault();
        
        const email = Auth.username.value.trim();
        const password = Auth.password.value.trim();
        
        if (!email || !password) {
            showToast('لطفاً ایمیل و رمز عبور را وارد کنید', 'error');
            return;
        }
        
        // Simple authentication (for testing)
        if (email === 'admin@parsian.com' && password === 'admin123') {
            appData.user = { 
                username: 'admin', 
                profilePic: '', 
                email: email, 
                fullname: 'مدیر کلینیک' 
            };
            appData.isAuthenticated = true;
            saveAppData();
            
            showToast('ورود موفقیت‌آمیز بود!', 'success');
            showDashboard();
        } else {
            showToast('ایمیل یا رمز عبور اشتباه است', 'error');
        }
    }

    // Simple Signup Function
    function handleSignup(event) {
        event.preventDefault();
        
        const fullname = Auth.signupFullname.value.trim();
        const email = Auth.signupEmail.value.trim();
        const password = Auth.signupPassword.value;
        const confirmPassword = Auth.signupConfirmPassword.value;
        
        if (!fullname || !email || !password || !confirmPassword) {
            showToast('لطفاً تمام فیلدها را پر کنید', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            showToast('رمز عبور و تکرار آن یکسان نیستند', 'error');
            return;
        }
        
        if (!Auth.acceptTerms.checked) {
            showToast('لطفاً شرایط و قوانین را بپذیرید', 'error');
            return;
        }
        
        // Simple registration (for testing)
        appData.user = { 
            username: email.split('@')[0], 
            profilePic: '', 
            email: email, 
            fullname: fullname 
        };
        appData.isAuthenticated = true;
        saveAppData();
        
        showToast('ثبت نام موفقیت‌آمیز بود!', 'success');
        showDashboard();
    }

    // Show Dashboard
    function showDashboard() {
        UI.loginPage.classList.remove('active');
        UI.dashboardPage.classList.add('active');
        UI.pageLoader.style.display = 'none';
        
        // Update user info
        if (Header.headerAvatar) {
            Header.headerAvatar.src = appData.user.profilePic || 'https://via.placeholder.com/100?text=' + appData.user.fullname.charAt(0);
        }
        if (Header.dropdownAvatar) {
            Header.dropdownAvatar.src = appData.user.profilePic || 'https://via.placeholder.com/100?text=' + appData.user.fullname.charAt(0);
        }
        
        // Load initial data
        loadAppData();
        updateDashboardStats();
        renderReservationList();
    }

    // Logout Function
    function handleLogout() {
        appData.isAuthenticated = false;
        saveAppData();
        
        UI.dashboardPage.classList.remove('active');
        UI.loginPage.classList.add('active');
        
        // Reset forms
        Auth.loginForm.reset();
        Auth.signupForm.reset();
        
        showToast('خروج موفقیت‌آمیز بود!', 'success');
    }

    // Show Toast Notification
    function showToast(message, type = 'info', duration = 3500) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close">×</button>
        `;
        
        UI.toastContainer.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.remove();
        }, duration);
        
        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });
    }

    // Load App Data
    function loadAppData() {
        const savedData = localStorage.getItem(APP_DATA_KEY);
        if (savedData) {
            try {
                appData = { ...defaultAppData, ...JSON.parse(savedData) };
            } catch (error) {
                console.error('Error loading app data:', error);
                appData = { ...defaultAppData };
            }
        } else {
            appData = { ...defaultAppData };
        }
    }

    // Save App Data
    function saveAppData() {
        localStorage.setItem(APP_DATA_KEY, JSON.stringify(appData));
    }

    // Update Dashboard Stats
    function updateDashboardStats() {
        if (DashboardView.statActiveReservations) {
            DashboardView.statActiveReservations.textContent = appData.reservations.length;
        }
    }

    // Render Reservation List
    function renderReservationList() {
        if (!ReservationList.container) return;
        
        if (appData.reservations.length === 0) {
            ReservationList.container.innerHTML = '';
            if (ReservationList.noReservationsMessage) {
                ReservationList.noReservationsMessage.style.display = 'block';
            }
            return;
        }
        
        if (ReservationList.noReservationsMessage) {
            ReservationList.noReservationsMessage.style.display = 'none';
        }
        
        const reservationsHtml = appData.reservations.map(reservation => `
            <div class="invoice-card glass-effect-card" data-id="${reservation.id}">
                <div class="invoice-header">
                    <h3>${reservation.petName}</h3>
                    <span class="invoice-status status-confirmed">تأیید شده</span>
                </div>
                <div class="invoice-details">
                    <p><strong>صاحب:</strong> ${reservation.ownerName}</p>
                    <p><strong>تلفن:</strong> ${reservation.phoneNumber}</p>
                    <p><strong>تاریخ مراجعه:</strong> ${reservation.visitDate}</p>
                    <p><strong>نوع واکسن:</strong> ${reservation.vaccineType}</p>
                </div>
            </div>
        `).join('');
        
        ReservationList.container.innerHTML = reservationsHtml;
    }

    // Initialize App
    function initializeApp() {
        loadAppData();
        
        // Check if user is already authenticated
        if (appData.isAuthenticated) {
            showDashboard();
        } else {
            UI.pageLoader.style.display = 'none';
        }
        
        // Update current year
        if (UI.currentYear) {
            UI.currentYear.textContent = new Date().getFullYear();
        }
        
        // Apply current theme
        if (appData.settings.currentTheme) {
            UI.body.setAttribute('data-theme', appData.settings.currentTheme);
        }
    }

    // Setup Event Listeners
    function setupEventListeners() {
        // Auth form switching
        if (Auth.showSignup) {
            Auth.showSignup.addEventListener('click', (e) => {
                e.preventDefault();
                switchAuthForm(true);
            });
        }
        
        if (Auth.showLogin) {
            Auth.showLogin.addEventListener('click', (e) => {
                e.preventDefault();
                switchAuthForm(false);
            });
        }
        
        // Login form
        if (Auth.loginForm) {
            Auth.loginForm.addEventListener('submit', handleLogin);
        }
        
        // Signup form
        if (Auth.signupForm) {
            Auth.signupForm.addEventListener('submit', handleSignup);
        }
        
        // Logout
        if (Auth.logoutButton) {
            Auth.logoutButton.addEventListener('click', handleLogout);
        }
        
        // Profile dropdown
        if (Header.profileToggleBtn && Header.profileDropdown) {
            Header.profileToggleBtn.addEventListener('click', () => {
                Header.profileDropdown.style.display = Header.profileDropdown.style.display === 'block' ? 'none' : 'block';
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (Header.profileDropdown && !Header.profileToggleBtn.contains(e.target) && !Header.profileDropdown.contains(e.target)) {
                Header.profileDropdown.style.display = 'none';
            }
        });
        
        // Theme toggle
        if (Header.themeToggleBtn) {
            Header.themeToggleBtn.addEventListener('click', () => {
                const themes = ['purple-gradient-1', 'purple-gradient-2', 'purple-gradient-3', 'amethyst-dream', 'lavender-mist', 'royal-purple'];
                const currentTheme = UI.body.getAttribute('data-theme');
                const currentIndex = themes.indexOf(currentTheme);
                const nextIndex = (currentIndex + 1) % themes.length;
                const newTheme = themes[nextIndex];
                
                UI.body.setAttribute('data-theme', newTheme);
                appData.settings.currentTheme = newTheme;
                saveAppData();
                
                showToast(`تم تغییر کرد به: ${getThemeName(newTheme)}`, 'success');
            });
        }
        
        // Sidebar toggle
        if (Header.sidebarToggleBtn) {
            Header.sidebarToggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
            });
        }
        
        // Password visibility toggle
        Auth.togglePassword.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });
    }

    // Initialize the app
    initializeApp();
    setupEventListeners();
});