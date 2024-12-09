<?php
// Использованная функция. Хранит время, группу и саму функцию
namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: Repos\UsedFunctionRepo::class)]
#[ORM\Table(name: 'used_function')]
class UsedFunction {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Какая функция использована
    #[ORM\ManyToOne(TexbotFunction::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TexbotFunction $fn;

    // Дата использования. Компонент времени только 00:00:00
    #[ORM\Column(type: 'datetime_immutable', name: 'used_at')]
    private \DateTimeImmutable $used_at;

    // Из какой группы использована
    #[ORM\ManyToOne(CollegeGroup::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CollegeGroup $caller_group;

    public function __construct(TexbotFunction $fn, CollegeGroup $caller_group) {
        $this->fn = $fn;
        $this->caller_group = $caller_group;
        $this->used_at = \DateTimeImmutable::now()->setTime(0,0,0);
    }
    
    #region getters
    public function getFunction() : TexbotFunction {
        return $this->fn;
    }
    
    public function getUsedAt() : \DateTimeImmutable {
        return $this->used_at;
    }

    public function getCallerGroup() : CollegeGroup {
        return $this->caller_group;
    }
    #endregion
    
    #region setters
    public function setFunction(TexbotFunction $fn) : void {
        $this->fn = $fn;
    }
    
    public function setUsedAt(\DateTimeImmutable $used_at) : void {
        $this->used_at = $used_at->setTime(0,0,0);
    }

    public function setCallerGroup(CollegeGroup $group) : void {
        $this->caller_group = $group;
    }
    #endregion
}
