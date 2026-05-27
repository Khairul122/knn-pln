<?php
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Flash.php';
require_once ROOT_PATH . '/config/app.php';

$router = new Router();

require_once ROOT_PATH . '/routes/web.php';

$router->dispatch();
