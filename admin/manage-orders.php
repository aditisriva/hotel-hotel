<?php
require_once 'auth_guard.php';
require_once 'db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action']==='update_status') {
        $bid    = sanitize($_POST['booking_id']??'');
        $status = sanitize($_POST['status']??'');
        $valid  = ['pending','confirmed','checked_in','checked_out','cancelled'];
        if ($bid && in_array($status,$valid)) {
            $stmt = mysqli_prepare($conn,"UPDATE bookings SET booking_status=? WHERE booking_id=?");
            mysqli_stmt_bind_param($stmt,'ss',$status,$bid);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success'=>$ok]);
        } else echo json_encode(['success'=>false]);
        exit;
    }
}

// Stats
$stats=['total'=>0,'confirmed'=>0,'pending'=>0,'cancelled'=>0,'revenue'=>0];
$res=mysqli_query($conn,"SELECT COUNT(*) AS total,
    SUM(booking_status IN('confirmed','checked_in','checked_out')) AS confirmed,
    SUM(booking_status='pending') AS pending,
    SUM(booking_status='cancelled') AS cancelled,
    SUM(CASE WHEN booking_status!='cancelled' THEN total_amount ELSE 0 END) AS revenue
    FROM bookings");
if($res) $stats=array_merge($stats,mysqli_fetch_assoc($res));

// Filter
$fs = isset($_GET['q']) ? trim($_GET['q']) : '';
$fstat = isset($_GET['status']) ? $_GET['status'] : '';
$where = ['1=1'];
if ($fstat) $where[] = "booking_status='".mysqli_real_escape_string($conn,$fstat)."'";
if ($fs) {
    $s=mysqli_real_escape_string($conn,$fs);
    $where[] = "(booking_id LIKE '%$s%' OR guest_name LIKE '%$s%' OR hotel_name LIKE '%$s%' OR guest_email LIKE '%$s%')";
}
$whereSQL = implode(' AND ',$where);
$bookings=[];
$res=mysqli_query($conn,"SELECT * FROM bookings WHERE $whereSQL ORDER BY created_at DESC LIMIT 300");
if($res) while($row=mysqli_fetch_assoc($res)) $bookings[]=$row;

$pageTitle='Booking Oversight';
$pageSubtitle='Reservations, cancellations, and booking workflow · Live DB';
include 'partials/header.php';
?>
<section class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat blue"><div class="ds-si"><i class="bi bi-calendar2-check-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo (int)$stats['total']; ?></div><div class="ds-sl">Total Bookings</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat green"><div class="ds-si"><i class="bi bi-check-circle-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo (int)$stats['confirmed']; ?></div><div class="ds-sl">Confirmed</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat gold"><div class="ds-si"><i class="bi bi-hourglass-split"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo (int)$stats['pending']; ?></div><div class="ds-sl">Pending</div></div></div>
  <div class="col-12 col-md-6 col-xl-3"><div class="ds-stat red"><div class="ds-si"><i class="bi bi-x-circle-fill"></i></div><div class="ds-sn"><?php
require_once 'auth_guard.php'; echo (int)$stats['cancelled']; ?></div><div class="ds-sl">Cancelled</div></div></div>
</section>

<div class="ds-card">
  <div class="ds-ch">
    <div class="ds-ct"><i class="bi bi-calendar2-check-fill"></i> Reservation Queue (<?php
require_once 'auth_guard.php'; echo count($bookings); ?>)</div>
    <div class="d-flex flex-wrap gap-2">
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <div class="ds-sw"><i class="bi bi-search ds-si-ic"></i>
          <input class="ds-inp search" name="q" placeholder="Search bookings..." value="<?php
require_once 'auth_guard.php'; echo htmlspecialchars($fs); ?>" style="width:200px"/>
        </div>
        <select class="ds-inp ds-sel" name="status" style="width:140px" onchange="this.form.submit()">
          <option value="">All</option>
          <?php
require_once 'auth_guard.php'; foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
          <option value="<?php
require_once 'auth_guard.php'; echo $s;?>" <?php
require_once 'auth_guard.php'; echo $fstat===$s?'selected':'';?>><?php
require_once 'auth_guard.php'; echo ucwords(str_replace('_',' ',$s));?></option>
          <?php
require_once 'auth_guard.php'; endforeach; ?>
        </select>
        <button type="submit" class="ds-btn prim sm"><i class="bi bi-search"></i></button>
        <a href="bookings.php" class="ds-btn gho sm">Clear</a>
      </form>
    </div>
  </div>
  <div class="ds-cb">
    <?php
require_once 'auth_guard.php'; if(empty($bookings)): ?>
    <div class="text-center py-5">
      <i class="bi bi-calendar-x" style="font-size:3rem;color:#cbd5e1"></i>
      <div class="fw-700 mt-3 text-muted">No bookings found</div>
      <div class="text-muted small"><?php
require_once 'auth_guard.php'; echo $fs||$fstat?'Try clearing filters.':'Bookings will appear once users complete payment.';?></div>
    </div>
    <?php
require_once 'auth_guard.php'; else: ?>
    <div style="overflow-x:auto">
      <table class="ds-tbl">
        <thead><tr>
          <th>Booking ID</th><th>Guest</th><th>Hotel</th><th>Room</th>
          <th>Check-in</th><th>Check-out</th><th>Amount</th>
          <th>Payment</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php
require_once 'auth_guard.php'; foreach($bookings as $b):
          $sc=['confirmed'=>'confirmed','pending'=>'pending','checked_in'=>'checkin','checked_out'=>'checkout','cancelled'=>'cancelled'];
          $pc=$b['payment_status']==='paid'?'confirmed':($b['payment_status']==='failed'?'cancelled':'pending');
        ?>
          <tr>
            <td><span class="fw-700 small" style="color:#1a56db"><?php
require_once 'auth_guard.php'; echo htmlspecialchars($b['booking_id']);?></span></td>
            <td>
              <div class="fw-600 small"><?php
require_once 'auth_guard.php'; echo htmlspecialchars($b['guest_name']);?></div>
              <div style="font-size:.72rem;color:#64748b"><?php
require_once 'auth_guard.php'; echo htmlspecialchars($b['guest_email']);?></div>
            </td>
            <td>
              <div class="fw-600 small"><?php
require_once 'auth_guard.php'; echo htmlspecialchars($b['hotel_name']);?></div>
              <div style="font-size:.72rem;color:#64748b"><?php
require_once 'auth_guard.php'; echo htmlspecialchars(ucfirst($b['hotel_city']??''));?></div>
            </td>
            <td class="small"><?php
require_once 'auth_guard.php'; echo htmlspecialchars($b['room_type']);?></td>
            <td class="small fw-600"><?php
require_once 'auth_guard.php'; echo date('d M Y',strtotime($b['checkin_date']));?></td>
            <td class="small fw-600"><?php
require_once 'auth_guard.php'; echo date('d M Y',strtotime($b['checkout_date']));?></td>
            <td class="fw-700 small">₹<?php
require_once 'auth_guard.php'; echo number_format((float)$b['total_amount']);?></td>
            <td><span class="ds-badge <?php
require_once 'auth_guard.php'; echo $pc;?>"><?php
require_once 'auth_guard.php'; echo ucfirst($b['payment_status']);?></span></td>
            <td>
              <select class="ds-inp ds-sel" style="font-size:.75rem;padding:2px 6px"
                onchange="updateStatus('<?php
require_once 'auth_guard.php'; echo $b['booking_id'];?>',this.value,this)">
                <?php
require_once 'auth_guard.php'; foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $s):?>
                <option value="<?php
require_once 'auth_guard.php'; echo $s;?>" <?php
require_once 'auth_guard.php'; echo $b['booking_status']===$s?'selected':'';?>><?php
require_once 'auth_guard.php'; echo ucwords(str_replace('_',' ',$s));?></option>
                <?php
require_once 'auth_guard.php'; endforeach;?>
              </select>
            </td>
            <td><button class="ds-btn gho sm" onclick='viewBooking(<?php
require_once 'auth_guard.php'; echo json_encode($b);?>)'><i class="bi bi-eye-fill"></i> Details</button></td>
          </tr>
        <?php
require_once 'auth_guard.php'; endforeach;?>
        </tbody>
      </table>
    </div>
    <?php
require_once 'auth_guard.php'; endif;?>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal fade ds-modal" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Booking Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="detailBody"></div>
    </div>
  </div>
</div>

<script>
function updateStatus(id,status,el){
  fetch('bookings.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=update_status&booking_id='+encodeURIComponent(id)+'&status='+encodeURIComponent(status)})
  .then(r=>r.json()).then(d=>{
    if(d.success) dsToast('Status updated','success'); else dsToast('Failed','error');
  });
}
function viewBooking(b){
  const fmt=s=>s?new Date(s).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}):'—';
  document.getElementById('detailBody').innerHTML=`
    <div class="row g-3">
      <div class="col-md-6"><div class="ds-lbl">Booking ID</div><div class="fw-700 text-primary">${b.booking_id}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Booked On</div><div>${fmt(b.created_at)}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Guest</div><div class="fw-600">${b.guest_name}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Email</div><div>${b.guest_email}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Hotel</div><div class="fw-700">${b.hotel_name}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Room</div><div>${b.room_type}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Check-in</div><div class="fw-600">${fmt(b.checkin_date)}</div></div>
      <div class="col-md-6"><div class="ds-lbl">Check-out</div><div class="fw-600">${fmt(b.checkout_date)}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Nights</div><div>${b.nights}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Guests</div><div>${b.guests}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Payment</div><div>${b.payment_method||'—'}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Pay Status</div><div>${b.payment_status}</div></div>
      <div class="col-12"><hr/></div>
      <div class="col-md-3"><div class="ds-lbl">Base</div><div>₹${Number(b.base_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Discount</div><div class="text-success">−₹${Number(b.discount_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Tax</div><div>₹${Number(b.tax_amount).toLocaleString('en-IN')}</div></div>
      <div class="col-md-3"><div class="ds-lbl">Total</div><div class="fw-800 text-primary fs-5">₹${Number(b.total_amount).toLocaleString('en-IN')}</div></div>
      ${b.special_requests?`<div class="col-12"><div class="ds-lbl">Special Requests</div><div>${b.special_requests}</div></div>`:''}
    </div>`;
  new bootstrap.Modal(document.getElementById('detailModal')).show();
}
</script>
<?php
require_once 'auth_guard.php'; include 'partials/footer.php'; ?>

