// ملف JavaScript محسن لموقع حجز الباصات السودانية

// متغيرات عامة
let selectedSeats = [];
let maxSeats = 1;
let pricePerSeat = 0;

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل الرسوم المتحركة
    initAnimations();
    
    // تهيئة اختيار المقاعد
    initSeatSelection();
    
    // تهيئة نموذج البحث
    initSearchForm();
    
    // تهيئة طرق الدفع
    initPaymentMethods();
    
    // تهيئة التحقق من النماذج
    initFormValidation();
});

// تفعيل الرسوم المتحركة
function initAnimations() {
    // تأثير الظهور التدريجي
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    fadeElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });
    
    // تأثير التحويم على البطاقات
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        });
    });
}

// تهيئة اختيار المقاعد
function initSeatSelection() {
    const passengersInput = document.getElementById('passengers_count');
    const priceInput = document.getElementById('price_per_seat');
    
    if (passengersInput) {
        maxSeats = parseInt(passengersInput.value) || 1;
    }
    
    if (priceInput) {
        pricePerSeat = parseFloat(priceInput.value) || 0;
    }
}

// اختيار المقعد
function selectSeat(seatNumber, element) {
    if (element.classList.contains('occupied')) {
        showNotification('هذا المقعد محجوز بالفعل', 'error');
        return;
    }
    
    if (element.classList.contains('selected')) {
        // إلغاء اختيار المقعد
        element.classList.remove('selected');
        selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
    } else {
        // اختيار المقعد
        if (selectedSeats.length >= maxSeats) {
            showNotification(`يمكنك اختيار ${maxSeats} مقعد فقط`, 'warning');
            return;
        }
        
        element.classList.add('selected');
        selectedSeats.push(seatNumber);
    }
    
    updateSeatDisplay();
}

// تحديث عرض المقاعد المختارة
function updateSeatDisplay() {
    const selectedSeatsDisplay = document.getElementById('selected_seats_display');
    const totalPriceDisplay = document.getElementById('total_price_display');
    const selectedSeatsInput = document.getElementById('selected_seats');
    const totalPriceInput = document.getElementById('total_price');
    
    if (selectedSeats.length === 0) {
        if (selectedSeatsDisplay) {
            selectedSeatsDisplay.textContent = 'لم يتم اختيار مقاعد بعد';
        }
        if (totalPriceDisplay) {
            totalPriceDisplay.textContent = '0.00 جنيه';
        }
    } else {
        const seatsText = selectedSeats.sort((a, b) => a - b).join(', ');
        const totalPrice = selectedSeats.length * pricePerSeat;
        
        if (selectedSeatsDisplay) {
            selectedSeatsDisplay.textContent = `المقاعد المختارة: ${seatsText}`;
        }
        if (totalPriceDisplay) {
            totalPriceDisplay.textContent = `${totalPrice.toFixed(2)} جنيه`;
        }
        if (selectedSeatsInput) {
            selectedSeatsInput.value = seatsText;
        }
        if (totalPriceInput) {
            totalPriceInput.value = totalPrice.toFixed(2);
        }
    }
}

// تهيئة نموذج البحث
function initSearchForm() {
    const fromCitySelect = document.getElementById('from_city');
    const toCitySelect = document.getElementById('to_city');
    
    if (fromCitySelect && toCitySelect) {
        fromCitySelect.addEventListener('change', function() {
            if (this.value === toCitySelect.value && this.value !== '') {
                showNotification('لا يمكن أن تكون مدينة المغادرة والوصول نفسها', 'warning');
                this.value = '';
            }
        });
        
        toCitySelect.addEventListener('change', function() {
            if (this.value === fromCitySelect.value && this.value !== '') {
                showNotification('لا يمكن أن تكون مدينة المغادرة والوصول نفسها', 'warning');
                this.value = '';
            }
        });
    }
}

// تهيئة طرق الدفع
function initPaymentMethods() {
    const cashPayment = document.getElementById('cash_payment');
    const cardPayment = document.getElementById('card_payment');
    
    if (cashPayment && cardPayment) {
        cashPayment.addEventListener('change', togglePaymentMethod);
        cardPayment.addEventListener('change', togglePaymentMethod);
    }
}

// تبديل طريقة الدفع
function togglePaymentMethod() {
    const cardDetails = document.getElementById('card_details');
    const cardPayment = document.getElementById('card_payment');
    
    if (cardDetails && cardPayment) {
        if (cardPayment.checked) {
            cardDetails.style.display = 'block';
            cardDetails.style.animation = 'slideDown 0.3s ease';
        } else {
            cardDetails.style.display = 'none';
        }
    }
}

// تهيئة التحقق من النماذج
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

// التحقق من صحة النموذج
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// تأكيد الحجز
function confirmBooking() {
    if (selectedSeats.length === 0) {
        showNotification('يرجى اختيار المقاعد أولاً', 'error');
        return false;
    }
    
    if (selectedSeats.length !== maxSeats) {
        showNotification(`يجب اختيار ${maxSeats} مقعد بالضبط`, 'error');
        return false;
    }
    
    const passengerNames = document.querySelectorAll('.passenger-name');
    const passengerPhones = document.querySelectorAll('.passenger-phone');
    
    for (let i = 0; i < passengerNames.length; i++) {
        if (!passengerNames[i].value.trim()) {
            showNotification(`يرجى إدخال اسم المسافر ${i + 1}`, 'error');
            passengerNames[i].focus();
            return false;
        }
        
        if (!passengerPhones[i].value.trim()) {
            showNotification(`يرجى إدخال رقم هاتف المسافر ${i + 1}`, 'error');
            passengerPhones[i].focus();
            return false;
        }
    }
    
    const totalPrice = selectedSeats.length * pricePerSeat;
    const confirmMessage = `
        تأكيد الحجز:
        المقاعد: ${selectedSeats.join(', ')}
        المبلغ الإجمالي: ${totalPrice.toFixed(2)} جنيه
        
        هل أنت متأكد من المتابعة؟
    `;
    
    return confirm(confirmMessage);
}

// عرض الإشعارات
function showNotification(message, type = 'info') {
    // إنشاء عنصر الإشعار
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'} notification`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // إزالة الإشعار تلقائياً بعد 5 ثوانٍ
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 5000);
}

// تحميل البيانات بـ AJAX
function loadData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('خطأ في تحميل البيانات:', error);
            showNotification('حدث خطأ في تحميل البيانات', 'error');
        });
}

// تنسيق الأرقام
function formatNumber(number) {
    return new Intl.NumberFormat('ar-SD').format(number);
}

// تنسيق العملة
function formatCurrency(amount) {
    return `${formatNumber(amount)} جنيه`;
}

// تنسيق التاريخ
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SD', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// تنسيق الوقت
function formatTime(timeString) {
    const time = new Date(`2000-01-01 ${timeString}`);
    return time.toLocaleTimeString('ar-SD', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

// إضافة CSS للرسوم المتحركة
const animationCSS = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes slideDown {
        from {
            max-height: 0;
            opacity: 0;
        }
        to {
            max-height: 200px;
            opacity: 1;
        }
    }
    
    .notification {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .btn {
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
    }
    
    .seat {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .seat:hover:not(.occupied) {
        transform: scale(1.1);
    }
    
    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        border-color: #007bff;
    }
    
    .is-invalid {
        border-color: #dc3545;
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;

// إضافة CSS للصفحة
const style = document.createElement('style');
style.textContent = animationCSS;
document.head.appendChild(style);

// تصدير الدوال للاستخدام العام
window.selectSeat = selectSeat;
window.confirmBooking = confirmBooking;
window.togglePaymentMethod = togglePaymentMethod;
window.showNotification = showNotification;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatTime = formatTime;

