<?php
// Файл на который поступают запросы

require '../botkit/bootstrap.php';

use BotKit\Bot;

// Загрузка драйверов. Этот код можно редактировать до строки
// "Нельзя редактировать дальше"
use BotKit\Drivers\VkComDriver;
use BotKit\Drivers\TelegramOrgDriver;

Bot::loadDriver(new VkComDriver());
Bot::loadDriver(new TelegramOrgDriver());

define('texbot_terms_url', 'https://www.vpmt.ru/texbot/prod/terms.html');
// Нельзя редактировать дальше

Bot::onLoadingFinished();

require root_dir . '/botkit/routing.php';
