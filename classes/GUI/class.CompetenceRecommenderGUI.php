<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

//require_once __DIR__ . "/../vendor/autoload.php";

use feldbusl\Plugins\CompetenceRecommender\Utils\CompetenceRecommenderTrait;
use srag\DIC\CompetenceRecommender\DICTrait;

/**
 * Class CompetenceRecommenderGUI
 *
 * Generated by srag\PluginGenerator v0.9.7
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_isCalledBy CompetenceRecommenderGUI: ilUIPluginRouterGUI
 */
class CompetenceRecommenderGUI {

	use DICTrait;
	use CompetenceRecommenderTrait;
	const PLUGIN_CLASS_NAME = ilCompetenceRecommenderPlugin::class;
	const CMD_SOME = "some";
    /** @var  ilCtrl */
    protected $ctrl;

    /** @var  ilTabsGUI */
    protected $tabs;

    /** @var  ilTemplate */
    public $tpl;

	/** @var  ilCompetenceRecommenderPlugin */
    public $pl;

	/**
	 * CompetenceRecommenderGUI constructor
	 */
	public function __construct() {
        	global $ilCtrl, $ilTabs, $tpl;
        	$this->ctrl = $ilCtrl;
        	$this->tabs = $ilTabs;
        	$this->tpl = $tpl;
		$this->pl = ilCompetenceRecommenderPlugin::getInstance();
	}


	/**
	 *
	 */
	public function executeCommand()/*: void*/ {
		if (!$this->pl->isActive()) {
			ilUtil::sendFailure('Activate Plugin first', true);
			ilUtil::redirect('index.php');
		}
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		switch ($cmd) {
			default:
				$this->performCommand($cmd);
				break;
		}

		return true;
	}


	/**
	 *
	 */
	protected function performCommand($cmd)/*: void*/ {
		$this->newPageTemplate();
	}


    	protected function newPageTemplate()
    	{
        	global $tpl;
        	$tpl->setTitle("Testtitel");
        	$tpl->getStandardTemplate();
		$tpl->setContent("Hallo neue Welt");
        	$tpl->show();
        	return;
    	}
}
