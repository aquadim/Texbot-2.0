<?php

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class SuggestEnterAversCredentialsKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    public function __construct() {
        $this->layout = [
            [
                new CallbackButton(
                    "Ввести данные АВЕРС",
                    CallbackType::EnterJournalLogin,
                    ['first_time' => false],
                    ButtonColor::Positive
                )
            ]
        ];
    }
}
