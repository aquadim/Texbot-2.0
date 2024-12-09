<?php
// Перечисление наименований всех функций студентов Техбота
namespace BotKit\Enums;

enum FunctionNames: string {
    case Rasp = "Расписание";
    case OtherRasp = "Расписание другой группы";
    case Grades = "Оценки";
    case TeacherRasp = "Расписание преподавателя";
    case Bells = "Звонки";
    case Next = "Что дальше";
}
