<?php

/*
 * Eqiat
 * Easy QTI Item Authoring Tool
 */

/*------------------------------------------------------------------------------
(c) 2010 JISC-funded EASiHE project, University of Southampton
Licensed under the Creative Commons 'Attribution non-commercial share alike' 
licence -- see the LICENCE file for more details
------------------------------------------------------------------------------*/

class QTIExtendedMatchingItem extends QTIAssessmentItem {
	public function itemTypePrint() {
		return "extended matching item";
	}
	public function itemTypeDescription() {
		return "A video followed by a number of possible responses and then a number of question prompts. The candidate checks each response which is correct for each question prompt.";
	}

	protected function headerJS() {
		ob_start();
		?>
		alphaChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

		addoption = function() {
			// clone the last option on the list and increment its id
			var newoption = $("#options tr.option:last").clone();
			var oldid = parseInt($("input.optiontext", newoption).attr("id").split("_")[1]);
			var newid = oldid + 1;

			// give it the new id number and wipe its text
			newoption.attr("id", "option_" + newid);
			$(".optionid", newoption).text(alphaChars.charAt(newid));
			$("input.optiontext", newoption).attr("id", "option_" + newid + "_optiontext").attr("name", "option_" + newid + "_optiontext").val("").removeClass("error warning");

			// stripe it
			newoption.removeClass("row" + (oldid % 2)).addClass("row" + (newid % 2));

			// reinstate the remove action
			$("input.removeoption", newoption).click(removeoption);

			// add it to the list
			$("#options").append(newoption);

			// add checkboxes for this new option to each question
			$("#questions tr.question td.correctresponses").each(function() {
				var newcorrect = $("label.correct:last", this).clone();
				var questionid = newcorrect.attr("id").split("_")[1];
				newcorrect.attr("id", "question_" + questionid + "_option_" + newid);
				$("input", newcorrect).removeAttr("checked").attr("id", "question_" + questionid + "_option_" + newid + "_correct").attr("name", "question_" + questionid + "_option_" + newid + "_correct");
				$(".optionid", newcorrect).text(alphaChars.charAt(newid));
				$(this).append(newcorrect);
			});
		};

		removeoption = function() {
			if ($("#options tr.option").size() < 2) {
				alert("Can't remove the last option");
				return;
			}

			var row = $(this).parents("tr:first");

			// get its id
			var optionid = row.attr("id").split("_")[1];

			// remove it
			row.remove();

			// renumber and restripe the remaining options
			var i = 0;
			$("#options tr.option").each(function() {
				$(this).attr("id", "option_" + i);
				$(".optionid", this).text(alphaChars.charAt(i));
				$("input.optiontext", this).attr("id", "option_" + i + "_optiontext").attr("name", "option_" + i + "_optiontext");
				$(this).removeClass("row" + ((i + 1) % 2)).addClass("row" + (i % 2));
				i++;
			});

			// remove this option's checkboxes from each question
			for (var i = 0; i < $("#questions tr.question").size(); i++) {
				$("#question_" + i + "_option_" + optionid).remove();
			}

			// renumber the remaining checkboxes
			$("#questions tr.question td.correctresponses").each(function() {
				var questionid = $(this).parents("tr.question:first").attr("id").split("_")[1];
				i = 0;
				$("label.correct", this).each(function() {
					$(this).attr("id", "question_" + questionid + "_option_" + i);
					$(".optionid", this).text(alphaChars.charAt(i));
					$("input.correct", this).attr("id", "question_" + questionid + "_option_" + i + "_correct").attr("name", "question_" + questionid + "_option_" + i + "_correct");
					i++;
				});
			});
		};

		addquestion = function() {
			// clone the last question on the list and increment its id
			var newquestion = $("#questions tr.question:last").clone();
			var oldid = parseInt($("textarea", newquestion).attr("id").split("_")[1]);
			var newid = oldid + 1;

			// give it the new id number and wipe its text
			newquestion.attr("id", "question_" + newid);
			$("textarea", newquestion).attr("id", "question_" + newid + "_prompt").attr("name", "question_" + newid + "_prompt").val("").removeClass("error warning");

			// clear all its checkboxes and update their question numbers
			$("input.correct", newquestion).removeAttr("checked");
			var i = 0;
			$("td.correctresponses label.correct", newquestion).each(function() {
				$(this).attr("id", "question_" + newid + "_option_" + i);
				$("input.correct", this).attr("id", "question_" + newid + "_option_" + i + "_correct").attr("name", "question_" + newid + "_option_" + i + "_correct");
				i++;
			});

			// stripe it
			newquestion.removeClass("row" + (oldid % 2)).addClass("row" + (newid % 2));

			// reinstate the remove action
			$("input.removequestion", newquestion).click(removequestion);

			// add it to the list
			$("#questions").append(newquestion);
		};

		removequestion = function() {
			if ($("#questions tr.question").size() < 2) {
				alert("Can't remove the last question");
				return;
			}

			$(this).parents("tr:first").remove();

			// renumber the remaining questions
			var i = 0;
			$("#questions tr.question").each(function() {
				$(this).attr("id", "question_" + i);
				$("textarea", this).attr("id", "question_" + i + "_prompt").attr("name", "question_" + i + "_prompt");
				var j = 0;
				$("td.correctresponses label.correct", this).each(function() {
					$(this).attr("id", "question_" + i + "_option_" + j);
					$("input.correct", this).attr("id", "question_" + i + "_option_" + j + "_correct").attr("name", "question_" + i + "_option_" + j + "_correct");
					j++;
				});
				$(this).removeClass("row" + ((i + 1) % 2)).addClass("row" + (i % 2));
				i++;
			});
		};

		edititemsubmitcheck_itemspecificwarnings = function() {
			// confirm the user wanted any empty boxes
			var ok = true;
			$("input.optiontext").each(function(n) {
				if ($(this).val().length == 0) {
					$.scrollTo($(this).addClass("warning"), scrollduration, scrolloptions);
					ok = confirm("Option " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
					if (ok)
						$(this).removeClass("error warning");
					else
						return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;
			$("textarea.prompt").each(function(n) {
				if ($(this).val().length == 0) {
					$.scrollTo($(this).addClass("warning"), scrollduration, scrolloptions);
					ok = confirm("The prompt for question " + (n + 1) + " is empty -- click OK to continue regardless or cancel to edit it");
					if (ok)
						$(this).removeClass("error warning");
					else
						return false; //this is "break" in the Jquery each() pseudoloop
				}
			});
			if (!ok) return false;

			// warn about any identical options
			for (var i = 0; i < $("input.optiontext").size(); i++) {
				for (var j = i + 1; j < $("input.optiontext").size(); j++) {
					if ($("#option_" + i + "_optiontext").val() == $("#option_" + j + "_optiontext").val()) {
						$.scrollTo($("#option_" + i + "_optiontext, #option_" + j + "_optiontext").addClass("warning"), scrollduration, scrolloptions);
						ok = confirm("Options " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
						if (ok)
							$("#option_" + i + "_optiontext, #option_" + j + "_optiontext").removeClass("error warning");
						else
							break;
					}
				}
				if (!ok) break;
			}
			if (!ok) return false;

			// warn about any identical questions
			for (var i = 0; i < $("textarea.prompt").size(); i++) {
				for (var j = i + 1; j < $("textarea.prompt").size(); j++) {
					if ($("#question_" + i + "_prompt").val() == $("#question_" + j + "_prompt").val()) {
						$.scrollTo($("#question_" + i + "_prompt, #question_" + j + "_prompt").addClass("warning"), scrollduration, scrolloptions);
						ok = confirm("The prompts for questions " + (i + 1) + " and " + (j + 1) + " are the same -- click OK to continue regardless or cancel to edit them");
						if (ok)
							$("#question_" + i + "_prompt, #question_" + j + "_prompt").removeClass("error warning");
						else
							break;
					}
				}
				if (!ok) break;
			}
			if (!ok) return false;

			// confirm the user wanted only one option
			if ($("input.optiontext").size() == 1 && !confirm("There is only one option -- click OK to continue regardless or cancel to add more"))
				return false;

			// confirm the user wanted only one question
			if ($("textarea.prompt").size() == 1 && !confirm("There is only one question -- click OK to continue regardless or cancel to add more"))
				return false;

			return true;
		};

		$(document).ready(function() {
			$("#addoption").click(addoption);
			$(".removeoption").click(removeoption);
			$("#addquestion").click(addquestion);
			$(".removequestion").click(removequestion);
		});
		<?php
		return ob_get_clean();
	}

	protected function formHTML() {
		ob_start();
		?>
		<dt>Options</dt>
		<dd>
			<table id="options">
				<tr>
					<th>ID</th>
					<th>Option text</th>
					<th>Actions</th>
				</tr>
				<?php if (!isset($this->data["option_0_optiontext"])) {
					// starting from scratch -- initialize first options
					$this->data["option_0_optiontext"] = "";
					$this->data["option_1_optiontext"] = "";
				}
				for ($i = 0; array_key_exists("option_{$i}_optiontext", $this->data); $i++) { $odd = $i % 2; ?>
					<tr class="option row<?php echo $odd; ?>" id="option_<?php echo $i; ?>">
						<td class="optionid"><?php echo chr(ord("A") + $i); ?></td>
						<td><input size="48" type="text" id="option_<?php echo $i; ?>_optiontext" name="option_<?php echo $i; ?>_optiontext" class="optiontext" value="<?php echo htmlspecialchars($this->data["option_{$i}_optiontext"]); ?>"></td>
						<td><input type="button" class="removeoption" value="Remove"></td>
					</tr>
				<?php } ?>
			</table>
			<input type="button" id="addoption" value="Add option">
		</dd>

		<dt>Questions</dt>
		<dd>
			<table id="questions">
				<tr>
					<th>Question prompt</th>
					<th>Correct response</th>
					<th>Actions</th>
				</tr>
				<?php if (!isset($this->data["question_0_prompt"])) {
					// starting from scratch -- initialize first questions
					$this->data["question_0_prompt"] = "";
					$this->data["question_1_prompt"] = "";
				}
				for ($i = 0; array_key_exists("question_{$i}_prompt", $this->data); $i++) { $odd = $i % 2; ?>
					<tr class="question row<?php echo $odd; ?>" id="question_<?php echo $i; ?>">
						<td><textarea class="prompt" rows="2" cols="48" name="question_<?php echo $i; ?>_prompt" id="question_<?php echo $i; ?>_prompt"><?php if (isset($this->data["question_{$i}_prompt"])) echo htmlspecialchars($this->data["question_{$i}_prompt"]); ?></textarea></td>
						<td class="correctresponses">
							<?php for ($j = 0; array_key_exists("option_{$j}_optiontext", $this->data); $j++) { ?>
								<label class="correct" id="question_<?php echo $i; ?>_option_<?php echo $j; ?>">
									<span class="optionid"><?php echo chr(ord("A") + $j); ?></span>
									<input type="checkbox" id="question_<?php echo $i; ?>_option_<?php echo $j; ?>_correct" name="question_<?php echo $i; ?>_option_<?php echo $j; ?>_correct" class="correct"<?php if (isset($this->data["question_{$i}_option_{$j}_correct"])) { ?> checked="checked"<?php } ?>>
								</label>
							<?php } ?>
						</td>
						<td><input type="button" class="removequestion" value="Remove"></td>
					</tr>
				<?php } ?>
			</table>
			<input type="button" id="addquestion" value="Add question">
		</dd>
		<?php
		return ob_get_clean();
	}

	public function buildQTI($data = null) {
		if (!is_null($data))
			$this->data = $data;

		if (empty($this->data))
			return false;

		// container element and other metadata
		$ai = $this->initialXML();

		// response declarations
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rd = $ai->addChild("responseDeclaration");
			$rd->addAttribute("identifier", "RESPONSE_question_$q");
			$rd->addAttribute("cardinality", "multiple");
			$rd->addAttribute("baseType", "identifier");

			// build array of correct responses
			$correct = array();
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
				if (isset($this->data["question_{$q}_option_{$o}_correct"]))
					$correct[] = $o;

			// add correctResponse node only if any options are correct
			if (!empty($correct)) {
				$rd->addChild("correctResponse");
				foreach ($correct as $o)
					$rd->correctResponse->addChild("value", "question_{$q}_option_$o");
			}
		}

		// outcome declaration
		$od = $ai->addChild("outcomeDeclaration");
		$od->addAttribute("identifier", "SCORE");
		$od->addAttribute("cardinality", "single");
		$od->addAttribute("baseType", "integer");
		$od->addChild("defaultValue");
		$od->defaultValue->addChild("value", "0");

		// item body
		$ib = $ai->addChild("itemBody");

		// get stimulus and add to the XML tree
		if (isset($this->data["stimulus"]) && !empty($this->data["stimulus"])) {
			$this->data["stimulus"] = wrapindiv($this->data["stimulus"]);

			// parse it as XML
			$stimulus = stringtoxml($this->data["stimulus"], "stimulus");
			if (is_array($stimulus)) {
				// errors
				$this->errors[] = "Stimulus is not valid XML. It must not only be valid XML but valid QTI, which accepts a subset of XHTML. Details on specific issues follow:";
				$this->errors = array_merge($this->errors, $stimulus);
			} else
				simplexml_append($ib, $stimulus);
		}

		// div with class eqiat-emi
		$d = $ib->addChild("div");
		$d->addAttribute("class", "eqiat-emi");

		// list the options
		$options = "";
		for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
			$options .= "<li>" . xmlspecialchars($this->data["option_{$o}_optiontext"]) . "</li>";
		simplexml_append($d, simplexml_load_string('<ol class="emioptions">' . $options . '</ol>'));

		// questions
		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$ci = $d->addChild("choiceInteraction");
			$ci->addAttribute("maxChoices", "0");
			$ci->addAttribute("minChoices", "0");
			$ci->addAttribute("shuffle", "false");
			$ci->addAttribute("responseIdentifier", "RESPONSE_question_$q");
			$ci->addChild("prompt", $this->data["question_{$q}_prompt"]);
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++) {
				$sc = $ci->addChild("simpleChoice", chr(ord("A") + $o));
				$sc->addAttribute("identifier", "question_{$q}_option_$o");
			}
		}

		// response processing
		$rp = $ai->addChild("responseProcessing");

		// set score = 0
		$sov = $rp->addChild("setOutcomeValue");
		$sov->addAttribute("identifier", "SCORE");
		$sov->addChild("baseValue", "0")->addAttribute("baseType", "integer");

		for ($q = 0; array_key_exists("question_{$q}_prompt", $this->data); $q++) {
			$rc = $rp->addChild("responseCondition");

			// if
			$ri = $rc->addChild("responseIf");

			// build array of correct responses
			$correct = array();
			for ($o = 0; array_key_exists("option_{$o}_optiontext", $this->data); $o++)
				if (isset($this->data["question_{$q}_option_{$o}_correct"]))
					$correct[] = $o;

			// criteria for a correct answer
			if (empty($correct)) {
				// multiple response in which the correct response is to tick no 
				// boxes -- check number of responses is equal to zero
				$e = $ri->addChild("equal");
				$e->addAttribute("toleranceMode", "exact");
				$e->addChild("containerSize")->addChild("variable")->addAttribute("identifier", "RESPONSE_question_$q");
				$e->addChild("baseValue", "0")->addAttribute("baseType", "integer");
			} else {
				// otherwise, we match responses to the correctResponse above
				$m = $ri->addChild("match");
				$m->addChild("variable")->addAttribute("identifier", "RESPONSE_question_$q");
				$m->addChild("correct")->addAttribute("identifier", "RESPONSE_question_$q");
			}

			// increment score
			$sov = $ri->addChild("setOutcomeValue");
			$sov->addAttribute("identifier", "SCORE");
			$s = $sov->addChild("sum");
			$s->addChild("variable")->addAttribute("identifier", "SCORE");
			$s->addChild("baseValue", "1")->addAttribute("baseType", "integer");
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
			"stimulus"	=>	qti_get_stimulus($xml->itemBody),
		);

		// check for a div with the item class name
		$itembodycontainer = null;
		foreach ($xml->itemBody->div as $div) {
			if (!isset($div["class"]) || (string) $div["class"] != "eqiat-emi")
				continue;
			// get elements from here
			$itembodycontainer = $div;
			break;
		}
		// if there was none, get elements from itemBody
		if (is_null($itembodycontainer))
			$itembodycontainer = $xml->itemBody;

		// count the choiceInteractions
		$questioncount = count($itembodycontainer->choiceInteraction);

		// no good if there are no questions
		if ($questioncount == 0)
			return 0;

		// ensure there are the same number of responseDeclarations
		if (count($xml->responseDeclaration) != $questioncount)
			return 0;

		// ensure there are the same number of responseConditions
		if (count($xml->responseProcessing->responseCondition) != $questioncount)
			return 0;

		// check the stimulus for the options and collect them
		$options = array();
		foreach ($itembodycontainer->ol as $ol) {
			if (!isset($ol["class"]) || (string) $ol["class"] != "emioptions")
				continue;
			if (count($ol->li) < 2)
				return 0;
			foreach ($ol->li as $listitem)
				$options[] = (string) $listitem;
			break;
		}
		if (empty($options)) {
			// check for table for backwards compatibility
			foreach ($itembodycontainer->table as $table) {
				if (!isset($table["class"]) || (string) $table["class"] != "emioptions")
					continue;
				if (count($table->tbody) != 1 || count($table->tbody->tr) < 2)
					return 0;
				foreach ($table->tbody->tr as $row) {
					if (count($row->td) != 1)
						return 0;
					$options[] = (string) $row->td;
				}
				break;
			}
		}
		if (empty($options))
			return 0;

		// add options to data
		foreach ($options as $k => $option)
			$data["option_{$k}_optiontext"] = $option;

		// ensure some stuff for each question
		$q = 0;
		foreach ($itembodycontainer->choiceInteraction as $ci) {
			// questions are multiple response so fail if maxChoices is 1. don't 
			// care about minChoices
			if ((string) $ci["maxChoices"] == "1")
				return 0;

			// there are the right number of choices
			if (count($ci->simpleChoice) != count($options))
				return 0;

			// answers are ascending single letters; collect their identifiers
			$i = 0;
			$answers = array();
			foreach ($ci->simpleChoice as $sc) {
				if (strtolower((string) $sc) != chr(ord("a") + $i))
					return 0;
				$answers[] = (string) $sc["identifier"];
				$i++;
			}

			// check some responseDeclaration things
			$declarationsfound = 0;
			foreach ($xml->responseDeclaration as $rd) {
				if ((string) $rd["identifier"] != (string) $ci["responseIdentifier"])
					continue;

				$declarationsfound++;

				if (count($rd->correctResponse)) {
					// the correct response values are some of the options; 
					// collect them
					$correct = array();
					foreach ($rd->correctResponse->value as $value) {
						$answer = array_search((string) $value, $answers);
						if ($answer === false)
							return 0;
						$correct[] = $answer;
					}

					// add answers to data
					foreach ($correct as $o)
						$data["question_{$q}_option_{$o}_correct"] = "on";
				} // else an empty response is correct -- nothing to check
			}

			// there was a good responseDeclaration for this question
			if ($declarationsfound != 1)
				return 0;

			// add prompt to data
			$data["question_{$q}_prompt"] = (string) $ci->prompt;

			$q++;
		}

		// happy with that -- set data property and identifier
		$this->data = $data;
		$this->setQTIID((string) $xml["identifier"]);

		// rather strange extended matching item if it's only one question
		if ($questioncount == 1)
			return 127;

		return 255;
	}
}

?>
