/*
 * Select list class
 */
+function ($) {
    "use strict";

    var SelectList = function (element, options) {
        this.$el = $(element)
        this.$container = null
        this.choices = null

        this.options = options

        this.init()
    }

    SelectList.prototype.constructor = SelectList

    SelectList.prototype.init = function () {
        if (this.$el.find('option').length < 8) {
            this.options.searchEnabled = false
        }

        this.choices = new Choices(this.$el[0], this.options)

        if (this.$el.closest('.table-responsive').length) {
            this.$el.on('showDropdown', $.proxy(this.repositionDropdown, this))
            this.$el.on('hideDropdown', $.proxy(this.revertDropdownPosition, this))
        }
    }

    SelectList.prototype.setChoices = function (choices) {
        this.choices.setChoices(choices, 'value', 'label', true)
    }

    SelectList.prototype.repositionDropdown = function () {
        const $container = this.$el.closest('.choices')
        const $inner = $container.find('.choices__inner');
        const $dropdown = $container.find('.choices__list--dropdown');
        const $tableWrapper = $container.closest('.table-responsive');
        const $tableContainer = $tableWrapper.parent();

        if (!$inner.length || !$dropdown.length || !$tableWrapper.length) return;

        $tableContainer.css('position', 'relative');

        const innerOffset = $inner.offset();
        const containerOffset = $tableContainer.offset();
        const top = innerOffset.top - containerOffset.top + $inner.outerHeight();
        const left = innerOffset.left - containerOffset.left;
        const width = $inner.outerWidth();

        $dropdown.css({
            position: 'absolute',
            top: `${top}px`,
            left: `${left}px`,
            width: `${width}px`,
            zIndex: 9999
        });

        $tableContainer.append($dropdown);
    }

    SelectList.prototype.revertDropdownPosition = function () {
        const $container = this.$el.closest('.choices')
        const $tableWrapper = $container.closest('.table-responsive');
        const $dropdown = $tableWrapper.parent().find('.choices__list--dropdown');

        console.log($container, $dropdown);
        if ($dropdown.length && $container.length) {
            $container.find('.choices__inner').after($dropdown);
            $dropdown.attr('style', '');
        }
    }

    // SELECT LIST PLUGIN DEFINITION
    // ============================

    SelectList.DEFAULTS = {
        removeItemButton: true,
    }

    var old = $.fn.selectList

    $.fn.selectList = function (option) {
        var args = Array.prototype.slice.call(arguments, 1),
            result = undefined

        this.each(function () {
            var $this = $(this)
            var data = $this.data('ti.selectList')
            var options = $.extend({}, SelectList.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('ti.selectList', (data = new SelectList(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.selectList.Constructor = SelectList

    // SELECT LIST NO CONFLICT
    // =================

    $.fn.selectList.noConflict = function () {
        $.fn.selectList = old
        return this
    }

    // SELECT LIST DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="selectlist"]').selectList()
    })

}(window.jQuery);
