<?php
// Страница авторизации
$PAGE_TITLE = "Авторизация";
require_once '../../botkit/bootstrap.php';
require "html-start.php";
session_start();

$display_error = false;

// Если метод - POST, произошла попытка авторизации, проверяем
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["password"]) &&
        hash('sha256', $_POST["password"]) == $_ENV["admin_password"]
    ) {
        // Успешная авторизация
        $_SESSION['allowed'] = true;
        header("Location: /admin/index.php");
        exit();
    } else {
        // Неуспешная авторизация
        $display_error = true;
    }
}
?>

<div class="container">
    <h1>Авторизация | Техбот</h1>

    <?php if ($display_error) { ?>
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">Авторизация не выполнена</div>
            <div class="card-body">
                <p class="card-text">
                    Логин или пароль не правильны
                </p>
            </div>
        </div>
    <?php } ?>

    <form method="POST">
        
        <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        
        <button type="submit" class="btn btn-primary">Авторизация</button>
    </form>
</div>

<?php require "html-end.php"; ?>