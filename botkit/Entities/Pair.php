<?php
// Пара

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: "PairRepo")]
#[ORM\Table(name: 'pair')]
class Pair {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Для какого расписания
    #[ORM\ManyToOne(Schedule::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Schedule $schedule;

    // Время проведения
    #[ORM\Column(type: 'time', name: 'ptime')]
    private \Datetime $time;

    // Название пары
    #[ORM\ManyToOne(PairName::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PairName $pair_name;

    // Детали проведения
    #[ORM\OneToMany(targetEntity: PairConductionDetail::class, mappedBy: 'pair')]
    private Collection $conduction_details;

    public function __construct() {
        $this->conduction_details = new ArrayCollection();
    }
    
    #region getters
    public function getSchedule() : Schedule {
        return $this->schedule;
    }
    
    public function getTime() : \Datetime {
        return $this->time;
    }

    public function getPairName() : PairName {
        return $this->pair_name;
    }

    public function getConductionDetails() {
        return $this->conduction_details;
    }
    #endregion
    
    #region setters
    public function setSchedule(Schedule $schedule) : void {
        $this->schedule = $schedule;
    }
    
    public function setTime(\Datetime $time) : void {
        $this->time = $time;
    }

    public function setPairName(PairName $pair_name) : void {
        $this->pair_name = $pair_name;
    }

    public function setConductionDetails(Collection $conduction_details) : void {
        $this->conduction_details = $conduction_details;
    }
    #endregion
    
    public function getPairNameAsText() : string {
        return $this->pair_name->getName();
    }
}
