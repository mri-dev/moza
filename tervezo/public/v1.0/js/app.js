var app = angular.module('Moza', ['ngMaterial', 'ngMessages', 'ngCookies']);

app.controller('App', ['$scope', '$sce', '$http', '$mdToast', '$mdDialog', '$location','$cookies', '$cookieStore', '$timeout', function($scope, $sce, $http, $mdToast, $mdDialog, $location, $cookies, $cookieStore, $timeout)
{
  $scope.originColors = ["#000000", "#666666", "#888888"];
  $scope.motivumok = {};
  $scope.kategoriak = {};
  $scope.kategoria_lista = [];
  $scope.colors = [];
  $scope.aktiv_kat = 0;
  $scope.workstage = false;
  $scope.worklayer = false;
  $scope.workrotate = 0;
  $scope.test = "ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(100,0);ctx.quadraticCurveTo(100,0,100,0);ctx.lineTo(100,100);ctx.quadraticCurveTo(100,100,100,100);ctx.lineTo(0,100);ctx.quadraticCurveTo(0,100,0,100);ctx.lineTo(0,0);ctx.quadraticCurveTo(0,0,0,0);ctx.closePath();";
  $scope.currentFillColor = 'green';
  $scope.currentMotivum = false;
  $scope.changeColorObj = {};
  $scope.used_motifs = [];
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

      $timeout(function() {
        var color_width = $('.colors-table').width();
        var color_size = color_width / 12;
        $scope.color_size = color_size;
        $('.colors-table .color').css({
          height: color_size
        });

        var tiles_width = $('.tiles').width();
        var tile_size = tiles_width / $scope.grid.x;
        $scope.tile_size = tile_size;
        $('.tiles > table tbody tr td').css({
          height: $scope.tile_size,
          width: $scope.tile_size
        });

        $scope.workmotiv_size = $('.sample-editor .sample').width()-5;

        // STAGE
        $scope.workstage = new Konva.Stage({
          container: 'motivum',
          width: $scope.workmotiv_size,
          height: $scope.workmotiv_size
        });

        // LAYER
        $scope.worklayer = new Konva.Layer();

        $scope.addShape(
          $scope.workstage,
          $scope.worklayer,
          "ctx.beginPath();ctx.moveTo(0.5,0.5);ctx.lineTo(200.5,0.5);ctx.lineTo(200.5,200.5);ctx.lineTo(0.5,200.5);ctx.lineTo(0.5,0.5);ctx.closePath();",
          {
            fill: '#D86651',
            shapesize: $scope.workmotiv_size
          }
        );

        $scope.addShape(
          $scope.workstage,
          $scope.worklayer,
          "ctx.beginPath();ctx.moveTo(100.5,200.5);ctx.lineTo(100.5,0.5);ctx.lineTo(0.5,0.5);ctx.lineTo(0.5,100.5);ctx.lineTo(200.5,100.5);ctx.lineTo(200.5,200.5);ctx.lineTo(100.5,200.5);ctx.closePath();",
          {
            fill: '#D9D9D9',
            shapesize: $scope.workmotiv_size
          }
        );

      }, 600);

    });
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
      console.log($scope.workstage.rotation());
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
      }
    }
  }

  $scope.fillFullGrid = function()
  {
    for (var x = 0; x < $scope.grid.x; x++) {
      for (var y = 0; y < $scope.grid.y; y++) {
        $scope.fillGrid( x, y);
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

  $scope.passMotivToResource = function( res, copystage )
  {
    console.log( copystage );
    /* */
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
        shapes.scale({
          x: $scope.calcScaleFactor(width),
          y: $scope.calcScaleFactor(height)
        });
      });
      stage.add( lay );
      lay.draw();
    });
    /* */
  }

  $scope.saveMotivsToList = function( m ) {
    $scope.used_motifs.push( m );
  }

  $scope.fillGrid = function(ri, ci) {
    var fillholder = $('#grid-h'+ri+'x'+ci);
    $scope.passMotivToResource( fillholder, $scope.workstage );
  }

  $scope.changingFillColor = function( color, rgb ) {
    $scope.currentFillColor = '#'+rgb;
    $scope.changeColorObj = color;
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
