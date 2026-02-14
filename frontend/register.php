<?php
require '../backend/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AUTO-MIGRATION: Add columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN email VARCHAR(100) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN gender VARCHAR(20) DEFAULT NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN allergies TEXT DEFAULT NULL");
    } catch (Exception $e) {}

    $reg_no = trim($_POST['reg_no']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $gender = trim($_POST['gender']);
    $faculty = trim($_POST['faculty']);
    $allergies = trim($_POST['allergies']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $blood_type = $_POST['blood_type'];
    
    $medical_report = null;
    $profile_picture = 'default.png'; // Default avatar

    // 1. Handle Medical Report Upload
    if (isset($_FILES['medical_report']) && $_FILES['medical_report']['error'] == 0) {
        $allowed = ['pdf'];
        $filename = $_FILES['medical_report']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "med_" . $reg_no . "_" . time() . "." . $ext;
            if (!is_dir('uploads/reports')) mkdir('uploads/reports', 0777, true);
            $dest = "uploads/reports/" . $new_name;
            if (move_uploaded_file($_FILES['medical_report']['tmp_name'], $dest)) {
                $medical_report = $new_name;
            } else {
                $error = "Failed to upload medical report.";
            }
        } else {
            $error = "Only PDF files are allowed for Medical Report.";
        }
    }

    // 2. Handle Profile Picture Upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_imgs = ['jpg', 'jpeg', 'png', 'gif'];
        $filename_img = $_FILES['profile_picture']['name'];
        $ext_img = strtolower(pathinfo($filename_img, PATHINFO_EXTENSION));
        
        if (in_array($ext_img, $allowed_imgs)) {
            $new_name_img = "profile_" . $reg_no . "_" . time() . "." . $ext_img;
            if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0777, true);
            $dest_img = "uploads/profiles/" . $new_name_img;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest_img)) {
                $profile_picture = $new_name_img;
            } else {
                $error = "Failed to upload profile picture.";
            }
        } else {
            $error = "Only JPG, PNG, GIF allowed for Profile Picture.";
        }
    }

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif(!$error) {
        // Check Exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$reg_no]);
        if ($stmt->fetch()) {
            $error = "User already exists.";
        } else {
            // Register
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                $stmt->execute([$reg_no, $hashed]);
                $uid = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO students (user_id, reg_number, full_name, faculty, email, phone_number, gender, blood_type, allergies, medical_report, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$uid, $reg_no, $full_name, $faculty, $email, $phone_number, $gender, $blood_type, $allergies, $medical_report, $profile_picture]);

                $pdo->commit();
                $message = "Account created! You can now login.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UMCMS</title>
    <link rel="stylesheet" href="style.css?v=8.0">
</head>
<body>

<div class="auth-wrapper">
    <div class="card">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 300px; width: 100%; height: auto;">
        </div>
        <h2 style="text-align: center; margin-bottom: 30px;">Student Registration</h2>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <script>setTimeout(() => window.location.href='login_student.php', 2000);</script>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label style="font-weight: 600; color: #666;">Registration Number</label>
            <input type="text" name="reg_no" class="input-field" placeholder="e.g. S12345" required>

            <label style="font-weight: 600; color: #666;">Full Name</label>
            <input type="text" name="full_name" class="input-field" placeholder="Enter Full Name" required>

            <label style="font-weight: 600; color: #666;">Email Address</label>
            <input type="email" name="email" class="input-field" placeholder="Enter Email" required>

            <label style="font-weight: 600; color: #666;">Phone Number</label>
            <input type="tel" name="phone_number" class="input-field" placeholder="Enter Phone Number" required>

            <label style="font-weight: 600; color: #666;">Profile Picture</label>
            <input type="file" name="profile_picture" class="input-field" accept="image/*" style="padding: 10px;">

            <label style="font-weight: 600; color: #666;">Gender</label>
            <select name="gender" class="input-field" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>

            <label style="font-weight: 600; color: #666;">Faculty</label>
            <select name="faculty" class="input-field" required>
                <option value="">Select Faculty</option>
                <option value="Faculty of Technology Studies">Faculty of Technology Studies</option>
                <option value="Faculty of Applied Science">Faculty of Applied Science</option>
                <option value="Faculty of Business Studies">Faculty of Business Studies</option>
            </select>

            <label style="font-weight: 600; color: #666;">Blood Group</label>
            <select name="blood_type" class="input-field" required>
                <option value="">Select Blood Group</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>

            <label style="font-weight: 600; color: #666;">Allergies (if any)</label>
            <textarea name="allergies" class="input-field" placeholder="List any allergies..." style="height: 100px; resize: none;"></textarea>

            <label style="font-weight: 600; color: #666;">Student Medical (PDF)</label>
            <input type="file" name="medical_report" class="input-field" accept=".pdf" style="padding: 10px;">

            <label style="font-weight: 600; color: #666;">Password</label>
            <input type="password" name="password" class="input-field" placeholder="Create Password" required>

            <label style="font-weight: 600; color: #666;">Confirm Password</label>
            <input type="password" name="confirm_password" class="input-field" placeholder="Confirm Password" required>

            <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 1.1rem; padding: 15px;">Create Student Account</button>
        </form>

        <div style="text-align: center; margin-top: 25px;">
            <p style="color: var(--text-muted); font-weight: 500;">Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 700; text-decoration: none;">Sign In Here</a></p>
            <a href="index.php" style="display: block; margin-top: 15px; color: #999; text-decoration: none; font-size: 0.9rem;">Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>
