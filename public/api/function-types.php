<?php
/*
[
    {    
        "name": "Расписание",
        "id": 1
    },
    ...
]
*/
require '../../botkit/bootstrap.php';
require 'auth.php';

use BotKit\Database;
use BotKit\Entities\TexbotFunction;

#region validation
#endregion

$output = [];

$em = Database::getEM();
$repo = $em->getRepository(TexbotFunction::class);
$data = $repo->findAll();

foreach ($data as $f) {
    $output[] = ["id" => $f->getId(), "name"=> $f->getName()];
}

echo json_encode($output);