'use strict';

app.controller('DashboardController', function($scope, $rootScope, RestData, $filter, $localStorage, $location)
{
//	$scope.totals = [];				// transaction totals by date
//	$scope.startDate = [];			// interval start dates
//	$scope.endDate = [];			// interval end dates
//	$scope.ftotals = [];			// forecast totals by date
//	$scope.fstartDate = [];			// forecast start dates
//	$scope.fendDate = [];			// forecast end dates
//	$scope.rTotals = [];			// running transaction totals
//	$scope.rfTotals = [];			// running forecast totals
	$scope.balance_forward = {};

	$scope.transactions = [];
//	$scope.result = {};
//	$scope.forecast = {};
//	$scope.categories = [];
	$scope.categories = $rootScope.categories;

	$scope.dataErrorMsg = false;
	$scope.dataErrorMsg2 = false;
	$scope.isVisible = false;

	var currentDate = new Date();
	var interval = 0;

	var loadForecast = function()
	{
		RestData(
			{
				Authorization:		$localStorage.authorization,
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
									angular.forEach($scope.transactions[key].totals,
										function(total, x)
										{
											if (total == 0 && interval.totals[x] != 0)
											{
												$scope.transactions[key].totals[x] = interval.totals[x];						// use the forcasted amount
												$scope.transactions[key].types[x] = '1';										// flag this as a forecast total
												$scope.transactions[key].interval_total += parseFloat(interval.totals[x]);		// update the interval total
											}
										});
								}
							});

						// now calculate running totals
						angular.forEach($scope.transactions,
							function(interval, key)
							{
								if (key == 0)
								{
									interval.running_total = parseFloat(response.data.balance_forward + interval.interval_total);
								} else {
									var x = key - 1;
									interval.running_total = parseFloat($scope.transactions[x].running_total + interval.interval_total);
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
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

	var loadTransactions = function()
	{
		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getTransactions(
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

								$scope.transactions[key] = interval;
								$scope.transactions[key].types = [];

								$scope.transactions[key].interval_total = parseFloat(0);	// zero the interval total
								angular.forEach($scope.transactions[key].totals,
									function(total, x)
									{
										$scope.transactions[key].types[x] = '0';			// flag this as a transaction total
										$scope.transactions[key].interval_total += parseFloat(total);
									});
							});
console.log($scope.transactions)
						// now set the balance forward
						$scope.balance_forward[0] = $filter('currency')(response.data.balance_forward, "$", 2);

//						// now calculate running totals
//						angular.forEach($scope.transactions,
//							function(interval, key)
//							{
//								if (key == 0)
//								{
//									interval.running_total = parseFloat(response.data.balance_forward + interval.interval_total);
//								} else {
//									var x = key - 1;
//									interval.running_total = parseFloat($scope.transactions[x].running_total + interval.interval_total);
//								}
//							});

						// load the forecast
						loadForecast();
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
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

//	loadForecast();
	loadTransactions();

$scope.dynamicPopover = {
	content: 'Hello, World!aaaa',
	templateUrl: 'myPopoverTemplate.html',
	title: 'Title'
};

	$scope.showTheseTransactions = function(interval_beginning, category_id)
	{
		$scope.dataErrorMsg2 = false;

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getTheseTransactions(
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
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	};

	$scope.showThisForecast = function(interval_beginning, category_id)
	{
		$scope.dataErrorMsg2 = false;

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getThisForecast(
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
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	};

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

//		loadForecast();
		loadTransactions();
	}

});
