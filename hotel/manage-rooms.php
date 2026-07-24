<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'hotel_functions.php';

// Auth check or default manager ID
$manager_id = (int)($_SESSION['hm_id'] ?? $_SESSION['user_id'] ?? 1);

// Resolve hotel_id for logged in manager
$hotel_id = 1;
if ($manager_id > 0 && isset($conn)) {
    $h_res = mysqli_query($conn, "SELECT hotel_id FROM hotels WHERE assigned_to = $manager_id LIMIT 1");
    if ($h_res && $row = mysqli_fetch_assoc($h_res)) {
        $hotel_id = (int)$row['hotel_id'];
    }
}

// POST AJAX Handler - Guarantee JSON Output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    try {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        if ($action === 'add_room') {
            $room_number  = trim($_POST['room_number'] ?? '');
            $room_type    = trim($_POST['room_type'] ?? 'Standard');
            $floor        = trim($_POST['floor'] ?? '1st');
            $base_price   = (float)($_POST['base_price'] ?? 0);
            $discount_pct = (float)($_POST['discount_percent'] ?? 0);
            $capacity     = trim($_POST['capacity'] ?? '2 Adults');
            $bed_type     = trim($_POST['bed_type'] ?? 'Double');
            $status       = trim($_POST['status'] ?? 'Available');
            $amenities    = trim($_POST['amenities'] ?? '');
            $description  = trim($_POST['description'] ?? '');

            // Validation
            if (empty($room_number)) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Room Number is required.', 'error' => 'Room Number is required.']);
                exit();
            }
            if ($base_price <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Base Price must be greater than 0.', 'error' => 'Base Price must be greater than 0.']);
                exit();
            }

            // Ensure database schema columns exist
            $col_chk = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'manager_id'");
            if ($col_chk && mysqli_num_rows($col_chk) === 0) {
                mysqli_query($conn, "ALTER TABLE `rooms` ADD COLUMN `manager_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 AFTER `hotel_id`");
            }
            $col_chk2 = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'room_name'");
            if ($col_chk2 && mysqli_num_rows($col_chk2) === 0) {
                mysqli_query($conn, "ALTER TABLE `rooms` ADD COLUMN `room_name` VARCHAR(150) DEFAULT NULL AFTER `room_type`");
            }

            // Duplicate room check
            $rn_safe = mysqli_real_escape_string($conn, $room_number);
            $check = mysqli_query($conn, "SELECT room_id FROM rooms WHERE room_number = '$rn_safe' AND hotel_id = $hotel_id LIMIT 1");
            if ($check && mysqli_num_rows($check) > 0) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "Room '$room_number' already exists in this hotel.", 'error' => "Room '$room_number' already exists in this hotel."]);
                exit();
            }

            // Upload images
            $uploaded_images = [];
            if (!empty($_FILES['room_images']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/rooms/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                foreach ($_FILES['room_images']['tmp_name'] as $i => $tmp_name) {
                    if (!empty($tmp_name) && isset($_FILES['room_images']['error'][$i]) && $_FILES['room_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['room_images']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $filename = 'room_' . time() . '_' . uniqid() . '.' . $ext;
                            if (@move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                                $uploaded_images[] = 'uploads/rooms/' . $filename;
                            }
                        }
                    }
                }
            }

            // Default image fallback if none provided
            if (empty($uploaded_images)) {
                $uploaded_images[] = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&q=80';
            }

            $room_images_json = json_encode($uploaded_images);
            $final_price = round($base_price * (1 - ($discount_pct / 100)), 2);

            // Adult and child capacity parsing
            $adult_cap = 2;
            $child_cap = 0;
            if (preg_match('/(\d+)\s*Adult/i', $capacity, $m)) {
                $adult_cap = (int)$m[1];
            }
            if (preg_match('/(\d+)\s*Child/i', $capacity, $m)) {
                $child_cap = (int)$m[1];
            }

            // Prepared statement INSERT query
            $sql = "INSERT INTO rooms 
                (hotel_id, manager_id, room_number, room_type, room_name, floor, adult_capacity, child_capacity, bed_type, base_price, discount_percent, final_price, description, amenities, room_images, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                $err_msg = 'Database prepare failed: ' . mysqli_error($conn);
                ob_clean();
                echo json_encode(['success' => false, 'message' => $err_msg, 'error' => $err_msg]);
                exit();
            }

            $room_name = $room_type . ' ' . $room_number;
            
            // Types: i (hotel_id), i (manager_id), s (room_number), s (room_type), s (room_name), s (floor), i (adult_capacity), i (child_capacity), s (bed_type), d (base_price), d (discount_percent), d (final_price), s (description), s (amenities), s (room_images), s (status)
            mysqli_stmt_bind_param($stmt, 'iissssiisdddssss',
                $hotel_id, $manager_id, $room_number, $room_type, $room_name, $floor,
                $adult_cap, $child_cap, $bed_type, $base_price, $discount_pct, $final_price,
                $description, $amenities, $room_images_json, $status
            );

            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                // Fetch newly inserted room
                $new_room = bhGetRoomById($new_id);

                // Fetch updated stats
                $stats = bhRoomStats($hotel_id);

                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Room added successfully.',
                    'error'   => null,
                    'room'    => $new_room,
                    'stats'   => $stats
                ]);
            } else {
                $db_error = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $db_error,
                    'error'   => 'Database error: ' . $db_error
                ]);
            }
            exit();
        }

        if ($action === 'delete_room') {
            $room_id = (int)($_POST['room_id'] ?? 0);
            if ($room_id > 0) {
                $ok = bhDeleteRoom($room_id);
                $stats = bhRoomStats($hotel_id);
                ob_clean();
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Room deleted successfully.', 'stats' => $stats]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete room: ' . mysqli_error($conn), 'error' => 'Failed to delete room: ' . mysqli_error($conn)]);
                }
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid Room ID', 'error' => 'Invalid Room ID']);
            }
            exit();
        }

        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action', 'error' => 'Invalid action']);
        exit();

    } catch (Throwable $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'PHP Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            'error'   => 'PHP Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
        ]);
        exit();
    }
}

// Get live stats & rooms for current hotel
$stats = bhRoomStats($hotel_id);
$rooms = bhGetRoomsByHotel($hotel_id);
?>
<?php
require_once 'db.php';
require_once 'auth_guard.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/><title>Room Management -- Hotel Manager</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/><link rel="stylesheet" href="dashboard.css"/><style>
.img-drop{border:2px dashed var(--bdr);border-radius:10px;padding:1.25rem;text-align:center;cursor:pointer;background:var(--srf);transition:.2s}
.img-drop:hover,.img-drop.drag-over{border-color:var(--pr);background:var(--pr-lt)}
.upload-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:.6rem;margin-top:.75rem}
.upload-item{position:relative;border-radius:10px;overflow:hidden;border:2px solid var(--bdr);aspect-ratio:4/3;background:var(--srf)}
.upload-item img{width:100%;height:100%;object-fit:cover;display:block}
.upload-item .img-actions{position:absolute;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;gap:.3rem;opacity:0;transition:.2s}
.upload-item:hover .img-actions{opacity:1}
.img-act-btn{width:28px;height:28px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;transition:.15s}
.img-act-btn.del{background:var(--red);color:#fff}
.img-act-btn:hover{transform:scale(1.12)}
.upload-count{font-size:.72rem;font-weight:600;color:var(--mut);margin-top:.4rem}
</style></head><body><div class="ds-ov" id="dsOv"></div><aside class="ds-sb" id="dsSb"><a href="admin-dashboard.php" class="ds-logo"><div class="ds-logo-icon"><i class="bi bi-building-fill"></i></div><div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Manager Portal</div></div></a><nav class="ds-nav" id="mainSidebar">
      <div class="ds-sec">Main</div>
      <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
      <a href="manage-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Manage Bookings</a>
      <a href="check-in-order.php" class="ds-link"><i class="bi bi-person-check-fill"></i> Check In Order</a>
      <a href="manage-hotel-listing.php" class="ds-link"><i class="bi bi-card-checklist"></i> Manage Hotel Listing</a>
      <a href="manage-rooms.php" class="ds-link active"><i class="bi bi-door-open-fill"></i> Manage Rooms</a>
      <a href="view-ratings.php" class="ds-link"><i class="bi bi-star-fill"></i> View Ratings</a>
      <a href="transaction-history.php" class="ds-link"><i class="bi bi-cash-stack"></i> Transaction History</a>
      <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
    <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script><div class="ds-foot"><a href="admin-hotel-profile.php" class="ds-hpill"><div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"><?= $manager_initials ?></div><div><div class="ds-hpill-name"><?= htmlspecialchars($manager_name) ?></div><div class="ds-hpill-status"><?= htmlspecialchars($manager_role) ?></div></div></a></div></aside><header class="ds-top"><div class="ds-top-l"><button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button><div><div class="ds-page-title">Room Management</div><div class="ds-breadcrumb">Dashboard / Room Management</div></div></div><div class="ds-top-r"><a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i><span class="ds-dot"></span></a><div class="ds-avbtn" id="dsAvBtn"><div class="ds-av"><?= $manager_initials ?></div><span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span><div class="ds-dropdown" id="dsAvMenu"><a href="profile.php" class="ds-drop-item"><i class="bi bi-person-fill text-primary"></i> My Profile</a><hr class="my-1 mx-2"/><a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a></div></div></div></header><main class="ds-main">
  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-door-open-fill"></i></div><div class="ds-sn" id="statTotal"><?= $stats['total'] ?></div><div class="ds-sl">Total Rooms</div></div></div>
    <div class="col-6 col-md-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div><div class="ds-sn" id="statAvailable"><?= $stats['available'] ?></div><div class="ds-sl">Available</div></div></div>
    <div class="col-6 col-md-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-person-fill"></i></div><div class="ds-sn" id="statOccupied"><?= $stats['occupied'] ?></div><div class="ds-sl">Occupied</div></div></div>
    <div class="col-6 col-md-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-tools"></i></div><div class="ds-sn" id="statMaintenance"><?= $stats['maintenance'] ?></div><div class="ds-sl">Maintenance</div></div></div>
  </div>

  <!-- Rooms Table -->
  <div class="ds-card">
    <div class="ds-ch">
      <div class="ds-ct"><i class="bi bi-door-open-fill"></i> All Rooms</div>
      <div class="d-flex gap-2 flex-wrap">
        <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i><input class="ds-inp search" placeholder="Search rooms…" style="width:200px" oninput="filterRooms(this.value)"/></div>
        <select class="ds-inp ds-sel" style="width:auto" onchange="filterRoomType(this.value)">
          <option value="">All Types</option><option>Standard</option><option>Deluxe</option><option>Suite</option><option>Presidential</option>
        </select>
        <button class="ds-btn prim" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="bi bi-plus-lg"></i> Add Room</button>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="ds-tbl" id="roomTable">
        <thead><tr><th>Room No.</th><th>Preview</th><th>Type</th><th>Floor</th><th>Capacity</th><th>Price/Night</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="roomTableBody">
          <?php if (empty($rooms)): ?>
          <tr id="noRoomsRow"><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-door-open fs-3 opacity-50 d-block mb-2"></i>No rooms found in database. Click "Add Room" to create one.</td></tr>
          <?php else: ?>
          <?php foreach ($rooms as $r):
              $imgs = !empty($r['room_images']) ? json_decode($r['room_images'], true) : [];
              $img_url = (!empty($imgs) && is_array($imgs)) ? $imgs[0] : 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=80&q=80';
              $cap = ($r['adult_capacity'] ?? 2) . ' Adult' . (($r['adult_capacity'] ?? 2) > 1 ? 's' : '');
              if (!empty($r['child_capacity'])) {
                  $cap .= ', ' . $r['child_capacity'] . ' Child' . ($r['child_capacity'] > 1 ? 'ren' : '');
              }
              $status_class = strtolower($r['status'] ?? 'available');
          ?>
          <tr data-type="<?= htmlspecialchars($r['room_type']) ?>" data-room-id="<?= $r['room_id'] ?>">
            <td class="fw-700"><?= htmlspecialchars($r['room_number']) ?></td>
            <td><img src="<?= htmlspecialchars($img_url) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:6px" alt=""/></td>
            <td><?= htmlspecialchars($r['room_type']) ?></td>
            <td><?= htmlspecialchars($r['floor'] ?? '1st') ?></td>
            <td><?= htmlspecialchars($cap) ?></td>
            <td class="fw-700 text-primary">₹<?= number_format($r['base_price']) ?></td>
            <td><span class="ds-badge <?= $status_class ?>"><?= ucfirst($r['status']) ?></span></td>
            <td><div class="d-flex gap-1">
              <button class="ds-btn gho ico" data-bs-toggle="modal" data-bs-target="#editRoomModal" title="Edit"><i class="bi bi-pencil-fill"></i></button>
              <button class="ds-btn dng ico" onclick="deleteRoom(<?= $r['room_id'] ?>, '<?= htmlspecialchars($r['room_number']) ?>', this)" title="Delete"><i class="bi bi-trash-fill"></i></button>
            </div></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Add Room Modal -->
<div class="modal fade ds-modal" id="addRoomModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Add New Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form id="addRoomForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_room"/>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4"><label class="ds-lbl">Room Number *</label><input class="ds-inp" name="room_number" placeholder="e.g. 205" required/></div>
            <div class="col-md-4"><label class="ds-lbl">Room Type</label>
              <select class="ds-inp ds-sel" name="room_type">
                <option>Standard Single</option><option>Standard Twin</option><option>Deluxe King</option><option>Deluxe Twin</option><option>Ocean Suite</option><option>Deluxe Suite</option><option>Presidential Suite</option>
              </select>
            </div>
            <div class="col-md-4"><label class="ds-lbl">Floor</label><input class="ds-inp" name="floor" placeholder="e.g. 2nd"/></div>
            <div class="col-md-4"><label class="ds-lbl">Price / Night (₹) *</label><input type="number" class="ds-inp" name="base_price" placeholder="5500" required min="1"/></div>
            <div class="col-md-4"><label class="ds-lbl">Capacity</label><input class="ds-inp" name="capacity" placeholder="e.g. 2 Adults"/></div>
            <div class="col-md-4"><label class="ds-lbl">Status</label>
              <select class="ds-inp ds-sel" name="status"><option value="Available">Available</option><option value="Occupied">Occupied</option><option value="Maintenance">Maintenance</option></select>
            </div>
            <div class="col-12"><label class="ds-lbl">Amenities</label><input class="ds-inp" name="amenities" placeholder="WiFi, AC, TV, Mini-bar, Safe…"/></div>
            <div class="col-12"><label class="ds-lbl">Description</label><textarea class="ds-inp" name="description" rows="3" placeholder="Describe the room…"></textarea></div>
            <div class="col-12">
              <label class="ds-lbl">Room Images <span class="text-muted fw-400">(select from folder, up to 10)</span></label>
              <div class="img-drop" id="roomImgDrop" onclick="document.getElementById('roomImgInput').click()" ondragover="event.preventDefault();this.classList.add('drag-over')" ondragleave="this.classList.remove('drag-over')" ondrop="handleDrop(event)">
                <i class="bi bi-cloud-arrow-up fs-3 text-primary"></i>
                <div class="fw-700 mt-1">Click to browse or drag &amp; drop</div>
                <div class="text-muted small">JPG, JPEG, PNG, WEBP — From your computer</div>
              </div>
              <input type="file" id="roomImgInput" name="room_images[]" multiple accept=".jpg,.jpeg,.png,.webp,image/*" class="d-none" onchange="buildPreview(this.files,'roomImgGrid','roomImgDrop')"/>
              <div class="upload-grid" id="roomImgGrid"></div>
              <div class="upload-count" id="roomImgCount"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ds-btn prim" id="addRoomSubmitBtn"><i class="bi bi-plus-lg"></i> Add Room</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade ds-modal" id="editRoomModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-md-4"><label class="ds-lbl">Room Number</label><input class="ds-inp" value="201"/></div>
          <div class="col-md-4"><label class="ds-lbl">Room Type</label>
            <select class="ds-inp ds-sel"><option selected>Deluxe King</option><option>Standard Single</option><option>Standard Twin</option><option>Suite</option><option>Presidential Suite</option></select>
          </div>
          <div class="col-md-4"><label class="ds-lbl">Floor</label><input class="ds-inp" value="2nd"/></div>
          <div class="col-md-4"><label class="ds-lbl">Price / Night (₹)</label><input type="number" class="ds-inp" value="5500"/></div>
          <div class="col-md-4"><label class="ds-lbl">Capacity</label><input class="ds-inp" value="2 Adults"/></div>
          <div class="col-md-4"><label class="ds-lbl">Status</label>
            <select class="ds-inp ds-sel"><option>Available</option><option selected>Occupied</option><option>Maintenance</option></select>
          </div>
          <div class="col-12"><label class="ds-lbl">Amenities</label><input class="ds-inp" value="WiFi, AC, 55&quot; TV, Mini-bar, Safe, King Bed"/></div>
          <div class="col-12"><label class="ds-lbl">Description</label><textarea class="ds-inp" rows="3">Spacious king-bed room with city view, premium bath amenities and 24-hour room service.</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="ds-btn prim" onclick="dsToast('Room updated!','success')" data-bs-dismiss="modal"><i class="bi bi-check-lg"></i> Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
function filterRooms(q){
  q=q.toLowerCase();
  document.querySelectorAll('#roomTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none'});
}
function filterRoomType(t){
  document.querySelectorAll('#roomTable tbody tr').forEach(r=>{r.style.display=(!t||r.dataset.type===t)?'':'none'});
}

function buildPreview(files, gridId, dropId) {
  const grid = document.getElementById(gridId);
  const countEl = document.getElementById(gridId.replace('Grid','Count'));
  const drop = document.getElementById(dropId);
  const MAX = 10;
  let existing = grid.querySelectorAll('.upload-item').length;
  if (existing >= MAX) {
    dsToast('Maximum 10 images allowed', 'error');
    return;
  }
  Array.from(files).forEach(file => {
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) return;
    if (file.size > 5*1024*1024) { dsToast(file.name+' exceeds 5MB', 'error'); return; }
    if (existing >= MAX) return;
    const reader = new FileReader();
    reader.onload = e => {
      const item = document.createElement('div');
      item.className = 'upload-item';
      item.innerHTML = '<img src="'+e.target.result+'" alt=""/><div class="img-actions"><button type="button" class="img-act-btn del" title="Remove" onclick="removeItem(this)"><i class="bi bi-trash-fill"></i></button></div>';
      grid.appendChild(item);
      existing++;
      updateCount();
    };
    reader.readAsDataURL(file);
  });
  updateCount();
}
function removeItem(btn) {
  const item = btn.closest('.upload-item');
  item.remove();
  updateCount();
}
function updateCount() {
  const grid = document.getElementById('roomImgGrid');
  const countEl = document.getElementById('roomImgCount');
  const n = grid ? grid.querySelectorAll('.upload-item').length : 0;
  if (countEl) {
    countEl.textContent = n > 0 ? n + '/10 images selected' : '';
  }
}
function handleDrop(e) {
  e.preventDefault();
  const drop = document.getElementById('roomImgDrop');
  drop.classList.remove('drag-over');
  const input = document.getElementById('roomImgInput');
  const dt = new DataTransfer();
  if (input.files) Array.from(input.files).forEach(f => dt.items.add(f));
  Array.from(e.dataTransfer.files).forEach(f => { if (dt.files.length < 10) dt.items.add(f); });
  input.files = dt.files;
  buildPreview(input.files, 'roomImgGrid', 'roomImgDrop');
}

// Submit Add Room Form via AJAX
document.getElementById('addRoomForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const form = this;
  const btn = document.getElementById('addRoomSubmitBtn');
  const fd = new FormData(form);

  // Client validation
  const roomNum = fd.get('room_number')?.toString().trim();
  const basePrice = parseFloat(fd.get('base_price')?.toString() || '0');

  if (!roomNum) {
    dsToast('Please enter a room number', 'error');
    return;
  }
  if (!basePrice || basePrice <= 0) {
    dsToast('Please enter a valid price per night', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding Room...';

  fetch('manage-rooms.php', {
    method: 'POST',
    body: fd
  })
  .then(r => r.text())
  .then(text => {
    let d;
    try {
      d = JSON.parse(text);
    } catch(err) {
      console.error('Non-JSON Server Output:', text);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-plus-lg"></i> Add Room';
      dsToast('Server response error: ' + text.substring(0, 150), 'error');
      return;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-plus-lg"></i> Add Room';

    if (d.success) {
      dsToast(d.message || 'Room added successfully.', 'success');

      // Close modal automatically
      const modalEl = document.getElementById('addRoomModal');
      const modalInst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      if (modalInst) modalInst.hide();

      // Reset form & preview grid
      form.reset();
      document.getElementById('roomImgGrid').innerHTML = '';
      document.getElementById('roomImgCount').textContent = '';

      // Update stat counters live
      if (d.stats) {
        if (document.getElementById('statTotal')) document.getElementById('statTotal').textContent = d.stats.total;
        if (document.getElementById('statAvailable')) document.getElementById('statAvailable').textContent = d.stats.available;
        if (document.getElementById('statOccupied')) document.getElementById('statOccupied').textContent = d.stats.occupied;
        if (document.getElementById('statMaintenance')) document.getElementById('statMaintenance').textContent = d.stats.maintenance;
      }

      // Append new room to table immediately
      if (d.room) {
        const noRoomsRow = document.getElementById('noRoomsRow');
        if (noRoomsRow) noRoomsRow.remove();

        const r = d.room;
        let imgs = [];
        try { imgs = typeof r.room_images === 'string' ? JSON.parse(r.room_images) : (r.room_images || []); } catch(_) {}
        const imgUrl = (Array.isArray(imgs) && imgs.length > 0) ? imgs[0] : 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=80&q=80';
        const statusClass = (r.status || 'available').toLowerCase();
        const cap = (r.adult_capacity || 2) + ' Adult' + ((r.adult_capacity || 2) > 1 ? 's' : '');

        const tr = document.createElement('tr');
        tr.dataset.type = r.room_type || 'Standard';
        tr.dataset.roomId = r.room_id;
        tr.innerHTML = `
          <td class="fw-700">${escapeHtml(r.room_number)}</td>
          <td><img src="${escapeHtml(imgUrl)}" style="width:60px;height:40px;object-fit:cover;border-radius:6px" alt=""/></td>
          <td>${escapeHtml(r.room_type)}</td>
          <td>${escapeHtml(r.floor || '1st')}</td>
          <td>${escapeHtml(cap)}</td>
          <td class="fw-700 text-primary">₹${Number(r.base_price).toLocaleString()}</td>
          <td><span class="ds-badge ${statusClass}">${escapeHtml(r.status)}</span></td>
          <td><div class="d-flex gap-1">
            <button class="ds-btn gho ico" data-bs-toggle="modal" data-bs-target="#editRoomModal" title="Edit"><i class="bi bi-pencil-fill"></i></button>
            <button class="ds-btn dng ico" onclick="deleteRoom(${r.room_id}, '${escapeHtml(r.room_number)}', this)" title="Delete"><i class="bi bi-trash-fill"></i></button>
          </div></td>
        `;
        document.getElementById('roomTableBody').prepend(tr);
      }
    } else {
      dsToast(d.message || d.error || 'Failed to add room', 'error');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-plus-lg"></i> Add Room';
    dsToast('Request failed: ' + err.message, 'error');
  });
});

function deleteRoom(roomId, roomNum, btn) {
  if (!confirm('Are you sure you want to delete Room ' + roomNum + '?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_room');
  fd.append('room_id', roomId);

  fetch('manage-rooms.php', { method: 'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      dsToast(d.message || 'Room deleted successfully', 'success');
      btn.closest('tr').remove();
      if (d.stats) {
        if (document.getElementById('statTotal')) document.getElementById('statTotal').textContent = d.stats.total;
        if (document.getElementById('statAvailable')) document.getElementById('statAvailable').textContent = d.stats.available;
        if (document.getElementById('statOccupied')) document.getElementById('statOccupied').textContent = d.stats.occupied;
        if (document.getElementById('statMaintenance')) document.getElementById('statMaintenance').textContent = d.stats.maintenance;
      }
    } else {
      dsToast(d.message || d.error || 'Failed to delete room', 'error');
    }
  })
  .catch(err => dsToast('Network error: ' + err.message, 'error'));
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script><script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" crossorigin="anonymous"></script><script src="dashboard.js"></script></body></html>
