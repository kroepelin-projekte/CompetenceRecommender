<?php

declare(strict_types=1);

use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Component\Table\Data as DataTable;

class ilCompetenceRecommenderConfigTable
{
    private \ILIAS\DI\Container $dic;
    private \ILIAS\UI\Factory $factory;
    private \ILIAS\UI\Renderer $renderer;
    private ilCtrl $ctrl;
    private ilLanguage $lng;

    /** @var array<int, array<string, mixed>> */
    private array $records;

    private object $parent_obj;

    /**
     * @param object $parent_obj
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(object $parent_obj, array $data)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();

        $this->parent_obj = $parent_obj;
        $this->records = $data;
    }

    public function getHTML(): string
    {
        return $this->renderer->render([$this->getTable()]);
    }

    public function getTable(): DataTable
    {
        $columns = [
            'profile' => $this->factory->table()->column()->text($this->lng->txt('ui_uihk_comprec_profile'))
                ->withHighlight(true)
                ->withIsSortable(true),
            'state' => $this->factory->table()->column()->text($this->lng->txt('ui_uihk_comprec_state'))
                ->withIsSortable(false),
            'init_obj' => $this->factory->table()->column()->text($this->lng->txt('ui_uihk_comprec_init_obj_label'))
                ->withIsSortable(false),
            'actions' => $this->factory->table()->column()->text($this->lng->txt('ui_uihk_comprec_action'))
                ->withIsSortable(false),
        ];

        $request = $this->dic->http()->request();

        $data_retrieval = new class ($this->records, $this->factory, $this->renderer, $this->ctrl, $this->lng, $this->parent_obj) implements \ILIAS\UI\Component\Table\DataRetrieval {
            /** @var array<int, array<string, mixed>> */
            private array $records;
            private \ILIAS\UI\Factory $factory;
            private \ILIAS\UI\Renderer $renderer;
            private ilCtrl $ctrl;
            private ilLanguage $lng;
            private object $parent_obj;

            /**
             * @param array<int, array<string, mixed>> $records
             */
            public function __construct(
                array $records,
                \ILIAS\UI\Factory $factory,
                \ILIAS\UI\Renderer $renderer,
                ilCtrl $ctrl,
                ilLanguage $lng,
                object $parent_obj
            ) {
                $this->records = $records;
                $this->factory = $factory;
                $this->renderer = $renderer;
                $this->ctrl = $ctrl;
                $this->lng = $lng;
                $this->parent_obj = $parent_obj;
            }

            public function getRows(
                \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): \Generator {
                $records = $this->records;

                $order_data = $order->get();
                if (isset($order_data['profile'])) {
                    $direction = $order_data['profile'];
                    usort($records, static fn($a, $b) => strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
                    if ($direction === Order::DESC) {
                        $records = array_reverse($records);
                    }
                }
                $records = array_slice($records, $range->getStart(), $range->getLength());

                foreach ($records as $record) {
                    $id = (int) ($record['id'] ?? 0);
                    $row_id = (string) $id;

                    $this->ctrl->setParameter($this->parent_obj, 'profile_id', $id);
                    $actions = $this->buildActionsDropdownHTML($id);
                    $this->ctrl->setParameter($this->parent_obj, 'profile_id', '');

                    $row = [
                        'profile' => (string) ($record['title'] ?? ''),
                        'state' => (string) ($record['active'] ?? ''),
                        'init_obj' => (string) ($record['init_obj'] ?? ''),
                        'actions' => $actions,
                    ];

                    yield $row_builder->buildDataRow($row_id, $row);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                return count($this->records);
            }

            private function buildActionsDropdownHTML(int $id): string
            {
                $items = [
                    $this->factory->link()->standard(
                        $this->lng->txt('ui_uihk_comprec_deactivate'),
                        $this->ctrl->getLinkTarget($this->parent_obj, 'activate_profile')
                    ),
                    $this->factory->link()->standard(
                        $this->lng->txt('ui_uihk_comprec_set_init_obj'),
                        $this->ctrl->getLinkTarget($this->parent_obj, 'set_init_obj')
                    ),
                    $this->factory->link()->standard(
                        $this->lng->txt('ui_uihk_comprec_delete_init_obj'),
                        $this->ctrl->getLinkTarget($this->parent_obj, 'delete_init_obj')
                    ),
                ];

                $dropdown = $this->factory->dropdown()->standard($items)
                    ->withLabel($this->lng->txt('actions'));
                return $this->renderer->render($dropdown);
            }
        };

        return $this->factory->table()
            ->data('', $columns, $data_retrieval)
            ->withId('comprec_config_tbl')
            ->withRange(new Range(0, 10000))
            ->withRequest($request);
    }
}
