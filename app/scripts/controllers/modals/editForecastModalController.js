'use strict';

app.controller('EditForecastModalController', ['$q', '$scope', '$modalInstance', 'RestData2', 'params', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $modalInstance, RestData2, params, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];
	$scope.forecast = {};
	$scope.title = params.title;
	$scope.isSaving = false;

	var getForecast = function() {
		var deferred = $q.defer();
		if (params.id > 0) {	// if we are editing a forecast - get it from the REST
			var result = RestData2().editForecast({ id: params.id},
				function(response) {
					deferred.resolve(result);
				},
				function(err) {
					deferred.resolve(err);
				});
		} else {
			deferred.resolve(true);
		}
		return deferred.promise;
	};

	$q.all([
		Accounts.get(),
		Categories.get(),
		getForecast()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
		// load the categories
		$scope.categories = Categories.data;
		// load the forecast
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.forecast = response[2].data.result;
			}
//		} else {
//			if (response[2].errors) {
//				angular.forEach(response[2].errors,
//					function(error) {
//						$scope.dataErrorMsg.push(error.error);
//					})
//			} else {
//				$scope.dataErrorMsg[2] = response;
//			}
		}
	});

	$scope.open1 = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened1 = true;
	};

	$scope.open2 = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened2 = true;
	};

	// save edited forecast
	$scope.save = function () {
		$scope.dataErrorMsg = [];
		$scope.isSaving = true;

		$scope.validation = {};

		RestData2().saveForecast($scope.forecast,
			function(response) {
				$scope.isSaving = false;
				if (!!response.success) {
					$modalInstance.close();
					// now update the global intervals data
					Periods.clear();
				} else if (response.validation) {
					angular.forEach(response.validation, function(validation) {
						switch (validation.fieldName) {
							case 'bank_account_id':
								$scope.validation.bank_account_id = validation.errorMessage;
								break;
							case 'first_due_date':
								$scope.validation.first_due_date = validation.errorMessage;
								break;
							case 'description':
								$scope.validation.description = validation.errorMessage;
								break;
							case 'category_id':
								$scope.validation.category_id = validation.errorMessage;
								break;
							case 'type':
								$scope.validation.type = validation.errorMessage;
								break;
							case 'amount':
								$scope.validation.amount = validation.errorMessage;
								break;
							case 'every':
								$scope.validation.every = validation.errorMessage;
								break;
							case 'every_unit':
								$scope.validation.every_unit = validation.errorMessage;
								break;
							default:
								break;
						}
					});
				} else {
					if (response.errors) {
						angular.forEach(response.errors, function(error) {
							$scope.dataErrorMsg.push(error.error);
						})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	};

	// cancel forecast edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

}]);