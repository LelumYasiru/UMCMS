<?php
require '../backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mc_id = $_GET['id'] ?? 0;

// Fetch MC details with Student and Doctor info
$stmt = $pdo->prepare("
    SELECT mc.*, s.full_name, s.reg_number, s.faculty, u.username as doctor_name 
    FROM medical_certificates mc 
    JOIN students s ON mc.student_id = s.id 
    JOIN users u ON mc.doctor_id = u.id 
    WHERE mc.id = ?
");
$stmt->execute([$mc_id]);
$mc = $stmt->fetch();

if (!$mc) {
    die("Medical Certificate not found.");
}

// Security check: Only the student or a doctor/admin can view it
if ($_SESSION['role'] === 'student') {
    // Check if this student owns the MC
    $stmt_check = $pdo->prepare("SELECT id FROM students WHERE user_id = ? AND id = ?");
    $stmt_check->execute([$_SESSION['user_id'], $mc['student_id']]);
    if (!$stmt_check->fetch()) {
        die("Unauthorized access.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Certificate - <?php echo $mc['reg_number']; ?></title>
    <link rel="stylesheet" href="style.css?v=8.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f4f7f6 !important;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .mc-container {
            background: white;
            width: 800px;
            padding: 40px 60px;
            border-radius: 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
            border: 10px solid var(--primary-color);
        }
        .mc-header {
            text-align: center;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .mc-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 15px;
        }
        .mc-body {
            line-height: 1.8;
            font-size: 1.1rem;
            color: #333;
        }
        .info-row {
            margin-bottom: 15px;
            display: flex;
            border-bottom: 1px dashed #eee;
            padding-bottom: 5px;
        }
        .info-label {
            font-weight: 700;
            width: 200px;
            color: var(--primary-color);
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sig-box {
            text-align: center;
            width: 250px;
        }
        .sig-line {
            border-top: 2px solid #333;
            margin-bottom: 10px;
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: white !important; padding: 0; margin: 0; display: block; }
            .mc-container { 
                box-shadow: none; 
                border: 5px solid var(--primary-color); 
                width: 210mm; /* A4 Width */
                height: 297mm; /* A4 Height */
                margin: 0 auto;
                box-sizing: border-box;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="mc-container">
        <!-- Certificate Number for Authenticity -->
        <div style="position: absolute; top: 30px; right: 40px; text-align: right;">
            <p style="font-weight: 800; color: var(--primary-color); font-size: 0.85rem; margin-bottom: 2px;">CERTIFICATE NO:</p>
            <p style="font-family: 'Courier New', monospace; font-weight: 700; font-size: 1.1rem; color: #333;">UMCMS-<?php echo date('Y', strtotime($mc['created_at'] ?? 'now')); ?>-<?php echo str_pad($mc['id'], 5, '0', STR_PAD_LEFT); ?></p>
        </div>

        <div class="mc-header">
            <img src="images/vavuniya_logo.png" style="max-width: 350px; height: auto; margin-bottom: 5px;">
            <h1 class="mc-title" style="font-size: 2rem; margin-top: 10px;">Medical Certificate</h1>
            <p style="text-transform: uppercase; font-weight: 600; letter-spacing: 1px; color: #666; font-size: 0.9rem;">University Medical Center</p>
        </div>

        <div class="mc-body">
            <p style="margin-bottom: 20px;">This is to certify that the following student was examined at the University Medical Center:</p>
            
            <div class="info-row">
                <span class="info-label">Student Name:</span>
                <span><?php echo htmlspecialchars($mc['full_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Registration No:</span>
                <span><?php echo htmlspecialchars($mc['reg_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Faculty:</span>
                <span><?php echo htmlspecialchars($mc['faculty']); ?></span>
            </div>
            
            <p style="margin-top: 30px; margin-bottom: 15px;">
                Testing and examination revealed that the student is suffering from 
                <strong style="color: var(--primary-color); border-bottom: 2px solid var(--secondary-color);"><?php echo htmlspecialchars($mc['diagnosis']); ?></strong>.
            </p>

            <p>
                In consequence thereof, I consider that a period of 
                <strong style="font-size: 1.3rem;"><?php echo $mc['rest_days']; ?> Day(s)</strong> 
                of absolute bed rest is essential for recovery, starting from 
                <strong><?php echo date('F j, Y', strtotime($mc['start_date'])); ?></strong>.
            </p>
        </div>

        <div class="signature-section">
            <div class="sig-box" style="text-align: left;">
                <p style="font-size: 0.9rem; color: #888;"><?php echo date('Y-m-d H:i'); ?></p>
                <p>Date & Time Issued</p>
                <div style="margin-top: 20px; color: #27ae60; font-weight: 800; font-size: 0.8rem; border: 2px solid #27ae60; display: inline-block; padding: 5px 10px; text-transform: uppercase; transform: rotate(-5deg);">
                    <i class="fas fa-check-circle"></i> Digitally Verified
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <p><strong>Dr. <?php echo htmlspecialchars($mc['doctor_name']); ?></strong></p>
                <p>Medical Officer In-Charge</p>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; text-align: center;">
            <p style="font-size: 0.75rem; color: #aaa; font-style: italic;">
                This certificate is an official university document. Any unauthorized alteration or forgery is a punishable offense. 
                Verification can be done through the UMCMS portal using the Certificate Number at the top.
            </p>
        </div>

        <div style="margin-top: 50px; text-align: center;" class="no-print">
            <button onclick="window.print()" class="btn" style="background: var(--secondary-color); color: var(--primary-color);">
                <i class="fas fa-print"></i> Print / Download PDF
            </button>
            <a href="student.php" class="btn" style="background: #ccc; color: #333; margin-left: 10px;">Back to Portal</a>
        </div>
    </div>

</body>
</html>
