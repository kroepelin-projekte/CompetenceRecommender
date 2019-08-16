<?php
declare(strict_types=1);

include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");
include_once("./Services/Skill/classes/class.ilSkillTreeNode.php");
include_once("./Services/Skill/classes/class.ilVirtualSkillTree.php");
include_once("./Services/Skill/classes/class.ilSkillTemplateReference.php");
include_once("./Services/Skill/classes/class.ilSelfEvaluationSimpleTableGUI.php");

/**
 * Class ilCompetenceRecommenderActivitiesGUI
 *
 * Shows the Activities Screen (Main Screen) of the Recommender
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderActivitiesGUI: ilCompetenceRecommenderGUI
 * @ilCtrl_Calls ilCompetenceRecommenderAllGUI: ilPersonalSkillsGUI
 *
 */
class ilCompetenceRecommenderActivitiesGUI
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

	/** @var \ilUIFramework */
	protected $ui;

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
	}

	/**
	 * Delegate incoming commands.
	 *
	 * @return 	void
	 * @throws Exception if command not known
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('show');
		switch ($cmd) {
			case 'show':
				$this->showDashboard();
				break;
			case 'saveSelfEvaluation':
				$this->saveEval();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderActivitiesGUI: Unknown command: ".$cmd);
				break;
		}

		return;
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
		$this->ctrl->clearParametersByClass(\ilCompetenceRecommenderActivitiesGUI::class);
		$this->ctrl->redirect($this, "show");
	}

	/**
	 * Shows the template with bars or a possibility to give data
	 *
	 * @return void
	 * @throws ilTemplateException
	 */
	protected function showDashboard()
	{
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		// standard <= 5 bars are shown. More with a button setting "num" in URL
		isset($_GET["num"]) ? $n = $_GET["num"] : $n = 5;
		$max_n = \ilCompetenceRecommenderAlgorithm::getNumberOfCompetencesForActivities();

		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));
		$html = "";

		// findout dropout-setting to know whether a warning has to be shown
		$settings = new ilCompetenceRecommenderSettings();
		$dropout = $settings->get("dropout_input");

		// get data from algorithm
		$competences = ilCompetenceRecommenderAlgorithm::getNCompetencesOfUserProfile(intval($n));
		// if no data available show init obj if possible, else show self-eval
		if ($competences == []) {
			$resourcearray = array();
			$init_obj = \ilCompetenceRecommenderAlgorithm::getInitObjects();
			if ($init_obj != array()) {
				foreach ($init_obj as $object) {
					$obj_id = ilObject::_lookupObjectId($object["id"]);
					$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($object["id"])));
					$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
					$card = $factory->card($link, $image);
					array_push($resourcearray, $card);
				}
				$deck = $factory->deck($resourcearray);
				$text = $this->lng->txt('ui_uihk_comprec_no_formationdata_init_obj');
				$this->tpl->setContent($text . " <br /> " . $renderer->render($deck));
			} else {
				$html = $this->lng->txt('ui_uihk_comprec_no_formationdata') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
						$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval')));
				$this->tpl->setContent($html);
			}
			$this->tpl->show();
			return;
		}
		// show head (title of columns)
		$atpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBarColumnTitle.html", true, true);
		$atpl->setVariable("NAME_HEAD", $this->lng->txt('ui_uihk_comprec_competence'));
		$atpl->setVariable("BAR_HEAD", $this->lng->txt('ui_uihk_comprec_progress'));
		$html .= $atpl->get();
		// show bars
		foreach ($competences as $competence) {
			// set parameters for self eval
			$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
			$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
			$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_skill"]);
			$this->ctrl->setParameter($this, 'skill_id', $competence["parent"]);
			$this->ctrl->setParameter($this, 'tref_id', $competence["id"]);
			$this->ctrl->setParameter($this, 'basic_skill_id', $competence["base_skill"]);

			$score = $competence["score"];
			$goalat = $competence["goal"];
			$resourcearray = array();
			$oldresourcearray = array();
			$btpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBar.html", true, true);
			$btpl->setVariable("TITLE", $competence["title"]);
			$btpl->setVariable("ID", $competence["id"]);
			$btpl->setVariable("SCORE", $score);
			$btpl->setVariable("GOALAT", $goalat);
			$btpl->setVariable("SCALE", $competence["scale"]);
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
			$btpl->setVariable("COLLAPSEONRESOURCE", $renderer->render($factory->glyph()->collapse()));
			$btpl->setVariable("COLLAPSERESOURCE", $renderer->render($factory->glyph()->expand()));
			$btpl->setVariable("COLLAPSEON", $renderer->render($factory->glyph()->collapse()));
			$btpl->setVariable("COLLAPSE", $renderer->render($factory->glyph()->expand()));
			$html .= $btpl->get();
		}

		// set the show more button
		if ($n < $max_n) {
			$this->ctrl->setParameterByClass(ilCompetenceRecommenderActivitiesGUI::class, "num", $n + 1);
			$html .= $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_button_show_more'), $this->ctrl->getLinkTarget($this, "show")));
		}

		// show
		$this->tpl->setContent($html);
		$this->tpl->show();
		return;
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
