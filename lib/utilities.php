<?php

/**
 * Выполняет HTTP-запрос по заданному URL,
 *
 *@var string url адрес запроса
 *@return string|false|null Возвращает: ответ сервера (без заголовков), false в случае неудачи или null в случае таймаута
 */
function getUrl($url,$data=array())
{
	$c = curl_init();
	curl_setopt_array($c, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FOLLOWLOCATION => 1,
	));
	if (array_key_exists('post',$data))	//POST запрос
		curl_setopt_array($c, array(
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => is_array($data['post'])?http_build_query($data['post']):$data['post'],
		));
	if (isset(QFW::$config['consts']['curlTimeOut']))
		curl_setopt($c, CURLOPT_TIMEOUT, QFW::$config['consts']['curlTimeOut']),
	if (array_key_exists('sid',$data))
		curl_setopt($c, CURLOPT_COOKIE, 'PHPSESSID='.$data['sid']);
	if (array_key_exists('user',$data) && array_key_exists('pass',$data))	//запрос авторизации
		curl_setopt($c, CURLOPT_USERPWD, $data['user'] . ":" . $data['pass']);
	if (isset(QFW::$config['host']['proxy']))
		curl_setopt($c, CURLOPT_PROXY, QFW::$config['host']['proxy']);

	$content = curl_exec($c);
	$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	if (curl_errno($c) == 28)	//CURLE_OPERATION_TIMEDOUT
		return null;
	if (array_key_exists('rcode',$data))
		return array(
			'code'=>$code,
			'content'=>$content,
		);
	if ($code>=300)
		return false;
	return $content;
}


/**
 * Вывод сообщения с разбивкой длинных слов без повреждения тегов
 */
function msg2html($s,$n=50) {

	$marker = " <> ";

	# Сохраняем все тэги чтобы уберечь их от разбивки
	preg_match_all("/(<.*?>)/si",$s,$tags);

	# Заменяем все тэги на маркеры
	$s =  preg_replace("/(<.*?>)/si", $marker, $s);

	$s = preg_replace('|\S{'.$n.'}|u','\0 ',$s);

	# Восстанавливаем тэги в места которых были отмечены маркерами
	for ($i=0; $i<count($tags[0]);  $i++)
		$s = preg_replace("/$marker/si", $tags[1][$i], $s, 1);

	return $s;
}

/**
 * 	Преобразование URL в ссылки
 */
function make_urls($string, $nofollow=false) {
    $p = '/((?:(?:ht|f)tps?:\/\/|www\.)[^<\s\n]+)(?<![]\.,:;!\})<-])/msiu';
    $r = '<a href="$1">$1</a>$2';

    $string = preg_replace($p, $r, $string);

    $p = '/ href="www\./msiu';
    $r = ' href="http://www.';

    return preg_replace($p, $r, $string);
}

/**
 * 	Печать размера файла
 */
function printSize($size)
{
	if ($size>1024*10)
		return (round($size/1024*100)/100).' Kb';
	return $size.'b';
}

/**
 * 	Усечение UTF8 строк
 */
function my_trim($srt,$size)
{
	if ((mb_strlen($srt)-3)>$size)
		$srt=mb_substr($srt,0,$size-3).'...';
	return $srt;
}

/**
 * Прообразование xml в массив
 */
function xml2array($xml,$attrName='attr',$arrFlar='array')
{
	$xmlary = array();

	$reels = '/<(\w+)\s*([^>]*)\s*(?:\/>|>(.*?)<\/\s*\\1\s*>)/s';
	$reattrs = '/(\w+)=(?:"|\')([^"\']*)(?:"|\')/';

	preg_match_all($reels, $xml, $elements);

	foreach ($elements[1] as $ie => $xx)
	{
		$name=$elements[1][$ie];

		//для получения блока текста
		$cdend = strpos($elements[3][$ie], "<");
		if ($cdend > 0)
			$xmlary[$name][$ie]["text"] = substr($elements[3][$ie], 0, $cdend);

		if (preg_match($reels, $elements[3][$ie]))
			$xmlary[$name][$ie] = xml2array($elements[3][$ie]);
		else if ($elements[3][$ie])
			$xmlary[$name][$ie] = $elements[3][$ie];
		else
			$xmlary[$name][$ie] = null;

		if ($attributes = trim($elements[2][$ie]))
		{
			preg_match_all($reattrs, $attributes, $att);
			foreach ($att[1] as $ia => $xx)
				$xmlary[$name][$ie][$attrName][$att[1][$ia]] = $att[2][$ia];
		}

	}
	foreach ($xmlary as $k => $v)
	{
		if (count($v)==1)
			$xmlary[$k]=current($v);
		else
		{
			$xmlary[$k]=array_values($v);
			$xmlary[$k][$arrFlar]=$k;
		}
	}

	return $xmlary;
}

/**
 * Прообразование массива в xml
 */
function array2xml($array,$attrName='attr',$arrFlar='array')
{
	$xml=array();
	$subattr='';
	$arr=array_key_exists($arrFlar,$array)?$array[$arrFlar]:false;
	foreach ($array as $k => $v)
	{
		if ($k===$arrFlar)
			continue;
		if ($k===$attrName)
		{
			foreach ($v as $an => $av)
				$v[$an]=$an.'="'.$av.'"';
			$subattr=' '.join($v,' ');
			continue;
		}
		$k=$arr?$arr:$k;
		if (is_array($v))
		{
			$carr=array_key_exists($arrFlar,$v);
			$child=array2xml($v,$attrName);
			if (is_array($child) && !$carr)
				$xml[]='<'.$k.$child['attr'].'>'.$child['xml'].'</'.$k.'>';
			elseif ($carr)
				$xml[]=$child;
			else
				$xml[]='<'.$k.'>'.$child.'</'.$k.'>';
		}
		elseif ($v!=null)
			$xml[]='<'.$k.'>'.$v.'</'.$k.'>';
		else
			$xml[]='<'.$k.'/>';
	}
	$xml=join("\n",$xml);
	if ($subattr!='')
		return array('xml'=>$xml,'attr'=>$subattr);
	return $xml;
}

?>
