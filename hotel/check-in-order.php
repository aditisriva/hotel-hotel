<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'auth_guard.php';
require_once 'hotel_functions.php';

$manager = getCurrentHotelManager();
$manager_id    = $manager ? (int)$manager['id'] : 0;
$manager_name  = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role  = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';
$is_admin      = ($manager && strtolower($manager['role'] ?? '') === 'admin');

// Manager clause for SQL queries
if ($is_admin) {
    $manager_where = "1=1";
} else {
    $manager_where = "(h.assigned_to = $manager_id OR h.assigned_to IS NULL OR h.assigned_to = 0)";
}

// AJAX / POST: Check-in & Check-out actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $booking_id = trim($_POST['booking_id'] ?? '');
    if (!$booking_id) {
        echo json_encode(['success' => false, 'message' => 'Missing booking ID', 'error' => 'Missing booking ID']);
        exit;
    }

    // Auto-create columns if missing
    $chk1 = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'checked_in_at'");
    if ($chk1 && mysqli_num_rows($chk1) === 0) {
        @mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN `checked_in_at` TIMESTAMP NULL DEFAULT NULL");
    }
    $chk2 = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'checked_out_at'");
    if ($chk2 && mysqli_num_rows($chk2) === 0) {
        @mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN `checked_out_at` TIMESTAMP NULL DEFAULT NULL");
    }

    // Verify booking belongs to this manager's hotel
    $bid_esc = mysqli_real_escape_string($conn, $booking_id);
    $chk_sql = "SELECT b.booking_id, b.booking_status, b.hotel_id, b.room_type
                FROM bookings b
                INNER JOIN hotels h ON b.hotel_id = h.hotel_id
                WHERE b.booking_id = '$bid_esc' AND $manager_where
                LIMIT 1";
    $chk_res = mysqli_query($conn, $chk_sql);
    $brow    = $chk_res ? mysqli_fetch_assoc($chk_res) : null;

    if (!$brow) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or access denied for this hotel manager.', 'error' => 'Booking not found or not authorized']);
        exit;
    }

    if ($_POST['action'] === 'mark_checked_in') {
        $cur_status = strtolower($brow['booking_status']);
        if (!in_array($cur_status, ['confirmed', 'pending'])) {
            echo json_encode(['success' => false, 'message' => 'Cannot check in a booking with status: ' . $brow['booking_status'], 'error' => 'Invalid status for check-in']);
            exit;
        }

        $u_sql = "UPDATE bookings SET booking_status='checked_in', checked_in_at=NOW(), updated_at=NOW() WHERE booking_id='$bid_esc'";
        $ok    = mysqli_query($conn, $u_sql);

        if ($ok && $brow['hotel_id']) {
            $hid = (int)$brow['hotel_id'];
            $rt  = mysqli_real_escape_string($conn, $brow['room_type']);
            mysqli_query($conn, "UPDATE rooms SET status='Occupied', updated_at=NOW() WHERE hotel_id=$hid AND room_type='$rt' AND status='Available' LIMIT 1");
        }

        if ($ok) {
            echo json_encode([
                'success'    => true,
                'message'    => 'Guest checked in successfully.',
                'new_status' => 'checked_in',
                'booking_id' => $booking_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . mysqli_error($conn)]);
        }
        exit;
    }

    if ($_POST['action'] === 'mark_checked_out') {
        $cur_status = strtolower($brow['booking_status']);
        if ($cur_status !== 'checked_in') {
            echo json_encode(['success' => false, 'message' => 'Booking must be checked in before checkout.']);
            exit;
        }

        $u_sql = "UPDATE bookings SET booking_status='checked_out', checked_out_at=NOW(), updated_at=NOW() WHERE booking_id='$bid_esc'";
        $ok    = mysqli_query($conn, $u_sql);

        if ($ok && $brow['hotel_id']) {
            $hid = (int)$brow['hotel_id'];
            $rt  = mysqli_real_escape_string($conn, $brow['room_type']);
            mysqli_query($conn, "UPDATE rooms SET status='Available', updated_at=NOW() WHERE hotel_id=$hid AND room_type='$rt' AND status='Occupied' LIMIT 1");
        }

        if ($ok) {
            echo json_encode([
                'success'    => true,
                'message'    => 'Guest checked out successfully.',
                'new_status' => 'checked_out',
                'booking_id' => $booking_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . mysqli_error($conn)]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action requested.']);
    exit;
}

// Fetch bookings
$today = date('Y-m-d');
$max_arrival_date = date('Y-m-d', strtotime('+1 day'));
$active_tab = $_GET['tab'] ?? 'today';

if ($active_tab === 'checked_in') {
    // In-house Guests tab
    $sql = "SELECT b.*, h.hotel_name AS hotel_display_name
            FROM bookings b
            INNER JOIN hotels h ON b.hotel_id = h.hotel_id
            WHERE $manager_where
              AND LOWER(b.booking_status) = 'checked_in'
            ORDER BY b.checkin_date ASC, b.guest_name ASC";
} else {
    // Today's Arrivals tab
    $sql = "SELECT b.*, h.hotel_name AS hotel_display_name
            FROM bookings b
            INNER JOIN hotels h ON b.hotel_id = h.hotel_id
            WHERE $manager_where
              AND LOWER(b.booking_status) IN ('confirmed', 'pending')
              AND LOWER(b.payment_status) IN ('paid', 'success', 'completed')
              AND b.checkin_date <= '$max_arrival_date'
            ORDER BY b.checkin_date ASC, b.guest_name ASC";
}

$res = mysqli_query($conn, $sql);
$bookings = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $bookings[] = $row;
    }
}
$rows_returned = count($bookings);

// Summary stats
$stats_sql = "SELECT
   SUM(b.checkin_date <= '$max_arrival_date' AND LOWER(b.booking_status) IN ('confirmed','pending') AND LOWER(b.payment_status) IN ('paid','success','completed')) AS today_arrivals,
   SUM(LOWER(b.booking_status) = 'checked_in') AS currently_in,
   SUM(b.checkout_date = '$today' AND LOWER(b.booking_status) = 'checked_in') AS today_departures
 FROM bookings b
 INNER JOIN hotels h ON b.hotel_id = h.hotel_id
 WHERE $manager_where";
$stats_res = mysqli_query($conn, $stats_sql);
$stats = ($stats_res ? mysqli_fetch_assoc($stats_res) : null) ?? ['today_arrivals'=>0,'currently_in'=>0,'today_departures'=>0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Check In Order | Hotel Operations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
  <style>
    .tab-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.42rem 1rem;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;border:1.5px solid var(--bdr);background:#fff;color:var(--mut);text-decoration:none;transition:all .2s}
    .tab-pill.active{background:var(--pr);color:#fff;border-color:var(--pr)}
    .tab-pill:hover:not(.active){border-color:var(--pr);color:var(--pr)}
    .guest-card{background:#fff;border:1.5px solid var(--bdr);border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.05);padding:1.25rem 1.5rem;transition:all .2s;position:relative}
    .guest-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.1);transform:translateY(-2px)}
    .guest-av{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--pr),#1d4ed8);color:#fff;font-size:1.1rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .detail-row{display:flex;align-items:center;gap:.45rem;font-size:.82rem;color:var(--mut);margin-top:.3rem}
    .detail-row i{font-size:.85rem;flex-shrink:0}
    .empty-state{text-align:center;padding:4rem 1rem}
    .empty-state i{font-size:3.5rem;color:#cbd5e1}
    .action-group{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-top:.85rem}
    .btn-checkin{background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:all .2s}
    .btn-checkin:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,.35)}
    .btn-checkout{background:linear-gradient(135deg,var(--pr),var(--pr-dk));color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:all .2s}
    .btn-checkout:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,86,219,.35)}
    .btn-done{background:#f1f5f9;color:#64748b;border:1.5px solid var(--bdr);border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:600;cursor:default;display:flex;align-items:center;gap:.4rem}
    .ds-toast-wrap{position:fixed;top:1.25rem;right:1.25rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none}
    .ds-toast{background:#0f172a;color:#fff;border-radius:10px;padding:.75rem 1.1rem;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:.5rem;opacity:0;transform:translateX(40px);transition:all .35s;pointer-events:auto}
    .ds-toast.show{opacity:1;transform:translateX(0)}
    .ds-toast.success i{color:#10b981}
    .ds-toast.error i{color:#ef4444}
    .loading-overlay{display:none;position:absolute;inset:0;background:rgba(255,255,255,.7);border-radius:14px;z-index:10;align-items:center;justify-content:center}
    .spinner-ring{width:32px;height:32px;border:3px solid var(--bdr);border-top-color:var(--pr);border-radius:50%;animation:spin .7s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
    .today-tag{display:inline-flex;align-items:center;gap:.3rem;background:#fef3c7;color:#92400e;border-radius:6px;padding:.15rem .55rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
    .overdue-tag{background:#fee2e2;color:#991b1b}
  </style>
</head>
<body>
<!-- DEBUG LOG FOR BACKEND VERIFICATION -->
<!--
[Check-In Backend Debug Log]
Manager ID: <?php echo $manager_id; ?> (Role: <?php echo htmlspecialchars($manager_role); ?>)
Active Tab: <?php echo htmlspecialchars($active_tab); ?>

Generated SQL Query:
<?php echo htmlspecialchars($sql); ?>

Rows Returned: <?php echo $rows_returned; ?>

-->

<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo">
    <div class="ds-logo-icon"><i class="bi bi-buildings"></i></div>
    <div><div class="ds-logo-name">BookHotel</div><div class="ds-logo-role">Hotel Operations</div></div>
  </a>
  <nav class="ds-nav" id="mainSidebar">
    <div class="ds-sec">Main</div>
    <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
    <a href="manage-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Manage Bookings</a>
    <a href="check-in-order.php" class="ds-link"><i class="bi bi-person-check-fill"></i> Check In Order</a>
    <a href="manage-hotel-listing.php" class="ds-link"><i class="bi bi-card-checklist"></i> Manage Hotel Listing</a>
    <a href="manage-rooms.php" class="ds-link"><i class="bi bi-door-open-fill"></i> Manage Rooms</a>
    <a href="view-ratings.php" class="ds-link"><i class="bi bi-star-fill"></i> View Ratings</a>
    <a href="transaction-history.php" class="ds-link"><i class="bi bi-cash-stack"></i> Transaction History</a>
    <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
  <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")});});</script>
  <div class="ds-foot">
    <a href="#" class="ds-hpill">
      <div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?php echo $manager_initials; ?></div>
      <div><div class="ds-hpill-name"><?php echo htmlspecialchars($manager_name); ?></div><div class="ds-hpill-status">&#9679; <?php echo htmlspecialchars($manager_role); ?></div></div>
    </a>
  </div>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div>
      <div class="ds-page-title">Check In Order</div>
      <div class="ds-breadcrumb">Dashboard / Check In &middot; <?php echo date('d M Y'); ?></div>
    </div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i></a>
    <div class="ds-avbtn" id="dsAvBtn">
      <div class="ds-av"><?php echo $manager_initials; ?></div>
      <span class="ds-avname d-none d-sm-block"><?php echo htmlspecialchars($manager_firstname ?: $manager_name); ?></span>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/>
        <a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">
<div class="admin-shell">
  <div class="row g-3 mb-2">
    <div class="col-6 col-xl-4">
      <div class="ds-stat blue">
        <div class="ds-si"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="ds-sn"><?php echo (int)$stats['today_arrivals']; ?></div>
        <div class="ds-sl">Today's Arrivals</div>
      </div>
    </div>
    <div class="col-6 col-xl-4">
      <div class="ds-stat green">
        <div class="ds-si"><i class="bi bi-person-check-fill"></i></div>
        <div class="ds-sn"><?php echo (int)$stats['currently_in']; ?></div>
        <div class="ds-sl">Currently Checked In</div>
      </div>
    </div>
    <div class="col-6 col-xl-4">
      <div class="ds-stat gold">
        <div class="ds-si"><i class="bi bi-door-open-fill"></i></div>
        <div class="ds-sn"><?php echo (int)$stats['today_departures']; ?></div>
        <div class="ds-sl">Today's Departures</div>
      </div>
    </div>
  </div>

  <div class="ds-card">
    <div class="ds-ch">
      <div class="ds-ct"><i class="bi bi-person-check-fill"></i>
        <?php echo $active_tab === 'checked_in' ? 'Currently Checked In' : "Today's Check-ins"; ?>
        <span style="font-size:.78rem;font-weight:500;color:var(--mut);background:var(--srf);padding:.15rem .55rem;border-radius:6px;"><?php echo count($bookings); ?></span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="?tab=today" class="tab-pill <?php echo $active_tab !== 'checked_in' ? 'active' : ''; ?>">
          <i class="bi bi-calendar-event"></i> Today's Arrivals
        </a>
        <a href="?tab=checked_in" class="tab-pill <?php echo $active_tab === 'checked_in' ? 'active' : ''; ?>">
          <i class="bi bi-person-check"></i> In-house Guests
        </a>
        <button onclick="location.reload()" class="ds-btn gho sm" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
      </div>
    </div>
    <div class="ds-cb">
      <?php if (empty($bookings)): ?>
        <div class="empty-state">
          <i class="bi bi-calendar-x"></i>
          <div class="fw-700 mt-3" style="color:#334155;font-size:1.05rem;">
            <?php echo $active_tab === 'checked_in' ? 'No guests currently checked in' : 'No arrivals scheduled for today'; ?>
          </div>
          <div class="text-muted small mt-2">
            <?php echo $active_tab === 'checked_in'
                ? 'All checked-in guests will appear here under In-house Guests.'
                : 'Confirmed & Paid guests with check-in date due on or before ' . date('d M Y') . ' will appear here.'; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($bookings as $b):
            $initials = strtoupper(substr($b['guest_name'], 0, 1));
            $ci_date = date('d M Y', strtotime($b['checkin_date']));
            $co_date = date('d M Y', strtotime($b['checkout_date']));
            $status  = strtolower($b['booking_status']);
            $is_today_checkout = ($b['checkout_date'] === $today);
            $badge_map = [
              'confirmed'   => ['class'=>'confirmed',  'label'=>'Confirmed'],
              'pending'     => ['class'=>'pending',    'label'=>'Pending'],
              'checked_in'  => ['class'=>'checkin',    'label'=>'Checked In'],
              'checked_out' => ['class'=>'checkout',   'label'=>'Completed'],
              'cancelled'   => ['class'=>'cancelled',  'label'=>'Cancelled'],
            ];
            $badge = $badge_map[$status] ?? ['class'=>'pending','label'=>ucfirst($status)];
          ?>
          <div class="col-12 col-lg-6">
            <div class="guest-card" id="card-<?php echo htmlspecialchars($b['booking_id']); ?>">
              <div class="loading-overlay" id="loader-<?php echo htmlspecialchars($b['booking_id']); ?>">
                <div class="spinner-ring"></div>
              </div>
              <div class="d-flex align-items-start gap-3">
                <div class="guest-av"><?php echo $initials; ?></div>
                <div class="flex-grow-1" style="min-width:0">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="fw-700" style="font-size:.97rem;"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                      <div class="detail-row"><i class="bi bi-hash"></i><?php echo htmlspecialchars($b['booking_id']); ?></div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <?php if ($is_today_checkout && $status === 'checked_in'): ?>
                        <span class="today-tag overdue-tag"><i class="bi bi-clock"></i> Checkout Today</span>
                      <?php elseif ($b['checkin_date'] <= $today && $status !== 'checked_in'): ?>
                        <span class="today-tag"><i class="bi bi-calendar-check"></i> Arriving Today</span>
                      <?php endif; ?>
                      <span class="ds-badge <?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                    </div>
                  </div>
                  <div class="row g-0 mt-2">
                    <div class="col-12 col-sm-6">
                      <div class="detail-row"><i class="bi bi-building"></i><?php echo htmlspecialchars($b['hotel_name']); ?></div>
                      <div class="detail-row"><i class="bi bi-door-closed"></i><?php echo htmlspecialchars($b['room_type']); ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="detail-row"><i class="bi bi-box-arrow-in-right"></i>Check-in: <?php echo $ci_date; ?></div>
                      <div class="detail-row"><i class="bi bi-box-arrow-right"></i>Check-out: <?php echo $co_date; ?></div>
                    </div>
                  </div>
                  <?php if ($b['guest_phone'] || $b['guest_email']): ?>
                  <div class="detail-row mt-1" style="flex-wrap:wrap;gap:.5rem">
                    <?php if ($b['guest_phone']): ?><span><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($b['guest_phone']); ?></span><?php endif; ?>
                    <?php if ($b['guest_email']): ?><span><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($b['guest_email']); ?></span><?php endif; ?>
                  </div>
                  <?php endif; ?>
                  <?php if ($b['guests'] || $b['nights']): ?>
                  <div class="detail-row">
                    <i class="bi bi-people-fill"></i><?php echo (int)$b['guests']; ?> Guest<?php echo $b['guests'] != 1 ? 's' : ''; ?>
                    &nbsp;&middot;&nbsp;<?php echo (int)$b['nights']; ?> Night<?php echo $b['nights'] != 1 ? 's' : ''; ?>
                    &nbsp;&middot;&nbsp;<span class="fw-600" style="color:var(--txt);">&#8377;<?php echo number_format((float)$b['total_amount']); ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="action-group">
                    <?php if (in_array($status, ['confirmed','pending'])): ?>
                      <button class="btn-checkin" onclick="doAction('mark_checked_in','<?php echo htmlspecialchars($b['booking_id']); ?>')">
                        <i class="bi bi-check-circle-fill"></i> Mark as Checked In
                      </button>
                    <?php elseif ($status === 'checked_in'): ?>
                      <button class="btn-checkout" onclick="doAction('mark_checked_out','<?php echo htmlspecialchars($b['booking_id']); ?>')">
                        <i class="bi bi-box-arrow-right"></i> Check Out
                      </button>
                    <?php else: ?>
                      <div class="btn-done"><i class="bi bi-check2-all"></i> Completed</div>
                    <?php endif; ?>
                    <?php if (!empty($b['special_requests'])): ?>
                      <button class="ds-btn gho sm" onclick="showRequests('<?php echo htmlspecialchars(addslashes($b['special_requests']), ENT_QUOTES); ?>')" title="Special Requests">
                        <i class="bi bi-chat-dots-fill"></i> Requests
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</main>

<!-- Special Requests Modal -->
<div class="modal fade ds-modal" id="reqModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-chat-dots-fill me-2"></i>Special Requests</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="reqModalBody"></div>
    </div>
  </div>
</div>

<div class="ds-toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="dashboard.js"></script>
<script>
console.log('[Check-In Order] Manager ID: <?php echo $manager_id; ?> | Active Tab: "<?php echo htmlspecialchars($active_tab); ?>" | Rows Returned: <?php echo $rows_returned; ?>');
console.log('[Check-In Order] SQL Executed:\n<?php echo str_replace(["\r", "\n"], ' ', $sql); ?>');

function doAction(action, bookingId) {
  console.log(`[Check-In Workflow] Step 1: Action triggered -> ${action} for Booking ID: ${bookingId}`);

  const card   = document.getElementById('card-' + bookingId);
  const loader = document.getElementById('loader-' + bookingId);
  const label  = action === 'mark_checked_in' ? 'Check In' : 'Check Out';

  if (!confirm('Confirm ' + label + ' for booking ' + bookingId + '?')) {
    console.log('[Check-In Workflow] Action cancelled by user.');
    return;
  }

  if (loader) loader.style.display = 'flex';

  console.log('[Check-In Workflow] Step 2: Preparing POST request to check-in-order.php...');
  const postData = 'action=' + encodeURIComponent(action) + '&booking_id=' + encodeURIComponent(bookingId);

  console.log('[Check-In Workflow] Step 3: Fetching endpoint check-in-order.php with payload:', postData);

  fetch('check-in-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: postData
  })
  .then(async r => {
    console.log(`[Check-In Workflow] Step 4: HTTP Response Status = ${r.status}`);
    const text = await r.text();
    let data;
    try {
      data = JSON.parse(text);
      console.log('[Check-In Workflow] Step 5: Successfully parsed JSON response:', data);
    } catch (e) {
      console.error('[Check-In Workflow] ERROR: Server returned non-JSON response:', text);
      throw new Error(text.replace(/<[^>]*>/g, '').trim() || 'Server returned invalid non-JSON response.');
    }

    if (!r.ok && data && (data.message || data.error)) {
      throw new Error(data.message || data.error);
    }
    return data;
  })
  .then(d => {
    if (loader) loader.style.display = 'none';

    if (d.success) {
      console.log('[Check-In Workflow] Step 6: Database update succeeded! Updating UI in real-time...');
      const msg = action === 'mark_checked_in'
        ? 'Guest checked in successfully! Booking moved to In-house Guests.'
        : 'Guest checked out successfully! Room is now Available.';
      showToast(msg, 'success');

      if (action === 'mark_checked_in') {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'today';

        if (currentTab !== 'checked_in') {
          // On Arrivals tab: smoothly transition card out
          if (card) {
            const col = card.closest('.col-12');
            col.style.transition = 'all 0.4s ease';
            col.style.opacity = '0';
            col.style.transform = 'translateY(-10px)';
            setTimeout(() => {
              col.remove();
              checkEmptyState();
            }, 400);
          }
        } else {
          // On In-house Guests tab: update badge & action button
          if (card) {
            const badge = card.querySelector('.ds-badge');
            if (badge) {
              badge.className = 'ds-badge checkin';
              badge.textContent = 'Checked In';
            }
            const btnGroup = card.querySelector('.action-group');
            if (btnGroup) {
              const oldBtn = btnGroup.querySelector('.btn-checkin');
              if (oldBtn) {
                oldBtn.className = 'btn-checkout';
                oldBtn.setAttribute('onclick', `doAction('mark_checked_out','${bookingId}')`);
                oldBtn.innerHTML = '<i class="bi bi-box-arrow-right"></i> Check Out';
              }
            }
          }
        }
        updateStatCounters(1, 1, 0);
      } else if (action === 'mark_checked_out') {
        if (card) {
          const col = card.closest('.col-12');
          col.style.transition = 'all 0.4s ease';
          col.style.opacity = '0';
          col.style.transform = 'translateY(-10px)';
          setTimeout(() => {
            col.remove();
            checkEmptyState();
          }, 400);
        }
        updateStatCounters(0, -1, 0);
      }
    } else {
      console.error('[Check-In Workflow] ERROR: Backend returned failure state:', d.message || d.error);
      showToast(d.message || d.error || 'Action failed.', 'error');
    }
  })
  .catch(err => {
    console.error('[Check-In Workflow] ERROR caught during execution:', err);
    if (loader) loader.style.display = 'none';
    showToast(err.message || 'An error occurred during check-in.', 'error');
  });
}

function updateStatCounters(arrDelta = 0, inDelta = 0, depDelta = 0) {
  const statNums = document.querySelectorAll('.ds-sn');
  if (statNums[0] && arrDelta !== 0) {
    let v = parseInt(statNums[0].textContent) || 0;
    statNums[0].textContent = Math.max(0, v - arrDelta);
  }
  if (statNums[1] && inDelta !== 0) {
    let v = parseInt(statNums[1].textContent) || 0;
    statNums[1].textContent = Math.max(0, v + inDelta);
  }
  if (statNums[2] && depDelta !== 0) {
    let v = parseInt(statNums[2].textContent) || 0;
    statNums[2].textContent = Math.max(0, v + depDelta);
  }
}

function checkEmptyState() {
  const container = document.querySelector('.ds-cb .row.g-3');
  if (container && container.children.length === 0) {
    container.parentElement.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-calendar-x"></i>
        <div class="fw-700 mt-3" style="color:#334155;font-size:1.05rem;">No bookings remaining in this view</div>
        <div class="text-muted small mt-2">All guests processed successfully.</div>
      </div>`;
  }
}

function showRequests(text) {
  document.getElementById('reqModalBody').textContent = text;
  new bootstrap.Modal(document.getElementById('reqModal')).show();
}

function showToast(msg, type) {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'ds-toast ' + type;
  t.innerHTML = '<i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i>' + msg;
  wrap.appendChild(t);
  requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 3500);
}
</script>
</body>
</html>