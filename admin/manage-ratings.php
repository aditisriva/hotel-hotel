<?php
require_once 'auth_guard.php';
require_once 'db.php';

// ── Ensure reviews table has status + booking_id columns ────────────────────
$add_booking = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'booking_id'");
if (!$add_booking || mysqli_num_rows($add_booking) === 0) {
    mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN booking_id VARCHAR(20) DEFAULT NULL AFTER hotel_id, ADD INDEX idx_booking (booking_id)");
}
$add_status = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'status'");
if (!$add_status || mysqli_num_rows($add_status) === 0) {
    mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN status ENUM('pending','approved','hidden') DEFAULT 'pending' AFTER comment, ADD INDEX idx_status (status)");
}

// ── AJAX Handlers ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'approve_review') {
        $rid = (int)($_POST['review_id'] ?? 0);
        if (!$rid) { echo json_encode(['success'=>false,'message'=>'Invalid review.']); exit; }
        $stmt = mysqli_prepare($conn, "UPDATE reviews SET status='approved' WHERE review_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $rid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Review approved successfully!':'Database error.','status'=>'approved']);
        exit;
    }

    if ($action === 'hide_review') {
        $rid = (int)($_POST['review_id'] ?? 0);
        if (!$rid) { echo json_encode(['success'=>false,'message'=>'Invalid review.']); exit; }
        $stmt = mysqli_prepare($conn, "UPDATE reviews SET status='hidden' WHERE review_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $rid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Review hidden successfully!':'Database error.','status'=>'hidden']);
        exit;
    }

    if ($action === 'delete_review') {
        $rid = (int)($_POST['review_id'] ?? 0);
        if (!$rid) { echo json_encode(['success'=>false,'message'=>'Invalid review.']); exit; }
        $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id=?");
        mysqli_stmt_bind_param($stmt, 'i', $rid);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Review deleted permanently!':'Database error.']);
        exit;
    }

    if ($action === 'get_review') {
        $rid = (int)($_POST['review_id'] ?? 0);
        $st = mysqli_prepare($conn, "SELECT r.*, h.hotel_name, u.first_name, u.last_name, u.email FROM reviews r LEFT JOIN hotels h ON r.hotel_id = h.hotel_id LEFT JOIN users u ON r.user_id = u.id WHERE r.review_id = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $rid);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $review = mysqli_fetch_assoc($res);
        mysqli_stmt_close($st);
        echo json_encode($review ?: ['error'=>'Not found']);
        exit;
    }
}

// ── Stats ───────────────────────────────────────────────────────────────────
$total_reviews    = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM reviews"))['c'];
$avg_rating       = $total_reviews > 0 ? round((float)mysqli_fetch_assoc(mysqli_query($conn,"SELECT AVG(rating) AS a FROM reviews"))['a'], 1) : 0;
$pending_reviews  = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM reviews WHERE status='pending'"))['c'];
$approved_reviews = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM reviews WHERE status='approved'"))['c'];
$hidden_reviews   = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM reviews WHERE status='hidden'"))['c'];

// ── Filters ─────────────────────────────────────────────────────────────────
$fs    = trim($_GET['q'] ?? '');
$fstat = $_GET['status'] ?? '';
$fstar = $_GET['star'] ?? '';
$where = ['1=1'];
if ($fs !== '') {
    $s = mysqli_real_escape_string($conn, $fs);
    $where[] = "(r.guest_name LIKE '%$s%' OR h.hotel_name LIKE '%$s%' OR r.booking_id LIKE '%$s%')";
}
if (in_array($fstat, ['pending','approved','hidden'])) {
    $where[] = "r.status = '" . mysqli_real_escape_string($conn, $fstat) . "'";
}
if (in_array($fstar, ['5','4','3','2','1'])) {
    $where[] = "ROUND(r.rating) = " . (int)$fstar;
}

$reviews_list = [];
$sql = "SELECT r.*, h.hotel_name, u.first_name, u.last_name, u.email FROM reviews r LEFT JOIN hotels h ON r.hotel_id = h.hotel_id LEFT JOIN users u ON r.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY r.created_at DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $reviews_list[] = $row;

$pageTitle    = 'Manage Ratings';
$pageSubtitle = 'Moderate guest reviews and maintain content quality';
include 'partials/header.php';
?>

<!-- Stats -->
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat blue"><div class="ds-si"><i class="bi bi-chat-dots-fill"></i></div>
      <div class="ds-sn"><?= $total_reviews ?></div><div class="ds-sl">Total Reviews</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat gold"><div class="ds-si"><i class="bi bi-star-fill"></i></div>
      <div class="ds-sn"><?= $avg_rating ?>/5</div><div class="ds-sl">Average Rating</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div>
      <div class="ds-sn"><?= $approved_reviews ?></div><div class="ds-sl">Approved Reviews</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat red"><div class="ds-si"><i class="bi bi-slash-circle"></i></div>
      <div class="ds-sn"><?= $hidden_reviews ?></div><div class="ds-sl">Hidden Reviews</div></div>
  </div>
</section>

<!-- Reviews Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-star-fill me-2"></i>Reviews (<?= count($reviews_list) ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="filterForm">
        <div class="ds-sw">
          <i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search user, hotel or booking…"
                 value="<?= htmlspecialchars($fs) ?>" style="width:220px"/>
        </div>
        <select class="ds-inp ds-sel" name="status" style="width:140px" onchange="this.form.submit()">
          <option value="">All Reviews</option>
          <option value="pending" <?= $fstat==='pending'?'selected':'' ?>>Pending</option>
          <option value="approved" <?= $fstat==='approved'?'selected':'' ?>>Approved</option>
          <option value="hidden" <?= $fstat==='hidden'?'selected':'' ?>>Hidden</option>
        </select>
        <select class="ds-inp ds-sel" name="star" style="width:110px" onchange="this.form.submit()">
          <option value="">All Stars</option>
          <option value="5" <?= $fstar==='5'?'selected':'' ?>>5 Star</option>
          <option value="4" <?= $fstar==='4'?'selected':'' ?>>4 Star</option>
          <option value="3" <?= $fstar==='3'?'selected':'' ?>>3 Star</option>
          <option value="2" <?= $fstar==='2'?'selected':'' ?>>2 Star</option>
          <option value="1" <?= $fstar==='1'?'selected':'' ?>>1 Star</option>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="manage-ratings.php" class="ds-btn gho sm">Clear</a>
      </form>
    </div>
  </div>
  <div class="ds-cb p-0" id="reviewTableContainer">
    <?php if (empty($reviews_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-chat-square-text" style="font-size:3rem;opacity:.3"></i>
        <div class="fw-bold mt-3">No reviews found</div>
        <div class="small mt-1">Reviews submitted by guests will appear here.</div>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead>
          <tr>
            <th>Review ID</th>
            <th>User Name</th>
            <th>Hotel Name</th>
            <th>Booking ID</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Review Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reviews_list as $r):
            $badge = match($r['status']) {
                'approved' => 'confirmed',
                'hidden'   => 'pending',
                default    => 'pending'
            };
            $stars = str_repeat('★', (int)round($r['rating'])) . str_repeat('☆', 5 - (int)round($r['rating']));
            $user_name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if (!$user_name) $user_name = htmlspecialchars($r['guest_name']);
            $created_at = date('d M Y, h:i A', strtotime($r['created_at']));
            $review_short = strlen($r['comment']) > 60 ? substr($r['comment'], 0, 60) . '...' : $r['comment'];
        ?>
          <tr id="reviewRow<?= $r['review_id'] ?>">
            <td class="text-muted small">#<?= $r['review_id'] ?></td>
            <td><div class="fw-700 small"><?= htmlspecialchars($user_name) ?></div></td>
            <td class="small"><?= htmlspecialchars($r['hotel_name'] ?? '—') ?></td>
            <td class="small text-muted"><?= htmlspecialchars($r['booking_id'] ?? '—') ?></td>
            <td><span style="color:var(--gold);font-size:.9rem"><?= $stars ?></span> <small class="text-muted"><?= $r['rating'] ?></small></td>
            <td class="small" style="max-width:220px"><?= htmlspecialchars($review_short) ?></td>
            <td class="small"><?= $created_at ?></td>
            <td id="status-cell-<?= $r['review_id'] ?>"><span class="ds-badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <button class="ds-btn gho sm" onclick="openViewReviewModal(<?= $r['review_id'] ?>)" title="View">
                  <i class="bi bi-eye-fill"></i> View
                </button>
                <?php if ($r['status'] !== 'approved'): ?>
                  <button class="ds-btn sm" style="background:#10b981;color:#fff" onclick="approveReview(<?= $r['review_id'] ?>)" title="Approve">
                    <i class="bi bi-check-lg"></i> Approve
                  </button>
                <?php endif; ?>
                <?php if ($r['status'] !== 'hidden'): ?>
                  <button class="ds-btn sm" style="background:#f59e0b;color:#fff" onclick="hideReview(<?= $r['review_id'] ?>)" title="Hide">
                    <i class="bi bi-eye-slash"></i> Hide
                  </button>
                <?php else: ?>
                  <button class="ds-btn sm" style="background:#10b981;color:#fff" onclick="approveReview(<?= $r['review_id'] ?>)" title="Unhide">
                    <i class="bi bi-eye"></i> Unhide
                  </button>
                <?php endif; ?>
                <button class="ds-btn sm" style="color:#ef4444" title="Delete" onclick="confirmDeleteReview(<?= $r['review_id'] ?>, '<?= htmlspecialchars(addslashes($r['guest_name'])) ?>')">
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

<!-- View Review Modal -->
<div class="modal fade ds-modal" id="viewReviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-eye-fill me-2"></i>Review Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="viewReviewAlert" class="alert d-none mb-3"></div>
        <div id="viewReviewLoading" class="text-center py-4">
          <span class="spinner-border text-primary"></span><div class="mt-2 text-muted">Loading review…</div>
        </div>
        <div id="viewReviewContent" class="d-none">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.1rem;font-weight:700" id="vr-avatar"></div>
            <div>
              <div class="fw-700" id="vr-user"></div>
              <div class="small text-muted" id="vr-email"></div>
            </div>
            <div class="ms-auto" id="vr-rating"></div>
          </div>
          <table class="table table-sm table-borderless mb-3">
            <tr><th class="text-muted" style="width:140px">Hotel</th><td id="vr-hotel"></td></tr>
            <tr><th class="text-muted">Booking ID</th><td id="vr-booking"></td></tr>
            <tr><th class="text-muted">Review Date</th><td id="vr-date"></td></tr>
            <tr><th class="text-muted">Status</th><td id="vr-status"></td></tr>
          </table>
          <div class="p-3 rounded" style="background:var(--srf);border:1px solid var(--bdr)">
            <div class="fw-700 small text-muted mb-1">REVIEW</div>
            <p class="mb-0" id="vr-comment" style="font-size:.9rem;line-height:1.6"></p>
          </div>
          <div id="vr-reply-wrap" class="d-none mt-3">
            <div class="p-3 rounded" style="background:var(--pr-lt);border:1px solid rgba(26,86,219,.15)">
              <div class="fw-700 small text-primary mb-1">MANAGER REPLY</div>
              <p class="mb-0 text-primary" style="font-size:.9rem;line-height:1.6" id="vr-reply"></p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade ds-modal" id="deleteReviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="bi bi-trash-fill me-2"></i>Delete Review</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p>Are you sure you want to permanently delete the review by <strong id="deleteReviewName"></strong>? This action cannot be undone.</p>
        <div id="deleteReviewError" class="alert alert-danger d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="ds-btn" style="background:#ef4444;color:#fff" onclick="submitDeleteReview()" id="confirmDeleteReviewBtn">
          <i class="bi bi-trash-fill me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="deleteReviewId"/>

<script>
function openViewReviewModal(reviewId) {
  const modal   = new bootstrap.Modal(document.getElementById('viewReviewModal'));
  const loading = document.getElementById('viewReviewLoading');
  const content = document.getElementById('viewReviewContent');
  const alertEl = document.getElementById('viewReviewAlert');

  alertEl.className = 'alert d-none mb-3';
  loading.classList.remove('d-none');
  content.classList.add('d-none');
  modal.show();

  const fd = new FormData();
  fd.append('action', 'get_review');
  fd.append('review_id', reviewId);

  fetch('manage-ratings.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    loading.classList.add('d-none');
    if (d.error) { alert('Could not load review data.'); return; }

    const user_name = (d.first_name && d.last_name) ? (d.first_name + ' ' + d.last_name) : (d.guest_name || 'Guest');
    document.getElementById('vr-user').textContent = user_name;
    document.getElementById('vr-email').textContent = d.email || '—';
    document.getElementById('vr-hotel').textContent = d.hotel_name || '—';
    document.getElementById('vr-booking').textContent = d.booking_id || '—';
    document.getElementById('vr-date').textContent = new Date(d.created_at).toLocaleString('en-IN');
    document.getElementById('vr-comment').textContent = d.comment || '';
    const replyWrap = document.getElementById('vr-reply-wrap');
    const replyEl = document.getElementById('vr-reply');
    if (d.manager_reply) {
      replyEl.textContent = d.manager_reply;
      replyWrap.classList.remove('d-none');
    } else {
      replyWrap.classList.add('d-none');
    }

    const stars = '★'.repeat(Math.round(d.rating)) + '☆'.repeat(5 - Math.round(d.rating));
    document.getElementById('vr-rating').innerHTML = '<span style="color:var(--gold);font-size:1.1rem">' + stars + '</span> <small class="text-muted">' + d.rating + '</small>';

    const avatar = document.getElementById('vr-avatar');
    avatar.textContent = user_name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();

    const statusBadge = {pending:'<span class="ds-badge pending">Pending</span>', approved:'<span class="ds-badge confirmed">Approved</span>', hidden:'<span class="ds-badge cancelled">Hidden</span>'};
    document.getElementById('vr-status').innerHTML = statusBadge[d.status] || d.status;

    content.classList.remove('d-none');
  })
  .catch(() => { loading.innerHTML = '<div class="text-danger">Failed to load review data.</div>'; });
}

function approveReview(reviewId) {
  const fd = new FormData();
  fd.append('action', 'approve_review');
  fd.append('review_id', reviewId);
  fetch('manage-ratings.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const cell = document.getElementById('status-cell-' + reviewId);
      cell.innerHTML = '<span class="ds-badge confirmed">Approved</span>';
      const row = document.getElementById('reviewRow' + reviewId);
      if (row) {
        const btns = row.querySelectorAll('button');
        btns.forEach(b => {
          if (b.textContent.includes('Approve') || b.textContent.includes('Unhide')) b.style.display = 'none';
          if (b.textContent.includes('Hide')) b.style.display = '';
        });
      }
      dsToast(d.message);
    } else {
      dsToast(d.message, 'error');
    }
  })
  .catch(() => dsToast('Network error.', 'error'));
}

function hideReview(reviewId) {
  const fd = new FormData();
  fd.append('action', 'hide_review');
  fd.append('review_id', reviewId);
  fetch('manage-ratings.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const cell = document.getElementById('status-cell-' + reviewId);
      cell.innerHTML = '<span class="ds-badge pending">Hidden</span>';
      const row = document.getElementById('reviewRow' + reviewId);
      if (row) {
        const btns = row.querySelectorAll('button');
        btns.forEach(b => {
          if (b.textContent.includes('Hide')) b.style.display = 'none';
          if (b.textContent.includes('Unhide') || b.textContent.includes('Approve')) b.style.display = '';
        });
      }
      dsToast(d.message);
    } else {
      dsToast(d.message, 'error');
    }
  })
  .catch(() => dsToast('Network error.', 'error'));
}

function confirmDeleteReview(reviewId, guestName) {
  document.getElementById('deleteReviewId').value = reviewId;
  document.getElementById('deleteReviewName').textContent = guestName;
  document.getElementById('deleteReviewError').className = 'alert alert-danger d-none mt-3';
  document.getElementById('confirmDeleteReviewBtn').disabled = false;
  document.getElementById('confirmDeleteReviewBtn').innerHTML = '<i class="bi bi-trash-fill me-1"></i>Delete';
  new bootstrap.Modal(document.getElementById('deleteReviewModal')).show();
}

function submitDeleteReview() {
  const reviewId = document.getElementById('deleteReviewId').value;
  const errorEl  = document.getElementById('deleteReviewError');
  const btn      = document.getElementById('confirmDeleteReviewBtn');

  const fd = new FormData();
  fd.append('action', 'delete_review');
  fd.append('review_id', reviewId);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting…';

  fetch('manage-ratings.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const row = document.getElementById('reviewRow' + reviewId);
      if (row) row.remove();
      bootstrap.Modal.getInstance(document.getElementById('deleteReviewModal')).hide();
      dsToast(d.message);
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
