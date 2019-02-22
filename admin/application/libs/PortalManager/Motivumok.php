<?
namespace PortalManager;

use PortalManager\Motivum;

/**
* class Colors
* @package PortalManager
* @version 1.0
*/
class Motivumok
{
	private $db = null;
	public $tree = false;

	function __construct( $arg = array() )
	{
		$this->db = $arg[db];
    }

	/**
	 * Kategória létrehzás
	 * @param array $data új kategória létrehozásához szükséges adatok
	 * @return void
	 */
	public function add( $data = array() )
	{
		$name = ($data['name']) ?: false;
		$sort = ($data['sortnumber']) ?: 0;
		$kod = ($data['kod']) ?: NULL;
		$szin_rgb = ($data['szin_rgb']) ?: NULL;
    $szin_ncs = ($data['szin_ncs']) ?: NULL;

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a szín elnevezését!" );
		}
    if ( !$kod ) {
			throw new \Exception( "Kérjük, hogy adja meg a szín azonosítóját!" );
		}
    if ( !$szin_rgb ) {
			throw new \Exception( "Kérjük, hogy adja meg a szín RGB színkódját!" );
		}

		$this->db->insert(
			"motivumok",
			array(
				'neve' => $name,
				'sorrend' => $sort,
        'kod' => $kod,
        'szin_rgb' => $szin_rgb,
        'szin_ncs' => $szin_ncs
			)
		);
	}

	public function edit( Color $color, $new_data = array() )
	{
		$name = ($new_data['name']) ?: false;
		$sort = ($new_data['sortnumber']) ?: 0;

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a szín elnevezését!" );
		}

		$color->edit(array(
			'neve' 		=> $name,
			'sorrend' 	=> $sort,
		));
	}

	public function delete( Color $color )
	{
		$color->delete();
	}

  public function getMotivumShapes( $motivum )
  {
    $shapes = array();

    $qry = "SELECT ID, canvas_js, fill_color FROM motivum_layers WHERE 1=1 and motivumID = :mid ORDER BY sortindex ASC, ID ASC";
    $qry = $this->db->squery( $qry, array( 'mid' => $motivum ) );

    if ($qry->rowCount() == 0) {
      return false;
    }

    $data = $qry->fetchAll(\PDO::FETCH_ASSOC);

    foreach ((array)$data as $d ) {
      $shapes[] = $d;
    }
    unset($data);

    return $shapes;
  }

	public function getAll( $arg = array() )
	{
		$tree = array();
    $this->tree = $tree;

		$qry = "SELECT
      m.*,
      k.hashkey as kat_hashkey,
      k.neve as kat_name
		FROM motivumok as m
    LEFT OUTER JOIN kategoriak as k ON k.ID = m.kategoria
		WHERE 1=1";

    if ($arg['admin'] === true) {

    } else {
      $qry .= " and m.lathato = 1";
    }

		// ID SET
		if( isset($arg['id_set']) && count($arg['id_set']) )
		{
			$qry .= " and m.ID IN (".implode(",",$arg['id_set']).") ";
		}

		$qry .= " ORDER BY m.sorrend ASC, m.ID ASC";

		$qry = $this->db->query( $qry );

		if( $qry->rowCount() == 0 ) return $this;

		$data = $qry->fetchAll(\PDO::FETCH_ASSOC);

		foreach ( $data as $d ) {
      $d['ID'] = (int)$d['ID'];
      $d['kategoria'] = (int)$d['kategoria'];
      $d['shapes'] = $this->getMotivumShapes( $d['mintakod'] );
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
