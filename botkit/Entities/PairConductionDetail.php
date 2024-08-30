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
    #[ORM\ManyToOne(Pair::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Pair $pair;

    // Какой преподаватель
    #[ORM\ManyToOne(Employee::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Employee $employee = null;

    // В каком месте
    #[ORM\ManyToOne(Place::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Place $place = null;

    #region getters
    public function getEmployee() : ?Employee {
        return $this->employee;
    }

    public function getPlace() : ?Place {
        return $this->place;
    }

    public function getPair() : Pair {
        return $this->pair;
    }
    #enregion
    
    #region setters
    public function setPair(Pair $pair) : void {
        $this->pair = $pair;
    }
    
    public function setEmployee(?Employee $employee) : void {
        $this->employee = $employee;
    }

    public function setPlace(?Place $place) : void {
        $this->place = $place;
    }
    #enregion
}
