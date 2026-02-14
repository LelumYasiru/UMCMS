<?php
require '../backend/config.php';

// Ensure Database Schema is Up-to-Date for Reporting
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN faculty VARCHAR(100) DEFAULT 'General' AFTER full_name");
} catch (Exception $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS medical_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    rest_days INT NOT NULL,
    start_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
)");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

$view = $_GET['view'] ?? 'dashboard';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $pass = password_hash('password123', PASSWORD_DEFAULT);

        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if(!$stmt->fetch()){
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $pass, $role]);
            $message = "User $username added as $role.";
        }
    } elseif ($_POST['action'] === 'remove_user') {
        $username = trim($_POST['username']);
        
        // Prevent deleting self
        if ($username === $_SESSION['username']) {
            $message = "You cannot delete yourself!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $message = "User '$username' has been removed successfully.";
            } else {
                $message = "User '$username' not found.";
            }
        }
    } elseif ($_POST['action'] === 'add_medicine') {
        $name = trim($_POST['name']);
        $qty = (int)$_POST['quantity'];
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE to merge quantities if medicine exists
        $stmt = $pdo->prepare("INSERT INTO medicines (name, stock_quantity) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)");
        $stmt->execute([$name, $qty]);
        $message = "Medicine '$name' stock updated successfully.";
    } elseif ($_POST['action'] === 'remove_medicine') {
        $id = $_POST['medicine_id'];
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Medicine removed.";
    } elseif ($_POST['action'] === 'mark_read') {
        $id = $_POST['message_id'];
        $stmt = $pdo->prepare("UPDATE admin_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Notification marked as read.";
    }
}

// Fetch Stats
$stats = [
    'users' => $pdo->query("SELECT count(*) FROM users")->fetchColumn(),
    'students' => $pdo->query("SELECT count(*) FROM students")->fetchColumn(),
    'medicines' => $pdo->query("SELECT count(*) FROM medicines")->fetchColumn()
];

// Fetch Low Stock items (less than 100)
$low_stock_items = $pdo->query("SELECT name, stock_quantity FROM medicines WHERE stock_quantity < 100")->fetchAll();

// Fetch Admin Messages
try {
    $admin_msgs = $pdo->query("SELECT * FROM admin_messages WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    $admin_msgs = [];
}

// Fetch Data based on View
$users = [];
$medicines = [];

if ($view === 'dashboard' || $view === 'users') {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();
}
if ($view === 'medicines') {
    $medicines = $pdo->query("SELECT * FROM medicines ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
            <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center; padding: 10px 0;">
                <img src="images/vavuniya_logo.png" style="width: 180px; height: auto; margin-bottom: 15px;">
                <span style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">Admin Portal</span>
            </div>
            <ul class="nav-links">
                <li><a href="admin.php?view=dashboard" class="<?php echo $view==='dashboard'?'active':''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="admin.php?view=users" class="<?php echo $view==='users'?'active':''; ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                <li><a href="admin.php?view=medicines" class="<?php echo $view==='medicines'?'active':''; ?>"><i class="fas fa-pills"></i> Manage Medicines</a></li>
                <li><a href="admin.php?view=reports" class="<?php echo $view==='reports'?'active':''; ?>"><i class="fas fa-file-medical-alt"></i> Daily Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <h1 class="header-title">
                <?php 
                    if ($view === 'users') echo 'Manage Users';
                    elseif ($view === 'medicines') echo 'Manage Medicines';
                    elseif ($view === 'reports') echo 'Daily Reports & Analytics';
                    else echo 'Admin Dashboard'; 
                ?>
            </h1>
            
            <?php if(!empty($low_stock_items)): ?>
                <div class="alert alert-error" style="background: #ff7675; color: white; border: none; font-weight: 500;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Low Stock Alert:</strong> 
                    <?php 
                        $names = array_map(function($item) { return $item['name'] . " (" . $item['stock_quantity'] . ")"; }, $low_stock_items);
                        echo implode(", ", $names);
                    ?> 
                    need restocking!
                </div>
            <?php endif; ?>

            <?php if($message): ?><div class="alert alert-info"><?php echo $message; ?></div><?php endif; ?>

            <?php if($view === 'dashboard'): ?>
                <!-- DASHBOARD VIEW -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--primary-color); color: var(--secondary-color);"><i class="fas fa-users"></i></div> 
                        <div><h3><?php echo $stats['users']; ?></h3><p>Total Users</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--secondary-color); color: var(--primary-color);"><i class="fas fa-user-graduate"></i></div> 
                        <div><h3><?php echo $stats['students']; ?></h3><p>Students</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--accent-purple); color: white;"><i class="fas fa-briefcase-medical"></i></div> 
                        <div><h3><?php echo $stats['medicines']; ?></h3><p>Medicines</p></div>
                    </div>
                </div>

                <!-- Messages Section -->
                <?php if(!empty($admin_msgs)): ?>
                <div class="card" style="margin-bottom: 30px; border-left: 5px solid #3498db;">
                    <h3 style="margin-bottom: 15px; color: #3498db;"><i class="fas fa-envelope"></i> Pharmacist Notices</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach($admin_msgs as $msg): ?>
                            <li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; flex-direction: column;">
                                    <span><?php echo htmlspecialchars($msg['message']); ?></span>
                                    <small style="color: #888; font-size: 0.8rem;"><?php echo $msg['created_at']; ?></small>
                                </div>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                    <button type="submit" class="btn" style="background: transparent; color: #2ecc71; padding: 5px; border: 1px solid #2ecc71; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Mark as Read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="table-container">
                    <h3 style="margin-bottom: 20px;">Recent Users</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><span class="badge" style="background: #eee;"><?php echo strtoupper($u['role']); ?></span></td>
                                <td><?php echo $u['created_at']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif($view === 'users'): ?>
                <!-- MANAGE USERS VIEW -->
                <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-top: 20px;">
                    <!-- Add User -->
                    <div class="card" style="flex: 1; min-width: 400px;">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-user-plus"></i> Add New Staff</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_user">
                            <label>Username</label>
                            <input type="text" name="username" class="input-field" placeholder="Username" required>
                            <label>Role</label>
                            <select name="role" class="input-field">
                                <option value="doctor">Doctor</option>
                                <option value="pharmacist">Pharmacist</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" style="width: 100%;">Add New User</button>
                        </form>
                    </div>

                    <!-- Remove User -->
                    <div class="card" style="flex: 1; min-width: 400px; border-left: 5px solid #e74c3c;">
                        <h3 style="margin-bottom: 20px; color: #e74c3c;"><i class="fas fa-user-times"></i> Remove User</h3>
                        <p style="margin-bottom: 15px; font-size: 0.9rem; color: #666;">Enter the Username like 'S12345' or 'doctor1' to completely remove them from the system.</p>
                        <form method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                            <input type="hidden" name="action" value="remove_user">
                            <label>Registration Number / Username</label>
                            <input type="text" name="username" class="input-field" placeholder="e.g. S12345" required>
                            <button type="submit" class="btn" style="width: 100%; background: #e74c3c;">Remove User</button>
                        </form>
                    </div>
                </div>

            <?php elseif($view === 'medicines'): ?>
                <!-- MANAGE MEDICINES VIEW -->
                <div style="display: flex; gap: 30px; margin-top: 20px;">
                    <div class="card" style="width: 350px; height: fit-content;">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Add Medicine</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_medicine">
                            <label>Medicine Name</label>
                            <input type="text" name="name" class="input-field" placeholder="e.g. Paracetamol" required>
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="input-field" placeholder="e.g. 100" required>
                            <button class="btn" style="width: 100%;">Add to Stock</button>
                        </form>
                    </div>

                    <div class="table-container" style="flex: 1;">
                        <h3>Inventory</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th style="width: 100px; text-align: center;">Stock</th>
                                    <th style="width: 100px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($medicines as $m): 
                                    $is_low = $m['stock_quantity'] < 100;
                                ?>
                                <tr style="<?php echo $is_low ? 'background: #fff5f5;' : ''; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($m['name']); ?>
                                        <?php if($is_low): ?>
                                            <i class="fas fa-exclamation-circle" style="color: #e74c3c;" title="Low Stock!"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; font-weight: bold; color: <?php echo $is_low ? '#e74c3c' : 'inherit'; ?>;">
                                        <?php echo $m['stock_quantity']; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" onsubmit="return confirm('Delete this medicine?');" style="margin:0;">
                                            <input type="hidden" name="action" value="remove_medicine">
                                            <input type="hidden" name="medicine_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" class="btn" style="background: #e74c3c; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif($view === 'reports'): ?>
                <!-- REPORTS & ANALYTICS VIEW -->
                <?php
                    $filter_date = $_GET['date'] ?? date('Y-m-d');
                    
                    // Base Query for Visits (Prescriptions)
                    // Note: We use LEFT JOIN for medical_certificates because not every visit has one
                    // But we join on student_id and DATE(created_at).
                    // Subquery approach is safer to avoid duplication if multiple MCs (unlikely but possible)
                    
                    $sql = "SELECT p.*, s.reg_number, s.full_name, s.faculty, u.username as doctor_name,
                            (SELECT COUNT(*) FROM medical_certificates mc WHERE mc.student_id = p.student_id AND DATE(mc.created_at) = DATE(p.created_at)) as has_mc,
                            (SELECT diagnosis FROM medical_certificates mc WHERE mc.student_id = p.student_id AND DATE(mc.created_at) = DATE(p.created_at) LIMIT 1) as mc_diagnosis
                            FROM prescriptions p 
                            JOIN students s ON p.student_id = s.id 
                            JOIN users u ON p.doctor_id = u.id 
                            WHERE DATE(p.created_at) = :date
                            ORDER BY p.created_at DESC";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['date' => $filter_date]);
                    $visits = $stmt->fetchAll();

                    // Calculate Stats
                    $total_visits = count($visits);
                    $total_mcs = 0;
                    foreach($visits as $v) {
                        if($v['has_mc'] > 0) $total_mcs++;
                    }
                    
                    $filter_type = $_GET['type'] ?? 'all';
                    $display_visits = $visits;
                    if ($filter_type === 'mc_only') {
                        $display_visits = array_filter($visits, function($v) { return $v['has_mc'] > 0; });
                    }
                ?>

                <div class="card" style="margin-top: 20px; padding: 20px;">
                    <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
                        <input type="hidden" name="view" value="reports">
                        <div>
                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Filter Date</label>
                            <input type="date" name="date" class="input-field" value="<?php echo $filter_date; ?>" style="margin: 0; padding: 8px 12px;">
                        </div>
                        <div>
                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Filter Type</label>
                            <select name="type" class="input-field" style="margin: 0; padding: 10px; width: 200px;">
                                <option value="all" <?php echo $filter_type=='all'?'selected':''; ?>>Show All Visits</option>
                                <option value="mc_only" <?php echo $filter_type=='mc_only'?'selected':''; ?>>Only MC Issued</option>
                            </select>
                        </div>
                        <button type="submit" class="btn" style="background: var(--primary-color);">Apply Filter</button>
                    </form>
                </div>

                <div class="stats-grid" style="margin: 25px 0;">
                    <div class="stat-card" style="border-left: 5px solid var(--secondary-color);">
                        <div class="stat-icon" style="background: var(--secondary-color); color: white;"><i class="fas fa-user-injured"></i></div> 
                        <div><h3><?php echo $total_visits; ?></h3><p>Total Patients</p></div>
                    </div>
                    <div class="stat-card" style="border-left: 5px solid #e74c3c;">
                        <div class="stat-icon" style="background: #e74c3c; color: white;"><i class="fas fa-file-medical"></i></div> 
                        <div><h3><?php echo $total_mcs; ?></h3><p>Medical Certificates Issued</p></div>
                    </div>
                </div>

                <div class="table-container">
                    <h3 style="margin-bottom: 15px;">Patient Visit Records (<?php echo $filter_date; ?>)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Faculty</th>
                                <th>Doctor</th>
                                <th>Symptoms / Notes</th>
                                <th>MC Status</th>
                                <th>MC Diagnosis (Lede)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($display_visits)): ?>
                                <tr><td colspan="7" style="text-align: center; padding: 20px;">No records found for this date.</td></tr>
                            <?php else: ?>
                                <?php foreach($display_visits as $visit): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($visit['created_at'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($visit['full_name']); ?></strong><br>
                                        <small style="color: grey;"><?php echo htmlspecialchars($visit['reg_number']); ?></small>
                                    </td>
                                    <td><span class="badge" style="background: #eee;"><?php echo htmlspecialchars($visit['faculty'] ?? '-'); ?></span></td>
                                    <td>Dr. <?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($visit['notes']); ?></td>
                                    <td>
                                        <?php if($visit['has_mc'] > 0): ?>
                                            <span class="badge" style="background: #e74c3c; color: white;">ISSUED</span>
                                        <?php else: ?>
                                            <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: bold; color: #2c3e50;">
                                        <?php echo $visit['mc_diagnosis'] ? htmlspecialchars($visit['mc_diagnosis']) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
