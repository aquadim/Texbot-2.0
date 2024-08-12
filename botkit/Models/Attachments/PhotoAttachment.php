<?php
// Изображение, отправляемое вместе с сообщением

namespace BotKit\Models\Attachments;
use BotKit\Enums\PhotoAttachmentType;

class PhotoAttachment {
    
    // Тип загрузки изображения
    private PhotoAttachmentType $type;
    
    // Значение для загрузки -- url, название файла и т.д.
    private $value;
    
    // id загрузки
    private $id;
    
    public function __construct(PhotoAttachmentType $type, $value) {
        $this->type = $type;
        $this->value = $value;
        
        if ($type == PhotoAttachmentType::FromUploaded) {
            $this->id = $value;
        }
    }
    
    public static function fromFile(string $filename) : PhotoAttachment {
        return new PhotoAttachment(
            PhotoAttachmentType::FromFile,
            $filename
        );
    }
    
    public static function fromURL(string $url) : PhotoAttachment {
        return new PhotoAttachment(
            PhotoAttachmentType::FromURL,
            $url
        );
    }
    
    public static function fromUploaded(string $id) : PhotoAttachment {
        return new PhotoAttachment(
            PhotoAttachmentType::FromUploaded,
            $id
        );
    }
    
    public function getType() : PhotoAttachmentType {
        return $this->type;
    }
    
    public function getValue() {
        return $this->value;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) : void {
        $this->id = $id;
    }
}