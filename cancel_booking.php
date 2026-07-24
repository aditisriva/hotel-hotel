<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel a booking.']);
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

$stmt = mysqli_prepare($conn, "SELECT booking_id, booking_status, checkin_date FROM bookings WHERE booking_id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'si', $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
    exit;
}

$allowed_statuses = ['confirmed', 'pending'];
if (!in_array(strtolower($booking['booking_status']), $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled (status: ' . htmlspecialchars($booking['booking_status']) . ').']);
    exit;
}

if ($booking['checkin_date'] && strtotime($booking['checkin_date']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a booking after check-in date.']);
    exit;
}

$upd = mysqli_prepare($conn, "UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refund_pending', updated_at = NOW() WHERE booking_id = ? AND user_id = ?");
mysqli_stmt_bind_param($upd, 'si', $booking_id, $user_id);
$ok = mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

if ($ok && mysqli_affected_rows($conn) > 0) {
    echo json_encode(['success' => true, 'message' => 'Booking cancelled. Refund will be processed in 5-7 business days.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking. Please try again.']);
}
exit;
