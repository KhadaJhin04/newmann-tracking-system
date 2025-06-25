<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

switch ($route) {
    case '/driver_dashboard':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'driver') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        try {
            $driver_id = $_SESSION['user_id'];
            $stmt = $db->prepare("
                SELECT delivery_id, truck_id, destination_details, status, 
                       DATE_FORMAT(departure_datetime, '%b %d, %Y %h:%i %p') as departure_datetime_formatted, 
                       DATE_FORMAT(estimated_arrival, '%b %d, %Y %h:%i %p') as estimated_arrival_formatted
                FROM delivery
                WHERE driver_id = :driver_id
                AND status IN ('Pending', 'Scheduled', 'Preparing', 'In Transit', 'Delayed')
                ORDER BY departure_datetime ASC
            ");
            $stmt->execute([':driver_id' => $driver_id]);
            $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $deliveries = [];
            $page_error = "Could not fetch deliveries: " . $e->getMessage();
        }
        $page_title = 'My Dashboard';
        require_once ROOT_PATH . '/templates/driver/dashboard.php';
        break;

    case '/driver/update_status':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'driver') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        $delivery_id = $_GET['id'] ?? 0;
        if (!$delivery_id) {
            $_SESSION['error_message'] = "No delivery specified.";
            header('Location: ' . BASE_PATH . '/driver_dashboard');
            exit;
        }
        try {
            $stmt = $db->prepare("SELECT * FROM delivery WHERE delivery_id = :id AND driver_id = :driver_id");
            $stmt->execute([':id' => $delivery_id, ':driver_id' => $_SESSION['user_id']]);
            $delivery = $stmt->fetch();
            if (!$delivery) {
                $_SESSION['error_message'] = "Delivery not found or not assigned to you.";
                header('Location: ' . BASE_PATH . '/driver_dashboard');
                exit;
            }
            $delay_reasons = $db->query("SELECT * FROM delay_reasons WHERE is_active = 1")->fetchAll();
            $stmt_anomalies = $db->prepare("SELECT anomaly_id, type, location FROM road_anomaly WHERE delivery_id = ?");
            $stmt_anomalies->execute([$delivery_id]);
            $delivery_anomalies = $stmt_anomalies->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $page_error = "Database Error: " . $e->getMessage();
            $delivery = null;
        }
        $page_title = 'Update Delivery Status';
        require_once ROOT_PATH . '/templates/driver/update_status.php';
        break;

    case '/driver/report_anomaly':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'driver') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        $delivery_id = $_GET['id'] ?? 0;
        if (!$delivery_id) {
            $_SESSION['error_message'] = "A delivery must be specified to report an anomaly.";
            header('Location: ' . BASE_PATH . '/driver_dashboard');
            exit;
        }
        $page_title = 'Report Road Anomaly';
        require_once ROOT_PATH . '/templates/driver/report_anomaly.php';
        break;
        
    case '/driver/scan':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'driver') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        $page_title = 'Scan QR Code';
        require_once ROOT_PATH . '/templates/driver/scan.php';
        break;

    case '/drivers':
    case '/driver/add':
    case '/driver/edit':
    case '/driver/delete':
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'management') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        handle_management_driver_actions($route, $db);
        break;
}

function handle_management_driver_actions($route, $db) {
    switch ($route) {
        case '/drivers':
            try {
                $stmt = $db->query("SELECT driver_id, name, contact, license_number FROM driver ORDER BY name ASC");
                $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Database error fetching drivers: " . $e->getMessage();
                $drivers = [];
            }
            $page_title = 'Drivers';
            require_once ROOT_PATH . '/templates/driver/list.php';
            break;
        
        case '/driver/add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = [];
                if (empty($_POST['name'])) { $errors[] = "Driver name is required."; }
                if (empty($_POST['license_number'])) { $errors[] = "License number is required."; }
                
                if (!empty($_POST['username'])) {
                    $stmt = $db->prepare("SELECT driver_id FROM driver WHERE username = ?");
                    $stmt->execute([$_POST['username']]);
                    if ($stmt->fetch()) { $errors[] = "Username '{$_POST['username']}' is already taken."; }
                }
                
                $stmt = $db->prepare("SELECT driver_id FROM driver WHERE license_number = ?");
                $stmt->execute([$_POST['license_number']]);
                if ($stmt->fetch()) { $errors[] = "A driver with this license number already exists."; }

                if (!empty($errors)) {
                    $_SESSION['error_message'] = implode('<br>', $errors);
                } else {
                    try {
                        $hashed_password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
                        $sql = "INSERT INTO driver (name, contact, license_number, username, password_hash) VALUES (:name, :contact, :license_number, :username, :password_hash)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            ':name' => $_POST['name'], ':contact' => $_POST['contact'] ?: null, ':license_number' => $_POST['license_number'],
                            ':username' => $_POST['username'] ?: null, ':password_hash' => $hashed_password
                        ]);
                        $_SESSION['success_message'] = "Driver successfully added!";
                        header('Location: ' . BASE_PATH . '/drivers');
                        exit;
                    } catch (PDOException $e) { $_SESSION['error_message'] = "Error adding driver: " . $e->getMessage(); }
                }
            }
            $page_title = 'Add New Driver';
            require_once ROOT_PATH . '/templates/driver/add.php';
            break;

        case '/driver/edit':
            $driver_id = $_GET['id'] ?? 0;
            if (!$driver_id) { header('Location: ' . BASE_PATH . '/drivers'); exit; }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = [];
                if (empty($_POST['name'])) { $errors[] = "Driver name is required."; }
                if (empty($_POST['license_number'])) { $errors[] = "License number is required."; }
                
                if (!empty($_POST['username'])) {
                    $stmt = $db->prepare("SELECT driver_id FROM driver WHERE username = ? AND driver_id != ?");
                    $stmt->execute([$_POST['username'], $driver_id]);
                    if ($stmt->fetch()) { $errors[] = "Username '{$_POST['username']}' is already taken by another driver."; }
                }
                
                $stmt = $db->prepare("SELECT driver_id FROM driver WHERE license_number = ? AND driver_id != ?");
                $stmt->execute([$_POST['license_number'], $driver_id]);
                if ($stmt->fetch()) { $errors[] = "Another driver with this license number already exists."; }

                if (!empty($errors)) {
                    $_SESSION['error_message'] = implode('<br>', $errors);
                } else {
                    try {
                        $params = [
                            ':name' => $_POST['name'], ':contact' => $_POST['contact'] ?: null,
                            ':license_number' => $_POST['license_number'], ':username' => $_POST['username'] ?: null, ':driver_id' => $driver_id
                        ];
                        if (!empty($_POST['password'])) {
                            $sql = "UPDATE driver SET name = :name, contact = :contact, license_number = :license_number, username = :username, password_hash = :password_hash WHERE driver_id = :driver_id";
                            $params[':password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        } else {
                            $sql = "UPDATE driver SET name = :name, contact = :contact, license_number = :license_number, username = :username WHERE driver_id = :driver_id";
                        }
                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        $_SESSION['success_message'] = "Driver #{$driver_id} updated successfully!";
                        header('Location: ' . BASE_PATH . '/drivers');
                        exit;
                    } catch (PDOException $e) { $_SESSION['error_message'] = "Error updating driver: " . $e->getMessage(); }
                }
            }

            try {
                $stmt = $db->prepare("SELECT * FROM driver WHERE driver_id = :id");
                $stmt->execute([':id' => $driver_id]);
                $driver = $stmt->fetch();
                if (!$driver) {
                    $_SESSION['error_message'] = "Driver not found.";
                    header('Location: ' . BASE_PATH . '/drivers');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Database error fetching driver: " . $e->getMessage();
                header('Location: ' . BASE_PATH . '/drivers');
                exit;
            }
            $page_title = 'Edit Driver';
            require_once ROOT_PATH . '/templates/driver/edit.php';
            break;

        case '/driver/delete':
            $driver_id = $_GET['id'] ?? 0;
            if ($driver_id) {
                try {
                    $stmt = $db->prepare("DELETE FROM driver WHERE driver_id = :id");
                    $stmt->execute([':id' => $driver_id]);
                    $_SESSION['success_message'] = "Driver #{$driver_id} has been deleted.";
                } catch (PDOException $e) {
                    if ($e->getCode() == '23000') {
                        $_SESSION['error_message'] = "Cannot delete this driver as they are assigned to existing deliveries.";
                    } else {
                        $_SESSION['error_message'] = "Error deleting driver: " . $e->getMessage();
                    }
                }
            }
            header('Location: ' . BASE_PATH . '/drivers');
            exit;
    }
}