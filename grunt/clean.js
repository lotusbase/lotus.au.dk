module.exports = {
    all: [
    	"build/www/*",
    	"build/www/**/*.*",
    	"!build/www/vendor",
    	"!build/www/vendor/**/*.*",
    	"!build/www/config.ini",
    	"!build/www/.htaccess"
    	]
};