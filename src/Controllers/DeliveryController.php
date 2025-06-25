<?php

use App\Models\Database;
use App\Utils\Notifier;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

$user_role = $_SESSION['role'] ?? null;
$user_warehouse_id = $_SESSION['warehouse_id'] ?? null;

switch ($route) {
    case '/deliveries':
        try {
            // --- NEW: Handle Search Query ---
            $search_term = $_GET['search'] ?? '';

            $base_query = "
                SELECT d.delivery_id, d.status, d.destination_details, dr.name as driver_name, t.plate_number, d.motorcycle_count
                FROM delivery d
                LEFT JOIN driver dr ON d.driver_id = dr.driver_id
                LEFT JOIN truck t ON d.truck_id = t.truck_id
            ";
            
            $params = [];
            $where_clauses = [];

            // Role-Based Access Control Filter
            if ($user_role === 'Warehouse Manager' && !empty($user_warehouse_id)) {
                $where_clauses[] = "d.warehouse_id = ?";
                $params[] = $user_warehouse_id;
            }
            
            // Search Filter
            if (!empty($search_term)) {
                $where_clauses[] = "(d.delivery_id LIKE ? OR d.destination_details LIKE ? OR dr.name LIKE ? OR t.plate_number LIKE ?)";
                $search_param = "%{$search_term}%";
                array_push($params, $search_param, $search_param, $search_param, $search_param);
            }

            if (!empty($where_clauses)) {
                $base_query .= " WHERE " . implode(' AND ', $where_clauses);
            }

            $base_query .= " ORDER BY d.delivery_id DESC";
            
            $stmt = $db->prepare($base_query);
            $stmt->execute($params);
            $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching deliveries: " . $e->getMessage();
            $deliveries = [];
        }
        $page_title = 'Deliveries';
        require_once ROOT_PATH . '/templates/delivery/list.php';
        break;

    // All other cases (add, edit, delete, details, qr) remain the same
    case '/delivery/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db->beginTransaction();
                $motorcycle_models = $_POST['motorcycle_model_id'] ?? [];
                $motorcycle_quantities = $_POST['motorcycle_quantity'] ?? [];
                $total_motorcycle_count = array_sum(array_map('intval', $motorcycle_quantities));
                $sql = "INSERT INTO delivery (driver_id, truck_id, warehouse_id, destination_details, motorcycle_count, status, departure_datetime, estimated_arrival) VALUES (:driver_id, :truck_id, :warehouse_id, :destination_details, :motorcycle_count, :status, :departure_datetime, :estimated_arrival)";
                $stmt = $db->prepare($sql);
                $stmt->execute([':driver_id' => $_POST['driver_id'] ?: null, ':truck_id' => $_POST['truck_id'] ?: null, ':warehouse_id' => $_POST['warehouse_id'] ?: null, ':destination_details' => $_POST['destination_details'], ':motorcycle_count' => $total_motorcycle_count, ':status' => $_POST['status'], ':departure_datetime' => $_POST['departure_datetime'] ?: null, ':estimated_arrival' => $_POST['estimated_arrival'] ?: null, ]);
                $last_delivery_id = $db->lastInsertId();
                $sql_pivot = "INSERT INTO delivery_motorcycles (delivery_id, model_id, quantity) VALUES (:delivery_id, :model_id, :quantity)";
                $stmt_pivot = $db->prepare($sql_pivot);
                for ($i = 0; $i < count($motorcycle_models); $i++) {
                    if (!empty($motorcycle_models[$i]) && !empty($motorcycle_quantities[$i])) {
                        $stmt_pivot->execute([':delivery_id' => $last_delivery_id, ':model_id' => $motorcycle_models[$i], ':quantity' => $motorcycle_quantities[$i]]);
                    }
                }
                $notification_message = "New Delivery #{$last_delivery_id} has been scheduled.";
                Notifier::createForDeliveryEvent($db, $last_delivery_id, $notification_message, BASE_PATH . "/delivery/details?id={$last_delivery_id}");
                $db->commit();
                $_SESSION['success_message'] = "Delivery successfully added!";
                header('Location: ' . BASE_PATH . '/deliveries');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error_message'] = "Error adding delivery: " . $e->getMessage();
            }
        }
        try {
            $drivers = $db->query("SELECT driver_id, name FROM driver ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $trucks = $db->query("SELECT truck_id, plate_number FROM truck WHERE status = 'Active' ORDER BY plate_number")->fetchAll(PDO::FETCH_ASSOC);
            $warehouses = $db->query("SELECT warehouse_id, location FROM warehouse ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);
            $motorcycle_models_list = $db->query("SELECT model_id, brand, model_name FROM motorcycle_models ORDER BY brand, model_name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching form data: " . $e->getMessage();
            $drivers = $trucks = $warehouses = $motorcycle_models_list = [];
        }
        $page_title = 'Add New Delivery';
        require_once ROOT_PATH . '/templates/delivery/add.php';
        break;
    case '/delivery/edit':
        $delivery_id = $_GET['id'] ?? 0;
        if (!$delivery_id) { header('Location: ' . BASE_PATH . '/deliveries'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db->beginTransaction();
                $motorcycle_models = $_POST['motorcycle_model_id'] ?? [];
                $motorcycle_quantities = $_POST['motorcycle_quantity'] ?? [];
                $total_motorcycle_count = array_sum(array_map('intval', $motorcycle_quantities));
                $sql = "UPDATE delivery SET driver_id = :driver_id, truck_id = :truck_id, warehouse_id = :warehouse_id, destination_details = :destination_details, motorcycle_count = :motorcycle_count, status = :status, departure_datetime = :departure_datetime, estimated_arrival = :estimated_arrival WHERE delivery_id = :delivery_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':driver_id' => $_POST['driver_id'] ?: null, ':truck_id' => $_POST['truck_id'] ?: null, ':warehouse_id' => $_POST['warehouse_id'] ?: null, ':destination_details' => $_POST['destination_details'], ':motorcycle_count' => $total_motorcycle_count, ':status' => $_POST['status'], ':departure_datetime' => $_POST['departure_datetime'] ?: null, ':estimated_arrival' => $_POST['estimated_arrival'] ?: null, ':delivery_id' => $delivery_id ]);
                $stmt_delete = $db->prepare("DELETE FROM delivery_motorcycles WHERE delivery_id = ?");
                $stmt_delete->execute([$delivery_id]);
                $sql_pivot = "INSERT INTO delivery_motorcycles (delivery_id, model_id, quantity) VALUES (:delivery_id, :model_id, :quantity)";
                $stmt_pivot = $db->prepare($sql_pivot);
                for ($i = 0; $i < count($motorcycle_models); $i++) {
                    if (!empty($motorcycle_models[$i]) && !empty($motorcycle_quantities[$i])) {
                        $stmt_pivot->execute([':delivery_id' => $delivery_id, ':model_id' => $motorcycle_models[$i], ':quantity' => $motorcycle_quantities[$i]]);
                    }
                }
                $notification_message = "Delivery #{$delivery_id} has been updated by management.";
                Notifier::createForDeliveryEvent($db, $delivery_id, $notification_message, BASE_PATH . "/delivery/details?id={$delivery_id}");
                $db->commit();
                $_SESSION['success_message'] = "Delivery #{$delivery_id} updated successfully!";
                header('Location: ' . BASE_PATH . '/deliveries');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error_message'] = "Error updating delivery: " . $e->getMessage();
            }
        }
        try {
            $stmt = $db->prepare("SELECT * FROM delivery WHERE delivery_id = :id");
            $stmt->execute([':id' => $delivery_id]);
            $delivery = $stmt->fetch();
            if (!$delivery) { $_SESSION['error_message'] = "Delivery not found."; header('Location: ' . BASE_PATH . '/deliveries'); exit; }
            $drivers = $db->query("SELECT driver_id, name FROM driver ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $trucks = $db->query("SELECT truck_id, plate_number FROM truck ORDER BY plate_number")->fetchAll(PDO::FETCH_ASSOC);
            $warehouses = $db->query("SELECT warehouse_id, location FROM warehouse ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);
            $motorcycle_models_list = $db->query("SELECT model_id, brand, model_name FROM motorcycle_models ORDER BY brand, model_name")->fetchAll(PDO::FETCH_ASSOC);
            $stmt_items = $db->prepare("SELECT model_id, quantity FROM delivery_motorcycles WHERE delivery_id = :id");
            $stmt_items->execute([':id' => $delivery_id]);
            $delivery_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching data: " . $e->getMessage(); header('Location: ' . BASE_PATH . '/deliveries'); exit;
        }
        $page_title = 'Edit Delivery';
        require_once ROOT_PATH . '/templates/delivery/edit.php';
        break;
    case '/delivery/delete':
        $delivery_id = $_GET['id'] ?? 0;
        if ($delivery_id) {
            try {
                $stmt = $db->prepare("DELETE FROM delivery WHERE delivery_id = :id");
                $stmt->execute([':id' => $delivery_id]);
                $_SESSION['success_message'] = "Delivery #{$delivery_id} has been deleted.";
            } catch (PDOException $e) { $_SESSION['error_message'] = "Error deleting delivery: " . $e->getMessage(); }
        }
        header('Location: ' . BASE_PATH . '/deliveries');
        exit;
    case '/delivery/details':
        $delivery_id = $_GET['id'] ?? 0;
        if (!$delivery_id) { header('Location: ' . BASE_PATH . '/deliveries'); exit; }
        try {
            $sql_delivery = "SELECT d.*, dr.name as driver_name, t.plate_number, w.location as warehouse_location FROM delivery d LEFT JOIN driver dr ON d.driver_id = dr.driver_id LEFT JOIN truck t ON d.truck_id = t.truck_id LEFT JOIN warehouse w ON d.warehouse_id = w.warehouse_id WHERE d.delivery_id = :id";
            $stmt = $db->prepare($sql_delivery);
            $stmt->execute([':id' => $delivery_id]);
            $delivery = $stmt->fetch();
            $delivery_items = [];
            $delivery_anomalies = [];
            if ($delivery) {
                $stmt_items = $db->prepare("SELECT dm.quantity, mm.model_name, mm.brand FROM delivery_motorcycles dm JOIN motorcycle_models mm ON dm.model_id = mm.model_id WHERE dm.delivery_id = :id ORDER BY mm.brand, mm.model_name");
                $stmt_items->execute([':id' => $delivery_id]);
                $delivery_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                $stmt_anomalies = $db->prepare("SELECT * FROM road_anomaly WHERE delivery_id = :id ORDER BY reported_at DESC");
                $stmt_anomalies->execute([':id' => $delivery_id]);
                $delivery_anomalies = $stmt_anomalies->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error fetching delivery details: " . $e->getMessage(); $delivery = null;
        }
        $page_title = 'Delivery Details';
        require_once ROOT_PATH . '/templates/delivery/details.php';
        break;
    case '/delivery/qr':
        $delivery_id = $_GET['id'] ?? 0;
        if (!$delivery_id) { http_response_code(400); echo "Error: Delivery ID is required."; exit; }
        $qr_data_string = "NEWMANN_DELIVERY_ID::" . $delivery_id;
        try {
            $result = Builder::create()->writer(new PngWriter())->data($qr_data_string)->encoding(new Encoding('UTF-8'))->errorCorrectionLevel(new ErrorCorrectionLevelHigh())->size(300)->margin(10)->roundBlockSizeMode(new RoundBlockSizeModeMargin())->foregroundColor(new Color(0, 0, 0))->backgroundColor(new Color(255, 255, 255))->build();
            header('Content-Type: '.$result->getMimeType());
            echo $result->getString();
        } catch (Exception $e) { http_response_code(500); echo "Error generating QR code: " . $e->getMessage(); }
        exit;
    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>Delivery action not found.</p>";
        break;
}