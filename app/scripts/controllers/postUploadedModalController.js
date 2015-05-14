app.controller('PostUploadedModalController', function ($scope, $rootScope, $modalInstance, RestData, params)
{
	$scope.uploaded = {
			splits: {}
		};
	$scope.categories = [];
	$scope.title = params.title;
	$scope.post = 'Post New';

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
					$scope.uploaded = response.data.result;
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

	// post uploaded uploaded
	$scope.postUploaded = function ()
	{
		$scope.validation = {};

		$scope.uploaded.transaction_id = $scope.idSelectedTransaction;
console.log($scope.uploaded)
		RestData.postUploadedTransaction($scope.uploaded,
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
								case 'category_id':
									$scope.validation.category_id = validation.errorMessage;
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
//				ngProgress.complete();
			});
	};

	$scope.deleteUploaded = function()
	{
//		ngProgress.start();

		RestData.deleteUploadedTransaction(
			{
				'id': params.id
			},
			function(response)
			{
				if (!!response.success)
				{
					$modalInstance.close();
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

	// cancel uploaded transactionedit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

	$scope.idSelectedTransaction = null;
	$scope.setSelected = function (idSelectedTransaction)
	{
		if ($scope.idSelectedTransaction !== idSelectedTransaction)
		{
			$scope.idSelectedTransaction = idSelectedTransaction;
			$scope.post = 'Post New & Overwrite';
		} else {
			$scope.idSelectedTransaction = null;
			$scope.post = 'Post New';
		}
	};

});