'use strict';

app.service('Config', function Config() {
	
	/**
	 * You can have as many environments as you like in here
	 * just make sure the host matches up to your hostname including port
	 */
	var _environments = {
		local: {
			host: 'budgettrackerpro.loc',
			config: {
				/**
				 * Add any config properties you want in here for this environment
				 */
				api_url: 'http://rest.budgettrackerpro.loc/:path/:object/:action/:param',
				upload_url:	'http://rest.budgettrackerpro.loc/upload/'
			}
		},
		production: {
			host: 'budgettrackerpro.com',
			config: {
				/**
				 * Add any config properties you want in here for this environment
				 */
				api_url: 'http://rest.budgettrackerpro.com/:path/:object/:action/:param',
				upload_url:	'http://rest.budgettrackerpro.com/upload/'
			}
//		},
//		test: {
//			host: 'test.com',
//			config: {
//				/**
//				 * Add any config properties you want in here for this environment
//				 */
//				apiroot: 'http://eventphoto.dev/app_dev.php'
//			}
//		},
//		jsfiddle: {
//			host: 'jsfiddle.net',
//			config: {
//				/**
//				 * Add any config properties you want in here for this environment
//				 */
//				apiroot: 'HELLO!'   
//			}
//		},
//		stage: {
//			host: 'stage.com',
//			config: {
//				/**
//				 * Add any config properties you want in here for this environment
//				 */
//				apiroot: 'http://eventphoto.dev/app_dev.php'
//			}
//		},
//		prod: {
//			host: 'production.com',
//			config: {
//				/**
//				 * Add any config properties you want in here for this environment
//				 */
//				apiroot: 'http://eventphoto.dev/app_dev.php'
//			}
		}
	},
	_environment;

	return {
		getEnvironment: function(){
			var host = window.location.host;

			if(_environment){
				return _environment;
			}

			for(var environment in _environments){
				if(typeof _environments[environment].host && _environments[environment].host == host){
					_environment = environment;
					return _environment;
				}
			}

			return null;
		},
		get: function(property){
			return _environments[this.getEnvironment()].config[property];
		}
	}

});