<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

// todo entfernen?
// require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilCompetenceRecommenderPlugin
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */
class ilCompetenceRecommenderPlugin extends ilUserInterfaceHookPlugin {

    // todo entfernen?
/*	const PLUGIN_ID = "comprec";
	const PLUGIN_NAME = "CompetenceRecommender";
	const PLUGIN_CLASS_NAME = self::class;*/
	/**
	 * @var self|null
	 */
	protected static ?ilCompetenceRecommenderPlugin $instance = null;

	/**
	 * @return ilCompetenceRecommenderPlugin
	 */
	public static function getInstance(): ilCompetenceRecommenderPlugin
    {
		if (is_null(self::$instance)) {
            global $DIC;
			self::$instance = new self($DIC->database(), $DIC['component.repository'], '');
		}

		return self::$instance;
	}

	/**
	 * @inheritdoc
	 */
	protected function afterUninstall(): void
    {
		global $DIC;
		$DIC->database()->dropTable("ui_uihk_comprec_config");
	}
}
