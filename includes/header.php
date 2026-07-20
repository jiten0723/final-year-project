<?php
// ============================================
// EDUCORE - Global Header / Navbar
// ============================================
require_once __DIR__ . '/../includes/auth.php';
$currentUser = getCurrentUser();
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EDUCORE - Learn in-demand skills online with expert instructors. Courses in Programming, Design, Business and more.">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | EDUCORE' : 'EDUCORE - Learn Without Limits'; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico">

    <!-- Google Fonts -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#f0fdf4',100:'#dcfce7',200:'#bbf7d0',300:'#86efac',400:'#4ade80',500:'#22c55e',600:'#16a34a',700:'#15803d',800:'#166534',900:'#14532d' },
                        secondary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<?php
// Add page-specific body classes (e.g., admin page)
$bodyClasses = 'bg-gray-950 text-white';
?>
<body class="<?php echo $bodyClasses; ?>">

<!-- ============ NAVBAR ============ -->
<nav class="navbar-educore" id="mainNavbar">
    <div class="nav-container">
        <!-- Logo -->
        <a href="<?php echo BASE_URL; ?>/index.php" class="navbar-brand-custom">
            <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
            <span class="logo-text">EDU<span class="logo-accent">CORE</span></span>
        </a>

        <!-- Search Bar -->
        <div class="nav-search-wrap d-none d-lg-flex">
            <form action="<?php echo BASE_URL; ?>/courses/index.php" method="GET" class="nav-search-form">
                <i class="fas fa-search nav-search-icon"></i>
                <input type="text" name="search" class="nav-search-input"
                    placeholder="Search for anything..."
                    value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>"
                    autocomplete="off">
                <button type="submit" class="nav-search-btn">Search</button>
            </form>
        </div>

        <!-- Nav Links -->
        <div class="nav-links d-none d-xl-flex">
            <a href="<?php echo BASE_URL; ?>/courses/index.php" class="nav-link-item <?php echo $currentPage=='index.php' && strpos($_SERVER['PHP_SELF'],'courses') !== false ? 'active':'' ?>">Explore</a>
            <a href="<?php echo BASE_URL; ?>/courses/index.php?type=free" class="nav-link-item">Free Courses</a>
            <a href="<?php echo BASE_URL; ?>/quiz/index.php" class="nav-link-item">Quizzes</a>
        </div>

        <!-- Right Section -->
        <div class="nav-right">
            <?php if (isLoggedIn()): ?>
                <!-- Notifications -->
                <div class="nav-icon-btn dropdown">
                    <button class="icon-btn" data-bs-toggle="dropdown" id="notifBtn">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown">
                        <div class="notif-header" style="color:#fff;">
                            <span style="color:#fff;">Notifications</span>
                            <a href="#" class="mark-all-read" onclick="markAllRead()">Mark all read</a>
                        </div>
                        <div id="notifList">
                            <div class="notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="user-menu-btn" data-bs-toggle="dropdown">
                        <div class="user-avatar-sm">
                            <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                        </div>
                        <span class="user-name-nav d-none d-md-inline"><?php echo e(explode(' ', $currentUser['name'])[0]); ?></span>
                        <i class="fas fa-chevron-down nav-chevron"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown" style="width:320px;min-width:320px;background:#111827;border:1px solid rgba(255,255,255,0.08);">
                        <li class="user-dropdown-header" style="background:#111827;padding:16px;display:flex;align-items:center;gap:12px;">
                            <div class="user-avatar-lg"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
                            <div style="min-width:0;flex:1;">
                                <div class="fw-semibold" style="color:#fff;font-size:14px;"><?php echo e($currentUser['name']); ?></div>
                                <div style="color:#9ca3af;font-size:12px;word-break:break-all;white-space:normal;"><?php echo e($currentUser['email']); ?></div>
                                <span class="role-badge role-<?php echo $currentUser['role']; ?>"><?php echo ucfirst($currentUser['role']); ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if ($currentUser['role'] === 'student'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/student.php"><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=courses"><i class="fas fa-book me-2"></i>My Courses</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=certificates"><i class="fas fa-certificate me-2"></i>Certificates</a></li>
                        <?php elseif ($currentUser['role'] === 'teacher'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/teacher.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/teacher.php?tab=courses"><i class="fas fa-plus-circle me-2"></i>Create Course</a></li>
                        <?php elseif ($currentUser['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/admin.php"><i class="fas fa-cogs me-2"></i>Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn-nav-login">Log In</a>
                <a href="<?php echo BASE_URL; ?>/register.php" class="btn-nav-signup">Get Started</a>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-btn d-xl-none" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <form action="<?php echo BASE_URL; ?>/courses/index.php" method="GET" class="mobile-search">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search courses...">
        </form>
        <a href="<?php echo BASE_URL; ?>/courses/index.php">Explore Courses</a>
        <a href="<?php echo BASE_URL; ?>/courses/index.php?type=free">Free Courses</a>
        <a href="<?php echo BASE_URL; ?>/quiz/index.php">Quizzes</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard/<?php echo $currentUser['role']; ?>.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="text-danger">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
