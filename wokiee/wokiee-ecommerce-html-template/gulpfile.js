const gulp = require('gulp');
const concat = require('gulp-concat');
const autoprefixer = require('gulp-autoprefixer');
const terser = require('gulp-terser');
const del = require('del');
const browserSync = require('browser-sync').create();
const plumber = require('gulp-plumber');
const notify = require("gulp-notify");
const fileinclude = require('gulp-file-include');
const replace = require('gulp-replace');
const htmlmin = require('gulp-htmlmin');
const cleanCSS = require('gulp-clean-css');
const dartSass =  require('sass');
const gulpSass = require('gulp-sass');
const sass = gulpSass(dartSass);
/*Remove Folder*/
function clean(){
	return del(['build/*'])
}
/*File Transfer*/
function fileTransfer() {
	return gulp.src(['./src/**/*', '!./src/js/**/*', '!./src/*.html'])
	.pipe(gulp.dest('./build/'))
	.pipe(browserSync.stream());
}
/*HTML*/
function html(){
	return gulp.src('./src/*.html')
	.pipe(plumber({errorHandler: notify.onError("Error: <%= error.message %>")}))
	.pipe(fileinclude({
		prefix: '@@',
		basepath: '@file'
	}))
	.pipe(gulp.dest('./build/'))
	.pipe(browserSync.stream());
}
function htmlMin(){
	return gulp.src('./build/*.html')
	.pipe(htmlmin({ collapseWhitespace: true }))
	.pipe(gulp.dest('./build/'));
}
/*CSS*/
const cssFiles = [
	'./src/scss/style.scss',
		// './src/scss/rtl.scss',
		// './src/scss/style-skin-snowboards.scss',
		// './src/scss/style-skin-phones.scss',
		// './src/scss/style-skin-lingerie.scss',
		// './src/scss/style-skin-furniture.scss',
		// './src/scss/style-skin-carsshop.scss',
		// './src/scss/style-skin-books.scss',
		// './src/scss/style-skin-bikes.scss',
		//wookie 2
		// './src/scss/style-skin-baby.scss',
		// './src/scss/style-skin-food.scss',
		// './src/scss/style-skin-food02.scss',
		// './src/scss/style-skin-oneproducts.scss',
		// './src/scss/style-skin-oneproducts02.scss',
		// './src/scss/style-skin-oneproducts03.scss',
		// './src/scss/style-skin-coffee.scss',
		// './src/scss/style-skin-clothes.scss',
		// './src/scss/index-skin-cosmetics.scss',
		// './src/scss/style-skin-electronics.scss',
		// './src/scss/style-skin-glasses.scss',
		// './src/scss/style-skin-handmade.scss',
		// './src/scss/style-skin-shirts.scss',
		// './src/scss/style-skin-cookware.scss',
		// './src/scss/style-skin-cakes.scss',
		// './src/scss/style-skin-christmas.scss',
		// './src/scss/style-skin-flowers.scss',
		// './src/scss/style-skin-furniture02.scss',
		// './src/scss/style-skin-gothic.scss',
		// './src/scss/style-skin-jewerly.scss',
		// './src/scss/style-skin-kids-clothes.scss',
		// './src/scss/style-skin-watches.scss',
		// './src/scss/style-skin-care.scss',
		// './src/scss/style-skin-beer.scss',
		// './src/scss/style-skin-books02.scss',
		// './src/scss/style-skin-bicycle.scss',
		// './src/scss/style-skin-tools.scss',
		// './src/scss/style-skin-toys.scss',
		// './src/scss/style-skin-tea.scss',
		// './src/scss/style-skin-comic-books.scss',
		// './src/scss/style-skin-wallets.scss',
		// './src/scss/style-skin-weapons.scss',
		// './src/scss/style-skin-medical.scss',
		// './src/scss/style-skin-phone-cases.scss',
		// './src/scss/style-skin-yoga.scss',
		// './src/scss/style-skin-plants.scss',
		// './src/scss/style-skin-basketball.scss',
		// './src/scss/style-skin-football.scss',
		// './src/scss/style-skin-base-ball.scss',
		// './src/scss/style-skin-lifestyle.scss',
		// './src/scss/style-skin-drones.scss',
		// './src/scss/style-skin-olivers.scss'
 ];
function css(){
	return gulp.src(cssFiles,  { allowEmpty: true })
	.pipe(plumber({errorHandler: notify.onError("Error: <%= error.message %>")}))
	.pipe(sass().on('error', sass.logError))
	.pipe(autoprefixer({
		overrideBrowserslist: ['last 1 versions'],
		cascade: false
	}))
	.pipe(gulp.dest('./build/css'))
	.pipe(gulp.dest('./src/css'))
	.pipe(browserSync.stream());
}
function cssMin(){
	return gulp.src(cssFiles,  { allowEmpty: true })
	.pipe(plumber({errorHandler: notify.onError("Error: <%= error.message %>")}))
	.pipe(sass().on('error', sass.logError))
	.pipe(autoprefixer({
		overrideBrowserslist: ['last 1 versions'],
		cascade: false
	}))
	.pipe(cleanCSS({level: 2}))
	.pipe(gulp.dest('./build/css'))
	.pipe(browserSync.stream());
}
/*Watch*/
function watch(){
	gulp.watch('./src/scss/**/*.scss', css)
	gulp.watch('./src/js/**/*.js', js)
	gulp.watch('./src/**/*.html', gulp.series(html)).on('change', browserSync.reload);
	browserSync.init({
		server:{
			baseDir: "./build/"
		}
	});
}
/*JS*/
const jsFiles = [
	'./src/external/bootstrap/js/bootstrap.min.js',
	'./src/external/slick/slick.min.js',
	'./src/external/elevatezoom/jquery.elevatezoom.js',
	'./src/external/isotope/imagesloaded.js',
	'./src/external/isotope/isotope.pkgd.min.js',
	'./src/external/magnific-popup/jquery.magnific-popup.min.js',
	'./src/external/perfect-scrollbar/perfect-scrollbar.min.js',
	'./src/external/panelmenu/panelmenu.js',
	'./src/external/instagram-feed/jquery.instagramFeed.min.js',
	'./src/external/rs-plugin/js/jquery.themepunch.tools.min.js',
	'./src/external/rs-plugin/js/jquery.themepunch.revolution.min.js',
	'./src/external/countdown/jquery.plugin.min.js',
	'./src/external/countdown/jquery.countdown.min.js',
	'./src/external/lazyLoad/lazyload.min.js',
	'./src/external/form/jquery.form.js',
	'./src/external/form/jquery.validate.min.js',
	'./src/external/form/jquery.form-init.js',
	'./src/js/**/*.js'
];
function js() {
	return gulp.src(jsFiles, { allowEmpty: true })
	.pipe(concat('bundle.js'))
	.pipe(gulp.dest('./build/js'))
	.pipe(browserSync.stream());
}
function jsMin(){
	return gulp.src(jsFiles, { allowEmpty: true })
	.pipe(concat('bundle.js'))
	.pipe(terser({
		keep_fnames: true,
		mangle: false
	}))
	.pipe(gulp.dest('./build/js'))
	.pipe(browserSync.stream());
}
function watch(){
	gulp.watch('./src/scss/**/*.scss', css)	
	gulp.watch('./src/js/**/*.js', js)
	gulp.watch('./src/**/*.html', gulp.series(html)).on('change', browserSync.reload);
	browserSync.init({
		server:{
			baseDir: "./build/"
		}
	});
}
/*Task*/
gulp.task('css', css);
gulp.task('clean', clean);
gulp.task('fileTransfer', fileTransfer);
gulp.task('html', html);
gulp.task('js', js);
gulp.task('watch', watch);
gulp.task('build', gulp.series(clean, gulp.parallel(css,js,html,fileTransfer)));
/*
	Task
	*Code minimization.
	**For website optimization
*/
gulp.task('htmlMin', htmlMin);
gulp.task('jsMin', jsMin);
gulp.task('cssMin', cssMin);
gulp.task('minAll', gulp.series(htmlMin,jsMin, cssMin));