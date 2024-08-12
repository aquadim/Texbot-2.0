<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;

class ScheduleRepo extends EntityRepository {

    // Возвращает расписание для группы на определённую дату
    // $group - группа
    // $asked_at - на какое время запрашивается
    public function findForGroupAtDate(CollegeGroup $group, $asked_at) {
        $day = date("Y-m-d", strtotime("today", $asked_at));
        
        $schedule = $this->createQueryBuilder('schedule')
        ->andWhere('schedule.college_group = :group')
        ->setParameter('group', $group)
        ->andWhere('schedule.day = :day')
        ->setParameter('day', $day)
        ->getQuery()
        ->execute();

        if (count($schedule) == 0) {
            // Не найдено расписание
            return null;
        }

        return $schedule[0];
    }
    
}