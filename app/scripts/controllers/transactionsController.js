'use strict';

app.controller('TransactionsController', function($q, $scope, $rootScope, $modal, $timeout, RestData2, Accounts, Categories) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.transactions	= [];

	$scope.dataErrorMsg	= [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:		1,
		date:				'',
		vendor:				'',
		description:		'',
		amount:				'',
		bank_account_id:	'',
		category_id:		''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllTransactions({
				'date':					$scope.search.date,
				'vendor':				$scope.search.vendor,
				'description':			$scope.search.description,
				'amount':				$scope.search.amount,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
				'sort':					'transaction_date',
				'sort_dir':				'DESC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					for(var x in $scope.transactions) {
						for(var y = 0; y < $scope.accounts.length; y++) {
							if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
								$scope.transactions[x].bankName = $scope.accounts[y].name;
								break;
							}
						}
					}
					$scope.transactions_seq = Object.keys(response.data.result);
					$scope.recCount = response.data.total_rows;
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
//				ngProgress.complete();
			});
	}

//	loadData();

	var getTransactions = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllTransactions({
				'date':					$scope.search.date,
				'vendor':				$scope.search.vendor,
				'description':			$scope.search.description,
				'amount':				$scope.search.amount,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
				'sort':					'transaction_date',
				'sort_dir':				'DESC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
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
		Accounts.get(),
		Categories.get(),
		getTransactions()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
		// load the categories
		$scope.categories = Categories.data;

		// load the transaction
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.transactions = response[2].data.result;
				for(var x in $scope.transactions) {
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
							$scope.transactions[x].bankName = $scope.accounts[y].name;
							break;
						}
					}
				}
				$scope.transactions_seq = Object.keys(response[2].data.result);
				$scope.recCount = response[2].data.total_rows;
			}
		} else {
			if (response[2].errors) {
				angular.forEach(response[2].errors,
					function(error) {
						$scope.dataErrorMsg.push(error.error);
					})
			} else {
				$scope.dataErrorMsg[0] = response[2];
			}
		}
	});

	var timer = null;
	$scope.refreshData = function() {
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
	};

	$scope.pageChanged = function() {
		loadData();
	};

	// open date picker
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.uploadTransactions = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/uploadModal.html',
			controller: 'UploadModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							title: 'Upload Transactions'
						}
					}
			}
		});

		modalInstance.result.then(function (response) {
			$rootScope.transaction_count = (parseInt(response.count) > 0) ? parseInt(response.count): '';
		},
		function () {
			console.log('Upload Modal dismissed at: ' + new Date());
		});
	};

	$scope.downloadTransactions = function() {

		RestData2().getAllTransactions({
				'date':					$scope.search.date,
				'vendor':				$scope.search.vendor,
				'description':			$scope.search.description,
				'amount':				$scope.search.amount,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
				'sort':					'transaction_date',
				'sort_dir':				'DESC'//,
//				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
//				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					var arrData = response.data.result;
console.log(arrData);
					var CSV = '';    
					//Set Report title in first row or line
					//CSV += ReportTitle + '\r\n\n';

					//This condition will generate the Label/Header
					var row = "";

					//This loop will extract the label from 1st index of on array
					for (var index in arrData[0]) {
						//Now convert each value to string and comma-seprated
						row += index + ',';
					}

					row = row.slice(0, -1);

					//append Label row with line break
					CSV += row + '\r\n';
console.log(CSV)
//1st loop is to extract each row
for (var i in arrData) {
	var row = "";
console.log(arrData[i]);
	//2nd loop will extract each column and convert it in string comma-seprated
	for (var index in arrData[i]) {
console.log(arrData[i][index]);
		if (typeof arrData[i][index] !== 'object') {
			row += '"' + arrData[i][index] + '",';
		} else {
			console.log('INDEX', index);
			switch (index) {
				
			}
		}
	}
//return;
//	row.slice(0, row.length - 1);

	//add a line break after each row
	CSV += row + '\r\n';
break;
}
console.log(CSV);
/*
if (CSV == '') {        
	alert("Invalid data");
	return;
}   

//Generate a file name
var fileName = "MyReport_";
//this will remove the blank-spaces from the title and replace it with an underscore
fileName += ReportTitle.replace(/ /g,"_");   

//Initialize file format you want csv or xls
var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);

// Now the little tricky part.
// you can use either>> window.open(uri);
// but this will not work in some browsers
// or you will not get the correct file extension    

//this trick will generate a temp <a /> tag
var link = document.createElement("a");    
link.href = uri;

//set the visibility hidden so it will not effect on your web-layout
link.style = "visibility:hidden";
link.download = fileName + ".csv";

//this part will append the anchor tag and remove it after automatic click
document.body.appendChild(link);
link.click();
document.body.removeChild(link);
*/
//					$scope.transactions = response.data.result;
//					for(var x in $scope.transactions) {
//						for(var y = 0; y < $scope.accounts.length; y++) {
//							if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
//								$scope.transactions[x].bankName = $scope.accounts[y].name;
//								break;
//							}
//						}
//					}
//					$scope.transactions_seq = Object.keys(response.data.result);
//					$scope.recCount = response.data.total_rows;
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
//				ngProgress.complete();
			});
	};
	
	$scope.addTransaction = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editModal.html',
			controller: 'EditModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: 0,
							title: 'Add Transaction'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Modal dismissed at: ' + new Date());
		});
	};

	$scope.editTransaction = function(transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editModal.html',
			controller: 'EditModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: transaction_id,
							title: 'Edit Transaction'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteTransaction = function (transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							id: transaction_id,
							title: 'Delete Transaction ?',
							msg: 'Are you sure you want to delete this transaction. This action cannot be undone.'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Modal dismissed at: ' + new Date());
		});
	};

});
