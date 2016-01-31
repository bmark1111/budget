'use strict'

app.filter('getIntervals', function ($location, $localStorage) {

	return function (periods, start, colspan) {
		var return_intervals = Array();
		var views;
		var path = $location.path();
		if (path.indexOf('budget') > 0) {
			views = (typeof colspan === 'undefined') ? $localStorage.budget_views: parseInt($localStorage.budget_views/2);
		} else {
			views = $localStorage.sheet_views;
		}
		angular.forEach(periods,
			function(interval, index) {
				if (index >= start && return_intervals.length < views) {
					if (typeof colspan === 'undefined' || (index&colspan) !== 0) {
						return_intervals.push(interval);
					}
				}
			});
		return return_intervals;
	};
});