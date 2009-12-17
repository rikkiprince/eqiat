<?php

if (!isset($_REQUEST["qtiid"])) die("No QTI ID specified");
if (!isset($_SESSION["items"][$_REQUEST["qtiid"]])) die("No QTI found in session data for specified QTI ID");

$ai = $_SESSION["items"][$_REQUEST["qtiid"]];

if (isset($_POST["makecp"])) {
	// build the manifest

	$imsqti = "http://www.imsglobal.org/xsd/imsqti_v2p1";
	$imsmd = "http://www.imsglobal.org/xsd/imsmd_v1p2";
	$manifest = simplexml_load_string('<manifest
		xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
		xmlns:imsmd="' . $imsmd . '"
		xmlns:imsqti="' . $imsqti . '"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.imsglobal.org/xsd/imsmd_v1p2 imsmd_v1p2p4.xsd http://www.imsglobal.org/xsd/imsqti_v2p1  http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
	/>');
	$manifest->addAttribute("identifier", "MANIFEST-" . $ai->getQTIID());

	// organizations element
	$manifest->addChild("organizations");

	// resources element
	$rs = $manifest->addChild("resources");
	$r = $rs->addChild("resource");
	$r->addAttribute("identifier", $ai->getQTIID());
	$r->addAttribute("type", "imsqti_item_xmlv2p1");
	$r->addAttribute("href", "{$ai->getTitleFS()}.qti.xml");
	$md = $r->addChild("metadata");

	// resource qti metadata
	$qmd = $md->addChild("qtiMetadata", null, $imsqti);
	$qmd->addChild("timeDependent", "false", $imsqti);
	$qmd->addChild("interactionType", "choiceInteraction", $imsqti); //TODO: get this from item object
	$qmd->addChild("feedbackType", "none", $imsqti); //TODO: change this when feedback is available
	$qmd->addChild("solutionAvailable", "true", $imsqti);

	// resource LOM metadata
	$lom = $md->addChild("lom", null, $imsmd);
	$g = $lom->addChild("general", null, $imsmd);
	$g->addChild("title", null, $imsmd)->addChild("langstring", $ai->getTitle(), $imsmd);
	if (isset($_POST["description"]) && !empty($_POST["description"]))
		$g->addChild("description", null, $imsmd)->addChild("langstring", $_POST["description"], $imsmd);
	if (isset($_POST["keywords"])) {
		$keywords = explode(",", $_POST["keywords"]);
		$keywords = array_map("trim", $keywords);
		foreach ($keywords as $keyword) {
			if (strlen($keyword) == 0)
				continue;
			$g->addChild("keyword", null, $imsmd)->addChild("langstring", $keyword, $imsmd);
		}
	}

	// file element
	$r->addChild("file")->addAttribute("href", "{$ai->getTitleFS()}.qti.xml");

	// make temporary zip archive
	$zip = new ZipArchive();
	$filename = "/tmp/" . uniqid("zip");
	if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true)
		die("couldn't make zip file");
	$zip->addFromString("imsmanifest.xml", simplexml_indented_string($manifest));
	$zip->addFromString("{$ai->getTitleFS()}.qti.xml", $ai->getQTIIndentedString());
	$zip->close();

	// download the content package
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=\"{$ai->getTitleFS()}.zip\"");
	echo file_get_contents($filename);

	// delete the temporary zip archive
	unlink($filename);

	exit;
}

include "htmlheader.php";
?>

<h2>Make a content package for your item</h2>

<form id="makecp" action="?page=makeContentPackage" method="post">
	<input type="hidden" name="qtiid" value="<?php echo htmlspecialchars($_REQUEST['qtiid']); ?>">
	<dl>
		<dt>Title</dt>
		<dd><?php echo htmlspecialchars($ai->getTitle()); ?></dd>

		<dt>Description</dt>
		<dd><textarea id="description" name="description" rows="4" cols="64"><?php if (isset($_POST["description"])) echo htmlspecialchars($_POST["description"]); ?></textarea></dd>

		<dt>Keywords (comma-separated)</dt>
		<dd><textarea id="keywords" name="keywords" rows="4" cols="64"><?php if (isset($_POST["keywords"])) echo htmlspecialchars($_POST["keywords"]); ?></textarea></dd>
	</dl>
	<div><input id="submit" type="submit" name="makecp" value="Submit"></div>
</form>

<?php
include "htmlfooter.php";
?>
