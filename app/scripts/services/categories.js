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
 * @public
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

	if (this.data.length == 0) {
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

services.categories.prototype.clear = function () {

	console.log("categories cleared");
	this.data = [];
};


app.service('Categories',  [ "$q", "RestData2", services.categories]);
