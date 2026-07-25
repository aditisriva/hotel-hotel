<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/hotel_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel your booking.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$booking_id = trim($_POST['booking_id'] ?? '');
$user_id    = (int)$_SESSION['user_id'];

if (empty($booking_id)) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}

// Fix payment_status & booking_status column types if ENUM truncates values
@mysqli_query($conn, "ALTER TABLE bookings MODIFY COLUMN `payment_status` VARCHAR(50) DEFAULT 'pending'");
@mysqli_query($conn, "ALTER TABLE bookings MODIFY COLUMN `booking_status` VARCHAR(50) DEFAULT 'confirmed'");

$chk_cancelled = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'cancelled_at'");
if ($chk_cancelled && mysqli_num_rows($chk_cancelled) === 0) {
    @mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL");
}

$chk_refund = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'refund_status'");
if ($chk_refund && mysqli_num_rows($chk_refund) === 0) {
    @mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN `refund_status` VARCHAR(50) DEFAULT 'none'");
}

$stmt = mysqli_prepare($conn, "SELECT booking_id, hotel_id, room_type, booking_status, checkin_date, payment_status FROM bookings WHERE booking_id = ? AND user_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'si', $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
    exit;
}

$current_status = strtolower($booking['booking_status']);

if ($current_status === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'This booking is already cancelled.']);
    exit;
}

$allowed_statuses = ['confirmed', 'pending'];
if (!in_array($current_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled (current status: ' . htmlspecialchars($booking['booking_status']) . ').']);
    exit;
}

if (!empty($booking['checkin_date'])) {
    $today_ts   = strtotime(date('Y-m-d'));
    $checkin_ts = strtotime($booking['checkin_date']);
    if ($checkin_ts <= $today_ts) {
        echo json_encode(['success' => false, 'message' => 'Cancellation period expired. Cannot cancel on or after check-in date.']);
        exit;
    }
}

$upd = mysqli_prepare($conn, "UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refund_pending', refund_status = 'refund_pending', cancelled_at = NOW(), updated_at = NOW() WHERE booking_id = ? AND user_id = ?");
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Database update error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($upd, 'si', $booking_id, $user_id);
$ok = mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

if ($ok) {
    if (!empty($booking['hotel_id']) && !empty($booking['room_type'])) {
        $hid = (int)$booking['hotel_id'];
        $rt  = $booking['room_type'];
        $room_upd = mysqli_prepare($conn, "UPDATE rooms SET status = 'Available', updated_at = NOW() WHERE hotel_id = ? AND room_type = ? AND status = 'Occupied' LIMIT 1");
        if ($room_upd) {
            mysqli_stmt_bind_param($room_upd, 'is', $hid, $rt);
            mysqli_stmt_execute($room_upd);
            mysqli_stmt_close($room_upd);
        }
    }

    echo json_encode([
        'success'    => true,
        'message'    => 'Booking cancelled successfully.',
        'booking_id' => $booking_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking. Database error: ' . mysqli_error($conn)]);
}
exit;