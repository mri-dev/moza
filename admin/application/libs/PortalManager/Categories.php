<?
namespace PortalManager;

use PortalManager\Category;

/**
* class Categories
* @package ShopManager
* @version 1.0
*/
class Categories
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
		$deep = 0;
		$name = ($data['name']) ?: false;
		$sort = ($data['sortnumber']) ?: 0;
		$parent = ($data['parent_category']) ?: NULL;
		$hashkey = ($data['hashkey']) ?: NULL;
		$oldal_hashkeys = (count($new_data['oldal_hashkeys']) > 0) ? implode(",",$new_data['oldal_hashkeys']) : NULL;

		if ($parent) {
			$xparent = explode('_',$parent);
			$parent = (int)$xparent[0];
			$deep = (int)$xparent[1] + 1;
		}

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a kategória elnevezését!" );
		}

		$this->db->insert(
			"kategoriak",
			array(
				'neve' 		=> $name,
				'szulo_id' 	=> $parent,
				'sorrend' 	=> $sort,
				'deep' 		=> $deep,
				'hashkey' 	=> $hashkey,
				'oldal_hashkeys' => $oldal_hashkeys
			)
		);
	}

	/**
	 * Kategória szerkesztése
	 * @param  Category $category ShopManager\Category class
	 * @param  array    $new_data
	 * @return void
	 */
	public function edit( Category $category, $new_data = array() )
	{
		$deep = 0;
		$name = ($new_data['name']) ?: false;
		$sort = ($new_data['sortnumber']) ?: 0;
		$parent = ($new_data['parent_category']) ?: NULL;
		$hashkey = ($new_data['hashkey']) ?: NULL;
		$oldal_hashkeys = (count($new_data['oldal_hashkeys']) > 0) ? implode(",",$new_data['oldal_hashkeys']) : NULL;
		$image = ( isset($new_data['image']) ) ? $new_data['image'] : NULL;

		if ($parent) {
			$xparent = explode('_',$parent);
			$parent = (int)$xparent[0];
			$deep = (int)$xparent[1] + 1;
		}

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a kategória elnevezését!" );
		}

		$category->edit(array(
			'neve' 		=> $name,
			'szulo_id' 	=> $parent,
			'sorrend' 	=> $sort,
			'deep' 		=> $deep,
			'hashkey' 	=> $hashkey,
			'oldal_hashkeys' => $oldal_hashkeys,
			'kep' 		=> $image
		));
	}

	public function delete( Category $category )
	{
		$category->delete();
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
			$this->parent_data = $this->db->query( sprintf("SELECT * FROM kategoriak WHERE ID = %d", $top_category_id) )->fetch(\PDO::FETCH_ASSOC);
		}

		// Legfelső színtű kategóriák
		$qry = "
			SELECT *
			FROM kategoriak
			WHERE 1=1 ";

		if ( !$top_category_id ) {
			$qry .= " and szulo_id IS NULL ";
		} else {
			$qry .= " and  szulo_id = ".$top_category_id;
		}

		// ID SET
		if( isset($arg['id_set']) && count($arg['id_set']) )
		{
			$qry .= " and ID IN (".implode(",",$arg['id_set']).") ";
		}

		$qry .= " ORDER BY 		sorrend ASC, ID ASC;";

		$top_cat_qry 	= $this->db->query($qry);
		$top_cat_data 	= $top_cat_qry->fetchAll(\PDO::FETCH_ASSOC);

		if( $top_cat_qry->rowCount() == 0 ) return $this;

		foreach ( $top_cat_data as $top_cat ) {
			$this->tree_items++;

			$top_cat['link'] = DOMAIN.'termekek/'.\PortalManager\Formater::makeSafeUrl($top_cat['neve'],'_-'.$top_cat['ID']);

			$this->tree_steped_item[] = $top_cat;

			// Alkategóriák betöltése
			$top_cat['child'] = $this->getChildCategories($top_cat['ID']);
			$tree[] = $top_cat;
		}

		$this->tree = $tree;

		return $this;
	}

	/**
	 * Végigjárja az összes kategóriát, amit betöltöttünk a getFree() függvény segítségével. while php függvénnyel
	 * járjuk végig. A while függvényen belül használjuk a the_cat() objektum függvényt, ami az aktuális kategória
	 * adataiat tartalmazza tömbbe sorolva.
	 * @return boolean
	 */
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

	/**
	 * A walk() fgv-en belül visszakaphatjuk az aktuális kategória elem adatait tömbbe tárolva.
	 * @return array
	 */
	public function the_cat()
	{
		return $this->current_category;
	}

	public function getParentData( $field = false )
	{
		if ( $field ) {
			return $this->parent_data[$field];
		} else
		return $this->parent_data;
	}

	/**
	 * Kategória alkategóriáinak listázása
	 * @param  int $parent_id 	Szülő kategória ID
	 * @return array 			Szülő kategória alkategóriái
	 */
	public function getChildCategories( $parent_id )
	{
		$tree = array();


		// Gyerek kategóriák
		$child_cat_qry 	= $this->db->query( sprintf("
			SELECT 			*
			FROM 			kategoriak
			WHERE 			szulo_id = %d
			ORDER BY 		sorrend ASC, ID ASC;", $parent_id));
		$child_cat_data	= $child_cat_qry->fetchAll(\PDO::FETCH_ASSOC);

		if( $child_cat_qry->rowCount() == 0 ) return false;
		foreach ( $child_cat_data as $child_cat ) {
			$this->tree_items++;
			$child_cat['link'] 	= DOMAIN.'termekek/'.\PortalManager\Formater::makeSafeUrl($child_cat['neve'],'_-'.$child_cat['ID']);
			$child_cat['kep'] 	= ($child_cat['kep'] == '') ? '/src/images/no-image.png' : $child_cat['kep'];
			$this->tree_steped_item[] = $child_cat;

			$child_cat['child'] = $this->getChildCategories($child_cat['ID']);
			$tree[] = $child_cat;
		}

		return $tree;

	}


	/**
	 * Kategória szülő listázása
	 * @param  int $child_id 	Szülő kategória ID
	 * @return array 			Szülő szülő kategóriái
	 */
	public function getCategoryParentRow( $id, $return_row = 'ID', $deep_allow_under = 0 )
	{
		$row = array();

		$has_parent = true;

		$limit = 10;

		$sid = $id;

		while( $has_parent && $limit > 0 ) {

			$q 		= "SELECT ".( ($return_row) ? $return_row.', szulo_id, deep' : '*' )." FROM kategoriak WHERE ID = ".$sid.";";
			$qry 	= $this->db->query($q);
			$data 	= $qry->fetch(\PDO::FETCH_ASSOC);

			$sid = $data['szulo_id'];

			if( is_null( $data['szulo_id'] ) ) {
				$has_parent = false;
			}

			if( (int)$data['deep'] >= $deep_allow_under ) {
				if (!$return_row) {
					$row[] = $data;
				} else {
					$row[] = $data[$return_row];
				}
			}

			$limit--;
		}

		return $row;
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
