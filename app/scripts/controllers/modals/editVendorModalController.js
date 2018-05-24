'use strict';

app.controller('EditVendorModalController', ['$scope', '$modalInstance', 'RestData2', 'params',

	function($scope, $modalInstance, RestData2, params) {

		$scope.dataErrorMsg = [];
		$scope.isSaving = false;

		$scope.vendor = {
			name: params.name
		}

		if (params.id > 0) {
			$scope.dataErrorMsg = [];

	//		ngProgress.start();

			RestData2().editVendor({
					id: params.id
				},
				function(response) {
					if (!!response.success) {
						if (response.data.result) {
							$scope.vendor = response.data.result;
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
	//				ngProgress.complete();
				});
		}

		// save vendor
		$scope.save = function () {
			$scope.dataErrorMsg = [];
			$scope.isSaving = true;

			$scope.validation = {};
			RestData2().saveVendor($scope.vendor,
					function(response) {
console.log('MODAL response',response)
						$scope.isSaving = false;
						if (!!response.success) {
							$modalInstance.close(response);
						} else if (response.validation) {
							angular.forEach(response.validation,
								function(validation) {
									switch (validation.fieldName) {
										case 'name':
											$scope.validation.name = validation.errorMessage;
											break;
										case 'description':
											$scope.validation.description = validation.errorMessage;
											break;
										case 'street':
											$scope.validation.street = validation.errorMessage;
											break;
										case 'city':
											$scope.validation.city = validation.errorMessage;
											break;
										case 'state':
											$scope.validation.state = validation.errorMessage;
											break;
										case 'phone_area_code':
											$scope.validation.phone_area_code = validation.errorMessage;
											break;
										case 'phone_prefix':
											$scope.validation.phone_prefix = validation.errorMessage;
											break;
										case 'phone_number':
											$scope.validation.phone_number = validation.errorMessage;
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

		// cancel transaction edit
		$scope.cancel = function () {
			$modalInstance.dismiss('cancel');
		};

	}]);