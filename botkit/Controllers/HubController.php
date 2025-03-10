<?php
// Контроллер основных функций

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Models\Keyboards\ClearKeyboard;
use BotKit\Models\Attachments\PhotoAttachment;
use BotKit\Database;

use BotKit\Entities\Student;
use BotKit\Entities\Teacher;
use BotKit\Entities\CollegeGroup;
use BotKit\Entities\Period;
use BotKit\Entities\Pair;
use BotKit\Entities\PairConductionDetail;
use BotKit\Entities\UsedFunction;

use BotKit\Keyboards\SuggestEnterAversCredentialsKeyboard;
use BotKit\Keyboards\SelectGroup1Keyboard;
use BotKit\Keyboards\StudentHubKeyboard;
use BotKit\Keyboards\TeacherHubKeyboard;
use BotKit\Keyboards\StudentProfileKeyboard;
use BotKit\Keyboards\TeacherProfileKeyboard;
use BotKit\Keyboards\SelectPeriodKeyboard;
use BotKit\Keyboards\SelectDateKeyboard;
use BotKit\Keyboards\AnotherPeriodKeyboard;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;
use BotKit\Enums\ImageCacheType;
use BotKit\Enums\FunctionNames;

use Texbot\GenericImagen;
use Texbot\GradesImagen;
use function Texbot\getWaitMessage;
use function Texbot\getDoneText;
use function Texbot\getStudentGrades;
use function Texbot\createCache;
use function Texbot\getCache;
use function Texbot\getConductionDetailsAsText;
use function Texbot\addStat;

class HubController extends Controller {

    // Отправляет сообщение "Ты не зарегистрирован"
    private function errorNotRegistered() {
        $this->replyText("❌ Сначала пройди регистрацию");
    }

    // Отправляет сообщение с запретом выполнения студенту
    private function errorStudentNotAllowed() {
        $this->replyText("❌ Студентам эта функция недоступна");
    }

    // Отправляет сообщение с запретом выполнения преподу
    private function errorTeacherNotAllowed() {
        $this->replyText("❌ Преподавателям эта функция недоступна");
    }

    // Показывает выбор даты расписания
    // Если пользователь - студент, расписание создаётся для группы, иначе для
    // преподавателя
    public function schedule() {
        $user_ent = $this->u->getEntity();
        
        if ($user_ent->isStudent()) {
            // Выбор даты для студента
            $m = M::create("📅 Выбери дату");
            $m->setKeyboard(new SelectDateKeyboard(
                CallbackType::SelectedDateForCurrentStudentRasp
            ));
            $this->reply($m);
            return;
        }

        if ($user_ent->isTeacher()) {
            // Выбор даты для преподавателя
            $m = M::create("📅 Выбери дату");
            $m->setKeyboard(new SelectDateKeyboard(
                CallbackType::SelectedDateForCurrentTeacherRasp
            ));
            $this->reply($m);
            return;
        }

        $this->errorNotRegistered();
    }

    // Оценки
    public function grades() {
        $user_ent = $this->u->getEntity();

        if ($user_ent->isTeacher()) {
            // Преподам оценки недоступны
            $this->errorTeacherNotAllowed();
            return;
        }

        if (!$user_ent->isStudent()) {
            // Не студент? Значит не зарегистрирован
            $this->errorNotRegistered();
            return;
        }
        
        $wait = getWaitMessage();
        $this->reply($wait);
        
        // Поиск студента
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $user_ent]
        );
        
        // Проверка заполненности логина и пароля
        $login = $student->getAversLogin();
        $password = $student->getAversPassword();
        
        if ($login === null || $password === null) {
            $m = M::create("❌ Неизвестны твои логин и пароль от АВЕРС");
            $m->setKeyboard(new SuggestEnterAversCredentialsKeyboard());
            $this->edit($wait, $m);
            return;
        }
        
        // Получение предпочитаемого семестра студента
        $period = $student->getPreferencedPeriod();
        if ($period === null) {
            $this->edit(
                $wait,
                M::create("❌ Не выбран предпочитаемый семестр. Выбери семестр из меню профиля")
            );
            return;
        }

        // Попытка найти кэш
        $cached = getCache(
            ImageCacheType::Grades,
            $this->u->getEntity()->getPlatform(),
            $period->getOrdNumber().'-'.$this->u->getIdOnPlatform(),
        );
        if ($cached !== null) {
            // Кэш найден! Отправляем сообщение
            $m = M::create(getDoneText());
            $m->addPhoto($cached);
            $m->setKeyboard(new AnotherPeriodKeyboard());
            $this->edit($wait, $m);
            return;
        }

        // Получение данных об оценках
        $data = getStudentGrades(
            $login,
            $password,
            $period->getAversId()
        );
        if (!$data['ok']) {
            // Что-то не так, в data сообщение об ошибке
            $this->edit($wait, M::create('❌ '.$data['data']));
            return;
        }

        // Генерация изображения таблицы оценок
        $filename = GradesImagen::generateTable(
            $data['data'],
            ['Дисциплина', 'Оценки', 'Средний балл'],
            'Оценки, '.$period->getHumanName(),
            [35, 40, 0],
            0
        );

        // Отправка
        $m = M::create(getDoneText());
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $m->setKeyboard(new AnotherPeriodKeyboard());
        $this->edit($wait, $m);

        // Создание кэша
        createCache(
            ImageCacheType::Grades,
            $this->u->getEntity()->getPlatform(),
            $period->getOrdNumber().'-'.$this->u->getIdOnPlatform(),
            $m->getPhotos()[0]->getId()
        );

        // Добавление статистики
        addStat(FunctionNames::Grades, $this->u);
    }

    // Кабинеты
    public function cabinets() {
        $user_ent = $this->u->getEntity();

        if ($user_ent->isStudent()) {
            $this->errorStudentNotAllowed();
            return;
        }

        if (!$user_ent->isTeacher()) {
            $this->errorNotRegistered();
            return;
        }

        $this->u->setState(State::EnterCabinetLocationForRasp);
        
        $m = M::create("❓ Введи номер кабинета");
        $m->setKeyboard(new ClearKeyboard());
        $this->reply($m);
    }

    // Следующая пара
    public function nextPair() {
        $user_ent = $this->u->getEntity();
        $em = Database::getEm();
        $now = new \DateTimeImmutable();

        if (!$user_ent->isStudent() && !$user_ent->isTeacher()) {
            // Пользователь ни студент, ни препод
            $this->errorNotRegistered();
            return;
        }
        
        // -- Определение DQL и параметров запроса --
        if ($user_ent->isStudent()) {

            $student_ent = $em->getRepository(Student::class)->findOneBy(
                ['user' => $user_ent]
            );
            
            $dql =
            'SELECT p FROM '.Pair::class.' p '.
            'JOIN p.schedule s '.
            'WHERE s.college_group=:studentGroup AND p.time > :currentDate '.
            'ORDER BY p.time ASC';

            $query_params = [
                'currentDate' => $now,
                'studentGroup' => $student_ent->getGroup()
            ];
            
        } else if ($user_ent->isTeacher()) {

            $teacher_ent = $em->getRepository(Teacher::class)->findOneBy(
                ['user' => $user_ent]
            );

            $dql =
            'SELECT pcd FROM '.PairConductionDetail::class.' pcd '.
            'JOIN pcd.pair p '.
            'WHERE pcd.employee=:employee AND p.time > :currentDate '.
            'ORDER BY p.time ASC';

            $query_params = [
                'currentDate' => $now,
                'employee' => $teacher_ent->getEmployee()
            ];
        }

        $q = $em->createQuery($dql);
        $q->setMaxResults(1);
        $q->setParameters($query_params);
        $r = $q->getResult();

        if (count($r) == 0) {
            $this->replyText("❌ Не удалось найти следующую пару");
            return;
        }

        // -- Получение пары из результата запроса --
        if ($user_ent->isStudent()) {
            $pair = $r[0];
        } else {
            $pair = $r[0]->getPair();
        }

        // -- Вычисление разницы между "сейчас" и временем следующей пары --
        $time_diff = $pair->getTime()->diff($now);                
        $time_diff_text = $time_diff->h.' ч. '.$time_diff->i.' м. ';
        if ($time_diff->d > 0) {
            $time_diff_text = $time_diff->d.' д. '.$time_diff_text;
        }

        // -- Вывод --
        $out_text =
        "➡ Дальше ".$pair->getPairNameAsText().
        "\n⌛ В ".$pair->getTime()->format('H:i')." (через ".$time_diff_text.")".
        "\nℹ️ Детали проведения: ".
        getConductionDetailsAsText($pair->getConductionDetails());

        if ($user_ent->isTeacher()) {
            $schedule = $pair->getSchedule();
            $group = $schedule->getCollegeGroup();
            $out_text .= "\n👥 Группа: ".$group->getHumanName();
        }
        
        // Добавление статистики
        addStat(FunctionNames::Next, $this->u);
        
        $this->replyText($out_text);
    }

    // Функция "Где преподаватель?" находится в UtilController
 
    // Расписание группы (1 шаг из 4)
    // Показывает выбор группы для последующего показа расписания
    // 1. Курс
    // 2. Группа
    // 3. Дата
    // 4. profit (показ расписания)
    public function scheduleForOtherGroup() {
        $m = M::create("Выбери курс");
        $m->setKeyboard(new SelectGroup1Keyboard(
            CallbackType::SelectedGroupForOtherRasp
        ));
        $this->reply($m);
    }

    // Расписание группы (3 шаг из 4)
    public function selectedGroupForOtherRasp($group_id) {
        $em = Database::getEm();
        $group = $em->find(CollegeGroup::class, (int)$group_id);
        $m = M::create(
            "📅 Выбери дату для просмотра расписания группы ".
            $group->getHumanName()
        );
        $m->setKeyboard(new SelectDateKeyboard(
            CallbackType::SelectedDateForGroupRasp,
            ["group_id" => $group_id]
        ));
        $this->editAssociatedMessage($m);
    }
    
    // Расписание звонков
    public function bellsSchedule() {
        $this->replyText(
        "Звонки в понедельник:\n".
        "1 пара: 8:00 - 9:35 (перерыв в 8:45)\n".
        "2 пара: 9:45 - 11:20 (перерыв в 10:30)\n".
        "Кл час: 11:30 - 12:15\n".
        "Обед: 12:15-13:00\n".
        "3 пара: 13:00 - 14:35 (перерыв в 13:45)\n".
        "4 пара: 14:45 - 16:20 (перерыв в 15:30)\n".
        "5 пара: 16:30 - 18:05 (перерыв в 17:15).\n".
        "\n".
        "Звонки со вторника по пятницу\n".
        "1 пара: 8:00 - 9:35 (перерыв в 8:45)\n".
        "2 пара:9:45 - 11:20 (перерыв в 10:30)\n".
        "Обед: 11:20 - 12:20\n".
        "3 пара: 12:20 - 13:55 (перерыв в 13:05)\n".
        "4 пара: 14:05 - 15:40 (перерыв в 14:50)\n".
        "5 пара: 15:50 - 17:25 (перерыв в 16:35)\n".
        "\n".
        "Звонки в субботу\n".
        "1 пара: 8:00 - 9:00\n".
        "2 пара: 9:10 - 10:10\n".
        "3 пара: 10:20 - 11:20\n".
        "4 пара: 11:30 - 12:30"
        );

        addStat(FunctionNames::Bells, $this->u);
    }
    
    // Показ профиля
    public function showProfile() {
        $user_ent = $this->u->getEntity();
        $em = Database::getEm();

        if ($user_ent->isStudent()) {
            $student = $em->getRepository(Student::class)->findOneBy(
                ['user' => $user_ent]
            );
        
            // Группа студента
            $group = $student->getGroup();

            $profile_text = 
            '👥 Твоя группа: '.$group->getCourseNum().' '.
            $group->getSpec()->getName()."\n";
        
            // Логин и пароль АВЕРС
            $avers_login = $student->getAversLogin();
            $avers_login_set = $avers_login !== null;
            if (!$avers_login_set) {
                $profile_text .=
                "⚠ Ты не указывал логин и пароль от электронного журнала\n";
            } else {
                $profile_text .=
                "🆔 Логин, используемый для сбора ваших оценок - ".
                $avers_login."\n";
            }
        
            // Отображаемый семестр
            $avers_period = $student->getPreferencedPeriod();
            $avers_period_set = $avers_period !== null;
            if (!$avers_period_set) {
                // Если при регистрации пользователь пропустил шаг ввода
                // данных АВЕРС, то и предпочитаемого семестра не будет
                $profile_text .=
                "⚠ Неизвестен предпочитаемый семестр сбора оценок\n";
            } else {
                $profile_text .=
                "🗓 Семестр сбора оценок: ".$avers_period->getHumanName()."\n";
            }

            // Клавиатура
            $keyboard = new StudentProfileKeyboard(
                $avers_login_set,
                $user_ent->notificationsAllowed()
            );

        } else if ($user_ent->isTeacher()) {
            $teacher = $em->getRepository(Teacher::class)->findOneBy(
                ['user' => $user_ent]
            );
            $employee = $teacher->getEmployee();

            // Сотрудник
            $profile_text =
            '👥 Сотрудник, связанный с тобой - '.
            $employee->getNameWithInitials() . "\n";

            // Клавиатура
            $keyboard = new TeacherProfileKeyboard(
                $user_ent->notificationsAllowed()
            );

        } else {
            $this->errorNotRegistered();
            return;
        }

        if ($user_ent->notificationsAllowed()) {
            $profile_text .= "✅ Уведомления включены";
        } else {
            $profile_text .= "🚫 Уведомления отключены";
        }
         
        $m = M::create($profile_text);
        $m->setKeyboard($keyboard);
        $this->reply($m);
    }

    // Переносит пользователя в хаб, обновляет клавиатуру
    public function hub() {
        $user_ent = $this->u->getEntity();
        if ($user_ent->isStudent()) {
            $keyboard = new StudentHubKeyboard();
        } else if ($user_ent->isTeacher()) {
            $keyboard = new TeacherHubKeyboard();
        } else {
            $this->errorNotRegistered();
            return;
        }

        $this->u->setState(State::Hub);

        $m = M::create("🪄 Вжух, теперь ты в главном меню");
        $m->setKeyboard($keyboard);
        $this->reply($m);
    }
    
    // Смена группы студента 1/2
    public function changeStudentGroupStart() {
        $this->u->setState(State::NoResponse);
        $m = M::create("Начинаем обновление группы");
        $m->setKeyboard(new ClearKeyboard());
        $this->editAssociatedMessage($m);
        
        $m = M::create("На какой курс меняем?");
        $m->setKeyboard(new SelectGroup1Keyboard(
            CallbackType::SelectedGroupForStudentEdit
        ));
        $this->reply($m);
    }
    
    // Смена группы студента 2/2
    public function changeStudentGroupEnd($group_id) {
        // Обновление группы
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $group = $em->find(CollegeGroup::class, $group_id);
        $student->setGroup($group);
        $student->setPreferencedPeriod(null);
        
        // Переход в главное меню
        $this->u->setState(State::Hub);

        $m = M::create("Группа обновлена, наслаждайся!");
        $this->editAssociatedMessage($m);

        $m = M::create("Возвращаем тебя в главное меню...");
        $m->setKeyboard(new StudentHubKeyboard());
        $this->reply($m);
    }
    
    // Смена семестра 1/2
    public function changeStudentPeriod() {
        // Выбрать все семестры этой группы
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $group = $student->getGroup();
        $periods = $em->getRepository(Period::class)->findBy(
            ['group' => $group]
        );
        if (count($periods) === 0) {
            $this->replyText("В настоящее время ни один семестр не доступен для выбора");
            return;
        }
        $m = M::create("Выбери новый семестр");
        $m->setKeyboard(new SelectPeriodKeyboard($periods));
        $this->editAssociatedMessage($m);
    }
    
    // Смена семестра 2/2
    public function studentPeriodSelected($period_id) {
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $period = $em->find(Period::class, $period_id);
        $student->setPreferencedPeriod($period);
        $this->editAssociatedMessage(M::create("✅ Предпочтения сохранены"));
    }
}
