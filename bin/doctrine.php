#!/usr/bin/env php
<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use BotKit\Database;

require realpath(__DIR__ . '/../botkit/bootstrap.php');

$em = Database::getEM();
ConsoleRunner::run(
    new SingleManagerProvider($em)
);