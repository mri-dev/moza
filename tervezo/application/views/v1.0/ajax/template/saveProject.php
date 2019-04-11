<md-dialog aria-label="<?php echo __('Projekt mentése'); ?>">
  <form ng-cloak>
    <md-toolbar>
      <div class="md-toolbar-tools">
        <h2><?php echo __('Projekt mentése'); ?></h2>
        <span flex></span>
        <md-button class="md-icon-button" ng-click="cancel()">
          <md-icon md-svg-src="src/images/ic_close_24px.svg" aria-label="Bezár"></md-icon>
        </md-button>
      </div>
    </md-toolbar>
    <md-dialog-content>
      <div class="md-dialog-content">
        <?php echo __('Projektjét el tudja menteni, hogy később megtekintse. A projek terveket betöltheti, ha megadja e-mail címét.'); ?>
        <md-input-container md-no-float class="md-block">
          <label><?php echo __('Projekt elnevezése'); ?></label>
          <input ng-model="save.name" required >
        </md-input-container>
        <md-input-container md-no-float class="md-block">
          <label><?php echo __('E-mail cím'); ?></label>
          <input ng-model="save.email" type="email" required maxlength="100" ng-pattern="/^.+@.+\..+$/">
        </md-input-container>
        <div style="color: #e66363;" ng-show="!save.name || !save.email">
          <?php echo __('Mentéshez adja meg a projekt nevét és az Ön e-mail címét.'); ?>
        </div>
      </div>
    </md-dialog-content>

    <md-dialog-actions layout="row">
      <md-button ng-click="saving()" ng-if="save.name && save.email">
        <?php echo __('Projekt mentése'); ?>
      </md-button>
    </md-dialog-actions>
  </form>
</md-dialog>
