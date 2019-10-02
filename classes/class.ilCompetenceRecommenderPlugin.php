<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilCompetenceRecommenderPlugin
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */
class ilCompetenceRecommenderPlugin extends ilUserInterfaceHookPlugin {

	const PLUGIN_ID = "comprec";
	const PLUGIN_NAME = "CompetenceRecommender";
	const PLUGIN_CLASS_NAME = self::class;
	/**
	 * @var self|null
	 */
	protected static $instance = NULL;


	/**
	 * @return self
	 */
	public static function getInstance(): self {
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * ilCompetenceRecommenderPlugin constructor
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * @return string
	 */
	public function getPluginName(): string {
		return self::PLUGIN_NAME;
	}


	/**
	 * @inheritdoc
	 */
	protected function deleteData()/*: void*/ {
		self::dic()->database()->dropTable("ui_uihk_comprec_config");
		self::dic()->database()->dropTable("ui_uihk_comprec_config_seq");
	}
}
