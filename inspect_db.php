<?php
require_once 'db.php';

echo "Connected successfully to: " . DB_HOST . ":" . DB_PORT . "\n";

echo "\n--- CITIES TABLE ---\n";
$res = mysqli_query($conn, "SELECT * FROM cities");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "ID: {$row['id']} | Name: {$row['city_name']} | Status: {$row['status']} | Popular: {$row['is_popular']}\n";
    }
} else {
    echo "Error querying cities: " . mysqli_error($conn) . "\n";
}

echo "\n--- HOTELS TABLE ---\n";
$res = mysqli_query($conn, "SELECT hotel_id, hotel_name, city_id, city, availability_status, approval_status FROM hotels");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "ID: {$row['hotel_id']} | Name: {$row['hotel_name']} | City ID: {$row['city_id']} | City: {$row['city']} | Status: {$row['availability_status']} | Approval: {$row['approval_status']}\n";
    }
} else {
    echo "Error querying hotels: " . mysqli_error($conn) . "\n";
}
?>
