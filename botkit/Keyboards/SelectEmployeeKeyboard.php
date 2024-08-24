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

    public function __construct($paginator, CallbackType $goal, $offset) {
        $this->layout = [];

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

            // 3 кнопки на ряд
            if ($buttons_added % 3 == 0) {
                $this->layout[] = $row;
                $row = [];
                $buttons_added = 0;
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
                    'offset' => $offset - 6
                ],
                ButtonColor::Secondary
            );
        }

        if ($offset + 6 < count($paginator)) {
            $pagination_row[] = new CallbackButton(
                "Вперёд ➡︎",
                CallbackType::EmployeeSelectionPagination,
                [
                    'goal' => $goal->value,
                    'offset' => $offset + 6
                ],
                ButtonColor::Secondary
            );
        }

        $this->layout[] = $pagination_row;
    }
}
