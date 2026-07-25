<?php
session_start();
$is_logged_in   = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? htmlspecialchars($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'User') : '';
$user_initial   = $is_logged_in ? strtoupper(substr($_SESSION['user_firstname'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)) : '';
require_once 'db.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $full_name = sanitize($input['full_name'] ?? '');
    $email     = sanitize($input['email']    ?? '');
    $phone     = sanitize($input['phone']    ?? '');
    $subject   = sanitize($input['subject']  ?? '');
    $message   = sanitize($input['message']  ?? '');
    
    if (empty($full_name) || empty($email) || empty($subject) || empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'All fields are required.']);
        exit();
    }
    
    if (!validateEmail($email)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid email address.']);
        exit();
    }
    
    $sql = "INSERT INTO contact_submissions (name, email, subject, message) 
            VALUES ('$full_name', '$email', '$subject', '$message')";
            
    if (mysqli_query($conn, $sql)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to save message. ' . mysqli_error($conn)]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us — bookHotel</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="contact.css"/>
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
        <li class="nav-item"><a class="nav-link active" href="contact.php" class="nav-link active">Contact</a></li>
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
<section class="ct-hero">
  <div class="ct-hero__overlay"></div>
  <div class="container position-relative" style="z-index:2">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb ct-breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Contact</li>
      </ol>
    </nav>
    <div class="ct-hero__content">
      <span class="ct-hero__badge"><i class="bi bi-headset me-2"></i>We're Here For You</span>
      <h1 class="ct-hero__title">Contact Us</h1>
      <p class="ct-hero__sub">We're here to help you with your hotel bookings. Reach out anytime — our team is available 24/7.</p>
    </div>
  </div>
</section>

<!-- ========== CONTACT INFO CARDS ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-info-card">
          <div class="ct-info-card__icon ct-info-card__icon--blue">
            <i class="bi bi-telephone-fill"></i>
          </div>
          <h6 class="ct-info-card__title">Call Us</h6>
          <p class="ct-info-card__val">+91 9876543210</p>
          <p class="ct-info-card__hint">Mon – Sun, 24 × 7</p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-info-card">
          <div class="ct-info-card__icon ct-info-card__icon--green">
            <i class="bi bi-envelope-fill"></i>
          </div>
          <h6 class="ct-info-card__title">Email Us</h6>
          <p class="ct-info-card__val">support@bookhotel.com</p>
          <p class="ct-info-card__hint">Reply within 2 hours</p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-info-card">
          <div class="ct-info-card__icon ct-info-card__icon--orange">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <h6 class="ct-info-card__title">Visit Us</h6>
          <p class="ct-info-card__val">Lucknow, Uttar Pradesh</p>
          <p class="ct-info-card__hint">India — 226001</p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-info-card">
          <div class="ct-info-card__icon ct-info-card__icon--purple">
            <i class="bi bi-clock-fill"></i>
          </div>
          <h6 class="ct-info-card__title">Support Hours</h6>
          <p class="ct-info-card__val">24/7 Customer Support</p>
          <p class="ct-info-card__hint">Always here for you</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== CONTACT FORM ========== -->
<section class="py-5">
  <div class="container">
    <div class="row g-5 align-items-start">

      <!-- Left: form -->
      <div class="col-12 col-lg-7">
        <div class="ct-form-card">
          <h2 class="fw-800 mb-1" style="color:#1a1a2e">Send Us a Message</h2>
          <p class="text-muted mb-4 small">Fill in the form and our team will get back to you within 2 hours.</p>

          <form id="contactForm" novalidate>
            <div class="row g-3">

              <div class="col-12 col-sm-6">
                <div class="ct-field">
                  <input type="text" class="ct-input" id="cfName" placeholder=" " required/>
                  <label class="ct-label" for="cfName"><i class="bi bi-person me-1"></i>Full Name</label>
                  <div class="ct-err">Please enter your full name.</div>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="ct-field">
                  <input type="email" class="ct-input" id="cfEmail" placeholder=" " required/>
                  <label class="ct-label" for="cfEmail"><i class="bi bi-envelope me-1"></i>Email Address</label>
                  <div class="ct-err">Please enter a valid email.</div>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="ct-field">
                  <input type="tel" class="ct-input" id="cfPhone" placeholder=" "/>
                  <label class="ct-label" for="cfPhone"><i class="bi bi-phone me-1"></i>Phone Number</label>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="ct-field">
                  <select class="ct-input ct-select" id="cfSubject" required>
                    <option value="" disabled selected></option>
                    <option>Booking Issue</option>
                    <option>Cancellation & Refund</option>
                    <option>Payment Problem</option>
                    <option>Account Assistance</option>
                    <option>General Inquiry</option>
                  </select>
                  <label class="ct-label ct-label--select" for="cfSubject"><i class="bi bi-tag me-1"></i>Subject</label>
                  <div class="ct-err">Please select a subject.</div>
                </div>
              </div>

              <div class="col-12">
                <div class="ct-field">
                  <textarea class="ct-input ct-textarea" id="cfMessage" placeholder=" " rows="5" required></textarea>
                  <label class="ct-label" for="cfMessage"><i class="bi bi-chat-left-text me-1"></i>Your Message</label>
                  <div class="ct-err">Please enter your message.</div>
                </div>
              </div>

              <div class="col-12">
                <button type="submit" class="ct-submit-btn" id="ctSubmitBtn">
                  <span class="ct-submit-btn__text"><i class="bi bi-send-fill me-2"></i>Send Message</span>
                  <span class="ct-submit-btn__loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Sending…</span>
                </button>
              </div>

            </div>
          </form>

          <!-- Success message -->
          <div class="ct-success d-none" id="ctSuccess">
            <div class="ct-success__icon"><i class="bi bi-check-circle-fill"></i></div>
            <h5 class="ct-success__title">Message Sent!</h5>
            <p class="ct-success__sub">Thanks for reaching out. Our team will get back to you within 2 hours.</p>
          </div>

        </div>
      </div>

      <!-- Right: extra info -->
      <div class="col-12 col-lg-5">
        <div class="ct-side-info">
          <h5 class="fw-700 mb-4" style="color:#1a1a2e"><i class="bi bi-info-circle-fill text-primary me-2"></i>Before You Write</h5>
          <ul class="ct-checklist">
            <li><i class="bi bi-check-circle-fill"></i>Have your Booking ID ready for faster support</li>
            <li><i class="bi bi-check-circle-fill"></i>Check our FAQ below for instant answers</li>
            <li><i class="bi bi-check-circle-fill"></i>For urgent help, call us directly at +91 9876543210</li>
            <li><i class="bi bi-check-circle-fill"></i>Refund queries are usually resolved in 5–7 business days</li>
          </ul>
          <div class="ct-response-badge">
            <i class="bi bi-lightning-charge-fill"></i>
            <div>
              <div class="fw-700 small" style="color:#1a1a2e">Average Response Time</div>
              <div class="text-muted small">Under 2 hours · 24/7 support</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== QUICK HELP ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="ct-section-badge">Quick Help</span>
      <h2 class="fw-800 mt-2" style="color:#1a1a2e">How Can We Help You?</h2>
      <p class="text-muted">Find answers to the most common questions instantly.</p>
    </div>
    <div class="row g-4">

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-help-card">
          <div class="ct-help-card__icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8">
            <i class="bi bi-calendar2-check-fill"></i>
          </div>
          <h6 class="ct-help-card__title">Booking Issues</h6>
          <p class="ct-help-card__desc">Trouble with your reservation? We'll sort it out quickly.</p>
          <a href="#faq" class="ct-help-card__link">Learn More <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-help-card">
          <div class="ct-help-card__icon" style="background:linear-gradient(135deg,#fce7f3,#fbcfe8);color:#be185d">
            <i class="bi bi-arrow-counterclockwise"></i>
          </div>
          <h6 class="ct-help-card__title">Cancellation & Refunds</h6>
          <p class="ct-help-card__desc">Understand our cancellation policy and refund timelines.</p>
          <a href="#faq" class="ct-help-card__link">Learn More <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-help-card">
          <div class="ct-help-card__icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46">
            <i class="bi bi-credit-card-fill"></i>
          </div>
          <h6 class="ct-help-card__title">Payment Problems</h6>
          <p class="ct-help-card__desc">Payment failed or charged twice? We'll fix it fast.</p>
          <a href="#faq" class="ct-help-card__link">Learn More <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="ct-help-card">
          <div class="ct-help-card__icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e">
            <i class="bi bi-person-gear"></i>
          </div>
          <h6 class="ct-help-card__title">Account Assistance</h6>
          <p class="ct-help-card__desc">Login issues, profile updates, or password resets.</p>
          <a href="#faq" class="ct-help-card__link">Learn More <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section class="py-5" id="faq">
  <div class="container">
    <div class="text-center mb-5">
      <span class="ct-section-badge">FAQ</span>
      <h2 class="fw-800 mt-2" style="color:#1a1a2e">Frequently Asked Questions</h2>
      <p class="text-muted">Quick answers to help you before you reach out.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="ct-accordion accordion" id="faqAccordion">

          <div class="ct-accordion__item accordion-item">
            <h2 class="accordion-header">
              <button class="ct-accordion__btn accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                <i class="bi bi-x-circle-fill me-3 text-danger"></i> How can I cancel a booking?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="ct-accordion__body accordion-body">
                Go to <strong>My Bookings</strong> from the navbar, find the booking you want to cancel, and click the <strong>Cancel</strong> button. Cancellations are subject to the hotel's policy. Free cancellations are available if done 24 hours before check-in.
              </div>
            </div>
          </div>

          <div class="ct-accordion__item accordion-item">
            <h2 class="accordion-header">
              <button class="ct-accordion__btn accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                <i class="bi bi-currency-rupee me-3 text-success"></i> How do refunds work?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="ct-accordion__body accordion-body">
                Once a cancellation is confirmed, refunds are processed within <strong>5–7 business days</strong> to your original payment method. UPI and wallet refunds are usually faster (1–3 days). You'll receive an email confirmation once the refund is initiated.
              </div>
            </div>
          </div>

          <div class="ct-accordion__item accordion-item">
            <h2 class="accordion-header">
              <button class="ct-accordion__btn accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                <i class="bi bi-pencil-fill me-3 text-primary"></i> Can I modify my reservation?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="ct-accordion__body accordion-body">
                Modifications such as changing check-in/check-out dates or room type depend on the hotel's availability and policy. Please contact our support team at <strong>support@bookhotel.com</strong> or call us and we'll assist you with the modification.
              </div>
            </div>
          </div>

          <div class="ct-accordion__item accordion-item">
            <h2 class="accordion-header">
              <button class="ct-accordion__btn accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                <i class="bi bi-headset me-3 text-warning"></i> How do I contact support?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="ct-accordion__body accordion-body">
                You can reach us via the contact form on this page, by emailing <strong>support@bookhotel.com</strong>, or by calling <strong>+91 9876543210</strong>. Our support team is available <strong>24/7</strong> including weekends and holidays.
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== MAP ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="ct-section-badge">Location</span>
      <h2 class="fw-800 mt-2" style="color:#1a1a2e">Find Us</h2>
      <p class="text-muted">Visit our office in Lucknow, Uttar Pradesh.</p>
    </div>
    <div class="ct-map-wrap">
      <div class="ct-map-overlay-pin">
        <div class="ct-map-pin-pulse"></div>
        <div class="ct-map-pin">
          <i class="bi bi-building-fill"></i>
        </div>
        <div class="ct-map-label">bookHotel HQ · Lucknow, UP</div>
      </div>
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d114514.36220842898!2d80.84681729570312!3d26.846693999999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x399bfd991f32b16b%3A0x93ccba8909978be7!2sLucknow%2C%20Uttar%20Pradesh!5e0!3m2!1sen!2sin!4v1720000000000!5m2!1sen!2sin"
        width="100%" height="420" style="border:0;border-radius:16px;display:block;" allowfullscreen="" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade" title="bookHotel Office Location">
      </iframe>
    </div>
  </div>
</section>

<!-- ========== TRUST STATS ========== -->
<section class="ct-trust py-5">
  <div class="ct-trust__overlay"></div>
  <div class="container position-relative" style="z-index:2">
    <div class="text-center mb-5">
      <h2 class="fw-800 text-white">Trusted by Millions</h2>
      <p class="text-white-50">Why travellers choose bookHotel</p>
    </div>
    <div class="row g-4 justify-content-center">

      <div class="col-6 col-md-3">
        <div class="ct-stat-card">
          <div class="ct-stat-card__icon"><i class="bi bi-people-fill"></i></div>
          <div class="ct-stat-card__num">1M+</div>
          <div class="ct-stat-card__label">Happy Travelers</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="ct-stat-card">
          <div class="ct-stat-card__icon"><i class="bi bi-shield-fill-check"></i></div>
          <div class="ct-stat-card__num">100%</div>
          <div class="ct-stat-card__label">Secure Payments</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="ct-stat-card">
          <div class="ct-stat-card__icon"><i class="bi bi-headset"></i></div>
          <div class="ct-stat-card__num">24/7</div>
          <div class="ct-stat-card__label">Support Available</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="ct-stat-card">
          <div class="ct-stat-card__icon"><i class="bi bi-tag-fill"></i></div>
          <div class="ct-stat-card__num">Best</div>
          <div class="ct-stat-card__label">Price Guarantee</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== CTA ========== -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="ct-cta-card">
      <div class="ct-cta-card__glow"></div>
      <div class="position-relative" style="z-index:1">
        <h2 class="fw-800 ct-cta-card__title">Need help finding your perfect stay?</h2>
        <p class="ct-cta-card__sub">Browse thousands of hotels or speak with our travel experts.</p>
        <div class="d-flex gap-3 flex-wrap justify-content-center">
          <a href="hotels.php" class="btn btn-warning fw-700 px-4 py-2">
            <i class="bi bi-search me-2"></i>Browse Hotels
          </a>
          <a href="#contactForm" class="btn btn-outline-light fw-600 px-4 py-2">
            <i class="bi bi-chat-left-dots me-2"></i>Contact Support
          </a>
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
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="#">Help Center</a></li>
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

<!-- ========== BACK TO TOP ========== -->
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

window.addEventListener('scroll', () => {
  document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
  document.getElementById('backToTop').classList.toggle('show', window.scrollY > 300);
});

const form      = document.getElementById('contactForm');
const success   = document.getElementById('ctSuccess');
const submitBtn = document.getElementById('ctSubmitBtn');

form.addEventListener('submit', async function(e) {
  e.preventDefault();
  let valid = true;

  form.querySelectorAll('.ct-input[required]').forEach(el => {
    if (!el.value.trim()) { el.closest('.ct-field').classList.add('ct-field--error'); valid = false; }
    else el.closest('.ct-field').classList.remove('ct-field--error');
  });

  const emailEl = document.getElementById('cfEmail');
  if (emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
    emailEl.closest('.ct-field').classList.add('ct-field--error'); valid = false;
  }

  if (!valid) return;

  submitBtn.querySelector('.ct-submit-btn__text').classList.add('d-none');
  submitBtn.querySelector('.ct-submit-btn__loading').classList.remove('d-none');
  submitBtn.disabled = true;

  let responseError = null;
  try {
    const response = await fetch('contact.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        full_name: document.getElementById('cfName').value.trim(),
        email:     emailEl.value.trim(),
        phone:     document.getElementById('cfPhone').value.trim() || null,
        subject:   document.getElementById('cfSubject').value.trim(),
        message:   document.getElementById('cfMessage').value.trim()
      })
    });
    const result = await response.json();
    if (result.error) {
      responseError = result.error;
    }
  } catch (err) {
    responseError = err.message;
  }

  submitBtn.querySelector('.ct-submit-btn__text').classList.remove('d-none');
  submitBtn.querySelector('.ct-submit-btn__loading').classList.add('d-none');
  submitBtn.disabled = false;

  if (responseError) {
    const errBox = document.createElement('div');
    errBox.className = 'alert alert-danger mt-3 small';
    errBox.textContent = responseError;
    form.appendChild(errBox);
    setTimeout(() => errBox.remove(), 4000);
    return;
  }

  form.classList.add('d-none');
  success.classList.remove('d-none');
});

form.querySelectorAll('.ct-input').forEach(el => {
  el.addEventListener('input', () => el.closest('.ct-field').classList.remove('ct-field--error'));
});
</script>
</body>
</html>

