<?php
// Сущность пользователя мессенджера
// Критический файл для BotKit, не удаляйте!

namespace BotKit\Entities;

use BotKit\Enums\State;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "UserRepo")]
#[ORM\Table(name: 'user')]
class User {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Платформа мессенджера
    #[ORM\ManyToOne(Platform::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Platform $platform;

    // ID на платформе
    #[ORM\Column(type: 'string')]
    private string $id_on_platform;

    // Состояние пользователя
    #[ORM\Column(type: 'integer')]
    private int $state;

    // Тип аккаунта
    // 0 - неопределено
    // 1 - студент
    // 2 - препод
    // 3 - переход между типом
    // 4 - когда не зарегистрировался до конца
    #[ORM\Column(type: 'integer')]
    private int $account_type = 0;

    // Разрешены ли уведомления
    #[ORM\Column(type: 'integer')]
    private int $notifications_allowed = 0;

    #region getters
    // Возвращает ID пользователя в БД
    public function getId() : int {
        return $this->id;
    }

    // Возвращает состояние
    public function getState() : State {
        return State::from($this->state);
    }

    // Возвращает название состояния в котором находится пользователь
    public function getStateName() : string {
        return serialize(State::from($this->state));
    }

    // Возвращает платформу
    public function getPlatform() : Platform {
        return $this->platform;
    }
    
    // Возвращает тип аккаунта
    public function getAccountType() : int {
        return $this->account_type;
    }

    // Возвращает значение можно ли пользователю присылать уведомления
    public function notificationsAlloed() : bool {
        return $this->notifications_allowed == 1;
    }

    public function getIdOnPlatform() : string {
        return $this->id_on_platform;
    }
    #endregion

    #region setters
    // Устанавливает ID на платформе
    public function setIdOnPlatform(string $id_on_platform) : void {
        $this->id_on_platform = $id_on_platform;
    }

    // Устанавливает платформу
    public function setPlatform(Platform $platform) : void {
        $this->platform = $platform;
    }

    // Устанавливает состояние через перечисление
    public function setState(State $new_state) : void {
        $this->state = $new_state->value;
    }

    // Устанавливает состояние через int
    public function setStateByInt(int $new_state) : void {
        $this->state = $new_state;
    }

    public function setAccountType(int $type) : void {
        $this->account_type = $type;
    }

    public function setNotificationsAllowed(bool $allowed) : void {
        $this->notifications_allowed = (int)$allowed;
    }
    #endregion

    #region other
    // Возвращает true если состояние пользователя совпадает со $state
    public function inState(State $state) : bool {
        return $state->value == $this->state;
    }

    // Возвращает true если пользователь - студент
    public function isStudent() : bool {
        return $this->account_type == 1;
    }

    // Возвращает true если пользователь - препод
    public function isTeacher() : bool {
        return $this->account_type == 2;
    }
    #endregion
}
