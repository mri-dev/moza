<div class="dashboard">
<? if($this->adm->logged): ?>
  <div class="row">
    <div class="col-md-12">
      <h1>Dashboard</h1>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card bg-warning border-warning">
        <div class="card-header text-white"><i class="fa fa-eye-slash"></i> Még nem láttott ajánlatkérések</div>
        <div class="card-body" style="padding: 0;">
          <div class="card-item-holder">
            <div class="row row-head">
              <div class="col-md-7">
                Név / Email
              </div>
              <div class="col-md-2 center">
                Telefonszám
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
                  Minden ajánlatkérést megtekintett.
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
              <div class="col-md-7">
                <strong><a target="_blank" href="<?=HOMEDOMAIN?>sessions/<?=$o['hashkey']?>?av=1" title="Adatlap"><?=$o['orderer_name']?></a></strong><br>
                <?=$o['orderer_email']?> <?=($o['admin_megjegyzes'] == '')?'':'&nbsp;&nbsp;<i title="Admin megjegyzés" class="fa fa-comment"></i>'?>
              </div>
              <div class="col-md-2 center">
                <?=$o['orderer_phone']?>
              </div>
              <div class="col-md-2 center">
                <?=$o['idopont']?>
              </div>
              <div class="col-md-1 center">
                <a href="/ajanlatkeresek/edit/<?=$o['ID']?>"><i class="fa fa-pencil"></i></a> &nbsp;
                <a href="javascript:voit(0);" onclick="$('#page<?=$o['hashkey']?>').slideToggle(400);"><i class="fa fa-eye"></i></a>
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
    </div>
    <div class="col-md-4" style="display:none;">
      <div class="card">
        <div class="card-header"><i class="fa fa-pie-chart"></i> Statisztika</div>
        <div class="card-body">
          ...
        </div>
      </div>
    </div>
  </div>
<? endif;?>
</div>
