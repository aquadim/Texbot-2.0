<?php
// Контроллер подготовки и регистрации аккаунта

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Database;

use BotKit\Entities\Student;

use BotKit\Keyboards\TOSKeyboard;
use BotKit\Keyboards\TeacherOrStudentKeyboard;
use BotKit\Keyboards\SelectGroup1Keyboard;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

class OnboardingController extends Controller {
    
    // Первое взаимодействие
    public function welcome() {
        $this->replyText("Привет, я - Техбот. Моя задача - облегчить твою жизнь, но, для начала, мне нужно задать несколько вопросов");
        
        $m = M::create("Ознакомься с условиями использования прежде чем использовать мои функции");
        $m->setKeyboard(new TOSKeyboard());
        $this->reply($m);
        
        // Пользователь будет использовать клавиатуру для выбора ответов
        // Текстовые сообщения не следует в этот момент обрабатывать
        $this->u->setState(State::NoResponse);
        
        $m = M::create("Ты преподаватель или студент?");
        $m->setKeyboard(new TeacherOrStudentKeyboard());
        $this->reply($m);
    }
    
    // Выбран тип аккаунта
    public function selectedAccountType($answer) {
        if ($answer == "student") {
            // Создать объект студента
            $em = Database::getEm();
            $s = new Student();
            $s->setUser($this->u->getEntity());
            $em->persist($s);
            
            // Отправить сообщение с выбором группы
            $m = M::create("На каком курсе сейчас учишься?");
            $m->setKeyboard(new SelectGroup1Keyboard(
                CallbackType::SelectedGroupForStudentRegister
            ));
            $this->editAssociatedMessage($m);
        } else {
            $this->replyText("TODO");
        }
    }
    
    // Студент выбрал свою группу
    public function studentSelectedGroup($group_id) {
        $this->replyText("Ты выбрал группу $group_id чтобы зарегаться");
    }
}
