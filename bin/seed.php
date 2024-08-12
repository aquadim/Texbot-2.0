#!/usr/bin/env php
<?php

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;

$em = Database::getEM();

// Платформы
$vk_platform = new Entities\Platform("vk.com");
$em->persist($vk_platform);

// Группы
$spec_names = [
    'ИС', 'СП', 'ТМ',
    'ЭЛ', 'ПК', 'ОС',
    'МО', 'ЭМ', 'НС',
    'СВ', 'ТП'
];
foreach ($spec_names as $spec_name) {
    $spec = new Entities\CollegeSpec();
    $spec->setName($spec_name);
    $em->persist($spec);
    
    for ($i = 1; $i < 5; $i++) {
        $group = new Entities\CollegeGroup();
        $group->setSpec($spec);
        $group->setCourseNum($i);
        $em->persist($group);
    }
}

$em->flush();
echo "Старт базы данных проведён успешно!\n";
