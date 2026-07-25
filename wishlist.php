<?php
session_start();
$is_logged_in   = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? htmlspecialchars($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'User') : '';
$user_initial   = $is_logged_in ? strtoupper(substr($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Wishlist — bookHotel</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="hotels.css"/>
  <link rel="stylesheet" href="wishlist.css"/>
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

<!-- ========== HERO ========== -->
<section class="wl-hero">
  <div class="wl-hero__overlay"></div>
  <div class="container position-relative" style="z-index:2">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb wl-breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">My Wishlist</li>
      </ol>
    </nav>
    <div class="wl-hero__content">
      <div>
        <div class="wl-hero__badge"><i class="bi bi-heart-fill me-2"></i>Saved Hotels</div>
        <h1 class="wl-hero__title">My Wishlist</h1>
        <p class="wl-hero__sub">Save your favourite hotels for future stays.</p>
      </div>
      <div class="wl-hero__count" id="heroCount">
        <i class="bi bi-heart-fill wl-hero__count-icon"></i>
        <span class="wl-hero__count-num" id="heroCountNum">5</span>
        <span class="wl-hero__count-label">Saved Hotels</span>
      </div>
    </div>
  </div>
</section>

<!-- ========== SUMMARY CARDS ========== -->
<section class="py-4 bg-white border-bottom">
  <div class="container">
    <div class="wl-summary-grid">
      <div class="wl-sum-card wl-sum-card--blue">
        <i class="bi bi-heart-fill wl-sum-card__icon"></i>
        <div class="wl-sum-card__body">
          <span class="wl-sum-card__num" id="sumTotal">5</span>
          <span class="wl-sum-card__label">Total Saved</span>
        </div>
      </div>
      <div class="wl-sum-card wl-sum-card--gold">
        <i class="bi bi-gem wl-sum-card__icon"></i>
        <div class="wl-sum-card__body">
          <span class="wl-sum-card__num">2</span>
          <span class="wl-sum-card__label">Luxury Hotels</span>
        </div>
      </div>
      <div class="wl-sum-card wl-sum-card--green">
        <i class="bi bi-water wl-sum-card__icon"></i>
        <div class="wl-sum-card__body">
          <span class="wl-sum-card__num">2</span>
          <span class="wl-sum-card__label">Resorts</span>
        </div>
      </div>
      <div class="wl-sum-card wl-sum-card--purple">
        <i class="bi bi-tag-fill wl-sum-card__icon"></i>
        <div class="wl-sum-card__body">
          <span class="wl-sum-card__num">1</span>
          <span class="wl-sum-card__label">Budget Hotels</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== WISHLIST GRID ========== -->
<section class="py-5 bg-light">
  <div class="container">

    <!-- Toolbar -->
    <div class="wl-toolbar">
      <p class="wl-toolbar__count mb-0"><span class="fw-700 text-dark" id="toolbarCount">5 hotels</span> saved in your wishlist</p>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <select class="form-select form-select-sm wl-sort-select" id="wlSort">
          <option value="">Sort: Default</option>
          <option value="price-asc">Price: Low to High</option>
          <option value="price-desc">Price: High to Low</option>
          <option value="rating">Rating: High to Low</option>
        </select>
        <button class="wl-clear-btn" id="clearAllBtn" title="Clear wishlist">
          <i class="bi bi-trash3"></i> Clear All
        </button>
      </div>
    </div>

    <!-- Cards Grid -->
    <div class="row g-4" id="wishlistGrid">

      <!-- Card 1 -->
      <div class="col-12 col-md-6 col-xl-4 wl-card-wrap" data-price="4299" data-rating="4.8" data-id="wl1">
        <div class="hotel-card card border-0 shadow-sm h-100 wl-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=80" class="card-img-top hotel-img" alt="The Grand Palace"/>
            <span class="badge bg-success position-absolute top-0 start-0 m-2">Free Cancellation</span>
            <button class="btn-wishlist wl-remove-btn active" aria-label="Remove from Wishlist" data-id="wl1">
              <i class="bi bi-heart-fill text-danger"></i>
            </button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
            <span class="wl-discount-badge">33% OFF</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">The Grand Palace</h6>
              <span class="rating-badge">4.8 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Mumbai, Maharashtra</p>
            <p class="text-muted small mb-3 flex-grow-1">Iconic luxury hotel overlooking the Arabian Sea with world-class dining and premium spa.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-droplet-fill"></i> Pool</span>
              <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹6,500</span>
                <div class="fw-800 text-primary fs-5">₹4,299<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
            </div>
            <div class="wl-card-actions">
              <a href="hotel-details.php" class="wl-btn wl-btn--ghost"><i class="bi bi-eye-fill"></i> View Details</a>
              <a href="guest-details.php" class="wl-btn wl-btn--primary"><i class="bi bi-calendar2-check"></i> Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="col-12 col-md-6 col-xl-4 wl-card-wrap" data-price="5499" data-rating="4.6" data-id="wl2">
        <div class="hotel-card card border-0 shadow-sm h-100 wl-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=500&q=80" class="card-img-top hotel-img" alt="Sunset Beach Resort"/>
            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Best Seller</span>
            <button class="btn-wishlist wl-remove-btn active" aria-label="Remove from Wishlist" data-id="wl2">
              <i class="bi bi-heart-fill text-danger"></i>
            </button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></span>
            <span class="wl-discount-badge">31% OFF</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Sunset Beach Resort</h6>
              <span class="rating-badge">4.6 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Goa, North Goa</p>
            <p class="text-muted small mb-3 flex-grow-1">Beachfront resort with stunning ocean views, water sports, and award-winning seafood restaurant.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-droplet-fill"></i> Pool</span>
              <span class="amenity-tag"><i class="bi bi-car-front"></i> Parking</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹8,000</span>
                <div class="fw-800 text-primary fs-5">₹5,499<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
            </div>
            <div class="wl-card-actions">
              <a href="hotel-details.php" class="wl-btn wl-btn--ghost"><i class="bi bi-eye-fill"></i> View Details</a>
              <a href="guest-details.php" class="wl-btn wl-btn--primary"><i class="bi bi-calendar2-check"></i> Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="col-12 col-md-6 col-xl-4 wl-card-wrap" data-price="4680" data-rating="4.9" data-id="wl3">
        <div class="hotel-card card border-0 shadow-sm h-100 wl-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=500&q=80" class="card-img-top hotel-img" alt="Heritage Haveli"/>
            <span class="badge bg-danger position-absolute top-0 start-0 m-2">35% OFF</span>
            <button class="btn-wishlist wl-remove-btn active" aria-label="Remove from Wishlist" data-id="wl3">
              <i class="bi bi-heart-fill text-danger"></i>
            </button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
            <span class="wl-discount-badge">35% OFF</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Heritage Haveli</h6>
              <span class="rating-badge">4.9 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Jaipur, Rajasthan</p>
            <p class="text-muted small mb-3 flex-grow-1">Royal heritage property with authentic Rajasthani architecture and cultural performances.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast</span>
              <span class="amenity-tag"><i class="bi bi-fan"></i> AC</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹7,200</span>
                <div class="fw-800 text-primary fs-5">₹4,680<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
            </div>
            <div class="wl-card-actions">
              <a href="hotel-details.php" class="wl-btn wl-btn--ghost"><i class="bi bi-eye-fill"></i> View Details</a>
              <a href="guest-details.php" class="wl-btn wl-btn--primary"><i class="bi bi-calendar2-check"></i> Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="col-12 col-md-6 col-xl-4 wl-card-wrap" data-price="12499" data-rating="4.9" data-id="wl4">
        <div class="hotel-card card border-0 shadow-sm h-100 wl-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=500&q=80" class="card-img-top hotel-img" alt="Lake Palace Udaipur"/>
            <span class="badge bg-success position-absolute top-0 start-0 m-2">Free Cancellation</span>
            <button class="btn-wishlist wl-remove-btn active" aria-label="Remove from Wishlist" data-id="wl4">
              <i class="bi bi-heart-fill text-danger"></i>
            </button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
            <span class="wl-discount-badge">31% OFF</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Lake Palace Udaipur</h6>
              <span class="rating-badge">4.9 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Udaipur, Rajasthan</p>
            <p class="text-muted small mb-3 flex-grow-1">Floating palace on Lake Pichola offering unparalleled royal luxury with stunning sunset views.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-droplet-fill"></i> Pool</span>
              <span class="amenity-tag"><i class="bi bi-flower1"></i> Spa</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹18,000</span>
                <div class="fw-800 text-primary fs-5">₹12,499<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
            </div>
            <div class="wl-card-actions">
              <a href="hotel-details.php" class="wl-btn wl-btn--ghost"><i class="bi bi-eye-fill"></i> View Details</a>
              <a href="guest-details.php" class="wl-btn wl-btn--primary"><i class="bi bi-calendar2-check"></i> Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 5 -->
      <div class="col-12 col-md-6 col-xl-4 wl-card-wrap" data-price="3299" data-rating="4.7" data-id="wl5">
        <div class="hotel-card card border-0 shadow-sm h-100 wl-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500&q=80" class="card-img-top hotel-img" alt="Mountain View Lodge"/>
            <span class="badge bg-info text-dark position-absolute top-0 start-0 m-2">Budget Pick</span>
            <button class="btn-wishlist wl-remove-btn active" aria-label="Remove from Wishlist" data-id="wl5">
              <i class="bi bi-heart-fill text-danger"></i>
            </button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></span>
            <span class="wl-discount-badge">40% OFF</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Mountain View Lodge</h6>
              <span class="rating-badge">4.7 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Manali, Himachal Pradesh</p>
            <p class="text-muted small mb-3 flex-grow-1">Cosy mountain retreat with panoramic Himalayan views, wood-fired fireplaces and adventure activities.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-fire"></i> Fireplace</span>
              <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹5,500</span>
                <div class="fw-800 text-primary fs-5">₹3,299<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
            </div>
            <div class="wl-card-actions">
              <a href="hotel-details.php" class="wl-btn wl-btn--ghost"><i class="bi bi-eye-fill"></i> View Details</a>
              <a href="guest-details.php" class="wl-btn wl-btn--primary"><i class="bi bi-calendar2-check"></i> Book Now</a>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /#wishlistGrid -->

    <!-- Empty State -->
    <div class="wl-empty d-none" id="emptyState">
      <div class="wl-empty__illus" aria-hidden="true">
        <div class="wl-empty__circle wl-empty__circle--1"></div>
        <div class="wl-empty__circle wl-empty__circle--2"></div>
        <div class="wl-empty__icon"><i class="bi bi-heart"></i></div>
      </div>
      <h3 class="wl-empty__title">No saved hotels yet</h3>
      <p class="wl-empty__sub">Start exploring and tap the <i class="bi bi-heart-fill text-danger mx-1"></i> to save hotels you love.</p>
      <a href="hotels.php" class="wl-btn wl-btn--primary wl-btn--lg">
        <i class="bi bi-search me-2"></i>Explore Hotels
      </a>
    </div>

  </div>
</section>

<!-- ========== RECENTLY VIEWED ========== -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-800 mb-1" style="color:#1a1a2e"><i class="bi bi-clock-history me-2 text-primary"></i>Recently Viewed</h4>
        <p class="text-muted small mb-0">Hotels you browsed recently</p>
      </div>
      <a href="hotels.php" class="wl-btn wl-btn--outline wl-btn--sm">View All <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="wl-slider-wrap">
      <div class="wl-slider" id="recentSlider">

        <div class="wl-slide-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1582610116397-edb318620f90?w=400&q=80" class="wl-slide-img" alt="Kerala Backwater Resort"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="wl-slide-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0 small">Kerala Backwater Resort</h6>
              <span class="rating-badge" style="font-size:.65rem">4.8 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted mb-2" style="font-size:.75rem"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Alleppey, Kerala</p>
            <div class="fw-800 text-primary">₹6,799<span class="fw-400 text-muted" style="font-size:.75rem">/night</span></div>
            <a href="hotel-details.php" class="wl-btn wl-btn--ghost wl-btn--sm mt-2 w-100 justify-content-center">View Details</a>
          </div>
        </div>

        <div class="wl-slide-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=400&q=80" class="wl-slide-img" alt="The Imperial Delhi"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="wl-slide-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0 small">The Imperial Delhi</h6>
              <span class="rating-badge" style="font-size:.65rem">4.7 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted mb-2" style="font-size:.75rem"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>New Delhi, Delhi</p>
            <div class="fw-800 text-primary">₹8,799<span class="fw-400 text-muted" style="font-size:.75rem">/night</span></div>
            <a href="hotel-details.php" class="wl-btn wl-btn--ghost wl-btn--sm mt-2 w-100 justify-content-center">View Details</a>
          </div>
        </div>

        <div class="wl-slide-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1561501900-3701fa6a0864?w=400&q=80" class="wl-slide-img" alt="Zen Garden Resort"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="wl-slide-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0 small">Zen Garden Resort</h6>
              <span class="rating-badge" style="font-size:.65rem">4.5 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted mb-2" style="font-size:.75rem"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Munnar, Kerala</p>
            <div class="fw-800 text-primary">₹4,100<span class="fw-400 text-muted" style="font-size:.75rem">/night</span></div>
            <a href="hotel-details.php" class="wl-btn wl-btn--ghost wl-btn--sm mt-2 w-100 justify-content-center">View Details</a>
          </div>
        </div>

        <div class="wl-slide-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&q=80" class="wl-slide-img" alt="Coorg Wilderness"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="wl-slide-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0 small">Coorg Wilderness Resort</h6>
              <span class="rating-badge" style="font-size:.65rem">4.6 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted mb-2" style="font-size:.75rem"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Coorg, Karnataka</p>
            <div class="fw-800 text-primary">₹5,200<span class="fw-400 text-muted" style="font-size:.75rem">/night</span></div>
            <a href="hotel-details.php" class="wl-btn wl-btn--ghost wl-btn--sm mt-2 w-100 justify-content-center">View Details</a>
          </div>
        </div>

        <div class="wl-slide-card">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1596436100015-b8eef1ad3a91?w=400&q=80" class="wl-slide-img" alt="Rishikesh Retreat"/>
            <button class="btn-wishlist" aria-label="Wishlist"><i class="bi bi-heart"></i></button>
          </div>
          <div class="wl-slide-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0 small">Rishikesh River Retreat</h6>
              <span class="rating-badge" style="font-size:.65rem">4.4 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted mb-2" style="font-size:.75rem"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Rishikesh, Uttarakhand</p>
            <div class="fw-800 text-primary">₹3,800<span class="fw-400 text-muted" style="font-size:.75rem">/night</span></div>
            <a href="hotel-details.php" class="wl-btn wl-btn--ghost wl-btn--sm mt-2 w-100 justify-content-center">View Details</a>
          </div>
        </div>

      </div>
      <button class="wl-slider-arrow wl-slider-arrow--prev" id="sliderPrev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
      <button class="wl-slider-arrow wl-slider-arrow--next" id="sliderNext" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
    </div>
  </div>
</section>

<!-- ========== RECOMMENDED FOR YOU ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="wl-section-badge">Recommended For You</span>
      <h4 class="fw-800 mt-2" style="color:#1a1a2e">You Might Also Love These</h4>
      <p class="text-muted small">Based on your saved hotels and travel preferences</p>
    </div>
    <div class="row g-4">

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1596386461350-326ccb383e9f?w=400&q=80" class="card-img-top hotel-img" alt="Andaman Pearl Resort"/>
            <span class="badge bg-danger position-absolute top-0 start-0 m-2">25% OFF</span>
            <button class="btn-wishlist" aria-label="Wishlist" onclick="toggleWishlistBtn(this)"><i class="bi bi-heart"></i></button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Andaman Pearl Resort</h6>
              <span class="rating-badge">4.7 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Port Blair, Andaman</p>
            <p class="text-muted small mb-3 flex-grow-1">Pristine beachfront resort with crystal clear waters and world-class snorkelling experiences.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-droplet-fill"></i> Pool</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹9,200</span>
                <div class="fw-800 text-primary fs-5">₹6,900<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View Details</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?w=400&q=80" class="card-img-top hotel-img" alt="Shimla Pine Villa"/>
            <span class="badge bg-success position-absolute top-0 start-0 m-2">Free Cancellation</span>
            <button class="btn-wishlist" aria-label="Wishlist" onclick="toggleWishlistBtn(this)"><i class="bi bi-heart"></i></button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Shimla Pine Villa</h6>
              <span class="rating-badge">4.8 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Shimla, Himachal Pradesh</p>
            <p class="text-muted small mb-3 flex-grow-1">Charming colonial villa surrounded by deodar forests with sweeping valley views.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹5,800</span>
                <div class="fw-800 text-primary fs-5">₹4,100<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View Details</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1590490359854-7e3a9028db99?w=400&q=80" class="card-img-top hotel-img" alt="Mysore Palace Hotel"/>
            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Top Rated</span>
            <button class="btn-wishlist" aria-label="Wishlist" onclick="toggleWishlistBtn(this)"><i class="bi bi-heart"></i></button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Mysore Palace Hotel</h6>
              <span class="rating-badge">4.9 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Mysore, Karnataka</p>
            <p class="text-muted small mb-3 flex-grow-1">Heritage hotel adjacent to the iconic Mysore Palace with royal interiors and garden views.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-flower1"></i> Spa</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹7,500</span>
                <div class="fw-800 text-primary fs-5">₹5,800<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View Details</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="hotel-card card border-0 shadow-sm h-100">
          <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=400&q=80" class="card-img-top hotel-img" alt="Varanasi Ghat Hotel"/>
            <span class="badge bg-info text-dark position-absolute top-0 start-0 m-2">New</span>
            <button class="btn-wishlist" aria-label="Wishlist" onclick="toggleWishlistBtn(this)"><i class="bi bi-heart"></i></button>
            <span class="stars-badge"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-700 mb-0">Varanasi Ghat Boutique</h6>
              <span class="rating-badge">4.5 <i class="bi bi-star-fill"></i></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Varanasi, Uttar Pradesh</p>
            <p class="text-muted small mb-3 flex-grow-1">Boutique hotel on the sacred ghats with sunrise Ganga views and spiritual retreat packages.</p>
            <div class="d-flex gap-1 flex-wrap mb-3">
              <span class="amenity-tag"><i class="bi bi-wifi"></i> WiFi</span>
              <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-auto">
              <div>
                <span class="text-muted text-decoration-line-through small">₹4,500</span>
                <div class="fw-800 text-primary fs-5">₹3,100<span class="fs-6 fw-400 text-muted">/night</span></div>
              </div>
              <a href="hotel-details.php" class="btn btn-primary btn-sm px-3">View Details</a>
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
          <li><a href="#">About Us</a></li><li><a href="#">Careers</a></li>
          <li><a href="#">Press</a></li><li><a href="#">Blog</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="contact.php">Contact Us</a></li><li><a href="#">Help Center</a></li>
          <li><a href="#">Cancellation Policy</a></li><li><a href="#">Safety Info</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="hotels.php">Hotels</a></li><li><a href="destinations.php">Destinations</a></li>
          <li><a href="my-bookings.php">My Bookings</a></li><li><a href="wishlist.php">Wishlist</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Legal</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="privacy-policy.php">Privacy Policy</a></li><li><a href="terms-of-service.php">Terms of Use</a></li>
          <li><a href="cookie-policy.php">Cookie Policy</a></li><li><a href="#">Sitemap</a></li>
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

<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top"
  onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <i class="bi bi-arrow-up"></i>
</button>

<!-- Remove confirm toast -->
<div class="wl-toast-wrap" id="wlToastWrap" aria-live="polite"></div>

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
'use strict';

// Navbar scroll + back-to-top
window.addEventListener('scroll', () => {
  document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
  document.getElementById('backToTop').classList.toggle('show', window.scrollY > 300);
});

// ── Remove from Wishlist ──
document.querySelectorAll('.wl-remove-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const wrap = this.closest('.wl-card-wrap');
    wrap.style.transition = 'all .35s ease';
    wrap.style.opacity = '0';
    wrap.style.transform = 'scale(.9)';
    setTimeout(() => {
      wrap.remove();
      updateCounts();
      checkEmpty();
    }, 350);
    showToast('Removed from wishlist', 'warn');
  });
});

// ── Clear All ──
document.getElementById('clearAllBtn').addEventListener('click', () => {
  const cards = document.querySelectorAll('.wl-card-wrap');
  cards.forEach((c, i) => {
    setTimeout(() => {
      c.style.transition = 'all .3s ease';
      c.style.opacity = '0';
      c.style.transform = 'scale(.9)';
      setTimeout(() => { c.remove(); updateCounts(); checkEmpty(); }, 320);
    }, i * 60);
  });
  showToast('All hotels removed from wishlist', 'warn');
});

// ── Sort ──
document.getElementById('wlSort').addEventListener('change', function() {
  const val = this.value;
  const grid = document.getElementById('wishlistGrid');
  const cards = [...grid.querySelectorAll('.wl-card-wrap')];
  cards.sort((a, b) => {
    const pa = +a.dataset.price, pb = +b.dataset.price;
    const ra = +a.dataset.rating, rb = +b.dataset.rating;
    if (val === 'price-asc')  return pa - pb;
    if (val === 'price-desc') return pb - pa;
    if (val === 'rating')     return rb - ra;
    return 0;
  });
  cards.forEach(c => grid.appendChild(c));
});

// ── Toggle wishlist on recommended cards ──
function toggleWishlistBtn(btn) {
  const icon = btn.querySelector('i');
  const isActive = icon.classList.contains('bi-heart-fill');
  icon.classList.toggle('bi-heart', isActive);
  icon.classList.toggle('bi-heart-fill', !isActive);
  icon.classList.toggle('text-danger', !isActive);
  showToast(isActive ? 'Removed from wishlist' : 'Added to wishlist!', isActive ? 'warn' : 'success');
}

// ── Slider ──
const slider = document.getElementById('recentSlider');
document.getElementById('sliderNext').addEventListener('click', () => {
  slider.scrollBy({ left: 280, behavior: 'smooth' });
});
document.getElementById('sliderPrev').addEventListener('click', () => {
  slider.scrollBy({ left: -280, behavior: 'smooth' });
});

// ── Count helpers ──
function updateCounts() {
  const n = document.querySelectorAll('.wl-card-wrap').length;
  document.getElementById('heroCountNum').textContent = n;
  document.getElementById('sumTotal').textContent = n;
  document.getElementById('toolbarCount').textContent = n + (n === 1 ? ' hotel' : ' hotels');
}
function checkEmpty() {
  const n = document.querySelectorAll('.wl-card-wrap').length;
  document.getElementById('emptyState').classList.toggle('d-none', n > 0);
}

// ── Toast ──
function showToast(msg, type) {
  const wrap = document.getElementById('wlToastWrap');
  const icons = { success: 'bi-check-circle-fill', warn: 'bi-heart-fill', info: 'bi-info-circle-fill' };
  const t = document.createElement('div');
  t.className = `wl-toast wl-toast--${type}`;
  t.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${msg}</span>`;
  wrap.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s, transform .3s';
    t.style.opacity = '0'; t.style.transform = 'translateY(6px)';
    setTimeout(() => t.remove(), 320);
  }, 2800);
}
</script>
</body>
</html>

