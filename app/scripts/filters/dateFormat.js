app.filter('displayDate', function ($localStorage, $filter) {

	return function (input) {
		var dt = input.split('T');
		switch ($localStorage.budget_mode) {
			case 'weekly':
				return $filter('date')(dt[0], "EEE MMM dd, yyyy");
			case 'bi-weekly':
				return $filter('date')(dt[0], "EEE MMM dd, yyyy");
			case 'semi-monthly':
				return $filter('date')(dt[0], "EEE MMM dd, yyyy");
			case 'monthly':
				var dt2 = dt[0].split('-');
				var dt3 = new Date(dt2[0], dt2[1] - 1, 0);
				return $filter('date')(dt3, "EEE MMM dd yyyy");
//				return $filter('date')(dt[0], "MMM yyyy");
		}
	};
});