<?php

class motivumconfig extends Controller{
		function __construct(){
			parent::__construct();
			parent::$pageTitle = 'Saját motívum beállítások';

    	$this->view->adm = $this->AdminUser;
			$this->view->adm->logged = $this->AdminUser->isLogged();

			if($this->gets[1] == 'exit'){
				$this->AdminUser->logout();
			}

			$motif = $this->db->squery("SELECT
				m.ID
			FROM motivum_styles as ms
			LEFT OUTER JOIN motivumok as m ON m.mintakod = ms.motivumID
			WHERE 1=1 and
			 	ms.ID = :id
			", array('id' => $this->gets[2]));

			if ($motif->rowCount() != 0) {
				$motivum = $motif->fetch(\PDO::FETCH_ASSOC);
				$this->out( 'motivum_id', $motivum['ID'] );
			}

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

		function config()
		{

		}

		function __destruct(){
			// RENDER OUTPUT
				parent::bodyHead();					# HEADER
				$this->view->render(__CLASS__);		# CONTENT
				parent::__destruct();				# FOOTER
		}
	}

?>
