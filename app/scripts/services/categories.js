app.service('Categories',  [ "$q", "RestData2", '$rootScope',
	function ($q, RestData2, $rootScope) {
		this.get = function () {
			var deferred = $q.defer();
			if (typeof($rootScope.categories) === 'undefined') {
				RestData2().getCategories(
					function (response) {
						console.log("categories got");
						deferred.resolve(response);
					},
					function (error) {
						console.log("failed to get categories");
						deferred.reject(error);
					});
			} else {
				console.log("already loaded categories");
				deferred.resolve(true);
			}
			return deferred.promise;
		};
	}]);