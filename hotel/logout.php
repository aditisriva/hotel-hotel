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
  <title>Logout | Hotel Operations</title>
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
      <a href="logout.php" class="ds-link active"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
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
        <div class="ds-page-title">Logout Confirmation</div>
        <div class="ds-breadcrumb">Securely sign out of your account</div>
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
      <div class="ds-card text-center py-5" style="max-width: 500px; margin: 2rem auto;">
        <div class="ds-cb d-flex flex-column align-items-center">
           <div class="mb-4">
              <i class="bi bi-box-arrow-right text-danger" style="font-size: 4rem;"></i>
           </div>
           <h3 class="fw-800 mb-2">Ready to Leave?</h3>
           <p class="text-muted mb-4">Are you sure you want to log out of the Hotel Operations Panel? Any unsaved changes may be lost.</p>
           
           <div class="d-flex gap-3 w-100 px-4">
             <a href="admin-dashboard.php" class="ds-btn outl w-50 justify-content-center">Cancel</a>
             <a href="do-logout.php" class="ds-btn danger w-50 justify-content-center">Yes, Logout</a>
           </div>
        </div>
      </div>
    </div>
  </main>
  <script src="dashboard.js"></script>
</body>
</html>
