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
        case 'add_city':
            $name = getPost('name');
            $state = getPost('state');
            
            if (empty($name) || empty($state)) {
                $error = 'يرجى ملء جميع الحقول';
            } else {
                $stmt = $db->prepare("INSERT INTO cities (name, state) VALUES (?, ?)");
                if ($stmt->execute([$name, $state])) {
                    $message = 'تم إضافة المدينة بنجاح';
                } else {
                    $error = 'حدث خطأ أثناء إضافة المدينة';
                }
            }
            break;
            
        case 'edit_city':
            $city_id = getPost('city_id');
            $name = getPost('name');
            $state = getPost('state');
            
            if (empty($name) || empty($state)) {
                $error = 'يرجى ملء جميع الحقول';
            } else {
                $stmt = $db->prepare("UPDATE cities SET name = ?, state = ? WHERE id = ?");
                if ($stmt->execute([$name, $state, $city_id])) {
                    $message = 'تم تحديث المدينة بنجاح';
                } else {
                    $error = 'حدث خطأ أثناء تحديث المدينة';
                }
            }
            break;
            
        case 'delete_city':
            $city_id = getPost('city_id');
            
            // التحقق من وجود رحلات للمدينة
            $stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE from_city_id = ? OR to_city_id = ?");
            $stmt->execute([$city_id, $city_id]);
            $trip_count = $stmt->fetchColumn();
            
            if ($trip_count > 0) {
                $error = 'لا يمكن حذف المدينة لأن لديها رحلات موجودة';
            } else {
                $stmt = $db->prepare("DELETE FROM cities WHERE id = ?");
                if ($stmt->execute([$city_id])) {
                    $message = 'تم حذف المدينة بنجاح';
                } else {
                    $error = 'حدث خطأ أثناء حذف المدينة';
                }
            }
            break;
    }
}

// البحث والفلترة
$search = getGet('search', '');
$state_filter = getGet('state', '');
$page = max(1, getGet('page', 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR state LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term]);
}

if (!empty($state_filter)) {
    $where_conditions[] = "state = ?";
    $params[] = $state_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// الحصول على إجمالي عدد المدن
$count_sql = "SELECT COUNT(*) FROM cities $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_cities = $stmt->fetchColumn();
$total_pages = ceil($total_cities / $limit);

// الحصول على المدن
$sql = "SELECT c.*, 
               COUNT(DISTINCT t1.id) + COUNT(DISTINCT t2.id) as total_trips
        FROM cities c
        LEFT JOIN trips t1 ON c.id = t1.from_city_id
        LEFT JOIN trips t2 ON c.id = t2.to_city_id
        $where_clause
        GROUP BY c.id
        ORDER BY c.name ASC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$cities = $stmt->fetchAll();

// الحصول على قائمة الولايات
$states = $db->query("SELECT DISTINCT state FROM cities ORDER BY state")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المدن - لوحة تحكم المسؤول</title>
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
        .city-card {
            transition: all 0.3s ease;
        }
        .city-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
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
                    <a href="trips.php" class="nav-link">
                        <i class="fas fa-bus"></i> إدارة الرحلات
                    </a>
                    <a href="cities.php" class="nav-link active">
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
                        <h2><i class="fas fa-map-marker-alt text-primary"></i> إدارة المدن</h2>
                        <div>
                            <button class="btn btn-primary" onclick="addCity()">
                                <i class="fas fa-plus"></i> إضافة مدينة جديدة
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
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="البحث بالاسم أو الولاية..."
                                       value="<?= sanitize($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="state" class="form-control">
                                    <option value="">جميع الولايات</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= sanitize($state) ?>" <?= $state_filter === $state ? 'selected' : '' ?>>
                                            <?= sanitize($state) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </form>
                        <?php if (!empty($search) || !empty($state_filter)): ?>
                            <div class="mt-2">
                                <a href="cities.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> إلغاء الفلترة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- قائمة المدن -->
                <?php if (empty($cities)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h4>لا توجد مدن</h4>
                            <p class="text-muted">لم يتم العثور على أي مدن.</p>
                            <button class="btn btn-primary" onclick="addCity()">
                                <i class="fas fa-plus"></i> إضافة مدينة جديدة
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($cities as $city): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card city-card hover-lift">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">
                                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                                    <?= sanitize($city['name']) ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map text-secondary"></i>
                                                    <?= sanitize($city['state']) ?>
                                                </p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="editCity(<?= htmlspecialchars(json_encode($city)) ?>)">
                                                            <i class="fas fa-edit text-primary"></i> تعديل
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="deleteCity(<?= $city['id'] ?>, '<?= sanitize($city['name']) ?>')">
                                                            <i class="fas fa-trash text-danger"></i> حذف
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="border-end">
                                                    <h6 class="text-primary mb-0"><?= $city['total_trips'] ?></h6>
                                                    <small class="text-muted">رحلة</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h6 class="text-success mb-0"><?= date('d/m/Y', strtotime($city['created_at'])) ?></h6>
                                                <small class="text-muted">تاريخ الإضافة</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- الترقيم -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>">السابق</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>">التالي</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- نموذج إضافة/تعديل المدينة -->
    <div class="modal fade" id="cityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cityModalTitle">إضافة مدينة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="cityForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="city_action" value="add_city">
                        <input type="hidden" name="city_id" id="city_id">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم المدينة</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="state" class="form-label">الولاية</label>
                            <input type="text" name="state" id="state" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="city_submit_btn">إضافة المدينة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج حذف المدينة -->
    <div class="modal fade" id="deleteCityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_city">
                        <input type="hidden" name="city_id" id="delete_city_id">
                        
                        <p>هل أنت متأكد من حذف المدينة <strong id="delete_city_name"></strong>؟</p>
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
        function addCity() {
            document.getElementById('cityModalTitle').textContent = 'إضافة مدينة جديدة';
            document.getElementById('city_action').value = 'add_city';
            document.getElementById('city_submit_btn').textContent = 'إضافة المدينة';
            document.getElementById('cityForm').reset();
            
            new bootstrap.Modal(document.getElementById('cityModal')).show();
        }
        
        function editCity(city) {
            document.getElementById('cityModalTitle').textContent = 'تعديل المدينة';
            document.getElementById('city_action').value = 'edit_city';
            document.getElementById('city_submit_btn').textContent = 'حفظ التغييرات';
            
            document.getElementById('city_id').value = city.id;
            document.getElementById('name').value = city.name;
            document.getElementById('state').value = city.state;
            
            new bootstrap.Modal(document.getElementById('cityModal')).show();
        }
        
        function deleteCity(cityId, cityName) {
            document.getElementById('delete_city_id').value = cityId;
            document.getElementById('delete_city_name').textContent = cityName;
            
            new bootstrap.Modal(document.getElementById('deleteCityModal')).show();
        }
    </script>
</body>
</html>

