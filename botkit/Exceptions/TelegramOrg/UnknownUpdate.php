<?php
// Бот получил неизвестное событие.
namespace BotKit\Exceptions\TelegramOrg;

class UnknownUpdate extends \Exception  {
    public function __construct($message) {
        parent::__construct($message, 0, null);
    }

    public function __toString() {
        return "При обработке запроса произошла ошибка: " . $message . "\n";
    }
}