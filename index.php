<?php

define('DOCROOT', '/data/projects/yiphp');
$yi=DOCROOT.'/framework/yi.php';
$conf=dirname(__FILE__).'/protected/config/conf.php';

require_once($yi);
Yi::app()->runWebApp($conf);
