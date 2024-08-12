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

// Обратные вызовы: выбрана группа
Bot::onCallback(CallbackType::SelectedGroupForStudentRegister, 'OnboardingController@studentSelectedGroup');
Bot::onCallback(CallbackType::SelectedGroupForStudentEdit, 'HubController@changeStudentGroupEnd');

// Обратные вызовы: пагинация
Bot::onCallback(CallbackType::GroupSelectionPagination, 'UtilController@groupSelectionPage');

// Главное меню
Bot::onText("Звонки", 'HubController@bellsSchedule', State::Hub);
Bot::onText("Профиль", 'HubController@showProfile', State::Hub);

// АВЕРС
Bot::whenUserInState(State::EnterJournalLogin, 'OnboardingController@loginEnteredAskPassword');
Bot::whenUserInState(State::EnterJournalPassword, 'OnboardingController@passwordEnteredShowHub');

// Первое взаимодействие
Bot::whenUserInState(State::FirstInteraction, 'OnboardingController@welcome');
