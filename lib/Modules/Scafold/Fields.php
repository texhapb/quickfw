<?php

require_once LIBPATH.'/utils.php';

/**
 * Класс, на основе которого создаются остальные<br>
 * Содержит набор полей для заполнения пользователем
 *
 */
class Scafold_Field_Info
{
	/** @var boolean скрытое поле */
	public $hide = null;
	/** @var string класс поля */
	public $type = false;
	/** @var string параметры класса */
	public $typeParams = false;
	/** @var string фильтр */
	public $filter = false;
	/** @var array настройки зависимостей */
	public $foreign = false;

	/** @var array Данные из базы */
	public $fiendInfo;

	/** @var string Имя таблицы */
	public $table;
	/** @var string Имя первичного ключа */
	public $primaryKey;

	/** @var string Имя поля */
	public $name = '';
	/** @var string Дефолтовое значение */
	public $default = '';
	/** @var string Заголовок колонки */
	public $title = '';
	/** @var string Описание колонки */
	public $desc = '';

}

/**
 * Базовый класс для всех типов полей
 * Не содержит никаких проверок, оформлений, преобразований
 *
 */
class Scafold_Field extends Scafold_Field_Info
{

	/**
	 * Создает полноценное поле из данных о пользователе
	 *
	 * @param Scafold_Field_Info $info Информация о поле
	 */
	public function __construct($info)
	{
		$vars = get_class_vars('Scafold_Field_Info');
		foreach ($vars as $k=>$v)
			$this->$k = $info->$k;
		$this->name = $info->fiendInfo['Field'];
		$this->default = $info->fiendInfo['Default'];
		if (!$this->title)
			$this->title = $this->name;
	}

	/**
	 * Выводит значение поля
	 *
	 * @param string $id первичный ключ
	 * @param string $value значение поля
	 * @return string То, что будет показано
	 */
	public function display($id, $value)
	{
		return $value===null ? ( $this->fiendInfo['Null'] == 'YES' ? 'NULL' : '-')
			: QFW::$view->esc($value);
	}

	/**
	 * Выводит редактор для поля
	 *
	 * @param string $id первичный ключ или -1 для новой записи
	 * @param string $value значение поля
	 * @return string html код редактора
	 */
	public function editor($id, $value)
	{
		return '<input type="text" name="data['.$this->name.']"
			   default="'.QFW::$view->esc($value).'" />';
	}

	/**
	 * Проверяет корректность ввода поля
	 *
	 * @param string $id первичный ключ или -1 для новой записи
	 * @param string $value проверяемое значение
	 * @return bool|string true, если правильно<br>
	 * false, для стандартного сообщения об ошибке<br>
	 * строка для сообщения об ошибке
	 */
	public function validator($id, $value)
	{
		return true;
	}

	/**
	 * Часть условия WHERE для данного фильтра
	 *
	 * @param mixed $session сохраненное в сессии значение
	 * @return DbSimple_SubQuery часть запроса к базе данных
	 */
	public function filterWhere($session)
	{
		return QFW::$db->subquery('?# LIKE ?', 
			array($this->table=>$this->name), $session.'%');
	}

	/**
	 * Формирует поле ввода для фильтра
	 *
	 * @param mixed $session сохраненные в сесии данные
	 * @return string часть формы для фильтра
	 */
	public function filterForm($session)
	{
		return '<input type="text" name="filter['.$this->name.']" '.
			'default="'.$session.'" label="'.$this->title.': ^" />';
	}

	/**
	 * Обработка значения перед изменением в таблице
	 *
	 * @param string $id первичный ключ или -1 для новой записи
	 * @param string|false $value исходное значение или false при удалении
	 * @return string обработанное значение
	 */
	public function proccess($id, $value)
	{
		if ($this->fiendInfo['Null'] == 'YES' && $value=='')
			return null;
		return $value;
	}

	public function action($id, $action='do')
	{
		die('Был вызван метод '.$action.' для поля '. $this->title);
	}

	/**
	 * Вызывается при заполнениии формы для вставки
	 *
	 * @return string дефолтовое значение
	 */
	public function def()
	{
		return $this->default;
	}

	/**
	 * Строит стандартный селект
	 *
	 * @param array $data массив ключ=>значение
	 * @param scalar $cur текущий элемент
	 * @return string блок селекта
	 */
	protected function selectBuild($data, $cur, $null=true)
	{
		$text = '<select name="data['.$this->name.']">';
		if ($null)
			$text.= '<option value="0"'.
				(!isset($data[$cur]) ? ' selected="selected"' : '').
					'> -- Не указано -- </option>';
		foreach ($data as $i=>$v)
			$text.= '<option value="'.$i.'"'.
				($i == $cur ? ' selected="selected"' : '').
				'>'.QFW::$view->esc($v).'</option>';
		$text.= '</select>';
		return $text;
	}

}

//Сервисные классы

/**
 * Класс для главного поля
 */
class Scafold_Parent extends Scafold_Field
{
	public function proccess($id, $value)
	{
		return $_SESSION['scafold'][$this->table]['parent'];
	}

	public function validator($id, $value)
	{
		//если у нас будет потеря сессии, то случится фигня
		return isset($_SESSION['scafold'][$this->table]['parent']);
	}
	
	public function filterForm($session)
	{
		return '';
	}
}

/**
 * Класс для зависимых полей
 * формирует select со значениями из другой таблицы
 */
class Scafold_Foreign extends Scafold_Field
{
	/** @var array Зависимые поля */
	protected $lookup;

	/** @var bool Может ли быть нулевое значение */
	protected $isnull;

	public function __construct($info, $where = DBSIMPLE_SKIP)
	{
		if (!empty($info->typeParams))
			$where = $info->typeParams;
		$this->isnull = $info->foreign['null'];
		parent::__construct($info);
		$this->lookup = QFW::$db->selectCol('SELECT ?# AS ARRAY_KEY_1, ?# FROM ?# {WHERE ?s}',
			$info->foreign['key'], $info->foreign['field'], $info->foreign['table'], $where);
	}

	public function editor($id, $value)
	{
		return $this->selectBuild($this->lookup, $value);
	}

	public function validator($id, $value)
	{
		return $this->isnull || !empty($value);
	}

}

/**
 * Сервисный классс для полей, вводимых пользователем
 * <br>пока только обрезка при выводе, так как обязательно нагадят :)
 */
abstract class Scafold_UserInput extends Scafold_Field
{
	/** @var integer До скольки обрезать */
	private $trim;
	
	public function __construct($info)
	{
		parent::__construct($info);
		$this->trim = isset($info->typeParams['trim']) ? $info->typeParams['trim'] : 80;
	}

	public function display($id, $value)
	{
		return QFW::$view->esc(my_trim($value, $this->trim));
	}
}

//Классы для различных типов полей из базы данных
//Соответствие в функции ScafoldController::getFieldClass


/**
 * Пока тестовый класс для типа TEXT
 */
class Scafold_Text extends Scafold_UserInput
{
	/** @var integer Сколько строк */
	private $rows;
	/** @var integer Сколько колонок */
	private $cols;

	public function __construct($info)
	{
		parent::__construct($info);
		$this->rows = isset($info->typeParams['rows']) ? $info->typeParams['rows'] : 10;
		$this->cols = isset($info->typeParams['cols']) ? $info->typeParams['cols'] : 80;
	}

	public function editor($id, $value)
	{
		return '<textarea name="data['.$this->name.']" '.
			'rows="'.$this->rows.'" cols="'.$this->cols.'">'.
			QFW::$view->esc($value).'</textarea>';
	}
}

/**
 * Класс для типа Int
 */
class Scafold_Int extends Scafold_Field
{
	public function validator($id, $value)
	{
		return is_numeric($value);
	}
}

/**
 * Класс для типа Varchar
 */
class Scafold_Varchar extends Scafold_Field
{
	/** @var integer размер поля в базе */
	private $size;

	public function __construct($info, $size = 100)
	{
		if (!empty($info->typeParams) && is_numeric($info->typeParams))
			$size = $info->typeParams;
		parent::__construct($info);
		$this->size = $size;
	}

	public function validator($id, $value)
	{
		return mb_strlen($value) <= $this->size;
	}
}

/**
 * Класс для типа Char - полностью аналогичен Varchar
 */
class Scafold_Char extends Scafold_Varchar {}

/**
 * Класс для типа ENUM
 */
class Scafold_Enum extends Scafold_Field
{
	/** @var array что в перечислении */
	private $items;

	public function __construct($info, $items)
	{
		parent::__construct($info);
		$items = str_getcsv($items, ',', "'");
		$this->items = array_combine($items, $items);
	}

	public function editor($id, $value)
	{
		return $this->selectBuild($this->items, $value, false);
	}

}

//Классы для других типов полей, указываемых пользователем

class Scafold_Checkbox extends Scafold_Field
{

	public function display($id, $value)
	{
		return $value ? 'Да' : 'Нет';
	}

	public function editor($id, $value)
	{
		return '<input type="hidden" name="data['.$this->name.']" value="0" />
			<input type="checkbox" name="data['.$this->name.']" value="1" label="'.$this->title.'"
				default="'.($value?'checked':'').'" />';
	}

}

/**
 * Класс для поля, в котором хранится имя файла,
 * загружаемого на сервер
 */
class Scafold_File extends Scafold_Field
{
	/** @var string Путь к директории, где хранятся файлы */
	private $path;
	/** @var string Первичный ключ таблицы */
	private $prim;
	/** @var bool Скачиваемый (доступен извне) */
	private $download;

	/**
	 * Проверяет параметры для файлового поля
	 *
	 * @param Scafold_Field_Info $info Информация о поле
	 */
	public function __construct($info)
	{
		parent::__construct($info);
		if (empty ($info->typeParams['path']))
			throw new Exception('Не указана директория для фалов', 1);
		if (!is_dir($info->typeParams['path']))
			throw new Exception('Неверная директория для файлов '.$info->typeParams['path'], 1);
		if (!is_writable($info->typeParams['path']))
			throw new Exception('Нельзя писать в директорию файлов '.$info->typeParams['path'], 1);
		$this->path = $info->typeParams['path'];
		$this->prim = $info->primaryKey;
		if (strpos($info->typeParams['path'], DOC_ROOT) === 0)
			$this->download = substr($info->typeParams['path'], strlen(DOC_ROOT) );
		else
			$this->download = false;
	}

	public function editor($id, $value)
	{
		return '<input type="file" name="file['.$this->name.']" />
				<input type="hidden" name="data['.$this->name.']" value="0" />
				<input type="checkbox" name="data['.$this->name.']" value="1" label="Удалить" /> '.
				$this->display($id, $value);
	}

	public function validator($id, $value)
	{
		//оставляем старый файл
		if ($_FILES['file']['error'][$this->name] == 4)
			return true;
		if ($_FILES['file']['error'][$this->name] != 0)
			return 'Ошибка при загрузке файла '.$this->title;
		return is_uploaded_file($_FILES['file']['tmp_name'][$this->name]);
	}

	public function proccess($id, $value)
	{
		$old = QFW::$db->selectCell('SELECT ?# FROM ?# WHERE ?#=?',
			$this->name, $this->table, $this->prim, $id);
		//если запись удалили
		if ($value === false && is_file($this->path.'/'.$old))
			unlink($this->path.'/'.$old);
		//оставляем старое значение
		if ($_FILES['file']['error'][$this->name] == 4 && !$value)
			return $old ? $old : '';
		//удяляем старый
		if (is_file($this->path.'/'.$old))
			unlink($this->path.'/'.$old);
		//флаг что удалили
		if ($value)
			return '';
		//генерим новое имя
		$info = pathinfo($_FILES['file']['name'][$this->name]);
		if ($id == -1)
			$id = time();
		$new_name = $this->name.'_'.$id.'.'.$info['extension'];
		move_uploaded_file($_FILES['file']['tmp_name'][$this->name], $this->path.'/'.$new_name);
		return $new_name;
	}

	public function display($id, $value)
	{
		if (!$value || !is_file($this->path.'/'.$value))
			return '-нет-';
		if ($this->download)
			return '<a href="'.$this->download.'/'.$value.'">'.$value.'</a>';
		return $value;
	}

}

?>
