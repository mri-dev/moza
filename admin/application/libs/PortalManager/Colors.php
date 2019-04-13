<?
namespace PortalManager;

use PortalManager\Color;

/**
* class Colors
* @package PortalManager
* @version 1.0
*/
class Colors
{
	private $db = null;
	public $tree = false;
	private $current_category = false;
	private $tree_steped_item = false;
	private $tree_items = 0;
	private $walk_step = 0;
	private $parent_data = false;

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
		$name_en = ($data['name_en']) ? $new_data['name_en'] : NULL;
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
			"szinek",
			array(
				'neve' => $name,
				'neve_en'	=> $name_en,
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
		$name_en = ($new_data['name_en']) ? $new_data['name_en'] : NULL;
		$sort = ($new_data['sortnumber']) ?: 0;

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a szín elnevezését!" );
		}

		$color->edit(array(
			'neve' => $name,
			'neve_en'	=> $name_en,
			'sorrend' => $sort,
		));
	}

	public function delete( Color $color )
	{
		$color->delete();
	}

	/**
	 * Kategória fa kilistázása
	 * @param int $top_category_id Felső kategória ID meghatározása, nem kötelező. Ha nincs megadva, akkor
	 * a teljes kategória fa listázódik.
	 * @return array Kategóriák
	 */
	public function getTree( $top_category_id = false, $arg = array() )
	{
		$tree 		= array();

		if ( $top_category_id ) {
			$this->parent_data = $this->db->query( sprintf("SELECT * FROM szinek WHERE ID = %d", $top_category_id) )->fetch(\PDO::FETCH_ASSOC);
		}

		// Legfelső színtű kategóriák
		$qry = "
			SELECT *
			FROM szinek
			WHERE 1=1 ";

		// ID SET
		if( isset($arg['id_set']) && count($arg['id_set']) )
		{
			$qry .= " and ID IN (".implode(",",$arg['id_set']).") ";
		}

		$qry .= " ORDER BY sorrend ASC, ID ASC;";

		$top_cat_qry = $this->db->query($qry);
		$top_cat_data = $top_cat_qry->fetchAll(\PDO::FETCH_ASSOC);

		if( $top_cat_qry->rowCount() == 0 ) return $this;

		foreach ( $top_cat_data as $top_cat ) {
			$this->tree_items++;
			if( !$arg['admin'] ){
				$lang = \Lang::getLang();
				$top_cat['neve'] = ($lang != DLANG) ? ( ($top_cat['neve_'.$lang]) == '' && $top_cat['neve'] != '' ) ? $top_cat['neve'] : $top_cat['neve_'.$lang] : $top_cat['neve'];
			}
			$this->tree_steped_item[] = $top_cat;
			$tree[] = $top_cat;
		}

		$this->tree = $tree;

		return $this;
	}

	public function walk()
	{
		if( !$this->tree_steped_item ) return false;

		$this->current_category = $this->tree_steped_item[$this->walk_step];

		$this->walk_step++;

		if ( $this->walk_step > $this->tree_items ) {
			// Reset Walk
			$this->walk_step = 0;
			$this->current_category = false;

			return false;
		}

		return true;
	}

	public function the_cat()
	{
		return $this->current_category;
	}

	public function killDB()
	{
		$this->db = null;
	}

	public function __destruct()
	{
		//echo ' -DEST- ';
		$this->tree = false;
		$this->current_category = false;
		$this->tree_steped_item = false;
		$this->tree_items = 0;
		$this->walk_step = 0;
		$this->parent_data = false;
	}
}
?>
