'use strict';

app.controller('PopoverdemoController', function($scope, $popover, $localStorage, $location, $rootScope, RestData)
{
//	$scope.user = {
//			name: ""
//		};

//	var myPopover = $popover(angular.element(document.querySelector('#popover-as-service')), {
//			title: 'My Title',
//			contentTemplate: 'example.html',
//			html: true,
//			trigger: 'manual',
//			autoClose: true,
//			scope: $scope
//		});

	$scope.showPopover = function(interval_beginning, category_id, ele)
	{
		var myPopover = $popover(angular.element(document.querySelector('#' + ele)), {
				title: interval_beginning + ' for category ' + category_id,
				contentTemplate: 'myPopoverTemplate.html',
				html: true,
				trigger: 'manual',
				autoClose: true,
				scope: $scope,
				conbtainer: 'div'
			});

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getTheseTransactions(
				{
					interval_beginning:	interval_beginning,
					category_id:	category_id
				},
				function(response)
				{
					if (!!response.success)
					{

//		$scope.user.name = 'asasasasas';
		myPopover.show();

						$scope.transactions = response.data.result;
						$scope.transactions_seq = Object.keys(response.data.result);
					} else {
						$scope.dataErrorMsg2 = response.errors[0];
					}
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	}

/*
	var popoverShow = false;

	$scope.popover = {title: 'Title', content: "<table><tr><td>Hello Popover</td></tr><tr><td>This is a multiline message!</td></tr><tr><td>Third Line</td></tr></table>"};

	var asAServiceOptions = {
			title:		$scope.popover.title,
			content:	$scope.popover.content,
			trigger:	'click',
			autoClose:	true
		}

//	asAServiceOptions.content = 'i am content from the toggle';
	var myPopover = $popover(angular.element(document.querySelector('#popover-as-service')), asAServiceOptions);

//	myPopover.$promise.then(myPopover.toggle);

	$scope.togglePopover = function()
		{
			if (!popoverShow)
			{
console.log($('div.popover-content').text());
				$('div.popover-content').text('xxxxxxxxxxxxxxxxx');
//				var txt = $('div.popover-content').text();
//console.log(txt)
///				$scope.popover = {title: 'Title', content: "Hello Popover<br />This is a multiline message!"};
//
//				var asAServiceOptions = {
//						title:		$scope.popover.title,
//						content:	$scope.popover.content,
//						trigger:	'click',
//						autoClose:	true
//					}
//
//				asAServiceOptions.content = 'i am content from the toggle';
//				myPopover = $popover(angular.element(document.querySelector('#popover-as-service')), asAServiceOptions);

				popoverShow = true;
console.log('show popover')
			} else {
				popoverShow = false;
console.log('hide popover')
			}
//			myPopover.$promise.then(myPopover.toggle);
		}

//	myPopover.$promise.then(myPopover.toggle);
*/
});
