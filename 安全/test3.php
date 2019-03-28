<?php
error_reporting(0);
$name = $_GET["name"];
?>
<input id="text" type="text" value="<?php echo $name;?>" />
<div id="print"></div>
<script type="text/javascript">
var text = document.getElementById("text"); 
var print = document.getElementById("print");
print.innerHTML = text.value; // 获取 text的值，并且输出在print内。这里是导致xss的主要原因。
</script>

<!-- <img src=1 onerror=alert(1)> -->