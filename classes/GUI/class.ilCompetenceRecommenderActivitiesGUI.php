<?php
declare(strict_types=1);

include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");

/**
 * Class ilCompetenceRecommenderActivitiesGUI
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
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/** @var  ilUIFramework */
	protected $ui;

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
	}

	/**
	 * Delegate incoming comands.
	 *
	 * @return 	void
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('show');
		switch ($cmd) {
			case 'show':
				$this->showDashboard();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderActivitiesGUI: Unknown command: ".$cmd);
				break;
		}

		return;
	}

	/**
	 * Displays the settings form
	 *
	 * @return	void
	 */
	protected function showDashboard()
	{
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));
		$html = "";

		$competences = ilCompetenceRecommenderAlgorithm::getNCompetencesOfUserProfile(5);
		if ($competences == []) {
			$text = $this->lng->txt('ui_uihk_comprec_no_formationdata');
			$modal = $factory->modal()->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"],$competence["id"],$competence["base_id"]));
			$modalbutton = $factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'), "")->withOnClick($modal->getShowSignal());
			$html = $text . " " . $renderer->render([$modalbutton, $modal]);
			$this->tpl->setContent($html);
			$this->tpl->show();
			return;
		}
		$atpl = new ilTemplate("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/tpl.comprecBarColumnTitle.html", true, true);
		$atpl->setVariable("NAME_HEAD", $this->lng->txt('ui_uihk_comprec_competence'));
		$atpl->setVariable("BAR_HEAD", $this->lng->txt('ui_uihk_comprec_progress'));
		$html .= $atpl->get();
		foreach ($competences as $competence) {
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
				$text = $this->lng->txt('ui_uihk_comprec_no_formationdata');
				$modal = $factory->modal()->roundtrip($this->lng->txt('ui_uihk_comprec_self_eval'), $this->getModalContent($competence["parent"],$competence["id"],$competence["base_id"]));
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

		$this->tpl->setContent($html);
		$this->tpl->show();
		return;
	}

	private function getModalContent($skill_id, $tref_id, $base_skill_id)
	{
		$factory = $this->ui->factory();

		$this->ctrl->saveParameter($skill_id, "skill_id");
		$this->ctrl->saveParameter($base_skill_id, "basic_skill_id");
		$this->ctrl->saveParameter($tref_id, "tref_id");

		// basic skill selection
		include_once("./Services/Skill/classes/class.ilSkillTreeNode.php");
		include_once("./Services/Skill/classes/class.ilVirtualSkillTree.php");
		$vtree = new ilVirtualSkillTree();
		$vtref_id = 0;
		if (ilSkillTreeNode::_lookupType((int)$skill_id) == "sktr") {
			include_once("./Services/Skill/classes/class.ilSkillTemplateReference.php");
			$vtref_id = $skill_id;
			$skill_id = ilSkillTemplateReference::_lookupTemplateId($skill_id);
		}
		$bs = $vtree->getSubTreeForCSkillId($skill_id . ":" . $vtref_id, true);


		$options = array();
		foreach ($bs as $b) {
			$options[$b["skill_id"]] = ilSkillTreeNode::_lookupTitle($b["skill_id"]);
		}

		$cur_basic_skill_id = ((int)$_POST["basic_skill_id"] > 0)
			? (int)$_POST["basic_skill_id"]
			: (((int)$_GET["basic_skill_id"] > 0)
				? (int)$_GET["basic_skill_id"]
				: key($options));

		$this->ctrl->setParameter($this, "basic_skill_id", $cur_basic_skill_id);
		$this->ctrl->setParameter($this, "skill_id", $skill_id);
		$this->ctrl->setParameter($this, "tref_id", $tref_id);

		// table
		include_once("./Services/Skill/classes/class.ilSelfEvaluationSimpleTableGUI.php");
		$tab = new ilSelfEvaluationSimpleTableGUI($this, "selfEvaluation",
			(int)$skill_id, (int)$tref_id, $cur_basic_skill_id);
		$html = $tab->getHTML();

		$html = str_replace("-skmg_skill_level-", $this->lng->txt("ui_uihk_comprec_skmg_skill_level"), $html);
		$html = str_replace("-skmg_no_skills-", $this->lng->txt("ui_uihk_comprec_skmg_no_skills"), $html);

		$modalContent = $factory->legacy($html);
		return $modalContent;
	}
}
