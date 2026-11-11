<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission for settings update
if (isPost()) {
    $site_name = getPost('site_name');
    $contact_email = getPost('contact_email');
    $contact_phone = getPost('contact_phone');
    $address = getPost('address');
    $facebook_link = getPost('facebook_link');
    $twitter_link = getPost('twitter_link');
    $instagram_link = getPost('instagram_link');

    // Update settings in the database (assuming a 'settings' table with key-value pairs)
    // This is a simplified example; a real implementation might use a single row or a more complex structure
    try {
        $db->beginTransaction();

        updateSetting($db, 'site_name', $site_name);
        updateSetting($db, 'contact_email', $contact_email);
        updateSetting($db, 'contact_phone', $contact_phone);
        updateSetting($db, 'address', $address);
        updateSetting($db, 'facebook_link', $facebook_link);
        updateSetting($db, 'twitter_link', $twitter_link);
        updateSetting($db, 'instagram_link', $instagram_link);

        $db->commit();
        $message = 'تم تحديث الإعدادات بنجاح!';
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage();
    }
}

// Function to update or insert a setting
function updateSetting($db, $key, $value) {
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// Fetch current settings
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if settings are not found
$site_name = $settings['site_name'] ?? 'نظام حجز الباصات';
$contact_email = $settings['contact_email'] ?? 'info@example.com';
$contact_phone = $settings['contact_phone'] ?? '+249123456789';
$address = $settings['address'] ?? 'الخرطوم، السودان';
$facebook_link = $settings['facebook_link'] ?? '#';
$twitter_link = $settings['twitter_link'] ?? '#';
$instagram_link = $settings['instagram_link'] ?? '#';

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - لوحة تحكم المسؤول</title>
    <link rel="stylesheet" href="../css/enhanced-style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: var(--dark-color);
            min-height: 100vh;
            padding: 20px 0;
        }
        .admin-sidebar .nav-link {
            color: #fff;
            padding: 15px 20px;
            border-radius: 0;
            margin-bottom: 5px;
        }
        .admin-sidebar .nav-link:hover {
            background: var(--primary-color);
            color: #fff;
        }
        .admin-sidebar .nav-link.active {
            background: var(--primary-color);
            color: #fff;
        }
        .admin-header {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-md-2 admin-sidebar">
                <div class="text-center text-white mb-4">
                    <i class="fas fa-user-shield fa-2x mb-2"></i>
                    <h5>لوحة التحكم</h5>
                    <small><?= sanitize($_SESSION["admin_name"]) ?></small>
                </div>
                
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> الرئيسية
                    </a>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i> إدارة المستخدمين
                    </a>
                    <a href="bookings.php" class="nav-link">
                        <i class="fas fa-ticket-alt"></i> إدارة الحجوزات
                    </a>
                    <a href="trips.php" class="nav-link">
                        <i class="fas fa-bus"></i> إدارة الرحلات
                    </a>
                    <a href="cities.php" class="nav-link">
                        <i class="fas fa-map-marker-alt"></i> إدارة المدن
                    </a>
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> التقارير
                    </a>
                    <a href="settings.php" class="nav-link active">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                    <hr class="text-white">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- المحتوى الرئيسي -->
            <div class="col-md-10">
                <!-- الهيدر -->
                <div class="admin-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-cog text-primary"></i> الإعدادات</h2>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">اسم الموقع</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?= sanitize($site_name) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">البريد الإلكتروني للتواصل</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= sanitize($contact_email) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">رقم الهاتف للتواصل</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= sanitize($contact_phone) ?>">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">العنوان</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= sanitize($address) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="facebook_link" class="form-label">رابط فيسبوك</label>
                                <input type="url" class="form-control" id="facebook_link" name="facebook_link" value="<?= sanitize($facebook_link) ?>">
                            </div>
                            <div class="mb-3">
                                <label for="twitter_link" class="form-label">رابط تويتر</label>
                                <input type="url" class="form-control" id="twitter_link" name="twitter_link" value="<?= sanitize($twitter_link) ?>">
                            </div>
                            <div class="mb-3">
                                <label for="instagram_link" class="form-label">رابط انستغرام</label>
                                <input type="url" class="form-control" id="instagram_link" name="instagram_link" value="<?= sanitize($instagram_link) ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


