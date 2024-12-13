<?php
namespace BotKit\Entities\Repos;

use Doctrine\ORM\EntityRepository;
use BotKit\Entities\User;

class StudentRepo extends EntityRepository {

    // Возвращает студента по связанному пользователю
    public function findByUser(User $user) {
        $students = $this->createQueryBuilder('student')
            ->andWhere('student.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $students[0];
    }
    
}