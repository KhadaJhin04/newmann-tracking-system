<?php

use App\Models\Database;

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

$db = Database::getInstance()->getConnection();

// --- Authorization: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

// Determine user type and table details from session
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$table_name = ($user_type === 'management') ? 'management' : 'driver';
$id_column = ($user_type === 'management') ? 'manager_id' : 'driver_id';

// Handle form submission for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $errors = [];

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All password fields are required.";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation do not match.";
    }
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }
    
    if (empty($errors)) {
        try {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM {$table_name} WHERE {$id_column} = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Current password is correct, update to new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $db->prepare("UPDATE {$table_name} SET password_hash = ? WHERE {$id_column} = ?");
                $update_stmt->execute([$new_hash, $user_id]);

                $_SESSION['success_message'] = "Password updated successfully!";
            } else {
                $_SESSION['error_message'] = "Incorrect current password.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode('<br>', $errors);
    }
    
    // Redirect back to profile page to show message
    header('Location: ' . BASE_PATH . '/profile');
    exit;
}


// Fetch user details to display on the page
try {
    $stmt = $db->prepare("SELECT * FROM {$table_name} WHERE {$id_column} = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
} catch (PDOException $e) {
    $user_data = null;
    $_SESSION['error_message'] = "Could not fetch user profile.";
}

$page_title = 'My Profile';
require_once ROOT_PATH . '/templates/profile/index.php';