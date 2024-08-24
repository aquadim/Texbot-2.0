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
use function Texbot\getConductionDetailsAsText;

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
            $matrix[] = [
                $pair->getTime()->format('H:i'),
                $pair->getPairNameAsText(),
                getConductionDetailsAsText($pair->getConductionDetails())
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
            [0, 40, 30],
            25
        );
        
        $m = M::create(getDoneText(true));
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $this->editAssociatedMessage($m);
    }

    // Показывает расписание текущего студента
    // $data - пустой массив
    public function currentStudentRasp($date, $data) {
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

    // 4 шаг при показе расписания другой группы
    // $date - дата расписания
    // $data - ["group_id" - id группы на которую нужно показать расписание]
    public function groupRasp($date, $data) {
        $em = Database::getEm();
        $group_id = $data['group_id'];
        $group = $em->find(CollegeGroup::class, $group_id);
        $this->sendRasp($group, $date);
    }
}