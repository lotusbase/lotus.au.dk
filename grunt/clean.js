module.exports = {
    all: [
    	"build/*",
    	"build/**/*.*",
    	"!build/www/vendor",
    	"!build/www/vendor/**/*.*",
    	"!build/www/config.php",
    	"!build/www/config.ini"
    	]
};