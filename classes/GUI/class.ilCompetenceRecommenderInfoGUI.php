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
	protected ilCtrl $ctrl;
	protected ilGlobalTemplateInterface $tpl;
	protected ilLanguage $lng;

    /**
	 * Constructor of the class ilDistributorTrainingsLanguagesGUI.
	 *
	 * @return void
	 */
	public function __construct()
	{
		global $DIC;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
	}

	/**
	 * Delegate incoming commands.
	 *
	 * @return void
	 * @throws Exception if command not known
	 */
	public function executeCommand(): void
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
    }

	/**
	 * Displays the info text set by a language variable
	 *
	 * @return	void
	 */
	protected function showInfo(): void
	{
		$this->tpl->loadStandardTemplate();
		$this->tpl->setTitle($this->lng->txt('ui_uihk_comprec_plugin_title'));

		$this->tpl->setContent($this->lng->txt('ui_uihk_comprec_info_text'));
		$this->tpl->printToStdout();
	}
}
