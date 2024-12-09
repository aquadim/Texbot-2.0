<?php
// Группа студентов
namespace BotKit\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Repos\CollegeGroupRepo::class)]
#[ORM\Table(name: 'college_group')]
class CollegeGroup {
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    // Специальность группы
    #[ORM\ManyToOne(CollegeSpec::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CollegeSpec $spec;

    // Номер курса группы
    #[ORM\Column(type: 'integer')]
    private int $course_num;
    
    // Год поступления
    #[ORM\Column(type: 'integer')]
    private int $enrolled_at;

    public function getId() {
        return $this->id;
    }

    public function getSpec() {
        return $this->spec;
    }

    public function setSpec(CollegeSpec $spec) {
        $this->spec = $spec;
    }
    
    public function getCourseNum() : int {
        return $this->course_num;
    }

    public function setCourseNum(int $num) {
        $this->course_num = $num;
    }
    
    public function getEnrolledAt() : int {
        return $this->enrolled_at;
    }

    public function setEnrolledAt(int $enrolled_at) {
        $this->enrolled_at = $enrolled_at;
    }
    
    public function getHumanName() : string {
        return $this->course_num.' '.$this->spec->getName();
    }
}
