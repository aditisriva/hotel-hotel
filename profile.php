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
  <title>My Profile — bookHotel</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="profile.css"/>
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
<section class="pf-hero">
  <div class="pf-hero__overlay"></div>
  <div class="container position-relative" style="z-index:2">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb pf-breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">My Profile</li>
      </ol>
    </nav>
    <div class="pf-hero__inner">
      <!-- Avatar -->
      <div class="pf-hero__avatar-wrap">
        <div class="pf-hero__avatar" id="heroAvatar">AD</div>
        <button class="pf-hero__avatar-edit" aria-label="Change photo" title="Change photo">
          <i class="bi bi-camera-fill"></i>
        </button>
      </div>
      <!-- Info -->
      <div class="pf-hero__info">
        <div class="pf-hero__name" id="heroName">Aditi</div>
        <div class="pf-hero__email" id="heroEmail">aditi@bookhotel.com</div>
        <div class="pf-hero__meta">
          <span><i class="bi bi-calendar3"></i> Member Since June 2024</span>
          <span class="pf-hero__sep"></span>
          <span><i class="bi bi-patch-check-fill text-warning"></i> Verified Member</span>
        </div>
      </div>
      <!-- Completion badge -->
      <div class="pf-hero__completion">
        <div class="pf-completion-ring" data-pct="80">
          <svg viewBox="0 0 80 80" class="pf-ring-svg">
            <circle cx="40" cy="40" r="33" class="pf-ring-bg"/>
            <circle cx="40" cy="40" r="33" class="pf-ring-fill" id="ringFill"/>
          </svg>
          <div class="pf-ring-label">
            <span class="pf-ring-pct">80%</span>
            <span class="pf-ring-txt">Complete</span>
          </div>
        </div>
        <div class="pf-completion-hint">Complete your profile for better recommendations</div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TRAVEL STATS ========== -->
<section class="pf-stats bg-white py-4 border-bottom">
  <div class="container">
    <div class="pf-stats__grid">

      <div class="pf-stat-card pf-stat-card--blue">
        <div class="pf-stat-card__icon"><i class="bi bi-calendar2-check-fill"></i></div>
        <div class="pf-stat-card__body">
          <span class="pf-stat-card__num">12</span>
          <span class="pf-stat-card__label">Total Bookings</span>
        </div>
      </div>

      <div class="pf-stat-card pf-stat-card--green">
        <div class="pf-stat-card__icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="pf-stat-card__body">
          <span class="pf-stat-card__num">9</span>
          <span class="pf-stat-card__label">Completed Trips</span>
        </div>
      </div>

      <div class="pf-stat-card pf-stat-card--red">
        <div class="pf-stat-card__icon"><i class="bi bi-heart-fill"></i></div>
        <div class="pf-stat-card__body">
          <span class="pf-stat-card__num">5</span>
          <span class="pf-stat-card__label">Wishlist Hotels</span>
        </div>
      </div>

      <div class="pf-stat-card pf-stat-card--gold">
        <div class="pf-stat-card__icon"><i class="bi bi-currency-rupee"></i></div>
        <div class="pf-stat-card__body">
          <span class="pf-stat-card__num">₹82K</span>
          <span class="pf-stat-card__label">Total Amount Spent</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== MAIN LAYOUT ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">

      <!-- ===== LEFT COLUMN ===== -->
      <div class="col-12 col-lg-8">

        <!-- PROFILE INFORMATION -->
        <div class="pf-card mb-4">
          <div class="pf-card__head">
            <div>
              <h5 class="pf-card__title"><i class="bi bi-person-fill me-2 text-primary"></i>Profile Information</h5>
              <p class="pf-card__sub">Keep your details up to date for a smoother booking experience.</p>
            </div>
            <button class="pf-btn pf-btn--outline" id="editBtn" onclick="toggleEdit()">
              <i class="bi bi-pencil-fill"></i> Edit Profile
            </button>
          </div>

          <!-- Progress Bar -->
          <div class="pf-progress-wrap mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="pf-progress-label"><i class="bi bi-bar-chart-fill me-1 text-primary"></i>Profile Completion</span>
              <span class="pf-progress-pct">80%</span>
            </div>
            <div class="pf-progress-bar">
              <div class="pf-progress-fill" style="width:80%"></div>
            </div>
            <div class="pf-progress-hint">Add your Date of Birth to reach 100%</div>
          </div>

          <form id="profileForm">
            <div class="row g-3">

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfName"><i class="bi bi-person me-1"></i>Full Name</label>
                  <input type="text" class="pf-input" id="pfName" value="Aditi Sharma" disabled/>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfEmail"><i class="bi bi-envelope me-1"></i>Email Address</label>
                  <input type="email" class="pf-input" id="pfEmail" value="aditi@bookhotel.com" disabled/>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfPhone"><i class="bi bi-phone me-1"></i>Phone Number</label>
                  <input type="tel" class="pf-input" id="pfPhone" value="+91 9876543210" disabled/>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfGender"><i class="bi bi-gender-ambiguous me-1"></i>Gender</label>
                  <select class="pf-input pf-select" id="pfGender" disabled>
                    <option>Female</option>
                    <option>Male</option>
                    <option>Other</option>
                    <option>Prefer not to say</option>
                  </select>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfDob"><i class="bi bi-cake me-1"></i>Date of Birth</label>
                  <input type="date" class="pf-input" id="pfDob" value="" disabled placeholder="Not set"/>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="pf-field">
                  <label class="pf-label" for="pfCity"><i class="bi bi-geo-alt me-1"></i>City</label>
                  <input type="text" class="pf-input" id="pfCity" value="Lucknow" disabled/>
                </div>
              </div>

            </div>

            <div class="pf-form-actions d-none" id="formActions">
              <button type="button" class="pf-btn pf-btn--ghost" onclick="cancelEdit()">
                <i class="bi bi-x-lg"></i> Cancel
              </button>
              <button type="submit" class="pf-btn pf-btn--primary">
                <i class="bi bi-check-lg"></i> Save Changes
              </button>
            </div>
          </form>

          <!-- Save success toast (inline) -->
          <div class="pf-save-success d-none" id="saveSuccess">
            <i class="bi bi-check-circle-fill"></i> Profile updated successfully!
          </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="pf-card">
          <div class="pf-card__head mb-3">
            <h5 class="pf-card__title"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</h5>
          </div>
          <div class="pf-activity-list">

            <div class="pf-activity-item">
              <div class="pf-activity-icon pf-activity-icon--blue">
                <i class="bi bi-calendar2-check-fill"></i>
              </div>
              <div class="pf-activity-body">
                <div class="pf-activity-title">Latest Booking</div>
                <div class="pf-activity-desc">The Grand Palace, New Delhi &nbsp;·&nbsp; <span class="pf-activity-badge pf-activity-badge--green">Confirmed</span></div>
                <div class="pf-activity-time"><i class="bi bi-clock me-1"></i>25 Jun 2026 · Booking ID #BKH2024001</div>
              </div>
              <a href="my-bookings.php" class="pf-activity-link">View <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="pf-activity-item">
              <div class="pf-activity-icon pf-activity-icon--green">
                <i class="bi bi-box-arrow-in-right"></i>
              </div>
              <div class="pf-activity-body">
                <div class="pf-activity-title">Last Login</div>
                <div class="pf-activity-desc">Logged in from Chrome on Windows</div>
                <div class="pf-activity-time"><i class="bi bi-clock me-1"></i>Today, 5:30 PM · Lucknow, India</div>
              </div>
            </div>

            <div class="pf-activity-item">
              <div class="pf-activity-icon pf-activity-icon--gold">
                <i class="bi bi-eye-fill"></i>
              </div>
              <div class="pf-activity-body">
                <div class="pf-activity-title">Recently Viewed Hotel</div>
                <div class="pf-activity-desc">Sunset Beach Resort, Goa &nbsp;·&nbsp; <span class="pf-activity-rating">★ 4.6</span></div>
                <div class="pf-activity-time"><i class="bi bi-clock me-1"></i>Yesterday, 3:12 PM</div>
              </div>
              <a href="hotel-details.php" class="pf-activity-link">View <i class="bi bi-arrow-right"></i></a>
            </div>

          </div>
        </div>

      </div>

      <!-- ===== RIGHT COLUMN ===== -->
      <div class="col-12 col-lg-4">

        <!-- ACCOUNT SETTINGS -->
        <div class="pf-card mb-4">
          <div class="pf-card__head mb-3">
            <h5 class="pf-card__title"><i class="bi bi-gear-fill me-2 text-primary"></i>Account Settings</h5>
          </div>
          <div class="pf-settings-list">

            <button class="pf-setting-item" data-bs-toggle="modal" data-bs-target="#changePwModal">
              <div class="pf-setting-icon pf-setting-icon--blue"><i class="bi bi-lock-fill"></i></div>
              <div class="pf-setting-body">
                <div class="pf-setting-title">Change Password</div>
                <div class="pf-setting-sub">Last changed 3 months ago</div>
              </div>
              <i class="bi bi-chevron-right pf-setting-arrow"></i>
            </button>

            <button class="pf-setting-item" data-bs-toggle="modal" data-bs-target="#notifModal">
              <div class="pf-setting-icon pf-setting-icon--green"><i class="bi bi-bell-fill"></i></div>
              <div class="pf-setting-body">
                <div class="pf-setting-title">Notification Preferences</div>
                <div class="pf-setting-sub">Email &amp; push alerts enabled</div>
              </div>
              <i class="bi bi-chevron-right pf-setting-arrow"></i>
            </button>

            <button class="pf-setting-item" data-bs-toggle="modal" data-bs-target="#privacyModal">
              <div class="pf-setting-icon pf-setting-icon--purple"><i class="bi bi-shield-lock-fill"></i></div>
              <div class="pf-setting-body">
                <div class="pf-setting-title">Privacy Settings</div>
                <div class="pf-setting-sub">Manage data &amp; visibility</div>
              </div>
              <i class="bi bi-chevron-right pf-setting-arrow"></i>
            </button>

          </div>
        </div>

        <!-- QUICK LINKS -->
        <div class="pf-card">
          <div class="pf-card__head mb-3">
            <h5 class="pf-card__title"><i class="bi bi-lightning-fill me-2 text-warning"></i>Quick Links</h5>
          </div>
          <div class="d-flex flex-column gap-2">
            <a href="my-bookings.php" class="pf-quick-link">
              <i class="bi bi-calendar2-check text-primary"></i> My Bookings
              <i class="bi bi-arrow-right ms-auto"></i>
            </a>
            <a href="hotels.php" class="pf-quick-link">
              <i class="bi bi-search text-success"></i> Browse Hotels
              <i class="bi bi-arrow-right ms-auto"></i>
            </a>
            <a href="contact.php" class="pf-quick-link">
              <i class="bi bi-headset text-warning"></i> Contact Support
              <i class="bi bi-arrow-right ms-auto"></i>
            </a>
            <button class="pf-quick-link pf-quick-link--danger" id="logoutBtn">
              <i class="bi bi-box-arrow-right"></i> Sign Out
              <i class="bi bi-arrow-right ms-auto"></i>
            </button>
          </div>
        </div>

      </div>
    </div><!-- /row -->
  </div>
</section>

<!-- ========== CHANGE PASSWORD MODAL ========== -->
<div class="modal fade" id="changePwModal" tabindex="-1" aria-labelledby="changePwLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content pf-modal">
      <div class="modal-header pf-modal__header">
        <div class="d-flex align-items-center gap-2">
          <div class="pf-modal__icon"><i class="bi bi-lock-fill"></i></div>
          <h5 class="modal-title fw-800 text-white" id="changePwLabel">Change Password</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="pf-field mb-3">
          <label class="pf-label" for="pwCurrent"><i class="bi bi-lock me-1"></i>Current Password</label>
          <input type="password" class="pf-input" id="pwCurrent" placeholder="Enter current password"/>
        </div>
        <div class="pf-field mb-3">
          <label class="pf-label" for="pwNew"><i class="bi bi-lock-fill me-1"></i>New Password</label>
          <input type="password" class="pf-input" id="pwNew" placeholder="At least 8 characters"/>
        </div>
        <div class="pf-field">
          <label class="pf-label" for="pwConfirm"><i class="bi bi-check-circle me-1"></i>Confirm New Password</label>
          <input type="password" class="pf-input" id="pwConfirm" placeholder="Repeat new password"/>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4">
        <button class="pf-btn pf-btn--ghost" data-bs-dismiss="modal">Cancel</button>
        <button class="pf-btn pf-btn--primary" onclick="showPwSuccess()">Update Password</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== NOTIFICATION MODAL ========== -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content pf-modal">
      <div class="modal-header pf-modal__header">
        <div class="d-flex align-items-center gap-2">
          <div class="pf-modal__icon pf-modal__icon--green"><i class="bi bi-bell-fill"></i></div>
          <h5 class="modal-title fw-800 text-white" id="notifLabel">Notification Preferences</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Booking Confirmations</div><div class="pf-toggle-sub">Get notified when a booking is confirmed</div></div>
          <div class="pf-toggle"><input type="checkbox" id="nt1" checked/><label for="nt1"></label></div>
        </div>
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Promotional Offers</div><div class="pf-toggle-sub">Exclusive deals and hotel discounts</div></div>
          <div class="pf-toggle"><input type="checkbox" id="nt2" checked/><label for="nt2"></label></div>
        </div>
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Price Alerts</div><div class="pf-toggle-sub">Notify when wishlist hotel prices drop</div></div>
          <div class="pf-toggle"><input type="checkbox" id="nt3"/><label for="nt3"></label></div>
        </div>
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Trip Reminders</div><div class="pf-toggle-sub">Check-in reminders 24 hours before</div></div>
          <div class="pf-toggle"><input type="checkbox" id="nt4" checked/><label for="nt4"></label></div>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4">
        <button class="pf-btn pf-btn--ghost" data-bs-dismiss="modal">Close</button>
        <button class="pf-btn pf-btn--primary" data-bs-dismiss="modal">Save Preferences</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== PRIVACY MODAL ========== -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content pf-modal">
      <div class="modal-header pf-modal__header">
        <div class="d-flex align-items-center gap-2">
          <div class="pf-modal__icon pf-modal__icon--purple"><i class="bi bi-shield-lock-fill"></i></div>
          <h5 class="modal-title fw-800 text-white" id="privacyLabel">Privacy Settings</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Profile Visibility</div><div class="pf-toggle-sub">Allow others to see your travel reviews</div></div>
          <div class="pf-toggle"><input type="checkbox" id="pv1" checked/><label for="pv1"></label></div>
        </div>
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Data Analytics</div><div class="pf-toggle-sub">Help us improve with anonymous usage data</div></div>
          <div class="pf-toggle"><input type="checkbox" id="pv2" checked/><label for="pv2"></label></div>
        </div>
        <div class="pf-toggle-row">
          <div><div class="pf-toggle-title">Two-Factor Authentication</div><div class="pf-toggle-sub">Add extra security to your account</div></div>
          <div class="pf-toggle"><input type="checkbox" id="pv3"/><label for="pv3"></label></div>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4">
        <button class="pf-btn pf-btn--ghost" data-bs-dismiss="modal">Close</button>
        <button class="pf-btn pf-btn--primary" data-bs-dismiss="modal">Save Settings</button>
      </div>
    </div>
  </div>
</div>

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
          <li><a href="my-bookings.php">My Bookings</a></li><li><a href="#">Car Rentals</a></li>
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

// Populate hero from localStorage
(function() {
  try {
    const u = JSON.parse(localStorage.getItem('bh_user') || '{}');
    if (u.name) {
      document.getElementById('heroName').textContent = u.name;
      document.getElementById('heroAvatar').textContent = u.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
      document.getElementById('pfName').value = u.name;
    }
    if (u.email) {
      document.getElementById('heroEmail').textContent = u.email;
      document.getElementById('pfEmail').value = u.email;
    }
  } catch(_) {}
})();

// Animate ring on load
document.addEventListener('DOMContentLoaded', () => {
  const ring = document.getElementById('ringFill');
  if (ring) {
    const r = 33, circ = 2 * Math.PI * r;
    ring.style.strokeDasharray = circ;
    ring.style.strokeDashoffset = circ;
    setTimeout(() => {
      ring.style.strokeDashoffset = circ - (circ * 0.80);
    }, 300);
  }
});

// Edit / cancel profile
function toggleEdit() {
  document.querySelectorAll('.pf-input').forEach(el => el.removeAttribute('disabled'));
  document.getElementById('formActions').classList.remove('d-none');
  document.getElementById('editBtn').classList.add('d-none');
  document.getElementById('saveSuccess').classList.add('d-none');
}
function cancelEdit() {
  document.querySelectorAll('.pf-input').forEach(el => el.setAttribute('disabled', ''));
  document.getElementById('formActions').classList.add('d-none');
  document.getElementById('editBtn').classList.remove('d-none');
}

// Save profile
document.getElementById('profileForm').addEventListener('submit', function(e) {
  e.preventDefault();
  cancelEdit();
  const s = document.getElementById('saveSuccess');
  s.classList.remove('d-none');
  setTimeout(() => s.classList.add('d-none'), 3500);
});

// Change password success
function showPwSuccess() {
  bootstrap.Modal.getInstance(document.getElementById('changePwModal')).hide();
  showToast('Password updated successfully!', 'success');
}

// Simple toast
function showToast(msg, type) {
  const wrap = document.createElement('div');
  wrap.style.cssText = 'position:fixed;bottom:5rem;right:1.5rem;z-index:9999';
  wrap.innerHTML = `<div style="background:${type==='success'?'#064e3b':'#7f1d1d'};color:${type==='success'?'#a7f3d0':'#fecaca'};padding:.8rem 1.2rem;border-radius:10px;font-family:Inter,sans-serif;font-size:.84rem;font-weight:500;box-shadow:0 4px 20px rgba(0,0,0,.18);display:flex;align-items:center;gap:.6rem"><i class="bi bi-check-circle-fill"></i>${msg}</div>`;
  document.body.appendChild(wrap);
  setTimeout(() => wrap.remove(), 3200);
}

// Logout
document.getElementById('logoutBtn')?.addEventListener('click', () => {
  localStorage.removeItem('bh_user');
  window.location.href = 'login.php';
});
</script>
</body>
</html>

