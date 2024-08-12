<?php

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class TeacherOrStudentKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    public function __construct() {
        $this->layout = [
            [
                new CallbackButton(
                    "Я преподаватель",
                    CallbackType::SelectedAccountType,
                    ["answer" => "teacher"],
                    ButtonColor::Primary
                ),
                
                new CallbackButton(
                    "Я студент",
                    CallbackType::SelectedAccountType,
                    ["answer" => "student"],
                    ButtonColor::Primary
                )
            ]
        ];
    }
}
