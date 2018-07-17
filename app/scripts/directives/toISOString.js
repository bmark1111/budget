'use strict'

app.filter('toISOString', function () {

	return function (input) {
		if (typeof input !== 'undefined' && input.substring(0,10) !== '0000-00-00') {
			var dt = input.split(' ');
			var dt1 = dt[0].split('-');
			var d;
			if (dt[1]) {
				var dt2 = dt[1].split(':');
				d = new Date(dt1[0], --dt1[1], dt1[2], dt2[0], dt2[1], dt2[2]);
			} else {
				d = new Date(dt1[0], --dt1[1], dt1[2]);
			}
			return d.toISOString();
		}
		return null;
	};
});