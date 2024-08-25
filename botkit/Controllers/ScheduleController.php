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
use BotKit\Entities\Employee;
use BotKit\Entities\Teacher;

use BotKit\Keyboards\SelectDateKeyboard;

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

    // Собирает пары ГРУППЫ, рисует расписание и отправляет пользователю
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

        // Ищем кэш
        $cached = getCache(
            ImageCacheType::GroupSchedule,
            $this->u->getEntity()->getPlatform(),
            $group->getId().'-'.$date
        );
        
        if ($cached !== null) {
            // Кэш найден! Отправляем сообщение
            $m = M::create(getDoneText());
            $m->addPhoto($cached);
            $this->editAssociatedMessage($m);
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
        
        $m = M::create(getDoneText());
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $this->editAssociatedMessage($m);

        createCache(
            ImageCacheType::GroupSchedule,
            $this->u->getEntity()->getPlatform(),
            $group->getId().'-'.$date,
            $m->getPhotos()[0]->getId()
        );
    }

    // Собирает пары ПРЕПОДА, рисует расписание и отправляет пользователю
    private function sendTeacherRasp(Employee $employee, $date) {
        // TODO: cache

        // Просим подождать
        $this->editAssociatedMessage(getWaitMessage());
        
        // Ищем расписание
        $em = Database::getEm();
        $pairs = $em->getRepository(Pair::class)->getPairsOfTeacher(
            $employee, $date
        );

        if (count($pairs) == 0) {
            $this->editAssociatedMessage(M::create(
            "❌ Для преподавателя ".
            $employee->getNameWithInitials().
            " на запрошенную дату не найдено пар"));
            return;
        }

        // Собираем пары в матрицу
        // Время | Название | Группа | Место проведения
        $matrix = [];
        foreach ($pairs as $pair_info) {
            $pair = $pair_info->getPair();
            $group = $pair->getSchedule()->getCollegeGroup();
            $place = $pair_info->getPlace();
            $place_text = $place ? $place->getName() : 'нет данных';

            $matrix[] = [
                $pair->getTime()->format('H:i'),
                $pair->getPairName()->getName(),
                $group->getHumanName(),
                $place_text
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
            ['Время', 'Дисциплина', 'Группа', 'Место проведения'],
            'Расписание преподавателя '.$employee->getNameWithInitials().
            ' на '.$date_string,
            [0, 40, 0, 30],
            30
        );
        
        $m = M::create(getDoneText());
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $this->editAssociatedMessage($m);
    }

    // Показывает расписание текущего студента
    // $data - пустой массив
    public function currentStudentRasp($date, $data) {
        $user_ent = $this->u->getEntity();
        
        if ($user_ent->isStudent()) {
            // У текущего студента получаем его группу
            $em = Database::getEm();
            $student_obj = $em->getRepository(Student::class)->findOneBy(
                ['user' => $user_ent]
            );

            $this->sendRasp($student_obj->getGroup(), $date);
            return;
        }

        $this->replyText("❌ Ты не студент, либо не зарегистрировался");
    }
    
    // Показывает расписание текущего препода
    // $data - пустой массив
    public function currentTeacherRasp($date, $data) {
        $user_ent = $this->u->getEntity();
        
        if ($user_ent->isTeacher()) {
            // У текущего препода получаем его сотрудника
            $em = Database::getEm();
            $teacher_ent = $em->getRepository(Teacher::class)->findOneBy(
                ['user' => $user_ent]
            );
            $employee_ent = $teacher_ent->getEmployee();
            
            $this->sendTeacherRasp($employee_ent, $date);
            return;
        }

        $this->replyText("❌ Ты не преподаватель, либо не зарегистрировался");
    }

    // 4 шаг при показе расписания определённой группы
    // $date - дата расписания
    // $data - ["group_id" - id группы на которую нужно показать расписание]
    public function groupRasp($date, $data) {
        $em = Database::getEm();
        $group_id = $data['group_id'];
        $group = $em->find(CollegeGroup::class, $group_id);
        $this->sendRasp($group, $date);
    }

    // последний шаг при показе расписания определённого преподавателя
    // $date - дата расписания
    // $data - ["employee_id" - id препода у которого нужно показать расписание]
    public function teacherRasp($date, $data) {
        $em = Database::getEm();
        $employee_id = $data['employee_id'];
        $employee = $em->find(Employee::class, $employee_id);
        $this->sendTeacherRasp($employee, $date);
    }

    // Показывает выбор даты для расписания препода
    public function showDateForEmployeeRasp($employee_id) {
        $em = Database::getEm();
        $employee = $em->find(Employee::class, (int)$employee_id);
        $m = M::create(
            "📅 Выбери дату для просмотра расписания преподавателя ".
            $employee->getNameWithInitials()
        );
        $m->setKeyboard(new SelectDateKeyboard(
            CallbackType::SelectedDateForTeacherRasp,
            ["employee_id" => $employee_id]
        ));
        $this->editAssociatedMessage($m);
    }
}