<?
use PortalManager\Admin;
use PortalManager\Portal;

class beallitasok extends Controller {
		function __construct(){
			parent::__construct();
			parent::$pageTitle = 'Beállítások / Adminisztráció';

			$this->view->adm = $this->AdminUser;
			$this->view->adm->logged = $this->AdminUser->isLogged();

			// Load Admin
			$admin_id = false;
			if ($this->view->gets[1] == 'admin_torles' || $this->view->gets[1] == 'admin_szerkesztes') {
				$admin_id = $this->view->gets[2];
			}

			$admin = new Admin($admin_id, array( 'db' => $this->db ));
			$admins = $admin->getAdminList();
			$this->out( 'admins', $admins );
			$this->out( 'admin', $admin );


			if ( ( isset($_POST['addAdmin']) || isset($_POST['saveAdmin']) || isset($_POST['delAdmin']) ) && $this->AdminUser->admin_jog != \PortalManager\Admin::SUPER_ADMIN_PRIV_INDEX ) {
				$this->view->err			= true;
				$this->view->bmsg['admin'] 	= Helper::makeAlertMsg('pError', 'Nincs jogosultsága a művelet végrehajtására! Csak <strong>Szuper Adminisztrátor</strong> joggal rendelkező fiókkal módosíthatja a beállításokat!');
			} else {
				// Admin létrehozása
				if (isset($_POST['addAdmin'])) {
					try {
						$admin->add( $_POST );
						Helper::reload();
					} catch ( Exception $e ) {
						$this->view->err			= true;
						$this->view->bmsg['admin'] 	= Helper::makeAlertMsg('pError', $e->getMessage());
					}
				}

				// Admin szerkesztése
				if ( isset($_POST['saveAdmin']) ) {
					try {
						$admin->save( $_POST );
						Helper::reload();
					} catch ( Exception $e ) {
						$this->view->err			= true;
						$this->view->bmsg['admin'] 	= Helper::makeAlertMsg('pError', $e->getMessage());
					}
				}

				// Admin törlése
				if (isset($_POST['delAdmin'])) {
					try {
						$admin->delete();
						Helper::reload( '/beallitasok/#admins' );
					} catch ( Exception $e ) {
						$this->view->err			= true;
						$this->view->bmsg['admin'] 	= Helper::makeAlertMsg('pError', $e->getMessage());
					}
				}
			}

			// Változók beállítása
			if ( ( isset($_POST['saveBasics']) ) && $this->AdminUser->admin_jog != \PortalManager\Admin::SUPER_ADMIN_PRIV_INDEX ) {
				$this->view->err			= true;
				$this->view->bmsg['basics'] = Helper::makeAlertMsg('pError', 'Nincs jogosultsága a művelet végrehajtására! Csak <strong>Szuper Adminisztrátor</strong> joggal rendelkező fiókkal módosíthatja a beállításokat!');
			} else {
				if (isset($_POST['saveBasics'])) {
					unset($_POST['saveBasics']);
					$admin->saveSettings($_POST);
					Helper::reload();

				}
			}


			// SEO Információk
			$SEO = null;
			// Site info
			$SEO .= $this->view->addMeta('description','');
			$SEO .= $this->view->addMeta('keywords','');
			$SEO .= $this->view->addMeta('revisit-after','3 days');

			// FB info
			$SEO .= $this->view->addOG('type','website');
			$SEO .= $this->view->addOG('url','');
			$SEO .= $this->view->addOG('image','');
			$SEO .= $this->view->addOG('site_name','');

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
