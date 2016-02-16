<?php
use Sledgehammer\Framework;

$moduleDir = dirname(dirname(__DIR__)) . '/';
$projectDir = dirname(dirname($moduleDir)) . '/';
Framework::$autoloader->importFolder($projectDir . 'vendor/sebastian/diff/src', ['mandatory_superclass' => false, 'matching_filename' => false]);
Framework::$autoloader->importFolder($moduleDir . 'orm/classes');
Framework::$autoloader->importFolder($moduleDir . 'wordpress/classes');
Framework::initModule($moduleDir . 'orm');
Framework::initModule($moduleDir . 'wordpress');
require_once($projectDir . '/web/wp-config.php');

Sledgehammer\Wordpress\init();