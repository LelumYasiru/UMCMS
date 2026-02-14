<?php
require '../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: login.php");
    exit;
}

if(isset($_POST['dispense_id'])) {
    $pid = $_POST['dispense_id'];
    $qtys = $_POST['qty'] ?? [];
    
    $pdo->beginTransaction();
    try {
        foreach ($qtys as $item_id => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            // 1. Get item info
            $stmt = $pdo->prepare("SELECT medicine_id FROM prescription_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            
            if ($item) {
                // 2. Check stock
                $stmt_stock = $pdo->prepare("SELECT stock_quantity FROM medicines WHERE id = ?");
                $stmt_stock->execute([$item['medicine_id']]);
                $stock = $stmt_stock->fetchColumn();

                if ($stock < $qty) {
                    $stmt_med_name = $pdo->prepare("SELECT name FROM medicines WHERE id = ?");
                    $stmt_med_name->execute([$item['medicine_id']]);
                    $med_name = $stmt_med_name->fetchColumn();
                    throw new Exception("Insufficient stock for $med_name. Only $stock left.");
                }

                // 3. Update item quantity
                $stmt_upd = $pdo->prepare("UPDATE prescription_items SET quantity = ? WHERE id = ?");
                $stmt_upd->execute([$qty, $item_id]);

                // 4. Deduct stock
                $stmt_deduct = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt_deduct->execute([$qty, $item['medicine_id']]);
            }
        }

        // 5. Mark prescription as dispensed
        $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'dispensed' WHERE id = ?");
        $stmt->execute([$pid]);

        $pdo->commit();
        $message = "Prescription dispensed and stock updated!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}





if(isset($_POST['send_notice'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_messages (message) VALUES (?)");
            $stmt->execute([$msg]);
            $notice_success = "Message sent to Admin.";
        } catch (Exception $e) {
            // Lazy create table if not exists (fallback)
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_read TINYINT(1) DEFAULT 0
            )");
            // Retry
            $stmt = $pdo->prepare("INSERT INTO admin_messages (message) VALUES (?)");
            $stmt->execute([$msg]);
            $notice_success = "Message sent to Admin.";
        }
    }
}

if(isset($_POST['deduct_stock'])) {
    $id = $_POST['medicine_id'];
    $amount = (int)$_POST['deduct_amount'];
    
    // Check current stock first to avoid negative
    $stmt = $pdo->prepare("SELECT stock_quantity FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    
    if ($current >= $amount) {
        $stmt = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$amount, $id]);
    } else {
        $error = "Cannot deduct $amount. Only $current in stock.";
    }
}

// Fetch Low Stock items (less than 100)
$low_stock_items = $pdo->query("SELECT name, stock_quantity FROM medicines WHERE stock_quantity < 100")->fetchAll();

// Fetch Pending Prescriptions
$pending = $pdo->query("
    SELECT p.*, s.reg_number, s.full_name 
    FROM prescriptions p 
    JOIN students s ON p.student_id = s.id 
    WHERE p.status = 'pending'
    ORDER BY p.created_at ASC
")->fetchAll();

// Fetch Medicines
$medicines = $pdo->query("SELECT * FROM medicines ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Dashboard</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo" style="display: flex; flex-direction: column; align-items: center; padding: 10px 0;">
                <img src="images/vavuniya_logo.png" style="width: 180px; height: auto; margin-bottom: 15px;">
                <span style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;">Pharmacy Portal</span>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="active"><i class="fas fa-boxes"></i> Dashboard</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <h1 class="header-title">Pharmacy Dashboard</h1>
            
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

            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if(isset($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="table-container" style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; color: var(--danger);">Pending Prescriptions</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reg No</th>
                            <th>Prescription</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="4">No pending prescriptions.</td></tr>
                        <?php else: ?>
                            <?php foreach($pending as $p): 
                                // Fetch items for this prescription
                                $stmt_items = $pdo->prepare("
                                    SELECT pi.*, m.name 
                                    FROM prescription_items pi 
                                    JOIN medicines m ON pi.medicine_id = m.id 
                                    WHERE pi.prescription_id = ?
                                ");
                                $stmt_items->execute([$p['id']]);
                                $items = $stmt_items->fetchAll();
                            ?>
                            <tr>
                                <td colspan="4" style="padding: 0;">
                                    <form method="POST">
                                        <table style="width: 100%; border-collapse: collapse; margin: 0;">
                                            <tr>
                                                <td style="width: 20%;"><?php echo htmlspecialchars($p['full_name']); ?></td>
                                                <td style="width: 15%;"><?php echo htmlspecialchars($p['reg_number']); ?></td>
                                                <td style="width: 45%;">
                                                    <strong>Notes:</strong> <?php echo htmlspecialchars($p['notes']); ?><br>
                                                    <div style="margin-top: 5px; font-size: 0.85rem; background: #f0f4f8; padding: 10px; border-radius: 8px;">
                                                        <strong>Enter Quantities Given:</strong><br>
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px;">
                                                        <?php foreach($items as $it): ?>
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <span style="flex: 1;"><?php echo htmlspecialchars($it['name']); ?>:</span>
                                                                <input type="number" name="qty[<?php echo $it['id']; ?>]" value="0" min="0" required style="width: 60px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                                                            </div>
                                                        <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="width: 20%; text-align: center;">
                                                    <input type="hidden" name="dispense_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn btn-success" style="padding: 10px 20px; font-size: 0.9rem;">
                                                        <i class="fas fa-check-circle"></i> Dispense
                                                    </button>
                                                </td>
                                            </tr>
                                        </table>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Send Notice Card -->
            <div class="card" style="margin-bottom: 20px;">
                <h3><i class="fas fa-paper-plane"></i> Send Notice to Admin</h3>
                <form method="POST" style="display: flex; gap: 10px; margin-top: 10px;">
                    <input type="hidden" name="send_notice" value="1">
                    <input type="text" name="message" class="input-field" placeholder="Type your message to the admin..." style="flex: 1;" required>
                    <button class="btn">Send</button>
                </form>
                <?php if(isset($notice_success)): ?>
                    <div class="alert alert-success" style="margin-top: 10px;"><?php echo $notice_success; ?></div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 30px;">


                <div class="table-container" style="flex: 1;">
                    <h3>Inventory</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width: 100px; text-align: center;">Stock</th>
                                <th style="width: 250px; text-align: center;">Update/Action</th>
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
                                    <div style="display: flex; gap: 5px; justify-content: center; align-items: center;">
                                        <!-- Deduct Stock Form -->
                                        <form method="POST" style="display: flex; gap: 4px; border: 1px solid #ddd; padding: 2px; border-radius: 8px;">
                                            <input type="hidden" name="deduct_stock" value="1">
                                            <input type="hidden" name="medicine_id" value="<?php echo $m['id']; ?>">
                                            <input type="number" name="deduct_amount" value="1" min="1" max="<?php echo $m['stock_quantity']; ?>" style="width: 50px; border: none; padding: 4px; font-size: 0.8rem; outline: none;">
                                            <button type="submit" class="btn" style="padding: 4px 8px; font-size: 0.7rem; background: var(--secondary-color);" title="Deduct Quantity">Deduct</button>
                                        </form>


                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
