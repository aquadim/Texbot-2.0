<?php
// Контроллер разных штук

namespace BotKit\Controllers;

use Doctrine\ORM\Tools\Pagination\Paginator;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Models\Keyboards\ClearKeyboard;
use BotKit\Database;

use BotKit\Entities\Student;
use BotKit\Entities\CollegeGroup;
use BotKit\Entities\Teacher;
use BotKit\Entities\Employee;

use BotKit\Keyboards\TOSKeyboard;
use BotKit\Keyboards\TeacherOrStudentKeyboard;
use BotKit\Keyboards\SelectGroup1Keyboard;
use BotKit\Keyboards\SelectGroup2Keyboard;
use BotKit\Keyboards\SelectEmployeeKeyboard;
use BotKit\Keyboards\TeacherHubKeyboard;
use BotKit\Keyboards\StudentHubKeyboard;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

class UtilController extends Controller {
    
    // Отправляет сообщение со страницей групп
    // $num - курс группы
    // $goal - цель из CallbackType
    // $offset - сдвиг по результатам
    private function sendGroupSelectionPage($num, $goal, $offset) {
        // Получение списка групп этой страницы
        // На странице 6 групп максимум
        $em = Database::getEm();
        $query = $em->createQuery(
            'SELECT g FROM '.CollegeGroup::class.' g '.
            'WHERE g.course_num=:course_num'
        );
        $query->setParameters(['course_num' => $num]);
        $query->setFirstResult($offset);
        $query->setMaxResults(6);
        $paginator = new Paginator($query, fetchJoinCollection: false);
        
        $callback_type = CallbackType::from($goal);
        
        $m = M::create("А теперь выбери группу");
        $m->setKeyboard(new SelectGroup2Keyboard(
            $paginator,
            $callback_type,
            $num,
            $offset
        ));
        $this->editAssociatedMessage($m);
    }
    
    // Отправляет сообщение со страницей преподавателей
    // $message - текст сообщения
    // $goal - цель из CallbackType
    // $offset - сдвиг по результатам
    // $reply - если true, будет отправлено новое сообщение, иначе изменится
    // существующее
    private function sendTeacherSelectionPage($message, CallbackType $goal, $offset, $reply) {
        // Получение списка преподавателей этой страницы
        $current_platform = $this->d->getPlatformDomain();

        switch ($current_platform) {
            case 'vk.com':
                $max_results = 6;
                break;
            default:
                $max_results = 6;
                break;
        }
        
        $em = Database::getEm();
        $query = $em->createQuery('SELECT e FROM '.Employee::class.' e ');
        $query->setFirstResult($offset);
        $query->setMaxResults($max_results);

        $paginator = new Paginator($query, fetchJoinCollection: false);

        // Отправка сообщения
        $m = M::create($message);
        $m->setKeyboard(new SelectEmployeeKeyboard(
            $paginator,
            $goal,
            $offset
        ));
        if ($reply) {
            $this->reply($m);
        } else {
            $this->editAssociatedMessage($m);
        }
    }
    
    // Выбран курс группы. Теперь нужно выбрать непосредственно группу
    public function advanceGroupSelection($num, $goal) {
        // Первая страница, offset=0
        $this->sendGroupSelectionPage($num, $goal, 0);
    }
    
    public function groupSelectionPage($num, $goal, $offset) {
        $this->sendGroupSelectionPage($num, $goal, $offset);
    }

    // Отправляет выбор преподавателей для просмотра их расписания
    public function sendTeacherSelectionForRasp() {
        $this->sendTeacherSelectionPage(
            "Выбери преподавателя",
            CallbackType::SelectedEmployeeForRasp,
            0,
            true
        );
    }

    public function teacherSelectionPage($goal, $offset) {
        $this->sendTeacherSelectionPage(
            "Выбери преподавателя",
            CallbackType::from($goal),
            $offset,
            false
        );
    }

    // Шаг 1 смены типа аккаунта
    // $type - новый тип аккаунта. 1 - студент, 2 - преподаватель
    public function changeAccountType($type) {
        // Устанавливаем неопределённый тип аккаунта пока пользователь
        // не введёт все данные
        $user_obj = $this->u->getEntity();
        $user_obj->setAccountType(0);
        $this->u->setState(State::NoResponse);

        $m = M::create("Начинаем обновление твоего аккаунта!");
        $m->setKeyboard(new ClearKeyboard());
        $this->editAssociatedMessage($m);

        if ($type == 2) {
            // Показать клавиатуру выбора преподавателей
            $this->sendTeacherSelectionPage(
                "Выбери себя из списка",
                CallbackType::SelectedEmployeeForNewAccountType,
                0,
                true
            );
        } else {
            // Показать клавиатуру выбора группы
            $this->replyText(
            "⚠ При смене группы можно выбрать только новую группу. Остальные ".
            "данные можно ввести с помощью кнопки \"Профиль\"");

            $m = M::create("На каком ты курсе?");
            $m->setKeyboard(new SelectGroup1Keyboard(
                CallbackType::SelectedGroupForNewAccountType
            ));
            $this->reply($m);
        }
    }

    // Шаг 2 смены типа аккаунта на преподавателя
    public function newAccountTypeTeacher($employee_id) {
        $em = Database::getEm();
        
        $user_obj = $this->u->getEntity();
        $user_obj->setAccountType(2);
        $this->u->setState(State::Hub);

        // Ищем студента, затем удаляем
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $user_obj]
        );
        $em->remove($student);

        // Создаём объект препода
        $teacher = new Teacher();
        $teacher->setUser($user_obj);
        $teacher->setEmployee($em->find(Employee::class, $employee_id));
        $em->persist($teacher);

        // Всё готово
        $m = M::create("✅ Тип аккаунта обновлён, теперь ты - преподаватель");
        $m->setKeyboard(new TeacherHubKeyboard());
        $this->editAssociatedMessage($m);
    }
    
    // Шаг 2 смены типа аккаунта на студента
    public function newAccountTypeStudent($group_id) {
        $em = Database::getEm();
        
        $user_obj = $this->u->getEntity();
        $user_obj->setAccountType(1);
        $this->u->setState(State::Hub);

        // Ищем препода, затем удаляем
        $teacher = $em->getRepository(Teacher::class)->findOneBy(
            ['user' => $user_obj]
        );
        $em->remove($teacher);

        // Создаём объект студента
        $student = new Student();
        $student->setUser($user_obj);
        $student->setGroup($em->find(CollegeGroup::class, $group_id));
        $em->persist($student);

        // Всё готово
        $m = M::create("✅ Тип аккаунта обновлён, теперь ты - студент");
        $m->setKeyboard(new StudentHubKeyboard());
        $this->editAssociatedMessage($m);
    }
}
