<div style="float:right;">
  <a href="/motivumjaim/config/0" class="btn btn-primary"> <i class="fa fa-plus"></i> Új motívum hozzáadása</a>
</div>
<h1>Motívumok</h1>
<div class="clr"></div>
<div class="motifs-editor" ng-controller="MotifsEditor" ng-init="init()">
  <div class="categories">
    <div class="category" ng-repeat="cat in kategoria_lista">
      <div class="header">
        <h3>{{cat.neve}}</h3>
      </div>
      <div class="motif-list-holder">
        <div class="wrapper">
          <div class="motif-list-header">
            <div class="mintakod center">Minta kódja</div>
            <div class="minta center">Előnézet</div>
            <div class="data">

            </div>
            <div class="sorrend center">Sorrend</div>
            <div class="lathato center">Aktív</div>
            <div class="act center"></div>
          </div>
          <div class="no-motifs" ng-show="!motifs[cat.hashkey].length">
            Nincsenek motívumok ebben a kategóriában.<br>
            <a class="btn btn-sm btn-default" href="/motivumjaim/config/0"><i class="fa fa-plus-circle"></i> hozzáadás</a>
          </div>
          <div class="motif" ng-repeat="m in motifs[cat.hashkey]">
            <div class="mintakod center">
              {{m.mintakod}}
            </div>
            <div class="minta center">
              <motivum kod="m.mintakod" shapes="m.shapes"></motivum>
            </div>
            <div class="data">
              <div class="">
                Rétegek: <strong>{{m.shapes.length}}</strong>
              </div>
              <div class="colors">
                Színek: <span ng-repeat="s in m.shapes" title="{{s.fill_color}}" style="background:{{s.fill_color}}; ">&nbsp;&nbsp;&nbsp;&nbsp;</span>
              </div>
            </div>
            <div class="sorrend center">
              {{m.sorrend}}
            </div>
            <div class="lathato center">
              <i class="fa fa-times" style="color:#ff7c7c;" ng-show="m.lathato==0"></i>
              <i class="fa fa-check" style="color:#71c571;" ng-show="m.lathato==1"></i>
            </div>
            <div class="act center">
              <a href="/motivumjaim/config/{{m.ID}}"><i class="fa fa-gear"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
