<?php
// Файл на который поступают запросы

require '../botkit/bootstrap.php';

use BotKit\Bot;

// Загрузка драйверов. Этот код можно редактировать до строки
// "Нельзя редактировать дальше"
use BotKit\Drivers\VkComDriver;
Bot::loadDriver(new VkComDriver());

use BotKit\Drivers\TelegramOrgDriver;
Bot::loadDriver(new TelegramOrgDriver());
// Нельзя редактировать дальше

define('texbot_terms_url', 'https://www.vpmt.ru/texbot/prod/terms.html');
Bot::onLoadingFinished();
require root_dir . '/botkit/routing.php';
