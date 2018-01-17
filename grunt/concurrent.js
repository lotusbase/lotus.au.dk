module.exports = {

    // Task options
    options: {
        limit: 4
    },

    // Dev tasks
    devFirst: [
        'copy',
        'jshint',
        'jekyll'
    ],
    devSecond: [
        'sass:dev',
        'uglify'
    ],
    devThird: [
        'postcss:dev'
    ],

    // Production tasks
    prodFirst: [
        'copy',
        'jshint',
        'jekyll'
    ],
    prodSecond: [
        'sass:prod',
        'cssmin',
        'uglify'
    ],
    prodThird: [
        'postcss:prod',
        'removeHtmlComments'
    ],

    // Image tasks
    imgFirst: [
        'imagemin'
    ],

    // Copy tasks
    fileCopy: [
        'copy'
    ]
};
