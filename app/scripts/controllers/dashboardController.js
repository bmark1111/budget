'use strict';

app.controller('DashboardController', function($scope, $rootScope, RestData2, $filter)//, $localStorage, $location)//, $popover)
{
	$scope.intervals = [];

	$scope.categories = $rootScope.categories;

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var loadForecast = function()
	{
		$scope.dataErrorMsg = [];

//		RestData(
//			{
//				Authorization:		$localStorage.authorization,
//				'TOKENID':			$localStorage.token_id,
//				'X-Requested-With':	'XMLHttpRequest'
//			})
		RestData2().getForecast(
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
								if ((now >= sd && now <= ed) || now < ed)
								{
									// check to see what current values need to be from the forecast
									angular.forEach($scope.intervals[key].totals,
										function(total, x)
										{
											if (total == 0 && interval.totals[x] != 0)
											{
												$scope.intervals[key].totals[x] = interval.totals[x];						// use the forcasted amount
												$scope.intervals[key].types[x] = '1';										// flag this as a forecast total
												$scope.intervals[key].interval_total += parseFloat(interval.totals[x]);		// update the interval total
											}
										});
								}
							});

						// now calculate running totals
						angular.forEach($scope.intervals,
							function(interval, key)
							{
								if (key == 0)
								{
//									interval.running_total = parseFloat(response.data.balance_forward + interval.interval_total);
									interval.running_total = parseFloat(interval.balance_forward + interval.interval_total);
								} else {
									var x = key - 1;
									interval.running_total = parseFloat($scope.intervals[x].running_total + interval.interval_total);
								}
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
//				},
//				function (error)
//				{
//					if (error.status == '401' && error.statusText == 'EXPIRED')
//					{
//						$localStorage.authenticated		= false;
//						$localStorage.authorizedRoles	= false;
//						$localStorage.userFullName		= false;
//						$localStorage.token_id			= false;
//						$localStorage.authorization		= false;
//						$location.path("/login");
//					} else {
//						$rootScope.error = error.status + ' ' + error.statusText;
//					}
				});
	}

	var loadTransactions = function()
	{
		$scope.dataErrorMsg = [];

//		RestData(
//			{
//				Authorization:		$localStorage.authorization,
//				'TOKENID':			$localStorage.token_id,
//				'X-Requested-With':	'XMLHttpRequest'
//			})
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
								$scope.intervals[key].types = [];

								$scope.intervals[key].interval_total = parseFloat(0);	// zero the interval total
								angular.forEach($scope.intervals[key].totals,
									function(total, x)
									{
										$scope.intervals[key].types[x] = '0';			// flag this as a transaction total
										$scope.intervals[key].interval_total += parseFloat(total);
									});
							});

						loadForecast();
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
//				},
//				function (error)
//				{
//					if (error.status == '401' && error.statusText == 'EXPIRED')
//					{
//						$localStorage.authenticated		= false;
//						$localStorage.authorizedRoles	= false;
//						$localStorage.userFullName		= false;
//						$localStorage.token_id			= false;
//						$localStorage.authorization		= false;
//						$location.path("/login");
//					} else {
//						$rootScope.error = error.status + ' ' + error.statusText;
//					}
				});
	}

	loadTransactions();

	$scope.showTheseTransactions = function(interval_ending, category_id, index)
	{
		$scope.dataErrorMsgThese = false;

		var date = $filter('date')(interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + index + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

//		RestData(
//			{
//				Authorization:		$localStorage.authorization,
//				'TOKENID':			$localStorage.token_id,
//				'X-Requested-With':	'XMLHttpRequest'
//			})
		RestData2().getTheseTransactions(
				{
					interval_ending:	interval_ending,
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
//				},
//				function (error)
//				{
//					if (error.status == '401' && error.statusText == 'EXPIRED')
//					{
//						$localStorage.authenticated		= false;
//						$localStorage.authorizedRoles	= false;
//						$localStorage.userFullName		= false;
//						$localStorage.token_id			= false;
//						$localStorage.authorization		= false;
//						$location.path("/login");
//					} else {
//						$scope.errorThese = error.status + ' ' + error.statusText;
//					}
				});
	}

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		loadTransactions();
	}

});
