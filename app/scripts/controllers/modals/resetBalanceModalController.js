'use strict';

app.controller('ResetBalanceModalController', ResetBalanceModalController);

function ResetBalanceModalController($scope, $rootScope, $modalInstance, RestData2, params) {
console.log('ResetBalanceModalController');
	this.dataErrorMsg	= [];
	this.title = params.title;
	this.message = params.message;
$rootScope.accountBalancesResetDate = '2015-10-30';
	this.ok = function () {
//		ngProgress.start();
		RestData2().resetAccountBalances({
				accountBalancesResetDate: $rootScope.accountBalancesResetDate
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
								this.dataErrorMsg.push(error.error);
							})
					} else {
						this.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	};

	this.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
};