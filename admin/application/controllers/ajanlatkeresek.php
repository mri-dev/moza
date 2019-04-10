<?php
use PortalManager\Orders;

class ajanlatkeresek extends Controller{
		function __construct(){
			parent::__construct();
			parent::$pageTitle = 'Ajánlatkérések';

    	$this->view->adm = $this->AdminUser;
			$this->view->adm->logged = $this->AdminUser->isLogged();

			if($this->gets[1] == 'exit'){
				$this->AdminUser->logout();
			}

			$is_archivalt_list = ($this->gets[1] == 'archivalt') ? true : false;
			$this->out( 'is_archivalt_list', $is_archivalt_list );

			$orders = new Orders(array('db' => $this->db));

			// State - Welldone
			if (isset($_POST['welldoneSession']))
			{
				try {
					$orders->setWelldone( $this->gets[2], true );
					Helper::reload();
				} catch ( Exception $e ) {
					$this->view->err = true;
					$this->view->bmsg = Helper::makeAlertMsg('pError', $e->getMessage());
				}
			}

			// State - Archivalt
			if (isset($_POST['archiveSession']))
			{
				try {
					$orders->setArchived( $this->gets[2], true );
					Helper::reload();
				} catch ( Exception $e ) {
					$this->view->err = true;
					$this->view->bmsg = Helper::makeAlertMsg('pError', $e->getMessage());
				}
			}

			if (isset($_POST['saveSession']))
			{
				try {
					$orders->save( $this->gets[2], $_POST );
				} catch ( Exception $e ) {
					$this->view->err = true;
					$this->view->bmsg = Helper::makeAlertMsg('pError', $e->getMessage());
				}
			}

			$list_param = array();
			if ($is_archivalt_list) {
				$list_param['show_archive'] = true;
			}
			$list_param['filters'] = $_GET;
			$order_list = $orders->getAll($list_param);
			$this->out( 'orders', $order_list );

			// SEO Információk
			$SEO = null;
			// Site info
			$SEO .= $this->view->addMeta('description','');
			$SEO .= $this->view->addMeta('keywords','');
			$SEO .= $this->view->addMeta('revisit-after','3 days');

			// FB info
			$SEO .= $this->view->addOG('type','website');
			$SEO .= $this->view->addOG('url',DOMAIN);
			$SEO .= $this->view->addOG('image',DOMAIN.substr(IMG,1).'noimg.jpg');
			$SEO .= $this->view->addOG('site_name',TITLE);

			$this->view->SEOSERVICE = $SEO;
		}

		function edit()
		{
			$orders = new Orders(array('db' => $this->db));
			$order = $orders->getAll(array(
				'ID' => $this->gets[2]
			));
			$this->out( 'order', $order[0] );
		}

		function __destruct(){
			// RENDER OUTPUT
				parent::bodyHead();					# HEADER
				$this->view->render(__CLASS__);		# CONTENT
				parent::__destruct();				# FOOTER
		}
	}

?>
