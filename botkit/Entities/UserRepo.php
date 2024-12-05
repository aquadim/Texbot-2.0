<?php
namespace BotKit\Entities;
use Doctrine\ORM\EntityRepository;

class UserRepo extends EntityRepository {

    // Возвращает пользователей-студентов Техбота у которых группа - $group
    // и которые разрешили уведомления
    public function getStudentsForNotification(CollegeGroup $group) {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
        'SELECT s, u, p FROM '. Student::class .' s '.
        'JOIN s.user u '.
        'JOIN u.platform p '.
        'WHERE s.group=:group '.
        'AND u.notifications_allowed=1'
        );

        $query->setParameters(['group'=>$group]);

        return $query->getResult();
    }
}
