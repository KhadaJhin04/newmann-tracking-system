<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

switch ($route) {
    case '/models':
        try {
            $stmt = $db->query("SELECT model_id, model_name, brand, created_at FROM motorcycle_models ORDER BY brand, model_name ASC");
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching models: " . $e->getMessage();
            $models = [];
        }
        $page_title = 'Motorcycle Models';
        require_once ROOT_PATH . '/templates/model/list.php';
        break;

    case '/model/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // TODO: Add validation for duplicate model name/brand
                $sql = "INSERT INTO motorcycle_models (model_name, brand) VALUES (:model_name, :brand)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':model_name' => $_POST['model_name'],
                    ':brand' => $_POST['brand'] ?: null
                ]);
                $_SESSION['success_message'] = "Motorcycle Model successfully added!";
                header('Location: ' . BASE_PATH . '/models');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error adding model: " . $e->getMessage();
            }
        }
        $page_title = 'Add New Model';
        require_once ROOT_PATH . '/templates/model/add.php';
        break;

    case '/model/edit':
        $model_id = $_GET['id'] ?? 0;
        if (!$model_id) {
            header('Location: ' . BASE_PATH . '/models');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // TODO: Add validation for duplicate model name/brand if changed
                $sql = "UPDATE motorcycle_models SET model_name = :model_name, brand = :brand WHERE model_id = :model_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':model_name' => $_POST['model_name'],
                    ':brand' => $_POST['brand'] ?: null,
                    ':model_id' => $model_id
                ]);
                $_SESSION['success_message'] = "Model #{$model_id} updated successfully!";
                header('Location: ' . BASE_PATH . '/models');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating model: " . $e->getMessage();
            }
        }

        try {
            $stmt = $db->prepare("SELECT * FROM motorcycle_models WHERE model_id = :id");
            $stmt->execute([':id' => $model_id]);
            $model = $stmt->fetch();
            if (!$model) {
                $_SESSION['error_message'] = "Model not found.";
                header('Location: ' . BASE_PATH . '/models');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching model: " . $e->getMessage();
            header('Location: ' . BASE_PATH . '/models');
            exit;
        }

        $page_title = 'Edit Model';
        require_once ROOT_PATH . '/templates/model/edit.php';
        break;

    case '/model/delete':
        $model_id = $_GET['id'] ?? 0;
        if ($model_id) {
            try {
                $stmt = $db->prepare("DELETE FROM motorcycle_models WHERE model_id = :id");
                $stmt->execute([':id' => $model_id]);
                $_SESSION['success_message'] = "Model #{$model_id} has been deleted.";
            } catch (PDOException $e) {
                // Catch foreign key constraint violation
                if ($e->getCode() == '23000') {
                    $_SESSION['error_message'] = "Cannot delete model #{$model_id} because it is currently assigned to one or more deliveries.";
                } else {
                    $_SESSION['error_message'] = "Error deleting model: " . $e->getMessage();
                }
            }
        }
        header('Location: ' . BASE_PATH . '/models');
        exit;
}