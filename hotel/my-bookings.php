<?php
session_start();
require_once 'db.php';

$userName = '';
$userEmail = '';
$userAvatar = 'U';

if (isset($_SESSION['hm_id'])) {
    $manager = getCurrentHotelManager();
    if ($manager) {
        $userName = $manager['first_name'] . ' ' . $manager['last_name'];
        $userEmail = $manager['email'];
        $userAvatar = strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1));
    }
} elseif (isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT first_name, last_name, email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $u = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if ($u) {
        $userName = $u['first_name'] . ' ' . $u['last_name'];
        $userEmail = $u['email'];
        $userAvatar = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
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
          <a class="btn btn-outline-warning btn-sm px-3" href="login.php">Login / Sign Up</a>
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
          <span class="mb-stat-card__num">3</span>
          <span class="mb-stat-card__label">Total Bookings</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--upcoming">
        <div class="mb-stat-card__icon"><i class="bi bi-clock-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num">1</span>
          <span class="mb-stat-card__label">Upcoming</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--completed">
        <div class="mb-stat-card__icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num">1</span>
          <span class="mb-stat-card__label">Completed Stays</span>
        </div>
      </div>

      <div class="mb-stat-card mb-stat-card--cancelled">
        <div class="mb-stat-card__icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="mb-stat-card__body">
          <span class="mb-stat-card__num">1</span>
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
          <i class="bi bi-grid-fill"></i> All <span class="mb-tab-count">3</span>
        </button>
        <button class="mb-tab" data-filter="upcoming" role="tab" aria-selected="false">
          <i class="bi bi-clock"></i> Upcoming <span class="mb-tab-count">1</span>
        </button>
        <button class="mb-tab" data-filter="completed" role="tab" aria-selected="false">
          <i class="bi bi-check-circle"></i> Completed <span class="mb-tab-count">1</span>
        </button>
        <button class="mb-tab" data-filter="cancelled" role="tab" aria-selected="false">
          <i class="bi bi-x-circle"></i> Cancelled <span class="mb-tab-count">1</span>
        </button>
      </div>

    </div>
  </div>
</section>

<!-- ========== BOOKING CARDS ========== -->
<section class="mb-list py-4">
  <div class="container">
    <div class="mb-cards" id="bookingCards">

      <!-- ── CARD 1 : UPCOMING ── -->
      <article class="mb-card" data-status="upcoming" data-hotel="The Grand Palace" data-id="BKH2024001">
        <div class="mb-card__img-wrap">
          <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=480&q=80"
               alt="The Grand Palace" class="mb-card__img" loading="lazy"/>
          <span class="mb-badge mb-badge--upcoming">
            <i class="bi bi-check-circle-fill me-1"></i>Confirmed
          </span>
        </div>
        <div class="mb-card__body">
          <div class="mb-card__top">
            <div>
              <h3 class="mb-card__hotel">The Grand Palace</h3>
              <p class="mb-card__loc"><i class="bi bi-geo-alt-fill"></i>Connaught Place, New Delhi</p>
            </div>
            <div class="mb-card__id-wrap">
              <span class="mb-card__id-label">Booking ID</span>
              <span class="mb-card__id">#BKH2024001</span>
              <span class="mb-card__booked-on"><i class="bi bi-calendar-event"></i> Booked On: 25 Jun 2026</span>
            </div>
          </div>

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
            <div class="mb-timeline__line"></div>
            <div class="mb-timeline__step mb-timeline__step--active">
              <div class="mb-timeline__icon"><i class="bi bi-clock-fill"></i></div>
              <span class="mb-timeline__label">Check-in Pending</span>
            </div>
          </div>

          <!-- Payment Status -->
          <div class="mb-payment-status">
            <i class="bi bi-credit-card-fill"></i>
            <span class="mb-payment-status__label">Payment Status:</span>
            <span class="mb-payment-status__badge mb-payment-status__badge--paid">Paid ✓</span>
          </div>

          <div class="mb-card__meta">
            <div class="mb-meta-item">
              <i class="bi bi-calendar-check-fill"></i>
              <div><span class="mb-meta-label">Check-in</span><span class="mb-meta-val">Sat, 12 Jul 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-calendar-x-fill"></i>
              <div><span class="mb-meta-label">Check-out</span><span class="mb-meta-val">Mon, 14 Jul 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-people-fill"></i>
              <div><span class="mb-meta-label">Guests</span><span class="mb-meta-val">2 Adults, 1 Child</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-door-open-fill"></i>
              <div><span class="mb-meta-label">Room</span><span class="mb-meta-val">Deluxe King Room</span></div>
            </div>
          </div>

          <div class="mb-card__footer">
            <div class="mb-card__price-wrap">
              <span class="mb-card__price-label">Total Amount</span>
              <span class="mb-card__price">₹8,598</span>
              <span class="mb-card__nights">· 2 nights</span>
            </div>
            <div class="mb-card__actions">
              <button class="mb-btn mb-btn--ghost" onclick="openDetailsModal(this.closest('.mb-card'))">
                <i class="bi bi-eye-fill"></i> View Details
              </button>
              <button class="mb-btn mb-btn--outline" onclick="showToastMsg('Invoice downloading…','success')">
                <i class="bi bi-download"></i> Invoice
              </button>
              <button class="mb-btn mb-btn--danger" onclick="confirmCancel(this)">
                <i class="bi bi-x-circle"></i> Cancel
              </button>
            </div>
          </div>
        </div>
      </article>

      <!-- ── CARD 2 : COMPLETED ── -->
      <article class="mb-card" data-status="completed" data-hotel="Sunset Beach Resort" data-id="BKH2024002">
        <div class="mb-card__img-wrap">
          <img src="https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=480&q=80"
               alt="Sunset Beach Resort" class="mb-card__img" loading="lazy"/>
          <span class="mb-badge mb-badge--completed">
            <i class="bi bi-patch-check-fill me-1"></i>Completed
          </span>
        </div>
        <div class="mb-card__body">
          <div class="mb-card__top">
            <div>
              <h3 class="mb-card__hotel">Sunset Beach Resort</h3>
              <p class="mb-card__loc"><i class="bi bi-geo-alt-fill"></i>Calangute, Goa</p>
            </div>
            <div class="mb-card__id-wrap">
              <span class="mb-card__id-label">Booking ID</span>
              <span class="mb-card__id">#BKH2024002</span>
              <span class="mb-card__booked-on"><i class="bi bi-calendar-event"></i> Booked On: 20 Feb 2025</span>
            </div>
          </div>

          <!-- Payment Status -->
          <div class="mb-payment-status">
            <i class="bi bi-credit-card-fill"></i>
            <span class="mb-payment-status__label">Payment Status:</span>
            <span class="mb-payment-status__badge mb-payment-status__badge--paid">Paid ✓</span>
          </div>

          <div class="mb-card__meta">
            <div class="mb-meta-item">
              <i class="bi bi-calendar-check-fill"></i>
              <div><span class="mb-meta-label">Check-in</span><span class="mb-meta-val">Fri, 14 Mar 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-calendar-x-fill"></i>
              <div><span class="mb-meta-label">Check-out</span><span class="mb-meta-val">Mon, 17 Mar 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-people-fill"></i>
              <div><span class="mb-meta-label">Guests</span><span class="mb-meta-val">2 Adults</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-door-open-fill"></i>
              <div><span class="mb-meta-label">Room</span><span class="mb-meta-val">Ocean View Suite</span></div>
            </div>
          </div>

          <!-- Rating prompt -->
          <div class="mb-rating-prompt">
            <span class="mb-rating-label"><i class="bi bi-star-fill text-warning me-1"></i>How was your stay?</span>
            <div class="mb-stars" id="starsCard2" data-id="2">
              <button class="mb-star" data-val="1" aria-label="1 star"><i class="bi bi-star"></i></button>
              <button class="mb-star" data-val="2" aria-label="2 stars"><i class="bi bi-star"></i></button>
              <button class="mb-star" data-val="3" aria-label="3 stars"><i class="bi bi-star"></i></button>
              <button class="mb-star" data-val="4" aria-label="4 stars"><i class="bi bi-star"></i></button>
              <button class="mb-star" data-val="5" aria-label="5 stars"><i class="bi bi-star"></i></button>
            </div>
          </div>

          <div class="mb-card__footer">
            <div class="mb-card__price-wrap">
              <span class="mb-card__price-label">Total Paid</span>
              <span class="mb-card__price">₹16,497</span>
              <span class="mb-card__nights">· 3 nights</span>
            </div>
            <div class="mb-card__actions">
              <button class="mb-btn mb-btn--ghost" onclick="openDetailsModal(this.closest('.mb-card'))">
                <i class="bi bi-eye-fill"></i> View Details
              </button>
              <a href="hotels.php" class="mb-btn mb-btn--primary">
                <i class="bi bi-arrow-repeat"></i> Book Again
              </a>
            </div>
          </div>
        </div>
      </article>

      <!-- ── CARD 3 : CANCELLED ── -->
      <article class="mb-card" data-status="cancelled" data-hotel="Heritage Haveli" data-id="BKH2024003">
        <div class="mb-card__img-wrap">
          <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=480&q=80"
               alt="Heritage Haveli" class="mb-card__img" loading="lazy"/>
          <span class="mb-badge mb-badge--cancelled">
            <i class="bi bi-x-circle-fill me-1"></i>Cancelled
          </span>
          <div class="mb-card__cancelled-overlay" aria-hidden="true"></div>
        </div>
        <div class="mb-card__body mb-card__body--cancelled">
          <div class="mb-card__top">
            <div>
              <h3 class="mb-card__hotel">Heritage Haveli</h3>
              <p class="mb-card__loc"><i class="bi bi-geo-alt-fill"></i>Civil Lines, Jaipur</p>
            </div>
            <div class="mb-card__id-wrap">
              <span class="mb-card__id-label">Booking ID</span>
              <span class="mb-card__id">#BKH2024003</span>
              <span class="mb-card__booked-on"><i class="bi bi-calendar-event"></i> Booked On: 02 Jan 2025</span>
            </div>
          </div>

          <!-- Payment Status -->
          <div class="mb-payment-status">
            <i class="bi bi-credit-card-fill"></i>
            <span class="mb-payment-status__label">Payment Status:</span>
            <span class="mb-payment-status__badge mb-payment-status__badge--refund">Refund Initiated</span>
          </div>

          <div class="mb-card__meta">
            <div class="mb-meta-item">
              <i class="bi bi-calendar-check-fill"></i>
              <div><span class="mb-meta-label">Check-in</span><span class="mb-meta-val">Wed, 22 Jan 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-calendar-x-fill"></i>
              <div><span class="mb-meta-label">Check-out</span><span class="mb-meta-val">Fri, 24 Jan 2025</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-people-fill"></i>
              <div><span class="mb-meta-label">Guests</span><span class="mb-meta-val">2 Adults</span></div>
            </div>
            <div class="mb-meta-sep"></div>
            <div class="mb-meta-item">
              <i class="bi bi-door-open-fill"></i>
              <div><span class="mb-meta-label">Room</span><span class="mb-meta-val">Royal Heritage Suite</span></div>
            </div>
          </div>

          <div class="mb-cancel-reason">
            <i class="bi bi-info-circle-fill"></i>
            Cancelled by guest on 10 Jan 2025 · Refund of ₹7,020 processed
          </div>

          <div class="mb-card__footer">
            <div class="mb-card__price-wrap">
              <span class="mb-card__price-label">Booking Amount</span>
              <span class="mb-card__price mb-card__price--struck">₹9,360</span>
              <span class="mb-card__nights">· 2 nights</span>
            </div>
            <div class="mb-card__actions">
              <button class="mb-btn mb-btn--ghost" onclick="openDetailsModal(this.closest('.mb-card'))">
                <i class="bi bi-eye-fill"></i> View Details
              </button>
              <a href="hotels.php" class="mb-btn mb-btn--primary">
                <i class="bi bi-arrow-repeat"></i> Book Again
              </a>
            </div>
          </div>
        </div>
      </article>

    </div><!-- /#bookingCards -->

    <!-- ── EMPTY STATE ── -->
    <div class="mb-empty d-none" id="emptyState">
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
<script src="navbar.js"></script>
<script src="my-bookings.js"></script>
</body>
</html>

