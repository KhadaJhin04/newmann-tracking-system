<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

if ($route === '/handle_login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $user_type = $_POST['user_type'] ?? 'management';

        if (empty($username) || empty($password)) {
            $_SESSION['error_message'] = 'Username and password are required.';
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        
        $table = ($user_type === 'management') ? 'management' : 'driver';
        $id_field = ($user_type === 'management') ? 'manager_id' : 'driver_id';

        try {
            $columns = ($user_type === 'management') ? "$id_field, name, role, warehouse_id, password_hash" : "$id_field, name, password_hash";
            
            $stmt = $db->prepare("SELECT $columns FROM $table WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user[$id_field];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user_type;
                $_SESSION['role'] = $user['role'] ?? 'Driver';
                
                // --- NEW LOGIC: Save warehouse_id if it exists ---
                if (isset($user['warehouse_id'])) {
                    $_SESSION['warehouse_id'] = $user['warehouse_id'];
                }
                
                $redirect_path = ($user_type === 'management') ? '/dashboard' : '/driver_dashboard';
                header('Location: ' . BASE_PATH . $redirect_path);
                exit;
            } else {
                $_SESSION['error_message'] = 'Invalid username or password.';
                header('Location: ' . BASE_PATH . '/login');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'A database error occurred.';
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
    }
}

if ($route === '/logout') {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

$page_title = 'Login';
require_once ROOT_PATH . '/templates/auth/login.php';