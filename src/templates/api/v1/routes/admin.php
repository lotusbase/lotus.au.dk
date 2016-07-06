<?php
// Admin API
$api->get('/admin', function ($request, $response) {
	$response->write('Welcome to Lotus base admin API v1');
	return $response;
});

?>