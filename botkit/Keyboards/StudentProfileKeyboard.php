<?php
// Клавиатура профиля студента

namespace BotKit\Keyboards;

use BotKit\Models\Keyboards\InlineKeyboard;
use BotKit\Models\KeyboardButtons\CallbackButton;

use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

class StudentProfileKeyboard extends InlineKeyboard {
    
    protected bool $cacheable = true;
    protected bool $one_time = false;
    
    // $avers_set - установлен ли логин АВЕРС
    public function __construct(bool $avers_set, bool $notifications_allowed) {
        $this->layout = [];

        $row1 = [];
        $row1[] = new CallbackButton(
            "Сменить группу",
            CallbackType::ChangeGroup,
            [],
            ButtonColor::Primary
        );
        
        if ($avers_set) {
            $avers_text = "Сменить логин и пароль от АВЕРС";
            $avers_color = ButtonColor::Primary;
        } else {
            $avers_text = "Ввести логин и пароль от АВЕРС";
            $avers_color = ButtonColor::Primary;
        }
        $row1[] = new CallbackButton(
            $avers_text,
            CallbackType::EnterJournalLogin,
            ["first_time"=>false],
            $avers_color
        );
        
        $row1[] = new CallbackButton(
            "Выбрать другой семестр",
            CallbackType::ChangePeriod,
            [],
            ButtonColor::Primary
        );
        
        $this->layout[] = $row1;

        if ($notifications_allowed) {
            $notification_btn_cb = CallbackType::DisableNotifications;
            $notification_btn_text = "Отключить уведомления";
        } else {
            $notification_btn_cb = CallbackType::EnableNotifications;
            $notification_btn_text = "Включить уведомления";
        }
        $this->layout[] = [
            new CallbackButton(
                $notification_btn_text,
                $notification_btn_cb,
                [],
                ButtonColor::Secondary
            )
        ];
        
        $this->layout[] = [
            new CallbackButton(
                "Я - преподаватель",
                CallbackType::ChangeAccountType,
                ['type' => 2],
                ButtonColor::Secondary
            )
        ];
    }
}
