<?php
// Программа для тестирования массовых уведомлений
// Использование: php notify.php <имя группы>
// Формат имени группы: <курс><название специальности>
// Программа отправит тестовое сообщение
// НЕ использовать в продакшене
require realpath(__DIR__ . '/../botkit/bootstrap.php');

use BotKit\Database;
use BotKit\Entities\CollegeGroup;
use BotKit\Enums\CallbackType;
use Texbot\NotificationService;

$group_spec = $argv[1];

$em = Database::getEm();
$groups_repo = $em->getRepository(CollegeGroup::class);
$group = $groups_repo->getAllByGroupSpec($group_spec);

if ($group == null) {
    echo "Группа ".$group_spec." не найдена\n";
    exit();
}

NotificationService::sendToGroup(
    $group,
    "тестовое сообщение рассылки",
    CallbackType::ViewRasp,
    "Просмотр расписания"
);