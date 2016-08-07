'use strict'

var services = {};

/**
 * @constructor
 * @returns {undefined}
 */
services.periods = function($q, RestData2, $rootScope, $localStorage) {
	
	this.$q = $q;
	this.RestData2 = RestData2;
	this.$rootScope = $rootScope;
	this.$localStorage = $localStorage;
};

/**
 *
 */
//services.periods.prototype.getTransactions = function () {
services.periods.prototype.get = function (interval) {

	var self = this;

	var deferred = self.$q.defer();

	self.$rootScope.periods = [];
	self.$rootScope.period_start = 0;

	self.RestData2().getSheetTransactions({ interval: interval },
		function (response) {
			deferred.resolve(response);
		},
		function (error) {
			deferred.reject(error);
		});
	return deferred.promise;
};

/**
 * @method buildPeriods
 * @public
 * @param {Object} data
 * @param {number} interval
 * @returns {periods_L3.buildPeriods.periodsAnonym$1}
 */
services.periods.prototype.buildPeriods = function(data, interval) {
	
	var self = this;

	var budget_interval;
	var budget_interval_unit;

	switch (this.$localStorage.budget_mode) {
		case 'weekly':
			budget_interval = 7;
			budget_interval_unit = 'Days';
			break;
		case 'bi-weekly':
			budget_interval = 14;
			budget_interval_unit = 'Days';
//				$offset = $this->_getEndDay();
//				if ($interval == 0) {
//					$start_day = ($offset - (budget_interval * ($localStorage.sheet_views)));					// go back 'sheet views'
//					$end_day = ($offset + (budget_interval * ($localStorage.sheet_views)));						// go forward 'sheet views'
//				} else if ($interval < 0) {
//					$start_day = ($offset - (budget_interval * ($localStorage.sheet_views - $interval)));		// - 'sheet_views' entries and adjust for interval
//					$end_day = ($offset - (budget_interval * ($localStorage.sheet_views - $interval - 1)));		// + 'sheet_views' entries and adjust for interval
//				} else if ($interval > 0) {
//					$start_day = ($offset + (budget_interval * ($localStorage.sheet_views + $interval - 1)));	// - 'sheet_views' entries and adjust for interval
//					$end_day = ($offset + (budget_interval * ($localStorage.sheet_views + $interval)));			// + 'sheet_views' entries and adjust for interval
//				}
//				$sd = date('Y-m-d', strtotime($this->budget_start_date . " +" . $start_day . " Days"));
//				$ed = date('Y-m-d', strtotime($this->budget_start_date . " +" . $end_day . " Days"));
			break;
		case 'semi-monthy':
			budget_interval = 1;
			budget_interval_unit = 'Months';
			break;
		case 'monthly':
			budget_interval_unit = 'Months';
			var start = new Date();
			start.setDate(1);
			var end = new Date();
			end.setDate(1);
			var start_month;
			var end_month;
			if (interval == 0) {
				start.setMonth(start.getMonth() - (this.$localStorage.sheet_views/2) + 1);
				start.setDate(1);
				end.setMonth(end.getMonth() + (this.$localStorage.sheet_views/2) + 1);
				end.setDate(0);
			} else if (interval < 0) {
				start_month = budget_interval * (this.$localStorage.sheet_views - interval - 1);		// - 'sheet_views' entries and adjust for interval
			//	$start->sub(new DateInterval("P" . $start_month . "M"));
				end_month = budget_interval * (this.$localStorage.sheet_views - interval);			// + 'sheet_views' entries and adjust for interval
			//	$end->add(new DateInterval("P" . $end_month . "M"));
			} else if (interval > 0) {
				start_month = budget_interval * (this.$localStorage.sheet_views + interval);			// go back 'sheet views' and adjust for interval
			//	$start->add(new DateInterval("P" . $start_month . "M"));
				end_month = budget_interval * (this.$localStorage.sheet_views + interval + 1);		// go forward 'sheet views' and adjust for interval
			//	$end->add(new DateInterval("P" . $end_month . "M"));
			}
			start.setHours(0);
			start.setMinutes(0);
			start.setSeconds(0);
			start.setMilliseconds(0);
			end.setHours(0);
			end.setMinutes(0);
			end.setSeconds(0);
			end.setMilliseconds(0);
			break;
		default:
//			$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/loadAll)"));
//			$this->ajax->output();
	}

	var output = [], o_idx = 0;
	var running_total = data.balance_forward;
	var idx = 0;
	var transaction_date;
	while (start.getTime() < end.getTime()) {
		var interval_beginning = start.toISOString().split('T')[0] + 'T00:00:00';
		switch (this.$localStorage.budget_mode) {
			case 'weekly':
				break;
			case 'bi-weekly':
				break
			case 'semi-monthly':
				break;
			case 'monthly':
				start.setMonth(start.getMonth() + 1);
				break;
		}
		var interval_ending = new Date(start.getFullYear(), start.getMonth(), start.getDate(), 0, 0, 0);
		interval_ending.setDate(0);
		interval_ending = interval_ending.toISOString().split('T')[0] + 'T23:59:59';
		output[o_idx] = {
			'accounts': [],
			'amounts': {},
			'balance_forward': running_total,
			'balances': {},
			'interval_beginning': interval_beginning,
			'interval_ending': interval_ending,
			'interval_total': 0,
			'running_total': running_total,
			'totals': {},
			'types': {},
			'transactions': {}								// individual transactions in this period
		};
		for(var x in self.$rootScope.bank_accounts) {
			output[o_idx].accounts[x] = {
				balance: null,
				bank_account_id: self.$rootScope.bank_accounts[x].id,
				name: self.$rootScope.bank_accounts[x].name,
				reconciled_date: null,
				balance_date: null
			};
		}

		if (data.result[idx]) {
			var trd = data.result[idx].transaction_date.split('-');
			transaction_date = new Date(trd[0], --trd[1], trd[2], 0, 0, 0, 0);

			while (transaction_date.getTime() < start.getTime()) {
				if (data.result[idx].splits) {
					// split transaction
					angular.forEach(data.result[idx].splits, function(split, ii) {
						self.addToTotals(split, output[o_idx]);
					});
				} else {
					self.addToTotals(data.result[idx], output[o_idx]);
				}

				// save account balance
				for(var x = 0; x < output[o_idx].accounts.length; x++) {
					if (output[o_idx].accounts[x].bank_account_id == data.result[idx].bank_account_id) {
						output[o_idx].accounts[x].balance			= data.result[idx].bank_account_balance;
						output[o_idx].accounts[x].reconciled_date	= (data.result[idx].reconciled_date) ? data.result[idx].reconciled_date: null;
						output[o_idx].accounts[x].balance_date		= data.result[idx].transaction_date;
output[o_idx].accounts[x].transaction_id = data.result[idx].id
						break;
					}
				};

				idx++;
				if (!data.result[idx]) {
					break;
				}

				var trd = data.result[idx].transaction_date.split('-');
				transaction_date = new Date(trd[0], --trd[1], trd[2], 0, 0, 0, 0);
			}
			running_total = output[o_idx].running_total;
		}
		o_idx++;
	}
	return output;
};

/**
 * @method addToTotals
 * @private
 * @param {Object} data
 * @param {Object} output
 * @returns {undefined}
 */
services.periods.prototype.addToTotals = function(data, output) {

	var category_id = parseInt(data.category_id);

	var amount = parseFloat(data.amount);

	switch(data.transaction_type) {
		default:
		case 0:
			if (!output.types[category_id]) {
				output.types[category_id] = 1;
			} else {
				output.types[category_id] |= 1;
			}
			break;
		case 1:
			if (!output.types[category_id]) {
				output.types[category_id] = 2;
			} else {
				output.types[category_id] |= 2;
			}
			break;
		case 2:
			if (!output.types[category_id]) {
				output.types[category_id] = 4;
			} else {
				output.types[category_id] |= 4;
			}
			break;
	}

	// Save the individual transactions
	if (output.transactions[category_id]) {
		output.transactions[category_id].push(data);
	} else {
		output.transactions[category_id] = Array(data);
	}

	// Calculate running total
	switch (data.type) {
		case 'CREDIT':
		case 'DSLIP':
			output.interval_total += amount;
			output.running_total += amount;
			if (output.totals[category_id]) {
				output.totals[category_id] += amount;
			} else {
				output.totals[category_id] = amount;
			}
			break;
		case 'DEBIT':
		case 'CHECK':
			output.interval_total -= amount;
			output.running_total -= amount;
			if (output.totals[category_id]) {
				output.totals[category_id] -= amount;
			} else {
				output.totals[category_id] = -amount;
			}
			break;
	}
};

app.service('Periods',  [ "$q", "RestData2", '$rootScope', '$localStorage', services.periods]);
