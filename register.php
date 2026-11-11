<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$error = '';
$success = '';

// إذا كان المستخدم مسجل دخول بالفعل، إعادة توجيه للصفحة الرئيسية
if (isLoggedIn()) {
    redirect('index.php');
}

if (isPost()) {
    $name = getPost('name');
    $email = getPost('email');
    $phone = getPost('phone');
    $password = getPost('password');
    $confirm_password = getPost('confirm_password');
    
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } elseif (!validatePhone($phone)) {
        $error = 'رقم الهاتف غير صحيح';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمة المرور وتأكيد كلمة المرور غير متطابقتان';
    } else {
        $db = getDB();
        
        // التحقق من وجود البريد الإلكتروني
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'البريد الإلكتروني مستخدم بالفعل';
        } else {
            // إنشاء المستخدم الجديد
            $hashed_password = hashPassword($password);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $phone, $hashed_password])) {
                $success = 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول';
                header('refresh:3;url=login.php');
            } else {
                $error = 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل جديد - BusGo</title>
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
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-user-plus text-primary"></i> تسجيل جديد</h3>
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
                            <div class="form-group mb-3">
                                <label for="name">
                                    <i class="fas fa-user text-primary"></i> الاسم الكامل
                                </label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="<?= sanitize(getPost('name')) ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="email">
                                    <i class="fas fa-envelope text-primary"></i> البريد الإلكتروني
                                </label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?= sanitize(getPost('email')) ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="phone">
                                    <i class="fas fa-phone text-primary"></i> رقم الهاتف
                                </label>
                                <input type="tel" name="phone" id="phone" class="form-control" 
                                       value="<?= sanitize(getPost('phone')) ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">
                                    <i class="fas fa-lock text-primary"></i> كلمة المرور
                                </label>
                                <input type="password" name="password" id="password" class="form-control" 
                                       minlength="6" required>
                                <small class="text-muted">يجب أن تكون 6 أحرف على الأقل</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="confirm_password">
                                    <i class="fas fa-lock text-primary"></i> تأكيد كلمة المرور
                                </label>
                                <input type="password" name="confirm_password" id="confirm_password" 
                                       class="form-control" required>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> إنشاء الحساب
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>لديك حساب بالفعل؟ <a href="login.php" class="text-primary">سجل دخولك</a></p>
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
    <script>
        // التحقق من تطابق كلمة المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

