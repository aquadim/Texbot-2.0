<?php
// Клавиатура профиля препода

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\CallbackButton;

use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class TeacherProfileKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    public function __construct($notifications_allowed) {
        $this->layout = [];

        if ($notifications_allowed) {
            $notification_btn_cb = CallbackType::DisableNotifications;
            $notification_btn_text = "Отключить уведомления";
        } else {
            $notification_btn_cb = CallbackType::EnableNotifications;
            $notification_btn_text = "Включить уведомления";
        }
        $this->layout[] = [
            new CallbackButton(
                $notification_btn_text,
                $notification_btn_cb,
                [],
                ButtonColor::Secondary
            )
        ];

        $this->layout[] = [
            new CallbackButton(
                "Я - студент",
                CallbackType::ChangeAccountType,
                ['type' => 1],
                ButtonColor::Secondary
            )
        ];
    }
}
