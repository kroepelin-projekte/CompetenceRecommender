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
		$this->tpl->setContent("Info");
	}
}