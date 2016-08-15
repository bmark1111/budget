'use strict';

app.controller('DeleteCategoryModalController', function ($scope, $modalInstance, RestData2, params, Periods, Categories) {

	$scope.dataErrorMsg	= [];
	$scope.title = params.title;
	$scope.message = params.msg;

	$scope.ok = function () {
//		ngProgress.start();

		RestData2().deleteCategory({
				'id': params.id
			},
			function(response) {
				if (!!response.success) {
					$modalInstance.close();
					// now update the categories data
					Categories.data = [];
					// now update the global intervals data
					Periods.clear();
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
//				ngProgress.complete();
			});
	};

	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
});