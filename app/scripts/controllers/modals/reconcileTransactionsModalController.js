'use strict';

app.controller('ReconcileTransactionsModalController',
	function ($scope, $modalInstance, $filter, RestData2, params) {

		$scope.dataErrorMsg	= [];
		$scope.isSaving = false;

		$scope.account_name	= params.account.name;
		$scope.balance		= params.period.accounts[params.index].balance;
		$scope.date			= $filter('date')(params.date, "EEE MMM dd, yyyy");

		$scope.ok = function () {
			$scope.isSaving = true;

	//		ngProgress.start();
			RestData2().reconcileTransactions({
					account_id:	params.account.id,
					date:		$filter('date')(params.date, "yyyy-MM-dd")
				},
				function(response) {
					$scope.isSaving = false;
					if (!!response.success) {
						$modalInstance.close();
						// now update the global intervals data
						params.period.accounts[params.index].reconciled_date = $filter('date')(params.date, "yyyy-MM-dd");
						params.period.accounts[params.index].reconciled = 2;
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