module.exports = {

    options: {
        reporter: require('jshint-stylish')
    },

    main: [
        'build/src/js/*.js',
        '!build/src/js/*.min.js'
    ]
};