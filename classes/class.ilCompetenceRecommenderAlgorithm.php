<?php

declare(strict_types=1);

use ILIAS\Skill\Profile\SkillProfileUserDBRepository;

/**
 * Class ilCompetenceRecommenderAlgorithm
 *
 * utils class to compute data to show
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */

class ilCompetenceRecommenderAlgorithm
{
	private static ?ilCompetenceRecommenderAlgorithm $instance = null;
	protected ilDBInterface $db;
	protected ilRbacSystem $access;
    private ilObjUser $user;


    /**
	 * ilCompetenceRecommenderAlgorithm constructor.
	 */
	public function __construct()
	{
		global $DIC, $ilUser, $rbacsystem;
		$this->db = $DIC->database();
		$this->user = $DIC->user();
		$this->access = $rbacsystem;
	}

	/**
	 * @return ilCompetenceRecommenderAlgorithm instance
	 */
	protected static function getInstance(): ilCompetenceRecommenderAlgorithm
	{
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * @return ilDBInterface database of instance
	 */
	public static function getDatabaseObj(): ilDBInterface
	{
		$instance = self::getInstance();
		return $instance->db;
	}

	/**
	 * @return ilObjUser current user
	 */
	public static function getUserObj(): ilObjUser
	{
		$instance = self::getInstance();
		return $instance->user;
	}

	/**
	 * @return ilRbacSystem access object of instance
	 */
	public static function getAccessObj(): ilRbacSystem
	{
		$instance = self::getInstance();
		return $instance->access;
	}

	/**
	 * Returns number of competences of profiles of the user
	 *
	 * @param $profile
	 * @param array $skillsToSort
	 * @param int $n
	 * @return int
	 */
	public static function getNumberOfCompetencesForActivities(): int
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		$skillsarray = [];

		// todo get user profiles geändert. keine rollen mehr?
		//$profiles = ilSkillProfile::getProfilesOfUser($user_id);
		$profiles = (new SkillProfileUserDBRepository($db))->getProfilesOfUser($user_id);

		$profile_settings = new ilCompetenceRecommenderSettings();
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_" . $profile['id']) == $profile['id']) {
				$result = $db->query("SELECT spl.level_id, spl.base_skill_id, spl.tref_id
									FROM skl_profile_level AS spl
									WHERE spl.profile_id = '" . $profile["id"] . "'");
				$skills = $db->fetchAll($result);
				foreach ($skills as $skill) {
					$profilegoal = $db->query("SELECT nr FROM skl_level WHERE skill_id = '" . $skill["base_skill_id"] . "' AND id = '" . $skill["level_id"] . "'");
					$goal = $profilegoal->fetchAssoc();
					$score = self::computeScore($skill["tref_id"]);
					if ($score < $goal["nr"] && $score > 0) {
						$skillsarray[] = $skill;
					}
				}
			}
		}
		return count($skillsarray);
	}

	/**
	 * Returns whether user has a profile or not
	 *
	 * @return bool
	 */
	public static function hasUserProfile(): bool
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// todo get user profiles
		// $profiles = ilSkillProfile::getProfilesOfUser($user_id);
		$profiles = (new SkillProfileUserDBRepository($db))->getProfilesOfUser($user_id);

		$profile_settings = new ilCompetenceRecommenderSettings();
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['id']) == $profile['id']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns all profiles of the user, that are set active in the plugin config
	 *
	 * @return array
	 */
	public static function getUserProfiles(): array
    {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		$profilearray = [];

		// todo get user profiles
		// $profiles = ilSkillProfile::getProfilesOfUser($user_id);
		$profiles = (new SkillProfileUserDBRepository($db))->getProfilesOfUser($user_id);

		$profile_settings = new ilCompetenceRecommenderSettings();
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['id']) == $profile['id']) {
				$profilearray[] = $profile;
			}
		}

		return $profilearray;
	}

	/**
	 * Returns whether user has reached all goals of all profiles
	 *
	 * @return bool
	 */
	public static function hasUserFinishedAll(): bool
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// todo get user profiles
		// $profiles = ilSkillProfile::getProfilesOfUser($user_id);
		$profiles = (new SkillProfileUserDBRepository($db))->getProfilesOfUser($user_id);

		$profile_settings = new ilCompetenceRecommenderSettings();
		foreach ($profiles as $profile) {
			if ($profile_settings->get("checked_profile_".$profile['id']) == $profile['id']) {
				$result = $db->query("SELECT spl.level_id, spl.base_skill_id, spl.tref_id
									FROM skl_profile_level AS spl
									WHERE spl.profile_id = '" . $profile["id"] . "'");
				$skills = $db->fetchAll($result);
				foreach ($skills as $skill) {
					$profilegoal = $db->query("SELECT nr FROM skl_level WHERE skill_id = '" . $skill["base_skill_id"] . "' AND id = '" . $skill["level_id"] . "'");
					$goal = $profilegoal->fetchAssoc();
					$score = self::computeScore($skill["tref_id"]);
					if ($score < $goal["nr"]) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Returns whether the user has material to do left or not
	 *
	 * @return bool
	 */
	public static function noResourcesLeft(): bool
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

	/**
	 * Returns whether the user has a competence where he has not reached the goal but has data
	 *
	 * @return bool
	 */
	public static function noFormationdata(): bool
	{
		$competences = self::getAllCompetencesOfUserProfile();

		foreach ($competences as $competence) {
			if ($competence["score"] < $competence["goal"] && $competence["existsdata"]) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the initiation object for a specific profile or all initiation objects if profile_id is set to -1
	 *
	 * @param int $profile_id
	 * @return array
	 */
	public static function getInitObjects(int $profile_id = -1): array
    {
		$profiles = self::getUserProfiles();
		$settings = new ilCompetenceRecommenderSettings();
		$ref_ids = [];

		foreach ($profiles as $profile) {
			if ($profile_id == -1 || $profile_id == $profile["id"]) {
				$ref_id = $settings->get("init_obj_" . $profile["id"]);
				if (is_numeric($ref_id)) {
					$ref_ids[] = array("id" => $ref_id, "title" => $profile["title"]);
				}
			}
		}
		return $ref_ids;
	}

	/**
	 * Returns the data to show in the widget on the desktop. Standard amount is 3
	 *
	 * @param int $n
	 * @return array
	 */
	public static function getDataForDesktop(int $n = 3): array
    {
		$allRefIds = [];
		$competences = self::getAllCompetencesOfUserProfile();

		foreach ($competences as $competence) {
			foreach ($competence["resources"] as $resource) {
				if ($resource["level"] > $competence["score"] && $competence["score"] < $competence["goal"] && $competence["existsdata"]) {
					array_push($allRefIds, $resource);
					break;
				}
			}
		}


		$data = array_slice($allRefIds, 0, $n);

		return $data;
	}

	/**
	 * Returns all competences of the user or if n is set only the best n ones
	 *
	 * @param int $n
	 * @return array
	 */
	public static function getAllCompetencesOfUserProfile(int $n = 0): array
    {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// todo get user profiles
		// $profiles = ilSkillProfile::getProfilesOfUser($user_id);
		$profiles = (new SkillProfileUserDBRepository($db))->getProfilesOfUser($user_id);
		$skillsToSort = [];

		foreach ($profiles as $profile) {
			$skillsToSort = self::getCompetencesToProfile($profile, $skillsToSort, $n);
		}

		$sortedSkills = self::sortCompetences($skillsToSort);
		if ($n > 0) {
			return array_slice($sortedSkills, 0, $n);
		}

		return $sortedSkills;
	}

    /**
     * Returns all competences of a sepcific profile of the user or if n is set only the best n ones
     *
     * @param array $profile
     * @param array $skillsToSort
     * @param int $n
     * @return array
     */
	public static function getCompetencesToProfile(array $profile, array $skillsToSort = [], int $n = 0): array
    {
		$db = self::getDatabaseObj();

		$profile_settings = new ilCompetenceRecommenderSettings();

		if ($profile_settings->get("checked_profile_".$profile['id']) == $profile['id']) {
			$result = $db->query("SELECT spl.tref_id,spl.base_skill_id,spl.level_id,stn.title
									FROM skl_profile_level AS spl
									JOIN skl_tree_node AS stn ON spl.tref_id = stn.obj_id
									WHERE spl.profile_id = '" . $profile["id"] . "'");
			$skills = $db->fetchAll($result);
			$result_wo_template = $db->query("SELECT spl.tref_id,spl.base_skill_id,spl.level_id,stn.title
									FROM skl_profile_level AS spl
									JOIN skl_tree_node AS stn ON spl.base_skill_id = stn.obj_id
									WHERE spl.profile_id = '" . $profile["id"] . "'");
			$skills_wo_template = $db->fetchAll($result_wo_template);
			foreach ($skills as $skill) {
				$skillsToSort = self::getSkillData($skill, $skillsToSort, $n);
			}
			foreach ($skills_wo_template as $skill) {
				$skillsToSort = self::getSkillData($skill, $skillsToSort, $n);
			}
		}
		return $skillsToSort;
	}

    /**
     * Gets the skill data for skills with templates and without templates.
     *
     * @param array $skill
     * @param array $skillsToSort
     * @param int $n
     * @return array
     */
	private static function getSkillData(array $skill, array $skillsToSort, int $n): array
    {
		$db = self::getDatabaseObj();

		// get data needed for selfevaluations
		$skill["tref_id"] != 0 ? $childId = $skill["tref_id"] : $childId = $skill["base_skill_id"];

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
		if ($skill["tref_id"] != 0) {$score = self::computeScore($skill["tref_id"]);}
		else {$score = self::computeScore($skill["base_skill_id"], true);}
		if ($n == 0 || ($score != 0 && $score < $goal["nr"])) {
			if ($skill["tref_id"] == 0) {
				//set everything a step down (-1) for that the percentage of the lowest step is 0%
				$skillsToSort[$skill["base_skill_id"]] = array(
					"id" => $skill["tref_id"],
					"base_skill" => $skill["base_skill_id"],
					"parent" => $parentId,
					"title" => $skill['title'],
					"description" => ilSkillTreeNode::_lookupDescription(intval($skill["base_skill_id"])),
					"lastUsed" => self::getLastUsedDate(intval($skill["base_skill_id"]),true),
					"existsdata" => ($score != 0),
					"score" => $score == 0 ? $score = 0 : $score = $score - 1,
					"diff" => $score == 0 ? 1 - ($goal["nr"]-1) / ($levelcount-1) : ($score-1) / ($goal["nr"]-1),
					"goal" => $goal["nr"] - 1,
					"percentage" => ($score-1) / ($goal["nr"]-1),
					"scale" => $levelcount-1,
					"resources" => self::getResourcesForCompetence(intval($skill["base_skill_id"]), true));
			} else if (!isset($skillsToSort[$skill["tref_id"]])) {
				//set everything a step down (-1) for that the percentage of the lowest step is 0%
				$skillsToSort[$skill["tref_id"]] = array(
					"id" => $skill["tref_id"],
					"base_skill" => $skill["base_skill_id"],
					"parent" => $parentId,
					"title" => $skill['title'],
					"description" => ilSkillTreeNode::_lookupDescription(intval($skill["base_skill_id"])),
					"lastUsed" => self::getLastUsedDate(intval($skill["tref_id"])),
					"existsdata" => ($score != 0),
					"score" => $score == 0 ? $score = 0 : $score = $score - 1,
					"diff" => $score == 0 ? 1 - ($goal["nr"]-1) / ($levelcount-1) : ($score-1) / ($goal["nr"]-1),
					"goal" => $goal["nr"] - 1,
					"percentage" => ($score-1) / ($goal["nr"]-1),
					"scale" => $levelcount-1,
					"resources" => self::getResourcesForCompetence(intval($skill["tref_id"])));
			} else if ($goal["nr"] > $skillsToSort[$skill["tref_id"]]["goal"]) {
				// if several profiles with same skill take maximum
				$skillsToSort[$skill["tref_id"]]["goal"] = $goal["nr"]-1;
				$skillsToSort[$skill["tref_id"]]["percentage"] = ($score-1) / ($goal["nr"]-1);
				$skillsToSort[$skill["tref_id"]]["diff"] = ($score == 0 ? 1 - ($goal["nr"]-1) / ($levelcount-1) : ($score-1) / ($goal["nr"]-1));
			}
		}
		return $skillsToSort;
	}

	/**
	 * Sort the competences with standard sortation is diff
	 *
	 * @param array $competences
	 * @return array the sorted $competences array
	 */
	public static function sortCompetences(array $competences): array
    {
		$sortation = $_GET["sortation"];
		$valid_sortations = array('diff','percentage','lastUsed','oldest');
		// more dimensional sorting requires columns diff and name of skill (=title)
		$diffsorter = array_column($competences, 'diff');
		$titlesorter = array_column($competences, 'title');

		if (in_array($sortation, $valid_sortations) && $sortation != 'diff') {
			if ($sortation != 'oldest') {
				$score_sorter = array_column($competences, $sortation);
				if ($sortation != 'lastUsed') {
					array_multisort($score_sorter, SORT_NUMERIC, SORT_ASC, $diffsorter, SORT_NUMERIC, SORT_ASC, $titlesorter, SORT_STRING, SORT_ASC, $competences);
				} else {
					array_multisort($score_sorter, SORT_STRING, SORT_ASC, $diffsorter, SORT_NUMERIC, SORT_ASC, $titlesorter, SORT_STRING, SORT_ASC,$competences);
				}
			} else {
				$score_sorter = array_column($competences, 'lastUsed');
				array_multisort($score_sorter, SORT_STRING, SORT_DESC, $diffsorter, SORT_NUMERIC, SORT_ASC, $titlesorter, SORT_STRING, SORT_ASC,$competences);
			}
		} else {
			$score_sorter = array_column($competences, 'diff');
			array_multisort($score_sorter, SORT_NUMERIC, SORT_ASC, $titlesorter, SORT_STRING, SORT_ASC, $competences);
		}
		return $competences;
	}

	/**
	 * Returns only n competences of a users profile
	 *
	 * @param int $n
	 * @return array
	 */
	public static function getNCompetencesOfUserProfile(int $n): array
    {
		$competences = self::getAllCompetencesOfUserProfile($n);
		return $competences;
	}

    /**
     * Finds out the values to compute the score for a competence/skill and returns the score
     *
     * @param string $skill
     * @param bool $wo_template
     * @return float|int|string|null the score
     */
	private static function computeScore(string $skill, bool $wo_template = false)/*: float|int|string|null*/
	{
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		// if not in template
		if (!$wo_template) {$use_id = "tref_id";} else {$use_id = "skill_id";}

		$resultLastSelfEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.". $use_id ." ='" . $skill . "'
								AND suhl.self_eval = '1'
								ORDER BY suhl.status_date DESC");
		$resultLastFremdEval = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.". $use_id ." ='" . $skill . "'
								AND suhl.self_eval = '0'
								AND (suhl.trigger_obj_type = 'crs' OR suhl.trigger_obj_type = 'svy' OR suhl.trigger_obj_type = 'lm')
								ORDER BY suhl.status_date DESC");
		$resultLastMessung = $db->query("SELECT suhl.level_id, sl.nr, suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.". $use_id ." ='" . $skill . "'
								AND suhl.self_eval = '0'
								AND suhl.trigger_obj_type = 'tst'
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
		$dropout_setting = new ilCompetenceRecommenderSettings();
		$dropout_value = $dropout_setting->get("dropout_input");
		if ($dropout_value == null) {$dropout_value = 0;}

		return self::score($t_S, $t_M, $t_F, $scoreS, $scoreM, $scoreF, intval($dropout_value));
	}

	/**
	 * The actual computation of the score
	 *
	 * @param int $t_S time since last selfevaluation
	 * @param int $t_M time since last "messung"
	 * @param int $t_F time since last "fremd"evaluation
	 * @param int $scoreS the score of last selfevaluation
	 * @param int $scoreM the score of last "messung"
	 * @param int $scoreF the score of last "fremd"evaluation
	 * @param int $dropout_value the value when to ignore data
	 * @return float|int|string|null the score
	 */
	public static function score(int $t_S, int $t_M, int $t_F, int $scoreS, int $scoreM, int $scoreF, int $dropout_value = 0)/*: float|int|string|null*/
    {
		$score = 0;

        return max($scoreS, $scoreM, $scoreF);


		/*
		($t_M < $t_S && $t_M != 0) ? $t_minimum = $t_M : $t_minimum = $t_S;
		($t_F < $t_minimum && $t_F != 0) ? $t_minimum = $t_F : $t_minimum = $t_minimum;

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
			$t_S == 0 ? $t_S = 0 : $t_S -= $t_M - 1;
			$t_F == 0 ? $t_F = 0 : $t_F -= $t_M - 1;
			$t_M = 1;
		} else if ($t_F == $t_minimum && $t_minimum > 0) {
			$t_M == 0 ? $t_M = 0 : $t_M -= $t_F - 1;
			$t_S == 0 ? $t_S = 0 : $t_S -= $t_F - 1;
			$t_F = 1;
		}

		$m_S = 1/3; $m_F = 1/3; $m_M = 1/3;
		//Fallunterscheidung
		if ($t_S == 0 || $scoreS == 0) {$m_S = 0; $t_S = 0;}
		if ($t_F == 0 || $scoreF == 0) {$m_F = 0; $t_F = 0;}
		if ($t_M == 0 || $scoreM == 0) {$m_M = 0; $t_M = 0;}

		$sum_t = $t_S+$t_F+$t_M;
		//Berechnung
		if ($sum_t != 0) {
			if ($t_S / $sum_t == 1) {
				$score = $scoreS;
			} else if ($t_M / $sum_t == 1) {
				$score = $scoreM;
			} else if ($t_F / $sum_t == 1) {
				$score = $scoreF;
			} else {
				$m_S != 0 ? $sumS = array($sum_t - $t_S, $sum_t* 3) : $sumS=array(0,1);
				$m_M != 0 ? $sumM = array($sum_t - $t_M, $sum_t* 3) : $sumM=array(0,1);
				$m_F != 0 ? $sumF = array($sum_t - $t_F, $sum_t* 3) : $sumF=array(0,1);

				$longdivisor = $sumS[0]*$sumM[1]*$sumF[1]+$sumS[1]*$sumM[0]*$sumF[1]+$sumS[1]*$sumM[1]*$sumF[0];
				$mult = $sumS[1]*$sumM[1]*$sumF[1];
				$scorePartS = $sumS[0] *  $mult * $scoreS/ ($sumS[1] * $longdivisor);
				$scorePartM = $sumM[0] *  $mult * $scoreM/ ($sumM[1] * $longdivisor);
				$scorePartF = $sumF[0] *  $mult * $scoreF/ ($sumF[1] * $longdivisor);
				$score = $scorePartS + $scorePartF + $scorePartM;
				$score = round($score, 3);
			}
		}

		return $score;*/
	}

    /**
     * Returns the date of the last formationdata of a user in a competence
     *
     * @param int $skill_id
     * @param bool $wo_template
     * @return string
     */
	private static function getLastUsedDate(int $skill_id, bool $wo_template=false): string // todo string?
    {
		$db = self::getDatabaseObj();
		$user_id = self::getUserObj()->getId();

		if (!$wo_template) {$use_id = "tref_id";} else {$use_id = "skill_id";}

		$lastUsedDate = $db->query("SELECT suhl.status_date
								FROM skl_user_has_level AS suhl
								JOIN skl_level AS sl ON suhl.level_id = sl.id
								WHERE suhl.user_id ='" . $user_id . "' 
								AND suhl.".$use_id." ='" . $skill_id . "'
								ORDER BY suhl.status_date DESC");

		$date = $lastUsedDate->fetchAssoc()["status_date"];
		if (!isset($date)) {
			$date = 0;
		}

		return $date;
	}

    /**
     * Returns the resources of a competence for a user
     *
     * @param int $skill_id
     * @param bool $wo_template
     * @return array
     */
	private static function getResourcesForCompetence(int $skill_id, bool $wo_template=false): array
    {
		$db = self::getDatabaseObj();
		$access = self::getAccessObj();
		$user = self::getUserObj()->getId();

		$refIds = [];
		if (!$wo_template) {
			$result = $db->query("SELECT ssr.rep_ref_id,ssr.tref_id,ssr.level_id,stn.title 
								FROM skl_skill_resource AS ssr 
								JOIN skl_tree_node AS stn ON ssr.tref_id = stn.obj_id
								WHERE ssr.tref_id ='" . $skill_id . "'");
		} else {
			$result = $db->query("SELECT ssr.rep_ref_id,ssr.tref_id,ssr.level_id,stn.title 
								FROM skl_skill_resource AS ssr 
								JOIN skl_tree_node AS stn ON ssr.base_skill_id = stn.obj_id
								WHERE ssr.base_skill_id ='" . $skill_id . "'");
		}
		$values = $db->fetchAll($result);

		foreach ($values as $value) {
			$level = $db->query("SELECT nr
								FROM skl_level
								WHERE id ='".$value["level_id"]."'");
			$levelnumber = $level->fetchAssoc();
			if ($access->checkAccessOfUser($user, 'read', $value["rep_ref_id"])) {
				$refIds[] = array("id" => $value["rep_ref_id"], "title" => $value["title"], "level" => ($levelnumber["nr"] - 1));
			}
		}

		// sort
		$sorter  = array_column($refIds, 'level');
		array_multisort($sorter, SORT_NUMERIC, SORT_ASC, $refIds);

		return $refIds;
	}
}
