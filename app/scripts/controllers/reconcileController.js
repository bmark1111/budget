'use strict';

app.controller('ReconcileController', ['$q', '$scope', '$rootScope', '$modal', 'RestData2', 'Categories', function($q, $scope, $rootScope, $modal, RestData2, Categories) {

	$scope.dataErrorMsg = [];
	$scope.recIntervals = [];

	var interval = 0;

	var buildPeriods = function(response) {
		$rootScope.intervals = [];
		$rootScope.start_interval = 0;
		angular.forEach(response.data.result,
			function(interval, key) {
				var sd = new Date(new Date(interval.interval_beginning).setHours(0,0,0,0));
				var ed = new Date(new Date(interval.interval_ending).setHours(0,0,0,0));
				var now = new Date(new Date().setHours(0,0,0,0));
				if (+now >= +sd && +now <= +ed) {
					interval.alt_ending = now;				// set alternative ending
					interval.current_interval = true;		// mark the current interval
				}

				angular.forEach(interval.accounts,
					function(account) {
						if (account.reconciled_date) {
							var dt = account.reconciled_date.split('-');
							var rd = new Date(dt[0], --dt[1], dt[2]);
							var now = new Date(new Date().setHours(0,0,0,0));
							if (+rd === +ed || +rd === +now) {
								// if everything has been reconciled up to the period ending date
								account.reconciled = 1;
							}
						}
					})

				$rootScope.intervals[key] = interval;
			});
	};

	var loadPeriods = function() {
		var deferred = $q.defer();
		if (typeof($rootScope.intervals) === 'undefined') {
			var result = RestData2().getTransactions({ interval: interval },
				function() {
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
		loadPeriods()
	]).then(function(response) {
		// load the categories
		if (!!response[0].success) {
			$rootScope.categories = [];
			angular.forEach(response[0].data.categories,
				function(category) {
					$rootScope.categories.push(category)
				});
		}
		// build the periods
		if (!!response[1].success) {
			buildPeriods(response[1]);
		}
		$rootScope.recIntervals = [];
		angular.forEach($scope.intervals,
			function(interval, key) {
				if (interval.forecast != 1) {
					$scope.recIntervals.push(interval);
				}
			});
	});

	$scope.reconcile = function(account_name, account_id, date, alt_date) {
		var use_date = (alt_date) ? alt_date: date;
		var modalInstance = $modal.open({
			templateUrl: 'reconcileTransactionsModal.html',
			controller: 'ReconcileTransactionsModalController',
			size: 'md',
			resolve: {
				params: function() {
						return {
							account_name:	account_name,
							account_id:		account_id,
							date:			use_date
						}
					}
			}
		});

		modalInstance.result.then(function () {
			$q.all([
				loadPeriods()
			]).then(function(response) {
				// build the periods
				if (!!response[0].success) {
					buildPeriods(response[0]);
				}
				$scope.recIntervals = [];
				angular.forEach($scope.intervals,
					function(interval, key) {
						if (interval.forecast != 1) {
							$scope.recIntervals.push(interval);
						}
					});
			});
		},
		function () {
			console.log('Reconcile Modal dismissed at: ' + new Date());
		});
	};

}]);