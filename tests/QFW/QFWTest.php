<?php

class QFWTest extends PHPUnit_Framework_TestCase
{
	public function testInitTime()
	{
		global $InitTime;
		$this->assertLessThanOrEqual(0.01, $InitTime);
		unset($GLOBALS['InitTime']);
	}

	/**
	 * Соответствия урлам и дефолтным урлам
	 *
	 * @return array
	 */
	public function testDdefProvider()
	{
		return array(
			array('aaa/bbb/ccc', ''),
			array('aaa/bbb/fff', 'bbb/fff'),
			array('aaa/eee/ccc', 'eee'),
			array('aaa/eee/fff', 'eee/fff'),
			array('ddd/bbb/ccc', 'ddd/bbb'),
			array('ddd/bbb/fff', 'ddd/bbb/fff'),
			array('ddd/eee/ccc', 'ddd/eee'),
			array('ddd/eee/fff', 'ddd/eee/fff'),
			array('aaa/bbb', ''),
			array('aaa/eee', 'eee'),
			array('ddd/bbb', 'ddd/bbb'),
			array('ddd/eee', 'ddd/eee'),
			array('aaa/ccc', ''),
			array('aaa/fff', 'fff'),
			array('ddd/ccc', 'ddd'),
			array('ddd/fff', 'ddd/fff'),
			array('bbb/ccc', ''),
			array('bbb/fff', 'bbb/fff'),
			array('eee/ccc', 'eee'),
			array('eee/fff', 'eee/fff'),
			array('aaa', ''),
			array('ddd', 'ddd'),
			array('bbb', ''),
			array('eee', 'eee'),
			array('ccc', ''),
			array('fff', 'fff'),
		);
	}

	/**
	 * @dataProvider testDdefProvider
	 *
	 * Тестирование delDef
	 */
	public function testDdef($in, $out)
	{
		QFW::Init();
		QFW::$config['default']['module'] = 'aaa';
		QFW::$config['default']['controller'] = 'bbb';
		QFW::$config['default']['action'] = 'ccc';
		QFW::$router->__construct(APPPATH);
		$this->assertEquals(QFW::$router->delDef($in),$out);
	}

}

?>