<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/newmann_tracking');
}
if (!isset($_SESSION['user_id']) && strpos($_SERVER['REQUEST_URI'], 'login') === false) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}
$current_uri = $_SERVER['REQUEST_URI'];
$user_role = $_SESSION['role'] ?? '';

function isActive($uri, $current_uri) {
    if ($uri === '/dashboard') {
        return $current_uri === BASE_PATH . '/dashboard' ? 'active' : '';
    }
    return strpos($current_uri, BASE_PATH . $uri) === 0 ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Newmann Tracking' ?></title>
    
    <meta name="theme-color" content="#2c3e50"/>
    <link rel="manifest" href="<?= BASE_PATH ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/assets/icons/icon-192x192.png">

    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>
    <div class="main-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Newmann Tracking</h3>
            </div>
            <nav>
                <ul>
                    <li><a href="<?= BASE_PATH ?>/dashboard" class="<?= isActive('/dashboard', $current_uri) ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_PATH ?>/deliveries" class="<?= isActive('/deliveries', $current_uri) ?>"><i class="fas fa-truck"></i> Deliveries</a></li>
                    <li><a href="<?= BASE_PATH ?>/drivers" class="<?= isActive('/drivers', $current_uri) ?>"><i class="fas fa-id-card"></i> Drivers</a></li>
                    <li><a href="<?= BASE_PATH ?>/trucks" class="<?= isActive('/trucks', $current_uri) ?>"><i class="fas fa-truck-moving"></i> Trucks</a></li>
                    <li><a href="<?= BASE_PATH ?>/warehouses" class="<?= isActive('/warehouses', $current_uri) ?>"><i class="fas fa-warehouse"></i> Warehouses</a></li>
                    <li><a href="<?= BASE_PATH ?>/models" class="<?= isActive('/models', $current_uri) ?>"><i class="fas fa-motorcycle"></i> Motorcycle Models</a></li>
                    <li><a href="<?= BASE_PATH ?>/reports" class="<?= isActive('/reports', $current_uri) ?>"><i class="fas fa-chart-line"></i> Reports</a></li>
                    
                    <?php if ($user_role === 'System Admin'): ?>
                        <li><a href="<?= BASE_PATH ?>/users" class="<?= isActive('/users', $current_uri) ?>"><i class="fas fa-users-cog"></i> User Management</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <div class="content-overlay" id="content-overlay"></div>

        <div class="main-content">
            <header class="header">
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-title">
                    <h1><?= $page_title ?? 'Dashboard' ?></h1>
                </div>
                <?php if (isset($_SESSION['user_name'])): ?>
                <div class="user-info">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (<?= htmlspecialchars($user_role) ?>)!</span>

                    <div class="notifications-container">
                        <a href="#" class="header-icon-link" id="notificationBell" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span id="notificationCount"></span>
                        </a>
                        <div id="notificationsDropdown" class="notifications-dropdown">
                            <div class="notifications-dropdown-header">
                                <span>Notifications</span>
                                <a href="#" id="markAllReadLink">Mark all as read</a>
                            </div>
                            <div id="notificationList">
                                <div class="no-notifications">Loading...</div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="<?= BASE_PATH ?>/profile" class="header-icon-link" title="My Profile">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="<?= BASE_PATH ?>/logout" class="header-icon-link" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <?php endif; ?>
            </header>
            <main class="content">