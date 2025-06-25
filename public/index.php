<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define absolute paths for the application
define('BASE_PATH', '/newmann_tracking');
define('ROOT_PATH', dirname(__DIR__));

// Autoload dependencies using the absolute root path
require_once ROOT_PATH . '/vendor/autoload.php';

// A simple router
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri);

// Default route mapping
if ($route === '/public/' || $route === '/public/index.php' || $route === '/public' || $route === '/' || $route === '') {
    $route = '/login';
}

$controller_path = ROOT_PATH . '/src/Controllers/';

// Define routes
$routes = [
    // Auth
    '/login' => $controller_path . 'AuthController.php',
    '/handle_login' => $controller_path . 'AuthController.php',
    '/logout' => $controller_path . 'AuthController.php',
    
    // Driver PWA
    '/driver_dashboard' => $controller_path . 'DriverController.php',
    '/driver/update_status' => $controller_path . 'DriverController.php',
    '/driver/report_anomaly' => $controller_path . 'DriverController.php',
    '/driver/scan' => $controller_path . 'DriverController.php',

    // Management Dashboard
    '/dashboard' => $controller_path . 'DashboardController.php',

    // Management CRUD
    '/deliveries' => $controller_path . 'DeliveryController.php',
    '/delivery/add' => $controller_path . 'DeliveryController.php',
    '/delivery/edit' => $controller_path . 'DeliveryController.php',
    '/delivery/delete' => $controller_path . 'DeliveryController.php',
    '/delivery/details' => $controller_path . 'DeliveryController.php',
    '/delivery/qr' => $controller_path . 'DeliveryController.php',
    '/trucks' => $controller_path . 'TruckController.php',
    '/truck/add' => $controller_path . 'TruckController.php',
    '/truck/edit' => $controller_path . 'TruckController.php',
    '/truck/delete' => $controller_path . 'TruckController.php',
    '/drivers' => $controller_path . 'DriverController.php',
    '/driver/add' => $controller_path . 'DriverController.php',
    '/driver/edit' => $controller_path . 'DriverController.php',
    '/driver/delete' => $controller_path . 'DriverController.php',
    '/warehouses' => $controller_path . 'WarehouseController.php',
    '/warehouse/add' => $controller_path . 'WarehouseController.php',
    '/warehouse/edit' => $controller_path . 'WarehouseController.php',
    '/warehouse/delete' => $controller_path . 'WarehouseController.php',
    '/models' => $controller_path . 'ModelController.php',
    '/model/add' => $controller_path . 'ModelController.php',
    '/model/edit' => $controller_path . 'ModelController.php',
    '/model/delete' => $controller_path . 'ModelController.php',
    '/users' => $controller_path . 'ManagementController.php',
    '/user/add' => $controller_path . 'ManagementController.php',
    '/user/edit' => $controller_path . 'ManagementController.php',
    '/user/delete' => $controller_path . 'ManagementController.php',
    '/reports' => $controller_path . 'ReportController.php',

    // API endpoints
    '/api/driver/deliveries' => $controller_path . 'ApiController.php',
    '/api/delivery/update_status' => $controller_path . 'ApiController.php',
    '/api/anomaly/report' => $controller_path . 'ApiController.php',
    '/api/gps/log' => $controller_path . 'ApiController.php',
    '/api/truck_locations' => $controller_path . 'ApiController.php',
    '/api/notifications' => $controller_path . 'ApiController.php',
    '/api/notifications/mark_read' => $controller_path . 'ApiController.php',

    // Profile pages
    '/profile' => $controller_path . 'ProfileController.php',
];


if (array_key_exists($route, $routes)) {
    require $routes[$route];
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    echo "The page you requested (<code>" . htmlspecialchars($route) . "</code>) could not be found.";
}