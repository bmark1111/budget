'use strict';

app.controller('EditForecastModalController', function ($scope, $rootScope, $localStorage, $location, $modalInstance, RestData, params)
{
	$scope.forecast = {};
	$scope.title = params.title;

	if (params.id > 0)
	{
//		ngProgress.start();

		RestData(
			{
				Authorization:		$localStorage.authorization,
//				Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.editForecast(
				{
					id: params.id
				},
				function(response)
				{
					if (!!response.success)
					{
						if (response.data.result)
						{
							$scope.forecast = response.data.result;
						}
					} else {
						if (response.errors)
						{
							$scope.dataErrorMsg = response.errors[0].error;
						} else {
							$scope.dataErrorMsg = response;
						}
					}
//					ngProgress.complete();
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
//						$localStorage.username			= false;
//						$localStorage.password			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

	$scope.open1 = function($event)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened1 = true;
	};

	$scope.open2 = function($event)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened2 = true;
	};

	// save edited forecast
	$scope.save = function ()
	{
		$scope.validation = {};

		RestData(
			{
				Authorization:		$localStorage.authorization,
//				Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.saveForecast($scope.forecast,
				function(response)
				{
					if (!!response.success)
					{
						$modalInstance.close();
					}
					else if (response.validation)
					{
						angular.forEach(response.validation,
							function(validation)
							{
								switch (validation.fieldName)
								{
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
						if (response.errors)
						{
							$scope.dataErrorMsg = response.errors[0].error;
						} else {
							$scope.dataErrorMsg = response;
						}
					}
//					ngProgress.complete();
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
//						$localStorage.username			= false;
//						$localStorage.password			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	};

	// cancel forecast edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});