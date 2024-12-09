<?php
// Пары расписания на одинь день для группы
namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Repos\ScheduleRepo::class)]
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
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $day;

    // Дата и время создания записи
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    #region getters
    public function getId() {
        return $this->id;
    }
    
    public function getCollegeGroup() : CollegeGroup {
        return $this->college_group;
    }
    
    public function getDay() : \DateTimeImmutable {
        return $this->day;
    }
    #endregion

    #region setters
    public function setCollegeGroup(CollegeGroup $college_group) : void {
        $this->college_group = $college_group;
    }
    
    public function setDay(\DateTimeImmutable $day) : void {
        $this->day = $day;
    }
    
    public function setCreatedAt(\DateTimeImmutable $created_at) : void {
        $this->created_at = $created_at;
    }
    #endregion
}
