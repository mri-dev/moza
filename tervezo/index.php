<?php
	/*
	* Könyvtárak
	*/
	session_cache_limiter('none');
	//die('Az oldal átmenetileg nem elérhető!');
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
	ini_set('display_errors', 0);

	require "settings/config.php";

	if( $_GET[dev] ){
		require 'devautoload.php';
	}else require 'autoload.php';

   /* $file       = __FILE__;
    $timestamp  = filemtime($file);
    $gmt_mtime = gmdate('r', $timestamp);

    header('ETag: "'.md5($timestamp.$file).'"');
    header('Last-Modified: '.$gmt_mtime);
    header('Cache-Control: public');
    //header('Pragma: public');
    //header('Expires: '. gmdate(DATE_RFC1123, time() - ($cacheTime)));

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp.$file)) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }
    */

	$start = new Start();

	function __( $text){
		return Lang::content($text);
	}
?>
