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
		$profiles = $db->fetchAll($result);

		$profile_settings = new ilSetting("comprec");
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['profile_id']) == $profile['profile_id']) {
				return true;
			}
		}

		return false;
	}

	public static function hasUserFinishedAll()
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// get user profiles
		$result = $db->query("SELECT profile_id FROM skl_profile_user WHERE user_id = '".$user_id."'");
		$profiles = $db->fetchAll($result);

		$profile_settings = new ilSetting("comprec");
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['profile_id']) == $profile['profile_id']) {
				$result = $db->query("SELECT spl.level_id, spl.base_skill_id, spl.tref_id
									FROM skl_profile_level AS spl
									WHERE spl.profile_id = '" . $profile["profile_id"] . "'");
				$skills = $db->fetchAll($result);
				foreach ($skills as $skill) {
					$profilegoal = $db->query("SELECT nr FROM skl_level WHERE skill_id = '" . $skill["base_skill_id"] . "' AND id = '" . $skill["level_id"] . "'");
					$goal = $profilegoal->fetchAssoc();
					$score = self::computeScore($skill["tref_id"]);
					if ($score < $goal) {
						return false;
					}
				}
			}
		}

		return true;
	}

	public static function noResourcesLeft()
	{
		$competences = self::getAllCompetencesOfUserProfile();

		foreach ($competences as $competence) {
			foreach ($competence["resources"] as $resource) {
				if ($resource["level"] >= $competence["score"] && $competence["score"] < $competence["goal"]) {
					return false;
				}
			}
		}

		return true;
	}

	public static function getDataForDesktop(int $n = 3) {
		$allRefIds = array();
		$competences = self::getAllCompetencesOfUserProfile();

		foreach ($competences as $competence) {
			foreach ($competence["resources"] as $resource) {
				if ($resource["level"] >= $competence["score"] && $competence["score"] < $competence["goal"] && $competence["score"] > 0) {
					array_push($allRefIds, $resource);
					break;
				}
			}
		}


		$data = array_slice($allRefIds, 0, $n);

		return $data;
	}

	public static function getAllCompetencesOfUserProfile(int $n = 0) {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// get user profiles
		$result = $db->query("SELECT profile_id FROM skl_profile_user WHERE user_id = '".$user_id."'");
		$profiles = $db->fetchAll($result);
		$skillsToSort = array();

		$profile_settings = new ilSetting("comprec");

		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['profile_id']) == $profile['profile_id']) {
				$result = $db->query("SELECT spl.tref_id,spl.base_skill_id,spl.level_id,stn.title
									FROM skl_profile_level AS spl
									JOIN skl_tree_node AS stn ON spl.tref_id = stn.obj_id
									WHERE spl.profile_id = '" . $profile["profile_id"] . "'");
				$skills = $db->fetchAll($result);
				foreach ($skills as $skill) {
					// get data needed for Selfevaluations
					$childId = $skill["tref_id"];
					$depth = 3;
					while ($depth > 2) {
						$parent_query = $db->query("SELECT depth, child, parent
									FROM skl_tree
									WHERE child = '" . $childId . "'");
						$parent = $db->fetchAssoc($parent_query);
						$depth = $parent["depth"];
						$childId = $parent["parent"];
						$parentId = $parent["child"];
					}

					// get resources and score
					$level = $db->query("SELECT * FROM skl_level WHERE skill_id = '" . $skill["base_skill_id"] . "'");
					$levelcount = $level->numRows();
					$profilegoal = $db->query("SELECT nr FROM skl_level WHERE skill_id = '" . $skill["base_skill_id"] . "' AND id = '" . $skill["level_id"] . "'");
					$goal = $profilegoal->fetchAssoc();
					$score = self::computeScore($skill["tref_id"]);
					if ($n == 0 || $score != 0) {
						if (!isset($skillsToSort["tref_id"])) {
							$skillsToSort[$skill["tref_id"]] = array(
								"id" => $skill["tref_id"],
								"base_skill" => $skill["base_id"],
								"parent" => $parentId,
								"title" => $skill['title'],
								"lastUsed" => self::getLastUsedDate(intval($skill["tref_id"])),
								"score" => $score,
								"diff" => $score == 0 ? 1 - $goal["nr"] / $levelcount : $score / $goal["nr"],
								"goal" => $goal["nr"],
								"scale" => $levelcount,
								"resources" => self::getResourcesForCompetence(intval($skill["tref_id"])));
						} else if ($goal["nr"] > $skillsToSort["tref_id"]["goal"]) {
							// if several profiles with same skill take maximum
							$skillsToSort[$skill["tref_id"]]["goal"] = $goal["nr"];
						}
					}
				}
			}
		}

		$sortedSkills = self::sortCompetences($skillsToSort);
		if ($n > 0) {
			return array_slice($sortedSkills, 0, $n);
		}

		return $sortedSkills;
	}

	public static function sortCompetences(array $competences) {
		$score_sorter  = array_column($competences, 'diff');
		array_multisort($score_sorter, SORT_NUMERIC, SORT_ASC,$competences);
		return $competences;
	}

	public static function getNCompetencesOfUserProfile(int $n) {
		$competences = self::getAllCompetencesOfUserProfile($n);
		return $competences;
	}

	private static function computeScore($skill)
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		$resultLastSelfEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.tref_id ='" . $skill . "'
								AND suhl.self_eval = '1'
								ORDER BY suhl.status_date DESC");
		$resultLastFremdEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.tref_id ='" . $skill . "'
								AND suhl.self_eval = '0'
								AND (suhl.trigger_obj_type = 'crs' OR suhl.trigger_obj_type = 'svy')
								ORDER BY suhl.status_date DESC");
		$resultLastMessung = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.tref_id ='" . $skill . "'
								AND suhl.self_eval = '0'
								AND suhl.trigger_obj_type != 'crs'
								AND suhl.trigger_obj_type != 'svy'
								ORDER BY suhl.status_date DESC");

		// last value of user levels
		$scoreS = 0;
		$scoreF = 0;
		$scoreM = 0;
		// time in days since value was set
		$t_S = 0;
		$t_F = 0;
		$t_M = 0;
		if ($resultLastSelfEval->numRows() > 0) {
			$valueLastSelfEval = $db->fetchAssoc($resultLastSelfEval);
			$scoreS = intval($valueLastSelfEval["nr"]);
			$t_S = intval(ceil((time() - strtotime($valueLastSelfEval["status_date"])) / 86400));
		}
		if ($resultLastFremdEval->numRows() > 0) {
			$valueLastFremdEval = $db->fetchAssoc($resultLastFremdEval);
			$scoreF = intval($valueLastFremdEval["nr"]);
			$t_F = intval(ceil((time() - strtotime($valueLastFremdEval["status_date"])) / 86400));
		}
		if ($resultLastMessung->numRows() > 0) {
			$valueLastMessung = $db->fetchAssoc($resultLastMessung);
			$scoreM = intval($valueLastMessung["nr"]);
			$t_M = intval(ceil((time() - strtotime($valueLastMessung["status_date"])) / 86400));
		}

		// drop values older than dropout_input
		$dropout_setting = new ilSetting("comprec");
		$dropout_value = $dropout_setting->get("dropout_input");
		if ($dropout_value == null) {$dropout_value = 0;}

		return self::score($t_S, $t_M, $t_F, $scoreS, $scoreM, $scoreF, intval($dropout_value));
	}

	public static function score(int $t_S, int $t_M, int $t_F, int $scoreS, int $scoreM, int $scoreF, int $dropout_value = 0) {
		$score = 0;

		($t_M < $t_S && $t_M != 0) ? $t_minimum = $t_M : $t_minimum = $t_S;
		($t_F > $t_minimum && $t_minimum != 0) ? $t_minimum = $t_minimum : $t_minimum = $t_F;

		if ($dropout_value > 0) {
			$t_S - $t_minimum > $dropout_value ? $t_S = 0 : $t_S = $t_S;
			$t_M - $t_minimum > $dropout_value ? $t_M = 0 : $t_M = $t_M;
			$t_F - $t_minimum > $dropout_value ? $t_F = 0 : $t_F = $t_F;
		}

		// set t_i to value since newest date
		if ($t_S == $t_minimum && $t_minimum > 0) {
			$t_M == 0 ? $t_M = 0 : $t_M -= $t_S - 1;
			$t_F == 0 ? $t_F = 0 : $t_F -= $t_S - 1;
			$t_S = 1;
		} else if ($t_M == $t_minimum && $t_minimum > 0) {
			$t_S == 0 ? $t_S = 0 : $t_S -= $t_M + 1;
			$t_F == 0 ? $t_F = 0 : $t_F -= $t_M + 1;
			$t_M = 1;
		} else if ($t_F == $t_minimum && $t_minimum > 0) {
			$t_M == 0 ? $t_M = 0 : $t_M -= $t_F + 1;
			$t_S == 0 ? $t_S = 0 : $t_S -= $t_F + 1;
			$t_F = 1;
		}

		//Konstanten
		$m_S = 1/3; $m_F = 1/3; $m_M = 1/3;

		//Fallunterscheidung
		if ($t_S == 0 || $scoreS == 0) {$m_S = 0; $t_S = 0;}
		if ($t_F == 0 || $scoreF == 0) {$m_F = 0; $t_F = 0;}
		if ($t_M == 0 || $scoreM == 0) {$m_M = 0; $t_M = 0;}

		$sum_t = $t_S+$t_F+$t_M;
		//Berechnung
		if ($sum_t != 0) {
			$sumS = (1 - ($t_S / $sum_t)) * $m_S;
			$sumM = (1 - ($t_M / $sum_t)) * $m_M;
			$sumF = (1 - ($t_F / $sum_t)) * $m_F;
			if ($t_S / $sum_t == 1) {
				$score = $scoreS;
			} else if ($t_M / $sum_t == 1) {
				$score = $scoreM;
			} else if ($t_F / $sum_t == 1) {
				$score = $scoreF;
			} else {
				$scorePartS = ($sumS / ($sumS + $sumM + $sumF)) * $scoreS;
				$scorePartM = ($sumM / ($sumS + $sumM + $sumF)) * $scoreM;
				$scorePartF = ($sumF / ($sumS + $sumM + $sumF)) * $scoreF;
				$score = $scorePartS + $scorePartF + $scorePartM;
			}
		}

		return $score;
	}

	private static function getLastUsedDate(int $skill_id) {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		$lastUsedDate = $db->query("SELECT suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.tref_id ='" . $skill_id . "'");

		return $lastUsedDate->fetchAssoc()["status_date"];
	}

	private static function getResourcesForCompetence(int $skill_id) {
		$db = self::getDatabaseObj();

		$refIds = array();
		$result = $db->query("SELECT ssr.rep_ref_id,ssr.tref_id,ssr.level_id,stn.title 
								FROM skl_skill_resource AS ssr 
								JOIN skl_tree_node AS stn ON ssr.tref_id = stn.obj_id
								WHERE ssr.tref_id ='".$skill_id."'");
		$values = $db->fetchAll($result);

		foreach ($values as $value) {
			$level = $db->query("SELECT nr
								FROM skl_level
								WHERE id ='".$value["level_id"]."'");
			$levelnumber = $level->fetchAssoc();
			array_push($refIds, array("id" => $value["rep_ref_id"], "title" => $value["title"], "level" => $levelnumber["nr"]));
		}

		// sort
		$sorter  = array_column($refIds, 'level');
		array_multisort($sorter, SORT_NUMERIC, SORT_ASC, $refIds);

		return $refIds;
	}
}