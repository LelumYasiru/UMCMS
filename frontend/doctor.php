<?php
require '../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login_doctor.php");
    exit;
}

// AUTO-MIGRATION: Update schema for Medical Certificates
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

$pdo->exec("CREATE TABLE IF NOT EXISTS prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
)");


$message = '';
$search_results = null;
$search_student = null;

// Handle Prescription Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_prescription'])) {
    $reg_no = trim($_POST['reg_no']);
    $notes = trim($_POST['notes']);
    $meds = $_POST['meds'] ?? [];

    $stmt = $pdo->prepare("SELECT id FROM students WHERE reg_number = ?");
    $stmt->execute([$reg_no]);
    $student = $stmt->fetch();

    if ($student) {
        $pdo->beginTransaction();
        try {
            // 1. Insert Prescription
            $stmt = $pdo->prepare("INSERT INTO prescriptions (student_id, doctor_id, notes) VALUES (?, ?, ?)");
            $stmt->execute([$student['id'], $_SESSION['user_id'], $notes]);
            $prescription_id = $pdo->lastInsertId();

            // 2. Insert Items
            $stmt_item = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_id, quantity) VALUES (?, ?, ?)");
            foreach ($meds as $med_id) {
                $stmt_item->execute([$prescription_id, $med_id, 0]); // Store with 0 quantity, pharmacy will set it
            }

            // 3. Optional: Medical Certificate
            if (isset($_POST['is_mc']) && $_POST['is_mc'] == '1') {
                $diagnosis = trim($_POST['diagnosis']);
                $rest_days = (int)$_POST['rest_days'];
                $start_date = $_POST['start_date'];
                
                if (!empty($diagnosis) && $rest_days > 0) {
                    $stmt_mc = $pdo->prepare("INSERT INTO medical_certificates (student_id, doctor_id, diagnosis, rest_days, start_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt_mc->execute([$student['id'], $_SESSION['user_id'], $diagnosis, $rest_days, $start_date]);
                }
            }
            
            $pdo->commit();
            $message = "Success! Prescription (and MC if requested) has been issued.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Student with Reg No '$reg_no' not found!";
    }
}

// Handle Search
if (isset($_GET['search_reg_no'])) {
    $search_query = trim($_GET['search_reg_no']);
    if (!empty($search_query)) {
        // Find Student
        $stmt = $pdo->prepare("SELECT * FROM students WHERE reg_number = ? OR full_name LIKE ?");
        $stmt->execute([$search_query, "%$search_query%"]);
        $search_student = $stmt->fetch();

        if ($search_student) {
            // Get History
            $stmt = $pdo->prepare("
                SELECT p.*, u.username as doctor_name 
                FROM prescriptions p 
                JOIN users u ON p.doctor_id = u.id 
                WHERE p.student_id = ? 
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$search_student['id']]);
            $search_results = $stmt->fetchAll();
        } else {
            $message = "No student found matching '$search_query'";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center; padding: 10px 0;">
                <img src="images/vavuniya_logo.png" style="width: 180px; height: auto; margin-bottom: 15px;">
                <span style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">Doctor Portal</span>
            </div>
            <ul class="nav-links">
                <li><a href="doctor.php" class="active"><i class="fas fa-user-md"></i> Dashboard</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <h1 class="header-title">Doctor Dashboard</h1>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3 style="margin-bottom: 20px;">Issue Prescription</h3>
                    <form method="POST">
                        <input type="hidden" name="issue_prescription" value="1">
                        <label>Student Registration Number</label>
                        <input type="text" name="reg_no" class="input-field" placeholder="e.g. S12345" required value="<?php echo $search_student['reg_number'] ?? ''; ?>">
                        
                        <label>Reason / General Symptoms</label>
                        <textarea name="notes" class="input-field" rows="2" placeholder="e.g. Fever and body pain..." style="height: auto;"></textarea>

                        <!-- Medical Certificate Section -->
                        <div style="background: rgba(255, 204, 0, 0.1); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed var(--secondary-color);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--primary-color); font-weight: 700;">
                                <input type="checkbox" name="is_mc" value="1" id="mcToggle" onchange="toggleMC(this)"> Issue Medical Certificate (MC)
                            </label>
                            
                            <div id="mcFields" style="display: none; margin-top: 15px;">
                                <label>Diagnosis (Lede)</label>
                                <input type="text" name="diagnosis" class="input-field" placeholder="Enter diagnosis for MC...">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label>Rest Duration (Days)</label>
                                        <input type="number" name="rest_days" class="input-field" placeholder="Days" min="0">
                                    </div>
                                    <div>
                                        <label>Starting From</label>
                                        <input type="date" name="start_date" class="input-field" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                        function toggleMC(cb) {
                            document.getElementById('mcFields').style.display = cb.checked ? 'block' : 'none';
                        }
                        </script>

                        <label>Select Medicines</label>
                        <input type="text" id="medSearch" placeholder="Search medicine..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px;" onkeyup="filterMedicines()">
                        
                        <div id="medicineList" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 12px; margin-bottom: 20px;">
                            <?php
                            $all_meds = $pdo->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll();
                            foreach($all_meds as $med):
                            ?>
                            <div class="med-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-bottom: 1px solid #eee; cursor: pointer;">
                                <label for="med_<?php echo $med['id']; ?>" style="font-size: 0.9rem; flex: 1; cursor: pointer;"><?php echo htmlspecialchars($med['name']); ?> (Stock: <?php echo $med['stock_quantity']; ?>)</label>
                                <input type="checkbox" id="med_<?php echo $med['id']; ?>" name="meds[]" value="<?php echo $med['id']; ?>" style="width: 20px; height: 20px; cursor: pointer;">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <script>
                        function filterMedicines() {
                            var input, filter, container, items, label, i, txtValue;
                            input = document.getElementById("medSearch");
                            filter = input.value.toUpperCase();
                            container = document.getElementById("medicineList");
                            items = container.getElementsByClassName("med-item");
                            for (i = 0; i < items.length; i++) {
                                label = items[i].getElementsByTagName("label")[0];
                                txtValue = label.textContent || label.innerText;
                                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                    items[i].style.display = "";
                                } else {
                                    items[i].style.display = "none";
                                }
                            }
                        }
                        </script>
                        
                        <button type="submit" class="btn" style="width: 100%;">Send to Pharmacy</button>
                    </form>
                </div>

                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3 style="margin-bottom: 20px;">Quick Search</h3>
                    <p>Enter a registration number or name to view patient history.</p>
                    <form method="GET" action="doctor.php">
                        <input type="text" name="search_reg_no" class="input-field" placeholder="Search Reg No (e.g. S1001) or Name..." value="<?php echo htmlspecialchars($_GET['search_reg_no'] ?? ''); ?>">
                        <button type="submit" class="btn" style="background:var(--secondary-color); width: 100%;">Search History</button>
                    </form>

                    <?php if ($search_student): ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                            <h4><i class="fas fa-user"></i> Student Profile</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($search_student['full_name']); ?></p>
                            <p><strong>Reg No:</strong> <?php echo htmlspecialchars($search_student['reg_number']); ?></p>
                            <p><i class="fas fa-venus-mars"></i> <strong>Gender:</strong> <?php echo htmlspecialchars($search_student['gender'] ?? 'N/A'); ?> | <i class="fas fa-tint"></i> <strong>Blood:</strong> <?php echo htmlspecialchars($search_student['blood_type'] ?? 'N/A'); ?></p>
                            <p style="color: #e74c3c; font-weight: 600;"><i class="fas fa-allergies"></i> <strong>Allergies:</strong> <?php echo htmlspecialchars($search_student['allergies'] ?: 'None'); ?></p>
                            
                            <?php if (!empty($search_student['medical_report'])): ?>
                                <div style="margin-top: 15px;">
                                    <a href="uploads/reports/<?php echo htmlspecialchars($search_student['medical_report']); ?>" target="_blank" class="btn" style="background: #e74c3c; padding: 10px 15px; font-size: 0.9rem;">
                                        <i class="fas fa-file-pdf"></i> View Medical Report
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($search_student && $search_results !== null): ?>
                <div class="table-container" style="margin-top: 30px;">
                    <h3>Medical History: <?php echo htmlspecialchars($search_student['full_name']); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Notes/Prescription</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($search_results)): ?>
                                <tr><td colspan="4" style="text-align:center;">No previous records found.</td></tr>
                            <?php else: ?>
                                <?php foreach($search_results as $rec): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($rec['created_at'])); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($rec['notes'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $rec['status'] == 'dispensed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($rec['status']); ?>
                                        </span>
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
