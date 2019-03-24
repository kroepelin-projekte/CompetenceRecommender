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
		$cmd = $this->ctrl->getCmd('all');
		switch ($cmd) {
			case 'eval':
			case 'all':
				$this->showAll();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderAllGUI: Unknown command: ".$cmd);
				break;
		}

		return;
	}

	/**
	 * Displays the settings form
	 *
	 * @return	void
	 */
	protected function showAll()
	{
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));
		$html = "";

		$competences = ilCompetenceRecommenderAlgorithm::getAllCompetencesOfUserProfile();
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
			if ($score > 0) {
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
					$btpl->setVariable("RESOURCES", $renderer->render($deck));
				} else if ($score < $goalat) {
					$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
					$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
					$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_id"]);
					$btpl->setVariable("RESOURCES",
						$link = $this->lng->txt('ui_uihk_comprec_no_resources') . " " . $renderer->render($factory->link()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
								$this->ctrl->getLinkTargetByClass([ilPersonalDesktopGUI::class, ilPersonalSkillsGUI::class], 'selfEvaluation'))));
				}
				$btpl->setVariable("OLDRESOURCETEXT", $this->lng->txt('ui_uihk_comprec_old_resources_text'));
				if ($oldresourcearray != []) {
					$deck = $factory->deck($oldresourcearray);
					$btpl->setVariable("OLDRESOURCES", $renderer->render($deck));
				}
				$btpl->setVariable("COLLAPSEONRESOURCE", $renderer->render($factory->glyph()->collapse()));
				$btpl->setVariable("COLLAPSERESOURCE", $renderer->render($factory->glyph()->expand()));
			} else {
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'skill_id', $competence["parent"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'tref_id', $competence["id"]);
				$this->ctrl->setParameterByClass(ilPersonalSkillsGUI::class, 'basic_skill_id', $competence["base_id"]);
				$btpl->setVariable("RESOURCES",
					$link = $this->lng->txt('ui_uihk_comprec_no_formationdata') . " " . $renderer->render($factory->link()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
							$this->ctrl->getLinkTargetByClass([ilPersonalDesktopGUI::class, ilPersonalSkillsGUI::class], 'selfEvaluation'))));
			}
			$btpl->setVariable("COLLAPSEON", $renderer->render($factory->glyph()->collapse()));
			$btpl->setVariable("COLLAPSE", $renderer->render($factory->glyph()->expand()));
			$html .= $btpl->get();
		}


		$this->tpl->setContent($html);
		$this->tpl->show();
		return;
	}
}