/**
* Dokumentumok
**/
var a = angular.module('Moza', ['ngMaterial', 'ngSanitize']);

a.controller("MotifConfigurator", ['$scope', '$http', '$mdToast', function($scope, $http, $mdToast)
{
	$scope.motiv_size = 364;
	$scope.kategoria_lista = [];
  $scope.colors = [];
	$scope.motivumkod = '';
	$scope.motivum = {};
	$scope.motifs = [];
	$scope.loadid = 0;
	$scope.calcScaleFactor = function( size ){
    return parseFloat( size / 200);
  }

	$scope.init = function( id )
	{
		$scope.loadid = id ;
		$scope.loadSettings(function()
    {
			$scope.loadMotivum(function( motivum ){
				motivum.lathato = (motivum.lathato == '1') ? true : false;
				motivum.sorrend = parseInt(motivum.sorrend);
				$scope.motivumkod = motivum.mintakod;
				$scope.motivum = motivum;
				console.log(motivum);
			});
		});
	}

	$scope.createMotivum = function() {
		$scope.saveMotivum(function(){
			$scope.init( $scope.loadid );
		});
	}

	$scope.saveMotivum = function( callback ){
		$http({
			method: 'POST',
			url: '/ajax/post',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			data: $.param({
				type: "Moza",
				mode: 'addMotivum',
				id: $scope.loadid,
				motivum: $scope.motivum
			})
		}).success(function(r){
			console.log(r);
			if (r.success == 1) {
				$scope.toast(r.msg, 'success', 5000);
			} else {
				$scope.toast(r.msg, 'success', 5000);
			}
			if (typeof callback !== 'undefined') {
				callback(r.data);
			}
		});
	}

	$scope.loadMotivum = function( callback ){
    $http({
      method: 'POST',
      url: '/ajax/post',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Moza",
        mode: 'getMotivumok',
				getid: $scope.loadid,
				admin: 1
      })
    }).success(function(r){
			$scope.motifs = r.data;
      if (typeof callback !== 'undefined') {
        callback(r.data[0]);
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


a.controller("MotifsEditor", ['$scope', '$http', '$mdToast', function($scope, $http, $mdToast)
{
  $scope.kategoria_lista = [];
  $scope.colors = [];
	$scope.motifs = {};
	$scope.motiv_size = 80;

	$scope.calcScaleFactor = function( size ){
    return parseFloat( size / 200);
  }

	$scope.init = function( id ){
		$scope.loadSettings(function()
    {
      $scope.loadMotivums(function( motivums )
      {
				angular.forEach(motivums, function(e,i){
					if (typeof $scope.motifs[e.kat_hashkey] === 'undefined') {
						$scope.motifs[e.kat_hashkey] = [];
					}
					$scope.motifs[e.kat_hashkey].push(e);
				});
      });
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

	$scope.loadMotivums = function( callback ){
    $http({
      method: 'POST',
      url: '/ajax/post',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Moza",
        mode: 'getMotivumok',
				admin: 1
      })
    }).success(function(r){
			console.log(r);
      if (typeof callback !== 'undefined') {
        callback(r.data);
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

a.controller("DocumentList", ['$scope', '$http', '$sce', '$mdToast', function($scope, $http, $sce, $mdToast)
{
	$scope.docs = [];
	$scope.docs_inserted_ids = [];
	$scope.searchdocs = [];
	$scope.selectedItem = null;
	$scope.searcher = null;
	$scope.loading = false;
	$scope.termid = 0;
	$scope.error = false;
	$scope.docs_in_sync = false;

	$scope.init = function( id ){
		$scope.termid = id;
		$scope.loadDocsList( function( docs ){
			$scope.searchdocs = docs;
			$scope.loadList();
		} );
	}

	$scope.findSearchDocs = function( src ) {
		var result = src ? $scope.searchdocs.filter( $scope.filterForSearch( src ) ) : $scope.searchdocs;

		return result;
	}

	$scope.filterForSearch = function( query ){
		var lowercaseQuery = angular.lowercase(query);

    return function filterFn(item) {
      return (item.value.indexOf(lowercaseQuery) !== -1);
    };
	}

	$scope.searchTextChange = function(text) {
		console.log( 'searchTextChange: ' + text );
  }

	$scope.selectedItemChange = function( item )
	{
		if ( item && typeof item !== 'undefined' && typeof item.ID !== 'undefined') {
			var checkin = $scope.docs_inserted_ids.indexOf( parseInt(item.ID) );
			if ( checkin === -1 ) $scope.docs_inserted_ids.push(parseInt(item.ID));
		}

		if (typeof item !== 'undefined') {
			if ( checkin === -1 ) $scope.docs.push(item);
		}

		$scope.syncDocuments(function(){

		});
	}

	$scope.loadDocsList = function( callback )
	{
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Documents",
        key: 'DocsList'
      })
    }).success(function( r ){
			if (typeof callback !== 'undefined') {
				callback( r.data.map(function(doc){
					doc.value = doc.cim.toLowerCase();
					return doc;
				}) );
			}
    });
	}

	$scope.removeDocument = function(docid){
		$scope.docs_in_sync = true;
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Documents",
        key: 'RemoveItemFromList',
				id: $scope.termid,
				docid: docid
      })
    }).success(function( r ){
			$scope.docs_in_sync = false;
			$scope.toast('Dokumentum eltávolítva. Lista mentve.', 'success', 5000);
			$scope.loadList();
    });
	}

	$scope.syncDocuments = function( callback )
	{
		$scope.docs_in_sync = true;
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Documents",
        key: 'SaveList',
				id: $scope.termid,
				list: $scope.docs
      })
    }).success(function( r ){
			console.log(r);
			$scope.docs_in_sync = false;
			if ( r.synced == 0 ) {
				$scope.toast('Dokumentum lista mentve. Nem történt új dokumentumfelvétel.', 'warning', 5000);
			} else {
				$scope.toast(r.synced + 'db új dokumentum hozzáadva a termékhez.', 'success', 8000);
			}
			if (typeof callback !== 'undefined') {
				callback();
			}
    });
	}

	$scope.loadList = function()
	{
		$scope.loading = true;
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Documents",
        key: 'List',
				id: $scope.termid
      })
    }).success(function(r){
			$scope.loading = false;
			if (r.error == 0) {
				$scope.error = false;
				if ( r.data.length != 0) {
					$scope.docs = r.data;
					angular.forEach( $scope.docs, function(v,k) {
						$scope.docs_inserted_ids.push(parseInt(v.doc_id));
					});
				}
			} else {
				$scope.error = r.msg;
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


a.directive('motivum', function($rootScope){
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
              y: $scope.calcScaleFactor($scope.motiv_size)
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
