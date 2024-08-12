<?php

namespace BotKit\Keyboards;

use BotKit\Enums\ButtonColor;
use BotKit\Models\Keyboards\TextKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;

class HubKeyboard extends TextKeyboard {

    protected bool $cacheable = true;
    protected bool $one_time = false;

    public function __construct() {
        $this->layout = [
[
new TextKeyboardButton("Расписание", ButtonColor::Primary),
new TextKeyboardButton("Оценки", ButtonColor::Primary),
new TextKeyboardButton("Что дальше?", ButtonColor::Primary),
],
[
new TextKeyboardButton("Где преподаватель?", ButtonColor::Secondary),
new TextKeyboardButton("Расписание группы", ButtonColor::Secondary),
new TextKeyboardButton("Звонки", ButtonColor::Secondary),
],
[
new TextKeyboardButton("Профиль", ButtonColor::Secondary),
],
        ];
    }
}
