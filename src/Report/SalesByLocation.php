<?php

namespace Message\Mothership\Commerce\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Cog\Routing\UrlGenerator;
use Message\Cog\Event\DispatcherInterface;

use Message\Mothership\Report\Chart\TableChart;
use Message\Mothership\Report\Filter\DateRange;
use Message\Mothership\Report\Filter\Choices;


class SalesByLocation extends AbstractSales
{
	/**
	 * Constructor.
	 *
	 * @param QueryBuilderFactory   $builderFactory
	 * @param UrlGenerator          $routingGenerator
	 * @param DispatcherInterface   $eventDispatcher
	 */
	public function __construct(QueryBuilderFactory $builderFactory, UrlGenerator $routingGenerator, DispatcherInterface $eventDispatcher)
	{
		parent::__construct($builderFactory, $routingGenerator, $eventDispatcher);
		$this->name = 'sales_by_location';
		$this->displayName = 'Sales by Location';
		$this->description =
			"This report groups the total income by the country of the order delivery address.
			By default it includes all data (orders, returns, shipping) from the last 12 months (by completed date).";
		$this->reportGroup = "Sales";
	}

	/**
	 * Retrieves JSON representation of the data and columns.
	 * Applies data to chart types set on report.
	 *
	 * @return Array  Returns all types of chart set on report with appropriate data.
	 */
	public function getCharts()
	{
		$data = $this->_dataTransform($this->_getQuery()->run(), "json");
		$columns = $this->_parseColumns($this->getColumns());

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	/**
	 * Set columns for use in reports.
	 *
	 * @return array  Returns array of columns as keys with format for Google Charts as the value.
	 */
	public function getColumns()
	{
		return [
			'Country'  => 'string',
			'Currency' => 'string',
			'Net'      => 'number',
			'Tax'      => 'number',
			'Gross'    => 'number',
		];
	}

	/**
	 * Dispatches event to get all sales, returns & shipping queries.
	 *
	 * Unions all sub queries & creates parent query.
	 * Sum all totals and grouping by COUNTRY & CURRENCY.
	 * Order by GROSS DESC.
	 *
	 * @return Query
	 */
	protected function _getQuery()
	{
		$unions = $this->_dispatchEvent()->getQueryBuilders();

		$fromQuery = $this->_builderFactory->getQueryBuilder();

		foreach($unions as $query) {
			$fromQuery->unionAll($query);
		}

		$queryBuilder = $this->_builderFactory->getQueryBuilder();
		$queryBuilder
			->select('totals.country AS "Country"')
			->select('totals.currency AS "Currency"')
			->select('SUM(totals.net) AS "Net"')
			->select('SUM(totals.tax) AS "Tax"')
			->select('SUM(totals.gross) AS "Gross"')
			->from('totals', $fromQuery)
			->where('country IS NOT NULL')
			->groupBy('country, currency')
			->orderBy('SUM(totals.gross) DESC')
		;

		// Filter dates
		if($this->_filters->exists('date_range')) {

			$defaultDate = 'date BETWEEN UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 3 MONTH)) AND UNIX_TIMESTAMP(NOW())';

			$dateFilter = $this->_filters->get('date_range');

			if($date = $dateFilter->getStartDate()) {
				$queryBuilder->where('date > ?d', [$date->format('U')]);
				$defaultDate = NULL;
			}

			if($date = $dateFilter->getEndDate()) {
				$queryBuilder->where('date < ?d', [$date->format('U')]);
				$defaultDate = NULL;
			}

			if($defaultDate) {
				$queryBuilder->where($defaultDate);
			}
		}

		// Filter currency
		if($this->_filters->exists('currency')) {
			$currency = $this->_filters->get('currency');
			if($currency = $currency->getChoices()) {
				is_array($currency) ?
					$queryBuilder->where('Currency IN (?js)', [$currency]) :
					$queryBuilder->where('Currency = (?s)', [$currency])
				;
			}
		}

		// Filter source
		if($this->_filters->exists('source')) {
			$source = $this->_filters->get('source');
			if($source = $source->getChoices()) {
				is_array($source) ?
					$queryBuilder->where('Source IN (?js)', [$source]) :
					$queryBuilder->where('Source = (?s)', [$source])
				;
			}
		}

		// Filter type
		if($this->_filters->exists('type')) {
			$type = $this->_filters->get('type');
			if($type = $type->getChoices()) {
				is_array($type) ?
					$queryBuilder->where('Type IN (?js)', [$type]) :
					$queryBuilder->where('Type = (?s)', [$type])
				;
			}
		}

		return $queryBuilder->getQuery();
	}

	/**
	 * Takes the data and transforms it into a useable format.
	 *
	 * @param  $data    DB\Result    The data from the report query.
	 * @param  $output  string|null  The type of output required.
	 *
	 * @return string|array  Returns columns as string in JSON format or array.
	 */
	protected function _dataTransform($data, $output = null)
	{
		$result = [];

		if ($output === "json") {

			foreach ($data as $row) {
				$result[] = [
					$row->Country,
					$row->Currency,
					[
						'v' => (float) $row->Net,
						'f' => (string) number_format($row->Net,2,'.',',')
					],
					[
						'v' => (float) $row->Tax,
						'f' => (string) number_format($row->Tax,2,'.',',')
					],
					[
						'v' => (float) $row->Gross,
						'f' => (string) number_format($row->Gross,2,'.',',')
					],
				];
			}
			return json_encode($result);

		} else {

			foreach ($data as $row) {
				$result[] = [
					$row->Country,
					$row->Currency,
					$row->Net,
					$row->Tax,
					$row->Gross
				];
			}
			return $result;
		}
	}
}