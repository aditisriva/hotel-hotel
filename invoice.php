<?php
session_start();
require_once 'db.php';
require_once 'hotel_functions.php';

$booking_id = trim($_GET['bid'] ?? '');
$user_id    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$booking = null;
if ($booking_id && $user_id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT b.*, h.hotel_images, h.location AS hotel_location, h.phone AS hotel_phone,
                h.email AS hotel_email, h.checkin_time, h.checkout_time,
                h.gst_percentage
         FROM bookings b
         LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
         WHERE b.booking_id = ? AND b.user_id = ?
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$booking) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:4rem">Invoice not found or access denied.</h2>');
}

$img         = bhFirstImage($booking['hotel_images'] ?? '', '');
$hotel_name  = $booking['hotel_name'] ?? 'Hotel';
$hotel_city  = $booking['hotel_city'] ?? '';
$hotel_loc   = $booking['hotel_location'] ?? '';
$hotel_phone = $booking['hotel_phone'] ?? '';
$hotel_email = $booking['hotel_email'] ?? '';

$ci  = $booking['checkin_date']  ? date('D, d M Y', strtotime($booking['checkin_date']))  : '-';
$co  = $booking['checkout_date'] ? date('D, d M Y', strtotime($booking['checkout_date'])) : '-';
$bon = $booking['created_at']    ? date('d M Y, h:i A', strtotime($booking['created_at'])) : '-';

$nights        = (int)($booking['nights'] ?? 1);
$base          = (float)($booking['base_amount'] ?? 0);
$discount      = (float)($booking['discount_amount'] ?? 0);
$coupon        = (float)($booking['coupon_discount'] ?? 0);
$tax           = (float)($booking['tax_amount'] ?? 0);
$service       = (float)($booking['service_charge'] ?? 0);
$total         = (float)($booking['total_amount'] ?? 0);
$gst_pct       = (float)($booking['gst_percentage'] ?? 12);
$pay_method    = $booking['payment_method'] ?? 'UPI';
$pay_status    = ucfirst($booking['payment_status'] ?? 'paid');
$book_status   = ucfirst(str_replace('_', ' ', $booking['booking_status'] ?? 'confirmed'));
$guest_name    = $booking['guest_name']  ?? $_SESSION['user_name'] ?? 'Guest';
$guest_email   = $booking['guest_email'] ?? '';
$guest_phone   = $booking['guest_phone'] ?? '';
$room_type     = $booking['room_type']   ?? 'Standard Room';
$guests_num    = (int)($booking['guests'] ?? 1);
$special_req   = $booking['special_requests'] ?? '';
$arrival_time  = $booking['arrival_time'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Invoice #<?= htmlspecialchars($booking_id) ?> — bookHotel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;}
    .inv-wrap{max-width:820px;margin:2rem auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 30px rgba(0,0,0,.1);}
    .inv-header{background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#1a56db 100%);padding:2.5rem 2rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;}
    .inv-logo{display:flex;align-items:center;gap:.75rem;}
    .inv-logo-icon{width:44px;height:44px;background:rgba(245,158,11,.9);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
    .inv-logo-name{font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.3px;}
    .inv-logo-tag{font-size:.75rem;color:rgba(255,255,255,.6);}
    .inv-meta{text-align:right;}
    .inv-meta h1{font-size:1.6rem;font-weight:800;color:#f59e0b;margin-bottom:.25rem;}
    .inv-meta p{font-size:.8rem;color:rgba(255,255,255,.7);}
    .inv-body{padding:2rem;}
    .inv-section{margin-bottom:1.75rem;}
    .inv-section-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
    .inv-section-title::after{content:'';flex:1;height:1px;background:#e2e8f0;}
    .inv-hotel-card{display:flex;gap:1.25rem;padding:1.25rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;margin-bottom:1.75rem;}
    .inv-hotel-img{width:90px;height:70px;object-fit:cover;border-radius:8px;flex-shrink:0;}
    .inv-hotel-info h2{font-size:1.05rem;font-weight:800;color:#0f172a;margin-bottom:.25rem;}
    .inv-hotel-info p{font-size:.8rem;color:#64748b;margin-bottom:.2rem;}
    .inv-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
    .inv-field{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.75rem 1rem;}
    .inv-field-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:.3rem;}
    .inv-field-val{font-size:.9rem;font-weight:600;color:#1e293b;}
    .inv-table{width:100%;border-collapse:collapse;}
    .inv-table th{background:#f8fafc;padding:.75rem 1rem;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;border-bottom:1.5px solid #e2e8f0;}
    .inv-table td{padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.87rem;}
    .inv-table tr:last-child td{border-bottom:none;}
    .inv-table td.amt{text-align:right;font-weight:600;}
    .inv-table td.disc{text-align:right;color:#059669;font-weight:600;}
    .inv-table tr.total-row td{font-weight:800;font-size:1rem;border-top:2px solid #e2e8f0;padding-top:1rem;}
    .inv-table tr.total-row td.amt{color:#1a56db;}
    .inv-badge{display:inline-flex;align-items:center;padding:.3rem .8rem;border-radius:50px;font-size:.75rem;font-weight:700;}
    .inv-badge-paid{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
    .inv-badge-refund{background:#fef3c7;color:#78350f;border:1px solid #fde68a;}
    .inv-badge-pending{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
    .inv-footer{background:#f8fafc;border-top:1.5px solid #e2e8f0;padding:1.5rem 2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
    .inv-footer-note{font-size:.78rem;color:#64748b;}
    .inv-footer-note strong{color:#1e293b;}
    .inv-print-btn{background:#1a56db;color:#fff;border:none;padding:.7rem 1.6rem;border-radius:8px;font-family:'Inter',sans-serif;font-size:.87rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;transition:background .2s;}
    .inv-print-btn:hover{background:#1141b0;}
    @media print{
      body{background:#fff;}
      .inv-wrap{box-shadow:none;margin:0;border-radius:0;}
      .inv-print-btn{display:none!important;}
    }
    @media(max-width:600px){
      .inv-grid{grid-template-columns:1fr;}
      .inv-header{flex-direction:column;}
      .inv-meta{text-align:left;}
    }
  </style>
</head>
<body>
<div class="inv-wrap">
  <!-- Header -->
  <div class="inv-header">
    <div>
      <div class="inv-logo">
        <div class="inv-logo-icon">🏨</div>
        <div>
          <div class="inv-logo-name">bookHotel</div>
          <div class="inv-logo-tag">Hotel Booking Platform</div>
        </div>
      </div>
    </div>
    <div class="inv-meta">
      <h1>INVOICE</h1>
      <p>#<?= htmlspecialchars($booking_id) ?></p>
      <p>Issued: <?= $bon ?></p>
    </div>
  </div>

  <div class="inv-body">
    <!-- Hotel -->
    <div class="inv-hotel-card">
      <?php if ($img): ?>
      <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($hotel_name) ?>" class="inv-hotel-img"/>
      <?php endif; ?>
      <div class="inv-hotel-info">
        <h2><?= htmlspecialchars($hotel_name) ?></h2>
        <?php if ($hotel_loc): ?><p>📍 <?= htmlspecialchars($hotel_loc) ?></p><?php endif; ?>
        <?php if ($hotel_phone): ?><p>📞 <?= htmlspecialchars($hotel_phone) ?></p><?php endif; ?>
        <?php if ($hotel_email): ?><p>✉ <?= htmlspecialchars($hotel_email) ?></p><?php endif; ?>
      </div>
    </div>

    <!-- Stay Details -->
    <div class="inv-section">
      <div class="inv-section-title">Stay Details</div>
      <div class="inv-grid">
        <div class="inv-field"><div class="inv-field-label">Check-in</div><div class="inv-field-val"><?= $ci ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Check-out</div><div class="inv-field-val"><?= $co ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Duration</div><div class="inv-field-val"><?= $nights ?> Night<?= $nights !== 1 ? 's' : '' ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Room Type</div><div class="inv-field-val"><?= htmlspecialchars($room_type) ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Guests</div><div class="inv-field-val"><?= $guests_num ?> Adult<?= $guests_num !== 1 ? 's' : '' ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Booking Status</div><div class="inv-field-val"><?= htmlspecialchars($book_status) ?></div></div>
        <?php if ($arrival_time): ?>
        <div class="inv-field"><div class="inv-field-label">Expected Arrival</div><div class="inv-field-val"><?= htmlspecialchars($arrival_time) ?></div></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Guest Details -->
    <div class="inv-section">
      <div class="inv-section-title">Guest Details</div>
      <div class="inv-grid">
        <div class="inv-field"><div class="inv-field-label">Guest Name</div><div class="inv-field-val"><?= htmlspecialchars($guest_name) ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Email</div><div class="inv-field-val"><?= htmlspecialchars($guest_email) ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Phone</div><div class="inv-field-val"><?= htmlspecialchars($guest_phone) ?></div></div>
        <div class="inv-field"><div class="inv-field-label">Payment Method</div><div class="inv-field-val"><?= htmlspecialchars($pay_method) ?></div></div>
      </div>
      <?php if ($special_req): ?>
      <div class="inv-field" style="margin-top:.75rem">
        <div class="inv-field-label">Special Requests</div>
        <div class="inv-field-val"><?= htmlspecialchars($special_req) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Billing Breakdown -->
    <div class="inv-section">
      <div class="inv-section-title">Billing Breakdown</div>
      <table class="inv-table">
        <thead>
          <tr>
            <th>Description</th>
            <th style="text-align:right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($room_type) ?> × <?= $nights ?> night<?= $nights !== 1 ? 's' : '' ?></td>
            <td class="amt">₹<?= number_format($base, 2) ?></td>
          </tr>
          <?php if ($discount > 0): ?>
          <tr>
            <td>Hotel Discount</td>
            <td class="disc">−₹<?= number_format($discount, 2) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($coupon > 0): ?>
          <tr>
            <td>Coupon Discount</td>
            <td class="disc">−₹<?= number_format($coupon, 2) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($tax > 0): ?>
          <tr>
            <td>GST & Taxes (<?= $gst_pct ?>%)</td>
            <td class="amt">₹<?= number_format($tax, 2) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($service > 0): ?>
          <tr>
            <td>Service Charge</td>
            <td class="amt">₹<?= number_format($service, 2) ?></td>
          </tr>
          <?php endif; ?>
          <tr class="total-row">
            <td>
              Total Amount
              <?php
              $badge_cls2 = strtolower($booking['payment_status'] ?? 'paid') === 'paid' ? 'inv-badge-paid'
                          : (str_contains(strtolower($booking['payment_status'] ?? ''), 'refund') ? 'inv-badge-refund' : 'inv-badge-pending');
              ?>
              <span class="inv-badge <?= $badge_cls2 ?> ms-2"><?= htmlspecialchars($pay_status) ?></span>
            </td>
            <td class="amt">₹<?= number_format($total, 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Note -->
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1rem;font-size:.8rem;color:#1e40af;">
      <strong>Note:</strong> This is a computer-generated invoice and does not require a physical signature.
      For queries, contact bookHotel support or the hotel directly.
    </div>
  </div>

  <!-- Footer -->
  <div class="inv-footer">
    <div class="inv-footer-note">
      <strong>bookHotel</strong> · Booking ID: <strong><?= htmlspecialchars($booking_id) ?></strong><br/>
      Thank you for choosing bookHotel!
    </div>
    <button class="inv-print-btn" onclick="window.print()">
      🖨 Print / Save PDF
    </button>
  </div>
</div>
</body>
</html>
