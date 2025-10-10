<?php
define('ROOT_PATH', __DIR__ . '/..');
define('CONFIG_PATH', __DIR__ . '/');
require_once CONFIG_PATH . '/config_main.php';

if ($phpBoolen):
    $dateiendungPHP = '.php';
else:
    $dateiendungPHP = '';
endif;
?>