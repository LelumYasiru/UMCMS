<?php header("Location: login.php"); exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UMCMS - Medical Center</title>
    <link rel="stylesheet" href="style.css?v=8.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: var(--bg-gradient) !important;
            color: #fff !important;
        }
        .auth-wrapper {
            color: #fff !important;
            padding: 40px 20px;
        }
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1100px;
            width: 100%;
            margin-top: 60px;
        }
        .portal-card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: var(--primary-color) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 50px 30px !important;
            border-radius: 24px !important;
            text-align: center;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .portal-card:hover {
            transform: translateY(-15px) scale(1.02);
            background: #fff !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            border-color: var(--secondary-color) !important;
        }
        .portal-card i {
            font-size: 3.5rem;
            color: var(--primary-color);
            background: rgba(90, 10, 56, 0.05);
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            transition: 0.3s;
        }
        .portal-card:hover i {
            background: var(--primary-color);
            color: var(--secondary-color);
            transform: rotate(5deg);
        }
        .portal-card h3 {
            font-size: 1.6rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 700;
        }
        .portal-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        .admin-link {
            margin-top: 60px;
            color: rgba(255, 255, 255, 0.6) !important;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            padding: 10px 20px;
            border-radius: 30px;
            background: rgba(0, 0, 0, 0.1);
        }
        .admin-link:hover {
            color: var(--secondary-color) !important;
            background: rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div style="margin-bottom: 30px;">
            <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 400px; height: auto;">
        </div>
        <h1 style="font-size: 3.5rem; margin-bottom: 5px; font-weight: 800; color: var(--secondary-color); text-shadow: 0 10px 20px rgba(0,0,0,0.3);">UMCMS</h1>
        <p style="font-size: 1.1rem; letter-spacing: 4px; text-transform: uppercase; opacity: 0.8;">Medical Center</p>

        <div class="portal-grid">
            <a href="login_doctor.php" class="portal-card">
                <i class="fas fa-user-md"></i>
                <div>
                    <h3>Medical Staff</h3>
                    <p>Doctor & Nursing Login</p>
                </div>
            </a>
            <a href="login_student.php" class="portal-card">
                <i class="fas fa-user-graduate"></i>
                <div>
                    <h3>Students</h3>
                    <p>Access your health records</p>
                </div>
            </a>
            <a href="login_pharmacist.php" class="portal-card">
                <i class="fas fa-pills"></i>
                <div>
                    <h3>Pharmacy</h3>
                    <p>Inventory & Prescriptions</p>
                </div>
            </a>
        </div>
        
        <a href="login_admin.php" class="admin-link">
            <i class="fas fa-shield-alt"></i> 
            Administrative Portal
        </a>
    </div>

</body>
</html>
