<?php

namespace BotKit\Entities;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class EmployeeRepo extends EntityRepository {

    // Возвращает элементы страницы выбора преподов
    // $group - группа
    // $date - на какое время запрашивается
    public function getPageElements(string $platform, int $offset) : Paginator {

        switch ($platform) {
        case 'vk.com':
            $max_results = 6;
            break;
        default:
            $max_results = 6;
            break;
        }
        
        $em = $this->getEntityManager();
        $q = $em->createQuery('SELECT e FROM '.Employee::class.' e ORDER BY e.surname ASC');
        $q->setFirstResult($offset);
        $q->setMaxResults($max_results);

        return new Paginator($q, fetchJoinCollection: false);
    }
}