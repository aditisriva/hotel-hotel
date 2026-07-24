<?php
/**
 * Hotel Manager Panel — Database & Auth
 * Uses the main users table with role-based access
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Ensure role column has hotel_manager option
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($col_check && mysqli_num_rows($col_check) > 0) {
    $row = mysqli_fetch_assoc($col_check);
    if (strpos($row['Type'], 'hotel_manager') === false) {
        mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','hotel_manager') DEFAULT 'user'");
    }
}

// Load main hotel tables if not already loaded by main db.php
function ensure_hotel_tables() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS `hotels` (
      `hotel_id`            INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `hotel_name`          VARCHAR(255) NOT NULL,
      `city_id`             INT(11) UNSIGNED DEFAULT NULL,
      `city`                VARCHAR(100) NOT NULL,
      `location`            VARCHAR(255) NOT NULL,
      `state`               VARCHAR(100) DEFAULT NULL,
      `description`         TEXT DEFAULT NULL,
      `price_per_night`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `original_price`      DECIMAL(10,2) DEFAULT NULL,
      `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
      `gst_percentage`      DECIMAL(5,2) DEFAULT 12.00,
      `rating`              DECIMAL(3,1) DEFAULT 0.0,
      `star_rating`         TINYINT(1) DEFAULT 3,
      `property_type`       VARCHAR(50) DEFAULT 'hotel',
      `amenities`           TEXT DEFAULT NULL,
      `capacity`            TINYINT(3) DEFAULT 2,
      `availability_status` ENUM('active','inactive','maintenance') DEFAULT 'active',
      `hotel_images`        TEXT DEFAULT NULL,
      `featured`            TINYINT(1) DEFAULT 0,
      `checkin_time`        VARCHAR(10) DEFAULT '14:00',
      `checkout_time`       VARCHAR(10) DEFAULT '11:00',
      `phone`               VARCHAR(30) DEFAULT NULL,
      `email`               VARCHAR(255) DEFAULT NULL,
      `assigned_to`         INT(11) UNSIGNED DEFAULT NULL,
      `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_city`      (`city`),
      INDEX `idx_city_id`   (`city_id`),
      INDEX `idx_status`    (`availability_status`),
      INDEX `idx_rating`    (`rating`),
      INDEX `idx_price`     (`price_per_night`),
      INDEX `idx_featured`  (`featured`),
      INDEX `idx_assigned`  (`assigned_to`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);

    $alters = [
      'city_id' => "ALTER TABLE `hotels` ADD COLUMN `city_id` INT(11) UNSIGNED DEFAULT NULL AFTER `hotel_name`",
      'assigned_to' => "ALTER TABLE `hotels` ADD COLUMN `assigned_to` INT(11) UNSIGNED DEFAULT NULL AFTER `email`",
    ];
    foreach ($alters as $col => $q) {
      $chk = mysqli_query($conn, "SHOW COLUMNS FROM `hotels` LIKE '$col'");
      if ($chk && mysqli_num_rows($chk) === 0) {
        mysqli_query($conn, $q);
      }
    }
    
if (!function_exists('syncRoomsTableSchema')) {
function syncRoomsTableSchema($conn) {
    if (!$conn) return;

    $create_sql = "CREATE TABLE IF NOT EXISTS `rooms` (
      `room_id`          INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `hotel_id`         INT(11) UNSIGNED NOT NULL,
      `manager_id`       INT(11) UNSIGNED NOT NULL DEFAULT 1,
      `room_number`      VARCHAR(50) NOT NULL DEFAULT '101',
      `room_type`        VARCHAR(100) NOT NULL DEFAULT 'Standard',
      `room_name`        VARCHAR(150) DEFAULT NULL,
      `floor`            VARCHAR(50) DEFAULT '1st',
      `adult_capacity`   TINYINT(3) DEFAULT 2,
      `child_capacity`   TINYINT(3) DEFAULT 0,
      `capacity`         TINYINT(3) DEFAULT 2,
      `bed_type`         VARCHAR(100) DEFAULT 'Double',
      `base_price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `discount_percent` DECIMAL(5,2) DEFAULT 0.00,
      `discount_pct`     DECIMAL(5,2) DEFAULT 0.00,
      `final_price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `description`      TEXT DEFAULT NULL,
      `amenities`        TEXT DEFAULT NULL,
      `room_images`      TEXT DEFAULT NULL,
      `status`           ENUM('Available','Occupied','Maintenance') DEFAULT 'Available',
      `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_hotel`    (`hotel_id`),
      INDEX `idx_manager`  (`manager_id`),
      INDEX `idx_status`   (`status`),
      INDEX `idx_number`   (`room_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $create_sql);

    $columns = [
        'manager_id'       => "ALTER TABLE `rooms` ADD COLUMN `manager_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 AFTER `hotel_id`",
        'room_number'      => "ALTER TABLE `rooms` ADD COLUMN `room_number` VARCHAR(50) NOT NULL DEFAULT '101' AFTER `manager_id`",
        'room_name'        => "ALTER TABLE `rooms` ADD COLUMN `room_name` VARCHAR(150) DEFAULT NULL AFTER `room_type`",
        'floor'            => "ALTER TABLE `rooms` ADD COLUMN `floor` VARCHAR(50) DEFAULT '1st' AFTER `room_name`",
        'adult_capacity'   => "ALTER TABLE `rooms` ADD COLUMN `adult_capacity` TINYINT(3) DEFAULT 2 AFTER `floor`",
        'child_capacity'   => "ALTER TABLE `rooms` ADD COLUMN `child_capacity` TINYINT(3) DEFAULT 0 AFTER `adult_capacity`",
        'capacity'         => "ALTER TABLE `rooms` ADD COLUMN `capacity` TINYINT(3) DEFAULT 2 AFTER `child_capacity`",
        'bed_type'         => "ALTER TABLE `rooms` ADD COLUMN `bed_type` VARCHAR(100) DEFAULT 'Double' AFTER `capacity`",
        'discount_percent' => "ALTER TABLE `rooms` ADD COLUMN `discount_percent` DECIMAL(5,2) DEFAULT 0.00 AFTER `base_price`",
        'discount_pct'     => "ALTER TABLE `rooms` ADD COLUMN `discount_pct` DECIMAL(5,2) DEFAULT 0.00 AFTER `discount_percent`",
        'final_price'      => "ALTER TABLE `rooms` ADD COLUMN `final_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `discount_pct`",
        'description'      => "ALTER TABLE `rooms` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `final_price`",
        'room_images'      => "ALTER TABLE `rooms` ADD COLUMN `room_images` TEXT DEFAULT NULL AFTER `amenities`",
    ];

    foreach ($columns as $col => $alter_query) {
        $chk = mysqli_query($conn, "SHOW COLUMNS FROM `rooms` LIKE '$col'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            mysqli_query($conn, $alter_query);
        }
    }
}
} // end if (!function_exists('syncRoomsTableSchema'))
    
    $sql = "CREATE TABLE IF NOT EXISTS `bookings` (
      `booking_id`       VARCHAR(20) PRIMARY KEY,
      `user_id`          INT(11) UNSIGNED DEFAULT NULL,
      `hotel_id`         INT(11) UNSIGNED DEFAULT NULL,
      `hotel_name`       VARCHAR(255) NOT NULL,
      `hotel_city`       VARCHAR(100) DEFAULT NULL,
      `room_type`        VARCHAR(100) DEFAULT 'Standard Room',
      `guest_name`       VARCHAR(255) NOT NULL,
      `guest_email`      VARCHAR(255) NOT NULL,
      `guest_phone`      VARCHAR(30) DEFAULT NULL,
      `checkin_date`     DATE NOT NULL,
      `checkout_date`    DATE NOT NULL,
      `nights`           TINYINT(3) DEFAULT 1,
      `guests`           TINYINT(3) DEFAULT 2,
      `base_amount`      DECIMAL(10,2) DEFAULT 0.00,
      `discount_amount`  DECIMAL(10,2) DEFAULT 0.00,
      `tax_amount`       DECIMAL(10,2) DEFAULT 0.00,
      `service_charge`   DECIMAL(10,2) DEFAULT 200.00,
      `coupon_discount`  DECIMAL(10,2) DEFAULT 0.00,
      `total_amount`     DECIMAL(10,2) NOT NULL,
      `payment_method`   VARCHAR(50) DEFAULT 'UPI',
      `payment_status`   ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
      `booking_status`   ENUM('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'confirmed',
      `special_requests` TEXT DEFAULT NULL,
      `arrival_time`     VARCHAR(30) DEFAULT NULL,
      `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_user`   (`user_id`),
      INDEX `idx_hotel`  (`hotel_id`),
      INDEX `idx_status` (`booking_status`),
      INDEX `idx_checkin`(`checkin_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS `reviews` (
      `review_id`        INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `hotel_id`         INT(11) UNSIGNED NOT NULL,
      `user_id`          INT(11) UNSIGNED DEFAULT NULL,
      `guest_name`       VARCHAR(255) NOT NULL,
      `rating`           DECIMAL(2,1) NOT NULL,
      `comment`          TEXT DEFAULT NULL,
      `manager_reply`    TEXT DEFAULT NULL,
      `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_hotel`  (`hotel_id`),
      INDEX `idx_user`   (`user_id`),
      INDEX `idx_rating` (`rating`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);

    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
      `id`         INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id`    INT(11) UNSIGNED NOT NULL,
      `type`       VARCHAR(100) NOT NULL,
      `title`      VARCHAR(255) NOT NULL,
      `message`    TEXT DEFAULT NULL,
      `is_read`    TINYINT(1) DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_user` (`user_id`,`is_read`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);

    $sql = "CREATE TABLE IF NOT EXISTS `notification_settings` (
      `id`           INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id`      INT(11) UNSIGNED NOT NULL,
      `booking_new`  TINYINT(1) DEFAULT 1,
      `booking_cancel` TINYINT(1) DEFAULT 1,
      `room_update`  TINYINT(1) DEFAULT 1,
      `hotel_approval` TINYINT(1) DEFAULT 1,
      `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY `uniq_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);
}

ensure_hotel_tables();

function ensure_cities_table() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS `cities` (
      `id`               INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `city_name`        VARCHAR(100) NOT NULL,
      `state`            VARCHAR(100) DEFAULT NULL,
      `country`          VARCHAR(100) DEFAULT 'India',
      `city_image`       VARCHAR(255) DEFAULT NULL,
      `description`      TEXT DEFAULT NULL,
      `status`           ENUM('active','inactive') DEFAULT 'active',
      `is_popular`       TINYINT(1) DEFAULT 0,
      `created_by`       INT(11) UNSIGNED DEFAULT NULL,
      `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY `uniq_city_name` (`city_name`),
      INDEX `idx_status` (`status`),
      INDEX `idx_popular` (`is_popular`),
      INDEX `idx_country` (`country`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);

    $alters = [
        'state'            => "ALTER TABLE `cities` ADD COLUMN `state` VARCHAR(100) DEFAULT NULL AFTER `city_name`",
        'country'          => "ALTER TABLE `cities` ADD COLUMN `country` VARCHAR(100) DEFAULT 'India' AFTER `state`",
        'city_image'       => "ALTER TABLE `cities` ADD COLUMN `city_image` VARCHAR(255) DEFAULT NULL AFTER `country`",
        'description'      => "ALTER TABLE `cities` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `city_image`",
        'is_popular'       => "ALTER TABLE `cities` ADD COLUMN `is_popular` TINYINT(1) DEFAULT 0 AFTER `status`",
        'created_by'       => "ALTER TABLE `cities` ADD COLUMN `created_by` INT(11) UNSIGNED DEFAULT NULL AFTER `is_popular`",
    ];
    foreach ($alters as $col => $q) {
        $chk = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE '$col'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            mysqli_query($conn, $q);
        }
    }
}
ensure_cities_table();

function hm_sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

function hm_validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hm_validateMobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    return preg_match('/^[6-9][0-9]{9}$/', $mobile);
}

function hm_getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function hm_checkLoginAttempts($email, $max_attempts = 5, $lockout_time = 900) {
    return true;
}

function hm_logLoginAttempt($email, $success = false) {
    // Logging disabled to prevent login_attempts table issues
}

function initializeLoginAttemptsTable() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    mysqli_query($conn, $sql);
}

initializeLoginAttemptsTable();

function getCurrentHotelManager() {
    global $conn;
    if (!isset($_SESSION['hm_id'])) {
        return null;
    }
    $uid = (int)$_SESSION['hm_id'];
    $stmt = mysqli_prepare($conn, "SELECT id, first_name, last_name, email, mobile, role FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $manager = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $manager ?: null;
}

?>
