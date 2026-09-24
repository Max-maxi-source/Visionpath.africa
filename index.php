<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisionPath - Unlock Student Purpose & Potential Early</title>
    <meta name="description" content="VisionPath — Discover Purpose Early. Build the Future. Empowering Kenya's CBC generation to discover their calling early through continuous portfolios and pathway mentorship.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004494; /* Deep Blue */
            --secondary-color: #00b4d8; /* Light Blue */
            --accent-color: #f77f00; /* Vibrant Orange for CTA */
            --bg-color: #f8f9fa;
            --text-color: #2b2d42;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        /* Navbar */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo span {
            color: var(--secondary-color);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links .btn-login {
            border: 2px solid var(--primary-color);
            padding: 8px 24px;
            border-radius: 30px;
            color: var(--primary-color);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-links .btn-login:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .nav-links .btn-register {
            background: var(--accent-color);
            color: var(--white);
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(247, 127, 0, 0.3);
            transition: all 0.3s ease;
        }

        .nav-links .btn-register:hover {
            background: #e67300;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 127, 0, 0.4);
        }

        /* Logo image styling */
        .site-logo { height:56px; width:auto; display:block; border-radius:10px; box-shadow: 0 8px 30px rgba(2,6,23,0.08); }
        .brand-text { display:flex; flex-direction:column; line-height:1; }
        .brand-text .logo-name { font-weight:800; color:var(--primary-color); font-size:20px; }
        .brand-text .tagline { font-size:12px; color:#475569; opacity:0.9; }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 20px;
            background: radial-gradient(circle at top right, rgba(0,180,216,0.15) 0%, transparent 40%),
                        radial-gradient(circle at bottom left, rgba(0,68,148,0.1) 0%, transparent 40%),
                        var(--bg-color);
            margin-top: 60px; /* Offset for fixed header */
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 25px;
            max-width: 1000px;
            line-height: 1.1;
            letter-spacing: -1.5px;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.3rem;
            color: #555;
            max-width: 650px;
            margin-bottom: 45px;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .btn-main {
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 40px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 68, 148, 0.3);
        }

        .btn-main:hover {
            background: #003377;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 68, 148, 0.4);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary-color);
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 40px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,68,148,0.1);
        }

        .btn-secondary:hover {
            background: #f1f1f1;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        /* Features Section */
        .features {
            padding: 100px 5%;
            background: var(--white);
            text-align: center;
        }

        .features h2 {
            font-size: 2.8rem;
            color: var(--text-color);
            margin-bottom: 60px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: #fff;
            padding: 50px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
            border-bottom: 5px solid var(--secondary-color);
        }

        .feature-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 50px rgba(0,180,216,0.15);
        }
        
        .feature-icon {
            font-size: 40px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .feature-card p {
            color: #666;
            font-size: 1.05rem;
        }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.8rem; }
            .cta-buttons { flex-direction: column; width: 100%; max-width: 300px; }
            .nav-links { display: none; }
            .hero p { font-size: 1.1rem; padding: 0 10px; }
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <img src="assets/logo.svg" alt="VisionPath logo" class="site-logo">
        <div class="brand-text" style="margin-left:12px;">
            <div class="logo-name">VisionPath</div>
            <div class="tagline">Discover Purpose Early.</div>
        </div>
    </a>
    <nav class="nav-links">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="<?php echo ($_SESSION['role'] === 'teacher') ? 'add_project.php' : 'portfolio.php'; ?>" class="btn-main" style="padding: 10px 24px; box-shadow:none;">My Dashboard</a>
            <a href="logout.php" style="color:#d9534f; font-weight:600;">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register as Student</a>
        <?php endif; ?>
    </nav>
</header>

<section class="hero">
    <h1>Bridging CBC Projects with <span>Real-World Mentorship</span></h1>
    <p>Empowering Kenyan students by showcasing their academic achievements directly to parents and corporate mentors in one beautiful, dynamic portfolio.</p>
    <div class="cta-buttons">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="<?php echo ($_SESSION['role'] === 'teacher') ? 'add_project.php' : 'portfolio.php'; ?>" class="btn-main">Open My Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn-main">Join as a Student</a>
            <a href="login.php" class="btn-secondary">Teacher / Parent Login</a>
        <?php endif; ?>
    </div>
</section>

<section class="features" id="features">
    <h2>Why Choose VisionPath?</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📁</div>
            <h3>Student Portfolios</h3>
            <p>Every CBC student gets a dynamic digital portfolio showcasing their projects, talents, and academic growth over time.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👩‍🏫</div>
            <h3>Teacher Uploads</h3>
            <p>Teachers can easily document student achievements across different CBC strands, ensuring no milestone is ever lost.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔔</div>
            <h3>Parent Notifications</h3>
            <p>Parents are automatically notified via email when their child achieves something new, keeping them fully involved.</p>
        </div>
    </div>
</section>

</body>
</html>
