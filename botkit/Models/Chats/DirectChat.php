<?php
// Чат бота с пользователем мессенджера

namespace BotKit\Models\Chats;

class DirectChat implements IChat {

    public function __construct(
        protected string $id_on_platform,) {}

    // Возвращает id на платформе
    public function getIdOnPlatform() : string {
        return $this->id_on_platform;
    }
}