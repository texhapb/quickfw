<?php

require_once(QFWPATH.'/QuickFW/Cacher/Interface.php');

class Cacher_Bdb implements Zend_Cache_Backend_Interface
{
	protected $file = NULL;

	public function __construct()
	{
	}

	public function setDirectives($directives)
	{
		$this->file=dba_open(TMPPATH.'/'.(isset($directives['file'])?$directives['file']:'cache').'.db4','cd','db4');
	}

	public function save($data, $id, $tags = array(), $specificLifetime = 3600)
	{
		return dba_replace($id,serialize(array(time()+$specificLifetime,$data)),$this->file);
	}

	public function load($id, $doNotTest = false)
	{
		$data=dba_fetch($id,$this->file);
		if (!$data)
			return false;
		$data=unserialize($data);
		if ($data[0]<time())
		{
			dba_delete($id,$this->file);
			return false;
		}
		return $data[1];
	}

	public function test($id)
	{
		return ($this->load($id)!==false);
	}

	public function remove($id)
	{
		return dba_delete($id,$this->file);
	}

	public function clean($mode = CACHE_CLR_ALL, $tags = array())
	{
		$key=dba_firstkey($this->file);
		if (!$key)
			return;
		do {
			if ($mode == CACHE_CLR_ALL)
				dba_delete($key,$this->file);
			elseif($mode == CACHE_CLR_OLD)
			{
				$data=dba_fetch($key,$this->file);
				$data=unserialize($data);
				if ($data[0]<time())
					dba_delete($key,$this->file);
			}
		} while($key=dba_nextkey($this->file));
		dba_optimize($this->file);
		dba_sync($this->file);
	}

}
?>