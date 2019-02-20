<?	class Model {
		function __construct($test = 1){
			// Adatbázis
			
				$this->db = new Database($test);
							
			// Facebook
				$this->facebook = new FacebookAPI(array(
					"appId" => FBAPPID,
					"secret" => FBSECRET
				));
				
		}
		
		function open($model, $ext = null){
			$pg = (!$ext) ? 'index.php' : $ext . '.php' ;
			if(file_exists(MODEL . $model . '/' . $pg)){
				require MODEL . $model . '/' . $pg;
			}else if(file_exists(MODEL . $model . '.php')){
				require MODEL . $model . '.php';
			}else{
				$noModel = true;	
			}
			$em = (!$ext) ? '' : '_'.$ext ;
			if(!$noModel){
				$mod = $model . $em . '_Model';
				return $md = new $mod();
			}
		}
		
		function openLib($lib_name, $arg = array()){
			if(file_exists(LIBS . $lib_name . ".php")){
				require LIBS . $lib_name . ".php";
			}else{
				$cantOpen = true;	
			}
			
			if(!$cantOpen){
				return $lib = new $lib_name($arg);	
			}
		}


		function __destruct()
		{
			$this->db = null;
		}
	}

?>