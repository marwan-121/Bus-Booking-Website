// ملف JavaScript الرئيسي لموقع حجز الباصات السودانية

document.addEventListener('DOMContentLoaded', function() {
    // تأثير الظهور التدريجي للعناصر
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
        element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(element);
    });
    
    // التحقق من نموذج البحث
    const searchForm = document.querySelector('form[action="search.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const fromCity = document.getElementById('from_city').value;
            const toCity = document.getElementById('to_city').value;
            
            if (fromCity === toCity) {
                e.preventDefault();
                alert('لا يمكن أن تكون مدينة المغادرة والوجهة نفس المدينة');
                return false;
            }
        });
    }
    
    // تحديث التاريخ الأدنى لتاريخ السفر
    const travelDateInput = document.getElementById('travel_date');
    if (travelDateInput) {
        const today = new Date().toISOString().split('T')[0];
        travelDateInput.min = today;
    }
    
    // تأثيرات الأزرار
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // إغلاق الرسائل تلقائياً
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 3000);
        }
    });
});

// دالة اختيار المقاعد
function selectSeat(seatNumber, element) {
    if (element.classList.contains('occupied')) {
        return false;
    }
    
    const selectedSeats = document.querySelectorAll('.seat.selected');
    const maxSeats = parseInt(document.getElementById('passengers_count').value) || 1;
    
    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        updateSelectedSeats();
    } else if (selectedSeats.length < maxSeats) {
        element.classList.add('selected');
        updateSelectedSeats();
    } else {
        alert('لقد وصلت للحد الأقصى من المقاعد المسموح بها');
    }
}

// تحديث المقاعد المختارة
function updateSelectedSeats() {
    const selectedSeats = document.querySelectorAll('.seat.selected');
    const seatNumbers = Array.from(selectedSeats).map(seat => seat.textContent);
    
    const selectedSeatsInput = document.getElementById('selected_seats');
    if (selectedSeatsInput) {
        selectedSeatsInput.value = seatNumbers.join(',');
    }
    
    const selectedSeatsDisplay = document.getElementById('selected_seats_display');
    if (selectedSeatsDisplay) {
        if (seatNumbers.length > 0) {
            selectedSeatsDisplay.textContent = 'المقاعد المختارة: ' + seatNumbers.join(', ');
        } else {
            selectedSeatsDisplay.textContent = 'لم يتم اختيار مقاعد بعد';
        }
    }
    
    // تحديث السعر الإجمالي
    updateTotalPrice();
}

// تحديث السعر الإجمالي
function updateTotalPrice() {
    const selectedSeats = document.querySelectorAll('.seat.selected');
    const pricePerSeat = parseFloat(document.getElementById('price_per_seat').value) || 0;
    const totalPrice = selectedSeats.length * pricePerSeat;
    
    const totalPriceDisplay = document.getElementById('total_price_display');
    if (totalPriceDisplay) {
        totalPriceDisplay.textContent = totalPrice.toFixed(2) + ' جنيه';
    }
    
    const totalPriceInput = document.getElementById('total_price');
    if (totalPriceInput) {
        totalPriceInput.value = totalPrice.toFixed(2);
    }
}

// دالة تأكيد الحجز
function confirmBooking() {
    const selectedSeats = document.querySelectorAll('.seat.selected');
    
    if (selectedSeats.length === 0) {
        alert('يرجى اختيار مقعد واحد على الأقل');
        return false;
    }
    
    const confirmation = confirm('هل أنت متأكد من تأكيد الحجز؟');
    return confirmation;
}

// دالة تبديل طريقة الدفع
function togglePaymentMethod() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const cardDetails = document.getElementById('card_details');
    
    if (cardDetails) {
        if (paymentMethod === 'credit_card') {
            cardDetails.style.display = 'block';
            cardDetails.querySelectorAll('input').forEach(input => {
                input.required = true;
            });
        } else {
            cardDetails.style.display = 'none';
            cardDetails.querySelectorAll('input').forEach(input => {
                input.required = false;
            });
        }
    }
}

// دالة البحث المباشر
function liveSearch(input, resultsContainer) {
    const query = input.value.trim();
    
    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }
    
    // هنا يمكن إضافة AJAX للبحث المباشر
    // fetch('search_ajax.php?q=' + encodeURIComponent(query))
    //     .then(response => response.json())
    //     .then(data => {
    //         // عرض النتائج
    //     });
}

// دالة تنسيق أرقام الهاتف
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('249')) {
        value = '+' + value;
    } else if (value.startsWith('0')) {
        value = '+249' + value.substring(1);
    } else if (!value.startsWith('+')) {
        value = '+249' + value;
    }
    
    input.value = value;
}

// دالة التحقق من صحة البريد الإلكتروني
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// دالة عرض رسالة تحميل
function showLoading(element) {
    const originalText = element.textContent;
    element.textContent = 'جاري التحميل...';
    element.disabled = true;
    
    return function() {
        element.textContent = originalText;
        element.disabled = false;
    };
}

// دالة تحديث الوقت المتبقي للرحلة
function updateTimeRemaining() {
    const timeElements = document.querySelectorAll('[data-departure-time]');
    
    timeElements.forEach(element => {
        const departureTime = new Date(element.dataset.departureTime);
        const now = new Date();
        const timeDiff = departureTime - now;
        
        if (timeDiff > 0) {
            const hours = Math.floor(timeDiff / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            
            element.textContent = `${hours} ساعة و ${minutes} دقيقة`;
        } else {
            element.textContent = 'انتهت الرحلة';
            element.classList.add('text-danger');
        }
    });
}

// تحديث الوقت كل دقيقة
setInterval(updateTimeRemaining, 60000);

// تشغيل تحديث الوقت عند تحميل الصفحة
updateTimeRemaining();

