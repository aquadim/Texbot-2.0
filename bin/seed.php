<?php
// Использование:
// php seed.php <год поступления первого курса> <путь до файла с id семестров>

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;

$em = Database::getEM();

// Платформы
$vk_platform = new Entities\Platform("vk.com");
$em->persist($vk_platform);

// Группы
$first_course_year = $argv[1]; // Год поступления первого курса
$spec_names = [
    'ИС', 'СП', 'ТМ',
    'ЭЛ', 'ПК', 'ОС',
    'МО', 'ЭМ', 'НС',
    'СВ', 'ТП', 'ТО',
    'СПП'
];
foreach ($spec_names as $spec_name) {
    $spec = new Entities\CollegeSpec();
    $spec->setName($spec_name);
    $em->persist($spec);
    
    for ($i = 1; $i < 5; $i++) {
        $group = new Entities\CollegeGroup();
        $group->setSpec($spec);
        $group->setCourseNum($i);
        $group->setEnrolledAt($first_course_year - $i + 1);
        $em->persist($group);
    }
}
$em->flush();

// Семестры АВЕРС
// Это первая строка в файле?
$first_line = true;
// Последний ID_PGROUP
$last_csv_id = null;
// Сколько раз эта группа попалась подряд
$this_group_in_a_row = 1;

foreach (file($argv[2]) as $line) {
    if ($first_line) {
        $first_line = false;
        continue;
    }
    
    $data = str_getcsv($line);
    
    if ($data[0] === $last_csv_id) {
        $this_group_in_a_row++;
    } else {
        $this_group_in_a_row = 1;
    }
    
    $last_csv_id = $data[0];
    
    // Получение группы
    $dql = 'SELECT g '.
    'FROM '.Entities\CollegeGroup::class.' g '.
    'JOIN g.spec s '.
    'WHERE s.name=:specName AND g.enrolled_at=:enrolledAt';
    
    $q = $em->createQuery($dql);
    $q->setParameters(['specName'=>$data[1], 'enrolledAt'=>$data[2]]);
    $result = $q->getResult();
    
    if (count($result) === 0) {
        echo "Предупреждение! Строка не обработана:\n";
        print_r($data);
        continue;
    }
    $group = $result[0];
    
    // Создание сущности
    $period = new Entities\Period();
    $period->setGroup($group);
    $period->setOrdNumber($this_group_in_a_row);
    $period->setAversId((int)$data[7]);
    $em->persist($period);
}

$em->flush();
echo "Старт базы данных проведён успешно!\n";
