<?php
require_once 'config/database.php';
require_once 'config/functions.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - BusGo</title>
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
                    <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> من نحن</a></li>
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
            <h1 class="fade-in">من نحن</h1>
            <p class="fade-in">تعرف على قصتنا ورؤيتنا في خدمة النقل السوداني</p>
        </div>
    </section>

    <div class="container mt-5">
        <!-- قصتنا -->
        <div class="row mb-5">
            <div class="col-md-6 fade-in">
                <h2><i class="fas fa-book text-primary"></i> قصتنا</h2>
                <p class="lead">
                    بدأت رحلتنا في عام 2020 برؤية واضحة: تسهيل السفر وتحسين تجربة النقل في السودان. 
                    نحن فريق من المتخصصين في التكنولوجيا والنقل، نعمل بشغف لتقديم حلول مبتكرة تلبي احتياجات المسافرين.
                </p>
                <p>
                    منذ انطلاقتنا، نجحنا في ربط أكثر من 50 مدينة سودانية، وخدمنا آلاف المسافرين، 
                    وأصبحنا الخيار الأول للعديد من الأسر والشركات في السودان.
                </p>
            </div>
            <div class="col-md-6 fade-in">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h4>أكثر من 10,000 عميل راضٍ</h4>
                        <p class="text-muted">نفخر بثقة عملائنا وولائهم لخدماتنا</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- رؤيتنا ورسالتنا -->
        <div class="row mb-5">
            <div class="col-md-6 mb-4">
                <div class="card h-100 fade-in">
                    <div class="card-body text-center">
                        <i class="fas fa-eye fa-3x text-primary mb-3"></i>
                        <h3>رؤيتنا</h3>
                        <p>
                            أن نكون الرائدين في مجال حجز النقل الإلكتروني في السودان، 
                            ونساهم في تطوير قطاع النقل وتحسين تجربة السفر للجميع.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100 fade-in">
                    <div class="card-body text-center">
                        <i class="fas fa-bullseye fa-3x text-secondary mb-3"></i>
                        <h3>رسالتنا</h3>
                        <p>
                            تقديم منصة موثوقة وسهلة الاستخدام لحجز تذاكر النقل، 
                            مع ضمان أعلى معايير الجودة والأمان والراحة لعملائنا.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- قيمنا -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4 fade-in"><i class="fas fa-heart text-primary"></i> قيمنا</h2>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100 fade-in">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>
                        <h5>الأمان</h5>
                        <p class="text-muted">نضع سلامة عملائنا في المقدمة دائماً</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100 fade-in">
                    <div class="card-body">
                        <i class="fas fa-handshake fa-2x text-success mb-3"></i>
                        <h5>الثقة</h5>
                        <p class="text-muted">نبني علاقات طويلة الأمد مع عملائنا</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100 fade-in">
                    <div class="card-body">
                        <i class="fas fa-rocket fa-2x text-warning mb-3"></i>
                        <h5>الابتكار</h5>
                        <p class="text-muted">نسعى دائماً لتطوير خدماتنا وتحسينها</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100 fade-in">
                    <div class="card-body">
                        <i class="fas fa-star fa-2x text-info mb-3"></i>
                        <h5>التميز</h5>
                        <p class="text-muted">نلتزم بأعلى معايير الجودة في كل ما نقدمه</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- فريق العمل -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4 fade-in"><i class="fas fa-users text-primary"></i> فريق العمل</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <div class="team-avatar mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5>أحمد محمد</h5>
                        <p class="text-muted">المدير التنفيذي</p>
                        <p>خبرة 15 عاماً في مجال النقل والتكنولوجيا</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <div class="team-avatar mb-3">
                            <i class="fas fa-user-circle fa-5x text-secondary"></i>
                        </div>
                        <h5>فاطمة علي</h5>
                        <p class="text-muted">مديرة التطوير</p>
                        <p>متخصصة في تطوير المنصات الرقمية</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <div class="team-avatar mb-3">
                            <i class="fas fa-user-circle fa-5x text-success"></i>
                        </div>
                        <h5>محمد عثمان</h5>
                        <p class="text-muted">مدير خدمة العملاء</p>
                        <p>خبرة واسعة في خدمة العملاء والدعم الفني</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائياتنا -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4 fade-in"><i class="fas fa-chart-bar text-primary"></i> إحصائياتنا</h2>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                        <h3 class="text-primary">50+</h3>
                        <p class="mb-0">مدينة مخدومة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class="fas fa-bus fa-2x text-success mb-2"></i>
                        <h3 class="text-success">200+</h3>
                        <p class="mb-0">رحلة يومية</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt fa-2x text-warning mb-2"></i>
                        <h3 class="text-warning">50,000+</h3>
                        <p class="mb-0">تذكرة محجوزة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class="fas fa-smile fa-2x text-info mb-2"></i>
                        <h3 class="text-info">98%</h3>
                        <p class="mb-0">رضا العملاء</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- اتصل بنا -->
        <div class="row">
            <div class="col-12">
                <div class="card fade-in">
                    <div class="card-body text-center">
                        <h3><i class="fas fa-phone text-primary"></i> تواصل معنا</h3>
                        <p class="lead">نحن هنا لخدمتك على مدار الساعة</p>
                        <div class="row">
                            <div class="col-md-4">
                                <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                <p><strong>الهاتف:</strong><br>+249 123 456 789</p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <p><strong>البريد الإلكتروني:</strong><br>info@sudanbuses.com</p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                                <p><strong>العنوان:</strong><br>الخرطوم، السودان</p>
                            </div>
                        </div>
                        <a href="contact.php" class="btn btn-primary mt-3">
                            <i class="fas fa-envelope"></i> راسلنا الآن
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <footer class="footer">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2025 BusGo. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/enhanced-main.js"></script>
</body>
</html>

