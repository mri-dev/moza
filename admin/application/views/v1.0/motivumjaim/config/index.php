<div class="motiv-configurator" ng-controller="MotifConfigurator" ng-init="init(<?=$this->gets[2]?>)">
  <h1>"<u>{{motivumkod}}</u>" motívum szerkesztése</h1>
  <div class="right">
    <button type="button" class="btn btn-success" ng-click="createMotivum()">Motívum adatainak rögzítése</button>
  </div>
  <br>
  <div class="holder">
    <div class="settings">
      <h4>Motívum alapadatok</h4>
      <br>
      <div class="row np">
        <div class="col-md-3">
          Motívum kódja
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control" ng-model="motivum.mintakod">
        </div>
      </div>
      <br>
      <div class="row np">
        <div class="col-md-3">
          Sorrend
        </div>
        <div class="col-md-2">
          <input type="number" class="form-control" ng-model="motivum.sorrend">
        </div>
      </div>
      <br>
      <div class="row np">
        <div class="col-md-3">
          Aktív
        </div>
        <div class="col-md-1">
          <input type="checkbox" class="form-control" ng-model="motivum.lathato">
        </div>
      </div>
      <br>
      <div class="row np">
        <div class="col-md-3">
          SVG script
        </div>
        <div class="col-md-9">
          <textarea style="min-height: 400px;" class="form-control no-editor" ng-model="motivum.svgpath"></textarea>
        </div>
      </div>
      <br><br>
      <h4>Rétegek</h4>
      <div class="shapes">
        <div class="shape" ng-repeat="s in motivum.shapes">
          <div class="row">
            <div class="col-md-1 center">
              {{s.sortindex}}
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control" ng-model="s.fill_color">
            </div>
            <div class="col-md-9">
              <textarea style="min-height: 100px;" readonly="readonly" class="form-control no-editor" ng-model="s.canvas_js"></textarea>
            </div>
          </div>
          <br>
        </div>
      </div>
    </div>
    <div class="motif-preview">
      <div class="" ng-repeat="m in motifs">
        <motivum kod="m.mintakod" shapes="m.shapes"></motivum>
      </div>
    </div>
  </div>
</div>
