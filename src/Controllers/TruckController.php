<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

switch ($route) {
    case '/trucks':
        try {
            $stmt = $db->query("SELECT truck_id, plate_number, capacity, status FROM truck ORDER BY truck_id DESC");
            $trucks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching trucks: " . $e->getMessage();
            $trucks = [];
        }
        $page_title = 'Trucks';
        require_once ROOT_PATH . '/templates/truck/list.php';
        break;

    case '/truck/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $plate_number = trim($_POST['plate_number']);
            
            // --- VALIDATION LOGIC ---
            $stmt = $db->prepare("SELECT truck_id FROM truck WHERE plate_number = ?");
            $stmt->execute([$plate_number]);
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = "Error: A truck with plate number '{$plate_number}' already exists.";
            } else {
                try {
                    $sql = "INSERT INTO truck (plate_number, capacity, status) VALUES (:plate_number, :capacity, :status)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':plate_number' => $plate_number,
                        ':capacity' => $_POST['capacity'] ?: null,
                        ':status' => $_POST['status']
                    ]);
                    $_SESSION['success_message'] = "Truck successfully added!";
                    header('Location: ' . BASE_PATH . '/trucks');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error adding truck: " . $e->getMessage();
                }
            }
        }
        $page_title = 'Add New Truck';
        require_once ROOT_PATH . '/templates/truck/add.php';
        break;

    case '/truck/edit':
        $truck_id = $_GET['id'] ?? 0;
        if (!$truck_id) {
            header('Location: ' . BASE_PATH . '/trucks');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $plate_number = trim($_POST['plate_number']);

            // --- VALIDATION LOGIC ---
            // Check if another truck (not this one) already has the new plate number
            $stmt = $db->prepare("SELECT truck_id FROM truck WHERE plate_number = ? AND truck_id != ?");
            $stmt->execute([$plate_number, $truck_id]);
            if ($stmt->fetch()) {
                 $_SESSION['error_message'] = "Error: Another truck with plate number '{$plate_number}' already exists.";
            } else {
                try {
                    $sql = "UPDATE truck SET plate_number = :plate_number, capacity = :capacity, status = :status WHERE truck_id = :truck_id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':plate_number' => $plate_number,
                        ':capacity' => $_POST['capacity'] ?: null,
                        ':status' => $_POST['status'],
                        ':truck_id' => $truck_id
                    ]);
                    $_SESSION['success_message'] = "Truck #{$truck_id} updated successfully!";
                    header('Location: ' . BASE_PATH . '/trucks');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error updating truck: " . $e->getMessage();
                }
            }
        }

        try {
            $stmt = $db->prepare("SELECT * FROM truck WHERE truck_id = :id");
            $stmt->execute([':id' => $truck_id]);
            $truck = $stmt->fetch();
            if (!$truck) {
                $_SESSION['error_message'] = "Truck not found.";
                header('Location: ' . BASE_PATH . '/trucks');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching truck: " . $e->getMessage();
            header('Location: ' . BASE_PATH . '/trucks');
            exit;
        }

        $page_title = 'Edit Truck';
        require_once ROOT_PATH . '/templates/truck/edit.php';
        break;

    case '/truck/delete':
        $truck_id = $_GET['id'] ?? 0;
        if ($truck_id) {
            try {
                $stmt = $db->prepare("DELETE FROM truck WHERE truck_id = :id");
                $stmt->execute([':id' => $truck_id]);
                $_SESSION['success_message'] = "Truck #{$truck_id} has been deleted.";
            } catch (PDOException $e) {
                 if ($e->getCode() == '23000') {
                    $_SESSION['error_message'] = "Cannot delete this truck as it is assigned to existing deliveries.";
                } else {
                    $_SESSION['error_message'] = "Error deleting truck: " . $e->getMessage();
                }
            }
        }
        header('Location: ' . BASE_PATH . '/trucks');
        exit;
}