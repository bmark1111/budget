'use strict';

app.controller('DashboardController', function($scope, $rootScope, RestData2, $filter)//, $localStorage, $location)//, $popover)
{
	$scope.intervals = [];

	$scope.categories = $rootScope.categories;

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var loadTransactions = function()
	{
		$scope.dataErrorMsg = [];

		RestData2().getTransactions(
				{
					interval: interval
				},
				function(response)
				{
					if (!!response.success)
					{
						// now calulate totals
						angular.forEach(response.data.result,
							function(interval, key)
							{
								// set the current interval
								var sd = new Date(interval.interval_beginning);
								var ed = new Date(interval.interval_ending);
								var now = new Date();
								interval.current_interval = (now >= sd && now <= ed) ? true: false;

								$scope.intervals[key] = interval;
							});

					} else {
						if (response.errors)
						{
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
				});
	}

	loadTransactions();

	$scope.showTheseTransactions = function(category_id, index)
	{
		$scope.dataErrorMsgThese = false;

		var date = $filter('date')(interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + index + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

		RestData2().getTheseTransactions(
				{
					interval_beginning:	$scope.intervals[index].interval_beginning,
					interval_ending:	$scope.intervals[index].interval_ending,
					category_id:		category_id
				},
				function(response)
				{
					if (!!response.success)
					{
						$scope.transactions = response.data.result;
						$scope.transactions_seq = Object.keys(response.data.result);
					} else {
						$scope.dataErrorMsgThese = response.errors;
					}
				});
	}

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		loadTransactions();
	}

});
