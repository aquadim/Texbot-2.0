<?php
// Клавиатура выбора семестра

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;

use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class SelectPeriodKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = false;
    protected bool $one_time = true;

	public function __construct(array $periods) {		
		$this->layout = [];

		$row = [];
        $buttons_added = 0;
		foreach ($periods as $period) {
			
            $button = new CallbackButton(
				$period->getHumanName(),
				CallbackType::SelectedPeriod,
				['period_id' => $period->getId()],
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
	}
}
