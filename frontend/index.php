<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome - UMCMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=8.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Landing Page Specific Styles */
        body {
            background: var(--bg-gradient);
            color: #333;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        /* Diagonal pattern like the university image */
        .hero::after {
            content: '';
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(255, 204, 0, 0.1) 0%, transparent 70%);
            z-index: 1;
        }

        .hero-content {
            z-index: 2;
            animation: fadeIn 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(90, 10, 56, 0.4);
            padding: 60px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero h1 {
            font-size: 4.5rem;
            margin-bottom: 5px;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--secondary-color);
            text-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 5px;
            color: white;
            opacity: 0.9;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 40px;
            opacity: 0.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            padding: 15px 45px;
            font-size: 1.1rem;
            color: var(--primary-color);
            background: var(--secondary-color);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(255, 204, 0, 0.3);
            transition: all 0.3s ease;
        }

        .cta-btn:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255, 204, 0, 0.4);
            background: var(--secondary-light);
        }

        /* Info Section */
        .info-section {
            padding: 100px 20px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .section-title {
            font-size: 2.8rem;
            color: white;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 60px;
        }

        .emergency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .e-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-top: 6px solid var(--primary-color);
            text-align: left;
        }

        .e-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }

        .e-card i {
            font-size: 2.5rem;
            margin-bottom: 25px;
            color: var(--primary-color);
            background: rgba(90, 10, 56, 0.05);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }

        .e-card h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: #2d3436;
        }

        .e-card p {
            color: #636e72;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .e-card .number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <div style="margin-bottom: 25px;">
                <img src="images/vavuniya_logo.png" class="app-logo" style="max-width: 400px; height: auto;">
            </div>
            <h1 style="color: var(--secondary-color);">UMCMS</h1>
            <div class="hero-subtitle">Medical Center</div>
            <p>Providing excellence in university healthcare through a modern, digital management system for students, staff, and medical professionals.</p>
            
            <a href="login.php" class="cta-btn">Access Medical Portal <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div style="position: absolute; bottom: 40px; animation: bounce 2s infinite; opacity: 0.6; color: white;">
            <p style="margin-bottom: 8px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;">Emergency Contacts</p>
            <i class="fas fa-chevron-down" style="font-size: 1.2rem;"></i>
        </div>
    </header>

    <style>
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }
    </style>

    <!-- Emergency Contacts -->
    <section class="info-section">
        <h2 class="section-title">Emergency Contacts</h2>
        <p class="section-subtitle">24/7 Support for any medical emergencies on campus.</p>

        <div class="emergency-grid">
            <div class="e-card">
                <i class="fas fa-ambulance"></i>
                <h3>Ambulance</h3>
                <p>Immediate medical transport and life support.</p>
                <span class="number">1990</span>
            </div>

            <div class="e-card">
                <i class="fas fa-user-shield"></i>
                <h3>Campus Security</h3>
                <p>For immediate safety and security assistance.</p>
                <span class="number">011-234-5678</span>
            </div>

            <div class="e-card">
                <i class="fas fa-clinic-medical"></i>
                <h3>Medical Center</h3>
                <p>General inquiries and emergency coordination.</p>
                <span class="number">Ext. 1234</span>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="info-section" style="background: rgba(0, 0, 0, 0.2); max-width: 100%;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle">Comprehensive healthcare tailored for the University community.</p>
            <div class="emergency-grid">
                <div class="e-card">
                    <i class="fas fa-user-md"></i>
                    <h3>General Checkups</h3>
                    <p>Daily health monitoring and consultations for students and staff.</p>
                </div>
                <div class="e-card" style="border-top-color: var(--secondary-color);">
                    <i class="fas fa-pills" style="color: var(--secondary-color);"></i>
                    <h3>Pharmacy</h3>
                    <p>On-campus pharmacy providing necessary medicines and advice.</p>
                </div>
                <div class="e-card">
                    <i class="fas fa-file-medical"></i>
                    <h3>Medical Reports</h3>
                    <p>Official medical certificates and health evaluations for academic purposes.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 UMCMS - University of Vavuniya. All Rights Reserved.</p>
    </footer>

</body>
</html>
