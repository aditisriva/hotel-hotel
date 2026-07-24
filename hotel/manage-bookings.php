<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';
require_once 'hotel_functions.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

// ── Handle status update ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_status') {
        $bid    = sanitize($_POST['booking_id'] ?? '');
        $status = sanitize($_POST['status'] ?? '');
        $valid  = ['pending','confirmed','checked_in','checked_out','cancelled'];
        if ($bid && in_array($status, $valid)) {
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET booking_status=? WHERE booking_id=?");
            mysqli_stmt_bind_param($stmt,'ss',$status,$bid);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success'=>$ok]);
        } else {
            echo json_encode(['success'=>false,'error'=>'Invalid input']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'toggle_booking_visibility') {
        $user_id = $_SESSION['hm_id'] ?? 0;
        $hotels = bhGetHotels('', 0, 0, 0, $user_id);
        $hotel = $hotels[0] ?? null;
        
        if ($hotel) {
            $new_status = $_POST['booking_enabled'] ? 'active' : 'inactive';
            $ok = bhUpdateHotel($hotel['hotel_id'], ['availability_status' => $new_status]);
            echo json_encode(['success' => $ok, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No hotel assigned']);
        }
        exit;
    }
}

// ── Fetch assigned hotel for visibility toggle ─────────────────────────────
$user_id = $_SESSION['hm_id'] ?? 0;
$hotels = bhGetHotels('', 0, 0, 0, $user_id);
$assigned_hotel = $hotels[0] ?? null;
$booking_enabled = $assigned_hotel ? ($assigned_hotel['availability_status'] ?? 'active') === 'active' : true;

// ── Fetch stats ───────────────────────────────────────────────────────────
$stats = ['total'=>0,'confirmed'=>0,'pending'=>0,'cancelled'=>0,'revenue'=>0];
$res = mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(booking_status='confirmed' OR booking_status='checked_in' OR booking_status='checked_out') AS confirmed,
    SUM(booking_status='pending') AS pending,
    SUM(booking_status='cancelled') AS cancelled,
    SUM(CASE WHEN booking_status!='cancelled' THEN total_amount ELSE 0 END) AS revenue
    FROM bookings");
if ($res) { $stats = array_merge($stats, mysqli_fetch_assoc($res)); }

// ── Filters ───────────────────────────────────────────────────────────────
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_search = isset($_GET['q'])      ? trim($_GET['q']) : '';
$where = ['1=1'];
if ($filter_status) $where[] = "b.booking_status = '" . mysqli_real_escape_string($conn,$filter_status) . "'";
if ($filter_search) {
    $fs = mysqli_real_escape_string($conn, $filter_search);
    $where[] = "(b.booking_id LIKE '%$fs%' OR b.guest_name LIKE '%$fs%' OR b.hotel_name LIKE '%$fs%' OR b.guest_email LIKE '%$fs%')";
}
$whereSQL = implode(' AND ', $where);

$bookings = [];
$res = mysqli_query($conn, "SELECT b.*, h.hotel_images FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.hotel_id WHERE $whereSQL ORDER BY b.created_at DESC LIMIT 200");
if ($res) while ($row = mysqli_fetch_assoc($res)) $bookings[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Booking Management – Hotel Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="dashboard.css"/>
</head>
<body>
<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo">
    <div class="ds-logo-icon"><i class="bi bi-buildings"></i></div>
    <div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Hotel Operations</div></div>
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
    <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div><div class="ds-page-title">Booking Management</div>
    <div class="ds-breadcrumb">Dashboard / Bookings · Live from Database</div></div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i></a>
    <div class="ds-avbtn" id="dsAvBtn">
      <div class="ds-av"><?= $manager_initials ?></div><span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/>
        <a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-calendar2-check-fill"></i></div>
    <div class="ds-sn"><?php
    require_once 'auth_guard.php'; echo (int)$stats['total']; ?></div><div class="ds-sl">Total Bookings</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div>
    <div class="ds-sn"><?php
    require_once 'auth_guard.php'; echo (int)$stats['confirmed']; ?></div><div class="ds-sl">Confirmed</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-hourglass-split"></i></div>
    <div class="ds-sn"><?php
    require_once 'auth_guard.php'; echo (int)$stats['pending']; ?></div><div class="ds-sl">Pending</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat purple"><div class="ds-si"><i class="bi bi-currency-rupee"></i></div>
    <div class="ds-sn">₹<?php
    require_once 'auth_guard.php'; echo number_format((float)$stats['revenue']); ?></div><div class="ds-sl">Total Revenue</div></div></div>
</div>

<!-- Booking Visibility Toggle -->
<?php if ($assigned_hotel): ?>
<div class="ds-card mb-4" id="visibilityCard">
  <div class="ds-cb">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="ds-si" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:<?php echo $booking_enabled ? '#dcfce7' : '#fee2e2'; ?>">
          <i class="bi bi-<?php echo $booking_enabled ? 'check-circle-fill' : 'x-circle-fill'; ?>" style="color:<?php echo $booking_enabled ? '#10b981' : '#ef4444'; ?>"></i>
        </div>
        <div>
          <div class="fw-700">Booking Visibility</div>
          <div class="text-muted small"><?php echo $booking_enabled ? 'Your property is accepting reservations. Users can view and book your rooms.' : 'Bookings are disabled. Your property is hidden from search results.'; ?></div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="small fw-600" style="color:<?php echo $booking_enabled ? '#10b981' : '#ef4444'; ?>">
          <?php echo $booking_enabled ? 'Enabled' : 'Disabled'; ?>
        </span>
        <div class="form-check form-switch" style="transform: scale(1.3);">
          <input class="form-check-input" type="checkbox" role="switch" id="bookingVisibilityToggle" <?php echo $booking_enabled ? 'checked' : ''; ?> style="cursor: pointer;">
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filters + Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-calendar2-check-fill me-2"></i>All Bookings (<?php
    require_once 'auth_guard.php'; echo count($bookings); ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search by ID, guest, hotel..." value="<?php
          require_once 'auth_guard.php'; echo htmlspecialchars($filter_search); ?>" style="width:220px"/>
        </div>
        <select class="ds-inp ds-sel" name="status" style="width:150px" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <?php
          require_once 'auth_guard.php'; foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
          <option value="<?php
          require_once 'auth_guard.php'; echo $s; ?>" <?php
          require_once 'auth_guard.php'; echo $filter_status===$s?'selected':''; ?>><?php
          require_once 'auth_guard.php'; echo ucwords(str_replace('_',' ',$s)); ?></option>
          <?php
          require_once 'auth_guard.php'; endforeach; ?>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="admin-bookings.php" class="ds-btn gho sm">Clear</a>
      </form>
    </div>
  </div>
  <div class="ds-cb p-0">
    <?php
    require_once 'auth_guard.php'; if (empty($bookings)): ?>
    <div class="text-center py-5">
      <i class="bi bi-calendar-x" style="font-size:3rem;color:#cbd5e1"></i>
      <div class="fw-700 mt-3" style="color:#64748b">No bookings found</div>
      <div class="text-muted small mt-1">
        <?php
        require_once 'auth_guard.php'; echo $filter_search||$filter_status ? 'Try clearing filters.' : 'Bookings will appear here once users complete a payment.'; ?>
      </div>
      <?php
      require_once 'auth_guard.php'; if ($filter_search||$filter_status): ?>
      <a href="admin-bookings.php" class="btn btn-outline-primary btn-sm mt-3">Clear Filters</a>
      <?php
      require_once 'auth_guard.php'; endif; ?>
    </div>
    <?php
    require_once 'auth_guard.php'; else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Guest</th>
            <th>Hotel</th>
            <th>Room</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Nights</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        require_once 'auth_guard.php'; foreach ($bookings as $b):
          $statusColors = [
            'confirmed'   => 'confirmed',
            'pending'     => 'pending',
            'checked_in'  => 'checkin',
            'checked_out' => 'checkout',
            'cancelled'   => 'cancelled',
          ];
          $statusColor = $statusColors[$b['booking_status']] ?? 'pending';
          $payColor    = $b['payment_status']==='paid' ? 'confirmed' : ($b['payment_status']==='failed'?'cancelled':'pending');
          $ci = date('d M Y', strtotime($b['checkin_date']));
          $co = date('d M Y', strtotime($b['checkout_date']));
        ?>
          <tr>
            <td><span class="fw-700 small" style="color:#1a56db"><?php
            require_once 'auth_guard.php'; echo htmlspecialchars($b['booking_id']); ?></span></td>
            <td>
              <div class="fw-600 small"><?php
              require_once 'auth_guard.php'; echo htmlspecialchars($b['guest_name']); ?></div>
              <div class="text-muted" style="font-size:.72rem"><?php
              require_once 'auth_guard.php'; echo htmlspecialchars($b['guest_email']); ?></div>
            </td>
            <td>
              <div class="fw-600 small"><?php
              require_once 'auth_guard.php'; echo htmlspecialchars($b['hotel_name']); ?></div>
              <div class="text-muted" style="font-size:.72rem"><?php
              require_once 'auth_guard.php'; echo htmlspecialchars(ucfirst($b['hotel_city']??'')); ?></div>
            </td>
            <td class="small"><?php
            require_once 'auth_guard.php'; echo htmlspecialchars($b['room_type']); ?></td>
            <td class="small fw-600"><?php
            require_once 'auth_guard.php'; echo $ci; ?></td>
            <td class="small fw-600"><?php
            require_once 'auth_guard.php'; echo $co; ?></td>
            <td class="text-center small"><?php
            require_once 'auth_guard.php'; echo (int)$b['nights']; ?></td>
            <td class="fw-700 small">₹<?php
            require_once 'auth_guard.php'; echo number_format((float)$b['total_amount']); ?></td>
            <td><span class="ds-badge <?php
            require_once 'auth_guard.php'; echo $payColor; ?>"><?php
            require_once 'auth_guard.php'; echo ucfirst($b['payment_status']); ?></span></td>
            <td>
              <select class="ds-inp ds-sel" style="font-size:.75rem;padding:2px 8px;min-width:120px"
                onchange="updateStatus('<?php
                require_once 'auth_guard.php'; echo $b['booking_id']; ?>',this.value,this)">
                <?php
                require_once 'auth_guard.php'; foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
                <option value="<?php
                require_once 'auth_guard.php'; echo $s; ?>" <?php
                require_once 'auth_guard.php'; echo $b['booking_status']===$s?'selected':''; ?>><?php
                require_once 'auth_guard.php'; echo ucwords(str_replace('_',' ',$s)); ?></option>
                <?php
                require_once 'auth_guard.php'; endforeach; ?>
              </select>
            </td>
            <td>
              <button class="ds-btn gho sm" onclick="viewBooking(<?php
              require_once 'auth_guard.php'; echo htmlspecialchars(json_encode($b)); ?>)">
                <i class="bi bi-eye-fill"></i>
              </button>
            </td>
          </tr>
        <?php
        require_once 'auth_guard.php'; endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    require_once 'auth_guard.php'; endif; ?>
  </div>
</div>

</main>

<!-- Detail Modal -->
<div class="modal fade ds-modal" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Booking Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="detailBody"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
<script>
function updateStatus(bookingId, status, el) {
  fetch('admin-bookings.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=update_status&booking_id='+encodeURIComponent(bookingId)+'&status='+encodeURIComponent(status)
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) dsToast('Status updated to ' + status.replace('_',' '), 'success');
    else { dsToast('Update failed', 'error'); }
  });
}

function viewBooking(b) {
  const fmt = s => s ? new Date(s).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—';
  document.getElementById('detailBody').innerHTML = `
    <div class="row g-3">
      <div class="col-md-6"><div class="ds-lbl">Booking ID</div><div class="fw-700 text-primary">${b.booking_id}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Booked On</div><div>${fmt(b.created_at)}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Guest Name</div><div class="fw-600">${b.guest_name}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Guest Email</div><div>${b.guest_email}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Guest Phone</div><div>${b.guest_phone||'—'}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Guests</div><div>${b.guests}</div></div>
      <div class="col-12"><hr/></div>
      <div class="col-md-6"><div class="ds-lbl">Hotel</div><div class="fw-700">${b.hotel_name}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Room Type</div><div class="fw-600">${b.room_type}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Check-in</div><div class="fw-600">${fmt(b.checkin_date)}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Check-out</div><div class="fw-600">${fmt(b.checkout_date)}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Nights</div><div>${b.nights}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Payment Method</div><div>${b.payment_method||'—'}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Payment Status</div><div>${b.payment_status}</div></div>
      <div class="col-12"><hr/></div>
      <div class="col-md-3"><div class="ds-lbl">Base Amount</div><div>₹${Number(b.base_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Discount</div><div class="text-success">−₹${Number(b.discount_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Tax</div><div>₹${Number(b.tax_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Total Paid</div><div class="fw-800 text-primary">₹${Number(b.total_amount).toLocaleString('en-IN')}</div></div>
      ${b.special_requests ? `<div class="col-12"><div class="ds-lbl">Special Requests</div><div>${b.special_requests}</div></div>` : ''}
    </div>`;
  new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Booking visibility toggle
document.addEventListener('DOMContentLoaded', function() {
  const toggle = document.getElementById('bookingVisibilityToggle');
  if (!toggle) return;
  
  let timeout;
  toggle.addEventListener('change', function() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      const formData = new FormData();
      formData.append('action', 'toggle_booking_visibility');
      formData.append('booking_enabled', this.checked ? '1' : '0');
      
      fetch('admin-bookings.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          dsToast('Booking visibility updated', 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          dsToast('Update failed: ' + (d.error || 'Unknown error'), 'error');
          this.checked = !this.checked;
        }
      })
      .catch(() => {
        dsToast('Network error', 'error');
        this.checked = !this.checked;
      });
    }, 300);
  });
});
</script>
</body>
</html>
