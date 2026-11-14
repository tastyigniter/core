/*
 * Page builder form widget
 *
 * Edits a page as a list of sections with column layouts holding blocks.
 * The whole tree is kept as state and written to a hidden JSON input.
 * Vanilla JS, uses the global Sortable and bootstrap libraries.
 */
(function () {
    'use strict'

    const uid = (prefix) => prefix + Math.random().toString(36).slice(2, 10)

    const clone = (value) => JSON.parse(JSON.stringify(value))

    class PageBuilder {
        constructor(element) {
            this.element = element
            this.input = element.querySelector('[data-page-builder-input]')
            this.config = JSON.parse(element.querySelector('[data-page-builder-config]').textContent)
            this.sectionList = element.querySelector('[data-page-builder-sections]')
            this.emptyMessage = element.querySelector('[data-page-builder-empty]')
            this.modalElement = element.querySelector('[data-page-builder-modal]')
            this.templates = {}
            element.querySelectorAll('template[data-page-builder-template]').forEach((template) => {
                this.templates[template.dataset.pageBuilderTemplate] = template
            })

            this.sections = this.parseValue(this.input.value)
            this.summaries = Object.assign({}, this.config.summaries)
            this.previewMode = !!this.config.previewMode
            this.sortables = []

            // Modal forms cannot live inside the main admin form
            document.body.appendChild(this.modalElement)
            this.modal = new bootstrap.Modal(this.modalElement)

            this.bindEvents()
            this.render()
        }

        parseValue(value) {
            try {
                const sections = JSON.parse(value || '[]')
                return Array.isArray(sections) ? sections : []
            } catch (e) {
                return []
            }
        }

        //
        // Rendering
        //

        render() {
            this.sortables.forEach((sortable) => sortable.destroy())
            this.sortables = []
            this.sectionList.replaceChildren(...this.sections.map((section) => this.renderSection(section)))
            this.emptyMessage.hidden = this.sections.length > 0

            if (!this.previewMode) {
                this.sortables.push(new Sortable(this.sectionList, {
                    handle: '[data-section-handle]',
                    draggable: '[data-section]',
                    animation: 150,
                    onEnd: () => this.syncFromDom(),
                }))
            }

            this.sync()
        }

        renderSection(section) {
            const element = this.templates.section.content.firstElementChild.cloneNode(true)
            const layout = this.config.layouts[section.layout]
            const columnsElement = element.querySelector('[data-section-columns]')
            const layoutSelect = element.querySelector('[data-section-layout]')

            element.dataset.sectionId = section.id
            layoutSelect.value = section.layout
            layoutSelect.disabled = this.previewMode
            element.querySelector('[data-section-summary]').textContent = this.sectionSummary(section)

            columnsElement.style.setProperty('--page-builder-columns', layout.spans
                .map((span) => (span ? span + 'fr' : '1fr'))
                .join(' '))

            columnsElement.replaceChildren(...section.columns.map((column, index) => this.renderColumn(section, column, index)))

            return element
        }

        renderColumn(section, column, index) {
            const element = this.templates.column.content.firstElementChild.cloneNode(true)
            const blocksElement = element.querySelector('[data-column-blocks]')

            element.dataset.columnIndex = index
            blocksElement.replaceChildren(...column.blocks.map((block) => this.renderBlock(block)))
            this.renderBlockMenu(element.querySelector('[data-column-block-menu]'), section, index)

            if (!this.previewMode) {
                this.sortables.push(new Sortable(blocksElement, {
                    group: 'page-builder-blocks-' + this.config.alias,
                    handle: '[data-block-handle]',
                    draggable: '[data-block]',
                    ghostClass: 'opacity-50',
                    animation: 150,
                    onMove: (event) => this.canMoveBlock(event.dragged, event.to),
                    onEnd: () => this.syncFromDom(),
                }))
            }

            return element
        }

        renderBlock(block) {
            const element = this.templates.block.content.firstElementChild.cloneNode(true)
            const definition = this.config.blocks[block.code] || {name: block.code, icon: 'fa fa-cube'}

            element.dataset.blockId = block.id
            element.dataset.blockCode = block.code
            element.querySelector('[data-block-icon]').className = 'fa-fw text-body-secondary ' + (definition.icon || 'fa fa-cube')
            element.querySelector('[data-block-name]').textContent = definition.name
            element.querySelector('[data-block-summary]').textContent = this.summaries[block.id] || ''
            element.querySelector('[data-block-required]').hidden = !definition.required
            element.querySelector('[data-block-action="remove"]').hidden = !!definition.required

            // The whole block opens its settings, so it acts as a button when it has any.
            if (definition.hasFields) {
                element.classList.add('is-editable')
                element.tabIndex = 0
                element.setAttribute('role', 'button')
                element.setAttribute('aria-label', this.config.messages.editBlock.replace(':name', definition.name))
            }

            return element
        }

        renderBlockMenu(menu, section, columnIndex) {
            const usedCodes = this.usedBlockCodes()
            const isWideColumn = this.isWideColumn(section.layout, columnIndex)

            const items = Object.values(this.config.blocks).map((definition) => {
                const item = document.createElement('li')
                const button = document.createElement('button')
                const name = document.createElement('span')

                button.type = 'button'
                button.className = 'dropdown-item'
                button.dataset.addBlock = definition.code
                item.dataset.search = (definition.name + ' ' + (definition.description || '')).toLowerCase()
                button.disabled = (definition.unique && usedCodes.has(definition.code))
                    || (definition.wide && !isWideColumn)

                name.innerHTML = '<i class="fa-fw"></i> '
                name.firstElementChild.className = (definition.icon || 'fa fa-cube') + ' fa-fw'
                name.append(definition.name)
                button.append(name)

                if (definition.description) {
                    const description = document.createElement('small')
                    description.className = 'd-block text-wrap text-body-secondary'
                    description.textContent = definition.description
                    button.append(description)
                }

                item.append(button)

                return item
            })

            menu.replaceChildren(...items)

            const search = menu.closest('.dropdown-menu').querySelector('[data-column-block-search]')
            if (search && search.value) this.filterBlockMenu(search)
        }

        // Shows the blocks whose name or description contains every word searched for.
        filterBlockMenu(search) {
            const picker = search.closest('.dropdown-menu')
            const words = search.value.toLowerCase().split(/\s+/).filter(Boolean)
            let shown = 0

            picker.querySelectorAll('[data-column-block-menu] > li').forEach((item) => {
                item.hidden = !words.every((word) => item.dataset.search.includes(word))
                if (!item.hidden) shown++
            })

            picker.querySelector('[data-column-block-empty]').hidden = shown > 0
        }

        sectionSummary(section) {
            const settings = section.settings || {}
            return [settings.anchor ? '#' + settings.anchor : null, settings.css_class].filter(Boolean).join(' ')
        }

        //
        // State
        //

        sync() {
            this.input.value = JSON.stringify(this.sections)
            this.input.dispatchEvent(new Event('change', {bubbles: true}))
        }

        syncFromDom() {
            const blocksById = {}
            this.eachBlock((block) => {
                blocksById[block.id] = block
            })

            const sectionsById = {}
            this.sections.forEach((section) => {
                sectionsById[section.id] = section
            })

            this.sections = Array.from(this.sectionList.querySelectorAll(':scope > [data-section]')).map((sectionElement) => {
                const section = sectionsById[sectionElement.dataset.sectionId]

                section.columns = Array.from(sectionElement.querySelectorAll('[data-column]')).map((columnElement) => ({
                    blocks: Array.from(columnElement.querySelectorAll('[data-block]'))
                        .map((blockElement) => blocksById[blockElement.dataset.blockId])
                        .filter(Boolean),
                }))

                return section
            })

            this.refreshBlockMenus()
            this.sync()
        }

        refreshBlockMenus() {
            this.sectionList.querySelectorAll('[data-section]').forEach((sectionElement) => {
                const section = this.findSection(sectionElement.dataset.sectionId)
                sectionElement.querySelectorAll('[data-column]').forEach((columnElement) => {
                    this.renderBlockMenu(columnElement.querySelector('[data-column-block-menu]'), section, Number(columnElement.dataset.columnIndex))
                })
            })
        }

        eachBlock(callback) {
            this.sections.forEach((section) => {
                section.columns.forEach((column, columnIndex) => {
                    column.blocks.forEach((block) => callback(block, section, columnIndex))
                })
            })
        }

        usedBlockCodes() {
            const codes = new Set()
            this.eachBlock((block) => codes.add(block.code))
            return codes
        }

        findSection(sectionId) {
            return this.sections.find((section) => section.id === sectionId)
        }

        findBlock(blockId) {
            let found = null
            this.eachBlock((block) => {
                if (block.id === blockId) found = block
            })
            return found
        }

        isWideColumn(layoutCode, columnIndex) {
            const span = this.config.layouts[layoutCode].spans[columnIndex]
            return span !== null && span !== undefined && span >= this.config.wideSpan
        }

        canMoveBlock(blockElement, targetBlocksElement) {
            const definition = this.config.blocks[blockElement.dataset.blockCode]
            if (!definition || !definition.wide) return true

            const sectionElement = targetBlocksElement.closest('[data-section]')
            const columnElement = targetBlocksElement.closest('[data-column]')
            const section = this.findSection(sectionElement.dataset.sectionId)

            return this.isWideColumn(section.layout, Number(columnElement.dataset.columnIndex))
        }

        //
        // Actions
        //

        addSection(layoutCode) {
            const layout = this.config.layouts[layoutCode]

            this.sections.push({
                id: uid('s_'),
                layout: layoutCode,
                settings: clone(this.config.defaultSectionSettings),
                columns: Array.from({length: layout.columns}, () => ({blocks: []})),
            })

            this.render()
            this.sectionList.lastElementChild.scrollIntoView({behavior: 'smooth', block: 'center'})
        }

        duplicateSection(sectionId) {
            const index = this.sections.findIndex((section) => section.id === sectionId)
            const copy = clone(this.sections[index])

            copy.id = uid('s_')
            copy.columns.forEach((column) => {
                column.blocks = column.blocks
                    .filter((block) => !(this.config.blocks[block.code] || {}).unique)
                    .map((block) => {
                        const newId = uid('b_')
                        this.summaries[newId] = this.summaries[block.id] || ''
                        return Object.assign(block, {id: newId})
                    })
            })

            this.sections.splice(index + 1, 0, copy)
            this.render()
        }

        removeSection(sectionId) {
            const section = this.findSection(sectionId)
            const hasRequired = section.columns.some((column) => column.blocks
                .some((block) => (this.config.blocks[block.code] || {}).required))

            if (hasRequired) {
                this.alert(this.config.messages.alertRequiredBlock)
                return
            }

            if (!window.confirm(this.config.messages.confirmRemoveSection)) return

            this.sections = this.sections.filter((item) => item.id !== sectionId)
            this.render()
        }

        changeLayout(sectionId, layoutCode, selectElement) {
            const section = this.findSection(sectionId)
            const layout = this.config.layouts[layoutCode]
            const columns = section.columns.slice(0, layout.columns)
            const extraBlocks = section.columns.slice(layout.columns).flatMap((column) => column.blocks)

            while (columns.length < layout.columns) columns.push({blocks: []})

            if (extraBlocks.length) {
                if (!window.confirm(this.config.messages.confirmLayoutChange)) {
                    selectElement.value = section.layout
                    return
                }
                columns[columns.length - 1].blocks.push(...extraBlocks)
            }

            const breaksWideBlock = columns.some((column, index) => !this.isWideColumn(layoutCode, index)
                && column.blocks.some((block) => (this.config.blocks[block.code] || {}).wide))

            if (breaksWideBlock) {
                this.alert(this.config.messages.alertWideBlock)
                selectElement.value = section.layout
                return
            }

            section.layout = layoutCode
            section.columns = columns
            this.render()
        }

        addBlock(sectionId, columnIndex, code) {
            const definition = this.config.blocks[code]
            const block = {id: uid('b_'), code: code, settings: clone(definition.defaults || {})}

            this.findSection(sectionId).columns[columnIndex].blocks.push(block)
            this.summaries[block.id] = ''
            this.render()

            if (definition.hasFields) this.editBlock(block.id)
        }

        removeBlock(blockId) {
            if (!window.confirm(this.config.messages.confirmRemoveBlock)) return

            this.sections.forEach((section) => {
                section.columns.forEach((column) => {
                    column.blocks = column.blocks.filter((block) => block.id !== blockId)
                })
            })

            delete this.summaries[blockId]
            this.render()
        }

        editBlock(blockId) {
            const block = this.findBlock(blockId)

            this.openForm(this.handler('onLoadBlockForm'), {
                code: block.code,
                settings: JSON.stringify(block.settings),
            }, (response) => {
                block.settings = response.settings
                this.summaries[block.id] = response.summary || ''
                this.render()
            })
        }

        editSection(sectionId) {
            const section = this.findSection(sectionId)

            this.openForm(this.handler('onLoadSectionForm'), {
                settings: JSON.stringify(section.settings),
            }, (response) => {
                section.settings = response.settings
                this.render()
            })
        }

        //
        // Settings modal
        //

        openForm(loadHandler, data, onApply) {
            const content = this.modalElement.querySelector('[data-page-builder-modal-content]')

            content.innerHTML = '<div class="modal-body text-center p-5"><div class="spinner-border" role="status"></div></div>'
            this.modal.show()

            this.request(loadHandler, data).then((response) => {
                content.innerHTML = response.result

                // Initialise jQuery based form field widgets inside the modal
                if (window.jQuery) window.jQuery(document).trigger('render')

                const form = content.querySelector('form[data-page-builder-form]')
                form.addEventListener('submit', (event) => {
                    event.preventDefault()
                    this.submitForm(form, onApply)
                })
            }).catch((error) => {
                this.modal.hide()
                this.alert(error.message)
            })
        }

        submitForm(form, onApply) {
            const submitButton = form.querySelector('[type="submit"]')
            const formData = new FormData(form)

            if (form.dataset.pageBuilderFormCode) formData.append('code', form.dataset.pageBuilderFormCode)

            if (submitButton) submitButton.disabled = true

            this.request(form.dataset.pageBuilderForm, formData).then((response) => {
                onApply(response)
                this.modal.hide()
            }).catch((error) => {
                this.alert(error.message)
            }).finally(() => {
                if (submitButton) submitButton.disabled = false
            })
        }

        //
        // Requests
        //

        handler(name) {
            return this.config.alias + '::' + name
        }

        request(handler, data) {
            const body = data instanceof FormData ? data : new FormData()
            if (!(data instanceof FormData)) {
                Object.entries(data || {}).forEach(([key, value]) => body.append(key, value))
            }

            const headers = {
                'X-IGNITER-REQUEST-HANDLER': handler,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }

            const token = document.querySelector('meta[name="csrf-token"]')
            if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content')

            return fetch(window.location.href, {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: body,
            }).then((response) => response.json().catch(() => ({})).then((json) => {
                if (!response.ok) {
                    throw new Error(this.errorMessage(json))
                }

                return json
            }))
        }

        errorMessage(json) {
            if (json.X_IGNITER_ERROR_FIELDS) {
                return Object.values(json.X_IGNITER_ERROR_FIELDS).flat().join('\n')
            }

            return json.X_IGNITER_ERROR_MESSAGE || json.message || this.config.messages.alertRequestFailed
        }

        alert(message) {
            if (window.jQuery && window.jQuery.ti && window.jQuery.ti.flashMessage) {
                window.jQuery.ti.flashMessage({class: 'danger', text: message})
            } else {
                window.alert(message)
            }
        }

        //
        // Events
        //

        bindEvents() {
            this.element.addEventListener('click', (event) => {
                const addSection = event.target.closest('[data-page-builder-add-section]')
                if (addSection) {
                    this.addSection(addSection.dataset.pageBuilderAddSection)
                    return
                }

                const sectionElement = event.target.closest('[data-section]')
                if (!sectionElement) return

                const sectionId = sectionElement.dataset.sectionId
                const addBlock = event.target.closest('[data-add-block]')
                if (addBlock) {
                    const columnElement = addBlock.closest('[data-column]')
                    this.addBlock(sectionId, Number(columnElement.dataset.columnIndex), addBlock.dataset.addBlock)
                    return
                }

                const blockAction = event.target.closest('[data-block-action]')
                if (blockAction) {
                    if (blockAction.dataset.blockAction === 'remove') this.removeBlock(blockAction.closest('[data-block]').dataset.blockId)
                    return
                }

                const editableBlock = event.target.closest('[data-block].is-editable')
                if (editableBlock && !event.target.closest('[data-block-handle]')) {
                    this.editBlock(editableBlock.dataset.blockId)
                    return
                }

                const sectionAction = event.target.closest('[data-section-action]')
                if (sectionAction) {
                    if (sectionAction.dataset.sectionAction === 'settings') this.editSection(sectionId)
                    if (sectionAction.dataset.sectionAction === 'duplicate') this.duplicateSection(sectionId)
                    if (sectionAction.dataset.sectionAction === 'remove') this.removeSection(sectionId)
                }
            })

            this.element.addEventListener('input', (event) => {
                if (event.target.matches('[data-column-block-search]')) this.filterBlockMenu(event.target)
            })

            // Open the block picker with an empty search, ready to type in.
            this.element.addEventListener('shown.bs.dropdown', (event) => {
                const search = event.target.closest('.dropdown')?.querySelector('[data-column-block-search]')
                if (!search) return

                search.value = ''
                this.filterBlockMenu(search)
                search.focus()
            })

            this.element.addEventListener('keydown', (event) => {
                // Enter in the search adds the first block that can be added.
                if (event.key === 'Enter' && event.target.matches('[data-column-block-search]')) {
                    event.preventDefault()
                    const first = event.target.closest('.dropdown-menu')
                        .querySelector('[data-column-block-menu] > li:not([hidden]) [data-add-block]:not(:disabled)')
                    first?.click()
                    return
                }

                if (event.key !== 'Enter' && event.key !== ' ') return
                if (!event.target.matches('[data-block].is-editable')) return

                event.preventDefault()
                this.editBlock(event.target.dataset.blockId)
            })

            this.element.addEventListener('change', (event) => {
                const layoutSelect = event.target.closest('[data-section-layout]')
                if (layoutSelect) {
                    this.changeLayout(layoutSelect.closest('[data-section]').dataset.sectionId, layoutSelect.value, layoutSelect)
                }
            })
        }
    }

    const init = () => {
        document.querySelectorAll('[data-control="page-builder"]').forEach((element) => {
            if (!element.pageBuilder) element.pageBuilder = new PageBuilder(element)
        })
    }

    window.PageBuilder = PageBuilder

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init)
    } else {
        init()
    }

    // Re-initialise after admin AJAX partial updates
    if (window.jQuery) window.jQuery(document).on('render', init)
})()
