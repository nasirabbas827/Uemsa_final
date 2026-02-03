<?php
session_start();
require_once 'config.php';

// Fetch some basic stats for the About page
$conn = new mysqli("localhost", "root", "", "uems_db");
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM events WHERE status = 'approved') as total_events,
                (SELECT COUNT(*) FROM users) as total_users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - UEMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        :root {
            --university-blue: #003366;
            --university-gold: #ffd700;
        }

        .about-hero {
            background: linear-gradient(rgba(0, 51, 102, 0.9), rgba(0, 51, 102, 0.9)), 
                        url('https://images.unsplash.com/photo-1523050853064-dbad323b7ff3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0 60px;
            text-align: center;
        }

        .section-padding {
            padding: 80px 0;
        }

        .mission-box {
            border-left: 5px solid var(--university-gold);
            padding: 20px 30px;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }

        .team-member {
            text-align: center;
            margin-bottom: 30px;
        }

        .team-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 5px solid #f8f9fa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .values-icon {
            font-size: 2.5rem;
            color: var(--university-blue);
            margin-bottom: 20px;
        }

/* Footer */
        footer {
            background: #212529;
            color: #adb5bd;
            padding: 60px 0 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }

        .footer-logo span {
            color: var(--university-gold);
        }

        .footer-links h5 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--university-gold);
            padding-left: 5px;
        }

        .copyright {
            border-top: 1px solid #343a40;
            padding-top: 30px;
            margin-top: 50px;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<section class="about-hero">
    <div class="container">
        <h1 class="display-4 text-white font-weight-bold">Empowering Campus Life</h1>
        <p class="lead">Bridging the gap between students, faculty, and unforgettable experiences.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4">
                <img src="https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="About UEMS" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="mb-4" style="color: var(--university-blue);">Our Story</h2>
                <p>The <strong>University Event Management System (UEMS)</strong> was born out of a simple need: to make campus life more accessible. We realized that many students were missing out on incredible workshops and seminars simply because they didn't know they were happening.</p>
                <div class="mission-box mt-4">
                    <h4>Our Mission</h4>
                    <p class="mb-0">To provide a unified, digital ecosystem that simplifies the planning, promotion, and participation of university events, fostering a more vibrant and connected campus community.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-light">
    <div class="container text-center">
        <h2 class="mb-5">Our Core Values</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="values-icon"><i class="fas fa-handshake"></i></div>
                <h4>Collaboration</h4>
                <p>We believe events are better when departments and students work together seamlessly.</p>
            </div>
            <div class="col-md-4">
                <div class="values-icon"><i class="fas fa-lightbulb"></i></div>
                <h4>Innovation</h4>
                <p>Using technology to solve the logistical headaches of venue booking and registration.</p>
            </div>
            <div class="col-md-4">
                <div class="values-icon"><i class="fas fa-check-double"></i></div>
                <h4>Transparency</h4>
                <p>Providing clear data and feedback loops for organizers and attendees alike.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <h2 class="text-center mb-5">The People Behind UEMS</h2>
        <div class="row justify-content-center">
            <div class="col-md-3 team-member">
                <img src="https://i.pravatar.cc/150?u=1" alt="Team" class="team-img">
                <h5>Alex Johnson</h5>
                <p class="text-muted">Lead Developer</p>
            </div>
            <div class="col-md-3 team-member">
                <img src="https://i.pravatar.cc/150?u=2" alt="Team" class="team-img">
                <h5>Sarah Chen</h5>
                <p class="text-muted">UI/UX Designer</p>
            </div>
            <div class="col-md-3 team-member">
                <img src="https://i.pravatar.cc/150?u=3" alt="Team" class="team-img">
                <h5>Dr. Michael Ross</h5>
                <p class="text-muted">Project Advisor</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-logo">U<span>EMS</span></div>
                <p>University Event Management System - Streamlining campus events for a connected university community.</p>
                <div class="mt-4">
                    <a href="#" class="text-light mr-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-light mr-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-light mr-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-links">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="login.php">Home</a></li>
                        <li><a href="login.php">Events</a></li>
                        <li><a href="login.php">About</a></li>
                        <li><a href="login.php">Contact</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-links">
                    <h5>Resources</h5>
                    <ul>
                        <li><a href="login.php">Help Center</a></li>
                        <li><a href="login.php">FAQ</a></li>
                        <li><a href="login.php">User Guides</a></li>
                        <li><a href="login.php">API Docs</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-4 mb-4">
                <div class="footer-links">
                    <h5>Contact Info</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> University Campus</li>
                        <li><i class="fas fa-phone mr-2"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope mr-2"></i> support@uems.edu</li>
                        <li><i class="fas fa-clock mr-2"></i> Mon-Fri: 9:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?= date('Y') ?> University Event Management System. All rights reserved.</p>
            <p class="mb-0">
                <a href="login.php" class="text-muted mr-3">Privacy Policy</a> | 
                <a href="login.php" class="text-muted mr-3">Terms of Service</a> | 
                <a href="login.php" class="text-muted">Accessibility</a>
            </p>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>