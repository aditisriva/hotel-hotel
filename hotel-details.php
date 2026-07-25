<?php
session_start();
require_once 'db.php';
require_once 'hotel_functions.php';
require_once 'pricing.php';

$is_logged_in   = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? htmlspecialchars($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'User') : '';
$user_initial   = $is_logged_in ? strtoupper(substr($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)) : '';

// ── Handle review submission ──────────────────────────────────────────
$review_msg = '';
$review_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    if (!$is_logged_in) {
        $review_msg = 'Please log in to submit a review.';
        $review_type = 'danger';
    } else {
        $rating  = (float)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $booking_id = trim($_POST['booking_id'] ?? '');
        if ($rating < 1 || $rating > 5 || empty($comment)) {
            $review_msg = 'Please provide a rating (1–5) and a review.';
            $review_type = 'danger';
        } else {
            $result = bhSubmitReview([
                'hotel_id'   => $hotel_id,
                'booking_id' => $booking_id ?: null,
                'user_id'    => $_SESSION['user_id'],
                'guest_name' => $_SESSION['user_name'] ?? 'User',
                'rating'     => $rating,
                'comment'    => $comment,
            ]);
            if ($result) {
                $review_msg = 'Thank you! Your review has been submitted and is awaiting approval.';
                $review_type = 'success';
            } else {
                $review_msg = 'Failed to submit review. Please try again.';
                $review_type = 'danger';
            }
        }
    }
}

// ── Fetch hotel from DB ──────────────────────────────────────────────────
$hotel_id_req = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hotel = $hotel_id_req ? bhGetHotelById($hotel_id_req) : null;

// Fallback: try to get first hotel for demo if no id given
if (!$hotel) {
    $all = bhGetHotels();
    $hotel = $all ? $all[0] : null;
}

if (!$hotel) {
    die('<div style="text-align:center;padding:4rem;font-family:sans-serif"><h2>Hotel not found</h2><a href="hotels.php">← Browse hotels</a></div>');
}

// ── Extract hotel data ───────────────────────────────────────────────────
$hotel_id   = (int)$hotel['hotel_id'];
$hotel_name = $hotel['hotel_name'];
$hotel_city = ucfirst($hotel['city']);
$hotel_loc  = $hotel['location'];
$hotel_state= $hotel['state'] ?? '';
$hotel_desc = $hotel['description'] ?? '';
$hotel_price= (float)$hotel['price_per_night'];
$hotel_orig = (float)($hotel['original_price'] ?? 0);
$hotel_rating=(float)$hotel['rating'];
$hotel_stars= (int)($hotel['star_rating'] ?? 3);
$hotel_type = $hotel['property_type'];
$hotel_cap  = (int)$hotel['capacity'];
$hotel_amenities = $hotel['amenities'] ?? '';
$hotel_images_arr = bhAllImages($hotel['hotel_images'] ?? '');
$hotel_img  = $hotel_images_arr ? $hotel_images_arr[0] : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80';
$checkin_time  = $hotel['checkin_time'] ?? '14:00';
$checkout_time = $hotel['checkout_time'] ?? '11:00';

// ── Booking params from URL ───────────────────────────────────────────────
$city_hd      = isset($_GET['city'])     ? trim($_GET['city'])     : strtolower($hotel['city']);
$city_hd_lbl  = $hotel_city;
$checkin_raw  = isset($_GET['checkin'])  ? trim($_GET['checkin'])  : '';
$checkout_raw = isset($_GET['checkout']) ? trim($_GET['checkout']) : '';
$guests_raw   = isset($_GET['guests'])   ? (int)$_GET['guests']   : 2;
$nights       = 1;
if ($checkin_raw && $checkout_raw) {
    $diff   = (strtotime($checkout_raw) - strtotime($checkin_raw)) / 86400;
    $nights = max(1, (int)$diff);
}
function hd_fmt(string $d): string { return $d ? date('d M Y', strtotime($d)) : ''; }
$checkin_fmt  = hd_fmt($checkin_raw);
$checkout_fmt = hd_fmt($checkout_raw);
$guests_label = $guests_raw >= 6 ? '3 Rooms, 6 Guests' : ($guests_raw >= 4 ? '2 Rooms, 4 Guests' : ($guests_raw == 1 ? '1 Room, 1 Guest' : '1 Room, 2 Guests'));
$qs_parts = [];
if ($city_hd)      $qs_parts_city[] = 'city='     . urlencode(strtolower($city_hd));
if ($checkin_raw)  $qs_parts[] = 'checkin='  . urlencode($checkin_raw);
if ($checkout_raw) $qs_parts[] = 'checkout=' . urlencode($checkout_raw);
if ($guests_raw)   $qs_parts[] = 'guests='   . $guests_raw;
$booking_qs = $qs_parts ? '&' . implode('&', $qs_parts) : '';
$full_qs    = array_merge($qs_parts_city ?? [], $qs_parts);
$full_qs_str = $full_qs ? '?' . implode('&', $full_qs) : '';
$id_qs = 'id=' . $hotel_id . ($full_qs_str ? str_replace('?','&',$full_qs_str) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <title><?php echo htmlspecialchars($hotel_name); ?> – bookHotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="hotels.css"/>
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
        <li class="nav-item"><a class="nav-link active" href="hotels.php">Hotels</a></li>
        <li class="nav-item"><a class="nav-link" href="destinations.php">Destinations</a></li>
        <li class="nav-item"><a class="nav-link" href="my-bookings.php">My Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
         <li class="nav-item ms-lg-3">
           <?php if ($is_logged_in): ?>
           <div class="dropdown">
             <a class="btn btn-warning btn-sm px-3 dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
               <span class="rounded-circle bg-white text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;font-size:0.7rem;font-weight:700;"><?= $user_initial ?></span>
               <span><?= $user_firstname ?></span>
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

<!-- ========== COMPACT SEARCH SUMMARY BAR ========== -->
<div class="hd-search-bar" id="hdSearchBar">
  <div class="container">
    <div class="hd-summary-row" id="hdSummaryRow">

      <!-- Destination segment -->
      <div class="hd-seg" onclick="hdOpenModal('city')">
        <i class="bi bi-geo-alt-fill hd-seg-icon"></i>
        <div class="hd-seg-body">
          <div class="hd-seg-val" id="hdDispCity"><?php echo htmlspecialchars($city_hd_lbl ? $city_hd_lbl . ', India' : 'Select Destination'); ?></div>
        </div>
        <i class="bi bi-chevron-down hd-seg-chevron"></i>
      </div>

      <div class="hd-seg-div"></div>

      <!-- Check-in segment -->
      <div class="hd-seg" onclick="hdOpenModal('checkin')">
        <i class="bi bi-calendar-check hd-seg-icon"></i>
        <div class="hd-seg-body">
          <div class="hd-seg-tiny">Check-in</div>
          <div class="hd-seg-val" id="hdDispCheckin"><?php echo $checkin_fmt ?: 'Select Date'; ?></div>
        </div>
        <i class="bi bi-chevron-down hd-seg-chevron"></i>
      </div>

      <div class="hd-seg-div"></div>

      <!-- Check-out segment -->
      <div class="hd-seg" onclick="hdOpenModal('checkout')">
        <i class="bi bi-calendar-x hd-seg-icon"></i>
        <div class="hd-seg-body">
          <div class="hd-seg-tiny">Check-out<?php if ($nights > 1): ?> <span class="hd-nights-badge"><?php echo $nights; ?> Nights</span><?php endif; ?></div>
          <div class="hd-seg-val" id="hdDispCheckout"><?php echo $checkout_fmt ?: 'Select Date'; ?></div>
        </div>
        <i class="bi bi-chevron-down hd-seg-chevron"></i>
      </div>

      <div class="hd-seg-div"></div>

      <!-- Guests segment -->
      <div class="hd-seg" onclick="hdOpenModal('guests')">
        <i class="bi bi-people-fill hd-seg-icon"></i>
        <div class="hd-seg-body">
          <div class="hd-seg-tiny">Rooms & Guests</div>
          <div class="hd-seg-val" id="hdDispGuests"><?php echo htmlspecialchars($guests_label ?: '1 Room, 2 Guests'); ?></div>
        </div>
        <i class="bi bi-chevron-down hd-seg-chevron"></i>
      </div>

      <!-- Search Button -->
      <button class="hd-search-btn" onclick="hdDoSearch()">
        <i class="bi bi-search"></i><span class="hd-btn-txt"> Search</span>
      </button>

    </div>
  </div>
</div>

<!-- Edit Search Modal -->
<div class="modal fade" id="hdEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18)">
      <div class="modal-header" style="background:linear-gradient(135deg,#0f172a,#1e3a8a);border-bottom:none;border-radius:16px 16px 0 0;padding:1.1rem 1.5rem">
        <h6 class="modal-title fw-700 text-white"><i class="bi bi-search me-2"></i>Modify Search</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label small fw-700 text-muted text-uppercase" style="letter-spacing:.06em">Destination</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
              <input type="text" class="form-control border-start-0" id="hdModalCity" placeholder="City or hotel name" style="font-weight:600"/>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-700 text-muted text-uppercase" style="letter-spacing:.06em">Check-In</label>
            <input type="date" class="form-control fw-600" id="hdModalCheckin"/>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-700 text-muted text-uppercase" style="letter-spacing:.06em">Check-Out</label>
            <input type="date" class="form-control fw-600" id="hdModalCheckout"/>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small fw-700 text-muted text-uppercase" style="letter-spacing:.06em">Rooms & Guests</label>
            <select class="form-select fw-600" id="hdModalGuests">
              <option value="1">1 Room, 1 Guest</option>
              <option value="2">1 Room, 2 Guests</option>
              <option value="3">1 Room, 3 Guests</option>
              <option value="4">2 Rooms, 4 Guests</option>
              <option value="6">3 Rooms, 6 Guests</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #e2e8f0;border-radius:0 0 16px 16px">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary fw-700 px-4" onclick="hdApplySearch()"><i class="bi bi-search me-2"></i>Search Hotels</button>
      </div>
    </div>
  </div>
</div>


<!-- ========== BREADCRUMB ========== -->
<div class="bg-white border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-danger text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="hotels.php" class="text-danger text-decoration-none">Hotels</a></li>
        <li class="breadcrumb-item active text-muted"><?php echo htmlspecialchars($hotel_name); ?>, <?php echo htmlspecialchars($hotel_city); ?></li>
      </ol>
    </nav>
  </div>
</div>

<!-- ========== IMAGE GALLERY ========== -->
<section class="gallery-section bg-dark">
  <div class="container-fluid px-0">
    <div class="gallery-grid">
      <div class="gallery-main">
        <img src="<?php echo htmlspecialchars($hotel_img); ?>" alt="<?php echo htmlspecialchars($hotel_name); ?>"/>
        <button class="btn btn-light btn-sm gallery-all-btn" data-bs-toggle="modal" data-bs-target="#galleryModal">
          <i class="bi bi-images me-1"></i>View All Photos
        </button>
      </div>
      <div class="gallery-thumbs">
        <?php
        $thumbs = count($hotel_images_arr) > 1 ? array_slice($hotel_images_arr, 1, 4) : [];
        $fallbacks = [
            'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&q=80',
            'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=400&q=80',
            'https://images.unsplash.com/photo-1540541338537-1220059ddcdd?w=400&q=80',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400&q=80',
        ];
        for ($ti = 0; $ti < 4; $ti++) {
            $tsrc = $thumbs[$ti] ?? $fallbacks[$ti];
            echo '<img src="' . htmlspecialchars($tsrc) . '" alt="' . htmlspecialchars($hotel_name) . '" onerror="this.src=\'' . $fallbacks[$ti % 4] . '\'"/>';
        }
        ?>
      </div>
    </div>
  </div>
</section>

<!-- ========== MAIN CONTENT ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">

      <!-- LEFT: Hotel Info -->
      <div class="col-12 col-lg-8">

        <!-- Hotel Header -->
        <div class="detail-card mb-4">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
              <div class="d-flex gap-2 align-items-center mb-2">
                <span class="text-warning">★★★★★</span>
                <span class="badge bg-warning text-dark">Luxury</span>
                <span class="badge bg-success">Free Cancellation</span>
              </div>
              <h1 class="fw-800 fs-3 mb-1"><?php echo htmlspecialchars($hotel_name); ?></h1>
              <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo htmlspecialchars($hotel_loc . ($hotel_state ? ', ' . $hotel_state : '')); ?>
                <a href="#" class="text-primary ms-2 small">View on Map</a>
              </p>
            </div>
            <div class="text-end">
              <div class="d-flex align-items-center gap-2 justify-content-end mb-1">
                <div class="rating-big"><?php echo number_format($hotel_rating, 1); ?></div>
              </div>
              <button class="btn-wishlist-lg"><i class="bi bi-heart me-1"></i>Save</button>
            </div>
          </div>
          <!-- Quick amenity icons -->
          <div class="d-flex flex-wrap gap-3">
            <?php
            $amenList = array_filter(array_map('trim', explode(',', $hotel_amenities)));
            foreach ($amenList as $am):
              $ic = bhAmenityIcon($am);
              $lbl = ucfirst($am);
            ?>
            <span class="quick-amenity"><i class="bi <?php echo $ic; ?>"></i> <?php echo htmlspecialchars($lbl); ?></span>
            <?php endforeach; ?>
          </div>

          <!-- Availability Summary Strip -->
          <?php if ($checkin_raw || $guests_raw): ?>
          <div class="mt-3 p-3 rounded-3 d-flex flex-wrap gap-3 align-items-center" style="background:linear-gradient(135deg,#e8f0fe,#dbeafe);border:1.5px solid #bfdbfe">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-calendar-check-fill text-primary"></i>
              <div>
                <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Check-in</div>
                <div style="font-weight:700;color:#1a1a2e;font-size:.875rem"><?php echo $checkin_raw ? hd_fmt($checkin_raw) : 'Select date'; ?></div>
              </div>
            </div>
            <i class="bi bi-arrow-right text-muted"></i>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-calendar-x-fill text-danger"></i>
              <div>
                <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em">Check-out</div>
                <div style="font-weight:700;color:#1a1a2e;font-size:.875rem"><?php echo $checkout_raw ? hd_fmt($checkout_raw) : 'Select date'; ?></div>
              </div>
            </div>
            <?php if ($nights > 1): ?>
            <span class="badge bg-primary" style="font-size:.78rem"><?php echo $nights; ?> Nights</span>
            <?php endif; ?>
            <?php if ($guests_raw > 0): ?>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-people-fill text-success"></i>
              <div style="font-weight:700;color:#1a1a2e;font-size:.875rem"><?php echo htmlspecialchars($guests_raw); ?> Guests</div>
            </div>
            <?php endif; ?>
            <a href="hotels.php?<?php echo $city_param ? 'city='.urlencode($city_param).'&' : ''; ?>checkin=<?php echo urlencode($checkin_raw); ?>&checkout=<?php echo urlencode($checkout_raw); ?>&guests=<?php echo $guests_raw; ?>" class="ms-auto btn btn-outline-primary btn-sm">
              <i class="bi bi-pencil-fill me-1"></i>Change Dates
            </a>
          </div>
          <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="detail-card mb-4">
          <h5 class="fw-700 mb-3"><i class="bi bi-info-circle text-primary me-2"></i>About This Property</h5>
          <p class="text-muted"><?php echo nl2br(htmlspecialchars($hotel_desc ?: 'A premium property offering world-class amenities and an unforgettable stay experience.')); ?></p>
        </div>

        <!-- Amenities -->
        <div class="detail-card mb-4">
          <h5 class="fw-700 mb-4"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Amenities</h5>
          <div class="row g-3">
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-wifi"></i><span>Free High-Speed WiFi</span></div>
              <div class="amenity-item"><i class="bi bi-droplet-fill"></i><span>Outdoor Pool</span></div>
              <div class="amenity-item"><i class="bi bi-flower1"></i><span>Luxury Spa</span></div>
            </div>
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-cup-hot"></i><span>Breakfast Included</span></div>
              <div class="amenity-item"><i class="bi bi-car-front"></i><span>Free Valet Parking</span></div>
            </div>
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-wind"></i><span>Air Conditioning</span></div>
              <div class="amenity-item"><i class="bi bi-reception-4"></i><span>24/7 Concierge</span></div>
            </div>
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-camera"></i><span>Heritage Tours</span></div>
              <div class="amenity-item"><i class="bi bi-music-note-beamed"></i><span>Cultural Performances</span></div>
              <div class="amenity-item"><i class="bi bi-luggage"></i><span>Luggage Storage</span></div>
            </div>
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-shield-check"></i><span>24/7 Security</span></div>
              <div class="amenity-item"><i class="bi bi-telephone"></i><span>Room Service</span></div>
              <div class="amenity-item"><i class="bi bi-person-check"></i><span>Butler Service</span></div>
            </div>
            <div class="col-6 col-md-4">
              <div class="amenity-item"><i class="bi bi-sun"></i><span>Rooftop Terrace</span></div>
              <div class="amenity-item"><i class="bi bi-people"></i><span>Event Spaces</span></div>
              <div class="amenity-item"><i class="bi bi-arrow-repeat"></i><span>Free Cancellation</span></div>
            </div>
          </div>
        </div>

        <!-- Room Types (Dynamic from DB) -->
        <div class="detail-card mb-4">
          <h5 class="fw-700 mb-4"><i class="bi bi-door-open text-primary me-2"></i>Available Rooms</h5>
          <?php
            // Fetch only Available rooms for this hotel from DB
            $db_rooms = bhGetRoomsByHotel($hotel_id, true);
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
          ?>
          <?php if (empty($db_rooms)): ?>
            <div class="text-center py-5">
              <i class="bi bi-door-closed fs-1 text-muted opacity-50 d-block mb-3"></i>
              <p class="text-muted fw-500">No rooms are currently available for this hotel.</p>
              <p class="text-muted small">Please check back later or contact the hotel directly.</p>
            </div>
          <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($db_rooms as $r):
                // Images
                $r_imgs = !empty($r['room_images']) ? json_decode($r['room_images'], true) : [];
                $r_img  = (!empty($r_imgs) && is_array($r_imgs))
                    ? (str_starts_with($r_imgs[0], 'http') ? $r_imgs[0] : $base_url . '/Prrrooooojjjeeecccttt/hotel/' . ltrim($r_imgs[0], '/'))
                    : 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&q=80';

                // Price: prefer final_price, fallback to base_price, fallback to hotel price
                $r_base  = (float)($r['base_price']  ?? 0);
                $r_final = (float)($r['final_price']  ?? 0);
                $r_price = ($r_final > 0) ? $r_final : (($r_base > 0) ? $r_base : $hotel_price);
                $r_orig  = ($r_base  > 0) ? $r_base  : $hotel_orig;
                $r_disc  = ($r_base > 0 && $r_final > 0 && $r_final < $r_base) ? round((1 - $r_final / $r_base) * 100) : 0;

                // Name & capacity
                $r_type   = htmlspecialchars($r['room_type'] ?? 'Room');
                $r_num    = htmlspecialchars($r['room_number'] ?? '');
                $r_floor  = htmlspecialchars($r['floor'] ?? '1st');
                $r_bed    = htmlspecialchars($r['bed_type'] ?? 'Double Bed');
                $adults   = (int)($r['adult_capacity'] ?? 2);
                $children = (int)($r['child_capacity'] ?? 0);
                $cap_str  = $adults . ' Adult' . ($adults !== 1 ? 's' : '');
                if ($children > 0) $cap_str .= ', ' . $children . ' Child' . ($children !== 1 ? 'ren' : '');

                // Amenities tags (comma-separated string)
                $amenities_arr = $r['amenities'] ? array_map('trim', explode(',', $r['amenities'])) : [];

                // Status badge
                $r_status = $r['status'] ?? 'Available';

                // Booking URL
                $book_url = 'review-booking.php?room=' . urlencode($r['room_type'] ?? 'standard')
                          . '&room_id=' . (int)$r['room_id']
                          . '&id=' . $hotel_id . $booking_qs;
            ?>
            <div class="room-card">
              <div class="row g-0">
                <div class="col-4 col-md-3">
                  <img src="<?= htmlspecialchars($r_img) ?>" class="room-img" alt="<?= $r_type ?>"/>
                </div>
                <div class="col-8 col-md-9">
                  <div class="p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                      <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="fw-700 mb-0"><?= $r_type ?> <?= $r_num ? '· Room '.$r_num : '' ?></h6>
                        <span class="badge <?= strtolower($r_status) === 'available' ? 'bg-success' : 'bg-secondary' ?> ms-2 small"><?= htmlspecialchars($r_status) ?></span>
                      </div>
                      <p class="text-muted small mb-2">
                        Floor: <?= $r_floor ?> · <?= $r_bed ?> · <?= $cap_str ?>
                      </p>
                      <?php if (!empty($amenities_arr)): ?>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach (array_slice($amenities_arr, 0, 5) as $am): ?>
                          <span class="amenity-tag"><i class="bi <?= htmlspecialchars(bhAmenityIcon($am)) ?>"></i> <?= htmlspecialchars(ucfirst($am)) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($amenities_arr) > 5): ?>
                          <span class="amenity-tag text-muted">+<?= count($amenities_arr) - 5 ?> more</span>
                        <?php endif; ?>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                      <div>
                        <?php bhPriceBlock($r_price, $r_orig); ?>
                      </div>
                      <a href="<?= $book_url ?>" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-calendar-check me-1"></i>Book Now
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>


         <!-- Guest Reviews -->
        <div class="detail-card mb-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-700 mb-0"><i class="bi bi-chat-quote text-primary me-2"></i>Guest Reviews</h5>
            <div class="d-flex align-items-center gap-2">
              <div class="rating-big"><?php echo number_format($hotel_rating, 1); ?></div>
              <div>
                <div class="fw-700 small">Based on <?php echo bhGetReviewCount($hotel_id); ?> reviews</div>
                <div class="text-muted small"><?php echo bhGetAverageRating($hotel_id); ?> average</div>
              </div>
            </div>
          </div>
          <?php if ($review_msg): ?>
          <div class="alert alert-<?php echo $review_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($review_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          <?php if ($is_logged_in): ?>
          <div class="detail-card mb-4" style="border:1px solid var(--bs-border-color)">
            <h6 class="fw-700 mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Write a Review</h6>
            <form method="POST" id="reviewForm">
              <input type="hidden" name="review_action" value="submit"/>
              <div class="row g-2 mb-2">
                <div class="col-12">
                  <label class="form-label small fw-600">Your Rating</label>
                  <select name="rating" class="form-select form-select-sm" required>
                    <option value="">Select rating...</option>
                    <option value="5">★★★★★ Excellent</option>
                    <option value="4">★★★★☆ Very Good</option>
                    <option value="3">★★★☆☆ Average</option>
                    <option value="2">★★☆☆☆ Poor</option>
                    <option value="1">★☆☆☆☆ Terrible</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-600">Your Review</label>
                  <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Share your experience..." required></textarea>
                </div>
              </div>
              <button type="submit" class="btn btn-warning btn-sm">Submit Review</button>
            </form>
          </div>
          <?php endif; ?>
          <?php
          $approved_reviews = bhGetReviewsByHotel($hotel_id, 0, 10);
          if (empty($approved_reviews)): ?>
          <div class="text-center py-4 text-muted"><i class="bi bi-chat-square" style="font-size:2rem;opacity:.3"></i><div class="mt-2 small">No reviews yet. Be the first to review!</div></div>
          <?php else: ?>
          <div class="row g-3">
            <?php foreach ($approved_reviews as $rev): ?>
            <div class="col-12 col-md-6">
              <div class="review-card p-3 h-100">
                <div class="d-flex gap-1 text-warning mb-2 small">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi <?php echo $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                  <?php endfor; ?>
                </div>
                <p class="text-muted small mb-3">"<?php echo htmlspecialchars($rev['comment']); ?>"</p>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.7rem;font-weight:700;"><?php echo strtoupper(substr($rev['first_name'] ?? 'U', 0, 1)); ?></div>
                  <div>
                    <div class="fw-700 small"><?php echo htmlspecialchars($rev['first_name'] ?? 'Guest'); ?> <?php echo htmlspecialchars($rev['last_name'] ?? ''); ?></div>
                    <div class="text-muted" style="font-size:.72rem"><?php echo date('M Y', strtotime($rev['created_at'])); ?></div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div><!-- end left col -->

      <!-- RIGHT: Sticky Booking Sidebar -->
      <div class="col-12 col-lg-4">
        <div class="booking-card sticky-booking">
          <div class="booking-card-header text-white p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-700 mb-0"><i class="bi bi-calendar2-check me-2"></i>Book This Hotel</h6>
              <span class="badge bg-success small">Free Cancellation</span>
            </div>
            <?php bhPriceBlock($hotel_price, $hotel_orig, false); ?>
          </div>
          <div class="booking-card-body">
            <!-- Dates -->
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-600 text-muted mb-1">CHECK-IN</label>
                <input type="date" class="form-control form-control-sm" id="bkCheckin" value="<?php echo htmlspecialchars($checkin_raw); ?>"/>
              </div>
              <div class="col-6">
                <label class="form-label small fw-600 text-muted mb-1">CHECK-OUT</label>
                <input type="date" class="form-control form-control-sm" id="bkCheckout" value="<?php echo htmlspecialchars($checkout_raw); ?>"/>
              </div>
            </div>
            <!-- Guests -->
            <div class="mb-3">
              <label class="form-label small fw-600 text-muted mb-1">GUESTS</label>
              <select class="form-select form-select-sm" id="bkGuests">
                <option value="1" <?php echo $guests_raw==1?'selected':''; ?>>1 Guest</option>
                <option value="2" <?php echo (!$guests_raw||$guests_raw==2)?'selected':''; ?>>2 Guests</option>
                <option value="3" <?php echo $guests_raw==3?'selected':''; ?>>3 Guests</option>
                <option value="4" <?php echo $guests_raw>=4?'selected':''; ?>>4 Guests</option>
              </select>
            </div>
            <!-- Price Breakdown -->
            <div class="price-breakdown-box mb-3" id="bkBreakdown">
              <?php
                $p_calc = bhCalcPricing($hotel_price);
                $room_cost = $hotel_price * $nights;
                $tax_amt   = round($room_cost * bhTaxRate($hotel_price));
                $disc      = round($room_cost * 0.35);
                $grand     = $room_cost - $disc + $tax_amt + SERVICE_CHARGE;
              ?>
              <div class="row-item"><span class="label">₹<?php echo number_format($hotel_price); ?> × <?php echo $nights; ?> night<?php echo $nights>1?'s':''; ?></span><span class="value">₹<?php echo number_format($room_cost); ?></span></div>
              <div class="row-item"><span class="label" style="color:#059669">Discount</span><span class="value" style="color:#059669">− ₹<?php echo number_format($disc); ?></span></div>
              <div class="row-item"><span class="label">GST (12%)</span><span class="value">₹<?php echo number_format($tax_amt); ?></span></div>
              <div class="row-item"><span class="label">Service Charge</span><span class="value">₹200</span></div>
              <div class="row-item total"><span>Total Payable</span><span class="value">₹<?php echo number_format($grand); ?></span></div>
              <div class="savings-row"><i class="bi bi-piggy-bank-fill"></i>You save ₹<?php echo number_format($disc); ?> on this booking!</div>
            </div>
            <!-- CTA -->
            <a href="review-booking.php?room=deluxe&id=<?php echo $hotel_id; ?><?php echo $booking_qs; ?>" class="btn book-now-btn w-100 fw-700 py-3 mb-2">
              <i class="bi bi-calendar2-check me-2"></i>Book Now
            </a>
            <p class="text-muted text-center" style="font-size:.75rem"><i class="bi bi-shield-check text-success me-1"></i>Secure booking · No hidden charges</p>
          </div>
        </div>
      </div><!-- end right col -->

    </div><!-- end row -->
  </div><!-- end container -->
</section>

<!-- ========== SIMILAR HOTELS ========== -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-800 mb-1">Similar Hotels in <?php echo htmlspecialchars($hotel_city); ?></h2>
        <p class="text-muted mb-0">You might also like these properties</p>
      </div>
      <a href="hotels.php" class="btn btn-outline-primary btn-sm">View All</a>
    </div>
    <div class="row g-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?w=400&q=80" class="card-img-top hotel-img" alt="Similar Hotel"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Rambagh Palace</h6>
              <span class="rating-badge">4.8 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Jaipur, Rajasthan</p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div><?php bhPriceBlock(8200); ?></div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80" class="card-img-top hotel-img" alt="Similar Hotel"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Jai Mahal Palace</h6>
              <span class="rating-badge">4.7 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Jaipur, Rajasthan</p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div><?php bhPriceBlock(6500); ?></div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=400&q=80" class="card-img-top hotel-img" alt="Similar Hotel"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Samode Haveli</h6>
              <span class="rating-badge">4.6 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Jaipur, Rajasthan</p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div><?php bhPriceBlock(5800); ?></div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=400&q=80" class="card-img-top hotel-img" alt="Similar Hotel"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Trident Jaipur</h6>
              <span class="rating-badge">4.5 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Jaipur, Rajasthan</p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div><?php bhPriceBlock(4200); ?></div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="bg-dark text-white pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <h5 class="fw-800 mb-3"><i class="bi bi-building-fill text-warning me-1"></i>bookHotel</h5>
        <p class="text-white-50 small">Your trusted travel partner since 2015. We make hotel booking simple, affordable, and enjoyable for millions of travellers.</p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-facebook fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-twitter-x fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-instagram fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-youtube fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-linkedin fs-5"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Company</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">About Us</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Press</a></li>
          <li><a href="#">Blog</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="#">Cancellation Policy</a></li>
          <li><a href="#">Safety Info</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="hotels.php">Hotels</a></li>
          <li><a href="destinations.php">Destinations</a></li>
          <li><a href="my-bookings.php">My Bookings</a></li>
          <li><a href="#">Car Rentals</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Legal</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="privacy-policy.php">Privacy Policy</a></li>
          <li><a href="terms-of-service.php">Terms of Use</a></li>
          <li><a href="cookie-policy.php">Cookie Policy</a></li>
          <li><a href="#">Sitemap</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary"/>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <p class="text-white-50 small mb-0">© 2026 bookHotel Technologies Pvt. Ltd. All rights reserved.</p>
      <div class="d-flex gap-2">
        <img src="https://img.shields.io/badge/Visa-1A1F71?style=flat&logo=visa&logoColor=white" height="20" alt="Visa"/>
        <img src="https://img.shields.io/badge/Mastercard-EB001B?style=flat&logo=mastercard&logoColor=white" height="20" alt="Mastercard"/>
        <img src="https://img.shields.io/badge/UPI-1a73e8?style=flat&logo=google-pay&logoColor=white" height="20" alt="UPI"/>
        <img src="https://img.shields.io/badge/PayPal-003087?style=flat&logo=paypal&logoColor=white" height="20" alt="PayPal"/>
      </div>
    </div>
  </div>
</footer>

<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <i class="bi bi-arrow-up"></i>
</button>

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
<script>
// State — persisted from URL
var _hdCity    = "<?php echo addslashes($city_hd_lbl ? $city_hd_lbl . ', India' : ''); ?>";
var _hdCheckin = "<?php echo addslashes($checkin_raw); ?>";
var _hdCheckout= "<?php echo addslashes($checkout_raw); ?>";
var _hdGuests  = "<?php echo $guests_raw ?: 2; ?>";

// Navbar scroll
window.addEventListener("scroll", function() {
  document.getElementById("mainNav").classList.toggle("scrolled", window.scrollY > 50);
});

// Wishlist toggle
document.querySelectorAll(".btn-wishlist").forEach(function(btn) {
  btn.addEventListener("click", function() {
    var icon = btn.querySelector("i");
    icon.classList.toggle("bi-heart");
    icon.classList.toggle("bi-heart-fill");
    icon.classList.toggle("text-danger");
  });
});

// Open modal and pre-fill values
function hdOpenModal(focus) {
  var mc = document.getElementById("hdModalCity");
  var mi = document.getElementById("hdModalCheckin");
  var mo = document.getElementById("hdModalCheckout");
  var mg = document.getElementById("hdModalGuests");
  if (mc) mc.value = _hdCity.replace(', India', '').trim();
  if (mi) mi.value = _hdCheckin;
  if (mo) mo.value = _hdCheckout;
  if (mg) {
    for (var k = 0; k < mg.options.length; k++) {
      if (mg.options[k].value == _hdGuests) { mg.selectedIndex = k; break; }
    }
  }
  var modal = new bootstrap.Modal(document.getElementById("hdEditModal"));
  modal.show();
  setTimeout(function() {
    if (focus === "checkin" && mi) mi.focus();
    else if (focus === "checkout" && mo) mo.focus();
    else if (focus === "city" && mc) mc.focus();
  }, 300);
}

// Apply search from modal
function hdApplySearch() {
  var city   = (document.getElementById("hdModalCity")?.value   || "").trim().toLowerCase().replace(/,.*$/, "").trim();
  var ci     = document.getElementById("hdModalCheckin")?.value  || "";
  var co     = document.getElementById("hdModalCheckout")?.value || "";
  var guests = document.getElementById("hdModalGuests")?.value   || "2";
  var qs = [];
  if (city)   qs.push("city="     + encodeURIComponent(city));
  if (ci)     qs.push("checkin="  + encodeURIComponent(ci));
  if (co)     qs.push("checkout=" + encodeURIComponent(co));
  if (guests) qs.push("guests="   + encodeURIComponent(guests));
  bootstrap.Modal.getInstance(document.getElementById("hdEditModal"))?.hide();
  window.location.href = "hotels.php" + (qs.length ? "?" + qs.join("&") : "");
}

// Direct search button (no modal)
function hdDoSearch() { hdOpenModal("city"); }
</script>
<script src="search-state.js"></script>
</body>
</html>
