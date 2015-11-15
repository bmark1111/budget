app.filter('getIntervals', function ($localStorage) {

	return function (intervals, start_interval) {
		var return_intervals = Array();
		angular.forEach(intervals,
			function(interval, index) {
				if (index >= start_interval && return_intervals.length < $localStorage.budget_views) {
					return_intervals.push(interval);
				}
			})
		return return_intervals;
	};
});