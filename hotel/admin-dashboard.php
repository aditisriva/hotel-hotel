<?php
require_once 'db.php';
require_once 'auth_guard.php';
require_once 'hotel_functions.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

// Assuming logged in manager manages hotel_id = 1
$hotel_id = 1;

// Fetch hotel info
$stmt = mysqli_prepare($conn, "SELECT hotel_name, city FROM hotels WHERE hotel_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
mysqli_stmt_execute($stmt);
$hotel_res = mysqli_stmt_get_result($stmt);
$hotel_info = mysqli_fetch_assoc($hotel_res);
mysqli_stmt_close($stmt);

$hotel_name = $hotel_info ? $hotel_info['hotel_name'] : 'My Hotel';
$hotel_city = $hotel_info ? ucfirst($hotel_info['city']) : 'Unknown';

// Total bookings
$b_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE hotel_id = ?");
mysqli_stmt_bind_param($b_stmt, 'i', $hotel_id);
mysqli_stmt_execute($b_stmt);
$b_res = mysqli_stmt_get_result($b_stmt);
$booking_count = $b_res ? (int)mysqli_fetch_assoc($b_res)['cnt'] : 0;
mysqli_stmt_close($b_stmt);

// Total rooms
$r_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM rooms WHERE hotel_id = ?");
mysqli_stmt_bind_param($r_stmt, 'i', $hotel_id);
mysqli_stmt_execute($r_stmt);
$r_res = mysqli_stmt_get_result($r_stmt);
$total_rooms = $r_res ? (int)mysqli_fetch_assoc($r_res)['cnt'] : 0;
mysqli_stmt_close($r_stmt);

// Occupancy (assuming rooms table has 'status' = 'Occupied')
$occ_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM rooms WHERE hotel_id = ? AND status = 'Occupied'");
mysqli_stmt_bind_param($occ_stmt, 'i', $hotel_id);
mysqli_stmt_execute($occ_stmt);
$occ_res = mysqli_stmt_get_result($occ_stmt);
$occupied_rooms = $occ_res ? (int)mysqli_fetch_assoc($occ_res)['cnt'] : 0;
mysqli_stmt_close($occ_stmt);

$occupancy_rate = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

// Revenue (total_amount from paid bookings)
$rev_stmt = mysqli_prepare($conn, "SELECT SUM(total_amount) as total FROM bookings WHERE hotel_id = ? AND payment_status = 'paid'");
mysqli_stmt_bind_param($rev_stmt, 'i', $hotel_id);
mysqli_stmt_execute($rev_stmt);
$rev_res = mysqli_stmt_get_result($rev_stmt);
$total_revenue = $rev_res ? (float)mysqli_fetch_assoc($rev_res)['total'] : 0;
mysqli_stmt_close($rev_stmt);

// Check-ins today
$ci_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM bookings WHERE hotel_id = ? AND checkin_date = CURDATE()");
mysqli_stmt_bind_param($ci_stmt, 'i', $hotel_id);
mysqli_stmt_execute($ci_stmt);
$ci_res = mysqli_stmt_get_result($ci_stmt);
$checkins_today = $ci_res ? (int)mysqli_fetch_assoc($ci_res)['cnt'] : 0;
mysqli_stmt_close($ci_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BookHotel | Hotel Operations Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
  <div class="ds-ov" id="dsOv"></div>
  <aside class="ds-sb" id="dsSb">
    <a href="admin-dashboard.php" class="ds-logo">
      <div class="ds-logo-icon"><i class="bi bi-buildings"></i></div>
      <div>
        <div class="ds-logo-name">BookHotel</div>
        <div class="ds-logo-role">Hotel Operations</div>
      </div>
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
    <div class="ds-foot">
      <a href="admin-hotel-profile.php" class="ds-hpill">
        <div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"><?= $manager_initials ?></div>
        <div>
          <div class="ds-hpill-name"><?= htmlspecialchars($manager_name) ?></div>
          <div class="ds-hpill-status">● <?= htmlspecialchars($manager_role) ?></div>
        </div>
      </a>
    </div>
  </aside>

  <header class="ds-top">
    <div class="ds-top-l">
      <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
      <div>
        <div class="ds-page-title">Hotel Operations Dashboard</div>
        <div class="ds-breadcrumb">Property control center · rooms, stays, and guest care</div>
      </div>
    </div>
    <div class="ds-top-r">
      <div class="ds-sw d-none d-md-block">
        <i class="bi bi-search ds-si-ic"></i>
        <input class="ds-inp search" type="text" placeholder="Search rooms, bookings, guests" />
      </div>
      <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i><span class="ds-dot"></span></a>
        <div class="ds-avbtn" id="dsAvBtn">
          <div class="ds-av"><?= $manager_initials ?></div>
          <span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
          <i class="bi bi-chevron-down ms-1" style="font-size:.7rem;color:var(--mut)"></i>
        <div class="ds-dropdown" id="dsAvMenu">
          <a href="profile.php" class="ds-drop-item"><i class="bi bi-person-fill text-primary"></i> My Profile</a>
          <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
          <hr class="my-1 mx-2" />
          <a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <main class="ds-main">
    <div class="admin-shell">
      <section class="hero-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
          <div>
            <span class="hero-pill"><i class="bi bi-building-check"></i> Hotel Operations Console</span>
            <h1 class="hero-title">Welcome back. <?php
require_once 'auth_guard.php'; echo htmlspecialchars($hotel_name); ?> is running smoothly for today’s arrivals.</h1>
            <p class="hero-sub">Keep room readiness, guest stay quality, and revenue momentum on track from a refined property operations dashboard.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
              <a href="admin-bookings.php" class="ds-btn prim"><i class="bi bi-calendar2-check-fill"></i> View Today’s Arrivals</a>
              <a href="admin-rooms.php" class="ds-btn outl"><i class="bi bi-door-open-fill"></i> Open Room Board</a>
            </div>
          </div>
          <div class="hero-metrics">
            <div class="hero-metric"><span>Occupancy</span><strong>64%</strong></div>
            <div class="hero-metric"><span>Check-ins</span><strong>6 today</strong></div>
            <div class="hero-metric"><span>Service</span><strong>On track</strong></div>
          </div>
        </div>
      </section>

      <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-door-open-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo $total_rooms; ?></div><div class="ds-sl">Total Rooms</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>Available inventory</div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-calendar2-check-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo $booking_count; ?></div><div class="ds-sl">Total Bookings</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>All time</div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-person-check-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo $checkins_today; ?></div><div class="ds-sl">Check-ins Today</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>Pending arrivals</div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat purple"><div class="ds-si"><i class="bi bi-cash-stack"></i></div><div class="ds-sn">₹<?php
require_once 'auth_guard.php'; echo number_format($total_revenue, 2); ?></div><div class="ds-sl">Total Revenue</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>From paid bookings</div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat orange"><div class="ds-si"><i class="bi bi-percent"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo $occupancy_rate; ?>%</div><div class="ds-sl">Occupancy Rate</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>Based on occupied rooms</div></div></div>
        <div class="col-12 col-sm-6 col-xl-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-bell-fill"></i></div><div class="ds-sn">0</div><div class="ds-sl">Pending Requests</div><div class="ds-tr down"><i class="bi bi-arrow-down-short"></i>0 urgent</div></div></div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
          <div class="ds-card">
            <div class="ds-ch">
              <div class="ds-ct"><i class="bi bi-graph-up-arrow"></i> Occupancy Trend</div>
              <span class="ds-badge confirmed"><i class="bi bi-fire"></i> Strong week</span>
            </div>
            <div class="ds-cb"><div class="ds-chart"><canvas id="revChart" height="260"></canvas></div></div>
          </div>
        </div>
        <div class="col-12 col-xl-4">
          <div class="ds-card h-100">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-broadcast"></i> Today’s Priority</div></div>
            <div class="ds-cb">
              <div class="activity-item"><div class="activity-icon blue"><i class="bi bi-door-open-fill"></i></div><div><div class="activity-title">Room housekeeping refresh</div><div class="activity-sub">4 rooms need turnaround before 3 PM</div></div></div>
              <div class="activity-item"><div class="activity-icon green"><i class="bi bi-person-check-fill"></i></div><div><div class="activity-title">Early arrivals</div><div class="activity-sub">2 VIP guests need welcome setup</div></div></div>
              <div class="activity-item"><div class="activity-icon gold"><i class="bi bi-chat-dots"></i></div><div><div class="activity-title">Guest review follow-up</div><div class="activity-sub">A recent stay needs a response</div></div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-lg-7">
          <div class="ds-card">
            <div class="ds-ch">
              <div class="ds-ct"><i class="bi bi-grid-3x3-gap-fill"></i> Hotel Operations Modules</div>
            </div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-rooms.php" class="module-card"><i class="bi bi-door-open-fill"></i><strong>Rooms</strong><span>Manage room inventory, availability, and maintenance.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-bookings.php" class="module-card"><i class="bi bi-calendar2-check-fill"></i><strong>Bookings</strong><span>Track stays, arrivals, departures, and confirmations.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-guests.php" class="module-card"><i class="bi bi-people-fill"></i><strong>Guests</strong><span>Monitor in-house guests and returning visitor profiles.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-reviews.php" class="module-card"><i class="bi bi-star-fill"></i><strong>Reviews</strong><span>Reply to guest feedback and protect brand experience.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-revenue.php" class="module-card"><i class="bi bi-bar-chart-fill"></i><strong>Revenue</strong><span>Review daily earnings, occupancy, and room performance.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="notifications.php" class="module-card"><i class="bi bi-bell-fill"></i><strong>Notifications</strong><span>Stay on top of service alerts, requests, and updates.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-hotel-profile.php" class="module-card"><i class="bi bi-building"></i><strong>Hotel Management</strong><span>Edit assigned hotel listing. All changes reflect on user site instantly.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="settings.php" class="module-card"><i class="bi bi-sliders"></i><strong>Settings</strong><span>Adjust hotel preferences, policies, and team access.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-availability.php" class="module-card"><i class="bi bi-calendar-range-fill"></i><strong>Availability</strong><span>Manage room availability and block dates.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-pricing.php" class="module-card"><i class="bi bi-tags-fill"></i><strong>Pricing</strong><span>Set base rates, weekend, and seasonal pricing.</span></a></div>
                <div class="col-12 col-sm-6 col-xl-4"><a href="admin-discounts.php" class="module-card"><i class="bi bi-percent"></i><strong>Discounts</strong><span>Create offers, promo codes, and special rates.</span></a></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
          <div class="ds-card">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-clock-history"></i> Service Snapshot</div></div>
            <div class="ds-cb">
              <div class="health-row"><span>Housekeeping</span><strong>On schedule</strong></div>
              <div class="health-row"><span>Check-in readiness</span><strong>92%</strong></div>
              <div class="health-row"><span>Guest requests</span><strong>8 open</strong></div>
              <div class="ds-prog mt-3"><div class="ds-progf" style="width:92%;background:linear-gradient(90deg,var(--grn),#2dd4bf)"></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" crossorigin="anonymous"></script>
  <script src="dashboard.js"></script>
  <script>renderRevenue('revChart');renderOcc('occChart');</script>
</body>
</html>



