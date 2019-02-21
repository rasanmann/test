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
      .pipe(gulp.dest('../css'))
  );
}

exports.default = function() {
  watch(['scss/*.scss', 'scss/**/*.scss'], css);
};
