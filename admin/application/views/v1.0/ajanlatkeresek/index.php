<?php if ($this->is_archivalt_list): ?>
  <h1>Archivált ajánlatkérések</h1>
<?php else: ?>
  <h1>Ajánlatkérések</h1>
<?php endif; ?>
<?php
  $get = $_GET;
  unset($get['tag']);
?>
<div class="row">
  <div class="col-md-12">
    <form class="" action="/ajanlatkeresek<?=($this->is_archivalt_list)?'/archivalt':''?>" method="get">
      <div class="card filters">
        <div class="card-body">
          <h5><i class="fa fa-filter"></i> Szűrők</h5>
          <div class="divider"></div><br>
          <div class="row">
            <div class="col-md-2">
              <?php if ($this->is_archivalt_list): ?>
                <a class="btn btn-default" href="/ajanlatkeresek?<?=http_build_query($get)?>"><i class="fa fa-circle-o"></i> Aktuális ajánlatkérések</a>
              <?php else: ?>
                <a class="btn btn-default" href="/ajanlatkeresek/archivalt?<?=http_build_query($get)?>"><i class="fa fa-archive"></i> Archivált ajánlatkérések</a>
              <?php endif; ?>
            </div>
            <div class="col-md-8">
              <input type="text" class="form-control" name="src" value="<?=$_GET["src"]?>" placeholder="Keresés...">
            </div>
            <div class="col-md-2 right">
              <button type="submit" class="btn btn-dark">KERESÉS <i class="fa fa-search"></i></button>
            </div>
          </div>
        </div>
      </div>
    </form>
    <br>
    <div class="card bg-primary">
      <div class="card-header text-white"><i class="fa fa-file-text-o"></i> Leadott ajánlatkérések</div>
      <div class="card-body bg-white" style="padding: 0;">
        <div class="card-item-holder">
          <div class="row row-head">
            <div class="col-md-5">
              Név / Email
            </div>
            <div class="col-md-2 center">
              Telefonszám
            </div>
            <div class="col-md-1 center">
              Megtekintve
            </div>
            <div class="col-md-1 center">
              Archivált
            </div>
            <div class="col-md-2 center">
              Időpont
            </div>
            <div class="col-md-1 center"></div>
          </div>
          <?php if (count((array)$this->orders) == 0): ?>
          <div class="row">
            <div class="col-md-12 center">
              <div class="no-items">
                Nincs találat.
              </div>
            </div>
          </div>
          <?php endif; ?>
          <?php foreach ( (array)$this->orders as $o ):
            $state = 'untouched';
            if ($o['megtekintve'] != '') {
              $state = 'touched';
            }
            if ($o['archivalt'] == '1') {
              $state = 'archived';
            }
            if ($o['welldone'] == '1') {
              $state = 'welldone';
            }
          ?>
          <div class="row <?=$state?>">
            <div class="col-md-5">
              <strong><a target="_blank" href="<?=HOMEDOMAIN?>sessions/<?=$o['hashkey']?>?av=1" title="Adatlap"><?=$o['orderer_name']?></a></strong><br>
              <?=$o['orderer_email']?> <?=($o['admin_megjegyzes'] == '')?'':'&nbsp;&nbsp;<i title="Admin megjegyzés" class="fa fa-comment"></i>'?>
            </div>
            <div class="col-md-2 center">
              <?=$o['orderer_phone']?>
            </div>
            <div class="col-md-1 center">
              <?=(is_null($o['megtekintve']))?'<i class="fa fa-eye-slash"></i>':$o['megtekintve']?>
            </div>
            <div class="col-md-1 center">
              <?=($o['archivalt']=='0')?'<i class="fa fa-circle-o"></i>':'<i class="fa fa-check-circle-o"></i>'?>
            </div>
            <div class="col-md-2 center">
              <?=$o['idopont']?>
            </div>
            <div class="col-md-1 center">
              <a href="/ajanlatkeresek/edit/<?=$o['ID']?>"><i class="fa fa-pencil"></i></a> &nbsp;
              <a href="javascript:voit(0);" onclick="$('#page<?=$o['hashkey']?>').slideToggle(400);"><i class="fa fa-arrow-down"></i></a>
            </div>
          </div>
          <div class="row more-details" id="page<?=$o['hashkey']?>">
            <div class="col-md-12">
              <div class=""><strong>ADMIN MEGJEGYZÉS</strong></div>
              <div class="comment">
                <?=($o['admin_megjegyzes'] == '')?'- nincs megjegyzés -':$o['admin_megjegyzes']?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <br>
    <div class="card">
      <div class="card-header"><i class="fa fa-info-circle"></i> Jelmagyarázat</div>
      <div class="card-body">
        <div class="jel-info">
          <div class="background untouched">Háttérszín - Új ajánlat</div>
          <div class="background touched">Háttérszín - Látott ajánlat</div>
          <div class="background welldone">Háttérszín - Sikeresen lezárt ajánlat</div>
          <div class="background archived">Háttérszín - Archivált ajánlat</div>
          <div class="clr"></div>
          <div class="icon"><i class="fa fa-comment"></i> Admin megjegyzés</div>
          <div class="icon"><i class="fa fa-eye-slash"></i> Nem megtekintett ajánlat</div>          
          <div class="icon"><i class="fa fa-arrow-down"></i> Részletek</div>
          <div class="icon"><i class="fa fa-circle-o"></i> Nincs archiválva</div>
          <div class="icon"><i class="fa fa-check-circle-o"></i> Archiválva</div>
          <div class="icon"><i class="fa fa-pencil"></i> Adminisztráció</div>
        </div>
      </div>
    </div>
  </div>
</div>
