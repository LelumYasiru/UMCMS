<?php
// Config: Database Connection
$host = 'localhost';
$dbname = 'my_new_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed. Please import database.sql into MySQL. Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
