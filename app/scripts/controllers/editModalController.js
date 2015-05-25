'use strict';

app.controller('EditModalController', function ($scope, $rootScope, $localStorage, $location, $modalInstance, RestData, params)
{
	$scope.transaction = {
			splits: {}
		};

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
			.editTransaction(
				{
					id: params.id
				},
				function(response)
				{
					if (!!response.success)
					{
						if (response.data.result)
						{
							$scope.transaction = response.data.result;
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

	$scope.open = function($event)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	// save edited transaction
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
			.saveTransaction($scope.transaction,
				function(response)
				{
					if (!!response.success)
					{
						$modalInstance.close();
					}
					else if (response.validation)
					{
						$scope.validation.splits = {};
						angular.forEach(response.validation,
							function(validation)
							{
								switch (validation.fieldName)
								{
									case 'transaction_date':
										$scope.validation.date = validation.errorMessage;
										break;
									case 'description':
										$scope.validation.description = validation.errorMessage;
										break;
									case 'type':
										$scope.validation.type = validation.errorMessage;
										break;
									case 'splits':
										$scope.validation.splits = validation.errorMessage;
										break;
									default:
										if (validation.fieldName.substr(0,6) == 'splits')
										{
											var fieldName = validation.fieldName;
											var matches = fieldName.match(/\[(.*?)\]/g);
											if (matches)
											{
												for (var x = 0; x < matches.length; x++)
												{
													matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
												}
												if (typeof $scope.validation.splits[matches[1]] == 'undefined')
												{
													$scope.validation.splits[matches[1]] = Array();
												}
												$scope.validation.splits[matches[1]].push(validation.errorMessage);
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

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

	// split transaction
	$scope.split = function()
	{
		var newItem = {
			amount:			'',
			category_id:	'',
			notes:			''
		}
		if ($scope.transaction.splits)
		{
//			// calculate total of all splits
//			var total = parseFloat(0);
//			angular.forEach($scope.transaction.splits,
//				function(split)
//				{
//					if (split.is_deleted != 1)
//					{
//						total += parseFloat(split.amount);
//					}
//				});
//			newItem.amount = $scope.transaction.amount - total;
//			var yy = Object.keys($scope.transaction.splits).length
//			$scope.transaction.splits[yy] = newItem;
		} else {
			newItem.amount = $scope.transaction.amount;
			$scope.transaction.splits = {};
			$scope.transaction.splits[0] = newItem;
		}
	};

	$scope.refreshSplits = function()
	{
		var newItem = {
			amount:			'',
			category_id:	'',
			notes:			''
		}
		if ($scope.transaction.splits)
		{
			// calculate total of all splits
			var total = parseFloat(0);
			angular.forEach($scope.transaction.splits,
				function(split)
				{
					if (split.is_deleted != 1)
					{
						total += parseFloat(split.amount);
					}
				});
			$scope.calc = Array();
			var yy = Object.keys($scope.transaction.splits).length
			if ($scope.transaction.amount > total)
			{
				newItem.amount = $scope.transaction.amount - total;
				$scope.transaction.splits[yy] = newItem;
			}
			else if ($scope.transaction.amount < total)
			{
				$scope.calc[yy-1] = 'Split amounts do not match Item amount';
			}
		}
	};

	$scope.deleteSplit = function(ele)
	{
		$scope.transaction.splits[ele].is_deleted = 1;

		// calculate total of all splits
		var total = parseFloat(0);
		angular.forEach($scope.transaction.splits,
			function(split)
			{
				if (split.is_deleted != 1)
				{
					total += parseFloat(split.amount);
				}
			});
		$scope.calc = Array();
		if ($scope.transaction.amount != total)
		{
			$scope.calc[ele-1] = 'Split amounts do not match Item amount';
		}
	};

});