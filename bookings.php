<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// التحقق من تسجيل دخول المسؤول
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$message = '';
$error = '';

// معالجة الإجراءات
if (isPost()) {
    $action = getPost('action');
    $booking_id = getPost('booking_id');
    
    switch ($action) {
        case 'update_status':
            $payment_status = getPost('payment_status');
            $stmt = $db->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            if ($stmt->execute([$payment_status, $booking_id])) {
                $message = 'تم تحديث حالة الحجز بنجاح';
            } else {
                $error = 'حدث خطأ أثناء تحديث الحجز';
            }
            break;
            
        case 'cancel_booking':
            $stmt = $db->prepare("UPDATE bookings SET payment_status = 'cancelled' WHERE id = ?");
            if ($stmt->execute([$booking_id])) {
                $message = 'تم إلغاء الحجز بنجاح';
            } else {
                $error = 'حدث خطأ أثناء إلغاء الحجز';
            }
            break;
    }
}

// البحث والفلترة
$search = getGet('search', '');
$status_filter = getGet('status', '');
$date_filter = getGet('date', '');
$page = max(1, getGet('page', 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.booking_reference LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "b.payment_status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(b.booking_date) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// الحصول على إجمالي عدد الحجوزات
$count_sql = "SELECT COUNT(*) FROM bookings b 
              JOIN users u ON b.user_id = u.id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_bookings = $stmt->fetchColumn();
$total_pages = ceil($total_bookings / $limit);

// الحصول على الحجوزات
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
               t.trip_date, t.departure_time, t.arrival_time,
               fc.name as from_city, tc.name as to_city,
               bt.name as bus_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN trips t ON b.trip_id = t.id
        JOIN cities fc ON t.from_city_id = fc.id
        JOIN cities tc ON t.to_city_id = tc.id
        JOIN bus_types bt ON t.bus_type_id = bt.id
        $where_clause
        ORDER BY b.booking_date DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الحجوزات - لوحة تحكم المسؤول</title>
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
                        <h2><i class="fas fa-ticket-alt text-primary"></i> إدارة الحجوزات</h2>
                        <div>
                            <span class="text-muted">إجمالي الحجوزات: <?= number_format($total_bookings) ?></span>
                        </div>
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

                <!-- البحث والفلترة -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="البحث برقم الحجز أو اسم المستخدم..."
                                       value="<?= sanitize($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">جميع الحالات</option>
                                    <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>مدفوع</option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>في الانتظار</option>
                                    <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>فشل</option>
                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                                    <option value="refunded" <?= $status_filter === 'refunded' ? 'selected' : '' ?>>مسترد</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date" class="form-control" 
                                       value="<?= sanitize($date_filter) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </form>
                        <?php if (!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
                            <div class="mt-2">
                                <a href="bookings.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> إلغاء الفلترة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- قائمة الحجوزات -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <h4>لا توجد حجوزات</h4>
                                <p class="text-muted">لم يتم العثور على أي حجوزات.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>رقم الحجز</th>
                                            <th>المستخدم</th>
                                            <th>الرحلة</th>
                                            <th>تاريخ السفر</th>
                                            <th>المقاعد</th>
                                            <th>المبلغ</th>
                                            <th>حالة الدفع</th>
                                            <th>تاريخ الحجز</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= sanitize($booking['booking_reference']) ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?= sanitize($booking['user_name']) ?></strong><br>
                                                    <small class="text-muted"><?= sanitize($booking['user_email']) ?></small><br>
                                                    <small class="text-muted"><?= sanitize($booking['user_phone']) ?></small>
                                                </td>
                                                <td>
                                                    <?= sanitize($booking['from_city']) ?> 
                                                    <i class="fas fa-arrow-left text-primary"></i>
                                                    <?= sanitize($booking['to_city']) ?><br>
                                                    <small class="text-muted"><?= sanitize($booking['bus_type']) ?></small>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($booking['trip_date'])) ?><br>
                                                    <small class="text-muted"><?= formatTime($booking['departure_time']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $booking['seats_booked'] ?></span><br>
                                                    <small class="text-muted"><?= sanitize($booking['seat_numbers']) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= formatPrice($booking['total_amount']) ?></strong><br>
                                                    <small class="text-muted"><?= $booking['payment_method'] === 'credit_card' ? 'بطاقة ائتمانية' : 'دفع عند الاستلام' ?></small>
                                                </td>
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
                                                        case 'cancelled':
                                                            $status_class = 'secondary';
                                                            $status_text = 'ملغي';
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
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                onclick="updateStatus(<?= $booking['id'] ?>, '<?= $booking['payment_status'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($booking['payment_status'] !== 'cancelled'): ?>
                                                            <button class="btn btn-outline-danger btn-sm" 
                                                                    onclick="cancelBooking(<?= $booking['id'] ?>, '<?= sanitize($booking['booking_reference']) ?>')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- الترقيم -->
                            <?php if ($total_pages > 1): ?>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>">السابق</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>">التالي</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نموذج تحديث حالة الحجز -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تحديث حالة الحجز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="booking_id" id="update_booking_id">
                        
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">حالة الدفع</label>
                            <select name="payment_status" id="payment_status" class="form-control" required>
                                <option value="pending">في الانتظار</option>
                                <option value="paid">مدفوع</option>
                                <option value="failed">فشل</option>
                                <option value="cancelled">ملغي</option>
                                <option value="refunded">مسترد</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج إلغاء الحجز -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد إلغاء الحجز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cancel_booking">
                        <input type="hidden" name="booking_id" id="cancel_booking_id">
                        
                        <p>هل أنت متأكد من إلغاء الحجز رقم <strong id="cancel_booking_ref"></strong>؟</p>
                        <p class="text-danger">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">إلغاء الحجز</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(bookingId, currentStatus) {
            document.getElementById('update_booking_id').value = bookingId;
            document.getElementById('payment_status').value = currentStatus;
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
        
        function cancelBooking(bookingId, bookingRef) {
            document.getElementById('cancel_booking_id').value = bookingId;
            document.getElementById('cancel_booking_ref').textContent = bookingRef;
            
            new bootstrap.Modal(document.getElementById('cancelBookingModal')).show();
        }
    </script>
</body>
</html>

