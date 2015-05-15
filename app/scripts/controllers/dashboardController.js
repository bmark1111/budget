'use strict';

app.controller('DashboardController', function($scope, $rootScope, RestData, $filter, $location)
{
	$rootScope.nav_active = 'dashboard';

	$scope.totals = [];				// transaction totals by date
	$scope.startDate = [];			// interval start dates
	$scope.endDate = [];			// interval end dates
	$scope.ftotals = [];			// forecast totals by date
	$scope.fstartDate = [];			// forecast start dates
	$scope.fendDate = [];			// forecast end dates
	$scope.rTotals = [];			// running transaction totals
	$scope.rfTotals = [];			// running forecast totals
	$scope.balance_forward = {};

	$scope.result = {};
	$scope.forecast = {};
	$scope.categories = [];

	$scope.dataErrorMsg = false;
	$scope.dataErrorMsg2 = false;
	$scope.isVisible = false;

	var currentDate = new Date();
	var interval = 0;
	
	var loadForecast = function()
	{
		RestData.getForecast(
			{
				interval: interval
			},
			function(response)
			{
				if (!!response.success)
				{
					$scope.forecast = response.data.result;
					$scope.forecast_seq = Object.keys(response.data.result);

					// now calulate totals
					angular.forEach($scope.forecast,
						function(total, key)
						{
							$scope.ftotals[key]		= parseFloat(0);
							$scope.fstartDate[key]	= total.interval_beginning;
							$scope.fendDate[key]	= total.interval_ending;
							angular.forEach(total.totals,
								function(value, key2)
								{
									if (currentDate.toISOString() <= total.interval_beginning)
									{	// only add in forecast amounts past the current date
										$scope.ftotals[key] += parseFloat(value);
									} else {
										$scope.forecast[key].totals[key2] = 0;
									}
								});
						});

					// now calculate forecast running totals
					angular.forEach($scope.ftotals,
						function(total, key)
						{
							if (key == 0)
							{
								$scope.rfTotals[key] = parseFloat(total);
							} else {
								var x = key - 1;
								$scope.rfTotals[key] = $scope.rfTotals[x] + parseFloat(total);
							}
						});
				} else {
					$scope.dataErrorMsg = response.errors[0];
				}
	//			ngProgress.complete();
			});
	}

	var loadTransactions = function()
	{
		RestData.getTransactions(
			{
				interval: interval
			},
			function(response)
			{
				if (!!response.success)
				{
					$scope.result = response.data.result;
					$scope.result_seq = Object.keys(response.data.result);

					angular.forEach($rootScope.categories,
						function(category)
						{
							$scope.categories.push(category)
						});

					// now calulate totals
					angular.forEach($scope.result,
						function(total, key)
						{
							$scope.balance_forward[key]	= '';
							$scope.totals[key]			= parseFloat(0);
							$scope.startDate[key]		= total.interval_beginning;
							$scope.endDate[key]			= total.interval_ending;

							// set the current interval
							var sd = new Date(total.interval_beginning);
							var ed = new Date(total.interval_ending);
							var now = new Date();
							if (now >= sd && now <= ed)
							{
							console.log('BINGO')
								total.current_interval = true;
							} else {
								total.current_interval = false;
							}
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
								$scope.rTotals[key] = parseFloat(response.data.balance_forward + total);
							} else {
								var x = key - 1;
								$scope.rTotals[key] = parseFloat($scope.rTotals[x] + total);
							}
						});
				} else {
					$scope.dataErrorMsg = response.errors[0];
				}
	//			ngProgress.complete();
			});
	}

	loadForecast();
	loadTransactions();

	$scope.showTheseTransactions = function(interval_beginning, category_id)
	{
		$scope.dataErrorMsg2 = false;

		RestData.getTheseTransactions(
			{
				interval_beginning:	interval_beginning,
				category_id:	category_id
			},
			function(response)
			{
				if (!!response.success)
				{
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
				} else {
					$scope.dataErrorMsg2 = response.errors[0];
				}
			});
	};

	$scope.showThisForecast = function(interval_beginning, category_id)
	{
		$scope.dataErrorMsg2 = false;

		RestData.getThisForecast(
			{
				interval_beginning:	interval_beginning,
				category_id:	category_id
			},
			function(response)
			{
				if (!!response.success)
				{
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
				} else {
					$scope.dataErrorMsg2 = response.errors[0];
				}
			});
	};

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		loadForecast();
		loadTransactions();
	}

});
