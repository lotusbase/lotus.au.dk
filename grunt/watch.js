module.exports = {

    options: {
        spawn: false,
        livereload: true,
        interrupt: true
    },

    scripts: {
        files: [
            'src/js/**/*.js',
            'src/templates/admin/includes/**/*.js'
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

    jekyll_amp_styles: {
        files: [
            'src/blog/_includes/**/*.scss'
        ],
        tasks: [
            'sass:jekyll_amp',
            'postcss:jekyll_amp'
        ]
    },

    templates: {
        files: [
            'src/templates/**/*.ini',
            'src/templates/**/*.pl',
            'src/templates/**/*.py',
            'src/templates/**/*.sh',
            'src/templates/data/**/*.*',
            'src/gatekeeper/**/*.*',
            'src/templates/lib/**/*.js'
        ],
        tasks: [
            'newer:copy'
        ]
    },

    phpTemplates: {
        files: [
            'src/templates/**/*.php'
        ],
        tasks: [
            'newer:copy',
            'newer:removeHtmlComments',
            'newer:cacheBust:prod'
        ]
    },

    blog: {
        files: [
            'src/blog/**/*.*',
            '!src/blog/_includes/**/*.scss'
        ],
        tasks: [
            'jekyll'
        ]
    }
};