<?php
session_start();
define('ROOT', __DIR__ . '/');
define('URL_RACINE', 'http://localhost/demo/');

require_once ROOT . 'database.php';

spl_autoload_register(function (string $class) {
    $dirs = [
        ROOT . 'controllers/',
        ROOT . 'models/'
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$userController = new UserController();