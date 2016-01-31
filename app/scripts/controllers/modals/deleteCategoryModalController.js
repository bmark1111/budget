'use strict';

app.controller('DeleteCategoryModalController', function ($scope, $rootScope, $modalInstance, RestData2, params)
{
	$scope.dataErrorMsg	= [];
	$scope.title = params.title;
	$scope.message = params.msg;

	$scope.ok = function () {
//		ngProgress.start();

		RestData2().deleteCategory(
				{
					'id': params.id
				},
				function(response) {
					if (!!response.success) {
						$modalInstance.close();
						// now update the global categories data
						delete $rootScope.categories;
						// now update the global intervals data
						delete $rootScope.intervals;
						delete $rootScope.periods;
					} else {
						if (response.errors) {
							angular.forEach(response.errors,
								function(error) {
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
				});
	};

	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
});