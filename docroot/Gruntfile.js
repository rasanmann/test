/*global module:false*/
module.exports = function (grunt) {

	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' +
		'<%= grunt.template.today("yyyy-mm-dd") %>\n' +
		'<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
		'* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' +
		' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n',
		drush:{
			cr:{
				args:['cache-rebuild']
			},
			cc_theme_registry: {
				args: ['cc', 'theme-registry']
			},
			cc_router: {
				args: ['cc', 'router']
			},
			cc_css_js: {
				args: ['cc', 'css-js']
			},
			cc_module_list: {
				args: ['cc', 'module-list']
			},
			cc_theme_list: {
				args: ['cc', 'theme-list']
			},
			cc_render: {
				args: ['cc', 'render']
			},
			cc_views: {
				args: ['cc', 'views']
			},
		},
		imagemin: {
			icons: {
				files: [
					{
						expand: true,
						cwd: './themes/custom/yqb/svg',
						src: [
							'*.svg',
							'!**/min/*.svg' // Except those already minified,
						],
						dest: './themes/custom/yqb/svg/min/'
					}
				]
			}
		},
		grunticon: {
		    icons: {
		        files: [{
		            expand: true,
		            cwd: './themes/custom/yqb/svg/min',
			        src: ['*.svg'],
			        dest: './themes/custom/yqb/dist/output'
		        }],
		        options: {
			        enhanceSVG: true,
			        loadersnippet: "grunticon.loader.js"
		        }
		    }
		},
		watch: {
			// Reloads page when stylesheets, script or templates are changed
			frontend: {
				options: { livereload:true },
				files: [
					'themes/**/*.css',
					'themes/**/*.js',
					'themes/**/*.html.twig'
				],
				tasks:['drush:cc_css_js']
			},
			// Reloads page when modules are changed
			backend: {
				options: { livereload:true },
				files: [
					"modules/**/*.php",
					"modules/**/*.module"
				]
			},
			// Clear theme registry cache
			templates:{
				files: [
					'themes/**/*.html.twig',
					'themes/**/*.theme'
				],
				tasks:['drush:cc_theme_registry']
			},
			// Clear router cache
			routes:{
				files: ['modules/**/*.yml',],
				tasks:['drush:cc_router']
			}
		},
		exec:{
			ngrok: {
				command: 'ngrok http 8080',
				stdout: false,
				stderr: false
			}
		}
	});

	require('load-grunt-tasks')(grunt);


	// Grunticons
	grunt.registerTask('default', [
		'newer:imagemin:icons',
		'grunticon:icons'
	]);

	// Grunticons
	grunt.registerTask('ngrok', [
		'exec:ngrok'
	]);

};
