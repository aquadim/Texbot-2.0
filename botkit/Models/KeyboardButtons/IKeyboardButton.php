<?php
// Интерфейс кнопки клавиатуры

namespace BotKit\Models\KeyboardButtons;
use BotKit\Enums\ButtonColor;

interface IKeyboardButton {
    
    // Устанавливает текст, отображаемый на кнопке
    public function setText(string $text) : void;
    
    // Возвращает текст, отображаемый на кнопке
    public function getText() : string;
    
    // Возвращает доп. значение
    public function getValue();
    
    // Устанавливает доп. значение
    public function setValue($value) : void;
    
    // Устанавливает цвет
    public function setColor(ButtonColor $color) : void;
    
    // Возвращает цвет
    public function getColor() : ButtonColor;
}

