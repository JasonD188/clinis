<?php
include 'db.php';

$search = '';
$patient = null;
$records = null;
$appointments = null;
$error = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_id'])) {

    $search = trim($_POST['student_id']);

    $stmt = $conn->prepare("SELECT * FROM patients WHERE student_id = ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if (!$patient) {
        $error = "No patient found with Student ID: " . htmlspecialchars($search);
    } else {
        $pid = (int)$patient['id'];

        $records = $conn->query("
            SELECT * FROM medical_records 
            WHERE patient_id = $pid 
            ORDER BY record_date DESC
        ");

        $appointments = $conn->query("
            SELECT * FROM appointments 
            WHERE patient_id = $pid 
            ORDER BY appointment_date DESC
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BCP Clinic Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/emailjs-com@3.2.0/dist/email.min.js"></script>
<style>
body { background:#f0f4f8; }
.header { background:linear-gradient(135deg,#1a3a5c,#2980b9); color:#fff; padding:40px 0; text-align:center; }
.card { border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin-bottom:20px; }
.vital-badge { padding:4px 10px; border-radius:20px; font-size:12px; margin:3px; background:#f0f4f8; border:1px solid #dee2e6; display:inline-block; }
.contact-section { background:#ffffff; padding:60px 0; margin-top:60px; }
</style>
</head>
<body>

<div class="header">
    <h2>BCP CLINIC</h2>
    <p>Patient Self-Service Portal</p>
</div>

<div class="container py-5">

    <!-- SEARCH -->
    <div class="card p-4">
        <h5>Find Your Health Records</h5>
        <form method="POST">
            <div class="input-group mb-2">
                <input type="text" name="student_id" class="form-control" placeholder="Enter Student ID" required>
                <button type="submit" name="search_id" class="btn btn-primary">Search</button>
            </div>
        </form>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
    </div>

    <!-- PATIENT DATA -->
    <?php if($patient): ?>
    <div class="card p-4">
        <h5><?= htmlspecialchars($patient['full_name']) ?></h5>
        <p><strong>Student ID:</strong> <?= htmlspecialchars($patient['student_id']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
        <p><strong>Blood Type:</strong> <?= htmlspecialchars($patient['blood_type']) ?></p>
    </div>

    <!-- APPOINTMENTS -->
    <div class="card p-4">
        <h6>Appointments</h6>
        <table class="table">
            <tr><th>Date</th><th>Doctor</th><th>Status</th></tr>
            <?php if($appointments->num_rows==0): ?>
                <tr><td colspan="3">No appointments</td></tr>
            <?php else: while($a=$appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($a['status']) ?></td>
                </tr>
            <?php endwhile; endif; ?>
        </table>
    </div>

    <!-- MEDICAL RECORDS -->
    <div class="card p-4">
        <h6>Medical History</h6>
        <?php if($records->num_rows==0): ?>
            <p>No records found</p>
        <?php else: while($r=$records->fetch_assoc()): ?>
            <div class="border p-3 mb-3 rounded">
                <div><strong>Date:</strong> <?= htmlspecialchars($r['record_date']) ?></div>
                <?php if($r['bp']): ?><span class="vital-badge">BP: <?= htmlspecialchars($r['bp']) ?></span><?php endif; ?>
                <?php if($r['weight']): ?><span class="vital-badge">Weight: <?= htmlspecialchars($r['weight']) ?> kg</span><?php endif; ?>
                <?php if($r['temperature']): ?><span class="vital-badge">Temp: <?= htmlspecialchars($r['temperature']) ?>°C</span><?php endif; ?>
                <p><strong>Diagnosis:</strong> <?= htmlspecialchars($r['diagnosis']) ?></p>
                <p><strong>Prescription:</strong> <?= htmlspecialchars($r['prescription']) ?></p>
            </div>
        <?php endwhile; endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- CONTACT SECTION -->
<div class="contact-section">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-6">

<h3 class="mb-4 text-center">Contact Us</h3>

<form id="contactForm">
    <input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" required>
    <input type="text" name="last_name" class="form-control mb-2" placeholder="Last Name" required>
    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
    <input type="text" name="subject" class="form-control mb-2" placeholder="Subject" required>
    <textarea name="message" class="form-control mb-2" placeholder="Message" required></textarea>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="agree" required>
        <label class="form-check-label">Agree to Terms</label>
    </div>

    <button type="submit" class="btn btn-success w-100">Send Message</button>
</form>

</div>
</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    emailjs.init("");

    const form = document.getElementById('contactForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const first_name = form.first_name.value.trim();
        const last_name = form.last_name.value.trim();
        const email = form.email.value.trim();
        const subject = form.subject.value.trim();
        const message = form.message.value.trim();

        if (!form.agree.checked) {
            alert("you must agree to the terms");
            retrun;
        }
        emailjs.send("", "", {
            first_name: first_name,
            last_name: last_name,
            email: email,
            subject: subject,
            message: message,
            reply_to: email
        }).then(function(response) {
              alert("message sen successfully!");
              form.reset();
        },function(error) {
            console.error("Failed to send ",error);
            alert("Failed to send a message Please try again");
        });
    })
})
</script>

</body>
</html>