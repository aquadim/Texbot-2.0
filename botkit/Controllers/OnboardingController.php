<?php
// Контроллер подготовки и регистрации аккаунта

namespace BotKit\Controllers;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Database;

use BotKit\Entities\Student;
use BotKit\Entities\CollegeGroup;

use BotKit\Keyboards\TOSKeyboard;
use BotKit\Keyboards\TeacherOrStudentKeyboard;
use BotKit\Keyboards\SelectGroup1Keyboard;
use BotKit\Keyboards\HubKeyboard;
use BotKit\Keyboards\YesNoKeyboard;

use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

class OnboardingController extends Controller {
    
    private function replyWelcomeWithHub() {
        $this->u->setState(State::Hub);
        
        $m = M::create("Ответы сохранены! Добро пожаловать");
        $m->setKeyboard(new HubKeyboard());
        $this->reply($m);
    }
    
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
        // Найти студента, присвоить ему группу
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $group = $em->find(CollegeGroup::class, $group_id);
        $student->setGroup($group);
        
        // Спросить нужно ли ввести оценки
        $m = M::create("Хочешь ввести логин и пароль от электронного дневника чтобы просматривать оценки?");
        $m->setKeyboard(new YesNoKeyboard(
            CallbackType::EnterJournalLogin,
            [],
            CallbackType::SkipCredentials,
            []
        ));
        $this->editAssociatedMessage($m);
    }
    
    // Показ сообщения с просьбой ввести логин
    public function enterJournalLogin() {
        $this->u->setState(State::EnterJournalLogin);
        $this->editAssociatedMessage(M::create("Введи логин"));
    }
    
    // Показ сообщения с просьбой ввести логин
    public function loginEnteredAskPassword() {
        // Сохранить в базу данных
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $student->setAversLogin($this->getEventText());
        
        // Спросить пароль
        $this->u->setState(State::EnterJournalPassword);
        $this->replyText("Теперь пароль");
    }
    
    // Пароль сохранили, показывает главное меню
    public function passwordEnteredShowHub() {
        // Сохранить в базу данных
        $em = Database::getEm();
        $student = $em->getRepository(Student::class)->findOneBy(
            ['user' => $this->u->getEntity()]
        );
        $student->setAversPassword($this->getEventText());
        
        $this->replyWelcomeWithHub();
    }
    
    public function skipCredentials() {
        $this->replyWelcomeWithHub();
    }
}
