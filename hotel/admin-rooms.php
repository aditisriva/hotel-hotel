<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';
mysqli_report(MYSQLI_REPORT_OFF); // return false on error, never throw

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

// Auto-create rooms table with VARCHAR columns (no ENUM, avoids strict mode issues)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hotel_id`       INT UNSIGNED NOT NULL DEFAULT 1,
  `room_number`    VARCHAR(20)  NOT NULL,
  `room_type`      VARCHAR(50)  NOT NULL DEFAULT 'Standard',
  `floor`          SMALLINT     NOT NULL DEFAULT 1,
  `adult_capacity` SMALLINT     NOT NULL DEFAULT 2,
  `child_capacity` SMALLINT     NOT NULL DEFAULT 0,
  `bed_type`       VARCHAR(30)  NOT NULL DEFAULT 'Double',
  `base_price`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_pct`   DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `description`    TEXT DEFAULT NULL,
  `amenities`      VARCHAR(500) DEFAULT NULL,
  `room_images`    TEXT DEFAULT NULL,
  `status`         VARCHAR(20)  NOT NULL DEFAULT 'available',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_hotel`(`hotel_id`),
  INDEX `idx_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Helper: upload images and return JSON array string (max 10, JPG/PNG/WEBP/JPEG only)
function uploadRoomImages(int $rid, array $existingPaths = []): array {
    $paths  = $existingPaths; // start with existing
    $errors = [];

    if (empty($_FILES['room_images']['name'][0])) {
        return ['paths' => $paths, 'errors' => $errors];
    }

    $dir     = __DIR__ . '/uploads/rooms/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $allowed = ['jpg','jpeg','png','webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB per file
    $maxFiles= 10;
    $count   = count($paths); // already have this many

    foreach ($_FILES['room_images']['tmp_name'] as $i => $tmp) {
        if ($count >= $maxFiles) { $errors[] = "Max $maxFiles images allowed"; break; }
        if ($_FILES['room_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($_FILES['room_images']['size'][$i] > $maxSize) { $errors[] = "Image ".($i+1)." exceeds 5MB"; continue; }

        $ext = strtolower(pathinfo($_FILES['room_images']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = "Image ".($i+1)." must be JPG/JPEG/PNG/WEBP"; continue; }

        // Unique filename — prevents overwriting
        $fn = 'room_' . $rid . '_' . uniqid('', true) . '.' . $ext;
        if (@move_uploaded_file($tmp, $dir . $fn)) {
            $paths[] = 'uploads/rooms/' . $fn;
            $count++;
        }
    }
    return ['paths' => $paths, 'errors' => $errors];
}

// Helper: delete a specific image file from disk
function deleteRoomImageFile(string $path): void {
    $full = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($full) && strpos(realpath($full), realpath(__DIR__)) === 0) {
        @unlink($full);
    }
}

// POST / AJAX handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $rn  = trim($_POST['room_number']    ?? '');
        $rt  = trim($_POST['room_type']      ?? 'Standard');
        $fl  = max(1, (int)($_POST['floor']           ?? 1));
        $ac  = max(1, (int)($_POST['adult_capacity']  ?? 2));
        $cc  = max(0, (int)($_POST['child_capacity']  ?? 0));
        $bt  = trim($_POST['bed_type']       ?? 'Double');
        $bp  = (float)str_replace(',', '', $_POST['base_price']   ?? '0');
        $dp  = (float)str_replace(',', '', $_POST['discount_pct'] ?? '0');
        $des = trim($_POST['description']    ?? '');
        $am  = trim($_POST['amenities']      ?? '');
        $st  = trim($_POST['status']         ?? 'available');
        $hid = 1;

        if ($rn === '') { echo json_encode(['success'=>false,'error'=>'Room number is required']); exit; }
        if ($bp <= 0)   { echo json_encode(['success'=>false,'error'=>'Base price must be > 0 (received: '.var_export($_POST['base_price']??'missing',true).')']); exit; }

        $rn_e  = mysqli_real_escape_string($conn, $rn);
        $rt_e  = mysqli_real_escape_string($conn, $rt);
        $bt_e  = mysqli_real_escape_string($conn, $bt);
        $des_e = mysqli_real_escape_string($conn, $des);
        $am_e  = mysqli_real_escape_string($conn, $am);
        $st_e  = mysqli_real_escape_string($conn, $st);

        // Duplicate check
        $dup = mysqli_query($conn, "SELECT room_id FROM rooms WHERE room_number='$rn_e' AND hotel_id=$hid LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) { echo json_encode(['success'=>false,'error'=>"Room '$rn' already exists"]); exit; }

        $sql = "INSERT INTO rooms
            (hotel_id,room_number,room_type,floor,adult_capacity,child_capacity,bed_type,base_price,discount_pct,description,amenities,status)
            VALUES ($hid,'$rn_e','$rt_e',$fl,$ac,$cc,'$bt_e',$bp,$dp,'$des_e','$am_e','$st_e')";

        if (!mysqli_query($conn, $sql)) {
            $err = mysqli_error($conn);
            error_log("Room INSERT failed: $err | SQL: $sql");
            echo json_encode(['success'=>false,'error'=>'DB error: '.$err]);
            exit;
        }
        $new_id = (int)mysqli_insert_id($conn);
        // Image upload — required: at least 1
        if (empty($_FILES['room_images']['name'][0])) {
            // Rollback insert if no image
            mysqli_query($conn,"DELETE FROM rooms WHERE room_id=$new_id");
            echo json_encode(['success'=>false,'error'=>'Please upload at least one room image.']);
            exit;
        }
        $upload = uploadRoomImages($new_id, []);
        if (empty($upload['paths'])) {
            mysqli_query($conn,"DELETE FROM rooms WHERE room_id=$new_id");
            echo json_encode(['success'=>false,'error'=>'Image upload failed. ' . implode(' ', $upload['errors'])]);
            exit;
        }
        $imgs_json = json_encode(array_values($upload['paths']));
        $im_e = mysqli_real_escape_string($conn, $imgs_json);
        mysqli_query($conn,"UPDATE rooms SET room_images='$im_e' WHERE room_id=$new_id");
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM rooms WHERE room_id=$new_id LIMIT 1"));
        $resp = ['success'=>true,'room_id'=>$new_id,'room'=>$row];
        if (!empty($upload['errors'])) $resp['warnings'] = $upload['errors'];
        echo json_encode($resp);
        exit;
    }

    if ($act === 'delete_image') {
        $rid   = (int)($_POST['room_id'] ?? 0);
        $imgPath = trim($_POST['img_path'] ?? '');
        if (!$rid || !$imgPath) { echo json_encode(['success'=>false,'error'=>'Invalid request']); exit; }
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT room_images FROM rooms WHERE room_id=$rid AND hotel_id=1 LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Room not found']); exit; }
        $existing = $row['room_images'] ? json_decode($row['room_images'],true) : [];
        // Remove the specified path
        $updated = array_values(array_filter($existing, fn($p) => $p !== $imgPath));
        deleteRoomImageFile($imgPath);
        $new_json = json_encode($updated);
        $ne = mysqli_real_escape_string($conn, $new_json);
        mysqli_query($conn,"UPDATE rooms SET room_images='$ne' WHERE room_id=$rid");
        echo json_encode(['success'=>true,'remaining'=>count($updated),'images'=>$updated]);
        exit;
    }

    if ($act === 'set_primary') {
        $rid     = (int)($_POST['room_id'] ?? 0);
        $imgPath = trim($_POST['img_path'] ?? '');
        if (!$rid || !$imgPath) { echo json_encode(['success'=>false,'error'=>'Invalid request']); exit; }
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT room_images FROM rooms WHERE room_id=$rid AND hotel_id=1 LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Room not found']); exit; }
        $existing = $row['room_images'] ? json_decode($row['room_images'],true) : [];
        // Move selected image to front
        $reordered = array_values(array_merge([$imgPath], array_filter($existing, fn($p) => $p !== $imgPath)));
        $ne = mysqli_real_escape_string($conn, json_encode($reordered));
        mysqli_query($conn,"UPDATE rooms SET room_images='$ne' WHERE room_id=$rid");
        echo json_encode(['success'=>true,'images'=>$reordered]);
        exit;
    }

    if ($act === 'edit') {
        $rid = (int)($_POST['room_id'] ?? 0);
        $rn  = trim($_POST['room_number']    ?? '');
        $rt  = trim($_POST['room_type']      ?? 'Standard');
        $fl  = max(1,(int)($_POST['floor']           ?? 1));
        $ac  = max(1,(int)($_POST['adult_capacity']  ?? 2));
        $cc  = max(0,(int)($_POST['child_capacity']  ?? 0));
        $bt  = trim($_POST['bed_type']       ?? 'Double');
        $bp  = (float)str_replace(',','',$_POST['base_price']   ?? '0');
        $dp  = (float)str_replace(',','',$_POST['discount_pct'] ?? '0');
        $des = trim($_POST['description']    ?? '');
        $am  = trim($_POST['amenities']      ?? '');
        $st  = trim($_POST['status']         ?? 'available');
        if (!$rid || $rn==='' || $bp<=0) { echo json_encode(['success'=>false,'error'=>'Invalid input']); exit; }
        $rn_e=mysqli_real_escape_string($conn,$rn); $rt_e=mysqli_real_escape_string($conn,$rt);
        $bt_e=mysqli_real_escape_string($conn,$bt); $des_e=mysqli_real_escape_string($conn,$des);
        $am_e=mysqli_real_escape_string($conn,$am); $st_e=mysqli_real_escape_string($conn,$st);
        $dup=mysqli_query($conn,"SELECT room_id FROM rooms WHERE room_number='$rn_e' AND hotel_id=1 AND room_id!=$rid LIMIT 1");
        if ($dup && mysqli_num_rows($dup)>0){echo json_encode(['success'=>false,'error'=>"Room '$rn' already used"]);exit;}
        // Get existing images to merge with new uploads
        $cur_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT room_images FROM rooms WHERE room_id=$rid LIMIT 1"));
        $existing_imgs = ($cur_row && $cur_row['room_images']) ? json_decode($cur_row['room_images'],true) : [];
        $imgSql='';
        if (!empty($_FILES['room_images']['name'][0])) {
            $upload = uploadRoomImages($rid, $existing_imgs);
            if (!empty($upload['paths'])) {
                $imgs_json = json_encode(array_values($upload['paths']));
                $im_e = mysqli_real_escape_string($conn, $imgs_json);
                $imgSql = ",room_images='$im_e'";
            }
        }
        $ok=mysqli_query($conn,"UPDATE rooms SET room_number='$rn_e',room_type='$rt_e',floor=$fl,adult_capacity=$ac,child_capacity=$cc,bed_type='$bt_e',base_price=$bp,discount_pct=$dp,description='$des_e',amenities='$am_e',status='$st_e'$imgSql WHERE room_id=$rid AND hotel_id=1");
        echo json_encode(['success'=>(bool)$ok,'error'=>$ok?'':mysqli_error($conn)]);
        exit;
    }

    if ($act === 'delete') {
        $rid=(int)($_POST['room_id']??0);
        if(!$rid){echo json_encode(['success'=>false,'error'=>'Invalid ID']);exit;}
        $ok=mysqli_query($conn,"DELETE FROM rooms WHERE room_id=$rid AND hotel_id=1");
        echo json_encode(['success'=>(bool)$ok,'error'=>$ok?'':mysqli_error($conn)]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action']);
    exit;
}

// Page load — filters + pagination
$search  = trim($_GET['q']      ?? '');
$fstatus = trim($_GET['status'] ?? '');
$page    = max(1,(int)($_GET['page']??1));
$perpage = 10;
$where   = ['hotel_id=1'];
if ($search)  { $s=mysqli_real_escape_string($conn,$search);  $where[]="(room_number LIKE '%$s%' OR room_type LIKE '%$s%')"; }
if ($fstatus) { $fs=mysqli_real_escape_string($conn,$fstatus);$where[]="status='$fs'"; }
$wsql    = implode(' AND ',$where);
$total   = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM rooms WHERE $wsql"))['c']??0);
$pages   = max(1,ceil($total/$perpage));
$offset  = ($page-1)*$perpage;
$rooms   = [];
$res=mysqli_query($conn,"SELECT * FROM rooms WHERE $wsql ORDER BY CAST(room_number AS UNSIGNED),room_number ASC LIMIT $perpage OFFSET $offset");
if($res) while($r=mysqli_fetch_assoc($res)) $rooms[]=$r;

$st_res=mysqli_query($conn,"SELECT COUNT(*) t,SUM(status='available') av,SUM(status='occupied') oc,SUM(status='maintenance') mn FROM rooms WHERE hotel_id=1");
$s=mysqli_fetch_assoc($st_res)??['t'=>0,'av'=>0,'oc'=>0,'mn'=>0];

$amenity_defs=['wifi'=>['WiFi','bi-wifi'],'ac'=>['AC','bi-fan'],'tv'=>['TV','bi-tv-fill'],'breakfast'=>['Breakfast','bi-cup-hot-fill'],'parking'=>['Parking','bi-car-front-fill'],'balcony'=>['Balcony','bi-house-door-fill'],'minibar'=>['Mini Bar','bi-cup-straw']];
function amenChips(string $sel=''):void{
    global $amenity_defs;
    $cur=array_filter(array_map('trim',explode(',',$sel)));
    foreach($amenity_defs as $k=>[$l,$ic]):
        $chk=in_array($k,$cur)?'checked':''; $cls='amenity-chip'.($chk?' selected':'');
        echo "<label class=\"$cls\"><input type=\"checkbox\" name=\"amenities[]\" value=\"$k\" $chk onchange=\"this.closest('label').classList.toggle('selected',this.checked)\"/><i class=\"bi $ic\"></i> $l</label>";
    endforeach;
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Manage Rooms – Hotel Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="dashboard.css"/>
<style>
.room-thumb{width:64px;height:48px;object-fit:cover;border-radius:8px;border:1.5px solid var(--bdr)}
.room-ph{width:64px;height:48px;border-radius:8px;background:var(--srf);border:1.5px dashed var(--bdr);display:flex;align-items:center;justify-content:center;color:var(--mut)}
.amenity-chip{display:inline-flex;align-items:center;gap:4px;background:var(--pr-lt);color:var(--pr);border-radius:20px;padding:3px 10px;font-size:.73rem;font-weight:600;cursor:pointer;user-select:none;transition:.15s}
.amenity-chip input{display:none}
.amenity-chip.selected{background:var(--pr);color:#fff}
.am-tag{display:inline-flex;align-items:center;gap:3px;background:var(--pr-lt);color:var(--pr);border-radius:20px;padding:2px 9px;font-size:.72rem;font-weight:600;margin:2px}
.page-n{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1.5px solid var(--bdr);font-size:.82rem;font-weight:600;color:var(--txt2);text-decoration:none;transition:.15s}
.page-n:hover,.page-n.on{background:var(--pr);color:#fff;border-color:var(--pr)}
.ds-lbl{font-size:.72rem;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem}
.img-drop{border:2px dashed var(--bdr);border-radius:10px;padding:1.25rem;text-align:center;cursor:pointer;background:var(--srf);transition:.2s}
.img-drop:hover{border-color:var(--pr);background:var(--pr-lt)}
.img-drop.drag-over{border-color:var(--pr);background:var(--pr-lt)}
/* New upload preview grid */
.upload-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:.6rem;margin-top:.75rem}
.upload-item{position:relative;border-radius:10px;overflow:hidden;border:2px solid var(--bdr);aspect-ratio:4/3;background:var(--srf)}
.upload-item img{width:100%;height:100%;object-fit:cover;display:block}
.upload-item.is-primary{border-color:var(--grn);box-shadow:0 0 0 3px rgba(5,150,105,.18)}
.upload-item .img-badge{position:absolute;top:4px;left:4px;background:var(--grn);color:#fff;font-size:.6rem;font-weight:800;padding:2px 6px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em}
.upload-item .img-actions{position:absolute;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;gap:.3rem;opacity:0;transition:.2s}
.upload-item:hover .img-actions{opacity:1}
.img-act-btn{width:28px;height:28px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;transition:.15s}
.img-act-btn.star{background:#fbbf24;color:#0f172a}.img-act-btn.del{background:var(--red);color:#fff}
.img-act-btn:hover{transform:scale(1.12)}
.upload-count{font-size:.72rem;font-weight:600;color:var(--mut);margin-top:.4rem}
.final-p{color:var(--grn);font-weight:800}.orig-p{text-decoration:line-through;color:var(--mut);font-size:.75rem}</style>
</head><body>
<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo"><div class="ds-logo-icon"><i class="bi bi-buildings"></i></div><div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Hotel Operations</div></div></a>
  <nav class="ds-nav">
    <div class="ds-sec">Main</div>
    <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
    <a href="admin-hotel-profile.php" class="ds-link"><i class="bi bi-building"></i> Hotel Management</a>
    <div class="ds-sec">Operations</div>
    <a href="admin-rooms.php" class="ds-link active"><i class="bi bi-door-open-fill"></i> Rooms</a>
    <a href="admin-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Bookings</a>
    <a href="admin-guests.php" class="ds-link"><i class="bi bi-people-fill"></i> Guests</a>
    <div class="ds-sec">Insights</div>
    <a href="admin-reviews.php" class="ds-link"><i class="bi bi-star-fill"></i> Reviews</a>
    <a href="admin-revenue.php" class="ds-link"><i class="bi bi-bar-chart-fill"></i> Revenue</a>
    <a href="notifications.php" class="ds-link"><i class="bi bi-bell-fill"></i> Notifications</a>
    <div class="ds-sec">Account</div>
    <a href="settings.php" class="ds-link"><i class="bi bi-sliders"></i> Settings</a>
    <a href="index.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Back to Website</a>
  </nav>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div><div class="ds-page-title">Manage Rooms</div><div class="ds-breadcrumb">Dashboard / Rooms &middot; <?php echo (int)$s['t']; ?> rooms</div></div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i></a>
    <div class="ds-avbtn" id="dsAvBtn"><div class="ds-av"><?= $manager_initials ?></div><span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/><a href="login.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">
<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-door-open-fill"></i></div><div class="ds-sn"><?php echo (int)$s['t']; ?></div><div class="ds-sl">Total Rooms</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div><div class="ds-sn"><?php echo (int)$s['av']; ?></div><div class="ds-sl">Available</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-person-fill"></i></div><div class="ds-sn"><?php echo (int)$s['oc']; ?></div><div class="ds-sl">Occupied</div></div></div>
  <div class="col-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-tools"></i></div><div class="ds-sn"><?php echo (int)$s['mn']; ?></div><div class="ds-sl">Maintenance</div></div></div>
</div>
<!-- Rooms table card -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-door-open-fill"></i> Room Inventory <span class="badge bg-primary ms-1" style="font-size:.7rem"><?php echo $total; ?></span></div>
    <div class="d-flex flex-wrap gap-2">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i><input class="ds-inp search" name="q" placeholder="Room no. or type..." value="<?php echo htmlspecialchars($search); ?>" style="width:185px"/></div>
        <select class="ds-inp ds-sel" name="status" style="width:140px" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="available"   <?php echo $fstatus==='available'  ?'selected':'';?>>Available</option>
          <option value="occupied"    <?php echo $fstatus==='occupied'   ?'selected':'';?>>Occupied</option>
          <option value="maintenance" <?php echo $fstatus==='maintenance'?'selected':'';?>>Maintenance</option>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <?php if($search||$fstatus):?><a href="admin-rooms.php" class="ds-btn gho sm"><i class="bi bi-x-lg"></i></a><?php endif;?>
      </form>
      <button class="ds-btn prim" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Room</button>
    </div>
  </div>
  <div class="ds-cb p-0">
    <?php if(empty($rooms)):?>
    <div class="text-center py-5"><i class="bi bi-door-open" style="font-size:3rem;color:#cbd5e1"></i>
      <div class="fw-700 mt-3" style="color:#64748b">No rooms found</div>
      <div class="text-muted small"><?php echo ($search||$fstatus)?'Clear filters to see all rooms.':'Add your first room using the button above.';?></div>
    </div>
    <?php else:?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead><tr>
          <th class="ps-3">Image</th><th>Room No.</th><th>Type</th><th>Floor</th>
          <th>Capacity</th><th>Base Price</th><th>Discount</th><th>Final Price</th>
          <th>Status</th><th class="text-center">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($rooms as $r):
          $imgs=($r['room_images']?json_decode($r['room_images'],true):[]);
          $thumb=$imgs?$imgs[0]:'';
          $disc=(float)$r['discount_pct']; $base=(float)$r['base_price'];
          $final=round($base*(1-$disc/100));
          $cap=$r['adult_capacity'].' Adult'.($r['adult_capacity']>1?'s':'');
          if($r['child_capacity']>0) $cap.=', '.$r['child_capacity'].' Child'.($r['child_capacity']>1?'ren':'');
          $sc=['available'=>'available','occupied'=>'occupied','maintenance'=>'maintenance'];
        ?>
        <tr>
          <td class="ps-3"><?php if($thumb):?><img src="<?php echo htmlspecialchars($thumb);?>" class="room-thumb" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/><div class="room-ph" style="display:none"><i class="bi bi-image"></i></div><?php else:?><div class="room-ph"><i class="bi bi-image"></i></div><?php endif;?></td>
          <td><span class="fw-800" style="color:var(--pr)"><?php echo htmlspecialchars($r['room_number']);?></span></td>
          <td><div class="fw-600 small"><?php echo htmlspecialchars($r['room_type']);?></div><div class="text-muted" style="font-size:.72rem"><?php echo htmlspecialchars($r['bed_type']);?> Bed</div></td>
          <td class="small">Floor <?php echo (int)$r['floor'];?></td>
          <td class="small"><?php echo htmlspecialchars($cap);?></td>
          <td class="small">&#8377;<?php echo number_format($base);?></td>
          <td><?php if($disc>0):?><span class="badge bg-danger" style="font-size:.7rem"><?php echo $disc;?>% OFF</span><?php else:?><span class="text-muted">&#8212;</span><?php endif;?></td>
          <td><?php if($disc>0):?><div class="orig-p">&#8377;<?php echo number_format($base);?></div><?php endif;?><div class="final-p small">&#8377;<?php echo number_format($final);?></div></td>
          <td><span class="ds-badge <?php echo $sc[$r['status']]??'available';?>"><?php echo ucfirst($r['status']);?></span></td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <button class="ds-btn gho sm" title="View" onclick='viewRoom(<?php echo htmlspecialchars(json_encode($r),ENT_QUOTES);?>)'><i class="bi bi-eye-fill"></i></button>
              <button class="ds-btn outl sm" title="Edit" onclick='editRoom(<?php echo htmlspecialchars(json_encode($r),ENT_QUOTES);?>)'><i class="bi bi-pencil-fill"></i></button>
              <button class="ds-btn sm" style="background:var(--red-lt);color:var(--red);border-color:var(--red-lt)" title="Delete" onclick="confirmDel(<?php echo (int)$r['room_id'];?>,'<?php echo htmlspecialchars(addslashes($r['room_number']));?>')"><i class="bi bi-trash-fill"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php if($pages>1): $qs=http_build_query(['q'=>$search,'status'=>$fstatus]);?>
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
      <div class="text-muted small">Showing <?php echo $offset+1;?>&#8211;<?php echo min($offset+$perpage,$total);?> of <?php echo $total;?></div>
      <div class="d-flex gap-1 flex-wrap">
        <?php if($page>1):?><a class="page-n" href="?page=<?php echo $page-1;?>&<?php echo $qs;?>"><i class="bi bi-chevron-left"></i></a><?php endif;?>
        <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++):?><a class="page-n <?php echo $p===$page?'on':'';?>" href="?page=<?php echo $p;?>&<?php echo $qs;?>"><?php echo $p;?></a><?php endfor;?>
        <?php if($page<$pages):?><a class="page-n" href="?page=<?php echo $page+1;?>&<?php echo $qs;?>"><i class="bi bi-chevron-right"></i></a><?php endif;?>
      </div>
    </div>
    <?php endif;?>
    <?php endif;?>
  </div>
</div>
</main>

<!-- ADD MODAL -->
<div class="modal fade ds-modal" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Add New Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form id="addForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4"><div class="ds-lbl">Room Number *</div><input class="ds-inp" name="room_number" required placeholder="e.g. 101"/></div>
            <div class="col-md-4"><div class="ds-lbl">Room Type *</div><select class="ds-inp ds-sel" name="room_type"><option>Standard</option><option>Deluxe</option><option>Suite</option><option>Presidential Suite</option><option>Family Room</option><option>Studio</option></select></div>
            <div class="col-md-4"><div class="ds-lbl">Floor</div><input class="ds-inp" type="number" name="floor" min="1" max="50" value="1"/></div>
            <div class="col-md-3"><div class="ds-lbl">Adults</div><input class="ds-inp" type="number" name="adult_capacity" min="1" max="10" value="2"/></div>
            <div class="col-md-3"><div class="ds-lbl">Children</div><input class="ds-inp" type="number" name="child_capacity" min="0" max="6" value="0"/></div>
            <div class="col-md-3"><div class="ds-lbl">Bed Type</div><select class="ds-inp ds-sel" name="bed_type"><option>Single</option><option>Double</option><option>Queen</option><option>King</option><option>Twin</option><option>Bunk</option></select></div>
            <div class="col-md-3"><div class="ds-lbl">Status</div><select class="ds-inp ds-sel" name="status"><option value="available">Available</option><option value="occupied">Occupied</option><option value="maintenance">Maintenance</option></select></div>
            <div class="col-md-6"><div class="ds-lbl">Base Price (Rs.) *</div><input class="ds-inp" type="number" name="base_price" id="addBP" min="1" step="1" placeholder="e.g. 3500" required/></div>
            <div class="col-md-6"><div class="ds-lbl">Discount %</div><input class="ds-inp" type="number" name="discount_pct" min="0" max="100" step="1" value="0"/></div>
            <div class="col-12"><div class="ds-lbl">Description</div><textarea class="ds-inp" name="description" rows="2" placeholder="Room features..."></textarea></div>
            <div class="col-12">
              <div class="ds-lbl">Amenities</div>
              <div class="d-flex flex-wrap gap-2 mt-1" id="addAmenWrap"><?php amenChips(); ?></div>
            </div>
            <div class="col-12">
              <div class="ds-lbl">Room Images <span class="text-danger">*</span> <span class="text-muted fw-400">(1–10 images, JPG/JPEG/PNG/WEBP, max 5MB each)</span></div>
              <div class="img-drop" id="addDrop" onclick="document.getElementById('addImgIn').click()" ondragover="dragOver(event,'addDrop')" ondragleave="dragLeave('addDrop')" ondrop="dropFiles(event,'addImgIn','addGrid')">
                <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                <div class="fw-700 mt-1">Click to upload or drag &amp; drop</div>
                <div class="text-muted small">JPG, JPEG, PNG, WEBP &middot; Max 5MB each &middot; Up to 10 images</div>
              </div>
              <input type="file" id="addImgIn" name="room_images[]" multiple accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="buildPreviewGrid(this.files,'addGrid','add')"/>
              <div class="upload-grid" id="addGrid"></div>
              <div class="upload-count" id="addCount"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button><button type="submit" class="ds-btn prim" id="addBtn"><i class="bi bi-check-lg me-1"></i>Add Room</button></div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade ds-modal" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form id="editForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="room_id" id="eRoomId"/>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4"><div class="ds-lbl">Room Number *</div><input class="ds-inp" name="room_number" id="eRN" required/></div>
            <div class="col-md-4"><div class="ds-lbl">Room Type</div><select class="ds-inp ds-sel" name="room_type" id="eRT"><option>Standard</option><option>Deluxe</option><option>Suite</option><option>Presidential Suite</option><option>Family Room</option><option>Studio</option></select></div>
            <div class="col-md-4"><div class="ds-lbl">Floor</div><input class="ds-inp" type="number" name="floor" id="eFL" min="1" max="50"/></div>
            <div class="col-md-3"><div class="ds-lbl">Adults</div><input class="ds-inp" type="number" name="adult_capacity" id="eAC" min="1" max="10"/></div>
            <div class="col-md-3"><div class="ds-lbl">Children</div><input class="ds-inp" type="number" name="child_capacity" id="eCC" min="0" max="6"/></div>
            <div class="col-md-3"><div class="ds-lbl">Bed Type</div><select class="ds-inp ds-sel" name="bed_type" id="eBT"><option>Single</option><option>Double</option><option>Queen</option><option>King</option><option>Twin</option><option>Bunk</option></select></div>
            <div class="col-md-3"><div class="ds-lbl">Status</div><select class="ds-inp ds-sel" name="status" id="eST"><option value="available">Available</option><option value="occupied">Occupied</option><option value="maintenance">Maintenance</option></select></div>
            <div class="col-md-6"><div class="ds-lbl">Base Price (Rs.) *</div><input class="ds-inp" type="number" name="base_price" id="eBP" min="1" step="1" required/></div>
            <div class="col-md-6"><div class="ds-lbl">Discount %</div><input class="ds-inp" type="number" name="discount_pct" id="eDP" min="0" max="100" step="1"/></div>
            <div class="col-12"><div class="ds-lbl">Description</div><textarea class="ds-inp" name="description" id="eDsc" rows="2"></textarea></div>
            <div class="col-12"><div class="ds-lbl">Amenities</div><div class="d-flex flex-wrap gap-2 mt-1" id="eAmenWrap"></div></div>
            <div class="col-12">
              <div class="ds-lbl">Add More Images <span class="text-muted fw-400">(optional, JPG/JPEG/PNG/WEBP, max 5MB each)</span></div>
              <div class="img-drop" id="editDrop" onclick="document.getElementById('eImgIn').click()" ondragover="dragOver(event,'editDrop')" ondragleave="dragLeave('editDrop')" ondrop="dropFiles(event,'eImgIn','eNewGrid')">
                <i class="bi bi-cloud-arrow-up fs-4 text-primary"></i>
                <div class="fw-700 mt-1 small">Click or drag &amp; drop to add images</div>
                <div class="text-muted" style="font-size:.72rem">Adds to existing · Max 10 total</div>
              </div>
              <input type="file" id="eImgIn" name="room_images[]" multiple accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="buildPreviewGrid(this.files,'eNewGrid','add_new')"/>
              <div class="upload-grid" id="eNewGrid"></div>
              <!-- Existing images from DB -->
              <div id="eCurImgs" class="mt-3"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button><button type="submit" class="ds-btn prim" id="editBtn"><i class="bi bi-check-lg me-1"></i>Save Changes</button></div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade ds-modal" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye-fill me-2"></i>Room Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-4" id="viewBody"></div>
  </div></div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade ds-modal" id="delModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header" style="background:linear-gradient(135deg,#7f1d1d,var(--red))"><h5 class="modal-title text-white"><i class="bi bi-trash-fill me-2"></i>Delete Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-4"><p>Delete Room <strong id="delRN"></strong>? This cannot be undone.</p></div>
    <div class="modal-footer"><button class="ds-btn gho" data-bs-dismiss="modal">Cancel</button><button class="ds-btn" id="delBtn" style="background:var(--red);color:#fff;border-color:var(--red)"><i class="bi bi-trash-fill me-1"></i>Delete</button></div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
<script>
const amenDefs = <?php echo json_encode($amenity_defs); ?>;

// ── Drag & drop ───────────────────────────────────────────────────────────
function dragOver(e, id) { e.preventDefault(); document.getElementById(id).classList.add('drag-over'); }
function dragLeave(id)   { document.getElementById(id).classList.remove('drag-over'); }
function dropFiles(e, inputId, gridId) {
  e.preventDefault(); dragLeave(e.target.closest('.img-drop')?.id || '');
  const input = document.getElementById(inputId);
  const dt = new DataTransfer();
  // merge existing files
  if (input.files) Array.from(input.files).forEach(f => dt.items.add(f));
  Array.from(e.dataTransfer.files).forEach(f => { if (dt.files.length < 10) dt.items.add(f); });
  input.files = dt.files;
  buildPreviewGrid(input.files, gridId, gridId.startsWith('eNew') ? 'add_new' : 'add');
}

// ── Shared in-memory selected files (for add form) ───────────────────────
let _addFiles = new DataTransfer(); // tracks selected files for add form

function buildPreviewGrid(files, gridId, mode) {
  const grid = document.getElementById(gridId);
  const countEl = document.getElementById(gridId.replace('Grid','Count').replace('eNew','eNew'));
  const MAX = 10;
  let existing = grid.querySelectorAll('.upload-item').length;

  Array.from(files).forEach((file, idx) => {
    if (existing >= MAX) return;
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) return;
    if (file.size > 5*1024*1024) { dsToast(file.name+' exceeds 5MB','error'); return; }

    const reader = new FileReader();
    reader.onload = e => {
      const item = document.createElement('div');
      item.className = 'upload-item' + (existing===0?' is-primary':'');
      item.dataset.idx = existing;
      item.innerHTML = `
        <img src="${e.target.result}" alt=""/>
        ${existing===0?'<span class="img-badge">Primary</span>':''}
        <div class="img-actions">
          ${existing>0?`<button type="button" class="img-act-btn star" title="Set as primary" onclick="setNewPrimary(this)"><i class="bi bi-star-fill"></i></button>`:''}
          <button type="button" class="img-act-btn del" title="Remove" onclick="removePreviewItem(this,'${gridId}')"><i class="bi bi-trash-fill"></i></button>
        </div>`;
      grid.appendChild(item);
      existing++;
      updateCount(gridId);
    };
    reader.readAsDataURL(file);
  });
}

function setNewPrimary(btn) {
  const item  = btn.closest('.upload-item');
  const grid  = item.closest('.upload-grid');
  grid.querySelectorAll('.upload-item').forEach(i => {
    i.classList.remove('is-primary');
    i.querySelector('.img-badge')?.remove();
    const sb = i.querySelector('.img-act-btn.star');
    if (sb) sb.style.display='';
  });
  item.classList.add('is-primary');
  item.insertAdjacentHTML('afterbegin','<span class="img-badge">Primary</span>');
  const sb = item.querySelector('.img-act-btn.star');
  if (sb) sb.style.display='none';
  // Move item to front
  grid.prepend(item);
}

function removePreviewItem(btn, gridId) {
  btn.closest('.upload-item').remove();
  // Ensure first item is marked primary
  const grid  = document.getElementById(gridId);
  const first = grid.querySelector('.upload-item');
  if (first && !first.classList.contains('is-primary')) {
    first.classList.add('is-primary');
    if (!first.querySelector('.img-badge')) first.insertAdjacentHTML('afterbegin','<span class="img-badge">Primary</span>');
  }
  updateCount(gridId);
}

function updateCount(gridId) {
  const grid  = document.getElementById(gridId);
  const n     = grid.querySelectorAll('.upload-item').length;
  const cid   = gridId === 'addGrid' ? 'addCount' : null;
  if (cid) document.getElementById(cid).textContent = n > 0 ? n+' image'+(n>1?'s':'')+' selected' : '';
}

// ── Existing images in edit modal ─────────────────────────────────────────
let _editRoomId = null;

function renderExistingImages(images, rid) {
  const wrap = document.getElementById('eCurImgs');
  if (!images || !images.length) { wrap.innerHTML='<div class="text-muted small">No existing images</div>'; return; }
  let html = '<div class="ds-lbl mb-2">Existing Images <span class="text-muted fw-400">(click ★ to set primary, 🗑 to delete)</span></div>';
  html += '<div class="upload-grid" id="existingGrid">';
  images.forEach((path, idx) => {
    html += `<div class="upload-item ${idx===0?'is-primary':''}" id="ep_${idx}">
      <img src="${path}" alt="" onerror="this.src='https://via.placeholder.com/88x66?text=Error'"/>
      ${idx===0?'<span class="img-badge">Primary</span>':''}
      <div class="img-actions">
        ${idx>0?`<button type="button" class="img-act-btn star" title="Set as primary" onclick="setExistingPrimary(${rid},'${path}')"><i class="bi bi-star-fill"></i></button>`:''}
        <button type="button" class="img-act-btn del" title="Delete image" onclick="deleteExistingImg(${rid},'${path}',this)"><i class="bi bi-trash-fill"></i></button>
      </div>
    </div>`;
  });
  html += '</div>';
  wrap.innerHTML = html;
}

function setExistingPrimary(rid, path) {
  const fd = new FormData();
  fd.append('action','set_primary'); fd.append('room_id',rid); fd.append('img_path',path);
  fetch('admin-rooms.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.success){ renderExistingImages(d.images, rid); dsToast('Primary image updated','success'); }
    else dsToast('Failed: '+(d.error||'error'),'error');
  });
}

function deleteExistingImg(rid, path, btn) {
  if (!confirm('Delete this image?')) return;
  const fd = new FormData();
  fd.append('action','delete_image'); fd.append('room_id',rid); fd.append('img_path',path);
  fetch('admin-rooms.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.success){ renderExistingImages(d.images, rid); dsToast('Image deleted','success'); }
    else dsToast('Failed: '+(d.error||'error'),'error');
  });
}

// ── Amenity chips ─────────────────────────────────────────────────────────
function buildChips(wrap, sel) {
  wrap.innerHTML = '';
  const cur = sel ? sel.split(',').map(s=>s.trim()) : [];
  Object.entries(amenDefs).forEach(([k,[l,ic]]) => {
    const on = cur.includes(k);
    const lb = document.createElement('label');
    lb.className = 'amenity-chip' + (on?' selected':'');
    lb.innerHTML = `<input type="checkbox" name="amenities[]" value="${k}" ${on?'checked':''} onchange="this.closest('label').classList.toggle('selected',this.checked)"/><i class="bi ${ic}"></i> ${l}`;
    wrap.appendChild(lb);
  });
}

// ── View modal ────────────────────────────────────────────────────────────
function viewRoom(r) {
  const imgs  = r.room_images ? JSON.parse(r.room_images) : [];
  const disc  = parseFloat(r.discount_pct)||0, base = parseFloat(r.base_price)||0;
  const final = Math.round(base*(1-disc/100));
  const sel   = r.amenities ? r.amenities.split(',').map(s=>s.trim()) : [];
  const amenH = sel.map(k => { const d=amenDefs[k]; return d?`<span class="am-tag"><i class="bi ${d[1]}"></i> ${d[0]}</span>`:''; }).join('');
  const imgH  = imgs.length
    ? `<div class="upload-grid mb-3">${imgs.map((src,i)=>`<div class="upload-item ${i===0?'is-primary':''}">${i===0?'<span class="img-badge">Primary</span>':''}<img src="${src}" onerror="this.src='https://via.placeholder.com/88?text=No+Image'"></div>`).join('')}</div>`
    : '<div class="text-muted small mb-3">No images uploaded</div>';
  const sc = {available:'confirmed',occupied:'occupied',maintenance:'maintenance'};
  document.getElementById('viewBody').innerHTML = `
    ${imgH}
    <div class="row g-3">
      <div class="col-md-4"><div class="ds-lbl">Room Number</div><div class="fw-800 fs-5" style="color:var(--pr)">${r.room_number}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Type</div><div class="fw-600">${r.room_type}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Status</div><span class="ds-badge ${sc[r.status]||'available'}">${r.status}</span></div>
      <div class="col-md-3"><div class="ds-lbl">Floor</div><div>${r.floor}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Bed Type</div><div>${r.bed_type}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Adults</div><div>${r.adult_capacity}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Children</div><div>${r.child_capacity}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Base Price</div><div class="fw-700">&#8377;${base.toLocaleString('en-IN')}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Discount</div><div class="fw-700 text-danger">${disc}%</div></div>
      <div class="col-md-4"><div class="ds-lbl">Final Price</div><div class="fw-800" style="color:var(--grn);font-size:1.1rem">&#8377;${final.toLocaleString('en-IN')}</div></div>
      ${r.description?`<div class="col-12"><div class="ds-lbl">Description</div><div class="text-muted small">${r.description}</div></div>`:''}
      ${amenH?`<div class="col-12"><div class="ds-lbl">Amenities</div><div class="mt-1">${amenH}</div></div>`:''}
    </div>`;
  new bootstrap.Modal(document.getElementById('viewModal')).show();
}

// ── Edit modal ────────────────────────────────────────────────────────────
function editRoom(r) {
  _editRoomId = r.room_id;
  document.getElementById('eRoomId').value = r.room_id;
  document.getElementById('eRN').value     = r.room_number;
  document.getElementById('eFL').value     = r.floor;
  document.getElementById('eAC').value     = r.adult_capacity;
  document.getElementById('eCC').value     = r.child_capacity;
  document.getElementById('eBP').value     = r.base_price;
  document.getElementById('eDP').value     = r.discount_pct;
  document.getElementById('eDsc').value    = r.description||'';
  ['eRT','eBT','eST'].forEach(id => {
    const el = document.getElementById(id);
    const field = {eRT:'room_type',eBT:'bed_type',eST:'status'}[id];
    for(let o of el.options) if(o.value===r[field]){o.selected=true;break;}
  });
  buildChips(document.getElementById('eAmenWrap'), r.amenities||'');
  // Clear new image grid
  document.getElementById('eNewGrid').innerHTML = '';
  document.getElementById('eImgIn').value = '';
  // Render existing images
  const imgs = r.room_images ? JSON.parse(r.room_images) : [];
  renderExistingImages(imgs, r.room_id);
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ── Delete ────────────────────────────────────────────────────────────────
let _delId = null;
function confirmDel(id, num) { _delId=id; document.getElementById('delRN').textContent=num; new bootstrap.Modal(document.getElementById('delModal')).show(); }
document.getElementById('delBtn').addEventListener('click', () => {
  if (!_delId) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('room_id',_delId);
  fetch('admin-rooms.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.success){dsToast('Room deleted','success');bootstrap.Modal.getInstance(document.getElementById('delModal')).hide();setTimeout(()=>location.reload(),600);}
    else dsToast('Delete failed: '+(d.error||'error'),'error');
  }).catch(e=>dsToast('Network error','error'));
});

// ── Form submit handler ───────────────────────────────────────────────────
function postForm(formId, btnId, modalId) {
  document.getElementById(formId).addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const rn = form.querySelector('[name="room_number"]').value.trim();
    const bp = parseFloat(form.querySelector('[name="base_price"]').value);
    if (!rn) { dsToast('Room number is required','error'); return; }
    if (!bp || bp<=0) { dsToast('Base price must be greater than 0','error'); return; }

    // Validate image for add form
    if (formId === 'addForm') {
      const imgInput = document.getElementById('addImgIn');
      if (!imgInput.files || imgInput.files.length === 0) {
        dsToast('Please upload at least one room image.','error'); return;
      }
    }

    const fd = new FormData(form);
    const checked = [...form.querySelectorAll('input[name="amenities[]"]:checked')].map(c=>c.value);
    fd.delete('amenities[]'); fd.delete('amenities');
    fd.append('amenities', checked.join(','));

    const btn = document.getElementById(btnId);
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    btn.disabled = true;

    fetch('admin-rooms.php',{method:'POST',body:fd})
      .then(r=>r.text())
      .then(text=>{
        let d;
        try { d=JSON.parse(text); }
        catch(ex){ throw new Error('PHP error: '+text.substring(0,250)); }
        if(d.success){
          if(d.warnings) d.warnings.forEach(w=>dsToast(w,'error'));
          dsToast(formId==='addForm'?'Room added!':'Room updated!','success');
          bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
          if(formId==='addForm'){
            form.reset();
            document.getElementById('addGrid').innerHTML='';
            document.getElementById('addCount').textContent='';
            form.querySelectorAll('.amenity-chip').forEach(c=>c.classList.remove('selected'));
          }
          setTimeout(()=>location.reload(),700);
        } else {
          dsToast('Error: '+(d.error||'Unknown error'),'error');
          console.error('Server error:',d.error);
        }
        btn.innerHTML=formId==='addForm'?'<i class="bi bi-check-lg me-1"></i>Add Room':'<i class="bi bi-check-lg me-1"></i>Save Changes';
        btn.disabled=false;
      })
      .catch(err=>{
        dsToast('Failed: '+err.message,'error');
        console.error(err);
        btn.innerHTML=formId==='addForm'?'<i class="bi bi-check-lg me-1"></i>Add Room':'<i class="bi bi-check-lg me-1"></i>Save Changes';
        btn.disabled=false;
      });
  });
}

postForm('addForm','addBtn','addModal');
postForm('editForm','editBtn','editModal');
</script>
</body></html>d = new FileReader(); rd.onload = e => { const img = document.createElement('img'); img.src = e.target.result; wrap.appendChild(img); }; rd.readAsDataURL(f); });
}

function buildChips(wrap, sel) {
  wrap.innerHTML = '';
  const cur = sel ? sel.split(',').map(s=>s.trim()) : [];
  Object.entries(amenDefs).forEach(([k,[l,ic]]) => {
    const on = cur.includes(k);
    const lb = document.createElement('label');
    lb.className = 'amenity-chip' + (on?' selected':'');
    lb.innerHTML = `<input type="checkbox" name="amenities[]" value="${k}" ${on?'checked':''} onchange="this.closest('label').classList.toggle('selected',this.checked)"/><i class="bi ${ic}"></i> ${l}`;
    wrap.appendChild(lb);
  });
}

function viewRoom(r) {
  const imgs = r.room_images ? JSON.parse(r.room_images) : [];
  const disc = parseFloat(r.discount_pct)||0, base = parseFloat(r.base_price)||0;
  const final = Math.round(base*(1-disc/100));
  const sel = r.amenities ? r.amenities.split(',').map(s=>s.trim()) : [];
  const amenH = sel.map(k => { const d=amenDefs[k]; return d?`<span class="am-tag"><i class="bi ${d[1]}"></i> ${d[0]}</span>`:''; }).join('');
  const imgH = imgs.length ? imgs.map(i=>`<img src="${i}" style="width:100px;height:75px;object-fit:cover;border-radius:8px;border:1.5px solid var(--bdr)" onerror="this.style.display='none'">`).join(' ') : '<span class="text-muted small">No images</span>';
  const sc = {available:'confirmed',occupied:'occupied',maintenance:'maintenance'};
  document.getElementById('viewBody').innerHTML = `
    <div class="row g-3">
      <div class="col-12">${imgH}</div>
      <div class="col-md-4"><div class="ds-lbl">Room Number</div><div class="fw-800 fs-5" style="color:var(--pr)">${r.room_number}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Type</div><div class="fw-600">${r.room_type}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Status</div><span class="ds-badge ${sc[r.status]||'available'}">${r.status}</span></div>
      <div class="col-md-3"><div class="ds-lbl">Floor</div><div>${r.floor}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Bed Type</div><div>${r.bed_type}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Adults</div><div>${r.adult_capacity}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Children</div><div>${r.child_capacity}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Base Price</div><div class="fw-700">Rs.${base.toLocaleString('en-IN')}</div></div>
      <div class="col-md-4"><div class="ds-lbl">Discount</div><div class="fw-700 text-danger">${disc}%</div></div>
      <div class="col-md-4"><div class="ds-lbl">Final Price</div><div class="fw-800" style="color:var(--grn);font-size:1.1rem">Rs.${final.toLocaleString('en-IN')}</div></div>
      ${r.description?`<div class="col-12"><div class="ds-lbl">Description</div><div class="text-muted small">${r.description}</div></div>`:''}
      ${amenH?`<div class="col-12"><div class="ds-lbl">Amenities</div><div class="mt-1">${amenH}</div></div>`:''}
    </div>`;
  new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function editRoom(r) {
  document.getElementById('eRoomId').value = r.room_id;
  document.getElementById('eRN').value     = r.room_number;
  document.getElementById('eFL').value     = r.floor;
  document.getElementById('eAC').value     = r.adult_capacity;
  document.getElementById('eCC').value     = r.child_capacity;
  document.getElementById('eBP').value     = r.base_price;
  document.getElementById('eDP').value     = r.discount_pct;
  document.getElementById('eDsc').value    = r.description||'';
  ['eRT','eBT','eST'].forEach(id => {
    const el = document.getElementById(id);
    const field = {eRT:'room_type',eBT:'bed_type',eST:'status'}[id];
    for(let o of el.options) if(o.value===r[field]){o.selected=true;break;}
  });
  buildChips(document.getElementById('eAmenWrap'), r.amenities||'');
  const imgs = r.room_images ? JSON.parse(r.room_images) : [];
  document.getElementById('eCurImgs').innerHTML = imgs.length
    ? '<div class="ds-lbl mt-2">Current Images</div><div class="img-prev">' + imgs.map(i=>`<img src="${i}" onerror="this.style.display='none'">`).join('') + '</div>' : '';
  document.getElementById('ePrev').innerHTML = '';
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

let _delId = null;
function confirmDel(id, num) { _delId=id; document.getElementById('delRN').textContent=num; new bootstrap.Modal(document.getElementById('delModal')).show(); }
document.getElementById('delBtn').addEventListener('click', () => {
  if (!_delId) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('room_id',_delId);
  fetch('admin-rooms.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.success){dsToast('Room deleted','success');bootstrap.Modal.getInstance(document.getElementById('delModal')).hide();setTimeout(()=>location.reload(),600);}
    else dsToast('Delete failed: '+(d.error||'error'),'error');
  }).catch(e=>dsToast('Network error: '+e.message,'error'));
});

function postForm(formId, btnId, modalId) {
  const form = document.getElementById(formId);
  const btn  = document.getElementById(btnId);
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    // Client-side validation
    const rn = form.querySelector('[name="room_number"]').value.trim();
    const bp = parseFloat(form.querySelector('[name="base_price"]').value);
    if (!rn) { dsToast('Room number is required','error'); return; }
    if (!bp || bp <= 0) { dsToast('Base price must be greater than 0','error'); return; }

    const fd = new FormData(form);
    // Fix amenities: collect checked values, remove array keys, set single value
    const checked = [...form.querySelectorAll('input[name="amenities[]"]:checked')].map(c=>c.value);
    fd.delete('amenities[]');
    fd.delete('amenities');
    fd.append('amenities', checked.join(','));

    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    btn.disabled = true;

    fetch('admin-rooms.php', {method:'POST', body:fd})
      .then(r => r.text())
      .then(text => {
        let d;
        try { d = JSON.parse(text); }
        catch(ex) { throw new Error('PHP error: ' + text.substring(0,200)); }
        if (d.success) {
          dsToast(formId==='addForm'?'Room added successfully!':'Room updated!','success');
          bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
          if (formId==='addForm') { form.reset(); document.getElementById('addPrev').innerHTML=''; form.querySelectorAll('.amenity-chip').forEach(c=>c.classList.remove('selected')); }
          setTimeout(()=>location.reload(), 700);
        } else {
          dsToast('Error: '+(d.error||'Unknown error'),'error');
          console.error('Server error:', d.error);
        }
        btn.innerHTML = formId==='addForm'?'<i class="bi bi-check-lg me-1"></i>Add Room':'<i class="bi bi-check-lg me-1"></i>Save Changes';
        btn.disabled = false;
      })
      .catch(err => {
        dsToast('Failed: '+err.message,'error');
        console.error(err);
        btn.innerHTML = formId==='addForm'?'<i class="bi bi-check-lg me-1"></i>Add Room':'<i class="bi bi-check-lg me-1"></i>Save Changes';
        btn.disabled = false;
      });
  });
}

postForm('addForm',  'addBtn',  'addModal');
postForm('editForm', 'editBtn', 'editModal');
</script>
</body></html>
