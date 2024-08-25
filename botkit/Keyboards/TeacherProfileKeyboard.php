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
    
    public function __construct() {
        $this->layout = [[
            new CallbackButton(
                "Я - студент",
                CallbackType::ChangeAccountType,
                ['type' => 1],
                ButtonColor::Secondary
            )
        ]];
    }
}
