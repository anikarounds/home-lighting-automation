<?php
require('./control.php');

//$Pdu = new Pdu("http://admin:1234@192.168.0.101");

$dc = new DeviceController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$dc->setMode($_POST['mode']);
} else {
	echo json_encode($dc->getModes());
}

?>
