<?
namespace PortalManager;

/**
* class Projects
* @package PortalManager
* @version 1.0
*/
class Projects
{
  const DB_PROJECTS = 'projektek';
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
	public function add( $form, $used_motifs, $used_colors, $grid  )
	{
		$name = ($form['name']) ? $form['name'] : false;
		$email = ($form['email']) ? $form['email'] : false;

		if ( !$name ) {
			throw new \Exception( "Kérjük, hogy adja meg a projekt elnevezését!" );
		}
    if ( !$email ) {
			throw new \Exception( "Kérjük, hogy adja meg az Ön e-mail címét!" );
		}

		$this->db->insert(
			self::DB_PROJECTS,
			array(
				'title' => $name,
				'email' => $email,
        'used_motifs' => json_encode($used_motifs, \JSON_UNESCAPED_UNICODE),
        'used_colors' => json_encode($used_colors, \JSON_UNESCAPED_UNICODE),
        'gridconfig' => json_encode($grid, \JSON_UNESCAPED_UNICODE)
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
