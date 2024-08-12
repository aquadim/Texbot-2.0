<?php
// Кнопка, которая содержит ссылку

namespace BotKit\Models\KeyboardButtons;
use BotKit\Enums\ButtonColor;

class UrlKeyboardButton implements IKeyboardButton {
    
    protected string $text;
    protected string $url;
    protected ButtonColor $color;
    
    public function __construct(
        string $text,
        ButtonColor $color = ButtonColor::Primary,
        string $url
    )
    {
        $this->text = $text;
        $this->color = $color;
        $this->url = $url;
    }
    
    public function setText(string $text) : void {
        $this->text = $text;
    }
    
    public function getText() : string {
        return $this->text;
    }

    public function setValue($value) : void {
        $this->url = $value;
    }
    
    public function getValue() {
        return $this->url;
    }
    
    public function setColor(ButtonColor $color) : void {
        $this->color = $color;
    }
    
    public function getColor() : ButtonColor {
        return $this->color;
    }
}
