'use strict';

app.controller('PostUploadedModalController', function ($scope, $rootScope, $localStorage, $location, $modalInstance, RestData, params)
{
	$scope.uploaded = {
			splits: {}
		};
	$scope.categories = [];
	$scope.title = params.title;
	$scope.post = 'Post New';

//	ngProgress.start();

	RestData(
		{
			Authorization:		$localStorage.authorization,
			'TOKENID':			$localStorage.token_id,
			'ACCOUNTID':		$localStorage.account_id,
			'X-Requested-With':	'XMLHttpRequest'
		})
		.getUploadedTransaction(
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
					$scope.categories = $rootScope.categories;
				} else {
					if (response.errors)
					{
						$scope.dataErrorMsg = response.errors[0].error;
					} else {
						$scope.dataErrorMsg = response;
					}
				}
//				ngProgress.complete();
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

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'ACCOUNTID':		$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.postUploadedTransaction($scope.uploaded,
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

	$scope.deleteUploaded = function()
	{
//		ngProgress.start();

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'ACCOUNTID':		$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.deleteUploadedTransaction(
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