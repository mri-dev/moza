var app = angular.module('Moza', ['ngMaterial', 'ngMessages', 'ngCookies']);

app.controller('App', ['$scope', '$sce', '$http', '$mdToast', '$mdDialog', '$location','$cookies', '$cookieStore', '$timeout', function($scope, $sce, $http, $mdToast, $mdDialog, $location, $cookies, $cookieStore, $timeout)
{
  $scope.current_lang = 'hu';
  $scope.originColors = ["#000000", "#666666", "#888888"];
  $scope.motivumok = {};
  $scope.kategoriak = {};
  $scope.kategoria_lista = [];
  $scope.colors = [];
  $scope.aktiv_kat = 0;
  $scope.deletemode = false;
  $scope.workstage = false;
  $scope.worklayer = false;
  $scope.workrotate = 0;
  $scope.test = "ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(100,0);ctx.quadraticCurveTo(100,0,100,0);ctx.lineTo(100,100);ctx.quadraticCurveTo(100,100,100,100);ctx.lineTo(0,100);ctx.quadraticCurveTo(0,100,0,100);ctx.lineTo(0,0);ctx.quadraticCurveTo(0,0,0,0);ctx.closePath();";
  $scope.currentFillColor = 'green';
  $scope.currentMotivum = false;
  $scope.usingHistoryHash = false;
  $scope.changeColorObj = {};
  $scope.lastbuildmotifs = false;
  $scope.used_motifs = {};
  $scope.used_colors = [];
  $scope.color_size = 0;
  $scope.tile_size = 0;
  $scope.grid = {
    x: 16,
    y: 16
  };
  $scope.motiv_size = ($('.sidebar').width() - 8 - 12 - 6) / 3;
  $scope.workmotiv_size = 200;

  $scope.calcScaleFactor = function( size ){
    return parseFloat( size / 200 );
  }

  $scope.init = function()
  {
    $scope.loadSettings(function()
    {
      $scope.loadMotivums(function( motivums )
      {
        if (motivums) {
          angular.forEach(motivums, function(i,e){
            if (typeof $scope.motivumok[i.mintakod] === 'undefined') {
              $scope.motivumok[i.mintakod] = i;
            }
            if (typeof $scope.kategoriak[i.kat_hashkey] === 'undefined') {
              $scope.kategoriak[i.kat_hashkey] = [];
              $scope.kategoriak[i.kat_hashkey].push(i);
            } else {
              $scope.kategoriak[i.kat_hashkey].push(i);
            }
          });
        }
      });

      $scope.fixColorTableSizes(200);

      $timeout(function() {
        var tiles_width = $('.tiles').width();
        var tile_size = tiles_width / $scope.grid.x;
        $scope.tile_size = tile_size;
        $('.tiles > table tbody tr td').css({
          height: $scope.tile_size,
          width: $scope.tile_size
        });

        $scope.workmotiv_size = $('.sample-editor .sample').width()-5;
        $('#motivum').css({
          height: $scope.workmotiv_size
        });
      }, 600);

    });
  }

  $scope.fixColorTableSizes = function( delay ) {
    $timeout(function() {
      var color_width = $('.colors-table').width();
      var color_size = color_width / 12;
      $scope.color_size = color_size;
      $('.colors-table .color').css({
        height: color_size
      });
    }, delay);
  }

  $scope.rotateWorkMotiv = function( r ) {
    if ( $scope.workstage ) {
      if ( r >= 0 ) {
        $scope.workrotate += r;
      } else if( r <= 0 ){
        $scope.workrotate -= r;
      }
      if ($scope.workrotate >= 360 || $scope.workrotate <= -360) {
        $scope.workrotate = 0;
      }
      $scope.workstage.rotation( $scope.workrotate );
      $scope.workstage.draw();
    }
  }

  $scope.pickNewMotiv = function( m )
  {
    if ( m ) {
      // STAGE
      $scope.workstage = new Konva.Stage({
        container: 'motivum',
        width: $scope.workmotiv_size,
        height: $scope.workmotiv_size
      });
      $scope.workstage.setAttr('minta', m.mintakod);

      // LAYER
      $scope.worklayer = new Konva.Layer();

      $scope.workstage.clear();
      $scope.workrotate = 0;

      if ( m.shapes && m.shapes.length ) {
        angular.forEach( m.shapes, function(si, se){
          $scope.addShape(
            $scope.workstage,
            $scope.worklayer,
            si.canvas_js,
            {
              fill: si.fill_color,
              shapesize: $scope.workmotiv_size
            }
          );
        });
        $scope.currentMotivum = m;
        $scope.usingHistoryHash = false;
      }
    }
  }

  $scope.fillFullGrid = function()
  {
    for (var x = 0; x < $scope.grid.x; x++) {
      for (var y = 0; y < $scope.grid.y; y++) {
        $scope.fillGrid( x, y, true);
      }
    }
  }

  $scope.resetGrid = function()
  {
    for (var x = 0; x < $scope.grid.x; x++) {
      for (var y = 0; y < $scope.grid.y; y++) {
        $('#grid-h'+x+'x'+y).find('.konvajs-content').remove();
      }
    }
  }

  $scope.passMotivToResource = function( res, copystage, use_delay )
  {
    if ($scope.workstage) {
      var colors = [];
      var hashid = copystage.getAttr('minta');
      var layers = copystage.getLayers();
      var width = $scope.tile_size-2;
      var height = $scope.tile_size-2;
      var stage = new Konva.Stage({
        container: res.selector.replace("#",""),
        width: width,
        height: height
      });

      layers.each(function(layer, n) {
        var lay = layer.clone();
        lay.getChildren(function( shapes ){
          var fillcolor = shapes.getAttr('fill');
          hashid += fillcolor;
          colors.push( fillcolor );
          shapes.scale({
            x: $scope.calcScaleFactor(width),
            y: $scope.calcScaleFactor(height)
          });
        });
        stage.add( lay );
        lay.draw();
      });

      hashid = $scope.generHash(hashid);
      stage.setAttr('hashid', hashid);

      $scope.refreshHistoryLists( copystage, colors, hashid, use_delay);
    } else {
      $scope.toast($scope.translate('no_motiv_selected'), 'error', 5000);
    }
  }

  $scope.toggleDeleteMode = function() {
    if ($scope.deletemode) {
      $scope.deletemode = false;
    } else {
      $scope.deletemode = true;
    }
  }

  $scope.generHash = function( string ){
    var hash = 0, i, chr;
    if (string.length === 0) return hash;
    for (i = 0; i < string.length; i++) {
      chr   = string.charCodeAt(i);
      hash  = ((hash << 5) - hash) + chr;
      hash |= 0; // Convert to 32bit integer
    }
    hash = (hash < 0) ? hash * -1 : hash;
    return hash;
  }

  $scope.refreshHistoryLists = function( stage, colors, hashid, use_delay )
  {
    var colorstack = [];

    var minta = stage.getAttr('minta');

    $scope.saveMotivsToList( minta, hashid, stage, colors, function()
    {
      if ($scope.used_motifs && $scope.used_motifs.length != 0) {
        angular.forEach($scope.used_motifs, function(e,i){
          angular.forEach(e.colors, function(color,i){
            if (colorstack.indexOf(color) === -1) {
              colorstack.push(color);
            }
          });
        });
      };
      var current_date = new Date().getTime();

      if (
        (typeof use_delay === 'undefined' || use_delay === false ) ||
        (typeof use_delay !== 'undefined' && use_delay && ($scope.lastbuildmotifs == false || (current_date - $scope.lastbuildmotifs) > 2000))
      ){
        $scope.lastbuildmotifs = new Date().getTime();
        $scope.used_colors = colorstack;
        $scope.fixColorTableSizes(0);

        if ( $scope.used_motifs && $scope.used_motifs.length != 0) {
          var width = $scope.color_size-2;
          var height = $scope.color_size-2;

          $timeout(function(){
            angular.forEach($scope.used_motifs, function(m,i){
              if (m.stage) {
                var historystage = new Konva.Stage({
                  container: 'shapemotiv'+m.hashid,
                  width: width,
                  height: height
                });
                var layers = m.stage.getLayers();

                layers.each(function(layer, n) {
                  var lay = layer.clone();
                  var si = 0;
                  lay.getChildren(function( shapes ){
                    shapes.scale({
                      x: $scope.calcScaleFactor(width),
                      y: $scope.calcScaleFactor(height)
                    });
                    shapes.fill( m.colors[si] );
                    si++;
                  });
                  historystage.add( lay );
                  lay.draw();
                });
              }
            });
          }, 100);
        }
      }
    } );
  }

  $scope.loadHistoryMotiv = function( hash )
  {
    var motiv = $scope.used_motifs[hash];
    $scope.usingHistoryHash = hash;
    $scope.workstage = new Konva.Stage({
      container: 'motivum',
      width: $scope.workmotiv_size,
      height: $scope.workmotiv_size,
      hashid: hash,
      loadfromhistory: true,
      minta: motiv.minta
    });

    var layers = motiv.stage.getLayers();

    layers.each(function(layer, n) {
      var lay = layer.clone();
      var si = 0;
      lay.getChildren(function( shapes ){
        shapes.scale({
          x: $scope.calcScaleFactor($scope.workmotiv_size),
          y: $scope.calcScaleFactor($scope.workmotiv_size)
        });
        shapes.on('click', function(){
          this.fill( $scope.currentFillColor );
          lay.draw();
        });
        shapes.fill( motiv.colors[si] );
        si++;
      });
      $scope.workstage.add( lay );
      lay.draw();
    });

    $scope.currentMotivum = $scope.motivumok[motiv.minta];
  }

  $scope.saveMotivsToList = function( minta, hashid, stage, colors, callback ) {
    if (typeof $scope.used_motifs[hashid] == 'undefined') {
      $scope.used_motifs[hashid] = {
        'minta': minta,
        'hashid': hashid,
        'colors': colors,
        'stage': stage
      };
    }
    if (typeof callback !== 'undefined') {
      callback();
    }
  }

  $scope.fillGrid = function(ri, ci, use_delay) {
    var fillholder = $('#grid-h'+ri+'x'+ci);
    if ( !$scope.deletemode ) {
      $scope.passMotivToResource( fillholder, $scope.workstage, use_delay );
    } else {
      $scope.removeMotivFromGrid( fillholder );
    }
  }

  $scope.removeMotivFromGrid = function( res ) {
    var stage = new Konva.Stage({
      container: res.selector.replace("#","")
    });

    stage.remove();
  }

  $scope.changingFillColor = function( color, rgb ) {
    if (typeof color === 'string') {
      $scope.findColorObjectByRGB( rgb.replace("#",""),  function( color ){
        $scope.currentFillColor = rgb;
        $scope.changeColorObj = color;
      } );
    } else {
      $scope.currentFillColor = '#'+rgb;
      $scope.changeColorObj = color;
    }
  }

  $scope.findColorObjectByRGB = function( rgb, callback ){
    if ($scope.colors && $scope.colors.length != 0) {
      angular.forEach( $scope.colors, function(c,i){
        if( c.szin_rgb == rgb ) {
          callback(c);
        }
      });
    }

    return rgb;
  }

  $scope.saveProject = function() {
    
  }

  $scope.getNumberRepeat = function( n ) {
    return new Array( n );
  }

  $scope.changeKat = function( id ) {
    $scope.aktiv_kat = id;
  }

  $scope.addShape = function( stage, layer, context, options )
  {
    var fillColor = '#000000';
    if (options && typeof options.fill !== 'undefined') {
      fillColor = options.fill;
    }

    var shapeOptions = {
      sceneFunc: function(ctx)
      {
        eval(context);
        ctx.fillStrokeShape(this);
      },
      fill: fillColor,
      shapesize: 200
    };

    angular.extend(shapeOptions, options);

    shapeOptions.scale = {
      x: $scope.calcScaleFactor(shapeOptions.shapesize),
      y: $scope.calcScaleFactor(shapeOptions.shapesize)
    };

    var shape =  new Konva.Shape(shapeOptions);
    //shape.scale($scope.calcScaleFactor(shapeOptions.shapesize),$scope.calcScaleFactor(shapeOptions.shapesize));

    if ( typeof options === 'undefined' || (typeof options === 'undefined' && typeof options.colorizable === 'undefined') || options.colorizable !== false)
    {
      shape.on('click', function(){
        this.fill( $scope.currentFillColor );
        layer.draw();
      });
    }

    layer.add( shape );
    stage.add( layer );
    layer.draw();
  }

  $scope.loadMotivums = function( callback ){
    $http({
      method: 'POST',
      url: '/ajax/post',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Moza",
        mode: 'getMotivumok'
      })
    }).success(function(r){
      if (typeof callback !== 'undefined') {
        callback(r.data);
      }
    });
  }

  $scope.loadSettings = function( callback ){
    $http({
      method: 'POST',
      url: '/ajax/post',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Moza",
        mode: 'getSettings'
      })
    }).success(function(r){
      if (r.data) {
        if (r.data.kategoria_lista !== 'undefined') {
          $scope.kategoria_lista = r.data.kategoria_lista;
        }
        if (r.data.colors !== 'undefined') {
          $scope.colors = r.data.colors;
        }
      }
      if (typeof callback !== 'undefined') {
        callback();
      }
    });
  }

  $scope.emptyObject = function( obj ) {
    return (Object.keys(obj).length === 0) ? true : false;
  }

  $scope.translate = function( id ) {
    var lang = $scope.current_lang;
    var translates = {
      'hu':{
        'no_motiv_selected': 'Nincs kiválasztva aktív minta motívum. Válasszon a kategóriák közül.'
      }
    }

    return translates[lang][id];
  }

  $scope.toast = function( text, mode, delay ){
    mode = (typeof mode === 'undefined') ? 'simple' : mode;
    delay = (typeof delay === 'undefined') ? 5000 : delay;

    if (typeof text !== 'undefined') {
      $mdToast.show(
        $mdToast.simple()
        .textContent(text)
        .position('top')
        .toastClass('alert-toast mode-'+mode)
        .hideDelay(delay)
      );
    }
  }

}]);

app.directive('motivum', function($rootScope){
  var motivum = {};
  motivum.restrict = 'E';
  motivum.scope = true;
  motivum.transclude = true;
  motivum.replace = true;
  motivum.compile  = function(e, a){
    return function($scope, e, a)
    {
      var konva = {};
      var id = 'katmot'+$scope.m.mintakod;
      e.attr("id", id);
      konva.stage = new Konva.Stage({
        container: id,
        width: $scope.motiv_size,
        height: $scope.motiv_size
      });
      konva.stage.setAttr('minta', $scope.m.mintakod);
      konva.layer = new Konva.Layer();

      if ($scope.m.shapes && $scope.m.shapes.length) {
        angular.forEach( $scope.m.shapes, function(si, se){
          // TODO: konva draw shape
          var shape =  new Konva.Shape({
            sceneFunc: function(ctx)
            {
              eval(si.canvas_js);
              ctx.fillStrokeShape(this);
            },
            fill: si.fill_color,
            scale: {
              x: $scope.calcScaleFactor($scope.motiv_size),
              y:$scope.calcScaleFactor($scope.motiv_size)
            }
          });

          konva.layer.add( shape );
          konva.stage.add( konva.layer );
          konva.layer.draw();
        });
      }

      $scope.konva = konva;
      $rootScope.$broadcast('KONVA:READY', konva.stage);
    }
  }

  return motivum;
});


app.filter('unsafe', function($sce){ return $sce.trustAsHtml; });


$(function(){
  recalcPositions();

  $(window).resize(function(){
    recalcPositions();
  });

  function recalcPositions()
  {
    recalcGridSizes();

    var header_height = $('body header').height();
    var window_height = $(window).height();
    $('.inside-content').css({
      paddingTop: header_height + 20,
      height: window_height
    });

    var copy_height = $('.sidebar .copy').height();
    var kat_tit_height = $('.sidebar .kat-title').height();

    $('.sidebar .cat-list').css({
      height: window_height-header_height-copy_height-kat_tit_height-65
    });
    $('.sidebar').css({
      top: header_height-1
    });
  }

  function recalcGridSizes()
  {
    var tiles_width = $('.tiles').width();
    var height = tiles_width / 16;
    $('.tiles tr td').css({
      height: height,
      width: height
    });
  }
});
