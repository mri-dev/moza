var app = angular.module('Moza', ['ngMaterial', 'ngMessages', 'ngCookies']);

app.controller('App', ['$scope', '$sce', '$http', '$mdToast', '$mdDialog', '$location','$cookies', '$cookieStore', '$timeout', function($scope, $sce, $http, $mdToast, $mdDialog, $location, $cookies, $cookieStore, $timeout)
{
  $scope.loader_title = '';
  $scope.apploading = true;
  $scope.current_lang = 'hu';
  $scope.originColors = ["#000000", "#666666", "#888888"];
  $scope.motivumok = {};
  $scope.motivumnum = 0;
  $scope.kategoriak = {};
  $scope.kategoria_lista = [];
  $scope.colors = [];
  $scope.colorsbyrgb = {};
  $scope.loaded_projects = [];
  $scope.aktiv_kat = 0;
  $scope.deletemode = false;
  $scope.workstage = false;
  $scope.worklayer = false;
  $scope.workrotate = 0;
  $scope.test = "ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(100,0);ctx.quadraticCurveTo(100,0,100,0);ctx.lineTo(100,100);ctx.quadraticCurveTo(100,100,100,100);ctx.lineTo(0,100);ctx.quadraticCurveTo(0,100,0,100);ctx.lineTo(0,0);ctx.quadraticCurveTo(0,0,0,0);ctx.closePath();";
  $scope.currentFillColor = '#333333';
  $scope.strokeWidth = 0.5;
  $scope.showStrokes = false;
  $scope.currentMotivum = false;
  $scope.usingHistoryHash = false;
  $scope.changeColorObj = {};
  $scope.lastbuildmotifs = false;
  $scope.project_load_email = '';
  $scope.project_loading = false;
  $scope.selected_project = false;
  $scope.used_motifs = {};
  $scope.used_colors = [];
  $scope.gridStages = {};
  $scope.color_size = 0;
  $scope.tile_size = 0;
  $scope.grid = {
    x: 16,
    y: 16
  };
  $scope.motiv_size = ($('.sidebar').width() - (3*(10))) / 3;
  $scope.workmotiv_size = 202;
  $scope.csempenmdb = 25;

  $scope.calcScaleFactor = function( size ){
    return parseFloat( size / 200);
  }

  $scope.init = function()
  {
    $scope.load_language();
    $scope.loader_title = $scope.translate('default_loader_title');
    $scope.loadSettings(function()
    {
      $scope.loadMotivums(function( motivums )
      {
        if (motivums) {
          $scope.motivumnum = 0;
          angular.forEach(motivums, function(i,e){
            if (typeof $scope.motivumok[i.mintakod] === 'undefined') {
              $scope.motivumok[i.mintakod] = i;
              $scope.motivumnum++;
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

        $scope.workmotiv_size = $('.sample-editor .sample').width()-4;
        $('#motivum').css({
          height: $scope.workmotiv_size
        });
      }, 600);

    });
  }

  $scope.load_language = function() {
    var lang = $cookies.get('lang');
    if (lang =='' || typeof lang === 'undefined') {
      $scope.current_lang = 'hu';
    } else {
      $scope.current_lang = lang;
    }
  }

  $scope.toggleBorderOnSample = function()
  {
    if ($scope.showStrokes) {
      $scope.showStrokes = false;
    } else {
      $scope.showStrokes = true;
    }

    if ( $scope.workstage ) {
      var layers = $scope.workstage.getLayers();

      layers.each(function(layer, n) {
        layer.getChildren(function( shapes ){
          if ( $scope.showStrokes ) {
            shapes.stroke('black');
            shapes.strokeWidth($scope.strokeWidth);
          } else {
            shapes.stroke('');
            shapes.strokeWidth(0);
          }
        });
        layer.draw();
      });
    }
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
      var node = $scope.workstage;

      $scope.workrotate -= r;

      if ($scope.workrotate >= 360 || $scope.workrotate <= -360) {
        $scope.workrotate = 0;
      }

      $scope.rotateStage( node, $scope.workrotate );
    }
  }

  $scope.rotateStage = function( node, rot )
  {
    const degToRad = Math.PI / 180;
    const rotatePoint = ({x, y}, deg) => {
        const rcos = Math.cos(deg * degToRad), rsin = Math.sin(deg * degToRad)
        return {x: x*rcos - y*rsin, y: y*rcos + x*rsin}
    };
    const topLeft = {x:-node.width()/2, y:-node.height()/2};
    const current = rotatePoint(topLeft, node.rotation());
    const rotated = rotatePoint(topLeft, rot);
    const dx = rotated.x - current.x, dy = rotated.y - current.y;

    node.rotation( rot );
    node.x(node.x() + dx);
    node.y(node.y() + dy);
    node.draw();
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
          $scope.findColorObjectByRGB( si.fill_color.replace("#",""),  function( color ){
            var settings = {
              fill: si.fill_color,
              shapesize: $scope.workmotiv_size,
              colorinfo: color
            };
           if ($scope.showStrokes) {
             settings.stroke = 'black';
             settings.strokeWidth = $scope.strokeWidth;
           }
           $scope.addShape(
             $scope.workstage,
             $scope.worklayer,
             si.canvas_js,
             settings
           );
          } );
        });

        $scope.currentMotivum = m;
        $scope.usingHistoryHash = false;
      }
    }
  }

  $scope.fillFullGrid = function()
  {
    if ($scope.workstage) {
      for (var x = 0; x < $scope.grid.x; x++) {
        for (var y = 0; y < $scope.grid.y; y++) {
          $scope.fillGrid( x, y, true);
        }
      }
    } else {
      $scope.toast($scope.translate('no_motiv_selected'), 'error', 5000);
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
    if ( copystage ) {
      var colors = [];
      var gridx = res.data('grid-x');
      var gridy = res.data('grid-y');
      var hashid = copystage.getAttr('minta');
      var rotate = copystage.getAttr('rotation');
      var layers = copystage.getLayers();
      var width = $scope.tile_size-2;
      var height = $scope.tile_size-2;
      var stage = new Konva.Stage({
        container: res.selector.replace("#",""),
        width: width,
        height: height
      });

      // Rotate
      $scope.rotateStage( stage, rotate );

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
          shapes.stroke('');
          shapes.strokeWidth(0);
        });
        stage.add( lay );
        lay.draw();
      });

      hashid = $scope.generHash(hashid);
      stage.setAttr('hashid', hashid);

      $scope.gridStages[gridx+'x'+gridy] = {
        hashid: stage.getAttr('hashid'),
        rotation: stage.getAttr('rotation')
        //stage: stage.toObject()
      };
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
                    shapes.stroke('');
                    shapes.strokeWidth(0);
                    shapes.fill( m.colors[si] );
                    si++;
                  });
                  historystage.add( lay );
                  lay.draw();
                });
                // Refresg stage if colors fixed
                $scope.used_motifs[m.hashid].stage = historystage;
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
    var options = {};

    var layers = motiv.stage.getLayers();
    layers.each(function(layer, n) {
      var lay = layer.clone();
      var si = 0;
      lay.getChildren(function( shapes ){
        shapes.scale({
          x: $scope.calcScaleFactor($scope.workmotiv_size),
          y: $scope.calcScaleFactor($scope.workmotiv_size)
        });
        if ($scope.showStrokes) {
          shapes.stroke('black');
          shapes.strokeWidth($scope.strokeWidth);
        } else {
          shapes.stroke('');
          shapes.strokeWidth(0);
        }
        shapes.on('click', function(){
          this.fill( $scope.currentFillColor );
          lay.draw();
        });
        shapes.fill( motiv.colors[si] );

        // hover tooltip
        shapes.on('mousemove', function(){
          if ( true ) {
            var mousePos = $scope.workstage.getPointerPosition();
            if (mousePos) {
              var toh = 5;
              var tow = 5;
              var tw = mousePos.x + tow;
              var th = mousePos.y + toh;

              var szinkod = $scope.colorsbyrgb[shapes.attrs.fill.replace("#","")].kod;
              $scope.updateTooltip(tw, th, szinkod, shapes.attrs.fill, $scope.colorsbyrgb[shapes.attrs.fill.replace("#","")].szin_ncs);
            }
          }
        });

        shapes.on('mouseout', function(){
          $scope.hideTooltip();
        });

        /*
        shapes.on('mousemove', function(){
          if (options.tooltiplayer && options.tooltip) {
            var sw = $scope.workstage.width();
            var sh = $scope.workstage.height();
            var mousePos = $scope.workstage.getPointerPosition();
            if (mousePos) {
              var toh = 5;
              var tow = 5;
              var tw = mousePos.x + tow;
              var th = mousePos.y + toh;
              var ttext = "";

              ttext += $scope.translate('color')+" "+$scope.colorsbyrgb[shapes.attrs.fill.replace("#","")].kod+"\n";
              ttext += "RGB: "+shapes.attrs.fill+"\n";
              ttext += "NCS: "+$scope.colorsbyrgb[shapes.attrs.fill.replace("#","")].szin_ncs;

              options.tooltip.text(ttext);
              options.tooltipbg.height(options.tooltip.height());
              options.tooltipbg.width(options.tooltip.width());

              if ( mousePos.y >= (sh-options.tooltip.height()-toh) ) {
                th = mousePos.y - options.tooltip.height() - toh;
              }

              if ( mousePos.x >= (sw-options.tooltip.width()-tow) ) {
                tw = mousePos.x - options.tooltip.width() - tow;
              }

              options.tooltip.position({
                x: tw,
                y: th
              });
              options.tooltipbg.position({
                x: tw,
                y: th
              });

              options.tooltip.show();
              options.tooltipbg.show();
              options.tooltiplayer.batchDraw();
            }
          }
        });

        shapes.on('mouseout', function(){
          if (options.tooltiplayer && options.tooltip) {
            options.tooltip.hide();
            options.tooltipbg.hide();
            options.tooltiplayer.draw();
          }
        });
        */

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

  $scope.removeMotivFromGrid = function( res )
  {
    var gridx = res.data('grid-x');
    var gridy = res.data('grid-y');
    var stage = new Konva.Stage({
      container: res.selector.replace("#","")
    });

    stage.remove();
    delete $scope.gridStages[gridx+'x'+gridy];
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

  $scope.order = function(ev) {
    $scope.collectDataToOrder(function( data ) {
      $mdDialog.show({
        controller: OrderDialogController,
        templateUrl: '/ajax/template/order',
        parent: angular.element(document.body),
        targetEvent: ev,
        clickOutsideToClose:true,
        locals: {
          toast: $scope.toast,
          motifs: data.motifs,
          dbnm: $scope.csempenmdb,
          grid: $scope.grid,
          gridstages: $scope.gridStages,
          project: $scope.selected_project
        }
      })
      .then(function(form) {
        if (form) {
        }
      }, function() {

      });
    });
  }

  function OrderDialogController($scope, $mdDialog, toast, motifs, dbnm, grid, gridstages, project ) {
    $scope.toast = toast;
    $scope.motifs = motifs;
    $scope.csempenmdb = dbnm;
    $scope.qtyconf = {};
    $scope.grid = grid;
    $scope.gridStages = gridstages;
    $scope.savingorder = false;
    $scope.project = project;
    $scope.error = false;

    /*$scope.$watch('qtyconf', function(n,o,s){
      console.log(n);
      console.log(o);
    }, true);*/

    $scope.hide = function() {
      $mdDialog.hide(false);
    };

    $scope.cancel = function() {
      $mdDialog.cancel(false);
    };

    $scope.saving = function() {
      $scope.savingorder = true;
      $scope.error = false;

      // Delete stage
      var motifs = angular.copy( $scope.motifs );

      angular.forEach(motifs, function(m,i){
        delete m.stage;
      });

      $http({
        method: 'POST',
        url: '/ajax/post',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        data: $.param({
          type: "Moza",
          mode: 'Order',
          orderer: $scope.order,
          gridsizes: $scope.grid,
          gridconfig: $scope.gridStages,
          qtyconfig: $scope.qtyconf,
          motifs: motifs,
          project: $scope.project.ID
        })
      }).success(function(r){
        $scope.savingorder = false;
        if (r.success == 1) {
          $scope.toast(r.msg, 'success', 5000);
          $mdDialog.hide();
        } else {
          $scope.error = r.msg;
        }
      });
    };

    $scope.modifyQty = function(hash, by) {
      var n = $scope.qtyconf[hash].nm;
      var d = $scope.qtyconf[hash].db;

      // reset
      $scope.qtyconf[hash].nm = 0;
      $scope.qtyconf[hash].db = 0;

      if (by == 'db') {
        $scope.qtyconf[hash].nm = d / $scope.csempenmdb;
        $scope.qtyconf[hash].db = d;
      } else if( by == 'nm') {
        $scope.qtyconf[hash].db = n * $scope.csempenmdb;
        $scope.qtyconf[hash].nm = n;
      }
    }
  }

  $scope.collectDataToOrder = function( callback ) {
    var data = {};
    data.motifs = [];

    if ($scope.used_motifs) {
      angular.forEach($scope.used_motifs, function(e,i){
        var colors = [];
        angular.forEach(e.colors, function(c,ii){
          $scope.findColorObjectByRGB( c.replace("#",""),  function( color ){
            colors.push({
              'obj': color,
              'rgb': c
            })
          } );
        });
        e.coloring = colors;
        e.imageurl = e.stage.toDataURL({
          pixelRatio: 3
        });
        data.motifs.push(e);
      });
    }

    if (typeof callback !== 'undefined') {
      callback( data );
    }
  }

  $scope.saveProject = function(ev)
  {
    $scope.collectDataToSave(function( dataset ) {
      $mdDialog.show({
        controller: SaveDialogController,
        templateUrl: '/ajax/template/saveProject',
        parent: angular.element(document.body),
        targetEvent: ev,
        clickOutsideToClose:true,
        locals: {
          save: {
            name: '',
            email: ''
          }
        }
      })
      .then(function(form) {
        if (form) {
          $scope.savingProject(form.name, form.email, dataset );
        }
      }, function() {

      });
    });
  }

  function SaveDialogController($scope, $mdDialog, save) {
    $scope.save = save;
    $scope.hide = function() {
      $mdDialog.hide(false);
    };

    $scope.cancel = function() {
      $mdDialog.cancel(false);
    };

    $scope.saving = function() {
      $mdDialog.hide($scope.save);
    };
  }

  $scope.collectDataToSave = function( callback ) {
    var set = {};
    var um = {};
    angular.forEach($scope.used_motifs, function(m,i){
      um[i] = {
        colors: m.colors,
        hashid: m.hashid,
        minta: m.minta
      };
    });
    set.used_motifs = um;
    um = null;
    set.used_colors = $scope.used_colors;
    set.grid = {};
    set.grid.size = {
      x: $scope.grid.x,
      y: $scope.grid.y
    };
    set.grid.stages = $scope.gridStages;

    if (typeof callback !== 'undefined') {
      callback(set);
    }
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
      // hover tooltip
      shape.on('mousemove', function(){
        if ( true ) {
          var mousePos = stage.getPointerPosition();
          if (mousePos) {
            var toh = 5;
            var tow = 5;
            var tw = mousePos.x + tow;
            var th = mousePos.y + toh;

            var szinkod = $scope.colorsbyrgb[shape.attrs.fill.replace("#","")].kod;
            $scope.updateTooltip(tw, th, szinkod, shape.attrs.fill, $scope.colorsbyrgb[shape.attrs.fill.replace("#","")].szin_ncs);
          }
        }
      });

      shape.on('mouseout', function(){
        $scope.hideTooltip();
      });
    }

    layer.add( shape );
    stage.add( layer );
    layer.draw();
  }

  $scope.hideTooltip = function()
  {
    $('#motivumtooltip').hide();
  }

  $scope.updateTooltip = function(x, y, kod, rgb, ncs)
  {
    if ($('#motivumtooltip_kod').text() != kod) {
      $('#motivumtooltip_kod').text(kod);
    }
    if ($('#motivumtooltip_rgb').text() != rgb) {
      $('#motivumtooltip_rgb').text(rgb);
    }
    if ($('#motivumtooltip_ncs').text() != ncs) {
      $('#motivumtooltip_ncs').text(ncs);
    }   

    $('#motivumtooltip').css({
      top: y,
      left: x
    }).show();
  }

  $scope.savingProject = function( name, email, data ){
    $http({
      method: 'POST',
      url: '/ajax/post',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Moza",
        mode: 'saveProject',
        form: {
          name: name,
          email: email
        },
        used_colors: data.used_colors,
        used_motifs: data.used_motifs,
        grid: data.grid
      })
    }).success(function(r){
      if (r.success == 1) {
        $scope.toast( r.msg, 'success', 5000);
      } else {
        $scope.toast( r.msg, 'error', 5000);
      }
    });
  }

  $scope.loadProjects = function() {
    if ($scope.project_load_email == '')
    {
      $scope.toast($scope.translate('missing_project_loading_email'), 'error', 5000);
      $('#project_load_email').focus();
    } else {
      $scope.project_loading = true;
      $http({
        method: 'POST',
        url: '/ajax/post',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        data: $.param({
          type: "Moza",
          mode: 'getProjects',
          email: $scope.project_load_email
        })
      }).success(function(r){
        if (r.success == 1) {
          if (r.data.length != 0) {
            $scope.loaded_projects = r.data;
          }
        } else {
          $scope.loaded_projects = [];
          $scope.toast(r.msg, 'warning', 10000);
        }
        $scope.project_loading = false;
      });
    }
  }

  $scope.loadProject = function() {
    var p = $scope.selected_project;
    $scope.loader_title = $scope.translate('loader_title_loadproject');
    $scope.apploading = true;

    // Színek betöltése
    if ( p.used_colors && p.used_colors.length != 0 )  {
      $scope.used_colors = p.used_colors;
    }
    // Szín box-ok méret fixálás
    $scope.fixColorTableSizes(0);

    // Motívumok betöltése és stage generálása
    if ( p.used_motifs && p.used_motifs.length != 0 ) {
      // reset
      $scope.used_motifs = {};
      var width = $scope.color_size-2;
      var height = $scope.color_size-2;

      angular.forEach(p.used_motifs, function(motiv,hash){
        var mot = motiv;
        $scope.used_motifs[hash] = mot;

        $timeout(function(){
          // Motivum stage helyreállítás
          var m = $scope.motivumok[mot.minta];

          if ( m ) {
            // STAGE
            var stage = new Konva.Stage({
              container: 'shapemotiv'+hash,
              width: width,
              height: height
            });
            stage.setAttr('minta', m.mintakod);

            // LAYER
            var layer = new Konva.Layer();

            if ( m.shapes && m.shapes.length ) {
              angular.forEach( m.shapes, function(si, se){
                var settings = {
                  fill: mot.colors[se],
                  shapesize: width
                };
                if ($scope.showStrokes) {
                  settings.stroke = 'black';
                  settings.strokeWidth = $scope.strokeWidth;
                }
                $scope.addShape(
                  stage,
                  layer,
                  si.canvas_js,
                  settings
                );
              });
              $scope.used_motifs[hash].stage = stage;
            }
          }
        }, 100);

      });
    }

    // Grid feltöltése
    var _g = p.grid;
    if ( _g && _g.stages ) {
      // Grid tábla fixálás
      if (_g.size.x && _g.size.y) {
        $scope.grid.x = parseInt(_g.size.x);
        $scope.grid.y = parseInt(_g.size.y);
      }

      if (_g.stages) {
        $scope.resetGrid();
      }

      $timeout(function(){
        angular.forEach(_g.stages, function(h, gridpos){
          var width = $scope.color_size-2;
          var height = $scope.color_size-2;
          var mot = p.used_motifs[h.hashid];
          var rotation = parseInt(h.rotation);

          var fillholder = $('#grid-h'+gridpos);
          var current_stage = $scope.used_motifs[h.hashid].stage;

          if (current_stage) {
            current_stage.setAttr('rotation', rotation);
            $scope.passMotivToResource( fillholder, current_stage, false );
            // reset rotation
            current_stage.setAttr('rotation', 0);
          }
        });

        $scope.loader_title = $scope.translate('default_loader_title');
        $scope.apploading = false;
      }, 100);
    }
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
          angular.forEach($scope.colors, function(c,i){
            $scope.colorsbyrgb[c.szin_rgb] = c;
          });
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
        'default_loader_title': 'MOZA Tervező',
        'loader_title_loadproject': 'Saját projekt betöltése',
        'no_motiv_selected': 'Nincs kiválasztva aktív minta motívum. Válasszon a kategóriák közül.',
        'missing_project_loading_email': 'A projektek betöltéséhez adja meg az e-mail címét!',
        'color':'Szín:',
      },
      'en':{
        'default_loader_title': 'MOZA TILE CONFIGURATOR',
        'loader_title_loadproject': 'Load own projects',
        'no_motiv_selected': 'No motif selected. Please choose one from any categories!',
        'missing_project_loading_email': 'Please give your e-mail address to load your saved projects!',
        'color':'Color:',
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
  var motivcnt = 0;
  var loaded_all = false;
  motivum.restrict = 'E';
  //motivum.scope = true;
  motivum.transclude = true;
  //motivum.replace = true;
  motivum.compile  = function(e, a){
    return function($scope, e, a)
    {
      var konva = {};
      var id = 'katmot'+$scope.m.mintakod+$scope.m.ID;
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
      motivcnt++;
      var parent_motiv_num = $scope.$parent.$parent.motivumnum;
      if( !loaded_all && motivcnt >= parent_motiv_num) {
        loaded_all = true;
        $scope.$parent.$parent.apploading = false;
      }
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
      paddingTop: header_height + 20
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
