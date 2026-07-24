<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin Portal';
$pageSubtitle = isset($pageSubtitle) ? $pageSubtitle : 'Platform oversight';
$currentPage = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($conn)) {
    require_once __DIR__ . '/../db.php';
}

$bellCount = 0;
if (isset($_SESSION['admin_id'])) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=" . (int)$_SESSION['admin_id'] . " AND is_read=0");
    if ($res) { $bellCount = (int)mysqli_fetch_assoc($res)['cnt']; }
}

$admin = getCurrentAdmin();
if (!$admin) {
    header('Location: login.php');
    exit();
}
$adminName = $admin['first_name'] . ' ' . $admin['last_name'];
$adminInitials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
$adminRole = ucwords(str_replace('_', ' ', $admin['role']));
$adminAvatarHtml = '';
if (!empty($admin['profile_image'])) {
    $adminAvatarHtml = '<img src="' . htmlspecialchars($admin['profile_image']) . '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" />';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> · BookHotel Admin</title>
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
        <div class="ds-logo-role">Admin Portal</div>
      </div>
    </a>
    <nav class="ds-nav">
      <div class="ds-sec">Main</div>
      <a href="admin-dashboard.php" class="ds-link <?= $currentPage === 'admin-dashboard.php' ? 'active' : '' ?>"><i class="bi bi-grid-fill"></i> Dashboard</a>
      
      <div class="ds-sec">Management</div>
      <a href="manage-users.php" class="ds-link <?= $currentPage === 'manage-users.php' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Manage Users</a>
      <a href="manage-hotels.php" class="ds-link <?= $currentPage === 'manage-hotels.php' ? 'active' : '' ?>"><i class="bi bi-building"></i> Manage Hotels</a>
      <a href="manage-cities.php" class="ds-link <?= $currentPage === 'manage-cities.php' ? 'active' : '' ?>"><i class="bi bi-geo-alt-fill"></i> Manage Cities</a>
      <a href="manage-orders.php" class="ds-link <?= $currentPage === 'manage-orders.php' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> Manage Orders</a>
      <a href="manage-ratings.php" class="ds-link <?= $currentPage === 'manage-ratings.php' ? 'active' : '' ?>"><i class="bi bi-star-fill"></i> Manage Ratings</a>
      <a href="coupon-management.php" class="ds-link <?= $currentPage === 'coupon-management.php' ? 'active' : '' ?>"><i class="bi bi-tags-fill"></i> Coupon Management</a>
      <a href="top-rated-hotels.php" class="ds-link <?= $currentPage === 'top-rated-hotels.php' ? 'active' : '' ?>"><i class="bi bi-award-fill"></i> Top Rated Hotels</a>
      <a href="commission-management.php" class="ds-link <?= $currentPage === 'commission-management.php' ? 'active' : '' ?>"><i class="bi bi-wallet-fill"></i> Commission Management</a>
      
      <div class="ds-sec">System</div>
      <a href="settings.php" class="ds-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>"><i class="bi bi-sliders"></i> Settings</a>
      <a href="logout.php" class="ds-link <?= $currentPage === 'logout.php' ? 'active' : '' ?>"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
    <div class="ds-foot">
      <a href="profile.php" class="ds-hpill">
        <div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
    <?= $adminAvatarHtml ?: $adminInitials ?>
</div>
        <div>
          <div class="ds-hpill-name"><?= htmlspecialchars($adminName) ?></div>
          <div class="ds-hpill-status">● <?= htmlspecialchars($adminRole) ?></div>
        </div>
      </a>
    </div>
  </aside>

  <header class="ds-top">
    <div class="ds-top-l">
      <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
      <div>
        <div class="ds-page-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="ds-breadcrumb"><?= htmlspecialchars($pageSubtitle) ?></div>
      </div>
    </div>
    <div class="ds-top-r">
      <div class="d-none d-md-flex align-items-center" style="position: relative;">
        <i class="bi bi-search" style="position: absolute; left: .65rem; color: rgba(15,23,42,.4); font-size: .85rem;"></i>
        <input type="text" placeholder="Search…" style="background: rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); border-radius: 8px; padding: .38rem .8rem .38rem 2.1rem; color: #0f172a; font-size: .8rem; width: 200px; outline: none; transition: all .2s;" />
      </div>
      <a href="notifications.php" class="ds-ibtn" style="position:relative">
        <i class="bi bi-bell-fill"></i>
        <?php if ($bellCount > 0): ?><span class="ds-dot"></span><?php endif; ?>
        <span id="bellCount" style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0;<?php echo $bellCount > 0 ? '' : 'display:none;'; ?>"><?php echo $bellCount > 99 ? '99+' : $bellCount; ?></span>
      </a>
      <div class="ds-avbtn" id="dsAvBtn">
        <div class="ds-av" style="background:linear-gradient(135deg,#f59e0b,#d97706);overflow:hidden;"><?= $adminAvatarHtml ?: $adminInitials ?></div>
        <span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($adminName) ?></span>
        <div class="ds-dropdown" id="dsAvMenu">
          <a href="profile.php" class="ds-drop-item"><i class="bi bi-person-fill text-primary"></i> My Profile</a>
          <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
          <hr class="my-1 mx-2" />
          <a href="../login.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <main class="ds-main">
    <div class="admin-shell">
