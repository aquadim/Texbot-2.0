<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;

class CollegeGroupRepo extends EntityRepository {

    // Возвращает группы по курсу
    public function getAllByCourse(int $num) {
        $query = $this->getEntityManager()->createQuery(
            'SELECT g FROM '. CollegeGroup::class .' g '.
            'WHERE g.course_num=:course_num '
        );
        $query->setParameters([
            'course_num' => $num
        ]);
        return $query->getResult();
    }
}
