<?
namespace PortalManager;

/**
* class Category
* @package ShopManager
* @version 1.0
*/
class Category
{
	private $db = null;
	private $id = false;
	private $cat_data = false;

	function __construct( $category_id, $arg = array() )
	{
		$this->db = $arg[db];
		$this->id = $category_id;

		$this->get();

		return $this;
	}

	/**
	 * Kategória adatainak lekérése
	 * @return void
	 */
	private function get()
	{
		$cat_qry 	= $this->db->query( sprintf("
			SELECT 			*
			FROM 			kategoriak
			WHERE 			ID = %d;", $this->id));
		$cat_data = $cat_qry->fetch(\PDO::FETCH_ASSOC);
		$this->cat_data = $cat_data;
	}

	/**
	 * Aktuális kategória adatainak szerkesztése / mentése
	 * @param  array $db_fields új kategória adatok
	 * @return void
	 */
	public function edit( $db_fields )
	{
		$this->db->update(
			'kategoriak',
			$db_fields,
			"ID = ".$this->id
		);
	}

	/**
	 * Aktuális kategória törlése
	 * @return void
	 */
	public function delete()
	{
		$this->db->query(sprintf("DELETE FROM kategoriak WHERE ID = %d",$this->id));
	}

	/*===============================
	=            GETTERS            =
	===============================*/
	public function getName( $lang = '')
	{
		$lang = ($lang == '') ?'':'_'.$lang;

		return $this->cat_data['neve'.$lang];
	}
	public function getPageHashkeys()
	{
		$hashkeys = array();

		if( is_null( $this->cat_data['oldal_hashkeys'] ) ) {
			return $hashkeys;
		}

		$hashkeys = explode(",",$this->cat_data['oldal_hashkeys']);

		return $hashkeys;
	}
	public function getHashkey()
	{
		return $this->cat_data['hashkey'];
	}
	public function getURL()
	{
		return '/termekek/'.\Helper::makeSafeUrl($this->cat_data['neve'],'_-').$this->cat_data['ID'];
	}
	public function getImage()
	{
		return $this->cat_data['kep'];
	}
	public function getSortNumber()
	{
		return $this->cat_data['sorrend'];
	}
	public function getParentKey()
	{
		return $this->cat_data['szulo_id'].'_'.($this->cat_data['deep']-1);
	}
	public function getParentId()
	{
		return $this->cat_data['szulo_id'];
	}
	public function getDeep()
	{
		return $this->cat_data['deep'];
	}
	public function getId()
	{
		return $this->cat_data['ID'];
	}
	/*-----  End of GETTERS  ------*/

	public function __destruct()
	{
		$this->db = null;
		$this->cat_data = false;
	}

}
?>
