<div class="sessionpage inside-content">
  <div class="row">
    <div class="col-md-6">
      <div class="block">
        <h3><?=__('Ajánlatkérő adatai')?></h3>
        <div class="data-rows">
          <div class="row">
            <div class="col-md-4"><?=__('Név')?></div>
            <div class="col-md-8"><strong><?=$this->order['orderer_name']?></strong></div>
          </div>
          <div class="row">
            <div class="col-md-4"><?=__('E-mail')?></div>
            <div class="col-md-8"><strong><?=$this->order['orderer_email']?></strong></div>
          </div>
          <div class="row">
            <div class="col-md-4"><?=__('Telefonszám')?></div>
            <div class="col-md-8"><strong><?=$this->order['orderer_phone']?></strong></div>
          </div>
          <div class="row">
            <div class="col-md-12">
              &mdash;
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
            <?=__('Ajánlatkérés ideje')?></div>
            <div class="col-md-8"><strong><?=$this->order['idopont']?></strong></div>
          </div>
          <div class="row">
            <div class="col-md-4"><?=__('Azonosító')?></div>
            <div class="col-md-8"><strong><?=$this->order['hashkey']?></strong></div>
          </div>
        </div>
      </div>
      <div class="block">
        <h3><?=__('Konfiguráció összesítő')?></h3>
        <div class="config">
          <?php
          $motifs = $this->order['motifs'];
          $previews = array();
          foreach ((array)$motifs as $m):
            $me_db = (float)$m['me_db'];
            $me_nm = (float)$m['me_nm'];

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
            <div class="data">
              <div class="wrapper">
                <div class="minta">
                  <?=__('Minta:')?> <strong><?=$m['minta']?></strong>
                </div>
                <div class="qty">
                  <div class="me"><?=__('Darab')?>: <strong><?=$me_db?></strong></div>
          				<div class="me"><?=__('Négyzetméter')?>: <strong><?=$me_nm?></strong></div>
                </div>
              </div>
            </div>
            <div class="colors">
              <div class="wrapper">
                <div class="h"><?=__('Színkonfiguráció')?>:</div>
        				<?php foreach ((array)$m['szinek'] as $c): ?>
        					<div class="col">
        						<span class="color-preview" style="display: block; float: left; width: 20px; height: 20px; background:<?=$c['rgb']?>;">&nbsp;</span>&nbsp;
        						<strong><?=$c['obj']['kod']?></strong> - <?=$c['obj']['neve']?> &bull; <?=$c['obj']['szin_ncs']?>
        					</div>
        					<div class="clr"></div>
        				<?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="block">
        <h3><?=__('Minta előnézet')?></h3>
        <table class="preview" width="100%" border="1" style="border-collapse:collapse;" cellpadding="10" cellspacing="0">
        	<tbody style="color:#888;">
        		<?php for($x = 0; $x < (int)$this->order['grid_x']; $x++){ ?>
        		<tr>
        			<?php for($y = 0; $y < (int)$this->order['grid_y']; $y++){
        					$key = $this->order['gridconfig'][$x.'x'.$y]['hashid'];
                  $rotate = (int)$this->order['gridconfig'][$x.'x'.$y]['rotation'];
        			?>
        			<td style="width: calc(100% / <?=(int)$this->order['grid_y']?>);">
                <?php if ($previews[$key]['img']): ?>
                  <img src="<?=$previews[$key]['img']?>" alt="Minta: <?=$previews[$key]['minta']?>" style="transform:rotate(<?=$rotate?>deg);">
                <?php else: ?>
                  <img src="//via.placeholder.com/102/ffffff/eaeaea/?text=MOZA" alt="empty">
                <?php endif; ?>
        			</td>
        			<? } ?>
        		</tr>
        		<? } ?>
        	</tbody>
        </table>
      </div>
    </div>
  </div>
</div>
