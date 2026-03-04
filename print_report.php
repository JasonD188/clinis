<?php
session_start();
include 'db.php';

$data = $conn->query("SELECT * FROM patients ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Patient List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="p-4">

<h3>Patient List</h3>

<table class="table table-bordered">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Student ID</th>
    <th>Full Name</th>
    <th>Gender</th>
    <th>Blood Type</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php $i=1; while($r=$data->fetch_assoc()): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($r['student_id']) ?></td>
    <td><?= htmlspecialchars($r['full_name']) ?></td>
    <td><?= $r['gender'] ?></td>
    <td><?= $r['blood_type'] ?></td>
    <td>
        

        <a href="x.ray.php?id=<?= $r['id'] ?>" 
           class="btn btn-primary btn-sm">
           <i class="fas fa-edit"></i> Edit
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>


</body>
</html>