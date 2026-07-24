<?php
require_once 'auth_guard.php';
$pageTitle = 'Platform Admin Dashboard';
$pageSubtitle = 'Marketplace oversight · users, hotels, and governance';
include 'partials/header.php';
?>
<section class="hero-card">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
    <div>
      <span class="hero-pill"><i class="bi bi-shield-check"></i> Platform Administration Console</span>
      <h1 class="hero-title">Welcome back, <?= htmlspecialchars($admin['first_name']) ?>. BookHotel is scaling smoothly across every market.</h1>
      <p class="hero-sub">Oversee users, hotel managers, property listings, bookings, and business health from one central command center.</p>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <a href="bookings.php" class="ds-btn prim"><i class="bi bi-check2-circle"></i> Review Pending Approvals</a>
        <a href="users.php" class="ds-btn outl"><i class="bi bi-people-fill"></i> Manage Users</a>
      </div>
    </div>
    <div class="hero-metrics">
      <div class="hero-metric"><span>Live</span><strong>98.7%</strong></div>
      <div class="hero-metric"><span>Pending</span><strong>11 actions</strong></div>
      <div class="hero-metric"><span>Security</span><strong>Protected</strong></div>
    </div>
  </div>
</section>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-people-fill"></i></div><div class="ds-sn">3,248</div><div class="ds-sl">Total Users</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+12.4% this month</div></div></div>
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-building"></i></div><div class="ds-sn">186</div><div class="ds-sl">Total Hotels</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+8 new properties</div></div></div>
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat purple"><div class="ds-si"><i class="bi bi-person-badge-fill"></i></div><div class="ds-sn">64</div><div class="ds-sl">Hotel Managers</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>4 awaiting approval</div></div></div>
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-calendar2-check-fill"></i></div><div class="ds-sn">1,208</div><div class="ds-sl">Total Bookings</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+6.8% weekly</div></div></div>
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-currency-dollar"></i></div><div class="ds-sn">₹42.6L</div><div class="ds-sl">Monthly Revenue</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+18% vs last month</div></div></div>
  <div class="col-12 col-sm-6 col-xl-4"><div class="ds-stat orange"><div class="ds-si"><i class="bi bi-hourglass-split"></i></div><div class="ds-sn">11</div><div class="ds-sl">Pending Actions</div><div class="ds-tr down"><i class="bi bi-arrow-down-short"></i>Needs attention today</div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-xl-8">
    <div class="ds-card">
      <div class="ds-ch">
        <div class="ds-ct"><i class="bi bi-graph-up-arrow"></i> Revenue Overview</div>
        <select class="ds-inp ds-sel" style="width:auto;font-size:.8rem;padding:.35rem .75rem"><option>2026</option><option>2025</option></select>
      </div>
      <div class="ds-cb"><div class="ds-chart"><canvas id="revChart" height="260"></canvas></div></div>
    </div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="ds-card h-100">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-pie-chart-fill"></i> Booking Statistics</div></div>
      <div class="ds-cb">
        <div class="ds-chart" style="min-height:220px"><canvas id="occChart" height="220"></canvas></div>
        <div class="row g-2 mt-2 text-center">
          <div class="col-4"><div class="mini-value blue">812</div><div class="mini-label">Confirmed</div></div>
          <div class="col-4"><div class="mini-value green">298</div><div class="mini-label">Checked-in</div></div>
          <div class="col-4"><div class="mini-value gold">98</div><div class="mini-label">Pending</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-lg-7">
    <div class="ds-card">
      <div class="ds-ch">
        <div class="ds-ct"><i class="bi bi-collection"></i> Core Admin Screens</div>
        <a href="#" class="ds-btn gho sm">View All Modules</a>
      </div>
      <div class="ds-cb">
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-xl-4"><a href="users.php" class="module-card"><i class="bi bi-people-fill"></i><strong>Users</strong><span>View all accounts, search, filter, block or delete users.</span></a></div>
          <div class="col-12 col-sm-6 col-xl-4"><a href="managers.php" class="module-card"><i class="bi bi-person-badge-fill"></i><strong>Managers</strong><span>Approve, suspend, and inspect hotel managers in one place.</span></a></div>
          <div class="col-12 col-sm-6 col-xl-4"><a href="hotels.php" class="module-card"><i class="bi bi-building"></i><strong>Hotels</strong><span>Manage listings, approve or reject hotels, and update status.</span></a></div>
          <div class="col-12 col-sm-6 col-xl-4"><a href="bookings.php" class="module-card"><i class="bi bi-calendar2-check-fill"></i><strong>Bookings</strong><span>Track reservations, update status, and handle cancellations.</span></a></div>
          <div class="col-12 col-sm-6 col-xl-4"><a href="settings.php" class="module-card"><i class="bi bi-sliders"></i><strong>Settings</strong><span>Manage website preferences, contact details and policies.</span></a></div>
          <div class="col-12 col-sm-6 col-xl-4"><a href="profile.php" class="module-card"><i class="bi bi-person-circle"></i><strong>Profile</strong><span>Update personal information, password and security preferences.</span></a></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-5">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-clock-history"></i> Recent Activities</div><a href="#" class="ds-btn gho sm">See All</a></div>
      <div class="ds-cb">
        <div class="activity-item"><div class="activity-icon blue"><i class="bi bi-person-plus"></i></div><div><div class="activity-title">New hotel manager approved</div><div class="activity-sub">Riya Kapoor was approved for Grand Horizons</div><div class="activity-time">12 mins ago</div></div></div>
        <div class="activity-item"><div class="activity-icon green"><i class="bi bi-building-check"></i></div><div><div class="activity-title">Hotel listing published</div><div class="activity-sub">Blue Peak Retreat is now live in the marketplace</div><div class="activity-time">45 mins ago</div></div></div>
        <div class="activity-item"><div class="activity-icon gold"><i class="bi bi-calendar2-check-fill"></i></div><div><div class="activity-title">New booking confirmed</div><div class="activity-sub">Rohan Gupta booked The Grand Palace for 3 nights</div><div class="activity-time">1 hour ago</div></div></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-8">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-table"></i> Pending Approvals</div><button class="ds-btn gho sm" data-bs-toggle="modal" data-bs-target="#approveModal">Open Queue</button></div>
      <div style="overflow-x:auto">
        <table class="ds-tbl">
          <thead><tr><th>Item</th><th>Submitted</th><th>Type</th><th>Status</th></tr></thead>
          <tbody>
            <tr><td class="fw-600">Aarav Mehta</td><td>Today · 09:40</td><td>Hotel Manager</td><td><span class="ds-badge pending"><i class="bi bi-hourglass-split"></i>Pending</span></td></tr>
            <tr><td class="fw-600">Sea Breeze Resort</td><td>Today · 11:15</td><td>Hotel Listing</td><td><span class="ds-badge new"><i class="bi bi-bell"></i>Review</span></td></tr>
            <tr><td class="fw-600">Rohan Dutta</td><td>Yesterday</td><td>Booking Dispute</td><td><span class="ds-badge checkin"><i class="bi bi-info-circle"></i>Needs Action</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-speedometer2"></i> Platform Health</div></div>
      <div class="ds-cb">
        <div class="health-row"><span>Availability</span><strong>99.9%</strong></div>
        <div class="health-row"><span>Response Time</span><strong>320ms</strong></div>
        <div class="health-row"><span>Operational Load</span><strong>76%</strong></div>
        <div class="ds-prog mt-3"><div class="ds-progf" style="width:76%;background:linear-gradient(90deg,var(--pr),#38bdf8)"></div></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade ds-modal" id="approveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Approve Pending Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">This premium modal layout is ready for manager approvals, hotel reviews, or booking actions.</p>
      </div>
      <div class="modal-footer">
        <button class="ds-btn gho sm" data-bs-dismiss="modal">Close</button>
        <button class="ds-btn prim sm">Approve</button>
      </div>
    </div>
  </div>
</div>
<?php
require_once 'auth_guard.php'; include 'partials/footer.php'; ?>
