<?php
// Клавиатура, присылаемая с уведомлением
namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class NotificationKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;

    // $btn - первая кнопка
    public function __construct(
        ?CallbackType $buttonCallbackType,
        ?string $buttonText
    ) {
        $this->layout = [];

        if ($buttonCallbackType != null && $buttonText != null) {
            $this->layout[] = [new CallbackButton(
                $buttonText,
                $buttonCallbackType,
                [],
                ButtonColor::Primary
            )];
        }

        $this->layout[] =
        [
            new CallbackButton(
                "Отключить уведомления",
                CallbackType::DisableNotifications,
                [],
                ButtonColor::Secondary
            )
        ];
    }
}
