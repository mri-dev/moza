<?
  	////////////////////////////////////////
	// Domain név
	define('DM', 'moza.web-pro.hu');
	define('DLANG', 'hu');
	define('DOMAIN','https://'.$_SERVER['HTTP_HOST'].'/');
	define('MDOMAIN',$_SERVER['HTTP_HOST']);
	define('CLR_DOMAIN',str_replace(array("http://","www."),"",substr('www.'.DOMAIN,0,-1)));
	define('HOMEDOMAIN','https://www.'.DM.'/');
	// AJAX PATH's
	define('AJAX_GET','/ajax/get/');
	define('AJAX_POST','/ajax/post/');

	////////////////////////////////////////
	// Ne módosítsa innen a beállításokat //
	    date_default_timezone_set('Europe/Berlin');
	// PATH //
		define('TEMP','v1.0');
		define('PATH', realpath($_SERVER['HTTP_HOST']));
		define('APP_PATH','application/');
		define('LIBS',APP_PATH . 'libs/');
		define('MODEL',APP_PATH . 'models/');
		define('VIEW',APP_PATH . 'views/'.TEMP.'/');
		define('CONTROL',APP_PATH . 'controllers/');
		define('STYLE','/src/css/');
		define('SSTYLE','/public/'.TEMP.'/styles/');
		define('JS','/src/js/');
		define('SJS','/public/'.TEMP.'/js/');
		define('IMG','https://cp.'.DM.'/src/images/');
		define( 'FILE_BROWSER_IMAGE', JS.'tinymce/plugins/filemanager/dialog.php?type=1&lang=hu_HU');

	// Környezeti beállítások //
		define('SKEY','sdfew86f789w748rh4z8t48v97r4ft8drsx4');
		define('NOW',date('Y-m-d H:i:s'));
		define('PREV_PAGE',$_SERVER['HTTP_REFERER']);
		define('UPLOADS', 'src/uploads/');
		define('RPDOCUMENTROOT', '/home/webprohu/moza.web-pro.hu/cp/src/uploaded_files');


	// Adminisztráció
		define('ADMROOT','https://www.cp.'.DM.'/');
		define('SOURCE', 'https://www.'.DM.'/admin/src/' );

	require "data.php";
?>
