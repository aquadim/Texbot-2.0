<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;
use DateTimeImmutable;

class PairRepo extends EntityRepository {

    // Возвращает пары расписания
    public function getPairsOfScheduleForGroup(Schedule $schedule) {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
        'SELECT p, pn, cd FROM '.Pair::class.' p '.
        'JOIN p.pair_name pn '.
        'JOIN p.conduction_details cd '.
        'WHERE p.schedule=:schedule '
        );

        $query->setParameters(['schedule'=>$schedule]);

        return $query->getResult();
    }

    // Ищет пары преподавателя на заданный день
    // $e - сущность сотрудника
    // $date - дата в формате ГГГГ-ММ-ДД
    public function getPairsOfTeacher(Employee $e, $date) {
        $em = $this->getEntityManager();
        
        $query = $em->createQuery(
        'SELECT pcd, p, pn, s, g '.
        'FROM '.PairConductionDetail::class.' pcd '.
        'JOIN pcd.pair p '.
        'JOIN p.pair_name pn '.
        'JOIN p.schedule s '.
        'JOIN s.college_group g '.
        'WHERE s.day=:date '.
        'AND pcd.employee=:employee'
        );

        $query->setParameters([
            'employee'=>$e,
            'date'=>$date
        ]);

        return $query->getResult();
    }
}
