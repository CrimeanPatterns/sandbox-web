<?#End main part of the page?>

<?php
if (isset($Interface->onLoadScripts) && count($Interface->onLoadScripts) > 0)
	echo "<script>
	\$(window).load(function() {
		".implode("\n", $Interface->onLoadScripts)."\n
		activateDatepickers('active');
	});
	</script>";
?>

</body>

</html>
