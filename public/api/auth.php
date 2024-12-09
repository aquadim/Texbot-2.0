<?php
// Скрипт проверяет авторизацию
session_start();
if (!$_SESSION["allowed"]) {
    http_response_code(401);
    exit();
}