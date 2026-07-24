<?php
require_once 'auth_guard.php';
$pageTitle = 'Platform Settings';
$pageSubtitle = 'Configuration, policies, and marketplace controls';
include 'partials/header.php';
?>
<div class="row g-3">
  <div class="col-12 col-xl-6">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-sliders"></i> Website Configuration</div></div>
      <div class="ds-cb">
        <div class="mb-3"><label class="ds-lbl">Platform Name</label><input class="ds-inp" value="<?= htmlspecialchars($adminName) ?>" /></div>
        <div class="mb-3"><label class="ds-lbl">Support Email</label><input class="ds-inp" value="<?= htmlspecialchars($admin['email']) ?>" /></div>
        <div class="mb-3"><label class="ds-lbl">Default Currency</label><input class="ds-inp" value="INR" /></div>
        <button class="ds-btn prim"><i class="bi bi-floppy-fill"></i> Save Configuration</button>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="ds-card">
      <div class="ds-ch"><div class="ds-ct"><i class="bi bi-shield-lock-fill"></i> Security & Policies</div></div>
      <div class="ds-cb">
        <div class="mb-3"><label class="ds-lbl">Terms & Privacy</label><textarea class="ds-inp" rows="4">BookHotel uses secure sessions, role-based access, and verified partner onboarding to protect guests and hotel operators.</textarea></div>
        <div class="mb-3"><label class="ds-lbl">Two-Factor Authentication</label><select class="ds-inp ds-sel"><option>Enabled for admins</option><option>Enabled for all roles</option><option>Disabled</option></select></div>
        <button class="ds-btn outl"><i class="bi bi-shield-check"></i> Update Security</button>
      </div>
    </div>
  </div>
</div>
<?php
require_once 'auth_guard.php'; include 'partials/footer.php'; ?>
