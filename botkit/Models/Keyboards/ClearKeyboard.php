<?php
// Клавиатура, очищающая предыдущую клавиатуру

namespace BotKit\Models\Keyboards;
use BotKit\Models\KeyboardButtons\IKeyboardButton;

class ClearKeyboard implements IKeyboard {
    
    protected bool $cacheable;

    public function isCacheable() : bool {
        return $this->cacheable;
    }
    
    public function getLayout() : array {
        return [];
    }
    
    public function isOneTime() : bool {
        return true;
    }
    
    public function addButton(IKeyboardButton $button) : void {
        throw new \Exception("Clear keyboard cannot have layout");
    }
    
    public function breakRow() : void {
        throw new \Exception("Clear keyboard cannot have layout");
    }
    
    public function addRow(array $row) : void {
        throw new \Exception("Clear keyboard cannot have layout");
    }
}