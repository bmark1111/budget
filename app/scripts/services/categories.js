'use strict'

/**
 * @constructor
 * @returns {undefined}
 */
services.categories = function($q, RestData2) {
	
	this.$q = $q;
	this.RestData2 = RestData2;
};

/**
 * Holds category information
 * @name data
 * @private
 * @type {Array}
 */
services.categories.prototype.data = [];

/**
 * @name getCategories
 * @public
 */
services.categories.prototype.get = function () {

	var self = this;

	var deferred = this.$q.defer();

	if (typeof(this.categories) === 'undefined') {
		this.RestData2().getCategories(function (response) {
			console.log("categories got");
			self.data = [];
			angular.forEach(response.data.categories, function(category, x) {
				self.data.push(category)
			});
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

app.service('Categories',  [ "$q", "RestData2", services.categories]);
