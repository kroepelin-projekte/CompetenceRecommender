<?php
declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderActivitiesGUI
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderActivitiesGUI: ilCompetenceRecommenderGUI
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
		$this->tpl->setTitle("Meine Lernempfehlungen");

		$items = $renderer->render($factory->image()->standard("Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/BildMittel.png", "Platzhalter Item"));
		$items = $items ."<br/>". $renderer->render($factory->image()->standard("Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/BildMittel.png", "Platzhalter Item"));
		$items = $items ."<br/>". $renderer->render($factory->image()->standard("Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/BildMittel.png", "Platzhalter Item"));

		$this->tpl->setContent("Hier sollen die konkreten Lernempfehlungen des Kurses erscheinen"."<br/>". $items);
		$this->tpl->show();
		return;
	}
}