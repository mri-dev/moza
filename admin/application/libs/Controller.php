<?
use DatabaseManager\Database;
use PortalManager\AdminUser;
use PortalManager\Menus;
use PortalManager\Template;
use PortalManager\Installer;

class Controller {
    public $db = null;
    public $hidePatern 	= true;
    private $theme_wire 	= '';
    private $theme_folder 	= '';
    private $start_time     = 0;
    private $finish_time    = 0;
    private $is_admin       = false;

    public static $pageTitle;
    public $fnTemp          = array();
    public static $user_opt = array();

    function __construct($arg = array())
    {
        $this->start_time = microtime(true);
        $this->is_admin = $arg[admin];
        Session::init();
        Helper::setMashineID();
        $this->gets = Helper::GET();

        //$this->memory_usage();

        // CORE
        // $this->model 		= new Model();
        $this->view = new View();
        $this->db = new Database();
        $this->installer = new Installer(array('db'=> $this->db));
        //////////////////////////////////////////////////////
        $this->view->settings = $this->getAllValtozo();
        $this->gets = Helper::GET();
        $this->view->gets = $this->gets;

        $this->AdminUser = new AdminUser( array( 'db' => $this->db, 'view' => $this->view, 'settings' => $this->view->settings )  );

        $this->out( 'db',   $this->db );

        // Ha nem ajax requestről van szó
        if ($this->gets[0] != 'ajax')
        {
          // Only admin
          if ( !defined('PRODUCTIONSITE') )
          {
            $this->out( 'modules', $this->installer->listModules(array('only_active' => true)) );
          }

          $templates = new Template( VIEW . 'templates/' );
          $this->out( 'templates', $templates );


          if ( $_GET['msgkey'] ) {
              $this->out( $_GET['msgkey'], Helper::makeAlertMsg('pSuccess', $_GET[$_GET['msgkey']]) );
          }
        }

        if(!$arg[hidePatern]){ $this->hidePatern = false; }
        $this->view->valuta  = 'Ft';
    }

    function out( $viewKey, $output ){
        $this->view->$viewKey = $output;
    }

    function bodyHead($key = ''){
        $mode       = false;
        $subfolder  = '';

        $this->theme_wire   = ($key != '') ? $key : '';

        if($this->getThemeFolder() != ''){
            $mode       = true;
            $subfolder  = $this->getThemeFolder().'/';
        }

        # Oldal címe
        if(self::$pageTitle != null){
            $this->view->title = self::$pageTitle . ' | ' . $this->view->settings['page_title'];
        } else {
            $this->view->title = $this->view->settings['page_title'] . " &mdash; ".$this->view->settings['page_description'];
        }

        # Render HEADER
        if(!$this->hidePatern){
            $this->view->render($subfolder.$this->theme_wire.'header'.( (isset($_GET['header'])) ? '-'.$_GET['header'] : '' ),$mode);
        }

        # Aloldal átadása a VIEW-nek
        $this->view->called = $this->fnTemp;
    }



    function setTitle($title){
        $this->view->title = $title;
    }

    function valtozok($key){
        $d = $this->db->query("SELECT bErtek FROM beallitasok WHERE bKulcs = '$key'");
        $dt = $d->fetch(PDO::FETCH_ASSOC);

        return $dt[bErtek];
    }

    function getAllValtozo(){
        $v = array();
        $d = $this->db->query("SELECT bErtek, bKulcs FROM beallitasok");
        $dt = $d->fetchAll(PDO::FETCH_ASSOC);

        foreach($dt as $d){
            $v[$d[bKulcs]] = $d[bErtek];
        }

        $protocol = ($_SERVER['HTTPS']) ? 'https://' : 'http://';

        $v['domain'] = $protocol.str_replace( array('http://','https://'), '', $v['page_url']);

        if (strpos($v['alert_email'],",") !== false)
        {
          $v['alert_email'] = explode(",",$v['alert_email']);
        }

        return $v;
    }

    function setValtozok($key,$val){
        $iq = "UPDATE beallitasok SET bErtek = '$val' WHERE bKulcs = '$key'";
        $this->db->query($iq);
    }

    protected function setThemeFolder($folder = ''){
        $this->theme_folder = $folder;
    }

    protected function getThemeFolder(){
        return $this->theme_folder;
    }

    public function memory_usage()
    {
       echo '-Memory: ',round(memory_get_usage()/1048576,2),' MB used-';
    }
    public function get_speed()
    {
       echo "-Operation Speed:", (number_format($this->finish_time - $this->start_time, 4))," sec-";
    }

    function __destruct(){
        $mode       = false;
        $subfolder  = '';

        if($this->getThemeFolder() != ''){
            $mode       = true;
            $subfolder  = $this->getThemeFolder().'/';
        }

        if(!$this->hidePatern){
            # Render FOOTER
            $this->view->render($subfolder.$this->theme_wire.'footer',$mode);
        }
        $this->db = null;
       // $this->memory_usage();

        $this->finish_time = microtime(true);
        //$this->get_speed();
    }
}

?>
