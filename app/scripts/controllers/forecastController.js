'use strict';

app.controller('ForecastController', function($q, $scope, $modal, $timeout, RestData2, Accounts, Categories) {

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
		bank_account_id:	'',
		category_id:		'',
		amount:				''
	};

	var loadAllForecasts = function() {
		$scope.dataErrorMsg = [];

		RestData2().getAllForecasts({
				'last_due_date':		$scope.search.last_due_date,
				'first_due_date':		$scope.search.first_due_date,
				'description':			$scope.search.description,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
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

	var getForecasts = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllForecasts({
				'last_due_date':		$scope.search.last_due_date,
				'first_due_date':		$scope.search.first_due_date,
				'description':			$scope.search.description,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
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

	$q.all([
		Accounts.get(),
		Categories.get(),
		getForecasts()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
		// load the categories
		$scope.categories = Categories.data;

		// load the transaction
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.forecasts = response[2].data.result;
				for(var x in $scope.forecasts) {
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == $scope.forecasts[x].bank_account_id) {
							$scope.forecasts[x].bankName = $scope.accounts[y].name;
							break;
						}
					}
				}
				$scope.forecasts_seq = Object.keys(response[2].data.result);
				$scope.recCount = response[2].data.total_rows;
			}
		} else {
			if (response[2].errors) {
				angular.forEach(response[2].errors,
					function(error) {
						$scope.dataErrorMsg.push(error.error);
					})
			} else {
				$scope.dataErrorMsg[0] = response[2];
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