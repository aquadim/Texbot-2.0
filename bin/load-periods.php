<?php
// Скрипт для обновления ID семестров

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;

$em = Database::getEM();

if (count($argv) != 2) {
    exit("Использование: php load-periods.php <путь до выгрузки с семестрами>\n");
}
$filename = $argv[1];

// Последний ID_PGROUP
$last_csv_id = null;

// Сколько раз эта группа попалась подряд
$this_group_in_a_row = 1;

// Выражение для получения группы по её специальности и году поступления
$get_group_dql =
'SELECT g '.
'FROM '.Entities\CollegeGroup::class.' g '.
'JOIN g.spec s '.
'WHERE s.name=:specName AND g.enrolled_at=:enrolledAt';

// Выражение для поиска семестра по его id в АВЕРС
$get_period_dql =
'SELECT p '.
'FROM '.Entities\Period::class.' p '.
'WHERE p.avers_id=:aversId';

// Номер текущей строки
$current_line_num = 1;

foreach (file($filename) as $line) {
    if ($current_line_num === 1) {
        $current_line_num++;
        continue;
    }
    
    $data = str_getcsv($line);

    $spec_name = $data[1];
    $enrolled_at = $data[2];
    $avers_id = (int)$data[7];
    
    if ($data[0] === $last_csv_id) {
        $this_group_in_a_row++;
    } else {
        $this_group_in_a_row = 1;
    }
    
    $last_csv_id = $data[0];
    
    // Получение группы
    $q = $em->createQuery($get_group_dql);
    $q->setParameters(['specName'=>$spec_name, 'enrolledAt'=>$enrolled_at]);
    $result = $q->getResult();
    
    if (count($result) === 0) {
        echo "Предупреждение! Строка #$current_line_num не обработана:\n";
        $current_line_num++;
        continue;
    }
    $group = $result[0];

    // Поиск существующего семестра
    $q = $em->createQuery($get_period_dql);
    $q->setParameters(['aversId'=>$avers_id]);
    $result = $q->getResult();

    if (count($result) === 0) {
        // Такого семестра нет, добавляем
        $period = new Entities\Period();
        $period->setGroup($group);
        $period->setOrdNumber($this_group_in_a_row);
        $period->setAversId($avers_id);
        $em->persist($period);
    }
    $current_line_num++;
}
$em->flush();