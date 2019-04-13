<?
namespace PortalManager;

use MailManager\Mailer;
use PortalManager\Template;
/**
* class Orders
* @package PortalManager
* @version 1.0
*/
class Orders
{
  const DB_ORDERS = 'megrendelesek';
  const DB_ORDER_ITEMS = 'megrendelesek_items';

	private $db = null;
	public $tree = false;

	function __construct( $arg = array() )
	{
		$this->db = $arg[db];
  }

  public function save( $id, $post )
  {
    if ($post['order']['orderer_name'] == '') {
      throw new \Exception("Az ajánlatkérő nevét kötelező megadni!");
    }
    if ($post['order']['orderer_phone'] == '') {
      throw new \Exception("Az ajánlatkérő telefonszámát kötelező megadni!");
    }
    if ($post['order']['orderer_email'] == '') {
      throw new \Exception("Az ajánlatkérő e-mail címét kötelező megadni!");
    }
    $this->db->update(
      self::DB_ORDERS,
      array(
        'orderer_name' => trim($post['order']['orderer_name']),
        'orderer_email' => trim($post['order']['orderer_email']),
        'orderer_phone' => trim($post['order']['orderer_phone']),
        'admin_megjegyzes' => addslashes(trim($post['admin_megjegyzes']))
      ),
      sprintf("ID = %d", $id)
    );

    if ($post['items']) {
      foreach ((array)$post['items'] as $id => $v) {
        $this->db->update(
          self::DB_ORDER_ITEMS,
          array(
            'me_db' => (float)$v['me_db'],
            'me_nm' => (float)$v['me_nm']
          ),
          sprintf("ID = %d", $id)
        );
      }
    }
  }

  public function setWelldone( $id, $state )
  {
    $this->db->update(
      self::DB_ORDERS,
      array(
        'welldone' => ($state === true) ? 1 : false
      ),
      sprintf("ID = %d", $id)
    );
  }

  public function setArchived( $id, $state )
  {
    $this->db->update(
      self::DB_ORDERS,
      array(
        'archivalt' => ($state === true) ? 1 : false
      ),
      sprintf("ID = %d", $id)
    );
  }

	public function create( $orderer, $motifs, $qtyconfig, $gridsizes, $gridconfig, $project = false )
	{

    if ( !$qtyconfig || count($qtyconfig) != count($motifs) ) {
      throw new \Exception(__('Kérjük, hogy adja meg a motívumoknál a rendelendő mennyiségeket (darab vagy nm)!'), 1);
    }

    $hashkey = md5(uniqid());
		$this->db->insert(
			self::DB_ORDERS,
			array(
        'saved_project_id' => ($project && $project != '') ? $project : NULL,
        'hashkey' => $hashkey,
        'orderer_name' => trim($orderer['name']),
        'orderer_phone' => trim($orderer['phone']),
        'orderer_email' => trim($orderer['email']),
        'grid_x' => $gridsizes['x'],
        'grid_y' => $gridsizes['y'],
        'gridconfig' => json_encode($gridconfig, \JSON_UNESCAPED_UNICODE)
			)
		);

    $order_id = $this->db->lastInsertId();

    if ($motifs) {
      foreach ( (array)$motifs as $m ) {
        $me_db = (float)$qtyconfig[$m['hashid']]['db'];
        $me_nm = (float)$qtyconfig[$m['hashid']]['nm'];
        $this->db->insert(
    			self::DB_ORDER_ITEMS,
    			array(
            'rendeles_id' => $order_id,
            'hashid' => $m['hashid'],
            'minta' => $m['minta'],
            'szinek' => json_encode($m['coloring'], \JSON_UNESCAPED_UNICODE),
            'me_db' => $me_db,
            'me_nm' => $me_nm,
            'preview_code' => trim($m['imageurl']),
    			)
    		);
      }
    }

    // Admin értesítés
    $mail = new Mailer(
      $this->db->settings['page_title'],
      SMTP_USER,
      $this->db->settings['mail_sender_mode']
    );
    $mail->add( $this->db->settings['alert_email'] );
    $arg = array(
      'settings' => $this->db->settings,
      'hashkey' => $hashkey,
      'order_name' => trim($orderer['name']),
      'order_email' => trim($orderer['email']),
      'order_phone' => trim($orderer['phone']),
      'gridsizes' => $gridsizes,
      'gridconfig' => $gridconfig,
      'qtyconfig' => $qtyconfig,
      'motifs' => $motifs
    );
    $mail->setSubject( 'Értesítés: új ajánlatkérés érkezett - '.trim($orderer['name']) );
    $mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'order_new_admin', $arg ) );
    $re = $mail->sendMail();

    // Ajánlatkérő értesítés
    $mail = new Mailer(
      $this->db->settings['page_title'],
      SMTP_USER,
      $this->db->settings['mail_sender_mode']
    );
    $mail->add( trim($orderer['email']) );
    $arg = array(
      'settings' => $this->db->settings,
      'hashkey' => $hashkey,
      'order_name' => trim($orderer['name']),
      'order_email' => trim($orderer['email']),
      'order_phone' => trim($orderer['phone']),
      'gridsizes' => $gridsizes,
      'gridconfig' => $gridconfig,
      'qtyconfig' => $qtyconfig,
      'motifs' => $motifs
    );
    $mail->setSubject( __('Visszaigazolás: ajánlatkérését igényét fogadtuk.') );
    $mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'order_new_user', $arg ) );
    $re = $mail->sendMail();

    return __('Sikeresen elküldte az ajánlatkérő konfigurációt. Hamarosan felvesszük Önnel a kapcsolatot!');
	}

  public function logAdminVisit( $id )
  {
    $this->db->update(
      self::DB_ORDERS,
      array(
        'megtekintve' => NOW
      ),
      sprintf("ID = %d and megtekintve IS NULL", $id)
    );
  }

	public function getAll( $arg = array() )
	{
		$tree = array();
    $this->tree = $tree;
    $qryp = array();

		$qry = "SELECT
      p.*
		FROM ".self::DB_ORDERS." as p
		WHERE 1=1";

    // Filters
    if (isset($arg['filters'])) {
      if(!empty($arg['filters']['src'])){
        $src = trim($arg['filters']['src']);
        $qry .= " and (";
          $qry .= "p.orderer_name LIKE '%".$src."%' or ";
          $qry .= "p.orderer_email = '".$src."' or ";
          $qry .= "p.orderer_phone LIKE '%".$src."%' ";
        $qry .= ")";
      }
    }

    if (isset($arg['ID'])) {
      $qry .= " and p.ID = '".$arg['ID']."'";
    }

    if (isset($arg['session'])) {
      $qry .= " and p.hashkey = '".$arg['session']."'";
    }

    if (isset($arg['only_unseen'])) {
      $qry .= " and p.megtekintve IS NULL ";
    }

    if (isset($arg['show_archive'])) {
      $qry .= " and p.archivalt = 1 ";
    } else {
      $qry .= " and p.archivalt = 0 ";
    }

    if (isset($arg['show_unwatched'])) {
      $qry .= " and p.megtekintve IS NULL ";
    }

		$qry .= " ORDER BY p.archivalt ASC, p.welldone ASC, p.megtekintve ASC, p.idopont DESC ";

    //echo $qry;

		$qry = $this->db->squery( $qry, $qryp );

		if( $qry->rowCount() == 0 ) return array();

		$data = $qry->fetchAll(\PDO::FETCH_ASSOC);

		foreach ( $data as $d ) {
      $d['gridconfig'] = json_decode($d['gridconfig'], true);
      $d['motifs'] = $this->getMotifs( $d['ID'] );
			$tree[] = $d;
		}

    $this->tree = $tree;

		return $tree;
	}

  public function getEmailOrders( $email )
  {
    $qryp = array();
    $qry = "SELECT
      p.ID,p.hashkey,p.idopont
    FROM ".self::DB_ORDERS." as p
    WHERE 1=1 and p.orderer_email = :email ORDER BY p.idopont DESC";
    $qryp['email'] = $email;
    $qry = $this->db->squery( $qry, $qryp );

    if( $qry->rowCount() == 0 ) return array();

    $data = $qry->fetchAll(\PDO::FETCH_ASSOC);

    $tree = array();
    foreach ( $data as $d ) {
      $tree[] = $d;
    }

    return $tree;
  }

  public function getMotifs( $order )
  {

    $qryp = array();
    $qry = "SELECT
      p.*
		FROM ".self::DB_ORDER_ITEMS." as p
		WHERE 1=1 and p.rendeles_id = :order";
    $qryp['order'] = (int)$order;

		$qry = $this->db->squery( $qry, $qryp );

		if( $qry->rowCount() == 0 ) return $this;

		$data = $qry->fetchAll(\PDO::FETCH_ASSOC);

    $tree = array();
		foreach ( $data as $d ) {
      $d['szinek'] = json_decode($d['szinek'], true);
			$tree[] = $d;
		}

    return $tree;
  }

	public function __destruct()
	{
    $this->tree = array();
    $this->db = null;
	}
}
?>
