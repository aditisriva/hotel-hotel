<?php
/**
 * Admin Login — Independent Panel
 */
session_start();
require_once 'db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

$error     = '';
$success   = '';
$email_val = '';

if (isset($_COOKIE['admin_remember_email'])) {
    $email_val = htmlspecialchars($_COOKIE['admin_remember_email']);
}

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = 'Account created successfully! Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier  = trim($_POST['identifier'] ?? '');
    $password    = trim($_POST['password']   ?? '');
    $remember_me = isset($_POST['remember_me']);

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $identifier_safe = admin_sanitize($identifier);
        $sql = "SELECT admin_id, first_name, last_name, email, password, status
                FROM admins
                WHERE email = '$identifier_safe' OR mobile = '$identifier_safe'
                LIMIT 1";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);

            if ($admin['status'] !== 'active') {
                $error = 'Your account has been suspended. Please contact support.';
            } elseif (password_verify($password, $admin['password'])) {
                 $_SESSION['admin_id']        = $admin['admin_id'];
                 $_SESSION['admin_name']      = $admin['first_name'] . ' ' . $admin['last_name'];
                 $_SESSION['admin_email']     = $admin['email'];
                 $_SESSION['admin_firstname'] = $admin['first_name'];
                 $_SESSION['admin_role']      = $admin['role'];

                $aid = (int)$admin['admin_id'];
                mysqli_query($conn, "UPDATE admins SET last_login = NOW() WHERE admin_id = $aid");

                if ($remember_me) {
                    setcookie('admin_remember_email', $admin['email'], time() + (30 * 24 * 60 * 60), '/');
                } else {
                    setcookie('admin_remember_email', '', time() - 3600, '/');
                }

                header('Location: admin-dashboard.php');
                exit();
            } else {
                $error = 'Invalid email/mobile or password.';
            }
        } else {
            $error = 'Invalid email/mobile or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login — bookHotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../login.css"/>
</head>
<body class="auth-body">

<div class="auth-wrapper">
  <div class="auth-left d-none d-lg-flex">
    <div class="auth-left-overlay"></div>
    <div class="auth-left-content text-white">
      <h1 class="fw-bold mb-4"><i class="fa fa-hotel me-2 text-warning"></i>bookHotel</h1>
      <h2 class="fw-bold mb-3">Platform Administration</h2>
      <p class="mb-4 text-white-50">Secure access to the admin control center. Manage users, hotels, and platform settings.</p>
      <div class="auth-features">
        <div class="auth-feature-item">
          <div class="auth-feature-icon"><i class="fa fa-lock"></i></div>
          <div>
            <h6 class="mb-0 fw-bold">Restricted Access</h6>
            <small class="text-white-50">Authorized personnel only</small>
          </div>
        </div>
        <div class="auth-feature-item">
          <div class="auth-feature-icon"><i class="fa fa-chart-line"></i></div>
          <div>
            <h6 class="mb-0 fw-bold">Platform Insights</h6>
            <small class="text-white-50">Complete oversight dashboard</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-box">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Welcome Back</h2>
        <p class="text-muted">Admin Sign In</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <i class="fa fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success" role="alert">
        <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php" id="loginForm" novalidate>
        <div class="mb-3">
          <label class="auth-label" for="identifier">EMAIL OR MOBILE NUMBER</label>
          <div class="input-group auth-input-group">
            <span class="input-group-text"><i class="fa fa-user"></i></span>
            <input type="text" id="identifier" name="identifier" class="form-control auth-input" placeholder="Enter email or mobile" value="<?= htmlspecialchars($email_val) ?>" required/>
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <label class="auth-label mb-0" for="password">PASSWORD</label>
          </div>
          <div class="input-group auth-input-group mt-1">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input type="password" id="password" name="password" class="form-control auth-input" placeholder="Enter your password" required/>
            <span class="input-group-text" id="togglePassword"><i class="fa fa-eye" id="toggleIcon"></i></span>
          </div>
        </div>

        <div class="mb-4 form-check">
          <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" <?= !empty($email_val) ? 'checked' : '' ?>>
          <label class="form-check-label text-muted" style="font-size: 0.85rem;" for="remember_me">Remember me for 30 days</label>
        </div>

        <button type="submit" class="auth-submit-btn w-100 py-2 mb-4" id="loginBtn">
          Sign In <i class="fa fa-arrow-right ms-2"></i>
        </button>

        <p class="text-center text-muted mb-0" style="font-size: 0.9rem;">
          Don't have an account? <a href="signup.php" class="text-danger fw-bold text-decoration-none">Create Admin Account</a>
        </p>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggleBtn = document.getElementById('togglePassword');
  const pwdInput = document.getElementById('password');
  const toggleIcon = document.getElementById('toggleIcon');
  if(toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', () => {
      const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
      pwdInput.setAttribute('type', type);
      toggleIcon.className = type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
    });
  }
</script>
</body>
</html>
