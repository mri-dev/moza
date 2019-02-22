var app = angular.module('Moza', ['ngMaterial', 'ngMessages', 'ngCookies']);

app.controller('App', ['$scope', '$sce', '$http', '$mdToast', '$mdDialog', '$location','$cookies', '$cookieStore', function($scope, $sce, $http, $mdToast, $mdDialog, $location, $cookies, $cookieStore)
{
  $scope.originColors = ["#000000", "#666666", "#888888"];
  $scope.motivumok = {};
  $scope.kategoriak = {};
  $scope.kategoria_lista = [];
  $scope.aktiv_kat = 0;
  $scope.workstage = false;
  $scope.worklayer = false;
  $scope.test = "ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(100,0);ctx.quadraticCurveTo(100,0,100,0);ctx.lineTo(100,100);ctx.quadraticCurveTo(100,100,100,100);ctx.lineTo(0,100);ctx.quadraticCurveTo(0,100,0,100);ctx.lineTo(0,0);ctx.quadraticCurveTo(0,0,0,0);ctx.closePath();";
  $scope.currentFillColor = 'green';

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
      $scope.test
    );

    // create our shape
    /*
    var obj = new Konva.Path({
      data: 'M100,100H0V0h100V100z M200,100H100v100h100V100z',
      fill: $scope.originColors[0],
    });
    $scope.worklayer.add( obj );

    obj.on('click', function() {
      this.fill('#345422');
      $scope.worklayer.draw();
    });
    */
  }

  $scope.changeKat = function( id ) {
    $scope.aktiv_kat = id;
  }

  $scope.addShape = function( stage, layer, context, options )
  {
    var shapeOptions = {
      sceneFunc: function(ctx)
      {
        eval(context);
        ctx.fillStrokeShape(this);
      },
      fill: $scope.originColors[1]
    };

    angular.extend(shapeOptions, options);

    var shape =  new Konva.Shape(shapeOptions);

    shape.on('click', function(){
      this.fill( $scope.currentFillColor );
      layer.draw();
    });

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
      console.log(r);
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
      console.log(r);
      if (r.data) {
        if (r.data.kategoria_lista !== 'undefined') {
          $scope.kategoria_lista = r.data.kategoria_lista;
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


app.filter('unsafe', function($sce){ return $sce.trustAsHtml; });


$(function(){
  recalcPositions();

  $(window).resize(function(){
    recalcPositions();
  });

  function recalcPositions() {
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

});
