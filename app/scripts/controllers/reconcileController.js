'use strict';

app.controller('ReconcileController', ['$q', '$scope', '$rootScope', 'RestData2', '$filter', 'Categories', function($q, $scope, $rootScope, RestData2, $filter, Categories) {

	$scope.dataErrorMsg = [];
	$scope.recIntervals = [];

	var interval = 0;
	var loadIntervals = function() {
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
					interval.current_interval = (+now >= +sd && +now <= +ed) ? true: false;		// mark the current interval

					$rootScope.intervals[key] = interval;
				});
		}
		angular.forEach($scope.intervals,
			function(interval, key) {
				var ed = new Date(interval.interval_ending);
				var now = new Date();
				if (+now > +ed) {
					var dt = interval.reconciled_date.split('-');
//console.log('==============')
//console.log(dt);
					var xx = new Date(dt[0], --dt[1], dt[2], 0, 0, 0, 0);
//console.log(xx);
//console.log(xx.getTime());
					var dt = interval.interval_ending.split('T');
					var dt = dt[0].split('-');
//console.log(dt);
					var yy = new Date(dt[0], --dt[1], dt[2], 0, 0, 0, 0);
//console.log(yy);
//console.log(yy.getTime());
					$scope.recIntervals[key] = interval;
					$scope.recIntervals[key].reconciled = yy.getTime() - xx.getTime();
				}
			});
//console.log($scope.recIntervals)
	});

}]);