<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $this->conn = null;
        
        // Check for Render's PostgreSQL connection string first
        $render_db_url = getenv('DATABASE_URL');
        if ($render_db_url) {
            // We are on Render, connect to PostgreSQL
            try {
                $this->conn = new PDO($render_db_url);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die('Render DB Connection Error: ' . $e->getMessage());
            }
        } else {
            // We are on a local machine, connect to MySQL
            $host = 'localhost';
            $db_name = 'newmann_tracking_db';
            $username = 'root';
            $password = '';

            try {
                $this->conn = new PDO(
                    "mysql:host={$host};dbname={$db_name}",
                    $username,
                    $password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die('Local DB Connection Error: ' . $e->getMessage());
            }
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}