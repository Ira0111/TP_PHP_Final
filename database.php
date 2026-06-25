<?php

function getPDO() {
    static $pdo = null;

    if ($pdo === null) {
        $host = "localhost";
        $dbname = "kultrack";
        $username = "root";
        $password = "root";

        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur connexion : " . $e->getMessage());
        }
    }

    return $pdo;
}
