# *Lotus* Base
# v3.0.0

## Updating the Wiki
The Wiki is available as a separate repo. [Refer to the wiki for wiki instructions](https://bitbucket.org/terrymun/lotusbase-web/wiki/Home#markdown-header-wiki) (oh, recursive reference).

## Setting up the server
Please [refer to the wiki](https://bitbucket.org/terrymun/lotusbase-web/wiki/ServerSetup) for further instructions. It includes instructions on:

- Installing basic dependencies and developer tools
- Installing Apache, PHP and MySQL (basic necessities server stack)
- Installing Ruby (with rbenv)
- Installing Python and other Python libraries (SciPy and Numpy)
- Installing and configuring Apache Phusion Passenger
- Building SequenceServer
- Building jBrowse and adding reference sequences and tracks

## Dependency management
### Composer
#### Installing composer
[**Composer**](https://getcomposer.org) is used to manage some, but not all, PHP-based dependencies used by *Lotus* Base. The [installation instructions are available here](https://getcomposer.org/doc/00-intro.md).

#### Installing packages
To install packages, remember to update the `composer.json` file. The vendor directory should be set to `build/vendor`.

#### Updating packages
Installed packages can be updated by executing the following command:

```bash
$ composer update
```


## Where is everything?
### Important paths
| Directories                                               | Description                                                                              
|-----------------------------------------------------------|-------------
| `/root/.rbenv/versions/2.1.0/lib/ruby/gems/2.1.0/gems`    | Location of all installed Ruby gems.
| `/var/www/html/`                                          | Location of web resources served by Apache, specified in  `/etc/httpd/conf/httpd.conf/`.
| `/var/www/html/_modules/`                                 | Location of **modified** third party module files that allows deep integrated with *Lotus* Base. See [wiki for notes](https://bitbucket.org/terrymun/lotusbase-web/wiki/Update).
| `/var/www/html/src/`                                      | Location of source files used by [Grunt](#markdown-header-building-with-grunt) for building distribution-ready files.

### Important configuration files
The (non-exhaustive) list of configuration files important for *Lotus* Base to work:

| File path                                                 | Description
|-----------------------------------------------------------|------------
| `/etc/httpd/conf/httpd.conf`                              | The Apache configuration file.
| `/var/www/html/_sample.config.php`                        | Defines important PHP constants to be used by all PHP files. It reads the configurations from **config.ini** (see below). Rename to **config.php** for it to work.
| `/var/www/html/_sample.config.ini`                        | Defines site-wide settings for absolute paths, URLs and MySQL connections. Rename to **config.ini** for it to work.

### Site resources
Resources that are served with *Lotus* Base, i.e. CSS and JS files, images and fonts, are located in the `src/` directory. You will need to install and run `grunt` in order to build them into a usable form before deployment. Please refer to [building with Grunt](#markdown-header-building-with-grunt).

### Code libraries
All server-side libraries (Python scripts, syntax highlighting and etc.) are located in the `lib/` folder. Make sure that these folders have `755` permission because they contain files that have to be executable:

```bash
$ find /var/www/html/lib -type f -exec chmod 755 {} \;
```

## Building with Grunt
The `dist/` and `node_modules/` directory are intentionally left out. `build/` contains all the resources needed for the site: minified CSS files, uglified JS files, compressed images and font files. You will need to install Node and Grunt globally. The following guide is a distallation of Matt Bailey's [*A beginner's guide to Grunt: Redux*](http://mattbailey.io/a-beginners-guide-to-grunt-redux/).

A gruntfile (`Gruntfile.js`) and a corresponding package file (`package.json`) are already included in the repo. Simply run `grunt` or `grunt watch` to build the site. Note that you will have to [install all the listed dependencies](#markdown-header-install-dependencies) for this to work. The barebones `package.json` is available as `package.example.json`:

```json
{
  "name": "lotusbase",
  "version": "3.0.0",
  "description": "Lotus Base, an integrated genomics and proteomics resource for the model legume Lotus japonicus.",
  "private": true
}
```

### Install dependencies

Simply run `npm install` to install all the dev dependencies listed below. If you want to do it on a per-dependency basis, see below.

1. Install Node.js from their [official releases](https://nodejs.org/download/release/)
2. Install the command line interface of Grunt using:

        $ npm install -g grunt-cli

3. Install Grunt.js dependencies:

    They will update `package.json` if needed.

        $ npm install grunt --save-dev
        $ npm install grunt-concurrent --save-dev
        $ npm install grunt-contrib-clean --save-dev
        $ npm install grunt-contrib-copy --savedev
        $ npm install grunt-contrib-imagemin --save-dev
        $ npm install grunt-contrib-jshint --save-dev
        $ npm install grunt-contrib-uglify --save-dev
        $ npm install grunt-contrib-watch --save-dev
        $ npm install grunt-sass --save-dev
        $ npm install jshint-stylish --save
        $ npm install load-grunt-config --save-dev
        $ npm install time-grunt --save
        
## Publishing with Jekyll
We are using [Jekyll](https://jekyllrb.com) to generate static pages on the fly. Make sure that you have Ruby installed, and then install Jekyll as a gem:

```bash
$ gem install jekyll
```

All the blog resources are being stored in the `_blog` directory, and you should be building to the `blog` directory, which is publicly accessible. All requests made to `/_blog` are being redirected by htaccess to `/blog` instead.

### Dependencies
If you are getting the `It looks like you don't have classifier-reborn or one of its dependencies installed` error, you will have to install the gem by running:

```bash
$ gem install classifier-reborn
```

Also, it has been noted that when the number of posts increases, the indexing time exponentially increases. You can use [this fix](http://footle.org/2014/11/06/speeding-up-jekylls-lsi/) to spped up indexing:

```bash
$ brew install gsl
$ gem install rb-gsl
```

### Building
You will have to CD to the `src/blog` directory:

```bash
# Generating from the base directory
$ jekyll build --source ./src/blog --destination ./build/blog
```

If you want to enable live watch and rebuilds, simply use the `--watch` flag in your `build` command.

### Authorship
The `author` field in your blog posts/pages must correspond to preexisting team members for their avatars to show up. It is a design decision not to maintain two separate copies of avatars. The avatars are stored in the `/src/images/team/` directory and is minimized and copied over by Grunt to `/dist/images/team/`. The file names should correspond to author names that have bene (1) converted to lowercase and (2) underscore-delimited. For example, if I am using `Terry Mun` as the author of a post, the image file `terry_mun.jpg` should be present in the `/dist/images/team/` directory.