/*!
 * Datepicker for Bootstrap v1.9.0 (https://github.com/uxsolutions/bootstrap-datepicker)
 *
 * Licensed under the Apache License v2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

!function (a) {
    "function" == typeof define && define.amd ? define(["jquery"], a) : a("object" == typeof exports ? require("jquery") : jQuery)
}(function (a, b) {
    function c() {
        return new Date(Date.UTC.apply(Date, arguments))
    }

    function d() {
        var a = new Date;
        return c(a.getFullYear(), a.getMonth(), a.getDate())
    }

    function e(a, b) {
        return a.getUTCFullYear() === b.getUTCFullYear() && a.getUTCMonth() === b.getUTCMonth() && a.getUTCDate() === b.getUTCDate()
    }

    function f(c, d) {
        return function () {
            return d !== b && a.fn.datepicker.deprecated(d), this[c].apply(this, arguments)
        }
    }

    function g(a) {
        return a && !isNaN(a.getTime())
    }

    function h(b, c) {
        function d(a, b) {
            return b.toLowerCase()
        }

        var e, f = a(b).data(), g = {}, h = new RegExp("^"+c.toLowerCase()+"([A-Z])");
        c = new RegExp("^"+c.toLowerCase());
        for (var i in f) c.test(i) && (e = i.replace(h, d), g[e] = f[i]);
        return g
    }

    function i(b) {
        var c = {};
        if (q[b] || (b = b.split("-")[0], q[b])) {
            var d = q[b];
            return a.each(p, function (a, b) {
                b in d && (c[b] = d[b])
            }), c
        }
    }

    var j = function () {
        var b = {
            get: function (a) {
                return this.slice(a)[0]
            }, contains: function (a) {
                for (var b = a && a.valueOf(), c = 0, d = this.length; c < d; c++) if (0 <= this[c].valueOf()-b && this[c].valueOf()-b < 864e5) return c;
                return -1
            }, remove: function (a) {
                this.splice(a, 1)
            }, replace: function (b) {
                b && (a.isArray(b) || (b = [b]), this.clear(), this.push.apply(this, b))
            }, clear: function () {
                this.length = 0
            }, copy: function () {
                var a = new j;
                return a.replace(this), a
            }
        };
        return function () {
            var c = [];
            return c.push.apply(c, arguments), a.extend(c, b), c
        }
    }(), k = function (b, c) {
        a.data(b, "datepicker", this), this._events = [], this._secondaryEvents = [], this._process_options(c), this.dates = new j, this.viewDate = this.o.defaultViewDate, this.focusDate = null, this.element = a(b), this.isInput = this.element.is("input"), this.inputField = this.isInput ? this.element : this.element.find("input"), this.component = !!this.element.hasClass("date") && this.element.find(".add-on, .input-group-addon, .input-group-append, .input-group-prepend, .btn"), this.component && 0 === this.component.length && (this.component = !1), this.isInline = !this.component && this.element.is("div"), this.picker = a(r.template), this._check_template(this.o.templates.leftArrow) && this.picker.find(".prev").html(this.o.templates.leftArrow), this._check_template(this.o.templates.rightArrow) && this.picker.find(".next").html(this.o.templates.rightArrow), this._buildEvents(), this._attachEvents(), this.isInline ? this.picker.addClass("datepicker-inline").appendTo(this.element) : this.picker.addClass("datepicker-dropdown dropdown-menu"), this.o.rtl && this.picker.addClass("datepicker-rtl"), this.o.calendarWeeks && this.picker.find(".datepicker-days .datepicker-switch, thead .datepicker-title, tfoot .today, tfoot .clear").attr("colspan", function (a, b) {
            return Number(b)+1
        }), this._process_options({
            startDate: this._o.startDate,
            endDate: this._o.endDate,
            daysOfWeekDisabled: this.o.daysOfWeekDisabled,
            daysOfWeekHighlighted: this.o.daysOfWeekHighlighted,
            datesDisabled: this.o.datesDisabled
        }), this._allow_update = !1, this.setViewMode(this.o.startView), this._allow_update = !0, this.fillDow(), this.fillMonths(), this.update(), this.isInline && this.show()
    };
    k.prototype = {
        constructor: k,
        _resolveViewName: function (b) {
            return a.each(r.viewModes, function (c, d) {
                if (b === c || -1 !== a.inArray(b, d.names)) return b = c, !1
            }), b
        },
        _resolveDaysOfWeek: function (b) {
            return a.isArray(b) || (b = b.split(/[,\s]*/)), a.map(b, Number)
        },
        _check_template: function (c) {
            try {
                if (c === b || "" === c) return !1;
                if ((c.match(/[<>]/g) || []).length <= 0) return !0;
                return a(c).length > 0
            } catch (a) {
                return !1
            }
        },
        _process_options: function (b) {
            this._o = a.extend({}, this._o, b);
            var e = this.o = a.extend({}, this._o), f = e.language;
            q[f] || (f = f.split("-")[0], q[f] || (f = o.language)), e.language = f, e.startView = this._resolveViewName(e.startView), e.minViewMode = this._resolveViewName(e.minViewMode), e.maxViewMode = this._resolveViewName(e.maxViewMode), e.startView = Math.max(this.o.minViewMode, Math.min(this.o.maxViewMode, e.startView)), !0 !== e.multidate && (e.multidate = Number(e.multidate) || !1, !1 !== e.multidate && (e.multidate = Math.max(0, e.multidate))), e.multidateSeparator = String(e.multidateSeparator), e.weekStart %= 7, e.weekEnd = (e.weekStart+6) % 7;
            var g = r.parseFormat(e.format);
            e.startDate !== -1 / 0 && (e.startDate ? e.startDate instanceof Date ? e.startDate = this._local_to_utc(this._zero_time(e.startDate)) : e.startDate = r.parseDate(e.startDate, g, e.language, e.assumeNearbyYear) : e.startDate = -1 / 0), e.endDate !== 1 / 0 && (e.endDate ? e.endDate instanceof Date ? e.endDate = this._local_to_utc(this._zero_time(e.endDate)) : e.endDate = r.parseDate(e.endDate, g, e.language, e.assumeNearbyYear) : e.endDate = 1 / 0), e.daysOfWeekDisabled = this._resolveDaysOfWeek(e.daysOfWeekDisabled || []), e.daysOfWeekHighlighted = this._resolveDaysOfWeek(e.daysOfWeekHighlighted || []), e.datesDisabled = e.datesDisabled || [], a.isArray(e.datesDisabled) || (e.datesDisabled = e.datesDisabled.split(",")), e.datesDisabled = a.map(e.datesDisabled, function (a) {
                return r.parseDate(a, g, e.language, e.assumeNearbyYear)
            });
            var h = String(e.orientation).toLowerCase().split(/\s+/g), i = e.orientation.toLowerCase();
            if (h = a.grep(h, function (a) {
                return /^auto|left|right|top|bottom$/.test(a)
            }), e.orientation = {x: "auto", y: "auto"}, i && "auto" !== i) if (1 === h.length) switch (h[0]) {
                case"top":
                case"bottom":
                    e.orientation.y = h[0];
                    break;
                case"left":
                case"right":
                    e.orientation.x = h[0]
            } else i = a.grep(h, function (a) {
                return /^left|right$/.test(a)
            }), e.orientation.x = i[0] || "auto", i = a.grep(h, function (a) {
                return /^top|bottom$/.test(a)
            }), e.orientation.y = i[0] || "auto"; else ;
            if (e.defaultViewDate instanceof Date || "string" == typeof e.defaultViewDate) e.defaultViewDate = r.parseDate(e.defaultViewDate, g, e.language, e.assumeNearbyYear); else if (e.defaultViewDate) {
                var j = e.defaultViewDate.year || (new Date).getFullYear(), k = e.defaultViewDate.month || 0,
                    l = e.defaultViewDate.day || 1;
                e.defaultViewDate = c(j, k, l)
            } else e.defaultViewDate = d()
        },
        _applyEvents: function (a) {
            for (var c, d, e, f = 0; f < a.length; f++) c = a[f][0], 2 === a[f].length ? (d = b, e = a[f][1]) : 3 === a[f].length && (d = a[f][1], e = a[f][2]), c.on(e, d)
        },
        _unapplyEvents: function (a) {
            for (var c, d, e, f = 0; f < a.length; f++) c = a[f][0], 2 === a[f].length ? (e = b, d = a[f][1]) : 3 === a[f].length && (e = a[f][1], d = a[f][2]), c.off(d, e)
        },
        _buildEvents: function () {
            var b = {
                keyup: a.proxy(function (b) {
                    -1 === a.inArray(b.keyCode, [27, 37, 39, 38, 40, 32, 13, 9]) && this.update()
                }, this), keydown: a.proxy(this.keydown, this), paste: a.proxy(this.paste, this)
            };
            !0 === this.o.showOnFocus && (b.focus = a.proxy(this.show, this)), this.isInput ? this._events = [[this.element, b]] : this.component && this.inputField.length ? this._events = [[this.inputField, b], [this.component, {click: a.proxy(this.show, this)}]] : this._events = [[this.element, {
                click: a.proxy(this.show, this),
                keydown: a.proxy(this.keydown, this)
            }]], this._events.push([this.element, "*", {
                blur: a.proxy(function (a) {
                    this._focused_from = a.target
                }, this)
            }], [this.element, {
                blur: a.proxy(function (a) {
                    this._focused_from = a.target
                }, this)
            }]), this.o.immediateUpdates && this._events.push([this.element, {
                "changeYear changeMonth": a.proxy(function (a) {
                    this.update(a.date)
                }, this)
            }]), this._secondaryEvents = [[this.picker, {click: a.proxy(this.click, this)}], [this.picker, ".prev, .next", {click: a.proxy(this.navArrowsClick, this)}], [this.picker, ".day:not(.disabled)", {click: a.proxy(this.dayCellClick, this)}], [a(window), {resize: a.proxy(this.place, this)}], [a(document), {
                "mousedown touchstart": a.proxy(function (a) {
                    this.element.is(a.target) || this.element.find(a.target).length || this.picker.is(a.target) || this.picker.find(a.target).length || this.isInline || this.hide()
                }, this)
            }]]
        },
        _attachEvents: function () {
            this._detachEvents(), this._applyEvents(this._events)
        },
        _detachEvents: function () {
            this._unapplyEvents(this._events)
        },
        _attachSecondaryEvents: function () {
            this._detachSecondaryEvents(), this._applyEvents(this._secondaryEvents)
        },
        _detachSecondaryEvents: function () {
            this._unapplyEvents(this._secondaryEvents)
        },
        _trigger: function (b, c) {
            var d = c || this.dates.get(-1), e = this._utc_to_local(d);
            this.element.trigger({
                type: b,
                date: e,
                viewMode: this.viewMode,
                dates: a.map(this.dates, this._utc_to_local),
                format: a.proxy(function (a, b) {
                    0 === arguments.length ? (a = this.dates.length-1, b = this.o.format) : "string" == typeof a && (b = a, a = this.dates.length-1), b = b || this.o.format;
                    var c = this.dates.get(a);
                    return r.formatDate(c, b, this.o.language)
                }, this)
            })
        },
        show: function () {
            if (!(this.inputField.is(":disabled") || this.inputField.prop("readonly") && !1 === this.o.enableOnReadonly)) return this.isInline || this.picker.appendTo(this.o.container), this.place(), this.picker.show(), this._attachSecondaryEvents(), this._trigger("show"), (window.navigator.msMaxTouchPoints || "ontouchstart" in document) && this.o.disableTouchKeyboard && a(this.element).blur(), this
        },
        hide: function () {
            return this.isInline || !this.picker.is(":visible") ? this : (this.focusDate = null, this.picker.hide().detach(), this._detachSecondaryEvents(), this.setViewMode(this.o.startView), this.o.forceParse && this.inputField.val() && this.setValue(), this._trigger("hide"), this)
        },
        destroy: function () {
            return this.hide(), this._detachEvents(), this._detachSecondaryEvents(), this.picker.remove(), delete this.element.data().datepicker, this.isInput || delete this.element.data().date, this
        },
        paste: function (b) {
            var c;
            if (b.originalEvent.clipboardData && b.originalEvent.clipboardData.types && -1 !== a.inArray("text/plain", b.originalEvent.clipboardData.types)) c = b.originalEvent.clipboardData.getData("text/plain"); else {
                if (!window.clipboardData) return;
                c = window.clipboardData.getData("Text")
            }
            this.setDate(c), this.update(), b.preventDefault()
        },
        _utc_to_local: function (a) {
            if (!a) return a;
            var b = new Date(a.getTime()+6e4 * a.getTimezoneOffset());
            return b.getTimezoneOffset() !== a.getTimezoneOffset() && (b = new Date(a.getTime()+6e4 * b.getTimezoneOffset())), b
        },
        _local_to_utc: function (a) {
            return a && new Date(a.getTime()-6e4 * a.getTimezoneOffset())
        },
        _zero_time: function (a) {
            return a && new Date(a.getFullYear(), a.getMonth(), a.getDate())
        },
        _zero_utc_time: function (a) {
            return a && c(a.getUTCFullYear(), a.getUTCMonth(), a.getUTCDate())
        },
        getDates: function () {
            return a.map(this.dates, this._utc_to_local)
        },
        getUTCDates: function () {
            return a.map(this.dates, function (a) {
                return new Date(a)
            })
        },
        getDate: function () {
            return this._utc_to_local(this.getUTCDate())
        },
        getUTCDate: function () {
            var a = this.dates.get(-1);
            return a !== b ? new Date(a) : null
        },
        clearDates: function () {
            this.inputField.val(""), this.update(), this._trigger("changeDate"), this.o.autoclose && this.hide()
        },
        setDates: function () {
            var b = a.isArray(arguments[0]) ? arguments[0] : arguments;
            return this.update.apply(this, b), this._trigger("changeDate"), this.setValue(), this
        },
        setUTCDates: function () {
            var b = a.isArray(arguments[0]) ? arguments[0] : arguments;
            return this.setDates.apply(this, a.map(b, this._utc_to_local)), this
        },
        setDate: f("setDates"),
        setUTCDate: f("setUTCDates"),
        remove: f("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead"),
        setValue: function () {
            var a = this.getFormattedDate();
            return this.inputField.val(a), this
        },
        getFormattedDate: function (c) {
            c === b && (c = this.o.format);
            var d = this.o.language;
            return a.map(this.dates, function (a) {
                return r.formatDate(a, c, d)
            }).join(this.o.multidateSeparator)
        },
        getStartDate: function () {
            return this.o.startDate
        },
        setStartDate: function (a) {
            return this._process_options({startDate: a}), this.update(), this.updateNavArrows(), this
        },
        getEndDate: function () {
            return this.o.endDate
        },
        setEndDate: function (a) {
            return this._process_options({endDate: a}), this.update(), this.updateNavArrows(), this
        },
        setDaysOfWeekDisabled: function (a) {
            return this._process_options({daysOfWeekDisabled: a}), this.update(), this
        },
        setDaysOfWeekHighlighted: function (a) {
            return this._process_options({daysOfWeekHighlighted: a}), this.update(), this
        },
        setDatesDisabled: function (a) {
            return this._process_options({datesDisabled: a}), this.update(), this
        },
        place: function () {
            if (this.isInline) return this;
            var b = this.picker.outerWidth(), c = this.picker.outerHeight(), d = a(this.o.container), e = d.width(),
                f = "body" === this.o.container ? a(document).scrollTop() : d.scrollTop(), g = d.offset(), h = [0];
            this.element.parents().each(function () {
                var b = a(this).css("z-index");
                "auto" !== b && 0 !== Number(b) && h.push(Number(b))
            });
            var i = Math.max.apply(Math, h)+this.o.zIndexOffset,
                j = this.component ? this.component.parent().offset() : this.element.offset(),
                k = this.component ? this.component.outerHeight(!0) : this.element.outerHeight(!1),
                l = this.component ? this.component.outerWidth(!0) : this.element.outerWidth(!1), m = j.left-g.left,
                n = j.top-g.top;
            "body" !== this.o.container && (n += f), this.picker.removeClass("datepicker-orient-top datepicker-orient-bottom datepicker-orient-right datepicker-orient-left"), "auto" !== this.o.orientation.x ? (this.picker.addClass("datepicker-orient-"+this.o.orientation.x), "right" === this.o.orientation.x && (m -= b-l)) : j.left < 0 ? (this.picker.addClass("datepicker-orient-left"), m -= j.left-10) : m+b > e ? (this.picker.addClass("datepicker-orient-right"), m += l-b) : this.o.rtl ? this.picker.addClass("datepicker-orient-right") : this.picker.addClass("datepicker-orient-left");
            var o, p = this.o.orientation.y;
            if ("auto" === p && (o = -f+n-c, p = o < 0 ? "bottom" : "top"), this.picker.addClass("datepicker-orient-"+p), "top" === p ? n -= c+parseInt(this.picker.css("padding-top")) : n += k, this.o.rtl) {
                var q = e-(m+l);
                this.picker.css({top: n, right: q, zIndex: i})
            } else this.picker.css({top: n, left: m, zIndex: i});
            return this
        },
        _allow_update: !0,
        update: function () {
            if (!this._allow_update) return this;
            var b = this.dates.copy(), c = [], d = !1;
            return arguments.length ? (a.each(arguments, a.proxy(function (a, b) {
                b instanceof Date && (b = this._local_to_utc(b)), c.push(b)
            }, this)), d = !0) : (c = this.isInput ? this.element.val() : this.element.data("date") || this.inputField.val(), c = c && this.o.multidate ? c.split(this.o.multidateSeparator) : [c], delete this.element.data().date), c = a.map(c, a.proxy(function (a) {
                return r.parseDate(a, this.o.format, this.o.language, this.o.assumeNearbyYear)
            }, this)), c = a.grep(c, a.proxy(function (a) {
                return !this.dateWithinRange(a) || !a
            }, this), !0), this.dates.replace(c), this.o.updateViewDate && (this.dates.length ? this.viewDate = new Date(this.dates.get(-1)) : this.viewDate < this.o.startDate ? this.viewDate = new Date(this.o.startDate) : this.viewDate > this.o.endDate ? this.viewDate = new Date(this.o.endDate) : this.viewDate = this.o.defaultViewDate), d ? (this.setValue(), this.element.change()) : this.dates.length && String(b) !== String(this.dates) && d && (this._trigger("changeDate"), this.element.change()), !this.dates.length && b.length && (this._trigger("clearDate"), this.element.change()), this.fill(), this
        },
        fillDow: function () {
            if (this.o.showWeekDays) {
                var b = this.o.weekStart, c = "<tr>";
                for (this.o.calendarWeeks && (c += '<th class="cw">&#160;</th>'); b < this.o.weekStart+7;) c += '<th class="dow', -1 !== a.inArray(b, this.o.daysOfWeekDisabled) && (c += " disabled"), c += '">'+q[this.o.language].daysMin[b++ % 7]+"</th>";
                c += "</tr>", this.picker.find(".datepicker-days thead").append(c)
            }
        },
        fillMonths: function () {
            for (var a, b = this._utc_to_local(this.viewDate), c = "", d = 0; d < 12; d++) a = b && b.getMonth() === d ? " focused" : "", c += '<span class="month'+a+'">'+q[this.o.language].monthsShort[d]+"</span>";
            this.picker.find(".datepicker-months td").html(c)
        },
        setRange: function (b) {
            b && b.length ? this.range = a.map(b, function (a) {
                return a.valueOf()
            }) : delete this.range, this.fill()
        },
        getClassNames: function (b) {
            var c = [], f = this.viewDate.getUTCFullYear(), g = this.viewDate.getUTCMonth(), h = d();
            return b.getUTCFullYear() < f || b.getUTCFullYear() === f && b.getUTCMonth() < g ? c.push("old") : (b.getUTCFullYear() > f || b.getUTCFullYear() === f && b.getUTCMonth() > g) && c.push("new"), this.focusDate && b.valueOf() === this.focusDate.valueOf() && c.push("focused"), this.o.todayHighlight && e(b, h) && c.push("today"), -1 !== this.dates.contains(b) && c.push("active"), this.dateWithinRange(b) || c.push("disabled"), this.dateIsDisabled(b) && c.push("disabled", "disabled-date"), -1 !== a.inArray(b.getUTCDay(), this.o.daysOfWeekHighlighted) && c.push("highlighted"), this.range && (b > this.range[0] && b < this.range[this.range.length-1] && c.push("range"), -1 !== a.inArray(b.valueOf(), this.range) && c.push("selected"), b.valueOf() === this.range[0] && c.push("range-start"), b.valueOf() === this.range[this.range.length-1] && c.push("range-end")), c
        },
        _fill_yearsView: function (c, d, e, f, g, h, i) {
            for (var j, k, l, m = "", n = e / 10, o = this.picker.find(c), p = Math.floor(f / e) * e, q = p+9 * n, r = Math.floor(this.viewDate.getFullYear() / n) * n, s = a.map(this.dates, function (a) {
                return Math.floor(a.getUTCFullYear() / n) * n
            }), t = p-n; t <= q+n; t += n) j = [d], k = null, t === p-n ? j.push("old") : t === q+n && j.push("new"), -1 !== a.inArray(t, s) && j.push("active"), (t < g || t > h) && j.push("disabled"), t === r && j.push("focused"), i !== a.noop && (l = i(new Date(t, 0, 1)), l === b ? l = {} : "boolean" == typeof l ? l = {enabled: l} : "string" == typeof l && (l = {classes: l}), !1 === l.enabled && j.push("disabled"), l.classes && (j = j.concat(l.classes.split(/\s+/))), l.tooltip && (k = l.tooltip)), m += '<span class="'+j.join(" ")+'"'+(k ? ' title="'+k+'"' : "")+">"+t+"</span>";
            o.find(".datepicker-switch").text(p+"-"+q), o.find("td").html(m)
        },
        fill: function () {
            var e, f, g = new Date(this.viewDate), h = g.getUTCFullYear(), i = g.getUTCMonth(),
                j = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0,
                k = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0,
                l = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0,
                m = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0,
                n = q[this.o.language].today || q.en.today || "", o = q[this.o.language].clear || q.en.clear || "",
                p = q[this.o.language].titleFormat || q.en.titleFormat, s = d(),
                t = (!0 === this.o.todayBtn || "linked" === this.o.todayBtn) && s >= this.o.startDate && s <= this.o.endDate && !this.weekOfDateIsDisabled(s);
            if (!isNaN(h) && !isNaN(i)) {
                this.picker.find(".datepicker-days .datepicker-switch").text(r.formatDate(g, p, this.o.language)), this.picker.find("tfoot .today").text(n).css("display", t ? "table-cell" : "none"), this.picker.find("tfoot .clear").text(o).css("display", !0 === this.o.clearBtn ? "table-cell" : "none"), this.picker.find("thead .datepicker-title").text(this.o.title).css("display", "string" == typeof this.o.title && "" !== this.o.title ? "table-cell" : "none"), this.updateNavArrows(), this.fillMonths();
                var u = c(h, i, 0), v = u.getUTCDate();
                u.setUTCDate(v-(u.getUTCDay()-this.o.weekStart+7) % 7);
                var w = new Date(u);
                u.getUTCFullYear() < 100 && w.setUTCFullYear(u.getUTCFullYear()), w.setUTCDate(w.getUTCDate()+42), w = w.valueOf();
                for (var x, y, z = []; u.valueOf() < w;) {
                    if ((x = u.getUTCDay()) === this.o.weekStart && (z.push("<tr>"), this.o.calendarWeeks)) {
                        var A = new Date(+u+(this.o.weekStart-x-7) % 7 * 864e5),
                            B = new Date(Number(A)+(11-A.getUTCDay()) % 7 * 864e5),
                            C = new Date(Number(C = c(B.getUTCFullYear(), 0, 1))+(11-C.getUTCDay()) % 7 * 864e5),
                            D = (B-C) / 864e5 / 7+1;
                        z.push('<td class="cw">'+D+"</td>")
                    }
                    y = this.getClassNames(u), y.push("day");
                    var E = u.getUTCDate();
                    this.o.beforeShowDay !== a.noop && (f = this.o.beforeShowDay(this._utc_to_local(u)), f === b ? f = {} : "boolean" == typeof f ? f = {enabled: f} : "string" == typeof f && (f = {classes: f}), !1 === f.enabled && y.push("disabled"), f.classes && (y = y.concat(f.classes.split(/\s+/))), f.tooltip && (e = f.tooltip), f.content && (E = f.content)), y = a.isFunction(a.uniqueSort) ? a.uniqueSort(y) : a.unique(y), z.push('<td class="'+y.join(" ")+'"'+(e ? ' title="'+e+'"' : "")+' data-date="'+u.getTime().toString()+'">'+E+"</td>"), e = null, x === this.o.weekEnd && z.push("</tr>"), u.setUTCDate(u.getUTCDate()+1)
                }
                this.picker.find(".datepicker-days tbody").html(z.join(""));
                var F = q[this.o.language].monthsTitle || q.en.monthsTitle || "Months",
                    G = this.picker.find(".datepicker-months").find(".datepicker-switch").text(this.o.maxViewMode < 2 ? F : h).end().find("tbody span").removeClass("active");
                if (a.each(this.dates, function (a, b) {
                    b.getUTCFullYear() === h && G.eq(b.getUTCMonth()).addClass("active")
                }), (h < j || h > l) && G.addClass("disabled"), h === j && G.slice(0, k).addClass("disabled"), h === l && G.slice(m+1).addClass("disabled"), this.o.beforeShowMonth !== a.noop) {
                    var H = this;
                    a.each(G, function (c, d) {
                        var e = new Date(h, c, 1), f = H.o.beforeShowMonth(e);
                        f === b ? f = {} : "boolean" == typeof f ? f = {enabled: f} : "string" == typeof f && (f = {classes: f}), !1 !== f.enabled || a(d).hasClass("disabled") || a(d).addClass("disabled"), f.classes && a(d).addClass(f.classes), f.tooltip && a(d).prop("title", f.tooltip)
                    })
                }
                this._fill_yearsView(".datepicker-years", "year", 10, h, j, l, this.o.beforeShowYear), this._fill_yearsView(".datepicker-decades", "decade", 100, h, j, l, this.o.beforeShowDecade), this._fill_yearsView(".datepicker-centuries", "century", 1e3, h, j, l, this.o.beforeShowCentury)
            }
        },
        updateNavArrows: function () {
            if (this._allow_update) {
                var a, b, c = new Date(this.viewDate), d = c.getUTCFullYear(), e = c.getUTCMonth(),
                    f = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCFullYear() : -1 / 0,
                    g = this.o.startDate !== -1 / 0 ? this.o.startDate.getUTCMonth() : -1 / 0,
                    h = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCFullYear() : 1 / 0,
                    i = this.o.endDate !== 1 / 0 ? this.o.endDate.getUTCMonth() : 1 / 0, j = 1;
                switch (this.viewMode) {
                    case 4:
                        j *= 10;
                    case 3:
                        j *= 10;
                    case 2:
                        j *= 10;
                    case 1:
                        a = Math.floor(d / j) * j <= f, b = Math.floor(d / j) * j+j > h;
                        break;
                    case 0:
                        a = d <= f && e <= g, b = d >= h && e >= i
                }
                this.picker.find(".prev").toggleClass("disabled", a), this.picker.find(".next").toggleClass("disabled", b)
            }
        },
        click: function (b) {
            b.preventDefault(), b.stopPropagation();
            var e, f, g, h;
            e = a(b.target), e.hasClass("datepicker-switch") && this.viewMode !== this.o.maxViewMode && this.setViewMode(this.viewMode+1), e.hasClass("today") && !e.hasClass("day") && (this.setViewMode(0), this._setDate(d(), "linked" === this.o.todayBtn ? null : "view")), e.hasClass("clear") && this.clearDates(), e.hasClass("disabled") || (e.hasClass("month") || e.hasClass("year") || e.hasClass("decade") || e.hasClass("century")) && (this.viewDate.setUTCDate(1), f = 1, 1 === this.viewMode ? (h = e.parent().find("span").index(e), g = this.viewDate.getUTCFullYear(), this.viewDate.setUTCMonth(h)) : (h = 0, g = Number(e.text()), this.viewDate.setUTCFullYear(g)), this._trigger(r.viewModes[this.viewMode-1].e, this.viewDate), this.viewMode === this.o.minViewMode ? this._setDate(c(g, h, f)) : (this.setViewMode(this.viewMode-1), this.fill())), this.picker.is(":visible") && this._focused_from && this._focused_from.focus(), delete this._focused_from
        },
        dayCellClick: function (b) {
            var c = a(b.currentTarget), d = c.data("date"), e = new Date(d);
            this.o.updateViewDate && (e.getUTCFullYear() !== this.viewDate.getUTCFullYear() && this._trigger("changeYear", this.viewDate), e.getUTCMonth() !== this.viewDate.getUTCMonth() && this._trigger("changeMonth", this.viewDate)), this._setDate(e)
        },
        navArrowsClick: function (b) {
            var c = a(b.currentTarget), d = c.hasClass("prev") ? -1 : 1;
            0 !== this.viewMode && (d *= 12 * r.viewModes[this.viewMode].navStep), this.viewDate = this.moveMonth(this.viewDate, d), this._trigger(r.viewModes[this.viewMode].e, this.viewDate), this.fill()
        },
        _toggle_multidate: function (a) {
            var b = this.dates.contains(a);
            if (a || this.dates.clear(), -1 !== b ? (!0 === this.o.multidate || this.o.multidate > 1 || this.o.toggleActive) && this.dates.remove(b) : !1 === this.o.multidate ? (this.dates.clear(), this.dates.push(a)) : this.dates.push(a), "number" == typeof this.o.multidate) for (; this.dates.length > this.o.multidate;) this.dates.remove(0)
        },
        _setDate: function (a, b) {
            b && "date" !== b || this._toggle_multidate(a && new Date(a)), (!b && this.o.updateViewDate || "view" === b) && (this.viewDate = a && new Date(a)), this.fill(), this.setValue(), b && "view" === b || this._trigger("changeDate"), this.inputField.trigger("change"), !this.o.autoclose || b && "date" !== b || this.hide()
        },
        moveDay: function (a, b) {
            var c = new Date(a);
            return c.setUTCDate(a.getUTCDate()+b), c
        },
        moveWeek: function (a, b) {
            return this.moveDay(a, 7 * b)
        },
        moveMonth: function (a, b) {
            if (!g(a)) return this.o.defaultViewDate;
            if (!b) return a;
            var c, d, e = new Date(a.valueOf()), f = e.getUTCDate(), h = e.getUTCMonth(), i = Math.abs(b);
            if (b = b > 0 ? 1 : -1, 1 === i) d = -1 === b ? function () {
                return e.getUTCMonth() === h
            } : function () {
                return e.getUTCMonth() !== c
            }, c = h+b, e.setUTCMonth(c), c = (c+12) % 12; else {
                for (var j = 0; j < i; j++) e = this.moveMonth(e, b);
                c = e.getUTCMonth(), e.setUTCDate(f), d = function () {
                    return c !== e.getUTCMonth()
                }
            }
            for (; d();) e.setUTCDate(--f), e.setUTCMonth(c);
            return e
        },
        moveYear: function (a, b) {
            return this.moveMonth(a, 12 * b)
        },
        moveAvailableDate: function (a, b, c) {
            do {
                if (a = this[c](a, b), !this.dateWithinRange(a)) return !1;
                c = "moveDay"
            } while (this.dateIsDisabled(a));
            return a
        },
        weekOfDateIsDisabled: function (b) {
            return -1 !== a.inArray(b.getUTCDay(), this.o.daysOfWeekDisabled)
        },
        dateIsDisabled: function (b) {
            return this.weekOfDateIsDisabled(b) || a.grep(this.o.datesDisabled, function (a) {
                return e(b, a)
            }).length > 0
        },
        dateWithinRange: function (a) {
            return a >= this.o.startDate && a <= this.o.endDate
        },
        keydown: function (a) {
            if (!this.picker.is(":visible")) return void (40 !== a.keyCode && 27 !== a.keyCode || (this.show(), a.stopPropagation()));
            var b, c, d = !1, e = this.focusDate || this.viewDate;
            switch (a.keyCode) {
                case 27:
                    this.focusDate ? (this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.fill()) : this.hide(), a.preventDefault(), a.stopPropagation();
                    break;
                case 37:
                case 38:
                case 39:
                case 40:
                    if (!this.o.keyboardNavigation || 7 === this.o.daysOfWeekDisabled.length) break;
                    b = 37 === a.keyCode || 38 === a.keyCode ? -1 : 1, 0 === this.viewMode ? a.ctrlKey ? (c = this.moveAvailableDate(e, b, "moveYear")) && this._trigger("changeYear", this.viewDate) : a.shiftKey ? (c = this.moveAvailableDate(e, b, "moveMonth")) && this._trigger("changeMonth", this.viewDate) : 37 === a.keyCode || 39 === a.keyCode ? c = this.moveAvailableDate(e, b, "moveDay") : this.weekOfDateIsDisabled(e) || (c = this.moveAvailableDate(e, b, "moveWeek")) : 1 === this.viewMode ? (38 !== a.keyCode && 40 !== a.keyCode || (b *= 4), c = this.moveAvailableDate(e, b, "moveMonth")) : 2 === this.viewMode && (38 !== a.keyCode && 40 !== a.keyCode || (b *= 4), c = this.moveAvailableDate(e, b, "moveYear")), c && (this.focusDate = this.viewDate = c, this.setValue(), this.fill(), a.preventDefault());
                    break;
                case 13:
                    if (!this.o.forceParse) break;
                    e = this.focusDate || this.dates.get(-1) || this.viewDate, this.o.keyboardNavigation && (this._toggle_multidate(e), d = !0), this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.setValue(), this.fill(), this.picker.is(":visible") && (a.preventDefault(), a.stopPropagation(), this.o.autoclose && this.hide());
                    break;
                case 9:
                    this.focusDate = null, this.viewDate = this.dates.get(-1) || this.viewDate, this.fill(), this.hide()
            }
            d && (this.dates.length ? this._trigger("changeDate") : this._trigger("clearDate"), this.inputField.trigger("change"))
        },
        setViewMode: function (a) {
            this.viewMode = a, this.picker.children("div").hide().filter(".datepicker-"+r.viewModes[this.viewMode].clsName).show(), this.updateNavArrows(), this._trigger("changeViewMode", new Date(this.viewDate))
        }
    };
    var l = function (b, c) {
        a.data(b, "datepicker", this), this.element = a(b), this.inputs = a.map(c.inputs, function (a) {
            return a.jquery ? a[0] : a
        }), delete c.inputs, this.keepEmptyValues = c.keepEmptyValues, delete c.keepEmptyValues, n.call(a(this.inputs), c).on("changeDate", a.proxy(this.dateUpdated, this)), this.pickers = a.map(this.inputs, function (b) {
            return a.data(b, "datepicker")
        }), this.updateDates()
    };
    l.prototype = {
        updateDates: function () {
            this.dates = a.map(this.pickers, function (a) {
                return a.getUTCDate()
            }), this.updateRanges()
        },
        updateRanges: function () {
            var b = a.map(this.dates, function (a) {
                return a.valueOf()
            });
            a.each(this.pickers, function (a, c) {
                c.setRange(b)
            })
        },
        clearDates: function () {
            a.each(this.pickers, function (a, b) {
                b.clearDates()
            })
        },
        dateUpdated: function (c) {
            if (!this.updating) {
                this.updating = !0;
                var d = a.data(c.target, "datepicker");
                if (d !== b) {
                    var e = d.getUTCDate(), f = this.keepEmptyValues, g = a.inArray(c.target, this.inputs), h = g-1,
                        i = g+1, j = this.inputs.length;
                    if (-1 !== g) {
                        if (a.each(this.pickers, function (a, b) {
                            b.getUTCDate() || b !== d && f || b.setUTCDate(e)
                        }), e < this.dates[h]) for (; h >= 0 && e < this.dates[h];) this.pickers[h--].setUTCDate(e); else if (e > this.dates[i]) for (; i < j && e > this.dates[i];) this.pickers[i++].setUTCDate(e);
                        this.updateDates(), delete this.updating
                    }
                }
            }
        },
        destroy: function () {
            a.map(this.pickers, function (a) {
                a.destroy()
            }), a(this.inputs).off("changeDate", this.dateUpdated), delete this.element.data().datepicker
        },
        remove: f("destroy", "Method `remove` is deprecated and will be removed in version 2.0. Use `destroy` instead")
    };
    var m = a.fn.datepicker, n = function (c) {
        var d = Array.apply(null, arguments);
        d.shift();
        var e;
        if (this.each(function () {
            var b = a(this), f = b.data("datepicker"), g = "object" == typeof c && c;
            if (!f) {
                var j = h(this, "date"), m = a.extend({}, o, j, g), n = i(m.language), p = a.extend({}, o, n, j, g);
                b.hasClass("input-daterange") || p.inputs ? (a.extend(p, {inputs: p.inputs || b.find("input").toArray()}), f = new l(this, p)) : f = new k(this, p), b.data("datepicker", f)
            }
            "string" == typeof c && "function" == typeof f[c] && (e = f[c].apply(f, d))
        }), e === b || e instanceof k || e instanceof l) return this;
        if (this.length > 1) throw new Error("Using only allowed for the collection of a single element ("+c+" function)");
        return e
    };
    a.fn.datepicker = n;
    var o = a.fn.datepicker.defaults = {
        assumeNearbyYear: !1,
        autoclose: !1,
        beforeShowDay: a.noop,
        beforeShowMonth: a.noop,
        beforeShowYear: a.noop,
        beforeShowDecade: a.noop,
        beforeShowCentury: a.noop,
        calendarWeeks: !1,
        clearBtn: !1,
        toggleActive: !1,
        daysOfWeekDisabled: [],
        daysOfWeekHighlighted: [],
        datesDisabled: [],
        endDate: 1 / 0,
        forceParse: !0,
        format: "mm/dd/yyyy",
        keepEmptyValues: !1,
        keyboardNavigation: !0,
        language: "en",
        minViewMode: 0,
        maxViewMode: 4,
        multidate: !1,
        multidateSeparator: ",",
        orientation: "auto",
        rtl: !1,
        startDate: -1 / 0,
        startView: 0,
        todayBtn: !1,
        todayHighlight: !1,
        updateViewDate: !0,
        weekStart: 0,
        disableTouchKeyboard: !1,
        enableOnReadonly: !0,
        showOnFocus: !0,
        zIndexOffset: 10,
        container: "body",
        immediateUpdates: !1,
        title: "",
        templates: {leftArrow: "&#x00AB;", rightArrow: "&#x00BB;"},
        showWeekDays: !0
    }, p = a.fn.datepicker.locale_opts = ["format", "rtl", "weekStart"];
    a.fn.datepicker.Constructor = k;
    var q = a.fn.datepicker.dates = {
        en: {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            today: "Today",
            clear: "Clear",
            titleFormat: "MM yyyy"
        }
    }, r = {
        viewModes: [{names: ["days", "month"], clsName: "days", e: "changeMonth"}, {
            names: ["months", "year"],
            clsName: "months",
            e: "changeYear",
            navStep: 1
        }, {
            names: ["years", "decade"],
            clsName: "years",
            e: "changeDecade",
            navStep: 10
        }, {
            names: ["decades", "century"],
            clsName: "decades",
            e: "changeCentury",
            navStep: 100
        }, {names: ["centuries", "millennium"], clsName: "centuries", e: "changeMillennium", navStep: 1e3}],
        validParts: /dd?|DD?|mm?|MM?|yy(?:yy)?/g,
        nonpunctuation: /[^ -\/:-@\u5e74\u6708\u65e5\[-`{-~\t\n\r]+/g,
        parseFormat: function (a) {
            if ("function" == typeof a.toValue && "function" == typeof a.toDisplay) return a;
            var b = a.replace(this.validParts, "\0").split("\0"), c = a.match(this.validParts);
            if (!b || !b.length || !c || 0 === c.length) throw new Error("Invalid date format.");
            return {separators: b, parts: c}
        },
        parseDate: function (c, e, f, g) {
            function h(a, b) {
                return !0 === b && (b = 10), a < 100 && (a += 2e3) > (new Date).getFullYear()+b && (a -= 100), a
            }

            function i() {
                var a = this.slice(0, j[n].length), b = j[n].slice(0, a.length);
                return a.toLowerCase() === b.toLowerCase()
            }

            if (!c) return b;
            if (c instanceof Date) return c;
            if ("string" == typeof e && (e = r.parseFormat(e)), e.toValue) return e.toValue(c, e, f);
            var j, l, m, n, o, p = {d: "moveDay", m: "moveMonth", w: "moveWeek", y: "moveYear"},
                s = {yesterday: "-1d", today: "+0d", tomorrow: "+1d"};
            if (c in s && (c = s[c]), /^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/i.test(c)) {
                for (j = c.match(/([\-+]\d+)([dmwy])/gi), c = new Date, n = 0; n < j.length; n++) l = j[n].match(/([\-+]\d+)([dmwy])/i), m = Number(l[1]), o = p[l[2].toLowerCase()], c = k.prototype[o](c, m);
                return k.prototype._zero_utc_time(c)
            }
            j = c && c.match(this.nonpunctuation) || [];
            var t, u, v = {}, w = ["yyyy", "yy", "M", "MM", "m", "mm", "d", "dd"], x = {
                yyyy: function (a, b) {
                    return a.setUTCFullYear(g ? h(b, g) : b)
                }, m: function (a, b) {
                    if (isNaN(a)) return a;
                    for (b -= 1; b < 0;) b += 12;
                    for (b %= 12, a.setUTCMonth(b); a.getUTCMonth() !== b;) a.setUTCDate(a.getUTCDate()-1);
                    return a
                }, d: function (a, b) {
                    return a.setUTCDate(b)
                }
            };
            x.yy = x.yyyy, x.M = x.MM = x.mm = x.m, x.dd = x.d, c = d();
            var y = e.parts.slice();
            if (j.length !== y.length && (y = a(y).filter(function (b, c) {
                return -1 !== a.inArray(c, w)
            }).toArray()), j.length === y.length) {
                var z;
                for (n = 0, z = y.length; n < z; n++) {
                    if (t = parseInt(j[n], 10), l = y[n], isNaN(t)) switch (l) {
                        case"MM":
                            u = a(q[f].months).filter(i), t = a.inArray(u[0], q[f].months)+1;
                            break;
                        case"M":
                            u = a(q[f].monthsShort).filter(i), t = a.inArray(u[0], q[f].monthsShort)+1
                    }
                    v[l] = t
                }
                var A, B;
                for (n = 0; n < w.length; n++) (B = w[n]) in v && !isNaN(v[B]) && (A = new Date(c), x[B](A, v[B]), isNaN(A) || (c = A))
            }
            return c
        },
        formatDate: function (b, c, d) {
            if (!b) return "";
            if ("string" == typeof c && (c = r.parseFormat(c)), c.toDisplay) return c.toDisplay(b, c, d);
            var e = {
                d: b.getUTCDate(),
                D: q[d].daysShort[b.getUTCDay()],
                DD: q[d].days[b.getUTCDay()],
                m: b.getUTCMonth()+1,
                M: q[d].monthsShort[b.getUTCMonth()],
                MM: q[d].months[b.getUTCMonth()],
                yy: b.getUTCFullYear().toString().substring(2),
                yyyy: b.getUTCFullYear()
            };
            e.dd = (e.d < 10 ? "0" : "")+e.d, e.mm = (e.m < 10 ? "0" : "")+e.m, b = [];
            for (var f = a.extend([], c.separators), g = 0, h = c.parts.length; g <= h; g++) f.length && b.push(f.shift()), b.push(e[c.parts[g]]);
            return b.join("")
        },
        headTemplate: '<thead><tr><th colspan="7" class="datepicker-title"></th></tr><tr><th class="prev">'+o.templates.leftArrow+'</th><th colspan="5" class="datepicker-switch"></th><th class="next">'+o.templates.rightArrow+"</th></tr></thead>",
        contTemplate: '<tbody><tr><td colspan="7"></td></tr></tbody>',
        footTemplate: '<tfoot><tr><th colspan="7" class="today"></th></tr><tr><th colspan="7" class="clear"></th></tr></tfoot>'
    };
    r.template = '<div class="datepicker"><div class="datepicker-days"><table class="table-condensed">'+r.headTemplate+"<tbody></tbody>"+r.footTemplate+'</table></div><div class="datepicker-months"><table class="table-condensed">'+r.headTemplate+r.contTemplate+r.footTemplate+'</table></div><div class="datepicker-years"><table class="table-condensed">'+r.headTemplate+r.contTemplate+r.footTemplate+'</table></div><div class="datepicker-decades"><table class="table-condensed">'+r.headTemplate+r.contTemplate+r.footTemplate+'</table></div><div class="datepicker-centuries"><table class="table-condensed">'+r.headTemplate+r.contTemplate+r.footTemplate+"</table></div></div>", a.fn.datepicker.DPGlobal = r, a.fn.datepicker.noConflict = function () {
        return a.fn.datepicker = m, this
    }, a.fn.datepicker.version = "1.9.0", a.fn.datepicker.deprecated = function (a) {
        var b = window.console;
        b && b.warn && b.warn("DEPRECATED: "+a)
    }, a(document).on("focus.datepicker.data-api click.datepicker.data-api", '[data-provide="datepicker"]', function (b) {
        var c = a(this);
        c.data("datepicker") || (b.preventDefault(), n.call(c, "show"))
    }), a(function () {
        n.call(a('[data-provide="datepicker-inline"]'))
    })
});
/*!
 * ClockPicker v0.0.7 (http://weareoutman.github.io/clockpicker/)
 * Copyright 2014 Wang Shenwei.
 * Licensed under MIT (https://github.com/weareoutman/clockpicker/blob/gh-pages/LICENSE)
 */
!function () {
    function t(t) {
        return document.createElementNS(p, t)
    }

    function i(t) {
        return (10 > t ? "0" : "")+t
    }

    function e(t) {
        var i = ++m+"";
        return t ? t+i : i
    }

    function s(s, r) {
        function p(t, i) {
            var e = u.offset(), s = /^touch/.test(t.type), o = e.left+b, n = e.top+b,
                p = (s ? t.originalEvent.touches[0] : t).pageX-o, h = (s ? t.originalEvent.touches[0] : t).pageY-n,
                k = Math.sqrt(p * p+h * h), v = !1;
            if (!i || !(g-y > k || k > g+y)) {
                t.preventDefault();
                var m = setTimeout(function () {
                    c.addClass("clockpicker-moving")
                }, 200);
                l && u.append(x.canvas), x.setHand(p, h, !i, !0), a.off(d).on(d, function (t) {
                    t.preventDefault();
                    var i = /^touch/.test(t.type), e = (i ? t.originalEvent.touches[0] : t).pageX-o,
                        s = (i ? t.originalEvent.touches[0] : t).pageY-n;
                    (v || e !== p || s !== h) && (v = !0, x.setHand(e, s, !1, !0))
                }), a.off(f).on(f, function (t) {
                    a.off(f), t.preventDefault();
                    var e = /^touch/.test(t.type), s = (e ? t.originalEvent.changedTouches[0] : t).pageX-o,
                        l = (e ? t.originalEvent.changedTouches[0] : t).pageY-n;
                    (i || v) && s === p && l === h && x.setHand(s, l), "hours" === x.currentView ? x.toggleView("minutes", A / 2) : r.autoclose && (x.minutesView.addClass("clockpicker-dial-out"), setTimeout(function () {
                        x.done()
                    }, A / 2)), u.prepend(j), clearTimeout(m), c.removeClass("clockpicker-moving"), a.off(d)
                })
            }
        }

        var h = n(V), u = h.find(".clockpicker-plate"), v = h.find(".clockpicker-hours"),
            m = h.find(".clockpicker-minutes"), T = h.find(".clockpicker-am-pm-block"),
            C = "INPUT" === s.prop("tagName"), H = C ? s : s.find("input"), P = s.find(".input-group-addon"), x = this;
        if (this.id = e("cp"), this.element = s, this.options = r, this.isAppended = !1, this.isShown = !1, this.currentView = "hours", this.isInput = C, this.input = H, this.addon = P, this.popover = h, this.plate = u, this.hoursView = v, this.minutesView = m, this.amPmBlock = T, this.spanHours = h.find(".clockpicker-span-hours"), this.spanMinutes = h.find(".clockpicker-span-minutes"), this.spanAmPm = h.find(".clockpicker-span-am-pm"), this.amOrPm = "PM", r.twelvehour) {
            {
                var S = ['<div class="clockpicker-am-pm-block">', '<button type="button" class="btn btn-sm btn-default clockpicker-button clockpicker-am-button">', "AM</button>", '<button type="button" class="btn btn-sm btn-default clockpicker-button clockpicker-pm-button">', "PM</button>", "</div>"].join("");
                n(S)
            }
            n('<button type="button" class="btn btn-sm btn-default clockpicker-button am-button">AM</button>').on("click", function () {
                x.amOrPm = "AM", n(".clockpicker-span-am-pm").empty().append("AM")
            }).appendTo(this.amPmBlock), n('<button type="button" class="btn btn-sm btn-default clockpicker-button pm-button">PM</button>').on("click", function () {
                x.amOrPm = "PM", n(".clockpicker-span-am-pm").empty().append("PM")
            }).appendTo(this.amPmBlock)
        }
        r.autoclose || n('<button type="button" class="btn btn-sm btn-default btn-block clockpicker-button">'+r.donetext+"</button>").click(n.proxy(this.done, this)).appendTo(h), "top" !== r.placement && "bottom" !== r.placement || "top" !== r.align && "bottom" !== r.align || (r.align = "left"), "left" !== r.placement && "right" !== r.placement || "left" !== r.align && "right" !== r.align || (r.align = "top"), h.addClass(r.placement), h.addClass("clockpicker-align-"+r.align), this.spanHours.click(n.proxy(this.toggleView, this, "hours")), this.spanMinutes.click(n.proxy(this.toggleView, this, "minutes")), H.on("focus.clockpicker click.clockpicker", n.proxy(this.show, this)), P.on("click.clockpicker", n.proxy(this.toggle, this));
        var E, D, I, B, z = n('<div class="clockpicker-tick"></div>');
        if (r.twelvehour) for (E = 1; 13 > E; E += 1) D = z.clone(), I = E / 6 * Math.PI, B = g, D.css("font-size", "120%"), D.css({
            left: b+Math.sin(I) * B-y,
            top: b-Math.cos(I) * B-y
        }), D.html(0 === E ? "00" : E), v.append(D), D.on(k, p); else for (E = 0; 24 > E; E += 1) {
            D = z.clone(), I = E / 6 * Math.PI;
            var O = E > 0 && 13 > E;
            B = O ? w : g, D.css({
                left: b+Math.sin(I) * B-y,
                top: b-Math.cos(I) * B-y
            }), O && D.css("font-size", "120%"), D.html(0 === E ? "00" : E), v.append(D), D.on(k, p)
        }
        for (E = 0; 60 > E; E += 5) D = z.clone(), I = E / 30 * Math.PI, D.css({
            left: b+Math.sin(I) * g-y,
            top: b-Math.cos(I) * g-y
        }), D.css("font-size", "120%"), D.html(i(E)), m.append(D), D.on(k, p);
        if (u.on(k, function (t) {
            0 === n(t.target).closest(".clockpicker-tick").length && p(t, !0)
        }), l) {
            var j = h.find(".clockpicker-canvas"), L = t("svg");
            L.setAttribute("class", "clockpicker-svg"), L.setAttribute("width", M), L.setAttribute("height", M);
            var U = t("g");
            U.setAttribute("transform", "translate("+b+","+b+")");
            var W = t("circle");
            W.setAttribute("class", "clockpicker-canvas-bearing"), W.setAttribute("cx", 0), W.setAttribute("cy", 0), W.setAttribute("r", 2);
            var N = t("line");
            N.setAttribute("x1", 0), N.setAttribute("y1", 0);
            var X = t("circle");
            X.setAttribute("class", "clockpicker-canvas-bg"), X.setAttribute("r", y);
            var Y = t("circle");
            Y.setAttribute("class", "clockpicker-canvas-fg"), Y.setAttribute("r", 3.5), U.appendChild(N), U.appendChild(X), U.appendChild(Y), U.appendChild(W), L.appendChild(U), j.append(L), this.hand = N, this.bg = X, this.fg = Y, this.bearing = W, this.g = U, this.canvas = j
        }
        o(this.options.init)
    }

    function o(t) {
        t && "function" == typeof t && t()
    }

    var c, n = window.jQuery, r = n(window), a = n(document), p = "http://www.w3.org/2000/svg",
        l = "SVGAngle" in window && function () {
            var t, i = document.createElement("div");
            return i.innerHTML = "<svg/>", t = (i.firstChild && i.firstChild.namespaceURI) == p, i.innerHTML = "", t
        }(), h = function () {
            var t = document.createElement("div").style;
            return "transition" in t || "WebkitTransition" in t || "MozTransition" in t || "msTransition" in t || "OTransition" in t
        }(), u = "ontouchstart" in window, k = "mousedown"+(u ? " touchstart" : ""),
        d = "mousemove.clockpicker"+(u ? " touchmove.clockpicker" : ""),
        f = "mouseup.clockpicker"+(u ? " touchend.clockpicker" : ""),
        v = navigator.vibrate ? "vibrate" : navigator.webkitVibrate ? "webkitVibrate" : null, m = 0, b = 100, g = 80,
        w = 54, y = 13, M = 2 * b, A = h ? 350 : 1,
        V = ['<div class="popover clockpicker-popover">', '<div class="arrow"></div>', '<div class="popover-title">', '<span class="clockpicker-span-hours text-primary"></span>', " : ", '<span class="clockpicker-span-minutes"></span>', '<span class="clockpicker-span-am-pm"></span>', "</div>", '<div class="popover-content">', '<div class="clockpicker-plate">', '<div class="clockpicker-canvas"></div>', '<div class="clockpicker-dial clockpicker-hours"></div>', '<div class="clockpicker-dial clockpicker-minutes clockpicker-dial-out"></div>', "</div>", '<span class="clockpicker-am-pm-block">', "</span>", "</div>", "</div>"].join("");
    s.DEFAULTS = {
        "default": "",
        fromnow: 0,
        placement: "bottom",
        align: "left",
        donetext: "完成",
        autoclose: !1,
        twelvehour: !1,
        vibrate: !0
    }, s.prototype.toggle = function () {
        this[this.isShown ? "hide" : "show"]()
    }, s.prototype.locate = function () {
        var t = this.element, i = this.popover, e = t.offset(), s = t.outerWidth(), o = t.outerHeight(),
            c = this.options.placement, n = this.options.align, r = {};
        switch (i.show(), c) {
            case"bottom":
                r.top = e.top+o;
                break;
            case"right":
                r.left = e.left+s;
                break;
            case"top":
                r.top = e.top-i.outerHeight();
                break;
            case"left":
                r.left = e.left-i.outerWidth()
        }
        switch (n) {
            case"left":
                r.left = e.left;
                break;
            case"right":
                r.left = e.left+s-i.outerWidth();
                break;
            case"top":
                r.top = e.top;
                break;
            case"bottom":
                r.top = e.top+o-i.outerHeight()
        }
        i.css(r)
    }, s.prototype.show = function () {
        if (!this.isShown) {
            o(this.options.beforeShow);
            var t = this;
            this.isAppended || (c = n(document.body).append(this.popover), r.on("resize.clockpicker"+this.id, function () {
                t.isShown && t.locate()
            }), this.isAppended = !0);
            var e = ((this.input.prop("value") || this.options["default"] || "")+"").split(":");
            if ("now" === e[0]) {
                var s = new Date(+new Date+this.options.fromnow);
                e = [s.getHours(), s.getMinutes()]
            }
            this.hours = +e[0] || 0, this.minutes = +e[1] || 0, this.spanHours.html(i(this.hours)), this.spanMinutes.html(i(this.minutes)), this.toggleView("hours"), this.locate(), this.isShown = !0, a.on("click.clockpicker."+this.id+" focusin.clockpicker."+this.id, function (i) {
                var e = n(i.target);
                0 === e.closest(t.popover).length && 0 === e.closest(t.addon).length && 0 === e.closest(t.input).length && t.hide()
            }), a.on("keyup.clockpicker."+this.id, function (i) {
                27 === i.keyCode && t.hide()
            }), o(this.options.afterShow)
        }
    }, s.prototype.hide = function () {
        o(this.options.beforeHide), this.isShown = !1, a.off("click.clockpicker."+this.id+" focusin.clockpicker."+this.id), a.off("keyup.clockpicker."+this.id), this.popover.hide(), o(this.options.afterHide)
    }, s.prototype.toggleView = function (t, i) {
        var e = !1;
        "minutes" === t && "visible" === n(this.hoursView).css("visibility") && (o(this.options.beforeHourSelect), e = !0);
        var s = "hours" === t, c = s ? this.hoursView : this.minutesView, r = s ? this.minutesView : this.hoursView;
        this.currentView = t, this.spanHours.toggleClass("text-primary", s), this.spanMinutes.toggleClass("text-primary", !s), r.addClass("clockpicker-dial-out"), c.css("visibility", "visible").removeClass("clockpicker-dial-out"), this.resetClock(i), clearTimeout(this.toggleViewTimer), this.toggleViewTimer = setTimeout(function () {
            r.css("visibility", "hidden")
        }, A), e && o(this.options.afterHourSelect)
    }, s.prototype.resetClock = function (t) {
        var i = this.currentView, e = this[i], s = "hours" === i, o = Math.PI / (s ? 6 : 30), c = e * o,
            n = s && e > 0 && 13 > e ? w : g, r = Math.sin(c) * n, a = -Math.cos(c) * n, p = this;
        l && t ? (p.canvas.addClass("clockpicker-canvas-out"), setTimeout(function () {
            p.canvas.removeClass("clockpicker-canvas-out"), p.setHand(r, a)
        }, t)) : this.setHand(r, a)
    }, s.prototype.setHand = function (t, e, s, o) {
        var c, r = Math.atan2(t, -e), a = "hours" === this.currentView, p = Math.PI / (a || s ? 6 : 30),
            h = Math.sqrt(t * t+e * e), u = this.options, k = a && (g+w) / 2 > h, d = k ? w : g;
        if (u.twelvehour && (d = g), 0 > r && (r = 2 * Math.PI+r), c = Math.round(r / p), r = c * p, u.twelvehour ? a ? 0 === c && (c = 12) : (s && (c *= 5), 60 === c && (c = 0)) : a ? (12 === c && (c = 0), c = k ? 0 === c ? 12 : c : 0 === c ? 0 : c+12) : (s && (c *= 5), 60 === c && (c = 0)), this[this.currentView] !== c && v && this.options.vibrate && (this.vibrateTimer || (navigator[v](10), this.vibrateTimer = setTimeout(n.proxy(function () {
            this.vibrateTimer = null
        }, this), 100))), this[this.currentView] = c, this[a ? "spanHours" : "spanMinutes"].html(i(c)), !l) return void this[a ? "hoursView" : "minutesView"].find(".clockpicker-tick").each(function () {
            var t = n(this);
            t.toggleClass("active", c === +t.html())
        });
        o || !a && c % 5 ? (this.g.insertBefore(this.hand, this.bearing), this.g.insertBefore(this.bg, this.fg), this.bg.setAttribute("class", "clockpicker-canvas-bg clockpicker-canvas-bg-trans")) : (this.g.insertBefore(this.hand, this.bg), this.g.insertBefore(this.fg, this.bg), this.bg.setAttribute("class", "clockpicker-canvas-bg"));
        var f = Math.sin(r) * d, m = -Math.cos(r) * d;
        this.hand.setAttribute("x2", f), this.hand.setAttribute("y2", m), this.bg.setAttribute("cx", f), this.bg.setAttribute("cy", m), this.fg.setAttribute("cx", f), this.fg.setAttribute("cy", m)
    }, s.prototype.done = function () {
        o(this.options.beforeDone), this.hide();
        var t = this.input.prop("value"), e = i(this.hours)+":"+i(this.minutes);
        this.options.twelvehour && (e += this.amOrPm), this.input.prop("value", e), e !== t && (this.input.triggerHandler("change"), this.isInput || this.element.trigger("change")), this.options.autoclose && this.input.trigger("blur"), o(this.options.afterDone)
    }, s.prototype.remove = function () {
        this.element.removeData("clockpicker"), this.input.off("focus.clockpicker click.clockpicker"), this.addon.off("click.clockpicker"), this.isShown && this.hide(), this.isAppended && (r.off("resize.clockpicker"+this.id), this.popover.remove())
    }, n.fn.clockpicker = function (t) {
        var i = Array.prototype.slice.call(arguments, 1);
        return this.each(function () {
            var e = n(this), o = e.data("clockpicker");
            if (o) "function" == typeof o[t] && o[t].apply(o, i); else {
                var c = n.extend({}, s.DEFAULTS, e.data(), "object" == typeof t && t);
                e.data("clockpicker", new s(e, c))
            }
        })
    }
}();
/**
 * @version: 3.1
 * @author: Dan Grossman http://www.dangrossman.info/
 * @copyright: Copyright (c) 2012-2019 Dan Grossman. All rights reserved.
 * @license: Licensed under the MIT license. See http://www.opensource.org/licenses/mit-license.php
 * @website: http://www.daterangepicker.com/
 */
// Following the UMD template https://github.com/umdjs/umd/blob/master/templates/returnExportsGlobal.js
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Make globaly available as well
        define(['moment', 'jquery'], function (moment, jquery) {
            if (!jquery.fn) jquery.fn = {}; // webpack server rendering
            if (typeof moment !== 'function' && moment.hasOwnProperty('default')) moment = moment['default']
            return factory(moment, jquery);
        });
    } else if (typeof module === 'object' && module.exports) {
        // Node / Browserify
        //isomorphic issue
        var jQuery = (typeof window != 'undefined') ? window.jQuery : undefined;
        if (!jQuery) {
            jQuery = require('jquery');
            if (!jQuery.fn) jQuery.fn = {};
        }
        var moment = (typeof window != 'undefined' && typeof window.moment != 'undefined') ? window.moment : require('moment');
        module.exports = factory(moment, jQuery);
    } else {
        // Browser globals
        root.daterangepicker = factory(root.moment, root.jQuery);
    }
}(this, function (moment, $) {
    var DateRangePicker = function (element, options, cb) {

        //default settings for options
        this.parentEl = 'body';
        this.element = $(element);
        this.startDate = moment().startOf('day');
        this.endDate = moment().endOf('day');
        this.minDate = false;
        this.maxDate = false;
        this.maxSpan = false;
        this.autoApply = false;
        this.singleDatePicker = false;
        this.showDropdowns = false;
        this.minYear = moment().subtract(100, 'year').format('YYYY');
        this.maxYear = moment().add(100, 'year').format('YYYY');
        this.showWeekNumbers = false;
        this.showISOWeekNumbers = false;
        this.showCustomRangeLabel = true;
        this.timePicker = false;
        this.timePicker24Hour = false;
        this.timePickerIncrement = 1;
        this.timePickerSeconds = false;
        this.linkedCalendars = true;
        this.autoUpdateInput = true;
        this.alwaysShowCalendars = false;
        this.ranges = {};

        this.opens = 'right';
        if (this.element.hasClass('pull-right'))
            this.opens = 'left';

        this.drops = 'down';
        if (this.element.hasClass('dropup'))
            this.drops = 'up';

        this.buttonClasses = 'btn btn-sm';
        this.applyButtonClasses = 'btn-primary';
        this.cancelButtonClasses = 'btn-default';

        this.locale = {
            direction: 'ltr',
            format: moment.localeData().longDateFormat('L'),
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            weekLabel: 'W',
            customRangeLabel: 'Custom Range',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: moment.localeData().firstDayOfWeek()
        };

        this.callback = function () {
        };

        //some state information
        this.isShowing = false;
        this.leftCalendar = {};
        this.rightCalendar = {};

        //custom options from user
        if (typeof options !== 'object' || options === null)
            options = {};

        //allow setting options with data attributes
        //data-api options will be overwritten with custom javascript options
        options = $.extend(this.element.data(), options);

        //html template for the picker UI
        if (typeof options.template !== 'string' && !(options.template instanceof $))
            options.template =
                '<div class="daterangepicker">'+
                '<div class="ranges"></div>'+
                '<div class="drp-calendar left">'+
                '<div class="calendar-table"></div>'+
                '<div class="calendar-time"></div>'+
                '</div>'+
                '<div class="drp-calendar right">'+
                '<div class="calendar-table"></div>'+
                '<div class="calendar-time"></div>'+
                '</div>'+
                '<div class="drp-buttons">'+
                '<span class="drp-selected"></span>'+
                '<button class="cancelBtn" type="button"></button>'+
                '<button class="applyBtn" disabled="disabled" type="button"></button> '+
                '</div>'+
                '</div>';

        this.parentEl = (options.parentEl && $(options.parentEl).length) ? $(options.parentEl) : $(this.parentEl);
        this.container = $(options.template).appendTo(this.parentEl);

        //
        // handle all the possible options overriding defaults
        //

        if (typeof options.locale === 'object') {

            if (typeof options.locale.direction === 'string')
                this.locale.direction = options.locale.direction;

            if (typeof options.locale.format === 'string')
                this.locale.format = options.locale.format;

            if (typeof options.locale.separator === 'string')
                this.locale.separator = options.locale.separator;

            if (typeof options.locale.daysOfWeek === 'object')
                this.locale.daysOfWeek = options.locale.daysOfWeek.slice();

            if (typeof options.locale.monthNames === 'object')
                this.locale.monthNames = options.locale.monthNames.slice();

            if (typeof options.locale.firstDay === 'number')
                this.locale.firstDay = options.locale.firstDay;

            if (typeof options.locale.applyLabel === 'string')
                this.locale.applyLabel = options.locale.applyLabel;

            if (typeof options.locale.cancelLabel === 'string')
                this.locale.cancelLabel = options.locale.cancelLabel;

            if (typeof options.locale.weekLabel === 'string')
                this.locale.weekLabel = options.locale.weekLabel;

            if (typeof options.locale.customRangeLabel === 'string') {
                //Support unicode chars in the custom range name.
                var elem = document.createElement('textarea');
                elem.innerHTML = options.locale.customRangeLabel;
                var rangeHtml = elem.value;
                this.locale.customRangeLabel = rangeHtml;
            }
        }
        this.container.addClass(this.locale.direction);

        if (typeof options.startDate === 'string')
            this.startDate = moment(options.startDate, this.locale.format);

        if (typeof options.endDate === 'string')
            this.endDate = moment(options.endDate, this.locale.format);

        if (typeof options.minDate === 'string')
            this.minDate = moment(options.minDate, this.locale.format);

        if (typeof options.maxDate === 'string')
            this.maxDate = moment(options.maxDate, this.locale.format);

        if (typeof options.startDate === 'object')
            this.startDate = moment(options.startDate);

        if (typeof options.endDate === 'object')
            this.endDate = moment(options.endDate);

        if (typeof options.minDate === 'object')
            this.minDate = moment(options.minDate);

        if (typeof options.maxDate === 'object')
            this.maxDate = moment(options.maxDate);

        // sanity check for bad options
        if (this.minDate && this.startDate.isBefore(this.minDate))
            this.startDate = this.minDate.clone();

        // sanity check for bad options
        if (this.maxDate && this.endDate.isAfter(this.maxDate))
            this.endDate = this.maxDate.clone();

        if (typeof options.applyButtonClasses === 'string')
            this.applyButtonClasses = options.applyButtonClasses;

        if (typeof options.applyClass === 'string') //backwards compat
            this.applyButtonClasses = options.applyClass;

        if (typeof options.cancelButtonClasses === 'string')
            this.cancelButtonClasses = options.cancelButtonClasses;

        if (typeof options.cancelClass === 'string') //backwards compat
            this.cancelButtonClasses = options.cancelClass;

        if (typeof options.maxSpan === 'object')
            this.maxSpan = options.maxSpan;

        if (typeof options.dateLimit === 'object') //backwards compat
            this.maxSpan = options.dateLimit;

        if (typeof options.opens === 'string')
            this.opens = options.opens;

        if (typeof options.drops === 'string')
            this.drops = options.drops;

        if (typeof options.showWeekNumbers === 'boolean')
            this.showWeekNumbers = options.showWeekNumbers;

        if (typeof options.showISOWeekNumbers === 'boolean')
            this.showISOWeekNumbers = options.showISOWeekNumbers;

        if (typeof options.buttonClasses === 'string')
            this.buttonClasses = options.buttonClasses;

        if (typeof options.buttonClasses === 'object')
            this.buttonClasses = options.buttonClasses.join(' ');

        if (typeof options.showDropdowns === 'boolean')
            this.showDropdowns = options.showDropdowns;

        if (typeof options.minYear === 'number')
            this.minYear = options.minYear;

        if (typeof options.maxYear === 'number')
            this.maxYear = options.maxYear;

        if (typeof options.showCustomRangeLabel === 'boolean')
            this.showCustomRangeLabel = options.showCustomRangeLabel;

        if (typeof options.singleDatePicker === 'boolean') {
            this.singleDatePicker = options.singleDatePicker;
            if (this.singleDatePicker)
                this.endDate = this.startDate.clone();
        }

        if (typeof options.timePicker === 'boolean')
            this.timePicker = options.timePicker;

        if (typeof options.timePickerSeconds === 'boolean')
            this.timePickerSeconds = options.timePickerSeconds;

        if (typeof options.timePickerIncrement === 'number')
            this.timePickerIncrement = options.timePickerIncrement;

        if (typeof options.timePicker24Hour === 'boolean')
            this.timePicker24Hour = options.timePicker24Hour;

        if (typeof options.autoApply === 'boolean')
            this.autoApply = options.autoApply;

        if (typeof options.autoUpdateInput === 'boolean')
            this.autoUpdateInput = options.autoUpdateInput;

        if (typeof options.linkedCalendars === 'boolean')
            this.linkedCalendars = options.linkedCalendars;

        if (typeof options.isInvalidDate === 'function')
            this.isInvalidDate = options.isInvalidDate;

        if (typeof options.isCustomDate === 'function')
            this.isCustomDate = options.isCustomDate;

        if (typeof options.alwaysShowCalendars === 'boolean')
            this.alwaysShowCalendars = options.alwaysShowCalendars;

        // update day names order to firstDay
        if (this.locale.firstDay != 0) {
            var iterator = this.locale.firstDay;
            while (iterator > 0) {
                this.locale.daysOfWeek.push(this.locale.daysOfWeek.shift());
                iterator--;
            }
        }

        var start, end, range;

        //if no start/end dates set, check if an input element contains initial values
        if (typeof options.startDate === 'undefined' && typeof options.endDate === 'undefined') {
            if ($(this.element).is(':text')) {
                var val = $(this.element).val(),
                    split = val.split(this.locale.separator);

                start = end = null;

                if (split.length == 2) {
                    start = moment(split[0], this.locale.format);
                    end = moment(split[1], this.locale.format);
                } else if (this.singleDatePicker && val !== "") {
                    start = moment(val, this.locale.format);
                    end = moment(val, this.locale.format);
                }
                if (start !== null && end !== null) {
                    this.setStartDate(start);
                    this.setEndDate(end);
                }
            }
        }

        if (typeof options.ranges === 'object') {
            for (range in options.ranges) {

                if (typeof options.ranges[range][0] === 'string')
                    start = moment(options.ranges[range][0], this.locale.format);
                else
                    start = moment(options.ranges[range][0]);

                if (typeof options.ranges[range][1] === 'string')
                    end = moment(options.ranges[range][1], this.locale.format);
                else
                    end = moment(options.ranges[range][1]);

                // If the start or end date exceed those allowed by the minDate or maxSpan
                // options, shorten the range to the allowable period.
                if (this.minDate && start.isBefore(this.minDate))
                    start = this.minDate.clone();

                var maxDate = this.maxDate;
                if (this.maxSpan && maxDate && start.clone().add(this.maxSpan).isAfter(maxDate))
                    maxDate = start.clone().add(this.maxSpan);
                if (maxDate && end.isAfter(maxDate))
                    end = maxDate.clone();

                // If the end of the range is before the minimum or the start of the range is
                // after the maximum, don't display this range option at all.
                if ((this.minDate && end.isBefore(this.minDate, this.timepicker ? 'minute' : 'day'))
                    || (maxDate && start.isAfter(maxDate, this.timepicker ? 'minute' : 'day')))
                    continue;

                //Support unicode chars in the range names.
                var elem = document.createElement('textarea');
                elem.innerHTML = range;
                var rangeHtml = elem.value;

                this.ranges[rangeHtml] = [start, end];
            }

            var list = '<ul>';
            for (range in this.ranges) {
                list += '<li data-range-key="'+range+'">'+range+'</li>';
            }
            if (this.showCustomRangeLabel) {
                list += '<li data-range-key="'+this.locale.customRangeLabel+'">'+this.locale.customRangeLabel+'</li>';
            }
            list += '</ul>';
            this.container.find('.ranges').prepend(list);
        }

        if (typeof cb === 'function') {
            this.callback = cb;
        }

        if (!this.timePicker) {
            this.startDate = this.startDate.startOf('day');
            this.endDate = this.endDate.endOf('day');
            this.container.find('.calendar-time').hide();
        }

        //can't be used together for now
        if (this.timePicker && this.autoApply)
            this.autoApply = false;

        if (this.autoApply) {
            this.container.addClass('auto-apply');
        }

        if (typeof options.ranges === 'object')
            this.container.addClass('show-ranges');

        if (this.singleDatePicker) {
            this.container.addClass('single');
            this.container.find('.drp-calendar.left').addClass('single');
            this.container.find('.drp-calendar.left').show();
            this.container.find('.drp-calendar.right').hide();
            if (!this.timePicker && this.autoApply) {
                this.container.addClass('auto-apply');
            }
        }

        if ((typeof options.ranges === 'undefined' && !this.singleDatePicker) || this.alwaysShowCalendars) {
            this.container.addClass('show-calendar');
        }

        this.container.addClass('opens'+this.opens);

        //apply CSS classes and labels to buttons
        this.container.find('.applyBtn, .cancelBtn').addClass(this.buttonClasses);
        if (this.applyButtonClasses.length)
            this.container.find('.applyBtn').addClass(this.applyButtonClasses);
        if (this.cancelButtonClasses.length)
            this.container.find('.cancelBtn').addClass(this.cancelButtonClasses);
        this.container.find('.applyBtn').html(this.locale.applyLabel);
        this.container.find('.cancelBtn').html(this.locale.cancelLabel);

        //
        // event listeners
        //

        this.container.find('.drp-calendar')
            .on('click.daterangepicker', '.prev', $.proxy(this.clickPrev, this))
            .on('click.daterangepicker', '.next', $.proxy(this.clickNext, this))
            .on('mousedown.daterangepicker', 'td.available', $.proxy(this.clickDate, this))
            .on('mouseenter.daterangepicker', 'td.available', $.proxy(this.hoverDate, this))
            .on('change.daterangepicker', 'select.yearselect', $.proxy(this.monthOrYearChanged, this))
            .on('change.daterangepicker', 'select.monthselect', $.proxy(this.monthOrYearChanged, this))
            .on('change.daterangepicker', 'select.hourselect,select.minuteselect,select.secondselect,select.ampmselect', $.proxy(this.timeChanged, this));

        this.container.find('.ranges')
            .on('click.daterangepicker', 'li', $.proxy(this.clickRange, this));

        this.container.find('.drp-buttons')
            .on('click.daterangepicker', 'button.applyBtn', $.proxy(this.clickApply, this))
            .on('click.daterangepicker', 'button.cancelBtn', $.proxy(this.clickCancel, this));

        if (this.element.is('input') || this.element.is('button')) {
            this.element.on({
                'click.daterangepicker': $.proxy(this.show, this),
                'focus.daterangepicker': $.proxy(this.show, this),
                'keyup.daterangepicker': $.proxy(this.elementChanged, this),
                'keydown.daterangepicker': $.proxy(this.keydown, this) //IE 11 compatibility
            });
        } else {
            this.element.on('click.daterangepicker', $.proxy(this.toggle, this));
            this.element.on('keydown.daterangepicker', $.proxy(this.toggle, this));
        }

        //
        // if attached to a text input, set the initial value
        //

        this.updateElement();

    };

    DateRangePicker.prototype = {

        constructor: DateRangePicker,

        setStartDate: function (startDate) {
            if (typeof startDate === 'string')
                this.startDate = moment(startDate, this.locale.format);

            if (typeof startDate === 'object')
                this.startDate = moment(startDate);

            if (!this.timePicker)
                this.startDate = this.startDate.startOf('day');

            if (this.timePicker && this.timePickerIncrement)
                this.startDate.minute(Math.round(this.startDate.minute() / this.timePickerIncrement) * this.timePickerIncrement);

            if (this.minDate && this.startDate.isBefore(this.minDate)) {
                this.startDate = this.minDate.clone();
                if (this.timePicker && this.timePickerIncrement)
                    this.startDate.minute(Math.round(this.startDate.minute() / this.timePickerIncrement) * this.timePickerIncrement);
            }

            if (this.maxDate && this.startDate.isAfter(this.maxDate)) {
                this.startDate = this.maxDate.clone();
                if (this.timePicker && this.timePickerIncrement)
                    this.startDate.minute(Math.floor(this.startDate.minute() / this.timePickerIncrement) * this.timePickerIncrement);
            }

            if (!this.isShowing)
                this.updateElement();

            this.updateMonthsInView();
        },

        setEndDate: function (endDate) {
            if (typeof endDate === 'string')
                this.endDate = moment(endDate, this.locale.format);

            if (typeof endDate === 'object')
                this.endDate = moment(endDate);

            if (!this.timePicker)
                this.endDate = this.endDate.endOf('day');

            if (this.timePicker && this.timePickerIncrement)
                this.endDate.minute(Math.round(this.endDate.minute() / this.timePickerIncrement) * this.timePickerIncrement);

            if (this.endDate.isBefore(this.startDate))
                this.endDate = this.startDate.clone();

            if (this.maxDate && this.endDate.isAfter(this.maxDate))
                this.endDate = this.maxDate.clone();

            if (this.maxSpan && this.startDate.clone().add(this.maxSpan).isBefore(this.endDate))
                this.endDate = this.startDate.clone().add(this.maxSpan);

            this.previousRightTime = this.endDate.clone();

            this.container.find('.drp-selected').html(this.startDate.format(this.locale.format)+this.locale.separator+this.endDate.format(this.locale.format));

            if (!this.isShowing)
                this.updateElement();

            this.updateMonthsInView();
        },

        isInvalidDate: function () {
            return false;
        },

        isCustomDate: function () {
            return false;
        },

        updateView: function () {
            if (this.timePicker) {
                this.renderTimePicker('left');
                this.renderTimePicker('right');
                if (!this.endDate) {
                    this.container.find('.right .calendar-time select').prop('disabled', true).addClass('disabled');
                } else {
                    this.container.find('.right .calendar-time select').prop('disabled', false).removeClass('disabled');
                }
            }
            if (this.endDate)
                this.container.find('.drp-selected').html(this.startDate.format(this.locale.format)+this.locale.separator+this.endDate.format(this.locale.format));
            this.updateMonthsInView();
            this.updateCalendars();
            this.updateFormInputs();
        },

        updateMonthsInView: function () {
            if (this.endDate) {

                //if both dates are visible already, do nothing
                if (!this.singleDatePicker && this.leftCalendar.month && this.rightCalendar.month &&
                    (this.startDate.format('YYYY-MM') == this.leftCalendar.month.format('YYYY-MM') || this.startDate.format('YYYY-MM') == this.rightCalendar.month.format('YYYY-MM'))
                    &&
                    (this.endDate.format('YYYY-MM') == this.leftCalendar.month.format('YYYY-MM') || this.endDate.format('YYYY-MM') == this.rightCalendar.month.format('YYYY-MM'))
                ) {
                    return;
                }

                this.leftCalendar.month = this.startDate.clone().date(2);
                if (!this.linkedCalendars && (this.endDate.month() != this.startDate.month() || this.endDate.year() != this.startDate.year())) {
                    this.rightCalendar.month = this.endDate.clone().date(2);
                } else {
                    this.rightCalendar.month = this.startDate.clone().date(2).add(1, 'month');
                }

            } else {
                if (this.leftCalendar.month.format('YYYY-MM') != this.startDate.format('YYYY-MM') && this.rightCalendar.month.format('YYYY-MM') != this.startDate.format('YYYY-MM')) {
                    this.leftCalendar.month = this.startDate.clone().date(2);
                    this.rightCalendar.month = this.startDate.clone().date(2).add(1, 'month');
                }
            }
            if (this.maxDate && this.linkedCalendars && !this.singleDatePicker && this.rightCalendar.month > this.maxDate) {
                this.rightCalendar.month = this.maxDate.clone().date(2);
                this.leftCalendar.month = this.maxDate.clone().date(2).subtract(1, 'month');
            }
        },

        updateCalendars: function () {

            if (this.timePicker) {
                var hour, minute, second;
                if (this.endDate) {
                    hour = parseInt(this.container.find('.left .hourselect').val(), 10);
                    minute = parseInt(this.container.find('.left .minuteselect').val(), 10);
                    if (isNaN(minute)) {
                        minute = parseInt(this.container.find('.left .minuteselect option:last').val(), 10);
                    }
                    second = this.timePickerSeconds ? parseInt(this.container.find('.left .secondselect').val(), 10) : 0;
                    if (!this.timePicker24Hour) {
                        var ampm = this.container.find('.left .ampmselect').val();
                        if (ampm === 'PM' && hour < 12)
                            hour += 12;
                        if (ampm === 'AM' && hour === 12)
                            hour = 0;
                    }
                } else {
                    hour = parseInt(this.container.find('.right .hourselect').val(), 10);
                    minute = parseInt(this.container.find('.right .minuteselect').val(), 10);
                    if (isNaN(minute)) {
                        minute = parseInt(this.container.find('.right .minuteselect option:last').val(), 10);
                    }
                    second = this.timePickerSeconds ? parseInt(this.container.find('.right .secondselect').val(), 10) : 0;
                    if (!this.timePicker24Hour) {
                        var ampm = this.container.find('.right .ampmselect').val();
                        if (ampm === 'PM' && hour < 12)
                            hour += 12;
                        if (ampm === 'AM' && hour === 12)
                            hour = 0;
                    }
                }
                this.leftCalendar.month.hour(hour).minute(minute).second(second);
                this.rightCalendar.month.hour(hour).minute(minute).second(second);
            }

            this.renderCalendar('left');
            this.renderCalendar('right');

            //highlight any predefined range matching the current start and end dates
            this.container.find('.ranges li').removeClass('active');
            if (this.endDate == null) return;

            this.calculateChosenLabel();
        },

        renderCalendar: function (side) {

            //
            // Build the matrix of dates that will populate the calendar
            //

            var calendar = side == 'left' ? this.leftCalendar : this.rightCalendar;
            var month = calendar.month.month();
            var year = calendar.month.year();
            var hour = calendar.month.hour();
            var minute = calendar.month.minute();
            var second = calendar.month.second();
            var daysInMonth = moment([year, month]).daysInMonth();
            var firstDay = moment([year, month, 1]);
            var lastDay = moment([year, month, daysInMonth]);
            var lastMonth = moment(firstDay).subtract(1, 'month').month();
            var lastYear = moment(firstDay).subtract(1, 'month').year();
            var daysInLastMonth = moment([lastYear, lastMonth]).daysInMonth();
            var dayOfWeek = firstDay.day();

            //initialize a 6 rows x 7 columns array for the calendar
            var calendar = [];
            calendar.firstDay = firstDay;
            calendar.lastDay = lastDay;

            for (var i = 0; i < 6; i++) {
                calendar[i] = [];
            }

            //populate the calendar with date objects
            var startDay = daysInLastMonth-dayOfWeek+this.locale.firstDay+1;
            if (startDay > daysInLastMonth)
                startDay -= 7;

            if (dayOfWeek == this.locale.firstDay)
                startDay = daysInLastMonth-6;

            var curDate = moment([lastYear, lastMonth, startDay, 12, minute, second]);

            var col, row;
            for (var i = 0, col = 0, row = 0; i < 42; i++, col++, curDate = moment(curDate).add(24, 'hour')) {
                if (i > 0 && col % 7 === 0) {
                    col = 0;
                    row++;
                }
                calendar[row][col] = curDate.clone().hour(hour).minute(minute).second(second);
                curDate.hour(12);

                if (this.minDate && calendar[row][col].format('YYYY-MM-DD') == this.minDate.format('YYYY-MM-DD') && calendar[row][col].isBefore(this.minDate) && side == 'left') {
                    calendar[row][col] = this.minDate.clone();
                }

                if (this.maxDate && calendar[row][col].format('YYYY-MM-DD') == this.maxDate.format('YYYY-MM-DD') && calendar[row][col].isAfter(this.maxDate) && side == 'right') {
                    calendar[row][col] = this.maxDate.clone();
                }

            }

            //make the calendar object available to hoverDate/clickDate
            if (side == 'left') {
                this.leftCalendar.calendar = calendar;
            } else {
                this.rightCalendar.calendar = calendar;
            }

            //
            // Display the calendar
            //

            var minDate = side == 'left' ? this.minDate : this.startDate;
            var maxDate = this.maxDate;
            var selected = side == 'left' ? this.startDate : this.endDate;
            var arrow = this.locale.direction == 'ltr' ? {
                left: 'chevron-left',
                right: 'chevron-right'
            } : {left: 'chevron-right', right: 'chevron-left'};

            var html = '<table class="table-condensed">';
            html += '<thead>';
            html += '<tr>';

            // add empty cell for week number
            if (this.showWeekNumbers || this.showISOWeekNumbers)
                html += '<th></th>';

            if ((!minDate || minDate.isBefore(calendar.firstDay)) && (!this.linkedCalendars || side == 'left')) {
                html += '<th class="prev available"><span></span></th>';
            } else {
                html += '<th></th>';
            }

            var dateHtml = this.locale.monthNames[calendar[1][1].month()]+calendar[1][1].format(" YYYY");

            if (this.showDropdowns) {
                var currentMonth = calendar[1][1].month();
                var currentYear = calendar[1][1].year();
                var maxYear = (maxDate && maxDate.year()) || (this.maxYear);
                var minYear = (minDate && minDate.year()) || (this.minYear);
                var inMinYear = currentYear == minYear;
                var inMaxYear = currentYear == maxYear;

                var monthHtml = '<select class="monthselect">';
                for (var m = 0; m < 12; m++) {
                    if ((!inMinYear || (minDate && m >= minDate.month())) && (!inMaxYear || (maxDate && m <= maxDate.month()))) {
                        monthHtml += "<option value='"+m+"'"+
                            (m === currentMonth ? " selected='selected'" : "")+
                            ">"+this.locale.monthNames[m]+"</option>";
                    } else {
                        monthHtml += "<option value='"+m+"'"+
                            (m === currentMonth ? " selected='selected'" : "")+
                            " disabled='disabled'>"+this.locale.monthNames[m]+"</option>";
                    }
                }
                monthHtml += "</select>";

                var yearHtml = '<select class="yearselect">';
                for (var y = minYear; y <= maxYear; y++) {
                    yearHtml += '<option value="'+y+'"'+
                        (y === currentYear ? ' selected="selected"' : '')+
                        '>'+y+'</option>';
                }
                yearHtml += '</select>';

                dateHtml = monthHtml+yearHtml;
            }

            html += '<th colspan="5" class="month">'+dateHtml+'</th>';
            if ((!maxDate || maxDate.isAfter(calendar.lastDay)) && (!this.linkedCalendars || side == 'right' || this.singleDatePicker)) {
                html += '<th class="next available"><span></span></th>';
            } else {
                html += '<th></th>';
            }

            html += '</tr>';
            html += '<tr>';

            // add week number label
            if (this.showWeekNumbers || this.showISOWeekNumbers)
                html += '<th class="week">'+this.locale.weekLabel+'</th>';

            $.each(this.locale.daysOfWeek, function (index, dayOfWeek) {
                html += '<th>'+dayOfWeek+'</th>';
            });

            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            //adjust maxDate to reflect the maxSpan setting in order to
            //grey out end dates beyond the maxSpan
            if (this.endDate == null && this.maxSpan) {
                var maxLimit = this.startDate.clone().add(this.maxSpan).endOf('day');
                if (!maxDate || maxLimit.isBefore(maxDate)) {
                    maxDate = maxLimit;
                }
            }

            for (var row = 0; row < 6; row++) {
                html += '<tr>';

                // add week number
                if (this.showWeekNumbers)
                    html += '<td class="week">'+calendar[row][0].week()+'</td>';
                else if (this.showISOWeekNumbers)
                    html += '<td class="week">'+calendar[row][0].isoWeek()+'</td>';

                for (var col = 0; col < 7; col++) {

                    var classes = [];

                    //highlight today's date
                    if (calendar[row][col].isSame(new Date(), "day"))
                        classes.push('today');

                    //highlight weekends
                    if (calendar[row][col].isoWeekday() > 5)
                        classes.push('weekend');

                    //grey out the dates in other months displayed at beginning and end of this calendar
                    if (calendar[row][col].month() != calendar[1][1].month())
                        classes.push('off', 'ends');

                    //don't allow selection of dates before the minimum date
                    if (this.minDate && calendar[row][col].isBefore(this.minDate, 'day'))
                        classes.push('off', 'disabled');

                    //don't allow selection of dates after the maximum date
                    if (maxDate && calendar[row][col].isAfter(maxDate, 'day'))
                        classes.push('off', 'disabled');

                    //don't allow selection of date if a custom function decides it's invalid
                    if (this.isInvalidDate(calendar[row][col]))
                        classes.push('off', 'disabled');

                    //highlight the currently selected start date
                    if (calendar[row][col].format('YYYY-MM-DD') == this.startDate.format('YYYY-MM-DD'))
                        classes.push('active', 'start-date');

                    //highlight the currently selected end date
                    if (this.endDate != null && calendar[row][col].format('YYYY-MM-DD') == this.endDate.format('YYYY-MM-DD'))
                        classes.push('active', 'end-date');

                    //highlight dates in-between the selected dates
                    if (this.endDate != null && calendar[row][col] > this.startDate && calendar[row][col] < this.endDate)
                        classes.push('in-range');

                    //apply custom classes for this date
                    var isCustom = this.isCustomDate(calendar[row][col]);
                    if (isCustom !== false) {
                        if (typeof isCustom === 'string')
                            classes.push(isCustom);
                        else
                            Array.prototype.push.apply(classes, isCustom);
                    }

                    var cname = '', disabled = false;
                    for (var i = 0; i < classes.length; i++) {
                        cname += classes[i]+' ';
                        if (classes[i] == 'disabled')
                            disabled = true;
                    }
                    if (!disabled)
                        cname += 'available';

                    html += '<td class="'+cname.replace(/^\s+|\s+$/g, '')+'" data-title="'+'r'+row+'c'+col+'">'+calendar[row][col].date()+'</td>';

                }
                html += '</tr>';
            }

            html += '</tbody>';
            html += '</table>';

            this.container.find('.drp-calendar.'+side+' .calendar-table').html(html);

        },

        renderTimePicker: function (side) {

            // Don't bother updating the time picker if it's currently disabled
            // because an end date hasn't been clicked yet
            if (side == 'right' && !this.endDate) return;

            var html, selected, minDate, maxDate = this.maxDate;

            if (this.maxSpan && (!this.maxDate || this.startDate.clone().add(this.maxSpan).isBefore(this.maxDate)))
                maxDate = this.startDate.clone().add(this.maxSpan);

            if (side == 'left') {
                selected = this.startDate.clone();
                minDate = this.minDate;
            } else if (side == 'right') {
                selected = this.endDate.clone();
                minDate = this.startDate;

                //Preserve the time already selected
                var timeSelector = this.container.find('.drp-calendar.right .calendar-time');
                if (timeSelector.html() != '') {

                    selected.hour(!isNaN(selected.hour()) ? selected.hour() : timeSelector.find('.hourselect option:selected').val());
                    selected.minute(!isNaN(selected.minute()) ? selected.minute() : timeSelector.find('.minuteselect option:selected').val());
                    selected.second(!isNaN(selected.second()) ? selected.second() : timeSelector.find('.secondselect option:selected').val());

                    if (!this.timePicker24Hour) {
                        var ampm = timeSelector.find('.ampmselect option:selected').val();
                        if (ampm === 'PM' && selected.hour() < 12)
                            selected.hour(selected.hour()+12);
                        if (ampm === 'AM' && selected.hour() === 12)
                            selected.hour(0);
                    }

                }

                if (selected.isBefore(this.startDate))
                    selected = this.startDate.clone();

                if (maxDate && selected.isAfter(maxDate))
                    selected = maxDate.clone();

            }

            //
            // hours
            //

            html = '<select class="hourselect">';

            var start = this.timePicker24Hour ? 0 : 1;
            var end = this.timePicker24Hour ? 23 : 12;

            for (var i = start; i <= end; i++) {
                var i_in_24 = i;
                if (!this.timePicker24Hour)
                    i_in_24 = selected.hour() >= 12 ? (i == 12 ? 12 : i+12) : (i == 12 ? 0 : i);

                var time = selected.clone().hour(i_in_24);
                var disabled = false;
                if (minDate && time.minute(59).isBefore(minDate))
                    disabled = true;
                if (maxDate && time.minute(0).isAfter(maxDate))
                    disabled = true;

                if (i_in_24 == selected.hour() && !disabled) {
                    html += '<option value="'+i+'" selected="selected">'+i+'</option>';
                } else if (disabled) {
                    html += '<option value="'+i+'" disabled="disabled" class="disabled">'+i+'</option>';
                } else {
                    html += '<option value="'+i+'">'+i+'</option>';
                }
            }

            html += '</select> ';

            //
            // minutes
            //

            html += ': <select class="minuteselect">';

            for (var i = 0; i < 60; i += this.timePickerIncrement) {
                var padded = i < 10 ? '0'+i : i;
                var time = selected.clone().minute(i);

                var disabled = false;
                if (minDate && time.second(59).isBefore(minDate))
                    disabled = true;
                if (maxDate && time.second(0).isAfter(maxDate))
                    disabled = true;

                if (selected.minute() == i && !disabled) {
                    html += '<option value="'+i+'" selected="selected">'+padded+'</option>';
                } else if (disabled) {
                    html += '<option value="'+i+'" disabled="disabled" class="disabled">'+padded+'</option>';
                } else {
                    html += '<option value="'+i+'">'+padded+'</option>';
                }
            }

            html += '</select> ';

            //
            // seconds
            //

            if (this.timePickerSeconds) {
                html += ': <select class="secondselect">';

                for (var i = 0; i < 60; i++) {
                    var padded = i < 10 ? '0'+i : i;
                    var time = selected.clone().second(i);

                    var disabled = false;
                    if (minDate && time.isBefore(minDate))
                        disabled = true;
                    if (maxDate && time.isAfter(maxDate))
                        disabled = true;

                    if (selected.second() == i && !disabled) {
                        html += '<option value="'+i+'" selected="selected">'+padded+'</option>';
                    } else if (disabled) {
                        html += '<option value="'+i+'" disabled="disabled" class="disabled">'+padded+'</option>';
                    } else {
                        html += '<option value="'+i+'">'+padded+'</option>';
                    }
                }

                html += '</select> ';
            }

            //
            // AM/PM
            //

            if (!this.timePicker24Hour) {
                html += '<select class="ampmselect">';

                var am_html = '';
                var pm_html = '';

                if (minDate && selected.clone().hour(12).minute(0).second(0).isBefore(minDate))
                    am_html = ' disabled="disabled" class="disabled"';

                if (maxDate && selected.clone().hour(0).minute(0).second(0).isAfter(maxDate))
                    pm_html = ' disabled="disabled" class="disabled"';

                if (selected.hour() >= 12) {
                    html += '<option value="AM"'+am_html+'>AM</option><option value="PM" selected="selected"'+pm_html+'>PM</option>';
                } else {
                    html += '<option value="AM" selected="selected"'+am_html+'>AM</option><option value="PM"'+pm_html+'>PM</option>';
                }

                html += '</select>';
            }

            this.container.find('.drp-calendar.'+side+' .calendar-time').html(html);

        },

        updateFormInputs: function () {

            if (this.singleDatePicker || (this.endDate && (this.startDate.isBefore(this.endDate) || this.startDate.isSame(this.endDate)))) {
                this.container.find('button.applyBtn').prop('disabled', false);
            } else {
                this.container.find('button.applyBtn').prop('disabled', true);
            }

        },

        move: function () {
            var parentOffset = {top: 0, left: 0},
                containerTop,
                drops = this.drops;

            var parentRightEdge = $(window).width();
            if (!this.parentEl.is('body')) {
                parentOffset = {
                    top: this.parentEl.offset().top-this.parentEl.scrollTop(),
                    left: this.parentEl.offset().left-this.parentEl.scrollLeft()
                };
                parentRightEdge = this.parentEl[0].clientWidth+this.parentEl.offset().left;
            }

            switch (drops) {
                case 'auto':
                    containerTop = this.element.offset().top+this.element.outerHeight()-parentOffset.top;
                    if (containerTop+this.container.outerHeight() >= this.parentEl[0].scrollHeight) {
                        containerTop = this.element.offset().top-this.container.outerHeight()-parentOffset.top;
                        drops = 'up';
                    }
                    break;
                case 'up':
                    containerTop = this.element.offset().top-this.container.outerHeight()-parentOffset.top;
                    break;
                default:
                    containerTop = this.element.offset().top+this.element.outerHeight()-parentOffset.top;
                    break;
            }

            // Force the container to it's actual width
            this.container.css({
                top: 0,
                left: 0,
                right: 'auto'
            });
            var containerWidth = this.container.outerWidth();

            this.container.toggleClass('drop-up', drops == 'up');

            if (this.opens == 'left') {
                var containerRight = parentRightEdge-this.element.offset().left-this.element.outerWidth();
                if (containerWidth+containerRight > $(window).width()) {
                    this.container.css({
                        top: containerTop,
                        right: 'auto',
                        left: 9
                    });
                } else {
                    this.container.css({
                        top: containerTop,
                        right: containerRight,
                        left: 'auto'
                    });
                }
            } else if (this.opens == 'center') {
                var containerLeft = this.element.offset().left-parentOffset.left+this.element.outerWidth() / 2
                    -containerWidth / 2;
                if (containerLeft < 0) {
                    this.container.css({
                        top: containerTop,
                        right: 'auto',
                        left: 9
                    });
                } else if (containerLeft+containerWidth > $(window).width()) {
                    this.container.css({
                        top: containerTop,
                        left: 'auto',
                        right: 0
                    });
                } else {
                    this.container.css({
                        top: containerTop,
                        left: containerLeft,
                        right: 'auto'
                    });
                }
            } else {
                var containerLeft = this.element.offset().left-parentOffset.left;
                if (containerLeft+containerWidth > $(window).width()) {
                    this.container.css({
                        top: containerTop,
                        left: 'auto',
                        right: 0
                    });
                } else {
                    this.container.css({
                        top: containerTop,
                        left: containerLeft,
                        right: 'auto'
                    });
                }
            }
        },

        show: function (e) {
            if (this.isShowing) return;

            // Create a click proxy that is private to this instance of datepicker, for unbinding
            this._outsideClickProxy = $.proxy(function (e) {
                this.outsideClick(e);
            }, this);

            // Bind global datepicker mousedown for hiding and
            $(document)
                .on('mousedown.daterangepicker', this._outsideClickProxy)
                // also support mobile devices
                .on('touchend.daterangepicker', this._outsideClickProxy)
                // also explicitly play nice with Bootstrap dropdowns, which stopPropagation when clicking them
                .on('click.daterangepicker', '[data-toggle=dropdown]', this._outsideClickProxy)
                // and also close when focus changes to outside the picker (eg. tabbing between controls)
                .on('focusin.daterangepicker', this._outsideClickProxy);

            // Reposition the picker if the window is resized while it's open
            $(window).on('resize.daterangepicker', $.proxy(function (e) {
                this.move(e);
            }, this));

            this.oldStartDate = this.startDate.clone();
            this.oldEndDate = this.endDate.clone();
            this.previousRightTime = this.endDate.clone();

            this.updateView();
            this.container.show();
            this.move();
            this.element.trigger('show.daterangepicker', this);
            this.isShowing = true;
        },

        hide: function (e) {
            if (!this.isShowing) return;

            //incomplete date selection, revert to last values
            if (!this.endDate) {
                this.startDate = this.oldStartDate.clone();
                this.endDate = this.oldEndDate.clone();
            }

            //if a new date range was selected, invoke the user callback function
            if (!this.startDate.isSame(this.oldStartDate) || !this.endDate.isSame(this.oldEndDate))
                this.callback(this.startDate.clone(), this.endDate.clone(), this.chosenLabel);

            //if picker is attached to a text input, update it
            this.updateElement();

            $(document).off('.daterangepicker');
            $(window).off('.daterangepicker');
            this.container.hide();
            this.element.trigger('hide.daterangepicker', this);
            this.isShowing = false;
        },

        toggle: function (e) {
            if (this.isShowing) {
                this.hide();
            } else {
                this.show();
            }
        },

        outsideClick: function (e) {
            var target = $(e.target);
            // if the page is clicked anywhere except within the daterangerpicker/button
            // itself then call this.hide()
            if (
                // ie modal dialog fix
                e.type == "focusin" ||
                target.closest(this.element).length ||
                target.closest(this.container).length ||
                target.closest('.calendar-table').length
            ) return;
            this.hide();
            this.element.trigger('outsideClick.daterangepicker', this);
        },

        showCalendars: function () {
            this.container.addClass('show-calendar');
            this.move();
            this.element.trigger('showCalendar.daterangepicker', this);
        },

        hideCalendars: function () {
            this.container.removeClass('show-calendar');
            this.element.trigger('hideCalendar.daterangepicker', this);
        },

        clickRange: function (e) {
            var label = e.target.getAttribute('data-range-key');
            this.chosenLabel = label;
            if (label == this.locale.customRangeLabel) {
                this.showCalendars();
            } else {
                var dates = this.ranges[label];
                this.startDate = dates[0];
                this.endDate = dates[1];

                if (!this.timePicker) {
                    this.startDate.startOf('day');
                    this.endDate.endOf('day');
                }

                if (!this.alwaysShowCalendars)
                    this.hideCalendars();
                this.clickApply();
            }
        },

        clickPrev: function (e) {
            var cal = $(e.target).parents('.drp-calendar');
            if (cal.hasClass('left')) {
                this.leftCalendar.month.subtract(1, 'month');
                if (this.linkedCalendars)
                    this.rightCalendar.month.subtract(1, 'month');
            } else {
                this.rightCalendar.month.subtract(1, 'month');
            }
            this.updateCalendars();
        },

        clickNext: function (e) {
            var cal = $(e.target).parents('.drp-calendar');
            if (cal.hasClass('left')) {
                this.leftCalendar.month.add(1, 'month');
            } else {
                this.rightCalendar.month.add(1, 'month');
                if (this.linkedCalendars)
                    this.leftCalendar.month.add(1, 'month');
            }
            this.updateCalendars();
        },

        hoverDate: function (e) {

            //ignore dates that can't be selected
            if (!$(e.target).hasClass('available')) return;

            var title = $(e.target).attr('data-title');
            var row = title.substr(1, 1);
            var col = title.substr(3, 1);
            var cal = $(e.target).parents('.drp-calendar');
            var date = cal.hasClass('left') ? this.leftCalendar.calendar[row][col] : this.rightCalendar.calendar[row][col];

            //highlight the dates between the start date and the date being hovered as a potential end date
            var leftCalendar = this.leftCalendar;
            var rightCalendar = this.rightCalendar;
            var startDate = this.startDate;
            if (!this.endDate) {
                this.container.find('.drp-calendar tbody td').each(function (index, el) {

                    //skip week numbers, only look at dates
                    if ($(el).hasClass('week')) return;

                    var title = $(el).attr('data-title');
                    var row = title.substr(1, 1);
                    var col = title.substr(3, 1);
                    var cal = $(el).parents('.drp-calendar');
                    var dt = cal.hasClass('left') ? leftCalendar.calendar[row][col] : rightCalendar.calendar[row][col];

                    if ((dt.isAfter(startDate) && dt.isBefore(date)) || dt.isSame(date, 'day')) {
                        $(el).addClass('in-range');
                    } else {
                        $(el).removeClass('in-range');
                    }

                });
            }

        },

        clickDate: function (e) {

            if (!$(e.target).hasClass('available')) return;

            var title = $(e.target).attr('data-title');
            var row = title.substr(1, 1);
            var col = title.substr(3, 1);
            var cal = $(e.target).parents('.drp-calendar');
            var date = cal.hasClass('left') ? this.leftCalendar.calendar[row][col] : this.rightCalendar.calendar[row][col];

            //
            // this function needs to do a few things:
            // * alternate between selecting a start and end date for the range,
            // * if the time picker is enabled, apply the hour/minute/second from the select boxes to the clicked date
            // * if autoapply is enabled, and an end date was chosen, apply the selection
            // * if single date picker mode, and time picker isn't enabled, apply the selection immediately
            // * if one of the inputs above the calendars was focused, cancel that manual input
            //

            if (this.endDate || date.isBefore(this.startDate, 'day')) { //picking start
                if (this.timePicker) {
                    var hour = parseInt(this.container.find('.left .hourselect').val(), 10);
                    if (!this.timePicker24Hour) {
                        var ampm = this.container.find('.left .ampmselect').val();
                        if (ampm === 'PM' && hour < 12)
                            hour += 12;
                        if (ampm === 'AM' && hour === 12)
                            hour = 0;
                    }
                    var minute = parseInt(this.container.find('.left .minuteselect').val(), 10);
                    if (isNaN(minute)) {
                        minute = parseInt(this.container.find('.left .minuteselect option:last').val(), 10);
                    }
                    var second = this.timePickerSeconds ? parseInt(this.container.find('.left .secondselect').val(), 10) : 0;
                    date = date.clone().hour(hour).minute(minute).second(second);
                }
                this.endDate = null;
                this.setStartDate(date.clone());
            } else if (!this.endDate && date.isBefore(this.startDate)) {
                //special case: clicking the same date for start/end,
                //but the time of the end date is before the start date
                this.setEndDate(this.startDate.clone());
            } else { // picking end
                if (this.timePicker) {
                    var hour = parseInt(this.container.find('.right .hourselect').val(), 10);
                    if (!this.timePicker24Hour) {
                        var ampm = this.container.find('.right .ampmselect').val();
                        if (ampm === 'PM' && hour < 12)
                            hour += 12;
                        if (ampm === 'AM' && hour === 12)
                            hour = 0;
                    }
                    var minute = parseInt(this.container.find('.right .minuteselect').val(), 10);
                    if (isNaN(minute)) {
                        minute = parseInt(this.container.find('.right .minuteselect option:last').val(), 10);
                    }
                    var second = this.timePickerSeconds ? parseInt(this.container.find('.right .secondselect').val(), 10) : 0;
                    date = date.clone().hour(hour).minute(minute).second(second);
                }
                this.setEndDate(date.clone());
                if (this.autoApply) {
                    this.calculateChosenLabel();
                    this.clickApply();
                }
            }

            if (this.singleDatePicker) {
                this.setEndDate(this.startDate);
                if (!this.timePicker && this.autoApply)
                    this.clickApply();
            }

            this.updateView();

            //This is to cancel the blur event handler if the mouse was in one of the inputs
            e.stopPropagation();

        },

        calculateChosenLabel: function () {
            var customRange = true;
            var i = 0;
            for (var range in this.ranges) {
                if (this.timePicker) {
                    var format = this.timePickerSeconds ? "YYYY-MM-DD HH:mm:ss" : "YYYY-MM-DD HH:mm";
                    //ignore times when comparing dates if time picker seconds is not enabled
                    if (this.startDate.format(format) == this.ranges[range][0].format(format) && this.endDate.format(format) == this.ranges[range][1].format(format)) {
                        customRange = false;
                        this.chosenLabel = this.container.find('.ranges li:eq('+i+')').addClass('active').attr('data-range-key');
                        break;
                    }
                } else {
                    //ignore times when comparing dates if time picker is not enabled
                    if (this.startDate.format('YYYY-MM-DD') == this.ranges[range][0].format('YYYY-MM-DD') && this.endDate.format('YYYY-MM-DD') == this.ranges[range][1].format('YYYY-MM-DD')) {
                        customRange = false;
                        this.chosenLabel = this.container.find('.ranges li:eq('+i+')').addClass('active').attr('data-range-key');
                        break;
                    }
                }
                i++;
            }
            if (customRange) {
                if (this.showCustomRangeLabel) {
                    this.chosenLabel = this.container.find('.ranges li:last').addClass('active').attr('data-range-key');
                } else {
                    this.chosenLabel = null;
                }
                this.showCalendars();
            }
        },

        clickApply: function (e) {
            this.hide();
            this.element.trigger('apply.daterangepicker', this);
        },

        clickCancel: function (e) {
            this.startDate = this.oldStartDate;
            this.endDate = this.oldEndDate;
            this.hide();
            this.element.trigger('cancel.daterangepicker', this);
        },

        monthOrYearChanged: function (e) {
            var isLeft = $(e.target).closest('.drp-calendar').hasClass('left'),
                leftOrRight = isLeft ? 'left' : 'right',
                cal = this.container.find('.drp-calendar.'+leftOrRight);

            // Month must be Number for new moment versions
            var month = parseInt(cal.find('.monthselect').val(), 10);
            var year = cal.find('.yearselect').val();

            if (!isLeft) {
                if (year < this.startDate.year() || (year == this.startDate.year() && month < this.startDate.month())) {
                    month = this.startDate.month();
                    year = this.startDate.year();
                }
            }

            if (this.minDate) {
                if (year < this.minDate.year() || (year == this.minDate.year() && month < this.minDate.month())) {
                    month = this.minDate.month();
                    year = this.minDate.year();
                }
            }

            if (this.maxDate) {
                if (year > this.maxDate.year() || (year == this.maxDate.year() && month > this.maxDate.month())) {
                    month = this.maxDate.month();
                    year = this.maxDate.year();
                }
            }

            if (isLeft) {
                this.leftCalendar.month.month(month).year(year);
                if (this.linkedCalendars)
                    this.rightCalendar.month = this.leftCalendar.month.clone().add(1, 'month');
            } else {
                this.rightCalendar.month.month(month).year(year);
                if (this.linkedCalendars)
                    this.leftCalendar.month = this.rightCalendar.month.clone().subtract(1, 'month');
            }
            this.updateCalendars();
        },

        timeChanged: function (e) {

            var cal = $(e.target).closest('.drp-calendar'),
                isLeft = cal.hasClass('left');

            var hour = parseInt(cal.find('.hourselect').val(), 10);
            var minute = parseInt(cal.find('.minuteselect').val(), 10);
            if (isNaN(minute)) {
                minute = parseInt(cal.find('.minuteselect option:last').val(), 10);
            }
            var second = this.timePickerSeconds ? parseInt(cal.find('.secondselect').val(), 10) : 0;

            if (!this.timePicker24Hour) {
                var ampm = cal.find('.ampmselect').val();
                if (ampm === 'PM' && hour < 12)
                    hour += 12;
                if (ampm === 'AM' && hour === 12)
                    hour = 0;
            }

            if (isLeft) {
                var start = this.startDate.clone();
                start.hour(hour);
                start.minute(minute);
                start.second(second);
                this.setStartDate(start);
                if (this.singleDatePicker) {
                    this.endDate = this.startDate.clone();
                } else if (this.endDate && this.endDate.format('YYYY-MM-DD') == start.format('YYYY-MM-DD') && this.endDate.isBefore(start)) {
                    this.setEndDate(start.clone());
                }
            } else if (this.endDate) {
                var end = this.endDate.clone();
                end.hour(hour);
                end.minute(minute);
                end.second(second);
                this.setEndDate(end);
            }

            //update the calendars so all clickable dates reflect the new time component
            this.updateCalendars();

            //update the form inputs above the calendars with the new time
            this.updateFormInputs();

            //re-render the time pickers because changing one selection can affect what's enabled in another
            this.renderTimePicker('left');
            this.renderTimePicker('right');

        },

        elementChanged: function () {
            if (!this.element.is('input')) return;
            if (!this.element.val().length) return;

            var dateString = this.element.val().split(this.locale.separator),
                start = null,
                end = null;

            if (dateString.length === 2) {
                start = moment(dateString[0], this.locale.format);
                end = moment(dateString[1], this.locale.format);
            }

            if (this.singleDatePicker || start === null || end === null) {
                start = moment(this.element.val(), this.locale.format);
                end = start;
            }

            if (!start.isValid() || !end.isValid()) return;

            this.setStartDate(start);
            this.setEndDate(end);
            this.updateView();
        },

        keydown: function (e) {
            //hide on tab or enter
            if ((e.keyCode === 9) || (e.keyCode === 13)) {
                this.hide();
            }

            //hide on esc and prevent propagation
            if (e.keyCode === 27) {
                e.preventDefault();
                e.stopPropagation();

                this.hide();
            }
        },

        updateElement: function () {
            if (this.element.is('input') && this.autoUpdateInput) {
                var newValue = this.startDate.format(this.locale.format);
                if (!this.singleDatePicker) {
                    newValue += this.locale.separator+this.endDate.format(this.locale.format);
                }
                if (newValue !== this.element.val()) {
                    this.element.val(newValue).trigger('change');
                }
            }
        },

        remove: function () {
            this.container.remove();
            this.element.off('.daterangepicker');
            this.element.removeData();
        }

    };

    $.fn.daterangepicker = function (options, callback) {
        var implementOptions = $.extend(true, {}, $.fn.daterangepicker.defaultOptions, options);
        this.each(function () {
            var el = $(this);
            if (el.data('daterangepicker'))
                el.data('daterangepicker').remove();
            el.data('daterangepicker', new DateRangePicker(el, implementOptions, callback));
        });
        return this;
    };

    return DateRangePicker;

}));

/*!@preserve
 * Tempus Dominus Bootstrap4 v5.39.0 (https://tempusdominus.github.io/bootstrap-4/)
 * Copyright 2016-2020 Jonathan Peterson and contributors
 * Licensed under MIT (https://github.com/tempusdominus/bootstrap-3/blob/master/LICENSE)
 */
if ("undefined" == typeof jQuery) throw new Error("Tempus Dominus Bootstrap4's requires jQuery. jQuery must be included before Tempus Dominus Bootstrap4's JavaScript.");
if (!function () {
    var t = jQuery.fn.jquery.split(" ")[0].split(".");
    if (t[0] < 2 && t[1] < 9 || 1 === t[0] && 9 === t[1] && t[2] < 1 || 4 <= t[0]) throw new Error("Tempus Dominus Bootstrap4's requires at least jQuery v3.0.0 but less than v4.0.0")
}(), "undefined" == typeof moment) throw new Error("Tempus Dominus Bootstrap4's requires moment.js. Moment.js must be included before Tempus Dominus Bootstrap4's JavaScript.");
var version = moment.version.split(".");
if (version[0] <= 2 && version[1] < 17 || 3 <= version[0]) throw new Error("Tempus Dominus Bootstrap4's requires at least moment.js v2.17.0 but less than v3.0.0");
!function () {
    function s(t, e) {
        for (var i = 0; i < e.length; i++) {
            var s = e[i];
            s.enumerable = s.enumerable || !1, s.configurable = !0, "value" in s && (s.writable = !0), Object.defineProperty(t, s.key, s)
        }
    }

    var a, n, o, r, d, h, l, c, u, _, f, m, w, g, i, b, v,
        M = (a = jQuery, n = moment, l = {DATA_TOGGLE: '[data-toggle="'+(r = o = "datetimepicker")+'"]'}, c = {INPUT: o+"-input"}, u = {
            CHANGE: "change"+(d = "."+r),
            BLUR: "blur"+d,
            KEYUP: "keyup"+d,
            KEYDOWN: "keydown"+d,
            FOCUS: "focus"+d,
            CLICK_DATA_API: "click"+d+(h = ".data-api"),
            UPDATE: "update"+d,
            ERROR: "error"+d,
            HIDE: "hide"+d,
            SHOW: "show"+d
        }, _ = [{CLASS_NAME: "days", NAV_FUNCTION: "M", NAV_STEP: 1}, {
            CLASS_NAME: "months",
            NAV_FUNCTION: "y",
            NAV_STEP: 1
        }, {CLASS_NAME: "years", NAV_FUNCTION: "y", NAV_STEP: 10}, {
            CLASS_NAME: "decades",
            NAV_FUNCTION: "y",
            NAV_STEP: 100
        }], f = {
            up: 38,
            38: "up",
            down: 40,
            40: "down",
            left: 37,
            37: "left",
            right: 39,
            39: "right",
            tab: 9,
            9: "tab",
            escape: 27,
            27: "escape",
            enter: 13,
            13: "enter",
            pageUp: 33,
            33: "pageUp",
            pageDown: 34,
            34: "pageDown",
            shift: 16,
            16: "shift",
            control: 17,
            17: "control",
            space: 32,
            32: "space",
            t: 84,
            84: "t",
            delete: 46,
            46: "delete"
        }, m = ["times", "days", "months", "years", "decades"], v = {
            timeZone: "",
            format: !(b = {
                time: "clock",
                date: "calendar",
                up: "arrow-up",
                down: "arrow-down",
                previous: "arrow-left",
                next: "arrow-right",
                today: "arrow-down-circle",
                clear: "trash-2",
                close: "x"
            }),
            dayViewHeaderFormat: "MMMM YYYY",
            extraFormats: !(i = {
                timeZone: -39,
                format: -38,
                dayViewHeaderFormat: -37,
                extraFormats: -36,
                stepping: -35,
                minDate: -34,
                maxDate: -33,
                useCurrent: -32,
                collapse: -31,
                locale: -30,
                defaultDate: -29,
                disabledDates: -28,
                enabledDates: -27,
                icons: -26,
                tooltips: -25,
                useStrict: -24,
                sideBySide: -23,
                daysOfWeekDisabled: -22,
                calendarWeeks: -21,
                viewMode: -20,
                toolbarPlacement: -19,
                buttons: -18,
                widgetPositioning: -17,
                widgetParent: -16,
                ignoreReadonly: -15,
                keepOpen: -14,
                focusOnShow: -13,
                inline: -12,
                keepInvalid: -11,
                keyBinds: -10,
                debug: -9,
                allowInputToggle: -8,
                disabledTimeIntervals: -7,
                disabledHours: -6,
                enabledHours: -5,
                viewDate: -4,
                allowMultidate: -3,
                multidateSeparator: -2,
                updateOnlyThroughDateOption: -1,
                date: 1
            }),
            stepping: 1,
            minDate: !(g = {}),
            maxDate: !(w = {}),
            useCurrent: !0,
            collapse: !0,
            locale: n.locale(),
            defaultDate: !1,
            disabledDates: !1,
            enabledDates: !1,
            icons: {
                type: "class",
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-calendar-check-o",
                clear: "fa fa-trash",
                close: "fa fa-times"
            },
            tooltips: {
                today: "Go to today",
                clear: "Clear selection",
                close: "Close the picker",
                selectMonth: "Select Month",
                prevMonth: "Previous Month",
                nextMonth: "Next Month",
                selectYear: "Select Year",
                prevYear: "Previous Year",
                nextYear: "Next Year",
                selectDecade: "Select Decade",
                prevDecade: "Previous Decade",
                nextDecade: "Next Decade",
                prevCentury: "Previous Century",
                nextCentury: "Next Century",
                pickHour: "Pick Hour",
                incrementHour: "Increment Hour",
                decrementHour: "Decrement Hour",
                pickMinute: "Pick Minute",
                incrementMinute: "Increment Minute",
                decrementMinute: "Decrement Minute",
                pickSecond: "Pick Second",
                incrementSecond: "Increment Second",
                decrementSecond: "Decrement Second",
                togglePeriod: "Toggle Period",
                selectTime: "Select Time",
                selectDate: "Select Date"
            },
            useStrict: !1,
            sideBySide: !1,
            daysOfWeekDisabled: !1,
            calendarWeeks: !1,
            viewMode: "days",
            toolbarPlacement: "default",
            buttons: {showToday: !1, showClear: !1, showClose: !1},
            widgetPositioning: {horizontal: "auto", vertical: "auto"},
            widgetParent: null,
            readonly: !1,
            ignoreReadonly: !1,
            keepOpen: !1,
            focusOnShow: !0,
            inline: !1,
            keepInvalid: !1,
            keyBinds: {
                up: function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") ? this.date(t.clone().subtract(7, "d")) : this.date(t.clone().add(this.stepping(), "m")), !0
                }, down: function () {
                    if (!this.widget) return this.show(), !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") ? this.date(t.clone().add(7, "d")) : this.date(t.clone().subtract(this.stepping(), "m")), !0
                }, "control up": function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") ? this.date(t.clone().subtract(1, "y")) : this.date(t.clone().add(1, "h")), !0
                }, "control down": function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") ? this.date(t.clone().add(1, "y")) : this.date(t.clone().subtract(1, "h")), !0
                }, left: function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") && this.date(t.clone().subtract(1, "d")), !0
                }, right: function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") && this.date(t.clone().add(1, "d")), !0
                }, pageUp: function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") && this.date(t.clone().subtract(1, "M")), !0
                }, pageDown: function () {
                    if (!this.widget) return !1;
                    var t = this._dates[0] || this.getMoment();
                    return this.widget.find(".datepicker").is(":visible") && this.date(t.clone().add(1, "M")), !0
                }, enter: function () {
                    return !!this.widget && (this.hide(), !0)
                }, escape: function () {
                    return !!this.widget && (this.hide(), !0)
                }, "control space": function () {
                    return !!this.widget && (this.widget.find(".timepicker").is(":visible") && this.widget.find('.btn[data-action="togglePeriod"]').click(), !0)
                }, t: function () {
                    return !!this.widget && (this.date(this.getMoment()), !0)
                }, delete: function () {
                    return !!this.widget && (this.clear(), !0)
                }
            },
            debug: !1,
            allowInputToggle: !1,
            disabledTimeIntervals: !1,
            disabledHours: !1,
            enabledHours: !1,
            viewDate: !1,
            allowMultidate: !1,
            multidateSeparator: ", ",
            updateOnlyThroughDateOption: !1,
            promptTimeOnDateChange: !1,
            promptTimeOnDateChangeTransitionDelay: 200
        }, function () {
            function p(t, e) {
                this._options = this._getOptions(e), this._element = t, this._dates = [], this._datesFormatted = [], this._viewDate = null, this.unset = !0, this.component = !1, this.widget = !1, this.use24Hours = null, this.actualFormat = null, this.parseFormats = null, this.currentViewMode = null, this.MinViewModeNumber = 0, this.isInitFormatting = !1, this.isInit = !1, this.isDateUpdateThroughDateOptionFromClientCode = !1, this.hasInitDate = !1, this.initDate = void 0, this._notifyChangeEventContext = void 0, this._currentPromptTimeTimeout = null, this._int()
            }

            var t, e, i = p.prototype;
            return i._int = function () {
                this.isInit = !0;
                var t = this._element.data("target-input");
                this._element.is("input") ? this.input = this._element : void 0 !== t && (this.input = "nearest" === t ? this._element.find("input") : a(t)), this._dates = [], this._dates[0] = this.getMoment(), this._viewDate = this.getMoment().clone(), a.extend(!0, this._options, this._dataToOptions()), this.hasInitDate = !1, this.initDate = void 0, this.options(this._options), this.isInitFormatting = !0, this._initFormatting(), this.isInitFormatting = !1, void 0 !== this.input && this.input.is("input") && 0 !== this.input.val().trim().length ? this._setValue(this._parseInputDate(this.input.val().trim()), 0) : this._options.defaultDate && void 0 !== this.input && void 0 === this.input.attr("placeholder") && this._setValue(this._options.defaultDate, 0), this.hasInitDate && this.date(this.initDate), this._options.inline && this.show(), this.isInit = !1
            }, i._update = function () {
                this.widget && (this._fillDate(), this._fillTime())
            }, i._setValue = function (t, e) {
                var i = void 0 === e, s = !t && i, a = this.isDateUpdateThroughDateOptionFromClientCode,
                    n = !this.isInit && this._options.updateOnlyThroughDateOption && !a, o = "", r = !1,
                    d = this.unset ? null : this._dates[e];
                if (!d && !this.unset && i && s && (d = this._dates[this._dates.length-1]), !t) return n ? void this._notifyEvent({
                    type: p.Event.CHANGE,
                    date: t,
                    oldDate: d,
                    isClear: s,
                    isInvalid: r,
                    isDateUpdateThroughDateOptionFromClientCode: a,
                    isInit: this.isInit
                }) : (!this._options.allowMultidate || 1 === this._dates.length || s ? (this.unset = !0, this._dates = [], this._datesFormatted = []) : (o = ""+this._element.data("date")+this._options.multidateSeparator, o = d && o.replace(""+d.format(this.actualFormat)+this._options.multidateSeparator, "").replace(""+this._options.multidateSeparator+this._options.multidateSeparator, "").replace(new RegExp(this._options.multidateSeparator.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&")+"\\s*$"), "") || "", this._dates.splice(e, 1), this._datesFormatted.splice(e, 1)), o = D(o), void 0 !== this.input && (this.input.val(o), this.input.trigger("input")), this._element.data("date", o), this._notifyEvent({
                    type: p.Event.CHANGE,
                    date: !1,
                    oldDate: d,
                    isClear: s,
                    isInvalid: r,
                    isDateUpdateThroughDateOptionFromClientCode: a,
                    isInit: this.isInit
                }), void this._update());
                if (t = t.clone().locale(this._options.locale), this._hasTimeZone() && t.tz(this._options.timeZone), 1 !== this._options.stepping && t.minutes(Math.round(t.minutes() / this._options.stepping) * this._options.stepping).seconds(0), this._isValid(t)) {
                    if (n) return void this._notifyEvent({
                        type: p.Event.CHANGE,
                        date: t.clone(),
                        oldDate: d,
                        isClear: s,
                        isInvalid: r,
                        isDateUpdateThroughDateOptionFromClientCode: a,
                        isInit: this.isInit
                    });
                    if (this._dates[e] = t, this._datesFormatted[e] = t.format("YYYY-MM-DD"), this._viewDate = t.clone(), this._options.allowMultidate && 1 < this._dates.length) {
                        for (var h = 0; h < this._dates.length; h++) o += ""+this._dates[h].format(this.actualFormat)+this._options.multidateSeparator;
                        o = o.replace(new RegExp(this._options.multidateSeparator+"\\s*$"), "")
                    } else o = this._dates[e].format(this.actualFormat);
                    o = D(o), void 0 !== this.input && (this.input.val(o), this.input.trigger("input")), this._element.data("date", o), this.unset = !1, this._update(), this._notifyEvent({
                        type: p.Event.CHANGE,
                        date: this._dates[e].clone(),
                        oldDate: d,
                        isClear: s,
                        isInvalid: r,
                        isDateUpdateThroughDateOptionFromClientCode: a,
                        isInit: this.isInit
                    })
                } else r = !0, this._options.keepInvalid ? this._notifyEvent({
                    type: p.Event.CHANGE,
                    date: t,
                    oldDate: d,
                    isClear: s,
                    isInvalid: r,
                    isDateUpdateThroughDateOptionFromClientCode: a,
                    isInit: this.isInit
                }) : void 0 !== this.input && (this.input.val(""+(this.unset ? "" : this._dates[e].format(this.actualFormat))), this.input.trigger("input")), this._notifyEvent({
                    type: p.Event.ERROR,
                    date: t,
                    oldDate: d
                })
            }, i._change = function (t) {
                var e = a(t.target).val().trim(), i = e ? this._parseInputDate(e) : null;
                return this._setValue(i, 0), t.stopImmediatePropagation(), !1
            }, i._getOptions = function (t) {
                return t = a.extend(!0, {}, v, t && t.icons && "feather" === t.icons.type ? {icons: b} : {}, t)
            }, i._hasTimeZone = function () {
                return void 0 !== n.tz && void 0 !== this._options.timeZone && null !== this._options.timeZone && "" !== this._options.timeZone
            }, i._isEnabled = function (t) {
                if ("string" != typeof t || 1 < t.length) throw new TypeError("isEnabled expects a single character string parameter");
                switch (t) {
                    case"y":
                        return -1 !== this.actualFormat.indexOf("Y");
                    case"M":
                        return -1 !== this.actualFormat.indexOf("M");
                    case"d":
                        return -1 !== this.actualFormat.toLowerCase().indexOf("d");
                    case"h":
                    case"H":
                        return -1 !== this.actualFormat.toLowerCase().indexOf("h");
                    case"m":
                        return -1 !== this.actualFormat.indexOf("m");
                    case"s":
                        return -1 !== this.actualFormat.indexOf("s");
                    case"a":
                    case"A":
                        return -1 !== this.actualFormat.toLowerCase().indexOf("a");
                    default:
                        return !1
                }
            }, i._hasTime = function () {
                return this._isEnabled("h") || this._isEnabled("m") || this._isEnabled("s")
            }, i._hasDate = function () {
                return this._isEnabled("y") || this._isEnabled("M") || this._isEnabled("d")
            }, i._dataToOptions = function () {
                var i = this._element.data(), s = {};
                return i.dateOptions && i.dateOptions instanceof Object && (s = a.extend(!0, s, i.dateOptions)), a.each(this._options, function (t) {
                    var e = "date"+t.charAt(0).toUpperCase()+t.slice(1);
                    void 0 !== i[e] ? s[t] = i[e] : delete s[t]
                }), s
            }, i._format = function () {
                return this._options.format || "YYYY-MM-DD HH:mm"
            }, i._areSameDates = function (t, e) {
                var i = this._format();
                return t && e && (t.isSame(e) || n(t.format(i), i).isSame(n(e.format(i), i)))
            }, i._notifyEvent = function (t) {
                if (t.type === p.Event.CHANGE) {
                    if (this._notifyChangeEventContext = this._notifyChangeEventContext || 0, this._notifyChangeEventContext++, t.date && this._areSameDates(t.date, t.oldDate) || !t.isClear && !t.date && !t.oldDate || 1 < this._notifyChangeEventContext) return void (this._notifyChangeEventContext = void 0);
                    this._handlePromptTimeIfNeeded(t)
                }
                this._element.trigger(t), this._notifyChangeEventContext = void 0
            }, i._handlePromptTimeIfNeeded = function (t) {
                if (this._options.promptTimeOnDateChange) {
                    if (!t.oldDate && this._options.useCurrent) return;
                    if (t.oldDate && t.date && (t.oldDate.format("YYYY-MM-DD") === t.date.format("YYYY-MM-DD") || t.oldDate.format("YYYY-MM-DD") !== t.date.format("YYYY-MM-DD") && t.oldDate.format("HH:mm:ss") !== t.date.format("HH:mm:ss"))) return;
                    var e = this;
                    clearTimeout(this._currentPromptTimeTimeout), this._currentPromptTimeTimeout = setTimeout(function () {
                        e.widget && e.widget.find('[data-action="togglePicker"]').click()
                    }, this._options.promptTimeOnDateChangeTransitionDelay)
                }
            }, i._viewUpdate = function (t) {
                "y" === t && (t = "YYYY"), this._notifyEvent({
                    type: p.Event.UPDATE,
                    change: t,
                    viewDate: this._viewDate.clone()
                })
            }, i._showMode = function (t) {
                this.widget && (t && (this.currentViewMode = Math.max(this.MinViewModeNumber, Math.min(3, this.currentViewMode+t))), this.widget.find(".datepicker > div").hide().filter(".datepicker-"+_[this.currentViewMode].CLASS_NAME).show())
            }, i._isInDisabledDates = function (t) {
                return !0 === this._options.disabledDates[t.format("YYYY-MM-DD")]
            }, i._isInEnabledDates = function (t) {
                return !0 === this._options.enabledDates[t.format("YYYY-MM-DD")]
            }, i._isInDisabledHours = function (t) {
                return !0 === this._options.disabledHours[t.format("H")]
            }, i._isInEnabledHours = function (t) {
                return !0 === this._options.enabledHours[t.format("H")]
            }, i._isValid = function (t, e) {
                if (!t || !t.isValid()) return !1;
                if (this._options.disabledDates && "d" === e && this._isInDisabledDates(t)) return !1;
                if (this._options.enabledDates && "d" === e && !this._isInEnabledDates(t)) return !1;
                if (this._options.minDate && t.isBefore(this._options.minDate, e)) return !1;
                if (this._options.maxDate && t.isAfter(this._options.maxDate, e)) return !1;
                if (this._options.daysOfWeekDisabled && "d" === e && -1 !== this._options.daysOfWeekDisabled.indexOf(t.day())) return !1;
                if (this._options.disabledHours && ("h" === e || "m" === e || "s" === e) && this._isInDisabledHours(t)) return !1;
                if (this._options.enabledHours && ("h" === e || "m" === e || "s" === e) && !this._isInEnabledHours(t)) return !1;
                if (this._options.disabledTimeIntervals && ("h" === e || "m" === e || "s" === e)) {
                    var i = !1;
                    if (a.each(this._options.disabledTimeIntervals, function () {
                        if (t.isBetween(this[0], this[1])) return !(i = !0)
                    }), i) return !1
                }
                return !0
            }, i._parseInputDate = function (t, e) {
                var i = (void 0 === e ? {} : e).isPickerShow, s = void 0 !== i && i;
                return void 0 === this._options.parseInputDate || s ? n.isMoment(t) || (t = this.getMoment(t)) : t = this._options.parseInputDate(t), t
            }, i._keydown = function (t) {
                var e, i, s, a, n = null, o = [], r = {}, d = t.which;
                for (e in w[d] = "p", w) w.hasOwnProperty(e) && "p" === w[e] && (o.push(e), parseInt(e, 10) !== d && (r[e] = !0));
                for (e in this._options.keyBinds) if (this._options.keyBinds.hasOwnProperty(e) && "function" == typeof this._options.keyBinds[e] && (s = e.split(" ")).length === o.length && f[d] === s[s.length-1]) {
                    for (a = !0, i = s.length-2; 0 <= i; i--) if (!(f[s[i]] in r)) {
                        a = !1;
                        break
                    }
                    if (a) {
                        n = this._options.keyBinds[e];
                        break
                    }
                }
                n && n.call(this) && (t.stopPropagation(), t.preventDefault())
            }, i._keyup = function (t) {
                w[t.which] = "r", g[t.which] && (g[t.which] = !1, t.stopPropagation(), t.preventDefault())
            }, i._indexGivenDates = function (t) {
                var e = {}, i = this;
                return a.each(t, function () {
                    var t = i._parseInputDate(this);
                    t.isValid() && (e[t.format("YYYY-MM-DD")] = !0)
                }), !!Object.keys(e).length && e
            }, i._indexGivenHours = function (t) {
                var e = {};
                return a.each(t, function () {
                    e[this] = !0
                }), !!Object.keys(e).length && e
            }, i._initFormatting = function () {
                var t = this._options.format || "L LT", e = this;
                this.actualFormat = t.replace(/(\[[^\[]*])|(\\)?(LTS|LT|LL?L?L?|l{1,4})/g, function (t) {
                    return (e.isInitFormatting && null === e._options.date ? e.getMoment() : e._dates[0]).localeData().longDateFormat(t) || t
                }), this.parseFormats = this._options.extraFormats ? this._options.extraFormats.slice() : [], this.parseFormats.indexOf(t) < 0 && this.parseFormats.indexOf(this.actualFormat) < 0 && this.parseFormats.push(this.actualFormat), this.use24Hours = this.actualFormat.toLowerCase().indexOf("a") < 1 && this.actualFormat.replace(/\[.*?]/g, "").indexOf("h") < 1, this._isEnabled("y") && (this.MinViewModeNumber = 2), this._isEnabled("M") && (this.MinViewModeNumber = 1), this._isEnabled("d") && (this.MinViewModeNumber = 0), this.currentViewMode = Math.max(this.MinViewModeNumber, this.currentViewMode), this.unset || this._setValue(this._dates[0], 0)
            }, i._getLastPickedDate = function () {
                var t = this._dates[this._getLastPickedDateIndex()];
                return !t && this._options.allowMultidate && (t = n(new Date)), t
            }, i._getLastPickedDateIndex = function () {
                return this._dates.length-1
            }, i.getMoment = function (t) {
                var e = null == t ? n().clone().locale(this._options.locale) : this._hasTimeZone() ? n.tz(t, this.parseFormats, this._options.locale, this._options.useStrict, this._options.timeZone) : n(t, this.parseFormats, this._options.locale, this._options.useStrict);
                return this._hasTimeZone() && e.tz(this._options.timeZone), e
            }, i.toggle = function () {
                return this.widget ? this.hide() : this.show()
            }, i.readonly = function (t) {
                if (0 === arguments.length) return this._options.readonly;
                if ("boolean" != typeof t) throw new TypeError("readonly() expects a boolean parameter");
                this._options.readonly = t, void 0 !== this.input && this.input.prop("readonly", this._options.readonly), this.widget && (this.hide(), this.show())
            }, i.ignoreReadonly = function (t) {
                if (0 === arguments.length) return this._options.ignoreReadonly;
                if ("boolean" != typeof t) throw new TypeError("ignoreReadonly() expects a boolean parameter");
                this._options.ignoreReadonly = t
            }, i.options = function (t) {
                if (0 === arguments.length) return a.extend(!0, {}, this._options);
                if (!(t instanceof Object)) throw new TypeError("options() this.options parameter should be an object");
                a.extend(!0, this._options, t);
                var s = this, e = Object.keys(this._options).sort(k);
                a.each(e, function (t, e) {
                    var i = s._options[e];
                    if (void 0 !== s[e]) {
                        if (s.isInit && "date" === e) return s.hasInitDate = !0, void (s.initDate = i);
                        s[e](i)
                    }
                })
            }, i.date = function (t, e) {
                if (e = e || 0, 0 === arguments.length) return this.unset ? null : this._options.allowMultidate ? this._dates.join(this._options.multidateSeparator) : this._dates[e].clone();
                if (!(null === t || "string" == typeof t || n.isMoment(t) || t instanceof Date)) throw new TypeError("date() parameter must be one of [null, string, moment or Date]");
                "string" == typeof t && y(t) && (t = new Date(t)), this._setValue(null === t ? null : this._parseInputDate(t), e)
            }, i.updateOnlyThroughDateOption = function (t) {
                if ("boolean" != typeof t) throw new TypeError("updateOnlyThroughDateOption() expects a boolean parameter");
                this._options.updateOnlyThroughDateOption = t
            }, i.format = function (t) {
                if (0 === arguments.length) return this._options.format;
                if ("string" != typeof t && ("boolean" != typeof t || !1 !== t)) throw new TypeError("format() expects a string or boolean:false parameter "+t);
                this._options.format = t, this.actualFormat && this._initFormatting()
            }, i.timeZone = function (t) {
                if (0 === arguments.length) return this._options.timeZone;
                if ("string" != typeof t) throw new TypeError("newZone() expects a string parameter");
                this._options.timeZone = t
            }, i.dayViewHeaderFormat = function (t) {
                if (0 === arguments.length) return this._options.dayViewHeaderFormat;
                if ("string" != typeof t) throw new TypeError("dayViewHeaderFormat() expects a string parameter");
                this._options.dayViewHeaderFormat = t
            }, i.extraFormats = function (t) {
                if (0 === arguments.length) return this._options.extraFormats;
                if (!1 !== t && !(t instanceof Array)) throw new TypeError("extraFormats() expects an array or false parameter");
                this._options.extraFormats = t, this.parseFormats && this._initFormatting()
            }, i.disabledDates = function (t) {
                if (0 === arguments.length) return this._options.disabledDates ? a.extend({}, this._options.disabledDates) : this._options.disabledDates;
                if (!t) return this._options.disabledDates = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("disabledDates() expects an array parameter");
                this._options.disabledDates = this._indexGivenDates(t), this._options.enabledDates = !1, this._update()
            }, i.enabledDates = function (t) {
                if (0 === arguments.length) return this._options.enabledDates ? a.extend({}, this._options.enabledDates) : this._options.enabledDates;
                if (!t) return this._options.enabledDates = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("enabledDates() expects an array parameter");
                this._options.enabledDates = this._indexGivenDates(t), this._options.disabledDates = !1, this._update()
            }, i.daysOfWeekDisabled = function (t) {
                if (0 === arguments.length) return this._options.daysOfWeekDisabled.splice(0);
                if ("boolean" == typeof t && !t) return this._options.daysOfWeekDisabled = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("daysOfWeekDisabled() expects an array parameter");
                if (this._options.daysOfWeekDisabled = t.reduce(function (t, e) {
                    return 6 < (e = parseInt(e, 10)) || e < 0 || isNaN(e) || -1 === t.indexOf(e) && t.push(e), t
                }, []).sort(), this._options.useCurrent && !this._options.keepInvalid) for (var e = 0; e < this._dates.length; e++) {
                    for (var i = 0; !this._isValid(this._dates[e], "d");) {
                        if (this._dates[e].add(1, "d"), 31 === i) throw"Tried 31 times to find a valid date";
                        i++
                    }
                    this._setValue(this._dates[e], e)
                }
                this._update()
            }, i.maxDate = function (t) {
                if (0 === arguments.length) return this._options.maxDate ? this._options.maxDate.clone() : this._options.maxDate;
                if ("boolean" == typeof t && !1 === t) return this._options.maxDate = !1, this._update(), !0;
                "string" == typeof t && ("now" !== t && "moment" !== t || (t = this.getMoment()));
                var e = this._parseInputDate(t);
                if (!e.isValid()) throw new TypeError("maxDate() Could not parse date parameter: "+t);
                if (this._options.minDate && e.isBefore(this._options.minDate)) throw new TypeError("maxDate() date parameter is before this.options.minDate: "+e.format(this.actualFormat));
                this._options.maxDate = e;
                for (var i = 0; i < this._dates.length; i++) this._options.useCurrent && !this._options.keepInvalid && this._dates[i].isAfter(t) && this._setValue(this._options.maxDate, i);
                this._viewDate.isAfter(e) && (this._viewDate = e.clone().subtract(this._options.stepping, "m")), this._update()
            }, i.minDate = function (t) {
                if (0 === arguments.length) return this._options.minDate ? this._options.minDate.clone() : this._options.minDate;
                if ("boolean" == typeof t && !1 === t) return this._options.minDate = !1, this._update(), !0;
                "string" == typeof t && ("now" !== t && "moment" !== t || (t = this.getMoment()));
                var e = this._parseInputDate(t);
                if (!e.isValid()) throw new TypeError("minDate() Could not parse date parameter: "+t);
                if (this._options.maxDate && e.isAfter(this._options.maxDate)) throw new TypeError("minDate() date parameter is after this.options.maxDate: "+e.format(this.actualFormat));
                this._options.minDate = e;
                for (var i = 0; i < this._dates.length; i++) this._options.useCurrent && !this._options.keepInvalid && this._dates[i].isBefore(t) && this._setValue(this._options.minDate, i);
                this._viewDate.isBefore(e) && (this._viewDate = e.clone().add(this._options.stepping, "m")), this._update()
            }, i.defaultDate = function (t) {
                if (0 === arguments.length) return this._options.defaultDate ? this._options.defaultDate.clone() : this._options.defaultDate;
                if (!t) return !(this._options.defaultDate = !1);
                "string" == typeof t && (t = "now" === t || "moment" === t ? this.getMoment() : this.getMoment(t));
                var e = this._parseInputDate(t);
                if (!e.isValid()) throw new TypeError("defaultDate() Could not parse date parameter: "+t);
                if (!this._isValid(e)) throw new TypeError("defaultDate() date passed is invalid according to component setup validations");
                this._options.defaultDate = e, (this._options.defaultDate && this._options.inline || void 0 !== this.input && "" === this.input.val().trim()) && this._setValue(this._options.defaultDate, 0)
            }, i.locale = function (t) {
                if (0 === arguments.length) return this._options.locale;
                if (!n.localeData(t)) throw new TypeError("locale() locale "+t+" is not loaded from moment locales!");
                this._options.locale = t;
                for (var e = 0; e < this._dates.length; e++) this._dates[e].locale(this._options.locale);
                this._viewDate.locale(this._options.locale), this.actualFormat && this._initFormatting(), this.widget && (this.hide(), this.show())
            }, i.stepping = function (t) {
                if (0 === arguments.length) return this._options.stepping;
                t = parseInt(t, 10), (isNaN(t) || t < 1) && (t = 1), this._options.stepping = t
            }, i.useCurrent = function (t) {
                var e = ["year", "month", "day", "hour", "minute"];
                if (0 === arguments.length) return this._options.useCurrent;
                if ("boolean" != typeof t && "string" != typeof t) throw new TypeError("useCurrent() expects a boolean or string parameter");
                if ("string" == typeof t && -1 === e.indexOf(t.toLowerCase())) throw new TypeError("useCurrent() expects a string parameter of "+e.join(", "));
                this._options.useCurrent = t
            }, i.collapse = function (t) {
                if (0 === arguments.length) return this._options.collapse;
                if ("boolean" != typeof t) throw new TypeError("collapse() expects a boolean parameter");
                if (this._options.collapse === t) return !0;
                this._options.collapse = t, this.widget && (this.hide(), this.show())
            }, i.icons = function (t) {
                if (0 === arguments.length) return a.extend({}, this._options.icons);
                if (!(t instanceof Object)) throw new TypeError("icons() expects parameter to be an Object");
                a.extend(this._options.icons, t), this.widget && (this.hide(), this.show())
            }, i.tooltips = function (t) {
                if (0 === arguments.length) return a.extend({}, this._options.tooltips);
                if (!(t instanceof Object)) throw new TypeError("tooltips() expects parameter to be an Object");
                a.extend(this._options.tooltips, t), this.widget && (this.hide(), this.show())
            }, i.useStrict = function (t) {
                if (0 === arguments.length) return this._options.useStrict;
                if ("boolean" != typeof t) throw new TypeError("useStrict() expects a boolean parameter");
                this._options.useStrict = t
            }, i.sideBySide = function (t) {
                if (0 === arguments.length) return this._options.sideBySide;
                if ("boolean" != typeof t) throw new TypeError("sideBySide() expects a boolean parameter");
                this._options.sideBySide = t, this.widget && (this.hide(), this.show())
            }, i.viewMode = function (t) {
                if (0 === arguments.length) return this._options.viewMode;
                if ("string" != typeof t) throw new TypeError("viewMode() expects a string parameter");
                if (-1 === p.ViewModes.indexOf(t)) throw new TypeError("viewMode() parameter must be one of ("+p.ViewModes.join(", ")+") value");
                this._options.viewMode = t, this.currentViewMode = Math.max(p.ViewModes.indexOf(t)-1, this.MinViewModeNumber), this._showMode()
            }, i.calendarWeeks = function (t) {
                if (0 === arguments.length) return this._options.calendarWeeks;
                if ("boolean" != typeof t) throw new TypeError("calendarWeeks() expects parameter to be a boolean value");
                this._options.calendarWeeks = t, this._update()
            }, i.buttons = function (t) {
                if (0 === arguments.length) return a.extend({}, this._options.buttons);
                if (!(t instanceof Object)) throw new TypeError("buttons() expects parameter to be an Object");
                if (a.extend(this._options.buttons, t), "boolean" != typeof this._options.buttons.showToday) throw new TypeError("buttons.showToday expects a boolean parameter");
                if ("boolean" != typeof this._options.buttons.showClear) throw new TypeError("buttons.showClear expects a boolean parameter");
                if ("boolean" != typeof this._options.buttons.showClose) throw new TypeError("buttons.showClose expects a boolean parameter");
                this.widget && (this.hide(), this.show())
            }, i.keepOpen = function (t) {
                if (0 === arguments.length) return this._options.keepOpen;
                if ("boolean" != typeof t) throw new TypeError("keepOpen() expects a boolean parameter");
                this._options.keepOpen = t
            }, i.focusOnShow = function (t) {
                if (0 === arguments.length) return this._options.focusOnShow;
                if ("boolean" != typeof t) throw new TypeError("focusOnShow() expects a boolean parameter");
                this._options.focusOnShow = t
            }, i.inline = function (t) {
                if (0 === arguments.length) return this._options.inline;
                if ("boolean" != typeof t) throw new TypeError("inline() expects a boolean parameter");
                this._options.inline = t
            }, i.clear = function () {
                this._setValue(null)
            }, i.keyBinds = function (t) {
                if (0 === arguments.length) return this._options.keyBinds;
                this._options.keyBinds = t
            }, i.debug = function (t) {
                if ("boolean" != typeof t) throw new TypeError("debug() expects a boolean parameter");
                this._options.debug = t
            }, i.allowInputToggle = function (t) {
                if (0 === arguments.length) return this._options.allowInputToggle;
                if ("boolean" != typeof t) throw new TypeError("allowInputToggle() expects a boolean parameter");
                this._options.allowInputToggle = t
            }, i.keepInvalid = function (t) {
                if (0 === arguments.length) return this._options.keepInvalid;
                if ("boolean" != typeof t) throw new TypeError("keepInvalid() expects a boolean parameter");
                this._options.keepInvalid = t
            }, i.datepickerInput = function (t) {
                if (0 === arguments.length) return this._options.datepickerInput;
                if ("string" != typeof t) throw new TypeError("datepickerInput() expects a string parameter");
                this._options.datepickerInput = t
            }, i.parseInputDate = function (t) {
                if (0 === arguments.length) return this._options.parseInputDate;
                if ("function" != typeof t) throw new TypeError("parseInputDate() should be as function");
                this._options.parseInputDate = t
            }, i.disabledTimeIntervals = function (t) {
                if (0 === arguments.length) return this._options.disabledTimeIntervals ? a.extend({}, this._options.disabledTimeIntervals) : this._options.disabledTimeIntervals;
                if (!t) return this._options.disabledTimeIntervals = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("disabledTimeIntervals() expects an array parameter");
                this._options.disabledTimeIntervals = t, this._update()
            }, i.disabledHours = function (t) {
                if (0 === arguments.length) return this._options.disabledHours ? a.extend({}, this._options.disabledHours) : this._options.disabledHours;
                if (!t) return this._options.disabledHours = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("disabledHours() expects an array parameter");
                if (this._options.disabledHours = this._indexGivenHours(t), this._options.enabledHours = !1, this._options.useCurrent && !this._options.keepInvalid) for (var e = 0; e < this._dates.length; e++) {
                    for (var i = 0; !this._isValid(this._dates[e], "h");) {
                        if (this._dates[e].add(1, "h"), 24 === i) throw"Tried 24 times to find a valid date";
                        i++
                    }
                    this._setValue(this._dates[e], e)
                }
                this._update()
            }, i.enabledHours = function (t) {
                if (0 === arguments.length) return this._options.enabledHours ? a.extend({}, this._options.enabledHours) : this._options.enabledHours;
                if (!t) return this._options.enabledHours = !1, this._update(), !0;
                if (!(t instanceof Array)) throw new TypeError("enabledHours() expects an array parameter");
                if (this._options.enabledHours = this._indexGivenHours(t), this._options.disabledHours = !1, this._options.useCurrent && !this._options.keepInvalid) for (var e = 0; e < this._dates.length; e++) {
                    for (var i = 0; !this._isValid(this._dates[e], "h");) {
                        if (this._dates[e].add(1, "h"), 24 === i) throw"Tried 24 times to find a valid date";
                        i++
                    }
                    this._setValue(this._dates[e], e)
                }
                this._update()
            }, i.viewDate = function (t) {
                if (0 === arguments.length) return this._viewDate.clone();
                if (!t) return this._viewDate = (this._dates[0] || this.getMoment()).clone(), !0;
                if (!("string" == typeof t || n.isMoment(t) || t instanceof Date)) throw new TypeError("viewDate() parameter must be one of [string, moment or Date]");
                this._viewDate = this._parseInputDate(t), this._update(), this._viewUpdate(_[this.currentViewMode] && _[this.currentViewMode].NAV_FUNCTION)
            }, i._fillDate = function () {
            }, i._useFeatherIcons = function () {
                return "feather" === this._options.icons.type
            }, i.allowMultidate = function (t) {
                if ("boolean" != typeof t) throw new TypeError("allowMultidate() expects a boolean parameter");
                this._options.allowMultidate = t
            }, i.multidateSeparator = function (t) {
                if (0 === arguments.length) return this._options.multidateSeparator;
                if ("string" != typeof t) throw new TypeError("multidateSeparator expects a string parameter");
                this._options.multidateSeparator = t
            }, t = p, (e = [{
                key: "NAME", get: function () {
                    return o
                }
            }, {
                key: "DATA_KEY", get: function () {
                    return r
                }
            }, {
                key: "EVENT_KEY", get: function () {
                    return d
                }
            }, {
                key: "DATA_API_KEY", get: function () {
                    return h
                }
            }, {
                key: "DatePickerModes", get: function () {
                    return _
                }
            }, {
                key: "ViewModes", get: function () {
                    return m
                }
            }, {
                key: "Event", get: function () {
                    return u
                }
            }, {
                key: "Selector", get: function () {
                    return l
                }
            }, {
                key: "Default", get: function () {
                    return v
                }, set: function (t) {
                    v = t
                }
            }, {
                key: "ClassName", get: function () {
                    return c
                }
            }]) && s(t, e), p
        }());

    function y(t) {
        return e = new Date(t), "[object Date]" === Object.prototype.toString.call(e) && !isNaN(e.getTime());
        var e
    }

    function D(t) {
        return t.replace(/(^\s+)|(\s+$)/g, "")
    }

    function k(t, e) {
        return i[t] && i[e] ? i[t] < 0 && i[e] < 0 ? Math.abs(i[e])-Math.abs(i[t]) : i[t] < 0 ? -1 : i[e] < 0 ? 1 : i[t]-i[e] : i[t] ? i[t] : i[e] ? i[e] : 0
    }

    var E, t, p, C, T, x;
    E = jQuery, t = E.fn[M.NAME], p = ["top", "bottom", "auto"], C = ["left", "right", "auto"], T = ["default", "top", "bottom"], x = function (d) {
        var t, e;

        function n(t, e) {
            var i = d.call(this, t, e) || this;
            return i._init(), i
        }

        e = d, (t = n).prototype = Object.create(e.prototype), (t.prototype.constructor = t).__proto__ = e;
        var i = n.prototype;
        return i._init = function () {
            var t;
            this._element.hasClass("input-group") && (0 === (t = this._element.find(".datepickerbutton")).length ? this.component = this._element.find('[data-toggle="datetimepicker"]') : this.component = t)
        }, i._iconTag = function (t) {
            return "undefined" != typeof feather && this._useFeatherIcons() && feather.icons[t] ? E("<span>").html(feather.icons[t].toSvg()) : E("<span>").addClass(t)
        }, i._getDatePickerTemplate = function () {
            var t = E("<thead>").append(E("<tr>").append(E("<th>").addClass("prev").attr("data-action", "previous").append(this._iconTag(this._options.icons.previous))).append(E("<th>").addClass("picker-switch").attr("data-action", "pickerSwitch").attr("colspan", this._options.calendarWeeks ? "6" : "5")).append(E("<th>").addClass("next").attr("data-action", "next").append(this._iconTag(this._options.icons.next)))),
                e = E("<tbody>").append(E("<tr>").append(E("<td>").attr("colspan", this._options.calendarWeeks ? "8" : "7")));
            return [E("<div>").addClass("datepicker-days").append(E("<table>").addClass("table table-sm").append(t).append(E("<tbody>"))), E("<div>").addClass("datepicker-months").append(E("<table>").addClass("table-condensed").append(t.clone()).append(e.clone())), E("<div>").addClass("datepicker-years").append(E("<table>").addClass("table-condensed").append(t.clone()).append(e.clone())), E("<div>").addClass("datepicker-decades").append(E("<table>").addClass("table-condensed").append(t.clone()).append(e.clone()))]
        }, i._getTimePickerMainTemplate = function () {
            var t = E("<tr>"), e = E("<tr>"), i = E("<tr>");
            return this._isEnabled("h") && (t.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.incrementHour
            }).addClass("btn").attr("data-action", "incrementHours").append(this._iconTag(this._options.icons.up)))), e.append(E("<td>").append(E("<span>").addClass("timepicker-hour").attr({
                "data-time-component": "hours",
                title: this._options.tooltips.pickHour
            }).attr("data-action", "showHours"))), i.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.decrementHour
            }).addClass("btn").attr("data-action", "decrementHours").append(this._iconTag(this._options.icons.down))))), this._isEnabled("m") && (this._isEnabled("h") && (t.append(E("<td>").addClass("separator")), e.append(E("<td>").addClass("separator").html(":")), i.append(E("<td>").addClass("separator"))), t.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.incrementMinute
            }).addClass("btn").attr("data-action", "incrementMinutes").append(this._iconTag(this._options.icons.up)))), e.append(E("<td>").append(E("<span>").addClass("timepicker-minute").attr({
                "data-time-component": "minutes",
                title: this._options.tooltips.pickMinute
            }).attr("data-action", "showMinutes"))), i.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.decrementMinute
            }).addClass("btn").attr("data-action", "decrementMinutes").append(this._iconTag(this._options.icons.down))))), this._isEnabled("s") && (this._isEnabled("m") && (t.append(E("<td>").addClass("separator")), e.append(E("<td>").addClass("separator").html(":")), i.append(E("<td>").addClass("separator"))), t.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.incrementSecond
            }).addClass("btn").attr("data-action", "incrementSeconds").append(this._iconTag(this._options.icons.up)))), e.append(E("<td>").append(E("<span>").addClass("timepicker-second").attr({
                "data-time-component": "seconds",
                title: this._options.tooltips.pickSecond
            }).attr("data-action", "showSeconds"))), i.append(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                title: this._options.tooltips.decrementSecond
            }).addClass("btn").attr("data-action", "decrementSeconds").append(this._iconTag(this._options.icons.down))))), this.use24Hours || (t.append(E("<td>").addClass("separator")), e.append(E("<td>").append(E("<button>").addClass("btn btn-primary").attr({
                "data-action": "togglePeriod",
                tabindex: "-1",
                title: this._options.tooltips.togglePeriod
            }))), i.append(E("<td>").addClass("separator"))), E("<div>").addClass("timepicker-picker").append(E("<table>").addClass("table-condensed").append([t, e, i]))
        }, i._getTimePickerTemplate = function () {
            var t = E("<div>").addClass("timepicker-hours").append(E("<table>").addClass("table-condensed")),
                e = E("<div>").addClass("timepicker-minutes").append(E("<table>").addClass("table-condensed")),
                i = E("<div>").addClass("timepicker-seconds").append(E("<table>").addClass("table-condensed")),
                s = [this._getTimePickerMainTemplate()];
            return this._isEnabled("h") && s.push(t), this._isEnabled("m") && s.push(e), this._isEnabled("s") && s.push(i), s
        }, i._getToolbar = function () {
            var t, e, i = [];
            return this._options.buttons.showToday && i.push(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                "data-action": "today",
                title: this._options.tooltips.today
            }).append(this._iconTag(this._options.icons.today)))), !this._options.sideBySide && this._options.collapse && this._hasDate() && this._hasTime() && (e = "times" === this._options.viewMode ? (t = this._options.tooltips.selectDate, this._options.icons.date) : (t = this._options.tooltips.selectTime, this._options.icons.time), i.push(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                "data-action": "togglePicker",
                title: t
            }).append(this._iconTag(e))))), this._options.buttons.showClear && i.push(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                "data-action": "clear",
                title: this._options.tooltips.clear
            }).append(this._iconTag(this._options.icons.clear)))), this._options.buttons.showClose && i.push(E("<td>").append(E("<a>").attr({
                href: "#",
                tabindex: "-1",
                "data-action": "close",
                title: this._options.tooltips.close
            }).append(this._iconTag(this._options.icons.close)))), 0 === i.length ? "" : E("<table>").addClass("table-condensed").append(E("<tbody>").append(E("<tr>").append(i)))
        }, i._getTemplate = function () {
            var t = E("<div>").addClass(("bootstrap-datetimepicker-widget dropdown-menu "+(this._options.calendarWeeks ? "tempusdominus-bootstrap-datetimepicker-widget-with-calendar-weeks" : "")+" "+(this._useFeatherIcons() ? "tempusdominus-bootstrap-datetimepicker-widget-with-feather-icons" : "")+" ").trim()),
                e = E("<div>").addClass("datepicker").append(this._getDatePickerTemplate()),
                i = E("<div>").addClass("timepicker").append(this._getTimePickerTemplate()),
                s = E("<ul>").addClass("list-unstyled"),
                a = E("<li>").addClass(("picker-switch"+(this._options.collapse ? " accordion-toggle" : "")+" "+(this._useFeatherIcons() ? "picker-switch-with-feathers-icons" : "")).trim()).append(this._getToolbar());
            return this._options.inline && t.removeClass("dropdown-menu"), this.use24Hours && t.addClass("usetwentyfour"), (void 0 !== this.input && this.input.prop("readonly") || this._options.readonly) && t.addClass("bootstrap-datetimepicker-widget-readonly"), this._isEnabled("s") && !this.use24Hours && t.addClass("wider"), this._options.sideBySide && this._hasDate() && this._hasTime() ? (t.addClass("timepicker-sbs"), "top" === this._options.toolbarPlacement && t.append(a), t.append(E("<div>").addClass("row").append(e.addClass("col-md-6")).append(i.addClass("col-md-6"))), "bottom" !== this._options.toolbarPlacement && "default" !== this._options.toolbarPlacement || t.append(a), t) : ("top" === this._options.toolbarPlacement && s.append(a), this._hasDate() && s.append(E("<li>").addClass(this._options.collapse && this._hasTime() ? "collapse" : "").addClass(this._options.collapse && this._hasTime() && "times" === this._options.viewMode ? "" : "show").append(e)), "default" === this._options.toolbarPlacement && s.append(a), this._hasTime() && s.append(E("<li>").addClass(this._options.collapse && this._hasDate() ? "collapse" : "").addClass(this._options.collapse && this._hasDate() && "times" === this._options.viewMode ? "show" : "").append(i)), "bottom" === this._options.toolbarPlacement && s.append(a), t.append(s))
        }, i._place = function (t) {
            var e, i = t && t.data && t.data.picker || this, s = i._options.widgetPositioning.vertical,
                a = i._options.widgetPositioning.horizontal,
                n = (i.component && i.component.length ? i.component : i._element).position(),
                o = (i.component && i.component.length ? i.component : i._element).offset();
            if (i._options.widgetParent) e = i._options.widgetParent.append(i.widget); else if (i._element.is("input")) e = i._element.after(i.widget).parent(); else {
                if (i._options.inline) return void (e = i._element.append(i.widget));
                e = i._element, i._element.children().first().after(i.widget)
            }
            if ("auto" === s && (s = o.top+1.5 * i.widget.height() >= E(window).height()+E(window).scrollTop() && i.widget.height()+i._element.outerHeight() < o.top ? "top" : "bottom"), "auto" === a && (a = e.width() < o.left+i.widget.outerWidth() / 2 && o.left+i.widget.outerWidth() > E(window).width() ? "right" : "left"), "top" === s ? i.widget.addClass("top").removeClass("bottom") : i.widget.addClass("bottom").removeClass("top"), "right" === a ? i.widget.addClass("float-right") : i.widget.removeClass("float-right"), "relative" !== e.css("position") && (e = e.parents().filter(function () {
                return "relative" === E(this).css("position")
            }).first()), 0 === e.length) throw new Error("datetimepicker component should be placed within a relative positioned container");
            i.widget.css({
                top: "top" === s ? "auto" : n.top+i._element.outerHeight()+"px",
                bottom: "top" === s ? e.outerHeight()-(e === i._element ? 0 : n.top)+"px" : "auto",
                left: "left" === a ? (e === i._element ? 0 : n.left)+"px" : "auto",
                right: "left" === a ? "auto" : e.outerWidth()-i._element.outerWidth()-(e === i._element ? 0 : n.left)+"px"
            })
        }, i._fillDow = function () {
            var t = E("<tr>"), e = this._viewDate.clone().startOf("w").startOf("d");
            for (!0 === this._options.calendarWeeks && t.append(E("<th>").addClass("cw").text("#")); e.isBefore(this._viewDate.clone().endOf("w"));) t.append(E("<th>").addClass("dow").text(e.format("dd"))), e.add(1, "d");
            this.widget.find(".datepicker-days thead").append(t)
        }, i._fillMonths = function () {
            for (var t = [], e = this._viewDate.clone().startOf("y").startOf("d"); e.isSame(this._viewDate, "y");) t.push(E("<span>").attr("data-action", "selectMonth").addClass("month").text(e.format("MMM"))), e.add(1, "M");
            this.widget.find(".datepicker-months td").empty().append(t)
        }, i._updateMonths = function () {
            var t = this.widget.find(".datepicker-months"), e = t.find("th"), i = t.find("tbody").find("span"),
                s = this, a = this._getLastPickedDate();
            e.eq(0).find("span").attr("title", this._options.tooltips.prevYear), e.eq(1).attr("title", this._options.tooltips.selectYear), e.eq(2).find("span").attr("title", this._options.tooltips.nextYear), t.find(".disabled").removeClass("disabled"), this._isValid(this._viewDate.clone().subtract(1, "y"), "y") || e.eq(0).addClass("disabled"), e.eq(1).text(this._viewDate.year()), this._isValid(this._viewDate.clone().add(1, "y"), "y") || e.eq(2).addClass("disabled"), i.removeClass("active"), a && a.isSame(this._viewDate, "y") && !this.unset && i.eq(a.month()).addClass("active"), i.each(function (t) {
                s._isValid(s._viewDate.clone().month(t), "M") || E(this).addClass("disabled")
            })
        }, i._getStartEndYear = function (t, e) {
            var i = t / 10, s = Math.floor(e / t) * t;
            return [s, s+9 * i, Math.floor(e / i) * i]
        }, i._updateYears = function () {
            var t = this.widget.find(".datepicker-years"), e = t.find("th"),
                i = this._getStartEndYear(10, this._viewDate.year()), s = this._viewDate.clone().year(i[0]),
                a = this._viewDate.clone().year(i[1]), n = "";
            for (e.eq(0).find("span").attr("title", this._options.tooltips.prevDecade), e.eq(1).attr("title", this._options.tooltips.selectDecade), e.eq(2).find("span").attr("title", this._options.tooltips.nextDecade), t.find(".disabled").removeClass("disabled"), this._options.minDate && this._options.minDate.isAfter(s, "y") && e.eq(0).addClass("disabled"), e.eq(1).text(s.year()+"-"+a.year()), this._options.maxDate && this._options.maxDate.isBefore(a, "y") && e.eq(2).addClass("disabled"), n += '<span data-action="selectYear" class="year old'+(this._isValid(s, "y") ? "" : " disabled")+'">'+(s.year()-1)+"</span>"; !s.isAfter(a, "y");) n += '<span data-action="selectYear" class="year'+(s.isSame(this._getLastPickedDate(), "y") && !this.unset ? " active" : "")+(this._isValid(s, "y") ? "" : " disabled")+'">'+s.year()+"</span>", s.add(1, "y");
            n += '<span data-action="selectYear" class="year old'+(this._isValid(s, "y") ? "" : " disabled")+'">'+s.year()+"</span>", t.find("td").html(n)
        }, i._updateDecades = function () {
            var t, e = this.widget.find(".datepicker-decades"), i = e.find("th"),
                s = this._getStartEndYear(100, this._viewDate.year()), a = this._viewDate.clone().year(s[0]),
                n = this._viewDate.clone().year(s[1]), o = this._getLastPickedDate(), r = !1, d = !1, h = "";
            for (i.eq(0).find("span").attr("title", this._options.tooltips.prevCentury), i.eq(2).find("span").attr("title", this._options.tooltips.nextCentury), e.find(".disabled").removeClass("disabled"), (0 === a.year() || this._options.minDate && this._options.minDate.isAfter(a, "y")) && i.eq(0).addClass("disabled"), i.eq(1).text(a.year()+"-"+n.year()), this._options.maxDate && this._options.maxDate.isBefore(n, "y") && i.eq(2).addClass("disabled"), a.year()-10 < 0 ? h += "<span>&nbsp;</span>" : h += '<span data-action="selectDecade" class="decade old" data-selection="'+(a.year()+6)+'">'+(a.year()-10)+"</span>"; !a.isAfter(n, "y");) t = a.year()+11, r = this._options.minDate && this._options.minDate.isAfter(a, "y") && this._options.minDate.year() <= t, d = this._options.maxDate && this._options.maxDate.isAfter(a, "y") && this._options.maxDate.year() <= t, h += '<span data-action="selectDecade" class="decade'+(o && o.isAfter(a) && o.year() <= t ? " active" : "")+(this._isValid(a, "y") || r || d ? "" : " disabled")+'" data-selection="'+(a.year()+6)+'">'+a.year()+"</span>", a.add(10, "y");
            h += '<span data-action="selectDecade" class="decade old" data-selection="'+(a.year()+6)+'">'+a.year()+"</span>", e.find("td").html(h)
        }, i._fillDate = function () {
            d.prototype._fillDate.call(this);
            var t, e, i, s, a, n = this.widget.find(".datepicker-days"), o = n.find("th"), r = [];
            if (this._hasDate()) {
                for (o.eq(0).find("span").attr("title", this._options.tooltips.prevMonth), o.eq(1).attr("title", this._options.tooltips.selectMonth), o.eq(2).find("span").attr("title", this._options.tooltips.nextMonth), n.find(".disabled").removeClass("disabled"), o.eq(1).text(this._viewDate.format(this._options.dayViewHeaderFormat)), this._isValid(this._viewDate.clone().subtract(1, "M"), "M") || o.eq(0).addClass("disabled"), this._isValid(this._viewDate.clone().add(1, "M"), "M") || o.eq(2).addClass("disabled"), t = this._viewDate.clone().startOf("M").startOf("w").startOf("d"), s = 0; s < 42; s++) {
                    0 === t.weekday() && (e = E("<tr>"), this._options.calendarWeeks && e.append('<td class="cw">'+t.week()+"</td>"), r.push(e)), i = "", t.isBefore(this._viewDate, "M") && (i += " old"), t.isAfter(this._viewDate, "M") && (i += " new"), this._options.allowMultidate ? -1 !== (a = this._datesFormatted.indexOf(t.format("YYYY-MM-DD"))) && t.isSame(this._datesFormatted[a], "d") && !this.unset && (i += " active") : t.isSame(this._getLastPickedDate(), "d") && !this.unset && (i += " active"), this._isValid(t, "d") || (i += " disabled"), t.isSame(this.getMoment(), "d") && (i += " today"), 0 !== t.day() && 6 !== t.day() || (i += " weekend"), e.append('<td data-action="selectDay" data-day="'+t.format("L")+'" class="day'+i+'">'+t.date()+"</td>"), t.add(1, "d")
                }
                E("body").addClass("tempusdominus-bootstrap-datetimepicker-widget-day-click"), E("body").append('<div class="tempusdominus-bootstrap-datetimepicker-widget-day-click-glass-panel"></div>'), n.find("tbody").empty().append(r), E("body").find(".tempusdominus-bootstrap-datetimepicker-widget-day-click-glass-panel").remove(), E("body").removeClass("tempusdominus-bootstrap-datetimepicker-widget-day-click"), this._updateMonths(), this._updateYears(), this._updateDecades()
            }
        }, i._fillHours = function () {
            var t = this.widget.find(".timepicker-hours table"), e = this._viewDate.clone().startOf("d"), i = [],
                s = E("<tr>");
            for (11 < this._viewDate.hour() && !this.use24Hours && e.hour(12); e.isSame(this._viewDate, "d") && (this.use24Hours || this._viewDate.hour() < 12 && e.hour() < 12 || 11 < this._viewDate.hour());) e.hour() % 4 == 0 && (s = E("<tr>"), i.push(s)), s.append('<td data-action="selectHour" class="hour'+(this._isValid(e, "h") ? "" : " disabled")+'">'+e.format(this.use24Hours ? "HH" : "hh")+"</td>"), e.add(1, "h");
            t.empty().append(i)
        }, i._fillMinutes = function () {
            for (var t = this.widget.find(".timepicker-minutes table"), e = this._viewDate.clone().startOf("h"), i = [], s = 1 === this._options.stepping ? 5 : this._options.stepping, a = E("<tr>"); this._viewDate.isSame(e, "h");) e.minute() % (4 * s) == 0 && (a = E("<tr>"), i.push(a)), a.append('<td data-action="selectMinute" class="minute'+(this._isValid(e, "m") ? "" : " disabled")+'">'+e.format("mm")+"</td>"), e.add(s, "m");
            t.empty().append(i)
        }, i._fillSeconds = function () {
            for (var t = this.widget.find(".timepicker-seconds table"), e = this._viewDate.clone().startOf("m"), i = [], s = E("<tr>"); this._viewDate.isSame(e, "m");) e.second() % 20 == 0 && (s = E("<tr>"), i.push(s)), s.append('<td data-action="selectSecond" class="second'+(this._isValid(e, "s") ? "" : " disabled")+'">'+e.format("ss")+"</td>"), e.add(5, "s");
            t.empty().append(i)
        }, i._fillTime = function () {
            var t, e, i = this.widget.find(".timepicker span[data-time-component]"), s = this._getLastPickedDate();
            this.use24Hours || (t = this.widget.find(".timepicker [data-action=togglePeriod]"), e = s ? s.clone().add(12 <= s.hours() ? -12 : 12, "h") : void 0, s && t.text(s.format("A")), this._isValid(e, "h") ? t.removeClass("disabled") : t.addClass("disabled")), s && i.filter("[data-time-component=hours]").text(s.format(this.use24Hours ? "HH" : "hh")), s && i.filter("[data-time-component=minutes]").text(s.format("mm")), s && i.filter("[data-time-component=seconds]").text(s.format("ss")), this._fillHours(), this._fillMinutes(), this._fillSeconds()
        }, i._doAction = function (t, e) {
            var i = this._getLastPickedDate();
            if (E(t.currentTarget).is(".disabled")) return !1;
            switch (e = e || E(t.currentTarget).data("action")) {
                case"next":
                    var s = M.DatePickerModes[this.currentViewMode].NAV_FUNCTION;
                    this._viewDate.add(M.DatePickerModes[this.currentViewMode].NAV_STEP, s), this._fillDate(), this._viewUpdate(s);
                    break;
                case"previous":
                    var a = M.DatePickerModes[this.currentViewMode].NAV_FUNCTION;
                    this._viewDate.subtract(M.DatePickerModes[this.currentViewMode].NAV_STEP, a), this._fillDate(), this._viewUpdate(a);
                    break;
                case"pickerSwitch":
                    this._showMode(1);
                    break;
                case"selectMonth":
                    var n = E(t.target).closest("tbody").find("span").index(E(t.target));
                    this._viewDate.month(n), this.currentViewMode === this.MinViewModeNumber ? (this._setValue(i.clone().year(this._viewDate.year()).month(this._viewDate.month()), this._getLastPickedDateIndex()), this._options.inline || this.hide()) : (this._showMode(-1), this._fillDate()), this._viewUpdate("M");
                    break;
                case"selectYear":
                    var o = parseInt(E(t.target).text(), 10) || 0;
                    this._viewDate.year(o), this.currentViewMode === this.MinViewModeNumber ? (this._setValue(i.clone().year(this._viewDate.year()), this._getLastPickedDateIndex()), this._options.inline || this.hide()) : (this._showMode(-1), this._fillDate()), this._viewUpdate("YYYY");
                    break;
                case"selectDecade":
                    var r = parseInt(E(t.target).data("selection"), 10) || 0;
                    this._viewDate.year(r), this.currentViewMode === this.MinViewModeNumber ? (this._setValue(i.clone().year(this._viewDate.year()), this._getLastPickedDateIndex()), this._options.inline || this.hide()) : (this._showMode(-1), this._fillDate()), this._viewUpdate("YYYY");
                    break;
                case"selectDay":
                    var d = this._viewDate.clone();
                    E(t.target).is(".old") && d.subtract(1, "M"), E(t.target).is(".new") && d.add(1, "M");
                    var h = d.date(parseInt(E(t.target).text(), 10)), p = 0;
                    this._options.allowMultidate ? -1 !== (p = this._datesFormatted.indexOf(h.format("YYYY-MM-DD"))) ? this._setValue(null, p) : this._setValue(h, this._getLastPickedDateIndex()+1) : this._setValue(h, this._getLastPickedDateIndex()), this._hasTime() || this._options.keepOpen || this._options.inline || this._options.allowMultidate || this.hide();
                    break;
                case"incrementHours":
                    if (!i) break;
                    var l = i.clone().add(1, "h");
                    this._isValid(l, "h") && (this._getLastPickedDateIndex() < 0 && this.date(l), this._setValue(l, this._getLastPickedDateIndex()));
                    break;
                case"incrementMinutes":
                    if (!i) break;
                    var c = i.clone().add(this._options.stepping, "m");
                    this._isValid(c, "m") && (this._getLastPickedDateIndex() < 0 && this.date(c), this._setValue(c, this._getLastPickedDateIndex()));
                    break;
                case"incrementSeconds":
                    if (!i) break;
                    var u = i.clone().add(1, "s");
                    this._isValid(u, "s") && (this._getLastPickedDateIndex() < 0 && this.date(u), this._setValue(u, this._getLastPickedDateIndex()));
                    break;
                case"decrementHours":
                    if (!i) break;
                    var _ = i.clone().subtract(1, "h");
                    this._isValid(_, "h") && (this._getLastPickedDateIndex() < 0 && this.date(_), this._setValue(_, this._getLastPickedDateIndex()));
                    break;
                case"decrementMinutes":
                    if (!i) break;
                    var f = i.clone().subtract(this._options.stepping, "m");
                    this._isValid(f, "m") && (this._getLastPickedDateIndex() < 0 && this.date(f), this._setValue(f, this._getLastPickedDateIndex()));
                    break;
                case"decrementSeconds":
                    if (!i) break;
                    var m = i.clone().subtract(1, "s");
                    this._isValid(m, "s") && (this._getLastPickedDateIndex() < 0 && this.date(m), this._setValue(m, this._getLastPickedDateIndex()));
                    break;
                case"togglePeriod":
                    this._setValue(i.clone().add(12 <= i.hours() ? -12 : 12, "h"), this._getLastPickedDateIndex());
                    break;
                case"togglePicker":
                    var w, g, b = E(t.target), v = b.closest("a"), y = b.closest("ul"), D = y.find(".show"),
                        k = y.find(".collapse:not(.show)"), C = b.is("span") ? b : b.find("span");
                    if (D && D.length) {
                        if ((w = D.data("collapse")) && w.transitioning) return !0;
                        D.collapse ? (D.collapse("hide"), k.collapse("show")) : (D.removeClass("show"), k.addClass("show")), this._useFeatherIcons() ? (v.toggleClass(this._options.icons.time+" "+this._options.icons.date), g = v.hasClass(this._options.icons.time) ? this._options.icons.date : this._options.icons.time, v.html(this._iconTag(g))) : C.toggleClass(this._options.icons.time+" "+this._options.icons.date), (this._useFeatherIcons() ? v.hasClass(this._options.icons.date) : C.hasClass(this._options.icons.date)) ? v.attr("title", this._options.tooltips.selectDate) : v.attr("title", this._options.tooltips.selectTime)
                    }
                    break;
                case"showPicker":
                    this.widget.find(".timepicker > div:not(.timepicker-picker)").hide(), this.widget.find(".timepicker .timepicker-picker").show();
                    break;
                case"showHours":
                    this.widget.find(".timepicker .timepicker-picker").hide(), this.widget.find(".timepicker .timepicker-hours").show();
                    break;
                case"showMinutes":
                    this.widget.find(".timepicker .timepicker-picker").hide(), this.widget.find(".timepicker .timepicker-minutes").show();
                    break;
                case"showSeconds":
                    this.widget.find(".timepicker .timepicker-picker").hide(), this.widget.find(".timepicker .timepicker-seconds").show();
                    break;
                case"selectHour":
                    var T = parseInt(E(t.target).text(), 10);
                    this.use24Hours || (12 <= i.hours() ? 12 !== T && (T += 12) : 12 === T && (T = 0)), this._setValue(i.clone().hours(T), this._getLastPickedDateIndex()), this._isEnabled("a") || this._isEnabled("m") || this._options.keepOpen || this._options.inline ? this._doAction(t, "showPicker") : this.hide();
                    break;
                case"selectMinute":
                    this._setValue(i.clone().minutes(parseInt(E(t.target).text(), 10)), this._getLastPickedDateIndex()), this._isEnabled("a") || this._isEnabled("s") || this._options.keepOpen || this._options.inline ? this._doAction(t, "showPicker") : this.hide();
                    break;
                case"selectSecond":
                    this._setValue(i.clone().seconds(parseInt(E(t.target).text(), 10)), this._getLastPickedDateIndex()), this._isEnabled("a") || this._options.keepOpen || this._options.inline ? this._doAction(t, "showPicker") : this.hide();
                    break;
                case"clear":
                    this.clear();
                    break;
                case"close":
                    this.hide();
                    break;
                case"today":
                    var x = this.getMoment();
                    this._isValid(x, "d") && this._setValue(x, this._getLastPickedDateIndex());
                    break
            }
            return !1
        }, i.hide = function () {
            var t, e = !1;
            this.widget && (this.widget.find(".collapse").each(function () {
                var t = E(this).data("collapse");
                return !t || !t.transitioning || !(e = !0)
            }), e || (this.component && this.component.hasClass("btn") && this.component.toggleClass("active"), this.widget.hide(), E(window).off("resize", this._place), this.widget.off("click", "[data-action]"), this.widget.off("mousedown", !1), this.widget.remove(), this.widget = !1, void 0 !== this.input && void 0 !== this.input.val() && 0 !== this.input.val().trim().length && this._setValue(this._parseInputDate(this.input.val().trim(), {isPickerShow: !1}), 0), t = this._getLastPickedDate(), this._notifyEvent({
                type: M.Event.HIDE,
                date: this.unset ? null : t ? t.clone() : void 0
            }), void 0 !== this.input && this.input.blur(), this._viewDate = t ? t.clone() : this.getMoment()))
        }, i.show = function () {
            var t, e = !1;
            if (void 0 !== this.input) {
                if (this.input.prop("disabled") || !this._options.ignoreReadonly && this.input.prop("readonly") || this.widget) return;
                void 0 !== this.input.val() && 0 !== this.input.val().trim().length ? this._setValue(this._parseInputDate(this.input.val().trim(), {isPickerShow: !0}), 0) : e = !0
            } else e = !0;
            e && this.unset && this._options.useCurrent && (t = this.getMoment(), "string" == typeof this._options.useCurrent && (t = {
                year: function (t) {
                    return t.month(0).date(1).hours(0).seconds(0).minutes(0)
                }, month: function (t) {
                    return t.date(1).hours(0).seconds(0).minutes(0)
                }, day: function (t) {
                    return t.hours(0).seconds(0).minutes(0)
                }, hour: function (t) {
                    return t.seconds(0).minutes(0)
                }, minute: function (t) {
                    return t.seconds(0)
                }
            }[this._options.useCurrent](t)), this._setValue(t, 0)), this.widget = this._getTemplate(), this._fillDow(), this._fillMonths(), this.widget.find(".timepicker-hours").hide(), this.widget.find(".timepicker-minutes").hide(), this.widget.find(".timepicker-seconds").hide(), this._update(), this._showMode(), E(window).on("resize", {picker: this}, this._place), this.widget.on("click", "[data-action]", E.proxy(this._doAction, this)), this.widget.on("mousedown", !1), this.component && this.component.hasClass("btn") && this.component.toggleClass("active"), this._place(), this.widget.show(), void 0 !== this.input && this._options.focusOnShow && !this.input.is(":focus") && this.input.focus(), this._notifyEvent({type: M.Event.SHOW})
        }, i.destroy = function () {
            this.hide(), this._element.removeData(M.DATA_KEY), this._element.removeData("date")
        }, i.disable = function () {
            this.hide(), this.component && this.component.hasClass("btn") && this.component.addClass("disabled"), void 0 !== this.input && this.input.prop("disabled", !0)
        }, i.enable = function () {
            this.component && this.component.hasClass("btn") && this.component.removeClass("disabled"), void 0 !== this.input && this.input.prop("disabled", !1)
        }, i.toolbarPlacement = function (t) {
            if (0 === arguments.length) return this._options.toolbarPlacement;
            if ("string" != typeof t) throw new TypeError("toolbarPlacement() expects a string parameter");
            if (-1 === T.indexOf(t)) throw new TypeError("toolbarPlacement() parameter must be one of ("+T.join(", ")+") value");
            this._options.toolbarPlacement = t, this.widget && (this.hide(), this.show())
        }, i.widgetPositioning = function (t) {
            if (0 === arguments.length) return E.extend({}, this._options.widgetPositioning);
            if ("[object Object]" !== {}.toString.call(t)) throw new TypeError("widgetPositioning() expects an object variable");
            if (t.horizontal) {
                if ("string" != typeof t.horizontal) throw new TypeError("widgetPositioning() horizontal variable must be a string");
                if (t.horizontal = t.horizontal.toLowerCase(), -1 === C.indexOf(t.horizontal)) throw new TypeError("widgetPositioning() expects horizontal parameter to be one of ("+C.join(", ")+")");
                this._options.widgetPositioning.horizontal = t.horizontal
            }
            if (t.vertical) {
                if ("string" != typeof t.vertical) throw new TypeError("widgetPositioning() vertical variable must be a string");
                if (t.vertical = t.vertical.toLowerCase(), -1 === p.indexOf(t.vertical)) throw new TypeError("widgetPositioning() expects vertical parameter to be one of ("+p.join(", ")+")");
                this._options.widgetPositioning.vertical = t.vertical
            }
            this._update()
        }, i.widgetParent = function (t) {
            if (0 === arguments.length) return this._options.widgetParent;
            if ("string" == typeof t && (t = E(t)), null !== t && "string" != typeof t && !(t instanceof E)) throw new TypeError("widgetParent() expects a string or a jQuery object parameter");
            this._options.widgetParent = t, this.widget && (this.hide(), this.show())
        }, i.setMultiDate = function (t) {
            var e = this._options.format;
            this.clear();
            for (var i = 0; i < t.length; i++) {
                var s = moment(t[i], e);
                this._setValue(s, i)
            }
        }, n._jQueryHandleThis = function (t, e, i) {
            var s = E(t).data(M.DATA_KEY);
            if ("object" == typeof e && E.extend({}, M.Default, e), s || (s = new n(E(t), e), E(t).data(M.DATA_KEY, s)), "string" == typeof e) {
                if (void 0 === s[e]) throw new Error('No method named "'+e+'"');
                if (void 0 === i) return s[e]();
                "date" === e && (s.isDateUpdateThroughDateOptionFromClientCode = !0);
                var a = s[e](i);
                return s.isDateUpdateThroughDateOptionFromClientCode = !1, a
            }
        }, n._jQueryInterface = function (t, e) {
            return 1 === this.length ? n._jQueryHandleThis(this[0], t, e) : this.each(function () {
                n._jQueryHandleThis(this, t, e)
            })
        }, n
    }(M), E(document).on(M.Event.CLICK_DATA_API, M.Selector.DATA_TOGGLE, function () {
        var t = E(this), e = I(t), i = e.data(M.DATA_KEY);
        0 !== e.length && (i._options.allowInputToggle && t.is('input[data-toggle="datetimepicker"]') || x._jQueryInterface.call(e, "toggle"))
    }).on(M.Event.CHANGE, "."+M.ClassName.INPUT, function (t) {
        var e = I(E(this));
        0 === e.length || t.isInit || x._jQueryInterface.call(e, "_change", t)
    }).on(M.Event.BLUR, "."+M.ClassName.INPUT, function (t) {
        var e = I(E(this)), i = e.data(M.DATA_KEY);
        0 !== e.length && (i._options.debug || window.debug || x._jQueryInterface.call(e, "hide", t))
    }).on(M.Event.KEYDOWN, "."+M.ClassName.INPUT, function (t) {
        var e = I(E(this));
        0 !== e.length && x._jQueryInterface.call(e, "_keydown", t)
    }).on(M.Event.KEYUP, "."+M.ClassName.INPUT, function (t) {
        var e = I(E(this));
        0 !== e.length && x._jQueryInterface.call(e, "_keyup", t)
    }).on(M.Event.FOCUS, "."+M.ClassName.INPUT, function (t) {
        var e = I(E(this)), i = e.data(M.DATA_KEY);
        0 !== e.length && i._options.allowInputToggle && x._jQueryInterface.call(e, "show", t)
    }), E.fn[M.NAME] = x._jQueryInterface, E.fn[M.NAME].Constructor = x, E.fn[M.NAME].noConflict = function () {
        return E.fn[M.NAME] = t, x._jQueryInterface
    };

    function I(t) {
        var e, i = t.data("target");
        return i || (i = t.attr("href") || "", i = /^#[a-z]/i.test(i) ? i : null), 0 === (e = E(i)).length ? t : (e.data(M.DATA_KEY) || E.extend({}, e.data(), E(this).data()), e)
    }
}();
/**
 * TimeSheet.js [v1.0]
 * Li Bin
 */

(function ($) {

    /*
    * 表格中的单元格类
    * */
    var CSheetCell = function (opt) {

        /*
         * opt : {
         *   state : 0 or 1,
         *   toggleCallback : function(curState){...}
         *   settingCallback : function(){...}
         * }
         *
         * */

        var cellPrivate = $.extend({
            state: 0,
            toggleCallback: false,
            settingCallback: false
        }, opt);


        /*反向切换单元格状态*/
        this.toggle = function () {
            cellPrivate.state = cellPrivate.state > 0 ? cellPrivate.state-1 : cellPrivate.state+1;
            if (cellPrivate.toggleCallback) {
                cellPrivate.toggleCallback(cellPrivate.state);
            }
        }

        /*
        * 设置单元格状态
        * state : 0 or 1
        * */
        this.set = function (state) {
            cellPrivate.state = state == 0 ? 0 : 1;
            if (cellPrivate.settingCallback) {
                cellPrivate.settingCallback();
            }
        }

        /*
        * 获取单元格状态
        * */
        this.get = function () {
            return cellPrivate.state;
        }

    }

    /*
    * 表格类
    * */
    var CSheet = function (opt) {

        /*
        * opt : {
        *   dimensions : [8,9],  [行数，列数]
        *   sheetData : [[0,1,1,0,0],[...],[...],...]    sheet数据，二维数组，索引(a,b),a-行下标，b-列下标，每个cell只有0,1两态，与dimensions对应
        *   toggleCallback : function(){..}
        *   settingCallback : function(){..}
        * }
        *
        * */

        var sheetPrivate = $.extend({
            dimensions: undefined,
            sheetData: undefined,
            toggleCallback: false,
            settingCallback: false
        }, opt);

        sheetPrivate.cells = [];

        /*
        * 初始化表格中的所有单元格
        * */
        sheetPrivate.initCells = function () {

            var rowNum = sheetPrivate.dimensions[0];
            var colNum = sheetPrivate.dimensions[1];

            if (sheetPrivate.dimensions.length == 2 && rowNum > 0 && colNum > 0) {
                for (var row = 0, curRow = []; row < rowNum; ++row) {
                    curRow = [];
                    for (var col = 0; col < colNum; ++col) {
                        curRow.push(new CSheetCell({
                            state: sheetPrivate.sheetData ? (sheetPrivate.sheetData[row] ? parseInt(sheetPrivate.sheetData[row][col]) : 0) : 0
                        }));
                    }
                    sheetPrivate.cells.push(curRow);
                }
            } else {
                throw new Error("CSheet : wrong dimensions");
            }
        }

        /*
        * 对给定的表各区域进行 toggle 或 set操作
        * */
        sheetPrivate.areaOperate = function (area, opt) {

            /*
             * area : {
             *   startCell : [2,1],
             *   endCell : [7,6]
             * }
             * opt : {
             *    type:"set" or "toggle",
             *    state : 0 or 1 if type is set
             * }
             * */

            var rowCount = sheetPrivate.cells.length;
            var colCount = sheetPrivate.cells[0] ? sheetPrivate.cells[0].length : 0;
            var operationArea = $.extend({
                startCell: [0, 0],
                endCell: [rowCount-1, colCount-1]
            }, area);

            var isSheetEmpty = rowCount == 0 || colCount == 0;
            var isAreaValid = operationArea.startCell[0] >= 0 && operationArea.endCell[0] <= rowCount-1 &&
                operationArea.startCell[1] >= 0 && operationArea.endCell[1] <= colCount-1 &&                                             //operationArea不能超越sheet的边界
                operationArea.startCell[0] <= operationArea.endCell[0] && operationArea.startCell[1] <= operationArea.endCell[1];      //startCell必须居于endCell的左上方，或与之重合

            if (!isAreaValid) {
                throw new Error("CSheet : operation area is invalid");
            } else if (!isSheetEmpty) {
                for (var row = operationArea.startCell[0]; row <= operationArea.endCell[0]; ++row) {
                    for (var col = operationArea.startCell[1]; col <= operationArea.endCell[1]; ++col) {
                        if (opt.type == "toggle") {
                            sheetPrivate.cells[row][col].toggle();
                        } else if (opt.type == "set") {
                            sheetPrivate.cells[row][col].set(opt.state);
                        }
                    }
                }
            }

        }

        sheetPrivate.initCells();

        /*
        * 对表格的指定区域进行状态反向切换
        * toggleArea : {
         *   startCell : [2,1],
         *   endCell : [7,6]
         * }
        *
        * */
        this.toggle = function (toggleArea) {

            sheetPrivate.areaOperate(toggleArea, {type: "toggle"});
            if (sheetPrivate.toggleCallback) {
                sheetPrivate.toggleCallback();
            }

        }


        /*
        *  对表格的指定区域进行状态设置
        *  state : 0 or 1
        *  settingArea : {
         *   startCell : [2,1],
         *   endCell : [7,6]
         * }
        * */
        this.set = function (state, settingArea) {

            sheetPrivate.areaOperate(settingArea, {type: "set", state: state});
            if (sheetPrivate.settingCallback) {
                sheetPrivate.settingCallback();
            }
        }

        /*
         *  获取指定单元格的状态
         *  cellIndex ： [2,3]
         *  @return : 0 or 1
         * */
        this.getCellState = function (cellIndex) {
            return sheetPrivate.cells[cellIndex[0]][cellIndex[1]].get();
        }

        /*
         *  获取指定行所有单元格的状态
         *  row ： 2
         *  @return : [1,0,...,1]
         * */
        this.getRowStates = function (row) {
            var rowStates = [];
            for (var col = 0; col < sheetPrivate.dimensions[1]; ++col) {
                rowStates.push(sheetPrivate.cells[row][col].get());
            }
            return rowStates;
        }

        /*
         *  获取所有单元格的状态
         *  @return : [[1,0,...,1],[1,0,...,1],...,[1,0,...,1]]
         * */
        this.getSheetStates = function () {
            var sheetStates = [];
            for (var row = 0, rowStates = []; row < sheetPrivate.dimensions[0]; ++row) {
                rowStates = [];
                for (var col = 0; col < sheetPrivate.dimensions[1]; ++col) {
                    rowStates.push(sheetPrivate.cells[row][col].get());
                }
                sheetStates.push(rowStates);
            }
            return sheetStates;
        }

    }


    $.fn.timeSheet = function (opt) {

        /*
         *   说明 ：
         *
         *   TimeSheet 应该被绑定在 TBODY 元素上，其子元素有如下默认class:
         *
         *   表头 ---- class: .TimeSheet-head
         *   列表头 ---- class: .TimeSheet-colHead
         *   行表头 ---- class: .TimeSheet-rowHead
         *   单元格 ---- class: .TimeSheet-cell
         *
         *   用户可在传入的sheetClass下将元素的默认样式覆盖
         *   sheetClass将被赋予 TBODY 元素
         *
         *
         * opt :
         * {
         *      data : {
         *          dimensions : [7,8],
         *          colHead : [{name:"name1",title:"",style:"width,background,color,font"},{name:"name2",title:"",style:"width,background,color,font"},...]
         *          rowHead : [{name:"name1",title:"",style:"height,background,color,font"},{name:"name2",title:"",style:"height,background,color,font"},...]
         *          sheetHead : {name:"headName",style:"width,height,background,color,font"}
         *          sheetData : [[0,1,1,0,0],[...],[...],...]    sheet数据，二维数组，行主序，索引(a,b),a-行下标，b-列下标，每个cell只有0,1两态，与dimensions对应
         *      },
         *
         *      sheetClass : "",
         *      start : function(ev){...}
         *      end : function(ev, selectedArea){...}
         *      remarks : false
         * }
         *
         */

        var thisSheet = $(this);

        if (!thisSheet.is("TBODY")) {
            throw new Error("TimeSheet needs to be bound on a TBODY element");
        }


        var sheetOption = $.extend({
            data: {},
            sheetClass: "",
            start: false,
            end: false,
            remarks: null
        }, opt);

        if (!sheetOption.data.dimensions || sheetOption.data.dimensions.length !== 2 || sheetOption.data.dimensions[0] < 0 || sheetOption.data.dimensions[1] < 0) {
            throw new Error("TimeSheet : wrong dimensions");
        }

        var operationArea = {
            startCell: undefined,
            endCell: undefined
        };

        var sheetModel = new CSheet({
            dimensions: sheetOption.data.dimensions,
            sheetData: sheetOption.data.sheetData ? sheetOption.data.sheetData : undefined
        });

        /*
        * 表格初始化
        * */
        var initSheet = function () {
            thisSheet.html("");
            thisSheet.addClass("TimeSheet");
            if (sheetOption.sheetClass) {
                thisSheet.addClass(sheetOption.sheetClass);
            }
            initColHeads();
            initRows();
            repaintSheet();
        };

        /*
        * 初始化每一列的顶部表头
        * */
        var initColHeads = function () {
            var colHeadHtml = '<tr>';
            for (var i = 0, curColHead = ''; i <= sheetOption.data.dimensions[1]; ++i) {
                if (i === 0) {
                    curColHead = '<td class="TimeSheet-head" style="'+(sheetOption.data.sheetHead.style ? sheetOption.data.sheetHead.style : '')+'">'+sheetOption.data.sheetHead.name+'</td>';
                } else {
                    curColHead = '<td title="'+(sheetOption.data.colHead[i-1].title ? sheetOption.data.colHead[i-1].title : "")+'" data-col="'+(i-1)+'" class="TimeSheet-colHead '+(i === sheetOption.data.dimensions[1] ? 'rightMost' : '')+'" style="'+(sheetOption.data.colHead[i-1].style ? sheetOption.data.colHead[i-1].style : '')+'">'+sheetOption.data.colHead[i-1].name+'</td>';
                }
                colHeadHtml += curColHead;
            }
            if (sheetOption.remarks) {
                colHeadHtml += '<td class="TimeSheet-remarkHead">'+sheetOption.remarks.title+'</td>';
            }
            colHeadHtml += '</tr>';
            thisSheet.append(colHeadHtml);
        };

        /*
        * 初始化每一行
        * */
        var initRows = function () {
            for (var row = 0, curRowHtml = ''; row < sheetOption.data.dimensions[0]; ++row) {
                curRowHtml = '<tr class="TimeSheet-row">'
                for (var col = 0, curCell = ''; col <= sheetOption.data.dimensions[1]; ++col) {
                    if (col === 0) {
                        curCell = '<td title="'+(sheetOption.data.rowHead[row].title ? sheetOption.data.rowHead[row].title : "")+'"class="TimeSheet-rowHead '+(row === sheetOption.data.dimensions[0]-1 ? 'bottomMost ' : ' ')+'" style="'+(sheetOption.data.rowHead[row].style ? sheetOption.data.rowHead[row].style : '')+'">'+sheetOption.data.rowHead[row].name+'</td>';
                    } else {
                        curCell = '<td class="TimeSheet-cell '+(row === sheetOption.data.dimensions[0]-1 ? 'bottomMost ' : ' ')+(col === sheetOption.data.dimensions[1] ? 'rightMost' : '')+'" data-row="'+row+'" data-col="'+(col-1)+'"></td>';
                    }
                    curRowHtml += curCell;
                }
                if (sheetOption.remarks) {
                    curRowHtml += '<td class="TimeSheet-remark '+(row === sheetOption.data.dimensions[0]-1 ? 'bottomMost ' : ' ')+'">'+sheetOption.remarks.default+'</td>';
                }
                curRowHtml += '</tr>';
                thisSheet.append(curRowHtml);
            }
        };

        /*
        * 比较两个单元格谁更靠近左上角
        * cell1:[2,3]
        * cell2:[4,5]
        * @return:{
             topLeft : cell1,
             bottomRight : cell2
         }
        * */
        var cellCompare = function (cell1, cell2) {  //check which cell is more top-left
            var sum1 = cell1[0]+cell1[1];
            var sum2 = cell2[0]+cell2[1];

            if ((cell1[0]-cell2[0]) * (cell1[1]-cell2[1]) < 0) {
                return {
                    topLeft: cell1[0] < cell2[0] ? [cell1[0], cell2[1]] : [cell2[0], cell1[1]],
                    bottomRight: cell1[0] < cell2[0] ? [cell2[0], cell1[1]] : [cell1[0], cell2[1]]
                };
            }

            return {
                topLeft: sum1 <= sum2 ? cell1 : cell2,
                bottomRight: sum1 > sum2 ? cell1 : cell2
            };
        };

        /*
        * 刷新表格
        * */
        var repaintSheet = function () {
            var sheetStates = sheetModel.getSheetStates();
            thisSheet.find(".TimeSheet-row").each(function (row, rowDom) {
                var curRow = $(rowDom);
                curRow.find(".TimeSheet-cell").each(function (col, cellDom) {
                    var curCell = $(cellDom);
                    if (sheetStates[row][col] === 1) {
                        curCell.addClass("TimeSheet-cell-selected");
                    } else if (sheetStates[row][col] === 0) {
                        curCell.removeClass("TimeSheet-cell-selected");
                    }
                });
            });
        };

        /*
        * 移除所有单元格的 TimeSheet-cell-selecting 类
        * */
        var removeSelecting = function () {
            thisSheet.find(".TimeSheet-cell-selecting").removeClass("TimeSheet-cell-selecting");
        };

        /*
        * 清空备注栏
        * */
        var cleanRemark = function () {
            thisSheet.find(".TimeSheet-remark").each(function (idx, ele) {
                var curDom = $(ele);
                curDom.prop("title", "");
                curDom.html(sheetOption.remarks.default);
            });
        };

        /*
        * 鼠标开始做选择操作
        * startCel ： [1,4]
        * */
        var startSelecting = function (ev, startCel) {
            operationArea.startCell = startCel;
            if (sheetOption.start) {
                sheetOption.start(ev);
            }
        };

        /*
         * 鼠标在选择操作过程中
         * topLeftCell ： [1,4]，      鼠标选择区域的左上角
         * bottomRightCell ： [3,9]      鼠标选择区域的右下角
         * */
        var duringSelecting = function (ev, topLeftCell, bottomRightCell) {
            var curDom = $(ev.currentTarget);

            if (isSelecting && curDom.hasClass("TimeSheet-cell") || isColSelecting && curDom.hasClass("TimeSheet-colHead")) {
                removeSelecting();
                for (var row = topLeftCell[0]; row <= bottomRightCell[0]; ++row) {
                    for (var col = topLeftCell[1]; col <= bottomRightCell[1]; ++col) {
                        $($(thisSheet.find(".TimeSheet-row")[row]).find(".TimeSheet-cell")[col]).addClass("TimeSheet-cell-selecting");
                    }
                }
            }
        };

        /*
         * 选择操作完成后
         * targetArea ： {
         *      topLeft ： [1,2],
         *      bottomRight: [3,8]
         * }
         * */
        var afterSelecting = function (ev, targetArea) {
            var curDom = $(ev.currentTarget);
            var key = $(ev.which);
            var targetState = !sheetModel.getCellState(operationArea.startCell);

            //if(key[0]===1){       targetState = 1;}   //鼠标左键,将选定区域置1
            //else if(key[0]===3){ targetState = 0;}   //鼠标右键,将选定区域置0

            if (isSelecting && curDom.hasClass("TimeSheet-cell") || isColSelecting && curDom.hasClass("TimeSheet-colHead")) {
                sheetModel.set(targetState, {
                    startCell: targetArea.topLeft,
                    endCell: targetArea.bottomRight
                });
                removeSelecting();
                repaintSheet();
                if (sheetOption.end) {
                    sheetOption.end(ev, targetArea);
                }
            } else {
                removeSelecting();
            }

            isSelecting = false;
            isColSelecting = false;
            operationArea = {
                startCell: undefined,
                endCell: undefined
            }
        };

        var isSelecting = false;  /*鼠标在表格区域做选择*/

        var isColSelecting = false; /*鼠标在列表头区域做选择*/

        var eventBinding = function () {

            /*防止重复绑定*/
            thisSheet.undelegate(".umsSheetEvent");

            /*表格开始选择*/
            thisSheet.delegate(".TimeSheet-cell", "mousedown.umsSheetEvent", function (ev) {
                var curCell = $(ev.currentTarget);
                var startCell = [curCell.data("row"), curCell.data("col")];
                isSelecting = true;
                startSelecting(ev, startCell);
            });

            /*表格选择完成*/
            thisSheet.delegate(".TimeSheet-cell", "mouseup.umsSheetEvent", function (ev) {
                if (!operationArea.startCell) {
                    return;
                }
                var curCell = $(ev.currentTarget);
                var endCell = [curCell.data("row"), curCell.data("col")];
                var correctedCells = cellCompare(operationArea.startCell, endCell);
                afterSelecting(ev, correctedCells);
            });

            /*表格正在选择*/
            thisSheet.delegate(".TimeSheet-cell", "mouseover.umsSheetEvent", function (ev) {
                if (!isSelecting) {
                    return;
                }
                var curCell = $(ev.currentTarget);
                var curCellIndex = [curCell.data("row"), curCell.data("col")];
                var correctedCells = cellCompare(operationArea.startCell, curCellIndex);
                var topLeftCell = correctedCells.topLeft;
                var bottomRightCell = correctedCells.bottomRight;

                duringSelecting(ev, topLeftCell, bottomRightCell);
            });


            /*列表头开始选择*/
            thisSheet.delegate(".TimeSheet-colHead", "mousedown.umsSheetEvent", function (ev) {
                var curColHead = $(ev.currentTarget);
                var startCell = [0, curColHead.data("col")];
                isColSelecting = true;
                startSelecting(ev, startCell);
            });

            /*列表头选择完成*/
            thisSheet.delegate(".TimeSheet-colHead", "mouseup.umsSheetEvent", function (ev) {
                if (!operationArea.startCell) {
                    return;
                }
                var curColHead = $(ev.currentTarget);
                var endCell = [sheetOption.data.dimensions[0]-1, curColHead.data("col")];
                var correctedCells = cellCompare(operationArea.startCell, endCell);
                afterSelecting(ev, correctedCells);
            });

            /*列表头正在选择*/
            thisSheet.delegate(".TimeSheet-colHead", "mouseover.umsSheetEvent", function (ev) {
                if (!isColSelecting) {
                    return;
                }
                var curColHead = $(ev.currentTarget);
                var curCellIndex = [sheetOption.data.dimensions[0]-1, curColHead.data("col")];
                var correctedCells = cellCompare(operationArea.startCell, curCellIndex);
                var topLeftCell = correctedCells.topLeft;
                var bottomRightCell = correctedCells.bottomRight;

                duringSelecting(ev, topLeftCell, bottomRightCell);
            });

            /*表格禁止鼠标右键菜单*/
            thisSheet.delegate("td", "contextmenu.umsSheetEvent", function (ev) {
                return false;
            });
        };


        initSheet();
        eventBinding();


        var publicAPI = {

            /*
            * 获取单元格状态
            * cellIndex ：[1,2]
            * @return : 0 or 1
            * */
            getCellState: function (cellIndex) {
                return sheetModel.getCellState(cellIndex);
            },

            /*
             * 获取某行所有单元格状态
             * row ：2
             * @return : [1,0,0,...,0,1]
             * */
            getRowStates: function (row) {
                return sheetModel.getRowStates(row);
            },

            /*
             * 获取表格所有单元格状态
             * @return : [[1,0,0,...,0,1],[1,0,0,...,0,1],...,[1,0,0,...,0,1]]
             * */
            getSheetStates: function () {
                return sheetModel.getSheetStates();
            },

            /*
            * 设置某行的说明文字
            * row : 2,
            * html : 说明
            * */
            setRemark: function (row, html) {
                if ($.trim(html) !== '') {
                    $(thisSheet.find(".TimeSheet-row")[row]).find(".TimeSheet-remark").prop("title", html).html(html);
                }
            },

            /*
            * 重置表格
            * */
            clean: function () {
                sheetModel.set(0, {});
                repaintSheet();
                cleanRemark();
            },

            /*
            * 获取 default remark
            * */
            getDefaultRemark: function () {
                return sheetOption.remarks.default;
            },

            /*
            * 使表格不可操作
            * */
            disable: function () {
                thisSheet.undelegate(".umsSheetEvent");
            },

            /*
             * 使表格可操作
             * */
            enable: function () {
                eventBinding();
            },

            /*
            * 判断表格是否所有单元格状态都是1
            * @return ： true or false
            * */
            isFull: function () {
                for (var row = 0; row < sheetOption.data.dimensions[0]; ++row) {
                    for (var col = 0; col < sheetOption.data.dimensions[1]; ++col) {
                        if (sheetModel.getCellState([row, col]) === 0) {
                            return false;
                        }
                    }
                }
                return true;
            }
        };

        return publicAPI;

    }


})(jQuery);
