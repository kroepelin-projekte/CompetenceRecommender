<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

namespace feldbusl\Plugins\CompetenceRecommender\Access;

use feldbusl\Plugins\CompetenceRecommender\Utils\CompetenceRecommenderTrait;
use ilCompetenceRecommenderPlugin;
use srag\DIC\CompetenceRecommender\DICTrait;

/**
 * Class Ilias
 *
 * Generated by srag\PluginGenerator v0.9.7
 *
 * @package feldbusl\Plugins\CompetenceRecommender\Access
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */
final class Ilias {

	use DICTrait;
	use CompetenceRecommenderTrait;
	const PLUGIN_CLASS_NAME = ilCompetenceRecommenderPlugin::class;
	/**
	 * @var self
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
	 * Ilias constructor
	 */
	private function __construct() {

	}
}
