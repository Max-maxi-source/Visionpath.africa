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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;0,900;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0B2A4A;
            --gold: #C9A24B;
            --teal: #1CA9C9;
            --orange: #F0862A;
            --cream: #FAF9F6;
            --charcoal: #1E1E22;
            --text: #1A1A1A;
            --warm-white: #F5F1E8;
            --border-soft: #E4E1D8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--cream);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, .serif { font-family: 'Playfair Display', serif; }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 14px;
            display: block;
        }

        /* Navbar */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 5%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(11,42,74,0.08);
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-mark {
            width: 44px; height: 44px; flex-shrink: 0;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--navy), #123a63);
            color: var(--gold);
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 22px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(11,42,74,0.28);
        }
        .brand-text { display:flex; flex-direction:column; line-height:1; margin-left:12px; }
        .brand-text .logo-name { font-family:'Playfair Display', serif; font-weight:800; color:var(--navy); font-size:21px; }
        .brand-text .logo-name span { color: var(--gold); }
        .brand-text .tagline { font-size:12px; color:#5b6472; opacity:0.9; font-family:'Inter',sans-serif; }

        .nav-links { display: flex; align-items: center; gap: 28px; }

        .nav-links a.nav-item {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s;
        }
        .nav-links a.nav-item:hover { color: var(--navy); }

        .nav-cta { display: flex; align-items: center; gap: 14px; }

        .btn-login {
            border: 2px solid var(--navy);
            padding: 8px 22px;
            border-radius: 30px;
            color: var(--navy);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn-login:hover { background: var(--navy); color: var(--warm-white); }

        .btn-register {
            background: var(--gold);
            color: var(--navy);
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 30px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(201,162,75,0.35);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn-register:hover { background: #b8913e; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(201,162,75,0.45); }

        .hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background:none; border:none; }
        .hamburger span { width: 26px; height: 3px; background: var(--navy); border-radius: 2px; }

        /* Hero */
        .hero {
            background: var(--charcoal);
            color: var(--warm-white);
            padding: 170px 5% 100px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 60px;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -20%; right: -10%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(201,162,75,0.18) 0%, transparent 65%);
            pointer-events: none;
        }

        .hero-content { position: relative; z-index: 1; }

        .hero h1 {
            font-size: 3.6rem;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.15;
            color: var(--warm-white);
        }
        .hero h1 .accent { color: var(--gold); font-style: italic; }

        .hero p.lead {
            font-size: 1.15rem;
            color: #D8D4C8;
            max-width: 560px;
            margin-bottom: 40px;
        }

        .cta-buttons { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 36px; }

        .btn-main {
            background: var(--gold);
            color: var(--navy);
            text-decoration: none;
            padding: 16px 38px;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(201,162,75,0.35);
            display: inline-block;
        }
        .btn-main:hover { background: #b8913e; transform: translateY(-3px); box-shadow: 0 10px 28px rgba(201,162,75,0.45); }

        .btn-secondary {
            background: transparent;
            color: var(--warm-white);
            text-decoration: none;
            padding: 15px 36px;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1.5px solid rgba(245,241,232,0.4);
            display: inline-block;
        }
        .btn-secondary:hover { background: rgba(245,241,232,0.1); border-color: var(--warm-white); }

        .trust-bar { font-size: 0.85rem; color: #B7B2A5; border-top: 1px solid rgba(245,241,232,0.15); padding-top: 20px; }

        .hero-media { position: relative; z-index: 1; }
        .hero-media img {
            width: 100%;
            height: 460px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.45);
        }
        .hero-media .overlay-tint {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(11,42,74,0.05), rgba(11,42,74,0.35));
            border-radius: 20px;
        }

        /* Section shell */
        .section { padding: 90px 5%; }
        .section.pt-tight { padding-top: 40px; }
        .section.pb-tight { padding-bottom: 40px; }
        .section.cream { background: var(--cream); }
        .section.white { background: #ffffff; }
        .section.charcoal { background: var(--charcoal); color: var(--warm-white); }
        .section-header { text-align: center; max-width: 680px; margin: 0 auto 60px; }
        .section-header h2 { font-size: 2.5rem; font-weight: 700; color: var(--navy); }
        .section.charcoal .section-header h2 { color: var(--warm-white); }
        .section-header p { color: #5b6472; margin-top: 16px; font-size: 1.05rem; }
        .section.charcoal .section-header p { color: #cfd2d6; }

        /* Split image/text bands */
        .split-band {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        .split-band.reverse .split-media { order: 2; }
        .split-media img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 24px 50px rgba(11,42,74,0.14);
        }
        .split-text .eyebrow { display: block; }
        .split-text h3 {
            font-size: 2rem;
            color: var(--navy);
            font-weight: 700;
            margin-bottom: 18px;
            line-height: 1.25;
        }
        .split-text p { color: #5b6472; font-size: 1.05rem; margin-bottom: 24px; }
        .split-text .btn-link {
            color: var(--navy);
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid var(--gold);
            padding-bottom: 3px;
        }
        .split-text .btn-link:hover { color: var(--gold); }

        @media (max-width: 900px) {
            .split-band { grid-template-columns: 1fr; }
            .split-band.reverse .split-media { order: -1; }
            .split-media img { height: 280px; }
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: #fff;
            padding: 44px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(11,42,74,0.06);
            transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
            border: 1px solid var(--border-soft);
            border-bottom: 4px solid transparent;
            text-align: left;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 22px 50px rgba(11,42,74,0.12);
            border-bottom-color: var(--gold);
        }

        .feature-icon-badge {
            width: 58px; height: 58px;
            border-radius: 50%;
            background: var(--navy);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 22px;
        }
        .feature-icon-badge svg { width: 26px; height: 26px; stroke: var(--gold); fill: none; }

        .feature-card h3 { font-size: 1.35rem; margin-bottom: 12px; color: var(--navy); font-weight: 700; }
        .feature-card p { color: #5b6472; font-size: 1rem; }

        /* How it works */
        .steps-strip {
            max-width: 1150px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 28px;
            position: relative;
        }
        .steps-strip::before {
            content: '';
            position: absolute;
            top: 28px; left: 12%; right: 12%;
            height: 2px;
            background-image: linear-gradient(90deg, var(--gold) 40%, transparent 40%);
            background-size: 14px 2px;
        }
        .step { text-align: center; position: relative; }
        .step-num {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: var(--navy);
            color: var(--gold);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            position: relative; z-index: 1;
            box-shadow: 0 8px 20px rgba(11,42,74,0.25);
        }
        .step h4 { font-size: 1.05rem; color: var(--navy); margin-bottom: 8px; font-weight: 700; }
        .step p { font-size: 0.92rem; color: #5b6472; }

        /* Testimonials */
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .testimonial-card {
            background: #fff;
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            padding: 32px 28px;
        }
        .testimonial-card p.quote {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.1rem;
            color: var(--navy);
            margin-bottom: 22px;
            line-height: 1.5;
        }
        .testimonial-person { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: var(--navy); color: var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-family: 'Playfair Display', serif;
        }
        .testimonial-person .name { font-weight: 700; font-size: 0.92rem; color: var(--text); }
        .testimonial-person .role { font-size: 0.8rem; color: #5b6472; }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
        }
        .stat-num { font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 800; color: var(--gold); }
        .stat-label { font-size: 0.95rem; color: #cfd2d6; margin-top: 6px; }

        /* Final CTA */
        .final-cta {
            text-align: center;
            max-width: 1100px; margin: 70px auto 0;
            padding-top: 60px;
            border-top: 1px solid rgba(245,241,232,0.12);
        }
        .final-cta h2 { font-size: 2.4rem; margin-bottom: 32px; max-width: 700px; margin-left:auto; margin-right:auto; }
        .final-cta .cta-buttons { justify-content: center; }

        /* Footer */
        footer {
            background: var(--charcoal);
            color: var(--warm-white);
            padding: 70px 5% 30px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto 50px;
        }
        .footer-brand .logo-name { font-family:'Playfair Display', serif; font-size: 1.4rem; font-weight: 800; color: var(--warm-white); }
        .footer-brand p { color: #B7B2A5; font-size: 0.9rem; margin-top: 12px; max-width: 260px; }
        .footer-col h5 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--gold); margin-bottom: 18px; }
        .footer-col a { display: block; color: #D8D4C8; text-decoration: none; font-size: 0.92rem; margin-bottom: 12px; transition: color 0.25s; }
        .footer-col a:hover { color: var(--gold); }
        .footer-bottom {
            max-width: 1200px; margin: 0 auto;
            border-top: 1px solid rgba(245,241,232,0.12);
            padding-top: 24px;
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px;
            font-size: 0.82rem; color: #9a9689;
        }

        @media (max-width: 1080px) {
            .nav-links { position: fixed; top: 88px; left: 0; right: 0; background: #fff;
                flex-direction: column; align-items: stretch; gap: 0; padding: 0;
                max-height: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(11,42,74,0.12);
                transition: max-height 0.3s ease; }
            .nav-links.open { max-height: 420px; padding: 10px 0 20px; }
            .nav-links > .nav-item { display: block; padding: 14px 24px; }
            .nav-cta { flex-direction: column; align-items: stretch; gap: 10px; padding: 10px 24px 0; }
            .nav-cta a { text-align: center; }
            .hamburger { display: flex; }
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; padding-top: 140px; }
            .hero-media { order: -1; }
            .hero-media img { height: 320px; }
            .steps-strip { grid-template-columns: repeat(2, 1fr); gap: 40px 20px; }
            .steps-strip::before { display: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.4rem; }
            .cta-buttons { flex-direction: column; width: 100%; max-width: 320px; }
            .hero p.lead { font-size: 1rem; }
            .section-header h2 { font-size: 1.9rem; }
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <span class="logo-mark">V</span>
        <div class="brand-text">
            <div class="logo-name">Vision<span>Path</span></div>
            <div class="tagline">Discover Purpose Early.</div>
        </div>
    </a>
    <nav class="nav-links">
        <a href="index.php" class="nav-item">Home</a>
        <a href="#how-it-works" class="nav-item">How It Works</a>
        <a href="#features" class="nav-item">Why VisionPath</a>
        <a href="#contact" class="nav-item">Contact</a>
        <div class="nav-cta">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo ($_SESSION['role'] === 'teacher') ? 'add_project.php' : 'portfolio.php'; ?>" class="btn-main" style="padding: 10px 24px; box-shadow:none;">My Dashboard</a>
                <a href="logout.php" style="color:#d9534f; font-weight:600; text-decoration:none;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register as Student</a>
            <?php endif; ?>
        </div>
    </nav>
    <button class="hamburger" aria-label="Menu" onclick="document.querySelector('.nav-links').classList.toggle('open')"><span></span><span></span><span></span></button>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">CBC Portfolios &middot; Real Mentorship</span>
        <h1>Bridging CBC Projects with <span class="accent">Real-World Mentorship</span></h1>
        <p class="lead">Every Kenyan learner deserves to be seen. VisionPath turns CBC coursework into a living portfolio — visible to parents, teachers, and the mentors who can open doors.</p>
        <div class="cta-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo ($_SESSION['role'] === 'teacher') ? 'add_project.php' : 'portfolio.php'; ?>" class="btn-main">Open My Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn-main">Join as a Student</a>
                <a href="login.php" class="btn-secondary">Teacher / Parent Login</a>
            <?php endif; ?>
        </div>
        <div class="trust-bar">Trusted by schools across Nairobi, Kisumu &amp; Mombasa</div>
    </div>
    <div class="hero-media">
        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&q=80" alt="Students collaborating on a project">
        <div class="overlay-tint"></div>
    </div>
</section>

<section class="section white pb-tight" id="features">
    <div class="section-header">
        <span class="eyebrow" style="display:block; text-align:center;">Why Choose Us</span>
        <h2>Why Choose VisionPath?</h2>
        <p>A single platform that connects every achievement to the people who care most.</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon-badge">
                <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H3z"/><path d="M3 7V5a2 2 0 0 1 2-2h4l2 2"/></svg>
            </div>
            <h3>Student Portfolios</h3>
            <p>Every CBC student gets a dynamic digital portfolio showcasing their projects, talents, and academic growth over time.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-badge">
                <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v11H6.5A2.5 2.5 0 0 0 4 18.5z"/><path d="M8 8h8M8 12h5"/></svg>
            </div>
            <h3>Teacher Uploads</h3>
            <p>Teachers can easily document student achievements across different CBC strands, ensuring no milestone is ever lost.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-badge">
                <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <h3>Parent Notifications</h3>
            <p>Parents are automatically notified via email when their child achieves something new, keeping them fully involved.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-badge">
                <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>
            </div>
            <h3>Career Pathway Matching</h3>
            <p>Student strengths are connected to real CBC career tracks, turning classroom talent into a clear future direction.</p>
        </div>
    </div>
</section>

<section class="section white pt-tight">
    <div class="split-band">
        <div class="split-media">
            <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=900&q=80" alt="Teacher documenting a student's achievement in class">
        </div>
        <div class="split-text">
            <span class="eyebrow">In the Classroom</span>
            <h3>Every achievement, documented the moment it happens</h3>
            <p>Teachers no longer chase paperwork at the end of term. A quick upload after class — a science project, a debate win, a piece of art — and it's already part of the student's permanent record, tagged to the right CBC strand.</p>
            <a href="register.php" class="btn-link">Register your school &rarr;</a>
        </div>
    </div>
</section>

<section class="section cream" id="how-it-works">
    <div class="section-header">
        <span class="eyebrow" style="display:block; text-align:center;">The Process</span>
        <h2>How It Works</h2>
        <p>From first login to a proud parent's notification — here's the journey.</p>
    </div>
    <div class="steps-strip">
        <div class="step">
            <div class="step-num">1</div>
            <h4>Student Registers</h4>
            <p>Creates their profile and joins their school on VisionPath.</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <h4>Teacher Uploads Achievements</h4>
            <p>Work is tagged by CBC strand and added to the student's record.</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <h4>Portfolio Builds Automatically</h4>
            <p>Growth is visualized on a living timeline over the school year.</p>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <h4>Parents &amp; Mentors Notified</h4>
            <p>Engagement happens in real time, the moment something new happens.</p>
        </div>
    </div>
</section>

<section class="section white pb-tight">
    <div class="split-band reverse">
        <div class="split-media">
            <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=900&q=80" alt="Corporate mentor available to guide students">
        </div>
        <div class="split-text">
            <span class="eyebrow">Real Mentorship</span>
            <h3>Corporate mentors who invest in Kenya's next generation</h3>
            <p>Once a portfolio shows where a student's strengths lie, VisionPath connects them with vetted professionals in matching CBC pathways — for advice, encouragement, and a real window into the careers ahead of them.</p>
            <a href="register.php" class="btn-link">Become a mentor &rarr;</a>
        </div>
    </div>
</section>

<section class="section white pt-tight">
    <div class="section-header">
        <span class="eyebrow" style="display:block; text-align:center;">Voices</span>
        <h2>What Our Community Says</h2>
    </div>
    <div class="testimonial-grid">
        <div class="testimonial-card">
            <p class="quote">"For the first time, I can see exactly what my daughter is learning — not just her report card, but the actual work."</p>
            <div class="testimonial-person">
                <div class="avatar">M</div>
                <div><div class="name">Mary W.</div><div class="role">Parent, Nairobi</div></div>
            </div>
        </div>
        <div class="testimonial-card">
            <p class="quote">"VisionPath turned my weekly grading into a running record of every learner's progress. It's effortless now."</p>
            <div class="testimonial-person">
                <div class="avatar">J</div>
                <div><div class="name">James O.</div><div class="role">CBC Teacher, Kisumu</div></div>
            </div>
        </div>
        <div class="testimonial-card">
            <p class="quote">"My portfolio helped me explain to a mentor exactly where my strengths are. It felt like being seen for the first time."</p>
            <div class="testimonial-person">
                <div class="avatar">A</div>
                <div><div class="name">Amina K.</div><div class="role">Student, Mombasa</div></div>
            </div>
        </div>
    </div>
</section>

<section class="section charcoal" id="contact">
    <div class="stats-grid">
        <div><div class="stat-num">500+</div><div class="stat-label">Students Empowered</div></div>
        <div><div class="stat-num">50+</div><div class="stat-label">Partner Schools</div></div>
        <div><div class="stat-num">120+</div><div class="stat-label">Corporate Mentors</div></div>
        <div><div class="stat-num">1,000+</div><div class="stat-label">Milestones Celebrated</div></div>
    </div>
    <div class="final-cta">
        <h2 class="serif">Ready to showcase your child's journey?</h2>
        <div class="cta-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo ($_SESSION['role'] === 'teacher') ? 'add_project.php' : 'portfolio.php'; ?>" class="btn-main">Open My Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn-main">Join as a Student</a>
                <a href="mailto:hello@visionpath.co.ke" class="btn-secondary">Talk to Our Team</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="logo-name">VisionPath</div>
            <p>Discover Purpose Early. Empowering Kenya's CBC generation through living portfolios and pathway mentorship.</p>
        </div>
        <div class="footer-col">
            <h5>Quick Links</h5>
            <a href="index.php">Home</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#features">Why VisionPath</a>
        </div>
        <div class="footer-col">
            <h5>For Schools &amp; Mentors</h5>
            <a href="register.php">Register as Student</a>
            <a href="login.php">Teacher / Parent Login</a>
        </div>
        <div class="footer-col">
            <h5>Contact</h5>
            <a href="mailto:hello@visionpath.co.ke">hello@visionpath.co.ke</a>
            <a href="#contact">Nairobi, Kenya</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?php echo date('Y'); ?> VisionPath. All rights reserved.</span>
        <span>Privacy Policy &middot; Terms of Service</span>
    </div>
</footer>

</body>
</html>
