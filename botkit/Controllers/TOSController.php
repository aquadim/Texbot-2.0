<?php
// Тестовый контроллер

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Enums\State;
use BotKit\Models\Messages\TextMessage as M;

class TOSController extends Controller {
    
    // Условия использования
    public function showTos() {
        $this->replyText("Условия использования: ".texbot_terms_url);
    }
}
