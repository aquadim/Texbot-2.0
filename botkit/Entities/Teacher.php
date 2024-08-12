<?php
// Преподаватель, пользователь Техбота

namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'teacher')]
class Teacher {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Связанный пользователь
    #[ORM\OneToOne(User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    // Связанный сотрудник
    #[ORM\ManyToOne(Employee::class)]
    private ?Employee $employee = null;
    
    #region getters
    public function getUser() : User {
        return $this->user;
    }
    
    public function getEmployee() : Employee {
        return $this->employee;
    }
    #endregion
    
    #region setters
    public function setUser(User $user) : void {
        $this->user = $user;
    }
    
    public function setEmployee(Employee $employee) : void {
        $this->employee = $employee;
    }
    #endregion
}
