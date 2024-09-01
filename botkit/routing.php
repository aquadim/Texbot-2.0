<?php
// Прописывайте свои условия обработки здесь

use BotKit\Bot;
use BotKit\Models\Events\IEvent;
use BotKit\Models\Events\TextMessageEvent;
use BotKit\Enums\State;
use BotKit\Enums\CallbackType;

// Обратные вызовы
Bot::onCallback(CallbackType::ShowTos, 'TOSController@showTos');
Bot::onCallback(CallbackType::SelectedAccountType, 'OnboardingController@selectedAccountType');
Bot::onCallback(CallbackType::SelectedGroupNum, 'UtilController@advanceGroupSelection');
Bot::onCallback(CallbackType::SkipCredentials, 'OnboardingController@skipCredentials');
Bot::onCallback(CallbackType::EnterJournalLogin, 'OnboardingController@enterJournalLogin');
Bot::onCallback(CallbackType::ChangeGroup, 'HubController@changeStudentGroupStart');
Bot::onCallback(CallbackType::ChangePeriod, 'HubController@changeStudentPeriod');
Bot::onCallback(CallbackType::SelectedPeriod, 'HubController@studentPeriodSelected');
Bot::onCallback(CallbackType::ChangeAccountType, 'UtilController@changeAccountType');

// Обратные вызовы: выбрана группа
Bot::onCallback(CallbackType::SelectedGroupForStudentRegister, 'OnboardingController@studentSelectedGroup');
Bot::onCallback(CallbackType::SelectedGroupForStudentEdit, 'HubController@changeStudentGroupEnd');
Bot::onCallback(CallbackType::SelectedGroupForOtherRasp, 'HubController@selectedGroupForOtherRasp');
Bot::onCallback(CallbackType::SelectedGroupForNewAccountType, 'UtilController@newAccountTypeStudent');

// Обратные вызовы: выбрана дата
Bot::onCallback(CallbackType::SelectedDateForCurrentStudentRasp, 'ScheduleController@currentStudentRasp');
Bot::onCallback(CallbackType::SelectedDateForCurrentTeacherRasp, 'ScheduleController@currentTeacherRasp');
Bot::onCallback(CallbackType::SelectedDateForGroupRasp, 'ScheduleController@groupRasp');
Bot::onCallback(CallbackType::SelectedDateForTeacherRasp, 'ScheduleController@teacherRasp');
Bot::onCallback(CallbackType::SelectedDateForCabinetRasp, 'ScheduleController@cabinetRasp');

// Обратные вызовы: пагинация
Bot::onCallback(CallbackType::GroupSelectionPagination, 'UtilController@groupSelectionPage');
Bot::onCallback(CallbackType::EmployeeSelectionPagination, 'UtilController@teacherSelectionPage');

// Обратные вызовы: выбран преподаватель
Bot::onCallback(CallbackType::SelectedEmployeeForRasp, 'ScheduleController@showDateForEmployeeRasp');
Bot::onCallback(CallbackType::SelectedEmployeeForNewAccountType, 'UtilController@newAccountTypeTeacher');
Bot::onCallback(CallbackType::SelectedEmployeeForRegister, 'OnboardingController@teacherSelectedEmployee');

// Главное меню
Bot::onText("Расписание", 'HubController@schedule', State::Hub);
Bot::onText("Оценки", 'HubController@grades', State::Hub);
Bot::onText("Кабинеты", 'HubController@cabinets', State::Hub);
Bot::onText("Что дальше?", 'HubController@nextPair', State::Hub);
Bot::onText("Где преподаватель?", 'UtilController@sendTeacherSelectionForRasp', State::Hub);
Bot::onText("Расписание группы", 'HubController@scheduleForOtherGroup', State::Hub);
Bot::onText("Звонки", 'HubController@bellsSchedule', State::Hub);
Bot::onText("Профиль", 'HubController@showProfile', State::Hub);

// Команды
Bot::onCommand("/start", 'OnboardingController@welcome');
Bot::onCommand("/hub", 'HubController@hub');
Bot::onCommand("/rasp", 'HubController@schedule');
Bot::onCommand("/grades", 'HubController@grades');
Bot::onCommand("/next", 'HubController@nextPair');
Bot::onCommand("/bells", 'HubController@bellsSchedule');
Bot::onCommand("/profile", 'HubController@showProfile');
Bot::onCommand("/report", 'UtilController@reportProblem');
Bot::onCommand("/terms", 'TOSController@showTos');

// Ввод кабинета для просмотра его занятости
Bot::whenUserInState(State::EnterCabinetLocationForRasp, 'ScheduleController@showDateForCabinetRasp');

// АВЕРС
Bot::whenUserInState(State::EnterJournalLogin, 'OnboardingController@loginEnteredAskPassword');
Bot::whenUserInState(State::EnterJournalPassword, 'OnboardingController@passwordEnteredShowHub');

// Отчёт об ошибках
Bot::whenUserInState(State::EnterReportProblem, 'UtilController@reportSteps');
Bot::whenUserInState(State::EnterReportSteps, 'UtilController@reportFinish');

// Первое взаимодействие
Bot::whenUserInState(State::FirstInteraction, 'OnboardingController@welcome');
