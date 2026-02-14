<?php
require '../backend/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if(!empty($username) && !empty($password)){
        // Enhanced student lookup (Reg No OR Name)
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            WHERE (u.username = ? OR s.full_name = ?) AND u.role = 'student'
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: student.php");
            exit;
        } else {
            $error = "Invalid credentials or unauthorized access.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - UMCMS</title>
    <link rel="stylesheet" href="style.css?v=8.0">
</head>
<body>

<div class="auth-wrapper">
    <div class="card">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 300px; width: 100%; height: auto;">
        </div>
        <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">Student Portal</h2>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Reg Number or Full Name</label>
            <input type="text" name="username" class="input-field" placeholder="e.g. S12345 or John Doe" required>

            <label>Password</label>
            <input type="password" name="password" class="input-field" placeholder="Password" required>

            <button type="submit" class="btn btn-secondary" style="width: 100%;">Login as Student</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p style="font-size: 0.9rem;">New Student? <a href="register.php" style="color: var(--secondary-color); font-weight: bold;">Create Account</a></p>
            <a href="index.php" style="display: block; margin-top: 10px; color: #999; text-decoration: none; font-size: 0.9rem;">Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>
