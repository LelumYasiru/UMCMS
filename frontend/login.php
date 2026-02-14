<?php
require '../backend/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if(!empty($username) && !empty($password) && !empty($_POST['role'])){
        $selected_role = $_POST['role'];
        // Mapping 'pharmacy' to 'pharmacist' in DB if necessary
        $db_role = ($selected_role === 'pharmacy') ? 'pharmacist' : $selected_role;

        // Allow login by Username OR Student Full Name, with role check
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            WHERE (u.username = ? OR s.full_name = ?) AND u.role = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username, $db_role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Role Redirection
            $redirects = [
                'doctor' => 'doctor.php',
                'pharmacist' => 'pharmacy.php',
                'student' => 'student.php',
                'admin' => 'admin.php'
            ];
            header("Location: " . ($redirects[$user['role']] ?? 'index.php'));
            exit;
        } else {
            $error = "Invalid credentials. Please try again.";
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
    <title>Sign In - UMCMS</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <style>
        body { background: var(--bg-gradient) !important; color: white; }
        .auth-wrapper { background: transparent; }
        .card { box-shadow: 0 30px 60px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="card">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 300px; width: 100%; height: auto;">
        </div>
        <h2 style="text-align: center; margin-bottom: 30px;">Welcome Back</h2>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Select Your Role</label>
            <select name="role" class="input-field" required>
                <option value="" disabled selected>-- Select Role --</option>
                <option value="student">Student</option>
                <option value="doctor">Doctor</option>
                <option value="pharmacy">Pharmacy</option>
                <option value="admin">Admin</option>
            </select>

            <label>Username / Reg No</label>
            <input type="text" name="username" class="input-field" placeholder="Enter your username" required>

            <label>Password</label>
            <input type="password" name="password" class="input-field" placeholder="Enter your password" required>

            <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 1.1rem; padding: 15px;">Secure Login</button>
        </form>

        <div id="registration-link" style="text-align: center; margin-top: 25px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px; display: none;">
            <p style="font-size: 0.95rem; color: var(--text-muted);">New Student? <a href="register.php" style="color: var(--primary-color); font-weight: 800; text-decoration: none;">Create Account</a></p>
        </div>

        <div style="text-align: center; margin-top: 15px;">
            <a href="index.php" style="display: block; color: var(--primary-color); text-decoration: none; font-size: 0.9rem; font-weight: 600;">← Back to Home</a>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="role"]').addEventListener('change', function() {
    const regLink = document.getElementById('registration-link');
    if (this.value === 'student') {
        regLink.style.display = 'block';
    } else {
        regLink.style.display = 'none';
    }
});
</script>

</body>
</html>
