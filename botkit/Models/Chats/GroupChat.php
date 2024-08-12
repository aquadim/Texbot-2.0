<?php
// Чат бота с группой пользователей мессенджера

namespace BotKit\Models\Chats;

use BotKit\Entities\Platform;

class GroupChat extends DirectChat implements IChat {

    public function __construct(
        protected string $id_on_platform,) {}
}