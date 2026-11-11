<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// الحصول على حجوزات المستخدم
$stmt = $db->prepare("
    SELECT b.*, 
           t.trip_date, t.departure_time, t.arrival_time,
           fc.name as from_city_name, 
           tc.name as to_city_name,
           bt.name as bus_type_name
    FROM bookings b
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    JOIN bus_types bt ON t.bus_type_id = bt.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
");

$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجوزاتي - BusGo</title>
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
                    <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                    <li><a href="bookings.php" class="active"><i class="fas fa-ticket-alt"></i> حجوزاتي</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-ticket-alt text-primary"></i> حجوزاتي (<?= count($bookings) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h4>لا توجد حجوزات</h4>
                        <p class="text-muted">لم تقم بأي حجوزات بعد.</p>
                        <a href="search.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> ابحث عن رحلة
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card border">
                                    <div class="card-header">
                                        <div class="row">
                                            <div class="col-8">
                                                <h5 class="mb-0">
                                                    <?= sanitize($booking['from_city_name']) ?> 
                                                    <i class="fas fa-arrow-left text-primary"></i>
                                                    <?= sanitize($booking['to_city_name']) ?>
                                                </h5>
                                            </div>
                                            <div class="col-4 text-end">
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
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">رقم الحجز</small>
                                                <p class="mb-0 fw-bold"><?= sanitize($booking['booking_reference']) ?></p>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">تاريخ السفر</small>
                                                <p class="mb-0"><?= date('d/m/Y', strtotime($booking['trip_date'])) ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">وقت المغادرة</small>
                                                <p class="mb-0"><?= formatTime($booking['departure_time']) ?></p>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">وقت الوصول</small>
                                                <p class="mb-0"><?= formatTime($booking['arrival_time']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">نوع الباص</small>
                                                <p class="mb-0"><?= sanitize($booking['bus_type_name']) ?></p>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">عدد المقاعد</small>
                                                <p class="mb-0"><?= $booking['seats_booked'] ?> مقعد</p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['seat_numbers']): ?>
                                            <div class="row mb-2">
                                                <div class="col-12">
                                                    <small class="text-muted">أرقام المقاعد</small>
                                                    <p class="mb-0"><?= sanitize($booking['seat_numbers']) ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">طريقة الدفع</small>
                                                <p class="mb-0">
                                                    <?= $booking['payment_method'] === 'credit_card' ? 'بطاقة ائتمانية' : 'دفع عند الاستلام' ?>
                                                </p>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">المبلغ الإجمالي</small>
                                                <p class="mb-0 fw-bold text-primary"><?= formatPrice($booking['total_amount']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <small class="text-muted">تاريخ الحجز</small>
                                                <p class="mb-0"><?= date('d/m/Y H:i', strtotime($booking['booking_date'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <?php if ($booking['payment_status'] === 'pending' && $booking['payment_method'] === 'credit_card'): ?>
                                            <a href="payment.php?booking_ref=<?= $booking['booking_reference'] ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-credit-card"></i> إكمال الدفع
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (strtotime($booking['trip_date']) > time()): ?>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="cancelBooking('<?= $booking['booking_reference'] ?>')">
                                                <i class="fas fa-times"></i> إلغاء الحجز
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="print_ticket.php?booking_ref=<?= $booking["booking_reference"] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-print"></i> طباعة التذكرة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
        function cancelBooking(bookingRef) {
            if (confirm('هل أنت متأكد من إلغاء هذا الحجز؟')) {
                // هنا يمكن إضافة AJAX لإلغاء الحجز
                alert('تم إلغاء الحجز بنجاح');
                location.reload();
            }
        }
        
        function printTicket(bookingRef) {
            // فتح نافذة طباعة التذكرة
            window.open('print_ticket.php?booking_ref=' + bookingRef, '_blank');
        }
    </script>
</body>
</html>

