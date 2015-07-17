'use strict';

app.controller('DashboardController', function($scope, $rootScope, RestData2, $filter)
{
	$scope.intervals = [];
	$scope.start_interval = 0;

	$scope.categories = $rootScope.categories;

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var loadTransactions = function()
	{
		$scope.dataErrorMsg = [];

		RestData2().getTransactions(
				{
					interval: interval
				},
				function(response)
				{
					if (!!response.success)
					{
						// set current interval
						angular.forEach(response.data.result,
							function(interval, key)
							{
								// set the current interval
								var sd = new Date(interval.interval_beginning);
								var ed = new Date(interval.interval_ending);
								var now = new Date();
								interval.current_interval = (now >= sd && now <= ed) ? true: false;

								$scope.start_interval = 0;
								$scope.intervals[key] = interval;
							});
					} else {
						if (response.errors)
						{
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
				});
	};

	loadTransactions();

	$scope.showTheseTransactions = function(category_id, index)
	{
		$scope.dataErrorMsgThese = false;

		var date = $filter('date')($scope.intervals[index].interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + index + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

		RestData2().getTheseTransactions(
				{
					interval_beginning:	$scope.intervals[index].interval_beginning,
					interval_ending:	$scope.intervals[index].interval_ending,
					category_id:		category_id
				},
				function(response)
				{
					if (!!response.success)
					{
						$scope.transactions = response.data.result;
						$scope.transactions_seq = Object.keys(response.data.result);
					} else {
						$scope.dataErrorMsgThese = response.errors;
					}
				});
	};

	$scope.moveInterval = function(direction)
	{
//		interval = interval + direction;
//
//		loadTransactions();
//////////////////////////////////////////////////////////////////
//		var moved = Array();
		if (direction == -1)
		{
			if ($scope.start_interval > 0)
			{	// move the start pointer
				$scope.start_interval--;
			} else {
				// add an array element at the beginning
//				moved.push(getNext(0));
				getNext(0, direction);
			}
		}

//		angular.forEach($scope.intervals,
//			function(interval)
//			{
//				moved.push(interval)
//			});

		else if (direction == 1)
		{
			$scope.start_interval++;
			var last_interval = $scope.start_interval + 11;
			if (typeof($scope.intervals[last_interval]) == 'undefined')
			{
//				moved.push(getNext($scope.intervals.length - 1));
				getNext($scope.intervals.length - 1, direction);
			}
		}

//		$scope.intervals = moved;
	};

	var getNext = function(index, direction)
	{
		var ed = new Date($scope.intervals[index].interval_ending);
		var sd = new Date($scope.intervals[index].interval_beginning);

// TODO: this will have to be changed to accomodate other interval types (ie. 2 weeks, month, etc)
		if (index == 0)
		{
			ed.setDate(ed.getDate() - 7);
			sd.setDate(sd.getDate() - 7);
		} else {
			ed.setDate(ed.getDate() + 7);
			sd.setDate(sd.getDate() + 7);
		}

		RestData2().getInterval(
				{
					interval_beginning: sd.toISOString(),
					interval_ending: ed.toISOString()
				},
				function(response)
				{
					if (!!response.success)
					{
						var moved = Array();
						// if moving backwards add interval to front of array
						if (direction == -1)
						{
							moved.push(response.data.result);
						}
						// add the current intervals
						angular.forEach($scope.intervals,
							function(interval)
							{
								moved.push(interval)
							});
						// if moving forward add interval to end of array
						if (direction == 1)
						{
							moved.push(response.data.result);
						}
						$scope.intervals = moved;
					} else {
						if (response.errors)
						{
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
				});
//		return {
//			balance_forward:	'',
//			current_interval:	false,
//			interval_beginning:	sd.toISOString(),
//			interval_ending:	ed.toISOString(),
//			interval_total:		0,
//			running_total:		0,
//			totals:				{
//									1:	0,
//									2:	0,
//									3:	0,
//									4:	0,
//									5:	0,
//									6:	0,
//									7:	0,
//									8:	0,
//									9:	0,
//									10:	0,
//									11:	0,
//									12:	0,
//									13:	0,
//									14:	0,
//									15:	0,
//									16:	0,
//									17:	0,
//									18:	0,
//									19:	0,
//									21:	0
//								}
//				}

	}

});
