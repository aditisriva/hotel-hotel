<?php
session_start();
require_once 'pricing.php';
require_once 'hotel_functions.php';

// Read all URL params
$city_param   = isset($_GET['city'])     ? strtolower(trim($_GET['city']))  : '';
$city_label   = $city_param ? ucfirst($city_param) : '';
$checkin_raw  = isset($_GET['checkin'])  ? trim($_GET['checkin'])  : '';
$checkout_raw = isset($_GET['checkout']) ? trim($_GET['checkout']) : '';
$guests_raw   = isset($_GET['guests'])   ? (int)$_GET['guests']   : 0;
$max_price    = isset($_GET['max_price'])? (float)$_GET['max_price'] : 0;
$min_rating   = isset($_GET['min_rating'])?(float)$_GET['min_rating']: 0;

if (!function_exists('bhFmtDate')) {
    function bhFmtDate(string $d): string {
        if (!$d) return ''; $ts = strtotime($d); return $ts ? date('d M Y', $ts) : $d;
    }
    function bhCalcNights(string $ci, string $co): int {
        if (!$ci || !$co) return 1; $diff = (strtotime($co) - strtotime($ci)) / 86400; return max(1,(int)$diff);
    }
}
$nights       = bhCalcNights($checkin_raw, $checkout_raw);
$checkin_fmt  = bhFmtDate($checkin_raw);
$checkout_fmt = bhFmtDate($checkout_raw);
$guests_label = $guests_raw >= 6 ? '3 Rooms, 6 Guests' : ($guests_raw >= 4 ? '2 Rooms, 4 Guests' : ($guests_raw == 1 ? '1 Room, 1 Guest' : '1 Room, 2 Guests'));
$qs_parts = [];
if ($checkin_raw)  $qs_parts[] = 'checkin='  . urlencode($checkin_raw);
if ($checkout_raw) $qs_parts[] = 'checkout=' . urlencode($checkout_raw);
if ($guests_raw)   $qs_parts[] = 'guests='   . $guests_raw;
$booking_qs = $qs_parts ? '&' . implode('&', $qs_parts) : '';

$is_logged_in   = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? htmlspecialchars($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'User') : '';
$user_initial   = $is_logged_in ? strtoupper(substr($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)) : '';

// ── Fetch hotels from DB ──────────────────────────────────────────────────
$filtered    = bhGetHotels($city_param, $guests_raw, $max_price, $min_rating);
$hotel_count = count($filtered);
$page_title  = $city_param ? "Hotels in $city_label" : "Find Your Perfect Hotel";
$count_text  = $hotel_count . ' hotel' . ($hotel_count !== 1 ? 's' : '');
$sub_parts = [];
if ($city_label)   $sub_parts[] = $city_label;
if ($checkin_fmt && $checkout_fmt) $sub_parts[] = $checkin_fmt . " -> " . $checkout_fmt . " (" . $nights . " night" . ($nights>1?"s":"") . ")";
if ($guests_label && $guests_raw)  $sub_parts[] = $guests_label;
$page_sub = $count_text . ' found' . ($sub_parts ? ' · ' . implode(' · ', $sub_parts) : ' across India');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <title><?php echo htmlspecialchars($page_title); ?> – bookHotel</title>
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
         <li class="nav-item ms-lg-3" id="navAuthSlot">
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

<!-- ========== PAGE HERO ========== -->
<section class="listing-hero">
  <div class="listing-hero-overlay"></div>
  <div class="container position-relative z-1 text-white py-5">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb breadcrumb-dark">
        <li class="breadcrumb-item"><a href="index.php" class="text-warning text-decoration-none">Home</a></li>
        <li class="breadcrumb-item active text-white-50">Hotels</li>
      </ol>
    </nav>
    <h1 class="fw-800 display-5 mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
    <p class="opacity-75 mb-4"><?php echo htmlspecialchars($page_sub); ?></p>
    <div class="listing-search-bar">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small fw-600 text-muted">WHERE</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-warning"></i></span>
            <input type="text" id="heroSearchCity" class="form-control border-start-0 ps-0" placeholder="City, hotel or destination" value="<?php echo htmlspecialchars($city_label ?: "India"); ?>" />
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-600 text-muted">CHECK-IN</label>
          <input type="date" class="form-control" id="checkin" value="<?php echo htmlspecialchars($checkin_raw); ?>"/>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-600 text-muted">CHECK-OUT</label>
          <input type="date" class="form-control" id="checkout" value="<?php echo htmlspecialchars($checkout_raw); ?>"/>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-600 text-muted">ROOMS & GUESTS</label>
          <select class="form-select">
            <?php $g=$guests_raw; ?><option value="2" <?php echo (!$g||$g==2)?"selected":""; ?>>1 Room, 2 Guests</option>
            <option value="1" <?php echo ($g==1)?"selected":""; ?>>1 Room, 1 Guest</option>
            <option value="4" <?php echo ($g==4)?"selected":""; ?>>2 Rooms, 4 Guests</option>
            <option value="6" <?php echo ($g>=6)?"selected":""; ?>>3 Rooms, 6 Guests</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button class="btn btn-warning w-100 fw-700 py-2">
            <i class="bi bi-search me-1"></i>Search
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== MAIN CONTENT ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">

      <!-- ===== FILTERS SIDEBAR ===== -->
      <div class="col-12 col-lg-3">
        <div class="filter-card">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-700 mb-0"><i class="bi bi-sliders me-2 text-danger"></i>Filters</h6>
            <a href="#" class="text-danger small fw-600 text-decoration-none">Clear All</a>
          </div>

          <!-- Location -->
          <div class="filter-group">
            <h6 class="filter-title">Location</h6>
            <div class="input-group input-group-sm mb-2">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control" placeholder="Search location..."/>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <span class="filter-chip <?php echo !$city_param ? 'active' : ''; ?>" data-city="">All</span>
              <span class="filter-chip <?php echo $city_param==='mumbai'  ? 'active' : ''; ?>" data-city="mumbai">Mumbai</span>
              <span class="filter-chip <?php echo $city_param==='goa'     ? 'active' : ''; ?>" data-city="goa">Goa</span>
              <span class="filter-chip <?php echo $city_param==='delhi'   ? 'active' : ''; ?>" data-city="delhi">Delhi</span>
              <span class="filter-chip <?php echo $city_param==='jaipur'  ? 'active' : ''; ?>" data-city="jaipur">Jaipur</span>
              <span class="filter-chip <?php echo $city_param==='kerala'  ? 'active' : ''; ?>" data-city="kerala">Kerala</span>
              <span class="filter-chip <?php echo $city_param==='manali'  ? 'active' : ''; ?>" data-city="manali">Manali</span>
              <span class="filter-chip <?php echo $city_param==='udaipur' ? 'active' : ''; ?>" data-city="udaipur">Udaipur</span>
            </div>
          </div>

          <!-- Price Range -->
          <div class="filter-group">
            <h6 class="filter-title">Price Per Night</h6>
            <input type="range" class="form-range price-range" min="500" max="25000" value="15000" id="priceRange"
              oninput="document.getElementById('priceVal').textContent='₹'+this.value.toLocaleString()"/>
            <div class="d-flex justify-content-between mt-1">
              <span class="text-muted small">₹500</span>
              <span class="fw-700 small text-danger" id="priceVal">₹15,000</span>
              <span class="text-muted small">₹25,000</span>
            </div>
          </div>

          <!-- Star Rating -->
          <div class="filter-group">
            <h6 class="filter-title">Star Rating</h6>
            <div class="d-flex flex-column gap-2">
              <label class="filter-check"><input type="checkbox" checked/><span class="ms-2">5 Star <span class="text-warning ms-1">★★★★★</span></span></label>
              <label class="filter-check"><input type="checkbox" checked/><span class="ms-2">4 Star <span class="text-warning ms-1">★★★★</span></span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2">3 Star <span class="text-warning ms-1">★★★</span></span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2">2 Star <span class="text-warning ms-1">★★</span></span></label>
            </div>
          </div>

          <!-- Guest Rating -->
          <div class="filter-group">
            <h6 class="filter-title">Guest Rating</h6>
            <div class="d-flex flex-column gap-2">
              <label class="filter-check"><input type="checkbox" class="guest-rating-check" data-minrating="4.5" checked/><span class="ms-2">Excellent (4.5+)</span></label>
              <label class="filter-check"><input type="checkbox" class="guest-rating-check" data-minrating="4.0" checked/><span class="ms-2">Very Good (4.0+)</span></label>
              <label class="filter-check"><input type="checkbox" class="guest-rating-check" data-minrating="3.5"/><span class="ms-2">Good (3.5+)</span></label>
            </div>
          </div>

          <!-- Amenities -->
          <div class="filter-group">
            <h6 class="filter-title">Amenities</h6>
            <div class="d-flex flex-column gap-2">
              <label class="filter-check"><input type="checkbox" checked/><span class="ms-2"><i class="bi bi-wifi text-primary me-1"></i>Free WiFi</span></label>
              <label class="filter-check"><input type="checkbox" checked/><span class="ms-2"><i class="bi bi-droplet-fill text-info me-1"></i>Swimming Pool</span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2"><i class="bi bi-cup-hot text-warning me-1"></i>Breakfast Included</span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2"><i class="bi bi-car-front text-success me-1"></i>Free Parking</span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2"><i class="bi bi-fan text-secondary me-1"></i>Air Conditioning</span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2"><i class="bi bi-dumbbell text-danger me-1"></i>Gym / Fitness</span></label>
              <label class="filter-check"><input type="checkbox"/><span class="ms-2"><i class="bi bi-flower1 text-success me-1"></i>Spa &amp; Wellness</span></label>
            </div>
          </div>

          <!-- Property Type -->
          <div class="filter-group border-0 pb-0">
            <h6 class="filter-title">Property Type</h6>
            <div class="d-flex flex-column gap-2">
              <label class="filter-check"><input type="checkbox" class="prop-type-check" data-proptype="hotel" checked/><span class="ms-2">Hotel</span></label>
              <label class="filter-check"><input type="checkbox" class="prop-type-check" data-proptype="resort"/><span class="ms-2">Resort</span></label>
              <label class="filter-check"><input type="checkbox" class="prop-type-check" data-proptype="villa"/><span class="ms-2">Villa</span></label>
              <label class="filter-check"><input type="checkbox" class="prop-type-check" data-proptype="homestay"/><span class="ms-2">Homestay</span></label>
              <label class="filter-check"><input type="checkbox" class="prop-type-check" data-proptype="boutique-hotel"/><span class="ms-2">Boutique Hotel</span></label>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== HOTEL LISTINGS ===== -->
      <div class="col-12 col-lg-9">

        <!-- City Banner (shown when filtering by city) -->
        <?php if ($city_param): ?>
        <div class="mb-3 p-3 rounded-3 d-flex align-items-center gap-3" style="background:linear-gradient(135deg,#e8f0fe,#dbeafe);border:1.5px solid #bfdbfe">
          <i class="bi bi-geo-alt-fill text-primary fs-4"></i>
          <div>
            <div class="fw-700" style="color:#1a1a2e">Hotels in <?php echo htmlspecialchars($city_label); ?></div>
            <div class="small text-muted">Showing all available hotels in this destination</div>
          </div>
          <a href="hotels.php" class="ms-auto btn btn-outline-primary btn-sm flex-shrink-0">Clear Filter</a>
        </div>
        <?php endif; ?>


        <!-- Active Search Filter Strip -->
        <?php if ($city_param || $checkin_raw || $guests_raw): ?>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 rounded-3" style="background:#f0f4ff;border:1px solid #bfdbfe">
          <span class="small fw-700 text-primary"><i class="bi bi-funnel-fill me-1"></i>Filters:</span>
          <?php if ($city_label): ?>
          <span class="badge" style="background:#1a56db;font-size:.75rem"><i class="bi bi-geo-alt-fill me-1"></i><?php echo htmlspecialchars($city_label); ?></span>
          <?php endif; ?>
          <?php if ($checkin_fmt && $checkout_fmt): ?>
          <span class="badge bg-secondary" style="font-size:.75rem"><i class="bi bi-calendar3 me-1"></i><?php echo htmlspecialchars($checkin_fmt); ?> → <?php echo htmlspecialchars($checkout_fmt); ?> (<?php echo $nights; ?> night<?php echo $nights>1?'s':''; ?>)</span>
          <?php endif; ?>
          <?php if ($guests_raw > 0): ?>
          <span class="badge bg-dark" style="font-size:.75rem"><i class="bi bi-people-fill me-1"></i><?php echo htmlspecialchars($guests_label); ?></span>
          <?php endif; ?>
          <a href="hotels.php" class="ms-auto text-danger small fw-600 text-decoration-none"><i class="bi bi-x-circle me-1"></i>Clear All</a>
        </div>
        <?php endif; ?>
        <!-- Sort Bar -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
          <p class="mb-0 text-muted small"><span class="fw-700 text-dark"><?php echo $count_text; ?></span> found<?php echo $city_param ? ' in ' . htmlspecialchars($city_label) : ' in India'; ?></p>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Sort by:</span>
            <select class="form-select form-select-sm sort-select">
              <option>Recommended</option>
              <option>Price: Low to High</option>
              <option>Price: High to Low</option>
              <option>Rating: High to Low</option>
              <option>Newest First</option>
            </select>
          </div>
        </div>

        <!-- Hotel Cards Grid -->
        <div class="row g-4" id="hotelGrid">

          <?php if (empty($filtered)): ?>
          <!-- No hotels found -->
          <div class="col-12">
            <div class="text-center py-5 my-3">
              <div style="width:100px;height:100px;background:#e8f0fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
                <i class="bi bi-building-x" style="font-size:2.5rem;color:#1a56db"></i>
              </div>
              <h4 class="fw-800 mb-2" style="color:#1a1a2e">No Hotels Found<?php echo $city_param ? ' in ' . htmlspecialchars($city_label) : ''; ?></h4>
              <p class="text-muted mb-4">Try a different city or browse all available hotels.</p>
              <a href="hotels.php" class="btn btn-primary px-4 me-2"><i class="bi bi-search me-2"></i>Browse All Hotels</a>
              <a href="index.php" class="btn btn-outline-secondary px-4"><i class="bi bi-house me-2"></i>Back to Home</a>
            </div>
          </div>

          <?php else: ?>
          <?php foreach ($filtered as $h):
            $hid      = (int)$h['hotel_id'];
            $hname    = htmlspecialchars($h['hotel_name']);
            $hcity    = htmlspecialchars(ucfirst($h['city']));
            $hloc     = htmlspecialchars($h['location']);
            $hstate   = htmlspecialchars($h['state'] ?? '');
            $hdesc    = htmlspecialchars($h['description'] ?? '');
            $hprice   = (float)$h['price_per_night'];
            $horig    = (float)($h['original_price'] ?? 0);
            $hrating  = (float)$h['rating'];
            $hstars   = (int)($h['star_rating'] ?? 3);
            $htype    = $h['property_type'];
            $hcap     = (int)$h['capacity'];
            $hfeatured= (int)($h['featured'] ?? 0);
            $hstatus  = $h['availability_status'];
            $hamenity = $h['amenities'] ?? '';
            $amenTags = array_slice(array_filter(array_map('trim', explode(',', $hamenity))), 0, 3);
            $img      = bhFirstImage($h['hotel_images'] ?? '', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=80');
            $discPct  = $horig > $hprice ? round(($horig - $hprice) / $horig * 100) : 0;
            // Badge
            if ($hfeatured && $discPct >= 30) $badge = ['bg-danger', $discPct . '% OFF'];
            elseif ($hfeatured)               $badge = ['bg-warning text-dark', 'Best Seller'];
            elseif ($discPct >= 20)           $badge = ['bg-danger', $discPct . '% OFF'];
            else                              $badge = ['bg-success', 'Free Cancellation'];
            // Stars HTML
            $starsHtml = '';
            for ($s = 1; $s <= 5; $s++) {
              if ($s <= $hstars) $starsHtml .= '<i class="bi bi-star-fill"></i>';
            }
            // Detail link with hotel_id
            $detailUrl = 'hotel-details.php?id=' . $hid . '&city=' . urlencode($h['city']) . $booking_qs;
          ?>
          <div class="col-12 col-md-6 col-xl-4"
               data-price="<?php echo $hprice; ?>"
               data-rating="<?php echo $hrating; ?>"
               data-name="<?php echo $hname; ?>"
               data-location="<?php echo htmlspecialchars($h['city']); ?>"
               data-type="<?php echo htmlspecialchars($htype); ?>">
            <div class="hotel-card card border-0 shadow-sm h-100">
              <div class="position-relative">
                <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top hotel-img" alt="<?php echo $hname; ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=80'"/>
                <span class="badge <?php echo $badge[0]; ?> position-absolute top-0 start-0 m-2"><?php echo $badge[1]; ?></span>
                <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
                <span class="stars-badge"><?php echo $starsHtml; ?></span>
              </div>
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <h6 class="fw-700 mb-0"><?php echo $hname; ?></h6>
                  <span class="rating-badge"><?php echo number_format($hrating,1); ?> <i class="bi bi-star-fill"></i></span>
                </div>
                <p class="text-muted small mb-2">
                  <i class="bi bi-geo-alt-fill me-1 text-danger"></i>
                  <?php echo $hcity; ?><?php echo $hstate ? ', ' . $hstate : ''; ?>
                </p>
                <p class="text-muted small mb-3 flex-grow-1"><?php echo $hdesc ?: 'A premium stay awaits you.'; ?></p>
                <div class="d-flex gap-1 flex-wrap mb-3">
                  <?php foreach ($amenTags as $tag):
                    $icon = bhAmenityIcon($tag);
                    $label = ucfirst($tag);
                  ?>
                  <span class="amenity-tag"><i class="bi <?php echo $icon; ?>"></i> <?php echo $label; ?></span>
                  <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <div>
                    <?php bhPriceBlock($hprice, $horig); ?>
                  </div>
                  <a href="<?php echo $detailUrl; ?>" class="btn btn-primary btn-sm px-3">View Details</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>

        </div><!-- end #hotelGrid -->

        <div id="emptyState" class="d-none text-center py-5 my-3">
          <div style="width:100px;height:100px;background:#e8f0fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
            <i class="bi bi-building-x" style="font-size:2.5rem;color:#1a56db"></i>
          </div>
          <h4 class="fw-800 mb-2" style="color:#1a1a2e">No Hotels Match Your Filters</h4>
          <p class="text-muted mb-4" id="emptyStateMsg">Try adjusting your search criteria.</p>
          <a href="hotels.php" class="btn btn-primary px-4 me-2"><i class="bi bi-search me-2"></i>Browse All Hotels</a>
        </div>

        <!-- Pagination — rendered dynamically by pagination.js -->
        <div class="d-flex justify-content-center mt-5">
          <nav aria-label="Hotel listing pagination">
            <ul class="pagination pagination-custom" id="paginationList">
              <!-- Rendered dynamically by pagination.js -->
            </ul>
          </nav>
        </div>

      </div><!-- end listings col -->
    </div><!-- end row -->
  </div><!-- end container -->
</section>

<!-- ========== FOOTER ========== -->
<footer class="bg-dark text-white pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <h5 class="fw-800 mb-3"><i class="bi bi-building-fill text-warning me-1"></i>bookHotel</h5>
        <p class="text-white-50 small">Your trusted travel partner. We make hotel booking simple, affordable, and enjoyable.</p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="text-white-50"><i class="bi bi-facebook fs-5"></i></a>
          <a href="#" class="text-white-50"><i class="bi bi-twitter-x fs-5"></i></a>
          <a href="#" class="text-white-50"><i class="bi bi-instagram fs-5"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Company</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">About Us</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="hotels.php">Hotels</a></li>
          <li><a href="my-bookings.php">My Bookings</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Legal</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="privacy-policy.php">Privacy Policy</a></li>
          <li><a href="terms-of-service.php">Terms of Use</a></li>
          <li><a href="cookie-policy.php">Cookie Policy</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary"/>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <p class="text-white-50 small mb-0">© 2026 bookHotel Technologies Pvt. Ltd. All rights reserved.</p>
    </div>
  </div>
</footer>

<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top"
  onclick="window.scrollTo({top:0,behavior:'smooth'})">
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
<script src="search-state.js"></script>
<script src="pagination.js"></script>
<script>
  // Navbar scroll + back-to-top
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
    const btt = document.getElementById('backToTop');
    if (btt) btt.classList.toggle('show', window.scrollY > 300);
  });

  // Wishlist toggle
  document.querySelectorAll('.btn-wishlist').forEach(btn => {
    btn.addEventListener('click', () => {
      const icon = btn.querySelector('i');
      icon.classList.toggle('bi-heart');
      icon.classList.toggle('bi-heart-fill');
      icon.classList.toggle('text-danger');
    });
  });

  // Restore search state from sessionStorage if not in URL
  (function() {
    const p = new URLSearchParams(window.location.search);
    const ci = document.getElementById('checkin');
    const co = document.getElementById('checkout');
    if (ci && !p.get('checkin')) {
      const saved = bhSearch ? bhSearch.get('checkin') : sessionStorage.getItem('bh_checkin');
      if (saved) ci.value = saved;
    }
    if (co && !p.get('checkout')) {
      const saved = bhSearch ? bhSearch.get('checkout') : sessionStorage.getItem('bh_checkout');
      if (saved) co.value = saved;
    }
  })();

  // Hero search button
  document.querySelector('.listing-search-bar .btn-warning')?.addEventListener('click', function() {
    const city     = document.getElementById('heroSearchCity').value.trim().toLowerCase();
    const checkin  = document.getElementById('checkin').value;
    const checkout = document.getElementById('checkout').value;
    const guests   = document.querySelector('.listing-search-bar select').value;
    if (window.bhSearch) { bhSearch.save({city, checkin, checkout, guests}); }
    let url = 'hotels.php?';
    if (city)     url += 'city='     + encodeURIComponent(city)    + '&';
    if (checkin)  url += 'checkin='  + encodeURIComponent(checkin) + '&';
    if (checkout) url += 'checkout=' + encodeURIComponent(checkout)+ '&';
    if (guests)   url += 'guests='   + guests;
    window.location.href = url;
  });

  // Filter chips (city)
  document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', function() {
      const city = this.dataset.city;
      const p    = new URLSearchParams(window.location.search);
      if (city) p.set('city', city); else p.delete('city');
      window.location.href = 'hotels.php?' + p.toString();
    });
  });

  // ── Client-side filter/sort on already-fetched cards ─────────────────────
  let maxPrice = 25000;

  document.getElementById('priceRange')?.addEventListener('input', function() {
    maxPrice = parseInt(this.value);
    applyFilters();
  });

  document.querySelectorAll('.guest-rating-check').forEach(cb => {
    cb.addEventListener('change', applyFilters);
  });
  document.querySelectorAll('.prop-type-check').forEach(cb => {
    cb.addEventListener('change', applyFilters);
  });

  document.querySelector('.sort-select')?.addEventListener('change', function() {
    const cards = [...document.querySelectorAll('#hotelGrid .col-12[data-price]')];
    const val   = this.value;
    cards.sort((a, b) => {
      if (val === 'Price: Low to High')  return a.dataset.price - b.dataset.price;
      if (val === 'Price: High to Low')  return b.dataset.price - a.dataset.price;
      if (val === 'Rating: High to Low') return b.dataset.rating - a.dataset.rating;
      return 0;
    });
    const grid = document.getElementById('hotelGrid');
    cards.forEach(c => grid.appendChild(c));
  });

  function applyFilters() {
    const minRatings = [...document.querySelectorAll('.guest-rating-check:checked')].map(c => parseFloat(c.dataset.minrating));
    const minRating  = minRatings.length ? Math.min(...minRatings) : 0;
    const propTypes  = [...document.querySelectorAll('.prop-type-check:checked')].map(c => c.dataset.proptype);

    let visible = 0;
    document.querySelectorAll('#hotelGrid .col-12[data-price]').forEach(card => {
      const price  = parseFloat(card.dataset.price);
      const rating = parseFloat(card.dataset.rating);
      const type   = card.dataset.type;
      const show   = price <= maxPrice
        && (minRatings.length === 0 || rating >= minRating)
        && (propTypes.length === 0  || propTypes.includes(type));
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    const empty = document.getElementById('emptyState');
    if (empty) empty.classList.toggle('d-none', visible > 0);
  }
</script>
</body>
</html>
