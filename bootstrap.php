<?php

/**
 * Single entry point for all HTTP requests
 *
 * @package Asap
 */


// Fix for nginx
if (empty($_SERVER['REDIRECT_URL']))
{
	$sUrl = $_SERVER['REQUEST_URI'];
	if ($iPos = strpos($sUrl, '?'))
		$sUrl = substr($sUrl, 0, $iPos);
	$_SERVER['REDIRECT_URL'] = $sUrl;
}



/*
$iIter = 1000000;

function f1($aData)
{

}

function f2(&$aData)
{

}

$t1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	f1(array('toto' => 'tata', 'tutu' => 'titi', 'tete' => 'tyty', 'trtr' => 'tyru'));
}
$t2 = microtime(true);
echo 'Temps copie : ' . ($t2 - $t1) . '<br />';

$t1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	$aData = array('toto' => 'tata', 'tutu' => 'titi', 'tete' => 'tyty', 'trtr' => 'tyru');
	f2($aData);
}
$t2 = microtime(true);
echo 'Temps ref : ' . ($t2 - $t1) . '<br />';

die();
*/

//var_dump($_SERVER['HTTP_ACCEPT_LANGUAGE']);

/*
echo '<br />';

$iIter = 1000000;

$sVal = 'fr-fr';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	list($s1, $s2) = explode('-', $sVal);
	$sKey = $s1 . '_' . strtoupper($s2);
}
$f2 = microtime(true);
echo 'Temps explode : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	$sKey = substr($sVal, 0, 2) . '_' . strtoupper(substr($sVal, 3));
}
$f2 = microtime(true);
echo 'Temps substr : ' . ($f2 - $f1) . '<br />';

die();
*/

//var_dump(get_loaded_extensions());

//var_dump(PDO::MYSQL_ATTR_INIT_COMMAND);

/*
$sText = 'SELECT * FROM coucou WHERE champ1 = ? AND champ2 = ? INNER JOIN toncul ON pepete = infini serialize()qdqdqdqdqsdqsdqsdsqdqsdqsdqsdqsdqsdqsdqsdqsqdqsdqsdqd';

$iIter = 1000000;

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
	$sHash = md5($sText);
$f2 = microtime(true);
echo 'Temps md5 : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
	$sHash = sha1($sText);
$f2 = microtime(true);
echo 'Temps sha1 : ' . ($f2 - $f1) . '<br />';
*/



/*
$aData = array(':name' => 'coucou', ':pseudo' => 'emilie', ':age' => 15);

$iIter = 100000;

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
	$sHash = serialize($aData);
$f2 = microtime(true);
echo 'Temps serialize : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	$sHash = '';
	foreach ($aData as $sKey => &$sVal)
		$sHash .= $sKey . ':' . $sVal . '|';
}
$f2 = microtime(true);
echo 'Temps concat : ' . ($f2 - $f1) . '<br />';
*/




register_shutdown_function('session_write_close');


require_once(dirname(__FILE__) . '/Core/Asap.php');

/*
require_once(ASAP_MAIN_DIR . 'Util/Util.php');
require_once(ASAP_MAIN_DIR . 'Core/Route/Route.php');

require_once(ASAP_MAIN_DIR . 'Util/Timer.php');
*/
//var_dump(ctype_digit('0xFFFFFF'));
//var_dump(ctype_digit(0xFFFFFF));
//var_dump(is_int(0xFFFFFF));

$oAsap = Asap_Core_Asap::init();

//User::findOne(array('test' => array('val1', 'val2', "va'l3")));

/*
require_once('Util/Packer.php');

Asap_Util_Packer::init();

$iIter = 100000;

$sVal = 'fr-fr';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	Asap_Util_Packer::_getFilename1('js');
}
$f2 = microtime(true);
echo 'Temps 1 : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	Asap_Util_Packer::_getFilename2('js');
}
$f2 = microtime(true);
echo 'Temps 2 : ' . ($f2 - $f1) . '<br />';

die();
*/





$oAsap->routeAndLaunch();


//require_once(ASAP_DIR_MODEL . 'User.php');
//$oUser = new User('coucou');

/*
echo '<br />';

$iIter = 100000;
$iNb2 = 10;

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	$GLOBALS['cache'] = Asap_Cache_Generic::getInstance();
	for ($j = 0; $j < $iNb2; $j++)
		$oCache = $GLOBALS['cache'];
}
$f2 = microtime(true);
echo 'Temps global : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	for ($j = 0; $j < $iNb2; $j++)
		$oCache = Asap_Cache_Generic::getInstance();
}
$f2 = microtime(true);
echo 'Temps direct : ' . ($f2 - $f1) . '<br />';

$f1 = microtime(true);
for ($i = 0; $i < $iIter; $i++)
{
	for ($j = 0; $j < $iNb2; $j++)
		$oCache = Asap_Core_Asap::getInstance()->getCache();
}
$f2 = microtime(true);
echo 'Temps indirect : ' . ($f2 - $f1) . '<br />';
*/


/*
$aCheck = array('coucou' => 'caca', 'toto' => 'tata', 'pipi' => 'papa', 'nene' => 'nana', 'zizi' => 'zaza');
$sCheck = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum varius dui ac leo blandit et consectetur tortor pulvinar. Morbi augue tellus, zizi vitae sollicitudin sed, tincidunt vel sem. Curabitur mattis sapien non ante rutrum porta. Aliquam tincidunt sagittis tellus quis rutrum. Aliquam erat volutpat. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Cras eu tellus mi. Sed eleifend vehicula sapien eget porttitor. Duis et nisi sem. Aenean molestie mauris non sem congue eget sagittis felis tempus. Nulla pharetra sollicitudin nisl, nec posuere sapien porta eget. Curabitur luctus mi eu lorem porta eget sodales lorem bibendum.

Nam lacinia arcu nec est nene coucou lacinia nisi lacinia. Integer at ultrices ante. Nam laoreet lacus pipi. Duis lorem leo, facilisis ornare dictum ut, zizi sit amet nisl. Sed hendrerit lobortis nulla ut zizi. Vivamus ac justo velit, vel convallis odio. Nam interdum vestibulum dolor, eget tempor odio vehicula pharetra. Aliquam accumsan purus non sapien sagittis fermentum.

Sed id purus nene. Quisque in mi non orci pretium venenatis sed vel orci. Etiam et libero toto, sed faucibus urna. Curabitur eget mauris nibh, id sollicitudin justo. Ut ut consequat sem. Morbi massa diam, tempor ut volutpat et, tempor vel est. Pellentesque et tellus orci, at vehicula lacus. Suspendisse pulvinar vehicula bibendum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a nulla nec nisl volutpat fringilla et et neque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vivamus dapibus, purus id auctor hendrerit, eros diam hendrerit purus, vel mollis ipsum est ut massa. Nunc et porttitor nisl. Morbi sagittis ullamcorper ipsum, at porttitor ante luctus non. Sed in dolor purus. Sed et elit ligula, vitae iaculis ante.';


$oTime = new Timer('strtr');
for ($i = 0; $i < 100000; $i++)
	$sStr = strtr($sCheck, $aCheck);
$oTime->stop();

$oTime = new Timer('str_replace');
for ($i = 0; $i < 100000; $i++)
	$sStr = str_replace(array_keys($aCheck), array_values($aCheck), $sCheck);
$oTime->stop();
*/


//$oTime = new Timer('array_merge');
//for ($i = 0; $i < 100000; $i++)
	//Asap\Core\Asap::getInstance()->getParameter('friends.igor');
//$oTime->stop();

/*
use Asap\Core\Route;
$sRegex = Route::toRegex('/escort/{ville}/{id}-{nom}');
$aParams = Route::extractParams('/escort/{ville}/{id}-{nom}');
var_dump($aParams);
var_dump($sRegex);
$aTest = array('/escort/', '/escort/paris/1234-coucou', '/escort/paris/coucou-124-blabla');
foreach ($aTest as $sTest)
{
	$aRes = array();
	var_dump(preg_match($sRegex, $sTest, $aRes));
	var_dump($aRes);
}
*/
