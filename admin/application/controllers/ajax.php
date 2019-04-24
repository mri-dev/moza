<?
use PortalManager\Template;
use PortalManager\Motivumok;
use PortalManager\Categories;
use PortalManager\Projects;
use PortalManager\Orders;
use PortalManager\Colors;

class ajax extends Controller
{
		function __construct(){
			parent::__construct();
		}

		function post(){
			extract($_POST);

			$ret = array(
				'success' => 0,
				'msg' => false
			);

			switch($type)
			{
				case 'Moza':
					$ret['data'] = array();
					$ret['pass'] = $_POST;

					switch ( $mode )
					{
						case 'Order':
							$orders = new Orders( array('db' => $this->db) );
							try {
								$msg = $orders->create( $_POST['orderer'], $_POST['motifs'], $_POST['qtyconfig'], $_POST['gridsizes'], $_POST['gridconfig'], $_POST['project'] );
								$ret['success'] = 1;
								$ret['msg'] = $msg;
							} catch (\Exception $e) {
								$this->escape($e->getMessage(), $ret);
							}
						break;
						case 'getProjects':
							$projects = new Projects( array('db' => $this->db) );
							try {
								$list = $projects->getAll(array('email' => $email));
								$ret['data'] = $list;
								$ret['success'] = 1;
							} catch (\Exception $e) {
								$err = $this->escape($e->getMessage(), $ret);
								$ret[errorCode] = $e->getCode();
							}
						break;
						case 'saveProject':
							$projects = new Projects( array('db' => $this->db) );
							try {
								$projects->add( $form, $used_motifs, $used_colors, $grid );
								$ret['success'] = 1;
								$ret['msg'] = __('Sikeresen mentette a(z) "'.$form['name'].'" projektjét ide: '.$form['email']);
							} catch (\Exception $e) {
								$err = $this->escape($e->getMessage(), $ret);
								$ret[errorCode] = $e->getCode();
							}
						break;
						case 'saveStyleConfig':
							$m = new Motivumok(array('db' => $this->db));
							/**/
							try {
								$newid = $m->addStyleConfig((int)$id, $name, $motivum);
								$ret['success'] = 1;
								$ret['newid'] = $newid;
								$ret['msg'] = 'Új saját minta mentésre került.';
							} catch (\Exception $e) {
								$err = $this->escape($e->getMessage(), $ret);
								$ret[errorCode] = $e->getCode();
							}
							/**/
						break;
						case 'getStyleConfigs':
							$m = new Motivumok(array('db' => $this->db));
							$mintak = $m->getAll(array(
								'admin' => true
							));
							$data = array();
							foreach ( (array)$mintak as $minta ) {
								if ($minta['kat_hashkey'] == "OWN") {
									$data[] = $minta;
								}
							}

							$ret['data'] = $data;
						break;
						case 'saveMotivumConfig':
							$m = new Motivumok(array('db' => $this->db));
							if ($motivum == 'false') {
								$motivum = false;
							}
							/**/
							try {
								$newid = $m->saveConfigMotivum((int)$configid, $motivum);
								$ret['success'] = 1;
								$ret['msg'] = 'Egyedi színezett motívum adatai sikeresen mentve lettek.';
							} catch (\Exception $e) {
								$err = $this->escape($e->getMessage(), $ret);
								$ret[errorCode] = $e->getCode();
							}
							/**/
						break;
						case 'addMotivum':
							$m = new Motivumok(array('db' => $this->db));
							if ($motivum == 'false') {
								$motivum = false;
							}
							/**/
							try {
								$newid = $m->add((int)$id, $motivum);
								$ret['success'] = 1;
								$ret['newid'] = $newid;
								$ret['msg'] = 'Motívum adatai sikeresen rögzítve lettek.';
							} catch (\Exception $e) {
								$err = $this->escape($e->getMessage(), $ret);
								$ret[errorCode] = $e->getCode();
							}
							/**/
						break;
						case 'getMotivumok':
							$m = new Motivumok(array('db' => $this->db));
							$arg = array();
							if ($admin == 1) {
								$arg['admin'] = true;
							}
							if ($configid != 0) {
								$arg['configid'] = $configid;
							}
							if ($hideown == 1) {
								$arg['hideown'] = true;
							}
							if (isset($getid) && $getid != 0) {
								$arg['id_set'] = (array)$getid;
								$list = $m->getAll($arg);
							} else if(!isset($getid)){
								$list = $m->getAll($arg);
							}
							$ret['data'] = $list;
						break;
						case 'getSettings':
							$settings = array();
							$kategoriak = array();
							$szinek = array();

							// Kategóriák
							$c = new Categories(array('db' => $this->db));
							$cats = $c->getTree();

							while( $cats->walk() )
							{
								$cat = $cats->the_cat();
								$cat['ID'] = (int)$cat['ID'];
								$kategoriak[] = $cat;
							}
							$settings['kategoria_lista'] = $kategoriak;

							// Színek
							$colors = new Colors(array('db' => $this->db));
							$colors = $colors->getTree();
							while( $colors->walk() )
							{
								$cat = $colors->the_cat();
								$szinek[] = $cat;
							}
							$settings['colors'] = $szinek;

							$ret['data'] = $settings;
						break;
					}
					echo json_encode($ret);
					return;
				break;
			}
		}

		private function setSuccess($msg, &$ret){
			$ret[msg] 		= $msg;
			$ret[success] 	= 1;
			return true;
		}
		private function escape($msg, &$ret){
			$ret[msg] 		= $msg;
			$ret[success] 	= 0;
			return true;
		}

		function get(){
			extract($_POST);

			$sub_page = '';

			switch($type){
				/**
				* ANGULAR ACTIONS
				**/
				case 'Sample':
					$key = $_POST['key'];

					$re = array(
						'error' => 0,
						'msg' => null,
						'data' 	=> array()
					);
					$re['pass'] = $_POST;


					echo json_encode( $re );
				break;
				/* END: ANGULAR ACTIONS */
			}

			$sub_page = ( $sub_page != '' ) ? '_'.$sub_page : '';
			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$type.$sub_page, true);
		}

		function traffic(){
			extract($_POST);
			switch($action){
				case 'add':
					try{
						$options = $_POST;
						$re = $this->traffic->add($options);
						echo '<span style="color:green;">'.$re.'</span>';
					}catch(Exception $e){
						echo '<span style="color:red;">Hiba történt: '.$e->getMessage().'</span>';
					}
				break;
			}
		}

		function __destruct(){
		}
	}

?>
