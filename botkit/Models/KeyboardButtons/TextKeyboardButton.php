<?php
// Кнопка с текстом
// При нажатии на кнопку должно отправиться сообщение от имени
// пользователя с текстом, таким же как на кнопке
// Так же могут отправиться данные из $payload

namespace BotKit\Models\KeyboardButtons;
use BotKit\Enums\ButtonColor;

class TextKeyboardButton implements IKeyboardButton {
    
    protected string $text;
    protected ButtonColor $color;
    
    public function __construct(
        string $text,
        ButtonColor $color = ButtonColor::Primary,
    )
    {
        $this->text = $text;
        $this->color = $color;
    }
    
    public function setText(string $text) : void {
        $this->text = $text;
    }
    
    public function getText() : string {
        return $this->text;
    }

    public function setValue($value) : void {
        throw new \Exception("Additional value is not supported");
    }
    
    public function getValue() {
        throw new \Exception("Additional value is not supported");
    }
    
    public function setColor(ButtonColor $color) : void {
        $this->color = $color;
    }
    
    public function getColor() : ButtonColor {
        return $this->color;
    }
}
