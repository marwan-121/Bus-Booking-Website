<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$db = getDB();

// الحصول على قائمة المدن
$cities_stmt = $db->query("SELECT * FROM cities ORDER BY name");
$cities = $cities_stmt->fetchAll();

// متغيرات البحث
$from_city = getGet('from_city');
$to_city = getGet('to_city');
$travel_date = getGet('travel_date', date('Y-m-d'));
$passengers = getGet('passengers', 1);

$trips = [];
$search_performed = false;

// إذا تم إرسال نموذج البحث
if ($from_city && $to_city && $travel_date) {
    $search_performed = true;
    
    $stmt = $db->prepare("
        SELECT t.*, 
               fc.name as from_city_name, 
               tc.name as to_city_name,
               bt.name as bus_type_name,
               bt.description as bus_description
        FROM trips t
        JOIN cities fc ON t.from_city_id = fc.id
        JOIN cities tc ON t.to_city_id = tc.id
        JOIN bus_types bt ON t.bus_type_id = bt.id
        WHERE t.from_city_id = ? 
        AND t.to_city_id = ? 
        AND t.trip_date = ?
        AND t.status = 'active'
        AND t.available_seats >= ?
        ORDER BY t.departure_time
    ");
    
    $stmt->execute([$from_city, $to_city, $travel_date, $passengers]);
    $trips = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث عن الرحلات - BusGo</title>
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
                    <li><a href="search.php" class="active"><i class="fas fa-search"></i> البحث</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                        <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> حجوزاتي</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> تسجيل جديد</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-4">
        <!-- نموذج البحث -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-search text-primary"></i> البحث عن الرحلات</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="from_city">
                                <i class="fas fa-map-marker-alt text-primary"></i> من
                            </label>
                            <select name="from_city" id="from_city" class="form-control" required>
                                <option value="">اختر المدينة</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" <?= $from_city == $city['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="to_city">
                                <i class="fas fa-map-marker-alt text-secondary"></i> إلى
                            </label>
                            <select name="to_city" id="to_city" class="form-control" required>
                                <option value="">اختر المدينة</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" <?= $to_city == $city['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="travel_date">
                                <i class="fas fa-calendar text-warning"></i> تاريخ السفر
                            </label>
                            <input type="date" name="travel_date" id="travel_date" class="form-control" 
                                   min="<?= date('Y-m-d') ?>" value="<?= $travel_date ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="passengers">
                                <i class="fas fa-users text-info"></i> عدد المسافرين
                            </label>
                            <select name="passengers" id="passengers" class="form-control" required>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>" <?= $passengers == $i ? 'selected' : '' ?>>
                                        <?= $i ?> <?= $i == 1 ? 'مسافر' : 'مسافرين' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> البحث
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- نتائج البحث -->
        <?php if ($search_performed): ?>
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-list text-primary"></i> 
                        نتائج البحث (<?= count($trips) ?> رحلة)
                    </h4>
                    <?php if ($from_city && $to_city): ?>
                        <?php
                        $from_city_name = '';
                        $to_city_name = '';
                        foreach ($cities as $city) {
                            if ($city['id'] == $from_city) $from_city_name = $city['name'];
                            if ($city['id'] == $to_city) $to_city_name = $city['name'];
                        }
                        ?>
                        <p class="text-muted mb-0">
                            من <?= $from_city_name ?> إلى <?= $to_city_name ?> 
                            في <?= date('d/m/Y', strtotime($travel_date)) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($trips)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                            <h4>لا توجد رحلات متاحة</h4>
                            <p class="text-muted">لم نجد رحلات متاحة للمعايير المحددة. يرجى تجربة تواريخ أخرى.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($trips as $trip): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-8">
                                                    <h5 class="card-title">
                                                        <?= sanitize($trip['from_city_name']) ?> 
                                                        <i class="fas fa-arrow-left text-primary"></i>
                                                        <?= sanitize($trip['to_city_name']) ?>
                                                    </h5>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-bus"></i> <?= sanitize($trip['bus_type_name']) ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <i class="fas fa-clock text-primary"></i>
                                                        المغادرة: <?= formatTime($trip['departure_time']) ?>
                                                        <br>
                                                        <i class="fas fa-clock text-secondary"></i>
                                                        الوصول: <?= formatTime($trip['arrival_time']) ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <i class="fas fa-chair text-success"></i>
                                                        المقاعد المتاحة: <?= $trip['available_seats'] ?>
                                                    </p>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <h4 class="text-primary mb-3">
                                                        <?= formatPrice($trip['price']) ?>
                                                    </h4>
                                                    <?php if (isLoggedIn()): ?>
                                                        <a href="seat_selection.php?trip_id=<?= $trip["id"] ?>&passengers=<?= $passengers ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-ticket-alt"></i> احجز الآن
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="login.php" class="btn btn-outline btn-sm">
                                                            <i class="fas fa-sign-in-alt"></i> سجل للحجز
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
</body>
</html>

