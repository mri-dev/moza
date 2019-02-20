<?
use PortalManager\Template;

class ajax extends Controller
{
		function __construct(){
			parent::__construct();
		}

		function post(){
			extract($_POST);

			switch($type)
			{
			}
		}

		function get(){
			extract($_POST);

			$sub_page = '';

			switch($type){
				/**
				* ANGULAR ACTIONS
				**/
				case 'Sample':
					$key = $_POST['key'];

					$re = array(
						'error' => 0,
						'msg' => null,
						'data' 	=> array()
					);
					$re['pass'] = $_POST;


					echo json_encode( $re );
				break;
				/* END: ANGULAR ACTIONS */
			}

			$sub_page = ( $sub_page != '' ) ? '_'.$sub_page : '';
			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$type.$sub_page, true);
		}

		function traffic(){
			extract($_POST);
			switch($action){
				case 'add':
					try{
						$options = $_POST;
						$re = $this->traffic->add($options);
						echo '<span style="color:green;">'.$re.'</span>';
					}catch(Exception $e){
						echo '<span style="color:red;">Hiba történt: '.$e->getMessage().'</span>';
					}
				break;
			}
		}

		function __destruct(){
		}
	}

?>
