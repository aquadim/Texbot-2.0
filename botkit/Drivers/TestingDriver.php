<?php
// Драйвер бота для тестирования

namespace BotKit\Drivers;

use BotKit\Models\User as UserModel;
use BotKit\Models\Chats\IChat;
use BotKit\Models\Chats\DirectChat;
use BotKit\Models\Messages\TextMessage;
use BotKit\Models\Events\IEvent;
use BotKit\Models\Events\UnknownEvent;
use BotKit\Models\Events\TextMessageEvent;
use BotKit\Database;
use BotKit\Enums\State;

use BotKit\Models\Messages\IMessage;

class TestingDriver implements IDriver {

    // Буфер действий
    protected array $actions = [];

    // Домен
    private static string $domain = "example.com";

    // JSON данные полученного POST запроса
    private array $post_body;

    #region IDriver
    public function forThis() : bool {
        // Проверить заголовок запроса TESTINGDRIVER
        if (isset($_SERVER['HTTP_TESTINGDRIVER'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserIdOnPlatform() : string {
        return $this->post_body['user']['id'];
    }
    
    public function getUserName() : string {
        return $this->post_body['user']['username'];
    }

    public function getEvent(UserModel $user_model) : IEvent {
        $type               = $this->post_body['type'];
        $details            = $this->post_body['details'];

        $chat_with_user         = new DirectChat($this->getUserIdOnPlatform());
        $chat_of_msg            = $chat_with_user;
        $this->start_user_state = $user_model->getState();

        switch ($type) {
            case 'newMessage':
                return new TextMessageEvent(
                    $details['msgId'],
                    $user_model,
                    $chat_of_msg,
                    $details['text'],
                    []
                );

            case 'statesRequest':
                $all_states = array_combine(
                    array_column(State::cases(), 'value'),
                    array_column(State::cases(), 'name')
                );
                $details = [];
                foreach ($all_states as $id => $name) {
                    $details[] = ['id'=>$id, 'name'=>$name];
                }
                $this->addAction(
                    "statesResponse",
                    $details
                );
                // Дальнейшая обработка не требуется, завершаем выполнение здесь
                $this->echoActions();
            
            default:
                return new UnknownEvent($user_model, $chat_of_msg);
        }

        // Интерфейс тестов запрашивает установку состояния
        //~ if ($data['type'] == 'stateSet') {
            //~ $user = $this->getUser($details['userID']);
            //~ $user->setState(State::from($details['stateID']));

            //~ // Сохраняем пользователя вручную
            //~ UserModel::updateObject($user->getDbObject());
            
            //~ $this->actions[] = [
                //~ "action" => "info",
                //~ "title" => "Состояние пользователя изменено вручную",
                //~ "body" => "Новое состояние: ".serialize($user->getState())
            //~ ];
            //~ // Дальнейшая обработка не требуется, завершаем выполнение здесь
            //~ $this->echoActions();
        //~ }

        //~ if ($data['type'] == 'callback') {
            //~ // Обратный вызов
            //~ $user = $this->getUser($details['userId']);
            //~ return new CallbackEvent(
                //~ $details['msgId'],
                //~ $user,
                //~ $chat,
                //~ CallbackType::from($details['callbackType']),
                //~ $details['params']
            //~ );
        //~ }

        //~ if ($data['type'] == 'botKitMsg') {
            //~ // Обычное текстовое сообщение
            //~ $user = $this->getUser($details['userID']);
            //~ $text = $details['text'];
            //~ return new PlainMessageEvent(
                //~ $details['id'],
                //~ $user,
                //~ $chat,
                //~ $text,
                //~ []
            //~ );
        //~ }
    }
    
    public function reply(
        TextMessageEvent $e,
        IMessage $msg,
        bool $empathise = true) : void
    {
        if ($empathise) {
            $reply_to_id = $e->getMessageID();
        } else {
            $reply_to_id = -1;
        }
        $this->sendInternal($msg, $reply_to_id);
    }

    public function sendDirectMessage(UserModel $user, IMessage $msg) : void {
        $this->sendInternal($msg, -1);
    }
    
    public function editMessage($message_id, IMessage $msg) : void {
        $this->addAction(
            'editMessage',
            [
                'msgId'=>$message_id,
                'newMessage'=>$this->getMessageData($msg, -1)
            ]);
    }

    public function sendToChat(IChat $chat, IMessage $msg) : void {
        $this->sendInternal($msg, -1);
    }
    
    public function onSelected() : void {
        // TODO: добавить условие, проверяющее значение из .env файла
        //~ set_error_handler([$this, "errorHandler"], E_ALL);
        //~ set_exception_handler([$this, "exceptionHandler"]);

        $payload = file_get_contents('php://input');
        if ($payload === '') {
            // Нет данных, драйвер не будет обрабатывать запрос
            throw new \Exception("No payload");
        }
        $this->post_body = json_decode($payload, true);
    }

    public function onProcessStart() : void {}

    // Событие завершения обработки
    public function onProcessEnd() : void {
        //~ $end_state = $user->getState();
        //~ if ($this->start_user_state != $end_state) {
            //~ // Если вначале пользователь был в одном состоянии, а в конце
            //~ // в другом, уведомляем об этом
            //~ $this->addAction('info',
                //~ [
                    //~ "title" => "Состояние пользователя изменено",
                    //~ "body" => "Новое состояние: ".serialize($end_state)
                //~ ]
            //~ );
        //~ }
        $this->echoActions();
    }

    // Показывает содержимое переменной (для отладки)
    // $label - что именно за переменная
    // $variable - значение переменной
    public function showContent(string $label, $variable) : void {
        ob_start();
        var_dump($variable);
        $info = ob_get_clean();
        $this->addAction("varDump",
            [
                "title" => $title,
                "info" => $info 
            ]
        );
    }

    public function getPlatformDomain() : string {
        return 'example.com';
    }
    #endregion

    // Добавляет действие в буфер
    protected function addAction(string $command, array $details) : void {
        $this->actions[] = ['action' => $command, 'details' => $details];
    }

    // Отправляет сообщение
    protected function sendInternal(IMessage $msg, int $reply_to_id) : void {
        $this->addAction(
            'newMessage',
            $this->getMessageData($msg, $reply_to_id));
    }

    // Возвращает разметку для сообщения
    private function getMessageData(IMessage $msg, int $reply_to_id) : array {
        //~ $attachments = [];

        //~ // Поиск клавиатур
        //~ if ($msg->hasKeyboard()) {
            //~ $keyboard = $msg->getKeyboard();

            //~ // Определение типа
            //~ if ($keyboard->inline) {
                //~ $attachment_type = "inlineKeyboard";
            //~ } else {
                //~ $attachment_type = "keyboard";
            //~ }

            //~ $serialized_layout = [];

            //~ // Разметка
            //~ $layout = $keyboard->getLayout();
            //~ foreach ($layout as $row) {
                //~ $serialized_row = [];
                //~ foreach ($row as $button) {

                    //~ // Определение типа
                    //~ if (is_a($button, CallbackButton::class)) {
                        //~ // Кнопка обратного вызова
                        //~ $button_type = "callbackButton";
                    //~ } else {
                        //~ $button_type = "button";
                    //~ }

                    //~ // Определение цвета
                    //~ switch ($button->getColor()) {
                        //~ case KeyboardButtonColor::Primary:
                            //~ $button_color = "primary";
                            //~ break;
                        //~ case KeyboardButtonColor::Secondary:
                            //~ $button_color = "secondary";
                            //~ break;
                        //~ default:
                            //~ $button_color = "primary";
                            //~ break;
                    //~ }

                    //~ $button_data = [
                        //~ "type" => $button_type,
                        //~ "color" => $button_color,
                        //~ "label" => $button->getText()
                    //~ ];

                    //~ // Добавление параметров обратного вызова
                    //~ if ($button_type == "callbackButton") {
                        //~ $button_data["callbackType"] = $button->getType();
                        //~ $button_data["payload"] = $button->getPayload();
                    //~ }

                    //~ $serialized_row[] = $button_data;
                //~ }
                //~ $serialized_layout[] = $serialized_row;
            //~ }

            //~ $attachments[] = [
                //~ "type" => $attachment_type,
                //~ "layout" => $serialized_layout
            //~ ];
        //~ }

        //~ // Поиск изображений
        //~ if ($msg->hasImages()) {
            //~ $images = $msg->getImages();
            //~ foreach ($images as $image) {
                //~ $attachments[] = [
                    //~ 'type' => 'image',
                    //~ 'url' => $image->getValue()
                //~ ];
            //~ }
        //~ }

        return [
            "text" => $msg->getText(),
            "reply_to" => $reply_to_id
        ];
    }

    public function errorHandler(
        int $errno,
        string $errstr,
        string $errfile = null,
        int $errline = null,
        array $errcontext = null
    ): bool {

        $meaning = [
            E_ERROR => "error",
            E_WARNING => "warning",
            E_PARSE => "error",
            E_NOTICE => "warning",
            E_CORE_ERROR => "error",
            E_CORE_WARNING => "warning",
            E_COMPILE_ERROR => "error",
            E_COMPILE_WARNING => "warning",
            E_USER_ERROR => "error",
            E_USER_WARNING => "warning",
            E_USER_NOTICE => "warning",
            E_STRICT => "warning",
            E_RECOVERABLE_ERROR => "error",
            E_DEPRECATED => "warning",
            E_USER_DEPRECATED => "warning"
        ];

        $this->addAction(
            $meaning[$errno]."Message",
            [
                "line" => $errline,
                "file" => $errfile,
                "trace" => "<Нет стека вызовов>",
                "msg" => $errstr
            ]
        );

        if ($meaning[$errno] === 'error') {
            // Если произошла фатальная ошибка, завершаем работу
            $this->echoActions();
            return true; // Не достигается
        } else {
            return false;
        }
    }

    public function exceptionHandler($ex) : void {
        $this->addAction(
            "errorMessage",
            [
                "line" => $ex->getLine(),
                "file" => $ex->getFile(),
                "trace" => $ex->getTraceAsString(),
                "msg" => $ex->getMessage()
            ]
        );
        $this->echoActions();
    }

    // Выводит все события в JSON
    protected function echoActions() {
        echo json_encode($this->actions);
        exit();
    }
}