'use strict';

app.controller('BudgetController', ['$q', '$scope', '$rootScope', 'RestData2', '$filter', 'Categories', function($q, $scope, $rootScope, RestData2, $filter, Categories) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var loadIntervals = function() {
		var deferred = $q.defer();
		if (typeof($rootScope.intervals) === 'undefined') {
			var result = RestData2().getTransactions({ interval: 0 },
				function(response) {
					deferred.resolve(result);
				},
				function(err) {
					deferred.resolve(err);
				});
		} else {
			deferred.resolve(true);
		}
		return deferred.promise;
	};

	$q.all([
		Categories.get(),
		loadIntervals()
	]).then(function(response) {
		// load the categories
		if (!!response[0].success) {
			$rootScope.categories = [];
			angular.forEach(response[0].data.categories,
				function(category) {
					$rootScope.categories.push(category)
				});
		}
		// load the intervals
		if (!!response[1].success) {
			// set current interval
			$rootScope.intervals = [];
			$rootScope.start_interval = 0;
			angular.forEach(response[1].data.result,
				function(interval, key) {
					var sd = new Date(interval.interval_beginning);
					var ed = new Date(interval.interval_ending);
					var now = new Date();
					interval.current_interval = (now >= sd && now <= ed) ? true: false;		// mark the current interval

					$rootScope.intervals[key] = interval;
				});
		}
	});

	$scope.showTheseTransactions = function(category_id, index) {
		var idx = index + $rootScope.start_interval;

		$scope.dataErrorMsgThese = false;

		var date = $filter('date')($rootScope.intervals[idx].interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + idx + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

		RestData2().getTheseTransactions({
				interval_beginning:	$rootScope.intervals[idx].interval_beginning,
				interval_ending:	$rootScope.intervals[idx].interval_ending,
				category_id:		category_id
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
				} else {
					$scope.dataErrorMsgThese = response.errors;
				}
			});
	};

	$scope.moveInterval = function(direction) {
		interval = interval + direction;

		if (direction == -1) {
			if ($rootScope.start_interval > 0) {
				// move the start pointer
				$rootScope.start_interval--;
			} else {
				// add an array element at the beginning
				getNext(0, direction);
			}
		} else if (direction == 1) {
			$rootScope.start_interval++;
			var last_interval = $rootScope.start_interval + 11;
			if (typeof($rootScope.intervals[last_interval]) == 'undefined') {
				getNext($rootScope.intervals.length - 1, direction);
			}
		}
	};

	var getNext = function(index, direction) {
		RestData2().getTransactions(
				{
					interval: interval
				},
				function(response) {
					if (!!response.success) {
						var moved = Array();
						// if moving backwards add interval to front of array
						if (direction == -1) {
							moved.push(response.data.result[0]);
						}
						// add the current intervals
						angular.forEach($rootScope.intervals,
							function(interval) {
								moved.push(interval)
							});
						// if moving forward add interval to end of array
						if (direction == 1) {
							response.data.result[0].balance_forward = moved[moved.length-1].running_total;
							response.data.result[0].running_total = response.data.result[0].balance_forward + response.data.result[0].interval_total;
							moved.push(response.data.result[0]);
						}
						$rootScope.intervals = moved;
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