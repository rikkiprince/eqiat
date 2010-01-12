<?php

class QTITextEntry extends QTIAssessmentItem {
	public function __construct() {
		parent::__construct();

		$this->itemtype = "textEntry";
		$this->itemtypeprint = "text entry";
		$this->itemtypedescription = "A stimulus or question prompt followed by a body of text with gaps. The candidate enters the appropriate words to complete the text.";
		$this->interactionType = "textEntryInteraction";
	}

	public function showForm($data = null) {
		if (!is_null($data))
			$this->data = $data;

		include "htmlheader.php";
		?>

		<script type="text/javascript">
			getgapstrings = function() {
				var value = $("#textbody").val();
				var pos = 0;
				var endpos;
				var gaps = [];

				while (true) {
					pos = value.indexOf("[", pos);
					if (pos == -1)
						break;
					endpos = value.indexOf("]", pos);
					if (endpos == -1)
						break;

					gaps[gaps.length] = value.substring(pos + 1, endpos);

					pos++;
				}
				return gaps;
			};

			updatetextgap = function() {
				var gapid = parseInt($(this).parents("div.gap:first").attr("id").split("_")[1]);
				var gapstrings = getgapstrings();
				if (gapid > gapstrings.length - 1) {
					console.error("trying to update gapstring which doesn't exist");
					return;
				}

				var value = $("#textbody").val();
				var pos = -1;
				var endpos;
				var gap = -1;
				while (gap < gapid) {
					pos++;
					pos = value.indexOf("[", pos);
					if (pos == -1) {
						console.error("didn't find next gap");
						return;
					}
					endpos = value.indexOf("]", pos);
					if (endpos == -1) {
						console.error("didn't find end of gap");
						return;
					}
					gap++;
				}

				$("#textbody").val(value.substring(0, pos + 1) + $("#gap_" + gapid + " input.responsetext:first").val() + value.substring(endpos));
			};

			getfirstresponsestrings = function() {
				var strings = [];
				$("table.responses:visible").each(function() {
					strings[strings.length] = $("input.responsetext:first", this).val();
				});
				return strings;
			};

			updategapstable = function() {
				var currentgap = 0;

				while (true) {
					var gapstrings = getgapstrings();
					var firstresponsestrings = getfirstresponsestrings();

					// finished if current gap doesn't exist
					if (currentgap >= gapstrings.length)
						break;

					// match gaps in text to table gaps, in order
					var matches = [];
					var prev = -1;
					for (var textgap = 0; textgap < gapstrings.length; textgap++) {
						for (var gaptable = prev + 1; gaptable < firstresponsestrings.length; gaptable++) {
							if (gapstrings[textgap] == firstresponsestrings[gaptable]) {
								matches[matches.length] = [textgap, gaptable];
								prev = textgap;
								break;
							}
						}
					}

					// consider the first match from the current gap
					var match = undefined;
					for (var i = 0; i < matches.length; i++)
						if (matches[i][0] >= currentgap)
							match = matches[i];

					// if no more matches, correct remaining gaps
					if (match == undefined)
						break;

					// go through the textgap/gaptable pairs from current gap to 
					// current match
					for (var gap = currentgap; gap < match[0] && gap < match[1]; gap++) {
						// update the first response in the table to the 
						// contents of the text gap
						$("#gap_" + gap + "_response_0").val(gapstrings[gap]);
					}

					if (match[0] == match[1]) {
						// nothing needs to be added or deleted
						currentgap = match[0] + 1;
						continue;
					}

					if (gap == match[1]) {
						// there are extra gaps in the text -- add tables in 
						// reverse order at this position
						for (var i = match[0]; i > match[1]; i--)
							addgap(match[1], gapstrings[i - 1]);
						currentgap = match[0] + 1;
						continue;
					}

					if (gap == match[0]) {
						// there are extra gap tables -- delete the extras
						for (var i = match[0]; i < match[1]; i++)
							$("#gap_" + i).remove();
						renumber();
						currentgap = match[0] + 1;
						continue;
					}
				}

				// we're after the last match now -- all that's left is to 
				// correct/add/delete tables for any gaps after the last match
				// to simplify logic just treat all of them as after the last 
				// match

				var gapstrings = getgapstrings();
				var firstresponsestrings = getfirstresponsestrings();

				// correct number of tables
				for (var i = firstresponsestrings.length; i < gapstrings.length; i++)
					addgap(i, gapstrings[i]);
				for (var i = gapstrings.length - 1; i < firstresponsestrings.length - 1; i++)
					$("#gap_" + (i + 1)).remove();

				// update first responses
				for (var i = 0; i < gapstrings.length; i++)
					$("#gap_" + i + "_response_0").val(gapstrings[i]);
			};

			addgap = function(newid, response) {
				// clone the template gap
				var newgap = $("#gap_-1").clone();

				// give it and its bits the new id number
				$("input.responsetext", newgap).val(response).change(updatetextgap);

				// reinstate the add action
				$("input.addresponse", newgap).click(addresponse);

				// make it visible
				newgap.show();

				// add it to the list in place
				$("#gap_" + (newid - 1)).after(newgap);

				// renumber everything
				renumber();

				return newid;
			};

			removeresponse = function() {
				// get our gap and its id
				var gap = $(this).parents("div.gap:first");
				var gapid = gap.attr("id").split("_")[1];

				// can't delete the last response
				if ($("table.responses tr.response", gap).size() < 2) {
					alert("Can't remove the only response");
					return;
				}

				$(this).parents("tr:first").remove();

				// renumber everything
				renumber();
			};

			addresponse = function() {
				// get our gap and its id
				var gap = $(this).parents("div.gap:first");
				var gapid = gap.attr("id").split("_")[1];

				// get the new response id
				var newid = parseInt($("table.responses tr.response:last input.responsetext", gap).attr("id").split("_")[3]) + 1;

				// clone the template response and update the ids
				var newresponse = $("#gap_-1 table.responses tr.response:first").clone();

				// reinstate the remove action and make it visible
				$("input.removeresponse", newresponse).click(removeresponse).show();

				// add the new row to the table
				$("table.responses", gap).append(newresponse);

				// renumber everything
				renumber();
			};

			renumber = function() {
				var gapid = -1; // include template gap
				$("#gaps div.gap").each(function() {
					$(this).attr("id", "gap_" + gapid);
					$("span.gapnumber", this).html(gapid + 1);
					var responseid = 0;
					$("table.responses tr.response", this).each(function() {
						$("input.responsetext", this).attr("id", "gap_" + gapid + "_response_" + responseid).attr("name", "gap_" + gapid + "_response_" + responseid);
						$("input.responsescore", this).attr("id", "gap_" + gapid + "_response_" + responseid + "_score").attr("name", "gap_" + gapid + "_response_" + responseid + "_score");
						responseid++;
					});
					gapid++;
				});
			};

			submitcheck = function() {
				// ensure the gaps table is up to date
				updategapstable();

				// clear any previously set background colours
				$("input, textarea").removeClass("error warning");

				// title must be set
				if ($("#title").val().length == 0) {
					$("#title").addClass("error");
					alert("A title must be set for this item");
					return false;
				}

				// must have at least one gap
				if ($("div.gap:visible").size() == 0) {
					$("#textbody").addClass("error");
					alert("You must have at least one gap for the candidate to fill in");
					return false;
				}

				// scores must make sense
				var ok = true;
				$("input.responsescore:visible").each(function() {
					if ($(this).val().length == 0 || isNaN($(this).val()) || parseFloat($(this).val()) < 0) {
						var gapid = parseInt($(this).attr("id").split("_")[1]);
						var responseid = parseInt($(this).attr("id").split("_")[3]);
						$(this).addClass("error");
						alert("Score for gap " + (gapid + 1) + " response " + (responseid + 1) + " must be a positive number");
						ok = false;
						return false;
					}
				});
				if (!ok) return false;

				// can't have identical responses for a single gap
				for (var gap = 0; gap < $("div.gap:visible").size(); gap++) {
					for (var i = 0; i < $("#gap_" + gap + " input.responsetext").size(); i++) {
						for (var j = i + 1; j < $("#gap_" + gap + " input.responsetext").size(); j++) {
							if ($("#gap_" + gap + "_response_" + i).val() == $("#gap_" + gap + "_response_" + j).val()) {
								$("#gap_" + gap + "_response_" + i + ", #gap_" + gap + "_response_" + j).addClass("error");
								alert("No two responses can be the same but gap " + (gap + 1) + " responses " + (i + 1) + " and " + (j + 1) + " are equal");
								return false;
							}
						}
					}
				};

				// issue warnings if applicable

				// confirm the user wanted an empty stimulus
				if ($("#stimulus").val().length == 0) {
					$("#stimulus").addClass("warning");
					if (!confirm("Stimulus is empty -- click OK to continue regardless or cancel to edit it"))
						return false;
					else
						$("#stimulus").removeClass("error warning");
				}

				// confirm the user wanted any empty boxes
				$("input.responsetext:visible").each(function(n) {
					if ($(this).val().length == 0) {
						var gapid = parseInt($(this).attr("id").split("_")[1]);
						var responseid = parseInt($(this).attr("id").split("_")[3]);
						$(this).addClass("warning");
						ok = confirm("Gap " + (gapid + 1) + " response " + (responseid + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
						if (ok)
							$(this).removeClass("error warning");
						else
							return false; //this is "break" in the Jquery each() pseudoloop
					}
				});
				if (!ok) return false;

				// confirm the user wanted zero scores
				$("input.responsescore:visible").each(function(n) {
					if (parseFloat($(this).val()) == 0.0) {
						var gapid = parseInt($(this).attr("id").split("_")[1]);
						var responseid = parseInt($(this).attr("id").split("_")[3]);
						$(this).addClass("warning");
						ok = confirm("Score for gap " + (gapid + 1) + " response " + (responseid + 1) + " is zero but this is the default score for any response not listed -- click OK to continue regardless or cancel to edit it");
						if (ok)
							$(this).removeClass("error warning");
						else
							return false; //this is "break" in the Jquery each() pseudoloop
					}
				});
				if (!ok) return false;

				return true;
			};

			$(document).ready(function() {
				$("#textbody").change(updategapstable);
				$("input.addresponse:visible").click(addresponse);
				$("input.removeresponse:visible").click(removeresponse);
				$("input.responsetext:visible").change(updatetextgap);
				$("#submit").click(submitcheck);
			});
		</script>

		<h2>Edit text entry item</h2>

		<?php $this->showmessages(); ?>

		<form id="edititem" action="?page=editAssessmentItem" method="post">
			<input type="hidden" name="qtiid" value="<?php echo $this->getQTIID(); ?>">
			<dl>
				<dt><label for="title">Title</label></dt>
				<dd><input size="64" type="text" name="title" id="title"<?php if (isset($this->data["title"])) { ?> value="<?php echo htmlspecialchars($this->data["title"]); ?>"<?php } ?>></dd>

				<dt><label for="stimulus">Stimulus or question prompt</label></dt>
				<dd><textarea rows="8" cols="64" name="stimulus" id="stimulus"><?php if (isset($this->data["stimulus"])) echo htmlspecialchars($this->data["stimulus"]); ?></textarea></dd>

				<dt>Text body</dt>
				<dd>
					<p class="hint">Mark positions of gaps with [] &ndash; you can put a possible response in the brackets if you like</p>
					<textarea rows="8" cols="64" name="textbody" id="textbody"><?php if (isset($this->data["textbody"])) echo htmlspecialchars($this->data["textbody"]); ?></textarea>
				</dd>

				<dt>Responses</dt>
				<dd>
					<p class="hint">Responses are always case-sensitive</p>
					<dl id="gaps">
						<div class="gap" id="gap_-1" style="display: none;">
							<dt>Gap <span class="gapnumber">0</span></dt>
							<dd>
								<table class="responses">
									<tr>
										<th>Response</th>
										<th>Score</th>
										<th>Actions</th>
									</tr>
									<tr class="response">
										<td><input class="responsetext" type="text" name="gap_-1_response_0" id="gap_-1_response_0" size="32"></td>
										<td><input class="responsescore" type="text" name="gap_-1_response_0_score" id="gap_-1_response_0_score" size="3" value="1"></td>
										<td><input style="display: none;" type="button" class="removeresponse" value="Remove"></td>
									</tr>
								</table>
								<input type="button" class="addresponse" value="Add response">
							</dd>
						</div>
						<?php for ($i = 0; array_key_exists("gap_{$i}_response_0", $this->data); $i++) { ?>
							<div class="gap" id="gap_<?php echo $i; ?>">
								<dt>Gap <span class="gapnumber"><?php echo $i + 1; ?></span></dt>
								<dd>
									<table class="responses">
										<tr>
											<th>Response</th>
											<th>Score</th>
											<th>Actions</th>
										</tr>
										<?php for ($j = 0; array_key_exists("gap_{$i}_response_{$j}", $this->data); $j++) { ?>
											<tr class="response">
												<td><input type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>" size="32" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}"]); ?>"></td>
												<td><input type="text" name="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" id="gap_<?php echo $i; ?>_response_<?php echo $j; ?>_score" size="3" value="<?php echo htmlspecialchars($this->data["gap_{$i}_response_{$j}_score"]); ?>"></td>
												<td><input type="button" class="removequestion" value="Remove"></td>
											</tr>
										<?php } ?>
									</table>
									<input type="button" class="addresponse" value="Add response">
								</dd>
							</div>
						<?php } ?>
					</dl>
				</dd>
			</dl>
			<div><input id="submit" type="submit" name="edititem" value="Submit"></div>
		</form>

		<?php
		include "htmlfooter.php";
	}

	public function buildQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;

		if (empty($this->data))
			return false;

		// container element and other metadata
		$ai = new SimpleXMLElement('
			<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"/>
		');
		$ai->addAttribute("adaptive", "false");
		$ai->addAttribute("timeDependent", "false");
		$ai->addAttribute("identifier", "te_" . md5(uniqid()));
		if (isset($this->data["title"]))
			$ai->addAttribute("title", $this->data["title"]);

		// response declarations
		for ($g = 0; array_key_exists("gap_{$g}_response_0", $this->data); $g++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_gap_$g");
			$rd->addAttribute("cardinality", "single");
			$rd->addAttribute("baseType", "string");

			$m = $rd->addChild("mapping");
			$m->addAttribute("defaultValue", "0");
			for ($r = 0; array_key_exists("gap_{$g}_response_{$r}", $this->data); $r++) {
				$me = $m->addChild("mapEntry");
				$me->addAttribute("mapKey", $this->data["gap_{$g}_response_$r"]);
				$me->addAttribute("mappedValue", $this->data["gap_{$g}_response_${r}_score"]);
			}
		}

		// outcome declaration
		$od = $ai->addChild("outcomeDeclaration");
		$od->addAttribute("identifier", "SCORE");
		$od->addAttribute("cardinality", "single");
		$od->addAttribute("baseType", "float");
		$od->addChild("defaultValue");
		$od->defaultValue->addChild("value", "0");

		// item body
		$ib = $ai->addChild("itemBody");

		// get stimulus and add to the XML tree
		if (isset($this->data["stimulus"]) && !empty($this->data["stimulus"])) {
			// if stimulus doesn't start with a div tag, wrap it in one
			$this->data["stimulus"] = trim($this->data["stimulus"]);
			if (substr($this->data["stimulus"], 0, 4) != "<div")
				$this->data["stimulus"] = "<div>" . $this->data["stimulus"] . "</div>";

			// parse it as XML
			// The stimulus must be valid XML at this point. Even if it is, and even 
			// if it's also valid XHTML, it may still not be valid QTI since QTI 
			// only allows a subset of XHTML. So we collect errors here.
			libxml_use_internal_errors(true);
			$stimulus = simplexml_load_string($this->data["stimulus"]);
			if ($stimulus === false) {
				$this->errors[] = "Stimulus is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
				foreach (libxml_get_errors() as $error)
					$this->errors[] = "Stimulus line " . $error->line . ", column " . $error->column . ": " . $error->message;
				libxml_clear_errors();
			} else {
				simplexml_append($ib, $stimulus);
			}
			libxml_use_internal_errors(false);
		}

		// body text
		$bt = $ib->addChild("div");
		$bt->addAttribute("class", "textentrytextbody");
		$text = xmlspecialchars($this->data["textbody"]);
		$text = preg_replace('%\n\n+%', "</p><p>", $text);
		$text = preg_replace('%\n%', "<br/>", $text);
		$text = "<p>" . $text . "</p>";
		$g = 0;
		$start = 0;
		while (($start = strpos($text, "[", $start)) !== false) {
			$start = strpos($text, "[");
			$end = strpos($text, "]", $start);

			// base expected length on the longest answer plus 10%
			$el = 0;
			for ($r = 0; array_key_exists("gap_{$g}_response_{$r}", $this->data); $r++)
				$el = max($el, strlen($this->data["gap_{$g}_response_{$r}"]));
			$el = ceil($el * 1.1);

			$text = substr($text, 0, $start)
				. '<textEntryInteraction responseIdentifier="RESPONSE_gap_' . ($g++) . '" expectedLength="' . $el . '"/>'
				. substr($text, $end + 1);
		}
		// parse it as XML
		libxml_use_internal_errors(true);
		$textxml = simplexml_load_string($text);
		if ($textxml === false) {
			$this->errors[] = "Text body did not convert to valid XML";
			foreach (libxml_get_errors() as $error)
				$this->errors[] = "Text body line " . $error->line . ", column " . $error->column . ": " . $error->message;
			libxml_clear_errors();
		} else {
			simplexml_append($bt, $textxml);
		}
		libxml_use_internal_errors(false);

		// response processing
		$rp = $ai->addChild("responseProcessing");

		// set score = 0
		$sov = $rp->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0.0")->addAttribute("baseType", "float");

		for ($g = 0; array_key_exists("gap_{$g}_response_0", $this->data); $g++) {
			$rc = $rp->addChild("responseCondition");

			// if
			$ri = $rc->addChild("responseIf");

			// not null
			$ri->addChild("not")->addChild("isNull")->addChild("variable")->addAttribute("identifier", "RESPONSE_gap_{$g}");

			// increment score
			$sov = $ri->addChild("setOutcomeValue");
			$sov->addAttribute("identifier", "SCORE");
			$s = $sov->addChild("sum");
			$s->addChild("variable")->addAttribute("identifier", "SCORE");
			$s->addChild("mapResponse")->addAttribute("identifier", "RESPONSE_gap_{$g}");
		}

		if (!empty($this->errors))
			return false;

		// validate the QTI
		validateQTI($ai, $this->errors, $this->warnings, $this->messages);

		if (!empty($this->errors))
			return false;

		$this->qti = $ai;
		return $this->qti;
	}

	public function fromXML(SimpleXMLElement $xml) {
		$data = array(
			"itemtype"	=>	$this->itemType(),
			"title"		=>	(string) $xml["title"],
		);

		// TODO: logic

		// happy with that -- set data property
		$this->data = $data;

		return 255;
	}
}

?>