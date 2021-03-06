<?php

/**
 * Используйте константу DBSIMPLE_SKIP в качестве подстановочного значения чтобы пропустить опцональный SQL блок.
 */
define('DBSIMPLE_SKIP', log(0));
/**
 * Имена специализированных колонок в резальтате,
 * которые используются как ключи в результирующем массиве
 */
define('DBSIMPLE_ARRAY_KEY', 'ARRAY_KEY');   // hash-based resultset support
define('DBSIMPLE_PARENT_KEY', 'PARENT_KEY'); // forrest-based resultset support

/**
 * Класс обертка для DbSimple
 *
 * <br>нужен для ленивой инициализации коннекта к базе
 *
 * @package DbSimple
 * @method mixed transaction(string $mode=null)
 * @method mixed commit()
 * @method mixed rollback()
 * @method mixed select(string $query [, $arg1] [,$arg2] ...)
 * @method mixed selectRow(string $query [, $arg1] [,$arg2] ...)
 * @method array selectCol(string $query [, $arg1] [,$arg2] ...)
 * @method string selectCell(string $query [, $arg1] [,$arg2] ...)
 * @method mixed query(string $query [, $arg1] [,$arg2] ...)
 * @method string escape(mixed $s, bool $isIdent=false)
 * @method DbSimple_SubQuery subquery(string $query [, $arg1] [,$arg2] ...)
 * @method callback setLogger(callback $logger)
 * @method callback setCacher(callback $cacher)
 * @method string setIdentPrefix($prx)
 * @method string setCachePrefix($prx)
 */
class DbSimple_Connect
{
	/** @var DbSimple_Generic_Database База данных */
	protected $DbSimple;
	/** @var string DSN подключения */
	protected $DSN;
	/** @var string Тип базы данных */
	protected $shema;

	/**
	 * Конструктор только запоминает переданный DSN
	 * создание класса и коннект происходит позже
	 *
	 * @param string $dsn DSN строка БД
	 */
	public function __construct($dsn)
	{
		$this->DbSimple = null;
		$this->DSN      = $dsn;
		$this->shema    = ucfirst(substr($dsn, 0, strpos($dsn, ':')));
	}

	/**
	 * Взять базу из пула коннектов
	 *
	 * @param string $dsn DSN строка БД
	 * @return DbSimple_Connect
	 */
	public static function get($dsn)
	{
		static $pool = array();
		return isset($pool[$dsn]) ? $pool[$dsn] : $pool[$dsn] = new self($dsn);
	}

	/**
	 * Возвращает тип базы данных
	 *
	 * @return string имя типа БД
	 */
	public function getShema()
	{
		return $this->shema;
	}

	/**
	 * Коннект при первом запросе к базе данных
	 */
	public function __call($method, $params)
	{
		if ($this->DbSimple === null)
			$this->connect($this->DSN);
		return call_user_func_array(array(&$this->DbSimple, $method), $params);
	}

	/**
	 * mixed selectPage(int &$total, string $query [, $arg1] [,$arg2] ...)
	 * Функцию нужно вызвать отдельно из-за передачи по ссылке
	 */
	public function selectPage(&$total, $query)
	{
		if ($this->DbSimple === null)
			$this->connect($this->DSN);
		$args = func_get_args();
		$args[0] = &$total;
		return call_user_func_array(array(&$this->DbSimple, 'selectPage'), $args);
	}

	/**
	 * Подключение к базе данных
	 */
	protected function connect($dsn)
	{
		$parsed = $this->parseDSN($dsn);
		if (!$parsed)
			$this->errorHandler('Ошибка разбора строки DSN', $dsn);
		if (!isset($parsed['scheme']))
			$this->errorHandler('Невозможно загрузить драйвер базы данных', $parsed);
		$this->shema = ucfirst($parsed['scheme']);
		require_once dirname(__FILE__).'/'.$this->shema.'.php';
		$class = 'DbSimple_'.$this->shema;
		$this->DbSimple = new $class($parsed);
		if (isset($parsed['prefix']))
			$this->DbSimple->setIdentPrefix($parsed['prefix']);
		$this->DbSimple->setCachePrefix('db_'.md5($parsed['dsn']).'_');
		$this->DbSimple->setErrorHandler(array(&$this, 'errorHandler'), false);
	}

	/**
	 * Функция обработки ошибок - выводит сообщение об ошибке на тестовом
	 * на продакшене показывает 404 и пишит в sql.log
	 * Все вызовы без @ прекращают выполнение скрипта
	 */
	public function errorHandler($msg, $info)
	{
		// Если использовалась @, ничего не делать.
		if (!error_reporting()) return;
		if (QFW::$config['QFW']['release'])
		{
			require_once LIBPATH.'/Log.php';
			Log::log('SQL Error - '.$msg,'sql');
			QFW::$router->show404();
		}
		// Выводим подробную информацию об ошибке.
		echo "SQL Error: $msg<br><pre>";
		print_r($info);
		echo "</pre>";
		exit();
	}

	/**
	 * array parseDSN(string $dsn)
	 * Разбирает строку DSN в массив параметров подключения к базе
	 */
	protected function parseDSN($dsn)
	{
		$parsed = parse_url($dsn);
		if (!$parsed)
			return null;
		$params = null;
		if (!empty($parsed['query']))
		{
			parse_str($parsed['query'], $params);
			$parsed += $params;
		}
		$parsed['dsn'] = $dsn;
		return $parsed;
	}
}

?>