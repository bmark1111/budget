'use strict';

app.controller('EditCategoryModalController', function ($scope, $modalInstance, RestData2, params)
{
	$scope.dataErrorMsg = [];

	$scope.category = {};

	$scope.opened1 = [];
	$scope.opened2 = [];

	$scope.title = params.title;

	if (params.id > 0)
	{
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().editCategory(
				{
					id: params.id
				},
				function(response)
				{
					if (!!response.success)
					{
						if (response.data.result)
						{
							$scope.category = response.data.result;
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

	$scope.open = function($event, index)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	// save edited Category
	$scope.save = function ()
	{
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		RestData2().saveCategory($scope.category,
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
									case 'order':
										$scope.validation.order = validation.errorMessage;
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

	// cancel Category edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});