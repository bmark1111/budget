app.factory('ModalDialog', function ($modal) {
	var modalDialog = {};
	modalDialog.init =
		function() {
			var modalInstance = $modal.open({
				templateUrl: 'resetBalanceModal.html',
				controller: 'ResetBalanceModalController',
				controllerAs: 'vm',
				size: 'md',
				resolve: {
					params: function()
						{
							return {
								id: 0,
								title: 'Reset Account Balances',
								message: 'This will reset the account balances after transactions have been uploaded, added or changed'
							}
						}
				}
			});

			modalInstance.result.then(function () {
//				loadData();
				console.log('Reset balance modal result')
			},
			function () {
				console.log('Reset Balance Modal dismissed at: ' + new Date());
			});
		};
	return modalDialog;
});
