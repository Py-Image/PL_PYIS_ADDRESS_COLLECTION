var $             = require( 'gulp-load-plugins' )();
var config        = require( '../util/loadConfig' ).javascript;
var gulp          = require( 'gulp' );
var foreach       = require( 'gulp-foreach' );
var sequence      = require( 'run-sequence' );
var notify        = require( 'gulp-notify' );
var fs            = require( 'fs' );
var pkg           = JSON.parse( fs.readFileSync( './package.json' ) );
var onError       = notify.onError( {
   title:    pkg.name,
   message:  '<%= error.name %> <%= error.message %>'   
} );

gulp.task( 'front-uglify', function() {

    return gulp.src( config.front.src )
        .pipe( $.plumber( { errorHandler: onError } ) )
        .pipe( $.sourcemaps.init() )
        .pipe( $.babel() )
        .pipe( $.concat( config.front.filename ) )
        .pipe( $.uglify() )
        .pipe( $.sourcemaps.write( '.' ) )
        .pipe( gulp.dest( config.front.root ) )
        .pipe( $.plumber.stop() )
        .pipe( notify( {
            title: pkg.name,
            message: 'JS Complete'
        } ) );

} );

gulp.task( 'admin-uglify', function() {

    return gulp.src( config.admin.bowerPaths.concat( config.admin.src ) )
        .pipe( $.plumber( { errorHandler: onError } ) )
        .pipe( $.sourcemaps.init() )
        .pipe( $.babel() )
        .pipe( $.concat( config.admin.filename ) )
        .pipe( $.uglify() )
        .pipe( $.sourcemaps.write( '.' ) )
        .pipe( gulp.dest( config.admin.root ) )
        .pipe( $.plumber.stop() )
        .pipe( notify( {
            title: pkg.name,
            message: 'Admin JS Complete'
        } ) );

} );

gulp.task( 'tinymce-uglify', function() {

    return gulp.src( config.tinymce.src )
        .pipe( foreach( function( stream, file ) {
            return stream
                .pipe( $.plumber( { errorHandler: onError } ) )
                .pipe( $.babel() )
                .pipe( $.uglify() )
                .pipe( gulp.dest( config.tinymce.root ) )
                .pipe( $.plumber.stop() )
        } ) )
        .pipe( notify( {
            title: pkg.name,
            message: 'TinyMCE JS Complete'
        } ) );

} );

gulp.task( 'uglify', ['front-uglify', 'admin-uglify', 'tinymce-uglify'], function( done ) {
} );