module.exports = {

    options: {
        reporter: require('jshint-stylish')
    },

    main: [
        'build/www/src/js/**/*.js',
        '!build/www/src/js/**/*.min.js',
        '!build/www/src/js/plugins/**/*.js'
    ]
};