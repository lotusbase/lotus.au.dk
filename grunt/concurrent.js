module.exports = {

    // Task options
    options: {
        limit: 4
    },

    // Dev tasks
    devFirst: [
        'copy',
        'jshint'
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
        'jshint'
    ],
    prodSecond: [
        'sass:prod',
        'cssmin',
        'uglify'
    ],
    prodThird: [
        'postcss:prod'
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
