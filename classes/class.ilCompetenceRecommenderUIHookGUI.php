<?php
declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

include_once("GUI/class.ilCompetenceRecommenderGUI.php");
include_once("class.ilCompetenceRecommenderAlgorithm.php");

/**
 * Class ilCompetenceRecommenderUIHookGUI
 *
 * Shows the widget on the Personal Desktop if user has a profile that is set active in the config
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_Calls ilCompetenceRecommenderUIHookGUI: ilCompetenceRecommenderGUI
 */
class ilCompetenceRecommenderUIHookGUI extends ilUIHookPluginGUI {

	const PLUGIN_CLASS_NAME = ilCompetenceRecommenderPlugin::class;

	/**
	 * @var \ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var \ilLanguage
	 */
	protected $lng;

	/**
	 * @var \ilTabsGUI
	 */
	protected $tabs;

	/**
	 * @var \ilHelpGUI
	 */
	protected $help;

	/**
	 * @var \ilToolbarGUI
	 */
	protected $toolbar;

	/**
	 * @var \ilAccessHandler
	 */
	var $access;

	/**
	 * @var \ilUIFramework
	 */
	var $ui;

	/**
	 * @var \ilDB
	 */
	var $db;

	/**
	 * @var \ilCompetenceRecommenderPlugin
	 */
	var $pl;


	/**
	 * ilCompetenceRecommenderUIHookGUI constructor
	 */
	public function __construct() {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->access = $DIC->access();
		$this->ui = $DIC->ui();
		$this->db = $DIC->database();
		$this->pl = ilCompetenceRecommenderPlugin::getInstance();
		$this->pl->includeClass("GUI/class.ilCompetenceRecommenderGUI.php");
	}


	/**
	 * Sets the Recommendation to the Personal Desktop
	 *
	 * @param string $a_comp
	 * @param string $a_part
	 * @param array  $a_par
	 *
	 * @return array
	 */
	public function getHTML(/*string*/
		$a_comp, /*string*/
		$a_part, /*array*/
		$a_par = []): array 
	{
		if ($a_comp == "Services/PersonalDesktop" && $a_part == "center_column") {
			// change if recommender should disappear when user has finished all
			if (ilCompetenceRecommenderAlgorithm::hasUserProfile()) { //&& !\ilCompetenceRecommenderAlgorithm::hasUserFinishedAll()) {
				return ["mode" => ilUIHookPluginGUI::PREPEND, "html" => $this->pdRecommendation()];
			}
		}
		return [ "mode" => ilUIHookPluginGUI::KEEP, "html" => "" ];
	}

	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
	}

	/**
	* write on personal desktop
	*
	* @return string HTML of div
	*/
	function pdRecommendation()
	{
		$renderer = $this->ui->renderer();
		$factory = $this->ui->factory();

		// show recommendations in template
		$pl = $this->getPluginObject();
		$btpl = $pl->getTemplate("tpl.comprecDesktop.html");
		$btpl->setVariable("TITLE", $this->lng->txt('ui_uihk_comprec_plugin_title'));

		// render button for dashboard on extra page
		$button = $renderer->render($factory->button()
			->standard($this->lng->txt('ui_uihk_comprec_detail_button'), $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
				ilCompetenceRecommenderGUI::class], 'show')));

		$data = \ilCompetenceRecommenderAlgorithm::getDataForDesktop();
		$allcards = array();

		// if data, show resources, else show init_obj or self-eval
		if ($data != array()) {
			foreach ($data as $row) {
				$obj_id = ilObject::_lookupObjectId($row["id"]);
				$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($row["id"])));
				$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
				$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($row["title"])));
				array_push($allcards, $card);
			};

			$deck = $factory->deck($allcards)->withNormalCardsSize();
			$renderedobjects = $renderer->render($deck);
		} else {
			if (\ilCompetenceRecommenderAlgorithm::noFormationdata()) {
				$init_obj = \ilCompetenceRecommenderAlgorithm::getInitObjects();
				if ($init_obj != array()) {
					foreach ($init_obj as $object) {
						$obj_id = ilObject::_lookupObjectId($object["id"]);
						$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($object["id"])));
						$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
						$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($object["title"])));
						array_push($allcards, $card);
					}
					$deck = $factory->deck($allcards)->withNormalCardsSize();
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata_init_obj') . "<br />" .$renderer->render($deck);
				} else {
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
							$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval')));
					// overwrite button
					$button = $renderer->render($factory->button()
						->standard($this->lng->txt('ui_uihk_comprec_detail_button_nodata'), $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
							ilCompetenceRecommenderGUI::class], 'show')));
				}
			} else if (!\ilCompetenceRecommenderAlgorithm::noResourcesLeft()) {
				$init_obj = \ilCompetenceRecommenderAlgorithm::getInitObjects();
				if ($init_obj != array()) {
					foreach ($init_obj as $object) {
						$obj_id = ilObject::_lookupObjectId($object["id"]);
						$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink($object["id"])));
						$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
						$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($object["title"])));
						array_push($allcards, $card);
					}
					$deck = $factory->deck($allcards)->withNormalCardsSize();
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata_init_obj') . "<br />" . $renderer->render($deck);
				} else {
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
							$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval')));
					// overwrite button
					$button = $renderer->render($factory->button()
						->standard($this->lng->txt('ui_uihk_comprec_detail_button_nodata'), $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
							ilCompetenceRecommenderGUI::class], 'show')));

				}
			} else {
				$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_resources') . " " . $renderer->render($factory->button()->standard($this->lng->txt('ui_uihk_comprec_self_eval'),
						$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval')));
				// overwrite button
				$button = $renderer->render($factory->button()
					->standard($this->lng->txt('ui_uihk_comprec_detail_button_nodata'), $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
						ilCompetenceRecommenderGUI::class], 'show')));
			}
		}

		$linktoinfo = $renderer->render($factory->link()->standard($this->lng->txt('ui_uihk_comprec_info_link'),
						$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'info')));

		// set everything in html
		$btpl->setVariable("OBJECTS", $renderedobjects);
		$btpl->setVariable("BUTTON", $button);
		$btpl->setVariable("LINK", $linktoinfo);
		return $btpl->get();
	}
}
