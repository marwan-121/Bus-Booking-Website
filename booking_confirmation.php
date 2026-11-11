<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

$booking_reference = getGet('reference');

if (!$booking_reference) {
    redirect('bookings.php');
}

$db = getDB();

// الحصول على تفاصيل الحجز
$stmt = $db->prepare("
    SELECT b.*, 
           t.trip_date, t.departure_time, t.arrival_time, t.price,
           fc.name as from_city_name, 
           tc.name as to_city_name,
           bt.name as bus_type_name,
           u.name as user_name, u.email as user_email
    FROM bookings b
    JOIN trips t ON b.trip_id = t.id
    JOIN cities fc ON t.from_city_id = fc.id
    JOIN cities tc ON t.to_city_id = tc.id
    JOIN bus_types bt ON t.bus_type_id = bt.id
    JOIN users u ON b.user_id = u.id
    WHERE b.booking_reference = ? AND b.user_id = ?
");

$stmt->execute([$booking_reference, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('bookings.php');
}

$passenger_names = explode(',', $booking['passenger_names']);
$passenger_phones = explode(',', $booking['passenger_phones']);
$emails = explode(',', $booking['passenger_emails']);
$seat_numbers = explode(',', $booking['seat_numbers']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الحجز - BusGo</title>
    <link rel="stylesheet" href="css/enhanced-style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .confirmation-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .confirmation-title {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }

        .confirmation-subtitle {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }

        .booking-reference {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            display: inline-block;
        }

        .ticket-details {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #dee2e6;
            text-align: right;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }

        .route-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .route-arrow {
            color: #007bff;
            font-size: 30px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
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

        .passengers-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .passengers-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .passenger-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .passenger-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .passenger-details {
            font-size: 14px;
            color: #6c757d;
        }

        .payment-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }

        .payment-status.paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .payment-status.pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .important-note {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: right;
        }

        .note-title {
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 10px;
        }

        .note-text {
            color: #424242;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                padding: 10px;
            }
            
            .confirmation-card {
                padding: 20px;
            }
            
            .route-display {
                flex-direction: column;
                gap: 10px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="success-icon">✅</div>
            
            <div class="confirmation-title">تم تأكيد حجزك بنجاح!</div>
            <div class="confirmation-subtitle">شكراً لك لاختيار خدماتنا</div>
            
            <div class="booking-reference">
                رقم الحجز: <?= htmlspecialchars($booking['booking_reference']) ?>
            </div>
            
            <div class="ticket-details">
                <div class="ticket-header">
                    <div class="route-display">
                        <span><?= htmlspecialchars($booking['from_city_name']) ?></span>
                        <span class="route-arrow">←</span>
                        <span><?= htmlspecialchars($booking['to_city_name']) ?></span>
                    </div>
                    
                    <div class="payment-status <?= $booking['payment_status'] ?>">
                        <?php if ($booking['payment_status'] === 'paid'): ?>
                            💳 تم الدفع
                        <?php else: ?>
                            💵 الدفع عند الصعود
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="details-grid">
                    <div class="detail-box">
                        <div class="detail-label">تاريخ السفر</div>
                       <div class="detail-value"><?= formatDate($booking['trip_date']) ?></div>

                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-label">وقت المغادرة</div>
                        <div class="detail-value"><?= formatTime($booking['departure_time']) ?></div>
                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-label">وقت الوصول</div>
                        <div class="detail-value"><?= formatTime($booking['arrival_time']) ?></div>
                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-label">نوع الباص</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['bus_type_name']) ?></div>
                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-label">المقاعد</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['seat_numbers']) ?></div>
                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-label">المبلغ الإجمالي</div>
                        <div class="detail-value"><?= formatPrice($booking['total_amount']) ?></div>
                    </div>
                </div>
                
                <div class="passengers-section">
                    <div class="section-title">بيانات المسافرين</div>
                    
                    <div class="passengers-list">
                        <?php for ($i = 0; $i < count($passenger_names); $i++): ?>
                            <div class="passenger-card">
                                <div class="passenger-name">
                                    👤 <?= htmlspecialchars($passenger_names[$i]) ?>
                                </div>
                                <div class="passenger-details">
                                    📱 <?= htmlspecialchars($passenger_phones[$i]) ?><br>
                                    🪑 مقعد رقم <?= htmlspecialchars($seat_numbers[$i]) ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="print_ticket.php?reference=<?= urlencode($booking['booking_reference']) ?>" 
                   class="btn btn-primary" target="_blank">
                    🖨️ طباعة التذكرة
                </a>
                
                <a href="bookings.php" class="btn btn-success">
                    📋 عرض جميع حجوزاتي
                </a>
                
                <a href="index.php" class="btn btn-secondary">
                    🏠 العودة للرئيسية
                </a>
            </div>
            
            <div class="important-note">
                <div class="note-title">📌 ملاحظات مهمة:</div>
                <div class="note-text">
                    • يرجى الوصول إلى محطة المغادرة قبل 30 دقيقة من موعد السفر<br>
                    • احتفظ برقم الحجز معك عند السفر<br>
                    • في حالة الدفع نقداً، يرجى إحضار المبلغ كاملاً<br>
                    • يمكنك إلغاء الحجز قبل 24 ساعة من موعد السفر<br>
                    • لأي استفسارات، تواصل معنا على الرقم: 0123456789
                </div>
            </div>
        </div>
    </div>

    <script>
        // إضافة تأثير الاحتفال
        function createConfetti() {
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.borderRadius = '50%';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '9999';
                    confetti.style.animation = 'fall 3s linear forwards';
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }, i * 100);
            }
        }

        // إضافة CSS للرسوم المتحركة
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);

        // تشغيل الاحتفال عند تحميل الصفحة
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>

