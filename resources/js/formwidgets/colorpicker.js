/*
 * Color Picker plugin
 *
 * Data attributes:
 * - data-control="colorpicker" - enables the plugin on an element
 */
+function ($) {
    "use strict"

    // FIELD REPEATER CLASS DEFINITION
    // ============================

    var ColorPicker = function (element, options) {
        this.options = options
        this.$el = $(element)
        this.$color = this.$el.find('[data-colorpicker-color]')
        this.$text = this.$el.find('[data-colorpicker-text]')

        // Init
        this.init()
    }

    ColorPicker.DEFAULTS = {
    }

    ColorPicker.prototype.init = function () {
        this.$el.find('[data-swatches-color]').on('click', $.proxy(this.onPresetClick, this))
        this.$color.on('input change', $.proxy(this.onColorChange, this))
        this.$text.on('input', $.proxy(this.onTextInput, this))
        this.$text.on('change blur', $.proxy(this.onTextCommit, this))
    }

    ColorPicker.prototype.unbind = function () {
        this.$el.find('[data-swatches-color]').off('click')
        this.$color.off('input change')
        this.$text.off('input change blur')
        this.$el.removeData('ti.colorpicker')
        this.picker = null
    }

    ColorPicker.prototype.onPresetClick = function (event) {
        var color = $(event.currentTarget).data('swatchesColor')

        this.setColor(color)
    }

    ColorPicker.prototype.onColorChange = function () {
        this.$text.val(this.$color.val())
    }

    ColorPicker.prototype.onTextInput = function () {
        var color = this.normalizeHex(this.$text.val())

        if (color) {
            this.$color.val(color)
        }
    }

    ColorPicker.prototype.onTextCommit = function () {
        var color = this.normalizeHex(this.$text.val())

        if (color) {
            this.setColor(color)
        }
    }

    ColorPicker.prototype.setColor = function (color) {
        color = this.normalizeHex(color) || color

        this.$color.val(color)
        this.$text.val(color)
    }

    ColorPicker.prototype.normalizeHex = function (value) {
        if (!value) {
            return null
        }

        var hex = String(value).trim()

        if (hex.charAt(0) !== '#') {
            hex = '#' + hex
        }

        if (/^#([A-Fa-f0-9]{3})$/.test(hex)) {
            hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]
        }

        if (!/^#([A-Fa-f0-9]{6})$/.test(hex)) {
            return null
        }

        return hex.toLowerCase()
    }

    // FIELD ColorPicker PLUGIN DEFINITION
    // ============================

    var old = $.fn.colorPicker

    $.fn.colorPicker = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this = $(this)
            var data = $this.data('ti.colorpicker')
            var options = $.extend({}, ColorPicker.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('ti.colorpicker', (data = new ColorPicker(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.colorPicker.Constructor = ColorPicker

    // FIELD ColorPicker NO CONFLICT
    // =================

    $.fn.colorPicker.noConflict = function () {
        $.fn.colorPicker = old
        return this
    }

    // FIELD ColorPicker DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="colorpicker"]').colorPicker()
    })

}(window.jQuery)
