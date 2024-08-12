<?php
// Место. Кабинет в техникуме, актовый зал -- что угодно, где может
// проводиться пара.

namespace BotKit\Entities;

use BotKit\Enums\State;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'place')]
class Place {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Имя места проведения пары
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #region getters
    public function getName() : string {
        return $this->name;
    }
    #endregion
    
    #region setters
    public function setName(string $name) : void {
        $this->name = $name;
    }
    #endregion
    
    
}
