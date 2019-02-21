/**
* Dokumentumok
**/
var a = angular.module('Moza', ['ngMaterial', 'ngSanitize']);

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

a.controller("VehicleArticleConfig", ['$scope', '$http', '$mdToast', function($scope, $http, $mdToast)
{
	$scope.vehicles = [];
	$scope.cvehicle = {
		title: '',
		manufacturer: false,
		type: false,
		evejarat_end: '',
		evejarat_start: ''
	};
	$scope.compatible = [];
	$scope.loading = false;
	$scope.termid = 0;
	$scope.error = false;

	$scope.init = function( id ){
		$scope.termid = id;
		$scope.loadVehicles( function(){
			$scope.loadArticleCompatibility(function(){

			});
		});
	}

	$scope.addConfig = function()
	{
		$scope.saveing_config = true;
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Vehicles",
				id: $scope.termid,
				config: $scope.cvehicle,
        key: 'registerConfig'
      })
    }).success(function( r ){
			$scope.saveing_config = false;
			$scope.init($scope.termid);
			console.log(r);
    });
	}

	$scope.removeModel = function( id )
	{
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Vehicles",
				id: $scope.termid,
				mid: id,
        key: 'removeModelConfig'
      })
    }).success(function( r ){
			console.log(r);
			$scope.init($scope.termid);
    });
	}

	$scope.removeRestriction = function( restid )
	{
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Vehicles",
				id: $scope.termid,
				restid: restid,
        key: 'removeRestriction'
      })
    }).success(function( r ){
			$scope.init($scope.termid);
    });
	}

	$scope.loadArticleCompatibility = function( callback )
	{
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Vehicles",
				id: $scope.termid,
        key: 'loadArticleCompatibility'
      })
    }).success(function( r ){
			console.log(r);
			if (r.data.length != 0) {
				$scope.compatible = r.data;
			} else {
				$scope.compatible = false;
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

	$scope.loadVehicles = function( callback )
	{
		$scope.loading = true;
		$http({
      method: 'POST',
      url: '/ajax/get',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      data: $.param({
        type: "Vehicles",
        key: 'getVehicles'
      })
    }).success(function(r){
			if (typeof callback !== 'undefined') {
				callback();
			}
			$scope.loading = false;
			if (r.error == 0) {
				$scope.error = false;
				if ( r.data.length != 0) {
					$scope.vehicles = r.data;
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
