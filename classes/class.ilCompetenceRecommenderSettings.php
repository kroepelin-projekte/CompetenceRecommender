<?php

declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderSettings
 *
 * utils class for saving the settings into the database ui_uihk_comprec_config
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 */
class ilCompetenceRecommenderSettings
{
	protected ilDBInterface $db;

	/**
	 * Initialize settings
	 */
	public function __construct()
	{
		global $DIC;

		$this->db = $DIC->database();

		// check whether ini file object exists
		if (!is_object($this->db))
		{
			die ("Fatal Error: ilCompetenceRecommenderSettings object instantiated without DB initialisation.");
		}
	}

	/**
	 * Gets the value to a specific keyword
	 *
	 * @param string $a_keyword
	 * @param int|null $a_user_id
	 * @return null|string
	 */
	public function get(string $a_keyword, ?int $a_user_id = null): ?string
	{
		if ($a_user_id == null) {
			$query = "SELECT * FROM ui_uihk_comprec_config WHERE name ='" . $a_keyword . "'";
		} else {
			$query = "SELECT * FROM ui_uihk_comprec_config WHERE name ='" . $a_keyword . "' AND user_id ='" . $a_user_id . "'";
		}
		$res = $this->db->query($query);
		$row = $res->fetchAssoc();
		return $row["value"] ?? null;
	}

	/**
	 * Deletes (if exists) a row in the database, depending on keyword and user
	 *
	 * @param string $a_keyword
	 * @param int|null $a_user_id
	 * @return bool
	 */
	public function delete(string $a_keyword, ?int $a_user_id = null): bool
	{
		$ilDB = $this->db;

		if ($a_user_id == null) {
			$query = "DELETE FROM ui_uihk_comprec_config WHERE name ='" . $a_keyword . "'";
		} else {
			$query = "DELETE FROM ui_uihk_comprec_config WHERE name = '" . $a_keyword . "' AND user_id = '".$a_user_id."'";
		}

		$ilDB->manipulate($query);

		return true;
	}

	/**
	 * Sets the value to a specific keyword and user
	 *
	 * @param string $a_key
	 * @param string $a_val
	 * @param int|null $a_user_id
	 * @return bool
	 */
	public function set(string $a_key, string $a_val, ?int $a_user_id = null): bool
	{
		$ilDB = $this->db;

		$this->delete($a_key);

		$id = $ilDB->nextID('ui_uihk_comprec_config');

		$ilDB->insert("ui_uihk_comprec_config", array(
			"id" => array("int", $id),
			"user_id" => array("int", $a_user_id),
			"name" => array("text", $a_key),
			"value" => array("text", $a_val)));

		return true;
	}
}
