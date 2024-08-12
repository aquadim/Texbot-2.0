<?php
// Общий файл инициализации

define('public_dir', realpath(__DIR__ . '/../public'));
define('root_dir', realpath(__DIR__ . '/..'));

require_once root_dir . '/vendor/autoload.php';

// Сбор переменных окружения
$dotenv = \Dotenv\Dotenv::createImmutable(root_dir);
$dotenv->load();