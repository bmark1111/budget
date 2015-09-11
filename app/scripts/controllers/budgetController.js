'use strict';

app.controller('BudgetController', ['$q', '$scope', '$rootScope', 'RestData2', '$filter', function($q, $scope, $rootScope, RestData2, $filter) {

	$scope.intervals = [];
	$scope.start_interval = 0;

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;
/*
INSERT INTO `bank_account_balance` (`bank_id`, `date`, `balance`, `is_deleted`, `updated_by`, `updated_at`, `created_by`, `created_at`) VALUES
(1, '2015-06-10', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-06-17', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-06-24', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-07-01', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-07-08', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-07-15', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-07-22', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-07-29', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-08-05', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-08-12', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-08-19', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-08-26', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-09-02', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(1, '2015-09-09', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-06-10', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-06-17', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-06-24', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-07-01', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-07-08', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-07-15', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-07-22', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-07-29', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-08-05', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-08-12', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-08-19', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-08-26', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-09-02', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(2, '2015-09-09', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-06-10', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-06-17', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-06-24', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-07-01', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-07-08', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-07-15', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-07-22', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-07-29', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-08-05', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-08-12', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-08-19', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-08-26', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-09-02', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19'),
(3, '2015-09-09', 2147.30, 0, 1, '2015-09-10 00:00:00', 1, '2015-09-10 21:17:19');
*/

//	RestData2().getBankBalances(
//		function(response)
//		{
//			if (!!response.success)
//			{
//				$scope.accounts = response.data.accounts;
//			} else {
//				if (response.errors)
//				{
//					angular.forEach(response.errors,
//						function(error)
//						{
//							$scope.dataErrorMsg.push(error.error);
//						})
//				} else {
//					$scope.dataErrorMsg[0] = response;
//				}
//			}
//		});

	var loadTransactions = function() {
		$scope.dataErrorMsg = [];

		RestData2().getTransactions(
				{
					interval: interval
				},
				function(response)
				{
					if (!!response.success) {
						// set current interval
						angular.forEach(response.data.result,
							function(interval, key) {
								var sd = new Date(interval.interval_beginning);
								var ed = new Date(interval.interval_ending);
								var now = new Date();
								interval.current_interval = (now >= sd && now <= ed) ? true: false;

								$scope.start_interval = 0;
								$scope.intervals[key] = interval;
							});
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
//					ngProgress.complete();
				});
	};

	var getCategories = function() {
		var deferred = $q.defer();

		if (typeof($rootScope.categories) == 'undefined') {	// load the categories
			RestData2().getCategories().$promise.then(
				function(results) {
					deferred.resolve(results);
				},
				function(err) {
					deferred.resolve(err);
				}
			);
		}

		return deferred.promise;
	};

	if (typeof($rootScope.categories) == 'undefined') {
		// first check to see if we need to load the categories
		var categoryPromise = getCategories();
		categoryPromise.then(
			function (categoryPromiseResult) {
				if (typeof($rootScope.categories) == 'undefined' && categoryPromiseResult.data.categories) {
					$rootScope.categories = [];
					angular.forEach(categoryPromiseResult.data.categories,
						function(category) {
							$rootScope.categories.push(category)
						});
				}

				// now get the budget
				loadTransactions();
			});
	} else {
		loadTransactions();
	}

//	loadTransactions();

	$scope.showTheseTransactions = function(category_id, index) {
		var idx = index + $scope.start_interval;

		$scope.dataErrorMsgThese = false;

		var date = $filter('date')($scope.intervals[idx].interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + idx + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

		RestData2().getTheseTransactions(
				{
					interval_beginning:	$scope.intervals[idx].interval_beginning,
					interval_ending:	$scope.intervals[idx].interval_ending,
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
	};

	$scope.moveInterval = function(direction)
	{
		interval = interval + direction;

		if (direction == -1)
		{
			if ($scope.start_interval > 0)
			{	// move the start pointer
				$scope.start_interval--;
			} else {
				// add an array element at the beginning
				getNext(0, direction);
			}
		}
		else if (direction == 1)
		{
			$scope.start_interval++;
			var last_interval = $scope.start_interval + 11;
			if (typeof($scope.intervals[last_interval]) == 'undefined')
			{
				getNext($scope.intervals.length - 1, direction);
			}
		}
	};

	var getNext = function(index, direction)
	{
		RestData2().getTransactions(
				{
					interval: interval
				},
				function(response)
				{
					if (!!response.success)
					{
						var moved = Array();
						// if moving backwards add interval to front of array
						if (direction == -1) {
							moved.push(response.data.result[0]);
						}
						// add the current intervals
						angular.forEach($scope.intervals,
							function(interval) {
								moved.push(interval)
							});
						// if moving forward add interval to end of array
						if (direction == 1) {
							response.data.result[0].balance_forward = moved[moved.length-1].running_total;
							response.data.result[0].running_total = response.data.result[0].balance_forward + response.data.result[0].interval_total;
							moved.push(response.data.result[0]);
						}
						$scope.intervals = moved;
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
//					ngProgress.complete();
				});
	}

}]);