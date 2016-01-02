'use strict';

app.controller('DashboardController', ['$q', '$scope', '$rootScope', 'RestData2', 'Categories', function($q, $scope, $rootScope, RestData2, Categories) {

	$scope.dataErrorMsg = [];
	var now = new Date();
	$scope.ytdYear = now.getFullYear();
	$scope.ytdTotals = [];

	var getYTDTotals = function() {
		var deferred = $q.defer();
		var result = RestData2().getYTDTotals({
				year: $scope.ytdYear
			},
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});
		return deferred.promise;
	};

	$q.all([
		Categories.get(),
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
			$scope.dataErrorMsg = [];
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
	});

	$scope.getYTDTransactions = function(category_id) {
		$scope.dataErrorMsg = [];

		RestData2().getYTDTransactions({
				year:			$scope.ytdYear,
				category_id:	category_id
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
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
			});
	};

	$scope.getYTD = function() {
		$scope.dataErrorMsg = [];
		RestData2().getYTDTotals({
				year: $scope.ytdYear
			},
			function (response) {
			// load the YTD Totals
			if (!!response.success) {
				$scope.ytdTotals = [];
				angular.forEach($rootScope.categories,
					function(category, key) {
						var category = {
							id:		category.id,
							name:	category.name,
							total:	response.data.result['total_' + category.id]
						};
						$scope.ytdTotals.push(category);
					});
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
		});
	}
}]);
