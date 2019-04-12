<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/html4"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" lang="hu-HU" <?=(defined('PILOT_ANGULAR_CALL'))?'ng-app="pilot"':''?>>
<head>
	<title><?=$this->title?></title>
    <?=$this->addMeta('robots','index,folow')?>
    <?=$this->SEOSERVICE?>
   	<? $this->render('meta'); ?>
    <script type="text/javascript">
    	$(function(){
			var slideMenu 	= $('#content .slideMenu');
			var closeNum 	= slideMenu.width() - 58;
			var isSlideOut 	= getMenuState();
			var prePressed = false;

			$(document).keyup(function(e){
				var key = e.keyCode;
				if(key === 17){
					prePressed = false;
				}
			});

			$(document).keydown(function(e){
				var key = e.keyCode;
				var keyUrl = new Array();
					keyUrl[49] = '/'; keyUrl[97] = '/';
					keyUrl[50] = '/termekek'; keyUrl[98] = '/termekek';
					keyUrl[51] = '/reklamfal'; keyUrl[99] = '/reklamfal';
					keyUrl[52] = '/menu'; keyUrl[100] = '/menu';
					keyUrl[53] = '/oldalak'; keyUrl[101] = '/oldalak';
					keyUrl[54] = '/kategoriak'; keyUrl[102] = '/kategoriak';
					keyUrl[55] = '/markak'; keyUrl[103] = '/markak';
				if(key === 17){
					prePressed = true;
				}
				if(typeof keyUrl[key] !== 'undefined'){
					if(prePressed){
						//document.location.href=keyUrl[key];
					}
				}
			});

			if(isSlideOut){
				slideMenu.css({
					'left' : '0px'
				});
				$('.ct').css({
					'paddingLeft' : '220px'
				});
			}else{
				slideMenu.css({
					'left' : '-'+closeNum+'px'
				});
				$('.ct').css({
					'paddingLeft' : '75px'
				});
			}

			$('.slideMenuToggle').click(function(){
				if(isSlideOut){
					isSlideOut = false;
					slideMenu.animate({
						'left' : '-'+closeNum+'px'

					},200);
					$('.ct').animate({
						'paddingLeft' : '75px'
					},200);
					saveState('closed');
				}else{
					isSlideOut = true;
					slideMenu.animate({
						'left' : '0px'
					},200);
					$('.ct').animate({
						'paddingLeft' : '220px'
					},200);
					saveState('opened');
				}
			});
		})

		function saveState(state){
			if(typeof(Storage) !== "undefined") {
				if(state == 'opened'){
					localStorage.setItem("slideMenuOpened", "1");
				}else if(state == 'closed'){
					localStorage.setItem("slideMenuOpened", "0");
				}
			}
		}

		function getMenuState(){
			var state =  localStorage.getItem("slideMenuOpened");

			if(typeof(state) === null){
				return false;
			}else{
				if(state == "1") return true; else return false;
			}
		}
    </script>
</head>
<body class="<? if(!$this->adm->logged): ?>blured-bg<? endif; ?>" ng-app="Moza">
<div id="top" class="container-fluid">
	<div class="row" style="margin: 0 -15px;">
		<? if(!$this->adm->logged): ?>
		<div class="col-md-12 center">&nbsp;</div>
		<? else: ?>
    	<div class="col-md-7 left">
    		<img height="58" class="top-logo" src="<?=IMG?>moza_motivum.svg" alt="<?=TITLE?>">
    		<div class="link">
    			<a href="<?=HOMEDOMAIN?>" target="_blank"><strong><?php echo $this->settings['page_title']; ?></strong> &mdash; <?php echo $this->settings['page_description']; ?></a>
    		</div>
    	</div>

        <div class="col-md-5" align="right">
        	<div class="shower">
            	<i class="fa fa-user"></i>
            	<?=$this->adm->admin?>
                <i class="fa fa-caret-down"></i>
                <div class="dmenu">
                	<ul>
                		<li><a href="/home/exit">Kijelentkezés</a></li>
                	</ul>
                </div>
            </div>
        	<div class="shower no-bg">
        		<a href="<?=FILE_BROWSER_IMAGE?>" data-fancybox-type="iframe" class="iframe-btn">Galéria <i class="fa fa-picture-o"></i></a>
            </div>
        </div>
        <? endif; ?>
    </div>
</div>
<!-- Login module -->
<? if(!$this->adm->logged): ?>
<div id="login" class="container-fluid">
  <div class="row justify-content-md-center">
    <div class=" col-md-6 center">
      <img src="<?=IMG?>moza_logo_hu.svg" alt="">
    </div>
  </div>
  <br><br>
	<div class="row justify-content-md-center">
	    <div class="bg col-md-6">
	    	<h3>Bejelentkezés</h3>
            <? if($this->err){ echo $this->bmsg; } ?>
            <form action="/" method="post">
	            <div class="input-group">
      	        <span class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user"></i></span></span>
      				  <input type="text" class="form-control" name="user">
      				</div>
                <br>
                <div class="input-group">
	              <span class="input-group-prepend"><span class="input-group-text"><i class="fa fa-lock"></i></span></span>
				  <input type="password" class="form-control" name="pw">
				</div>
                <br>
                <div class="left links"><a href="<?=HOMEDOMAIN?>"><i class="fa fa-angle-left"></i> www.<?=str_replace(array('https://','www.'), '', $this->settings['page_url'])?></a></div>
                <div align="right"><button name="login" class="btn btn-warning">Bejelentkezés <i class="fa fa-arrow-circle-right"></i></button></div>
            </form>

	    </div>
    </div>
</div>
<? endif; ?>
<!--/Login module -->
<div id="content">
<div class="container-fluid">
	<? if($this->adm->logged): ?>
    <div class="slideMenu">
    	<div class="slideMenuToggle" title="Kinyit/Becsuk"><i class="fa fa-arrows-h"></i></div>
      <div class="clr"></div>
   		<div class="menu">
        	<ul>
            	<li class="<?=($this->gets[0] == 'home')?'on':''?>"><a href="/" title="Dashboard"><span class="ni">1</span><i class="fa fa-life-saver"></i> Dashboard</a></li>
              <li class="<?=($this->gets[0] == 'ajanlatkeresek')?'on':''?>"><a href="/ajanlatkeresek" title="Ajánlatkérések"><span class="ni">2</span><i class="fa fa-edit"></i> Ajánlatkérések</a></li>
              <?php if (false): ?>
                <li class="<?=($this->gets[0] == 'emails')?'on':''?>"><a href="/emails" title="Email sablonok"><span class="ni">8</span><i class="fa fa-envelope"></i> Email sablonok</a></li>
              <?php endif; ?>
              <li class="<?=($this->gets[0] == 'beallitasok')?'on':''?>"><a href="/beallitasok" title="Beállítások"><span class="ni">8</span><i class="fa fa-gear"></i> Beállítások</a></li>
              <li class="div"></li>
              <!-- MODULS-->
              <?php if ( !empty($this->modules) ): ?>
              <li class="div"></li>
              <?php foreach ($this->modules as $module): ?>
              <li class="<?=($this->gets[0] == $module['menu_slug'])?'on':''?>"><a href="/<?=$module['menu_slug']?>" title="<?=$module['menu_title']?>"><span class="ni"><?=$module['ID']?></span><i class="fa fa-<?=$module['faico']?>"></i> <?=$module['menu_title']?></a></li>
              <?php endforeach; ?>
              <?php endif; ?>
              <!-- End of MODULS-->
              <li class="<?=($this->gets[0] == 'kategoriak')?'on':''?>"><a href="/kategoriak" title="Kategóriák"><span class="ni">6</span><i class="fa fa-bars"></i> Kategóriák</a></li>
              <li class="<?=($this->gets[0] == 'szinek')?'on':''?>"><a href="/szinek" title="Színek"><span class="ni">8</span><i class="fa fa-th"></i> Színek</a></li>
              <li class="<?=($this->gets[0] == 'motivumjaim')?'on':''?>"><a href="/motivumjaim" title="Motívumok"><span class="ni">8</span><i class="fa fa-stop"></i> Motívumok</a></li>
              <li class="<?=($this->gets[0] == "motivumconfig")?'on':''?>"><a href="/motivumconfig" title="Saját motívum beállítások"><span class="ni">8</span><i class="fa fa-stop"></i> Saját minták</a></li>
        	</ul>
        </div>
    </div>
    <? endif; ?>
    <div class="ct">
    	<div class="innerContent">
