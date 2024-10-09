<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderPlugin
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */
class ilCompetenceRecommenderPlugin extends ilUserInterfaceHookPlugin
{
    public const PLUGIN_ID = "comprec";

	protected static ?ilCompetenceRecommenderPlugin $instance = null;

	/**
	 * @return ilCompetenceRecommenderPlugin
	 */
	public static function getInstance(): ilCompetenceRecommenderPlugin
    {
		if (is_null(self::$instance)) {
            global $DIC;
			self::$instance = new self($DIC->database(), $DIC['component.repository'], self::PLUGIN_ID);
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
