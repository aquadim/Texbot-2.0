<?php
// Контроллер основных функций

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Models\Keyboards\ClearKeyboard;
use BotKit\Models\Attachments\PhotoAttachment;
use BotKit\Database;

use BotKit\Entities\Student;
use BotKit\Entities\CollegeGroup;

use BotKit\Keyboards\TOSKeyboard;
use BotKit\Keyboards\SuggestEnterAversCredentialsKeyboard;
use BotKit\Keyboards\TeacherOrStudentKeyboard;
use BotKit\Keyboards\SelectGroup1Keyboard;
use BotKit\Keyboards\HubKeyboard;
use BotKit\Keyboards\YesNoKeyboard;
use BotKit\Keyboards\ProfileKeyboard;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

use Texbot\GenericImagen;
use Texbot\GradesImagen;
use function Texbot\getWaitMessage;
use function Texbot\getDoneText;
use function Texbot\getStudentGrades;

class HubController extends Controller {
    
    // Оценки
    public function grades() {
        // TODO: поиск кэшированного
        
        $wait = getWaitMessage();
        $this->reply($wait);
        
        // Поиск студента
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        
        // Проверка заполненности логина и пароля
        $login = $student->getAversLogin();
        $password = $student->getAversPassword();
        
        if ($login === null || $password === null) {
            $m = M::create("❌ Неизвестны твои логин и пароль от АВЕРС");
            $m->setKeyboard(new SuggestEnterAversCredentialsKeyboard());
            $this->reply($m);
            return;
        }
        
        $data = getStudentGrades(
            $login,
            $password,
            577,
            $this
        );
        
        if (!$data['ok']) {
            $this->edit($wait, M::create('❌ '.$data['data']));
            return;
        }
        
        $filename = GradesImagen::generateTable(
            $data['data'],
            ['Дисциплина', 'Оценки', 'Средний балл'],
            'Оценки', // TODO: добавить семестр
            [35, 40, 0],
            0
        );
        
        $m = M::create(getDoneText(true));
        $m->addPhoto(PhotoAttachment::fromFile($filename));
        $this->edit($wait, $m);
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
        "2 пара: 9:45 - 11:20 (перерыв в 10:30)\n".
        "Обед: 11:20 - 12:20\n".
        "3 пара: 12:20 - 13:55 (перерыв в 13:05)\n".
        "4 пара: 14:05 - 15:40 (перерыв в 14:50)\n".
        "5 пара: 15:50 - 17:25 (перерыв в 16:35)\n".
        "\n".
        "Звонки в субботу\n".
        "1 пара: 8:00 - 9:25 (перерыв в 8:40)\n".
        "2 пара: 09:35 - 11:00 (перерыв в 10:15)\n".
        "3 пара: 11:10 - 12:35 (перерыв в 11:50)\n".
        "4 пара: 12:45 - 14:10 (перерыв в 13:25)"
        );
    }
    
    // Показ профиля
    public function showProfile() {
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        
        // Группа студента
        $group = $student->getGroup();
        $profile_text = 
        '👥 Твоя группа: '.
        $group->getCourseNum().
        ' '.
        $group->getSpec()->getName().
        "\n";
        
        // Логин и пароль АВЕРС
        $avers_login = $student->getAversLogin();
        $avers_set = $avers_login !== null;
        if (!$avers_set) {
            $profile_text .= "⚠ Вы не указывали логин и пароль от электронного журнала\n";
        } else {
            $profile_text .= "🆔 Логин, используемый для сбора ваших оценок - ".$avers_login."\n";
        }
         
        $m = M::create($profile_text);
        $m->setKeyboard(new ProfileKeyboard($avers_set));
        $this->reply($m);
    }
    
    // Смена группы студента начало
    public function changeStudentGroupStart() {
        // 1.
        $this->u->setState(State::NoResponse);
        $m = M::create("Начинаем обновление группы");
        $m->setKeyboard(new ClearKeyboard());
        $this->editAssociatedMessage($m);
        
        // 2.
        $m = M::create("На какой курс меняем?");
        $m->setKeyboard(new SelectGroup1Keyboard(
            CallbackType::SelectedGroupForStudentEdit
        ));
        $this->reply($m);
    }
    
    // Смена группы студента конец
    public function changeStudentGroupEnd($group_id) {
        // Обновление группы
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]);
        $group = $em->find(CollegeGroup::class, $group_id);
        $student->setGroup($group);
        
        // Переход в главное меню
        $this->u->setState(State::Hub);
        $m = M::create("Группа обновлена, наслаждайся!");
        $m->setKeyboard(new HubKeyboard());
        $this->reply($m);
    }
}
