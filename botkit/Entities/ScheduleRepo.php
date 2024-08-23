<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;

class ScheduleRepo extends EntityRepository {

    // Возвращает расписание для группы на определённую дату
    // $group - группа
    // $date - на какое время запрашивается
    public function findSchedule(CollegeGroup $group, $date) : ?Schedule {
        $em = $this->getEntityManager();

        $dql =
        'SELECT s FROM '.Schedule::class.' s '.
        'WHERE s.day=:day AND s.college_group=:group';
        $q_schedule = $em->createQuery($dql);
        $q_schedule->setParameters([
            'day' => $date,
            'group' => $group
        ]);
        $r_schedule = $q_schedule->getResult();

        if (count($r_schedule) == 0) {
            return null;
        }
        return $r_schedule[0];
    }
}