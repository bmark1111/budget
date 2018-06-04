'use strict'

app.filter('getBalanceForward', function () {

	return function (periods, start) {

		if (typeof periods !== 'undefined' && typeof start !== 'undefined') {
			return periods[start].balance_forward;
		}
	};
});