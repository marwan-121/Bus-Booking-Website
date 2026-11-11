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
    
    switch ($action) {
        case 'add_trip':
            $from_city_id = getPost('from_city_id');
            $to_city_id = getPost('to_city_id');
            $departure_time = getPost('departure_time');
            $arrival_time = getPost('arrival_time');
            $bus_type_id = getPost('bus_type_id');
            $price = getPost('price');
            $trip_date = getPost('trip_date');
            
            if (empty($from_city_id) || empty($to_city_id) || empty($departure_time) || empty($arrival_time) || empty($bus_type_id) || empty($price) || empty($trip_date)) {
                $error = 'يرجى ملء جميع الحقول';
            } else {
                // الحصول على عدد المقاعد من نوع الباص
                $stmt = $db->prepare("SELECT total_seats FROM bus_types WHERE id = ?");
                $stmt->execute([$bus_type_id]);
                $bus_type = $stmt->fetch();
                
                if ($bus_type) {
                    $stmt = $db->prepare("INSERT INTO trips (from_city_id, to_city_id, departure_time, arrival_time, bus_type_id, total_seats, available_seats, price, trip_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$from_city_id, $to_city_id, $departure_time, $arrival_time, $bus_type_id, $bus_type['total_seats'], $bus_type['total_seats'], $price, $trip_date])) {
                        $message = 'تم إضافة الرحلة بنجاح';
                    } else {
                        $error = 'حدث خطأ أثناء إضافة الرحلة';
                    }
                } else {
                    $error = 'نوع الباص غير موجود';
                }
            }
            break;
            
        case 'edit_trip':
            $trip_id = getPost('trip_id');
            $from_city_id = getPost('from_city_id');
            $to_city_id = getPost('to_city_id');
            $departure_time = getPost('departure_time');
            $arrival_time = getPost('arrival_time');
            $price = getPost('price');
            $status = getPost('status');
            
            $stmt = $db->prepare("UPDATE trips SET from_city_id = ?, to_city_id = ?, departure_time = ?, arrival_time = ?, price = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$from_city_id, $to_city_id, $departure_time, $arrival_time, $price, $status, $trip_id])) {
                $message = 'تم تحديث الرحلة بنجاح';
            } else {
                $error = 'حدث خطأ أثناء تحديث الرحلة';
            }
            break;
            
        case 'delete_trip':
            $trip_id = getPost('trip_id');
            
            // التحقق من وجود حجوزات للرحلة
            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE trip_id = ?");
            $stmt->execute([$trip_id]);
            $booking_count = $stmt->fetchColumn();
            
            if ($booking_count > 0) {
                $error = 'لا يمكن حذف الرحلة لأن لديها حجوزات موجودة';
            } else {
                $stmt = $db->prepare("DELETE FROM trips WHERE id = ?");
                if ($stmt->execute([$trip_id])) {
                    $message = 'تم حذف الرحلة بنجاح';
                } else {
                    $error = 'حدث خطأ أثناء حذف الرحلة';
                }
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
    $where_conditions[] = "(fc.name LIKE ? OR tc.name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "t.trip_date = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// الحصول على إجمالي عدد الرحلات
$count_sql = "SELECT COUNT(*) FROM trips t 
              JOIN cities fc ON t.from_city_id = fc.id 
              JOIN cities tc ON t.to_city_id = tc.id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_trips = $stmt->fetchColumn();
$total_pages = ceil($total_trips / $limit);

// الحصول على الرحلات
$sql = "SELECT t.*, fc.name as from_city, tc.name as to_city, bt.name as bus_type,
               COUNT(b.id) as total_bookings,
               SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END) as total_revenue
        FROM trips t
        JOIN cities fc ON t.from_city_id = fc.id
        JOIN cities tc ON t.to_city_id = tc.id
        JOIN bus_types bt ON t.bus_type_id = bt.id
        LEFT JOIN bookings b ON t.id = b.trip_id
        $where_clause
        GROUP BY t.id
        ORDER BY t.trip_date DESC, t.departure_time ASC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// الحصول على المدن وأنواع الباصات للنماذج
$cities = $db->query("SELECT * FROM cities ORDER BY name")->fetchAll();
$bus_types = $db->query("SELECT * FROM bus_types ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرحلات - لوحة تحكم المسؤول</title><link rel="stylesheet" href="../css/enhanced-style.css">
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
                    <a href="bookings.php" class="nav-link">
                        <i class="fas fa-ticket-alt"></i> إدارة الحجوزات
                    </a>
                    <a href="trips.php" class="nav-link active">
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
                        <h2><i class="fas fa-bus text-primary"></i> إدارة الرحلات</h2>
                        <div>
                            <button class="btn btn-primary" onclick="addTrip()">
                                <i class="fas fa-plus"></i> إضافة رحلة جديدة
                            </button>
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
                                       placeholder="البحث بالمدينة..."
                                       value="<?= sanitize($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">جميع الحالات</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>نشطة</option>
                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>ملغية</option>
                                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>مكتملة</option>
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
                                <a href="trips.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> إلغاء الفلترة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- قائمة الرحلات -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($trips)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                                <h4>لا توجد رحلات</h4>
                                <p class="text-muted">لم يتم العثور على أي رحلات.</p>
                                <button class="btn btn-primary" onclick="addTrip()">
                                    <i class="fas fa-plus"></i> إضافة رحلة جديدة
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الرحلة</th>
                                            <th>التاريخ والوقت</th>
                                            <th>نوع الباص</th>
                                            <th>المقاعد</th>
                                            <th>السعر</th>
                                            <th>الحجوزات</th>
                                            <th>الإيرادات</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trips as $trip): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= sanitize($trip['from_city']) ?></strong>
                                                    <i class="fas fa-arrow-left text-primary mx-2"></i>
                                                    <strong><?= sanitize($trip['to_city']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y', strtotime($trip['trip_date'])) ?><br>
                                                    <small class="text-muted">
                                                        <?= formatTime($trip['departure_time']) ?> - <?= formatTime($trip['arrival_time']) ?>
                                                    </small>
                                                </td>
                                                <td><?= sanitize($trip['bus_type']) ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?= $trip['available_seats'] ?></span> / <?= $trip['total_seats'] ?>
                                                </td>
                                                <td>
                                                    <strong class="price-highlight"><?= formatPrice($trip['price']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?= $trip['total_bookings'] ?></span>
                                                </td>
                                                <td><?= formatPrice($trip['total_revenue'] ?: 0) ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($trip['status']) {
                                                        case 'active':
                                                            $status_class = 'success';
                                                            $status_text = 'نشطة';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'danger';
                                                            $status_text = 'ملغية';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'secondary';
                                                            $status_text = 'مكتملة';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                onclick="editTrip(<?= htmlspecialchars(json_encode($trip)) ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteTrip(<?= $trip['id'] ?>, '<?= sanitize($trip['from_city']) ?> - <?= sanitize($trip['to_city']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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

    <!-- نموذج إضافة/تعديل الرحلة -->
    <div class="modal fade" id="tripModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tripModalTitle">إضافة رحلة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="tripForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="trip_action" value="add_trip">
                        <input type="hidden" name="trip_id" id="trip_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="from_city_id" class="form-label">من</label>
                                <select name="from_city_id" id="from_city_id" class="form-control" required>
                                    <option value="">اختر المدينة</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city['id'] ?>"><?= sanitize($city['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="to_city_id" class="form-label">إلى</label>
                                <select name="to_city_id" id="to_city_id" class="form-control" required>
                                    <option value="">اختر المدينة</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city['id'] ?>"><?= sanitize($city['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="departure_time" class="form-label">وقت المغادرة</label>
                                <input type="time" name="departure_time" id="departure_time" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="arrival_time" class="form-label">وقت الوصول</label>
                                <input type="time" name="arrival_time" id="arrival_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bus_type_id" class="form-label">نوع الباص</label>
                                <select name="bus_type_id" id="bus_type_id" class="form-control" required>
                                    <option value="">اختر نوع الباص</option>
                                    <?php foreach ($bus_types as $bus_type): ?>
                                        <option value="<?= $bus_type['id'] ?>"><?= sanitize($bus_type['name']) ?> (<?= $bus_type['total_seats'] ?> مقعد)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">السعر (جنيه)</label>
                                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="trip_date" class="form-label">تاريخ الرحلة</label>
                                <input type="date" name="trip_date" id="trip_date" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="status_field" style="display: none;">
                                <label for="status" class="form-label">الحالة</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active">نشطة</option>
                                    <option value="cancelled">ملغية</option>
                                    <option value="completed">مكتملة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="trip_submit_btn">إضافة الرحلة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج حذف الرحلة -->
    <div class="modal fade" id="deleteTripModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_trip">
                        <input type="hidden" name="trip_id" id="delete_trip_id">
                        
                        <p>هل أنت متأكد من حذف الرحلة <strong id="delete_trip_name"></strong>؟</p>
                        <p class="text-danger">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">حذف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addTrip() {
            document.getElementById('tripModalTitle').textContent = 'إضافة رحلة جديدة';
            document.getElementById('trip_action').value = 'add_trip';
            document.getElementById('trip_submit_btn').textContent = 'إضافة الرحلة';
            document.getElementById('status_field').style.display = 'none';
            document.getElementById('tripForm').reset();
            
            new bootstrap.Modal(document.getElementById('tripModal')).show();
        }
        
        function editTrip(trip) {
            document.getElementById('tripModalTitle').textContent = 'تعديل الرحلة';
            document.getElementById('trip_action').value = 'edit_trip';
            document.getElementById('trip_submit_btn').textContent = 'حفظ التغييرات';
            document.getElementById('status_field').style.display = 'block';
            
            document.getElementById('trip_id').value = trip.id;
            document.getElementById('from_city_id').value = trip.from_city_id;
            document.getElementById('to_city_id').value = trip.to_city_id;
            document.getElementById('departure_time').value = trip.departure_time;
            document.getElementById('arrival_time').value = trip.arrival_time;
            document.getElementById('price').value = trip.price;
            document.getElementById('status').value = trip.status;
            
            new bootstrap.Modal(document.getElementById('tripModal')).show();
        }
        
        function deleteTrip(tripId, tripName) {
            document.getElementById('delete_trip_id').value = tripId;
            document.getElementById('delete_trip_name').textContent = tripName;
            
            new bootstrap.Modal(document.getElementById('deleteTripModal')).show();
        }
        
        // تعيين الحد الأدنى لتاريخ الرحلة إلى اليوم
        document.getElementById('trip_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>




