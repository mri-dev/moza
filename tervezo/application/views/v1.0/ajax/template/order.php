<md-dialog class="order-dialog" aria-label="<?php echo __('Megrendelés'); ?>">
  <form ng-cloak>
    <md-toolbar class="order">
      <div class="md-toolbar-tools">
        <h2><?php echo __('Megrendelés'); ?></h2>
        <span flex></span>
        <md-button class="md-icon-button" ng-click="cancel()">
          <md-icon md-svg-src="img/icons/ic_close_24px.svg" aria-label="Close dialog"></md-icon>
        </md-button>
      </div>
    </md-toolbar>
    <md-dialog-content>
      <div class="md-dialog-content">
        <div layout-gt-sm="row">
          <md-input-container class="md-block" flex-gt-sm>
            <label><?php echo __('Az Ön neve'); ?></label>
            <input type="text" required ng-model="order.name">
          </md-input-container>
          <md-input-container class="md-block" flex-gt-sm>
            <label><?php echo __('Telefonszám'); ?></label>
            <input type="tel" required ng-model="order.phone">
          </md-input-container>
        </div>
        <div layout-gt-sm="row">
          <md-input-container class="md-block" flex-gt-sm>
            <label><?php echo __('Az Ön e-mail címe'); ?></label>
            <input type="email" required ng-model="order.email">
          </md-input-container>
        </div>
        <div class="order-modal-overall">
          <h3><?php echo __('Összesítő'); ?></h3>
          <div class="motifs" ng-repeat="m in motifs">
            <div class="csempe">
              <img src="{{m.imageurl}}" alt="">
            </div>
            <div class="data">
              <div class="motiv">
                <?php echo __('Motívum'); ?>: <strong>{{m.minta}}</strong>
              </div>
              <div class="colors">
                <div class="h">
                  <?php echo __('Színkonfiguráció'); ?>:
                </div>
                <div class="color-config" ng-repeat="color in m.colors">
                  <span class="color-preview" style="background:{{color.rgb}};">&nbsp;</span>
                  <strong>{{color.obj.kod}}</strong> - {{color.obj.neve}} &bull; {{color.obj.szin_ncs}}
                </div>
              </div>
            </div>
            <div class="order-details">
              <div class="fconf">
                <div class="f"><input ng-change="modifyQty(m.hashid, 'db')" type="number" ng-model="qtyconf[m.hashid].db"></div>
                <div class="t"><?php echo __('db'); ?> =</div>
                <div class="f"><input ng-change="modifyQty(m.hashid, 'nm')" type="number" ng-model="qtyconf[m.hashid].nm"></div>
                <div class="t">m<sup>2</sup></div>
              </div>
            </div>
          </div>
        </div>
        <div style="color: #e66363;" ng-show="!order.name || !order.email || !order.phone">
          <?php echo __('A megrendelés leadásához adja meg az adatait!'); ?>
        </div>
      </div>
    </md-dialog-content>

    <md-dialog-actions layout="row">
      <md-button ng-click="saving()" ng-if="order.name && order.email && order.phone">
        <?php echo __('Megrendelés leadása'); ?>
      </md-button>
    </md-dialog-actions>
  </form>
</md-dialog>
