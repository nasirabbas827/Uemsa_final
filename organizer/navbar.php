<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UEMS - Event Organizer</title>
    
    <!-- Bootstrap 5 (optional but makes life easier – you can remove if you don't want it) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        /* Sidebar */
        #sidebar {
            width: 240px;
            background: #1a1a2e;          /* Dark navy - professional look */
            color: white;
            transition: width 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        #sidebar .brand {
            padding: 1.5rem 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #00d4ff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: block;
        }

        #sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        #sidebar .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            padding-left: 1.8rem;
        }

        #sidebar .nav-link.active {
            background: rgba(0, 212, 255, 0.15);
            color: #00d4ff;
            border-left: 4px solid #00d4ff;
        }

        #sidebar .nav-item.logout {
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Main content */
        #content {
            margin-left: 240px;
            flex: 1;
            padding: 2rem;
            background: #ffffff;
            min-height: 100vh;
        }

        /* Mobile – collapse sidebar */
        @media (max-width: 992px) {
            #sidebar {
                width: 0;
                padding: 0;
            }
            #content {
                margin-left: 0;
            }
            /* You can add a toggle button in header for mobile */
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav id="sidebar" class="d-flex flex-column">
        <a href="dashboard.php" class="brand">UEMS Organizer</a>

        <?php if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])): ?>
            <ul class="nav flex-column mt-2">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-house-door me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags me-2"></i> Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="view_events.php"><i class="bi bi-calendar-event me-2"></i> Events</a></li>
                <li class="nav-item"><a class="nav-link" href="event_media.php"><i class="bi bi-images me-2"></i> Event Media</a></li>
                <li class="nav-item"><a class="nav-link" href="venue_bookings.php"><i class="bi bi-geo-alt me-2"></i> Venue Booking</a></li>
                <li class="nav-item"><a class="nav-link" href="resources_bookings.php"><i class="bi bi-tools me-2"></i> Resources Booking</a></li>
                <li class="nav-item"><a class="nav-link" href="view_registration_forms.php"><i class="bi bi-ui-checks-grid me-2"></i> Registration Forms</a></li>
                <li class="nav-item"><a class="nav-link" href="event_registrations.php"><i class="bi bi-people me-2"></i> Registrations</a></li>
                <li class="nav-item"><a class="nav-link" href="organizer_attendance.php"><i class="bi bi-qr-code-scan me-2"></i> Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="organizer_feedbacks.php"><i class="bi bi-chat-square-text me-2"></i> Feedbacks</a></li>
                <li class="nav-item"><a class="nav-link" href="announcements.php"><i class="bi bi-megaphone me-2"></i> Announcements</a></li>
                <li class="nav-item"><a class="nav-link" href="organizer_messages.php"><i class="bi bi-chat-square-text me-2"></i> Messages</a></li>
                
                <li class="nav-item logout">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>

        <?php else: ?>
            <ul class="nav flex-column mt-4">
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-person-plus me-2"></i> Register</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i> Login</a></li>
                <li class="nav-item"><a class="nav-link" href="./admin/admin_login.php"><i class="bi bi-shield-lock me-2"></i> Admin Login</a></li>
            </ul>
        <?php endif; ?>
    </nav>

    <!-- Main Content Area -->
        
    </div>

    <!-- Optional: Bootstrap Icons (very clean line icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</body>
</html>