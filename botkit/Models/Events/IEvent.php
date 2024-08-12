<?php
// Интерфейс события

namespace BotKit\Models\Events;

use BotKit\Models\User;
use BotKit\Models\Chats\IChat;

interface IEvent {
    
    // Возвращает ID события на платформе. Не на всех платформах
    // у событий есть идентификаторы, поэтому можно возвращать null
    public function getEventId() : ?string;
    
    // Возвращает ID сообщения, с которым ассоциировано событие.
    // Если событие не связано ни с каким сообщением, можно вернуть null
    public function getAssociatedMessageId() : ?string;
    
    // Возвращает пользователя, с которым ассоциировано данное событие
    public function getUser() : User;

    // Возвращает чат, из которого был вызвано это событие
    public function getChat() : IChat;
    
    // Возвращает текст сообщения
    public function getText() : string;
}
