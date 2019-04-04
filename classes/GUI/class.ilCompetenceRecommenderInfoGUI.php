<?php
declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderInfoGUI
 *
 * Shows the Info Screen of the Recommender
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
	 * @var \ilTemplate
	 */
	protected $tpl;

	/**
	 * @var \ilLanguage
	 */
	protected $lng;

	/** @var  \ilUIFramework */
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
	 * Delegate incoming commands.
	 *
	 * @return 	void
	 * @throws Exception if command not known
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('info');
		switch ($cmd) {
			case 'info':
				$this->showInfo();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderInfoGUI: Unknown command: ".$cmd);
				break;
		}

		return;
	}

	/**
	 * Displays the info text set by a language variable
	 *
	 * @return	void
	 */
	protected function showInfo()
	{
		$this->tpl->getStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));

		$this->tpl->setContent($this->lng->txt('ui_uihk_comprec_info_text'));
		$this->tpl->show();
		return;
	}
}