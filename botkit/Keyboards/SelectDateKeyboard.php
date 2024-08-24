<?php
// Клавиатура выбора даты

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\CallbackButton;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;
use DateTimeImmutable;
use DateInterval;
use IntlDateFormatter;

class SelectDateKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = false;
    protected bool $one_time = false;

    // $callback_type - тип обратного вызова, который будет использован при
    // нажатии на кнопку
    // $data - доп. объект данных
    public function __construct(CallbackType $callback_type, array $data = []) {
        $this->layout = [];
        $added = 0;
        $date = new DateTimeImmutable();
        $now = new DateTimeImmutable();
        $day_interval = new DateInterval('P1D'); // Интервал одного дня

        // Формат дат
        $fmt = new IntlDateFormatter(
            'ru_RU',
            IntlDateFormatter::RELATIVE_MEDIUM,
            IntlDateFormatter::NONE,
            'Europe/Kirov',
            IntlDateFormatter::GREGORIAN
        );

        // Добавляем 4 кнопки для выбора
        while ($added < 4) {
            $weekday = $date->format('N');
            if ($weekday == 7) {
                // Воскресенье пропускается т.к. у него нет пар
                $date = $date->add($day_interval);
                continue;
            }
            
            $payload_date = $date->format('Y-m-d');
            $label = $fmt->format($date);

            $this->layout[] = [new CallbackButton(
                $label,
                $callback_type,
                ["date" => $payload_date, "data" => $data],
                ButtonColor::Primary
            )];
            $date = $date->add($day_interval);
            $added++;
        }
    }
}
