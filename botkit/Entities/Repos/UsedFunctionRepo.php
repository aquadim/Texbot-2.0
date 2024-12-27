<?php
namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use \DateTimeImmutable;
use BotKit\Entities\UsedFunction as UF;
use BotKit\Entities\CollegeGroup;

class UsedFunctionRepo extends EntityRepository {

    // Возвращает статистику использования функций
    public function getStats(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        CollegeGroup $group
    ) {
        $dql =
        "SELECT COUNT(uf.id) AS cnt, fn.id AS fnid, uf.used_at " .
        "FROM " . UF::class . " uf ".
        "JOIN uf.fn fn " .
        "JOIN uf.caller_group gr " .
        "WHERE uf.used_at BETWEEN :start AND :end " .
        "AND uf.caller_group = :callerGroup " .
        "GROUP BY uf.used_at, fn.id";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters([
            'start' => $start,
            'end' => $end,
            'callerGroup' => $group
        ]);
        return $query->getResult();
    }
}
