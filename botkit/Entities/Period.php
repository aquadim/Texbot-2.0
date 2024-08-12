<?php
// Семестр обучения

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'period')]
class Period {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Группа для этой записи
    #[ORM\ManyToOne(targetEntity: CollegeGroup::class)]
    #[ORM\JoinColumn(nullable: false, name: 'group_id')]
    private CollegeGroup $group;

    // Порядковый номер семестра
    #[ORM\Column(type: 'integer')]
    private int $ord_number;

    // ID семестра в системе АВЕРС
    #[ORM\Column(type: 'integer')]
    private int $avers_id;

    #region setters
    public function setGroup(CollegeGroup $group) {
        $this->group = $group;
    }

    public function setOrdNumber(int $ord_number) {
        $this->ord_number = $ord_number;
    }

    public function setAversId(int $avers_id) {
        $this->avers_id = $avers_id;
    }
    #endregion 
}
