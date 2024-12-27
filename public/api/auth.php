<?php
// Скрипт проверяет авторизацию
session_start();
if (!isset($_SESSION["allowed"]) || !$_SESSION["allowed"]) {
    http_response_code(401);
    exit();
}