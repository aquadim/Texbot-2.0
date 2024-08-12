<?php
// Файл на который поступают запросы

require '../botkit/bootstrap.php';

use BotKit\Bot;

// Загрузка драйверов. Этот код можно редактировать до строки
// "Нельзя редактировать дальше"
use BotKit\Drivers\VkComDriver;

Bot::loadDriver(new VkComDriver());
// Нельзя редактировать дальше

Bot::onLoadingFinished();

require root_dir . '/botkit/routing.php';
