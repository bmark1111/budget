'use strict';

app.controller('SettingsController', function($scope, $localStorage, RestData2, Periods) {

	var self = this;
	
	this.settings = false;
	var saved_values = [];

	$scope.minDate = null;
	$scope.maxDate = null;
	$scope.status = {
		opened: false
	}

	$scope.dataErrorMsg = [];
	this.validation = {};

//	ngProgress.start();

	RestData2().getSettings(
		function(response) {
			if (!!response.success) {
				self.settings = response.data.settings;
				angular.forEach(self.settings,
					function(setting, index) {
						switch (setting.type) {
							case '0':
								saved_values.push(setting.value);
								break;
							case '1':
								var selected = 0;
								var options = setting.options.split(',');
								setting.options = [];
								angular.forEach(options,
									function(option, index) {
										setting.options.push({name: option});
										if (option === setting.value) {
											selected = index;
										}
									});
								setting.value = setting.options[selected];
								saved_values.push(setting.value.name);
								break;
							case '2':
								var dt = setting.value.split('-');
								setting.value = new Date(dt[0], --dt[1], dt[2]);
								saved_values.push(setting.value);
								break;
						}
					});
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
//			ngProgress.complete();
		});

	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.status.opened = true;
	};

	// save edited settings
	$scope.save = function () {
		$scope.dataErrorMsg = [];

		self.validation = {};

		RestData2().saveSettings({settings: self.settings},
			function(response) {
				if (!!response.success) {
					// now update the global intervals data
					$scope.dataErrorMsg.push('Settings have been saved');
					angular.forEach(self.settings,
						function(setting, index) {
							switch (setting.type) {
								case '0':
									if(setting.value !== saved_values[index]) {
										Periods.clear();
									}
									break;
								case '1':
									if(setting.value.name !== saved_values[index]) {
										Periods.clear();
										switch (setting.name) {
											case 'budget_views':
												$localStorage.budget_views	= parseInt((setting.value.name * 2) + 2);
												break;
											case 'sheet_views':
												$localStorage.sheet_views	= parseInt(setting.value.name * 2);
												break;
											case 'budget_mode':
												$localStorage.budget_mode	= setting.value.name;
												break;
										}
									}
									break;
								case '2':
									if(setting.value !== saved_values[index]) {
										Periods.clear();
										$localStorage.budget_start_date	= setting.value;
									}
									break;
							}
						});
				} else if (response.validation) {
					self.validation.settings = {};
					angular.forEach(response.validation,
						function(validation) {
							if (validation.fieldName.substr(0,8) == 'settings') {
								var fieldName = validation.fieldName;
								var matches = fieldName.match(/\[(.*?)\]/g);
								if (matches) {
									for (var x = 0; x < matches.length; x++) {
										matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
									}
									if (typeof self.validation.settings[matches[1]] == 'undefined') {
										self.validation.settings[matches[1]] = Array();
									}
									self.validation.settings[matches[1]].push(validation.errorMessage);
								} else {
									self.validation[fieldName] = validation.errorMessage;
								}
							}
						});
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

});