app.controller('AssignModalController', function ($scope, $modalInstance, RestData, params)
{
	$scope.transaction = {};

	$scope.title = params.title;

//	ngProgress.start();

	RestData.assignUpload(
		{
			id: params.id
		},
		function(response)
		{
			if (!!response.success)
			{
				if (response.data.result)
				{
					$scope.uploaded = response.data.result;
					$scope.transactions = response.data.transactions;
					$scope.transactions_seq = Object.keys(response.data.transactions);
				}
			} else {
				if (response.errors)
				{
					$scope.dataErrorMsg = response.errors[0].error;
				} else {
					$scope.dataErrorMsg = response;
				}
			}
//			ngProgress.complete();
		});

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

		RestData.saveTransaction($scope.transaction,
			function(response)
			{
				if (!!response.success)
				{
					$modalInstance.close();
				}
				else if (response.validation)
				{
					$scope.validation.items = {};
					angular.forEach(response.validation,
						function(validation)
						{
							switch (validation.fieldName)
							{
								case 'transaction[date]':
									$scope.validation.date = validation.errorMessage;
									break;
								case 'transaction[description]':
									$scope.validation.description = validation.errorMessage;
									break;
								case 'transaction[categories]':
									$scope.validation.categories = validation.errorMessage;
									break;
								default:
									if (validation.fieldName.substr(0,23) == 'transaction[categories]')
									{
										var fieldName = validation.fieldName;
										var matches = fieldName.match(/\[(.*?)\]/g);
										if (matches)
										{
											for (var x = 0; x < matches.length; x++)
											{
												matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
											}
											if (typeof $scope.validation.items[matches[2]] == 'undefined')
											{
												$scope.validation.items[matches[2]] = Array();
											}
											$scope.validation.items[matches[2]].push(validation.errorMessage);
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
//				ngProgress.complete();
			});
	};

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

	// add new item to transaction
	$scope.addItem = function()
	{
		var newItem = {
			amount:			'',
			category_id:	'',
			check_num:		'',
			notes:			'',
			type:			''
		}
		if ($scope.transaction.categories)
		{
			var yy = Object.keys($scope.transaction.categories).length
			$scope.transaction.categories[yy] = newItem;
		} else {
			$scope.transaction.categories = {};
			$scope.transaction.categories[0] = newItem;
		}
	}

	$scope.deleteItem = function(ele)
	{
		$scope.transaction.categories[ele].is_deleted = 1;
	}

});