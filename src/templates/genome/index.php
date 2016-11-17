<?php
    require_once('../config.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lotus Genome Browser&mdash;Lotus Base</title>
    <?php
        $document_header = new \LotusBase\Component\DocumentHeader();
        $document_header->set_meta_tags(array(
            'description' => 'Explore the Lotus japonicus genome using JBrowse.'
            ));
        echo $document_header->get_document_header();
    ?>
    <link rel="stylesheet" type="text/css" href="css/genome.css">
    <link rel="stylesheet" href="<?php echo DOMAIN_NAME; ?>/dist/css/genome.min.css" type="text/css" />
</head>
<body class="tools jbrowse <?php echo (isset($_GET['embed']) ? 'embed' : ''); ?>">

    <?php
        if(!isset($_GET['embed'])) {
            $header = new \LotusBase\Component\PageHeader();
            echo $header->get_header();
        }
    ?>

    <section class="full-width" id="lotusbase__genome">
        <div id="GenomeBrowser" style="height: 100%; width: 100%; padding: 0; border: 0;">
            <div id="jBrowse__default-message">
                <h2>JBrowse is loading</h2>
                <p>It takes a bit of time to render all the elements in JBrowse. However, if this message does not go away, try refreshing the browser.</p>
            </div>
        </div>
        <div style="display: none">JBrowseDefaultMainPage</div>
    </section>

    <?php
        if(!isset($_GET['embed'])) {
            include(DOC_ROOT.'/footer.php');
        }
    ?>
   <script type="text/javascript">
                        // jshint unused: false
                        var dojoConfig = {
                                async: true,
                                baseUrl: './src',
                                has: {
                                        'host-node': false // Prevent dojo from being fooled by Electron
                                }
                        };
                        // Move Electron's require out before loading Dojo
                        if(window.process&&process.versions&&process.versions.electron) {
                                window.electronRequire = require;
                                delete window.require;
                        }
        </script>
        <script type="text/javascript" src="src/dojo/dojo.js"></script>
        <script type="text/javascript" src="src/JBrowse/init.js"></script>
        <script type="text/javascript">
                window.onerror=function(msg){
                        if( document.body )
                                document.body.setAttribute("JSError",msg);
                }

                // puts the main Browser object in this for convenience.    feel
                // free to move it into function scope if you want to keep it
                // out of the global namespace
                var JBrowse;
                require(['JBrowse/Browser', 'dojo/io-query', 'dojo/json' ],
                         function (Browser,ioQuery,JSON) {
                                     // the initial configuration of this JBrowse
                                     // instance

                                     // NOTE: this initial config is the same as any
                                     // other JBrowse config in any other file. this
                                     // one just sets defaults from URL query params.
                                     // If you are embedding JBrowse in some other app,
                                     // you might as well just set this initial config
                                     // to something like { include: '../my/dynamic/conf.json' },
                                     // or you could put the entire
                                     // dynamically-generated JBrowse config here.

                                     // parse the query vars in the page URL
                                     var queryParams = ioQuery.queryToObject( window.location.search.slice(1) );

                                     var config = {
                                             containerID: "GenomeBrowser",

                                             dataRoot: queryParams.data,
                                             queryParams: queryParams,
                                             location: queryParams.loc,
                                             forceTracks: queryParams.tracks,
                                             initialHighlight: queryParams.highlight,
                                             show_nav: queryParams.nav,
                                             show_tracklist: queryParams.tracklist,
                                             show_overview: queryParams.overview,
                                             show_menu: queryParams.menu,
                                             show_tracklabels: queryParams.tracklabels,
                                             highResolutionMode: queryParams.highres,
                                             stores: { url: { type: "JBrowse/Store/SeqFeature/FromConfig", features: [] } },
                                             makeFullViewURL: function( browser ) {

                                                     // the URL for the 'Full view' link
                                                     // in embedded mode should be the current
                                                     // view URL, except with 'nav', 'tracklist',
                                                     // and 'overview' parameters forced to 1.

                                                     return browser.makeCurrentViewURL({ nav: 1, tracklist: 1, overview: 1 });
                                             },
                                             updateBrowserURL: true
                                     };

                                     //if there is ?addFeatures in the query params,
                                     //define a store for data from the URL
                                     if( queryParams.addFeatures ) {
                                             config.stores.url.features = JSON.parse( queryParams.addFeatures );
                                     }

                                     // if there is ?addTracks in the query params, add
                                     // those track configurations to our initial
                                     // configuration
                                     if( queryParams.addTracks ) {
                                             config.tracks = JSON.parse( queryParams.addTracks );
                                     }

                                     // if there is ?addStores in the query params, add
                                     // those store configurations to our initial
                                     // configuration
                                     if( queryParams.addStores ) {
                                             config.stores = JSON.parse( queryParams.addStores );
                                     }

                                     // create a JBrowse global variable holding the JBrowse instance
                                     JBrowse = new Browser( config );
                });
        </script>
    </body>
</html>
