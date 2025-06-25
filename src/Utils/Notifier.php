<?php

namespace App\Utils;

use PDO;
use Exception;

class Notifier
{
    /**
     * Creates notifications for a delivery event, sending to Admins, Logistics Managers,
     * and the specific Warehouse Manager responsible for the delivery's origin warehouse.
     *
     * @param PDO $db The database connection.
     * @param int $delivery_id The ID of the relevant delivery.
     * @param string $message The notification message.
     * @param string|null $link_url Optional URL for the notification link.
     * @return bool True on success, false on failure.
     */
    public static function createForDeliveryEvent(PDO $db, int $delivery_id, string $message, ?string $link_url = null): bool
    {
        $recipient_ids = [];

        try {
            // 1. Get all System Admins and Logistics Managers
            $stmt_roles = $db->prepare("SELECT manager_id FROM management WHERE role IN ('System Admin', 'Logistics Manager')");
            $stmt_roles->execute();
            $recipient_ids = $stmt_roles->fetchAll(PDO::FETCH_COLUMN);

            // 2. Get the specific Warehouse Manager for this delivery's origin warehouse
            $stmt_wh_manager = $db->prepare("
                SELECT m.manager_id
                FROM management m
                JOIN delivery d ON m.warehouse_id = d.warehouse_id
                WHERE d.delivery_id = ? AND m.role = 'Warehouse Manager'
            ");
            $stmt_wh_manager->execute([$delivery_id]);
            $warehouse_manager_id = $stmt_wh_manager->fetchColumn();
            if ($warehouse_manager_id) {
                $recipient_ids[] = $warehouse_manager_id;
            }

            // 3. Ensure the list of recipients is unique and not empty
            $unique_recipient_ids = array_unique($recipient_ids);
            if (empty($unique_recipient_ids)) {
                return false;
            }
            
            // 4. Insert notifications for all recipients
            $sql_insert = "INSERT INTO notifications (manager_id, message, link_url) VALUES (?, ?, ?)";
            $stmt_insert = $db->prepare($sql_insert);
            
            $success_count = 0;
            foreach ($unique_recipient_ids as $manager_id) {
                if ($stmt_insert->execute([$manager_id, $message, $link_url])) {
                    $success_count++;
                }
            }
            
            return $success_count > 0;

        } catch (Exception $e) {
            error_log("Notification creation failed for delivery event: " . $e->getMessage());
            return false;
        }
    }
}