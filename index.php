<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/database.php';
require_once 'config/functions.php';

// الحصول على قائمة المدن
$db = getDB();
$cities_stmt = $db->query("SELECT * FROM cities ORDER BY name");
$cities = $cities_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BusGo</title>
    <link rel="stylesheet" href="css/enhanced-style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- الهيدر -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <i class="fas fa-bus"></i> BusGo
                </a>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li><a href="search.php"><i class="fas fa-search"></i> البحث</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                        <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> حجوزاتي</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> تسجيل جديد</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- القسم الرئيسي -->
    <section class="hero">
        <div class="container">
            <h1 class="fade-in">مرحباً بكم في BusGo</h1>
            <p class="fade-in">احجز رحلتك بسهولة وأمان إلى جميع أنحاء السودان</p>
        </div>
    </section>

    <!-- نموذج البحث -->
    <section class="container">
        <div class="search-form fade-in">
            <h2 class="text-center mb-3">
                <i class="fas fa-search text-primary"></i> ابحث عن رحلتك
            </h2>
            <form action="search.php" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="from_city">
                            <i class="fas fa-map-marker-alt text-primary"></i> من
                        </label>
                        <select name="from_city" id="from_city" class="form-control" required>
                            <option value="">اختر المدينة</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>"><?= sanitize($city['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="to_city">
                            <i class="fas fa-map-marker-alt text-secondary"></i> إلى
                        </label>
                        <select name="to_city" id="to_city" class="form-control" required>
                            <option value="">اختر المدينة</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>"><?= sanitize($city['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="travel_date">
                            <i class="fas fa-calendar text-warning"></i> تاريخ السفر
                        </label>
                        <input type="date" name="travel_date" id="travel_date" class="form-control" 
                               min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="passengers">
                            <i class="fas fa-users text-info"></i> عدد المسافرين
                        </label>
                        <select name="passengers" id="passengers" class="form-control" required>
                            <option value="1">1 مسافر</option>
                            <option value="2">2 مسافر</option>
                            <option value="3">3 مسافرين</option>
                            <option value="4">4 مسافرين</option>
                            <option value="5">5 مسافرين</option>
                            <option value="6">6 مسافرين</option>
                        </select>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> البحث عن الرحلات
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- الميزات -->
    <section class="container mt-5">
        <h2 class="text-center mb-4">لماذا تختارنا؟</h2>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>حجز آمن</h4>
                        <p class="text-muted">نضمن لك حجز آمن ومحمي بأحدث تقنيات الأمان</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-3x text-secondary mb-3"></i>
                        <h4>حجز سريع</h4>
                        <p class="text-muted">احجز رحلتك في دقائق معدودة بخطوات بسيطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-headset fa-3x text-warning mb-3"></i>
                        <h4>دعم 24/7</h4>
                        <p class="text-muted">فريق الدعم متاح على مدار الساعة لمساعدتك</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- الوجهات الشائعة -->
    <section class="container mt-5">
        <h2 class="text-center mb-4">الوجهات الشائعة</h2>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-city fa-2x text-primary mb-2"></i>
                        <h5>الخرطوم - بورتسودان</h5>
                        <p class="text-muted">من 150 جنيه</p>
                        <a href="search.php?from_city=1&to_city=4" class="btn btn-outline">احجز الآن</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-mountain fa-2x text-secondary mb-2"></i>
                        <h5>الخرطوم - كسلا</h5>
                        <p class="text-muted">من 120 جنيه</p>
                        <a href="search.php?from_city=1&to_city=5" class="btn btn-outline">احجز الآن</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-tree fa-2x text-success mb-2"></i>
                        <h5>الخرطوم - نيالا</h5>
                        <p class="text-muted">من 250 جنيه</p>
                        <a href="search.php?from_city=1&to_city=8" class="btn btn-outline">احجز الآن</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-water fa-2x text-info mb-2"></i>
                        <h5>الخرطوم - القضارف</h5>
                        <p class="text-muted">من 130 جنيه</p>
                        <a href="search.php?from_city=1&to_city=6" class="btn btn-outline">احجز الآن</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- الفوتر -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>BusGo</h3>
                    <p>موقعك الأول لحجز رحلات الباصات في جميع أنحاء السودان بسهولة وأمان.</p>
                </div>
                <div class="footer-section">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="index.php">الرئيسية</a></li>
                        <li><a href="search.php">البحث عن الرحلات</a></li>
                        <li><a href="about.php">من نحن</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>خدمة العملاء</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> +249 123 456 789</li>
                        <li><i class="fas fa-envelope"></i> info@sudanbuses.com</li>
                        <li><i class="fas fa-clock"></i> متاح 24/7</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>تابعنا</h3>
                    <ul>
                        <li><a href="https://www.facebook.com"><i class="fab fa-facebook"></i> فيسبوك</a></li>
                        <li><a href="https://www.twitter.com"><i class="fab fa-twitter"></i> تويتر</a></li>
                        <li><a href="https://www.instagram.com"><i class="fab fa-instagram"></i> إنستغرام</a></li>
                        <li><a href="https://www.whatsapp.com"><i class="fab fa-whatsapp"></i> واتساب</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-3 pt-3" style="border-top: 1px solid #374151;">
                <p>&copy; 2025 BusGo. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/enhanced-main.js"></script>
</body>
</html>

