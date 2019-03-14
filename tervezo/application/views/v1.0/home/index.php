<div class="home inside-content">
  <div class="wrapper">
    <div class="side-left">
      <div class="bubble-block block-szinek">
        <div class="header">
          <div class="title">
            <?php echo __('Színek'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          <div class="colors-table">
            <div class="wrapper">
              <div class="color" title="{{color.neve}}" ng-click="changingFillColor(color, color.szin_rgb)" ng-class="(color == changeColorObj)?'selected':''" ng-repeat="color in colors" style="background: #{{color.szin_rgb}};">
                <div class="szinkod" style="color:{{color.szin_felirat}};">{{color.kod}}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="bubble-block block-motivum">
        <div class="header">
          <div class="title">
            <?php echo __('Minta'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          <div class="sample-editor">
            <div class="sample">
              <div id="motivum">
                <div class="alert-msg" ng-show="!currentMotivum">
                  <i class="fa fa-bell-o"></i> <br>
                  <strong><?php echo __('Nincs minta kiválasztva!'); ?></strong><br>
                  <?php echo __('Válasszon egy mintát.'); ?>
                </div>
              </div>
            </div>
            <div class="sample-details">
              <table>
                <tr>
                  <td class="head"><?php echo __('Minta'); ?>:</td>
                  <td class="bigt">{{currentMotivum.mintakod}}</td>
                  <td class="head"><?php echo __('RGB'); ?>:</td>
                  <td><span ng-show="changeColorObj.szin_rgb">#{{changeColorObj.szin_rgb}}</span> </td>
                </tr>
                <tr>
                  <td class="head"><?php echo __('Szín'); ?>:</td>
                  <td class="bigt">{{changeColorObj.kod}}</td>
                  <td class="head"><?php echo __('NCS'); ?>:</td>
                  <td>{{changeColorObj.szin_ncs}}</td>
                </tr>
              </table>
              <div class="rotates">
                <div class="">
                  <button ng-click="rotateWorkMotiv(-90)" type="button" class="btn btn-sm btn-info"><span class="ico"><i class="fa fa-repeat"></i></span> <?php echo __('Forgatás jobbra'); ?></button>
                </div>
                <div class="">
                  <button ng-click="rotateWorkMotiv(90)" type="button" class="btn btn-sm btn-info"><span class="ico"><i class="fa fa-undo"></i></span> <?php echo __('Forgatás balra'); ?></button>
                </div>
              </div>
              <div class="action-buttons">
                <div class="">
                  {{showStrokes}}
                  <button ng-click="toggleBorderOnSample()" type="button" class="btn btn-sm btn-clear"><span class="ico"><i class="fa fa-th"></i></span> <?php echo __('Körvonal megjelenítés'); ?></button>
                </div>
                <div class="">
                  <button ng-click="fillFullGrid()" type="button" class="btn btn-sm btn-clear"><span class="ico"><i class="fa fa-th"></i></span> <?php echo __('Teljes kitöltés'); ?></button>
                </div>
              </div>
            </div>
          </div>
          <div class="divider"></div>
        </div>
        <div class="header">
          <div class="title">
            <?php echo __('HASZNÁLT MINTÁK'); ?>
          </div>
        </div>
        <div class="cwrapper">
          <div class="used-motifs">
            <div class="no-dataset" ng-show="emptyObject(used_motifs)">
              <?php echo __('Nincsenek jelenleg használtban lévő motívumok.'); ?>
            </div>
            <div class="list">
              <div class="shape" ng-repeat="(hash, shape) in used_motifs" ng-class="(usingHistoryHash == hash)?'active':''">
                <div id="shapemotiv{{hash}}" ng-click="loadHistoryMotiv(hash)"></div>
              </div>
            </div>
          </div>
          <div class="divider"></div>
        </div>
        <div class="header">
          <div class="title">
            <?php echo __('HASZNÁLT SZÍNEK'); ?>
          </div>
        </div>
        <div class="cwrapper">
          <div class="used-colors">
            <div class="no-dataset" ng-hide="used_colors.length!=0">
              <?php echo __('Nincsenek jelenleg használtban lévő színek.'); ?>
            </div>
            <div class="colors-table" ng-show="used_colors.length!=0">
              <div class="wrapper">
                <div class="color" ng-repeat="color in used_colors" ng-click="changingFillColor(color, color)" style="background: {{color}};">
                  <div class="szinkod" style="color:;">&nbsp;</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="side-right">
      <div class="bubble-block block-tervezo">
        <div class="header">
          <div class="title">
            <?php echo __('Tervező'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          <div class="saver">
            <div class="saver-email">
              <input type="text" ng-model="saver.email" class="form-control" placeholder="<?php echo __('Adja meg az e-mail címét'); ?>" value="">
            </div>
            <div class="saver-loader">
              <button type="button" class="btn btn-sm btn-default"><span class="ico"><i class="fa fa-refresh"></i></span> <?php echo __('Terveim betöltése'); ?></button>
            </div>
            <div class="saver-list">

            </div>
          </div>
          <div class="divider"></div>
          <div class="actions">
            <div class="button-groups">
              <div class="">
                <button ng-click="resetGrid()" type="button" class="btn btn-sm btn-default"><span class="ico"><i class="fa fa-plus"></i></span> <?php echo __('Új'); ?></button>
              </div>
              <div class="">
                <button type="button" class="btn btn-sm btn-default" ng-click="saveProject()"><span class="ico"><i class="fa fa-download"></i></span> <?php echo __('Mentés'); ?></button>
              </div>
              <div class="">
                <button type="button" class="btn btn-sm btn-default" ng-click="saveProjectAs()"><span class="ico"><i class="fa fa-download"></i></span> <?php echo __('Mentés másként'); ?></button>
              </div>
              <div class="">
                <button type="button" class="btn btn-sm btn-default"><span class="ico"><i class="fa fa-upload"></i></span> <?php echo __('Betöltés'); ?></button>
              </div>
              <div class="">
                <button type="button" class="btn btn-sm" ng-class="(deletemode)?'btn-danger':'btn-default'" ng-click="toggleDeleteMode()"><span class="ico"><i class="fa fa-trash"></i></span> <?php echo __('Törlés'); ?></button>
              </div>
            </div>
          </div>
          <div class="tiles" ng-class="(deletemode)?'deleting-mode':''">
            <table>
              <tr ng-repeat="(ri, row) in getNumberRepeat(grid.x) track by $index">
                <td id="grid-h{{ri}}x{{ci}}" data-grid-x="{{ri}}" data-grid-y="{{ci}}" ng-repeat="(ci, col) in getNumberRepeat(grid.y) track by $index" ng-click="fillGrid(ri, ci)"></td>
              </tr>
            </table>
          </div>
          <div class="orders">
            <div class="button-groups">
              <div class="">
                <button type="button" class="btn btn-default"><span class="ico"><i class="fa fa-file-text"></i></span> <?php echo __('Ajánlatkérés'); ?></button>
              </div>
              <div class="">
                <button type="button" class="btn btn-success"><span class="ico"><i class="fa fa-cart-plus"></i></span> <?php echo __('Megrendelés'); ?></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
