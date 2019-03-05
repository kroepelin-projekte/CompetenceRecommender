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
		$this->tpl->setContent("All");
		$this->tpl->show();
	}
}