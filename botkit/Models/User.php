<?php
// Модель пользователя
// Хранит в себе объект базы данных

namespace BotKit\Models;

use BotKit\Entities\User as UserEntity;
use BotKit\Enums\State;
use BotKit\Bot;

class User {

    // Ленивая загрузка
    private $lazy_username; // Имя пользователя на платформе. Например: Вадим Королёв
    private $lazy_nickname; // Ник пользователя на платформе. Например: aquadim

    public function __construct(
        protected UserEntity $entity_obj,
        protected string $id_on_platform,
    ) {}

    public function getIdOnPlatform() : string {
        return $this->id_on_platform;
    }

    public function getUsername() {
        if (!isset($this->lazy_username)) {
            $this->lazy_username = Bot::getCurrentDriver()->getUserName();
        }
        return $this->lazy_username;
    }

    public function getNickname() {
        if (!isset($this->lazy_nickname)) {
            $this->lazy_nickname = Bot::getCurrentDriver()->getNickName();
        }
        return $this->lazy_nickname;
    }

    public function getState() : State {
        return $this->entity_obj->getState();
    }
    
    public function setState(State $state) : void {
        $this->entity_obj->setState($state);
    }
    
    public function getEntity() : UserEntity {
        return $this->entity_obj;
    }
}
