<?php
// Check if this is an API request
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    // API requests should be handled by .htaccess routing
    // This file should not be reached for API calls
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// For non-API requests, serve the HTML file
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم رزرو کلینیک دامپزشکی</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <!-- OAuth disabled for now -->
    <!-- <script src="https://accounts.google.com/gsi/client" async defer></script> -->
</head>
<body>
    <!-- Login/Register Page -->
    <div class="login-page" id="loginPage">
        <div class="background-orbs">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>
        
        <div class="login-container">
            <div class="login-header">
                <h1>سیستم رزرو کلینیک دامپزشکی</h1>
                <p>مدیریت حرفه‌ای نوبت‌های دامپزشکی</p>
            </div>
            
            <div class="form-container">
                <!-- Login Form -->
                <div class="form-wrapper active" id="loginForm">
                    <h2>ورود به سیستم</h2>
                    <form id="loginFormElement">
                        <div class="input-group">
                            <input type="email" id="loginEmail" required>
                            <label for="loginEmail">ایمیل</label>
                        </div>
                        <div class="input-group">
                            <input type="password" id="loginPassword" required>
                            <label for="loginPassword">رمز عبور</label>
                        </div>
                        <button type="submit" class="btn-primary">ورود</button>
                    </form>
                    
                    <!-- OAuth disabled -->
                    <!-- <div class="divider">یا</div>
                    <div class="oauth-buttons">
                        <div id="googleSignInButton" class="btn-oauth google">
                            <span class="oauth-icon">G</span>
                            <span>ورود با Google</span>
                        </div>
                    </div> -->
                    
                    <div class="form-footer">
                        <p>حساب کاربری ندارید؟ <a href="#" id="showSignup">ثبت نام</a></p>
                    </div>
                </div>
                
                <!-- Signup Form -->
                <div class="form-wrapper" id="signupForm">
                    <h2>ثبت نام</h2>
                    <form id="signupFormElement">
                        <div class="input-group">
                            <input type="text" id="signupFirstName" required>
                            <label for="signupFirstName">نام</label>
                        </div>
                        <div class="input-group">
                            <input type="text" id="signupLastName" required>
                            <label for="signupLastName">نام خانوادگی</label>
                        </div>
                        <div class="input-group">
                            <input type="email" id="signupEmail" required>
                            <label for="signupEmail">ایمیل</label>
                        </div>
                        <div class="input-group">
                            <input type="tel" id="signupPhone">
                            <label for="signupPhone">شماره موبایل</label>
                        </div>
                        <div class="input-group">
                            <input type="password" id="signupPassword" required>
                            <label for="signupPassword">رمز عبور</label>
                        </div>
                        <div class="input-group">
                            <input type="password" id="signupConfirmPassword" required>
                            <label for="signupConfirmPassword">تأیید رمز عبور</label>
                        </div>
                        <button type="submit" class="btn-primary">ثبت نام</button>
                    </form>
                    
                    <!-- OAuth disabled -->
                    <!-- <div class="divider">یا</div>
                    <div class="oauth-buttons">
                        <div id="googleSignUpButton" class="btn-oauth google">
                            <span class="oauth-icon">G</span>
                            <span>ثبت نام با Google</span>
                        </div>
                    </div> -->
                    
                    <div class="form-footer">
                        <p>حساب کاربری دارید؟ <a href="#" id="showLogin">ورود</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div class="dashboard" id="dashboard" style="display: none;">
        <div class="sidebar">
            <div class="logo">
                <h2>کلینیک دامپزشکی</h2>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-section="dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">داشبورد</span>
                </a>
                <a href="#" class="nav-item" data-section="reservations">
                    <span class="nav-icon">📅</span>
                    <span class="nav-text">رزروها</span>
                </a>
                <a href="#" class="nav-item" data-section="sms">
                    <span class="nav-icon">📱</span>
                    <span class="nav-text">ارسال پیام</span>
                </a>
                <a href="#" class="nav-item" data-section="archive">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">آرشیو پیام‌ها</span>
                </a>
                <a href="#" class="nav-item" data-section="settings">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">تنظیمات</span>
                </a>
            </nav>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1 id="pageTitle">داشبورد</h1>
                </div>
                <div class="header-right">
                    <div class="theme-switcher">
                        <button id="themeToggle">🎨</button>
                    </div>
                    <div class="user-menu">
                        <div class="user-info" id="userMenuToggle">
                            <img src="https://via.placeholder.com/40" alt="User" class="user-avatar" id="userAvatar">
                            <span class="user-name" id="userName">کاربر</span>
                        </div>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="#" class="dropdown-item">پروفایل</a>
                            <a href="#" class="dropdown-item" id="logoutBtn">خروج</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <!-- Dashboard Section -->
                <div class="section active" id="dashboardSection">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-info">
                                <h3 id="totalReservations">0</h3>
                                <p>کل رزروها</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-info">
                                <h3 id="pendingReservations">0</h3>
                                <p>در انتظار تأیید</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-info">
                                <h3 id="confirmedReservations">0</h3>
                                <p>تأیید شده</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📱</div>
                            <div class="stat-info">
                                <h3 id="totalSMS">0</h3>
                                <p>پیام‌های ارسالی</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="charts-section">
                        <div class="chart-card">
                            <h3>آمار روزانه</h3>
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Reservations Section -->
                <div class="section" id="reservationsSection">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>مدیریت رزروها</h2>
                        </div>
                        <div class="section-actions">
                            <button class="btn btn-primary" id="addReservationBtn">افزودن رزرو</button>
                        </div>
                    </div>
                    
                    <div class="filters">
                        <div class="filter-group">
                            <input type="text" id="searchReservations" placeholder="جستجو..." class="filter-input">
                            <select id="statusFilter" class="filter-select">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="pending">در انتظار</option>
                                <option value="confirmed">تأیید شده</option>
                                <option value="completed">تکمیل شده</option>
                                <option value="cancelled">لغو شده</option>
                            </select>
                            <input type="date" id="dateFromFilter" class="filter-input">
                            <input type="date" id="dateToFilter" class="filter-input">
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table" id="reservationsTable">
                            <thead>
                                <tr>
                                    <th>شماره</th>
                                    <th>نام حیوان</th>
                                    <th>نام صاحب</th>
                                    <th>شماره تماس</th>
                                    <th>تاریخ</th>
                                    <th>ساعت</th>
                                    <th>نوع خدمت</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody id="reservationsTableBody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination" id="reservationsPagination">
                        <!-- Dynamic pagination -->
                    </div>
                </div>

                <!-- SMS Section -->
                <div class="section" id="smsSection">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>ارسال پیام</h2>
                        </div>
                    </div>
                    
                    <div class="sms-form-container">
                        <form id="smsForm" class="sms-form">
                            <div class="form-group">
                                <label for="smsRecipient">شماره موبایل گیرنده:</label>
                                <input type="tel" id="smsRecipient" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="smsMessage">متن پیام:</label>
                                <textarea id="smsMessage" rows="4" required></textarea>
                                <div class="char-counter">
                                    <span id="charCount">0</span>/1000 کاراکتر
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">ارسال پیام</button>
                                <button type="button" class="btn btn-secondary" id="bulkSMSBtn">ارسال گروهی</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Archive Section -->
                <div class="section" id="archiveSection">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>آرشیو پیام‌ها</h2>
                        </div>
                    </div>
                    
                    <div class="archive-stats">
                        <div class="stat-card">
                            <div class="stat-icon">📊</div>
                            <div class="stat-info">
                                <h3 id="archiveTotalSMS">0</h3>
                                <p>کل پیام‌ها</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-info">
                                <h3 id="archiveSuccessRate">0%</h3>
                                <p>نرخ موفقیت</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">💰</div>
                            <div class="stat-info">
                                <h3 id="archiveTotalCost">0</h3>
                                <p>هزینه کل</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filters">
                        <div class="filter-group">
                            <input type="text" id="archivePhoneFilter" placeholder="شماره موبایل..." class="filter-input">
                            <select id="archiveStatusFilter" class="filter-select">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="sent">ارسال شده</option>
                                <option value="delivered">تحویل داده شده</option>
                                <option value="failed">ناموفق</option>
                            </select>
                            <input type="date" id="archiveDateFrom" class="filter-input">
                            <input type="date" id="archiveDateTo" class="filter-input">
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table" id="archiveTable">
                            <thead>
                                <tr>
                                    <th>شماره</th>
                                    <th>شماره موبایل</th>
                                    <th>متن پیام</th>
                                    <th>وضعیت</th>
                                    <th>هزینه</th>
                                    <th>تاریخ ارسال</th>
                                </tr>
                            </thead>
                            <tbody id="archiveTableBody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination" id="archivePagination">
                        <!-- Dynamic pagination -->
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="section" id="settingsSection">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>تنظیمات</h2>
                        </div>
                    </div>
                    
                    <div class="settings-grid">
                        <div class="setting-card">
                            <h3>تنظیمات SMS</h3>
                            <div class="setting-group">
                                <label>API Key کاوه‌نگار:</label>
                                <input type="password" id="smsApiKey" placeholder="API Key خود را وارد کنید">
                            </div>
                            <div class="setting-group">
                                <label>شماره ارسال:</label>
                                <input type="text" id="smsSender" placeholder="شماره پنل خود را وارد کنید">
                            </div>
                            <button class="btn btn-primary">ذخیره تنظیمات</button>
                        </div>
                        
                        <div class="setting-card">
                            <h3>تنظیمات پروفایل</h3>
                            <div class="setting-group">
                                <label>نام:</label>
                                <input type="text" id="profileFirstName">
                            </div>
                            <div class="setting-group">
                                <label>نام خانوادگی:</label>
                                <input type="text" id="profileLastName">
                            </div>
                            <div class="setting-group">
                                <label>شماره موبایل:</label>
                                <input type="tel" id="profilePhone">
                            </div>
                            <button class="btn btn-primary">بروزرسانی پروفایل</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal" id="reservationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="reservationModalTitle">افزودن رزرو جدید</h3>
                <button class="modal-close" id="closeReservationModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="petName">نام حیوان:</label>
                            <input type="text" id="petName" required>
                        </div>
                        <div class="form-group">
                            <label for="petType">نوع حیوان:</label>
                            <select id="petType" required>
                                <option value="">انتخاب کنید</option>
                                <option value="سگ">سگ</option>
                                <option value="گربه">گربه</option>
                                <option value="خرگوش">خرگوش</option>
                                <option value="پرنده">پرنده</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="petAge">سن حیوان (سال):</label>
                            <input type="number" id="petAge" min="0" max="30">
                        </div>
                        <div class="form-group">
                            <label for="serviceType">نوع خدمت:</label>
                            <select id="serviceType" required>
                                <option value="">انتخاب کنید</option>
                                <option value="معاینه عمومی">معاینه عمومی</option>
                                <option value="واکسیناسیون">واکسیناسیون</option>
                                <option value="جراحی">جراحی</option>
                                <option value="دندانپزشکی">دندانپزشکی</option>
                                <option value="گرومینگ">گرومینگ</option>
                                <option value="آزمایش">آزمایش</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ownerName">نام صاحب:</label>
                            <input type="text" id="ownerName" required>
                        </div>
                        <div class="form-group">
                            <label for="ownerPhone">شماره تماس:</label>
                            <input type="tel" id="ownerPhone" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ownerEmail">ایمیل:</label>
                            <input type="email" id="ownerEmail">
                        </div>
                        <div class="form-group">
                            <label for="reservationPrice">هزینه (تومان):</label>
                            <input type="number" id="reservationPrice" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reservationDate">تاریخ رزرو:</label>
                            <input type="date" id="reservationDate" required>
                        </div>
                        <div class="form-group">
                            <label for="reservationTime">ساعت رزرو:</label>
                            <input type="time" id="reservationTime" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="vaccineType">نوع واکسن (در صورت نیاز):</label>
                        <input type="text" id="vaccineType">
                    </div>
                    
                    <div class="form-group">
                        <label for="reservationNotes">توضیحات:</label>
                        <textarea id="reservationNotes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                        <button type="button" class="btn btn-secondary" id="cancelReservation">لغو</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>