<?php
// Скрипт для
// 1. Парсинга и обновления расписания
// 2. Инвалидации кэша изображений расписаний
// 3. Оповещения об изменении в расписании
// Спасибо за помощь (и код):
// https://stackoverflow.com/questions/63249647/how-to-read-table-cell-content-via-phpword-library
// https://stackoverflow.com/questions/50994146/read-ms-word-document-with-php-word
// Использование:
/* php schedule-update.php <файл расписания> 
 * <нужно ли делать проверку актуальности (--parse-irrelevant/--no-parse-irrelevant)> */

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;
use BotKit\Enums\ImageCacheType;
use function Texbot\adminNotify;
use function Texbot\getPairsChangedText;
use function Texbot\notifyGroup;

function info($text) {
    echo $text."\n";
}

function success($text) {
    echo "\033[92m".$text."\033[0m\n";
}

function warning($text) {
    echo "\033[93m".$text."\033[0m\n";
}

function err($text) {
    echo "\033[91m".$text."\033[0m\n";
}

function stopWithError($text) {
    err($text);
    adminNotify("[schedule-update.php]: ".$text);
    exit();
}

// Возвращает текст из run элемента
function getTextFromRun($element) {
    $children = $element->getElements();
    $runtext = "";
    foreach ($children as $child) {
        if (method_exists($child, 'getText')) {
            $runtext .= $child->getText();
        } else if (method_exists($child, 'getContent')) {
            $runtext .= $child->getContent();
        }
    }

    // Иногда в таблице встречается такое, что латинские буквы выдаются за
    // русские. Типа С, А, Е. В таком случае заменяем так чтобы было всё на
    // русском.

    // Массив перевода. Слева английский, справа русский
    $transliteration = [
        'A' => 'А',
        'E' => 'Е',
        'K' => 'К',
        'M' => 'М',
        'H' => 'Н',
        'O' => 'О',
        'P' => 'Р',
        'C' => 'С',
        'T' => 'Т',
        'Y' => 'У',
        'X' => 'Х',
        'a' => 'а',
        'b' => 'б',
        'e' => 'е',
        'o' => 'о',
        'p' => 'р',
        'c' => 'с',
        'y' => 'у',
        'x' => 'х'
    ];

    return strtr($runtext, $transliteration);
}

// Возвращает true если данная $string - название группы
// Строка - название группы, если первый символ - число, а число слов - именно два
function isGroupName($string) {
    $parts = explode(" ", $string);
    if (count($parts) != 2) {
        return false;
    }
    if (!is_numeric($parts[0])) {
        return false;
    }
    return true;
}

// В случае, если в БД не обнаружен преподаватель, ищем во всех фамилиях
// преподавателей наиболее похожую строку с помощью расстояния Левенштейна
// Если найдено несколько подходящих преподавателей, скрипт завершается ошибкой
function findClosestTeacher(
    $surname,
    $all_employees
) : Entities\Employee | false
{
    // Расстояние, выше которого строки даже не проверяем
    $threshold = 4;

    // Расстояние: совпадение
    $distances = [];
    for ($i = 1; $i <= $threshold; $i++) {
        $distances[$i] = [];
    }

    foreach ($all_employees as $employee) {
        $char_map = array();
        $s1 = utf8_to_extended_ascii($employee->getSurname(), $char_map);
        $s2 = utf8_to_extended_ascii($surname, $char_map);
        $distance = levenshtein($s1, $s2);

        if ($distance > $threshold) {
            continue;
        }

        $distances[$distance][] = $employee;
    }

    // Ищем совпадения с самых похожих строк до менее похожих
    for ($i = 1; $i <= $threshold; $i++) {
        $captured = count($distances[$i]);

        if ($captured == 0) {
            // Нет совпадений с таким расстоянием
            continue;
        }

        if ($captured == 1) {
            // Результат - самый похожий и единственный
            return $distances[$i][0];
        }

        // Несколько результатов с очень похожим расстоянием
        $error_message =
        "Не удаётся установить личность: ".$surname."\n".
        "Возможные значения:\n";
        foreach ($distances[$i] as $e) {
            $error_message .= "- ".$e->getNameWithInitials()."\n";
        }
        stopWithError($error_message);
    }

    // Ничего не нашли
    return false;
}

// Разбирает данные деталей проведения пары. Возвращает в формате
// [['Фамилия препода', 'Место проведения'], [...]]
// И Фамилия и место могут быть null.
// $celltext - текст ячейки
function handleConductionData($celltext) {
    $celltext = trim($celltext);
    
    // Крайние случаи
    if ($celltext === 'спорт зал') {
        return [[null, 'спорт зал']];
    }

    $details = explode('/', $celltext);
    $output = [];

    foreach ($details as $detail) {
        // Формат: "фамилия преподавателя" "место проведения"
        // Либо: "фамилия преподавателя"
        $parts = explode(" ", trim($detail));

        if (count($parts) === 1) {
            // Есть только место
            $teacher = null;
            $place = trim($parts[0]);
        } else {
            $teacher = trim($parts[0]);
            $place = trim($parts[1]);
        }

        $output[] = [$teacher, $place];
    }

    return $output;
}

// Парсить неактуальные даты?
if ($argv[2] === '--parse-irrelevant') {
    $parse_irrelevant = true;
} else if ($argv[2] === '--no-parse-irrelevant') {
    $parse_irrelevant = false;
} else {
    err('Второй аргумент не распознан. Допустимые значения: --parse-irrelevant, --no-parse-irrelevant');
    exit();
}

// https://www.php.net/manual/en/function.levenshtein.php#113702
function utf8_to_extended_ascii($str, &$map) {
    // find all multibyte characters (cf. utf-8 encoding specs)
    $matches = array();
    if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches))
        return $str; // plain ascii string
    
    // update the encoding map with the characters not already met
    foreach ($matches[0] as $mbc)
        if (!isset($map[$mbc]))
            $map[$mbc] = chr(128 + count($map));
    
    // finally remap non-ascii characters
    return strtr($str, $map);
}

#region Считывание информации
// Загрузка файла расписания
info('Загрузка файла расписания...');
$contents = file_get_contents($argv[1]);
file_put_contents('/tmp/schedule.doc', $contents);

info('Преобразование в docx..');
exec('unoconv -d document --format=docx /tmp/schedule.doc');
$phpWord = \PhpOffice\PhpWord\IOFactory::load('/tmp/schedule.docx');

// Считывание всей информации документа
$tables = array();
$textruns = array();

info("===Cбор информации документа===");
// Проходимся по всем секциям и по всем элементам секций в документе
foreach ($phpWord->getSections() as $section) {
    foreach ($section->getElements() as $element) {
        switch (get_class($element)) {

        // Если элемент - таблица, то добавляем её в массив чтобы обработать её позже
        case "PhpOffice\PhpWord\Element\Table":
            info("Таблица");
            $tables[] = $element;
            break;

        // Если элемент - текстоподобный, то считываем весь его текст и добавляем в массив текстов
        // Позже этот массив будет обработан
        case "PhpOffice\PhpWord\Element\ListItemRun":
        case "PhpOffice\PhpWord\Element\TextRun":
            $runtext = getTextFromRun($element);

            if (strlen($runtext) > 0) {
                info("Текстовый элемент ($runtext)");
                $textruns[] = $runtext;
            } else {
                // Пустые строки незачем добавлять в textruns
                warning("Пустой текст");
            }
            break;

        // Элемент неизвестен, он просто не будет обработан
        default:
            warning("Неопознанный элемент: ".get_class($element));
            break;
        }
    }
}
#endregion

#region Парсинг данных
/* Поиск дат расписаний. Так как общий формат названия таблиц следует такому шаблону
 * РАСПИСАНИЕ ЗАНЯТИЙ на <день недели> <число с 0 впереди если оно меньше 10> <месяц в родительном падеже>
 * то элемент должен проходить проверку следующих условий для того чтобы считаться датой расписания
 *
 * 1. Текст должен иметь не менее 6 слов (слово - последовательность символов, ограниченная пробелами)
 * 2. Первые два слова - расписание занятий (в любом регистре)
 * 3. В тексте должен присутствовать месяц в родительном падеже (октября, ноября, декабря, ...)
 * 4. Перед месяцем должно присутствовать слово. Это слово обязано содержать только цифры т.к. это число месяца
 * 5. В тексте должно присутствовать название какого-либо дня недели. */
info("===Проверка дат расписаний===");
$dates = array();
$month_names = array("января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");
$weekday_names = array("понедельник", "вторник", "среду", "четверг", "пятницу", "субботу", "воскресенье");

foreach ($textruns as $text) {
    $words = explode(" ", $text);

    // Проверка условия #1
    if (count($words) < 6) {
        warning("$text не дата расписания - количество слов меньше 6");
        continue;
    }

    // Проверка условия #2  
    if (mb_strtolower($words[0]) != "расписание" || mb_strtolower($words[1]) != "занятий") {
        err("$text не дата расписания - первые два слова не 'расписание занятий'");
        continue;
    }

    // Проверка условия #3
    $month_id = 0;
    $found_month = false;
    foreach ($month_names as $name) {
        if (str_contains($text, $name)) {
            $found_month = true;
            break;
        }
        $month_id++;
    }
    if (!$found_month) {
        err("$text не дата расписания - не обнаружено месяца");
        continue;
    }

    // Проверка условия #4
    $month_word_index = array_search($month_names[$month_id], $words);
    if (!is_numeric($words[$month_word_index - 1])) {
        err("$text не дата расписания - число месяца не обнаружено");
        continue;
    }

    // Проверка условия #5
    $found_weekday = false;
    foreach ($weekday_names as $wd) {
        if (in_array($wd, $words)) {
            $found_weekday = true;
            break;
        }
    }
    if (!$found_weekday) {
        err("$text не дата расписания - не обнаружен день недели");
        continue;
    }

    success("$text - дата расписания");

    // Все условия пройдены, continue не вызывался, а значит эта строка - дата расписания!
    // На основании предыдущих данных определяем дату в формате дд-мм-гггг
    // Как год берётся текущий год на сервере
    // https://stackoverflow.com/a/1699980
    $dates[] = DateTimeImmutable::createFromFormat(
        'm-d',
        ($month_id+1).'-'.($words[$month_word_index - 1])
    );
}

info("===Парсинг таблиц===");

if (count($dates) != count($tables)) {
    warning("Предупреждение: количество дат не совпадает с количеством таблиц");
}
$counter = 0;

// После какой временной отметки расписание не актуально? (текущее время + 4 дня)
$now = new DateTimeImmutable();
$date_relevancy = $now->add(new DateInterval("P4D"));

$em = Database::getEm();

$dql_find_group = 
'SELECT g FROM '.Entities\CollegeGroup::class.' g '.
'JOIN g.spec s '.
'WHERE g.course_num=:courseNum AND s.name=:specName';

// Запрос актуального расписания для группы на дату
$dql_find_latest_schedule =
'SELECT s FROM '.Entities\Schedule::class.' s '.
'WHERE s.college_group=:collegeGroup AND s.day=:day '.
'ORDER BY s.created_at DESC';

// Запрос пар расписания
$dql_get_pairs_of_schedule =
'SELECT p FROM '.Entities\Pair::class.' p '.
'WHERE p.schedule=:schedule';

// Получение всех сотрудников
$dql_all_employees  = 'SELECT e FROM '.Entities\Employee::class.' e ';
$q                  = $em->createQuery($dql_all_employees);
$all_employees      = $q->getResult();

foreach($dates as $date) {
    // Отформатированная дата
    $date_text = $date->format("Y-m-d");

    // Проверяем актуальность даты
    // Должно быть позже чем сейчас, но раньше чем через 4 дня
    if (($date > $date_relevancy || $date < $now) && $parse_irrelevant==false) {
        warning($date_text.' пропускается - т.к. дата не актуальна');
        $counter++;
        continue;
    }

    info("Выполняется парсинг даты ".$date_text);
    
    // День этого расписания
    $schedule_day = $date->setTime(0, 0, 0);

    // Дата актуальна. Парсим таблицу, связанную с этой датой
    info("Выполняется парсинг таблицы для даты: ".$date_text);
    $table = $tables[$counter]; // Объект таблицы из документа
    $data = array(); // Двумерный массив, содержащий в себе данные таблицы

    $rows = $table->getRows();
    foreach ($rows as $row) {
        $datarow = array();
        $cells = $row->getCells();
        foreach ($cells as $cell) {
            $celltext = '';
            foreach ($cell->getElements() as $element) {
                $celltext .= getTextFromRun($element);
            }
            $datarow[] = trim($celltext, "\xC2\xA0\n ");
        }
        $data[] = $datarow;
    }

    // Настоящий парсинг таблицы
    $dataheight = count($data);
    $datawidth = count($data[0]);
    
    /* Проверка каждой строки таблицы. Если в строке обнаружено 
     * название группы, то для каждой группы выполняется сбор пар */
    for ($y = 0; $y < $dataheight; $y++) {
    
        $row_contains_group_name = false;
        for ($x = 0; $x < $datawidth; $x++) {
            if (isGroupName($data[$y][$x])) {
                $row_contains_group_name = true;
                break;
            }
        }
    
        if (!$row_contains_group_name) {
            // В строке не обнаружены названия групп. Пропускаем строку
            continue;
        }
    
        // Циклом проходимся по всем названиям групп в этой строке.
        for ($x = 0; $x < $datawidth; $x++) {
            
            if (!isGroupName($data[$y][$x])) {
                // Это не название группы, пропускаем столбец
                continue;
            }
            
            $group_parts = explode(" ", $data[$y][$x]);
            $group_course = $group_parts[0];
            $group_spec = $group_parts[1];
            
            // -- Поиск группы в БД --
            $q = $em->createQuery($dql_find_group);
            $q->setParameters([
                'courseNum'=> $group_course,
                'specName' => $group_spec
            ]);
            $result = $q->getResult();

            if (count($result) == 0) {
                // В БД такой группы нет
                err("Неопознанная группа: ".$data[$y][$x]);
                adminNotify(
                    "Неопознанная группа во время парсинга расписаний: ".
                    $data[$y][$x].
                    "\nДата расписания: ".$date_text
                );
                exit();
            }
            $group = $result[0];
            info("Сбор данных для группы ".$group->getHumanName());

            // -- Получение самого актуального расписания на этот момент --
            $check_difference = true; // нужна ли проверка отличий?
            $q = $em->createQuery($dql_find_latest_schedule);
            $q->setParameters([
                'collegeGroup' => $group,
                'day' => $schedule_day
            ]);
            $q->setMaxResults(1);
            $r = $q->getResult();
            if (count($r) == 0) {
                // Нет предыдущих версий, сверять не нужно
                $check_difference = false;
            } else {
                $q_old_pairs = $em->createQuery($dql_get_pairs_of_schedule);
                $q_old_pairs->setParameters([
                    'schedule' => $r[0]
                ]);
                $old_pairs = $q_old_pairs->getResult();
            }
            
            // Создание записи расписания
            $schedule = new Entities\Schedule();
            $schedule->setCollegeGroup($group);
            $schedule->setDay($schedule_day);
            $schedule->setCreatedAt($now);
            $em->persist($schedule);

            // Парсинг пар группы по столбцу до конца таблицы
            $group_y = $y + 1;
            $new_pairs = [];

            while ($group_y < $dataheight) {
            
                if (count($data[$group_y]) < 14) {
                    // Скорее всего на этой строке пары заканчиваются
                    break;
                }
            
                $time = $data[$group_y][$x * 2];
                if (strlen($time) < 2) {
                    // В столбце времени ничего полезного, пропускаем
                    // эту строку
                    $group_y += 2;
                    continue;
                }

                $pair_name = $data[$group_y][$x * 2 + 1];
                if (strlen($pair_name) < 3) {
                    // В столбце пары ничего полезного, пропускаем
                    // эту строку
                    $group_y += 2;
                    continue;
                }
            
                $teacher_data = $data[$group_y + 1][$x * 2 + 1];
                
                // -- Разбор времени пары --
                $pair_parts = explode('.', $time);
                $pair_time = $schedule_day->setTime(
                    (int)$pair_parts[0],
                    (int)$pair_parts[1]
                );
            
                // -- Поиск/создание названия пары --
                $pair_name_obj = $em
                    ->getRepository(Entities\PairName::class)
                    ->findOneBy(['name' => $pair_name]);
                if ($pair_name_obj === null) {
                    // Такого названия пары в БД нет. Создаём!
                    $pair_name_obj = new Entities\PairName();
                    $pair_name_obj->setName($pair_name);
                    $em->persist($pair_name_obj);
                    $em->flush();
                }
            
                // -- Создание записи пары --
                $pair = new Entities\Pair();
                $pair->setSchedule($schedule);
                $pair->setTime($pair_time);
                $pair->setPairName($pair_name_obj);
                $new_pairs[] = $pair;
                $em->persist($pair);
            
                // -- Разбор деталей проведения --
                $conduction_details = handleConductionData($teacher_data);
                $employee_repo = $em->getRepository(Entities\Employee::class);
                $place_repo = $em->getRepository(Entities\Place::class);
                foreach ($conduction_details as $detail) {

                    if ($detail[0] === null) {
                        $employee = null;
                    } else {
                        $employee = $employee_repo->findOneBy(
                            ['surname' => $detail[0]]
                        );

                        // Если после поиска препода мы его не нашли то
                        // считаем что произошла очепятка, пытаемся найти
                        // ближайшее совпадение
                        if ($employee === null) {
                            $employee = findClosestTeacher(
                                $detail[0],
                                $all_employees
                            );

                            if ($employee === false) {
                                stopWithError(
                                    "Преподаватель не опознан из строки: $teacher_data"
                                );
                            }
                            
                            warning("Считаю что ".$detail[0]." - это ".$employee->getNameWithInitials());
                        }
                    }

                    if ($detail[1] === null) {
                        $place = null;
                    } else {
                        $place = $place_repo->findOneBy(['name' => $detail[1]]);
                            
                        if ($place === null) {
                            // Места нет, создаём
                            $place = new Entities\Place();
                            $place->setName($detail[1]);
                            $em->persist($place);
                            $em->flush();
                        }
                    }

                    $conduction_detail = new Entities\PairConductionDetail();
                    $conduction_detail->setEmployee($employee);
                    $conduction_detail->setPlace($place);
                    $conduction_detail->setPair($pair);
                    $em->persist($conduction_detail);
                }
            
                // На одну пару приходится две строки
                $group_y += 2;
            }

            // На этом этапе все пары расписания были записаны
            // Сверяем количество, наименования и время пар нового с последней
            // версией расписания
            if ($check_difference) {
                info("Выполняется проверка различности пар");
                
                // -- Проверка есть ли изменения --
                $amount_increased   = false;
                $amount_decreased   = false;
                $time_changed       = false;
                $discipline_changed = false;

                // Если больше пар
                if (count($new_pairs) > count($old_pairs)) {
                    $amount_increased = true;
                }

                // Если меньше пар
                if (count($new_pairs) < count($old_pairs)) {
                    $amount_decreased = true;
                }

                if (count($new_pairs) == count($old_pairs)) {
                    for ($i = 0; $i < count($new_pairs); $i++) {
                        $old = $old_pairs[$i];
                        $new = $new_pairs[$i];

                        // Если время не совпадает
                        if ($old->getTime() != $new->getTime()) {
                            $time_changed = true;
                        }

                        // Если дисциплина не совпадает
                        if ($old->getPairName() != $new->getPairName()) {
                            $discipline_changed = true;
                        }
                    }
                }

                // -- Сборка сообщения --
                $items = [];
                if ($amount_increased) {
                    $items[] = "⬆️ Количество пар увеличилось";
                }
                if ($amount_decreased) {
                    $items[] = "⬇️ Количество пар уменьшилось";
                }
                if ($time_changed) {
                    $items[] = "🕔 Время пар изменилось";
                }
                if ($discipline_changed) {
                    $items[] = "📚 Дисциплины пар изменились";
                }

                if (count($items) > 0) {
                    info("Обнаружены различия между последним и текущим расписанием");
                    $message = getPairsChangedText($items);
                    notifyGroup($message);
                } else {
                    info("Различия не выявлены");
                }
            } else {
                info("Проверка различности пар пропущена");
            }
        }
    }
    $counter++;
}

// Инвалидация кэша изображений
// ограничения в 0 и 4 - все типы кэша, связанные с расписанием.
// См. botkit/Entities/ImageCache.php
$dql_invalidate_cache =
"
UPDATE ".Entities\ImageCache::class." c
SET c.valid=0
WHERE c.cache_type > 0 AND c.cache_type < 4
";
$query = $em->createQuery($dql_invalidate_cache);
$query->execute();

$em->flush();
#endregion
