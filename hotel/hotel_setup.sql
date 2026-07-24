-- ================================================================
-- BookHotel — Hotel Table Setup & Initial Data
-- Run this in phpMyAdmin or MySQL CLI
-- ================================================================

USE bookhotel_db;

-- Drop and recreate hotels table
CREATE TABLE IF NOT EXISTS `hotels` (
  `hotel_id`           INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hotel_name`         VARCHAR(255) NOT NULL,
  `city_id`            INT(11) UNSIGNED DEFAULT NULL,
  `city`               VARCHAR(100) NOT NULL,
  `location`           VARCHAR(255) NOT NULL,
  `state`              VARCHAR(100) DEFAULT NULL,
  `description`        TEXT DEFAULT NULL,
  `price_per_night`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `original_price`     DECIMAL(10,2) DEFAULT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `gst_percentage`     DECIMAL(5,2) DEFAULT 12.00,
  `rating`             DECIMAL(3,1) DEFAULT 0.0,
  `star_rating`        TINYINT(1) DEFAULT 3,
  `property_type`      ENUM('hotel','resort','villa','homestay','boutique-hotel') DEFAULT 'hotel',
  `amenities`          TEXT DEFAULT NULL COMMENT 'comma-separated: wifi,pool,breakfast,parking,ac,gym,spa',
  `capacity`           TINYINT(3) DEFAULT 2 COMMENT 'max guests',
  `availability_status` ENUM('active','inactive','maintenance') DEFAULT 'active',
  `hotel_images`       TEXT DEFAULT NULL COMMENT 'JSON array of image URLs',
  `featured`           TINYINT(1) DEFAULT 0,
  `checkin_time`       VARCHAR(10) DEFAULT '14:00',
  `checkout_time`      VARCHAR(10) DEFAULT '11:00',
  `phone`               VARCHAR(30) DEFAULT NULL,
  `email`               VARCHAR(255) DEFAULT NULL,
  `assigned_to`         INT(11) UNSIGNED DEFAULT NULL,
  `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_city`     (`city`),
  INDEX `idx_city_id`  (`city_id`),
  INDEX `idx_status`   (`availability_status`),
  INDEX `idx_rating`   (`rating`),
  INDEX `idx_price`    (`price_per_night`),
  INDEX `idx_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
