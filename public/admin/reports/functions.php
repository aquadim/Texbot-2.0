<?php
$PAGE_TITLE = "Статистика использования функций";
require_once '../../../botkit/bootstrap.php';
require "../html-start.php";
?>
<?php require "../navbar.php" ?>

<div class="container">
    <h1>Статистика использования функций</h1>
    <p>Сколько раз студенты использовали функции за определённый период.</p>

    <!--Параметры-->
    <form id="settings">
        <div class="row">
            <div class="col">
                <label for="dateStart" class="form-label">Дата начала сбора статистики</label>
                <input type="date" name="dateStart" class="form-control" id="dateStart" required="required">
            </div>
            <div class="col">
                <label for="dateEnd" class="form-label">Дата окончания сбора статистики</label>
                <input type="date" name="dateEnd" class="form-control" id="dateEnd" required="required">
            </div>
            <div class="col">
                <label for="groupId" class="form-label">Группа</label>
                <select name="groupId" class="form-select" id="groupId" required="required"></select>
            </div>
        </div>
        <div class="row mt-3">
            <button class="btn btn-primary">Сформировать</button>
        </div>
    </form>

    <!--Область данных-->
    <div id="dataArea" class="mt-3"></div>
</div>

<script src="/plotly/plotly-2.35.2.min.js"></script>
<script src="/plotly/plotly-locale-ru-latest.js"></script>
<script src="/admin/reports/js/functions.js"></script>
<?php require "../html-end.php"; ?>