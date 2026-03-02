<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

$date_today = date("F d, Y h:i A");

/* ===============================
   IF NO ID → SHOW PATIENT LIST
=================================*/
if (!isset($_GET['id'])) {

    $stmt = $conn->prepare("SELECT id, full_name, student_id FROM patients ORDER BY full_name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h3>Select Patient to Print Report</h3>

<div class="list-group mt-3">
    <?php while($p = $result->fetch_assoc()): ?>
        <a href="?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action">
            <?= htmlspecialchars($p['full_name']) ?>
            (<?= htmlspecialchars($p['student_id']) ?>)
        </a>
    <?php endwhile; ?>
</div>

</body>
</html>
<?php
exit;
}

/* ===============================
   GET PATIENT INFORMATION
=================================*/

$patient_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result();

if ($patient->num_rows == 0) {
    die("Patient not found.");
}

$patientData = $patient->fetch_assoc();

/* ===============================
   GET APPOINTMENTS
=================================*/

$stmt = $conn->prepare("SELECT * FROM appointments 
                        WHERE patient_id = ? 
                        ORDER BY appointment_date DESC, appointment_time DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();

/* ===============================
   GET MEDICAL RECORDS
=================================*/

$stmt = $conn->prepare("SELECT * FROM medical_patient 
                        WHERE patient_id = ? 
                        ORDER BY patient_date DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Full Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; }
        .section-title { 
            font-weight: bold; 
            margin-top: 25px; 
            margin-bottom: 10px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="p-4">

<div class="d-flex align-items-center mb-3">
    <img src="logo.png" width="70" class="me-3">
    <div>
        <h4 class="mb-0">SCHOOL CLINIC MANAGEMENT SYSTEM</h4>
        <small><strong>PATIENT FULL REPORT</strong></small><br>
        <small>Date: <?= $date_today ?></small>
    </div>
</div>

<hr>

<!-- PATIENT INFORMATION -->
<div class="mb-3">
    <strong>Student ID:</strong> <?= htmlspecialchars($patientData['student_id']) ?><br>
    <strong>Full Name:</strong> <?= htmlspecialchars($patientData['full_name']) ?><br>
    <strong>Gender:</strong> <?= htmlspecialchars($patientData['gender']) ?><br>
    <strong>Blood Type:</strong> <?= htmlspecialchars($patientData['blood_type']) ?>
</div>

<!-- APPOINTMENTS -->
<div class="section-title">Appointments</div>

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time</th>
            <th>Doctor</th>
            <th>Type</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    if ($appointments->num_rows > 0):
        while ($r = $appointments->fetch_assoc()):
    ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['appointment_date']) ?></td>
            <td><?= htmlspecialchars($r['appointment_time']) ?></td>
            <td><?= htmlspecialchars($r['doctor_name']) ?></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
        </tr>
    <?php endwhile; else: ?>
        <tr>
            <td colspan="6" class="text-center">No appointments found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<!-- MEDICAL RECORDS -->
<div class="section-title">Medical Records</div>

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Vitals</th>
            <th>Diagnosis</th>
            <th>Prescription</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    if ($medical->num_rows > 0):
        while ($r = $medical->fetch_assoc()):
    ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= date('M d, Y h:i A', strtotime($r['record_date'])) ?></td>
            <td>
                <?php if(!empty($r['bp'])) echo "BP: ".$r['bp']."<br>"; ?>
                <?php if(!empty($r['weight'])) echo "Weight: ".$r['weight']." kg<br>"; ?>
                <?php if(!empty($r['height'])) echo "Height: ".$r['height']." cm<br>"; ?>
                <?php if(!empty($r['temperature'])) echo "Temperature: ".$r['temperature']." °C<br>"; ?>
            </td>
            <td><?= htmlspecialchars($r['diagnosis']) ?></td>
            <td><?= htmlspecialchars($r['prescription']) ?></td>
        </tr>
    <?php endwhile; else: ?>
        <tr>
            <td colspan="5" class="text-center">No medical records found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<br><br>

<div class="row mt-5 text-center">
    <div class="col-6">
        ___________________________<br>
        <strong>CLINIC NURSE</strong>
    </div>
    <div class="col-6">
        ___________________________<br>
        <strong>ADMIN</strong>
    </div>
</div>

<script>
window.onload = function() {
    window.print();
};
</script>

</body>
</html>