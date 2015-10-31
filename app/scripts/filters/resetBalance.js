app.filter('resetBalance', function ($rootScope, $filter) {

	return function (balance, interval_beginning, interval_ending) {
		if (typeof($rootScope.accountBalancesResetDate) !== 'undefined') {
			var d1 = new Date($rootScope.accountBalancesResetDate);
			var dat = interval_beginning.split('T');
			var d2 = new Date(dat[0]);
			var dat = interval_ending.split('T');
			var d3 = new Date(dat[0]);
//console.log('=========================')
//console.log(d1)
//console.log('----------')
//console.log(d2)
//console.log('----------')
//console.log(d3)
			if(+d1 <= +d2 || +d1 <= +d3) {
				return '****';
			}
		}
//		return balance;
		return $filter('currency')(balance, '$', 2);
	};
});