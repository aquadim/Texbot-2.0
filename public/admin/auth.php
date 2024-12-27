<?php
// Скрипт проверки авторизации
session_start();
if (!isset($_SESSION["allowed"]) || $_SESSION['allowed'] == false) {
    header("Location: /admin/login.php");
    exit();
}