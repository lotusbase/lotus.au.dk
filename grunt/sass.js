module.exports = {
    // Development settings
    dev: {
        options: {
            outputStyle: 'nested',
            sourceMap: true
        },
        files: [{
            expand: true,
            cwd: 'build/src/css',
            src: ['*.scss'],
            dest: 'build/dist/css',
            ext: '.css'
        }]
    },

    // Production settings
    prod: {
        options: {
            outputStyle: 'nested',
            sourceMap: true
        },
        files: [{
            expand: true,
            cwd: 'build/src/css',
            src: ['*.scss'],
            dest: 'build/dist/css',
            ext: '.min.css'
        }]
    },

    // Jekyll AMP styles
    jekyll_amp: {
        options: {
            outputStyle: 'nested',
            sourceMap: false
        },
        files: [{
            expand: true,
            cwd: 'src/blog/_includes/amp',
            src: ['*.scss'],
            dest: 'src/blog/_includes/amp',
            ext: '.min.css'
        }]
    }
};