<?php
// Главная страница
$PAGE_TITLE = "Главная";
require "auth.php";
require_once '../../botkit/bootstrap.php';
require "html-start.php";
?>
<?php require "navbar.php" ?>

<div class="container">
    <h1 class="m-3">Управление Техботом</h1>
    <div class="row">
        <div class="col-sm">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ссылки на соц. сети Техбота</h5>
                    <div class="list-group list-group-flush">
                        <a href="https://vk.com/vpmt_bot" class="list-group-item list-group-item-action">ВКонтакте</a>
                        <a href="https://t.me/vpmt_texbot" class="list-group-item list-group-item-action">Telegram</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Отчёты</h5>
                    <div class="list-group list-group-flush">
                        <a href="/admin/reports/usercount.php" class="list-group-item list-group-item-action">Количество зарегистрированных пользователей</a>
                        <a href="/admin/reports/functions.php" class="list-group-item list-group-item-action">Использование функций Техбота (по группам)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require "html-end.php"; ?>