<?php
// Отчёт об ошибке

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'error_reports')]
class ErrorReport {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Пользователь, отправивший отчёт
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    // Описание проблемы
    #[ORM\Column(type: 'string')]
    private string $description;

    // Шаги воспроизведения
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $steps_to_reproduce = null;

    // Время создания
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created_at;

    #region setters
    public function setUser(User $user) : void {
        $this->user = $user;
    }

    public function setDescription(string $description) : void {
        $this->description = $description;
    }

    public function setStepsToReproduce(string $steps_to_reproduce) : void {
        $this->steps_to_reproduce = $steps_to_reproduce;
    }
    
    public function setCreatedAt(DateTimeImmutable $created_at) : void {
        $this->created_at = $created_at;
    }
    #endregion 

    #region getters
    public function getId() {
        return $this->id;
    }

    public function getUser() : User {
        return $this->user;
    }
    
    public function getDescription() : string {
        return $this->description;
    }
    
    public function getStepsToReproduce() : string {
        return $this->steps_to_reproduce;
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->created_at;
    }
    #endregion
}
