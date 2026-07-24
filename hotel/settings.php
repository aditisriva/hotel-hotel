<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';

$user_id = $_SESSION['hm_id'] ?? 0;
$manager = ['first_name'=>'', 'last_name'=>'', 'email'=>'', 'mobile'=>'', 'role'=>'hotel_manager'];
$msg = '';
$msg_type = '';

if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT first_name, last_name, email, mobile, role FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $manager = mysqli_fetch_assoc($res) ?: $manager;
    mysqli_stmt_close($stmt);
}

$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        
        if ($first_name && $email) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, last_name=?, email=?, mobile=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $first_name, $last_name, $email, $mobile, $user_id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = $ok ? 'Profile updated successfully!' : 'Error updating profile.';
            $msg_type = $ok ? 'success' : 'danger';
            
            if ($ok) {
                $manager['first_name'] = $first_name;
                $manager['last_name'] = $last_name;
                $manager['email'] = $email;
                $manager['mobile'] = $mobile;
                $_SESSION['hm_firstname'] = $first_name . ' ' . $last_name;
            }
        } else {
            $msg = 'Name and email are required.';
            $msg_type = 'warning';
        }
    }
    
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!$current || !$new || !$confirm) {
            $msg = 'All password fields are required.';
            $msg_type = 'warning';
        } elseif ($new !== $confirm) {
            $msg = 'New passwords do not match.';
            $msg_type = 'warning';
        } elseif (strlen($new) < 8) {
            $msg = 'Password must be at least 8 characters.';
            $msg_type = 'warning';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            
            if ($row && password_verify($current, $row['password'])) {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'si', $hash, $user_id);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = $ok ? 'Password updated successfully!' : 'Error updating password.';
                $msg_type = $ok ? 'success' : 'danger';
            } else {
                $msg = 'Current password is incorrect.';
                $msg_type = 'danger';
            }
        }
    }
    
    if ($action === 'update_notifications') {
        $notif = isset($_POST['booking_notif']) ? 1 : 0;
        $mkt = isset($_POST['marketing_notif']) ? 1 : 0;
        $upd = isset($_POST['update_notif']) ? 1 : 0;
        $msg = 'Notification preferences saved!';
        $msg_type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Settings — Hotel Operations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
</head>
<body>
<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo">
    <div class="ds-logo-icon"><i class="bi bi-building-fill"></i></div>
    <div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Manager Portal</div></div>
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
    <div class="ds-sec">Account</div>
    <a href="settings.php" class="ds-link active"><i class="bi bi-sliders"></i> Settings</a>
    <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
  <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script>
  <div class="ds-foot">
    <a href="manage-hotel-listing.php" class="ds-hpill">
      <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=80&q=80" alt=""/>
      <div><div class="ds-hpill-name"><?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></div><div class="ds-hpill-status"><?= htmlspecialchars($manager_role) ?></div></div>
    </a>
  </div>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div><div class="ds-page-title">Settings</div><div class="ds-breadcrumb">Dashboard / Account Settings</div></div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i><span class="ds-dot"></span></a>
    <div class="ds-avbtn" id="dsAvBtn">
      <div class="ds-av"><?php echo strtoupper(substr($manager['first_name'] ?? 'M',0,1)); ?></div>
      <span class="ds-avname d-none d-sm-block"><?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></span>
      <i class="bi bi-chevron-down ms-1" style="font-size:.7rem;color:var(--mut)"></i>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="profile.php" class="ds-drop-item"><i class="bi bi-person-fill text-primary"></i> My Profile</a>
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/>
        <a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">
  <?php if ($msg): ?>
  <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : ($msg_type === 'danger' ? 'danger' : 'warning'); ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-12 col-xl-4">
      <div class="ds-card h-100">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-person-fill me-2"></i>Account Settings</div></div>
        <div class="ds-cb">
          <form method="POST">
            <input type="hidden" name="action" value="update_profile"/>
            <div class="mb-3">
              <label class="ds-lbl">First Name</label>
              <input class="ds-inp" name="first_name" value="<?php echo htmlspecialchars($manager['first_name']); ?>" required/>
            </div>
            <div class="mb-3">
              <label class="ds-lbl">Last Name</label>
              <input class="ds-inp" name="last_name" value="<?php echo htmlspecialchars($manager['last_name']); ?>" required/>
            </div>
            <div class="mb-3">
              <label class="ds-lbl">Email</label>
              <input class="ds-inp" type="email" name="email" value="<?php echo htmlspecialchars($manager['email']); ?>" required/>
            </div>
            <div class="mb-3">
              <label class="ds-lbl">Phone</label>
              <input class="ds-inp" name="mobile" value="<?php echo htmlspecialchars($manager['mobile']); ?>"/>
            </div>
            <button type="submit" class="ds-btn prim w-100"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-4">
      <div class="ds-card h-100">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-bell-fill me-2"></i>Notification Preferences</div></div>
        <div class="ds-cb">
          <form method="POST">
            <input type="hidden" name="action" value="update_notifications"/>
            <div class="d-flex flex-column gap-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="booking_notif" id="notif1" checked>
                <label class="form-check-label fw-600" for="notif1">Booking Confirmations</label>
                <div class="text-muted small">Get notified when a new booking is made</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="marketing_notif" id="notif2">
                <label class="form-check-label fw-600" for="notif2">Promotional Offers</label>
                <div class="text-muted small">Receive updates about deals and features</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="update_notif" id="notif3" checked>
                <label class="form-check-label fw-600" for="notif3">Product Updates</label>
                <div class="text-muted small">Important platform updates and changes</div>
              </div>
            </div>
            <button type="submit" class="ds-btn prim w-100 mt-3"><i class="bi bi-check-lg me-1"></i> Save Preferences</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-4">
      <div class="ds-card h-100">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-shield-lock-fill me-2"></i>Password & Security</div></div>
        <div class="ds-cb">
          <form method="POST">
            <input type="hidden" name="action" value="change_password"/>
            <div class="mb-3">
              <label class="ds-lbl">Current Password</label>
              <input class="ds-inp" type="password" name="current_password" required/>
            </div>
            <div class="mb-3">
              <label class="ds-lbl">New Password</label>
              <input class="ds-inp" type="password" name="new_password" required minlength="8"/>
            </div>
            <div class="mb-3">
              <label class="ds-lbl">Confirm New Password</label>
              <input class="ds-inp" type="password" name="confirm_password" required minlength="8"/>
            </div>
            <button type="submit" class="ds-btn prim w-100"><i class="bi bi-check-lg me-1"></i> Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
      <div class="ds-card">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-globe me-2"></i>Language</div></div>
        <div class="ds-cb">
          <select class="ds-inp ds-sel">
            <option>English (US)</option>
            <option>English (UK)</option>
            <option>Hindi</option>
            <option>Spanish</option>
            <option>French</option>
          </select>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="ds-card">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-moon me-2"></i>Theme</div></div>
        <div class="ds-cb">
          <select class="ds-inp ds-sel">
            <option>Light</option>
            <option>Dark</option>
            <option>Auto</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="ds-card">
    <div class="ds-ch"><div class="ds-ct"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Logout</div></div>
    <div class="ds-cb">
      <p class="text-muted mb-3">Sign out of your Hotel Manager account on this device.</p>
      <a href="logout.php" class="ds-btn" style="background:#ef4444;color:#fff"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
</body>
</html>