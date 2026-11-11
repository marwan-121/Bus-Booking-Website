<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id === 0) {
    redirect('dashboard.php'); // Redirect if no ID is provided
}

$stmt = $db->prepare("
    SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
           fc.name as from_city, tc.name as to_city,
           t.trip_date, t.departure_time,
           t.price as trip_price
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    WHERE b.id = :booking_id
");
$stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    // Booking not found, redirect or show error
    redirect('dashboard.php');
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الحجز - BusGo</title>
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
        .card-header {
            background-color: var(--primary-color);
            color: white;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-item strong {
            display: inline-block;
            width: 150px; /* Adjust as needed for alignment */
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
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> الرئيسية
                    </a>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i> إدارة المستخدمين
                    </a>
                    <a href="bookings.php" class="nav-link active">
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
                        <h2><i class="fas fa-ticket-alt text-primary"></i> تفاصيل الحجز</h2>
                        <div>
                            <span class="text-muted">مرحباً، <?= sanitize($_SESSION['admin_name']) ?></span>
                            <span class="badge bg-primary ms-2"><?= sanitize($_SESSION['admin_role']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- تفاصيل الحجز -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات الحجز #<?= sanitize($booking['booking_reference']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item"><strong>رقم الحجز:</strong> <?= sanitize($booking['booking_reference']) ?></div>
                                <div class="detail-item"><strong>تاريخ الحجز:</strong> <?= date('d/m/Y H:i', strtotime($booking['booking_date'])) ?></div>
                                <div class="detail-item"><strong>عدد المقاعد:</strong> <?= sanitize($booking['seats_booked']) ?></div>
                                <div class="detail-item"><strong>المبلغ الإجمالي:</strong> <?= formatPrice($booking['total_amount']) ?></div>
                                <div class="detail-item"><strong>حالة الدفع:</strong> 
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($booking['payment_status']) {
                                        case 'paid': $status_class = 'success'; $status_text = 'مدفوع'; break;
                                        case 'pending': $status_class = 'warning'; $status_text = 'في الانتظار'; break;
                                        case 'failed': $status_class = 'danger'; $status_text = 'فشل'; break;
                                        case 'refunded': $status_class = 'info'; $status_text = 'مسترد'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item"><strong>اسم المستخدم:</strong> <?= sanitize($booking['user_name']) ?></div>
                                <div class="detail-item"><strong>بريد المستخدم:</strong> <?= sanitize($booking['user_email']) ?></div>
                                <div class="detail-item"><strong>رقم هاتف المستخدم:</strong> <?= sanitize($booking['user_phone']) ?></div>
                            
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تفاصيل الرحلة -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات الرحلة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item"><strong>من:</strong> <?= sanitize($booking['from_city']) ?></div>
                                <div class="detail-item"><strong>إلى:</strong> <?= sanitize($booking['to_city']) ?></div>
                                <div class="detail-item"><strong>تاريخ السفر:</strong> <?= date('d/m/Y', strtotime($booking['trip_date'])) ?></div>
                                <div class="detail-item"><strong>وقت المغادرة:</strong> <?= formatTime($booking['departure_time']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item"><strong>سعر الرحلة:</strong> <?= formatPrice($booking['trip_price']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <a href="dashboard.php" class="btn btn-secondary me-2"><i class="fas fa-arrow-left"></i> العودة للوحة التحكم</a>
                    <a href="bookings.php" class="btn btn-info"><i class="fas fa-list"></i> عرض جميع الحجوزات</a>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
