<?php
// Кэш изображений

namespace BotKit\Entities;

use BotKit\Enums\ImageCacheType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'image_cache')]
class ImageCache {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Тип изображения (оценки/расписание/...) см. BotKit\Enums\ImageCacheType
    #[ORM\Column(type: 'integer')]
    private int $cache_type;

    // Ключ
    #[ORM\Column(type: 'integer')]
    private int $search;

    // Значение
    #[ORM\Column(type: 'string', length: 64)]
    private string $value;
    
    #region setters
    public function setCacheType(ImageCacheType $cache_type) {
        $this->cache_type = $cache_type->value;
    }
    
    public function setSearch(int $search) {
        $this->search = $search;
    }
    
    public function setValue(string $value) {
        $this->value = $value;
    }
    #endregion

    #region getters
    public function getValue() : string {
        return $this->value;
    }
    #endregion
}
