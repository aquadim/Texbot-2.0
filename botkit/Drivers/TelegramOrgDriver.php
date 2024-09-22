<?php
// Драйвер бота для telegram.org

namespace BotKit\Drivers;

use BotKit\Models\User as UserModel;
use BotKit\Database;

use BotKit\Enums\PhotoAttachmentType;
use BotKit\Enums\State;
use BotKit\Enums\ButtonColor;
use BotKit\Enums\CallbackType;

use BotKit\Models\Chats\IChat;
use BotKit\Models\Chats\DirectChat;
use BotKit\Models\Chats\GroupChat;

use BotKit\Models\Messages\IMessage;
use BotKit\Models\Messages\TextMessage;

use BotKit\Models\Events\IEvent;
use BotKit\Models\Events\UnknownEvent;
use BotKit\Models\Events\TextMessageEvent;
use BotKit\Models\Events\CallbackEvent;

use BotKit\Models\Keyboards\IKeyboard;
use BotKit\Models\Keyboards\TextKeyboard;
use BotKit\Models\Keyboards\InlineKeyboard;

use BotKit\Models\KeyboardButtons\TextKeyboardButton;
use BotKit\Models\KeyboardButtons\UrlKeyboardButton;
use BotKit\Models\KeyboardButtons\CallbackButton;

use CURLFile;

class TelegramOrgDriver implements IDriver {

    // Домен
    private static string $domain = "telegram.org";

    // JSON данные полученного POST запроса
    private array $post_body;

    // Текущее событие
    protected IEvent $current_event;

    // Чат текущего события
    protected IChat $current_chat;
    
    // Данные текущего пользователя из POST запроса
    // https://core.telegram.org/bots/api#user
    protected array $current_user_http;

    // Объект поля из POST запроса
    protected array $field_obj_http = [];

    // Название поля, которое заполнено в событии кроме update_id
    // Перечисление полей: https://core.telegram.org/bots/api#update
    protected string $field_name;

    // Тип поля, которое заполнено в событии кроме update_id
    // Перечисление типов https://core.telegram.org/bots/api#available-types
    protected string $field_type;

    // Текст сообщения события (если есть)
    protected string $msg_text;

    // ID сообщения события (если есть)
    protected ?int $msg_id;

    // Если в событии медиа?
    protected bool $event_has_media;
    
    // Выполняет метод API
    // https://core.telegram.org/bots/api#making-requests
    // $method - метод API
    // $fields - поля запроса
    // $json - делать ли запрос с помощью json?
    public function execApiMethod(string $method, array $fields, bool $json) : ?array {
        $fields['method'] = $method;

        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://api.telegram.org/bot".$_ENV['telegramorg_apikey'].'/'
        );

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($json) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"]);
        }
        
        $output = curl_exec($ch);
        curl_close($ch);

        $http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        if ($http_code >= 500) {
            // Что то с телеграммом, игнорируем запрос
            return null;
        }

        if ($http_code == 401) {
            // TODO: выбросить исключение
        }
        
        $response = json_decode($output, true);

        if ($response === null) {
            throw new \Exception("Could not decode JSON: ".$output);
        }
        
        if (!$response['ok']) {
            throw new \Exception("API error response: ".$output);
        }

        return $response;
    }

    #region IDriver
    public function forThis() : bool {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data === null) {
            return false;
        }
        
        $this->post_body = $data;
        
        // В запросе от telegram должен быть update_id
        return isset($this->post_body['update_id']);
    }

    public function getUserIdOnPlatform() : string {
        return $this->current_user_http['id'];
    }
    
    public function getUserName($id = null) : string {
        return $this->current_user_http['first_name'];
    }
    
    public function getNickName($id = null) : string {
        if (isset($this->current_user_http['username'])) {
            return $this->current_user_http['username'];
        } else {
            return $this->getUserName();
        }
    }

    public function getEvent(UserModel $user_model) : IEvent {
        // https://core.telegram.org/bots/api#update
        $event_id = $this->post_body['update_id'];

        // Строим объект события
        switch($this->field_type) {
        case 'Message':
            $event = new TextMessageEvent(
                $event_id,
                $this->msg_id,
                $user_model,
                $this->current_chat,
                $this->msg_text,
                []
            );
            break;

        case 'CallbackQuery':
            // "Запрос получил"
            $this->execApiMethod('answerCallbackQuery', [
                'callback_query_id' => $this->field_obj_http['id']
            ], true);
            
            // Разбираем наш формат сериализации
            // ТИП~ПАРАМЕТРЫ
            list($cb_type, $cb_params_string) = explode(
                "~",
                $this->field_obj_http['data'],
                2
            );
            $cb_params = json_decode($cb_params_string, true);

            $event = new CallbackEvent(
                $event_id,
                $this->msg_id,
                $this->field_obj_http['id'],
                $user_model,
                $this->current_chat,
                CallbackType::from($cb_type),
                $cb_params
            );
            break;

        default:
            break;
        }

        $this->current_event = $event;
        
        return $this->current_event;
    }

    public function sendDirectMessage(UserModel $user, IMessage $msg) : void {
        // TODO
    }
    
    public function editMessage(IMessage $old, IMessage $new) : void {
        $this->editMessageInternal(
            $old->getId(),
            $old->getChat(),
            $this->messageHasMedia($old),
            $new
        );
    }
    
    public function editMessageOfCurrentEvent(IMessage $msg) : void {
        $this->editMessageInternal(
            $this->current_event->getAssociatedMessageId(),
            $this->current_chat,
            $this->event_has_media,
            $msg
        );
    }

    public function sendToChat(IChat $chat, IMessage $msg) : void {
        $params = [
            "chat_id" => $chat->getIdOnPlatform(),
            "text" => $msg->getText()
        ];

        $kb = $msg->getKeyboard();
        if ($kb !== null) {
            $keyboard_markup = $this->getKeyboardMarkup($kb);
            $params['reply_markup'] = $keyboard_markup;
        }
        
        $response = $this->execApiMethod("sendMessage", $params, true);

        $msg->setId($response['result']['message_id']);
        $msg->setChat($chat);
    }
    
    public function onSelected() : void {
        http_response_code(200);
        flush();

        // Проверка заголовка секретной строки
        if (!isset($_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"])) {
            exit();
        }
        
        if ($_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"] != $_ENV['telegramorg_secret']) {
            exit();
        }

        #region Определяем какое поле заполнено кроме update_id
        // О каких полях драйвер знает
        $known_field_names = ['message', 'callback_query'];

        // Найдено ли поле которое мы знаем?
        $field_found = false;
        foreach ($known_field_names as $field_name) {
            if (isset($this->post_body[$field_name])) {
                $this->field_name = $field_name;
                $field_found = true;
                break;
            }
        }

        if (!$field_found) {
            throw new \Exception("Unknown update type: ".$field_name);
        }

        // Объект поля из POST запроса
        $this->field_obj_http = $this->post_body[$this->field_name];
        #endregion

        #region На основании названия заполненного поля определяем его тип
        switch ($this->field_name) {
        case 'message':
            $this->field_type = 'Message';
            break;

        case 'callback_query':
            $this->field_type = 'CallbackQuery';
            break;

        default:
            // сюда не дойдет, по идее
            break;
        }
        #endregion

        #region Определяем пользователя платформы
        switch ($this->field_type) {
        case 'Message':
        case 'CallbackQuery':
            $this->current_user_http = $this->field_obj_http['from'];
            break;

        default:
            break;
        }
        #endregion

        #region Определяем чат из которого было отправлено сообщение
        // 1. Пытаемся найти объект чата, описанный в
        // https://core.telegram.org/bots/api#chat
        $chat_object_exists = false;

        switch ($this->field_type) {
        case 'Message':
            $chat_object = $this->field_obj_http['chat'];
            $chat_object_exists = true;
            break;
        case 'CallbackQuery':
            $chat_object = $this->field_obj_http['message']['chat'];
            $chat_object_exists = true;
            break;

        default:
            break;
        }

        if ($chat_object_exists) {
            // 2.1 По спецификации телеграмма создаём объекты чатов
            switch ($chat_object['type']) {
            case 'private':
                // Из чата с пользователем
                $this->current_chat = new DirectChat($chat_object['id']);
                break;

            case 'group':
            case 'supergroup':
            case 'channel':
                // Групповой чат
                $this->current_chat = new GroupChat($chat_object['id']);
                break;

            default:
                // Неизвестный тип чата. Считаем что с пользователем
                $this->current_chat = new DirectChat($chat_object['id']);
                break;
            }
        }
        #endregion

        #region Определяем текст и ID сообщения
        switch ($this->field_type) {
        case 'Message':
            if (isset($this->field_obj_http['text'])) {
                $this->msg_text = $this->field_obj_http['text'];
            } else {
                $this->msg_text = '';
            }
            $this->msg_id = (int)$this->field_obj_http['message_id'];
            break;
        case 'CallbackQuery':
            $this->msg_text = '';
            $this->msg_id = (int)$this->field_obj_http['message']['message_id'];
            break;
        default:
            $this->msg_text = '';
            $this->msg_id = null;
            break;
        }
        #endregion

        #region Определяем есть ли вложения
        switch ($this->field_type) {
        case 'Message':
        case 'CallbackQuery':
            $this->event_has_media = isset($this->field_obj_http['message']['photo']);
            break;
        default:
            $this->event_has_media = false;
            break;
        }
        #endregion
    }

    public function onProcessStart() : void {}

    public function onProcessEnd() : void {}

    // Показывает содержимое переменной (для отладки)
    // $label - что именно за переменная
    // $variable - значение переменной
    public function showContent(string $label, $variable) : void {
        ob_start();
        var_dump($variable);
        $info = ob_get_clean();
        $html =
        "<!DOCTYPE html>".
        "<html>".
            "<head>".
                "<title>Информация о ".$label."</title>".
                "<meta charset='utf-8'>".
                "<style>#info{font-size:1.25rem}</style>".
            "</head>".
            "<body>".
                "<h1>".$label."</h1>".
                "<div id='info'>".$info."</div>".
            "</body>".
        "</html>";
        
        $filename = uniqid(rand(), true) . '.html';
        $filename_abs = public_dir.'/dumps/'.$filename;
        
        file_put_contents($filename_abs, $html);
        
        $this->execApiMethod("sendMessage", [
            "chat_id" => $this->getUserIdOnPlatform(),
            "text" => "DUMP: ".$label.": https://vpmt.ru/callback/test/dumps/".$filename
        ], true);
    }

    public function getPlatformDomain() : string {
        return self::$domain;
    }
    
    public static function getKeyboardMarkup(IKeyboard $keyboard) : string {
        
        if (is_a($keyboard, ClearKeyboard::class)) {
            // Очищающая клавиатура
            return json_encode(['remove_keyboard' => true]);
        }
        
        $object = [];
        
        if (is_a($keyboard, InlineKeyboard::class)) {
            $buttons_key = 'inline_keyboard';
        } else {
            $buttons_key = 'keyboard';
            $object["one_time_keyboard"] = $keyboard->isOneTime();
            $object["resize_keyboard"] = true;
        }
        
        $layout = $keyboard->getLayout();
        $buttons = [];
        foreach ($layout as $row) {
            $buttons_row = [];
            
            foreach ($row as $button) {
                if (is_a($button, TextKeyboardButton::class)) {
                    // Это обычная текстовая кнопка
                    $button_obj = [
                        "text" => $button->getText()
                    ];
                } else if (is_a($button, CallbackButton::class)) {
                    // Кнопка обратного вызова

                    // Строим строку данных. Максимум 64 байт
                    $button_data = $button->getValue();
                    $callback_data = $button_data['type']->value.'~'.
                    json_encode($button_data['data']);
                    
                    $button_obj = [
                        "text" => $button->getText(),
                        "callback_data" => $callback_data,
                    ];
                } else if (is_a($button, UrlKeyboardButton::class)) {
                    // Кнопка-ссылка
                    $button_obj = [
                        "text" => $button->getText(),
                        "link" => $button->getValue()
                    ];
                    $can_set_color = false;
                }
                $buttons_row[] = $button_obj;
            }
            
            $buttons[] = $buttons_row;
        }
        $object[$buttons_key] = $buttons;
        
        return json_encode($object);
    }
    #endregion

    // Выполняет все необходимые действия по редактированию сообщения
    protected function editMessageInternal(
        string $message_id,
        IChat $chat,
        bool $old_message_has_media,
        IMessage $new
    ) : void {
        $new_message_has_media = $this->messageHasMedia($new);

        if ($new_message_has_media && !$old_message_has_media) {
            // У нового сообщения есть медиа, а у старого нет
            // Телеграм не поддерживает такое редактирование
            // Пользователи могут указать в .env файле
            // telegramorg_sendWhenEditNotPossible=true
            // В таком случае драйвер пришлёт новое сообщение, вместо
            // редактирования старого

            if (!$_ENV['telegramorg_sendWhenEditNotPossible']) {
                // Нельзя отправить, выбрасываем исключение
                throw new \Exception("Can't edit this message");
            }

            // Определить метод api
            $api_method = $this->getApiMethodToSendMessage($new);
            $json_request = true;
            $params = ['chat_id' => $chat->getIdOnPlatform()];

            // Клавиатура
            $kb = $new->getKeyboard();
            if ($kb !== null) {
                $params['reply_markup'] = $this->getKeyboardMarkup($kb);
            }

            // Текст
            $params['caption'] = $new->getText();

            if ($api_method == 'sendPhoto') {
                $photos = $new->getPhotos();
                $photo_attachment = $photos[0];

                switch ($photo_attachment->getType()) {
                case PhotoAttachmentType::FromFile:
                    $params['photo'] = new CURLFile(
                        realpath($photo_attachment->getValue()),
                        null,
                        'botkitupload'
                    );
                    $json_request = false;
                    break;

                case PhotoAttachmentType::FromUploaded:
                case PhotoAttachmentType::FromURL:
                    $params['photo'] = $photo_attachment->getValue();
                    break;

                default:
                    break;
                }
            }

            $r = $this->execApiMethod($api_method, $params, $json_request);

            // Присваиваем изображению ID на платформе
            $photo_attachment->setId(end($r['result']['photo'])['file_id']);
        }

        if (!$new_message_has_media && !$old_message_has_media) {
            // Оба сообщения - обычные, текстовые
            $params = [
                'chat_id' => $chat->getIdOnPlatform(),
                'message_id' => $message_id,
                'text' => $new->getText()
            ];
            $kb = $new->getKeyboard();
            if ($kb !== null) {
                $params['reply_markup'] = $this->getKeyboardMarkup($kb);
            }
            $this->execApiMethod('editMessageText', $params, true);
        }

        if (!$new_message_has_media && $old_message_has_media) {
            // В старом сообщении есть media, в новом нет

            if (!$_ENV['telegramorg_sendWhenEditNotPossible']) {
                // Нельзя отправить, выбрасываем исключение
                throw new \Exception("Can't edit this message");
            }

            $params = [
                "chat_id" => $chat->getIdOnPlatform(),
                "text" => $new->getText()
            ];

            $kb = $new->getKeyboard();
            if ($kb !== null) {
                $keyboard_markup = $this->getKeyboardMarkup($kb);
                $params['reply_markup'] = $keyboard_markup;
            }
        
            $this->execApiMethod("sendMessage", $params, true);
        }

        if ($new_message_has_media && $old_message_has_media) {
            $json_request = true;
            $photos = $new->getPhotos();
            $params = [
                'chat_id' => $chat->getIdOnPlatform(),
                'message_id' => $message_id
            ];
            if (count($photos) > 0) {
                $photo = $photos[0];

                switch ($photo->getType()) {
                case PhotoAttachmentType::FromFile:
                    $params['botkitupload'] = new CURLFile(
                        realpath($photo->getValue()),
                        null,
                        'botkitupload'
                    );
                    $json_request = false;
                    $media_string = 'botkitupload';
                    break;

                case PhotoAttachmentType::FromUploaded:
                case PhotoAttachmentType::FromURL:
                    $media_string = $photo->getValue();
                    break;

                default:
                    break;
                }
                
                $media_object = [
                    'type' => 'photo',
                    'media' => $media_string,
                    'caption' => $new->getText()
                ];
            }
            $params['media'] = json_encode($media_object);

            // Клавиатура
            $kb = $new->getKeyboard();
            if ($kb === null) {
                $params['reply_markup'] = $this->getKeyboardMarkup($kb);
            }
            
            $this->execApiMethod('editMessageMedia', $params, true);
            // TODO: установить ID платформы вложениям
        }
        
        // Присваиваем новому сообщению старый ID
        $new->setId($message_id);
        $new->setChat($chat);
    }

    // Возвращает true если в сообщении есть медиа
    protected function messageHasMedia(IMessage $msg) : bool {
        return count($msg->getPhotos()) > 0;
    }

    // Определяет метод api который нужно использовать при отправке сообщения
    protected function getApiMethodToSendMessage(IMessage $msg) : string {
        $photos = $msg->getPhotos();

        if (count($photos) == 1) {
            // Одно изображение
            return 'sendPhoto';
        }

        return 'sendMessage';
    }
}
