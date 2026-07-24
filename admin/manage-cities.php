<?php
require_once 'auth_guard.php';
require_once 'db.php';

if (!isset($_SESSION['admin_id']) || !($admin = getCurrentAdmin())) {
    header('Location: login.php');
    exit();
}

$db_admin_id = (int)$_SESSION['admin_id'];

// ── AJAX Handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'create_city') {
        $city_name = trim($_POST['city_name'] ?? '');
        $state     = trim($_POST['state'] ?? '');
        $country   = trim($_POST['country'] ?? 'India');
        $desc      = trim($_POST['description'] ?? '');
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';
        $popular   = isset($_POST['is_popular']) ? 1 : 0;

        if (!$city_name) {
            echo json_encode(['success'=>false,'message'=>'City name is required.']); exit;
        }

        $chk = mysqli_prepare($conn, "SELECT id FROM cities WHERE city_name = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, 's', $city_name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            mysqli_stmt_close($chk);
            echo json_encode(['success'=>false,'message'=>'A city with this name already exists.']); exit;
        }
        mysqli_stmt_close($chk);

        $image_path = admin_handleCityImageUpload(0);

        $stmt = mysqli_prepare($conn, "INSERT INTO cities (city_name, state, country, city_image, description, status, is_popular, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssssii', $city_name, $state, $country, $image_path, $desc, $status, $popular, $db_admin_id);
        $ok = mysqli_stmt_execute($stmt);
        $new_id = $ok ? mysqli_insert_id($conn) : 0;
        mysqli_stmt_close($stmt);

        echo json_encode(['success'=>$ok,'message'=>$ok?'City added successfully!':'Database error.','city_id'=>$new_id]);
        exit;
    }

    if ($action === 'get_city') {
        $cid = (int)($_POST['city_id'] ?? 0);
        $st = mysqli_prepare($conn, "SELECT id, city_name, state, country, city_image, description, status, is_popular FROM cities WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $cid);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $city = mysqli_fetch_assoc($res);
        mysqli_stmt_close($st);
        echo json_encode($city ?: ['error'=>'Not found']);
        exit;
    }

    if ($action === 'update_city') {
        $city_id   = (int)($_POST['city_id'] ?? 0);
        $city_name = trim($_POST['city_name'] ?? '');
        $state     = trim($_POST['state'] ?? '');
        $country   = trim($_POST['country'] ?? 'India');
        $desc      = trim($_POST['description'] ?? '');
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';
        $popular   = isset($_POST['is_popular']) ? 1 : 0;

        if (!$city_id || !$city_name) {
            echo json_encode(['success'=>false,'message'=>'City name is required.']); exit;
        }

        $chk = mysqli_prepare($conn, "SELECT id FROM cities WHERE city_name = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($chk, 'si', $city_name, $city_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            mysqli_stmt_close($chk);
            echo json_encode(['success'=>false,'message'=>'Another city with this name already exists.']); exit;
        }
        mysqli_stmt_close($chk);

        $image_path = '';
        if (isset($_FILES['city_image']) && $_FILES['city_image']['error'] === UPLOAD_ERR_OK) {
            $image_path = admin_handleCityImageUpload($city_id);
            $old = mysqli_prepare($conn, "SELECT city_image FROM cities WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($old, 'i', $city_id);
            mysqli_stmt_execute($old);
            $old_res = mysqli_stmt_get_result($old);
            $old_row = mysqli_fetch_assoc($old_res);
            mysqli_stmt_close($old);
            if (!empty($old_row['city_image']) && file_exists(__DIR__ . '/' . $old_row['city_image'])) {
                @unlink(__DIR__ . '/' . $old_row['city_image']);
            }
        }

        if (!empty($image_path)) {
            $stmt = mysqli_prepare($conn, "UPDATE cities SET city_name=?, state=?, country=?, city_image=?, description=?, status=?, is_popular=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssii', $city_name, $state, $country, $image_path, $desc, $status, $popular, $city_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE cities SET city_name=?, state=?, country=?, description=?, status=?, is_popular=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssii', $city_name, $state, $country, $desc, $status, $popular, $city_id);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success'=>$ok,'message'=>$ok?'City updated successfully!':'Database error.']);
        exit;
    }

    if ($action === 'delete_city') {
        $city_id = (int)($_POST['city_id'] ?? 0);
        if (!$city_id) {
            echo json_encode(['success'=>false,'message'=>'Invalid city.']); exit;
        }

        $hotel_count = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM hotels WHERE city = (SELECT LOWER(city_name) FROM cities WHERE id = $city_id)"))['c'];
        if ($hotel_count > 0) {
            echo json_encode(['success'=>false,'message'=>'This city cannot be deleted because hotels are assigned to it.']); exit;
        }

        $old = mysqli_prepare($conn, "SELECT city_image FROM cities WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($old, 'i', $city_id);
        mysqli_stmt_execute($old);
        $old_res = mysqli_stmt_get_result($old);
        $old_row = mysqli_fetch_assoc($old_res);
        mysqli_stmt_close($old);

        $stmt = mysqli_prepare($conn, "DELETE FROM cities WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $city_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok && !empty($old_row['city_image']) && file_exists(__DIR__ . '/' . $old_row['city_image'])) {
            @unlink(__DIR__ . '/' . $old_row['city_image']);
        }

        echo json_encode(['success'=>$ok,'message'=>$ok?'City deleted successfully!':'Database error.']);
        exit;
    }

    if ($action === 'toggle_status') {
        $city_id = (int)($_POST['city_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        if (!in_array($new_status, ['active','inactive'])) {
            echo json_encode(['success'=>false,'message'=>'Invalid status.']); exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE cities SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $new_status, $city_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success'=>$ok,'status'=>$new_status]);
        exit;
    }

    if ($action === 'get_hotels') {
        $city_id = (int)($_POST['city_id'] ?? 0);
        $r = mysqli_query($conn, "SELECT LOWER(city_name) AS cname FROM cities WHERE id = $city_id LIMIT 1");
        $cname = $r ? mysqli_fetch_assoc($r)['cname'] : '';
        $hotels = [];
        if ($cname) {
            $hs = mysqli_query($conn, "SELECT hotel_id, hotel_name, location, availability_status, city FROM hotels WHERE city = '" . mysqli_real_escape_string($conn, $cname) . "' ORDER BY hotel_name ASC");
            if ($hs) while ($h = mysqli_fetch_assoc($hs)) $hotels[] = $h;
        }
        echo json_encode(['success'=>true,'hotels'=>$hotels]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

// ── Stats ────────────────────────────────────────────────────────────────────
$total_cities    = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM cities"))['c'];
$active_cities   = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM cities WHERE status='active'"))['c'];
$inactive_cities = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM cities WHERE status='inactive'"))['c'];
$total_hotels    = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels"))['c'];

// ── Filters ──────────────────────────────────────────────────────────────────
$fs       = trim($_GET['q'] ?? '');
$fstat    = $_GET['status'] ?? '';
$fpop     = $_GET['popular'] ?? '';
$fcountry = $_GET['country'] ?? '';
$where    = ['1=1'];
if ($fs !== '') {
    $s = mysqli_real_escape_string($conn, $fs);
    $where[] = "(city_name LIKE '%$s%' OR state LIKE '%$s%')";
}
if (in_array($fstat, ['active','inactive'])) {
    $where[] = "status = '" . mysqli_real_escape_string($conn, $fstat) . "'";
}
if ($fpop === '1') {
    $where[] = "is_popular = 1";
}
if ($fcountry !== '') {
    $fcountry_esc = mysqli_real_escape_string($conn, $fcountry);
    $where[] = "country = '$fcountry_esc'";
}

$cities_list = [];
$res = mysqli_query($conn, "SELECT * FROM cities WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
if ($res) while ($row = mysqli_fetch_assoc($res)) $cities_list[] = $row;

// distinct countries for filter dropdown
$country_rows = mysqli_query($conn, "SELECT DISTINCT country FROM cities WHERE country IS NOT NULL AND country != '' ORDER BY country ASC");
$countries = [];
if ($country_rows) while ($r = mysqli_fetch_assoc($country_rows)) $countries[] = $r['country'];

$pageTitle    = 'Manage Cities';
$pageSubtitle = 'Manage operational cities for hotels';
include 'partials/header.php';
?>

<!-- Stats -->
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat blue"><div class="ds-si"><i class="bi bi-geo-alt-fill"></i></div>
      <div class="ds-sn"><?= $total_cities ?></div><div class="ds-sl">Total Cities</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div>
      <div class="ds-sn"><?= $active_cities ?></div><div class="ds-sl">Active Cities</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat gold"><div class="ds-si"><i class="bi bi-building"></i></div>
      <div class="ds-sn"><?= $total_hotels ?></div><div class="ds-sl">Total Hotels Across Cities</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat red"><div class="ds-si"><i class="bi bi-x-circle-fill"></i></div>
      <div class="ds-sn"><?= $inactive_cities ?></div><div class="ds-sl">Inactive Cities</div></div>
  </div>
</section>

<!-- Cities Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-geo-alt-fill me-2"></i>Cities (<?= count($cities_list) ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="filterForm">
        <div class="ds-sw">
          <i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search city or state…"
                 value="<?= htmlspecialchars($fs) ?>" style="width:220px"/>
        </div>
        <select class="ds-inp ds-sel" name="status" style="width:140px" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="active" <?= $fstat==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $fstat==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
        <select class="ds-inp ds-sel" name="popular" style="width:140px" onchange="this.form.submit()">
          <option value="">All Cities</option>
          <option value="1" <?= $fpop==='1'?'selected':'' ?>>Popular Only</option>
        </select>
        <select class="ds-inp ds-sel" name="country" style="width:160px" onchange="this.form.submit()">
          <option value="">All Countries</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $fcountry===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="manage-cities.php" class="ds-btn gho sm">Clear</a>
      </form>
      <button class="ds-btn prim sm ms-auto" onclick="openAddCityModal()">
        <i class="bi bi-plus-lg me-1"></i>Add City
      </button>
    </div>
  </div>
  <div class="ds-cb p-0" id="cityTableContainer">
    <?php if (empty($cities_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-geo-alt" style="font-size:3rem;opacity:.3"></i>
        <div class="fw-bold mt-3">No cities found</div>
        <div class="small mt-1">Add a city to get started.</div>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead>
          <tr>
            <th>Image</th>
            <th>City Name</th>
            <th>State</th>
            <th>Country</th>
            <th>Total Hotels</th>
            <th>Status</th>
            <th>Popular</th>
            <th>Created Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cities_list as $c):
            $hotel_count = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM hotels WHERE city = '" . mysqli_real_escape_string($conn, strtolower($c['city_name'])) . "'"))['c'];
            $badge = $c['status'] === 'active' ? 'confirmed' : 'pending';
            $created_at = date('d M Y, h:i A', strtotime($c['created_at']));
            $img_src = !empty($c['city_image']) ? htmlspecialchars($c['city_image']) : 'https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=200&q=80';
        ?>
          <tr id="cityRow<?= $c['id'] ?>">
            <td><img src="<?= $img_src ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;" onerror="this.src='https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=200&q=80'" /></td>
            <td><div class="fw-700"><?= htmlspecialchars($c['city_name']) ?></div></td>
            <td class="small"><?= htmlspecialchars($c['state'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($c['country'] ?? 'India') ?></td>
            <td class="small"><?= $hotel_count ?></td>
            <td id="status-cell-<?= $c['id'] ?>"><span class="ds-badge <?= $badge ?>"><?= ucfirst($c['status']) ?></span></td>
            <td class="small"><?= $c['is_popular'] ? '<i class="bi bi-star-fill text-warning"></i> Yes' : 'No' ?></td>
            <td class="small"><?= $created_at ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <button class="ds-btn gho sm" onclick="openViewHotelsModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['city_name'])) ?>')" title="View Hotels">
                  <i class="bi bi-building"></i> View Hotels
                </button>
                <button class="ds-btn gho sm" onclick="openEditCityModal(<?= $c['id'] ?>)" title="Edit">
                  <i class="bi bi-pencil-fill"></i> Edit
                </button>
                <?php if ($c['status'] === 'active'): ?>
                  <button id="toggle-btn-<?= $c['id'] ?>" class="ds-btn sm" style="background:#ef4444;color:#fff"
                          onclick="toggleCityStatus(<?= $c['id'] ?>, 'inactive', this)">
                    <i class="bi bi-slash-circle"></i> Deactivate
                  </button>
                <?php else: ?>
                  <button id="toggle-btn-<?= $c['id'] ?>" class="ds-btn sm" style="background:#10b981;color:#fff"
                          onclick="toggleCityStatus(<?= $c['id'] ?>, 'active', this)">
                    <i class="bi bi-check-circle"></i> Activate
                  </button>
                <?php endif; ?>
                <button class="ds-btn sm" style="color:#ef4444" title="Delete" onclick="confirmDeleteCity(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['city_name'])) ?>')">
                  <i class="bi bi-trash-fill"></i>
                </button>
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

<!-- Add City Modal -->
<div class="modal fade ds-modal" id="addCityModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Add New City</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="addCityForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_city"/>
        <div class="modal-body p-4">
          <div id="addCityAlert" class="alert d-none mb-3"></div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="ds-lbl">City Name <span class="text-danger">*</span></label>
              <input class="ds-inp" name="city_name" placeholder="e.g. Mumbai" required/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">State</label>
              <input class="ds-inp" name="state" placeholder="e.g. Maharashtra"/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">Country</label>
              <input class="ds-inp" name="country" value="India" placeholder="e.g. India"/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">City Image</label>
              <input class="ds-inp" type="file" name="city_image" accept="image/*"/>
              <small class="text-muted">Upload an image for this city</small>
            </div>
            <div class="col-md-12">
              <label class="ds-lbl">Description</label>
              <textarea class="ds-inp" name="description" rows="3" placeholder="Brief description of the city..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">Status</label>
              <select class="ds-inp ds-sel" name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_popular" id="addPopular">
                <label class="form-check-label" for="addPopular">Mark as Popular City</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ds-btn prim" id="addCityBtn"><i class="bi bi-check-lg me-1"></i>Add City</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit City Modal -->
<div class="modal fade ds-modal" id="editCityModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit City</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="editCityForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_city"/>
        <input type="hidden" name="city_id" id="editCityId"/>
        <div class="modal-body p-4">
          <div id="editCityAlert" class="alert d-none mb-3"></div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="ds-lbl">City Name <span class="text-danger">*</span></label>
              <input class="ds-inp" name="city_name" id="editCityName" required/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">State</label>
              <input class="ds-inp" name="state" id="editCityState"/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">Country</label>
              <input class="ds-inp" name="country" id="editCityCountry" value="India"/>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">City Image</label>
              <input class="ds-inp" type="file" name="city_image" accept="image/*"/>
              <small class="text-muted">Leave empty to keep existing image</small>
            </div>
            <div class="col-md-12">
              <label class="ds-lbl">Description</label>
              <textarea class="ds-inp" name="description" id="editCityDesc" rows="3"></textarea>
            </div>
            <div class="col-md-6">
              <label class="ds-lbl">Status</label>
              <select class="ds-inp ds-sel" name="status" id="editCityStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_popular" id="editPopular">
                <label class="form-check-label" for="editPopular">Mark as Popular City</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ds-btn prim" id="editCityBtn"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Hotels Modal -->
<div class="modal fade ds-modal" id="viewHotelsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-building me-2"></i>Hotels in <span id="viewCityName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="viewHotelsContent"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade ds-modal" id="deleteCityModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="bi bi-trash-fill me-2"></i>Delete City</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p>Are you sure you want to delete <strong id="deleteCityName"></strong>? This action cannot be undone.</p>
        <div id="deleteCityError" class="alert alert-danger d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="ds-btn" style="background:#ef4444;color:#fff" onclick="submitDeleteCity()" id="confirmDeleteBtn">
          <i class="bi bi-trash-fill me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="deleteCityId"/>

<script>
function openAddCityModal() {
  document.getElementById('addCityForm').reset();
  document.getElementById('addCityAlert').className = 'alert d-none mb-3';
  new bootstrap.Modal(document.getElementById('addCityModal')).show();
}

document.getElementById('addCityForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const btn = document.getElementById('addCityBtn');
  const alertEl = document.getElementById('addCityAlert');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
  btn.disabled = true;

  fetch('manage-cities.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Add City'; btn.disabled = false;
    if (d.success) {
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = '✓ ' + d.message + ' Refreshing…';
      setTimeout(() => location.reload(), 1200);
    } else {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = '✗ ' + d.message;
    }
  })
  .catch(() => { btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Add City'; btn.disabled=false; });
});

function openEditCityModal(cityId) {
  const modal   = new bootstrap.Modal(document.getElementById('editCityModal'));
  const alertEl = document.getElementById('editCityAlert');
  alertEl.className = 'alert d-none mb-3';
  document.getElementById('editCityId').value = cityId;

  const fd = new FormData();
  fd.append('action', 'get_city');
  fd.append('city_id', cityId);

  fetch('manage-cities.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(c => {
    if (c.error) { alert('Could not load city data.'); return; }
    document.getElementById('editCityName').value = c.city_name || '';
    document.getElementById('editCityState').value = c.state || '';
    document.getElementById('editCityCountry').value = c.country || 'India';
    document.getElementById('editCityDesc').value = c.description || '';
    document.getElementById('editCityStatus').value = c.status || 'active';
    document.getElementById('editPopular').checked = c.is_popular ? true : false;
    modal.show();
  })
  .catch(() => { alert('Network error.'); });
}

document.getElementById('editCityForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const btn = document.getElementById('editCityBtn');
  const alertEl = document.getElementById('editCityAlert');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
  btn.disabled = true;

  fetch('manage-cities.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes'; btn.disabled = false;
    if (d.success) {
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = '✓ ' + d.message + ' Refreshing…';
      setTimeout(() => location.reload(), 1200);
    } else {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = '✗ ' + d.message;
    }
  })
  .catch(() => { btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Save Changes'; btn.disabled=false; });
});

function toggleCityStatus(cityId, newStatus, btn) {
  if (!confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this city?')) return;
  const fd = new FormData();
  fd.append('action', 'toggle_status');
  fd.append('city_id', cityId);
  fd.append('status', newStatus);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  fetch('manage-cities.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const cell = document.getElementById('status-cell-' + cityId);
      const bmap = {active:'confirmed',inactive:'pending'};
      cell.innerHTML = '<span class="ds-badge '+(bmap[d.status]||'pending')+'">' +
                       d.status.charAt(0).toUpperCase()+d.status.slice(1)+'</span>';
      if (d.status === 'active') {
        btn.style.background='#ef4444'; btn.style.color='#fff';
        btn.innerHTML='<i class="bi bi-slash-circle"></i> Deactivate';
        btn.onclick=()=>toggleCityStatus(cityId,'inactive',btn);
      } else {
        btn.style.background='#10b981'; btn.style.color='#fff';
        btn.innerHTML='<i class="bi bi-check-circle"></i> Activate';
        btn.onclick=()=>toggleCityStatus(cityId,'active',btn);
      }
      btn.disabled = false;
    } else {
      alert('Failed to update status.'); btn.disabled = false;
    }
  })
  .catch(()=>{ alert('Network error.'); btn.disabled = false; });
}

function openViewHotelsModal(cityId, cityName) {
  document.getElementById('viewCityName').textContent = cityName;
  const container = document.getElementById('viewHotelsContent');
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">Loading hotels...</div></div>';
  new bootstrap.Modal(document.getElementById('viewHotelsModal')).show();

  fetch('manage-cities.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=get_hotels&city_id=' + cityId
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success || !d.hotels || d.hotels.length === 0) {
      container.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-building-x" style="font-size:2rem"></i><div class="mt-2">No hotels found in this city.</div></div>';
      return;
    }
    let html = '<div class="row g-3">';
    d.hotels.forEach(h => {
      html += '<div class="col-md-6">'+
        '<div class="d-flex align-items-center gap-3 p-2 border rounded">'+
        '<div class="rounded bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-weight:700;flex-shrink:0">'+((h.hotel_name||'?')[0]||'?').toUpperCase()+'</div>'+
        '<div><div class="fw-700 small">'+h.hotel_name+'</div>'+
        '<div class="small text-muted">'+h.location+' · '+(h.availability_status||'active').toUpperCase()+'</div></div></div></div>';
    });
    html += '</div>';
    container.innerHTML = html;
  })
  .catch(() => { container.innerHTML = '<div class="text-center text-danger">Failed to load hotels.</div>'; });
}

function confirmDeleteCity(cityId, cityName) {
  document.getElementById('deleteCityId').value = cityId;
  document.getElementById('deleteCityName').textContent = cityName;
  document.getElementById('deleteCityError').className = 'alert alert-danger d-none mt-3';
  document.getElementById('confirmDeleteBtn').disabled = false;
  new bootstrap.Modal(document.getElementById('deleteCityModal')).show();
}

function submitDeleteCity() {
  const cityId = document.getElementById('deleteCityId').value;
  const errorEl = document.getElementById('deleteCityError');
  const btn = document.getElementById('confirmDeleteBtn');
  const fd = new FormData();
  fd.append('action','delete_city'); fd.append('city_id', cityId);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting…';

  fetch('manage-cities.php', {method:'POST', body: fd})
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const row = document.getElementById('cityRow' + cityId);
      if (row) row.remove();
      bootstrap.Modal.getInstance(document.getElementById('deleteCityModal')).hide();
      setTimeout(() => location.reload(), 600);
    } else {
      errorEl.textContent = d.message;
      errorEl.className = 'alert alert-danger mt-3';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-trash-fill me-1"></i>Delete';
    }
  })
  .catch(() => {
    errorEl.textContent = 'Network error. Please try again.';
    errorEl.className = 'alert alert-danger mt-3';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-trash-fill me-1"></i>Delete';
  });
}
</script>

<?php include 'partials/footer.php'; ?>
