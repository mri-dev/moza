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

	public function add( $id = 0, $data = array() )
	{
		$kod = ($data['mintakod']) ? $data['mintakod'] : false;
		$kategoria = ($data['kategoria']) ? $data['kategoria'] : false;
		$svg = (!empty($data['svgpath'])) ? $data['svgpath'] : false;

		if ( empty($kod) ) {
			throw new \Exception( "Kérjük, hogy adja meg a motívum mintakódját!" );
		}
		if ( empty($kategoria) ) {
			throw new \Exception( "Kérjük, hogy adja meg a motívum kategóriáját!" );
		}
		if ( empty($svg) ) {
			throw new \Exception( "Kérjük, hogy adja meg a motívum SVG script kódját!" );
		}

		$svgpath = $this->prepareShapeSVGScript( $data['svgpath'] );

		if ($id == 0) {
			$this->db->insert(
				"motivumok",
				array(
					'lathato' => ($data['lathato'] == 'false' || !$data['lathato'] || empty($data['lathato']) || $data['lathato'] == '0') ? 0 : 1,
					'mintakod' => $data['mintakod'],
					'sorrend' => (int)$data['sorrend'],
					'kategoria' => (int)$kategoria,
					'svgpath' => $svgpath['cleared'],
				)
			);
			$newid = $this->db->lastInsertId();
		} else {
			$this->db->update(
				"motivumok",
				array(
					'lathato' => ($data['lathato'] == 'false' || !$data['lathato'] || empty($data['lathato']) || $data['lathato'] == '0') ? 0 : 1,
					'mintakod' => $data['mintakod'],
					'sorrend' => (int)$data['sorrend'],
					'kategoria' => (int)$kategoria,
					'svgpath' => $svgpath['cleared']
				),
				sprintf("ID = %d", $id)
			);
		}

		// has shapes
		$sh = $this->db->squery("SELECT ID FROM motivum_layers WHERE motivumID = :mid", array('mid' => $kod ));

		// colors greys
		$colr = $this->db->squery("SELECT szin_rgb FROM szinek WHERE kod IN ('F2', 'SZ1', 'SZ2', 'SZ3', 'SZ4', 'SZ5', 'SZ6', 'SZ7', 'SZ8', 'SZ9','SZ10') ORDER BY rand()");
		$colors = array();
		if ($colr->rowCount() != 0) {
			$colr = $colr->fetchAll(\PDO::FETCH_ASSOC);
			foreach ((array)$colr as $co) {
				$colors[] = $co['szin_rgb'];
			}
		}

		if ( $sh->rowCount() == 0 ) {
			if ($svgpath['shapes'] && count($svgpath['shapes']) != 0) {
				$si = 0;
				foreach ((array)$svgpath['shapes'] as $shape) {
					$si++;
					$shape_js  = '';
					foreach ((array)$shape as $s) {
						$shape_js .= $s.';';
					}
					if ($shape_js != '') {
						$fill = 'FFFFFF';
						if($si > 1) {
							$fill = $colors[$si-2];

							if ($fill == '') {
								$randi = mt_rand(0, count($colors)-1);
								$fill = $colors[$randi];
							}
						}
						$this->db->insert(
							'motivum_layers',
							array(
								'motivumID' => $kod,
								'canvas_js' => $shape_js,
								'sortindex' => $si,
								'fill_color' => '#'.$fill
							)
						);
					}
				}
			}
		} else {
			// shape fill color
			foreach ((array)$data['shapes'] as $sh) {
				$this->db->update(
					'motivum_layers',
					array(
						'fill_color' => $sh['fill_color']
					),
					sprintf("ID = %d", (int)$sh['ID'])
				);
			}
		}

		return ($newid) ? (int)$newid : true;
	}

	public function addStyleConfig( $id = 0, $name = false, $motivum = false)
	{
		if ( !$name || empty($name)) {
			throw new \Exception( "Kötelező megadni egy elnevezést a saját minta mentéséhez." );
		}

		if ( !$motivum ) {
			throw new \Exception( "A motívum adatai nem lettek betöltve. Frissítse az oldal és próbálja újra!" );
		}

		$this->db->insert(
			'motivum_styles',
			array(
				'nev' => addslashes(trim($name)),
				'motivumID' => trim($motivum['mintakod'])
			)
		);

		$style_id = $this->db->lastInsertId();

		if ($motivum['shapes']) {
			foreach ( (array)$motivum['shapes'] as $s ) {
				$this->db->insert(
					'motivum_style_layers',
					array(
						'styleID' => $style_id,
						'motivumID' => trim($motivum['mintakod']),
						'layerID' => (int)$s['ID'],
						'fill_color' => trim($s['fill_color'])
					)
				);
			}
		}

		return (int)$style_id;
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

    $qry = "SELECT ID, canvas_js, fill_color, sortindex  FROM motivum_layers WHERE 1=1 and motivumID = :mid ORDER BY sortindex ASC, ID ASC";
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

		// Saját minták
		if ( !$arg['hideown'] ) {
			$load_own_styles = $this->getStyleConfigs($tree);
			if ($load_own_styles) {
				foreach ( (array)$load_own_styles as $os ) {
					$tree[] = $os;
				}
			}
		}

    $this->tree = $tree;

		return $tree;
	}

	public function getStyleConfigs( $motifs, $arg = array())
	{
		$set = array();

		$mots = array();
		// contain
		foreach ((array)$motifs as $m) {
			$mots[$m['mintakod']] = $m;
		}
		unset($motifs);

		$q = "SELECT
		s.ID,
		s.nev,
		s.motivumID
		FROM motivum_styles as s WHERE 1=1 ";

		if ($arg['admin'] === true) {

		} else {
			$q .= " and s.lathato = 1";
		}

		$q .= " ORDER BY s.sorrend ASC, s.ID DESC";


		$qry = $this->db->squery($q);

		if ($qry->rowCount() != 0) {
			$data = $qry->fetchAll(\PDO::FETCH_ASSOC);
			foreach ( (array)$data as $d ) {
				if ( !array_key_exists($d['motivumID'], $mots) ) continue;

				$ID = $d['ID'];
				$nev = $d['nev'];
				$d = $mots[$d['motivumID']];
				$d['ID'] = $ID;
				$d['nev'] = $nev;
				$d['kat_hashkey'] = 'OWN';
				$d['kat_name'] = __('Kiemelt minták');

				// Shape configs
				$sq = $this->db->squery("SELECT fill_color, layerID FROM motivum_style_layers WHERE styleID = :sid", array('sid' => $ID));
				$style_colors = array();
				if ($sq->rowCount() != 0) {
					$sq = $sq->fetchAll(\PDO::FETCH_ASSOC);
					foreach ((array)$sq as $sl) {
						$style_colors[$sl['layerID']] = $sl;
					}
				}
				unset($sq);

				// Shapes
				$nshapes = array();
				if ($d['shapes']) {
					foreach ( (array)$d['shapes'] as $s ) {
						$color = $style_colors[$s['ID']]['fill_color'];
						if ($color != '') {
							$s['fill_color'] = $color;
						}
						$nshapes[] = $s;
					}
				}
				$d['shapes'] = $nshapes;
				unset($nshapes);
				unset($style_colors);

				$set[] = $d;
			}
		} else {
			return array();
		}

		return $set;
	}

	public function prepareShapeSVGScript( $svg )
	{
		$back = array();
		$clearsvg = '';

		// trim
		$svg = trim($svg);
		// remove whitespaces
		$svg = preg_replace('/\s+/', '', $svg);
		// remove if-s
		$svg = str_replace(array('if(c){','}'), '', $svg);
		// renames
		$svg = str_replace('canvas.', 'ctx.', $svg);
		// explode line
		$xsvg = explode(";", rtrim($svg,";"));
		unset($svg);

		// Grouping
		$shapes = array();
		$groupindex = 0;
		$current_line = 0;
		foreach ((array)$xsvg as $s) {
			// excludes
			if ( $this->expludeSVGLineCheck($s) ) {
				continue;
			}
			if (strpos($s, '.beginPath()') !== false) {
				$groupindex++;
			}
			$shapes[$groupindex][] = $s;
			$current_line++;
		}
		unset($xsvg);

		$reshape = array();
		foreach ((array)$shapes as $index => $shape) {
			if (strpos(end($shape), '.closePath()') === false) {
				$shape[] = 'ctx.closePath()';
			}
			$reshape[$index] = $shape;
		}
		unset($shapes);

		// collect clear svg
		foreach ( (array)$reshape as $s ) {
			foreach ((array)$s as $ss) {
				$clearsvg .= $ss.';';
			}
		}


		$back['shapes'] = $reshape;
		$back['cleared'] = $clearsvg;
		unset($reshape);

		return $back;
	}

	private function expludeSVGLineCheck( $line )
	{
		if (strpos($line,'ctx.fillStyle') !== false) {
			return true;
		}
		if (strpos($line,'ctx.strokeStyle=') !== false) {
			return true;
		}
		if (strpos($line,'ctx.fill()') !== false) {
			return true;
		}
		if (strpos($line,'ctx.stroke()') !== false) {
			return true;
		}
		// html5 converter removes
		if (strpos($line,'ctx.font=') !== false) {
			return true;
		}
		if (strpos($line,'ctx.scale(') !== false) {
			return true;
		}
		if (strpos($line,'ctx.save()') !== false) {
			return true;
		}
		if (strpos($line,'ctx.restore()') !== false) {
			return true;
		}
		if (strpos($line,'ctx.miterLimit=') !== false) {
			return true;
		}
	}

	public function __destruct()
	{
    $this->db = null;
	}
}
?>
