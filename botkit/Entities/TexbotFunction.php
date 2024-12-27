<?php
// Функция Техбота
namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'texbot_func')]
class TexbotFunction {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Название функции
    #[ORM\Column(type: 'string')]
    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    #region getters
    public function getId() : int {
        return $this->id;
    }

    public function getName() : string {
        return $this->name;
    }
    #endregion

    #region setters
    // Эта сущность только для чтения
    #endregion
}