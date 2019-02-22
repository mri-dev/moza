<?
namespace PortalManager;

/**
* class Color
* @package PortalManager
* @version 1.0
*/
class Motivum
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
			FROM motivumok
			WHERE ID = %d;", $this->id));
		$cat_data = $cat_qry->fetch(\PDO::FETCH_ASSOC);
		$this->cat_data = $cat_data;
	}

	public function edit( $db_fields )
	{
		$this->db->update(
			'motivumok',
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
  public function getKod()
	{
		return $this->cat_data['mintakod'];
	}
  public function isLathato()
	{
		return ($this->cat_data['lathato'] == '1') ? true : false;
	}
  public function getSVGPath()
	{
		return $this->cat_data['svgpath'];
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
