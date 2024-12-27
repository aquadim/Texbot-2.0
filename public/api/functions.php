<?php
// Возвращает количество использований функций группой
// в формате JSON
/*
{
    "2024-10-31": {
        "<function_id>": 108,
        "<function_id>": 21,
        "<function_id>": 150
    },
    "2024-11-01": {
        ...
    },
    ...
}
*/

require '../../botkit/bootstrap.php';
require 'auth.php';

use BotKit\Database;
use BotKit\Entities\UsedFunction;
use BotKit\Entities\CollegeGroup;

#region validation
if (!(
    isset($_POST["dateStart"])
    && isset($_POST["dateEnd"])
    && isset($_POST["groupId"]))) {
    http_response_code(403);
    exit();
}
if ($_POST["dateStart"] === ""
    || $_POST["dateEnd"] === ""
    || $_POST["groupId"] === "") {
    http_response_code(403);
    exit();
}
#endregion

$em = Database::getEM();

$uf_repo = $em->getRepository(UsedFunction::class);

$start_date = new DateTimeImmutable($_POST["dateStart"]);
$end_date   = new DateTimeImmutable($_POST["dateEnd"]);
$group      = $em->find(CollegeGroup::class, $_POST["groupId"]);

$output     = [];
$data       = $uf_repo->getStats($start_date, $end_date, $group);

foreach ($data as $row) {
    $count = $row["cnt"];
    $fn_id = $row["fnid"];
    $used_at = $row["used_at"]->format("Y-m-d");

    if (!array_key_exists($fn_id, $output)) {
        $output[$fn_id] = [];
    }

    $output[$fn_id][$used_at] = $count;
}

echo json_encode($output);