<?php

require (QFWPATH.'/QuickFW/Auth.php');

class IndexController extends QuickFW_Auth
{
	function __construct()
	{
		if(!parent::__construct())
			die(QFW::$view->displayMain(QFW::$view->fetch('auth.html')));
	}
	
	public function indexAction()
	{
		echo 'Главная страница админки. Защищенная зона';
	}
}

?>