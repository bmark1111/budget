'use strict';

app.controller('EditModalController', function ($scope, $modalInstance, RestData2, params)
{
	$scope.transaction = {
			splits: {}
		};

	$scope.title = params.title;
	
	$scope.dataErrorMsg = [];

	if (params.id > 0)
	{
		$scope.dataErrorMsg = [];

		RestData2().editTransaction(
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
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
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
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		RestData2().saveTransaction($scope.transaction,
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

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

	// split transaction
	$scope.split = function()
	{
		if (Object.size($scope.transaction.splits) == 0)
		{
			var newItem = {
				amount:			'',
				type:			'',
				category_id:	'',
				notes:			''
			}
			newItem.amount = $scope.transaction.amount;
			$scope.transaction.splits = {};
			$scope.transaction.splits[0] = newItem;
		}
	};

	$scope.refreshSplits = function()
	{
		var newItem = {
			amount:			'',
			type:			'',
			category_id:	'',
			notes:			''
		}

		if (Object.size($scope.transaction.splits) > 0)
		{
			// calculate total of all splits
			var total = parseFloat(0);
			angular.forEach($scope.transaction.splits,
				function(split)
				{
					if (split.is_deleted != 1)
					{
						switch (split.type)
						{
							case 'DEBIT':
							case 'CHECK':
								total -= parseFloat(split.amount);
								break;
							case 'CREDIT':
							case 'DSLIP':
								total += parseFloat(split.amount);
								break;
						}
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
console.log('deleteSplit');
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

	Object.size = function(obj)
	{
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	};

});