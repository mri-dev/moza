<?
namespace PortalManager;

use MailManager\Mailer;
use MailManager\MailTemplates;
use FileManager\FileLister;
use PortalManager\Request;

/**
* class Portal
*/
class Portal
{
	private $db = null;

	function __construct( $arg = array() )
	{
		$this->db = $arg['db'];
		$this->settings = $arg[view]->settings;
	}

	public function sendMessageToDistributor( $md5_user_id, $form )
	{
		$post_form = array();

		if (empty($form['name'])) {
			throw new \Exception("Az üzenet küldéséhez adja meg a saját nevét!");
		}
		if (empty($form['email'])) {
			throw new \Exception("Az üzenet küldéséhez adja meg a saját e-mail címét!");
		}

		if (empty($form['msg'])) {
			throw new \Exception("Az üzenet küldéséhez adja meg az üzenet tartalmát!");
		}

		// Captcha validate
		$cparam = array(
			'secret' => $this->settings['recaptcha_private_key'],
			'response' => $form['g-recaptcha-response'],
			'remoteip' => $_SERVER['REMOTE_ADDR'],
		);
		$captcha_request = (new Request())->post(
			'https://www.google.com/recaptcha/api/siteverify',
			$cparam
		)->setPort(443)->send();
		$captcha_request = json_decode($captcha_request->getResult(), true);

		if ( $captcha_request['success'] === false ) {
			throw new \Exception("Az üzenet küldéséhez azonosítsa magát, hogy nem robot (Nem vagyok robot-ra kattintva)!");
		}

		foreach ($form as $key => $value) {
			$post_form['form_'.$key] = $value;
		}

		$dist = $this->db->squery("
			SELECT email as partner_email, nev as partner_nev FROM felhasznalok WHERE md5(ID) = :id
		", array(
			'id' => $md5_user_id
		))->fetch(\PDO::FETCH_ASSOC);

		$mail = new Mailer(
			$this->settings['page_title'],
			SMTP_USER,
			$this->settings['mail_sender_mode']
		);

		$mail->setReplyTo( $post_form['form_email'], $post_form['form_name']  );
		$mail->add($dist['partner_email']);

		$arg = array(
			'settings' 		=> $this->settings
		);

		$arg = array_merge( $arg, $dist, $post_form );

		$arg['content'] = (new MailTemplates(array('db'=>$this->db)))->get('side_contact_msg', $arg);

		$mail->setSubject( 'Üzenete érkezett - Tanácsadás: '.$post_form['form_name'] );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'clearmail', $arg ) );
		$re = $mail->sendMail();

	}

	public function sendContactMsg()
	{

		if ( !\Applications\Captcha::verify() ) {
			throw new \Exception("Igazolja, hogy maga nem egy robot!");
		}

		if ( $_POST['contact_name'] == '' ) {
			throw new \Exception("Kérjük, hogy adja meg a nevét a kapcsolat üzenet elküldéséhez!");
		}

		if ( $_POST['contact_email'] == '' ) {
			throw new \Exception("Kérjük, hogy adja meg az e-mail címét a kapcsolat üzenet elküldéséhez!");
		}
		if ( $_POST['contact_subject'] == '' ) {
			throw new \Exception("Kérjük, hogy adja meg a tárgyat a kapcsolat üzenet elküldéséhez!");
		}
		if ( $_POST['contact_msg'] == '' ) {
			throw new \Exception("Kérjük, hogy írja be üzenetét a kapcsolat üzenet elküldéséhez!");
		}

		$this->db->insert( "uzenetek",
		array(
			'felado_email' 	=> $_POST['contact_email'],
			'felado_nev' 	=> $_POST['contact_name'],
			'uzenet_targy' 	=> $_POST['contact_subject'],
			'uzenet' => nl2br(strip_tags($_POST['contact_msg']))
		) );

		$msgid = $this->db->lastInsertId();

		// Értesítő e-mail az adminisztrátornak
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $this->settings['alert_email'] );
		$arg = array(
			'settings' 		=> $this->settings,
			'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!',
			'form' 			=> $_POST,
			'adminroot' 	=> ADMROOT,
			'msgid' 		=> $msgid
		);
		$mail->setSubject( 'Értesítő: Új kapcsolat üzenet érkezett' );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'admin_contact_msg', $arg ) );
		$re = $mail->sendMail();

	}

	public function getHighlightItems( $arg = array() )
	{
		$q = "SELECT * FROM kiemelt_ajanlo WHERE ID IS NOT NULL ";
		if (!$arg['admin']) {
			$q .= " and lathato = 1 ";
		}
		$q .= " ORDER BY sorrend ASC;";

		$arg['multi'] = 1;
		extract( $this->db->q( $q, $arg ) );

		return $ret;
	}

	public function getHighlightItem( $id )
	{
		if( !$id || !is_numeric($id) ) return false;

		$q = "SELECT * FROM kiemelt_ajanlo WHERE ID = ".$id;

		return $this->db->query($q)->fetch(\PDO::FETCH_ASSOC);
	}

	public function addHighlight( $post )
	{
		unset($post[__FUNCTION__]);

		if ( $post['tartalom'] == '') {
			throw new \Exception("Ajánló tartalmi szövegét megadni kötelező!");
		}

		$post['lathato'] = ($post['lathato'] == 'on') ? 1 : 0;
		$post['sorrend'] = ( !$post['sorrend']) ? 0 : $post['sorrend'];

		$post['lathato'] = ($post['lathato'] == 'on') ? 1 : 0;

		$this->db->insert(
			"kiemelt_ajanlo",
			$post
		);
	}

	public function saveHighlight( $id, $post )
	{
		unset($post[__FUNCTION__]);

		if ( $post['tartalom'] == '') {
			throw new \Exception("Ajánló tartalmi szövegét megadni kötelező!");
		}

		$post['lathato'] = ($post['lathato'] == 'on') ? 1 : 0;
		$post['sorrend'] = ( !$post['sorrend']) ? 0 : $post['sorrend'];

		$this->db->update(
			"kiemelt_ajanlo",
			$post,
			"ID = ".$id
		);
	}

	public function delHighlight( $id )
	{
		if( !$id || !is_numeric($id) ) return false;

		$this->db->query("DELETE FROM kiemelt_ajanlo WHERE ID = ".$id);
	}

	public function getSlideshow( $group = 'Home' )
	{
		$slides = array();

		$qry = $this->db->query( $q = "SELECT * FROM slideshow WHERE groups = '$group' and lathato = 1 ORDER BY sorrend ASC;");

		$data = $qry->fetchAll(\PDO::FETCH_ASSOC);

		foreach ( $data as $slide ) {
			$slide['kep'] = \PortalManager\Formater::sourceImg($slide['kep']);
			$slides[] = $slide;
		}

		return $slides;
	}

	public function getFeliratkozok( $arg )
	{
		$list = array();

		$sql = "
		SELECT 			f.*
		FROM 			feliratkozok as f
		WHERE 			f.ID IS NOT NULL
		";

		if(count($arg[filters]) > 0){
			foreach($arg[filters] as $key => $v){
				switch($key)
				{
					case 'hely':
						$sql .= " and ".$key." LIKE '%".$v."%' ";
					break;
					case 'nev': case 'email':
						$sql .= " and ".$key." LIKE '%".$v."%' ";
					break;
					default:
						$sql .= " and ".$key." = '".$v."' ";
					break;
				}

			}
		}

		$sql .= " ORDER BY f.idopont DESC ";
		$arg['multi'] = 1;
		extract($this->db->q( $sql, $arg ));

		$list = $ret;

		return $list;
	}

	public function feliratkozva( $email )
	{
		$check = $this->db->query( sprintf("SELECT leiratkozott FROM feliratkozok WHERE email ='%s'", $email) );

		if ( $check->rowCount() == 0 ) {
			return false;
		} else {
			return true;
		}
	}

	public function feliratkozas( $name, $email, $phone, $hol = 'page', $captcha = false )
	{
		if ( !\Applications\Captcha::verify() && $captcha ) {
			throw new \Exception("Igazolja, hogy maga nem egy robot!");
		}

		if ( $name == '' ) {
			throw new \Exception("Kérjük, hogy adja meg a nevét a feliratkozáshoz!");
		}

		if ( $phone == '' ) {
			throw new \Exception("Kérjük, hogy adja meg a saját telefonszámát!");
		}

		if ( $hol == 'feliratkozás' &&  $email == '' ) {
			throw new \Exception("Kérjük, hogy adja meg az e-mail címét a feliratkozáshoz!");
		}

		$check = $this->db->query( sprintf("SELECT leiratkozott FROM feliratkozok WHERE email ='%s'", $email) );

		if ( $check->rowCount() == 0 ) {
			$this->db->insert(
				"feliratkozok",
				array(
					'nev' => $name,
					'email' => $email,
					'phone' => $phone,
					'hely' => $hol
				)
			);
		} else {
			$data = $check->fetch(\PDO::FETCH_ASSOC);

			if ( $data['leiratkozott'] == '1' ) {
				$this->db->update( "feliratkozok", array(
					'leiratkozott' => 0,
					'leiratkozas_ideje' => NULL,
					'idopont' => NOW
				),
				sprintf("email = '%s'", $email) );
			} else {

				if( $hol != 'feliratkozás' ) return true;

				throw new \Exception("Ezzel az e-mail címmel (".$email.") már feliratkoztak korábban!");
			}
		}
	}

	public function leiratkozas( $email, $captcha = false )
	{
		if ( !\Applications\Captcha::verify() && $captcha ) {
			throw new \Exception("Igazolja, hogy maga nem egy robot!");
		}

		$check = $this->db->query( sprintf("SELECT leiratkozott FROM feliratkozok WHERE email ='%s'", $email) );

		if ( $check->rowCount() != 0 ) {
			$data = $check->fetch(\PDO::FETCH_ASSOC);

			if ( $data['leiratkozott'] == '0' ) {
				$this->db->update( "feliratkozok", array(
					'leiratkozott' => 1,
					'leiratkozas_ideje' => NOW
				),
				sprintf("email = '%s'", $email) );
			} else {
				throw new \Exception("Ezzel az e-mail címmel (".$email.") már leiratkoztak korábban!");
			}
		} else {
			throw new \Exception("Ezzel az e-mail címmel (".$email.") nem iratkoztak fel hírlevélre!");
		}
	}

	public function __destruct()
	{
		$this->db = null;
	}
}
?>
