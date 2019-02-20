<?
namespace PortalManager;

/**
* class Template
* @package PortalManager
*/
class Template
{
	private $template_root = '/';
	private $content = null;
	private $data_list = array();

	/**
	 * Megnyithatunk egy külső fájlt, amit felhasználhatunk, akár paraméterezve
	 * jól használható e-mail lapok létrehozására, felhasználsárára
	 * @param string $root    Template könyvtára
	 * @param array  $datalist Változók átadása tömbként, a tömb kulcsa lesz a változó neve
	 * @return object $this
	 */
	function __construct( $root, $datalist = array() )
	{
		$this->template_root 	= $root;
		$this->data_list 		= $datalist;
		return $this;
	}

	/**
	 * A template fájl megnyitása
	 * @param  string $template_name template fájl, kiterjesztés nélkül. Mindenkép php fájl legyen!
	 * @return string                template kimenet
	 */
	public function get( $template_name, $datalist = array() )
	{
		if (count($datalist) > 0) {
			$this->data_list = $datalist;
		}

		if ( !file_exists($this->template_root.$template_name.'.php') ) {

			return false;
		}

		extract($this->data_list);		
		
		ob_start();

		include $this->template_root.$template_name.'.php';

		return ob_get_clean();
	}

}
?>