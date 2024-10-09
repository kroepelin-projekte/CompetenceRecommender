<?php

declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderGUI
 *
 * Sets Tabs in Main Screen of the Recommender and delegates the commands accordingly
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderGUI: ilCompetenceRecommenderUIHookGUI, ilUIPluginRouterGUI
 * @ilCtrl_Calls ilCompetenceRecommenderGUI: ilCompetenceRecommenderActivitiesGUI, ilCompetenceRecommenderAllGUI, ilCompetenceRecommenderInfoGUI
 */
class ilCompetenceRecommenderGUI
{
    // todo entfernen?
	// const PLUGIN_CLASS_NAME = ilCompetenceRecommenderPlugin::class;

    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    public ilGlobalTemplateInterface $tpl;
    public ilCompetenceRecommenderPlugin $pl;
	protected ilLanguage $lng;
    public ilDBInterface $db;

    /**
	 * CompetenceRecommenderGUI constructor
	 */
	public function __construct() {
		global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->tpl = $DIC->ui()->mainTemplate();
		$this->db = $DIC->database();
		$this->pl = ilCompetenceRecommenderPlugin::getInstance();
		$this->lng = $DIC->language();
	}

	/**
	 * Delegates incoming commands
	 *
	 * @return bool
	 * @throws Exception if command is not known
	 */
	public function executeCommand(): void
    {
		if (!$this->pl->isActive()) {
            $this->tpl->setOnScreenMessage('failure', 'Activate Plugin first', true);

            // todo testen
            $this->ctrl->redirectToURL('index.php');
		}
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		$this->setTabs();
		$next_class = $this->ctrl->getNextClass();

		switch ($next_class) {
			case 'ilcompetencerecommenderactivitiesgui':
				$this->forwardShow();
				break;
			case 'ilcompetencerecommenderallgui':
				$this->forwardAll();
				break;
			case 'ilcompetencerecommenderinfogui':
				$this->forwardInfo();
				break;
			default:
				switch ($cmd) {
					case 'dashboard':
					case 'show':
						$this->forwardShow();
						break;
					case 'info':
						$this->forwardInfo();
						break;
					case 'eval':
						$this->forwardAll();
						break;
					default:
						throw new Exception("ilCompetenceRecommenderGUI: Unknown command: ".$cmd);
						break;
				}
		}
	}

	/**
	 * Get standard command.
	 *
	 * @return 	string
	 */
	public function getStandardCommand(): string
	{
		return 'dashboard';
	}

	/**
	 * forwards to standard view, made by the activities gui
	 *
     * @return void
	 * @throws ilCtrlException
	 */
	protected function forwardShow(): void
	{
		$this->tabs->activateTab("show");
		$gui = new \ilCompetenceRecommenderActivitiesGUI();
		$this->ctrl->forwardCommand($gui);
	}

	/**
	 * forwards to all view, made by the all gui
	 *
     * @return void
     * @throws ilCtrlException
	 */
	protected function forwardAll(): void
	{
		$this->tabs->activateTab("all");
		$gui = new \ilCompetenceRecommenderAllGUI();
		$this->ctrl->forwardCommand($gui);
	}

	/**
	 * forwards to info view, made by the info gui
	 *
     * @return void
	 * @throws ilCtrlException
	 */
	protected function forwardInfo(): void
	{
		$this->tabs->activateTab("info");
		$gui = new \ilCompetenceRecommenderInfoGUI();
		$this->ctrl->forwardCommand($gui);
	}

	/**
	 * Set the tabs into the template
	 *
	 * @return 	void
	 */
	protected function setTabs(): void
	{
		// Tabs
		$this->tabs->setBack2Target($this->lng->txt('ui_uihk_comprec_back_tab'), $this->ctrl->getLinkTargetByClass(ilDashboardGUI::class));
		$this->tabs->addTab('show', $this->lng->txt('ui_uihk_comprec_activities_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderActivitiesGUI::class));
		$this->tabs->addTab('all', $this->lng->txt('ui_uihk_comprec_all_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderAllGUI::class));
		$this->tabs->addTab('info', $this->lng->txt('ui_uihk_comprec_info_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderInfoGUI::class));
		$this->tabs->activateTab('show');
	}
}
