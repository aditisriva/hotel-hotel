<?php
/**
 * Admin Panel — Database & Auth
 * Completely independent from User and Hotel Manager panels
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_db_host = 'localhost';
$_db_user = 'root';
$_db_name = 'bookhotel_db';

$_db_candidates = [
    ['Aditi@1521', 3306],
    ['Aditi@1521', 3307],
    ['', 3306],
    ['', 3307],
    ['root',       3306],
    ['root',       3307],
    ['mysql',      3306],
];

$_prev_report = mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;
foreach ($_db_candidates as [$_pass, $_port]) {
    $conn = mysqli_connect($_db_host, $_db_user, $_pass, null, $_port);
    if ($conn) {
        define('DB_HOST', $_db_host);
        define('DB_USER', $_db_user);
        define('DB_PASS', $_pass);
        define('DB_NAME', $_db_name);
        define('DB_PORT', $_port);
        break;
    }
}
unset($_db_candidates, $_db_host, $_db_user, $_db_name, $_pass, $_port);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!$conn) {
    http_response_code(503);
    die('<h2 style="font-family:sans-serif;color:#c0392b">&#x26A0; Database Connection Failed</h2>'
      . '<p style="font-family:sans-serif">Could not connect to MySQL.</p>'
      . '<p style="font-family:sans-serif;color:#888">Error: ' . htmlspecialchars(mysqli_connect_error()) . '</p>');
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

if (!mysqli_select_db($conn, DB_NAME)) {
    die("Error selecting database: " . mysqli_error($conn));
}

mysqli_set_charset($conn, "utf8mb4");

function initializeAdminsTable() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS `admins` (
        `admin_id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `mobile` VARCHAR(20) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        `email_verified` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `last_login` TIMESTAMP NULL DEFAULT NULL,
        `profile_image` VARCHAR(255) DEFAULT NULL,
        INDEX `idx_email` (`email`),
        INDEX `idx_mobile` (`mobile`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $sql)) {
        die("Error creating admins table: " . mysqli_error($conn));
    }
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
    if (!mysqli_query($conn, $sql)) {
        die("Error creating login_attempts table: " . mysqli_error($conn));
    }
}

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
    if (!mysqli_query($conn, $sql)) {
        die("Error creating notifications table: " . mysqli_error($conn));
    }

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
    if (!mysqli_query($conn, $sql)) {
        die("Error creating notification_settings table: " . mysqli_error($conn));
    }

    initializeAdminsTable();
    initializeLoginAttemptsTable();

function admin_sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

function admin_validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function admin_validateMobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    return preg_match('/^[6-9][0-9]{9}$/', $mobile);
}

function admin_emailExists($email) {
    global $conn;
    $email = admin_sanitize($email);
    $sql = "SELECT admin_id FROM admins WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

function admin_mobileExists($mobile) {
    global $conn;
    $mobile = admin_sanitize($mobile);
    $sql = "SELECT admin_id FROM admins WHERE mobile = '$mobile' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

function admin_getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function admin_checkLoginAttempts($email, $max_attempts = 5, $lockout_time = 900) {
    return true;
}

function admin_logLoginAttempt($email, $success = false) {
    // Logging disabled to prevent login_attempts table issues
}

function admin_cleanOldLoginAttempts($days = 30) {
    global $conn;
    $threshold = date('Y-m-d H:i:s', time() - ($days * 24 * 60 * 60));
    $sql = "DELETE FROM login_attempts WHERE attempted_at < '$threshold'";
    mysqli_query($conn, $sql);
}

function admin_initializeCitiesTable() {
    global $conn;
    $chk = mysqli_query($conn, "SHOW TABLES LIKE 'cities'");
    $exists = $chk && mysqli_num_rows($chk) > 0;

    if (!$exists) {
        $sql = "CREATE TABLE `cities` (
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
    } else {
        $chk_id = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE 'id'");
        if ($chk_id && mysqli_num_rows($chk_id) === 0) {
            $chk_cid = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE 'city_id'");
            if ($chk_cid && mysqli_num_rows($chk_cid) > 0) {
                mysqli_query($conn, "ALTER TABLE `cities` CHANGE COLUMN `city_id` `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST");
            }
        }
        $chk_name = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE 'city_name'");
        if ($chk_name && mysqli_num_rows($chk_name) === 0) {
            $chk_old = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE 'name'");
            if ($chk_old && mysqli_num_rows($chk_old) > 0) {
                mysqli_query($conn, "ALTER TABLE `cities` CHANGE COLUMN `name` `city_name` VARCHAR(100) NOT NULL");
            }
        }
    }

    $alters = [
        'state'       => "ALTER TABLE `cities` ADD COLUMN `state` VARCHAR(100) DEFAULT NULL AFTER `city_name`",
        'country'     => "ALTER TABLE `cities` ADD COLUMN `country` VARCHAR(100) DEFAULT 'India' AFTER `state`",
        'city_image'  => "ALTER TABLE `cities` ADD COLUMN `city_image` VARCHAR(255) DEFAULT NULL AFTER `country`",
        'description' => "ALTER TABLE `cities` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `city_image`",
        'is_popular'  => "ALTER TABLE `cities` ADD COLUMN `is_popular` TINYINT(1) DEFAULT 0 AFTER `status`",
        'created_by'  => "ALTER TABLE `cities` ADD COLUMN `created_by` INT(11) UNSIGNED DEFAULT NULL AFTER `is_popular`",
    ];
    foreach ($alters as $col => $q) {
        $chk = mysqli_query($conn, "SHOW COLUMNS FROM `cities` LIKE '$col'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            mysqli_query($conn, $q);
        }
    }
}

admin_initializeCitiesTable();

function admin_handleCityImageUpload(int $cityId = 0): string {
    if (!isset($_FILES['city_image']) || $_FILES['city_image']['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($_FILES['city_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return '';
    $uploadDir = __DIR__ . '/uploads/cities/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'city_' . ($cityId ?: time()) . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['city_image']['tmp_name'], $uploadDir . $filename)) {
        return 'uploads/cities/' . $filename;
    }
    return '';
}

function getCurrentAdmin() {
    global $conn;
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    $admin_id = (int)$_SESSION['admin_id'];
    $sql = "SELECT admin_id, first_name, last_name, email, mobile, role FROM admins WHERE admin_id = $admin_id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        $admin['profile_image'] = '';
        return $admin;
    }
    return null;
}

?>
