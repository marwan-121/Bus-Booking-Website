<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$error = '';
$success = '';

if (isPost()) {
    $name = getPost('name');
    $email = getPost('email');
    $phone = getPost('phone');
    $subject = getPost('subject');
    $message = getPost('message');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        // هنا يمكن إضافة كود إرسال البريد الإلكتروني أو حفظ الرسالة في قاعدة البيانات
        $success = 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.';
        
        // مسح النموذج بعد الإرسال الناجح
        $_POST = [];
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - BusGo</title>
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
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> من نحن</a></li>
                    <li><a href="contact.php" class="active"><i class="fas fa-envelope"></i> اتصل بنا</a></li>
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
            <h1 class="fade-in">اتصل بنا</h1>
            <p class="fade-in">نحن هنا لمساعدتك والإجابة على استفساراتك</p>
        </div>
    </section>

    <div class="container mt-5">
        <div class="row">
            <!-- نموذج الاتصال -->
            <div class="col-md-8 mb-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope text-primary"></i> أرسل لنا رسالة</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= $success ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name">
                                        <i class="fas fa-user text-primary"></i> الاسم الكامل *
                                    </label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                           value="<?= sanitize(getPost('name')) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email">
                                        <i class="fas fa-envelope text-primary"></i> البريد الإلكتروني *
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           value="<?= sanitize(getPost('email')) ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone">
                                        <i class="fas fa-phone text-primary"></i> رقم الهاتف
                                    </label>
                                    <input type="tel" name="phone" id="phone" class="form-control" 
                                           value="<?= sanitize(getPost('phone')) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject">
                                        <i class="fas fa-tag text-primary"></i> الموضوع *
                                    </label>
                                    <select name="subject" id="subject" class="form-control" required>
                                        <option value="">اختر الموضوع</option>
                                        <option value="booking_inquiry" <?= getPost('subject') === 'booking_inquiry' ? 'selected' : '' ?>>
                                            استفسار عن الحجز
                                        </option>
                                        <option value="technical_support" <?= getPost('subject') === 'technical_support' ? 'selected' : '' ?>>
                                            دعم فني
                                        </option>
                                        <option value="complaint" <?= getPost('subject') === 'complaint' ? 'selected' : '' ?>>
                                            شكوى
                                        </option>
                                        <option value="suggestion" <?= getPost('subject') === 'suggestion' ? 'selected' : '' ?>>
                                            اقتراح
                                        </option>
                                        <option value="partnership" <?= getPost('subject') === 'partnership' ? 'selected' : '' ?>>
                                            شراكة
                                        </option>
                                        <option value="other" <?= getPost('subject') === 'other' ? 'selected' : '' ?>>
                                            أخرى
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message">
                                    <i class="fas fa-comment text-primary"></i> الرسالة *
                                </label>
                                <textarea name="message" id="message" class="form-control" rows="6" 
                                          placeholder="اكتب رسالتك هنا..." required><?= sanitize(getPost('message')) ?></textarea>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> إرسال الرسالة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- معلومات الاتصال -->
            <div class="col-md-4 mb-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle text-primary"></i> معلومات الاتصال</h4>
                    </div>
                    <div class="card-body">
                        <div class="contact-info">
                            <div class="contact-item mb-4">
                                <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                <h5>الهاتف</h5>
                                <p class="text-muted">+249 123 456 789</p>
                                <p class="text-muted">+249 987 654 321</p>
                            </div>

                            <div class="contact-item mb-4">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h5>البريد الإلكتروني</h5>
                                <p class="text-muted">info@sudanbuses.com</p>
                                <p class="text-muted">support@sudanbuses.com</p>
                            </div>

                            <div class="contact-item mb-4">
                                <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                                <h5>العنوان</h5>
                                <p class="text-muted">
                                    شارع النيل، الخرطوم<br>
                                    مجمع الأعمال التجاري<br>
                                    الطابق الثالث، مكتب 301
                                </p>
                            </div>

                            <div class="contact-item mb-4">
                                <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                <h5>ساعات العمل</h5>
                                <p class="text-muted">
                                    السبت - الخميس: 8:00 ص - 8:00 م<br>
                                    الجمعة: 2:00 م - 8:00 م
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- وسائل التواصل الاجتماعي -->
                <div class="card mt-3 fade-in">
                    <div class="card-header">
                        <h5><i class="fas fa-share-alt text-primary"></i> تابعنا</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="social-links">
                            <a href="https://www.facebook.com" class="btn btn-outline-primary me-2 mb-2">
                                <i class="fab fa-facebook-f"></i> فيسبوك
                            </a>
                            <a href="https://www.twitter.com" class="btn btn-outline-info me-2 mb-2">
                                <i class="fab fa-twitter"></i> تويتر
                            </a>
                            <a href="https://www.instagram.com" class="btn btn-outline-danger me-2 mb-2">
                                <i class="fab fa-instagram"></i> إنستغرام
                            </a>
                            <a href="https://www.whatsapp.com" class="btn btn-outline-success me-2 mb-2">
                                <i class="fab fa-whatsapp"></i> واتساب
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الأسئلة الشائعة -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card fade-in">
                    <div class="card-header">
                        <h3><i class="fas fa-question-circle text-primary"></i> الأسئلة الشائعة</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse1">
                                        كيف يمكنني حجز تذكرة؟
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        يمكنك حجز تذكرة بسهولة من خلال البحث عن الرحلة المناسبة، اختيار المقاعد، 
                                        وإكمال عملية الدفع. العملية بسيطة وتستغرق دقائق معدودة.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse2">
                                        ما هي طرق الدفع المتاحة؟
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        نوفر طريقتين للدفع: الدفع عند الاستلام والدفع بالبطاقة الائتمانية. 
                                        يمكنك اختيار الطريقة الأنسب لك أثناء عملية الحجز.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse3">
                                        هل يمكنني إلغاء أو تعديل الحجز؟
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        نعم، يمكنك إلغاء أو تعديل الحجز قبل 24 ساعة من موعد السفر. 
                                        تواصل معنا عبر خدمة العملاء لمساعدتك في ذلك.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse4">
                                        ماذا أحتاج عند الصعود للباص؟
                                    </button>
                                </h2>
                                <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        تحتاج إلى إظهار تذكرة الحجز (مطبوعة أو على الهاتف) وبطاقة الهوية. 
                                        ننصح بالوصول قبل 30 دقيقة من موعد المغادرة.
                                    </div>
                                </div>
                            </div>
                        </div>
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

