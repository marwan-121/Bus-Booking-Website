<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// التحقق من تسجيل دخول المسؤول
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// الحصول على الإحصائيات
$stats = [];

// إجمالي المستخدمين
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetchColumn();

// إجمالي الحجوزات
$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings");
$stmt->execute();
$stats['total_bookings'] = $stmt->fetchColumn();

// الحجوزات اليوم
$stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE DATE(booking_date) = CURDATE()");
$stmt->execute();
$stats['today_bookings'] = $stmt->fetchColumn();

// إجمالي الإيرادات
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'");
$stmt->execute();
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// آخر الحجوزات
$stmt = $db->prepare("
    SELECT b.*, u.name as user_name, u.email as user_email,
           fc.name as from_city, tc.name as to_city,
           t.trip_date, t.departure_time
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    ORDER BY b.booking_date DESC
    LIMIT 10
");
$stmt->execute();
$recent_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BusGo - admin</title>
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
        .admin-sidebar .nav-link:hover,
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
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 10px;
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
                    <small><?= sanitize($_SESSION['admin_name']) ?></small>
                </div>
                
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link active">
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
                    <a href="settings.php" class="nav-link">
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
                        <h2><i class="fas fa-tachometer-alt text-primary"></i> لوحة التحكم الرئيسية</h2>
                        <div>
                            <span class="text-muted">مرحباً، <?= sanitize($_SESSION['admin_name']) ?></span>
                            <span class="badge bg-primary ms-2"><?= sanitize($_SESSION['admin_role']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- الإحصائيات -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?= number_format($stats['total_users']) ?></h3>
                                    <p class="mb-0">إجمالي المستخدمين</p>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?= number_format($stats['total_bookings']) ?></h3>
                                    <p class="mb-0">إجمالي الحجوزات</p>
                                </div>
                                <i class="fas fa-ticket-alt fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?= number_format($stats['today_bookings']) ?></h3>
                                    <p class="mb-0">حجوزات اليوم</p>
                                </div>
                                <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?= number_format($stats['total_revenue'], 2) ?></h3>
                                    <p class="mb-0">إجمالي الإيرادات (جنيه)</p>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- آخر الحجوزات -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history text-primary"></i> آخر الحجوزات</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-ticket-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted">لا توجد حجوزات بعد</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>رقم الحجز</th>
                                            <th>اسم المستخدم</th>
                                            <th>الرحلة</th>
                                            <th>تاريخ السفر</th>
                                            <th>المبلغ</th>
                                            <th>حالة الدفع</th>
                                            <th>تاريخ الحجز</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td><?= sanitize($booking['booking_reference']) ?></td>
                                                <td>
                                                    <strong><?= sanitize($booking['user_name']) ?></strong><br>
                                                    <small class="text-muted"><?= sanitize($booking['user_email']) ?></small>
                                                </td>
                                                <td>
                                                    <?= sanitize($booking['from_city']) ?> 
                                                    <i class="fas fa-arrow-left text-primary"></i>
                                                    <?= sanitize($booking['to_city']) ?>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($booking['trip_date'])) ?><br>
                                                    <small class="text-muted"><?= formatTime($booking['departure_time']) ?></small>
                                                </td>
                                                <td><?= formatPrice($booking['total_amount']) ?></td>
                                                <td>
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
                                                    <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($booking['booking_date'])) ?></td>
                                                <td>
                                                    <a href="booking_details.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="bookings.php" class="btn btn-primary">
                                    <i class="fas fa-list"></i> عرض جميع الحجوزات
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

