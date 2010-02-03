<?php

if (!isset($_REQUEST["qtiid"])) badrequest("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) badrequest("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];
$title = $ai->getTitle();

unset($_SESSION["items"][$_REQUEST["qtiid"]]);

if (isset($_REQUEST["async"])) ok();

include "htmlheader.php";
?>

<h2>Item deleted</h2>
<p>The assessment item <?php echo htmlspecialchars($title); ?> has been removed 
from memory.</p>

<?php include "htmlfooter.php"; ?>
