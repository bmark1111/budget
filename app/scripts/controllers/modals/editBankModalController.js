'use strict';

app.controller('EditBankModalController', function ($scope, $rootScope, $localStorage, $location, $modalInstance, RestData, params)
{
	$scope.bank = {
			accounts: {}
		};

	$scope.opened1 = [];
	$scope.opened2 = [];

	$scope.title = params.title;

	if (params.id > 0)
	{
//		ngProgress.start();

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'ACCOUNTID':		$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.editBank(
				{
					id: params.id
				},
				function(response)
				{
					if (!!response.success)
					{
						if (response.data.result)
						{
							$scope.bank = response.data.result;
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
						$localStorage.account_id		= false;
						$localStorage.userId			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

	$scope.open1 = function($event, index)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened1 = [];
		$scope.opened2 = [];
		$scope.opened1[index] = true;
	};

	$scope.open2 = function($event, index)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened1 = [];
		$scope.opened2 = [];
		$scope.opened2[index] = true;
	};

	// save edited bank
	$scope.save = function ()
	{
		$scope.validation = {};

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'ACCOUNTID':		$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.saveBank($scope.bank,
				function(response)
				{
					if (!!response.success)
					{
						$modalInstance.close();
					}
					else if (response.validation)
					{
						$scope.validation.accounts = {};
						angular.forEach(response.validation,
							function(validation)
							{
								switch (validation.fieldName)
								{
									case 'name':
										$scope.validation.name = validation.errorMessage;
										break;
									case 'accounts':
										$scope.validation.accounts = validation.errorMessage;
										break;
									default:
										if (validation.fieldName.substr(0,8) == 'accounts')
										{
											var fieldName = validation.fieldName;
											var matches = fieldName.match(/\[(.*?)\]/g);
											if (matches)
											{
												for (var x = 0; x < matches.length; x++)
												{
													matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
												}
												if (typeof $scope.validation.accounts[matches[1]] == 'undefined')
												{
													$scope.validation.accounts[matches[1]] = Array();
												}
												$scope.validation.accounts[matches[1]].push(validation.errorMessage);
											} else {
												$scope.validation[fieldName] = validation.errorMessage;
											}
										}
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
						$localStorage.account_id		= false;
						$localStorage.userId			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	};

	// cancel bank edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

	// add account to Bank
	$scope.addAccount = function()
	{
		var idx = Object.size($scope.bank.accounts);

		var newItem = {
				name:		"",
				balance:	"0.00"
			}

		$scope.bank.accounts[idx] = newItem;
	};

	$scope.deleteAccount = function(ele)
	{
		$scope.bank.accounts[ele].is_deleted = 1;
	};

	Object.size = function(obj)
	{
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	};

});