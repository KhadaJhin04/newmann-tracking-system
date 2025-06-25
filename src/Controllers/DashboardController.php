<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();

$user_role = $_SESSION['role'] ?? null;
$user_warehouse_id = $_SESSION['warehouse_id'] ?? null;

$stats = [];
$recent_deliveries = [];
$recent_anomalies = [];

try {
    $warehouse_filter_sql = '';
    $warehouse_params = [];
    if ($user_role === 'Warehouse Manager' && !empty($user_warehouse_id)) {
        $warehouse_filter_sql = ' WHERE warehouse_id = ?';
        $warehouse_params[] = $user_warehouse_id;
    }

    // --- CORRECTED QUERY LOGIC ---
    // This query was the source of the error. The parameters were not being
    // correctly duplicated for each sub-query. This new version fixes that.
    $sql = "
        SELECT 
            (SELECT COUNT(*) FROM delivery{$warehouse_filter_sql}) as total_deliveries,
            (SELECT COUNT(*) FROM delivery WHERE status = 'In Transit' " . str_replace('WHERE', 'AND', $warehouse_filter_sql) . ") as in_transit,
            (SELECT COUNT(*) FROM delivery WHERE status = 'Pending' " . str_replace('WHERE', 'AND', $warehouse_filter_sql) . ") as pending,
            (SELECT COUNT(*) FROM delivery WHERE status = 'Delivered' " . str_replace('WHERE', 'AND', ' ' . $warehouse_filter_sql) . ") as delivered
    ";

    // Build the final parameters array, duplicating the warehouse ID for each sub-query that needs it.
    $final_params = [];
    if ($user_role === 'Warehouse Manager' && !empty($user_warehouse_id)) {
        $final_params = array_fill(0, substr_count($sql, '?'), $user_warehouse_id);
    }
    
    $stmt_stats = $db->prepare($sql);
    $stmt_stats->execute($final_params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // These remain global and do not need filtering
    $stats['total_trucks'] = $db->query("SELECT COUNT(*) FROM truck")->fetchColumn();
    $stats['total_drivers'] = $db->query("SELECT COUNT(*) FROM driver")->fetchColumn();


    // Fetch recent activity with filters
    $recent_deliveries_sql = "
        SELECT d.delivery_id, dr.name as driver_name, t.plate_number, d.status, d.departure_datetime 
        FROM delivery d 
        LEFT JOIN driver dr ON d.driver_id = dr.driver_id 
        LEFT JOIN truck t ON d.truck_id = t.truck_id 
        " . ($warehouse_filter_sql ? str_replace('delivery', 'd', $warehouse_filter_sql) : "") . "
        ORDER BY d.delivery_id DESC 
        LIMIT 5";
    $stmt_recent_deliveries = $db->prepare($recent_deliveries_sql);
    $stmt_recent_deliveries->execute($warehouse_params);
    $recent_deliveries = $stmt_recent_deliveries->fetchAll(PDO::FETCH_ASSOC);

    // Recent anomalies remain global
    $recent_anomalies_stmt = $db->query("
        SELECT ra.type, ra.location, ra.description, ra.reported_at, d.delivery_id 
        FROM road_anomaly ra 
        LEFT JOIN delivery d ON ra.delivery_id = d.delivery_id 
        ORDER BY ra.anomaly_id DESC 
        LIMIT 5
    ");
    $recent_anomalies = $recent_anomalies_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching dashboard data: " . $e->getMessage();
}

$page_title = 'Dashboard';
require_once ROOT_PATH . '/templates/dashboard/index.php';