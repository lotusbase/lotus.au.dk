$(function() {

	// Expand global functions
	globalFun.team = {
		expand: function() {
			var dfd = new $.Deferred();
			$(this).attr('data-order', 0).addClass('team-card--expanded');
			dfd.resolve();
			return dfd.promise();
		},
		collapse: function() {
			$(this).removeClass('team-card--expanded').attr('data-order', $(this).attr('data-order-original'));
		},
		collapseSelf: function() {
			var dfd = new $.Deferred();
			globalFun.team.collapse.call($(this)[0]);
			dfd.resolve();
			return dfd.promise();
		},
		collapseSiblings: function() {
			var dfd = new $.Deferred();
			$(this).siblings().each(globalFun.team.collapse);
			dfd.resolve();
			return dfd.promise();
		},
		relayout: function() {
			var dfd = new $.Deferred();
			$(this).isotope('updateSortData').isotope();
			$(this).one('layoutComplete', function() {
				dfd.resolve();
				return dfd.promise();
			});
		}
	};

	// Expand user card
	$('.masonry li.team-card').on('click', function() {
		var $t = $(this);

		if(!$t.hasClass('team-card--expanded')) {
			window.history.pushState({lotusbase: true}, '', '#'+$t.attr('id'));
			$w.trigger('hashchange', [{ smoothScroll: false, collapseSelf: false }]);
		} else {
			window.history.pushState({lotusbase: true}, '', '');
			$w.trigger('hashchange', [{ smoothScroll: false, collapseSelf: true }]);
		}
		
	});

	// Show cards when layout is complete
	$('.masonry').on('layoutComplete', function() {
		$(this).find('li.team-card').css('opacity', 1);
	});

	// Get URL param
	$w.on('hashchange', function(event, opts) {
		var hash = window.location.hash;
		if(opts.collapseSelf) {
			$.when(globalFun.team.collapseSelf.call($(hash)[0])).then(globalFun.team.relayout.call($(hash).closest('.masonry')[0]));
		} else {
			if(hash.length) {
				$
				.when.apply($, [
					globalFun.team.expand.call($(hash)[0]),
					globalFun.team.collapseSiblings.call($(hash)[0])
				])
				.then(globalFun.team.relayout.call($(hash).closest('.masonry')[0]));
			}
		}
	});
	window.setTimeout(function() {
		$w.trigger('hashchange', [{ smoothScroll: false, collapseSelf: false }]);
	}, 1000);

	// Listen to popstate
	$w.on('popstate', function(e) {
		if(e.originalEvent.state !== null) {
			var hash = window.location.hash;
			if(hash.length) {
				$
				.when.apply($, [
					globalFun.team.expand.call($(hash)[0]),
					globalFun.team.collapseSiblings.call($(hash)[0])
				])
				.then(globalFun.team.relayout.call($(hash).closest('.masonry')[0]));
			}
		}
	});

	// Map
	var map,
		accessToken = 'pk.eyJ1IjoibG90dXNiYXNlIiwiYSI6ImNpaGZjaXR3cDBsc2t0dGx6ZjV4NjdiamEifQ.tZYfphcXXFL17KLWHMppQQ',
		sourceObj = {
			"type": "FeatureCollection",
			"features": [{
				"type": "Feature",
				"geometry": {
					"type": "Point",
					"coordinates": [10.194797,56.168039]
				},
				"properties": {
					"title": "CARB",
					"heading": "Centre for Carbohydrate Recognition and Signalling",
					"contact": {
						"organizationName": "Centre for Carbohydrate Recognition and Signalling",
						"streetAddress": ["Gustav Wieds Vej 10", "8000 Aarhus C", "Denmark"],
						"telephoneNumber": "(+45) 87 15 55 04"
					},
					"marker-symbol": "chemist"
				}
			}]
		};

	// Set scrolling status
	globalVar.mapMoved = false;

	// Render popup content
	globalFun.mapbox = {
		popupContent: function(p) {
			return '<h1><img class="logo" src="/dist/images/logos/carb.png" title="'+p.title+': '+p.contact.organizaitonName+'" alt="'+p.title+': '+p.contact.organizaitonName+'" />'+p.heading+'</h1><p class="vcard"><a class="fn org url" href="https://lotus.au.dk/"><span class="organization-name">'+p.contact.organizationName+'</span></a><br /><span class="street-address">'+p.contact.streetAddress.join('<br />')+'</span><br /><span class="tel">Telephone: <span class="value"></span>'+p.contact.telephoneNumber+'</span></p>';
		}
	};

	// Check for WebGL support
	if (mapboxgl.supported() && window.location.search.split('?raster=')[1] !== 'true') {
	//if (mapboxgl.supported()) {

		// Create map
		mapboxgl.accessToken = accessToken;
		map = new mapboxgl.Map({
			container: 'map',
			//style: 'mapbox://styles/mapbox/streets-v8',
			style: 'mapbox://styles/lotusbase/cihfckrze00twbdm1cf5t3i32',
			center: [10.879, 56.161],
			zoom: 6,
			pitch: 25
		});

		map.on('style.load', function () {
			map.addSource("markers", {
				"type": "geojson",
				"data": sourceObj
			});

			map.addLayer({
				"id": "markers",
				"type": "symbol",
				"interactive": true,
				"source": "markers",
				"layout": {
					"icon-image": "{marker-symbol}-24",
					"text-field": "{title}",
					"text-font": ["Open Sans Semibold", "Arial Unicode MS Bold"],
					"text-offset": [0, 0.6],
					"text-anchor": "top"
				},
				"paint": {
					"text-size": 12,
					"text-halo-color": "rgba(255,255,255,0.75)",
					"text-halo-width": 1,
					"text-halo-blur": 1,
					"icon-halo-color": "rgba(255,255,255,0.75)",
					"icon-halo-width": 1,
					"icon-halo-blur": 1
				}
			});
		});

		// Click
		map.on('click', function(e) {
			map.featuresAt(e.point, {radius: 20} , function(err, features) {
				for (var i = 0; i < features.length; i++) {
					if (features[i].layer.id === 'markers') {
						var prop = features[i].properties,
							tooltip = new mapboxgl.Popup()
							.setLngLat(e.lngLat)
							.setHTML(globalFun.mapbox.popupContent(prop))
							.addTo(map);
						break;
					}
				}
			});
		});

		// Enable zooming during scrolling
		$w.on('scroll.mapMove', $.throttle(100, function() {
			if($w.scrollTop() > $('#map').offset().top - 0.5 * $('#map').outerHeight() && !globalVar.mapMoved) {
				// We don't need to listen to scroll anymore
				$w.off('scroll.mapMove');

				// Fly
				map.flyTo({
					center: [10.194866, 56.167947],
					zoom: 15.4,
					duration: 2500,
					bearing: -10
				});
				map.once('moveend', function() {
					globalVar.mapMoved = true;
				});
			}
		}));

	} else {

		// Create map
		L.mapbox.accessToken = accessToken;
		map = L.mapbox.map('map', 'lotusbase.o9e761mh')
			.setView([56.167947, 10.194866], 15);

		var featureLayer = L.mapbox.featureLayer(sourceObj).addTo(map);
		featureLayer.eachLayer(function(layer) {
			var prop = layer.feature.properties;
			layer.bindPopup(globalFun.mapbox.popupContent(prop), {
				maxWidth: $w.width() * 0.5
			});
		});
	}
});