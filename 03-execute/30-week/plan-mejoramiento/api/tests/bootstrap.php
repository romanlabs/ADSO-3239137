<?php

require __DIR__.'/../vendor/autoload.php';

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$database = getenv('DB_DATABASE') ?: 'laptops_test';
$user = getenv('DB_USERNAME') ?: 'laptops';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $password);
    $exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname = ".$pdo->quote($database))->fetchColumn();
    if (! $exists) {
        $pdo->exec('CREATE DATABASE "'.str_replace('"', '', $database).'"');
    }
} catch (PDOException) {
    // Unit tests do not need a database; feature tests will report the connection error themselves.
}
