<?php
// Клавиатура, отображаемая рядом с полем ввода отправки

namespace BotKit\Models\Keyboards;
use BotKit\Models\KeyboardButtons\IKeyboardButton;

class TextKeyboard implements IKeyboard {
    
    protected bool $cacheable;
    
    protected array $layout;
    
    protected int $current_row;
    
    protected bool $one_time;
    
    public function __construct() {
        $this->layout = [[]];
    }

    public function isCacheable() : bool {
        return $this->cacheable;
    }
    
    public function isOneTime() : bool {
        return $this->one_time;
    }
    
    public function getLayout() : array {
        return $this->layout;
    }
    
    public function addButton(IKeyboardButton $button) : void {
        $this->layout[$this->current_row][] = $button;
    }
    
    public function breakRow() : void {
        $this->layout[] = [];
        $this->current_row += 1;
    }
    
    public function addRow(array $row) : void {
        $this->layout[] = $row;
        $this->current_row += 2;
    }
}