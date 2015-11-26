app.filter('getIntervals', function ($localStorage) {

	return function (intervals, start_interval, colspan) {
		var return_intervals = Array();
		angular.forEach(intervals,
			function(interval, index) {
				if (index >= start_interval && return_intervals.length < ($localStorage.budget_views * 2)) {
					if (typeof colspan === 'undefined' || (index&colspan) !== 0) {
						return_intervals.push(interval);
					}
				}
			})
		return return_intervals;
	};
});