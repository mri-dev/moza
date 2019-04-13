<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/html4"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" lang="hu-HU">
<head>
    <title><?=$this->title?></title>
    <?=$this->addMeta('robots','index,folow')?>
    <?=$this->SEOSERVICE?>
    <?php if ( $this->settings['FB_APP_ID'] != '' ): ?>
    <meta property="fb:app_id" content="<?=$this->settings['FB_APP_ID']?>" />
    <?php endif; ?>
    <? $this->render('meta'); ?>
</head>
<body class="<?=$this->bodyclass?>" ng-app="Moza" ng-controller="App" ng-init="init()">
<? if(!empty($this->settings[google_analitics])): ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', ' <?=$this->settings[google_analitics]?>', 'auto');
  ga('send', 'pageview');
</script>
<? endif; ?>
<header>
  <div class="logo">
    <div class="in">
      <img src="<?=IMG?>moza_logo_hu.svg" alt="MOZA">
    </div>
  </div>
  <div class="backurl">
    <div class="in">
      <?php if ($this->sessionpage): ?>
        <a href="/"> <i class="fa fa-angle-left"></i> <?php echo __('vissza a moza cementlap tervezőbe'); ?></a>
      <?php else: ?>
        <a href="http://www.moza.hu"> <i class="fa fa-angle-left"></i> <?php echo __('vissza a moza cementlap manufaktúra weboldalára'); ?></a>
      <?php endif; ?>
    </div>
  </div>
  <div class="title">
    <div class="in">
      <?php if ($this->sessionpage): ?>
          <?php echo $this->order['orderer_name'].' '.__('ajánlatkérése'); ?>
      <?php else: ?>
        <?php echo __('Cementlap tervező program'); ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="langs">
    <div class="in">
      <div class=""><a class="<?=(Lang::getLang()=='hu')?'active':''?>" href="/?setlang=hu"><img src="<?=IMG?>icons/flag_hu.png" alt="Magyar"> Magyar</a></div>
      <div class=""><a class="<?=(Lang::getLang()=='en')?'active':''?>" href="/?setlang=en"><img src="<?=IMG?>icons/flag_en.png" alt="English"> English</a></div>
    </div>
  </div>
</header>
<div class="sidebar">
  <?php if ($this->sessionpage): ?>
    <div class="kat-title">
      <?php echo __('Ajánlatkérések'); ?>:
      <div class="subtitle">
        <?=$this->order['orderer_email']?>
      </div>
    </div>
    <div class="cat-list">
      <?php foreach ((array)$this->allorders as $o): ?>
        <div class="cat<?=($o['hashkey'] == $this->order['hashkey'])?' active':''?>">
          <div class="title"><a href="/sessions/<?=$o['hashkey']?>">#<?=$o['ID']?> <?=date('Y-m-d', strtotime($o['idopont']))?></a></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="kat-title">
      <?php echo __('Kategóriák'); ?>
    </div>
    <div class="cat-list">
      <div class="cat" ng-class="(aktiv_kat==cat.ID)?'active':''" ng-repeat="cat in kategoria_lista">
        <div class="title" ng-click="changeKat(cat.ID)">
          {{cat.neve}}
        </div>
        <div class="motifs" ng-show="(aktiv_kat==cat.ID && kategoriak[cat.hashkey].length != 0)">
          <div class="motiv" ng-repeat="m in kategoriak[cat.hashkey]">
            <div class="wrapper" title="{{m.mintakod}}" ng-click="pickNewMotiv(m)">
              <motivum kod="m.mintakod" shapes="m.shapes"></motivum>
            </div>
          </div>
        </div>
      </div>
      <div class="cat" ng-class="(aktiv_kat==cat.ID)?'active':''">
        <div class="title unique" ng-click="changeKat(cat.ID)">
          <?php echo __('Előszínezett lapok'); ?> <i class="fa fa-adjust"></i>
        </div>
        <div class="motifs" ng-show="(aktiv_kat==cat.ID && kategoriak['OWN'].length != 0)">
          <div class="motiv" ng-repeat="m in kategoriak['OWN']">
            <div class="wrapper" title="[{{m.mintakod}}] {{m.nev}}" ng-click="pickNewMotiv(m)">
              <motivum kod="m.mintakod" shapes="m.shapes"></motivum>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <div class="copy">
    <?php echo __('Minden jog fenntartva!'); ?>
    <div class="creator">
      <a href="https://www.web-pro.hu/?ref=moza" target="_blank">powered by WEBPRO</a>
    </div>
  </div>
</div>
