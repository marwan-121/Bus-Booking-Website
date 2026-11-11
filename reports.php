<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Get total bookings
$total_bookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Get total revenue
$total_revenue = $db->query("SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'paid'")->fetchColumn();

// Get total users
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get total trips
$total_trips = $db->query("SELECT COUNT(*) FROM trips")->fetchColumn();

// Get recent bookings
$recent_bookings = $db->query("SELECT b.*, u.name as username, t.trip_date, fc.name as from_city, tc.name as to_city 
                                FROM bookings b
                                JOIN users u ON b.user_id = u.id
                                JOIN trips t ON b.trip_id = t.id
                                JOIN cities fc ON t.from_city_id = fc.id
                                JOIN cities tc ON t.to_city_id = tc.id
                                ORDER BY b.booking_date DESC LIMIT 10")->fetchAll();

// Get top destinations
$top_destinations = $db->query("SELECT c.name, COUNT(b.id) as booking_count 
                                FROM bookings b
                                JOIN trips t ON b.trip_id = t.id
                                JOIN cities c ON t.to_city_id = c.id
                                GROUP BY c.name ORDER BY booking_count DESC LIMIT 5")->fetchAll();

// Get revenue by month (last 6 months)
$revenue_by_month = $db->query("SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, SUM(total_amount) as revenue
                                FROM bookings WHERE payment_status = 'paid'
                                GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - لوحة تحكم المسؤول</title>
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
                    <a href="reports.php" class="nav-link active">
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
                        <h2><i class="fas fa-chart-bar text-primary"></i> التقارير</h2>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_bookings ?></h3>
                                        <p class="mb-0">إجمالي الحجوزات</p>
                                    </div>
                                    <i class="fas fa-ticket-alt fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= formatPrice($total_revenue ?: 0) ?></h3>
                                        <p class="mb-0">إجمالي الإيرادات</p>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_users ?></h3>
                                        <p class="mb-0">إجمالي المستخدمين</p>
                                    </div>
                                    <i class="fas fa-users fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_trips ?></h3>
                                        <p class="mb-0">إجمالي الرحلات</p>
                                    </div>
                                    <i class="fas fa-bus fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">آخر 10 حجوزات</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_bookings)): ?>
                                    <p class="text-muted text-center">لا توجد حجوزات حديثة.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>المستخدم</th>
                                                    <th>الرحلة</th>
                                                    <th>التاريخ</th>
                                                    <th>المبلغ</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_bookings as $booking): ?>
                                                    <tr>
                                                        <td><?= sanitize($booking["username"]) ?></td>
                                                        <td><?= sanitize($booking["from_city"]) ?> إلى <?= sanitize($booking["to_city"]) ?></td>
                                                        <td><?= date("d/m/Y", strtotime($booking["trip_date"])) ?></td>
                                                        <td><?= formatPrice($booking["total_amount"]) ?></td>
                                                        <td>
                                                            <?php
                                                            $status_class = "";
                                                            switch ($booking["payment_status"]) {
                                                                case "paid": $status_class = "success"; break;
                                                                case "pending": $status_class = "warning"; break;
                                                                case "cancelled": $status_class = "danger"; break;
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?>"><?= $booking["payment_status"] == "paid" ? "مدفوع" : ($booking["payment_status"] == "pending" ? "معلق" : "ملغي") ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">أكثر الوجهات شيوعًا</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_destinations)): ?>
                                    <p class="text-muted text-center">لا توجد بيانات للوجهات.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($top_destinations as $destination): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= sanitize($destination["name"]) ?>
                                                <span class="badge bg-primary rounded-pill"><?= $destination["booking_count"] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">الإيرادات الشهرية (آخر 6 أشهر)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($revenue_by_month)): ?>
                                    <p class="text-muted text-center">لا توجد بيانات للإيرادات الشهرية.</p>
                                <?php else: ?>
                                    <canvas id="revenueChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if (!empty($revenue_by_month)): ?>
            const ctx = document.getElementById("revenueChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: [<?= implode(",", array_map(function($r) { return "'" . $r["month"] . "'"; }, array_reverse($revenue_by_month))) ?>],
                    datasets: [{
                        label: "الإيرادات",
                        data: [<?= implode(",", array_map(function($r) { return $r["revenue"]; }, array_reverse($revenue_by_month))) ?>],
                        backgroundColor: "rgba(75, 192, 192, 0.6)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: "الإيرادات (جنيه)"
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: "الشهر"
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>


