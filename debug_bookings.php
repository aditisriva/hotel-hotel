<?php
require 'F:/dummy prrrooooojjjeeecccttt/Prrrooooojjjeeecccttt/db.php';

// Check bookings table
$result = mysqli_query($conn, "SHOW TABLES LIKE 'bookings'");
echo "Bookings table exists: " . (mysqli_num_rows($result) > 0 ? "YES" : "NO") . "\n";

$result2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings");
$row = mysqli_fetch_assoc($result2);
echo "Total bookings in DB: " . $row['c'] . "\n";

if ($row['c'] > 0) {
    $result3 = mysqli_query($conn, "SELECT booking_id, user_id, hotel_id, hotel_name, booking_status, payment_status, created_at FROM bookings ORDER BY created_at DESC LIMIT 10");
    while ($r = mysqli_fetch_assoc($result3)) {
        echo "  booking_id=" . $r['booking_id'] . " user_id=" . $r['user_id'] . " hotel_id=" . $r['hotel_id'] . " hotel_name=" . $r['hotel_name'] . " status=" . $r['booking_status'] . " payment=" . $r['payment_status'] . " created=" . $r['created_at'] . "\n";
    }
}

// Check users
$u = mysqli_query($conn, "SELECT id, email, first_name, last_name FROM users LIMIT 10");
echo "\nUsers:\n";
while ($row = mysqli_fetch_assoc($u)) {
    echo "  id=" . $row['id'] . " email=" . $row['email'] . " name=" . $row['first_name'] . " " . $row['last_name'] . "\n";
}

// Check hotels
$h = mysqli_query($conn, "SELECT hotel_id, hotel_name, city FROM hotels LIMIT 10");
echo "\nHotels:\n";
while ($row = mysqli_fetch_assoc($h)) {
    echo "  hotel_id=" . $row['hotel_id'] . " name=" . $row['hotel_name'] . " city=" . $row['city'] . "\n";
}