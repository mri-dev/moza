<?
namespace PortalManager;

/**
* class Color
* @package PortalManager
* @version 1.0
*/
class Color
{
	private $db = null;
	private $id = false;
	private $cat_data = false;

	function __construct( $color_id, $arg = array() )
	{
		$this->db = $arg[db];
		$this->id = $color_id;

		$this->get();

		return $this;
	}

	private function get()
	{
		$cat_qry 	= $this->db->query( sprintf("
			SELECT *
			FROM szinek
			WHERE ID = %d;", $this->id));
		$cat_data = $cat_qry->fetch(\PDO::FETCH_ASSOC);
		$this->cat_data = $cat_data;
	}

	public function edit( $db_fields )
	{
		$this->db->update(
			'szinek',
			$db_fields,
			"ID = ".$this->id
		);
	}

	public function delete()
	{
		$this->db->query(sprintf("DELETE FROM szinek WHERE ID = %d",$this->id));
	}

	/*===============================
	=            GETTERS            =
	===============================*/
	public function getName()
	{
		return $this->cat_data['neve'];
	}
  public function getAzonosito()
	{
		return $this->cat_data['kod'];
	}
  public function getRGB()
	{
		return $this->cat_data['szin_rgb'];
	}
  public function getNCS()
	{
		return $this->cat_data['szin_ncs'];
	}
	public function getSortNumber()
	{
		return $this->cat_data['sorrend'];
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
