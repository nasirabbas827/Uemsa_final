<?php
session_start();
require_once 'config.php'; // Contains your database connection

// Connect to database
$conn = new mysqli("localhost", "root", "", "uems_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch upcoming events (approved events with future start dates)
$events_sql = "SELECT e.*, c.name as category_name, u.first_name, u.last_name 
               FROM events e 
               LEFT JOIN event_categories c ON e.category_id = c.id 
               LEFT JOIN users u ON e.organizer_id = u.id 
               WHERE e.status = 'approved' 
               AND e.start_datetime >= CURDATE() 
               ORDER BY e.start_datetime ASC 
               LIMIT 3";

$events_result = $conn->query($events_sql);
$upcoming_events = [];
if ($events_result->num_rows > 0) {
    while($row = $events_result->fetch_assoc()) {
        $upcoming_events[] = $row;
    }
}

// Fetch event statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM events WHERE status = 'approved') as total_events,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM venues) as total_venues,
                (SELECT COUNT(DISTINCT user_id) FROM event_feedback WHERE rating >= 4) as satisfied_users";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Calculate satisfaction rate
$satisfaction_rate = 20;


// Fetch event categories
$categories_sql = "SELECT * FROM event_categories LIMIT 6";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get user count by role
$role_stats_sql = "SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role";
$role_stats_result = $conn->query($role_stats_sql);
$role_counts = [
    'Student' => 0,
    'Faculty' => 0,
    'Event Organizer' => 0
];
if ($role_stats_result->num_rows > 0) {
    while($row = $role_stats_result->fetch_assoc()) {
        $role_counts[$row['role']] = $row['count'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Event Management System (UEMS)</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        :root {
            --primary: #1a73e8;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #673ab7;
            --university-blue: #003366;
            --university-gold: #ffd700;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--university-blue) 0%, #0a1f44 100%);
            color: white;
            padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-section .lead {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            color: rgba(255,255,255,0.9);
        }

        .hero-badge {
            display: inline-block;
            background: var(--university-gold);
            color: var(--university-blue);
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .btn-hero {
            padding: 14px 40px;
            font-size: 1.1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px 10px 0;
        }

        .btn-hero-primary {
            background: var(--university-gold);
            color: var(--university-blue);
            border: none;
        }

        .btn-hero-primary:hover {
            background: #e6c200;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-hero-secondary:hover {
            background: white;
            color: var(--university-blue);
            transform: translateY(-3px);
        }

        /* Sections */
        .section {
            padding: 80px 0;
        }

        .section-title {
            font-weight: 700;
            position: relative;
            margin-bottom: 60px;
            color: var(--university-blue);
        }

        .section-title::after {
            content: '';
            width: 80px;
            height: 4px;
            background: var(--university-gold);
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        /* Feature Cards */
        .feature-card {
            border: none;
            border-radius: 12px;
            padding: 40px 25px;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            text-align: center;
            border-top: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
            border-top-color: var(--university-gold);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 25px;
            color: var(--university-blue);
            background: linear-gradient(45deg, var(--university-blue), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Role Cards */
        .role-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            height: 100%;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .role-header {
            background: var(--university-blue);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }

        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .role-body {
            padding: 30px 20px;
        }

        .role-feature {
            margin-bottom: 10px;
            color: #666;
        }

        .role-feature i {
            color: var(--success);
            margin-right: 8px;
        }

        /* Stats */
        .stat-box {
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--university-blue);
            line-height: 1;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Event Cards */
        .event-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .event-date {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--university-gold);
            color: var(--university-blue);
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
        }

        .event-date .day {
            font-size: 1.5rem;
            display: block;
            line-height: 1;
        }

        .event-date .month {
            font-size: 0.9rem;
            display: block;
        }

        .event-img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }

        .event-body {
            padding: 25px;
        }

        .event-category {
            display: inline-block;
            background: #e9ecef;
            color: #666;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--university-blue) 0%, #0a1f44 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-desc {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 40px;
            color: rgba(255,255,255,0.9);
        }

        /* Category Badges */
        .category-badge {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .category-badge:hover {
            background: var(--university-blue);
            color: white;
            transform: translateY(-2px);
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

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section {
                padding: 80px 0 60px;
            }
            
            .section {
                padding: 60px 0;
            }
            
            .btn-hero {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <div class="hero-badge animate-fade-in-up">
                    <i class="fas fa-graduation-cap mr-2"></i> University Platform
                </div>
                <h1 class="animate-fade-in-up text-white" style="animation-delay: 0.2s">University Event Management System</h1>
                <p class="lead animate-fade-in-up" style="animation-delay: 0.4s">
                    Streamline event organization, participation, and management across campus. 
                    From workshops to cultural festivals, UEMS connects the entire university community.
                </p>
                <div class="mt-4 animate-fade-in-up" style="animation-delay: 0.6s">
                    <?php if (!isset($_SESSION['id'])): ?>
                        <a href="register.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-user-plus mr-2"></i> Get Started
                        </a>
                        <a href="login.php" class="btn btn-hero btn-hero-secondary">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                        </a>
                        <a href="login.php" class="btn btn-hero btn-hero-secondary">
                            <i class="fas fa-calendar-alt mr-2"></i> Browse Events
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Key Features -->
<section class="section bg-light">
    <div class="container">
        <h2 class="text-center section-title">Everything You Need for Campus Events</h2>
        <p class="text-center mb-5 lead" style="max-width: 800px; margin: 0 auto 50px;">
            A comprehensive platform designed to simplify event management for students, faculty, and organizers
        </p>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4>Smart Event Creation</h4>
                    <p>Create detailed events with descriptions, dates, venues, capacity limits, and multimedia attachments. Support for recurring events.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h4>Venue Booking System</h4>
                    <p>Interactive venue booking with availability checking, conflict resolution, and visual calendar interface.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Seamless Registration</h4>
                    <p>User-friendly registration with capacity management, waiting lists, QR codes, and customizable forms.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h4>Resource Management</h4>
                    <p>Track equipment, catering, and technical support with availability monitoring and allocation system.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>Advanced Analytics</h4>
                    <p>Comprehensive reports on attendance, feedback, and performance metrics with export capabilities.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h4>Smart Notifications</h4>
                    <p>Automated email system, in-app messaging, and announcements for effective communication.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Popular Categories -->
<section class="section">
    <div class="container">
        <h2 class="text-center section-title">Event Categories</h2>
        <p class="text-center mb-5 lead" style="max-width: 800px; margin: 0 auto 50px;">
            Discover events that match your interests
        </p>
        
        <div class="text-center">
            <?php foreach($categories as $category): ?>
                <a href="login.php?category=<?= urlencode($category['name']) ?>" class="category-badge">
                    <i class="fas fa-tag mr-2"></i><?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
            <?php if(empty($categories)): ?>
                <p class="text-muted">No categories available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- User Roles -->
<section class="section bg-light">
    <div class="container">
        <h2 class="text-center section-title">Designed for Every University Role</h2>
        <p class="text-center mb-5 lead" style="max-width: 800px; margin: 0 auto 50px;">
            Tailored experiences for different members of the university community
        </p>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="role-card">
                    <div class="role-header">
                        <div class="role-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4 class="text-white">Students</h4>
                        <small><?= $role_counts['Student'] ?> Active Students</small>
                    </div>
                    <div class="role-body">
                        <p>Discover and participate in campus events that match your interests.</p>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Easy event registration
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Personalized recommendations
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Track attendance history
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Receive event reminders
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="role-card">
                    <div class="role-header">
                        <div class="role-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4 class="text-white">Faculty</h4>
                        <small><?= $role_counts['Faculty'] ?> Active Faculty</small>
                    </div>
                    <div class="role-body">
                        <p>Organize academic events, workshops, and monitor student participation.</p>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Create academic events
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Track student attendance
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Generate participation reports
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Manage event resources
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="role-card">
                    <div class="role-header">
                        <div class="role-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4 class="text-white">Event Organizers</h4>
                        <small><?= $role_counts['Event Organizer'] ?> Active Organizers</small>
                    </div>
                    <div class="role-body">
                        <p>Manage all aspects of event planning, from conception to execution.</p>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Complete event lifecycle management
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Venue and resource booking
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Registration form customization
                        </div>
                        <div class="role-feature">
                            <i class="fas fa-check-circle"></i> Real-time participant tracking
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events -->
<section class="section">
    <div class="container">
        <h2 class="text-center section-title">Upcoming Campus Events</h2>
        <p class="text-center mb-5 lead" style="max-width: 800px; margin: 0 auto 50px;">
            Discover exciting events happening across campus
        </p>
        
        <div class="row">
            <?php if(!empty($upcoming_events)): ?>
                <?php foreach($upcoming_events as $event): 
                    $start_date = new DateTime($event['start_datetime']);
                    $end_date = new DateTime($event['end_datetime']);
                    $month = $start_date->format('M');
                    $day = $start_date->format('d');
                    $time = $start_date->format('g:i A');
                    
                    // Get registration count for this event
                    $conn2 = new mysqli("localhost", "root", "", "uems_db");
                    $reg_sql = "SELECT COUNT(*) as reg_count FROM event_registrations WHERE event_id = ? AND status IN ('confirmed', 'pending')";
                    $stmt = $conn2->prepare($reg_sql);
                    $stmt->bind_param("i", $event['event_id']);
                    $stmt->execute();
                    $reg_result = $stmt->get_result();
                    $reg_data = $reg_result->fetch_assoc();
                    $registered_count = $reg_data['reg_count'];
                    $available_spots = $event['capacity'] - $registered_count;
                    $conn2->close();
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="event-card">
                            <div class="position-relative">
                                <?php if($event['banner_image']): ?>
                                    <img src="images/<?= htmlspecialchars($event['banner_image']) ?>" 
                                         alt="<?= htmlspecialchars($event['title']) ?>" 
                                         class="event-img">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80" 
                                         alt="<?= htmlspecialchars($event['title']) ?>" 
                                         class="event-img">
                                <?php endif; ?>
                                <div class="event-date">
                                    <span class="day"><?= $day ?></span>
                                    <span class="month"><?= strtoupper($month) ?></span>
                                </div>
                            </div>
                            <div class="event-body">
                                <?php if($event['category_name']): ?>
                                    <span class="event-category"><?= htmlspecialchars($event['category_name']) ?></span>
                                <?php endif; ?>
                                <h5><?= htmlspecialchars($event['title']) ?></h5>
                                <p class="text-muted mb-3">
                                    <?= strlen($event['description']) > 100 ? 
                                        substr(htmlspecialchars($event['description']), 0, 100) . '...' : 
                                        htmlspecialchars($event['description']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-clock text-primary"></i> <?= $time ?></span>
                                    <span><i class="fas fa-users text-info"></i> 
                                        <?= $available_spots > 0 ? $available_spots . '/' . $event['capacity'] : 'Full' ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <a href="login.php?id=<?= $event['event_id'] ?>" class="btn btn-primary btn-sm btn-block">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-calendar-times fa-2x mb-3"></i>
                        <h4>No Upcoming Events</h4>
                        <p>Check back soon for new events or <a href="login.php">create one</a> yourself!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="login.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-calendar-alt mr-2"></i> View All Events
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="section bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-box">
                    <div class="stat-number" id="totalEvents"><?= $stats['total_events'] ?></div>
                    <div class="stat-label">Events Hosted</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-box">
                    <div class="stat-number" id="activeUsers"><?= $stats['active_users'] ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-box">
                    <div class="stat-number" id="venuesBooked"><?= $stats['total_venues'] ?></div>
                    <div class="stat-label">Venues Available</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-box">
                    <div class="stat-number" id="satisfactionRate"><?= $satisfaction_rate ?>%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title text-white">Ready to Transform Campus Event Management?</h2>
        <p class="cta-desc">
            Join <?= $stats['active_users'] ?>+ students, faculty, and organizers who are already using UEMS to create, 
            manage, and participate in amazing campus events.
        </p>
        <div class="mt-4">
            <?php if (!isset($_SESSION['id'])): ?>
                <a href="register.php" class="btn btn-light btn-lg px-5 mr-3 mb-3">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-5 mb-3">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login Now
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-light btn-lg px-5 mr-3 mb-3">
                    <i class="fas fa-plus-circle mr-2"></i> Create New Event
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-5 mb-3">
                    <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                </a>
            <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Simple counter animation for stats
    $('.stat-number').each(function() {
        var $this = $(this);
        var text = $this.text();
        var countTo = text.replace('%', '').replace('+', '');
        
        if($.isNumeric(countTo)) {
            $({ countNum: 0 }).animate({
                countNum: parseFloat(countTo)
            }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    var displayNum = Math.floor(this.countNum);
                    if(text.includes('%')) {
                        $this.text(displayNum + '%');
                    } else if(text.includes('+')) {
                        $this.text(displayNum + '+');
                    } else {
                        $this.text(displayNum);
                    }
                }
            });
        }
    });
    
    // Add animation on scroll
    $(window).scroll(function() {
        $('.feature-card, .role-card, .event-card').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-fade-in-up');
            }
        });
    });
});
</script>
</body>
</html>