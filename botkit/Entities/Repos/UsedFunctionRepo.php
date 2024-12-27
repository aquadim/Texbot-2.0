<?php
namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use BotKit\Entities\UsedFunction as UF;
use BotKit\Entities\CollegeGroup;
use BotKit\Enums\FunctionNames;
use BotKit\Entities\TexbotFunction;
use BotKit\Exceptions\DatabaseException;

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

    // Добавляет запись статистики для заданной группы.
    public function addStat(
        FunctionNames $function_type,
        CollegeGroup $called_for
    ) {
        $em = $this->getEntityManager();
        $tb_repo = $em->getRepository(TexbotFunction::class);

        // 1. Поиск функции по наименованию
        $function_name = $function_type->value;
        $func = $tb_repo->findOneBy(["name" => $function_name]);
        if ($func == null) {
            throw new DatabaseException("При создании записи статистики не найдена функция $function_name");
            return;
        }
        
        $stat = new UF();
        $stat->setFunction($func);
        $stat->setUsedAt(new DateTimeImmutable("now"));
        $stat->setCallerGroup($called_for);

        $em->persist($stat);
        $em->flush();
    }
}
