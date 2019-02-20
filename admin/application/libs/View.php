<?	class View {
		public $title = TITLE;
		public $called = null;

		function __construct(){}

		function render($item,$fnList = null){
            $step = 0;

            if($fnList){
                $f = VIEW.$item.'.php';
                $f = rtrim($f,'/');
                if(file_exists($f)){
                    require $f;
                    return true;
                }
            }

			if(strpos($item,"::") !== false){
				$ext 	= explode("::",$item);
				$pl 	= $ext[0] .'/'. $ext[1].'.php';
				$item 	= '';
			}else{
				$pl 	= '/index.php';
			}

			$vw = array(
				VIEW . $item . $pl,
				VIEW . $item . '.php'
			);

			if($this->called != null){
				 $call = VIEW . $item . '/' . $this->called . '.php';
                    if(!file_exists($call)){
                        $call = VIEW . $item . '/' . $this->called . '/index.php';
                    }
				 $vw[] = $call;
			}

			$target = (file_exists($vw[2])) ? $vw[2] : false ;

			if(!$target){
                $step++;
				$target = (file_exists($vw[0])) ? $vw[0] : false ;
			}

			if(!$target){
                $step++;
				$target = (file_exists($vw[1])) ? $vw[1] : false ;
			}

			if($target){
				if(!$this->clear){
                    if($step == 0){
                        $this->openSubPage($target);
                    }else{
                        require $target;
                    }

				}
			}
		}

		function openSubPage($fl){

			$fl 	= str_replace(".php","",$fl);
			$st 	= substr(VIEW,0,-1);
			$ex 	= substr($fl,strpos($fl,$st)+strlen($st)+1);
			$exi 	= explode("/",$ex);
			$param 	= Helper::getParam();
			$par 	= ($param[0] != null) ? $param[0].'.php' : 'index.php' ;

			$exp 	= VIEW . $exi[0] .'/'. $exi[1] .'/'.$par;

			if(file_exists($exp)){
				@require $exp;
			}else{
				@require VIEW . $exi[0] .'/'. $exi[1].'/index.php';
			}
		}

		function setStyle($type = "default"){
			return STYLE . $type . '.css';
		}

		function addStyle($style, $after = '', $source = true){
			if($source){
				return '<link rel="stylesheet" type="text/css" href="' . STYLE . $style .'.css'. ( (defined('DEVMODE') && DEVMODE === true)?'?t='.uniqid():'' ) .'" '.$after.'/>'."\n\r";
			}else{
				return '<link rel="stylesheet" type="text/css" href="' . SSTYLE . $style .'.css'. ( (defined('DEVMODE') && DEVMODE === true)?'?t='.uniqid():'' ) .'" '.$after.'/>'."\n\r";
			}
		}

		// Facebook content
		function addOG($type, $content){
			return '<meta property="og:'.$type.'" content="'.$content.'" />'."\n\r";
		}

		// Meta content
		function addMeta($name, $content){
			return '<meta name="'.$name.'" content="'.$content.'" />'."\n\r";
		}

		function addJS($file, $type = false, $source = true){
			if($source){
				$wt = (!$type) ? JS.$file.'.js'.( (defined('DEVMODE') && DEVMODE === true)?'?t='.uniqid():'' ) : $file ;
				return '<script type="text/javascript" src="'.$wt.'"></script>'."\n\r";
			}else{
				$wt = (!$type) ? SJS.$file.'.js'.( (defined('DEVMODE') && DEVMODE === true)?'?t='.uniqid():'' ) : $file ;
				return '<script type="text/javascript" src="'.$wt.'"></script>'."\n\r";
			}
		}

        function show($path, $inside = false){
            $file = VIEW;

            if($inside){
                $main = $this->gets[0].'/';
                $file .= $main;
            }

            $file .= $path.'.php';

            if(file_exists($file)){
                require_once $file;
            }
        }

        function getPreferences($prefs = array(), $arg = array()){
            $parent_path = VIEW.$this->gets[0].'/preferences/';
            $css_file = ($arg[css] != '') ? $arg[css].'.' : 'main.';

            if(!empty($prefs))
                foreach($prefs as $pf){
                    switch($pf){
                        case 'js':
                            $file = $parent_path.'main.'.$pf;
                            if(file_exists($file))
                                echo '<script type="application/javascript" src="/'.$file.'"></script>';
                            break;
                        case 'css':
                            $file = $parent_path.$css_file.$pf;

                            if(file_exists($file))
                                echo '<link rel="stylesheet" type="text/css" href="/'.$file.'" media="screen">';
                            break;
                    }
                }
        }
	}
?>
