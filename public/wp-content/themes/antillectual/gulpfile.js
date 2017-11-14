/*
|--------------------------------------------------------------------------
| Gulpfile
|--------------------------------------------------------------------------
|
| Any automated tasks for this theme are specified here.
|
| @author       Lars Dol <lars@digitalnatives.nl>
| @author       Ezra Pool <ezra@digitalnatives.nl>
| @copyright (c), 2015 Digital Natives
*/

'use strict';

var gulp = require('gulp'),
    gutil = require('gulp-util'),
    gulpif = require('gulp-if'),
    less = require('gulp-less'),
    plumber = require('gulp-plumber'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    LessPluginCleanCSS = require('less-plugin-clean-css'),
    LessPluginGlob = require('less-plugin-glob'),
    cleancss = new LessPluginCleanCSS({ advanced: true }),
    autoprefixer = require('gulp-autoprefixer'),
    source = require('vinyl-source-stream'),
    buffer = require('vinyl-buffer'),
    sourcemaps = require('gulp-sourcemaps'),
    csslint = require('gulp-csslint'),
    browserify = require('browserify'),
    watchify = require('watchify'),
    assign = require('lodash').assign,
    browserSync = require('browser-sync').create(),
    styleguide = require('sc5-styleguide'),
    babelify = require('babelify'),
    eslint = require('gulp-eslint');


/* ==========================================================================
   Configuration
   ========================================================================== */

var config = {
    assetDir: 'assets',
    debug: false
};


/* ==========================================================================
   Main task. Run this by entering 'gulp' in the root of the theme (terminal).
   ========================================================================== */

gulp.task('watch', ['less-theme', /*'less-wp-admin',*/ 'watchify', /*'styleguide'*/], function () {
    gulp.watch(config.assetDir + '/styles/theme/**/*.less', ['less-theme', /*'styleguide'*/]);
    // gulp.watch(config.assetDir + '/styles/wp-admin/**/*.less', ['less-wp-admin']);
    gulp.watch( '**/*.php').on('change', browserSync.reload);
});

gulp.task('default', ['browser-sync', 'watch']);

gulp.task('disable-config', function () {
   config.debug = false;
});
gulp.task('less', ['less-theme', /*'less-wp-admin'*/]);
gulp.task('build', ['disable-config', 'browserify', 'less']);

/* ==========================================================================
   Error handling
   ========================================================================== */

function onError(e) {
    gutil.beep();
    gutil.log(e);
    this.emit('end');
}

function onBrowserifyError(err) {
    gutil.beep();
    gutil.log(err.toString());
    this.emit('end');
}

/* ==========================================================================
   LESS
   ========================================================================== */
gulp.task('less-theme', function () {
    return gulp
        .src(config.assetDir + '/styles/theme/theme.less')
        .pipe(plumber(onError))
        .pipe(sourcemaps.init())
        .pipe(less({
            plugins: [LessPluginGlob, cleancss]
        }))
        .pipe(autoprefixer({
            browsers: ['last 2 versions', 'IE > 8'],
            cascade: false
        }))
        .pipe(rename({basename: 'theme'}))
        .pipe(sourcemaps.write('', {addComment: config.debug, debug:true}))
        .pipe(plumber.stop())
        .pipe(gulp.dest('dist/css/'))
        .pipe(browserSync.stream());
});

// gulp.task('less-wp-admin', function () {
//     return gulp
//         .src(config.assetDir + '/styles/wp-admin/wp-admin.less')
//         .pipe(plumber(onError))
//         .pipe(sourcemaps.init())
//         .pipe(less({
//             plugins: [LessPluginGlob, cleancss]
//         }))
//         .pipe(autoprefixer({
//             browsers: ['last 2 versions', 'IE > 8'],
//             cascade: false
//         }))
//         .pipe(rename({basename: 'wp-admin'}))
//         .pipe(sourcemaps.write('', {addComment: config.debug, debug:true}))
//         .pipe(plumber.stop())
//         .pipe(gulp.dest('dist/css/'))
//         .pipe(browserSync.stream());
// });


/* ==========================================================================
   JavaScript (Browserify & Watchify)
   ========================================================================== */

gulp.task('watchify', function () {
    var opts = assign({}, watchify.args, {
        debug: config.debug,
        entries: [config.assetDir + '/js/theme/theme.js']
    });
    var watcher = watchify(browserify(opts)
        .transform(babelify.configure({
            presets: ['es2015']
        }))
    );

    function bundle() {
        return watcher.bundle()
            .on('error', onBrowserifyError)
            .pipe(source('theme.js'))
            .pipe(buffer())
            .pipe(gulpif(config.debug, sourcemaps.init({loadMaps: true})))
            .pipe(gulpif(!config.debug, uglify()))
            .pipe(rename({basename: 'theme'}))
            .pipe(gulpif(config.debug, sourcemaps.write('.')))
            .pipe(gulp.dest('dist/js/'))
            .pipe(browserSync.stream());
    }

    bundle();

    watcher.on('update', bundle);
    watcher.on('log', gutil.log);
});

gulp.task('browserify', function () {
    var opts = assign({}, watchify.args, {
        debug: config.debug,
        entries: [config.assetDir + '/js/theme/theme.js']
    });
    var b = browserify(opts)
        .transform(babelify.configure({
            presets: ['es2015']
        }));

    return b.bundle()
        .on('error', onBrowserifyError)
        .pipe(source('theme.js'))
        .pipe(buffer())
        .pipe(gulpif(config.debug, sourcemaps.init({loadMaps: true})))
        .pipe(gulpif(!config.debug, uglify()))
        .pipe(rename({basename: 'theme'}))
        .pipe(gulpif(config.debug, sourcemaps.write('.')))
        .pipe(gulp.dest('dist/js/'))
        .pipe(browserSync.stream());
});

gulp.task('lint', function() {
    return gulp.src([config.assetDir + '/js/theme/**/*.js','!node_modules/**'])
        .pipe(eslint())
        .pipe(eslint.format())
        .pipe(eslint.failOnError());
});

/* ==========================================================================
   Browser Sync
   ========================================================================== */

gulp.task('browser-sync', function() {
    browserSync.init({
        proxy: 'https://antillectual.dev'
    });
});
