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
use BotKit\Entities\Place;

use BotKit\Keyboards\SelectDateKeyboard;
use BotKit\Keyboards\StudentHubKeyboard;
use BotKit\Keyboards\TeacherHubKeyboard;

use BotKit\Enums\State;
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

    // Создаёт изображение. Добавляет дату. Возвращает изображение
    // как вложение
    // $imagen_class - название класса, который сгенерирует изображение
    // $title - подпись таблицы. Будет передано в sprintf. %s - дата изображения
    // $legend - легенда данных
    // $matrix - матрица данных
    // $body_col_constraints - максимум символов в колонках тела таблицы
    // $title_constraint - максимум символов в строке названия изображения
    // $date - дата изображения в формате ГГГГ-ММ-ДД
    private function getImage(
        string $imagen_class,
        string $title,
        array $legend,
        array $matrix,
        array $body_col_constraints,
        int $title_constraint,
        string $date
    ) : PhotoAttachment {

        // Дата изображения
        $fmt = new IntlDateFormatter(
            'ru_RU',
            IntlDateFormatter::RELATIVE_MEDIUM,
            IntlDateFormatter::NONE,
            'Europe/Kirov',
            IntlDateFormatter::GREGORIAN
        );
        $date_string = $fmt->format(DateTime::createFromFormat('Y-m-d', $date));

        $image_title = sprintf($title, $date_string);

        $filename = $imagen_class::generateTable(
            $matrix,
            $legend,
            $image_title,
            $body_col_constraints,
            $title_constraint
        );

        return PhotoAttachment::fromFile($filename);
    }

    // Собирает пары ГРУППЫ, рисует расписание и отправляет пользователю
    private function sendRasp(CollegeGroup $group, $date) {
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

        // Просим подождать
        $this->editAssociatedMessage(getWaitMessage());
        
        // Ищем расписание
        $em = Database::getEm();
        $s = $em->getRepository(Schedule::class)->findSchedule($group, $date);

        if ($s === null) {
            $this->editAssociatedMessage(M::create(
            "❌ Расписание группы ".$group->getHumanName().
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

        $img = $this->getImage(
            GenericImagen::class,
            'Расписание группы '.$group->getHumanName().' на %s',
            ['Время', 'Дисциплина', 'Детали проведения'],
            $matrix,
            [0, 40, 30],
            25,
            $date
        );
        
        $m = M::create(getDoneText());
        $m->addPhoto($img);
        $this->editAssociatedMessage($m);

        createCache(
            ImageCacheType::GroupSchedule,
            $this->u->getEntity()->getPlatform(),
            $group->getId().'-'.$date,
            $img->getId()
        );
    }

    // Собирает пары ПРЕПОДА, рисует расписание и отправляет пользователю
    private function sendTeacherRasp(Employee $employee, $date) {
        // Ищем кэш
        $cached = getCache(
            ImageCacheType::TeacherSchedule,
            $this->u->getEntity()->getPlatform(),
            $employee->getId().'-'.$date
        );
        
        if ($cached !== null) {
            // Кэш найден! Отправляем сообщение
            $m = M::create(getDoneText());
            $m->addPhoto($cached);
            $this->editAssociatedMessage($m);
            return;
        }
        
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

        $img = $this->getImage(
            GenericImagen::class,
            'Расписание преподавателя '.$employee->getNameWithInitials().' на %s',
            ['Время', 'Дисциплина', 'Группа', 'Место проведения'],
            $matrix,
            [0, 40, 0, 30],
            30,
            $date
        );
        
        $m = M::create(getDoneText());
        $m->addPhoto($img);
        $this->editAssociatedMessage($m);

        createCache(
            ImageCacheType::TeacherSchedule,
            $this->u->getEntity()->getPlatform(),
            $employee->getId().'-'.$date,
            $img->getId()
        );
    }

    // Собирает все пары, которые связаны с определённым МЕСТОМ на дату $date
    private function sendCabinetRasp(Place $place, $date) {

        // Ищем кэш
        $cached = getCache(
            ImageCacheType::OccupancySchedule,
            $this->u->getEntity()->getPlatform(),
            $place->getId().'-'.$date
        );
        
        if ($cached !== null) {
            // Кэш найден! Отправляем сообщение
            $m = M::create(getDoneText());
            $m->addPhoto($cached);
            $this->editAssociatedMessage($m);
            return;
        }

        // Просим подождать
        $this->editAssociatedMessage(getWaitMessage());

        $em = Database::getEm();
        $pairs = $em->getRepository(Pair::class)->getPairsForPlace(
            $place, $date
        );

        if (count($pairs) == 0) {
            $this->editAssociatedMessage(M::create(
                "❌ Для запрошенного места на заданную дату не найдено пар "
            ));
            return;
        }

        // Собираем пары в матрицу
        // Время | Название | Группа | Преподаватель
        $matrix = [];
        foreach ($pairs as $pair_info) {
            $pair = $pair_info->getPair();
            $group = $pair->getSchedule()->getCollegeGroup();
            $employee = $pair_info->getEmployee();

            $matrix[] = [
                $pair->getTime()->format('H:i'),
                $pair->getPairName()->getName(),
                $group->getHumanName(),
                $employee->getNameWithInitials()
            ];
        }

        $img = $this->getImage(
            GenericImagen::class,
            'Занятость кабинета '.$place->getName().' на %s',
            ['Время', 'Дисциплина', 'Группа', 'Преподаватель'],
            $matrix,
            [0, 40, 0, 0],
            30,
            $date
        );

        $m = M::create(getDoneText());
        $m->addPhoto($img);
        $this->editAssociatedMessage($m);

        createCache(
            ImageCacheType::OccupancySchedule,
            $this->u->getEntity()->getPlatform(),
            $place->getId().'-'.$date,
            $img->getId()
        );
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

    // Расписание на завтра
    public function tomorrowStudentRasp() {
        
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

    // последний шаг при показе расписания кабинета
    // $date - дата расписания
    // $data - ["place_id" - id места]
    public function cabinetRasp($date, $data) {
        $em = Database::getEm();
        $place_id = $data['place_id'];
        $place = $em->find(Place::class, $place_id);
        $this->sendCabinetRasp($place, $date);

        $user_ent = $this->u->getEntity();
        if ($user_ent->isStudent()) {
            $keyboard = new StudentHubKeyboard();
        } else {
            $keyboard = new TeacherHubKeyboard();
        }

        $this->u->setState(State::Hub);
        $m = M::create("Переносим тебя в главное меню...");
        $m->setKeyboard($keyboard);
        $this->reply($m);
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

    // Показывает выбор даты для расписания занятости кабинета
    public function showDateForCabinetRasp() {
        $text = $this->getEventText();

        // Поиск места в БД
        $em = Database::getEm();
        $place = $em->getRepository(Place::class)->findOneBy(['name' => $text]);

        if ($place == null) {
            $this->replyText("❌ Неизвестное место: ".$text);
            return;
        }
        
        $m = M::create(
            "📅 Выбери дату для просмотра занятости кабинета ".$text
        );
        $m->setKeyboard(new SelectDateKeyboard(
            CallbackType::SelectedDateForCabinetRasp,
            ["place_id" => $place->getId()]
        ));
        $this->reply($m);
    }
}