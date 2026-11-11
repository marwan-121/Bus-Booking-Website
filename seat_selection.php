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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختيار المقاعد - BusGo</title>
    <link rel="stylesheet" href="css/enhanced-style.css">
    <style>
        .seat-selection-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .trip-info-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .route-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .route-arrow {
            color: #3498db;
            font-size: 30px;
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 2px solid #dee2e6;
        }

        .detail-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .bus-layout {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .bus-container {
            max-width: 400px;
            margin: 0 auto;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 25px;
            padding: 30px 20px;
            border: 3px solid #dee2e6;
            position: relative;
        }

        .bus-front {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 16px;
        }

        .driver-area {
            background: #34495e;
            color: white;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .seats-grid {
            display: grid;
            grid-template-columns: 1fr 40px 1fr;
            gap: 10px;
            align-items: center;
        }

        .seat-column {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .aisle {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }

        .seat {
            width: 45px;
            height: 45px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .seat.booked {
            background: linear-gradient(135deg, #dc3545, #c82333);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .seat.selected {
            background: linear-gradient(135deg, #007bff, #0056b3);
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .seat:hover:not(.booked) {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .legend-seat {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .selection-summary {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .summary-header {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .selected-seats-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .selected-seat-badge {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .total-amount {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }

        .continue-btn {
            width: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .continue-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .continue-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .seat-selection-container {
                padding: 10px;
            }
            
            .trip-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .route-info {
                font-size: 20px;
            }
            
            .trip-details {
                grid-template-columns: 1fr;
            }
            
            .bus-container {
                padding: 20px 15px;
            }
            
            .seat {
                width: 40px;
                height: 40px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="seat-selection-container">
        <!-- معلومات الرحلة -->
        <div class="trip-info-card">
            <div class="trip-header">
                <div class="route-info">
                    <span><?= htmlspecialchars($trip['from_city_name']) ?></span>
                    <span class="route-arrow">←</span>
                    <span><?= htmlspecialchars($trip['to_city_name']) ?></span>
                </div>
                <div style="color: #e74c3c; font-weight: bold;">
                    <?= formatPrice($trip['price']) ?> للمقعد الواحد
                </div>
            </div>
            
            <div class="trip-details">
                <div class="detail-item">
                    <div class="detail-label">تاريخ السفر</div>
                    <div class="detail-value"><?= formatDate($trip["trip_date"]) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">وقت المغادرة</div>
                    <div class="detail-value"><?= formatTime($trip['departure_time']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">وقت الوصول</div>
                    <div class="detail-value"><?= formatTime($trip['arrival_time']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">نوع الباص</div>
                    <div class="detail-value"><?= htmlspecialchars($trip['bus_type_name']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">عدد المسافرين</div>
                    <div class="detail-value"><?= $passengers_count ?> مسافر</div>
                </div>
            </div>
        </div>

        <!-- تخطيط الباص -->
        <div class="bus-layout">
            <h3 style="text-align: center; margin-bottom: 20px; color: #2c3e50;">اختر مقاعدك</h3>
            
            <div class="bus-container">
                <div class="bus-front">مقدمة الباص</div>
                <div class="driver-area">🚗 منطقة السائق</div>
                
                <div class="seats-grid">
                    <div class="seat-column" id="left-seats">
                        <!-- المقاعد اليسرى -->
                    </div>
                    <div class="aisle">ممر</div>
                    <div class="seat-column" id="right-seats">
                        <!-- المقاعد اليمنى -->
                    </div>
                </div>
            </div>
            
            <div class="seat-legend">
                <div class="legend-item">
                    <div class="legend-seat" style="background: linear-gradient(135deg, #28a745, #20c997);"></div>
                    <span>متاح</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat" style="background: linear-gradient(135deg, #007bff, #0056b3);"></div>
                    <span>مختار</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat" style="background: linear-gradient(135deg, #dc3545, #c82333);"></div>
                    <span>محجوز</span>
                </div>
            </div>
        </div>

        <!-- ملخص الاختيار -->
        <div class="selection-summary">
            <div class="summary-header">ملخص اختيارك</div>
            
            <div id="selected-seats-display">
                <p style="text-align: center; color: #6c757d;">لم يتم اختيار أي مقاعد بعد</p>
            </div>
            
            <div class="total-amount" id="total-amount">
                المجموع: 0.00 جنيه
            </div>
            
            <button class="continue-btn" id="continue-btn" disabled onclick="proceedToPayment()">
                متابعة إلى الدفع
            </button>
        </div>
    </div>

    <script>
        const tripPrice = <?= $trip['price'] ?>;
        const totalSeats = <?= $trip['total_seats'] ?>;
        const passengersCount = <?= $passengers_count ?>;
        const bookedSeats = <?= json_encode($booked_seats) ?>;
        let selectedSeats = [];

        // إنشاء المقاعد
        function createSeats() {
            const leftColumn = document.getElementById('left-seats');
            const rightColumn = document.getElementById('right-seats');
            
            const seatsPerSide = Math.ceil(totalSeats / 2);
            
            for (let i = 1; i <= seatsPerSide; i++) {
                // المقعد الأيسر
                if (i <= totalSeats) {
                    const leftSeat = createSeat(i);
                    leftColumn.appendChild(leftSeat);
                }
                
                // المقعد الأيمن
                const rightSeatNumber = i + seatsPerSide;
                if (rightSeatNumber <= totalSeats) {
                    const rightSeat = createSeat(rightSeatNumber);
                    rightColumn.appendChild(rightSeat);
                }
            }
        }

        function createSeat(seatNumber) {
            const seat = document.createElement('div');
            seat.className = 'seat';
            seat.textContent = seatNumber;
            seat.dataset.seatNumber = seatNumber;
            
            if (bookedSeats.includes(seatNumber.toString())) {
                seat.classList.add('booked');
                seat.title = 'مقعد محجوز';
            } else {
                seat.addEventListener('click', () => toggleSeat(seatNumber, seat));
                seat.title = 'انقر لاختيار المقعد';
            }
            
            return seat;
        }

        function toggleSeat(seatNumber, seatElement) {
            if (seatElement.classList.contains('booked')) return;
            
            if (selectedSeats.includes(seatNumber)) {
                // إلغاء اختيار المقعد
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
                seatElement.classList.remove('selected');
            } else {
                // اختيار المقعد
                if (selectedSeats.length < passengersCount) {
                    selectedSeats.push(seatNumber);
                    seatElement.classList.add('selected');
                } else {
                    alert(`يمكنك اختيار ${passengersCount} مقاعد فقط`);
                }
            }
            
            updateSummary();
        }

        function updateSummary() {
            const displayDiv = document.getElementById('selected-seats-display');
            const totalDiv = document.getElementById('total-amount');
            const continueBtn = document.getElementById('continue-btn');
            
            if (selectedSeats.length === 0) {
                displayDiv.innerHTML = '<p style="text-align: center; color: #6c757d;">لم يتم اختيار أي مقاعد بعد</p>';
                totalDiv.textContent = 'المجموع: 0.00 جنيه';
                continueBtn.disabled = true;
            } else {
                const seatsHtml = selectedSeats.map(seat => 
                    `<span class="selected-seat-badge">مقعد ${seat}</span>`
                ).join('');
                
                displayDiv.innerHTML = `
                    <div class="selected-seats-list">
                        ${seatsHtml}
                    </div>
                `;
                
                const total = selectedSeats.length * tripPrice;
                totalDiv.textContent = `المجموع: ${total.toFixed(2)} جنيه`;
                
                continueBtn.disabled = selectedSeats.length !== passengersCount;
            }
        }

        function proceedToPayment() {
            if (selectedSeats.length !== passengersCount) {
                alert(`يرجى اختيار ${passengers_count} مقاعد`);
                return;
            }
            
            // الانتقال إلى صفحة إكمال الدفع
            const params = new URLSearchParams({
                trip_id: <?= $trip_id ?>,
                selected_seats: selectedSeats.join(','),
                passengers: passengersCount
            });
            
            window.location.href = `payment.php?${params.toString()}`;
        }

        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            createSeats();
            updateSummary();
        });
    </script>
</body>
</html>


