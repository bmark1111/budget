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
				return $filter('date')(dt[0], "MMM yyyy");
		}
	};
});