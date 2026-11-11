-- قاعدة بيانات موقع حجز الباصات السودانية
CREATE DATABASE IF NOT EXISTS sudan_bus_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sudan_bus_booking;

-- جدول المدن
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول المستخدمين
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول أنواع الباصات
CREATE TABLE bus_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    total_seats INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الرحلات
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_city_id INT NOT NULL,
    to_city_id INT NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    bus_type_id INT NOT NULL,
    total_seats INT NOT NULL,
    available_seats INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    trip_date DATE NOT NULL,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_city_id) REFERENCES cities(id),
    FOREIGN KEY (to_city_id) REFERENCES cities(id),
    FOREIGN KEY (bus_type_id) REFERENCES bus_types(id)
);

-- جدول الحجوزات
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    seats_booked INT NOT NULL,
    seat_numbers TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'cash_on_delivery') DEFAULT 'cash_on_delivery',
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    passenger_names TEXT,
    passenger_phones TEXT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id)
);

-- جدول المدفوعات
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- إدراج بيانات تجريبية للمدن
INSERT INTO cities (name, state) VALUES
('الخرطوم', 'الخرطوم'),
('أم درمان', 'الخرطوم'),
('بحري', 'الخرطوم'),
('بورتسودان', 'البحر الأحمر'),
('كسلا', 'كسلا'),
('القضارف', 'القضارف'),
('الأبيض', 'شمال كردفان'),
('نيالا', 'جنوب دارفور'),
('الفاشر', 'شمال دارفور'),
('الجنينة', 'غرب دارفور'),
('عطبرة', 'نهر النيل'),
('دنقلا', 'الشمالية'),
('ود مدني', 'الجزيرة'),
('سنار', 'سنار'),
('الدمازين', 'النيل الأزرق');

-- إدراج أنواع الباصات
INSERT INTO bus_types (name, total_seats, description) VALUES
('باص عادي', 45, 'باص عادي بمقاعد مريحة'),
('باص مكيف', 40, 'باص مكيف بمقاعد فاخرة'),
('باص VIP', 30, 'باص فاخر مع مقاعد واسعة وخدمات إضافية'),
('ميني باص', 15, 'ميني باص للرحلات القصيرة');

-- إدراج رحلات تجريبية
INSERT INTO trips (from_city_id, to_city_id, departure_time, arrival_time, bus_type_id, total_seats, available_seats, price, trip_date) VALUES
(1, 4, '08:00:00', '16:00:00', 2, 40, 35, 150.00, '2025-01-15'),
(1, 5, '09:00:00', '14:00:00', 1, 45, 40, 120.00, '2025-01-15'),
(1, 6, '10:00:00', '15:00:00', 2, 40, 38, 130.00, '2025-01-15'),
(4, 1, '07:00:00', '15:00:00', 2, 40, 32, 150.00, '2025-01-15'),
(1, 8, '06:00:00', '18:00:00', 3, 30, 25, 250.00, '2025-01-15'),
(2, 7, '08:30:00', '14:30:00', 1, 45, 42, 140.00, '2025-01-15');


-- جدول المسؤولين (الآدمن)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator', 'viewer') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- إدراج مسؤول افتراضي
INSERT INTO admins (username, name, email, password, role) VALUES 
('admin', 'مدير النظام', 'admin@sudanbuses.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- كلمة المرور الافتراضية: password



-- جدول الإعدادات
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


