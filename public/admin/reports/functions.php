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
                <label for="courseNum" class="form-label">Курс</label>
                <select
                    name="courseNum"
                    class="form-select"
                    id="courseNum">
                    <option>Все курсы</option>
                    <option>1 курс</option>
                    <option>2 курс</option>
                    <option>3 курс</option>
                    <option>4 курс</option>
                </select>
            </div>
            
            <div class="col">
                <label for="spec" class="form-label">Специальность</label>
                <select
                    name="spec"
                    class="form-select"
                    id="spec">
                    <option>Все специальности</option>
                </select>
            </div>
            
        </div>
        <div class="row mt-3">
            <button class="btn btn-primary">Сформировать</button>
        </div>
    </form>
</div>

<script src="/admin/reports/js/functions.js"></script>
<?php require "../html-end.php"; ?>