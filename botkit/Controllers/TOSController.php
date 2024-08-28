<?php
// Тестовый контроллер

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Enums\State;
use BotKit\Models\Messages\TextMessage as M;

class TOSController extends Controller {
    
    // Условия использования
    public function showTos() {
        $this->replyText(
        "===УСЛОВИЯ ИСПОЛЬЗОВАНИЯ===\n\n".
        "1. Я могу ошибаться, ведь я всего лишь программный код\n\n".
        
        "2. Разработчики и администрация не отвечают за возможный ".
        "ущерб, причинённый ошибкой в функции\n\n".
        
        "3. Использование моих функций абсолютно бесплатное и ни к ".
        "чему вас не обязывает\n\n".

        "4. При использовании функции отправки отчёта ваш пользователь будет ".
        "включен в сообщение ошибки");
    }
}
