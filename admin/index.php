<?
	/*
	* Könyvtárak
	*/
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));

	//die('Az oldal átmenetileg nem elérhető!');

	ini_set('display_errors', 0);

	require "settings/config.php";

	if( $_GET[dev] ){
		require 'devautoload.php';
	}else require 'autoload.php';

	$start = new Start();

	function __($text){
		return $text;
	}
?>
