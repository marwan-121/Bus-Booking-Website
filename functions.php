<?php
// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// دالة تشفير كلمة المرور
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// دالة التحقق من كلمة المرور
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// دالة التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// دالة الحصول على معلومات المستخدم الحالي
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// دالة تسجيل الخروج
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function generateTransactionId() {
    return strtoupper(uniqid('TXN'));
}


// دالة إنشاء رقم مرجعي للحجز
function generateBookingReference() {
    return 'SB' . date('Ymd') . rand(1000, 9999);
}

// دالة تنسيق التاريخ
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

// دالة تنسيق الوقت
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// دالة تنسيق السعر
function formatPrice($price) {
    return number_format($price, 2) . ' جنيه';
}

// دالة التحقق من صحة البريد الإلكتروني
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// دالة التحقق من صحة رقم الهاتف
function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s]+$/', $phone);
}

// دالة عرض الرسائل
function showMessage($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// دالة الحماية من XSS
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// دالة إعادة التوجيه
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// دالة التحقق من الطلبات POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// دالة الحصول على قيمة من POST مع قيمة افتراضية
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// دالة الحصول على قيمة من GET مع قيمة افتراضية
function getGet($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}


// دوال المسؤول (الآدمن)

// دالة التحقق من تسجيل دخول المسؤول
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// دالة الحصول على معلومات المسؤول الحالي
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, name, email, role FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

// دالة تسجيل خروج المسؤول
function adminLogout() {
    // إزالة متغيرات جلسة المسؤول فقط
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    
    header('Location: login.php');
    exit();
}

// دالة التحقق من صلاحيات المسؤول
function hasAdminPermission($permission) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $admin_role = $_SESSION['admin_role'];
    
    // المدير العام له جميع الصلاحيات
    if ($admin_role === 'super_admin') {
        return true;
    }
    
    // تحديد الصلاحيات حسب الدور
    $permissions = [
        'admin' => ['view_users', 'edit_users', 'view_bookings', 'edit_bookings', 'view_reports'],
        'moderator' => ['view_users', 'view_bookings', 'edit_bookings'],
        'viewer' => ['view_users', 'view_bookings', 'view_reports']
    ];
    
    return isset($permissions[$admin_role]) && in_array($permission, $permissions[$admin_role]);
}





