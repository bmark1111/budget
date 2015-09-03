'use strict';

app.controller('EditBankModalController', function ($scope, $rootScope, $modalInstance, RestData2, params)
{
	$scope.dataErrorMsg = [];

	$scope.bank = {
			accounts: {}
		};

	$scope.opened1 = [];
	$scope.opened2 = [];

	$scope.title = params.title;

	if (params.id > 0)
	{
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().editBank(
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
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
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
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		RestData2().saveBank($scope.bank,
				function(response)
				{
					if (!!response.success)
					{
						$modalInstance.close();

						// now update the global bank account data
						$rootScope.bank_accounts = [];
						RestData2().getBankAccounts(
								function(response)
								{
									angular.forEach(response.data.bank_accounts,
										function(bank_account)
										{
											$rootScope.bank_accounts.push({
												'id': bank_account.id,
												'name': bank_account.bank.name + ' ' + bank_account.name
											})
										});
								});
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
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
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

		$scope.bank.accounts[idx] = {
				bank_id:	$scope.bank.id,
				name:		"",
				balance:	"0.00"
			};
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