<h1><u><?=$this->order[orderer_name]?></u> ajánlatának adminisztrálása
  <?php if ($this->order['welldone'] == 1): ?><span class="label label-success">Sikeres ajánlat</span> <? endif; ?>
  <?php if ($this->order['archivalt'] == 1): ?><span class="label label-danger">Archiválva</span> <? endif; ?>
</h1>
<form action="" method="post" class="sessioneditor">
  <div class="row">
    <div class="col-md-12 right">
      <div class="left">
      <?php echo $this->bmsg; ?>
      </div>
      <input type="submit" class="btn btn-success" name="saveSession" value="Változások mentése">
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          Ajánlat adatok szerkesztése
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              Azonosító
            </div>
            <div class="col-md-8">
              <strong><a title="Adatlap megtekintése" href="<?=HOMEDOMAIN?>sessions/<?=$this->order[hashkey]?>" target="_blank"><?=$this->order[hashkey]?></a></strong>
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-4">
              Időpont
            </div>
            <div class="col-md-8">
              <strong><?=$this->order[idopont]?></strong>
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-4">
              <label for="orderer_name">Név</label>
            </div>
            <div class="col-md-8">
              <input type="text" id="orderer_name" class="form-control" name="order[orderer_name]" value="<?=$this->order[orderer_name]?>">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-4">
              <label for="orderer_phone">Telefonszám</label>
            </div>
            <div class="col-md-8">
              <input type="text" id="orderer_phone" class="form-control" name="order[orderer_phone]" value="<?=$this->order[orderer_phone]?>">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-4">
              <label for="orderer_email">E-mail cím</label>
            </div>
            <div class="col-md-8">
              <input type="text" id="orderer_email" class="form-control" name="order[orderer_email]" value="<?=$this->order[orderer_email]?>">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-12">
              <label for="admin_megjegyzes">Admin megjegyzés (ajánlatkérő nem látja)</label>
              <textarea id="admin_megjegyzes" name="admin_megjegyzes" class="form-control"><?=$this->order[admin_megjegyzes]?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          Mennyiségek szerkesztése
        </div>
        <div class="card-body">
          <div class="config">
            <?php
            $motifs = $this->order['motifs'];
            $previews = array();
            foreach ((array)$motifs as $m):
              $me_db = (int)$m['me_db'];
              $me_nm = (int)$m['me_nm'];

              if (!array_key_exists($m['hashid'],$previews)) {
                $previews[$m['hashid']] = array(
                  'img' => $m['preview_code'],
                  'minta' => $m['minta']
                );
              }
            ?>
            <div class="each">
              <div class="mot">
                <img src="<?=$m['preview_code']?>" width="80" height="80" alt="Minta: <?=$m['minta']?>">
              </div>
              <div class="colors">
                <div class="wrapper">
                  <div class="h">Színkonfiguráció:</div>
          				<?php foreach ((array)$m['szinek'] as $c): ?>
          					<div class="col">
          						<span class="color-preview" style="display: block; float: left; width: 20px; height: 20px; background:<?=$c['rgb']?>;">&nbsp;</span>&nbsp;
          						<strong><?=$c['obj']['kod']?></strong> - <?=$c['obj']['neve']?> &bull; <?=$c['obj']['szin_ncs']?>
          					</div>
          					<div class="clr"></div>
          				<?php endforeach; ?>
                </div>
              </div>
              <div class="data">
                <div class="wrapper">
                  <div class="minta">
                    Minta: <strong><?=$m['minta']?></strong>
                  </div>
                  <div class="qty">
                    <div class="me">Darab: <input type="number" class="form-control" name="items[<?=$m['ID']?>][me_db]" value="<?=$me_db?>"></div>
            				<div class="me">Négyzetméter: <input type="number" step="0.01" class="form-control" name="items[<?=$m['ID']?>][me_nm]" value="<?=$me_nm?>"></div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
</div>
</form>
<br>
<form class="" action="" method="post">
  <div class="row">
    <div class="col-md-12">
      <div class="card border-warning">
        <div class="card-header">Műveletek</div>
        <div class="card-body">
          <?php if ($this->order['archivalt'] == 0): ?>
            <input type="submit" class="btn btn-danger" name="archiveSession" value="Ajánlat archiválása">
          <?php else: ?>
            <input type="submit" class="btn btn-danger" name="archiveSession" value="Ajánlat archiválva: OK" disabled="disabled">
          <?php endif; ?>
          <?php if ($this->order['welldone'] == 0): ?>
            <input type="submit" class="btn btn-success" name="welldoneSession" value="Sikeres ajánlatnak jelölés">
          <?php else: ?>
            <input type="submit" class="btn btn-success" name="welldoneSession" value="Sikeres ajánlatnak jelölve: OK" disabled="disabled">
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>
