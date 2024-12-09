<?php
namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use \DateTimeImmutable;
use BotKit\Entities\UsedFunction as UF;

class UsedFunctionRepo extends EntityRepository {

    // Возвращает статистику использования функций
    public function getStats(DateTimeImmutable $start, DateTimeImmutable $end) {
        $dql =
        "SELECT COUNT(uf.id), fn.name, spec.name, uf.used_at " .
        "FROM " . UF::class . " uf ".
        "JOIN uf.fn fn " .
        "JOIN uf.caller_group gr " .
        "JOIN gr.spec spec " .
        "WHERE uf.used_at BETWEEN :start AND :end ".
        "GROUP BY uf.caller_group, uf.used_at ";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters([
            'start' => $start,
            'end' => $end
        ]);
        return $query->getResult();
    }
}
