'use strict';

app.controller('LiveSearchController', ['$scope', 'RestData2',

	function($scope, RestData2) {
console.log('LiveSearchController')
		var liveSearch_save = false;
		$scope.livesearch_results = false;
		$scope.liveSearchId = null;
		$scope.liveSearchName = null;

		$scope.livesearch = function($event) {
			if ($event.keyCode !== 37 && $event.keyCode !== 39) {
				$scope.liveSearchId = null;
				if ($scope.liveSearchName.length >= 2) {
					RestData2().liveSearch({
						type:	'vendors',
						search:	$scope.liveSearchName
					},
					function(resp) {
						if (resp.success === 1) {
							$scope.livesearch_results = (resp.data.result[0]) ? resp.data.result: false;
							$scope.liveSearchId = null;
						} else {
							// ERROR
							$scope.livesearch_results = false;
							$scope.liveSearchId		= liveSearch_save.id;
							$scope.liveSearchName	= liveSearch_save.name;
						}
					},
					function(err) {
console.log('live search error')
console.log(err)
						$scope.livesearch_results = false;
						$scope.liveSearchId = liveSearch_save.id;
						$scope.liveSearchName = liveSearch_save.name;
					});
				} else {
					$scope.livesearch_results = false;
				}
			}
		};

		$scope.livesearchBlur = function($event) {
			$scope.livesearch_results = false;
			if (!$scope.liveSearchId && $scope.liveSearchName) {
				$scope.$emit('liveSearchBlur', {name: $scope.liveSearchName});
			}
		};

		$scope.livesearchFocus = function($event) {
			$scope.livesearch_results = false;
		};

		$scope.livesearchSelect = function(result) {
			$scope.liveSearchId		= result.id;
console.log(result)
			$scope.liveSearchName	= result.name + ' +++++';
			$scope.livesearch_results = false;
			$scope.$emit('liveSearchSelect', result);
		};

	}]);