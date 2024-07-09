<?php

namespace Igniter\Admin\Widgets;

use Carbon\Carbon;
use Igniter\Admin\Classes\BaseBulkActionWidget;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Classes\ListColumn;
use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Html\HtmlFacade as Html;
use Igniter\Local\Traits\LocationAwareWidget;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Lists extends BaseWidget
{
    use LocationAwareWidget;

    /** List action configuration. */
    public array $bulkActions = [];

    /** List column configuration. */
    public array $columns = [];

    public ?Model $model = null;

    /** Message to display when there are no records in the list. */
    public string $emptyMessage = 'lang:igniter::admin.text_empty';

    /** Maximum rows to display for each page. */
    public ?int $pageLimit = null;

    /** Display a checkbox next to each record row. */
    public bool $showCheckboxes = true;

    /** Display the list set up used for column visibility and ordering. */
    public bool $showSetup = true;

    /** string Display pagination when limiting records per page. */
    public bool|string $showPagination = 'auto';

    /** Display page numbers with pagination, disable to improve performance. */
    public bool $showPageNumbers = true;

    /** Display a drag handle next to each record row. */
    public bool $showDragHandle = false;

    /** Shows the sorting options for each column. */
    public bool $showSorting = true;

    /** A default sort column to look for. */
    public null|string|array $defaultSort = null;

    protected string $defaultAlias = 'list';

    /** Collection of all list columns used in this list. */
    protected array $allColumns = [];

    /** Override default columns with supplied key names. */
    protected ?array $columnOverride = null;

    /** Columns to display and their order. */
    protected array $visibleColumns = [];

    /** Model data collection. */
    protected LengthAwarePaginator $records;

    /** Current page number. */
    protected ?int $currentPageNumber = null;

    /** Filter the records by a search term. */
    protected ?string $searchTerm = null;

    /**
     * If searching the records, specifies a policy to use.
     * - all: result must contain all words
     * - any: result can contain any word
     * - exact: result must contain the exact phrase
     */
    protected ?string $searchMode = null;

    /** Use a custom scope method for performing searches. */
    protected ?string $searchScope = null;

    /** Collection of functions to apply to each list query. */
    protected array $filterCallbacks = [];

    /** All sortable columns. */
    protected ?array $sortableColumns = null;

    /** Sets the list sorting column. */
    protected ?string $sortColumn = null;

    /** Sets the list sorting direction (asc, desc) */
    protected ?string $sortDirection = null;

    protected array $allBulkActions = [];

    protected array $availableBulkActions = [];

    protected array $bulkActionWidgets = [];

    public function initialize()
    {
        $this->fillFromConfig([
            'bulkActions',
            'columns',
            'model',
            'emptyMessage',
            'pageLimit',
            'showSetup',
            'showPagination',
            'showDragHandle',
            'showCheckboxes',
            'showSorting',
            'defaultSort',
        ]);

        $this->pageLimit = $this->getSession('page_limit', $this->pageLimit ?? 20);

        if ($this->showPagination == 'auto') {
            $this->showPagination = $this->pageLimit && $this->pageLimit > 0;
        }

        $this->validateModel();
    }

    public function loadAssets()
    {
        $this->addJs('lists.js', 'lists-js');
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('lists/list');
    }

    public function prepareVars()
    {
        $this->vars['listId'] = $this->getId();
        $this->vars['bulkActions'] = $this->getAvailableBulkActions();
        $this->vars['columns'] = $this->getVisibleColumns();
        $this->vars['columnTotal'] = $this->getTotalColumns();
        $this->vars['records'] = $this->getRecords();
        $this->vars['emptyMessage'] = lang($this->emptyMessage);
        $this->vars['showCheckboxes'] = $this->showCheckboxes;
        $this->vars['showDragHandle'] = $this->showDragHandle;
        $this->vars['showSetup'] = $this->showSetup;
        $this->vars['showFilter'] = count($this->filterCallbacks);
        $this->vars['showPagination'] = $this->showPagination;
        $this->vars['showPageNumbers'] = $this->showPageNumbers;
        $this->vars['showSorting'] = $this->showSorting;
        $this->vars['sortColumn'] = $this->getSortColumn();
        $this->vars['sortDirection'] = $this->sortDirection;
    }

    /**
     * Event handler for refreshing the list.
     */
    public function onRefresh(): array
    {
        return [
            '~#'.$this->getId('list') => $this->render(),
        ];
    }

    /**
     * Event handler for switching the page number.
     */
    public function onPaginate(): array
    {
        $this->currentPageNumber = input('page');

        return $this->onRefresh();
    }

    protected function validateModel(): Model
    {
        if (!$this->model instanceof Model) {
            throw new SystemException(sprintf(lang('igniter::admin.list.missing_model'), get_class($this->controller)));
        }

        return $this->model;
    }

    /**
     * Replaces the @ symbol with a table name in a model
     */
    protected function parseTableName(string $sql, string $table): string
    {
        return str_replace('@', DB::getTablePrefix().$table.'.', $sql);
    }

    /**
     * Applies any filters to the model.
     */
    protected function prepareModel(): Builder
    {
        $query = $this->model->newQuery();
        $primaryTable = $this->model->getTable();
        $selects = [$primaryTable.'.*'];
        $joins = [];
        $withs = [];

        // Extensibility
        $this->fireSystemEvent('admin.list.extendQueryBefore', [$query]);

        // Prepare searchable column names
        $primarySearchable = [];
        $relationSearchable = [];

        if (!empty($this->searchTerm) && ($searchableColumns = $this->getSearchableColumns())) {
            foreach ($searchableColumns as $column) {
                // Relation
                if ($this->isColumnRelated($column)) {
                    $table = DB::getTablePrefix().$this->model->{$column->relation}()->getModel()->getTable();
                    $columnName = isset($column->sqlSelect)
                        ? DB::raw($this->parseTableName($column->sqlSelect, $table))->getValue(DB::connection()->getSchemaGrammar())
                        : $table.'.'.$column->valueFrom;

                    $relationSearchable[$column->relation][] = $columnName;
                } // Primary
                else {
                    $columnName = isset($column->sqlSelect)
                        ? DB::raw($this->parseTableName($column->sqlSelect, $primaryTable))->getValue(DB::connection()->getSchemaGrammar())
                        : DB::getTablePrefix().$primaryTable.'.'.$column->columnName;

                    $primarySearchable[] = $columnName;
                }
            }
        }

        // Prepare related eager loads (withs) and custom selects (joins)
        foreach ($this->getVisibleColumns() as $column) {
            if (!$this->isColumnRelated($column) || (!isset($column->sqlSelect) && !isset($column->valueFrom))) {
                continue;
            }

            $withs[] = $column->relation;

            $joins[] = $column->relation;
        }

        // Add eager loads to the query
        if ($withs) {
            $query->with(array_unique($withs));
        }

        // Apply search term
        $query->where(function($innerQuery) use ($primarySearchable, $relationSearchable, $joins) {
            // Search primary columns
            if (count($primarySearchable) > 0) {
                $this->applySearchToQuery($innerQuery, $primarySearchable, 'or');
            }

            // Search relation columns
            if ($joins) {
                foreach (array_unique($joins) as $join) {
                    // Apply a supplied search term for relation columns and
                    // constrain the query only if there is something to search for
                    $columnsToSearch = array_get($relationSearchable, $join, []);
                    if (count($columnsToSearch) > 0) {
                        $innerQuery->orWhereHas($join, function($_query) use ($columnsToSearch) {
                            $this->applySearchToQuery($_query, $columnsToSearch);
                        });
                    }
                }
            }
        });

        // Custom select queries
        foreach ($this->getVisibleColumns() as $column) {
            if (!isset($column->sqlSelect)) {
                continue;
            }

            $alias = $query->getQuery()->getGrammar()->wrap($column->columnName);

            // Relation column
            if (isset($column->relation)) {
                $relationType = $this->getRelationType($column->relation);
                if ($relationType == 'morphTo') {
                    throw new SystemException(sprintf(lang('igniter::admin.list.alert_relationship_not_supported'), 'morphTo'));
                }

                $relationObj = $this->model->{$column->relation}();
                $table = $relationObj->getModel()->getTable();
                $sqlSelect = $this->parseTableName($column->sqlSelect, $table);

                // Manipulate a count query for the sub query
                $countQuery = $relationObj->getRelationExistenceCountQuery($relationObj->getRelated()->newQueryWithoutScopes(), $query);

                $joinSql = $this->isColumnRelated($column, true)
                    ? Db::raw('group_concat('.$sqlSelect." separator ', ')")
                    : Db::raw($sqlSelect);

                $joinSql = $countQuery->select($joinSql)->toRawSql();

                $selects[] = Db::raw('('.$joinSql.') as '.$alias);
            } // Primary column
            else {
                $sqlSelect = $this->parseTableName($column->sqlSelect, $primaryTable);
                $selects[] = Db::raw($sqlSelect.' as '.$alias);
            }
        }

        // Apply sorting
        if ($sortColumn = $this->getSortColumn()) {
            if (($column = array_get($this->allColumns, $sortColumn)) && $column->valueFrom) {
                $sortColumn = $column->valueFrom;
            }

            $query->orderBy($sortColumn, $this->sortDirection);
        }

        // Apply filters
        foreach ($this->filterCallbacks as $callback) {
            $callback($query);
        }

        // Add custom selects
        $query->select($selects);

        // Extensibility
        if ($event = $this->fireSystemEvent('admin.list.extendQuery', [$query])) {
            return $event;
        }

        return $query;
    }

    /**
     * Returns all the records from the supplied model, after filtering.
     */
    protected function getRecords(): LengthAwarePaginator
    {
        $model = $this->prepareModel();

        if (!$this->currentPageNumber) {
            $this->currentPageNumber = input('page');
        }

        if ($this->showPagination) {
            $records = $model->paginate($this->pageLimit, $this->currentPageNumber);
        } else {
            $records = $model->get();
        }

        if ($event = $this->fireSystemEvent('admin.list.extendRecords', [&$records])) {
            $records = $event;
        }

        return $this->records = $records;
    }

    /**
     * Get all the registered columns for the instance.
     */
    public function getColumns(): array
    {
        return $this->allColumns ?: $this->defineListColumns();
    }

    /**
     * Get a specified column object
     */
    public function getColumn(string $column): ListColumn
    {
        return $this->allColumns[$column];
    }

    /**
     * Returns the list columns that are visible by list settings or default
     */
    public function getVisibleColumns(): array
    {
        $definitions = $this->defineListColumns();
        $columns = [];

        if ($this->columnOverride === null) {
            $this->columnOverride = $this->getSession('visible');
        }

        if ($this->columnOverride && is_array($this->columnOverride)) {
            $invalidColumns = array_diff($this->columnOverride, array_keys($definitions));
            if (!count($definitions)) {
                throw new SystemException(sprintf(
                    lang('igniter::admin.list.missing_column'), implode(',', $invalidColumns)
                ));
            }

            $availableColumns = array_intersect($this->columnOverride, array_keys($definitions));
            foreach ($availableColumns as $columnName) {
                $definitions[$columnName]->invisible = false;
                $columns[$columnName] = $definitions[$columnName];
            }
        } else {
            foreach ($definitions as $columnName => $column) {
                if ($column->invisible) {
                    continue;
                }

                $columns[$columnName] = $column;
            }
        }

        return $this->visibleColumns = $columns;
    }

    /**
     * Builds an array of list columns with keys as the column name and values as a ListColumn object.
     */
    protected function defineListColumns(): array
    {
        if (!count($this->columns)) {
            throw new SystemException(sprintf(lang('igniter::admin.list.missing_column'), get_class($this->controller)));
        }

        $this->addColumns($this->columns);

        // Extensibility
        $this->fireSystemEvent('admin.list.extendColumns');

        // Use a supplied column order
        if ($columnOrder = $this->getSession('order')) {
            $orderedDefinitions = [];
            foreach ($columnOrder as $column) {
                if (isset($this->allColumns[$column])) {
                    $orderedDefinitions[$column] = $this->allColumns[$column];
                }
            }

            $this->allColumns = array_merge($orderedDefinitions, $this->allColumns);
        }

        $this->applyFiltersFromModel();

        return $this->allColumns;
    }

    /**
     * Allow the model to filter columns.
     */
    protected function applyFiltersFromModel()
    {
        if (method_exists($this->model, 'filterColumns')) {
            $this->model->filterColumns((object)$this->allColumns);
        }
    }

    /**
     * Programmatically add columns, used internally and for extensibility.
     */
    public function addColumns(array $columns)
    {
        foreach ($columns as $columnName => $config) {
            // Check if admin has permissions to show this column
            $permissions = array_get($config, 'permissions');
            if (!empty($permissions) && !AdminAuth::getUser()->hasPermission($permissions, false)) {
                continue;
            }

            // Check that the filter scope matches the active location context
            if ($this->isLocationAware($config)) {
                continue;
            }

            $this->allColumns[$columnName] = $this->makeListColumn($columnName, $config);
        }
    }

    public function removeColumn(string $columnName)
    {
        if (isset($this->allColumns[$columnName])) {
            unset($this->allColumns[$columnName]);
        }
    }

    /**
     * Creates a list column object from it's name and configuration.
     */
    public function makeListColumn(string $name, string|array $config): ListColumn
    {
        if (is_string($config)) {
            $label = $config;
        } elseif (isset($config['label'])) {
            $label = $config['label'];
        } else {
            $label = studly_case($name);
        }

        if (starts_with($name, 'pivot[') && str_contains($name, ']')) {
            $_name = name_to_array($name);
            $config['relation'] = array_shift($_name);
            $config['valueFrom'] = array_shift($_name);
            $config['searchable'] = false;
        } elseif (str_contains($name, '[') && str_contains($name, ']')) {
            $config['valueFrom'] = $name;
            $config['sortable'] = false;
            $config['searchable'] = false;
        }

        $columnType = $config['type'] ?? null;

        $column = new ListColumn($name, $label);
        $column->displayAs($columnType, $config);

        return $column;
    }

    /**
     * Calculates the total columns used in the list, including checkboxes
     * and other additions.
     */
    protected function getTotalColumns(): int
    {
        $columns = $this->visibleColumns ?: $this->getVisibleColumns();
        $total = count($columns);
        if ($this->showCheckboxes) {
            $total++;
        }

        if ($this->showSetup) {
            $total++;
        }

        if ($this->showDragHandle) {
            $total++;
        }

        return $total;
    }

    /**
     * Looks up the column header
     */
    public function getHeaderValue(ListColumn $column): string
    {
        $value = lang($column->label);

        // Extensibility
        if ($response = $this->fireSystemEvent('admin.list.overrideHeaderValue', [$column, $value])) {
            $value = $response;
        }

        return $value;
    }

    /**
     * Looks up the column value
     */
    public function getColumnValue(mixed $record, ListColumn $column): ?string
    {
        $columnName = $column->columnName;

        // Handle taking value from model attribute.
        $value = $this->getValueFromData($record, $column, $columnName);

        if (method_exists($this, 'eval'.studly_case($column->type).'TypeValue')) {
            $value = $this->{'eval'.studly_case($column->type).'TypeValue'}($record, $column, $value);
        }

        // Apply default value.
        if ($value === '' || $value === null) {
            $value = $column->defaults;
        }

        // Extensibility
        if ($response = $this->fireSystemEvent('admin.list.overrideColumnValue', [$record, $column, $value])) {
            $value = $response;
        }

        if (is_callable($column->formatter) && ($response = ($column->formatter)($record, $column, $value)) !== null) {
            $value = $response;
        }

        return $value;
    }

    public function getButtonAttributes(mixed $record, ListColumn $column): string
    {
        $result = $column->attributes;

        // Extensibility
        if ($response = $this->fireSystemEvent('admin.list.overrideColumnValue', [$record, $column, $result])) {
            $result = $response;
        }

        if (!is_array($result)) {
            $result = '';
        }

        if (isset($result['title'])) {
            $result['title'] = e(lang($result['title']));
        }

        $result['class'] = $result['class'] ?? null;

        foreach ($result as $key => $value) {
            if ($key == 'href' && !preg_match('#^(\w+:)?//#', $value)) {
                $result[$key] = $this->controller->pageUrl($value);
            } elseif (is_string($value)) {
                $result[$key] = lang($value);
            }
        }

        if (isset($result['url'])) {
            $result['href'] = $result['url'];
            unset($result['url']);
        }

        $data = $record->toArray();
        $data += [$record->getKeyName() => $record->getKey()];

        return parse_values($data, Html::attributes($result));
    }

    public function getValueFromData(mixed $record, ListColumn $column, string $columnName): mixed
    {
        if ($column->valueFrom && $column->relation) {
            $columnName = $column->relation;

            if (!array_key_exists($columnName, $record->getRelations())) {
                $value = null;
            } elseif ($this->isColumnRelated($column, true)) {
                $value = implode(', ', $record->{$columnName}->pluck($column->valueFrom)->all());
            } elseif ($this->isColumnRelated($column) || $this->isColumnPivot($column)) {
                $value = $record->{$columnName} ? $record->{$columnName}->{$column->valueFrom} : null;
            } else {
                $value = null;
            }
        } elseif ($column->valueFrom) {
            $keyParts = name_to_array($column->valueFrom);
            $value = $record;
            foreach ($keyParts as $key) {
                $value = $value->{$key};
            }
        } else {
            $value = $record->{$columnName};
        }

        return $value;
    }

    //
    // Value processing
    //

    /**
     * Process as text, escape the value
     */
    protected function evalTextTypeValue(mixed $record, ListColumn $column, mixed $value): string
    {
        return htmlentities((string)$value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Process as partial reference
     */
    protected function evalPartialTypeValue(mixed $record, ListColumn $column, mixed $value): mixed
    {
        return $this->makePartial($column->path ?: $column->columnName, [
            'listColumn' => $column,
            'listRecord' => $record,
            'listValue' => $value,
            'column' => $column,
            'record' => $record,
            'value' => $value,
        ]);
    }

    /**
     * Process as partial reference
     */
    protected function evalMoneyTypeValue(mixed $record, ListColumn $column, mixed $value): string
    {
        return number_format($value, 2);
    }

    /**
     * Process as boolean control
     */
    protected function evalSwitchTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        $onText = lang($column->config['onText'] ?? 'igniter::admin.text_enabled');
        $offText = lang($column->config['offText'] ?? 'igniter::admin.text_disabled');

        return $value ? $onText : $offText;
    }

    /**
     * Process as a datetime value
     */
    protected function evalDatetimeTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $format = $column->format ?? lang('igniter::system.moment.date_time_format');
        $format = parse_date_format($format);

        return $dateTime->isoFormat($format);
    }

    /**
     * Process as a time value
     */
    protected function evalTimeTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $format = $column->format ?? lang('igniter::system.moment.time_format');
        $format = parse_date_format($format);

        return $dateTime->isoFormat($format);
    }

    /**
     * Process as a date value
     */
    protected function evalDateTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $format = $column->format ?? lang('igniter::system.moment.date_format');
        $format = parse_date_format($format);

        return $dateTime->isoFormat($format);
    }

    /**
     * Process as diff for humans (1 min ago)
     */
    protected function evalTimesinceTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        return $dateTime->diffForHumans();
    }

    /**
     * Process as diff for humans (today)
     */
    protected function evalDatesinceTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        return day_elapsed($dateTime, false);
    }

    /**
     * Process as time as current tense (Today at 0:00)
     */
    protected function evalTimetenseTypeValue(mixed $record, ListColumn $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $dateTime = $this->validateDateTimeValue($value, $column);

        return day_elapsed($dateTime);
    }

    /**
     * Process as partial reference
     */
    protected function evalCurrencyTypeValue(mixed $record, ListColumn $column, mixed $value): string
    {
        return currency_format((float)$value);
    }

    /**
     * Validates a column type as a date
     */
    protected function validateDateTimeValue(mixed $value, ListColumn $column): \DateTimeInterface
    {
        $value = make_carbon($value);

        if (!$value instanceof Carbon) {
            throw new FlashException(sprintf(
                lang('igniter::admin.list.invalid_column_datetime'), $column->columnName
            ));
        }

        return $value;
    }

    //
    // Filtering
    //

    public function addFilter(callable $filter)
    {
        $this->filterCallbacks[] = $filter;
    }

    //
    // Searching
    //

    /**
     * Applies a search term to the list results, searching will disable tree
     * view if a value is supplied.
     */
    public function setSearchTerm(string $term)
    {
        $this->searchTerm = $term;
    }

    /**
     * Applies a search options to the list search.
     */
    public function setSearchOptions(array $options = [])
    {
        extract(array_merge([
            'mode' => null,
            'scope' => null,
        ], $options));

        $this->searchMode = $mode;
        $this->searchScope = $scope;
    }

    /**
     * Returns a collection of columns which can be searched.
     */
    protected function getSearchableColumns(): array
    {
        $columns = $this->getColumns();
        $searchable = [];

        foreach ($columns as $column) {
            if (!$column->searchable) {
                continue;
            }

            $searchable[] = $column;
        }

        return $searchable;
    }

    /**
     * Applies the search constraint to a query.
     */
    protected function applySearchToQuery(Builder $query, array $columns, string $boolean = 'and')
    {
        $term = $this->searchTerm;

        if ($scopeMethod = $this->searchScope) {
            $searchMethod = $boolean == 'and' ? 'where' : 'orWhere';
            $query->$searchMethod(function($q) use ($term, $scopeMethod) {
                $q->$scopeMethod($term);
            });
        } else {
            $searchMethod = $boolean == 'and' ? 'search' : 'orSearch';
            $query->$searchMethod($term, $columns, $this->searchMode);
        }
    }

    //
    // Sorting
    //

    /**
     * Event handler for sorting the list.
     */
    public function onSort(): array
    {
        if (!$column = input('sort_by')) {
            return [];
        }

        // Toggle the sort direction and set the sorting column
        $sortOptions = [$this->getSortColumn(), $this->sortDirection];

        if ($column != $sortOptions[0] || strtolower($sortOptions[1]) == 'asc') {
            $this->sortDirection = $sortOptions[1] = 'desc';
        } else {
            $this->sortDirection = $sortOptions[1] = 'asc';
        }

        $this->sortColumn = $sortOptions[0] = $column;

        $this->putSession('sort', $sortOptions);

        // Persist the page number
        $this->currentPageNumber = input('page');

        return $this->onRefresh();
    }

    /**
     * Returns the current sorting column, saved in a session or cached.
     */
    protected function getSortColumn(): ?string
    {
        if (!$this->isSortable()) {
            return null;
        }

        if ($this->sortColumn !== null) {
            return $this->sortColumn;
        }

        // User preference
        if ($this->showSorting && ($sortOptions = $this->getSession('sort'))) {
            $this->sortColumn = $sortOptions[0];
            $this->sortDirection = $sortOptions[1];
        } // Supplied default
        else {
            if (is_string($this->defaultSort)) {
                $this->sortColumn = $this->defaultSort;
                $this->sortDirection = 'desc';
            } elseif (is_array($this->defaultSort) && isset($this->defaultSort[0])) {
                $this->sortColumn = $this->defaultSort[0];
                $this->sortDirection = $this->defaultSort[1] ?? 'desc';
            }
        }

        // First available column
        if ($this->sortColumn === null || !$this->isSortable($this->sortColumn)) {
            $columns = $this->visibleColumns ?: $this->getVisibleColumns();
            $columns = array_filter($columns, function($column) {
                return $column->sortable && $column->type != 'button';
            });
            $this->sortColumn = key($columns);
            $this->sortDirection = 'desc';
        }

        return $this->sortColumn;
    }

    /**
     * Returns true if the column can be sorted.
     */
    protected function isSortable(?string $column = null): bool
    {
        if ($column === null) {
            return count($this->getSortableColumns()) > 0;
        }

        return array_key_exists($column, $this->getSortableColumns());
    }

    /**
     * Returns a collection of columns which are sortable.
     */
    protected function getSortableColumns(): array
    {
        if ($this->sortableColumns !== null) {
            return $this->sortableColumns;
        }

        $columns = $this->getColumns();
        $sortable = array_filter($columns, function($column) {
            return $column->sortable;
        });

        return $this->sortableColumns = $sortable;
    }

    //
    // List Setup
    //

    /**
     * Event handler to display the list set up.
     */
    public function onLoadSetup(): array
    {
        $this->vars['columns'] = $this->getSetupListColumns();
        $this->vars['perPageOptions'] = $this->getSetupPerPageOptions();
        $this->vars['pageLimit'] = $this->pageLimit;

        $setupContentId = '#'.$this->getId().'-setup-modal-content';

        return [$setupContentId => $this->makePartial('lists/list_setup_form')];
    }

    /**
     * Event handler to apply the list set up.
     */
    public function onApplySetup(): array
    {
        if (($visibleColumns = post('visible_columns')) && is_array($visibleColumns)) {
            $this->columnOverride = $visibleColumns;
            $this->putSession('visible', $this->columnOverride);
        }

        $pageLimit = post('page_limit');
        $this->pageLimit = $pageLimit ?: $this->pageLimit;
        $this->putSession('order', $visibleColumns);
        $this->putSession('page_limit', $this->pageLimit);

        return $this->onRefresh();
    }

    /**
     * Event handler to reset the list set up.
     */
    public function onResetSetup(): array
    {
        $this->forgetSession('visible');
        $this->forgetSession('order');
        $this->forgetSession('page_limit');

        return $this->onRefresh();
    }

    /**
     * Returns all the list columns used for list set up.
     */
    protected function getSetupListColumns(): array
    {
        $columns = $this->defineListColumns();
        foreach ($columns as $column) {
            $column->invisible = true;
        }

        return array_merge($columns, $this->getVisibleColumns());
    }

    /**
     * Returns an array of allowable records per page.
     */
    protected function getSetupPerPageOptions(): array
    {
        $perPageOptions = [20, 40, 80, 100, 120];
        if (!in_array($this->pageLimit, $perPageOptions)) {
            $perPageOptions[] = $this->pageLimit;
        }

        return $perPageOptions;
    }

    //
    // Bulk Actions
    //

    public function onBulkAction(): array
    {
        if (!strlen($code = request()->input('code', ''))) {
            throw new FlashException(lang('igniter::admin.list.missing_action_code'));
        }

        $parts = explode('.', $code);
        $actionCode = array_shift($parts);
        if (!$bulkAction = array_get($this->getAvailableBulkActions(), $actionCode)) {
            throw new FlashException(sprintf(lang('igniter::admin.list.action_not_found'), $actionCode));
        }

        $checkedIds = request()->input('checked');
        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            throw new FlashException(lang('igniter::admin.list.delete_empty'));
        }

        $alias = request()->input('alias') ?: $this->primaryAlias;

        $query = $this->prepareModel();

        $records = request()->input('select_all') === '1'
            ? $query->get()
            : $query->whereIn($this->model->getKeyName(), $checkedIds)->get();

        $bulkAction->handleAction(request()->input(), $records);

        return $this->controller->refreshList($alias);
    }

    public function renderBulkActionButton(string|ToolbarButton $buttonObj): mixed
    {
        if (is_string($buttonObj)) {
            return $buttonObj;
        }

        $partialName = array_get(
            $buttonObj->config,
            'partial',
            'lists/list_action_button'
        );

        return $this->makePartial($partialName, ['button' => $buttonObj]);
    }

    protected function getAvailableBulkActions(): array
    {
        $this->fireSystemEvent('admin.list.extendBulkActions');

        $allBulkActions = $this->makeBulkActionButtons($this->bulkActions);
        $bulkActions = [];

        foreach ($allBulkActions as $actionCode => $buttonObj) {
            $bulkActions[$actionCode] = $this->makeBulkActionWidget($buttonObj);
        }

        return $this->availableBulkActions = $bulkActions;
    }

    protected function makeBulkActionButtons(array $bulkActions, ?string $parentActionCode = null): array
    {
        $result = [];
        foreach ($bulkActions as $actionCode => $config) {
            if ($parentActionCode) {
                $actionCode = $parentActionCode.'.'.$actionCode;
            }

            // Check if admin has permissions to show this column
            $permissions = array_get($config, 'permissions');
            if (!empty($permissions) && !AdminAuth::getUser()->hasPermission($permissions, false)) {
                continue;
            }

            // Check that the filter scope matches the active location context
            if ($this->isLocationAware($config)) {
                continue;
            }

            $button = $this->makeBulkActionButton($actionCode, $config);

            $this->allBulkActions[$actionCode] = $result[$actionCode] = $button;
        }

        return $result;
    }

    protected function makeBulkActionButton(string $actionCode, array $config): ToolbarButton
    {
        $buttonType = array_get($config, 'type', 'link');

        $buttonObj = new ToolbarButton($actionCode);
        $buttonObj->displayAs($buttonType, $config);

        if ($buttonType === 'dropdown' && array_key_exists('menuItems', $config)) {
            $buttonObj->menuItems($this->makeBulkActionButtons($config['menuItems'], $actionCode));
        }

        return $buttonObj;
    }

    protected function makeBulkActionWidget(ToolbarButton $actionButton): BaseBulkActionWidget
    {
        if (isset($this->bulkActionWidgets[$actionButton->name])) {
            return $this->bulkActionWidgets[$actionButton->name];
        }

        $widgetConfig = $this->makeConfig($actionButton->config);
        $widgetConfig['alias'] = $this->alias.studly_case('bulk_action_'.$actionButton->name);

        $actionCode = array_get($actionButton->config, 'code', $actionButton->name);
        $widgetClass = resolve(Widgets::class)->resolveBulkActionWidget($actionCode);
        if (!class_exists($widgetClass)) {
            throw new SystemException(sprintf(lang('igniter::admin.alert_widget_class_name'), $widgetClass));
        }

        $widget = new $widgetClass($this->controller, $actionButton, $widgetConfig);
        $widget->code = $actionButton->name;

        return $this->bulkActionWidgets[$actionButton->name] = $widget;
    }

    //
    // Helpers
    //

    /**
     * Check if column refers to a relation of the model
     *
     * @param ListColumn $column List column object
     * @param bool $multi If set, returns true only if the relation is a "multiple relation type"
     */
    protected function isColumnRelated(ListColumn $column, bool $multi = false): bool
    {
        if (!isset($column->relation) || $this->isColumnPivot($column)) {
            return false;
        }

        if (!$this->model->hasRelation($column->relation)) {
            throw new SystemException(sprintf(lang('igniter::admin.alert_missing_model_definition'), $this->model::class, $column->relation));
        }

        if (!$multi) {
            return true;
        }

        $relationType = $this->getRelationType($column->relation);

        return in_array($relationType, [
            'hasMany',
            'belongsToMany',
            'morphToMany',
            'morphedByMany',
            'morphMany',
            'attachMany',
            'hasManyThrough',
        ]);
    }

    /**
     * Checks if a column refers to a pivot model specifically.
     */
    protected function isColumnPivot(ListColumn $column): bool
    {
        return isset($column->relation) && $column->relation == 'pivot';
    }

    protected function getRelationType(string $relation): ?string
    {
        return $this->model->getRelationType($relation);
    }
}
