<div class="motiv-configurator" ng-controller="MotifConfigurator" ng-init="init(<?=$this->gets[2]?>)">
  <?php if ($this->gets[2] != 0): ?>
    <h1>"<u>{{motivumkod}}</u>" motívum szerkesztése</h1>
  <?php else: ?>
    <h1>Új motívum hozzáadása</h1>
  <?php endif; ?>

  <div class="right">
    <button type="button" class="btn btn-success" ng-click="createMotivum()">Motívum adatainak rögzítése</button>
  </div>
  <br>
  <div class="holder">
    <div class="settings">
      <h3>Motívum alapadatok</h3>
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
          Kategória
        </div>
        <div class="col-md-6">
          <select class="form-control" ng-model="motivum.kategoria" ng-options="item.ID as item.neve for item in kategoria_lista"></select>
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
      <h3>Rétegek</h3>
      <div class="shapes">
        <div class="no-item" ng-show='!motivum.shapes'>
          Jelenleg nincsenek rétegek. A rétegek az SVG script forrásból automatikusan generálódik le.
        </div>
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
      <?php if ($this->gets[2] != 0): ?>
      <h3>Minta előnézet, színező<p class="info">Válassza ki a színpalettából a színt, majd kattintson a motívum színezni kívánt részeire.</p></h3>
      <div class="" ng-repeat="m in motifs">
        <motivum kod="m.mintakod" shapes="m.shapes" editor="1"></motivum>
      </div>
      <br>
      <h4>Saját minta - Színkonfiguráció mentése</h4>
      <div class="save-motif-recommend">
        <div class="input-group">
          <input type="text" class="form-control" ng-model="ownmotifname" placeholder="Elnevezés">
          <div class="input-group-append">
            <button class="btn btn-primary" type="button" ng-click="saveOwnStyle()">Hozzáadás</button>
          </div>
        </div>
      </div>
      <br>
      <h4>Színpaletta</h4>
      <div class="colors">
        <div class="color" ng-repeat="c in colors" ng-class="(changeColorObj==c)?'current':''">
          <div class="wrapper" style="background:#{{c.szin_rgb}};" ng-click="changingFillColor(c, c.szin_rgb)">
            <div class="txt">{{c.kod}}</div>
          </div>
        </div>
      </div>
      <?php else: ?>
        <h3>Információk</h3>
        <br>
        <strong>SVG viewpoint tervezési méret:</strong><br>
        NAGYON FONTOS!<br>
        Mindig <strong>200x200</strong> pixeles méreten dolgozzunk a motívum létrehozása során tervező programunkban.
        <br><br>
        <strong>Javasolt tervező program:</strong><br>
        Adobe Illustrator
        <br><br>
        <strong>SVG script:</strong><br>
        A rendszer SVG-ből átalakított HTML5 canvas javascriptet dolgoz fel.<br>Hozzuk létre a kívánt mintát valamilyen vektorgrafikus programban, majd mentsük el SVG formátumba. Az SVG fájlt át kell konvertálni olyan módon, hogy az javascript legyen.
        <br><br>
        <a href="http://demo.qunee.com/svg2canvas/" class="btn btn-sm btn-primary" target="_blank">Online SVG -> HTML5 canvas JS generátor</a>
        <br><br>
        <strong>Script kimásolása:</strong><br>
        Az online kód generátorból nem minden részt kell kimásolni. Kizárólag csak a ctx. scripteket. <br>
        <a href="javascript:void(0);" onclick="$('#svgsampleimg').slideToggle(400);" style="font-weight: bold; font-size: 0.7rem;"><u>Mutasd a mintát</u></a>
        <div style="display:none;" id="svgsampleimg">
          <a href="<?=IMG?>svg_html5_sc.jpg" target="_blank"><img src="<?=IMG?>svg_html5_sc.jpg" alt="Minta" style="max-width:100%;"></a>
        </div>
        <br>
        <br>
        <strong>Rétegek alapértelmezett színezése:</strong><br>
        A motívum létrehozása során a rendszer szétdarabolja a rétegeket, görbéket, így minden egyes réteg színezhetővé válik. Alapértelmezetten a rendszer random sorrend alapján a szürkeárnyalatos színekből véletlenszerűen választ színt és rendeli hozzá a réteghez. Így a motívum mintája láthatóvá válik. Utólagosan így gyorsabban lehet a kívánt alapértelmezett színeket beállítani.
      <?php endif; ?>
    </div>
  </div>
</div>
