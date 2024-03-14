<?php

namespace Igniter\Admin\FormWidgets;

use Carbon\Carbon;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;

/**
 * Date picker
 * Renders a date picker field.
 */
class DatePicker extends BaseFormWidget
{
    //
    // Configurable properties
    //

    /** Display mode: datetime, date, time. */
    public string $mode = 'date';

    /** The minimum/the earliest date that can be selected. eg: 2000-01-01 */
    public null|int|string|\DateTimeInterface $startDate = null;

    /** The maximum/latest date that can be selected. eg: 2020-12-31 */
    public null|int|string|\DateTimeInterface $endDate = null;

    public string $dateFormat = 'Y-m-d';

    public string $timeFormat = 'H:i';

    public array $datesDisabled = [];

    //
    // Object properties
    //
    protected string $defaultAlias = 'datepicker';

    public function initialize()
    {
        $this->fillFromConfig([
            'dateFormat',
            'mode',
            'startDate',
            'endDate',
            'datesDisabled',
        ]);

        $this->mode = strtolower($this->mode);

        if ($this->startDate !== null) {
            $this->startDate = is_int($this->startDate)
                ? Carbon::createFromTimestamp($this->startDate)
                : Carbon::parse($this->startDate);
        }

        if ($this->endDate !== null) {
            $this->endDate = is_int($this->endDate)
                ? Carbon::createFromTimestamp($this->endDate)
                : Carbon::parse($this->endDate);
        }
    }

    public function loadAssets()
    {
        $mode = $this->getConfig('mode', 'date');
        if ($mode == 'date' || $mode == 'datetime') {
            $this->addCss('datepicker.css', 'datepicker-css');
        }
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('datepicker/datepicker');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();

        if ($value = $this->getLoadValue()) {
            $value = make_carbon($value, false);
        }

        // Display alias, used by preview mode
        if ($this->mode == 'time') {
            $formatAlias = lang('igniter::system.php.time_format');
        } elseif ($this->mode == 'date') {
            $formatAlias = lang('igniter::system.php.date_format');
        } else {
            $formatAlias = lang('igniter::system.php.date_time_format');
        }

        $find = ['d' => 'dd', 'D' => 'DD', 'm' => 'mm', 'M' => 'MM', 'y' => 'yy', 'Y' => 'yyyy', 'H' => 'HH', 'i' => 'i'];

        $this->vars['timeFormat'] = $this->timeFormat;
        $this->vars['dateFormat'] = $this->dateFormat;
        $this->vars['dateTimeFormat'] = $this->dateFormat.' '.$this->timeFormat;

        $this->vars['datePickerFormat'] = ($this->mode == 'datetime')
            ? convert_php_to_moment_js_format($formatAlias)
            : strtr($this->dateFormat, $find);

        $this->vars['formatAlias'] = $formatAlias;
        $this->vars['value'] = $value;
        $this->vars['field'] = $this->formField;
        $this->vars['mode'] = $this->mode;
        $this->vars['startDate'] = $this->startDate ?? null;
        $this->vars['endDate'] = $this->endDate ?? null;
        $this->vars['datesDisabled'] = $this->datesDisabled;
    }

    public function getSaveValue(mixed $value): mixed
    {
        if ($this->formField->disabled || $this->formField->hidden) {
            return FormField::NO_SAVE_DATA;
        }

        if (!$value) {
            return null;
        }

        return $value;
    }
}
