<?php
// Контроллер расписаний

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Models\Attachments\PhotoAttachment;
use BotKit\Database;

use BotKit\Entities\Schedule;
use BotKit\Entities\Student;
use BotKit\Entities\Pair;
use BotKit\Entities\CollegeGroup;

use BotKit\Enums\CallbackType;
use BotKit\Enums\ImageCacheType;

use Texbot\GenericImagen;
use function Texbot\getWaitMessage;
use function Texbot\getDoneText;
use function Texbot\createCache;
use function Texbot\getCache;

use IntlDateFormatter;
use DateTime;

class ScheduleController extends Controller {

    private function sendRasp(CollegeGroup $group, $date) {
        // TODO: cache

        // Просим подождать
        $this->editAssociatedMessage(getWaitMessage());
        
        // Ищем расписание
        $em = Database::getEm();
        $s = $em->getRepository(Schedule::class)->findSchedule($group, $date);

        if ($s === null) {
            $this->editAssociatedMessage(M::create(
            "❌ Расписание группы ".
            $group->getHumanName().
            " на запрошенную дату не найдено"));
            return;
        }

        // Собираем пары в матрицу
        // Время | Название | Место проведения
        $p = $em->getRepository(Pair::class)->getPairsOfScheduleForGroup($s);
        if (count($p) == 0) {
            $this->editAssociatedMessage(M::create(
            "Видимо, пар у группы на этот день нет"));
            return;
        }

        $matrix = [];
        foreach ($p as $pair) {
            $details = $pair->getConductionDetails();
            $details_texts = [];

            foreach ($details as $detail) {
                $employee = $detail->getEmployee();
                $place = $detail->getPlace();

                if ($place === null) {
                    $details_texts[] = $employee->getSurname();
                } else {
                    $details_texts[] = $employee->getSurname().' '.$place->getName();
                }
            }
            
            $matrix[] = [
                $pair->getTime()->format('H:i'),
                $pair->getPairNameAsText(),
                implode(' / ', $details_texts)
            ];
        }

        $fmt = new IntlDateFormatter(
            'ru_RU',
            IntlDateFormatter::RELATIVE_MEDIUM,
            IntlDateFormatter::NONE,
            'Europe/Kirov',
            IntlDateFormatter::GREGORIAN
        );
        $date_string = $fmt->format(DateTime::createFromFormat('Y-m-d', $date));

        $filename = GenericImagen::generateTable(
            $matrix,
            ['Время', 'Дисциплина', 'Детали проведения'],
            'Расписание группы '.$group->getHumanName().' на '.$date_string,
            [0, 20, 30],
            25
        );
        
        $m = M::create(getDoneText(true));
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $this->editAssociatedMessage($m);
    }

    // Показывает расписание текущего студента
    public function currentStudentRasp($date) {
        $user_obj = $this->u->getEntity();
        $em = Database::getEm();
        
        if ($user_obj->isStudent()) {
            // У текущего студента получаем его группу
            $student_obj = $em->getRepository(Student::class)->findOneBy(
                ['user' => $this->u->getEntity()]
            );
            $this->sendRasp($student_obj->getGroup(), $date);
            return;
        }

        $this->replyText("❌ Ты не студент, либо не зарегистрировался");
    }
}