<?
namespace PortalManager;

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

	public function create( $orderer, $motifs, $qtyconfig, $gridsizes, $gridconfig, $project = false )
	{

    if ( !$qtyconfig || count($qtyconfig) != count($motifs) ) {
      throw new \Exception(__('Kérjük, hogy adja meg a motívumoknál a rendelendő mennyiségeket (darab vagy nm)!'), 1);
    }

		$this->db->insert(
			self::DB_ORDERS,
			array(
        'saved_project_id' => ($project && $project != '') ? $project : NULL,
        'hashkey' => md5(uniqid()),
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
        $me_db = (int)$qtyconfig[$m['hashid']]['db'];
        $me_nm = (int)$qtyconfig[$m['hashid']]['nm'];
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

    return __('Sikeresen megrendelte a konfigurációt. Hamarosan felvesszük Önnel a kapcsolatot!');
	}

	public function getAll( $arg = array() )
	{
		$tree = array();
    $this->tree = $tree;
    $qryp = array();

		$qry = "SELECT
      p.*
		FROM ".self::DB_PROJECTS." as p
		WHERE 1=1";

    if (!empty($arg['email'])) {
      $qry .= " and p.email = :email";
      $qryp['email'] = trim($arg['email']);
    }

		$qry .= " ORDER BY p.savedate DESC ";

		$qry = $this->db->squery( $qry, $qryp );

		if( $qry->rowCount() == 0 ) return $this;

		$data = $qry->fetchAll(\PDO::FETCH_ASSOC);

		foreach ( $data as $d ) {
      $d['grid'] = json_decode($d['gridconfig'], true);
      $d['used_motifs'] = json_decode($d['used_motifs'], true);
      $d['used_colors'] = json_decode($d['used_colors'], true);
      unset($d['email']);
      unset($d['gridconfig']);
			$tree[] = $d;
		}

    $this->tree = $tree;

		return $tree;
	}

	public function __destruct()
	{
    $this->db = null;
	}
}
?>
