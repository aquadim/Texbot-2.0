<?php
// Связная сущность - хранит в себе информацию о проведении пары.
// Необходимо в случаях когда одна пара проводится сразу в нескольких 
// местами несколькими преподавателями.
// Например: английский язык может быть одновременно в двух подгруппах
// сразу, в таком случае для этой пары два преподавателя (так же как и
// два места)

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pair_conduction_detail')]
class PairConductionDetail {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Для какой пары
    #[ORM\ManyToOne(Pair::class, inversedBy: 'pair')]
    #[ORM\JoinColumn(nullable: false)]
    private Pair $pair;

    // Какой преподаватель
    #[ORM\ManyToOne(Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $employee;

    // В каком месте
    #[ORM\ManyToOne(Place::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Place $place;

    #region getters
    public function getEmployee() : Employee {
        return $this->employee;
    }

    public function getPlace() : Place {
        return $this->place;
    }
    #enregion
    
    #region setters
    public function setEmployee(Employee $employee) : void {
        $this->employee = $employee;
    }

    public function setPlace(Place $place) : void {
        $this->place = $place;
    }
    #enregion
}
