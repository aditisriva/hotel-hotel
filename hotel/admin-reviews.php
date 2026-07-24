<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';
require_once '../hotel/hotel_functions.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

$hotel_id = (int)($_SESSION['hm_hotel_id'] ?? 0);
if (!$hotel_id && $manager) {
    $hotel_id = (int)($manager['hotel_id'] ?? 0);
}

// ── Auto-create reviews table ─────────────────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id`    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hotel_id`     INT UNSIGNED NOT NULL DEFAULT 1,
  `booking_id`   VARCHAR(20) DEFAULT NULL,
  `guest_name`   VARCHAR(255) NOT NULL,
  `guest_email`  VARCHAR(255) DEFAULT NULL,
  `room_name`    VARCHAR(100) DEFAULT NULL,
  `checkin_date` DATE DEFAULT NULL,
  `checkout_date`DATE DEFAULT NULL,
  `rating`       TINYINT NOT NULL DEFAULT 5,
  `review_text`  TEXT DEFAULT NULL,
  `reply_text`   TEXT DEFAULT NULL,
  `reply_status` ENUM('pending','replied') DEFAULT 'pending',
  `replied_at`   TIMESTAMP NULL DEFAULT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_hotel`  (`hotel_id`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_status` (`reply_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── AJAX handlers ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Submit reply
    if ($action === 'reply') {
        $rid   = (int)($_POST['review_id'] ?? 0);
        $reply = trim($_POST['reply_text'] ?? '');
        if (!$rid || !$reply) { echo json_encode(['success'=>false,'error'=>'Reply text is required']); exit; }
        $r     = mysqli_real_escape_string($conn, $reply);
        $now   = date('Y-m-d H:i:s');
        $ok    = mysqli_query($conn, "UPDATE reviews SET reply_text='$r', reply_status='replied', replied_at='$now' WHERE review_id=$rid AND hotel_id=$hotel_id");
        echo json_encode(['success'=>(bool)$ok, 'replied_at' => date('d M Y, h:i A')]);
        exit;
    }

    // Delete review (admin action)
    if ($action === 'delete') {
        $rid = (int)($_POST['review_id'] ?? 0);
        $ok  = $rid ? mysqli_query($conn, "DELETE FROM reviews WHERE review_id=$rid AND hotel_id=$hotel_id") : false;
        echo json_encode(['success'=>(bool)$ok]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action']);
    exit;
}

// ── Filters ───────────────────────────────────────────────────────────────
$filter_rating  = isset($_GET['rating'])  ? (int)$_GET['rating']        : 0;
$filter_status  = isset($_GET['status'])  ? trim($_GET['status'])        : '';
$search         = isset($_GET['q'])       ? trim($_GET['q'])             : '';
$page           = max(1, (int)($_GET['page'] ?? 1));
$per_page       = 8;

$where = ["hotel_id = $hotel_id"];
if ($filter_rating > 0) $where[] = "rating = $filter_rating";
if ($filter_status === 'pending') $where[] = "reply_status = 'pending'";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(guest_name LIKE '%$s%' OR booking_id LIKE '%$s%')";
}
$whereSQL = implode(' AND ', $where);

// Stats (always full hotel, no filter)
$base_where = "hotel_id = $hotel_id";
$stat_res = mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    IFNULL(ROUND(AVG(rating),1),0) AS avg_rating,
    SUM(rating >= 4) AS positive,
    SUM(reply_status='pending') AS pending,
    SUM(rating=5) AS r5,
    SUM(rating=4) AS r4,
    SUM(rating=3) AS r3,
    SUM(rating=2) AS r2,
    SUM(rating=1) AS r1
    FROM reviews WHERE $base_where");
$stats = mysqli_fetch_assoc($stat_res) ?? [];
$total_reviews  = (int)($stats['total']   ?? 0);
$avg_rating     = (float)($stats['avg_rating'] ?? 0);
$positive_pct   = $total_reviews > 0 ? round((int)$stats['positive'] / $total_reviews * 100) : 0;
$pending_replies= (int)($stats['pending'] ?? 0);

$total_rows   = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM reviews WHERE $whereSQL"))['c'];
$total_pages  = max(1, ceil($total_rows / $per_page));
$offset       = ($page - 1) * $per_page;

$reviews = [];
$res = mysqli_query($conn, "SELECT * FROM reviews WHERE $whereSQL ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
if ($res) while ($row = mysqli_fetch_assoc($res)) $reviews[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Reviews – Hotel Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="dashboard.css"/>
<style>
.review-card{background:#fff;border:1.5px solid var(--bdr);border-radius:var(--r);padding:1.25rem 1.5rem;margin-bottom:1rem;transition:box-shadow .2s}
.review-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09)}
.guest-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;flex-shrink:0;color:#fff}
.star-bar-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.45rem}
.star-bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden}
.star-bar-fill{height:100%;border-radius:999px;transition:width .6s}
.star-bar-count{font-size:.78rem;font-weight:700;color:var(--mut);min-width:28px;text-align:right}
.star-bar-label{font-size:.78rem;font-weight:600;min-width:36px}
.reply-box{background:linear-gradient(135deg,#f0f7ff,#e8f0fe);border:1.5px solid #bfdbfe;border-radius:10px;padding:.85rem 1rem;margin-top:.85rem}
.rating-stars{color:#f59e0b;font-size:.95rem;letter-spacing:.05rem}
.review-meta{font-size:.75rem;color:var(--mut)}
.filter-chip{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .85rem;border-radius:999px;font-size:.78rem;font-weight:600;border:1.5px solid var(--bdr);background:#fff;color:var(--txt2);cursor:pointer;text-decoration:none;transition:.15s}
.filter-chip:hover,.filter-chip.active{background:var(--pr);color:#fff;border-color:var(--pr)}
.page-link-ds{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1.5px solid var(--bdr);font-size:.82rem;font-weight:600;color:var(--txt2);text-decoration:none;transition:.15s}
.page-link-ds:hover,.page-link-ds.active{background:var(--pr);color:#fff;border-color:var(--pr)}
.overall-score{font-size:3.5rem;font-weight:800;line-height:1;color:var(--txt)}
.overall-label{font-size:.78rem;font-weight:600;color:var(--mut);text-transform:uppercase;letter-spacing:.06em}
</style>
</head>
<body>
<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo">
    <div class="ds-logo-icon"><i class="bi bi-buildings"></i></div>
    <div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Hotel Operations</div></div>
  </a>
  <nav class="ds-nav">
    <div class="ds-sec">Main</div>
    <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
    <a href="admin-hotel-profile.php" class="ds-link"><i class="bi bi-building"></i> Hotel Management</a>
    <div class="ds-sec">Operations</div>
    <a href="admin-rooms.php" class="ds-link"><i class="bi bi-door-open-fill"></i> Rooms</a>
    <a href="admin-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Bookings</a>
    <a href="admin-guests.php" class="ds-link"><i class="bi bi-people-fill"></i> Guests</a>
    <div class="ds-sec">Insights</div>
    <a href="admin-reviews.php" class="ds-link active"><i class="bi bi-star-fill"></i> Reviews</a>
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
    <div>
      <div class="ds-page-title">Guest Reviews</div>
      <div class="ds-breadcrumb">Dashboard / Reviews · <?php echo $total_reviews; ?> total reviews</div>
    </div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i></a>
    <div class="ds-avbtn" id="dsAvBtn">
      <div class="ds-av"><?= $manager_initials ?></div><span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/>
        <a href="login.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="ds-stat gold">
      <div class="ds-si"><i class="bi bi-star-fill"></i></div>
      <div class="ds-sn"><?php echo $avg_rating > 0 ? number_format($avg_rating,1) : '—'; ?></div>
      <div class="ds-sl">Overall Rating</div>
      <div class="ds-tr up"><i class="bi bi-star-fill"></i><?php
        for($s=1;$s<=5;$s++) echo $s<=$avg_rating ? '★' : '☆';
      ?></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="ds-stat blue">
      <div class="ds-si"><i class="bi bi-chat-quote-fill"></i></div>
      <div class="ds-sn"><?php echo $total_reviews; ?></div>
      <div class="ds-sl">Total Reviews</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="ds-stat green">
      <div class="ds-si"><i class="bi bi-hand-thumbs-up-fill"></i></div>
      <div class="ds-sn"><?php echo $positive_pct; ?>%</div>
      <div class="ds-sl">Positive Reviews</div>
      <div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>4★ and above</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="ds-stat <?php echo $pending_replies>0?'red':'green'; ?>">
      <div class="ds-si"><i class="bi bi-reply-fill"></i></div>
      <div class="ds-sn"><?php echo $pending_replies; ?></div>
      <div class="ds-sl">Pending Replies</div>
      <div class="ds-tr <?php echo $pending_replies>0?'down':'up'; ?>">
        <?php echo $pending_replies>0 ? '<i class="bi bi-exclamation-circle"></i>Needs attention' : '<i class="bi bi-check-circle"></i>All replied'; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- LEFT: Rating Breakdown -->
  <div class="col-12 col-xl-4">
    <div class="ds-card h-100">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-bar-chart-fill"></i> Rating Breakdown</div></div>
      <div class="ds-cb">
        <!-- Overall score -->
        <div class="text-center mb-4">
          <div class="overall-score"><?php echo $avg_rating > 0 ? number_format($avg_rating,1) : '—'; ?></div>
          <div class="rating-stars my-1">
            <?php for($s=1;$s<=5;$s++): ?>
            <i class="bi bi-star<?php echo $s<=$avg_rating?'-fill':($s-0.5<=$avg_rating?'-half':''); ?>"></i>
            <?php endfor; ?>
          </div>
          <div class="overall-label">Based on <?php echo $total_reviews; ?> reviews</div>
        </div>
        <!-- Star bars -->
        <?php
        $star_data = [5=>(int)$stats['r5'],4=>(int)$stats['r4'],3=>(int)$stats['r3'],2=>(int)$stats['r2'],1=>(int)$stats['r1']];
        $bar_colors = [5=>'#10b981',4=>'#34d399',3=>'#f59e0b',2=>'#f97316',1=>'#ef4444'];
        foreach ($star_data as $n => $cnt):
            $pct = $total_reviews > 0 ? round($cnt/$total_reviews*100) : 0;
        ?>
        <div class="star-bar-row">
          <span class="star-bar-label fw-700 small"><?php echo $n; ?> <i class="bi bi-star-fill text-warning" style="font-size:.7rem"></i></span>
          <div class="star-bar-track">
            <div class="star-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bar_colors[$n]; ?>"></div>
          </div>
          <span class="star-bar-count"><?php echo $cnt; ?></span>
          <span class="text-muted" style="font-size:.72rem;min-width:34px"><?php echo $pct; ?>%</span>
        </div>
        <?php endforeach; ?>

        <!-- Quick filter chips -->
        <hr class="my-3"/>
        <div class="d-flex flex-wrap gap-2">
          <a href="?<?php echo http_build_query(['q'=>$search]); ?>"
             class="filter-chip <?php echo !$filter_rating&&!$filter_status?'active':''; ?>">All</a>
          <?php for($n=5;$n>=1;$n--): ?>
          <a href="?<?php echo http_build_query(['rating'=>$n,'q'=>$search]); ?>"
             class="filter-chip <?php echo $filter_rating===$n?'active':''; ?>">
            <?php echo $n; ?> <i class="bi bi-star-fill" style="font-size:.65rem"></i>
          </a>
          <?php endfor; ?>
          <a href="?<?php echo http_build_query(['status'=>'pending','q'=>$search]); ?>"
             class="filter-chip <?php echo $filter_status==='pending'?'active':''; ?>">
            <i class="bi bi-clock-fill"></i> Pending
            <?php if($pending_replies>0): ?>
            <span class="badge bg-danger ms-1" style="font-size:.6rem"><?php echo $pending_replies; ?></span>
            <?php endif; ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: Reviews List -->
  <div class="col-12 col-xl-8">
    <div class="ds-card">
      <div class="ds-ch">
        <div class="ds-ct"><i class="bi bi-chat-quote-fill"></i> Reviews
          <span class="badge bg-primary ms-2" style="font-size:.7rem"><?php echo $total_rows; ?></span>
        </div>
        <!-- Search -->
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
          <?php if($filter_rating): ?><input type="hidden" name="rating" value="<?php echo $filter_rating; ?>"/><?php endif; ?>
          <?php if($filter_status): ?><input type="hidden" name="status" value="<?php echo $filter_status; ?>"/><?php endif; ?>
          <div class="ds-sw">
            <i class="bi bi-search ds-si-ic"></i>
            <input class="ds-inp search" name="q" placeholder="Guest name or Booking ID..." value="<?php echo htmlspecialchars($search); ?>" style="width:220px"/>
          </div>
          <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
          <?php if($search||$filter_rating||$filter_status): ?>
          <a href="admin-reviews.php" class="ds-btn gho sm"><i class="bi bi-x-lg"></i></a>
          <?php endif; ?>
        </form>
      </div>
      <div class="ds-cb" id="reviewsList">

        <?php if (empty($reviews)): ?>
        <div class="text-center py-5">
          <i class="bi bi-chat-x" style="font-size:3rem;color:#cbd5e1"></i>
          <div class="fw-700 mt-3" style="color:#64748b">No reviews found</div>
          <div class="text-muted small mt-1">
            <?php echo ($search||$filter_rating||$filter_status) ? 'Try clearing filters.' : 'Reviews from guests will appear here.'; ?>
          </div>
        </div>
        <?php else: ?>

        <?php foreach ($reviews as $rv):
          $stars     = (int)$rv['rating'];
          $initials  = strtoupper(substr($rv['guest_name'],0,1));
          $colors    = ['#1a56db','#059669','#d97706','#7c3aed','#dc2626','#0891b2','#be185d'];
          $avatarBg  = $colors[crc32($rv['guest_name']) % count($colors)];
          $isPending = $rv['reply_status'] === 'pending';
          $ci = $rv['checkin_date']  ? date('d M Y', strtotime($rv['checkin_date']))  : '—';
          $co = $rv['checkout_date'] ? date('d M Y', strtotime($rv['checkout_date'])) : '—';
          $reviewDate= date('d M Y', strtotime($rv['created_at']));
        ?>
        <div class="review-card" id="reviewCard<?php echo $rv['review_id']; ?>">
          <div class="d-flex gap-3 align-items-start">
            <!-- Avatar -->
            <div class="guest-avatar" style="background:<?php echo $avatarBg; ?>">
              <?php echo htmlspecialchars($initials); ?>
            </div>
            <!-- Content -->
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <div class="fw-700" style="font-size:.95rem"><?php echo htmlspecialchars($rv['guest_name']); ?></div>
                  <div class="review-meta d-flex flex-wrap gap-2 mt-1">
                    <?php if($rv['booking_id']): ?>
                    <span><i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($rv['booking_id']); ?></span>
                    <?php endif; ?>
                    <?php if($rv['room_name']): ?>
                    <span><i class="bi bi-door-open me-1"></i><?php echo htmlspecialchars($rv['room_name']); ?></span>
                    <?php endif; ?>
                    <?php if($rv['checkin_date']): ?>
                    <span><i class="bi bi-calendar-check me-1"></i><?php echo $ci; ?> → <?php echo $co; ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-clock me-1"></i><?php echo $reviewDate; ?></span>
                  </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <!-- Stars -->
                  <div class="rating-stars">
                    <?php for($s=1;$s<=5;$s++) echo '<i class="bi bi-star'.($s<=$stars?'-fill':'').'"></i>'; ?>
                    <span class="fw-700 ms-1" style="font-size:.85rem;color:var(--txt)"><?php echo $stars; ?>.0</span>
                  </div>
                  <!-- Status badge -->
                  <span class="ds-badge <?php echo $isPending?'pending':'confirmed'; ?>">
                    <?php echo $isPending ? '<i class="bi bi-clock me-1"></i>Pending Reply' : '<i class="bi bi-check-circle-fill me-1"></i>Replied'; ?>
                  </span>
                </div>
              </div>

              <!-- Review Text -->
              <?php if($rv['review_text']): ?>
              <p class="mt-2 mb-0" style="font-size:.88rem;color:var(--txt2);line-height:1.65">
                "<?php echo htmlspecialchars($rv['review_text']); ?>"
              </p>
              <?php endif; ?>

              <!-- Manager Reply -->
              <?php if(!$isPending && $rv['reply_text']): ?>
              <div class="reply-box mt-2" id="replyBox<?php echo $rv['review_id']; ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="bi bi-building-fill" style="color:var(--pr)"></i>
                  <span class="fw-700 small" style="color:var(--pr)">Manager Reply</span>
                  <?php if($rv['replied_at']): ?>
                  <span class="text-muted" style="font-size:.7rem">· <?php echo date('d M Y', strtotime($rv['replied_at'])); ?></span>
                  <?php endif; ?>
                </div>
                <p class="mb-0 small" style="color:var(--txt2)"><?php echo htmlspecialchars($rv['reply_text']); ?></p>
              </div>
              <?php endif; ?>

              <!-- Reply button (pending only) -->
              <?php if($isPending): ?>
              <div class="mt-2 d-flex gap-2" id="replyActions<?php echo $rv['review_id']; ?>">
                <button class="ds-btn prim sm"
                  onclick="openReplyModal(<?php echo $rv['review_id']; ?>,<?php echo htmlspecialchars(json_encode($rv['guest_name']),ENT_QUOTES); ?>,<?php echo htmlspecialchars(json_encode($rv['review_text']??''),ENT_QUOTES); ?>,<?php echo $stars; ?>)">
                  <i class="bi bi-reply-fill me-1"></i>Reply Now
                </button>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1):
          $qs = http_build_query(['q'=>$search,'rating'=>$filter_rating,'status'=>$filter_status]);
        ?>
        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3" style="border-color:var(--bdr)!important">
          <div class="text-muted small">Showing <?php echo $offset+1; ?>–<?php echo min($offset+$per_page,$total_rows); ?> of <?php echo $total_rows; ?></div>
          <div class="d-flex gap-1">
            <?php if($page>1): ?>
            <a class="page-link-ds" href="?page=<?php echo $page-1; ?>&<?php echo $qs; ?>"><i class="bi bi-chevron-left"></i></a>
            <?php endif;
            for($p=max(1,$page-2);$p<=min($total_pages,$page+2);$p++): ?>
            <a class="page-link-ds <?php echo $p===$page?'active':''; ?>" href="?page=<?php echo $p; ?>&<?php echo $qs; ?>"><?php echo $p; ?></a>
            <?php endfor;
            if($page<$total_pages): ?>
            <a class="page-link-ds" href="?page=<?php echo $page+1; ?>&<?php echo $qs; ?>"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

      </div><!-- end ds-cb -->
    </div>
  </div>
</div><!-- end row -->

</main>

<!-- REPLY MODAL -->
<div class="modal fade ds-modal" id="replyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-reply-fill me-2"></i>Reply to Review</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Original review preview -->
        <div class="p-3 rounded-3 mb-3" style="background:var(--srf);border:1.5px solid var(--bdr)">
          <div class="d-flex align-items-center gap-2 mb-1">
            <div id="modalAvatar" class="guest-avatar" style="width:32px;height:32px;font-size:.78rem;background:#1a56db"></div>
            <div>
              <div class="fw-700 small" id="modalGuestName"></div>
              <div class="rating-stars" id="modalStars" style="font-size:.8rem"></div>
            </div>
          </div>
          <p class="mb-0 small text-muted fst-italic" id="modalReviewText"></p>
        </div>
        <!-- Reply textarea -->
        <div class="ds-lbl">Your Reply <span class="text-danger">*</span></div>
        <textarea class="ds-inp" id="replyTextarea" rows="4" placeholder="Write a professional, helpful reply to this guest's review..."></textarea>
        <div class="text-muted small mt-1" id="replyCharCount">0 / 500 characters</div>
        <div id="replyError" class="text-danger small mt-1 d-none"></div>
      </div>
      <div class="modal-footer">
        <button class="ds-btn gho" data-bs-dismiss="modal">Cancel</button>
        <button class="ds-btn prim" id="submitReplyBtn">
          <i class="bi bi-send-fill me-1"></i>Submit Reply
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
<script>
let _activeReviewId = null;

// Star colors based on rating
const starColors = {5:'#10b981',4:'#34d399',3:'#f59e0b',2:'#f97316',1:'#ef4444'};

function openReplyModal(reviewId, guestName, reviewText, rating) {
  _activeReviewId = reviewId;

  // Fill modal
  const initials = guestName.charAt(0).toUpperCase();
  const colors   = ['#1a56db','#059669','#d97706','#7c3aed','#dc2626','#0891b2','#be185d'];
  const idx      = Math.abs(guestName.split('').reduce((a,c)=>a+c.charCodeAt(0),0)) % colors.length;
  document.getElementById('modalAvatar').textContent         = initials;
  document.getElementById('modalAvatar').style.background   = colors[idx];
  document.getElementById('modalGuestName').textContent     = guestName;
  document.getElementById('modalReviewText').textContent    = reviewText ? '"' + reviewText + '"' : 'No review text provided.';

  let starsHtml = '';
  for (let s=1;s<=5;s++) starsHtml += `<i class="bi bi-star${s<=rating?'-fill':''}"></i>`;
  document.getElementById('modalStars').innerHTML = starsHtml;

  document.getElementById('replyTextarea').value = '';
  document.getElementById('replyError').classList.add('d-none');
  document.getElementById('replyCharCount').textContent = '0 / 500 characters';

  new bootstrap.Modal(document.getElementById('replyModal')).show();
  setTimeout(() => document.getElementById('replyTextarea').focus(), 400);
}

// Char counter
document.getElementById('replyTextarea').addEventListener('input', function() {
  const len = this.value.length;
  document.getElementById('replyCharCount').textContent = len + ' / 500 characters';
  if (len > 500) {
    this.value = this.value.substring(0,500);
    document.getElementById('replyCharCount').textContent = '500 / 500 characters';
  }
});

// Submit reply
document.getElementById('submitReplyBtn').addEventListener('click', function() {
  const replyText = document.getElementById('replyTextarea').value.trim();
  const errEl = document.getElementById('replyError');
  if (!replyText) {
    errEl.textContent = 'Please write a reply before submitting.';
    errEl.classList.remove('d-none');
    return;
  }
  errEl.classList.add('d-none');
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
  this.disabled = true;

  const fd = new FormData();
  fd.append('action','reply');
  fd.append('review_id', _activeReviewId);
  fd.append('reply_text', replyText);

  fetch('admin-reviews.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        // Update DOM without reload
        const card = document.getElementById('reviewCard' + _activeReviewId);

        // Remove "Reply Now" button area
        const actEl = document.getElementById('replyActions' + _activeReviewId);
        if (actEl) actEl.remove();

        // Update status badge
        const badge = card.querySelector('.ds-badge');
        if (badge) {
          badge.className = 'ds-badge confirmed';
          badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Replied';
        }

        // Inject reply box
        const existing = document.getElementById('replyBox' + _activeReviewId);
        const replyHtml = `<div class="reply-box mt-2" id="replyBox${_activeReviewId}">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-building-fill" style="color:var(--pr)"></i>
            <span class="fw-700 small" style="color:var(--pr)">Manager Reply</span>
            <span class="text-muted" style="font-size:.7rem">· ${d.replied_at}</span>
          </div>
          <p class="mb-0 small" style="color:var(--txt2)">${replyText.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>
        </div>`;
        if (!existing) {
          card.querySelector('.flex-grow-1').insertAdjacentHTML('beforeend', replyHtml);
        }

        // Update pending count in header stat card
        const pendingEl = document.querySelector('.ds-stat.red .ds-sn, .ds-stat.green .ds-sn');

        bootstrap.Modal.getInstance(document.getElementById('replyModal')).hide();
        dsToast('Reply submitted successfully!', 'success');
      } else {
        errEl.textContent = d.error || 'Something went wrong. Please try again.';
        errEl.classList.remove('d-none');
        dsToast('Failed to submit reply', 'error');
      }
      this.innerHTML = '<i class="bi bi-send-fill me-1"></i>Submit Reply';
      this.disabled = false;
    })
    .catch(() => {
      errEl.textContent = 'Network error. Please check your connection.';
      errEl.classList.remove('d-none');
      this.innerHTML = '<i class="bi bi-send-fill me-1"></i>Submit Reply';
      this.disabled = false;
    });
});
</script>
</body>
</html>
