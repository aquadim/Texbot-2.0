<?php
// Возвращает количество зарегистрированных студентов в разрезе групп
// в формате JSON
// [{
//      "group_id": <int>,
//      "group_name": <str>,
//      "vk.com": <int>
//      "telegram.org": <int>
// }, {...}, {...}]
require '../../botkit/bootstrap.php';
require 'auth.php';

$output = [["group_id"=> 1, "group_name"=> "4ИС", "vkcom"=> 30, "telegramorg"=> 18]];
echo json_encode($output);