<?php
require_once 'auth_guard.php';
require_once 'db.php';

// ── Handle AJAX actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Create user
    if ($action === 'create_user') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']       ?? '');
        $mobile     = trim($_POST['mobile']      ?? '') ?: '0000000000';
        $password   = trim($_POST['password']    ?? '');
        $role       = in_array($_POST['role'] ?? '', ['user','admin','hotel_manager']) ? $_POST['role'] : 'user';
        $status     = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        if (!$first_name || !$email || !$password) {
            echo json_encode(['success'=>false,'message'=>'First name, email, and password are required.']); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success'=>false,'message'=>'Invalid email address.']); exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters.']); exit;
        }
        $chk = mysqli_prepare($conn,"SELECT id FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($chk,'s',$email);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            mysqli_stmt_close($chk);
            echo json_encode(['success'=>false,'message'=>'A user with this email already exists.']); exit;
        }
        mysqli_stmt_close($chk);

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn,"INSERT INTO users (first_name,last_name,email,mobile,password,role,status) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt,'sssssss',$first_name,$last_name,$email,$mobile,$hashed,$role,$status);
        $ok  = mysqli_stmt_execute($stmt);
        $nid = $ok ? mysqli_insert_id($conn) : 0;
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'message'=>$ok?'User created successfully!':'Database error.','id'=>$nid]);
        exit;
    }

    // Status toggle
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid && in_array($action,['activate','deactivate','suspend'])) {
        $map = ['activate'=>'active','deactivate'=>'inactive','suspend'=>'suspended'];
        $ns  = $map[$action];
        $stmt = mysqli_prepare($conn,"UPDATE users SET status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,'si',$ns,$uid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'status'=>$ns]);
        exit;
    }

    // Update user role
    if ($action === 'update_role' && $uid) {
        $new_role = in_array($_POST['new_role'] ?? '', ['user','admin','hotel_manager']) ? $_POST['new_role'] : 'user';
        $stmt = mysqli_prepare($conn,"UPDATE users SET role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,'si',$new_role,$uid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'role'=>$new_role]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

// ── Stats ─────────────────────────────────────────────────────────────────
$total_users    = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM users"))['c'];
$active_users   = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM users WHERE status='active'"))['c'];
$inactive_users = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM users WHERE status='inactive'"))['c'];
$new_this_week  = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'];

// ── Filters ───────────────────────────────────────────────────────────────
$fs    = trim($_GET['q'] ?? '');
$fstat = $_GET['status'] ?? '';
$where = ['1=1'];
if ($fs !== '') {
    $s = mysqli_real_escape_string($conn,$fs);
    $where[] = "(first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR email LIKE '%$s%' OR mobile LIKE '%$s%')";
}
if (in_array($fstat,['active','inactive','suspended'])) {
    $where[] = "status = '" . mysqli_real_escape_string($conn,$fstat) . "'";
}

$users_list = [];
$res = mysqli_query($conn,"SELECT id,first_name,last_name,email,mobile,status,role,created_at,last_login FROM users WHERE " . implode(' AND ',$where) . " ORDER BY created_at DESC");
if ($res) { while ($row = mysqli_fetch_assoc($res)) $users_list[] = $row; }

$pageTitle    = 'User Management';
$pageSubtitle = 'Create and manage user accounts, hotel managers, and platform access';
include 'partials/header.php';
?>

<!-- Stats -->
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat blue"><div class="ds-si"><i class="bi bi-people-fill"></i></div>
      <div class="ds-sn"><?= $total_users ?></div><div class="ds-sl">Registered Users</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat green"><div class="ds-si"><i class="bi bi-person-check-fill"></i></div>
      <div class="ds-sn"><?= $active_users ?></div><div class="ds-sl">Active Accounts</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat gold"><div class="ds-si"><i class="bi bi-person-plus-fill"></i></div>
      <div class="ds-sn"><?= $new_this_week ?></div><div class="ds-sl">New This Week</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat red"><div class="ds-si"><i class="bi bi-person-dash-fill"></i></div>
      <div class="ds-sn"><?= $inactive_users ?></div><div class="ds-sl">Inactive Accounts</div></div>
  </div>
</section>

<!-- Users Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-people-fill me-2"></i>User Accounts (<?= count($users_list) ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="filterForm">
        <div class="ds-sw">
          <i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search name, email or mobile…"
                 value="<?= htmlspecialchars($fs) ?>" style="width:220px"/>
        </div>
        <select class="ds-inp ds-sel" name="status" style="width:140px" onchange="this.form.submit()">
          <option value="">All Users</option>
          <option value="active"    <?= $fstat==='active'    ?'selected':'' ?>>Active</option>
          <option value="inactive"  <?= $fstat==='inactive'  ?'selected':'' ?>>Inactive</option>
          <option value="suspended" <?= $fstat==='suspended' ?'selected':'' ?>>Suspended</option>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="manage-users.php" class="ds-btn gho sm">Clear</a>
      </form>
      <button class="ds-btn prim sm ms-auto" onclick="openAddUserModal()">
        <i class="bi bi-person-plus-fill me-1"></i>Add User
      </button>
    </div>
  </div>
  <div class="ds-cb p-0">
    <?php if (empty($users_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-people" style="font-size:3rem;opacity:.3"></i>
        <div class="fw-bold mt-3">No registered users found.</div>
        <div class="small mt-1">Users who sign up from the website will appear here automatically, or use the Add User button above.</div>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead>
          <tr>
            <th>#ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Role</th>
            <th>Status</th>
            <th>Registered On</th>
            <th>Last Login</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users_list as $u):
            $badge = match($u['status']) {
                'active'    => 'confirmed',
                'inactive'  => 'cancelled',
                'suspended' => 'pending',
                default     => 'pending'
            };
            $full_name  = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
            $created_at = date('d M Y, h:i A', strtotime($u['created_at']));
            $last_login = $u['last_login'] ? date('d M Y, h:i A', strtotime($u['last_login'])) : '—';
            $initials   = strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1));
            $role_label = match($u['role']) {
                'admin'         => 'Admin',
                'hotel_manager' => 'Hotel Manager',
                default         => 'Customer',
            };
            $role_color = match($u['role']) {
                'admin'         => '#6366f1',
                'hotel_manager' => '#0ea5e9',
                default         => '#64748b',
            };
        ?>
          <tr>
            <td class="text-muted small">#<?= $u['id'] ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                     style="width:34px;height:34px;font-size:.8rem;font-weight:700;flex-shrink:0"><?= $initials ?></div>
                <div>
                  <div class="fw-700" style="font-size:.875rem"><?= $full_name ?></div>
                  <div style="font-size:.75rem;color:<?= $role_color ?>;font-weight:600"><?= $role_label ?></div>
                </div>
              </div>
            </td>
            <td class="small"><?= htmlspecialchars($u['email']) ?></td>
            <td class="small"><?= htmlspecialchars($u['mobile'] ?? '—') ?></td>
            <td><span style="background:<?php
$bg = match($u['role']) { 'admin' => 'rgba(99,102,241,.15)', 'hotel_manager' => 'rgba(14,165,233,.12)', default => 'rgba(100,116,139,.1)' };
echo $bg; ?>;color:<?= $role_color ?>;padding:2px 8px;border-radius:20px;font-size:.75rem;font-weight:600"><?= $role_label ?></span></td>
            <td id="status-cell-<?= $u['id'] ?>"><span class="ds-badge <?= $badge ?>"><?= ucfirst($u['status']) ?></span></td>
            <td class="small"><?= $created_at ?></td>
            <td class="small text-muted"><?= $last_login ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <button class="ds-btn gho sm"
                        onclick='openUserModal(<?= json_encode([
                            "id"         => (int)$u["id"],
                            "name"       => $u["first_name"] . " " . $u["last_name"],
                            "email"      => $u["email"],
                            "mobile"     => $u["mobile"] ?? "—",
                            "role"       => $u["role"],
                            "status"     => $u["status"],
                            "registered" => $created_at,
                            "last_login" => $last_login,
                        ]) ?>)'>
                  <i class="bi bi-eye-fill"></i> View
                </button>
                <?php if ($u['status'] === 'active'): ?>
                  <button id="toggle-btn-<?= $u['id'] ?>" class="ds-btn sm" style="background:#ef4444;color:#fff"
                          onclick="toggleStatus(<?= $u['id'] ?>, 'deactivate', this)">
                    <i class="bi bi-slash-circle"></i> Deactivate
                  </button>
                <?php else: ?>
                  <button id="toggle-btn-<?= $u['id'] ?>" class="ds-btn sm" style="background:#10b981;color:#fff"
                          onclick="toggleStatus(<?= $u['id'] ?>, 'activate', this)">
                    <i class="bi bi-check-circle"></i> Activate
                  </button>
                <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade ds-modal" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add New User / Hotel Manager</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="addUserAlert" class="alert d-none mb-3"></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="ds-lbl">First Name <span class="text-danger">*</span></label>
            <input class="ds-inp" id="au-firstname" placeholder="e.g. Rahul"/>
          </div>
          <div class="col-md-6">
            <label class="ds-lbl">Last Name</label>
            <input class="ds-inp" id="au-lastname" placeholder="e.g. Sharma"/>
          </div>
          <div class="col-md-6">
            <label class="ds-lbl">Email Address <span class="text-danger">*</span></label>
            <input class="ds-inp" id="au-email" type="email" placeholder="rahul@example.com"/>
          </div>
          <div class="col-md-6">
            <label class="ds-lbl">Mobile Number</label>
            <input class="ds-inp" id="au-mobile" placeholder="+91 XXXXXXXXXX"/>
          </div>
          <div class="col-md-6">
            <label class="ds-lbl">Password <span class="text-danger">*</span></label>
            <input class="ds-inp" id="au-password" type="password" placeholder="Minimum 6 characters"/>
          </div>
          <div class="col-md-3">
            <label class="ds-lbl">Role</label>
            <select class="ds-inp ds-sel" id="au-role">
              <option value="user">Customer (User)</option>
              <option value="hotel_manager">Hotel Manager</option>
              <option value="admin">Platform Admin</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="ds-lbl">Status</label>
            <select class="ds-inp ds-sel" id="au-status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <div class="p-3 rounded" style="background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.2)">
              <i class="bi bi-lightbulb-fill text-info me-1"></i>
              <strong>Roles explained:</strong><br/>
              &bull; <strong>Customer</strong> — can browse and book hotels on the user website.<br/>
              &bull; <strong>Hotel Manager</strong> — logs into the Hotel Operations panel to manage their assigned property. Assign a hotel to them via <a href="manage-hotels.php" class="text-primary fw-bold">Manage Hotels</a>.<br/>
              &bull; <strong>Platform Admin</strong> — full access to this admin panel.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="ds-btn prim" id="au-submit-btn" onclick="createUser()">
          <i class="bi bi-person-plus-fill me-1"></i>Create Account
        </button>
      </div>
    </div>
  </div>
</div>

<!-- View User Modal -->
<div class="modal fade ds-modal" id="userDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>User Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
               style="width:56px;height:56px;font-size:1.3rem;font-weight:700" id="md-avatar"></div>
          <div>
            <div class="fw-700 fs-5" id="md-name"></div>
            <div id="md-status-badge" class="mt-1"></div>
          </div>
        </div>
        <table class="table table-sm table-borderless mb-0">
          <tr><th class="text-muted" style="width:130px">User ID</th><td id="md-id"></td></tr>
          <tr><th class="text-muted">Email</th><td id="md-email"></td></tr>
          <tr><th class="text-muted">Mobile</th><td id="md-mobile"></td></tr>
          <tr><th class="text-muted">Role</th><td id="md-role"></td></tr>
          <tr class="d-none" id="md-role-row"><th class="text-muted">Change Role</th><td>
            <select class="ds-inp ds-sel" id="md-role-select" style="width:180px">
              <option value="user">Customer</option>
              <option value="hotel_manager">Hotel Manager</option>
              <option value="admin">Admin</option>
            </select>
            <button class="ds-btn prim sm ms-2" onclick="updateUserRole()">
              <i class="bi bi-check-lg"></i> Update
            </button>
            <span id="md-role-msg" class="ms-2 small"></span>
          </td></tr>
          <tr><th class="text-muted">Registered On</th><td id="md-registered"></td></tr>
          <tr><th class="text-muted">Last Login</th><td id="md-lastlogin"></td></tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function openAddUserModal() {
  ['au-firstname','au-lastname','au-email','au-mobile','au-password'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('au-role').value   = 'user';
  document.getElementById('au-status').value = 'active';
  document.getElementById('addUserAlert').className = 'alert d-none mb-3';
  new bootstrap.Modal(document.getElementById('addUserModal')).show();
}

function createUser() {
  const fn = document.getElementById('au-firstname').value.trim();
  const ln = document.getElementById('au-lastname').value.trim();
  const em = document.getElementById('au-email').value.trim();
  const mb = document.getElementById('au-mobile').value.trim();
  const pw = document.getElementById('au-password').value.trim();
  const rl = document.getElementById('au-role').value;
  const st = document.getElementById('au-status').value;
  const alertEl = document.getElementById('addUserAlert');
  const btn = document.getElementById('au-submit-btn');

  if (!fn || !em || !pw) {
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'First name, email, and password are required.'; return;
  }
  if (pw.length < 6) {
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'Password must be at least 6 characters.'; return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating…';

  const fd = new FormData();
  fd.append('action','create_user'); fd.append('first_name',fn); fd.append('last_name',ln);
  fd.append('email',em); fd.append('mobile',mb); fd.append('password',pw);
  fd.append('role',rl); fd.append('status',st);

  fetch('manage-users.php',{method:'POST',body:fd})
  .then(r=>r.json())
  .then(d=>{
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>Create Account';
    if (d.success) {
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = '✓ ' + d.message + ' Refreshing…';
      setTimeout(()=>location.reload(), 1200);
    } else {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = '✗ ' + d.message;
    }
  })
  .catch(()=>{
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>Create Account';
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'Network error. Please try again.';
  });
}

function openUserModal(u) {
  document.getElementById('md-id').textContent         = '#' + u.id;
  document.getElementById('md-name').textContent       = u.name;
  document.getElementById('md-email').textContent      = u.email;
  document.getElementById('md-mobile').textContent     = u.mobile;
  document.getElementById('md-role').textContent       = u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : '—';
  document.getElementById('md-registered').textContent = u.registered;
  document.getElementById('md-lastlogin').textContent  = u.last_login;
  document.getElementById('md-role-select').value = u.role || 'user';
  document.getElementById('md-role-msg').textContent = '';
  document.getElementById('md-role-row').classList.remove('d-none');
  const parts = u.name.trim().split(' ');
  document.getElementById('md-avatar').textContent = ((parts[0]||'?')[0] + (parts[1]||'?')[0]).toUpperCase();
  const colors = {active:'#10b981',inactive:'#ef4444',suspended:'#f59e0b'};
  document.getElementById('md-status-badge').innerHTML =
    '<span style="color:'+(colors[u.status]||'#6b7280')+';font-weight:700">' +
    u.status.charAt(0).toUpperCase() + u.status.slice(1) + '</span>';
  new bootstrap.Modal(document.getElementById('userDetailModal')).show();
}

function updateUserRole() {
  const userId = parseInt(document.getElementById('md-id').textContent.replace('#',''));
  const newRole = document.getElementById('md-role-select').value;
  const msgEl = document.getElementById('md-role-msg');
  
  if (!confirm('Change this user\'s role to ' + newRole.replace('_',' ') + '?')) return;
  
  const fd = new FormData();
  fd.append('action','update_role'); fd.append('user_id', userId); fd.append('new_role', newRole);
  
  msgEl.textContent = 'Updating…';
  fetch('manage-users.php',{method:'POST',body:fd})
  .then(r=>r.json())
  .then(d=>{
    if (d.success) {
      msgEl.textContent = '✓ Role updated!';
      msgEl.style.color = '#10b981';
      document.getElementById('md-role').textContent = newRole.charAt(0).toUpperCase() + newRole.slice(1);
    } else {
      msgEl.textContent = '✗ Update failed.';
      msgEl.style.color = '#ef4444';
    }
  })
  .catch(()=>{
    msgEl.textContent = 'Network error.';
    msgEl.style.color = '#ef4444';
  });
}

function toggleStatus(userId, action, btn) {
  if (!confirm('Are you sure you want to ' + action + ' this user?')) return;
  const fd = new FormData();
  fd.append('action', action); fd.append('user_id', userId);
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  fetch('manage-users.php',{method:'POST',body:fd})
  .then(r=>r.json())
  .then(d=>{
    if (d.success) {
      const cell = document.getElementById('status-cell-' + userId);
      const bmap = {active:'confirmed',inactive:'cancelled',suspended:'pending'};
      cell.innerHTML = '<span class="ds-badge '+(bmap[d.status]||'pending')+'">' +
                       d.status.charAt(0).toUpperCase()+d.status.slice(1)+'</span>';
      if (d.status === 'active') {
        btn.style.background='#ef4444'; btn.style.color='#fff';
        btn.innerHTML='<i class="bi bi-slash-circle"></i> Deactivate';
        btn.onclick=()=>toggleStatus(userId,'deactivate',btn);
      } else {
        btn.style.background='#10b981'; btn.style.color='#fff';
        btn.innerHTML='<i class="bi bi-check-circle"></i> Activate';
        btn.onclick=()=>toggleStatus(userId,'activate',btn);
      }
      btn.disabled = false;
    } else {
      alert('Failed to update status.'); btn.disabled = false;
    }
  })
  .catch(()=>{ alert('Network error.'); btn.disabled = false; });
}
</script>

<?php include 'partials/footer.php'; ?>
