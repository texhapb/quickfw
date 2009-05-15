<?php

mb_internal_encoding("UTF-8");

/* настройки хоста - установка Content-Type: text/html; charset=encoding */
$config['host']=array(
	'encoding' => 'utf-8',
	'lang' => 'ru_RU',
	'logpath' => ROOTPATH.'/log',
);

/* Настройки дефолтового MCA */
$config['default']=array(
	'module'    => 'default',
	'controller' => 'index',
	'action'    => 'index',
);

/* Настройки коннекта к базе данных */
$config['database']=array(
	'type'     => 'mysql',
	'host'     => 'localhost',
	'username' => 'root',
	'password' => '',
	'dbname'   => '',
	'prefix'   => '',
	'encoding' => 'utf8',
);
$config['database']='mypdo://root@localhost/base?enc=utf8&persist=1';

/* Настройки перенаправления */
/*
$config['redirection']=array();
$config['redirection']['baseUrl']='/';
$config['redirection']['useIndex']=false;
$config['redirection']['defExt']='';
$config['redirection']['useRewrite']=true;
$config['redirection']['useBlockRewrite']=false;
*/

/* Настройки кешера (класс бекенда и дополнительные параметры, если есть) */
/*$config['cacher']=array(
	'module' => 'Memcache',
);*/
$config['cacher']=array(
	'module' => 'File',
);

/**
 * Флаги, влияющие на поведение всяких вещей
 */
$config['QFW'] = array(
	'release' => false, /* статус проекта на данном хосте - отладка и всякие быстрые компиляции */
	'catchFE' => false, /* перехват ошибок как исключений, исключений как логов и фатальных ошибок */
	'ErrorStack' => false, /* вывод стека вызовов в сообщении об ошибке в БД */
);

/* Шаблонизатор - имя класса + дефолтовый шаблон */
$config['templater']= array(
	'name'      => 'PlainView',
	'def_tpl'   => 'main.html',
);

/*
$config['templater']= array(
	'name'      => 'Smarty',
	'def_tpl'   => 'main.tpl',
);

$config['templater']= array(
	'name'      => 'Proxy',
	'def_tpl'   => 'main.html',
	'exts' => array(
		'tpl' => 'Smarty',
		'html' => 'PlainView',
	),
);
*/

/* деквотатор, включите если нужно на хостинге */
/*
function strips(&$el) {
	if (is_array($el))
		foreach($el as $k=>$v)
			strips($el[$k]);
	else $el = stripslashes($el);
}
if (get_magic_quotes_gpc()) {
	strips($_GET);
	strips($_POST);
	strips($_COOKIE);
	strips($_REQUEST);
	if (isset($_SERVER['PHP_AUTH_USER'])) strips($_SERVER['PHP_AUTH_USER']);
	if (isset($_SERVER['PHP_AUTH_PW']))   strips($_SERVER['PHP_AUTH_PW']);
}
set_magic_quotes_runtime(0);
/**/

?>