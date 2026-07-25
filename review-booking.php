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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <title>Review Your Booking – bookHotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="hotels.css"/>
  <link rel="stylesheet" href="booking.css"/>
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

<!-- ========== BREADCRUMB + PROGRESS ========== -->
<div class="booking-progress-bar">
  <div class="container">
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="hotel-details.php" class="text-warning text-decoration-none small fw-600">
        <i class="bi bi-arrow-left me-1"></i>Back to Hotel
      </a>
    </div>
    <div class="progress-steps">
      <div class="progress-step completed">
        <div class="step-circle"><i class="bi bi-check-lg"></i></div>
        <span>Select Room</span>
      </div>
      <div class="progress-line completed"></div>
      <div class="progress-step active">
        <div class="step-circle">2</div>
        <span>Review Booking</span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
        <div class="step-circle">3</div>
        <span>Guest Details</span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
        <div class="step-circle">4</div>
        <span>Payment</span>
      </div>
    </div>
  </div>
</div>

<!-- ========== MAIN CONTENT ========== -->
<section class="py-4 bg-light">
  <div class="container">
    <div class="row g-4">

      <!-- LEFT COLUMN -->
      <div class="col-12 col-lg-8">

        <!-- Hotel Summary -->
        <div class="review-section-card mb-4">
          <div class="review-section-header">
            <h6 class="fw-700 mb-0"><i class="bi bi-building me-2 text-warning"></i>Hotel Details</h6>
          </div>
          <div class="review-section-body">
            <div class="d-flex gap-3 align-items-start">
              <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=120&q=80"
                   class="hotel-thumb rounded-3" alt="Heritage Haveli"/>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                  <div>
                    <h5 class="fw-800 mb-1">Heritage Haveli</h5>
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>M.I. Road, Pink City, Jaipur, Rajasthan</p>
                    <div class="d-flex align-items-center gap-2">
                      <span class="text-warning small">★★★★★</span>
                      <span class="rating-badge">4.9 <i class="bi bi-star-fill"></i></span>
                      <span class="badge bg-success-subtle text-success border border-success-subtle small">Free Cancellation</span>
                    </div>
                  </div>
                  <a href="hotel-details.php" class="btn btn-outline-primary btn-sm">Change</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stay Details -->
        <div class="review-section-card mb-4">
          <div class="review-section-header">
            <h6 class="fw-700 mb-0"><i class="bi bi-calendar3 me-2 text-warning"></i>Stay Details</h6>
          </div>
          <div class="review-section-body">
            <div class="row g-3">
              <div class="col-6 col-md-3">
                <div class="stay-detail-box">
                  <div class="stay-detail-label">CHECK-IN</div>
                  <div class="stay-detail-value" id="dispCheckin">—</div>
                  <div class="stay-detail-sub">2:00 PM onwards</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="stay-detail-box">
                  <div class="stay-detail-label">CHECK-OUT</div>
                  <div class="stay-detail-value" id="dispCheckout">—</div>
                  <div class="stay-detail-sub">Until 11:00 AM</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="stay-detail-box">
                  <div class="stay-detail-label">DURATION</div>
                  <div class="stay-detail-value" id="dispNights">2 Nights</div>
                  <div class="stay-detail-sub" id="dispDates">—</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="stay-detail-box">
                  <div class="stay-detail-label">GUESTS</div>
                  <div class="stay-detail-value">2 Adults</div>
                  <div class="stay-detail-sub">1 Room</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Room Details -->
        <div class="review-section-card mb-4">
          <div class="review-section-header">
            <h6 class="fw-700 mb-0"><i class="bi bi-door-open me-2 text-warning"></i>Room Details</h6>
          </div>
          <div class="review-section-body">
            <div class="d-flex gap-3 align-items-start">
              <img src="https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=120&q=80"
                   class="hotel-thumb rounded-3" alt="Room"/>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                  <div>
                    <h6 class="fw-700 mb-1" id="selectedRoomName">Deluxe Heritage Room</h6>
                    <p class="text-muted small mb-2">30 m² · King Bed · City View · Non-smoking</p>
                    <div class="d-flex flex-wrap gap-2">
                      <span class="amenity-tag"><i class="bi bi-wifi"></i> Free WiFi</span>
                      <span class="amenity-tag"><i class="bi bi-cup-hot"></i> Breakfast Included</span>
                      <span class="amenity-tag"><i class="bi bi-arrow-repeat"></i> Free Cancellation</span>
                      <span class="amenity-tag"><i class="bi bi-fan"></i> Air Conditioning</span>
                    </div>
                  </div>
                  <a href="hotel-details.php" class="btn btn-outline-primary btn-sm">Change</a>
                </div>
              </div>
            </div>

            <!-- Cancellation Policy -->
            <div class="cancellation-policy mt-4">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-shield-check-fill text-success"></i>
                <span class="fw-700 small text-success">Free Cancellation</span>
              </div>
              <p class="text-muted small mb-1">Cancel before <strong>check-in date</strong> for a full refund.</p>
              <p class="text-muted small mb-0">After that, you'll be charged for the first night.</p>
            </div>
          </div>
        </div>

        <!-- Guest Information Form -->
        <div class="review-section-card mb-4">
          <div class="review-section-header">
            <h6 class="fw-700 mb-0"><i class="bi bi-person-fill me-2 text-warning"></i>Guest Information</h6>
          </div>
          <div class="review-section-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small fw-600 text-muted">FIRST NAME *</label>
                <input type="text" id="rbFirstName" class="form-control" placeholder="Enter first name"/>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-600 text-muted">LAST NAME *</label>
                <input type="text" id="rbLastName" class="form-control" placeholder="Enter last name"/>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-600 text-muted">EMAIL ADDRESS *</label>
                <input type="email" id="rbEmail" class="form-control" placeholder="Enter email address"/>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small fw-600 text-muted">MOBILE NUMBER *</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">+91</span>
                  <input type="tel" id="rbPhone" class="form-control" placeholder="10-digit mobile number" maxlength="10"/>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-600 text-muted">SPECIAL REQUESTS (Optional)</label>
                <textarea class="form-control" rows="3" placeholder="E.g. early check-in, high floor, anniversary setup..."></textarea>
              </div>
            </div>

            <!-- GST -->
            <div class="gst-toggle mt-4">
              <label class="d-flex align-items-center gap-2 cursor-pointer">
                <input type="checkbox" id="gstToggle" class="form-check-input mt-0" style="accent-color:#e52d5e"/>
                <span class="fw-600 small">I want a GST invoice for this booking</span>
              </label>
              <div id="gstFields" class="row g-3 mt-2" style="display:none!important">
                <div class="col-12 col-md-6">
                  <label class="form-label small fw-600 text-muted">GSTIN</label>
                  <input type="text" class="form-control" placeholder="Enter GSTIN"/>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label small fw-600 text-muted">COMPANY NAME</label>
                  <input type="text" class="form-control" placeholder="Enter company name"/>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Important Info -->
        <div class="review-section-card mb-4">
          <div class="review-section-header">
            <h6 class="fw-700 mb-0"><i class="bi bi-info-circle me-2 text-warning"></i>Important Information</h6>
          </div>
          <div class="review-section-body">
            <ul class="info-list">
              <li><i class="bi bi-check-circle-fill text-success"></i>Valid photo ID required at check-in (Aadhaar/Passport/Driving License)</li>
              <li><i class="bi bi-check-circle-fill text-success"></i>Pet-free property. No animals allowed on the premises.</li>
              <li><i class="bi bi-check-circle-fill text-success"></i>This is a non-smoking property. Smoking is only permitted in designated areas.</li>
              <li><i class="bi bi-check-circle-fill text-success"></i>Complimentary breakfast for 2 adults included in room rate.</li>
              <li><i class="bi bi-exclamation-circle-fill text-warning"></i>Extra bed charges apply for additional guests. Contact hotel for details.</li>
              <li><i class="bi bi-exclamation-circle-fill text-warning"></i>Outdoor swimming pool available from 7:00 AM to 9:00 PM.</li>
            </ul>
          </div>
        </div>

      </div><!-- end left col -->

      <!-- RIGHT: Price Summary -->
      <div class="col-12 col-lg-4">
        <div class="price-summary-card sticky-booking">

          <!-- Header -->
          <div class="price-summary-header">
            <h6 class="fw-700 text-white mb-0"><i class="bi bi-receipt me-2"></i>Price Summary</h6>
          </div>

          <div class="price-summary-body">

            <!-- Room & Dates -->
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="small text-muted">Room Type</span>
              <span class="small fw-600" id="sumRoomName">Deluxe Heritage Room</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="small text-muted">Duration</span>
              <span class="small fw-600" id="sumNights">2 Nights</span>
            </div>
            <hr class="my-3"/>

            <!-- Pricing breakdown -->
            <div class="d-flex justify-content-between mb-2">
              <span class="small text-muted" id="sumBaseLabel">₹4,680 × 2 nights</span>
              <span class="small fw-600" id="sumBase">₹9,360</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="small text-muted"><span id="sumTaxPctLabel">Taxes & Fees (12% GST)</span></span>
              <span class="small fw-600" id="sumTax">₹842</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="small text-muted">Service Charge</span>
              <span class="small fw-600" id="sumService">₹200</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="small text-success fw-600">Discount (35% OFF)</span>
              <span class="small fw-600 text-success" id="sumDiscount">−₹3,276</span>
            </div>

            <hr class="my-3"/>

            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-700">Total Amount</span>
              <span class="fw-800 text-primary fs-5" id="sumTotal">₹6,926</span>
            </div>
            <p class="text-muted small mb-0">Inclusive of all taxes</p>

            <hr class="my-3"/>

            <!-- Coupon -->
            <div class="coupon-input-group mb-4">
              <label class="form-label small fw-600 text-muted">HAVE A COUPON?</label>
              <div class="input-group">
                <input type="text" class="form-control" id="couponInput" placeholder="Enter coupon code"/>
                <button class="btn btn-outline-danger fw-700 btn-sm" onclick="applyCoupon()">Apply</button>
              </div>
              <div id="couponMsg" class="mt-1 small"></div>
            </div>

            <!-- Savings badge -->
            <div class="savings-badge mb-4">
              <i class="bi bi-piggy-bank-fill me-2 text-success"></i>
              <span class="small fw-700 text-success">You're saving <span id="savingsAmt">₹3,276</span> on this booking!</span>
            </div>

            <!-- Book Now CTA -->
            <button class="btn book-now-btn w-100 fw-700 py-3 mb-3" onclick="proceedToPayment()">
              <i class="bi bi-person-fill me-2"></i>Continue to Guest Details
            </button>
            <p class="text-center text-muted small mb-0">
              <i class="bi bi-shield-check-fill text-success me-1"></i>100% Secure · SSL Encrypted
            </p>

            <!-- Payment icons -->
            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
              <img src="https://img.shields.io/badge/Visa-1A1F71?style=flat&logo=visa&logoColor=white" height="20" alt="Visa"/>
              <img src="https://img.shields.io/badge/Mastercard-EB001B?style=flat&logo=mastercard&logoColor=white" height="20" alt="MC"/>
              <img src="https://img.shields.io/badge/UPI-1a73e8?style=flat&logo=google-pay&logoColor=white" height="20" alt="UPI"/>
              <img src="https://img.shields.io/badge/PayPal-003087?style=flat&logo=paypal&logoColor=white" height="20" alt="PP"/>
            </div>
          </div>
        </div>

        <!-- Trust badges -->
        <div class="trust-badges mt-3">
          <div class="trust-item"><i class="bi bi-shield-lock-fill text-success"></i><span>Secure Booking</span></div>
          <div class="trust-item"><i class="bi bi-arrow-counterclockwise text-primary"></i><span>Free Cancellation</span></div>
          <div class="trust-item"><i class="bi bi-headset text-warning"></i><span>24/7 Support</span></div>
        </div>

      </div><!-- end right col -->
    </div><!-- end row -->
  </div><!-- end container -->
</section>

<!-- ========== FOOTER ========== -->
<footer class="bg-dark text-white pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <h5 class="fw-800 mb-3"><i class="bi bi-building-fill text-warning me-1"></i>bookHotel</h5>
        <p class="text-white-50 small">Your trusted travel partner since 2015.</p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-facebook fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-instagram fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-twitter-x fs-5"></i></a>
          <a href="#" class="text-white-50 social-icon"><i class="bi bi-linkedin fs-5"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Company</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">About Us</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Blog</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="#">Cancellation Policy</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h6 class="fw-700 mb-3">Legal</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Use</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary"/>
    <p class="text-white-50 small text-center mb-0">© 2026 bookHotel Technologies Pvt. Ltd. All rights reserved.</p>
  </div>
</footer>

<button id="backToTop" class="btn btn-warning btn-sm rounded-circle shadow" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
  // Navbar scroll
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
    document.getElementById('backToTop').classList.toggle('show', window.scrollY > 300);
  });

  // Room data
  const rooms = {
    'deluxe': { name: 'Deluxe Heritage Room', price: 4680, discount: 0.35 },
    'royal':  { name: 'Royal Suite',          price: 9800, discount: 0.30 },
    'maharaja': { name: 'Maharaja Presidential Suite', price: 19500, discount: 0.30 }
  };

  // Read URL params passed from hotel-details page
  const params   = new URLSearchParams(window.location.search);
  const roomKey  = params.get('room')    || 'deluxe';
  const checkin  = params.get('checkin')  || '';
  const checkout = params.get('checkout') || '';

  const room = rooms[roomKey] || rooms['deluxe'];

  // Format dates
  const fmtDate = s => {
    const d = new Date(s);
    return d.toLocaleDateString('en-IN', { weekday:'short', day:'2-digit', month:'short', year:'numeric' });
  };

  // Calc nights
  const ci = new Date(checkin), co = new Date(checkout);
  const nights = Math.max(1, Math.round((co - ci) / 86400000));

  // Populate stay details
  document.getElementById('dispCheckin').textContent  = fmtDate(checkin);
  document.getElementById('dispCheckout').textContent = fmtDate(checkout);
  document.getElementById('dispNights').textContent   = nights + ' Night' + (nights>1?'s':'');
  document.getElementById('dispDates').textContent    = new Date(checkin).toLocaleDateString('en-IN',{day:'2-digit',month:'short'}) + ' – ' + new Date(checkout).toLocaleDateString('en-IN',{day:'2-digit',month:'short'});

  // Populate room name
  document.getElementById('selectedRoomName').textContent = room.name;

  // Calc pricing
  function updatePricing(extraDiscount = 0) {
    const base     = room.price * nights;
    const taxRate  = room.price <= 2500 ? 0 : room.price <= 7500 ? 0.12 : 0.18;
    const taxPct   = Math.round(taxRate * 100);
    const svc      = 200;
    const tax      = Math.round(base * taxRate);
    const disc     = Math.round(base * (room.discount + extraDiscount));
    const total    = base + tax - disc + svc;

    document.getElementById('sumRoomName').textContent   = room.name;
    document.getElementById('sumNights').textContent     = nights + ' Night' + (nights>1?'s':'');
    document.getElementById('sumBaseLabel').textContent  = '₹' + room.price.toLocaleString() + ' × ' + nights + ' night' + (nights>1?'s':'');
    document.getElementById('sumBase').textContent       = '₹' + base.toLocaleString();
    document.getElementById('sumTax').textContent        = '₹' + tax.toLocaleString();
    const taxPL = document.getElementById('sumTaxPctLabel');
    if (taxPL) taxPL.textContent = 'GST (' + taxPct + '%)';
    const svcEl = document.getElementById('sumService');
    if (svcEl) svcEl.textContent = '₹' + svc.toLocaleString();
    document.getElementById('sumDiscount').textContent   = '−₹' + disc.toLocaleString();
    document.getElementById('sumTotal').textContent      = '₹' + total.toLocaleString();
    document.getElementById('savingsAmt').textContent    = '₹' + disc.toLocaleString();
  }
  updatePricing();

  // Coupon
  const coupons = { 'WEEKEND30': 0.10, 'MONSOON50': 0.15, 'FIRST1000': 0.05 };
  function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const msg  = document.getElementById('couponMsg');
    if (coupons[code]) {
      updatePricing(coupons[code]);
      msg.innerHTML = '<span class="text-success fw-600"><i class="bi bi-check-circle-fill me-1"></i>Coupon applied! Extra ' + (coupons[code]*100) + '% off.</span>';
    } else {
      msg.innerHTML = '<span class="text-danger fw-600"><i class="bi bi-x-circle-fill me-1"></i>Invalid coupon code.</span>';
    }
  }
  document.getElementById('couponInput').addEventListener('keydown', e => { if(e.key==='Enter') applyCoupon(); });

  // GST toggle
  document.getElementById('gstToggle').addEventListener('change', function() {
    document.getElementById('gstFields').style.display = this.checked ? 'flex' : 'none';
  });

  // Proceed to guest details page
  function proceedToPayment() {
    const first = document.getElementById('rbFirstName').value.trim();
    const last  = document.getElementById('rbLastName').value.trim();
    const email = document.getElementById('rbEmail').value.trim();
    const phone = document.getElementById('rbPhone').value.trim();
    if (!first || !email || !phone) {
      alert('Please fill in First Name, Email and Mobile before proceeding.');
      return;
    }
    const p = new URLSearchParams(window.location.search);
    p.set('first', first);
    p.set('last',  last);
    p.set('email', email);
    p.set('phone', phone);
    window.location.href = 'guest-details.php?' + p.toString();
  }
</script>
<script src="search-state.js"></script>
</body>
</html>
