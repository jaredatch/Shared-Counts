/**
 * Load required gulp packages.
 */
var colors          = require('ansi-colors'),
	log             = require('fancy-log'),
	gulp            = require('gulp'),
	cached          = require('gulp-cached'),
	debug           = require('gulp-debug'),
	filter          = require('gulp-filter'),
	gulpif          = require('gulp-if'),
	rename          = require('gulp-rename'),
	sass            = require('gulp-sass'),
	sassInheritance = require('gulp-sass-inheritance'),
	sourcemaps      = require('gulp-sourcemaps'),
	uglify          = require('gulp-uglify'),
	watch           = require('gulp-watch'),
	wpPot           = require('gulp-wp-pot'),
	zip             = require('gulp-zip'),
	runSequence     = require('run-sequence');

var plugin = {
	name: 'Shared Counts',
	slug: 'shared-counts',
	files: [
		'**',
		'!assets/scss/**',
		'!assets/scss',
		'!build/**',
		'!build',
		'!gulpfile.js',
		'!**/node_modules/**',
		'!**/node_modules',
		'!package-lock.json',
		'!package.json'
	],
	php: '**/*.php',
	sass: [
		'assets/scss/**/*.scss'
	],
	js: [
		'assets/js/*.js',
		'!assets/js/*.min.js'
	]
};

/**
 * Task: process-sass.
 *
 * Compiles sass into CSS files and includes source maps.
 * Additionally creates minified versions.
 *
 * Only runs on updates files or its dependants.
 */
gulp.task('process-sass', function() {
	log();
	log(
		colors.gray('====== ') +
		colors.white(colors.bold('Processing .scss files')) +
		colors.gray(' ======')
	);

	return gulp.src(plugin.sass)
		// UnMinified file.
		.pipe(gulpif(global.isWatching, cached('processSass')))
		.pipe(sassInheritance({dir: 'assets/scss/'}))
		.pipe(filter(function(file) {
			return !/\/_/.test(file.path) || !/^_/.test(file.relative);
		}))
		.pipe(sourcemaps.init())
		.pipe(sass({outputStyle: 'expanded'})
			.on('error',sass.logError))
		.pipe(rename(function(path){
			path.dirname = '/assets/css';
			path.extname = '.css';
		}))
		.pipe(sourcemaps.write())
		.pipe(gulp.dest('.'))
		.pipe(debug({title: '[sass] Compiled'}))
		// Minified file.
		.pipe(sass({outputStyle: 'compressed'})
			.on('error',sass.logError))
		.pipe(rename(function(path){
			path.dirname = '/assets/css';
			path.extname = '.min.css';
		}))
		.pipe(gulp.dest('.'))
		.pipe(debug({title: '[sass] Minified'}));
});

/**
 * Task: process-js.
 *
 * Minifies the JS.
 *
 * Only runs on updates files.
 */
gulp.task('process-js', function() {
	log();
	log(
		colors.gray('====== ') +
		colors.white(colors.bold('Processing .js files')) +
		colors.gray(' ======')
	);

	return gulp.src(plugin.js)
		.pipe(cached('processJS'))
		.pipe(uglify())
			.on('error', log.error)
		.pipe(rename(function(path){
			path.dirname += '/assets/js';
			path.basename += '.min';
		}))
		.pipe(gulp.dest('.'))
		.pipe(debug({title: '[js] Minified'}));
});

/**
 * Task: process-pot.
 *
 * Generate a .pot file.
 */
gulp.task('process-pot', function() {
	log();
	log(
		colors.gray('====== ') +
		colors.white(colors.bold('Generating a .pot file')) +
		colors.gray(' ======')
	);

	return gulp.src(plugin.php)
		.pipe(wpPot( {
			domain: plugin.slug,
			package: plugin.name,
			team: 'WPForms <support@wpforms.com>'
		} ))
		.pipe(gulp.dest('languages/'+plugin.slug+'.pot'))
		.pipe(debug({title: '[pot] Generated'}));
});

/**
 * Task: process-pot.
 *
 * Generate a .zip file.
 */
gulp.task('process-zip', function() {
	log();
	log(
		colors.gray('====== ') +
		colors.white(colors.bold('Generating a .zip file')) +
		colors.gray(' ======')
	);

	// Modifying 'base' to include plugin directory in a zip.
	return gulp.src(plugin.files, {base: '../'})
		.pipe(zip(plugin.slug + '.zip'))
		.pipe(gulp.dest('./build'))
		.pipe(debug({title: '[zip] Generated'}));
});

/**
 * Task: build.
 *
 * Build a plugin by processing all required files.
 */
gulp.task('build', function() {
	runSequence('process-sass', 'process-js', 'process-pot', 'process-zip');
});

/**
 * Task: watch.
 *
 * Look out for relevant sass/js changes.
 */
gulp.task('watch', function() {
	global.isWatching = true;
	gulp.watch(plugin.sass, ['process-sass']);
	gulp.watch(plugin.js, ['process-js']);
});

/**
 * Default.
 */
gulp.task('default', function(callback) {
	runSequence('process-sass','process-js', callback);
});
