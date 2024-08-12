<?php
// Интерфейс клавиатуры

namespace BotKit\Models\Keyboards;
use BotKit\Models\KeyboardButtons\IKeyboardButton;

interface IKeyboard {

    // Возвращает значение кэшируемости клавиатуры
    public function isCacheable() : bool;
    
    // Возвращает разметку клавиатуры
    public function getLayout() : array;
    
    // Возвращает true, если после нажатия на любую кнопку
    // клавиатура должна пропасть
    public function isOneTime() : bool;
    
    // Добавляет кнопку на текущую строку кнопок
    public function addButton(IKeyboardButton $button) : void;
    
    // Добавляет пустую строку, следующее добавление кнопки будет на
    // этой строке
    public function breakRow() : void;
    
    // Добавляет строку кнопок
    // Следующее добавление будет на другой строке
    public function addRow(array $row) : void;
}