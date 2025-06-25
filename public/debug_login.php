<?php
// This script will help us debug the login issue.
// Make sure to update the database password if you use one.

echo '<h1>Login Debugging Information</h1>';
echo '<hr>';
echo '<strong>PHP Version:</strong> ' . phpversion() . '<br><br>';

// --- Database Connection ---
$host = 'localhost';
$db_name = 'newmann_tracking_db';
$username = 'root';
$password = ''; // IMPORTANT: Change this if your DB password is not empty

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '<strong>Database Connection:</strong> <span style="color:green;">Success!</span><br><br>';
} catch (PDOException $e) {
    die('<strong>Database Connection:</strong> <span style="color:red;">Failed: ' . $e->getMessage() . '</span>');
}

// --- Fetch User Data ---
echo '<strong>Attempting to fetch "admin" user...</strong><br>';
$admin_username = 'admin';

try {
    $stmt = $db->prepare("SELECT manager_id, username, password_hash FROM management WHERE username = :username");
    $stmt->execute([':username' => $admin_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('<strong style="color:red;">FATAL ERROR:</strong> User "admin" was not found in the `management` table. Please go to phpMyAdmin and run the INSERT SQL command again.');
    }

    echo '<strong>User Found:</strong><br><pre style="background:#eee; padding:10px; border:1px solid #ccc;">';
    print_r($user);
    echo '</pre>';

} catch (PDOException $e) {
    die('<strong style="color:red;">DATABASE ERROR:</strong> Could not query the management table. Error: ' . $e->getMessage());
}

// --- Password Verification Logic ---
echo '<hr><h2>Password Verification</h2>';
$password_to_check = 'password123';
$hash_from_db = $user['password_hash'];

echo "<strong>1. Password string being tested:</strong><br><pre>'$password_to_check'</pre>";
echo "<strong>2. Hash pulled from database:</strong><br><pre>'$hash_from_db'</pre>";
echo "<strong>3. Length of the hash:</strong> " . strlen($hash_from_db) . " characters.<br><br>";

if (strlen($hash_from_db) < 60) {
    echo '<p style="color:red; font-weight:bold;">Warning: The hash length is less than 60. This strongly suggests the `password_hash` column in your database is too small. It should be VARCHAR(255).</p>';
}

echo '<strong>Running password_verify()...</strong><br>';

if (password_verify($password_to_check, $hash_from_db)) {
    echo '<h2 style="color:green;">SUCCESS!</h2>';
    echo '<p>The password and the hash match. Login should be working. If it still fails in the main app, there is a very subtle issue we need to investigate further.</p>';
} else {
    echo '<h2 style="color:red;">FAILURE!</h2>';
    echo '<p>The password and the hash DO NOT match. This is the reason login is failing. The most common causes are:</p>';
    echo '<ul><li>The `password_hash` column is not VARCHAR(255) and the hash is being truncated.</li><li>The user was created with a different password or hash value.</li></ul>';
    echo '<p><strong>To fix this, please run the SQL INSERT command again carefully.</strong></p>';
}