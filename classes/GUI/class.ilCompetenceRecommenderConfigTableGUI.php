<?php
declare(strict_types=1);
include_once("Services/Table/classes/class.ilTable2GUI.php");
include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");

/**
 * TableGUI class for listing the profiles and their settings
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderConfigTableGUI: ilCompetenceRecommenderConfigGUI
 * @ilCtrl_Calls ilCompetenceRecommenderConfigTableGUI: ilCompetenceRecommenderConfigGUI
 */
class ilCompetenceRecommenderConfigTableGUI extends ilTable2GUI
{
	/**
	 * ilCompetenceRecommenderConfigTableGUI constructor.
	 *
	 * @param $a_parent_obj
	 * @param array $data the data to show
	 * @param string $a_parent_cmd the command to set to parent_obj with onclick on action
	 */
	function __construct($a_parent_obj, array $data, $a_parent_cmd = "")
	{
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setId("comprec_config_tbl");

		$this->addColumn($this->lng->txt("ui_uihk_comprec_profile"), "profil");
		$this->addColumn($this->lng->txt("ui_uihk_comprec_state"));
		$this->addColumn($this->lng->txt("ui_uihk_comprec_init_obj_label"));
		$this->addColumn($this->lng->txt("ui_uihk_comprec_action"));

		$this->setDefaultOrderField("profil");

		$this->setEnableHeader(true);
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.comprec_config_row.html",
			"Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender");

		$this->setData($data);
		$this->setLimit(10000);
	}

	/**
	 * overwritten fillRow
	 *
	 * @param array $a_set one element of data array
	 */
	protected function fillRow($a_set)
	{
		$this->tpl->setVariable("PROFILE_NAME", $a_set["title"]);
		$this->tpl->setVariable("ACTIVE", $a_set["active"]);
		$this->tpl->setVariable("INIT_OBJ", $a_set["init_obj"]);

		$actions = $this->getActionMenuEntries($a_set);
		$this->tpl->setVariable("ACTION_SELECTOR", $this->getActionMenu($actions, $a_set["id"]));
	}

	/**
	 * Get action menu for each row
	 *
	 * @param string[] 	$actions
	 * @param int $id
	 * @return string
	 */
	protected function getActionMenu(array $actions, $id) {
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId($id);
		$alist->setListTitle($this->lng->txt("actions"));

		foreach($actions as $caption => $cmd)
		{
			$alist->addItem($caption, "", $cmd);
		}

		return $alist->getHTML();
	}

	/**
	 * Get entries for action menu
	 *
	 * @param string[] $a_set
	 *
	 * @return string[]
	 */
	protected function getActionMenuEntries($a_set)
	{
		$actions = array();
		$this->ctrl->setParameter($this->parent_obj, "profile_id", $a_set["id"]);
		$this->addCommandToActions($actions, $this->lng->txt("ui_uihk_comprec_deactivate"), "activate_profile");
		$this->addCommandToActions($actions, $this->lng->txt("ui_uihk_comprec_set_init_obj"), "set_init_obj");
		$this->addCommandToActions($actions, $this->lng->txt("ui_uihk_comprec_delete_init_obj"), "delete_init_obj");

		return $actions;
	}

	/**
	 * Add command to actions
	 *
	 * @param string[] 	&$actions
	 * @param string 	$caption
	 * @param string 	$command
	 *
	 * @return void
	 */
	protected function addCommandToActions(array &$actions, $caption, $command) {
		$actions[$caption] =
			$this->ctrl->getLinkTarget($this->parent_obj, $command);
	}
}