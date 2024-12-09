<?php
// Скрипт проверки авторизации
session_start();
if ($_SESSION['allowed'] == false) {
    header("Location: /admin/login.php");
    exit();
}