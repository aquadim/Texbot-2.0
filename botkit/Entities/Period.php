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
    
    public function getId() {
        return $this->id;
    }
    
    public function getAversId() : int {
        return $this->avers_id;
    }
    
    public function getOrdNumber() : int {
        return $this->ord_number;
    }
    
    public function getHumanName() : string {
        switch ($this->ord_number) {
            case 1:
                $greek_num = 'I';
                break;
            case 2:
                $greek_num = 'II';
                break;
            case 3:
                $greek_num = 'III';
                break;
            case 4:
                $greek_num = 'IV';
                break;
            case 5:
                $greek_num = 'V';
                break;
            case 6:
                $greek_num = 'VI';
                break;
            case 7:
                $greek_num = 'VII';
                break;
            case 8:
                $greek_num = 'VIII';
                break;
            default:
                $greek_num = $this->ord_number;
        }
        return $greek_num.' семестр';
    }
}
