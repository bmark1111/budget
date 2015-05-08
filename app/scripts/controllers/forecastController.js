'use strict';

app.controller('ForecastController', function($scope, $rootScope, RestData, $filter)
{
	$rootScope.nav_active = 'forecast';

	$scope.totals = [];
	$scope.rTotals = [];
	$scope.balance_forward = {};
	$scope.result = {};
	$scope.categories = {};

	$scope.dataErrorMsg = false;

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
					$scope.result = response.data.result;
					$scope.result_seq = Object.keys(response.data.result);

					$scope.categories = response.data.categories;
					$scope.categories_seq = Object.keys(response.data.categories);

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
	//			ngProgress.complete();
			});
	}

	loadForecast();

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		loadForecast();
	}

});
