<?php
session_start();
require_once 'db.php';
require_once 'hotel_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
$userName   = $is_logged_in ? ($_SESSION['user_name'] ?? 'User') : 'Guest';
$userEmail  = $is_logged_in ? htmlspecialchars($_SESSION['user_email'] ?? '') : '';
$userAvatar = $is_logged_in ? strtoupper(substr($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)) : '?';

// Handle AJAX booking review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    header('Content-Type: application/json');
    if (!$is_logged_in) {
        echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.']);
        exit;
    }
    
    $booking_id = sanitize($_POST['booking_id'] ?? '');
    $rating = (float)($_POST['rating'] ?? 0);
    $comment = trim($_POST['review_text'] ?? '');
    
    if (empty($booking_id) || $rating < 1 || $rating > 5 || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid rating and review comment.']);
        exit;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $booking_query = mysqli_query($conn, "SELECT hotel_id, booking_status, checkout_date FROM bookings WHERE booking_id = '" . mysqli_real_escape_string($conn, $booking_id) . "' AND user_id = $user_id LIMIT 1");
    if (!$booking_query || mysqli_num_rows($booking_query) === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }
    
    $booking = mysqli_fetch_assoc($booking_query);
    $hotel_id = (int)$booking['hotel_id'];
    
    $is_completed = ($booking['booking_status'] === 'checked_out' || strtotime($booking['checkout_date']) < time());
    if (!$is_completed) {
        echo json_encode(['success' => false, 'message' => 'You can only review a hotel after completing your stay.']);
        exit;
    }
    
    $dup_query = mysqli_query($conn, "SELECT review_id FROM reviews WHERE booking_id = '" . mysqli_real_escape_string($conn, $booking_id) . "' LIMIT 1");
    if ($dup_query && mysqli_num_rows($dup_query) > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted a review for this booking.']);
        exit;
    }
    
    $res = bhSubmitReview([
        'hotel_id'   => $hotel_id,
        'booking_id' => $booking_id,
        'user_id'    => $user_id,
        'guest_name' => $_SESSION['user_name'] ?? 'Guest',
        'rating'     => $rating,
        'comment'    => $comment,
    ]);
    
    if ($res) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully! It is pending approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review. Database error.']);
    }
    exit;
}

$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;
$user_bookings = [];
if ($user_id > 0) {
    $sql = "SELECT b.*, h.hotel_images, h.location,
                   r.review_id, r.rating AS review_rating, r.comment AS review_comment, r.status AS review_status, r.manager_reply
            FROM bookings b
            LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
            LEFT JOIN reviews r ON b.booking_id = r.booking_id
            WHERE b.user_id = $user_id
            ORDER BY b.created_at DESC";
    $b_res = mysqli_query($conn, $sql);
    if ($b_res) {
        while ($b_row = mysqli_fetch_assoc($b_res)) {
            $user_bookings[] = $b_row;
        }
    }
}

// Compute counts dynamically
$total_count = count($user_bookings);
$upcoming_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($user_bookings as $b) {
    $is_completed = ($b['booking_status'] === 'checked_out' || strtotime($b['checkout_date']) < time());
    if ($b['booking_status'] === 'cancelled') {
        $cancelled_count++;
    } elseif ($is_completed) {
        $completed_count++;
    } else {
        $upcoming_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Bookings — bookHotel</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="my-bookings.css"/>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-800 fs-4" href="index.php">
      <i class="bi bi-building-fill text-warning me-1"></i>bookHotel
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="hotels.php">Hotels</a></li>
        <li class="nav-item"><a class="nav-link" href="destinations.php">Destinations</a></li>
        <li class="nav-item"><a class="nav-link active" href="my-bookings.php">My Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        <li class="nav-item ms-lg-3" id="navAuthSlot">
          <?php if ($is_logged_in): ?>
          <div class="dropdown">
            <a class="btn btn-warning btn-sm px-3 dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
              <span class="rounded-circle bg-white text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;font-size:0.7rem;font-weight:700;"><?= $userAvatar ?></span>
              <span><?= htmlspecialchars($userName) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
              <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
          <?php else: ?>
          <a class="btn btn-outline-warning btn-sm px-3" href="login.php">Login / Sign Up</a>
          <?php endif; ?>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ========== PAGE HERO ========== -->
<section class="mb-hero">
  <div class="mb-hero__overlay"></div>
  <div class="container position-relative" style="z-index:2">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">My Bookings</li>
      </ol>
    </nav>
    <div class="mb-hero__content">
      <div>
        <h1 class="mb-hero__title">My Bookings</h1>
        <p class="mb-hero__sub">Manage all your hotel reservations in one place</p>
      </div>
      <div class="mb-hero__user" id="heroUserWrap">
        <div class="mb-hero__avatar" id="heroAvatar"><?php echo htmlspecialchars($userAvatar); ?></div>
        <div>
          <div class="mb-hero__uname" id="heroName"><?php echo htmlspecialchars($userName); ?></div>
          <div class="mb-hero__uemail" id="heroEmail"><?php echo htmlspecialchars($userEmail); ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== SUMMARY CARDS ========== -->
<section class="mb-summary">
  <div class="container">
    <div class="mb-summary__grid">

      <div class="mb-stat-card mb-stat-card--total">
        <div class="mb-stat-card__icon"><i class="bi bi-calendar2-check-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num"><?php echo $total_count; ?></span>
          <span class="mb-stat-card__label">Total Bookings</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--upcoming">
        <div class="mb-stat-card__icon"><i class="bi bi-clock-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num"><?php echo $upcoming_count; ?></span>
          <span class="mb-stat-card__label">Upcoming</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--completed">
        <div class="mb-stat-card__icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num"><?php echo $completed_count; ?></span>
          <span class="mb-stat-card__label">Completed Stays</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--cancelled">
        <div class="mb-stat-card__icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num"><?php echo $cancelled_count; ?></span>
          <span class="mb-stat-card__label">Cancelled</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== SEARCH & FILTER ========== -->
<section class="mb-controls">
  <div class="container">
    <div class="mb-controls__inner">

      <div class="mb-search-wrap">
        <i class="bi bi-search mb-search-icon"></i>
        <input type="text" class="mb-search-input" id="bookingSearch"
          placeholder="Search by hotel name or Booking ID…" aria-label="Search bookings"/>
        <button class="mb-search-clear d-none" id="searchClear" aria-label="Clear search">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="mb-tabs" role="tablist" aria-label="Filter bookings">
        <button class="mb-tab mb-tab--active" data-filter="all" role="tab" aria-selected="true">
          <i class="bi bi-grid-fill"></i> All <span class="mb-tab-count"><?php echo $total_count; ?></span>
        </button>
        <button class="mb-tab" data-filter="upcoming" role="tab" aria-selected="false">
          <i class="bi bi-clock"></i> Upcoming <span class="mb-tab-count"><?php echo $upcoming_count; ?></span>
        </button>
        <button class="mb-tab" data-filter="completed" role="tab" aria-selected="false">
          <i class="bi bi-check-circle"></i> Completed <span class="mb-tab-count"><?php echo $completed_count; ?></span>
        </button>
        <button class="mb-tab" data-filter="cancelled" role="tab" aria-selected="false">
          <i class="bi bi-x-circle"></i> Cancelled <span class="mb-tab-count"><?php echo $cancelled_count; ?></span>
        </button>
      </div>

    </div>
  </div>
</section>

<!-- ========== BOOKING CARDS ========== -->
<section class="mb-list py-4">
  <div class="container">
    <div class="mb-cards" id="bookingCards">

<?php if (empty($user_bookings)): ?>
<?php /* No bookings — empty state shown immediately */ ?>
<?php else: ?>
<?php foreach ($user_bookings as $b):
    // ── Status key
    $s_key = 'upcoming';
    if ($b['booking_status'] === 'cancelled') {
        $s_key = 'cancelled';
    } elseif ($b['booking_status'] === 'checked_out' || strtotime($b['checkout_date']) < time()) {
        $s_key = 'completed';
    }

    // ── Hotel image
    $img = bhFirstImage($b['hotel_images'] ?? '', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=480&q=80');

    // ── Formatted dates
    $ci        = $b['checkin_date']  ? date('D, d M Y', strtotime($b['checkin_date']))  : '—';
    $co        = $b['checkout_date'] ? date('D, d M Y', strtotime($b['checkout_date'])) : '—';
    $booked_on = $b['created_at']    ? date('d M Y',    strtotime($b['created_at']))     : '—';

    // ── Nights
    $nights = (int)($b['nights'] ?? 0);
    if ($nights < 1 && $b['checkin_date'] && $b['checkout_date']) {
        $nights = max(1, (int)round((strtotime($b['checkout_date']) - strtotime($b['checkin_date'])) / 86400));
    }

    // ── Amount
    $amount = '₹' . number_format((float)($b['total_amount'] ?? 0), 0, '.', ',');

    // ── Payment badge
    $pay = strtolower($b['payment_status'] ?? 'paid');
    if ($s_key === 'cancelled') {
        $pbadge_cls = 'mb-payment-status__badge--refund';
        $pbadge_txt = 'Refund Initiated';
    } elseif ($pay === 'paid') {
        $pbadge_cls = 'mb-payment-status__badge--paid';
        $pbadge_txt = 'Paid ✓';
    } elseif (in_array($pay, ['refunded', 'refund', 'refund_pending'])) {
        $pbadge_cls = 'mb-payment-status__badge--refund';
        $pbadge_txt = 'Refund Processed';
    } else {
        $pbadge_cls = 'mb-payment-status__badge--failed';
        $pbadge_txt = 'Pending';
    }

    // ── Status badge
    $is_checked_in = (strtolower($b['booking_status'] ?? '') === 'checked_in');
    if ($is_checked_in) {
        $badge_cls = 'mb-badge--upcoming';
        $badge_txt = '<i class="bi bi-person-check-fill me-1"></i>Checked In';
    } elseif ($s_key === 'upcoming') {
        $badge_cls = 'mb-badge--upcoming';
        $badge_txt = '<i class="bi bi-check-circle-fill me-1"></i>Confirmed';
    } elseif ($s_key === 'completed') {
        $badge_cls = 'mb-badge--completed';
        $badge_txt = '<i class="bi bi-patch-check-fill me-1"></i>Completed';
    } else {
        $badge_cls = 'mb-badge--cancelled';
        $badge_txt = '<i class="bi bi-x-circle-fill me-1"></i>Cancelled';
    }

    // ── Guests string
    $num_guests = (int)($b['guests'] ?? 1);
    $guests_str = $num_guests . ' Adult' . ($num_guests > 1 ? 's' : '');

    // ── Review info
    $has_review    = !empty($b['review_id']);
    $review_text   = htmlspecialchars($b['review_comment'] ?? '');
    $review_rating = (float)($b['review_rating'] ?? 0);
    $review_status = $b['review_status'] ?? 'pending';
    $mgr_reply     = $b['manager_reply'] ?? '';

    // ── Identifiers
    $hotel_name = htmlspecialchars($b['hotel_name'] ?? 'Hotel');
    $hotel_id   = (int)($b['hotel_id'] ?? 0);
    $booking_id = htmlspecialchars($b['booking_id'] ?? '');
    $room_type  = htmlspecialchars($b['room_type'] ?? 'Standard Room');
    $location   = htmlspecialchars($b['location'] ?? $b['hotel_city'] ?? '');
    $hotel_city = htmlspecialchars($b['hotel_city'] ?? '');

    // ── Data for details modal (stored as JSON in data attribute)
    $modal_json = htmlspecialchars(json_encode([
        'hotel'      => $b['hotel_name'] ?? '',
        'location'   => $b['location'] ?? $b['hotel_city'] ?? '',
        'room'       => $b['room_type'] ?? '',
        'guests'     => $guests_str,
        'checkin'    => $ci,
        'checkout'   => $co,
        'price'      => $amount,
        'nights'     => $nights,
        'bookingId'  => $b['booking_id'] ?? '',
        'bookedOn'   => $booked_on,
        'payStatus'  => $pbadge_txt,
        'payClass'   => $pbadge_cls,
        'badgeText'  => strip_tags($badge_txt),
        'badgeClass' => $badge_cls,
        'img'        => $img,
        'status'     => $s_key,
        'guestName'  => $b['guest_name']  ?? '',
        'guestEmail' => $b['guest_email'] ?? '',
        'guestPhone' => $b['guest_phone'] ?? '',
        'baseAmount' => '₹' . number_format((float)($b['base_amount'] ?? 0), 0, '.', ','),
        'taxAmount'  => '₹' . number_format((float)($b['tax_amount'] ?? 0), 0, '.', ','),
        'couponDiscount' => (float)($b['coupon_discount'] ?? 0) > 0
            ? '₹' . number_format((float)$b['coupon_discount'], 0, '.', ',')
            : null,
        'payMethod'  => $b['payment_method'] ?? 'UPI',
    ]), ENT_QUOTES, 'UTF-8');
?>
      <article class="mb-card"
               data-status="<?= $s_key ?>"
               data-hotel="<?= $hotel_name ?>"
               data-id="<?= $booking_id ?>"
               data-bid="<?= $booking_id ?>"
               data-hotel-id="<?= $hotel_id ?>"
               data-modal='<?= $modal_json ?>'>

        <div class="mb-card__img-wrap">
          <img src="<?= htmlspecialchars($img) ?>"
               alt="<?= $hotel_name ?>" class="mb-card__img" loading="lazy"/>
          <span class="mb-badge <?= $badge_cls ?>"><?= $badge_txt ?></span>
          <?php if ($s_key === 'cancelled'): ?>
          <div class="mb-card__cancelled-overlay" aria-hidden="true"></div>
          <?php endif; ?>
        </div>

        <div class="mb-card__body<?= $s_key === 'cancelled' ? ' mb-card__body--cancelled' : '' ?>">
          <div class="mb-card__top">
            <div>
              <h3 class="mb-card__hotel"><?= $hotel_name ?></h3>
              <p class="mb-card__loc"><i class="bi bi-geo-alt-fill"></i><?= $location ?></p>
            </div>
            <div class="mb-card__id-wrap">
              <span class="mb-card__id-label">Booking ID</span>
              <span class="mb-card__id">#<?= $booking_id ?></span>
              <span class="mb-card__booked-on"><i class="bi bi-calendar-event"></i> Booked On: <?= $booked_on ?></span>
            </div>
          </div>

          <?php if ($s_key === 'upcoming'): ?>
          <!-- Progress Timeline -->
          <div class="mb-timeline">
            <div class="mb-timeline__step mb-timeline__step--done">
              <div class="mb-timeline__icon"><i class="bi bi-check-circle-fill"></i></div>
              <span class="mb-timeline__label">Booked</span>
            </div>
            <div class="mb-timeline__line mb-timeline__line--done"></div>
            <div class="mb-timeline__step mb-timeline__step--done">
              <div class="mb-timeline__icon"><i class="bi bi-check-circle-fill"></i></div>
              <span class="mb-timeline__label">Confirmed</span>
            </div>
            <div class="mb-timeline__line <?= $is_checked_in ? 'mb-timeline__line--done' : '' ?>"></div>
            <div class="mb-timeline__step <?= $is_checked_in ? 'mb-timeline__step--done' : 'mb-timeline__step--active' ?>">
              <div class="mb-timeline__icon"><i class="bi <?= $is_checked_in ? 'bi-check-circle-fill' : 'bi-clock-fill' ?>"></i></div>
              <span class="mb-timeline__label"><?= $is_checked_in ? 'Checked In' : 'Check-in Pending' ?></span>
            </div>
          </div>
          <?php endif; ?>

          <!-- Payment Status -->
          <div class="mb-payment-status">
            <i class="bi bi-credit-card-fill"></i>
            <span class="mb-payment-status__label">Payment Status:</span>
            <span class="mb-payment-status__badge <?= $pbadge_cls ?>"><?= $pbadge_txt ?></span>
          </div>

          <div class="mb-card__meta">
            <div class="mb-meta-item">
              <i class="bi bi-calendar-check-fill"></i>
              <div><span class="mb-meta-label">Check-in</span><span class="mb-meta-val"><?= $ci ?></span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-calendar-x-fill"></i>
              <div><span class="mb-meta-label">Check-out</span><span class="mb-meta-val"><?= $co ?></span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-people-fill"></i>
              <div><span class="mb-meta-label">Guests</span><span class="mb-meta-val"><?= $guests_str ?></span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-door-open-fill"></i>
              <div><span class="mb-meta-label">Room</span><span class="mb-meta-val"><?= $room_type ?></span></div>
            </div>
          </div>

          <?php if ($s_key === 'cancelled'): ?>
          <div class="mb-cancel-reason">
            <i class="bi bi-info-circle-fill"></i>
            Booking cancelled · <?= $pbadge_txt ?>
          </div>
          <?php endif; ?>

          <?php if ($s_key === 'completed'): ?>
          <!-- ── Rating & Review ── -->
          <?php if ($has_review): ?>
          <div class="mb-review-submitted">
            <div class="mb-review-submitted__stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi <?= $i <= $review_rating ? 'bi-star-fill' : 'bi-star' ?> text-warning"></i>
              <?php endfor; ?>
              <span class="mb-review-submitted__label ms-2">Your Review</span>
              <span class="mb-review-badge mb-review-badge--<?= $review_status === 'approved' ? 'approved' : ($review_status === 'hidden' ? 'hidden' : 'pending') ?>">
                <?= ucfirst($review_status) ?>
              </span>
            </div>
            <p class="mb-review-submitted__text"><?= $review_text ?></p>
            <?php if ($mgr_reply): ?>
            <div class="mb-manager-reply">
              <i class="bi bi-reply-fill me-1"></i><strong>Manager's Reply:</strong>
              <?= htmlspecialchars($mgr_reply) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="mb-rating-prompt">
            <span class="mb-rating-label"><i class="bi bi-star-fill text-warning me-1"></i>How was your stay?</span>
            <div class="mb-stars" id="stars_<?= $booking_id ?>" data-booking-id="<?= $booking_id ?>">
              <?php for ($v = 1; $v <= 5; $v++): ?>
              <button class="mb-star" data-val="<?= $v ?>" aria-label="<?= $v ?> star<?= $v > 1 ? 's' : '' ?>">
                <i class="bi bi-star"></i>
              </button>
              <?php endfor; ?>
            </div>
          </div>
          <div class="mb-review-form d-none" id="reviewForm_<?= $booking_id ?>">
            <textarea class="mb-review-textarea" rows="3"
                      placeholder="Share your experience… (min. 10 characters)"
                      maxlength="1000"></textarea>
            <div class="d-flex gap-2 mt-2 align-items-center flex-wrap">
              <button class="mb-btn mb-btn--primary mb-btn--sm mb-review-submit-btn"
                      data-booking-id="<?= $booking_id ?>">
                <i class="bi bi-send-fill"></i> Submit Review
              </button>
              <button class="mb-btn mb-btn--ghost mb-btn--sm mb-review-cancel-btn">Cancel</button>
              <span class="mb-review-char-count text-muted small ms-auto">0 / 1000</span>
            </div>
          </div>
          <?php endif; ?>
          <?php endif; ?>

          <div class="mb-card__footer">
            <div class="mb-card__price-wrap">
              <span class="mb-card__price-label">
                <?= $s_key === 'cancelled' ? 'Booking Amount' : ($s_key === 'completed' ? 'Total Paid' : 'Total Amount') ?>
              </span>
              <span class="mb-card__price<?= $s_key === 'cancelled' ? ' mb-card__price--struck' : '' ?>"><?= $amount ?></span>
              <span class="mb-card__nights">· <?= $nights ?> night<?= $nights !== 1 ? 's' : '' ?></span>
            </div>
            <div class="mb-card__actions">
              <button class="mb-btn mb-btn--ghost" onclick="openDetailsModal(this.closest('.mb-card'))">
                <i class="bi bi-eye-fill"></i> View Details
              </button>
              <a href="invoice.php?bid=<?= urlencode($booking_id) ?>" target="_blank" class="mb-btn mb-btn--outline">
                <i class="bi bi-download"></i> Invoice
              </a>
              <?php if ($s_key === 'upcoming'): ?>
              <button class="mb-btn mb-btn--danger mb-cancel-btn"
                      data-booking-id="<?= $booking_id ?>">
                <i class="bi bi-x-circle"></i> Cancel
              </button>
              <?php else: ?>
              <a href="hotels.php?city=<?= urlencode(strtolower($b['hotel_city'] ?? '')) ?>"
                 class="mb-btn mb-btn--primary">
                <i class="bi bi-arrow-repeat"></i> Book Again
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div><!-- /.mb-card__body -->
      </article>
<?php endforeach; ?>
<?php endif; ?>

    </div><!-- /#bookingCards -->

    <!-- ── EMPTY STATE ── -->
    <div class="mb-empty<?= empty($user_bookings) ? '' : ' d-none' ?>" id="emptyState">
      <div class="mb-empty__illus" aria-hidden="true">
        <div class="mb-empty__circle mb-empty__circle--1"></div>
        <div class="mb-empty__circle mb-empty__circle--2"></div>
        <div class="mb-empty__icon"><i class="bi bi-calendar2-x"></i></div>
      </div>
      <h3 class="mb-empty__title">No bookings found</h3>
      <p class="mb-empty__sub">We couldn't find any bookings matching your search.<br/>Try adjusting your filter or explore our hotels.</p>
      <a href="hotels.php" class="mb-btn mb-btn--primary mb-btn--lg">
        <i class="bi bi-search me-2"></i>Explore Hotels
      </a>
    </div>

  </div>
</section>

<!-- ========== BACK TO TOP ========== -->
<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top"
  onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <i class="bi bi-arrow-up"></i>
</button>

<!-- ========== VIEW DETAILS MODAL ========== -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content mb-details-modal">
      <div class="modal-header mb-details-modal__header">
        <div class="mb-details-modal__title-wrap">
          <div class="mb-details-modal__hotel-icon"><i class="bi bi-building-fill"></i></div>
          <div>
            <h5 class="modal-title mb-details-modal__title" id="detailsModalLabel">Booking Details</h5>
            <p class="mb-details-modal__subtitle" id="dmSubtitle">Hotel Name</p>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="mb-details-modal__img-wrap">
          <img id="dmHotelImg" src="" alt="Hotel" class="mb-details-modal__img"/>
          <div class="mb-details-modal__img-overlay"></div>
          <span class="mb-details-modal__status-badge" id="dmStatusBadge"></span>
        </div>
        <div class="mb-details-modal__body">
          <div class="mb-details-modal__grid">
            <div class="mb-dm-item">
              <div class="mb-dm-icon"><i class="bi bi-building-fill"></i></div>
              <div><span class="mb-dm-label">Hotel Name</span><span class="mb-dm-val" id="dmHotel">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon"><i class="bi bi-geo-alt-fill"></i></div>
              <div><span class="mb-dm-label">Full Address</span><span class="mb-dm-val" id="dmAddress">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon"><i class="bi bi-door-open-fill"></i></div>
              <div><span class="mb-dm-label">Room Type</span><span class="mb-dm-val" id="dmRoom">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon"><i class="bi bi-people-fill"></i></div>
              <div><span class="mb-dm-label">Guests</span><span class="mb-dm-val" id="dmGuests">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon mb-dm-icon--green"><i class="bi bi-calendar-check-fill"></i></div>
              <div><span class="mb-dm-label">Check-in Date</span><span class="mb-dm-val" id="dmCheckin">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon mb-dm-icon--red"><i class="bi bi-calendar-x-fill"></i></div>
              <div><span class="mb-dm-label">Check-out Date</span><span class="mb-dm-val" id="dmCheckout">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon mb-dm-icon--gold"><i class="bi bi-currency-rupee"></i></div>
              <div><span class="mb-dm-label">Total Amount</span><span class="mb-dm-val mb-dm-val--price" id="dmPrice">—</span></div>
            </div>
            <div class="mb-dm-item">
              <div class="mb-dm-icon"><i class="bi bi-hash"></i></div>
              <div><span class="mb-dm-label">Booking ID</span><span class="mb-dm-val mb-dm-val--mono" id="dmBookingId">—</span></div>
            </div>
          </div>
          <div class="mb-dm-payment">
            <i class="bi bi-credit-card-fill"></i>
            <span class="mb-dm-payment__label">Payment Status</span>
            <span class="mb-payment-status__badge" id="dmPayment">—</span>
          </div>
          <div class="mb-dm-bookedon">
            <i class="bi bi-calendar-event"></i>
            <span>Booked On: <strong id="dmBookedOn">—</strong></span>
          </div>
        </div>
      </div>
      <div class="modal-footer mb-details-modal__footer">
        <button type="button" class="mb-btn mb-btn--ghost" data-bs-dismiss="modal">Close</button>
        <button type="button" class="mb-btn mb-btn--outline" onclick="showToastMsg('Invoice downloading…','success')">
          <i class="bi bi-download me-1"></i>Download Invoice
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========== CANCEL CONFIRMATION MODAL ========== -->
<div class="mb-modal-backdrop" id="cancelModal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
  <div class="mb-modal-card">
    <div class="mb-modal-icon mb-modal-icon--warn">
      <i class="bi bi-exclamation-triangle-fill"></i>
    </div>
    <h3 class="mb-modal-title" id="cancelModalTitle">Cancel Booking?</h3>
    <p class="mb-modal-sub">Are you sure you want to cancel this booking? Cancellation charges may apply as per the hotel's policy.</p>
    <div class="mb-modal-actions">
      <button class="mb-btn mb-btn--ghost" id="cancelNo">Keep Booking</button>
      <button class="mb-btn mb-btn--danger" id="cancelYes">Yes, Cancel</button>
    </div>
  </div>
</div>

<!-- ========== TOAST ========== -->
<div class="mb-toast-wrap" id="mbToastWrap" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
  <?php if ($is_logged_in): ?>
    window.PHP_USER = {
      name: <?php echo json_encode($_SESSION['user_name'] ?? 'User'); ?>,
      email: <?php echo json_encode($_SESSION['user_email'] ?? ''); ?>
    };
  <?php else: ?>
    window.PHP_LOGGED_OUT = true;
  <?php endif; ?>
</script>
<script src="navbar.js"></script>
<script src="my-bookings.js"></script>
</body>
</html>
