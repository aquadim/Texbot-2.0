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

/* Примеры:
 * php schedule-update.php https://www.vpmt.ru/docs/rasp2.doc --parse-irrelevant
 * php schedule-update.php https://www.vpmt.ru/docs/rasp2.doc --no-parse-irrelevant
*/

require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities;
use BotKit\Enums\ImageCacheType;
use BotKit\Enums\CallbackType;
use function Texbot\adminNotify;
use function Texbot\getPairsChangedText;
use function Texbot\notifyGroup;
use Texbot\NotificationService;
use DateTimeImmutable as DT;

// Дата вместе с таблицей
class TableInfo {
    private DT $date;
    private $table;
    private array $matrix;
    private bool $is_relevant;

    public function __construct(DT $date, $table, bool $relevancy) {
        $this->date = $date->setTime(0,0,0);
        $this->table = $table;
        $matrix = [];
        $this->is_relevant = $relevancy;
    }

    // Останавливает парсинг и оповещает об ошибке
    // $what - что произошло
    // $expected - что ожидалось
    // $date - дата расписания в котором произошла ошибка
    private function stop(string $what, string $expected) {
        $lines = [];

        $lines[] = "При сборе данных расписания произошла ошибка";
        $lines[] = $what;
        $lines[] = "Ожидалось: " . $expected;
        $lines[] = "Дата расписания в котором произошла ошибка: " . $this->humanDate();

        $text = implode(".\n", $lines);
    
        err($text);
        adminNotify("[schedule-update.php]: " . $text);
        exit();
    }

    // Разбирает данные деталей проведения пары. Возвращает в формате
    // [['Фамилия препода', 'Место проведения'], [...]]
    // И Фамилия и место могут быть null.
    // $celltext - текст ячейки
    private function handleConductionData($celltext) {
        $celltext = trim($celltext);
    
        // Крайние случаи
        if ($celltext === 'спорт зал') {
            return [[null, 'спорт зал']];
        }

        $details = explode('/', $celltext);
        $output = [];

        foreach ($details as $detail) {
            // Формат: "фамилия преподавателя" "место проведения"
            // Либо: "место"
            $parts = explode(" ", trim($detail));

            if (count($parts) === 1) {
                // Есть только место(?)
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

    public function humanDate() : string {
        return $this->date->format("Y-m-d");
    }

    // Проверяет актуальность даты
    public function isRelevant() : bool {
        return $this->is_relevant;
    }

    // Преобразовывает таблицу в массив массивов строк
    private function getMatrix() : array {
        $output = array();

        $rows = $this->table->getRows();
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
            $output[] = $datarow;
        }

        return $output;
    }

    // Возвращает true если данная $text - название группы
    // Строка - название группы, если первый символ - число, а число слов - именно два
    private function isGroupName(string $text) : bool {
        $parts = explode(" ", $text);
        if (count($parts) != 2) {
            return false;
        }
        if (!is_numeric($parts[0])) {
            return false;
        }
        return true;
    }

    // Парсит строку с названиями групп
    private function parseGroupRow($row) : array {
        $output = [];
        foreach ($row as $cell) {
            if (!$this->isGroupName($cell)) {
                // Это не название группы, пропускаем столбец
                continue;
            }
            
            $group_parts    = explode(" ", $cell);
            $group_course   = intval($group_parts[0]);
            $group_spec     = $group_parts[1];
            
            // Поиск группы в БД
            $em = Database::getEm();
            $cg_repo = $em->getRepository(Entities\CollegeGroup::class);
            $group = $cg_repo->getByHumanParts($group_course, $group_spec);

            if ($group === null) {
                $this->stop(
                    "Группа не опознана: " . $cell,
                    "группа в формате <НОМЕР_КУРСА СПЕЦИАЛЬНОСТЬ_БЕЗ_ПРОБЕЛОВ>"
                );
            }

            $output[] = $group;
        }

        return $output;
    }

    // https://www.php.net/manual/en/function.levenshtein.php#113702
    private function  utf8_to_extended_ascii($str, &$map) {
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

    // В случае, если в БД не обнаружен преподаватель, ищем во всех фамилиях
    // преподавателей наиболее похожую строку с помощью расстояния Левенштейна
    // Если найдено несколько подходящих преподавателей, скрипт завершается ошибкой
    private function findClosestTeacher(
        $surname
    ) : Entities\Employee | false
    {
        $em = Database::getEm();
        $emp_repo = $em->getRepository(Entities\Employee::class);
        $all_employees = $emp_repo->findAll();
        
        // Расстояние, выше которого строки даже не проверяем
        $threshold = 4;

        // расстояние: совпадение
        $distances = [];
        for ($i = 1; $i <= $threshold; $i++) {
            $distances[$i] = [];
        }

        foreach ($all_employees as $employee) {
            $char_map = array();
            $s1 = $this->utf8_to_extended_ascii($employee->getSurname(), $char_map);
            $s2 = $this->utf8_to_extended_ascii($surname, $char_map);
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

            $possible = [];
            foreach ($distances[$i] as $e) {
                $possible[] = "- " . $e->getNameWithInitials();
            }
            $error_message .= implode(";\n", $possible);
            $this->stop($error_message, "одна фамилия преподавателя");
        }

        // Ничего не нашли
        return false;
    }

    // Парсит строку с парами
    private function parsePairsRow($row1, $row2, $groups, $group_schedules) {
        $x = 0;
        foreach ($groups as $g) {
            $time                   = $row1[$x * 2];     // Время пары
            $pair_name              = $row1[$x * 2 + 1]; // Имя пары
            $conduction_details_raw = $row2[$x * 2 + 1]; // Детали проведения

            #region Проверки и валидации
            if (mb_strlen($time) < 2 || mb_strlen($pair_name) < 3) {
                // В нужных ячейках ничего полезного, пропускаем эту строку
                $x++;
                continue;
            }
            $time_parts = explode('.', $time);
            if (count($time_parts) != 2) {
                // Время не в формате 12:36?
                $this->stop(
                    "Неверный формат времени пары: $time",
                    "время в формате <чч.мм>"
                );
            }
            #endregion

            #region Создание необходимых переменных
            $em = Database::getEm();
            // Репозитории сущностей
            $employee_repo = $em->getRepository(Entities\Employee::class);
            $place_repo = $em->getRepository(Entities\Place::class);
            $pn_repo = $em->getRepository(Entities\PairName::class);
            
            // Время пары
            $pair_time = $this->date->setTime(
                (int)$time_parts[0],
                (int)$time_parts[1],
                0
            );

            // Детали проведения
            $conduction_details = $this->handleConductionData($conduction_details_raw);

            // Название пары
            $pair_name_obj = $pn_repo->findOneBy(['name' => $pair_name]);
            if ($pair_name_obj === null) {
                // Такого названия пары в БД нет. Создаём
                $pair_name_obj = new Entities\PairName();
                $pair_name_obj->setName($pair_name);
                $em->persist($pair_name_obj);
                // $em->flush();
            }
            #endregion

            #region Создание объектов сущностей
            $pair = new Entities\Pair();
            $pair->setSchedule($group_schedules[$g->getId()]);
            $pair->setTime($pair_time);
            $pair->setPairName($pair_name_obj);
            $em->persist($pair);

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
                        $employee = $this->findClosestTeacher($detail[0]);

                        if ($employee === false) {
                            $this->stop(
                                "Преподаватель не опознан из строки: $conduction_details_raw",
                                "фамилия преподавателя в строке"
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
                        // $em->flush();
                    }
                }

                $conduction_detail = new Entities\PairConductionDetail();
                $conduction_detail->setEmployee($employee);
                $conduction_detail->setPlace($place);
                $conduction_detail->setPair($pair);
                $em->persist($conduction_detail);
            }
            #endregion

            $x++;
        }

        success("Собрана строка пар");
    }

    // Возвращает true если в строке есть имена групп
    private function rowContainsGroupName(array $row) : bool {
        foreach ($row as $cell) {
            if ($this->isGroupName($cell)) {
                return true;
            }
        }
        return false;
    }

    // Возвращает информацию о том нужно ли проверять различия пар
    public function checkDiff() : array {
        $now = new DT();
        $output = [];
        $tomorrow = $now->add(new DateInterval("P1D"))->setTime(0, 0, 0);

        // Нужно проверять только расписание на завтра
        if ($tomorrow != $this->date) {
            info("Проверка различий не нужна");
            return $output;
        }

        $em = Database::getEm();
        $gr_repo = $em->getRepository(Entities\CollegeGroup::class);
        $pair_repo = $em->getRepository(Entities\Pair::class);

        // Выбираем все группы которые есть
        $all_groups = $gr_repo->findAll();
        foreach ($all_groups as $g) {
            // -- Выбираем два самых новых расписания --
            $dql =
            "SELECT s FROM " . Entities\Schedule::class . " s " .
            "WHERE s.college_group=:collegeGroup " .
            "AND s.day=:day " .
            "ORDER BY s.created_at DESC";
            $q = $em->createQuery($dql);
            $q->setParameters(["collegeGroup" => $g, "day" => $this->date]);
            $q->setMaxResults(2);
            $result = $q->getResult();

            $human_name = $g->getHumanName();

            if (count($result) != 2) {
                // Проверку различий можно не делать - ещё мало расписаний
                info("Для группы " . $human_name . " проверка пропущена ");
                continue;
            }

            $old = $result[1];
            $new = $result[0];

            $old_pairs = $pair_repo->getPairsOfScheduleForGroup($old);
            $new_pairs = $pair_repo->getPairsOfScheduleForGroup($new);

            // -- Проверка изменений --
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
                    $old_pair = $old_pairs[$i];
                    $new_pair = $new_pairs[$i];

                    // Если время не совпадает
                    if ($old_pair->getTime() != $new_pair->getTime()) {
                        $time_changed = true;
                    }

                    // Если дисциплина не совпадает
                    if ($old_pair->getPairName() != $new_pair->getPairName()) {
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
                info($human_name . ": обнаружены различия");
                $message = getPairsChangedText($items);
                $output[] = ["group"=>$g, "msg"=>$message];
            } else {
                info($human_name . ": различия не выявлены");
            }
        }

        return $output;
    }

    // Парсит таблицу, ищет пары и время
    public function parseTable() {
        $this->matrix = $this->getMatrix();
        success("Получена матрица из таблицы");
        $now = new DT();
        $em = Database::getEm();

        // Текущий y
        $y = 0;
        while ($y < count($this->matrix)) {

            // Сначала парсим строку с названиями групп
            $group_names_row = $this->matrix[$y];
            if (!$this->rowContainsGroupName($group_names_row)) {
                info("Строка $y не содержит названий групп");
                $y++;
                continue;
            }
            $groups = $this->parseGroupRow($group_names_row);
            success("Получены группы из строки #" . $y);
            $msg = "Группы: ";
            foreach ($groups as $g) {
                $msg .= $g->getHumanName() . "(id: " . $g->getId() . ") ";
            }
            info($msg);

            // Соответствие группа: объект расписания
            $group_schedules = [];
            foreach ($groups as $g) {
                $schedule = new Entities\Schedule();
                $schedule->setCollegeGroup($g);
                $schedule->setDay($this->date);
                $schedule->setCreatedAt($now);
                $em->persist($schedule);
                $group_schedules[$g->getId()] = $schedule;
            }

            // Затем парсим строки с парами, до тех пор пока не встретим
            // строку с названиями групп или конец таблицы
            $y_pairs = $y + 1;
            while ($y_pairs < count($this->matrix)) {
                $pairs_row1 = $this->matrix[$y_pairs];
                $pairs_row2 = $this->matrix[$y_pairs + 1];

                if ($this->rowContainsGroupName($pairs_row1)) {
                    break;
                }
                
                $this->parsePairsRow(
                    $pairs_row1,
                    $pairs_row2,
                    $groups,
                    $group_schedules
                );

                // Одна строка пар имеет две строки, поэтому +2
                $y_pairs += 2;
            }

            $y = $y_pairs;
        }

        $em->flush();
    }
}

// Информация, полученная из документа
class ScrapedInfo {
    // Таблицы документа
    private array $tables;

    // Тексты документа. Обычно это даты расписаний
    private array $strings;

    private static array $month_names = array(
        "января",
        "февраля",
        "марта",
        "апреля",
        "мая",
        "июня",
        "июля",
        "августа",
        "сентября",
        "октября",
        "ноября",
        "декабря"
    );

    private static array $weekday_names = array(
        "понедельник",
        "вторник",
        "среду",
        "четверг",
        "пятницу",
        "субботу",
        "воскресенье"
    );

    public function __construct(array $tables, array $strings) {
        $this->tables = $tables;
        $this->strings = $strings;
    }

    // Возвращает массив информации расписаний. Даты считываются из $strings
    public function getSchedules(DT $now, DT $date_relevancy) : array | false {
        $today = $now->setTime(0,0,0);
        
        // Извлекаем из строк даты расписаний
        // Формат: РАСПИСАНИЕ ЗАНЯТИЙ на <день недели> <число с 0 впереди если
        // оно меньше 10> <месяц в родительном падеже>
        $dates = array();

        foreach ($this->strings as $text) {
            $words = explode(" ", $text);

            if (count($words) < 6) {
                warning("$text не дата расписания - количество слов меньше 6");
                continue;
            }

            if (mb_strtolower($words[0]) != "расписание" ||
                mb_strtolower($words[1]) != "занятий") {
                warning("$text не дата расписания - первые два слова не 'расписание занятий'");
                continue;
            }

            $month_id = 0;
            $found_month = false;
            foreach (self::$month_names as $name) {
                if (str_contains($text, $name)) {
                    $found_month = true;
                    break;
                }
                $month_id++;
            }
            if (!$found_month) {
                warning("$text не дата расписания - не обнаружено месяца");
                continue;
            }

            $month_word_index = array_search(self::$month_names[$month_id], $words);
            if (!is_numeric($words[$month_word_index - 1])) {
                warning("$text не дата расписания - число месяца не обнаружено");
                continue;
            }

            $found_weekday = false;
            foreach (self::$weekday_names as $wd) {
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
            $dates[] = DT::createFromFormat(
                'm-d',
                ($month_id+1).'-'.($words[$month_word_index - 1])
            )->setTime(0,0,0);
        }

        if (count($dates) != count($this->tables)) {
            err("Количество дат не совпадает с количеством таблиц");
            return false;
        }

        $output = [];
        $counter = 0;
        foreach ($this->tables as $t) {

            $date = $dates[$counter];

            if ($today <= $date && $date <= $date_relevancy) {
                $relevant = true;
            } else {
                if (!su_parseall) {
                    $relevant = false;
                } else {
                    $relevant = true;
                }
            }
            
            $output[] = new TableInfo($dates[$counter], $t, $relevant);
            $counter++;
        }
        return $output;
    }
}

// Показывает использование
function showUsageAndExit() {
    echo "Использование: php schedule-update.php -i <файл расписания.doc> [--parse-all]\n";
    echo "-i = файл расписания в формате doc\n";
    echo "--parse-all = парсить все даты, независимо от актуальности\n";
    exit();
}

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
function getTextFromRun($element) : string {
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

// Скачивает файл расписания и ищет там таблицы и даты. Возвращает таблицы
// в формате массива
function scrapeElements($input) : ScrapedInfo {
    // Загрузка файла расписания
    info('Загрузка файла расписания');
    $contents = file_get_contents($input);
    file_put_contents('/tmp/schedule.doc', $contents);

    info('Преобразование в docx..');
    $result = exec('unoconv -d document --format=docx /tmp/schedule.doc');
    if ($result === false) {
        err("Команда преобразования не выполнена, убедитесь что unoconv установлен");
        exit();
    }

    // Считывание всей информации документа
    $phpWord = \PhpOffice\PhpWord\IOFactory::load('/tmp/schedule.docx');
    $tables = array();
    $textruns = array();

    info("Cбор информации документа");
    // Проходимся по всем секциям и по всем элементам секций в документе
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            switch (get_class($element)) {
            // Если элемент - таблица, то добавляем её в массив чтобы обработать
            // её позже
            case "PhpOffice\PhpWord\Element\Table":
                info("Таблица");
                $tables[] = $element;
                break;

            // Если элемент - текстоподобный, то считываем весь его текст и
            // добавляем в массив текстов
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

    return new ScrapedInfo($tables, $textruns);
}

#region Разные переменные
// Сейчас
$now            = new DT();
// Завтра
$tomorrow       = $now->add(new DateInterval("P1D"));
// Актуальность
$date_relevant  = $now->add(new DateInterval("P1D"));
// EntityManager
$em             = Database::getEm();
// Массив уведомлений - какие группы должны быть уведомлены какими сообщениями
$notifications  = [];
#endregion

#region Парсинг аргументов
// https://www.php.net/manual/ru/function.getopt.php
$shortopts = "";
$shortopts .= "i:";
$longopts  = array(
    "parse-all"
);
$options = getopt($shortopts, $longopts);

if (!isset($options["i"])) {
    showUsageAndExit();
}
define("su_input", $options["i"]);

define("su_parseall", isset($options["parse-all"]));
#endregion

#region Считывание & парсинг информации
$scraped_info = scrapeElements(su_input);

info("Проверка дат расписаний");
$tables_info = $scraped_info->getSchedules($now, $date_relevant);
if ($tables_info === false) {
    exit();
}

info("Парсинг информации");
foreach ($tables_info as $ti) {
    $human_date = $ti->humanDate();
    if (!$ti->isRelevant()) {
        warning($human_date . ' пропускается - т.к. дата не актуальна');
        continue;
    }

    info("Выполняется парсинг для даты " . $human_date);
    $ti->parseTable();

    info("Проверка различий для даты: " . $human_date);
    $notifications = array_merge($notifications, $ti->checkDiff());
}

#endregion

#region Инвалидация кэша изображений
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
success("Установлен запрет использования старого кэша");
#endregion

#region Отправка уведомлений
foreach ($notifications as $n) {
    NotificationService::sendToGroup(
        $n["group"],
        $n["msg"],
        CallbackType::SelectedDateForCurrentStudentRasp,
        [
            "date" => $tomorrow->format('Y-m-d'),
            "data" => []
        ],
        "Расписание на завтра"
    );
}
#endregion