<?php
require '../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// AUTO-MIGRATION
try { $pdo->exec("ALTER TABLE students ADD COLUMN email VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN gender VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN allergies TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE students ADD COLUMN faculty VARCHAR(100) DEFAULT 'General'"); } catch (Exception $e) {}


$user_id = $_SESSION['user_id'];

// Get Student Info
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$profile_pic = $student['profile_picture'] && file_exists('uploads/profiles/'.$student['profile_picture']) 
               ? 'uploads/profiles/'.$student['profile_picture'] 
               : 'https://ui-avatars.com/api/?name='.urlencode($student['full_name']).'&background=random';

// Get Prescriptions
$stmt = $pdo->prepare("
    SELECT p.*, u.username as doctor_name 
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.id 
    WHERE p.student_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$student['id']]);
$prescriptions = $stmt->fetchAll();

// Get Medical Certificates
$stmt_mc = $pdo->prepare("
    SELECT mc.*, u.username as doctor_name 
    FROM medical_certificates mc 
    JOIN users u ON mc.doctor_id = u.id 
    WHERE mc.student_id = ? 
    ORDER BY mc.created_at DESC
");
$stmt_mc->execute([$student['id']]);
$mcs = $stmt_mc->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255, 255, 255, 0.5);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
        .profile-info h2 { margin-bottom: 5px; }
        .profile-info p { color: #666; font-size: 0.9rem; margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center; padding: 10px 0;">
                <img src="images/vavuniya_logo.png" style="width: 180px; height: auto; margin-bottom: 15px;">
                <span style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">Student Portal</span>
            </div>
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="<?php echo $profile_pic; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                <p style="margin-top: 10px; font-weight: 600; color: #333;"><?php echo htmlspecialchars($student['full_name']); ?></p>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="active"><i class="fas fa-id-card"></i> My Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <div class="profile-header">
                <img src="<?php echo $profile_pic; ?>" class="profile-img">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($student['reg_number']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'No Email'); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone_number'] ?? 'No Phone'); ?></p>
                    <p><i class="fas fa-venus-mars"></i> Gender: <?php echo htmlspecialchars($student['gender'] ?? 'Not specified'); ?></p>
                    <p><i class="fas fa-university"></i> Faculty: <?php echo htmlspecialchars($student['faculty'] ?? 'Not specified'); ?></p>
                    <p><i class="fas fa-tint"></i> Blood Group: <?php echo htmlspecialchars($student['blood_type']); ?></p>
                    <p style="color: #e74c3c; font-weight: 600;"><i class="fas fa-allergies"></i> Allergies: <?php echo htmlspecialchars($student['allergies'] ?: 'None'); ?></p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-medical"></i></div> 
                    <div><h3><?php echo count($prescriptions); ?></h3><p>Total Prescriptions</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--secondary-color); color: var(--primary-color);"><i class="fas fa-certificate"></i></div> 
                    <div><h3><?php echo count($mcs); ?></h3><p>Medical Certificates</p></div>
                </div>
            </div>

            <!-- Medical Certificates Table -->
            <?php if (!empty($mcs)): ?>
            <div class="table-container" style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-certificate"></i> My Medical Certificates</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date Issued</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mcs as $mc): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($mc['created_at'])); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($mc['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($mc['diagnosis']); ?></td>
                            <td><?php echo $mc['rest_days']; ?> Day(s) (From <?php echo $mc['start_date']; ?>)</td>
                            <td>
                                <a href="view_mc.php?id=<?php echo $mc['id']; ?>" target="_blank" class="btn" style="padding: 5px 15px; font-size: 0.8rem;">
                                    <i class="fas fa-download"></i> Download MC
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>


            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Medical History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Prescription / Notes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                            <tr><td colspan="4" style="text-align:center;">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach($prescriptions as $p): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($p['created_at'])); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($p['doctor_name']); ?></td>
                                <td style="max-width: 300px;"><?php echo nl2br(htmlspecialchars($p['notes'])); ?></td>
                                <td>
                                    <?php if($p['status'] == 'dispensed'): ?>
                                        <span class="badge badge-success">Dispensed</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
