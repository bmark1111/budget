'use strict';

app.controller('EditForecastModalController', function ($scope, $rootScope, $modalInstance, RestData2, params)
{
	$scope.dataErrorMsg = [];
	$scope.forecast = {};
	$scope.title = params.title;

	if (params.id > 0) {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().editForecast(
				{
					id: params.id
				},
				function(response) {
					if (!!response.success) {
						if (response.data.result) {
							$scope.forecast = response.data.result;
						}
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
	}

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

		$scope.validation = {};

		RestData2().saveForecast($scope.forecast,
				function(response) {
					if (!!response.success) {
						$modalInstance.close();
						// now update the global intervals data
						delete $rootScope.intervals;
					} else if (response.validation) {
						angular.forEach(response.validation,
							function(validation) {
								switch (validation.fieldName) {
									case 'first_due_date':
										$scope.validation.first_due_date = validation.errorMessage;
										break;
									case 'description':
										$scope.validation.description = validation.errorMessage;
										break;
									case 'type':
										$scope.validation.type = validation.errorMessage;
										break;
									case 'amount':
										$scope.validation.amount = validation.errorMessage;
										break;
									default:
										break;
								}
							});
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

	// cancel forecast edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

});