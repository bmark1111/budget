'use strict';

/**
 * @module RegisterController
 * @param {type} param1
 */
app.controller('RegisterController', function($scope, RestData2) {

	/**
	 * @name dataErrorMsg
	 * @type Array
	 */
	$scope.dataErrorMsg = [];

	/**
	 * @name data
	 * @type object
	 */
	$scope.data = {
		firstname:	'',
		lastname:	'',
		email:		''
	};

	/**
	 * @name register
	 * @type function
	 * @returns {undefined}
	 */
	$scope.register = function() {
		$scope.dataErrorMsg = [];

		$scope.validation = {
			firstname:	'',
			lastname:	'',
			email:		''
		};

		RestData2().register($scope.data,
			function(response) {
				if (!!response.success) {
					// success
				} else if (response.validation) {
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
//				ngProgress.complete();
			});
	};

});