'use strict';

/* ─────────────────────────────────────────────
   VIEW DETAILS MODAL
───────────────────────────────────────────── */
function openDetailsModal(card) {
  if (!card) return;

  // Extract data from card
  const hotelName   = card.querySelector('.mb-card__hotel')?.textContent.trim() || '—';
  const location    = card.querySelector('.mb-card__loc')?.textContent.trim().replace(/^.*?(?=\w)/, '') || '—';
  const bookingId   = card.dataset.id || '—';
  const status      = card.dataset.status || '';
  const hotelImg    = card.querySelector('.mb-card__img')?.src || '';
  
  // Extract from meta items
  const metaItems = card.querySelectorAll('.mb-meta-item');
  let checkin = '—', checkout = '—', guests = '—', room = '—';
  metaItems.forEach(item => {
    const label = item.querySelector('.mb-meta-label')?.textContent.toLowerCase();
    const val   = item.querySelector('.mb-meta-val')?.textContent.trim();
    if (label?.includes('check-in'))  checkin  = val;
    if (label?.includes('check-out')) checkout = val;
    if (label?.includes('guests'))    guests   = val;
    if (label?.includes('room'))      room     = val;
  });

  const price     = card.querySelector('.mb-card__price')?.textContent.trim() || '—';
  const bookedOn  = card.querySelector('.mb-card__booked-on')?.textContent.replace(/^.*?:\s*/, '').trim() || '—';
  
  // Payment status badge
  const payBadge  = card.querySelector('.mb-payment-status__badge');
  let payStatus   = '—';
  let payClass    = '';
  if (payBadge) {
    payStatus = payBadge.textContent.trim();
    if (payBadge.classList.contains('mb-payment-status__badge--paid'))   payClass = 'mb-payment-status__badge--paid';
    if (payBadge.classList.contains('mb-payment-status__badge--refund')) payClass = 'mb-payment-status__badge--refund';
    if (payBadge.classList.contains('mb-payment-status__badge--failed')) payClass = 'mb-payment-status__badge--failed';
  }

  // Status badge styling
  const badge = card.querySelector('.mb-badge');
  let badgeText  = 'Confirmed';
  let badgeClass = 'mb-badge--upcoming';
  if (badge) {
    badgeText = badge.textContent.trim();
    if (badge.classList.contains('mb-badge--upcoming'))  badgeClass = 'mb-badge--upcoming';
    if (badge.classList.contains('mb-badge--completed')) badgeClass = 'mb-badge--completed';
    if (badge.classList.contains('mb-badge--cancelled')) badgeClass = 'mb-badge--cancelled';
  }

  // Populate modal fields
  document.getElementById('dmSubtitle').textContent  = hotelName;
  document.getElementById('dmHotelImg').src          = hotelImg;
  document.getElementById('dmHotel').textContent     = hotelName;
  document.getElementById('dmAddress').textContent   = location;
  document.getElementById('dmRoom').textContent      = room;
  document.getElementById('dmGuests').textContent    = guests;
  document.getElementById('dmCheckin').textContent   = checkin;
  document.getElementById('dmCheckout').textContent  = checkout;
  document.getElementById('dmPrice').textContent     = price;
  document.getElementById('dmBookingId').textContent = bookingId;
  document.getElementById('dmBookedOn').textContent  = bookedOn;

  const statusBadge = document.getElementById('dmStatusBadge');
  statusBadge.textContent = badgeText;
  statusBadge.className   = `mb-details-modal__status-badge mb-badge ${badgeClass}`;

  const dmPayment = document.getElementById('dmPayment');
  dmPayment.textContent = payStatus;
  dmPayment.className   = `mb-payment-status__badge ${payClass}`;

  // Show modal
  const modalEl = document.getElementById('detailsModal');
  const modal   = new bootstrap.Modal(modalEl);
  modal.show();
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
  }, 3000);
}

/* ─────────────────────────────────────────────
   CANCEL MODAL
───────────────────────────────────────────── */
let _cancelTarget = null;

function confirmCancel(btn) {
  _cancelTarget = btn.closest('.mb-card');
  document.getElementById('cancelModal').classList.add('open');
}

document.getElementById('cancelNo')?.addEventListener('click', () => {
  document.getElementById('cancelModal').classList.remove('open');
  _cancelTarget = null;
});

document.getElementById('cancelYes')?.addEventListener('click', () => {
  if (!_cancelTarget) return;
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
  .then(async res => {
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      console.error('Non-JSON response from cancel_booking.php:', text);
      throw new Error(text.replace(/<[^>]*>/g, '').trim() || 'Server returned invalid response');
    }
    if (!res.ok && data && data.message) {
      throw new Error(data.message);
    }
    return data;
  })
  .then(data => {
    if (data.success) {
      card.setAttribute('data-status', 'cancelled');
      const badge = card.querySelector('.mb-badge');
      if (badge) {
        badge.className = 'mb-badge mb-badge--cancelled';
        badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Cancelled';
      }
      const actions = card.querySelector('.mb-card__actions');
      if (actions) {
        actions.innerHTML = `
          <button class="mb-btn mb-btn--ghost" onclick="openDetailsModal(this.closest('.mb-card'))">
            <i class="bi bi-eye-fill"></i> View Details
          </button>
          <a href="hotels.php" class="mb-btn mb-btn--primary">
            <i class="bi bi-arrow-repeat"></i> Book Again
          </a>`;
      }
      const imgWrap = card.querySelector('.mb-card__img-wrap');
      if (imgWrap && !imgWrap.querySelector('.mb-card__cancelled-overlay')) {
        const ov = document.createElement('div');
        ov.className = 'mb-card__cancelled-overlay';
        ov.setAttribute('aria-hidden', 'true');
        imgWrap.appendChild(ov);
      }
      updateSummary();
      showToastMsg(data.message || 'Booking cancelled successfully.', 'success');
      const payBadge = card.querySelector('.mb-payment-status__badge');
      if (payBadge) {
        payBadge.textContent = 'Refund Pending';
        payBadge.className   = 'mb-payment-status__badge mb-payment-status__badge--refund';
      }
    } else {
      showToastMsg(data.message || 'Could not cancel booking.', 'error');
    }
  })
  .catch(err => showToastMsg(err.message || 'Error processing request.', 'error'))
  .finally(() => {
    yesBtn.disabled = false;
    yesBtn.textContent = 'Yes, Cancel';
    document.getElementById('cancelModal')?.classList.remove('open');
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

  // Update tab counts
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
   STAR RATINGS
───────────────────────────────────────────── */
document.querySelectorAll('.mb-stars').forEach(starsEl => {
  const stars = starsEl.querySelectorAll('.mb-star');

  stars.forEach(star => {
    // hover highlight
    star.addEventListener('mouseenter', () => {
      const val = parseInt(star.dataset.val);
      stars.forEach(s => {
        const sv = parseInt(s.dataset.val);
        s.querySelector('i').className = sv <= val ? 'bi bi-star-fill' : 'bi bi-star';
      });
    });

    // reset on leave (unless rated)
    starsEl.addEventListener('mouseleave', () => {
      const rated = parseInt(starsEl.dataset.rated || '0');
      stars.forEach(s => {
        const sv = parseInt(s.dataset.val);
        s.querySelector('i').className = sv <= rated ? 'bi bi-star-fill' : 'bi bi-star';
      });
    });

    // click to rate
    star.addEventListener('click', () => {
      const val = parseInt(star.dataset.val);
      starsEl.dataset.rated = val;
      stars.forEach(s => {
        const sv = parseInt(s.dataset.val);
        s.querySelector('i').className = sv <= val ? 'bi bi-star-fill' : 'bi bi-star';
      });
      showToastMsg(`Thanks! You rated your stay ${val} out of 5 ⭐`, 'success');
    });
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
   HERO USER INFO from localStorage
───────────────────────────────────────────── */
(function populateHeroUser() {
  try {
    const raw = localStorage.getItem('bh_user');
    if (!raw) return;
    const u = JSON.parse(raw);
    const nameEl   = document.getElementById('heroName');
    const emailEl  = document.getElementById('heroEmail');
    const avatarEl = document.getElementById('heroAvatar');
    if (nameEl && u.name)   nameEl.textContent  = u.name;
    if (emailEl && u.email) emailEl.textContent = u.email;
    if (avatarEl && u.name) {
      avatarEl.textContent = u.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    }
  } catch (_) {}
})();

