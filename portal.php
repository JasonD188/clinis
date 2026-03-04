<?php
include 'db.php';
$search = '';
$patient = null;
$records = null;
$appointments = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_id'])) {
    $search = trim($_POST['student_id']);
    $stmt = $conn->prepare("SELECT * FROM patients WHERE student_id = ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    if (!$patient) $error = "No patient found with Student ID: " . htmlspecialchars($search);
    else {
        $pid = $patient['id'];
        $records = $conn->query("SELECT * FROM medical_records WHERE patient_id=$pid ORDER BY record_date DESC");
        $appointments = $conn->query("SELECT * FROM appointments WHERE patient_id=$pid ORDER BY appointment_date DESC");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP Clinic - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; }
        .portal-header { background: linear-gradient(135deg, #1a3a5c, #2980b9); color: white; padding: 30px 0; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .vital-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; margin: 3px; background: #f0f4f8; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
<div class="portal-header">
    <div class="container text-center">
        <div style="width:60px;height:60px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;">
            <i class="fas fa-hospital-user"></i>
        </div>
        <h2 class="mb-1">BCP CLINIC</h2>
        <p class="mb-0 opacity-75">Patient Self-Service Portal</p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3 fw-bold"><i class="fas fa-search me-2 text-primary"></i>Find Your Health Records</h5>
                    <p class="text-muted mb-3">Enter your Student ID to view your appointment history and medical records.</p>
                    <form method="POST">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-id-card text-muted"></i></span>
                            <input type="text" name="student_id" class="form-control form-control-lg" placeholder="Enter your Student ID (e.g., 2024-0001)" value="<?= htmlspecialchars($search) ?>" required>
                            <button type="submit" name="search_id" class="btn btn-primary px-4"><i class="fas fa-search me-2"></i>Search</button>
                        </div>
                    </form>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mt-3 mb-0"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($patient): ?>
            <!-- Patient Info -->
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:55px;height:55px;background:linear-gradient(135deg,#2980b9,#3498db);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:white;font-weight:bold;">
                            <?= strtoupper(substr($patient['full_name'],0,1)) ?>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($patient['full_name']) ?></h5>
                            <span class="badge bg-primary"><?= htmlspecialchars($patient['student_id']) ?></span>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4"><small class="text-muted d-block">Date of Birth</small><strong><?= $patient['dob'] ?: 'N/A' ?></strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Gender</small><strong><?= $patient['gender'] ?: 'N/A' ?></strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Blood Type</small><strong><?= $patient['blood_type'] ? "<span class='badge bg-danger'>{$patient['blood_type']}</span>" : 'N/A' ?></strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Phone</small><strong><?= htmlspecialchars($patient['phone'] ?: 'N/A') ?></strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong><?= htmlspecialchars($patient['email'] ?: 'N/A') ?></strong></div>
                    </div>
                    <?php if ($patient['emergency_name']): ?>
                    <div class="alert alert-danger mt-3 mb-0 py-2">
                        <small><strong><i class="fas fa-exclamation-triangle me-1"></i>Emergency Contact:</strong> <?= htmlspecialchars($patient['emergency_name']) ?> — <?= htmlspecialchars($patient['emergency_phone']) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Appointments -->
            <div class="card mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2 text-success"></i>Appointments</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Type</th><th>Doctor</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if ($appointments->num_rows == 0): ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted">No appointments on record</td></tr>
                        <?php else: while($r = $appointments->fetch_assoc()): $sc = $r['status']=='Scheduled'?'info':($r['status']=='Completed'?'success':'danger'); ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($r['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($r['appointment_time'])) ?></td>
                                <td><?= $r['type'] ?></td>
                                <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                                <td><span class="badge bg-<?= $sc ?>"><?= $r['status'] ?></span></td>
                            </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Medical Records -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-file-medical me-2 text-primary"></i>Medical History</h6>
                </div>
                <div class="card-body">
                <?php if ($records->num_rows == 0): ?>
                    <p class="text-center text-muted py-2">No medical records found</p>
                <?php else: while($r = $records->fetch_assoc()): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-bold text-muted mb-2 small"><?= date('F d, Y \a\t h:i A', strtotime($r['record_date'])) ?></div>
                        <div class="mb-2">
                            <?php if($r['bp']): ?><span class="vital-badge"><i class="fas fa-heartbeat text-danger me-1"></i>BP: <?= $r['bp'] ?></span><?php endif; ?>
                            <?php if($r['weight']): ?><span class="vital-badge"><i class="fas fa-weight text-info me-1"></i>Weight: <?= $r['weight'] ?> kg</span><?php endif; ?>
                            <?php if($r['height']): ?><span class="vital-badge"><i class="fas fa-ruler text-success me-1"></i>Height: <?= $r['height'] ?> cm</span><?php endif; ?>
                            <?php if($r['temperature']): ?><span class="vital-badge"><i class="fas fa-thermometer text-warning me-1"></i>Temp: <?= $r['temperature'] ?>°C</span><?php endif; ?>
                        </div>
                        <?php if($r['diagnosis']): ?><p class="mb-1"><strong>Diagnosis:</strong> <?= htmlspecialchars($r['diagnosis']) ?></p><?php endif; ?>
                        <?php if($r['prescription']): ?><p class="mb-1"><strong>Prescription:</strong> <?= htmlspecialchars($r['prescription']) ?></p><?php endif; ?>
                        <?php if($r['consultation_notes']): ?><p class="mb-0 text-muted"><small><?= htmlspecialchars($r['consultation_notes']) ?></small></p><?php endif; ?>
                    </div>
                <?php endwhile; endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4 text-muted">
                <small><i class="fas fa-lock me-1"></i>This portal is for authorized patients only. &copy; <?= date('Y') ?> BCP Clinic</small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>