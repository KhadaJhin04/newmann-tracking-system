<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$page_title = 'Reports & Analytics';

// --- Handle Date Filters ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$reports_data = [];
$prescriptive_insights = [];

try {
    // Build date filter WHERE clauses for different tables
    $delivery_params = [];
    $date_filter_delivery = '';
    if ($start_date && $end_date) {
        $date_filter_delivery = ' WHERE DATE(d.departure_datetime) BETWEEN ? AND ?';
        $delivery_params = [$start_date, $end_date];
    } elseif ($start_date) {
        $date_filter_delivery = ' WHERE DATE(d.departure_datetime) >= ?';
        $delivery_params = [$start_date];
    } elseif ($end_date) {
        $date_filter_delivery = ' WHERE DATE(d.departure_datetime) <= ?';
        $delivery_params = [$end_date];
    }
    
    // 1. Delivery Summary by Status (filtered)
    $query_summary = "SELECT status, COUNT(*) as count FROM delivery d {$date_filter_delivery} GROUP BY status ORDER BY status ASC";
    $stmt_summary = $db->prepare($query_summary);
    $stmt_summary->execute($delivery_params);
    $reports_data['delivery_summary'] = $stmt_summary->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. On-Time vs. Delayed Deliveries (using a date filter on actual_arrival)
    $date_filter_perf = str_replace('d.departure_datetime', 'd.actual_arrival', $date_filter_delivery);
    $perf_params = $delivery_params;

    $sql_on_time = "SELECT COUNT(*) FROM delivery d WHERE status = 'Delivered' AND actual_arrival <= estimated_arrival " . str_replace('WHERE', 'AND', $date_filter_perf);
    $stmt_on_time = $db->prepare($sql_on_time);
    $stmt_on_time->execute($perf_params);
    $reports_data['on_time_count'] = $stmt_on_time->fetchColumn();

    $sql_delayed = "SELECT COUNT(*) FROM delivery d WHERE status = 'Delivered' AND actual_arrival > estimated_arrival " . str_replace('WHERE', 'AND', $date_filter_perf);
    $stmt_delayed = $db->prepare($sql_delayed);
    $stmt_delayed->execute($perf_params);
    $reports_data['delayed_count'] = $stmt_delayed->fetchColumn();

    // 3. Road Anomaly Frequency (unfiltered for overall view)
    $reports_data['anomaly_frequency'] = $db->query("SELECT type, COUNT(*) as count FROM road_anomaly GROUP BY type ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Most Delivered Motorcycle Models (filtered)
    $query_models = "
        SELECT mm.brand, mm.model_name, SUM(dm.quantity) as total_delivered
        FROM delivery_motorcycles dm
        JOIN motorcycle_models mm ON dm.model_id = mm.model_id
        JOIN delivery d ON dm.delivery_id = d.delivery_id
        {$date_filter_delivery}
        GROUP BY dm.model_id, mm.brand, mm.model_name
        ORDER BY total_delivered DESC LIMIT 10
    ";
    $stmt_models = $db->prepare($query_models);
    $stmt_models->execute($delivery_params);
    $reports_data['model_report'] = $stmt_models->fetchAll(PDO::FETCH_ASSOC);

    // 5. Truck Utilization (filtered)
    $query_trucks = "
        SELECT t.plate_number, t.status as truck_status, COUNT(d.delivery_id) as delivery_count
        FROM truck t
        LEFT JOIN delivery d ON t.truck_id = d.truck_id {$date_filter_delivery}
        GROUP BY t.truck_id, t.plate_number, t.status
        ORDER BY delivery_count DESC, t.plate_number ASC
    ";
    $stmt_trucks = $db->prepare($query_trucks);
    $stmt_trucks->execute($delivery_params);
    $reports_data['truck_utilization'] = $stmt_trucks->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Driver Activity (filtered)
    $query_drivers = "
        SELECT dr.name, COUNT(d.delivery_id) as delivery_count
        FROM driver dr
        LEFT JOIN delivery d ON dr.driver_id = d.driver_id {$date_filter_delivery}
        GROUP BY dr.driver_id, dr.name
        ORDER BY delivery_count DESC, dr.name ASC
    ";
    $stmt_drivers = $db->prepare($query_drivers);
    $stmt_drivers->execute($delivery_params);
    $reports_data['driver_activity'] = $stmt_drivers->fetchAll(PDO::FETCH_ASSOC);

    // --- Generate Prescriptive Insights (uses unfiltered delay data for broader insights) ---
    $delays_by_predefined_reason = $db->query("SELECT dr.reason_description, COUNT(d.delivery_id) as count FROM delivery d JOIN delay_reasons dr ON d.delay_reason_id = dr.reason_id WHERE d.status = 'Delayed' GROUP BY dr.reason_id, dr.reason_description ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($delays_by_predefined_reason)) {
        $top_reason = $delays_by_predefined_reason[0];
        $prescriptive_insights[] = "<strong>Insight:</strong> The most common predefined delay reason is '<strong>" . htmlspecialchars($top_reason['reason_description']) . "</strong>', accounting for " . $top_reason['count'] . " delays.";
        
        if (stripos($top_reason['reason_description'], 'Traffic') !== false) {
            $prescriptive_insights[] = "<strong>Recommendation:</strong> Since traffic is a major issue, consider analyzing delivery times vs. traffic patterns to schedule departures during off-peak hours where possible.";
        } elseif (stripos($top_reason['reason_description'], 'Warehouse') !== false) {
            $prescriptive_insights[] = "<strong>Recommendation:</strong> For warehouse-related delays, review loading and dispatch processes to identify bottlenecks.";
        } elseif (stripos($top_reason['reason_description'], 'Mechanical') !== false) {
            $prescriptive_insights[] = "<strong>Recommendation:</strong> Frequent mechanical issues suggest a need to review pre-trip inspection protocols and vehicle maintenance schedules.";
        }
    }
    if (empty($prescriptive_insights)) {
        $prescriptive_insights[] = "Not enough specific delay data has been logged to generate operational insights.";
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error generating reports: " . $e->getMessage();
}

require_once ROOT_PATH . '/templates/report/index.php';