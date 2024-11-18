<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

declare(strict_types=1);

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Standard;

/**
 * Class ilCompetenceRecommenderConfigGUI
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_Calls ilCompetenceRecommenderConfigGUI: ilCompetenceRecommenderConfigTableGUI
 * @ilCtrl_IsCalledBy ilCompetenceRecommenderConfigGUI: ilObjComponentSettingsGUI
 */
class ilCompetenceRecommenderConfigGUI extends ilPluginConfigGUI
{
	protected ilCtrl $ctrl;
	protected ilGlobalTemplateInterface $tpl;
	protected ilLanguage $lng;
	protected ilDBInterface $db;
	protected Renderer $renderer;
	protected Factory $factory;

    /**
	 * Constructor of the class ilDistributorTrainingsConfigGUI.
	 *
	 * @return 	void
	 */
	public function __construct()
	{
		global $DIC;

        $this->dic = $DIC;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
		$this->db = $DIC->database();
		$this->renderer = $DIC->ui()->renderer();
		$this->factory = $DIC->ui()->factory();
	}

	/**
	 * Delegate incoming commands
	 *
	 * @param string $cmd
	 * @throws Exception if command not known
	 */
	public function performCommand($cmd): void
    {
		$cmd = $this->ctrl->getCmd("configure");
		switch ($cmd) {
			case "configure":
				$this->showConfig();
				break;
			case "set_init_obj":
				$this->repobj();
				break;
			case "save_dropout":
				$this->saveDropout();
				break;
			case "activate_profile":
				$this->saveProfile();
				break;
			case "save_init_obj":
				$this->saveResource();
				break;
			case "delete_init_obj":
				$this->deleteResource();
				break;
			default:
				throw new Exception("ilCompetenceRecommenderConfigGUI: Unknown command: ".$cmd);
				break;
		}
	}

	/**
	 * Returns all possible profiles
	 *
	 * @return array
	 */
	private function profiles(): array
    {
		$result = $this->db->query("SELECT id, title FROM skl_profile");
		$profiles = $this->db->fetchAll($result);
		$profileArray = [];
		foreach ($profiles as $profile) {
			$profileArray[$profile["id"]] = array("id" => $profile["id"], "title" => $profile["title"]);
		}
		return $profileArray;
	}

	/**
	 * saves the dropout value if it is an integer value >= 0
	 */
	private function saveDropout(): void
    {
        $form = $this->initConfigForm();
        $form = $form->withRequest($this->dic->http()->request());
        $form_data = $form->getData();

        if ($form->getError()) {
            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $this->lng->txt("ui_uihk_comprec_dropout_failure"));
            $this->showConfig();
            return;
        }

        $value = (int) $form_data['section']['dropout_input'];

        $save_settings = new ilCompetenceRecommenderSettings();
        $save_settings->set("dropout_input", strval(floor($value)));
        $this->tpl->setOnScreenMessage('info', $this->lng->txt("ui_uihk_comprec_dropout_save"));

		$this->showConfig();
	}

	/**
	 * sets the profile to active or inactive
	 */
	private function saveProfile(): void
    {
        $selected_profile = '';
        if ($this->dic->http()->wrapper()->query()->has('profile_id')) {
            $selected_profile = $this->dic->http()->wrapper()->query()->retrieve('profile_id', $this->dic->refinery()->kindlyTo()->string());
        }

		$save_settings = new ilCompetenceRecommenderSettings();
		if ($save_settings->get("checked_profile_".$selected_profile) != $selected_profile) {
			$save_settings->set("checked_profile_" . $selected_profile, $selected_profile);
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("ui_uihk_comprec_config_saved_active"));
		} else {
			$save_settings->delete("checked_profile_".$selected_profile);
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("ui_uihk_comprec_config_saved_inactive"));
		}
		$this->showConfig();
	}

	/**
	 * Save initiation object for profile
	 */
	public function saveResource(): void
	{
		$ref_id = (int) $_GET["root_id"];
		$selected_profile = $_GET["profile_id"];
		if ($ref_id > 0 && isset($selected_profile)) {
			$save_input = new ilCompetenceRecommenderSettings();
			$save_input->set("init_obj_".$selected_profile, (string) $ref_id);
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
		} else {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("ui_uihk_comprec_error"));
		}

		$this->showConfig();
	}

	/**
	 * Deletes initiation object for profile
	 */
	function deleteResource(): void
	{
		$selected_profile = $_GET["profile_id"];
		if (isset($selected_profile))
		{
			$del_obj = new ilCompetenceRecommenderSettings();
			$del_obj->delete("init_obj_".$selected_profile);
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
		} else {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("ui_uihk_comprec_error"));
		}

		$this->showConfig();
	}

	/**
	 * Shows the main configuration page
	 */
	private function showConfig(): void
    {
		$this->tpl->addJavascript("Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CompetenceRecommender/templates/ProfileSelector.js");

		$available_profiles = $this->profiles();
		$old_data = new ilCompetenceRecommenderSettings();

		$html = "";

		// info text
		$html .= $this->lng->txt("ui_uihk_comprec_config_info");



/*		$form = new ilPropertyFormGUI();
		$form->setTitle($this->lng->txt('ui_uihk_comprec_config_dropout_title'));
		$form->addCommandButton("save_dropout", $this->lng->txt('ui_uihk_comprec_config_save'));
		$form->setFormAction($this->ctrl->getFormAction($this));

		$dropout_input = new ilTextInputGUI($this->lng->txt('ui_uihk_comprec_dropout_input_label'), "dropout_input");
		$dropout_input->setInfo($this->lng->txt('ui_uihk_comprec_dropout_input_info'));
		$dropout_input->setValue($old_data->get("dropout_input"));
		$form->addItem($dropout_input);

		$html .= $form->getHTML();

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->lng->txt('ui_uihk_comprec_profile_config'));

		$html .= $form->getHTML();*/

        $html .= $this->renderer->render($this->initConfigForm());

		$tabledata = [];
		foreach ($available_profiles as $profile) {
			$active = $old_data->get("checked_profile_".$profile["id"]);
			$active == $profile["id"] ? $profile["active"] = $this->lng->txt("ui_uihk_comprec_active") : $profile["active"] = $this->lng->txt("ui_uihk_comprec_inactive");
			$init_obj_id = ilObject::_lookupObjectId((int) $old_data->get("init_obj_".$profile["id"]));
			$profile["init_obj"] = ilObject::_lookupTitle($init_obj_id);
			$tabledata[] = $profile;
		}

		// sets the table for the configuration
		$table = new ilCompetenceRecommenderConfigTableGUI($this, $tabledata, "configure");

		$html .= $table->getHTML();

		$this->tpl->setContent($html);
	}

    /**
     * @return Standard
     * @throws ilCtrlException
     */
    private function initConfigForm(): Standard
    {
        $old_data = new ilCompetenceRecommenderSettings();

        $dropout_input = $this->factory->input()->field()->text($this->lng->txt('ui_uihk_comprec_dropout_input_label'), $this->lng->txt('ui_uihk_comprec_dropout_input_info'))
                           ->withRequired(true)
                           ->withAdditionalTransformation($this->dic->refinery()->custom()->constraint(
                               fn($value) => is_numeric($value) && $value >= 0,
                               $this->lng->txt("ui_uihk_comprec_dropout_failure")
                           ))
                           ->withValue($old_data->get("dropout_input"));

        $section = $this->factory->input()->field()->section([
            'dropout_input' => $dropout_input,
        ], $this->lng->txt('ui_uihk_comprec_config_dropout_title'));

        $form = $this->factory->input()->container()->form()->standard(
            $this->ctrl->getFormActionByClass(self::class, 'save_dropout'),
            ['section' => $section]
        );

        // Load $_POST data if available
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $form->withRequest($this->dic->http()->request());
        }

        return $form;
    }

	/**
	 * shows the repository object picker
	 */
	private function repobj(): void
    {
		$this->tpl->setTitle($this->lng->txt("ui_uihk_comprec_init_obj_title"));

		$initiationsobj = new ilRepositorySelectorExplorerGUI($this, "set_init_obj", $this, "save_init_obj", "root_id");
		//uncomment if only tests and survey are allowed as initiation object
		//$initiationsobj->setClickableTypes(array("tst", "svy"));

		$this->ctrl->setParameterByClass(ilCompetenceRecommenderConfigGUI::class, "profile_id", $_GET["profile_id"]);
		if (!$initiationsobj->handleCommand())
		{
			$button = $this->renderer->render($this->factory->button()->standard($this->lng->txt("ui_uihk_comprec_cancel"), $this->ctrl->getLinkTarget($this, "configure")));
			$this->tpl->setContent($initiationsobj->getHTML(). $button);
		}
	}
}
