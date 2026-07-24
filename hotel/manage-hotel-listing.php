<?php
session_start();
require_once 'db.php';
require_once 'auth_guard.php';
require_once 'hotel_functions.php';

$user_id = $_SESSION['hm_id'] ?? 0;
$msg = '';
$msg_type = 'success';

$manager = getCurrentHotelManager();
$manager_name = $manager ? ($manager['first_name'] . ' ' . $manager['last_name']) : 'Hotel Manager';
$manager_initials = $manager ? strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1)) : 'M';
$manager_firstname = $manager ? $manager['first_name'] : '';
$manager_role = $manager ? ucwords(str_replace('_', ' ', $manager['role'])) : 'Hotel Manager';

// Fetch assigned hotel for this manager
$hotels = bhGetHotels('', 0, 0, 0, $user_id);
$hotel = $hotels[0] ?? null;

// Fetch active cities for dropdown
$cities_list = [];
$cr = mysqli_query($conn, "SELECT id, city_name, state FROM cities WHERE status='active' ORDER BY city_name ASC");
if ($cr) while ($row = mysqli_fetch_assoc($cr)) $cities_list[] = $row;

if (!$hotel) {
    $msg = 'No hotel assigned to you yet. Please contact the Main Admin.';
    $msg_type = 'warning';
}

// Handle POST - update hotel listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hotel) {
    $hotel_id = (int)$hotel['hotel_id'];
    
    $amenities_arr = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
    $amenities_str = implode(',', array_map('trim', $amenities_arr));
    
    $images_json = $hotel['hotel_images'] ?? '[]';
    $uploaded_path = bhHandleImageUpload('hotel_image', $hotel_id);
    if ($uploaded_path) {
        $existing = json_decode($images_json, true) ?: [];
        $existing[] = $uploaded_path;
        $images_json = json_encode(array_values($existing));
    } elseif (!empty($_POST['image_url'])) {
        $existing = json_decode($images_json, true) ?: [];
        $existing[] = trim($_POST['image_url']);
        $images_json = json_encode(array_values($existing));
    }
    
    $data = [
        'hotel_name'          => trim($_POST['hotel_name'] ?? $hotel['hotel_name']),
        'city'                => strtolower(trim($_POST['city'] ?? $hotel['city'])),
        'location'            => trim($_POST['location'] ?? $hotel['location']),
        'state'               => trim($_POST['state'] ?? $hotel['state']),
        'description'         => trim($_POST['description'] ?? $hotel['description']),
        'price_per_night'     => (float)($_POST['price_per_night'] ?? $hotel['price_per_night']),
        'original_price'      => (float)($_POST['original_price'] ?? $hotel['original_price']),
        'discount_percentage' => (float)($_POST['discount_percentage'] ?? $hotel['discount_percentage']),
        'gst_percentage'      => (float)($_POST['gst_percentage'] ?? $hotel['gst_percentage']),
        'rating'              => (float)($_POST['rating'] ?? $hotel['rating']),
        'star_rating'         => (int)($_POST['star_rating'] ?? $hotel['star_rating']),
        'property_type'       => $_POST['property_type'] ?? $hotel['property_type'],
        'amenities'           => $amenities_str,
        'capacity'            => (int)($_POST['capacity'] ?? $hotel['capacity']),
        'availability_status' => $_POST['availability_status'] ?? $hotel['availability_status'],
        'hotel_images'        => $images_json,
        'featured'            => isset($_POST['featured']) ? 1 : 0,
        'checkin_time'        => $_POST['checkin_time'] ?? $hotel['checkin_time'],
        'checkout_time'       => $_POST['checkout_time'] ?? $hotel['checkout_time'],
        'phone'               => trim($_POST['phone'] ?? $hotel['phone']),
        'email'               => trim($_POST['email'] ?? $hotel['email']),
    ];
    
    $ok = bhUpdateHotel($hotel_id, $data);
    $msg = $ok ? 'Hotel listing updated successfully!' : 'Error updating hotel listing.';
    $msg_type = $ok ? 'success' : 'danger';
    
    if ($ok) {
        $hotel = bhGetHotelById($hotel_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Hotel Listing | Hotel Operations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
  <div class="ds-ov" id="dsOv"></div>
  <aside class="ds-sb" id="dsSb">
    <a href="admin-dashboard.php" class="ds-logo">
      <div class="ds-logo-icon"><i class="bi bi-buildings"></i></div>
      <div>
        <div class="ds-logo-name">BookHotel</div>
        <div class="ds-logo-role">Hotel Operations</div>
      </div>
    </a>
    <nav class="ds-nav" id="mainSidebar">
      <div class="ds-sec">Main</div>
      <a href="admin-dashboard.php" class="ds-link"><i class="bi bi-grid-fill"></i> Dashboard</a>
      <a href="manage-bookings.php" class="ds-link"><i class="bi bi-calendar2-check-fill"></i> Manage Bookings</a>
      <a href="check-in-order.php" class="ds-link"><i class="bi bi-person-check-fill"></i> Check In Order</a>
      <a href="manage-hotel-listing.php" class="ds-link active"><i class="bi bi-card-checklist"></i> Manage Hotel Listing</a>
      <a href="manage-rooms.php" class="ds-link"><i class="bi bi-door-open-fill"></i> Manage Rooms</a>
      <a href="view-ratings.php" class="ds-link"><i class="bi bi-star-fill"></i> View Ratings</a>
      <a href="transaction-history.php" class="ds-link"><i class="bi bi-cash-stack"></i> Transaction History</a>
      <a href="logout.php" class="ds-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </nav>
    <script>document.addEventListener("DOMContentLoaded",()=>{let c=location.pathname.split("/").pop()||"admin-dashboard.php";document.querySelectorAll("#mainSidebar a").forEach(l=>{l.getAttribute("href")===c?l.classList.add("active"):l.classList.remove("active")})});</script>
    <div class="ds-foot">
      <a href="#" class="ds-hpill">
        <div class="ds-av" style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#0f172a;font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;"><?= $manager_initials ?></div>
        <div>
          <div class="ds-hpill-name"><?= htmlspecialchars($manager_name) ?></div>
          <div class="ds-hpill-status">● <?= htmlspecialchars($manager_role) ?></div>
        </div>
      </a>
    </div>
  </aside>

  <header class="ds-top">
    <div class="ds-top-l">
      <button class="ds-ibtn d-lg-none" id="dsTog"><i class="bi bi-list fs-5"></i></button>
      <div>
        <div class="ds-page-title">Manage Hotel Listing</div>
        <div class="ds-breadcrumb">Update your assigned hotel listing details</div>
      </div>
    </div>
    <div class="ds-top-r">
      <div class="ds-avbtn" id="dsAvBtn">
        <div class="ds-av"><?= $manager_initials ?></div>
        <span class="ds-avname d-none d-sm-block"><?= htmlspecialchars($manager_firstname ?: $manager_name) ?></span>
      </div>
    </div>
  </header>

  <main class="ds-main">
    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show mx-3 mt-2" role="alert">
      <i class="bi bi-<?php echo $msg_type==='success'?'check-circle-fill':'exclamation-triangle-fill'; ?> me-2"></i>
      <?php echo htmlspecialchars($msg); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!$hotel): ?>
    <div class="ds-card mx-3 mt-3">
      <div class="ds-cb text-center py-5">
        <i class="bi bi-building-x" style="font-size: 3rem; color: #94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Hotel Assigned</h5>
        <p class="text-muted">You do not have any hotel assigned yet. Please contact the Main Admin to get a hotel assigned to your account.</p>
      </div>
    </div>
    <?php else: ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="row g-4">
        <div class="col-12 col-lg-8">
          <div class="ds-card mb-4">
            <div class="ds-ch">
              <div class="ds-ct"><i class="bi bi-building me-2"></i>Hotel Information</div>
              <span class="ds-badge <?php echo ($hotel['approval_status'] ?? 'approved') === 'approved' ? 'confirmed' : (($hotel['approval_status'] ?? 'approved') === 'pending' ? 'pending' : 'cancelled'); ?>">
                <?php echo ucfirst($hotel['approval_status'] ?? 'Approved'); ?>
              </span>
            </div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label fw-600 small">Hotel Name</label>
                  <input type="text" class="ds-inp" name="hotel_name" value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600 small">City</label>
                  <select class="ds-inp ds-sel" name="city" required>
                    <option value="">Select City</option>
                    <?php foreach ($cities_list as $c): ?>
                      <option value="<?php echo htmlspecialchars(strtolower($c['city_name'])); ?>" <?php echo (strtolower($hotel['city'] ?? '') === strtolower($c['city_name'])) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['city_name'] . ($c['state'] ? ', ' . $c['state'] : '')); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600 small">State</label>
                  <input type="text" class="ds-inp" name="state" value="<?php echo htmlspecialchars($hotel['state'] ?? ''); ?>" />
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-600 small">Full Address</label>
                  <textarea class="ds-inp" name="location" rows="2"><?php echo htmlspecialchars($hotel['location'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-600 small">Description</label>
                  <textarea class="ds-inp" name="description" rows="4"><?php echo htmlspecialchars($hotel['description'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="ds-card mb-4">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-images me-2"></i>Hotel Images</div></div>
            <div class="ds-cb">
              <div class="d-flex gap-3 overflow-auto pb-2" id="imageGallery">
                <?php
                $images = json_decode($hotel['hotel_images'] ?? '[]', true) ?: [];
                foreach ($images as $img):
                ?>
                <div class="position-relative" style="min-width: 150px;">
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="Hotel" class="img-fluid rounded" style="height: 100px; width: 150px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=200&q=80'"/>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="mt-3">
                <label class="form-label fw-600 small">Add Image</label>
                <div class="row g-2">
                  <div class="col-md-6">
                    <input type="file" class="ds-inp" name="hotel_image" accept="image/*" />
                  </div>
                  <div class="col-md-6">
                    <input type="text" class="ds-inp" name="image_url" placeholder="Or paste image URL" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="ds-card mb-4">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-list-check me-2"></i>Amenities & Facilities</div></div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label fw-600 small">Amenities</label>
                  <div class="d-flex flex-wrap gap-2">
                    <?php
                    $all_amenities = ['wifi', 'pool', 'breakfast', 'parking', 'spa', 'gym', 'ac', 'restaurant', 'room-service', 'laundry', 'bar', 'concierge'];
                    $current = array_map('trim', explode(',', $hotel['amenities'] ?? ''));
                    foreach ($all_amenities as $am):
                    ?>
                    <div class="amenity-chip">
                      <input type="checkbox" name="amenities[]" value="<?php echo $am; ?>" id="am_<?php echo $am; ?>" <?php echo in_array($am, $current) ? 'checked' : ''; ?> />
                      <label for="am_<?php echo $am; ?>" style="cursor:pointer"><?php echo ucfirst($am); ?></label>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="ds-card mb-4">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-clock me-2"></i>Policies & Timings</div></div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600 small">Check-in Time</label>
                  <input type="text" class="ds-inp" name="checkin_time" value="<?php echo htmlspecialchars($hotel['checkin_time'] ?? '14:00'); ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600 small">Check-out Time</label>
                  <input type="text" class="ds-inp" name="checkout_time" value="<?php echo htmlspecialchars($hotel['checkout_time'] ?? '11:00'); ?>" />
                </div>
              </div>
            </div>
          </div>

          <div class="ds-card mb-4">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-telephone me-2"></i>Contact Information</div></div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600 small">Phone</label>
                  <input type="text" class="ds-inp" name="phone" value="<?php echo htmlspecialchars($hotel['phone'] ?? ''); ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600 small">Email</label>
                  <input type="email" class="ds-inp" name="email" value="<?php echo htmlspecialchars($hotel['email'] ?? ''); ?>" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="ds-card mb-4 border-success" style="border-width: 2px;">
            <div class="ds-ch bg-success bg-opacity-10 border-bottom-0">
              <div class="ds-ct text-success"><i class="bi bi-patch-check-fill me-2"></i>Listing Status</div>
            </div>
            <div class="ds-cb text-center py-4">
              <h4 class="fw-800 text-success mb-2"><?php echo ucfirst($hotel['approval_status'] ?? 'Approved'); ?></h4>
              <p class="text-muted small mb-0">
                <?php if (($hotel['approval_status'] ?? 'approved') === 'approved'): ?>
                  Your listing is live on the BookHotel platform and visible to all customers.
                <?php elseif (($hotel['approval_status'] ?? 'approved') === 'pending'): ?>
                  Your listing is pending admin approval. It will be visible to customers once approved.
                <?php else: ?>
                  Your listing has been rejected. Please update the details and contact admin for re-approval.
                <?php endif; ?>
              </p>
            </div>
          </div>

          <div class="ds-card mb-4">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-gear me-2"></i>Visibility</div></div>
            <div class="ds-cb">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="availability_status" value="active" id="visibilityToggle" <?php echo ($hotel['availability_status'] ?? 'active') === 'active' ? 'checked' : ''; ?>>
                <label class="form-check-label fw-600" for="visibilityToggle">Listing Visible to Customers</label>
              </div>
              <p class="text-muted small mt-2">When disabled, your hotel will not appear in search results.</p>
            </div>
          </div>

          <div class="ds-card">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-star me-2"></i>Rating & Pricing</div></div>
            <div class="ds-cb">
              <div class="row g-3">
                <div class="col-6">
                  <label class="form-label fw-600 small">Rating</label>
                  <input type="number" step="0.1" class="ds-inp" name="rating" value="<?php echo htmlspecialchars($hotel['rating'] ?? '4.0'); ?>" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-600 small">Star Rating</label>
                  <select class="ds-inp" name="star_rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($hotel['star_rating'] ?? 3) == $i ? 'selected' : ''; ?>><?php echo $i; ?> Star</option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label fw-600 small">Price/Night (₹)</label>
                  <input type="number" step="0.01" class="ds-inp" name="price_per_night" value="<?php echo htmlspecialchars($hotel['price_per_night'] ?? '0'); ?>" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-600 small">Original Price (₹)</label>
                  <input type="number" step="0.01" class="ds-inp" name="original_price" value="<?php echo htmlspecialchars($hotel['original_price'] ?? '0'); ?>" />
                </div>
              </div>
            </div>
          </div>

          <div class="ds-card">
            <div class="ds-ch"><div class="ds-ct"><i class="bi bi-chat-left-text me-2"></i>Admin Remarks</div></div>
            <div class="ds-cb">
              <div class="p-3 bg-light rounded border-start border-4 border-primary">
                <p class="mb-1 small">"Everything looks great! The new professional photos were approved."</p>
                <small class="text-muted fw-600">- System Admin (12 Jun 2026)</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="fixed-bottom p-3 bg-white border-top">
        <div class="container">
          <div class="d-flex justify-content-end gap-2">
            <a href="admin-dashboard.php" class="ds-btn gho">Cancel</a>
            <button type="submit" class="ds-btn prim"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
          </div>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="dashboard.js"></script>
</body>
</html>
