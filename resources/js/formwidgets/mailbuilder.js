/*
 * Mail builder form widget
 *
 * Edits a mail template, layout or partial as a list of blocks. The blocks are
 * kept as state and written to a hidden JSON input; the server compiles them
 * on save. The preview is rendered by the server into a sandboxed iframe,
 * where blocks are selected, moved, duplicated and removed.
 *
 * Vanilla JS.
 */
(function () {
    'use strict'

    const clone = (value) => JSON.parse(JSON.stringify(value))

    const uid = () => 'b' + Math.random().toString(36).slice(2, 12)

    const el = (tag, attributes, ...children) => {
        const element = document.createElement(tag)
        Object.entries(attributes || {}).forEach(([key, value]) => {
            if (value === null || value === undefined || value === false) return
            if (key === 'className') element.className = value
            else if (key === 'text') element.textContent = value
            else if (key === 'dataset') Object.assign(element.dataset, value)
            else if (key.startsWith('on') && typeof value === 'function') element.addEventListener(key.slice(2), value)
            else element.setAttribute(key, value === true ? '' : value)
        })
        children.flat().forEach((child) => {
            if (child === null || child === undefined || child === false) return
            element.append(child instanceof Node ? child : document.createTextNode(String(child)))
        })
        return element
    }

    const icon = (className) => el('i', {className: className, 'aria-hidden': 'true'})

    /*
     * Undo history of builder snapshots. Pushes with the same group within the
     * grouping delay replace the last entry, so typing a word is one step.
     */
    class History {
        constructor(initial, limit = 100, groupDelay = 500) {
            this.limit = limit
            this.groupDelay = groupDelay
            this.entries = [JSON.stringify(initial)]
            this.index = 0
            this.savedIndex = 0
            this.lastGroup = null
            this.lastPushAt = 0
        }

        push(state, group = null) {
            const serialized = JSON.stringify(state)
            if (serialized === this.entries[this.index]) return false

            const now = Date.now()
            const merge = group && group === this.lastGroup && (now - this.lastPushAt) < this.groupDelay && this.index > 0

            this.entries = this.entries.slice(0, this.index + 1)
            if (merge) {
                this.entries[this.index] = serialized
            } else {
                this.entries.push(serialized)
                this.index++
            }

            if (this.entries.length > this.limit) {
                const overflow = this.entries.length - this.limit
                this.entries.splice(0, overflow)
                this.index -= overflow
                this.savedIndex -= overflow
            }

            this.lastGroup = group
            this.lastPushAt = now
            return true
        }

        undo() {
            if (!this.canUndo()) return null
            this.index--
            this.lastGroup = null
            return JSON.parse(this.entries[this.index])
        }

        redo() {
            if (!this.canRedo()) return null
            this.index++
            this.lastGroup = null
            return JSON.parse(this.entries[this.index])
        }

        canUndo() {
            return this.index > 0
        }

        canRedo() {
            return this.index < this.entries.length - 1
        }

        isDirty() {
            return this.entries[this.index] !== this.entries[this.savedIndex]
        }
    }

    class MailBuilder {
        constructor(element) {
            this.element = element
            this.input = element.querySelector('[data-mail-builder-input]')
            this.config = JSON.parse(element.querySelector('[data-mail-builder-config]').textContent)
            this.form = element.closest('form')
            this.lang = this.config.lang || {}
            this.mode = this.config.mode
            this.previewMode = !!this.config.previewMode

            this.blockDefinitions = {}
            this.config.blocks.forEach((block) => this.blockDefinitions[block.code] = block)

            this.dom = {
                notices: element.querySelector('[data-mail-builder-notices]'),
                palette: element.querySelector('[data-mail-builder-palette]'),
                frame: element.querySelector('[data-mail-builder-frame]'),
                frameWrap: element.querySelector('[data-mail-builder-frame-wrap]'),
                canvas: element.querySelector('[data-mail-builder-canvas]'),
                subject: element.querySelector('[data-mail-builder-subject]'),
                subjectPreview: element.querySelector('[data-mail-builder-subject-preview]'),
                variables: element.querySelector('[data-mail-builder-variables]'),
                themeModal: element.querySelector('[data-mail-builder-theme-modal]'),
                theme: element.querySelector('[data-mail-builder-theme]'),
                text: element.querySelector('[data-mail-builder-text]'),
                error: element.querySelector('[data-mail-builder-error]'),
                inspector: element.querySelector('[data-mail-builder-inspector]'),
                dirty: element.querySelector('[data-mail-builder-dirty]'),
                testEmail: element.querySelector('[data-mail-builder-test-email]'),
            }

            // The subject is saved by a hidden form field and edited in the subject bar
            this.subjectField = this.form && this.dom.subject
                ? this.form.querySelector('input[type="hidden"][name$="[subject]"]')
                : null

            this.state = {
                blocks: this.parseValue(this.input.value),
                theme: this.themeValues(),
                subject: this.subjectField ? this.subjectField.value : (this.config.subject || ''),
            }
            this.selectedId = null
            this.sideTab = 'blocks'
            this.view = 'desktop'
            this.errors = {}
            this.summaries = {}
            this.lastField = null
            this.saving = false
            this.previewTimer = null
            this.previewRequest = 0
            this.history = new History(this.state)

            this.dom.overlay = this.createOverlay()
            this.scrollToSelected = false
            this.suppressClick = false

            if (this.dom.testEmail) this.dom.testEmail.value = this.config.testEmail || ''

            this.bindEvents()
            this.renderNotices()
            this.renderPalette()
            this.bindSubject()
            this.render()
            this.schedulePreview(0)
        }

        parseValue(value) {
            try {
                const blocks = JSON.parse(value || '[]')
                return Array.isArray(blocks) ? blocks : []
            } catch (e) {
                return []
            }
        }

        themeValues() {
            const theme = {}
            ;(this.config.theme || []).forEach((field) => theme[field.name] = field.value)
            return theme
        }

        t(key) {
            return this.lang[key] || key
        }

        //
        // State
        //

        commit(group = null) {
            this.writeValue()
            const changed = this.history.push(this.state, group) !== false
            this.updateToolbar()
            this.renderCanvasState()
            if (changed) this.schedulePreview()
        }

        restore(state) {
            if (!state) return
            this.state = state
            this.writeValue()
            if (this.dom.subject) this.dom.subject.value = this.state.subject || ''
            if (!this.findBlock(this.selectedId)) this.selectedId = null
            this.render()
            this.schedulePreview(0)
        }

        writeValue() {
            this.input.value = JSON.stringify(this.state.blocks)
            if (this.subjectField) this.subjectField.value = this.state.subject || ''
        }

        bindSubject() {
            if (!this.dom.subject) return
            this.dom.subject.value = this.state.subject || ''
            this.trackField(this.dom.subject, null)
            this.dom.subject.addEventListener('input', () => {
                this.state.subject = this.dom.subject.value
                this.commit('subject')
            })
        }

        /*
         * Remembers the text field and caret a variable is inserted into, so the
         * Variables panel can insert into it after the field loses focus.
         */
        trackField(control, blockId) {
            control.dataset.mailBuilderVariables = 'true'
            const remember = () => this.lastField = {
                blockId: blockId,
                controlId: control.id,
                start: control.selectionStart,
                end: control.selectionEnd,
            }
            ;['focus', 'keyup', 'mouseup', 'select', 'blur'].forEach((name) => control.addEventListener(name, remember))
        }

        undo() {
            this.restore(this.history.undo())
        }

        redo() {
            this.restore(this.history.redo())
        }

        findBlock(id) {
            return this.state.blocks.find((block) => block.id === id) || null
        }

        indexOf(id) {
            return this.state.blocks.findIndex((block) => block.id === id)
        }

        definition(block) {
            return block ? (this.blockDefinitions[block.code] || null) : null
        }

        isRequired(block) {
            return this.mode === 'layout' && block.code === 'content_slot'
        }

        /*
         * Adds a block at the index, or after the selected block.
         */
        addBlock(code, index = null) {
            const definition = this.blockDefinitions[code]
            if (!definition) return

            if (definition.unique && this.state.blocks.some((block) => block.code === code)) {
                this.selectBlock(this.state.blocks.find((block) => block.code === code).id)
                return
            }

            const block = {id: uid(), code: code, settings: clone(definition.defaults || {})}
            if (index === null) {
                const selected = this.indexOf(this.selectedId)
                index = selected < 0 ? this.state.blocks.length : selected + 1
            }
            this.state.blocks.splice(index, 0, block)
            this.selectedId = block.id
            this.scrollToSelected = true
            this.commit()
            this.renderInspector()
        }

        removeBlock(id) {
            const block = this.findBlock(id)
            if (!block || this.isRequired(block)) return
            this.state.blocks.splice(this.indexOf(id), 1)
            if (this.selectedId === id) this.selectedId = null
            this.commit()
            this.renderInspector()
        }

        duplicateBlock(id) {
            const block = this.findBlock(id)
            const definition = this.definition(block)
            if (!block || !definition || definition.unique) return
            const copy = {id: uid(), code: block.code, settings: clone(block.settings)}
            this.state.blocks.splice(this.indexOf(id) + 1, 0, copy)
            this.selectedId = copy.id
            this.scrollToSelected = true
            this.commit()
            this.renderInspector()
        }

        moveBlock(id, offset) {
            const index = this.indexOf(id)
            const target = index + offset
            if (index < 0 || target < 0 || target >= this.state.blocks.length) return
            const [block] = this.state.blocks.splice(index, 1)
            this.state.blocks.splice(target, 0, block)
            this.scrollToSelected = true
            this.commit()
        }

        /*
         * Moves a block, or adds one from the palette, to a position in the list.
         */
        dropAt(source, index) {
            if (source.code) {
                this.addBlock(source.code, index)
                return
            }

            const from = this.indexOf(source.id)
            const to = index > from ? index - 1 : index
            if (from < 0 || to === from) return
            const [block] = this.state.blocks.splice(from, 1)
            this.state.blocks.splice(to, 0, block)
            this.selectedId = block.id
            this.commit()
            this.renderInspector()
        }

        selectBlock(id) {
            if (id === this.selectedId && id !== null) return
            this.selectedId = id
            this.renderCanvasState()
            this.renderInspector()
        }

        updateSetting(id, name, value) {
            const block = this.findBlock(id)
            if (!block) return
            block.settings = Object.assign({}, block.settings, {[name]: value})
            this.commit('setting:' + id + ':' + name)
        }

        //
        // Rendering
        //

        render() {
            this.updateToolbar()
            this.renderCanvasState()
            this.renderInspector()
        }

        updateToolbar() {
            const undo = this.element.querySelector('[data-mail-builder-action="undo"]')
            const redo = this.element.querySelector('[data-mail-builder-action="redo"]')
            undo.disabled = !this.history.canUndo()
            redo.disabled = !this.history.canRedo()
            undo.title = this.t('undo') + ' (' + this.history.index + ')'
            redo.title = this.t('redo') + ' (' + (this.history.entries.length - 1 - this.history.index) + ')'
            this.dom.dirty.hidden = !this.history.isDirty()

            this.element.querySelectorAll('[data-mail-builder-view]').forEach((button) => {
                button.classList.toggle('active', button.dataset.mailBuilderView === this.view)
            })
            this.element.querySelectorAll('[data-mail-builder-side-tab]').forEach((button) => {
                button.classList.toggle('active', button.dataset.mailBuilderSideTab === this.sideTab)
            })
            this.element.querySelectorAll('[data-mail-builder-side-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.mailBuilderSidePanel !== this.sideTab
            })
        }

        renderNotices() {
            const notices = this.config.notices || []
            const canReset = !!(this.form && this.form.dataset.mailBuilderCanReset)

            this.dom.notices.replaceChildren(...notices.map((notice, index) => el('div', {
                className: 'alert alert-warning py-2 mb-2 d-flex align-items-center gap-2',
                role: 'status',
            },
                el('span', {className: 'flex-grow-1', text: notice}),
                // Deleting the record brings back the registered content, offered once
                canReset && index === notices.length - 1 ? el('button', {
                    type: 'button',
                    className: 'btn btn-sm btn-outline-danger',
                    'data-request': 'onReset',
                    'data-request-confirm': this.t('confirm_reset'),
                    'data-progress-indicator': this.t('saving'),
                }, this.t('reset_template')) : null,
            )))
        }

        renderPalette() {
            const blocks = this.config.blocks.filter((block) => !block.hidden)
            this.dom.palette.replaceChildren(...blocks.map((block) => el('div', {className: 'col'}, el('button', {
                type: 'button',
                className: 'mail-builder-draggable btn btn-light btn-sm border w-100 h-100 d-flex flex-column align-items-center gap-1 small',
                title: block.description || block.name,
                disabled: this.previewMode,
                // Click adds after the selected block, dragging drops it into the email
                onpointerdown: (e) => this.startDrag(e, {code: block.code}),
                onclick: () => { if (!this.suppressClick) this.addBlock(block.code) },
            }, icon(block.icon), el('span', {text: block.name})))))
        }

        //
        // Canvas: the selected block's toolbar and dragging blocks
        //

        createOverlay() {
            const label = el('div', {className: 'position-absolute z-2 d-flex align-items-center gap-1 px-2 small text-white text-nowrap bg-primary rounded-top pe-none', hidden: true})
            const toolbar = el('div', {className: 'position-absolute z-2 d-flex bg-primary rounded-top', role: 'toolbar', hidden: true})
            const indicator = el('div', {className: 'mail-builder-drop-indicator position-absolute z-3 bg-primary rounded pe-none', hidden: true})
            this.dom.frameWrap.append(label, toolbar, indicator)
            return {label, toolbar, indicator}
        }

        renderCanvasState() {
            this.highlightFrameBlock()
            this.renderBlockToolbar()
        }

        frameNode(id) {
            const doc = this.dom.frame.contentDocument
            return doc && id ? doc.querySelector('[data-mb-block="' + CSS.escape(id) + '"]') : null
        }

        /*
         * The visible blocks in the frame, in order, with their list index.
         */
        frameNodes() {
            const doc = this.dom.frame.contentDocument
            if (!doc) return []
            return Array.from(doc.querySelectorAll('[data-mb-block]'))
                .map((node) => ({node, index: this.indexOf(node.getAttribute('data-mb-block'))}))
                .filter((item) => item.index >= 0 && item.node.getBoundingClientRect().height > 0)
        }

        /*
         * The position of an element in the frame, relative to the frame wrap.
         */
        nodeRect(node) {
            const frameRect = this.dom.frame.getBoundingClientRect()
            const wrapRect = this.dom.frameWrap.getBoundingClientRect()
            const rect = node.getBoundingClientRect()
            const top = frameRect.top - wrapRect.top + rect.top
            const left = frameRect.left - wrapRect.left + rect.left
            return {top, left, width: rect.width, bottom: top + rect.height}
        }

        renderBlockToolbar() {
            const {label, toolbar} = this.dom.overlay
            const block = this.findBlock(this.selectedId)
            const node = block && this.view !== 'text' ? this.frameNode(block.id) : null
            label.hidden = toolbar.hidden = !node
            if (!node) return

            const definition = this.definition(block)
            const index = this.indexOf(block.id)
            const action = (title, iconClass, onclick, attributes = {}) => el('button', Object.assign({
                type: 'button',
                className: 'btn btn-sm btn-primary border-0 py-0',
                title: title,
                'aria-label': title,
                onclick: (e) => {
                    e.preventDefault()
                    onclick()
                },
            }, attributes), icon(iconClass))

            label.replaceChildren(
                icon(definition ? definition.icon : 'fa fa-triangle-exclamation'),
                el('span', {text: definition ? definition.name : (this.config.blockNames[block.code] || block.code)}),
            )

            toolbar.replaceChildren(...(this.previewMode ? [] : [
                el('span', {
                    className: 'mail-builder-draggable btn btn-sm btn-primary border-0 py-0',
                    title: this.t('drag'),
                    onpointerdown: (e) => this.startDrag(e, {id: block.id}),
                }, icon('fa fa-grip-vertical')),
                action(this.t('move_up'), 'fa fa-arrow-up', () => this.moveBlock(block.id, -1), {disabled: index === 0}),
                action(this.t('move_down'), 'fa fa-arrow-down', () => this.moveBlock(block.id, 1), {disabled: index === this.state.blocks.length - 1}),
                definition && !definition.unique ? action(this.t('duplicate'), 'fa fa-clone', () => this.duplicateBlock(block.id)) : null,
                !this.isRequired(block) ? action(this.t('delete'), 'fa fa-trash', () => this.removeBlock(block.id)) : null,
            ].filter(Boolean)))
            toolbar.hidden = !toolbar.childElementCount

            // Above the block, or at the top of the canvas when there is no room above
            const rect = this.nodeRect(node)
            label.style.top = Math.max(0, rect.top - label.offsetHeight) + 'px'
            label.style.left = rect.left + 'px'
            toolbar.style.top = Math.max(0, rect.top - toolbar.offsetHeight) + 'px'
            toolbar.style.left = Math.max(0, rect.left + rect.width - toolbar.offsetWidth) + 'px'
        }

        /*
         * Drags a block, {id}, or a palette block, {code}, to a position in the email.
         * The pointer is captured so moves over the frame still reach the builder.
         */
        startDrag(event, source) {
            if (this.previewMode || event.button !== 0 || this.view === 'text') return

            const handle = event.currentTarget
            const startX = event.clientX
            const startY = event.clientY
            let dragging = false
            let target = null

            const move = (e) => {
                if (!dragging) {
                    if (Math.abs(e.clientX - startX) + Math.abs(e.clientY - startY) < 6) return
                    dragging = true
                    this.element.classList.add('is-dragging')
                }
                e.preventDefault()
                target = this.dropTarget(e.clientX, e.clientY)
                this.showDropIndicator(target)
            }

            const end = (e) => {
                handle.removeEventListener('pointermove', move)
                handle.removeEventListener('pointerup', end)
                handle.removeEventListener('pointercancel', end)
                this.element.classList.remove('is-dragging')
                this.dom.overlay.indicator.hidden = true
                if (!dragging) return

                // A drag is not a click on the palette item
                this.suppressClick = true
                setTimeout(() => this.suppressClick = false, 0)
                if (e.type === 'pointerup' && target) this.dropAt(source, target.index)
            }

            handle.setPointerCapture(event.pointerId)
            handle.addEventListener('pointermove', move)
            handle.addEventListener('pointerup', end)
            handle.addEventListener('pointercancel', end)
        }

        /*
         * Where a block dropped at the pointer goes: before the first block whose
         * middle is below the pointer, or at the end.
         */
        dropTarget(clientX, clientY) {
            const frameRect = this.dom.frame.getBoundingClientRect()
            if (clientX < frameRect.left || clientX > frameRect.right || clientY < frameRect.top || clientY > frameRect.bottom) {
                return null
            }

            const nodes = this.frameNodes()
            const y = clientY - frameRect.top
            for (const item of nodes) {
                const rect = item.node.getBoundingClientRect()
                if (y < rect.top + rect.height / 2) return {index: item.index, node: item.node, before: true}
            }

            const last = nodes[nodes.length - 1]
            return {index: this.state.blocks.length, node: last ? last.node : null, before: false}
        }

        showDropIndicator(target) {
            const indicator = this.dom.overlay.indicator
            indicator.hidden = !target || !target.node
            if (indicator.hidden) return

            const rect = this.nodeRect(target.node)
            indicator.style.top = (target.before ? rect.top : rect.bottom) + 'px'
            indicator.style.left = rect.left + 'px'
            indicator.style.width = rect.width + 'px'
        }

        renderInspector() {
            this.updateToolbar()
            this.dom.inspector.replaceChildren(this.renderSettings())
            if (this.isThemeOpen()) this.renderThemeFields()
        }

        renderSettings() {
            const block = this.findBlock(this.selectedId)
            const definition = this.definition(block)

            if (!block) return el('p', {className: 'text-muted', text: this.t('no_selection')})
            if (!definition) return el('p', {className: 'text-muted', text: this.t('unavailable_block') + ' (' + block.code + ')'})

            const container = el('div', {className: 'mail-builder-settings'},
                el('div', {className: 'fw-semibold mb-3'}, icon(definition.icon), ' ', definition.name),
            )

            if (definition.readOnly) {
                container.append(el('p', {className: 'text-muted', text: definition.description || this.t('read_only_block')}))
                return container
            }

            if (this.isRequired(block)) {
                container.append(el('p', {className: 'text-muted small', text: this.t('required_block')}))
            }

            const fields = (definition.fields || []).filter((field) => field.type !== 'hidden')
            if (!fields.length) {
                container.append(el('p', {className: 'text-muted', text: definition.description || this.t('no_settings')}))
                return container
            }

            const row = el('div', {className: 'row g-2'})
            fields.forEach((field) => row.append(this.renderField(block, field)))
            container.append(row)
            return container
        }

        renderField(block, field) {
            const id = this.element.id + '-' + block.id + '-' + field.name
            const value = block.settings[field.name]
            const error = (this.errors['blocks.' + block.id + '.' + field.name] || [])[0]
            const column = el('div', {className: field.span === 'left' || field.span === 'right' ? 'col-6' : 'col-12'})
            const onValue = (newValue) => this.updateSetting(block.id, field.name, newValue)

            // Field types registered by extensions with MailBuilder.registerField()
            const custom = MailBuilder.fieldTypes[field.type]
            if (custom) {
                column.append(el('label', {className: 'form-label', for: id, text: field.label}),
                    custom.render({id, field, value, onChange: onValue, builder: this}))
                if (error) column.append(el('div', {className: 'invalid-feedback d-block', text: error}))
                return column
            }

            let control
            switch (field.type) {
                case 'textarea':
                case 'markdown':
                    control = el('textarea', {id, className: 'form-control', rows: field.type === 'markdown' ? 6 : 3, placeholder: field.placeholder})
                    control.value = value ?? ''
                    control.addEventListener('input', () => onValue(control.value))
                    break
                case 'select':
                    control = el('select', {id, className: 'form-select'},
                        Object.entries(field.options || {}).map(([optionValue, label]) => el('option', {value: optionValue, text: label})))
                    control.value = value ?? ''
                    control.addEventListener('change', () => onValue(control.value))
                    break
                case 'number':
                    control = el('input', {id, type: 'number', className: 'form-control', min: field.min, max: field.max, step: 1})
                    control.value = value ?? ''
                    control.addEventListener('input', () => onValue(control.value === '' ? null : parseInt(control.value, 10)))
                    break
                case 'switch':
                    control = el('div', {className: 'form-check form-switch'},
                        el('input', {id, type: 'checkbox', className: 'form-check-input', checked: !!value, onchange: (e) => onValue(e.target.checked)}),
                        el('label', {className: 'form-check-label', for: id, text: field.label}),
                    )
                    column.append(control)
                    if (field.comment) column.append(el('div', {className: 'form-text', text: field.comment}))
                    return column
                case 'color':
                    control = el('input', {id, type: 'color', className: 'form-control form-control-color'})
                    control.value = value || '#000000'
                    control.addEventListener('change', () => onValue(control.value))
                    break
                case 'table':
                    return this.renderTableField(block, field, column, id)
                case 'variable':
                    control = el('select', {id, className: 'form-select'},
                        el('option', {value: '', text: this.t('always_visible')}),
                        this.scalarVariables().map((variable) => el('option', {value: variable.name, text: variable.label + ' ($' + variable.name + ')'})))
                    control.value = value ?? ''
                    control.addEventListener('change', () => onValue(control.value || null))
                    break
                case 'param':
                    control = el('select', {id, className: 'form-select'},
                        el('option', {value: '', text: this.t('select_param')}),
                        (this.variableGroups().find((group) => group.code === 'params') || {variables: []}).variables
                            .map((param) => el('option', {value: param.name, text: param.label + ' ($' + param.name + ')'})))
                    control.value = value ?? ''
                    control.addEventListener('change', () => onValue(control.value))
                    break
                case 'image':
                    return this.renderImageField(block, field, column, id, value, error, onValue)
                default:
                    control = el('input', {id, type: 'text', className: 'form-control', placeholder: field.placeholder})
                    control.value = value ?? ''
                    control.addEventListener('input', () => onValue(control.value))
            }

            if (field.variables) this.trackField(control, block.id)
            if (error) control.classList.add('is-invalid')

            column.append(el('label', {className: 'form-label', for: id, text: field.label}), control)
            if (error) column.append(el('div', {className: 'invalid-feedback d-block', text: error}))
            if (field.comment) column.append(el('div', {className: 'form-text', text: field.comment}))
            return column
        }

        /*
         * A table of text cells: a heading and an alignment per column, and rows.
         */
        /*
         * An image URL, typed or chosen from the media manager.
         */
        renderImageField(block, field, column, id, value, error, onValue) {
            const input = el('input', {id, type: 'text', className: 'form-control', placeholder: field.placeholder || 'https://'})
            input.value = value ?? ''
            if (error) input.classList.add('is-invalid')
            if (field.variables) this.trackField(input, block.id)

            const thumbnail = el('img', {className: 'd-block img-fluid rounded mt-2', style: 'max-height: 8rem', alt: ''})
            const showThumbnail = () => {
                const url = input.value.trim()
                thumbnail.hidden = !/^(https?:\/\/|\/)/i.test(url)
                if (!thumbnail.hidden) thumbnail.src = url
            }
            input.addEventListener('input', () => {
                onValue(input.value)
                showThumbnail()
            })
            showThumbnail()

            const mediaManager = window.$ && window.$.ti && window.$.ti.mediaManager
            const choose = mediaManager && mediaManager.modal ? el('button', {
                type: 'button',
                className: 'btn btn-light border',
                title: this.t('choose_media'),
                'aria-label': this.t('choose_media'),
                onclick: () => this.chooseMedia((url) => {
                    input.value = url
                    input.dispatchEvent(new Event('input', {bubbles: true}))
                }),
            }, icon('fa fa-images')) : null

            column.append(
                el('label', {className: 'form-label', for: id, text: field.label}),
                el('div', {className: 'input-group'}, input, choose),
            )
            if (error) column.append(el('div', {className: 'invalid-feedback d-block', text: error}))
            if (field.comment) column.append(el('div', {className: 'form-text', text: field.comment}))
            column.append(thumbnail)
            return column
        }

        chooseMedia(onSelect) {
            const mediaManager = window.$ && window.$.ti && window.$.ti.mediaManager
            if (!mediaManager || !mediaManager.modal) {
                this.notify(this.t('media_unavailable'), 'warning')
                return
            }

            const builder = this
            new mediaManager.modal({
                alias: 'mediamanager',
                selectMode: 'single',
                chooseButton: true,
                chooseButtonText: this.t('insert_image'),
                onInsert: function (items) {
                    const item = items.length ? items[0].querySelector('[data-media-item]') : null
                    const url = item ? item.getAttribute('data-media-item-url') : null
                    if (!url) {
                        builder.notify(builder.t('select_image'), 'warning')
                        return
                    }

                    onSelect(url)
                    this.hide()
                },
            })
        }

        renderTableField(block, field, column, id) {
            const maxColumns = 8
            const nextAlign = {left: 'center', center: 'right', right: 'left'}
            const read = () => {
                const current = (this.findBlock(block.id) || block).settings[field.name] || {}
                return {columns: clone(current.columns || []), rows: clone(current.rows || [])}
            }
            // Typing keeps the inspector, adding or removing rows and columns redraws it
            const change = (update, redraw = false) => {
                const table = read()
                update(table)
                this.updateSetting(block.id, field.name, table)
                if (redraw) this.renderInspector()
            }
            // Stable ids let a variable be inserted after the table is redrawn
            const input = (value, label, key, onInput) => {
                const control = el('input', {id: id + '-' + key, type: 'text', className: 'form-control form-control-sm', 'aria-label': label})
                control.value = value ?? ''
                this.trackField(control, block.id)
                control.addEventListener('input', () => onInput(control.value))
                return control
            }
            const button = (title, iconClass, onclick, disabled = false) => el('button', {
                type: 'button', className: 'btn btn-sm btn-light', title, 'aria-label': title, disabled, onclick,
            }, icon(iconClass))

            const table = read()
            const head = el('tr', {},
                table.columns.map((col, c) => el('th', {},
                    input(col.label, this.t('column_label') + ' ' + (c + 1), 'head-' + c, (value) => change((t) => t.columns[c].label = value)),
                    el('div', {className: 'btn-group btn-group-sm mt-1'},
                        button(this.t('align') + ': ' + col.align, 'fa fa-align-' + col.align, () => change((t) => {
                            t.columns[c].align = nextAlign[t.columns[c].align] || 'left'
                        }, true)),
                        button(this.t('remove_column'), 'fa fa-xmark', () => change((t) => {
                            t.columns.splice(c, 1)
                            t.rows.forEach((row) => row.splice(c, 1))
                        }, true), table.columns.length < 2),
                    ),
                )),
                el('th', {}, button(this.t('add_column'), 'fa fa-plus', () => change((t) => {
                    t.columns.push({label: '', align: 'left'})
                    t.rows.forEach((row) => row.push(''))
                }, true), table.columns.length >= maxColumns)),
            )

            const body = table.rows.map((row, r) => el('tr', {},
                table.columns.map((col, c) => el('td', {},
                    input(row[c], this.t('cell') + ' ' + (r + 1) + ', ' + (c + 1), 'cell-' + r + '-' + c, (value) => change((t) => t.rows[r][c] = value)))),
                el('td', {}, button(this.t('remove_row'), 'fa fa-trash', () => change((t) => t.rows.splice(r, 1), true))),
            ))

            column.append(
                el('div', {className: 'form-label', id: id, text: field.label}),
                el('div', {className: 'mail-builder-table-field table-responsive'},
                    el('table', {className: 'table table-sm table-borderless mb-1', 'aria-labelledby': id},
                        el('thead', {}, head), el('tbody', {}, body))),
                el('button', {
                    type: 'button',
                    className: 'btn btn-sm btn-light',
                    onclick: () => change((t) => t.rows.push(t.columns.map(() => '')), true),
                }, icon('fa fa-plus'), ' ', this.t('add_row')),
            )
            return column
        }

        renderVariablesPanel() {
            if (this.dom.variables) this.dom.variables.replaceChildren(this.renderVariables())
        }

        renderVariables() {
            const container = el('div')
            const search = el('input', {type: 'search', className: 'form-control form-control-sm mb-2', placeholder: this.t('search_variables'), 'aria-label': this.t('search_variables')})
            const groups = el('div')
            container.append(search, el('p', {className: 'form-text mt-0', text: this.t('insert_variable_hint')}), groups)

            const render = () => {
                const query = search.value.trim().toLowerCase()
                const items = this.variableGroups().map((group) => {
                    const variables = group.variables.filter((variable) => !query
                        || variable.label.toLowerCase().includes(query)
                        || (variable.token || variable.name).toLowerCase().includes(query))
                    if (!variables.length) return null

                    // Groups start collapsed, a search opens the groups with matches
                    return el('details', {className: 'mb-2', open: !!query},
                        el('summary', {className: 'small fw-semibold py-1 user-select-none', text: group.label}),
                        el('div', {className: 'list-group mt-1'}, variables.map((variable) => {
                            if (variable.type === 'array') {
                                return el('div', {className: 'list-group-item px-2 py-1 small text-body-secondary', text: variable.label + ' — ' + this.t('loop_hint')})
                            }
                            if (variable.type === 'item') return null

                            return el('button', {
                                type: 'button',
                                className: 'list-group-item list-group-item-action d-flex align-items-center gap-2 px-2 py-1',
                                draggable: 'true',
                                title: this.t('insert') + ' ' + variable.token,
                                // Keep the focus in the text field the variable goes into
                                onmousedown: (e) => e.preventDefault(),
                                onclick: () => this.insertVariable(variable.token),
                                ondragstart: (e) => e.dataTransfer.setData('text/plain', variable.token),
                            },
                                el('span', {className: 'flex-grow-1 overflow-hidden'},
                                    el('span', {className: 'd-block small text-truncate', text: variable.label}),
                                    el('code', {className: 'd-block small text-truncate', text: variable.token}),
                                ),
                                icon('fa fa-plus small text-body-secondary'),
                            )
                        })),
                    )
                }).filter(Boolean)

                groups.replaceChildren(...(items.length ? items : [
                    el('p', {className: 'small text-body-secondary', text: this.t('no_variables')}),
                ]))
            }

            search.addEventListener('input', render)
            render()
            return container
        }

        scalarVariables() {
            const seen = {}
            const variables = []
            this.variableGroups().forEach((group) => group.variables.forEach((variable) => {
                if (variable.type !== 'scalar' || seen[variable.name]) return
                seen[variable.name] = true
                variables.push(variable)
            }))
            return variables
        }

        variableGroups() {
            if (this.mode !== 'partial') return this.config.variables || []

            // Parameters come from the partial form, which may have changed since load
            const params = this.partialParams()
            if (params === null) return this.config.variables || []

            return (this.config.variables || []).map((group) => group.code !== 'params' ? group : Object.assign({}, group, {
                variables: params.map((param) => ({type: 'scalar', name: param.name, token: '{{ $' + param.name + ' }}', label: param.label})),
            }))
        }

        /*
         * Parameters from the partial form, or null when the page has no
         * parameter fields, such as the builder page, and the saved ones apply.
         */
        partialParams() {
            if (this.mode !== 'partial' || !this.form) return []

            const inputs = this.form.querySelectorAll('[name*="[params]"]')
            if (!inputs.length) return null

            const params = {}
            inputs.forEach((input) => {
                const match = input.name.match(/\[params\]\[([^\]]+)\]\[(name|label|sample)\]$/)
                if (!match) return
                params[match[1]] = params[match[1]] || {}
                params[match[1]][match[2]] = input.value
            })

            return Object.values(params)
                .filter((param) => /^[A-Za-z_]\w*$/.test(param.name || '') && param.name !== 'slot' && param.name !== 'body')
                .map((param) => ({name: param.name, label: param.label || param.name, sample: param.sample || ''}))
        }

        insertVariable(token) {
            const target = this.lastField
            // The field may have been redrawn since, find it again by its id
            const control = target && target.controlId ? document.getElementById(target.controlId) : null
            if (!control || (target.blockId && this.findBlock(target.blockId) === null)) {
                this.notify(this.t('select_text_field'), 'warning')
                return
            }

            const length = control.value.length
            const start = Math.min(target.start ?? length, length)
            const end = Math.min(Math.max(target.end ?? start, start), length)
            control.value = control.value.slice(0, start) + token + control.value.slice(end)
            control.dispatchEvent(new Event('input', {bubbles: true}))
            this.history.lastGroup = null

            control.focus()
            control.setSelectionRange(start + token.length, start + token.length)
            Object.assign(target, {start: start + token.length, end: start + token.length})
        }

        //
        // Theme: site-wide colours edited in a modal, previewed live
        //

        themeModal() {
            return window.bootstrap && this.dom.themeModal
                ? window.bootstrap.Modal.getOrCreateInstance(this.dom.themeModal)
                : null
        }

        isThemeOpen() {
            return !!this.dom.themeModal && this.dom.themeModal.classList.contains('show')
        }

        openTheme() {
            this.renderThemeFields()
            const modal = this.themeModal()
            if (modal) modal.show()
        }

        renderThemeFields() {
            if (!this.dom.theme) return

            const container = el('div', {},
                el('p', {className: 'form-text mt-0', text: this.t('theme_site_wide')}),
            )

            let currentGroup = null;
            (this.config.theme || []).forEach((field) => {
                if (field.group && field.group !== currentGroup) {
                    currentGroup = field.group
                    container.append(el('div', {className: 'small fw-semibold text-uppercase text-body-secondary mt-3 mb-2', text: field.group}))
                }

                const id = this.element.id + '-theme-' + field.name
                const input = el('input', {id, type: 'color', className: 'form-control form-control-color'})
                input.value = this.state.theme[field.name] || field.default
                // Applied once the colour is picked, not on every move of the picker
                input.addEventListener('change', () => {
                    if (input.value === this.state.theme[field.name]) return
                    this.state.theme = Object.assign({}, this.state.theme, {[field.name]: input.value})
                    this.commit('theme:' + field.name)
                })

                container.append(el('div', {className: 'd-flex align-items-center justify-content-between gap-2 mb-2 small'},
                    el('label', {for: id, text: field.label}), input))
            })

            this.dom.theme.replaceChildren(container)
        }

        resetTheme() {
            const theme = {}
            ;(this.config.theme || []).forEach((field) => theme[field.name] = field.default)
            this.state.theme = theme
            this.commit('theme:reset')
            this.renderThemeFields()
        }

        //
        // Preview
        //

        schedulePreview(delay = 300) {
            clearTimeout(this.previewTimer)
            this.previewTimer = setTimeout(() => this.refreshPreview(), delay)
        }

        draftData() {
            const data = new FormData()
            data.append('blocks', JSON.stringify(this.state.blocks))
            data.append('theme', JSON.stringify(this.state.theme))

            if (this.dom.subject) data.append('subject', this.state.subject || '')

            if (this.form) {
                ;[this.dom.subject ? null : 'subject', 'layout_id', 'layout_css'].filter(Boolean).forEach((name) => {
                    const input = this.form.querySelector('[name$="[' + name + ']"]')
                    if (input) data.append(name, input.value)
                })
            }

            const params = this.mode === 'partial' ? this.partialParams() : null
            if (params !== null) data.append('params', JSON.stringify(params))
            return data
        }

        refreshPreview() {
            const requestId = ++this.previewRequest

            this.request(this.config.handlers.preview, this.draftData()).then((result) => {
                if (requestId !== this.previewRequest) return

                this.errors = result.errors || {}
                this.summaries = result.summaries || this.summaries
                this.dom.error.hidden = !result.error
                this.dom.error.textContent = result.error ? this.t('preview_failed') + ' ' + result.error : ''

                if (!result.error) {
                    // The subject as sent, shown when variables change it
                    if (this.dom.subjectPreview) {
                        const rendered = result.subject || ''
                        this.dom.subjectPreview.hidden = !rendered || rendered === (this.state.subject || '')
                        this.dom.subjectPreview.textContent = this.t('subject_preview') + ' ' + rendered
                    }
                    this.dom.text.textContent = result.text || ''
                    this.writeFrame(result.html || '')
                }

                this.renderCanvasState()
                if (!this.dom.inspector.contains(document.activeElement)) this.renderInspector()
                else this.markFieldErrors()
            }).catch((error) => {
                if (requestId !== this.previewRequest) return
                this.dom.error.hidden = false
                this.dom.error.textContent = this.t('preview_failed') + ' ' + error.message
            })
        }

        markFieldErrors() {
            const block = this.findBlock(this.selectedId)
            if (!block) return
            this.dom.inspector.querySelectorAll('.form-control, .form-select').forEach((control) => {
                const name = control.id.slice((this.element.id + '-' + block.id + '-').length)
                control.classList.toggle('is-invalid', !!this.errors['blocks.' + block.id + '.' + name])
            })
        }

        writeFrame(html) {
            const frame = this.dom.frame
            const scroll = frame.contentWindow ? frame.contentWindow.scrollY : 0
            frame.onload = () => {
                const doc = frame.contentDocument
                if (!doc) return

                const style = doc.createElement('style')
                style.textContent = '[data-mb-block]{cursor:pointer}'
                    + '[data-mb-block]:hover{outline:1px dashed #ff4900;outline-offset:2px}'
                    + '[data-mb-block].mb-error{outline:2px solid #dc3545;outline-offset:2px}'
                    + '[data-mb-block].mb-selected{outline:2px solid #ff4900;outline-offset:2px}'
                    + '.mb-placeholder{margin:4px 0;padding:6px 8px;border:1px dashed #adb5bd;border-radius:4px;'
                    + 'color:#6c757d;font:12px/1.4 system-ui,sans-serif;text-align:center}'
                doc.head ? doc.head.append(style) : doc.documentElement.append(style)
                this.addPlaceholders(doc)

                doc.addEventListener('click', (event) => {
                    event.preventDefault()
                    // The second click of a double click would undo the first
                    if (event.detail > 1) return

                    // Clicking the selected block again, or empty space, deselects
                    const target = event.target.closest('[data-mb-block]')
                    const id = target ? target.getAttribute('data-mb-block') : null
                    this.selectBlock(id === this.selectedId ? null : id)
                })
                doc.addEventListener('keydown', (event) => this.onEscape(event))

                frame.contentWindow.scrollTo(0, scroll)
                this.resizeFrame()
                this.renderCanvasState()
            }
            frame.srcdoc = html
        }

        /*
         * Escape deselects the block, unless it closes something else first.
         */
        onEscape(event) {
            if (event.key !== 'Escape' || event.defaultPrevented || !this.selectedId) return
            if (this.isThemeOpen() || document.querySelector('.modal.show')) return
            const target = event.target
            if (target && target.matches && target.matches('input, textarea, select') && this.element.contains(target)) return
            this.selectBlock(null)
        }

        resizeFrame() {
            const doc = this.dom.frame.contentDocument
            if (doc && doc.documentElement) {
                this.dom.frame.style.height = Math.max(400, doc.documentElement.scrollHeight) + 'px'
            }
        }

        /*
         * Blocks without output, such as one hidden by its condition with the
         * sample data, get a placeholder so they can still be selected.
         */
        addPlaceholders(doc) {
            doc.querySelectorAll('[data-mb-block]').forEach((node) => {
                if (node.getBoundingClientRect().height > 2) return

                const definition = this.definition(this.findBlock(node.getAttribute('data-mb-block')))
                const placeholder = doc.createElement('div')
                placeholder.className = 'mb-placeholder'
                placeholder.textContent = (definition ? definition.name + ': ' : '') + this.t('empty_block')

                if (node.tagName === 'TBODY') {
                    const row = doc.createElement('tr')
                    const cell = doc.createElement('td')
                    cell.className = 'content-cell'
                    cell.append(placeholder)
                    row.append(cell)
                    node.append(row)
                } else {
                    node.append(placeholder)
                }
            })
        }

        highlightFrameBlock() {
            const doc = this.dom.frame.contentDocument
            if (!doc) return

            doc.querySelectorAll('[data-mb-block]').forEach((node) => {
                const id = node.getAttribute('data-mb-block')
                node.classList.toggle('mb-selected', id === this.selectedId)
                node.classList.toggle('mb-error', Object.keys(this.errors).some((key) => key.startsWith('blocks.' + id + '.')))
            })

            const node = this.frameNode(this.selectedId)
            if (node && this.scrollToSelected) {
                this.scrollToSelected = false
                node.scrollIntoView({block: 'nearest'})
            }
        }

        setView(view) {
            this.view = view
            this.dom.frameWrap.hidden = view === 'text'
            this.dom.text.hidden = view !== 'text'
            this.dom.frameWrap.classList.toggle('is-mobile', view === 'mobile')
            this.updateToolbar()
            this.renderBlockToolbar()
            if (view !== 'text') {
                // Wait for the width transition before measuring the blocks
                setTimeout(() => {
                    this.resizeFrame()
                    this.renderBlockToolbar()
                }, 250)
            }
        }

        //
        // Server actions
        //

        sendTest(button) {
            const email = (this.dom.testEmail.value || '').trim()
            if (!email) {
                this.dom.testEmail.classList.add('is-invalid')
                this.dom.testEmail.focus()
                return
            }

            this.dom.testEmail.classList.remove('is-invalid')
            const data = this.draftData()
            data.append('email', email)

            button.disabled = true
            this.request(this.config.handlers.sendTest, data)
                .then((result) => this.notify(result.message, 'success'))
                .catch((error) => this.notify(error.message, 'danger'))
                .finally(() => button.disabled = false)
        }

        saveTheme(button) {
            const data = new FormData()
            data.append('theme', JSON.stringify(this.state.theme))
            button.disabled = true
            this.request(this.config.handlers.saveTheme, data)
                .then((result) => {
                    ;(this.config.theme || []).forEach((field) => field.value = this.state.theme[field.name])
                    this.notify(result.message, 'success')
                    const modal = this.themeModal()
                    if (modal) modal.hide()
                })
                .catch((error) => this.notify(error.message, 'danger'))
                .finally(() => button.disabled = false)
        }

        request(handler, data) {
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
                body: data,
            }).then((response) => response.json().catch(() => ({})).then((json) => {
                if (!response.ok) throw new Error(this.errorMessage(json))
                return json
            }))
        }

        errorMessage(json) {
            if (json && json.errors) {
                const first = Object.values(json.errors)[0]
                if (first) return Array.isArray(first) ? first[0] : String(first)
            }
            return (json && (json.message || json.X_IGNITER_ERROR_MESSAGE)) || 'Request failed'
        }

        notify(message, type = 'info') {
            if (window.$ && window.$.ti && typeof window.$.ti.flashMessage === 'function') {
                window.$.ti.flashMessage({class: type, text: message, allowDismiss: true})
                return
            }

            const notice = el('div', {className: 'alert alert-' + type + ' py-2 mb-2', role: 'status', text: message})
            this.dom.notices.append(notice)
            setTimeout(() => notice.remove(), 4000)
        }

        //
        // Events
        //

        bindEvents() {
            this.element.addEventListener('click', (event) => {
                const action = event.target.closest('[data-mail-builder-action]')
                if (action) {
                    const name = action.dataset.mailBuilderAction
                    if (name === 'undo') this.undo()
                    if (name === 'redo') this.redo()
                    if (name === 'send-test') this.sendTest(action)
                    if (name === 'theme') this.openTheme()
                    if (name === 'save-theme') this.saveTheme(action)
                    if (name === 'reset-theme') this.resetTheme()
                    return
                }

                const view = event.target.closest('[data-mail-builder-view]')
                if (view) {
                    this.setView(view.dataset.mailBuilderView)
                    return
                }

                const sideTab = event.target.closest('[data-mail-builder-side-tab]')
                if (sideTab) {
                    this.sideTab = sideTab.dataset.mailBuilderSideTab
                    if (this.sideTab === 'variables') this.renderVariablesPanel()
                    this.updateToolbar()
                    return
                }

            })

            // The canvas background around the email deselects
            this.dom.canvas.addEventListener('click', (event) => {
                if (event.target === this.dom.canvas || event.target === this.dom.frameWrap) this.selectBlock(null)
            })
            document.addEventListener('keydown', (event) => this.onEscape(event))

            // Keyboard undo and redo, for the builder and its inspector fields
            document.addEventListener('keydown', (event) => {
                if (!(event.metaKey || event.ctrlKey) || event.altKey) return

                const target = event.target
                const inBuilder = this.element.contains(target)
                const editingElsewhere = !inBuilder && target.matches && target.matches('input, textarea, select, [contenteditable]')
                if (editingElsewhere) return

                const key = event.key.toLowerCase()
                if (key === 'z' && !event.shiftKey) {
                    event.preventDefault()
                    this.undo()
                } else if ((key === 'z' && event.shiftKey) || key === 'y') {
                    event.preventDefault()
                    this.redo()
                }
            })

            // Subject, layout and partial parameter changes refresh the preview
            if (this.form) {
                this.form.addEventListener('input', (event) => {
                    if (!this.element.contains(event.target) && /\[(subject|layout_css|params)\]/.test(event.target.name || '')) {
                        this.schedulePreview(500)
                    }
                })
                this.form.addEventListener('change', (event) => {
                    if (!this.element.contains(event.target) && /\[(layout_id|params)\]/.test(event.target.name || '')) {
                        this.schedulePreview(0)
                    }
                })
                this.form.addEventListener('click', (event) => {
                    if (event.target.closest('[data-request="onSave"], [data-request="onReset"]')) {
                        this.saving = true
                        setTimeout(() => this.saving = false, 5000)
                    }
                }, true)
            }

            window.addEventListener('beforeunload', (event) => {
                if (this.saving || !this.history.isDirty()) return
                event.preventDefault()
                event.returnValue = ''
            })

            window.addEventListener('resize', () => {
                this.resizeFrame()
                this.renderBlockToolbar()
            })
        }
    }

    MailBuilder.History = History

    /*
     * Custom field types for extension blocks:
     *   MailBuilder.registerField('rating', {
     *       render: ({id, field, value, onChange}) => element,
     *   })
     */
    MailBuilder.fieldTypes = {}
    MailBuilder.registerField = (type, definition) => {
        if (!definition || typeof definition.render !== 'function') {
            throw new Error('Mail builder field [' + type + '] needs a render function.')
        }
        MailBuilder.fieldTypes[type] = definition
    }

    const init = (root) => {
        (root || document).querySelectorAll('[data-control="mail-builder"]').forEach((element) => {
            if (!element.mailBuilder) element.mailBuilder = new MailBuilder(element)
        })
    }

    window.MailBuilder = MailBuilder
    window.MailBuilder.init = init

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init())
    } else {
        init()
    }
})()
