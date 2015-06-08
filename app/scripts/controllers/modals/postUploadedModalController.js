'use strict';

app.controller('PostUploadedModalController', function ($scope, $rootScope, $modalInstance, RestData2, params)
{
	$scope.uploaded = {
			splits: {}
		};
	$scope.categories = [];
	$scope.title = params.title;
	$scope.post = 'Post New';

	$scope.dataErrorMsg = [];

//	ngProgress.start();

//	RestData(
//		{
//			Authorization:		$localStorage.authorization,
//			'TOKENID':			$localStorage.token_id,
//			'X-Requested-With':	'XMLHttpRequest'
//		})
	RestData2().getUploadedTransaction(
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
						angular.forEach(response.errors,
							function(error)
							{
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
//			},
//			function (error)
//			{
//				if (error.status == '401' && error.statusText == 'EXPIRED')
//				{
//					$localStorage.authenticated		= false;
//					$localStorage.authorizedRoles	= false;
//					$localStorage.userFullName		= false;
//					$localStorage.token_id			= false;
//					$localStorage.authorization		= false;
//					$location.path("/login");
//				} else {
//					$rootScope.error = error.status + ' ' + error.statusText;
//				}
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
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		$scope.uploaded.transaction_id = $scope.idSelectedTransaction;

//		RestData(
//			{
//				Authorization:		$localStorage.authorization,
//				'TOKENID':			$localStorage.token_id,
//				'X-Requested-With':	'XMLHttpRequest'
//			})
		RestData2().postUploadedTransaction($scope.uploaded,
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
//				},
//				function (error)
//				{
//					if (error.status == '401' && error.statusText == 'EXPIRED')
//					{
//						$localStorage.authenticated		= false;
//						$localStorage.authorizedRoles	= false;
//						$localStorage.userFullName		= false;
//						$localStorage.token_id			= false;
//						$localStorage.authorization		= false;
//						$location.path("/login");
//					} else {
//						$rootScope.error = error.status + ' ' + error.statusText;
//					}
				});
	};

	$scope.deleteUploaded = function()
	{
		$scope.dataErrorMsg = [];

//		ngProgress.start();

//		RestData(
//			{
//				Authorization:		$localStorage.authorization,
//				'TOKENID':			$localStorage.token_id,
//				'X-Requested-With':	'XMLHttpRequest'
//			})
		RestData2().deleteUploadedTransaction(
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
//				},
//				function (error)
//				{
//					if (error.status == '401' && error.statusText == 'EXPIRED')
//					{
//						$localStorage.authenticated		= false;
//						$localStorage.authorizedRoles	= false;
//						$localStorage.userFullName		= false;
//						$localStorage.token_id			= false;
//						$localStorage.authorization		= false;
//						$location.path("/login");
//					} else {
//						$rootScope.error = error.status + ' ' + error.statusText;
//					}
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