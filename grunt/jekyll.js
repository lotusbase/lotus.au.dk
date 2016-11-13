module.exports = {
    options: {
        src: 'src/blog',
        serve: false
    },

    main: {
    	options: {
    		 dest: 'build/blog',
        	config: 'src/blog/_config.yml'
    	}
    }
};