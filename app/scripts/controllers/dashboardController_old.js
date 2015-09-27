'use strict';

app.controller('DashboardController', ['$q', '$scope', '$rootScope', 'RestData2', function($q, $scope, $rootScope, RestData2) {

	var getYTDTotals = function()
	{
		RestData2().getYTDTotals(
			function(response) {
				if (!!response.success) {
					$scope.ytdYear = response.data.year;
					// set current interval
					angular.forEach($rootScope.categories,
						function(category, key) {
							var category = {
								id:		category.id,
								name:	category.name,
								total:	response.data.result['total_' + category.id]
							};
							$scope.ytdTotals.push(category);
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
			});
	};

	var getCategories = function() {
		var deferred = $q.defer();

		RestData2().getCategories().$promise.then(
			function(results) {
				deferred.resolve(results);
			},
			function(err) {
				deferred.resolve(err);
			}
		);
		return deferred.promise;
	};

//	var interval = 0;
//	var loadIntervals = function() {
//		$scope.dataErrorMsg = [];
//
//		$rootScope.start_interval = 0;
//		$rootScope.intervals = [];
//
//		RestData2().getTransactions(
//				{
//					interval: interval
//				},
//				function(response)
//				{
//					if (!!response.success) {
//						// set current interval
//						angular.forEach(response.data.result,
//							function(interval, key) {
//								var sd = new Date(interval.interval_beginning);
//								var ed = new Date(interval.interval_ending);
//								var now = new Date();
//								interval.current_interval = (now >= sd && now <= ed) ? true: false;
//
//								$rootScope.start_interval = 0;
//								$rootScope.intervals[key] = interval;
//							});
//					} else {
//						if (response.errors) {
//							angular.forEach(response.errors,
//								function(error) {
//									$scope.dataErrorMsg.push(error.error);
//								})
//						} else {
//							$scope.dataErrorMsg[0] = response;
//						}
//					}
//				});
//	};

	$scope.dataErrorMsg = [];
	$scope.ytdTotals = [];

	if (typeof($rootScope.categories) === 'undefined') {
		// first check to see if we need to load the categories
		var categoryPromise = getCategories();
		categoryPromise.then(
			function (categoryPromiseResult) {
//				if (typeof($rootScope.categories) === 'undefined' && categoryPromiseResult.data.categories) {
				if (categoryPromiseResult.data.categories) {
					$rootScope.categories = [];
					angular.forEach(categoryPromiseResult.data.categories,
						function(category) {
							$rootScope.categories.push(category)
						});
				}

				// now get the YTD totals
				getYTDTotals();
			});
	} else {
		getYTDTotals();
	}

	$scope.getYTDTransactions = function(category_id, year) {
		$scope.dataErrorMsgThese = false;

		RestData2().getYTDTransactions( {
					year:			year,
					category_id:	category_id
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

//	if (typeof($rootScope.intervals) == 'undefined') {
//		loadIntervals();
//	}

}]);