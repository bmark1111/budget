app.directive("splitTran", function () {
	return {
		restrict: 'A',
		replace: true,
		template:	'<div class="row table-responsive">' +
						'<table style="width:95%;margin-left:15px;" ng-if="is_split">' +
							'<tr>' +
								'<th style="width: 28%"><span class="pull-left">Payer/Payee</span></th>' +
								'<th style="width: 12%"><span class="pull-left">Amount</span></th>' +
								'<th style="width: 12%"><span class="pull-left">Type</span></th>' +
								'<th style="width: 20%"><span class="pull-left">Category</span></th>' +
								'<th style="width: 23%"><span class="pull-left">Notes</span></th>' +
								'<th style="width: 5%"></th>' +
							'</tr>' +
							'<tr ng-repeat="t in transaction.splits" ng-if="t.is_deleted != 1">' +
								'<td style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.vendor_id[$index] || calc[$index] }">' +
									'<div live-search="vendor_id" table="splits" index="{{ $index }}" displayname="{{ t.vendor.display_name }}"></div>' +
									'<span class="help-block" ng-show="validation.splits.vendor_id[$index]">{{ validation.splits.vendor_id[$index] }}</span>' +
									'<span class="help-block" ng-show="calc[$index]">{{ calc[$index] }}</span>' +
								'</td>' +
								'<td style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.amount[$index] || calc[$index] }">' +
									'<input type="text" class="form-control" ng-model="t.amount" ng-blur="refreshSplits()" />' +
									'<span class="help-block" ng-show="validation.splits.amount[$index]">{{ validation.splits.amount[$index] }}</span>' +
									'<span class="help-block" ng-show="calc[$index]">{{ calc[$index] }}</span>' +
								'</td>' +
								'<td style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.type[$index] || calc[$index] }">' +
									'<select class="form-control" name="type" ng-model="t.type" ng-change="refreshSplits()">' +
										'<option>Select Type</option>' +
										'<option ng-selected="t.type == \'CHECK\'" value="CHECK">Check</option>' +
										'<option ng-selected="t.type == \'DEBIT\'" value="DEBIT">Debit</option>' +
										'<option ng-selected="t.type == \'CREDIT\'" value="CREDIT">Credit</option>' +
										'<option ng-selected="t.type == \'DSLIP\'" value="DSLIP">Deposit</option>' +
									'</select>' +
									'<span class="help-block" ng-show="validation.splits.type[$index]">{{ validation.splits.type[$index] }}</span>' +
								'</td>' +
								'<td style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.category_id[$index] }">' +
									'<select class="form-control" ng-model="t.category_id" ng-options="category.id as category.name for category in categories"></select>' +
									'<span class="help-block" ng-show="validation.splits.category_id[$index]">{{ validation.splits.category_id[$index] }}</span>' +
								'</td>' +
								'<td style="vertical-align:top;">' +
									'<input type="text" class="form-control" ng-model="t.notes" />' +
								'</td>' +
								'<td style="vertical-align:top;">' +
									'<span class="glyphicon glyphicon-trash" ng-click="deleteSplit($index)" style="cursor:pointer;"></span>' +
								'</td>' +
							'</tr>' +
						'</table>' +
					'</div>',
//		template:	'<div>' +
//							'<div class="row" ng-if="is_split">' +
//								'<div class="col-sm-3 pull-left">Payer/Payee</div>' +
//								'<div class="col-sm-1 pull-left">Amount</div>' +
//								'<div class="col-sm-1 pull-left">Type</div>' +
//								'<div class="col-sm-2 pull-left">Category</div>' +
//								'<div class="col-sm-3 pull-left">Notes</div>' +
//								'<div class="col-sm-1></div>' +
//							'</div>' +
//							'<div class="row xxxx" ng-repeat="t in transaction.splits" ng-if="t.is_deleted != 1">' +
//								'<div class="col-sm-3" style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.vendor_id[$index] || calc[$index] }">' +
//									'<div live-search="vendor_id" table="splits" index="{{ $index }}" displayname="{{ t.vendor.display_name }}"></div>' +
//									'<span class="help-block" ng-show="validation.splits.vendor_id[$index]">{{ validation.splits.vendor_id[$index] }}</span>' +
//									'<span class="help-block" ng-show="calc[$index]">{{ calc[$index] }}</span>' +
//								'</div>' +
//								'<div class="col-sm-1" style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.amount[$index] || calc[$index] }">' +
//									'<input type="text" class="form-control" ng-model="t.amount" ng-blur="refreshSplits()" />' +
//									'<span class="help-block" ng-show="validation.splits.amount[$index]">{{ validation.splits.amount[$index] }}</span>' +
//									'<span class="help-block" ng-show="calc[$index]">{{ calc[$index] }}</span>' +
//								'</div>' +
//								'<div class="col-sm-1" style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.type[$index] || calc[$index] }">' +
//									'<select class="form-control" name="type" ng-model="t.type" ng-change="refreshSplits()">' +
//										'<option>Select Type</option>' +
//										'<option ng-selected="t.type == \'CHECK\'" value="CHECK">Check</option>' +
//										'<option ng-selected="t.type == \'DEBIT\'" value="DEBIT">Debit</option>' +
//										'<option ng-selected="t.type == \'CREDIT\'" value="CREDIT">Credit</option>' +
//										'<option ng-selected="t.type == \'DSLIP\'" value="DSLIP">Deposit</option>' +
//									'</select>' +
//									'<span class="help-block" ng-show="validation.splits.type[$index]">{{ validation.splits.type[$index] }}</span>' +
//								'</div>' +
//								'<div class="col-sm-2" style="vertical-align:top;" ng-class="{\'has-error\': validation.splits.category_id[$index] }">' +
//									'<select class="form-control" ng-model="t.category_id" ng-options="category.id as category.name for category in categories"></select>' +
//									'<span class="help-block" ng-show="validation.splits.category_id[$index]">{{ validation.splits.category_id[$index] }}</span>' +
//								'</div>' +
//								'<div class="col-sm-3" style="vertical-align:top;">' +
//									'<input type="text" class="form-control" ng-model="t.notes" />' +
//								'</div>' +
//								'<div class="col-sm-1" style="vertical-align:top;">' +
//									'<span class="glyphicon glyphicon-trash" ng-click="deleteSplit($index)" style="cursor:pointer;"></span>' +
//								'</div>' +
//							'</div>' +
//					'</div>',
		link: function (scope, element, attrs) {
			// split transaction
			attrs.$observe('isSplit', function(is_split) {
				if (is_split) {
					if (Object.size(scope.transaction.splits) === 0 && scope.transaction.amount > 0 && typeof(scope.transaction.type) !== 'undefined') {
						var newItem = {
							amount:			scope.transaction.amount,
							type:			scope.transaction.type,
							category_id:	'',
							notes:			''
						}
						scope.transaction.splits = {};
						scope.transaction.splits[0] = newItem;
//						return;
					}
				}
//				scope.is_split = false;
			});

			scope.refreshSplits = function() {
				split();
			};

			scope.$watch('transaction.amount', function() {
				split();
			});
			
			function split() {
//				if (Object.size(scope.transaction.splits) > 0) {
				if (scope.is_split) {
					var newItem = {
						amount:			'',
						type:			scope.transaction.type,
						category_id:	'',
						notes:			''
					}
					// calculate total of all splits
					var split_total = parseFloat(0);
					angular.forEach(scope.transaction.splits,
						function(split) {
							if (split.is_deleted !== 1) {
								switch (split.type) {
									case 'DEBIT':
									case 'CHECK':
										split_total -= parseFloat(split.amount);
										break;
									case 'CREDIT':
									case 'DSLIP':
										split_total += parseFloat(split.amount);
										break;
								}
							}
						});

					var new_amount_type = '';
					var transaction_amount = 0;
					scope.calc = Array();
					var yy = Object.keys(scope.transaction.splits).length
					switch (scope.transaction.type) {
						case 'CREDIT':
						case 'DSLIP':
							transaction_amount = parseFloat(scope.transaction.amount);
							split_total = parseFloat(split_total);
							new_amount_type = 'DEBIT';
							break;
						case 'DEBIT':
						case 'CHECK':
							transaction_amount = parseFloat(scope.transaction.amount);
							split_total = -parseFloat(split_total);
							new_amount_type = 'CREDIT';
						break;
					}
					if (transaction_amount != split_total.toFixed(2)) {
						var new_amount = scope.transaction.amount - split_total;
						if (new_amount < 0) {
							new_amount = -new_amount.toFixed(2);
							newItem.type = new_amount_type;
						}
						newItem.amount = new_amount.toFixed(2);
						scope.transaction.splits[yy] = newItem;
					}
				}
			};

			scope.deleteSplit = function(ele) {
				scope.transaction.splits[ele].is_deleted = 1;

				// calculate split_total of all splits
				var split_total = parseFloat(0);
				angular.forEach(scope.transaction.splits,
					function(split) {
						if (split.is_deleted != 1) {
							split_total += parseFloat(split.amount);
						}
					});
				scope.calc = Array();
				if (scope.transaction.amount != split_total.toFixed(2)) {
					scope.calc[ele-1] = 'Split amounts do not match Item amount';
				}
			};

			Object.size = function(obj) {
				var size = 0, key;
				for (key in obj) {
					if (obj.hasOwnProperty(key)) size++;
				}
				return size;
			};
		}
	};
});