<?
namespace PortalManager;

use PortalManager\Traffic;
use PortalManager\Coupons;
use PortalManager\PartnerReferrer;

/**
 *  * @version 1.0
 */
class AdminUser
{
	const LOGINHANDLE_MODE 				= LOGIN_MODE;
	const ORDER_STATUS_KEY_DONE 		= 4;
	const ORDER_STATUS_KEY_DEFAULT 		= 1;
	const ORDER_STATUS_KEY_DELETED 		= 13;
	const SUPER_ADMIN_PRIV_INDEX		= \PortalManager\Admin::SUPER_ADMIN_PRIV_INDEX;

	private $db = null;
	private $settings = null;
	public $admin = null;
	public $admin_jog 					= 100; 	// Szuper admin = 0, alapé.: 100

	function __construct( $arg = array() ){
		$this->db 		= $arg[db];
		$this->settings = $arg[settings];

		switch(self::LOGINHANDLE_MODE){
			default: case 'session':
				$this->admin = $_SESSION[adm];
			break;
			case 'cookie':
				$this->admin = $this->getAdminByCookieToken($_COOKIE[__admin]);
			break;
		}

		$this->getAdminStatus();
	}

	/**
	 * Termék eltávolítás a kosárból
	 * @param  boolean $id A termék azonosítója
	 * @return void
	 */
	public function removeProductFromCart( $id = false ){
		if( !$id ) return false;

		$this->db->query( "DELETE FROM shop_kosar WHERE termekID = $id" );
	}

	private function setCookieToken($admin){
		$token = md5(time());
		$this->db->update("admin",
		array(
			"valid_cookie_token" => $token
		),
		"user = '$admin'");

		return $token;
	}

	public function getAdminStatus(){
		if( !$this->admin ) return true;

		$data = $this->db->query("SELECT engedelyezve, jog FROM admin WHERE user = '$this->admin'")->fetch(\PDO::FETCH_ASSOC);

		$this->admin_jog = (int)$data['jog'];

		return ($data['engedelyezve'] == 1) ? true : false;
	}

	private function getAdminByCookieToken($token){
		$admin = $this->db->query("SELECT user FROM admin WHERE valid_cookie_token = '$token'")->fetch(\PDO::FETCH_COLUMN);

		return $admin;
	}

	private function getCookieToken($token){
		$admin = $this->db->query("SELECT user FROM admin WHERE valid_cookie_token = '$token'")->fetch(\PDO::FETCH_COLUMN);

		return $admin;
	}

	function isLogged(){
		if(isset($this->admin) && $this->admin != ''){
			if(self::LOGINHANDLE_MODE == 'cookie'){
				setcookie("__admin",$_COOKIE[__admin],time()+60*60*24,"/");
			}

			$this->db->query("UPDATE admin SET utolso_aktivitas = now() WHERE user = '$this->admin'");

			return true;
		}else{
			return false;
		}
	}

	function getStats(){
		$ret = array();
			// Új megrendelések
			$inew = array(
				'tetel' => 0,
				'ar' 	=> 0,
				'db'  	=> 0
			);
			$new = $this->db->query("SELECT count(orderKey) as me, sum(me) as tetel, sum(me*egysegAr+szallitasi_koltseg-kedvezmeny) as ar FROM `order_termekek` as t LEFT OUTER JOIN orders as o ON o.ID = t.orderKey WHERE o.allapot = ".self::ORDER_STATUS_KEY_DEFAULT." GROUP BY t.orderKey");
			$inew[db] 	= $new->rowCount();
			$data 		= $new->fetchAll(\PDO::FETCH_ASSOC);

			foreach($data as $newd){
				$inew[tetel] += $newd[tetel];
				$inew[ar] += $newd[ar];
			}

			$ret[orders][news] = array(
				'tetel' => $inew[tetel],
				'ar' 	=> $inew[ar],
				'db' 	=> $inew[db]
			);

			// Folyamatban lévő megrendelések
			$iprogress = array(
				'tetel' => 0,
				'ar' 	=> 0,
				'db'  	=> 0
			);
			$progress = $this->db->query("SELECT count(orderKey) as me, sum(me) as tetel, sum(me*egysegAr+szallitasi_koltseg-kedvezmeny) as ar FROM `order_termekek` as t LEFT OUTER JOIN orders as o ON o.ID = t.orderKey WHERE o.allapot != ".self::ORDER_STATUS_KEY_DONE." and o.allapot != ".self::ORDER_STATUS_KEY_DELETED." and o.allapot != ".self::ORDER_STATUS_KEY_DEFAULT." GROUP BY t.orderKey");
			$iprogress[db] 	= $progress->rowCount();
			$data 			= $progress->fetchAll(\PDO::FETCH_ASSOC);

			foreach($data as $progressd){
				$iprogress[tetel] += $progressd[tetel];
				$iprogress[ar] += $progressd[ar];
			}

			$ret[orders][progress] = array(
				'tetel' => $iprogress[tetel],
				'ar' 	=> $iprogress[ar],
				'db' 	=> $iprogress[db]
			);

			// Felhasználók
			$users 		= $this->db->query("SELECT *,datediff(now(), utoljara_belepett) as lastLoginDiff FROM felhasznalok");
			$thisMonth 	= date('Y-m');

			$aktivalt 	= 0;
			$ebbenahonapban = 0;
			$loginInThisWeek = 0;

			foreach($users->fetchAll() as $d){
				// Aktivált
				if(!is_null($d[aktivalva])) $aktivalt++;
				// Ebben a hónapban
				$regYM = substr($d[regisztralt],0,7);
				if($regYM == $thisMonth){
					$ebbenahonapban++;
				}
				// Az elmúlt héten beléptek
				if($d[lastLoginDiff] <= 7){
					$loginInThisWeek++;
				}
			}
			$ret[users][total] 			= $users->rowCount();
			$ret[users][activated] 		= $aktivalt;
			$ret[users][regInThisMonth] = $ebbenahonapban;
			$ret[users][loginInThisWeek] = $loginInThisWeek;

			$search = $this->getSearchMostStat();
			$ret[search][total] 		= $search[info][total_num];
			$ret[search][data] 			= $search[data];

			$termekView = $this->getTermekMostViewStat();
			$ret[termekView][total] 		= $termekView[info][total_num];
			$ret[termekView][data] 			= $termekView[data];

			$kat = $this->getKategoriaAvStat();
			$ret[kategoria][total] 			= $kat[info][total_num];
			$ret[kategoria][data] 			= $kat[data];

			$q = "SELECT
				u.*,
				t.nev as item_nev,
				getTermekUrl(u.item_id,'".$this->settings['page_url']."') as item_url
			FROM uzenetek as u
			LEFT OUTER JOIN shop_termekek as t ON t.ID = u.item_id
			LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
			WHERE
				u.ID IS NOT NULL and
				IF(
					u.felado_email IS NOT NULL,
					IF(u.valaszolva IS NULL and u.archivalva = 0,1,0),
					IF(u.archivalva = 0, 1, 0)
				) = 1
			ORDER BY
				u.elkuldve DESC LIMIT 0, 10;";

			// Last Messages
			$lastMsg = $this->db->query( $q )->fetchAll(\PDO::FETCH_ASSOC);
			$ret[lastMessages][data] = $lastMsg;
		return $ret;
	}

	function getSlideShow( $arg = array() ){
		$q = "SELECT * FROM slideshow WHERE 1=1 ";

		if( $arg['group'] ) {
			$q .= " and groups = '".$arg['group']."' ";
		}

		$q .= " ORDER BY lathato DESC, sorrend ASC";

		extract($this->db->q($q,array('multi' => '1')));

		return $data;
	}

	public function getOrderData($key){
		$q = "SELECT
			o.*
		FROM orders as o
		WHERE o.accessKey = '$key'";
		extract($this->db->q($q));



		$data[items] = $this->getOrderListItems($data[ID]);

		/**
		 * Utánvétel összegének kiszámolása
		 * */
		$uv = 0;
		// Ha a fizetési mód utánvételre lett állítva
		if( $data['fizetesiModID'] == $this->db->query("SELECT bErtek FROM beallitasok WHERE bKulcs = 'flagkey_pay_ontransfer_id'; ")->fetchColumn() ) {

			if( $data[szallitasi_koltseg] > 0 ) {
				$uv += $data[szallitasi_koltseg];
			}

			foreach( $data[items] as $item ) {
				$uv += \PortalManager\Formater::discountPrice( $item['subAr'], $data[kedvezmeny_szazalek], true );
			}
		}

		$data[utanvatel_osszeg] = $uv;

		return $data;
	}
	function listAllOrder($arg = array()){
		$q = "SELECT
			o.* ,
			fm.nev as fizetesMod
		FROM orders as o
		LEFT OUTER JOIN shop_fizetesi_modok as fm ON fm.ID = o.fizetesiModID
		WHERE o.ID IS NOT NULL
		";
		if($arg[excInProgress]){
			$q .= " and o.allapot IN (11,12) ";
		}
		$arg[multi] = "1";
		extract($this->db->q($q,$arg));

		$back = array();

		foreach($data as $d){
			$d[items] = $this->getOrderItems($d[ID]);
			$back[] = $d;
		}

		$data = $back;

		return $data;
	}
	function getMegrendelesek($arg = array())
	{
		$stat = array();
		$q = "SELECT
			o.*
		FROM orders as o
		LEFT OUTER JOIN order_allapot as oa ON oa.ID = o.allapot
		WHERE 1=1 ";

		if (isset($arg['archivalt']))
		{
			$q .= " and o.archivalt ='".$arg['archivalt']."'";
		}

		if (isset($arg['onlyreferer']))
		{
			$q .= " and o.referer_code IS NOT NULL ";
		}

		if (isset($arg['referer']))
		{
			$q .= " and o.referer_code = refererID(".$arg['referer'].")";
		}

		if (isset($arg['referercode']))
		{
			$q .= " and o.referer_code = '".$arg['referercode']."'";
		}

		if (isset($arg['couponcode']))
		{
			$q .= " and o.coupon_code = '".$arg['couponcode']."'";
		}

			// FILTERS
			if($arg[filters][ID]){
				$q .= " and o.ID = '".trim($arg[filters][ID])."'"	;
			}
			if($arg[filters][azonosito]){
				$q .= " and o.azonosito = '".trim($arg[filters][azonosito])."'"	;
			}
			if($arg[filters][access]){
				$q .= " and o.accessKey = '".trim($arg[filters][access])."'"	;
			}
			if($arg[filters][fallapot]){
				$q .= " and o.allapot = '".trim($arg[filters][fallapot])."'"	;
			}
			if($arg[filters][fszallitas]){
				$q .= " and o.szallitasiModID = '".trim($arg[filters][fszallitas])."'"	;
			}
			if($arg[filters][ffizetes]){
				$q .= " and o.fizetesiModID = '".trim($arg[filters][ffizetes])."'"	;
			}



		if (isset($arg['order']))
		{
			$q .= " ORDER BY ".$arg['order'];
		}
		else
		{
			$q .= " ORDER BY oa.sorrend ASC, o.idopont DESC";
		}

		$arg[multi] = '1';
		extract($this->db->q($q,$arg));

		//print_r($data);

		$back = array();
		$new 	= 0;
		$cont 	= 0;

		foreach($data as $d){
			$d['items'] 	= $this->getOrderItems($d[ID], $arg);
			$d['payu_ipn'] 	= $this->getPayUIPN( $d['azonosito'] );
			$d['coupon'] 	= false;
			$d['referer'] 	= false;

			if ( $d['userID'] != '' ) {
				$d['user'] = $this->db->query(sprintf("SELECT f.nev, f.user_group, f.email FROM felhasznalok as f WHERE ID = %d", (int)$d['userID']))->fetch(\PDO::FETCH_ASSOC);
			} else {
				$d['user'] = false;
			}


			//$d[ppp_adat] 	= $this->getPickPackPontAdat($d[pickpackpont_uzlet_kod]);

			if($d[allapot] == self::ORDER_STATUS_KEY_DEFAULT){
				$new++;
			}
			if($d[allapot] != self::ORDER_STATUS_KEY_DONE){
				$cont++;
			}

			// Kupon adatok
			if( $d['coupon_code'] )
			{
				$coupon = new Coupon(array(
					'db' => $this->db
				));
				$coupon->get($d['coupon_code']);

				$d['coupon'] = $coupon;
			}

			// Ajánló partner
			if ($d['referer_code'])
			{
				$partner_ref = (new PartnerReferrer ( $d['referer_code'], array(
					'db' 		=> $this->db,
					'settings' 	=> $this->settings
				)))
				->load();

				$d['referer'] = $partner_ref;
			}

			$back[] = $d;
		}

		$stat[uj] 			= $new;
		$stat[folyamatban] 	= $cont;
		$ret[data] 			= $back;

		$ret[info][stat] = $stat;

		return $ret;
	}

	public function getPayUIPN( $azonosito )
	{
		if ( !$azonosito ) {
			return array();
		}

		$q = "SELECT statusz, idopont FROM gateway_payu_ipn WHERE megrendeles = '$azonosito' GROUP BY statusz ORDER BY idopont DESC";

		return $this->db->query($q)->fetchAll(\PDO::FETCH_ASSOC);
	}

	private function getPickPackPontAdat($ppp_kod){
		if($ppp_kod == '') return false;

		$q = "SELECT * FROM pickpackpont_boltlista WHERE ppp_uzlet_kod = $ppp_kod";

		return $this->db->query($q)->fetch(\PDO::FETCH_ASSOC);
	}

	private function getOrderListItems($orderID){
		if($orderID == '') return false;
		$q = "SELECT
			ok.*,
			CONCAT(m.neve,' ',t.nev) as nev,
			(ok.egysegAr * ok.me) as subAr,
			FULLIMG(t.profil_kep) as profil_kep,
			getTermekUrl(t.ID,'".DOMAIN."') as url,
			ok.egysegAr as ar,
			otp.nev as allapotNev,
			otp.szin as allapotSzin
		FROM order_termekek as ok
		LEFT OUTER JOIN shop_termekek as t ON t.ID = ok.termekID
		LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
		LEFT OUTER JOIN order_termek_allapot as otp ON ok.allapotID = otp.ID
		WHERE ok.orderKey = $orderID";

		$arg[multi] = '1';
		extract($this->db->q($q,$arg));

		return $data;
	}

	public function getOrderItems($orderID, $arg = array()){
		$back = array();

		$q = "SELECT
			ot.ID,
			ot.termekID,
			ot.me,
			ot.egysegAr,
			ot.egysegArKedvezmeny,
			ot.allapotID ,
			IF(t.cikkszam IS NULL,'n.a.',t.cikkszam) as cikkszam,
			t.nev as termekNev,
			t.meret,
			t.szin,
			t.szin_kod,
			t.raktar_articleid,
			t.raktar_variantid,
			t.profil_kep,
			(ot.me * ot.egysegAr) as subAr
		FROM order_termekek as ot
		LEFT OUTER JOIN shop_termekek as t ON t.ID = ot.termekID
		LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
		WHERE ot.orderKey = $orderID";

		extract($this->db->q($q,array('multi'=>'1')));

		$tetel = 0;
		$totalPrice = 0;
		$bdata = array();
		foreach($data as $d){
			$totalPrice += $d[subAr];
			$tetel 		+= $d[me];
			$bdata[] = $d;
		}

		$back[total] 	= $totalPrice;
		$back[tetel] 	= $tetel;
		$back[data] 	= $bdata;

		return $back;
	}

	public function getFizetesiModok(){
		$q = "SELECT * FROM shop_fizetesi_modok";

		extract($this->db->q($q,array('multi'=>'1')));
		$back = array();
		foreach($data as $d){
			$back[$d[ID]] = $d;
		}

		return $back;
	}
	public function getMegrendeltTermekAllapotok(){
		$q = "SELECT * FROM order_termek_allapot ORDER BY sorrend ASC";

		extract($this->db->q($q,array('multi'=>'1')));

		$back = array();
		foreach($data as $d){
			$back[$d[ID]] = $d;
		}

		return $back;
	}
	public function getMegrendelesAllapotok(){
		$q = "SELECT * FROM order_allapot ORDER BY sorrend ASC";

		extract($this->db->q($q,array('multi'=>'1')));

		$back = array();
		foreach($data as $d){
			$back[$d[ID]] = $d;
		}

		return $back;
	}
	public function getSzallitasiModok(){
		$q = "SELECT * FROM shop_szallitasi_mod";

		extract($this->db->q($q,array('multi'=>'1')));

		$back = array();
		foreach($data as $d){
			$back[$d[ID]] = $d;
		}

		return $back;
	}

	function addSlideShow($post){
		$group_in = 'Home';
		extract($post);

		$lathato 	= ($lathato == 'on') ? 1 : 0;
		$group_in 	= ( isset($groups) ) ? $groups : $group_in;

		$this->db->insert('slideshow',
			array_combine(
			array('url','sorrend','kep','lathato', 'focim', 'alcim', 'focim_link', 'focim_link_text', 'groups'),
			array($url,$sorrend,$img,$lathato,$focim, $alcim, $focim_link, $focim_link_text, $group_in)
			)
		);
	}

	function saveSlideShow($post){
		$group_in = 'Home';
		extract($post);

		$lathato 	= ($lathato == 'on') ? 1 : 0;
		$url 		= ($url == '') ? '' : $url;
		$group_in 	= ( isset($groups) ) ? $groups : $group_in;

		$this->db->update('slideshow',
		array(
			'url' 		=> $url,
			'groups' 	=> $group_in,
			'kep' 		=> $img,
			'alcim' 	=> $alcim,
			'focim' 	=> $focim,
			'focim_link'=> $focim_link,
			'focim_link_text' => $focim_link_text,
			'sorrend' 	=> $sorrend,
			'lathato' 	=> $lathato
		),
		"ID = $id"
		);
	}

	function delSlideShow($post){
		extract($post);
		$this->db->query("DELETE FROM slideshow WHERE ID = $id");
		unlink('../img/'.$img);
	}

	function logout(){
		unset($_SESSION[adm]);
		setcookie("__admin","",time()-3600,"/");
		header('Location: /'); exit;
	}

	function addParameterToTermek($termekID, $kategoriaID, $parameterID, $ertek){
		$check = $this->checkParameterOnTermek($termekID, $kategoriaID, $parameterID);

		if(!$check){
			$this->db->insert('shop_termek_parameter',
				array_combine(
				array('termekID','katID','parameterID','ertek'),
				array($termekID,$kategoriaID, $parameterID,$ertek)
				)
			);
		}
	}
	function delAllTermekImage($termekID){
		$q = "SELECT * FROM shop_termek_kepek WHERE termekID = $termekID";
		extract($this->db->q($q,array('multi'=>'1')));
		foreach($data as $d){
			$this->db->query("DELETE FROM shop_termek_kepek WHERE ID = ".$d[ID]."");
		}
	}
	function delTermekImage($termekID, $image){

		$adat 	= $this->getTermekAdat($termekID);


		// Full
		//$img 	= str_replace(IMG,'../img/',$image);
		//unlink($img);
		// THMB75
		//$img_75 	= str_replace(IMG,'../img/',substr(Images::getThumbImg('75',$image),1));
		//unlink($img_75);
		// THMB150
		//$img_150 	= str_replace(IMG,'../img/',substr(Images::getThumbImg('150',$image),1));
		//unlink($img_150);

		$this->db->query("DELETE FROM shop_termek_kepek WHERE termekID = $termekID and kep = '".str_replace(IMG,'',$image)."'");

		if($image == $adat[profil_kep]){
			$this->db->query("UPDATE shop_termekek SET profil_kep = null WHERE ID = $termekID");
			$adat 	= $this->getTermekAdat($termekID);
			$imgs 	= $adat[images];

			if(count($imgs) > 0){
				$i = str_replace(IMG,'',$imgs[0]);
				$this->db->query("UPDATE shop_termekek SET profil_kep = '{$i}' WHERE ID = $termekID");
			}
		}
	}

	function getSzallitasModLista(){
		extract($this->db->q("SELECT * FROM shop_szallitasi_mod ORDER BY nev ASC",array('multi' => '1')));

		return $data;
	}

	function getKeszletLista(){
		extract($this->db->q("SELECT * FROM shop_termek_allapotok ORDER BY elnevezes ASC",array('multi' => '1')));

		return $data;
	}
	function getSzallitasIdoLista(){
		extract($this->db->q("SELECT * FROM shop_szallitasi_ido ORDER BY elnevezes ASC",array('multi' => '1')));

		return $data;
	}
	function getTermekek($arg = array()){
		$akcios_plus_szaz = AKCIOS_BRUTTO_AR_PLUSZ_SZAZALEK;
		$apsz = $akcios_plus_szaz / 100 + 1;

		$arg[multi] = '1';
		$q = "SELECT ";
		// select
			$q .= "t.*";
			$q .= ",FULLIMG(profil_kep) as profil_kep";
			$q .= ",ta.elnevezes as keszlet";
			$q .= ",sz.elnevezes as szallitas";
			$q .= ",(SELECT count(ID) FROM shop_termek_in_kategoria WHERE termekID = t.ID) as inKategoriaNum";
		// FROM
		$q .= " FROM shop_termekek as t ";
		$q .= " LEFT OUTER JOIN shop_termek_allapotok as ta ON ta.ID = t.keszletID";
		$q .= " LEFT OUTER JOIN shop_szallitasi_ido as sz ON sz.ID = t.szallitasID";
		// WHERE
		$q .= " WHERE t.id IS NOT NULL";
			if(count($arg[filters]) > 0){
				foreach($arg[filters] as $key => $v){
					switch($key)
					{
						case 'ID':
							$q .= " and t.".$key." LIKE '".$v."%' ";
						break;
						case 'nev':
							$q .= " and ".$key." LIKE '%".$v."%' ";
						break;
						default:
							$q .= " and ".$key." = '".$v."' ";
						break;
					}

				}
			}
		// ORDER
		$q .= " ORDER BY t.letrehozva DESC";
		// LIMIT
		extract($this->db->q($q,$arg));

		$bdata = array();

		foreach($data as $d){
			$brutto_ar 			= $d[brutto_ar];
			$akcios_brutto_ar 	= $d[akcios_brutto_ar];

			$arInfo 		= $this->getTermekArInfo($d[marka], $brutto_ar);
			$akcios_arInfo 	= $this->getTermekArInfo($d[marka], $akcios_brutto_ar);

			if($d[akcios] == '1'){
				if($d[akcios_egyedi_brutto_ar] != 0){
					$arInfo[ar] = $d[akcios_egyedi_brutto_ar];
				}else{
					$arInfo[ar] = $arInfo[ar] * $apsz;
				}
			}

			$katlist 	= $this->getKategoriakWhereTermekIn($d[ID]);
			$params 	= $this->getTermekParameter($d[ID], $d[termek_kategoria]);

			$arInfo[ar] 		= round($arInfo[ar] / 5) * 5;
			$akcios_arInfo[ar] 	= round($akcios_arInfo[ar] / 5) * 5;

			$d[ar] 				= $arInfo[ar];
			$d[akcios_fogy_ar]	= $akcios_arInfo[ar];
			$d[arres_szazalek] 	= $arInfo[arres];
			$d[inKatList] 		= $katlist;
			$d[params] 			= $params;
			$bdata[] 			= $d;
		}

		$ret[data] = $bdata;

		return $ret;
	}

	private function getTermekAr($markaID, $bruttoAr){
		$ari =  $this->getTermekArInfo($markaID, $bruttoAr);
		return $ari[ar];
	}

	private function getTermekArInfo($markaID, $bruttoAr){
		$re 	  = array();
		$re[info] =  array();
		$re[arres] = 0;
		$re[ar]   =  $bruttoAr;

		// Márka adatok
		$marka = $this->db->query("SELECT fix_arres FROM shop_markak WHERE ID = $markaID")->fetch(\PDO::FETCH_ASSOC);

		if(!is_null($marka[fix_arres])){
		// Fix árrés
			$re[info] 	= 'FIX : '.$marka[fix_arres].'%';
			$re[arres] 	= $marka[fix_arres];
			$re[ar] 	= round($bruttoAr * ($marka[fix_arres]/100+1));
		}else{
		// Sávos árrés
			$savok = $this->db->query("SELECT ar_min, ar_max, arres FROM shop_marka_arres_savok WHERE markaID = $markaID ORDER BY ar_min ASC")->fetchAll(\PDO::FETCH_ASSOC);
			foreach($savok as $s){
				$min = $s[ar_min];
				$max = $s[ar_max];
				$max = (is_null($max)) ? 999999999999999 : $max;

				if($bruttoAr >= $min && $bruttoAr <= $max){
					$re[info] 	= $min.' - '.$max.' : '.$s[arres].'%';
					$re[arres] 	= $s[arres];
					$re[ar] 	= round($bruttoAr * ($s[arres]/100+1));
					break;
				}else{
					$re[info] 	= $min.' - '.$max.' : '.$s[arres].'%';
					$re[arres] 	= $s[arres];
					$re[ar] 	= round($bruttoAr * ($s[arres]/100+1));
				}

			}
		}

		return $re;
	}


	function delPuttedTermekInListingKategoria( $termekID, $modszerID, $gyujtoID = null ){
		$dq = "DELETE FROM shop_termek_in_kategoria WHERE termekID = $termekID and modszerID = $modszerID";
		if(!is_null($gyujtoID)){
			$dq .= " and gyujtoID = $gyujtoID";
		}
		$this->db->query($dq);
	}


	function putTermekInListingKategoria($termekID, $modszerID, $gyujtoID, $arg = array()){
		 $gyujtoID 	= ($gyujtoID == '' || $gyujtoID == '0') ? null : $gyujtoID;
		 $remove 	= ($arg[autoRemove]) ? true : false;

		 $c = $this->checkTermekInListingKategoria($termekID, $modszerID, $gyujtoID);

		 if(!$c){
			 $this->db->insert('shop_termek_in_kategoria',
			 	array_combine(
			 	array('termekID','modszerID','gyujtoID'),
				array($termekID, $modszerID, $gyujtoID)
				)
			 );
		 }else{
			// Remove IF exits
			if($remove){
				$dq = "DELETE FROM shop_termek_in_kategoria WHERE termekID = $termekID and modszerID = $modszerID";
				if(!is_null($gyujtoID)){
					$dq .= " and gyujtoID = $gyujtoID";
				}
				$this->db->query($dq);
			}
		 }
	}

	private function checkTermekInListingKategoria($termekID, $modszerID, $gyujtoID){
		$re = false;

		if($gyujtoID == ''){
			$c = $this->db->query("SELECT id FROM shop_termek_in_kategoria WHERE termekID = $termekID and modszerID = $modszerID;");
		}else{
			$c = $this->db->query("SELECT id FROM shop_termek_in_kategoria WHERE termekID = $termekID and modszerID = $modszerID and gyujtoID = $gyujtoID;");
		}
		if($c->rowCount() > 0){
			$re = true;
		}

		return $re;
	}

	private function checkParameterOnTermek($termekID, $katID, $parameterID){
		$re = false;

		$c = $this->db->query("SELECT id FROM shop_termek_parameter WHERE termekID = $termekID and katID = $katID and parameterID = $parameterID;");

		if($c->rowCount() > 0){
			$re = true;
		}

		return $re;
	}
	/**

		TODO:
		- DEPRECATED

	**/
	function addTermek($post){
		/*
		extract($post);
		$dir = '';
		$uploadedProductId = 0;

		if($nev == '') throw new \Exception('Termék nevének megadása kötelező!');
		if($marka == '') throw new \Exception('Márka kiválasztása kötelező!');
		if($szallitasID == '') throw new \Exception('Szállítási időt kötelező kiválasztani!');
		if($keszletID == '') throw new \Exception('Állapotot kötelező kiválasztani!');
		if($termek_kategoria == '') throw new \Exception('Termék kategória kiválasztása kötelező!');
		if($ar == '0' || $ar == '') throw new \Exception('Termék árát kötelező megadni!');


		// Termék feltöltése
		if(1 == 1){
			$pickpackszallitas 	= ($pickpackszallitas == '') ? 0 : 1;
			$szuper_akcios 		= ($szuper_akcios == '') ? 0 : 1;
			$akcios 		= ($akcios == '') ? 0 : 1;
			$akcios_n_ar 	= ($akcios == 1) ? $akcios_netto_ar : 0;
			$akcios_b_ar 	= ($akcios == 1) ? $akcios_brutto_ar : 0;
			$argep 	  		= ($argep == '') ? 0 : 1;
			$arukereso 		= ($arukereso == '') ? 0 : 1;
			$ujdonsag 		= ($ujdonsag == '') ? 0 : 1;

			$nev 	= addslashes($nev);
			$leiras = addslashes($leiras);
			$garancia = ($garancia == '') ? null : $garancia;
			$nagyker_kod = ($nagyker_kod == '') ? null : $nagyker_kod;
			$linkek = '';

			if(count($post[linkNev]) > 0){
				$step = 0;
				foreach($post[linkNev] as $l){
					$url = $post[linkUrl][$step];
					if($l != '' && $url != ''){
						$linkek .= trim($l)."==>".trim($url)."||";
					}
					$step++;
				}
			}

			$this->db->insert('shop_termekek',
				array('marka','nev','leiras','termek_kategoria','netto_ar','brutto_ar','lathato','pickpackszallitas','szuper_akcios','akcios','akcios_netto_ar','akcios_brutto_ar','szallitasID','keszletID','ujdonsag','argep','arukereso','garancia_honap','linkek', 'nagyker_kod'),
				array($marka,$nev,$leiras,$termek_kategoria,$netto_ar,$brutto_ar,'0',$pickpackszallitas,$szuper_akcios,$akcios,$akcios_n_ar,$akcios_b_ar,$szallitasID,$keszletID,$ujdonsag,$argep,$arukereso,$garancia,$linkek, $nagyker_kod)
			);
			$uploadedProductId = $this->db->lastInsertId();
		}

		// Paraméterek bejegyzése
		if(isset($post[param])){
			foreach($post[param] as $pid => $pval){
				if($pval != '' && $pid != '')
				$this->addParameterToTermek($uploadedProductId, $termek_kategoria, $pid, $pval);
			}
		}

		// Kategóriákba sorolás
		if(isset($post[modszer])){
			foreach($post[modszer] as $mi => $mid){
				if( $mid != '' ){
					$gyujtoID = $post[gyujtokat][$mi];
					$this->putTermekInListingKategoria( $uploadedProductId, $mid, $gyujtoID );
				}
			}
		}

		// Kép feltöltés
		if($_FILES[img][name][0] != ''){
			$dir 	= 'p'.$uploadedProductId;
			$idir 	= '../img/products'.$dir;
			if(!file_exists($idir)){
				mkdir($idir,0777,true);
			}
			$mt = explode(" ",str_replace(".","",microtime()));
			$imgName = \Helper::makeSafeUrl($this->getMarkaNev($marka).'-'.$nev.'__'.date('YmdHis').$mt[0]);
			$img = Images::upload(array(
				'src' => 'img',
				'upDir' => $idir,
				'noRoot' => true,
				'fileName' => $imgName,
				'maxFileSize' => 1024
			));

			$upDir 		= str_replace(array('../img/'),array(''),$img[dir]);
			$upProfil 	= str_replace(array('../img/'),array(''),$img[file]);
			$this->db->update('shop_termekek',
				array(
					'kep_mappa' => $upDir,
					'profil_kep' => $upProfil
				),
				"ID = $uploadedProductId"
			);

			foreach($img[allUploadedFiles] as $kep){
				$this->pushTermekImg($uploadedProductId,$kep);
			}
		}

		if($pickpackszallitas == 1)
			setcookie("cr_pickpackszallitas","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_pickpackszallitas",false,time()-3600,"/termekek");


		if($akcios == 1)
			setcookie("cr_akcios","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_akcios",false,time()-3600,"/termekek");

		if($szuper_akcios == 1)
			setcookie("cr_szuper_akcios","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_szuper_akcios",false,time()-3600,"/termekek");

		if($ujdonsag == 1)
			setcookie("cr_ujdonsag","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_ujdonsag",false,time()-3600,"/termekek");

		if($argep == 1)
			setcookie("cr_argep","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_argep",false,time()-3600,"/termekek");

		if($arukereso == 1)
			setcookie("cr_arukereso","on",time()+3600*30,"/termekek");
		else
			setcookie("cr_arukereso",false,time()-3600,"/termekek");

		if($szallitasID)
			setcookie("cr_szallitasID",$szallitasID,time()+3600*30,"/termekek");
		else
			setcookie("cr_szallitasID",false,time()-3600,"/termekek");

		if($keszletID)
			setcookie("cr_keszletID",$keszletID,time()+3600*30,"/termekek");
		else
			setcookie("cr_keszletID",false,time()-3600,"/termekek");

		return 'Termék sikeresen létrehozva! ';
		*/
	}

	function getMarkaNev($id){
		$nev = '';
		if($id == '') return $nev;

		$q = $this->db->query("SELECT neve FROM shop_markak WHERE ID = $id")->fetch(\PDO::FETCH_COLUMN);
		$nev = $q;
		return $nev;
	}

	function copyTermek($termekID = 0, $copyNum = 0){
		if($termekID == 0) throw new \Exception('Termék azonosító hiányzik vagy hibás!');
		if($copyNum == 0) throw new \Exception('Legalább 1 vagy több számban tudjuk lemásolni a terméket.');

		for($c = 1; $c <= $copyNum; $c++){
			$newID = 0;
			//////////////////////////////////////////////////////////////////////////////////////
			// Termék másolás
				// Create temp table
				$this->db->query("CREATE TEMPORARY TABLE copyTermek ENGINE=MYISAM SELECT * FROM shop_termekek WHERE ID = $termekID;");
				// Update
				$this->db->query("UPDATE copyTermek SET ID = null, letrehozva = now(), lathato = 0, cikkszam = NULL, fotermek = 0;");
				// Insert
				$this->db->query("INSERT INTO shop_termekek SELECT * FROM copyTermek;");
					$newID = $this->db->lastInsertId();
				// Drop temp table
				$this->db->query("DROP TABLE copyTermek;");
			//////////////////////////////////////////////////////////////////////////////////////
			// Képek másolás
				$this->db->query("CREATE TEMPORARY TABLE copyTermekKepek ENGINE=MYISAM SELECT * FROM shop_termek_kepek WHERE termekID = $termekID;");
				// Update
				$this->db->query("UPDATE copyTermekKepek SET ID = null, termekID = $newID;");
				// Insert
				$this->db->query("INSERT INTO shop_termek_kepek SELECT * FROM copyTermekKepek;");
				// Drop temp table
				$this->db->query("DROP TABLE copyTermekKepek;");
			//////////////////////////////////////////////////////////////////////////////////////
			// Paraméterek másolása
				$this->db->query("CREATE TEMPORARY TABLE copyTermekParam ENGINE=MYISAM SELECT * FROM shop_termek_parameter WHERE termekID = $termekID;");
				// Update
				$this->db->query("UPDATE copyTermekParam SET ID = null, termekID = $newID;");
				// Insert
				$this->db->query("INSERT INTO shop_termek_parameter SELECT * FROM copyTermekParam;");
				// Drop temp table
				$this->db->query("DROP TABLE copyTermekParam;");
			//////////////////////////////////////////////////////////////////////////////
			// Kategóriák másolása
				$this->db->query("CREATE TEMPORARY TABLE copyTermekKat ENGINE=MYISAM SELECT * FROM shop_termek_in_kategoria WHERE termekID = $termekID;");
				// Update
				$this->db->query("UPDATE copyTermekKat SET ID = null, termekID = $newID;");
				// Insert
				$this->db->query("INSERT INTO shop_termek_in_kategoria SELECT * FROM copyTermekKat;");
				// Drop temp table
				$this->db->query("DROP TABLE copyTermekKat;");

		}

		return "Termék $copyNum db számban lemásolva!";
	}

	function login($post){
		extract($post);

		if($user == '') throw new \Exception('Bejelentkezési azonosító megadása kötelező!');
		if($pw == '') throw new \Exception('Bejelentkezési jelszó megadása kötelező!');

		$pw = \Hash::jelszo($pw);

		$iq = "SELECT engedelyezve FROM admin WHERE user = '$user' and pw = '$pw'";

		$q = $this->db->query($iq);

		if($q->rowCount() > 0){
			if($q->fetch(\PDO::FETCH_COLUMN) == 0) {
				throw new \Exception('A fiók korlátozásra került.');
			}
			switch(self::LOGINHANDLE_MODE){
				default: case 'session':
					\Session::set('adm',$user);
				break;
				case 'cookie':
					setcookie("__admin",$this->setCookieToken($user),time()+60*60*24,"/");
				break;
			}

			$this->db->query(sprintf("UPDATE admin SET utoljara_belepett = now() WHERE user = '%s'", $user));


		}else{
			throw new \Exception('Nincs ilyen adminisztrátor. Próbáld újra!');
		}
	}

	function addModszer($post){
		extract($post);

		if($newmodszer == '') throw new \Exception('Módszer elnevezésének megadása kötelező!');

		$check = $this->db->query("SELECT id FROM shop_modszerek WHERE neve = '$newmodszer'");

		if($check->rowCount() != 0) throw new \Exception('Ez a módszer már létezik!');

		$this->db->insert('shop_modszerek',
			array_combine(
			array('neve'),
			array($newmodszer)
			)
		);
	}

	function editModszer($post){
		extract($post);

		if($newmodszer == '') throw new \Exception('Módszer elnevezésének megadása kötelező!');

		$newmodszer_sorr = ($newmodszer_sorr == '') ? 0 : $newmodszer_sorr;

		$this->db->query("UPDATE shop_modszerek SET
			neve = '$newmodszer'
		WHERE ID = $editId
		");

	}

	function delModszer($post){
		extract($post);
		$this->db->query("DELETE FROM shop_modszerek WHERE ID = $editId");
		$this->db->query("DELETE FROM shop_gyujto_kategoriak WHERE modszerID = $editId");
	}

	function addGyujtoKategoria($post){
		extract($post);

		if($katId == '') throw new \Exception('Módszer kiválasztása kötelező!');
		if($newgyujtokat == '') throw new \Exception('Gyűjtő kategória elnevezése kötelező!');

		$check = $this->db->query("SELECT id FROM shop_gyujto_kategoriak WHERE neve = '$newgyujtokat' and modszerID = '$katId'");

		if($check->rowCount() != 0) throw new \Exception('Ez a gyűjtő kategória már létezik!');

		$this->db->insert('shop_gyujto_kategoriak',
			array_combine(
			array('modszerID','neve'),
			array($katId, $newgyujtokat)
			)
		);
	}

	function editGyujtoKategoria($post){
		extract($post);

		if($newgyujtokat == '') throw new \Exception('Gyűjtő kategória elnevezése kötelező!');

		$this->db->query("UPDATE shop_gyujto_kategoriak SET
			neve = '$newgyujtokat'
		WHERE ID = $editId
		");

	}

	function delGyujtoKategoria($post){
		extract($post);
		$this->db->query("DELETE FROM shop_gyujto_kategoriak WHERE ID = $editId");
	}

	function addTermekKategoria($post){
		extract($post);

		if($newtermkat == '') throw new \Exception('Termék kategória elnevezésének megadása kötelező!');

		$check = $this->db->query("SELECT id FROM shop_termek_kategoriak WHERE neve = '$newtermkat'");

		if($check->rowCount() != 0) throw new \Exception('Ez a termék kategória már létezik!');

		$this->db->insert('shop_termek_kategoriak',
			array_combine(
			array('neve'),
			array($newtermkat)
			)
		);
	}

	function addMarka($post){
		extract($post);
		$mnev = addslashes($mnev);

		if($mnev == '') throw new \Exception('Márka elnevezésének megadása kötelező!');
		if($markepz == '') throw new \Exception('Árképzés módjának kiválasztása kötelező!');
		if($markepz == '0' && $marres == 0) throw new \Exception('Fix árrés esetén kötelező meghatározni az árrés százalékát (%)!');
		if($this->checkMarkaLetezes($mnev)) throw new \Exception('Ez a márka már létre lett hozva!');

		$mod 		= $markepz;
		$fixarres 	= ($markepz == '0') ? $marres : null;
		$elorend 	= ($elorendelheto == 'on') ? 1 : 0;

		$this->db->insert('shop_markak',
			array_combine(
			array('neve','arres_mod','fix_arres','brutto','elorendelheto','nagyker_id'),
			array($mnev, $mod, $fixarres, $nb, $elorend, $nagyker_id)
			)
		);

		// Árrés sávok
		if($mod == '1'){
			$markaId = $this->db->lastInsertId();

			foreach($post[sav_start] as $i => $d):
				$ar_min = 0;
				$ar_max = null;
				$arres 	= null;

				$ar_min = (is_numeric($d) && $d >= 0) ? $d : $ar_min;
				$ar_max = ($post[sav_end][$i] != 0) ? $post[sav_end][$i] : null;
				$arres 	= (is_numeric($post[sav_arres][$i]) && $post[sav_arres][$i] > 0) ? $post[sav_arres][$i] : $arres;


				$this->db->insert('shop_marka_arres_savok',
					array_combine(
					array('markaID','ar_min','ar_max','arres'),
					array($markaId,$ar_min,$ar_max,$arres)
					)
				);
			endforeach;
		}
	}

	function checkMarkaLetezes($marka){
		$c = $this->db->query("SELECT id FROM shop_markak WHERE neve = '$marka'");

		if($c->rowCount() == 0){
			return false;
		}else return true;
	}

	function addParameterOnTermekKategoria($post){
		extract($post);

		foreach($post[paramNev] as $i => $d){
			$param 		= $d;
			$me 		= $post[paramMe][$i];
			$id 		= $post[paramId][$i];
			$kulcs		= $post[paramKulcs][$id];
			$range		= $post[paramRange][$id];
			$priority	= $post[paramPriority][$id];

			if($param != ''){
				if($id == '0'){
					if(!$this->checkParameterOnTermekKategoriaLetezik($termkatID,$param)){
						$kulcs = 0;
						$range = ($range == 'on') ? 1 : 0;
						$this->db->insert("shop_termek_kategoria_parameter",
							array_combine(
							array('kategoriaID','parameter','mertekegyseg'),
							array($termkatID, $param, $me)
							)
						);
					}
				}else{
					$kulcs = ($kulcs == '1') ? 1 : 0;
					$range = ($range == '1') ? 1 : 0;
					$this->db->update("shop_termek_kategoria_parameter",
					array(
						'parameter' => $param,
						'mertekegyseg' => $me,
						'kulcs' => $kulcs,
						'is_range' => $range,
						'priority' => $priority
					),
					"ID = $id");
				}
			}
		}
	}

	function checkParameterOnTermekKategoriaLetezik($katid, $param){
		if($katid == '' || $param == '') return false;

		$c = $this->db->query("SELECT id FROM shop_termek_kategoria_parameter WHERE kategoriaID = $katid and parameter = '$param';");

		if($c->rowCount() == 0) return false; else return true;
	}

	function getTermekParameter($termekID, $katID = false){
		$q = "SELECT
			p.* ,
			pm.parameter as neve,
			pm.mertekegyseg as me
		FROM shop_termek_parameter as p
		LEFT OUTER JOIN shop_termek_kategoria_parameter as pm ON pm.ID = p.parameterID
		 WHERE p.termekID = $termekID ";
		if($katID){
			$q .= " and katID = $katID ";
		}
		$q .= "
		 ORDER BY pm.parameter ASC";
		extract($this->db->q($q,array('multi'=> '1')));
		$back = array();
		foreach($data as $d){
			$back[$d[parameterID]] = $d;
		}
		return $back;
	}

	function getParameterOnTermekKategoria($katid){
		if( ! $katid ) return false;
		extract($this->db->q("SELECT * FROM shop_termek_kategoria_parameter WHERE kategoriaID = $katid ORDER BY CAST(priority as unsigned) ASC",array('multi' => '1')));
		return $data;
	}

	function editTermekKategoria($post){
		extract($post);

		if($newtermkat == '') throw new \Exception('Termék kategória elnevezésének megadása kötelező!');

		$this->db->query("UPDATE shop_termek_kategoriak SET
			neve = '$newtermkat'
		WHERE ID = $editId
		");

	}
	function delTermekKategoria($post){
		extract($post);
		$this->db->query("DELETE FROM shop_termek_kategoriak WHERE ID = $editId");
	}

	function getTermekAdat($termekID){
		$q = "SELECT
			*,
			t.ID as termek_ID,
			getTermekAr(t.marka,t.brutto_ar) as ar,
			getTermekAr(t.marka,t.akcios_brutto_ar) as akcios_ar,
			FULLIMG(t.profil_kep) as profil_kep,
			CONCAT(m.neve,' ', t.nev) as full_name
		FROM shop_termekek as t
		LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
		WHERE t.ID = $termekID";

		extract($this->db->q($q));
		$imgs = array();
		$imgs = $this->getAllTermekImg($termekID);

		$data[images] 	= $imgs;

		return $data;
	}
	function getAllTermekImg($termekID){
		$imgs = array();
		if($termekID == '') return $imgs;
		$q = "SELECT FULLIMG(kep) as kep FROM shop_termek_kepek WHERE termekID = $termekID ORDER BY sorrend ASC, kep ASC";
		extract($this->db->q($q,array('multi'=> '1')));

		foreach($data as $i){
			$imgs[] = $i[kep];
		}

		return $imgs;
	}
	function pushTermekImg($termekID, $kep, $sorrend = 0){
		$this->db->insert('shop_termek_kepek',
			array_combine(
			array('termekID','kep','sorrend'),
			array($termekID,str_replace('../img/','',$kep),$sorrend)
			)
		);
	}
	function getKategoriakWhereTermekIn($termekID){
		extract($this->db->q("SELECT
			tik.*,
			m.neve as modszer,
			gy.neve as gyujto
		FROM shop_termek_in_kategoria as tik
		LEFT OUTER JOIN shop_modszerek as m ON m.ID = tik.modszerID
		LEFT OUTER JOIN shop_gyujto_kategoriak as gy ON gy.ID = tik.gyujtoID
		WHERE tik.termekID = $termekID ORDER BY m.neve ASC",array('multi' => '1')));

		return $data;
	}

	function delTermek($termekID){
		$termek = $this->getTermekAdat($termekID);
			$kepMappa = '../img/'.$termek[kep_mappa];

		// Delete images
		$this->delAllTermekImage($termekID);
		// Delete from kategória
		$this->db->query("DELETE FROM shop_termek_in_kategoria WHERE termekID = $termekID");
		// Delete pareméterek
		$this->db->query("DELETE FROM shop_termek_parameter WHERE termekID = $termekID");
		// Termék törlése
		$this->db->query("DELETE FROM shop_termekek WHERE ID = $termekID");
	}

	function getModszerek(){
		extract($this->db->q("SELECT * FROM shop_modszerek ORDER BY neve ASC",array('multi' => '1')));

		return $data;
	}

	function getGyujtoKategoriak(){
		extract($this->db->q("SELECT k.*,m.neve as modszerNev FROM shop_gyujto_kategoriak as k LEFT OUTER JOIN shop_modszerek as m ON m.ID = k.modszerID  ORDER BY m.neve ASC, m.neve ASC",array('multi' => '1')));

		\Helper::arry($data, 'neve','ASC');

		$cucc = array();
		foreach($data as $d){
			$cucc[$d[modszerNev]][] = $d;
		}

		return $cucc;
	}
	function getGyujtoKategoriaSub($modszerId){
		extract($this->db->q("SELECT * FROM shop_gyujto_kategoriak WHERE modszerID = $modszerId ORDER BY neve ASC",array('multi' => '1')));
		return $data;
	}

	function listTermekKategoriaParameterek(){
		$back = array();
		$q = "SELECT
			kp.* ,
			k.neve as kategoria
		FROM shop_termek_kategoria_parameter as kp
		LEFT OUTER JOIN shop_termek_kategoriak as k ON k.id = kp.kategoriaID
		WHERE kp.id IS NOT NULL
		ORDER BY CAST(kp.priority as unsigned) ASC
		";

		extract($this->db->q($q, array('multi' => '1')));
		foreach($data as $d){
			$back[$d[kategoria]][] = $d;
		}
		return $back;
	}

	function getTermekKategoriak(){
		extract($this->db->q("SELECT * FROM shop_termek_kategoriak ORDER BY neve ASC",array('multi' => '1')));

		return $data;
	}

	function getMarkak(){
		extract($this->db->q("SELECT
			m.*,
			n.nagyker_nev
		FROM shop_markak as m
		LEFT OUTER JOIN nagyker as n ON n.ID = m.nagyker_id
		ORDER BY m.neve ASC",array('multi' => '1')));
		$cucc = array();

		foreach($data as $d){
			$savok = array();

			if($d[arres_mod] == '1'){
				$savok = $this->getMarkaArresek($d[ID]);
			}

			$d[arres_savok] = $savok;
			$cucc[] = $d;
		}

		return $cucc;
	}

	function addNagyker($post){
		extract($post);

		if($nev == '') throw new \Exception('A nagyker címkének elnevezése kötelező!');

		$check = $this->db->query("SELECT ID FROM nagyker WHERE nagyker_nev = '$nev'");

		if($check->rowCount() > 0){
			throw new \Exception('Ez a nagyker cimke már létezik!');
		}

		$this->db->insert(
			'nagyker',
			array_combine(
			array( 'nagyker_nev' ),
			array( $nev )
			)
		);
	}

	function getNagykerek(){
		extract($this->db->q("SELECT * FROM nagyker ORDER BY nagyker_nev ASC",array('multi' => '1')));

		return $data;
	}

	/**
	 * Az összes előrendelési kedvezmény listázása
	 * @return mixed A kedvezmények értékei
	 */
	function getElorendelesiKedvezmenyek()
	{
		extract($this->db->q("SELECT * FROM elorendelesi_kedvezmeny ORDER BY ar_from ASC",array('multi' => '1')));
		return $data;
	}

	function getKedvezmenyek(){
		extract($this->db->q("SELECT * FROM torzsvasarloi_kedvezmeny ORDER BY ar_from ASC",array('multi' => '1')));
		return $data;
	}

	function getKedvezmeny($kedvezmenyID){
		extract($this->db->q("SELECT * FROM torzsvasarloi_kedvezmeny WHERE ID = $kedvezmenyID"));

		return $data;
	}
	function getElorendelesiKedvezmeny($kedvezmenyID){
		extract($this->db->q("SELECT * FROM elorendelesi_kedvezmeny WHERE ID = $kedvezmenyID"));

		return $data;
	}

	function addKedvezmeny($post){
		extract($post);
		if($ar_from == '') throw new \Exception('A minimális értékhatárt meg kell adni.');
		if($ar_to == '') throw new \Exception('A maximális értékhatárt meg kell adni.');
		if($kedvezmeny == '' || $kedvezmeny <= 0) throw new \Exception('A kedvezmény mértékének nagyobbnak kell lennie, mint 0%.');

		$this->db->insert('torzsvasarloi_kedvezmeny',
			array_combine(
			array('ar_from','ar_to','kedvezmeny'),
			array($ar_from,$ar_to,$kedvezmeny)
			)
		);
	}
	function addElorendelesiKedvezmeny($post){
		extract($post);
		if($ar_from == '') throw new \Exception('A minimális értékhatárt meg kell adni.');
		if($ar_to == '') throw new \Exception('A maximális értékhatárt meg kell adni.');
		if($kedvezmeny == '' || $kedvezmeny <= 0) throw new \Exception('A kedvezmény mértékének nagyobbnak kell lennie, mint 0%.');

		$this->db->insert('elorendelesi_kedvezmeny',
			array_combine(
			array('ar_from','ar_to','kedvezmeny'),
			array($ar_from,$ar_to,$kedvezmeny)
			)
		);
	}


	function editKedvezmeny($post){
		extract($post);
		$this->db->update('torzsvasarloi_kedvezmeny',
			array(
				'ar_from' => $ar_from,
				'ar_to' => $ar_to,
				'kedvezmeny' => $kedvezmeny
			),
			"ID = $id"
		);
	}
	function editElorendelesiKedvezmeny($post){
		extract($post);
		$this->db->update('elorendelesi_kedvezmeny',
			array(
				'ar_from' => $ar_from,
				'ar_to' => $ar_to,
				'kedvezmeny' => $kedvezmeny
			),
			"ID = $id"
		);
	}

	function delKedvezmeny($kedvezmenyID){
		$this->db->query("DELETE FROM torzsvasarloi_kedvezmeny WHERE ID = $kedvezmenyID");
	}
	function delElorendelesiKedvezmeny($kedvezmenyID){
		$this->db->query("DELETE FROM elorendelesi_kedvezmeny WHERE ID = $kedvezmenyID");
	}

	function getMarka($markaID){
		extract($this->db->q("SELECT
			m.*,
			n.nagyker_nev
		FROM shop_markak as m
		LEFT OUTER JOIN nagyker as n ON n.ID = m.nagyker_id
		WHERE m.ID = $markaID"));

		return $data;
	}
	function editMarka($post){
		extract($post);

		$enev = addslashes($enev);

		if($enev == '') throw new \Exception('Márkanév megadása kötelező!');

		$elorend = ($elorendelheto == 'on') ? 1 : 0;

		$this->db->update('shop_markak',
			array(
				'neve' => $enev,
				'arres_mod' 		=> $earkepz,
				'fix_arres' 		=> $earres,
				'brutto' 			=> $enb,
				'elorendelheto' 	=> $elorend,
				'nagyker_id' 		=> $nagyker_id
			),
			"ID = $markaID"
		);

		if($earkepz == '1'){
			$this->db->query("UPDATE shop_markak SET fix_arres = null WHERE ID = $markaID");

			// Sávok
			foreach($post[esav_start] as $i => $s){
				$id 		= $post[esav_id][$i];
				$start 		= $s;
				$end 		= ($post[esav_end][$i]) ? $post[esav_end][$i] : 0;
				$arres 		= $post[esav_arres][$i];

				$end = ($end == 0) ? 'NULL' : $end;
				if($arres != 0){
					if($id == '0'){
						$this->db->insert('shop_marka_arres_savok',
							array_combine(
							array('markaID','ar_min','ar_max','arres'),
							array($markaID, $start, $end, $arres)
							)
						);
					}else{
						$uq = "UPDATE shop_marka_arres_savok SET
							ar_min = $start,
							ar_max = $end,
							arres = $arres
						WHERE ID = $id
						";
						//echo $uq;
						$this->db->query($uq);

					}
				}
			}
			// Törlés
			foreach($post[esav_del] as $del => $e){
				$this->db->query("DELETE FROM shop_marka_arres_savok WHERE ID = $del");
			}
		}else if($earkepz == '0'){
			$this->db->query("DELETE FROM shop_marka_arres_savok WHERE markaID = $markaID");
		}
	}
	function delMarka($markaID){
		$this->db->query("DELETE FROM shop_marka_arres_savok WHERE markaID = $markaID");
		$this->db->query("DELETE FROM shop_markak WHERE ID = $markaID");
	}
	function getMarkaArresek($markaID){
		extract($this->db->q("SELECT ID,ar_min,ar_max,arres FROM shop_marka_arres_savok WHERE markaID = $markaID ORDER BY ar_min ASC",array('multi' => '1')));

		return $data;
	}

	function getMenuList(){
		extract($this->db->q("SELECT * FROM menu ORDER BY sorrend ASC",array('multi' => '1')));

		return $data;
	}
	function getSzallitasIdoList(){
		extract($this->db->q("SELECT * FROM shop_szallitasi_ido ORDER BY elnevezes ASC",array('multi' => '1')));

		return $data;
	}
	function getPages(){
		extract($this->db->q("SELECT * FROM oldalak ORDER BY idopont DESC",array('multi' => '1')));

		return $data;
	}

	function addPage($post){
		extract($post);
		if($cim == '') throw new \Exception('Az oldal címének megadása kötelező!');
		if($eleres == '') throw new \Exception('Az oldal elérésének kulcsát megadni kötelező!');

		$check = $this->db->query("SELECT ID FROM oldalak WHERE eleres = '$eleres'");

		if($check->rowCount() > 0){
			throw new \Exception('A megadott elérési kulcs már használatban van: <strong>'.DOMAIN.'p/'.$eleres.'</strong>! Kérjük, hogy változtasson rajta!');
		}

		$lathato = ($lathato == 'on') ? 1 : 0;

		$this->db->insert('oldalak',
			array_combine(
			array('cim','eleres','lathato','szoveg'),
			array($cim,$eleres,$lathato,$szoveg)
			)
		);
	}
	function getOldalData($id){
		extract($this->db->q("SELECT * FROM oldalak WHERE ID = $id"));

		return $data;
	}
	function getMenuData($id){
		extract($this->db->q("SELECT * FROM menu WHERE ID = $id"));

		return $data;
	}
	function getSzallitasIdoData($id){
		extract($this->db->q("SELECT * FROM shop_szallitasi_ido WHERE ID = $id"));

		return $data;
	}
	function getSzallitasModData($id){
		extract($this->db->q("SELECT * FROM shop_szallitasi_mod WHERE ID = $id"));

		return $data;
	}
	function getFizetesiModData($id){
		extract($this->db->q("SELECT * FROM shop_fizetesi_modok WHERE ID = $id"));

		return $data;
	}
	function getTermekAllapotData($id){
		extract($this->db->q("SELECT * FROM shop_termek_allapotok WHERE ID = $id"));

		return $data;
	}
	function addMenu($post){
		extract($post);

		$this->db->insert('menu',
			array_combine(
			array('nev','url','sorrend','gyujto'),
			array($nev,$url,$sorrend,'top')
			)
		);
	}
	function addSzallitasIdo($post){
		extract($post);

		$this->db->insert('shop_szallitasi_ido',
			array_combine(
			array('elnevezes'),
			array($nev)
			)
		);
	}
	function addSzallitasMod($post){
		extract($post);

		$this->db->insert('shop_szallitasi_mod',
			array_combine(
			array('nev','koltseg','osszeghatar'),
			array($nev,$koltseg,$osszeghatar)
			)
		);
	}
	function addFizetesiMod($post){
		extract($post);

		$this->db->insert('shop_fizetesi_modok',
			array_combine(
			array('nev'),
			array($nev)
			)
		);
	}
	function addTermekAllapot($post){
		extract($post);

		$this->db->insert('shop_termek_allapotok',
			array_combine(
			array('elnevezes', 'color'),
			array($nev, $color)
			)
		);
	}
	function saveMenu($post){
		extract($post);
		$this->db->update('menu',
			array(
				'nev' => $nev,
				'url' => $url,
				'sorrend' => $sorrend
			),
			"ID = $id"
		);
	}
	function saveSzallitasIdo($post){
		extract($post);
		$this->db->update('shop_szallitasi_ido',
			array(
				'elnevezes' => $nev
			),
			"ID = $id"
		);
	}
	function saveSzallitasMod($post){
		extract($post);

		$fizmod = ( empty($fizmod) ) ? NULL : implode(",", $fizmod);

		$this->db->update('shop_szallitasi_mod',
			array(
				'nev' 			=> $nev,
				'koltseg' 		=> $koltseg,
				'osszeghatar' 	=> $osszeghatar,
				'fizetesi_mod' 	=> $fizmod
			),
			"ID = $id"
		);
	}
	function saveFizetesiMod($post){
		extract($post);
		$this->db->update('shop_fizetesi_modok',
			array(
				'nev' 			=> $nev
			),
			"ID = $id"
		);
	}
	function saveTermekAllapot($post){
		extract($post);
		$this->db->update('shop_termek_allapotok',
			array(
				'elnevezes' => $nev,
				'color' => $color
			),
			"ID = $id"
		);
	}
	function savePage($post){
		extract($post);
		$check = $this->db->query("SELECT ID FROM oldalak WHERE eleres = '$eleres' and ID != $id");

		if($check->rowCount() > 0){
			throw new \Exception('A megadott elérési kulcs már használatban van: <strong>'.DOMAIN.'p/'.$eleres.'</strong>! Kérjük, hogy változtasson rajta!');
		}

		$lathato 	= ($lathato == 'on') ? 1 : 0;
	//	$szoveg 	= addslashes($szoveg);

		$this->db->update('oldalak',
			array(
				'cim' => $cim,
				'eleres' => $eleres,
				'lathato' => $lathato,
				'szoveg' => $szoveg,
				'idopont' => NOW
			),
			"ID = $id"
		);
	}
	function addMegrendelesAllapot($post){
		extract($post);
		if($nev == '') throw new \Exception('Állapot lenevezése kötelező!');

		if(strpos($szin,'#') !== 0){
			$szin = "#".$szin;
		}
		$this->db->insert('order_allapot',
			array_combine(
			array('nev','sorrend','szin'),
			array($nev,$sorrend,$szin)
			)
		);
	}
	function addMegrendelesTermekAllapot($post){
		extract($post);
		if($nev == '') throw new \Exception('Állapot lenevezése kötelező!');

		if(strpos($szin,'#') !== 0){
			$szin = "#".$szin;
		}
		$this->db->insert('order_termek_allapot',
			array_combine(
			array('nev','sorrend','szin'),
			array($nev,$sorrend,$szin)
			)
		);
	}
	function saveMegrendelesAllapot($post){
		extract($post);
		if(strpos($szin,'#') !== 0){
			$szin = "#".$szin;
		}
		$this->db->update('order_allapot',
			array(
				'nev' => $nev,
				'sorrend' => $sorrend,
				'szin' => $szin
			),
			"ID = $id"
		);
	}
	function saveMegrendelesTermekAllapot($post){
		extract($post);
		if(strpos($szin,'#') !== 0){
			$szin = "#".$szin;
		}
		$this->db->update('order_termek_allapot',
			array(
				'nev' => $nev,
				'sorrend' => $sorrend,
				'szin' => $szin
			),
			"ID = $id"
		);
	}
	function delMegrendelesAllapot($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM order_allapot WHERE ID = $id");
	}
	function delMegrendelesTermekAllapot($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM order_termek_allapot WHERE ID = $id");
	}
	function delMenu($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM menu WHERE ID = $id");
	}
	function delSzallitasIdo($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM shop_szallitasi_ido WHERE ID = $id");
	}
	function delSzallitasMod($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM shop_szallitasi_mod WHERE ID = $id");
	}
	function delFizetesiMod($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM shop_fizetesi_modok WHERE ID = $id");
	}
	function delTermekAllapot($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM shop_termek_allapotok WHERE ID = $id");
	}
	function delPage($id){
		if($id == '') return false;
		$this->db->query("DELETE FROM oldalak WHERE ID = $id");
	}

	function saveOrderData($orderID, $post){
		if($orderID == '') return false;
		$accessKey 		= $post[accessKey][$orderID];
		$updateData		= array();
		$changedData 	= array();
		$strKey 		= array(
			'allapot' 				=> 'Megrendelés állapota',
			'szallitasi_koltseg' 	=> 'Szállítási költsége',
			'kedvezmeny' 			=> 'Kedvezmény mértéke',
			'termekAllapot' 		=> 'Termék állapot(ok)',
			'termekMe' 				=> 'Termék mennyiség(ek)',
			'termekAr' 				=> 'Termék ár(ak)',
			'fizetes' 				=> 'Fizetési mód',
			'szallitas' 			=> 'Átvételi mód',
			'szallitasi_adat' 		=> 'Szállítási adatok',
			'szamlazasi_adat' 		=> 'Számlázási adatok'
		);

		// Megrendelés állapot
		$allapot 		= $post[allapotID][$orderID];
		$allapot_pre 	= $post[prev_allapotID][$orderID];
		if($allapot != $allapot_pre){
			$updateData['allapot'] 	= $allapot;
			$changedData[allapot]	= 1;
		}

		// Szállítási költség
		$szallitasi_koltseg 		= $post[szallitasi_koltseg][$orderID];
		$szallitasi_koltseg_pre 	= $post[prev_szallitasi_koltseg][$orderID];
		if($szallitasi_koltseg != $szallitasi_koltseg_pre){
			$updateData['szallitasi_koltseg'] 	= $szallitasi_koltseg;
			$changedData[szallitasi_koltseg]	= 1;
		}
		// Kedvezmény
		$kedvezmeny 		= $post[kedvezmeny][$orderID];
		$kedvezmeny_pre 	= $post[prev_kedvezmeny][$orderID];
		if($kedvezmeny != $kedvezmeny_pre){
			$updateData['kedvezmeny'] 	= $kedvezmeny;
			$changedData[kedvezmeny]	= 1;
		}
		// Átvétel
		$szallitas 		= $post[szallitas][$orderID];
		$szallitas_pre 	= $post[prev_szallitas][$orderID];
		if($szallitas != $szallitas_pre){
			$updateData['szallitasiModID'] 	= $szallitas;
			$changedData[szallitas]			= 1;
		}
		// Fizetés
		$fizetes 		= $post[fizetes][$orderID];
		$fizetes_pre 	= $post[prev_fizetes][$orderID];
		if($fizetes != $fizetes_pre){
			$updateData['fizetesiModID'] 	= $fizetes;
			$changedData[fizetes]			= 1;
		}
		// Megrendelés megváltoztatása
		$this->db->update('orders',$updateData,"ID = $orderID");

		// Termékek állapota

		$termek_allapotok 		= $post[termekAllapot][$orderID];
		$termek_allapotok_pre 	= $post[prev_termekAllapot][$orderID];
		$termekAllapotChange 	= array();
		$termek_will_delete 	= array();

		if( count($termek_allapotok) > 0 )
		foreach($termek_allapotok  as $tid => $tv){
			$pre = $termek_allapotok_pre[$tid];
			if($tv != $pre){
				if(!$changedData[termekAllapot]){
					$changedData[termekAllapot] = 1;
				}else{
					$changedData[termekAllapot] += 1;
				}

				if( $tv == '7') {
					// Törlés
					$termek_will_delete[] = $tid;
				} else {
					// Frissítés
					$termekAllapotChange[] = array(
						'id' => $tid,
						'val' => $tv
					);
				}
			}
		}

		// Termék állapotok mentése
		foreach($termekAllapotChange as $tac){
			$this->db->update('order_termekek',
				array(
					'allapotID' => $tac[val]
				),
				"ID = ".$tac[id]
			);
		}

		// Termékek törlése, amelyek törölve állapotra lett helyezve
		foreach($termek_will_delete as $dtid){
			$this->db->query("DELETE FROM order_termekek WHERE ID = $dtid");
		}

		// Termék mennyiségek
		$termek_me 		= $post[termekMe][$orderID];
		$termek_me_pre 	= $post[prev_termekMe][$orderID];
		$termekMeChange	= array();
		if(count( $termek_me ) > 0)
		foreach($termek_me  as $tid => $tv){
			$pre = $termek_me_pre[$tid];
			if($tv != $pre){
				if(!$changedData[termekMe]){
					$changedData[termekMe] = 1;
				}else{
					$changedData[termekMe] += 1;
				}
				$termekMeChange[] = array(
					'id' => $tid,
					'val' => $tv
				);
			}
		}
		if(count( $termekMeChange ) > 0)
		foreach($termekMeChange as $tac){
			$changedData[termekMe]	= 1;
			$this->db->update('order_termekek',
				array(
					'me' => $tac[val]
				),
				"ID = ".$tac[id]
			);
		}
		// Termék árak
		$termek_ar 		= $post[termekAr][$orderID];
		$termek_ar_pre 	= $post[prev_termekAr][$orderID];
		$termekArChange	= array();
		if(count( $termek_ar ) > 0)
		foreach($termek_ar  as $tid => $tv){
			$pre = $termek_ar_pre[$tid];
			if($tv != $pre){
				if(!$changedData[termekAr]){
					$changedData[termekAr] = 1;
				}else{
					$changedData[termekAr] += 1;
				}
				$termekArChange[] = array(
					'id' => $tid,
					'val' => $tv
				);
			}
		}
		if(count( $termekArChange ) > 0)
		foreach($termekArChange as $tac){
			$changedData[termekAr]	= 1;
			$this->db->update('order_termekek',
				array(
					'egysegAr' => $tac[val]
				),
				"ID = ".$tac[id]
			);
		}

		// Számlázási adatok
		$szamlazasi_adat 		= $post[szamlazasi_adat][$orderID];
		$szamlazasi_adat_pre 	= $post[prev_szamlazasi_adat][$orderID];
		if(count( $szamlazasi_adat ) > 0)
		foreach($szamlazasi_adat  as $tid => $tv){
			$pre = $szamlazasi_adat_pre[$tid];
			if($tv != $pre){
				if(!$changedData[szamlazasi_adat]){
					$changedData[szamlazasi_adat] = 1;
				}else{
					$changedData[szamlazasi_adat] += 1;
				}
			}
		}
		$szamlazasi_keys = json_encode($szamlazasi_adat,JSON_UNESCAPED_UNICODE);
		$this->db->update('orders',
			array(
				'szamlazasi_keys' => $szamlazasi_keys
			),
			"ID = ".$orderID
		);

		// Szállítási adatok
		$szallitasi_adat 		= $post[szallitasi_adat][$orderID];
		$szallitasi_adat_pre 	= $post[prev_szallitasi_adat][$orderID];
		if(count( $szallitasi_adats ) > 0)
		foreach($szallitasi_adat  as $tid => $tv){
			$pre = $szallitasi_adat_pre[$tid];
			if($tv != $pre){
				if(!$changedData[szallitasi_adat]){
					$changedData[szallitasi_adat] = 1;
				}else{
					$changedData[szallitasi_adat] += 1;
				}
			}
		}
		$szallitasi_keys = json_encode($szallitasi_adat,JSON_UNESCAPED_UNICODE);
		$this->db->update('orders',
			array(
				'szallitasi_keys' => $szallitasi_keys
			),
			"ID = ".$orderID
		);

		// Forgalom bejegyzése
		if($updateData['allapot'] == self::ORDER_STATUS_KEY_DONE){
			$traffic =  new Traffic( array( 'db' => $this->db ) );

			$bevetel 	= $this->calcIncomeFromBoughtItems($orderID);
			$kiadas 	= $this->calcOutgoFromBoughtItems($orderID);

			if($bevetel > 0){
				$re = $traffic->add(array(
					'type' 		=> $traffic::ADDTYPE_BUY,
					'item_id' 	=> $orderID,
					'bevetel' 	=> $bevetel
				));

				$re = $traffic->add(array(
					'type' 		=> $traffic::ADDTYPE_BUY_OUTGO,
					'item_id' 	=> $orderID,
					'kiadas' 	=> $kiadas
				));
			}
		}

		// E-mail Értesítő kiküldése
		// User alert
		$orderData = $this->getOrderData($accessKey);
		extract($orderData);

		$cart 				= $items;
		$szamlazasi_keys 	= json_decode($szamlazasi_keys, true);
		$szallitasi_keys 	= json_decode($szallitasi_keys, true);
		$total 				= 0;
		$termekAllapotok 	= $this->getMegrendeltTermekAllapotok();
		$orderAllapotok 	= $this->getMegrendelesAllapotok();

		$param 		= array();
		$msg 		= '<h2>Tisztelt '.$nev.'!</h2>';
		$msg 		.= '<div><strong><u>'.$orderData[azonosito].'</u></strong> azonosítójú rendelése <strong><u>'.NOW.'</u></strong> időponttal megváltozott.</div>';
		$msg 		.= '<div><h3>Változások:</h3></div>';
		foreach($changedData as $chkey => $chv){
			$keyname = $strKey[$chkey];
			if($chkey == 'termekAllapot'){
				$after = ' ('.$chv.' db termék)';
			}
			$msg 	.= '<div>- '.$keyname.$after.'</div>';
		}
		$msg 		.= '<div></div>';
		$msg 		.= '<div><h3>Megrendelés állapota:</h3></div>';
		$msg 		.= '<div><strong style="color:'.$orderAllapotok[$allapot][szin].';">'.$orderAllapotok[$allapot][nev].'</strong></div>';

		// Termék lista
		$msg 		.= '<div><h3>Termékek</h3></div>';
		$msg 		.= '<table width="100%" border="1" style="border-collapse:collapse; border:2px solid #dddddd; background:#ffffff;" cellpadding="10" cellspacing="0">';
		$msg 		.= '<thead>';
			$msg 		.= '<tr>';
				$msg 		.= '<th align="center">Me.</th>';
				$msg 		.= '<th align="center">Termék</th>';
				$msg 		.= '<th align="center">Bruttó e. ár</th>';
				$msg 		.= '<th align="center">Bruttó ár</th>';
				$msg 		.= '<th align="center">Állapot</th>';
			$msg 		.= '</tr>';
		$msg 		.= '</thead>';
		$msg 		.= '<tbody style="color:#888;">';

		if( count( $cart ) > 0){
			foreach($cart as $d){
				$total += ($d[ar]*$d[me]);
				$msg 		.= '<tr>';
					$msg 		.= '<td align="center">'.$d[me].'x</td>';
					$msg 		.= '<td><a href="'.$d[url].'">'.$d[nev].'</a></td>';
					$msg 		.= '<td align="center">'.\Helper::cashFormat($d[ar]).' Ft</td>';
					$msg 		.= '<td align="center">'.\Helper::cashFormat(($d[ar]*$d[me])).' Ft</td>';
					$msg 		.= '<td align="center"><strong style="color:'.$termekAllapotok[$d[allapotID]][szin].';">'.$termekAllapotok[$d[allapotID]][nev].'</strong></td>';
				$msg 		.= '</tr>';
			}
		}
			// Összesítő ár
			$msg 		.= '<tr>';
				$msg 		.= '<td colspan="4" align="right">Összesen:</td>';
				$msg 		.= '<td align="center">'.\Helper::cashFormat($total).' Ft</td>';
			$msg 		.= '</tr>';
			// Egyéb kiegészítő adatok
			$msg 		.= '<tr>';
				$msg 		.= '<td colspan="4" align="right">Szállítási költség:</td>';
				$msg 		.= '<td align="center">'.\Helper::cashFormat($szallitasi_koltseg).' Ft</td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td colspan="4" align="right">Kedvezmény:</td>';
				$msg 		.= '<td align="center">'.(($kedvezmeny > 0) ? '-'.\Helper::cashFormat($kedvezmeny):0).' Ft</td>';
			$msg 		.= '</tr>';
			if($szallitasi_koltseg > 0) $total += $szallitasi_koltseg;
			if($kedvezmeny > 0) $total -= $kedvezmeny;
			$msg 		.= '<tr>';
				$msg 		.= '<td colspan="4" align="right"><strong>Végösszeg:</strong></td>';
				$msg 		.= '<td align="center"><strong>'.\Helper::cashFormat($total).' Ft</strong></td>';
			$msg 		.= '</tr>';
		$msg 		.= '</tbody>';
		$msg 		.= '</table>';

		// Számlázási adatok
		$msg 		.= '<div><h3>Számlázási adatok</h3></div>';
		$msg 		.= '<table width="100%" border="1" style="border-collapse:collapse; border:2px solid #dddddd; background:#ffffff;" cellpadding="10" cellspacing="0">';
		$msg 		.= '<tbody>';
			$msg 		.= '<tr>';
				$msg 		.= '<td width="150" align="left">Név</td>';
				$msg 		.= '<td align="left"><strong>'.$szamlazasi_keys[nev].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Utca, házszám</td>';
				$msg 		.= '<td align="left"><strong>'.$szamlazasi_keys[uhsz].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Város</td>';
				$msg 		.= '<td align="left"><strong>'.$szamlazasi_keys[city].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Irányítószám</td>';
				$msg 		.= '<td align="left"><strong>'.$szamlazasi_keys[irsz].'</strong></td>';
			$msg 		.= '</tr>';
		$msg 		.= '</tbody>';
		$msg 		.= '</table>';

		// Szállítási adatok
		$msg 		.= '<div><h3>Szállítási adatok</h3></div>';
		$msg 		.= '<table width="100%" border="1" style="border-collapse:collapse; border:2px solid #dddddd; background:#ffffff;" cellpadding="10" cellspacing="0">';
		$msg 		.= '<tbody>';
			$msg 		.= '<tr>';
				$msg 		.= '<td width="150" align="left">Név</td>';
				$msg 		.= '<td align="left"><strong>'.$szallitasi_keys[nev].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Utca, házszám</td>';
				$msg 		.= '<td align="left"><strong>'.$szallitasi_keys[uhsz].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Város</td>';
				$msg 		.= '<td align="left"><strong>'.$szallitasi_keys[city].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Irányítószám</td>';
				$msg 		.= '<td align="left"><strong>'.$szallitasi_keys[irsz].'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Telefonszám</td>';
				$msg 		.= '<td align="left"><strong>'.$szallitasi_keys[phone].'</strong></td>';
			$msg 		.= '</tr>';
		$msg 		.= '</tbody>';
		$msg 		.= '</table>';

		// Egyéb adatok
		$msg 		.= '<div><h3>Egyéb adatok</h3></div>';
		$msg 		.= '<table width="100%" border="1" style="border-collapse:collapse; border:2px solid #dddddd; background:#ffffff;" cellpadding="10" cellspacing="0">';
		$msg 		.= '<tbody>';
			$msg 		.= '<tr>';
				$msg 		.= '<td width="150" align="left">Átvétel módja</td>';
				$msg 		.= '<td align="left"><strong>'.$this->getSzallitasiModeData($szallitasiModID,'nev').'</strong></td>';
			$msg 		.= '</tr>';
			$msg 		.= '<tr>';
				$msg 		.= '<td align="left">Fizetés módja</td>';
				$msg 		.= '<td align="left"><strong>'.$this->getFizetesiModeData($fizetesiModID,'nev').'</strong></td>';
			$msg 		.= '</tr>';

		$msg 		.= '</tbody>';
		$msg 		.= '</table>';

		// Előreutalás adatok
		if( $this->getFizetesiModeData( $fizetesiModID,'nev' ) == 'Előreutalás' ){

			$msg 		.= '<div><h3>Átutaláshoz szükséges adatok</h3></div>';
			$msg 		.= '<table width="100%" border="1" style="border-collapse:collapse; border:2px solid #dddddd; background:#ffffff;" cellpadding="10" cellspacing="0">';
			$msg 		.= '<tbody>';
				$msg 		.= '<tr>';
					$msg 		.= '<td width="150" align="left">Név</td>';
					$msg 		.= '<td align="left"><strong>GOLDONLINE Kft.</strong></td>';
				$msg 		.= '</tr>';
				$msg 		.= '<tr>';
					$msg 		.= '<td align="left">Számlaszám:</td>';
					$msg 		.= '<td align="left"><strong>11738008-20886343</strong></td>';
				$msg 		.= '</tr>';
				$msg 		.= '<tr>';
					$msg 		.= '<td align="left">Bank:</td>';
					$msg 		.= '<td align="left"><strong>OTP Bank Nyrt.</strong></td>';
				$msg 		.= '</tr>';
			$msg 		.= '</tbody>';
			$msg 		.= '</table>';

		}


		$msg 		.= '<div>Megrendelését nyomon követheti weboldalunkon. Regisztrált tagként, bejelentkezés után a megrendelések menüpont alatt keresse. <br /><br />
		<strong>Ha Ön nem regisztrált, ezen a linken megtekintheti aktuális megrendelését:</strong><br />
		<a href="'.DOMAIN.'order/'.$accessKey.'">'.DOMAIN.'order/'.$accessKey.'</a>
		</div>';

		$param[msg] 		= $msg;
		$param[elnevezes] 	= 'Megrendelése megváltozott';

		$remsg = \Helper::smtpMail(array(
			'recepiens' => array($email),
			'msg' 	=> $msg,
			'tema' 	=> $orderData[azonosito].'<br/>megrendelése megváltozott',
			'from' 	=> NOREPLY_EMAIL,
			'sub' 	=> 'Megrendelése megváltozott'
		));
		return $changedData;
	}
	/*
	* Megrendelt termékekből összesített bevétel összeg
	*/
	private function calcIncomeFromBoughtItems($orderID){
		if($orderID == '') return false;
		$q = "SELECT sum(o.egysegAr  * o.me) as ertek FROM `order_termekek` as o LEFT OUTER JOIN shop_termekek as t ON t.ID = o.termekID WHERE o.orderKey = $orderID";

		$qq = $this->db->query($q);
		$ertek = $qq->fetch(\PDO::FETCH_COLUMN);

		if($ertek == '' || is_null($ertek)){
			return false;
		}

		return $ertek;
	}
	/*
	* Megrendelt termékekből összesített kiadás (nagykerár) összeg
	*/
	private function calcOutgoFromBoughtItems($orderID){
		if($orderID == '') return false;
		$q = "SELECT
			sum(IF(t.akcios = 1, t.akcios_brutto_ar, brutto_ar)  * o.me) as ertek
		FROM `order_termekek` as o
		LEFT OUTER JOIN shop_termekek as t ON t.ID = o.termekID
		WHERE o.orderKey = $orderID";

		$qq = $this->db->query($q);
		$ertek = $qq->fetch(\PDO::FETCH_COLUMN);

		if($ertek == '' || is_null($ertek)){
			return false;
		}

		return $ertek;
	}

	private function getFizetesiModeData($id, $row = false){
		$q = "SELECT * FROM shop_fizetesi_modok WHERE ID = $id";

		extract($this->db->q($q));
		if(!$row){
			return $data;
		}else{
			return $data[$row];
		}

	}
	private function getSzallitasiModeData($id, $row = false){
		$q = "SELECT * FROM shop_szallitasi_mod WHERE ID = $id";

		extract($this->db->q($q));
		if(!$row){
			return $data;
		}else{
			return $data[$row];
		}

	}

	private function getUserKedvezmeny($userID){
		$kedv = 0;
		if($userID == '') return $kedv;
		$doneOrderID = $this->db->query("SELECT ID FROM order_allapot WHERE nev = 'Teljesítve'")->fetch(\PDO::FETCH_COLUMN);

		// Korábban rendelt
		$totalOrderPrice = $this->db->query("SELECT sum((me * egysegAr)) as ar FROM `order_termekek` WHERE userID = $userID and datediff(now(),hozzaadva) <= 365  and (SELECT allapot FROM orders WHERE ID = orderKey) = 4")->fetch(\PDO::FETCH_COLUMN);

		// Kosár tartalma
		$cartPrice = 0/*$this->db->query("SELECT sum(getTermekAr(t.marka,IF(t.egyedi_ar IS NOT NULL,t.egyedi_ar,IF(t.akcios,t.akcios_brutto_ar,t.brutto_ar))) * c.me) as cartPrice FROM `shop_kosar` as c LEFT OUTER JOIN shop_termekek as t ON t.ID = c.termekID WHERE c.gepID = ".\Helper::getMachineID().";")->fetch(\PDO::FETCH_COLUMN)*/;

		if($cartPrice > 0){
			$totalOrderPrice += $cartPrice;
		}

		// Kedvezmény sávok
		$sv = "SELECT * FROM torzsvasarloi_kedvezmeny ORDER BY ar_from ASC;";

		extract($this->db->q($sv,array('multi' => '1')));



		foreach($data as $d){
			$from 	= (int)$d[ar_from];
			$to 	= (int)$d[ar_to];
			$k 		= (float)$d[kedvezmeny];

			if($to === 0) $to = 999999999;

			if($totalOrderPrice >= $from && $totalOrderPrice <= $to){
				$kedv = $k;
				break;
			}

		}

		return $kedv;
	}

	function getSearchMostStat(){
		$date = date('Y-m');
		$q .= "SELECT *, sum(me) as me FROM `stat_kereses` WHERE szoveg != '' and datum LIKE '$date%' GROUP BY szoveg ORDER BY sum(me) desc LIMIT 0,8";

		extract($this->db->q($q,array('multi' => '1')));

		return $ret;
	}

	function getKategoriaAvStat(){
		$date = date('Y-m');
		$q = "SELECT
			s.*,
			IF(k.szulo_id IS NOT NULL, CONCAT(p.neve,' / ', k.neve), k.neve) as kategoriaNev,
			sum(me) as me
		FROM `stat_nezettseg_kategoria` as s
		LEFT OUTER JOIN shop_termek_kategoriak as k ON k.ID = s.kategoriaID
		LEFT OUTER JOIN shop_termek_kategoriak as p ON k.szulo_id = p.ID
		WHERE k.ID IS NOT NULL and s.datum LIKE '$date%'
		GROUP BY s.kategoriaID
		ORDER BY sum(me) DESC, k.neve ASC
		LIMIT 0, 10";

		extract($this->db->q($q,array('multi' => '1')));

		return $ret;
	}

	function getTermekMostViewStat(){
		$date = date('Y-m');
		$q .= "SELECT
			s.*,
			t.nev,
			t.meret,
			t.szin,
			t.cikkszam,
			getTermekUrl(t.ID,'".DOMAIN."') as url,
			sum(me) as me
		FROM `stat_nezettseg_termek` as s
		LEFT OUTER JOIN shop_termekek as t ON t.ID = s.termekID
		WHERE t.nev IS NOT NULL and s.datum LIKE '$date%'
		GROUP BY s.termekID
		ORDER BY sum(me) DESC, t.nev ASC
		LIMIT 0, 5";


		extract($this->db->q($q,array('multi' => '1')));

		return $ret;
	}
	function getTermekStatistic($arg = array()){
		$viewDate = date('Y-m');

		if($arg[inDate]){
			$viewDate = $arg[inDate];
		}

		$q = "SELECT
			s.termekID,
			t.nev as termek,
			t.meret,
			t.szin,
			t.akcios,
			t.cikkszam,
			t.ujdonsag,
			sum(me) as me,
			m.neve as markaNev,
			getTermekUrl(t.ID,'".DOMAIN."') as url
		FROM stat_nezettseg_termek as s
		LEFT OUTER JOIN shop_termekek as t ON t.ID = s.termekID
		LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
		WHERE s.ID IS NOT NULL ";

		if(is_array($viewDate)){
			$q .= " and (datum >= '".$viewDate[0]."' and datum <= '".$viewDate[1]."') ";
		}else{
			$q .= " and datum LIKE '".$viewDate."%' ";
		}
		$q .= "GROUP BY s.termekID ";
		$q .= "ORDER BY sum(me) DESC, t.nev ASC ";

		$arg[multi] = "1";
		extract($this->db->q($q, $arg));

		return $ret;

	}
	function getKeresesStatistic($arg = array()){
		$viewDate = date('Y-m');

		if($arg[inDate]){
			$viewDate = $arg[inDate];
		}

		$q = "SELECT
			s.szoveg,
			sum(s.me) as me
		FROM stat_kereses as s
		WHERE s.ID IS NOT NULL ";

		if(is_array($viewDate)){
			$q .= " and (s.datum >= '".$viewDate[0]."' and s.datum <= '".$viewDate[1]."') ";
		}else{
			$q .= " and s.datum LIKE '".$viewDate."%' ";
		}
		$q .= "GROUP BY s.szoveg ";
		$q .= "ORDER BY sum(s.me) DESC, s.szoveg ASC ";


		$arg[multi] = "1";
		extract($this->db->q($q, $arg));

		return $ret;

	}
	function getKategoriaStatistic($arg = array()){
		$viewDate = date('Y-m');

		if($arg[inDate]){
			$viewDate = $arg[inDate];
		}

		$q = "SELECT
			s.*,
			k.ID as kat_id,
			IF(k.szulo_id IS NULL, k.neve, CONCAT(p.neve, ' / ', k.neve)) as kategoriaNev,
			sum(me) as me
		FROM `stat_nezettseg_kategoria` as s
		LEFT OUTER JOIN shop_termek_kategoriak as k ON k.ID = s.kategoriaID
		LEFT OUTER JOIN shop_termek_kategoriak as p ON p.ID = k.szulo_id
		WHERE k.ID IS NOT NULL";

		if(is_array($viewDate)){
			$q .= " and (s.datum >= '".$viewDate[0]."' and s.datum <= '".$viewDate[1]."') ";
		}else{
			$q .= " and s.datum LIKE '".$viewDate."%' ";
		}
		$q .= "GROUP BY s.kategoriaID ";
		$q .= "ORDER BY sum(s.me) DESC ";

		$arg[multi] = "1";
		extract($this->db->q($q, $arg));

		return $ret;

	}

	function getUserList($arg = array()){
		$q = "SELECT
			f.*,
			(SELECT sum(me*egysegAr+o.szallitasi_koltseg-o.kedvezmeny) FROM `order_termekek`as t LEFT OUTER JOIN orders as o ON o.ID = t.orderKey WHERE o.allapot = 4 and t.userID = f.ID) as totalOrderPrices
		FROM felhasznalok as f";
		// WHERE
		$q .= " WHERE f.ID IS NOT NULL";
		if(count($arg[filters]) > 0){
			foreach($arg[filters] as $key => $v){
				switch($key)
				{
					case 'ID':
						$q .= " and t.".$key." LIKE '".$v."%' ";
					break;
					case 'nev':
						$q .= " and ".$key." LIKE '".$v."%' ";
					break;
					default:
						$q .= " and ".$key." = '".$v."' ";
					break;
				}

			}
		}
		$q .= "
		ORDER BY f.regisztralt DESC
		";

		$arg[multi] = "1";
		extract($this->db->q($q, $arg));

		$B = array();
		foreach($data as $d){
			$d[kedvezmeny] = $this->getUserKedvezmeny($d[ID]);
			$B[] = $d;
		}

		$ret[data] = $B;

		return $ret;
	}

	function getMessageData($msgID = false){

		if(!$msgID || $msgID == '') return false;

		$q = "SELECT u.* FROM uzenetek as u WHERE u.ID = $msgID";

		$data = $this->db->query($q)->fetch(\PDO::FETCH_ASSOC);

		if ( $data['tipus'] == 'requesttermprice' )
		{
			$data['item'] = $this->getTermekAdat( $data['item_id'] );
		}

		return $data;
	}

	function getUzenetek($arg = array()){
		$q = "SELECT
			u.*,
			CONCAT(m.neve,' ',t.nev) as item_nev,
			getTermekUrl(u.item_id,'".DOMAIN."') as item_url
		FROM uzenetek as u
		LEFT OUTER JOIN shop_termekek as t ON t.ID = u.item_id
		LEFT OUTER JOIN shop_markak as m ON m.ID = t.marka
		";
		// WHERE
		$q .= " WHERE u.ID IS NOT NULL";
			if(count($arg[filters]) > 0){
				foreach($arg[filters] as $key => $v){
					switch($key)
					{
						case 'ID':
							$q .= " and u.".$key." = '".$v."' ";
						break;
						case 'termeknev':
							$q .= " and CONCAT(m.neve,' ',t.nev) LIKE '".$v."%' ";
						break;
						case 'uzenet_targy':
							$q .= " and ".$key." LIKE '%".$v."%' ";
						break;
						case 'fvalaszolva':
							if($v == '0'){
								$q .= " and u.valaszolva IS NULL ";
							}else{
								$q .= " and u.valaszolva IS NOT NULL ";
							}
						break;
						case 'farchivalt':
							$q .= " and u.archivalva = '".$v."' ";
						break;
						case 'contact':
							$q .= " and (u.felado_nev LIKE '%".$v."%' or u.felado_email LIKE '%".$v."%') ";
						break;
						default:
							$q .= " and ".$key." = '".$v."' ";
						break;
					}

				}
			}
		$q .= "
		ORDER BY
		u.archivalva ASC,
		u.valaszolva ASC,
		u.elkuldve DESC";

		$arg[multi] = '1';
		extract($this->db->q($q,$arg));


		return $ret;
	}

	function replyToMessage($msgData, $post){
		$msg = '';
		extract($post);
		if($replyMsg == ''){
			throw new \Exception('Válaszüzenet megadása kötelező!');
		}

		if($msgData[felado_email] == ''){
			throw new \Exception('Nincs válaszcím, amire küldhetjük a válaszüzenetet!');
		}

		$msg .= $msgData[uzenet];
		$msg .= '----------------------------------<br /><br />';

		$valasz = '';
		$valasz .= '<div>Tisztelt '.$replyToName.'!</div>';
		$valasz .= '<br /><div>'.$replyMsg.'</div>';
		$valasz .= '<br /><div>Üdvözlettel,<br/>'.TITLE.' TEAM</div>';

		$msg .= $valasz;

		$remsg = \Helper::smtpMail(array(
			'recepiens' => array(trim($msgData[felado_email])),
			'msg' 		=> $msg,
			'tema' 		=> $msgData[uzenet_targy],
			'from' 		=> EMAIL,
			'fromName' 	=> TITLE,
			'sub' 		=> 'RE: '.$msgData[uzenet_targy]
		));

		if(count($remsg[failed]) == 0){
			$this->db->update("uzenetek",
			array(
				"valaszolva" 	=> NOW,
				"valasz_uzenet" => $valasz
			),
			"ID = ".$msgData[ID]
			);
		}
	}

	function doMessageAction($action, $prefix, $arg = array()){
		$query 		= '';
		$setValue 	= false;

		if($action){
			if(strpos($action,$prefix.'value') === 0){
				if($_POST[$prefix.'value'] == ''){
					throw new \Exception('Művelet nem lett végrehajtva, mert nem lett új érték megadva.');
				}
				$setValue = $_POST[$prefix.'value'];
			}else{
				if($_POST[$action] == ''){
					throw new \Exception('Művelet nem lett végrehajtva, mert nem lett új érték kiválasztva.');
				}
				$setValue = $_POST[$action];
			}

			if(count($_POST[selectedItem]) == 0){
				throw new \Exception('Nincs kiválsztva üzenet, amin a művelet végrehajtható.');
			}

			switch($action){
				case $prefix.'archivalva':
					$query = "UPDATE uzenetek SET archivalva = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'torles':
					if($setValue == '1'){
						$query = "DELETE FROM uzenetek WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
					}
				break;
			}
		}

		if($query != ''){
			$this->db->query($query);
		}

		return 'Művelet végrehajtva! <strong>'.count($_POST[selectedItem]).' db üzenet adata megváltozott</strong>!';
	}

	function doAction($action, $prefix, $arg = array()){
		$query 		= '';
		$err 		= false;
		$setValue 	= false;

		switch ($action) {
			case 'action_uploadimage':
				\Helper::reload('/termekek/upload_image/'.implode("|", $_POST['selectedItem']));
			break;
		}

		if($action){
			if( $action != $prefix.'variacio') {
				if(strpos($action,$prefix.'value') === 0){
					if($_POST[$prefix.'value'] == ''){
						throw new \Exception('Művelet nem lett végrehajtva, mert nem lett új érték megadva.');
					}
					$setValue = $_POST[$prefix.'value'];
				}else {
					if($_POST[$action] == ''){
						throw new \Exception('Művelet nem lett végrehajtva, mert nem lett új érték kiválasztva.');
					}
					$setValue = $_POST[$action];
				}

				if(count($_POST[selectedItem]) == 0){
					throw new \Exception('Nincs kiválsztva termék, amin a művelet végrehajtható.');
				}
			}


			switch($action){
				case $prefix.'addtocategory':
					$catid = $_POST[$prefix.'addtocategory'];

					foreach ( $catid as $cid ) {
						foreach ($_POST['selectedItem'] as $tid ) {
							$in = $this->db->squery("SELECT 1 FROM shop_termek_in_kategoria WHERE termekID = :tid and kategoria_id = :cat;", array('tid' => $tid, 'cat' => $cid));

							if($in->rowCount() != 0) continue;

							$this->db->insert(
								'shop_termek_in_kategoria',
								array_combine(
								array(
									'termekID',
									'kategoria_id'
								),
								array( $tid, $cid)
								)
							);
						}
					}

				break;
				case $prefix.'defaultcategory':
					$query = "UPDATE shop_termekek SET alapertelmezett_kategoria = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'footer_listing':
					$query = "UPDATE shop_termekek SET footer_listing = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'fotermek':
					$query = "UPDATE shop_termekek SET fotermek = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'szin':
					$query = "UPDATE shop_termekek SET szin = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'meret':
					$query = "UPDATE shop_termekek SET meret = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'kategoria':
					$query = "UPDATE shop_termekek SET termek_kategoria = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'marka':
					$query = "UPDATE shop_termekek SET marka = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'szallitasID':
					$query = "UPDATE shop_termekek SET szallitasID = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'keszletID':
					$query = "UPDATE shop_termekek SET keszletID = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'lathato':
					$query = "UPDATE shop_termekek SET lathato = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'cetelem':
					$query = "UPDATE shop_termekek SET no_cetelem = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'ujdonsag':
					$query = "UPDATE shop_termekek SET ujdonsag = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'akcios':
					$query = "UPDATE shop_termekek SET akcios = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'szuper_akcios':
					$query = "UPDATE shop_termekek SET szuper_akcios = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_netto_ar':
					$query = "UPDATE shop_termekek SET netto_ar = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_akcios_netto_ar':
					$query = "UPDATE shop_termekek SET akcios_netto_ar = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_brutto_ar':
					$query = "UPDATE shop_termekek SET brutto_ar = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_akcios_brutto_ar':
					$query = "UPDATE shop_termekek SET akcios_brutto_ar = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_egyedi_ar':
					$setValue = ($setValue == 0) ? 'NULL' : $setValue;
					$query = "UPDATE shop_termekek SET egyedi_ar = $setValue WHERE ID IN (".implode($_POST[selectedItem],', ').") ";

				break;
				case $prefix.'value_raktar_keszlet':
					$query = "UPDATE shop_termekek SET raktar_keszlet = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";
				break;
				case $prefix.'value_kulcsszavak':
					$setValue = ($setValue == '') ? 'NULL' : $setValue;
					$query = "UPDATE shop_termekek SET kulcsszavak = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";

				break;
				case $prefix.'value_linkek':
					$setValue = ($setValue == '') ? 'NULL' : $setValue;
					$query = "UPDATE shop_termekek SET linkek = '$setValue' WHERE ID IN (".implode($_POST[selectedItem],', ').") ";

				break;
				case $prefix.'akcio_szaz':
					$err = true;
					$percent 	= (int)$_POST[ $prefix.'akcio_szaz' ][percent];
					$mode 		= ($_POST[ $prefix.'akcio_szaz' ][type] == 0) ? 'akcios' : 'szuperakcios';

					$query = "UPDATE shop_termekek SET ";

					if( $_POST[ $prefix.'akcio_szaz' ][type] == -1){
						$query .= " akcios = 0";
					}else{
						$query .= " akcios = 1";
					}

					if( !is_null( $percent ) ){

						if( $percent > 0 ){
							$p 		= ((int)$percent) / 100 + 1;
							$query .= ", akcios_netto_ar = netto_ar -  ( netto_ar / 100 * ".$percent.")";
							$query .= ", akcios_brutto_ar = brutto_ar - ( brutto_ar / 100 * ".$percent.")";
						}else if($percent === 0){
							$query .= ", akcios_netto_ar = 0";
							$query .= ", akcios_brutto_ar = 0";
							$query .= ", akcios = 0";
						}else{
							throw new \Exception('Művelet nem lett végrehajtva. Az akciós mérték (%) értéke nem lehet 0 -nál kevesebb!');
						}
						//echo $query;
					}

					$query .= " WHERE ID IN (".implode($_POST[selectedItem],', ').") ";

					//echo $query;
				break;
			}
		}

		if($query != ''){
			$this->db->query($query);
		}

		return 'Művelet végrehajtva! <strong>'.count($_POST[selectedItem]).' db termék adata megváltozott</strong>!';
	}

	/******************************
	* Nagyker árlista
	*******************************/
	const NAGYKER_LISTA_PATH = '../src/store/nagyker_csv/';

	function getNagykerArlista( $arg = array() ){
		$back = array();

		// Összes nagyker
		$nagyker_db 	= $this->db->query("SELECT * FROM nagyker");
		$nagyker_data 	= $nagyker_db->fetchAll(\PDO::FETCH_ASSOC);

		foreach( $nagyker_data as $nd ){
			$list = array();
			$info = array();

			$list = $this->getNagykerCSVLista( $nd[ID] );

			$back[] = array(
				'ID' 	=> $nd[ID],
				'nev' 	=> $nd[nagyker_nev],
				'lista' => $list,
				'info' 	=> $info
			);
		}


		return $back;
	}

	function getNagykerCSVLista( $nagyker_id = null, $arg = array() ){
		if( is_null($nagyker_id) ) return false;

		$q = "SELECT ID, file_name, file_path, uploaded_at, imported_at, refreshed_at FROM nagyker_uploaded_xls WHERE nagyker_id = $nagyker_id";

		$arg[multi] = 1;

		extract( $this->db->q( $q, $arg ) );

		return $data;
	}

	function uploadNagykerLista(){
		$wrong_file_type 	= 0;
		$upp 				= array();

		$files = $_FILES[csv];


		if( !$_POST[nagyker_id] ){
			throw new \Exception("Nem választotta ki, hogy melyik nagykerbe szeretnél feltölteni a listát!");
		}

		$fs = 0;

		foreach( $files[name] as $f ){

			$type 	= $files[type][$fs];
			$ext 	= pathinfo( $files[name][$fs], PATHINFO_EXTENSION );

			if( $type != 'application/vnd.ms-excel' || $ext != 'csv' ){
				$wrong_file_type++;
			}else{
				$upp[] = array(
					'temp' => $files[tmp_name][$fs],
					'name' => $files[name][$fs]
				);
			}

			$fs++;
		}

		if( $wrong_file_type > 0 ){
			throw new \Exception(count( $files[name] )." / ".$wrong_file_type." db hibás fájltípus! Csak .csv típusú listákat tölthet fel!");
		}


		foreach( $upp as $up ){
			if(@move_uploaded_file( $up[temp], self::NAGYKER_LISTA_PATH.date('YmdHis').'__'.$up[name] )){
				$this->db->insert(
					"nagyker_uploaded_xls",
					array_combine(
					array( 'nagyker_id', 'file_name', 'file_path' ),
					array( $_POST[nagyker_id], $up[name], str_replace('../src/','', self::NAGYKER_LISTA_PATH).date('YmdHis').'__'.$up[name]  )
					)
				);
			}else{

			}
		}
	}

	public function __destruct()
	{
	 	$this->db = null;
	 	$this->settings = null;
	}

}
?>
