<?php
require_once 'db.php';
require_once 'auth_guard.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transaction History | Hotel Operations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
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
        <div class="ds-page-title">Transaction History</div>
        <div class="ds-breadcrumb">View all booking payments and revenue</div>
      </div>
    </div>
    <div class="ds-top-r">
      <div class="ds-avbtn" id="dsAvBtn">
        <div class="ds-av"><?= $manager_initials ?></div>
        <span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
      </div>
    </div>
  </header>

  <main class="ds-main">
    <div class="admin-shell">
      
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4"><div class="ds-stat purple"><div class="ds-si"><i class="bi bi-cash-stack"></i></div><div class="ds-sn">₹ 124,500</div><div class="ds-sl">Total Revenue (This Month)</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+12% vs last month</div></div></div>
        <div class="col-12 col-md-4"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div><div class="ds-sn">142</div><div class="ds-sl">Successful Transactions</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>All paid bookings</div></div></div>
        <div class="col-12 col-md-4"><div class="ds-stat orange"><div class="ds-si"><i class="bi bi-clock-history"></i></div><div class="ds-sn">₹ 12,400</div><div class="ds-sl">Pending Payments</div><div class="ds-tr down"><i class="bi bi-arrow-down-short"></i>Awaiting clearance</div></div></div>
      </div>

      <div class="ds-card">
        <div class="ds-ch">
          <div class="ds-ct"><i class="bi bi-card-list"></i> Recent Transactions</div>
          <div class="d-flex gap-2">
             <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i><input class="ds-inp search" placeholder="Search ID or Name" style="width:180px" /></div>
             <select class="ds-inp" style="width: auto;"><option>All Status</option><option>Paid</option><option>Pending</option></select>
          </div>
        </div>
        <div class="ds-cb">
          <div style="overflow-x:auto;">
            <table class="ds-tbl">
              <thead><tr><th>Booking ID</th><th>Guest Name</th><th>Date & Time</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr>
                   <td><div class="fw-700">#BKG-1029</div></td>
                   <td>Ravi Kumar</td>
                   <td><div class="small">16 Jun 2026<br><span class="text-muted">14:30 PM</span></div></td>
                   <td><div class="fw-700 text-success">₹ 12,450</div></td>
                   <td><span class="ds-badge confirmed">Paid</span></td>
                   <td><button class="ds-btn outl sm"><i class="bi bi-download"></i> Receipt</button></td>
                </tr>
                <tr>
                   <td><div class="fw-700">#BKG-1030</div></td>
                   <td>Neha Singh</td>
                   <td><div class="small">15 Jun 2026<br><span class="text-muted">09:15 AM</span></div></td>
                   <td><div class="fw-700 text-success">₹ 8,200</div></td>
                   <td><span class="ds-badge confirmed">Paid</span></td>
                   <td><button class="ds-btn outl sm"><i class="bi bi-download"></i> Receipt</button></td>
                </tr>
                <tr>
                   <td><div class="fw-700">#BKG-1031</div></td>
                   <td>Aman Verma</td>
                   <td><div class="small">15 Jun 2026<br><span class="text-muted">18:45 PM</span></div></td>
                   <td><div class="fw-700 text-muted">₹ 4,500</div></td>
                   <td><span class="ds-badge pending">Pending</span></td>
                   <td><button class="ds-btn outl sm" disabled><i class="bi bi-download"></i> Receipt</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="dashboard.js"></script>
</body>
</html>

