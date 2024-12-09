<?php
// Студент
namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Repos\StudentRepo::class)]
#[ORM\Table(name: 'student')]
class Student {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Связанный пользователь
    #[ORM\OneToOne(User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    // Группа студента
    #[ORM\ManyToOne(CollegeGroup::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CollegeGroup $group;
    
    // Логин от АВЕРС
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $avers_login = null;

    // Пароль от АВЕРС
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avers_password = null;

    // Предпочитаемый семестр для получения оценок
    #[ORM\ManyToOne(Period::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Period $preferenced_period = null;

    #region setters
    public function setUser(User $user) : void {
        $this->user = $user;
    }

    public function setGroup(CollegeGroup $group) : void {
        $this->group = $group;
    }

    public function setAversLogin(string $login) : void {
        $this->avers_login = $login;
    }

    public function setAversPassword(string $password) : void {
        // Пароль хэшируется алгоритмом SHA-1
        $this->avers_password = sha1($password);
    }

    public function setPreferencedPeriod(?Period $period) : void {
        $this->preferenced_period = $period;
    }
    #endregion

    #region getters
    public function getUser() : User {
        return $this->user;
    }

    public function getGroup() : ?CollegeGroup {
        return $this->group;
    }
    
    public function getAversLogin() : ?string {
        return $this->avers_login;
    }
    
    public function getAversPassword() : ?string {
        return $this->avers_password;
    }
    
    public function getPreferencedPeriod() : ?Period {
        return $this->preferenced_period;
    }
    #endregion
}
