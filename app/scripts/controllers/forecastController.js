'use strict';

app.controller('ForecastController', function($q, $scope, $modal, $timeout, RestData2, $filter, Categories, Accounts) {

	$scope.totals = [];
	$scope.rTotals = [];
	$scope.balance_forward = {};
	$scope.result = {};
	$scope.categories = [];

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages		= 5;
	$scope.forecasts	= [];

	$scope.dataErrorMsg = [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:		1,
		last_due_date:		false,
		first_due_date:		'',
		description:		'',
		amount:				''
	};

	var interval = 0;

//	var loadForecast = function() {
//		$scope.dataErrorMsg = [];
//
//		RestData2().getForecast({
//				interval: interval
//			},
//			function(response) {
//				if (!!response.success) {
//					$scope.result = response.data.result;
//					$scope.result_seq = Object.keys(response.data.result);
//
//					$scope.categories = Categories.data;
//
//					// now calulate totals
//					angular.forEach($scope.result,
//						function(total, key) {
//							$scope.balance_forward[key] = ''
//							$scope.totals[key] = parseFloat(0);
//							angular.forEach(total.totals,
//								function(value) {
//									$scope.totals[key] += parseFloat(value);
//								});
//						});
//
//					// now set the balance forward
//					$scope.balance_forward[0] = $filter('currency')(response.data.balance_forward, "$", 2);
//
//					// now calculate running totals
//					angular.forEach($scope.totals,
//						function(total, key) {
//							if (key == 0) {
//								$scope.rTotals[key] = parseFloat(response.data.balance_forward) + total;
//							} else {
//								var x = key - 1;
//								$scope.rTotals[key] = $scope.rTotals[x] + total;
//							}
//						});
//				} else {
//					if (response.errors) {
//						angular.forEach(response.errors,
//							function(error) {
//								$scope.dataErrorMsg.push(error.error);
//							})
//					} else {
//						$scope.dataErrorMsg[0] = response;
//					}
//				}
////				ngProgress.complete();
//			});
//	};

	var loadAllForecasts = function() {
		$scope.dataErrorMsg = [];

		RestData2().getAllForecasts({
				'last_due_date':		$scope.search.last_due_date,
				'first_due_date':		$scope.search.first_due_date,
				'description':			$scope.search.description,
				'amount':				$scope.search.amount,
				'sort':					'first_due_date',
				'sort_dir':				'DESC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.forecasts = response.data.result;
					for(var x in $scope.forecasts) {
						for(var y = 0; y < $scope.accounts.length; y++) {
							if ($scope.accounts[y].id == $scope.forecasts[x].bank_account_id) {
								$scope.forecasts[x].bankName = $scope.accounts[y].name;
								break;
							}
						}
					}
					$scope.forecasts_seq = Object.keys(response.data.result);
					$scope.recCount = response.data.total_rows;
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								$scope.dataErrorMsg.push(error.error);
							});
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	};

//	loadAllForecasts();

	var getForecasts = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllForecasts({
				'last_due_date':		$scope.search.last_due_date,
				'first_due_date':		$scope.search.first_due_date,
				'description':			$scope.search.description,
				'amount':				$scope.search.amount,
				'sort':					'first_due_date',
				'sort_dir':				'DESC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});

		return deferred.promise;
	};

//	loadData();
	$q.all([
		Accounts.get(),
		getForecasts()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;

		// load the transaction
		if (!!response[1].success) {
			if (response[1].data.result) {
				$scope.forecasts = response[1].data.result;
				for(var x in $scope.forecasts) {
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == $scope.forecasts[x].bank_account_id) {
							$scope.forecasts[x].bankName = $scope.accounts[y].name;
							break;
						}
					}
				}
				$scope.forecasts_seq = Object.keys(response[1].data.result);
			}
		} else {
			if (response[1].errors) {
				angular.forEach(response[1].errors,
					function(error) {
						$scope.dataErrorMsg.push(error.error);
					})
			} else {
				$scope.dataErrorMsg[0] = response[1];
			}
		}
	});

//	$scope.moveInterval = function(direction) {
//		interval = interval + direction;
//
//		loadForecast();
//	};

	var timer = null;
	$scope.refreshData = function() {
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadAllForecasts, 1000);
	};

	$scope.pageChanged = function() {
		loadAllForecasts();
	};

	// open date picker
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.addForecast = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editForecastModal.html',
			controller: 'EditForecastModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: 0,
								title: 'Add Forecast'
							}
						}
			}
		});

		modalInstance.result.then(
			function () {
				loadAllForecasts();
			},
			function () {
				console.log('Add Forecast Modal dismissed at: ' + new Date());
			});
	};

	$scope.editForecast = function(forecast_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editForecastModal.html',
			controller: 'EditForecastModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: forecast_id,
								title: 'Edit Forecast'
							}
						}
			}
		});

		modalInstance.result.then(
			function () {
				loadAllForecasts();
			},
			function () {
				console.log('Edit Forecast Modal dismissed at: ' + new Date());
			});
	};

	$scope.deleteForecast = function (forecast_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteForecastModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: forecast_id,
								title: 'Delete Forecast ?',
								msg: 'Are you sure you want to delete this forecast. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(
			function () {
				loadAllForecasts();
			},
			function () {
				console.log('Delete Forecast Modal dismissed at: ' + new Date());
			});
	};

});