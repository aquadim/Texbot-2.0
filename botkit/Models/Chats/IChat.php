<?php
// Интерфейс чатов

namespace BotKit\Models\Chats;

use BotKit\Entities\Platform;

interface IChat {

    // Возвращает id на платформе
    // Платформа определяется драйвером
    public function getIdOnPlatform() : string;
}