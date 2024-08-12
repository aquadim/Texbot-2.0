<?php
// Пары расписания на одинь день для группы

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "ScheduleRepo")]
#[ORM\Table(name: 'schedule')]
class Schedule {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Группа, связанная с расписанием
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(CollegeGroup::class)]
    private CollegeGroup $college_group;

    // День расписания
    #[ORM\Column(type: 'date')]
    private \DateTime $day;

    #region getters
    public function getId() {
        return $this->id;
    }
    
    public function getCollegeGroup() : CollegeGroup {
        return $this->college_group;
    }
    
    public function getDay() : \DateTime {
        return $this->day;
    }
    #endregion

    #region setters
    public function setCollegeGroup(CollegeGroup $college_group) : void {
        $this->college_group = $college_group;
    }
    
    public function setDay(\DateTime $day) : void {
        $this->day = $day;
    }
    #endregion
}
