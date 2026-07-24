<?php
/**
 * hotel_functions.php — Shared DB helpers for hotels
 * Include wherever hotel DB queries are needed.
 */

if (!isset($conn)) require_once __DIR__ . '/db.php';

// ── Seed default hotels if table is empty ──────────────────────────────────
function bhSeedHotels(): void {
    global $conn;
    $chk = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM hotels");
    if (!$chk) return;
    $row = mysqli_fetch_assoc($chk);
    if ((int)$row['cnt'] > 0) return; // already seeded

    $city_map = [];
    $cr = mysqli_query($conn, "SELECT id, LOWER(city_name) AS slug FROM cities");
    if ($cr) while ($r = mysqli_fetch_assoc($cr)) $city_map[$r['slug']] = (int)$r['id'];

    $seeds = [
        ["The Grand Palace",       "mumbai",  "Marine Drive, Mumbai",          "Maharashtra",      "Iconic luxury hotel overlooking the Arabian Sea with world-class dining and premium spa facilities.",       4299.00, 6500.00, 33.86, 12, 4.8, 5, "hotel",         "wifi,pool,breakfast,parking,spa,gym,ac", 4, 'active', '["https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80"]', 1, '14:00', '11:00', '+91 22 1234 5678', 'info@grandpalace.com'],
        ["Sunset Beach Resort",    "goa",     "Calangute, North Goa",          "Goa",              "Beachfront resort with stunning ocean views, water sports, and award-winning seafood restaurant.",           5499.00, 8000.00, 31.26, 12, 4.6, 5, "resort",        "wifi,pool,parking,ac",                  4, 'active', '["https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=800&q=80"]', 1, '14:00', '11:00', '+91 832 987 6543', 'info@sunsetbeachgoa.com'],
        ["Heritage Haveli",        "jaipur",  "M.I. Road, Pink City, Jaipur",  "Rajasthan",        "Royal heritage property with authentic Rajasthani architecture, cultural performances and royal dining.",    4680.00, 7200.00, 35.00, 12, 4.9, 5, "boutique-hotel","wifi,breakfast,ac,spa",                4, 'active', '["https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&q=80"]', 1, '14:00', '11:00', '+91 141 456 7890', 'info@heritagehaveli.com'],
        ["Mountain View Lodge",    "manali",  "Old Manali Road, Manali",       "Himachal Pradesh", "Cosy mountain retreat with panoramic Himalayan views, wood-fired fireplaces and adventure activities.",     3299.00, 5500.00, 40.02, 12, 4.7, 4, "hotel",         "wifi,breakfast,ac",                     2, 'active', '["https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80"]', 0, '14:00', '11:00', '+91 1902 234 567', 'info@mountainviewmanali.com'],
        ["Lake Palace Udaipur",    "udaipur", "Lake Pichola, Udaipur",         "Rajasthan",        "Floating palace on Lake Pichola offering unparalleled royal luxury with stunning sunset views.",            12499.00,18000.00,30.56, 12, 4.9, 5, "resort",        "wifi,pool,spa,breakfast,parking,ac,gym",6, 'active', '["https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80"]', 1, '14:00', '11:00', '+91 294 345 6789', 'info@lakepalaceudaipur.com'],
        ["Kerala Backwater Resort","kerala",  "Alleppey Backwaters, Kerala",   "Kerala",           "Serene resort on the famous backwaters with houseboat experiences and Ayurvedic treatments.",               6799.00, 9000.00, 24.46, 12, 4.8, 5, "resort",        "wifi,breakfast,spa,ac",                 4, 'active', '["https://images.unsplash.com/photo-1582610116397-edb318620f90?w=800&q=80"]', 1, '14:00', '11:00', '+91 479 456 7890', 'info@keralabackwater.com'],
        ["Zen Garden Resort",      "kerala",  "Munnar Tea Estates, Kerala",    "Kerala",           "Nestled in lush tea plantations with valley views, yoga retreats, and organic farm dining.",                4100.00, 6500.00, 36.92, 12, 4.5, 4, "boutique-hotel","wifi,breakfast,ac",                     2, 'active', '["https://images.unsplash.com/photo-1561501900-3701fa6a0864?w=800&q=80"]', 0, '14:00', '11:00', '+91 486 567 8901', 'info=zengardenkerala.com'],
        ["The Imperial Delhi",     "delhi",   "Janpath, New Delhi",            "Delhi",            "Historic luxury hotel in the heart of New Delhi with colonial charm and modern five-star amenities.",       8799.00,11000.00, 20.01, 12, 4.7, 5, "hotel",         "wifi,pool,parking,ac,gym",              4, 'active', '["https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&q=80"]', 0, '14:00', '11:00', '+91 11 2345 6789', 'info@imperialdelhi.com'],
    ];

    $stmt = mysqli_prepare($conn,
        "INSERT INTO hotels (hotel_name,city_id,city,location,state,description,price_per_night,original_price,discount_percentage,gst_percentage,rating,star_rating,property_type,amenities,capacity,availability_status,hotel_images,featured,checkin_time,checkout_time,phone,email)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($seeds as $s) {
        $cid = $city_map[$s[1]] ?? null;
        mysqli_stmt_bind_param($stmt, 'sissssdddddissississss',
            $s[0],$cid,$s[1],$s[2],$s[3],$s[4],$s[5],$s[6],$s[7],$s[8],$s[9],$s[10],$s[11],$s[12],$s[13],$s[14],$s[15],$s[16],$s[17],$s[18],$s[19],$s[20]
        );
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// ── Fetch all active hotels (with optional filters) ───────────────────────
function bhGetHotels(string $city = '', int $guests = 0, float $maxPrice = 0, float $minRating = 0, int $assignedTo = 0, int $cityId = 0): array {
    global $conn;
    $where = ["h.availability_status = 'active'", "h.approval_status = 'approved'", "(h.city_id IS NULL OR c.status = 'active')"];
    $params = [];
    $types  = '';

    if ($cityId > 0) {
        $where[] = "h.city_id = ?";
        $params[] = $cityId;
        $types   .= 'i';
    } elseif ($city) {
        $where[] = "LOWER(h.city) = ?";
        $params[] = strtolower(trim($city));
        $types   .= 's';
    }
    if ($guests > 0) {
        $where[] = "h.capacity >= ?";
        $params[] = $guests;
        $types   .= 'i';
    }
    if ($maxPrice > 0) {
        $where[] = "h.price_per_night <= ?";
        $params[] = $maxPrice;
        $types   .= 'd';
    }
    if ($minRating > 0) {
        $where[] = "h.rating >= ?";
        $params[] = $minRating;
        $types   .= 'd';
    }
    if ($assignedTo > 0) {
        $where[] = "h.assigned_to = ?";
        $params[] = $assignedTo;
        $types   .= 'i';
    }

    $sql = "SELECT h.* FROM hotels h LEFT JOIN cities c ON h.city_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY h.featured DESC, h.rating DESC";
    if ($params) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $res = mysqli_query($conn, $sql);
    }
    $hotels = [];
    while ($row = mysqli_fetch_assoc($res)) $hotels[] = $row;
    return $hotels;
}

// ── Fetch single hotel by ID ───────────────────────────────────────────────
function bhGetHotelById(int $id): ?array {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM hotels WHERE hotel_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $row  = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// ── Insert new hotel ───────────────────────────────────────────────────────
function bhInsertHotel(array $d): int|false {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "INSERT INTO hotels (hotel_name,city_id,city,location,state,description,price_per_night,original_price,
         discount_percentage,gst_percentage,rating,star_rating,property_type,amenities,capacity,
         availability_status,hotel_images,featured,checkin_time,checkout_time,phone,email)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $city_id = isset($d['city_id']) && $d['city_id'] > 0 ? (int)$d['city_id'] : null;
    $city_name = $d['city'] ?? '';
    if ($city_id) {
        $cr = mysqli_query($conn, "SELECT city_name FROM cities WHERE id=$city_id");
        if ($cr && ($row = mysqli_fetch_assoc($cr))) {
            $city_name = strtolower($row['city_name']);
        } else {
            $city_id = null;
        }
    }
    if (empty($city_name)) {
        trigger_error('bhInsertHotel: city name is empty', E_USER_WARNING);
        return false;
    }
    $featured = (int)($d['featured'] ?? 0);
    mysqli_stmt_bind_param($stmt, 'sissssdddddissississss',
        $d['hotel_name'], $city_id, $city_name, $d['location'], $d['state'], $d['description'],
        $d['price_per_night'], $d['original_price'], $d['discount_percentage'], $d['gst_percentage'],
        $d['rating'], $d['star_rating'], $d['property_type'], $d['amenities'], $d['capacity'],
        $d['availability_status'], $d['hotel_images'], $featured,
        $d['checkin_time'], $d['checkout_time'], $d['phone'], $d['email']
    );
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return $id;
    }
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    trigger_error('bhInsertHotel failed: '.$err, E_USER_WARNING);
    return false;
}

// ── Update existing hotel ──────────────────────────────────────────────────
function bhUpdateHotel(int $id, array $d): bool {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "UPDATE hotels SET hotel_name=?,city_id=?,city=?,location=?,state=?,description=?,price_per_night=?,
         original_price=?,discount_percentage=?,gst_percentage=?,rating=?,star_rating=?,property_type=?,
         amenities=?,capacity=?,availability_status=?,hotel_images=?,featured=?,
         checkin_time=?,checkout_time=?,phone=?,email=?
         WHERE hotel_id=?"
    );
    $city_id = isset($d['city_id']) && $d['city_id'] > 0 ? (int)$d['city_id'] : null;
    $city_name = $d['city'] ?? '';
    if ($city_id) {
        $cr = mysqli_query($conn, "SELECT city_name FROM cities WHERE id=$city_id");
        if ($cr && ($row = mysqli_fetch_assoc($cr))) {
            $city_name = strtolower($row['city_name']);
        } else {
            $city_id = null;
        }
    }
    if (empty($city_name)) {
        trigger_error('bhUpdateHotel: city name is empty', E_USER_WARNING);
        return false;
    }
    $featured = (int)($d['featured'] ?? 0);
    mysqli_stmt_bind_param($stmt, 'sissssdddddissississssi',
        $d['hotel_name'], $city_id, $city_name, $d['location'], $d['state'], $d['description'],
        $d['price_per_night'], $d['original_price'], $d['discount_percentage'], $d['gst_percentage'],
        $d['rating'], $d['star_rating'], $d['property_type'], $d['amenities'], $d['capacity'],
        $d['availability_status'], $d['hotel_images'], $featured,
        $d['checkin_time'], $d['checkout_time'], $d['phone'], $d['email'],
        $id
    );
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $err = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        trigger_error('bhUpdateHotel failed: '.$err, E_USER_WARNING);
        return false;
    }
    mysqli_stmt_close($stmt);
    return true;
}

// ── Delete hotel ───────────────────────────────────────────────────────────
function bhDeleteHotel(int $id): bool {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM hotels WHERE hotel_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// ── Get first image from JSON array ──────────────────────────────────────
function bhFirstImage(string $images_json, string $fallback = ''): string {
    if (!$images_json) return $fallback;
    $arr = json_decode($images_json, true);
    if (is_array($arr) && count($arr) > 0) return $arr[0];
    return $fallback;
}

// ── Get all images as array ────────────────────────────────────────────────
function bhAllImages(string $images_json): array {
    if (!$images_json) return [];
    $arr = json_decode($images_json, true);
    return is_array($arr) ? $arr : [];
}

// ── Amenity icon map ───────────────────────────────────────────────────────
function bhAmenityIcon(string $tag): string {
    $map = [
        'wifi'      => 'bi-wifi',
        'pool'      => 'bi-droplet-fill',
        'breakfast' => 'bi-cup-hot',
        'parking'   => 'bi-car-front',
        'ac'        => 'bi-fan',
        'gym'       => 'bi-dumbbell',
        'spa'       => 'bi-flower1',
        'bar'       => 'bi-cup-straw',
        'restaurant'=> 'bi-shop',
        'fireplace' => 'bi-fire',
    ];
    return $map[strtolower(trim($tag))] ?? 'bi-check-circle';
}

// ── Live stats for admin dashboard ────────────────────────────────────────
function bhHotelStats(): array {
    global $conn;
    $stats = [
        'total'    => 0,
        'active'   => 0,
        'inactive' => 0,
        'featured' => 0,
        'cities'   => 0,
    ];
    $res = mysqli_query($conn,
        "SELECT
            COUNT(*) AS total,
            SUM(availability_status='active') AS active_count,
            SUM(availability_status='inactive') AS inactive_count,
            SUM(featured=1) AS featured_count,
            COUNT(DISTINCT city_id) AS city_count
         FROM hotels"
    );
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $stats['total']    = (int)$row['total'];
        $stats['active']   = (int)$row['active_count'];
        $stats['inactive'] = (int)$row['inactive_count'];
        $stats['featured'] = (int)$row['featured_count'];
        $stats['cities']   = (int)$row['city_count'];
    }
    return $stats;
}

// ── Handle image upload ────────────────────────────────────────────────────
function bhHandleImageUpload(string $field, int $hotelId = 0): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return '';
    $uploadDir = dirname(__DIR__) . '/uploads/hotels/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'hotel_' . ($hotelId ?: time()) . '_' . time() . '.' . $ext;
    $path = $uploadDir . $filename;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $path)) {
        return 'uploads/hotels/' . $filename;
    }
    return '';
}

// ── Room helpers ────────────────────────────────────────────────────────────
function bhGetRoomsByHotel(int $hotel_id): array {
    global $conn;
    $rooms = [];
    $stmt = mysqli_prepare($conn, "SELECT * FROM rooms WHERE hotel_id = ? ORDER BY room_id ASC");
    mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $rooms[] = $row;
    mysqli_stmt_close($stmt);
    return $rooms;
}

function bhGetRoomById(int $room_id): ?array {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM rooms WHERE room_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $room_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function bhInsertRoom(array $d): int|false {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "INSERT INTO rooms (hotel_id, manager_id, room_number, room_type, room_name, floor, adult_capacity, child_capacity, bed_type, base_price, discount_percent, final_price, description, amenities, room_images, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, 'iissssiiisddssss',
        $d['hotel_id'], $d['manager_id'], $d['room_number'], $d['room_type'], $d['room_name'] ?? null,
        $d['floor'] ?? null, $d['adult_capacity'] ?? 2, $d['child_capacity'] ?? 0,
        $d['bed_type'] ?? null, $d['base_price'], $d['discount_percent'] ?? 0, $d['final_price'],
        $d['description'] ?? null, $d['amenities'] ?? null, $d['room_images'] ?? null,
        $d['status'] ?? 'Available'
    );
    $ok = mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $ok ? $id : false;
}

function bhUpdateRoom(int $room_id, array $d): bool {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "UPDATE rooms SET hotel_id=?, manager_id=?, room_number=?, room_type=?, room_name=?, floor=?, adult_capacity=?, child_capacity=?, bed_type=?, base_price=?, discount_percent=?, final_price=?, description=?, amenities=?, room_images=?, status=? WHERE room_id=?"
    );
    mysqli_stmt_bind_param($stmt, 'iissssiiisddssssi',
        $d['hotel_id'], $d['manager_id'], $d['room_number'], $d['room_type'], $d['room_name'] ?? null,
        $d['floor'] ?? null, $d['adult_capacity'] ?? 2, $d['child_capacity'] ?? 0,
        $d['bed_type'] ?? null, $d['base_price'], $d['discount_percent'] ?? 0, $d['final_price'],
        $d['description'] ?? null, $d['amenities'] ?? null, $d['room_images'] ?? null,
        $d['status'] ?? 'Available', $room_id
    );
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function bhDeleteRoom(int $room_id): bool {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE room_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $room_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function bhRoomStats(int $hotel_id): array {
    global $conn;
    $stats = ['total' => 0, 'available' => 0, 'occupied' => 0, 'maintenance' => 0];
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total, SUM(status='Available') AS available, SUM(status='Occupied') AS occupied, SUM(status='Maintenance') AS maintenance FROM rooms WHERE hotel_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if ($row) {
        $stats['total']       = (int)$row['total'];
        $stats['available']   = (int)$row['available'];
        $stats['occupied']    = (int)$row['occupied'];
        $stats['maintenance'] = (int)$row['maintenance'];
    }
    return $stats;
}

function bhHandleRoomImageUpload(string $field, int $roomId = 0): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) return '';
    $uploadDir = __DIR__ . '/uploads/rooms/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'room_' . ($roomId ?: time()) . '_' . time() . '.' . $ext;
    $path = $uploadDir . $filename;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $path)) {
        return 'uploads/rooms/' . $filename;
    }
    return '';
}

// ── Review helpers ──────────────────────────────────────────────────
function bhGetReviewsByHotel(int $hotel_id, int $page = 0, int $per_page = 10): array {
    global $conn;
    $offset = $page * $per_page;
    $stmt = mysqli_prepare($conn, "SELECT r.*, u.first_name, u.last_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.hotel_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'iii', $hotel_id, $per_page, $offset);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $reviews = [];
    while ($row = mysqli_fetch_assoc($res)) $reviews[] = $row;
    mysqli_stmt_close($stmt);
    return $reviews;
}

function bhGetReviewCount(int $hotel_id): int {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM reviews r WHERE r.hotel_id = ? AND r.status = 'approved'");
    mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return (int)($row['c'] ?? 0);
}

function bhGetAverageRating(int $hotel_id): float {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT AVG(rating) AS avg_rating FROM reviews r WHERE r.hotel_id = ? AND r.status = 'approved'");
    mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return round((float)($row['avg_rating'] ?? 0), 1);
}

function bhSubmitReview(array $data): int|false {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO reviews (hotel_id, booking_id, user_id, guest_name, rating, comment, review) VALUES (?,?,?,?,?,?,?)");
    $review_comment = $data['comment'] ?? $data['review'] ?? '';
    mysqli_stmt_bind_param($stmt,'isisiss',
        $data['hotel_id'], $data['booking_id'], $data['user_id'],
        $data['guest_name'], $data['rating'], $review_comment, $review_comment
    );
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        bhRecalculateHotelRating($data['hotel_id']);
        return $id;
    }
    mysqli_stmt_close($stmt);
    return false;
}

function bhGetPendingReviewsForHotel(int $hotel_id): array {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT r.*, u.first_name, u.last_name, u.email FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.hotel_id = ? AND r.status = 'pending' ORDER BY r.created_at ASC");
    mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $reviews = [];
    while ($row = mysqli_fetch_assoc($res)) $reviews[] = $row;
    mysqli_stmt_close($stmt);
    return $reviews;
}

function bhReplyToReview(int $review_id, string $reply_text, int $hotel_id): bool {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE reviews SET manager_reply = ?, reply_status = 'replied', updated_at = NOW() WHERE review_id = ? AND hotel_id = ? AND status != 'hidden'");
    $stmt_reply = mysqli_real_escape_string($conn, $reply_text);
    mysqli_stmt_bind_param($stmt, 'sii', $stmt_reply, $review_id, $hotel_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function bhRecalculateHotelRating(int $hotel_id): void {
    global $conn;
    $avg = bhGetAverageRating($hotel_id);
    $stmt = mysqli_prepare($conn, "UPDATE hotels SET rating = ? WHERE hotel_id = ?");
    mysqli_stmt_bind_param($stmt, 'di', $avg, $hotel_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

