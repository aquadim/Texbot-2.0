<?php
// Использование:
// php seed.php <год поступления первого курса> <путь до файла с id семестров>

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;
use BotKit\Enums\FunctionNames;

$em = Database::getEM();

// Функции
$function_names = array_column(FunctionNames::cases(), 'value');
foreach ($function_names as $fname) {
    $fn = new Entities\TexbotFunction($fname);
    $em->persist($fn);
}

// Платформы
$vk_platform = new Entities\Platform("vk.com");
$em->persist($vk_platform);
$tg_platform = new Entities\Platform("telegram.org");
$em->persist($tg_platform);

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

        // hack: в 2024 г. были набраны две группы ОС. Поэтому они названы
        // ОС-1 и ОС-2.
        if ($i == 1 && $spec_name == 'ОС') {
            for ($j = 1; $j < 3; $j++) {
                $hackspec = new Entities\CollegeSpec();
                $hackspec->setName('ОС-'.$j);
                $em->persist($hackspec);

                $group = new Entities\CollegeGroup();
                $group->setSpec($hackspec);
                $group->setEnrolledAt(2024);
                $group->setCourseNum(1);

                $em->persist($group);
            }
            continue;
        }
        
        $group = new Entities\CollegeGroup();
        $group->setSpec($spec);
        $group->setCourseNum($i);
        $group->setEnrolledAt($first_course_year - $i + 1);
        $em->persist($group);
    }
}
$em->flush();

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

// Преподаватели
$teachers = array(
    array('patron' => 'Германовна', 'name' => 'Ольга', 'surname' => 'Александрова'),
    array('patron' => 'Владимировна', 'name' => 'Елена', 'surname' => 'Антоненко'),
    array('patron' => 'Александрович', 'name' => 'Иван', 'surname' => 'Бегунов'),
    array('patron' => 'Сергеевич', 'name' => 'Александр', 'surname' => 'Бондин'),
    array('patron' => 'Леонидович', 'name' => 'Андрей', 'surname' => 'Воронин'),
    array('patron' => 'Валерьевна', 'name' => 'Екатерина', 'surname' => 'Галимова'),
    array('patron' => 'Альферовна', 'name' => 'Алмазия', 'surname' => 'Гарифова'),
    array('patron' => 'Валентиновна', 'name' => 'Любовь', 'surname' => 'Дербышева'),
    array('patron' => 'Николаевна', 'name' => 'Юлия', 'surname' => 'Еремеева'),
    array('patron' => 'Сергеевна', 'name' => 'Наталья', 'surname' => 'Игнатьева'),
    array('patron' => 'Анатольевна', 'name' => 'Светлана', 'surname' => 'Ильина'),
    array('patron' => 'Загидович', 'name' => 'Равиль', 'surname' => 'Исаков'),
    array('patron' => 'Юрьевна', 'name' => 'Марина', 'surname' => 'Коралихина'),
    array('patron' => 'Евгеньевна', 'name' => 'Елена', 'surname' => 'Логинова'),
    array('patron' => 'Сергеевич', 'name' => 'Александр', 'surname' => 'Маскин'),
    array('patron' => 'Георгиевна', 'name' => 'Людмила', 'surname' => 'Матвеева'),
    array('patron' => 'Евгеньевич', 'name' => 'Михаил', 'surname' => 'Медведев'),
    array('patron' => 'Альбертовна', 'name' => 'Альбина', 'surname' => 'Медянцева'),
    array('patron' => 'Рифовна', 'name' => 'Римма', 'surname' => 'Мингалеева'),
    array('patron' => 'Александровна', 'name' => 'Елена', 'surname' => 'Немтинова'),
    array('patron' => 'Нургаянович', 'name' => 'Нурислам', 'surname' => 'Нигаматзянов'),
    array('patron' => 'Аркадьевна', 'name' => 'Елена', 'surname' => 'Новикова'),
    array('patron' => 'Леонидовна', 'name' => 'Ольга', 'surname' => 'Овчинникова'),
    array('patron' => 'Александрович', 'name' => 'Сергей', 'surname' => 'Пивоваров'),
    array('patron' => 'Анатольевна', 'name' => 'Елена', 'surname' => 'Пономарева'),
    array('patron' => 'Геннадьевна', 'name' => 'Кристина', 'surname' => 'Поткина'),
    array('patron' => 'Валерьевна', 'name' => 'Алевтина', 'surname' => 'Пупкова'),
    array('patron' => 'Анатольевич', 'name' => 'Александр', 'surname' => 'Пушкарев'),
    array('patron' => 'Станиславовна', 'name' => 'Вера', 'surname' => 'Солоницына'),
    array('patron' => 'Викторовна', 'name' => 'Жанна', 'surname' => 'Усова'),
    array('patron' => 'Гаптельнуровна', 'name' => 'Гюзелия', 'surname' => 'Хайрутдинова'),
    array('patron' => 'Равилевич', 'name' => 'Марсиль', 'surname' => 'Хуснутдинов'),
    array('patron' => 'Ильсурович', 'name' => 'Рамиль', 'surname' => 'Шамсумухаметов'),
    array('patron' => 'Викторовна', 'name' => 'Елена', 'surname' => 'Шафикова'),
    array('patron' => 'Александрович', 'name' => 'Сергей', 'surname' => 'Шешегов'),
    array('patron' => 'Владимировна', 'name' => 'Наталья', 'surname' => 'Шешегова'),

    // 2024
    array('patron' => 'Рафисовна', 'name' => 'Наиля', 'surname' => 'Хантимирова'),
    array('patron' => 'Сергеевна', 'name' => 'Юлия', 'surname' => 'Зезюлина'),
    array('patron' => 'Владиславовна', 'name' => 'Юлия', 'surname' => 'Тимофеева'),
    array('patron' => 'Александровна', 'name' => 'Анастасия', 'surname' => 'Суворова')
);
foreach ($teachers as $e) {
    $e_obj = new Entities\Employee();
    $e_obj->setSurname($e['surname']);
    $e_obj->setName($e['name']);
    $e_obj->setPatronymic($e['patron']);
    $em->persist($e_obj);
}

$em->flush();
echo "Старт базы данных проведён успешно!\n";
