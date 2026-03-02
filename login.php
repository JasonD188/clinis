<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCP Clinic - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a3a5c 0%, #2980b9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: linear-gradient(135deg, #1a3a5c, #2980b9);
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
        }
        .login-header .icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 15px;
        }
        .login-body { padding: 35px 30px; }
        .form-control:focus { border-color: #2980b9; box-shadow: 0 0 0 0.2rem rgba(41,128,185,0.25); }
        .btn-login {
            background: linear-gradient(135deg, #1a3a5c, #2980b9);
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
        }
        .btn-login:hover { opacity: 0.9; }
        .input-group-text { background: #f8f9fa; border-right: none; }
        .form-control { border-left: none; }
        .form-floating .form-control { border-left: 1px solid #ced4da; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="icon"><i class="fas fa-hospital-user"></i></div>
        <h3 class="mb-1">BCP CLINIC</h3>
        <p class="mb-0 opacity-75">Clinic Management System</p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass()"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100 text-white">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        <p class="text-center text-muted mt-3 mb-0" style="font-size:13px;">
            Default: admin / admin123
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
    else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
</body>
</html>
