module.exports = {
    options: {
        src: 'src/blog',
        serve: false
    },

    main: {
    	options: {
    		dest: 'build/www/blog',
        	config: 'src/blog/_config.yml'
    	}
    }
};