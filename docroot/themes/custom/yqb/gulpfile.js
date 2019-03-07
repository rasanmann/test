<<<<<<< HEAD
'use strict';

const gulp = require('gulp'),
  sass = require('gulp-sass'),
  autoprefixer = require('gulp-autoprefixer'),
  cleanCSS = require('gulp-clean-css'),
  sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function () {
  return gulp.src('./scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer({
      browsers: ['> 1%']
    }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./css'));
});

gulp.task('watch', function () {
  gulp.watch('./scss/**/*.scss', ['sass']);
});

gulp.task('default', ['sass', 'watch']);
=======
const {parallel, watch} = require('gulp'),
  sass = require('gulp-sass'),
  gulp = require("gulp"),
  autoprefixer = require('gulp-autoprefixer');

function javascript(cb) {
  // place code for your default task here
  cb();
}

function css() {
  return (
    gulp
      .src(['scss/*.scss', 'scss/**/*.scss'])
      .pipe(sass())
      .pipe(autoprefixer({
        browsers: ['> 1%'],
        cascade: false
      }))
      .on('error', sass.logError)
      .pipe(gulp.dest('css'))
  );
}

exports.default = function() {
  watch(['scss/*.scss', 'scss/**/*.scss'], css);
};
>>>>>>> feature/90135
