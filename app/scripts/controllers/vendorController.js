'use strict';

app.controller('VendorController', function($scope, $modal, $timeout, RestData2) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages		= 5;
	$scope.vendors		= [];

	$scope.dataErrorMsg	= [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:	1,
		name:			''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllVendors( {
				'name':					$scope.search.name,
				'sort':					'name',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.vendors = response.data.result;
					$scope.vendors_seq = Object.keys(response.data.result);
					$scope.recCount = response.data.total_rows;
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	}

	loadData();

	var timer = null;
	$scope.refreshData = function() {
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
		loadData();
	};

	$scope.pageChanged = function() {
		loadData();
	};

	// open date picker
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.addVendor = function() {
		var modalInstance = $modal.open({
			templateUrl: 'editVendorModal.html',
			controller: 'EditVendorModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: 0,
								title: 'Add Payer/Payee'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Vendor Modal dismissed at: ' + new Date());
		});
	};

	$scope.editVendor = function(vendor_id) {
		var modalInstance = $modal.open({
			templateUrl: 'editVendorModal.html',
			controller: 'EditVendorModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: vendor_id,
								title: 'Edit Payer/Payee'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Payer/Payee Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteVendor = function (vendor_id) {
		var modalInstance = $modal.open({
			templateUrl: 'deleteModal.html',
			controller: 'DeleteVendorModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: vendor_id,
								title: 'Delete Payer/Payee ?',
								msg: 'Are you sure you want to delete this Payer/Payee. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Payer/Payee Modal dismissed at: ' + new Date());
		});
	};

});