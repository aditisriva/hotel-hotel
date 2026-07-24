<?php
require_once 'auth_guard.php';
$pageTitle = 'Admin Profile';
$pageSubtitle = 'Personal details, access preferences, and security';
include 'partials/header.php';
?>
<div class="row g-3">
  <div class="col-12 col-xl-6">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-person-circle"></i> Profile Details</div></div>
      <div class="ds-cb">
        <div class="mb-3"><label class="ds-lbl">Full Name</label><input class="ds-inp" value="<?= htmlspecialchars($adminName) ?>" /></div>
        <div class="mb-3"><label class="ds-lbl">Email Address</label><input class="ds-inp" value="<?= htmlspecialchars($admin['email']) ?>" /></div>
        <div class="mb-3"><label class="ds-lbl">Phone Number</label><input class="ds-inp" value="<?= htmlspecialchars($admin['mobile']) ?>" /></div>
        <button class="ds-btn prim"><i class="bi bi-floppy-fill"></i> Save Profile</button>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-shield-lock-fill"></i> Security Settings</div></div>
      <div class="ds-cb">
        <div class="mb-3"><label class="ds-lbl">New Password</label><input class="ds-inp" type="password" /></div>
        <div class="mb-3"><label class="ds-lbl">Confirm Password</label><input class="ds-inp" type="password" /></div>
        <div class="mb-3"><label class="ds-lbl">Two-Factor Authentication</label><select class="ds-inp ds-sel"><option>Enabled</option><option>Disabled</option></select></div>
        <button class="ds-btn outl"><i class="bi bi-shield-check"></i> Update Security</button>
      </div>
    </div>
  </div>
</div>
<?php
require_once 'auth_guard.php'; include 'partials/footer.php'; ?>
