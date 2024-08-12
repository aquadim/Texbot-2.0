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

// Обратные вызовы: выбрана группа
Bot::onCallback(CallbackType::SelectedGroupForStudentRegister, 'OnboardingController@studentSelectedGroup');

// Обратные вызовы: пагинация
Bot::onCallback(CallbackType::GroupSelectionPagination, 'UtilController@groupSelectionPage');

// Первое взаимодействие
Bot::whenUserInState(State::FirstInteraction, 'OnboardingController@welcome');
