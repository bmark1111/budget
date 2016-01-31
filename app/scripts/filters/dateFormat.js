app.filter('displayDate', function ($localStorage, $filter) {

	return function (input) {
		switch ($localStorage.budget_mode) {
			case 'weekly':
				return $filter('date')(input, "EEE MMM dd, yyyy");
			case 'bi-weekly':
				return $filter('date')(input, "EEE MMM dd, yyyy");
			case 'semi-monthly':
				return $filter('date')(input, "EEE MMM dd, yyyy");
			case 'monthly':
				return $filter('date')(input, "MMM yyyy");
		}
	};
});