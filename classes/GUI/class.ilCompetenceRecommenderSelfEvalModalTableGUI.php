<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

include_once("./Services/Skill/classes/class.ilSkillTree.php");
include_once("./Services/Skill/classes/class.ilSkillTreeNodeFactory.php");
include_once("./Services/Skill/classes/class.ilPersonalSkill.php");

/**
 * Self evaluation, based on ilSelfEvaluationSimpleTableGUI but better usable for modal
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_isCalledBy ilCompetenceRecommenderSelfEvalModalTableGUI: ilCompetenceRecommenderAllGUI
 */
class ilCompetenceRecommenderSelfEvalModalTableGUI extends ilTable2GUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilAccessHandler
	 */
	protected $access;

	/**
	 * @var ilObjUser
	 */
	protected $user;

	protected $top_skill_id;
	protected $tref_id;
	protected $basic_skill_id;
	protected $cur_level_id;
	protected $skill;
	protected $parent_obj;
	protected $levels;

	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_top_skill_id, $a_tref_id,
						 $a_basic_skill_id)
	{
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->access = $DIC->access();
		$this->user = $DIC->user();

		$ilUser = $DIC->user();

		$this->top_skill_id = $a_top_skill_id;
		$this->tref_id = (int)$a_tref_id;
		$this->basic_skill_id = $a_basic_skill_id;
		$this->parent_obj = $a_parent_obj;

		$this->cur_level_id = ilPersonalSkill::getSelfEvaluation($ilUser->getId(),
			$this->top_skill_id, $this->tref_id, $this->basic_skill_id);

		// build title
		$stree = new ilSkillTree();
		if ($this->tref_id != 0) {
			$path = $stree->getPathFull($this->tref_id);
		} else {
			$path = $stree->getPathFull($this->basic_skill_id);
		}
		$title = $path[count($path) - 1]["title"];

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->levels = $this->getLevels();
		$this->setData($this->levels);
		$this->setTitle($title);
		$this->setLimit(9999);
		$this->setId('selfevaltable');

		$this->addColumn("", "", "", true);
		$this->addColumn($this->lng->txt("ui_uihk_comprec_skmg_skill_level"));
		$this->addColumn($this->lng->txt("description"));

		$this->setEnableHeader(true);
		$this->setRowTemplate("tpl.simple_self_eval.html", "Services/Skill");
		$this->disable("footer");
		$this->setEnableTitle(true);

		$this->addCommandButton("saveSelfEvaluation", $this->lng->txt("ui_uihk_comprec_save"));
		$this->setFormAction($this->formAction($a_parent_obj));
		$this->setFormName("selfevalform");
	}

	function formAction($a_parent_obj) {
		return $this->ctrl->getFormAction($a_parent_obj);
	}

	/**
	 * Get levels
	 *
	 * @param
	 * @return
	 */
	function getLevels()
	{
		$this->skill = ilSkillTreeNodeFactory::getInstance($this->basic_skill_id);
		$levels[] = array("id" => 0, "description" => $this->lng->txt("ui_uihk_comprec_skmg_no_skills"));
		foreach ($this->skill->getLevelData() as $k => $v)
		{
			$levels[] = $v;
		}

		return $levels;
	}

	/**
	 * Fill table row
	 */
	protected function fillRow($a_set)
	{
		if ($this->cur_level_id == $a_set["id"])
		{
			$this->tpl->setVariable("CHECKED", "checked='checked'");
		}

		$this->tpl->setVariable("LEVEL_ID", $a_set["id"]);
		$this->tpl->setVariable("SKILL_ID", $this->basic_skill_id);
		$this->tpl->setVariable("TXT_SKILL", $a_set["title"]);
		$this->tpl->setVariable("TXT_SKILL_DESC", $a_set["description"]);
	}

}
?>
