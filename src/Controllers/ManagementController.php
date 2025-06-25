<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();
$request_uri_path = strtok($_SERVER['REQUEST_URI'], '?');
$route = str_replace(BASE_PATH, '', $request_uri_path);

// --- Authorization Check ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'management' || $_SESSION['role'] !== 'System Admin') {
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    header('Location: ' . BASE_PATH . '/dashboard');
    exit;
}


switch ($route) {
    case '/users':
        try {
            $stmt = $db->query("SELECT manager_id, name, username, email, role FROM management ORDER BY name ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching users: " . $e->getMessage();
            $users = [];
        }
        $page_title = 'User Management';
        require_once ROOT_PATH . '/templates/management/list.php';
        break;

    case '/user/add':
        $warehouses = $db->query("SELECT warehouse_id, location FROM warehouse ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            // --- SERVER-SIDE VALIDATION ---
            if (empty($_POST['name'])) { $errors[] = "Name is required."; }
            if (empty($_POST['username'])) { $errors[] = "Username is required."; }
            if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = "A valid email is required."; }
            if (empty($_POST['role'])) { $errors[] = "Role is required."; }
            if (empty($_POST['password'])) { $errors[] = "Password is required."; }
            if (strlen($_POST['password']) < 6) { $errors[] = "Password must be at least 6 characters long."; }
            if ($_POST['password'] !== $_POST['confirm_password']) { $errors[] = "Passwords do not match."; }

            // Check for unique username
            $stmt = $db->prepare("SELECT manager_id FROM management WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            if ($stmt->fetch()) { $errors[] = "Username '{$_POST['username']}' is already taken."; }

            // Check for unique email
            $stmt = $db->prepare("SELECT manager_id FROM management WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) { $errors[] = "Email '{$_POST['email']}' is already in use."; }
            
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
            } else {
                try {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $warehouse_id = ($_POST['role'] === 'Warehouse Manager' && !empty($_POST['warehouse_id'])) ? $_POST['warehouse_id'] : null;

                    $sql = "INSERT INTO management (name, username, email, role, password_hash, warehouse_id) VALUES (:name, :username, :email, :role, :password_hash, :warehouse_id)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':name' => $_POST['name'], ':username' => $_POST['username'], ':email' => $_POST['email'],
                        ':role' => $_POST['role'], ':password_hash' => $hashed_password, ':warehouse_id' => $warehouse_id
                    ]);
                    $_SESSION['success_message'] = "User successfully created!";
                    header('Location: ' . BASE_PATH . '/users');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Database error creating user: " . $e->getMessage();
                }
            }
        }
        $page_title = 'Add New User';
        require_once ROOT_PATH . '/templates/management/add.php';
        break;

    case '/user/edit':
        $user_id = $_GET['id'] ?? 0;
        if (!$user_id) { header('Location: ' . BASE_PATH . '/users'); exit; }
        
        $warehouses = $db->query("SELECT warehouse_id, location FROM warehouse ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            // --- SERVER-SIDE VALIDATION ---
            if (empty($_POST['name'])) { $errors[] = "Name is required."; }
            if (empty($_POST['username'])) { $errors[] = "Username is required."; }
            if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = "A valid email is required."; }
            if (empty($_POST['role'])) { $errors[] = "Role is required."; }

            // Check for unique username (if changed)
            $stmt = $db->prepare("SELECT manager_id FROM management WHERE username = ? AND manager_id != ?");
            $stmt->execute([$_POST['username'], $user_id]);
            if ($stmt->fetch()) { $errors[] = "Username '{$_POST['username']}' is already taken by another user."; }

            // Check for unique email (if changed)
            $stmt = $db->prepare("SELECT manager_id FROM management WHERE email = ? AND manager_id != ?");
            $stmt->execute([$_POST['email'], $user_id]);
            if ($stmt->fetch()) { $errors[] = "Email '{$_POST['email']}' is already in use by another user."; }

            // Validate password only if it's being changed
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 6) { $errors[] = "Password must be at least 6 characters long."; }
                if ($_POST['password'] !== $_POST['confirm_password']) { $errors[] = "Passwords do not match."; }
            }

            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
            } else {
                try {
                    $warehouse_id = ($_POST['role'] === 'Warehouse Manager' && !empty($_POST['warehouse_id'])) ? $_POST['warehouse_id'] : null;
                    
                    if (!empty($_POST['password'])) {
                        $sql = "UPDATE management SET name = :name, username = :username, email = :email, role = :role, warehouse_id = :warehouse_id, password_hash = :password_hash WHERE manager_id = :user_id";
                        $params = [
                            ':name' => $_POST['name'], ':username' => $_POST['username'], ':email' => $_POST['email'], ':role' => $_POST['role'],
                            ':warehouse_id' => $warehouse_id, ':password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT), ':user_id' => $user_id
                        ];
                    } else {
                        $sql = "UPDATE management SET name = :name, username = :username, email = :email, role = :role, warehouse_id = :warehouse_id WHERE manager_id = :user_id";
                        $params = [
                            ':name' => $_POST['name'], ':username' => $_POST['username'], ':email' => $_POST['email'], ':role' => $_POST['role'],
                            ':warehouse_id' => $warehouse_id, ':user_id' => $user_id
                        ];
                    }
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);

                    $_SESSION['success_message'] = "User #{$user_id} updated successfully!";
                    header('Location: ' . BASE_PATH . '/users');
                    exit;
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error updating user: " . $e->getMessage();
                }
            }
        }

        try {
            $stmt = $db->prepare("SELECT * FROM management WHERE manager_id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch();
            if (!$user) { $_SESSION['error_message'] = "User not found."; header('Location: ' . BASE_PATH . '/users'); exit; }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error fetching user: " . $e->getMessage(); header('Location: ' . BASE_PATH . '/users'); exit;
        }
        $page_title = 'Edit User';
        require_once ROOT_PATH . '/templates/management/edit.php';
        break;

    case '/user/delete':
        $user_id = $_GET['id'] ?? 0;
        if ($user_id) {
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error_message'] = "You cannot delete your own account.";
            } else {
                 try {
                    $stmt = $db->prepare("DELETE FROM management WHERE manager_id = :id");
                    $stmt->execute([':id' => $user_id]);
                    $_SESSION['success_message'] = "User #{$user_id} has been deleted.";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
                }
            }
        }
        header('Location: ' . BASE_PATH . '/users');
        exit;
}