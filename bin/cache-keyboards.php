<?php
// Скрипт для кэширования клавиатур

require_once __DIR__."/../botkit/bootstrap.php";

use BotKit\Drivers\VkComDriver;

use BotKit\Keyboards\TestKeyboard;
use BotKit\Keyboards\YTThumbnailKeyboard;

$keyboards = [new TestKeyboard(), new YTThumbnailKeyboard("hello")];

foreach ($keyboards as $kb) {
    if (!$kb->isCacheable()) {
        continue;
    }
    $cache = VkComDriver::getKeyboardMarkup($kb);
    echo $cache;
    echo "\n";
}