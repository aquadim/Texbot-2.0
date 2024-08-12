<?php
// Выбор группы. Часть 1 - курс

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;

use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class SelectGroup1Keyboard extends InlineKeyboard {
    
    protected bool $cacheable = false;
    protected bool $one_time = true;
    
    public function __construct(CallbackType $goal) {
        $this->layout = [
            [
                new CallbackButton(
                    "1",
                    CallbackType::SelectedGroupNum,
                    ["num" => "1", "goal" => $goal->value],
                    ButtonColor::Primary
                ),
                
                new CallbackButton(
                    "2",
                    CallbackType::SelectedGroupNum,
                    ["num" => "2", "goal" => $goal->value],
                    ButtonColor::Primary
                )
            ],
            [
                new CallbackButton(
                    "3",
                    CallbackType::SelectedGroupNum,
                    ["num" => "3", "goal" => $goal->value],
                    ButtonColor::Primary
                ),
                
                new CallbackButton(
                    "4",
                    CallbackType::SelectedGroupNum,
                    ["num" => "4", "goal" => $goal->value],
                    ButtonColor::Primary
                )
            ]
        ];
    }
}
