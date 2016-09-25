app.directive("liveSearch", ['RestData2', function (RestData2) {
	return {
		restrict: 'A',
//		replace: false,
//		scope: {
//			liveSearchName: '=?',
//			liveSearchId: '=?',
//			livesearchResults: '=?',
//			livesearchModel: '=?',
//			livesearchTable: '=?',
//			livesearchIndex: '=?'
//		},
		template:	'<input type="text" name="name" class="form-control" ng-model="liveSearchName" autocomplete="off" />' +
					'<div ng-show="livesearchResults" class="liveSearchResults">' +
						'<div ng-repeat="result in livesearchResults">' +
							'<div ng-mousedown="livesearchSelect(result)">' +
								'<a href="">{{ result.display_name }}</a>' +
							'</div>' +
						'</div>' +
					'</div>',
		link: function (scope, element, attrs) {
			attrs.$observe('displayname', function(value) {
				scope.liveSearchName = value;
			});
			scope.liveSearchModel = attrs.liveSearch;
			scope.liveSearchTable = attrs.table || null;
			scope.liveSearchIndex = attrs.index || null;
			scope.livesearchResults = false;
			scope.liveSearchId = null;

			element.find('input').bind('keyup', function($event) {
				if ($event.keyCode !== 37 && $event.keyCode !== 39) {
					scope.liveSearchId = null;
					if (scope.liveSearchName.length >= 2) {
						RestData2().liveSearch({
							type:	'vendors',
							search:	scope.liveSearchName
						},
						function(resp) {
							if (resp.success === 1) {
								scope.livesearchResults = (resp.data.result[0]) ? resp.data.result: false;
								scope.liveSearchId = null;
							} else {
								// ERROR
								scope.livesearchResults = false;
							}
						},
						function(err) {
							scope.livesearchResults = false;
						});
					} else {
						scope.livesearchResults = false;
					}
				}
			});

			element.find('input').bind('blur', function($event) {
				scope.livesearchResults = false;
				// give the selected result or a new name to the parent
				scope.$emit('liveSearchBlur', {
						id:		scope.liveSearchId,
						name:	scope.liveSearchName,
						model:	scope.liveSearchModel,
						table:	scope.liveSearchTable,
						index:	scope.liveSearchIndex
					});
			});

			element.find('input').bind('focus', function($event) {
				scope.livesearchResults = false;
			});

			scope.livesearchSelect = function(result) {
				scope.liveSearchId = result.id;
				scope.liveSearchName = result.display_name;
				scope.livesearchResults = false;
				// give the selected result to the parent
				scope.$emit('liveSearchSelect', {
					result: result,
					table:	scope.liveSearchTable,
					model:	scope.liveSearchModel,
					index:	scope.liveSearchIndex
				});
			};

		}
	};
}]);