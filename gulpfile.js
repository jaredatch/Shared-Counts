/**
 * Load required gulp packages.
 */
const colors          = require( 'ansi-colors' ),
      log             = require( 'fancy-log' ),
      gulp            = require( 'gulp' ),
      debug           = require( 'gulp-debug' ),
      rename          = require( 'gulp-rename' ),
      sass            = require( 'gulp-sass' ),
      sassInheritance = require( 'gulp-sass-inheritance' ),
      sourcemaps      = require( 'gulp-sourcemaps' ),
      uglify          = require( 'gulp-uglify' ),
      wpPot           = require( 'gulp-wp-pot' ),
      zip             = require( 'gulp-zip' );

var plugin = {
	name: 'Shared Counts',
	slug: 'shared-counts',
	files: [
		'**',
		// Exclude all the files/dirs below. Note the double negate (when ! is used inside the exclusion) - we may actually need some things.
		'!**/*.map',
		'!assets/scss/**',
		'!assets/scss',
		'!**/.github/**',
		'!**/.github',
		'!**/bin/**',
		'!**/bin',
		'!**/tests/**',
		'!**/tests',
		'!**/Test/**',
		'!**/Test',
		'!**/Tests/**',
		'!**/Tests',
		'!**/build/**',
		'!**/build',
		'!**/examples/**',
		'!**/examples',
		'!**/doc/**',
		'!**/doc',
		'!**/docs/**',
		'!**/docs',
		'!**/node_modules/**',
		'!**/node_modules',
		'!**/*.md',
		'!**/*.rst',
		'!**/*.xml',
		'!**/*.dist',
		'!**/*.json',
		'!**/*.lock',
		'!**/gulpfile.js',
		'!LICENSE', // but include licenses in the packages
		'!**/Makefile',
		'!**/AUTHORS'
	],
	php: [
		'**/*.php'
	],
	scss: [
		'assets/scss/**/*.scss'
	],
	js: [
		'assets/js/*.js',
		'!assets/js/*.min.js'
	]
};

gulp.task( 'scss', function(){
	return processScss();
} );

gulp.task( 'js', function () {
	return processJs();
} );

gulp.task( 'zip', function () {
	return processZip();
} );

gulp.task( 'pot', function () {
	return processPot();
} );

gulp.task( 'watch', function () {

	gulp.watch( plugin.scss ).on( 'change', function ( path, stats ) {
		processScss( path );
	} );

	gulp.watch( plugin.js ).on( 'change', function ( path, stats ) {
		processJs( path );
	} );
} );

gulp.task( 'build', gulp.series( 'scss', 'js', 'pot', 'zip' ) );
gulp.task( 'default', gulp.series( 'scss', 'js' ) );

/**
 * Compiles SCSS into CSS files and includes source maps.
 * Additionally creates minified versions.
 *
 * Only runs on updated files or its dependants.
 */
function processScss( path ) {
	log();
	log(
		colors.gray( '====== ' ) +
		colors.white( colors.bold( 'Processing .scss files' ) ) +
		colors.gray( ' ======' )
	);

	let files = path || plugin.scss;

	return gulp.src( files, { base: './' } )
			   // UnMinified file.
			   // Find files that depend on the files that have changed.
			   .pipe( sassInheritance( { dir: './assets/scss/' } ) )
			   .pipe( sourcemaps.init() )
			   .pipe( sass( { outputStyle: 'expanded' } )
				   .on( 'error', sass.logError ) )
			   .pipe( rename( function ( path ) {
				   path.dirname += '/../css';
				   path.extname = '.css';
			   } ) )
			   .pipe( sourcemaps.write() )
			   .pipe( gulp.dest( './' ) )
			   .pipe( debug( { title: '[sass] Compiled' } ) )
			   // Minified file.
			   .pipe( sass( { outputStyle: 'compressed' } )
				   .on( 'error', sass.logError ) )
			   .pipe( rename( function ( path ) {
				   path.dirname += '/../css';
				   path.extname = '.min.css';
			   } ) )
			   .pipe( gulp.dest( './' ) )
			   .pipe( debug( { title: '[sass] Minified' } ) );
}

/**
 * Minifies the JS.
 *
 * Only runs on updated files.
 */
function processJs( path ) {
	log();
	log(
		colors.gray( '====== ' ) +
		colors.white( colors.bold( 'Processing .js files' ) ) +
		colors.gray( ' ======' )
	);

	let files = path || plugin.js;

	return gulp.src( files, { base: './' } )
			   .pipe( uglify() )
			   .on( 'error', log.error )
			   .pipe( rename( function ( path ) {
				   path.basename += '.min';
			   } ) )
			   .pipe( gulp.dest( './' ) )
			   .pipe( debug( { title: '[js] Minified' } ) );
}

function processPot( path ) {
	log();
	log(
		colors.gray( '====== ' ) +
		colors.white( colors.bold( 'Processing .pot file' ) ) +
		colors.gray( ' ======' )
	);

	let files = path || plugin.php;

	return gulp.src( files )
			   .pipe( wpPot( {
					domain: plugin.slug,
					package: plugin.name,
					team: 'Shared Counts Team <none@none.com>'
				} ) )
			   .pipe( gulp.dest( 'languages/' + plugin.slug + '.pot' ) )
			   .pipe( debug( { title: '[pot] Generated' } ) );
}

function processZip( path ) {
	log();
	log(
		colors.gray( '====== ' ) +
		colors.white( colors.bold( 'Generating .zip file' ) ) +
		colors.gray( ' ======' )
	);

	let files = path || plugin.files;

	return gulp.src( files, { base: '../' } )
			   .pipe( zip( plugin.slug + '.zip' ) )
		       .pipe( gulp.dest( './build' ) )
			   .pipe( debug( {title: '[zip] Generated' } ) );
}