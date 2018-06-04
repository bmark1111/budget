'use strict'

app.filter('getIntervals', function ($localStorage) {

	return function (periods, start) {
		var return_intervals = Array();
		var views;
		views = $localStorage.sheet_views;
		angular.forEach(periods,
			function(interval, index) {
				if (index >= start && return_intervals.length < views) {
					return_intervals.push(interval);
				}
			});
		return return_intervals;
	};
});