<?php

class Templater_PlainView
{
	protected $_vars;
	protected $_tmplPath;

	public $P;
	public $mainTemplate;

	public function __construct($tmplPath, $mainTmpl)
	{
		$this->_vars = array();
		if (null !== $tmplPath)
		{
			$this->_tmplPath = $tmplPath;
		}

		$this->P = QuickFW_Plugs::getInstance();

		$this->mainTemplate = $mainTmpl;
	}

	/**
	* Assign variables to the template
	*
	* Allows setting a specific key to the specified value, OR passing an array
	* of key => value pairs to set en masse.
	*
	* @see __set()
	* @param string|array $spec The assignment strategy to use (key or array of key
	* => value pairs)
	* @param mixed $value (Optional) If assigning a named variable, use this
	* as the value.
	* @return void
	*/
	public function assign($spec, $value = null)
	{
		if (is_array($spec))
			$this->_vars = array_merge($this->_vars, $spec);
		else
			$this->_vars[$spec] = $value;
		return $this;
	}

	/**
	* Clear assigned variable
	*
	* @param string|array
	* @return void
	*/
	public function delete($spec)
	{
		if (is_array($spec))
			foreach ($spec as $item)
				$this->delete($item);
		elseif (isset($this->_vars[$spec]))
				unset($this->_vars[$spec]);
	}

	/**
	* Clear all assigned variables
	*
	* @return void
	*/
	public function clearVars()
	{
		$this->_vars=array();
	}

	public function getTemplateVars($var = null)
	{
		if ($var === null)
			return $this->_vars;
		elseif (isset($this->_vars[$var]))
			return $this->_vars[$var];
		else
			return null;
	}

	public function getScriptPath()
	{
		return $this->_tmplPath;
	}

	public function setScriptPath($path)
	{
		if (!is_readable($path))
			return false;
		$this->_tmplPath = $path;
		return true;
	}

	public function block($block)
	{
		return QFW::$router->blockRoute($block);
	}

	public function render($tmpl)
	{
		extract($this->_vars, EXTR_OVERWRITE);
		$P=&$this->P;
		ob_start();
		include($this->_tmplPath . '/' . $tmpl);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function fetch($tmpl)
	{
		extract($this->_vars, EXTR_OVERWRITE);
		$P=&$this->P;
		ob_start();
		include($this->_tmplPath . '/' . $tmpl);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function displayMain($content)
	{
		if (isset($this->mainTemplate) && $this->mainTemplate!="")
		{
			//Необходимо для установки флага CSS
			$this->P->startDisplayMain();
			$this->assign('content',$content);
			$content = $this->render($this->mainTemplate);
		}
		//Необходимо для вызовов всех деструкторов
		QFW::$router->startDisplayMain();
		return $this->P->HeaderFilter($content);
	}

	//Функции ескейпинга с учетом utf8
	public function esc($s) { return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}


}

?>