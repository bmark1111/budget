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
		localwww: {
			host: 'www.budgettrackerpro.loc',
			config: {
				/**
				 * Add any config properties you want in here for this environment
				 */
				api_url: 'http://rest.budgettrackerpro.loc/:path/:object/:action/:param',
				upload_url:	'http://rest.budgettrackerpro.loc/upload/'
			}
		},
		test: {
			host: 'dev.budgettrackerpro.loc',
			config: {
				/**
				 * Add any config properties you want in here for this environment
				 */
				api_url: 'http://rest.budgettrackerpro.com/:path/:object/:action/:param',
				upload_url:	'http://rest.budgettrackerpro.com/upload/'
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
		},
		productionwww: {
			host: 'www.budgettrackerpro.com',
			config: {
				/**
				 * Add any config properties you want in here for this environment
				 */
				api_url: 'http://rest.budgettrackerpro.com/:path/:object/:action/:param',
				upload_url:	'http://rest.budgettrackerpro.com/upload/'
			}
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