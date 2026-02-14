<?php
require '../backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student'; // Default fallback
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $user_id])) {
                $message = "Password updated successfully.";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $role === 'student') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = trim($_POST['gender']);
    $allergies = trim($_POST['allergies']);
    $faculty = trim($_POST['faculty']);

    
    // Handle Profile Pic
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_imgs = ['jpg', 'jpeg', 'png', 'gif'];
        $filename_img = $_FILES['profile_picture']['name'];
        $ext_img = strtolower(pathinfo($filename_img, PATHINFO_EXTENSION));
        
        if (in_array($ext_img, $allowed_imgs)) {
            // Get Reg No for naming
            $stmt = $pdo->prepare("SELECT reg_number FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $reg_no = $stmt->fetchColumn();

            $new_name_img = "profile_" . $reg_no . "_" . time() . "." . $ext_img;
            if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0777, true);
            $dest_img = "uploads/profiles/" . $new_name_img;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest_img)) {
                $stmt = $pdo->prepare("UPDATE students SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$new_name_img, $user_id]);
                $message = "Profile picture updated!";
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid image format.";
        }
    }

    // Update Text Fields
    $stmt = $pdo->prepare("UPDATE students SET email = ?, phone_number = ?, gender = ?, allergies = ?, faculty = ? WHERE user_id = ?");
    if ($stmt->execute([$email, $phone, $gender, $allergies, $faculty, $user_id])) {
        $message = "Profile details updated!";
    }
}

// Fetch Student Current Details if Student
$student_info = null;
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_info = $stmt->fetch();
}

// Determine dashboard link
$dashboard_link = '#';
switch ($role) {
    case 'admin': $dashboard_link = 'admin.php'; break;
    case 'pharmacist': $dashboard_link = 'pharmacy.php'; break;
    case 'doctor': $dashboard_link = 'doctor.php'; break;
    case 'student': $dashboard_link = 'student.php'; break;
    default: $dashboard_link = 'login.php'; break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link rel="stylesheet" href="style.css?v=8.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center; padding: 10px 0;">
                <img src="images/vavuniya_logo.png" style="width: 180px; height: auto; margin-bottom: 15px;">
                <span style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">Settings</span>
            </div>
            <ul class="nav-links">
                <li><a href="<?php echo $dashboard_link; ?>"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
                <li><a href="#" class="active"><i class="fas fa-key"></i> Security</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <h1 class="header-title">Account Settings</h1>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                
                <?php if($role === 'student' && $student_info): ?>
                <!-- Update Profile (Student Only) -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3><i class="fas fa-user-edit"></i> Update Profile</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div style="text-align: center; margin-bottom: 20px;">
                            <?php 
                                $pic = $student_info['profile_picture'] && file_exists('uploads/profiles/'.$student_info['profile_picture']) 
                                       ? 'uploads/profiles/'.$student_info['profile_picture'] 
                                       : 'https://ui-avatars.com/api/?name='.urlencode($student_info['full_name']);
                            ?>
                            <img src="<?php echo $pic; ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #eee;">
                        </div>

                        <label>Profile Picture</label>
                        <input type="file" name="profile_picture" class="input-field" accept="image/*" style="padding: 10px;">

                        <label>Email Address</label>
                        <input type="email" name="email" class="input-field" value="<?php echo htmlspecialchars($student_info['email'] ?? ''); ?>" placeholder="Email">

                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="input-field" value="<?php echo htmlspecialchars($student_info['phone_number'] ?? ''); ?>" placeholder="Phone">

                        <label>Gender</label>
                        <select name="gender" class="input-field">
                            <option value="Male" <?php echo ($student_info['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student_info['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>

                        <label>Faculty</label>
                        <select name="faculty" class="input-field" required>
                            <option value="Faculty of Technology Studies" <?php echo ($student_info['faculty'] == 'Faculty of Technology Studies') ? 'selected' : ''; ?>>Faculty of Technology Studies</option>
                            <option value="Faculty of Applied Science" <?php echo ($student_info['faculty'] == 'Faculty of Applied Science') ? 'selected' : ''; ?>>Faculty of Applied Science</option>
                            <option value="Faculty of Business Studies" <?php echo ($student_info['faculty'] == 'Faculty of Business Studies') ? 'selected' : ''; ?>>Faculty of Business Studies</option>
                        </select>


                        <label>Allergies</label>
                        <textarea name="allergies" class="input-field" placeholder="List any allergies..." style="height: 80px; resize: none;"><?php echo htmlspecialchars($student_info['allergies'] ?? ''); ?></textarea>

                        <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Change Password -->
                <div class="card" style="flex: 1; min-width: 300px; height: fit-content;">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="input-field" required>

                    <label>New Password</label>
                    <input type="password" name="new_password" class="input-field" required>

                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="input-field" required>

                    <button type="submit" class="btn" style="width: 100%;">Update Password</button>
                </form>
            </div>
            </div> <!-- End Flex Container -->
        </main>
    </div>
</body>
</html>
