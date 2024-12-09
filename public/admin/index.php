<?php
// Главная страница
$PAGE_TITLE = "Главная";
require "auth.php";
require_once '../../botkit/bootstrap.php';
require "html-start.php";
?>
<?php require "navbar.php" ?>

<div class="container">
    <h1>Управление Техботом</h1>
    <p>Используйте навигацию сверху страницы</p>
</div>

<?php require "html-end.php"; ?>