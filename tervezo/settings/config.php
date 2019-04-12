<?
  	////////////////////////////////////////
	// Protocol
	$protocol = 'https://';
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$protocol = 'https://';
	}
	define('DEVMODE',true);
	define('DLANG', 'hu');
	// Domain név
	define('DOMAIN',$protocol.$_SERVER['HTTP_HOST'].'/');
	define('CURRENT_URI',$protocol.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"] );
	define('MDOMAIN',$_SERVER['HTTP_HOST']);
	define('CLR_DOMAIN',str_replace(array($protocol,"www."),"",substr('www.'.DOMAIN,0,-1)));
	// Üdvözlő üzenet
	define('WELCOME','Üdvözlünk a '.TITLE.' weboldalán!');
	define('AJAX_GET','/ajax/get/');


	////////////////////////////////////////
	// Ne módosítsa innen a beállításokat //
	date_default_timezone_set('Europe/Berlin');

	// PATH //
		define('TEMP','v1.0');
		define('PATH', realpath($_SERVER['HTTP_HOST']));
		define('APP_PATH','application/');
		define('LIBS','../admin/'.APP_PATH . 'libs/');
		define('MODEL',APP_PATH . 'models/');
		define('VIEW',APP_PATH . 'views/'.TEMP.'/');
		define('CONTROL',APP_PATH . 'controllers/');
		define('STYLE','/src/css/');
		define('SSTYLE','/public/'.TEMP.'/styles/');
		define('JS','/src/js/');
		define('SJS','/public/'.TEMP.'/js/');
		define('UPLOADS',	$protocol.'cp.moza.web-pro.hu/src/uploads/');
		define('IMG',		$protocol.'cp.moza.web-pro.hu/src/images/');
		define('IMGDOMAIN',	$protocol.'cp.moza.web-pro.hu/');
		define('SOURCE',	$protocol.'cp.moza.web-pro.hu/src/');

	// Környezeti beállítások //
		define('SKEY','sdfew86f789w748rh4z8t48v97r4ft8drsx4');
		define('NOW',date('Y-m-d H:i:s'));
		define('PREV_PAGE',$_SERVER['HTTP_REFERER']);
		define('RPDOCUMENTROOT', '../admin/src/uploaded_files');
		define('PRODUCTIONSITE', true);

	// Adminisztráció

		define('ADMROOT',$protocol.'cp.moza.web-pro.hu/');

	require "data.php";
?>
