module.exports = {
    all: {
    	files: [{
    		expand: true,
    		cwd: 'build/www/',
    		src: [
            	'**/*.php'
            ],
            dest: 'build/www/',
    	}]
    }
};