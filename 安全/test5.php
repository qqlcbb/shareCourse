<?php
header("Set-Cookie: cookie1=test1;httponly");
header("Set-Cookie: cookie2=test2;", false);

?>

<script>
	alert(document.cookie);
</script>