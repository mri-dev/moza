<?
  class Lang{
	private static $lang_root = 'lang';
	private static $language_content 	= false;


	public static function content($string){
		$langfile = self::$lang_root .'/'. self::getLang() . '.txt';

		if(file_exists($langfile)){

			if(!self::$language_content){
				$ctx 	= @file_get_contents($langfile);
				self::$language_content = $ctx;
			}else{
				$ctx = self::$language_content;
			}

			$src 	= self::formatToArray($ctx);

			$string = (array_key_exists($string,$src))?$src[$string]:$string;

			return $string;
		}else{
			return $string;
		}
	}


	private static function formatToArray($str){
		$arr = array();
		$a_str = explode(';;',rtrim($str,';;'));
		foreach($a_str as $as){
			$b_str = explode('::',$as);
			$arr[trim($b_str[0])] = trim($b_str[1]);
		}

		return $arr;
	}

	public static function setLang($langKey){
		setcookie('lang', $langKey, time() + 60*60*24*7, '/');
	}

	public static function getLang(){
		$lang = DLANG;

		if($_COOKIE[lang] != ''){
			$lang = $_COOKIE[lang];
		}

		return $lang;
	}

	public static function ip_info($ip = NULL, $purpose = "countrycode", $deep_detect = TRUE) {
		$output = NULL;
		if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
			$ip = $_SERVER["REMOTE_ADDR"];
			if ($deep_detect) {
				if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
		}
		$purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
		$support    = array("country", "countrycode", "currencycode", "state", "region", "city", "location", "address");
		$continents = array(
			"AF" => "Africa",
			"AN" => "Antarctica",
			"AS" => "Asia",
			"EU" => "Europe",
			"OC" => "Australia (Oceania)",
			"NA" => "North America",
			"SA" => "South America"
		);
		if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
			$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

			if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
				switch ($purpose) {
					case "location":
						$output = array(
							"city"           => @$ipdat->geoplugin_city,
							"state"          => @$ipdat->geoplugin_regionName,
							"country"        => @$ipdat->geoplugin_countryName,
							"country_code"   => @$ipdat->geoplugin_countryCode,
							"continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
							"continent_code" => @$ipdat->geoplugin_continentCode,
							"currency_code"  => @$ipdat->geoplugin_currencyCode,
						);
						break;
					case "address":
						$address = array($ipdat->geoplugin_countryName);
						if (@strlen($ipdat->geoplugin_regionName) >= 1)
							$address[] = $ipdat->geoplugin_regionName;
						if (@strlen($ipdat->geoplugin_city) >= 1)
							$address[] = $ipdat->geoplugin_city;
						$output = implode(", ", array_reverse($address));
						break;
					case "city":
						$output = @$ipdat->geoplugin_city;
						break;
					case "state":
						$output = @$ipdat->geoplugin_regionName;
						break;
					case "region":
						$output = @$ipdat->geoplugin_regionName;
						break;
					case "country":
						$output = @$ipdat->geoplugin_countryName;
						break;
					case "countrycode":
						$output = @$ipdat->geoplugin_countryCode;
						break;
					case "currencycode":
						$output = @$ipdat->geoplugin_currencyCode;
						break;
				}
			}
		}
		return $output;
	}
  }
?>
