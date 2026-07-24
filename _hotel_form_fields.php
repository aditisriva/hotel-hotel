<?php
// Shared form fields used by both Add Modal and Edit inline form.
// $eh = null for add, $eh = hotel array for edit.
$v = function($field, $default='') use ($eh) {
    return htmlspecialchars($eh[$field] ?? $default);
};
$amenity_list = ['wifi','pool','breakfast','parking','ac','gym','spa','bar','restaurant','fireplace'];
$cur_amenities = $eh ? array_map('trim', explode(',', $eh['amenities'] ?? '')) : [];

$cities_list = [];
if (isset($conn)) {
    $cr = mysqli_query($conn, "SELECT id, city_name, state FROM cities WHERE status='active' ORDER BY city_name ASC");
    if ($cr) while ($row = mysqli_fetch_assoc($cr)) $cities_list[] = $row;
}
?>
<div class="row g-3">
  <div class="col-md-8">
    <label class="ds-lbl">Hotel Name <span class="text-danger">*</span></label>
    <input class="ds-inp" name="hotel_name" required value="<?php echo $v('hotel_name'); ?>" placeholder="e.g. The Grand Palace"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Star Rating</label>
    <select class="ds-inp ds-sel" name="star_rating">
      <?php for($s=1;$s<=5;$s++): ?>
      <option value="<?php echo $s; ?>" <?php echo ($eh&&(int)$eh['star_rating']===$s)?'selected':''; ?>><?php echo $s; ?> Star</option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">City <span class="text-danger">*</span></label>
    <select class="ds-inp ds-sel" name="city_id" required onchange="this.form.querySelector('[name=city]').value=this.options[this.selectedIndex].textContent">
      <option value="">Select City</option>
      <?php foreach($cities_list as $c): ?>
      <option value="<?php echo $c['id']; ?>" <?php echo ($eh && (int)$eh['city_id']===(int)$c['id']) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($c['city_name'] . ($c['state'] ? ', ' . $c['state'] : '')); ?>
      </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="city" value="<?php echo $v('city'); ?>"/>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">State</label>
    <input class="ds-inp" name="state" value="<?php echo $v('state'); ?>" placeholder="e.g. Maharashtra"/>
  </div>
  <div class="col-12">
    <label class="ds-lbl">Full Location / Address</label>
    <input class="ds-inp" name="location" value="<?php echo $v('location'); ?>" placeholder="e.g. Marine Drive, Mumbai"/>
  </div>
  <div class="col-12">
    <label class="ds-lbl">Description</label>
    <textarea class="ds-inp" name="description" rows="3"><?php echo $v('description'); ?></textarea>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Price / Night (₹) <span class="text-danger">*</span></label>
    <input class="ds-inp" type="number" name="price_per_night" required min="0" step="0.01"
           value="<?php echo $v('price_per_night','0'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Original Price (₹)</label>
    <input class="ds-inp" type="number" name="original_price" min="0" step="0.01"
           value="<?php echo $v('original_price','0'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Guest Rating (0–5)</label>
    <input class="ds-inp" type="number" name="rating" min="0" max="5" step="0.1"
           value="<?php echo $v('rating','4.0'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">GST %</label>
    <input class="ds-inp" type="number" name="gst_percentage" min="0" max="28" step="0.01"
           value="<?php echo $v('gst_percentage','12'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Max Guests</label>
    <input class="ds-inp" type="number" name="capacity" min="1" max="20"
           value="<?php echo $v('capacity','2'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Property Type</label>
    <select class="ds-inp ds-sel" name="property_type">
      <?php foreach(['hotel','resort','villa','homestay','boutique-hotel'] as $pt): ?>
      <option value="<?php echo $pt; ?>" <?php echo ($eh&&$eh['property_type']===$pt)?'selected':''; ?>><?php echo ucwords(str_replace('-',' ',$pt)); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Check-in Time</label>
    <input class="ds-inp" type="time" name="checkin_time" value="<?php echo $v('checkin_time','14:00'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Check-out Time</label>
    <input class="ds-inp" type="time" name="checkout_time" value="<?php echo $v('checkout_time','11:00'); ?>"/>
  </div>
  <div class="col-md-4">
    <label class="ds-lbl">Status</label>
    <select class="ds-inp ds-sel" name="availability_status">
      <?php foreach(['active','inactive','maintenance'] as $st): ?>
      <option value="<?php echo $st; ?>" <?php echo ($eh&&$eh['availability_status']===$st)?'selected':''; ?>><?php echo ucfirst($st); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">Phone</label>
    <input class="ds-inp" name="phone" value="<?php echo $v('phone'); ?>" placeholder="+91 XXXXXXXXXX"/>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">Email</label>
    <input class="ds-inp" type="email" name="email" value="<?php echo $v('email'); ?>" placeholder="hotel@example.com"/>
  </div>
  <div class="col-12">
    <label class="ds-lbl">Amenities</label>
    <div class="d-flex flex-wrap gap-2 mt-1">
      <?php foreach($amenity_list as $am): $checked = in_array($am,$cur_amenities); ?>
      <label class="amenity-chip <?php echo $checked?'selected':''; ?>">
        <input type="checkbox" name="amenities[]" value="<?php echo $am; ?>" <?php echo $checked?'checked':''; ?>
               onchange="this.closest('label').classList.toggle('selected',this.checked)"/>
        <i class="bi <?php echo ['wifi'=>'bi-wifi','pool'=>'bi-droplet-fill','breakfast'=>'bi-cup-hot','parking'=>'bi-car-front','ac'=>'bi-fan','gym'=>'bi-dumbbell','spa'=>'bi-flower1','bar'=>'bi-cup-straw','restaurant'=>'bi-shop','fireplace'=>'bi-fire'][$am]??'bi-check'; ?>"></i>
        <?php echo ucfirst($am); ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">Hotel Image (Upload)</label>
    <input class="ds-inp" type="file" name="hotel_image" accept="image/*"
           onchange="previewImage(this,'imgPreview<?php echo $eh?$eh['hotel_id']:'New'; ?>')"/>
    <img id="imgPreview<?php echo $eh?$eh['hotel_id']:'New'; ?>"
         src="<?php echo $eh ? htmlspecialchars(bhFirstImage($eh['hotel_images']??'','')) : ''; ?>"
         class="img-preview mt-2 <?php echo ($eh&&$eh['hotel_images'])?'':'d-none'; ?>" alt="Preview"/>
  </div>
  <div class="col-md-6">
    <label class="ds-lbl">Or Image URL</label>
    <input class="ds-inp" name="image_url" type="url"
           value="<?php echo $eh ? htmlspecialchars(bhFirstImage($eh['hotel_images']??'','')) : ''; ?>"
           placeholder="https://images.unsplash.com/..."/>
  </div>
  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck<?php echo $eh?$eh['hotel_id']:'New'; ?>"
             <?php echo ($eh&&$eh['featured'])?'checked':''; ?>>
      <label class="form-check-label fw-600" for="featuredCheck<?php echo $eh?$eh['hotel_id']:'New'; ?>">
        <i class="bi bi-star-fill text-warning me-1"></i>Feature this hotel on homepage
      </label>
    </div>
  </div>
</div>
