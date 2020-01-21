// 'use strict';

const {parallel, watch} = require('gulp'),
  sass = require('gulp-sass'),
  gulp = require("gulp"),
  autoprefixer = require('gulp-autoprefixer');


function css() {
  return (
    gulp
      .src(['scss/*.scss', 'scss/**/*.scss'])
      .pipe(sass())
      .pipe(autoprefixer())
      .on('error', sass.logError)
      .pipe(gulp.dest('css'))
  );
}

exports.default = function() {
    css();
  watch(['scss/*.scss', 'scss/**/*.scss'], css);
};
