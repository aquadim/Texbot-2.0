<?php
// Название пары

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pair_name')]
class PairName {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Собственно имя
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
