<?php
declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderConfigGUI
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */

class ilCompetenceRecommenderAlgorithm {

	/**
	 * @var \ilCompetenceRecommenderAlgorithm
	 */
	protected static $instance;

	/**
	 * @var \ilDB
	 */
	protected $db;

	/**
	 * @var \ilUser
	 */
	protected $user;

	public function __construct()
	{
		global $DIC, $ilUser;
		$this->db = $DIC->database();
		$this->user = $ilUser;
	}

	protected static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	public static function getDatabaseObj()
	{
		$instance = self::getInstance();
		return $instance->db;
	}

	public static function getUserObj()
	{
		$instance = self::getInstance();
		return $instance->user;
	}

	public static function getDataForDesktop() {
		$db = self::getDatabaseObj();

		// todo: beste Ressourcen, nicht zufällige
		$allRefIds = array();
		$result = $db->query("SELECT ssr.rep_ref_id,ssr.tref_id,stn.title 
								FROM skl_skill_resource AS ssr 
								JOIN skl_tree_node AS stn ON ssr.tref_id = stn.obj_id");
		$values = $db->fetchAll($result);

		foreach ($values as $value) {
			array_push($allRefIds, array("id" => $value["rep_ref_id"], "title" => $value["title"]));
		}

		return array_slice($allRefIds, 0 ,3);
	}

	public static function getAllCompetencesOfUserProfile() {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// get user profiles
		$result = $db->query("SELECT profile_id FROM skl_profile_user WHERE user_id = '".$user_id."'");
		$profiles = $db->fetchAll($result);
		$skillsToSort = array();

		foreach ($profiles as $profile) {
			$result = $db->query("SELECT spl.tref_id,spl.base_skill_id,spl.level_id,stn.title
									FROM skl_profile_level AS spl
									JOIN skl_tree_node AS stn ON spl.tref_id = stn.obj_id
									WHERE spl.profile_id = '".$profile["profile_id"]."'");
			$skills = $db->fetchAll($result);
			foreach($skills as $skill) {
				$level = $db->query("SELECT * FROM skl_level WHERE skill_id = '".$skill["base_skill_id"]."'");
				$levelcount = $level->numRows();
				$profilegoal = $db->query("SELECT nr FROM skl_level WHERE skill_id = '".$skill["base_skill_id"]."' AND id = '".$skill["level_id"]."'");
				$goal = $profilegoal->fetchAssoc();
				$score = self::computeScore($skill["tref_id"]);
				$skillsToSort[$skill["tref_id"]] = array("title" => $skill['title'],
					"score" => $score,
					"goal" => $goal["nr"],
					"scale" => $levelcount,
					"resources" => self::getResourcesForCompetence(intval($skill["tref_id"])));
			}
		}

		arsort($skillsToSort);

		return $skillsToSort;
	}

	public static function getNCompetencesOfUserProfile(int $n) {
		$allCompetences = self::getAllCompetencesOfUserProfile();
		return array_slice($allCompetences, 0, $n);
	}

	private static function computeScore($skill)
	{
		//todo compute score with formula
		return 1;
	}

	private static function getResourcesForCompetence(int $skill_id) {
		$db = self::getDatabaseObj();

		$refIds = array();
		$result = $db->query("SELECT ssr.rep_ref_id,ssr.tref_id,stn.title 
								FROM skl_skill_resource AS ssr 
								JOIN skl_tree_node AS stn ON ssr.tref_id = stn.obj_id
								WHERE ssr.tref_id ='".$skill_id."'");
		$values = $db->fetchAll($result);

		foreach ($values as $value) {
			array_push($refIds, array("id" => $value["rep_ref_id"], "title" => $value["title"]));
		}
		// todo: sort
		return $refIds;
	}
}