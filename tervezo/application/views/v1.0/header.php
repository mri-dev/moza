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
<body class="<?=$this->bodyclass?>">
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
      <img src="<?=IMG?>moza_logo_hu.svg" alt="">
    </div>
  </div>
  <div class="backurl">
    <div class="in">
      <a href="http://www.moza.hu"> <i class="fa fa-angle-left"></i> <?php echo __('vissza a moza cementlap manufaktúra weboldalára'); ?></a>
    </div>
  </div>
  <div class="title">
    <div class="in">
      <?php echo __('Cementlap tervező program'); ?>
    </div>
  </div>
  <div class="langs">
    <div class="in">
      langs
    </div>
  </div>
</header>
<div class="sidebar">
  <div class="kat-title">
    <?php echo __('Kategóriák'); ?>
  </div>
  <div class="cat-list">
    <?php for ($i=0; $i < 50 ; $i++) {?>
      <div class="">
        Kategória #<?php echo $i; ?>
      </div>
    <? } ?>
  </div>
  <div class="copy">
    <?php echo __('Minden jog fenntartva!'); ?>
  </div>
</div>
