<div class="dashboard">
<? if($this->adm->logged): ?>
<div class="alert-board">
    <? if( count( $this->unused_images ) > 0): ?>
    <div class="item">
        <div class="head"><?=count( $this->unused_images )?> db használaton kívüli termékkép</div>
        <div class="sl"><a href="/beallitasok/clearimages">képek megtekintéséhez ide kattintson</a></div>
    </div>
    <? endif; ?>
</div>

<h1>Dashboard</h1>
<div class="clr"></div>
	<? if(true): ?>
  <div class="row">
    <div class="col-md-4">
      <div class="box orange">
          <div class="head">
              <div class="btn"><a href="/megrendelesek/"><i class="fa fa-arrow-circle-right"></i></a></div>
              <div class="n"><?=$this->stats[orders][news][db]?></div>
                <div class="txt">
                  <div>új megrendelés</div>
                    <div>még nem feldolgozott</div>
                </div>
            </div>
            <div class="c">
              <? if($this->stats[orders][news][db] > 0): ?>
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=Helper::cashFormat($this->stats[orders][news][ar])?> FT</strong></div>
                    <div class="col-md-8 title">össz. értékben</div>
                </div>
                <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=$this->stats[orders][news][tetel]?> DB</strong></div>
                    <div class="col-md-8 title">össz. tétel</div>
                </div>
                <? else: ?>
                <div class="noItem">
                  Nincs feldolgozatlan megrendelés
                </div>
                <? endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
      <div class="box green">
          <div class="head">
              <div class="btn"><a href="/megrendelesek/"><i class="fa fa-arrow-circle-right"></i></a></div>
              <div class="n"><?=$this->stats[orders][progress][db]?></div>
              <div class="txt">
                  <div>megrendelés</div>
                  <div>folyamatban</div>
              </div>
          </div>
          <div class="c">
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=Helper::cashFormat($this->stats[orders][progress][ar])?> FT</strong></div>
                  <div class="col-md-8 title">össz. értékben</div>
              </div>
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=$this->stats[orders][progress][tetel]?> DB</strong></div>
                  <div class="col-md-8 title">össz. tétel</div>
              </div>
          </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box blue">
          <div class="head">
              <div class="btn"><a href="/felhasznalok/"><i class="fa fa-arrow-circle-right"></i></a></div>
              <div class="n"><?=number_format($this->stats[users][regInThisMonth],0,"",",")?></div>
              <div class="txt">
                  <div>új felhasználó</div>
                  <div>ebben a hónapban</div>
              </div>
          </div>
          <div class="c">
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=number_format($this->stats[users][loginInThisWeek],0,"",",")?> DB</strong></div>
                  <div class="col-md-8 title">belépett az elmúlt héten</div>
              </div>
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=number_format($this->stats[users][activated],0,"",",")?> DB</strong></div>
                  <div class="col-md-8 title">aktivált felhasználó</div>
              </div>
              <div class="row">
                  <div class="col-md-4 bgtxt"><strong><?=number_format($this->stats[users][total],0,"",",")?> DB</strong></div>
                  <div class="col-md-8 title">összesen</div>
              </div>
          </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
        <? if(true): ?>
        <div class="box">
            <div class="head">
                <div class="n"><?=count($this->stats[lastMessages][data])?></div>
                <div class="btn"><a href="/uzenetek"><i class="fa fa-arrow-circle-right"></i></a></div>
                <div class="txt">
                    <div><strong>beérkezett</strong> üzenet</div>
                    <div>interakcióra vár</div>
                </div>
            </div>
            <div class="c">
                <div class="row">
                  <div class="col-md-2">Feladó</div>
                  <div class="col-md-3">Téma</div>
                  <div class="col-md-3  center">Időpont</div>
                  <div class="col-md-4">Kapcsolt elem</div>
                </div>
                <? if(count($this->stats[lastMessages][data]) > 0): ?>
                <? foreach($this->stats[lastMessages][data] as $d): ?>
                <div class="row">
                    <div class="col-md-2">
                        <?=$d[felado_nev]?>
                    </div>
                    <div class="col-md-3">
                        <a href="/uzenetek/msg/<?=$d[ID]?>"><strong>
                        <span>
                        <?php
                          switch($d[tipus]){
                            case 'recall':
                                echo '<i style="width:15px;" class="fa fa-phone"></i>';
                            break;
                            case 'requesttermprice':
                                echo '<i style="width:15px;" class="fa fa-archive"></i>';
                            break;
                            case 'ajanlat':
                                echo '<i style="width:15px;" class="fa fa-file"></i>';
                            break;
                          }
                        ?>
                        </span>
                        <?=$d[uzenet_targy]?></strong></a>
                    </div>
                    <div class="col-md-3 center" style="font-size:12px;">
                        <?=\PortalManager\Formater::dateFormat($d[elkuldve], $this->settings['date_format'])?> (<?=Helper::distanceDate($d[elkuldve])?>)
                    </div>
                    <div class="col-md-4 left">
                        <? if($d[item_id]): ?>
                          <a title="Termék adatlapja" target="_blank" href="<?php echo $d['item_url']; ?>"><strong><?=$d[item_nev]?></strong></a><br>
                          <a href="/termekek/t/edit/<?=$d[item_id]?>">Termék szerkesztése >></a>
                        <?php else: ?>
                          &mdash;
                        <? endif; ?>
                    </div>
                </div>
                <? endforeach; ?>
                <? else: ?>
                    <div class="noItem">
                        Nincs új üzenet!
                    </div>
                <? endif; ?>
            </div>
        </div>
        <? endif; ?>
    </div>

    <?php if (false): ?>
    <div class="col-md-4">
         <div class="box">
            <div class="head">
                <div class="btn"><a href="/forgalom"><i class="fa fa-arrow-circle-right"></i></a></div>
                <div class="txt">
                    <div><h4><i class="fa fa-money"></i> <strong>FORGALOM</strong> ADATOK</h4></div>
                </div>
            </div>
            <div class="c traffic-info" style="padding:0;">
                <div class="box-head">Ebben a hónapban</div>
                <div class="row clear">
                    <div class="col-md-6" style="border-right:1px solid #ddd;" align="center">
                        <div class="traffic-price price-income">
                            <?
                                $x1 = $this->tafficInfo[latest][in];
                                $x2 = $this->tafficInfo[latest][out];
                            ?>
                            <div class="stat <?=($x2 < $x1)?'p':'n'?>"><span><?=Helper::getPercent($x1,$x1-$x2)?>%</span></div>
                            <div class="price"><span><?=Helper::cashFormat($x1)?></span></div>
                            <div class="txt">bevétel</div>
                        </div>
                    </div>
                    <div class="col-md-6" align="center">
                        <div class="traffic-price price-outgo">
                            <div class="price"><span><?=Helper::cashFormat($x2)?></span></div>
                            <div class="txt">kiadás</div>
                        </div>
                    </div>
                </div>
                <div class="divider clear"></div>
                <?
                    $x1 = $this->tafficInfo[all][in];
                    $x2 = $this->tafficInfo[all][out];
                ?>
                <div class="box-head">Összesen</div>
                <div class="row clear">
                    <div class="col-md-6" style="border-right:1px solid #ddd;"  align="center">
                        <div class="traffic-price price-income">
                            <div class="stat <?=($x2 < $x1)?'p':'n'?>"><span><?=Helper::getPercent($x1,$x1-$x2)?>%</span></div>
                            <div class="price"><span><?=Helper::cashFormat($x1)?></span></div>
                            <div class="txt">bevétel</div>

                        </div>
                    </div>
                    <div class="col-md-6"  align="center">
                        <div class="traffic-price price-outgo">
                            <div class="price"><span><?=Helper::cashFormat($x2)?></span></div>
                            <div class="txt">kiadás</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="row">
	 <div class="col-md-4">
    	<div class="box">
        	<div class="head">
            	<div class="btn"><a title="Teljes lista megtekintése" href="/stat/termek"><i class="fa fa-arrow-circle-right"></i></a></div>
                <div class="txt">
                	<div><h4><i class="fa fa-eye"></i> <strong>TERMÉK NÉZETTSÉG</strong> STATISZTIKA</h4></div>
                </div>
            </div>
            <div class="c">
            	<? foreach($this->stats[termekView][data] as $d): ?>
                 <div class="row">
                	<div class="col-md-3"><strong><?=$d[me]?></strong> <span title="Oldal betöltés / Megjelenés"><i class="fa fa-eye"></i></span></div>
                    <div class="col-md-9 title">
                        <strong><a href="/termekek/t/edit/<?=$d[termekID]?>"><?=$d[nev]?></a></strong> &nbsp; <a href="<?=HOMEDOMAIN.'termek/'.\PortalManager\Formater::makeSafeUrl($d['nev'],'_-'.$d['termekID'])?>" target="_blank" style="color:black;" title="Publikus adatlap"><i class="fa fa-external-link"></i></a>
                        <div class="stat-feat-info">
                          <?=($d['szin'])? '<span class="text">Variáció:</span>'.$d['szin'] :'' ?>
                          <?=($d['meret'])? '; <span class="text">Kiszerelés:</span>'.$d['meret'] :'' ?>
                        </div>
                    </div>
                </div>
                <? endforeach; ?>
                <div class="divider"></div>
                <div class="row">
                	<div class="col-md-12 center">
                    	<strong><?=$this->stats[termekView][total]?> db</strong> termék érdekelt ebben a hónapban.
                    </div>
                </div>
            </div>
        </div>
    </div>

  	<div class="col-md-4">
      	<div class="box">
          	<div class="head">
              	<div class="btn"><a title="Teljes lista megtekintése"  href="/stat/kereses"><i class="fa fa-arrow-circle-right"></i></a></div>
                  <div class="txt">
                  	<div><h4><i class="fa fa-search"></i> <strong>TERMÉK KERESÉS</strong> STATISZTIKA</h4></div>
                  </div>
              </div>
              <div class="c">
                <?php if (count($this->stats[search][data]) == 0): ?>
                  <div class="no-item">
                    Jelenleg nincs rendelkezésre álló adat.
                  </div>
                <?php else: ?>
                  <? foreach($this->stats[search][data] as $d): ?>
                   <div class="row">
                  	<div class="col-md-3"><strong><?=$d[me]?>x</strong></div>
                      <div class="col-md-9 title"><?=$d[szoveg]?></div>
                  </div>
                  <? endforeach; ?>
                <?php endif; ?>
                <div class="divider"></div>
                <div class="row">
                	<div class="col-md-12 center">
                    	<strong><?=$this->stats[search][total]?> db</strong> keresési kulcsszó összesen ebben a hónapban.
                    </div>
                </div>
              </div>
          </div>
      </div>

      <div class="col-md-4">
      	<div class="box">
          	<div class="head">
              	<div class="btn"><a title="Teljes lista megtekintése"  href="/stat/kategoria"><i class="fa fa-arrow-circle-right"></i></a></div>
                  <div class="txt">
                  	<div><h4><i class="fa fa-bars"></i> <strong>KATEGÓRIA NÉZETTSÉG</strong> STATISZTIKA</h4></div>
                  </div>
              </div>
              <div class="c">
                <?php if (count($this->stats[kategoria][data]) == 0): ?>
                  <div class="no-item">
                    Jelenleg nincs rendelkezésre álló adat.
                  </div>
                <?php else: ?>
                  <? foreach($this->stats[kategoria][data] as $d): ?>
                   <div class="row">
                  	<div class="col-md-3"><strong><?=$d[me]?></strong> <span title="Oldal betöltés / Megjelenés"><i class="fa fa-eye"></i></span></div>
                      <div class="col-md-9 title"><a target="_blank" href="http://www.<?=str_replace(array('www.','http://'),'',rtrim($this->settings['page_url'],'/')).'/termekek/'.\PortalManager\Formater::makeSafeUrl($d['kategoriaNev'],'_-'.$d['kategoriaID'])?>"><?=$d['kategoriaNev']?></a></div>
                  </div>
                  <? endforeach; ?>
                <?php endif; ?>
                <div class="divider"></div>
                <div class="row">
                	<div class="col-md-12 center">
                    	<strong><?=$this->stats[kategoria][total]?> db</strong> kategória érdekelt ebben a hónapban.
                    </div>
                </div>
              </div>
          </div>
      </div>
  </div>
  <? endif; ?>
<? endif;?>
</div>
