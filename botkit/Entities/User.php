<?php
// Сущность пользователя мессенджера
// Критический файл для BotKit, не удаляйте!

namespace BotKit\Entities;

use BotKit\Enums\State;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
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
    #endregion

    #region other
    // Возвращает true если состояние пользователя совпадает со $state
    public function inState(State $state) : bool {
        return $state->value == $this->state;
    }
    #endregion
}
