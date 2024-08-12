<?php
// Класс работы с БД

namespace BotKit;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

class Database {
    private static $db;
    private $connection;
    private $entity_manager;
    
    private function __construct($dsn) {
        // Подключение к БД
        $dsnParser = new DsnParser();
        $connection_params = $dsnParser->parse($dsn);
        $this->connection = DriverManager::getConnection($connection_params);

        // Получение менеджера сущностей
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: array(root_dir . '/botkit/Entities'),
            isDevMode: true,
        );
        $this->entity_manager = new EntityManager($this->connection, $config);
    }

    // Инициализирует БД
    public static function init($dsn): void {
        self::$db = new Database($dsn);
    }

    // Возвращает ссылку на $connection
    public static function getConnection() {
        if (self::$db == null) {
            self::init($_ENV['dsn']);
        }
        return self::$db->connection;
    }

    // Возвращает ссылку на $entity_manager
    public static function getEM() {
        if (self::$db == null) {
            self::init($_ENV['dsn']);
        }
        return self::$db->entity_manager;
    }
}
