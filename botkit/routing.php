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

// Обратные вызовы: выбрана группа
Bot::onCallback(CallbackType::SelectedGroupForStudentRegister, 'OnboardingController@studentSelectedGroup');
Bot::onCallback(CallbackType::SelectedGroupForStudentEdit, 'HubController@changeStudentGroupEnd');
Bot::onCallback(CallbackType::SelectedGroupForOtherRasp, 'HubController@selectedGroupForOtherRasp');

// Обратные вызовы: выбрана дата
Bot::onCallback(CallbackType::SelectedDateForCurrentStudentRasp, 'ScheduleController@currentStudentRasp');
Bot::onCallback(CallbackType::SelectedDateForGroupRasp, 'ScheduleController@groupRasp');
Bot::onCallback(CallbackType::SelectedDateForTeacherRasp, 'ScheduleController@teacherRasp');

// Обратные вызовы: пагинация
Bot::onCallback(CallbackType::GroupSelectionPagination, 'UtilController@groupSelectionPage');
Bot::onCallback(CallbackType::EmployeeSelectionPagination, 'UtilController@teacherSelectionPage');

// Обратные вызовы: выбран преподаватель
Bot::onCallback(CallbackType::SelectedEmployeeForRasp, 'ScheduleController@showDateForEmployeeRasp');

// Главное меню
Bot::onText("Расписание", 'HubController@schedule', State::Hub);
Bot::onText("Оценки", 'HubController@grades', State::Hub);
Bot::onText("Что дальше?", 'HubController@nextPair', State::Hub);
Bot::onText("Где преподаватель?", 'UtilController@sendTeacherSelectionForRasp', State::Hub);
Bot::onText("Расписание группы", 'HubController@scheduleForOtherGroup', State::Hub);
Bot::onText("Звонки", 'HubController@bellsSchedule', State::Hub);
Bot::onText("Профиль", 'HubController@showProfile', State::Hub);

// АВЕРС
Bot::whenUserInState(State::EnterJournalLogin, 'OnboardingController@loginEnteredAskPassword');
Bot::whenUserInState(State::EnterJournalPassword, 'OnboardingController@passwordEnteredShowHub');

// Первое взаимодействие
Bot::whenUserInState(State::FirstInteraction, 'OnboardingController@welcome');
