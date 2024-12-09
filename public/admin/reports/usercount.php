<?php
$PAGE_TITLE = "Зарегистрированные пользователи";
require_once '../../../botkit/bootstrap.php';
require "../html-start.php";
?>
<?php require "../navbar.php" ?>

<div class="container">
    <h1>Зарегистрированные пользователи</h1>
    <p>Количество студентов, которые зарегистрировались в Техботе в разрезе групп</p>
    <div id="tableArea"></div>
</div>

<script src="/admin/reports/js/usercount.js"></script>
<?php require "../html-end.php"; ?>