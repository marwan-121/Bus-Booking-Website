<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$user = getCurrentUser();
$error = '';
$success = '';

// معالجة تحديث البيانات
if (isPost()) {
    $name = getPost('name');
    $email = getPost('email');
    $phone = getPost('phone');
    $current_password = getPost('current_password');
    $new_password = getPost('new_password');
    $confirm_password = getPost('confirm_password');
    
    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'يرجى ملء جميع الحقول الأساسية';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } elseif (!validatePhone($phone)) {
        $error = 'رقم الهاتف غير صحيح';
    } else {
        // التحقق من وجود البريد الإلكتروني لمستخدم آخر
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error = 'البريد الإلكتروني مستخدم بالفعل من قبل مستخدم آخر';
        } else {
            // تحديث البيانات الأساسية
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            
            if ($stmt->execute([$name, $email, $phone, $_SESSION['user_id']])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // تحديث كلمة المرور إذا تم إدخالها
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = 'يرجى إدخال كلمة المرور الحالية';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'كلمة المرور الجديدة وتأكيدها غير متطابقتان';
                    } else {
                        // التحقق من كلمة المرور الحالية
                        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user_data = $stmt->fetch();
                        
                        if (!verifyPassword($current_password, $user_data['password'])) {
                            $error = 'كلمة المرور الحالية غير صحيحة';
                        } else {
                            // تحديث كلمة المرور
                            $hashed_password = hashPassword($new_password);
                            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            
                            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                                $success = 'تم تحديث البيانات وكلمة المرور بنجاح';
                            } else {
                                $error = 'حدث خطأ أثناء تحديث كلمة المرور';
                            }
                        }
                    }
                } else {
                    $success = 'تم تحديث البيانات بنجاح';
                }
                
                // تحديث بيانات المستخدم
                $user = getCurrentUser();
            } else {
                $error = 'حدث خطأ أثناء تحديث البيانات';
            }
        }
    }
}

// الحصول على إحصائيات المستخدم
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(total_amount) as total_spent
    FROM bookings 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// الحصول على آخر الحجوزات
$stmt = $db->prepare("
    SELECT b.*, 
           t.trip_date, t.departure_time,
           fc.name as from_city_name, 
           tc.name as to_city_name
    FROM bookings b
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - BusGo</title>
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
                    <li><a href="profile.php" class="active"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                    <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> حجوزاتي</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-4">
        <!-- ترحيب -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2><i class="fas fa-user-circle"></i> مرحباً، <?= sanitize($user['name']) ?></h2>
                                <p class="mb-0">إدارة ملفك الشخصي ومعلومات حسابك</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle fa-5x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt fa-2x text-primary mb-2"></i>
                        <h4><?= $stats['total_bookings'] ?: 0 ?></h4>
                        <p class="text-muted mb-0">إجمالي الحجوزات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4><?= $stats['paid_bookings'] ?: 0 ?></h4>
                        <p class="text-muted mb-0">الحجوزات المدفوعة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h4><?= $stats['pending_bookings'] ?: 0 ?></h4>
                        <p class="text-muted mb-0">في الانتظار</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-2x text-info mb-2"></i>
                        <h4><?= number_format($stats['total_spent'] ?: 0, 2) ?></h4>
                        <p class="text-muted mb-0">إجمالي المبلغ (جنيه)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- تحديث البيانات -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-edit text-primary"></i> تحديث البيانات الشخصية</h4>
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
                                        <i class="fas fa-user text-primary"></i> الاسم الكامل
                                    </label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                           value="<?= sanitize($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email">
                                        <i class="fas fa-envelope text-primary"></i> البريد الإلكتروني
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           value="<?= sanitize($user['email']) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone">
                                    <i class="fas fa-phone text-primary"></i> رقم الهاتف
                                </label>
                                <input type="tel" name="phone" id="phone" class="form-control" 
                                       value="<?= sanitize($user['phone']) ?>" required>
                            </div>

                            <hr>
                            <h5><i class="fas fa-lock text-secondary"></i> تغيير كلمة المرور (اختياري)</h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password">كلمة المرور الحالية</label>
                                    <input type="password" name="current_password" id="current_password" 
                                           class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="new_password">كلمة المرور الجديدة</label>
                                    <input type="password" name="new_password" id="new_password" 
                                           class="form-control" minlength="6">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password">تأكيد كلمة المرور</label>
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                           class="form-control">
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- آخر الحجوزات -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history text-primary"></i> آخر الحجوزات</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-ticket-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted">لا توجد حجوزات بعد</p>
                                <a href="search.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> ابحث عن رحلة
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= sanitize($booking['from_city_name']) ?> 
                                                <i class="fas fa-arrow-left text-primary"></i>
                                                <?= sanitize($booking['to_city_name']) ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($booking['trip_date'])) ?>
                                                - <?= formatTime($booking['departure_time']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                رقم الحجز: <?= sanitize($booking['booking_reference']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($booking['payment_status']) {
                                                case 'paid':
                                                    $status_class = 'success';
                                                    $status_text = 'مدفوع';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    $status_text = 'في الانتظار';
                                                    break;
                                                case 'failed':
                                                    $status_class = 'danger';
                                                    $status_text = 'فشل';
                                                    break;
                                                case 'refunded':
                                                    $status_class = 'info';
                                                    $status_text = 'مسترد';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $status_class ?> mb-1"><?= $status_text ?></span>
                                            <br>
                                            <small class="text-primary fw-bold"><?= formatPrice($booking['total_amount']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="bookings.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-list"></i> عرض جميع الحجوزات
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- معلومات الحساب -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle text-primary"></i> معلومات الحساب</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <i class="fas fa-calendar text-muted"></i>
                            <strong>تاريخ التسجيل:</strong><br>
                            <small class="text-muted">
                                <?php
                                $stmt = $db->prepare("SELECT created_at FROM users WHERE id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $user_data = $stmt->fetch();
                                echo date('d/m/Y', strtotime($user_data['created_at']));
                                ?>
                            </small>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-envelope text-muted"></i>
                            <strong>البريد الإلكتروني:</strong><br>
                            <small class="text-muted"><?= sanitize($user['email']) ?></small>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-phone text-muted"></i>
                            <strong>رقم الهاتف:</strong><br>
                            <small class="text-muted"><?= sanitize($user['phone']) ?></small>
                        </p>
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
    <script>
        // التحقق من تطابق كلمة المرور الجديدة
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });

        // إظهار/إخفاء حقول كلمة المرور
        document.getElementById('new_password').addEventListener('input', function() {
            const currentPasswordField = document.getElementById('current_password');
            if (this.value) {
                currentPasswordField.required = true;
                currentPasswordField.parentElement.querySelector('label').innerHTML = 
                    'كلمة المرور الحالية <span class="text-danger">*</span>';
            } else {
                currentPasswordField.required = false;
                currentPasswordField.parentElement.querySelector('label').innerHTML = 
                    'كلمة المرور الحالية';
            }
        });
    </script>
</body>
</html>

