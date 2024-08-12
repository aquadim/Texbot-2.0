<?php
// Кнопка с текстом
// При нажатии на кнопку должно отправиться событие, в котором
// отправятся данные из $payload

namespace BotKit\Models\KeyboardButtons;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class CallbackButton implements IKeyboardButton {
    
    protected string $text;
    protected ButtonColor $color;
    protected CallbackType $callback_type;
    protected array $callback_data;
    
    public function __construct(
        string $text,
        CallbackType $callback_type,
        array $callback_data = [],
        ButtonColor $color = ButtonColor::Primary
    )
    {
        $this->text = $text;
        $this->callback_type = $callback_type;
        $this->callback_data = $callback_data;
        $this->color = $color;
    }
    
    public function setText(string $text) : void {
        $this->text = $text;
    }
    
    public function getText() : string {
        return $this->text;
    }
    
    public function setValue($value) : void {
        throw new \Exception("Setting additional value is not supported. Use constructor.");
    }
    
    public function getValue() {
        return [
            "type" => $this->callback_type,
            "data" => $this->callback_data
        ];
    }
    
    public function setColor(ButtonColor $color) : void {
        $this->color = $color;
    }
    
    public function getColor() : ButtonColor {
        return $this->color;
    }
    
    public function getCallbackData() : array {
        return $this->callback_data;
    }
    
    public function getCallbackType() : CallbackType {
        return $this->callback_type;
    }
}
