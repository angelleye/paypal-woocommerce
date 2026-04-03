module.exports = function ( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		cssmin: {
			target: {
				files: {
					'ppcp-gateway/css/angelleye-ppcp-gateway-admin.min.css': 'ppcp-gateway/css/angelleye-ppcp-gateway-admin.css',
					'ppcp-gateway/css/wc-gateway-ppcp-angelleye-public.min.css': 'ppcp-gateway/css/wc-gateway-ppcp-angelleye-public.css',
				},
			},
		},
		uglify: {
			build: {
				files: {
					'ppcp-gateway/js/pay-later-messaging.min.js': 'ppcp-gateway/js/pay-later-messaging.js',
					'ppcp-gateway/js/wc-angelleye-common-functions.min.js': 'ppcp-gateway/js/wc-angelleye-common-functions.js',
					'ppcp-gateway/js/wc-gateway-ppcp-add-payment-method.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-add-payment-method.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-apple-pay.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-apple-pay.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-google-pay.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-google-pay.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-capture.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-order-capture.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-public.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-public.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings-list.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings-list.js',
					'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings.min.js': 'ppcp-gateway/js/wc-gateway-ppcp-angelleye-settings.js',
				},
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.registerTask( 'default', [ 'uglify', 'cssmin' ] );
};
