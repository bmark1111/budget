'use strict';

app.controller('ReconcileTransactionsModalController', function ($scope, $rootScope, $modalInstance, $filter, RestData2, params)
{
	$scope.dataErrorMsg	= [];

	$scope.account_name	= params.account_name;
	$scope.account_id	= params.account_id;
	$scope.date			= $filter('date')(params.date, "EEE MMM dd, yyyy");

	$scope.ok = function () {
//		ngProgress.start();

		RestData2().reconcileTransactions({
				account_id:	$scope.account_id,
				date:		params.date
			},
			function(response) {
				if (!!response.success) {
					$modalInstance.close();
					// now update the global intervals data
					delete $rootScope.intervals;
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