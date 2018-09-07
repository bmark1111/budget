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
 * Holds all account information
 * @name data
 * @public
 * @type {Array}
 */
services.accounts.prototype.data = [];

/**
 * Holds all active account information
 * @name active
 * @public
 * @type {Array}
 */
services.accounts.prototype.active = [];

/**
 * @name getAccounts
 * @public
 */
services.accounts.prototype.get = function () {

	var self = this;

	var deferred = this.$q.defer();

	if (this.data.length === 0) {
		this.RestData2().getBankAccounts(function (response) {
			console.log("accounts got");
			self.active = [];
			self.data = [];
			angular.forEach(response.data.bank_accounts, function(account) {
				var diff = 0;
				if (account.date_closed) {
					var now = new Date();
					now.setHours(0);
					now.setMinutes(0);
					now.setSeconds(0);
					now.setMilliseconds(0);
					var dt = account.date_closed.split('-');
					var date_closed = new Date(dt[0], dt[1]-1, dt[2], 0, 0, 0, 0);
					diff = date_closed.getTime() - now.getTime();
				}
				if (diff >= 0) {
					// account is still open, add to active array
					self.active.push({
						'id': account.id,
						'name': account.bank.name + ' ' + account.name,
						'date_opened': account.date_opened,
						'date_closed': account.date_closed
					});
				}
				self.data.push({
					'id': account.id,
					'name': account.bank.name + ' ' + account.name,
					'date_opened': account.date_opened,
					'date_closed': account.date_closed
				});
			});
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

services.accounts.prototype.clear = function () {

//	this.all = false;
//	this.bank_account_balance = Array();
//	this.periods = [];
//	this.period_start = 0;
	this.data = [];
};

app.service('Accounts',  [ "$q", "RestData2", services.accounts]);
