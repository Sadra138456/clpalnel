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

    // Auth Elements
    const Auth = {
        loginForm: document.getElementById('login-form'),
        username: document.getElementById('username'),
        password: document.getElementById('password'),
        loginButton: document.getElementById('login-button'),
        logoutButton: document.getElementById('logout-button'),
        togglePassword: document.querySelector('.toggle-password-visibility'),
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

    // SMS Elements
    const SMS = {
        reservationSelect: document.getElementById('sms-reservation-select'),
        message: document.getElementById('sms-message'),
        sendBefore: document.getElementById('sms-send-before'),
        sendButton: document.getElementById('send-sms-button'),
        scheduledList: document.getElementById('scheduled-sms-list'),
    };

    let appData = {};
    const defaultAppData = {
        reservations: [],
        nextReservationId: 1,
        settings: {
            currentTheme: 'purple-gradient-1',
            gradientAnimation: true,
            sms: {
                template: 'سلام، یادآور واکسن [نام حیوان] شما در تاریخ [تاریخ] می باشد. کلینیک پارسیان',
                apiKey: '',
                sendBefore: 1, // days
            }
        },
        user: { username: 'admin', profilePic: '' },
        isAuthenticated: false,
    };

    let currentActiveSection = 'dashboard-main-view';
    const APP_DATA_KEY = 'parsianClinicData_v1.0';

    // Initialize App
    function initializeApp() {
        loadAppData();
        applySettings();
        loadUserProfile();
        loadSmsSettings();
        updateCurrentYear();
        setupEventListeners();
        if (appData.isAuthenticated) {
            UI.loginPage.style.display = 'none';
            UI.dashboardPage.classList.add('active');
            navigateToSection(currentActiveSection, true);
        } else {
            UI.loginPage.classList.add('active');
            UI.dashboardPage.classList.remove('active');
        }
        updateDashboardStats();
        renderReservationList();
        renderScheduledSMS();
        setInterval(scheduleReminders, 60000); // Check every minute
        setTimeout(() => UI.pageLoader.classList.add('hidden'), 500);
        updateReservationOptions(); // اطمینان از رندر اولیه گزینه‌ها
    }

    // Load App Data
    function loadAppData() {
        const storedData = localStorage.getItem(APP_DATA_KEY);
        if (storedData) {
            try {
                const parsedData = JSON.parse(storedData);
                appData = { 
                    ...defaultAppData, 
                    ...parsedData,
                    user: { ...defaultAppData.user, ...(parsedData.user || {}) },
                    settings: { 
                        ...defaultAppData.settings, 
                        ...(parsedData.settings || {}),
                        sms: { ...defaultAppData.settings.sms, ...(parsedData.settings?.sms || {}) }
                    }
                };
            } catch (e) { 
                console.error('Error parsing appData:', e); 
                appData = defaultAppData; 
            }
        } else { 
            appData = defaultAppData; 
        }
        if (appData.reservations.length > 0 && appData.reservations.every(r => r.id)) {
            appData.nextReservationId = Math.max(0, ...appData.reservations.map(r => parseInt(r.id))) + 1;
        }
    }

    // Save App Data
    function saveAppData() { 
        localStorage.setItem(APP_DATA_KEY, JSON.stringify(appData)); 
        renderScheduledSMS();
        updateReservationOptions(); // به‌روز کردن گزینه‌ها بعد از ذخیره
    }

    // Apply Theme Settings
    function applySettings() {
        if (SettingsPage.themeSelect) SettingsPage.themeSelect.value = appData.settings.currentTheme;
        UI.body.setAttribute('data-theme', appData.settings.currentTheme);
        UI.body.classList.toggle('no-gradient-animation', !appData.settings.gradientAnimation);
    }

    // Load SMS Settings
    function loadSmsSettings() {
        if (!SettingsPage.smsTemplate) return;
        SettingsPage.smsTemplate.value = appData.settings.sms.template;
        SettingsPage.smsApiKey.value = appData.settings.sms.apiKey;
        SettingsPage.smsSendBefore.value = appData.settings.sms.sendBefore;
    }

    // Load User Profile
    function loadUserProfile() {
        const defaultAvatar = 'https://via.placeholder.com/100';
        const profilePic = appData.user.profilePic || defaultAvatar;
        if (Header.headerAvatar) Header.headerAvatar.src = profilePic;
        if (Header.dropdownAvatar) Header.dropdownAvatar.src = profilePic;
        if (AccountSettingsModal.profilePicPreview) AccountSettingsModal.profilePicPreview.src = profilePic;
    }

    // Open Account Settings Modal
    function openAccountSettingsModal() {
        loadUserProfile();
        Header.profileDropdown.classList.remove('open');
        AccountSettingsModal.overlay.classList.add('active');
        AccountSettingsModal.modal.classList.add('active');
    }

    // Close Account Settings Modal
    function closeAccountSettingsModal() {
        AccountSettingsModal.overlay.classList.remove('active');
        AccountSettingsModal.modal.classList.remove('active');
    }

    // Handle Profile Picture Change
    function handleProfilePicChange(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                appData.user.profilePic = e.target.result;
                saveAppData();
                loadUserProfile();
                showToast('عکس پروفایل به‌روزرسانی شد.', 'success');
            };
            reader.readAsDataURL(file);
        } else if (file) { 
            showToast('لطفاً یک فایل عکس معتبر انتخاب کنید.', 'danger'); 
        }
    }

    // Update Current Year
    function updateCurrentYear() {
        const year = new Date().getFullYear();
        document.querySelectorAll('#current-year, #current-year-footer').forEach(el => { 
            if(el) el.textContent = year; 
        });
    }

    // Navigate Between Sections
    function navigateToSection(sectionId, isInitialLoad = false) {
        UI.activeContentSections().forEach(s => { 
            s.classList.remove('active-content-section'); 
            s.style.display = 'none'; 
        });
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.style.display = 'block';
            setTimeout(() => targetSection.classList.add('active-content-section'), 10);
            currentActiveSection = sectionId;
            Sidebar.navLinks().forEach(link => link.classList.toggle('active', link.dataset.section === sectionId));
        } else { 
            navigateToSection('dashboard-main-view', true); 
        }
    }

    // Render Reservation List
    function renderReservationList() {
        if (!ReservationList.container) return;
        ReservationList.container.innerHTML = '';
        const displayList = [...appData.reservations].sort((a, b) => new Date(b.visitDate) - new Date(a.visitDate));
        ReservationList.noReservationsMessage.style.display = displayList.length === 0 ? 'block' : 'none';
        displayList.forEach(res => {
            const resDate = new Date(res.visitDate).toLocaleDateString('fa-IR-u-nu-latn', { year: 'numeric', month: '2-digit', day: '2-digit' });
            const nextDate = res.nextVisitDate ? new Date(res.nextVisitDate).toLocaleDateString('fa-IR-u-nu-latn', { year: 'numeric', month: '2-digit', day: '2-digit' }) : '---';
            const card = document.createElement('div');
            card.className = 'reservation-card glass-effect-card';
            card.dataset.id = res.id;
            card.innerHTML = `
                <div class="reservation-card-header">
                    <span class="reservation-card-id">#${res.id} - ${res.ownerName}</span>
                    <span class="reservation-card-status active">${res.price ? res.price.toLocaleString('fa-IR') + ' تومان' : ''}</span>
                </div>
                <h3 class="reservation-card-title">${res.petName} <small>(${res.breed || 'نامشخص'})</small></h3>
                <div class="reservation-card-details">
                    <div><i class="fas fa-calendar-alt"></i> تاریخ رزرو: <strong>${resDate}</strong></div>
                    <div><i class="fas fa-calendar-check"></i> واکسن بعدی: <strong>${nextDate}</strong></div>
                    <div><i class="fas fa-syringe"></i> نوع واکسن: <strong>${res.vaccineType}</strong></div>
                    <div><i class="fas fa-weight-scale"></i> وزن: <strong>${res.weight || '---'} ک‌گ</strong></div>
                    <div><i class="fas fa-birthday-cake"></i> سال تولد: <strong>${res.birthYear || '---'}</strong></div>
                    <div><i class="fas fa-phone"></i> تماس: <strong>${res.phoneNumber || '---'}</strong></div>
                </div>
                ${res.notes ? `<p class="reservation-card-notes"><i class="fas fa-comment"></i> ${res.notes}</p>` : ''}
                <div class="reservation-card-actions">
                    <button class="btn btn-sm btn-outline-danger btn-delete-reservation" data-id="${res.id}" title="حذف"><i class="fas fa-trash-alt"></i> حذف</button>
                </div>`;
            ReservationList.container.appendChild(card);
        });
    }

    // Render Scheduled SMS
    function renderScheduledSMS() {
        if (!SMS.scheduledList) return;
        SMS.scheduledList.innerHTML = '';
        appData.reservations.forEach(res => {
            if (!res.nextVisitDate || res.reminderSent || !res.phoneNumber) return;
            const nextVaccineDate = new Date(res.nextVisitDate);
            const reminderTime = new Date(nextVaccineDate.setDate(nextVaccineDate.getDate() - appData.settings.sms.sendBefore));
            const message = appData.settings.sms.template
                .replace('[نام حیوان]', res.petName)
                .replace('[تاریخ]', new Date(res.nextVisitDate).toLocaleDateString('fa-IR'));
            const card = document.createElement('div');
            card.className = 'sms-card glass-effect-card';
            card.innerHTML = `
                <strong>${res.petName} (${res.ownerName})</strong>
                <div class="sms-date">تاریخ: ${new Date(res.nextVisitDate).toLocaleDateString('fa-IR')}</div>
                <div class="sms-message">${message}</div>
                <div class="sms-actions">
                    <button class="btn btn-sm btn-outline-danger delete-sms" data-id="${res.id}">حذف</button>
                </div>`;
            SMS.scheduledList.appendChild(card);

            card.querySelector('.delete-sms').addEventListener('click', () => {
                if (confirm(`آیا از حذف یادآور برای ${res.petName} مطمئن هستید؟`)) {
                    res.reminderSent = true;
                    saveAppData();
                    renderScheduledSMS();
                    showToast(`یادآور برای ${res.petName} حذف شد.`, 'info');
                }
            });
        });
    }

    // Update Dashboard Stats
    function updateDashboardStats() {
        if (DashboardView.statActiveReservations) { 
            DashboardView.statActiveReservations.textContent = appData.reservations.length; 
        }
        if (Sidebar.reservationCountBadge) { 
            Sidebar.reservationCountBadge.textContent = appData.reservations.length; 
            Sidebar.reservationCountBadge.style.display = appData.reservations.length > 0 ? 'inline-block' : 'none'; 
        }
    }

    // Handle Form Submit
    function handleFormSubmit(event) {
        event.preventDefault();
        const newReservation = {
            id: appData.nextReservationId++,
            petName: Form.petName.value.trim(), 
            ownerName: Form.ownerName.value.trim(),
            phoneNumber: Form.phoneNumber.value.trim(), 
            visitDate: Form.visitDate.value,
            nextVisitDate: Form.nextVisitDate.value,
            vaccineType: Form.vaccineTypeSelect.value === 'سایر' ? Form.customVaccineTypeInput.value.trim() : Form.vaccineTypeSelect.value,
            notes: Form.notes.value.trim(), 
            createdAt: new Date().toISOString(),
            breed: Form.breed?.value.trim(), 
            weight: Form.weight?.value,
            birthYear: Form.birthYear?.value, 
            price: Form.price?.value,
            reminderSent: false,
        };
        appData.reservations.push(newReservation);
        saveAppData(); 
        renderReservationList(); 
        updateDashboardStats(); 
        Form.reservationForm.reset();
        Form.customVaccineTypeGroup.style.display = 'none'; 
        showToast('رزرو با موفقیت ثبت شد!', 'success');
        navigateToSection('reservation-list-section');
    }

    // Handle Login
    function handleLogin(event) {
        event.preventDefault();
        appData.isAuthenticated = true; 
        saveAppData();
        UI.loginPage.classList.remove('active'); 
        UI.dashboardPage.classList.add('active');
        setTimeout(() => UI.loginPage.style.display = 'none', 500);
        navigateToSection('dashboard-main-view', true);
        showToast('ورود با موفقیت انجام شد!', 'success');
    }

    // Show Toast Messages
    function showToast(message, type = 'info', duration = 3500) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const iconClass = type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-times-circle' : 'fa-info-circle';
        toast.innerHTML = `<i class="fas ${iconClass}"></i><span>${message}</span>`;
        if(UI.toastContainer) {
            UI.toastContainer.appendChild(toast);
            requestAnimationFrame(() => { toast.classList.add('show'); });
            setTimeout(() => { 
                toast.classList.remove('show'); 
                toast.addEventListener('transitionend', () => toast.remove(), { once: true }); 
            }, duration);
        }
    }

    // Schedule SMS Reminders
    function scheduleReminders() {
        const now = new Date();
        const { template, apiKey, sendBefore } = appData.settings.sms;
        appData.reservations.forEach(res => {
            if (!res.nextVisitDate || res.reminderSent || !res.phoneNumber) return;
            const nextVisitDate = new Date(res.nextVisitDate);
            const reminderTime = new Date(nextVisitDate.setDate(nextVisitDate.getDate() - sendBefore));
            if (now >= reminderTime && !res.reminderSent) {
                const message = template
                    .replace('[نام حیوان]', res.petName)
                    .replace('[تاریخ]', new Date(res.nextVisitDate).toLocaleDateString('fa-IR'));
                console.log(`Simulating SMS to ${res.phoneNumber}: ${message}`);
                if (apiKey) {
                    console.log("API Key is present, would send SMS now.");
                }
                showToast(`یادآور واکسن برای ${res.petName} ارسال شد.`, 'success');
                res.reminderSent = true;
                saveAppData();
            }
        });
    }

    // Update Reservation Options for SMS Panel
    function updateReservationOptions() {
        if (!SMS.reservationSelect) return;
        SMS.reservationSelect.innerHTML = '<option value="">-- انتخاب کنید --</option>';
        if (appData.reservations.length === 0) {
            console.log('No reservations available to display.');
            return;
        }
        appData.reservations.forEach((res, index) => {
            if (res.reminderSent || !res.nextVisitDate) return; // فقط رزروهایی که هنوز پیامک ارسال‌نشده و تاریخ بعدی دارند
            const option = document.createElement('option');
            option.value = index;
            const nextDate = new Date(res.nextVisitDate).toLocaleDateString('fa-IR-u-nu-latn');
            option.textContent = `${res.petName} (${res.ownerName}) - ${nextDate}`;
            SMS.reservationSelect.appendChild(option);
        });
        console.log('Reservation options updated successfully');
    }

    // Setup Event Listeners
    function setupEventListeners() {
        Auth.loginForm?.addEventListener('submit', handleLogin);
        Auth.logoutButton?.addEventListener('click', () => { 
            appData.isAuthenticated = false; 
            saveAppData(); 
            window.location.reload(); 
        });
        Auth.togglePassword?.addEventListener('click', () => { 
            const isPasswordVisible = Auth.password.type === 'text'; 
            Auth.password.type = isPasswordVisible ? 'password' : 'text'; 
            Auth.togglePassword.querySelector('i').classList.toggle('fa-eye-slash'); 
        });
        Header.sidebarToggleBtn?.addEventListener('click', () => { 
            Sidebar.nav.classList.toggle('open'); 
        });
        UI.mainContentArea?.addEventListener('click', (e) => { 
            if (Sidebar.nav.classList.contains('open') && !Sidebar.nav.contains(e.target) && !Header.sidebarToggleBtn.contains(e.target)) { 
                Sidebar.nav.classList.remove('open'); 
            } 
        });
        Header.profileToggleBtn?.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            Header.profileDropdown.classList.toggle('open'); 
        });
        document.addEventListener('click', (e) => { 
            if (Header.profileDropdown && !Header.profileDropdown.contains(e.target) && !Header.profileToggleBtn.contains(e.target)) { 
                Header.profileDropdown.classList.remove('open'); 
            } 
        });
        Header.contactDeveloperDropdown?.addEventListener('click', () => { 
            showToast('توسعه‌دهنده: صدرا | 09307398501', 'info', 6000); 
            Header.profileDropdown.classList.remove('open'); 
        });
        Header.themeToggleBtn?.addEventListener('click', () => { 
            const themes = ['purple-gradient-1', 'purple-gradient-2', 'purple-gradient-3'];
            let currentThemeIndex = themes.indexOf(appData.settings.currentTheme);
            currentThemeIndex = (currentThemeIndex + 1) % themes.length; 
            appData.settings.currentTheme = themes[currentThemeIndex]; 
            applySettings(); 
            saveAppData(); 
        });
        Sidebar.navLinks().forEach(link => { 
            link.addEventListener('click', (e) => { 
                e.preventDefault(); 
                navigateToSection(link.dataset.section); 
                if (window.innerWidth < 992) Sidebar.nav.classList.remove('open'); 
            }); 
        });
        Form.reservationForm?.addEventListener('submit', handleFormSubmit);
        Form.resetFormButton?.addEventListener('click', () => { 
            Form.reservationForm.reset(); 
            Form.customVaccineTypeGroup.style.display = 'none'; 
            showToast('فرم پاک شد.', 'info'); 
        });
        Form.vaccineTypeSelect?.addEventListener('change', () => { 
            const isOther = Form.vaccineTypeSelect.value === 'سایر'; 
            Form.customVaccineTypeGroup.style.display = isOther ? 'flex' : 'none'; 
            Form.customVaccineTypeInput.required = isOther; 
        });
        ReservationList.container?.addEventListener('click', (e) => { 
            const targetButton = e.target.closest('.btn-delete-reservation'); 
            if (!targetButton) return; 
            const reservationId = parseInt(targetButton.dataset.id); 
            if (confirm(`آیا از حذف رزرو #${reservationId} مطمئن هستید؟`)) { 
                appData.reservations = appData.reservations.filter(r => r.id !== reservationId); 
                saveAppData(); 
                renderReservationList(); 
                updateDashboardStats(); 
                showToast(`رزرو #${reservationId} حذف شد.`, 'info'); 
            } 
        });
        SettingsPage.themeSelect?.addEventListener('change', (e) => { 
            appData.settings.currentTheme = e.target.value; 
            applySettings(); 
            saveAppData(); 
        });
        SettingsPage.gradientToggle?.addEventListener('change', (e) => { 
            appData.settings.gradientAnimation = e.target.checked; 
            applySettings(); 
            saveAppData(); 
        });
        SettingsPage.saveSmsSettingsBtn?.addEventListener('click', () => {
            appData.settings.sms.template = SettingsPage.smsTemplate.value;
            appData.settings.sms.apiKey = SettingsPage.smsApiKey.value;
            appData.settings.sms.sendBefore = parseInt(SettingsPage.smsSendBefore.value) || 1;
            saveAppData();
            showToast('تنظیمات پیامک ذخیره شد.', 'success');
        });
        Header.accountSettingsBtn?.addEventListener('click', openAccountSettingsModal);
        AccountSettingsModal.closeBtn?.addEventListener('click', closeAccountSettingsModal);
        AccountSettingsModal.cancelBtn?.addEventListener('click', closeAccountSettingsModal);
        AccountSettingsModal.overlay?.addEventListener('click', closeAccountSettingsModal);
        AccountSettingsModal.profilePicUpload?.addEventListener('change', handleProfilePicChange);
        AccountSettingsModal.saveBtn?.addEventListener('click', () => { 
            showToast('تغییرات ذخیره شد!', 'success'); 
            closeAccountSettingsModal(); 
        });

        if (SMS.reservationSelect) {
            SMS.reservationSelect.addEventListener('change', () => {
                const index = SMS.reservationSelect.value;
                if (index !== '' && appData.reservations[index]) {
                    const res = appData.reservations[index];
                    const nextDate = new Date(res.nextVisitDate).toLocaleDateString('fa-IR-u-nu-latn');
                    SMS.message.value = `سلام ${res.ownerName}، یادآوری واکسن ${res.petName} برای تاریخ ${nextDate}. کلینیک پارسیان`;
                    SMS.sendButton.disabled = false;
                } else {
                    SMS.message.value = '';
                    SMS.sendButton.disabled = true;
                }
            });
        }

        if (SMS.sendBefore) {
            SMS.sendBefore.addEventListener('change', (e) => {
                appData.settings.sms.sendBefore = parseInt(e.target.value);
                saveAppData();
                renderScheduledSMS();
            });
        }

        if (SMS.sendButton) {
            SMS.sendButton.addEventListener('click', () => {
                const index = SMS.reservationSelect.value;
                if (index !== '' && SMS.message.value.trim() && appData.reservations[index]) {
                    const res = appData.reservations[index];
                    const message = SMS.message.value.trim();
                    console.log(`Simulating SMS to ${res.phoneNumber}: ${message}`);
                    if (appData.settings.sms.apiKey) {
                        console.log("API Key is present, would send SMS now.");
                    }
                    showToast(`پیامک برای ${res.petName} ارسال شد! (شبیه‌سازی)`, 'success');
                    res.reminderSent = true;
                    saveAppData();
                    SMS.reservationSelect.value = '';
                    SMS.message.value = '';
                    SMS.sendButton.disabled = true;
                    updateReservationOptions();
                    renderScheduledSMS();
                } else {
                    showToast('لطفاً رزرو و متن پیام را انتخاب کنید!', 'danger');
                }
            });
        }
    }

    // Initialize App
    initializeApp();
});
