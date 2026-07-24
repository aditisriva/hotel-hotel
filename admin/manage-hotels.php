<?php
require_once 'auth_guard.php';
require_once 'db.php';
require_once '../hotel/hotel_functions.php';

// ── POST handlers ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $hid    = (int)($_POST['hotel_id'] ?? 0);

    // Approval
    if ($hid && in_array($action,['approve','reject','pending'])) {
        $sm  = ['approve'=>'approved','reject'=>'rejected','pending'=>'pending'];
        $ns  = $sm[$action];
        $st  = mysqli_prepare($conn,"UPDATE hotels SET approval_status=? WHERE hotel_id=?");
        mysqli_stmt_bind_param($st,'si',$ns,$hid);
        $ok  = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        echo json_encode(['success'=>$ok,'status'=>$ns]);
        exit;
    }

    // Add / Edit
    if ($action === 'add' || $action === 'edit') {
        $amenities_str = implode(',', array_map('trim', (array)($_POST['amenities'] ?? [])));
        $images_json   = $_POST['existing_images'] ?? '[]';
        $uploaded      = bhHandleImageUpload('hotel_image', $hid ?: 0);
        if ($uploaded)                      $images_json = json_encode([$uploaded]);
        elseif (!empty($_POST['image_url'])) $images_json = json_encode([trim($_POST['image_url'])]);

        $assigned = (int)($_POST['assigned_to'] ?? 0);
        $city_id  = (int)($_POST['city_id'] ?? 0);
        $city_name = strtolower(trim($_POST['city'] ?? ''));
        if ($city_id) {
            $cr = mysqli_query($conn, "SELECT city_name FROM cities WHERE id=$city_id");
            if ($cr && ($crow = mysqli_fetch_assoc($cr))) $city_name = strtolower($crow['city_name']);
        }
        $data = [
            'hotel_name'          => trim($_POST['hotel_name'] ?? ''),
            'city_id'             => $city_id > 0 ? $city_id : null,
            'city'                => $city_name,
            'location'            => trim($_POST['location'] ?? ''),
            'state'               => trim($_POST['state'] ?? ''),
            'description'         => trim($_POST['description'] ?? ''),
            'price_per_night'     => (float)($_POST['price_per_night'] ?? 0),
            'original_price'      => (float)($_POST['original_price'] ?? 0),
            'discount_percentage' => (float)($_POST['discount_percentage'] ?? 0),
            'gst_percentage'      => (float)($_POST['gst_percentage'] ?? 12),
            'rating'              => (float)($_POST['rating'] ?? 4.0),
            'star_rating'         => (int)($_POST['star_rating'] ?? 3),
            'property_type'       => $_POST['property_type'] ?? 'hotel',
            'amenities'           => $amenities_str,
            'capacity'            => (int)($_POST['capacity'] ?? 2),
            'availability_status' => $_POST['availability_status'] ?? 'active',
            'hotel_images'        => $images_json,
            'featured'            => isset($_POST['featured']) ? 1 : 0,
            'checkin_time'        => $_POST['checkin_time'] ?? '14:00',
            'checkout_time'       => $_POST['checkout_time'] ?? '11:00',
            'phone'               => trim($_POST['phone'] ?? ''),
            'email'               => trim($_POST['email'] ?? ''),
            'assigned_to'         => $assigned ?: null,
        ];

        if (empty($data['hotel_name']) || (!$data['city_id'] && empty($data['city'])) || $data['price_per_night'] <= 0) {
            echo json_encode(['success'=>false,'message'=>'Hotel name, city, and price are required.']); exit;
        }

        if ($action === 'add') {
            $new_id = bhInsertHotel($data);
            error_log('bhInsertHotel result: ' . var_export($new_id, true) . ' error: ' . mysqli_error($conn));
            if ($new_id) {
                if ($data['assigned_to']) {
                    $av = (int)$data['assigned_to'];
                    mysqli_query($conn,"UPDATE hotels SET assigned_to=$av WHERE hotel_id=$new_id");
                }
                echo json_encode(['success'=>true,'message'=>'Hotel added successfully!']);
            } else {
                echo json_encode(['success'=>false,'message'=>'Error adding hotel: '.mysqli_error($conn)]);
            }
        } else {
            $ok = bhUpdateHotel($hid, $data);
            if ($ok) {
                $av = $data['assigned_to'] ? (int)$data['assigned_to'] : 'NULL';
                mysqli_query($conn,"UPDATE hotels SET assigned_to=$av WHERE hotel_id=$hid");
            }
            echo json_encode($ok ? ['success'=>true,'message'=>'Hotel updated successfully!'] : ['success'=>false,'message'=>'Error updating hotel: '.mysqli_error($conn)]);
        }
        exit;
    }

    // Delete
    if ($action === 'delete') {
        $ok = $hid ? bhDeleteHotel($hid) : false;
        echo json_encode($ok ? ['success'=>true,'message'=>'Hotel deleted.'] : ['success'=>false,'message'=>'Error deleting hotel.']);
        exit;
    }

    // Get hotel data for edit modal
    if ($action === 'get_hotel') {
        $h = bhGetHotelById($hid);
        echo json_encode($h ?: ['error'=>'Not found']);
        exit;
    }
}

// ── Stats ─────────────────────────────────────────────────────────────────
$total   = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels"))['c'];
$approved= (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels WHERE approval_status='approved'"))['c'];
$pending = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels WHERE approval_status='pending'"))['c'];
$rejected= (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels WHERE approval_status='rejected'"))['c'];

// ── Filters ───────────────────────────────────────────────────────────────
$fapp = $_GET['approval'] ?? '';
$fs   = trim($_GET['q'] ?? '');
$fcity = (int)($_GET['city_id'] ?? 0);
$where=['1=1'];
if ($fapp) $where[] = "approval_status='".mysqli_real_escape_string($conn,$fapp)."'";
if ($fs)   { $s=$fs; $where[] = "(hotel_name LIKE '%$s%' OR city LIKE '%$s%')"; }
if ($fcity) $where[] = "city_id=$fcity";

$hotels_list=[];
$res=mysqli_query($conn,"SELECT * FROM hotels WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC");
if ($res) while ($row=mysqli_fetch_assoc($res)) $hotels_list[]=$row;

$users_list=[];
$ures=mysqli_query($conn,"SELECT id,first_name,last_name,email FROM users ORDER BY first_name ASC");
if ($ures) while ($ur=mysqli_fetch_assoc($ures)) $users_list[]=$ur;

$cities_list=[];
$cr=mysqli_query($conn,"SELECT id,city_name,state FROM cities WHERE status='active' ORDER BY city_name ASC");
if ($cr) while ($row=mysqli_fetch_assoc($cr)) $cities_list[]=$row;

$pageTitle='Hotel Management';
$pageSubtitle='Property listings, approvals, and assignments · Live DB';
include 'partials/header.php';
?>

<style>
/* Ensure modals scroll properly */
.modal-dialog-scrollable .modal-body { overflow-y: auto !important; max-height: 70vh !important; }
.modal-dialog-scrollable { max-height: 95vh !important; }
</style>

<!-- Stats -->
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-buildings"></i></div><div class="ds-sn"><?= $total ?></div><div class="ds-sl">Total Hotels</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div><div class="ds-sn"><?= $approved ?></div><div class="ds-sl">Approved</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-hourglass-split"></i></div><div class="ds-sn"><?= $pending ?></div><div class="ds-sl">Pending Review</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-x-circle-fill"></i></div><div class="ds-sn"><?= $rejected ?></div><div class="ds-sl">Rejected</div></div></div>
</section>

<!-- Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-buildings me-2"></i>Hotel Listings (<?= count($hotels_list) ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search hotels..." value="<?= htmlspecialchars($fs) ?>" style="width:180px"/>
        </div>
        <select class="ds-inp ds-sel" name="city_id" style="width:180px" onchange="this.form.submit()">
          <option value="">All Cities</option>
          <?php foreach ($cities_list as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $fcity===$c['id']?'selected':'' ?>><?= htmlspecialchars(ucfirst($c['city_name'])) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="ds-inp ds-sel" name="approval" style="width:140px" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="approved" <?= $fapp==='approved'?'selected':'' ?>>Approved</option>
          <option value="pending"  <?= $fapp==='pending'?'selected':''  ?>>Pending</option>
          <option value="rejected" <?= $fapp==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="manage-hotels.php" class="ds-btn gho sm">Clear</a>
      </form>
      <button class="ds-btn prim sm ms-auto" onclick="openAddModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Hotel
      </button>
    </div>
  </div>
  <div class="ds-cb p-0" id="hotelTableContainer">
    <?php if (empty($hotels_list)): ?>
    <div class="text-center py-5 text-muted"><i class="bi bi-buildings" style="font-size:3rem;opacity:.3"></i><div class="fw-700 mt-3">No hotels found</div></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="ds-tbl">
        <thead><tr><th>Hotel</th><th>City</th><th>Price/Night</th><th>Rating</th><th>Status</th><th>Approval</th><th>Assigned To</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($hotels_list as $h):
            $img    = bhFirstImage($h['hotel_images']??'','https://images.unsplash.com/photo-1566073771259-6a8506099945?w=80&q=60');
            $apSt   = $h['approval_status'] ?? 'approved';
            $apColor= ['approved'=>'confirmed','pending'=>'pending','rejected'=>'cancelled'];
            // find assigned user name
            $assigned_name = '—';
            if (!empty($h['assigned_to'])) {
                foreach ($users_list as $u) {
                    if ($u['id'] == $h['assigned_to']) { $assigned_name = htmlspecialchars($u['first_name'].' '.$u['last_name']); break; }
                }
            }
            // resolve city name from city_id
            $hcity_name = ucfirst($h['city']);
            if ($h['city_id']) {
                foreach ($cities_list as $c) {
                    if ($c['id'] == $h['city_id']) { $hcity_name = htmlspecialchars(ucfirst($c['city_name'])); break; }
                }
            }
        ?>
          <tr id="hotelRow<?= $h['hotel_id'] ?>">
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($img) ?>" style="width:50px;height:38px;object-fit:cover;border-radius:6px" alt=""
                     onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=80&q=60'"/>
                <div>
                  <div class="fw-700 small"><?= htmlspecialchars($h['hotel_name']) ?></div>
                  <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($h['property_type']) ?></div>
                </div>
              </div>
            </td>
            <td class="small"><?= $hcity_name ?></td>
            <td class="fw-700 small">₹<?= number_format($h['price_per_night']) ?></td>
            <td><span class="badge bg-warning text-dark"><?= $h['rating'] ?> ★</span></td>
            <td><span class="ds-badge <?= $h['availability_status']==='active'?'confirmed':'pending' ?>"><?= ucfirst($h['availability_status']) ?></span></td>
            <td id="ap<?= $h['hotel_id'] ?>"><span class="ds-badge <?= $apColor[$apSt]??'pending' ?>"><?= ucfirst($apSt) ?></span></td>
            <td class="small text-muted"><?= $assigned_name ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php if ($apSt==='pending' || $apSt==='rejected'): ?>
                <button class="ds-btn prim sm" onclick="approveHotel(<?= $h['hotel_id'] ?>,'approve')"><i class="bi bi-check-lg"></i> Approve</button>
                <?php endif; ?>
                <?php if ($apSt==='pending' || $apSt==='approved'): ?>
                <button class="ds-btn sm" style="background:#ef4444;color:#fff" onclick="approveHotel(<?= $h['hotel_id'] ?>,'reject')"><i class="bi bi-x-lg"></i> Reject</button>
                <?php endif; ?>
                <button class="ds-btn gho sm" onclick="openEditModal(<?= $h['hotel_id'] ?>)" title="Edit">
                  <i class="bi bi-pencil-fill"></i> Edit
                </button>
                <button class="ds-btn sm" style="color:#ef4444" title="Delete" onclick="confirmDelete(<?= $h['hotel_id'] ?>,'<?= htmlspecialchars(addslashes($h['hotel_name'])) ?>')">
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

<!-- ── ADD HOTEL MODAL ── -->
<div class="modal fade ds-modal" id="addHotelModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Add New Hotel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="addHotelForm">
        <input type="hidden" name="action" value="add"/>
        <div class="modal-body p-4">
          <div id="addHotelAlert" class="alert d-none mb-3"></div>
          <?php $eh = null; include '../hotel/_hotel_form_fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ds-btn prim"><i class="bi bi-check-lg me-1"></i>Add Hotel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── EDIT HOTEL MODAL ── -->
<div class="modal fade ds-modal" id="editHotelModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Hotel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="editHotelForm">
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="hotel_id" id="editHotelId"/>
        <div class="modal-body p-4">
          <div id="editHotelAlert" class="alert d-none mb-3"></div>
          <div id="editHotelLoading" class="text-center py-4">
            <span class="spinner-border text-primary"></span><div class="mt-2 text-muted">Loading hotel data…</div>
          </div>
          <div id="editHotelFields" class="d-none">
            <?php
            // Render all field names — JS will populate values
            $eh = ['hotel_id'=>0,'hotel_name'=>'','city_id'=>0,'city'=>'','state'=>'','location'=>'',
                   'description'=>'','price_per_night'=>0,'original_price'=>0,'rating'=>4,
                   'star_rating'=>3,'property_type'=>'hotel','amenities'=>'','capacity'=>2,
                   'availability_status'=>'active','hotel_images'=>'','featured'=>0,
                   'checkin_time'=>'14:00','checkout_time'=>'11:00','phone'=>'','email'=>'','assigned_to'=>null];
            include '../hotel/_hotel_form_fields.php';
            ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ds-btn prim" id="editSubmitBtn"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── DELETE CONFIRM MODAL ── -->
<div class="modal fade ds-modal" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title text-danger"><i class="bi bi-trash-fill me-2"></i>Delete Hotel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p>Are you sure you want to delete <strong id="deleteHotelName"></strong>? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="ds-btn" style="background:#ef4444;color:#fff" onclick="submitDeleteHotel()"><i class="bi bi-trash-fill me-1"></i>Delete</button>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="deleteHotelId"/>

<script>
// ── Add Hotel ────────────────────────────────────────────────────────────
function previewImage(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById(previewId).src = e.target.result;
      document.getElementById(previewId).classList.remove('d-none');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function openAddModal() {
  document.getElementById('addHotelForm').reset();
  document.getElementById('addHotelAlert').className = 'alert d-none mb-3';
  new bootstrap.Modal(document.getElementById('addHotelModal')).show();
}

document.getElementById('addHotelForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const alertEl = document.getElementById('addHotelAlert');
  const name  = this.querySelector('[name="hotel_name"]').value.trim();
  const cityId = parseInt(this.querySelector('[name="city_id"]').value) || 0;
  const price = parseFloat(this.querySelector('[name="price_per_night"]').value);
  if (!name || !cityId || price <= 0) {
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'Hotel name, city, and a valid price are required.'; return;
  }
  const btn = this.querySelector('button[type="submit"]');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
  btn.disabled = true;
  alertEl.className = 'alert d-none mb-3';
  fetch('manage-hotels.php', { method:'POST', body: new FormData(this) })
  .then(r => {
        if (!r.ok) throw new Error('Server returned ' + r.status + ' ' + r.statusText);
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Invalid response type: ' + ct);
        return r.json();
      })
      .then(d => {
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Add Hotel'; btn.disabled = false;
      if (d.success) {
        alertEl.className = 'alert alert-success mb-3';
        alertEl.innerHTML = '<strong>Success:</strong> ' + d.message;
        const modal = bootstrap.Modal.getInstance(document.getElementById('addHotelModal'));
        if (modal) modal.hide();
        this.reset();
        setTimeout(function() { loadHotelTable(); }, 500);
      } else {
        alertEl.className = 'alert alert-danger mb-3';
        alertEl.innerHTML = '<strong>Error:</strong> ' + (d.message || 'Unknown error');
      }
  })
  .catch(err => {
    btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Add Hotel'; btn.disabled=false;
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.innerHTML = '<strong>Network Error:</strong> ' + err.message + ' — the server may be down or returned an invalid response.';
  });
});

// ── Edit Hotel ───────────────────────────────────────────────────────────
function openEditModal(hotelId) {
  const modal   = new bootstrap.Modal(document.getElementById('editHotelModal'));
  const loading = document.getElementById('editHotelLoading');
  const fields  = document.getElementById('editHotelFields');
  const alertEl = document.getElementById('editHotelAlert');

  document.getElementById('editHotelId').value = hotelId;
  loading.classList.remove('d-none');
  fields.classList.add('d-none');
  alertEl.className = 'alert d-none mb-3';
  modal.show();

  const fd = new FormData();
  fd.append('action', 'get_hotel');
  fd.append('hotel_id', hotelId);

  fetch('manage-hotels.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(h => {
    if (h.error) { alert('Could not load hotel data.'); return; }
    // Populate all form fields
    const form = document.getElementById('editHotelForm');
    const setV = (name, val) => { const el = form.querySelector('[name="'+name+'"]'); if (el) el.value = val || ''; };
    const setS = (name, val) => { const el = form.querySelector('[name="'+name+'"]'); if (el) { el.value = val || ''; } };

    setV('hotel_name', h.hotel_name);
    setV('city',       h.city);
    setV('state',      h.state);
    setV('location',   h.location);
    setV('description',h.description);
    setV('price_per_night', h.price_per_night);
    setV('original_price',  h.original_price);
    setV('gst_percentage',  h.gst_percentage);
    setV('rating',          h.rating);
    setV('capacity',        h.capacity);
    setV('checkin_time',    h.checkin_time);
    setV('checkout_time',   h.checkout_time);
    setV('phone',           h.phone);
    setV('email',           h.email);
    setV('image_url',       '');
    setS('star_rating',         h.star_rating);
    setS('property_type',       h.property_type);
    setS('availability_status', h.availability_status);
    setS('assigned_to',         h.assigned_to || '');
    setS('city_id',             h.city_id || '');

    // Featured checkbox
    const featCb = form.querySelector('[name="featured"]');
    if (featCb) featCb.checked = !!parseInt(h.featured || 0);

    // Amenities checkboxes
    const cur = (h.amenities || '').split(',').map(a => a.trim());
    form.querySelectorAll('[name="amenities[]"]').forEach(cb => {
      cb.checked = cur.includes(cb.value);
      cb.closest('label').classList.toggle('selected', cb.checked);
    });

    // Existing images
    const imgInp = form.querySelector('[name="existing_images"]');
    if (imgInp) imgInp.value = h.hotel_images || '[]';

    loading.classList.add('d-none');
    fields.classList.remove('d-none');
  })
  .catch(() => { loading.innerHTML = '<div class="text-danger">Failed to load hotel data.</div>'; });
}

function loadHotelTable() {
  fetch('manage-hotels.php?ajax=1&t=' + Date.now())
  .then(r => {
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.text();
  })
  .then(html => {
    const container = document.getElementById('hotelTableContainer');
    if (container) {
      const tmp = document.createElement('div');
      tmp.innerHTML = html;
      const newTable = tmp.querySelector('#hotelTableContainer');
      if (newTable) {
        container.innerHTML = newTable.innerHTML;
      } else {
        location.reload();
      }
    }
  })
  .catch(err => {
    console.error('loadHotelTable error:', err.message);
    location.reload();
  });
}

document.getElementById('editHotelForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const alertEl = document.getElementById('editHotelAlert');
  const btn = document.getElementById('editSubmitBtn');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
  btn.disabled = true;
  fetch('manage-hotels.php', { method:'POST', body: new FormData(this) })
  .then(r => r.json())
  .then(d => {
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes'; btn.disabled = false;
    if (d.success) {
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = '✓ ' + d.message;
      const modal = bootstrap.Modal.getInstance(document.getElementById('editHotelModal'));
      if (modal) modal.hide();
      loadHotelTable();
    } else {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = '✗ ' + d.message;
    }
  })
  .catch(err => {
    btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Save Changes'; btn.disabled=false;
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = '✗ Request failed: ' + err.message;
  });
});

// ── Approve / Reject ─────────────────────────────────────────────────────
function approveHotel(id, action) {
  const fd = new FormData();
  fd.append('action', action); fd.append('hotel_id', id);
  fetch('manage-hotels.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const c = {approved:'confirmed',rejected:'cancelled',pending:'pending'};
      document.getElementById('ap'+id).innerHTML =
        '<span class="ds-badge '+c[d.status]+'">'+d.status.charAt(0).toUpperCase()+d.status.slice(1)+'</span>';
    }
  });
}

// ── Delete ───────────────────────────────────────────────────────────────
function confirmDelete(id, name) {
  document.getElementById('deleteHotelId').value = id;
  document.getElementById('deleteHotelName').textContent = name;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function submitDeleteHotel() {
  const id = document.getElementById('deleteHotelId').value;
  const fd = new FormData();
  fd.append('action','delete'); fd.append('hotel_id',id);
  fetch('manage-hotels.php',{method:'POST',body:fd})
  .then(r=>r.json())
  .then(d=>{ if(d.success){ loadHotelTable(); } else { alert(d.message); } });
}
</script>

<?php include 'partials/footer.php'; ?>
