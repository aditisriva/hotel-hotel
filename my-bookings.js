'use strict';

/* ─────────────────────────────────────────────
   VIEW DETAILS MODAL  (reads data-modal JSON)
───────────────────────────────────────────── */
function openDetailsModal(card) {
  if (!card) return;

  // Try reading from data-modal JSON attribute first (dynamic cards)
  let d = null;
  try { d = JSON.parse(card.dataset.modal || '{}'); } catch(_) {}

  // Fallbacks for any fields missing from JSON
  const hotelName  = d?.hotel   || card.querySelector('.mb-card__hotel')?.textContent.trim() || '—';
  const location   = d?.location|| card.querySelector('.mb-card__loc')?.textContent.trim().replace(/^\s*[^\w]*/, '') || '—';
  const bookingId  = d?.bookingId|| card.dataset.id || '—';
  const status     = d?.status  || card.dataset.status || '';
  const hotelImg   = d?.img     || card.querySelector('.mb-card__img')?.src || '';
  const room       = d?.room    || '—';
  const guests     = d?.guests  || '—';
  const checkin    = d?.checkin || '—';
  const checkout   = d?.checkout|| '—';
  const price      = d?.price   || card.querySelector('.mb-card__price')?.textContent.trim() || '—';
  const bookedOn   = d?.bookedOn|| card.querySelector('.mb-card__booked-on')?.textContent.replace(/^.*?:\s*/, '').trim() || '—';
  const payStatus  = d?.payStatus|| '—';
  const payClass   = d?.payClass || '';
  const badgeText  = d?.badgeText|| 'Confirmed';
  const badgeClass = d?.badgeClass|| 'mb-badge--upcoming';

  // Extra rich details
  const guestName  = d?.guestName  || '';
  const guestEmail = d?.guestEmail || '';
  const guestPhone = d?.guestPhone || '';
  const baseAmount = d?.baseAmount || '';
  const taxAmount  = d?.taxAmount  || '';
  const coupon     = d?.couponDiscount || null;
  const payMethod  = d?.payMethod  || '';
  const nights     = d?.nights     || '';

  // Populate basic modal fields
  document.getElementById('dmSubtitle').textContent  = hotelName;
  document.getElementById('dmHotelImg').src           = hotelImg;
  document.getElementById('dmHotel').textContent      = hotelName;
  document.getElementById('dmAddress').textContent    = location;
  document.getElementById('dmRoom').textContent       = room;
  document.getElementById('dmGuests').textContent     = guests;
  document.getElementById('dmCheckin').textContent    = checkin;
  document.getElementById('dmCheckout').textContent   = checkout;
  document.getElementById('dmPrice').textContent      = price + (nights ? ` · ${nights} night${nights !== 1 ? 's' : ''}` : '');
  document.getElementById('dmBookingId').textContent  = bookingId;
  document.getElementById('dmBookedOn').textContent   = bookedOn;

  const statusBadge = document.getElementById('dmStatusBadge');
  statusBadge.textContent = badgeText;
  statusBadge.className   = `mb-details-modal__status-badge mb-badge ${badgeClass}`;

  const dmPayment = document.getElementById('dmPayment');
  dmPayment.textContent = payStatus;
  dmPayment.className   = `mb-payment-status__badge ${payClass}`;

  // Populate extended details if elements exist
  const setOptEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
  setOptEl('dmGuestName',  guestName);
  setOptEl('dmGuestEmail', guestEmail);
  setOptEl('dmGuestPhone', guestPhone);
  setOptEl('dmBaseAmount', baseAmount);
  setOptEl('dmTaxAmount',  taxAmount);
  setOptEl('dmPayMethod',  payMethod);
  const couponEl = document.getElementById('dmCoupon');
  if (couponEl) couponEl.closest('.mb-dm-item')?.style.setProperty('display', coupon ? '' : 'none');
  if (couponEl && coupon) couponEl.textContent = coupon;

  // Show modal
  const modalEl = document.getElementById('detailsModal');
  new bootstrap.Modal(modalEl).show();
}

/* ─────────────────────────────────────────────
   TOAST
───────────────────────────────────────────── */
function showToastMsg(msg, type = 'info') {
  const wrap = document.getElementById('mbToastWrap');
  if (!wrap) return;
  const icons = { success: 'bi-check-circle-fill', info: 'bi-info-circle-fill', warn: 'bi-exclamation-triangle-fill', error: 'bi-x-circle-fill' };
  const t = document.createElement('div');
  t.className = `mb-toast mb-toast--${type}`;
  t.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${msg}</span>`;
  wrap.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s ease, transform .3s ease';
    t.style.opacity = '0'; t.style.transform = 'translateY(6px)';
    setTimeout(() => t.remove(), 320);
  }, 3500);
}

/* ─────────────────────────────────────────────
   CANCEL MODAL  (now posts to cancel_booking.php)
───────────────────────────────────────────── */
let _cancelTarget = null;

// Triggered by the new .mb-cancel-btn buttons
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.mb-cancel-btn');
  if (!btn) return;
  _cancelTarget = btn.closest('.mb-card');
  document.getElementById('cancelModal').classList.add('open');
});

document.getElementById('cancelNo')?.addEventListener('click', () => {
  document.getElementById('cancelModal').classList.remove('open');
  _cancelTarget = null;
});

document.getElementById('cancelYes')?.addEventListener('click', () => {
  if (!_cancelTarget) return;
  document.getElementById('cancelModal').classList.remove('open');

  const card = _cancelTarget;
  const bookingId = card.dataset.bid || card.dataset.id || '';
  const yesBtn = document.getElementById('cancelYes');
  yesBtn.disabled = true;
  yesBtn.textContent = 'Cancelling…';

  fetch('cancel_booking.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'booking_id=' + encodeURIComponent(bookingId),
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Visually update card
      card.dataset.status = 'cancelled';

      const badge = card.querySelector('.mb-badge');
      if (badge) {
        badge.className = 'mb-badge mb-badge--cancelled';
        badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Cancelled';
      }

      // Add cancelled overlay
      const imgWrap = card.querySelector('.mb-card__img-wrap');
      if (imgWrap && !imgWrap.querySelector('.mb-card__cancelled-overlay')) {
        const ov = document.createElement('div');
        ov.className = 'mb-card__cancelled-overlay';
        ov.setAttribute('aria-hidden', 'true');
        imgWrap.appendChild(ov);
      }

      // Remove timeline
      card.querySelector('.mb-timeline')?.remove();

      // Update payment badge
      const payBadge = card.querySelector('.mb-payment-status__badge');
      if (payBadge) {
        payBadge.textContent = 'Refund Initiated';
        payBadge.className   = 'mb-payment-status__badge mb-payment-status__badge--refund';
      }

      // Swap action buttons
      const actions = card.querySelector('.mb-card__actions');
      if (actions) {
        const oldCancel = actions.querySelector('.mb-cancel-btn');
        if (oldCancel) {
          const bookAgain = document.createElement('a');
          bookAgain.href = 'hotels.php';
          bookAgain.className = 'mb-btn mb-btn--primary';
          bookAgain.innerHTML = '<i class="bi bi-arrow-repeat"></i> Book Again';
          oldCancel.replaceWith(bookAgain);
        }
        // Update invoice href if present
        const invoiceLink = actions.querySelector('a[href*="invoice.php"]');
        if (invoiceLink && bookingId) invoiceLink.href = `invoice.php?bid=${encodeURIComponent(bookingId)}`;
      }

      // Add cancel reason
      const meta = card.querySelector('.mb-card__meta');
      if (meta && !card.querySelector('.mb-cancel-reason')) {
        const reason = document.createElement('div');
        reason.className = 'mb-cancel-reason';
        reason.innerHTML = '<i class="bi bi-info-circle-fill"></i> Booking cancelled · Refund Initiated';
        meta.insertAdjacentElement('afterend', reason);
      }

      // Update price label
      const priceLabel = card.querySelector('.mb-card__price-label');
      if (priceLabel) priceLabel.textContent = 'Booking Amount';
      const priceEl = card.querySelector('.mb-card__price');
      if (priceEl) priceEl.classList.add('mb-card__price--struck');

      // Update card body class
      const body = card.querySelector('.mb-card__body');
      if (body) body.classList.add('mb-card__body--cancelled');

      updateSummary();
      showToastMsg(data.message, 'warn');
    } else {
      showToastMsg(data.message || 'Could not cancel booking.', 'error');
    }
  })
  .catch(() => showToastMsg('Network error. Please try again.', 'error'))
  .finally(() => {
    yesBtn.disabled = false;
    yesBtn.textContent = 'Yes, Cancel';
    _cancelTarget = null;
  });
});

// Close on backdrop click or Escape
document.getElementById('cancelModal')?.addEventListener('click', (e) => {
  if (e.target === document.getElementById('cancelModal')) {
    document.getElementById('cancelModal').classList.remove('open');
    _cancelTarget = null;
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') document.getElementById('cancelModal')?.classList.remove('open');
});

/* ─────────────────────────────────────────────
   SEARCH
───────────────────────────────────────────── */
const searchInput = document.getElementById('bookingSearch');
const searchClear = document.getElementById('searchClear');

searchInput?.addEventListener('input', () => {
  const q = searchInput.value.trim().toLowerCase();
  searchClear?.classList.toggle('d-none', !q);
  filterCards();
});

searchClear?.addEventListener('click', () => {
  searchInput.value = '';
  searchClear.classList.add('d-none');
  filterCards();
  searchInput.focus();
});

/* ─────────────────────────────────────────────
   FILTER TABS
───────────────────────────────────────────── */
let activeFilter = 'all';

document.querySelectorAll('.mb-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.mb-tab').forEach(t => {
      t.classList.remove('mb-tab--active');
      t.setAttribute('aria-selected', 'false');
    });
    tab.classList.add('mb-tab--active');
    tab.setAttribute('aria-selected', 'true');
    activeFilter = tab.dataset.filter;
    filterCards();
  });
});

function filterCards() {
  const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
  const cards = document.querySelectorAll('.mb-card');
  let visible = 0;

  cards.forEach(card => {
    const status  = card.dataset.status;
    const hotel   = (card.dataset.hotel || '').toLowerCase();
    const id      = (card.dataset.id   || '').toLowerCase();

    const matchFilter = activeFilter === 'all' || status === activeFilter;
    const matchSearch = !q || hotel.includes(q) || id.includes(q);

    const show = matchFilter && matchSearch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // Show / hide empty state
  const empty = document.getElementById('emptyState');
  if (empty) empty.classList.toggle('d-none', visible > 0);
}

/* ─────────────────────────────────────────────
   SUMMARY COUNTS (live update after cancel)
───────────────────────────────────────────── */
function updateSummary() {
  const cards = document.querySelectorAll('.mb-card');
  let total = 0, upcoming = 0, completed = 0, cancelled = 0;
  cards.forEach(c => {
    total++;
    const s = c.dataset.status;
    if (s === 'upcoming')  upcoming++;
    if (s === 'completed') completed++;
    if (s === 'cancelled') cancelled++;
  });
  const nums = document.querySelectorAll('.mb-stat-card__num');
  if (nums[0]) nums[0].textContent = total;
  if (nums[1]) nums[1].textContent = upcoming;
  if (nums[2]) nums[2].textContent = completed;
  if (nums[3]) nums[3].textContent = cancelled;

  document.querySelectorAll('.mb-tab').forEach(tab => {
    const count = tab.querySelector('.mb-tab-count');
    if (!count) return;
    const f = tab.dataset.filter;
    if (f === 'all')       count.textContent = total;
    if (f === 'upcoming')  count.textContent = upcoming;
    if (f === 'completed') count.textContent = completed;
    if (f === 'cancelled') count.textContent = cancelled;
  });
}

/* ─────────────────────────────────────────────
   STAR RATINGS  (click → show review form)
───────────────────────────────────────────── */
document.addEventListener('click', (e) => {
  const star = e.target.closest('.mb-star');
  if (!star) return;

  const starsEl   = star.closest('.mb-stars');
  if (!starsEl) return;
  const bookingId = starsEl.dataset.bookingId;
  const val       = parseInt(star.dataset.val);
  const stars     = starsEl.querySelectorAll('.mb-star');

  // Set rating visually
  starsEl.dataset.rated = val;
  stars.forEach(s => {
    const sv = parseInt(s.dataset.val);
    s.querySelector('i').className = sv <= val ? 'bi bi-star-fill' : 'bi bi-star';
  });

  // Show review form
  const form = document.getElementById(`reviewForm_${bookingId}`);
  if (form) {
    form.classList.remove('d-none');
    form.querySelector('.mb-review-textarea')?.focus();
  }
});

// Star hover effects
document.addEventListener('mouseover', (e) => {
  const star = e.target.closest('.mb-star');
  if (!star) return;
  const starsEl = star.closest('.mb-stars');
  if (!starsEl) return;
  const val   = parseInt(star.dataset.val);
  starsEl.querySelectorAll('.mb-star').forEach(s => {
    const sv = parseInt(s.dataset.val);
    s.querySelector('i').className = sv <= val ? 'bi bi-star-fill' : 'bi bi-star';
  });
});
document.addEventListener('mouseout', (e) => {
  const star = e.target.closest('.mb-star');
  if (!star) return;
  const starsEl = star.closest('.mb-stars');
  if (!starsEl) return;
  const rated = parseInt(starsEl.dataset.rated || '0');
  starsEl.querySelectorAll('.mb-star').forEach(s => {
    const sv = parseInt(s.dataset.val);
    s.querySelector('i').className = sv <= rated ? 'bi bi-star-fill' : 'bi bi-star';
  });
});

/* ─────────────────────────────────────────────
   REVIEW FORM — Char counter + Cancel
───────────────────────────────────────────── */
document.addEventListener('input', (e) => {
  if (!e.target.classList.contains('mb-review-textarea')) return;
  const max     = parseInt(e.target.getAttribute('maxlength') || '1000');
  const current = e.target.value.length;
  const counter = e.target.closest('.mb-review-form')?.querySelector('.mb-review-char-count');
  if (counter) {
    counter.textContent = `${current} / ${max}`;
    counter.style.color = current > max * 0.9 ? '#dc2626' : '';
  }
});

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('mb-review-cancel-btn') || e.target.closest('.mb-review-cancel-btn')) {
    const form = e.target.closest('.mb-review-form');
    if (!form) return;
    form.classList.add('d-none');
    form.querySelector('.mb-review-textarea').value = '';
    const counter = form.querySelector('.mb-review-char-count');
    if (counter) counter.textContent = '0 / 1000';
    // Reset stars
    const card = form.closest('.mb-card');
    const starsEl = card?.querySelector('.mb-stars');
    if (starsEl) {
      starsEl.dataset.rated = 0;
      starsEl.querySelectorAll('.mb-star i').forEach(i => i.className = 'bi bi-star');
    }
  }
});

/* ─────────────────────────────────────────────
   REVIEW FORM — AJAX Submit
───────────────────────────────────────────── */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.mb-review-submit-btn');
  if (!btn) return;

  const bookingId = btn.dataset.bookingId;
  const form      = btn.closest('.mb-review-form');
  const textarea  = form?.querySelector('.mb-review-textarea');
  const card      = form?.closest('.mb-card');
  const starsEl   = card?.querySelector('.mb-stars');
  const rating    = parseInt(starsEl?.dataset.rated || '0');
  const text      = textarea?.value.trim() || '';

  if (!rating || rating < 1 || rating > 5) {
    showToastMsg('Please select a star rating first.', 'warn'); return;
  }
  if (text.length < 10) {
    showToastMsg('Review must be at least 10 characters.', 'warn'); return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting…';

  const payload = new URLSearchParams({
    action:      'submit_review',
    booking_id:  bookingId,
    rating:      rating,
    review_text: text,
  });

  fetch('my-bookings.php', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToastMsg(data.message || 'Review submitted! Pending approval.', 'success');

        // Replace star prompt + form with submitted view
        const prompt = card?.querySelector('.mb-rating-prompt');
        const reviewFormEl = card?.querySelector('.mb-review-form');

        const stars_html = Array.from({length:5}, (_,i) =>
          `<i class="bi ${i < rating ? 'bi-star-fill' : 'bi-star'} text-warning"></i>`
        ).join('');

        const submittedDiv = document.createElement('div');
        submittedDiv.className = 'mb-review-submitted';
        submittedDiv.innerHTML = `
          <div class="mb-review-submitted__stars">
            ${stars_html}
            <span class="mb-review-submitted__label ms-2">Your Review</span>
            <span class="mb-review-badge mb-review-badge--pending">Pending</span>
          </div>
          <p class="mb-review-submitted__text">${text.replace(/</g,'&lt;')}</p>`;

        if (prompt) prompt.replaceWith(submittedDiv);
        if (reviewFormEl) reviewFormEl.remove();
      } else {
        showToastMsg(data.message || 'Failed to submit review.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit Review';
      }
    })
    .catch(() => {
      showToastMsg('Network error. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit Review';
    });
});

/* ─────────────────────────────────────────────
   NAVBAR SCROLL + BACK-TO-TOP
───────────────────────────────────────────── */
window.addEventListener('scroll', () => {
  const nav = document.getElementById('mainNav');
  if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
  const btt = document.getElementById('backToTop');
  if (btt) btt.classList.toggle('show', window.scrollY > 300);
});

/* ─────────────────────────────────────────────
   HERO USER INFO from localStorage (fallback)
───────────────────────────────────────────── */
(function populateHeroUser() {
  try {
    const raw = localStorage.getItem('bh_user');
    if (!raw) return;
    const u = JSON.parse(raw);
    const nameEl   = document.getElementById('heroName');
    const emailEl  = document.getElementById('heroEmail');
    const avatarEl = document.getElementById('heroAvatar');
    // Only override if PHP left blanks
    if (nameEl && !nameEl.textContent.trim() && u.name)   nameEl.textContent  = u.name;
    if (emailEl && !emailEl.textContent.trim() && u.email) emailEl.textContent = u.email;
    if (avatarEl && !avatarEl.textContent.trim() && u.name) {
      avatarEl.textContent = u.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    }
  } catch (_) {}
})();

/* ─────────────────────────────────────────────
   INIT on DOM ready
───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  filterCards(); // ensure empty state is correct on page load
});
