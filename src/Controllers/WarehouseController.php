<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

switch ($route) {
    case '/warehouses':
        try {
            $stmt = $db->query("SELECT warehouse_id, location, manager, capacity FROM warehouse ORDER BY location ASC");
            $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching warehouses: " . $e->getMessage();
            $warehouses = [];
        }
        $page_title = 'Warehouses';
        require_once ROOT_PATH . '/templates/warehouse/list.php';
        break;

    case '/warehouse/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // TODO: Add validation for duplicate location
                $sql = "INSERT INTO warehouse (location, manager, capacity) VALUES (:location, :manager, :capacity)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':location' => $_POST['location'],
                    ':manager' => $_POST['manager'] ?: null,
                    ':capacity' => $_POST['capacity'] ?: null
                ]);
                $_SESSION['success_message'] = "Warehouse successfully added!";
                header('Location: ' . BASE_PATH . '/warehouses');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error adding warehouse: " . $e->getMessage();
            }
        }
        $page_title = 'Add New Warehouse';
        require_once ROOT_PATH . '/templates/warehouse/add.php';
        break;

    case '/warehouse/edit':
        $warehouse_id = $_GET['id'] ?? 0;
        if (!$warehouse_id) {
            header('Location: ' . BASE_PATH . '/warehouses');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // TODO: Add validation for duplicate location if it has changed
                $sql = "UPDATE warehouse SET location = :location, manager = :manager, capacity = :capacity WHERE warehouse_id = :warehouse_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':location' => $_POST['location'],
                    ':manager' => $_POST['manager'] ?: null,
                    ':capacity' => $_POST['capacity'] ?: null,
                    ':warehouse_id' => $warehouse_id
                ]);
                $_SESSION['success_message'] = "Warehouse #{$warehouse_id} updated successfully!";
                header('Location: ' . BASE_PATH . '/warehouses');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating warehouse: " . $e->getMessage();
            }
        }

        try {
            $stmt = $db->prepare("SELECT * FROM warehouse WHERE warehouse_id = :id");
            $stmt->execute([':id' => $warehouse_id]);
            $warehouse = $stmt->fetch();
            if (!$warehouse) {
                $_SESSION['error_message'] = "Warehouse not found.";
                header('Location: ' . BASE_PATH . '/warehouses');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching warehouse: " . $e->getMessage();
            header('Location: ' . BASE_PATH . '/warehouses');
            exit;
        }

        $page_title = 'Edit Warehouse';
        require_once ROOT_PATH . '/templates/warehouse/edit.php';
        break;

    case '/warehouse/delete':
        $warehouse_id = $_GET['id'] ?? 0;
        if ($warehouse_id) {
            try {
                $stmt = $db->prepare("DELETE FROM warehouse WHERE warehouse_id = :id");
                $stmt->execute([':id' => $warehouse_id]);
                $_SESSION['success_message'] = "Warehouse #{$warehouse_id} has been deleted.";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error deleting warehouse: " . $e->getMessage();
            }
        }
        header('Location: ' . BASE_PATH . '/warehouses');
        exit;
}