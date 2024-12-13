<?php

namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use BotKit\Entities\CollegeGroup;
use BotKit\Entities\Schedule;

class ScheduleRepo extends EntityRepository {

    // Возвращает расписание для группы на определённую дату
    // Расписание возвращается самое последнее созданное
    // $group - группа
    // $date - на какое время запрашивается
    public function findSchedule(CollegeGroup $group, $date) : ?Schedule {
        $em = $this->getEntityManager();

        $dql =
        'SELECT s FROM '.Schedule::class.' s '.
        'WHERE s.day=:day AND s.college_group=:group '.
        'ORDER BY s.created_at DESC';
        $q_schedule = $em->createQuery($dql);
        $q_schedule->setParameters([
            'day' => $date,
            'group' => $group
        ]);
        $q_schedule->setMaxResults(1);
        $r_schedule = $q_schedule->getResult();

        if (count($r_schedule) == 0) {
            return null;
        }
        return $r_schedule[0];
    }
}