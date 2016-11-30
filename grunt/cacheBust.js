module.exports = {
    // Development settings
    dev: {
        options: {
            assets: ['dist/css/**/*.min.css', 'dist/js/**/*.min.js'],
            queryString: true,
            baseDir: 'build/www/',
            jsonOutput: true,
            jsonOutputFilename: 'grunt-cache-bust.json'
        },
        files: [{
            cwd: 'build/www/',
            expand: true,
            src: [
                '**/*.php',
                '**/*.html',
                '!vendor/**/*.php',
                '!vendor/**/*.html',
                '!lib/**/*.php',
                '!_*.*'
            ]
        }]
    },
    // Production settings
    prod: {
        options: {
            assets: ['dist/css/**/*.min.css', 'dist/js/**/*.min.js'],
            queryString: true,
            baseDir: 'build/www/'
        },
        files: [{
            cwd: 'build/www/',
            expand: true,
            src: [
                '**/*.php',
                '**/*.html',
                '!vendor/**/*.php',
                '!vendor/**/*.html',
                '!lib/**/*.php',
                '!_*.*'
            ]
        }]
    }
};