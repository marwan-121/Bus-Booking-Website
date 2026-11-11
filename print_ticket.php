<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$booking_reference = getGet('booking_ref');

if (!$booking_reference) {
    redirect('bookings.php');
}

// الحصول على تفاصيل الحجز
$stmt = $db->prepare("
    SELECT b.*, 
           u.name as user_name, u.email as user_email, u.phone as user_phone,
           t.trip_date, t.departure_time, t.arrival_time, t.price,
           fc.name as from_city_name, 
           tc.name as to_city_name,
           bt.name as bus_type_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    JOIN bus_types bt ON t.bus_type_id = bt.id
    WHERE b.booking_reference = ? AND b.user_id = ?
");

$stmt->execute([$booking_reference, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    echo "<p style='text-align: center; margin-top: 50px; font-size: 20px;'>خطأ: لم يتم العثور على الحجز أو ليس لديك صلاحية لعرضه.</p>";
    exit();
}

// تحويل أرقام المقاعد وأسماء المسافرين إلى مصفوفات
$seat_numbers = explode(',', $booking['seat_numbers']);
$passenger_names = explode(',', $booking['passenger_names']);
$passenger_phones = explode(',', $booking['passenger_phones']);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذكرة الحجز - <?= sanitize($booking['booking_reference']) ?></title>
    <link rel="stylesheet" href="css/enhanced-style.css">
    <style>
        body {
            background-color: #fff;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
        }
        .ticket-container {
            width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 20px;
        }
        .ticket-header h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .ticket-header p {
            font-size: 1.1rem;
            color: #555;
        }
        .ticket-details,
        .passenger-details {
            margin-bottom: 20px;
        }
        .ticket-details h3,
        .passenger-details h3 {
            font-size: 1.5rem;
            color: var(--dark-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .detail-item {
            flex: 1;
            padding: 0 10px;
        }
        .detail-item strong {
            display: block;
            font-size: 1rem;
            color: #333;
            margin-bottom: 5px;
        }
        .detail-item span {
            font-size: 1.1rem;
            color: #000;
        }
        .barcode {
            text-align: center;
            margin-top: 40px;
        }
        .barcode img {
            max-width: 200px;
            height: auto;
        }
        .print-button-container {
            text-align: center;
            margin-top: 30px;
        }
        @media print {
            .print-button-container {
                display: none;
            }
            body {
                margin: 0;
            }
            .ticket-container {
                border: none;
                box-shadow: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>تذكرة حجز الباص</h1>
            <p>رقم الحجز: <strong><?= sanitize($booking['booking_reference']) ?></strong></p>
            <p>تاريخ الحجز: <?= date('d/m/Y H:i', strtotime($booking['booking_date'])) ?></p>
        </div>

        <div class="ticket-details">
            <h3>تفاصيل الرحلة</h3>
            <div class="detail-row">
                <div class="detail-item">
                    <strong>من:</strong>
                    <span><?= sanitize($booking['from_city_name']) ?></span>
                </div>
                <div class="detail-item">
                    <strong>إلى:</strong>
                    <span><?= sanitize($booking['to_city_name']) ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <strong>تاريخ السفر:</strong>
                    <span><?= date('d/m/Y', strtotime($booking['trip_date'])) ?></span>
                </div>
                <div class="detail-item">
                    <strong>وقت المغادرة:</strong>
                    <span><?= formatTime($booking['departure_time']) ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <strong>نوع الباص:</strong>
                    <span><?= sanitize($booking['bus_type_name']) ?></span>
                </div>
                <div class="detail-item">
                    <strong>عدد المقاعد:</strong>
                    <span><?= $booking['seats_booked'] ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <strong>أرقام المقاعد:</strong>
                    <span><?= sanitize($booking['seat_numbers']) ?></span>
                </div>
                <div class="detail-item">
                    <strong>السعر الإجمالي:</strong>
                    <span><?= formatPrice($booking['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <div class="passenger-details">
            <h3>تفاصيل المسافرين</h3>
            <?php for ($i = 0; $i < count($passenger_names); $i++): ?>
                <div class="detail-row">
                    <div class="detail-item">
                        <strong>المسافر <?= $i + 1 ?>:</strong>
                        <span><?= sanitize($passenger_names[$i]) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>رقم الهاتف:</strong>
                        <span><?= sanitize($passenger_phones[$i]) ?></span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="barcode">
            <!-- يمكنك استبدال هذا بصورة باركود حقيقية -->
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= urlencode($booking['booking_reference']) ?>&code=Code128&dpi=96" alt="Barcode">
            <p>يرجى إظهار هذا الباركود عند الصعود للباص</p>
        </div>
    </div>

    <div class="print-button-container">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> طباعة التذكرة
        </button>
        <a href="bookings.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> العودة للحجوزات
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

