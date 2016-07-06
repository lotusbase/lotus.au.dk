module.exports = {
    options: {
        sourceMap: true,
        unused: false
    },
    all: {
        files: [{
            expand: true,
            cwd: 'build/src/js',
            src: [
            	'**/*.js',
            	'!**/*.min.js'
            ],
            dest: 'build/dist/js',
            ext: '.min.js'
        }]
    }
};