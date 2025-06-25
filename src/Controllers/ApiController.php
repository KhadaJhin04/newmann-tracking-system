<?php

use App\Models\Database;
use App\Utils\Notifier;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/newmann_tracking');
    define('ROOT_PATH', dirname(__DIR__, 2));
}

$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();

    switch($route) {
        case '/api/delivery/update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "driver") { throw new Exception('Unauthorized.', 401); }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $delivery_id = filter_var($data['delivery_id'] ?? null, FILTER_VALIDATE_INT);
            $new_status = trim($data['new_status'] ?? '');
            $delay_reason_id = filter_var($data['delay_reason_id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $linked_anomaly_id = filter_var($data['linked_anomaly_id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            if (!$delivery_id || empty($new_status)) { throw new Exception('Delivery ID and new status are required.', 400); }

            $stmt_check = $db->prepare("SELECT truck_id FROM delivery WHERE delivery_id = ? AND driver_id = ?");
            $stmt_check->execute([$delivery_id, $_SESSION['user_id']]);
            $delivery_truck = $stmt_check->fetch();
            if (!$delivery_truck) { throw new Exception('This delivery is not assigned to you.', 403); }

            $sql_parts = ["status = :status"];
            $params = [':status' => $new_status, ':delivery_id' => $delivery_id];

            if ($new_status === 'Delivered') {
                $sql_parts[] = "actual_arrival = NOW()";
            }
            if ($new_status === 'Delayed') {
                if ($delay_reason_id !== null) {
                    $sql_parts[] = "delay_reason_id = :delay_reason_id";
                    $params[':delay_reason_id'] = $delay_reason_id;
                }
                if ($linked_anomaly_id !== null) {
                    $sql_parts[] = "linked_anomaly_id = :linked_anomaly_id";
                    $params[':linked_anomaly_id'] = $linked_anomaly_id;
                }
            }

            $sql = "UPDATE delivery SET " . implode(', ', $sql_parts) . " WHERE delivery_id = :delivery_id";
            $stmt_update = $db->prepare($sql);
            $stmt_update->execute($params);
            
            if (isset($data['latitude'], $data['longitude'])) {
                $lat = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
                $lon = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);
                if ($lat && $lon && $delivery_truck['truck_id']) {
                    $sql_gps = "INSERT INTO truck_gps_logs (truck_id, latitude, longitude, log_timestamp) VALUES (?, ?, ?, NOW())";
                    $stmt_gps = $db->prepare($sql_gps);
                    $stmt_gps->execute([$delivery_truck['truck_id'], $lat, $lon]);
                }
            }
            
            $notification_message = "Driver updated status for Delivery #{$delivery_id} to '{$new_status}'.";
            Notifier::createForDeliveryEvent($db, $delivery_id, $notification_message, BASE_PATH . "/delivery/details?id={$delivery_id}");
            
            echo json_encode(['status' => 'success', 'message' => 'Delivery status updated successfully.']);
            break;
            
        // The rest of the API cases
        case '/api/driver/deliveries':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "driver") { throw new Exception('Unauthorized.', 401); }
            $driver_id = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT delivery_id, truck_id, destination_details, status, DATE_FORMAT(departure_datetime, '%b %d, %Y %h:%i %p') as departure_datetime_formatted, DATE_FORMAT(estimated_arrival, '%b %d, %Y %h:%i %p') as estimated_arrival_formatted FROM delivery WHERE driver_id = :driver_id AND status IN ('Pending', 'Scheduled', 'Preparing', 'In Transit', 'Delayed') ORDER BY departure_datetime ASC");
            $stmt->execute([':driver_id' => $driver_id]);
            echo json_encode(['status' => 'success', 'deliveries' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case '/api/anomaly/report':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "driver") { throw new Exception('Unauthorized.', 401); }
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['delivery_id']) || empty($data['anomaly_type']) || empty($data['location']) || empty($data['severity'])) { throw new Exception('Missing required fields.', 400); }
            $sql = "INSERT INTO road_anomaly (delivery_id, type, location, severity, description, reported_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$data['delivery_id'], $data['anomaly_type'], $data['location'], $data['severity'], $data['description'] ?? null]);
            $notification_message = "Driver reported a new anomaly ({$data['severity']} {$data['anomaly_type']}) for Delivery #{$data['delivery_id']}.";
            Notifier::createForDeliveryEvent($db, $data['delivery_id'], $notification_message, BASE_PATH . "/delivery/details?id={$data['delivery_id']}");
            echo json_encode(['status' => 'success', 'message' => 'Anomaly reported successfully.']);
            break;

        case '/api/gps/log':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "driver") { throw new Exception('Unauthorized.', 401); }
            $data = json_decode(file_get_contents('php://input'), true);
            $truck_id = filter_var($data['truck_id'] ?? null, FILTER_VALIDATE_INT);
            $lat = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $lon = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$truck_id || !$lat || !$lon) { throw new Exception('Missing required GPS data.', 400); }
            $sql = "INSERT INTO truck_gps_logs (truck_id, latitude, longitude, log_timestamp) VALUES (?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$truck_id, $lat, $lon]);
            echo json_encode(['status' => 'success', 'message' => 'GPS log received.']);
            break;

        case '/api/truck_locations':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "management") { throw new Exception('Unauthorized.', 401); }
            $sql = "SELECT t.truck_id, t.plate_number, d.status as delivery_status, d.destination_details, gps.latitude, gps.longitude, gps.log_timestamp FROM truck t JOIN delivery d ON t.truck_id = d.truck_id JOIN truck_gps_logs gps ON t.truck_id = gps.truck_id WHERE d.status IN ('In Transit', 'Delayed') AND gps.log_id = (SELECT MAX(gps_inner.log_id) FROM truck_gps_logs gps_inner WHERE gps_inner.truck_id = t.truck_id) ORDER BY t.plate_number ASC";
            $stmt = $db->query($sql);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case '/api/notifications':
            if (!isset($_SESSION['user_id'])) { throw new Exception('Unauthorized', 401); }
            $manager_id = $_SESSION['user_id'];
            $count_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE manager_id = ? AND is_read = 0");
            $count_stmt->execute([$manager_id]);
            $unread_count = $count_stmt->fetchColumn();
            $list_stmt = $db->prepare("SELECT * FROM notifications WHERE manager_id = ? ORDER BY created_at DESC LIMIT 10");
            $list_stmt->execute([$manager_id]);
            $notifications = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'unread_count' => $unread_count, 'notifications' => $notifications]);
            break;

        case '/api/notifications/mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Invalid request method.', 405); }
            if (!isset($_SESSION['user_id'])) { throw new Exception('Unauthorized', 401); }
            $manager_id = $_SESSION['user_id'];
            $action = $_POST['action'] ?? '';
            $notification_id = $_POST['notification_id'] ?? null;
            if ($action === 'mark_one_read' && $notification_id) {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND manager_id = ?");
                $stmt->execute([$notification_id, $manager_id]);
            } elseif ($action === 'mark_all_read') {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE manager_id = ? AND is_read = 0");
                $stmt->execute([$manager_id]);
            } else {
                throw new Exception('Invalid action.', 400);
            }
            echo json_encode(['status' => 'success']);
            break;
            
        default:
            throw new Exception('API endpoint not found.', 404);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("API PDOException: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}