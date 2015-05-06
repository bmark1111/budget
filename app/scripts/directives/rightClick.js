//app.directive('myRightClick', function($parse) {
//  return {
//    scope: false,
//    restrict: 'A',
//    link: function(scope, element, attrs) {
//      var fn = $parse(attrs.myRightClick);
//      element.bind('contextmenu', function(event) {
//        scope.$apply(function() {
//          event.preventDefault();
//          fn(scope, {$event:event});
//        });
//      });
//    }
//  }
//});
app.directive('ngRightClick', function($parse) {
	return function(scope, element, attrs) {
		var fn = $parse(attrs.ngRightClick);
		element.bind('contextmenu', function(event) {
			scope.$apply(function() {
				event.preventDefault();
				fn(scope, {$event:event});
			});
		});
	};
});

app.directive("ngContextmenu", function () {
	contextMenu = {};
	contextMenu.restrict = "AE";
	contextMenu.scope = {"isVisible": "="};
	contextMenu.link = function (lScope, lElem, lAttr)
		{
			lElem.on("contextmenu", function (event) {
				event.preventDefault();
//console.log("Element right clicked.");
				lScope.$apply(function () {
					lScope.$parent.isVisible = true;
				});
			});
			lElem.on("mouseleave", function (event) {
//console.log("Leaved the div");
				lScope.$apply(function () {
					lScope.$parent.isVisible = false;
				});
			});
		};
	return contextMenu;
});
