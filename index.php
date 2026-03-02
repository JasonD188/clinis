<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msgType = 'success';

// helper functions for validation
function is_letters($str) {
    return preg_match('/^[A-Za-z\s]+$/', $str);
}
function is_numbers($str) {
    return preg_match('/^[0-9]+$/', $str);
}

// central error collector for POST handlers
$errors = [];


// ===================== HANDLE POST ACTIONS =====================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ---- PATIENT REGISTRATION ----
    if (isset($_POST['add_patient'])) {
        $errors = [];
        // trim inputs
        $student_id = trim($_POST['student_id']);
        $full_name  = trim($_POST['full_name']);
        $dob        = trim($_POST['dob']);
        $gender     = trim($_POST['gender']);
        $phone      = trim($_POST['phone']);
        $email      = trim($_POST['email']);
        $address    = trim($_POST['address']);
        $emergency_name  = trim($_POST['emergency_name']);
        $emergency_phone = trim($_POST['emergency_phone']);
        $blood_type = trim($_POST['blood_type']);

        // validate required/format
        if ($student_id === '' || !is_numbers(str_replace('-', '', $student_id))) {
            $errors[] = 'Student ID must contain numbers only.';
        }
        if ($full_name === '' || !is_letters($full_name)) {
            $errors[] = 'Full name must contain letters only.';
        }
        if ($gender && !in_array($gender, ['Male','Female','Other'])) {
            $errors[] = 'Invalid gender selected.';
        }
        if ($phone && !preg_match('/^[0-9]+$/', $phone)) {
            $errors[] = 'Phone number must contain digits only.';
        }
        if ($emergency_name && !is_letters($emergency_name)) {
            $errors[] = 'Emergency contact name must contain letters only.';
        }
        if ($emergency_phone && !preg_match('/^[0-9]+$/', $emergency_phone)) {
            $errors[] = 'Emergency contact phone must contain digits only.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO patients (student_id, full_name, dob, gender, phone, email, address, emergency_name, emergency_phone, blood_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $student_id, $full_name, $dob, $gender, $phone, $email, $address, $emergency_name, $emergency_phone, $blood_type);
            if ($stmt->execute()) {
                $msg = "Patient registered successfully!";
            } else {
                $msg = "Error: Student ID may already exist.";
                $msgType = 'danger';
            }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    }

    // ---- UPDATE PATIENT ----
    if (isset($_POST['update_patient'])) {
        $errors = [];
        // reuse trimmed values from above if available or re-get them
        $student_id = trim($_POST['student_id']);
        $full_name  = trim($_POST['full_name']);
        $dob        = trim($_POST['dob']);
        $gender     = trim($_POST['gender']);
        $phone      = trim($_POST['phone']);
        $email      = trim($_POST['email']);
        $address    = trim($_POST['address']);
        $emergency_name  = trim($_POST['emergency_name']);
        $emergency_phone = trim($_POST['emergency_phone']);
        $blood_type = trim($_POST['blood_type']);
        $pid        = intval($_POST['patient_id']);

        if ($student_id === '' || !is_numbers(str_replace('-', '', $student_id))) {
            $errors[] = 'Student ID must contain numbers only.';
        }
        if ($full_name === '' || !is_letters($full_name)) {
            $errors[] = 'Full name must contain letters only.';
        }
        if ($gender && !in_array($gender, ['Male','Female','Other'])) {
            $errors[] = 'Invalid gender selected.';
        }
        if ($phone && !preg_match('/^[0-9]+$/', $phone)) {
            $errors[] = 'Phone number must contain digits only.';
        }
        if ($emergency_name && !is_letters($emergency_name)) {
            $errors[] = 'Emergency contact name must contain letters only.';
        }
        if ($emergency_phone && !preg_match('/^[0-9]+$/', $emergency_phone)) {
            $errors[] = 'Emergency contact phone must contain digits only.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE patients SET student_id=?, full_name=?, dob=?, gender=?, phone=?, email=?, address=?, emergency_name=?, emergency_phone=?, blood_type=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $student_id, $full_name, $dob, $gender, $phone, $email, $address, $emergency_name, $emergency_phone, $blood_type, $pid);
            if ($stmt->execute()) $msg = "Patient updated successfully!";
            else { $msg = "Update failed."; $msgType = 'danger'; }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- DELETE PATIENT ----
    if (isset($_POST['delete_patient'])) {
        $id = intval($_POST['patient_id']);
        $conn->query("DELETE FROM appointments WHERE patient_id=$id");
        $conn->query("DELETE FROM medical_records WHERE patient_id=$id");
        $conn->query("DELETE FROM patients WHERE id=$id");
        $msg = "Patient and associated records deleted.";
    }

    // ---- BOOK APPOINTMENT ----
    if (isset($_POST['book_appointment'])) {
        $errors = [];
        $pid = intval($_POST['patient_id']);
        $doctor_name = trim($_POST['doctor_name']);
        $appt_date = trim($_POST['appt_date']);
        $appt_time = trim($_POST['appt_time']);
        $appt_type = trim($_POST['appt_type']);
        $appt_notes = trim($_POST['appt_notes']);

        if ($doctor_name === '' || !is_letters($doctor_name)) {
            $errors[] = 'Doctor name must contain letters only.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_name, appointment_date, appointment_time, type, status, notes) VALUES (?, ?, ?, ?, ?, 'Scheduled', ?)");
            $stmt->bind_param("isssss", $pid, $doctor_name, $appt_date, $appt_time, $appt_type, $appt_notes);
            if ($stmt->execute()) $msg = "Appointment scheduled successfully!";
            else { $msg = "Booking failed."; $msgType = 'danger'; }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- UPDATE APPOINTMENT STATUS ----
    if (isset($_POST['update_appt_status'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
        $stmt->bind_param("si", $_POST['status'], $_POST['appt_id']);
        if ($stmt->execute()) $msg = "Appointment status updated!";
    }

    // ---- CANCEL APPOINTMENT ----
    if (isset($_POST['cancel_appointment'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=?");
        $stmt->bind_param("i", $_POST['appt_id']);
        if ($stmt->execute()) $msg = "Appointment cancelled.";
    }

    // ---- ADD MEDICAL RECORD ----
    if (isset($_POST['add_medical_record'])) {
        $errors = [];
        $pid = intval($_POST['patient_id']);
        $bp = trim($_POST['bp']);
        $wt = trim($_POST['weight']);
        $ht = trim($_POST['height']);
        $tmp = trim($_POST['temp']);
        $diag = trim($_POST['diagnosis']);
        $presc = trim($_POST['prescription']);
        $cnotes = trim($_POST['consultation_notes']);

        if ($wt !== '' && !is_numeric($wt)) {
            $errors[] = 'Weight must be a number.';
        }
        if ($ht !== '' && !is_numeric($ht)) {
            $errors[] = 'Height must be a number.';
        }
        if ($tmp !== '' && !is_numeric($tmp)) {
            $errors[] = 'Temperature must be a number.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, bp, weight, height, temperature, diagnosis, prescription, consultation_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $pid, $bp, $wt, $ht, $tmp, $diag, $presc, $cnotes);
            if ($stmt->execute()) $msg = "Medical record saved successfully!";
            else { $msg = "Error saving record."; $msgType = 'danger'; }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- ADD INVENTORY ITEM ----
    if (isset($_POST['add_inventory'])) {
        $errors = [];
        $item_name = trim($_POST['item_name']);
        $category  = trim($_POST['category']);
        $quantity  = trim($_POST['quantity']);
        $unit      = trim($_POST['unit']);
        if ($item_name === '') {
            $errors[] = 'Item name is required.';
        }
        if ($quantity === '' || !is_numbers($quantity)) {
            $errors[] = 'Quantity must be a number.';
        }

        if (empty($errors)) {
            $qty = intval($quantity);
            $status = $qty == 0 ? 'Out of Stock' : ($qty < 10 ? 'Low Stock' : 'In Stock');
            $stmt = $conn->prepare("INSERT INTO inventory (item_name, category, quantity, unit, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $item_name, $category, $qty, $unit, $status);
            if ($stmt->execute()) $msg = "Inventory item added!";
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- UPDATE INVENTORY ----
    if (isset($_POST['update_inventory'])) {
        $errors = [];
        $item_name = trim($_POST['item_name']);
        $category  = trim($_POST['category']);
        $quantity  = trim($_POST['quantity']);
        $unit      = trim($_POST['unit']);
        $invId     = intval($_POST['inv_id']);
        if ($item_name === '') {
            $errors[] = 'Item name is required.';
        }
        if ($quantity === '' || !is_numbers($quantity)) {
            $errors[] = 'Quantity must be a number.';
        }
        if (empty($errors)) {
            $qty = intval($quantity);
            $status = $qty == 0 ? 'Out of Stock' : ($qty < 10 ? 'Low Stock' : 'In Stock');
            $stmt = $conn->prepare("UPDATE inventory SET item_name=?, category=?, quantity=?, unit=?, status=? WHERE id=?");
            $stmt->bind_param("ssissi", $item_name, $category, $qty, $unit, $status, $invId);
            if ($stmt->execute()) $msg = "Inventory updated!";
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- RESTOCK INVENTORY ----
    if (isset($_POST['restock_inventory'])) {
        $errors = [];
        $addQty = trim($_POST['add_quantity']);
        $invId = intval($_POST['inv_id']);
        if ($addQty === '' || !is_numbers($addQty)) {
            $errors[] = 'Restock quantity must be a number.';
        }
        if (empty($errors)) {
            $addQtyInt = intval($addQty);
            $conn->query("UPDATE inventory SET quantity = quantity + $addQtyInt, status = CASE WHEN quantity + $addQtyInt = 0 THEN 'Out of Stock' WHEN quantity + $addQtyInt < 10 THEN 'Low Stock' ELSE 'In Stock' END, updated_at = NOW() WHERE id = $invId");
            $msg = "Stock restocked successfully!";
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }
    }

    // ---- DELETE INVENTORY ----
    if (isset($_POST['delete_inventory'])) {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id=?");
        $stmt->bind_param("i", $_POST['inv_id']);
        if ($stmt->execute()) $msg = "Item deleted from inventory.";
    }

    // ---- ADD ROOM ----
    if (isset($_POST['add_room'])) {
        $stmt = $conn->prepare("INSERT INTO rooms (room_name, room_type, status, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_POST['room_name'], $_POST['room_type'], $_POST['room_status'], $_POST['room_notes']);
        if ($stmt->execute()) $msg = "Room added!";
    }

    // ---- UPDATE ROOM STATUS ----
    if (isset($_POST['update_room'])) {
        $stmt = $conn->prepare("UPDATE rooms SET status=?, notes=? WHERE id=?");
        $stmt->bind_param("ssi", $_POST['room_status'], $_POST['room_notes'], $_POST['room_id']);
        if ($stmt->execute()) $msg = "Room status updated!";
    }

    // ---- ADD MAINTENANCE ----
    if (isset($_POST['add_maintenance'])) {
        $stmt = $conn->prepare("INSERT INTO maintenance (equipment_name, schedule_date, technician, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $_POST['equipment_name'], $_POST['schedule_date'], $_POST['technician'], $_POST['maint_status'], $_POST['maint_notes']);
        if ($stmt->execute()) $msg = "Maintenance schedule added!";
    }

    // ---- UPDATE MAINTENANCE ----
    if (isset($_POST['update_maintenance'])) {
        $stmt = $conn->prepare("UPDATE maintenance SET status=? WHERE id=?");
        $stmt->bind_param("si", $_POST['maint_status'], $_POST['maint_id']);
        if ($stmt->execute()) $msg = "Maintenance status updated!";
    }

    // ---- CHANGE PASSWORD ----
    if (isset($_POST['change_password'])) {
        if ($_POST['new_pass'] === $_POST['confirm_pass']) {
            $hashed = password_hash($_POST['new_pass'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $_SESSION['user_id']);
            if ($stmt->execute()) $msg = "Password changed successfully!";
        } else { $msg = "Passwords do not match."; $msgType = 'danger'; }
    }


// ===================== FETCH DATA =====================
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Counts for dashboard
$totalPatients = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];
$totalAppts = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='Scheduled'")->fetch_assoc()['c'];
$totalInventory = $conn->query("SELECT COUNT(*) as c FROM inventory")->fetch_assoc()['c'];
$pendingMaint = $conn->query("SELECT COUNT(*) as c FROM maintenance WHERE status='Pending'")->fetch_assoc()['c'];
$lowStock = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE status IN ('Low Stock','Out of Stock')")->fetch_assoc()['c'];
$todayAppts = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c'];

// Patients list with search
$pSearch = $search ? "WHERE student_id LIKE '%$search%' OR full_name LIKE '%$search%'" : "";
$patients = $conn->query("SELECT * FROM patients $pSearch ORDER BY created_at DESC");

// Appointments
$appointments = $conn->query("SELECT a.*, p.full_name, p.student_id FROM appointments a JOIN patients p ON a.patient_id = p.id ORDER BY a.appointment_date DESC, a.appointment_time ASC");

// Medical records
$medRecords = $conn->query("SELECT m.*, p.full_name, p.student_id FROM medical_records m JOIN patients p ON m.patient_id = p.id ORDER BY m.record_date DESC");

// Inventory
$inventory = $conn->query("SELECT * FROM inventory ORDER BY status ASC, item_name ASC");

// Rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_type ASC");

// Maintenance
$maintenance = $conn->query("SELECT * FROM maintenance ORDER BY schedule_date ASC");

// All patients for dropdowns
$allPatients = $conn->query("SELECT id, student_id, full_name FROM patients ORDER BY full_name ASC");

// Patient to edit
$editPatient = null;
if (isset($_GET['edit_patient'])) {
    $epId = intval($_GET['edit_patient']);
    $editPatient = $conn->query("SELECT * FROM patients WHERE id=$epId")->fetch_assoc();
}

// Patient medical history view
$viewPatient = null;
$patientRecords = null;
if (isset($_GET['view_patient'])) {
    $vpId = intval($_GET['view_patient']);
    $viewPatient = $conn->query("SELECT * FROM patients WHERE id=$vpId")->fetch_assoc();
    $patientRecords = $conn->query("SELECT * FROM medical_records WHERE patient_id=$vpId ORDER BY record_date DESC");
    $patientAppts = $conn->query("SELECT * FROM appointments WHERE patient_id=$vpId ORDER BY appointment_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP Clinic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #1a3a5c;
            --sidebar-hover: #2980b9;
            --primary: #2980b9;
            --accent: #27ae60;
        }
        * { box-sizing: border-box; }
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

        /* SIDEBAR */
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            top: 0; left: 0;
            width: 250px;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s;
        }
        .sidebar-brand {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-brand .brand-icon {
            width: 50px; height: 50px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin: 0 auto 10px;
        }
        .sidebar a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar a.active { background: var(--sidebar-hover); color: white; border-left: 3px solid #74b9ff; }
        .sidebar .nav-section { padding: 10px 20px 5px; font-size: 11px; text-transform: uppercase; color: rgba(255,255,255,0.4); letter-spacing: 1px; margin-top: 5px; }
        .sidebar-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 999; }

        /* MAIN CONTENT */
        .main-content { margin-left: 250px; min-height: 100vh; }
        .topnav {
            background: white;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: sticky; top: 0; z-index: 100;
        }
         .brand-icon {
            width: 30px;
            height: 30px;
            background: var(--primary);
            border-radius: 50%;
         }
        .page-content { padding: 25px; }

        /* CARDS */
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .card-header { border-radius: 12px 12px 0 0 !important; }
        .stat-card { border-radius: 12px; padding: 20px; color: white; text-decoration: none; display: block; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); color: white; }
        .stat-card .stat-num { font-size: 36px; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: 13px; opacity: 0.85; margin-top: 4px; }
        .stat-card .stat-icon { font-size: 30px; opacity: 0.3; }

        /* TABLES */
        .table th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; font-weight: 600; }
        .table-hover tbody tr:hover { background: #f8f9ff; }

        /* BADGES */
        .badge { font-size: 11px; padding: 5px 10px; border-radius: 20px; }

        /* FORMS */
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(41,128,185,0.2); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #2471a3; border-color: #2471a3; }

        /* MOBILE */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
        }

        /* STATUS COLORS */
        .status-scheduled { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-instock { background: #d4edda; color: #155724; }
        .status-lowstock { background: #fff3cd; color: #856404; }
        .status-outofstock { background: #f8d7da; color: #721c24; }

        /* SECTION DIVIDER */
        .section-title { font-size: 22px; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .section-sub { color: #6c757d; font-size: 14px; margin-bottom: 20px; }

        /* PRINT */
        @media print {
            .sidebar, .topnav, .btn, .no-print { display: none !important; }
            .main-content { margin-left: 0; }
        }

        .hamburger-btn { display: none; }
        @media (max-width: 768px) { .hamburger-btn { display: block; } }

        .vital-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; margin: 2px; }
    </style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
       <div classs="brand-icon">
        <img src="./components/logo.png" alt=""class="brand-icon object-fit-cover">
       </div>
        <h5 class="mb-0 fw-bold">BCP CLINIC</h5>
        <small style="color:rgba(255,255,255,0.5)">Management System</small>
    </div>

    
    <a href="?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>

    <div class="nav-section">Patient Services</div>
    <a href="?page=patients" class="<?= $page=='patients'?'active':'' ?>">
        <i class="fas fa-user-injured"></i> Patient Registration
    </a>
    <a href="?page=appointments" class="<?= $page=='appointments'?'active':'' ?>">
        <i class="fas fa-calendar-check"></i> Appointments
    </a>
    <a href="?page=medical" class="<?= $page=='medical'?'active':'' ?>">
        <i class="fas fa-file-medical-alt"></i> Health Records & Vitals
    </a>

    <div class="nav-section">Facility</div>
    <a href="?page=inventory" class="<?= $page=='inventory'?'active':'' ?>">
        <i class="fas fa-boxes"></i> Inventory & Supplies
        <?php if ($lowStock > 0): ?><span class="badge bg-warning text-dark ms-auto"><?= $lowStock ?></span><?php endif; ?>
    </a>
    <a href="?page=rooms" class="<?= $page=='rooms'?'active':'' ?>">
        <i class="fas fa-door-open"></i> Rooms & Equipment
    </a>
    <a href="?page=maintenance" class="<?= $page=='maintenance'?'active':'' ?>">
        <i class="fas fa-tools"></i> Maintenance Schedule
        <?php if ($pendingMaint > 0): ?><span class="badge bg-danger ms-auto"><?= $pendingMaint ?></span><?php endif; ?>
    </a>

    <div class="nav-section">Analytics</div>
    <a href="?page=reports" class="<?= $page=='reports'?'active':'' ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
    <a href="portal.php" target="_blank">
        <i class="fas fa-user-circle"></i> Patient Portal
    </a>

   
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- TOP NAV -->
    <div class="topnav">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light hamburger-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <span class="fw-bold text-primary">BCP Clinic</span>
                <span class="text-muted d-none d-md-inline"> — <?= ucfirst($page) ?></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if ($lowStock > 0): ?>
            <span class="badge bg-warning text-dark d-none d-md-inline">
                <i class="fas fa-exclamation-triangle me-1"></i><?= $lowStock ?> Low Stock
            </span>
            <?php endif; ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small"><?= ucfirst($_SESSION['role']) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?page=settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="page-content">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $msgType=='success'?'check-circle':'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<!-- ===================== DASHBOARD ===================== -->
<?php if ($page == 'dashboard'): ?>
<div class="section-title"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Clinic Overview</div>
<div class="section-sub">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?>! Here's what's happening today.</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="?page=patients" class="stat-card" style="background: linear-gradient(135deg, #2980b9, #3498db);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-num"><?= $totalPatients ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="?page=appointments" class="stat-card" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-num"><?= $totalAppts ?></div>
                    <div class="stat-label">Scheduled Appointments</div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="?page=inventory" class="stat-card" style="background: linear-gradient(135deg, #e67e22, #f39c12);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-num"><?= $totalInventory ?></div>
                    <div class="stat-label">Inventory Items</div>
                </div>
                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="?page=maintenance" class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-num"><?= $pendingMaint ?></div>
                    <div class="stat-label">Pending Maintenance</div>
                </div>
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Today's Appointments</div>
            <div class="fs-2 fw-bold text-primary"><?= $todayAppts ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Low/Out of Stock Items</div>
            <div class="fs-2 fw-bold text-warning"><?= $lowStock ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Today's Date</div>
            <div class="fs-5 fw-bold text-success"><?= date('M d, Y') ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2 text-primary"></i>Today's Appointments</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Patient</th><th>Time</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    $todayApptData = $conn->query("SELECT a.*, p.full_name FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.appointment_date = CURDATE() ORDER BY a.appointment_time ASC");
                    if ($todayApptData->num_rows == 0): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No appointments today</td></tr>
                    <?php else: while($r = $todayApptData->fetch_assoc()): 
                        $sc = $r['status'] == 'Scheduled' ? 'info' : ($r['status'] == 'Completed' ? 'success' : 'danger'); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                            <td><?= date('h:i A', strtotime($r['appointment_time'])) ?></td>
                            <td><?= $r['type'] ?></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= $r['status'] ?></span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Low Stock Alerts</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Item</th><th>Qty</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    $lowItems = $conn->query("SELECT * FROM inventory WHERE status IN ('Low Stock','Out of Stock') ORDER BY quantity ASC LIMIT 8");
                    if ($lowItems->num_rows == 0): ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted">All items in stock</td></tr>
                    <?php else: while($r = $lowItems->fetch_assoc()): 
                        $sc = $r['status'] == 'Out of Stock' ? 'danger' : 'warning'; ?>
                        <tr>
                            <td><?= htmlspecialchars($r['item_name']) ?></td>
                            <td><?= $r['quantity'] ?> <?= $r['unit'] ?></td>
                            <td><span class="badge bg-<?= $sc ?> text-<?= $sc=='warning'?'dark':'white' ?>"><?= $r['status'] ?></span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== PATIENTS ===================== -->
<?php elseif ($page == 'patients'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-user-injured me-2 text-primary"></i>Patient Management</div>
        <div class="section-sub">Register new patients, update information, and manage emergency contacts.</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
        <i class="fas fa-plus me-2"></i>Register Patient
    </button>
</div>

<?php if ($viewPatient): ?>
<!-- Patient Detail View -->
<div class="mb-3">
    <a href="?page=patients" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Patients</a>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#2980b9,#3498db);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:32px;color:white;">
                <?= strtoupper(substr($viewPatient['full_name'],0,1)) ?>
            </div>
            <h5 class="mb-1"><?= htmlspecialchars($viewPatient['full_name']) ?></h5>
            <span class="badge bg-primary"><?= htmlspecialchars($viewPatient['student_id']) ?></span>
            <hr>
            <div class="text-start">
                <p class="mb-1"><strong>DOB:</strong> <?= $viewPatient['dob'] ?: 'N/A' ?></p>
                <p class="mb-1"><strong>Gender:</strong> <?= $viewPatient['gender'] ?: 'N/A' ?></p>
                <p class="mb-1"><strong>Blood Type:</strong> <span class="badge bg-danger"><?= $viewPatient['blood_type'] ?: 'N/A' ?></span></p>
                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($viewPatient['phone'] ?: 'N/A') ?></p>
                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($viewPatient['email'] ?: 'N/A') ?></p>
                <p class="mb-0"><strong>Address:</strong> <?= htmlspecialchars($viewPatient['address'] ?: 'N/A') ?></p>
            </div>
            <hr>
            <div class="text-start">
                <strong class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Emergency Contact</strong>
                <p class="mb-1 mt-2"><?= htmlspecialchars($viewPatient['emergency_name'] ?: 'N/A') ?></p>
                <p class="mb-0"><?= htmlspecialchars($viewPatient['emergency_phone'] ?: 'N/A') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2 text-primary"></i>Appointment History</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 table-sm">
                    <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Type</th><th>Doctor</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($patientAppts->num_rows == 0): ?>
                        <tr><td colspan="5" class="text-center py-3 text-muted">No appointments found</td></tr>
                    <?php else: while($r = $patientAppts->fetch_assoc()): $sc = $r['status']=='Scheduled'?'info':($r['status']=='Completed'?'success':'danger'); ?>
                        <tr>
                            <td><?= $r['appointment_date'] ?></td>
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
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-file-medical me-2 text-success"></i>Medical Records</h6></div>
            <div class="card-body p-0">
                <?php if ($patientRecords->num_rows == 0): ?>
                    <p class="text-center py-3 text-muted">No medical records found</p>
                <?php else: while($r = $patientRecords->fetch_assoc()): ?>
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?= date('M d, Y h:i A', strtotime($r['record_date'])) ?></strong>
                        </div>
                        <div class="row g-2 mb-2">
                            <?php if($r['bp']): ?><span class="vital-badge bg-light border"><i class="fas fa-heartbeat text-danger me-1"></i>BP: <?= $r['bp'] ?></span><?php endif; ?>
                            <?php if($r['weight']): ?><span class="vital-badge bg-light border"><i class="fas fa-weight text-info me-1"></i><?= $r['weight'] ?> kg</span><?php endif; ?>
                            <?php if($r['height']): ?><span class="vital-badge bg-light border"><i class="fas fa-ruler-vertical text-success me-1"></i><?= $r['height'] ?> cm</span><?php endif; ?>
                            <?php if($r['temperature']): ?><span class="vital-badge bg-light border"><i class="fas fa-thermometer text-warning me-1"></i><?= $r['temperature'] ?>°C</span><?php endif; ?>
                        </div>
                        <?php if($r['diagnosis']): ?><p class="mb-1"><strong>Diagnosis:</strong> <?= htmlspecialchars($r['diagnosis']) ?></p><?php endif; ?>
                        <?php if($r['prescription']): ?><p class="mb-1"><strong>Prescription:</strong> <?= htmlspecialchars($r['prescription']) ?></p><?php endif; ?>
                        <?php if($r['consultation_notes']): ?><p class="mb-0 text-muted"><strong>Notes:</strong> <?= htmlspecialchars($r['consultation_notes']) ?></p><?php endif; ?>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($editPatient): ?>
<!-- Edit Patient Form -->
<div class="mb-3"><a href="?page=patients" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a></div>
<div class="card">
    <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="fas fa-edit me-2 text-warning"></i>Update Patient: <?= htmlspecialchars($editPatient['full_name']) ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="patient_id" value="<?= $editPatient['id'] ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Student ID *</label><input type="text" name="student_id" class="form-control numbers-only" pattern="\d+" value="<?= htmlspecialchars($editPatient['student_id']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control letters-only" pattern="[A-Za-z\s]+" value="<?= htmlspecialchars($editPatient['full_name']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" value="<?= $editPatient['dob'] ?>"></div>
                <div class="col-md-4"><label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <option <?= $editPatient['gender']=='Male'?'selected':'' ?>>Male</option>
                        <option <?= $editPatient['gender']=='Female'?'selected':'' ?>>Female</option>
                        <option <?= $editPatient['gender']=='Other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Blood Type</label>
                    <select name="blood_type" class="form-select">
                        <option value="">Select</option>
                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                        <option <?= $editPatient['blood_type']==$bt?'selected':'' ?>><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control numbers-only" pattern="\d+" value="<?= htmlspecialchars($editPatient['phone']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editPatient['email']) ?>"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($editPatient['address']) ?></textarea></div>
                <div class="col-md-6"><label class="form-label text-danger">Emergency Contact Name</label><input type="text" name="emergency_name" class="form-control letters-only" pattern="[A-Za-z\s]+" value="<?= htmlspecialchars($editPatient['emergency_name']) ?>"></div>
                <div class="col-md-6"><label class="form-label text-danger">Emergency Contact Phone</label><input type="text" name="emergency_phone" class="form-control numbers-only" pattern="\d+" value="<?= htmlspecialchars($editPatient['emergency_phone']) ?>"></div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" name="update_patient" class="btn btn-warning"><i class="fas fa-save me-2"></i>Save Changes</button>
                    <a href="?page=patients" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Patient List -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="page" value="patients">
            <input type="text" name="search" class="form-control" placeholder="Search by name or student ID..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary px-3"><i class="fas fa-search"></i></button>
            <?php if ($search): ?><a href="?page=patients" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a><?php endif; ?>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Student ID</th><th>Name</th><th>Gender</th><th>Blood Type</th><th>Phone</th><th>Emergency Contact</th><th>Registered</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($patients->num_rows == 0): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No patients found</td></tr>
            <?php else: while ($row = $patients->fetch_assoc()): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= htmlspecialchars($row['student_id']) ?></span></td>
                    <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                    <td><?= $row['gender'] ?: '-' ?></td>
                    <td><?= $row['blood_type'] ? "<span class='badge bg-danger'>{$row['blood_type']}</span>" : '-' ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['emergency_name'] ?: '-') ?><br><small class="text-muted"><?= htmlspecialchars($row['emergency_phone'] ?: '') ?></small></td>
                    <td><small><?= date('M d, Y', strtotime($row['created_at'])) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?page=patients&view_patient=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                            <a href="?page=patients&edit_patient=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" onsubmit="return confirm('Delete this patient and all records?')">
                                <input type="hidden" name="patient_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_patient" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">Personal Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Student ID *</label><input type="text" name="student_id" class="form-control numbers-only" pattern="\d+" required placeholder="e.g., 20240001"></div>
                        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control letters-only" pattern="[A-Za-z\s]+" required placeholder="Last, First Middle"></div>
                        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Gender</label>
                            <select name="gender" class="form-select"><option value="">Select Gender</option><option>Male</option><option>Female</option><option>Other</option></select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Blood Type</label>
                            <select name="blood_type" class="form-select"><option value="">Select Blood Type</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?><option><?= $bt ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control numbers-only" pattern="\d+" placeholder="09123456789"></div>
                        <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" placeholder="email@example.com"></div>
                        <div class="col-12"><label class="form-label">Home Address</label><textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea></div>
                    </div>
                    <h6 class="fw-bold text-danger mb-3 mt-4 border-bottom pb-2"><i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Contact Name</label><input type="text" name="emergency_name" class="form-control letters-only" pattern="[A-Za-z\s]+" placeholder="Full name of emergency contact"></div>
                        <div class="col-md-6"><label class="form-label">Contact Phone</label><input type="text" name="emergency_phone" class="form-control numbers-only" pattern="\d+" placeholder="09123456789"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_patient" class="btn btn-primary"><i class="fas fa-save me-2"></i>Register Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== APPOINTMENTS ===================== -->
<?php elseif ($page == 'appointments'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-calendar-check me-2 text-primary"></i>Appointment Management</div>
        <div class="section-sub">Schedule online, phone bookings, manage cancellations and status updates.</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookApptModal">
        <i class="fas fa-plus me-2"></i>Book Appointment
    </button>
</div>

<div class="row g-3 mb-3">
    <?php
    $sched = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='Scheduled'")->fetch_assoc()['c'];
    $comp = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='Completed'")->fetch_assoc()['c'];
    $canc = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='Cancelled'")->fetch_assoc()['c'];
    ?>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-info"><div class="fs-3 fw-bold text-info"><?= $sched ?></div><div class="small text-muted">Scheduled</div></div></div>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-success"><div class="fs-3 fw-bold text-success"><?= $comp ?></div><div class="small text-muted">Completed</div></div></div>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-danger"><div class="fs-3 fw-bold text-danger"><?= $canc ?></div><div class="small text-muted">Cancelled</div></div></div>
</div>

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">All Appointments</h6>
        <div class="d-flex gap-2">
            <select id="filterStatus" class="form-select form-select-sm" onchange="filterTable()" style="width:auto">
                <option value="">All Status</option>
                <option>Scheduled</option>
                <option>Completed</option>
                <option>Cancelled</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="apptTable">
            <thead class="table-light">
                <tr><th>Patient</th><th>Date & Time</th><th>Doctor</th><th>Type</th><th>Status</th><th>Notes</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $appointments->data_seek(0);
            if ($appointments->num_rows == 0): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No appointments found</td></tr>
            <?php else: while ($row = $appointments->fetch_assoc()):
                $sc = $row['status']=='Scheduled'?'info':($row['status']=='Completed'?'success':'danger'); ?>
                <tr data-status="<?= $row['status'] ?>">
                    <td>
                        <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($row['student_id']) ?></small>
                    </td>
                    <td>
                        <?= date('M d, Y', strtotime($row['appointment_date'])) ?><br>
                        <small class="text-muted"><?= date('h:i A', strtotime($row['appointment_time'])) ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= $row['type'] ?></span></td>
                    <td><span class="badge bg-<?= $sc ?>"><?= $row['status'] ?></span></td>
                    <td><small><?= htmlspecialchars(substr($row['notes'],0,40)) ?><?= strlen($row['notes'])>40?'...':'' ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($row['status'] == 'Scheduled'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="appt_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="Completed">
                                <button type="submit" name="update_appt_status" class="btn btn-sm btn-outline-success" title="Mark Complete"><i class="fas fa-check"></i></button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this appointment?')">
                                <input type="hidden" name="appt_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="cancel_appointment" class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Book Appointment Modal -->
<div class="modal fade" id="bookApptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">-- Select Patient --</option>
                                <?php $allPatients->data_seek(0); while($p = $allPatients->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['student_id']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Doctor/Physician *</label><input type="text" name="doctor_name" class="form-control letters-only" pattern="[A-Za-z\s\.]+" required placeholder="Dr. "></div>
                        <div class="col-md-6"><label class="form-label">Date *</label><input type="date" name="appt_date" class="form-control" required min="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Time *</label><input type="time" name="appt_time" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Appointment Type *</label>
                            <select name="appt_type" class="form-select" required>
                                <option>Consultation</option>
                                <option>Check-up</option>
                                <option>Emergency</option>
                                <option>Phone Booking</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="appt_notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="book_appointment" class="btn btn-success"><i class="fas fa-calendar-check me-2"></i>Book Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MEDICAL RECORDS ===================== -->
<?php elseif ($page == 'medical'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-file-medical-alt me-2 text-success"></i>Health Records & Vitals</div>
        <div class="section-sub">Patient medical history, vital signs tracking, consultation notes, and prescriptions.</div>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMedicalModal">
        <i class="fas fa-plus me-2"></i>Add Record
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Patient</th><th>Date</th><th>Vitals</th><th>Diagnosis</th><th>Prescription</th><th>Notes</th></tr>
            </thead>
            <tbody>
            <?php
            $medRecords->data_seek(0);
            if ($medRecords->num_rows == 0): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No medical records found</td></tr>
            <?php else: while ($row = $medRecords->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['full_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['student_id']) ?></small></td>
                    <td><small><?= date('M d, Y<br>h:i A', strtotime($row['record_date'])) ?></small></td>
                    <td>
                        <?php if($row['bp']): ?><span class="vital-badge bg-light border"><i class="fas fa-heartbeat text-danger me-1"></i><?= $row['bp'] ?></span><br><?php endif; ?>
                        <?php if($row['weight']): ?><span class="vital-badge bg-light border"><i class="fas fa-weight text-info me-1"></i><?= $row['weight'] ?>kg</span><?php endif; ?>
                        <?php if($row['height']): ?><span class="vital-badge bg-light border"><i class="fas fa-ruler text-success me-1"></i><?= $row['height'] ?>cm</span><?php endif; ?>
                        <?php if($row['temperature']): ?><span class="vital-badge bg-light border"><i class="fas fa-thermometer text-warning me-1"></i><?= $row['temperature'] ?>°C</span><?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars(substr($row['diagnosis'],0,50)) ?><?= strlen($row['diagnosis'])>50?'...':'' ?></small></td>
                    <td><small><?= htmlspecialchars(substr($row['prescription'],0,50)) ?><?= strlen($row['prescription'])>50?'...':'' ?></small></td>
                    <td><small class="text-muted"><?= htmlspecialchars(substr($row['consultation_notes'],0,40)) ?><?= strlen($row['consultation_notes'])>40?'...':'' ?></small></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Medical Record Modal -->
<div class="modal fade" id="addMedicalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-medical me-2"></i>Add Medical Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">-- Select Patient --</option>
                            <?php $allPatients->data_seek(0); while($p = $allPatients->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['student_id']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2"><i class="fas fa-heartbeat me-2 text-danger"></i>Vital Signs</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><label class="form-label">Blood Pressure</label><input type="text" name="bp" class="form-control" placeholder="120/80"></div>
                        <div class="col-md-3"><label class="form-label">Weight (kg)</label><input type="text" name="weight" class="form-control numbers-only" pattern="\d+(\.\d+)?" placeholder="e.g., 65"></div>
                        <div class="col-md-3"><label class="form-label">Height (cm)</label><input type="text" name="height" class="form-control numbers-only" pattern="\d+(\.\d+)?" placeholder="e.g., 165"></div>
                        <div class="col-md-3"><label class="form-label">Temperature (°C)</label><input type="text" name="temp" class="form-control numbers-only" pattern="\d+(\.\d+)?" placeholder="e.g., 36.5"></div>
                    </div>
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2"><i class="fas fa-stethoscope me-2 text-success"></i>Clinical Notes</h6>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Diagnosis</label><textarea name="diagnosis" class="form-control" rows="2" placeholder="Patient diagnosis..."></textarea></div>
                        <div class="col-12"><label class="form-label">Prescription / Treatment</label><textarea name="prescription" class="form-control" rows="2" placeholder="Medications, dosage, frequency..."></textarea></div>
                        <div class="col-12"><label class="form-label">Consultation Notes</label><textarea name="consultation_notes" class="form-control" rows="3" placeholder="Detailed consultation notes..."></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_medical_record" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== INVENTORY ===================== -->
<?php elseif ($page == 'inventory'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-boxes me-2 text-warning"></i>Inventory & Supplies</div>
        <div class="section-sub">Manage medical supplies, medicines, and equipment stock levels.</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInvModal">
        <i class="fas fa-plus me-2"></i>Add Item
    </button>
</div>

<div class="row g-3 mb-3">
    <?php
    $inStock = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE status='In Stock'")->fetch_assoc()['c'];
    $lowS = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE status='Low Stock'")->fetch_assoc()['c'];
    $outS = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE status='Out of Stock'")->fetch_assoc()['c'];
    ?>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-success"><div class="fs-3 fw-bold text-success"><?= $inStock ?></div><div class="small text-muted">In Stock</div></div></div>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-warning"><div class="fs-3 fw-bold text-warning"><?= $lowS ?></div><div class="small text-muted">Low Stock</div></div></div>
    <div class="col-4"><div class="card p-3 text-center border-start border-4 border-danger"><div class="fs-3 fw-bold text-danger"><?= $outS ?></div><div class="small text-muted">Out of Stock</div></div></div>
</div>

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">Inventory List</h6>
        <select id="filterCat" class="form-select form-select-sm" style="width:auto" onchange="filterInv()">
            <option value="">All Categories</option>
            <option>Medicine</option>
            <option>Equipment</option>
            <option>Supply</option>
        </select>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="invTable">
            <thead class="table-light">
                <tr><th>Item Name</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $inventory->data_seek(0);
            if ($inventory->num_rows == 0): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No items in inventory</td></tr>
            <?php else: while ($row = $inventory->fetch_assoc()):
                $sc = $row['status']=='In Stock'?'success':($row['status']=='Low Stock'?'warning':'danger'); ?>
                <tr data-category="<?= $row['category'] ?>">
                    <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                    <td><span class="badge bg-light text-dark border"><?= $row['category'] ?></span></td>
                    <td class="fw-bold"><?= $row['quantity'] ?></td>
                    <td><?= $row['unit'] ?></td>
                    <td><span class="badge bg-<?= $sc ?> <?= $sc=='warning'?'text-dark':'' ?>"><?= $row['status'] ?></span></td>
                    <td><small><?= date('M d, Y', strtotime($row['updated_at'])) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-success" onclick="restockItem(<?= $row['id'] ?>, '<?= htmlspecialchars($row['item_name']) ?>')" title="Restock"><i class="fas fa-plus-circle"></i></button>
                            <button class="btn btn-sm btn-outline-warning" onclick="editItem(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['item_name'])) ?>', '<?= $row['category'] ?>', <?= $row['quantity'] ?>, '<?= $row['unit'] ?>')" title="Edit"><i class="fas fa-edit"></i></button>
                            <form method="POST" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="inv_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_inventory" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Inventory Modal -->
<div class="modal fade" id="addInvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Item Name *</label><input type="text" name="item_name" class="form-control" required placeholder="e.g., Paracetamol 500mg"></div>
                        <div class="col-md-6"><label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option>Medicine</option>
                                <option>Equipment</option>
                                <option>Supply</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Quantity *</label><input type="number" name="quantity" class="form-control" required min="0" placeholder="0"></div>
                        <div class="col-md-3"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="pcs/box/ml"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_inventory" class="btn btn-warning"><i class="fas fa-save me-2"></i>Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div class="modal fade" id="editInvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="inv_id" id="editInvId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Item Name *</label><input type="text" name="item_name" id="editInvName" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Category *</label>
                            <select name="category" id="editInvCat" class="form-select" required>
                                <option>Medicine</option><option>Equipment</option><option>Supply</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Quantity *</label><input type="number" name="quantity" id="editInvQty" class="form-control" required min="0"></div>
                        <div class="col-md-3"><label class="form-label">Unit</label><input type="text" name="unit" id="editInvUnit" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_inventory" class="btn btn-warning"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Restock Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="inv_id" id="restockId">
                <div class="modal-body">
                    <p class="mb-2 fw-semibold" id="restockItemName"></p>
                    <label class="form-label">Quantity to Add *</label>
                    <input type="number" name="add_quantity" class="form-control" min="1" required placeholder="Enter quantity">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="restock_inventory" class="btn btn-success"><i class="fas fa-save me-2"></i>Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== ROOMS ===================== -->
<?php elseif ($page == 'rooms'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-door-open me-2 text-info"></i>Rooms & Equipment</div>
        <div class="section-sub">Manage room availability, equipment booking and status.</div>
    </div>
    <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#addRoomModal">
        <i class="fas fa-plus me-2"></i>Add Room
    </button>
</div>

<div class="row g-3">
<?php
$rooms->data_seek(0);
while ($row = $rooms->fetch_assoc()):
    $sc = $row['status']=='Available'?'success':($row['status']=='Occupied'?'warning':'danger');
    $icon = $row['room_type']=='Consultation Room'?'user-md':($row['room_type']=='Treatment Room'?'briefcase-medical':($row['room_type']=='Waiting Area'?'chair':'archive'));
?>
<div class="col-md-4 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:40px;height:40px;background:<?= $sc=='success'?'#d4edda':($sc=='warning'?'#fff3cd':'#f8d7da') ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-<?= $icon ?> text-<?= $sc ?>"></i>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($row['room_name']) ?></div>
                        <small class="text-muted"><?= $row['room_type'] ?></small>
                    </div>
                </div>
                <span class="badge bg-<?= $sc ?> <?= $sc=='warning'?'text-dark':'' ?>"><?= $row['status'] ?></span>
            </div>
            <?php if ($row['notes']): ?><p class="small text-muted mb-3"><?= htmlspecialchars($row['notes']) ?></p><?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="editRoom(<?= $row['id'] ?>, '<?= $row['status'] ?>', '<?= htmlspecialchars(addslashes($row['notes'])) ?>')">
                <i class="fas fa-edit me-1"></i>Update Status
            </button>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Room Name *</label><input type="text" name="room_name" class="form-control" required placeholder="e.g., Consultation Room 1"></div>
                        <div class="col-md-6"><label class="form-label">Room Type</label>
                            <select name="room_type" class="form-select">
                                <option>Consultation Room</option>
                                <option>Treatment Room</option>
                                <option>Waiting Area</option>
                                <option>Storage</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Status</label>
                            <select name="room_status" class="form-select">
                                <option>Available</option>
                                <option>Occupied</option>
                                <option>Under Maintenance</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="room_notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_room" class="btn btn-info text-white"><i class="fas fa-save me-2"></i>Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="room_id" id="editRoomId">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select name="room_status" id="editRoomStatus" class="form-select">
                            <option>Available</option>
                            <option>Occupied</option>
                            <option>Under Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="room_notes" id="editRoomNotes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_room" class="btn btn-secondary text-white">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MAINTENANCE ===================== -->
<?php elseif ($page == 'maintenance'): ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <div class="section-title"><i class="fas fa-tools me-2 text-secondary"></i>Equipment Maintenance</div>
        <div class="section-sub">Track equipment maintenance schedules and service status.</div>
    </div>
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addMaintModal">
        <i class="fas fa-plus me-2"></i>Schedule Maintenance
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Equipment</th><th>Scheduled Date</th><th>Technician</th><th>Status</th><th>Notes</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $maintenance->data_seek(0);
            if ($maintenance->num_rows == 0): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No maintenance schedules</td></tr>
            <?php else: while ($row = $maintenance->fetch_assoc()):
                $sc = $row['status']=='Completed'?'success':($row['status']=='In Progress'?'warning':'danger'); ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['equipment_name']) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?></td>
                    <td><?= htmlspecialchars($row['technician'] ?: 'N/A') ?></td>
                    <td><span class="badge bg-<?= $sc ?> <?= $sc=='warning'?'text-dark':'' ?>"><?= $row['status'] ?></span></td>
                    <td><small><?= htmlspecialchars(substr($row['notes'],0,40)) ?></small></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="maint_id" value="<?= $row['id'] ?>">
                            <select name="maint_status" class="form-select form-select-sm" style="width:auto">
                                <option <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                <option <?= $row['status']=='In Progress'?'selected':'' ?>>In Progress</option>
                                <option <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_maintenance" class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Maintenance Modal -->
<div class="modal fade" id="addMaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-tools me-2"></i>Schedule Maintenance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Equipment Name *</label><input type="text" name="equipment_name" class="form-control" required placeholder="e.g., ECG Machine, Blood Pressure Monitor"></div>
                        <div class="col-md-6"><label class="form-label">Scheduled Date *</label><input type="date" name="schedule_date" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Technician</label><input type="text" name="technician" class="form-control" placeholder="Technician name"></div>
                        <div class="col-12"><label class="form-label">Status</label>
                            <select name="maint_status" class="form-select">
                                <option>Pending</option>
                                <option>In Progress</option>
                                <option>Completed</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="maint_notes" class="form-control" rows="2" placeholder="Details about the maintenance..."></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_maintenance" class="btn btn-secondary text-white"><i class="fas fa-save me-2"></i>Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== REPORTS ===================== -->
<?php elseif ($page == 'reports'): ?>
<div class="section-title"><i class="fas fa-chart-bar me-2 text-primary"></i>Clinic Reports</div>
<div class="section-sub">Summary statistics and analytics for clinic operations.</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-primary"><?= $totalPatients ?></div><div class="text-muted small">Total Patients</div></div></div>
    <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-success"><?= $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='Completed'")->fetch_assoc()['c'] ?></div><div class="text-muted small">Completed Consultations</div></div></div>
    <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-warning"><?= $totalInventory ?></div><div class="text-muted small">Inventory Items</div></div></div>
    <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-2 fw-bold text-info"><?= $conn->query("SELECT COUNT(*) as c FROM medical_records")->fetch_assoc()['c'] ?></div><div class="text-muted small">Medical Records</div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="fas fa-calendar me-2 text-primary"></i>Appointments by Type</h6></div>
            <div class="card-body">
            <?php
            $apptTypes = $conn->query("SELECT type, COUNT(*) as cnt FROM appointments GROUP BY type");
            while ($r = $apptTypes->fetch_assoc()):
                $total = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
                $pct = $total > 0 ? round(($r['cnt']/$total)*100) : 0;
                $colors = ['Consultation'=>'primary','Check-up'=>'success','Emergency'=>'danger','Phone Booking'=>'info'];
                $color = $colors[$r['type']] ?? 'secondary';
            ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold"><?= $r['type'] ?></span>
                    <span class="small text-muted"><?= $r['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-warning"></i>Inventory by Category</h6></div>
            <div class="card-body">
            <?php
            $invCats = $conn->query("SELECT category, COUNT(*) as cnt, SUM(quantity) as total_qty FROM inventory GROUP BY category");
            while ($r = $invCats->fetch_assoc()):
                $colors = ['Medicine'=>'success','Equipment'=>'primary','Supply'=>'warning'];
                $color = $colors[$r['category']] ?? 'secondary';
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                <div><span class="badge bg-<?= $color ?> me-2"><?= $r['category'] ?></span><?= $r['cnt'] ?> types</div>
                <div class="fw-bold"><?= $r['total_qty'] ?> units total</div>
            </div>
            <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="fas fa-user-injured me-2 text-info"></i>Patient Demographics</h6></div>
            <div class="card-body">
            <?php
            $genders = $conn->query("SELECT gender, COUNT(*) as cnt FROM patients WHERE gender != '' AND gender IS NOT NULL GROUP BY gender");
            while ($r = $genders->fetch_assoc()):
                $total = $conn->query("SELECT COUNT(*) as c FROM patients WHERE gender IS NOT NULL AND gender != ''")->fetch_assoc()['c'];
                $pct = $total > 0 ? round(($r['cnt']/$total)*100) : 0;
                $color = $r['gender']=='Male'?'primary':($r['gender']=='Female'?'danger':'secondary');
            ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold"><?= $r['gender'] ?></span>
                    <span class="small text-muted"><?= $r['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between"><h6 class="mb-0 fw-bold"><i class="fas fa-print me-2"></i>Quick Reports</h6></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="print_report.php?type=patients" target="_blank" class="btn btn-outline-primary"><i class="fas fa-users me-2"></i>Print Patient List</a>
                    <a href="appointmen.print.php?type=appointments" target="_blank" class="btn btn-outline-success"><i class="fas fa-calendar me-2"></i>Print Appointments Report</a>
                    <a href="print_report.php?type=inventory" target="_blank" class="btn btn-outline-warning"><i class="fas fa-boxes me-2"></i>Print Inventory Report</a>
                </div>
            </div>
        </div>
    </div>
</div>



<?php endif; ?>

            </div>
        </div>
    

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle (mobile)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('show');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// Filter appointment table by status
function filterTable() {
    const val = document.getElementById('filterStatus').value.toLowerCase();
    document.querySelectorAll('#apptTable tbody tr').forEach(r => {
        r.style.display = (!val || r.dataset.status.toLowerCase() === val) ? '' : 'none';
    });
}

// Filter inventory by category
function filterInv() {
    const val = document.getElementById('filterCat').value;
    document.querySelectorAll('#invTable tbody tr').forEach(r => {
        r.style.display = (!val || r.dataset.category === val) ? '' : 'none';
    });
}

// Edit inventory item
function editItem(id, name, cat, qty, unit) {
    document.getElementById('editInvId').value = id;
    document.getElementById('editInvName').value = name;
    document.getElementById('editInvCat').value = cat;
    document.getElementById('editInvQty').value = qty;
    document.getElementById('editInvUnit').value = unit;
    new bootstrap.Modal(document.getElementById('editInvModal')).show();
}

// Restock item
function restockItem(id, name) {
    document.getElementById('restockId').value = id;
    document.getElementById('restockItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('restockModal')).show();
}

// Edit room
function editRoom(id, status, notes) {
    document.getElementById('editRoomId').value = id;
    document.getElementById('editRoomStatus').value = status;
    document.getElementById('editRoomNotes').value = notes;
    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

// Auto-dismiss alerts after 4 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        const alert = new bootstrap.Alert(a);
        alert.close();
    });
}, 4000);


// validation para sa paginput sa lahat 
document.addEventListener('input', function(e){
    if(e.target.matches('.letters-only')){
        e.target.value = e.target.value.replace(/[^a-zA-Z\s\.]/g,'');
    }
    if(e.target.matches('.numbers-only')){
        e.target.value = e.target.value.replace(/[^0-9\.]/g,'');
    }
});


</script>
</body>
</html>
