<?php
use PortalManager\Motivumok;
use PortalManager\Categories;
use PortalManager\Projects;
use PortalManager\Orders;
use PortalManager\Colors;

class ajax extends Controller{
		function __construct()
		{
			header("Access-Control-Allow-Origin: *");
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
								$err = $this->escape($e->getMessage());
								$ret[errorCode] = $e->getCode();
							}
						break;
						case 'getMotivumok':
							$m = new Motivumok(array('db' => $this->db));
							$list = $m->getAll();
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
				case 'user':
					switch($mode){
						case 'add':
							$err = false;
							try{
								$re = $this->User->add($_POST);
							}catch(Exception $e){
								$err = $this->escape($e->getMessage(),$ret);
								$ret[errorCode] = $e->getCode();
							}

							if(!$err)
							$this->setSuccess('Regisztráció sikeres! Kellemes vásárlást kívánunk!',$ret);

							echo json_encode($ret);
							return;
						break;
						case 'login':
							$err = false;
							try{
								$re = $this->User->login($_POST[data]);

								if( $re && $re[remember]){
									setcookie('ajx_login_usr', $re[email], time() + 60*60*24*3, '/' );
									setcookie('ajx_login_pw', $re[pw], time() + 60*60*24*3, '/' );
								}else{
									setcookie('ajx_login_usr', null, time() - 3600, '/' );
									setcookie('ajx_login_pw', null , time() -3600, '/' );
								}

							}catch(Exception $e){
								$err = $this->escape($e->getMessage(),$ret);
								$ret[errorCode] = $e->getCode();
							}

							if(!$err)
							$this->setSuccess('Sikeresen bejelentkezett!',$ret);

							echo json_encode($ret);
							return;
						break;
						case 'resetPassword':
							$err = false;
							try{
								$re = $this->User->resetPassword($_POST[data]);
							}catch(Exception $e){
								$err = $this->escape($e->getMessage(),$ret);
								$ret[errorCode] = $e->getCode();
							}

							if(!$err)
							$this->setSuccess('Új jelszó sikeresen generálva!',$ret);

							echo json_encode($ret);
							return;
						break;
					}
				break;
				case 'modalMessage':
					$err = false;
					$ret['pass'] = $_POST;
					$datas = $_POST['datas'];

					switch ($_POST['modalby'])
					{
						// Ingyenes visszahívás
						case 'recall':
							try {
								$remsg = $this->shop->requestReCall( $datas );
							} catch (\Exception $e) {
								$err = $this->escape( $e->getMessage(), $ret );
							}
						break;
						// Ingyenes ajánlatkérés
						case 'ajanlat':
							try {
								$remsg = $this->shop->requestOffer( $datas );
							} catch (\Exception $e) {
								$err = $this->escape( $e->getMessage(), $ret );
							}
						break;
						// Termék ár kérés
						case 'requesttermprice':
							try {
								$remsg = $this->shop->requestTermprice( $datas );
							} catch (\Exception $e) {
								$err = $this->escape( $e->getMessage(), $ret );
							}
						break;
					}

					if(!$err) $this->setSuccess( $remsg ,$ret );

				break;

			}
			echo json_encode($ret);
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

		function update () {

			switch ( $this->view->gets[2] ) {
				// Pick Pack Pontok listájának frissítése
				// {DOMAIN}/ajax/update/updatePickPackPont
				/*
				case 'updatePickPackPont':
					$this->model->openLib('PickPackPont',array(
						'database' => $this->model->db,
						'update' => true
					));
				break;
				*/
			}
		}

		function get(){
			extract($_POST);

			switch($type){
				case 'settings':
					$_POST['key'] = ($_POST['key'] != '') ? (array)$_POST['key'] : array();

					if ( empty($_POST['key']) ) {
						$ret['data'] = $this->view->settings;
					} else {
						$settings = array();

						foreach ( $_POST['key'] as $key ) {
							$settings[$key] = $this->view->settings[$key];
						}

						$ret['data'] = $settings;
					}

					$ret['pass'] = $_POST;
					echo json_encode($ret);
				break;
			}

			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$type, true);
		}

		function box(){
			extract($_POST);

			switch($type){
				case 'recall':
					$this->view->t = $this->shop->getTermekAdat($tid);
				break;
				case 'askForTermek':
					$this->view->t = $this->shop->getTermekAdat($tid);
				break;
				case 'map':
					$shop = new CasadaShop( (int)$tid, array(
						'db' => $this->db
					));

					$this->out('shop',$shop);
				break;
			}

			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$type, true);
		}

		function template(){
			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$this->gets[2], true);
		}

		function __destruct(){
		}
	}

?>
