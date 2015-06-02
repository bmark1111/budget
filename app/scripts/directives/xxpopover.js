//app.directive("customPopover", ["$popover", "$compile", function($popover, $compile) {
//	return {
//		restrict: "A",
//		link: function(scope, element, attrs, RestData, $localStorage, $location, $rootScope)
//		{
//			var myPopover = $popover(element, {
//					title: 'My Title',
//					contentTemplate: 'example.html',
//					html: true,
//					trigger: 'manual',
//					autoClose: true,
//					scope: scope
//				});
//		}
//	}
//}]);
app.directive('viewportWidth', function() {
	return {
		link:	function(scope, elm, attrs)
				{
					function getViewport()
					{
						var e = window, a = 'inner';
						if (!('innerWidth' in window))
						{
							a = 'client';
							e = document.documentElement || document.body;
						}
						return {
							width : e[a + 'Width'] ,
							height : e[a + 'Height']
						};
					}
					elm.css('maxWidth', getViewport().width + 'px');
				}
	};
});