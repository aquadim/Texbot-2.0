<?php
// Сервис уведомлений Техбота
namespace Texbot;

use BotKit\Database;

use BotKit\Entities\User as UserEntity;
use BotKit\Entities\CollegeGroup;

use BotKit\Drivers\VkComDriver;
use BotKit\Drivers\TelegramOrgDriver;

use BotKit\Models\User as UserModel;
use BotKit\Models\Messages\TextMessage as M;

use BotKit\Keyboards\NotificationKeyboard;

use BotKit\Enums\CallbackType;

class NotificationService {

    // Отсылает сообщение всем студентам группы
    // Возвращает количество отправленных сообщений
    // $group - группа техникума которой нужно отправить
    // $message - текст сообщения
    // $buttonCallbackType - тип доп. кнопки если нужен
    // $buttonText - текст доп. кнопки
    public static function sendToGroup(
        CollegeGroup $group,
        string $message,
        ?CallbackType $buttonCallbackType = null,
        ?array $callback_params = null,
        ?string $buttonText = null
    ) : int {

        $em = Database::getEm();
        $repo = $em->getRepository(UserEntity::class);
        $students_to_send = $repo->getStudentsForNotification($group);

        $vkcom_driver = new VkComDriver();
        $telegramorg_driver = new TelegramOrgDriver();

        $vk_users = [];
        $tg_users = [];
        foreach ($students_to_send as $student) {
            $user               = $student->getUser();
            $id_on_platform     = $user->getIdOnPlatform();
            $platform           = $user->getPlatform();

            if ($platform->getDomain() == "vk.com") {
                $vk_users[] = new UserModel($user, $id_on_platform);
            } else if ($platform->getDomain() == "telegram.org") {
                $tg_users[] = new UserModel($user, $id_on_platform);
            }
        }

        $msg = M::create($message);
        $kb = new NotificationKeyboard(
            $buttonCallbackType,
            $callback_params,
            $buttonText
        );
        $msg->setKeyboard($kb);

        try {
            $vkcom_driver->massSend($vk_users, $msg);
            $telegramorg_driver->massSend($tg_users, $msg);
        } catch (\Exception $e) {
            // pass
        }
        

        return count($vk_users) + count($tg_users);
    }
}