'use strict';

app.controller('EditUserModalController', editUserModalController);

function editUserModalController($q, $scope, $filter, $modalInstance, RestData2, params) {

	$scope.dataErrorMsg = [];
	$scope.user = {
			splits: {}
		};
	$scope.title = params.title;

	var getUser = function() {
		var deferred = $q.defer();
		if (params.id > 0) {	// if we are editing a user - get it from the REST
			var result = RestData2().editUser({ id: params.id},
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
		getUser()
	]).then(function(response) {
		// load the user
		if (!!response[0].success) {
			if (response[0].data.result) {
				$scope.user = response[0].data.result;
				$scope.user.joindate = $filter('toISOString')($scope.user.joindate);
				$scope.user.joindate = $filter('date')($scope.user.joindate, 'EEE MMM dd, yyyy');
				$scope.user.active = ($scope.user.active === "1") ? true: false;
console.log(response[0].data.sessions)
			}
//		} else {
//			if (response[2].errors) {
//				angular.forEach(response[2].errors,
//					function(error) {
//						$scope.dataErrorMsg.push(error.error);
//					})
//			} else {
//				$scope.dataErrorMsg[0] = response[2];
//			}
		}
	});

	// save edited user
	$scope.save = function () {
		$scope.dataErrorMsg = [];

		$scope.validation = {};
		$scope.user.active = ($scope.user.active) ? 1: 0;
		RestData2().saveUser($scope.user,
				function(response) {
					if (!!response.success) {
						$modalInstance.close();
					} else if (response.validation) {
						$scope.validation.splits = {};
						angular.forEach(response.validation,
							function(validation) {
								switch (validation.fieldName) {
									case 'firstname':
										$scope.validation.firstname = validation.errorMessage;
										break;
									case 'lastname':
										$scope.validation.lastname = validation.errorMessage;
										break;
									case 'email':
										$scope.validation.email = validation.errorMessage;
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

	// cancel user edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

};