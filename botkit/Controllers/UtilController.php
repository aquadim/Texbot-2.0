<?php
// Контроллер разных штук

namespace BotKit\Controllers;

use Doctrine\ORM\Tools\Pagination\Paginator;

use BotKit\Controller;
use BotKit\Models\Messages\TextMessage as M;
use BotKit\Database;

use BotKit\Entities\Student;
use BotKit\Entities\CollegeGroup;

use BotKit\Keyboards\TOSKeyboard;
use BotKit\Keyboards\TeacherOrStudentKeyboard;
use BotKit\Keyboards\SelectGroup2Keyboard;

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
    
    // Выбран курс группы. Теперь нужно выбрать непосредственно группу
    public function advanceGroupSelection($num, $goal) {
        // Первая страница, offset=0
        $this->sendGroupSelectionPage($num, $goal, 0);
    }
    
    public function groupSelectionPage($num, $goal, $offset) {
        $this->sendGroupSelectionPage($num, $goal, $offset);
    }
}
