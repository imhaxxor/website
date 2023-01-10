<?php

require_once (dirname(__FILE__) . '/Astra.php');
require_once(dirname(__FILE__) . '/libraries/Astra_ip.php');

$client_ip = new Astra_ip();

$ip_address = $client_ip->get_ip_address();

ASTRA::$_db->block_bot($ip_address);
ASTRA::show_block_page();
die();