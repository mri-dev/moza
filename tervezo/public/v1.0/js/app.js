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
  $scope.test = "ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(100,0);ctx.quadraticCurveTo(100,0,100,0);ctx.lineTo(100,100);ctx.quadraticCurveTo(100,100,100,100);ctx.lineTo(0,100);ctx.quadraticCurveTo(0,100,0,100);ctx.lineTo(0,0);ctx.quadraticCurveTo(0,0,0,0);ctx.closePath();";
  $scope.currentFillColor = 'green';
  $scope.changeColorObj = {};
  $scope.grid = {
    x: 16,
    y: 16
  };

  $scope.init = function()
  {
    $scope.loadSettings(function()
    {
      $scope.loadMotivums(function( motivums )
      {
        console.log($scope.colors);
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

          // Kategória canvas
          //$scope.buildCategoriesMotifs();
        }
      });

      $timeout(function() {
        var tiles_width = $('.colors-table').width();
        var height = tiles_width / 12;
        $('.colors-table .color').css({
          height: height
        });

        // STAGE
        $scope.workstage = new Konva.Stage({
          container: 'motivum',
          width: 200,
          height: 200
        });

        // LAYER
        $scope.worklayer = new Konva.Layer();

        $scope.addShape(
          $scope.workstage,
          $scope.worklayer,
          "ctx.beginPath();ctx.moveTo(0.5,0.5);ctx.lineTo(200.5,0.5);ctx.lineTo(200.5,200.5);ctx.lineTo(0.5,200.5);ctx.lineTo(0.5,0.5);ctx.closePath();",
          {
            fill: '#D86651'
          }
        );

        $scope.addShape(
          $scope.workstage,
          $scope.worklayer,
          "ctx.beginPath();ctx.moveTo(100.5,200.5);ctx.lineTo(100.5,0.5);ctx.lineTo(0.5,0.5);ctx.lineTo(0.5,100.5);ctx.lineTo(200.5,100.5);ctx.lineTo(200.5,200.5);ctx.lineTo(100.5,200.5);ctx.closePath();",
          {
            fill: '#D9D9D9'
          }
        );

      }, 600);

    });


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
      fill: fillColor
    };

    angular.extend(shapeOptions, options);

    var shape =  new Konva.Shape(shapeOptions);

    if ( typeof options === 'undefined' || (typeof options === 'undefined' && typeof options.colorizable === 'undefined') || options.colorizable !== false)
    {
      shape.on('click', function(){
        this.fill( $scope.currentFillColor );
        layer.draw();
      });
    }

    shape.scale(1,1);

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
    return function($scope, e, a){
      var konva = {};
      var id = 'katmot'+$scope.m.mintakod;
      e.attr("id", id);
      konva.stage = new Konva.Stage({
        container: id,
        width: 90,
        height: 90
      });
      konva.layer = new Konva.Layer();

      if ($scope.m.shapes && $scope.m.shapes.length) {
        angular.forEach( $scope.m.shapes, function(si, se){
          // TODO: konva draw shape
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
    recalcColorTableSizes();

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
      height: height
    });
  }

  function recalcColorTableSizes()
  {
    var tiles_width = $('.colors-table').width();
    var height = tiles_width / 12;
    $('.colors-table .color').css({
      height: height
    });
  }

});
