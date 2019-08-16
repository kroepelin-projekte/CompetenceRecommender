<?php
declare(strict_types=1);

include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");
include_once("./Services/Skill/classes/class.ilSkillTreeNode.php");
include_once("./Services/Skill/classes/class.ilVirtualSkillTree.php");
include_once("./Services/Skill/classes/class.ilSkillTemplateReference.php");
include_once("./Services/Skill/classes/class.ilSelfEvaluationSimpleTableGUI.php");
include_once("class.ilCompetenceRecommenderSelfEvalModalTableGUI.php");

/**
 * Class ilCompetenceRecommenderAllGUI
 *
 * Shows the All Screen of the Recommender
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderAllGUI: ilCompetenceRecommenderGUI
 * @ilCtrl_Calls ilCompetenceRecommenderAllGUI: ilPersonalSkillsGUI, ilCompetenceRecommenderSelfEvalModalTableGUI
 */
class ilCompetenceRecommenderAllGUI
{
	/**
	 * @var \ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var \ilTemplate
	 */
	protected $tpl;

	/**
	 * @var \ilLanguage
	 */
	protected $lng;

	/** @var  \ilUIFramework */
	protected $ui;

	/** @var  \ilToolbarGUI */
	protected $toolbar;

	/** @var  \ILIAS\DI\HTTPServices */
	protected $http;

	/**
	 * Constructor of the class ilDistributorTrainingsLanguagesGUI.
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
	 * Delegate incoming commands.
	 *
	 * @return 	void
	 * @throws Exception if command not known
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('all');
		$user_id = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();

		$settings = new ilCompetenceRecommenderSettings();
		$viewmode = $settings->get("viewmode", $user_id);
		if (isset($viewmode) && ($cmd == 'eval' || $cmd == 'all')) {$cmd = $viewmode;}
		if ($_GET["sortation"] == null) {$this->ctrl->setParameterByClass(\ilCompetenceRecommenderAllGUI::class, "sortation", $settings->get('sortation', $user_id));}
		switch ($cmd) {
			case 'eval':
			case 'all':
				$this->showAll();
				break;
			case 'listnew':
				$settings->set("selected_profile", "-1", $user_id);
				$this->ctrl->redirect($this, 'list');
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
			case 'post':
				$this->saveEval();
				break;
			case 'filter_showall':
			case 'filter_onlymaterial':
			case 'filter_withoutdata':
			case 'filter_hasfinished':
				$this->filterCmd($cmd);
				break;
			default:
				throw new Exception("ilCompetenceRecommenderAllGUI: Unknown command: ".$cmd);
				break;
		}

		return;
	}

	protected function filterCmd($cmd) {
		$filter_array = explode("_", $cmd);
		$this->ctrl->setParameterByClass(\ilCompetenceRecommenderAllGUI::class, $filter_array[0], $filter_array[1]);
		$this->ctrl->redirect($this, "all");
	}

	/**
	 * save the self-evaluation after submitting in the modal
	 */
	protected function saveEval() {
		$user = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();
		$base_skill_id = $_GET["basic_skill_id"];
		$skill_id = $_GET["skill_id"];
		$tref_id = $_GET["tref_id"];
		$level_id = $_POST["se"];
		ilPersonalSkill::saveSelfEvaluation($user, (int) $skill_id,
			(int) $tref_id, (int) $base_skill_id, (int) $level_id);
		sleep(1);
		ilUtil::sendSuccess($this->lng->txt("ui_uihk_comprec_self_eval_saved"), true);
		$this->ctrl->clearParametersByClass(\ilCompetenceRecommenderAllGUI::class);
		$this->ctrl->redirect($this, "all");
	}

	/**
	 * Shows the template with bars or a possibility to give data
	 * Firstly it adds the toolbar and gets all saved data
	 *
	 * @param string $viewmode the viewmode to show. Default is list, other is profile
	 * @return void
	 */
	protected function showAll(string $viewmode = "list")
	{
		$user_id = ilCompetenceRecommenderAlgorithm::getUserObj()->getId();
		$settings = new ilCompetenceRecommenderSettings();
		$factory = $this->ui->factory();

		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));
		$html = "";

		// get selected profiles
		if (isset($_GET["selected_profile"])) {
			$settings->set("selected_profile", $_GET["selected_profile"], $user_id);
			$showprofile = $_GET["selected_profile"];
		}  else if ($settings->get("selected_profile", $user_id) != null) {
			$showprofile = $settings->get("selected_profile", $user_id);
		} else {
			$showprofile = -1;
		}
		$showprofile != -1 ? $viewmode = "profiles" : $showprofile = -1;

		// set the viewmode-control
		$actions = array(
			$this->lng->txt('ui_uihk_comprec_list') => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, "listnew"),
			$this->lng->txt('ui_uihk_comprec_profiles') => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, "profiles")
		);
		$aria_label = "change_the_currently_displayed_mode";
		$view_control = $factory->viewControl()->mode($actions, $aria_label)->withActive($this->lng->txt("ui_uihk_comprec_" . $viewmode));

		// add the selector of profiles and set selected profile
		$this->tpl->addJavaScript("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/ProfileSelector.js");
		$selectprofiles = new ilSelectInputGUI($this->lng->txt("profile"), "selected_profile");
		$options = array(-1 => $this->lng->txt("ui_uihk_comprec_selector_show_all"));
		$profiles = ilCompetenceRecommenderAlgorithm::getUserProfiles();
		foreach ($profiles as $profile) {
			$options[$profile["profile_id"]] = $profile["title"];
		}
		$selectprofiles->setOptions($options);
		$selectprofiles->setValue($showprofile);

		// add the sorter for sortation, standard if diff which is the best
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

		if (isset($_GET["filter"])) {
			$settings->set("filter",$_GET["filter"], $user_id);
			$filter = $_GET["filter"];

		} else if ($settings->get("filter", $user_id) != null) {
			$filter = $settings->get("filter", $user_id);
		} else {
			$filter = "showall";
		}
		$actions = array(
			$this->lng->txt("ui_uihk_comprec_showall") => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, 'filter_showall'),
			$this->lng->txt("ui_uihk_comprec_onlymaterial") => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, 'filter_onlymaterial'),
			$this->lng->txt("ui_uihk_comprec_withoutdata") => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, 'filter_withoutdata'),
			$this->lng->txt("ui_uihk_comprec_hasfinished") => $this->ctrl->getLinkTargetByClass(\ilCompetenceRecommenderAllGUI::class, 'filter_hasfinished'),
		);
		$aria_label = "filter";
		$filter_view = $factory->viewControl()->mode($actions, $aria_label)->withActive($this->lng->txt("ui_uihk_comprec_" . $filter));

		// set toolbar
		$this->toolbar->addComponent($view_control);
		$this->toolbar->addSeparator();
		$this->toolbar->addInputItem($selectprofiles);
		$this->toolbar->addSeparator();
		$this->toolbar->addComponent($sorter);
		$this->toolbar->addSeparator();
		$this->toolbar->addComponent($filter_view);

		$filter == 'onlymaterial' ? $onlymaterial = 1 : $onlymaterial = 0;
		$filter == 'withoutdata' ? $withoutdata = 1 : $withoutdata = 0;
		$filter == 'hasfinished' ? $hasfinished = 1 : $hasfinished = 0;
		// determine the viewmode and show bars accordingly
		$checkboxarray = array("onlymaterial" => $onlymaterial, "withoutdata" => $withoutdata, "hasfinished" => $hasfinished);
		if ($viewmode == "list" && $showprofile == -1) {
			$html .= $this->showList($checkboxarray);
		} else {
			$html .= $this->showProfiles($showprofile, $checkboxarray);
		}

		// set the actual content
		$this->tpl->setContent($html);
		$this->tpl->show();
		return;
	}

	/**
	 * Shows the bars list-wise
	 *
	 * @param array $checked an array of filters with 0 if not active and 1 if active
	 * @return string the bar-html
	 * @throws ilTemplateException
	 */
	private function showList(array $checked) {
		$html = '';

		// show head (title of columns)
		$atpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBarColumnTitle.html", true, true);
		$atpl->setVariable("NAME_HEAD", $this->lng->txt('ui_uihk_comprec_competence'));
		$atpl->setVariable("BAR_HEAD", $this->lng->txt('ui_uihk_comprec_progress'));
		$html .= $atpl->get();

		// get data from algorithm
		$competences = ilCompetenceRecommenderAlgorithm::getAllCompetencesOfUserProfile();
		// show bars
		foreach ($competences as $competence) {
			if (($competence["score"] == 0 || $checked["withoutdata"] != 1)
				&& (($competence["resources"] != array() && $competence["score"] < $competence["goal"] && $competence["score"] > 0) || $checked["onlymaterial"] != 1)
				&& ($competence["score"] >= $competence["goal"] || $checked["hasfinished"] != 1)
			) {
				$html .= $this->setBar($competence);
			}
		}
		return $html;
	}

	/**
	 * Shows the bars profile-wise
	 *
	 * @param array $checked an array of filters with 0 if not active and 1 if active
	 * @return string the bar-html
	 * @throws ilTemplateException
	 */
	private function showProfiles($profile_id, array $checked) {
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$html = '';

		// get profiles the user has from algorithm
		$profiles = ilCompetenceRecommenderAlgorithm::getUserProfiles();
		foreach ($profiles as $profile) {
			if ($profile["profile_id"] == $profile_id || $profile_id == -1) {
				// get data from algorithm
				$rawcontent = ilCompetenceRecommenderAlgorithm::getCompetencesToProfile($profile);
				$sortedRaw = ilCompetenceRecommenderAlgorithm::sortCompetences($rawcontent);
				$content = "";
				// show bars
				foreach ($sortedRaw as $competence) {
					if (($competence["score"] == 0 || $checked["withoutdata"] != 1)
						&& (($competence["resources"] != array() && $competence["score"] < $competence["goal"] && $competence["score"] > 0) || $checked["onlymaterial"] != 1)
						&& ($competence["score"] >= $competence["goal"] || $checked["hasfinished"] != 1)
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

	/**
	 * Sets the actual bar html
	 *
	 * @param $competence
	 * @param string $profile_id
	 * @return string
	 * @throws ilTemplateException
	 */
	private function setBar($competence, string $profile_id = "") {
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$html = '';
		$score = $competence["score"];
		$goalat = $competence["goal"];
		$resourcearray = array();
		$oldresourcearray = array();

		// findout dropout-setting to know whether a warning has to be shown
		$settings = new ilCompetenceRecommenderSettings();
		$dropout = $settings->get("dropout_input");

		// set Parameters for self eval
		$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
		$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
		$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_skill"]);

		// show bars
		$btpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBar.html", true, true);
		$btpl->setVariable("TITLE", $competence["title"]);
		$btpl->setVariable("ID", $profile_id."_".$competence["id"]);
		$btpl->setVariable("SCORE", $score);
		$btpl->setVariable("GOALAT", $goalat);
		$btpl->setVariable("SCALE", $competence["scale"]);;
		if ($score > 0) {
			$btpl->setVariable("LASTUSEDTEXT", $this->lng->txt('ui_uihk_comprec_last_used'));
			$btpl->setVariable("LASTUSEDDATE", $competence["lastUsed"]);
			$btpl->setVariable("SELFEVALTEXT", ". " . $this->lng->txt('ui_uihk_comprec_selfevaltext'));
			$modal = $factory->modal()
				->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"], $competence["id"], $competence["base_skill"]));
			$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
			$btpl->setVariable("SELFEVALBUTTON", $renderer->render([$modalbutton, $modal]));
			if ((time()-strtotime($competence["lastUsed"]))/86400 > $dropout && $dropout > 0) {
				$btpl->setVariable("ALERTMESSAGE", $this->lng->txt("ui_uihk_comprec_alert_olddata"));
			}
			// find resources to show
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
			// show number of materials as text
			if (count($resourcearray) > 0) {
				$btpl->setVariable("NUMBEROFMATERIAL", $this->lng->txt("ui_uihk_comprec_number_material").": " . count($resourcearray));
			}
			if ($resourcearray != []) {
				$deck = $factory->deck($resourcearray);
				$btpl->setVariable("RESOURCESINFO", $this->lng->txt('ui_uihk_comprec_resources'));
				$btpl->setVariable("RESOURCES", $renderer->render($deck));
			} else if ($score < $goalat) {
				$text = $this->lng->txt('ui_uihk_comprec_no_resources');
				$modal = $factory->modal()
					->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"], $competence["id"], $competence["base_skill"]));
				$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
				$btpl->setVariable("RESOURCES", $text . " " . $renderer->render([$modalbutton, $modal]));
			}
			$btpl->setVariable("OLDRESOURCETEXT", $this->lng->txt('ui_uihk_comprec_old_resources_text'));
			if ($oldresourcearray != []) {
				$deck = $factory->deck($oldresourcearray);
				$btpl->setVariable("OLDRESOURCES", $renderer->render($deck));
			}
		} else {
			// keine Formationsdaten
			$this->ctrl->setParameter($this, 'skill_id', $competence["parent"]);
			$this->ctrl->setParameter($this, 'tref_id', $competence["id"]);
			$this->ctrl->setParameter($this, 'basic_skill_id', $competence["base_skill"]);
			$text = $this->lng->txt('ui_uihk_comprec_no_formationdata');
			$modal = $factory->modal()
				->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"], $competence["id"], $competence["base_skill"]));
			$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
			$btpl->setVariable("RESOURCES", $text . " " . $renderer->render([$modalbutton, $modal]));
			$btpl->setVariable("OLDRESOURCETEXT", $this->lng->txt('ui_uihk_comprec_resources_hidden_text'));
			// find resources to show
			foreach ($competence["resources"] as $resource) {
				$obj_id = ilObject::_lookupObjectId($resource["id"]);
				$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($resource["id"])));
				$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
				$card = $factory->card($link, $image);
				array_push($oldresourcearray, $card);
			};
			$deck = $factory->deck($oldresourcearray);
			$btpl->setVariable("OLDRESOURCES", $renderer->render($deck));
		}
		$btpl->setVariable("COLLAPSEONRESOURCE", $renderer->render($factory->glyph()->collapse()));
		$btpl->setVariable("COLLAPSERESOURCE", $renderer->render($factory->glyph()->expand()));
		$btpl->setVariable("COLLAPSEON", $renderer->render($factory->glyph()->collapse()));
		$btpl->setVariable("COLLAPSE", $renderer->render($factory->glyph()->expand()));
		$html .= $btpl->get();
		return $html;
	}

	/**
	 * shows modal for self-evaluation
	 *
	 * @param $skill_id
	 * @param $tref_id
	 * @param $base_skill_id
	 * @return \ILIAS\UI\Component\Legacy\Legacy
	 */
	private function getModalContent($skill_id, $tref_id, $base_skill_id) {
		$factory = $this->ui->factory();

		$this->ctrl->saveParameter($skill_id, "skill_id");
		$this->ctrl->saveParameter($base_skill_id, "basic_skill_id");
		$this->ctrl->saveParameter($tref_id, "tref_id");

		// basic skill selection
		$vtree = new ilVirtualSkillTree();
		$vtref_id = 0;
		if (ilSkillTreeNode::_lookupType((int) $skill_id) == "sktr")
		{
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
		if ($tref_id == 0) {$cur_basic_skill_id = $base_skill_id;}

		$this->ctrl->setParameter($this, "basic_skill_id", $cur_basic_skill_id);
		$this->ctrl->setParameter($this, "skill_id", $skill_id);
		$this->ctrl->setParameter($this, "tref_id", $tref_id);

		// table
		$tab = new ilCompetenceRecommenderSelfEvalModalTableGUI($this, "all",
			(int) $skill_id, (int) $tref_id, $cur_basic_skill_id);
		$html = $tab->getHTML();

		$modalContent = $factory->legacy($html);
		return $modalContent;
	}
}