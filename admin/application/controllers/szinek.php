<?php
use PortalManager\Colors;
use PortalManager\Color;

class szinek extends Controller {
		function __construct(){
			parent::__construct();
			parent::$pageTitle = 'Színek / Adminisztráció';

			$this->view->adm = $this->AdminUser;
			$this->view->adm->logged = $this->AdminUser->isLogged();

      // CREATE
      ///////////////////////////////////////////////////////////////////////////////////////
      $colors = new Colors( array( 'db' => $this->db ) );

      // Új kategória
      if( isset($_POST['addColor']) )
      {
        try {
          $colors->add( $_POST );
          Helper::reload();
        } catch ( Exception $e ) {
          $this->view->err	= true;
          $this->view->bmsg 	= Helper::makeAlertMsg('pError', $e->getMessage());
        }
      }

      // Szerkesztés
      if ( $this->view->gets[1] == 'szerkeszt') {
        // Kategória adatok
        $cat_data = new Color( $this->view->gets[2],  array( 'db' => $this->db )  );
        $this->out( 'color', $cat_data );

        // Változások mentése
        if(isset($_POST['saveColor']) )
        {
          try {
            $colors->edit( $cat_data, $_POST );
            Helper::reload();
          } catch ( Exception $e ) {
            $this->view->err	= true;
            $this->view->bmsg 	= Helper::makeAlertMsg('pError', $e->getMessage());
          }
        }
      }

      // Törlés
      if ( $this->view->gets[1] == 'torles') {
        // Kategória adatok
        $cat_data = new Color( $this->view->gets[2], array( 'db' => $this->db )  );
        $this->out( 'color_d', $cat_data );

        // Kategória törlése
        if( isset($_POST['delColor']) )
        {
          try {
            $colors->delete( $cat_data );
            Helper::reload( '/szinek' );
          } catch ( Exception $e ) {
            $this->view->err	= true;
            $this->view->bmsg 	= Helper::makeAlertMsg('pError', $e->getMessage());
          }
        }
      }

      // LOAD
      ////////////////////////////////////////////////////////////////////////////////////////
      $cat_tree 	= $colors->getTree();
      // Kategoriák
      $this->out( 'colors', $cat_tree );

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


		function __destruct(){
			// RENDER OUTPUT
				parent::bodyHead();					# HEADER
				$this->view->render(__CLASS__);		# CONTENT
				parent::__destruct();				# FOOTER
		}
	}

?>
