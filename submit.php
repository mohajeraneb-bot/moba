<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.html');
    exit;
}

// Collect & trim fields
$firstName   = trim($_POST['fname'] ?? '');
$lastName    = trim($_POST['lname'] ?? '');
$whatsapp    = trim($_POST['whatsapp'] ?? '');
$dob         = trim($_POST['dob'] ?? '');
$hair        = trim($_POST['hair'] ?? '');
$eyes        = trim($_POST['eyes'] ?? '');
$height      = trim($_POST['height'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$destination = trim($_POST['destination'] ?? '');

// Required check
$required = [$firstName, $lastName, $whatsapp, $dob, $hair, $eyes, $height, $nationality, $destination];
if (in_array('', $required, true)) {
    exit('لطفاً همه فیلدها را تکمیل کنید.');
}

// File upload validation
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    exit('مشکلی در آپلود عکس رخ داد.');
}

$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedType = $finfo->file($_FILES['photo']['tmp_name']) ?: '';

if (!array_key_exists($detectedType, $allowedTypes)) {
    exit('فقط فایل‌های JPG/PNG/WEBP مجاز هستند.');
}

$maxBytes = 5 * 1024 * 1024;
if ($_FILES['photo']['size'] > $maxBytes) {
    exit('حجم فایل نباید بیشتر از 5 مگابایت باشد.');
}

// Ensure uploads folder exists
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Unique filename
$ext = $allowedTypes[$detectedType];
$uniqueName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $uniqueName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
    exit('ذخیره‌سازی فایل انجام نشد.');
}

$photoRelative = 'uploads/' . $uniqueName;

// Insert into DB
$sql = "INSERT INTO form_data
  (first_name, last_name, whatsapp, photo_path, dob, hair_color, eye_color, height_cm, nationality, destination_country, created_at)
  VALUES
  (:first_name, :last_name, :whatsapp, :photo_path, :dob, :hair_color, :eye_color, :height_cm, :nationality, :destination_country, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name'  => $lastName,
    ':whatsapp'   => $whatsapp,
    ':photo_path' => $photoRelative,
    ':dob'        => $dob,
    ':hair_color' => $hair,
    ':eye_color'  => $eyes,
    ':height_cm'  => (int)$height,
    ':nationality'=> $nationality,
    ':destination_country' => $destination,
]);

// Success page
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>ثبت موفق</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card p-4 text-center shadow">
      <h3>اطلاعات شما با موفقیت ثبت شد ✅</h3>
      <a href="form.html" class="btn btn-primary mt-2">بازگشت</a>
    </div>
  </div>
</body>
</html>
