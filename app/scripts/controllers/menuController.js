'use strict';

app.controller('MenuController', MenuController);

function MenuController($scope, ModalDialog) {
console.log('MenuController');
	$scope.openResetBalancesModal = function() {
		ModalDialog.init();
	};
};
