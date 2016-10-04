module.exports = {

    options: {
        spawn: false,
        livereload: true
    },

    scripts: {
        files: [
            'src/js/**/*.js',
        ],
        tasks: [
            'newer:copy',
            'newer:jshint',
            'newer:uglify'
        ]
    },

    styles: {
        files: [
            'src/css/**/*.css',
            'src/css/**/*.scss'
        ],
        tasks: [
            'newer:copy',
            'sass:prod',
            'postcss:prod'
        ]
    },

    templates: {
        files: [
            'src/templates/**/*.php',
            'src/templates/**/*.ini',
            'src/templates/**/*.py',
            'src/templates/data/**/*.*'
        ],
        tasks: [
            'newer:copy'
        ]
    }
};