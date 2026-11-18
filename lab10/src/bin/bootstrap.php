<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
define('BASE_PATH',dirname(__DIR__));define('APP_PATH',BASE_PATH.'/app');
require APP_PATH.'/autoload.php';require APP_PATH.'/helpers.php';
