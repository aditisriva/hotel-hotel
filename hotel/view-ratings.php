<?php
require_once 'db.php';
require_once 'auth_guard.php';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Reviews Management — Hotel Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="dashboard.css"/>
</head><body>
<div class="ds-ov" id="dsOv"></div>
<aside class="ds-sb" id="dsSb">
  <a href="admin-dashboard.php" class="ds-logo"><div class="ds-logo-icon"><i class="bi bi-building-fill"></i></div><div><div class="ds-logo-name">bookHotel</div><div class="ds-logo-role">Manager Portal</div></div></a>
  <nav class="ds-nav" id="mainSidebar">
      <div class="ds-sec">Main</div>
      <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
      <a href="manage-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Manage Bookings</a>
      <a href="check-in-order.php" class="ds-link"><i class="bi bi-person-check-fill"></i> Check In Order</a>
      <a href="manage-hotel-listing.php" class="ds-link"><i class="bi bi-card-checklist"></i> Manage Hotel Listing</a>
      <a href="manage-rooms.php" class="ds-link"><i class="bi bi-door-open-fill"></i> Manage Rooms</a>
      <a href="view-ratings.php" class="ds-link"><i class="bi bi-star-fill"></i> View Ratings</a>
      <a href="transaction-history.php" class="ds-link"><i class="bi bi-cash-stack"></i> Transaction History</a>
      <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
    <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script>
  <div class="ds-foot"><a href="admin-hotel-profile.php" class="ds-hpill"><div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"><?= $manager_initials ?></div><div><div class="ds-hpill-name"><?= htmlspecialchars($manager_name) ?></div><div class="ds-hpill-status">● <?= htmlspecialchars($manager_role) ?></div></div></a></div>
</aside>
<header class="ds-top">
  <div class="ds-top-l">
    <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
    <div><div class="ds-page-title">Reviews Management</div><div class="ds-breadcrumb">Insights &rsaquo; Reviews</div></div>
  </div>
  <div class="ds-top-r">
    <a href="notifications.php" class="ds-ibtn"><i class="bi bi-bell-fill"></i><span class="ds-dot"></span></a>
    <div class="ds-avbtn" id="dsAvBtn"><div class="ds-av"><?= $manager_initials ?></div><span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span><i class="bi bi-chevron-down ms-1" style="font-size:.7rem;color:var(--mut)"></i>
      <div class="ds-dropdown" id="dsAvMenu">
        <a href="profile.php" class="ds-drop-item"><i class="bi bi-person-fill text-primary"></i> My Profile</a>
        <a href="settings.php" class="ds-drop-item"><i class="bi bi-gear-fill text-primary"></i> Settings</a>
        <hr class="my-1 mx-2"/>
        <a href="logout.php" class="ds-drop-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
<main class="ds-main">
  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-star-fill"></i></div><div class="ds-sn">4.8</div><div class="ds-sl">Overall Rating</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>+0.2 this month</div></div></div>
    <div class="col-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-chat-square-text-fill"></i></div><div class="ds-sn">1,248</div><div class="ds-sl">Total Reviews</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>28 this month</div></div></div>
    <div class="col-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-emoji-smile-fill"></i></div><div class="ds-sn">94%</div><div class="ds-sl">Positive</div><div class="ds-tr up"><i class="bi bi-arrow-up-short"></i>Above average</div></div></div>
    <div class="col-6 col-xl-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-reply-fill"></i></div><div class="ds-sn">5</div><div class="ds-sl">Awaiting Reply</div><div class="ds-tr down"><i class="bi bi-arrow-down-short"></i>Need attention</div></div></div>
  </div>
  <div class="row g-3">
    <!-- Rating Breakdown -->
    <div class="col-12 col-xl-3">
      <div class="ds-card">
        <div class="ds-ch"><div class="ds-ct"><i class="bi bi-bar-chart-fill"></i> Rating Breakdown</div></div>
        <div class="ds-cb">
          <div class="text-center mb-3">
            <div style="font-size:3rem;font-weight:800;color:var(--gold);line-height:1">4.8</div>
            <div class="ds-stars mb-1">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div style="font-size:.8rem;color:var(--mut)">Based on 1,248 reviews</div>
          </div>
          <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center gap-2"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">5★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:72%;background:var(--gold)"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">72%</span></div>
            <div class="d-flex align-items-center gap-2"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">4★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:19%;background:var(--grn)"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">19%</span></div>
            <div class="d-flex align-items-center gap-2"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">3★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:5%;background:#a0aec0"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">5%</span></div>
            <div class="d-flex align-items-center gap-2"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">2★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:3%;background:#f97316"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">3%</span></div>
            <div class="d-flex align-items-center gap-2"><span style="font-size:.8rem;width:24px;text-align:right;color:var(--mut)">1★</span><div class="ds-prog flex-grow-1"><div class="ds-progf" style="width:1%;background:var(--red)"></div></div><span style="font-size:.75rem;color:var(--mut);width:32px">1%</span></div>
          </div>
        </div>
      </div>
    </div>
    <!-- Reviews List -->
    <div class="col-12 col-xl-9">
      <!-- Filter Bar -->
      <div class="ds-card mb-3">
        <div class="ds-cb">
          <div class="d-flex gap-2 flex-wrap">
            <button class="ds-btn prim sm" onclick="filterRevs('')">All <span class="badge bg-light text-dark ms-1">1248</span></button>
            <button class="ds-btn outl sm" onclick="filterRevs('5')">5 Stars</button>
            <button class="ds-btn outl sm" onclick="filterRevs('4')">4 Stars</button>
            <button class="ds-btn outl sm" onclick="filterRevs('awaiting')">Awaiting Reply</button>
          </div>
        </div>
      </div>
      <!-- Review Cards -->
      <div id="revList">
        <!-- Review 1 -->
        <div class="ds-rev ds-card mb-3" data-stars="5" data-status="replied">
          <div class="ds-cb">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-3">
                <div class="ds-gav" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">RS</div>
                <div><div class="fw-700">Rahul Sharma</div><div style="font-size:.75rem;color:var(--mut)">Deluxe King · 12 Jul 2026 · Mumbai</div></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="ds-badge confirmed"><i class="bi bi-check-circle-fill"></i>Replied</span>
                <div class="ds-stars" style="color:var(--gold)">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
              </div>
            </div>
            <p style="font-size:.88rem;margin-bottom:.75rem">"Absolutely stunning property! The room was immaculate, views breathtaking, and the staff went above and beyond. The breakfast spread was incredible — easily the best hotel stay I've had in Mumbai. Will definitely be returning for our anniversary."</p>
            <div style="background:var(--sb-bg);border-left:3px solid var(--pr);padding:.75rem;border-radius:0 8px 8px 0;font-size:.82rem">
              <div style="font-weight:700;color:var(--pr);margin-bottom:.3rem"><i class="bi bi-building-fill me-1"></i>Management Reply</div>
              <div style="color:var(--txt)">Thank you so much, Rahul! We're thrilled you enjoyed your stay and look forward to celebrating your anniversary with us at The Grand Palace. 🙏</div>
            </div>
          </div>
        </div>
        <!-- Review 2 -->
        <div class="ds-rev ds-card mb-3" data-stars="4" data-status="awaiting">
          <div class="ds-cb">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-3">
                <div class="ds-gav" style="background:linear-gradient(135deg,#10b981,#059669)">PG</div>
                <div><div class="fw-700">Priya Gupta</div><div style="font-size:.75rem;color:var(--mut)">Ocean Suite · 10 Jul 2026 · Delhi</div></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="ds-badge pending"><i class="bi bi-hourglass-split"></i>Awaiting Reply</span>
                <div class="ds-stars" style="color:var(--gold)">&#9733;&#9733;&#9733;&#9733;&#9734;</div>
              </div>
            </div>
            <p style="font-size:.88rem;margin-bottom:.75rem">"Overall a wonderful experience. The ocean suite was gorgeous and the spa was absolutely relaxing. Only minor issue was the room service was a bit slow on the second evening, but the food quality made up for it. Would recommend to anyone visiting Mumbai."</p>
            <button class="ds-btn prim sm" onclick="openReplyModal(this, 'Priya Gupta','4 stars — Ocean Suite · 10 Jul 2026','Overall a wonderful experience. The ocean suite was gorgeous...')"><i class="bi bi-reply-fill me-1"></i>Reply Now</button>
          </div>
        </div>
        <!-- Review 3 -->
        <div class="ds-rev ds-card mb-3" data-stars="5" data-status="replied">
          <div class="ds-cb">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-3">
                <div class="ds-gav" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">AK</div>
                <div><div class="fw-700">Amit Kumar</div><div style="font-size:.75rem;color:var(--mut)">Executive Room · 5 Jun 2026 · Bangalore</div></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="ds-badge confirmed"><i class="bi bi-check-circle-fill"></i>Replied</span>
                <div class="ds-stars" style="color:var(--gold)">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
              </div>
            </div>
            <p style="font-size:.88rem;margin-bottom:.75rem">"Stayed here for a business trip and I was truly impressed. The business lounge, fast WiFi, and in-room amenities were perfect. The concierge arranged everything flawlessly. This is my go-to property for Mumbai visits now."</p>
            <div style="background:var(--sb-bg);border-left:3px solid var(--pr);padding:.75rem;border-radius:0 8px 8px 0;font-size:.82rem">
              <div style="font-weight:700;color:var(--pr);margin-bottom:.3rem"><i class="bi bi-building-fill me-1"></i>Management Reply</div>
              <div style="color:var(--txt)">We're delighted to be your preferred stay in Mumbai, Amit. Your comfort and productivity are our priority. See you on your next visit!</div>
            </div>
          </div>
        </div>
        <!-- Review 4 -->
        <div class="ds-rev ds-card mb-3" data-stars="2" data-status="awaiting">
          <div class="ds-cb">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-3">
                <div class="ds-gav" style="background:linear-gradient(135deg,#ef4444,#dc2626)">VJ</div>
                <div><div class="fw-700">Vijay Joshi</div><div style="font-size:.75rem;color:var(--mut)">Deluxe King · 18 May 2026 · Chennai</div></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="ds-badge cancelled"><i class="bi bi-exclamation-circle-fill"></i>Awaiting Reply</span>
                <div class="ds-stars" style="color:var(--gold)">&#9733;&#9733;&#9734;&#9734;&#9734;</div>
              </div>
            </div>
            <p style="font-size:.88rem;margin-bottom:.75rem">"Disappointed with the stay. AC was not working properly in the room and it took over 3 hours for maintenance to fix it. Staff at the front desk were not very helpful when I raised the complaint. Expected much better service given the room rate."</p>
            <button class="ds-btn dng sm" onclick="openReplyModal(this, 'Vijay Joshi','2 stars — Deluxe King · 18 May 2026','Disappointed with the stay. AC was not working properly...')"><i class="bi bi-reply-fill me-1"></i>Reply Now</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<!-- Reply Modal -->
<div class="ds-modal modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title fw-700" id="replyModalLabel"><i class="bi bi-reply-fill text-primary me-2"></i>Reply to Review</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="ds-card mb-3" style="background:var(--sb-bg)">
          <div class="ds-cb">
            <div class="fw-700 mb-1" id="rplGuestName"></div>
            <div style="font-size:.8rem;color:var(--mut);margin-bottom:.5rem" id="rplMeta"></div>
            <div style="font-size:.85rem;font-style:italic;color:var(--txt)" id="rplText"></div>
          </div>
        </div>
        <label class="ds-lbl" for="replyTextarea">Your Reply</label>
        <textarea class="ds-inp" id="replyTextarea" rows="5" placeholder="Write a professional, empathetic reply to this guest..."></textarea>
      </div>
      <div class="modal-footer gap-2">
        <button class="ds-btn gho sm" data-bs-dismiss="modal">Cancel</button>
        <button class="ds-btn prim sm" onclick="postReply()"><i class="bi bi-send-fill me-1"></i>Post Reply</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="dashboard.js"></script>
<script>
let currentReplyReview = null;
let currentReplyBtn = null;
function openReplyModal(btn, guest,meta,text){
  currentReplyBtn = btn;
  currentReplyReview = btn.closest('.ds-rev');
  document.getElementById('rplGuestName').textContent=guest;
  document.getElementById('rplMeta').textContent=meta;
  document.getElementById('rplText').textContent='"'+text+'"';
  document.getElementById('replyTextarea').value='';
  new bootstrap.Modal(document.getElementById('replyModal')).show();
}
function postReply() {
  const text = document.getElementById('replyTextarea').value.trim();
  if(!text) return dsToast('Please enter a reply', 'error');
  if(currentReplyReview) {
    currentReplyReview.dataset.status = 'replied';
    const badge = currentReplyReview.querySelector('.ds-badge');
    badge.className = 'ds-badge confirmed';
    badge.innerHTML = '<i class="bi bi-check-circle-fill"></i>Replied';
    
    const replyHtml = '<div style="background:var(--sb-bg);border-left:3px solid var(--pr);padding:.75rem;border-radius:0 8px 8px 0;font-size:.82rem;margin-top:1rem">' +
                      '<div style="font-weight:700;color:var(--pr);margin-bottom:.3rem"><i class="bi bi-building-fill me-1"></i>Management Reply</div>' +
                      '<div style="color:var(--txt)">'+text+'</div></div>';
    currentReplyBtn.insertAdjacentHTML('beforebegin', replyHtml);
    currentReplyBtn.remove();
  }
  dsToast('Reply posted successfully!','success');
  bootstrap.Modal.getInstance(document.getElementById('replyModal')).hide();
  filterRevs(document.querySelector('.ds-btn.outl.active')?.dataset?.filter || '');
}
function filterRevs(f){
  document.querySelectorAll('#revList .ds-rev').forEach(r=>{
    if(!f){r.style.display='';return;}
    if(f==='awaiting'){r.style.display=r.dataset.status==='awaiting'?'':'none';}
    else{r.style.display=r.dataset.stars===f?'':'none';}
  });
}
</script>
</body></html>



