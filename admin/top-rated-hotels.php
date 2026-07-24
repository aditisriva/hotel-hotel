<?php
require_once 'auth_guard.php';
require_once 'db.php';
require_once '../hotel/hotel_functions.php';

// ── AJAX Handlers ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'toggle_featured') {
        $hotel_id = (int)($_POST['hotel_id'] ?? 0);
        $featured = (int)($_POST['featured'] ?? 0);
        if (!$hotel_id) { echo json_encode(['success'=>false,'message'=>'Invalid hotel.']); exit; }
        $stmt = mysqli_prepare($conn, "UPDATE hotels SET featured = ? WHERE hotel_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $featured, $hotel_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success'=>$ok,'message'=>$ok?($featured?'Hotel marked as featured!':'Featured removed.'):'Database error.','featured'=>$featured]);
        exit;
    }

    if ($action === 'get_hotel_details') {
        $hotel_id = (int)($_POST['hotel_id'] ?? 0);
        if (!$hotel_id) { echo json_encode(['error'=>'Invalid hotel.']); exit; }

        $hotel = bhGetHotelById($hotel_id);
        if (!$hotel) { echo json_encode(['error'=>'Hotel not found.']); exit; }

        $avg_rating = 0;
        $total_reviews = 0;
        $rating_breakdown = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
        $reviews_list = [];

        $rev_res = mysqli_query($conn, "SELECT rating, comment, guest_name, created_at FROM reviews WHERE hotel_id = $hotel_id AND status = 'approved' ORDER BY created_at DESC LIMIT 10");
        if ($rev_res) {
            while ($rev = mysqli_fetch_assoc($rev_res)) {
                $reviews_list[] = $rev;
                $total_reviews++;
                $avg_rating += $rev['rating'];
                $star_key = (int)round($rev['rating']);
                if (isset($rating_breakdown[$star_key])) $rating_breakdown[$star_key]++;
            }
        }
        if ($total_reviews > 0) $avg_rating = round($avg_rating / $total_reviews, 1);

        $total_bookings = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE hotel_id = $hotel_id"))['c'];

        echo json_encode([
            'hotel' => $hotel,
            'avg_rating' => $avg_rating,
            'total_reviews' => $total_reviews,
            'total_bookings' => $total_bookings,
            'rating_breakdown' => $rating_breakdown,
            'reviews' => $reviews_list
        ]);
        exit;
    }
}

// ── Stats ───────────────────────────────────────────────────────────────────
$total_hotels = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels WHERE approval_status='approved'"))['c'];
$featured_hotels = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM hotels WHERE featured=1 AND approval_status='approved'"))['c'];

$avg_platform = 0;
$avg_res = mysqli_query($conn, "SELECT AVG(rating) AS a FROM reviews WHERE status='approved'");
if ($avg_res) $avg_platform = round((float)mysqli_fetch_assoc($avg_res)['a'], 1);

$high_rated = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(DISTINCT hotel_id) AS c FROM reviews WHERE status='approved' GROUP BY hotel_id HAVING AVG(rating) >= 4.5"))['c'];

// ── Filters ─────────────────────────────────────────────────────────────────
$fs = trim($_GET['q'] ?? '');
$f_filter = $_GET['filter'] ?? '';
$f_sort = $_GET['sort'] ?? 'rating_desc';
$where = ["h.approval_status = 'approved'"];

if ($fs !== '') {
    $s = mysqli_real_escape_string($conn, $fs);
    $where[] = "(h.hotel_name LIKE '%$s%' OR h.city LIKE '%$s%')";
}
if ($f_filter === 'featured') {
    $where[] = "h.featured = 1";
}

$hotels_list = [];
$sql = "SELECT h.*, 
               COALESCE(AVG(r.rating), 0) AS avg_rating,
               COUNT(r.review_id) AS total_reviews,
               (SELECT COUNT(*) FROM bookings WHERE hotel_id = h.hotel_id) AS total_bookings
        FROM hotels h
        LEFT JOIN reviews r ON h.hotel_id = r.hotel_id AND r.status = 'approved'
        WHERE " . implode(' AND ', $where) . "
        GROUP BY h.hotel_id";

switch ($f_sort) {
    case 'rating_desc': $sql .= " ORDER BY avg_rating DESC, total_reviews DESC"; break;
    case 'rating_asc':  $sql .= " ORDER BY avg_rating ASC, total_reviews DESC"; break;
    case 'reviews_desc':$sql .= " ORDER BY total_reviews DESC, avg_rating DESC"; break;
    case 'bookings_desc':$sql .= " ORDER BY total_bookings DESC, avg_rating DESC"; break;
    default:            $sql .= " ORDER BY avg_rating DESC, total_reviews DESC";
}

$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $hotels_list[] = $row;

$pageTitle    = 'Top Rated Hotels';
$pageSubtitle = 'Ranking and management of highest rated properties';
include 'partials/header.php';
?>

<!-- Stats -->
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat blue"><div class="ds-si"><i class="bi bi-award-fill"></i></div>
      <div class="ds-sn"><?= $featured_hotels ?></div><div class="ds-sl">Total Top Rated Hotels</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat gold"><div class="ds-si"><i class="bi bi-trophy-fill"></i></div>
      <div class="ds-sn"><?= $high_rated ?></div><div class="ds-sl">Highest Rated (4.5+)</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat green"><div class="ds-si"><i class="bi bi-star-fill"></i></div>
      <div class="ds-sn"><?= $avg_platform ?>/5</div><div class="ds-sl">Average Platform Rating</div></div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="ds-stat purple"><div class="ds-si"><i class="bi bi-building-check"></i></div>
      <div class="ds-sn"><?= $total_hotels ?></div><div class="ds-sl">Total Approved Hotels</div></div>
  </div>
</section>

<!-- Hotels Table -->
<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-award-fill me-2"></i>Top Rated Hotels Ranking (<?= count($hotels_list) ?>)</div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="filterForm">
        <div class="ds-sw">
          <i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search hotel or city…"
                 value="<?= htmlspecialchars($fs) ?>" style="width:200px"/>
        </div>
        <select class="ds-inp ds-sel" name="filter" style="width:150px" onchange="this.form.submit()">
          <option value="">All Hotels</option>
          <option value="featured" <?= $f_filter==='featured'?'selected':'' ?>>Featured Hotels</option>
          <option value="5" <?= $f_filter==='5'?'selected':'' ?>>Rating 5★</option>
          <option value="4" <?= $f_filter==='4'?'selected':'' ?>>Rating 4★+</option>
          <option value="3" <?= $f_filter==='3'?'selected':'' ?>>Rating 3★+</option>
        </select>
        <select class="ds-inp ds-sel" name="sort" style="width:160px" onchange="this.form.submit()">
          <option value="rating_desc" <?= $f_sort==='rating_desc'?'selected':'' ?>>Highest Rating</option>
          <option value="reviews_desc" <?= $f_sort==='reviews_desc'?'selected':'' ?>>Most Reviews</option>
          <option value="bookings_desc" <?= $f_sort==='bookings_desc'?'selected':'' ?>>Most Bookings</option>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="top-rated-hotels.php" class="ds-btn gho sm">Clear</a>
      </form>
    </div>
  </div>
  <div class="ds-cb p-0" id="hotelTableContainer">
    <?php if (empty($hotels_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-building" style="font-size:3rem;opacity:.3"></i>
        <div class="fw-bold mt-3">No hotels found</div>
        <div class="small mt-1">Hotels with approved reviews will appear here.</div>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Hotel Image</th>
            <th>Hotel Name</th>
            <th>City</th>
            <th>Average Rating</th>
            <th>Total Reviews</th>
            <th>Total Bookings</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($hotels_list as $idx => $h):
            $img = bhFirstImage($h['hotel_images'] ?? '', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=80&q=60');
            $stars = str_repeat('★', (int)round($h['avg_rating'])) . str_repeat('☆', 5 - (int)round($h['avg_rating']));
            $rank_num = $idx + 1;
            $rank_color = $rank_num == 1 ? '#f59e0b' : ($rank_num == 2 ? '#94a3b8' : ($rank_num == 3 ? '#cd7f32' : '#64748b'));
            $featured = (int)$h['featured'];
        ?>
          <tr id="hotelRow<?= $h['hotel_id'] ?>">
            <td><div class="fw-700 fs-5" style="color:<?= $rank_color ?>">#<?= $rank_num ?></div></td>
            <td>
              <img src="<?= htmlspecialchars($img) ?>" style="width:50px;height:38px;object-fit:cover;border-radius:6px" alt=""
                   onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=80&q=60'"/>
            </td>
            <td><div class="fw-700"><?= htmlspecialchars($h['hotel_name']) ?></div></td>
            <td class="small"><?= htmlspecialchars(ucfirst($h['city'])) ?></td>
            <td>
              <div class="d-flex align-items-center gap-1">
                <span style="color:var(--gold);font-size:.9rem"><?= $stars ?></span>
                <small class="text-muted"><?= $h['avg_rating'] ?></small>
              </div>
            </td>
            <td class="small"><?= $h['total_reviews'] ?></td>
            <td class="small"><?= $h['total_bookings'] ?></td>
            <td id="featured-cell-<?= $h['hotel_id'] ?>">
              <?php if ($featured): ?>
                <span class="ds-badge confirmed"><i class="bi bi-star-fill"></i> Featured</span>
              <?php else: ?>
                <span class="ds-badge pending">Normal</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <button class="ds-btn gho sm" onclick="openViewDetailsModal(<?= $h['hotel_id'] ?>)" title="View Details">
                  <i class="bi bi-eye-fill"></i> View
                </button>
                <?php if ($featured): ?>
                  <button id="feat-btn-<?= $h['hotel_id'] ?>" class="ds-btn sm" style="background:#ef4444;color:#fff"
                          onclick="toggleFeatured(<?= $h['hotel_id'] ?>, 0, this)" title="Remove Featured">
                    <i class="bi bi-star-slash"></i> Unfeature
                  </button>
                <?php else: ?>
                  <button id="feat-btn-<?= $h['hotel_id'] ?>" class="ds-btn sm" style="background:#f59e0b;color:#fff"
                          onclick="toggleFeatured(<?= $h['hotel_id'] ?>, 1, this)" title="Mark Featured">
                    <i class="bi bi-star-fill"></i> Feature
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

<!-- View Details Modal -->
<div class="modal fade ds-modal" id="viewDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-building me-2"></i>Hotel Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="viewDetailsLoading" class="text-center py-4">
          <span class="spinner-border text-primary"></span><div class="mt-2 text-muted">Loading hotel details…</div>
        </div>
        <div id="viewDetailsContent" class="d-none">
          <div class="d-flex align-items-start gap-3 mb-4">
            <img id="vd-image" src="" style="width:100px;height:80px;object-fit:cover;border-radius:10px" alt=""/>
            <div class="flex-grow-1">
              <div class="fw-800 fs-5" id="vd-name"></div>
              <div class="small text-muted" id="vd-location"></div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="rating-badge" id="vd-rating"></span>
                <span class="small text-muted" id="vd-reviews"></span>
                <span class="small text-muted" id="vd-bookings"></span>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="p-3 rounded" style="background:var(--srf);border:1px solid var(--bdr)">
                <div class="fw-700 small text-muted mb-2">RATING BREAKDOWN</div>
                <div id="vd-breakdown"></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded" style="background:var(--srf);border:1px solid var(--bdr)">
                <div class="fw-700 small text-muted mb-2">HOTEL INFO</div>
                <table class="table table-sm table-borderless mb-0">
                  <tr><th class="text-muted" style="width:120px">Price/Night</th><td id="vd-price"></td></tr>
                  <tr><th class="text-muted">Property Type</th><td id="vd-type"></td></tr>
                  <tr><th class="text-muted">Capacity</th><td id="vd-capacity"></td></tr>
                  <tr><th class="text-muted">Check-in</th><td id="vd-checkin"></td></tr>
                  <tr><th class="text-muted">Check-out</th><td id="vd-checkout"></td></tr>
                </table>
              </div>
            </div>
          </div>

          <div class="fw-700 small text-muted mb-2">RECENT REVIEWS</div>
          <div id="vd-reviews" class="d-flex flex-column gap-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ds-btn gho" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function toggleFeatured(hotelId, featured, btn) {
  const fd = new FormData();
  fd.append('action', 'toggle_featured');
  fd.append('hotel_id', hotelId);
  fd.append('featured', featured);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

  fetch('top-rated-hotels.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    btn.disabled = false;
    if (d.success) {
      const cell = document.getElementById('featured-cell-' + hotelId);
      if (featured) {
        cell.innerHTML = '<span class="ds-badge confirmed"><i class="bi bi-star-fill"></i> Featured</span>';
        btn.style.background = '#ef4444'; btn.style.color = '#fff';
        btn.innerHTML = '<i class="bi bi-star-slash"></i> Unfeature';
        btn.onclick = () => toggleFeatured(hotelId, 0, btn);
      } else {
        cell.innerHTML = '<span class="ds-badge pending">Normal</span>';
        btn.style.background = '#f59e0b'; btn.style.color = '#fff';
        btn.innerHTML = '<i class="bi bi-star-fill"></i> Feature';
        btn.onclick = () => toggleFeatured(hotelId, 1, btn);
      }
      dsToast(d.message);
    } else {
      dsToast(d.message, 'error');
    }
  })
  .catch(() => { dsToast('Network error.', 'error'); btn.disabled = false; });
}

function openViewDetailsModal(hotelId) {
  const modal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
  const loading = document.getElementById('viewDetailsLoading');
  const content = document.getElementById('viewDetailsContent');

  loading.classList.remove('d-none');
  content.classList.add('d-none');
  modal.show();

  const fd = new FormData();
  fd.append('action', 'get_hotel_details');
  fd.append('hotel_id', hotelId);

  fetch('top-rated-hotels.php', { method:'POST', body: fd })
  .then(r => r.json())
  .then(d => {
    loading.classList.add('d-none');
    if (d.error) { alert(d.error); return; }

    const h = d.hotel;
    document.getElementById('vd-image').src = bhFirstImage(h.hotel_images, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=200&q=80');
    document.getElementById('vd-name').textContent = h.hotel_name;
    document.getElementById('vd-location').textContent = (h.location || '') + ', ' + (h.city || '').charAt(0).toUpperCase() + (h.city || '').slice(1);
    document.getElementById('vd-rating').innerHTML = '<i class="bi bi-star-fill text-warning"></i> ' + d.avg_rating;
    document.getElementById('vd-reviews').textContent = d.total_reviews + ' reviews';
    document.getElementById('vd-bookings').textContent = d.total_bookings + ' bookings';
    document.getElementById('vd-price').textContent = '₹' + Number(h.price_per_night).toLocaleString() + '/night';
    document.getElementById('vd-type').textContent = (h.property_type || 'hotel').charAt(0).toUpperCase() + (h.property_type || 'hotel').slice(1);
    document.getElementById('vd-capacity').textContent = (h.capacity || 2) + ' guests';
    document.getElementById('vd-checkin').textContent = h.checkin_time || '14:00';
    document.getElementById('vd-checkout').textContent = h.checkout_time || '11:00';

    const breakdown = d.rating_breakdown;
    const total = d.total_reviews || 1;
    let bhtml = '';
    for (let s = 5; s >= 1; s--) {
      const pct = total > 0 ? Math.round((breakdown[s] / total) * 100) : 0;
      bhtml += '<div class="d-flex align-items-center gap-2 mb-1"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">' + s + '★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:' + pct + '%;background:var(--gold)"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">' + pct + '%</span></div>';
    }
    document.getElementById('vd-breakdown').innerHTML = bhtml;

    const revContainer = document.getElementById('vd-reviews');
    if (d.reviews && d.reviews.length > 0) {
      revContainer.innerHTML = d.reviews.map(r => {
        const stars = '★'.repeat(Math.round(r.rating)) + '☆'.repeat(5 - Math.round(r.rating));
        return '<div class="p-3 rounded" style="background:var(--srf);border:1px solid var(--bdr)"><div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-700 small">' + (r.guest_name || 'Guest') + '</span><span style="color:var(--gold);font-size:.85rem">' + stars + '</span></div><p class="mb-0 small" style="font-size:.85rem;line-height:1.5">' + (r.comment || '') + '</p><div class="small text-muted mt-1">' + new Date(r.created_at).toLocaleDateString('en-IN') + '</div></div>';
      }).join('');
    } else {
      revContainer.innerHTML = '<div class="text-center text-muted py-3">No reviews yet.</div>';
    }

    content.classList.remove('d-none');
  })
  .catch(() => { loading.innerHTML = '<div class="text-danger">Failed to load details.</div>'; });
}
</script>

<?php include 'partials/footer.php'; ?>
