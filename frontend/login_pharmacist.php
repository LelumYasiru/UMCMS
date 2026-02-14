<?php
require '../backend/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if(!empty($username) && !empty($password)){
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'pharmacist'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: pharmacy.php");
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
    <title>Pharmacy Login - UMCMS</title>
    <link rel="stylesheet" href="style.css?v=8.0">
</head>
<body>

<div class="auth-wrapper">
    <div class="card">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 300px; width: 100%; height: auto;">
        </div>
        <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">Pharmacy Portal</h2>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Pharmacist Username</label>
            <input type="text" name="username" class="input-field" placeholder="Pharmacist Username" required>

            <label>Password</label>
            <input type="password" name="password" class="input-field" placeholder="Password" required>

            <button type="submit" class="btn btn-secondary" style="width: 100%;">Login to Pharmacy</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #999; text-decoration: none; font-size: 0.9rem;">Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>
