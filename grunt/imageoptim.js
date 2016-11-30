module.exports = {
    all: {
    	options: {
    		jpegMini: true,
    		imageAlpha: true,
    		quitAfter: false
    	},
        files: [{
            expand: true,
            cwd: 'build/www/dist/images',
            src: '**/*.{png,jpg,jpeg,gif}',
        }]
    }
};