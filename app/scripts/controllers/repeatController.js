'use strict';

app.controller('RepeatController', function($scope, $modal, $timeout, RestData2) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages		= 5;
	$scope.repeats		= [];

	$scope.dataErrorMsg	= [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:	1,
		last_due_date:	false,
		name:			''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllRepeats( {
				'last_due_date':		$scope.search.last_due_date,
				'name':					$scope.search.name,
				'sort':					'next_due_date',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.repeats = response.data.result;
					$scope.repeats_seq = Object.keys(response.data.result);
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

	$scope.addRepeat = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editRepeatModal.html',
			controller: 'EditRepeatModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: 0,
								title: 'Add Repeat'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Repeat Modal dismissed at: ' + new Date());
		});
	};

	$scope.editRepeat = function(repeat_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editRepeatModal.html',
			controller: 'EditRepeatModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: repeat_id,
								title: 'Edit Repeat'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Repeat Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteRepeat = function (repeat_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteRepeatModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: repeat_id,
								title: 'Delete Repeat ?',
								msg: 'Are you sure you want to delete this Repeat. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Repeat Modal dismissed at: ' + new Date());
		});
	};

});