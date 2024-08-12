<?php
// Интерфейс для драйверов ботов

namespace BotKit\Drivers;

use BotKit\Models\User as UserModel;
use BotKit\Models\Chats\IChat;
use BotKit\Models\Chats\DirectChat;
use BotKit\Models\Messages\IMessage;
use BotKit\Models\Events\IEvent;
use BotKit\Models\Events\TextMessageEvent;
use BotKit\Entities\Platform;
use BotKit\Models\Keyboards\IKeyboard;

interface IDriver {

    // Возвращает true, если драйвер считает, что именно ему необходимо
    // обработать этот запрос.
    public function forThis() : bool;

    // Возвращает событие на основании данных входящего HTTP запроса
    // $user_model - модель пользователя
    public function getEvent(UserModel $user_model) : IEvent;

    // Отсылает сообщение пользователю в личный чат между ботом и пользователем
    public function sendDirectMessage(UserModel $user, IMessage $msg) : void;

    // Изменяет сообщение
    // old - старое сообщение
    // new - новое сообщение
    public function editMessage(IMessage $old, IMessage $new) : void;
    
    // Изменяет сообщение, ассоциированное с текущим событием
    public function editMessageOfCurrentEvent(IMessage $msg) : void;

    // Отправляет сообщение в чат
    public function sendToChat(IChat $chat, IMessage $msg) : void;

    // Событие после ensureDriversLoaded
    public function onSelected() : void;
    
    // Событие перед началом обработки запроса
    public function onProcessStart() : void;

    // Событие завершения обработки
    public function onProcessEnd() : void;

    // Показывает содержимое переменной (для отладки)
    // $label - что именно за переменная
    // $variable - значение переменной
    public function showContent(string $label, $variable) : void;

    // Возвращает домент платформы бота
    // Например: telegram.org, vk.com, whatsapp.com
    public function getPlatformDomain() : string;

    // Возвращает id на платформе у пользователя, который вызвал текущий запрос.
    public function getUserIdOnPlatform() : string;

    // Возвращает имя и фамилию на платформе у пользователя, который вызвал текущий запрос.
    // id - id пользователя на платформе. Если null, следует использовать
    // id текущего пользователя
    public function getUserName($id = null) : string;
    
    // Возвращает ник на платформе у пользователя, который вызвал текущий запрос.
    // Например @vadim_aqua, @pydim. Если ника нет, либо если платформа не
    // поддерживает ников, следует вернуть id на платформе.
    // id - id пользователя на платформе. Если null, следует использовать
    // id текущего пользователя
    public function getNickName($id = null) : string;
    
    // Возвращает разметку клавиатуры. Обычно в формате JSON или XML
    public static function getKeyboardMarkup(IKeyboard $keyboard) : string;
}
