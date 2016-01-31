app.service('BankAccounts',  [ "$q", "RestData2", '$rootScope',
	function ($q, RestData2, $rootScope) {
		this.get = function () {
			var deferred = $q.defer();
			if (typeof($rootScope.bank_accounts) === 'undefined') {
				RestData2().getBankAccounts(
					function (response) {
						console.log("bank accounts got");
						deferred.resolve(response);
					},
					function (error) {
						console.log("failed to get bank accounts");
						deferred.reject(error);
					});
			} else {
				console.log("already loaded bank accounts");
				deferred.resolve(true);
			}
			return deferred.promise;
		};
	}]);