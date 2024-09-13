<?php

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class AnotherPeriodKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    public function __construct() {
        $this->layout = [
            [
                new CallbackButton(
                    "Другой семестр",
                    CallbackType::ChangePeriod,
                    [],
                    ButtonColor::Primary
                )
            ]
        ];
    }
}
