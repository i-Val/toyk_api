<?php
function test($host, $user, $pass) {
    echo "Testing $user@$host ... ";
    try {
        $dsn = "mysql:host=$host;dbname=toykappadmin"; // Try connecting to known DB
        $pdo = new PDO($dsn, $user, $pass);
        echo "SUCCESS\n";
    } catch (\PDOException $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

test('localhost', 'root', '');
test('127.0.0.1', 'root', '');
test('localhost', 'toykadmin', 'toyk@#$123');
