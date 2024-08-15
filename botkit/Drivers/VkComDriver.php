<?php
// Драйвер бота для vk.com

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

class VkComDriver implements IDriver {

    // Домен
    private static string $domain = "vk.com";
    
    // API версия
    private string $api_version = "5.199";
    
    // Это запрос подтверждения сервера?
    private bool $request_is_confirmation;

    // JSON данные полученного POST запроса
    private array $post_body;
    
    // URL загрузки изображений
    protected string $uploadurl_photo;
    
    protected IEvent $current_event;
    
    // Выполняет метод API
    public function execApiMethod(string $method, array $fields) : ?array {
        $fields["v"] = $this->api_version;
        $fields["access_token"] = $_ENV["vkcom_apikey"];
        $post_fields = http_build_query($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.vk.com/method/".$method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($output, true);
    }

    #region IDriver
    public function forThis() : bool {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );
        
        if ($data === null) {
            return false;
        }
        
        $this->post_body = $data;
        
        // В запросе от ВКонтакте должны быть эти поля:
        $required = ['type', 'group_id'];
        foreach ($required as $field) {
            if (!isset($this->post_body[$field])) {
                return false;
            }
        }
        return true;
    }

    public function getUserIdOnPlatform() : string {
        switch ($this->post_body["type"]) {
            case "message_new":
            case "message_edit":
            case "message_typing_state":
                return $this->post_body["object"]["message"]["from_id"];
            
            case "message_allow":
            case "message_deny":
            case "message_event":
                return $this->post_body["object"]["user_id"];
            
            case "confirmation":
            default:
                // Запрос отправляет не пользователь, а сам ВКонтакте
                // Говорим что это псевдо-пользователь с ID -1
                return -1;
        }
    }
    
    public function getUserName($id = null) : string {
        $user = $this->execApiMethod(
            "users.get",
            ["user_ids" => $id ? $id : $this->getUserIdOnPlatform()]
        )["response"][0];
        return $user["first_name"] . " " . $user["last_name"];
    }
    
    public function getNickName($id = null) : string {
        $user = $this->execApiMethod("users.get",
        [
            "user_ids" => $id ? $id : $this->getUserIdOnPlatform(),
            "fields" => "domain"
        ])["response"][0];
        return $user["domain"];
    }

    public function getEvent(UserModel $user_model) : IEvent {
        $type = $this->post_body["type"];
        if ($type == "confirmation") {
            exit($_ENV["vkcom_confirmation"]);
        }
        
        $object = $this->post_body["object"];
        $chat_with_user = new DirectChat($this->getUserIdOnPlatform());
        
        // Узнаём чат события
        switch ($type) {
            case "message_new":
                if ($object["message"]["peer_id"] > 2000000000) {
                    $chat_of_msg = new GroupChat($object["message"]["peer_id"]);
                } else {
                    $chat_of_msg = $chat_with_user;
                }
                break;
                
            case "message_event":
                if ($object["peer_id"] > 2000000000) {
                    $chat_of_msg = new GroupChat($object["peer_id"]);
                } else {
                    $chat_of_msg = $chat_with_user;
                }
                break;
            
            default:
                $chat_of_msg = $chat_with_user;
                break;
        }
        
        // Закрываем соединение для того чтобы скрипт мог работать больше чем 10 секунд
		// Скрипт должен уметь работать больше чем 10 секунд потому что если vk не получил "ok"
		// за 10 секунд от сервера, он пришлёт запрос ещё раз. На самом деле сервер обрабатывал первый
		// запрос, и когда он его закончил, он ответил бы "ok", но второй запрос уже прислался...
		// Так будет происходить 5 раз перед тем как вк не сдастся и не прекратит присылать новые запросы
		// https://ru.stackoverflow.com/q/893864/418543
		ob_end_clean();
		header("Connection: close");
		ignore_user_abort(true);
		ob_start();
		echo "ok";
		$size = ob_get_length();
		header("Content-Length: ".$size);
		ob_end_flush();
		flush();
        
        switch ($type) {
            case "message_new":
                $this->current_event = new TextMessageEvent(
                    null,
                    $object["message"]["conversation_message_id"],
                    $user_model,
                    $chat_of_msg,
                    $object["message"]["text"],
                    []
                );
                break;
            
            case "message_event":
                $payload = $object["payload"];
                
                $this->execApiMethod('messages.sendMessageEventAnswer', [
                    'event_id' => $object['event_id'],
                    'user_id' => $object['user_id'],
                    'peer_id' => $object['peer_id'],
                    'event_data' => ''
                ]);
                
                $this->current_event = new CallbackEvent(
                    $object["event_id"],
                    $object["conversation_message_id"],
                    $user_model,
                    $chat_of_msg,
                    CallbackType::from($payload["type"]),
                    $payload["data"]
                );
                break;
            
            default:
                $this->current_event = new UnknownEvent(
                    null,
                    null,
                    $user_model,
                    $chat_of_msg,
                    ""
                );
                break;
        }
        
        return $this->current_event;
    }

    public function sendDirectMessage(UserModel $user, IMessage $msg) : void {
        $attachment_strings = $this->getAttachmentStrings($msg->getPhotos());
        $keyboard_string = $this->getKeyboardString($msg->getKeyboard());
        $reply_to_string = $this->getReplyToString($msg);
        
        $this->execApiMethod("messages.send",
        [
            "user_id" => $user->getIdOnPlatform(),
            "random_id" => 0,
            "message" => $msg->getText(),
            "reply_to" => $reply_to_string,
            "attachment" => implode(",", $attachment_strings),
            "keyboard" => $keyboard_string
        ]);
    }
    
    public function editMessage(IMessage $old, IMessage $new) : void {
        $attachment_strings = $this->getAttachmentStrings($new->getPhotos());
        $keyboard_string = $this->getKeyboardString($new->getKeyboard());
        $reply_to_string = $this->getReplyToString($new);
        
        $this->execApiMethod("messages.edit",
        [
            "peer_id" => $old->getChat()->getIdOnPlatform(),
            "random_id" => 0,
            "message" => $new->getText(),
            "attachment" => implode(",", $attachment_strings),
            "reply_to" => $reply_to_string,
            "keyboard" => $keyboard_string,
            "conversation_message_id" => $old->getId()
        ]);
        
        // Присваиваем новому сообщению старый ID
        $new->setId($old->getId());
    }
    
    public function editMessageOfCurrentEvent(IMessage $msg) : void {
        $attachment_strings = $this->getAttachmentStrings($msg->getPhotos());
        $keyboard_string = $this->getKeyboardString($msg->getKeyboard());
        $reply_to_string = $this->getReplyToString($msg);
        
        $r = $this->execApiMethod("messages.edit",
        [
            "peer_id" => $this->current_event->getChat()->getIdOnPlatform(),
            "random_id" => 0,
            "message" => $msg->getText(),
            "attachment" => implode(",", $attachment_strings),
            "reply_to" => $reply_to_string,
            "keyboard" => $keyboard_string,
            "conversation_message_id" => $this->current_event->getAssociatedMessageId()
        ]);
        
        // Присваиваем новому сообщению старый ID
        $msg->setId($this->current_event->getAssociatedMessageId());
    }

    public function sendToChat(IChat $chat, IMessage $msg) : void {
        $attachment_strings = $this->getAttachmentStrings($msg->getPhotos());
        $keyboard_string = $this->getKeyboardString($msg->getKeyboard());
        $reply_to_string = $this->getReplyToString($msg);
        
        $response = $this->execApiMethod("messages.send",
        [
            "peer_ids" => $chat->getIdOnPlatform(),
            "random_id" => 0,
            "message" => $msg->getText(),
            "reply_to" => $reply_to_string,
            "attachment" => implode(",", $attachment_strings),
            "keyboard" => $keyboard_string
        ]);
        
        $msg->setId(strval($response["response"][0]["conversation_message_id"]));
        $msg->setChat($chat);
    }
    
    public function onSelected() : void {}

    public function onProcessStart() : void {}

    public function onProcessEnd() : void {}

    // Показывает содержимое переменной (для отладки)
    // $label - что именно за переменная
    // $variable - значение переменной
    public function showContent(string $label, $variable) : void {
        ob_start();
        var_dump($variable);
        $info = ob_get_clean();
        $info = "<h1>$label</h1>".$info;
        
        $filename = uniqid(rand(), true) . '.html';
        $filename_abs = public_dir.'/dumps/'.$filename;
        
        file_put_contents($filename_abs, $info);
        
        $this->execApiMethod("messages.send",
        [
            "peer_id" => $this->getUserIdOnPlatform(),
            "random_id" => 0,
            "message" => "DUMP: ".$label.": https://vpmt.ru/callback/test/dumps/".$filename
        ]);
    }

    public function getPlatformDomain() : string {
        return self::$domain;
    }
    
    public static function getKeyboardMarkup(IKeyboard $keyboard) : string {
        // TODO:
        // URL кнопок в строке может быть максимум две, обработать
        
        if (is_a($keyboard, ClearKeyboard::class)) {
            // Очищающая клавиатура
            return "{\"buttons\":[]}";
        }
        
        $object = [];
        
        if (is_a($keyboard, InlineKeyboard::class)) {
            $object["inline"] = true;
        } else {
            $object["inline"] = false;
            $object["one_time"] = $keyboard->isOneTime();
        }
        
        $layout = $keyboard->getLayout();
        $buttons = [];
        foreach ($layout as $row) {
            $buttons_row = [];
            
            foreach ($row as $button) {
                
                $can_set_color = true;
                $button_obj = [];
                
                if (is_a($button, TextKeyboardButton::class)) {
                    // Это обычная текстовая кнопка
                    $button_action = [
                        "type" => "text",
                        "label" => $button->getText()
                    ];
                } else if (is_a($button, CallbackButton::class)) {
                    // Кнопка обратного вызова
                    $button_action = [
                        "type" => "callback",
                        "label" => $button->getText(),
                        "payload" => json_encode($button->getValue())
                    ];
                } else if (is_a($button, UrlKeyboardButton::class)) {
                    // Кнопка-ссылка
                    $button_action = [
                        "type" => "open_link",
                        "link" => $button->getValue(),
                        "label" => $button->getText()
                    ];
                    $can_set_color = false;
                }
                $button_obj["action"] = $button_action;
                
                if ($can_set_color) {
                    switch ($button->getColor()) {
                        case ButtonColor::Primary:
                            $button_color = "primary";
                            break;
                        case ButtonColor::Secondary:
                            $button_color = "secondary";
                            break;
                        case ButtonColor::Positive:
                            $button_color = "positive";
                            break;
                        case ButtonColor::Negative:
                            $button_color = "negative";
                            break;
                        default:
                            $button_color = "primary";
                            break;
                    }
                    $button_obj["color"] = $button_color;
                }
                
                $buttons_row[] = $button_obj;
            }
            
            $buttons[] = $buttons_row;
        }
        $object["buttons"] = $buttons;
        
        return json_encode($object);
    }
    #endregion
    
    // Сохраняет в драйвер сервер для загрузки фотографий в сообщения
    protected function getUploadURLPhoto() : string {
        if (isset($this->uploadurl_photo)) {
            return $this->uploadurl_photo;
        }
        
        $response = $this->execApiMethod("photos.getMessagesUploadServer",
        [
            "public_id" => $_ENV["vkcom_public_id"]
        ]);
        $this->uploadurl_photo = $response["response"]["upload_url"];
        return $this->uploadurl_photo;
    }
    
    // Загружает изображение с диска. Возвращает строку, которую можно
    // использовать как $attachment
    protected function uploadImage($filename) : string {
        // Получение URL для загрузки фото
        $upload_url = $this->getUploadURLPhoto();
        
        $image = new \CURLFile($filename, 'image/jpeg');
        
        // Передача файла
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $upload_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file1' => $image]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response_afterupload = curl_exec($ch);
    
        $data_afterupload = json_decode($response_afterupload, true);
        
        $response = $this->execApiMethod("photos.saveMessagesPhoto",
        [
            'photo'=>$data_afterupload['photo'],
            'server'=>$data_afterupload['server'],
            'hash'=>$data_afterupload['hash'],
        ]);
        
        return 
            "photo".
            $response['response'][0]['owner_id'].
            '_'.
            $response['response'][0]['id'];
    }
    
    // Возвращает строку, которую можно использовать как поле
    // attachment при отправке/редактирования сообщения
    protected function getAttachmentStrings($photos) : array {
        $attachment_strings = [];
        
        // photo
        foreach ($photos as $photo) {
            switch ($photo->getType()) {
                case PhotoAttachmentType::FromFile:
                    // Загружаем на сервер
                    $attachment = $this->uploadImage($photo->getValue());
                    $attachment_strings[] = $attachment;
                    $photo->setId($attachment);
                    break;
                
                case PhotoAttachmentType::FromURL:
                    // Скачиваем и сохраняем
                    $image_data = file_get_contents($photo->getValue());
                    $filename = tempnam("/tmp", "botkit").'.jpeg';
                    file_put_contents($filename, $image_data);
                    
                    // Загружаем как в FromFile
                    $attachment = $this->uploadImage($filename);
                    $attachment_strings[] = $attachment;
                    $photo->setId($attachment);
                    break;
                
                case PhotoAttachmentType::FromUploaded:
                    $attachment_strings[] = $photo->getValue();
                    break;
                
                default:
                    break;
            }
        }
        
        return $attachment_strings;
    }
    
    // Возвращает строку, которую можно использовать как поле
    // keyboard при отправке/редактирования сообщения
    protected function getKeyboardString(?IKeyboard $kb_obj) : string {
        if ($kb_obj == null) {
            return "";
        }
        return self::getKeyboardMarkup($kb_obj);
    }

    // Возвращает строку -- id сообщения на которое отвечает сообщение
    protected function getReplyToString(IMessage $msg) : string {
        if ($msg->isReplying()) {
            return $msg->getReplyId();
        } else {
            return "";
        }
    }
}
