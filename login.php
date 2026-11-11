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
    $email = getPost('email');
    $password = getPost('password');
    
    if (empty($email) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif (!validateEmail($email)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            $success = 'تم تسجيل الدخول بنجاح';
            header('refresh:2;url=index.php');
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - BusGo</title>
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
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> تسجيل جديد</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="fas fa-sign-in-alt text-primary"></i> تسجيل الدخول</h3>
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
                                <label for="email">
                                    <i class="fas fa-envelope text-primary"></i> البريد الإلكتروني
                                </label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?= sanitize(getPost('email')) ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">
                                    <i class="fas fa-lock text-primary"></i> كلمة المرور
                                </label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>ليس لديك حساب؟ <a href="register.php" class="text-primary">سجل الآن</a></p>
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
</body>
</html>

