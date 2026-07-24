<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';

$user_id = $_SESSION['hm_id'] ?? 0;

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read' && isset($_POST['id'])) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $_POST['id'], $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok]);
        exit;
    }
    
    if ($action === 'delete' && isset($_POST['id'])) {
        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $_POST['id'], $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok]);
        exit;
    }
    
    if ($action === 'delete_all') {
        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok]);
        exit;
    }
}

$unread_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
if ($res) { $unread_count = (int)mysqli_fetch_assoc($res)['cnt']; }

$notifications = [];
$res = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC");
if ($res) while ($row = mysqli_fetch_assoc($res)) $notifications[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Notifications — Hotel Operations</title>
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
    <a href="notifications.php" class="ds-link active"><i class="bi bi-bell-fill"></i> Notifications</a>
    <a href="settings.php" class="ds-link"><i class="bi bi-sliders"></i> Settings</a>
    <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
  <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script>
  <div class="ds-foot">
    <a href="manage-hotel-listing.php" class="ds-hpill">
      <div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"><?= $manager_initials ?></div>
      <div>
        <div class="ds-hpill-name"><?= htmlspecialchars($manager_name) ?></div>
        <div class="ds-hpill-status"><?= htmlspecialchars($manager_role) ?></div>
      </div>
    </a>
  </div>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div><div class="ds-page-title">Notifications</div><div class="ds-breadcrumb">Dashboard / Notifications</div></div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn" style="position:relative">
      <i class="bi bi-bell-fill"></i>
      <?php if ($unread_count > 0): ?><span class="ds-dot"></span><?php endif; ?>
      <span id="bellCount" style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0;"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
    </a>
    <div class="ds-avbtn" id="dsAvBtn">
      <div class="ds-av"><?= $manager_initials ?></div>
      <span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
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
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="ds-card">
        <div class="ds-ch">
          <div class="ds-ct"><i class="bi bi-bell-fill"></i> All Notifications
            <span class="badge bg-primary ms-2" style="font-size:.72rem"><?php echo count($notifications); ?></span>
            <span class="badge bg-danger ms-1" style="font-size:.72rem"><?php echo $unread_count; ?> unread</span>
          </div>
          <div class="d-flex gap-2">
            <button class="ds-btn prim sm" onclick="markAllRead()"><i class="bi bi-check2-all me-1"></i>Mark All Read</button>
            <button class="ds-btn gho sm" onclick="deleteAll()"><i class="bi bi-trash3 me-1"></i>Clear All</button>
          </div>
        </div>
        <div class="ds-cb">
          <?php if (empty($notifications)): ?>
          <div class="text-center py-5">
            <i class="bi bi-bell-slash" style="font-size:3rem;color:#cbd5e1"></i>
            <div class="fw-700 mt-3" style="color:#64748b">No notifications yet</div>
            <div class="text-muted small mt-1">You'll see booking alerts and updates here.</div>
          </div>
          <?php else: ?>
          <div style="overflow-x:auto">
            <table class="ds-tbl" id="notifTable">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Type</th>
                  <th>Title</th>
                  <th>Description</th>
                  <th>Date & Time</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($notifications as $n): 
                  $iconMap = [
                    'new_booking'=>'bi-calendar2-check-fill',
                    'booking_cancelled'=>'bi-calendar-x-fill',
                    'room_status_updated'=>'bi-door-open-fill',
                    'checkin_today'=>'bi-person-check-fill',
                    'checkout_today'=>'bi-person-x-fill',
                    'hotel_approved'=>'bi-building-check',
                    'hotel_rejected'=>'bi-building-x-fill'
                  ];
                  $icon = $iconMap[$n['type']] ?? 'bi-bell-fill';
                  $colorMap = [
                    'new_booking'=>'blue',
                    'booking_cancelled'=>'red',
                    'room_status_updated'=>'gold',
                    'checkin_today'=>'green',
                    'checkout_today'=>'purple',
                    'hotel_approved'=>'green',
                    'hotel_rejected'=>'red'
                  ];
                  $color = $colorMap[$n['type']] ?? 'blue';
                ?>
                <tr id="notif-<?php echo $n['id']; ?>" style="<?php echo $n['is_read'] ? '' : 'background:#f8faff;'; ?>">
                  <td>
                    <span class="ds-badge <?php echo $n['is_read'] ? 'blocked' : 'available'; ?>">
                      <?php echo $n['is_read'] ? 'Read' : 'Unread'; ?>
                    </span>
                  </td>
                  <td>
                    <div class="ds-si" style="width:38px;height:38px;border-radius:10px;background:var(--<?php echo $n['is_read'] ? 'bdr' : ($color==='blue'?'pr-lt':($color==='green'?'grn-lt':($color==='red'?'red-lt':($color==='gold'?'#fef3c7':'var(--purp-lt)')))); ?>);color:var(--<?php echo $color==='blue'?'pr':($color==='green'?'grn':($color==='red'?'red':($color==='gold'?'gold':'purp'))); ?>);display:flex;align-items:center;justify-content:center;font-size:1rem;">
                      <i class="bi <?php echo $icon; ?>"></i>
                    </div>
                  </td>
                  <td class="fw-700 small"><?php echo htmlspecialchars($n['title']); ?></td>
                  <td class="small text-muted" style="max-width:300px"><?php echo htmlspecialchars($n['message'] ?? ''); ?></td>
                  <td class="small fw-600"><?php echo date('d M Y', strtotime($n['created_at'])); ?><br><span class="text-muted" style="font-size:.7rem"><?php echo date('h:i A', strtotime($n['created_at'])); ?></span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if (!$n['is_read']): ?>
                      <button class="ds-btn gho sm ico" onclick="markRead(<?php echo $n['id']; ?>)" title="Mark as Read"><i class="bi bi-check2"></i></button>
                      <?php endif; ?>
                      <button class="ds-btn dng sm ico" onclick="deleteNotif(<?php echo $n['id']; ?>)" title="Delete"><i class="bi bi-trash3"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
<script>
function markRead(id){
  fetch('notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read&id='+id})
  .then(r=>r.json()).then(d=>{
    if(d.success){
      const row=document.getElementById('notif-'+id);
      if(row){row.style.background='';row.querySelector('.ds-badge').className='ds-badge blocked';row.querySelector('.ds-badge').textContent='Read';
      const btn=row.querySelector('button[title="Mark as Read"]');if(btn)btn.remove();}
      updateBellCount(-1);
    }
  });
}
function markAllRead(){
  fetch('notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_all_read'})
  .then(r=>r.json()).then(d=>{
    if(d.success){document.querySelectorAll('#notifTable tbody tr').forEach(row=>{row.style.background='';const badge=row.querySelector('.ds-badge');if(badge){badge.className='ds-badge blocked';badge.textContent='Read';}const btn=row.querySelector('button[title="Mark as Read"]');if(btn)btn.remove();});updateBellCount(0);dsToast('All notifications marked as read','success');}
  });
}
function deleteNotif(id){
  fetch('notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=delete&id='+id})
  .then(r=>r.json()).then(d=>{
    if(d.success){const row=document.getElementById('notif-'+id);if(row){row.remove();}updateBellCount(-1);dsToast('Notification deleted','error');}
  });
}
function deleteAll(){
  if(!confirm('Delete all notifications?'))return;
  fetch('notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=delete_all'})
  .then(r=>r.json()).then(d=>{
    if(d.success){document.getElementById('notifTable').querySelector('tbody').innerHTML='';updateBellCount(0);dsToast('All notifications cleared','error');}
  });
}
function updateBellCount(delta){
  const el=document.getElementById('bellCount');
  if(!el)return;
  let c=parseInt(el.textContent)||0;
  if(delta===0){c=0;}else{c=Math.max(0,c+delta);}
  el.textContent=c>99?'99+':c;
  if(c<=0){el.style.display='none';}
  const dot=document.querySelector('.ds-dot');
  if(dot){dot.style.display=c>0?'block':'none';}
}
</script>
</body>
</html>