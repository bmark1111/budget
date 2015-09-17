'use strict';

app.controller('DashboardController', ['$q', '$scope', '$rootScope', 'RestData2', 'Categories', function($q, $scope, $rootScope, RestData2, Categories) {

	$scope.dataErrorMsg = [];

//	var categoryPromise = Categories.get();

	var getYTDTotals = function() {
		var deferred = $q.defer();
		var result = RestData2().getYTDTotals(
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});
		return deferred.promise;
	};

	$q.all([
		Categories.get(),	//categoryPromise,	//getCategories(),
		getYTDTotals()
	]).then(function(response) {
		// load the categories
		if (!!response[0].success) {
			$rootScope.categories = [];
			angular.forEach(response[0].data.categories,
				function(category) {
					$rootScope.categories.push(category)
				});
		}
		// load the YTD Totals
		if (!!response[1].success) {
			$scope.ytdYear = response[1].data.year;
			$scope.ytdTotals = [];
			angular.forEach($rootScope.categories,
				function(category, key) {
					var category = {
						id:		category.id,
						name:	category.name,
						total:	response[1].data.result['total_' + category.id]
					};
					$scope.ytdTotals.push(category);
				});
//		} else {
//			if (response.errors) {
//				angular.forEach(response.errors,
//					function(error) {
//						$scope.dataErrorMsg.push(error.error);
//					})
//			} else {
//				$scope.dataErrorMsg[0] = response;
//			}
		}
	});

	$scope.getYTDTransactions = function(category_id, year) {
		$scope.dataErrorMsgThese = false;

		RestData2().getYTDTransactions({
				year:			year,
				category_id:	category_id
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
				} else {
					$scope.dataErrorMsgThese = response.errors;
				}
			});
	};

}]);
