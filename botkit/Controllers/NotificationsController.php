<?php
// Управление уведомлениями пользователей
namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Enums\State;
use BotKit\Models\Messages\TextMessage as M;

class NotificationsController extends Controller {
    
    // Отключить уведомления
    public function disable() {
        $user_entity = $this->u->getEntity();
        if (!$user_entity->notificationsAllowed()) {
            $this->replyText("ℹ️ Уведомления уже отключены");
        } else {
            $user_entity->setNotificationsAllowed(false);
            $this->replyText("✅ Уведомления отключены");
        }
    }
    
    // Включить уведомления
    public function enable() {
        $user_entity = $this->u->getEntity();
        if ($user_entity->notificationsAllowed()) {
            $this->replyText("ℹ️ Уведомления уже включены");
        } else {
            $user_entity->setNotificationsAllowed(true);
            $this->replyText("✅ Уведомления включены");
        }
    }
}
