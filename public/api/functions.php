<?php
// Возвращает количество использований функций
// в формате JSON
/*
{
    "2024-10-31": {
        "1ИС": {
            "Расписание": 108,
            "Оценки": 21,
            "Преподаватель": 150
        },
        "1СП": {
            ...
        }
    },
    "2024-11-01": {
        ...
    }
}
*/

require '../../botkit/bootstrap.php';
require 'auth.php';

use BotKit\Database;
use BotKit\Entities\UsedFunction;
use \DateTimeImmutable;

#region validation
if (!(isset($_POST["dateStart"]) && isset($_POST["dateEnd"]))) {
    http_response_code(403);
    exit();
}
#endregion

$em = Database::getEM();
$repo = $em->getRepository(UsedFunction::class);

$start_date = new DateTimeImmutable($_POST["dateStart"]);
$end_date = new DateTimeImmutable($_POST["dateEnd"]);
$data = $repo->getStats($start_date, $end_date);

var_dump($data);