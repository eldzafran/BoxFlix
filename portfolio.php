<?php
// No login required for portfolio
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - BoxFlix Creator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/bflixpng2.png">
    <style>
        body {
            background: linear-gradient(120deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: #e2e8f0;
            margin: 0;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }
        .logo img {
            height: 70px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }
        .nav-left {
            display: flex;
            align-items: center;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            margin-left: auto;
            margin-right: 32px;
        }
        .nav-links a {
            color: #e2e8f0;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .nav-links a:hover {
            background: #60a5fa;
            color: #fff;
        }
        .main-content {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            margin-top: 100px;
        }
        .portfolio-glass {
            display: flex;
            flex-direction: row;
            gap: 2.5rem;
            background: rgba(30, 41, 59, 0.7);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 2.5rem 3rem;
            max-width: 900px;
            width: 100%;
            align-items: center;
            animation: fadeIn 0.7s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-pic {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #60a5fa;
            box-shadow: 0 4px 24px rgba(96,165,250,0.18);
            background: #fff;
        }
        .portfolio-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .creator-name {
            font-size: 2.3rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.2rem;
        }
        .creator-role {
            color: #60a5fa;
            font-size: 1.2rem;
            margin-bottom: 0.7rem;
            font-weight: 500;
        }
        .creator-desc {
            color: #cbd5e1;
            font-size: 1.08rem;
            margin-bottom: 0.5rem;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #60a5fa;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
        }
        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-bottom: 0.5rem;
        }
        .skill {
            background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 100%);
            color: #fff;
            padding: 0.4rem 1.1rem;
            border-radius: 50px;
            font-size: 0.98rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(96,165,250,0.08);
            letter-spacing: 0.5px;
        }
        .projects {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
        }
        .project-card {
            background: rgba(255,255,255,0.07);
            border-radius: 18px;
            padding: 1.2rem 1.1rem;
            min-width: 180px;
            flex: 1 1 180px;
            color: #e2e8f0;
            box-shadow: 0 2px 8px rgba(96,165,250,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .project-card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 8px 24px rgba(96,165,250,0.18);
        }
        .project-title {
            font-weight: 600;
            color: #60a5fa;
            margin-bottom: 0.3rem;
        }
        .project-desc {
            font-size: 0.97rem;
            color: #cbd5e1;
        }
        .social-links {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .social-links a {
            color: #60a5fa;
            font-size: 2.1rem;
            transition: color 0.2s, transform 0.2s;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            padding: 0.5rem;
        }
        .social-links a:hover {
            color: #fff;
            background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 100%);
            transform: scale(1.15) rotate(-6deg);
        }
        .contact-info {
            color: #cbd5e1;
            font-size: 1.05rem;
            margin-bottom: 0.2rem;
        }
        @media (max-width: 900px) {
            .portfolio-glass {
                flex-direction: column;
                align-items: center;
                padding: 2rem 1rem;
            }
            .profile-pic {
                margin-bottom: 1.2rem;
            }
        }
        @media (max-width: 600px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links {
                margin-left: 0;
                width: 100%;
                justify-content: flex-end;
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <img src="assets/bflixpng2.png" alt="BoxFlix Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>
    <div class="main-content">
        <div class="portfolio-glass">
            <img src="assets/gwejh.png" alt="Creator Photo" class="profile-pic">
            <div class="portfolio-info">
                <div>
                    <div class="creator-name">El Farrel dzafran</div>
                    <div class="creator-role">Web Developer & UI Designer</div>
                    <div class="creator-desc">
                        Halo! Saya El Farrel Dzafran dan saya adalah pengembang dan desainer antarmuka untuk BoxFlix.<br>
                        Saya merupakan murid SMKN 01 Depok kelas XI PPLG 1.<br>
                        Saya selalu berusaha membuat aplikasi yang modern, responsif, dan mudah digunakan.
                    </div>
                </div>
                <div>
                    <div class="section-title">Skills</div>
                    <div class="skills">
                        <div class="skill">PHP</div>
                        <div class="skill">MySQL</div>
                        <div class="skill">HTML5</div>
                        <div class="skill">CSS3</div>
                        <div class="skill">JavaScript</div>
                        <div class="skill">UI/UX</div>
                        <div class="skill">Figma</div>
                        <div class="skill">Bootstrap</div>
                    </div>
                </div>
                <div>
                    <div class="section-title">Projects</div>
                    <div class="projects">
                        <div class="project-card">
                            <div class="project-title">BoxFlix</div>
                            <div class="project-desc">A modern movie streaming platform with premium and standard content, built with PHP & MySQL.</div>
                        </div>
                        <div class="project-card">
                            <div class="project-title">UI Design Portfolio</div>
                            <div class="project-desc">Collection of UI/UX works and prototypes for various web and mobile apps.</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="section-title">Contact</div>
                    <div class="social-links">
                        <a href="https://github.com/eldzafran" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>
                        <a href="https://instagram.com/eldzafran_" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="mailto:dzafranelfarrel@gmail.com" title="Email"><i class="fas fa-envelope"></i></a>
                    </div>
                    <div class="contact-info">
                        <strong>Email:</strong> dzafranelfarrel@gmail.com
                    </div>
                    <div class="contact-info">
                        <strong>Telepon:</strong> 08118710113
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 