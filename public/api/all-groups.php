<?php
// Возвращает все группы из БД
/*
[
    {
        "id": <int>,
        "name": <string>
    },
    ...
]
*/
require '../../botkit/bootstrap.php';
require 'auth.php';

use BotKit\Database;
use BotKit\Entities\CollegeGroup;

$output = [];

$em = Database::getEM();
$repo = $em->getRepository(CollegeGroup::class);
$data = $repo->findAll();

foreach ($data as $g) {
    $output[] = ["id" => $g->getId(), "name"=> $g->getHumanName()];
}

echo json_encode($output);