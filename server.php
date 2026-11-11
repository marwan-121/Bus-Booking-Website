<?php
// خادم PHP المحلي للتطوير والاختبار
// تشغيل الأمر: php -S 0.0.0.0:8000 server.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// إذا كان الملف موجود، عرضه مباشرة
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// إعادة توجيه الطلبات للملفات المناسبة
if ($uri === '/') {
    include_once 'index.php';
} else {
    // محاولة العثور على الملف
    $file = __DIR__ . $uri;
    if (file_exists($file . '.php')) {
        include_once $file . '.php';
    } elseif (file_exists($file)) {
        return false; // دع الخادم يتعامل مع الملف
    } else {
        // صفحة 404
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الصفحة غير موجودة - 404</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 text-center">
        <h1>404 - الصفحة غير موجودة</h1>
        <p>الصفحة التي تبحث عنها غير موجودة.</p>
        <a href="/" class="btn btn-primary">العودة للرئيسية</a>
    </div>
</body>
</html>';
    }
}
?>

