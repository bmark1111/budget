app.controller('PostUploadedModalController', function ($scope, $rootScope, $modalInstance, RestData, params)
{
	$scope.transaction = {
			splits: {}
		};
	$scope.categories = [];
	$scope.title = params.title;

//	ngProgress.start();

	RestData.getUploadedTransaction(
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
					$scope.transactions = response.data.transactions;
					$scope.transactions_seq = Object.keys(response.data.transactions);
				}

				angular.forEach($rootScope.categories,
					function(category)
					{
						$scope.categories.push(category)
					});
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

	// post uploaded transaction
	$scope.post = function ()
	{
		$scope.validation = {};

		RestData.postUploadedTransaction($scope.transaction,
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
//				ngProgress.complete();
			});
	};

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});