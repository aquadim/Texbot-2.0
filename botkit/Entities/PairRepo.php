<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;

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

        $pairs = $query->getResult();

        return $pairs;
    }
}
