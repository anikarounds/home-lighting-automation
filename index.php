<?php
require('./control.php');

//$Pdu = new Pdu("http://admin:1234@192.168.0.101");

$dc = new DeviceController();

//$dc->getState();
//$dc->setState("Kitchen Light",'ON');
//$dc->getModes();
//$dc->setMode("Full");

//var_dump( $dc->getState("Nick Nightstand"));

//$Pdu->getUnitState(1);
//$Pdu->setUnitState(1,"off");

//echo "welcome to your home";
?>
<!DOCTYPE html>
<html>
<head>
<style>
h2 {
  color: white;
}
body {
  background:
	black;
 }
.button {
    background-color: #FF3333;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 30px;
    margin: 4px 2px;
    cursor: pointer;
}
.active {
    background-color: #4CAF50;
}
</style>
<script type="text/javascript">
var url = "http://home/data.php";
var getJSON = function(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open("get", url, true);
    xhr.responseType = "json";
    xhr.onload = function() {
      var status = xhr.status;
      if (status == 200) {
        callback(null, xhr.response);
      } else {
        callback(status);
      }
    };
    xhr.send();
};


var update = function() {
	getJSON(url,function(err, modes) {
	var body = document.getElementById("buttonz");
	body.innerHTML = '';
	for (var key in modes) {
		var button = document.createElement("button");
		button.innerHTML = key;
		button.className += " button";
		if (modes[key] == "Active") {
			button.className += " active";
		}
		body.appendChild(button);
		button.addEventListener("click",setMode);
	}
});
};;

var setMode = function(mode) {
	var xhr = new XMLHttpRequest();
	xhr.open("post",url, true);
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.send("mode=" + mode.srcElement.innerHTML);
	setTimeout(update,250);
};

update();
setInterval(update, 10000);

</script>
<meta name="viewport" content="width=device-width" />
</head>
<body>

<h2>509 Light Controller</h2>
<p><div id="buttonz"></div></p>



</body>
</html>

