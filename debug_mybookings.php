<?php
require 'F:/dummy prrrooooojjjeeecccttt/Prrrooooojjjeeecccttt/db.php';

// Simulate what my-bookings.php does
$test_user_id = 8; // Apoorva Sriva

$sql = "SELECT b.*, h.hotel_images, h.location,
               r.review_id, r.rating AS review_rating, r.comment AS review_comment, r.status AS review_status, r.manager_reply
        FROM bookings b
        LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
        LEFT JOIN reviews r ON b.booking_id = r.booking_id
        WHERE b.user_id = $test_user_id
        ORDER BY b.created_at DESC";

$res = mysqli_query($conn, $sql);
echo "Query: $sql\n";
echo "Result count: " . mysqli_num_rows($res) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "  booking_id=" . $row['booking_id'] . " hotel_name=" . $row['hotel_name'] . " status=" . $row['booking_status'] . " payment=" . $row['payment_status'] . "\n";
}

// Also check with user_id=9
$test_user_id = 9;
$sql = "SELECT b.*, h.hotel_images FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.hotel_id WHERE b.user_id=$test_user_id";
$res = mysqli_query($conn, $sql);
echo "\nFor user_id=$test_user_id: count=" . mysqli_num_rows($res) . "\n";

// Also check with user_id=null (not logged in)
$test_user_id = null;
$sql = "SELECT b.*, h.hotel_images FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.hotel_id WHERE b.user_id IS NULL";
$res = mysqli_query($conn, $sql);
echo "\nFor user_id=NULL: count=" . mysqli_num_rows($res) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "  booking_id=" . $row['booking_id'] . " hotel_name=" . $row['hotel_name'] . "\n";
}