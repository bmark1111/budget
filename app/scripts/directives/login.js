//app.directive('loginDialog', function (AUTH_EVENTS)
//{
//console.log('loginDialog')
//	return {
//		restrict: 'A',
//		template: '<div ng-if="visible" ng-include="\'app/views/login-form.html\'">',
//		link: function (scope)
//			{
//console.log('loginDialog - link')
//				var showDialog = function ()
//								{
//console.log('loginDialog - showDialog')
//									scope.visible = true;
//								};
//
//				scope.visible = false;
//
//				scope.$on(AUTH_EVENTS.notAuthenticated, showDialog);
//				scope.$on(AUTH_EVENTS.sessionTimeout, showDialog)
//			}
//	};
//});

app.directive('formAutofillFix', function ($timeout)
{
	return function (scope, element, attrs)
	{
		element.prop('method', 'post');
		if (attrs.ngSubmit)
		{
			$timeout(function ()
			{
				element
				.unbind('submit')
				.bind('submit', function (event)
					{
						event.preventDefault();
						element.find('input, textarea, select')
//							.trigger('input')
//							.trigger('change')
//							.trigger('keydown');
						scope.$apply(attrs.ngSubmit);
					});
			});
		}
	};
});