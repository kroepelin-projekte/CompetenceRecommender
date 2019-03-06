<?php
declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderInfoGUI
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderInfoGUI: ilCompetenceRecommenderGUI
 */
class ilCompetenceRecommenderInfoGUI
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
		$cmd = $this->ctrl->getCmd('info');
		switch ($cmd) {
			case 'info':
				$this->showInfo();
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
	protected function showInfo()
	{
		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle("Meine Lernempfehlungen");

		$this->tpl->setContent("Hier erscheint Information zum Recommender");
		$this->tpl->show();
		return;
	}
}