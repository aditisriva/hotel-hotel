<?php
session_start();
$current_year = date("Y");
$is_logged_in   = isset($_SESSION['hm_id']);
$user_firstname = $is_logged_in ? htmlspecialchars($_SESSION['hm_firstname'] ?? $_SESSION['hm_name'] ?? 'Manager') : '';

require_once '../db.php';
$all_cities = [];
$ac_res = mysqli_query($conn, "SELECT c.id, c.city_name, c.state, c.country, c.city_image, c.is_popular, COUNT(h.hotel_id) AS hotel_count, MIN(h.price_per_night) AS min_price FROM cities c LEFT JOIN hotels h ON h.city_id = c.id AND h.availability_status='active' AND h.approval_status='approved' WHERE c.status='active' GROUP BY c.id ORDER BY c.is_popular DESC, c.city_name ASC");
if ($ac_res) while ($row = mysqli_fetch_assoc($ac_res)) $all_cities[] = $row;

$dest_total_hotels = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM hotels WHERE availability_status='active' AND approval_status='approved'"))['c'];
$dest_total_cities = count($all_cities);
$dest_avg_rating = number_format((float)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(AVG(rating),0) AS r FROM hotels WHERE availability_status='active' AND approval_status='approved'"))['r'], 1);
$dest_min_price = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MIN(price_per_night),999) AS p FROM hotels WHERE availability_status='active' AND approval_status='approved'"))['p'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <title>Explore Destinations – bookHotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="hotels.css"/>
  <style>
    /* Custom adjustments to ensure premium look */
    .dest-listing-card { transition: all 0.3s ease; border-radius: 12px; overflow: hidden; border: 1px solid #eee; }
    .dest-listing-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .dest-img-wrapper { position: relative; height: 200px; overflow: hidden; }
    .dest-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .dest-listing-card:hover .dest-img-wrapper img { transform: scale(1.05); }
    .stat-card { background: white; border-radius: 12px; padding: 24px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon { width: 60px; height: 60px; border-radius: 50%; background: #eef2f6; color: var(--bs-primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 15px; }
    .trending-card { display: flex; align-items: center; background: white; border-radius: 12px; padding: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eee; margin-bottom: 15px; text-decoration: none; color: inherit; transition: all 0.3s; }
    .trending-card:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-color: var(--bs-primary); }
    .trending-img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; margin-right: 15px; }
  </style>
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
        <li class="nav-item"><a class="nav-link active" href="destinations.php">Destinations</a></li>
        <li class="nav-item"><a class="nav-link" href="my-bookings.php">My Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        <li class="nav-item ms-lg-3">
          <?php if ($is_logged_in): ?>
            <div class="dropdown">
              <a class="btn btn-warning btn-sm px-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-fill me-1"></i><?= $user_firstname ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
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

<!-- ========== HERO SECTION ========== -->
<section class="listing-hero" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1506461883276-59463f24f8d5?w=1600&q=80') center/cover no-repeat;">
  <div class="listing-hero-overlay"></div>
  <div class="container position-relative z-1 text-white py-5">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb breadcrumb-dark">
        <li class="breadcrumb-item"><a href="index.php" class="text-warning text-decoration-none">Home</a></li>
        <li class="breadcrumb-item active text-white-50">Destinations</li>
      </ol>
    </nav>
    <h1 class="fw-800 display-4 mb-2">Explore Popular Destinations</h1>
    <p class="opacity-75 lead mb-4">Discover the most breathtaking places and find the perfect stay for your next adventure.</p>
    
    <!-- Search Destinations Section -->
    <div class="bg-white p-2 rounded-pill shadow-sm d-inline-block w-100" style="max-width: 600px;">
      <form action="hotels.php" method="GET" class="d-flex align-items-center">
        <i class="bi bi-search text-muted ms-3 me-2"></i>
        <select name="city_id" class="form-select border-0 shadow-none bg-transparent ps-1" style="width:auto; min-width:180px">
          <option value="">All Cities</option>
          <?php foreach ($all_cities as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars(ucfirst($c['city_name'])) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary px-4 fw-600 rounded-pill">Search</button>
      </form>
    </div>
    <div class="mt-3">
      <span class="text-white-50 small me-2">Popular searches:</span>
      <?php 
        $pop_searches = array_slice($all_cities, 0, 3);
        foreach ($pop_searches as $ps):
      ?>
        <a href="hotels.php?city_id=<?= (int)$ps['id'] ?>" class="badge bg-light text-dark text-decoration-none rounded-pill py-2 px-3 fw-500 opacity-75"><?= htmlspecialchars(ucfirst($ps['city_name'])) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ========== POPULAR DESTINATIONS GRID ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-800 mb-2">Our Top Destinations</h2>
      <p class="text-muted">Handpicked cities loved by travelers. Best price guaranteed.</p>
    </div>
    <div class="row g-4">
      <?php if (empty($all_cities)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i class="bi bi-geo-alt" style="font-size:3rem;opacity:.3"></i>
          <div class="fw-bold mt-3">No destinations available yet</div>
        </div>
      <?php else: ?>
        <?php foreach ($all_cities as $c): 
          $img = !empty($c['city_image']) ? htmlspecialchars($c['city_image']) : 'https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=600&q=80';
          $name = htmlspecialchars(ucfirst($c['city_name']));
          $state_country = htmlspecialchars(($c['state'] ?? '') . ($c['state'] && $c['country'] ? ', ' : '') . ($c['country'] ?? 'India'));
          $count = (int)$c['hotel_count'];
          $min_price = isset($c['min_price']) && $c['min_price'] > 0 ? '₹' . number_format($c['min_price'], 0) . '/night' : '—';
        ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="dest-listing-card bg-white h-100 d-flex flex-column">
            <div class="dest-img-wrapper">
              <img src="<?= $img ?>" alt="<?= $name ?>" onerror="this.src='https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=600&q=80'">
              <span class="badge bg-primary position-absolute top-0 start-0 m-3 fs-6"><?= $count ?> Hotels</span>
            </div>
            <div class="p-4 d-flex flex-column flex-grow-1">
              <h4 class="fw-700 mb-1"><?= $name ?></h4>
              <p class="text-muted small mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= $state_country ?></p>
              <div class="d-flex justify-content-between align-items-center mb-3 mt-auto border-top pt-3">
                <div>
                  <p class="text-muted small mb-0">Starting from</p>
                  <div class="fw-800 text-primary fs-5"><?= $min_price ?></div>
                </div>
                <div class="text-end">
                  <p class="text-muted small mb-0">Hotels</p>
                  <div class="fw-600 text-dark"><?= $count ?></div>
                </div>
              </div>
              <a href="hotels.php?city_id=<?= (int)$c['id'] ?>" class="btn btn-outline-primary w-100 fw-600">View Hotels</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ========== TWO-COLUMN INFO SECTION ========== -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-5">
      <!-- Trending Destinations -->
      <div class="col-lg-6">
        <h3 class="fw-800 mb-4">Trending This Month</h3>
        <?php 
          $trending = array_slice($all_cities, 0, 3);
          $trending_descs = [
            'goa' => 'Beach escapes & vibrant nightlife',
            'manali' => 'Snowy peaks & adventure sports',
            'jaipur' => 'Royal heritage & cultural experiences',
            'mumbai' => 'City of dreams & culinary delights',
            'delhi' => 'Historic landmarks & bustling markets',
            'kerala' => 'Backwaters & serene beaches',
            'udaipur' => 'Royal palaces & lakeside romance',
          ];
          foreach ($trending as $t):
            $slug = strtolower($t['city_name']);
            $desc = $trending_descs[$slug] ?? 'Popular destination';
        ?>
        <a href="hotels.php?city_id=<?= (int)$t['id'] ?>" class="trending-card">
          <img src="<?= !empty($t['city_image']) ? htmlspecialchars($t['city_image']) : 'https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=200&q=80' ?>" alt="<?= htmlspecialchars(ucfirst($t['city_name'])) ?>" class="trending-img" onerror="this.src='https://images.unsplash.com/photo-1477959858617-3f65e62c5714?w=200&q=80'">
          <div>
            <h5 class="fw-700 mb-1 text-dark"><?= htmlspecialchars(ucfirst($t['city_name'])) ?></h5>
            <p class="text-muted small mb-0"><?= $desc ?></p>
          </div>
          <div class="ms-auto pe-3 text-primary"><i class="bi bi-arrow-right-circle fs-3"></i></div>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Why Visit Section -->
      <div class="col-lg-6">
        <h3 class="fw-800 mb-4">Why Book With Us?</h3>
        <div class="d-flex mb-4">
          <div class="me-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
              <i class="bi bi-map fs-5"></i>
            </div>
          </div>
          <div>
            <h5 class="fw-700 mb-1">Top Attractions</h5>
            <p class="text-muted small">Stay closer to the most famous landmarks and tourist spots to maximize your holiday experiences.</p>
          </div>
        </div>
        <div class="d-flex mb-4">
          <div class="me-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
              <i class="bi bi-heart fs-5"></i>
            </div>
          </div>
          <div>
            <h5 class="fw-700 mb-1">Local Experiences</h5>
            <p class="text-muted small">Discover hidden gems and authentic local culture verified by our network of travelers.</p>
          </div>
        </div>
        <div class="d-flex">
          <div class="me-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
              <i class="bi bi-calendar2-check fs-5"></i>
            </div>
          </div>
          <div>
            <h5 class="fw-700 mb-1">Best Time to Visit</h5>
            <p class="text-muted small">Get dynamic pricing that helps you travel at the perfect time without breaking your budget.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== DESTINATION STATISTICS ========== -->
<section class="py-5 bg-light border-top">
  <div class="container">
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-buildings"></i></div>
          <h3 class="fw-800 text-dark mb-1"><?= number_format($dest_total_hotels) ?>+</h3>
          <p class="text-muted small fw-600 mb-0">Total Hotels</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-geo-alt"></i></div>
          <h3 class="fw-800 text-dark mb-1"><?= $dest_total_cities ?>+</h3>
          <p class="text-muted small fw-600 mb-0">Destinations</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-star"></i></div>
          <h3 class="fw-800 text-dark mb-1"><?= $dest_avg_rating ?>/5</h3>
          <p class="text-muted small fw-600 mb-0">Average Rating</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-tag"></i></div>
          <h3 class="fw-800 text-dark mb-1">₹<?= number_format($dest_min_price) ?></h3>
          <p class="text-muted small fw-600 mb-0">Starting Price</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== CTA SECTION ========== -->
<section class="py-5 text-center text-white" style="background: var(--bs-primary); background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);">
  <div class="container py-4">
    <h2 class="display-6 fw-800 mb-3">Ready to Book Your Next Stay?</h2>
    <p class="lead opacity-75 mb-4 max-w-700 mx-auto" style="max-width: 700px;">Explore thousands of premium hotels and secure the best deals today. Don't wait until prices go up!</p>
    <div class="d-flex gap-3 justify-content-center">
      <a href="hotels.php" class="btn btn-light btn-lg px-4 fw-700 text-primary rounded-pill shadow-sm">Explore Hotels</a>
      <a href="contact.php" class="btn btn-outline-light btn-lg px-4 fw-700 rounded-pill border-0" style="background: rgba(255,255,255,0.15); color: white;">Contact Us</a>
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
          <li><a href="#">Partners</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="#">Cancellation Policy</a></li>
          <li><a href="#">Safety Info</a></li>
          <li><a href="#">Report Issue</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="hotels.php">Hotels</a></li>
          <li><a href="destinations.php">Destinations</a></li>
          <li><a href="#">Flights</a></li>
          <li><a href="#">Packages</a></li>
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
      <p class="text-white-50 small mb-0">© <?php echo $current_year; ?> bookHotel Technologies Pvt. Ltd. All rights reserved.</p>
      <div class="d-flex gap-2">
        <img src="https://img.shields.io/badge/Visa-1A1F71?style=flat&logo=visa&logoColor=white" height="20" alt="Visa"/>
        <img src="https://img.shields.io/badge/Mastercard-EB001B?style=flat&logo=mastercard&logoColor=white" height="20" alt="Mastercard"/>
        <img src="https://img.shields.io/badge/UPI-1a73e8?style=flat&logo=google-pay&logoColor=white" height="20" alt="UPI"/>
        <img src="https://img.shields.io/badge/PayPal-003087?style=flat&logo=paypal&logoColor=white" height="20" alt="PayPal"/>
      </div>
    </div>
  </div>
</footer>

<!-- Back to top -->
<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="position:fixed; bottom:20px; right:20px; display:none; z-index:99;">
  <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 50) {
      nav.classList.add('scrolled', 'bg-dark', 'shadow-sm');
    } else {
      nav.classList.remove('scrolled', 'bg-dark', 'shadow-sm');
    }
    const btt = document.getElementById('backToTop');
    if(window.scrollY > 300) btt.style.display = 'block';
    else btt.style.display = 'none';
  });
</script>
</body>
</html>
