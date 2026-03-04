<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    die("No patient ID provided.");
}

$id = intval($_GET['id']);


// =====================
// IMAGE UPLOAD SECTION
// =====================
if (isset($_POST['upload_image'])) {

    if (!empty($_FILES['image']['name'])) {

        // Create uploads folder if not exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $filename = time() . "_" . basename($_FILES['image']['name']);
        $target = "uploads/" . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {

            $stmt = $conn->prepare("UPDATE patients SET xray_image = ? WHERE id = ?");
            $stmt->bind_param("si", $filename, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Failed to upload image.";
        }
    }

    header("Location: x.ray.php?id=$id");
    exit();
}


// =====================
// FETCH PATIENT DATA
// =====================
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Patient not found.");
}

$patient = $result->fetch_assoc();
$stmt->close();

$total = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Medical Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
<style>
body {
    background: white;
    font-family: Arial;
}
.report-container {
    width: 850px;
    margin: 30px auto;
    background: white;
    padding: 50px;
    border: 1px solid #ccc;
}
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.header h1 {
    font-family: Georgia;
    font-size: 42px;
    font-weight: bold;
    letter-spacing: 3px;
    margin: 0;
}
.red-cross {
    font-size: 70px;
    color: #dc162a;
}
.medical-icon {
    font-size: 60px;
    color: #6c757d;
}
.line {
    border-bottom: 2px solid #999;
    margin: 20px 0 30px;
}
.label {
    font-weight: bold;
    width: 180px;
    display: inline-block;
}
.xray-img {
    width: 100%;
    max-width: 500px;
    margin-top: 15px;
    max-height: 250px;
    object-fit: contain;
}
.footer {
    margin-top: 40px;
    border-top: 2px solid #999;
    padding-top: 10px;
    color: #555;
    display: flex;
    justify-content: space-between;
}
.no-print {
    text-align: right;
    margin-bottom: 15px;
}
@media print {
    .no-print {
        display: none;
    }
}
</style>
</head>

<body>

<div class="report-container">

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-secondary btn-sm">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div class="header">
         <img src="./components/bestlink_college_of_the_philippines_logo.jpg" class="logo me-3">
        <h1>Medical Report</h1>
        <i class="fas fa-staff-snake medical-icon"></i>
    </div>

    <div class="line"></div>

    <p><span class="label">Total Patients:</span> <?= $total ?></p>
    <p><span class="label">Student ID:</span> <?= htmlspecialchars($patient['student_id']) ?></p>
    <p><span class="label">Full Name:</span> <?= htmlspecialchars($patient['full_name']) ?></p>
    <p><span class="label">Gender:</span> <?= htmlspecialchars($patient['gender']) ?></p>
    <p><span class="label">Blood Type:</span> <?= htmlspecialchars($patient['blood_type']) ?></p>
    <p><span class="label">Phone:</span> <?= htmlspecialchars($patient['phone']) ?></p>
    <p><span class="label">Emergency Name:</span> <?= htmlspecialchars($patient['emergency_name']) ?></p>
    <p><span class="label">Emergency Phone:</span> <?= htmlspecialchars($patient['emergency_phone']) ?></p>

    <div class="line"></div>
    <h5><strong>Chest X-Ray</strong></h5>

    <?php if (!empty($patient['xray_image'])): ?>
        <img src="uploads/<?= htmlspecialchars($patient['xray_image']) ?>" class="xray-img" alt="X-Ray Image">
    <?php else: ?>
        <p class="text-muted">No Image Uploaded.</p>
    <?php endif; ?>

    <div class="no-print mt-3">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="file" name="image" class="form-control" required accept="image/*">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="upload_image" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload"></i> Upload Image
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="footer">
        <span>Confidential Medical Record</span>
        <span>Page 1</span>
    </div>

</div>

</body>
</html>