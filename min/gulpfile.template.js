/**
 * Created by jack on 08.11.2015.
 */

var gulp = require('gulp')
    , minifyCss = require("gulp-minify-css")
    , concat = require("gulp-concat")
    , uglify = require("gulp-uglify")
    ;

// CSS
gulp.task('styles', function () {
    gulp.src([
	{%css%}
    ]).pipe(concat('./assets/_/{%id%}.css'))
        .pipe(minifyCss())
        .pipe(gulp.dest('.'));
});


// JS
gulp.task('scripts', function () {
    	gulp
	.src([{%js%}])
	.pipe(concat('./assets/_/{%id%}.js'))
        .pipe(uglify())
        .pipe(gulp.dest('.'));
});

gulp.task('default', function() {
	gulp.run('scripts', 'styles');
});