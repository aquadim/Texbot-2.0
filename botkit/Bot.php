<?php
// Класс бота. Отвечает за вызов событий драйверов

namespace BotKit;

use BotKit\Drivers\IDriver;
use BotKit\Entities\{User as UserEntity, Platform};

use BotKit\Models\Events\IEvent;
use BotKit\Models\User as UserModel;
use BotKit\Models\Events\TextMessageEvent;
use BotKit\Models\Events\CallbackEvent;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

class Bot {

    // Событие которое обрабатывает бот
    private static IEvent $event;

    // Загружен ли драйвер
    private static bool $driver_loaded = false;

    // Драйвер, который будет обрабатывать запрос
    private static IDriver $driver;

    // Загружает драйвер
    // Драйвер определяет будет ли он обрабатывать запрос и если ответ
    // положительный, драйвер становится загруженным.
    public static function loadDriver(IDriver $driver) {
        if (self::$driver_loaded == true) {
            // Драйвер уже выбран
            return;
        }

        if ($driver->forThis()) {
            self::$driver_loaded = true;
            self::$driver = $driver;
        }
    }

    // Убеждается в том, что драйвер для бота загружен
    public static function onLoadingFinished() {
        // Выбросить исключение если ни один драйвер не согласился обработать
        // запрос
        if (self::$driver_loaded == false) {
            throw new \Exception("Bot has no loaded drivers");
        }
        self::$driver->onSelected();

        // "- Драйвер, какой у пользователя ID на платформе?"
        $user_platform_id = self::$driver->getUserIdOnPlatform();
        // "- А какая у тебя вообще платформа?"
        $driver_platform = self::$driver->getPlatformDomain();

        // Получение объекта сущности пользователя
        $em = Database::getEM();
        $query = $em->createQuery(
            'SELECT user, platform FROM '. UserEntity::class .' user '.
            'JOIN user.platform platform '.
            'WHERE platform.domain=:platformDomain '.
            'AND user.id_on_platform=:id_on_platform');
        $query->setParameters([
            'platformDomain' => $driver_platform,
            'id_on_platform'=> $user_platform_id
        ]);
        $result = $query->getResult();

        if (count($result) === 0) {
            // Нет пользователя, создаём
            $platform_query = $em->createQuery('SELECT platform FROM '.
            Platform::class .' platform WHERE platform.domain=:platformDomain');
            $platform_query->setParameters([
                'platformDomain' => $driver_platform
            ]);
            $platform = $platform_query->getResult()[0];
            
            $user_entity = new UserEntity();
            $user_entity->setIdOnPlatform($user_platform_id);
            $user_entity->setPlatform($platform);
            $user_entity->setState(State::FirstInteraction);

            $em->persist($user_entity);
            
        } else {
            $user_entity = $result[0];
        }
        
        $user_model = new UserModel($user_entity, $user_platform_id);

        // "- Я нашёл пользователя по тем данным, которые ты мне дал, можешь
        // теперь создать объект события? Я его потом сверю с правилами из
        // routing.php"
        self::$event = self::$driver->getEvent($user_model);
    }

    // Общая процедура обработки запроса
    // $callback - то что будет выполняться
    // $params - доп. параметры для $callback
    private static function processRequest(string $callback, array $params) : void {
        self::$driver->onProcessStart();

        // Создание объекта контроллера
        // TODO: проверка ошибок формата $callback
        list($class_name, $method_name) = explode('@', $callback);
        $class_name = 'BotKit\\Controllers\\' . $class_name;
        $controller = new $class_name;

        // Инициализация объекта
        $controller->init(self::$event, self::$driver);

        // Вызов метода объекта с параметрами
        call_user_func_array([$controller, $method_name], $params);

        // Сохранение изменений
        // Необходимо, если метод контроллера меняет состояние пользователя
        // или что-либо ещё. Не дописывать же эти две строки после почти каждых
        // методов контроллеров.
        $em = Database::getEM();
        $em->flush();

        // Завершение работы
        self::$driver->onProcessEnd();
        exit();
    }

    // Подключает обработчик события
    public static function onEvent(string $event_classname, string $callback) {
        if (!is_a(self::$event, $event_classname, true)) {
            // Событие, которое сейчас обрабатывается - это не событие,
            // которое проверяет эта функция. Поиск продолжается
            return;
        }
        self::processRequest($callback, []);
    }

    // Подключает обработчик команды
    // Команда должна быть только текстовым сообщением
    public static function onCommand(string $template, string $callback) {
        if (!is_a(self::$event, TextMessageEvent::class)) {
            // Не текстовое сообщение
            return;
        }

        // Определяем что будет параметрами
		$pattern = '/^'.preg_replace(
			['/\//','/{(\w+)}/'],
			['\\\/', '(?<$1>.*)'],
			$template
		).'$/';


        if (!preg_match($pattern, self::$event->getText(), $named_groups)) {
            // Обрабатываемое событие - не для этого обработчика
            return;
		};

        // Оставляем только строковые ключи
        $named_groups_filter = array_filter(
            $named_groups,
            function ($k) {
                return is_string($k);
            },
            ARRAY_FILTER_USE_KEY
        );

        // Вызов обработки с пойманными параметрами
        self::processRequest($callback, $named_groups_filter);
    }

    // Подключает обработчик текстового события
    // text - текст сообщения должен быть таковым
    // callback - метод контроллера
    // required_state - в каком состоянии должен быть пользователь
    // should_be_from_group_chat - должно ли быть событие от группового чата?
    public static function onText(
        string $text,
        string $callback,
        State $required_state = State::Any,
        bool $should_be_from_group_chat = false
    )
    {
        if ($required_state != State::Any && self::$event->getUser()->getState() != $required_state) {
            return;
        }
        
        if (is_a(self::$event->getChat(), GroupChat::class) != $should_be_from_group_chat) {
            return;
        }
        
        if (self::$event->getText() !== $text) {
            return;
        }
        
        self::processRequest($callback, []);
    }

    // Подключает обработчик обратного вызова
    public static function onCallback(CallbackType $type, string $callback) {
        if (!is_a(self::$event, CallbackEvent::class)) {
            // Не событие обратного вызова
            return;
        }

        if (self::$event->getCallbackType() != $type) {
            // Тип обратного вызова не подходит
            return;
        }

        // Вызов обработки
        self::processRequest($callback, self::$event->getCallbackData());
    }

    // Подключает обработчик текстового для состояния пользователя
    public static function whenUserInState(State $required_state, string $callback) {
        if (self::$event->getUser()->getState() != $required_state) {
            // Требуемое состояние у пользователя не обнаружено
            return;
        }
        self::processRequest($callback, []);
    }

    // Все условия не прошли, вызываем план Б
    public static function fallback(string $callback) {
        self::processRequest($callback, []);
    }

    public static function getCurrentDriver() : IDriver {
        return self::$driver;
    }
}
