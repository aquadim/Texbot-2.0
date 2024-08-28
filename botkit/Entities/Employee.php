<?php
// Сотрудник техникума

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "EmployeeRepo")]
#[ORM\Table(name: 'employee')]
class Employee {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Фамилия
    #[ORM\Column(type: 'string', length: 50)]
    private string $surname;

    // Имя
    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    // Отчество
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $patronymic = null;

    #region getters
    public function getId() : ?int {
        return $this->id;
    }
    
    public function getSurname() : string {
        return $this->surname;
    }
    
    public function getName() : string {
        return $this->name;
    }
    
    public function getPatronymic() : ?string {
        return $this->patronymic;
    }
    #endregion

    #region setters
    public function setSurname(string $surname) : void {
        $this->surname = $surname;
    }
    
    public function setName(string $name) : void {
        $this->name = $name;
    }
    
    public function setPatronymic(?string $patronymic) : void {
        $this->patronymic = $patronymic;
    }
    #endregion

    // Возвращает имя формата "Королёв В. С."
    public function getNameWithInitials() : string {
        return $this->surname." ".mb_substr($this->name, 0, 1).". ".mb_substr($this->patronymic, 0, 1).".";
    }
}
