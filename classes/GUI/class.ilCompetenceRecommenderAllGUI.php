<?php
declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderAllGUI
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderAllGUI: ilCompetenceRecommenderGUI
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
			case 'all':
				$this->showAll();
				break;
			default:
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
		$this->tpl->setTitle("Meine Lernempfehlungen");
		$html = "<br />";

		$competences = ilCompetenceRecommenderAlgorithm::getAllCompetencesOfUserProfile();
		foreach ($competences as $competence) {
			$barwidth = $competence["score"]/$competence["scale"]*100;
			$goalwidthmax = $competence["goal"]/$competence["scale"]*100+1;
			$goalwidthmin = $competence["goal"]/$competence["scale"]*100-1;
			$html .= "<br />".$competence["title"]."<br />";
			$html .= "<svg id=\"statSvg\" width=\"421\" height=\"151\">
					<rect x=\"50\" y=\"32\" width=\"".$goalwidthmax."%\" 
					height=\"26\" rx=\"3\" ry=\"3\" fill=\"#000000\" />
					<rect x=\"50\" y=\"30\" width=\"".$goalwidthmin."%\" 
					height=\"30\" rx=\"3\" ry=\"3\" fill=\"#FFFFFF\" />
					<rect x=\"50\" y=\"35\" width=\"".$barwidth."%\" 
					height=\"20\" rx=\"3\" ry=\"3\" fill=\"#2A7BB4\" />
					</svg>";
			$resourcearray = array();
			foreach ($competence["resources"] as $resource) {
				$obj_id = ilObject::_lookupObjectId($resource["id"]);
				$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($obj_id)));
				$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
				$card = $factory->card($link, $image);
				array_push($resourcearray, $card);
			};
			if ($resourcearray != []) {
				$deck = $factory->deck($resourcearray);
				$html .= "<br />" . $renderer->render($deck);
			}
		}

		$this->tpl->setContent("Hier sollen alle zu verbessernden Kompetenzen auftauchen". $html);
		$this->tpl->show();
		return;
	}
}