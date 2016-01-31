'use strict';

app.controller('ReconcileTransactionsModalController', function ($scope, $rootScope, $modalInstance, $filter, RestData2, params) {

	$scope.dataErrorMsg	= [];

	$scope.account_name	= params.account.name;
	$scope.balance		= params.period.accounts[params.index].balance;
	$scope.date			= $filter('date')(params.date, "EEE MMM dd, yyyy");

	$scope.ok = function () {
//		ngProgress.start();

		RestData2().reconcileTransactions({
				account_id:	params.account.bank_account_id,
				date:		$filter('date')(params.date, "yyyy-MM-dd")
			},
			function(response) {
				if (!!response.success) {
					$modalInstance.close();
					// now update the global intervals data
					params.period.accounts[params.index].reconciled_date = $filter('date')(params.date, "yyyy-MM-dd");
					params.period.accounts[params.index].reconciled = 2;
//					delete $rootScope.intervals;
//					delete $rootScope.periods;
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//					ngProgress.complete();
			});
	};

	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
});