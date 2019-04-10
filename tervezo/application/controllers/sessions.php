<?php
use PortalManager\Orders;

class sessions extends Controller{
		function __construct(){
			parent::__construct();
			parent::$pageTitle = __('Ajánlatkérés adatlap');

			$this->out('sessionpage', $this->gets[1]);
			$this->out('bodyclass', 'sessionpage');

			$orders = new Orders(array('db' => $this->db));
			$order = $orders->getAll(array(
				'session' => $this->view->sessionpage
			));
			$this->out( 'order', $order[0] );

			if (isset($_GET['av']) && $_GET['av'] == 1) {
				$orders->logAdminVisit($this->view->order['ID']);
			}

			$emailorders = $orders->getEmailOrders($this->view->order['orderer_email']);
			$this->out( 'allorders', $emailorders );

			// SEO Információk
			$SEO = null;
			// Site info
			$SEO .= $this->view->addMeta('description', 'MOZA Cementlap tervező program.');
			$SEO .= $this->view->addMeta('keywords', 'tervező, egyedi, cementlap, csempe');
			$SEO .= $this->view->addMeta('revisit-after','3 days');

			// FB info
			$SEO .= $this->view->addOG('title', $this->view->settings['page_title'] . ' - '.$this->view->settings['page_description']);
			$SEO .= $this->view->addOG('description', $this->view->settings['about_us']);
			$SEO .= $this->view->addOG('type','website');
			$SEO .= $this->view->addOG('url', CURRENT_URI );
			$SEO .= $this->view->addOG('image', $this->view->settings['domain'].'/admin'.$this->view->settings['logo']);
			$SEO .= $this->view->addOG('site_name', $this->view->settings['page_title']);
			$this->view->SEOSERVICE = $SEO;
		}

		function __destruct(){
			// RENDER OUTPUT
				parent::bodyHead();					# HEADER
				$this->view->render(__CLASS__);		# CONTENT
				parent::__destruct();				# FOOTER
		}
	}

?>
