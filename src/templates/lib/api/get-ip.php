<?php
$dataReturn->set_data(array(
	'ipAddress' => $_SERVER['REMOTE_ADDR'],
	'isIntranet' => is_intranet_client()
));
$dataReturn->execute();
?>