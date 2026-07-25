<?php
/**
 * Login Page — bookHotel Authentication
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = '';
$email_val = '';

// Handle registered success query parameter
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = 'Account created successfully! Please sign in.';
}

// Handle Remember Me cookie
if (isset($_COOKIE['remember_email'])) {
    $email_val = htmlspecialchars($_COOKIE['remember_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier  = trim($_POST['identifier'] ?? '');
    $password    = trim($_POST['password']   ?? '');
    $remember_me = isset($_POST['remember_me']);

    // Basic validation
    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Support login by email OR mobile
        $identifier_safe = sanitize($identifier);
        $sql = "SELECT id, first_name, last_name, email, password, status, role
                FROM users
                WHERE email = '$identifier_safe' OR mobile = '$identifier_safe'
                LIMIT 1";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if ($user['status'] !== 'active') {
                $error = 'Your account has been suspended. Please contact support.';
            } elseif (password_verify($password, $user['password'])) {
                // Success
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['user_name']      = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email']     = $user['email'];
                $_SESSION['user_firstname'] = $user['first_name'];
                $_SESSION['role']           = $user['role'];

                // Update last login
                $uid = (int)$user['id'];
                mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = $uid");

                // Remember Me cookie (30 days)
                if ($remember_me) {
                    setcookie('remember_email', $user['email'], time() + (30 * 24 * 60 * 60), '/');
                } else {
                    setcookie('remember_email', '', time() - 3600, '/');
                }

                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email/mobile or password.';
            }
        } else {
            $error = 'Invalid email/mobile or password.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231a56db'/%3E%3Ctext x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='18' font-family='system-ui' fill='%23f59e0b'%3E&#x1F3E8;%3C/text%3E%3C/svg%3E"/>
  <title>Sign In - bookHotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous"/>
  <link rel="stylesheet" href="auth.css"/>
</head>
<body class="auth-body">

<!-- 🌪️ PAGE TRANSITION OVERLAY 🌪️ -->
<div class="page-overlay" id="pageOverlay"></div>

<!-- 🔔 TOAST NOTIFICATION 🔔 -->
<div class="toast-container" id="toastContainer"></div>

<div class="auth-wrapper" id="authWrapper">

  <!-- 🎨
       LEFT  -  Illustration Panel
  🎨 -->
  <aside class="auth-left" aria-hidden="true">

    <!-- floating blobs -->
    <div class="blob blob--1"></div>
    <div class="blob blob--2"></div>
    <div class="blob blob--3"></div>

    <div class="auth-left-inner">

      <!-- Brand -->
      <a href="index.php" class="brand-logo">
        <div class="brand-icon"><i class="bi bi-building-fill"></i></div>
        <span class="brand-name">bookHotel</span>
      </a>

      <!-- Hero image -->
      <div class="illus-wrap">
        <div class="illus-glow"></div>
        <div class="illus-card">
          <img
            src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=620&q=85"
            alt="Luxury Hotel Lobby"
            class="illus-img"
            loading="lazy"
          />
          <!-- floating chips -->
          <div class="chip chip--tr float-anim">
            <div class="chip-icon chip-icon--star"><i class="bi bi-star-fill"></i></div>
            <div>
              <div class="chip-title">5-Star Luxury</div>
              <div class="chip-sub">Top Rated Properties</div>
            </div>
          </div>

          <div class="chip chip--br float-anim float-anim--delay">
            <div class="chip-icon chip-icon--shield"><i class="bi bi-shield-check-fill"></i></div>
            <div>
              <div class="chip-title">Secure Booking</div>
              <div class="chip-sub">256-bit SSL encrypted</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Copy -->
      <div class="left-copy">
        <h2 class="left-heading">Welcome Back,<br/>Explorer <span class="wave">👋</span></h2>
        <p class="left-sub">Pick up where you left off - your dream stay is just a sign-in away.</p>

        <!-- Trust indicators -->
        <ul class="trust-list">
          <li class="trust-item">
            <span class="trust-icon"><i class="bi bi-lock-fill"></i></span>
            <span>Secure Login with end-to-end encryption</span>
          </li>
          <li class="trust-item">
            <span class="trust-icon"><i class="bi bi-tag-fill"></i></span>
            <span>Exclusive member deals up to 50% off</span>
          </li>
          <li class="trust-item">
            <span class="trust-icon"><i class="bi bi-lightning-charge-fill"></i></span>
            <span>One-tap booking across 1M+ properties</span>
          </li>
        </ul>

        <!-- Stats pill -->
        <div class="stats-pill">
          <div class="stat-block">
            <span class="stat-n">10M+</span>
            <span class="stat-l">Travellers</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-block">
            <span class="stat-n">1M+</span>
            <span class="stat-l">Hotels</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-block">
            <span class="stat-n">200+</span>
            <span class="stat-l">Countries</span>
          </div>
        </div>
      </div>

    </div>
  </aside>

  <!-- 📝
       RIGHT  -  Form Panel
  📝 -->
  <main class="auth-right">
    <div class="auth-form-wrap">

      <!-- Mobile brand -->
      <a href="index.php" class="brand-logo brand-logo--mobile">
        <div class="brand-icon"><i class="bi bi-building-fill"></i></div>
        <span class="brand-name">bookHotel</span>
      </a>

      <!-- Header -->
      <div class="form-head">
        <h1 class="form-title">Sign in to your account</h1>
        <p class="form-sub">New here? <a href="signup.php" class="inline-link" id="goSignup">Create a free account</a></p>
      </div>

      <!-- Alert area -->
      <div class="alert-box alert-box--error <?= $error ? '' : 'd-none' ?>" id="alertError" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span id="alertErrorMsg"><?= htmlspecialchars($error) ?></span>
      </div>
      <div class="alert-box alert-box--success <?= $success ? '' : 'd-none' ?>" id="alertSuccess" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= htmlspecialchars($success) ?></span>
      </div>

      <!-- Form -->
      <form id="loginForm" method="POST" action="login.php" novalidate autocomplete="on">

        <!-- Email / Mobile -->
        <div class="fl-group" id="fg-identifier">
          <div class="fl-wrap">
            <span class="fl-icon"><i class="bi bi-person-fill"></i></span>
            <input
              type="text"
              id="identifier"
              name="identifier"
              class="fl-input"
              placeholder=" "
              autocomplete="username"
              value="<?= htmlspecialchars($_POST['identifier'] ?? $email_val) ?>"
            />
            <label class="fl-label" for="identifier">Email or Mobile Number</label>
            <span class="fl-status" id="identifierStatus"></span>
          </div>
          <span class="fl-err" id="identifierErr" role="alert"></span>
        </div>

        <!-- Password -->
        <div class="fl-group" id="fg-password">
          <div class="fl-label-row">
            <label class="fl-label-top" for="password">Password</label>
            <a href="#" class="forgot-link" id="forgotLink">Forgot password?</a>
          </div>
          <div class="fl-wrap">
            <span class="fl-icon"><i class="bi bi-lock-fill"></i></span>
            <input
              type="password"
              id="password"
              name="password"
              class="fl-input"
              placeholder=" "
              autocomplete="current-password"
            />
            <label class="fl-label" for="password">Enter your password</label>
            <button type="button" class="eye-btn" id="togglePwd" aria-label="Toggle password visibility">
              <i class="bi bi-eye-fill" id="eyeIcon"></i>
            </button>
          </div>
          <span class="fl-err" id="passwordErr" role="alert"></span>
        </div>

        <!-- Remember me -->
        <div class="check-row">
          <label class="custom-check">
            <input type="checkbox" id="rememberMe" name="remember_me" <?= isset($_POST['remember_me']) || (!empty($email_val) && !isset($_POST['identifier'])) ? 'checked' : '' ?>/>
            <span class="check-box"></span>
            <span class="check-txt">Remember me for 30 days</span>
          </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit" id="loginBtn">
          <span class="btn-label" id="loginBtnLabel">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
          </span>
          <span class="btn-loader d-none" id="loginBtnLoader" aria-hidden="true">
            <span class="spinner"></span> Signing in.
          </span>
        </button>

        <!-- Divider -->
        <div class="or-divider"><span>or continue with</span></div>

        <!-- Social -->
        <div class="social-row">
          <button type="button" class="btn-social" id="googleBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Continue with Google
          </button>
        </div>

        <!-- Switch -->
        <p class="switch-txt">
          Don't have an account? <a href="signup.php" class="switch-link" id="switchToSignup">Sign Up</a>
        </p>

      </form>
    </div>
  </main>

</div><!-- /.auth-wrapper -->

<!-- Forgot password modal -->
<div class="modal-backdrop" id="forgotModal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
  <div class="modal-card">
    <button class="modal-close" id="closeModal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    <div class="modal-icon"><i class="bi bi-envelope-open-fill"></i></div>
    <h3 class="modal-title" id="forgotTitle">Reset your password</h3>
    <p class="modal-sub">Enter your registered email and we'll send you a reset link.</p>
    <div class="fl-group mt-3">
      <div class="fl-wrap">
        <span class="fl-icon"><i class="bi bi-envelope-fill"></i></span>
        <input type="email" id="resetEmail" class="fl-input" placeholder=" "/>
        <label class="fl-label" for="resetEmail">Email Address</label>
      </div>
      <span class="fl-err" id="resetErr" role="alert"></span>
    </div>
    <button class="btn-submit mt-3" id="sendResetBtn">
      <span class="btn-label">Send Reset Link</span>
    </button>
    <div class="alert-box alert-box--success d-none mt-3" id="resetSuccess" role="status">
      <i class="bi bi-check-circle-fill"></i>
      <span>Reset link sent! Check your inbox.</span>
    </div>
  </div>
</div>

<script src="auth.js"></script>
</body>
</html>
