/* ========================================================================
 * TastyIgniter: updates.js v2.2.0
 * https://tastyigniter.com/docs/javascript
 * ======================================================================== */

+function ($) {
    "use strict"

    // UPDATES CLASS DEFINITION
    // =========================

    var Updates = function (element, options) {
        this.options = options
        this.$container = $(element)
        this.$itemModal = null

        this.init()
    }

    Updates.DEFAULTS = {
        carteModal: '#carte-modal',
        itemModal: '#item-modal',
        searchContainer: '#marketplace-search',
        searchInput: '#marketplace-search .search',
        updateSteps: [],
        itemInModal: null,
        itemsToApply: [],
        installedItems: []
    }

    Updates.prototype.init = function () {
        this.bindSearch(this.options.searchInput)

        $(document).on('click', '#update-carte', $.proxy(this.onUpdateCarteClick, this))

        $(document).on('click', '[data-control="apply-recommended"]', $.proxy(this.onApplyRecommended, this))

        $(document).on('click', '[data-control="apply-updates"]', $.proxy(this.onApplyUpdateClick, this))

        $(document).on('click', '[data-control="apply-install"]', $.proxy(this.onApplyInstallClick, this))

        $(document).on('click', '[data-control="add-item"]', $.proxy(this.onAddItemClick, this))
    }

    Updates.prototype.executeSteps = function (steps) {

        var self = this,
            success = true,
            requestChain = [],
            failMessages = []

        this.showProgressBar()

        console.log(steps)
        $.each(steps, function (index, step) {
            var timeout = 500

            requestChain.push(function () {
                var deferred = $.Deferred()

                $.request('onProcessItems', {
                    data: step,
                    beforeSend: self.setProgressBar(step.progress),
                    success: function (json) {
                        if (json.success) {
                            setTimeout(function () {
                                self.setProgressBar(json.message)
                                deferred.resolve()
                            }, timeout)
                        } else {
                            setTimeout(function () {
                                success = false
                                failMessages.push(json.message)
                                deferred.reject(json.message)
                            }, timeout)
                        }
                    },
                })

                return deferred
            })
        })

        $.waterfall.apply(this, requestChain).done(function () {
            if (success) {
                self.setProgressBar(null, 'success')
                setTimeout(function () {
                    window.location.reload(true)
                }, 500)
            }
        }).fail(function () {
            self.setProgressBar(failMessages.join('<br> '), 'danger')
        })
    }

    Updates.prototype.openModal = function (itemToOpen, context) {

        this.$itemModal = $(Updates.TEMPLATES.modal)

        if (!itemToOpen || !context) return

        this.$container.after(this.$itemModal)
        this.$itemModal.find('.modal-title').html(itemToOpen.title)

        if (context !== null) {
            this.options.itemInModal = $.extend({}, context, itemToOpen)
            this.loadModal()
        }

        var modal = new bootstrap.Modal(this.$itemModal, {backdrop: 'static', keyboard: false})

        modal.show()
        this.$itemModal.on('hidden.bs.modal', $.proxy(this.clearModal, this))
    }

    Updates.prototype.loadModal = function () {
        if (!this.options.itemInModal) return

        var context = this.options.itemInModal,
            bodyHtml = Mustache.render(Updates.TEMPLATES.modalBody, context),
            footerHtml = Mustache.render(Updates.TEMPLATES.modalFooter, context),
            installedItems = this.options.installedItems

        this.$itemModal.find('.item-details').html(bodyHtml)
        if (context.require.length && context.require.data.length) {
            context.require = context.require.data.map(function (require) {
                return $.extend(require, {installed: ($.inArray(require.code, installedItems) > -1)})
            })

            this.$itemModal.find('.item-details').after(Mustache.render(Updates.TEMPLATES.modalRequire, context))
        }

        this.$itemModal.find('.modal-footer').html(footerHtml)
    }

    Updates.prototype.clearModal = function (event) {
        var $modal = $(event.currentTarget)

        this.$itemModal = null
        this.options.itemInModal = null
        $modal.remove()
    }

    Updates.prototype.applyItemInModal = function ($modal) {
        var self = this

        // push require first
        if (this.options.itemInModal.require.length) {
            this.options.itemInModal.require.map(function (require) {
                if ($modal.find('[data-control="require-item"][data-item-code="'+require.code+'"].active').length < 1)
                    return

                self.options.itemsToApply.push({
                    name: require.code,
                    type: require.type,
                    ver: require.version,
                    action: self.options.itemInModal.action
                })
            })
        }

        this.options.itemsToApply.push({
            name: this.options.itemInModal.code,
            type: this.options.itemInModal.type,
            ver: this.options.itemInModal.version,
            action: this.options.itemInModal.action
        })
    }

    Updates.prototype.showProgressBar = function () {
        if (!this.$itemModal) {
            this.$itemModal = $(Updates.TEMPLATES.modal)
            this.$container.after(this.$itemModal)
            var modal = new bootstrap.Modal(this.$itemModal, {backdrop: 'static', keyboard: false})

            modal.show()
            this.$itemModal.on('hidden.bs.modal', $.proxy(this.clearModal, this))
        }

        var $modalContent = this.$itemModal.find('.modal-content')

        $('> div', $modalContent).slideUp()
        $('.modal-header', this.$itemModal).slideUp()
        $modalContent.html(Updates.TEMPLATES.progressBar)
    }

    Updates.prototype.setProgressBar = function (message, context) {
        var $container = $('#progressBar'),
            $message = $container.find('.message'),
            $spinner = $container.find('.spinner'),
            $icon = $container.find('.icon')

        if (message) {
            if (context === 'danger') {
                message = '<span class="text-danger">'+message+'</span>';
            }

            $message.append(message+'<br>')
        }

        if (context) {
            $icon.addClass('fas fa-3x text-'+context+' fa-circle-'+(context == 'danger' ? 'exclamation' : 'check'))
            $spinner.remove()

            if (context === 'danger') {
                this.$itemModal.find('.modal-content').append([
                    '<div class="modal-footer">',
                    '<button type="button" class="btn btn-secondary btn-block" data-bs-dismiss="modal" aria-hidden="true">Close</button>',
                    '</div>'
                ].join(''))
            }
        }
    }

    Updates.prototype.onUpdateCarteClick = function (event) {
        var $button = $(event.currentTarget),
            $icon = $button.find('.fa'),
            $form = $button.closest('#carte-form'),
            $modal = $button.closest('#carte-modal')

        if ($button.hasClass('disabled')) return

        $button.attr('disable', true).addClass('disabled')
        $icon.removeClass('fa-arrow-right').addClass('fa-spinner fa-spin')

        $form.request('onApplyCarte').always(function () {
            $button.attr('disable', false).removeClass('disabled')
            $icon.removeClass('fa-spinner fa-spin').addClass('fa-arrow-right')
        }).fail(function (xhr) {
            $modal.modal('hide')
        })
    }

    Updates.prototype.onAddItemClick = function (event) {
        var $button = $(event.target).closest('[data-control]'),
            itemCode = $button.data('itemCode'),
            itemType = $button.data('itemType'),
            context = $button.data('itemContext')

        this.openModal({
            title: 'Add '+$button.data('itemName'),
            code: itemCode,
            type: itemType,
            ver: $button.data('itemVersion'),
            action: $button.data('itemAction'),
            submit: $button.data('itemStatus') === 'installed' ? 'Already Added' : 'Add '+itemType
        }, context)
    }

    Updates.prototype.onApplyInstallClick = function (event) {
        var self = this,
            $button = $(event.target),
            $modal = $button.closest('.modal')

        this.applyItemInModal($modal)

        if ($button.hasClass('disabled')) return

        $button.attr('disable', true).addClass('disabled')

        $.request('onApplyItems', {
            data: {items: this.options.itemsToApply}
        }).always(function () {
            $button.attr('disable', false).removeClass('disabled')
        }).fail(function (xhr) {
            $modal.modal('hide')
        }).done(function (json) {
            if (json['steps'])
                self.executeSteps(json['steps'])
        })

        this.options.itemsToApply = []
    }

    Updates.prototype.onApplyUpdateClick = function (event) {
        var self = this,
            $button = $(event.currentTarget)

        if ($button.hasClass('disabled')) return

        $button.attr('disable', true).addClass('disabled')

        $.request('onApplyUpdate').always(function () {
            $button.attr('disable', false).removeClass('disabled')
        }).done(function (json) {
            if (json['steps'])
                self.executeSteps(json['steps'])
        })
    }

    Updates.prototype.onApplyRecommended = function (event) {
        var self = this,
            $button = $(event.currentTarget),
            $modal = $button.closest('.modal'),
            $form = $button.closest('form')

        $button.attr('disabled', true)

        $form.request('onApplyRecommended').always(function () {
            $modal.modal('hide')
        }).done(function (json) {
            if (json['steps'])
                self.executeSteps(json['steps'])
        })
    }

    Updates.prototype.bindSearch = function (field) {
        var self = this,
            $field = $(field),
            $container = $field.closest(this.options.searchContainer),
            searchType = $field.data('searchType'),
            searchAction = $field.data('searchAction')

        if ($field.length === 0) return

        var template = Updates.TEMPLATES.suggestion

        Mustache.parse(template)
        var suggestionTemplate = function (context) {
            return Mustache.render(template, context)
        }

        var engine = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            name: 'items',
            method: 'POST',
            limit: 15,
            remote: {
                url: searchAction+'?filter[type]='+searchType+'&filter[search]=%QUERY',
                wildcard: '%QUERY',
                transform: function (response) {
                    return (response && response.hasOwnProperty('data')) ? response.data : []
                }
            },
        })

        engine.initialize()

        $(field).typeahead({
            highlight: true
        }, {
            name: 'items',
            display: 'name',
            source: engine.ttAdapter(),
            templates: {
                notFound: [
                    '<div class="empty-message">',
                    'unable to find any '+searchType+' that match the current query',
                    '</div>'
                ].join('\n'),
                suggestion: suggestionTemplate
            }
        }).on('typeahead:asyncrequest', function (object, query, data) {
            $('.fa-icon', $container).hide()
            $('.fa-icon.loading', $container).addClass('fa-pulse').show()
        }).on('typeahead:asyncreceive', function (object, query, data) {
            $('.fa-icon', $container).show()
            $('.fa-icon.loading', $container).hide()
        }).on('typeahead:select', function (object, context) {
            self.openModal({
                title: 'Add '+context.name,
                code: context.code,
                type: context.type,
                ver: context.version,
                action: 'install',
                submit: context.installed ? 'Already Added' : 'Add '+context.type
            }, context)
        })
    }

    Updates.TEMPLATES = {
        suggestion: [
            '<div class="item-details">',
            '<div class="item-thumb text-muted">',
            '{{#icon.url}}',
            '<img src="{{icon.url}}" class="img-rounded" alt="No Image" style="width: 48px; height: 48px;" />',
            '{{/icon.url}}{{^icon.url}}',
            '<span class="extension-icon rounded" style="{{icon.styles}};"><i class="{{icon.class}}"></i></span>',
            '{{/icon.url}}',
            '</div>',
            '<div class="item-name">{{name}}</div>',
            '<div class="item-description text-muted text-overflow">{{{description}}}</div>',
            '</div>'
        ].join(''),

        modal: [
            '<div id="item-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">',
            '<div class="modal-dialog"><div class="modal-content"><div class="modal-header">',
            '<h4 class="modal-title"></h4>',
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>',
            '</div><div class="modal-body wrap-none">',
            '<div class="item-details"></div>',
            '</div><div class="modal-footer">',
            '</div></div></div></div></div>',
        ].join(''),

        modalBody: [
            '<div class="text-center py-4 px-3"><a>',
            '{{#icon.url}}',
            '<img src="{{icon.url}}" class="img-rounded" alt="No Image" style="width: 68px; height: 68px;">',
            '{{/icon.url}}{{^icon.url}}',
            '<span class="extension-icon icon-lg rounded" style="{{icon.styles}};"><i class="{{icon.class}}"></i></span>',
            '{{/icon.url}}',
            '</a><div class="pt-4">',
            '<p>{{{description}}}</p><span class="text-muted">Version:</span> <strong>{{version}}</strong>, ',
            '<span class="text-muted">Author:</span> <strong>{{author}}</strong>',
            '</div></div>',
        ].join(''),

        modalRequire: [
            '<ul class="list-group">',
            '<li class="list-group-item list-group-item-warning"><strong>Requires:</strong></li>',
            '{{#require}}',
            '<li class="list-group-item"><div class="media"> ',
            '<div class="media-left media-middle" style="padding-right:20px"><i class="fa {{icon}} text-muted"></i></div>',
            '<div class="media-body media-middle"><span>{{name}}</span></div>',
            '<div class="media-right">',
            '{{#installed}}',
            '<button class="btn btn-default" title="Added" disabled><i class="fa fa-cloud-download"></i></button>',
            '{{/installed}}{{^installed}}',
            '<button class="btn btn-default active" data-title="Add {{name}}" data-bs-toggle="button" aria-pressed="true" autocomplete="off" data-control="require-item" data-item-code="{{code}}" data-item-version="{{version}}" data-item-type="{{type}}"><i class="fa fa-cloud-download text-success"></i></button>',
            '{{/installed}}',
            '</div></li>',
            '{{/require}}',
            '</ul>',
        ].join(''),

        modalFooter: [
            '<div class="text-right">',
            '<button type="button" class="btn btn-link text-decoration-none fw-bold" data-bs-dismiss="modal" aria-hidden="true">Close</button>',
            '&nbsp;&nbsp;&nbsp;&nbsp;',
            '{{#installed}}',
            '<button class="btn btn-primary" disabled><i class="fa fa-cloud-download"></i>&nbsp;&nbsp;{{submit}}</button>',
            '{{/installed}}{{^installed}}',
            '<button type="submit" class="btn btn-primary" data-control="apply-install">',
            '<i class="fa fa-cloud-download"></i>&nbsp;&nbsp;{{submit}}</button>',
            '{{/installed}}',
            '</div>',
        ].join(''),

        progressBar: [
            '<div id="progressBar" class="card p-3"><div class="card-body text-center">',
            '<div class="spinner spinner-grow" role="status"><span class="visually-hidden">Loading...</span></div>',
            '<i class="icon"></i>',
            '<pre class="message bg-black rounded text-start text-white p-3 mt-4 mb-0 text-wrap"><code>Starting...<br></code></pre></div></div></div>',
        ].join(''),
    }

    // UPDATES PLUGIN DEFINITION
    // ==========================

    if ($.ti === undefined) $.ti = {}

    $.fn.updates = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this = $(this)
            var data = $this.data('ti.updates')
            var options = $.extend({}, Updates.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('ti.updates', (data = new Updates(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.updates.Constructor = Updates

    $(document).render(function () {
        $('body').updates()
    })

}(jQuery)
