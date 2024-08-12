<?php
// Клавиатура для выбора ответа "да" или "нет"
namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class YesNoKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    public function __construct(
        CallbackType $yes_callback,
        array $yes_data,
        CallbackType $no_callback,
        array $no_data
    )
    {
        $this->layout = [
            [
                new CallbackButton(
                    "Да",
                    $yes_callback,
                    $yes_data,
                    ButtonColor::Positive
                ),
                
                new CallbackButton(
                    "Нет",
                    $no_callback,
                    $no_data,
                    ButtonColor::Negative
                )
            ]
        ];
    }
}
