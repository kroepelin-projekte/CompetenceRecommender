<?php
declare(strict_types=1);

include_once("./Services/PersonalDesktop/classes/class.ilPersonalDesktopGUI.php");

include_once("class.ilCompetenceRecommenderActivitiesGUI.php");
include_once("class.ilCompetenceRecommenderAllGUI.php");
include_once("class.ilCompetenceRecommenderInfoGUI.php");

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
class ilCompetenceRecommenderGUI {

	const PLUGIN_CLASS_NAME = ilCompetenceRecommenderPlugin::class;

    /** @var  \ilCtrl */
    protected $ctrl;

    /** @var  \ilTabsGUI */
    protected $tabs;

    /** @var  \ilTemplate */
    public $tpl;

	/** @var  \ilCompetenceRecommenderPlugin */
    public $pl;

	/**
	 * @var \ilLanguage
	 */
	protected $lng;

	/** @var  \ilUIFramework */
	public $ui;

	/** @var  \ilDB */
	public $db;

	/**
	 * CompetenceRecommenderGUI constructor
	 */
	public function __construct() {
		global $ilCtrl, $ilTabs, $tpl, $DIC;
        $this->ctrl = $ilCtrl;
        $this->tabs = $ilTabs;
        $this->tpl = $tpl;
		$this->ui = $DIC->ui();
		$this->db = $DIC->database();
		$this->pl = ilCompetenceRecommenderPlugin::getInstance();
		$this->lng = $DIC['lng'];
	}

	/**
	 * Delegates incoming commands
	 *
	 * @return bool
	 * @throws Exception if command is not known
	 */
	public function executeCommand()/*: void*/ {
		if (!$this->pl->isActive()) {
			ilUtil::sendFailure('Activate Plugin first', true);
			ilUtil::redirect('index.php');
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
		return true;
	}


	/**
	 * Get standard command.
	 *
	 * @return 	string
	 */
	public function getStandardCommand()
	{
		return 'dashboard';
	}

	/**
	 * forwards to standard view, made by the activities gui
	 *
	 * @throws ilCtrlException
	 */
	protected function forwardShow()
	{
		$this->tabs->activateTab("show");
		$gui = new \ilCompetenceRecommenderActivitiesGUI();
		$this->ctrl->forwardCommand($gui);
	}

	/**
	 * forwards to all view, made by the all gui
	 *
	 * @throws ilCtrlException
	 */
	protected function forwardAll()
	{
		$this->tabs->activateTab("all");
		$gui = new \ilCompetenceRecommenderAllGUI();
		$this->ctrl->forwardCommand($gui);
	}

	/**
	 * forwards to info view, made by the info gui
	 *
	 * @throws ilCtrlException
	 */
	protected function forwardInfo()
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
	protected function setTabs()
	{
		// Tabs
		$this->tabs->setBack2Target($this->lng->txt('ui_uihk_comprec_back_tab'), $this->ctrl->getLinkTargetByClass("ilPersonalDesktopGUI"));
		$this->tabs->addTab('show', $this->lng->txt('ui_uihk_comprec_activities_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderActivitiesGUI::class));
		$this->tabs->addTab('all', $this->lng->txt('ui_uihk_comprec_all_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderAllGUI::class));
		$this->tabs->addTab('info', $this->lng->txt('ui_uihk_comprec_info_tab'), $this->ctrl->getLinkTargetByClass(ilCompetenceRecommenderInfoGUI::class));
		$this->tabs->activateTab('show');
	}
}
