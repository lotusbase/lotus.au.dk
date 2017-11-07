module.exports = {
    options: {
        sourceMap: true,
        unused: false
    },
    all: {
        files: [{
            expand: true,
            cwd: 'build/www/src/js',
            src: [
            	'**/*.js',
            	'!**/*.min.js'
            ],
            dest: 'build/www/dist/js',
            ext: '.min.js'
        }, {
            expand: true,
            cwd: 'build/www/admin/includes',
            src: [
                '**/*.js',
                '!**/*.min.js'
            ],
            dest: 'build/www/admin/includes',
            ext: '.min.js'
        }]
    }
};