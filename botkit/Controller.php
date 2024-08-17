<?php
// Родительский класс контроллеров

namespace BotKit;

use BotKit\Models\Events\IEvent;
use BotKit\Drivers\IDriver;
use BotKit\Models\User;
use BotKit\Models\Messages\IMessage;
use BotKit\Models\Messages\TextMessage;

class Controller {
    // Событие, которое сейчас обрабатывается
    protected IEvent $e;

    // Драйвер, который сейчас задействован
    public IDriver $d;

    // Пользователь, вызвавший событие
    protected User $u;

    // Текст сообщения (если есть)
    protected string $msg_text;

    // Внедрение зависимостей
    public function init(IEvent $event, IDriver $driver) {
        $this->e = $event;
        $this->d = $driver;
        $this->u = $event->getUser();
        $this->msg_text = $event->getText();
    }

    // Помощник: отправляет текстовое сообщение ПОЛЬЗОВАТЕЛЮ, вызвавшему событие
    protected function replyTextDM(string $text) {
        $this->d->sendDirectMessage($this->u, new TextMessage($text, []));
    }

    // Помощник: отправляет текстовое сообщение В ЧАТ, где было вызвано событие
    protected function replyText(string $text) {
        $this->d->sendToChat($this->e->getChat(), new TextMessage($text, []));
    }
    
    // Отправляет текстовое сообщение ПОЛЬЗОВАТЕЛЮ, вызвавшему сообщение
    protected function replyDM(TextMessage $msg) {
        $this->d->sendDirectMessage($this->u, $msg);
    }
     
    // Отвечает текстовое сообщение В ЧАТ
    protected function reply(TextMessage $msg) {
        $this->d->sendToChat($this->e->getChat(), $msg);
    }
    
    // Редактирует сообщение
    protected function edit(IMessage $old, IMessage $new) {
        $this->d->editMessage($old, $new);
    }
    
    // Редактирует сообщение, связанное с текущим событием
    protected function editAssociatedMessage(IMessage $new) {
        $this->d->editMessageOfCurrentEvent($new);
    }
    
    // Возвращает текст текущего события
    protected function getEventText() : string {
        return $this->e->getText();
    }
}
