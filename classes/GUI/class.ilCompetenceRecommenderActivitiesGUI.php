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
			$html = $this->lng->txt('ui_uihk_comprec_no_formationdata') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
					$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval')));
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
				$link = $this->lng->txt('ui_uihk_comprec_no_resources') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
						$this->ctrl->getLinkTargetByClass([ilPersonalDesktopGUI::class, ilPersonalSkillsGUI::class], 'selfEvaluation')));
				$modal = $factory->modal()->roundtrip("Title", $factory->legacy("Content"));
				$modalbutton = $renderer->render($factory->button()->standard("Test", "")->withOnClick($modal->getShowSignal()));
				$btpl->setVariable("RESOURCES", $link);
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
}
