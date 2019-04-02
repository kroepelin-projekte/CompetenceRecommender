<?php
declare(strict_types=1);

include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");

/**
 * Class ilCompetenceRecommenderAllGUI
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderAllGUI: ilCompetenceRecommenderGUI
 * @ilCtrl_Calls ilCompetenceRecommenderAllGUI: ilPersonalSkillsGUI
 */
class ilCompetenceRecommenderAllGUI
{
	/**
	 * @var \ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/** @var  ilUIFramework */
	protected $ui;

	/** @var  ilToolbarGUI */
	protected $toolbar;

	/** @var  \ILIAS\DI\HTTPServices */
	protected $http;

	/**
	 * Constructor of the class ilDistributorTrainingsLanguagesGUI.
	 *
	 * @return 	void
	 */
	public function __construct()
	{
		global $DIC;
		$this->tpl = $DIC['tpl'];
		$this->lng = $DIC['lng'];
		$this->ctrl = $DIC['ilCtrl'];
		$this->ui = $DIC->ui();
		$this->toolbar = $DIC->toolbar();
		$this->http = $DIC->http();
	}

	/**
	 * Delegate incoming comands.
	 *
	 * @return 	void
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('all');
		$user_id = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();

		$settings = new ilCompetenceRecommenderSettings();
		$viewmode = $settings->get("viewmode", $user_id);
		if (isset($viewmode) && ($cmd == 'eval' || $cmd == 'all')) {$cmd = $viewmode;}
		switch ($cmd) {
			case 'eval':
			case 'all':
				$this->showAll();
				break;
			case 'list':
				$settings->set("viewmode", 'list', $user_id);
				$this->showAll('list');
				break;
			case 'profiles':
				$settings->set("viewmode", 'profiles', $user_id);
				$this->showAll('profiles');
				break;
			case 'saveSelfEvaluation':
				$this->saveEval();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderAllGUI: Unknown command: ".$cmd);
				break;
		}

		return;
	}

	protected function saveEval() {
		$user = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();
		$base_skill_id = $_GET["basic_skill_id"];
		$skill_id = $_GET["skill_id"];
		$tref_id = $_GET["tref_id"];
		$level_id = $_POST["se"];
		ilPersonalSkill::saveSelfEvaluation($user, (int) $skill_id,
			(int) $tref_id, (int) $base_skill_id, (int) $level_id);
		ilUtil::sendSuccess($this->lng->txt("self_eval_saved"), true);
		$this->ctrl->redirect($this);
	}

	/**
	 * Displays the settings form
	 *
	 * @return	void
	 */
	protected function showAll(string $viewmode = "list")
	{
		$user_id = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();

		$settings = new ilCompetenceRecommenderSettings();
		if (isset($_GET["selected_profile"])) {
			$settings->set("selected_profile", $_GET["selected_profile"], $user_id);
			$showprofile = $_GET["selected_profile"];
		}  else if ($settings->get("selected_profile", $user_id) != null) {
			$showprofile = $settings->get("selected_profile", $user_id);
		} else {
			$showprofile = -1;
		}
		$showprofile != -1 ? $viewmode = "profiles" : $showprofile = -1;

		$factory = $this->ui->factory();

		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));
		$html = "";

		$actions = array(
			$this->lng->txt('ui_uihk_comprec_list') => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, "list"),
			$this->lng->txt('ui_uihk_comprec_profiles') => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, "profiles")
		);

		$aria_label = "change_the_currently_displayed_mode";
		$view_control = $factory->viewControl()->mode($actions, $aria_label)->withActive($this->lng->txt("ui_uihk_comprec_" . $viewmode));

		$this->tpl->addJavaScript("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/ProfileSelector.js");

		$selectprofiles = new ilSelectInputGUI($this->lng->txt("profile"), "selected_profile");
		$options = array(-1 => "alle anzeigen");
		$profiles = ilCompetenceRecommenderAlgorithm::getUserProfiles();
		foreach ($profiles as $profile) {
			$options[$profile["profile_id"]] = $profile["title"];
		}
		$selectprofiles->setOptions($options);
		$selectprofiles->setValue($showprofile);

		$sortoptions = array(
			'diff' => $this->lng->txt("ui_uihk_comprec_sort_best"),
			'percentage' => $this->lng->txt("ui_uihk_comprec_sort_percentage"),
			'lastUsed' => $this->lng->txt("ui_uihk_comprec_sort_lastUsed"),
			'oldest' => $this->lng->txt("ui_uihk_comprec_sort_oldest")
		);
		if (isset($_GET["sortation"])) {
			$settings->set("sortation",$_GET["sortation"], $user_id);
			$sortation = $_GET["sortation"];

		} else if ($settings->get("sortation", $user_id) != null) {
			$sortation = $settings->get("sortation", $user_id);
		} else {
			$sortation = "diff";
		}
		$sorter = $factory->viewControl()->sortation($sortoptions)
			->withTargetURL($this->http->request()->getRequestTarget(), 'sortation')
			->withLabel($this->lng->txt('ui_uihk_comprec_sortation_label') . ": " . $sortoptions[$sortation]);

		$this->tpl->addJavaScript("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/CheckboxChecker.js");

		if (isset($_GET["onlymaterial"])) {
			$settings->set("onlymaterial", $_GET["onlymaterial"], $user_id);
			$onlymaterial = $_GET["onlymaterial"];
		} else if ($settings->get("onlymaterial", $user_id)) {
			$onlymaterial = $settings->get("onlymaterial", $user_id);
		} else {
			$onlymaterial = "0";
		}
		$checkbox_material = new ilCheckboxInputGUI($this->lng->txt("ui_uihk_comprec_onlymaterial"), "onlymaterial");
		$checkbox_material->setOptionTitle($this->lng->txt("ui_uihk_comprec_onlymaterial"));
		$checkbox_material->setChecked($onlymaterial == "1");

		if (isset($_GET["withoutdata"])) {
			$settings->set("withoutdata", $_GET["withoutdata"], $user_id);
			$withoutdata = $_GET["withoutdata"];
		} else if ($settings->get("withoutdata", $user_id)) {
			$withoutdata = $settings->get("withoutdata", $user_id);
		} else {
			$withoutdata = "0";
		}
		$checkbox_data = new ilCheckboxInputGUI($this->lng->txt("ui_uihk_comprec_withoutdata"),"withoutdata");
		$checkbox_data->setOptionTitle($this->lng->txt("ui_uihk_comprec_withoutdata"));
		$checkbox_data->setChecked($withoutdata == "1");

		if (isset($_GET["notfinished"])) {
			$settings->set("notfinished", $_GET["notfinished"], $user_id);
			$notfinished = $_GET["notfinished"];
		} else if ($settings->get("notfinished", $user_id)) {
			$notfinished = $settings->get("notfinished", $user_id);
		} else {
			$notfinished = "0";
		}
		$checkbox_finished = new ilCheckboxInputGUI($this->lng->txt("ui_uihk_comprec_notfinished"), "notfinished");
		$checkbox_finished->setOptionTitle($this->lng->txt("ui_uihk_comprec_notfinished"));
		$checkbox_finished->setChecked($notfinished == "1");

		$this->toolbar->addComponent($view_control);
		$this->toolbar->addSeparator();
		$this->toolbar->addInputItem($selectprofiles);
		$this->toolbar->addSeparator();
		$this->toolbar->addComponent($sorter);
		$this->toolbar->addSeparator();
		$this->toolbar->addInputItem($checkbox_material);
		$this->toolbar->addInputItem($checkbox_data);
		$this->toolbar->addInputItem($checkbox_finished);

		$checkboxarray = array("onlymaterial" => $onlymaterial, "withoutdata" => $withoutdata, "notfinished" => $notfinished);
		if ($viewmode == "list" && $showprofile == -1) {
			$html .= $this->showList($checkboxarray);
		} else {
			$html .= $this->showProfiles($showprofile, $checkboxarray);
		}
		$this->tpl->setContent($html);
		$this->tpl->show();
		return;
	}

	private function showList(array $checked) {
		$html = '';
		$atpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBarColumnTitle.html", true, true);
		$atpl->setVariable("NAME_HEAD", $this->lng->txt('ui_uihk_comprec_competence'));
		$atpl->setVariable("BAR_HEAD", $this->lng->txt('ui_uihk_comprec_progress'));
		$html .= $atpl->get();

		$competences = ilCompetenceRecommenderAlgorithm::getAllCompetencesOfUserProfile();
		foreach ($competences as $competence) {
			if (($competence["score"] != 0 || ($_GET["withoutdata"] != 1 && $checked["withoutdata"] != 1))
				&& !($competence["resources"] == array() && ($_GET["onlymaterial"] == 1 || $checked["onlymaterial"] == 1))
				&& ($competence["score"] < $competence["goal"] || ($_GET["notfinished"] != 1 && $checked["notfinished"] != 1))
			) {
				$html .= $this->setBar($competence);
			}
		}
		return $html;
	}

	private function showProfiles($profile_id, array $checked) {
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$html = '';
		$profiles = ilCompetenceRecommenderAlgorithm::getUserProfiles();
		foreach ($profiles as $profile) {
			if ($profile["profile_id"] == $profile_id || $profile_id == -1) {
				$rawcontent = ilCompetenceRecommenderAlgorithm::getCompetencesToProfile($profile);
				$sortedRaw = ilCompetenceRecommenderAlgorithm::sortCompetences($rawcontent);
				$content = "";
				foreach ($sortedRaw as $competence) {
					if (($competence["score"] != 0 || ($_GET["withoutdata"] != 1 && $checked["withoutdata"] != 1))
						&& !($competence["resources"] == array() && ($_GET["onlymaterial"] == 1 || $checked["onlymaterial"] == 1))
						&& ($competence["score"] < $competence["goal"] || ($_GET["notfinished"] != 1 && $checked["notfinished"] != 1))
					) {
						$content .= $this->setBar($competence, $profile["profile_id"]);
					}
				}
				$panel = $factory->panel()->standard($profile["title"], $factory->legacy($content));
				$html .= $renderer->render($panel);
			}
		}
		return $html;
	}

	private function setBar($competence, string $profile_id = "") {
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$settings = new ilCompetenceRecommenderSettings();
		$dropout = $settings->get("dropout_input");

		$html = '';
		$score = $competence["score"];
		$goalat = $competence["goal"];
		$resourcearray = array();
		$oldresourcearray = array();
		$btpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBar.html", true, true);
		$btpl->setVariable("TITLE", $competence["title"]);
		$btpl->setVariable("ID", $profile_id."_".$competence["id"]);
		$btpl->setVariable("SCORE", $score);
		$btpl->setVariable("GOALAT", $goalat);
		$btpl->setVariable("SCALE", $competence["scale"]);
		if ($score > 0) {
			$btpl->setVariable("LASTUSEDTEXT", $this->lng->txt('ui_uihk_comprec_last_used'));
			$btpl->setVariable("LASTUSEDDATE", $competence["lastUsed"]);
			if (count($competence["resources"]) > 0) {
				$btpl->setVariable("NUMBEROFMATERIAL", $this->lng->txt("ui_uihk_comprec_number_material").": " . count($competence["resources"]));
			}
			if ((time()-strtotime($competence["lastUsed"]))/86400 > $dropout && $dropout > 0) {
				$btpl->setVariable("ALERTMESSAGE", $this->lng->txt("ui_uihk_comprec_alert_olddata"));
			}
			foreach ($competence["resources"] as $resource) {
				$obj_id = ilObject::_lookupObjectId($resource["id"]);
				$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($resource["id"])));
				$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
				$card = $factory->card($link, $image);
				if ($resource["level"] > $score) {
					array_push($resourcearray, $card);
				} else {
					array_push($oldresourcearray, $card);
				}
			};
			if ($resourcearray != []) {
				$deck = $factory->deck($resourcearray);
				$btpl->setVariable("RESOURCESINFO", $this->lng->txt('ui_uihk_comprec_resources'));
				$btpl->setVariable("RESOURCES", $renderer->render($deck));
			} else if ($score < $goalat) {
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_id"]);
				$text = $this->lng->txt('ui_uihk_comprec_no_resources');
				$modal = $factory->modal()->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"], $competence["id"], $competence["base_id"]));
				$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
				$btpl->setVariable("RESOURCES", $text . " " . $renderer->render([$modalbutton, $modal]));
			}
			$btpl->setVariable("OLDRESOURCETEXT", $this->lng->txt('ui_uihk_comprec_old_resources_text'));
			if ($oldresourcearray != []) {
				$deck = $factory->deck($oldresourcearray);
				$btpl->setVariable("OLDRESOURCES", $renderer->render($deck));
			}
			$btpl->setVariable("COLLAPSEONRESOURCE", $renderer->render($factory->glyph()->collapse()));
			$btpl->setVariable("COLLAPSERESOURCE", $renderer->render($factory->glyph()->expand()));
		} else {
			// keine Formationsdaten
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_id"]);
				$text = $this->lng->txt('ui_uihk_comprec_no_formationdata');
				$modal = $factory->modal()->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"], $competence["id"], $competence["base_id"]));
				$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
				$btpl->setVariable("RESOURCES", $text . " " . $renderer->render([$modalbutton, $modal]));
		}
		$btpl->setVariable("COLLAPSEON", $renderer->render($factory->glyph()->collapse()));
		$btpl->setVariable("COLLAPSE", $renderer->render($factory->glyph()->expand()));
		$html .= $btpl->get();
		return $html;
	}

	private function getModalContent($skill_id, $tref_id, $base_skill_id) {
		$factory = $this->ui->factory();

		$this->ctrl->saveParameter($skill_id, "skill_id");
		$this->ctrl->saveParameter($base_skill_id, "basic_skill_id");
		$this->ctrl->saveParameter($tref_id, "tref_id");

		// basic skill selection
		include_once("./Services/Skill/classes/class.ilSkillTreeNode.php");
		include_once("./Services/Skill/classes/class.ilVirtualSkillTree.php");
		$vtree = new ilVirtualSkillTree();
		$vtref_id = 0;
		if (ilSkillTreeNode::_lookupType((int) $skill_id) == "sktr")
		{
			include_once("./Services/Skill/classes/class.ilSkillTemplateReference.php");
			$vtref_id = $skill_id;
			$skill_id = ilSkillTemplateReference::_lookupTemplateId($skill_id);
		}
		$bs = $vtree->getSubTreeForCSkillId($skill_id.":".$vtref_id, true);


		$options = array();
		foreach ($bs as $b)
		{
			$options[$b["skill_id"]] = ilSkillTreeNode::_lookupTitle($b["skill_id"]);
		}

		$cur_basic_skill_id = ((int) $_POST["basic_skill_id"] > 0)
			? (int) $_POST["basic_skill_id"]
			: (((int) $_GET["basic_skill_id"] > 0)
				? (int) $_GET["basic_skill_id"]
				: key($options));

		$this->ctrl->setParameter($this, "basic_skill_id", $cur_basic_skill_id);
		$this->ctrl->setParameter($this, "skill_id", $skill_id);
		$this->ctrl->setParameter($this, "tref_id", $tref_id);

		// table
		include_once("./Services/Skill/classes/class.ilSelfEvaluationSimpleTableGUI.php");
		$tab = new ilSelfEvaluationSimpleTableGUI($this, "selfEvaluation",
			(int) $skill_id, (int) $tref_id, $cur_basic_skill_id);
		$html = $tab->getHTML();

		$html = str_replace("-skmg_skill_level-", $this->lng->txt("ui_uihk_comprec_skmg_skill_level"), $html);
		$html = str_replace("-skmg_no_skills-", $this->lng->txt("ui_uihk_comprec_skmg_no_skills"), $html);

		$modalContent = $factory->legacy($html);
		return $modalContent;
	}
}