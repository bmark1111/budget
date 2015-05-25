'use strict';

app.controller('ForecastController', function($scope, $rootScope, $localStorage, $location, $modal, $timeout, RestData, $filter)
{
	$rootScope.nav_active = 'forecast';

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

	$scope.dataErrorMsg = false;
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:		1,
		first_due_date:		'',
		description:		'',
		amount:				''
	};

	var interval = 0;

	var loadForecast = function()
	{
		RestData(
			{
				Authorization:		$localStorage.authorization,
//				Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getForecast(
				{
					interval: interval
				},
				function(response)
				{
					if (!!response.success)
					{
						$scope.result = response.data.result;
						$scope.result_seq = Object.keys(response.data.result);

						$scope.categories = $rootScope.categories;

						// now calulate totals
						angular.forEach($scope.result,
							function(total, key)
							{
								$scope.balance_forward[key] = ''
								$scope.totals[key] = parseFloat(0);
								angular.forEach(total.totals,
									function(value)
									{
										$scope.totals[key] += parseFloat(value);
									});
							});

						// now set the balance forward
						$scope.balance_forward[0] = $filter('currency')(response.data.balance_forward, "$", 2);

						// now calculate running totals
						angular.forEach($scope.totals,
							function(total, key)
							{
								if (key == 0)
								{
									$scope.rTotals[key] = parseFloat(response.data.balance_forward) + total;
								} else {
									var x = key - 1;
									$scope.rTotals[key] = $scope.rTotals[x] + total;
								}
							});
					} else {
						$scope.dataErrorMsg = response.errors[0];
					}
//					ngProgress.complete();
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
//						$localStorage.username			= false;
//						$localStorage.password			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

	var loadAllForecasts = function()
	{
		$scope.dataErrorMsg = false;

//		ngProgress.start();

		var searchCriteria = {
						'first_due_date':		$scope.search.first_due_date,
						'description':			$scope.search.description,
						'amount':				$scope.search.amount,
						'sort':					'first_due_date',
						'sort_dir':				'DESC',
						'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
						'pagination_amount':	$scope.itemsPerPage
		};

		RestData(
			{
				Authorization:		$localStorage.authorization,
//				Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getAllForecasts(searchCriteria,
				function(response)
				{
					if (!!response.success)
					{
						$scope.forecasts = response.data.result;
						$scope.forecasts_seq = Object.keys(response.data.result);
						$scope.recCount = response.data.total_rows;
					} else {
						if (response.errors)
						{
							$scope.dataErrorMsg = response.errors[0].error;
						} else {
							$scope.dataErrorMsg = response;
						}
					}
//					ngProgress.complete();
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
//						$localStorage.username			= false;
//						$localStorage.password			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

//	loadForecast();
	loadAllForecasts();

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		loadForecast();
	}

	var timer = null;
	$scope.refreshData = function()
	{
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadAllForecasts, 1000);
		loadAllForecasts();
	};

	$scope.pageChanged = function()
	{
		loadAllForecasts();
	};

	// open date picker
	$scope.open = function($event)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.addForecast = function()
	{
		var modalInstance = $modal.open({
			templateUrl: 'editForecastModal.html',
			controller: 'EditForecastModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function()
					{
						return {
							id: 0,
							title: 'Add Forecast'
						}
					}
			}
		});

		modalInstance.result.then(function ()
		{
			loadAllForecasts();
		},
		function ()
		{
			console.log('Add Forecast Modal dismissed at: ' + new Date());
		});
	};

	$scope.editForecast = function(forecast_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'editForecastModal.html',
			controller: 'EditForecastModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function()
					{
						return {
							id: forecast_id,
							title: 'Edit Forecast'
						}
					}
			}
		});

		modalInstance.result.then(function ()
		{
			loadAllForecasts();
		},
		function ()
		{
			console.log('Edit Forecast Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteForecast = function (forecast_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'deleteForecastModal.html',
			controller: 'DeleteForecastModalController',
			size: 'sm',
			resolve: {
				params: function()
					{
						return {
							id: forecast_id,
							title: 'Delete Forecast ?',
							msg: 'Are you sure you want to delete this forecast. This action cannot be undone.'
						}
					}
			}
		});

		modalInstance.result.then(function ()
		{
			loadAllForecasts();
		},
		function ()
		{
			console.log('Delete Forecast Modal dismissed at: ' + new Date());
		});
	};

});
