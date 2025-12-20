<?php
session_start();

try {
    $dbCustomers = new PDO("sqlite:" . __DIR__ . "/../File1.db");
    $dbCustomers->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $dbSales = new PDO("sqlite:" . __DIR__ . "/../File2.db");
    $dbSales->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database Connection Error");
}
