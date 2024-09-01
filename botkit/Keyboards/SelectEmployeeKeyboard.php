<?php
// Клавиатура выбора сотрудника

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\CallbackButton;

use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class SelectEmployeeKeyboard extends InlineKeyboard {

    protected bool $cacheable = false;
    protected bool $one_time = true;

    // $paginator - объект Paginator из Doctrine, содержит объекты сотрудников
    // $goal - CallbackType, который передастся в routing при выборе
    // $offset - какое количество первых результатов пропускается
    // $platform - платформа мессенджера
    public function __construct($paginator, CallbackType $goal, $offset, $platform) {
        $this->layout = [];

        switch ($platform) {
        case 'vk.com':
            $max_buttons_on_row = 3;
            $per_page = 6;
            break;
        case 'telegram.org':
            $max_buttons_on_row = 4;
            $per_page = 16;
            break;
        default:
            $max_buttons_on_row = 3;
            break;
        }

        $row = [];
        $buttons_added = 0;
        foreach ($paginator as $employee) {

            $button = new CallbackButton(
                $employee->getNameWithInitials(),
                $goal,
                ['employee_id' => $employee->getId()],
                ButtonColor::Primary
            );

            $row[] = $button;
            $buttons_added++;

            if ($buttons_added % $max_buttons_on_row == 0) {
                $this->layout[] = $row;
                $row = [];
            }
        }

        if (count($row) > 0) {
            $this->layout[] = $row;
        }

        // Кнопки пагинации
        $pagination_row = [];
        if ($offset != 0) {
            // Это не первая страница, кнопка "Назад" нужна
            $pagination_row[] = new CallbackButton(
                "⬅︎ Назад",
                CallbackType::EmployeeSelectionPagination,
                [
                    'goal' => $goal->value,
                    'offset' => $offset - $per_page
                ],
                ButtonColor::Secondary
            );
        }

        if ($offset + $per_page < count($paginator)) {
            $pagination_row[] = new CallbackButton(
                "Вперёд ➡︎",
                CallbackType::EmployeeSelectionPagination,
                [
                    'goal' => $goal->value,
                    'offset' => $offset + $per_page
                ],
                ButtonColor::Secondary
            );
        }

        $this->layout[] = $pagination_row;
    }
}
