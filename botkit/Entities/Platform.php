<?php
// Сущность платформы мессенджера
// Критический файл для BotKit, не удаляйте!

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'platform')]
class Platform {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // ID платформы
    #[ORM\Column(type: 'string')]
    private string $domain;

    public function __construct(string $domain) {
        $this->domain = $domain;
    }

    #region getters
    // Возвращает ID
    public function getId() : int {
        return $this->id;
    }

    // Устанавливает ID на платформе
    public function getDomain() : string {
        return $this->domain;
    }
    #endregion

    #region setters
    // Эта сущность только для чтения
    #endregion
}