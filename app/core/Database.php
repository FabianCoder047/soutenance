<?php
declare(strict_types=1);

class Database {
    private static ?mysqli $instance = null;

    public static function getInstance(): mysqli {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? 'fabio';
            $name = $_ENV['DB_NAME'] ?? 'emedia_support';
            
            self::$instance = new mysqli($host, $user, $pass, $name);
            if (self::$instance->connect_error) {
                die('Erreur de connexion : ' . self::$instance->connect_error);
            }
            self::$instance->set_charset('utf8mb4');
        }
        return self::$instance;
    }
}
