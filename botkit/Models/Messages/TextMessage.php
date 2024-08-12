<?php
// Класс исходящего сообщения

namespace BotKit\Models\Messages;

use BotKit\Models\Attachments\PhotoAttachment;
use BotKit\Models\Chats\IChat;
use BotKit\Models\Keyboards\IKeyboard;

class TextMessage implements IMessage {

	// ID сообщения
	protected string $id;
    // ID сообщения, на которое сообщение отвечает
    protected string $reply_to;
    // true, если сообщение отвечает на какое-либо
    protected bool $is_replying;
	// Текст сообщения
	protected string $text;
	// Вложения: изображения в сообщении
	protected array $photos;
	// Клавиатура
	protected ?IKeyboard $keyboard;
	// Чат, в который было отправлено сообщение
	protected IChat $chat;

	public function __construct($text, $photos) {
		$this->text = $text;
		$this->photos = $photos;
		$this->keyboard = null;
        $this->is_replying = false;
	}

	// Создаёт сообщение с текстом $text
	public static function create(string $text) : TextMessage {
		return new TextMessage($text, []);
	}
	
	public function setId(string $id) : void {
		$this->id = $id;
	}

	public function getId() : string {
		return $this->id;
	}
	
	public function setText(string $text) : void {
		$this->text = $text;
	}

	public function getText() : string {
		return $this->text;
	}
	
	public function getPhotos() : array {
		return $this->photos;
	}

	public function addPhoto(PhotoAttachment $photo) : void {
		$this->photos[] = $photo;
	}
	
	public function setKeyboard(IKeyboard $keyboard) : void {
		$this->keyboard = $keyboard;
	}
	
	public function getKeyboard() : ?IKeyboard {
		return $this->keyboard;
	}
	
	public function setChat(IChat $chat) : void {
		$this->chat = $chat;
	}
	
	public function getChat() : IChat {
		return $this->chat;
	}
    
    public function setReplyId(string $message_id) : void {
        $this->reply_to = $message_id;
        $this->is_replying = true;
    }
    
    public function setReplyMessage(IMessage $msg) : void {
        $this->reply_to = $msg->getId();
        $this->is_replying = true;
    }
    
    public function getReplyId() : string {
        return $this->reply_to;
    }
    
    public function isReplying() : bool {
        return $this->is_replying;
    }
}
