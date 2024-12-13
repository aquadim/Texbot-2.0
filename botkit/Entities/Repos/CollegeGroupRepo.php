<?php

namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use use BotKit\Entities\CollegeGroup;

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

    // Возвращает группы по формату группы
    // Возвращает null если не найдено
    // Формат группы: <курс><название специальности>
    public function getAllByGroupSpec(string $group_spec) : CollegeGroup|null {
        if (mb_strlen($group_spec) < 2) {
            return null;
        }
        $num = intval($group_spec[0]);
        $spec_name = mb_substr($group_spec, 1);

        $query = $this->getEntityManager()->createQuery(
            'SELECT g FROM '. CollegeGroup::class .' g '.
            'JOIN g.spec s '.
            'WHERE g.course_num=:course_num '.
            'AND s.name=:specName'
        );
        $query->setParameters([
            'course_num' => $num,
            'specName' => $spec_name
        ]);
        $r = $query->getResult();

        if (count($r) == 0) {
            return null;
        } else {
            return $r[0];
        }
    }
}
