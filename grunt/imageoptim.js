module.exports = {
    all: {
    	options: {
    		jpegMini: true,
    		imageAlpha: true,
    		quitAfter: false
    	},
        files: [{
            expand: true,
            cwd: 'dist/images',
            src: '**/*.{png,jpg,jpeg,gif}',
        }]
    }
};