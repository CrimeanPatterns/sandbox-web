<?
require "../kernel/public.php";
$nID = intval(ArrayVal($_GET, "ID"));

// redirect to aw_out
$route = $nID ? getSymfonyContainer()->get('router')->generate('aw_out', ['redirectId' => $nID]) : '/';
Redirect($route);

?>
