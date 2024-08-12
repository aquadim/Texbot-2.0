<?php
// Типы загрузки фото

namespace BotKit\Enums;

enum PhotoAttachmentType {
    // Изображение будет загружено из файловой системы
	case FromFile;
    
    // Изображение будет загружено по URL
    case FromURL;
    
    // Изображение уже загружено на платформу, будет загружено по id
    case FromUploaded;
}