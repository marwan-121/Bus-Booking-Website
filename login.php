<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// إذا كان المسؤول مسجل دخول بالفعل، إعادة توجيه للوحة التحكم
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if (isPost()) {
    $username = getPost('username');
    $password = getPost('password');
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول';
    } else {
        $db = getDB();
        
        // البحث عن المسؤول
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && verifyPassword($password, $admin['password'])) {
            // تسجيل دخول ناجح
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // تحديث آخر تسجيل دخول
            $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            redirect('dashboard.php');
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المسؤول - BusGo</title>
    <link rel="stylesheet" href="../css/enhanced-style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .admin-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .admin-logo i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .admin-logo h3 {
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        .admin-logo p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <div class="admin-logo">
            <i class="fas fa-user-shield"></i>
            <h3>لوحة تحكم المسؤول</h3>
            <p>حجز الباصات السودانية</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user text-primary"></i> اسم المستخدم
                </label>
                <input type="text" name="username" id="username" class="form-control" 
                       value="<?= sanitize(getPost('username')) ?>" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock text-primary"></i> كلمة المرور
                </label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
            </button>
        </form>

        <div class="text-center">
            <a href="../index.php" class="text-muted">
                <i class="fas fa-arrow-left"></i> العودة للموقع الرئيسي
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

