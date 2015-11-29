app.filter('getIntervals', function ($localStorage) {

	return function (intervals, start_interval, colspan) {
		var return_intervals = Array();
		var budget_views = (typeof colspan === 'undefined') ? $localStorage.budget_views: parseInt($localStorage.budget_views/2);
		angular.forEach(intervals,
			function(interval, index) {
				if (index >= start_interval && return_intervals.length <= budget_views) {
					if (typeof colspan === 'undefined' || (index&colspan) !== 0) {
						return_intervals.push(interval);
					}
				}
			})
		return return_intervals;
	};
});