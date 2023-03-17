+function ($) {
    "use strict";

    var RecordEditor = function (element, options) {
        this.$el = $(element)
        this.options = options

        this.$assignRecordEl = this.$el.find('[data-control="assign-record"]')
        this.$createRecordEl = this.$el.find('[data-control="create-record"]')
        this.$editRecordEl = this.$el.find('[data-control="edit-record"]')
        this.$deleteRecordEl = this.$el.find('[data-control="delete-record"]')

        this.init()
    }

    RecordEditor.prototype.constructor = RecordEditor

    RecordEditor.prototype.init = function () {
        // this.$createRecordEl.on('click', $.proxy(this.onClickFormButton, this))
        // this.$editRecordEl.on('click', $.proxy(this.onClickFormButton, this))
        // this.$deleteRecordEl.on('click', $.proxy(this.onClickDeleteButton, this))
        // this.$assignRecordEl.on('click', $.proxy(this.onClickAssignButton, this))
    }

    // EVENT HANDLERS
    // ============================

    RecordEditor.prototype.onClickFormButton = function (event) {
        var $button = $(event.currentTarget),
            isCreateContext = $button.data('control') === 'create-record'

        new $.ti.recordEditor.modal({
            alias: this.options.alias,
            recordId: isCreateContext ? null : $button.data('recordId'),
            onSave: function () {
                this.hide()
            }
        })
    }

    RecordEditor.prototype.onClickDeleteButton = function (event) {
        var handler = this.options.alias+'::onDeleteRecord',
            $button = $(event.currentTarget),
            confirmMsg = $button.data('confirmMessage')

        $.request(handler, {
            data: {recordId: $button.data('recordId')},
            confirm: confirmMsg
        })
    }

    RecordEditor.prototype.onClickAssignButton = function (event) {
        var handler = this.options.alias+'::onAssignRecord',
            $button = $(event.currentTarget)

        $button.request(handler, {
            data: {recordId: $button.data('recordId')}
        })
    }

    RecordEditor.DEFAULTS = {
        alias: undefined,
    }

    // FormTable PLUGIN DEFINITION
    // ============================

    var old = $.fn.recordEditor

    $.fn.recordEditor = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this = $(this)
            var data = $this.data('ti.recordEditor')
            var options = $.extend({}, RecordEditor.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('ti.recordEditor', (data = new RecordEditor(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.recordEditor.Constructor = RecordEditor

    // RecordEditor NO CONFLICT
    // =================

    $.fn.recordEditor.noConflict = function () {
        $.fn.recordEditor = old
        return this
    }

    // RecordEditor DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="record-editor"]').recordEditor()
    })
}(window.jQuery);
