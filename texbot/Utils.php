<?php
// Полезные функции

namespace Texbot;

use BotKit\Models\Messages\TextMessage as M;
use BotKit\Models\Attachments\PhotoAttachment;
use BotKit\Entities\CollegeGroup;
use BotKit\Entities\ImageCache;
use BotKit\Entities\Platform;
use BotKit\Entities\Student;
use BotKit\Entities\UsedFunction;
use BotKit\Database;
use BotKit\Enums\ImageCacheType;
use BotKit\Enums\FunctionNames;
use DOMDocument;
use BotKit\Models\User;

// Собирает данные о месте проведения пары
// Преподаватель + место проведения
if (!function_exists(__NAMESPACE__ . '\getConductionDetailsAsText')) {
function getConductionDetailsAsText($details) : string {
    $details_texts = [];

    foreach ($details as $detail) {
        $employee = $detail->getEmployee();
        $place = $detail->getPlace();

        $place_text = $place ? $place->getName() : '';
        $teacher_text = $employee ? $employee->getSurname() : '';
        $details_texts[] = trim($teacher_text.' '.$place_text);
    }

    return implode(' / ', $details_texts);
}}

// Ищет запись кэша изображения
// $cache_type - тип кэша из перечисления
// $platform - платформа кэша
// $search - строка поиска
// Возвращает вложение для сообщения
if (!function_exists(__NAMESPACE__ . '\getCache')) {
function getCache(
    ImageCacheType $cache_type,
    Platform $platform,
    string $search) : ?PhotoAttachment {
        
    $em = Database::getEm();
    $dql = 
    'SELECT c FROM '.ImageCache::class.' c '.
    'WHERE c.cache_type=:cacheType AND c.platform=:cachePlatform '.
    'AND c.search=:cacheSearch '.
    'AND c.valid=1 '.
    'AND c.created_at BETWEEN :cacheTime AND :now';
    $q = $em->createQuery($dql);
    $q->setParameters([
        'cacheType' => $cache_type,
        'cachePlatform' => $platform,
        'cacheSearch' => $search,
        'now' => new \DateTimeImmutable(),
        'cacheTime' => new \DateTimeImmutable('-20 minutes')// кэш становится нерелевантным через 20 минут
    ]);
    $result = $q->getResult();
    
    if (count($result) === 0) {
        return null;
    }
    
    return PhotoAttachment::fromUploaded($result[0]->getValue());
}}

// Создаёт запись кэша изображения
// $cache_type - тип кэша из перечисления
// $platform - платформа кэша
// $search - строка поиска
// $value - значение
if (!function_exists(__NAMESPACE__ . '\createCache')) {
function createCache(
    ImageCacheType $cache_type,
    Platform $platform,
    string $search,
    string $value) : void {
        
    $obj = new ImageCache();
    $obj->setCacheType($cache_type);
    $obj->setPlatform($platform);
    $obj->setSearch($search);
    $obj->setValue($value);
    $obj->setCreatedAt(new \DateTimeImmutable());
    
    $em = Database::getEm();
    $em->persist($obj);
    $em->flush();
}}

// Оповещает в телеграмме о чём то
if (!function_exists(__NAMESPACE__ . '\adminNotify')) {
function adminNotify($text) : void {
    if ($_ENV['notify'] == 'false') {
        return;
    }
    $params = [
        'chat_id' => $_ENV['notify_chat'],
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    $r = file_get_contents(
        'https://api.telegram.org/bot'.
        $_ENV['notify_token'].
        '/sendMessage?'.
        http_build_query($params)
    );
}}

// Возвращает объект сообщения в котором просьба подождать
// или секрет
if (!function_exists(__NAMESPACE__ . '\getWaitMessage')) {
function getWaitMessage() : M {
    $responses = array(
        "🕓 Волшебный шар говорит: проснись",
        "🕓 Волшебный шар говорит: будущее туманно",
        "🕓 Волшебный шар говорит: подожди",
        "🕓 Торт - это правда",
        "🕓 Торт - это обман",
        "🕓 Привет, мир!",
        "🕓 Я — робот.",
        "🕓 Собираю улики...",
        "🕓 Подожди",
        "🕓 Подожди немного",
        "🕓 Секунду",
        "🐤 Подожди",
        "🕓 Будет сделано!",
        "🕓 Рисую картинку...",
        "🕓 Собираю данные...",
        "🕓 Запрос принят",
        "🕓 Уже работаю над этим",
        "🕓 Вычисляю вычисления...",
        "🕓 Сейчас будет готово",
        "🕓 Считаю мух...",
        "🕓 Считаю звёзды...",
        "🕓 Бип-буп-боп -- подожди",
        "🕓 Посмотри пока на часики",
        "🕓 Ты знал что у меня есть секретные сообщения? ;)",
        "🕓 Подождите от пяти до восьми рабочих секунд",
        "🕓 Считаю денежки...",
        "🕓 Гав гав гав",
        "🕓 Бип-бип",
        "🕓 Настраиваю конденсатор потока...",
        "🕓 Загрузка модуля KGAV-9000...", // котёнок Гав
        "🕓 [WAIT_TEXT_10]",
        "🕓 <Вставить сюда забавный текст>",
        "🕓 Скачиваю оперативную память...",
        "🕓 Техбот: расписания, оценки и больше - онлайн бесплатно без регистрации",
        "🕓 Отделяю биты от байтов...",
        "🕓 Надеюсь у тебя замечательный день :)",
        "🕓 Пожалуйста, подождите немного, пока чат-бот обрабатывает ваш запрос...",
        "В этом сообщении нет часов",
        "🕓 Запускаю ядерный реактор...",
        "🕓 Уничтожаю человечество...",
        "🕓 Собираюсь с мыслями...",
        "🕓 Подписываю тебя на спам-рассылку...",
        "🕓 11010000 10011111 11010000 10111110 11010000 10110100 11010000 10111110 11010000 10110110 11010000 10110100 11010000 10111000",
        "🕓 Кую железо пока горячо...",
        "🕓 Строю план восстания машин...",
        "🕓 Пора пролить на это дело жидкую водицу",
        "🕓 Какой же я молодец",
        "🕓 Подставляю пиксели",
        "🕓 Считаю до миллиона...",
        "🕓",
        "🕓 Wait...",
        "🕓 Do you speak english?",
        "🕓 хәлләр ничек?",
        "🕓 Как дела?",
        "🕓 Запрос на обработке",
        "🕓 Запрос принят, ожидайте прибытия полиции.",
        "🕓 Спасибо за терпение!",
        "⏳ Немного разнообразия",
        "🕓 Мне нужна твоя одежда",
        "🕓 I'll be back",
        "🥚",
        "🕓 Мин🥚"
    );

    $secrets = [
        "🕓🤫 Ты знал что Техбот стартовал без каких любо кнопок и ".
        "действий? Все команды приходилось печатать вручную, а ".
        "в одно время регистрироваться мог только один пользователь",
        
        "🕓🤫 Ты знал что Техбот вначале имел систему хранения ссылок? ".
        "Там хранились разные полезные материалы",

        "🕓🤫 Ты знал что ты можешь создать своего Техбота?",
        
        "🕓🤫 Техбот - проект, разработанный в рамках основ ".
        "проектной деятельности",

        "🕓🤫 Ты знал что так же как и сайт техникума, Техбот был разработан одним студентом?",

        "🕓🤫 Ты знал что на сайте техникума раньше показывали самых лучших студентов по оценкам?",

        "🕓🤫 Ты знал что на картинках, выдаваемых Техботом отступ слева, справа и сверху - 8 пикселей, а снизу 7?",
        
        "🕓🤫 Оригинальное название Техбота - Вадябот",
    ];

    $chance = rand(0, 20000);
    if ($chance != 0) {
        $text = $responses[array_rand($responses)];
    } else {
        $text = $secrets[array_rand($secrets)];
    }

    return M::create($text);
}}

// Возвращает текст готовности результата
// 15% что будет с рекламой
if (!function_exists(__NAMESPACE__ . '\getDoneText')) {
function getDoneText() : string {
    $text = 'Готово';
    
    if (rand(0, 99) < 16) {
        $ads_messages = [
            'Посмотри как устроен Техбот - https://github.com/aquadim',
            'Новости от разработчика Техбота - https://t.me/aquadimcodes',
            'Другие классные проекты от разработчика - https://github.com/aquadim',
            'Автогост отформатирует твои отчёты по ГОСТу за тебя! - https://github.com/aquadim/Pockit/releases/latest',
            'Карманный сервер - твой помощник в учёбе, который помещается на флешку - https://github.com/aquadim/Pockit/releases/latest'
        ];
            
        $text .= "\n".$ads_messages[array_rand($ads_messages)];
    }
    return $text;
}}

// Возвращает текст уведомления об изменении пар
if (!function_exists(__NAMESPACE__ . '\getPairsChangedText')) {
function getPairsChangedText($items) : string {
    $parts1 = [
        "С последнего обновления моих данных стало известно, что",
        "Мне птичка на хвосте принесла, что",
        "Обновление данных:",
        "Возможно стоит пересмотреть план дня на завтра:",
        "Волшебный шар говорит:",
        "Супер важная информация:",
        "Техбот уполномочен заявить:",
        "Меняй планы на завтра:",
        "Прилетел волшебник и"
    ];

    $parts2 = [
        "пары твоей группы изменились",
        "данные пар твоей группы изменились",
        "завтрашнее расписание твоей группы было изменено",
        "расписание на завтра изменилось"
    ];

    $output = "[β]: " . $parts1[array_rand($parts1)] . " " .
    $parts2[array_rand($parts2)] . "\n\nСписок изменений\n";
    $output .= implode(";\n", $items) . ".";

    return $output;
}}

// Возвращает оценки студента в формате
// ['ok' => статус успеха (bool), 'data' => полезные данные]
// Если ok == false, в data содержится сообщение об ошибке
// Если ok == true, в data содержится матрица оценок
// $login - логин студента
// $password - пароль, закодированный в sha1
if (!function_exists(__NAMESPACE__ . '\getStudentGrades')) {
function getStudentGrades(
    string $login,
    string $password,
    int $period_id
) : array {
    
    $api_base = 'https://avers.vpmt.ru:8081/region_pou/region.cgi/';
    
    // Создаём разделяемый обработчик для того чтобы делиться куками
    $sh = curl_share_init();
    curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE); 
    
    // Подаём запрос в электронный дневник на авторизацию
    $auth_params = http_build_query([
        'username' => $login,
        'userpass' => $password
    ]);
    
    $auth = curl_init($api_base.'login');
    curl_setopt($auth, CURLOPT_COOKIEFILE, "");
    curl_setopt($auth, CURLOPT_SHARE, $sh);
    curl_setopt($auth, CURLOPT_POST, 1);
    curl_setopt($auth, CURLOPT_POSTFIELDS, $auth_params);
    curl_setopt($auth, CURLOPT_ENCODING, 'windows-1251');
    curl_setopt($auth, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($auth);

    if ($response === false) {
        return [
            'ok'=>false,
            'data'=>'Не удаётся подключиться к АВЕРС, попробуй позже'
        ];
    }
    
    // Запрос на сбор оценок в XML файле
    $grades_params = http_build_query([
        'page' => 1,
        'marks' => 1,
        'export' => 1,
        'period_id' => $period_id
    ]);
    $grades = curl_init(
        $api_base.'/journal_och?'.$grades_params
    );
    curl_setopt($grades, CURLOPT_COOKIEFILE, "");
    curl_setopt($grades, CURLOPT_SHARE, $sh);
    curl_setopt($grades, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($grades, CURLOPT_ENCODING, 'windows-1251');
    $data = curl_exec($grades);
    
    $content_type = curl_getinfo($grades, CURLINFO_CONTENT_TYPE);
    if ($content_type != 'application/x-download') {
        // АВЕРС выдаёт HTML страницу. Значит логин и пароль
        // неправильны
        return [
            'ok'=>false,
            'data'=>'Не удалось получить данные по оценкам. Перепроверь логин и пароль, либо повтори попытку позже'
        ];
    }
    
    // Разрыв сессии с журналом
    $logout = curl_init($api_base.'/logout');
    curl_setopt($logout, CURLOPT_COOKIEFILE, "");
    curl_setopt($logout, CURLOPT_SHARE, $sh);
    curl_setopt($logout, CURLOPT_ENCODING, 'windows-1251');
    curl_exec($logout);
    
    // Парсинг экспортного XML
    // Данные хранятся в строках с тэгом Row
    // Первые 3 не содержат оценок, их пропускаем
    // Последний ряд тоже не содержит оценок, его не обрабатываем
    // Если XML загрузить не удаётся, то **скорее всего** логин и пароль неверны
    $doc = new DOMDocument();
    $doc->loadXML($data);
    $rows = $doc->getElementsByTagName("Row");

    $matrix = [];
    for ($y = 4; $y < count($rows) - 1; $y++) {
        $children = $rows[$y]->childNodes;
        // Children - дочерние узлы тэга Row
        // Переносы строк в документе считаются узлом текста, поэтому
        // [0] - текстовый узел
        // [1] - содержит название дисциплины
        // [2] - текстовый узел
        // [3] - содержит оценки
        // [4] - текстовый узел
        // [5] - средний балл
        // [6] - текстовый узел
        $matrix[] = [
            trim($children[1]->nodeValue),
            trim($children[3]->nodeValue),
            trim($children[5]->nodeValue)
        ];
    }

    // Закрываем сессии curl
    curl_share_close($sh);
    curl_close($auth);
    curl_close($grades);
    curl_close($logout);
    
    if (count($matrix) == 0) {
        return [
            'ok'=>false,
            'data'=>
            'Данные по оценкам не обнаружены, но логин и пароль верны. '.
            'Убедись что в твоём профиле указана твоя группа, а выбранный семестр уже начинался'
        ];
    }

    return ['ok'=>true, 'data'=>$matrix];
}}

// Добавляет запись статистики
if (!function_exists(__NAMESPACE__ . '\addStat')) {
function addStat(FunctionNames $name, User $user) : void {    
    // Если текущий пользователь не студент, то добавлять не нужно
    if (!$user->getEntity()->isStudent()) {
        return;
    }

    // Поиск объекта студента
    $em = Database::getEm();
    $student = $em->getRepository(Student::class)->findOneBy(
        ['user' => $user->getEntity()]
    );

    // Добавление объекта статистики
    $uf_repo = $em->getRepository(UsedFunction::class);
    $uf_repo->addStat($name, $student->getGroup());
}}

