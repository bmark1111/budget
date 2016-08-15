'use strict'

/**
 * @constructor
 * @returns {undefined}
 */
services.accounts = function($q, RestData2) {
	
	this.$q = $q;
	this.RestData2 = RestData2;
};

/**
 * Holds account information
 * @name data
 * @private
 * @type {Array}
 */
services.accounts.prototype.data = [];

/**
 * @name getAccounts
 * @public
 */
services.accounts.prototype.get = function () {

	var deferred = this.$q.defer();

	if (typeof(this.data) === 'undefined') {
		this.RestData2().getBankAccounts(function (response) {
			console.log("accounts got");
			deferred.resolve(response);
		},
		function (error) {
			console.log("failed to get accounts");
			deferred.reject(error);
		});
	} else {
		console.log("already loaded accounts");
		deferred.resolve(true);
	}
	return deferred.promise;
};

app.service('Accounts',  [ "$q", "RestData2", services.accounts]);
