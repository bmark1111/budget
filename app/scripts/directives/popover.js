app.directive("customPopover", ["$popover", "$compile", function($popover, $compile) {
	return {
		restrict: "A",
		link: function(scope, element, attrs, RestData, $localStorage, $location, $rootScope)
		{
			var myPopover = $popover(element, {
					title: 'My Title',
					contentTemplate: 'example.html',
					html: true,
					trigger: 'manual',
					autoClose: true,
					scope: scope
				});
		}
	}
}]);
