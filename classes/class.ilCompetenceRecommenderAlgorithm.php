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

	public static function hasUserProfile()
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// get user profiles
		$result = $db->query("SELECT profile_id FROM skl_profile_user WHERE user_id = '".$user_id."'");
		$numberofprofiles = $result->numRows();

		return ($numberofprofiles > 0);
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
				$skillsToSort[$skill["tref_id"]] = array(
					"id" => $skill["tref_id"],
					"title" => $skill['title'],
					"score" => $score,
					"diff" => $goal["nr"]-$score,
					"goal" => $goal["nr"],
					"scale" => $levelcount,
					"resources" => self::getResourcesForCompetence(intval($skill["tref_id"])));
			}
		}

		$score_sorter  = array_column($skillsToSort, 'diff');
		array_multisort($score_sorter, SORT_NUMERIC, SORT_DESC,$skillsToSort);

		return $skillsToSort;
	}

	public static function sortCompetences(array $competences) {
		$score_sorter  = array_column($competences, 'diff');
		array_multisort($score_sorter, SORT_NUMERIC, SORT_DESC,$competences);
		return $score_sorter;
	}

	public static function getNCompetencesOfUserProfile(int $n) {
		$allCompetences = self::getAllCompetencesOfUserProfile();
		return array_slice($allCompetences, 0, $n);
	}

	private static function computeScore($skill)
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();
		$score = 0;

		$resultLastSelfEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='".$user_id. "' 
								AND suhl.tref_id ='".$skill."'
								AND suhl.self_eval = '1'
								ORDER BY suhl.status_date DESC");
		// todo: Fremdeinschaetzung richtig setzen
		$resultLastFremdEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='".$user_id. "' 
								AND suhl.tref_id ='".$skill."'
								AND suhl.self_eval = '1'
								ORDER BY suhl.status_date DESC");
		$resultLastMessung = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='".$user_id. "' 
								AND suhl.tref_id ='".$skill."'
								AND suhl.self_eval = '0'
								ORDER BY suhl.status_date DESC");

		// last value of user levels
		$scoreS = 0; $scoreF = 0; $scoreM = 0;
		// time in days since value was set
		$t_S = 0; $t_F = 0; $t_M = 0;
		if ($resultLastSelfEval->numRows() > 0) {
			$valueLastSelfEval = $db->fetchAssoc($resultLastSelfEval);
			$scoreS = $valueLastSelfEval["nr"];
			$t_S = ceil((time() - strtotime($valueLastSelfEval["status_date"]))/86400);
		}
		if ($resultLastFremdEval->numRows() > 0) {
			$valueLastFremdEval = $db->fetchAssoc($resultLastFremdEval);
			$scoreF = $valueLastFremdEval["nr"];
			$t_F = ceil((time() - strtotime($valueLastFremdEval["status_date"]))/86400);
		}
		if ($resultLastMessung->numRows() > 0) {
			$valueLastMessung = $db->fetchAssoc($resultLastMessung);
			$scoreM = $valueLastMessung["nr"];
			$t_M = ceil((time() - strtotime($valueLastMessung["status_date"]))/86400);
		}
		$sum_t = $t_S+$t_F+$t_M;

		//Konstanten
		$m_S = 1/3; $m_F = 1/3; $m_M = 1/3;

		//Fallunterscheidung
		if ($t_S === 0) {$m_S = 0;}
		if ($t_F === 0) {$m_F = 0;}
		if ($t_M === 0) {$m_M = 0;}
		//Berechnung
		if ($sum_t != 0) {
			$sumS = (1 - ($t_S / $sum_t)) * $m_S;
			$sumM = (1 - ($t_M / $sum_t)) * $m_M;
			$sumF = (1 - ($t_F / $sum_t)) * $m_F;
			$scorePartS = ( $sumS / ($sumS + $sumM + $sumF) ) * $scoreS;
			$scorePartM = ( $sumM / ($sumS + $sumM + $sumF) ) * $scoreM;
			$scorePartF = ( $sumF / ($sumS + $sumM + $sumF) ) * $scoreF;
			$score = $scorePartS+$scorePartF+$scorePartM;
		}

		return $score;
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