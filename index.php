<?php

define('DOCROOT', 'F:\work\pttw\labs\server\src\yiphp');
$yi=DOCROOT.'/framework/yi.php';
$conf=dirname(__FILE__).'/protected/config/conf.php';

require_once($yi);
Yi::app()->runWebApp($conf);