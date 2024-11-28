<?php

declare(strict_types=1);

/**
 * Class ilCompetenceRecommenderUIHookGUI
 *
 * Shows the widget on the Personal Desktop if user has a profile that is set active in the config
 *
 * @author Leonie Feldbusch <feldbusl@informatik.uni-freiburg.de>
 *
 * @ilCtrl_Calls ilCompetenceRecommenderUIHookGUI: ilCompetenceRecommenderGUI
 */
class ilCompetenceRecommenderUIHookGUI extends ilUIHookPluginGUI
{
	protected ilCtrl $ctrl;
	protected ilLanguage $lng;
	protected ilTabsGUI $tabs;
	protected ilHelpGUI $help;
	protected ilToolbarGUI $toolbar;
    protected ilAccessHandler $access;
    protected \ILIAS\DI\UIServices $ui;
    protected ilDBInterface $db;
    protected ilCompetenceRecommenderPlugin $pl;

    /**
	 * ilCompetenceRecommenderUIHookGUI constructor
	 */
	public function __construct()
    {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->access = $DIC->access();
		$this->ui = $DIC->ui();
		$this->db = $DIC->database();
		$this->pl = ilCompetenceRecommenderPlugin::getInstance();
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
	public function getHTML(string $a_comp, /*array*/ $a_part, /*array*/ $a_par = []): array
	{
		if ($a_comp == "Services/Dashboard" && $a_part == "center_column") {
			// change if recommender should disappear when user has finished all
			if (ilCompetenceRecommenderAlgorithm::hasUserProfile()) { //&& !\ilCompetenceRecommenderAlgorithm::hasUserFinishedAll()) {
				return ["mode" => ilUIHookPluginGUI::PREPEND, "html" => $this->pdRecommendation()];
			}
		}
		return [ "mode" => ilUIHookPluginGUI::KEEP, "html" => "" ];
	}

	/**
	* write on personal desktop
	*
	* @return string HTML of div
	*/
	private function pdRecommendation(): string
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
		$allcards = [];

		// if data, show resources, else show init_obj or self-eval
		if (!is_array($data)) {
			foreach ($data as $row) {
				$obj_id = ilObject::_lookupObjectId((int) $row["id"]);
				$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink((int) $row["id"])));
				$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
				$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($row["title"])));
				$allcards[] = $card;
			};

			$deck = $factory->deck($allcards)->withNormalCardsSize();
			$renderedobjects = $renderer->render($deck);
		} else {
			if (\ilCompetenceRecommenderAlgorithm::noFormationdata()) {
				$init_obj = \ilCompetenceRecommenderAlgorithm::getInitObjects();
				if (!is_array($init_obj)) {
					foreach ($init_obj as $object) {
						$obj_id = ilObject::_lookupObjectId((int) $object["id"]);
						$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink((int) $object["id"])));
						$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
						$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($object["title"])));
						$allcards[] = $card;
					}
					$deck = $factory->deck($allcards)->withNormalCardsSize();
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata_init_obj') . "<br />" .$renderer->render($deck);
				} else {
					$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_formationdata')
                        . "<br>"
                        . $renderer->render($factory->button()->standard(
                            $this->lng->txt('ui_uihk_comprec_self_eval'),
							$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval'))
                        );
					// overwrite button
					$button = $renderer->render(
                        $factory->button()->standard(
                            $this->lng->txt('ui_uihk_comprec_detail_button_nodata'),
                            $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
							ilCompetenceRecommenderGUI::class], 'show')
                        )
                    );
				}
			} else if (!\ilCompetenceRecommenderAlgorithm::noResourcesLeft()) {
				$init_obj = \ilCompetenceRecommenderAlgorithm::getInitObjects();
				if (!is_array($init_obj)) {
					foreach ($init_obj as $object) {
						$obj_id = ilObject::_lookupObjectId((int) $object["id"]);
						$link = $renderer->render($factory->link()->standard(ilObject::_lookupTitle($obj_id), ilLink::_getLink((int) $object["id"])));
						$image = $factory->image()->standard(ilObject::_getIcon($obj_id), "Icon");
						$card = $factory->card()->standard($link, $image)->withSections(array($factory->legacy($object["title"])));
						$allcards[] = $card;
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
				$renderedobjects = $this->lng->txt('ui_uihk_comprec_no_resources')
                    . " "
                    . $renderer->render($factory->button()->standard(
                        $this->lng->txt('ui_uihk_comprec_self_eval'),
						$this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, ilCompetenceRecommenderGUI::class], 'eval'))
                    );
				// overwrite button
				$button = $renderer->render(
                    $factory->button()->standard($this->lng->txt('ui_uihk_comprec_detail_button_nodata'),
                        $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class,
						ilCompetenceRecommenderGUI::class], 'show')
                    )
                );
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
