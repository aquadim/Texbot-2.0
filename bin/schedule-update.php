<?php
// Скрипт для парсинга расписания
// Спасибо за помощь (и код):
// https://stackoverflow.com/questions/63249647/how-to-read-table-cell-content-via-phpword-library
// https://stackoverflow.com/questions/50994146/read-ms-word-document-with-php-word
// Использование:
/* php schedule-update.php <файл расписания> 
 * <нужно ли делать проверку актуальности (1/0)> */

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;
use function Texbot\adminNotify;

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
	return $runtext;
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
	$dates[] = mktime(
		$hour = 0,
		$minute = 0,
		$second = 0,
		$month = $month_id + 1,
		$day = $words[$month_word_index - 1],
	);
}

info("===Парсинг таблиц===");

// Парсить нерелевантные?
if ($argv[2] === '0') {
    $parse_irrelevant = true;
} else {
    $parse_irrelevant = false;
}

if (count($dates) != count($tables)) {
	warning("Предупреждение: количество дат не совпадает с количеством таблиц");
}
$counter = 0;
$date_relevancy = time() + 172800; // После какой временной отметки расписание не актуально? (текущее время + 2 дня)

$em = Database::getEm();

$dql_find_group = 
'SELECT g FROM '.Entities\CollegeGroup::class.' g '.
'JOIN g.spec s '.
'WHERE g.course_num=:courseNum AND s.name=:specName';

foreach($dates as $date) {
	// Проверяем актуальность даты
	if ($date < $date_relevancy && $parse_irrelevant === false) {
        warning(date("Y-m-d", $date).' пропускается - т.к. дата не актуальна');
		continue;
	}
    
    // День этого расписания
    $schedule_day = (new \DateTimeImmutable())->setTimestamp($date);
    
    // Поиск существующих расписаний. Если они найдены, удаляем!
    $existing = $em
    ->getRepository(Entities\Schedule::class)
    ->findBy([
        'day' => $schedule_day
    ]);
    foreach ($existing as $s) {
        $em->remove($s);
    }
    $em->flush();

	// Дата актуальна. Парсим таблицу, связанную с этой датой
	info("Выполняется парсинг таблицы для временной метки: ".date("Y-m-d", $date));
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
            
            // Поиск группы в БД
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
                "\nДата расписания: ".date("Y-m-d", $date)
                );
				exit();
            }
            $group = $result[0];
            info("Сбор данных для группы ".$group->getHumanName());

            // Создание записи расписания
            $schedule = new Entities\Schedule();
            $schedule->setCollegeGroup($group);
            $schedule->setDay($schedule_day);
            $em->persist($schedule);

            // Парсинг пар группы по столбцу до конца таблицы
            $group_y = $y + 1;
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
                    
                // Разбор времени пары
                $pair_parts = explode('.', $time);
                $pair_time = $schedule_day->setTime(
                    (int)$pair_parts[0],
                    (int)$pair_parts[1]
                );
                
                // Поиск/создание названия пары
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
                
                // Создание записи пары
                $pair = new Entities\Pair();
                $pair->setSchedule($schedule);
                $pair->setTime($pair_time);
                $pair->setPairName($pair_name_obj);
                $em->persist($pair);
                
                // Разбор деталей проведения
                $details = explode('/', $teacher_data);
                foreach ($details as $detail) {
                    // Формат: "фамилия преподавателя" "место проведения"
                    // Либо: "фамилия преподавателя"
                    $parts = explode(" ", $detail);
                    
                    $employee = $em
                        ->getRepository(Entities\Employee::class)
                        ->findOneBy(['surname' => $parts[0]]);
                    if ($employee === null) {
                        err("Преподаватель {$parts[0]} не найден!");
                        adminNotify(
                        "Преподаватель {$parts[0]} не найден, обновление расписания завершено немедленно\\.\n".
                        "Примите меры <b>немедленно</b>");
                        exit();
                    }
                    
                    if (count($parts) === 1) {
                        // Есть только фамилия
                        $place = null;
                    } else {
                        $place = $em
                            ->getRepository(Entities\Place::class)
                            ->findOneBy(['name' => $parts[1]]);
                        
                        if ($place === null) {
                            // Места нет, создаём
                            $place = new Entities\Place();
                            $place->setName($parts[1]);
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
		}
	}
	$counter++;
}
$em->flush();
#endregion