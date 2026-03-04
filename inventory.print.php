<?php
session_start();
include 'db.php';

$date_today = date("F d, Y h:i A");

// =======================================
// STEP 1: SELECT ITEM IF NO ID
// =======================================
if (!isset($_GET['id'])) {

    $allItems = $conn->query("SELECT id, item_name, category FROM inventory ORDER BY item_name ASC");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Select Inventory Item</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-4">

        <h3>Select Inventory Item to Print</h3>

        <div class="list-group mt-3">
            <?php while($item = $allItems->fetch_assoc()): ?>
                <a href="?id=<?= $item['id'] ?>" 
                   class="list-group-item list-group-item-action">
                    <?= htmlspecialchars($item['item_name']) ?>
                    <small class="text-muted">
                        (<?= htmlspecialchars($item['category']) ?>)
                    </small>
                </a>
            <?php endwhile; ?>
        </div>

    </body>
    </html>
    <?php
    exit;
}

// =======================================
// STEP 2: FETCH SELECTED ITEM
// =======================================
$item_id = intval($_GET['id']);

$itemQuery = $conn->query("SELECT * FROM inventory WHERE id = $item_id");

if ($itemQuery->num_rows == 0) {
    die("Inventory item not found.");
}

$itemData = $itemQuery->fetch_assoc();

// =======================================
// STEP 3: FETCH LATEST ACTION DATE
// =======================================
$logQuery = $conn->query("SELECT action_date FROM inventory_logs WHERE inventory_id = $item_id ORDER BY action_date DESC LIMIT 1");
$log = $logQuery->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory Item Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { font-size: 14px; }
.report-header { border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
.logo { width: 80px; }
.section-title { margin-top: 40px; margin-bottom: 10px; font-weight: bold; font-size: 16px; border-bottom: 1px solid #000; padding-bottom: 5px; }
@media print { .no-print { display: none; } }
</style>
</head>

<body class="p-4">

<!-- HEADER -->
<div class="report-header d-flex align-items-center">
    <img src="./components/bestlink_college_of_the_philippines_logo.jpg" class="logo me-3">
    <div>
        <h4 class="mb-0">SCHOOL CLINIC MANAGEMENT SYSTEM</h4>
        <small><strong>Inventory Item Report</strong></small><br>
        <small>Date Generated: <?= $date_today ?></small>
    </div>
</div>

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-dark">
        Print / Save as PDF
    </button>
</div>

<!-- INVENTORY INFO -->
<div class="section-title">Item Information</div>

<table class="table table-bordered">
<tr>
    <th width="30%">Item Name</th>
    <td><?= htmlspecialchars($itemData['item_name']) ?></td>
</tr>
<tr>
    <th>Category</th>
    <td><?= htmlspecialchars($itemData['category']) ?></td>
</tr>
<tr>
    <th>Quantity</th>
    <td>
        <?= $itemData['quantity'] ?>
        <?php if($itemData['quantity'] <= 10): ?>
            <span class="badge bg-warning text-dark">Low Stock</span>
        <?php endif; ?>
    </td>
</tr>
<tr>
    <th>Unit</th>
    <td><?= htmlspecialchars($itemData['unit']) ?></td>
</tr>
</table>

<br><br>

<div class="row mt-5">
    <div class="col-6 text-center">
        ___________________________<br>
        <strong>Inventory Officer</strong>
    </div>
    <div class="col-6 text-center">
        ___________________________<br>
        <strong>Administrator</strong>
    </div>
</div>

<script>
window.onload = function() {
    window.print();
}
</script>

</body>
</html>