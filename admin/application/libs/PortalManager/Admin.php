<?
namespace PortalManager;

use PortalManager\Image;
use PortalManager\Request;
use FileManager\FileLister;
use MailManager\Mailer;
use PortalManager\Template;

/**
* class Admin
* @package PortalManager
* @version v1.0
*/
class Admin
{
	const SUPER_ADMIN_PRIV_INDEX = 0;

	private $db = null;
	private $admin_id = 0;
	private $admin = false;
	private $settings = false;

	function __construct( $admin_id = false, $arg = array() )
	{
		$this->db = $arg[db];
		$this->settings = $arg[view]->settings;

		if ($admin_id) {
			$this->admin_id = $admin_id;
			$this->getAdmin();
		}

		return $this;
	}

	/**
	 * Adminisztrátor létregozása
	 * @param array $data POST, admin adatokkal
	 * @return  void
	 */
	public function add( $data )
	{
		$name 	= ($data['admin_user']) ?: false;
		$pw1 	= ($data['admin_pw1']) ?: false;
		$pw2 	= ($data['admin_pw2']) ?: false;
		$status = $data['admin_status'];
		$jog 	= $data['admin_jog'];

		if (!$name) {
			throw new \Exception("Kérjük, hogy adja meg az adminisztrátor <strong>belépési azonosítóját</strong>!");
		}

		if ( !$pw1 || !$pw2 ) {
			throw new \Exception("Kérjük, hogy adja meg az adminisztrátor <strong>jelszavát</strong>!");
		}

		if ( $pw1 != $pw2 ) {
			throw new \Exception("A megadott jelszó nem egyezik, kérjük, hogy írja be újra!");
		}

		$this->db->insert(
			"admin",
			array(
				'user' => trim($name),
				'pw' => \Hash::jelszo($pw2),
				'engedelyezve' => $status,
				'jog' => $jog,
			)
		);
	}

	public function getTermekAllapotok()
	{
		extract( $this->db->q( "SELECT ID, elnevezes as nev FROM shop_termek_allapotok;", array( 'multi' => 1)));

		return $data;
	}

	public function getmarkak()
	{
		extract( $this->db->q( "SELECT ID, neve as nev FROM shop_markak;", array( 'multi' => 1)));

		return $data;
	}

	public function getSzallitasiIdok()
	{
		extract( $this->db->q( "SELECT ID, elnevezes as nev FROM shop_szallitasi_ido;", array( 'multi' => 1)));

		return $data;
	}

	public function save( $new_data )
	{
		$name 	= ($new_data['admin_user']) ?: false;
		$status = $new_data['admin_status'];
		$jog 	= $new_data['admin_jog'];
		$password = false;

		if (!$name) {
			throw new \Exception("Kérjük, hogy adja meg az adminisztrátor <strong>belépési azonosítóját</strong>!");
		}

		if ($new_data['admin_pw1'] != '' && $new_data['admin_pw2'] != '') {
			if ( $new_data['admin_pw1'] != $new_data['admin_pw2'] ) {
				throw new \Exception("A megadott jelszó nem egyezik, kérjük, hogy írja be újra!");
			}
			$password = ", pw = '".\Hash::jelszo($new_data['admin_pw2'])."'";
		}

		$this->db->query(sprintf("UPDATE admin SET user = '%s', jog = %d, engedelyezve = %d $password WHERE ID = %d", $name, $jog, $status, $this->admin_id ));
	}

	/**
	* Online XML terméklista URL mentése
	* @param string $url XML online URL elérhetőség
	*
	**/
	public function save_product_xml_url( $url )
	{
		if ( $url == '' ) {
			throw new \Exception( "Kérjük, hogy adja meg az URL-t." );
		}

		$this->db->update(
			"beallitasok",
			array(
				'bErtek' => trim($url)
			),
			"bKulcs = 'products_list_xml_url'"
		);
	}

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

	public function checkImportProducts( $xml_list = null )
	{
		$return = array();
		$return_list = new \SplFixedArray( count( $xml_list ) );

		// 21 mb
		//echo '<br>-'.round(memory_get_usage(true)/1048576,2);
		//return false;

		if ( $xml_list ) {

			$total_items = 0;
			$total_exists = 0;
			$total_not_exists = 0;
			$total_need_update = 0;
			$updateable_items = array();

			foreach ( $xml_list as $item ) {
				$total_items++;

				$in = array();
				$in['source'] = (array)$item;

				$in['data']['raktar_supplierid'] = strtoupper($in['source']['supplier_articlenumber']);
				$in['data']['raktar_articleid'] = $in['source']['articleid'];
				$in['data']['raktar_variantid'] = $in['source']['variantid'];
				$in['data']['raktar_number'] = $in['source']['number'];

				$in['data']['nev'] 		= $in['source']['name'];
				$in['data']['cikkszam'] = $in['source']['articleid'].'-'.$in['source']['variantid'];
				$in['data']['szin_kod']	= $in['source']['color_number'];
				$in['data']['szin'] 	= ($in['source']['color_name'] === '00' || $in['source']['color_name'] == '' ) ? NULL : $in['source']['color_name'];
				$in['data']['meret'] 	= ($in['source']['size_name'] === '00' || $in['source']['size_name'] == '') ? NULL : $in['source']['size_name'];
				$in['data']['netto_ar']	= $in['source']['netprice'];
				$in['data']['brutto_ar']= $in['source']['grossprice'];
				$in['data']['rovid_leiras']= ( is_string($in['source']['description']) && $in['source']['description']) ? $in['source']['description'] : NULL;
				$in['data']['kulcsszavak'] = $in['data']['nev']. ' '. str_replace(array( ' / ', ', ', ',' ), ' ', $in['data']['szin']) . ' ' . $in['data']['meret'];

				// Csoport kategória rövid leírásból
				$csoport_kategoria = NULL;
				$br_leiras = nl2br( $in['data']['rovid_leiras'], false );


				if ( strpos( $br_leiras, '<br>') !== false ) {
				 	$x_br_leiras = explode( "<br>", $br_leiras );
				 	$csoport_kategoria = trim( $x_br_leiras[0] );
				} else if( $in['data']['rovid_leiras'] ) {
					$csoport_kategoria = trim( $in['data']['rovid_leiras'] );
				}

				$in['data']['csoport_kategoria'] = $csoport_kategoria;

				/**
				 * Kategóriák kialakítása
				 **/
				$default_kat_id = 0;

				$kat_list = $in['source']['categories'];
				$kat_list_done = array();

				$kat_step = 0;
				if( count( $kat_list ) > 0 ) {
					foreach ( $kat_list as $kat_hash ) {
						$kat_step++;

						// Kategória ID, hashkey-ből
						$kat_id = $this->db->query("SELECT ID FROM shop_termek_kategoriak WHERE hashkey = '$kat_hash';")->fetchColumn();

						// Összes felmenü kategória hashkey lekérdezés
						foreach ( $this->getCategorySet( $kat_id ) as $c_hash ) {
							$kat_list_done[] = $c_hash['hash'];
						}

						// Alapértelmezett kategória
						if( $kat_step == 1 ) {
							$default_kat_id = $kat_id;
						}
					}
				}

				if(  count($in['source']['categories']) > 0 ) {
					$in['data']['kategoria_hashkeys'] = implode( ',', $kat_list_done );
				} else {
					$in['data']['kategoria_hashkeys'] = NULL;
				}

				// Alapértelmezett kategória megadása
				$in['data']['alapertelmezett_kategoria'] = NULL;


				/**
				* Linkek
				**/
				if(  $in['source']['links']->link && false ) {
					$in['data']['link_hashkeys'] = implode( ',', (array)$in['source']['links']->link );
				}

				$check = $this->db->query( sprintf("
					SELECT 				ID, netto_ar, brutto_ar, rovid_leiras, nev, rovid_leiras, szin_kod, akcios, akcios_netto_ar, akcios_brutto_ar, szin, meret, raktar_supplierid, link_hashkeys, kategoria_hashkeys, csoport_kategoria
					FROM 				shop_termekek
					WHERE 				raktar_articleid = '%s' and cikkszam = '%s'
				;",
				$in['data']['raktar_articleid'],
				$in['data']['cikkszam']
				) );

				$in['status']['exists'] 		= ( $check->rowCount() == 0 ) ? 0 : 1;

				$want_update = 0;
				$update_rows = array();
				$exist_data = $check->fetch(\PDO::FETCH_ASSOC);

				if ( $in['status']['exists'] == 1 ) {
					// Változások ellenőrzése
					if ( $in['data']['netto_ar'] != $exist_data['netto_ar'] ) {
						$update_rows[] = 'netto_ar';
						$want_update = 1;

						// Ha kisebb az ár, mint a meglévő
						if( $exist_data['akcios'] == 0 && $in['data']['netto_ar'] < $exist_data['netto_ar'] ) {
							$in['status']['discount_set'] 	= 1;
						} else if( $exist_data['akcios'] == 1 && $in['data']['netto_ar'] < $exist_data['akcios_netto_ar'] ) {
							$in['status']['discount_set'] = 1;
						} else if( $exist_data['akcios'] == 1 && $in['data']['netto_ar'] > $exist_data['akcios_netto_ar'] ) {
							$in['status']['discount_clear'] = 1;
						} else if( $exist_data['akcios'] == 1 && $in['data']['netto_ar'] == $exist_data['akcios_netto_ar'] ) {
							$in['status']['discount_set'] = 1;
						}
					}

					if ( $in['data']['brutto_ar'] != $exist_data['brutto_ar'] ) {
						$update_rows[] = 'brutto_ar';
						$want_update = 1;
					}
					if ( $in['data']['rovid_leiras'] != $exist_data['rovid_leiras'] ) {
						$update_rows[] = 'rovid_leiras';
						$want_update = 1;
					}
					if ( $in['data']['nev'] != $exist_data['nev'] ) {
						$update_rows[] = 'nev';
						$want_update = 1;
					}
					if ( $in['data']['szin_kod'] != $exist_data['szin_kod'] ) {
						$update_rows[] = 'szin_kod';
						$want_update = 1;
					}
					if ( $in['data']['szin'] != $exist_data['szin'] ) {
						$update_rows[] = 'szin';
						$want_update = 1;
					}
					if ( $in['data']['meret'] != $exist_data['meret'] ) {
						$update_rows[] = 'meret';
						$want_update = 1;
					}
					if ( $in['data']['raktar_supplierid'] != $exist_data['raktar_supplierid'] ) {
						$update_rows[] = 'raktar_supplierid';
						$want_update = 1;
					}

					if ( $in['data']['link_hashkeys'] != $exist_data['link_hashkeys'] ) {
						$update_rows[] = 'link_hashkeys';
						$want_update = 1;
					}

					if ( $in['data']['kategoria_hashkeys'] != $exist_data['kategoria_hashkeys'] ) {
						$update_rows[] = 'kategoria_hashkeys';
						$want_update = 1;
					}

					if ( $in['data']['csoport_kategoria'] != $exist_data['csoport_kategoria'] ) {
						$update_rows[] = 'csoport_kategoria';
						$want_update = 1;
					}

				}

				///////////////
				/*if ( in_array( 'netto_ar', $update_rows) && !in_array( 'brutto_ar', $update_rows) ) {
					$update_rows[] = 'brutto_ar';
					$in['data']['brutto_ar'] = $in['data']['netto_ar'] * 1.27;
				}

				if ( in_array( 'brutto_ar', $update_rows) && !in_array( 'netto_ar', $update_rows) ) {
					$update_rows[] = 'netto_ar';
					$in['data']['netto_ar'] = $in['data']['brutto_ar'] / 1.27;
				} */


				$in['status']['exists_db_id'] 	= $exist_data['ID'];
				$in['status']['exists_db_data']	= $exist_data;
				$in['status']['need_update'] 	= $want_update;
				$in['status']['update_rows'] 	= $update_rows;

				if( $in['status']['need_update'] === 1 ){
					$total_need_update++;
					$updateable_items[] = $in;
				}

				if( $in['status']['exists'] === 1) {
					$total_exists++;
				} else {
					$total_not_exists++;
				}

				$return_list[ $total_items - 1 ] = $in;
				unset( $in );
			}
		} else {
			return false;
		}

		unset( $xml_list );

		$return['updateable_items'] = $updateable_items;
		$return['total_items'] = $total_items;
		$return['total_exists'] = $total_exists;
		$return['total_not_exists'] = $total_not_exists;
		$return['total_need_update'] = $total_need_update;
		$return['list'] = $return_list;

		return $return;
	}

	/**
	*  Felmenő kategóriák ID-jának lekérdezése
	**/
	public function getCategorySet( $cat_id )
	{
		$set = array();

		$walk = true;

		while( $walk ) {
			$q = $this->db->query("SELECT ID, szulo_id, neve, hashkey FROM shop_termek_kategoriak WHERE ID = $cat_id;");
			$qd = $q->fetch(\PDO::FETCH_ASSOC);

			if( is_null( $qd['szulo_id'] ) ) {
				$set[] = array(
					'ID' => $qd['ID'],
					'nev' => $qd['neve'],
					'hash' => $qd['hashkey']
				);

				$walk = false;
			} else {
				$set[] = array(
					'ID' => $qd['ID'],
					'nev' => $qd['neve'],
					'hash' => $qd['hashkey']
				);
				$cat_id = $qd['szulo_id'];
			}
		}

		return $set;
	}

	public function getOrderData($key, $arg = array() ){
		$referer = false;

		$q = "SELECT
			o.*
		FROM orders as o
		WHERE o.accessKey = '$key'";
		extract($this->db->q($q));

		// Ajánló adatai
		if ($data['referer_code'])
		{
			$partner_ref = (new PartnerReferrer ( $data['referer_code'], array(
				'db' 		=> $this->db,
				'settings' 	=> $this->settings
			)))
			->load();

			if ($partner_ref->isValid())
			{
				$referer = $partner_ref;
			}
		}

		$arg[kedvezmeny] 	= $data[kedvezmeny_szazalek];
		$data[items] 		= $this->getOrderListItems($data[ID], $arg);
		$data[referer] 		= $referer;

		return $data;
	}

	private function getOrderListItems($orderID, $arg = array() ){
		if($orderID == '') return false;
		$q = "SELECT
			ok.*,
			t.nev,
			(ok.egysegAr * ok.me) as subAr,
			t.profil_kep,
			t.szin_kod,
			t.szin,
			t.meret,
			t.raktar_articleid,
			t.raktar_variantid,
			getTermekUrl(t.ID,'".$this->settings['domain']."') as url,
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

		$bdata = array();
		$kedvezmenyes = ($arg[kedvezmeny] > 0) ? true : false;
		foreach ($data as $d) {
			if( $kedvezmenyes ) {
				\PortalManager\Formater::discountPrice( $d[ar], $arg[kedvezmeny] );
			}
			$bdata[] = $d;
		}

		return $bdata;
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

	function saveOrderData($orderID, $post)
	{
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
			'szamlazasi_adat' 		=> 'Számlázási adatok',
			'pickpackpont_uzlet_kod'=> 'Pick Pack Pont átvétel hely',
			'uj_termek' 			=> 'Újonnan hozzáadott termék(ek)'
		);

		$orderData 	= $this->getOrderData($accessKey);

		/*echo '<pre>';
		print_r($orderData);

		return false;	*/

		$users 		= new Users( array(
			'db' => $this->db,
			'admin' => true,
			'settings' => $this->settings )
		);
		$user 		= $users->get(array( 'user' => $orderData['email'] ));

		if( $user ) {
			$totalOrderPrice = (float) $this->db->query("
				SELECT 				sum((o.me * o.egysegAr)) as ar
				FROM 				`order_termekek` as o
				WHERE 				o.userID = {$user[data][ID]} and
									datediff(now(),o.hozzaadva) <= 365  and
									(SELECT allapot FROM orders WHERE ID = o.orderKey) = {$this->settings['flagkey_orderstatus_done']}
			")->fetch(\PDO::FETCH_COLUMN);
		} else {
			$totalOrderPrice = 0;
		}

		// Új termékek hozzáadása
		$added_new_items = array();
		if ( count($post['new_product']) > 0 ) {
			$newi = -1;
			foreach ( $post['new_product'] as $new_id ) { $newi++;
				if( !$new_id ) continue;

				$check_new = $this->db->query("
					SELECT 			IF(egyedi_ar IS NOT NULL,
									egyedi_ar,
									getTermekAr(marka, IF(akcios,akcios_brutto_ar,brutto_ar))
									) as ar
					FROM 			shop_termekek
					WHERE 			ID = $new_id");

				if ( $check_new->rowCount() != 0 ) {
					$check_usage = $this->db->query( sprintf("SELECT egysegAr FROM order_termekek WHERE orderKey = %d and termekID = %d", $orderData['ID'], $new_id ));

					if ( $check_usage->rowCount() != 0 ) {
						$this->db->query( sprintf("UPDATE order_termekek SET me = me + %d, allapotID = %d WHERE orderKey = %d and termekID = %d", $post['new_product_number'][$newi], $post['new_product_allapot'][$newi], $orderData['ID'], $new_id ));
					} else {

						$tdata = $check_new->fetch(\PDO::FETCH_ASSOC);

						$this->db->insert(
							"order_termekek",
							array(
								"orderKey" => $orderData['ID'],
								"gepID" => $orderData['gepID'],
								"userID" => $orderData['userID'],
								"email" => $orderData['email'],
								"termekID" => $new_id,
								"me" => $post['new_product_number'][$newi],
								"egysegAr" => $tdata['ar'],
								"allapotID" => $post['new_product_allapot'][$newi]
							)
						);
					}

					$added_new_items[] = $new_id;

					// Készlet kivonás
					if ( $this->settings['stock_withdrawal'] == '1' ) {
						$this->db->query("UPDATE shop_termekek SET raktar_keszlet = raktar_keszlet - ".$post['new_product_number'][$newi]." WHERE ID = ".$new_id);
					}
				}
			}
		}

		if ( count($added_new_items) > 0 ) {
			$changedData[uj_termek]	= count($added_new_items);
		}

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
		// Pick Pack Pont üzletkód
		$ppp_uzlet 		= $post[pickpackpont_uzlet_kod];
		$ppp_uzlet_pre 	= $post[prev_pickpackpont_uzlet_kod];
		if($ppp_uzlet != $ppp_uzlet_pre){
			$updateData['pickpackpont_uzlet_kod'] 	= $ppp_uzlet;
			$changedData['pickpackpont_uzlet_kod']	= 1;
		}
		// Fizetés
		$fizetes 		= $post[fizetes][$orderID];
		$fizetes_pre 	= $post[prev_fizetes][$orderID];
		if($fizetes != $fizetes_pre){
			$updateData['fizetesiModID'] 	= $fizetes;
			$changedData[fizetes]			= 1;
		}

		// Megrendelés megváltoztatása
		if ( !empty($updateData)) {
			$this->db->update(
				'orders',
				$updateData,
				"ID = $orderID"
			);
		}

		// Termékek állapota

		$termek_allapotok 		= $post[termekAllapot][$orderID];
		$termek_allapotok_pre 	= $post[prev_termekAllapot][$orderID];
		$termekAllapotChange 	= array();
		$termek_will_delete 	= array();

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
			$this->db->update(
				'order_termekek',
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

		foreach($termekMeChange as $tac){
			$changedData[termekMe]	= 1;
			$this->db->update(
				'order_termekek',
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

		foreach($termekArChange as $tac){
			$changedData[termekAr]	= 1;
			$this->db->update(
				'order_termekek',
				array(
					'egysegAr' => $tac[val]
				),
				"ID = ".$tac[id]
			);
		}

		// Számlázási adatok
		$szamlazasi_adat 		= $post[szamlazasi_adat][$orderID];
		$szamlazasi_adat_pre 	= $post[prev_szamlazasi_adat][$orderID];
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
		$this->db->update(
			'orders',
			array(
				'szamlazasi_keys' => $szamlazasi_keys
			),
			"ID = ".$orderID
		);

		// Szállítási adatok
		$szallitasi_adat 		= $post[szallitasi_adat][$orderID];
		$szallitasi_adat_pre 	= $post[prev_szallitasi_adat][$orderID];
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
		$this->db->update(
			'orders',
			array(
				'szallitasi_keys' => $szallitasi_keys
			),
			"ID = ".$orderID
		);

		// E-mail Értesítő kiküldése
		// User alert
		if( isset( $post['alert_email_out'][$orderID] ) )
		{
			$orderData = $this->getOrderData($accessKey);
			extract($orderData);

			$is_pickpackpont = ( $szallitasiModID == $this->settings['flagkey_pickpacktransfer_id'] ) ? true : false;
			$is_eloreutalas = ( $fizetesiModID == $this->settings['flagkey_pay_banktransfer'] ) ? true : false;
			$is_payu = ( $fizetesiModID == $this->settings['flagkey_pay_payu'] ) ? true : false;

			$total = 0;
			$mail = new Mailer(
				$this->settings['page_title'],
				SMTP_USER,
				$this->settings['mail_sender_mode']
			);

			$mail->add( $email );

			$arg = array(
				'settings' 		=> $this->settings,
				'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!',
				'nev' 			=> $nev,
				'email' 		=> $email,
				'orderData' 	=> $orderData,
				'cart' 			=> $items,
				'total' 		=> $total,
				'szallitasi_koltseg' => $szallitasi_koltseg,
				'kedvezmeny' 	=> $orderData['kedvezmeny'],
				'szamlazasi_keys' => $szamlazasi_keys,
				'szallitasi_keys' => $szallitasi_keys,
				'atvetel' 		=> $this->getSzallitasiModeData($szallitasiModID,'nev'),
				'fizetes' 		=> $this->getFizetesiModeData($fizetesiModID,'nev'),
				'ppp_uzlet_str' => $pppkod,
				'is_pickpackpont' => $is_pickpackpont,
				'orderID' 		=> $orderID,
				'megjegyzes' 	=> $comment,
				'is_eloreutalas' => $is_eloreutalas,
				'accessKey' => $accessKey,
				'termekAllapotok' => $this->getMegrendeltTermekAllapotok(),
				'orderAllapotok' => $this->getMegrendelesAllapotok(),
				'changedData' => $changedData,
				'allapot' => $allapot,
				'strKey' => $strKey,
				'ppp_uzlet_str' => $pickpackpont_uzlet_kod,
			);

			$mail->setSubject( 'Megrendelése megváltozott: '.$orderData[azonosito] );
			$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'user_order_changes', $arg ) );
			$re = $mail->sendMail();
		}

		/**
		 * WebshopSale report
		 * */
		if( $updateData['allapot'] == $this->settings['flagkey_webshopSaleReport_orderstatus'] ) {
		/* * /

			// Számlázó program felé megrendelés adatok megküldése
			$items = array();
			$total_ar = (float) 0;
			$comment = '';
			$total_amount = 0;

			foreach ( $orderData['items'] as $cartItems ) {
				// Nettó ár
				$ar_netto = $cartItems['ar'] / 1.27;
				// Bruttó ár
				$ar_brutto = $cartItems['ar'];

				$total_ar += ($ar_brutto * $cartItems['me']);
				$items[] = array(
					"variantname" 	=> $cartItems['nev'] . ' (Méret: '.(($cartItems['meret']) ? $cartItems['meret'] : '-').'; Szín: '.(($cartItems['szin_kod']) ? $cartItems['szin_kod'] : '-').')',
					"variantid" 	=> (int)$cartItems['raktar_variantid'],
		            "netprice" 		=> (float)$ar_netto,
		            "amount" 		=> (int)$cartItems['me']
				);
				$total_amount +=  (int)$cartItems['me'];
			}


			$szam_data = json_decode($orderData['szamlazasi_keys'], true);
			$szall_data = json_decode($orderData['szallitasi_keys'], true);

			$buyer 				= array();
			$buyer["id"] 		= (int)$orderData['userID'];
	        $buyer["name"] 		= $szam_data['nev'];
	        $buyer["country"] 	= $szam_data['state'];
	        $buyer["zipcode"] 	= $szam_data['irsz'];
	        $buyer["city"] 		= $szam_data['city'];
	        $buyer["address"] 	= $szam_data['uhsz'];

	        $buyer["other"] 	= "";

	        // Összesített termékek bruttó árai
	        $total_items_ar_brutto = $total_ar;
	        // Összesített termékek nettó árai
	        $total_items_ar_netto = $total_items_ar_brutto / 1.27;


	        // Szállítás hozzáadás
	        if ( $orderData['szallitasi_koltseg'] > 0 ) {
	        	//$comment .= "Szállítási költség (bruttó): " . $orderData['szallitasi_koltseg'] . " Ft, ";
	        	$total_ar += (int)( $orderData['szallitasi_koltseg'] );
	        }

	        $comment .= 'Megrendelő: '.$szam_data["nev"]." (". $email ."), ";
	        $comment .= 'Cím: '.$szall_data['irsz'].' '.$szall_data['city']. ', '.$szall_data['uhsz'].", ";
	        $comment .= 'Átvétel: '. $this->getSzallitasiModeData($szallitasiModID,'nev').", ";
	        $comment .= 'Fizetés: '. $this->getFizetesiModeData($fizetesiModID,'nev').", ";
	        $comment .= 'Végösszeg: '. $total_ar." Ft, ";

	        // Kedvezmény
	        if ( $orderData['kedvezmeny_szazalek'] > 0) {
	        	$comment .= "Kedvezmény: " . $orderData['kedvezmeny_szazalek'] . "%, ";
	        }

	        if( $orderData['comment'] != "" ) {
	        	$comment .= "Vásárlói megjegyzés: " . $orderData['comment'];
	        }


	        $comment = rtrim($comment,", ");

			$request_object = (object) array(
				"command" 		=> "webshopSale",
				"parameters" 	=> (object)array(
					"id" 		=> $orderData['azonosito'],
					"comment" 	=> $comment,
					//"total_net"	=> (float)$total_netto,
					"value_net" 	=> $total_items_ar_netto, // Termékek nettó összértéke
					"value_gross" 	=> $total_items_ar_brutto, // Termékek bruttó összértéke
					"total_gross" 	=> $total_ar, // Fizetendő bruttó ár, minden levonva és hozzáadva
			        //"total_vat"	=> 0,
			        "total_amount" => $total_amount,
			        "buyer" 	=> $buyer,
			        "items" 	=> $items
				)
			);


			$saleRequest = (new Request)->post(
				CLORADE_API_IF,
				$request_object,
				"json" )
			->setJSONPrefix( 'json=' )
			->setPort( 999 )
			->send();

			$saleRequestResult = $saleRequest->getResult();

			$this->logWebshopsaleReport( $orderData['azonosito'], $saleRequestResult );
		/* */
		}


		//  Teljesített megrendelés
		if( $updateData['allapot'] == $this->settings['flagkey_orderstatus_done'] )
		{
			/* * /
			// Forgalom bejegyzése
			$bevetel 	= $this->calcIncomeFromBoughtItems($orderID);
			$kiadas 	= $this->calcOutgoFromBoughtItems($orderID);

			if($bevetel > 0){
				$re = $this->traffic->add(array(
					'type' 		=> Traffic::ADDTYPE_BUY,
					'item_id' 	=> $orderID,
					'bevetel' 	=> $bevetel
				));

				$re = $this->traffic->add(array(
					'type' 		=> Traffic::ADDTYPE_BUY_OUTGO,
					'item_id' 	=> $orderID,
					'kiadas' 	=> $kiadas
				));
			}
			/* */

			// Partner ajánlónak egyenleg jóváírás
			// KIKAPCSOLVA IDEIGLENESEN, MERT NINCS RÁ SZÜKSÉG
			/* * /
			if ( $orderData['referer_code'] )
			{
				if( $this->db->query("SELECT 1 FROM shop_partner_balance_log WHERE felh_id = ".$orderData['referer']->getPartnerID()." and order_id = ".$orderData[ID].";")->rowCount() == 0 )
				{
					// Logoloás
					$this->db->insert(
						'shop_partner_balance_log',
						array(
							'felh_id'	=> $orderData['referer']->getPartnerID(),
							'order_id' 	=> $orderData[ID],
							'price' 	=> $orderData[kedvezmeny]
						)
					);

					// Jóváírás
					$this->db->query("UPDATE felhasznalok SET cash = cash + ".$orderData['kedvezmeny']. " WHERE ID = ".$orderData['referer']->getPartnerID().";");

					// Log cash
					$this->db->insert(
						'cash_log',
						array(
							'felh_id' 		=> $orderData['referer']->getPartnerID(),
							'referer' 		=> 'Ajánló partnerkód vásárlás',
							'referer_id' 	=> $orderData[ID],
							'cash' 			=> $orderData[kedvezmeny],
							'direction' 	=> 'in'
						)
					);

					// Értesítés
					$mail = new Mailer(
						$this->settings['page_title'],
						SMTP_USER,
						$this->settings['mail_sender_mode']
					);

					$mail->add( $orderData['referer']->getPartnerEmail() );

					$arg = array(
						'settings' 		=> $this->settings,
						'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!',
						'name' 			=> $orderData['referer']->getPartnerName(false),
						'cash' 			=> $orderData[kedvezmeny]
					);

					$mail->setSubject( 'Egyenleg jóváírás partnerkód vásárlás után: '.$orderData[kedvezmeny].' Ft' );
					$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'user_bill_refererbuyingorder', $arg ) );
					$re = $mail->sendMail();
				}

			}
			/* */
		}


		return $changedData;
	}

	private function logWebshopsaleReport( $orderid, $json )
	{
		$result = json_decode( $json, true );

		$ins 					= array();
		$ins['megrendeles'] 	= $orderid;
		$ins['allapot']		 	= $result['parameters']['success'];
		$ins['vasarlas_idopont']= $result['parameters']['time'];
		$ins['hibauzenet'] 		= $result['parameters']['errorMsg'];
		$ins['json'] 			= $json;

		$this->db->insert(
			'webshopSale_report',
			$ins
		);
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

	public function productCategoryConnectByHash( $category_hash, $productID )
	{
		if( $category_hash == '' || !$category_hash ) return false;

		$categories = explode( ",", $category_hash );

		if( !$categories ) return false;

		// Reset
		$this->db->update(
			'shop_termek_in_kategoria',
			array(
				'connected' => 0
			),
			"termekID = ".$productID
		);

		$already_in = array();
		$already_in_qry = $this->db->query( "
			SELECT 			GROUP_CONCAT(kat.hashkey) as cat_hash,
							GROUP_CONCAT(kat.ID) as cat_ids
			FROM 			`shop_termek_in_kategoria` as t
			LEFT OUTER JOIN shop_termek_kategoriak as kat ON kat.ID = t.kategoria_id
			WHERE 			t.termekID = $productID;
		" )->fetch(\PDO::FETCH_ASSOC);


		if( $already_in_qry['cat_hash'] != '' ) {
			$i = 0;
			$cat_ids = explode( ",", $already_in_qry['cat_ids'] );
			foreach ( explode( ",", $already_in_qry['cat_hash'] ) as $d ) {
				$already_in[] = $d;
				$i++;
			}
		}

		$i = 0;
		foreach ($categories as $cat ) {
			$cat_id = $this->db->query("SELECT ID FROM shop_termek_kategoriak WHERE hashkey = '$cat';")->fetchColumn();

			//echo "SELECT ID FROM shop_termek_kategoriak WHERE hashkey = '$cat'; <br>";

			$hashkey = MD5($cat_id.$productID);

			if( in_array( $cat, $already_in ) ) {
				//echo 'UPDATE: hashkey = '.$hashkey.'<br>';
				$this->db->update(
					'shop_termek_in_kategoria',
					array(
						'connected' => 1
					),
					"hashkey = '{$hashkey}'"
				);
			} else {
				//echo 'INSERT: hashkey = '.$hashkey.' termekID = '.$productID.' kategoria_id = '.$cat_id.'<br>';
				if( $cat_id == 0 || !$cat_id || $cat_id == '' ) continue;

				$check = $this->db->query("SELECT ID FROM shop_termek_in_kategoria WHERE hashkey = '$hashkey';");

				if( $check->rowCount() != 0) continue;

				$this->db->insert(
					'shop_termek_in_kategoria',
					array(
						'hashkey' 		=> $hashkey,
						'termekID' 		=> $productID,
						'kategoria_id' 	=> $cat_id
					)
				);
			}
			$i++;
		}

		// Remove dis-connected
		$this->db->query( "DELETE FROM shop_termek_in_kategoria WHERE connected = 0;" );
	}

	public function importProducts( $dataset, $arg = array() )
	{
		$mode = $arg['mode'];

		if( !$dataset ) {
			throw new \Exception( "Művelet nem lett végrehajtva. Nincs forrás a feldolgozáshoz!" );
		}

		$ins_head = false;
		$ins_dataset = array();
		$new_items = array();

		// Aktuális Autoincrement index
		$ai_index = $this->db->query("
			SELECT 		AUTO_INCREMENT
			FROM 		information_schema.tables
			WHERE table_name = 'shop_termekek';")->fetchColumn();

		foreach ( $dataset['list'] as $list ) {


			if ( $mode == 'create' && $list['status']['exists'] != '0' ) {
				continue;
			}

			if ( $mode == 'update' && $list['status']['need_update'] != '1' ) {
				continue;
			}

			$update = ($list['status']['need_update'] == 1 ) ? true : false;

			if ( !$update ) {
				$datarow = array();
				if( !$ins_head ) {
					$headrow = array();
				}

				// Alapértelmezett márka
				$list['data']['marka'] = $this->settings['alapertelmezett_marka'];
				// Alapértelmezett termék állapot
				$list['data']['keszletID'] = $this->settings['alapertelmezett_termek_allapot'];
				// Alapértelmezett szállítási idő
				$list['data']['szallitasID'] = $this->settings['alapertelmezett_termek_szallitas'];

				$list['data']['lathato'] = 0;

				// Meghatározott ID megadása
				$headrow[] = 'ID';
				$datarow[] = $ai_index;

				$stepindex = 0;
				foreach ( $list['data'] as $key => $value ) {
					$stepindex++;
					if( !$ins_head ) {
						$headrow[] = $key;

						if( !$kategoria_hashkey_index && $key == 'kategoria_hashkeys' ) {
							$kategoria_hashkey_index = $stepindex;
						}
					}

					if( $key == 'raktar_supplierid' ) {
						$value = strtoupper( $value );
					}

					$datarow[] = $value;
				}

				if( !$ins_head ) {
					$ins_head 		= $headrow;
				}

				$ins_dataset[] 	= $datarow;

				// Összegyűjtjük az újonnan létrehozandó termékek adatait, amik kellenek a kategória becsatoláshoz
				// többnyire a termék ID-ja miatt kell, mivel az még nem létezik az adatbázisban
				$new_items[] = array(
					'ID' => $ai_index,
					'kategoria_hashkeys' => $datarow[$kategoria_hashkey_index]
				);

				// AI növelése
				$ai_index++;
			} else {
				$upd_rows = '';
				foreach ( $list['status']['update_rows'] as $ur ) {
					$value = $list['data'][$ur];

					if( isset($list['status']['discount_set']) ){
						if( $ur == 'brutto_ar' ) continue;
						if( $ur == 'netto_ar' ) continue;
					}

					$upd_rows .= $ur." = '".$value."'" .', ';
				}
				$upd_rows = rtrim($upd_rows, ', ');

				if( isset($list['status']['discount_set']) || isset($list['status']['discount_clear']) ) {
					if( isset($list['status']['discount_set']) ) {
						$upd_rows .= ", akcios = 1, akcios_netto_ar = {$list['data']['netto_ar']}, akcios_brutto_ar = {$list['data']['brutto_ar']} ";
					} else if( isset($list['status']['discount_clear']) ) {
						$upd_rows .= ", akcios = 0, akcios_netto_ar = NULL, akcios_brutto_ar = NULL ";
					}
				}

				$upd_qry = "UPDATE shop_termekek SET $upd_rows WHERE ID = ".$list['status']['exists_db_id'].";";
				$this->db->query( $upd_qry );

				// Kategóriákba csatolás hashkey alapján
				$this->productCategoryConnectByHash( $list['data']['kategoria_hashkeys'], $list['status']['exists_db_id'] );
			}
		}

		if ( !$update ) {

			// Új termékek beszúrása
			$qry_str =	$this->db->multi_insert(
				'shop_termekek',
				$ins_head,
				$ins_dataset,
				array( 'debug' => false )
			);

			// Új termékek kategóriákba csatolása
			if( count( $new_items) > 0 ) {
				foreach ( $new_items as $d ) {
					// Kategóriákba csatolás hashkey alapján
					$this->productCategoryConnectByHash( $d['kategoria_hashkeys'], $d['ID'] );
				}
			}
		}


		// Képek automatikus bekapcsolása
		if( !$arg['dont_connect_images'] ){
			$this->autoProductImageConnecter( $arg );
		}

	}

	public function getAPILog( $arg = array() )
	{
		$log = array();

		$q = "
		SELECT 		l.*
		FROM 		api_request as l
		ORDER BY 	l.idopont DESC;
		";

		$arg['multi'] = 1;
		extract( $this->db->q( $q, $arg ));

		$log = $ret;

		return $log;
	}

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

	public function delete()
	{
		$this->db->query(sprintf("DELETE FROM admin WHERE ID = %d",$this->admin_id));
	}

	public function saveSettings($settings)
	{
		foreach ($settings as $k => $v ) {
			$this->db->query( sprintf("UPDATE beallitasok SET bErtek = '%s' WHERE bKulcs = '%s'; ", $v, $k));
		}
	}

	private function getAdmin()
	{
		$this->admin = $this->db->query(sprintf("SELECT * FROM admin WHERE ID = %d", $this->admin_id))->fetch(\PDO::FETCH_ASSOC);
	}

	public function getAdminList()
	{
		return $this->db->query("SELECT * FROM admin")->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getAllUploadedProductImages( $arg = array() )
	{
		$image_path = ( $arg['image_path'] ) ? $arg['image_path'] : 'src/products/all';

		$images = new FileLister( $image_path );
		$total_list = $images->getFolderItems( array(
			'allowedExtension' => 'jpg|png|gif',
			'hideThumbnailImg' => true
		));

		$temp = array();
		$log_img = array();
		foreach ( $total_list as $img ) {
			$log_img[] = array( $img[name], $img[src_path], $img[time] );
			$renew = false;

			$renew_check = $this->db->query("SELECT 1 FROM shop_termek_kepek_valtozasok WHERE cim = '{$img[name]}' and {$img[time]} > utoljara_modositva;");

			if( $renew_check->rowCount() != 0 ){
				$renew = true;

			}

			$img[renewable] = $renew;
			$temp[] = $img;
		}

		unset( $total_list );

		if( count($log_img) > 0 ) {
			$this->db->multi_insert(
				'shop_termek_kepek_valtozasok',
				array( 'cim', 'eleresi_ut', 'utoljara_modositva' ),
				$log_img,
				array( 'duplicate_keys' => array( 'cim' ) )
			);
		}

		unset($log_img);

		return $temp;
	}

	public function getSavedProductImages()
	{
		$images = array();

		$q = "
		SELECT 		k.*, t.ID as tid
		FROM 		shop_termek_kepek as k
		LEFT OUTER JOIN shop_termekek as t ON t.ID = k.termekID
		WHERE t.ID IS NOT NULL
		GROUP BY 	k.kep;
		";

		$arg['multi'] = 1;
		extract( $this->db->q( $q, $arg ));

		// Használatlan képek törlése az adatbázisból
		foreach ( $data as $d ) {
			if( is_null($d['tid']) ) {
				$this->db->query("DELETE FROM shop_termek_kepek WHERE ID = {$d[ID]};");
			}
		}

		$images = $data;

		return $images;
	}

	public function autoProductImageConnecter( $arg = array() )
	{
		//  38 mb
		// Összes feltöltött kép betöltése
		$images = null;
		$images = $this->getAllUploadedProductImages( $arg );

		// Képek ellenőrzése
		$must_import = null;
		$must_import = $this->prepareProductsImagesForImport( $images );

		//  27 mb
		//echo 'IMG - '.round(memory_get_usage(true)/1048576,2);


		// Frissített képek bélyegképének újragenerálása
		foreach ($images as $img) {
			if( $img['renewable'] ) {
				/* * /
				echo '<pre>';
				print_r( $img);
				echo '</pre>';
				/* */

				/* */
				$thb75_img = str_replace($img[name],'thb75_'.$img[name],$img[src_path]);
				$thb150_img = str_replace($img[name],'thb150_'.$img[name],$img[src_path]);

				if( file_exists( $thb150_img ) ) {
					unlink($thb150_img);
					Image::makeThumbnail( $img[src_path], $img[short_path], str_replace( '.'.$img[extension], '', $img[name] ), 'thb150_', 150, '.'.$img[extension]);
				}

				if( file_exists( $thb75_img ) ) {
					unlink($thb75_img);
					Image::makeThumbnail( $img[src_path], $img[short_path], str_replace( '.'.$img[extension], '', $img[name] ), 'thb75_', 75, '.'.$img[extension]);
				}
				/* */
			}
			/* */
			$this->db->update(
				'shop_termek_kepek_valtozasok',
				array(
					'utoljara_modositva' => $img[time]
				),
				"cim = '{$img[name]}'"
			);
			/* */
		}

		/* * /
		echo '<pre>';
		print_r( $must_import);
		echo '</pre>';
		return false;
		/* */

		$db_head = array( 'hashkey', 'termekID', 'sorrend', 'kep' );
		$db_body = array();

		$unique_set = array();
		foreach ( $must_import as $i ) {

			foreach ( $i[ids] as $id ) {
				// MD5( $supplierid_$colorcode_$userid_$nev )
				$hashkey = md5( $i[supplierid].'_'.$i[color_code].'_'.$id.'_'.$i[nev]);
				if( $this->db->query("SELECT 1 FROM shop_termek_kepek WHERE hashkey = '$hashkey';")->rowCount() !== 0 )  continue;

				$db_body[] = array( $hashkey, $id, $i[index], str_replace( '../../admin/', '', $i[src]));

				/*$unique_set[ $i[supplierid].'_'.$i[color_code] ] = array(
					'supplierid' => $i[supplierid],
					'color_code' => $i[color_code],
					'id' => $id
				);*/

				/**
				* Profilkép beállítása
				* */
				if( $i[is_profil] == '1' && true ) {
					$this->db->update(
						'shop_termekek',
						array(
							'profil_kep' => str_replace( '../../admin/', '', $i[src])
						),
						"ID = $id"
					);
				}

				/**
				* Thumbnailok létrehozása
				* */
				// 150x150
				if( !file_exists(  $i[path] . 'thb150_'.$i[nev] ) ) {
					Image::makeThumbnail( $i[src], $i[path], str_replace( '.'.$i[extension], '', $i[nev] ), 'thb150_', 150, '.'.$i[extension]);
				}
				// 75x75
				if( !file_exists(  $i[path] . 'thb75_'.$i[nev] ) ) {
					Image::makeThumbnail( $i[src], $i[path], str_replace( '.'.$i[extension], '', $i[nev] ), 'thb75_', 75, '.'.$i[extension]);
				}
			}
		}

		//  39 mb
		unset( $must_import );

		/**
		 * Főtermék beállítása
		 * */
		/*
		foreach ( $unique_set as $set ) {
			// Összes főtermék 0-ra állítása
			// és láthatóság engedélyezése
			$this->db->update(
				'shop_termekek',
				array(
					'lathato' => 1,
					'fotermek' => 0
				),
				"raktar_supplierid = '{$set[supplierid]}' and szin_kod = '{$set[color_code]}'"
			);

			// Főtermék beállítása
			$this->db->update(
				'shop_termekek',
				array(
					'fotermek' => 1
				),
				"ID = '{$set[id]}'"
			);
		}
		/* */



		/* */
		$ins = $this->db->multi_insert(
			'shop_termek_kepek',
			$db_head,
			$db_body,
			array(
				'duplicate_keys' => array( 'hashkey', 'sorrend', 'kep' ),
				'debug' => false
			)
		);
		//echo $ins;

		unset( $db_head );
		unset( $db_body );
		/*	*/

	}

	public function prepareProductsImagesForImport( $images )
	{
		$must_import = array();

		foreach ( $images as $upimage ) {
			$import_ids = null;
			$x = explode( '_', str_replace( '.'.$upimage[extension], '', $upimage[name]));
			$import_ids = $this->getProductsIdForImageImport( $x[0], $x[1], $upimage[name] );

			if( $import_ids ) {

				$img_index = ( $x[2] != '' ) ? $x[2] : 1;
				$is_profil = ( $x[2] == '') ? 1 : (( $x[2] == '1') ? 1 : 0);

				$must_import[] = array(
					'nev' => $upimage[name],
					'src' => $upimage[src_path],
					'path' => $upimage[short_path],
					'extension' => $upimage[extension],
					'color_code' => $x[1],
					'index' => $img_index,
					'is_profil' => $is_profil,
					'supplierid' => $x[0],
					'ids' => $import_ids
				);
			}

			unset($import_ids);
		}

		return $must_import;
	}

	public function getProductsIdForImageImport( $supplierid = false, $color_code = false, $name)
	{
		if( !$supplierid ) return false;

		$q = "
		SELECT 		t.ID,
					MD5( CONCAT( t.raktar_supplierid,  '_', t.szin_kod,  '_', t.ID, '_', '{$name}' ) ) as md5hash
		FROM 		shop_termekek as t
		WHERE 		t.raktar_supplierid = '$supplierid' ";

		if( $color_code ) {
			$q .= " and t.szin_kod = $color_code ";
		}

		$q .= " and (SELECT ID FROM shop_termek_kepek WHERE hashkey = MD5( CONCAT( t.raktar_supplierid,  '_', t.szin_kod,  '_', t.ID, '_', '{$name}' ) ) ) IS NULL";

		$arg['multi'] = 1;
		extract( $this->db->q( $q, $arg ));

		$ids = array();
		foreach ( $data as $d ) {
			$ids[] = $d[ID];
		}

		return $ids;
	}

	public function replyToMessage( $msgData, $post)
	{
		extract($post);

		if($replyMsg == ''){
			throw new \Exception('Válaszüzenet megadása kötelező!');
		}

		if($msgData[felado_email] == ''){
			throw new \Exception('Nincs válaszcím, amire küldhetjük a válaszüzenetet!');
		}

		// Válasz a feladónak
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $msgData[felado_email] );
		$arg = array(
			'settings'=> $this->settings,
			'infoMsg' => 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!',
			'form' => $post,
			'msgData' => $msgData
		);
		$mail->setSubject( 'Válasz: '.$msgData['uzenet_targy'] );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'admin_contact_replymsg', $arg ) );
		$re = $mail->sendMail();

		if( true ){
			$this->db->update(
				"uzenetek",
				array(
					"valaszolva" 	=> NOW,
					"valasz_uzenet" => $replyMsg
				),
				"ID = ".$msgData[ID]
			);
		}
	}

	/*===============================
	=            GETTERS            =
	===============================*/

	public function getUsername()
	{
		return $this->admin['user'];
	}

	public function getId()
	{
		return $this->admin['ID'];
	}

	public function getLastLogindate()
	{
		return $this->admin['utoljara_belepett'];
	}

	public function getStatus()
	{
		return ($this->admin['engedelyezve'] == 1 ? true : false);
	}

	public function getPrivIndex()
	{
		return (int)$this->admin['jog'];
	}

	/*-----  End of GETTERS  ------*/

	public function __destruct()
	{
		$this->db = null;
	}


}
?>
