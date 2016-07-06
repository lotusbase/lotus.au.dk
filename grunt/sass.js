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
    }
};