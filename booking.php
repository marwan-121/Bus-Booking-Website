<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$trip_id = getGet('trip_id');
$passengers_count = getGet('passengers', 1);

if (!$trip_id) {
    redirect('search.php');
}

// الحصول على تفاصيل الرحلة
$stmt = $db->prepare("
    SELECT t.*, 
           fc.name as from_city_name, 
           tc.name as to_city_name,
           bt.name as bus_type_name,
           bt.description as bus_description,
           bt.total_seats
    FROM trips t
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    JOIN bus_types bt ON t.bus_type_id = bt.id
    WHERE t.id = ? AND t.status = 'active'
");

$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    redirect('search.php');
}

// الحصول على المقاعد المحجوزة
$stmt = $db->prepare("
    SELECT seat_numbers FROM bookings 
    WHERE trip_id = ? AND payment_status IN ('paid', 'pending')
");
$stmt->execute([$trip_id]);
$booked_seats_result = $stmt->fetchAll();

$booked_seats = [];
foreach ($booked_seats_result as $booking) {
    if ($booking['seat_numbers']) {
        $seats = explode(',', $booking['seat_numbers']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}

$error = '';
$success = '';

// معالجة الحجز
if (isPost()) {
    $selected_seats = getPost('selected_seats');
    $passenger_names = getPost('passenger_names');
    $passenger_phones = getPost('passenger_phones');
    $payment_method = getPost('payment_method');
    
    if (empty($selected_seats)) {
        $error = 'يرجى اختيار المقاعد';
    } elseif (empty($passenger_names) || empty($passenger_phones)) {
        $error = 'يرجى ملء بيانات جميع المسافرين';
    } else {
        $seats_array = explode(',', $selected_seats);
        $names_array = explode(',', $passenger_names);
        $phones_array = explode(',', $passenger_phones);
        
        if (count($seats_array) != $passengers_count || 
            count($names_array) != $passengers_count || 
            count($phones_array) != $passengers_count) {
            $error = 'عدد المقاعد أو بيانات المسافرين غير متطابق';
        } else {
            // التحقق من توفر المقاعد
            $conflicting_seats = array_intersect($seats_array, $booked_seats);
            if (!empty($conflicting_seats)) {
                $error = 'بعض المقاعد المختارة محجوزة بالفعل: ' . implode(', ', $conflicting_seats);
            } else {
                // إنشاء الحجز
                $booking_reference = generateBookingReference();
                $total_amount = count($seats_array) * $trip['price'];
                
                $stmt = $db->prepare("
                    INSERT INTO bookings 
                    (user_id, trip_id, seats_booked, seat_numbers, total_amount, 
                     payment_method, booking_reference, passenger_names, passenger_phones)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([
                    $_SESSION['user_id'],
                    $trip_id,
                    count($seats_array),
                    $selected_seats,
                    $total_amount,
                    $payment_method,
                    $booking_reference,
                    $passenger_names,
                    $passenger_phones
                ])) {
                    // تحديث المقاعد المتاحة
                    $stmt = $db->prepare("
                        UPDATE trips 
                        SET available_seats = available_seats - ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([count($seats_array), $trip_id]);
                    
                    $success = 'تم إنشاء الحجز بنجاح. رقم الحجز: ' . $booking_reference;
                    
                    if ($payment_method === 'credit_card') {
                        // هنا يمكن إضافة معالجة الدفع الإلكتروني
                        $success .= ' سيتم تحويلك لصفحة الدفع...';
                        header('refresh:3;url=payment.php?booking_ref=' . $booking_reference);
                    } else {
                        $success .= ' يمكنك الدفع عند الاستلام.';
                        header('refresh:3;url=bookings.php');
                    }
                } else {
                    $error = 'حدث خطأ أثناء إنشاء الحجز';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز الرحلة - BusGo</title>
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
                    <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> حجوزاتي</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-4">
        <!-- تفاصيل الرحلة -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-info-circle text-primary"></i> تفاصيل الرحلة</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4><?= sanitize($trip['from_city_name']) ?> 
                            <i class="fas fa-arrow-left text-primary"></i>
                            <?= sanitize($trip['to_city_name']) ?>
                        </h4>
                        <p><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($trip['trip_date'])) ?></p>
                        <p><i class="fas fa-clock"></i> المغادرة: <?= formatTime($trip['departure_time']) ?></p>
                        <p><i class="fas fa-clock"></i> الوصول: <?= formatTime($trip['arrival_time']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-bus"></i> <?= sanitize($trip['bus_type_name']) ?></p>
                        <p><i class="fas fa-chair"></i> المقاعد المتاحة: <?= $trip['available_seats'] ?></p>
                        <p><i class="fas fa-users"></i> عدد المسافرين: <?= $passengers_count ?></p>
                        <h4 class="text-primary">السعر: <?= formatPrice($trip['price']) ?> للمقعد</h4>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirmBooking()">
            <!-- اختيار المقاعد -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="fas fa-chair text-primary"></i> اختيار المقاعد</h4>
                    <p class="mb-0 text-muted">اختر <?= $passengers_count ?> مقعد/مقاعد</p>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="seat-map">
                            <?php for ($i = 1; $i <= $trip['total_seats']; $i++): ?>
                                <?php 
                                $seat_class = 'available';
                                if (in_array($i, $booked_seats)) {
                                    $seat_class = 'occupied';
                                }
                                ?>
                                <div class="seat <?= $seat_class ?>" 
                                     onclick="selectSeat(<?= $i ?>, this)"
                                     data-seat="<?= $i ?>">
                                    <?= $i ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="seat available d-inline-block"></div>
                            <span class="ms-2">متاح</span>
                        </div>
                        <div class="col-md-4">
                            <div class="seat occupied d-inline-block"></div>
                            <span class="ms-2">محجوز</span>
                        </div>
                        <div class="col-md-4">
                            <div class="seat selected d-inline-block"></div>
                            <span class="ms-2">مختار</span>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <p id="selected_seats_display" class="text-primary">لم يتم اختيار مقاعد بعد</p>
                        <h4 id="total_price_display" class="text-success">0.00 جنيه</h4>
                    </div>
                    
                    <input type="hidden" name="selected_seats" id="selected_seats">
                    <input type="hidden" id="passengers_count" value="<?= $passengers_count ?>">
                    <input type="hidden" id="price_per_seat" value="<?= $trip['price'] ?>">
                    <input type="hidden" name="total_price" id="total_price">
                </div>
            </div>

            <!-- بيانات المسافرين -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="fas fa-users text-primary"></i> بيانات المسافرين</h4>
                </div>
                <div class="card-body">
                    <div id="passengers_info">
                        <?php for ($i = 1; $i <= $passengers_count; $i++): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>اسم المسافر <?= $i ?></label>
                                    <input type="text" class="form-control passenger-name" 
                                           placeholder="الاسم الكامل" required>
                                </div>
                                <div class="col-md-6">
                                    <label>رقم الهاتف</label>
                                    <input type="tel" class="form-control passenger-phone" 
                                           placeholder="رقم الهاتف" required>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="passenger_names" id="passenger_names">
                    <input type="hidden" name="passenger_phones" id="passenger_phones">
                </div>
            </div>

            <!-- طريقة الدفع -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="fas fa-credit-card text-primary"></i> طريقة الدفع</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="cash_payment" value="cash_on_delivery" checked 
                                       onchange="togglePaymentMethod()">
                                <label class="form-check-label" for="cash_payment">
                                    <i class="fas fa-money-bill-wave text-success"></i>
                                    الدفع عند الاستلام
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="card_payment" value="credit_card" 
                                       onchange="togglePaymentMethod()">
                                <label class="form-check-label" for="card_payment">
                                    <i class="fas fa-credit-card text-primary"></i>
                                    الدفع بالبطاقة الائتمانية
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="card_details" style="display: none;" class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label>رقم البطاقة</label>
                                <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="col-md-3">
                                <label>تاريخ الانتهاء</label>
                                <input type="text" class="form-control" placeholder="MM/YY">
                            </div>
                            <div class="col-md-3">
                                <label>CVV</label>
                                <input type="text" class="form-control" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check"></i> تأكيد الحجز
                </button>
                <a href="search.php" class="btn btn-secondary btn-lg ms-3">
                    <i class="fas fa-arrow-right"></i> العودة للبحث
                </a>
            </div>
        </form>
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
        // تحديث بيانات المسافرين عند الإرسال
        document.querySelector('form').addEventListener('submit', function() {
            const names = Array.from(document.querySelectorAll('.passenger-name')).map(input => input.value);
            const phones = Array.from(document.querySelectorAll('.passenger-phone')).map(input => input.value);
            
            document.getElementById('passenger_names').value = names.join(',');
            document.getElementById('passenger_phones').value = phones.join(',');
        });
    </script>
</body>
</html>

