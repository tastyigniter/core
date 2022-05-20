// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

// This is CodeMirror (https://codemirror.net), a code editor
// implemented in JavaScript on top of the browser's DOM.
//
// You can find some technical background for some of the code below
// at http://marijnhaverbeke.nl/blog/#cm-internals .

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
        typeof define === 'function' && define.amd ? define(factory) :
            (global = global || self, global.CodeMirror = factory());
}(this, (function () {
    'use strict';

    // Kludges for bugs and behavior differences that can't be feature
    // detected are enabled based on userAgent etc sniffing.
    var userAgent = navigator.userAgent;
    var platform = navigator.platform;

    var gecko = /gecko\/\d/i.test(userAgent);
    var ie_upto10 = /MSIE \d/.test(userAgent);
    var ie_11up = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(userAgent);
    var edge = /Edge\/(\d+)/.exec(userAgent);
    var ie = ie_upto10 || ie_11up || edge;
    var ie_version = ie && (ie_upto10 ? document.documentMode || 6 : +(edge || ie_11up)[1]);
    var webkit = !edge && /WebKit\//.test(userAgent);
    var qtwebkit = webkit && /Qt\/\d+\.\d+/.test(userAgent);
    var chrome = !edge && /Chrome\//.test(userAgent);
    var presto = /Opera\//.test(userAgent);
    var safari = /Apple Computer/.test(navigator.vendor);
    var mac_geMountainLion = /Mac OS X 1\d\D([8-9]|\d\d)\D/.test(userAgent);
    var phantom = /PhantomJS/.test(userAgent);

    var ios = safari && (/Mobile\/\w+/.test(userAgent) || navigator.maxTouchPoints > 2);
    var android = /Android/.test(userAgent);
    // This is woefully incomplete. Suggestions for alternative methods welcome.
    var mobile = ios || android || /webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(userAgent);
    var mac = ios || /Mac/.test(platform);
    var chromeOS = /\bCrOS\b/.test(userAgent);
    var windows = /win/i.test(platform);

    var presto_version = presto && userAgent.match(/Version\/(\d*\.\d*)/);
    if (presto_version) {
        presto_version = Number(presto_version[1]);
    }
    if (presto_version && presto_version >= 15) {
        presto = false;
        webkit = true;
    }
    // Some browsers use the wrong event properties to signal cmd/ctrl on OS X
    var flipCtrlCmd = mac && (qtwebkit || presto && (presto_version == null || presto_version < 12.11));
    var captureRightClick = gecko || (ie && ie_version >= 9);

    function classTest(cls) {
        return new RegExp("(^|\\s)"+cls+"(?:$|\\s)\\s*")
    }

    var rmClass = function (node, cls) {
        var current = node.className;
        var match = classTest(cls).exec(current);
        if (match) {
            var after = current.slice(match.index+match[0].length);
            node.className = current.slice(0, match.index)+(after ? match[1]+after : "");
        }
    };

    function removeChildren(e) {
        for (var count = e.childNodes.length; count > 0; --count) {
            e.removeChild(e.firstChild);
        }
        return e
    }

    function removeChildrenAndAdd(parent, e) {
        return removeChildren(parent).appendChild(e)
    }

    function elt(tag, content, className, style) {
        var e = document.createElement(tag);
        if (className) {
            e.className = className;
        }
        if (style) {
            e.style.cssText = style;
        }
        if (typeof content == "string") {
            e.appendChild(document.createTextNode(content));
        } else if (content) {
            for (var i = 0; i < content.length; ++i) {
                e.appendChild(content[i]);
            }
        }
        return e
    }

    // wrapper for elt, which removes the elt from the accessibility tree
    function eltP(tag, content, className, style) {
        var e = elt(tag, content, className, style);
        e.setAttribute("role", "presentation");
        return e
    }

    var range;
    if (document.createRange) {
        range = function (node, start, end, endNode) {
            var r = document.createRange();
            r.setEnd(endNode || node, end);
            r.setStart(node, start);
            return r
        };
    } else {
        range = function (node, start, end) {
            var r = document.body.createTextRange();
            try {
                r.moveToElementText(node.parentNode);
            } catch (e) {
                return r
            }
            r.collapse(true);
            r.moveEnd("character", end);
            r.moveStart("character", start);
            return r
        };
    }

    function contains(parent, child) {
        if (child.nodeType == 3) // Android browser always returns false when child is a textnode
        {
            child = child.parentNode;
        }
        if (parent.contains) {
            return parent.contains(child)
        }
        do {
            if (child.nodeType == 11) {
                child = child.host;
            }
            if (child == parent) {
                return true
            }
        } while (child = child.parentNode)
    }

    function activeElt() {
        // IE and Edge may throw an "Unspecified Error" when accessing document.activeElement.
        // IE < 10 will throw when accessed while the page is loading or in an iframe.
        // IE > 9 and Edge will throw when accessed in an iframe if document.body is unavailable.
        var activeElement;
        try {
            activeElement = document.activeElement;
        } catch (e) {
            activeElement = document.body || null;
        }
        while (activeElement && activeElement.shadowRoot && activeElement.shadowRoot.activeElement) {
            activeElement = activeElement.shadowRoot.activeElement;
        }
        return activeElement
    }

    function addClass(node, cls) {
        var current = node.className;
        if (!classTest(cls).test(current)) {
            node.className += (current ? " " : "")+cls;
        }
    }

    function joinClasses(a, b) {
        var as = a.split(" ");
        for (var i = 0; i < as.length; i++) {
            if (as[i] && !classTest(as[i]).test(b)) {
                b += " "+as[i];
            }
        }
        return b
    }

    var selectInput = function (node) {
        node.select();
    };
    if (ios) // Mobile Safari apparently has a bug where select() is broken.
    {
        selectInput = function (node) {
            node.selectionStart = 0;
            node.selectionEnd = node.value.length;
        };
    } else if (ie) // Suppress mysterious IE10 errors
    {
        selectInput = function (node) {
            try {
                node.select();
            } catch (_e) {
            }
        };
    }

    function bind(f) {
        var args = Array.prototype.slice.call(arguments, 1);
        return function () {
            return f.apply(null, args)
        }
    }

    function copyObj(obj, target, overwrite) {
        if (!target) {
            target = {};
        }
        for (var prop in obj) {
            if (obj.hasOwnProperty(prop) && (overwrite !== false || !target.hasOwnProperty(prop))) {
                target[prop] = obj[prop];
            }
        }
        return target
    }

    // Counts the column offset in a string, taking tabs into account.
    // Used mostly to find indentation.
    function countColumn(string, end, tabSize, startIndex, startValue) {
        if (end == null) {
            end = string.search(/[^\s\u00a0]/);
            if (end == -1) {
                end = string.length;
            }
        }
        for (var i = startIndex || 0, n = startValue || 0; ;) {
            var nextTab = string.indexOf("\t", i);
            if (nextTab < 0 || nextTab >= end) {
                return n+(end-i)
            }
            n += nextTab-i;
            n += tabSize-(n % tabSize);
            i = nextTab+1;
        }
    }

    var Delayed = function () {
        this.id = null;
        this.f = null;
        this.time = 0;
        this.handler = bind(this.onTimeout, this);
    };
    Delayed.prototype.onTimeout = function (self) {
        self.id = 0;
        if (self.time <= +new Date) {
            self.f();
        } else {
            setTimeout(self.handler, self.time- +new Date);
        }
    };
    Delayed.prototype.set = function (ms, f) {
        this.f = f;
        var time = +new Date+ms;
        if (!this.id || time < this.time) {
            clearTimeout(this.id);
            this.id = setTimeout(this.handler, ms);
            this.time = time;
        }
    };

    function indexOf(array, elt) {
        for (var i = 0; i < array.length; ++i) {
            if (array[i] == elt) {
                return i
            }
        }
        return -1
    }

    // Number of pixels added to scroller and sizer to hide scrollbar
    var scrollerGap = 50;

    // Returned or thrown by various protocols to signal 'I'm not
    // handling this'.
    var Pass = {
        toString: function () {
            return "CodeMirror.Pass"
        }
    };

    // Reused option objects for setSelection & friends
    var sel_dontScroll = {scroll: false}, sel_mouse = {origin: "*mouse"}, sel_move = {origin: "+move"};

    // The inverse of countColumn -- find the offset that corresponds to
    // a particular column.
    function findColumn(string, goal, tabSize) {
        for (var pos = 0, col = 0; ;) {
            var nextTab = string.indexOf("\t", pos);
            if (nextTab == -1) {
                nextTab = string.length;
            }
            var skipped = nextTab-pos;
            if (nextTab == string.length || col+skipped >= goal) {
                return pos+Math.min(skipped, goal-col)
            }
            col += nextTab-pos;
            col += tabSize-(col % tabSize);
            pos = nextTab+1;
            if (col >= goal) {
                return pos
            }
        }
    }

    var spaceStrs = [""];

    function spaceStr(n) {
        while (spaceStrs.length <= n) {
            spaceStrs.push(lst(spaceStrs)+" ");
        }
        return spaceStrs[n]
    }

    function lst(arr) {
        return arr[arr.length-1]
    }

    function map(array, f) {
        var out = [];
        for (var i = 0; i < array.length; i++) {
            out[i] = f(array[i], i);
        }
        return out
    }

    function insertSorted(array, value, score) {
        var pos = 0, priority = score(value);
        while (pos < array.length && score(array[pos]) <= priority) {
            pos++;
        }
        array.splice(pos, 0, value);
    }

    function nothing() {
    }

    function createObj(base, props) {
        var inst;
        if (Object.create) {
            inst = Object.create(base);
        } else {
            nothing.prototype = base;
            inst = new nothing();
        }
        if (props) {
            copyObj(props, inst);
        }
        return inst
    }

    var nonASCIISingleCaseWordChar = /[\u00df\u0587\u0590-\u05f4\u0600-\u06ff\u3040-\u309f\u30a0-\u30ff\u3400-\u4db5\u4e00-\u9fcc\uac00-\ud7af]/;

    function isWordCharBasic(ch) {
        return /\w/.test(ch) || ch > "\x80" &&
            (ch.toUpperCase() != ch.toLowerCase() || nonASCIISingleCaseWordChar.test(ch))
    }

    function isWordChar(ch, helper) {
        if (!helper) {
            return isWordCharBasic(ch)
        }
        if (helper.source.indexOf("\\w") > -1 && isWordCharBasic(ch)) {
            return true
        }
        return helper.test(ch)
    }

    function isEmpty(obj) {
        for (var n in obj) {
            if (obj.hasOwnProperty(n) && obj[n]) {
                return false
            }
        }
        return true
    }

    // Extending unicode characters. A series of a non-extending char +
    // any number of extending chars is treated as a single unit as far
    // as editing and measuring is concerned. This is not fully correct,
    // since some scripts/fonts/browsers also treat other configurations
    // of code points as a group.
    var extendingChars = /[\u0300-\u036f\u0483-\u0489\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u065e\u0670\u06d6-\u06dc\u06de-\u06e4\u06e7\u06e8\u06ea-\u06ed\u0711\u0730-\u074a\u07a6-\u07b0\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0900-\u0902\u093c\u0941-\u0948\u094d\u0951-\u0955\u0962\u0963\u0981\u09bc\u09be\u09c1-\u09c4\u09cd\u09d7\u09e2\u09e3\u0a01\u0a02\u0a3c\u0a41\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a70\u0a71\u0a75\u0a81\u0a82\u0abc\u0ac1-\u0ac5\u0ac7\u0ac8\u0acd\u0ae2\u0ae3\u0b01\u0b3c\u0b3e\u0b3f\u0b41-\u0b44\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b82\u0bbe\u0bc0\u0bcd\u0bd7\u0c3e-\u0c40\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0cbc\u0cbf\u0cc2\u0cc6\u0ccc\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0d3e\u0d41-\u0d44\u0d4d\u0d57\u0d62\u0d63\u0dca\u0dcf\u0dd2-\u0dd4\u0dd6\u0ddf\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0f18\u0f19\u0f35\u0f37\u0f39\u0f71-\u0f7e\u0f80-\u0f84\u0f86\u0f87\u0f90-\u0f97\u0f99-\u0fbc\u0fc6\u102d-\u1030\u1032-\u1037\u1039\u103a\u103d\u103e\u1058\u1059\u105e-\u1060\u1071-\u1074\u1082\u1085\u1086\u108d\u109d\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b7-\u17bd\u17c6\u17c9-\u17d3\u17dd\u180b-\u180d\u18a9\u1920-\u1922\u1927\u1928\u1932\u1939-\u193b\u1a17\u1a18\u1a56\u1a58-\u1a5e\u1a60\u1a62\u1a65-\u1a6c\u1a73-\u1a7c\u1a7f\u1b00-\u1b03\u1b34\u1b36-\u1b3a\u1b3c\u1b42\u1b6b-\u1b73\u1b80\u1b81\u1ba2-\u1ba5\u1ba8\u1ba9\u1c2c-\u1c33\u1c36\u1c37\u1cd0-\u1cd2\u1cd4-\u1ce0\u1ce2-\u1ce8\u1ced\u1dc0-\u1de6\u1dfd-\u1dff\u200c\u200d\u20d0-\u20f0\u2cef-\u2cf1\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua66f-\ua672\ua67c\ua67d\ua6f0\ua6f1\ua802\ua806\ua80b\ua825\ua826\ua8c4\ua8e0-\ua8f1\ua926-\ua92d\ua947-\ua951\ua980-\ua982\ua9b3\ua9b6-\ua9b9\ua9bc\uaa29-\uaa2e\uaa31\uaa32\uaa35\uaa36\uaa43\uaa4c\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uabe5\uabe8\uabed\udc00-\udfff\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\uff9e\uff9f]/;

    function isExtendingChar(ch) {
        return ch.charCodeAt(0) >= 768 && extendingChars.test(ch)
    }

    // Returns a number from the range [`0`; `str.length`] unless `pos` is outside that range.
    function skipExtendingChars(str, pos, dir) {
        while ((dir < 0 ? pos > 0 : pos < str.length) && isExtendingChar(str.charAt(pos))) {
            pos += dir;
        }
        return pos
    }

    // Returns the value from the range [`from`; `to`] that satisfies
    // `pred` and is closest to `from`. Assumes that at least `to`
    // satisfies `pred`. Supports `from` being greater than `to`.
    function findFirst(pred, from, to) {
        // At any point we are certain `to` satisfies `pred`, don't know
        // whether `from` does.
        var dir = from > to ? -1 : 1;
        for (; ;) {
            if (from == to) {
                return from
            }
            var midF = (from+to) / 2, mid = dir < 0 ? Math.ceil(midF) : Math.floor(midF);
            if (mid == from) {
                return pred(mid) ? from : to
            }
            if (pred(mid)) {
                to = mid;
            } else {
                from = mid+dir;
            }
        }
    }

    // BIDI HELPERS

    function iterateBidiSections(order, from, to, f) {
        if (!order) {
            return f(from, to, "ltr", 0)
        }
        var found = false;
        for (var i = 0; i < order.length; ++i) {
            var part = order[i];
            if (part.from < to && part.to > from || from == to && part.to == from) {
                f(Math.max(part.from, from), Math.min(part.to, to), part.level == 1 ? "rtl" : "ltr", i);
                found = true;
            }
        }
        if (!found) {
            f(from, to, "ltr");
        }
    }

    var bidiOther = null;

    function getBidiPartAt(order, ch, sticky) {
        var found;
        bidiOther = null;
        for (var i = 0; i < order.length; ++i) {
            var cur = order[i];
            if (cur.from < ch && cur.to > ch) {
                return i
            }
            if (cur.to == ch) {
                if (cur.from != cur.to && sticky == "before") {
                    found = i;
                } else {
                    bidiOther = i;
                }
            }
            if (cur.from == ch) {
                if (cur.from != cur.to && sticky != "before") {
                    found = i;
                } else {
                    bidiOther = i;
                }
            }
        }
        return found != null ? found : bidiOther
    }

    // Bidirectional ordering algorithm
    // See http://unicode.org/reports/tr9/tr9-13.html for the algorithm
    // that this (partially) implements.

    // One-char codes used for character types:
    // L (L):   Left-to-Right
    // R (R):   Right-to-Left
    // r (AL):  Right-to-Left Arabic
    // 1 (EN):  European Number
    // + (ES):  European Number Separator
    // % (ET):  European Number Terminator
    // n (AN):  Arabic Number
    // , (CS):  Common Number Separator
    // m (NSM): Non-Spacing Mark
    // b (BN):  Boundary Neutral
    // s (B):   Paragraph Separator
    // t (S):   Segment Separator
    // w (WS):  Whitespace
    // N (ON):  Other Neutrals

    // Returns null if characters are ordered as they appear
    // (left-to-right), or an array of sections ({from, to, level}
    // objects) in the order in which they occur visually.
    var bidiOrdering = (function () {
        // Character types for codepoints 0 to 0xff
        var lowTypes = "bbbbbbbbbtstwsbbbbbbbbbbbbbbssstwNN%%%NNNNNN,N,N1111111111NNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNbbbbbbsbbbbbbbbbbbbbbbbbbbbbbbbbb,N%%%%NNNNLNNNNN%%11NLNNN1LNNNNNLLLLLLLLLLLLLLLLLLLLLLLNLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLN";
        // Character types for codepoints 0x600 to 0x6f9
        var arabicTypes = "nnnnnnNNr%%r,rNNmmmmmmmmmmmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmmmmmmmmnnnnnnnnnn%nnrrrmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmnNmmmmmmrrmmNmmmmrr1111111111";

        function charType(code) {
            if (code <= 0xf7) {
                return lowTypes.charAt(code)
            } else if (0x590 <= code && code <= 0x5f4) {
                return "R"
            } else if (0x600 <= code && code <= 0x6f9) {
                return arabicTypes.charAt(code-0x600)
            } else if (0x6ee <= code && code <= 0x8ac) {
                return "r"
            } else if (0x2000 <= code && code <= 0x200b) {
                return "w"
            } else if (code == 0x200c) {
                return "b"
            } else {
                return "L"
            }
        }

        var bidiRE = /[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/;
        var isNeutral = /[stwN]/, isStrong = /[LRr]/, countsAsLeft = /[Lb1n]/, countsAsNum = /[1n]/;

        function BidiSpan(level, from, to) {
            this.level = level;
            this.from = from;
            this.to = to;
        }

        return function (str, direction) {
            var outerType = direction == "ltr" ? "L" : "R";

            if (str.length == 0 || direction == "ltr" && !bidiRE.test(str)) {
                return false
            }
            var len = str.length, types = [];
            for (var i = 0; i < len; ++i) {
                types.push(charType(str.charCodeAt(i)));
            }

            // W1. Examine each non-spacing mark (NSM) in the level run, and
            // change the type of the NSM to the type of the previous
            // character. If the NSM is at the start of the level run, it will
            // get the type of sor.
            for (var i$1 = 0, prev = outerType; i$1 < len; ++i$1) {
                var type = types[i$1];
                if (type == "m") {
                    types[i$1] = prev;
                } else {
                    prev = type;
                }
            }

            // W2. Search backwards from each instance of a European number
            // until the first strong type (R, L, AL, or sor) is found. If an
            // AL is found, change the type of the European number to Arabic
            // number.
            // W3. Change all ALs to R.
            for (var i$2 = 0, cur = outerType; i$2 < len; ++i$2) {
                var type$1 = types[i$2];
                if (type$1 == "1" && cur == "r") {
                    types[i$2] = "n";
                } else if (isStrong.test(type$1)) {
                    cur = type$1;
                    if (type$1 == "r") {
                        types[i$2] = "R";
                    }
                }
            }

            // W4. A single European separator between two European numbers
            // changes to a European number. A single common separator between
            // two numbers of the same type changes to that type.
            for (var i$3 = 1, prev$1 = types[0]; i$3 < len-1; ++i$3) {
                var type$2 = types[i$3];
                if (type$2 == "+" && prev$1 == "1" && types[i$3+1] == "1") {
                    types[i$3] = "1";
                } else if (type$2 == "," && prev$1 == types[i$3+1] &&
                    (prev$1 == "1" || prev$1 == "n")) {
                    types[i$3] = prev$1;
                }
                prev$1 = type$2;
            }

            // W5. A sequence of European terminators adjacent to European
            // numbers changes to all European numbers.
            // W6. Otherwise, separators and terminators change to Other
            // Neutral.
            for (var i$4 = 0; i$4 < len; ++i$4) {
                var type$3 = types[i$4];
                if (type$3 == ",") {
                    types[i$4] = "N";
                } else if (type$3 == "%") {
                    var end = (void 0);
                    for (end = i$4+1; end < len && types[end] == "%"; ++end) {
                    }
                    var replace = (i$4 && types[i$4-1] == "!") || (end < len && types[end] == "1") ? "1" : "N";
                    for (var j = i$4; j < end; ++j) {
                        types[j] = replace;
                    }
                    i$4 = end-1;
                }
            }

            // W7. Search backwards from each instance of a European number
            // until the first strong type (R, L, or sor) is found. If an L is
            // found, then change the type of the European number to L.
            for (var i$5 = 0, cur$1 = outerType; i$5 < len; ++i$5) {
                var type$4 = types[i$5];
                if (cur$1 == "L" && type$4 == "1") {
                    types[i$5] = "L";
                } else if (isStrong.test(type$4)) {
                    cur$1 = type$4;
                }
            }

            // N1. A sequence of neutrals takes the direction of the
            // surrounding strong text if the text on both sides has the same
            // direction. European and Arabic numbers act as if they were R in
            // terms of their influence on neutrals. Start-of-level-run (sor)
            // and end-of-level-run (eor) are used at level run boundaries.
            // N2. Any remaining neutrals take the embedding direction.
            for (var i$6 = 0; i$6 < len; ++i$6) {
                if (isNeutral.test(types[i$6])) {
                    var end$1 = (void 0);
                    for (end$1 = i$6+1; end$1 < len && isNeutral.test(types[end$1]); ++end$1) {
                    }
                    var before = (i$6 ? types[i$6-1] : outerType) == "L";
                    var after = (end$1 < len ? types[end$1] : outerType) == "L";
                    var replace$1 = before == after ? (before ? "L" : "R") : outerType;
                    for (var j$1 = i$6; j$1 < end$1; ++j$1) {
                        types[j$1] = replace$1;
                    }
                    i$6 = end$1-1;
                }
            }

            // Here we depart from the documented algorithm, in order to avoid
            // building up an actual levels array. Since there are only three
            // levels (0, 1, 2) in an implementation that doesn't take
            // explicit embedding into account, we can build up the order on
            // the fly, without following the level-based algorithm.
            var order = [], m;
            for (var i$7 = 0; i$7 < len;) {
                if (countsAsLeft.test(types[i$7])) {
                    var start = i$7;
                    for (++i$7; i$7 < len && countsAsLeft.test(types[i$7]); ++i$7) {
                    }
                    order.push(new BidiSpan(0, start, i$7));
                } else {
                    var pos = i$7, at = order.length, isRTL = direction == "rtl" ? 1 : 0;
                    for (++i$7; i$7 < len && types[i$7] != "L"; ++i$7) {
                    }
                    for (var j$2 = pos; j$2 < i$7;) {
                        if (countsAsNum.test(types[j$2])) {
                            if (pos < j$2) {
                                order.splice(at, 0, new BidiSpan(1, pos, j$2));
                                at += isRTL;
                            }
                            var nstart = j$2;
                            for (++j$2; j$2 < i$7 && countsAsNum.test(types[j$2]); ++j$2) {
                            }
                            order.splice(at, 0, new BidiSpan(2, nstart, j$2));
                            at += isRTL;
                            pos = j$2;
                        } else {
                            ++j$2;
                        }
                    }
                    if (pos < i$7) {
                        order.splice(at, 0, new BidiSpan(1, pos, i$7));
                    }
                }
            }
            if (direction == "ltr") {
                if (order[0].level == 1 && (m = str.match(/^\s+/))) {
                    order[0].from = m[0].length;
                    order.unshift(new BidiSpan(0, 0, m[0].length));
                }
                if (lst(order).level == 1 && (m = str.match(/\s+$/))) {
                    lst(order).to -= m[0].length;
                    order.push(new BidiSpan(0, len-m[0].length, len));
                }
            }

            return direction == "rtl" ? order.reverse() : order
        }
    })();

    // Get the bidi ordering for the given line (and cache it). Returns
    // false for lines that are fully left-to-right, and an array of
    // BidiSpan objects otherwise.
    function getOrder(line, direction) {
        var order = line.order;
        if (order == null) {
            order = line.order = bidiOrdering(line.text, direction);
        }
        return order
    }

    // EVENT HANDLING

    // Lightweight event framework. on/off also work on DOM nodes,
    // registering native DOM handlers.

    var noHandlers = [];

    var on = function (emitter, type, f) {
        if (emitter.addEventListener) {
            emitter.addEventListener(type, f, false);
        } else if (emitter.attachEvent) {
            emitter.attachEvent("on"+type, f);
        } else {
            var map = emitter._handlers || (emitter._handlers = {});
            map[type] = (map[type] || noHandlers).concat(f);
        }
    };

    function getHandlers(emitter, type) {
        return emitter._handlers && emitter._handlers[type] || noHandlers
    }

    function off(emitter, type, f) {
        if (emitter.removeEventListener) {
            emitter.removeEventListener(type, f, false);
        } else if (emitter.detachEvent) {
            emitter.detachEvent("on"+type, f);
        } else {
            var map = emitter._handlers, arr = map && map[type];
            if (arr) {
                var index = indexOf(arr, f);
                if (index > -1) {
                    map[type] = arr.slice(0, index).concat(arr.slice(index+1));
                }
            }
        }
    }

    function signal(emitter, type /*, values...*/) {
        var handlers = getHandlers(emitter, type);
        if (!handlers.length) {
            return
        }
        var args = Array.prototype.slice.call(arguments, 2);
        for (var i = 0; i < handlers.length; ++i) {
            handlers[i].apply(null, args);
        }
    }

    // The DOM events that CodeMirror handles can be overridden by
    // registering a (non-DOM) handler on the editor for the event name,
    // and preventDefault-ing the event in that handler.
    function signalDOMEvent(cm, e, override) {
        if (typeof e == "string") {
            e = {
                type: e, preventDefault: function () {
                    this.defaultPrevented = true;
                }
            };
        }
        signal(cm, override || e.type, cm, e);
        return e_defaultPrevented(e) || e.codemirrorIgnore
    }

    function signalCursorActivity(cm) {
        var arr = cm._handlers && cm._handlers.cursorActivity;
        if (!arr) {
            return
        }
        var set = cm.curOp.cursorActivityHandlers || (cm.curOp.cursorActivityHandlers = []);
        for (var i = 0; i < arr.length; ++i) {
            if (indexOf(set, arr[i]) == -1) {
                set.push(arr[i]);
            }
        }
    }

    function hasHandler(emitter, type) {
        return getHandlers(emitter, type).length > 0
    }

    // Add on and off methods to a constructor's prototype, to make
    // registering events on such objects more convenient.
    function eventMixin(ctor) {
        ctor.prototype.on = function (type, f) {
            on(this, type, f);
        };
        ctor.prototype.off = function (type, f) {
            off(this, type, f);
        };
    }

    // Due to the fact that we still support jurassic IE versions, some
    // compatibility wrappers are needed.

    function e_preventDefault(e) {
        if (e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false;
        }
    }

    function e_stopPropagation(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        } else {
            e.cancelBubble = true;
        }
    }

    function e_defaultPrevented(e) {
        return e.defaultPrevented != null ? e.defaultPrevented : e.returnValue == false
    }

    function e_stop(e) {
        e_preventDefault(e);
        e_stopPropagation(e);
    }

    function e_target(e) {
        return e.target || e.srcElement
    }

    function e_button(e) {
        var b = e.which;
        if (b == null) {
            if (e.button & 1) {
                b = 1;
            } else if (e.button & 2) {
                b = 3;
            } else if (e.button & 4) {
                b = 2;
            }
        }
        if (mac && e.ctrlKey && b == 1) {
            b = 3;
        }
        return b
    }

    // Detect drag-and-drop
    var dragAndDrop = function () {
        // There is *some* kind of drag-and-drop support in IE6-8, but I
        // couldn't get it to work yet.
        if (ie && ie_version < 9) {
            return false
        }
        var div = elt('div');
        return "draggable" in div || "dragDrop" in div
    }();

    var zwspSupported;

    function zeroWidthElement(measure) {
        if (zwspSupported == null) {
            var test = elt("span", "\u200b");
            removeChildrenAndAdd(measure, elt("span", [test, document.createTextNode("x")]));
            if (measure.firstChild.offsetHeight != 0) {
                zwspSupported = test.offsetWidth <= 1 && test.offsetHeight > 2 && !(ie && ie_version < 8);
            }
        }
        var node = zwspSupported ? elt("span", "\u200b") :
            elt("span", "\u00a0", null, "display: inline-block; width: 1px; margin-right: -1px");
        node.setAttribute("cm-text", "");
        return node
    }

    // Feature-detect IE's crummy client rect reporting for bidi text
    var badBidiRects;

    function hasBadBidiRects(measure) {
        if (badBidiRects != null) {
            return badBidiRects
        }
        var txt = removeChildrenAndAdd(measure, document.createTextNode("A\u062eA"));
        var r0 = range(txt, 0, 1).getBoundingClientRect();
        var r1 = range(txt, 1, 2).getBoundingClientRect();
        removeChildren(measure);
        if (!r0 || r0.left == r0.right) {
            return false
        } // Safari returns null in some cases (#2780)
        return badBidiRects = (r1.right-r0.right < 3)
    }

    // See if "".split is the broken IE version, if so, provide an
    // alternative way to split lines.
    var splitLinesAuto = "\n\nb".split(/\n/).length != 3 ? function (string) {
        var pos = 0, result = [], l = string.length;
        while (pos <= l) {
            var nl = string.indexOf("\n", pos);
            if (nl == -1) {
                nl = string.length;
            }
            var line = string.slice(pos, string.charAt(nl-1) == "\r" ? nl-1 : nl);
            var rt = line.indexOf("\r");
            if (rt != -1) {
                result.push(line.slice(0, rt));
                pos += rt+1;
            } else {
                result.push(line);
                pos = nl+1;
            }
        }
        return result
    } : function (string) {
        return string.split(/\r\n?|\n/);
    };

    var hasSelection = window.getSelection ? function (te) {
        try {
            return te.selectionStart != te.selectionEnd
        } catch (e) {
            return false
        }
    } : function (te) {
        var range;
        try {
            range = te.ownerDocument.selection.createRange();
        } catch (e) {
        }
        if (!range || range.parentElement() != te) {
            return false
        }
        return range.compareEndPoints("StartToEnd", range) != 0
    };

    var hasCopyEvent = (function () {
        var e = elt("div");
        if ("oncopy" in e) {
            return true
        }
        e.setAttribute("oncopy", "return;");
        return typeof e.oncopy == "function"
    })();

    var badZoomedRects = null;

    function hasBadZoomedRects(measure) {
        if (badZoomedRects != null) {
            return badZoomedRects
        }
        var node = removeChildrenAndAdd(measure, elt("span", "x"));
        var normal = node.getBoundingClientRect();
        var fromRange = range(node, 0, 1).getBoundingClientRect();
        return badZoomedRects = Math.abs(normal.left-fromRange.left) > 1
    }

    // Known modes, by name and by MIME
    var modes = {}, mimeModes = {};

    // Extra arguments are stored as the mode's dependencies, which is
    // used by (legacy) mechanisms like loadmode.js to automatically
    // load a mode. (Preferred mechanism is the require/define calls.)
    function defineMode(name, mode) {
        if (arguments.length > 2) {
            mode.dependencies = Array.prototype.slice.call(arguments, 2);
        }
        modes[name] = mode;
    }

    function defineMIME(mime, spec) {
        mimeModes[mime] = spec;
    }

    // Given a MIME type, a {name, ...options} config object, or a name
    // string, return a mode config object.
    function resolveMode(spec) {
        if (typeof spec == "string" && mimeModes.hasOwnProperty(spec)) {
            spec = mimeModes[spec];
        } else if (spec && typeof spec.name == "string" && mimeModes.hasOwnProperty(spec.name)) {
            var found = mimeModes[spec.name];
            if (typeof found == "string") {
                found = {name: found};
            }
            spec = createObj(found, spec);
            spec.name = found.name;
        } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+xml$/.test(spec)) {
            return resolveMode("application/xml")
        } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+json$/.test(spec)) {
            return resolveMode("application/json")
        }
        if (typeof spec == "string") {
            return {name: spec}
        } else {
            return spec || {name: "null"}
        }
    }

    // Given a mode spec (anything that resolveMode accepts), find and
    // initialize an actual mode object.
    function getMode(options, spec) {
        spec = resolveMode(spec);
        var mfactory = modes[spec.name];
        if (!mfactory) {
            return getMode(options, "text/plain")
        }
        var modeObj = mfactory(options, spec);
        if (modeExtensions.hasOwnProperty(spec.name)) {
            var exts = modeExtensions[spec.name];
            for (var prop in exts) {
                if (!exts.hasOwnProperty(prop)) {
                    continue
                }
                if (modeObj.hasOwnProperty(prop)) {
                    modeObj["_"+prop] = modeObj[prop];
                }
                modeObj[prop] = exts[prop];
            }
        }
        modeObj.name = spec.name;
        if (spec.helperType) {
            modeObj.helperType = spec.helperType;
        }
        if (spec.modeProps) {
            for (var prop$1 in spec.modeProps) {
                modeObj[prop$1] = spec.modeProps[prop$1];
            }
        }

        return modeObj
    }

    // This can be used to attach properties to mode objects from
    // outside the actual mode definition.
    var modeExtensions = {};

    function extendMode(mode, properties) {
        var exts = modeExtensions.hasOwnProperty(mode) ? modeExtensions[mode] : (modeExtensions[mode] = {});
        copyObj(properties, exts);
    }

    function copyState(mode, state) {
        if (state === true) {
            return state
        }
        if (mode.copyState) {
            return mode.copyState(state)
        }
        var nstate = {};
        for (var n in state) {
            var val = state[n];
            if (val instanceof Array) {
                val = val.concat([]);
            }
            nstate[n] = val;
        }
        return nstate
    }

    // Given a mode and a state (for that mode), find the inner mode and
    // state at the position that the state refers to.
    function innerMode(mode, state) {
        var info;
        while (mode.innerMode) {
            info = mode.innerMode(state);
            if (!info || info.mode == mode) {
                break
            }
            state = info.state;
            mode = info.mode;
        }
        return info || {mode: mode, state: state}
    }

    function startState(mode, a1, a2) {
        return mode.startState ? mode.startState(a1, a2) : true
    }

    // STRING STREAM

    // Fed to the mode parsers, provides helper functions to make
    // parsers more succinct.

    var StringStream = function (string, tabSize, lineOracle) {
        this.pos = this.start = 0;
        this.string = string;
        this.tabSize = tabSize || 8;
        this.lastColumnPos = this.lastColumnValue = 0;
        this.lineStart = 0;
        this.lineOracle = lineOracle;
    };

    StringStream.prototype.eol = function () {
        return this.pos >= this.string.length
    };
    StringStream.prototype.sol = function () {
        return this.pos == this.lineStart
    };
    StringStream.prototype.peek = function () {
        return this.string.charAt(this.pos) || undefined
    };
    StringStream.prototype.next = function () {
        if (this.pos < this.string.length) {
            return this.string.charAt(this.pos++)
        }
    };
    StringStream.prototype.eat = function (match) {
        var ch = this.string.charAt(this.pos);
        var ok;
        if (typeof match == "string") {
            ok = ch == match;
        } else {
            ok = ch && (match.test ? match.test(ch) : match(ch));
        }
        if (ok) {
            ++this.pos;
            return ch
        }
    };
    StringStream.prototype.eatWhile = function (match) {
        var start = this.pos;
        while (this.eat(match)) {
        }
        return this.pos > start
    };
    StringStream.prototype.eatSpace = function () {
        var start = this.pos;
        while (/[\s\u00a0]/.test(this.string.charAt(this.pos))) {
            ++this.pos;
        }
        return this.pos > start
    };
    StringStream.prototype.skipToEnd = function () {
        this.pos = this.string.length;
    };
    StringStream.prototype.skipTo = function (ch) {
        var found = this.string.indexOf(ch, this.pos);
        if (found > -1) {
            this.pos = found;
            return true
        }
    };
    StringStream.prototype.backUp = function (n) {
        this.pos -= n;
    };
    StringStream.prototype.column = function () {
        if (this.lastColumnPos < this.start) {
            this.lastColumnValue = countColumn(this.string, this.start, this.tabSize, this.lastColumnPos, this.lastColumnValue);
            this.lastColumnPos = this.start;
        }
        return this.lastColumnValue-(this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
    };
    StringStream.prototype.indentation = function () {
        return countColumn(this.string, null, this.tabSize)-
            (this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
    };
    StringStream.prototype.match = function (pattern, consume, caseInsensitive) {
        if (typeof pattern == "string") {
            var cased = function (str) {
                return caseInsensitive ? str.toLowerCase() : str;
            };
            var substr = this.string.substr(this.pos, pattern.length);
            if (cased(substr) == cased(pattern)) {
                if (consume !== false) {
                    this.pos += pattern.length;
                }
                return true
            }
        } else {
            var match = this.string.slice(this.pos).match(pattern);
            if (match && match.index > 0) {
                return null
            }
            if (match && consume !== false) {
                this.pos += match[0].length;
            }
            return match
        }
    };
    StringStream.prototype.current = function () {
        return this.string.slice(this.start, this.pos)
    };
    StringStream.prototype.hideFirstChars = function (n, inner) {
        this.lineStart += n;
        try {
            return inner()
        } finally {
            this.lineStart -= n;
        }
    };
    StringStream.prototype.lookAhead = function (n) {
        var oracle = this.lineOracle;
        return oracle && oracle.lookAhead(n)
    };
    StringStream.prototype.baseToken = function () {
        var oracle = this.lineOracle;
        return oracle && oracle.baseToken(this.pos)
    };

    // Find the line object corresponding to the given line number.
    function getLine(doc, n) {
        n -= doc.first;
        if (n < 0 || n >= doc.size) {
            throw new Error("There is no line "+(n+doc.first)+" in the document.")
        }
        var chunk = doc;
        while (!chunk.lines) {
            for (var i = 0; ; ++i) {
                var child = chunk.children[i], sz = child.chunkSize();
                if (n < sz) {
                    chunk = child;
                    break
                }
                n -= sz;
            }
        }
        return chunk.lines[n]
    }

    // Get the part of a document between two positions, as an array of
    // strings.
    function getBetween(doc, start, end) {
        var out = [], n = start.line;
        doc.iter(start.line, end.line+1, function (line) {
            var text = line.text;
            if (n == end.line) {
                text = text.slice(0, end.ch);
            }
            if (n == start.line) {
                text = text.slice(start.ch);
            }
            out.push(text);
            ++n;
        });
        return out
    }

    // Get the lines between from and to, as array of strings.
    function getLines(doc, from, to) {
        var out = [];
        doc.iter(from, to, function (line) {
            out.push(line.text);
        }); // iter aborts when callback returns truthy value
        return out
    }

    // Update the height of a line, propagating the height change
    // upwards to parent nodes.
    function updateLineHeight(line, height) {
        var diff = height-line.height;
        if (diff) {
            for (var n = line; n; n = n.parent) {
                n.height += diff;
            }
        }
    }

    // Given a line object, find its line number by walking up through
    // its parent links.
    function lineNo(line) {
        if (line.parent == null) {
            return null
        }
        var cur = line.parent, no = indexOf(cur.lines, line);
        for (var chunk = cur.parent; chunk; cur = chunk, chunk = chunk.parent) {
            for (var i = 0; ; ++i) {
                if (chunk.children[i] == cur) {
                    break
                }
                no += chunk.children[i].chunkSize();
            }
        }
        return no+cur.first
    }

    // Find the line at the given vertical position, using the height
    // information in the document tree.
    function lineAtHeight(chunk, h) {
        var n = chunk.first;
        outer: do {
            for (var i$1 = 0; i$1 < chunk.children.length; ++i$1) {
                var child = chunk.children[i$1], ch = child.height;
                if (h < ch) {
                    chunk = child;
                    continue outer
                }
                h -= ch;
                n += child.chunkSize();
            }
            return n
        } while (!chunk.lines)
        var i = 0;
        for (; i < chunk.lines.length; ++i) {
            var line = chunk.lines[i], lh = line.height;
            if (h < lh) {
                break
            }
            h -= lh;
        }
        return n+i
    }

    function isLine(doc, l) {
        return l >= doc.first && l < doc.first+doc.size
    }

    function lineNumberFor(options, i) {
        return String(options.lineNumberFormatter(i+options.firstLineNumber))
    }

    // A Pos instance represents a position within the text.
    function Pos(line, ch, sticky) {
        if (sticky === void 0) sticky = null;

        if (!(this instanceof Pos)) {
            return new Pos(line, ch, sticky)
        }
        this.line = line;
        this.ch = ch;
        this.sticky = sticky;
    }

    // Compare two positions, return 0 if they are the same, a negative
    // number when a is less, and a positive number otherwise.
    function cmp(a, b) {
        return a.line-b.line || a.ch-b.ch
    }

    function equalCursorPos(a, b) {
        return a.sticky == b.sticky && cmp(a, b) == 0
    }

    function copyPos(x) {
        return Pos(x.line, x.ch)
    }

    function maxPos(a, b) {
        return cmp(a, b) < 0 ? b : a
    }

    function minPos(a, b) {
        return cmp(a, b) < 0 ? a : b
    }

    // Most of the external API clips given positions to make sure they
    // actually exist within the document.
    function clipLine(doc, n) {
        return Math.max(doc.first, Math.min(n, doc.first+doc.size-1))
    }

    function clipPos(doc, pos) {
        if (pos.line < doc.first) {
            return Pos(doc.first, 0)
        }
        var last = doc.first+doc.size-1;
        if (pos.line > last) {
            return Pos(last, getLine(doc, last).text.length)
        }
        return clipToLen(pos, getLine(doc, pos.line).text.length)
    }

    function clipToLen(pos, linelen) {
        var ch = pos.ch;
        if (ch == null || ch > linelen) {
            return Pos(pos.line, linelen)
        } else if (ch < 0) {
            return Pos(pos.line, 0)
        } else {
            return pos
        }
    }

    function clipPosArray(doc, array) {
        var out = [];
        for (var i = 0; i < array.length; i++) {
            out[i] = clipPos(doc, array[i]);
        }
        return out
    }

    var SavedContext = function (state, lookAhead) {
        this.state = state;
        this.lookAhead = lookAhead;
    };

    var Context = function (doc, state, line, lookAhead) {
        this.state = state;
        this.doc = doc;
        this.line = line;
        this.maxLookAhead = lookAhead || 0;
        this.baseTokens = null;
        this.baseTokenPos = 1;
    };

    Context.prototype.lookAhead = function (n) {
        var line = this.doc.getLine(this.line+n);
        if (line != null && n > this.maxLookAhead) {
            this.maxLookAhead = n;
        }
        return line
    };

    Context.prototype.baseToken = function (n) {
        if (!this.baseTokens) {
            return null
        }
        while (this.baseTokens[this.baseTokenPos] <= n) {
            this.baseTokenPos += 2;
        }
        var type = this.baseTokens[this.baseTokenPos+1];
        return {
            type: type && type.replace(/( |^)overlay .*/, ""),
            size: this.baseTokens[this.baseTokenPos]-n
        }
    };

    Context.prototype.nextLine = function () {
        this.line++;
        if (this.maxLookAhead > 0) {
            this.maxLookAhead--;
        }
    };

    Context.fromSaved = function (doc, saved, line) {
        if (saved instanceof SavedContext) {
            return new Context(doc, copyState(doc.mode, saved.state), line, saved.lookAhead)
        } else {
            return new Context(doc, copyState(doc.mode, saved), line)
        }
    };

    Context.prototype.save = function (copy) {
        var state = copy !== false ? copyState(this.doc.mode, this.state) : this.state;
        return this.maxLookAhead > 0 ? new SavedContext(state, this.maxLookAhead) : state
    };


    // Compute a style array (an array starting with a mode generation
    // -- for invalidation -- followed by pairs of end positions and
    // style strings), which is used to highlight the tokens on the
    // line.
    function highlightLine(cm, line, context, forceToEnd) {
        // A styles array always starts with a number identifying the
        // mode/overlays that it is based on (for easy invalidation).
        var st = [cm.state.modeGen], lineClasses = {};
        // Compute the base array of styles
        runMode(cm, line.text, cm.doc.mode, context, function (end, style) {
                return st.push(end, style);
            },
            lineClasses, forceToEnd);
        var state = context.state;

        // Run overlays, adjust style array.
        var loop = function (o) {
            context.baseTokens = st;
            var overlay = cm.state.overlays[o], i = 1, at = 0;
            context.state = true;
            runMode(cm, line.text, overlay.mode, context, function (end, style) {
                var start = i;
                // Ensure there's a token end at the current position, and that i points at it
                while (at < end) {
                    var i_end = st[i];
                    if (i_end > end) {
                        st.splice(i, 1, end, st[i+1], i_end);
                    }
                    i += 2;
                    at = Math.min(end, i_end);
                }
                if (!style) {
                    return
                }
                if (overlay.opaque) {
                    st.splice(start, i-start, end, "overlay "+style);
                    i = start+2;
                } else {
                    for (; start < i; start += 2) {
                        var cur = st[start+1];
                        st[start+1] = (cur ? cur+" " : "")+"overlay "+style;
                    }
                }
            }, lineClasses);
            context.state = state;
            context.baseTokens = null;
            context.baseTokenPos = 1;
        };

        for (var o = 0; o < cm.state.overlays.length; ++o) loop(o);

        return {styles: st, classes: lineClasses.bgClass || lineClasses.textClass ? lineClasses : null}
    }

    function getLineStyles(cm, line, updateFrontier) {
        if (!line.styles || line.styles[0] != cm.state.modeGen) {
            var context = getContextBefore(cm, lineNo(line));
            var resetState = line.text.length > cm.options.maxHighlightLength && copyState(cm.doc.mode, context.state);
            var result = highlightLine(cm, line, context);
            if (resetState) {
                context.state = resetState;
            }
            line.stateAfter = context.save(!resetState);
            line.styles = result.styles;
            if (result.classes) {
                line.styleClasses = result.classes;
            } else if (line.styleClasses) {
                line.styleClasses = null;
            }
            if (updateFrontier === cm.doc.highlightFrontier) {
                cm.doc.modeFrontier = Math.max(cm.doc.modeFrontier, ++cm.doc.highlightFrontier);
            }
        }
        return line.styles
    }

    function getContextBefore(cm, n, precise) {
        var doc = cm.doc, display = cm.display;
        if (!doc.mode.startState) {
            return new Context(doc, true, n)
        }
        var start = findStartLine(cm, n, precise);
        var saved = start > doc.first && getLine(doc, start-1).stateAfter;
        var context = saved ? Context.fromSaved(doc, saved, start) : new Context(doc, startState(doc.mode), start);

        doc.iter(start, n, function (line) {
            processLine(cm, line.text, context);
            var pos = context.line;
            line.stateAfter = pos == n-1 || pos % 5 == 0 || pos >= display.viewFrom && pos < display.viewTo ? context.save() : null;
            context.nextLine();
        });
        if (precise) {
            doc.modeFrontier = context.line;
        }
        return context
    }

    // Lightweight form of highlight -- proceed over this line and
    // update state, but don't save a style array. Used for lines that
    // aren't currently visible.
    function processLine(cm, text, context, startAt) {
        var mode = cm.doc.mode;
        var stream = new StringStream(text, cm.options.tabSize, context);
        stream.start = stream.pos = startAt || 0;
        if (text == "") {
            callBlankLine(mode, context.state);
        }
        while (!stream.eol()) {
            readToken(mode, stream, context.state);
            stream.start = stream.pos;
        }
    }

    function callBlankLine(mode, state) {
        if (mode.blankLine) {
            return mode.blankLine(state)
        }
        if (!mode.innerMode) {
            return
        }
        var inner = innerMode(mode, state);
        if (inner.mode.blankLine) {
            return inner.mode.blankLine(inner.state)
        }
    }

    function readToken(mode, stream, state, inner) {
        for (var i = 0; i < 10; i++) {
            if (inner) {
                inner[0] = innerMode(mode, state).mode;
            }
            var style = mode.token(stream, state);
            if (stream.pos > stream.start) {
                return style
            }
        }
        throw new Error("Mode "+mode.name+" failed to advance stream.")
    }

    var Token = function (stream, type, state) {
        this.start = stream.start;
        this.end = stream.pos;
        this.string = stream.current();
        this.type = type || null;
        this.state = state;
    };

    // Utility for getTokenAt and getLineTokens
    function takeToken(cm, pos, precise, asArray) {
        var doc = cm.doc, mode = doc.mode, style;
        pos = clipPos(doc, pos);
        var line = getLine(doc, pos.line), context = getContextBefore(cm, pos.line, precise);
        var stream = new StringStream(line.text, cm.options.tabSize, context), tokens;
        if (asArray) {
            tokens = [];
        }
        while ((asArray || stream.pos < pos.ch) && !stream.eol()) {
            stream.start = stream.pos;
            style = readToken(mode, stream, context.state);
            if (asArray) {
                tokens.push(new Token(stream, style, copyState(doc.mode, context.state)));
            }
        }
        return asArray ? tokens : new Token(stream, style, context.state)
    }

    function extractLineClasses(type, output) {
        if (type) {
            for (; ;) {
                var lineClass = type.match(/(?:^|\s+)line-(background-)?(\S+)/);
                if (!lineClass) {
                    break
                }
                type = type.slice(0, lineClass.index)+type.slice(lineClass.index+lineClass[0].length);
                var prop = lineClass[1] ? "bgClass" : "textClass";
                if (output[prop] == null) {
                    output[prop] = lineClass[2];
                } else if (!(new RegExp("(?:^|\\s)"+lineClass[2]+"(?:$|\\s)")).test(output[prop])) {
                    output[prop] += " "+lineClass[2];
                }
            }
        }
        return type
    }

    // Run the given mode's parser over a line, calling f for each token.
    function runMode(cm, text, mode, context, f, lineClasses, forceToEnd) {
        var flattenSpans = mode.flattenSpans;
        if (flattenSpans == null) {
            flattenSpans = cm.options.flattenSpans;
        }
        var curStart = 0, curStyle = null;
        var stream = new StringStream(text, cm.options.tabSize, context), style;
        var inner = cm.options.addModeClass && [null];
        if (text == "") {
            extractLineClasses(callBlankLine(mode, context.state), lineClasses);
        }
        while (!stream.eol()) {
            if (stream.pos > cm.options.maxHighlightLength) {
                flattenSpans = false;
                if (forceToEnd) {
                    processLine(cm, text, context, stream.pos);
                }
                stream.pos = text.length;
                style = null;
            } else {
                style = extractLineClasses(readToken(mode, stream, context.state, inner), lineClasses);
            }
            if (inner) {
                var mName = inner[0].name;
                if (mName) {
                    style = "m-"+(style ? mName+" "+style : mName);
                }
            }
            if (!flattenSpans || curStyle != style) {
                while (curStart < stream.start) {
                    curStart = Math.min(stream.start, curStart+5000);
                    f(curStart, curStyle);
                }
                curStyle = style;
            }
            stream.start = stream.pos;
        }
        while (curStart < stream.pos) {
            // Webkit seems to refuse to render text nodes longer than 57444
            // characters, and returns inaccurate measurements in nodes
            // starting around 5000 chars.
            var pos = Math.min(stream.pos, curStart+5000);
            f(pos, curStyle);
            curStart = pos;
        }
    }

    // Finds the line to start with when starting a parse. Tries to
    // find a line with a stateAfter, so that it can start with a
    // valid state. If that fails, it returns the line with the
    // smallest indentation, which tends to need the least context to
    // parse correctly.
    function findStartLine(cm, n, precise) {
        var minindent, minline, doc = cm.doc;
        var lim = precise ? -1 : n-(cm.doc.mode.innerMode ? 1000 : 100);
        for (var search = n; search > lim; --search) {
            if (search <= doc.first) {
                return doc.first
            }
            var line = getLine(doc, search-1), after = line.stateAfter;
            if (after && (!precise || search+(after instanceof SavedContext ? after.lookAhead : 0) <= doc.modeFrontier)) {
                return search
            }
            var indented = countColumn(line.text, null, cm.options.tabSize);
            if (minline == null || minindent > indented) {
                minline = search-1;
                minindent = indented;
            }
        }
        return minline
    }

    function retreatFrontier(doc, n) {
        doc.modeFrontier = Math.min(doc.modeFrontier, n);
        if (doc.highlightFrontier < n-10) {
            return
        }
        var start = doc.first;
        for (var line = n-1; line > start; line--) {
            var saved = getLine(doc, line).stateAfter;
            // change is on 3
            // state on line 1 looked ahead 2 -- so saw 3
            // test 1 + 2 < 3 should cover this
            if (saved && (!(saved instanceof SavedContext) || line+saved.lookAhead < n)) {
                start = line+1;
                break
            }
        }
        doc.highlightFrontier = Math.min(doc.highlightFrontier, start);
    }

    // Optimize some code when these features are not used.
    var sawReadOnlySpans = false, sawCollapsedSpans = false;

    function seeReadOnlySpans() {
        sawReadOnlySpans = true;
    }

    function seeCollapsedSpans() {
        sawCollapsedSpans = true;
    }

    // TEXTMARKER SPANS

    function MarkedSpan(marker, from, to) {
        this.marker = marker;
        this.from = from;
        this.to = to;
    }

    // Search an array of spans for a span matching the given marker.
    function getMarkedSpanFor(spans, marker) {
        if (spans) {
            for (var i = 0; i < spans.length; ++i) {
                var span = spans[i];
                if (span.marker == marker) {
                    return span
                }
            }
        }
    }

    // Remove a span from an array, returning undefined if no spans are
    // left (we don't store arrays for lines without spans).
    function removeMarkedSpan(spans, span) {
        var r;
        for (var i = 0; i < spans.length; ++i) {
            if (spans[i] != span) {
                (r || (r = [])).push(spans[i]);
            }
        }
        return r
    }

    // Add a span to a line.
    function addMarkedSpan(line, span, op) {
        var inThisOp = op && window.WeakSet && (op.markedSpans || (op.markedSpans = new WeakSet));
        if (inThisOp && line.markedSpans && inThisOp.has(line.markedSpans)) {
            line.markedSpans.push(span);
        } else {
            line.markedSpans = line.markedSpans ? line.markedSpans.concat([span]) : [span];
            if (inThisOp) {
                inThisOp.add(line.markedSpans);
            }
        }
        span.marker.attachLine(line);
    }

    // Used for the algorithm that adjusts markers for a change in the
    // document. These functions cut an array of spans at a given
    // character position, returning an array of remaining chunks (or
    // undefined if nothing remains).
    function markedSpansBefore(old, startCh, isInsert) {
        var nw;
        if (old) {
            for (var i = 0; i < old.length; ++i) {
                var span = old[i], marker = span.marker;
                var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= startCh : span.from < startCh);
                if (startsBefore || span.from == startCh && marker.type == "bookmark" && (!isInsert || !span.marker.insertLeft)) {
                    var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= startCh : span.to > startCh)
                    ;(nw || (nw = [])).push(new MarkedSpan(marker, span.from, endsAfter ? null : span.to));
                }
            }
        }
        return nw
    }

    function markedSpansAfter(old, endCh, isInsert) {
        var nw;
        if (old) {
            for (var i = 0; i < old.length; ++i) {
                var span = old[i], marker = span.marker;
                var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= endCh : span.to > endCh);
                if (endsAfter || span.from == endCh && marker.type == "bookmark" && (!isInsert || span.marker.insertLeft)) {
                    var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= endCh : span.from < endCh)
                    ;(nw || (nw = [])).push(new MarkedSpan(marker, startsBefore ? null : span.from-endCh,
                        span.to == null ? null : span.to-endCh));
                }
            }
        }
        return nw
    }

    // Given a change object, compute the new set of marker spans that
    // cover the line in which the change took place. Removes spans
    // entirely within the change, reconnects spans belonging to the
    // same marker that appear on both sides of the change, and cuts off
    // spans partially within the change. Returns an array of span
    // arrays with one element for each line in (after) the change.
    function stretchSpansOverChange(doc, change) {
        if (change.full) {
            return null
        }
        var oldFirst = isLine(doc, change.from.line) && getLine(doc, change.from.line).markedSpans;
        var oldLast = isLine(doc, change.to.line) && getLine(doc, change.to.line).markedSpans;
        if (!oldFirst && !oldLast) {
            return null
        }

        var startCh = change.from.ch, endCh = change.to.ch, isInsert = cmp(change.from, change.to) == 0;
        // Get the spans that 'stick out' on both sides
        var first = markedSpansBefore(oldFirst, startCh, isInsert);
        var last = markedSpansAfter(oldLast, endCh, isInsert);

        // Next, merge those two ends
        var sameLine = change.text.length == 1, offset = lst(change.text).length+(sameLine ? startCh : 0);
        if (first) {
            // Fix up .to properties of first
            for (var i = 0; i < first.length; ++i) {
                var span = first[i];
                if (span.to == null) {
                    var found = getMarkedSpanFor(last, span.marker);
                    if (!found) {
                        span.to = startCh;
                    } else if (sameLine) {
                        span.to = found.to == null ? null : found.to+offset;
                    }
                }
            }
        }
        if (last) {
            // Fix up .from in last (or move them into first in case of sameLine)
            for (var i$1 = 0; i$1 < last.length; ++i$1) {
                var span$1 = last[i$1];
                if (span$1.to != null) {
                    span$1.to += offset;
                }
                if (span$1.from == null) {
                    var found$1 = getMarkedSpanFor(first, span$1.marker);
                    if (!found$1) {
                        span$1.from = offset;
                        if (sameLine) {
                            (first || (first = [])).push(span$1);
                        }
                    }
                } else {
                    span$1.from += offset;
                    if (sameLine) {
                        (first || (first = [])).push(span$1);
                    }
                }
            }
        }
        // Make sure we didn't create any zero-length spans
        if (first) {
            first = clearEmptySpans(first);
        }
        if (last && last != first) {
            last = clearEmptySpans(last);
        }

        var newMarkers = [first];
        if (!sameLine) {
            // Fill gap with whole-line-spans
            var gap = change.text.length-2, gapMarkers;
            if (gap > 0 && first) {
                for (var i$2 = 0; i$2 < first.length; ++i$2) {
                    if (first[i$2].to == null) {
                        (gapMarkers || (gapMarkers = [])).push(new MarkedSpan(first[i$2].marker, null, null));
                    }
                }
            }
            for (var i$3 = 0; i$3 < gap; ++i$3) {
                newMarkers.push(gapMarkers);
            }
            newMarkers.push(last);
        }
        return newMarkers
    }

    // Remove spans that are empty and don't have a clearWhenEmpty
    // option of false.
    function clearEmptySpans(spans) {
        for (var i = 0; i < spans.length; ++i) {
            var span = spans[i];
            if (span.from != null && span.from == span.to && span.marker.clearWhenEmpty !== false) {
                spans.splice(i--, 1);
            }
        }
        if (!spans.length) {
            return null
        }
        return spans
    }

    // Used to 'clip' out readOnly ranges when making a change.
    function removeReadOnlyRanges(doc, from, to) {
        var markers = null;
        doc.iter(from.line, to.line+1, function (line) {
            if (line.markedSpans) {
                for (var i = 0; i < line.markedSpans.length; ++i) {
                    var mark = line.markedSpans[i].marker;
                    if (mark.readOnly && (!markers || indexOf(markers, mark) == -1)) {
                        (markers || (markers = [])).push(mark);
                    }
                }
            }
        });
        if (!markers) {
            return null
        }
        var parts = [{from: from, to: to}];
        for (var i = 0; i < markers.length; ++i) {
            var mk = markers[i], m = mk.find(0);
            for (var j = 0; j < parts.length; ++j) {
                var p = parts[j];
                if (cmp(p.to, m.from) < 0 || cmp(p.from, m.to) > 0) {
                    continue
                }
                var newParts = [j, 1], dfrom = cmp(p.from, m.from), dto = cmp(p.to, m.to);
                if (dfrom < 0 || !mk.inclusiveLeft && !dfrom) {
                    newParts.push({from: p.from, to: m.from});
                }
                if (dto > 0 || !mk.inclusiveRight && !dto) {
                    newParts.push({from: m.to, to: p.to});
                }
                parts.splice.apply(parts, newParts);
                j += newParts.length-3;
            }
        }
        return parts
    }

    // Connect or disconnect spans from a line.
    function detachMarkedSpans(line) {
        var spans = line.markedSpans;
        if (!spans) {
            return
        }
        for (var i = 0; i < spans.length; ++i) {
            spans[i].marker.detachLine(line);
        }
        line.markedSpans = null;
    }

    function attachMarkedSpans(line, spans) {
        if (!spans) {
            return
        }
        for (var i = 0; i < spans.length; ++i) {
            spans[i].marker.attachLine(line);
        }
        line.markedSpans = spans;
    }

    // Helpers used when computing which overlapping collapsed span
    // counts as the larger one.
    function extraLeft(marker) {
        return marker.inclusiveLeft ? -1 : 0
    }

    function extraRight(marker) {
        return marker.inclusiveRight ? 1 : 0
    }

    // Returns a number indicating which of two overlapping collapsed
    // spans is larger (and thus includes the other). Falls back to
    // comparing ids when the spans cover exactly the same range.
    function compareCollapsedMarkers(a, b) {
        var lenDiff = a.lines.length-b.lines.length;
        if (lenDiff != 0) {
            return lenDiff
        }
        var aPos = a.find(), bPos = b.find();
        var fromCmp = cmp(aPos.from, bPos.from) || extraLeft(a)-extraLeft(b);
        if (fromCmp) {
            return -fromCmp
        }
        var toCmp = cmp(aPos.to, bPos.to) || extraRight(a)-extraRight(b);
        if (toCmp) {
            return toCmp
        }
        return b.id-a.id
    }

    // Find out whether a line ends or starts in a collapsed span. If
    // so, return the marker for that span.
    function collapsedSpanAtSide(line, start) {
        var sps = sawCollapsedSpans && line.markedSpans, found;
        if (sps) {
            for (var sp = (void 0), i = 0; i < sps.length; ++i) {
                sp = sps[i];
                if (sp.marker.collapsed && (start ? sp.from : sp.to) == null &&
                    (!found || compareCollapsedMarkers(found, sp.marker) < 0)) {
                    found = sp.marker;
                }
            }
        }
        return found
    }

    function collapsedSpanAtStart(line) {
        return collapsedSpanAtSide(line, true)
    }

    function collapsedSpanAtEnd(line) {
        return collapsedSpanAtSide(line, false)
    }

    function collapsedSpanAround(line, ch) {
        var sps = sawCollapsedSpans && line.markedSpans, found;
        if (sps) {
            for (var i = 0; i < sps.length; ++i) {
                var sp = sps[i];
                if (sp.marker.collapsed && (sp.from == null || sp.from < ch) && (sp.to == null || sp.to > ch) &&
                    (!found || compareCollapsedMarkers(found, sp.marker) < 0)) {
                    found = sp.marker;
                }
            }
        }
        return found
    }

    // Test whether there exists a collapsed span that partially
    // overlaps (covers the start or end, but not both) of a new span.
    // Such overlap is not allowed.
    function conflictingCollapsedRange(doc, lineNo, from, to, marker) {
        var line = getLine(doc, lineNo);
        var sps = sawCollapsedSpans && line.markedSpans;
        if (sps) {
            for (var i = 0; i < sps.length; ++i) {
                var sp = sps[i];
                if (!sp.marker.collapsed) {
                    continue
                }
                var found = sp.marker.find(0);
                var fromCmp = cmp(found.from, from) || extraLeft(sp.marker)-extraLeft(marker);
                var toCmp = cmp(found.to, to) || extraRight(sp.marker)-extraRight(marker);
                if (fromCmp >= 0 && toCmp <= 0 || fromCmp <= 0 && toCmp >= 0) {
                    continue
                }
                if (fromCmp <= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.to, from) >= 0 : cmp(found.to, from) > 0) ||
                    fromCmp >= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.from, to) <= 0 : cmp(found.from, to) < 0)) {
                    return true
                }
            }
        }
    }

    // A visual line is a line as drawn on the screen. Folding, for
    // example, can cause multiple logical lines to appear on the same
    // visual line. This finds the start of the visual line that the
    // given line is part of (usually that is the line itself).
    function visualLine(line) {
        var merged;
        while (merged = collapsedSpanAtStart(line)) {
            line = merged.find(-1, true).line;
        }
        return line
    }

    function visualLineEnd(line) {
        var merged;
        while (merged = collapsedSpanAtEnd(line)) {
            line = merged.find(1, true).line;
        }
        return line
    }

    // Returns an array of logical lines that continue the visual line
    // started by the argument, or undefined if there are no such lines.
    function visualLineContinued(line) {
        var merged, lines;
        while (merged = collapsedSpanAtEnd(line)) {
            line = merged.find(1, true).line
            ;(lines || (lines = [])).push(line);
        }
        return lines
    }

    // Get the line number of the start of the visual line that the
    // given line number is part of.
    function visualLineNo(doc, lineN) {
        var line = getLine(doc, lineN), vis = visualLine(line);
        if (line == vis) {
            return lineN
        }
        return lineNo(vis)
    }

    // Get the line number of the start of the next visual line after
    // the given line.
    function visualLineEndNo(doc, lineN) {
        if (lineN > doc.lastLine()) {
            return lineN
        }
        var line = getLine(doc, lineN), merged;
        if (!lineIsHidden(doc, line)) {
            return lineN
        }
        while (merged = collapsedSpanAtEnd(line)) {
            line = merged.find(1, true).line;
        }
        return lineNo(line)+1
    }

    // Compute whether a line is hidden. Lines count as hidden when they
    // are part of a visual line that starts with another line, or when
    // they are entirely covered by collapsed, non-widget span.
    function lineIsHidden(doc, line) {
        var sps = sawCollapsedSpans && line.markedSpans;
        if (sps) {
            for (var sp = (void 0), i = 0; i < sps.length; ++i) {
                sp = sps[i];
                if (!sp.marker.collapsed) {
                    continue
                }
                if (sp.from == null) {
                    return true
                }
                if (sp.marker.widgetNode) {
                    continue
                }
                if (sp.from == 0 && sp.marker.inclusiveLeft && lineIsHiddenInner(doc, line, sp)) {
                    return true
                }
            }
        }
    }

    function lineIsHiddenInner(doc, line, span) {
        if (span.to == null) {
            var end = span.marker.find(1, true);
            return lineIsHiddenInner(doc, end.line, getMarkedSpanFor(end.line.markedSpans, span.marker))
        }
        if (span.marker.inclusiveRight && span.to == line.text.length) {
            return true
        }
        for (var sp = (void 0), i = 0; i < line.markedSpans.length; ++i) {
            sp = line.markedSpans[i];
            if (sp.marker.collapsed && !sp.marker.widgetNode && sp.from == span.to &&
                (sp.to == null || sp.to != span.from) &&
                (sp.marker.inclusiveLeft || span.marker.inclusiveRight) &&
                lineIsHiddenInner(doc, line, sp)) {
                return true
            }
        }
    }

    // Find the height above the given line.
    function heightAtLine(lineObj) {
        lineObj = visualLine(lineObj);

        var h = 0, chunk = lineObj.parent;
        for (var i = 0; i < chunk.lines.length; ++i) {
            var line = chunk.lines[i];
            if (line == lineObj) {
                break
            } else {
                h += line.height;
            }
        }
        for (var p = chunk.parent; p; chunk = p, p = chunk.parent) {
            for (var i$1 = 0; i$1 < p.children.length; ++i$1) {
                var cur = p.children[i$1];
                if (cur == chunk) {
                    break
                } else {
                    h += cur.height;
                }
            }
        }
        return h
    }

    // Compute the character length of a line, taking into account
    // collapsed ranges (see markText) that might hide parts, and join
    // other lines onto it.
    function lineLength(line) {
        if (line.height == 0) {
            return 0
        }
        var len = line.text.length, merged, cur = line;
        while (merged = collapsedSpanAtStart(cur)) {
            var found = merged.find(0, true);
            cur = found.from.line;
            len += found.from.ch-found.to.ch;
        }
        cur = line;
        while (merged = collapsedSpanAtEnd(cur)) {
            var found$1 = merged.find(0, true);
            len -= cur.text.length-found$1.from.ch;
            cur = found$1.to.line;
            len += cur.text.length-found$1.to.ch;
        }
        return len
    }

    // Find the longest line in the document.
    function findMaxLine(cm) {
        var d = cm.display, doc = cm.doc;
        d.maxLine = getLine(doc, doc.first);
        d.maxLineLength = lineLength(d.maxLine);
        d.maxLineChanged = true;
        doc.iter(function (line) {
            var len = lineLength(line);
            if (len > d.maxLineLength) {
                d.maxLineLength = len;
                d.maxLine = line;
            }
        });
    }

    // LINE DATA STRUCTURE

    // Line objects. These hold state related to a line, including
    // highlighting info (the styles array).
    var Line = function (text, markedSpans, estimateHeight) {
        this.text = text;
        attachMarkedSpans(this, markedSpans);
        this.height = estimateHeight ? estimateHeight(this) : 1;
    };

    Line.prototype.lineNo = function () {
        return lineNo(this)
    };
    eventMixin(Line);

    // Change the content (text, markers) of a line. Automatically
    // invalidates cached information and tries to re-estimate the
    // line's height.
    function updateLine(line, text, markedSpans, estimateHeight) {
        line.text = text;
        if (line.stateAfter) {
            line.stateAfter = null;
        }
        if (line.styles) {
            line.styles = null;
        }
        if (line.order != null) {
            line.order = null;
        }
        detachMarkedSpans(line);
        attachMarkedSpans(line, markedSpans);
        var estHeight = estimateHeight ? estimateHeight(line) : 1;
        if (estHeight != line.height) {
            updateLineHeight(line, estHeight);
        }
    }

    // Detach a line from the document tree and its markers.
    function cleanUpLine(line) {
        line.parent = null;
        detachMarkedSpans(line);
    }

    // Convert a style as returned by a mode (either null, or a string
    // containing one or more styles) to a CSS style. This is cached,
    // and also looks for line-wide styles.
    var styleToClassCache = {}, styleToClassCacheWithMode = {};

    function interpretTokenStyle(style, options) {
        if (!style || /^\s*$/.test(style)) {
            return null
        }
        var cache = options.addModeClass ? styleToClassCacheWithMode : styleToClassCache;
        return cache[style] ||
            (cache[style] = style.replace(/\S+/g, "cm-$&"))
    }

    // Render the DOM representation of the text of a line. Also builds
    // up a 'line map', which points at the DOM nodes that represent
    // specific stretches of text, and is used by the measuring code.
    // The returned object contains the DOM node, this map, and
    // information about line-wide styles that were set by the mode.
    function buildLineContent(cm, lineView) {
        // The padding-right forces the element to have a 'border', which
        // is needed on Webkit to be able to get line-level bounding
        // rectangles for it (in measureChar).
        var content = eltP("span", null, null, webkit ? "padding-right: .1px" : null);
        var builder = {
            pre: eltP("pre", [content], "CodeMirror-line"), content: content,
            col: 0, pos: 0, cm: cm,
            trailingSpace: false,
            splitSpaces: cm.getOption("lineWrapping")
        };
        lineView.measure = {};

        // Iterate over the logical lines that make up this visual line.
        for (var i = 0; i <= (lineView.rest ? lineView.rest.length : 0); i++) {
            var line = i ? lineView.rest[i-1] : lineView.line, order = (void 0);
            builder.pos = 0;
            builder.addToken = buildToken;
            // Optionally wire in some hacks into the token-rendering
            // algorithm, to deal with browser quirks.
            if (hasBadBidiRects(cm.display.measure) && (order = getOrder(line, cm.doc.direction))) {
                builder.addToken = buildTokenBadBidi(builder.addToken, order);
            }
            builder.map = [];
            var allowFrontierUpdate = lineView != cm.display.externalMeasured && lineNo(line);
            insertLineContent(line, builder, getLineStyles(cm, line, allowFrontierUpdate));
            if (line.styleClasses) {
                if (line.styleClasses.bgClass) {
                    builder.bgClass = joinClasses(line.styleClasses.bgClass, builder.bgClass || "");
                }
                if (line.styleClasses.textClass) {
                    builder.textClass = joinClasses(line.styleClasses.textClass, builder.textClass || "");
                }
            }

            // Ensure at least a single node is present, for measuring.
            if (builder.map.length == 0) {
                builder.map.push(0, 0, builder.content.appendChild(zeroWidthElement(cm.display.measure)));
            }

            // Store the map and a cache object for the current logical line
            if (i == 0) {
                lineView.measure.map = builder.map;
                lineView.measure.cache = {};
            } else {
                (lineView.measure.maps || (lineView.measure.maps = [])).push(builder.map)
                ;(lineView.measure.caches || (lineView.measure.caches = [])).push({});
            }
        }

        // See issue #2901
        if (webkit) {
            var last = builder.content.lastChild;
            if (/\bcm-tab\b/.test(last.className) || (last.querySelector && last.querySelector(".cm-tab"))) {
                builder.content.className = "cm-tab-wrap-hack";
            }
        }

        signal(cm, "renderLine", cm, lineView.line, builder.pre);
        if (builder.pre.className) {
            builder.textClass = joinClasses(builder.pre.className, builder.textClass || "");
        }

        return builder
    }

    function defaultSpecialCharPlaceholder(ch) {
        var token = elt("span", "\u2022", "cm-invalidchar");
        token.title = "\\u"+ch.charCodeAt(0).toString(16);
        token.setAttribute("aria-label", token.title);
        return token
    }

    // Build up the DOM representation for a single token, and add it to
    // the line map. Takes care to render special characters separately.
    function buildToken(builder, text, style, startStyle, endStyle, css, attributes) {
        if (!text) {
            return
        }
        var displayText = builder.splitSpaces ? splitSpaces(text, builder.trailingSpace) : text;
        var special = builder.cm.state.specialChars, mustWrap = false;
        var content;
        if (!special.test(text)) {
            builder.col += text.length;
            content = document.createTextNode(displayText);
            builder.map.push(builder.pos, builder.pos+text.length, content);
            if (ie && ie_version < 9) {
                mustWrap = true;
            }
            builder.pos += text.length;
        } else {
            content = document.createDocumentFragment();
            var pos = 0;
            while (true) {
                special.lastIndex = pos;
                var m = special.exec(text);
                var skipped = m ? m.index-pos : text.length-pos;
                if (skipped) {
                    var txt = document.createTextNode(displayText.slice(pos, pos+skipped));
                    if (ie && ie_version < 9) {
                        content.appendChild(elt("span", [txt]));
                    } else {
                        content.appendChild(txt);
                    }
                    builder.map.push(builder.pos, builder.pos+skipped, txt);
                    builder.col += skipped;
                    builder.pos += skipped;
                }
                if (!m) {
                    break
                }
                pos += skipped+1;
                var txt$1 = (void 0);
                if (m[0] == "\t") {
                    var tabSize = builder.cm.options.tabSize, tabWidth = tabSize-builder.col % tabSize;
                    txt$1 = content.appendChild(elt("span", spaceStr(tabWidth), "cm-tab"));
                    txt$1.setAttribute("role", "presentation");
                    txt$1.setAttribute("cm-text", "\t");
                    builder.col += tabWidth;
                } else if (m[0] == "\r" || m[0] == "\n") {
                    txt$1 = content.appendChild(elt("span", m[0] == "\r" ? "\u240d" : "\u2424", "cm-invalidchar"));
                    txt$1.setAttribute("cm-text", m[0]);
                    builder.col += 1;
                } else {
                    txt$1 = builder.cm.options.specialCharPlaceholder(m[0]);
                    txt$1.setAttribute("cm-text", m[0]);
                    if (ie && ie_version < 9) {
                        content.appendChild(elt("span", [txt$1]));
                    } else {
                        content.appendChild(txt$1);
                    }
                    builder.col += 1;
                }
                builder.map.push(builder.pos, builder.pos+1, txt$1);
                builder.pos++;
            }
        }
        builder.trailingSpace = displayText.charCodeAt(text.length-1) == 32;
        if (style || startStyle || endStyle || mustWrap || css || attributes) {
            var fullStyle = style || "";
            if (startStyle) {
                fullStyle += startStyle;
            }
            if (endStyle) {
                fullStyle += endStyle;
            }
            var token = elt("span", [content], fullStyle, css);
            if (attributes) {
                for (var attr in attributes) {
                    if (attributes.hasOwnProperty(attr) && attr != "style" && attr != "class") {
                        token.setAttribute(attr, attributes[attr]);
                    }
                }
            }
            return builder.content.appendChild(token)
        }
        builder.content.appendChild(content);
    }

    // Change some spaces to NBSP to prevent the browser from collapsing
    // trailing spaces at the end of a line when rendering text (issue #1362).
    function splitSpaces(text, trailingBefore) {
        if (text.length > 1 && !/  /.test(text)) {
            return text
        }
        var spaceBefore = trailingBefore, result = "";
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            if (ch == " " && spaceBefore && (i == text.length-1 || text.charCodeAt(i+1) == 32)) {
                ch = "\u00a0";
            }
            result += ch;
            spaceBefore = ch == " ";
        }
        return result
    }

    // Work around nonsense dimensions being reported for stretches of
    // right-to-left text.
    function buildTokenBadBidi(inner, order) {
        return function (builder, text, style, startStyle, endStyle, css, attributes) {
            style = style ? style+" cm-force-border" : "cm-force-border";
            var start = builder.pos, end = start+text.length;
            for (; ;) {
                // Find the part that overlaps with the start of this text
                var part = (void 0);
                for (var i = 0; i < order.length; i++) {
                    part = order[i];
                    if (part.to > start && part.from <= start) {
                        break
                    }
                }
                if (part.to >= end) {
                    return inner(builder, text, style, startStyle, endStyle, css, attributes)
                }
                inner(builder, text.slice(0, part.to-start), style, startStyle, null, css, attributes);
                startStyle = null;
                text = text.slice(part.to-start);
                start = part.to;
            }
        }
    }

    function buildCollapsedSpan(builder, size, marker, ignoreWidget) {
        var widget = !ignoreWidget && marker.widgetNode;
        if (widget) {
            builder.map.push(builder.pos, builder.pos+size, widget);
        }
        if (!ignoreWidget && builder.cm.display.input.needsContentAttribute) {
            if (!widget) {
                widget = builder.content.appendChild(document.createElement("span"));
            }
            widget.setAttribute("cm-marker", marker.id);
        }
        if (widget) {
            builder.cm.display.input.setUneditable(widget);
            builder.content.appendChild(widget);
        }
        builder.pos += size;
        builder.trailingSpace = false;
    }

    // Outputs a number of spans to make up a line, taking highlighting
    // and marked text into account.
    function insertLineContent(line, builder, styles) {
        var spans = line.markedSpans, allText = line.text, at = 0;
        if (!spans) {
            for (var i$1 = 1; i$1 < styles.length; i$1 += 2) {
                builder.addToken(builder, allText.slice(at, at = styles[i$1]), interpretTokenStyle(styles[i$1+1], builder.cm.options));
            }
            return
        }

        var len = allText.length, pos = 0, i = 1, text = "", style, css;
        var nextChange = 0, spanStyle, spanEndStyle, spanStartStyle, collapsed, attributes;
        for (; ;) {
            if (nextChange == pos) { // Update current marker set
                spanStyle = spanEndStyle = spanStartStyle = css = "";
                attributes = null;
                collapsed = null;
                nextChange = Infinity;
                var foundBookmarks = [], endStyles = (void 0);
                for (var j = 0; j < spans.length; ++j) {
                    var sp = spans[j], m = sp.marker;
                    if (m.type == "bookmark" && sp.from == pos && m.widgetNode) {
                        foundBookmarks.push(m);
                    } else if (sp.from <= pos && (sp.to == null || sp.to > pos || m.collapsed && sp.to == pos && sp.from == pos)) {
                        if (sp.to != null && sp.to != pos && nextChange > sp.to) {
                            nextChange = sp.to;
                            spanEndStyle = "";
                        }
                        if (m.className) {
                            spanStyle += " "+m.className;
                        }
                        if (m.css) {
                            css = (css ? css+";" : "")+m.css;
                        }
                        if (m.startStyle && sp.from == pos) {
                            spanStartStyle += " "+m.startStyle;
                        }
                        if (m.endStyle && sp.to == nextChange) {
                            (endStyles || (endStyles = [])).push(m.endStyle, sp.to);
                        }
                        // support for the old title property
                        // https://github.com/codemirror/CodeMirror/pull/5673
                        if (m.title) {
                            (attributes || (attributes = {})).title = m.title;
                        }
                        if (m.attributes) {
                            for (var attr in m.attributes) {
                                (attributes || (attributes = {}))[attr] = m.attributes[attr];
                            }
                        }
                        if (m.collapsed && (!collapsed || compareCollapsedMarkers(collapsed.marker, m) < 0)) {
                            collapsed = sp;
                        }
                    } else if (sp.from > pos && nextChange > sp.from) {
                        nextChange = sp.from;
                    }
                }
                if (endStyles) {
                    for (var j$1 = 0; j$1 < endStyles.length; j$1 += 2) {
                        if (endStyles[j$1+1] == nextChange) {
                            spanEndStyle += " "+endStyles[j$1];
                        }
                    }
                }

                if (!collapsed || collapsed.from == pos) {
                    for (var j$2 = 0; j$2 < foundBookmarks.length; ++j$2) {
                        buildCollapsedSpan(builder, 0, foundBookmarks[j$2]);
                    }
                }
                if (collapsed && (collapsed.from || 0) == pos) {
                    buildCollapsedSpan(builder, (collapsed.to == null ? len+1 : collapsed.to)-pos,
                        collapsed.marker, collapsed.from == null);
                    if (collapsed.to == null) {
                        return
                    }
                    if (collapsed.to == pos) {
                        collapsed = false;
                    }
                }
            }
            if (pos >= len) {
                break
            }

            var upto = Math.min(len, nextChange);
            while (true) {
                if (text) {
                    var end = pos+text.length;
                    if (!collapsed) {
                        var tokenText = end > upto ? text.slice(0, upto-pos) : text;
                        builder.addToken(builder, tokenText, style ? style+spanStyle : spanStyle,
                            spanStartStyle, pos+tokenText.length == nextChange ? spanEndStyle : "", css, attributes);
                    }
                    if (end >= upto) {
                        text = text.slice(upto-pos);
                        pos = upto;
                        break
                    }
                    pos = end;
                    spanStartStyle = "";
                }
                text = allText.slice(at, at = styles[i++]);
                style = interpretTokenStyle(styles[i++], builder.cm.options);
            }
        }
    }


    // These objects are used to represent the visible (currently drawn)
    // part of the document. A LineView may correspond to multiple
    // logical lines, if those are connected by collapsed ranges.
    function LineView(doc, line, lineN) {
        // The starting line
        this.line = line;
        // Continuing lines, if any
        this.rest = visualLineContinued(line);
        // Number of logical lines in this visual line
        this.size = this.rest ? lineNo(lst(this.rest))-lineN+1 : 1;
        this.node = this.text = null;
        this.hidden = lineIsHidden(doc, line);
    }

    // Create a range of LineView objects for the given lines.
    function buildViewArray(cm, from, to) {
        var array = [], nextPos;
        for (var pos = from; pos < to; pos = nextPos) {
            var view = new LineView(cm.doc, getLine(cm.doc, pos), pos);
            nextPos = pos+view.size;
            array.push(view);
        }
        return array
    }

    var operationGroup = null;

    function pushOperation(op) {
        if (operationGroup) {
            operationGroup.ops.push(op);
        } else {
            op.ownsGroup = operationGroup = {
                ops: [op],
                delayedCallbacks: []
            };
        }
    }

    function fireCallbacksForOps(group) {
        // Calls delayed callbacks and cursorActivity handlers until no
        // new ones appear
        var callbacks = group.delayedCallbacks, i = 0;
        do {
            for (; i < callbacks.length; i++) {
                callbacks[i].call(null);
            }
            for (var j = 0; j < group.ops.length; j++) {
                var op = group.ops[j];
                if (op.cursorActivityHandlers) {
                    while (op.cursorActivityCalled < op.cursorActivityHandlers.length) {
                        op.cursorActivityHandlers[op.cursorActivityCalled++].call(null, op.cm);
                    }
                }
            }
        } while (i < callbacks.length)
    }

    function finishOperation(op, endCb) {
        var group = op.ownsGroup;
        if (!group) {
            return
        }

        try {
            fireCallbacksForOps(group);
        } finally {
            operationGroup = null;
            endCb(group);
        }
    }

    var orphanDelayedCallbacks = null;

    // Often, we want to signal events at a point where we are in the
    // middle of some work, but don't want the handler to start calling
    // other methods on the editor, which might be in an inconsistent
    // state or simply not expect any other events to happen.
    // signalLater looks whether there are any handlers, and schedules
    // them to be executed when the last operation ends, or, if no
    // operation is active, when a timeout fires.
    function signalLater(emitter, type /*, values...*/) {
        var arr = getHandlers(emitter, type);
        if (!arr.length) {
            return
        }
        var args = Array.prototype.slice.call(arguments, 2), list;
        if (operationGroup) {
            list = operationGroup.delayedCallbacks;
        } else if (orphanDelayedCallbacks) {
            list = orphanDelayedCallbacks;
        } else {
            list = orphanDelayedCallbacks = [];
            setTimeout(fireOrphanDelayed, 0);
        }
        var loop = function (i) {
            list.push(function () {
                return arr[i].apply(null, args);
            });
        };

        for (var i = 0; i < arr.length; ++i)
            loop(i);
    }

    function fireOrphanDelayed() {
        var delayed = orphanDelayedCallbacks;
        orphanDelayedCallbacks = null;
        for (var i = 0; i < delayed.length; ++i) {
            delayed[i]();
        }
    }

    // When an aspect of a line changes, a string is added to
    // lineView.changes. This updates the relevant part of the line's
    // DOM structure.
    function updateLineForChanges(cm, lineView, lineN, dims) {
        for (var j = 0; j < lineView.changes.length; j++) {
            var type = lineView.changes[j];
            if (type == "text") {
                updateLineText(cm, lineView);
            } else if (type == "gutter") {
                updateLineGutter(cm, lineView, lineN, dims);
            } else if (type == "class") {
                updateLineClasses(cm, lineView);
            } else if (type == "widget") {
                updateLineWidgets(cm, lineView, dims);
            }
        }
        lineView.changes = null;
    }

    // Lines with gutter elements, widgets or a background class need to
    // be wrapped, and have the extra elements added to the wrapper div
    function ensureLineWrapped(lineView) {
        if (lineView.node == lineView.text) {
            lineView.node = elt("div", null, null, "position: relative");
            if (lineView.text.parentNode) {
                lineView.text.parentNode.replaceChild(lineView.node, lineView.text);
            }
            lineView.node.appendChild(lineView.text);
            if (ie && ie_version < 8) {
                lineView.node.style.zIndex = 2;
            }
        }
        return lineView.node
    }

    function updateLineBackground(cm, lineView) {
        var cls = lineView.bgClass ? lineView.bgClass+" "+(lineView.line.bgClass || "") : lineView.line.bgClass;
        if (cls) {
            cls += " CodeMirror-linebackground";
        }
        if (lineView.background) {
            if (cls) {
                lineView.background.className = cls;
            } else {
                lineView.background.parentNode.removeChild(lineView.background);
                lineView.background = null;
            }
        } else if (cls) {
            var wrap = ensureLineWrapped(lineView);
            lineView.background = wrap.insertBefore(elt("div", null, cls), wrap.firstChild);
            cm.display.input.setUneditable(lineView.background);
        }
    }

    // Wrapper around buildLineContent which will reuse the structure
    // in display.externalMeasured when possible.
    function getLineContent(cm, lineView) {
        var ext = cm.display.externalMeasured;
        if (ext && ext.line == lineView.line) {
            cm.display.externalMeasured = null;
            lineView.measure = ext.measure;
            return ext.built
        }
        return buildLineContent(cm, lineView)
    }

    // Redraw the line's text. Interacts with the background and text
    // classes because the mode may output tokens that influence these
    // classes.
    function updateLineText(cm, lineView) {
        var cls = lineView.text.className;
        var built = getLineContent(cm, lineView);
        if (lineView.text == lineView.node) {
            lineView.node = built.pre;
        }
        lineView.text.parentNode.replaceChild(built.pre, lineView.text);
        lineView.text = built.pre;
        if (built.bgClass != lineView.bgClass || built.textClass != lineView.textClass) {
            lineView.bgClass = built.bgClass;
            lineView.textClass = built.textClass;
            updateLineClasses(cm, lineView);
        } else if (cls) {
            lineView.text.className = cls;
        }
    }

    function updateLineClasses(cm, lineView) {
        updateLineBackground(cm, lineView);
        if (lineView.line.wrapClass) {
            ensureLineWrapped(lineView).className = lineView.line.wrapClass;
        } else if (lineView.node != lineView.text) {
            lineView.node.className = "";
        }
        var textClass = lineView.textClass ? lineView.textClass+" "+(lineView.line.textClass || "") : lineView.line.textClass;
        lineView.text.className = textClass || "";
    }

    function updateLineGutter(cm, lineView, lineN, dims) {
        if (lineView.gutter) {
            lineView.node.removeChild(lineView.gutter);
            lineView.gutter = null;
        }
        if (lineView.gutterBackground) {
            lineView.node.removeChild(lineView.gutterBackground);
            lineView.gutterBackground = null;
        }
        if (lineView.line.gutterClass) {
            var wrap = ensureLineWrapped(lineView);
            lineView.gutterBackground = elt("div", null, "CodeMirror-gutter-background "+lineView.line.gutterClass,
                ("left: "+(cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth)+"px; width: "+(dims.gutterTotalWidth)+"px"));
            cm.display.input.setUneditable(lineView.gutterBackground);
            wrap.insertBefore(lineView.gutterBackground, lineView.text);
        }
        var markers = lineView.line.gutterMarkers;
        if (cm.options.lineNumbers || markers) {
            var wrap$1 = ensureLineWrapped(lineView);
            var gutterWrap = lineView.gutter = elt("div", null, "CodeMirror-gutter-wrapper", ("left: "+(cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth)+"px"));
            gutterWrap.setAttribute("aria-hidden", "true");
            cm.display.input.setUneditable(gutterWrap);
            wrap$1.insertBefore(gutterWrap, lineView.text);
            if (lineView.line.gutterClass) {
                gutterWrap.className += " "+lineView.line.gutterClass;
            }
            if (cm.options.lineNumbers && (!markers || !markers["CodeMirror-linenumbers"])) {
                lineView.lineNumber = gutterWrap.appendChild(
                    elt("div", lineNumberFor(cm.options, lineN),
                        "CodeMirror-linenumber CodeMirror-gutter-elt",
                        ("left: "+(dims.gutterLeft["CodeMirror-linenumbers"])+"px; width: "+(cm.display.lineNumInnerWidth)+"px")));
            }
            if (markers) {
                for (var k = 0; k < cm.display.gutterSpecs.length; ++k) {
                    var id = cm.display.gutterSpecs[k].className, found = markers.hasOwnProperty(id) && markers[id];
                    if (found) {
                        gutterWrap.appendChild(elt("div", [found], "CodeMirror-gutter-elt",
                            ("left: "+(dims.gutterLeft[id])+"px; width: "+(dims.gutterWidth[id])+"px")));
                    }
                }
            }
        }
    }

    function updateLineWidgets(cm, lineView, dims) {
        if (lineView.alignable) {
            lineView.alignable = null;
        }
        var isWidget = classTest("CodeMirror-linewidget");
        for (var node = lineView.node.firstChild, next = (void 0); node; node = next) {
            next = node.nextSibling;
            if (isWidget.test(node.className)) {
                lineView.node.removeChild(node);
            }
        }
        insertLineWidgets(cm, lineView, dims);
    }

    // Build a line's DOM representation from scratch
    function buildLineElement(cm, lineView, lineN, dims) {
        var built = getLineContent(cm, lineView);
        lineView.text = lineView.node = built.pre;
        if (built.bgClass) {
            lineView.bgClass = built.bgClass;
        }
        if (built.textClass) {
            lineView.textClass = built.textClass;
        }

        updateLineClasses(cm, lineView);
        updateLineGutter(cm, lineView, lineN, dims);
        insertLineWidgets(cm, lineView, dims);
        return lineView.node
    }

    // A lineView may contain multiple logical lines (when merged by
    // collapsed spans). The widgets for all of them need to be drawn.
    function insertLineWidgets(cm, lineView, dims) {
        insertLineWidgetsFor(cm, lineView.line, lineView, dims, true);
        if (lineView.rest) {
            for (var i = 0; i < lineView.rest.length; i++) {
                insertLineWidgetsFor(cm, lineView.rest[i], lineView, dims, false);
            }
        }
    }

    function insertLineWidgetsFor(cm, line, lineView, dims, allowAbove) {
        if (!line.widgets) {
            return
        }
        var wrap = ensureLineWrapped(lineView);
        for (var i = 0, ws = line.widgets; i < ws.length; ++i) {
            var widget = ws[i],
                node = elt("div", [widget.node], "CodeMirror-linewidget"+(widget.className ? " "+widget.className : ""));
            if (!widget.handleMouseEvents) {
                node.setAttribute("cm-ignore-events", "true");
            }
            positionLineWidget(widget, node, lineView, dims);
            cm.display.input.setUneditable(node);
            if (allowAbove && widget.above) {
                wrap.insertBefore(node, lineView.gutter || lineView.text);
            } else {
                wrap.appendChild(node);
            }
            signalLater(widget, "redraw");
        }
    }

    function positionLineWidget(widget, node, lineView, dims) {
        if (widget.noHScroll) {
            (lineView.alignable || (lineView.alignable = [])).push(node);
            var width = dims.wrapperWidth;
            node.style.left = dims.fixedPos+"px";
            if (!widget.coverGutter) {
                width -= dims.gutterTotalWidth;
                node.style.paddingLeft = dims.gutterTotalWidth+"px";
            }
            node.style.width = width+"px";
        }
        if (widget.coverGutter) {
            node.style.zIndex = 5;
            node.style.position = "relative";
            if (!widget.noHScroll) {
                node.style.marginLeft = -dims.gutterTotalWidth+"px";
            }
        }
    }

    function widgetHeight(widget) {
        if (widget.height != null) {
            return widget.height
        }
        var cm = widget.doc.cm;
        if (!cm) {
            return 0
        }
        if (!contains(document.body, widget.node)) {
            var parentStyle = "position: relative;";
            if (widget.coverGutter) {
                parentStyle += "margin-left: -"+cm.display.gutters.offsetWidth+"px;";
            }
            if (widget.noHScroll) {
                parentStyle += "width: "+cm.display.wrapper.clientWidth+"px;";
            }
            removeChildrenAndAdd(cm.display.measure, elt("div", [widget.node], null, parentStyle));
        }
        return widget.height = widget.node.parentNode.offsetHeight
    }

    // Return true when the given mouse event happened in a widget
    function eventInWidget(display, e) {
        for (var n = e_target(e); n != display.wrapper; n = n.parentNode) {
            if (!n || (n.nodeType == 1 && n.getAttribute("cm-ignore-events") == "true") ||
                (n.parentNode == display.sizer && n != display.mover)) {
                return true
            }
        }
    }

    // POSITION MEASUREMENT

    function paddingTop(display) {
        return display.lineSpace.offsetTop
    }

    function paddingVert(display) {
        return display.mover.offsetHeight-display.lineSpace.offsetHeight
    }

    function paddingH(display) {
        if (display.cachedPaddingH) {
            return display.cachedPaddingH
        }
        var e = removeChildrenAndAdd(display.measure, elt("pre", "x", "CodeMirror-line-like"));
        var style = window.getComputedStyle ? window.getComputedStyle(e) : e.currentStyle;
        var data = {left: parseInt(style.paddingLeft), right: parseInt(style.paddingRight)};
        if (!isNaN(data.left) && !isNaN(data.right)) {
            display.cachedPaddingH = data;
        }
        return data
    }

    function scrollGap(cm) {
        return scrollerGap-cm.display.nativeBarWidth
    }

    function displayWidth(cm) {
        return cm.display.scroller.clientWidth-scrollGap(cm)-cm.display.barWidth
    }

    function displayHeight(cm) {
        return cm.display.scroller.clientHeight-scrollGap(cm)-cm.display.barHeight
    }

    // Ensure the lineView.wrapping.heights array is populated. This is
    // an array of bottom offsets for the lines that make up a drawn
    // line. When lineWrapping is on, there might be more than one
    // height.
    function ensureLineHeights(cm, lineView, rect) {
        var wrapping = cm.options.lineWrapping;
        var curWidth = wrapping && displayWidth(cm);
        if (!lineView.measure.heights || wrapping && lineView.measure.width != curWidth) {
            var heights = lineView.measure.heights = [];
            if (wrapping) {
                lineView.measure.width = curWidth;
                var rects = lineView.text.firstChild.getClientRects();
                for (var i = 0; i < rects.length-1; i++) {
                    var cur = rects[i], next = rects[i+1];
                    if (Math.abs(cur.bottom-next.bottom) > 2) {
                        heights.push((cur.bottom+next.top) / 2-rect.top);
                    }
                }
            }
            heights.push(rect.bottom-rect.top);
        }
    }

    // Find a line map (mapping character offsets to text nodes) and a
    // measurement cache for the given line number. (A line view might
    // contain multiple lines when collapsed ranges are present.)
    function mapFromLineView(lineView, line, lineN) {
        if (lineView.line == line) {
            return {map: lineView.measure.map, cache: lineView.measure.cache}
        }
        if (lineView.rest) {
            for (var i = 0; i < lineView.rest.length; i++) {
                if (lineView.rest[i] == line) {
                    return {map: lineView.measure.maps[i], cache: lineView.measure.caches[i]}
                }
            }
            for (var i$1 = 0; i$1 < lineView.rest.length; i$1++) {
                if (lineNo(lineView.rest[i$1]) > lineN) {
                    return {map: lineView.measure.maps[i$1], cache: lineView.measure.caches[i$1], before: true}
                }
            }
        }
    }

    // Render a line into the hidden node display.externalMeasured. Used
    // when measurement is needed for a line that's not in the viewport.
    function updateExternalMeasurement(cm, line) {
        line = visualLine(line);
        var lineN = lineNo(line);
        var view = cm.display.externalMeasured = new LineView(cm.doc, line, lineN);
        view.lineN = lineN;
        var built = view.built = buildLineContent(cm, view);
        view.text = built.pre;
        removeChildrenAndAdd(cm.display.lineMeasure, built.pre);
        return view
    }

    // Get a {top, bottom, left, right} box (in line-local coordinates)
    // for a given character.
    function measureChar(cm, line, ch, bias) {
        return measureCharPrepared(cm, prepareMeasureForLine(cm, line), ch, bias)
    }

    // Find a line view that corresponds to the given line number.
    function findViewForLine(cm, lineN) {
        if (lineN >= cm.display.viewFrom && lineN < cm.display.viewTo) {
            return cm.display.view[findViewIndex(cm, lineN)]
        }
        var ext = cm.display.externalMeasured;
        if (ext && lineN >= ext.lineN && lineN < ext.lineN+ext.size) {
            return ext
        }
    }

    // Measurement can be split in two steps, the set-up work that
    // applies to the whole line, and the measurement of the actual
    // character. Functions like coordsChar, that need to do a lot of
    // measurements in a row, can thus ensure that the set-up work is
    // only done once.
    function prepareMeasureForLine(cm, line) {
        var lineN = lineNo(line);
        var view = findViewForLine(cm, lineN);
        if (view && !view.text) {
            view = null;
        } else if (view && view.changes) {
            updateLineForChanges(cm, view, lineN, getDimensions(cm));
            cm.curOp.forceUpdate = true;
        }
        if (!view) {
            view = updateExternalMeasurement(cm, line);
        }

        var info = mapFromLineView(view, line, lineN);
        return {
            line: line, view: view, rect: null,
            map: info.map, cache: info.cache, before: info.before,
            hasHeights: false
        }
    }

    // Given a prepared measurement object, measures the position of an
    // actual character (or fetches it from the cache).
    function measureCharPrepared(cm, prepared, ch, bias, varHeight) {
        if (prepared.before) {
            ch = -1;
        }
        var key = ch+(bias || ""), found;
        if (prepared.cache.hasOwnProperty(key)) {
            found = prepared.cache[key];
        } else {
            if (!prepared.rect) {
                prepared.rect = prepared.view.text.getBoundingClientRect();
            }
            if (!prepared.hasHeights) {
                ensureLineHeights(cm, prepared.view, prepared.rect);
                prepared.hasHeights = true;
            }
            found = measureCharInner(cm, prepared, ch, bias);
            if (!found.bogus) {
                prepared.cache[key] = found;
            }
        }
        return {
            left: found.left, right: found.right,
            top: varHeight ? found.rtop : found.top,
            bottom: varHeight ? found.rbottom : found.bottom
        }
    }

    var nullRect = {left: 0, right: 0, top: 0, bottom: 0};

    function nodeAndOffsetInLineMap(map, ch, bias) {
        var node, start, end, collapse, mStart, mEnd;
        // First, search the line map for the text node corresponding to,
        // or closest to, the target character.
        for (var i = 0; i < map.length; i += 3) {
            mStart = map[i];
            mEnd = map[i+1];
            if (ch < mStart) {
                start = 0;
                end = 1;
                collapse = "left";
            } else if (ch < mEnd) {
                start = ch-mStart;
                end = start+1;
            } else if (i == map.length-3 || ch == mEnd && map[i+3] > ch) {
                end = mEnd-mStart;
                start = end-1;
                if (ch >= mEnd) {
                    collapse = "right";
                }
            }
            if (start != null) {
                node = map[i+2];
                if (mStart == mEnd && bias == (node.insertLeft ? "left" : "right")) {
                    collapse = bias;
                }
                if (bias == "left" && start == 0) {
                    while (i && map[i-2] == map[i-3] && map[i-1].insertLeft) {
                        node = map[(i -= 3)+2];
                        collapse = "left";
                    }
                }
                if (bias == "right" && start == mEnd-mStart) {
                    while (i < map.length-3 && map[i+3] == map[i+4] && !map[i+5].insertLeft) {
                        node = map[(i += 3)+2];
                        collapse = "right";
                    }
                }
                break
            }
        }
        return {node: node, start: start, end: end, collapse: collapse, coverStart: mStart, coverEnd: mEnd}
    }

    function getUsefulRect(rects, bias) {
        var rect = nullRect;
        if (bias == "left") {
            for (var i = 0; i < rects.length; i++) {
                if ((rect = rects[i]).left != rect.right) {
                    break
                }
            }
        } else {
            for (var i$1 = rects.length-1; i$1 >= 0; i$1--) {
                if ((rect = rects[i$1]).left != rect.right) {
                    break
                }
            }
        }
        return rect
    }

    function measureCharInner(cm, prepared, ch, bias) {
        var place = nodeAndOffsetInLineMap(prepared.map, ch, bias);
        var node = place.node, start = place.start, end = place.end, collapse = place.collapse;

        var rect;
        if (node.nodeType == 3) { // If it is a text node, use a range to retrieve the coordinates.
            for (var i$1 = 0; i$1 < 4; i$1++) { // Retry a maximum of 4 times when nonsense rectangles are returned
                while (start && isExtendingChar(prepared.line.text.charAt(place.coverStart+start))) {
                    --start;
                }
                while (place.coverStart+end < place.coverEnd && isExtendingChar(prepared.line.text.charAt(place.coverStart+end))) {
                    ++end;
                }
                if (ie && ie_version < 9 && start == 0 && end == place.coverEnd-place.coverStart) {
                    rect = node.parentNode.getBoundingClientRect();
                } else {
                    rect = getUsefulRect(range(node, start, end).getClientRects(), bias);
                }
                if (rect.left || rect.right || start == 0) {
                    break
                }
                end = start;
                start = start-1;
                collapse = "right";
            }
            if (ie && ie_version < 11) {
                rect = maybeUpdateRectForZooming(cm.display.measure, rect);
            }
        } else { // If it is a widget, simply get the box for the whole widget.
            if (start > 0) {
                collapse = bias = "right";
            }
            var rects;
            if (cm.options.lineWrapping && (rects = node.getClientRects()).length > 1) {
                rect = rects[bias == "right" ? rects.length-1 : 0];
            } else {
                rect = node.getBoundingClientRect();
            }
        }
        if (ie && ie_version < 9 && !start && (!rect || !rect.left && !rect.right)) {
            var rSpan = node.parentNode.getClientRects()[0];
            if (rSpan) {
                rect = {
                    left: rSpan.left,
                    right: rSpan.left+charWidth(cm.display),
                    top: rSpan.top,
                    bottom: rSpan.bottom
                };
            } else {
                rect = nullRect;
            }
        }

        var rtop = rect.top-prepared.rect.top, rbot = rect.bottom-prepared.rect.top;
        var mid = (rtop+rbot) / 2;
        var heights = prepared.view.measure.heights;
        var i = 0;
        for (; i < heights.length-1; i++) {
            if (mid < heights[i]) {
                break
            }
        }
        var top = i ? heights[i-1] : 0, bot = heights[i];
        var result = {
            left: (collapse == "right" ? rect.right : rect.left)-prepared.rect.left,
            right: (collapse == "left" ? rect.left : rect.right)-prepared.rect.left,
            top: top, bottom: bot
        };
        if (!rect.left && !rect.right) {
            result.bogus = true;
        }
        if (!cm.options.singleCursorHeightPerLine) {
            result.rtop = rtop;
            result.rbottom = rbot;
        }

        return result
    }

    // Work around problem with bounding client rects on ranges being
    // returned incorrectly when zoomed on IE10 and below.
    function maybeUpdateRectForZooming(measure, rect) {
        if (!window.screen || screen.logicalXDPI == null ||
            screen.logicalXDPI == screen.deviceXDPI || !hasBadZoomedRects(measure)) {
            return rect
        }
        var scaleX = screen.logicalXDPI / screen.deviceXDPI;
        var scaleY = screen.logicalYDPI / screen.deviceYDPI;
        return {
            left: rect.left * scaleX, right: rect.right * scaleX,
            top: rect.top * scaleY, bottom: rect.bottom * scaleY
        }
    }

    function clearLineMeasurementCacheFor(lineView) {
        if (lineView.measure) {
            lineView.measure.cache = {};
            lineView.measure.heights = null;
            if (lineView.rest) {
                for (var i = 0; i < lineView.rest.length; i++) {
                    lineView.measure.caches[i] = {};
                }
            }
        }
    }

    function clearLineMeasurementCache(cm) {
        cm.display.externalMeasure = null;
        removeChildren(cm.display.lineMeasure);
        for (var i = 0; i < cm.display.view.length; i++) {
            clearLineMeasurementCacheFor(cm.display.view[i]);
        }
    }

    function clearCaches(cm) {
        clearLineMeasurementCache(cm);
        cm.display.cachedCharWidth = cm.display.cachedTextHeight = cm.display.cachedPaddingH = null;
        if (!cm.options.lineWrapping) {
            cm.display.maxLineChanged = true;
        }
        cm.display.lineNumChars = null;
    }

    function pageScrollX() {
        // Work around https://bugs.chromium.org/p/chromium/issues/detail?id=489206
        // which causes page_Offset and bounding client rects to use
        // different reference viewports and invalidate our calculations.
        if (chrome && android) {
            return -(document.body.getBoundingClientRect().left-parseInt(getComputedStyle(document.body).marginLeft))
        }
        return window.pageXOffset || (document.documentElement || document.body).scrollLeft
    }

    function pageScrollY() {
        if (chrome && android) {
            return -(document.body.getBoundingClientRect().top-parseInt(getComputedStyle(document.body).marginTop))
        }
        return window.pageYOffset || (document.documentElement || document.body).scrollTop
    }

    function widgetTopHeight(lineObj) {
        var ref = visualLine(lineObj);
        var widgets = ref.widgets;
        var height = 0;
        if (widgets) {
            for (var i = 0; i < widgets.length; ++i) {
                if (widgets[i].above) {
                    height += widgetHeight(widgets[i]);
                }
            }
        }
        return height
    }

    // Converts a {top, bottom, left, right} box from line-local
    // coordinates into another coordinate system. Context may be one of
    // "line", "div" (display.lineDiv), "local"./null (editor), "window",
    // or "page".
    function intoCoordSystem(cm, lineObj, rect, context, includeWidgets) {
        if (!includeWidgets) {
            var height = widgetTopHeight(lineObj);
            rect.top += height;
            rect.bottom += height;
        }
        if (context == "line") {
            return rect
        }
        if (!context) {
            context = "local";
        }
        var yOff = heightAtLine(lineObj);
        if (context == "local") {
            yOff += paddingTop(cm.display);
        } else {
            yOff -= cm.display.viewOffset;
        }
        if (context == "page" || context == "window") {
            var lOff = cm.display.lineSpace.getBoundingClientRect();
            yOff += lOff.top+(context == "window" ? 0 : pageScrollY());
            var xOff = lOff.left+(context == "window" ? 0 : pageScrollX());
            rect.left += xOff;
            rect.right += xOff;
        }
        rect.top += yOff;
        rect.bottom += yOff;
        return rect
    }

    // Coverts a box from "div" coords to another coordinate system.
    // Context may be "window", "page", "div", or "local"./null.
    function fromCoordSystem(cm, coords, context) {
        if (context == "div") {
            return coords
        }
        var left = coords.left, top = coords.top;
        // First move into "page" coordinate system
        if (context == "page") {
            left -= pageScrollX();
            top -= pageScrollY();
        } else if (context == "local" || !context) {
            var localBox = cm.display.sizer.getBoundingClientRect();
            left += localBox.left;
            top += localBox.top;
        }

        var lineSpaceBox = cm.display.lineSpace.getBoundingClientRect();
        return {left: left-lineSpaceBox.left, top: top-lineSpaceBox.top}
    }

    function charCoords(cm, pos, context, lineObj, bias) {
        if (!lineObj) {
            lineObj = getLine(cm.doc, pos.line);
        }
        return intoCoordSystem(cm, lineObj, measureChar(cm, lineObj, pos.ch, bias), context)
    }

    // Returns a box for a given cursor position, which may have an
    // 'other' property containing the position of the secondary cursor
    // on a bidi boundary.
    // A cursor Pos(line, char, "before") is on the same visual line as `char - 1`
    // and after `char - 1` in writing order of `char - 1`
    // A cursor Pos(line, char, "after") is on the same visual line as `char`
    // and before `char` in writing order of `char`
    // Examples (upper-case letters are RTL, lower-case are LTR):
    //     Pos(0, 1, ...)
    //     before   after
    // ab     a|b     a|b
    // aB     a|B     aB|
    // Ab     |Ab     A|b
    // AB     B|A     B|A
    // Every position after the last character on a line is considered to stick
    // to the last character on the line.
    function cursorCoords(cm, pos, context, lineObj, preparedMeasure, varHeight) {
        lineObj = lineObj || getLine(cm.doc, pos.line);
        if (!preparedMeasure) {
            preparedMeasure = prepareMeasureForLine(cm, lineObj);
        }

        function get(ch, right) {
            var m = measureCharPrepared(cm, preparedMeasure, ch, right ? "right" : "left", varHeight);
            if (right) {
                m.left = m.right;
            } else {
                m.right = m.left;
            }
            return intoCoordSystem(cm, lineObj, m, context)
        }

        var order = getOrder(lineObj, cm.doc.direction), ch = pos.ch, sticky = pos.sticky;
        if (ch >= lineObj.text.length) {
            ch = lineObj.text.length;
            sticky = "before";
        } else if (ch <= 0) {
            ch = 0;
            sticky = "after";
        }
        if (!order) {
            return get(sticky == "before" ? ch-1 : ch, sticky == "before")
        }

        function getBidi(ch, partPos, invert) {
            var part = order[partPos], right = part.level == 1;
            return get(invert ? ch-1 : ch, right != invert)
        }

        var partPos = getBidiPartAt(order, ch, sticky);
        var other = bidiOther;
        var val = getBidi(ch, partPos, sticky == "before");
        if (other != null) {
            val.other = getBidi(ch, other, sticky != "before");
        }
        return val
    }

    // Used to cheaply estimate the coordinates for a position. Used for
    // intermediate scroll updates.
    function estimateCoords(cm, pos) {
        var left = 0;
        pos = clipPos(cm.doc, pos);
        if (!cm.options.lineWrapping) {
            left = charWidth(cm.display) * pos.ch;
        }
        var lineObj = getLine(cm.doc, pos.line);
        var top = heightAtLine(lineObj)+paddingTop(cm.display);
        return {left: left, right: left, top: top, bottom: top+lineObj.height}
    }

    // Positions returned by coordsChar contain some extra information.
    // xRel is the relative x position of the input coordinates compared
    // to the found position (so xRel > 0 means the coordinates are to
    // the right of the character position, for example). When outside
    // is true, that means the coordinates lie outside the line's
    // vertical range.
    function PosWithInfo(line, ch, sticky, outside, xRel) {
        var pos = Pos(line, ch, sticky);
        pos.xRel = xRel;
        if (outside) {
            pos.outside = outside;
        }
        return pos
    }

    // Compute the character position closest to the given coordinates.
    // Input must be lineSpace-local ("div" coordinate system).
    function coordsChar(cm, x, y) {
        var doc = cm.doc;
        y += cm.display.viewOffset;
        if (y < 0) {
            return PosWithInfo(doc.first, 0, null, -1, -1)
        }
        var lineN = lineAtHeight(doc, y), last = doc.first+doc.size-1;
        if (lineN > last) {
            return PosWithInfo(doc.first+doc.size-1, getLine(doc, last).text.length, null, 1, 1)
        }
        if (x < 0) {
            x = 0;
        }

        var lineObj = getLine(doc, lineN);
        for (; ;) {
            var found = coordsCharInner(cm, lineObj, lineN, x, y);
            var collapsed = collapsedSpanAround(lineObj, found.ch+(found.xRel > 0 || found.outside > 0 ? 1 : 0));
            if (!collapsed) {
                return found
            }
            var rangeEnd = collapsed.find(1);
            if (rangeEnd.line == lineN) {
                return rangeEnd
            }
            lineObj = getLine(doc, lineN = rangeEnd.line);
        }
    }

    function wrappedLineExtent(cm, lineObj, preparedMeasure, y) {
        y -= widgetTopHeight(lineObj);
        var end = lineObj.text.length;
        var begin = findFirst(function (ch) {
            return measureCharPrepared(cm, preparedMeasure, ch-1).bottom <= y;
        }, end, 0);
        end = findFirst(function (ch) {
            return measureCharPrepared(cm, preparedMeasure, ch).top > y;
        }, begin, end);
        return {begin: begin, end: end}
    }

    function wrappedLineExtentChar(cm, lineObj, preparedMeasure, target) {
        if (!preparedMeasure) {
            preparedMeasure = prepareMeasureForLine(cm, lineObj);
        }
        var targetTop = intoCoordSystem(cm, lineObj, measureCharPrepared(cm, preparedMeasure, target), "line").top;
        return wrappedLineExtent(cm, lineObj, preparedMeasure, targetTop)
    }

    // Returns true if the given side of a box is after the given
    // coordinates, in top-to-bottom, left-to-right order.
    function boxIsAfter(box, x, y, left) {
        return box.bottom <= y ? false : box.top > y ? true : (left ? box.left : box.right) > x
    }

    function coordsCharInner(cm, lineObj, lineNo, x, y) {
        // Move y into line-local coordinate space
        y -= heightAtLine(lineObj);
        var preparedMeasure = prepareMeasureForLine(cm, lineObj);
        // When directly calling `measureCharPrepared`, we have to adjust
        // for the widgets at this line.
        var widgetHeight = widgetTopHeight(lineObj);
        var begin = 0, end = lineObj.text.length, ltr = true;

        var order = getOrder(lineObj, cm.doc.direction);
        // If the line isn't plain left-to-right text, first figure out
        // which bidi section the coordinates fall into.
        if (order) {
            var part = (cm.options.lineWrapping ? coordsBidiPartWrapped : coordsBidiPart)
            (cm, lineObj, lineNo, preparedMeasure, order, x, y);
            ltr = part.level != 1;
            // The awkward -1 offsets are needed because findFirst (called
            // on these below) will treat its first bound as inclusive,
            // second as exclusive, but we want to actually address the
            // characters in the part's range
            begin = ltr ? part.from : part.to-1;
            end = ltr ? part.to : part.from-1;
        }

        // A binary search to find the first character whose bounding box
        // starts after the coordinates. If we run across any whose box wrap
        // the coordinates, store that.
        var chAround = null, boxAround = null;
        var ch = findFirst(function (ch) {
            var box = measureCharPrepared(cm, preparedMeasure, ch);
            box.top += widgetHeight;
            box.bottom += widgetHeight;
            if (!boxIsAfter(box, x, y, false)) {
                return false
            }
            if (box.top <= y && box.left <= x) {
                chAround = ch;
                boxAround = box;
            }
            return true
        }, begin, end);

        var baseX, sticky, outside = false;
        // If a box around the coordinates was found, use that
        if (boxAround) {
            // Distinguish coordinates nearer to the left or right side of the box
            var atLeft = x-boxAround.left < boxAround.right-x, atStart = atLeft == ltr;
            ch = chAround+(atStart ? 0 : 1);
            sticky = atStart ? "after" : "before";
            baseX = atLeft ? boxAround.left : boxAround.right;
        } else {
            // (Adjust for extended bound, if necessary.)
            if (!ltr && (ch == end || ch == begin)) {
                ch++;
            }
            // To determine which side to associate with, get the box to the
            // left of the character and compare it's vertical position to the
            // coordinates
            sticky = ch == 0 ? "after" : ch == lineObj.text.length ? "before" :
                (measureCharPrepared(cm, preparedMeasure, ch-(ltr ? 1 : 0)).bottom+widgetHeight <= y) == ltr ?
                    "after" : "before";
            // Now get accurate coordinates for this place, in order to get a
            // base X position
            var coords = cursorCoords(cm, Pos(lineNo, ch, sticky), "line", lineObj, preparedMeasure);
            baseX = coords.left;
            outside = y < coords.top ? -1 : y >= coords.bottom ? 1 : 0;
        }

        ch = skipExtendingChars(lineObj.text, ch, 1);
        return PosWithInfo(lineNo, ch, sticky, outside, x-baseX)
    }

    function coordsBidiPart(cm, lineObj, lineNo, preparedMeasure, order, x, y) {
        // Bidi parts are sorted left-to-right, and in a non-line-wrapping
        // situation, we can take this ordering to correspond to the visual
        // ordering. This finds the first part whose end is after the given
        // coordinates.
        var index = findFirst(function (i) {
            var part = order[i], ltr = part.level != 1;
            return boxIsAfter(cursorCoords(cm, Pos(lineNo, ltr ? part.to : part.from, ltr ? "before" : "after"),
                "line", lineObj, preparedMeasure), x, y, true)
        }, 0, order.length-1);
        var part = order[index];
        // If this isn't the first part, the part's start is also after
        // the coordinates, and the coordinates aren't on the same line as
        // that start, move one part back.
        if (index > 0) {
            var ltr = part.level != 1;
            var start = cursorCoords(cm, Pos(lineNo, ltr ? part.from : part.to, ltr ? "after" : "before"),
                "line", lineObj, preparedMeasure);
            if (boxIsAfter(start, x, y, true) && start.top > y) {
                part = order[index-1];
            }
        }
        return part
    }

    function coordsBidiPartWrapped(cm, lineObj, _lineNo, preparedMeasure, order, x, y) {
        // In a wrapped line, rtl text on wrapping boundaries can do things
        // that don't correspond to the ordering in our `order` array at
        // all, so a binary search doesn't work, and we want to return a
        // part that only spans one line so that the binary search in
        // coordsCharInner is safe. As such, we first find the extent of the
        // wrapped line, and then do a flat search in which we discard any
        // spans that aren't on the line.
        var ref = wrappedLineExtent(cm, lineObj, preparedMeasure, y);
        var begin = ref.begin;
        var end = ref.end;
        if (/\s/.test(lineObj.text.charAt(end-1))) {
            end--;
        }
        var part = null, closestDist = null;
        for (var i = 0; i < order.length; i++) {
            var p = order[i];
            if (p.from >= end || p.to <= begin) {
                continue
            }
            var ltr = p.level != 1;
            var endX = measureCharPrepared(cm, preparedMeasure, ltr ? Math.min(end, p.to)-1 : Math.max(begin, p.from)).right;
            // Weigh against spans ending before this, so that they are only
            // picked if nothing ends after
            var dist = endX < x ? x-endX+1e9 : endX-x;
            if (!part || closestDist > dist) {
                part = p;
                closestDist = dist;
            }
        }
        if (!part) {
            part = order[order.length-1];
        }
        // Clip the part to the wrapped line.
        if (part.from < begin) {
            part = {from: begin, to: part.to, level: part.level};
        }
        if (part.to > end) {
            part = {from: part.from, to: end, level: part.level};
        }
        return part
    }

    var measureText;

    // Compute the default text height.
    function textHeight(display) {
        if (display.cachedTextHeight != null) {
            return display.cachedTextHeight
        }
        if (measureText == null) {
            measureText = elt("pre", null, "CodeMirror-line-like");
            // Measure a bunch of lines, for browsers that compute
            // fractional heights.
            for (var i = 0; i < 49; ++i) {
                measureText.appendChild(document.createTextNode("x"));
                measureText.appendChild(elt("br"));
            }
            measureText.appendChild(document.createTextNode("x"));
        }
        removeChildrenAndAdd(display.measure, measureText);
        var height = measureText.offsetHeight / 50;
        if (height > 3) {
            display.cachedTextHeight = height;
        }
        removeChildren(display.measure);
        return height || 1
    }

    // Compute the default character width.
    function charWidth(display) {
        if (display.cachedCharWidth != null) {
            return display.cachedCharWidth
        }
        var anchor = elt("span", "xxxxxxxxxx");
        var pre = elt("pre", [anchor], "CodeMirror-line-like");
        removeChildrenAndAdd(display.measure, pre);
        var rect = anchor.getBoundingClientRect(), width = (rect.right-rect.left) / 10;
        if (width > 2) {
            display.cachedCharWidth = width;
        }
        return width || 10
    }

    // Do a bulk-read of the DOM positions and sizes needed to draw the
    // view, so that we don't interleave reading and writing to the DOM.
    function getDimensions(cm) {
        var d = cm.display, left = {}, width = {};
        var gutterLeft = d.gutters.clientLeft;
        for (var n = d.gutters.firstChild, i = 0; n; n = n.nextSibling, ++i) {
            var id = cm.display.gutterSpecs[i].className;
            left[id] = n.offsetLeft+n.clientLeft+gutterLeft;
            width[id] = n.clientWidth;
        }
        return {
            fixedPos: compensateForHScroll(d),
            gutterTotalWidth: d.gutters.offsetWidth,
            gutterLeft: left,
            gutterWidth: width,
            wrapperWidth: d.wrapper.clientWidth
        }
    }

    // Computes display.scroller.scrollLeft + display.gutters.offsetWidth,
    // but using getBoundingClientRect to get a sub-pixel-accurate
    // result.
    function compensateForHScroll(display) {
        return display.scroller.getBoundingClientRect().left-display.sizer.getBoundingClientRect().left
    }

    // Returns a function that estimates the height of a line, to use as
    // first approximation until the line becomes visible (and is thus
    // properly measurable).
    function estimateHeight(cm) {
        var th = textHeight(cm.display), wrapping = cm.options.lineWrapping;
        var perLine = wrapping && Math.max(5, cm.display.scroller.clientWidth / charWidth(cm.display)-3);
        return function (line) {
            if (lineIsHidden(cm.doc, line)) {
                return 0
            }

            var widgetsHeight = 0;
            if (line.widgets) {
                for (var i = 0; i < line.widgets.length; i++) {
                    if (line.widgets[i].height) {
                        widgetsHeight += line.widgets[i].height;
                    }
                }
            }

            if (wrapping) {
                return widgetsHeight+(Math.ceil(line.text.length / perLine) || 1) * th
            } else {
                return widgetsHeight+th
            }
        }
    }

    function estimateLineHeights(cm) {
        var doc = cm.doc, est = estimateHeight(cm);
        doc.iter(function (line) {
            var estHeight = est(line);
            if (estHeight != line.height) {
                updateLineHeight(line, estHeight);
            }
        });
    }

    // Given a mouse event, find the corresponding position. If liberal
    // is false, it checks whether a gutter or scrollbar was clicked,
    // and returns null if it was. forRect is used by rectangular
    // selections, and tries to estimate a character position even for
    // coordinates beyond the right of the text.
    function posFromMouse(cm, e, liberal, forRect) {
        var display = cm.display;
        if (!liberal && e_target(e).getAttribute("cm-not-content") == "true") {
            return null
        }

        var x, y, space = display.lineSpace.getBoundingClientRect();
        // Fails unpredictably on IE[67] when mouse is dragged around quickly.
        try {
            x = e.clientX-space.left;
            y = e.clientY-space.top;
        } catch (e$1) {
            return null
        }
        var coords = coordsChar(cm, x, y), line;
        if (forRect && coords.xRel > 0 && (line = getLine(cm.doc, coords.line).text).length == coords.ch) {
            var colDiff = countColumn(line, line.length, cm.options.tabSize)-line.length;
            coords = Pos(coords.line, Math.max(0, Math.round((x-paddingH(cm.display).left) / charWidth(cm.display))-colDiff));
        }
        return coords
    }

    // Find the view element corresponding to a given line. Return null
    // when the line isn't visible.
    function findViewIndex(cm, n) {
        if (n >= cm.display.viewTo) {
            return null
        }
        n -= cm.display.viewFrom;
        if (n < 0) {
            return null
        }
        var view = cm.display.view;
        for (var i = 0; i < view.length; i++) {
            n -= view[i].size;
            if (n < 0) {
                return i
            }
        }
    }

    // Updates the display.view data structure for a given change to the
    // document. From and to are in pre-change coordinates. Lendiff is
    // the amount of lines added or subtracted by the change. This is
    // used for changes that span multiple lines, or change the way
    // lines are divided into visual lines. regLineChange (below)
    // registers single-line changes.
    function regChange(cm, from, to, lendiff) {
        if (from == null) {
            from = cm.doc.first;
        }
        if (to == null) {
            to = cm.doc.first+cm.doc.size;
        }
        if (!lendiff) {
            lendiff = 0;
        }

        var display = cm.display;
        if (lendiff && to < display.viewTo &&
            (display.updateLineNumbers == null || display.updateLineNumbers > from)) {
            display.updateLineNumbers = from;
        }

        cm.curOp.viewChanged = true;

        if (from >= display.viewTo) { // Change after
            if (sawCollapsedSpans && visualLineNo(cm.doc, from) < display.viewTo) {
                resetView(cm);
            }
        } else if (to <= display.viewFrom) { // Change before
            if (sawCollapsedSpans && visualLineEndNo(cm.doc, to+lendiff) > display.viewFrom) {
                resetView(cm);
            } else {
                display.viewFrom += lendiff;
                display.viewTo += lendiff;
            }
        } else if (from <= display.viewFrom && to >= display.viewTo) { // Full overlap
            resetView(cm);
        } else if (from <= display.viewFrom) { // Top overlap
            var cut = viewCuttingPoint(cm, to, to+lendiff, 1);
            if (cut) {
                display.view = display.view.slice(cut.index);
                display.viewFrom = cut.lineN;
                display.viewTo += lendiff;
            } else {
                resetView(cm);
            }
        } else if (to >= display.viewTo) { // Bottom overlap
            var cut$1 = viewCuttingPoint(cm, from, from, -1);
            if (cut$1) {
                display.view = display.view.slice(0, cut$1.index);
                display.viewTo = cut$1.lineN;
            } else {
                resetView(cm);
            }
        } else { // Gap in the middle
            var cutTop = viewCuttingPoint(cm, from, from, -1);
            var cutBot = viewCuttingPoint(cm, to, to+lendiff, 1);
            if (cutTop && cutBot) {
                display.view = display.view.slice(0, cutTop.index)
                    .concat(buildViewArray(cm, cutTop.lineN, cutBot.lineN))
                    .concat(display.view.slice(cutBot.index));
                display.viewTo += lendiff;
            } else {
                resetView(cm);
            }
        }

        var ext = display.externalMeasured;
        if (ext) {
            if (to < ext.lineN) {
                ext.lineN += lendiff;
            } else if (from < ext.lineN+ext.size) {
                display.externalMeasured = null;
            }
        }
    }

    // Register a change to a single line. Type must be one of "text",
    // "gutter", "class", "widget"
    function regLineChange(cm, line, type) {
        cm.curOp.viewChanged = true;
        var display = cm.display, ext = cm.display.externalMeasured;
        if (ext && line >= ext.lineN && line < ext.lineN+ext.size) {
            display.externalMeasured = null;
        }

        if (line < display.viewFrom || line >= display.viewTo) {
            return
        }
        var lineView = display.view[findViewIndex(cm, line)];
        if (lineView.node == null) {
            return
        }
        var arr = lineView.changes || (lineView.changes = []);
        if (indexOf(arr, type) == -1) {
            arr.push(type);
        }
    }

    // Clear the view.
    function resetView(cm) {
        cm.display.viewFrom = cm.display.viewTo = cm.doc.first;
        cm.display.view = [];
        cm.display.viewOffset = 0;
    }

    function viewCuttingPoint(cm, oldN, newN, dir) {
        var index = findViewIndex(cm, oldN), diff, view = cm.display.view;
        if (!sawCollapsedSpans || newN == cm.doc.first+cm.doc.size) {
            return {index: index, lineN: newN}
        }
        var n = cm.display.viewFrom;
        for (var i = 0; i < index; i++) {
            n += view[i].size;
        }
        if (n != oldN) {
            if (dir > 0) {
                if (index == view.length-1) {
                    return null
                }
                diff = (n+view[index].size)-oldN;
                index++;
            } else {
                diff = n-oldN;
            }
            oldN += diff;
            newN += diff;
        }
        while (visualLineNo(cm.doc, newN) != newN) {
            if (index == (dir < 0 ? 0 : view.length-1)) {
                return null
            }
            newN += dir * view[index-(dir < 0 ? 1 : 0)].size;
            index += dir;
        }
        return {index: index, lineN: newN}
    }

    // Force the view to cover a given range, adding empty view element
    // or clipping off existing ones as needed.
    function adjustView(cm, from, to) {
        var display = cm.display, view = display.view;
        if (view.length == 0 || from >= display.viewTo || to <= display.viewFrom) {
            display.view = buildViewArray(cm, from, to);
            display.viewFrom = from;
        } else {
            if (display.viewFrom > from) {
                display.view = buildViewArray(cm, from, display.viewFrom).concat(display.view);
            } else if (display.viewFrom < from) {
                display.view = display.view.slice(findViewIndex(cm, from));
            }
            display.viewFrom = from;
            if (display.viewTo < to) {
                display.view = display.view.concat(buildViewArray(cm, display.viewTo, to));
            } else if (display.viewTo > to) {
                display.view = display.view.slice(0, findViewIndex(cm, to));
            }
        }
        display.viewTo = to;
    }

    // Count the number of lines in the view whose DOM representation is
    // out of date (or nonexistent).
    function countDirtyView(cm) {
        var view = cm.display.view, dirty = 0;
        for (var i = 0; i < view.length; i++) {
            var lineView = view[i];
            if (!lineView.hidden && (!lineView.node || lineView.changes)) {
                ++dirty;
            }
        }
        return dirty
    }

    function updateSelection(cm) {
        cm.display.input.showSelection(cm.display.input.prepareSelection());
    }

    function prepareSelection(cm, primary) {
        if (primary === void 0) primary = true;

        var doc = cm.doc, result = {};
        var curFragment = result.cursors = document.createDocumentFragment();
        var selFragment = result.selection = document.createDocumentFragment();

        var customCursor = cm.options.$customCursor;
        if (customCursor) {
            primary = true;
        }
        for (var i = 0; i < doc.sel.ranges.length; i++) {
            if (!primary && i == doc.sel.primIndex) {
                continue
            }
            var range = doc.sel.ranges[i];
            if (range.from().line >= cm.display.viewTo || range.to().line < cm.display.viewFrom) {
                continue
            }
            var collapsed = range.empty();
            if (customCursor) {
                var head = customCursor(cm, range);
                if (head) {
                    drawSelectionCursor(cm, head, curFragment);
                }
            } else if (collapsed || cm.options.showCursorWhenSelecting) {
                drawSelectionCursor(cm, range.head, curFragment);
            }
            if (!collapsed) {
                drawSelectionRange(cm, range, selFragment);
            }
        }
        return result
    }

    // Draws a cursor for the given range
    function drawSelectionCursor(cm, head, output) {
        var pos = cursorCoords(cm, head, "div", null, null, !cm.options.singleCursorHeightPerLine);

        var cursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor"));
        cursor.style.left = pos.left+"px";
        cursor.style.top = pos.top+"px";
        cursor.style.height = Math.max(0, pos.bottom-pos.top) * cm.options.cursorHeight+"px";

        if (/\bcm-fat-cursor\b/.test(cm.getWrapperElement().className)) {
            var charPos = charCoords(cm, head, "div", null, null);
            var width = charPos.right-charPos.left;
            cursor.style.width = (width > 0 ? width : cm.defaultCharWidth())+"px";
        }

        if (pos.other) {
            // Secondary cursor, shown when on a 'jump' in bi-directional text
            var otherCursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor CodeMirror-secondarycursor"));
            otherCursor.style.display = "";
            otherCursor.style.left = pos.other.left+"px";
            otherCursor.style.top = pos.other.top+"px";
            otherCursor.style.height = (pos.other.bottom-pos.other.top) * .85+"px";
        }
    }

    function cmpCoords(a, b) {
        return a.top-b.top || a.left-b.left
    }

    // Draws the given range as a highlighted selection
    function drawSelectionRange(cm, range, output) {
        var display = cm.display, doc = cm.doc;
        var fragment = document.createDocumentFragment();
        var padding = paddingH(cm.display), leftSide = padding.left;
        var rightSide = Math.max(display.sizerWidth, displayWidth(cm)-display.sizer.offsetLeft)-padding.right;
        var docLTR = doc.direction == "ltr";

        function add(left, top, width, bottom) {
            if (top < 0) {
                top = 0;
            }
            top = Math.round(top);
            bottom = Math.round(bottom);
            fragment.appendChild(elt("div", null, "CodeMirror-selected", ("position: absolute; left: "+left+"px;\n                             top: "+top+"px; width: "+(width == null ? rightSide-left : width)+"px;\n                             height: "+(bottom-top)+"px")));
        }

        function drawForLine(line, fromArg, toArg) {
            var lineObj = getLine(doc, line);
            var lineLen = lineObj.text.length;
            var start, end;

            function coords(ch, bias) {
                return charCoords(cm, Pos(line, ch), "div", lineObj, bias)
            }

            function wrapX(pos, dir, side) {
                var extent = wrappedLineExtentChar(cm, lineObj, null, pos);
                var prop = (dir == "ltr") == (side == "after") ? "left" : "right";
                var ch = side == "after" ? extent.begin : extent.end-(/\s/.test(lineObj.text.charAt(extent.end-1)) ? 2 : 1);
                return coords(ch, prop)[prop]
            }

            var order = getOrder(lineObj, doc.direction);
            iterateBidiSections(order, fromArg || 0, toArg == null ? lineLen : toArg, function (from, to, dir, i) {
                var ltr = dir == "ltr";
                var fromPos = coords(from, ltr ? "left" : "right");
                var toPos = coords(to-1, ltr ? "right" : "left");

                var openStart = fromArg == null && from == 0, openEnd = toArg == null && to == lineLen;
                var first = i == 0, last = !order || i == order.length-1;
                if (toPos.top-fromPos.top <= 3) { // Single line
                    var openLeft = (docLTR ? openStart : openEnd) && first;
                    var openRight = (docLTR ? openEnd : openStart) && last;
                    var left = openLeft ? leftSide : (ltr ? fromPos : toPos).left;
                    var right = openRight ? rightSide : (ltr ? toPos : fromPos).right;
                    add(left, fromPos.top, right-left, fromPos.bottom);
                } else { // Multiple lines
                    var topLeft, topRight, botLeft, botRight;
                    if (ltr) {
                        topLeft = docLTR && openStart && first ? leftSide : fromPos.left;
                        topRight = docLTR ? rightSide : wrapX(from, dir, "before");
                        botLeft = docLTR ? leftSide : wrapX(to, dir, "after");
                        botRight = docLTR && openEnd && last ? rightSide : toPos.right;
                    } else {
                        topLeft = !docLTR ? leftSide : wrapX(from, dir, "before");
                        topRight = !docLTR && openStart && first ? rightSide : fromPos.right;
                        botLeft = !docLTR && openEnd && last ? leftSide : toPos.left;
                        botRight = !docLTR ? rightSide : wrapX(to, dir, "after");
                    }
                    add(topLeft, fromPos.top, topRight-topLeft, fromPos.bottom);
                    if (fromPos.bottom < toPos.top) {
                        add(leftSide, fromPos.bottom, null, toPos.top);
                    }
                    add(botLeft, toPos.top, botRight-botLeft, toPos.bottom);
                }

                if (!start || cmpCoords(fromPos, start) < 0) {
                    start = fromPos;
                }
                if (cmpCoords(toPos, start) < 0) {
                    start = toPos;
                }
                if (!end || cmpCoords(fromPos, end) < 0) {
                    end = fromPos;
                }
                if (cmpCoords(toPos, end) < 0) {
                    end = toPos;
                }
            });
            return {start: start, end: end}
        }

        var sFrom = range.from(), sTo = range.to();
        if (sFrom.line == sTo.line) {
            drawForLine(sFrom.line, sFrom.ch, sTo.ch);
        } else {
            var fromLine = getLine(doc, sFrom.line), toLine = getLine(doc, sTo.line);
            var singleVLine = visualLine(fromLine) == visualLine(toLine);
            var leftEnd = drawForLine(sFrom.line, sFrom.ch, singleVLine ? fromLine.text.length+1 : null).end;
            var rightStart = drawForLine(sTo.line, singleVLine ? 0 : null, sTo.ch).start;
            if (singleVLine) {
                if (leftEnd.top < rightStart.top-2) {
                    add(leftEnd.right, leftEnd.top, null, leftEnd.bottom);
                    add(leftSide, rightStart.top, rightStart.left, rightStart.bottom);
                } else {
                    add(leftEnd.right, leftEnd.top, rightStart.left-leftEnd.right, leftEnd.bottom);
                }
            }
            if (leftEnd.bottom < rightStart.top) {
                add(leftSide, leftEnd.bottom, null, rightStart.top);
            }
        }

        output.appendChild(fragment);
    }

    // Cursor-blinking
    function restartBlink(cm) {
        if (!cm.state.focused) {
            return
        }
        var display = cm.display;
        clearInterval(display.blinker);
        var on = true;
        display.cursorDiv.style.visibility = "";
        if (cm.options.cursorBlinkRate > 0) {
            display.blinker = setInterval(function () {
                if (!cm.hasFocus()) {
                    onBlur(cm);
                }
                display.cursorDiv.style.visibility = (on = !on) ? "" : "hidden";
            }, cm.options.cursorBlinkRate);
        } else if (cm.options.cursorBlinkRate < 0) {
            display.cursorDiv.style.visibility = "hidden";
        }
    }

    function ensureFocus(cm) {
        if (!cm.hasFocus()) {
            cm.display.input.focus();
            if (!cm.state.focused) {
                onFocus(cm);
            }
        }
    }

    function delayBlurEvent(cm) {
        cm.state.delayingBlurEvent = true;
        setTimeout(function () {
            if (cm.state.delayingBlurEvent) {
                cm.state.delayingBlurEvent = false;
                if (cm.state.focused) {
                    onBlur(cm);
                }
            }
        }, 100);
    }

    function onFocus(cm, e) {
        if (cm.state.delayingBlurEvent && !cm.state.draggingText) {
            cm.state.delayingBlurEvent = false;
        }

        if (cm.options.readOnly == "nocursor") {
            return
        }
        if (!cm.state.focused) {
            signal(cm, "focus", cm, e);
            cm.state.focused = true;
            addClass(cm.display.wrapper, "CodeMirror-focused");
            // This test prevents this from firing when a context
            // menu is closed (since the input reset would kill the
            // select-all detection hack)
            if (!cm.curOp && cm.display.selForContextMenu != cm.doc.sel) {
                cm.display.input.reset();
                if (webkit) {
                    setTimeout(function () {
                        return cm.display.input.reset(true);
                    }, 20);
                } // Issue #1730
            }
            cm.display.input.receivedFocus();
        }
        restartBlink(cm);
    }

    function onBlur(cm, e) {
        if (cm.state.delayingBlurEvent) {
            return
        }

        if (cm.state.focused) {
            signal(cm, "blur", cm, e);
            cm.state.focused = false;
            rmClass(cm.display.wrapper, "CodeMirror-focused");
        }
        clearInterval(cm.display.blinker);
        setTimeout(function () {
            if (!cm.state.focused) {
                cm.display.shift = false;
            }
        }, 150);
    }

    // Read the actual heights of the rendered lines, and update their
    // stored heights to match.
    function updateHeightsInViewport(cm) {
        var display = cm.display;
        var prevBottom = display.lineDiv.offsetTop;
        var viewTop = Math.max(0, display.scroller.getBoundingClientRect().top);
        var oldHeight = display.lineDiv.getBoundingClientRect().top;
        var mustScroll = 0;
        for (var i = 0; i < display.view.length; i++) {
            var cur = display.view[i], wrapping = cm.options.lineWrapping;
            var height = (void 0), width = 0;
            if (cur.hidden) {
                continue
            }
            oldHeight += cur.line.height;
            if (ie && ie_version < 8) {
                var bot = cur.node.offsetTop+cur.node.offsetHeight;
                height = bot-prevBottom;
                prevBottom = bot;
            } else {
                var box = cur.node.getBoundingClientRect();
                height = box.bottom-box.top;
                // Check that lines don't extend past the right of the current
                // editor width
                if (!wrapping && cur.text.firstChild) {
                    width = cur.text.firstChild.getBoundingClientRect().right-box.left-1;
                }
            }
            var diff = cur.line.height-height;
            if (diff > .005 || diff < -.005) {
                if (oldHeight < viewTop) {
                    mustScroll -= diff;
                }
                updateLineHeight(cur.line, height);
                updateWidgetHeight(cur.line);
                if (cur.rest) {
                    for (var j = 0; j < cur.rest.length; j++) {
                        updateWidgetHeight(cur.rest[j]);
                    }
                }
            }
            if (width > cm.display.sizerWidth) {
                var chWidth = Math.ceil(width / charWidth(cm.display));
                if (chWidth > cm.display.maxLineLength) {
                    cm.display.maxLineLength = chWidth;
                    cm.display.maxLine = cur.line;
                    cm.display.maxLineChanged = true;
                }
            }
        }
        if (Math.abs(mustScroll) > 2) {
            display.scroller.scrollTop += mustScroll;
        }
    }

    // Read and store the height of line widgets associated with the
    // given line.
    function updateWidgetHeight(line) {
        if (line.widgets) {
            for (var i = 0; i < line.widgets.length; ++i) {
                var w = line.widgets[i], parent = w.node.parentNode;
                if (parent) {
                    w.height = parent.offsetHeight;
                }
            }
        }
    }

    // Compute the lines that are visible in a given viewport (defaults
    // the the current scroll position). viewport may contain top,
    // height, and ensure (see op.scrollToPos) properties.
    function visibleLines(display, doc, viewport) {
        var top = viewport && viewport.top != null ? Math.max(0, viewport.top) : display.scroller.scrollTop;
        top = Math.floor(top-paddingTop(display));
        var bottom = viewport && viewport.bottom != null ? viewport.bottom : top+display.wrapper.clientHeight;

        var from = lineAtHeight(doc, top), to = lineAtHeight(doc, bottom);
        // Ensure is a {from: {line, ch}, to: {line, ch}} object, and
        // forces those lines into the viewport (if possible).
        if (viewport && viewport.ensure) {
            var ensureFrom = viewport.ensure.from.line, ensureTo = viewport.ensure.to.line;
            if (ensureFrom < from) {
                from = ensureFrom;
                to = lineAtHeight(doc, heightAtLine(getLine(doc, ensureFrom))+display.wrapper.clientHeight);
            } else if (Math.min(ensureTo, doc.lastLine()) >= to) {
                from = lineAtHeight(doc, heightAtLine(getLine(doc, ensureTo))-display.wrapper.clientHeight);
                to = ensureTo;
            }
        }
        return {from: from, to: Math.max(to, from+1)}
    }

    // SCROLLING THINGS INTO VIEW

    // If an editor sits on the top or bottom of the window, partially
    // scrolled out of view, this ensures that the cursor is visible.
    function maybeScrollWindow(cm, rect) {
        if (signalDOMEvent(cm, "scrollCursorIntoView")) {
            return
        }

        var display = cm.display, box = display.sizer.getBoundingClientRect(), doScroll = null;
        if (rect.top+box.top < 0) {
            doScroll = true;
        } else if (rect.bottom+box.top > (window.innerHeight || document.documentElement.clientHeight)) {
            doScroll = false;
        }
        if (doScroll != null && !phantom) {
            var scrollNode = elt("div", "\u200b", null, ("position: absolute;\n                         top: "+(rect.top-display.viewOffset-paddingTop(cm.display))+"px;\n                         height: "+(rect.bottom-rect.top+scrollGap(cm)+display.barHeight)+"px;\n                         left: "+(rect.left)+"px; width: "+(Math.max(2, rect.right-rect.left))+"px;"));
            cm.display.lineSpace.appendChild(scrollNode);
            scrollNode.scrollIntoView(doScroll);
            cm.display.lineSpace.removeChild(scrollNode);
        }
    }

    // Scroll a given position into view (immediately), verifying that
    // it actually became visible (as line heights are accurately
    // measured, the position of something may 'drift' during drawing).
    function scrollPosIntoView(cm, pos, end, margin) {
        if (margin == null) {
            margin = 0;
        }
        var rect;
        if (!cm.options.lineWrapping && pos == end) {
            // Set pos and end to the cursor positions around the character pos sticks to
            // If pos.sticky == "before", that is around pos.ch - 1, otherwise around pos.ch
            // If pos == Pos(_, 0, "before"), pos and end are unchanged
            end = pos.sticky == "before" ? Pos(pos.line, pos.ch+1, "before") : pos;
            pos = pos.ch ? Pos(pos.line, pos.sticky == "before" ? pos.ch-1 : pos.ch, "after") : pos;
        }
        for (var limit = 0; limit < 5; limit++) {
            var changed = false;
            var coords = cursorCoords(cm, pos);
            var endCoords = !end || end == pos ? coords : cursorCoords(cm, end);
            rect = {
                left: Math.min(coords.left, endCoords.left),
                top: Math.min(coords.top, endCoords.top)-margin,
                right: Math.max(coords.left, endCoords.left),
                bottom: Math.max(coords.bottom, endCoords.bottom)+margin
            };
            var scrollPos = calculateScrollPos(cm, rect);
            var startTop = cm.doc.scrollTop, startLeft = cm.doc.scrollLeft;
            if (scrollPos.scrollTop != null) {
                updateScrollTop(cm, scrollPos.scrollTop);
                if (Math.abs(cm.doc.scrollTop-startTop) > 1) {
                    changed = true;
                }
            }
            if (scrollPos.scrollLeft != null) {
                setScrollLeft(cm, scrollPos.scrollLeft);
                if (Math.abs(cm.doc.scrollLeft-startLeft) > 1) {
                    changed = true;
                }
            }
            if (!changed) {
                break
            }
        }
        return rect
    }

    // Scroll a given set of coordinates into view (immediately).
    function scrollIntoView(cm, rect) {
        var scrollPos = calculateScrollPos(cm, rect);
        if (scrollPos.scrollTop != null) {
            updateScrollTop(cm, scrollPos.scrollTop);
        }
        if (scrollPos.scrollLeft != null) {
            setScrollLeft(cm, scrollPos.scrollLeft);
        }
    }

    // Calculate a new scroll position needed to scroll the given
    // rectangle into view. Returns an object with scrollTop and
    // scrollLeft properties. When these are undefined, the
    // vertical/horizontal position does not need to be adjusted.
    function calculateScrollPos(cm, rect) {
        var display = cm.display, snapMargin = textHeight(cm.display);
        if (rect.top < 0) {
            rect.top = 0;
        }
        var screentop = cm.curOp && cm.curOp.scrollTop != null ? cm.curOp.scrollTop : display.scroller.scrollTop;
        var screen = displayHeight(cm), result = {};
        if (rect.bottom-rect.top > screen) {
            rect.bottom = rect.top+screen;
        }
        var docBottom = cm.doc.height+paddingVert(display);
        var atTop = rect.top < snapMargin, atBottom = rect.bottom > docBottom-snapMargin;
        if (rect.top < screentop) {
            result.scrollTop = atTop ? 0 : rect.top;
        } else if (rect.bottom > screentop+screen) {
            var newTop = Math.min(rect.top, (atBottom ? docBottom : rect.bottom)-screen);
            if (newTop != screentop) {
                result.scrollTop = newTop;
            }
        }

        var gutterSpace = cm.options.fixedGutter ? 0 : display.gutters.offsetWidth;
        var screenleft = cm.curOp && cm.curOp.scrollLeft != null ? cm.curOp.scrollLeft : display.scroller.scrollLeft-gutterSpace;
        var screenw = displayWidth(cm)-display.gutters.offsetWidth;
        var tooWide = rect.right-rect.left > screenw;
        if (tooWide) {
            rect.right = rect.left+screenw;
        }
        if (rect.left < 10) {
            result.scrollLeft = 0;
        } else if (rect.left < screenleft) {
            result.scrollLeft = Math.max(0, rect.left+gutterSpace-(tooWide ? 0 : 10));
        } else if (rect.right > screenw+screenleft-3) {
            result.scrollLeft = rect.right+(tooWide ? 0 : 10)-screenw;
        }
        return result
    }

    // Store a relative adjustment to the scroll position in the current
    // operation (to be applied when the operation finishes).
    function addToScrollTop(cm, top) {
        if (top == null) {
            return
        }
        resolveScrollToPos(cm);
        cm.curOp.scrollTop = (cm.curOp.scrollTop == null ? cm.doc.scrollTop : cm.curOp.scrollTop)+top;
    }

    // Make sure that at the end of the operation the current cursor is
    // shown.
    function ensureCursorVisible(cm) {
        resolveScrollToPos(cm);
        var cur = cm.getCursor();
        cm.curOp.scrollToPos = {from: cur, to: cur, margin: cm.options.cursorScrollMargin};
    }

    function scrollToCoords(cm, x, y) {
        if (x != null || y != null) {
            resolveScrollToPos(cm);
        }
        if (x != null) {
            cm.curOp.scrollLeft = x;
        }
        if (y != null) {
            cm.curOp.scrollTop = y;
        }
    }

    function scrollToRange(cm, range) {
        resolveScrollToPos(cm);
        cm.curOp.scrollToPos = range;
    }

    // When an operation has its scrollToPos property set, and another
    // scroll action is applied before the end of the operation, this
    // 'simulates' scrolling that position into view in a cheap way, so
    // that the effect of intermediate scroll commands is not ignored.
    function resolveScrollToPos(cm) {
        var range = cm.curOp.scrollToPos;
        if (range) {
            cm.curOp.scrollToPos = null;
            var from = estimateCoords(cm, range.from), to = estimateCoords(cm, range.to);
            scrollToCoordsRange(cm, from, to, range.margin);
        }
    }

    function scrollToCoordsRange(cm, from, to, margin) {
        var sPos = calculateScrollPos(cm, {
            left: Math.min(from.left, to.left),
            top: Math.min(from.top, to.top)-margin,
            right: Math.max(from.right, to.right),
            bottom: Math.max(from.bottom, to.bottom)+margin
        });
        scrollToCoords(cm, sPos.scrollLeft, sPos.scrollTop);
    }

    // Sync the scrollable area and scrollbars, ensure the viewport
    // covers the visible area.
    function updateScrollTop(cm, val) {
        if (Math.abs(cm.doc.scrollTop-val) < 2) {
            return
        }
        if (!gecko) {
            updateDisplaySimple(cm, {top: val});
        }
        setScrollTop(cm, val, true);
        if (gecko) {
            updateDisplaySimple(cm);
        }
        startWorker(cm, 100);
    }

    function setScrollTop(cm, val, forceScroll) {
        val = Math.max(0, Math.min(cm.display.scroller.scrollHeight-cm.display.scroller.clientHeight, val));
        if (cm.display.scroller.scrollTop == val && !forceScroll) {
            return
        }
        cm.doc.scrollTop = val;
        cm.display.scrollbars.setScrollTop(val);
        if (cm.display.scroller.scrollTop != val) {
            cm.display.scroller.scrollTop = val;
        }
    }

    // Sync scroller and scrollbar, ensure the gutter elements are
    // aligned.
    function setScrollLeft(cm, val, isScroller, forceScroll) {
        val = Math.max(0, Math.min(val, cm.display.scroller.scrollWidth-cm.display.scroller.clientWidth));
        if ((isScroller ? val == cm.doc.scrollLeft : Math.abs(cm.doc.scrollLeft-val) < 2) && !forceScroll) {
            return
        }
        cm.doc.scrollLeft = val;
        alignHorizontally(cm);
        if (cm.display.scroller.scrollLeft != val) {
            cm.display.scroller.scrollLeft = val;
        }
        cm.display.scrollbars.setScrollLeft(val);
    }

    // SCROLLBARS

    // Prepare DOM reads needed to update the scrollbars. Done in one
    // shot to minimize update/measure roundtrips.
    function measureForScrollbars(cm) {
        var d = cm.display, gutterW = d.gutters.offsetWidth;
        var docH = Math.round(cm.doc.height+paddingVert(cm.display));
        return {
            clientHeight: d.scroller.clientHeight,
            viewHeight: d.wrapper.clientHeight,
            scrollWidth: d.scroller.scrollWidth, clientWidth: d.scroller.clientWidth,
            viewWidth: d.wrapper.clientWidth,
            barLeft: cm.options.fixedGutter ? gutterW : 0,
            docHeight: docH,
            scrollHeight: docH+scrollGap(cm)+d.barHeight,
            nativeBarWidth: d.nativeBarWidth,
            gutterWidth: gutterW
        }
    }

    var NativeScrollbars = function (place, scroll, cm) {
        this.cm = cm;
        var vert = this.vert = elt("div", [elt("div", null, null, "min-width: 1px")], "CodeMirror-vscrollbar");
        var horiz = this.horiz = elt("div", [elt("div", null, null, "height: 100%; min-height: 1px")], "CodeMirror-hscrollbar");
        vert.tabIndex = horiz.tabIndex = -1;
        place(vert);
        place(horiz);

        on(vert, "scroll", function () {
            if (vert.clientHeight) {
                scroll(vert.scrollTop, "vertical");
            }
        });
        on(horiz, "scroll", function () {
            if (horiz.clientWidth) {
                scroll(horiz.scrollLeft, "horizontal");
            }
        });

        this.checkedZeroWidth = false;
        // Need to set a minimum width to see the scrollbar on IE7 (but must not set it on IE8).
        if (ie && ie_version < 8) {
            this.horiz.style.minHeight = this.vert.style.minWidth = "18px";
        }
    };

    NativeScrollbars.prototype.update = function (measure) {
        var needsH = measure.scrollWidth > measure.clientWidth+1;
        var needsV = measure.scrollHeight > measure.clientHeight+1;
        var sWidth = measure.nativeBarWidth;

        if (needsV) {
            this.vert.style.display = "block";
            this.vert.style.bottom = needsH ? sWidth+"px" : "0";
            var totalHeight = measure.viewHeight-(needsH ? sWidth : 0);
            // A bug in IE8 can cause this value to be negative, so guard it.
            this.vert.firstChild.style.height =
                Math.max(0, measure.scrollHeight-measure.clientHeight+totalHeight)+"px";
        } else {
            this.vert.scrollTop = 0;
            this.vert.style.display = "";
            this.vert.firstChild.style.height = "0";
        }

        if (needsH) {
            this.horiz.style.display = "block";
            this.horiz.style.right = needsV ? sWidth+"px" : "0";
            this.horiz.style.left = measure.barLeft+"px";
            var totalWidth = measure.viewWidth-measure.barLeft-(needsV ? sWidth : 0);
            this.horiz.firstChild.style.width =
                Math.max(0, measure.scrollWidth-measure.clientWidth+totalWidth)+"px";
        } else {
            this.horiz.style.display = "";
            this.horiz.firstChild.style.width = "0";
        }

        if (!this.checkedZeroWidth && measure.clientHeight > 0) {
            if (sWidth == 0) {
                this.zeroWidthHack();
            }
            this.checkedZeroWidth = true;
        }

        return {right: needsV ? sWidth : 0, bottom: needsH ? sWidth : 0}
    };

    NativeScrollbars.prototype.setScrollLeft = function (pos) {
        if (this.horiz.scrollLeft != pos) {
            this.horiz.scrollLeft = pos;
        }
        if (this.disableHoriz) {
            this.enableZeroWidthBar(this.horiz, this.disableHoriz, "horiz");
        }
    };

    NativeScrollbars.prototype.setScrollTop = function (pos) {
        if (this.vert.scrollTop != pos) {
            this.vert.scrollTop = pos;
        }
        if (this.disableVert) {
            this.enableZeroWidthBar(this.vert, this.disableVert, "vert");
        }
    };

    NativeScrollbars.prototype.zeroWidthHack = function () {
        var w = mac && !mac_geMountainLion ? "12px" : "18px";
        this.horiz.style.height = this.vert.style.width = w;
        this.horiz.style.pointerEvents = this.vert.style.pointerEvents = "none";
        this.disableHoriz = new Delayed;
        this.disableVert = new Delayed;
    };

    NativeScrollbars.prototype.enableZeroWidthBar = function (bar, delay, type) {
        bar.style.pointerEvents = "auto";

        function maybeDisable() {
            // To find out whether the scrollbar is still visible, we
            // check whether the element under the pixel in the bottom
            // right corner of the scrollbar box is the scrollbar box
            // itself (when the bar is still visible) or its filler child
            // (when the bar is hidden). If it is still visible, we keep
            // it enabled, if it's hidden, we disable pointer events.
            var box = bar.getBoundingClientRect();
            var elt = type == "vert" ? document.elementFromPoint(box.right-1, (box.top+box.bottom) / 2)
                : document.elementFromPoint((box.right+box.left) / 2, box.bottom-1);
            if (elt != bar) {
                bar.style.pointerEvents = "none";
            } else {
                delay.set(1000, maybeDisable);
            }
        }

        delay.set(1000, maybeDisable);
    };

    NativeScrollbars.prototype.clear = function () {
        var parent = this.horiz.parentNode;
        parent.removeChild(this.horiz);
        parent.removeChild(this.vert);
    };

    var NullScrollbars = function () {
    };

    NullScrollbars.prototype.update = function () {
        return {bottom: 0, right: 0}
    };
    NullScrollbars.prototype.setScrollLeft = function () {
    };
    NullScrollbars.prototype.setScrollTop = function () {
    };
    NullScrollbars.prototype.clear = function () {
    };

    function updateScrollbars(cm, measure) {
        if (!measure) {
            measure = measureForScrollbars(cm);
        }
        var startWidth = cm.display.barWidth, startHeight = cm.display.barHeight;
        updateScrollbarsInner(cm, measure);
        for (var i = 0; i < 4 && startWidth != cm.display.barWidth || startHeight != cm.display.barHeight; i++) {
            if (startWidth != cm.display.barWidth && cm.options.lineWrapping) {
                updateHeightsInViewport(cm);
            }
            updateScrollbarsInner(cm, measureForScrollbars(cm));
            startWidth = cm.display.barWidth;
            startHeight = cm.display.barHeight;
        }
    }

    // Re-synchronize the fake scrollbars with the actual size of the
    // content.
    function updateScrollbarsInner(cm, measure) {
        var d = cm.display;
        var sizes = d.scrollbars.update(measure);

        d.sizer.style.paddingRight = (d.barWidth = sizes.right)+"px";
        d.sizer.style.paddingBottom = (d.barHeight = sizes.bottom)+"px";
        d.heightForcer.style.borderBottom = sizes.bottom+"px solid transparent";

        if (sizes.right && sizes.bottom) {
            d.scrollbarFiller.style.display = "block";
            d.scrollbarFiller.style.height = sizes.bottom+"px";
            d.scrollbarFiller.style.width = sizes.right+"px";
        } else {
            d.scrollbarFiller.style.display = "";
        }
        if (sizes.bottom && cm.options.coverGutterNextToScrollbar && cm.options.fixedGutter) {
            d.gutterFiller.style.display = "block";
            d.gutterFiller.style.height = sizes.bottom+"px";
            d.gutterFiller.style.width = measure.gutterWidth+"px";
        } else {
            d.gutterFiller.style.display = "";
        }
    }

    var scrollbarModel = {"native": NativeScrollbars, "null": NullScrollbars};

    function initScrollbars(cm) {
        if (cm.display.scrollbars) {
            cm.display.scrollbars.clear();
            if (cm.display.scrollbars.addClass) {
                rmClass(cm.display.wrapper, cm.display.scrollbars.addClass);
            }
        }

        cm.display.scrollbars = new scrollbarModel[cm.options.scrollbarStyle](function (node) {
            cm.display.wrapper.insertBefore(node, cm.display.scrollbarFiller);
            // Prevent clicks in the scrollbars from killing focus
            on(node, "mousedown", function () {
                if (cm.state.focused) {
                    setTimeout(function () {
                        return cm.display.input.focus();
                    }, 0);
                }
            });
            node.setAttribute("cm-not-content", "true");
        }, function (pos, axis) {
            if (axis == "horizontal") {
                setScrollLeft(cm, pos);
            } else {
                updateScrollTop(cm, pos);
            }
        }, cm);
        if (cm.display.scrollbars.addClass) {
            addClass(cm.display.wrapper, cm.display.scrollbars.addClass);
        }
    }

    // Operations are used to wrap a series of changes to the editor
    // state in such a way that each change won't have to update the
    // cursor and display (which would be awkward, slow, and
    // error-prone). Instead, display updates are batched and then all
    // combined and executed at once.

    var nextOpId = 0;

    // Start a new operation.
    function startOperation(cm) {
        cm.curOp = {
            cm: cm,
            viewChanged: false,      // Flag that indicates that lines might need to be redrawn
            startHeight: cm.doc.height, // Used to detect need to update scrollbar
            forceUpdate: false,      // Used to force a redraw
            updateInput: 0,       // Whether to reset the input textarea
            typing: false,           // Whether this reset should be careful to leave existing text (for compositing)
            changeObjs: null,        // Accumulated changes, for firing change events
            cursorActivityHandlers: null, // Set of handlers to fire cursorActivity on
            cursorActivityCalled: 0, // Tracks which cursorActivity handlers have been called already
            selectionChanged: false, // Whether the selection needs to be redrawn
            updateMaxLine: false,    // Set when the widest line needs to be determined anew
            scrollLeft: null, scrollTop: null, // Intermediate scroll position, not pushed to DOM yet
            scrollToPos: null,       // Used to scroll to a specific position
            focus: false,
            id: ++nextOpId,          // Unique ID
            markArrays: null         // Used by addMarkedSpan
        };
        pushOperation(cm.curOp);
    }

    // Finish an operation, updating the display and signalling delayed events
    function endOperation(cm) {
        var op = cm.curOp;
        if (op) {
            finishOperation(op, function (group) {
                for (var i = 0; i < group.ops.length; i++) {
                    group.ops[i].cm.curOp = null;
                }
                endOperations(group);
            });
        }
    }

    // The DOM updates done when an operation finishes are batched so
    // that the minimum number of relayouts are required.
    function endOperations(group) {
        var ops = group.ops;
        for (var i = 0; i < ops.length; i++) // Read DOM
        {
            endOperation_R1(ops[i]);
        }
        for (var i$1 = 0; i$1 < ops.length; i$1++) // Write DOM (maybe)
        {
            endOperation_W1(ops[i$1]);
        }
        for (var i$2 = 0; i$2 < ops.length; i$2++) // Read DOM
        {
            endOperation_R2(ops[i$2]);
        }
        for (var i$3 = 0; i$3 < ops.length; i$3++) // Write DOM (maybe)
        {
            endOperation_W2(ops[i$3]);
        }
        for (var i$4 = 0; i$4 < ops.length; i$4++) // Read DOM
        {
            endOperation_finish(ops[i$4]);
        }
    }

    function endOperation_R1(op) {
        var cm = op.cm, display = cm.display;
        maybeClipScrollbars(cm);
        if (op.updateMaxLine) {
            findMaxLine(cm);
        }

        op.mustUpdate = op.viewChanged || op.forceUpdate || op.scrollTop != null ||
            op.scrollToPos && (op.scrollToPos.from.line < display.viewFrom ||
                op.scrollToPos.to.line >= display.viewTo) ||
            display.maxLineChanged && cm.options.lineWrapping;
        op.update = op.mustUpdate &&
            new DisplayUpdate(cm, op.mustUpdate && {top: op.scrollTop, ensure: op.scrollToPos}, op.forceUpdate);
    }

    function endOperation_W1(op) {
        op.updatedDisplay = op.mustUpdate && updateDisplayIfNeeded(op.cm, op.update);
    }

    function endOperation_R2(op) {
        var cm = op.cm, display = cm.display;
        if (op.updatedDisplay) {
            updateHeightsInViewport(cm);
        }

        op.barMeasure = measureForScrollbars(cm);

        // If the max line changed since it was last measured, measure it,
        // and ensure the document's width matches it.
        // updateDisplay_W2 will use these properties to do the actual resizing
        if (display.maxLineChanged && !cm.options.lineWrapping) {
            op.adjustWidthTo = measureChar(cm, display.maxLine, display.maxLine.text.length).left+3;
            cm.display.sizerWidth = op.adjustWidthTo;
            op.barMeasure.scrollWidth =
                Math.max(display.scroller.clientWidth, display.sizer.offsetLeft+op.adjustWidthTo+scrollGap(cm)+cm.display.barWidth);
            op.maxScrollLeft = Math.max(0, display.sizer.offsetLeft+op.adjustWidthTo-displayWidth(cm));
        }

        if (op.updatedDisplay || op.selectionChanged) {
            op.preparedSelection = display.input.prepareSelection();
        }
    }

    function endOperation_W2(op) {
        var cm = op.cm;

        if (op.adjustWidthTo != null) {
            cm.display.sizer.style.minWidth = op.adjustWidthTo+"px";
            if (op.maxScrollLeft < cm.doc.scrollLeft) {
                setScrollLeft(cm, Math.min(cm.display.scroller.scrollLeft, op.maxScrollLeft), true);
            }
            cm.display.maxLineChanged = false;
        }

        var takeFocus = op.focus && op.focus == activeElt();
        if (op.preparedSelection) {
            cm.display.input.showSelection(op.preparedSelection, takeFocus);
        }
        if (op.updatedDisplay || op.startHeight != cm.doc.height) {
            updateScrollbars(cm, op.barMeasure);
        }
        if (op.updatedDisplay) {
            setDocumentHeight(cm, op.barMeasure);
        }

        if (op.selectionChanged) {
            restartBlink(cm);
        }

        if (cm.state.focused && op.updateInput) {
            cm.display.input.reset(op.typing);
        }
        if (takeFocus) {
            ensureFocus(op.cm);
        }
    }

    function endOperation_finish(op) {
        var cm = op.cm, display = cm.display, doc = cm.doc;

        if (op.updatedDisplay) {
            postUpdateDisplay(cm, op.update);
        }

        // Abort mouse wheel delta measurement, when scrolling explicitly
        if (display.wheelStartX != null && (op.scrollTop != null || op.scrollLeft != null || op.scrollToPos)) {
            display.wheelStartX = display.wheelStartY = null;
        }

        // Propagate the scroll position to the actual DOM scroller
        if (op.scrollTop != null) {
            setScrollTop(cm, op.scrollTop, op.forceScroll);
        }

        if (op.scrollLeft != null) {
            setScrollLeft(cm, op.scrollLeft, true, true);
        }
        // If we need to scroll a specific position into view, do so.
        if (op.scrollToPos) {
            var rect = scrollPosIntoView(cm, clipPos(doc, op.scrollToPos.from),
                clipPos(doc, op.scrollToPos.to), op.scrollToPos.margin);
            maybeScrollWindow(cm, rect);
        }

        // Fire events for markers that are hidden/unidden by editing or
        // undoing
        var hidden = op.maybeHiddenMarkers, unhidden = op.maybeUnhiddenMarkers;
        if (hidden) {
            for (var i = 0; i < hidden.length; ++i) {
                if (!hidden[i].lines.length) {
                    signal(hidden[i], "hide");
                }
            }
        }
        if (unhidden) {
            for (var i$1 = 0; i$1 < unhidden.length; ++i$1) {
                if (unhidden[i$1].lines.length) {
                    signal(unhidden[i$1], "unhide");
                }
            }
        }

        if (display.wrapper.offsetHeight) {
            doc.scrollTop = cm.display.scroller.scrollTop;
        }

        // Fire change events, and delayed event handlers
        if (op.changeObjs) {
            signal(cm, "changes", cm, op.changeObjs);
        }
        if (op.update) {
            op.update.finish();
        }
    }

    // Run the given function in an operation
    function runInOp(cm, f) {
        if (cm.curOp) {
            return f()
        }
        startOperation(cm);
        try {
            return f()
        } finally {
            endOperation(cm);
        }
    }

    // Wraps a function in an operation. Returns the wrapped function.
    function operation(cm, f) {
        return function () {
            if (cm.curOp) {
                return f.apply(cm, arguments)
            }
            startOperation(cm);
            try {
                return f.apply(cm, arguments)
            } finally {
                endOperation(cm);
            }
        }
    }

    // Used to add methods to editor and doc instances, wrapping them in
    // operations.
    function methodOp(f) {
        return function () {
            if (this.curOp) {
                return f.apply(this, arguments)
            }
            startOperation(this);
            try {
                return f.apply(this, arguments)
            } finally {
                endOperation(this);
            }
        }
    }

    function docMethodOp(f) {
        return function () {
            var cm = this.cm;
            if (!cm || cm.curOp) {
                return f.apply(this, arguments)
            }
            startOperation(cm);
            try {
                return f.apply(this, arguments)
            } finally {
                endOperation(cm);
            }
        }
    }

    // HIGHLIGHT WORKER

    function startWorker(cm, time) {
        if (cm.doc.highlightFrontier < cm.display.viewTo) {
            cm.state.highlight.set(time, bind(highlightWorker, cm));
        }
    }

    function highlightWorker(cm) {
        var doc = cm.doc;
        if (doc.highlightFrontier >= cm.display.viewTo) {
            return
        }
        var end = +new Date+cm.options.workTime;
        var context = getContextBefore(cm, doc.highlightFrontier);
        var changedLines = [];

        doc.iter(context.line, Math.min(doc.first+doc.size, cm.display.viewTo+500), function (line) {
            if (context.line >= cm.display.viewFrom) { // Visible
                var oldStyles = line.styles;
                var resetState = line.text.length > cm.options.maxHighlightLength ? copyState(doc.mode, context.state) : null;
                var highlighted = highlightLine(cm, line, context, true);
                if (resetState) {
                    context.state = resetState;
                }
                line.styles = highlighted.styles;
                var oldCls = line.styleClasses, newCls = highlighted.classes;
                if (newCls) {
                    line.styleClasses = newCls;
                } else if (oldCls) {
                    line.styleClasses = null;
                }
                var ischange = !oldStyles || oldStyles.length != line.styles.length ||
                    oldCls != newCls && (!oldCls || !newCls || oldCls.bgClass != newCls.bgClass || oldCls.textClass != newCls.textClass);
                for (var i = 0; !ischange && i < oldStyles.length; ++i) {
                    ischange = oldStyles[i] != line.styles[i];
                }
                if (ischange) {
                    changedLines.push(context.line);
                }
                line.stateAfter = context.save();
                context.nextLine();
            } else {
                if (line.text.length <= cm.options.maxHighlightLength) {
                    processLine(cm, line.text, context);
                }
                line.stateAfter = context.line % 5 == 0 ? context.save() : null;
                context.nextLine();
            }
            if (+new Date > end) {
                startWorker(cm, cm.options.workDelay);
                return true
            }
        });
        doc.highlightFrontier = context.line;
        doc.modeFrontier = Math.max(doc.modeFrontier, context.line);
        if (changedLines.length) {
            runInOp(cm, function () {
                for (var i = 0; i < changedLines.length; i++) {
                    regLineChange(cm, changedLines[i], "text");
                }
            });
        }
    }

    // DISPLAY DRAWING

    var DisplayUpdate = function (cm, viewport, force) {
        var display = cm.display;

        this.viewport = viewport;
        // Store some values that we'll need later (but don't want to force a relayout for)
        this.visible = visibleLines(display, cm.doc, viewport);
        this.editorIsHidden = !display.wrapper.offsetWidth;
        this.wrapperHeight = display.wrapper.clientHeight;
        this.wrapperWidth = display.wrapper.clientWidth;
        this.oldDisplayWidth = displayWidth(cm);
        this.force = force;
        this.dims = getDimensions(cm);
        this.events = [];
    };

    DisplayUpdate.prototype.signal = function (emitter, type) {
        if (hasHandler(emitter, type)) {
            this.events.push(arguments);
        }
    };
    DisplayUpdate.prototype.finish = function () {
        for (var i = 0; i < this.events.length; i++) {
            signal.apply(null, this.events[i]);
        }
    };

    function maybeClipScrollbars(cm) {
        var display = cm.display;
        if (!display.scrollbarsClipped && display.scroller.offsetWidth) {
            display.nativeBarWidth = display.scroller.offsetWidth-display.scroller.clientWidth;
            display.heightForcer.style.height = scrollGap(cm)+"px";
            display.sizer.style.marginBottom = -display.nativeBarWidth+"px";
            display.sizer.style.borderRightWidth = scrollGap(cm)+"px";
            display.scrollbarsClipped = true;
        }
    }

    function selectionSnapshot(cm) {
        if (cm.hasFocus()) {
            return null
        }
        var active = activeElt();
        if (!active || !contains(cm.display.lineDiv, active)) {
            return null
        }
        var result = {activeElt: active};
        if (window.getSelection) {
            var sel = window.getSelection();
            if (sel.anchorNode && sel.extend && contains(cm.display.lineDiv, sel.anchorNode)) {
                result.anchorNode = sel.anchorNode;
                result.anchorOffset = sel.anchorOffset;
                result.focusNode = sel.focusNode;
                result.focusOffset = sel.focusOffset;
            }
        }
        return result
    }

    function restoreSelection(snapshot) {
        if (!snapshot || !snapshot.activeElt || snapshot.activeElt == activeElt()) {
            return
        }
        snapshot.activeElt.focus();
        if (!/^(INPUT|TEXTAREA)$/.test(snapshot.activeElt.nodeName) &&
            snapshot.anchorNode && contains(document.body, snapshot.anchorNode) && contains(document.body, snapshot.focusNode)) {
            var sel = window.getSelection(), range = document.createRange();
            range.setEnd(snapshot.anchorNode, snapshot.anchorOffset);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            sel.extend(snapshot.focusNode, snapshot.focusOffset);
        }
    }

    // Does the actual updating of the line display. Bails out
    // (returning false) when there is nothing to be done and forced is
    // false.
    function updateDisplayIfNeeded(cm, update) {
        var display = cm.display, doc = cm.doc;

        if (update.editorIsHidden) {
            resetView(cm);
            return false
        }

        // Bail out if the visible area is already rendered and nothing changed.
        if (!update.force &&
            update.visible.from >= display.viewFrom && update.visible.to <= display.viewTo &&
            (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo) &&
            display.renderedView == display.view && countDirtyView(cm) == 0) {
            return false
        }

        if (maybeUpdateLineNumberWidth(cm)) {
            resetView(cm);
            update.dims = getDimensions(cm);
        }

        // Compute a suitable new viewport (from & to)
        var end = doc.first+doc.size;
        var from = Math.max(update.visible.from-cm.options.viewportMargin, doc.first);
        var to = Math.min(end, update.visible.to+cm.options.viewportMargin);
        if (display.viewFrom < from && from-display.viewFrom < 20) {
            from = Math.max(doc.first, display.viewFrom);
        }
        if (display.viewTo > to && display.viewTo-to < 20) {
            to = Math.min(end, display.viewTo);
        }
        if (sawCollapsedSpans) {
            from = visualLineNo(cm.doc, from);
            to = visualLineEndNo(cm.doc, to);
        }

        var different = from != display.viewFrom || to != display.viewTo ||
            display.lastWrapHeight != update.wrapperHeight || display.lastWrapWidth != update.wrapperWidth;
        adjustView(cm, from, to);

        display.viewOffset = heightAtLine(getLine(cm.doc, display.viewFrom));
        // Position the mover div to align with the current scroll position
        cm.display.mover.style.top = display.viewOffset+"px";

        var toUpdate = countDirtyView(cm);
        if (!different && toUpdate == 0 && !update.force && display.renderedView == display.view &&
            (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo)) {
            return false
        }

        // For big changes, we hide the enclosing element during the
        // update, since that speeds up the operations on most browsers.
        var selSnapshot = selectionSnapshot(cm);
        if (toUpdate > 4) {
            display.lineDiv.style.display = "none";
        }
        patchDisplay(cm, display.updateLineNumbers, update.dims);
        if (toUpdate > 4) {
            display.lineDiv.style.display = "";
        }
        display.renderedView = display.view;
        // There might have been a widget with a focused element that got
        // hidden or updated, if so re-focus it.
        restoreSelection(selSnapshot);

        // Prevent selection and cursors from interfering with the scroll
        // width and height.
        removeChildren(display.cursorDiv);
        removeChildren(display.selectionDiv);
        display.gutters.style.height = display.sizer.style.minHeight = 0;

        if (different) {
            display.lastWrapHeight = update.wrapperHeight;
            display.lastWrapWidth = update.wrapperWidth;
            startWorker(cm, 400);
        }

        display.updateLineNumbers = null;

        return true
    }

    function postUpdateDisplay(cm, update) {
        var viewport = update.viewport;

        for (var first = true; ; first = false) {
            if (!first || !cm.options.lineWrapping || update.oldDisplayWidth == displayWidth(cm)) {
                // Clip forced viewport to actual scrollable area.
                if (viewport && viewport.top != null) {
                    viewport = {top: Math.min(cm.doc.height+paddingVert(cm.display)-displayHeight(cm), viewport.top)};
                }
                // Updated line heights might result in the drawn area not
                // actually covering the viewport. Keep looping until it does.
                update.visible = visibleLines(cm.display, cm.doc, viewport);
                if (update.visible.from >= cm.display.viewFrom && update.visible.to <= cm.display.viewTo) {
                    break
                }
            } else if (first) {
                update.visible = visibleLines(cm.display, cm.doc, viewport);
            }
            if (!updateDisplayIfNeeded(cm, update)) {
                break
            }
            updateHeightsInViewport(cm);
            var barMeasure = measureForScrollbars(cm);
            updateSelection(cm);
            updateScrollbars(cm, barMeasure);
            setDocumentHeight(cm, barMeasure);
            update.force = false;
        }

        update.signal(cm, "update", cm);
        if (cm.display.viewFrom != cm.display.reportedViewFrom || cm.display.viewTo != cm.display.reportedViewTo) {
            update.signal(cm, "viewportChange", cm, cm.display.viewFrom, cm.display.viewTo);
            cm.display.reportedViewFrom = cm.display.viewFrom;
            cm.display.reportedViewTo = cm.display.viewTo;
        }
    }

    function updateDisplaySimple(cm, viewport) {
        var update = new DisplayUpdate(cm, viewport);
        if (updateDisplayIfNeeded(cm, update)) {
            updateHeightsInViewport(cm);
            postUpdateDisplay(cm, update);
            var barMeasure = measureForScrollbars(cm);
            updateSelection(cm);
            updateScrollbars(cm, barMeasure);
            setDocumentHeight(cm, barMeasure);
            update.finish();
        }
    }

    // Sync the actual display DOM structure with display.view, removing
    // nodes for lines that are no longer in view, and creating the ones
    // that are not there yet, and updating the ones that are out of
    // date.
    function patchDisplay(cm, updateNumbersFrom, dims) {
        var display = cm.display, lineNumbers = cm.options.lineNumbers;
        var container = display.lineDiv, cur = container.firstChild;

        function rm(node) {
            var next = node.nextSibling;
            // Works around a throw-scroll bug in OS X Webkit
            if (webkit && mac && cm.display.currentWheelTarget == node) {
                node.style.display = "none";
            } else {
                node.parentNode.removeChild(node);
            }
            return next
        }

        var view = display.view, lineN = display.viewFrom;
        // Loop over the elements in the view, syncing cur (the DOM nodes
        // in display.lineDiv) with the view as we go.
        for (var i = 0; i < view.length; i++) {
            var lineView = view[i];
            if (lineView.hidden) ; else if (!lineView.node || lineView.node.parentNode != container) { // Not drawn yet
                var node = buildLineElement(cm, lineView, lineN, dims);
                container.insertBefore(node, cur);
            } else { // Already drawn
                while (cur != lineView.node) {
                    cur = rm(cur);
                }
                var updateNumber = lineNumbers && updateNumbersFrom != null &&
                    updateNumbersFrom <= lineN && lineView.lineNumber;
                if (lineView.changes) {
                    if (indexOf(lineView.changes, "gutter") > -1) {
                        updateNumber = false;
                    }
                    updateLineForChanges(cm, lineView, lineN, dims);
                }
                if (updateNumber) {
                    removeChildren(lineView.lineNumber);
                    lineView.lineNumber.appendChild(document.createTextNode(lineNumberFor(cm.options, lineN)));
                }
                cur = lineView.node.nextSibling;
            }
            lineN += lineView.size;
        }
        while (cur) {
            cur = rm(cur);
        }
    }

    function updateGutterSpace(display) {
        var width = display.gutters.offsetWidth;
        display.sizer.style.marginLeft = width+"px";
        // Send an event to consumers responding to changes in gutter width.
        signalLater(display, "gutterChanged", display);
    }

    function setDocumentHeight(cm, measure) {
        cm.display.sizer.style.minHeight = measure.docHeight+"px";
        cm.display.heightForcer.style.top = measure.docHeight+"px";
        cm.display.gutters.style.height = (measure.docHeight+cm.display.barHeight+scrollGap(cm))+"px";
    }

    // Re-align line numbers and gutter marks to compensate for
    // horizontal scrolling.
    function alignHorizontally(cm) {
        var display = cm.display, view = display.view;
        if (!display.alignWidgets && (!display.gutters.firstChild || !cm.options.fixedGutter)) {
            return
        }
        var comp = compensateForHScroll(display)-display.scroller.scrollLeft+cm.doc.scrollLeft;
        var gutterW = display.gutters.offsetWidth, left = comp+"px";
        for (var i = 0; i < view.length; i++) {
            if (!view[i].hidden) {
                if (cm.options.fixedGutter) {
                    if (view[i].gutter) {
                        view[i].gutter.style.left = left;
                    }
                    if (view[i].gutterBackground) {
                        view[i].gutterBackground.style.left = left;
                    }
                }
                var align = view[i].alignable;
                if (align) {
                    for (var j = 0; j < align.length; j++) {
                        align[j].style.left = left;
                    }
                }
            }
        }
        if (cm.options.fixedGutter) {
            display.gutters.style.left = (comp+gutterW)+"px";
        }
    }

    // Used to ensure that the line number gutter is still the right
    // size for the current document size. Returns true when an update
    // is needed.
    function maybeUpdateLineNumberWidth(cm) {
        if (!cm.options.lineNumbers) {
            return false
        }
        var doc = cm.doc, last = lineNumberFor(cm.options, doc.first+doc.size-1), display = cm.display;
        if (last.length != display.lineNumChars) {
            var test = display.measure.appendChild(elt("div", [elt("div", last)],
                "CodeMirror-linenumber CodeMirror-gutter-elt"));
            var innerW = test.firstChild.offsetWidth, padding = test.offsetWidth-innerW;
            display.lineGutter.style.width = "";
            display.lineNumInnerWidth = Math.max(innerW, display.lineGutter.offsetWidth-padding)+1;
            display.lineNumWidth = display.lineNumInnerWidth+padding;
            display.lineNumChars = display.lineNumInnerWidth ? last.length : -1;
            display.lineGutter.style.width = display.lineNumWidth+"px";
            updateGutterSpace(cm.display);
            return true
        }
        return false
    }

    function getGutters(gutters, lineNumbers) {
        var result = [], sawLineNumbers = false;
        for (var i = 0; i < gutters.length; i++) {
            var name = gutters[i], style = null;
            if (typeof name != "string") {
                style = name.style;
                name = name.className;
            }
            if (name == "CodeMirror-linenumbers") {
                if (!lineNumbers) {
                    continue
                } else {
                    sawLineNumbers = true;
                }
            }
            result.push({className: name, style: style});
        }
        if (lineNumbers && !sawLineNumbers) {
            result.push({className: "CodeMirror-linenumbers", style: null});
        }
        return result
    }

    // Rebuild the gutter elements, ensure the margin to the left of the
    // code matches their width.
    function renderGutters(display) {
        var gutters = display.gutters, specs = display.gutterSpecs;
        removeChildren(gutters);
        display.lineGutter = null;
        for (var i = 0; i < specs.length; ++i) {
            var ref = specs[i];
            var className = ref.className;
            var style = ref.style;
            var gElt = gutters.appendChild(elt("div", null, "CodeMirror-gutter "+className));
            if (style) {
                gElt.style.cssText = style;
            }
            if (className == "CodeMirror-linenumbers") {
                display.lineGutter = gElt;
                gElt.style.width = (display.lineNumWidth || 1)+"px";
            }
        }
        gutters.style.display = specs.length ? "" : "none";
        updateGutterSpace(display);
    }

    function updateGutters(cm) {
        renderGutters(cm.display);
        regChange(cm);
        alignHorizontally(cm);
    }

    // The display handles the DOM integration, both for input reading
    // and content drawing. It holds references to DOM nodes and
    // display-related state.

    function Display(place, doc, input, options) {
        var d = this;
        this.input = input;

        // Covers bottom-right square when both scrollbars are present.
        d.scrollbarFiller = elt("div", null, "CodeMirror-scrollbar-filler");
        d.scrollbarFiller.setAttribute("cm-not-content", "true");
        // Covers bottom of gutter when coverGutterNextToScrollbar is on
        // and h scrollbar is present.
        d.gutterFiller = elt("div", null, "CodeMirror-gutter-filler");
        d.gutterFiller.setAttribute("cm-not-content", "true");
        // Will contain the actual code, positioned to cover the viewport.
        d.lineDiv = eltP("div", null, "CodeMirror-code");
        // Elements are added to these to represent selection and cursors.
        d.selectionDiv = elt("div", null, null, "position: relative; z-index: 1");
        d.cursorDiv = elt("div", null, "CodeMirror-cursors");
        // A visibility: hidden element used to find the size of things.
        d.measure = elt("div", null, "CodeMirror-measure");
        // When lines outside of the viewport are measured, they are drawn in this.
        d.lineMeasure = elt("div", null, "CodeMirror-measure");
        // Wraps everything that needs to exist inside the vertically-padded coordinate system
        d.lineSpace = eltP("div", [d.measure, d.lineMeasure, d.selectionDiv, d.cursorDiv, d.lineDiv],
            null, "position: relative; outline: none");
        var lines = eltP("div", [d.lineSpace], "CodeMirror-lines");
        // Moved around its parent to cover visible view.
        d.mover = elt("div", [lines], null, "position: relative");
        // Set to the height of the document, allowing scrolling.
        d.sizer = elt("div", [d.mover], "CodeMirror-sizer");
        d.sizerWidth = null;
        // Behavior of elts with overflow: auto and padding is
        // inconsistent across browsers. This is used to ensure the
        // scrollable area is big enough.
        d.heightForcer = elt("div", null, null, "position: absolute; height: "+scrollerGap+"px; width: 1px;");
        // Will contain the gutters, if any.
        d.gutters = elt("div", null, "CodeMirror-gutters");
        d.lineGutter = null;
        // Actual scrollable element.
        d.scroller = elt("div", [d.sizer, d.heightForcer, d.gutters], "CodeMirror-scroll");
        d.scroller.setAttribute("tabIndex", "-1");
        // The element in which the editor lives.
        d.wrapper = elt("div", [d.scrollbarFiller, d.gutterFiller, d.scroller], "CodeMirror");

        // This attribute is respected by automatic translation systems such as Google Translate,
        // and may also be respected by tools used by human translators.
        d.wrapper.setAttribute('translate', 'no');

        // Work around IE7 z-index bug (not perfect, hence IE7 not really being supported)
        if (ie && ie_version < 8) {
            d.gutters.style.zIndex = -1;
            d.scroller.style.paddingRight = 0;
        }
        if (!webkit && !(gecko && mobile)) {
            d.scroller.draggable = true;
        }

        if (place) {
            if (place.appendChild) {
                place.appendChild(d.wrapper);
            } else {
                place(d.wrapper);
            }
        }

        // Current rendered range (may be bigger than the view window).
        d.viewFrom = d.viewTo = doc.first;
        d.reportedViewFrom = d.reportedViewTo = doc.first;
        // Information about the rendered lines.
        d.view = [];
        d.renderedView = null;
        // Holds info about a single rendered line when it was rendered
        // for measurement, while not in view.
        d.externalMeasured = null;
        // Empty space (in pixels) above the view
        d.viewOffset = 0;
        d.lastWrapHeight = d.lastWrapWidth = 0;
        d.updateLineNumbers = null;

        d.nativeBarWidth = d.barHeight = d.barWidth = 0;
        d.scrollbarsClipped = false;

        // Used to only resize the line number gutter when necessary (when
        // the amount of lines crosses a boundary that makes its width change)
        d.lineNumWidth = d.lineNumInnerWidth = d.lineNumChars = null;
        // Set to true when a non-horizontal-scrolling line widget is
        // added. As an optimization, line widget aligning is skipped when
        // this is false.
        d.alignWidgets = false;

        d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null;

        // Tracks the maximum line length so that the horizontal scrollbar
        // can be kept static when scrolling.
        d.maxLine = null;
        d.maxLineLength = 0;
        d.maxLineChanged = false;

        // Used for measuring wheel scrolling granularity
        d.wheelDX = d.wheelDY = d.wheelStartX = d.wheelStartY = null;

        // True when shift is held down.
        d.shift = false;

        // Used to track whether anything happened since the context menu
        // was opened.
        d.selForContextMenu = null;

        d.activeTouch = null;

        d.gutterSpecs = getGutters(options.gutters, options.lineNumbers);
        renderGutters(d);

        input.init(d);
    }

    // Since the delta values reported on mouse wheel events are
    // unstandardized between browsers and even browser versions, and
    // generally horribly unpredictable, this code starts by measuring
    // the scroll effect that the first few mouse wheel events have,
    // and, from that, detects the way it can convert deltas to pixel
    // offsets afterwards.
    //
    // The reason we want to know the amount a wheel event will scroll
    // is that it gives us a chance to update the display before the
    // actual scrolling happens, reducing flickering.

    var wheelSamples = 0, wheelPixelsPerUnit = null;
    // Fill in a browser-detected starting value on browsers where we
    // know one. These don't have to be accurate -- the result of them
    // being wrong would just be a slight flicker on the first wheel
    // scroll (if it is large enough).
    if (ie) {
        wheelPixelsPerUnit = -.53;
    } else if (gecko) {
        wheelPixelsPerUnit = 15;
    } else if (chrome) {
        wheelPixelsPerUnit = -.7;
    } else if (safari) {
        wheelPixelsPerUnit = -1 / 3;
    }

    function wheelEventDelta(e) {
        var dx = e.wheelDeltaX, dy = e.wheelDeltaY;
        if (dx == null && e.detail && e.axis == e.HORIZONTAL_AXIS) {
            dx = e.detail;
        }
        if (dy == null && e.detail && e.axis == e.VERTICAL_AXIS) {
            dy = e.detail;
        } else if (dy == null) {
            dy = e.wheelDelta;
        }
        return {x: dx, y: dy}
    }

    function wheelEventPixels(e) {
        var delta = wheelEventDelta(e);
        delta.x *= wheelPixelsPerUnit;
        delta.y *= wheelPixelsPerUnit;
        return delta
    }

    function onScrollWheel(cm, e) {
        var delta = wheelEventDelta(e), dx = delta.x, dy = delta.y;
        var pixelsPerUnit = wheelPixelsPerUnit;
        if (e.deltaMode === 0) {
            dx = e.deltaX;
            dy = e.deltaY;
            pixelsPerUnit = 1;
        }

        var display = cm.display, scroll = display.scroller;
        // Quit if there's nothing to scroll here
        var canScrollX = scroll.scrollWidth > scroll.clientWidth;
        var canScrollY = scroll.scrollHeight > scroll.clientHeight;
        if (!(dx && canScrollX || dy && canScrollY)) {
            return
        }

        // Webkit browsers on OS X abort momentum scrolls when the target
        // of the scroll event is removed from the scrollable element.
        // This hack (see related code in patchDisplay) makes sure the
        // element is kept around.
        if (dy && mac && webkit) {
            outer: for (var cur = e.target, view = display.view; cur != scroll; cur = cur.parentNode) {
                for (var i = 0; i < view.length; i++) {
                    if (view[i].node == cur) {
                        cm.display.currentWheelTarget = cur;
                        break outer
                    }
                }
            }
        }

        // On some browsers, horizontal scrolling will cause redraws to
        // happen before the gutter has been realigned, causing it to
        // wriggle around in a most unseemly way. When we have an
        // estimated pixels/delta value, we just handle horizontal
        // scrolling entirely here. It'll be slightly off from native, but
        // better than glitching out.
        if (dx && !gecko && !presto && pixelsPerUnit != null) {
            if (dy && canScrollY) {
                updateScrollTop(cm, Math.max(0, scroll.scrollTop+dy * pixelsPerUnit));
            }
            setScrollLeft(cm, Math.max(0, scroll.scrollLeft+dx * pixelsPerUnit));
            // Only prevent default scrolling if vertical scrolling is
            // actually possible. Otherwise, it causes vertical scroll
            // jitter on OSX trackpads when deltaX is small and deltaY
            // is large (issue #3579)
            if (!dy || (dy && canScrollY)) {
                e_preventDefault(e);
            }
            display.wheelStartX = null; // Abort measurement, if in progress
            return
        }

        // 'Project' the visible viewport to cover the area that is being
        // scrolled into view (if we know enough to estimate it).
        if (dy && pixelsPerUnit != null) {
            var pixels = dy * pixelsPerUnit;
            var top = cm.doc.scrollTop, bot = top+display.wrapper.clientHeight;
            if (pixels < 0) {
                top = Math.max(0, top+pixels-50);
            } else {
                bot = Math.min(cm.doc.height, bot+pixels+50);
            }
            updateDisplaySimple(cm, {top: top, bottom: bot});
        }

        if (wheelSamples < 20 && e.deltaMode !== 0) {
            if (display.wheelStartX == null) {
                display.wheelStartX = scroll.scrollLeft;
                display.wheelStartY = scroll.scrollTop;
                display.wheelDX = dx;
                display.wheelDY = dy;
                setTimeout(function () {
                    if (display.wheelStartX == null) {
                        return
                    }
                    var movedX = scroll.scrollLeft-display.wheelStartX;
                    var movedY = scroll.scrollTop-display.wheelStartY;
                    var sample = (movedY && display.wheelDY && movedY / display.wheelDY) ||
                        (movedX && display.wheelDX && movedX / display.wheelDX);
                    display.wheelStartX = display.wheelStartY = null;
                    if (!sample) {
                        return
                    }
                    wheelPixelsPerUnit = (wheelPixelsPerUnit * wheelSamples+sample) / (wheelSamples+1);
                    ++wheelSamples;
                }, 200);
            } else {
                display.wheelDX += dx;
                display.wheelDY += dy;
            }
        }
    }

    // Selection objects are immutable. A new one is created every time
    // the selection changes. A selection is one or more non-overlapping
    // (and non-touching) ranges, sorted, and an integer that indicates
    // which one is the primary selection (the one that's scrolled into
    // view, that getCursor returns, etc).
    var Selection = function (ranges, primIndex) {
        this.ranges = ranges;
        this.primIndex = primIndex;
    };

    Selection.prototype.primary = function () {
        return this.ranges[this.primIndex]
    };

    Selection.prototype.equals = function (other) {
        if (other == this) {
            return true
        }
        if (other.primIndex != this.primIndex || other.ranges.length != this.ranges.length) {
            return false
        }
        for (var i = 0; i < this.ranges.length; i++) {
            var here = this.ranges[i], there = other.ranges[i];
            if (!equalCursorPos(here.anchor, there.anchor) || !equalCursorPos(here.head, there.head)) {
                return false
            }
        }
        return true
    };

    Selection.prototype.deepCopy = function () {
        var out = [];
        for (var i = 0; i < this.ranges.length; i++) {
            out[i] = new Range(copyPos(this.ranges[i].anchor), copyPos(this.ranges[i].head));
        }
        return new Selection(out, this.primIndex)
    };

    Selection.prototype.somethingSelected = function () {
        for (var i = 0; i < this.ranges.length; i++) {
            if (!this.ranges[i].empty()) {
                return true
            }
        }
        return false
    };

    Selection.prototype.contains = function (pos, end) {
        if (!end) {
            end = pos;
        }
        for (var i = 0; i < this.ranges.length; i++) {
            var range = this.ranges[i];
            if (cmp(end, range.from()) >= 0 && cmp(pos, range.to()) <= 0) {
                return i
            }
        }
        return -1
    };

    var Range = function (anchor, head) {
        this.anchor = anchor;
        this.head = head;
    };

    Range.prototype.from = function () {
        return minPos(this.anchor, this.head)
    };
    Range.prototype.to = function () {
        return maxPos(this.anchor, this.head)
    };
    Range.prototype.empty = function () {
        return this.head.line == this.anchor.line && this.head.ch == this.anchor.ch
    };

    // Take an unsorted, potentially overlapping set of ranges, and
    // build a selection out of it. 'Consumes' ranges array (modifying
    // it).
    function normalizeSelection(cm, ranges, primIndex) {
        var mayTouch = cm && cm.options.selectionsMayTouch;
        var prim = ranges[primIndex];
        ranges.sort(function (a, b) {
            return cmp(a.from(), b.from());
        });
        primIndex = indexOf(ranges, prim);
        for (var i = 1; i < ranges.length; i++) {
            var cur = ranges[i], prev = ranges[i-1];
            var diff = cmp(prev.to(), cur.from());
            if (mayTouch && !cur.empty() ? diff > 0 : diff >= 0) {
                var from = minPos(prev.from(), cur.from()), to = maxPos(prev.to(), cur.to());
                var inv = prev.empty() ? cur.from() == cur.head : prev.from() == prev.head;
                if (i <= primIndex) {
                    --primIndex;
                }
                ranges.splice(--i, 2, new Range(inv ? to : from, inv ? from : to));
            }
        }
        return new Selection(ranges, primIndex)
    }

    function simpleSelection(anchor, head) {
        return new Selection([new Range(anchor, head || anchor)], 0)
    }

    // Compute the position of the end of a change (its 'to' property
    // refers to the pre-change end).
    function changeEnd(change) {
        if (!change.text) {
            return change.to
        }
        return Pos(change.from.line+change.text.length-1,
            lst(change.text).length+(change.text.length == 1 ? change.from.ch : 0))
    }

    // Adjust a position to refer to the post-change position of the
    // same text, or the end of the change if the change covers it.
    function adjustForChange(pos, change) {
        if (cmp(pos, change.from) < 0) {
            return pos
        }
        if (cmp(pos, change.to) <= 0) {
            return changeEnd(change)
        }

        var line = pos.line+change.text.length-(change.to.line-change.from.line)-1, ch = pos.ch;
        if (pos.line == change.to.line) {
            ch += changeEnd(change).ch-change.to.ch;
        }
        return Pos(line, ch)
    }

    function computeSelAfterChange(doc, change) {
        var out = [];
        for (var i = 0; i < doc.sel.ranges.length; i++) {
            var range = doc.sel.ranges[i];
            out.push(new Range(adjustForChange(range.anchor, change),
                adjustForChange(range.head, change)));
        }
        return normalizeSelection(doc.cm, out, doc.sel.primIndex)
    }

    function offsetPos(pos, old, nw) {
        if (pos.line == old.line) {
            return Pos(nw.line, pos.ch-old.ch+nw.ch)
        } else {
            return Pos(nw.line+(pos.line-old.line), pos.ch)
        }
    }

    // Used by replaceSelections to allow moving the selection to the
    // start or around the replaced test. Hint may be "start" or "around".
    function computeReplacedSel(doc, changes, hint) {
        var out = [];
        var oldPrev = Pos(doc.first, 0), newPrev = oldPrev;
        for (var i = 0; i < changes.length; i++) {
            var change = changes[i];
            var from = offsetPos(change.from, oldPrev, newPrev);
            var to = offsetPos(changeEnd(change), oldPrev, newPrev);
            oldPrev = change.to;
            newPrev = to;
            if (hint == "around") {
                var range = doc.sel.ranges[i], inv = cmp(range.head, range.anchor) < 0;
                out[i] = new Range(inv ? to : from, inv ? from : to);
            } else {
                out[i] = new Range(from, from);
            }
        }
        return new Selection(out, doc.sel.primIndex)
    }

    // Used to get the editor into a consistent state again when options change.

    function loadMode(cm) {
        cm.doc.mode = getMode(cm.options, cm.doc.modeOption);
        resetModeState(cm);
    }

    function resetModeState(cm) {
        cm.doc.iter(function (line) {
            if (line.stateAfter) {
                line.stateAfter = null;
            }
            if (line.styles) {
                line.styles = null;
            }
        });
        cm.doc.modeFrontier = cm.doc.highlightFrontier = cm.doc.first;
        startWorker(cm, 100);
        cm.state.modeGen++;
        if (cm.curOp) {
            regChange(cm);
        }
    }

    // DOCUMENT DATA STRUCTURE

    // By default, updates that start and end at the beginning of a line
    // are treated specially, in order to make the association of line
    // widgets and marker elements with the text behave more intuitive.
    function isWholeLineUpdate(doc, change) {
        return change.from.ch == 0 && change.to.ch == 0 && lst(change.text) == "" &&
            (!doc.cm || doc.cm.options.wholeLineUpdateBefore)
    }

    // Perform a change on the document data structure.
    function updateDoc(doc, change, markedSpans, estimateHeight) {
        function spansFor(n) {
            return markedSpans ? markedSpans[n] : null
        }

        function update(line, text, spans) {
            updateLine(line, text, spans, estimateHeight);
            signalLater(line, "change", line, change);
        }

        function linesFor(start, end) {
            var result = [];
            for (var i = start; i < end; ++i) {
                result.push(new Line(text[i], spansFor(i), estimateHeight));
            }
            return result
        }

        var from = change.from, to = change.to, text = change.text;
        var firstLine = getLine(doc, from.line), lastLine = getLine(doc, to.line);
        var lastText = lst(text), lastSpans = spansFor(text.length-1), nlines = to.line-from.line;

        // Adjust the line structure
        if (change.full) {
            doc.insert(0, linesFor(0, text.length));
            doc.remove(text.length, doc.size-text.length);
        } else if (isWholeLineUpdate(doc, change)) {
            // This is a whole-line replace. Treated specially to make
            // sure line objects move the way they are supposed to.
            var added = linesFor(0, text.length-1);
            update(lastLine, lastLine.text, lastSpans);
            if (nlines) {
                doc.remove(from.line, nlines);
            }
            if (added.length) {
                doc.insert(from.line, added);
            }
        } else if (firstLine == lastLine) {
            if (text.length == 1) {
                update(firstLine, firstLine.text.slice(0, from.ch)+lastText+firstLine.text.slice(to.ch), lastSpans);
            } else {
                var added$1 = linesFor(1, text.length-1);
                added$1.push(new Line(lastText+firstLine.text.slice(to.ch), lastSpans, estimateHeight));
                update(firstLine, firstLine.text.slice(0, from.ch)+text[0], spansFor(0));
                doc.insert(from.line+1, added$1);
            }
        } else if (text.length == 1) {
            update(firstLine, firstLine.text.slice(0, from.ch)+text[0]+lastLine.text.slice(to.ch), spansFor(0));
            doc.remove(from.line+1, nlines);
        } else {
            update(firstLine, firstLine.text.slice(0, from.ch)+text[0], spansFor(0));
            update(lastLine, lastText+lastLine.text.slice(to.ch), lastSpans);
            var added$2 = linesFor(1, text.length-1);
            if (nlines > 1) {
                doc.remove(from.line+1, nlines-1);
            }
            doc.insert(from.line+1, added$2);
        }

        signalLater(doc, "change", doc, change);
    }

    // Call f for all linked documents.
    function linkedDocs(doc, f, sharedHistOnly) {
        function propagate(doc, skip, sharedHist) {
            if (doc.linked) {
                for (var i = 0; i < doc.linked.length; ++i) {
                    var rel = doc.linked[i];
                    if (rel.doc == skip) {
                        continue
                    }
                    var shared = sharedHist && rel.sharedHist;
                    if (sharedHistOnly && !shared) {
                        continue
                    }
                    f(rel.doc, shared);
                    propagate(rel.doc, doc, shared);
                }
            }
        }

        propagate(doc, null, true);
    }

    // Attach a document to an editor.
    function attachDoc(cm, doc) {
        if (doc.cm) {
            throw new Error("This document is already in use.")
        }
        cm.doc = doc;
        doc.cm = cm;
        estimateLineHeights(cm);
        loadMode(cm);
        setDirectionClass(cm);
        cm.options.direction = doc.direction;
        if (!cm.options.lineWrapping) {
            findMaxLine(cm);
        }
        cm.options.mode = doc.modeOption;
        regChange(cm);
    }

    function setDirectionClass(cm) {
        (cm.doc.direction == "rtl" ? addClass : rmClass)(cm.display.lineDiv, "CodeMirror-rtl");
    }

    function directionChanged(cm) {
        runInOp(cm, function () {
            setDirectionClass(cm);
            regChange(cm);
        });
    }

    function History(prev) {
        // Arrays of change events and selections. Doing something adds an
        // event to done and clears undo. Undoing moves events from done
        // to undone, redoing moves them in the other direction.
        this.done = [];
        this.undone = [];
        this.undoDepth = prev ? prev.undoDepth : Infinity;
        // Used to track when changes can be merged into a single undo
        // event
        this.lastModTime = this.lastSelTime = 0;
        this.lastOp = this.lastSelOp = null;
        this.lastOrigin = this.lastSelOrigin = null;
        // Used by the isClean() method
        this.generation = this.maxGeneration = prev ? prev.maxGeneration : 1;
    }

    // Create a history change event from an updateDoc-style change
    // object.
    function historyChangeFromChange(doc, change) {
        var histChange = {
            from: copyPos(change.from),
            to: changeEnd(change),
            text: getBetween(doc, change.from, change.to)
        };
        attachLocalSpans(doc, histChange, change.from.line, change.to.line+1);
        linkedDocs(doc, function (doc) {
            return attachLocalSpans(doc, histChange, change.from.line, change.to.line+1);
        }, true);
        return histChange
    }

    // Pop all selection events off the end of a history array. Stop at
    // a change event.
    function clearSelectionEvents(array) {
        while (array.length) {
            var last = lst(array);
            if (last.ranges) {
                array.pop();
            } else {
                break
            }
        }
    }

    // Find the top change event in the history. Pop off selection
    // events that are in the way.
    function lastChangeEvent(hist, force) {
        if (force) {
            clearSelectionEvents(hist.done);
            return lst(hist.done)
        } else if (hist.done.length && !lst(hist.done).ranges) {
            return lst(hist.done)
        } else if (hist.done.length > 1 && !hist.done[hist.done.length-2].ranges) {
            hist.done.pop();
            return lst(hist.done)
        }
    }

    // Register a change in the history. Merges changes that are within
    // a single operation, or are close together with an origin that
    // allows merging (starting with "+") into a single event.
    function addChangeToHistory(doc, change, selAfter, opId) {
        var hist = doc.history;
        hist.undone.length = 0;
        var time = +new Date, cur;
        var last;

        if ((hist.lastOp == opId ||
            hist.lastOrigin == change.origin && change.origin &&
            ((change.origin.charAt(0) == "+" && hist.lastModTime > time-(doc.cm ? doc.cm.options.historyEventDelay : 500)) ||
                change.origin.charAt(0) == "*")) &&
            (cur = lastChangeEvent(hist, hist.lastOp == opId))) {
            // Merge this change into the last event
            last = lst(cur.changes);
            if (cmp(change.from, change.to) == 0 && cmp(change.from, last.to) == 0) {
                // Optimized case for simple insertion -- don't want to add
                // new changesets for every character typed
                last.to = changeEnd(change);
            } else {
                // Add new sub-event
                cur.changes.push(historyChangeFromChange(doc, change));
            }
        } else {
            // Can not be merged, start a new event.
            var before = lst(hist.done);
            if (!before || !before.ranges) {
                pushSelectionToHistory(doc.sel, hist.done);
            }
            cur = {
                changes: [historyChangeFromChange(doc, change)],
                generation: hist.generation
            };
            hist.done.push(cur);
            while (hist.done.length > hist.undoDepth) {
                hist.done.shift();
                if (!hist.done[0].ranges) {
                    hist.done.shift();
                }
            }
        }
        hist.done.push(selAfter);
        hist.generation = ++hist.maxGeneration;
        hist.lastModTime = hist.lastSelTime = time;
        hist.lastOp = hist.lastSelOp = opId;
        hist.lastOrigin = hist.lastSelOrigin = change.origin;

        if (!last) {
            signal(doc, "historyAdded");
        }
    }

    function selectionEventCanBeMerged(doc, origin, prev, sel) {
        var ch = origin.charAt(0);
        return ch == "*" ||
            ch == "+" &&
            prev.ranges.length == sel.ranges.length &&
            prev.somethingSelected() == sel.somethingSelected() &&
            new Date-doc.history.lastSelTime <= (doc.cm ? doc.cm.options.historyEventDelay : 500)
    }

    // Called whenever the selection changes, sets the new selection as
    // the pending selection in the history, and pushes the old pending
    // selection into the 'done' array when it was significantly
    // different (in number of selected ranges, emptiness, or time).
    function addSelectionToHistory(doc, sel, opId, options) {
        var hist = doc.history, origin = options && options.origin;

        // A new event is started when the previous origin does not match
        // the current, or the origins don't allow matching. Origins
        // starting with * are always merged, those starting with + are
        // merged when similar and close together in time.
        if (opId == hist.lastSelOp ||
            (origin && hist.lastSelOrigin == origin &&
                (hist.lastModTime == hist.lastSelTime && hist.lastOrigin == origin ||
                    selectionEventCanBeMerged(doc, origin, lst(hist.done), sel)))) {
            hist.done[hist.done.length-1] = sel;
        } else {
            pushSelectionToHistory(sel, hist.done);
        }

        hist.lastSelTime = +new Date;
        hist.lastSelOrigin = origin;
        hist.lastSelOp = opId;
        if (options && options.clearRedo !== false) {
            clearSelectionEvents(hist.undone);
        }
    }

    function pushSelectionToHistory(sel, dest) {
        var top = lst(dest);
        if (!(top && top.ranges && top.equals(sel))) {
            dest.push(sel);
        }
    }

    // Used to store marked span information in the history.
    function attachLocalSpans(doc, change, from, to) {
        var existing = change["spans_"+doc.id], n = 0;
        doc.iter(Math.max(doc.first, from), Math.min(doc.first+doc.size, to), function (line) {
            if (line.markedSpans) {
                (existing || (existing = change["spans_"+doc.id] = {}))[n] = line.markedSpans;
            }
            ++n;
        });
    }

    // When un/re-doing restores text containing marked spans, those
    // that have been explicitly cleared should not be restored.
    function removeClearedSpans(spans) {
        if (!spans) {
            return null
        }
        var out;
        for (var i = 0; i < spans.length; ++i) {
            if (spans[i].marker.explicitlyCleared) {
                if (!out) {
                    out = spans.slice(0, i);
                }
            } else if (out) {
                out.push(spans[i]);
            }
        }
        return !out ? spans : out.length ? out : null
    }

    // Retrieve and filter the old marked spans stored in a change event.
    function getOldSpans(doc, change) {
        var found = change["spans_"+doc.id];
        if (!found) {
            return null
        }
        var nw = [];
        for (var i = 0; i < change.text.length; ++i) {
            nw.push(removeClearedSpans(found[i]));
        }
        return nw
    }

    // Used for un/re-doing changes from the history. Combines the
    // result of computing the existing spans with the set of spans that
    // existed in the history (so that deleting around a span and then
    // undoing brings back the span).
    function mergeOldSpans(doc, change) {
        var old = getOldSpans(doc, change);
        var stretched = stretchSpansOverChange(doc, change);
        if (!old) {
            return stretched
        }
        if (!stretched) {
            return old
        }

        for (var i = 0; i < old.length; ++i) {
            var oldCur = old[i], stretchCur = stretched[i];
            if (oldCur && stretchCur) {
                spans: for (var j = 0; j < stretchCur.length; ++j) {
                    var span = stretchCur[j];
                    for (var k = 0; k < oldCur.length; ++k) {
                        if (oldCur[k].marker == span.marker) {
                            continue spans
                        }
                    }
                    oldCur.push(span);
                }
            } else if (stretchCur) {
                old[i] = stretchCur;
            }
        }
        return old
    }

    // Used both to provide a JSON-safe object in .getHistory, and, when
    // detaching a document, to split the history in two
    function copyHistoryArray(events, newGroup, instantiateSel) {
        var copy = [];
        for (var i = 0; i < events.length; ++i) {
            var event = events[i];
            if (event.ranges) {
                copy.push(instantiateSel ? Selection.prototype.deepCopy.call(event) : event);
                continue
            }
            var changes = event.changes, newChanges = [];
            copy.push({changes: newChanges});
            for (var j = 0; j < changes.length; ++j) {
                var change = changes[j], m = (void 0);
                newChanges.push({from: change.from, to: change.to, text: change.text});
                if (newGroup) {
                    for (var prop in change) {
                        if (m = prop.match(/^spans_(\d+)$/)) {
                            if (indexOf(newGroup, Number(m[1])) > -1) {
                                lst(newChanges)[prop] = change[prop];
                                delete change[prop];
                            }
                        }
                    }
                }
            }
        }
        return copy
    }

    // The 'scroll' parameter given to many of these indicated whether
    // the new cursor position should be scrolled into view after
    // modifying the selection.

    // If shift is held or the extend flag is set, extends a range to
    // include a given position (and optionally a second position).
    // Otherwise, simply returns the range between the given positions.
    // Used for cursor motion and such.
    function extendRange(range, head, other, extend) {
        if (extend) {
            var anchor = range.anchor;
            if (other) {
                var posBefore = cmp(head, anchor) < 0;
                if (posBefore != (cmp(other, anchor) < 0)) {
                    anchor = head;
                    head = other;
                } else if (posBefore != (cmp(head, other) < 0)) {
                    head = other;
                }
            }
            return new Range(anchor, head)
        } else {
            return new Range(other || head, head)
        }
    }

    // Extend the primary selection range, discard the rest.
    function extendSelection(doc, head, other, options, extend) {
        if (extend == null) {
            extend = doc.cm && (doc.cm.display.shift || doc.extend);
        }
        setSelection(doc, new Selection([extendRange(doc.sel.primary(), head, other, extend)], 0), options);
    }

    // Extend all selections (pos is an array of selections with length
    // equal the number of selections)
    function extendSelections(doc, heads, options) {
        var out = [];
        var extend = doc.cm && (doc.cm.display.shift || doc.extend);
        for (var i = 0; i < doc.sel.ranges.length; i++) {
            out[i] = extendRange(doc.sel.ranges[i], heads[i], null, extend);
        }
        var newSel = normalizeSelection(doc.cm, out, doc.sel.primIndex);
        setSelection(doc, newSel, options);
    }

    // Updates a single range in the selection.
    function replaceOneSelection(doc, i, range, options) {
        var ranges = doc.sel.ranges.slice(0);
        ranges[i] = range;
        setSelection(doc, normalizeSelection(doc.cm, ranges, doc.sel.primIndex), options);
    }

    // Reset the selection to a single range.
    function setSimpleSelection(doc, anchor, head, options) {
        setSelection(doc, simpleSelection(anchor, head), options);
    }

    // Give beforeSelectionChange handlers a change to influence a
    // selection update.
    function filterSelectionChange(doc, sel, options) {
        var obj = {
            ranges: sel.ranges,
            update: function (ranges) {
                this.ranges = [];
                for (var i = 0; i < ranges.length; i++) {
                    this.ranges[i] = new Range(clipPos(doc, ranges[i].anchor),
                        clipPos(doc, ranges[i].head));
                }
            },
            origin: options && options.origin
        };
        signal(doc, "beforeSelectionChange", doc, obj);
        if (doc.cm) {
            signal(doc.cm, "beforeSelectionChange", doc.cm, obj);
        }
        if (obj.ranges != sel.ranges) {
            return normalizeSelection(doc.cm, obj.ranges, obj.ranges.length-1)
        } else {
            return sel
        }
    }

    function setSelectionReplaceHistory(doc, sel, options) {
        var done = doc.history.done, last = lst(done);
        if (last && last.ranges) {
            done[done.length-1] = sel;
            setSelectionNoUndo(doc, sel, options);
        } else {
            setSelection(doc, sel, options);
        }
    }

    // Set a new selection.
    function setSelection(doc, sel, options) {
        setSelectionNoUndo(doc, sel, options);
        addSelectionToHistory(doc, doc.sel, doc.cm ? doc.cm.curOp.id : NaN, options);
    }

    function setSelectionNoUndo(doc, sel, options) {
        if (hasHandler(doc, "beforeSelectionChange") || doc.cm && hasHandler(doc.cm, "beforeSelectionChange")) {
            sel = filterSelectionChange(doc, sel, options);
        }

        var bias = options && options.bias ||
            (cmp(sel.primary().head, doc.sel.primary().head) < 0 ? -1 : 1);
        setSelectionInner(doc, skipAtomicInSelection(doc, sel, bias, true));

        if (!(options && options.scroll === false) && doc.cm && doc.cm.getOption("readOnly") != "nocursor") {
            ensureCursorVisible(doc.cm);
        }
    }

    function setSelectionInner(doc, sel) {
        if (sel.equals(doc.sel)) {
            return
        }

        doc.sel = sel;

        if (doc.cm) {
            doc.cm.curOp.updateInput = 1;
            doc.cm.curOp.selectionChanged = true;
            signalCursorActivity(doc.cm);
        }
        signalLater(doc, "cursorActivity", doc);
    }

    // Verify that the selection does not partially select any atomic
    // marked ranges.
    function reCheckSelection(doc) {
        setSelectionInner(doc, skipAtomicInSelection(doc, doc.sel, null, false));
    }

    // Return a selection that does not partially select any atomic
    // ranges.
    function skipAtomicInSelection(doc, sel, bias, mayClear) {
        var out;
        for (var i = 0; i < sel.ranges.length; i++) {
            var range = sel.ranges[i];
            var old = sel.ranges.length == doc.sel.ranges.length && doc.sel.ranges[i];
            var newAnchor = skipAtomic(doc, range.anchor, old && old.anchor, bias, mayClear);
            var newHead = skipAtomic(doc, range.head, old && old.head, bias, mayClear);
            if (out || newAnchor != range.anchor || newHead != range.head) {
                if (!out) {
                    out = sel.ranges.slice(0, i);
                }
                out[i] = new Range(newAnchor, newHead);
            }
        }
        return out ? normalizeSelection(doc.cm, out, sel.primIndex) : sel
    }

    function skipAtomicInner(doc, pos, oldPos, dir, mayClear) {
        var line = getLine(doc, pos.line);
        if (line.markedSpans) {
            for (var i = 0; i < line.markedSpans.length; ++i) {
                var sp = line.markedSpans[i], m = sp.marker;

                // Determine if we should prevent the cursor being placed to the left/right of an atomic marker
                // Historically this was determined using the inclusiveLeft/Right option, but the new way to control it
                // is with selectLeft/Right
                var preventCursorLeft = ("selectLeft" in m) ? !m.selectLeft : m.inclusiveLeft;
                var preventCursorRight = ("selectRight" in m) ? !m.selectRight : m.inclusiveRight;

                if ((sp.from == null || (preventCursorLeft ? sp.from <= pos.ch : sp.from < pos.ch)) &&
                    (sp.to == null || (preventCursorRight ? sp.to >= pos.ch : sp.to > pos.ch))) {
                    if (mayClear) {
                        signal(m, "beforeCursorEnter");
                        if (m.explicitlyCleared) {
                            if (!line.markedSpans) {
                                break
                            } else {
                                --i;
                                continue
                            }
                        }
                    }
                    if (!m.atomic) {
                        continue
                    }

                    if (oldPos) {
                        var near = m.find(dir < 0 ? 1 : -1), diff = (void 0);
                        if (dir < 0 ? preventCursorRight : preventCursorLeft) {
                            near = movePos(doc, near, -dir, near && near.line == pos.line ? line : null);
                        }
                        if (near && near.line == pos.line && (diff = cmp(near, oldPos)) && (dir < 0 ? diff < 0 : diff > 0)) {
                            return skipAtomicInner(doc, near, pos, dir, mayClear)
                        }
                    }

                    var far = m.find(dir < 0 ? -1 : 1);
                    if (dir < 0 ? preventCursorLeft : preventCursorRight) {
                        far = movePos(doc, far, dir, far.line == pos.line ? line : null);
                    }
                    return far ? skipAtomicInner(doc, far, pos, dir, mayClear) : null
                }
            }
        }
        return pos
    }

    // Ensure a given position is not inside an atomic range.
    function skipAtomic(doc, pos, oldPos, bias, mayClear) {
        var dir = bias || 1;
        var found = skipAtomicInner(doc, pos, oldPos, dir, mayClear) ||
            (!mayClear && skipAtomicInner(doc, pos, oldPos, dir, true)) ||
            skipAtomicInner(doc, pos, oldPos, -dir, mayClear) ||
            (!mayClear && skipAtomicInner(doc, pos, oldPos, -dir, true));
        if (!found) {
            doc.cantEdit = true;
            return Pos(doc.first, 0)
        }
        return found
    }

    function movePos(doc, pos, dir, line) {
        if (dir < 0 && pos.ch == 0) {
            if (pos.line > doc.first) {
                return clipPos(doc, Pos(pos.line-1))
            } else {
                return null
            }
        } else if (dir > 0 && pos.ch == (line || getLine(doc, pos.line)).text.length) {
            if (pos.line < doc.first+doc.size-1) {
                return Pos(pos.line+1, 0)
            } else {
                return null
            }
        } else {
            return new Pos(pos.line, pos.ch+dir)
        }
    }

    function selectAll(cm) {
        cm.setSelection(Pos(cm.firstLine(), 0), Pos(cm.lastLine()), sel_dontScroll);
    }

    // UPDATING

    // Allow "beforeChange" event handlers to influence a change
    function filterChange(doc, change, update) {
        var obj = {
            canceled: false,
            from: change.from,
            to: change.to,
            text: change.text,
            origin: change.origin,
            cancel: function () {
                return obj.canceled = true;
            }
        };
        if (update) {
            obj.update = function (from, to, text, origin) {
                if (from) {
                    obj.from = clipPos(doc, from);
                }
                if (to) {
                    obj.to = clipPos(doc, to);
                }
                if (text) {
                    obj.text = text;
                }
                if (origin !== undefined) {
                    obj.origin = origin;
                }
            };
        }
        signal(doc, "beforeChange", doc, obj);
        if (doc.cm) {
            signal(doc.cm, "beforeChange", doc.cm, obj);
        }

        if (obj.canceled) {
            if (doc.cm) {
                doc.cm.curOp.updateInput = 2;
            }
            return null
        }
        return {from: obj.from, to: obj.to, text: obj.text, origin: obj.origin}
    }

    // Apply a change to a document, and add it to the document's
    // history, and propagating it to all linked documents.
    function makeChange(doc, change, ignoreReadOnly) {
        if (doc.cm) {
            if (!doc.cm.curOp) {
                return operation(doc.cm, makeChange)(doc, change, ignoreReadOnly)
            }
            if (doc.cm.state.suppressEdits) {
                return
            }
        }

        if (hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange")) {
            change = filterChange(doc, change, true);
            if (!change) {
                return
            }
        }

        // Possibly split or suppress the update based on the presence
        // of read-only spans in its range.
        var split = sawReadOnlySpans && !ignoreReadOnly && removeReadOnlyRanges(doc, change.from, change.to);
        if (split) {
            for (var i = split.length-1; i >= 0; --i) {
                makeChangeInner(doc, {
                    from: split[i].from,
                    to: split[i].to,
                    text: i ? [""] : change.text,
                    origin: change.origin
                });
            }
        } else {
            makeChangeInner(doc, change);
        }
    }

    function makeChangeInner(doc, change) {
        if (change.text.length == 1 && change.text[0] == "" && cmp(change.from, change.to) == 0) {
            return
        }
        var selAfter = computeSelAfterChange(doc, change);
        addChangeToHistory(doc, change, selAfter, doc.cm ? doc.cm.curOp.id : NaN);

        makeChangeSingleDoc(doc, change, selAfter, stretchSpansOverChange(doc, change));
        var rebased = [];

        linkedDocs(doc, function (doc, sharedHist) {
            if (!sharedHist && indexOf(rebased, doc.history) == -1) {
                rebaseHist(doc.history, change);
                rebased.push(doc.history);
            }
            makeChangeSingleDoc(doc, change, null, stretchSpansOverChange(doc, change));
        });
    }

    // Revert a change stored in a document's history.
    function makeChangeFromHistory(doc, type, allowSelectionOnly) {
        var suppress = doc.cm && doc.cm.state.suppressEdits;
        if (suppress && !allowSelectionOnly) {
            return
        }

        var hist = doc.history, event, selAfter = doc.sel;
        var source = type == "undo" ? hist.done : hist.undone, dest = type == "undo" ? hist.undone : hist.done;

        // Verify that there is a useable event (so that ctrl-z won't
        // needlessly clear selection events)
        var i = 0;
        for (; i < source.length; i++) {
            event = source[i];
            if (allowSelectionOnly ? event.ranges && !event.equals(doc.sel) : !event.ranges) {
                break
            }
        }
        if (i == source.length) {
            return
        }
        hist.lastOrigin = hist.lastSelOrigin = null;

        for (; ;) {
            event = source.pop();
            if (event.ranges) {
                pushSelectionToHistory(event, dest);
                if (allowSelectionOnly && !event.equals(doc.sel)) {
                    setSelection(doc, event, {clearRedo: false});
                    return
                }
                selAfter = event;
            } else if (suppress) {
                source.push(event);
                return
            } else {
                break
            }
        }

        // Build up a reverse change object to add to the opposite history
        // stack (redo when undoing, and vice versa).
        var antiChanges = [];
        pushSelectionToHistory(selAfter, dest);
        dest.push({changes: antiChanges, generation: hist.generation});
        hist.generation = event.generation || ++hist.maxGeneration;

        var filter = hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange");

        var loop = function (i) {
            var change = event.changes[i];
            change.origin = type;
            if (filter && !filterChange(doc, change, false)) {
                source.length = 0;
                return {}
            }

            antiChanges.push(historyChangeFromChange(doc, change));

            var after = i ? computeSelAfterChange(doc, change) : lst(source);
            makeChangeSingleDoc(doc, change, after, mergeOldSpans(doc, change));
            if (!i && doc.cm) {
                doc.cm.scrollIntoView({from: change.from, to: changeEnd(change)});
            }
            var rebased = [];

            // Propagate to the linked documents
            linkedDocs(doc, function (doc, sharedHist) {
                if (!sharedHist && indexOf(rebased, doc.history) == -1) {
                    rebaseHist(doc.history, change);
                    rebased.push(doc.history);
                }
                makeChangeSingleDoc(doc, change, null, mergeOldSpans(doc, change));
            });
        };

        for (var i$1 = event.changes.length-1; i$1 >= 0; --i$1) {
            var returned = loop(i$1);

            if (returned) return returned.v;
        }
    }

    // Sub-views need their line numbers shifted when text is added
    // above or below them in the parent document.
    function shiftDoc(doc, distance) {
        if (distance == 0) {
            return
        }
        doc.first += distance;
        doc.sel = new Selection(map(doc.sel.ranges, function (range) {
            return new Range(
                Pos(range.anchor.line+distance, range.anchor.ch),
                Pos(range.head.line+distance, range.head.ch)
            );
        }), doc.sel.primIndex);
        if (doc.cm) {
            regChange(doc.cm, doc.first, doc.first-distance, distance);
            for (var d = doc.cm.display, l = d.viewFrom; l < d.viewTo; l++) {
                regLineChange(doc.cm, l, "gutter");
            }
        }
    }

    // More lower-level change function, handling only a single document
    // (not linked ones).
    function makeChangeSingleDoc(doc, change, selAfter, spans) {
        if (doc.cm && !doc.cm.curOp) {
            return operation(doc.cm, makeChangeSingleDoc)(doc, change, selAfter, spans)
        }

        if (change.to.line < doc.first) {
            shiftDoc(doc, change.text.length-1-(change.to.line-change.from.line));
            return
        }
        if (change.from.line > doc.lastLine()) {
            return
        }

        // Clip the change to the size of this doc
        if (change.from.line < doc.first) {
            var shift = change.text.length-1-(doc.first-change.from.line);
            shiftDoc(doc, shift);
            change = {
                from: Pos(doc.first, 0), to: Pos(change.to.line+shift, change.to.ch),
                text: [lst(change.text)], origin: change.origin
            };
        }
        var last = doc.lastLine();
        if (change.to.line > last) {
            change = {
                from: change.from, to: Pos(last, getLine(doc, last).text.length),
                text: [change.text[0]], origin: change.origin
            };
        }

        change.removed = getBetween(doc, change.from, change.to);

        if (!selAfter) {
            selAfter = computeSelAfterChange(doc, change);
        }
        if (doc.cm) {
            makeChangeSingleDocInEditor(doc.cm, change, spans);
        } else {
            updateDoc(doc, change, spans);
        }
        setSelectionNoUndo(doc, selAfter, sel_dontScroll);

        if (doc.cantEdit && skipAtomic(doc, Pos(doc.firstLine(), 0))) {
            doc.cantEdit = false;
        }
    }

    // Handle the interaction of a change to a document with the editor
    // that this document is part of.
    function makeChangeSingleDocInEditor(cm, change, spans) {
        var doc = cm.doc, display = cm.display, from = change.from, to = change.to;

        var recomputeMaxLength = false, checkWidthStart = from.line;
        if (!cm.options.lineWrapping) {
            checkWidthStart = lineNo(visualLine(getLine(doc, from.line)));
            doc.iter(checkWidthStart, to.line+1, function (line) {
                if (line == display.maxLine) {
                    recomputeMaxLength = true;
                    return true
                }
            });
        }

        if (doc.sel.contains(change.from, change.to) > -1) {
            signalCursorActivity(cm);
        }

        updateDoc(doc, change, spans, estimateHeight(cm));

        if (!cm.options.lineWrapping) {
            doc.iter(checkWidthStart, from.line+change.text.length, function (line) {
                var len = lineLength(line);
                if (len > display.maxLineLength) {
                    display.maxLine = line;
                    display.maxLineLength = len;
                    display.maxLineChanged = true;
                    recomputeMaxLength = false;
                }
            });
            if (recomputeMaxLength) {
                cm.curOp.updateMaxLine = true;
            }
        }

        retreatFrontier(doc, from.line);
        startWorker(cm, 400);

        var lendiff = change.text.length-(to.line-from.line)-1;
        // Remember that these lines changed, for updating the display
        if (change.full) {
            regChange(cm);
        } else if (from.line == to.line && change.text.length == 1 && !isWholeLineUpdate(cm.doc, change)) {
            regLineChange(cm, from.line, "text");
        } else {
            regChange(cm, from.line, to.line+1, lendiff);
        }

        var changesHandler = hasHandler(cm, "changes"), changeHandler = hasHandler(cm, "change");
        if (changeHandler || changesHandler) {
            var obj = {
                from: from, to: to,
                text: change.text,
                removed: change.removed,
                origin: change.origin
            };
            if (changeHandler) {
                signalLater(cm, "change", cm, obj);
            }
            if (changesHandler) {
                (cm.curOp.changeObjs || (cm.curOp.changeObjs = [])).push(obj);
            }
        }
        cm.display.selForContextMenu = null;
    }

    function replaceRange(doc, code, from, to, origin) {
        var assign;

        if (!to) {
            to = from;
        }
        if (cmp(to, from) < 0) {
            (assign = [to, from], from = assign[0], to = assign[1]);
        }
        if (typeof code == "string") {
            code = doc.splitLines(code);
        }
        makeChange(doc, {from: from, to: to, text: code, origin: origin});
    }

    // Rebasing/resetting history to deal with externally-sourced changes

    function rebaseHistSelSingle(pos, from, to, diff) {
        if (to < pos.line) {
            pos.line += diff;
        } else if (from < pos.line) {
            pos.line = from;
            pos.ch = 0;
        }
    }

    // Tries to rebase an array of history events given a change in the
    // document. If the change touches the same lines as the event, the
    // event, and everything 'behind' it, is discarded. If the change is
    // before the event, the event's positions are updated. Uses a
    // copy-on-write scheme for the positions, to avoid having to
    // reallocate them all on every rebase, but also avoid problems with
    // shared position objects being unsafely updated.
    function rebaseHistArray(array, from, to, diff) {
        for (var i = 0; i < array.length; ++i) {
            var sub = array[i], ok = true;
            if (sub.ranges) {
                if (!sub.copied) {
                    sub = array[i] = sub.deepCopy();
                    sub.copied = true;
                }
                for (var j = 0; j < sub.ranges.length; j++) {
                    rebaseHistSelSingle(sub.ranges[j].anchor, from, to, diff);
                    rebaseHistSelSingle(sub.ranges[j].head, from, to, diff);
                }
                continue
            }
            for (var j$1 = 0; j$1 < sub.changes.length; ++j$1) {
                var cur = sub.changes[j$1];
                if (to < cur.from.line) {
                    cur.from = Pos(cur.from.line+diff, cur.from.ch);
                    cur.to = Pos(cur.to.line+diff, cur.to.ch);
                } else if (from <= cur.to.line) {
                    ok = false;
                    break
                }
            }
            if (!ok) {
                array.splice(0, i+1);
                i = 0;
            }
        }
    }

    function rebaseHist(hist, change) {
        var from = change.from.line, to = change.to.line, diff = change.text.length-(to-from)-1;
        rebaseHistArray(hist.done, from, to, diff);
        rebaseHistArray(hist.undone, from, to, diff);
    }

    // Utility for applying a change to a line by handle or number,
    // returning the number and optionally registering the line as
    // changed.
    function changeLine(doc, handle, changeType, op) {
        var no = handle, line = handle;
        if (typeof handle == "number") {
            line = getLine(doc, clipLine(doc, handle));
        } else {
            no = lineNo(handle);
        }
        if (no == null) {
            return null
        }
        if (op(line, no) && doc.cm) {
            regLineChange(doc.cm, no, changeType);
        }
        return line
    }

    // The document is represented as a BTree consisting of leaves, with
    // chunk of lines in them, and branches, with up to ten leaves or
    // other branch nodes below them. The top node is always a branch
    // node, and is the document object itself (meaning it has
    // additional methods and properties).
    //
    // All nodes have parent links. The tree is used both to go from
    // line numbers to line objects, and to go from objects to numbers.
    // It also indexes by height, and is used to convert between height
    // and line object, and to find the total height of the document.
    //
    // See also http://marijnhaverbeke.nl/blog/codemirror-line-tree.html

    function LeafChunk(lines) {
        this.lines = lines;
        this.parent = null;
        var height = 0;
        for (var i = 0; i < lines.length; ++i) {
            lines[i].parent = this;
            height += lines[i].height;
        }
        this.height = height;
    }

    LeafChunk.prototype = {
        chunkSize: function () {
            return this.lines.length
        },

        // Remove the n lines at offset 'at'.
        removeInner: function (at, n) {
            for (var i = at, e = at+n; i < e; ++i) {
                var line = this.lines[i];
                this.height -= line.height;
                cleanUpLine(line);
                signalLater(line, "delete");
            }
            this.lines.splice(at, n);
        },

        // Helper used to collapse a small branch into a single leaf.
        collapse: function (lines) {
            lines.push.apply(lines, this.lines);
        },

        // Insert the given array of lines at offset 'at', count them as
        // having the given height.
        insertInner: function (at, lines, height) {
            this.height += height;
            this.lines = this.lines.slice(0, at).concat(lines).concat(this.lines.slice(at));
            for (var i = 0; i < lines.length; ++i) {
                lines[i].parent = this;
            }
        },

        // Used to iterate over a part of the tree.
        iterN: function (at, n, op) {
            for (var e = at+n; at < e; ++at) {
                if (op(this.lines[at])) {
                    return true
                }
            }
        }
    };

    function BranchChunk(children) {
        this.children = children;
        var size = 0, height = 0;
        for (var i = 0; i < children.length; ++i) {
            var ch = children[i];
            size += ch.chunkSize();
            height += ch.height;
            ch.parent = this;
        }
        this.size = size;
        this.height = height;
        this.parent = null;
    }

    BranchChunk.prototype = {
        chunkSize: function () {
            return this.size
        },

        removeInner: function (at, n) {
            this.size -= n;
            for (var i = 0; i < this.children.length; ++i) {
                var child = this.children[i], sz = child.chunkSize();
                if (at < sz) {
                    var rm = Math.min(n, sz-at), oldHeight = child.height;
                    child.removeInner(at, rm);
                    this.height -= oldHeight-child.height;
                    if (sz == rm) {
                        this.children.splice(i--, 1);
                        child.parent = null;
                    }
                    if ((n -= rm) == 0) {
                        break
                    }
                    at = 0;
                } else {
                    at -= sz;
                }
            }
            // If the result is smaller than 25 lines, ensure that it is a
            // single leaf node.
            if (this.size-n < 25 &&
                (this.children.length > 1 || !(this.children[0] instanceof LeafChunk))) {
                var lines = [];
                this.collapse(lines);
                this.children = [new LeafChunk(lines)];
                this.children[0].parent = this;
            }
        },

        collapse: function (lines) {
            for (var i = 0; i < this.children.length; ++i) {
                this.children[i].collapse(lines);
            }
        },

        insertInner: function (at, lines, height) {
            this.size += lines.length;
            this.height += height;
            for (var i = 0; i < this.children.length; ++i) {
                var child = this.children[i], sz = child.chunkSize();
                if (at <= sz) {
                    child.insertInner(at, lines, height);
                    if (child.lines && child.lines.length > 50) {
                        // To avoid memory thrashing when child.lines is huge (e.g. first view of a large file), it's never spliced.
                        // Instead, small slices are taken. They're taken in order because sequential memory accesses are fastest.
                        var remaining = child.lines.length % 25+25;
                        for (var pos = remaining; pos < child.lines.length;) {
                            var leaf = new LeafChunk(child.lines.slice(pos, pos += 25));
                            child.height -= leaf.height;
                            this.children.splice(++i, 0, leaf);
                            leaf.parent = this;
                        }
                        child.lines = child.lines.slice(0, remaining);
                        this.maybeSpill();
                    }
                    break
                }
                at -= sz;
            }
        },

        // When a node has grown, check whether it should be split.
        maybeSpill: function () {
            if (this.children.length <= 10) {
                return
            }
            var me = this;
            do {
                var spilled = me.children.splice(me.children.length-5, 5);
                var sibling = new BranchChunk(spilled);
                if (!me.parent) { // Become the parent node
                    var copy = new BranchChunk(me.children);
                    copy.parent = me;
                    me.children = [copy, sibling];
                    me = copy;
                } else {
                    me.size -= sibling.size;
                    me.height -= sibling.height;
                    var myIndex = indexOf(me.parent.children, me);
                    me.parent.children.splice(myIndex+1, 0, sibling);
                }
                sibling.parent = me.parent;
            } while (me.children.length > 10)
            me.parent.maybeSpill();
        },

        iterN: function (at, n, op) {
            for (var i = 0; i < this.children.length; ++i) {
                var child = this.children[i], sz = child.chunkSize();
                if (at < sz) {
                    var used = Math.min(n, sz-at);
                    if (child.iterN(at, used, op)) {
                        return true
                    }
                    if ((n -= used) == 0) {
                        break
                    }
                    at = 0;
                } else {
                    at -= sz;
                }
            }
        }
    };

    // Line widgets are block elements displayed above or below a line.

    var LineWidget = function (doc, node, options) {
        if (options) {
            for (var opt in options) {
                if (options.hasOwnProperty(opt)) {
                    this[opt] = options[opt];
                }
            }
        }
        this.doc = doc;
        this.node = node;
    };

    LineWidget.prototype.clear = function () {
        var cm = this.doc.cm, ws = this.line.widgets, line = this.line, no = lineNo(line);
        if (no == null || !ws) {
            return
        }
        for (var i = 0; i < ws.length; ++i) {
            if (ws[i] == this) {
                ws.splice(i--, 1);
            }
        }
        if (!ws.length) {
            line.widgets = null;
        }
        var height = widgetHeight(this);
        updateLineHeight(line, Math.max(0, line.height-height));
        if (cm) {
            runInOp(cm, function () {
                adjustScrollWhenAboveVisible(cm, line, -height);
                regLineChange(cm, no, "widget");
            });
            signalLater(cm, "lineWidgetCleared", cm, this, no);
        }
    };

    LineWidget.prototype.changed = function () {
        var this$1 = this;

        var oldH = this.height, cm = this.doc.cm, line = this.line;
        this.height = null;
        var diff = widgetHeight(this)-oldH;
        if (!diff) {
            return
        }
        if (!lineIsHidden(this.doc, line)) {
            updateLineHeight(line, line.height+diff);
        }
        if (cm) {
            runInOp(cm, function () {
                cm.curOp.forceUpdate = true;
                adjustScrollWhenAboveVisible(cm, line, diff);
                signalLater(cm, "lineWidgetChanged", cm, this$1, lineNo(line));
            });
        }
    };
    eventMixin(LineWidget);

    function adjustScrollWhenAboveVisible(cm, line, diff) {
        if (heightAtLine(line) < ((cm.curOp && cm.curOp.scrollTop) || cm.doc.scrollTop)) {
            addToScrollTop(cm, diff);
        }
    }

    function addLineWidget(doc, handle, node, options) {
        var widget = new LineWidget(doc, node, options);
        var cm = doc.cm;
        if (cm && widget.noHScroll) {
            cm.display.alignWidgets = true;
        }
        changeLine(doc, handle, "widget", function (line) {
            var widgets = line.widgets || (line.widgets = []);
            if (widget.insertAt == null) {
                widgets.push(widget);
            } else {
                widgets.splice(Math.min(widgets.length, Math.max(0, widget.insertAt)), 0, widget);
            }
            widget.line = line;
            if (cm && !lineIsHidden(doc, line)) {
                var aboveVisible = heightAtLine(line) < doc.scrollTop;
                updateLineHeight(line, line.height+widgetHeight(widget));
                if (aboveVisible) {
                    addToScrollTop(cm, widget.height);
                }
                cm.curOp.forceUpdate = true;
            }
            return true
        });
        if (cm) {
            signalLater(cm, "lineWidgetAdded", cm, widget, typeof handle == "number" ? handle : lineNo(handle));
        }
        return widget
    }

    // TEXTMARKERS

    // Created with markText and setBookmark methods. A TextMarker is a
    // handle that can be used to clear or find a marked position in the
    // document. Line objects hold arrays (markedSpans) containing
    // {from, to, marker} object pointing to such marker objects, and
    // indicating that such a marker is present on that line. Multiple
    // lines may point to the same marker when it spans across lines.
    // The spans will have null for their from/to properties when the
    // marker continues beyond the start/end of the line. Markers have
    // links back to the lines they currently touch.

    // Collapsed markers have unique ids, in order to be able to order
    // them, which is needed for uniquely determining an outer marker
    // when they overlap (they may nest, but not partially overlap).
    var nextMarkerId = 0;

    var TextMarker = function (doc, type) {
        this.lines = [];
        this.type = type;
        this.doc = doc;
        this.id = ++nextMarkerId;
    };

    // Clear the marker.
    TextMarker.prototype.clear = function () {
        if (this.explicitlyCleared) {
            return
        }
        var cm = this.doc.cm, withOp = cm && !cm.curOp;
        if (withOp) {
            startOperation(cm);
        }
        if (hasHandler(this, "clear")) {
            var found = this.find();
            if (found) {
                signalLater(this, "clear", found.from, found.to);
            }
        }
        var min = null, max = null;
        for (var i = 0; i < this.lines.length; ++i) {
            var line = this.lines[i];
            var span = getMarkedSpanFor(line.markedSpans, this);
            if (cm && !this.collapsed) {
                regLineChange(cm, lineNo(line), "text");
            } else if (cm) {
                if (span.to != null) {
                    max = lineNo(line);
                }
                if (span.from != null) {
                    min = lineNo(line);
                }
            }
            line.markedSpans = removeMarkedSpan(line.markedSpans, span);
            if (span.from == null && this.collapsed && !lineIsHidden(this.doc, line) && cm) {
                updateLineHeight(line, textHeight(cm.display));
            }
        }
        if (cm && this.collapsed && !cm.options.lineWrapping) {
            for (var i$1 = 0; i$1 < this.lines.length; ++i$1) {
                var visual = visualLine(this.lines[i$1]), len = lineLength(visual);
                if (len > cm.display.maxLineLength) {
                    cm.display.maxLine = visual;
                    cm.display.maxLineLength = len;
                    cm.display.maxLineChanged = true;
                }
            }
        }

        if (min != null && cm && this.collapsed) {
            regChange(cm, min, max+1);
        }
        this.lines.length = 0;
        this.explicitlyCleared = true;
        if (this.atomic && this.doc.cantEdit) {
            this.doc.cantEdit = false;
            if (cm) {
                reCheckSelection(cm.doc);
            }
        }
        if (cm) {
            signalLater(cm, "markerCleared", cm, this, min, max);
        }
        if (withOp) {
            endOperation(cm);
        }
        if (this.parent) {
            this.parent.clear();
        }
    };

    // Find the position of the marker in the document. Returns a {from,
    // to} object by default. Side can be passed to get a specific side
    // -- 0 (both), -1 (left), or 1 (right). When lineObj is true, the
    // Pos objects returned contain a line object, rather than a line
    // number (used to prevent looking up the same line twice).
    TextMarker.prototype.find = function (side, lineObj) {
        if (side == null && this.type == "bookmark") {
            side = 1;
        }
        var from, to;
        for (var i = 0; i < this.lines.length; ++i) {
            var line = this.lines[i];
            var span = getMarkedSpanFor(line.markedSpans, this);
            if (span.from != null) {
                from = Pos(lineObj ? line : lineNo(line), span.from);
                if (side == -1) {
                    return from
                }
            }
            if (span.to != null) {
                to = Pos(lineObj ? line : lineNo(line), span.to);
                if (side == 1) {
                    return to
                }
            }
        }
        return from && {from: from, to: to}
    };

    // Signals that the marker's widget changed, and surrounding layout
    // should be recomputed.
    TextMarker.prototype.changed = function () {
        var this$1 = this;

        var pos = this.find(-1, true), widget = this, cm = this.doc.cm;
        if (!pos || !cm) {
            return
        }
        runInOp(cm, function () {
            var line = pos.line, lineN = lineNo(pos.line);
            var view = findViewForLine(cm, lineN);
            if (view) {
                clearLineMeasurementCacheFor(view);
                cm.curOp.selectionChanged = cm.curOp.forceUpdate = true;
            }
            cm.curOp.updateMaxLine = true;
            if (!lineIsHidden(widget.doc, line) && widget.height != null) {
                var oldHeight = widget.height;
                widget.height = null;
                var dHeight = widgetHeight(widget)-oldHeight;
                if (dHeight) {
                    updateLineHeight(line, line.height+dHeight);
                }
            }
            signalLater(cm, "markerChanged", cm, this$1);
        });
    };

    TextMarker.prototype.attachLine = function (line) {
        if (!this.lines.length && this.doc.cm) {
            var op = this.doc.cm.curOp;
            if (!op.maybeHiddenMarkers || indexOf(op.maybeHiddenMarkers, this) == -1) {
                (op.maybeUnhiddenMarkers || (op.maybeUnhiddenMarkers = [])).push(this);
            }
        }
        this.lines.push(line);
    };

    TextMarker.prototype.detachLine = function (line) {
        this.lines.splice(indexOf(this.lines, line), 1);
        if (!this.lines.length && this.doc.cm) {
            var op = this.doc.cm.curOp
            ;(op.maybeHiddenMarkers || (op.maybeHiddenMarkers = [])).push(this);
        }
    };
    eventMixin(TextMarker);

    // Create a marker, wire it up to the right lines, and
    function markText(doc, from, to, options, type) {
        // Shared markers (across linked documents) are handled separately
        // (markTextShared will call out to this again, once per
        // document).
        if (options && options.shared) {
            return markTextShared(doc, from, to, options, type)
        }
        // Ensure we are in an operation.
        if (doc.cm && !doc.cm.curOp) {
            return operation(doc.cm, markText)(doc, from, to, options, type)
        }

        var marker = new TextMarker(doc, type), diff = cmp(from, to);
        if (options) {
            copyObj(options, marker, false);
        }
        // Don't connect empty markers unless clearWhenEmpty is false
        if (diff > 0 || diff == 0 && marker.clearWhenEmpty !== false) {
            return marker
        }
        if (marker.replacedWith) {
            // Showing up as a widget implies collapsed (widget replaces text)
            marker.collapsed = true;
            marker.widgetNode = eltP("span", [marker.replacedWith], "CodeMirror-widget");
            if (!options.handleMouseEvents) {
                marker.widgetNode.setAttribute("cm-ignore-events", "true");
            }
            if (options.insertLeft) {
                marker.widgetNode.insertLeft = true;
            }
        }
        if (marker.collapsed) {
            if (conflictingCollapsedRange(doc, from.line, from, to, marker) ||
                from.line != to.line && conflictingCollapsedRange(doc, to.line, from, to, marker)) {
                throw new Error("Inserting collapsed marker partially overlapping an existing one")
            }
            seeCollapsedSpans();
        }

        if (marker.addToHistory) {
            addChangeToHistory(doc, {from: from, to: to, origin: "markText"}, doc.sel, NaN);
        }

        var curLine = from.line, cm = doc.cm, updateMaxLine;
        doc.iter(curLine, to.line+1, function (line) {
            if (cm && marker.collapsed && !cm.options.lineWrapping && visualLine(line) == cm.display.maxLine) {
                updateMaxLine = true;
            }
            if (marker.collapsed && curLine != from.line) {
                updateLineHeight(line, 0);
            }
            addMarkedSpan(line, new MarkedSpan(marker,
                curLine == from.line ? from.ch : null,
                curLine == to.line ? to.ch : null), doc.cm && doc.cm.curOp);
            ++curLine;
        });
        // lineIsHidden depends on the presence of the spans, so needs a second pass
        if (marker.collapsed) {
            doc.iter(from.line, to.line+1, function (line) {
                if (lineIsHidden(doc, line)) {
                    updateLineHeight(line, 0);
                }
            });
        }

        if (marker.clearOnEnter) {
            on(marker, "beforeCursorEnter", function () {
                return marker.clear();
            });
        }

        if (marker.readOnly) {
            seeReadOnlySpans();
            if (doc.history.done.length || doc.history.undone.length) {
                doc.clearHistory();
            }
        }
        if (marker.collapsed) {
            marker.id = ++nextMarkerId;
            marker.atomic = true;
        }
        if (cm) {
            // Sync editor state
            if (updateMaxLine) {
                cm.curOp.updateMaxLine = true;
            }
            if (marker.collapsed) {
                regChange(cm, from.line, to.line+1);
            } else if (marker.className || marker.startStyle || marker.endStyle || marker.css ||
                marker.attributes || marker.title) {
                for (var i = from.line; i <= to.line; i++) {
                    regLineChange(cm, i, "text");
                }
            }
            if (marker.atomic) {
                reCheckSelection(cm.doc);
            }
            signalLater(cm, "markerAdded", cm, marker);
        }
        return marker
    }

    // SHARED TEXTMARKERS

    // A shared marker spans multiple linked documents. It is
    // implemented as a meta-marker-object controlling multiple normal
    // markers.
    var SharedTextMarker = function (markers, primary) {
        this.markers = markers;
        this.primary = primary;
        for (var i = 0; i < markers.length; ++i) {
            markers[i].parent = this;
        }
    };

    SharedTextMarker.prototype.clear = function () {
        if (this.explicitlyCleared) {
            return
        }
        this.explicitlyCleared = true;
        for (var i = 0; i < this.markers.length; ++i) {
            this.markers[i].clear();
        }
        signalLater(this, "clear");
    };

    SharedTextMarker.prototype.find = function (side, lineObj) {
        return this.primary.find(side, lineObj)
    };
    eventMixin(SharedTextMarker);

    function markTextShared(doc, from, to, options, type) {
        options = copyObj(options);
        options.shared = false;
        var markers = [markText(doc, from, to, options, type)], primary = markers[0];
        var widget = options.widgetNode;
        linkedDocs(doc, function (doc) {
            if (widget) {
                options.widgetNode = widget.cloneNode(true);
            }
            markers.push(markText(doc, clipPos(doc, from), clipPos(doc, to), options, type));
            for (var i = 0; i < doc.linked.length; ++i) {
                if (doc.linked[i].isParent) {
                    return
                }
            }
            primary = lst(markers);
        });
        return new SharedTextMarker(markers, primary)
    }

    function findSharedMarkers(doc) {
        return doc.findMarks(Pos(doc.first, 0), doc.clipPos(Pos(doc.lastLine())), function (m) {
            return m.parent;
        })
    }

    function copySharedMarkers(doc, markers) {
        for (var i = 0; i < markers.length; i++) {
            var marker = markers[i], pos = marker.find();
            var mFrom = doc.clipPos(pos.from), mTo = doc.clipPos(pos.to);
            if (cmp(mFrom, mTo)) {
                var subMark = markText(doc, mFrom, mTo, marker.primary, marker.primary.type);
                marker.markers.push(subMark);
                subMark.parent = marker;
            }
        }
    }

    function detachSharedMarkers(markers) {
        var loop = function (i) {
            var marker = markers[i], linked = [marker.primary.doc];
            linkedDocs(marker.primary.doc, function (d) {
                return linked.push(d);
            });
            for (var j = 0; j < marker.markers.length; j++) {
                var subMarker = marker.markers[j];
                if (indexOf(linked, subMarker.doc) == -1) {
                    subMarker.parent = null;
                    marker.markers.splice(j--, 1);
                }
            }
        };

        for (var i = 0; i < markers.length; i++) loop(i);
    }

    var nextDocId = 0;
    var Doc = function (text, mode, firstLine, lineSep, direction) {
        if (!(this instanceof Doc)) {
            return new Doc(text, mode, firstLine, lineSep, direction)
        }
        if (firstLine == null) {
            firstLine = 0;
        }

        BranchChunk.call(this, [new LeafChunk([new Line("", null)])]);
        this.first = firstLine;
        this.scrollTop = this.scrollLeft = 0;
        this.cantEdit = false;
        this.cleanGeneration = 1;
        this.modeFrontier = this.highlightFrontier = firstLine;
        var start = Pos(firstLine, 0);
        this.sel = simpleSelection(start);
        this.history = new History(null);
        this.id = ++nextDocId;
        this.modeOption = mode;
        this.lineSep = lineSep;
        this.direction = (direction == "rtl") ? "rtl" : "ltr";
        this.extend = false;

        if (typeof text == "string") {
            text = this.splitLines(text);
        }
        updateDoc(this, {from: start, to: start, text: text});
        setSelection(this, simpleSelection(start), sel_dontScroll);
    };

    Doc.prototype = createObj(BranchChunk.prototype, {
        constructor: Doc,
        // Iterate over the document. Supports two forms -- with only one
        // argument, it calls that for each line in the document. With
        // three, it iterates over the range given by the first two (with
        // the second being non-inclusive).
        iter: function (from, to, op) {
            if (op) {
                this.iterN(from-this.first, to-from, op);
            } else {
                this.iterN(this.first, this.first+this.size, from);
            }
        },

        // Non-public interface for adding and removing lines.
        insert: function (at, lines) {
            var height = 0;
            for (var i = 0; i < lines.length; ++i) {
                height += lines[i].height;
            }
            this.insertInner(at-this.first, lines, height);
        },
        remove: function (at, n) {
            this.removeInner(at-this.first, n);
        },

        // From here, the methods are part of the public interface. Most
        // are also available from CodeMirror (editor) instances.

        getValue: function (lineSep) {
            var lines = getLines(this, this.first, this.first+this.size);
            if (lineSep === false) {
                return lines
            }
            return lines.join(lineSep || this.lineSeparator())
        },
        setValue: docMethodOp(function (code) {
            var top = Pos(this.first, 0), last = this.first+this.size-1;
            makeChange(this, {
                from: top, to: Pos(last, getLine(this, last).text.length),
                text: this.splitLines(code), origin: "setValue", full: true
            }, true);
            if (this.cm) {
                scrollToCoords(this.cm, 0, 0);
            }
            setSelection(this, simpleSelection(top), sel_dontScroll);
        }),
        replaceRange: function (code, from, to, origin) {
            from = clipPos(this, from);
            to = to ? clipPos(this, to) : from;
            replaceRange(this, code, from, to, origin);
        },
        getRange: function (from, to, lineSep) {
            var lines = getBetween(this, clipPos(this, from), clipPos(this, to));
            if (lineSep === false) {
                return lines
            }
            if (lineSep === '') {
                return lines.join('')
            }
            return lines.join(lineSep || this.lineSeparator())
        },

        getLine: function (line) {
            var l = this.getLineHandle(line);
            return l && l.text
        },

        getLineHandle: function (line) {
            if (isLine(this, line)) {
                return getLine(this, line)
            }
        },
        getLineNumber: function (line) {
            return lineNo(line)
        },

        getLineHandleVisualStart: function (line) {
            if (typeof line == "number") {
                line = getLine(this, line);
            }
            return visualLine(line)
        },

        lineCount: function () {
            return this.size
        },
        firstLine: function () {
            return this.first
        },
        lastLine: function () {
            return this.first+this.size-1
        },

        clipPos: function (pos) {
            return clipPos(this, pos)
        },

        getCursor: function (start) {
            var range = this.sel.primary(), pos;
            if (start == null || start == "head") {
                pos = range.head;
            } else if (start == "anchor") {
                pos = range.anchor;
            } else if (start == "end" || start == "to" || start === false) {
                pos = range.to();
            } else {
                pos = range.from();
            }
            return pos
        },
        listSelections: function () {
            return this.sel.ranges
        },
        somethingSelected: function () {
            return this.sel.somethingSelected()
        },

        setCursor: docMethodOp(function (line, ch, options) {
            setSimpleSelection(this, clipPos(this, typeof line == "number" ? Pos(line, ch || 0) : line), null, options);
        }),
        setSelection: docMethodOp(function (anchor, head, options) {
            setSimpleSelection(this, clipPos(this, anchor), clipPos(this, head || anchor), options);
        }),
        extendSelection: docMethodOp(function (head, other, options) {
            extendSelection(this, clipPos(this, head), other && clipPos(this, other), options);
        }),
        extendSelections: docMethodOp(function (heads, options) {
            extendSelections(this, clipPosArray(this, heads), options);
        }),
        extendSelectionsBy: docMethodOp(function (f, options) {
            var heads = map(this.sel.ranges, f);
            extendSelections(this, clipPosArray(this, heads), options);
        }),
        setSelections: docMethodOp(function (ranges, primary, options) {
            if (!ranges.length) {
                return
            }
            var out = [];
            for (var i = 0; i < ranges.length; i++) {
                out[i] = new Range(clipPos(this, ranges[i].anchor),
                    clipPos(this, ranges[i].head || ranges[i].anchor));
            }
            if (primary == null) {
                primary = Math.min(ranges.length-1, this.sel.primIndex);
            }
            setSelection(this, normalizeSelection(this.cm, out, primary), options);
        }),
        addSelection: docMethodOp(function (anchor, head, options) {
            var ranges = this.sel.ranges.slice(0);
            ranges.push(new Range(clipPos(this, anchor), clipPos(this, head || anchor)));
            setSelection(this, normalizeSelection(this.cm, ranges, ranges.length-1), options);
        }),

        getSelection: function (lineSep) {
            var ranges = this.sel.ranges, lines;
            for (var i = 0; i < ranges.length; i++) {
                var sel = getBetween(this, ranges[i].from(), ranges[i].to());
                lines = lines ? lines.concat(sel) : sel;
            }
            if (lineSep === false) {
                return lines
            } else {
                return lines.join(lineSep || this.lineSeparator())
            }
        },
        getSelections: function (lineSep) {
            var parts = [], ranges = this.sel.ranges;
            for (var i = 0; i < ranges.length; i++) {
                var sel = getBetween(this, ranges[i].from(), ranges[i].to());
                if (lineSep !== false) {
                    sel = sel.join(lineSep || this.lineSeparator());
                }
                parts[i] = sel;
            }
            return parts
        },
        replaceSelection: function (code, collapse, origin) {
            var dup = [];
            for (var i = 0; i < this.sel.ranges.length; i++) {
                dup[i] = code;
            }
            this.replaceSelections(dup, collapse, origin || "+input");
        },
        replaceSelections: docMethodOp(function (code, collapse, origin) {
            var changes = [], sel = this.sel;
            for (var i = 0; i < sel.ranges.length; i++) {
                var range = sel.ranges[i];
                changes[i] = {from: range.from(), to: range.to(), text: this.splitLines(code[i]), origin: origin};
            }
            var newSel = collapse && collapse != "end" && computeReplacedSel(this, changes, collapse);
            for (var i$1 = changes.length-1; i$1 >= 0; i$1--) {
                makeChange(this, changes[i$1]);
            }
            if (newSel) {
                setSelectionReplaceHistory(this, newSel);
            } else if (this.cm) {
                ensureCursorVisible(this.cm);
            }
        }),
        undo: docMethodOp(function () {
            makeChangeFromHistory(this, "undo");
        }),
        redo: docMethodOp(function () {
            makeChangeFromHistory(this, "redo");
        }),
        undoSelection: docMethodOp(function () {
            makeChangeFromHistory(this, "undo", true);
        }),
        redoSelection: docMethodOp(function () {
            makeChangeFromHistory(this, "redo", true);
        }),

        setExtending: function (val) {
            this.extend = val;
        },
        getExtending: function () {
            return this.extend
        },

        historySize: function () {
            var hist = this.history, done = 0, undone = 0;
            for (var i = 0; i < hist.done.length; i++) {
                if (!hist.done[i].ranges) {
                    ++done;
                }
            }
            for (var i$1 = 0; i$1 < hist.undone.length; i$1++) {
                if (!hist.undone[i$1].ranges) {
                    ++undone;
                }
            }
            return {undo: done, redo: undone}
        },
        clearHistory: function () {
            var this$1 = this;

            this.history = new History(this.history);
            linkedDocs(this, function (doc) {
                return doc.history = this$1.history;
            }, true);
        },

        markClean: function () {
            this.cleanGeneration = this.changeGeneration(true);
        },
        changeGeneration: function (forceSplit) {
            if (forceSplit) {
                this.history.lastOp = this.history.lastSelOp = this.history.lastOrigin = null;
            }
            return this.history.generation
        },
        isClean: function (gen) {
            return this.history.generation == (gen || this.cleanGeneration)
        },

        getHistory: function () {
            return {
                done: copyHistoryArray(this.history.done),
                undone: copyHistoryArray(this.history.undone)
            }
        },
        setHistory: function (histData) {
            var hist = this.history = new History(this.history);
            hist.done = copyHistoryArray(histData.done.slice(0), null, true);
            hist.undone = copyHistoryArray(histData.undone.slice(0), null, true);
        },

        setGutterMarker: docMethodOp(function (line, gutterID, value) {
            return changeLine(this, line, "gutter", function (line) {
                var markers = line.gutterMarkers || (line.gutterMarkers = {});
                markers[gutterID] = value;
                if (!value && isEmpty(markers)) {
                    line.gutterMarkers = null;
                }
                return true
            })
        }),

        clearGutter: docMethodOp(function (gutterID) {
            var this$1 = this;

            this.iter(function (line) {
                if (line.gutterMarkers && line.gutterMarkers[gutterID]) {
                    changeLine(this$1, line, "gutter", function () {
                        line.gutterMarkers[gutterID] = null;
                        if (isEmpty(line.gutterMarkers)) {
                            line.gutterMarkers = null;
                        }
                        return true
                    });
                }
            });
        }),

        lineInfo: function (line) {
            var n;
            if (typeof line == "number") {
                if (!isLine(this, line)) {
                    return null
                }
                n = line;
                line = getLine(this, line);
                if (!line) {
                    return null
                }
            } else {
                n = lineNo(line);
                if (n == null) {
                    return null
                }
            }
            return {
                line: n, handle: line, text: line.text, gutterMarkers: line.gutterMarkers,
                textClass: line.textClass, bgClass: line.bgClass, wrapClass: line.wrapClass,
                widgets: line.widgets
            }
        },

        addLineClass: docMethodOp(function (handle, where, cls) {
            return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
                var prop = where == "text" ? "textClass"
                    : where == "background" ? "bgClass"
                        : where == "gutter" ? "gutterClass" : "wrapClass";
                if (!line[prop]) {
                    line[prop] = cls;
                } else if (classTest(cls).test(line[prop])) {
                    return false
                } else {
                    line[prop] += " "+cls;
                }
                return true
            })
        }),
        removeLineClass: docMethodOp(function (handle, where, cls) {
            return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
                var prop = where == "text" ? "textClass"
                    : where == "background" ? "bgClass"
                        : where == "gutter" ? "gutterClass" : "wrapClass";
                var cur = line[prop];
                if (!cur) {
                    return false
                } else if (cls == null) {
                    line[prop] = null;
                } else {
                    var found = cur.match(classTest(cls));
                    if (!found) {
                        return false
                    }
                    var end = found.index+found[0].length;
                    line[prop] = cur.slice(0, found.index)+(!found.index || end == cur.length ? "" : " ")+cur.slice(end) || null;
                }
                return true
            })
        }),

        addLineWidget: docMethodOp(function (handle, node, options) {
            return addLineWidget(this, handle, node, options)
        }),
        removeLineWidget: function (widget) {
            widget.clear();
        },

        markText: function (from, to, options) {
            return markText(this, clipPos(this, from), clipPos(this, to), options, options && options.type || "range")
        },
        setBookmark: function (pos, options) {
            var realOpts = {
                replacedWith: options && (options.nodeType == null ? options.widget : options),
                insertLeft: options && options.insertLeft,
                clearWhenEmpty: false, shared: options && options.shared,
                handleMouseEvents: options && options.handleMouseEvents
            };
            pos = clipPos(this, pos);
            return markText(this, pos, pos, realOpts, "bookmark")
        },
        findMarksAt: function (pos) {
            pos = clipPos(this, pos);
            var markers = [], spans = getLine(this, pos.line).markedSpans;
            if (spans) {
                for (var i = 0; i < spans.length; ++i) {
                    var span = spans[i];
                    if ((span.from == null || span.from <= pos.ch) &&
                        (span.to == null || span.to >= pos.ch)) {
                        markers.push(span.marker.parent || span.marker);
                    }
                }
            }
            return markers
        },
        findMarks: function (from, to, filter) {
            from = clipPos(this, from);
            to = clipPos(this, to);
            var found = [], lineNo = from.line;
            this.iter(from.line, to.line+1, function (line) {
                var spans = line.markedSpans;
                if (spans) {
                    for (var i = 0; i < spans.length; i++) {
                        var span = spans[i];
                        if (!(span.to != null && lineNo == from.line && from.ch >= span.to ||
                            span.from == null && lineNo != from.line ||
                            span.from != null && lineNo == to.line && span.from >= to.ch) &&
                            (!filter || filter(span.marker))) {
                            found.push(span.marker.parent || span.marker);
                        }
                    }
                }
                ++lineNo;
            });
            return found
        },
        getAllMarks: function () {
            var markers = [];
            this.iter(function (line) {
                var sps = line.markedSpans;
                if (sps) {
                    for (var i = 0; i < sps.length; ++i) {
                        if (sps[i].from != null) {
                            markers.push(sps[i].marker);
                        }
                    }
                }
            });
            return markers
        },

        posFromIndex: function (off) {
            var ch, lineNo = this.first, sepSize = this.lineSeparator().length;
            this.iter(function (line) {
                var sz = line.text.length+sepSize;
                if (sz > off) {
                    ch = off;
                    return true
                }
                off -= sz;
                ++lineNo;
            });
            return clipPos(this, Pos(lineNo, ch))
        },
        indexFromPos: function (coords) {
            coords = clipPos(this, coords);
            var index = coords.ch;
            if (coords.line < this.first || coords.ch < 0) {
                return 0
            }
            var sepSize = this.lineSeparator().length;
            this.iter(this.first, coords.line, function (line) { // iter aborts when callback returns a truthy value
                index += line.text.length+sepSize;
            });
            return index
        },

        copy: function (copyHistory) {
            var doc = new Doc(getLines(this, this.first, this.first+this.size),
                this.modeOption, this.first, this.lineSep, this.direction);
            doc.scrollTop = this.scrollTop;
            doc.scrollLeft = this.scrollLeft;
            doc.sel = this.sel;
            doc.extend = false;
            if (copyHistory) {
                doc.history.undoDepth = this.history.undoDepth;
                doc.setHistory(this.getHistory());
            }
            return doc
        },

        linkedDoc: function (options) {
            if (!options) {
                options = {};
            }
            var from = this.first, to = this.first+this.size;
            if (options.from != null && options.from > from) {
                from = options.from;
            }
            if (options.to != null && options.to < to) {
                to = options.to;
            }
            var copy = new Doc(getLines(this, from, to), options.mode || this.modeOption, from, this.lineSep, this.direction);
            if (options.sharedHist) {
                copy.history = this.history
                ;
            }
            (this.linked || (this.linked = [])).push({doc: copy, sharedHist: options.sharedHist});
            copy.linked = [{doc: this, isParent: true, sharedHist: options.sharedHist}];
            copySharedMarkers(copy, findSharedMarkers(this));
            return copy
        },
        unlinkDoc: function (other) {
            if (other instanceof CodeMirror) {
                other = other.doc;
            }
            if (this.linked) {
                for (var i = 0; i < this.linked.length; ++i) {
                    var link = this.linked[i];
                    if (link.doc != other) {
                        continue
                    }
                    this.linked.splice(i, 1);
                    other.unlinkDoc(this);
                    detachSharedMarkers(findSharedMarkers(this));
                    break
                }
            }
            // If the histories were shared, split them again
            if (other.history == this.history) {
                var splitIds = [other.id];
                linkedDocs(other, function (doc) {
                    return splitIds.push(doc.id);
                }, true);
                other.history = new History(null);
                other.history.done = copyHistoryArray(this.history.done, splitIds);
                other.history.undone = copyHistoryArray(this.history.undone, splitIds);
            }
        },
        iterLinkedDocs: function (f) {
            linkedDocs(this, f);
        },

        getMode: function () {
            return this.mode
        },
        getEditor: function () {
            return this.cm
        },

        splitLines: function (str) {
            if (this.lineSep) {
                return str.split(this.lineSep)
            }
            return splitLinesAuto(str)
        },
        lineSeparator: function () {
            return this.lineSep || "\n"
        },

        setDirection: docMethodOp(function (dir) {
            if (dir != "rtl") {
                dir = "ltr";
            }
            if (dir == this.direction) {
                return
            }
            this.direction = dir;
            this.iter(function (line) {
                return line.order = null;
            });
            if (this.cm) {
                directionChanged(this.cm);
            }
        })
    });

    // Public alias.
    Doc.prototype.eachLine = Doc.prototype.iter;

    // Kludge to work around strange IE behavior where it'll sometimes
    // re-fire a series of drag-related events right after the drop (#1551)
    var lastDrop = 0;

    function onDrop(e) {
        var cm = this;
        clearDragCursor(cm);
        if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e)) {
            return
        }
        e_preventDefault(e);
        if (ie) {
            lastDrop = +new Date;
        }
        var pos = posFromMouse(cm, e, true), files = e.dataTransfer.files;
        if (!pos || cm.isReadOnly()) {
            return
        }
        // Might be a file drop, in which case we simply extract the text
        // and insert it.
        if (files && files.length && window.FileReader && window.File) {
            var n = files.length, text = Array(n), read = 0;
            var markAsReadAndPasteIfAllFilesAreRead = function () {
                if (++read == n) {
                    operation(cm, function () {
                        pos = clipPos(cm.doc, pos);
                        var change = {
                            from: pos, to: pos,
                            text: cm.doc.splitLines(
                                text.filter(function (t) {
                                    return t != null;
                                }).join(cm.doc.lineSeparator())),
                            origin: "paste"
                        };
                        makeChange(cm.doc, change);
                        setSelectionReplaceHistory(cm.doc, simpleSelection(clipPos(cm.doc, pos), clipPos(cm.doc, changeEnd(change))));
                    })();
                }
            };
            var readTextFromFile = function (file, i) {
                if (cm.options.allowDropFileTypes &&
                    indexOf(cm.options.allowDropFileTypes, file.type) == -1) {
                    markAsReadAndPasteIfAllFilesAreRead();
                    return
                }
                var reader = new FileReader;
                reader.onerror = function () {
                    return markAsReadAndPasteIfAllFilesAreRead();
                };
                reader.onload = function () {
                    var content = reader.result;
                    if (/[\x00-\x08\x0e-\x1f]{2}/.test(content)) {
                        markAsReadAndPasteIfAllFilesAreRead();
                        return
                    }
                    text[i] = content;
                    markAsReadAndPasteIfAllFilesAreRead();
                };
                reader.readAsText(file);
            };
            for (var i = 0; i < files.length; i++) {
                readTextFromFile(files[i], i);
            }
        } else { // Normal drop
            // Don't do a replace if the drop happened inside of the selected text.
            if (cm.state.draggingText && cm.doc.sel.contains(pos) > -1) {
                cm.state.draggingText(e);
                // Ensure the editor is re-focused
                setTimeout(function () {
                    return cm.display.input.focus();
                }, 20);
                return
            }
            try {
                var text$1 = e.dataTransfer.getData("Text");
                if (text$1) {
                    var selected;
                    if (cm.state.draggingText && !cm.state.draggingText.copy) {
                        selected = cm.listSelections();
                    }
                    setSelectionNoUndo(cm.doc, simpleSelection(pos, pos));
                    if (selected) {
                        for (var i$1 = 0; i$1 < selected.length; ++i$1) {
                            replaceRange(cm.doc, "", selected[i$1].anchor, selected[i$1].head, "drag");
                        }
                    }
                    cm.replaceSelection(text$1, "around", "paste");
                    cm.display.input.focus();
                }
            } catch (e$1) {
            }
        }
    }

    function onDragStart(cm, e) {
        if (ie && (!cm.state.draggingText || +new Date-lastDrop < 100)) {
            e_stop(e);
            return
        }
        if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e)) {
            return
        }

        e.dataTransfer.setData("Text", cm.getSelection());
        e.dataTransfer.effectAllowed = "copyMove";

        // Use dummy image instead of default browsers image.
        // Recent Safari (~6.0.2) have a tendency to segfault when this happens, so we don't do it there.
        if (e.dataTransfer.setDragImage && !safari) {
            var img = elt("img", null, null, "position: fixed; left: 0; top: 0;");
            img.src = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
            if (presto) {
                img.width = img.height = 1;
                cm.display.wrapper.appendChild(img);
                // Force a relayout, or Opera won't use our image for some obscure reason
                img._top = img.offsetTop;
            }
            e.dataTransfer.setDragImage(img, 0, 0);
            if (presto) {
                img.parentNode.removeChild(img);
            }
        }
    }

    function onDragOver(cm, e) {
        var pos = posFromMouse(cm, e);
        if (!pos) {
            return
        }
        var frag = document.createDocumentFragment();
        drawSelectionCursor(cm, pos, frag);
        if (!cm.display.dragCursor) {
            cm.display.dragCursor = elt("div", null, "CodeMirror-cursors CodeMirror-dragcursors");
            cm.display.lineSpace.insertBefore(cm.display.dragCursor, cm.display.cursorDiv);
        }
        removeChildrenAndAdd(cm.display.dragCursor, frag);
    }

    function clearDragCursor(cm) {
        if (cm.display.dragCursor) {
            cm.display.lineSpace.removeChild(cm.display.dragCursor);
            cm.display.dragCursor = null;
        }
    }

    // These must be handled carefully, because naively registering a
    // handler for each editor will cause the editors to never be
    // garbage collected.

    function forEachCodeMirror(f) {
        if (!document.getElementsByClassName) {
            return
        }
        var byClass = document.getElementsByClassName("CodeMirror"), editors = [];
        for (var i = 0; i < byClass.length; i++) {
            var cm = byClass[i].CodeMirror;
            if (cm) {
                editors.push(cm);
            }
        }
        if (editors.length) {
            editors[0].operation(function () {
                for (var i = 0; i < editors.length; i++) {
                    f(editors[i]);
                }
            });
        }
    }

    var globalsRegistered = false;

    function ensureGlobalHandlers() {
        if (globalsRegistered) {
            return
        }
        registerGlobalHandlers();
        globalsRegistered = true;
    }

    function registerGlobalHandlers() {
        // When the window resizes, we need to refresh active editors.
        var resizeTimer;
        on(window, "resize", function () {
            if (resizeTimer == null) {
                resizeTimer = setTimeout(function () {
                    resizeTimer = null;
                    forEachCodeMirror(onResize);
                }, 100);
            }
        });
        // When the window loses focus, we want to show the editor as blurred
        on(window, "blur", function () {
            return forEachCodeMirror(onBlur);
        });
    }

    // Called when the window resizes
    function onResize(cm) {
        var d = cm.display;
        // Might be a text scaling operation, clear size caches.
        d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null;
        d.scrollbarsClipped = false;
        cm.setSize();
    }

    var keyNames = {
        3: "Pause", 8: "Backspace", 9: "Tab", 13: "Enter", 16: "Shift", 17: "Ctrl", 18: "Alt",
        19: "Pause", 20: "CapsLock", 27: "Esc", 32: "Space", 33: "PageUp", 34: "PageDown", 35: "End",
        36: "Home", 37: "Left", 38: "Up", 39: "Right", 40: "Down", 44: "PrintScrn", 45: "Insert",
        46: "Delete", 59: ";", 61: "=", 91: "Mod", 92: "Mod", 93: "Mod",
        106: "*", 107: "=", 109: "-", 110: ".", 111: "/", 145: "ScrollLock",
        173: "-", 186: ";", 187: "=", 188: ",", 189: "-", 190: ".", 191: "/", 192: "`", 219: "[", 220: "\\",
        221: "]", 222: "'", 224: "Mod", 63232: "Up", 63233: "Down", 63234: "Left", 63235: "Right", 63272: "Delete",
        63273: "Home", 63275: "End", 63276: "PageUp", 63277: "PageDown", 63302: "Insert"
    };

    // Number keys
    for (var i = 0; i < 10; i++) {
        keyNames[i+48] = keyNames[i+96] = String(i);
    }
    // Alphabetic keys
    for (var i$1 = 65; i$1 <= 90; i$1++) {
        keyNames[i$1] = String.fromCharCode(i$1);
    }
    // Function keys
    for (var i$2 = 1; i$2 <= 12; i$2++) {
        keyNames[i$2+111] = keyNames[i$2+63235] = "F"+i$2;
    }

    var keyMap = {};

    keyMap.basic = {
        "Left": "goCharLeft", "Right": "goCharRight", "Up": "goLineUp", "Down": "goLineDown",
        "End": "goLineEnd", "Home": "goLineStartSmart", "PageUp": "goPageUp", "PageDown": "goPageDown",
        "Delete": "delCharAfter", "Backspace": "delCharBefore", "Shift-Backspace": "delCharBefore",
        "Tab": "defaultTab", "Shift-Tab": "indentAuto",
        "Enter": "newlineAndIndent", "Insert": "toggleOverwrite",
        "Esc": "singleSelection"
    };
    // Note that the save and find-related commands aren't defined by
    // default. User code or addons can define them. Unknown commands
    // are simply ignored.
    keyMap.pcDefault = {
        "Ctrl-A": "selectAll", "Ctrl-D": "deleteLine", "Ctrl-Z": "undo", "Shift-Ctrl-Z": "redo", "Ctrl-Y": "redo",
        "Ctrl-Home": "goDocStart", "Ctrl-End": "goDocEnd", "Ctrl-Up": "goLineUp", "Ctrl-Down": "goLineDown",
        "Ctrl-Left": "goGroupLeft", "Ctrl-Right": "goGroupRight", "Alt-Left": "goLineStart", "Alt-Right": "goLineEnd",
        "Ctrl-Backspace": "delGroupBefore", "Ctrl-Delete": "delGroupAfter", "Ctrl-S": "save", "Ctrl-F": "find",
        "Ctrl-G": "findNext", "Shift-Ctrl-G": "findPrev", "Shift-Ctrl-F": "replace", "Shift-Ctrl-R": "replaceAll",
        "Ctrl-[": "indentLess", "Ctrl-]": "indentMore",
        "Ctrl-U": "undoSelection", "Shift-Ctrl-U": "redoSelection", "Alt-U": "redoSelection",
        "fallthrough": "basic"
    };
    // Very basic readline/emacs-style bindings, which are standard on Mac.
    keyMap.emacsy = {
        "Ctrl-F": "goCharRight", "Ctrl-B": "goCharLeft", "Ctrl-P": "goLineUp", "Ctrl-N": "goLineDown",
        "Ctrl-A": "goLineStart", "Ctrl-E": "goLineEnd", "Ctrl-V": "goPageDown", "Shift-Ctrl-V": "goPageUp",
        "Ctrl-D": "delCharAfter", "Ctrl-H": "delCharBefore", "Alt-Backspace": "delWordBefore", "Ctrl-K": "killLine",
        "Ctrl-T": "transposeChars", "Ctrl-O": "openLine"
    };
    keyMap.macDefault = {
        "Cmd-A": "selectAll",
        "Cmd-D": "deleteLine",
        "Cmd-Z": "undo",
        "Shift-Cmd-Z": "redo",
        "Cmd-Y": "redo",
        "Cmd-Home": "goDocStart",
        "Cmd-Up": "goDocStart",
        "Cmd-End": "goDocEnd",
        "Cmd-Down": "goDocEnd",
        "Alt-Left": "goGroupLeft",
        "Alt-Right": "goGroupRight",
        "Cmd-Left": "goLineLeft",
        "Cmd-Right": "goLineRight",
        "Alt-Backspace": "delGroupBefore",
        "Ctrl-Alt-Backspace": "delGroupAfter",
        "Alt-Delete": "delGroupAfter",
        "Cmd-S": "save",
        "Cmd-F": "find",
        "Cmd-G": "findNext",
        "Shift-Cmd-G": "findPrev",
        "Cmd-Alt-F": "replace",
        "Shift-Cmd-Alt-F": "replaceAll",
        "Cmd-[": "indentLess",
        "Cmd-]": "indentMore",
        "Cmd-Backspace": "delWrappedLineLeft",
        "Cmd-Delete": "delWrappedLineRight",
        "Cmd-U": "undoSelection",
        "Shift-Cmd-U": "redoSelection",
        "Ctrl-Up": "goDocStart",
        "Ctrl-Down": "goDocEnd",
        "fallthrough": ["basic", "emacsy"]
    };
    keyMap["default"] = mac ? keyMap.macDefault : keyMap.pcDefault;

    // KEYMAP DISPATCH

    function normalizeKeyName(name) {
        var parts = name.split(/-(?!$)/);
        name = parts[parts.length-1];
        var alt, ctrl, shift, cmd;
        for (var i = 0; i < parts.length-1; i++) {
            var mod = parts[i];
            if (/^(cmd|meta|m)$/i.test(mod)) {
                cmd = true;
            } else if (/^a(lt)?$/i.test(mod)) {
                alt = true;
            } else if (/^(c|ctrl|control)$/i.test(mod)) {
                ctrl = true;
            } else if (/^s(hift)?$/i.test(mod)) {
                shift = true;
            } else {
                throw new Error("Unrecognized modifier name: "+mod)
            }
        }
        if (alt) {
            name = "Alt-"+name;
        }
        if (ctrl) {
            name = "Ctrl-"+name;
        }
        if (cmd) {
            name = "Cmd-"+name;
        }
        if (shift) {
            name = "Shift-"+name;
        }
        return name
    }

    // This is a kludge to keep keymaps mostly working as raw objects
    // (backwards compatibility) while at the same time support features
    // like normalization and multi-stroke key bindings. It compiles a
    // new normalized keymap, and then updates the old object to reflect
    // this.
    function normalizeKeyMap(keymap) {
        var copy = {};
        for (var keyname in keymap) {
            if (keymap.hasOwnProperty(keyname)) {
                var value = keymap[keyname];
                if (/^(name|fallthrough|(de|at)tach)$/.test(keyname)) {
                    continue
                }
                if (value == "...") {
                    delete keymap[keyname];
                    continue
                }

                var keys = map(keyname.split(" "), normalizeKeyName);
                for (var i = 0; i < keys.length; i++) {
                    var val = (void 0), name = (void 0);
                    if (i == keys.length-1) {
                        name = keys.join(" ");
                        val = value;
                    } else {
                        name = keys.slice(0, i+1).join(" ");
                        val = "...";
                    }
                    var prev = copy[name];
                    if (!prev) {
                        copy[name] = val;
                    } else if (prev != val) {
                        throw new Error("Inconsistent bindings for "+name)
                    }
                }
                delete keymap[keyname];
            }
        }
        for (var prop in copy) {
            keymap[prop] = copy[prop];
        }
        return keymap
    }

    function lookupKey(key, map, handle, context) {
        map = getKeyMap(map);
        var found = map.call ? map.call(key, context) : map[key];
        if (found === false) {
            return "nothing"
        }
        if (found === "...") {
            return "multi"
        }
        if (found != null && handle(found)) {
            return "handled"
        }

        if (map.fallthrough) {
            if (Object.prototype.toString.call(map.fallthrough) != "[object Array]") {
                return lookupKey(key, map.fallthrough, handle, context)
            }
            for (var i = 0; i < map.fallthrough.length; i++) {
                var result = lookupKey(key, map.fallthrough[i], handle, context);
                if (result) {
                    return result
                }
            }
        }
    }

    // Modifier key presses don't count as 'real' key presses for the
    // purpose of keymap fallthrough.
    function isModifierKey(value) {
        var name = typeof value == "string" ? value : keyNames[value.keyCode];
        return name == "Ctrl" || name == "Alt" || name == "Shift" || name == "Mod"
    }

    function addModifierNames(name, event, noShift) {
        var base = name;
        if (event.altKey && base != "Alt") {
            name = "Alt-"+name;
        }
        if ((flipCtrlCmd ? event.metaKey : event.ctrlKey) && base != "Ctrl") {
            name = "Ctrl-"+name;
        }
        if ((flipCtrlCmd ? event.ctrlKey : event.metaKey) && base != "Mod") {
            name = "Cmd-"+name;
        }
        if (!noShift && event.shiftKey && base != "Shift") {
            name = "Shift-"+name;
        }
        return name
    }

    // Look up the name of a key as indicated by an event object.
    function keyName(event, noShift) {
        if (presto && event.keyCode == 34 && event["char"]) {
            return false
        }
        var name = keyNames[event.keyCode];
        if (name == null || event.altGraphKey) {
            return false
        }
        // Ctrl-ScrollLock has keyCode 3, same as Ctrl-Pause,
        // so we'll use event.code when available (Chrome 48+, FF 38+, Safari 10.1+)
        if (event.keyCode == 3 && event.code) {
            name = event.code;
        }
        return addModifierNames(name, event, noShift)
    }

    function getKeyMap(val) {
        return typeof val == "string" ? keyMap[val] : val
    }

    // Helper for deleting text near the selection(s), used to implement
    // backspace, delete, and similar functionality.
    function deleteNearSelection(cm, compute) {
        var ranges = cm.doc.sel.ranges, kill = [];
        // Build up a set of ranges to kill first, merging overlapping
        // ranges.
        for (var i = 0; i < ranges.length; i++) {
            var toKill = compute(ranges[i]);
            while (kill.length && cmp(toKill.from, lst(kill).to) <= 0) {
                var replaced = kill.pop();
                if (cmp(replaced.from, toKill.from) < 0) {
                    toKill.from = replaced.from;
                    break
                }
            }
            kill.push(toKill);
        }
        // Next, remove those actual ranges.
        runInOp(cm, function () {
            for (var i = kill.length-1; i >= 0; i--) {
                replaceRange(cm.doc, "", kill[i].from, kill[i].to, "+delete");
            }
            ensureCursorVisible(cm);
        });
    }

    function moveCharLogically(line, ch, dir) {
        var target = skipExtendingChars(line.text, ch+dir, dir);
        return target < 0 || target > line.text.length ? null : target
    }

    function moveLogically(line, start, dir) {
        var ch = moveCharLogically(line, start.ch, dir);
        return ch == null ? null : new Pos(start.line, ch, dir < 0 ? "after" : "before")
    }

    function endOfLine(visually, cm, lineObj, lineNo, dir) {
        if (visually) {
            if (cm.doc.direction == "rtl") {
                dir = -dir;
            }
            var order = getOrder(lineObj, cm.doc.direction);
            if (order) {
                var part = dir < 0 ? lst(order) : order[0];
                var moveInStorageOrder = (dir < 0) == (part.level == 1);
                var sticky = moveInStorageOrder ? "after" : "before";
                var ch;
                // With a wrapped rtl chunk (possibly spanning multiple bidi parts),
                // it could be that the last bidi part is not on the last visual line,
                // since visual lines contain content order-consecutive chunks.
                // Thus, in rtl, we are looking for the first (content-order) character
                // in the rtl chunk that is on the last line (that is, the same line
                // as the last (content-order) character).
                if (part.level > 0 || cm.doc.direction == "rtl") {
                    var prep = prepareMeasureForLine(cm, lineObj);
                    ch = dir < 0 ? lineObj.text.length-1 : 0;
                    var targetTop = measureCharPrepared(cm, prep, ch).top;
                    ch = findFirst(function (ch) {
                        return measureCharPrepared(cm, prep, ch).top == targetTop;
                    }, (dir < 0) == (part.level == 1) ? part.from : part.to-1, ch);
                    if (sticky == "before") {
                        ch = moveCharLogically(lineObj, ch, 1);
                    }
                } else {
                    ch = dir < 0 ? part.to : part.from;
                }
                return new Pos(lineNo, ch, sticky)
            }
        }
        return new Pos(lineNo, dir < 0 ? lineObj.text.length : 0, dir < 0 ? "before" : "after")
    }

    function moveVisually(cm, line, start, dir) {
        var bidi = getOrder(line, cm.doc.direction);
        if (!bidi) {
            return moveLogically(line, start, dir)
        }
        if (start.ch >= line.text.length) {
            start.ch = line.text.length;
            start.sticky = "before";
        } else if (start.ch <= 0) {
            start.ch = 0;
            start.sticky = "after";
        }
        var partPos = getBidiPartAt(bidi, start.ch, start.sticky), part = bidi[partPos];
        if (cm.doc.direction == "ltr" && part.level % 2 == 0 && (dir > 0 ? part.to > start.ch : part.from < start.ch)) {
            // Case 1: We move within an ltr part in an ltr editor. Even with wrapped lines,
            // nothing interesting happens.
            return moveLogically(line, start, dir)
        }

        var mv = function (pos, dir) {
            return moveCharLogically(line, pos instanceof Pos ? pos.ch : pos, dir);
        };
        var prep;
        var getWrappedLineExtent = function (ch) {
            if (!cm.options.lineWrapping) {
                return {begin: 0, end: line.text.length}
            }
            prep = prep || prepareMeasureForLine(cm, line);
            return wrappedLineExtentChar(cm, line, prep, ch)
        };
        var wrappedLineExtent = getWrappedLineExtent(start.sticky == "before" ? mv(start, -1) : start.ch);

        if (cm.doc.direction == "rtl" || part.level == 1) {
            var moveInStorageOrder = (part.level == 1) == (dir < 0);
            var ch = mv(start, moveInStorageOrder ? 1 : -1);
            if (ch != null && (!moveInStorageOrder ? ch >= part.from && ch >= wrappedLineExtent.begin : ch <= part.to && ch <= wrappedLineExtent.end)) {
                // Case 2: We move within an rtl part or in an rtl editor on the same visual line
                var sticky = moveInStorageOrder ? "before" : "after";
                return new Pos(start.line, ch, sticky)
            }
        }

        // Case 3: Could not move within this bidi part in this visual line, so leave
        // the current bidi part

        var searchInVisualLine = function (partPos, dir, wrappedLineExtent) {
            var getRes = function (ch, moveInStorageOrder) {
                return moveInStorageOrder
                    ? new Pos(start.line, mv(ch, 1), "before")
                    : new Pos(start.line, ch, "after");
            };

            for (; partPos >= 0 && partPos < bidi.length; partPos += dir) {
                var part = bidi[partPos];
                var moveInStorageOrder = (dir > 0) == (part.level != 1);
                var ch = moveInStorageOrder ? wrappedLineExtent.begin : mv(wrappedLineExtent.end, -1);
                if (part.from <= ch && ch < part.to) {
                    return getRes(ch, moveInStorageOrder)
                }
                ch = moveInStorageOrder ? part.from : mv(part.to, -1);
                if (wrappedLineExtent.begin <= ch && ch < wrappedLineExtent.end) {
                    return getRes(ch, moveInStorageOrder)
                }
            }
        };

        // Case 3a: Look for other bidi parts on the same visual line
        var res = searchInVisualLine(partPos+dir, dir, wrappedLineExtent);
        if (res) {
            return res
        }

        // Case 3b: Look for other bidi parts on the next visual line
        var nextCh = dir > 0 ? wrappedLineExtent.end : mv(wrappedLineExtent.begin, -1);
        if (nextCh != null && !(dir > 0 && nextCh == line.text.length)) {
            res = searchInVisualLine(dir > 0 ? 0 : bidi.length-1, dir, getWrappedLineExtent(nextCh));
            if (res) {
                return res
            }
        }

        // Case 4: Nowhere to move
        return null
    }

    // Commands are parameter-less actions that can be performed on an
    // editor, mostly used for keybindings.
    var commands = {
        selectAll: selectAll,
        singleSelection: function (cm) {
            return cm.setSelection(cm.getCursor("anchor"), cm.getCursor("head"), sel_dontScroll);
        },
        killLine: function (cm) {
            return deleteNearSelection(cm, function (range) {
                if (range.empty()) {
                    var len = getLine(cm.doc, range.head.line).text.length;
                    if (range.head.ch == len && range.head.line < cm.lastLine()) {
                        return {from: range.head, to: Pos(range.head.line+1, 0)}
                    } else {
                        return {from: range.head, to: Pos(range.head.line, len)}
                    }
                } else {
                    return {from: range.from(), to: range.to()}
                }
            });
        },
        deleteLine: function (cm) {
            return deleteNearSelection(cm, function (range) {
                return ({
                    from: Pos(range.from().line, 0),
                    to: clipPos(cm.doc, Pos(range.to().line+1, 0))
                });
            });
        },
        delLineLeft: function (cm) {
            return deleteNearSelection(cm, function (range) {
                return ({
                    from: Pos(range.from().line, 0), to: range.from()
                });
            });
        },
        delWrappedLineLeft: function (cm) {
            return deleteNearSelection(cm, function (range) {
                var top = cm.charCoords(range.head, "div").top+5;
                var leftPos = cm.coordsChar({left: 0, top: top}, "div");
                return {from: leftPos, to: range.from()}
            });
        },
        delWrappedLineRight: function (cm) {
            return deleteNearSelection(cm, function (range) {
                var top = cm.charCoords(range.head, "div").top+5;
                var rightPos = cm.coordsChar({left: cm.display.lineDiv.offsetWidth+100, top: top}, "div");
                return {from: range.from(), to: rightPos}
            });
        },
        undo: function (cm) {
            return cm.undo();
        },
        redo: function (cm) {
            return cm.redo();
        },
        undoSelection: function (cm) {
            return cm.undoSelection();
        },
        redoSelection: function (cm) {
            return cm.redoSelection();
        },
        goDocStart: function (cm) {
            return cm.extendSelection(Pos(cm.firstLine(), 0));
        },
        goDocEnd: function (cm) {
            return cm.extendSelection(Pos(cm.lastLine()));
        },
        goLineStart: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                    return lineStart(cm, range.head.line);
                },
                {origin: "+move", bias: 1}
            );
        },
        goLineStartSmart: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                    return lineStartSmart(cm, range.head);
                },
                {origin: "+move", bias: 1}
            );
        },
        goLineEnd: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                    return lineEnd(cm, range.head.line);
                },
                {origin: "+move", bias: -1}
            );
        },
        goLineRight: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                var top = cm.cursorCoords(range.head, "div").top+5;
                return cm.coordsChar({left: cm.display.lineDiv.offsetWidth+100, top: top}, "div")
            }, sel_move);
        },
        goLineLeft: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                var top = cm.cursorCoords(range.head, "div").top+5;
                return cm.coordsChar({left: 0, top: top}, "div")
            }, sel_move);
        },
        goLineLeftSmart: function (cm) {
            return cm.extendSelectionsBy(function (range) {
                var top = cm.cursorCoords(range.head, "div").top+5;
                var pos = cm.coordsChar({left: 0, top: top}, "div");
                if (pos.ch < cm.getLine(pos.line).search(/\S/)) {
                    return lineStartSmart(cm, range.head)
                }
                return pos
            }, sel_move);
        },
        goLineUp: function (cm) {
            return cm.moveV(-1, "line");
        },
        goLineDown: function (cm) {
            return cm.moveV(1, "line");
        },
        goPageUp: function (cm) {
            return cm.moveV(-1, "page");
        },
        goPageDown: function (cm) {
            return cm.moveV(1, "page");
        },
        goCharLeft: function (cm) {
            return cm.moveH(-1, "char");
        },
        goCharRight: function (cm) {
            return cm.moveH(1, "char");
        },
        goColumnLeft: function (cm) {
            return cm.moveH(-1, "column");
        },
        goColumnRight: function (cm) {
            return cm.moveH(1, "column");
        },
        goWordLeft: function (cm) {
            return cm.moveH(-1, "word");
        },
        goGroupRight: function (cm) {
            return cm.moveH(1, "group");
        },
        goGroupLeft: function (cm) {
            return cm.moveH(-1, "group");
        },
        goWordRight: function (cm) {
            return cm.moveH(1, "word");
        },
        delCharBefore: function (cm) {
            return cm.deleteH(-1, "codepoint");
        },
        delCharAfter: function (cm) {
            return cm.deleteH(1, "char");
        },
        delWordBefore: function (cm) {
            return cm.deleteH(-1, "word");
        },
        delWordAfter: function (cm) {
            return cm.deleteH(1, "word");
        },
        delGroupBefore: function (cm) {
            return cm.deleteH(-1, "group");
        },
        delGroupAfter: function (cm) {
            return cm.deleteH(1, "group");
        },
        indentAuto: function (cm) {
            return cm.indentSelection("smart");
        },
        indentMore: function (cm) {
            return cm.indentSelection("add");
        },
        indentLess: function (cm) {
            return cm.indentSelection("subtract");
        },
        insertTab: function (cm) {
            return cm.replaceSelection("\t");
        },
        insertSoftTab: function (cm) {
            var spaces = [], ranges = cm.listSelections(), tabSize = cm.options.tabSize;
            for (var i = 0; i < ranges.length; i++) {
                var pos = ranges[i].from();
                var col = countColumn(cm.getLine(pos.line), pos.ch, tabSize);
                spaces.push(spaceStr(tabSize-col % tabSize));
            }
            cm.replaceSelections(spaces);
        },
        defaultTab: function (cm) {
            if (cm.somethingSelected()) {
                cm.indentSelection("add");
            } else {
                cm.execCommand("insertTab");
            }
        },
        // Swap the two chars left and right of each selection's head.
        // Move cursor behind the two swapped characters afterwards.
        //
        // Doesn't consider line feeds a character.
        // Doesn't scan more than one line above to find a character.
        // Doesn't do anything on an empty line.
        // Doesn't do anything with non-empty selections.
        transposeChars: function (cm) {
            return runInOp(cm, function () {
                var ranges = cm.listSelections(), newSel = [];
                for (var i = 0; i < ranges.length; i++) {
                    if (!ranges[i].empty()) {
                        continue
                    }
                    var cur = ranges[i].head, line = getLine(cm.doc, cur.line).text;
                    if (line) {
                        if (cur.ch == line.length) {
                            cur = new Pos(cur.line, cur.ch-1);
                        }
                        if (cur.ch > 0) {
                            cur = new Pos(cur.line, cur.ch+1);
                            cm.replaceRange(line.charAt(cur.ch-1)+line.charAt(cur.ch-2),
                                Pos(cur.line, cur.ch-2), cur, "+transpose");
                        } else if (cur.line > cm.doc.first) {
                            var prev = getLine(cm.doc, cur.line-1).text;
                            if (prev) {
                                cur = new Pos(cur.line, 1);
                                cm.replaceRange(line.charAt(0)+cm.doc.lineSeparator()+
                                    prev.charAt(prev.length-1),
                                    Pos(cur.line-1, prev.length-1), cur, "+transpose");
                            }
                        }
                    }
                    newSel.push(new Range(cur, cur));
                }
                cm.setSelections(newSel);
            });
        },
        newlineAndIndent: function (cm) {
            return runInOp(cm, function () {
                var sels = cm.listSelections();
                for (var i = sels.length-1; i >= 0; i--) {
                    cm.replaceRange(cm.doc.lineSeparator(), sels[i].anchor, sels[i].head, "+input");
                }
                sels = cm.listSelections();
                for (var i$1 = 0; i$1 < sels.length; i$1++) {
                    cm.indentLine(sels[i$1].from().line, null, true);
                }
                ensureCursorVisible(cm);
            });
        },
        openLine: function (cm) {
            return cm.replaceSelection("\n", "start");
        },
        toggleOverwrite: function (cm) {
            return cm.toggleOverwrite();
        }
    };


    function lineStart(cm, lineN) {
        var line = getLine(cm.doc, lineN);
        var visual = visualLine(line);
        if (visual != line) {
            lineN = lineNo(visual);
        }
        return endOfLine(true, cm, visual, lineN, 1)
    }

    function lineEnd(cm, lineN) {
        var line = getLine(cm.doc, lineN);
        var visual = visualLineEnd(line);
        if (visual != line) {
            lineN = lineNo(visual);
        }
        return endOfLine(true, cm, line, lineN, -1)
    }

    function lineStartSmart(cm, pos) {
        var start = lineStart(cm, pos.line);
        var line = getLine(cm.doc, start.line);
        var order = getOrder(line, cm.doc.direction);
        if (!order || order[0].level == 0) {
            var firstNonWS = Math.max(start.ch, line.text.search(/\S/));
            var inWS = pos.line == start.line && pos.ch <= firstNonWS && pos.ch;
            return Pos(start.line, inWS ? 0 : firstNonWS, start.sticky)
        }
        return start
    }

    // Run a handler that was bound to a key.
    function doHandleBinding(cm, bound, dropShift) {
        if (typeof bound == "string") {
            bound = commands[bound];
            if (!bound) {
                return false
            }
        }
        // Ensure previous input has been read, so that the handler sees a
        // consistent view of the document
        cm.display.input.ensurePolled();
        var prevShift = cm.display.shift, done = false;
        try {
            if (cm.isReadOnly()) {
                cm.state.suppressEdits = true;
            }
            if (dropShift) {
                cm.display.shift = false;
            }
            done = bound(cm) != Pass;
        } finally {
            cm.display.shift = prevShift;
            cm.state.suppressEdits = false;
        }
        return done
    }

    function lookupKeyForEditor(cm, name, handle) {
        for (var i = 0; i < cm.state.keyMaps.length; i++) {
            var result = lookupKey(name, cm.state.keyMaps[i], handle, cm);
            if (result) {
                return result
            }
        }
        return (cm.options.extraKeys && lookupKey(name, cm.options.extraKeys, handle, cm))
            || lookupKey(name, cm.options.keyMap, handle, cm)
    }

    // Note that, despite the name, this function is also used to check
    // for bound mouse clicks.

    var stopSeq = new Delayed;

    function dispatchKey(cm, name, e, handle) {
        var seq = cm.state.keySeq;
        if (seq) {
            if (isModifierKey(name)) {
                return "handled"
            }
            if (/\'$/.test(name)) {
                cm.state.keySeq = null;
            } else {
                stopSeq.set(50, function () {
                    if (cm.state.keySeq == seq) {
                        cm.state.keySeq = null;
                        cm.display.input.reset();
                    }
                });
            }
            if (dispatchKeyInner(cm, seq+" "+name, e, handle)) {
                return true
            }
        }
        return dispatchKeyInner(cm, name, e, handle)
    }

    function dispatchKeyInner(cm, name, e, handle) {
        var result = lookupKeyForEditor(cm, name, handle);

        if (result == "multi") {
            cm.state.keySeq = name;
        }
        if (result == "handled") {
            signalLater(cm, "keyHandled", cm, name, e);
        }

        if (result == "handled" || result == "multi") {
            e_preventDefault(e);
            restartBlink(cm);
        }

        return !!result
    }

    // Handle a key from the keydown event.
    function handleKeyBinding(cm, e) {
        var name = keyName(e, true);
        if (!name) {
            return false
        }

        if (e.shiftKey && !cm.state.keySeq) {
            // First try to resolve full name (including 'Shift-'). Failing
            // that, see if there is a cursor-motion command (starting with
            // 'go') bound to the keyname without 'Shift-'.
            return dispatchKey(cm, "Shift-"+name, e, function (b) {
                    return doHandleBinding(cm, b, true);
                })
                || dispatchKey(cm, name, e, function (b) {
                    if (typeof b == "string" ? /^go[A-Z]/.test(b) : b.motion) {
                        return doHandleBinding(cm, b)
                    }
                })
        } else {
            return dispatchKey(cm, name, e, function (b) {
                return doHandleBinding(cm, b);
            })
        }
    }

    // Handle a key from the keypress event
    function handleCharBinding(cm, e, ch) {
        return dispatchKey(cm, "'"+ch+"'", e, function (b) {
            return doHandleBinding(cm, b, true);
        })
    }

    var lastStoppedKey = null;

    function onKeyDown(e) {
        var cm = this;
        if (e.target && e.target != cm.display.input.getField()) {
            return
        }
        cm.curOp.focus = activeElt();
        if (signalDOMEvent(cm, e)) {
            return
        }
        // IE does strange things with escape.
        if (ie && ie_version < 11 && e.keyCode == 27) {
            e.returnValue = false;
        }
        var code = e.keyCode;
        cm.display.shift = code == 16 || e.shiftKey;
        var handled = handleKeyBinding(cm, e);
        if (presto) {
            lastStoppedKey = handled ? code : null;
            // Opera has no cut event... we try to at least catch the key combo
            if (!handled && code == 88 && !hasCopyEvent && (mac ? e.metaKey : e.ctrlKey)) {
                cm.replaceSelection("", null, "cut");
            }
        }
        if (gecko && !mac && !handled && code == 46 && e.shiftKey && !e.ctrlKey && document.execCommand) {
            document.execCommand("cut");
        }

        // Turn mouse into crosshair when Alt is held on Mac.
        if (code == 18 && !/\bCodeMirror-crosshair\b/.test(cm.display.lineDiv.className)) {
            showCrossHair(cm);
        }
    }

    function showCrossHair(cm) {
        var lineDiv = cm.display.lineDiv;
        addClass(lineDiv, "CodeMirror-crosshair");

        function up(e) {
            if (e.keyCode == 18 || !e.altKey) {
                rmClass(lineDiv, "CodeMirror-crosshair");
                off(document, "keyup", up);
                off(document, "mouseover", up);
            }
        }

        on(document, "keyup", up);
        on(document, "mouseover", up);
    }

    function onKeyUp(e) {
        if (e.keyCode == 16) {
            this.doc.sel.shift = false;
        }
        signalDOMEvent(this, e);
    }

    function onKeyPress(e) {
        var cm = this;
        if (e.target && e.target != cm.display.input.getField()) {
            return
        }
        if (eventInWidget(cm.display, e) || signalDOMEvent(cm, e) || e.ctrlKey && !e.altKey || mac && e.metaKey) {
            return
        }
        var keyCode = e.keyCode, charCode = e.charCode;
        if (presto && keyCode == lastStoppedKey) {
            lastStoppedKey = null;
            e_preventDefault(e);
            return
        }
        if ((presto && (!e.which || e.which < 10)) && handleKeyBinding(cm, e)) {
            return
        }
        var ch = String.fromCharCode(charCode == null ? keyCode : charCode);
        // Some browsers fire keypress events for backspace
        if (ch == "\x08") {
            return
        }
        if (handleCharBinding(cm, e, ch)) {
            return
        }
        cm.display.input.onKeyPress(e);
    }

    var DOUBLECLICK_DELAY = 400;

    var PastClick = function (time, pos, button) {
        this.time = time;
        this.pos = pos;
        this.button = button;
    };

    PastClick.prototype.compare = function (time, pos, button) {
        return this.time+DOUBLECLICK_DELAY > time &&
            cmp(pos, this.pos) == 0 && button == this.button
    };

    var lastClick, lastDoubleClick;

    function clickRepeat(pos, button) {
        var now = +new Date;
        if (lastDoubleClick && lastDoubleClick.compare(now, pos, button)) {
            lastClick = lastDoubleClick = null;
            return "triple"
        } else if (lastClick && lastClick.compare(now, pos, button)) {
            lastDoubleClick = new PastClick(now, pos, button);
            lastClick = null;
            return "double"
        } else {
            lastClick = new PastClick(now, pos, button);
            lastDoubleClick = null;
            return "single"
        }
    }

    // A mouse down can be a single click, double click, triple click,
    // start of selection drag, start of text drag, new cursor
    // (ctrl-click), rectangle drag (alt-drag), or xwin
    // middle-click-paste. Or it might be a click on something we should
    // not interfere with, such as a scrollbar or widget.
    function onMouseDown(e) {
        var cm = this, display = cm.display;
        if (signalDOMEvent(cm, e) || display.activeTouch && display.input.supportsTouch()) {
            return
        }
        display.input.ensurePolled();
        display.shift = e.shiftKey;

        if (eventInWidget(display, e)) {
            if (!webkit) {
                // Briefly turn off draggability, to allow widgets to do
                // normal dragging things.
                display.scroller.draggable = false;
                setTimeout(function () {
                    return display.scroller.draggable = true;
                }, 100);
            }
            return
        }
        if (clickInGutter(cm, e)) {
            return
        }
        var pos = posFromMouse(cm, e), button = e_button(e), repeat = pos ? clickRepeat(pos, button) : "single";
        window.focus();

        // #3261: make sure, that we're not starting a second selection
        if (button == 1 && cm.state.selectingText) {
            cm.state.selectingText(e);
        }

        if (pos && handleMappedButton(cm, button, pos, repeat, e)) {
            return
        }

        if (button == 1) {
            if (pos) {
                leftButtonDown(cm, pos, repeat, e);
            } else if (e_target(e) == display.scroller) {
                e_preventDefault(e);
            }
        } else if (button == 2) {
            if (pos) {
                extendSelection(cm.doc, pos);
            }
            setTimeout(function () {
                return display.input.focus();
            }, 20);
        } else if (button == 3) {
            if (captureRightClick) {
                cm.display.input.onContextMenu(e);
            } else {
                delayBlurEvent(cm);
            }
        }
    }

    function handleMappedButton(cm, button, pos, repeat, event) {
        var name = "Click";
        if (repeat == "double") {
            name = "Double"+name;
        } else if (repeat == "triple") {
            name = "Triple"+name;
        }
        name = (button == 1 ? "Left" : button == 2 ? "Middle" : "Right")+name;

        return dispatchKey(cm, addModifierNames(name, event), event, function (bound) {
            if (typeof bound == "string") {
                bound = commands[bound];
            }
            if (!bound) {
                return false
            }
            var done = false;
            try {
                if (cm.isReadOnly()) {
                    cm.state.suppressEdits = true;
                }
                done = bound(cm, pos) != Pass;
            } finally {
                cm.state.suppressEdits = false;
            }
            return done
        })
    }

    function configureMouse(cm, repeat, event) {
        var option = cm.getOption("configureMouse");
        var value = option ? option(cm, repeat, event) : {};
        if (value.unit == null) {
            var rect = chromeOS ? event.shiftKey && event.metaKey : event.altKey;
            value.unit = rect ? "rectangle" : repeat == "single" ? "char" : repeat == "double" ? "word" : "line";
        }
        if (value.extend == null || cm.doc.extend) {
            value.extend = cm.doc.extend || event.shiftKey;
        }
        if (value.addNew == null) {
            value.addNew = mac ? event.metaKey : event.ctrlKey;
        }
        if (value.moveOnDrag == null) {
            value.moveOnDrag = !(mac ? event.altKey : event.ctrlKey);
        }
        return value
    }

    function leftButtonDown(cm, pos, repeat, event) {
        if (ie) {
            setTimeout(bind(ensureFocus, cm), 0);
        } else {
            cm.curOp.focus = activeElt();
        }

        var behavior = configureMouse(cm, repeat, event);

        var sel = cm.doc.sel, contained;
        if (cm.options.dragDrop && dragAndDrop && !cm.isReadOnly() &&
            repeat == "single" && (contained = sel.contains(pos)) > -1 &&
            (cmp((contained = sel.ranges[contained]).from(), pos) < 0 || pos.xRel > 0) &&
            (cmp(contained.to(), pos) > 0 || pos.xRel < 0)) {
            leftButtonStartDrag(cm, event, pos, behavior);
        } else {
            leftButtonSelect(cm, event, pos, behavior);
        }
    }

    // Start a text drag. When it ends, see if any dragging actually
    // happen, and treat as a click if it didn't.
    function leftButtonStartDrag(cm, event, pos, behavior) {
        var display = cm.display, moved = false;
        var dragEnd = operation(cm, function (e) {
            if (webkit) {
                display.scroller.draggable = false;
            }
            cm.state.draggingText = false;
            if (cm.state.delayingBlurEvent) {
                if (cm.hasFocus()) {
                    cm.state.delayingBlurEvent = false;
                } else {
                    delayBlurEvent(cm);
                }
            }
            off(display.wrapper.ownerDocument, "mouseup", dragEnd);
            off(display.wrapper.ownerDocument, "mousemove", mouseMove);
            off(display.scroller, "dragstart", dragStart);
            off(display.scroller, "drop", dragEnd);
            if (!moved) {
                e_preventDefault(e);
                if (!behavior.addNew) {
                    extendSelection(cm.doc, pos, null, null, behavior.extend);
                }
                // Work around unexplainable focus problem in IE9 (#2127) and Chrome (#3081)
                if ((webkit && !safari) || ie && ie_version == 9) {
                    setTimeout(function () {
                        display.wrapper.ownerDocument.body.focus({preventScroll: true});
                        display.input.focus();
                    }, 20);
                } else {
                    display.input.focus();
                }
            }
        });
        var mouseMove = function (e2) {
            moved = moved || Math.abs(event.clientX-e2.clientX)+Math.abs(event.clientY-e2.clientY) >= 10;
        };
        var dragStart = function () {
            return moved = true;
        };
        // Let the drag handler handle this.
        if (webkit) {
            display.scroller.draggable = true;
        }
        cm.state.draggingText = dragEnd;
        dragEnd.copy = !behavior.moveOnDrag;
        on(display.wrapper.ownerDocument, "mouseup", dragEnd);
        on(display.wrapper.ownerDocument, "mousemove", mouseMove);
        on(display.scroller, "dragstart", dragStart);
        on(display.scroller, "drop", dragEnd);

        cm.state.delayingBlurEvent = true;
        setTimeout(function () {
            return display.input.focus();
        }, 20);
        // IE's approach to draggable
        if (display.scroller.dragDrop) {
            display.scroller.dragDrop();
        }
    }

    function rangeForUnit(cm, pos, unit) {
        if (unit == "char") {
            return new Range(pos, pos)
        }
        if (unit == "word") {
            return cm.findWordAt(pos)
        }
        if (unit == "line") {
            return new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line+1, 0)))
        }
        var result = unit(cm, pos);
        return new Range(result.from, result.to)
    }

    // Normal selection, as opposed to text dragging.
    function leftButtonSelect(cm, event, start, behavior) {
        if (ie) {
            delayBlurEvent(cm);
        }
        var display = cm.display, doc = cm.doc;
        e_preventDefault(event);

        var ourRange, ourIndex, startSel = doc.sel, ranges = startSel.ranges;
        if (behavior.addNew && !behavior.extend) {
            ourIndex = doc.sel.contains(start);
            if (ourIndex > -1) {
                ourRange = ranges[ourIndex];
            } else {
                ourRange = new Range(start, start);
            }
        } else {
            ourRange = doc.sel.primary();
            ourIndex = doc.sel.primIndex;
        }

        if (behavior.unit == "rectangle") {
            if (!behavior.addNew) {
                ourRange = new Range(start, start);
            }
            start = posFromMouse(cm, event, true, true);
            ourIndex = -1;
        } else {
            var range = rangeForUnit(cm, start, behavior.unit);
            if (behavior.extend) {
                ourRange = extendRange(ourRange, range.anchor, range.head, behavior.extend);
            } else {
                ourRange = range;
            }
        }

        if (!behavior.addNew) {
            ourIndex = 0;
            setSelection(doc, new Selection([ourRange], 0), sel_mouse);
            startSel = doc.sel;
        } else if (ourIndex == -1) {
            ourIndex = ranges.length;
            setSelection(doc, normalizeSelection(cm, ranges.concat([ourRange]), ourIndex),
                {scroll: false, origin: "*mouse"});
        } else if (ranges.length > 1 && ranges[ourIndex].empty() && behavior.unit == "char" && !behavior.extend) {
            setSelection(doc, normalizeSelection(cm, ranges.slice(0, ourIndex).concat(ranges.slice(ourIndex+1)), 0),
                {scroll: false, origin: "*mouse"});
            startSel = doc.sel;
        } else {
            replaceOneSelection(doc, ourIndex, ourRange, sel_mouse);
        }

        var lastPos = start;

        function extendTo(pos) {
            if (cmp(lastPos, pos) == 0) {
                return
            }
            lastPos = pos;

            if (behavior.unit == "rectangle") {
                var ranges = [], tabSize = cm.options.tabSize;
                var startCol = countColumn(getLine(doc, start.line).text, start.ch, tabSize);
                var posCol = countColumn(getLine(doc, pos.line).text, pos.ch, tabSize);
                var left = Math.min(startCol, posCol), right = Math.max(startCol, posCol);
                for (var line = Math.min(start.line, pos.line), end = Math.min(cm.lastLine(), Math.max(start.line, pos.line));
                     line <= end; line++) {
                    var text = getLine(doc, line).text, leftPos = findColumn(text, left, tabSize);
                    if (left == right) {
                        ranges.push(new Range(Pos(line, leftPos), Pos(line, leftPos)));
                    } else if (text.length > leftPos) {
                        ranges.push(new Range(Pos(line, leftPos), Pos(line, findColumn(text, right, tabSize))));
                    }
                }
                if (!ranges.length) {
                    ranges.push(new Range(start, start));
                }
                setSelection(doc, normalizeSelection(cm, startSel.ranges.slice(0, ourIndex).concat(ranges), ourIndex),
                    {origin: "*mouse", scroll: false});
                cm.scrollIntoView(pos);
            } else {
                var oldRange = ourRange;
                var range = rangeForUnit(cm, pos, behavior.unit);
                var anchor = oldRange.anchor, head;
                if (cmp(range.anchor, anchor) > 0) {
                    head = range.head;
                    anchor = minPos(oldRange.from(), range.anchor);
                } else {
                    head = range.anchor;
                    anchor = maxPos(oldRange.to(), range.head);
                }
                var ranges$1 = startSel.ranges.slice(0);
                ranges$1[ourIndex] = bidiSimplify(cm, new Range(clipPos(doc, anchor), head));
                setSelection(doc, normalizeSelection(cm, ranges$1, ourIndex), sel_mouse);
            }
        }

        var editorSize = display.wrapper.getBoundingClientRect();
        // Used to ensure timeout re-tries don't fire when another extend
        // happened in the meantime (clearTimeout isn't reliable -- at
        // least on Chrome, the timeouts still happen even when cleared,
        // if the clear happens after their scheduled firing time).
        var counter = 0;

        function extend(e) {
            var curCount = ++counter;
            var cur = posFromMouse(cm, e, true, behavior.unit == "rectangle");
            if (!cur) {
                return
            }
            if (cmp(cur, lastPos) != 0) {
                cm.curOp.focus = activeElt();
                extendTo(cur);
                var visible = visibleLines(display, doc);
                if (cur.line >= visible.to || cur.line < visible.from) {
                    setTimeout(operation(cm, function () {
                        if (counter == curCount) {
                            extend(e);
                        }
                    }), 150);
                }
            } else {
                var outside = e.clientY < editorSize.top ? -20 : e.clientY > editorSize.bottom ? 20 : 0;
                if (outside) {
                    setTimeout(operation(cm, function () {
                        if (counter != curCount) {
                            return
                        }
                        display.scroller.scrollTop += outside;
                        extend(e);
                    }), 50);
                }
            }
        }

        function done(e) {
            cm.state.selectingText = false;
            counter = Infinity;
            // If e is null or undefined we interpret this as someone trying
            // to explicitly cancel the selection rather than the user
            // letting go of the mouse button.
            if (e) {
                e_preventDefault(e);
                display.input.focus();
            }
            off(display.wrapper.ownerDocument, "mousemove", move);
            off(display.wrapper.ownerDocument, "mouseup", up);
            doc.history.lastSelOrigin = null;
        }

        var move = operation(cm, function (e) {
            if (e.buttons === 0 || !e_button(e)) {
                done(e);
            } else {
                extend(e);
            }
        });
        var up = operation(cm, done);
        cm.state.selectingText = up;
        on(display.wrapper.ownerDocument, "mousemove", move);
        on(display.wrapper.ownerDocument, "mouseup", up);
    }

    // Used when mouse-selecting to adjust the anchor to the proper side
    // of a bidi jump depending on the visual position of the head.
    function bidiSimplify(cm, range) {
        var anchor = range.anchor;
        var head = range.head;
        var anchorLine = getLine(cm.doc, anchor.line);
        if (cmp(anchor, head) == 0 && anchor.sticky == head.sticky) {
            return range
        }
        var order = getOrder(anchorLine);
        if (!order) {
            return range
        }
        var index = getBidiPartAt(order, anchor.ch, anchor.sticky), part = order[index];
        if (part.from != anchor.ch && part.to != anchor.ch) {
            return range
        }
        var boundary = index+((part.from == anchor.ch) == (part.level != 1) ? 0 : 1);
        if (boundary == 0 || boundary == order.length) {
            return range
        }

        // Compute the relative visual position of the head compared to the
        // anchor (<0 is to the left, >0 to the right)
        var leftSide;
        if (head.line != anchor.line) {
            leftSide = (head.line-anchor.line) * (cm.doc.direction == "ltr" ? 1 : -1) > 0;
        } else {
            var headIndex = getBidiPartAt(order, head.ch, head.sticky);
            var dir = headIndex-index || (head.ch-anchor.ch) * (part.level == 1 ? -1 : 1);
            if (headIndex == boundary-1 || headIndex == boundary) {
                leftSide = dir < 0;
            } else {
                leftSide = dir > 0;
            }
        }

        var usePart = order[boundary+(leftSide ? -1 : 0)];
        var from = leftSide == (usePart.level == 1);
        var ch = from ? usePart.from : usePart.to, sticky = from ? "after" : "before";
        return anchor.ch == ch && anchor.sticky == sticky ? range : new Range(new Pos(anchor.line, ch, sticky), head)
    }


    // Determines whether an event happened in the gutter, and fires the
    // handlers for the corresponding event.
    function gutterEvent(cm, e, type, prevent) {
        var mX, mY;
        if (e.touches) {
            mX = e.touches[0].clientX;
            mY = e.touches[0].clientY;
        } else {
            try {
                mX = e.clientX;
                mY = e.clientY;
            } catch (e$1) {
                return false
            }
        }
        if (mX >= Math.floor(cm.display.gutters.getBoundingClientRect().right)) {
            return false
        }
        if (prevent) {
            e_preventDefault(e);
        }

        var display = cm.display;
        var lineBox = display.lineDiv.getBoundingClientRect();

        if (mY > lineBox.bottom || !hasHandler(cm, type)) {
            return e_defaultPrevented(e)
        }
        mY -= lineBox.top-display.viewOffset;

        for (var i = 0; i < cm.display.gutterSpecs.length; ++i) {
            var g = display.gutters.childNodes[i];
            if (g && g.getBoundingClientRect().right >= mX) {
                var line = lineAtHeight(cm.doc, mY);
                var gutter = cm.display.gutterSpecs[i];
                signal(cm, type, cm, line, gutter.className, e);
                return e_defaultPrevented(e)
            }
        }
    }

    function clickInGutter(cm, e) {
        return gutterEvent(cm, e, "gutterClick", true)
    }

    // CONTEXT MENU HANDLING

    // To make the context menu work, we need to briefly unhide the
    // textarea (making it as unobtrusive as possible) to let the
    // right-click take effect on it.
    function onContextMenu(cm, e) {
        if (eventInWidget(cm.display, e) || contextMenuInGutter(cm, e)) {
            return
        }
        if (signalDOMEvent(cm, e, "contextmenu")) {
            return
        }
        if (!captureRightClick) {
            cm.display.input.onContextMenu(e);
        }
    }

    function contextMenuInGutter(cm, e) {
        if (!hasHandler(cm, "gutterContextMenu")) {
            return false
        }
        return gutterEvent(cm, e, "gutterContextMenu", false)
    }

    function themeChanged(cm) {
        cm.display.wrapper.className = cm.display.wrapper.className.replace(/\s*cm-s-\S+/g, "")+
            cm.options.theme.replace(/(^|\s)\s*/g, " cm-s-");
        clearCaches(cm);
    }

    var Init = {
        toString: function () {
            return "CodeMirror.Init"
        }
    };

    var defaults = {};
    var optionHandlers = {};

    function defineOptions(CodeMirror) {
        var optionHandlers = CodeMirror.optionHandlers;

        function option(name, deflt, handle, notOnInit) {
            CodeMirror.defaults[name] = deflt;
            if (handle) {
                optionHandlers[name] =
                    notOnInit ? function (cm, val, old) {
                        if (old != Init) {
                            handle(cm, val, old);
                        }
                    } : handle;
            }
        }

        CodeMirror.defineOption = option;

        // Passed to option handlers when there is no old value.
        CodeMirror.Init = Init;

        // These two are, on init, called from the constructor because they
        // have to be initialized before the editor can start at all.
        option("value", "", function (cm, val) {
            return cm.setValue(val);
        }, true);
        option("mode", null, function (cm, val) {
            cm.doc.modeOption = val;
            loadMode(cm);
        }, true);

        option("indentUnit", 2, loadMode, true);
        option("indentWithTabs", false);
        option("smartIndent", true);
        option("tabSize", 4, function (cm) {
            resetModeState(cm);
            clearCaches(cm);
            regChange(cm);
        }, true);

        option("lineSeparator", null, function (cm, val) {
            cm.doc.lineSep = val;
            if (!val) {
                return
            }
            var newBreaks = [], lineNo = cm.doc.first;
            cm.doc.iter(function (line) {
                for (var pos = 0; ;) {
                    var found = line.text.indexOf(val, pos);
                    if (found == -1) {
                        break
                    }
                    pos = found+val.length;
                    newBreaks.push(Pos(lineNo, found));
                }
                lineNo++;
            });
            for (var i = newBreaks.length-1; i >= 0; i--) {
                replaceRange(cm.doc, val, newBreaks[i], Pos(newBreaks[i].line, newBreaks[i].ch+val.length));
            }
        });
        option("specialChars", /[\u0000-\u001f\u007f-\u009f\u00ad\u061c\u200b\u200e\u200f\u2028\u2029\ufeff\ufff9-\ufffc]/g, function (cm, val, old) {
            cm.state.specialChars = new RegExp(val.source+(val.test("\t") ? "" : "|\t"), "g");
            if (old != Init) {
                cm.refresh();
            }
        });
        option("specialCharPlaceholder", defaultSpecialCharPlaceholder, function (cm) {
            return cm.refresh();
        }, true);
        option("electricChars", true);
        option("inputStyle", mobile ? "contenteditable" : "textarea", function () {
            throw new Error("inputStyle can not (yet) be changed in a running editor") // FIXME
        }, true);
        option("spellcheck", false, function (cm, val) {
            return cm.getInputField().spellcheck = val;
        }, true);
        option("autocorrect", false, function (cm, val) {
            return cm.getInputField().autocorrect = val;
        }, true);
        option("autocapitalize", false, function (cm, val) {
            return cm.getInputField().autocapitalize = val;
        }, true);
        option("rtlMoveVisually", !windows);
        option("wholeLineUpdateBefore", true);

        option("theme", "default", function (cm) {
            themeChanged(cm);
            updateGutters(cm);
        }, true);
        option("keyMap", "default", function (cm, val, old) {
            var next = getKeyMap(val);
            var prev = old != Init && getKeyMap(old);
            if (prev && prev.detach) {
                prev.detach(cm, next);
            }
            if (next.attach) {
                next.attach(cm, prev || null);
            }
        });
        option("extraKeys", null);
        option("configureMouse", null);

        option("lineWrapping", false, wrappingChanged, true);
        option("gutters", [], function (cm, val) {
            cm.display.gutterSpecs = getGutters(val, cm.options.lineNumbers);
            updateGutters(cm);
        }, true);
        option("fixedGutter", true, function (cm, val) {
            cm.display.gutters.style.left = val ? compensateForHScroll(cm.display)+"px" : "0";
            cm.refresh();
        }, true);
        option("coverGutterNextToScrollbar", false, function (cm) {
            return updateScrollbars(cm);
        }, true);
        option("scrollbarStyle", "native", function (cm) {
            initScrollbars(cm);
            updateScrollbars(cm);
            cm.display.scrollbars.setScrollTop(cm.doc.scrollTop);
            cm.display.scrollbars.setScrollLeft(cm.doc.scrollLeft);
        }, true);
        option("lineNumbers", false, function (cm, val) {
            cm.display.gutterSpecs = getGutters(cm.options.gutters, val);
            updateGutters(cm);
        }, true);
        option("firstLineNumber", 1, updateGutters, true);
        option("lineNumberFormatter", function (integer) {
            return integer;
        }, updateGutters, true);
        option("showCursorWhenSelecting", false, updateSelection, true);

        option("resetSelectionOnContextMenu", true);
        option("lineWiseCopyCut", true);
        option("pasteLinesPerSelection", true);
        option("selectionsMayTouch", false);

        option("readOnly", false, function (cm, val) {
            if (val == "nocursor") {
                onBlur(cm);
                cm.display.input.blur();
            }
            cm.display.input.readOnlyChanged(val);
        });

        option("screenReaderLabel", null, function (cm, val) {
            val = (val === '') ? null : val;
            cm.display.input.screenReaderLabelChanged(val);
        });

        option("disableInput", false, function (cm, val) {
            if (!val) {
                cm.display.input.reset();
            }
        }, true);
        option("dragDrop", true, dragDropChanged);
        option("allowDropFileTypes", null);

        option("cursorBlinkRate", 530);
        option("cursorScrollMargin", 0);
        option("cursorHeight", 1, updateSelection, true);
        option("singleCursorHeightPerLine", true, updateSelection, true);
        option("workTime", 100);
        option("workDelay", 100);
        option("flattenSpans", true, resetModeState, true);
        option("addModeClass", false, resetModeState, true);
        option("pollInterval", 100);
        option("undoDepth", 200, function (cm, val) {
            return cm.doc.history.undoDepth = val;
        });
        option("historyEventDelay", 1250);
        option("viewportMargin", 10, function (cm) {
            return cm.refresh();
        }, true);
        option("maxHighlightLength", 10000, resetModeState, true);
        option("moveInputWithCursor", true, function (cm, val) {
            if (!val) {
                cm.display.input.resetPosition();
            }
        });

        option("tabindex", null, function (cm, val) {
            return cm.display.input.getField().tabIndex = val || "";
        });
        option("autofocus", null);
        option("direction", "ltr", function (cm, val) {
            return cm.doc.setDirection(val);
        }, true);
        option("phrases", null);
    }

    function dragDropChanged(cm, value, old) {
        var wasOn = old && old != Init;
        if (!value != !wasOn) {
            var funcs = cm.display.dragFunctions;
            var toggle = value ? on : off;
            toggle(cm.display.scroller, "dragstart", funcs.start);
            toggle(cm.display.scroller, "dragenter", funcs.enter);
            toggle(cm.display.scroller, "dragover", funcs.over);
            toggle(cm.display.scroller, "dragleave", funcs.leave);
            toggle(cm.display.scroller, "drop", funcs.drop);
        }
    }

    function wrappingChanged(cm) {
        if (cm.options.lineWrapping) {
            addClass(cm.display.wrapper, "CodeMirror-wrap");
            cm.display.sizer.style.minWidth = "";
            cm.display.sizerWidth = null;
        } else {
            rmClass(cm.display.wrapper, "CodeMirror-wrap");
            findMaxLine(cm);
        }
        estimateLineHeights(cm);
        regChange(cm);
        clearCaches(cm);
        setTimeout(function () {
            return updateScrollbars(cm);
        }, 100);
    }

    // A CodeMirror instance represents an editor. This is the object
    // that user code is usually dealing with.

    function CodeMirror(place, options) {
        var this$1 = this;

        if (!(this instanceof CodeMirror)) {
            return new CodeMirror(place, options)
        }

        this.options = options = options ? copyObj(options) : {};
        // Determine effective options based on given values and defaults.
        copyObj(defaults, options, false);

        var doc = options.value;
        if (typeof doc == "string") {
            doc = new Doc(doc, options.mode, null, options.lineSeparator, options.direction);
        } else if (options.mode) {
            doc.modeOption = options.mode;
        }
        this.doc = doc;

        var input = new CodeMirror.inputStyles[options.inputStyle](this);
        var display = this.display = new Display(place, doc, input, options);
        display.wrapper.CodeMirror = this;
        themeChanged(this);
        if (options.lineWrapping) {
            this.display.wrapper.className += " CodeMirror-wrap";
        }
        initScrollbars(this);

        this.state = {
            keyMaps: [],  // stores maps added by addKeyMap
            overlays: [], // highlighting overlays, as added by addOverlay
            modeGen: 0,   // bumped when mode/overlay changes, used to invalidate highlighting info
            overwrite: false,
            delayingBlurEvent: false,
            focused: false,
            suppressEdits: false, // used to disable editing during key handlers when in readOnly mode
            pasteIncoming: -1, cutIncoming: -1, // help recognize paste/cut edits in input.poll
            selectingText: false,
            draggingText: false,
            highlight: new Delayed(), // stores highlight worker timeout
            keySeq: null,  // Unfinished key sequence
            specialChars: null
        };

        if (options.autofocus && !mobile) {
            display.input.focus();
        }

        // Override magic textarea content restore that IE sometimes does
        // on our hidden textarea on reload
        if (ie && ie_version < 11) {
            setTimeout(function () {
                return this$1.display.input.reset(true);
            }, 20);
        }

        registerEventHandlers(this);
        ensureGlobalHandlers();

        startOperation(this);
        this.curOp.forceUpdate = true;
        attachDoc(this, doc);

        if ((options.autofocus && !mobile) || this.hasFocus()) {
            setTimeout(function () {
                if (this$1.hasFocus() && !this$1.state.focused) {
                    onFocus(this$1);
                }
            }, 20);
        } else {
            onBlur(this);
        }

        for (var opt in optionHandlers) {
            if (optionHandlers.hasOwnProperty(opt)) {
                optionHandlers[opt](this, options[opt], Init);
            }
        }
        maybeUpdateLineNumberWidth(this);
        if (options.finishInit) {
            options.finishInit(this);
        }
        for (var i = 0; i < initHooks.length; ++i) {
            initHooks[i](this);
        }
        endOperation(this);
        // Suppress optimizelegibility in Webkit, since it breaks text
        // measuring on line wrapping boundaries.
        if (webkit && options.lineWrapping &&
            getComputedStyle(display.lineDiv).textRendering == "optimizelegibility") {
            display.lineDiv.style.textRendering = "auto";
        }
    }

    // The default configuration options.
    CodeMirror.defaults = defaults;
    // Functions to run when options are changed.
    CodeMirror.optionHandlers = optionHandlers;

    // Attach the necessary event handlers when initializing the editor
    function registerEventHandlers(cm) {
        var d = cm.display;
        on(d.scroller, "mousedown", operation(cm, onMouseDown));
        // Older IE's will not fire a second mousedown for a double click
        if (ie && ie_version < 11) {
            on(d.scroller, "dblclick", operation(cm, function (e) {
                if (signalDOMEvent(cm, e)) {
                    return
                }
                var pos = posFromMouse(cm, e);
                if (!pos || clickInGutter(cm, e) || eventInWidget(cm.display, e)) {
                    return
                }
                e_preventDefault(e);
                var word = cm.findWordAt(pos);
                extendSelection(cm.doc, word.anchor, word.head);
            }));
        } else {
            on(d.scroller, "dblclick", function (e) {
                return signalDOMEvent(cm, e) || e_preventDefault(e);
            });
        }
        // Some browsers fire contextmenu *after* opening the menu, at
        // which point we can't mess with it anymore. Context menu is
        // handled in onMouseDown for these browsers.
        on(d.scroller, "contextmenu", function (e) {
            return onContextMenu(cm, e);
        });
        on(d.input.getField(), "contextmenu", function (e) {
            if (!d.scroller.contains(e.target)) {
                onContextMenu(cm, e);
            }
        });

        // Used to suppress mouse event handling when a touch happens
        var touchFinished, prevTouch = {end: 0};

        function finishTouch() {
            if (d.activeTouch) {
                touchFinished = setTimeout(function () {
                    return d.activeTouch = null;
                }, 1000);
                prevTouch = d.activeTouch;
                prevTouch.end = +new Date;
            }
        }

        function isMouseLikeTouchEvent(e) {
            if (e.touches.length != 1) {
                return false
            }
            var touch = e.touches[0];
            return touch.radiusX <= 1 && touch.radiusY <= 1
        }

        function farAway(touch, other) {
            if (other.left == null) {
                return true
            }
            var dx = other.left-touch.left, dy = other.top-touch.top;
            return dx * dx+dy * dy > 20 * 20
        }

        on(d.scroller, "touchstart", function (e) {
            if (!signalDOMEvent(cm, e) && !isMouseLikeTouchEvent(e) && !clickInGutter(cm, e)) {
                d.input.ensurePolled();
                clearTimeout(touchFinished);
                var now = +new Date;
                d.activeTouch = {
                    start: now, moved: false,
                    prev: now-prevTouch.end <= 300 ? prevTouch : null
                };
                if (e.touches.length == 1) {
                    d.activeTouch.left = e.touches[0].pageX;
                    d.activeTouch.top = e.touches[0].pageY;
                }
            }
        });
        on(d.scroller, "touchmove", function () {
            if (d.activeTouch) {
                d.activeTouch.moved = true;
            }
        });
        on(d.scroller, "touchend", function (e) {
            var touch = d.activeTouch;
            if (touch && !eventInWidget(d, e) && touch.left != null &&
                !touch.moved && new Date-touch.start < 300) {
                var pos = cm.coordsChar(d.activeTouch, "page"), range;
                if (!touch.prev || farAway(touch, touch.prev)) // Single tap
                {
                    range = new Range(pos, pos);
                } else if (!touch.prev.prev || farAway(touch, touch.prev.prev)) // Double tap
                {
                    range = cm.findWordAt(pos);
                } else // Triple tap
                {
                    range = new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line+1, 0)));
                }
                cm.setSelection(range.anchor, range.head);
                cm.focus();
                e_preventDefault(e);
            }
            finishTouch();
        });
        on(d.scroller, "touchcancel", finishTouch);

        // Sync scrolling between fake scrollbars and real scrollable
        // area, ensure viewport is updated when scrolling.
        on(d.scroller, "scroll", function () {
            if (d.scroller.clientHeight) {
                updateScrollTop(cm, d.scroller.scrollTop);
                setScrollLeft(cm, d.scroller.scrollLeft, true);
                signal(cm, "scroll", cm);
            }
        });

        // Listen to wheel events in order to try and update the viewport on time.
        on(d.scroller, "mousewheel", function (e) {
            return onScrollWheel(cm, e);
        });
        on(d.scroller, "DOMMouseScroll", function (e) {
            return onScrollWheel(cm, e);
        });

        // Prevent wrapper from ever scrolling
        on(d.wrapper, "scroll", function () {
            return d.wrapper.scrollTop = d.wrapper.scrollLeft = 0;
        });

        d.dragFunctions = {
            enter: function (e) {
                if (!signalDOMEvent(cm, e)) {
                    e_stop(e);
                }
            },
            over: function (e) {
                if (!signalDOMEvent(cm, e)) {
                    onDragOver(cm, e);
                    e_stop(e);
                }
            },
            start: function (e) {
                return onDragStart(cm, e);
            },
            drop: operation(cm, onDrop),
            leave: function (e) {
                if (!signalDOMEvent(cm, e)) {
                    clearDragCursor(cm);
                }
            }
        };

        var inp = d.input.getField();
        on(inp, "keyup", function (e) {
            return onKeyUp.call(cm, e);
        });
        on(inp, "keydown", operation(cm, onKeyDown));
        on(inp, "keypress", operation(cm, onKeyPress));
        on(inp, "focus", function (e) {
            return onFocus(cm, e);
        });
        on(inp, "blur", function (e) {
            return onBlur(cm, e);
        });
    }

    var initHooks = [];
    CodeMirror.defineInitHook = function (f) {
        return initHooks.push(f);
    };

    // Indent the given line. The how parameter can be "smart",
    // "add"/null, "subtract", or "prev". When aggressive is false
    // (typically set to true for forced single-line indents), empty
    // lines are not indented, and places where the mode returns Pass
    // are left alone.
    function indentLine(cm, n, how, aggressive) {
        var doc = cm.doc, state;
        if (how == null) {
            how = "add";
        }
        if (how == "smart") {
            // Fall back to "prev" when the mode doesn't have an indentation
            // method.
            if (!doc.mode.indent) {
                how = "prev";
            } else {
                state = getContextBefore(cm, n).state;
            }
        }

        var tabSize = cm.options.tabSize;
        var line = getLine(doc, n), curSpace = countColumn(line.text, null, tabSize);
        if (line.stateAfter) {
            line.stateAfter = null;
        }
        var curSpaceString = line.text.match(/^\s*/)[0], indentation;
        if (!aggressive && !/\S/.test(line.text)) {
            indentation = 0;
            how = "not";
        } else if (how == "smart") {
            indentation = doc.mode.indent(state, line.text.slice(curSpaceString.length), line.text);
            if (indentation == Pass || indentation > 150) {
                if (!aggressive) {
                    return
                }
                how = "prev";
            }
        }
        if (how == "prev") {
            if (n > doc.first) {
                indentation = countColumn(getLine(doc, n-1).text, null, tabSize);
            } else {
                indentation = 0;
            }
        } else if (how == "add") {
            indentation = curSpace+cm.options.indentUnit;
        } else if (how == "subtract") {
            indentation = curSpace-cm.options.indentUnit;
        } else if (typeof how == "number") {
            indentation = curSpace+how;
        }
        indentation = Math.max(0, indentation);

        var indentString = "", pos = 0;
        if (cm.options.indentWithTabs) {
            for (var i = Math.floor(indentation / tabSize); i; --i) {
                pos += tabSize;
                indentString += "\t";
            }
        }
        if (pos < indentation) {
            indentString += spaceStr(indentation-pos);
        }

        if (indentString != curSpaceString) {
            replaceRange(doc, indentString, Pos(n, 0), Pos(n, curSpaceString.length), "+input");
            line.stateAfter = null;
            return true
        } else {
            // Ensure that, if the cursor was in the whitespace at the start
            // of the line, it is moved to the end of that space.
            for (var i$1 = 0; i$1 < doc.sel.ranges.length; i$1++) {
                var range = doc.sel.ranges[i$1];
                if (range.head.line == n && range.head.ch < curSpaceString.length) {
                    var pos$1 = Pos(n, curSpaceString.length);
                    replaceOneSelection(doc, i$1, new Range(pos$1, pos$1));
                    break
                }
            }
        }
    }

    // This will be set to a {lineWise: bool, text: [string]} object, so
    // that, when pasting, we know what kind of selections the copied
    // text was made out of.
    var lastCopied = null;

    function setLastCopied(newLastCopied) {
        lastCopied = newLastCopied;
    }

    function applyTextInput(cm, inserted, deleted, sel, origin) {
        var doc = cm.doc;
        cm.display.shift = false;
        if (!sel) {
            sel = doc.sel;
        }

        var recent = +new Date-200;
        var paste = origin == "paste" || cm.state.pasteIncoming > recent;
        var textLines = splitLinesAuto(inserted), multiPaste = null;
        // When pasting N lines into N selections, insert one line per selection
        if (paste && sel.ranges.length > 1) {
            if (lastCopied && lastCopied.text.join("\n") == inserted) {
                if (sel.ranges.length % lastCopied.text.length == 0) {
                    multiPaste = [];
                    for (var i = 0; i < lastCopied.text.length; i++) {
                        multiPaste.push(doc.splitLines(lastCopied.text[i]));
                    }
                }
            } else if (textLines.length == sel.ranges.length && cm.options.pasteLinesPerSelection) {
                multiPaste = map(textLines, function (l) {
                    return [l];
                });
            }
        }

        var updateInput = cm.curOp.updateInput;
        // Normal behavior is to insert the new text into every selection
        for (var i$1 = sel.ranges.length-1; i$1 >= 0; i$1--) {
            var range = sel.ranges[i$1];
            var from = range.from(), to = range.to();
            if (range.empty()) {
                if (deleted && deleted > 0) // Handle deletion
                {
                    from = Pos(from.line, from.ch-deleted);
                } else if (cm.state.overwrite && !paste) // Handle overwrite
                {
                    to = Pos(to.line, Math.min(getLine(doc, to.line).text.length, to.ch+lst(textLines).length));
                } else if (paste && lastCopied && lastCopied.lineWise && lastCopied.text.join("\n") == textLines.join("\n")) {
                    from = to = Pos(from.line, 0);
                }
            }
            var changeEvent = {
                from: from, to: to, text: multiPaste ? multiPaste[i$1 % multiPaste.length] : textLines,
                origin: origin || (paste ? "paste" : cm.state.cutIncoming > recent ? "cut" : "+input")
            };
            makeChange(cm.doc, changeEvent);
            signalLater(cm, "inputRead", cm, changeEvent);
        }
        if (inserted && !paste) {
            triggerElectric(cm, inserted);
        }

        ensureCursorVisible(cm);
        if (cm.curOp.updateInput < 2) {
            cm.curOp.updateInput = updateInput;
        }
        cm.curOp.typing = true;
        cm.state.pasteIncoming = cm.state.cutIncoming = -1;
    }

    function handlePaste(e, cm) {
        var pasted = e.clipboardData && e.clipboardData.getData("Text");
        if (pasted) {
            e.preventDefault();
            if (!cm.isReadOnly() && !cm.options.disableInput) {
                runInOp(cm, function () {
                    return applyTextInput(cm, pasted, 0, null, "paste");
                });
            }
            return true
        }
    }

    function triggerElectric(cm, inserted) {
        // When an 'electric' character is inserted, immediately trigger a reindent
        if (!cm.options.electricChars || !cm.options.smartIndent) {
            return
        }
        var sel = cm.doc.sel;

        for (var i = sel.ranges.length-1; i >= 0; i--) {
            var range = sel.ranges[i];
            if (range.head.ch > 100 || (i && sel.ranges[i-1].head.line == range.head.line)) {
                continue
            }
            var mode = cm.getModeAt(range.head);
            var indented = false;
            if (mode.electricChars) {
                for (var j = 0; j < mode.electricChars.length; j++) {
                    if (inserted.indexOf(mode.electricChars.charAt(j)) > -1) {
                        indented = indentLine(cm, range.head.line, "smart");
                        break
                    }
                }
            } else if (mode.electricInput) {
                if (mode.electricInput.test(getLine(cm.doc, range.head.line).text.slice(0, range.head.ch))) {
                    indented = indentLine(cm, range.head.line, "smart");
                }
            }
            if (indented) {
                signalLater(cm, "electricInput", cm, range.head.line);
            }
        }
    }

    function copyableRanges(cm) {
        var text = [], ranges = [];
        for (var i = 0; i < cm.doc.sel.ranges.length; i++) {
            var line = cm.doc.sel.ranges[i].head.line;
            var lineRange = {anchor: Pos(line, 0), head: Pos(line+1, 0)};
            ranges.push(lineRange);
            text.push(cm.getRange(lineRange.anchor, lineRange.head));
        }
        return {text: text, ranges: ranges}
    }

    function disableBrowserMagic(field, spellcheck, autocorrect, autocapitalize) {
        field.setAttribute("autocorrect", autocorrect ? "" : "off");
        field.setAttribute("autocapitalize", autocapitalize ? "" : "off");
        field.setAttribute("spellcheck", !!spellcheck);
    }

    function hiddenTextarea() {
        var te = elt("textarea", null, null, "position: absolute; bottom: -1em; padding: 0; width: 1px; height: 1em; min-height: 1em; outline: none");
        var div = elt("div", [te], null, "overflow: hidden; position: relative; width: 3px; height: 0px;");
        // The textarea is kept positioned near the cursor to prevent the
        // fact that it'll be scrolled into view on input from scrolling
        // our fake cursor out of view. On webkit, when wrap=off, paste is
        // very slow. So make the area wide instead.
        if (webkit) {
            te.style.width = "1000px";
        } else {
            te.setAttribute("wrap", "off");
        }
        // If border: 0; -- iOS fails to open keyboard (issue #1287)
        if (ios) {
            te.style.border = "1px solid black";
        }
        disableBrowserMagic(te);
        return div
    }

    // The publicly visible API. Note that methodOp(f) means
    // 'wrap f in an operation, performed on its `this` parameter'.

    // This is not the complete set of editor methods. Most of the
    // methods defined on the Doc type are also injected into
    // CodeMirror.prototype, for backwards compatibility and
    // convenience.

    function addEditorMethods(CodeMirror) {
        var optionHandlers = CodeMirror.optionHandlers;

        var helpers = CodeMirror.helpers = {};

        CodeMirror.prototype = {
            constructor: CodeMirror,
            focus: function () {
                window.focus();
                this.display.input.focus();
            },

            setOption: function (option, value) {
                var options = this.options, old = options[option];
                if (options[option] == value && option != "mode") {
                    return
                }
                options[option] = value;
                if (optionHandlers.hasOwnProperty(option)) {
                    operation(this, optionHandlers[option])(this, value, old);
                }
                signal(this, "optionChange", this, option);
            },

            getOption: function (option) {
                return this.options[option]
            },
            getDoc: function () {
                return this.doc
            },

            addKeyMap: function (map, bottom) {
                this.state.keyMaps[bottom ? "push" : "unshift"](getKeyMap(map));
            },
            removeKeyMap: function (map) {
                var maps = this.state.keyMaps;
                for (var i = 0; i < maps.length; ++i) {
                    if (maps[i] == map || maps[i].name == map) {
                        maps.splice(i, 1);
                        return true
                    }
                }
            },

            addOverlay: methodOp(function (spec, options) {
                var mode = spec.token ? spec : CodeMirror.getMode(this.options, spec);
                if (mode.startState) {
                    throw new Error("Overlays may not be stateful.")
                }
                insertSorted(this.state.overlays,
                    {
                        mode: mode, modeSpec: spec, opaque: options && options.opaque,
                        priority: (options && options.priority) || 0
                    },
                    function (overlay) {
                        return overlay.priority;
                    });
                this.state.modeGen++;
                regChange(this);
            }),
            removeOverlay: methodOp(function (spec) {
                var overlays = this.state.overlays;
                for (var i = 0; i < overlays.length; ++i) {
                    var cur = overlays[i].modeSpec;
                    if (cur == spec || typeof spec == "string" && cur.name == spec) {
                        overlays.splice(i, 1);
                        this.state.modeGen++;
                        regChange(this);
                        return
                    }
                }
            }),

            indentLine: methodOp(function (n, dir, aggressive) {
                if (typeof dir != "string" && typeof dir != "number") {
                    if (dir == null) {
                        dir = this.options.smartIndent ? "smart" : "prev";
                    } else {
                        dir = dir ? "add" : "subtract";
                    }
                }
                if (isLine(this.doc, n)) {
                    indentLine(this, n, dir, aggressive);
                }
            }),
            indentSelection: methodOp(function (how) {
                var ranges = this.doc.sel.ranges, end = -1;
                for (var i = 0; i < ranges.length; i++) {
                    var range = ranges[i];
                    if (!range.empty()) {
                        var from = range.from(), to = range.to();
                        var start = Math.max(end, from.line);
                        end = Math.min(this.lastLine(), to.line-(to.ch ? 0 : 1))+1;
                        for (var j = start; j < end; ++j) {
                            indentLine(this, j, how);
                        }
                        var newRanges = this.doc.sel.ranges;
                        if (from.ch == 0 && ranges.length == newRanges.length && newRanges[i].from().ch > 0) {
                            replaceOneSelection(this.doc, i, new Range(from, newRanges[i].to()), sel_dontScroll);
                        }
                    } else if (range.head.line > end) {
                        indentLine(this, range.head.line, how, true);
                        end = range.head.line;
                        if (i == this.doc.sel.primIndex) {
                            ensureCursorVisible(this);
                        }
                    }
                }
            }),

            // Fetch the parser token for a given character. Useful for hacks
            // that want to inspect the mode state (say, for completion).
            getTokenAt: function (pos, precise) {
                return takeToken(this, pos, precise)
            },

            getLineTokens: function (line, precise) {
                return takeToken(this, Pos(line), precise, true)
            },

            getTokenTypeAt: function (pos) {
                pos = clipPos(this.doc, pos);
                var styles = getLineStyles(this, getLine(this.doc, pos.line));
                var before = 0, after = (styles.length-1) / 2, ch = pos.ch;
                var type;
                if (ch == 0) {
                    type = styles[2];
                } else {
                    for (; ;) {
                        var mid = (before+after) >> 1;
                        if ((mid ? styles[mid * 2-1] : 0) >= ch) {
                            after = mid;
                        } else if (styles[mid * 2+1] < ch) {
                            before = mid+1;
                        } else {
                            type = styles[mid * 2+2];
                            break
                        }
                    }
                }
                var cut = type ? type.indexOf("overlay ") : -1;
                return cut < 0 ? type : cut == 0 ? null : type.slice(0, cut-1)
            },

            getModeAt: function (pos) {
                var mode = this.doc.mode;
                if (!mode.innerMode) {
                    return mode
                }
                return CodeMirror.innerMode(mode, this.getTokenAt(pos).state).mode
            },

            getHelper: function (pos, type) {
                return this.getHelpers(pos, type)[0]
            },

            getHelpers: function (pos, type) {
                var found = [];
                if (!helpers.hasOwnProperty(type)) {
                    return found
                }
                var help = helpers[type], mode = this.getModeAt(pos);
                if (typeof mode[type] == "string") {
                    if (help[mode[type]]) {
                        found.push(help[mode[type]]);
                    }
                } else if (mode[type]) {
                    for (var i = 0; i < mode[type].length; i++) {
                        var val = help[mode[type][i]];
                        if (val) {
                            found.push(val);
                        }
                    }
                } else if (mode.helperType && help[mode.helperType]) {
                    found.push(help[mode.helperType]);
                } else if (help[mode.name]) {
                    found.push(help[mode.name]);
                }
                for (var i$1 = 0; i$1 < help._global.length; i$1++) {
                    var cur = help._global[i$1];
                    if (cur.pred(mode, this) && indexOf(found, cur.val) == -1) {
                        found.push(cur.val);
                    }
                }
                return found
            },

            getStateAfter: function (line, precise) {
                var doc = this.doc;
                line = clipLine(doc, line == null ? doc.first+doc.size-1 : line);
                return getContextBefore(this, line+1, precise).state
            },

            cursorCoords: function (start, mode) {
                var pos, range = this.doc.sel.primary();
                if (start == null) {
                    pos = range.head;
                } else if (typeof start == "object") {
                    pos = clipPos(this.doc, start);
                } else {
                    pos = start ? range.from() : range.to();
                }
                return cursorCoords(this, pos, mode || "page")
            },

            charCoords: function (pos, mode) {
                return charCoords(this, clipPos(this.doc, pos), mode || "page")
            },

            coordsChar: function (coords, mode) {
                coords = fromCoordSystem(this, coords, mode || "page");
                return coordsChar(this, coords.left, coords.top)
            },

            lineAtHeight: function (height, mode) {
                height = fromCoordSystem(this, {top: height, left: 0}, mode || "page").top;
                return lineAtHeight(this.doc, height+this.display.viewOffset)
            },
            heightAtLine: function (line, mode, includeWidgets) {
                var end = false, lineObj;
                if (typeof line == "number") {
                    var last = this.doc.first+this.doc.size-1;
                    if (line < this.doc.first) {
                        line = this.doc.first;
                    } else if (line > last) {
                        line = last;
                        end = true;
                    }
                    lineObj = getLine(this.doc, line);
                } else {
                    lineObj = line;
                }
                return intoCoordSystem(this, lineObj, {top: 0, left: 0}, mode || "page", includeWidgets || end).top+
                    (end ? this.doc.height-heightAtLine(lineObj) : 0)
            },

            defaultTextHeight: function () {
                return textHeight(this.display)
            },
            defaultCharWidth: function () {
                return charWidth(this.display)
            },

            getViewport: function () {
                return {from: this.display.viewFrom, to: this.display.viewTo}
            },

            addWidget: function (pos, node, scroll, vert, horiz) {
                var display = this.display;
                pos = cursorCoords(this, clipPos(this.doc, pos));
                var top = pos.bottom, left = pos.left;
                node.style.position = "absolute";
                node.setAttribute("cm-ignore-events", "true");
                this.display.input.setUneditable(node);
                display.sizer.appendChild(node);
                if (vert == "over") {
                    top = pos.top;
                } else if (vert == "above" || vert == "near") {
                    var vspace = Math.max(display.wrapper.clientHeight, this.doc.height),
                        hspace = Math.max(display.sizer.clientWidth, display.lineSpace.clientWidth);
                    // Default to positioning above (if specified and possible); otherwise default to positioning below
                    if ((vert == 'above' || pos.bottom+node.offsetHeight > vspace) && pos.top > node.offsetHeight) {
                        top = pos.top-node.offsetHeight;
                    } else if (pos.bottom+node.offsetHeight <= vspace) {
                        top = pos.bottom;
                    }
                    if (left+node.offsetWidth > hspace) {
                        left = hspace-node.offsetWidth;
                    }
                }
                node.style.top = top+"px";
                node.style.left = node.style.right = "";
                if (horiz == "right") {
                    left = display.sizer.clientWidth-node.offsetWidth;
                    node.style.right = "0px";
                } else {
                    if (horiz == "left") {
                        left = 0;
                    } else if (horiz == "middle") {
                        left = (display.sizer.clientWidth-node.offsetWidth) / 2;
                    }
                    node.style.left = left+"px";
                }
                if (scroll) {
                    scrollIntoView(this, {
                        left: left,
                        top: top,
                        right: left+node.offsetWidth,
                        bottom: top+node.offsetHeight
                    });
                }
            },

            triggerOnKeyDown: methodOp(onKeyDown),
            triggerOnKeyPress: methodOp(onKeyPress),
            triggerOnKeyUp: onKeyUp,
            triggerOnMouseDown: methodOp(onMouseDown),

            execCommand: function (cmd) {
                if (commands.hasOwnProperty(cmd)) {
                    return commands[cmd].call(null, this)
                }
            },

            triggerElectric: methodOp(function (text) {
                triggerElectric(this, text);
            }),

            findPosH: function (from, amount, unit, visually) {
                var dir = 1;
                if (amount < 0) {
                    dir = -1;
                    amount = -amount;
                }
                var cur = clipPos(this.doc, from);
                for (var i = 0; i < amount; ++i) {
                    cur = findPosH(this.doc, cur, dir, unit, visually);
                    if (cur.hitSide) {
                        break
                    }
                }
                return cur
            },

            moveH: methodOp(function (dir, unit) {
                var this$1 = this;

                this.extendSelectionsBy(function (range) {
                    if (this$1.display.shift || this$1.doc.extend || range.empty()) {
                        return findPosH(this$1.doc, range.head, dir, unit, this$1.options.rtlMoveVisually)
                    } else {
                        return dir < 0 ? range.from() : range.to()
                    }
                }, sel_move);
            }),

            deleteH: methodOp(function (dir, unit) {
                var sel = this.doc.sel, doc = this.doc;
                if (sel.somethingSelected()) {
                    doc.replaceSelection("", null, "+delete");
                } else {
                    deleteNearSelection(this, function (range) {
                        var other = findPosH(doc, range.head, dir, unit, false);
                        return dir < 0 ? {from: other, to: range.head} : {from: range.head, to: other}
                    });
                }
            }),

            findPosV: function (from, amount, unit, goalColumn) {
                var dir = 1, x = goalColumn;
                if (amount < 0) {
                    dir = -1;
                    amount = -amount;
                }
                var cur = clipPos(this.doc, from);
                for (var i = 0; i < amount; ++i) {
                    var coords = cursorCoords(this, cur, "div");
                    if (x == null) {
                        x = coords.left;
                    } else {
                        coords.left = x;
                    }
                    cur = findPosV(this, coords, dir, unit);
                    if (cur.hitSide) {
                        break
                    }
                }
                return cur
            },

            moveV: methodOp(function (dir, unit) {
                var this$1 = this;

                var doc = this.doc, goals = [];
                var collapse = !this.display.shift && !doc.extend && doc.sel.somethingSelected();
                doc.extendSelectionsBy(function (range) {
                    if (collapse) {
                        return dir < 0 ? range.from() : range.to()
                    }
                    var headPos = cursorCoords(this$1, range.head, "div");
                    if (range.goalColumn != null) {
                        headPos.left = range.goalColumn;
                    }
                    goals.push(headPos.left);
                    var pos = findPosV(this$1, headPos, dir, unit);
                    if (unit == "page" && range == doc.sel.primary()) {
                        addToScrollTop(this$1, charCoords(this$1, pos, "div").top-headPos.top);
                    }
                    return pos
                }, sel_move);
                if (goals.length) {
                    for (var i = 0; i < doc.sel.ranges.length; i++) {
                        doc.sel.ranges[i].goalColumn = goals[i];
                    }
                }
            }),

            // Find the word at the given position (as returned by coordsChar).
            findWordAt: function (pos) {
                var doc = this.doc, line = getLine(doc, pos.line).text;
                var start = pos.ch, end = pos.ch;
                if (line) {
                    var helper = this.getHelper(pos, "wordChars");
                    if ((pos.sticky == "before" || end == line.length) && start) {
                        --start;
                    } else {
                        ++end;
                    }
                    var startChar = line.charAt(start);
                    var check = isWordChar(startChar, helper)
                        ? function (ch) {
                            return isWordChar(ch, helper);
                        }
                        : /\s/.test(startChar) ? function (ch) {
                                return /\s/.test(ch);
                            }
                            : function (ch) {
                                return (!/\s/.test(ch) && !isWordChar(ch));
                            };
                    while (start > 0 && check(line.charAt(start-1))) {
                        --start;
                    }
                    while (end < line.length && check(line.charAt(end))) {
                        ++end;
                    }
                }
                return new Range(Pos(pos.line, start), Pos(pos.line, end))
            },

            toggleOverwrite: function (value) {
                if (value != null && value == this.state.overwrite) {
                    return
                }
                if (this.state.overwrite = !this.state.overwrite) {
                    addClass(this.display.cursorDiv, "CodeMirror-overwrite");
                } else {
                    rmClass(this.display.cursorDiv, "CodeMirror-overwrite");
                }

                signal(this, "overwriteToggle", this, this.state.overwrite);
            },
            hasFocus: function () {
                return this.display.input.getField() == activeElt()
            },
            isReadOnly: function () {
                return !!(this.options.readOnly || this.doc.cantEdit)
            },

            scrollTo: methodOp(function (x, y) {
                scrollToCoords(this, x, y);
            }),
            getScrollInfo: function () {
                var scroller = this.display.scroller;
                return {
                    left: scroller.scrollLeft, top: scroller.scrollTop,
                    height: scroller.scrollHeight-scrollGap(this)-this.display.barHeight,
                    width: scroller.scrollWidth-scrollGap(this)-this.display.barWidth,
                    clientHeight: displayHeight(this), clientWidth: displayWidth(this)
                }
            },

            scrollIntoView: methodOp(function (range, margin) {
                if (range == null) {
                    range = {from: this.doc.sel.primary().head, to: null};
                    if (margin == null) {
                        margin = this.options.cursorScrollMargin;
                    }
                } else if (typeof range == "number") {
                    range = {from: Pos(range, 0), to: null};
                } else if (range.from == null) {
                    range = {from: range, to: null};
                }
                if (!range.to) {
                    range.to = range.from;
                }
                range.margin = margin || 0;

                if (range.from.line != null) {
                    scrollToRange(this, range);
                } else {
                    scrollToCoordsRange(this, range.from, range.to, range.margin);
                }
            }),

            setSize: methodOp(function (width, height) {
                var this$1 = this;

                var interpret = function (val) {
                    return typeof val == "number" || /^\d+$/.test(String(val)) ? val+"px" : val;
                };
                if (width != null) {
                    this.display.wrapper.style.width = interpret(width);
                }
                if (height != null) {
                    this.display.wrapper.style.height = interpret(height);
                }
                if (this.options.lineWrapping) {
                    clearLineMeasurementCache(this);
                }
                var lineNo = this.display.viewFrom;
                this.doc.iter(lineNo, this.display.viewTo, function (line) {
                    if (line.widgets) {
                        for (var i = 0; i < line.widgets.length; i++) {
                            if (line.widgets[i].noHScroll) {
                                regLineChange(this$1, lineNo, "widget");
                                break
                            }
                        }
                    }
                    ++lineNo;
                });
                this.curOp.forceUpdate = true;
                signal(this, "refresh", this);
            }),

            operation: function (f) {
                return runInOp(this, f)
            },
            startOperation: function () {
                return startOperation(this)
            },
            endOperation: function () {
                return endOperation(this)
            },

            refresh: methodOp(function () {
                var oldHeight = this.display.cachedTextHeight;
                regChange(this);
                this.curOp.forceUpdate = true;
                clearCaches(this);
                scrollToCoords(this, this.doc.scrollLeft, this.doc.scrollTop);
                updateGutterSpace(this.display);
                if (oldHeight == null || Math.abs(oldHeight-textHeight(this.display)) > .5 || this.options.lineWrapping) {
                    estimateLineHeights(this);
                }
                signal(this, "refresh", this);
            }),

            swapDoc: methodOp(function (doc) {
                var old = this.doc;
                old.cm = null;
                // Cancel the current text selection if any (#5821)
                if (this.state.selectingText) {
                    this.state.selectingText();
                }
                attachDoc(this, doc);
                clearCaches(this);
                this.display.input.reset();
                scrollToCoords(this, doc.scrollLeft, doc.scrollTop);
                this.curOp.forceScroll = true;
                signalLater(this, "swapDoc", this, old);
                return old
            }),

            phrase: function (phraseText) {
                var phrases = this.options.phrases;
                return phrases && Object.prototype.hasOwnProperty.call(phrases, phraseText) ? phrases[phraseText] : phraseText
            },

            getInputField: function () {
                return this.display.input.getField()
            },
            getWrapperElement: function () {
                return this.display.wrapper
            },
            getScrollerElement: function () {
                return this.display.scroller
            },
            getGutterElement: function () {
                return this.display.gutters
            }
        };
        eventMixin(CodeMirror);

        CodeMirror.registerHelper = function (type, name, value) {
            if (!helpers.hasOwnProperty(type)) {
                helpers[type] = CodeMirror[type] = {_global: []};
            }
            helpers[type][name] = value;
        };
        CodeMirror.registerGlobalHelper = function (type, name, predicate, value) {
            CodeMirror.registerHelper(type, name, value);
            helpers[type]._global.push({pred: predicate, val: value});
        };
    }

    // Used for horizontal relative motion. Dir is -1 or 1 (left or
    // right), unit can be "codepoint", "char", "column" (like char, but
    // doesn't cross line boundaries), "word" (across next word), or
    // "group" (to the start of next group of word or
    // non-word-non-whitespace chars). The visually param controls
    // whether, in right-to-left text, direction 1 means to move towards
    // the next index in the string, or towards the character to the right
    // of the current position. The resulting position will have a
    // hitSide=true property if it reached the end of the document.
    function findPosH(doc, pos, dir, unit, visually) {
        var oldPos = pos;
        var origDir = dir;
        var lineObj = getLine(doc, pos.line);
        var lineDir = visually && doc.direction == "rtl" ? -dir : dir;

        function findNextLine() {
            var l = pos.line+lineDir;
            if (l < doc.first || l >= doc.first+doc.size) {
                return false
            }
            pos = new Pos(l, pos.ch, pos.sticky);
            return lineObj = getLine(doc, l)
        }

        function moveOnce(boundToLine) {
            var next;
            if (unit == "codepoint") {
                var ch = lineObj.text.charCodeAt(pos.ch+(dir > 0 ? 0 : -1));
                if (isNaN(ch)) {
                    next = null;
                } else {
                    var astral = dir > 0 ? ch >= 0xD800 && ch < 0xDC00 : ch >= 0xDC00 && ch < 0xDFFF;
                    next = new Pos(pos.line, Math.max(0, Math.min(lineObj.text.length, pos.ch+dir * (astral ? 2 : 1))), -dir);
                }
            } else if (visually) {
                next = moveVisually(doc.cm, lineObj, pos, dir);
            } else {
                next = moveLogically(lineObj, pos, dir);
            }
            if (next == null) {
                if (!boundToLine && findNextLine()) {
                    pos = endOfLine(visually, doc.cm, lineObj, pos.line, lineDir);
                } else {
                    return false
                }
            } else {
                pos = next;
            }
            return true
        }

        if (unit == "char" || unit == "codepoint") {
            moveOnce();
        } else if (unit == "column") {
            moveOnce(true);
        } else if (unit == "word" || unit == "group") {
            var sawType = null, group = unit == "group";
            var helper = doc.cm && doc.cm.getHelper(pos, "wordChars");
            for (var first = true; ; first = false) {
                if (dir < 0 && !moveOnce(!first)) {
                    break
                }
                var cur = lineObj.text.charAt(pos.ch) || "\n";
                var type = isWordChar(cur, helper) ? "w"
                    : group && cur == "\n" ? "n"
                        : !group || /\s/.test(cur) ? null
                            : "p";
                if (group && !first && !type) {
                    type = "s";
                }
                if (sawType && sawType != type) {
                    if (dir < 0) {
                        dir = 1;
                        moveOnce();
                        pos.sticky = "after";
                    }
                    break
                }

                if (type) {
                    sawType = type;
                }
                if (dir > 0 && !moveOnce(!first)) {
                    break
                }
            }
        }
        var result = skipAtomic(doc, pos, oldPos, origDir, true);
        if (equalCursorPos(oldPos, result)) {
            result.hitSide = true;
        }
        return result
    }

    // For relative vertical movement. Dir may be -1 or 1. Unit can be
    // "page" or "line". The resulting position will have a hitSide=true
    // property if it reached the end of the document.
    function findPosV(cm, pos, dir, unit) {
        var doc = cm.doc, x = pos.left, y;
        if (unit == "page") {
            var pageSize = Math.min(cm.display.wrapper.clientHeight, window.innerHeight || document.documentElement.clientHeight);
            var moveAmount = Math.max(pageSize-.5 * textHeight(cm.display), 3);
            y = (dir > 0 ? pos.bottom : pos.top)+dir * moveAmount;

        } else if (unit == "line") {
            y = dir > 0 ? pos.bottom+3 : pos.top-3;
        }
        var target;
        for (; ;) {
            target = coordsChar(cm, x, y);
            if (!target.outside) {
                break
            }
            if (dir < 0 ? y <= 0 : y >= doc.height) {
                target.hitSide = true;
                break
            }
            y += dir * 5;
        }
        return target
    }

    // CONTENTEDITABLE INPUT STYLE

    var ContentEditableInput = function (cm) {
        this.cm = cm;
        this.lastAnchorNode = this.lastAnchorOffset = this.lastFocusNode = this.lastFocusOffset = null;
        this.polling = new Delayed();
        this.composing = null;
        this.gracePeriod = false;
        this.readDOMTimeout = null;
    };

    ContentEditableInput.prototype.init = function (display) {
        var this$1 = this;

        var input = this, cm = input.cm;
        var div = input.div = display.lineDiv;
        div.contentEditable = true;
        disableBrowserMagic(div, cm.options.spellcheck, cm.options.autocorrect, cm.options.autocapitalize);

        function belongsToInput(e) {
            for (var t = e.target; t; t = t.parentNode) {
                if (t == div) {
                    return true
                }
                if (/\bCodeMirror-(?:line)?widget\b/.test(t.className)) {
                    break
                }
            }
            return false
        }

        on(div, "paste", function (e) {
            if (!belongsToInput(e) || signalDOMEvent(cm, e) || handlePaste(e, cm)) {
                return
            }
            // IE doesn't fire input events, so we schedule a read for the pasted content in this way
            if (ie_version <= 11) {
                setTimeout(operation(cm, function () {
                    return this$1.updateFromDOM();
                }), 20);
            }
        });

        on(div, "compositionstart", function (e) {
            this$1.composing = {data: e.data, done: false};
        });
        on(div, "compositionupdate", function (e) {
            if (!this$1.composing) {
                this$1.composing = {data: e.data, done: false};
            }
        });
        on(div, "compositionend", function (e) {
            if (this$1.composing) {
                if (e.data != this$1.composing.data) {
                    this$1.readFromDOMSoon();
                }
                this$1.composing.done = true;
            }
        });

        on(div, "touchstart", function () {
            return input.forceCompositionEnd();
        });

        on(div, "input", function () {
            if (!this$1.composing) {
                this$1.readFromDOMSoon();
            }
        });

        function onCopyCut(e) {
            if (!belongsToInput(e) || signalDOMEvent(cm, e)) {
                return
            }
            if (cm.somethingSelected()) {
                setLastCopied({lineWise: false, text: cm.getSelections()});
                if (e.type == "cut") {
                    cm.replaceSelection("", null, "cut");
                }
            } else if (!cm.options.lineWiseCopyCut) {
                return
            } else {
                var ranges = copyableRanges(cm);
                setLastCopied({lineWise: true, text: ranges.text});
                if (e.type == "cut") {
                    cm.operation(function () {
                        cm.setSelections(ranges.ranges, 0, sel_dontScroll);
                        cm.replaceSelection("", null, "cut");
                    });
                }
            }
            if (e.clipboardData) {
                e.clipboardData.clearData();
                var content = lastCopied.text.join("\n");
                // iOS exposes the clipboard API, but seems to discard content inserted into it
                e.clipboardData.setData("Text", content);
                if (e.clipboardData.getData("Text") == content) {
                    e.preventDefault();
                    return
                }
            }
            // Old-fashioned briefly-focus-a-textarea hack
            var kludge = hiddenTextarea(), te = kludge.firstChild;
            cm.display.lineSpace.insertBefore(kludge, cm.display.lineSpace.firstChild);
            te.value = lastCopied.text.join("\n");
            var hadFocus = activeElt();
            selectInput(te);
            setTimeout(function () {
                cm.display.lineSpace.removeChild(kludge);
                hadFocus.focus();
                if (hadFocus == div) {
                    input.showPrimarySelection();
                }
            }, 50);
        }

        on(div, "copy", onCopyCut);
        on(div, "cut", onCopyCut);
    };

    ContentEditableInput.prototype.screenReaderLabelChanged = function (label) {
        // Label for screenreaders, accessibility
        if (label) {
            this.div.setAttribute('aria-label', label);
        } else {
            this.div.removeAttribute('aria-label');
        }
    };

    ContentEditableInput.prototype.prepareSelection = function () {
        var result = prepareSelection(this.cm, false);
        result.focus = activeElt() == this.div;
        return result
    };

    ContentEditableInput.prototype.showSelection = function (info, takeFocus) {
        if (!info || !this.cm.display.view.length) {
            return
        }
        if (info.focus || takeFocus) {
            this.showPrimarySelection();
        }
        this.showMultipleSelections(info);
    };

    ContentEditableInput.prototype.getSelection = function () {
        return this.cm.display.wrapper.ownerDocument.getSelection()
    };

    ContentEditableInput.prototype.showPrimarySelection = function () {
        var sel = this.getSelection(), cm = this.cm, prim = cm.doc.sel.primary();
        var from = prim.from(), to = prim.to();

        if (cm.display.viewTo == cm.display.viewFrom || from.line >= cm.display.viewTo || to.line < cm.display.viewFrom) {
            sel.removeAllRanges();
            return
        }

        var curAnchor = domToPos(cm, sel.anchorNode, sel.anchorOffset);
        var curFocus = domToPos(cm, sel.focusNode, sel.focusOffset);
        if (curAnchor && !curAnchor.bad && curFocus && !curFocus.bad &&
            cmp(minPos(curAnchor, curFocus), from) == 0 &&
            cmp(maxPos(curAnchor, curFocus), to) == 0) {
            return
        }

        var view = cm.display.view;
        var start = (from.line >= cm.display.viewFrom && posToDOM(cm, from)) ||
            {node: view[0].measure.map[2], offset: 0};
        var end = to.line < cm.display.viewTo && posToDOM(cm, to);
        if (!end) {
            var measure = view[view.length-1].measure;
            var map = measure.maps ? measure.maps[measure.maps.length-1] : measure.map;
            end = {node: map[map.length-1], offset: map[map.length-2]-map[map.length-3]};
        }

        if (!start || !end) {
            sel.removeAllRanges();
            return
        }

        var old = sel.rangeCount && sel.getRangeAt(0), rng;
        try {
            rng = range(start.node, start.offset, end.offset, end.node);
        } catch (e) {
        } // Our model of the DOM might be outdated, in which case the range we try to set can be impossible
        if (rng) {
            if (!gecko && cm.state.focused) {
                sel.collapse(start.node, start.offset);
                if (!rng.collapsed) {
                    sel.removeAllRanges();
                    sel.addRange(rng);
                }
            } else {
                sel.removeAllRanges();
                sel.addRange(rng);
            }
            if (old && sel.anchorNode == null) {
                sel.addRange(old);
            } else if (gecko) {
                this.startGracePeriod();
            }
        }
        this.rememberSelection();
    };

    ContentEditableInput.prototype.startGracePeriod = function () {
        var this$1 = this;

        clearTimeout(this.gracePeriod);
        this.gracePeriod = setTimeout(function () {
            this$1.gracePeriod = false;
            if (this$1.selectionChanged()) {
                this$1.cm.operation(function () {
                    return this$1.cm.curOp.selectionChanged = true;
                });
            }
        }, 20);
    };

    ContentEditableInput.prototype.showMultipleSelections = function (info) {
        removeChildrenAndAdd(this.cm.display.cursorDiv, info.cursors);
        removeChildrenAndAdd(this.cm.display.selectionDiv, info.selection);
    };

    ContentEditableInput.prototype.rememberSelection = function () {
        var sel = this.getSelection();
        this.lastAnchorNode = sel.anchorNode;
        this.lastAnchorOffset = sel.anchorOffset;
        this.lastFocusNode = sel.focusNode;
        this.lastFocusOffset = sel.focusOffset;
    };

    ContentEditableInput.prototype.selectionInEditor = function () {
        var sel = this.getSelection();
        if (!sel.rangeCount) {
            return false
        }
        var node = sel.getRangeAt(0).commonAncestorContainer;
        return contains(this.div, node)
    };

    ContentEditableInput.prototype.focus = function () {
        if (this.cm.options.readOnly != "nocursor") {
            if (!this.selectionInEditor() || activeElt() != this.div) {
                this.showSelection(this.prepareSelection(), true);
            }
            this.div.focus();
        }
    };
    ContentEditableInput.prototype.blur = function () {
        this.div.blur();
    };
    ContentEditableInput.prototype.getField = function () {
        return this.div
    };

    ContentEditableInput.prototype.supportsTouch = function () {
        return true
    };

    ContentEditableInput.prototype.receivedFocus = function () {
        var this$1 = this;

        var input = this;
        if (this.selectionInEditor()) {
            setTimeout(function () {
                return this$1.pollSelection();
            }, 20);
        } else {
            runInOp(this.cm, function () {
                return input.cm.curOp.selectionChanged = true;
            });
        }

        function poll() {
            if (input.cm.state.focused) {
                input.pollSelection();
                input.polling.set(input.cm.options.pollInterval, poll);
            }
        }

        this.polling.set(this.cm.options.pollInterval, poll);
    };

    ContentEditableInput.prototype.selectionChanged = function () {
        var sel = this.getSelection();
        return sel.anchorNode != this.lastAnchorNode || sel.anchorOffset != this.lastAnchorOffset ||
            sel.focusNode != this.lastFocusNode || sel.focusOffset != this.lastFocusOffset
    };

    ContentEditableInput.prototype.pollSelection = function () {
        if (this.readDOMTimeout != null || this.gracePeriod || !this.selectionChanged()) {
            return
        }
        var sel = this.getSelection(), cm = this.cm;
        // On Android Chrome (version 56, at least), backspacing into an
        // uneditable block element will put the cursor in that element,
        // and then, because it's not editable, hide the virtual keyboard.
        // Because Android doesn't allow us to actually detect backspace
        // presses in a sane way, this code checks for when that happens
        // and simulates a backspace press in this case.
        if (android && chrome && this.cm.display.gutterSpecs.length && isInGutter(sel.anchorNode)) {
            this.cm.triggerOnKeyDown({type: "keydown", keyCode: 8, preventDefault: Math.abs});
            this.blur();
            this.focus();
            return
        }
        if (this.composing) {
            return
        }
        this.rememberSelection();
        var anchor = domToPos(cm, sel.anchorNode, sel.anchorOffset);
        var head = domToPos(cm, sel.focusNode, sel.focusOffset);
        if (anchor && head) {
            runInOp(cm, function () {
                setSelection(cm.doc, simpleSelection(anchor, head), sel_dontScroll);
                if (anchor.bad || head.bad) {
                    cm.curOp.selectionChanged = true;
                }
            });
        }
    };

    ContentEditableInput.prototype.pollContent = function () {
        if (this.readDOMTimeout != null) {
            clearTimeout(this.readDOMTimeout);
            this.readDOMTimeout = null;
        }

        var cm = this.cm, display = cm.display, sel = cm.doc.sel.primary();
        var from = sel.from(), to = sel.to();
        if (from.ch == 0 && from.line > cm.firstLine()) {
            from = Pos(from.line-1, getLine(cm.doc, from.line-1).length);
        }
        if (to.ch == getLine(cm.doc, to.line).text.length && to.line < cm.lastLine()) {
            to = Pos(to.line+1, 0);
        }
        if (from.line < display.viewFrom || to.line > display.viewTo-1) {
            return false
        }

        var fromIndex, fromLine, fromNode;
        if (from.line == display.viewFrom || (fromIndex = findViewIndex(cm, from.line)) == 0) {
            fromLine = lineNo(display.view[0].line);
            fromNode = display.view[0].node;
        } else {
            fromLine = lineNo(display.view[fromIndex].line);
            fromNode = display.view[fromIndex-1].node.nextSibling;
        }
        var toIndex = findViewIndex(cm, to.line);
        var toLine, toNode;
        if (toIndex == display.view.length-1) {
            toLine = display.viewTo-1;
            toNode = display.lineDiv.lastChild;
        } else {
            toLine = lineNo(display.view[toIndex+1].line)-1;
            toNode = display.view[toIndex+1].node.previousSibling;
        }

        if (!fromNode) {
            return false
        }
        var newText = cm.doc.splitLines(domTextBetween(cm, fromNode, toNode, fromLine, toLine));
        var oldText = getBetween(cm.doc, Pos(fromLine, 0), Pos(toLine, getLine(cm.doc, toLine).text.length));
        while (newText.length > 1 && oldText.length > 1) {
            if (lst(newText) == lst(oldText)) {
                newText.pop();
                oldText.pop();
                toLine--;
            } else if (newText[0] == oldText[0]) {
                newText.shift();
                oldText.shift();
                fromLine++;
            } else {
                break
            }
        }

        var cutFront = 0, cutEnd = 0;
        var newTop = newText[0], oldTop = oldText[0], maxCutFront = Math.min(newTop.length, oldTop.length);
        while (cutFront < maxCutFront && newTop.charCodeAt(cutFront) == oldTop.charCodeAt(cutFront)) {
            ++cutFront;
        }
        var newBot = lst(newText), oldBot = lst(oldText);
        var maxCutEnd = Math.min(newBot.length-(newText.length == 1 ? cutFront : 0),
            oldBot.length-(oldText.length == 1 ? cutFront : 0));
        while (cutEnd < maxCutEnd &&
        newBot.charCodeAt(newBot.length-cutEnd-1) == oldBot.charCodeAt(oldBot.length-cutEnd-1)) {
            ++cutEnd;
        }
        // Try to move start of change to start of selection if ambiguous
        if (newText.length == 1 && oldText.length == 1 && fromLine == from.line) {
            while (cutFront && cutFront > from.ch &&
            newBot.charCodeAt(newBot.length-cutEnd-1) == oldBot.charCodeAt(oldBot.length-cutEnd-1)) {
                cutFront--;
                cutEnd++;
            }
        }

        newText[newText.length-1] = newBot.slice(0, newBot.length-cutEnd).replace(/^\u200b+/, "");
        newText[0] = newText[0].slice(cutFront).replace(/\u200b+$/, "");

        var chFrom = Pos(fromLine, cutFront);
        var chTo = Pos(toLine, oldText.length ? lst(oldText).length-cutEnd : 0);
        if (newText.length > 1 || newText[0] || cmp(chFrom, chTo)) {
            replaceRange(cm.doc, newText, chFrom, chTo, "+input");
            return true
        }
    };

    ContentEditableInput.prototype.ensurePolled = function () {
        this.forceCompositionEnd();
    };
    ContentEditableInput.prototype.reset = function () {
        this.forceCompositionEnd();
    };
    ContentEditableInput.prototype.forceCompositionEnd = function () {
        if (!this.composing) {
            return
        }
        clearTimeout(this.readDOMTimeout);
        this.composing = null;
        this.updateFromDOM();
        this.div.blur();
        this.div.focus();
    };
    ContentEditableInput.prototype.readFromDOMSoon = function () {
        var this$1 = this;

        if (this.readDOMTimeout != null) {
            return
        }
        this.readDOMTimeout = setTimeout(function () {
            this$1.readDOMTimeout = null;
            if (this$1.composing) {
                if (this$1.composing.done) {
                    this$1.composing = null;
                } else {
                    return
                }
            }
            this$1.updateFromDOM();
        }, 80);
    };

    ContentEditableInput.prototype.updateFromDOM = function () {
        var this$1 = this;

        if (this.cm.isReadOnly() || !this.pollContent()) {
            runInOp(this.cm, function () {
                return regChange(this$1.cm);
            });
        }
    };

    ContentEditableInput.prototype.setUneditable = function (node) {
        node.contentEditable = "false";
    };

    ContentEditableInput.prototype.onKeyPress = function (e) {
        if (e.charCode == 0 || this.composing) {
            return
        }
        e.preventDefault();
        if (!this.cm.isReadOnly()) {
            operation(this.cm, applyTextInput)(this.cm, String.fromCharCode(e.charCode == null ? e.keyCode : e.charCode), 0);
        }
    };

    ContentEditableInput.prototype.readOnlyChanged = function (val) {
        this.div.contentEditable = String(val != "nocursor");
    };

    ContentEditableInput.prototype.onContextMenu = function () {
    };
    ContentEditableInput.prototype.resetPosition = function () {
    };

    ContentEditableInput.prototype.needsContentAttribute = true;

    function posToDOM(cm, pos) {
        var view = findViewForLine(cm, pos.line);
        if (!view || view.hidden) {
            return null
        }
        var line = getLine(cm.doc, pos.line);
        var info = mapFromLineView(view, line, pos.line);

        var order = getOrder(line, cm.doc.direction), side = "left";
        if (order) {
            var partPos = getBidiPartAt(order, pos.ch);
            side = partPos % 2 ? "right" : "left";
        }
        var result = nodeAndOffsetInLineMap(info.map, pos.ch, side);
        result.offset = result.collapse == "right" ? result.end : result.start;
        return result
    }

    function isInGutter(node) {
        for (var scan = node; scan; scan = scan.parentNode) {
            if (/CodeMirror-gutter-wrapper/.test(scan.className)) {
                return true
            }
        }
        return false
    }

    function badPos(pos, bad) {
        if (bad) {
            pos.bad = true;
        }
        return pos
    }

    function domTextBetween(cm, from, to, fromLine, toLine) {
        var text = "", closing = false, lineSep = cm.doc.lineSeparator(), extraLinebreak = false;

        function recognizeMarker(id) {
            return function (marker) {
                return marker.id == id;
            }
        }

        function close() {
            if (closing) {
                text += lineSep;
                if (extraLinebreak) {
                    text += lineSep;
                }
                closing = extraLinebreak = false;
            }
        }

        function addText(str) {
            if (str) {
                close();
                text += str;
            }
        }

        function walk(node) {
            if (node.nodeType == 1) {
                var cmText = node.getAttribute("cm-text");
                if (cmText) {
                    addText(cmText);
                    return
                }
                var markerID = node.getAttribute("cm-marker"), range;
                if (markerID) {
                    var found = cm.findMarks(Pos(fromLine, 0), Pos(toLine+1, 0), recognizeMarker(+markerID));
                    if (found.length && (range = found[0].find(0))) {
                        addText(getBetween(cm.doc, range.from, range.to).join(lineSep));
                    }
                    return
                }
                if (node.getAttribute("contenteditable") == "false") {
                    return
                }
                var isBlock = /^(pre|div|p|li|table|br)$/i.test(node.nodeName);
                if (!/^br$/i.test(node.nodeName) && node.textContent.length == 0) {
                    return
                }

                if (isBlock) {
                    close();
                }
                for (var i = 0; i < node.childNodes.length; i++) {
                    walk(node.childNodes[i]);
                }

                if (/^(pre|p)$/i.test(node.nodeName)) {
                    extraLinebreak = true;
                }
                if (isBlock) {
                    closing = true;
                }
            } else if (node.nodeType == 3) {
                addText(node.nodeValue.replace(/\u200b/g, "").replace(/\u00a0/g, " "));
            }
        }

        for (; ;) {
            walk(from);
            if (from == to) {
                break
            }
            from = from.nextSibling;
            extraLinebreak = false;
        }
        return text
    }

    function domToPos(cm, node, offset) {
        var lineNode;
        if (node == cm.display.lineDiv) {
            lineNode = cm.display.lineDiv.childNodes[offset];
            if (!lineNode) {
                return badPos(cm.clipPos(Pos(cm.display.viewTo-1)), true)
            }
            node = null;
            offset = 0;
        } else {
            for (lineNode = node; ; lineNode = lineNode.parentNode) {
                if (!lineNode || lineNode == cm.display.lineDiv) {
                    return null
                }
                if (lineNode.parentNode && lineNode.parentNode == cm.display.lineDiv) {
                    break
                }
            }
        }
        for (var i = 0; i < cm.display.view.length; i++) {
            var lineView = cm.display.view[i];
            if (lineView.node == lineNode) {
                return locateNodeInLineView(lineView, node, offset)
            }
        }
    }

    function locateNodeInLineView(lineView, node, offset) {
        var wrapper = lineView.text.firstChild, bad = false;
        if (!node || !contains(wrapper, node)) {
            return badPos(Pos(lineNo(lineView.line), 0), true)
        }
        if (node == wrapper) {
            bad = true;
            node = wrapper.childNodes[offset];
            offset = 0;
            if (!node) {
                var line = lineView.rest ? lst(lineView.rest) : lineView.line;
                return badPos(Pos(lineNo(line), line.text.length), bad)
            }
        }

        var textNode = node.nodeType == 3 ? node : null, topNode = node;
        if (!textNode && node.childNodes.length == 1 && node.firstChild.nodeType == 3) {
            textNode = node.firstChild;
            if (offset) {
                offset = textNode.nodeValue.length;
            }
        }
        while (topNode.parentNode != wrapper) {
            topNode = topNode.parentNode;
        }
        var measure = lineView.measure, maps = measure.maps;

        function find(textNode, topNode, offset) {
            for (var i = -1; i < (maps ? maps.length : 0); i++) {
                var map = i < 0 ? measure.map : maps[i];
                for (var j = 0; j < map.length; j += 3) {
                    var curNode = map[j+2];
                    if (curNode == textNode || curNode == topNode) {
                        var line = lineNo(i < 0 ? lineView.line : lineView.rest[i]);
                        var ch = map[j]+offset;
                        if (offset < 0 || curNode != textNode) {
                            ch = map[j+(offset ? 1 : 0)];
                        }
                        return Pos(line, ch)
                    }
                }
            }
        }

        var found = find(textNode, topNode, offset);
        if (found) {
            return badPos(found, bad)
        }

        // FIXME this is all really shaky. might handle the few cases it needs to handle, but likely to cause problems
        for (var after = topNode.nextSibling, dist = textNode ? textNode.nodeValue.length-offset : 0; after; after = after.nextSibling) {
            found = find(after, after.firstChild, 0);
            if (found) {
                return badPos(Pos(found.line, found.ch-dist), bad)
            } else {
                dist += after.textContent.length;
            }
        }
        for (var before = topNode.previousSibling, dist$1 = offset; before; before = before.previousSibling) {
            found = find(before, before.firstChild, -1);
            if (found) {
                return badPos(Pos(found.line, found.ch+dist$1), bad)
            } else {
                dist$1 += before.textContent.length;
            }
        }
    }

    // TEXTAREA INPUT STYLE

    var TextareaInput = function (cm) {
        this.cm = cm;
        // See input.poll and input.reset
        this.prevInput = "";

        // Flag that indicates whether we expect input to appear real soon
        // now (after some event like 'keypress' or 'input') and are
        // polling intensively.
        this.pollingFast = false;
        // Self-resetting timeout for the poller
        this.polling = new Delayed();
        // Used to work around IE issue with selection being forgotten when focus moves away from textarea
        this.hasSelection = false;
        this.composing = null;
    };

    TextareaInput.prototype.init = function (display) {
        var this$1 = this;

        var input = this, cm = this.cm;
        this.createField(display);
        var te = this.textarea;

        display.wrapper.insertBefore(this.wrapper, display.wrapper.firstChild);

        // Needed to hide big blue blinking cursor on Mobile Safari (doesn't seem to work in iOS 8 anymore)
        if (ios) {
            te.style.width = "0px";
        }

        on(te, "input", function () {
            if (ie && ie_version >= 9 && this$1.hasSelection) {
                this$1.hasSelection = null;
            }
            input.poll();
        });

        on(te, "paste", function (e) {
            if (signalDOMEvent(cm, e) || handlePaste(e, cm)) {
                return
            }

            cm.state.pasteIncoming = +new Date;
            input.fastPoll();
        });

        function prepareCopyCut(e) {
            if (signalDOMEvent(cm, e)) {
                return
            }
            if (cm.somethingSelected()) {
                setLastCopied({lineWise: false, text: cm.getSelections()});
            } else if (!cm.options.lineWiseCopyCut) {
                return
            } else {
                var ranges = copyableRanges(cm);
                setLastCopied({lineWise: true, text: ranges.text});
                if (e.type == "cut") {
                    cm.setSelections(ranges.ranges, null, sel_dontScroll);
                } else {
                    input.prevInput = "";
                    te.value = ranges.text.join("\n");
                    selectInput(te);
                }
            }
            if (e.type == "cut") {
                cm.state.cutIncoming = +new Date;
            }
        }

        on(te, "cut", prepareCopyCut);
        on(te, "copy", prepareCopyCut);

        on(display.scroller, "paste", function (e) {
            if (eventInWidget(display, e) || signalDOMEvent(cm, e)) {
                return
            }
            if (!te.dispatchEvent) {
                cm.state.pasteIncoming = +new Date;
                input.focus();
                return
            }

            // Pass the `paste` event to the textarea so it's handled by its event listener.
            var event = new Event("paste");
            event.clipboardData = e.clipboardData;
            te.dispatchEvent(event);
        });

        // Prevent normal selection in the editor (we handle our own)
        on(display.lineSpace, "selectstart", function (e) {
            if (!eventInWidget(display, e)) {
                e_preventDefault(e);
            }
        });

        on(te, "compositionstart", function () {
            var start = cm.getCursor("from");
            if (input.composing) {
                input.composing.range.clear();
            }
            input.composing = {
                start: start,
                range: cm.markText(start, cm.getCursor("to"), {className: "CodeMirror-composing"})
            };
        });
        on(te, "compositionend", function () {
            if (input.composing) {
                input.poll();
                input.composing.range.clear();
                input.composing = null;
            }
        });
    };

    TextareaInput.prototype.createField = function (_display) {
        // Wraps and hides input textarea
        this.wrapper = hiddenTextarea();
        // The semihidden textarea that is focused when the editor is
        // focused, and receives input.
        this.textarea = this.wrapper.firstChild;
    };

    TextareaInput.prototype.screenReaderLabelChanged = function (label) {
        // Label for screenreaders, accessibility
        if (label) {
            this.textarea.setAttribute('aria-label', label);
        } else {
            this.textarea.removeAttribute('aria-label');
        }
    };

    TextareaInput.prototype.prepareSelection = function () {
        // Redraw the selection and/or cursor
        var cm = this.cm, display = cm.display, doc = cm.doc;
        var result = prepareSelection(cm);

        // Move the hidden textarea near the cursor to prevent scrolling artifacts
        if (cm.options.moveInputWithCursor) {
            var headPos = cursorCoords(cm, doc.sel.primary().head, "div");
            var wrapOff = display.wrapper.getBoundingClientRect(), lineOff = display.lineDiv.getBoundingClientRect();
            result.teTop = Math.max(0, Math.min(display.wrapper.clientHeight-10,
                headPos.top+lineOff.top-wrapOff.top));
            result.teLeft = Math.max(0, Math.min(display.wrapper.clientWidth-10,
                headPos.left+lineOff.left-wrapOff.left));
        }

        return result
    };

    TextareaInput.prototype.showSelection = function (drawn) {
        var cm = this.cm, display = cm.display;
        removeChildrenAndAdd(display.cursorDiv, drawn.cursors);
        removeChildrenAndAdd(display.selectionDiv, drawn.selection);
        if (drawn.teTop != null) {
            this.wrapper.style.top = drawn.teTop+"px";
            this.wrapper.style.left = drawn.teLeft+"px";
        }
    };

    // Reset the input to correspond to the selection (or to be empty,
    // when not typing and nothing is selected)
    TextareaInput.prototype.reset = function (typing) {
        if (this.contextMenuPending || this.composing) {
            return
        }
        var cm = this.cm;
        if (cm.somethingSelected()) {
            this.prevInput = "";
            var content = cm.getSelection();
            this.textarea.value = content;
            if (cm.state.focused) {
                selectInput(this.textarea);
            }
            if (ie && ie_version >= 9) {
                this.hasSelection = content;
            }
        } else if (!typing) {
            this.prevInput = this.textarea.value = "";
            if (ie && ie_version >= 9) {
                this.hasSelection = null;
            }
        }
    };

    TextareaInput.prototype.getField = function () {
        return this.textarea
    };

    TextareaInput.prototype.supportsTouch = function () {
        return false
    };

    TextareaInput.prototype.focus = function () {
        if (this.cm.options.readOnly != "nocursor" && (!mobile || activeElt() != this.textarea)) {
            try {
                this.textarea.focus();
            } catch (e) {
            } // IE8 will throw if the textarea is display: none or not in DOM
        }
    };

    TextareaInput.prototype.blur = function () {
        this.textarea.blur();
    };

    TextareaInput.prototype.resetPosition = function () {
        this.wrapper.style.top = this.wrapper.style.left = 0;
    };

    TextareaInput.prototype.receivedFocus = function () {
        this.slowPoll();
    };

    // Poll for input changes, using the normal rate of polling. This
    // runs as long as the editor is focused.
    TextareaInput.prototype.slowPoll = function () {
        var this$1 = this;

        if (this.pollingFast) {
            return
        }
        this.polling.set(this.cm.options.pollInterval, function () {
            this$1.poll();
            if (this$1.cm.state.focused) {
                this$1.slowPoll();
            }
        });
    };

    // When an event has just come in that is likely to add or change
    // something in the input textarea, we poll faster, to ensure that
    // the change appears on the screen quickly.
    TextareaInput.prototype.fastPoll = function () {
        var missed = false, input = this;
        input.pollingFast = true;

        function p() {
            var changed = input.poll();
            if (!changed && !missed) {
                missed = true;
                input.polling.set(60, p);
            } else {
                input.pollingFast = false;
                input.slowPoll();
            }
        }

        input.polling.set(20, p);
    };

    // Read input from the textarea, and update the document to match.
    // When something is selected, it is present in the textarea, and
    // selected (unless it is huge, in which case a placeholder is
    // used). When nothing is selected, the cursor sits after previously
    // seen text (can be empty), which is stored in prevInput (we must
    // not reset the textarea when typing, because that breaks IME).
    TextareaInput.prototype.poll = function () {
        var this$1 = this;

        var cm = this.cm, input = this.textarea, prevInput = this.prevInput;
        // Since this is called a *lot*, try to bail out as cheaply as
        // possible when it is clear that nothing happened. hasSelection
        // will be the case when there is a lot of text in the textarea,
        // in which case reading its value would be expensive.
        if (this.contextMenuPending || !cm.state.focused ||
            (hasSelection(input) && !prevInput && !this.composing) ||
            cm.isReadOnly() || cm.options.disableInput || cm.state.keySeq) {
            return false
        }

        var text = input.value;
        // If nothing changed, bail.
        if (text == prevInput && !cm.somethingSelected()) {
            return false
        }
        // Work around nonsensical selection resetting in IE9/10, and
        // inexplicable appearance of private area unicode characters on
        // some key combos in Mac (#2689).
        if (ie && ie_version >= 9 && this.hasSelection === text ||
            mac && /[\uf700-\uf7ff]/.test(text)) {
            cm.display.input.reset();
            return false
        }

        if (cm.doc.sel == cm.display.selForContextMenu) {
            var first = text.charCodeAt(0);
            if (first == 0x200b && !prevInput) {
                prevInput = "\u200b";
            }
            if (first == 0x21da) {
                this.reset();
                return this.cm.execCommand("undo")
            }
        }
        // Find the part of the input that is actually new
        var same = 0, l = Math.min(prevInput.length, text.length);
        while (same < l && prevInput.charCodeAt(same) == text.charCodeAt(same)) {
            ++same;
        }

        runInOp(cm, function () {
            applyTextInput(cm, text.slice(same), prevInput.length-same,
                null, this$1.composing ? "*compose" : null);

            // Don't leave long text in the textarea, since it makes further polling slow
            if (text.length > 1000 || text.indexOf("\n") > -1) {
                input.value = this$1.prevInput = "";
            } else {
                this$1.prevInput = text;
            }

            if (this$1.composing) {
                this$1.composing.range.clear();
                this$1.composing.range = cm.markText(this$1.composing.start, cm.getCursor("to"),
                    {className: "CodeMirror-composing"});
            }
        });
        return true
    };

    TextareaInput.prototype.ensurePolled = function () {
        if (this.pollingFast && this.poll()) {
            this.pollingFast = false;
        }
    };

    TextareaInput.prototype.onKeyPress = function () {
        if (ie && ie_version >= 9) {
            this.hasSelection = null;
        }
        this.fastPoll();
    };

    TextareaInput.prototype.onContextMenu = function (e) {
        var input = this, cm = input.cm, display = cm.display, te = input.textarea;
        if (input.contextMenuPending) {
            input.contextMenuPending();
        }
        var pos = posFromMouse(cm, e), scrollPos = display.scroller.scrollTop;
        if (!pos || presto) {
            return
        } // Opera is difficult.

        // Reset the current text selection only if the click is done outside of the selection
        // and 'resetSelectionOnContextMenu' option is true.
        var reset = cm.options.resetSelectionOnContextMenu;
        if (reset && cm.doc.sel.contains(pos) == -1) {
            operation(cm, setSelection)(cm.doc, simpleSelection(pos), sel_dontScroll);
        }

        var oldCSS = te.style.cssText, oldWrapperCSS = input.wrapper.style.cssText;
        var wrapperBox = input.wrapper.offsetParent.getBoundingClientRect();
        input.wrapper.style.cssText = "position: static";
        te.style.cssText = "position: absolute; width: 30px; height: 30px;\n      top: "+(e.clientY-wrapperBox.top-5)+"px; left: "+(e.clientX-wrapperBox.left-5)+"px;\n      z-index: 1000; background: "+(ie ? "rgba(255, 255, 255, .05)" : "transparent")+";\n      outline: none; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);";
        var oldScrollY;
        if (webkit) {
            oldScrollY = window.scrollY;
        } // Work around Chrome issue (#2712)
        display.input.focus();
        if (webkit) {
            window.scrollTo(null, oldScrollY);
        }
        display.input.reset();
        // Adds "Select all" to context menu in FF
        if (!cm.somethingSelected()) {
            te.value = input.prevInput = " ";
        }
        input.contextMenuPending = rehide;
        display.selForContextMenu = cm.doc.sel;
        clearTimeout(display.detectingSelectAll);

        // Select-all will be greyed out if there's nothing to select, so
        // this adds a zero-width space so that we can later check whether
        // it got selected.
        function prepareSelectAllHack() {
            if (te.selectionStart != null) {
                var selected = cm.somethingSelected();
                var extval = "\u200b"+(selected ? te.value : "");
                te.value = "\u21da"; // Used to catch context-menu undo
                te.value = extval;
                input.prevInput = selected ? "" : "\u200b";
                te.selectionStart = 1;
                te.selectionEnd = extval.length;
                // Re-set this, in case some other handler touched the
                // selection in the meantime.
                display.selForContextMenu = cm.doc.sel;
            }
        }

        function rehide() {
            if (input.contextMenuPending != rehide) {
                return
            }
            input.contextMenuPending = false;
            input.wrapper.style.cssText = oldWrapperCSS;
            te.style.cssText = oldCSS;
            if (ie && ie_version < 9) {
                display.scrollbars.setScrollTop(display.scroller.scrollTop = scrollPos);
            }

            // Try to detect the user choosing select-all
            if (te.selectionStart != null) {
                if (!ie || (ie && ie_version < 9)) {
                    prepareSelectAllHack();
                }
                var i = 0, poll = function () {
                    if (display.selForContextMenu == cm.doc.sel && te.selectionStart == 0 &&
                        te.selectionEnd > 0 && input.prevInput == "\u200b") {
                        operation(cm, selectAll)(cm);
                    } else if (i++ < 10) {
                        display.detectingSelectAll = setTimeout(poll, 500);
                    } else {
                        display.selForContextMenu = null;
                        display.input.reset();
                    }
                };
                display.detectingSelectAll = setTimeout(poll, 200);
            }
        }

        if (ie && ie_version >= 9) {
            prepareSelectAllHack();
        }
        if (captureRightClick) {
            e_stop(e);
            var mouseup = function () {
                off(window, "mouseup", mouseup);
                setTimeout(rehide, 20);
            };
            on(window, "mouseup", mouseup);
        } else {
            setTimeout(rehide, 50);
        }
    };

    TextareaInput.prototype.readOnlyChanged = function (val) {
        if (!val) {
            this.reset();
        }
        this.textarea.disabled = val == "nocursor";
        this.textarea.readOnly = !!val;
    };

    TextareaInput.prototype.setUneditable = function () {
    };

    TextareaInput.prototype.needsContentAttribute = false;

    function fromTextArea(textarea, options) {
        options = options ? copyObj(options) : {};
        options.value = textarea.value;
        if (!options.tabindex && textarea.tabIndex) {
            options.tabindex = textarea.tabIndex;
        }
        if (!options.placeholder && textarea.placeholder) {
            options.placeholder = textarea.placeholder;
        }
        // Set autofocus to true if this textarea is focused, or if it has
        // autofocus and no other element is focused.
        if (options.autofocus == null) {
            var hasFocus = activeElt();
            options.autofocus = hasFocus == textarea ||
                textarea.getAttribute("autofocus") != null && hasFocus == document.body;
        }

        function save() {
            textarea.value = cm.getValue();
        }

        var realSubmit;
        if (textarea.form) {
            on(textarea.form, "submit", save);
            // Deplorable hack to make the submit method do the right thing.
            if (!options.leaveSubmitMethodAlone) {
                var form = textarea.form;
                realSubmit = form.submit;
                try {
                    var wrappedSubmit = form.submit = function () {
                        save();
                        form.submit = realSubmit;
                        form.submit();
                        form.submit = wrappedSubmit;
                    };
                } catch (e) {
                }
            }
        }

        options.finishInit = function (cm) {
            cm.save = save;
            cm.getTextArea = function () {
                return textarea;
            };
            cm.toTextArea = function () {
                cm.toTextArea = isNaN; // Prevent this from being ran twice
                save();
                textarea.parentNode.removeChild(cm.getWrapperElement());
                textarea.style.display = "";
                if (textarea.form) {
                    off(textarea.form, "submit", save);
                    if (!options.leaveSubmitMethodAlone && typeof textarea.form.submit == "function") {
                        textarea.form.submit = realSubmit;
                    }
                }
            };
        };

        textarea.style.display = "none";
        var cm = CodeMirror(function (node) {
                return textarea.parentNode.insertBefore(node, textarea.nextSibling);
            },
            options);
        return cm
    }

    function addLegacyProps(CodeMirror) {
        CodeMirror.off = off;
        CodeMirror.on = on;
        CodeMirror.wheelEventPixels = wheelEventPixels;
        CodeMirror.Doc = Doc;
        CodeMirror.splitLines = splitLinesAuto;
        CodeMirror.countColumn = countColumn;
        CodeMirror.findColumn = findColumn;
        CodeMirror.isWordChar = isWordCharBasic;
        CodeMirror.Pass = Pass;
        CodeMirror.signal = signal;
        CodeMirror.Line = Line;
        CodeMirror.changeEnd = changeEnd;
        CodeMirror.scrollbarModel = scrollbarModel;
        CodeMirror.Pos = Pos;
        CodeMirror.cmpPos = cmp;
        CodeMirror.modes = modes;
        CodeMirror.mimeModes = mimeModes;
        CodeMirror.resolveMode = resolveMode;
        CodeMirror.getMode = getMode;
        CodeMirror.modeExtensions = modeExtensions;
        CodeMirror.extendMode = extendMode;
        CodeMirror.copyState = copyState;
        CodeMirror.startState = startState;
        CodeMirror.innerMode = innerMode;
        CodeMirror.commands = commands;
        CodeMirror.keyMap = keyMap;
        CodeMirror.keyName = keyName;
        CodeMirror.isModifierKey = isModifierKey;
        CodeMirror.lookupKey = lookupKey;
        CodeMirror.normalizeKeyMap = normalizeKeyMap;
        CodeMirror.StringStream = StringStream;
        CodeMirror.SharedTextMarker = SharedTextMarker;
        CodeMirror.TextMarker = TextMarker;
        CodeMirror.LineWidget = LineWidget;
        CodeMirror.e_preventDefault = e_preventDefault;
        CodeMirror.e_stopPropagation = e_stopPropagation;
        CodeMirror.e_stop = e_stop;
        CodeMirror.addClass = addClass;
        CodeMirror.contains = contains;
        CodeMirror.rmClass = rmClass;
        CodeMirror.keyNames = keyNames;
    }

    // EDITOR CONSTRUCTOR

    defineOptions(CodeMirror);

    addEditorMethods(CodeMirror);

    // Set up methods on CodeMirror's prototype to redirect to the editor's document.
    var dontDelegate = "iter insert remove copy getEditor constructor".split(" ");
    for (var prop in Doc.prototype) {
        if (Doc.prototype.hasOwnProperty(prop) && indexOf(dontDelegate, prop) < 0) {
            CodeMirror.prototype[prop] = (function (method) {
                return function () {
                    return method.apply(this.doc, arguments)
                }
            })(Doc.prototype[prop]);
        }
    }

    eventMixin(Doc);
    CodeMirror.inputStyles = {"textarea": TextareaInput, "contenteditable": ContentEditableInput};

    // Extra arguments are stored as the mode's dependencies, which is
    // used by (legacy) mechanisms like loadmode.js to automatically
    // load a mode. (Preferred mechanism is the require/define calls.)
    CodeMirror.defineMode = function (name/*, mode, …*/) {
        if (!CodeMirror.defaults.mode && name != "null") {
            CodeMirror.defaults.mode = name;
        }
        defineMode.apply(this, arguments);
    };

    CodeMirror.defineMIME = defineMIME;

    // Minimal default mode.
    CodeMirror.defineMode("null", function () {
        return ({
            token: function (stream) {
                return stream.skipToEnd();
            }
        });
    });
    CodeMirror.defineMIME("text/plain", "null");

    // EXTENSIONS

    CodeMirror.defineExtension = function (name, func) {
        CodeMirror.prototype[name] = func;
    };
    CodeMirror.defineDocExtension = function (name, func) {
        Doc.prototype[name] = func;
    };

    CodeMirror.fromTextArea = fromTextArea;

    addLegacyProps(CodeMirror);

    CodeMirror.version = "5.65.3";

    return CodeMirror;

})));

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    function Context(indented, column, type, info, align, prev) {
        this.indented = indented;
        this.column = column;
        this.type = type;
        this.info = info;
        this.align = align;
        this.prev = prev;
    }

    function pushContext(state, col, type, info) {
        var indent = state.indented;
        if (state.context && state.context.type == "statement" && type != "statement")
            indent = state.context.indented;
        return state.context = new Context(indent, col, type, info, null, state.context);
    }

    function popContext(state) {
        var t = state.context.type;
        if (t == ")" || t == "]" || t == "}")
            state.indented = state.context.indented;
        return state.context = state.context.prev;
    }

    function typeBefore(stream, state, pos) {
        if (state.prevToken == "variable" || state.prevToken == "type") return true;
        if (/\S(?:[^- ]>|[*\]])\s*$|\*$/.test(stream.string.slice(0, pos))) return true;
        if (state.typeAtEndOfLine && stream.column() == stream.indentation()) return true;
    }

    function isTopScope(context) {
        for (; ;) {
            if (!context || context.type == "top") return true;
            if (context.type == "}" && context.prev.info != "namespace") return false;
            context = context.prev;
        }
    }

    CodeMirror.defineMode("clike", function (config, parserConfig) {
        var indentUnit = config.indentUnit,
            statementIndentUnit = parserConfig.statementIndentUnit || indentUnit,
            dontAlignCalls = parserConfig.dontAlignCalls,
            keywords = parserConfig.keywords || {},
            types = parserConfig.types || {},
            builtin = parserConfig.builtin || {},
            blockKeywords = parserConfig.blockKeywords || {},
            defKeywords = parserConfig.defKeywords || {},
            atoms = parserConfig.atoms || {},
            hooks = parserConfig.hooks || {},
            multiLineStrings = parserConfig.multiLineStrings,
            indentStatements = parserConfig.indentStatements !== false,
            indentSwitch = parserConfig.indentSwitch !== false,
            namespaceSeparator = parserConfig.namespaceSeparator,
            isPunctuationChar = parserConfig.isPunctuationChar || /[\[\]{}\(\),;\:\.]/,
            numberStart = parserConfig.numberStart || /[\d\.]/,
            number = parserConfig.number || /^(?:0x[a-f\d]+|0b[01]+|(?:\d+\.?\d*|\.\d+)(?:e[-+]?\d+)?)(u|ll?|l|f)?/i,
            isOperatorChar = parserConfig.isOperatorChar || /[+\-*&%=<>!?|\/]/,
            isIdentifierChar = parserConfig.isIdentifierChar || /[\w\$_\xa1-\uffff]/,
            // An optional function that takes a {string} token and returns true if it
            // should be treated as a builtin.
            isReservedIdentifier = parserConfig.isReservedIdentifier || false;

        var curPunc, isDefKeyword;

        function tokenBase(stream, state) {
            var ch = stream.next();
            if (hooks[ch]) {
                var result = hooks[ch](stream, state);
                if (result !== false) return result;
            }
            if (ch == '"' || ch == "'") {
                state.tokenize = tokenString(ch);
                return state.tokenize(stream, state);
            }
            if (numberStart.test(ch)) {
                stream.backUp(1)
                if (stream.match(number)) return "number"
                stream.next()
            }
            if (isPunctuationChar.test(ch)) {
                curPunc = ch;
                return null;
            }
            if (ch == "/") {
                if (stream.eat("*")) {
                    state.tokenize = tokenComment;
                    return tokenComment(stream, state);
                }
                if (stream.eat("/")) {
                    stream.skipToEnd();
                    return "comment";
                }
            }
            if (isOperatorChar.test(ch)) {
                while (!stream.match(/^\/[\/*]/, false) && stream.eat(isOperatorChar)) {
                }
                return "operator";
            }
            stream.eatWhile(isIdentifierChar);
            if (namespaceSeparator) while (stream.match(namespaceSeparator))
                stream.eatWhile(isIdentifierChar);

            var cur = stream.current();
            if (contains(keywords, cur)) {
                if (contains(blockKeywords, cur)) curPunc = "newstatement";
                if (contains(defKeywords, cur)) isDefKeyword = true;
                return "keyword";
            }
            if (contains(types, cur)) return "type";
            if (contains(builtin, cur)
                || (isReservedIdentifier && isReservedIdentifier(cur))) {
                if (contains(blockKeywords, cur)) curPunc = "newstatement";
                return "builtin";
            }
            if (contains(atoms, cur)) return "atom";
            return "variable";
        }

        function tokenString(quote) {
            return function (stream, state) {
                var escaped = false, next, end = false;
                while ((next = stream.next()) != null) {
                    if (next == quote && !escaped) {
                        end = true;
                        break;
                    }
                    escaped = !escaped && next == "\\";
                }
                if (end || !(escaped || multiLineStrings))
                    state.tokenize = null;
                return "string";
            };
        }

        function tokenComment(stream, state) {
            var maybeEnd = false, ch;
            while (ch = stream.next()) {
                if (ch == "/" && maybeEnd) {
                    state.tokenize = null;
                    break;
                }
                maybeEnd = (ch == "*");
            }
            return "comment";
        }

        function maybeEOL(stream, state) {
            if (parserConfig.typeFirstDefinitions && stream.eol() && isTopScope(state.context))
                state.typeAtEndOfLine = typeBefore(stream, state, stream.pos)
        }

        // Interface

        return {
            startState: function (basecolumn) {
                return {
                    tokenize: null,
                    context: new Context((basecolumn || 0)-indentUnit, 0, "top", null, false),
                    indented: 0,
                    startOfLine: true,
                    prevToken: null
                };
            },

            token: function (stream, state) {
                var ctx = state.context;
                if (stream.sol()) {
                    if (ctx.align == null) ctx.align = false;
                    state.indented = stream.indentation();
                    state.startOfLine = true;
                }
                if (stream.eatSpace()) {
                    maybeEOL(stream, state);
                    return null;
                }
                curPunc = isDefKeyword = null;
                var style = (state.tokenize || tokenBase)(stream, state);
                if (style == "comment" || style == "meta") return style;
                if (ctx.align == null) ctx.align = true;

                if (curPunc == ";" || curPunc == ":" || (curPunc == "," && stream.match(/^\s*(?:\/\/.*)?$/, false)))
                    while (state.context.type == "statement") popContext(state);
                else if (curPunc == "{") pushContext(state, stream.column(), "}");
                else if (curPunc == "[") pushContext(state, stream.column(), "]");
                else if (curPunc == "(") pushContext(state, stream.column(), ")");
                else if (curPunc == "}") {
                    while (ctx.type == "statement") ctx = popContext(state);
                    if (ctx.type == "}") ctx = popContext(state);
                    while (ctx.type == "statement") ctx = popContext(state);
                } else if (curPunc == ctx.type) popContext(state);
                else if (indentStatements &&
                    (((ctx.type == "}" || ctx.type == "top") && curPunc != ";") ||
                        (ctx.type == "statement" && curPunc == "newstatement"))) {
                    pushContext(state, stream.column(), "statement", stream.current());
                }

                if (style == "variable" &&
                    ((state.prevToken == "def" ||
                        (parserConfig.typeFirstDefinitions && typeBefore(stream, state, stream.start) &&
                            isTopScope(state.context) && stream.match(/^\s*\(/, false)))))
                    style = "def";

                if (hooks.token) {
                    var result = hooks.token(stream, state, style);
                    if (result !== undefined) style = result;
                }

                if (style == "def" && parserConfig.styleDefs === false) style = "variable";

                state.startOfLine = false;
                state.prevToken = isDefKeyword ? "def" : style || curPunc;
                maybeEOL(stream, state);
                return style;
            },

            indent: function (state, textAfter) {
                if (state.tokenize != tokenBase && state.tokenize != null || state.typeAtEndOfLine) return CodeMirror.Pass;
                var ctx = state.context, firstChar = textAfter && textAfter.charAt(0);
                var closing = firstChar == ctx.type;
                if (ctx.type == "statement" && firstChar == "}") ctx = ctx.prev;
                if (parserConfig.dontIndentStatements)
                    while (ctx.type == "statement" && parserConfig.dontIndentStatements.test(ctx.info))
                        ctx = ctx.prev
                if (hooks.indent) {
                    var hook = hooks.indent(state, ctx, textAfter, indentUnit);
                    if (typeof hook == "number") return hook
                }
                var switchBlock = ctx.prev && ctx.prev.info == "switch";
                if (parserConfig.allmanIndentation && /[{(]/.test(firstChar)) {
                    while (ctx.type != "top" && ctx.type != "}") ctx = ctx.prev
                    return ctx.indented
                }
                if (ctx.type == "statement")
                    return ctx.indented+(firstChar == "{" ? 0 : statementIndentUnit);
                if (ctx.align && (!dontAlignCalls || ctx.type != ")"))
                    return ctx.column+(closing ? 0 : 1);
                if (ctx.type == ")" && !closing)
                    return ctx.indented+statementIndentUnit;

                return ctx.indented+(closing ? 0 : indentUnit)+
                    (!closing && switchBlock && !/^(?:case|default)\b/.test(textAfter) ? indentUnit : 0);
            },

            electricInput: indentSwitch ? /^\s*(?:case .*?:|default:|\{\}?|\})$/ : /^\s*[{}]$/,
            blockCommentStart: "/*",
            blockCommentEnd: "*/",
            blockCommentContinue: " * ",
            lineComment: "//",
            fold: "brace"
        };
    });

    function words(str) {
        var obj = {}, words = str.split(" ");
        for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
        return obj;
    }

    function contains(words, word) {
        if (typeof words === "function") {
            return words(word);
        } else {
            return words.propertyIsEnumerable(word);
        }
    }

    var cKeywords = "auto if break case register continue return default do sizeof "+
        "static else struct switch extern typedef union for goto while enum const "+
        "volatile inline restrict asm fortran";

    // Keywords from https://en.cppreference.com/w/cpp/keyword includes C++20.
    var cppKeywords = "alignas alignof and and_eq audit axiom bitand bitor catch "+
        "class compl concept constexpr const_cast decltype delete dynamic_cast "+
        "explicit export final friend import module mutable namespace new noexcept "+
        "not not_eq operator or or_eq override private protected public "+
        "reinterpret_cast requires static_assert static_cast template this "+
        "thread_local throw try typeid typename using virtual xor xor_eq";

    var objCKeywords = "bycopy byref in inout oneway out self super atomic nonatomic retain copy "+
        "readwrite readonly strong weak assign typeof nullable nonnull null_resettable _cmd "+
        "@interface @implementation @end @protocol @encode @property @synthesize @dynamic @class "+
        "@public @package @private @protected @required @optional @try @catch @finally @import "+
        "@selector @encode @defs @synchronized @autoreleasepool @compatibility_alias @available";

    var objCBuiltins = "FOUNDATION_EXPORT FOUNDATION_EXTERN NS_INLINE NS_FORMAT_FUNCTION "+
        " NS_RETURNS_RETAINEDNS_ERROR_ENUM NS_RETURNS_NOT_RETAINED NS_RETURNS_INNER_POINTER "+
        "NS_DESIGNATED_INITIALIZER NS_ENUM NS_OPTIONS NS_REQUIRES_NIL_TERMINATION "+
        "NS_ASSUME_NONNULL_BEGIN NS_ASSUME_NONNULL_END NS_SWIFT_NAME NS_REFINED_FOR_SWIFT"

    // Do not use this. Use the cTypes function below. This is global just to avoid
    // excessive calls when cTypes is being called multiple times during a parse.
    var basicCTypes = words("int long char short double float unsigned signed "+
        "void bool");

    // Do not use this. Use the objCTypes function below. This is global just to avoid
    // excessive calls when objCTypes is being called multiple times during a parse.
    var basicObjCTypes = words("SEL instancetype id Class Protocol BOOL");

    // Returns true if identifier is a "C" type.
    // C type is defined as those that are reserved by the compiler (basicTypes),
    // and those that end in _t (Reserved by POSIX for types)
    // http://www.gnu.org/software/libc/manual/html_node/Reserved-Names.html
    function cTypes(identifier) {
        return contains(basicCTypes, identifier) || /.+_t$/.test(identifier);
    }

    // Returns true if identifier is a "Objective C" type.
    function objCTypes(identifier) {
        return cTypes(identifier) || contains(basicObjCTypes, identifier);
    }

    var cBlockKeywords = "case do else for if switch while struct enum union";
    var cDefKeywords = "struct enum union";

    function cppHook(stream, state) {
        if (!state.startOfLine) return false
        for (var ch, next = null; ch = stream.peek();) {
            if (ch == "\\" && stream.match(/^.$/)) {
                next = cppHook
                break
            } else if (ch == "/" && stream.match(/^\/[\/\*]/, false)) {
                break
            }
            stream.next()
        }
        state.tokenize = next
        return "meta"
    }

    function pointerHook(_stream, state) {
        if (state.prevToken == "type") return "type";
        return false;
    }

    // For C and C++ (and ObjC): identifiers starting with __
    // or _ followed by a capital letter are reserved for the compiler.
    function cIsReservedIdentifier(token) {
        if (!token || token.length < 2) return false;
        if (token[0] != '_') return false;
        return (token[1] == '_') || (token[1] !== token[1].toLowerCase());
    }

    function cpp14Literal(stream) {
        stream.eatWhile(/[\w\.']/);
        return "number";
    }

    function cpp11StringHook(stream, state) {
        stream.backUp(1);
        // Raw strings.
        if (stream.match(/^(?:R|u8R|uR|UR|LR)/)) {
            var match = stream.match(/^"([^\s\\()]{0,16})\(/);
            if (!match) {
                return false;
            }
            state.cpp11RawStringDelim = match[1];
            state.tokenize = tokenRawString;
            return tokenRawString(stream, state);
        }
        // Unicode strings/chars.
        if (stream.match(/^(?:u8|u|U|L)/)) {
            if (stream.match(/^["']/, /* eat */ false)) {
                return "string";
            }
            return false;
        }
        // Ignore this hook.
        stream.next();
        return false;
    }

    function cppLooksLikeConstructor(word) {
        var lastTwo = /(\w+)::~?(\w+)$/.exec(word);
        return lastTwo && lastTwo[1] == lastTwo[2];
    }

    // C#-style strings where "" escapes a quote.
    function tokenAtString(stream, state) {
        var next;
        while ((next = stream.next()) != null) {
            if (next == '"' && !stream.eat('"')) {
                state.tokenize = null;
                break;
            }
        }
        return "string";
    }

    // C++11 raw string literal is <prefix>"<delim>( anything )<delim>", where
    // <delim> can be a string up to 16 characters long.
    function tokenRawString(stream, state) {
        // Escape characters that have special regex meanings.
        var delim = state.cpp11RawStringDelim.replace(/[^\w\s]/g, '\\$&');
        var match = stream.match(new RegExp(".*?\\)"+delim+'"'));
        if (match)
            state.tokenize = null;
        else
            stream.skipToEnd();
        return "string";
    }

    function def(mimes, mode) {
        if (typeof mimes == "string") mimes = [mimes];
        var words = [];

        function add(obj) {
            if (obj) for (var prop in obj) if (obj.hasOwnProperty(prop))
                words.push(prop);
        }

        add(mode.keywords);
        add(mode.types);
        add(mode.builtin);
        add(mode.atoms);
        if (words.length) {
            mode.helperType = mimes[0];
            CodeMirror.registerHelper("hintWords", mimes[0], words);
        }

        for (var i = 0; i < mimes.length; ++i)
            CodeMirror.defineMIME(mimes[i], mode);
    }

    def(["text/x-csrc", "text/x-c", "text/x-chdr"], {
        name: "clike",
        keywords: words(cKeywords),
        types: cTypes,
        blockKeywords: words(cBlockKeywords),
        defKeywords: words(cDefKeywords),
        typeFirstDefinitions: true,
        atoms: words("NULL true false"),
        isReservedIdentifier: cIsReservedIdentifier,
        hooks: {
            "#": cppHook,
            "*": pointerHook,
        },
        modeProps: {fold: ["brace", "include"]}
    });

    def(["text/x-c++src", "text/x-c++hdr"], {
        name: "clike",
        keywords: words(cKeywords+" "+cppKeywords),
        types: cTypes,
        blockKeywords: words(cBlockKeywords+" class try catch"),
        defKeywords: words(cDefKeywords+" class namespace"),
        typeFirstDefinitions: true,
        atoms: words("true false NULL nullptr"),
        dontIndentStatements: /^template$/,
        isIdentifierChar: /[\w\$_~\xa1-\uffff]/,
        isReservedIdentifier: cIsReservedIdentifier,
        hooks: {
            "#": cppHook,
            "*": pointerHook,
            "u": cpp11StringHook,
            "U": cpp11StringHook,
            "L": cpp11StringHook,
            "R": cpp11StringHook,
            "0": cpp14Literal,
            "1": cpp14Literal,
            "2": cpp14Literal,
            "3": cpp14Literal,
            "4": cpp14Literal,
            "5": cpp14Literal,
            "6": cpp14Literal,
            "7": cpp14Literal,
            "8": cpp14Literal,
            "9": cpp14Literal,
            token: function (stream, state, style) {
                if (style == "variable" && stream.peek() == "(" &&
                    (state.prevToken == ";" || state.prevToken == null ||
                        state.prevToken == "}") &&
                    cppLooksLikeConstructor(stream.current()))
                    return "def";
            }
        },
        namespaceSeparator: "::",
        modeProps: {fold: ["brace", "include"]}
    });

    def("text/x-java", {
        name: "clike",
        keywords: words("abstract assert break case catch class const continue default "+
            "do else enum extends final finally for goto if implements import "+
            "instanceof interface native new package private protected public "+
            "return static strictfp super switch synchronized this throw throws transient "+
            "try volatile while @interface"),
        types: words("var byte short int long float double boolean char void Boolean Byte Character Double Float "+
            "Integer Long Number Object Short String StringBuffer StringBuilder Void"),
        blockKeywords: words("catch class do else finally for if switch try while"),
        defKeywords: words("class interface enum @interface"),
        typeFirstDefinitions: true,
        atoms: words("true false null"),
        number: /^(?:0x[a-f\d_]+|0b[01_]+|(?:[\d_]+\.?\d*|\.\d+)(?:e[-+]?[\d_]+)?)(u|ll?|l|f)?/i,
        hooks: {
            "@": function (stream) {
                // Don't match the @interface keyword.
                if (stream.match('interface', false)) return false;

                stream.eatWhile(/[\w\$_]/);
                return "meta";
            },
            '"': function (stream, state) {
                if (!stream.match(/""$/)) return false;
                state.tokenize = tokenTripleString;
                return state.tokenize(stream, state);
            }
        },
        modeProps: {fold: ["brace", "import"]}
    });

    def("text/x-csharp", {
        name: "clike",
        keywords: words("abstract as async await base break case catch checked class const continue"+
            " default delegate do else enum event explicit extern finally fixed for"+
            " foreach goto if implicit in interface internal is lock namespace new"+
            " operator out override params private protected public readonly ref return sealed"+
            " sizeof stackalloc static struct switch this throw try typeof unchecked"+
            " unsafe using virtual void volatile while add alias ascending descending dynamic from get"+
            " global group into join let orderby partial remove select set value var yield"),
        types: words("Action Boolean Byte Char DateTime DateTimeOffset Decimal Double Func"+
            " Guid Int16 Int32 Int64 Object SByte Single String Task TimeSpan UInt16 UInt32"+
            " UInt64 bool byte char decimal double short int long object"+
            " sbyte float string ushort uint ulong"),
        blockKeywords: words("catch class do else finally for foreach if struct switch try while"),
        defKeywords: words("class interface namespace struct var"),
        typeFirstDefinitions: true,
        atoms: words("true false null"),
        hooks: {
            "@": function (stream, state) {
                if (stream.eat('"')) {
                    state.tokenize = tokenAtString;
                    return tokenAtString(stream, state);
                }
                stream.eatWhile(/[\w\$_]/);
                return "meta";
            }
        }
    });

    function tokenTripleString(stream, state) {
        var escaped = false;
        while (!stream.eol()) {
            if (!escaped && stream.match('"""')) {
                state.tokenize = null;
                break;
            }
            escaped = stream.next() == "\\" && !escaped;
        }
        return "string";
    }

    function tokenNestedComment(depth) {
        return function (stream, state) {
            var ch
            while (ch = stream.next()) {
                if (ch == "*" && stream.eat("/")) {
                    if (depth == 1) {
                        state.tokenize = null
                        break
                    } else {
                        state.tokenize = tokenNestedComment(depth-1)
                        return state.tokenize(stream, state)
                    }
                } else if (ch == "/" && stream.eat("*")) {
                    state.tokenize = tokenNestedComment(depth+1)
                    return state.tokenize(stream, state)
                }
            }
            return "comment"
        }
    }

    def("text/x-scala", {
        name: "clike",
        keywords: words(
            /* scala */
            "abstract case catch class def do else extends final finally for forSome if "+
            "implicit import lazy match new null object override package private protected return "+
            "sealed super this throw trait try type val var while with yield _ "+

            /* package scala */
            "assert assume require print println printf readLine readBoolean readByte readShort "+
            "readChar readInt readLong readFloat readDouble"
        ),
        types: words(
            "AnyVal App Application Array BufferedIterator BigDecimal BigInt Char Console Either "+
            "Enumeration Equiv Error Exception Fractional Function IndexedSeq Int Integral Iterable "+
            "Iterator List Map Numeric Nil NotNull Option Ordered Ordering PartialFunction PartialOrdering "+
            "Product Proxy Range Responder Seq Serializable Set Specializable Stream StringBuilder "+
            "StringContext Symbol Throwable Traversable TraversableOnce Tuple Unit Vector "+

            /* package java.lang */
            "Boolean Byte Character CharSequence Class ClassLoader Cloneable Comparable "+
            "Compiler Double Exception Float Integer Long Math Number Object Package Pair Process "+
            "Runtime Runnable SecurityManager Short StackTraceElement StrictMath String "+
            "StringBuffer System Thread ThreadGroup ThreadLocal Throwable Triple Void"
        ),
        multiLineStrings: true,
        blockKeywords: words("catch class enum do else finally for forSome if match switch try while"),
        defKeywords: words("class enum def object package trait type val var"),
        atoms: words("true false null"),
        indentStatements: false,
        indentSwitch: false,
        isOperatorChar: /[+\-*&%=<>!?|\/#:@]/,
        hooks: {
            "@": function (stream) {
                stream.eatWhile(/[\w\$_]/);
                return "meta";
            },
            '"': function (stream, state) {
                if (!stream.match('""')) return false;
                state.tokenize = tokenTripleString;
                return state.tokenize(stream, state);
            },
            "'": function (stream) {
                stream.eatWhile(/[\w\$_\xa1-\uffff]/);
                return "atom";
            },
            "=": function (stream, state) {
                var cx = state.context
                if (cx.type == "}" && cx.align && stream.eat(">")) {
                    state.context = new Context(cx.indented, cx.column, cx.type, cx.info, null, cx.prev)
                    return "operator"
                } else {
                    return false
                }
            },

            "/": function (stream, state) {
                if (!stream.eat("*")) return false
                state.tokenize = tokenNestedComment(1)
                return state.tokenize(stream, state)
            }
        },
        modeProps: {closeBrackets: {pairs: '()[]{}""', triples: '"'}}
    });

    function tokenKotlinString(tripleString) {
        return function (stream, state) {
            var escaped = false, next, end = false;
            while (!stream.eol()) {
                if (!tripleString && !escaped && stream.match('"')) {
                    end = true;
                    break;
                }
                if (tripleString && stream.match('"""')) {
                    end = true;
                    break;
                }
                next = stream.next();
                if (!escaped && next == "$" && stream.match('{'))
                    stream.skipTo("}");
                escaped = !escaped && next == "\\" && !tripleString;
            }
            if (end || !tripleString)
                state.tokenize = null;
            return "string";
        }
    }

    def("text/x-kotlin", {
        name: "clike",
        keywords: words(
            /*keywords*/
            "package as typealias class interface this super val operator "+
            "var fun for is in This throw return annotation "+
            "break continue object if else while do try when !in !is as? "+

            /*soft keywords*/
            "file import where by get set abstract enum open inner override private public internal "+
            "protected catch finally out final vararg reified dynamic companion constructor init "+
            "sealed field property receiver param sparam lateinit data inline noinline tailrec "+
            "external annotation crossinline const operator infix suspend actual expect setparam value"
        ),
        types: words(
            /* package java.lang */
            "Boolean Byte Character CharSequence Class ClassLoader Cloneable Comparable "+
            "Compiler Double Exception Float Integer Long Math Number Object Package Pair Process "+
            "Runtime Runnable SecurityManager Short StackTraceElement StrictMath String "+
            "StringBuffer System Thread ThreadGroup ThreadLocal Throwable Triple Void Annotation Any BooleanArray "+
            "ByteArray Char CharArray DeprecationLevel DoubleArray Enum FloatArray Function Int IntArray Lazy "+
            "LazyThreadSafetyMode LongArray Nothing ShortArray Unit"
        ),
        intendSwitch: false,
        indentStatements: false,
        multiLineStrings: true,
        number: /^(?:0x[a-f\d_]+|0b[01_]+|(?:[\d_]+(\.\d+)?|\.\d+)(?:e[-+]?[\d_]+)?)(u|ll?|l|f)?/i,
        blockKeywords: words("catch class do else finally for if where try while enum"),
        defKeywords: words("class val var object interface fun"),
        atoms: words("true false null this"),
        hooks: {
            "@": function (stream) {
                stream.eatWhile(/[\w\$_]/);
                return "meta";
            },
            '*': function (_stream, state) {
                return state.prevToken == '.' ? 'variable' : 'operator';
            },
            '"': function (stream, state) {
                state.tokenize = tokenKotlinString(stream.match('""'));
                return state.tokenize(stream, state);
            },
            "/": function (stream, state) {
                if (!stream.eat("*")) return false;
                state.tokenize = tokenNestedComment(1);
                return state.tokenize(stream, state)
            },
            indent: function (state, ctx, textAfter, indentUnit) {
                var firstChar = textAfter && textAfter.charAt(0);
                if ((state.prevToken == "}" || state.prevToken == ")") && textAfter == "")
                    return state.indented;
                if ((state.prevToken == "operator" && textAfter != "}" && state.context.type != "}") ||
                    state.prevToken == "variable" && firstChar == "." ||
                    (state.prevToken == "}" || state.prevToken == ")") && firstChar == ".")
                    return indentUnit * 2+ctx.indented;
                if (ctx.align && ctx.type == "}")
                    return ctx.indented+(state.context.type == (textAfter || "").charAt(0) ? 0 : indentUnit);
            }
        },
        modeProps: {closeBrackets: {triples: '"'}}
    });

    def(["x-shader/x-vertex", "x-shader/x-fragment"], {
        name: "clike",
        keywords: words("sampler1D sampler2D sampler3D samplerCube "+
            "sampler1DShadow sampler2DShadow "+
            "const attribute uniform varying "+
            "break continue discard return "+
            "for while do if else struct "+
            "in out inout"),
        types: words("float int bool void "+
            "vec2 vec3 vec4 ivec2 ivec3 ivec4 bvec2 bvec3 bvec4 "+
            "mat2 mat3 mat4"),
        blockKeywords: words("for while do if else struct"),
        builtin: words("radians degrees sin cos tan asin acos atan "+
            "pow exp log exp2 sqrt inversesqrt "+
            "abs sign floor ceil fract mod min max clamp mix step smoothstep "+
            "length distance dot cross normalize ftransform faceforward "+
            "reflect refract matrixCompMult "+
            "lessThan lessThanEqual greaterThan greaterThanEqual "+
            "equal notEqual any all not "+
            "texture1D texture1DProj texture1DLod texture1DProjLod "+
            "texture2D texture2DProj texture2DLod texture2DProjLod "+
            "texture3D texture3DProj texture3DLod texture3DProjLod "+
            "textureCube textureCubeLod "+
            "shadow1D shadow2D shadow1DProj shadow2DProj "+
            "shadow1DLod shadow2DLod shadow1DProjLod shadow2DProjLod "+
            "dFdx dFdy fwidth "+
            "noise1 noise2 noise3 noise4"),
        atoms: words("true false "+
            "gl_FragColor gl_SecondaryColor gl_Normal gl_Vertex "+
            "gl_MultiTexCoord0 gl_MultiTexCoord1 gl_MultiTexCoord2 gl_MultiTexCoord3 "+
            "gl_MultiTexCoord4 gl_MultiTexCoord5 gl_MultiTexCoord6 gl_MultiTexCoord7 "+
            "gl_FogCoord gl_PointCoord "+
            "gl_Position gl_PointSize gl_ClipVertex "+
            "gl_FrontColor gl_BackColor gl_FrontSecondaryColor gl_BackSecondaryColor "+
            "gl_TexCoord gl_FogFragCoord "+
            "gl_FragCoord gl_FrontFacing "+
            "gl_FragData gl_FragDepth "+
            "gl_ModelViewMatrix gl_ProjectionMatrix gl_ModelViewProjectionMatrix "+
            "gl_TextureMatrix gl_NormalMatrix gl_ModelViewMatrixInverse "+
            "gl_ProjectionMatrixInverse gl_ModelViewProjectionMatrixInverse "+
            "gl_TextureMatrixTranspose gl_ModelViewMatrixInverseTranspose "+
            "gl_ProjectionMatrixInverseTranspose "+
            "gl_ModelViewProjectionMatrixInverseTranspose "+
            "gl_TextureMatrixInverseTranspose "+
            "gl_NormalScale gl_DepthRange gl_ClipPlane "+
            "gl_Point gl_FrontMaterial gl_BackMaterial gl_LightSource gl_LightModel "+
            "gl_FrontLightModelProduct gl_BackLightModelProduct "+
            "gl_TextureColor gl_EyePlaneS gl_EyePlaneT gl_EyePlaneR gl_EyePlaneQ "+
            "gl_FogParameters "+
            "gl_MaxLights gl_MaxClipPlanes gl_MaxTextureUnits gl_MaxTextureCoords "+
            "gl_MaxVertexAttribs gl_MaxVertexUniformComponents gl_MaxVaryingFloats "+
            "gl_MaxVertexTextureImageUnits gl_MaxTextureImageUnits "+
            "gl_MaxFragmentUniformComponents gl_MaxCombineTextureImageUnits "+
            "gl_MaxDrawBuffers"),
        indentSwitch: false,
        hooks: {"#": cppHook},
        modeProps: {fold: ["brace", "include"]}
    });

    def("text/x-nesc", {
        name: "clike",
        keywords: words(cKeywords+" as atomic async call command component components configuration event generic "+
            "implementation includes interface module new norace nx_struct nx_union post provides "+
            "signal task uses abstract extends"),
        types: cTypes,
        blockKeywords: words(cBlockKeywords),
        atoms: words("null true false"),
        hooks: {"#": cppHook},
        modeProps: {fold: ["brace", "include"]}
    });

    def("text/x-objectivec", {
        name: "clike",
        keywords: words(cKeywords+" "+objCKeywords),
        types: objCTypes,
        builtin: words(objCBuiltins),
        blockKeywords: words(cBlockKeywords+" @synthesize @try @catch @finally @autoreleasepool @synchronized"),
        defKeywords: words(cDefKeywords+" @interface @implementation @protocol @class"),
        dontIndentStatements: /^@.*$/,
        typeFirstDefinitions: true,
        atoms: words("YES NO NULL Nil nil true false nullptr"),
        isReservedIdentifier: cIsReservedIdentifier,
        hooks: {
            "#": cppHook,
            "*": pointerHook,
        },
        modeProps: {fold: ["brace", "include"]}
    });

    def("text/x-objectivec++", {
        name: "clike",
        keywords: words(cKeywords+" "+objCKeywords+" "+cppKeywords),
        types: objCTypes,
        builtin: words(objCBuiltins),
        blockKeywords: words(cBlockKeywords+" @synthesize @try @catch @finally @autoreleasepool @synchronized class try catch"),
        defKeywords: words(cDefKeywords+" @interface @implementation @protocol @class class namespace"),
        dontIndentStatements: /^@.*$|^template$/,
        typeFirstDefinitions: true,
        atoms: words("YES NO NULL Nil nil true false nullptr"),
        isReservedIdentifier: cIsReservedIdentifier,
        hooks: {
            "#": cppHook,
            "*": pointerHook,
            "u": cpp11StringHook,
            "U": cpp11StringHook,
            "L": cpp11StringHook,
            "R": cpp11StringHook,
            "0": cpp14Literal,
            "1": cpp14Literal,
            "2": cpp14Literal,
            "3": cpp14Literal,
            "4": cpp14Literal,
            "5": cpp14Literal,
            "6": cpp14Literal,
            "7": cpp14Literal,
            "8": cpp14Literal,
            "9": cpp14Literal,
            token: function (stream, state, style) {
                if (style == "variable" && stream.peek() == "(" &&
                    (state.prevToken == ";" || state.prevToken == null ||
                        state.prevToken == "}") &&
                    cppLooksLikeConstructor(stream.current()))
                    return "def";
            }
        },
        namespaceSeparator: "::",
        modeProps: {fold: ["brace", "include"]}
    });

    def("text/x-squirrel", {
        name: "clike",
        keywords: words("base break clone continue const default delete enum extends function in class"+
            " foreach local resume return this throw typeof yield constructor instanceof static"),
        types: cTypes,
        blockKeywords: words("case catch class else for foreach if switch try while"),
        defKeywords: words("function local class"),
        typeFirstDefinitions: true,
        atoms: words("true false null"),
        hooks: {"#": cppHook},
        modeProps: {fold: ["brace", "include"]}
    });

    // Ceylon Strings need to deal with interpolation
    var stringTokenizer = null;

    function tokenCeylonString(type) {
        return function (stream, state) {
            var escaped = false, next, end = false;
            while (!stream.eol()) {
                if (!escaped && stream.match('"') &&
                    (type == "single" || stream.match('""'))) {
                    end = true;
                    break;
                }
                if (!escaped && stream.match('``')) {
                    stringTokenizer = tokenCeylonString(type);
                    end = true;
                    break;
                }
                next = stream.next();
                escaped = type == "single" && !escaped && next == "\\";
            }
            if (end)
                state.tokenize = null;
            return "string";
        }
    }

    def("text/x-ceylon", {
        name: "clike",
        keywords: words("abstracts alias assembly assert assign break case catch class continue dynamic else"+
            " exists extends finally for function given if import in interface is let module new"+
            " nonempty object of out outer package return satisfies super switch then this throw"+
            " try value void while"),
        types: function (word) {
            // In Ceylon all identifiers that start with an uppercase are types
            var first = word.charAt(0);
            return (first === first.toUpperCase() && first !== first.toLowerCase());
        },
        blockKeywords: words("case catch class dynamic else finally for function if interface module new object switch try while"),
        defKeywords: words("class dynamic function interface module object package value"),
        builtin: words("abstract actual aliased annotation by default deprecated doc final formal late license"+
            " native optional sealed see serializable shared suppressWarnings tagged throws variable"),
        isPunctuationChar: /[\[\]{}\(\),;\:\.`]/,
        isOperatorChar: /[+\-*&%=<>!?|^~:\/]/,
        numberStart: /[\d#$]/,
        number: /^(?:#[\da-fA-F_]+|\$[01_]+|[\d_]+[kMGTPmunpf]?|[\d_]+\.[\d_]+(?:[eE][-+]?\d+|[kMGTPmunpf]|)|)/i,
        multiLineStrings: true,
        typeFirstDefinitions: true,
        atoms: words("true false null larger smaller equal empty finished"),
        indentSwitch: false,
        styleDefs: false,
        hooks: {
            "@": function (stream) {
                stream.eatWhile(/[\w\$_]/);
                return "meta";
            },
            '"': function (stream, state) {
                state.tokenize = tokenCeylonString(stream.match('""') ? "triple" : "single");
                return state.tokenize(stream, state);
            },
            '`': function (stream, state) {
                if (!stringTokenizer || !stream.match('`')) return false;
                state.tokenize = stringTokenizer;
                stringTokenizer = null;
                return state.tokenize(stream, state);
            },
            "'": function (stream) {
                stream.eatWhile(/[\w\$_\xa1-\uffff]/);
                return "atom";
            },
            token: function (_stream, state, style) {
                if ((style == "variable" || style == "type") &&
                    state.prevToken == ".") {
                    return "variable-2";
                }
            }
        },
        modeProps: {
            fold: ["brace", "import"],
            closeBrackets: {triples: '"'}
        }
    });

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    CodeMirror.defineMode("css", function (config, parserConfig) {
        var inline = parserConfig.inline
        if (!parserConfig.propertyKeywords) parserConfig = CodeMirror.resolveMode("text/css");

        var indentUnit = config.indentUnit,
            tokenHooks = parserConfig.tokenHooks,
            documentTypes = parserConfig.documentTypes || {},
            mediaTypes = parserConfig.mediaTypes || {},
            mediaFeatures = parserConfig.mediaFeatures || {},
            mediaValueKeywords = parserConfig.mediaValueKeywords || {},
            propertyKeywords = parserConfig.propertyKeywords || {},
            nonStandardPropertyKeywords = parserConfig.nonStandardPropertyKeywords || {},
            fontProperties = parserConfig.fontProperties || {},
            counterDescriptors = parserConfig.counterDescriptors || {},
            colorKeywords = parserConfig.colorKeywords || {},
            valueKeywords = parserConfig.valueKeywords || {},
            allowNested = parserConfig.allowNested,
            lineComment = parserConfig.lineComment,
            supportsAtComponent = parserConfig.supportsAtComponent === true,
            highlightNonStandardPropertyKeywords = config.highlightNonStandardPropertyKeywords !== false;

        var type, override;

        function ret(style, tp) {
            type = tp;
            return style;
        }

        // Tokenizers

        function tokenBase(stream, state) {
            var ch = stream.next();
            if (tokenHooks[ch]) {
                var result = tokenHooks[ch](stream, state);
                if (result !== false) return result;
            }
            if (ch == "@") {
                stream.eatWhile(/[\w\\\-]/);
                return ret("def", stream.current());
            } else if (ch == "=" || (ch == "~" || ch == "|") && stream.eat("=")) {
                return ret(null, "compare");
            } else if (ch == "\"" || ch == "'") {
                state.tokenize = tokenString(ch);
                return state.tokenize(stream, state);
            } else if (ch == "#") {
                stream.eatWhile(/[\w\\\-]/);
                return ret("atom", "hash");
            } else if (ch == "!") {
                stream.match(/^\s*\w*/);
                return ret("keyword", "important");
            } else if (/\d/.test(ch) || ch == "." && stream.eat(/\d/)) {
                stream.eatWhile(/[\w.%]/);
                return ret("number", "unit");
            } else if (ch === "-") {
                if (/[\d.]/.test(stream.peek())) {
                    stream.eatWhile(/[\w.%]/);
                    return ret("number", "unit");
                } else if (stream.match(/^-[\w\\\-]*/)) {
                    stream.eatWhile(/[\w\\\-]/);
                    if (stream.match(/^\s*:/, false))
                        return ret("variable-2", "variable-definition");
                    return ret("variable-2", "variable");
                } else if (stream.match(/^\w+-/)) {
                    return ret("meta", "meta");
                }
            } else if (/[,+>*\/]/.test(ch)) {
                return ret(null, "select-op");
            } else if (ch == "." && stream.match(/^-?[_a-z][_a-z0-9-]*/i)) {
                return ret("qualifier", "qualifier");
            } else if (/[:;{}\[\]\(\)]/.test(ch)) {
                return ret(null, ch);
            } else if (stream.match(/^[\w-.]+(?=\()/)) {
                if (/^(url(-prefix)?|domain|regexp)$/i.test(stream.current())) {
                    state.tokenize = tokenParenthesized;
                }
                return ret("variable callee", "variable");
            } else if (/[\w\\\-]/.test(ch)) {
                stream.eatWhile(/[\w\\\-]/);
                return ret("property", "word");
            } else {
                return ret(null, null);
            }
        }

        function tokenString(quote) {
            return function (stream, state) {
                var escaped = false, ch;
                while ((ch = stream.next()) != null) {
                    if (ch == quote && !escaped) {
                        if (quote == ")") stream.backUp(1);
                        break;
                    }
                    escaped = !escaped && ch == "\\";
                }
                if (ch == quote || !escaped && quote != ")") state.tokenize = null;
                return ret("string", "string");
            };
        }

        function tokenParenthesized(stream, state) {
            stream.next(); // Must be '('
            if (!stream.match(/^\s*[\"\')]/, false))
                state.tokenize = tokenString(")");
            else
                state.tokenize = null;
            return ret(null, "(");
        }

        // Context management

        function Context(type, indent, prev) {
            this.type = type;
            this.indent = indent;
            this.prev = prev;
        }

        function pushContext(state, stream, type, indent) {
            state.context = new Context(type, stream.indentation()+(indent === false ? 0 : indentUnit), state.context);
            return type;
        }

        function popContext(state) {
            if (state.context.prev)
                state.context = state.context.prev;
            return state.context.type;
        }

        function pass(type, stream, state) {
            return states[state.context.type](type, stream, state);
        }

        function popAndPass(type, stream, state, n) {
            for (var i = n || 1; i > 0; i--)
                state.context = state.context.prev;
            return pass(type, stream, state);
        }

        // Parser

        function wordAsValue(stream) {
            var word = stream.current().toLowerCase();
            if (valueKeywords.hasOwnProperty(word))
                override = "atom";
            else if (colorKeywords.hasOwnProperty(word))
                override = "keyword";
            else
                override = "variable";
        }

        var states = {};

        states.top = function (type, stream, state) {
            if (type == "{") {
                return pushContext(state, stream, "block");
            } else if (type == "}" && state.context.prev) {
                return popContext(state);
            } else if (supportsAtComponent && /@component/i.test(type)) {
                return pushContext(state, stream, "atComponentBlock");
            } else if (/^@(-moz-)?document$/i.test(type)) {
                return pushContext(state, stream, "documentTypes");
            } else if (/^@(media|supports|(-moz-)?document|import)$/i.test(type)) {
                return pushContext(state, stream, "atBlock");
            } else if (/^@(font-face|counter-style)/i.test(type)) {
                state.stateArg = type;
                return "restricted_atBlock_before";
            } else if (/^@(-(moz|ms|o|webkit)-)?keyframes$/i.test(type)) {
                return "keyframes";
            } else if (type && type.charAt(0) == "@") {
                return pushContext(state, stream, "at");
            } else if (type == "hash") {
                override = "builtin";
            } else if (type == "word") {
                override = "tag";
            } else if (type == "variable-definition") {
                return "maybeprop";
            } else if (type == "interpolation") {
                return pushContext(state, stream, "interpolation");
            } else if (type == ":") {
                return "pseudo";
            } else if (allowNested && type == "(") {
                return pushContext(state, stream, "parens");
            }
            return state.context.type;
        };

        states.block = function (type, stream, state) {
            if (type == "word") {
                var word = stream.current().toLowerCase();
                if (propertyKeywords.hasOwnProperty(word)) {
                    override = "property";
                    return "maybeprop";
                } else if (nonStandardPropertyKeywords.hasOwnProperty(word)) {
                    override = highlightNonStandardPropertyKeywords ? "string-2" : "property";
                    return "maybeprop";
                } else if (allowNested) {
                    override = stream.match(/^\s*:(?:\s|$)/, false) ? "property" : "tag";
                    return "block";
                } else {
                    override += " error";
                    return "maybeprop";
                }
            } else if (type == "meta") {
                return "block";
            } else if (!allowNested && (type == "hash" || type == "qualifier")) {
                override = "error";
                return "block";
            } else {
                return states.top(type, stream, state);
            }
        };

        states.maybeprop = function (type, stream, state) {
            if (type == ":") return pushContext(state, stream, "prop");
            return pass(type, stream, state);
        };

        states.prop = function (type, stream, state) {
            if (type == ";") return popContext(state);
            if (type == "{" && allowNested) return pushContext(state, stream, "propBlock");
            if (type == "}" || type == "{") return popAndPass(type, stream, state);
            if (type == "(") return pushContext(state, stream, "parens");

            if (type == "hash" && !/^#([0-9a-fA-f]{3,4}|[0-9a-fA-f]{6}|[0-9a-fA-f]{8})$/.test(stream.current())) {
                override += " error";
            } else if (type == "word") {
                wordAsValue(stream);
            } else if (type == "interpolation") {
                return pushContext(state, stream, "interpolation");
            }
            return "prop";
        };

        states.propBlock = function (type, _stream, state) {
            if (type == "}") return popContext(state);
            if (type == "word") {
                override = "property";
                return "maybeprop";
            }
            return state.context.type;
        };

        states.parens = function (type, stream, state) {
            if (type == "{" || type == "}") return popAndPass(type, stream, state);
            if (type == ")") return popContext(state);
            if (type == "(") return pushContext(state, stream, "parens");
            if (type == "interpolation") return pushContext(state, stream, "interpolation");
            if (type == "word") wordAsValue(stream);
            return "parens";
        };

        states.pseudo = function (type, stream, state) {
            if (type == "meta") return "pseudo";

            if (type == "word") {
                override = "variable-3";
                return state.context.type;
            }
            return pass(type, stream, state);
        };

        states.documentTypes = function (type, stream, state) {
            if (type == "word" && documentTypes.hasOwnProperty(stream.current())) {
                override = "tag";
                return state.context.type;
            } else {
                return states.atBlock(type, stream, state);
            }
        };

        states.atBlock = function (type, stream, state) {
            if (type == "(") return pushContext(state, stream, "atBlock_parens");
            if (type == "}" || type == ";") return popAndPass(type, stream, state);
            if (type == "{") return popContext(state) && pushContext(state, stream, allowNested ? "block" : "top");

            if (type == "interpolation") return pushContext(state, stream, "interpolation");

            if (type == "word") {
                var word = stream.current().toLowerCase();
                if (word == "only" || word == "not" || word == "and" || word == "or")
                    override = "keyword";
                else if (mediaTypes.hasOwnProperty(word))
                    override = "attribute";
                else if (mediaFeatures.hasOwnProperty(word))
                    override = "property";
                else if (mediaValueKeywords.hasOwnProperty(word))
                    override = "keyword";
                else if (propertyKeywords.hasOwnProperty(word))
                    override = "property";
                else if (nonStandardPropertyKeywords.hasOwnProperty(word))
                    override = highlightNonStandardPropertyKeywords ? "string-2" : "property";
                else if (valueKeywords.hasOwnProperty(word))
                    override = "atom";
                else if (colorKeywords.hasOwnProperty(word))
                    override = "keyword";
                else
                    override = "error";
            }
            return state.context.type;
        };

        states.atComponentBlock = function (type, stream, state) {
            if (type == "}")
                return popAndPass(type, stream, state);
            if (type == "{")
                return popContext(state) && pushContext(state, stream, allowNested ? "block" : "top", false);
            if (type == "word")
                override = "error";
            return state.context.type;
        };

        states.atBlock_parens = function (type, stream, state) {
            if (type == ")") return popContext(state);
            if (type == "{" || type == "}") return popAndPass(type, stream, state, 2);
            return states.atBlock(type, stream, state);
        };

        states.restricted_atBlock_before = function (type, stream, state) {
            if (type == "{")
                return pushContext(state, stream, "restricted_atBlock");
            if (type == "word" && state.stateArg == "@counter-style") {
                override = "variable";
                return "restricted_atBlock_before";
            }
            return pass(type, stream, state);
        };

        states.restricted_atBlock = function (type, stream, state) {
            if (type == "}") {
                state.stateArg = null;
                return popContext(state);
            }
            if (type == "word") {
                if ((state.stateArg == "@font-face" && !fontProperties.hasOwnProperty(stream.current().toLowerCase())) ||
                    (state.stateArg == "@counter-style" && !counterDescriptors.hasOwnProperty(stream.current().toLowerCase())))
                    override = "error";
                else
                    override = "property";
                return "maybeprop";
            }
            return "restricted_atBlock";
        };

        states.keyframes = function (type, stream, state) {
            if (type == "word") {
                override = "variable";
                return "keyframes";
            }
            if (type == "{") return pushContext(state, stream, "top");
            return pass(type, stream, state);
        };

        states.at = function (type, stream, state) {
            if (type == ";") return popContext(state);
            if (type == "{" || type == "}") return popAndPass(type, stream, state);
            if (type == "word") override = "tag";
            else if (type == "hash") override = "builtin";
            return "at";
        };

        states.interpolation = function (type, stream, state) {
            if (type == "}") return popContext(state);
            if (type == "{" || type == ";") return popAndPass(type, stream, state);
            if (type == "word") override = "variable";
            else if (type != "variable" && type != "(" && type != ")") override = "error";
            return "interpolation";
        };

        return {
            startState: function (base) {
                return {
                    tokenize: null,
                    state: inline ? "block" : "top",
                    stateArg: null,
                    context: new Context(inline ? "block" : "top", base || 0, null)
                };
            },

            token: function (stream, state) {
                if (!state.tokenize && stream.eatSpace()) return null;
                var style = (state.tokenize || tokenBase)(stream, state);
                if (style && typeof style == "object") {
                    type = style[1];
                    style = style[0];
                }
                override = style;
                if (type != "comment")
                    state.state = states[state.state](type, stream, state);
                return override;
            },

            indent: function (state, textAfter) {
                var cx = state.context, ch = textAfter && textAfter.charAt(0);
                var indent = cx.indent;
                if (cx.type == "prop" && (ch == "}" || ch == ")")) cx = cx.prev;
                if (cx.prev) {
                    if (ch == "}" && (cx.type == "block" || cx.type == "top" ||
                        cx.type == "interpolation" || cx.type == "restricted_atBlock")) {
                        // Resume indentation from parent context.
                        cx = cx.prev;
                        indent = cx.indent;
                    } else if (ch == ")" && (cx.type == "parens" || cx.type == "atBlock_parens") ||
                        ch == "{" && (cx.type == "at" || cx.type == "atBlock")) {
                        // Dedent relative to current context.
                        indent = Math.max(0, cx.indent-indentUnit);
                    }
                }
                return indent;
            },

            electricChars: "}",
            blockCommentStart: "/*",
            blockCommentEnd: "*/",
            blockCommentContinue: " * ",
            lineComment: lineComment,
            fold: "brace"
        };
    });

    function keySet(array) {
        var keys = {};
        for (var i = 0; i < array.length; ++i) {
            keys[array[i].toLowerCase()] = true;
        }
        return keys;
    }

    var documentTypes_ = [
        "domain", "regexp", "url", "url-prefix"
    ], documentTypes = keySet(documentTypes_);

    var mediaTypes_ = [
        "all", "aural", "braille", "handheld", "print", "projection", "screen",
        "tty", "tv", "embossed"
    ], mediaTypes = keySet(mediaTypes_);

    var mediaFeatures_ = [
        "width", "min-width", "max-width", "height", "min-height", "max-height",
        "device-width", "min-device-width", "max-device-width", "device-height",
        "min-device-height", "max-device-height", "aspect-ratio",
        "min-aspect-ratio", "max-aspect-ratio", "device-aspect-ratio",
        "min-device-aspect-ratio", "max-device-aspect-ratio", "color", "min-color",
        "max-color", "color-index", "min-color-index", "max-color-index",
        "monochrome", "min-monochrome", "max-monochrome", "resolution",
        "min-resolution", "max-resolution", "scan", "grid", "orientation",
        "device-pixel-ratio", "min-device-pixel-ratio", "max-device-pixel-ratio",
        "pointer", "any-pointer", "hover", "any-hover", "prefers-color-scheme",
        "dynamic-range", "video-dynamic-range"
    ], mediaFeatures = keySet(mediaFeatures_);

    var mediaValueKeywords_ = [
        "landscape", "portrait", "none", "coarse", "fine", "on-demand", "hover",
        "interlace", "progressive",
        "dark", "light",
        "standard", "high"
    ], mediaValueKeywords = keySet(mediaValueKeywords_);

    var propertyKeywords_ = [
        "align-content", "align-items", "align-self", "alignment-adjust",
        "alignment-baseline", "all", "anchor-point", "animation", "animation-delay",
        "animation-direction", "animation-duration", "animation-fill-mode",
        "animation-iteration-count", "animation-name", "animation-play-state",
        "animation-timing-function", "appearance", "azimuth", "backdrop-filter",
        "backface-visibility", "background", "background-attachment",
        "background-blend-mode", "background-clip", "background-color",
        "background-image", "background-origin", "background-position",
        "background-position-x", "background-position-y", "background-repeat",
        "background-size", "baseline-shift", "binding", "bleed", "block-size",
        "bookmark-label", "bookmark-level", "bookmark-state", "bookmark-target",
        "border", "border-bottom", "border-bottom-color", "border-bottom-left-radius",
        "border-bottom-right-radius", "border-bottom-style", "border-bottom-width",
        "border-collapse", "border-color", "border-image", "border-image-outset",
        "border-image-repeat", "border-image-slice", "border-image-source",
        "border-image-width", "border-left", "border-left-color", "border-left-style",
        "border-left-width", "border-radius", "border-right", "border-right-color",
        "border-right-style", "border-right-width", "border-spacing", "border-style",
        "border-top", "border-top-color", "border-top-left-radius",
        "border-top-right-radius", "border-top-style", "border-top-width",
        "border-width", "bottom", "box-decoration-break", "box-shadow", "box-sizing",
        "break-after", "break-before", "break-inside", "caption-side", "caret-color",
        "clear", "clip", "color", "color-profile", "column-count", "column-fill",
        "column-gap", "column-rule", "column-rule-color", "column-rule-style",
        "column-rule-width", "column-span", "column-width", "columns", "contain",
        "content", "counter-increment", "counter-reset", "crop", "cue", "cue-after",
        "cue-before", "cursor", "direction", "display", "dominant-baseline",
        "drop-initial-after-adjust", "drop-initial-after-align",
        "drop-initial-before-adjust", "drop-initial-before-align", "drop-initial-size",
        "drop-initial-value", "elevation", "empty-cells", "fit", "fit-content", "fit-position",
        "flex", "flex-basis", "flex-direction", "flex-flow", "flex-grow",
        "flex-shrink", "flex-wrap", "float", "float-offset", "flow-from", "flow-into",
        "font", "font-family", "font-feature-settings", "font-kerning",
        "font-language-override", "font-optical-sizing", "font-size",
        "font-size-adjust", "font-stretch", "font-style", "font-synthesis",
        "font-variant", "font-variant-alternates", "font-variant-caps",
        "font-variant-east-asian", "font-variant-ligatures", "font-variant-numeric",
        "font-variant-position", "font-variation-settings", "font-weight", "gap",
        "grid", "grid-area", "grid-auto-columns", "grid-auto-flow", "grid-auto-rows",
        "grid-column", "grid-column-end", "grid-column-gap", "grid-column-start",
        "grid-gap", "grid-row", "grid-row-end", "grid-row-gap", "grid-row-start",
        "grid-template", "grid-template-areas", "grid-template-columns",
        "grid-template-rows", "hanging-punctuation", "height", "hyphens", "icon",
        "image-orientation", "image-rendering", "image-resolution", "inline-box-align",
        "inset", "inset-block", "inset-block-end", "inset-block-start", "inset-inline",
        "inset-inline-end", "inset-inline-start", "isolation", "justify-content",
        "justify-items", "justify-self", "left", "letter-spacing", "line-break",
        "line-height", "line-height-step", "line-stacking", "line-stacking-ruby",
        "line-stacking-shift", "line-stacking-strategy", "list-style",
        "list-style-image", "list-style-position", "list-style-type", "margin",
        "margin-bottom", "margin-left", "margin-right", "margin-top", "marks",
        "marquee-direction", "marquee-loop", "marquee-play-count", "marquee-speed",
        "marquee-style", "mask-clip", "mask-composite", "mask-image", "mask-mode",
        "mask-origin", "mask-position", "mask-repeat", "mask-size", "mask-type",
        "max-block-size", "max-height", "max-inline-size",
        "max-width", "min-block-size", "min-height", "min-inline-size", "min-width",
        "mix-blend-mode", "move-to", "nav-down", "nav-index", "nav-left", "nav-right",
        "nav-up", "object-fit", "object-position", "offset", "offset-anchor",
        "offset-distance", "offset-path", "offset-position", "offset-rotate",
        "opacity", "order", "orphans", "outline", "outline-color", "outline-offset",
        "outline-style", "outline-width", "overflow", "overflow-style",
        "overflow-wrap", "overflow-x", "overflow-y", "padding", "padding-bottom",
        "padding-left", "padding-right", "padding-top", "page", "page-break-after",
        "page-break-before", "page-break-inside", "page-policy", "pause",
        "pause-after", "pause-before", "perspective", "perspective-origin", "pitch",
        "pitch-range", "place-content", "place-items", "place-self", "play-during",
        "position", "presentation-level", "punctuation-trim", "quotes",
        "region-break-after", "region-break-before", "region-break-inside",
        "region-fragment", "rendering-intent", "resize", "rest", "rest-after",
        "rest-before", "richness", "right", "rotate", "rotation", "rotation-point",
        "row-gap", "ruby-align", "ruby-overhang", "ruby-position", "ruby-span",
        "scale", "scroll-behavior", "scroll-margin", "scroll-margin-block",
        "scroll-margin-block-end", "scroll-margin-block-start", "scroll-margin-bottom",
        "scroll-margin-inline", "scroll-margin-inline-end",
        "scroll-margin-inline-start", "scroll-margin-left", "scroll-margin-right",
        "scroll-margin-top", "scroll-padding", "scroll-padding-block",
        "scroll-padding-block-end", "scroll-padding-block-start",
        "scroll-padding-bottom", "scroll-padding-inline", "scroll-padding-inline-end",
        "scroll-padding-inline-start", "scroll-padding-left", "scroll-padding-right",
        "scroll-padding-top", "scroll-snap-align", "scroll-snap-type",
        "shape-image-threshold", "shape-inside", "shape-margin", "shape-outside",
        "size", "speak", "speak-as", "speak-header", "speak-numeral",
        "speak-punctuation", "speech-rate", "stress", "string-set", "tab-size",
        "table-layout", "target", "target-name", "target-new", "target-position",
        "text-align", "text-align-last", "text-combine-upright", "text-decoration",
        "text-decoration-color", "text-decoration-line", "text-decoration-skip",
        "text-decoration-skip-ink", "text-decoration-style", "text-emphasis",
        "text-emphasis-color", "text-emphasis-position", "text-emphasis-style",
        "text-height", "text-indent", "text-justify", "text-orientation",
        "text-outline", "text-overflow", "text-rendering", "text-shadow",
        "text-size-adjust", "text-space-collapse", "text-transform",
        "text-underline-position", "text-wrap", "top", "touch-action", "transform", "transform-origin",
        "transform-style", "transition", "transition-delay", "transition-duration",
        "transition-property", "transition-timing-function", "translate",
        "unicode-bidi", "user-select", "vertical-align", "visibility", "voice-balance",
        "voice-duration", "voice-family", "voice-pitch", "voice-range", "voice-rate",
        "voice-stress", "voice-volume", "volume", "white-space", "widows", "width",
        "will-change", "word-break", "word-spacing", "word-wrap", "writing-mode", "z-index",
        // SVG-specific
        "clip-path", "clip-rule", "mask", "enable-background", "filter", "flood-color",
        "flood-opacity", "lighting-color", "stop-color", "stop-opacity", "pointer-events",
        "color-interpolation", "color-interpolation-filters",
        "color-rendering", "fill", "fill-opacity", "fill-rule", "image-rendering",
        "marker", "marker-end", "marker-mid", "marker-start", "paint-order", "shape-rendering", "stroke",
        "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin",
        "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-rendering",
        "baseline-shift", "dominant-baseline", "glyph-orientation-horizontal",
        "glyph-orientation-vertical", "text-anchor", "writing-mode",
    ], propertyKeywords = keySet(propertyKeywords_);

    var nonStandardPropertyKeywords_ = [
        "accent-color", "aspect-ratio", "border-block", "border-block-color", "border-block-end",
        "border-block-end-color", "border-block-end-style", "border-block-end-width",
        "border-block-start", "border-block-start-color", "border-block-start-style",
        "border-block-start-width", "border-block-style", "border-block-width",
        "border-inline", "border-inline-color", "border-inline-end",
        "border-inline-end-color", "border-inline-end-style",
        "border-inline-end-width", "border-inline-start", "border-inline-start-color",
        "border-inline-start-style", "border-inline-start-width",
        "border-inline-style", "border-inline-width", "content-visibility", "margin-block",
        "margin-block-end", "margin-block-start", "margin-inline", "margin-inline-end",
        "margin-inline-start", "overflow-anchor", "overscroll-behavior", "padding-block", "padding-block-end",
        "padding-block-start", "padding-inline", "padding-inline-end",
        "padding-inline-start", "scroll-snap-stop", "scrollbar-3d-light-color",
        "scrollbar-arrow-color", "scrollbar-base-color", "scrollbar-dark-shadow-color",
        "scrollbar-face-color", "scrollbar-highlight-color", "scrollbar-shadow-color",
        "scrollbar-track-color", "searchfield-cancel-button", "searchfield-decoration",
        "searchfield-results-button", "searchfield-results-decoration", "shape-inside", "zoom"
    ], nonStandardPropertyKeywords = keySet(nonStandardPropertyKeywords_);

    var fontProperties_ = [
        "font-display", "font-family", "src", "unicode-range", "font-variant",
        "font-feature-settings", "font-stretch", "font-weight", "font-style"
    ], fontProperties = keySet(fontProperties_);

    var counterDescriptors_ = [
        "additive-symbols", "fallback", "negative", "pad", "prefix", "range",
        "speak-as", "suffix", "symbols", "system"
    ], counterDescriptors = keySet(counterDescriptors_);

    var colorKeywords_ = [
        "aliceblue", "antiquewhite", "aqua", "aquamarine", "azure", "beige",
        "bisque", "black", "blanchedalmond", "blue", "blueviolet", "brown",
        "burlywood", "cadetblue", "chartreuse", "chocolate", "coral", "cornflowerblue",
        "cornsilk", "crimson", "cyan", "darkblue", "darkcyan", "darkgoldenrod",
        "darkgray", "darkgreen", "darkgrey", "darkkhaki", "darkmagenta", "darkolivegreen",
        "darkorange", "darkorchid", "darkred", "darksalmon", "darkseagreen",
        "darkslateblue", "darkslategray", "darkslategrey", "darkturquoise", "darkviolet",
        "deeppink", "deepskyblue", "dimgray", "dimgrey", "dodgerblue", "firebrick",
        "floralwhite", "forestgreen", "fuchsia", "gainsboro", "ghostwhite",
        "gold", "goldenrod", "gray", "grey", "green", "greenyellow", "honeydew",
        "hotpink", "indianred", "indigo", "ivory", "khaki", "lavender",
        "lavenderblush", "lawngreen", "lemonchiffon", "lightblue", "lightcoral",
        "lightcyan", "lightgoldenrodyellow", "lightgray", "lightgreen", "lightgrey", "lightpink",
        "lightsalmon", "lightseagreen", "lightskyblue", "lightslategray", "lightslategrey",
        "lightsteelblue", "lightyellow", "lime", "limegreen", "linen", "magenta",
        "maroon", "mediumaquamarine", "mediumblue", "mediumorchid", "mediumpurple",
        "mediumseagreen", "mediumslateblue", "mediumspringgreen", "mediumturquoise",
        "mediumvioletred", "midnightblue", "mintcream", "mistyrose", "moccasin",
        "navajowhite", "navy", "oldlace", "olive", "olivedrab", "orange", "orangered",
        "orchid", "palegoldenrod", "palegreen", "paleturquoise", "palevioletred",
        "papayawhip", "peachpuff", "peru", "pink", "plum", "powderblue",
        "purple", "rebeccapurple", "red", "rosybrown", "royalblue", "saddlebrown",
        "salmon", "sandybrown", "seagreen", "seashell", "sienna", "silver", "skyblue",
        "slateblue", "slategray", "slategrey", "snow", "springgreen", "steelblue", "tan",
        "teal", "thistle", "tomato", "turquoise", "violet", "wheat", "white",
        "whitesmoke", "yellow", "yellowgreen"
    ], colorKeywords = keySet(colorKeywords_);

    var valueKeywords_ = [
        "above", "absolute", "activeborder", "additive", "activecaption", "afar",
        "after-white-space", "ahead", "alias", "all", "all-scroll", "alphabetic", "alternate",
        "always", "amharic", "amharic-abegede", "antialiased", "appworkspace",
        "arabic-indic", "armenian", "asterisks", "attr", "auto", "auto-flow", "avoid", "avoid-column", "avoid-page",
        "avoid-region", "axis-pan", "background", "backwards", "baseline", "below", "bidi-override", "binary",
        "bengali", "blink", "block", "block-axis", "blur", "bold", "bolder", "border", "border-box",
        "both", "bottom", "break", "break-all", "break-word", "brightness", "bullets", "button", "button-bevel",
        "buttonface", "buttonhighlight", "buttonshadow", "buttontext", "calc", "cambodian",
        "capitalize", "caps-lock-indicator", "caption", "captiontext", "caret",
        "cell", "center", "checkbox", "circle", "cjk-decimal", "cjk-earthly-branch",
        "cjk-heavenly-stem", "cjk-ideographic", "clear", "clip", "close-quote",
        "col-resize", "collapse", "color", "color-burn", "color-dodge", "column", "column-reverse",
        "compact", "condensed", "conic-gradient", "contain", "content", "contents",
        "content-box", "context-menu", "continuous", "contrast", "copy", "counter", "counters", "cover", "crop",
        "cross", "crosshair", "cubic-bezier", "currentcolor", "cursive", "cyclic", "darken", "dashed", "decimal",
        "decimal-leading-zero", "default", "default-button", "dense", "destination-atop",
        "destination-in", "destination-out", "destination-over", "devanagari", "difference",
        "disc", "discard", "disclosure-closed", "disclosure-open", "document",
        "dot-dash", "dot-dot-dash",
        "dotted", "double", "down", "drop-shadow", "e-resize", "ease", "ease-in", "ease-in-out", "ease-out",
        "element", "ellipse", "ellipsis", "embed", "end", "ethiopic", "ethiopic-abegede",
        "ethiopic-abegede-am-et", "ethiopic-abegede-gez", "ethiopic-abegede-ti-er",
        "ethiopic-abegede-ti-et", "ethiopic-halehame-aa-er",
        "ethiopic-halehame-aa-et", "ethiopic-halehame-am-et",
        "ethiopic-halehame-gez", "ethiopic-halehame-om-et",
        "ethiopic-halehame-sid-et", "ethiopic-halehame-so-et",
        "ethiopic-halehame-ti-er", "ethiopic-halehame-ti-et", "ethiopic-halehame-tig",
        "ethiopic-numeric", "ew-resize", "exclusion", "expanded", "extends", "extra-condensed",
        "extra-expanded", "fantasy", "fast", "fill", "fill-box", "fixed", "flat", "flex", "flex-end", "flex-start", "footnotes",
        "forwards", "from", "geometricPrecision", "georgian", "grayscale", "graytext", "grid", "groove",
        "gujarati", "gurmukhi", "hand", "hangul", "hangul-consonant", "hard-light", "hebrew",
        "help", "hidden", "hide", "higher", "highlight", "highlighttext",
        "hiragana", "hiragana-iroha", "horizontal", "hsl", "hsla", "hue", "hue-rotate", "icon", "ignore",
        "inactiveborder", "inactivecaption", "inactivecaptiontext", "infinite",
        "infobackground", "infotext", "inherit", "initial", "inline", "inline-axis",
        "inline-block", "inline-flex", "inline-grid", "inline-table", "inset", "inside", "intrinsic", "invert",
        "italic", "japanese-formal", "japanese-informal", "justify", "kannada",
        "katakana", "katakana-iroha", "keep-all", "khmer",
        "korean-hangul-formal", "korean-hanja-formal", "korean-hanja-informal",
        "landscape", "lao", "large", "larger", "left", "level", "lighter", "lighten",
        "line-through", "linear", "linear-gradient", "lines", "list-item", "listbox", "listitem",
        "local", "logical", "loud", "lower", "lower-alpha", "lower-armenian",
        "lower-greek", "lower-hexadecimal", "lower-latin", "lower-norwegian",
        "lower-roman", "lowercase", "ltr", "luminosity", "malayalam", "manipulation", "match", "matrix", "matrix3d",
        "media-controls-background", "media-current-time-display",
        "media-fullscreen-button", "media-mute-button", "media-play-button",
        "media-return-to-realtime-button", "media-rewind-button",
        "media-seek-back-button", "media-seek-forward-button", "media-slider",
        "media-sliderthumb", "media-time-remaining-display", "media-volume-slider",
        "media-volume-slider-container", "media-volume-sliderthumb", "medium",
        "menu", "menulist", "menulist-button", "menulist-text",
        "menulist-textfield", "menutext", "message-box", "middle", "min-intrinsic",
        "mix", "mongolian", "monospace", "move", "multiple", "multiple_mask_images", "multiply", "myanmar", "n-resize",
        "narrower", "ne-resize", "nesw-resize", "no-close-quote", "no-drop",
        "no-open-quote", "no-repeat", "none", "normal", "not-allowed", "nowrap",
        "ns-resize", "numbers", "numeric", "nw-resize", "nwse-resize", "oblique", "octal", "opacity", "open-quote",
        "optimizeLegibility", "optimizeSpeed", "oriya", "oromo", "outset",
        "outside", "outside-shape", "overlay", "overline", "padding", "padding-box",
        "painted", "page", "paused", "persian", "perspective", "pinch-zoom", "plus-darker", "plus-lighter",
        "pointer", "polygon", "portrait", "pre", "pre-line", "pre-wrap", "preserve-3d",
        "progress", "push-button", "radial-gradient", "radio", "read-only",
        "read-write", "read-write-plaintext-only", "rectangle", "region",
        "relative", "repeat", "repeating-linear-gradient", "repeating-radial-gradient",
        "repeating-conic-gradient", "repeat-x", "repeat-y", "reset", "reverse",
        "rgb", "rgba", "ridge", "right", "rotate", "rotate3d", "rotateX", "rotateY",
        "rotateZ", "round", "row", "row-resize", "row-reverse", "rtl", "run-in", "running",
        "s-resize", "sans-serif", "saturate", "saturation", "scale", "scale3d", "scaleX", "scaleY", "scaleZ", "screen",
        "scroll", "scrollbar", "scroll-position", "se-resize", "searchfield",
        "searchfield-cancel-button", "searchfield-decoration",
        "searchfield-results-button", "searchfield-results-decoration", "self-start", "self-end",
        "semi-condensed", "semi-expanded", "separate", "sepia", "serif", "show", "sidama",
        "simp-chinese-formal", "simp-chinese-informal", "single",
        "skew", "skewX", "skewY", "skip-white-space", "slide", "slider-horizontal",
        "slider-vertical", "sliderthumb-horizontal", "sliderthumb-vertical", "slow",
        "small", "small-caps", "small-caption", "smaller", "soft-light", "solid", "somali",
        "source-atop", "source-in", "source-out", "source-over", "space", "space-around", "space-between", "space-evenly", "spell-out", "square",
        "square-button", "start", "static", "status-bar", "stretch", "stroke", "stroke-box", "sub",
        "subpixel-antialiased", "svg_masks", "super", "sw-resize", "symbolic", "symbols", "system-ui", "table",
        "table-caption", "table-cell", "table-column", "table-column-group",
        "table-footer-group", "table-header-group", "table-row", "table-row-group",
        "tamil",
        "telugu", "text", "text-bottom", "text-top", "textarea", "textfield", "thai",
        "thick", "thin", "threeddarkshadow", "threedface", "threedhighlight",
        "threedlightshadow", "threedshadow", "tibetan", "tigre", "tigrinya-er",
        "tigrinya-er-abegede", "tigrinya-et", "tigrinya-et-abegede", "to", "top",
        "trad-chinese-formal", "trad-chinese-informal", "transform",
        "translate", "translate3d", "translateX", "translateY", "translateZ",
        "transparent", "ultra-condensed", "ultra-expanded", "underline", "unidirectional-pan", "unset", "up",
        "upper-alpha", "upper-armenian", "upper-greek", "upper-hexadecimal",
        "upper-latin", "upper-norwegian", "upper-roman", "uppercase", "urdu", "url",
        "var", "vertical", "vertical-text", "view-box", "visible", "visibleFill", "visiblePainted",
        "visibleStroke", "visual", "w-resize", "wait", "wave", "wider",
        "window", "windowframe", "windowtext", "words", "wrap", "wrap-reverse", "x-large", "x-small", "xor",
        "xx-large", "xx-small"
    ], valueKeywords = keySet(valueKeywords_);

    var allWords = documentTypes_.concat(mediaTypes_).concat(mediaFeatures_).concat(mediaValueKeywords_)
        .concat(propertyKeywords_).concat(nonStandardPropertyKeywords_).concat(colorKeywords_)
        .concat(valueKeywords_);
    CodeMirror.registerHelper("hintWords", "css", allWords);

    function tokenCComment(stream, state) {
        var maybeEnd = false, ch;
        while ((ch = stream.next()) != null) {
            if (maybeEnd && ch == "/") {
                state.tokenize = null;
                break;
            }
            maybeEnd = (ch == "*");
        }
        return ["comment", "comment"];
    }

    CodeMirror.defineMIME("text/css", {
        documentTypes: documentTypes,
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        fontProperties: fontProperties,
        counterDescriptors: counterDescriptors,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        tokenHooks: {
            "/": function (stream, state) {
                if (!stream.eat("*")) return false;
                state.tokenize = tokenCComment;
                return tokenCComment(stream, state);
            }
        },
        name: "css"
    });

    CodeMirror.defineMIME("text/x-scss", {
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        fontProperties: fontProperties,
        allowNested: true,
        lineComment: "//",
        tokenHooks: {
            "/": function (stream, state) {
                if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ["comment", "comment"];
                } else if (stream.eat("*")) {
                    state.tokenize = tokenCComment;
                    return tokenCComment(stream, state);
                } else {
                    return ["operator", "operator"];
                }
            },
            ":": function (stream) {
                if (stream.match(/^\s*\{/, false))
                    return [null, null]
                return false;
            },
            "$": function (stream) {
                stream.match(/^[\w-]+/);
                if (stream.match(/^\s*:/, false))
                    return ["variable-2", "variable-definition"];
                return ["variable-2", "variable"];
            },
            "#": function (stream) {
                if (!stream.eat("{")) return false;
                return [null, "interpolation"];
            }
        },
        name: "css",
        helperType: "scss"
    });

    CodeMirror.defineMIME("text/x-less", {
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        mediaValueKeywords: mediaValueKeywords,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        fontProperties: fontProperties,
        allowNested: true,
        lineComment: "//",
        tokenHooks: {
            "/": function (stream, state) {
                if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ["comment", "comment"];
                } else if (stream.eat("*")) {
                    state.tokenize = tokenCComment;
                    return tokenCComment(stream, state);
                } else {
                    return ["operator", "operator"];
                }
            },
            "@": function (stream) {
                if (stream.eat("{")) return [null, "interpolation"];
                if (stream.match(/^(charset|document|font-face|import|(-(moz|ms|o|webkit)-)?keyframes|media|namespace|page|supports)\b/i, false)) return false;
                stream.eatWhile(/[\w\\\-]/);
                if (stream.match(/^\s*:/, false))
                    return ["variable-2", "variable-definition"];
                return ["variable-2", "variable"];
            },
            "&": function () {
                return ["atom", "atom"];
            }
        },
        name: "css",
        helperType: "less"
    });

    CodeMirror.defineMIME("text/x-gss", {
        documentTypes: documentTypes,
        mediaTypes: mediaTypes,
        mediaFeatures: mediaFeatures,
        propertyKeywords: propertyKeywords,
        nonStandardPropertyKeywords: nonStandardPropertyKeywords,
        fontProperties: fontProperties,
        counterDescriptors: counterDescriptors,
        colorKeywords: colorKeywords,
        valueKeywords: valueKeywords,
        supportsAtComponent: true,
        tokenHooks: {
            "/": function (stream, state) {
                if (!stream.eat("*")) return false;
                state.tokenize = tokenCComment;
                return tokenCComment(stream, state);
            }
        },
        name: "css",
        helperType: "gss"
    });

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"), require("../htmlmixed/htmlmixed"),
            require("../../addon/mode/multiplex"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror", "../htmlmixed/htmlmixed",
            "../../addon/mode/multiplex"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    CodeMirror.defineMode("htmlembedded", function (config, parserConfig) {
        var closeComment = parserConfig.closeComment || "--%>"
        return CodeMirror.multiplexingMode(CodeMirror.getMode(config, "htmlmixed"), {
            open: parserConfig.openComment || "<%--",
            close: closeComment,
            delimStyle: "comment",
            mode: {
                token: function (stream) {
                    stream.skipTo(closeComment) || stream.skipToEnd()
                    return "comment"
                }
            }
        }, {
            open: parserConfig.open || parserConfig.scriptStartRegex || "<%",
            close: parserConfig.close || parserConfig.scriptEndRegex || "%>",
            mode: CodeMirror.getMode(config, parserConfig.scriptingModeSpec)
        });
    }, "htmlmixed");

    CodeMirror.defineMIME("application/x-ejs", {name: "htmlembedded", scriptingModeSpec: "javascript"});
    CodeMirror.defineMIME("application/x-aspx", {name: "htmlembedded", scriptingModeSpec: "text/x-csharp"});
    CodeMirror.defineMIME("application/x-jsp", {name: "htmlembedded", scriptingModeSpec: "text/x-java"});
    CodeMirror.defineMIME("application/x-erb", {name: "htmlembedded", scriptingModeSpec: "ruby"});
});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"), require("../xml/xml"), require("../javascript/javascript"), require("../css/css"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror", "../xml/xml", "../javascript/javascript", "../css/css"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    var defaultTags = {
        script: [
            ["lang", /(javascript|babel)/i, "javascript"],
            ["type", /^(?:text|application)\/(?:x-)?(?:java|ecma)script$|^module$|^$/i, "javascript"],
            ["type", /./, "text/plain"],
            [null, null, "javascript"]
        ],
        style: [
            ["lang", /^css$/i, "css"],
            ["type", /^(text\/)?(x-)?(stylesheet|css)$/i, "css"],
            ["type", /./, "text/plain"],
            [null, null, "css"]
        ]
    };

    function maybeBackup(stream, pat, style) {
        var cur = stream.current(), close = cur.search(pat);
        if (close > -1) {
            stream.backUp(cur.length-close);
        } else if (cur.match(/<\/?$/)) {
            stream.backUp(cur.length);
            if (!stream.match(pat, false)) stream.match(cur);
        }
        return style;
    }

    var attrRegexpCache = {};

    function getAttrRegexp(attr) {
        var regexp = attrRegexpCache[attr];
        if (regexp) return regexp;
        return attrRegexpCache[attr] = new RegExp("\\s+"+attr+"\\s*=\\s*('|\")?([^'\"]+)('|\")?\\s*");
    }

    function getAttrValue(text, attr) {
        var match = text.match(getAttrRegexp(attr))
        return match ? /^\s*(.*?)\s*$/.exec(match[2])[1] : ""
    }

    function getTagRegexp(tagName, anchored) {
        return new RegExp((anchored ? "^" : "")+"<\/\\s*"+tagName+"\\s*>", "i");
    }

    function addTags(from, to) {
        for (var tag in from) {
            var dest = to[tag] || (to[tag] = []);
            var source = from[tag];
            for (var i = source.length-1; i >= 0; i--)
                dest.unshift(source[i])
        }
    }

    function findMatchingMode(tagInfo, tagText) {
        for (var i = 0; i < tagInfo.length; i++) {
            var spec = tagInfo[i];
            if (!spec[0] || spec[1].test(getAttrValue(tagText, spec[0]))) return spec[2];
        }
    }

    CodeMirror.defineMode("htmlmixed", function (config, parserConfig) {
        var htmlMode = CodeMirror.getMode(config, {
            name: "xml",
            htmlMode: true,
            multilineTagIndentFactor: parserConfig.multilineTagIndentFactor,
            multilineTagIndentPastTag: parserConfig.multilineTagIndentPastTag,
            allowMissingTagName: parserConfig.allowMissingTagName,
        });

        var tags = {};
        var configTags = parserConfig && parserConfig.tags, configScript = parserConfig && parserConfig.scriptTypes;
        addTags(defaultTags, tags);
        if (configTags) addTags(configTags, tags);
        if (configScript) for (var i = configScript.length-1; i >= 0; i--)
            tags.script.unshift(["type", configScript[i].matches, configScript[i].mode])

        function html(stream, state) {
            var style = htmlMode.token(stream, state.htmlState), tag = /\btag\b/.test(style), tagName
            if (tag && !/[<>\s\/]/.test(stream.current()) &&
                (tagName = state.htmlState.tagName && state.htmlState.tagName.toLowerCase()) &&
                tags.hasOwnProperty(tagName)) {
                state.inTag = tagName+" "
            } else if (state.inTag && tag && />$/.test(stream.current())) {
                var inTag = /^([\S]+) (.*)/.exec(state.inTag)
                state.inTag = null
                var modeSpec = stream.current() == ">" && findMatchingMode(tags[inTag[1]], inTag[2])
                var mode = CodeMirror.getMode(config, modeSpec)
                var endTagA = getTagRegexp(inTag[1], true), endTag = getTagRegexp(inTag[1], false);
                state.token = function (stream, state) {
                    if (stream.match(endTagA, false)) {
                        state.token = html;
                        state.localState = state.localMode = null;
                        return null;
                    }
                    return maybeBackup(stream, endTag, state.localMode.token(stream, state.localState));
                };
                state.localMode = mode;
                state.localState = CodeMirror.startState(mode, htmlMode.indent(state.htmlState, "", ""));
            } else if (state.inTag) {
                state.inTag += stream.current()
                if (stream.eol()) state.inTag += " "
            }
            return style;
        };

        return {
            startState: function () {
                var state = CodeMirror.startState(htmlMode);
                return {token: html, inTag: null, localMode: null, localState: null, htmlState: state};
            },

            copyState: function (state) {
                var local;
                if (state.localState) {
                    local = CodeMirror.copyState(state.localMode, state.localState);
                }
                return {
                    token: state.token, inTag: state.inTag,
                    localMode: state.localMode, localState: local,
                    htmlState: CodeMirror.copyState(htmlMode, state.htmlState)
                };
            },

            token: function (stream, state) {
                return state.token(stream, state);
            },

            indent: function (state, textAfter, line) {
                if (!state.localMode || /^\s*<\//.test(textAfter))
                    return htmlMode.indent(state.htmlState, textAfter, line);
                else if (state.localMode.indent)
                    return state.localMode.indent(state.localState, textAfter, line);
                else
                    return CodeMirror.Pass;
            },

            innerMode: function (state) {
                return {state: state.localState || state.htmlState, mode: state.localMode || htmlMode};
            }
        };
    }, "xml", "javascript", "css");

    CodeMirror.defineMIME("text/html", "htmlmixed");
});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    CodeMirror.defineMode("javascript", function (config, parserConfig) {
        var indentUnit = config.indentUnit;
        var statementIndent = parserConfig.statementIndent;
        var jsonldMode = parserConfig.jsonld;
        var jsonMode = parserConfig.json || jsonldMode;
        var trackScope = parserConfig.trackScope !== false
        var isTS = parserConfig.typescript;
        var wordRE = parserConfig.wordCharacters || /[\w$\xa1-\uffff]/;

        // Tokenizer

        var keywords = function () {
            function kw(type) {
                return {type: type, style: "keyword"};
            }

            var A = kw("keyword a"), B = kw("keyword b"), C = kw("keyword c"), D = kw("keyword d");
            var operator = kw("operator"), atom = {type: "atom", style: "atom"};

            return {
                "if": kw("if"), "while": A, "with": A, "else": B, "do": B, "try": B, "finally": B,
                "return": D, "break": D, "continue": D, "new": kw("new"), "delete": C, "void": C, "throw": C,
                "debugger": kw("debugger"), "var": kw("var"), "const": kw("var"), "let": kw("var"),
                "function": kw("function"), "catch": kw("catch"),
                "for": kw("for"), "switch": kw("switch"), "case": kw("case"), "default": kw("default"),
                "in": operator, "typeof": operator, "instanceof": operator,
                "true": atom, "false": atom, "null": atom, "undefined": atom, "NaN": atom, "Infinity": atom,
                "this": kw("this"), "class": kw("class"), "super": kw("atom"),
                "yield": C, "export": kw("export"), "import": kw("import"), "extends": C,
                "await": C
            };
        }();

        var isOperatorChar = /[+\-*&%=<>!?|~^@]/;
        var isJsonldKeyword = /^@(context|id|value|language|type|container|list|set|reverse|index|base|vocab|graph)"/;

        function readRegexp(stream) {
            var escaped = false, next, inSet = false;
            while ((next = stream.next()) != null) {
                if (!escaped) {
                    if (next == "/" && !inSet) return;
                    if (next == "[") inSet = true;
                    else if (inSet && next == "]") inSet = false;
                }
                escaped = !escaped && next == "\\";
            }
        }

        // Used as scratch variables to communicate multiple values without
        // consing up tons of objects.
        var type, content;

        function ret(tp, style, cont) {
            type = tp;
            content = cont;
            return style;
        }

        function tokenBase(stream, state) {
            var ch = stream.next();
            if (ch == '"' || ch == "'") {
                state.tokenize = tokenString(ch);
                return state.tokenize(stream, state);
            } else if (ch == "." && stream.match(/^\d[\d_]*(?:[eE][+\-]?[\d_]+)?/)) {
                return ret("number", "number");
            } else if (ch == "." && stream.match("..")) {
                return ret("spread", "meta");
            } else if (/[\[\]{}\(\),;\:\.]/.test(ch)) {
                return ret(ch);
            } else if (ch == "=" && stream.eat(">")) {
                return ret("=>", "operator");
            } else if (ch == "0" && stream.match(/^(?:x[\dA-Fa-f_]+|o[0-7_]+|b[01_]+)n?/)) {
                return ret("number", "number");
            } else if (/\d/.test(ch)) {
                stream.match(/^[\d_]*(?:n|(?:\.[\d_]*)?(?:[eE][+\-]?[\d_]+)?)?/);
                return ret("number", "number");
            } else if (ch == "/") {
                if (stream.eat("*")) {
                    state.tokenize = tokenComment;
                    return tokenComment(stream, state);
                } else if (stream.eat("/")) {
                    stream.skipToEnd();
                    return ret("comment", "comment");
                } else if (expressionAllowed(stream, state, 1)) {
                    readRegexp(stream);
                    stream.match(/^\b(([gimyus])(?![gimyus]*\2))+\b/);
                    return ret("regexp", "string-2");
                } else {
                    stream.eat("=");
                    return ret("operator", "operator", stream.current());
                }
            } else if (ch == "`") {
                state.tokenize = tokenQuasi;
                return tokenQuasi(stream, state);
            } else if (ch == "#" && stream.peek() == "!") {
                stream.skipToEnd();
                return ret("meta", "meta");
            } else if (ch == "#" && stream.eatWhile(wordRE)) {
                return ret("variable", "property")
            } else if (ch == "<" && stream.match("!--") ||
                (ch == "-" && stream.match("->") && !/\S/.test(stream.string.slice(0, stream.start)))) {
                stream.skipToEnd()
                return ret("comment", "comment")
            } else if (isOperatorChar.test(ch)) {
                if (ch != ">" || !state.lexical || state.lexical.type != ">") {
                    if (stream.eat("=")) {
                        if (ch == "!" || ch == "=") stream.eat("=")
                    } else if (/[<>*+\-|&?]/.test(ch)) {
                        stream.eat(ch)
                        if (ch == ">") stream.eat(ch)
                    }
                }
                if (ch == "?" && stream.eat(".")) return ret(".")
                return ret("operator", "operator", stream.current());
            } else if (wordRE.test(ch)) {
                stream.eatWhile(wordRE);
                var word = stream.current()
                if (state.lastType != ".") {
                    if (keywords.propertyIsEnumerable(word)) {
                        var kw = keywords[word]
                        return ret(kw.type, kw.style, word)
                    }
                    if (word == "async" && stream.match(/^(\s|\/\*([^*]|\*(?!\/))*?\*\/)*[\[\(\w]/, false))
                        return ret("async", "keyword", word)
                }
                return ret("variable", "variable", word)
            }
        }

        function tokenString(quote) {
            return function (stream, state) {
                var escaped = false, next;
                if (jsonldMode && stream.peek() == "@" && stream.match(isJsonldKeyword)) {
                    state.tokenize = tokenBase;
                    return ret("jsonld-keyword", "meta");
                }
                while ((next = stream.next()) != null) {
                    if (next == quote && !escaped) break;
                    escaped = !escaped && next == "\\";
                }
                if (!escaped) state.tokenize = tokenBase;
                return ret("string", "string");
            };
        }

        function tokenComment(stream, state) {
            var maybeEnd = false, ch;
            while (ch = stream.next()) {
                if (ch == "/" && maybeEnd) {
                    state.tokenize = tokenBase;
                    break;
                }
                maybeEnd = (ch == "*");
            }
            return ret("comment", "comment");
        }

        function tokenQuasi(stream, state) {
            var escaped = false, next;
            while ((next = stream.next()) != null) {
                if (!escaped && (next == "`" || next == "$" && stream.eat("{"))) {
                    state.tokenize = tokenBase;
                    break;
                }
                escaped = !escaped && next == "\\";
            }
            return ret("quasi", "string-2", stream.current());
        }

        var brackets = "([{}])";
        // This is a crude lookahead trick to try and notice that we're
        // parsing the argument patterns for a fat-arrow function before we
        // actually hit the arrow token. It only works if the arrow is on
        // the same line as the arguments and there's no strange noise
        // (comments) in between. Fallback is to only notice when we hit the
        // arrow, and not declare the arguments as locals for the arrow
        // body.
        function findFatArrow(stream, state) {
            if (state.fatArrowAt) state.fatArrowAt = null;
            var arrow = stream.string.indexOf("=>", stream.start);
            if (arrow < 0) return;

            if (isTS) { // Try to skip TypeScript return type declarations after the arguments
                var m = /:\s*(?:\w+(?:<[^>]*>|\[\])?|\{[^}]*\})\s*$/.exec(stream.string.slice(stream.start, arrow))
                if (m) arrow = m.index
            }

            var depth = 0, sawSomething = false;
            for (var pos = arrow-1; pos >= 0; --pos) {
                var ch = stream.string.charAt(pos);
                var bracket = brackets.indexOf(ch);
                if (bracket >= 0 && bracket < 3) {
                    if (!depth) {
                        ++pos;
                        break;
                    }
                    if (--depth == 0) {
                        if (ch == "(") sawSomething = true;
                        break;
                    }
                } else if (bracket >= 3 && bracket < 6) {
                    ++depth;
                } else if (wordRE.test(ch)) {
                    sawSomething = true;
                } else if (/["'\/`]/.test(ch)) {
                    for (; ; --pos) {
                        if (pos == 0) return
                        var next = stream.string.charAt(pos-1)
                        if (next == ch && stream.string.charAt(pos-2) != "\\") {
                            pos--;
                            break
                        }
                    }
                } else if (sawSomething && !depth) {
                    ++pos;
                    break;
                }
            }
            if (sawSomething && !depth) state.fatArrowAt = pos;
        }

        // Parser

        var atomicTypes = {
            "atom": true, "number": true, "variable": true, "string": true,
            "regexp": true, "this": true, "import": true, "jsonld-keyword": true
        };

        function JSLexical(indented, column, type, align, prev, info) {
            this.indented = indented;
            this.column = column;
            this.type = type;
            this.prev = prev;
            this.info = info;
            if (align != null) this.align = align;
        }

        function inScope(state, varname) {
            if (!trackScope) return false
            for (var v = state.localVars; v; v = v.next)
                if (v.name == varname) return true;
            for (var cx = state.context; cx; cx = cx.prev) {
                for (var v = cx.vars; v; v = v.next)
                    if (v.name == varname) return true;
            }
        }

        function parseJS(state, style, type, content, stream) {
            var cc = state.cc;
            // Communicate our context to the combinators.
            // (Less wasteful than consing up a hundred closures on every call.)
            cx.state = state;
            cx.stream = stream;
            cx.marked = null, cx.cc = cc;
            cx.style = style;

            if (!state.lexical.hasOwnProperty("align"))
                state.lexical.align = true;

            while (true) {
                var combinator = cc.length ? cc.pop() : jsonMode ? expression : statement;
                if (combinator(type, content)) {
                    while (cc.length && cc[cc.length-1].lex)
                        cc.pop()();
                    if (cx.marked) return cx.marked;
                    if (type == "variable" && inScope(state, content)) return "variable-2";
                    return style;
                }
            }
        }

        // Combinator utils

        var cx = {state: null, column: null, marked: null, cc: null};

        function pass() {
            for (var i = arguments.length-1; i >= 0; i--) cx.cc.push(arguments[i]);
        }

        function cont() {
            pass.apply(null, arguments);
            return true;
        }

        function inList(name, list) {
            for (var v = list; v; v = v.next) if (v.name == name) return true
            return false;
        }

        function register(varname) {
            var state = cx.state;
            cx.marked = "def";
            if (!trackScope) return
            if (state.context) {
                if (state.lexical.info == "var" && state.context && state.context.block) {
                    // FIXME function decls are also not block scoped
                    var newContext = registerVarScoped(varname, state.context)
                    if (newContext != null) {
                        state.context = newContext
                        return
                    }
                } else if (!inList(varname, state.localVars)) {
                    state.localVars = new Var(varname, state.localVars)
                    return
                }
            }
            // Fall through means this is global
            if (parserConfig.globalVars && !inList(varname, state.globalVars))
                state.globalVars = new Var(varname, state.globalVars)
        }

        function registerVarScoped(varname, context) {
            if (!context) {
                return null
            } else if (context.block) {
                var inner = registerVarScoped(varname, context.prev)
                if (!inner) return null
                if (inner == context.prev) return context
                return new Context(inner, context.vars, true)
            } else if (inList(varname, context.vars)) {
                return context
            } else {
                return new Context(context.prev, new Var(varname, context.vars), false)
            }
        }

        function isModifier(name) {
            return name == "public" || name == "private" || name == "protected" || name == "abstract" || name == "readonly"
        }

        // Combinators

        function Context(prev, vars, block) {
            this.prev = prev;
            this.vars = vars;
            this.block = block
        }

        function Var(name, next) {
            this.name = name;
            this.next = next
        }

        var defaultVars = new Var("this", new Var("arguments", null))

        function pushcontext() {
            cx.state.context = new Context(cx.state.context, cx.state.localVars, false)
            cx.state.localVars = defaultVars
        }

        function pushblockcontext() {
            cx.state.context = new Context(cx.state.context, cx.state.localVars, true)
            cx.state.localVars = null
        }

        pushcontext.lex = pushblockcontext.lex = true

        function popcontext() {
            cx.state.localVars = cx.state.context.vars
            cx.state.context = cx.state.context.prev
        }

        popcontext.lex = true

        function pushlex(type, info) {
            var result = function () {
                var state = cx.state, indent = state.indented;
                if (state.lexical.type == "stat") indent = state.lexical.indented;
                else for (var outer = state.lexical; outer && outer.type == ")" && outer.align; outer = outer.prev)
                    indent = outer.indented;
                state.lexical = new JSLexical(indent, cx.stream.column(), type, null, state.lexical, info);
            };
            result.lex = true;
            return result;
        }

        function poplex() {
            var state = cx.state;
            if (state.lexical.prev) {
                if (state.lexical.type == ")")
                    state.indented = state.lexical.indented;
                state.lexical = state.lexical.prev;
            }
        }

        poplex.lex = true;

        function expect(wanted) {
            function exp(type) {
                if (type == wanted) return cont();
                else if (wanted == ";" || type == "}" || type == ")" || type == "]") return pass();
                else return cont(exp);
            };
            return exp;
        }

        function statement(type, value) {
            if (type == "var") return cont(pushlex("vardef", value), vardef, expect(";"), poplex);
            if (type == "keyword a") return cont(pushlex("form"), parenExpr, statement, poplex);
            if (type == "keyword b") return cont(pushlex("form"), statement, poplex);
            if (type == "keyword d") return cx.stream.match(/^\s*$/, false) ? cont() : cont(pushlex("stat"), maybeexpression, expect(";"), poplex);
            if (type == "debugger") return cont(expect(";"));
            if (type == "{") return cont(pushlex("}"), pushblockcontext, block, poplex, popcontext);
            if (type == ";") return cont();
            if (type == "if") {
                if (cx.state.lexical.info == "else" && cx.state.cc[cx.state.cc.length-1] == poplex)
                    cx.state.cc.pop()();
                return cont(pushlex("form"), parenExpr, statement, poplex, maybeelse);
            }
            if (type == "function") return cont(functiondef);
            if (type == "for") return cont(pushlex("form"), pushblockcontext, forspec, statement, popcontext, poplex);
            if (type == "class" || (isTS && value == "interface")) {
                cx.marked = "keyword"
                return cont(pushlex("form", type == "class" ? type : value), className, poplex)
            }
            if (type == "variable") {
                if (isTS && value == "declare") {
                    cx.marked = "keyword"
                    return cont(statement)
                } else if (isTS && (value == "module" || value == "enum" || value == "type") && cx.stream.match(/^\s*\w/, false)) {
                    cx.marked = "keyword"
                    if (value == "enum") return cont(enumdef);
                    else if (value == "type") return cont(typename, expect("operator"), typeexpr, expect(";"));
                    else return cont(pushlex("form"), pattern, expect("{"), pushlex("}"), block, poplex, poplex)
                } else if (isTS && value == "namespace") {
                    cx.marked = "keyword"
                    return cont(pushlex("form"), expression, statement, poplex)
                } else if (isTS && value == "abstract") {
                    cx.marked = "keyword"
                    return cont(statement)
                } else {
                    return cont(pushlex("stat"), maybelabel);
                }
            }
            if (type == "switch") return cont(pushlex("form"), parenExpr, expect("{"), pushlex("}", "switch"), pushblockcontext,
                block, poplex, poplex, popcontext);
            if (type == "case") return cont(expression, expect(":"));
            if (type == "default") return cont(expect(":"));
            if (type == "catch") return cont(pushlex("form"), pushcontext, maybeCatchBinding, statement, poplex, popcontext);
            if (type == "export") return cont(pushlex("stat"), afterExport, poplex);
            if (type == "import") return cont(pushlex("stat"), afterImport, poplex);
            if (type == "async") return cont(statement)
            if (value == "@") return cont(expression, statement)
            return pass(pushlex("stat"), expression, expect(";"), poplex);
        }

        function maybeCatchBinding(type) {
            if (type == "(") return cont(funarg, expect(")"))
        }

        function expression(type, value) {
            return expressionInner(type, value, false);
        }

        function expressionNoComma(type, value) {
            return expressionInner(type, value, true);
        }

        function parenExpr(type) {
            if (type != "(") return pass()
            return cont(pushlex(")"), maybeexpression, expect(")"), poplex)
        }

        function expressionInner(type, value, noComma) {
            if (cx.state.fatArrowAt == cx.stream.start) {
                var body = noComma ? arrowBodyNoComma : arrowBody;
                if (type == "(") return cont(pushcontext, pushlex(")"), commasep(funarg, ")"), poplex, expect("=>"), body, popcontext);
                else if (type == "variable") return pass(pushcontext, pattern, expect("=>"), body, popcontext);
            }

            var maybeop = noComma ? maybeoperatorNoComma : maybeoperatorComma;
            if (atomicTypes.hasOwnProperty(type)) return cont(maybeop);
            if (type == "function") return cont(functiondef, maybeop);
            if (type == "class" || (isTS && value == "interface")) {
                cx.marked = "keyword";
                return cont(pushlex("form"), classExpression, poplex);
            }
            if (type == "keyword c" || type == "async") return cont(noComma ? expressionNoComma : expression);
            if (type == "(") return cont(pushlex(")"), maybeexpression, expect(")"), poplex, maybeop);
            if (type == "operator" || type == "spread") return cont(noComma ? expressionNoComma : expression);
            if (type == "[") return cont(pushlex("]"), arrayLiteral, poplex, maybeop);
            if (type == "{") return contCommasep(objprop, "}", null, maybeop);
            if (type == "quasi") return pass(quasi, maybeop);
            if (type == "new") return cont(maybeTarget(noComma));
            return cont();
        }

        function maybeexpression(type) {
            if (type.match(/[;\}\)\],]/)) return pass();
            return pass(expression);
        }

        function maybeoperatorComma(type, value) {
            if (type == ",") return cont(maybeexpression);
            return maybeoperatorNoComma(type, value, false);
        }

        function maybeoperatorNoComma(type, value, noComma) {
            var me = noComma == false ? maybeoperatorComma : maybeoperatorNoComma;
            var expr = noComma == false ? expression : expressionNoComma;
            if (type == "=>") return cont(pushcontext, noComma ? arrowBodyNoComma : arrowBody, popcontext);
            if (type == "operator") {
                if (/\+\+|--/.test(value) || isTS && value == "!") return cont(me);
                if (isTS && value == "<" && cx.stream.match(/^([^<>]|<[^<>]*>)*>\s*\(/, false))
                    return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, me);
                if (value == "?") return cont(expression, expect(":"), expr);
                return cont(expr);
            }
            if (type == "quasi") {
                return pass(quasi, me);
            }
            if (type == ";") return;
            if (type == "(") return contCommasep(expressionNoComma, ")", "call", me);
            if (type == ".") return cont(property, me);
            if (type == "[") return cont(pushlex("]"), maybeexpression, expect("]"), poplex, me);
            if (isTS && value == "as") {
                cx.marked = "keyword";
                return cont(typeexpr, me)
            }
            if (type == "regexp") {
                cx.state.lastType = cx.marked = "operator"
                cx.stream.backUp(cx.stream.pos-cx.stream.start-1)
                return cont(expr)
            }
        }

        function quasi(type, value) {
            if (type != "quasi") return pass();
            if (value.slice(value.length-2) != "${") return cont(quasi);
            return cont(maybeexpression, continueQuasi);
        }

        function continueQuasi(type) {
            if (type == "}") {
                cx.marked = "string-2";
                cx.state.tokenize = tokenQuasi;
                return cont(quasi);
            }
        }

        function arrowBody(type) {
            findFatArrow(cx.stream, cx.state);
            return pass(type == "{" ? statement : expression);
        }

        function arrowBodyNoComma(type) {
            findFatArrow(cx.stream, cx.state);
            return pass(type == "{" ? statement : expressionNoComma);
        }

        function maybeTarget(noComma) {
            return function (type) {
                if (type == ".") return cont(noComma ? targetNoComma : target);
                else if (type == "variable" && isTS) return cont(maybeTypeArgs, noComma ? maybeoperatorNoComma : maybeoperatorComma)
                else return pass(noComma ? expressionNoComma : expression);
            };
        }

        function target(_, value) {
            if (value == "target") {
                cx.marked = "keyword";
                return cont(maybeoperatorComma);
            }
        }

        function targetNoComma(_, value) {
            if (value == "target") {
                cx.marked = "keyword";
                return cont(maybeoperatorNoComma);
            }
        }

        function maybelabel(type) {
            if (type == ":") return cont(poplex, statement);
            return pass(maybeoperatorComma, expect(";"), poplex);
        }

        function property(type) {
            if (type == "variable") {
                cx.marked = "property";
                return cont();
            }
        }

        function objprop(type, value) {
            if (type == "async") {
                cx.marked = "property";
                return cont(objprop);
            } else if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property";
                if (value == "get" || value == "set") return cont(getterSetter);
                var m // Work around fat-arrow-detection complication for detecting typescript typed arrow params
                if (isTS && cx.state.fatArrowAt == cx.stream.start && (m = cx.stream.match(/^\s*:\s*/, false)))
                    cx.state.fatArrowAt = cx.stream.pos+m[0].length
                return cont(afterprop);
            } else if (type == "number" || type == "string") {
                cx.marked = jsonldMode ? "property" : (cx.style+" property");
                return cont(afterprop);
            } else if (type == "jsonld-keyword") {
                return cont(afterprop);
            } else if (isTS && isModifier(value)) {
                cx.marked = "keyword"
                return cont(objprop)
            } else if (type == "[") {
                return cont(expression, maybetype, expect("]"), afterprop);
            } else if (type == "spread") {
                return cont(expressionNoComma, afterprop);
            } else if (value == "*") {
                cx.marked = "keyword";
                return cont(objprop);
            } else if (type == ":") {
                return pass(afterprop)
            }
        }

        function getterSetter(type) {
            if (type != "variable") return pass(afterprop);
            cx.marked = "property";
            return cont(functiondef);
        }

        function afterprop(type) {
            if (type == ":") return cont(expressionNoComma);
            if (type == "(") return pass(functiondef);
        }

        function commasep(what, end, sep) {
            function proceed(type, value) {
                if (sep ? sep.indexOf(type) > -1 : type == ",") {
                    var lex = cx.state.lexical;
                    if (lex.info == "call") lex.pos = (lex.pos || 0)+1;
                    return cont(function (type, value) {
                        if (type == end || value == end) return pass()
                        return pass(what)
                    }, proceed);
                }
                if (type == end || value == end) return cont();
                if (sep && sep.indexOf(";") > -1) return pass(what)
                return cont(expect(end));
            }

            return function (type, value) {
                if (type == end || value == end) return cont();
                return pass(what, proceed);
            };
        }

        function contCommasep(what, end, info) {
            for (var i = 3; i < arguments.length; i++)
                cx.cc.push(arguments[i]);
            return cont(pushlex(end, info), commasep(what, end), poplex);
        }

        function block(type) {
            if (type == "}") return cont();
            return pass(statement, block);
        }

        function maybetype(type, value) {
            if (isTS) {
                if (type == ":") return cont(typeexpr);
                if (value == "?") return cont(maybetype);
            }
        }

        function maybetypeOrIn(type, value) {
            if (isTS && (type == ":" || value == "in")) return cont(typeexpr)
        }

        function mayberettype(type) {
            if (isTS && type == ":") {
                if (cx.stream.match(/^\s*\w+\s+is\b/, false)) return cont(expression, isKW, typeexpr)
                else return cont(typeexpr)
            }
        }

        function isKW(_, value) {
            if (value == "is") {
                cx.marked = "keyword"
                return cont()
            }
        }

        function typeexpr(type, value) {
            if (value == "keyof" || value == "typeof" || value == "infer" || value == "readonly") {
                cx.marked = "keyword"
                return cont(value == "typeof" ? expressionNoComma : typeexpr)
            }
            if (type == "variable" || value == "void") {
                cx.marked = "type"
                return cont(afterType)
            }
            if (value == "|" || value == "&") return cont(typeexpr)
            if (type == "string" || type == "number" || type == "atom") return cont(afterType);
            if (type == "[") return cont(pushlex("]"), commasep(typeexpr, "]", ","), poplex, afterType)
            if (type == "{") return cont(pushlex("}"), typeprops, poplex, afterType)
            if (type == "(") return cont(commasep(typearg, ")"), maybeReturnType, afterType)
            if (type == "<") return cont(commasep(typeexpr, ">"), typeexpr)
            if (type == "quasi") {
                return pass(quasiType, afterType);
            }
        }

        function maybeReturnType(type) {
            if (type == "=>") return cont(typeexpr)
        }

        function typeprops(type) {
            if (type.match(/[\}\)\]]/)) return cont()
            if (type == "," || type == ";") return cont(typeprops)
            return pass(typeprop, typeprops)
        }

        function typeprop(type, value) {
            if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property"
                return cont(typeprop)
            } else if (value == "?" || type == "number" || type == "string") {
                return cont(typeprop)
            } else if (type == ":") {
                return cont(typeexpr)
            } else if (type == "[") {
                return cont(expect("variable"), maybetypeOrIn, expect("]"), typeprop)
            } else if (type == "(") {
                return pass(functiondecl, typeprop)
            } else if (!type.match(/[;\}\)\],]/)) {
                return cont()
            }
        }

        function quasiType(type, value) {
            if (type != "quasi") return pass();
            if (value.slice(value.length-2) != "${") return cont(quasiType);
            return cont(typeexpr, continueQuasiType);
        }

        function continueQuasiType(type) {
            if (type == "}") {
                cx.marked = "string-2";
                cx.state.tokenize = tokenQuasi;
                return cont(quasiType);
            }
        }

        function typearg(type, value) {
            if (type == "variable" && cx.stream.match(/^\s*[?:]/, false) || value == "?") return cont(typearg)
            if (type == ":") return cont(typeexpr)
            if (type == "spread") return cont(typearg)
            return pass(typeexpr)
        }

        function afterType(type, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, afterType)
            if (value == "|" || type == "." || value == "&") return cont(typeexpr)
            if (type == "[") return cont(typeexpr, expect("]"), afterType)
            if (value == "extends" || value == "implements") {
                cx.marked = "keyword";
                return cont(typeexpr)
            }
            if (value == "?") return cont(typeexpr, expect(":"), typeexpr)
        }

        function maybeTypeArgs(_, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeexpr, ">"), poplex, afterType)
        }

        function typeparam() {
            return pass(typeexpr, maybeTypeDefault)
        }

        function maybeTypeDefault(_, value) {
            if (value == "=") return cont(typeexpr)
        }

        function vardef(_, value) {
            if (value == "enum") {
                cx.marked = "keyword";
                return cont(enumdef)
            }
            return pass(pattern, maybetype, maybeAssign, vardefCont);
        }

        function pattern(type, value) {
            if (isTS && isModifier(value)) {
                cx.marked = "keyword";
                return cont(pattern)
            }
            if (type == "variable") {
                register(value);
                return cont();
            }
            if (type == "spread") return cont(pattern);
            if (type == "[") return contCommasep(eltpattern, "]");
            if (type == "{") return contCommasep(proppattern, "}");
        }

        function proppattern(type, value) {
            if (type == "variable" && !cx.stream.match(/^\s*:/, false)) {
                register(value);
                return cont(maybeAssign);
            }
            if (type == "variable") cx.marked = "property";
            if (type == "spread") return cont(pattern);
            if (type == "}") return pass();
            if (type == "[") return cont(expression, expect(']'), expect(':'), proppattern);
            return cont(expect(":"), pattern, maybeAssign);
        }

        function eltpattern() {
            return pass(pattern, maybeAssign)
        }

        function maybeAssign(_type, value) {
            if (value == "=") return cont(expressionNoComma);
        }

        function vardefCont(type) {
            if (type == ",") return cont(vardef);
        }

        function maybeelse(type, value) {
            if (type == "keyword b" && value == "else") return cont(pushlex("form", "else"), statement, poplex);
        }

        function forspec(type, value) {
            if (value == "await") return cont(forspec);
            if (type == "(") return cont(pushlex(")"), forspec1, poplex);
        }

        function forspec1(type) {
            if (type == "var") return cont(vardef, forspec2);
            if (type == "variable") return cont(forspec2);
            return pass(forspec2)
        }

        function forspec2(type, value) {
            if (type == ")") return cont()
            if (type == ";") return cont(forspec2)
            if (value == "in" || value == "of") {
                cx.marked = "keyword";
                return cont(expression, forspec2)
            }
            return pass(expression, forspec2)
        }

        function functiondef(type, value) {
            if (value == "*") {
                cx.marked = "keyword";
                return cont(functiondef);
            }
            if (type == "variable") {
                register(value);
                return cont(functiondef);
            }
            if (type == "(") return cont(pushcontext, pushlex(")"), commasep(funarg, ")"), poplex, mayberettype, statement, popcontext);
            if (isTS && value == "<") return cont(pushlex(">"), commasep(typeparam, ">"), poplex, functiondef)
        }

        function functiondecl(type, value) {
            if (value == "*") {
                cx.marked = "keyword";
                return cont(functiondecl);
            }
            if (type == "variable") {
                register(value);
                return cont(functiondecl);
            }
            if (type == "(") return cont(pushcontext, pushlex(")"), commasep(funarg, ")"), poplex, mayberettype, popcontext);
            if (isTS && value == "<") return cont(pushlex(">"), commasep(typeparam, ">"), poplex, functiondecl)
        }

        function typename(type, value) {
            if (type == "keyword" || type == "variable") {
                cx.marked = "type"
                return cont(typename)
            } else if (value == "<") {
                return cont(pushlex(">"), commasep(typeparam, ">"), poplex)
            }
        }

        function funarg(type, value) {
            if (value == "@") cont(expression, funarg)
            if (type == "spread") return cont(funarg);
            if (isTS && isModifier(value)) {
                cx.marked = "keyword";
                return cont(funarg);
            }
            if (isTS && type == "this") return cont(maybetype, maybeAssign)
            return pass(pattern, maybetype, maybeAssign);
        }

        function classExpression(type, value) {
            // Class expressions may have an optional name.
            if (type == "variable") return className(type, value);
            return classNameAfter(type, value);
        }

        function className(type, value) {
            if (type == "variable") {
                register(value);
                return cont(classNameAfter);
            }
        }

        function classNameAfter(type, value) {
            if (value == "<") return cont(pushlex(">"), commasep(typeparam, ">"), poplex, classNameAfter)
            if (value == "extends" || value == "implements" || (isTS && type == ",")) {
                if (value == "implements") cx.marked = "keyword";
                return cont(isTS ? typeexpr : expression, classNameAfter);
            }
            if (type == "{") return cont(pushlex("}"), classBody, poplex);
        }

        function classBody(type, value) {
            if (type == "async" ||
                (type == "variable" &&
                    (value == "static" || value == "get" || value == "set" || (isTS && isModifier(value))) &&
                    cx.stream.match(/^\s+[\w$\xa1-\uffff]/, false))) {
                cx.marked = "keyword";
                return cont(classBody);
            }
            if (type == "variable" || cx.style == "keyword") {
                cx.marked = "property";
                return cont(classfield, classBody);
            }
            if (type == "number" || type == "string") return cont(classfield, classBody);
            if (type == "[")
                return cont(expression, maybetype, expect("]"), classfield, classBody)
            if (value == "*") {
                cx.marked = "keyword";
                return cont(classBody);
            }
            if (isTS && type == "(") return pass(functiondecl, classBody)
            if (type == ";" || type == ",") return cont(classBody);
            if (type == "}") return cont();
            if (value == "@") return cont(expression, classBody)
        }

        function classfield(type, value) {
            if (value == "!") return cont(classfield)
            if (value == "?") return cont(classfield)
            if (type == ":") return cont(typeexpr, maybeAssign)
            if (value == "=") return cont(expressionNoComma)
            var context = cx.state.lexical.prev, isInterface = context && context.info == "interface"
            return pass(isInterface ? functiondecl : functiondef)
        }

        function afterExport(type, value) {
            if (value == "*") {
                cx.marked = "keyword";
                return cont(maybeFrom, expect(";"));
            }
            if (value == "default") {
                cx.marked = "keyword";
                return cont(expression, expect(";"));
            }
            if (type == "{") return cont(commasep(exportField, "}"), maybeFrom, expect(";"));
            return pass(statement);
        }

        function exportField(type, value) {
            if (value == "as") {
                cx.marked = "keyword";
                return cont(expect("variable"));
            }
            if (type == "variable") return pass(expressionNoComma, exportField);
        }

        function afterImport(type) {
            if (type == "string") return cont();
            if (type == "(") return pass(expression);
            if (type == ".") return pass(maybeoperatorComma);
            return pass(importSpec, maybeMoreImports, maybeFrom);
        }

        function importSpec(type, value) {
            if (type == "{") return contCommasep(importSpec, "}");
            if (type == "variable") register(value);
            if (value == "*") cx.marked = "keyword";
            return cont(maybeAs);
        }

        function maybeMoreImports(type) {
            if (type == ",") return cont(importSpec, maybeMoreImports)
        }

        function maybeAs(_type, value) {
            if (value == "as") {
                cx.marked = "keyword";
                return cont(importSpec);
            }
        }

        function maybeFrom(_type, value) {
            if (value == "from") {
                cx.marked = "keyword";
                return cont(expression);
            }
        }

        function arrayLiteral(type) {
            if (type == "]") return cont();
            return pass(commasep(expressionNoComma, "]"));
        }

        function enumdef() {
            return pass(pushlex("form"), pattern, expect("{"), pushlex("}"), commasep(enummember, "}"), poplex, poplex)
        }

        function enummember() {
            return pass(pattern, maybeAssign);
        }

        function isContinuedStatement(state, textAfter) {
            return state.lastType == "operator" || state.lastType == "," ||
                isOperatorChar.test(textAfter.charAt(0)) ||
                /[,.]/.test(textAfter.charAt(0));
        }

        function expressionAllowed(stream, state, backUp) {
            return state.tokenize == tokenBase &&
                /^(?:operator|sof|keyword [bcd]|case|new|export|default|spread|[\[{}\(,;:]|=>)$/.test(state.lastType) ||
                (state.lastType == "quasi" && /\{\s*$/.test(stream.string.slice(0, stream.pos-(backUp || 0))))
        }

        // Interface

        return {
            startState: function (basecolumn) {
                var state = {
                    tokenize: tokenBase,
                    lastType: "sof",
                    cc: [],
                    lexical: new JSLexical((basecolumn || 0)-indentUnit, 0, "block", false),
                    localVars: parserConfig.localVars,
                    context: parserConfig.localVars && new Context(null, null, false),
                    indented: basecolumn || 0
                };
                if (parserConfig.globalVars && typeof parserConfig.globalVars == "object")
                    state.globalVars = parserConfig.globalVars;
                return state;
            },

            token: function (stream, state) {
                if (stream.sol()) {
                    if (!state.lexical.hasOwnProperty("align"))
                        state.lexical.align = false;
                    state.indented = stream.indentation();
                    findFatArrow(stream, state);
                }
                if (state.tokenize != tokenComment && stream.eatSpace()) return null;
                var style = state.tokenize(stream, state);
                if (type == "comment") return style;
                state.lastType = type == "operator" && (content == "++" || content == "--") ? "incdec" : type;
                return parseJS(state, style, type, content, stream);
            },

            indent: function (state, textAfter) {
                if (state.tokenize == tokenComment || state.tokenize == tokenQuasi) return CodeMirror.Pass;
                if (state.tokenize != tokenBase) return 0;
                var firstChar = textAfter && textAfter.charAt(0), lexical = state.lexical, top
                // Kludge to prevent 'maybelse' from blocking lexical scope pops
                if (!/^\s*else\b/.test(textAfter)) for (var i = state.cc.length-1; i >= 0; --i) {
                    var c = state.cc[i];
                    if (c == poplex) lexical = lexical.prev;
                    else if (c != maybeelse && c != popcontext) break;
                }
                while ((lexical.type == "stat" || lexical.type == "form") &&
                (firstChar == "}" || ((top = state.cc[state.cc.length-1]) &&
                    (top == maybeoperatorComma || top == maybeoperatorNoComma) &&
                    !/^[,\.=+\-*:?[\(]/.test(textAfter))))
                    lexical = lexical.prev;
                if (statementIndent && lexical.type == ")" && lexical.prev.type == "stat")
                    lexical = lexical.prev;
                var type = lexical.type, closing = firstChar == type;

                if (type == "vardef") return lexical.indented+(state.lastType == "operator" || state.lastType == "," ? lexical.info.length+1 : 0);
                else if (type == "form" && firstChar == "{") return lexical.indented;
                else if (type == "form") return lexical.indented+indentUnit;
                else if (type == "stat")
                    return lexical.indented+(isContinuedStatement(state, textAfter) ? statementIndent || indentUnit : 0);
                else if (lexical.info == "switch" && !closing && parserConfig.doubleIndentSwitch != false)
                    return lexical.indented+(/^(?:case|default)\b/.test(textAfter) ? indentUnit : 2 * indentUnit);
                else if (lexical.align) return lexical.column+(closing ? 0 : 1);
                else return lexical.indented+(closing ? 0 : indentUnit);
            },

            electricInput: /^\s*(?:case .*?:|default:|\{|\})$/,
            blockCommentStart: jsonMode ? null : "/*",
            blockCommentEnd: jsonMode ? null : "*/",
            blockCommentContinue: jsonMode ? null : " * ",
            lineComment: jsonMode ? null : "//",
            fold: "brace",
            closeBrackets: "()[]{}''\"\"``",

            helperType: jsonMode ? "json" : "javascript",
            jsonldMode: jsonldMode,
            jsonMode: jsonMode,

            expressionAllowed: expressionAllowed,

            skipExpression: function (state) {
                parseJS(state, "atom", "atom", "true", new CodeMirror.StringStream("", 2, null))
            }
        };
    });

    CodeMirror.registerHelper("wordChars", "javascript", /[\w$]/);

    CodeMirror.defineMIME("text/javascript", "javascript");
    CodeMirror.defineMIME("text/ecmascript", "javascript");
    CodeMirror.defineMIME("application/javascript", "javascript");
    CodeMirror.defineMIME("application/x-javascript", "javascript");
    CodeMirror.defineMIME("application/ecmascript", "javascript");
    CodeMirror.defineMIME("application/json", {name: "javascript", json: true});
    CodeMirror.defineMIME("application/x-json", {name: "javascript", json: true});
    CodeMirror.defineMIME("application/manifest+json", {name: "javascript", json: true})
    CodeMirror.defineMIME("application/ld+json", {name: "javascript", jsonld: true});
    CodeMirror.defineMIME("text/typescript", {name: "javascript", typescript: true});
    CodeMirror.defineMIME("application/typescript", {name: "javascript", typescript: true});

});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"), require("../htmlmixed/htmlmixed"), require("../clike/clike"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror", "../htmlmixed/htmlmixed", "../clike/clike"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    function keywords(str) {
        var obj = {}, words = str.split(" ");
        for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
        return obj;
    }

    // Helper for phpString
    function matchSequence(list, end, escapes) {
        if (list.length == 0) return phpString(end);
        return function (stream, state) {
            var patterns = list[0];
            for (var i = 0; i < patterns.length; i++) if (stream.match(patterns[i][0])) {
                state.tokenize = matchSequence(list.slice(1), end);
                return patterns[i][1];
            }
            state.tokenize = phpString(end, escapes);
            return "string";
        };
    }

    function phpString(closing, escapes) {
        return function (stream, state) {
            return phpString_(stream, state, closing, escapes);
        };
    }

    function phpString_(stream, state, closing, escapes) {
        // "Complex" syntax
        if (escapes !== false && stream.match("${", false) || stream.match("{$", false)) {
            state.tokenize = null;
            return "string";
        }

        // Simple syntax
        if (escapes !== false && stream.match(/^\$[a-zA-Z_][a-zA-Z0-9_]*/)) {
            // After the variable name there may appear array or object operator.
            if (stream.match("[", false)) {
                // Match array operator
                state.tokenize = matchSequence([
                    [["[", null]],
                    [[/\d[\w\.]*/, "number"],
                        [/\$[a-zA-Z_][a-zA-Z0-9_]*/, "variable-2"],
                        [/[\w\$]+/, "variable"]],
                    [["]", null]]
                ], closing, escapes);
            }
            if (stream.match(/^->\w/, false)) {
                // Match object operator
                state.tokenize = matchSequence([
                    [["->", null]],
                    [[/[\w]+/, "variable"]]
                ], closing, escapes);
            }
            return "variable-2";
        }

        var escaped = false;
        // Normal string
        while (!stream.eol() &&
        (escaped || escapes === false ||
            (!stream.match("{$", false) &&
                !stream.match(/^(\$[a-zA-Z_][a-zA-Z0-9_]*|\$\{)/, false)))) {
            if (!escaped && stream.match(closing)) {
                state.tokenize = null;
                state.tokStack.pop();
                state.tokStack.pop();
                break;
            }
            escaped = stream.next() == "\\" && !escaped;
        }
        return "string";
    }

    var phpKeywords = "abstract and array as break case catch class clone const continue declare default "+
        "do else elseif enddeclare endfor endforeach endif endswitch endwhile enum extends final "+
        "for foreach function global goto if implements interface instanceof namespace "+
        "new or private protected public static switch throw trait try use var while xor "+
        "die echo empty exit eval include include_once isset list require require_once return "+
        "print unset __halt_compiler self static parent yield insteadof finally readonly match";
    var phpAtoms = "true false null TRUE FALSE NULL __CLASS__ __DIR__ __FILE__ __LINE__ __METHOD__ __FUNCTION__ __NAMESPACE__ __TRAIT__";
    var phpBuiltin = "func_num_args func_get_arg func_get_args strlen strcmp strncmp strcasecmp strncasecmp each error_reporting define defined trigger_error user_error set_error_handler restore_error_handler get_declared_classes get_loaded_extensions extension_loaded get_extension_funcs debug_backtrace constant bin2hex hex2bin sleep usleep time mktime gmmktime strftime gmstrftime strtotime date gmdate getdate localtime checkdate flush wordwrap htmlspecialchars htmlentities html_entity_decode md5 md5_file crc32 getimagesize image_type_to_mime_type phpinfo phpversion phpcredits strnatcmp strnatcasecmp substr_count strspn strcspn strtok strtoupper strtolower strpos strrpos strrev hebrev hebrevc nl2br basename dirname pathinfo stripslashes stripcslashes strstr stristr strrchr str_shuffle str_word_count strcoll substr substr_replace quotemeta ucfirst ucwords strtr addslashes addcslashes rtrim str_replace str_repeat count_chars chunk_split trim ltrim strip_tags similar_text explode implode setlocale localeconv parse_str str_pad chop strchr sprintf printf vprintf vsprintf sscanf fscanf parse_url urlencode urldecode rawurlencode rawurldecode readlink linkinfo link unlink exec system escapeshellcmd escapeshellarg passthru shell_exec proc_open proc_close rand srand getrandmax mt_rand mt_srand mt_getrandmax base64_decode base64_encode abs ceil floor round is_finite is_nan is_infinite bindec hexdec octdec decbin decoct dechex base_convert number_format fmod ip2long long2ip getenv putenv getopt microtime gettimeofday getrusage uniqid quoted_printable_decode set_time_limit get_cfg_var magic_quotes_runtime set_magic_quotes_runtime get_magic_quotes_gpc get_magic_quotes_runtime import_request_variables error_log serialize unserialize memory_get_usage memory_get_peak_usage var_dump var_export debug_zval_dump print_r highlight_file show_source highlight_string ini_get ini_get_all ini_set ini_alter ini_restore get_include_path set_include_path restore_include_path setcookie header headers_sent connection_aborted connection_status ignore_user_abort parse_ini_file is_uploaded_file move_uploaded_file intval floatval doubleval strval gettype settype is_null is_resource is_bool is_long is_float is_int is_integer is_double is_real is_numeric is_string is_array is_object is_scalar ereg ereg_replace eregi eregi_replace split spliti join sql_regcase dl pclose popen readfile rewind rmdir umask fclose feof fgetc fgets fgetss fread fopen fpassthru ftruncate fstat fseek ftell fflush fwrite fputs mkdir rename copy tempnam tmpfile file file_get_contents file_put_contents stream_select stream_context_create stream_context_set_params stream_context_set_option stream_context_get_options stream_filter_prepend stream_filter_append fgetcsv flock get_meta_tags stream_set_write_buffer set_file_buffer set_socket_blocking stream_set_blocking socket_set_blocking stream_get_meta_data stream_register_wrapper stream_wrapper_register stream_set_timeout socket_set_timeout socket_get_status realpath fnmatch fsockopen pfsockopen pack unpack get_browser crypt opendir closedir chdir getcwd rewinddir readdir dir glob fileatime filectime filegroup fileinode filemtime fileowner fileperms filesize filetype file_exists is_writable is_writeable is_readable is_executable is_file is_dir is_link stat lstat chown touch clearstatcache mail ob_start ob_flush ob_clean ob_end_flush ob_end_clean ob_get_flush ob_get_clean ob_get_length ob_get_level ob_get_status ob_get_contents ob_implicit_flush ob_list_handlers ksort krsort natsort natcasesort asort arsort sort rsort usort uasort uksort shuffle array_walk count end prev next reset current key min max in_array array_search extract compact array_fill range array_multisort array_push array_pop array_shift array_unshift array_splice array_slice array_merge array_merge_recursive array_keys array_values array_count_values array_reverse array_reduce array_pad array_flip array_change_key_case array_rand array_unique array_intersect array_intersect_assoc array_diff array_diff_assoc array_sum array_filter array_map array_chunk array_key_exists array_intersect_key array_combine array_column pos sizeof key_exists assert assert_options version_compare ftok str_rot13 aggregate session_name session_module_name session_save_path session_id session_regenerate_id session_decode session_register session_unregister session_is_registered session_encode session_start session_destroy session_unset session_set_save_handler session_cache_limiter session_cache_expire session_set_cookie_params session_get_cookie_params session_write_close preg_match preg_match_all preg_replace preg_replace_callback preg_split preg_quote preg_grep overload ctype_alnum ctype_alpha ctype_cntrl ctype_digit ctype_lower ctype_graph ctype_print ctype_punct ctype_space ctype_upper ctype_xdigit virtual apache_request_headers apache_note apache_lookup_uri apache_child_terminate apache_setenv apache_response_headers apache_get_version getallheaders mysql_connect mysql_pconnect mysql_close mysql_select_db mysql_create_db mysql_drop_db mysql_query mysql_unbuffered_query mysql_db_query mysql_list_dbs mysql_list_tables mysql_list_fields mysql_list_processes mysql_error mysql_errno mysql_affected_rows mysql_insert_id mysql_result mysql_num_rows mysql_num_fields mysql_fetch_row mysql_fetch_array mysql_fetch_assoc mysql_fetch_object mysql_data_seek mysql_fetch_lengths mysql_fetch_field mysql_field_seek mysql_free_result mysql_field_name mysql_field_table mysql_field_len mysql_field_type mysql_field_flags mysql_escape_string mysql_real_escape_string mysql_stat mysql_thread_id mysql_client_encoding mysql_get_client_info mysql_get_host_info mysql_get_proto_info mysql_get_server_info mysql_info mysql mysql_fieldname mysql_fieldtable mysql_fieldlen mysql_fieldtype mysql_fieldflags mysql_selectdb mysql_createdb mysql_dropdb mysql_freeresult mysql_numfields mysql_numrows mysql_listdbs mysql_listtables mysql_listfields mysql_db_name mysql_dbname mysql_tablename mysql_table_name pg_connect pg_pconnect pg_close pg_connection_status pg_connection_busy pg_connection_reset pg_host pg_dbname pg_port pg_tty pg_options pg_ping pg_query pg_send_query pg_cancel_query pg_fetch_result pg_fetch_row pg_fetch_assoc pg_fetch_array pg_fetch_object pg_fetch_all pg_affected_rows pg_get_result pg_result_seek pg_result_status pg_free_result pg_last_oid pg_num_rows pg_num_fields pg_field_name pg_field_num pg_field_size pg_field_type pg_field_prtlen pg_field_is_null pg_get_notify pg_get_pid pg_result_error pg_last_error pg_last_notice pg_put_line pg_end_copy pg_copy_to pg_copy_from pg_trace pg_untrace pg_lo_create pg_lo_unlink pg_lo_open pg_lo_close pg_lo_read pg_lo_write pg_lo_read_all pg_lo_import pg_lo_export pg_lo_seek pg_lo_tell pg_escape_string pg_escape_bytea pg_unescape_bytea pg_client_encoding pg_set_client_encoding pg_meta_data pg_convert pg_insert pg_update pg_delete pg_select pg_exec pg_getlastoid pg_cmdtuples pg_errormessage pg_numrows pg_numfields pg_fieldname pg_fieldsize pg_fieldtype pg_fieldnum pg_fieldprtlen pg_fieldisnull pg_freeresult pg_result pg_loreadall pg_locreate pg_lounlink pg_loopen pg_loclose pg_loread pg_lowrite pg_loimport pg_loexport http_response_code get_declared_traits getimagesizefromstring socket_import_stream stream_set_chunk_size trait_exists header_register_callback class_uses session_status session_register_shutdown echo print global static exit array empty eval isset unset die include require include_once require_once json_decode json_encode json_last_error json_last_error_msg curl_close curl_copy_handle curl_errno curl_error curl_escape curl_exec curl_file_create curl_getinfo curl_init curl_multi_add_handle curl_multi_close curl_multi_exec curl_multi_getcontent curl_multi_info_read curl_multi_init curl_multi_remove_handle curl_multi_select curl_multi_setopt curl_multi_strerror curl_pause curl_reset curl_setopt_array curl_setopt curl_share_close curl_share_init curl_share_setopt curl_strerror curl_unescape curl_version mysqli_affected_rows mysqli_autocommit mysqli_change_user mysqli_character_set_name mysqli_close mysqli_commit mysqli_connect_errno mysqli_connect_error mysqli_connect mysqli_data_seek mysqli_debug mysqli_dump_debug_info mysqli_errno mysqli_error_list mysqli_error mysqli_fetch_all mysqli_fetch_array mysqli_fetch_assoc mysqli_fetch_field_direct mysqli_fetch_field mysqli_fetch_fields mysqli_fetch_lengths mysqli_fetch_object mysqli_fetch_row mysqli_field_count mysqli_field_seek mysqli_field_tell mysqli_free_result mysqli_get_charset mysqli_get_client_info mysqli_get_client_stats mysqli_get_client_version mysqli_get_connection_stats mysqli_get_host_info mysqli_get_proto_info mysqli_get_server_info mysqli_get_server_version mysqli_info mysqli_init mysqli_insert_id mysqli_kill mysqli_more_results mysqli_multi_query mysqli_next_result mysqli_num_fields mysqli_num_rows mysqli_options mysqli_ping mysqli_prepare mysqli_query mysqli_real_connect mysqli_real_escape_string mysqli_real_query mysqli_reap_async_query mysqli_refresh mysqli_rollback mysqli_select_db mysqli_set_charset mysqli_set_local_infile_default mysqli_set_local_infile_handler mysqli_sqlstate mysqli_ssl_set mysqli_stat mysqli_stmt_init mysqli_store_result mysqli_thread_id mysqli_thread_safe mysqli_use_result mysqli_warning_count";
    CodeMirror.registerHelper("hintWords", "php", [phpKeywords, phpAtoms, phpBuiltin].join(" ").split(" "));
    CodeMirror.registerHelper("wordChars", "php", /[\w$]/);

    var phpConfig = {
        name: "clike",
        helperType: "php",
        keywords: keywords(phpKeywords),
        blockKeywords: keywords("catch do else elseif for foreach if switch try while finally"),
        defKeywords: keywords("class enum function interface namespace trait"),
        atoms: keywords(phpAtoms),
        builtin: keywords(phpBuiltin),
        multiLineStrings: true,
        hooks: {
            "$": function (stream) {
                stream.eatWhile(/[\w\$_]/);
                return "variable-2";
            },
            "<": function (stream, state) {
                var before;
                if (before = stream.match(/^<<\s*/)) {
                    var quoted = stream.eat(/['"]/);
                    stream.eatWhile(/[\w\.]/);
                    var delim = stream.current().slice(before[0].length+(quoted ? 2 : 1));
                    if (quoted) stream.eat(quoted);
                    if (delim) {
                        (state.tokStack || (state.tokStack = [])).push(delim, 0);
                        state.tokenize = phpString(delim, quoted != "'");
                        return "string";
                    }
                }
                return false;
            },
            "#": function (stream) {
                while (!stream.eol() && !stream.match("?>", false)) stream.next();
                return "comment";
            },
            "/": function (stream) {
                if (stream.eat("/")) {
                    while (!stream.eol() && !stream.match("?>", false)) stream.next();
                    return "comment";
                }
                return false;
            },
            '"': function (_stream, state) {
                (state.tokStack || (state.tokStack = [])).push('"', 0);
                state.tokenize = phpString('"');
                return "string";
            },
            "{": function (_stream, state) {
                if (state.tokStack && state.tokStack.length)
                    state.tokStack[state.tokStack.length-1]++;
                return false;
            },
            "}": function (_stream, state) {
                if (state.tokStack && state.tokStack.length > 0 &&
                    !--state.tokStack[state.tokStack.length-1]) {
                    state.tokenize = phpString(state.tokStack[state.tokStack.length-2]);
                }
                return false;
            }
        }
    };

    CodeMirror.defineMode("php", function (config, parserConfig) {
        var htmlMode = CodeMirror.getMode(config, (parserConfig && parserConfig.htmlMode) || "text/html");
        var phpMode = CodeMirror.getMode(config, phpConfig);

        function dispatch(stream, state) {
            var isPHP = state.curMode == phpMode;
            if (stream.sol() && state.pending && state.pending != '"' && state.pending != "'") state.pending = null;
            if (!isPHP) {
                if (stream.match(/^<\?\w*/)) {
                    state.curMode = phpMode;
                    if (!state.php) state.php = CodeMirror.startState(phpMode, htmlMode.indent(state.html, "", ""))
                    state.curState = state.php;
                    return "meta";
                }
                if (state.pending == '"' || state.pending == "'") {
                    while (!stream.eol() && stream.next() != state.pending) {
                    }
                    var style = "string";
                } else if (state.pending && stream.pos < state.pending.end) {
                    stream.pos = state.pending.end;
                    var style = state.pending.style;
                } else {
                    var style = htmlMode.token(stream, state.curState);
                }
                if (state.pending) state.pending = null;
                var cur = stream.current(), openPHP = cur.search(/<\?/), m;
                if (openPHP != -1) {
                    if (style == "string" && (m = cur.match(/[\'\"]$/)) && !/\?>/.test(cur)) state.pending = m[0];
                    else state.pending = {end: stream.pos, style: style};
                    stream.backUp(cur.length-openPHP);
                }
                return style;
            } else if (isPHP && state.php.tokenize == null && stream.match("?>")) {
                state.curMode = htmlMode;
                state.curState = state.html;
                if (!state.php.context.prev) state.php = null;
                return "meta";
            } else {
                return phpMode.token(stream, state.curState);
            }
        }

        return {
            startState: function () {
                var html = CodeMirror.startState(htmlMode)
                var php = parserConfig.startOpen ? CodeMirror.startState(phpMode) : null
                return {
                    html: html,
                    php: php,
                    curMode: parserConfig.startOpen ? phpMode : htmlMode,
                    curState: parserConfig.startOpen ? php : html,
                    pending: null
                };
            },

            copyState: function (state) {
                var html = state.html, htmlNew = CodeMirror.copyState(htmlMode, html),
                    php = state.php, phpNew = php && CodeMirror.copyState(phpMode, php), cur;
                if (state.curMode == htmlMode) cur = htmlNew;
                else cur = phpNew;
                return {
                    html: htmlNew, php: phpNew, curMode: state.curMode, curState: cur,
                    pending: state.pending
                };
            },

            token: dispatch,

            indent: function (state, textAfter, line) {
                if ((state.curMode != phpMode && /^\s*<\//.test(textAfter)) ||
                    (state.curMode == phpMode && /^\?>/.test(textAfter)))
                    return htmlMode.indent(state.html, textAfter, line);
                return state.curMode.indent(state.curState, textAfter, line);
            },

            blockCommentStart: "/*",
            blockCommentEnd: "*/",
            lineComment: "//",

            innerMode: function (state) {
                return {state: state.curState, mode: state.curMode};
            }
        };
    }, "htmlmixed", "clike");

    CodeMirror.defineMIME("application/x-httpd-php", "php");
    CodeMirror.defineMIME("application/x-httpd-php-open", {name: "php", startOpen: true});
    CodeMirror.defineMIME("text/x-php", phpConfig);
});

// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: https://codemirror.net/LICENSE

(function (mod) {
    if (typeof exports == "object" && typeof module == "object") // CommonJS
        mod(require("../../lib/codemirror"));
    else if (typeof define == "function" && define.amd) // AMD
        define(["../../lib/codemirror"], mod);
    else // Plain browser env
        mod(CodeMirror);
})(function (CodeMirror) {
    "use strict";

    var htmlConfig = {
        autoSelfClosers: {
            'area': true, 'base': true, 'br': true, 'col': true, 'command': true,
            'embed': true, 'frame': true, 'hr': true, 'img': true, 'input': true,
            'keygen': true, 'link': true, 'meta': true, 'param': true, 'source': true,
            'track': true, 'wbr': true, 'menuitem': true
        },
        implicitlyClosed: {
            'dd': true, 'li': true, 'optgroup': true, 'option': true, 'p': true,
            'rp': true, 'rt': true, 'tbody': true, 'td': true, 'tfoot': true,
            'th': true, 'tr': true
        },
        contextGrabbers: {
            'dd': {'dd': true, 'dt': true},
            'dt': {'dd': true, 'dt': true},
            'li': {'li': true},
            'option': {'option': true, 'optgroup': true},
            'optgroup': {'optgroup': true},
            'p': {
                'address': true, 'article': true, 'aside': true, 'blockquote': true, 'dir': true,
                'div': true, 'dl': true, 'fieldset': true, 'footer': true, 'form': true,
                'h1': true, 'h2': true, 'h3': true, 'h4': true, 'h5': true, 'h6': true,
                'header': true, 'hgroup': true, 'hr': true, 'menu': true, 'nav': true, 'ol': true,
                'p': true, 'pre': true, 'section': true, 'table': true, 'ul': true
            },
            'rp': {'rp': true, 'rt': true},
            'rt': {'rp': true, 'rt': true},
            'tbody': {'tbody': true, 'tfoot': true},
            'td': {'td': true, 'th': true},
            'tfoot': {'tbody': true},
            'th': {'td': true, 'th': true},
            'thead': {'tbody': true, 'tfoot': true},
            'tr': {'tr': true}
        },
        doNotIndent: {"pre": true},
        allowUnquoted: true,
        allowMissing: true,
        caseFold: true
    }

    var xmlConfig = {
        autoSelfClosers: {},
        implicitlyClosed: {},
        contextGrabbers: {},
        doNotIndent: {},
        allowUnquoted: false,
        allowMissing: false,
        allowMissingTagName: false,
        caseFold: false
    }

    CodeMirror.defineMode("xml", function (editorConf, config_) {
        var indentUnit = editorConf.indentUnit
        var config = {}
        var defaults = config_.htmlMode ? htmlConfig : xmlConfig
        for (var prop in defaults) config[prop] = defaults[prop]
        for (var prop in config_) config[prop] = config_[prop]

        // Return variables for tokenizers
        var type, setStyle;

        function inText(stream, state) {
            function chain(parser) {
                state.tokenize = parser;
                return parser(stream, state);
            }

            var ch = stream.next();
            if (ch == "<") {
                if (stream.eat("!")) {
                    if (stream.eat("[")) {
                        if (stream.match("CDATA[")) return chain(inBlock("atom", "]]>"));
                        else return null;
                    } else if (stream.match("--")) {
                        return chain(inBlock("comment", "-->"));
                    } else if (stream.match("DOCTYPE", true, true)) {
                        stream.eatWhile(/[\w\._\-]/);
                        return chain(doctype(1));
                    } else {
                        return null;
                    }
                } else if (stream.eat("?")) {
                    stream.eatWhile(/[\w\._\-]/);
                    state.tokenize = inBlock("meta", "?>");
                    return "meta";
                } else {
                    type = stream.eat("/") ? "closeTag" : "openTag";
                    state.tokenize = inTag;
                    return "tag bracket";
                }
            } else if (ch == "&") {
                var ok;
                if (stream.eat("#")) {
                    if (stream.eat("x")) {
                        ok = stream.eatWhile(/[a-fA-F\d]/) && stream.eat(";");
                    } else {
                        ok = stream.eatWhile(/[\d]/) && stream.eat(";");
                    }
                } else {
                    ok = stream.eatWhile(/[\w\.\-:]/) && stream.eat(";");
                }
                return ok ? "atom" : "error";
            } else {
                stream.eatWhile(/[^&<]/);
                return null;
            }
        }

        inText.isInText = true;

        function inTag(stream, state) {
            var ch = stream.next();
            if (ch == ">" || (ch == "/" && stream.eat(">"))) {
                state.tokenize = inText;
                type = ch == ">" ? "endTag" : "selfcloseTag";
                return "tag bracket";
            } else if (ch == "=") {
                type = "equals";
                return null;
            } else if (ch == "<") {
                state.tokenize = inText;
                state.state = baseState;
                state.tagName = state.tagStart = null;
                var next = state.tokenize(stream, state);
                return next ? next+" tag error" : "tag error";
            } else if (/[\'\"]/.test(ch)) {
                state.tokenize = inAttribute(ch);
                state.stringStartCol = stream.column();
                return state.tokenize(stream, state);
            } else {
                stream.match(/^[^\s\u00a0=<>\"\']*[^\s\u00a0=<>\"\'\/]/);
                return "word";
            }
        }

        function inAttribute(quote) {
            var closure = function (stream, state) {
                while (!stream.eol()) {
                    if (stream.next() == quote) {
                        state.tokenize = inTag;
                        break;
                    }
                }
                return "string";
            };
            closure.isInAttribute = true;
            return closure;
        }

        function inBlock(style, terminator) {
            return function (stream, state) {
                while (!stream.eol()) {
                    if (stream.match(terminator)) {
                        state.tokenize = inText;
                        break;
                    }
                    stream.next();
                }
                return style;
            }
        }

        function doctype(depth) {
            return function (stream, state) {
                var ch;
                while ((ch = stream.next()) != null) {
                    if (ch == "<") {
                        state.tokenize = doctype(depth+1);
                        return state.tokenize(stream, state);
                    } else if (ch == ">") {
                        if (depth == 1) {
                            state.tokenize = inText;
                            break;
                        } else {
                            state.tokenize = doctype(depth-1);
                            return state.tokenize(stream, state);
                        }
                    }
                }
                return "meta";
            };
        }

        function lower(tagName) {
            return tagName && tagName.toLowerCase();
        }

        function Context(state, tagName, startOfLine) {
            this.prev = state.context;
            this.tagName = tagName || "";
            this.indent = state.indented;
            this.startOfLine = startOfLine;
            if (config.doNotIndent.hasOwnProperty(tagName) || (state.context && state.context.noIndent))
                this.noIndent = true;
        }

        function popContext(state) {
            if (state.context) state.context = state.context.prev;
        }

        function maybePopContext(state, nextTagName) {
            var parentTagName;
            while (true) {
                if (!state.context) {
                    return;
                }
                parentTagName = state.context.tagName;
                if (!config.contextGrabbers.hasOwnProperty(lower(parentTagName)) ||
                    !config.contextGrabbers[lower(parentTagName)].hasOwnProperty(lower(nextTagName))) {
                    return;
                }
                popContext(state);
            }
        }

        function baseState(type, stream, state) {
            if (type == "openTag") {
                state.tagStart = stream.column();
                return tagNameState;
            } else if (type == "closeTag") {
                return closeTagNameState;
            } else {
                return baseState;
            }
        }

        function tagNameState(type, stream, state) {
            if (type == "word") {
                state.tagName = stream.current();
                setStyle = "tag";
                return attrState;
            } else if (config.allowMissingTagName && type == "endTag") {
                setStyle = "tag bracket";
                return attrState(type, stream, state);
            } else {
                setStyle = "error";
                return tagNameState;
            }
        }

        function closeTagNameState(type, stream, state) {
            if (type == "word") {
                var tagName = stream.current();
                if (state.context && state.context.tagName != tagName &&
                    config.implicitlyClosed.hasOwnProperty(lower(state.context.tagName)))
                    popContext(state);
                if ((state.context && state.context.tagName == tagName) || config.matchClosing === false) {
                    setStyle = "tag";
                    return closeState;
                } else {
                    setStyle = "tag error";
                    return closeStateErr;
                }
            } else if (config.allowMissingTagName && type == "endTag") {
                setStyle = "tag bracket";
                return closeState(type, stream, state);
            } else {
                setStyle = "error";
                return closeStateErr;
            }
        }

        function closeState(type, _stream, state) {
            if (type != "endTag") {
                setStyle = "error";
                return closeState;
            }
            popContext(state);
            return baseState;
        }

        function closeStateErr(type, stream, state) {
            setStyle = "error";
            return closeState(type, stream, state);
        }

        function attrState(type, _stream, state) {
            if (type == "word") {
                setStyle = "attribute";
                return attrEqState;
            } else if (type == "endTag" || type == "selfcloseTag") {
                var tagName = state.tagName, tagStart = state.tagStart;
                state.tagName = state.tagStart = null;
                if (type == "selfcloseTag" ||
                    config.autoSelfClosers.hasOwnProperty(lower(tagName))) {
                    maybePopContext(state, tagName);
                } else {
                    maybePopContext(state, tagName);
                    state.context = new Context(state, tagName, tagStart == state.indented);
                }
                return baseState;
            }
            setStyle = "error";
            return attrState;
        }

        function attrEqState(type, stream, state) {
            if (type == "equals") return attrValueState;
            if (!config.allowMissing) setStyle = "error";
            return attrState(type, stream, state);
        }

        function attrValueState(type, stream, state) {
            if (type == "string") return attrContinuedState;
            if (type == "word" && config.allowUnquoted) {
                setStyle = "string";
                return attrState;
            }
            setStyle = "error";
            return attrState(type, stream, state);
        }

        function attrContinuedState(type, stream, state) {
            if (type == "string") return attrContinuedState;
            return attrState(type, stream, state);
        }

        return {
            startState: function (baseIndent) {
                var state = {
                    tokenize: inText,
                    state: baseState,
                    indented: baseIndent || 0,
                    tagName: null, tagStart: null,
                    context: null
                }
                if (baseIndent != null) state.baseIndent = baseIndent
                return state
            },

            token: function (stream, state) {
                if (!state.tagName && stream.sol())
                    state.indented = stream.indentation();

                if (stream.eatSpace()) return null;
                type = null;
                var style = state.tokenize(stream, state);
                if ((style || type) && style != "comment") {
                    setStyle = null;
                    state.state = state.state(type || style, stream, state);
                    if (setStyle)
                        style = setStyle == "error" ? style+" error" : setStyle;
                }
                return style;
            },

            indent: function (state, textAfter, fullLine) {
                var context = state.context;
                // Indent multi-line strings (e.g. css).
                if (state.tokenize.isInAttribute) {
                    if (state.tagStart == state.indented)
                        return state.stringStartCol+1;
                    else
                        return state.indented+indentUnit;
                }
                if (context && context.noIndent) return CodeMirror.Pass;
                if (state.tokenize != inTag && state.tokenize != inText)
                    return fullLine ? fullLine.match(/^(\s*)/)[0].length : 0;
                // Indent the starts of attribute names.
                if (state.tagName) {
                    if (config.multilineTagIndentPastTag !== false)
                        return state.tagStart+state.tagName.length+2;
                    else
                        return state.tagStart+indentUnit * (config.multilineTagIndentFactor || 1);
                }
                if (config.alignCDATA && /<!\[CDATA\[/.test(textAfter)) return 0;
                var tagAfter = textAfter && /^<(\/)?([\w_:\.-]*)/.exec(textAfter);
                if (tagAfter && tagAfter[1]) { // Closing tag spotted
                    while (context) {
                        if (context.tagName == tagAfter[2]) {
                            context = context.prev;
                            break;
                        } else if (config.implicitlyClosed.hasOwnProperty(lower(context.tagName))) {
                            context = context.prev;
                        } else {
                            break;
                        }
                    }
                } else if (tagAfter) { // Opening tag spotted
                    while (context) {
                        var grabbers = config.contextGrabbers[lower(context.tagName)];
                        if (grabbers && grabbers.hasOwnProperty(lower(tagAfter[2])))
                            context = context.prev;
                        else
                            break;
                    }
                }
                while (context && context.prev && !context.startOfLine)
                    context = context.prev;
                if (context) return context.indent+indentUnit;
                else return state.baseIndent || 0;
            },

            electricInput: /<\/[\s\w:]+>$/,
            blockCommentStart: "<!--",
            blockCommentEnd: "-->",

            configuration: config.htmlMode ? "html" : "xml",
            helperType: config.htmlMode ? "html" : "xml",

            skipAttribute: function (state) {
                if (state.state == attrValueState)
                    state.state = attrState
            },

            xmlCurrentTag: function (state) {
                return state.tagName ? {name: state.tagName, close: state.type == "closeTag"} : null
            },

            xmlCurrentContext: function (state) {
                var context = []
                for (var cx = state.context; cx; cx = cx.prev)
                    context.push(cx.tagName)
                return context.reverse()
            }
        };
    });

    CodeMirror.defineMIME("text/xml", "xml");
    CodeMirror.defineMIME("application/xml", "xml");
    if (!CodeMirror.mimeModes.hasOwnProperty("text/html"))
        CodeMirror.defineMIME("text/html", {name: "xml", htmlMode: true});

});

/*! Summernote v0.8.20 | (c) 2013- Alan Hong and contributors | MIT license */
!function (t, e) {
    if ("object" == typeof exports && "object" == typeof module) module.exports = e(require("jQuery")); else if ("function" == typeof define && define.amd) define(["jQuery"], e); else {
        var n = "object" == typeof exports ? e(require("jQuery")) : e(t.jQuery);
        for (var o in n) ("object" == typeof exports ? exports : t)[o] = n[o]
    }
}(self, (function (t) {
    return (() => {
        "use strict";
        var e = {
            9770: (t, e, n) => {
                var o = n(1145), i = n.n(o);
                i().summernote = i().summernote || {lang: {}}, i().extend(i().summernote.lang, {
                    "en-US": {
                        font: {
                            bold: "Bold",
                            italic: "Italic",
                            underline: "Underline",
                            clear: "Remove Font Style",
                            height: "Line Height",
                            name: "Font Family",
                            strikethrough: "Strikethrough",
                            subscript: "Subscript",
                            superscript: "Superscript",
                            size: "Font Size",
                            sizeunit: "Font Size Unit"
                        },
                        image: {
                            image: "Picture",
                            insert: "Insert Image",
                            resizeFull: "Resize full",
                            resizeHalf: "Resize half",
                            resizeQuarter: "Resize quarter",
                            resizeNone: "Original size",
                            floatLeft: "Float Left",
                            floatRight: "Float Right",
                            floatNone: "Remove float",
                            shapeRounded: "Shape: Rounded",
                            shapeCircle: "Shape: Circle",
                            shapeThumbnail: "Shape: Thumbnail",
                            shapeNone: "Shape: None",
                            dragImageHere: "Drag image or text here",
                            dropImage: "Drop image or Text",
                            selectFromFiles: "Select from files",
                            maximumFileSize: "Maximum file size",
                            maximumFileSizeError: "Maximum file size exceeded.",
                            url: "Image URL",
                            remove: "Remove Image",
                            original: "Original"
                        },
                        video: {
                            video: "Video",
                            videoLink: "Video Link",
                            insert: "Insert Video",
                            url: "Video URL",
                            providers: "(YouTube, Google Drive, Vimeo, Vine, Instagram, DailyMotion, Youku, Peertube)"
                        },
                        link: {
                            link: "Link",
                            insert: "Insert Link",
                            unlink: "Unlink",
                            edit: "Edit",
                            textToDisplay: "Text to display",
                            url: "To what URL should this link go?",
                            openInNewWindow: "Open in new window",
                            useProtocol: "Use default protocol"
                        },
                        table: {
                            table: "Table",
                            addRowAbove: "Add row above",
                            addRowBelow: "Add row below",
                            addColLeft: "Add column left",
                            addColRight: "Add column right",
                            delRow: "Delete row",
                            delCol: "Delete column",
                            delTable: "Delete table"
                        },
                        hr: {insert: "Insert Horizontal Rule"},
                        style: {
                            style: "Style",
                            p: "Normal",
                            blockquote: "Quote",
                            pre: "Code",
                            h1: "Header 1",
                            h2: "Header 2",
                            h3: "Header 3",
                            h4: "Header 4",
                            h5: "Header 5",
                            h6: "Header 6"
                        },
                        lists: {unordered: "Unordered list", ordered: "Ordered list"},
                        options: {help: "Help", fullscreen: "Full Screen", codeview: "Code View"},
                        paragraph: {
                            paragraph: "Paragraph",
                            outdent: "Outdent",
                            indent: "Indent",
                            left: "Align left",
                            center: "Align center",
                            right: "Align right",
                            justify: "Justify full"
                        },
                        color: {
                            recent: "Recent Color",
                            more: "More Color",
                            background: "Background Color",
                            foreground: "Text Color",
                            transparent: "Transparent",
                            setTransparent: "Set transparent",
                            reset: "Reset",
                            resetToDefault: "Reset to default",
                            cpSelect: "Select"
                        },
                        shortcut: {
                            shortcuts: "Keyboard shortcuts",
                            close: "Close",
                            textFormatting: "Text formatting",
                            action: "Action",
                            paragraphFormatting: "Paragraph formatting",
                            documentStyle: "Document Style",
                            extraKeys: "Extra keys"
                        },
                        help: {
                            escape: "Escape",
                            insertParagraph: "Insert Paragraph",
                            undo: "Undo the last command",
                            redo: "Redo the last command",
                            tab: "Tab",
                            untab: "Untab",
                            bold: "Set a bold style",
                            italic: "Set a italic style",
                            underline: "Set a underline style",
                            strikethrough: "Set a strikethrough style",
                            removeFormat: "Clean a style",
                            justifyLeft: "Set left align",
                            justifyCenter: "Set center align",
                            justifyRight: "Set right align",
                            justifyFull: "Set full align",
                            insertUnorderedList: "Toggle unordered list",
                            insertOrderedList: "Toggle ordered list",
                            outdent: "Outdent on current paragraph",
                            indent: "Indent on current paragraph",
                            formatPara: "Change current block's format as a paragraph(P tag)",
                            formatH1: "Change current block's format as H1",
                            formatH2: "Change current block's format as H2",
                            formatH3: "Change current block's format as H3",
                            formatH4: "Change current block's format as H4",
                            formatH5: "Change current block's format as H5",
                            formatH6: "Change current block's format as H6",
                            insertHorizontalRule: "Insert horizontal rule",
                            "linkDialog.show": "Show Link Dialog"
                        },
                        history: {undo: "Undo", redo: "Redo"},
                        specialChar: {specialChar: "SPECIAL CHARACTERS", select: "Select Special characters"},
                        output: {noSelection: "No Selection Made!"}
                    }
                })
            }, 1145: e => {
                e.exports = t
            }
        }, n = {};

        function o(t) {
            var i = n[t];
            if (void 0 !== i) return i.exports;
            var r = n[t] = {exports: {}};
            return e[t](r, r.exports, o), r.exports
        }

        o.n = t => {
            var e = t && t.__esModule ? () => t.default : () => t;
            return o.d(e, {a: e}), e
        }, o.d = (t, e) => {
            for (var n in e) o.o(e, n) && !o.o(t, n) && Object.defineProperty(t, n, {enumerable: !0, get: e[n]})
        }, o.o = (t, e) => Object.prototype.hasOwnProperty.call(t, e), o.r = t => {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(t, Symbol.toStringTag, {value: "Module"}), Object.defineProperty(t, "__esModule", {value: !0})
        };
        var i = {};
        return (() => {
            o.r(i);
            var t = o(1145), e = o.n(t), n = (o(9770), ["sans-serif", "serif", "monospace", "cursive", "fantasy"]);

            function r(t) {
                return -1 === e().inArray(t.toLowerCase(), n) ? "'".concat(t, "'") : t
            }

            var a, s = navigator.userAgent, l = /MSIE|Trident/i.test(s);
            if (l) {
                var c = /MSIE (\d+[.]\d+)/.exec(s);
                c && (a = parseFloat(c[1])), (c = /Trident\/.*rv:([0-9]{1,}[.0-9]{0,})/.exec(s)) && (a = parseFloat(c[1]))
            }
            var u = /Edge\/\d+/.test(s),
                d = "ontouchstart" in window || navigator.MaxTouchPoints > 0 || navigator.msMaxTouchPoints > 0,
                h = l ? "DOMCharacterDataModified DOMSubtreeModified DOMNodeInserted" : "input";
            const f = {
                isMac: navigator.appVersion.indexOf("Mac") > -1,
                isMSIE: l,
                isEdge: u,
                isFF: !u && /firefox/i.test(s),
                isPhantom: /PhantomJS/i.test(s),
                isWebkit: !u && /webkit/i.test(s),
                isChrome: !u && /chrome/i.test(s),
                isSafari: !u && /safari/i.test(s) && !/chrome/i.test(s),
                browserVersion: a,
                isSupportTouch: d,
                isFontInstalled: function (t) {
                    var e = "Comic Sans MS" === t ? "Courier New" : "Comic Sans MS", n = "mmmmmmmmmmwwwww",
                        o = document.createElement("canvas").getContext("2d");
                    o.font = "200px '"+e+"'";
                    var i = o.measureText(n).width;
                    return o.font = "200px "+r(t)+', "'+e+'"', i !== o.measureText(n).width
                },
                isW3CRangeSupport: !!document.createRange,
                inputEventName: h,
                genericFontFamilies: n,
                validFontName: r
            };
            var p = 0;
            const m = {
                eq: function (t) {
                    return function (e) {
                        return t === e
                    }
                }, eq2: function (t, e) {
                    return t === e
                }, peq2: function (t) {
                    return function (e, n) {
                        return e[t] === n[t]
                    }
                }, ok: function () {
                    return !0
                }, fail: function () {
                    return !1
                }, self: function (t) {
                    return t
                }, not: function (t) {
                    return function () {
                        return !t.apply(t, arguments)
                    }
                }, and: function (t, e) {
                    return function (n) {
                        return t(n) && e(n)
                    }
                }, invoke: function (t, e) {
                    return function () {
                        return t[e].apply(t, arguments)
                    }
                }, resetUniqueId: function () {
                    p = 0
                }, uniqueId: function (t) {
                    var e = ++p+"";
                    return t ? t+e : e
                }, rect2bnd: function (t) {
                    var n = e()(document);
                    return {
                        top: t.top+n.scrollTop(),
                        left: t.left+n.scrollLeft(),
                        width: t.right-t.left,
                        height: t.bottom-t.top
                    }
                }, invertObject: function (t) {
                    var e = {};
                    for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[t[n]] = n);
                    return e
                }, namespaceToCamel: function (t, e) {
                    return (e = e || "")+t.split(".").map((function (t) {
                        return t.substring(0, 1).toUpperCase()+t.substring(1)
                    })).join("")
                }, debounce: function (t, e, n) {
                    var o;
                    return function () {
                        var i = this, r = arguments, a = function () {
                            o = null, n || t.apply(i, r)
                        }, s = n && !o;
                        clearTimeout(o), o = setTimeout(a, e), s && t.apply(i, r)
                    }
                }, isValidUrl: function (t) {
                    return /[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/gi.test(t)
                }
            };

            function v(t) {
                return t[0]
            }

            function g(t) {
                return t[t.length-1]
            }

            function b(t) {
                return t.slice(1)
            }

            function y(t, e) {
                if (t && t.length && e) {
                    if (t.indexOf) return -1 !== t.indexOf(e);
                    if (t.contains) return t.contains(e)
                }
                return !1
            }

            const k = {
                head: v, last: g, initial: function (t) {
                    return t.slice(0, t.length-1)
                }, tail: b, prev: function (t, e) {
                    if (t && t.length && e) {
                        var n = t.indexOf(e);
                        return -1 === n ? null : t[n-1]
                    }
                    return null
                }, next: function (t, e) {
                    if (t && t.length && e) {
                        var n = t.indexOf(e);
                        return -1 === n ? null : t[n+1]
                    }
                    return null
                }, find: function (t, e) {
                    for (var n = 0, o = t.length; n < o; n++) {
                        var i = t[n];
                        if (e(i)) return i
                    }
                }, contains: y, all: function (t, e) {
                    for (var n = 0, o = t.length; n < o; n++) if (!e(t[n])) return !1;
                    return !0
                }, sum: function (t, e) {
                    return e = e || m.self, t.reduce((function (t, n) {
                        return t+e(n)
                    }), 0)
                }, from: function (t) {
                    for (var e = [], n = t.length, o = -1; ++o < n;) e[o] = t[o];
                    return e
                }, isEmpty: function (t) {
                    return !t || !t.length
                }, clusterBy: function (t, e) {
                    return t.length ? b(t).reduce((function (t, n) {
                        var o = g(t);
                        return e(g(o), n) ? o[o.length] = n : t[t.length] = [n], t
                    }), [[v(t)]]) : []
                }, compact: function (t) {
                    for (var e = [], n = 0, o = t.length; n < o; n++) t[n] && e.push(t[n]);
                    return e
                }, unique: function (t) {
                    for (var e = [], n = 0, o = t.length; n < o; n++) y(e, t[n]) || e.push(t[n]);
                    return e
                }
            };
            var w = String.fromCharCode(160);

            function C(t) {
                return t && e()(t).hasClass("note-editable")
            }

            function x(t) {
                return t = t.toUpperCase(), function (e) {
                    return e && e.nodeName.toUpperCase() === t
                }
            }

            function S(t) {
                return t && 3 === t.nodeType
            }

            function T(t) {
                return t && /^BR|^IMG|^HR|^IFRAME|^BUTTON|^INPUT|^AUDIO|^VIDEO|^EMBED/.test(t.nodeName.toUpperCase())
            }

            function E(t) {
                return !C(t) && (t && /^DIV|^P|^LI|^H[1-7]/.test(t.nodeName.toUpperCase()))
            }

            var I = x("PRE"), $ = x("LI");
            var N = x("TABLE"), P = x("DATA");

            function R(t) {
                return !(H(t) || L(t) || A(t) || E(t) || N(t) || D(t) || P(t))
            }

            function L(t) {
                return t && /^UL|^OL/.test(t.nodeName.toUpperCase())
            }

            var A = x("HR");

            function F(t) {
                return t && /^TD|^TH/.test(t.nodeName.toUpperCase())
            }

            var D = x("BLOCKQUOTE");

            function H(t) {
                return F(t) || D(t) || C(t)
            }

            var z = x("A");
            var B = x("BODY");
            var M = f.isMSIE && f.browserVersion < 11 ? "&nbsp;" : "<br>";

            function O(t) {
                return S(t) ? t.nodeValue.length : t ? t.childNodes.length : 0
            }

            function U(t) {
                var e = O(t);
                return 0 === e || (!S(t) && 1 === e && t.innerHTML === M || !(!k.all(t.childNodes, S) || "" !== t.innerHTML))
            }

            function j(t) {
                T(t) || O(t) || (t.innerHTML = M)
            }

            function W(t, e) {
                for (; t;) {
                    if (e(t)) return t;
                    if (C(t)) break;
                    t = t.parentNode
                }
                return null
            }

            function K(t, e) {
                e = e || m.fail;
                var n = [];
                return W(t, (function (t) {
                    return C(t) || n.push(t), e(t)
                })), n
            }

            function V(t, e) {
                e = e || m.fail;
                for (var n = []; t && !e(t);) n.push(t), t = t.nextSibling;
                return n
            }

            function q(t, e) {
                var n = e.nextSibling, o = e.parentNode;
                return n ? o.insertBefore(t, n) : o.appendChild(t), t
            }

            function _(t, n) {
                return e().each(n, (function (e, n) {
                    t.appendChild(n)
                })), t
            }

            function G(t) {
                return 0 === t.offset
            }

            function Y(t) {
                return t.offset === O(t.node)
            }

            function Z(t) {
                return G(t) || Y(t)
            }

            function Q(t, e) {
                for (; t && t !== e;) {
                    if (0 !== J(t)) return !1;
                    t = t.parentNode
                }
                return !0
            }

            function X(t, e) {
                if (!e) return !1;
                for (; t && t !== e;) {
                    if (J(t) !== O(t.parentNode)-1) return !1;
                    t = t.parentNode
                }
                return !0
            }

            function J(t) {
                for (var e = 0; t = t.previousSibling;) e += 1;
                return e
            }

            function tt(t) {
                return !!(t && t.childNodes && t.childNodes.length)
            }

            function et(t, e) {
                var n, o;
                if (0 === t.offset) {
                    if (C(t.node)) return null;
                    n = t.node.parentNode, o = J(t.node)
                } else tt(t.node) ? o = O(n = t.node.childNodes[t.offset-1]) : (n = t.node, o = e ? 0 : t.offset-1);
                return {node: n, offset: o}
            }

            function nt(t, e) {
                var n, o;
                if (O(t.node) === t.offset) {
                    if (C(t.node)) return null;
                    var i = it(t.node);
                    i ? (n = i, o = 0) : (n = t.node.parentNode, o = J(t.node)+1)
                } else tt(t.node) ? (n = t.node.childNodes[t.offset], o = 0) : (n = t.node, o = e ? O(t.node) : t.offset+1);
                return {node: n, offset: o}
            }

            function ot(t, e) {
                var n, o = 0;
                if (U(t.node)) return null === t.node ? null : {node: n = t.node.nextSibling, offset: o = 0};
                if (O(t.node) === t.offset) {
                    if (C(t.node)) return null;
                    n = t.node.parentNode, o = J(t.node)+1, C(n) && (n = t.node.nextSibling, o = 0)
                } else if (tt(t.node)) {
                    if (o = 0, U(n = t.node.childNodes[t.offset])) return U(t.node.nextSibling) ? null : {
                        node: t.node.nextSibling,
                        offset: o
                    }
                } else if (n = t.node, o = e ? O(t.node) : t.offset+1, U(n)) return null;
                return {node: n, offset: o}
            }

            function it(t) {
                if (t.nextSibling && t.parent === t.nextSibling.parent) return S(t.nextSibling) ? t.nextSibling : it(t.nextSibling)
            }

            function rt(t, e) {
                return t.node === e.node && t.offset === e.offset
            }

            function at(t, e) {
                var n = e && e.isSkipPaddingBlankHTML, o = e && e.isNotSplitEdgePoint, i = e && e.isDiscardEmptySplits;
                if (i && (n = !0), Z(t) && (S(t.node) || o)) {
                    if (G(t)) return t.node;
                    if (Y(t)) return t.node.nextSibling
                }
                if (S(t.node)) return t.node.splitText(t.offset);
                var r = t.node.childNodes[t.offset], a = q(t.node.cloneNode(!1), t.node);
                return _(a, V(r)), n || (j(t.node), j(a)), i && (U(t.node) && ct(t.node), U(a)) ? (ct(a), t.node.nextSibling) : a
            }

            function st(t, e, n) {
                var o = K(e.node, m.eq(t));
                return o.length ? 1 === o.length ? at(e, n) : o.reduce((function (t, o) {
                    return t === e.node && (t = at(e, n)), at({node: o, offset: t ? J(t) : O(o)}, n)
                })) : null
            }

            function lt(t) {
                return document.createElement(t)
            }

            function ct(t, e) {
                if (t && t.parentNode) {
                    if (t.removeNode) return t.removeNode(e);
                    var n = t.parentNode;
                    if (!e) {
                        for (var o = [], i = 0, r = t.childNodes.length; i < r; i++) o.push(t.childNodes[i]);
                        for (var a = 0, s = o.length; a < s; a++) n.insertBefore(o[a], t)
                    }
                    n.removeChild(t)
                }
            }

            var ut = x("TEXTAREA");

            function dt(t, e) {
                var n = ut(t[0]) ? t.val() : t.html();
                return e ? n.replace(/[\n\r]/g, "") : n
            }

            const ht = {
                NBSP_CHAR: w,
                ZERO_WIDTH_NBSP_CHAR: "\ufeff",
                blank: M,
                emptyPara: "<p>".concat(M, "</p>"),
                makePredByNodeName: x,
                isEditable: C,
                isControlSizing: function (t) {
                    return t && e()(t).hasClass("note-control-sizing")
                },
                isText: S,
                isElement: function (t) {
                    return t && 1 === t.nodeType
                },
                isVoid: T,
                isPara: E,
                isPurePara: function (t) {
                    return E(t) && !$(t)
                },
                isHeading: function (t) {
                    return t && /^H[1-7]/.test(t.nodeName.toUpperCase())
                },
                isInline: R,
                isBlock: m.not(R),
                isBodyInline: function (t) {
                    return R(t) && !W(t, E)
                },
                isBody: B,
                isParaInline: function (t) {
                    return R(t) && !!W(t, E)
                },
                isPre: I,
                isList: L,
                isTable: N,
                isData: P,
                isCell: F,
                isBlockquote: D,
                isBodyContainer: H,
                isAnchor: z,
                isDiv: x("DIV"),
                isLi: $,
                isBR: x("BR"),
                isSpan: x("SPAN"),
                isB: x("B"),
                isU: x("U"),
                isS: x("S"),
                isI: x("I"),
                isImg: x("IMG"),
                isTextarea: ut,
                deepestChildIsEmpty: function (t) {
                    do {
                        if (null === t.firstElementChild || "" === t.firstElementChild.innerHTML) break
                    } while (t = t.firstElementChild);
                    return U(t)
                },
                isEmpty: U,
                isEmptyAnchor: m.and(z, U),
                isClosestSibling: function (t, e) {
                    return t.nextSibling === e || t.previousSibling === e
                },
                withClosestSiblings: function (t, e) {
                    e = e || m.ok;
                    var n = [];
                    return t.previousSibling && e(t.previousSibling) && n.push(t.previousSibling), n.push(t), t.nextSibling && e(t.nextSibling) && n.push(t.nextSibling), n
                },
                nodeLength: O,
                isLeftEdgePoint: G,
                isRightEdgePoint: Y,
                isEdgePoint: Z,
                isLeftEdgeOf: Q,
                isRightEdgeOf: X,
                isLeftEdgePointOf: function (t, e) {
                    return G(t) && Q(t.node, e)
                },
                isRightEdgePointOf: function (t, e) {
                    return Y(t) && X(t.node, e)
                },
                prevPoint: et,
                nextPoint: nt,
                nextPointWithEmptyNode: ot,
                isSamePoint: rt,
                isVisiblePoint: function (t) {
                    if (S(t.node) || !tt(t.node) || U(t.node)) return !0;
                    var e = t.node.childNodes[t.offset-1], n = t.node.childNodes[t.offset];
                    return !((e && !T(e) || n && !T(n)) && !N(n))
                },
                prevPointUntil: function (t, e) {
                    for (; t;) {
                        if (e(t)) return t;
                        t = et(t)
                    }
                    return null
                },
                nextPointUntil: function (t, e) {
                    for (; t;) {
                        if (e(t)) return t;
                        t = nt(t)
                    }
                    return null
                },
                isCharPoint: function (t) {
                    if (!S(t.node)) return !1;
                    var e = t.node.nodeValue.charAt(t.offset-1);
                    return e && " " !== e && e !== w
                },
                isSpacePoint: function (t) {
                    if (!S(t.node)) return !1;
                    var e = t.node.nodeValue.charAt(t.offset-1);
                    return " " === e || e === w
                },
                walkPoint: function (t, e, n, o) {
                    for (var i = t; i && (n(i), !rt(i, e));) {
                        i = ot(i, o && t.node !== i.node && e.node !== i.node)
                    }
                },
                ancestor: W,
                singleChildAncestor: function (t, e) {
                    for (t = t.parentNode; t && 1 === O(t);) {
                        if (e(t)) return t;
                        if (C(t)) break;
                        t = t.parentNode
                    }
                    return null
                },
                listAncestor: K,
                lastAncestor: function (t, e) {
                    var n = K(t);
                    return k.last(n.filter(e))
                },
                listNext: V,
                listPrev: function (t, e) {
                    e = e || m.fail;
                    for (var n = []; t && !e(t);) n.push(t), t = t.previousSibling;
                    return n
                },
                listDescendant: function (t, e) {
                    var n = [];
                    return e = e || m.ok, function o(i) {
                        t !== i && e(i) && n.push(i);
                        for (var r = 0, a = i.childNodes.length; r < a; r++) o(i.childNodes[r])
                    }(t), n
                },
                commonAncestor: function (t, e) {
                    for (var n = K(t), o = e; o; o = o.parentNode) if (n.indexOf(o) > -1) return o;
                    return null
                },
                wrap: function (t, n) {
                    var o = t.parentNode, i = e()("<"+n+">")[0];
                    return o.insertBefore(i, t), i.appendChild(t), i
                },
                insertAfter: q,
                appendChildNodes: _,
                position: J,
                hasChildren: tt,
                makeOffsetPath: function (t, e) {
                    return K(e, m.eq(t)).map(J).reverse()
                },
                fromOffsetPath: function (t, e) {
                    for (var n = t, o = 0, i = e.length; o < i; o++) n = n.childNodes.length <= e[o] ? n.childNodes[n.childNodes.length-1] : n.childNodes[e[o]];
                    return n
                },
                splitTree: st,
                splitPoint: function (t, e) {
                    var n, o, i = e ? E : H, r = K(t.node, i), a = k.last(r) || t.node;
                    i(a) ? (n = r[r.length-2], o = a) : o = (n = a).parentNode;
                    var s = n && st(n, t, {isSkipPaddingBlankHTML: e, isNotSplitEdgePoint: e});
                    return s || o !== t.node || (s = t.node.childNodes[t.offset]), {rightNode: s, container: o}
                },
                create: lt,
                createText: function (t) {
                    return document.createTextNode(t)
                },
                remove: ct,
                removeWhile: function (t, e) {
                    for (; t && !C(t) && e(t);) {
                        var n = t.parentNode;
                        ct(t), t = n
                    }
                },
                replace: function (t, e) {
                    if (t.nodeName.toUpperCase() === e.toUpperCase()) return t;
                    var n = lt(e);
                    return t.style.cssText && (n.style.cssText = t.style.cssText), _(n, k.from(t.childNodes)), q(n, t), ct(t), n
                },
                html: function (t, e) {
                    var n = dt(t);
                    if (e) {
                        n = (n = n.replace(/<(\/?)(\b(?!!)[^>\s]*)(.*?)(\s*\/?>)/g, (function (t, e, n) {
                            n = n.toUpperCase();
                            var o = /^DIV|^TD|^TH|^P|^LI|^H[1-7]/.test(n) && !!e,
                                i = /^BLOCKQUOTE|^TABLE|^TBODY|^TR|^HR|^UL|^OL/.test(n);
                            return t+(o || i ? "\n" : "")
                        }))).trim()
                    }
                    return n
                },
                value: dt,
                posFromPlaceholder: function (t) {
                    var n = e()(t), o = n.offset(), i = n.outerHeight(!0);
                    return {left: o.left, top: o.top+i}
                },
                attachEvents: function (t, e) {
                    Object.keys(e).forEach((function (n) {
                        t.on(n, e[n])
                    }))
                },
                detachEvents: function (t, e) {
                    Object.keys(e).forEach((function (n) {
                        t.off(n, e[n])
                    }))
                },
                isCustomStyleTag: function (t) {
                    return t && !S(t) && k.contains(t.classList, "note-styletag")
                }
            };

            function ft(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var pt = function () {
                function t(n, o) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.$note = n, this.memos = {}, this.modules = {}, this.layoutInfo = {}, this.options = e().extend(!0, {}, o), e().summernote.ui = e().summernote.ui_template(this.options), this.ui = e().summernote.ui, this.initialize()
                }

                var n, o, i;
                return n = t, o = [{
                    key: "initialize", value: function () {
                        return this.layoutInfo = this.ui.createLayout(this.$note), this._initialize(), this.$note.hide(), this
                    }
                }, {
                    key: "destroy", value: function () {
                        this._destroy(), this.$note.removeData("summernote"), this.ui.removeLayout(this.$note, this.layoutInfo)
                    }
                }, {
                    key: "reset", value: function () {
                        var t = this.isDisabled();
                        this.code(ht.emptyPara), this._destroy(), this._initialize(), t && this.disable()
                    }
                }, {
                    key: "_initialize", value: function () {
                        var t = this;
                        this.options.id = m.uniqueId(e().now()), this.options.container = this.options.container || this.layoutInfo.editor;
                        var n = e().extend({}, this.options.buttons);
                        Object.keys(n).forEach((function (e) {
                            t.memo("button."+e, n[e])
                        }));
                        var o = e().extend({}, this.options.modules, e().summernote.plugins || {});
                        Object.keys(o).forEach((function (e) {
                            t.module(e, o[e], !0)
                        })), Object.keys(this.modules).forEach((function (e) {
                            t.initializeModule(e)
                        }))
                    }
                }, {
                    key: "_destroy", value: function () {
                        var t = this;
                        Object.keys(this.modules).reverse().forEach((function (e) {
                            t.removeModule(e)
                        })), Object.keys(this.memos).forEach((function (e) {
                            t.removeMemo(e)
                        })), this.triggerEvent("destroy", this)
                    }
                }, {
                    key: "code", value: function (t) {
                        var e = this.invoke("codeview.isActivated");
                        if (void 0 === t) return this.invoke("codeview.sync"), e ? this.layoutInfo.codable.val() : this.layoutInfo.editable.html();
                        e ? this.invoke("codeview.sync", t) : this.layoutInfo.editable.html(t), this.$note.val(t), this.triggerEvent("change", t, this.layoutInfo.editable)
                    }
                }, {
                    key: "isDisabled", value: function () {
                        return "false" === this.layoutInfo.editable.attr("contenteditable")
                    }
                }, {
                    key: "enable", value: function () {
                        this.layoutInfo.editable.attr("contenteditable", !0), this.invoke("toolbar.activate", !0), this.triggerEvent("disable", !1), this.options.editing = !0
                    }
                }, {
                    key: "disable", value: function () {
                        this.invoke("codeview.isActivated") && this.invoke("codeview.deactivate"), this.layoutInfo.editable.attr("contenteditable", !1), this.options.editing = !1, this.invoke("toolbar.deactivate", !0), this.triggerEvent("disable", !0)
                    }
                }, {
                    key: "triggerEvent", value: function () {
                        var t = k.head(arguments), e = k.tail(k.from(arguments)),
                            n = this.options.callbacks[m.namespaceToCamel(t, "on")];
                        n && n.apply(this.$note[0], e), this.$note.trigger("summernote."+t, e)
                    }
                }, {
                    key: "initializeModule", value: function (t) {
                        var e = this.modules[t];
                        e.shouldInitialize = e.shouldInitialize || m.ok, e.shouldInitialize() && (e.initialize && e.initialize(), e.events && ht.attachEvents(this.$note, e.events))
                    }
                }, {
                    key: "module", value: function (t, e, n) {
                        if (1 === arguments.length) return this.modules[t];
                        this.modules[t] = new e(this), n || this.initializeModule(t)
                    }
                }, {
                    key: "removeModule", value: function (t) {
                        var e = this.modules[t];
                        e.shouldInitialize() && (e.events && ht.detachEvents(this.$note, e.events), e.destroy && e.destroy()), delete this.modules[t]
                    }
                }, {
                    key: "memo", value: function (t, e) {
                        if (1 === arguments.length) return this.memos[t];
                        this.memos[t] = e
                    }
                }, {
                    key: "removeMemo", value: function (t) {
                        this.memos[t] && this.memos[t].destroy && this.memos[t].destroy(), delete this.memos[t]
                    }
                }, {
                    key: "createInvokeHandlerAndUpdateState", value: function (t, e) {
                        var n = this;
                        return function (o) {
                            n.createInvokeHandler(t, e)(o), n.invoke("buttons.updateCurrentStyle")
                        }
                    }
                }, {
                    key: "createInvokeHandler", value: function (t, n) {
                        var o = this;
                        return function (i) {
                            i.preventDefault();
                            var r = e()(i.target);
                            o.invoke(t, n || r.closest("[data-value]").data("value"), r)
                        }
                    }
                }, {
                    key: "invoke", value: function () {
                        var t = k.head(arguments), e = k.tail(k.from(arguments)), n = t.split("."), o = n.length > 1,
                            i = o && k.head(n), r = o ? k.last(n) : k.head(n), a = this.modules[i || "editor"];
                        return !i && this[r] ? this[r].apply(this, e) : a && a[r] && a.shouldInitialize() ? a[r].apply(a, e) : void 0
                    }
                }], o && ft(n.prototype, o), i && ft(n, i), t
            }();

            function mt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            function vt(t, e) {
                var n, o, i = t.parentElement(), r = document.body.createTextRange(), a = k.from(i.childNodes);
                for (n = 0; n < a.length; n++) if (!ht.isText(a[n])) {
                    if (r.moveToElementText(a[n]), r.compareEndPoints("StartToStart", t) >= 0) break;
                    o = a[n]
                }
                if (0 !== n && ht.isText(a[n-1])) {
                    var s = document.body.createTextRange(), l = null;
                    s.moveToElementText(o || i), s.collapse(!o), l = o ? o.nextSibling : i.firstChild;
                    var c = t.duplicate();
                    c.setEndPoint("StartToStart", s);
                    for (var u = c.text.replace(/[\r\n]/g, "").length; u > l.nodeValue.length && l.nextSibling;) u -= l.nodeValue.length, l = l.nextSibling;
                    l.nodeValue;
                    e && l.nextSibling && ht.isText(l.nextSibling) && u === l.nodeValue.length && (u -= l.nodeValue.length, l = l.nextSibling), i = l, n = u
                }
                return {cont: i, offset: n}
            }

            function gt(t) {
                var e = document.body.createTextRange(), n = function t(e, n) {
                    var o, i;
                    if (ht.isText(e)) {
                        var r = ht.listPrev(e, m.not(ht.isText)), a = k.last(r).previousSibling;
                        o = a || e.parentNode, n += k.sum(k.tail(r), ht.nodeLength), i = !a
                    } else {
                        if (o = e.childNodes[n] || e, ht.isText(o)) return t(o, 0);
                        n = 0, i = !1
                    }
                    return {node: o, collapseToStart: i, offset: n}
                }(t.node, t.offset);
                return e.moveToElementText(n.node), e.collapse(n.collapseToStart), e.moveStart("character", n.offset), e
            }

            e().fn.extend({
                summernote: function () {
                    var t = e().type(k.head(arguments)), n = "string" === t, o = "object" === t,
                        i = e().extend({}, e().summernote.options, o ? k.head(arguments) : {});
                    i.langInfo = e().extend(!0, {}, e().summernote.lang["en-US"], e().summernote.lang[i.lang]), i.icons = e().extend(!0, {}, e().summernote.options.icons, i.icons), i.tooltip = "auto" === i.tooltip ? !f.isSupportTouch : i.tooltip, this.each((function (t, n) {
                        var o = e()(n);
                        if (!o.data("summernote")) {
                            var r = new pt(o, i);
                            o.data("summernote", r), o.data("summernote").triggerEvent("init", r.layoutInfo)
                        }
                    }));
                    var r = this.first();
                    if (r.length) {
                        var a = r.data("summernote");
                        if (n) return a.invoke.apply(a, k.from(arguments));
                        i.focus && a.invoke("editor.focus")
                    }
                    return this
                }
            });
            var bt = function () {
                function t(e, n, o, i) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.sc = e, this.so = n, this.ec = o, this.eo = i, this.isOnEditable = this.makeIsOn(ht.isEditable), this.isOnList = this.makeIsOn(ht.isList), this.isOnAnchor = this.makeIsOn(ht.isAnchor), this.isOnCell = this.makeIsOn(ht.isCell), this.isOnData = this.makeIsOn(ht.isData)
                }

                var n, o, i;
                return n = t, o = [{
                    key: "nativeRange", value: function () {
                        if (f.isW3CRangeSupport) {
                            var t = document.createRange();
                            return t.setStart(this.sc, this.so), t.setEnd(this.ec, this.eo), t
                        }
                        var e = gt({node: this.sc, offset: this.so});
                        return e.setEndPoint("EndToEnd", gt({node: this.ec, offset: this.eo})), e
                    }
                }, {
                    key: "getPoints", value: function () {
                        return {sc: this.sc, so: this.so, ec: this.ec, eo: this.eo}
                    }
                }, {
                    key: "getStartPoint", value: function () {
                        return {node: this.sc, offset: this.so}
                    }
                }, {
                    key: "getEndPoint", value: function () {
                        return {node: this.ec, offset: this.eo}
                    }
                }, {
                    key: "select", value: function () {
                        var t = this.nativeRange();
                        if (f.isW3CRangeSupport) {
                            var e = document.getSelection();
                            e.rangeCount > 0 && e.removeAllRanges(), e.addRange(t)
                        } else t.select();
                        return this
                    }
                }, {
                    key: "scrollIntoView", value: function (t) {
                        var n = e()(t).height();
                        return t.scrollTop+n < this.sc.offsetTop && (t.scrollTop += Math.abs(t.scrollTop+n-this.sc.offsetTop)), this
                    }
                }, {
                    key: "normalize", value: function () {
                        var e = function (t, e) {
                            if (!t) return t;
                            if (ht.isVisiblePoint(t) && (!ht.isEdgePoint(t) || ht.isRightEdgePoint(t) && !e || ht.isLeftEdgePoint(t) && e || ht.isRightEdgePoint(t) && e && ht.isVoid(t.node.nextSibling) || ht.isLeftEdgePoint(t) && !e && ht.isVoid(t.node.previousSibling) || ht.isBlock(t.node) && ht.isEmpty(t.node))) return t;
                            var n = ht.ancestor(t.node, ht.isBlock), o = !1;
                            if (!o) {
                                var i = ht.prevPoint(t) || {node: null};
                                o = (ht.isLeftEdgePointOf(t, n) || ht.isVoid(i.node)) && !e
                            }
                            var r = !1;
                            if (!r) {
                                var a = ht.nextPoint(t) || {node: null};
                                r = (ht.isRightEdgePointOf(t, n) || ht.isVoid(a.node)) && e
                            }
                            if (o || r) {
                                if (ht.isVisiblePoint(t)) return t;
                                e = !e
                            }
                            return (e ? ht.nextPointUntil(ht.nextPoint(t), ht.isVisiblePoint) : ht.prevPointUntil(ht.prevPoint(t), ht.isVisiblePoint)) || t
                        }, n = e(this.getEndPoint(), !1), o = this.isCollapsed() ? n : e(this.getStartPoint(), !0);
                        return new t(o.node, o.offset, n.node, n.offset)
                    }
                }, {
                    key: "nodes", value: function (t, e) {
                        t = t || m.ok;
                        var n = e && e.includeAncestor, o = e && e.fullyContains, i = this.getStartPoint(),
                            r = this.getEndPoint(), a = [], s = [];
                        return ht.walkPoint(i, r, (function (e) {
                            var i;
                            ht.isEditable(e.node) || (o ? (ht.isLeftEdgePoint(e) && s.push(e.node), ht.isRightEdgePoint(e) && k.contains(s, e.node) && (i = e.node)) : i = n ? ht.ancestor(e.node, t) : e.node, i && t(i) && a.push(i))
                        }), !0), k.unique(a)
                    }
                }, {
                    key: "commonAncestor", value: function () {
                        return ht.commonAncestor(this.sc, this.ec)
                    }
                }, {
                    key: "expand", value: function (e) {
                        var n = ht.ancestor(this.sc, e), o = ht.ancestor(this.ec, e);
                        if (!n && !o) return new t(this.sc, this.so, this.ec, this.eo);
                        var i = this.getPoints();
                        return n && (i.sc = n, i.so = 0), o && (i.ec = o, i.eo = ht.nodeLength(o)), new t(i.sc, i.so, i.ec, i.eo)
                    }
                }, {
                    key: "collapse", value: function (e) {
                        return e ? new t(this.sc, this.so, this.sc, this.so) : new t(this.ec, this.eo, this.ec, this.eo)
                    }
                }, {
                    key: "splitText", value: function () {
                        var e = this.sc === this.ec, n = this.getPoints();
                        return ht.isText(this.ec) && !ht.isEdgePoint(this.getEndPoint()) && this.ec.splitText(this.eo), ht.isText(this.sc) && !ht.isEdgePoint(this.getStartPoint()) && (n.sc = this.sc.splitText(this.so), n.so = 0, e && (n.ec = n.sc, n.eo = this.eo-this.so)), new t(n.sc, n.so, n.ec, n.eo)
                    }
                }, {
                    key: "deleteContents", value: function () {
                        if (this.isCollapsed()) return this;
                        var n = this.splitText(), o = n.nodes(null, {fullyContains: !0}),
                            i = ht.prevPointUntil(n.getStartPoint(), (function (t) {
                                return !k.contains(o, t.node)
                            })), r = [];
                        return e().each(o, (function (t, e) {
                            var n = e.parentNode;
                            i.node !== n && 1 === ht.nodeLength(n) && r.push(n), ht.remove(e, !1)
                        })), e().each(r, (function (t, e) {
                            ht.remove(e, !1)
                        })), new t(i.node, i.offset, i.node, i.offset).normalize()
                    }
                }, {
                    key: "makeIsOn", value: function (t) {
                        return function () {
                            var e = ht.ancestor(this.sc, t);
                            return !!e && e === ht.ancestor(this.ec, t)
                        }
                    }
                }, {
                    key: "isLeftEdgeOf", value: function (t) {
                        if (!ht.isLeftEdgePoint(this.getStartPoint())) return !1;
                        var e = ht.ancestor(this.sc, t);
                        return e && ht.isLeftEdgeOf(this.sc, e)
                    }
                }, {
                    key: "isCollapsed", value: function () {
                        return this.sc === this.ec && this.so === this.eo
                    }
                }, {
                    key: "wrapBodyInlineWithPara", value: function () {
                        if (ht.isBodyContainer(this.sc) && ht.isEmpty(this.sc)) return this.sc.innerHTML = ht.emptyPara, new t(this.sc.firstChild, 0, this.sc.firstChild, 0);
                        var e, n = this.normalize();
                        if (ht.isParaInline(this.sc) || ht.isPara(this.sc)) return n;
                        if (ht.isInline(n.sc)) {
                            var o = ht.listAncestor(n.sc, m.not(ht.isInline));
                            e = k.last(o), ht.isInline(e) || (e = o[o.length-2] || n.sc.childNodes[n.so])
                        } else e = n.sc.childNodes[n.so > 0 ? n.so-1 : 0];
                        if (e) {
                            var i = ht.listPrev(e, ht.isParaInline).reverse();
                            if ((i = i.concat(ht.listNext(e.nextSibling, ht.isParaInline))).length) {
                                var r = ht.wrap(k.head(i), "p");
                                ht.appendChildNodes(r, k.tail(i))
                            }
                        }
                        return this.normalize()
                    }
                }, {
                    key: "insertNode", value: function (t) {
                        var e = this;
                        (ht.isText(t) || ht.isInline(t)) && (e = this.wrapBodyInlineWithPara().deleteContents());
                        var n = ht.splitPoint(e.getStartPoint(), ht.isInline(t));
                        return n.rightNode ? (n.rightNode.parentNode.insertBefore(t, n.rightNode), ht.isEmpty(n.rightNode) && ht.isPara(t) && n.rightNode.parentNode.removeChild(n.rightNode)) : n.container.appendChild(t), t
                    }
                }, {
                    key: "pasteHTML", value: function (t) {
                        t = e().trim(t);
                        var n = e()("<div></div>").html(t)[0], o = k.from(n.childNodes), i = this, r = !1;
                        return i.so >= 0 && (o = o.reverse(), r = !0), o = o.map((function (t) {
                            return i.insertNode(t)
                        })), r && (o = o.reverse()), o
                    }
                }, {
                    key: "toString", value: function () {
                        var t = this.nativeRange();
                        return f.isW3CRangeSupport ? t.toString() : t.text
                    }
                }, {
                    key: "getWordRange", value: function (e) {
                        var n = this.getEndPoint();
                        if (!ht.isCharPoint(n)) return this;
                        var o = ht.prevPointUntil(n, (function (t) {
                            return !ht.isCharPoint(t)
                        }));
                        return e && (n = ht.nextPointUntil(n, (function (t) {
                            return !ht.isCharPoint(t)
                        }))), new t(o.node, o.offset, n.node, n.offset)
                    }
                }, {
                    key: "getWordsRange", value: function (e) {
                        var n = this.getEndPoint(), o = function (t) {
                            return !ht.isCharPoint(t) && !ht.isSpacePoint(t)
                        };
                        if (o(n)) return this;
                        var i = ht.prevPointUntil(n, o);
                        return e && (n = ht.nextPointUntil(n, o)), new t(i.node, i.offset, n.node, n.offset)
                    }
                }, {
                    key: "getWordsMatchRange", value: function (e) {
                        var n = this.getEndPoint(), o = ht.prevPointUntil(n, (function (o) {
                            if (!ht.isCharPoint(o) && !ht.isSpacePoint(o)) return !0;
                            var i = new t(o.node, o.offset, n.node, n.offset), r = e.exec(i.toString());
                            return r && 0 === r.index
                        })), i = new t(o.node, o.offset, n.node, n.offset), r = i.toString(), a = e.exec(r);
                        return a && a[0].length === r.length ? i : null
                    }
                }, {
                    key: "bookmark", value: function (t) {
                        return {
                            s: {path: ht.makeOffsetPath(t, this.sc), offset: this.so},
                            e: {path: ht.makeOffsetPath(t, this.ec), offset: this.eo}
                        }
                    }
                }, {
                    key: "paraBookmark", value: function (t) {
                        return {
                            s: {path: k.tail(ht.makeOffsetPath(k.head(t), this.sc)), offset: this.so},
                            e: {path: k.tail(ht.makeOffsetPath(k.last(t), this.ec)), offset: this.eo}
                        }
                    }
                }, {
                    key: "getClientRects", value: function () {
                        return this.nativeRange().getClientRects()
                    }
                }], o && mt(n.prototype, o), i && mt(n, i), t
            }();
            const yt = {
                create: function (t, e, n, o) {
                    if (4 === arguments.length) return new bt(t, e, n, o);
                    if (2 === arguments.length) return new bt(t, e, n = t, o = e);
                    var i = this.createFromSelection();
                    if (!i && 1 === arguments.length) {
                        var r = arguments[0];
                        return ht.isEditable(r) && (r = r.lastChild), this.createFromBodyElement(r, ht.emptyPara === arguments[0].innerHTML)
                    }
                    return i
                }, createFromBodyElement: function (t) {
                    var e = arguments.length > 1 && void 0 !== arguments[1] && arguments[1], n = this.createFromNode(t);
                    return n.collapse(e)
                }, createFromSelection: function () {
                    var t, e, n, o;
                    if (f.isW3CRangeSupport) {
                        var i = document.getSelection();
                        if (!i || 0 === i.rangeCount) return null;
                        if (ht.isBody(i.anchorNode)) return null;
                        var r = i.getRangeAt(0);
                        t = r.startContainer, e = r.startOffset, n = r.endContainer, o = r.endOffset
                    } else {
                        var a = document.selection.createRange(), s = a.duplicate();
                        s.collapse(!1);
                        var l = a;
                        l.collapse(!0);
                        var c = vt(l, !0), u = vt(s, !1);
                        ht.isText(c.node) && ht.isLeftEdgePoint(c) && ht.isTextNode(u.node) && ht.isRightEdgePoint(u) && u.node.nextSibling === c.node && (c = u), t = c.cont, e = c.offset, n = u.cont, o = u.offset
                    }
                    return new bt(t, e, n, o)
                }, createFromNode: function (t) {
                    var e = t, n = 0, o = t, i = ht.nodeLength(o);
                    return ht.isVoid(e) && (n = ht.listPrev(e).length-1, e = e.parentNode), ht.isBR(o) ? (i = ht.listPrev(o).length-1, o = o.parentNode) : ht.isVoid(o) && (i = ht.listPrev(o).length, o = o.parentNode), this.create(e, n, o, i)
                }, createFromNodeBefore: function (t) {
                    return this.createFromNode(t).collapse(!0)
                }, createFromNodeAfter: function (t) {
                    return this.createFromNode(t).collapse()
                }, createFromBookmark: function (t, e) {
                    var n = ht.fromOffsetPath(t, e.s.path), o = e.s.offset, i = ht.fromOffsetPath(t, e.e.path),
                        r = e.e.offset;
                    return new bt(n, o, i, r)
                }, createFromParaBookmark: function (t, e) {
                    var n = t.s.offset, o = t.e.offset, i = ht.fromOffsetPath(k.head(e), t.s.path),
                        r = ht.fromOffsetPath(k.last(e), t.e.path);
                    return new bt(i, n, r, o)
                }
            };
            var kt = {
                BACKSPACE: 8,
                TAB: 9,
                ENTER: 13,
                ESCAPE: 27,
                SPACE: 32,
                DELETE: 46,
                LEFT: 37,
                UP: 38,
                RIGHT: 39,
                DOWN: 40,
                NUM0: 48,
                NUM1: 49,
                NUM2: 50,
                NUM3: 51,
                NUM4: 52,
                NUM5: 53,
                NUM6: 54,
                NUM7: 55,
                NUM8: 56,
                B: 66,
                E: 69,
                I: 73,
                J: 74,
                K: 75,
                L: 76,
                R: 82,
                S: 83,
                U: 85,
                V: 86,
                Y: 89,
                Z: 90,
                SLASH: 191,
                LEFTBRACKET: 219,
                BACKSLASH: 220,
                RIGHTBRACKET: 221,
                HOME: 36,
                END: 35,
                PAGEUP: 33,
                PAGEDOWN: 34
            };
            const wt = {
                isEdit: function (t) {
                    return k.contains([kt.BACKSPACE, kt.TAB, kt.ENTER, kt.SPACE, kt.DELETE], t)
                }, isMove: function (t) {
                    return k.contains([kt.LEFT, kt.UP, kt.RIGHT, kt.DOWN], t)
                }, isNavigation: function (t) {
                    return k.contains([kt.HOME, kt.END, kt.PAGEUP, kt.PAGEDOWN], t)
                }, nameFromCode: m.invertObject(kt), code: kt
            };

            function Ct(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var xt = function () {
                function t(e) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.stack = [], this.stackOffset = -1, this.context = e, this.$editable = e.layoutInfo.editable, this.editable = this.$editable[0]
                }

                var e, n, o;
                return e = t, (n = [{
                    key: "makeSnapshot", value: function () {
                        var t = yt.create(this.editable);
                        return {
                            contents: this.$editable.html(),
                            bookmark: t && t.isOnEditable() ? t.bookmark(this.editable) : {
                                s: {path: [], offset: 0},
                                e: {path: [], offset: 0}
                            }
                        }
                    }
                }, {
                    key: "applySnapshot", value: function (t) {
                        null !== t.contents && this.$editable.html(t.contents), null !== t.bookmark && yt.createFromBookmark(this.editable, t.bookmark).select()
                    }
                }, {
                    key: "rewind", value: function () {
                        this.$editable.html() !== this.stack[this.stackOffset].contents && this.recordUndo(), this.stackOffset = 0, this.applySnapshot(this.stack[this.stackOffset])
                    }
                }, {
                    key: "commit", value: function () {
                        this.stack = [], this.stackOffset = -1, this.recordUndo()
                    }
                }, {
                    key: "reset", value: function () {
                        this.stack = [], this.stackOffset = -1, this.$editable.html(""), this.recordUndo()
                    }
                }, {
                    key: "undo", value: function () {
                        this.$editable.html() !== this.stack[this.stackOffset].contents && this.recordUndo(), this.stackOffset > 0 && (this.stackOffset--, this.applySnapshot(this.stack[this.stackOffset]))
                    }
                }, {
                    key: "redo", value: function () {
                        this.stack.length-1 > this.stackOffset && (this.stackOffset++, this.applySnapshot(this.stack[this.stackOffset]))
                    }
                }, {
                    key: "recordUndo", value: function () {
                        this.stackOffset++, this.stack.length > this.stackOffset && (this.stack = this.stack.slice(0, this.stackOffset)), this.stack.push(this.makeSnapshot()), this.stack.length > this.context.options.historyLimit && (this.stack.shift(), this.stackOffset -= 1)
                    }
                }]) && Ct(e.prototype, n), o && Ct(e, o), t
            }();

            function St(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Tt = function () {
                function t() {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t)
                }

                var n, o, i;
                return n = t, o = [{
                    key: "jQueryCSS", value: function (t, n) {
                        var o = {};
                        return e().each(n, (function (e, n) {
                            o[n] = t.css(n)
                        })), o
                    }
                }, {
                    key: "fromNode", value: function (t) {
                        var e = this.jQueryCSS(t, ["font-family", "font-size", "text-align", "list-style-type", "line-height"]) || {},
                            n = t[0].style.fontSize || e["font-size"];
                        return e["font-size"] = parseInt(n, 10), e["font-size-unit"] = n.match(/[a-z%]+$/), e
                    }
                }, {
                    key: "stylePara", value: function (t, n) {
                        e().each(t.nodes(ht.isPara, {includeAncestor: !0}), (function (t, o) {
                            e()(o).css(n)
                        }))
                    }
                }, {
                    key: "styleNodes", value: function (t, n) {
                        t = t.splitText();
                        var o = n && n.nodeName || "SPAN", i = !(!n || !n.expandClosestSibling),
                            r = !(!n || !n.onlyPartialContains);
                        if (t.isCollapsed()) return [t.insertNode(ht.create(o))];
                        var a = ht.makePredByNodeName(o),
                            s = t.nodes(ht.isText, {fullyContains: !0}).map((function (t) {
                                return ht.singleChildAncestor(t, a) || ht.wrap(t, o)
                            }));
                        if (i) {
                            if (r) {
                                var l = t.nodes();
                                a = m.and(a, (function (t) {
                                    return k.contains(l, t)
                                }))
                            }
                            return s.map((function (t) {
                                var n = ht.withClosestSiblings(t, a), o = k.head(n), i = k.tail(n);
                                return e().each(i, (function (t, e) {
                                    ht.appendChildNodes(o, e.childNodes), ht.remove(e)
                                })), k.head(n)
                            }))
                        }
                        return s
                    }
                }, {
                    key: "current", value: function (t) {
                        var n = e()(ht.isElement(t.sc) ? t.sc : t.sc.parentNode), o = this.fromNode(n);
                        try {
                            o = e().extend(o, {
                                "font-bold": document.queryCommandState("bold") ? "bold" : "normal",
                                "font-italic": document.queryCommandState("italic") ? "italic" : "normal",
                                "font-underline": document.queryCommandState("underline") ? "underline" : "normal",
                                "font-subscript": document.queryCommandState("subscript") ? "subscript" : "normal",
                                "font-superscript": document.queryCommandState("superscript") ? "superscript" : "normal",
                                "font-strikethrough": document.queryCommandState("strikethrough") ? "strikethrough" : "normal",
                                "font-family": document.queryCommandValue("fontname") || o["font-family"]
                            })
                        } catch (t) {
                        }
                        if (t.isOnList()) {
                            var i = ["circle", "disc", "disc-leading-zero", "square"].indexOf(o["list-style-type"]) > -1;
                            o["list-style"] = i ? "unordered" : "ordered"
                        } else o["list-style"] = "none";
                        var r = ht.ancestor(t.sc, ht.isPara);
                        if (r && r.style["line-height"]) o["line-height"] = r.style.lineHeight; else {
                            var a = parseInt(o["line-height"], 10) / parseInt(o["font-size"], 10);
                            o["line-height"] = a.toFixed(1)
                        }
                        return o.anchor = t.isOnAnchor() && ht.ancestor(t.sc, ht.isAnchor), o.ancestors = ht.listAncestor(t.sc, ht.isEditable), o.range = t, o
                    }
                }], o && St(n.prototype, o), i && St(n, i), t
            }();

            function Et(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var It = function () {
                function t() {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t)
                }

                var n, o, i;
                return n = t, o = [{
                    key: "insertOrderedList", value: function (t) {
                        this.toggleList("OL", t)
                    }
                }, {
                    key: "insertUnorderedList", value: function (t) {
                        this.toggleList("UL", t)
                    }
                }, {
                    key: "indent", value: function (t) {
                        var n = this, o = yt.create(t).wrapBodyInlineWithPara(),
                            i = o.nodes(ht.isPara, {includeAncestor: !0}), r = k.clusterBy(i, m.peq2("parentNode"));
                        e().each(r, (function (t, o) {
                            var i = k.head(o);
                            if (ht.isLi(i)) {
                                var r = n.findList(i.previousSibling);
                                r ? o.map((function (t) {
                                    return r.appendChild(t)
                                })) : (n.wrapList(o, i.parentNode.nodeName), o.map((function (t) {
                                    return t.parentNode
                                })).map((function (t) {
                                    return n.appendToPrevious(t)
                                })))
                            } else e().each(o, (function (t, n) {
                                e()(n).css("marginLeft", (function (t, e) {
                                    return (parseInt(e, 10) || 0)+25
                                }))
                            }))
                        })), o.select()
                    }
                }, {
                    key: "outdent", value: function (t) {
                        var n = this, o = yt.create(t).wrapBodyInlineWithPara(),
                            i = o.nodes(ht.isPara, {includeAncestor: !0}), r = k.clusterBy(i, m.peq2("parentNode"));
                        e().each(r, (function (t, o) {
                            var i = k.head(o);
                            ht.isLi(i) ? n.releaseList([o]) : e().each(o, (function (t, n) {
                                e()(n).css("marginLeft", (function (t, e) {
                                    return (e = parseInt(e, 10) || 0) > 25 ? e-25 : ""
                                }))
                            }))
                        })), o.select()
                    }
                }, {
                    key: "toggleList", value: function (t, n) {
                        var o = this, i = yt.create(n).wrapBodyInlineWithPara(),
                            r = i.nodes(ht.isPara, {includeAncestor: !0}), a = i.paraBookmark(r),
                            s = k.clusterBy(r, m.peq2("parentNode"));
                        if (k.find(r, ht.isPurePara)) {
                            var l = [];
                            e().each(s, (function (e, n) {
                                l = l.concat(o.wrapList(n, t))
                            })), r = l
                        } else {
                            var c = i.nodes(ht.isList, {includeAncestor: !0}).filter((function (n) {
                                return !e().nodeName(n, t)
                            }));
                            c.length ? e().each(c, (function (e, n) {
                                ht.replace(n, t)
                            })) : r = this.releaseList(s, !0)
                        }
                        yt.createFromParaBookmark(a, r).select()
                    }
                }, {
                    key: "wrapList", value: function (t, e) {
                        var n = k.head(t), o = k.last(t), i = ht.isList(n.previousSibling) && n.previousSibling,
                            r = ht.isList(o.nextSibling) && o.nextSibling,
                            a = i || ht.insertAfter(ht.create(e || "UL"), o);
                        return t = t.map((function (t) {
                            return ht.isPurePara(t) ? ht.replace(t, "LI") : t
                        })), ht.appendChildNodes(a, t), r && (ht.appendChildNodes(a, k.from(r.childNodes)), ht.remove(r)), t
                    }
                }, {
                    key: "releaseList", value: function (t, n) {
                        var o = this, i = [];
                        return e().each(t, (function (t, r) {
                            var a = k.head(r), s = k.last(r), l = n ? ht.lastAncestor(a, ht.isList) : a.parentNode,
                                c = l.parentNode;
                            if ("LI" === l.parentNode.nodeName) r.map((function (t) {
                                var e = o.findNextSiblings(t);
                                c.nextSibling ? c.parentNode.insertBefore(t, c.nextSibling) : c.parentNode.appendChild(t), e.length && (o.wrapList(e, l.nodeName), t.appendChild(e[0].parentNode))
                            })), 0 === l.children.length && c.removeChild(l), 0 === c.childNodes.length && c.parentNode.removeChild(c); else {
                                var u = l.childNodes.length > 1 ? ht.splitTree(l, {
                                    node: s.parentNode,
                                    offset: ht.position(s)+1
                                }, {isSkipPaddingBlankHTML: !0}) : null, d = ht.splitTree(l, {
                                    node: a.parentNode,
                                    offset: ht.position(a)
                                }, {isSkipPaddingBlankHTML: !0});
                                r = n ? ht.listDescendant(d, ht.isLi) : k.from(d.childNodes).filter(ht.isLi), !n && ht.isList(l.parentNode) || (r = r.map((function (t) {
                                    return ht.replace(t, "P")
                                }))), e().each(k.from(r).reverse(), (function (t, e) {
                                    ht.insertAfter(e, l)
                                }));
                                var h = k.compact([l, d, u]);
                                e().each(h, (function (t, n) {
                                    var o = [n].concat(ht.listDescendant(n, ht.isList));
                                    e().each(o.reverse(), (function (t, e) {
                                        ht.nodeLength(e) || ht.remove(e, !0)
                                    }))
                                }))
                            }
                            i = i.concat(r)
                        })), i
                    }
                }, {
                    key: "appendToPrevious", value: function (t) {
                        return t.previousSibling ? ht.appendChildNodes(t.previousSibling, [t]) : this.wrapList([t], "LI")
                    }
                }, {
                    key: "findList", value: function (t) {
                        return t ? k.find(t.children, (function (t) {
                            return ["OL", "UL"].indexOf(t.nodeName) > -1
                        })) : null
                    }
                }, {
                    key: "findNextSiblings", value: function (t) {
                        for (var e = []; t.nextSibling;) e.push(t.nextSibling), t = t.nextSibling;
                        return e
                    }
                }], o && Et(n.prototype, o), i && Et(n, i), t
            }();

            function $t(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Nt = function () {
                function t(e) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.bullet = new It, this.options = e.options
                }

                var n, o, i;
                return n = t, o = [{
                    key: "insertTab", value: function (t, e) {
                        var n = ht.createText(new Array(e+1).join(ht.NBSP_CHAR));
                        (t = t.deleteContents()).insertNode(n, !0), (t = yt.create(n, e)).select()
                    }
                }, {
                    key: "insertParagraph", value: function (t, n) {
                        n = (n = (n = n || yt.create(t)).deleteContents()).wrapBodyInlineWithPara();
                        var o, i = ht.ancestor(n.sc, ht.isPara);
                        if (i) {
                            if (ht.isLi(i) && (ht.isEmpty(i) || ht.deepestChildIsEmpty(i))) return void this.bullet.toggleList(i.parentNode.nodeName);
                            var r = null;
                            if (1 === this.options.blockquoteBreakingLevel ? r = ht.ancestor(i, ht.isBlockquote) : 2 === this.options.blockquoteBreakingLevel && (r = ht.lastAncestor(i, ht.isBlockquote)), r) {
                                o = e()(ht.emptyPara)[0], ht.isRightEdgePoint(n.getStartPoint()) && ht.isBR(n.sc.nextSibling) && e()(n.sc.nextSibling).remove();
                                var a = ht.splitTree(r, n.getStartPoint(), {isDiscardEmptySplits: !0});
                                a ? a.parentNode.insertBefore(o, a) : ht.insertAfter(o, r)
                            } else {
                                o = ht.splitTree(i, n.getStartPoint());
                                var s = ht.listDescendant(i, ht.isEmptyAnchor);
                                s = s.concat(ht.listDescendant(o, ht.isEmptyAnchor)), e().each(s, (function (t, e) {
                                    ht.remove(e)
                                })), (ht.isHeading(o) || ht.isPre(o) || ht.isCustomStyleTag(o)) && ht.isEmpty(o) && (o = ht.replace(o, "p"))
                            }
                        } else {
                            var l = n.sc.childNodes[n.so];
                            o = e()(ht.emptyPara)[0], l ? n.sc.insertBefore(o, l) : n.sc.appendChild(o)
                        }
                        yt.create(o, 0).normalize().select().scrollIntoView(t)
                    }
                }], o && $t(n.prototype, o), i && $t(n, i), t
            }();

            function Pt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Rt = function t(e, n, o, i) {
                var r = {colPos: 0, rowPos: 0}, a = [], s = [];

                function l(t, e, n, o, i, r, s) {
                    var l = {baseRow: n, baseCell: o, isRowSpan: i, isColSpan: r, isVirtual: s};
                    a[t] || (a[t] = []), a[t][e] = l
                }

                function c(t, e, n, o) {
                    return {baseCell: t.baseCell, action: e, virtualTable: {rowIndex: n, cellIndex: o}}
                }

                function u(t, e) {
                    if (!a[t]) return e;
                    if (!a[t][e]) return e;
                    for (var n = e; a[t][n];) if (n++, !a[t][n]) return n
                }

                function d(t, e) {
                    var n = u(t.rowIndex, e.cellIndex), o = e.colSpan > 1, i = e.rowSpan > 1,
                        a = t.rowIndex === r.rowPos && e.cellIndex === r.colPos;
                    l(t.rowIndex, n, t, e, i, o, !1);
                    var s = e.attributes.rowSpan ? parseInt(e.attributes.rowSpan.value, 10) : 0;
                    if (s > 1) for (var c = 1; c < s; c++) {
                        var d = t.rowIndex+c;
                        h(d, n, e, a), l(d, n, t, e, !0, o, !0)
                    }
                    var f = e.attributes.colSpan ? parseInt(e.attributes.colSpan.value, 10) : 0;
                    if (f > 1) for (var p = 1; p < f; p++) {
                        var m = u(t.rowIndex, n+p);
                        h(t.rowIndex, m, e, a), l(t.rowIndex, m, t, e, i, !0, !0)
                    }
                }

                function h(t, e, n, o) {
                    t === r.rowPos && r.colPos >= n.cellIndex && n.cellIndex <= e && !o && r.colPos++
                }

                function f(e) {
                    switch (n) {
                        case t.where.Column:
                            if (e.isColSpan) return t.resultAction.SubtractSpanCount;
                            break;
                        case t.where.Row:
                            if (!e.isVirtual && e.isRowSpan) return t.resultAction.AddCell;
                            if (e.isRowSpan) return t.resultAction.SubtractSpanCount
                    }
                    return t.resultAction.RemoveCell
                }

                function p(e) {
                    switch (n) {
                        case t.where.Column:
                            if (e.isColSpan) return t.resultAction.SumSpanCount;
                            if (e.isRowSpan && e.isVirtual) return t.resultAction.Ignore;
                            break;
                        case t.where.Row:
                            if (e.isRowSpan) return t.resultAction.SumSpanCount;
                            if (e.isColSpan && e.isVirtual) return t.resultAction.Ignore
                    }
                    return t.resultAction.AddCell
                }

                this.getActionList = function () {
                    for (var e = n === t.where.Row ? r.rowPos : -1, i = n === t.where.Column ? r.colPos : -1, l = 0, u = !0; u;) {
                        var d = e >= 0 ? e : l, h = i >= 0 ? i : l, m = a[d];
                        if (!m) return u = !1, s;
                        var v = m[h];
                        if (!v) return u = !1, s;
                        var g = t.resultAction.Ignore;
                        switch (o) {
                            case t.requestAction.Add:
                                g = p(v);
                                break;
                            case t.requestAction.Delete:
                                g = f(v)
                        }
                        s.push(c(v, g, d, h)), l++
                    }
                    return s
                }, e && e.tagName && ("td" === e.tagName.toLowerCase() || "th" === e.tagName.toLowerCase()) && (r.colPos = e.cellIndex, e.parentElement && e.parentElement.tagName && "tr" === e.parentElement.tagName.toLowerCase() && (r.rowPos = e.parentElement.rowIndex)), function () {
                    for (var t = i.rows, e = 0; e < t.length; e++) for (var n = t[e].cells, o = 0; o < n.length; o++) d(t[e], n[o])
                }()
            };
            Rt.where = {Row: 0, Column: 1}, Rt.requestAction = {Add: 0, Delete: 1}, Rt.resultAction = {
                Ignore: 0,
                SubtractSpanCount: 1,
                RemoveCell: 2,
                AddCell: 3,
                SumSpanCount: 4
            };
            var Lt = function () {
                function t() {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t)
                }

                var n, o, i;
                return n = t, o = [{
                    key: "tab", value: function (t, e) {
                        var n = ht.ancestor(t.commonAncestor(), ht.isCell), o = ht.ancestor(n, ht.isTable),
                            i = ht.listDescendant(o, ht.isCell), r = k[e ? "prev" : "next"](i, n);
                        r && yt.create(r, 0).select()
                    }
                }, {
                    key: "addRow", value: function (t, n) {
                        for (var o = ht.ancestor(t.commonAncestor(), ht.isCell), i = e()(o).closest("tr"), r = this.recoverAttributes(i), a = e()("<tr"+r+"></tr>"), s = new Rt(o, Rt.where.Row, Rt.requestAction.Add, e()(i).closest("table")[0]).getActionList(), l = 0; l < s.length; l++) {
                            var c = s[l], u = this.recoverAttributes(c.baseCell);
                            switch (c.action) {
                                case Rt.resultAction.AddCell:
                                    a.append("<td"+u+">"+ht.blank+"</td>");
                                    break;
                                case Rt.resultAction.SumSpanCount:
                                    if ("top" === n && (c.baseCell.parent ? c.baseCell.closest("tr").rowIndex : 0) <= i[0].rowIndex) {
                                        var d = e()("<div></div>").append(e()("<td"+u+">"+ht.blank+"</td>").removeAttr("rowspan")).html();
                                        a.append(d);
                                        break
                                    }
                                    var h = parseInt(c.baseCell.rowSpan, 10);
                                    h++, c.baseCell.setAttribute("rowSpan", h)
                            }
                        }
                        if ("top" === n) i.before(a); else {
                            if (o.rowSpan > 1) {
                                var f = i[0].rowIndex+(o.rowSpan-2);
                                return void e()(e()(i).parent().find("tr")[f]).after(e()(a))
                            }
                            i.after(a)
                        }
                    }
                }, {
                    key: "addCol", value: function (t, n) {
                        var o = ht.ancestor(t.commonAncestor(), ht.isCell), i = e()(o).closest("tr");
                        e()(i).siblings().push(i);
                        for (var r = new Rt(o, Rt.where.Column, Rt.requestAction.Add, e()(i).closest("table")[0]).getActionList(), a = 0; a < r.length; a++) {
                            var s = r[a], l = this.recoverAttributes(s.baseCell);
                            switch (s.action) {
                                case Rt.resultAction.AddCell:
                                    "right" === n ? e()(s.baseCell).after("<td"+l+">"+ht.blank+"</td>") : e()(s.baseCell).before("<td"+l+">"+ht.blank+"</td>");
                                    break;
                                case Rt.resultAction.SumSpanCount:
                                    if ("right" === n) {
                                        var c = parseInt(s.baseCell.colSpan, 10);
                                        c++, s.baseCell.setAttribute("colSpan", c)
                                    } else e()(s.baseCell).before("<td"+l+">"+ht.blank+"</td>")
                            }
                        }
                    }
                }, {
                    key: "recoverAttributes", value: function (t) {
                        var e = "";
                        if (!t) return e;
                        for (var n = t.attributes || [], o = 0; o < n.length; o++) "id" !== n[o].name.toLowerCase() && n[o].specified && (e += " "+n[o].name+"='"+n[o].value+"'");
                        return e
                    }
                }, {
                    key: "deleteRow", value: function (t) {
                        for (var n = ht.ancestor(t.commonAncestor(), ht.isCell), o = e()(n).closest("tr"), i = o.children("td, th").index(e()(n)), r = o[0].rowIndex, a = new Rt(n, Rt.where.Row, Rt.requestAction.Delete, e()(o).closest("table")[0]).getActionList(), s = 0; s < a.length; s++) if (a[s]) {
                            var l = a[s].baseCell, c = a[s].virtualTable, u = l.rowSpan && l.rowSpan > 1,
                                d = u ? parseInt(l.rowSpan, 10) : 0;
                            switch (a[s].action) {
                                case Rt.resultAction.Ignore:
                                    continue;
                                case Rt.resultAction.AddCell:
                                    var h = o.next("tr")[0];
                                    if (!h) continue;
                                    var f = o[0].cells[i];
                                    u && (d > 2 ? (d--, h.insertBefore(f, h.cells[i]), h.cells[i].setAttribute("rowSpan", d), h.cells[i].innerHTML = "") : 2 === d && (h.insertBefore(f, h.cells[i]), h.cells[i].removeAttribute("rowSpan"), h.cells[i].innerHTML = ""));
                                    continue;
                                case Rt.resultAction.SubtractSpanCount:
                                    u && (d > 2 ? (d--, l.setAttribute("rowSpan", d), c.rowIndex !== r && l.cellIndex === i && (l.innerHTML = "")) : 2 === d && (l.removeAttribute("rowSpan"), c.rowIndex !== r && l.cellIndex === i && (l.innerHTML = "")));
                                    continue;
                                case Rt.resultAction.RemoveCell:
                                    continue
                            }
                        }
                        o.remove()
                    }
                }, {
                    key: "deleteCol", value: function (t) {
                        for (var n = ht.ancestor(t.commonAncestor(), ht.isCell), o = e()(n).closest("tr"), i = o.children("td, th").index(e()(n)), r = new Rt(n, Rt.where.Column, Rt.requestAction.Delete, e()(o).closest("table")[0]).getActionList(), a = 0; a < r.length; a++) if (r[a]) switch (r[a].action) {
                            case Rt.resultAction.Ignore:
                                continue;
                            case Rt.resultAction.SubtractSpanCount:
                                var s = r[a].baseCell;
                                if (s.colSpan && s.colSpan > 1) {
                                    var l = s.colSpan ? parseInt(s.colSpan, 10) : 0;
                                    l > 2 ? (l--, s.setAttribute("colSpan", l), s.cellIndex === i && (s.innerHTML = "")) : 2 === l && (s.removeAttribute("colSpan"), s.cellIndex === i && (s.innerHTML = ""))
                                }
                                continue;
                            case Rt.resultAction.RemoveCell:
                                ht.remove(r[a].baseCell, !0);
                                continue
                        }
                    }
                }, {
                    key: "createTable", value: function (t, n, o) {
                        for (var i, r = [], a = 0; a < t; a++) r.push("<td>"+ht.blank+"</td>");
                        i = r.join("");
                        for (var s, l = [], c = 0; c < n; c++) l.push("<tr>"+i+"</tr>");
                        s = l.join("");
                        var u = e()("<table>"+s+"</table>");
                        return o && o.tableClassName && u.addClass(o.tableClassName), u[0]
                    }
                }, {
                    key: "deleteTable", value: function (t) {
                        var n = ht.ancestor(t.commonAncestor(), ht.isCell);
                        e()(n).closest("table").remove()
                    }
                }], o && Pt(n.prototype, o), i && Pt(n, i), t
            }();

            function At(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Ft = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.$note = n.layoutInfo.note, this.$editor = n.layoutInfo.editor, this.$editable = n.layoutInfo.editable, this.options = n.options, this.lang = this.options.langInfo, this.editable = this.$editable[0], this.lastRange = null, this.snapshot = null, this.style = new Tt, this.table = new Lt, this.typing = new Nt(n), this.bullet = new It, this.history = new xt(n), this.context.memo("help.escape", this.lang.help.escape), this.context.memo("help.undo", this.lang.help.undo), this.context.memo("help.redo", this.lang.help.redo), this.context.memo("help.tab", this.lang.help.tab), this.context.memo("help.untab", this.lang.help.untab), this.context.memo("help.insertParagraph", this.lang.help.insertParagraph), this.context.memo("help.insertOrderedList", this.lang.help.insertOrderedList), this.context.memo("help.insertUnorderedList", this.lang.help.insertUnorderedList), this.context.memo("help.indent", this.lang.help.indent), this.context.memo("help.outdent", this.lang.help.outdent), this.context.memo("help.formatPara", this.lang.help.formatPara), this.context.memo("help.insertHorizontalRule", this.lang.help.insertHorizontalRule), this.context.memo("help.fontName", this.lang.help.fontName);
                    for (var i = ["bold", "italic", "underline", "strikethrough", "superscript", "subscript", "justifyLeft", "justifyCenter", "justifyRight", "justifyFull", "formatBlock", "removeFormat", "backColor"], r = 0, a = i.length; r < a; r++) this[i[r]] = function (t) {
                        return function (e) {
                            o.beforeCommand(), document.execCommand(t, !1, e), o.afterCommand(!0)
                        }
                    }(i[r]), this.context.memo("help."+i[r], this.lang.help[i[r]]);
                    this.fontName = this.wrapCommand((function (t) {
                        return o.fontStyling("font-family", f.validFontName(t))
                    })), this.fontSize = this.wrapCommand((function (t) {
                        var e = o.currentStyle()["font-size-unit"];
                        return o.fontStyling("font-size", t+e)
                    })), this.fontSizeUnit = this.wrapCommand((function (t) {
                        var e = o.currentStyle()["font-size"];
                        return o.fontStyling("font-size", e+t)
                    }));
                    for (var s = 1; s <= 6; s++) this["formatH"+s] = function (t) {
                        return function () {
                            o.formatBlock("H"+t)
                        }
                    }(s), this.context.memo("help.formatH"+s, this.lang.help["formatH"+s]);
                    this.insertParagraph = this.wrapCommand((function () {
                        o.typing.insertParagraph(o.editable)
                    })), this.insertOrderedList = this.wrapCommand((function () {
                        o.bullet.insertOrderedList(o.editable)
                    })), this.insertUnorderedList = this.wrapCommand((function () {
                        o.bullet.insertUnorderedList(o.editable)
                    })), this.indent = this.wrapCommand((function () {
                        o.bullet.indent(o.editable)
                    })), this.outdent = this.wrapCommand((function () {
                        o.bullet.outdent(o.editable)
                    })), this.insertNode = this.wrapCommand((function (t) {
                        o.isLimited(e()(t).text().length) || (o.getLastRange().insertNode(t), o.setLastRange(yt.createFromNodeAfter(t).select()))
                    })), this.insertText = this.wrapCommand((function (t) {
                        if (!o.isLimited(t.length)) {
                            var e = o.getLastRange().insertNode(ht.createText(t));
                            o.setLastRange(yt.create(e, ht.nodeLength(e)).select())
                        }
                    })), this.pasteHTML = this.wrapCommand((function (t) {
                        if (!o.isLimited(t.length)) {
                            t = o.context.invoke("codeview.purify", t);
                            var e = o.getLastRange().pasteHTML(t);
                            o.setLastRange(yt.createFromNodeAfter(k.last(e)).select())
                        }
                    })), this.formatBlock = this.wrapCommand((function (t, e) {
                        var n = o.options.callbacks.onApplyCustomStyle;
                        n ? n.call(o, e, o.context, o.onFormatBlock) : o.onFormatBlock(t, e)
                    })), this.insertHorizontalRule = this.wrapCommand((function () {
                        var t = o.getLastRange().insertNode(ht.create("HR"));
                        t.nextSibling && o.setLastRange(yt.create(t.nextSibling, 0).normalize().select())
                    })), this.lineHeight = this.wrapCommand((function (t) {
                        o.style.stylePara(o.getLastRange(), {lineHeight: t})
                    })), this.createLink = this.wrapCommand((function (t) {
                        var n = t.url, i = t.text, r = t.isNewWindow, a = t.checkProtocol,
                            s = t.range || o.getLastRange(), l = i.length-s.toString().length;
                        if (!(l > 0 && o.isLimited(l))) {
                            var c = s.toString() !== i;
                            "string" == typeof n && (n = n.trim()), o.options.onCreateLink ? n = o.options.onCreateLink(n) : a && (n = /^([A-Za-z][A-Za-z0-9+-.]*\:|#|\/)/.test(n) ? n : o.options.defaultProtocol+n);
                            var u = [];
                            if (c) {
                                var d = (s = s.deleteContents()).insertNode(e()("<A>"+i+"</A>")[0]);
                                u.push(d)
                            } else u = o.style.styleNodes(s, {
                                nodeName: "A",
                                expandClosestSibling: !0,
                                onlyPartialContains: !0
                            });
                            e().each(u, (function (t, o) {
                                e()(o).attr("href", n), r ? e()(o).attr("target", "_blank") : e()(o).removeAttr("target")
                            })), o.setLastRange(o.createRangeFromList(u).select())
                        }
                    })), this.color = this.wrapCommand((function (t) {
                        var e = t.foreColor, n = t.backColor;
                        e && document.execCommand("foreColor", !1, e), n && document.execCommand("backColor", !1, n)
                    })), this.foreColor = this.wrapCommand((function (t) {
                        document.execCommand("foreColor", !1, t)
                    })), this.insertTable = this.wrapCommand((function (t) {
                        var e = t.split("x");
                        o.getLastRange().deleteContents().insertNode(o.table.createTable(e[0], e[1], o.options))
                    })), this.removeMedia = this.wrapCommand((function () {
                        var t = e()(o.restoreTarget()).parent();
                        t.closest("figure").length ? t.closest("figure").remove() : t = e()(o.restoreTarget()).detach(), o.context.triggerEvent("media.delete", t, o.$editable)
                    })), this.floatMe = this.wrapCommand((function (t) {
                        var n = e()(o.restoreTarget());
                        n.toggleClass("note-float-left", "left" === t), n.toggleClass("note-float-right", "right" === t), n.css("float", "none" === t ? "" : t)
                    })), this.resize = this.wrapCommand((function (t) {
                        var n = e()(o.restoreTarget());
                        0 === (t = parseFloat(t)) ? n.css("width", "") : n.css({width: 100 * t+"%", height: ""})
                    }))
                }

                var n, o, i;
                return n = t, o = [{
                    key: "initialize", value: function () {
                        var t = this;
                        this.$editable.on("keydown", (function (e) {
                            if (e.keyCode === wt.code.ENTER && t.context.triggerEvent("enter", e), t.context.triggerEvent("keydown", e), t.snapshot = t.history.makeSnapshot(), t.hasKeyShortCut = !1, e.isDefaultPrevented() || (t.options.shortcuts ? t.hasKeyShortCut = t.handleKeyMap(e) : t.preventDefaultEditableShortCuts(e)), t.isLimited(1, e)) {
                                var n = t.getLastRange();
                                if (n.eo-n.so == 0) return !1
                            }
                            t.setLastRange(), t.options.recordEveryKeystroke && !1 === t.hasKeyShortCut && t.history.recordUndo()
                        })).on("keyup", (function (e) {
                            t.setLastRange(), t.context.triggerEvent("keyup", e)
                        })).on("focus", (function (e) {
                            t.setLastRange(), t.context.triggerEvent("focus", e)
                        })).on("blur", (function (e) {
                            t.context.triggerEvent("blur", e)
                        })).on("mousedown", (function (e) {
                            t.context.triggerEvent("mousedown", e)
                        })).on("mouseup", (function (e) {
                            t.setLastRange(), t.history.recordUndo(), t.context.triggerEvent("mouseup", e)
                        })).on("scroll", (function (e) {
                            t.context.triggerEvent("scroll", e)
                        })).on("paste", (function (e) {
                            t.setLastRange(), t.context.triggerEvent("paste", e)
                        })).on("input", (function () {
                            t.isLimited(0) && t.snapshot && t.history.applySnapshot(t.snapshot)
                        })), this.$editable.attr("spellcheck", this.options.spellCheck), this.$editable.attr("autocorrect", this.options.spellCheck), this.options.disableGrammar && this.$editable.attr("data-gramm", !1), this.$editable.html(ht.html(this.$note) || ht.emptyPara), this.$editable.on(f.inputEventName, m.debounce((function () {
                            t.context.triggerEvent("change", t.$editable.html(), t.$editable)
                        }), 10)), this.$editable.on("focusin", (function (e) {
                            t.context.triggerEvent("focusin", e)
                        })).on("focusout", (function (e) {
                            t.context.triggerEvent("focusout", e)
                        })), this.options.airMode ? this.options.overrideContextMenu && this.$editor.on("contextmenu", (function (e) {
                            return t.context.triggerEvent("contextmenu", e), !1
                        })) : (this.options.width && this.$editor.outerWidth(this.options.width), this.options.height && this.$editable.outerHeight(this.options.height), this.options.maxHeight && this.$editable.css("max-height", this.options.maxHeight), this.options.minHeight && this.$editable.css("min-height", this.options.minHeight)), this.history.recordUndo(), this.setLastRange()
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$editable.off()
                    }
                }, {
                    key: "handleKeyMap", value: function (t) {
                        var e = this.options.keyMap[f.isMac ? "mac" : "pc"], n = [];
                        t.metaKey && n.push("CMD"), t.ctrlKey && !t.altKey && n.push("CTRL"), t.shiftKey && n.push("SHIFT");
                        var o = wt.nameFromCode[t.keyCode];
                        o && n.push(o);
                        var i = e[n.join("+")];
                        if ("TAB" !== o || this.options.tabDisable) if (i) {
                            if (!1 !== this.context.invoke(i)) return t.preventDefault(), !0
                        } else wt.isEdit(t.keyCode) && this.afterCommand(); else this.afterCommand();
                        return !1
                    }
                }, {
                    key: "preventDefaultEditableShortCuts", value: function (t) {
                        (t.ctrlKey || t.metaKey) && k.contains([66, 73, 85], t.keyCode) && t.preventDefault()
                    }
                }, {
                    key: "isLimited", value: function (t, e) {
                        return t = t || 0, (void 0 === e || !(wt.isMove(e.keyCode) || wt.isNavigation(e.keyCode) || e.ctrlKey || e.metaKey || k.contains([wt.code.BACKSPACE, wt.code.DELETE], e.keyCode))) && this.options.maxTextLength > 0 && this.$editable.text().length+t > this.options.maxTextLength
                    }
                }, {
                    key: "createRange", value: function () {
                        return this.focus(), this.setLastRange(), this.getLastRange()
                    }
                }, {
                    key: "createRangeFromList", value: function (t) {
                        var e = yt.createFromNodeBefore(k.head(t)).getStartPoint(),
                            n = yt.createFromNodeAfter(k.last(t)).getEndPoint();
                        return yt.create(e.node, e.offset, n.node, n.offset)
                    }
                }, {
                    key: "setLastRange", value: function (t) {
                        t ? this.lastRange = t : (this.lastRange = yt.create(this.editable), 0 === e()(this.lastRange.sc).closest(".note-editable").length && (this.lastRange = yt.createFromBodyElement(this.editable)))
                    }
                }, {
                    key: "getLastRange", value: function () {
                        return this.lastRange || this.setLastRange(), this.lastRange
                    }
                }, {
                    key: "saveRange", value: function (t) {
                        t && this.getLastRange().collapse().select()
                    }
                }, {
                    key: "restoreRange", value: function () {
                        this.lastRange && (this.lastRange.select(), this.focus())
                    }
                }, {
                    key: "saveTarget", value: function (t) {
                        this.$editable.data("target", t)
                    }
                }, {
                    key: "clearTarget", value: function () {
                        this.$editable.removeData("target")
                    }
                }, {
                    key: "restoreTarget", value: function () {
                        return this.$editable.data("target")
                    }
                }, {
                    key: "currentStyle", value: function () {
                        var t = yt.create();
                        return t && (t = t.normalize()), t ? this.style.current(t) : this.style.fromNode(this.$editable)
                    }
                }, {
                    key: "styleFromNode", value: function (t) {
                        return this.style.fromNode(t)
                    }
                }, {
                    key: "undo", value: function () {
                        this.context.triggerEvent("before.command", this.$editable.html()), this.history.undo(), this.context.triggerEvent("change", this.$editable.html(), this.$editable)
                    }
                }, {
                    key: "commit", value: function () {
                        this.context.triggerEvent("before.command", this.$editable.html()), this.history.commit(), this.context.triggerEvent("change", this.$editable.html(), this.$editable)
                    }
                }, {
                    key: "redo", value: function () {
                        this.context.triggerEvent("before.command", this.$editable.html()), this.history.redo(), this.context.triggerEvent("change", this.$editable.html(), this.$editable)
                    }
                }, {
                    key: "beforeCommand", value: function () {
                        this.context.triggerEvent("before.command", this.$editable.html()), document.execCommand("styleWithCSS", !1, this.options.styleWithCSS), this.focus()
                    }
                }, {
                    key: "afterCommand", value: function (t) {
                        this.normalizeContent(), this.history.recordUndo(), t || this.context.triggerEvent("change", this.$editable.html(), this.$editable)
                    }
                }, {
                    key: "tab", value: function () {
                        var t = this.getLastRange();
                        if (t.isCollapsed() && t.isOnCell()) this.table.tab(t); else {
                            if (0 === this.options.tabSize) return !1;
                            this.isLimited(this.options.tabSize) || (this.beforeCommand(), this.typing.insertTab(t, this.options.tabSize), this.afterCommand())
                        }
                    }
                }, {
                    key: "untab", value: function () {
                        var t = this.getLastRange();
                        if (t.isCollapsed() && t.isOnCell()) this.table.tab(t, !0); else if (0 === this.options.tabSize) return !1
                    }
                }, {
                    key: "wrapCommand", value: function (t) {
                        return function () {
                            this.beforeCommand(), t.apply(this, arguments), this.afterCommand()
                        }
                    }
                }, {
                    key: "insertImage", value: function (t, n) {
                        var o, i = this;
                        return (o = t, e().Deferred((function (t) {
                            var n = e()("<img>");
                            n.one("load", (function () {
                                n.off("error abort"), t.resolve(n)
                            })).one("error abort", (function () {
                                n.off("load").detach(), t.reject(n)
                            })).css({display: "none"}).appendTo(document.body).attr("src", o)
                        })).promise()).then((function (t) {
                            i.beforeCommand(), "function" == typeof n ? n(t) : ("string" == typeof n && t.attr("data-filename", n), t.css("width", Math.min(i.$editable.width(), t.width()))), t.show(), i.getLastRange().insertNode(t[0]), i.setLastRange(yt.createFromNodeAfter(t[0]).select()), i.afterCommand()
                        })).fail((function (t) {
                            i.context.triggerEvent("image.upload.error", t)
                        }))
                    }
                }, {
                    key: "insertImagesAsDataURL", value: function (t) {
                        var n = this;
                        e().each(t, (function (t, o) {
                            var i = o.name;
                            n.options.maximumImageFileSize && n.options.maximumImageFileSize < o.size ? n.context.triggerEvent("image.upload.error", n.lang.image.maximumFileSizeError) : function (t) {
                                return e().Deferred((function (n) {
                                    e().extend(new FileReader, {
                                        onload: function (t) {
                                            var e = t.target.result;
                                            n.resolve(e)
                                        }, onerror: function (t) {
                                            n.reject(t)
                                        }
                                    }).readAsDataURL(t)
                                })).promise()
                            }(o).then((function (t) {
                                return n.insertImage(t, i)
                            })).fail((function () {
                                n.context.triggerEvent("image.upload.error")
                            }))
                        }))
                    }
                }, {
                    key: "insertImagesOrCallback", value: function (t) {
                        this.options.callbacks.onImageUpload ? this.context.triggerEvent("image.upload", t) : this.insertImagesAsDataURL(t)
                    }
                }, {
                    key: "getSelectedText", value: function () {
                        var t = this.getLastRange();
                        return t.isOnAnchor() && (t = yt.createFromNode(ht.ancestor(t.sc, ht.isAnchor))), t.toString()
                    }
                }, {
                    key: "onFormatBlock", value: function (t, n) {
                        if (document.execCommand("FormatBlock", !1, f.isMSIE ? "<"+t+">" : t), n && n.length && (n[0].tagName.toUpperCase() !== t.toUpperCase() && (n = n.find(t)), n && n.length)) {
                            var o = this.createRange(), i = e()([o.sc, o.ec]).closest(t);
                            i.removeClass();
                            var r = n[0].className || "";
                            r && i.addClass(r)
                        }
                    }
                }, {
                    key: "formatPara", value: function () {
                        this.formatBlock("P")
                    }
                }, {
                    key: "fontStyling", value: function (t, n) {
                        var o = this.getLastRange();
                        if ("" !== o) {
                            var i = this.style.styleNodes(o);
                            if (this.$editor.find(".note-status-output").html(""), e()(i).css(t, n), o.isCollapsed()) {
                                var r = k.head(i);
                                r && !ht.nodeLength(r) && (r.innerHTML = ht.ZERO_WIDTH_NBSP_CHAR, yt.createFromNode(r.firstChild).select(), this.setLastRange(), this.$editable.data("bogus", r))
                            } else this.setLastRange(this.createRangeFromList(i).select())
                        } else {
                            var a = e().now();
                            this.$editor.find(".note-status-output").html('<div id="note-status-output-'+a+'" class="alert alert-info">'+this.lang.output.noSelection+"</div>"), setTimeout((function () {
                                e()("#note-status-output-"+a).remove()
                            }), 5e3)
                        }
                    }
                }, {
                    key: "unlink", value: function () {
                        var t = this.getLastRange();
                        if (t.isOnAnchor()) {
                            var e = ht.ancestor(t.sc, ht.isAnchor);
                            (t = yt.createFromNode(e)).select(), this.setLastRange(), this.beforeCommand(), document.execCommand("unlink"), this.afterCommand()
                        }
                    }
                }, {
                    key: "getLinkInfo", value: function () {
                        var t = this.getLastRange().expand(ht.isAnchor), n = e()(k.head(t.nodes(ht.isAnchor))),
                            o = {range: t, text: t.toString(), url: n.length ? n.attr("href") : ""};
                        return n.length && (o.isNewWindow = "_blank" === n.attr("target")), o
                    }
                }, {
                    key: "addRow", value: function (t) {
                        var e = this.getLastRange(this.$editable);
                        e.isCollapsed() && e.isOnCell() && (this.beforeCommand(), this.table.addRow(e, t), this.afterCommand())
                    }
                }, {
                    key: "addCol", value: function (t) {
                        var e = this.getLastRange(this.$editable);
                        e.isCollapsed() && e.isOnCell() && (this.beforeCommand(), this.table.addCol(e, t), this.afterCommand())
                    }
                }, {
                    key: "deleteRow", value: function () {
                        var t = this.getLastRange(this.$editable);
                        t.isCollapsed() && t.isOnCell() && (this.beforeCommand(), this.table.deleteRow(t), this.afterCommand())
                    }
                }, {
                    key: "deleteCol", value: function () {
                        var t = this.getLastRange(this.$editable);
                        t.isCollapsed() && t.isOnCell() && (this.beforeCommand(), this.table.deleteCol(t), this.afterCommand())
                    }
                }, {
                    key: "deleteTable", value: function () {
                        var t = this.getLastRange(this.$editable);
                        t.isCollapsed() && t.isOnCell() && (this.beforeCommand(), this.table.deleteTable(t), this.afterCommand())
                    }
                }, {
                    key: "resizeTo", value: function (t, e, n) {
                        var o;
                        if (n) {
                            var i = t.y / t.x, r = e.data("ratio");
                            o = {width: r > i ? t.x : t.y / r, height: r > i ? t.x * r : t.y}
                        } else o = {width: t.x, height: t.y};
                        e.css(o)
                    }
                }, {
                    key: "hasFocus", value: function () {
                        return this.$editable.is(":focus")
                    }
                }, {
                    key: "focus", value: function () {
                        this.hasFocus() || this.$editable.focus()
                    }
                }, {
                    key: "isEmpty", value: function () {
                        return ht.isEmpty(this.$editable[0]) || ht.emptyPara === this.$editable.html()
                    }
                }, {
                    key: "empty", value: function () {
                        this.context.invoke("code", ht.emptyPara)
                    }
                }, {
                    key: "normalizeContent", value: function () {
                        this.$editable[0].normalize()
                    }
                }], o && At(n.prototype, o), i && At(n, i), t
            }();

            function Dt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Ht = function () {
                function t(e) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = e, this.$editable = e.layoutInfo.editable
                }

                var e, n, o;
                return e = t, (n = [{
                    key: "initialize", value: function () {
                        this.$editable.on("paste", this.pasteByEvent.bind(this))
                    }
                }, {
                    key: "pasteByEvent", value: function (t) {
                        var e = this, n = t.originalEvent.clipboardData;
                        if (n && n.items && n.items.length) {
                            var o = n.items.length > 1 ? n.items[1] : k.head(n.items);
                            "file" === o.kind && -1 !== o.type.indexOf("image/") ? (this.context.invoke("editor.insertImagesOrCallback", [o.getAsFile()]), t.preventDefault()) : "string" === o.kind && this.context.invoke("editor.isLimited", n.getData("Text").length) && t.preventDefault()
                        } else if (window.clipboardData) {
                            var i = window.clipboardData.getData("text");
                            this.context.invoke("editor.isLimited", i.length) && t.preventDefault()
                        }
                        setTimeout((function () {
                            e.context.invoke("editor.afterCommand")
                        }), 10)
                    }
                }]) && Dt(e.prototype, n), o && Dt(e, o), t
            }();

            function zt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Bt = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.$eventListener = e()(document), this.$editor = n.layoutInfo.editor, this.$editable = n.layoutInfo.editable, this.options = n.options, this.lang = this.options.langInfo, this.documentEventHandlers = {}, this.$dropzone = e()(['<div class="note-dropzone">', '<div class="note-dropzone-message"></div>', "</div>"].join("")).prependTo(this.$editor)
                }

                var n, o, i;
                return n = t, o = [{
                    key: "initialize", value: function () {
                        this.options.disableDragAndDrop ? (this.documentEventHandlers.onDrop = function (t) {
                            t.preventDefault()
                        }, this.$eventListener = this.$dropzone, this.$eventListener.on("drop", this.documentEventHandlers.onDrop)) : this.attachDragAndDropEvent()
                    }
                }, {
                    key: "attachDragAndDropEvent", value: function () {
                        var t = this, n = e()(), o = this.$dropzone.find(".note-dropzone-message");
                        this.documentEventHandlers.onDragenter = function (e) {
                            var i = t.context.invoke("codeview.isActivated"),
                                r = t.$editor.width() > 0 && t.$editor.height() > 0;
                            i || n.length || !r || (t.$editor.addClass("dragover"), t.$dropzone.width(t.$editor.width()), t.$dropzone.height(t.$editor.height()), o.text(t.lang.image.dragImageHere)), n = n.add(e.target)
                        }, this.documentEventHandlers.onDragleave = function (o) {
                            (n = n.not(o.target)).length && "BODY" !== o.target.nodeName || (n = e()(), t.$editor.removeClass("dragover"))
                        }, this.documentEventHandlers.onDrop = function () {
                            n = e()(), t.$editor.removeClass("dragover")
                        }, this.$eventListener.on("dragenter", this.documentEventHandlers.onDragenter).on("dragleave", this.documentEventHandlers.onDragleave).on("drop", this.documentEventHandlers.onDrop), this.$dropzone.on("dragenter", (function () {
                            t.$dropzone.addClass("hover"), o.text(t.lang.image.dropImage)
                        })).on("dragleave", (function () {
                            t.$dropzone.removeClass("hover"), o.text(t.lang.image.dragImageHere)
                        })), this.$dropzone.on("drop", (function (n) {
                            var o = n.originalEvent.dataTransfer;
                            n.preventDefault(), o && o.files && o.files.length ? (t.$editable.focus(), t.context.invoke("editor.insertImagesOrCallback", o.files)) : e().each(o.types, (function (n, i) {
                                if (!(i.toLowerCase().indexOf("_moz_") > -1)) {
                                    var r = o.getData(i);
                                    i.toLowerCase().indexOf("text") > -1 ? t.context.invoke("editor.pasteHTML", r) : e()(r).each((function (e, n) {
                                        t.context.invoke("editor.insertNode", n)
                                    }))
                                }
                            }))
                        })).on("dragover", !1)
                    }
                }, {
                    key: "destroy", value: function () {
                        var t = this;
                        Object.keys(this.documentEventHandlers).forEach((function (e) {
                            t.$eventListener.off(e.substr(2).toLowerCase(), t.documentEventHandlers[e])
                        })), this.documentEventHandlers = {}
                    }
                }], o && zt(n.prototype, o), i && zt(n, i), t
            }();

            function Mt(t, e) {
                var n = "undefined" != typeof Symbol && t[Symbol.iterator] || t["@@iterator"];
                if (!n) {
                    if (Array.isArray(t) || (n = function (t, e) {
                        if (!t) return;
                        if ("string" == typeof t) return Ot(t, e);
                        var n = Object.prototype.toString.call(t).slice(8, -1);
                        "Object" === n && t.constructor && (n = t.constructor.name);
                        if ("Map" === n || "Set" === n) return Array.from(t);
                        if ("Arguments" === n || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return Ot(t, e)
                    }(t)) || e && t && "number" == typeof t.length) {
                        n && (t = n);
                        var o = 0, i = function () {
                        };
                        return {
                            s: i, n: function () {
                                return o >= t.length ? {done: !0} : {done: !1, value: t[o++]}
                            }, e: function (t) {
                                throw t
                            }, f: i
                        }
                    }
                    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")
                }
                var r, a = !0, s = !1;
                return {
                    s: function () {
                        n = n.call(t)
                    }, n: function () {
                        var t = n.next();
                        return a = t.done, t
                    }, e: function (t) {
                        s = !0, r = t
                    }, f: function () {
                        try {
                            a || null == n.return || n.return()
                        } finally {
                            if (s) throw r
                        }
                    }
                }
            }

            function Ot(t, e) {
                (null == e || e > t.length) && (e = t.length);
                for (var n = 0, o = new Array(e); n < e; n++) o[n] = t[n];
                return o
            }

            function Ut(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var jt = function () {
                function t(e) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = e, this.$editor = e.layoutInfo.editor, this.$editable = e.layoutInfo.editable, this.$codable = e.layoutInfo.codable, this.options = e.options, this.CodeMirrorConstructor = window.CodeMirror, this.options.codemirror.CodeMirrorConstructor && (this.CodeMirrorConstructor = this.options.codemirror.CodeMirrorConstructor)
                }

                var e, n, o;
                return e = t, n = [{
                    key: "sync", value: function (t) {
                        var e = this.isActivated(), n = this.CodeMirrorConstructor;
                        e && (t ? n ? this.$codable.data("cmEditor").getDoc().setValue(t) : this.$codable.val(t) : n && this.$codable.data("cmEditor").save())
                    }
                }, {
                    key: "initialize", value: function () {
                        var t = this;
                        this.$codable.on("keyup", (function (e) {
                            e.keyCode === wt.code.ESCAPE && t.deactivate()
                        }))
                    }
                }, {
                    key: "isActivated", value: function () {
                        return this.$editor.hasClass("codeview")
                    }
                }, {
                    key: "toggle", value: function () {
                        this.isActivated() ? this.deactivate() : this.activate(), this.context.triggerEvent("codeview.toggled")
                    }
                }, {
                    key: "purify", value: function (t) {
                        if (this.options.codeviewFilter && (t = t.replace(this.options.codeviewFilterRegex, ""), this.options.codeviewIframeFilter)) {
                            var e = this.options.codeviewIframeWhitelistSrc.concat(this.options.codeviewIframeWhitelistSrcBase);
                            t = t.replace(/(<iframe.*?>.*?(?:<\/iframe>)?)/gi, (function (t) {
                                if (/<.+src(?==?('|"|\s)?)[\s\S]+src(?=('|"|\s)?)[^>]*?>/i.test(t)) return "";
                                var n, o = Mt(e);
                                try {
                                    for (o.s(); !(n = o.n()).done;) {
                                        var i = n.value;
                                        if (new RegExp('src="(https?:)?//'+i.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&")+'/(.+)"').test(t)) return t
                                    }
                                } catch (t) {
                                    o.e(t)
                                } finally {
                                    o.f()
                                }
                                return ""
                            }))
                        }
                        return t
                    }
                }, {
                    key: "activate", value: function () {
                        var t = this, e = this.CodeMirrorConstructor;
                        if (this.$codable.val(ht.html(this.$editable, this.options.prettifyHtml)), this.$codable.height(this.$editable.height()), this.context.invoke("toolbar.updateCodeview", !0), this.context.invoke("airPopover.updateCodeview", !0), this.$editor.addClass("codeview"), this.$codable.focus(), e) {
                            var n = e.fromTextArea(this.$codable[0], this.options.codemirror);
                            if (this.options.codemirror.tern) {
                                var o = new e.TernServer(this.options.codemirror.tern);
                                n.ternServer = o, n.on("cursorActivity", (function (t) {
                                    o.updateArgHints(t)
                                }))
                            }
                            n.on("blur", (function (e) {
                                t.context.triggerEvent("blur.codeview", n.getValue(), e)
                            })), n.on("change", (function () {
                                t.context.triggerEvent("change.codeview", n.getValue(), n)
                            })), n.setSize(null, this.$editable.outerHeight()), this.$codable.data("cmEditor", n)
                        } else this.$codable.on("blur", (function (e) {
                            t.context.triggerEvent("blur.codeview", t.$codable.val(), e)
                        })), this.$codable.on("input", (function () {
                            t.context.triggerEvent("change.codeview", t.$codable.val(), t.$codable)
                        }))
                    }
                }, {
                    key: "deactivate", value: function () {
                        if (this.CodeMirrorConstructor) {
                            var t = this.$codable.data("cmEditor");
                            this.$codable.val(t.getValue()), t.toTextArea()
                        }
                        var e = this.purify(ht.value(this.$codable, this.options.prettifyHtml) || ht.emptyPara),
                            n = this.$editable.html() !== e;
                        this.$editable.html(e), this.$editable.height(this.options.height ? this.$codable.height() : "auto"), this.$editor.removeClass("codeview"), n && this.context.triggerEvent("change", this.$editable.html(), this.$editable), this.$editable.focus(), this.context.invoke("toolbar.updateCodeview", !1), this.context.invoke("airPopover.updateCodeview", !1)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.isActivated() && this.deactivate()
                    }
                }], n && Ut(e.prototype, n), o && Ut(e, o), t
            }();

            function Wt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Kt = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.$document = e()(document), this.$statusbar = n.layoutInfo.statusbar, this.$editable = n.layoutInfo.editable, this.$codable = n.layoutInfo.codable, this.options = n.options
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "initialize", value: function () {
                        var t = this;
                        this.options.airMode || this.options.disableResizeEditor ? this.destroy() : this.$statusbar.on("mousedown", (function (e) {
                            e.preventDefault(), e.stopPropagation();
                            var n = t.$editable.offset().top-t.$document.scrollTop(),
                                o = t.$codable.offset().top-t.$document.scrollTop(), i = function (e) {
                                    var i = e.clientY-(n+24), r = e.clientY-(o+24);
                                    i = t.options.minheight > 0 ? Math.max(i, t.options.minheight) : i, i = t.options.maxHeight > 0 ? Math.min(i, t.options.maxHeight) : i, r = t.options.minheight > 0 ? Math.max(r, t.options.minheight) : r, r = t.options.maxHeight > 0 ? Math.min(r, t.options.maxHeight) : r, t.$editable.height(i), t.$codable.height(r)
                                };
                            t.$document.on("mousemove", i).one("mouseup", (function () {
                                t.$document.off("mousemove", i)
                            }))
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$statusbar.off(), this.$statusbar.addClass("locked")
                    }
                }]) && Wt(n.prototype, o), i && Wt(n, i), t
            }();

            function Vt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var qt = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.$editor = n.layoutInfo.editor, this.$toolbar = n.layoutInfo.toolbar, this.$editable = n.layoutInfo.editable, this.$codable = n.layoutInfo.codable, this.$window = e()(window), this.$scrollbar = e()("html, body"), this.scrollbarClassName = "note-fullscreen-body", this.onResize = function () {
                        o.resizeTo({h: o.$window.height()-o.$toolbar.outerHeight()})
                    }
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "resizeTo", value: function (t) {
                        this.$editable.css("height", t.h), this.$codable.css("height", t.h), this.$codable.data("cmeditor") && this.$codable.data("cmeditor").setsize(null, t.h)
                    }
                }, {
                    key: "toggle", value: function () {
                        this.$editor.toggleClass("fullscreen");
                        var t = this.isFullscreen();
                        this.$scrollbar.toggleClass(this.scrollbarClassName, t), t ? (this.$editable.data("orgHeight", this.$editable.css("height")), this.$editable.data("orgMaxHeight", this.$editable.css("maxHeight")), this.$editable.css("maxHeight", ""), this.$window.on("resize", this.onResize).trigger("resize")) : (this.$window.off("resize", this.onResize), this.resizeTo({h: this.$editable.data("orgHeight")}), this.$editable.css("maxHeight", this.$editable.css("orgMaxHeight"))), this.context.invoke("toolbar.updateFullscreen", t)
                    }
                }, {
                    key: "isFullscreen", value: function () {
                        return this.$editor.hasClass("fullscreen")
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$scrollbar.removeClass(this.scrollbarClassName)
                    }
                }]) && Vt(n.prototype, o), i && Vt(n, i), t
            }();

            function _t(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Gt = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.$document = e()(document), this.$editingArea = n.layoutInfo.editingArea, this.options = n.options, this.lang = this.options.langInfo, this.events = {
                        "summernote.mousedown": function (t, e) {
                            o.update(e.target, e) && e.preventDefault()
                        }, "summernote.keyup summernote.scroll summernote.change summernote.dialog.shown": function () {
                            o.update()
                        }, "summernote.disable summernote.blur": function () {
                            o.hide()
                        }, "summernote.codeview.toggled": function () {
                            o.update()
                        }
                    }
                }

                var n, o, i;
                return n = t, o = [{
                    key: "initialize", value: function () {
                        var t = this;
                        this.$handle = e()(['<div class="note-handle">', '<div class="note-control-selection">', '<div class="note-control-selection-bg"></div>', '<div class="note-control-holder note-control-nw"></div>', '<div class="note-control-holder note-control-ne"></div>', '<div class="note-control-holder note-control-sw"></div>', '<div class="', this.options.disableResizeImage ? "note-control-holder" : "note-control-sizing", ' note-control-se"></div>', this.options.disableResizeImage ? "" : '<div class="note-control-selection-info"></div>', "</div>", "</div>"].join("")).prependTo(this.$editingArea), this.$handle.on("mousedown", (function (e) {
                            if (ht.isControlSizing(e.target)) {
                                e.preventDefault(), e.stopPropagation();
                                var n = t.$handle.find(".note-control-selection").data("target"), o = n.offset(),
                                    i = t.$document.scrollTop(), r = function (e) {
                                        t.context.invoke("editor.resizeTo", {
                                            x: e.clientX-o.left,
                                            y: e.clientY-(o.top-i)
                                        }, n, !e.shiftKey), t.update(n[0], e)
                                    };
                                t.$document.on("mousemove", r).one("mouseup", (function (e) {
                                    e.preventDefault(), t.$document.off("mousemove", r), t.context.invoke("editor.afterCommand")
                                })), n.data("ratio") || n.data("ratio", n.height() / n.width())
                            }
                        })), this.$handle.on("wheel", (function (e) {
                            e.preventDefault(), t.update()
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$handle.remove()
                    }
                }, {
                    key: "update", value: function (t, n) {
                        if (this.context.isDisabled()) return !1;
                        var o = ht.isImg(t), i = this.$handle.find(".note-control-selection");
                        if (this.context.invoke("imagePopover.update", t, n), o) {
                            var r = e()(t), a = r.position(), s = {
                                left: a.left+parseInt(r.css("marginLeft"), 10),
                                top: a.top+parseInt(r.css("marginTop"), 10)
                            }, l = {w: r.outerWidth(!1), h: r.outerHeight(!1)};
                            i.css({
                                display: "block",
                                left: s.left,
                                top: s.top,
                                width: l.w,
                                height: l.h
                            }).data("target", r);
                            var c = new Image;
                            c.src = r.attr("src");
                            var u = l.w+"x"+l.h+" ("+this.lang.image.original+": "+c.width+"x"+c.height+")";
                            i.find(".note-control-selection-info").text(u), this.context.invoke("editor.saveTarget", t)
                        } else this.hide();
                        return o
                    }
                }, {
                    key: "hide", value: function () {
                        this.context.invoke("editor.clearTarget"), this.$handle.children().hide()
                    }
                }], o && _t(n.prototype, o), i && _t(n, i), t
            }();

            function Yt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Zt = /^([A-Za-z][A-Za-z0-9+-.]*\:[\/]{2}|tel:|mailto:[A-Z0-9._%+-]+@|xmpp:[A-Z0-9._%+-]+@)?(www\.)?(.+)$/i,
                Qt = function () {
                    function t(e) {
                        var n = this;
                        !function (t, e) {
                            if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                        }(this, t), this.context = e, this.options = e.options, this.events = {
                            "summernote.keyup": function (t, e) {
                                e.isDefaultPrevented() || n.handleKeyup(e)
                            }, "summernote.keydown": function (t, e) {
                                n.handleKeydown(e)
                            }
                        }
                    }

                    var n, o, i;
                    return n = t, (o = [{
                        key: "initialize", value: function () {
                            this.lastWordRange = null
                        }
                    }, {
                        key: "destroy", value: function () {
                            this.lastWordRange = null
                        }
                    }, {
                        key: "replace", value: function () {
                            if (this.lastWordRange) {
                                var t = this.lastWordRange.toString(), n = t.match(Zt);
                                if (n && (n[1] || n[2])) {
                                    var o = n[1] ? t : "http://"+t,
                                        i = this.options.showDomainOnlyForAutolink ? t.replace(/^(?:https?:\/\/)?(?:tel?:?)?(?:mailto?:?)?(?:xmpp?:?)?(?:www\.)?/i, "").split("/")[0] : t,
                                        r = e()("<a></a>").html(i).attr("href", o)[0];
                                    this.context.options.linkTargetBlank && e()(r).attr("target", "_blank"), this.lastWordRange.insertNode(r), this.lastWordRange = null, this.context.invoke("editor.focus")
                                }
                            }
                        }
                    }, {
                        key: "handleKeydown", value: function (t) {
                            if (k.contains([wt.code.ENTER, wt.code.SPACE], t.keyCode)) {
                                var e = this.context.invoke("editor.createRange").getWordRange();
                                this.lastWordRange = e
                            }
                        }
                    }, {
                        key: "handleKeyup", value: function (t) {
                            k.contains([wt.code.ENTER, wt.code.SPACE], t.keyCode) && this.replace()
                        }
                    }]) && Yt(n.prototype, o), i && Yt(n, i), t
                }();

            function Xt(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Jt = function () {
                function t(e) {
                    var n = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.$note = e.layoutInfo.note, this.events = {
                        "summernote.change": function () {
                            n.$note.val(e.invoke("code"))
                        }
                    }
                }

                var e, n, o;
                return e = t, (n = [{
                    key: "shouldInitialize", value: function () {
                        return ht.isTextarea(this.$note[0])
                    }
                }]) && Xt(e.prototype, n), o && Xt(e, o), t
            }();

            function te(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var ee = function () {
                function t(e) {
                    var n = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = e, this.options = e.options.replace || {}, this.keys = [wt.code.ENTER, wt.code.SPACE, wt.code.PERIOD, wt.code.COMMA, wt.code.SEMICOLON, wt.code.SLASH], this.previousKeydownCode = null, this.events = {
                        "summernote.keyup": function (t, e) {
                            e.isDefaultPrevented() || n.handleKeyup(e)
                        }, "summernote.keydown": function (t, e) {
                            n.handleKeydown(e)
                        }
                    }
                }

                var e, n, o;
                return e = t, (n = [{
                    key: "shouldInitialize", value: function () {
                        return !!this.options.match
                    }
                }, {
                    key: "initialize", value: function () {
                        this.lastWord = null
                    }
                }, {
                    key: "destroy", value: function () {
                        this.lastWord = null
                    }
                }, {
                    key: "replace", value: function () {
                        if (this.lastWord) {
                            var t = this, e = this.lastWord.toString();
                            this.options.match(e, (function (e) {
                                if (e) {
                                    var n = "";
                                    if ("string" == typeof e ? n = ht.createText(e) : e instanceof jQuery ? n = e[0] : e instanceof Node && (n = e), !n) return;
                                    t.lastWord.insertNode(n), t.lastWord = null, t.context.invoke("editor.focus")
                                }
                            }))
                        }
                    }
                }, {
                    key: "handleKeydown", value: function (t) {
                        if (this.previousKeydownCode && k.contains(this.keys, this.previousKeydownCode)) this.previousKeydownCode = t.keyCode; else {
                            if (k.contains(this.keys, t.keyCode)) {
                                var e = this.context.invoke("editor.createRange").getWordRange();
                                this.lastWord = e
                            }
                            this.previousKeydownCode = t.keyCode
                        }
                    }
                }, {
                    key: "handleKeyup", value: function (t) {
                        k.contains(this.keys, t.keyCode) && this.replace()
                    }
                }]) && te(e.prototype, n), o && te(e, o), t
            }();

            function ne(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var oe = function () {
                function t(e) {
                    var n = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = e, this.$editingArea = e.layoutInfo.editingArea, this.options = e.options, !0 === this.options.inheritPlaceholder && (this.options.placeholder = this.context.$note.attr("placeholder") || this.options.placeholder), this.events = {
                        "summernote.init summernote.change": function () {
                            n.update()
                        }, "summernote.codeview.toggled": function () {
                            n.update()
                        }
                    }
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "shouldInitialize", value: function () {
                        return !!this.options.placeholder
                    }
                }, {
                    key: "initialize", value: function () {
                        var t = this;
                        this.$placeholder = e()('<div class="note-placeholder"></div>'), this.$placeholder.on("click", (function () {
                            t.context.invoke("focus")
                        })).html(this.options.placeholder).prependTo(this.$editingArea), this.update()
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$placeholder.remove()
                    }
                }, {
                    key: "update", value: function () {
                        var t = !this.context.invoke("codeview.isActivated") && this.context.invoke("editor.isEmpty");
                        this.$placeholder.toggle(t)
                    }
                }]) && ne(n.prototype, o), i && ne(n, i), t
            }();

            function ie(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var re = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.ui = e().summernote.ui, this.context = n, this.$toolbar = n.layoutInfo.toolbar, this.options = n.options, this.lang = this.options.langInfo, this.invertedKeyMap = m.invertObject(this.options.keyMap[f.isMac ? "mac" : "pc"])
                }

                var n, o, i;
                return n = t, o = [{
                    key: "representShortcut", value: function (t) {
                        var e = this.invertedKeyMap[t];
                        return this.options.shortcuts && e ? (f.isMac && (e = e.replace("CMD", "⌘").replace("SHIFT", "⇧")), " ("+(e = e.replace("BACKSLASH", "\\").replace("SLASH", "/").replace("LEFTBRACKET", "[").replace("RIGHTBRACKET", "]"))+")") : ""
                    }
                }, {
                    key: "button", value: function (t) {
                        return !this.options.tooltip && t.tooltip && delete t.tooltip, t.container = this.options.container, this.ui.button(t)
                    }
                }, {
                    key: "initialize", value: function () {
                        this.addToolbarButtons(), this.addImagePopoverButtons(), this.addLinkPopoverButtons(), this.addTablePopoverButtons(), this.fontInstalledMap = {}
                    }
                }, {
                    key: "destroy", value: function () {
                        delete this.fontInstalledMap
                    }
                }, {
                    key: "isFontInstalled", value: function (t) {
                        return Object.prototype.hasOwnProperty.call(this.fontInstalledMap, t) || (this.fontInstalledMap[t] = f.isFontInstalled(t) || k.contains(this.options.fontNamesIgnoreCheck, t)), this.fontInstalledMap[t]
                    }
                }, {
                    key: "isFontDeservedToAdd", value: function (t) {
                        return "" !== (t = t.toLowerCase()) && this.isFontInstalled(t) && -1 === f.genericFontFamilies.indexOf(t)
                    }
                }, {
                    key: "colorPalette", value: function (t, n, o, i) {
                        var r = this;
                        return this.ui.buttonGroup({
                            className: "note-color "+t,
                            children: [this.button({
                                className: "note-current-color-button",
                                contents: this.ui.icon(this.options.icons.font+" note-recent-color"),
                                tooltip: n,
                                click: function (t) {
                                    var n = e()(t.currentTarget);
                                    o && i ? r.context.invoke("editor.color", {
                                        backColor: n.attr("data-backColor"),
                                        foreColor: n.attr("data-foreColor")
                                    }) : o ? r.context.invoke("editor.color", {backColor: n.attr("data-backColor")}) : i && r.context.invoke("editor.color", {foreColor: n.attr("data-foreColor")})
                                },
                                callback: function (t) {
                                    var e = t.find(".note-recent-color");
                                    o && (e.css("background-color", r.options.colorButton.backColor), t.attr("data-backColor", r.options.colorButton.backColor)), i ? (e.css("color", r.options.colorButton.foreColor), t.attr("data-foreColor", r.options.colorButton.foreColor)) : e.css("color", "transparent")
                                }
                            }), this.button({
                                className: "dropdown-toggle",
                                contents: this.ui.dropdownButtonContents("", this.options),
                                tooltip: this.lang.color.more,
                                data: {toggle: "dropdown"}
                            }), this.ui.dropdown({
                                items: (o ? ['<div class="note-palette">', '<div class="note-palette-title">'+this.lang.color.background+"</div>", "<div>", '<button type="button" class="note-color-reset btn btn-light btn-default" data-event="backColor" data-value="transparent">', this.lang.color.transparent, "</button>", "</div>", '<div class="note-holder" data-event="backColor">\x3c!-- back colors --\x3e</div>', "<div>", '<button type="button" class="note-color-select btn btn-light btn-default" data-event="openPalette" data-value="backColorPicker-'+this.options.id+'">', this.lang.color.cpSelect, "</button>", '<input type="color" id="backColorPicker-'+this.options.id+'" class="note-btn note-color-select-btn" value="'+this.options.colorButton.backColor+'" data-event="backColorPalette-'+this.options.id+'">', "</div>", '<div class="note-holder-custom" id="backColorPalette-'+this.options.id+'" data-event="backColor"></div>', "</div>"].join("") : "")+(i ? ['<div class="note-palette">', '<div class="note-palette-title">'+this.lang.color.foreground+"</div>", "<div>", '<button type="button" class="note-color-reset btn btn-light btn-default" data-event="removeFormat" data-value="foreColor">', this.lang.color.resetToDefault, "</button>", "</div>", '<div class="note-holder" data-event="foreColor">\x3c!-- fore colors --\x3e</div>', "<div>", '<button type="button" class="note-color-select btn btn-light btn-default" data-event="openPalette" data-value="foreColorPicker-'+this.options.id+'">', this.lang.color.cpSelect, "</button>", '<input type="color" id="foreColorPicker-'+this.options.id+'" class="note-btn note-color-select-btn" value="'+this.options.colorButton.foreColor+'" data-event="foreColorPalette-'+this.options.id+'">', "</div>", '<div class="note-holder-custom" id="foreColorPalette-'+this.options.id+'" data-event="foreColor"></div>', "</div>"].join("") : ""),
                                callback: function (t) {
                                    t.find(".note-holder").each((function (t, n) {
                                        var o = e()(n);
                                        o.append(r.ui.palette({
                                            colors: r.options.colors,
                                            colorsName: r.options.colorsName,
                                            eventName: o.data("event"),
                                            container: r.options.container,
                                            tooltip: r.options.tooltip
                                        }).render())
                                    }));
                                    var n = [["#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF"]];
                                    t.find(".note-holder-custom").each((function (t, o) {
                                        var i = e()(o);
                                        i.append(r.ui.palette({
                                            colors: n,
                                            colorsName: n,
                                            eventName: i.data("event"),
                                            container: r.options.container,
                                            tooltip: r.options.tooltip
                                        }).render())
                                    })), t.find("input[type=color]").each((function (n, o) {
                                        e()(o).change((function () {
                                            var n = t.find("#"+e()(this).data("event")).find(".note-color-btn").first(),
                                                o = this.value.toUpperCase();
                                            n.css("background-color", o).attr("aria-label", o).attr("data-value", o).attr("data-original-title", o), n.click()
                                        }))
                                    }))
                                },
                                click: function (n) {
                                    n.stopPropagation();
                                    var o = e()("."+t).find(".note-dropdown-menu"), i = e()(n.target),
                                        a = i.data("event"), s = i.attr("data-value");
                                    if ("openPalette" === a) {
                                        var l = o.find("#"+s),
                                            c = e()(o.find("#"+l.data("event")).find(".note-color-row")[0]),
                                            u = c.find(".note-color-btn").last().detach(), d = l.val();
                                        u.css("background-color", d).attr("aria-label", d).attr("data-value", d).attr("data-original-title", d), c.prepend(u), l.click()
                                    } else {
                                        if (k.contains(["backColor", "foreColor"], a)) {
                                            var h = "backColor" === a ? "background-color" : "color",
                                                f = i.closest(".note-color").find(".note-recent-color"),
                                                p = i.closest(".note-color").find(".note-current-color-button");
                                            f.css(h, s), p.attr("data-"+a, s)
                                        }
                                        r.context.invoke("editor."+a, s)
                                    }
                                }
                            })]
                        }).render()
                    }
                }, {
                    key: "addToolbarButtons", value: function () {
                        var t = this;
                        this.context.memo("button.style", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.magic), t.options),
                                tooltip: t.lang.style.style,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdown({
                                className: "dropdown-style",
                                items: t.options.styleTags,
                                title: t.lang.style.style,
                                template: function (e) {
                                    "string" == typeof e && (e = {
                                        tag: e,
                                        title: Object.prototype.hasOwnProperty.call(t.lang.style, e) ? t.lang.style[e] : e
                                    });
                                    var n = e.tag, o = e.title;
                                    return "<"+n+(e.style ? ' style="'+e.style+'" ' : "")+(e.className ? ' class="'+e.className+'"' : "")+">"+o+"</"+n+">"
                                },
                                click: t.context.createInvokeHandler("editor.formatBlock")
                            })]).render()
                        }));
                        for (var n = function (e, n) {
                            var o = t.options.styleTags[e];
                            t.context.memo("button.style."+o, (function () {
                                return t.button({
                                    className: "note-btn-style-"+o,
                                    contents: '<div data-value="'+o+'">'+o.toUpperCase()+"</div>",
                                    tooltip: t.lang.style[o],
                                    click: t.context.createInvokeHandler("editor.formatBlock")
                                }).render()
                            }))
                        }, o = 0, i = this.options.styleTags.length; o < i; o++) n(o);
                        this.context.memo("button.bold", (function () {
                            return t.button({
                                className: "note-btn-bold",
                                contents: t.ui.icon(t.options.icons.bold),
                                tooltip: t.lang.font.bold+t.representShortcut("bold"),
                                click: t.context.createInvokeHandlerAndUpdateState("editor.bold")
                            }).render()
                        })), this.context.memo("button.italic", (function () {
                            return t.button({
                                className: "note-btn-italic",
                                contents: t.ui.icon(t.options.icons.italic),
                                tooltip: t.lang.font.italic+t.representShortcut("italic"),
                                click: t.context.createInvokeHandlerAndUpdateState("editor.italic")
                            }).render()
                        })), this.context.memo("button.underline", (function () {
                            return t.button({
                                className: "note-btn-underline",
                                contents: t.ui.icon(t.options.icons.underline),
                                tooltip: t.lang.font.underline+t.representShortcut("underline"),
                                click: t.context.createInvokeHandlerAndUpdateState("editor.underline")
                            }).render()
                        })), this.context.memo("button.clear", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.eraser),
                                tooltip: t.lang.font.clear+t.representShortcut("removeFormat"),
                                click: t.context.createInvokeHandler("editor.removeFormat")
                            }).render()
                        })), this.context.memo("button.strikethrough", (function () {
                            return t.button({
                                className: "note-btn-strikethrough",
                                contents: t.ui.icon(t.options.icons.strikethrough),
                                tooltip: t.lang.font.strikethrough+t.representShortcut("strikethrough"),
                                click: t.context.createInvokeHandlerAndUpdateState("editor.strikethrough")
                            }).render()
                        })), this.context.memo("button.superscript", (function () {
                            return t.button({
                                className: "note-btn-superscript",
                                contents: t.ui.icon(t.options.icons.superscript),
                                tooltip: t.lang.font.superscript,
                                click: t.context.createInvokeHandlerAndUpdateState("editor.superscript")
                            }).render()
                        })), this.context.memo("button.subscript", (function () {
                            return t.button({
                                className: "note-btn-subscript",
                                contents: t.ui.icon(t.options.icons.subscript),
                                tooltip: t.lang.font.subscript,
                                click: t.context.createInvokeHandlerAndUpdateState("editor.subscript")
                            }).render()
                        })), this.context.memo("button.fontname", (function () {
                            var n = t.context.invoke("editor.currentStyle");
                            return t.options.addDefaultFonts && e().each(n["font-family"].split(","), (function (e, n) {
                                n = n.trim().replace(/['"]+/g, ""), t.isFontDeservedToAdd(n) && -1 === t.options.fontNames.indexOf(n) && t.options.fontNames.push(n)
                            })), t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents('<span class="note-current-fontname"></span>', t.options),
                                tooltip: t.lang.font.name,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdownCheck({
                                className: "dropdown-fontname",
                                checkClassName: t.options.icons.menuCheck,
                                items: t.options.fontNames.filter(t.isFontInstalled.bind(t)),
                                title: t.lang.font.name,
                                template: function (t) {
                                    return '<span style="font-family: '+f.validFontName(t)+'">'+t+"</span>"
                                },
                                click: t.context.createInvokeHandlerAndUpdateState("editor.fontName")
                            })]).render()
                        })), this.context.memo("button.fontsize", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents('<span class="note-current-fontsize"></span>', t.options),
                                tooltip: t.lang.font.size,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdownCheck({
                                className: "dropdown-fontsize",
                                checkClassName: t.options.icons.menuCheck,
                                items: t.options.fontSizes,
                                title: t.lang.font.size,
                                click: t.context.createInvokeHandlerAndUpdateState("editor.fontSize")
                            })]).render()
                        })), this.context.memo("button.fontsizeunit", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents('<span class="note-current-fontsizeunit"></span>', t.options),
                                tooltip: t.lang.font.sizeunit,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdownCheck({
                                className: "dropdown-fontsizeunit",
                                checkClassName: t.options.icons.menuCheck,
                                items: t.options.fontSizeUnits,
                                title: t.lang.font.sizeunit,
                                click: t.context.createInvokeHandlerAndUpdateState("editor.fontSizeUnit")
                            })]).render()
                        })), this.context.memo("button.color", (function () {
                            return t.colorPalette("note-color-all", t.lang.color.recent, !0, !0)
                        })), this.context.memo("button.forecolor", (function () {
                            return t.colorPalette("note-color-fore", t.lang.color.foreground, !1, !0)
                        })), this.context.memo("button.backcolor", (function () {
                            return t.colorPalette("note-color-back", t.lang.color.background, !0, !1)
                        })), this.context.memo("button.ul", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.unorderedlist),
                                tooltip: t.lang.lists.unordered+t.representShortcut("insertUnorderedList"),
                                click: t.context.createInvokeHandler("editor.insertUnorderedList")
                            }).render()
                        })), this.context.memo("button.ol", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.orderedlist),
                                tooltip: t.lang.lists.ordered+t.representShortcut("insertOrderedList"),
                                click: t.context.createInvokeHandler("editor.insertOrderedList")
                            }).render()
                        }));
                        var r = this.button({
                            contents: this.ui.icon(this.options.icons.alignLeft),
                            tooltip: this.lang.paragraph.left+this.representShortcut("justifyLeft"),
                            click: this.context.createInvokeHandler("editor.justifyLeft")
                        }), a = this.button({
                            contents: this.ui.icon(this.options.icons.alignCenter),
                            tooltip: this.lang.paragraph.center+this.representShortcut("justifyCenter"),
                            click: this.context.createInvokeHandler("editor.justifyCenter")
                        }), s = this.button({
                            contents: this.ui.icon(this.options.icons.alignRight),
                            tooltip: this.lang.paragraph.right+this.representShortcut("justifyRight"),
                            click: this.context.createInvokeHandler("editor.justifyRight")
                        }), l = this.button({
                            contents: this.ui.icon(this.options.icons.alignJustify),
                            tooltip: this.lang.paragraph.justify+this.representShortcut("justifyFull"),
                            click: this.context.createInvokeHandler("editor.justifyFull")
                        }), c = this.button({
                            contents: this.ui.icon(this.options.icons.outdent),
                            tooltip: this.lang.paragraph.outdent+this.representShortcut("outdent"),
                            click: this.context.createInvokeHandler("editor.outdent")
                        }), u = this.button({
                            contents: this.ui.icon(this.options.icons.indent),
                            tooltip: this.lang.paragraph.indent+this.representShortcut("indent"),
                            click: this.context.createInvokeHandler("editor.indent")
                        });
                        this.context.memo("button.justifyLeft", m.invoke(r, "render")), this.context.memo("button.justifyCenter", m.invoke(a, "render")), this.context.memo("button.justifyRight", m.invoke(s, "render")), this.context.memo("button.justifyFull", m.invoke(l, "render")), this.context.memo("button.outdent", m.invoke(c, "render")), this.context.memo("button.indent", m.invoke(u, "render")), this.context.memo("button.paragraph", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.alignLeft), t.options),
                                tooltip: t.lang.paragraph.paragraph,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdown([t.ui.buttonGroup({
                                className: "note-align",
                                children: [r, a, s, l]
                            }), t.ui.buttonGroup({className: "note-list", children: [c, u]})])]).render()
                        })), this.context.memo("button.height", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.textHeight), t.options),
                                tooltip: t.lang.font.height,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdownCheck({
                                items: t.options.lineHeights,
                                checkClassName: t.options.icons.menuCheck,
                                className: "dropdown-line-height",
                                title: t.lang.font.height,
                                click: t.context.createInvokeHandler("editor.lineHeight")
                            })]).render()
                        })), this.context.memo("button.table", (function () {
                            return t.ui.buttonGroup([t.button({
                                className: "dropdown-toggle",
                                contents: t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.table), t.options),
                                tooltip: t.lang.table.table,
                                data: {toggle: "dropdown"}
                            }), t.ui.dropdown({
                                title: t.lang.table.table,
                                className: "note-table",
                                items: ['<div class="note-dimension-picker">', '<div class="note-dimension-picker-mousecatcher" data-event="insertTable" data-value="1x1"></div>', '<div class="note-dimension-picker-highlighted"></div>', '<div class="note-dimension-picker-unhighlighted"></div>', "</div>", '<div class="note-dimension-display">1 x 1</div>'].join("")
                            })], {
                                callback: function (e) {
                                    e.find(".note-dimension-picker-mousecatcher").css({
                                        width: t.options.insertTableMaxSize.col+"em",
                                        height: t.options.insertTableMaxSize.row+"em"
                                    }).mouseup(t.context.createInvokeHandler("editor.insertTable")).on("mousemove", t.tableMoveHandler.bind(t))
                                }
                            }).render()
                        })), this.context.memo("button.link", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.link),
                                tooltip: t.lang.link.link+t.representShortcut("linkDialog.show"),
                                click: t.context.createInvokeHandler("linkDialog.show")
                            }).render()
                        })), this.context.memo("button.picture", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.picture),
                                tooltip: t.lang.image.image,
                                click: t.context.createInvokeHandler("imageDialog.show")
                            }).render()
                        })), this.context.memo("button.video", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.video),
                                tooltip: t.lang.video.video,
                                click: t.context.createInvokeHandler("videoDialog.show")
                            }).render()
                        })), this.context.memo("button.hr", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.minus),
                                tooltip: t.lang.hr.insert+t.representShortcut("insertHorizontalRule"),
                                click: t.context.createInvokeHandler("editor.insertHorizontalRule")
                            }).render()
                        })), this.context.memo("button.fullscreen", (function () {
                            return t.button({
                                className: "btn-fullscreen note-codeview-keep",
                                contents: t.ui.icon(t.options.icons.arrowsAlt),
                                tooltip: t.lang.options.fullscreen,
                                click: t.context.createInvokeHandler("fullscreen.toggle")
                            }).render()
                        })), this.context.memo("button.codeview", (function () {
                            return t.button({
                                className: "btn-codeview note-codeview-keep",
                                contents: t.ui.icon(t.options.icons.code),
                                tooltip: t.lang.options.codeview,
                                click: t.context.createInvokeHandler("codeview.toggle")
                            }).render()
                        })), this.context.memo("button.redo", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.redo),
                                tooltip: t.lang.history.redo+t.representShortcut("redo"),
                                click: t.context.createInvokeHandler("editor.redo")
                            }).render()
                        })), this.context.memo("button.undo", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.undo),
                                tooltip: t.lang.history.undo+t.representShortcut("undo"),
                                click: t.context.createInvokeHandler("editor.undo")
                            }).render()
                        })), this.context.memo("button.help", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.question),
                                tooltip: t.lang.options.help,
                                click: t.context.createInvokeHandler("helpDialog.show")
                            }).render()
                        }))
                    }
                }, {
                    key: "addImagePopoverButtons", value: function () {
                        var t = this;
                        this.context.memo("button.resizeFull", (function () {
                            return t.button({
                                contents: '<span class="note-fontsize-10">100%</span>',
                                tooltip: t.lang.image.resizeFull,
                                click: t.context.createInvokeHandler("editor.resize", "1")
                            }).render()
                        })), this.context.memo("button.resizeHalf", (function () {
                            return t.button({
                                contents: '<span class="note-fontsize-10">50%</span>',
                                tooltip: t.lang.image.resizeHalf,
                                click: t.context.createInvokeHandler("editor.resize", "0.5")
                            }).render()
                        })), this.context.memo("button.resizeQuarter", (function () {
                            return t.button({
                                contents: '<span class="note-fontsize-10">25%</span>',
                                tooltip: t.lang.image.resizeQuarter,
                                click: t.context.createInvokeHandler("editor.resize", "0.25")
                            }).render()
                        })), this.context.memo("button.resizeNone", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.rollback),
                                tooltip: t.lang.image.resizeNone,
                                click: t.context.createInvokeHandler("editor.resize", "0")
                            }).render()
                        })), this.context.memo("button.floatLeft", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.floatLeft),
                                tooltip: t.lang.image.floatLeft,
                                click: t.context.createInvokeHandler("editor.floatMe", "left")
                            }).render()
                        })), this.context.memo("button.floatRight", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.floatRight),
                                tooltip: t.lang.image.floatRight,
                                click: t.context.createInvokeHandler("editor.floatMe", "right")
                            }).render()
                        })), this.context.memo("button.floatNone", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.rollback),
                                tooltip: t.lang.image.floatNone,
                                click: t.context.createInvokeHandler("editor.floatMe", "none")
                            }).render()
                        })), this.context.memo("button.removeMedia", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.trash),
                                tooltip: t.lang.image.remove,
                                click: t.context.createInvokeHandler("editor.removeMedia")
                            }).render()
                        }))
                    }
                }, {
                    key: "addLinkPopoverButtons", value: function () {
                        var t = this;
                        this.context.memo("button.linkDialogShow", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.link),
                                tooltip: t.lang.link.edit,
                                click: t.context.createInvokeHandler("linkDialog.show")
                            }).render()
                        })), this.context.memo("button.unlink", (function () {
                            return t.button({
                                contents: t.ui.icon(t.options.icons.unlink),
                                tooltip: t.lang.link.unlink,
                                click: t.context.createInvokeHandler("editor.unlink")
                            }).render()
                        }))
                    }
                }, {
                    key: "addTablePopoverButtons", value: function () {
                        var t = this;
                        this.context.memo("button.addRowUp", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.rowAbove),
                                tooltip: t.lang.table.addRowAbove,
                                click: t.context.createInvokeHandler("editor.addRow", "top")
                            }).render()
                        })), this.context.memo("button.addRowDown", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.rowBelow),
                                tooltip: t.lang.table.addRowBelow,
                                click: t.context.createInvokeHandler("editor.addRow", "bottom")
                            }).render()
                        })), this.context.memo("button.addColLeft", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.colBefore),
                                tooltip: t.lang.table.addColLeft,
                                click: t.context.createInvokeHandler("editor.addCol", "left")
                            }).render()
                        })), this.context.memo("button.addColRight", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.colAfter),
                                tooltip: t.lang.table.addColRight,
                                click: t.context.createInvokeHandler("editor.addCol", "right")
                            }).render()
                        })), this.context.memo("button.deleteRow", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.rowRemove),
                                tooltip: t.lang.table.delRow,
                                click: t.context.createInvokeHandler("editor.deleteRow")
                            }).render()
                        })), this.context.memo("button.deleteCol", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.colRemove),
                                tooltip: t.lang.table.delCol,
                                click: t.context.createInvokeHandler("editor.deleteCol")
                            }).render()
                        })), this.context.memo("button.deleteTable", (function () {
                            return t.button({
                                className: "btn-md",
                                contents: t.ui.icon(t.options.icons.trash),
                                tooltip: t.lang.table.delTable,
                                click: t.context.createInvokeHandler("editor.deleteTable")
                            }).render()
                        }))
                    }
                }, {
                    key: "build", value: function (t, e) {
                        for (var n = 0, o = e.length; n < o; n++) {
                            for (var i = e[n], r = Array.isArray(i) ? i[0] : i, a = Array.isArray(i) ? 1 === i.length ? [i[0]] : i[1] : [i], s = this.ui.buttonGroup({className: "note-"+r}).render(), l = 0, c = a.length; l < c; l++) {
                                var u = this.context.memo("button."+a[l]);
                                u && s.append("function" == typeof u ? u(this.context) : u)
                            }
                            s.appendTo(t)
                        }
                    }
                }, {
                    key: "updateCurrentStyle", value: function (t) {
                        var n = t || this.$toolbar, o = this.context.invoke("editor.currentStyle");
                        if (this.updateBtnStates(n, {
                            ".note-btn-bold": function () {
                                return "bold" === o["font-bold"]
                            }, ".note-btn-italic": function () {
                                return "italic" === o["font-italic"]
                            }, ".note-btn-underline": function () {
                                return "underline" === o["font-underline"]
                            }, ".note-btn-subscript": function () {
                                return "subscript" === o["font-subscript"]
                            }, ".note-btn-superscript": function () {
                                return "superscript" === o["font-superscript"]
                            }, ".note-btn-strikethrough": function () {
                                return "strikethrough" === o["font-strikethrough"]
                            }
                        }), o["font-family"]) {
                            var i = o["font-family"].split(",").map((function (t) {
                                return t.replace(/[\'\"]/g, "").replace(/\s+$/, "").replace(/^\s+/, "")
                            })), r = k.find(i, this.isFontInstalled.bind(this));
                            n.find(".dropdown-fontname a").each((function (t, n) {
                                var o = e()(n), i = o.data("value")+"" == r+"";
                                o.toggleClass("checked", i)
                            })), n.find(".note-current-fontname").text(r).css("font-family", r)
                        }
                        if (o["font-size"]) {
                            var a = o["font-size"];
                            n.find(".dropdown-fontsize a").each((function (t, n) {
                                var o = e()(n), i = o.data("value")+"" == a+"";
                                o.toggleClass("checked", i)
                            })), n.find(".note-current-fontsize").text(a);
                            var s = o["font-size-unit"];
                            n.find(".dropdown-fontsizeunit a").each((function (t, n) {
                                var o = e()(n), i = o.data("value")+"" == s+"";
                                o.toggleClass("checked", i)
                            })), n.find(".note-current-fontsizeunit").text(s)
                        }
                        if (o["line-height"]) {
                            var l = o["line-height"];
                            n.find(".dropdown-line-height a").each((function (t, n) {
                                var o = e()(n), i = e()(n).data("value")+"" == l+"";
                                o.toggleClass("checked", i)
                            })), n.find(".note-current-line-height").text(l)
                        }
                    }
                }, {
                    key: "updateBtnStates", value: function (t, n) {
                        var o = this;
                        e().each(n, (function (e, n) {
                            o.ui.toggleBtnActive(t.find(e), n())
                        }))
                    }
                }, {
                    key: "tableMoveHandler", value: function (t) {
                        var n, o = e()(t.target.parentNode), i = o.next(),
                            r = o.find(".note-dimension-picker-mousecatcher"),
                            a = o.find(".note-dimension-picker-highlighted"),
                            s = o.find(".note-dimension-picker-unhighlighted");
                        if (void 0 === t.offsetX) {
                            var l = e()(t.target).offset();
                            n = {x: t.pageX-l.left, y: t.pageY-l.top}
                        } else n = {x: t.offsetX, y: t.offsetY};
                        var c = Math.ceil(n.x / 18) || 1, u = Math.ceil(n.y / 18) || 1;
                        a.css({
                            width: c+"em",
                            height: u+"em"
                        }), r.data("value", c+"x"+u), c > 3 && c < this.options.insertTableMaxSize.col && s.css({width: c+1+"em"}), u > 3 && u < this.options.insertTableMaxSize.row && s.css({height: u+1+"em"}), i.html(c+" x "+u)
                    }
                }], o && ie(n.prototype, o), i && ie(n, i), t
            }();

            function ae(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var se = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.$window = e()(window), this.$document = e()(document), this.ui = e().summernote.ui, this.$note = n.layoutInfo.note, this.$editor = n.layoutInfo.editor, this.$toolbar = n.layoutInfo.toolbar, this.$editable = n.layoutInfo.editable, this.$statusbar = n.layoutInfo.statusbar, this.options = n.options, this.isFollowing = !1, this.followScroll = this.followScroll.bind(this)
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "shouldInitialize", value: function () {
                        return !this.options.airMode
                    }
                }, {
                    key: "initialize", value: function () {
                        var t = this;
                        this.options.toolbar = this.options.toolbar || [], this.options.toolbar.length ? this.context.invoke("buttons.build", this.$toolbar, this.options.toolbar) : this.$toolbar.hide(), this.options.toolbarContainer && this.$toolbar.appendTo(this.options.toolbarContainer), this.changeContainer(!1), this.$note.on("summernote.keyup summernote.mouseup summernote.change", (function () {
                            t.context.invoke("buttons.updateCurrentStyle")
                        })), this.context.invoke("buttons.updateCurrentStyle"), this.options.followingToolbar && this.$window.on("scroll resize", this.followScroll)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$toolbar.children().remove(), this.options.followingToolbar && this.$window.off("scroll resize", this.followScroll)
                    }
                }, {
                    key: "followScroll", value: function () {
                        if (this.$editor.hasClass("fullscreen")) return !1;
                        var t = this.$editor.outerHeight(), n = this.$editor.width(), o = this.$toolbar.height(),
                            i = this.$statusbar.height(), r = 0;
                        this.options.otherStaticBar && (r = e()(this.options.otherStaticBar).outerHeight());
                        var a = this.$document.scrollTop(), s = this.$editor.offset().top, l = s-r, c = s+t-r-o-i;
                        !this.isFollowing && a > l && a < c-o ? (this.isFollowing = !0, this.$editable.css({marginTop: this.$toolbar.outerHeight()}), this.$toolbar.css({
                            position: "fixed",
                            top: r,
                            width: n,
                            zIndex: 1e3
                        })) : this.isFollowing && (a < l || a > c) && (this.isFollowing = !1, this.$toolbar.css({
                            position: "relative",
                            top: 0,
                            width: "100%",
                            zIndex: "auto"
                        }), this.$editable.css({marginTop: ""}))
                    }
                }, {
                    key: "changeContainer", value: function (t) {
                        t ? this.$toolbar.prependTo(this.$editor) : this.options.toolbarContainer && this.$toolbar.appendTo(this.options.toolbarContainer), this.options.followingToolbar && this.followScroll()
                    }
                }, {
                    key: "updateFullscreen", value: function (t) {
                        this.ui.toggleBtnActive(this.$toolbar.find(".btn-fullscreen"), t), this.changeContainer(t)
                    }
                }, {
                    key: "updateCodeview", value: function (t) {
                        this.ui.toggleBtnActive(this.$toolbar.find(".btn-codeview"), t), t ? this.deactivate() : this.activate()
                    }
                }, {
                    key: "activate", value: function (t) {
                        var e = this.$toolbar.find("button");
                        t || (e = e.not(".note-codeview-keep")), this.ui.toggleBtn(e, !0)
                    }
                }, {
                    key: "deactivate", value: function (t) {
                        var e = this.$toolbar.find("button");
                        t || (e = e.not(".note-codeview-keep")), this.ui.toggleBtn(e, !1)
                    }
                }]) && ae(n.prototype, o), i && ae(n, i), t
            }();

            function le(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var ce = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.$body = e()(document.body), this.$editor = n.layoutInfo.editor, this.options = n.options, this.lang = this.options.langInfo, n.memo("help.linkDialog.show", this.options.langInfo.help["linkDialog.show"])
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "initialize", value: function () {
                        var t = this.options.dialogsInBody ? this.$body : this.options.container,
                            n = ['<div class="form-group note-form-group">', '<label for="note-dialog-link-txt-'.concat(this.options.id, '" class="note-form-label">').concat(this.lang.link.textToDisplay, "</label>"), '<input id="note-dialog-link-txt-'.concat(this.options.id, '" class="note-link-text form-control note-form-control note-input" type="text"/>'), "</div>", '<div class="form-group note-form-group">', '<label for="note-dialog-link-url-'.concat(this.options.id, '" class="note-form-label">').concat(this.lang.link.url, "</label>"), '<input id="note-dialog-link-url-'.concat(this.options.id, '" class="note-link-url form-control note-form-control note-input" type="text" value="http://"/>'), "</div>", this.options.disableLinkTarget ? "" : e()("<div></div>").append(this.ui.checkbox({
                                className: "sn-checkbox-open-in-new-window",
                                text: this.lang.link.openInNewWindow,
                                checked: !0
                            }).render()).html(), e()("<div></div>").append(this.ui.checkbox({
                                className: "sn-checkbox-use-protocol",
                                text: this.lang.link.useProtocol,
                                checked: !0
                            }).render()).html()].join(""),
                            o = '<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-link-btn", '" value="').concat(this.lang.link.insert, '" disabled>');
                        this.$dialog = this.ui.dialog({
                            className: "link-dialog",
                            title: this.lang.link.insert,
                            fade: this.options.dialogsFade,
                            body: n,
                            footer: o
                        }).render().appendTo(t)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.ui.hideDialog(this.$dialog), this.$dialog.remove()
                    }
                }, {
                    key: "bindEnterKey", value: function (t, e) {
                        t.on("keypress", (function (t) {
                            t.keyCode === wt.code.ENTER && (t.preventDefault(), e.trigger("click"))
                        }))
                    }
                }, {
                    key: "toggleLinkBtn", value: function (t, e, n) {
                        this.ui.toggleBtn(t, e.val() && n.val())
                    }
                }, {
                    key: "showLinkDialog", value: function (t) {
                        var n = this;
                        return e().Deferred((function (e) {
                            var o = n.$dialog.find(".note-link-text"), i = n.$dialog.find(".note-link-url"),
                                r = n.$dialog.find(".note-link-btn"),
                                a = n.$dialog.find(".sn-checkbox-open-in-new-window input[type=checkbox]"),
                                s = n.$dialog.find(".sn-checkbox-use-protocol input[type=checkbox]");
                            n.ui.onDialogShown(n.$dialog, (function () {
                                n.context.triggerEvent("dialog.shown"), !t.url && m.isValidUrl(t.text) && (t.url = t.text), o.on("input paste propertychange", (function () {
                                    t.text = o.val(), n.toggleLinkBtn(r, o, i)
                                })).val(t.text), i.on("input paste propertychange", (function () {
                                    t.text || o.val(i.val()), n.toggleLinkBtn(r, o, i)
                                })).val(t.url), f.isSupportTouch || i.trigger("focus"), n.toggleLinkBtn(r, o, i), n.bindEnterKey(i, r), n.bindEnterKey(o, r);
                                var l = void 0 !== t.isNewWindow ? t.isNewWindow : n.context.options.linkTargetBlank;
                                a.prop("checked", l);
                                var c = !t.url && n.context.options.useProtocol;
                                s.prop("checked", c), r.one("click", (function (r) {
                                    r.preventDefault(), e.resolve({
                                        range: t.range,
                                        url: i.val(),
                                        text: o.val(),
                                        isNewWindow: a.is(":checked"),
                                        checkProtocol: s.is(":checked")
                                    }), n.ui.hideDialog(n.$dialog)
                                }))
                            })), n.ui.onDialogHidden(n.$dialog, (function () {
                                o.off(), i.off(), r.off(), "pending" === e.state() && e.reject()
                            })), n.ui.showDialog(n.$dialog)
                        })).promise()
                    }
                }, {
                    key: "show", value: function () {
                        var t = this, e = this.context.invoke("editor.getLinkInfo");
                        this.context.invoke("editor.saveRange"), this.showLinkDialog(e).then((function (e) {
                            t.context.invoke("editor.restoreRange"), t.context.invoke("editor.createLink", e)
                        })).fail((function () {
                            t.context.invoke("editor.restoreRange")
                        }))
                    }
                }]) && le(n.prototype, o), i && le(n, i), t
            }();

            function ue(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var de = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.options = n.options, this.events = {
                        "summernote.keyup summernote.mouseup summernote.change summernote.scroll": function () {
                            o.update()
                        }, "summernote.disable summernote.dialog.shown": function () {
                            o.hide()
                        }, "summernote.blur": function (t, e) {
                            e.originalEvent && e.originalEvent.relatedTarget && o.$popover[0].contains(e.originalEvent.relatedTarget) || o.hide()
                        }
                    }
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "shouldInitialize", value: function () {
                        return !k.isEmpty(this.options.popover.link)
                    }
                }, {
                    key: "initialize", value: function () {
                        this.$popover = this.ui.popover({
                            className: "note-link-popover", callback: function (t) {
                                t.find(".popover-content,.note-popover-content").prepend('<span><a target="_blank"></a>&nbsp;</span>')
                            }
                        }).render().appendTo(this.options.container);
                        var t = this.$popover.find(".popover-content,.note-popover-content");
                        this.context.invoke("buttons.build", t, this.options.popover.link), this.$popover.on("mousedown", (function (t) {
                            t.preventDefault()
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$popover.remove()
                    }
                }, {
                    key: "update", value: function () {
                        if (this.context.invoke("editor.hasFocus")) {
                            var t = this.context.invoke("editor.getLastRange");
                            if (t.isCollapsed() && t.isOnAnchor()) {
                                var n = ht.ancestor(t.sc, ht.isAnchor), o = e()(n).attr("href");
                                this.$popover.find("a").attr("href", o).text(o);
                                var i = ht.posFromPlaceholder(n), r = e()(this.options.container).offset();
                                i.top -= r.top, i.left -= r.left, this.$popover.css({
                                    display: "block",
                                    left: i.left,
                                    top: i.top
                                })
                            } else this.hide()
                        } else this.hide()
                    }
                }, {
                    key: "hide", value: function () {
                        this.$popover.hide()
                    }
                }]) && ue(n.prototype, o), i && ue(n, i), t
            }();

            function he(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var fe = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.$body = e()(document.body), this.$editor = n.layoutInfo.editor, this.options = n.options, this.lang = this.options.langInfo
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "initialize", value: function () {
                        var t = "";
                        if (this.options.maximumImageFileSize) {
                            var e = Math.floor(Math.log(this.options.maximumImageFileSize) / Math.log(1024)),
                                n = 1 * (this.options.maximumImageFileSize / Math.pow(1024, e)).toFixed(2)+" "+" KMGTP"[e]+"B";
                            t = "<small>".concat(this.lang.image.maximumFileSize+" : "+n, "</small>")
                        }
                        var o = this.options.dialogsInBody ? this.$body : this.options.container,
                            i = ['<div class="form-group note-form-group note-group-select-from-files">', '<label for="note-dialog-image-file-'+this.options.id+'" class="note-form-label">'+this.lang.image.selectFromFiles+"</label>", '<input id="note-dialog-image-file-'+this.options.id+'" class="note-image-input form-control-file note-form-control note-input" ', ' type="file" name="files" accept="'+this.options.acceptImageFileTypes+'" multiple="multiple"/>', t, "</div>", '<div class="form-group note-group-image-url">', '<label for="note-dialog-image-url-'+this.options.id+'" class="note-form-label">'+this.lang.image.url+"</label>", '<input id="note-dialog-image-url-'+this.options.id+'" class="note-image-url form-control note-form-control note-input" type="text"/>', "</div>"].join(""),
                            r = '<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-image-btn", '" value="').concat(this.lang.image.insert, '" disabled>');
                        this.$dialog = this.ui.dialog({
                            title: this.lang.image.insert,
                            fade: this.options.dialogsFade,
                            body: i,
                            footer: r
                        }).render().appendTo(o)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.ui.hideDialog(this.$dialog), this.$dialog.remove()
                    }
                }, {
                    key: "bindEnterKey", value: function (t, e) {
                        t.on("keypress", (function (t) {
                            t.keyCode === wt.code.ENTER && (t.preventDefault(), e.trigger("click"))
                        }))
                    }
                }, {
                    key: "show", value: function () {
                        var t = this;
                        this.context.invoke("editor.saveRange"), this.showImageDialog().then((function (e) {
                            t.ui.hideDialog(t.$dialog), t.context.invoke("editor.restoreRange"), "string" == typeof e ? t.options.callbacks.onImageLinkInsert ? t.context.triggerEvent("image.link.insert", e) : t.context.invoke("editor.insertImage", e) : t.context.invoke("editor.insertImagesOrCallback", e)
                        })).fail((function () {
                            t.context.invoke("editor.restoreRange")
                        }))
                    }
                }, {
                    key: "showImageDialog", value: function () {
                        var t = this;
                        return e().Deferred((function (e) {
                            var n = t.$dialog.find(".note-image-input"), o = t.$dialog.find(".note-image-url"),
                                i = t.$dialog.find(".note-image-btn");
                            t.ui.onDialogShown(t.$dialog, (function () {
                                t.context.triggerEvent("dialog.shown"), n.replaceWith(n.clone().on("change", (function (t) {
                                    e.resolve(t.target.files || t.target.value)
                                })).val("")), o.on("input paste propertychange", (function () {
                                    t.ui.toggleBtn(i, o.val())
                                })).val(""), f.isSupportTouch || o.trigger("focus"), i.click((function (t) {
                                    t.preventDefault(), e.resolve(o.val())
                                })), t.bindEnterKey(o, i)
                            })), t.ui.onDialogHidden(t.$dialog, (function () {
                                n.off(), o.off(), i.off(), "pending" === e.state() && e.reject()
                            })), t.ui.showDialog(t.$dialog)
                        }))
                    }
                }]) && he(n.prototype, o), i && he(n, i), t
            }();

            function pe(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var me = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.editable = n.layoutInfo.editable[0], this.options = n.options, this.events = {
                        "summernote.disable summernote.dialog.shown": function () {
                            o.hide()
                        }, "summernote.blur": function (t, e) {
                            e.originalEvent && e.originalEvent.relatedTarget && o.$popover[0].contains(e.originalEvent.relatedTarget) || o.hide()
                        }
                    }
                }

                var n, o, i;
                return n = t, o = [{
                    key: "shouldInitialize", value: function () {
                        return !k.isEmpty(this.options.popover.image)
                    }
                }, {
                    key: "initialize", value: function () {
                        this.$popover = this.ui.popover({className: "note-image-popover"}).render().appendTo(this.options.container);
                        var t = this.$popover.find(".popover-content,.note-popover-content");
                        this.context.invoke("buttons.build", t, this.options.popover.image), this.$popover.on("mousedown", (function (t) {
                            t.preventDefault()
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$popover.remove()
                    }
                }, {
                    key: "update", value: function (t, n) {
                        if (ht.isImg(t)) {
                            var o = e()(t).offset(), i = e()(this.options.container).offset(), r = {};
                            this.options.popatmouse ? (r.left = n.pageX-20, r.top = n.pageY) : r = o, r.top -= i.top, r.left -= i.left, this.$popover.css({
                                display: "block",
                                left: r.left,
                                top: r.top
                            })
                        } else this.hide()
                    }
                }, {
                    key: "hide", value: function () {
                        this.$popover.hide()
                    }
                }], o && pe(n.prototype, o), i && pe(n, i), t
            }();

            function ve(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var ge = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.options = n.options, this.events = {
                        "summernote.mousedown": function (t, e) {
                            o.update(e.target)
                        }, "summernote.keyup summernote.scroll summernote.change": function () {
                            o.update()
                        }, "summernote.disable summernote.dialog.shown": function () {
                            o.hide()
                        }, "summernote.blur": function (t, e) {
                            e.originalEvent && e.originalEvent.relatedTarget && o.$popover[0].contains(e.originalEvent.relatedTarget) || o.hide()
                        }
                    }
                }

                var n, o, i;
                return n = t, o = [{
                    key: "shouldInitialize", value: function () {
                        return !k.isEmpty(this.options.popover.table)
                    }
                }, {
                    key: "initialize", value: function () {
                        this.$popover = this.ui.popover({className: "note-table-popover"}).render().appendTo(this.options.container);
                        var t = this.$popover.find(".popover-content,.note-popover-content");
                        this.context.invoke("buttons.build", t, this.options.popover.table), f.isFF && document.execCommand("enableInlineTableEditing", !1, !1), this.$popover.on("mousedown", (function (t) {
                            t.preventDefault()
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$popover.remove()
                    }
                }, {
                    key: "update", value: function (t) {
                        if (this.context.isDisabled()) return !1;
                        var n = ht.isCell(t) || ht.isCell(null == t ? void 0 : t.parentElement);
                        if (n) {
                            var o = ht.posFromPlaceholder(t), i = e()(this.options.container).offset();
                            o.top -= i.top, o.left -= i.left, this.$popover.css({
                                display: "block",
                                left: o.left,
                                top: o.top
                            })
                        } else this.hide();
                        return n
                    }
                }, {
                    key: "hide", value: function () {
                        this.$popover.hide()
                    }
                }], o && ve(n.prototype, o), i && ve(n, i), t
            }();

            function be(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var ye = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.$body = e()(document.body), this.$editor = n.layoutInfo.editor, this.options = n.options, this.lang = this.options.langInfo
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "initialize", value: function () {
                        var t = this.options.dialogsInBody ? this.$body : this.options.container,
                            e = ['<div class="form-group note-form-group row-fluid">', '<label for="note-dialog-video-url-'.concat(this.options.id, '" class="note-form-label">').concat(this.lang.video.url, ' <small class="text-muted">').concat(this.lang.video.providers, "</small></label>"), '<input id="note-dialog-video-url-'.concat(this.options.id, '" class="note-video-url form-control note-form-control note-input" type="text"/>'), "</div>"].join(""),
                            n = '<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-video-btn", '" value="').concat(this.lang.video.insert, '" disabled>');
                        this.$dialog = this.ui.dialog({
                            title: this.lang.video.insert,
                            fade: this.options.dialogsFade,
                            body: e,
                            footer: n
                        }).render().appendTo(t)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.ui.hideDialog(this.$dialog), this.$dialog.remove()
                    }
                }, {
                    key: "bindEnterKey", value: function (t, e) {
                        t.on("keypress", (function (t) {
                            t.keyCode === wt.code.ENTER && (t.preventDefault(), e.trigger("click"))
                        }))
                    }
                }, {
                    key: "createVideoNode", value: function (t) {
                        var n,
                            o = t.match(/\/\/(?:(?:www|m)\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w|-]{11})(?:(?:[\?&]t=)(\S+))?$/),
                            i = t.match(/(?:\.|\/\/)drive\.google\.com\/file\/d\/(.[a-zA-Z0-9_-]*)\/view/),
                            r = t.match(/(?:www\.|\/\/)instagram\.com\/p\/(.[a-zA-Z0-9_-]*)/),
                            a = t.match(/\/\/vine\.co\/v\/([a-zA-Z0-9]+)/),
                            s = t.match(/\/\/(player\.)?vimeo\.com\/([a-z]*\/)*(\d+)[?]?.*/),
                            l = t.match(/.+dailymotion.com\/(video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/),
                            c = t.match(/\/\/v\.youku\.com\/v_show\/id_(\w+)=*\.html/),
                            u = t.match(/\/\/(.*)\/videos\/watch\/([^?]*)(?:\?(?:start=(\w*))?(?:&stop=(\w*))?(?:&loop=([10]))?(?:&autoplay=([10]))?(?:&muted=([10]))?)?/),
                            d = t.match(/\/\/v\.qq\.com.*?vid=(.+)/),
                            h = t.match(/\/\/v\.qq\.com\/x?\/?(page|cover).*?\/([^\/]+)\.html\??.*/),
                            f = t.match(/^.+.(mp4|m4v)$/), p = t.match(/^.+.(ogg|ogv)$/), m = t.match(/^.+.(webm)$/),
                            v = t.match(/(?:www\.|\/\/)facebook\.com\/([^\/]+)\/videos\/([0-9]+)/);
                        if (o && 11 === o[1].length) {
                            var g = o[1], b = 0;
                            if (void 0 !== o[2]) {
                                var y = o[2].match(/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/);
                                if (y) for (var k = [3600, 60, 1], w = 0, C = k.length; w < C; w++) b += void 0 !== y[w+1] ? k[w] * parseInt(y[w+1], 10) : 0
                            }
                            n = e()("<iframe>").attr("frameborder", 0).attr("src", "//www.youtube.com/embed/"+g+(b > 0 ? "?start="+b : "")).attr("width", "640").attr("height", "360")
                        } else if (i && i[0].length) n = e()("<iframe>").attr("frameborder", 0).attr("src", "https://drive.google.com/file/d/"+i[1]+"/preview").attr("width", "640").attr("height", "480"); else if (r && r[0].length) n = e()("<iframe>").attr("frameborder", 0).attr("src", "https://instagram.com/p/"+r[1]+"/embed/").attr("width", "612").attr("height", "710").attr("scrolling", "no").attr("allowtransparency", "true"); else if (a && a[0].length) n = e()("<iframe>").attr("frameborder", 0).attr("src", a[0]+"/embed/simple").attr("width", "600").attr("height", "600").attr("class", "vine-embed"); else if (s && s[3].length) n = e()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder", 0).attr("src", "//player.vimeo.com/video/"+s[3]).attr("width", "640").attr("height", "360"); else if (l && l[2].length) n = e()("<iframe>").attr("frameborder", 0).attr("src", "//www.dailymotion.com/embed/video/"+l[2]).attr("width", "640").attr("height", "360"); else if (c && c[1].length) n = e()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder", 0).attr("height", "498").attr("width", "510").attr("src", "//player.youku.com/embed/"+c[1]); else if (u && u[0].length) {
                            var x = 0;
                            "undefined" !== u[2] && (x = u[2]);
                            var S = 0;
                            "undefined" !== u[3] && (S = u[3]);
                            var T = 0;
                            "undefined" !== u[4] && (T = u[4]);
                            var E = 0;
                            "undefined" !== u[5] && (E = u[5]);
                            var I = 0;
                            "undefined" !== u[6] && (I = u[6]), n = e()('<iframe allowfullscreen sandbox="allow-same-origin allow-scripts allow-popups">').attr("frameborder", 0).attr("src", "//"+u[1]+"/videos/embed/"+u[2]+"?loop="+T+"&autoplay="+E+"&muted="+I+(x > 0 ? "&start="+x : "")+(S > 0 ? "&end="+b : "")).attr("width", "560").attr("height", "315")
                        } else if (d && d[1].length || h && h[2].length) {
                            var $ = d && d[1].length ? d[1] : h[2];
                            n = e()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder", 0).attr("height", "310").attr("width", "500").attr("src", "https://v.qq.com/txp/iframe/player.html?vid="+$+"&amp;auto=0")
                        } else if (f || p || m) n = e()("<video controls>").attr("src", t).attr("width", "640").attr("height", "360"); else {
                            if (!v || !v[0].length) return !1;
                            n = e()("<iframe>").attr("frameborder", 0).attr("src", "https://www.facebook.com/plugins/video.php?href="+encodeURIComponent(v[0])+"&show_text=0&width=560").attr("width", "560").attr("height", "301").attr("scrolling", "no").attr("allowtransparency", "true")
                        }
                        return n.addClass("note-video-clip"), n[0]
                    }
                }, {
                    key: "show", value: function () {
                        var t = this, e = this.context.invoke("editor.getSelectedText");
                        this.context.invoke("editor.saveRange"), this.showVideoDialog(e).then((function (e) {
                            t.ui.hideDialog(t.$dialog), t.context.invoke("editor.restoreRange");
                            var n = t.createVideoNode(e);
                            n && t.context.invoke("editor.insertNode", n)
                        })).fail((function () {
                            t.context.invoke("editor.restoreRange")
                        }))
                    }
                }, {
                    key: "showVideoDialog", value: function () {
                        var t = this;
                        return e().Deferred((function (e) {
                            var n = t.$dialog.find(".note-video-url"), o = t.$dialog.find(".note-video-btn");
                            t.ui.onDialogShown(t.$dialog, (function () {
                                t.context.triggerEvent("dialog.shown"), n.on("input paste propertychange", (function () {
                                    t.ui.toggleBtn(o, n.val())
                                })), f.isSupportTouch || n.trigger("focus"), o.click((function (t) {
                                    t.preventDefault(), e.resolve(n.val())
                                })), t.bindEnterKey(n, o)
                            })), t.ui.onDialogHidden(t.$dialog, (function () {
                                n.off(), o.off(), "pending" === e.state() && e.reject()
                            })), t.ui.showDialog(t.$dialog)
                        }))
                    }
                }]) && be(n.prototype, o), i && be(n, i), t
            }();

            function ke(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var we = function () {
                function t(n) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.$body = e()(document.body), this.$editor = n.layoutInfo.editor, this.options = n.options, this.lang = this.options.langInfo
                }

                var n, o, i;
                return n = t, o = [{
                    key: "initialize", value: function () {
                        var t = this.options.dialogsInBody ? this.$body : this.options.container,
                            e = ['<p class="text-center">', '<a href="http://summernote.org/" target="_blank" rel="noopener noreferrer">Summernote 0.8.20</a> · ', '<a href="https://github.com/summernote/summernote" target="_blank" rel="noopener noreferrer">Project</a> · ', '<a href="https://github.com/summernote/summernote/issues" target="_blank" rel="noopener noreferrer">Issues</a>', "</p>"].join("");
                        this.$dialog = this.ui.dialog({
                            title: this.lang.options.help,
                            fade: this.options.dialogsFade,
                            body: this.createShortcutList(),
                            footer: e,
                            callback: function (t) {
                                t.find(".modal-body,.note-modal-body").css({"max-height": 300, overflow: "scroll"})
                            }
                        }).render().appendTo(t)
                    }
                }, {
                    key: "destroy", value: function () {
                        this.ui.hideDialog(this.$dialog), this.$dialog.remove()
                    }
                }, {
                    key: "createShortcutList", value: function () {
                        var t = this, n = this.options.keyMap[f.isMac ? "mac" : "pc"];
                        return Object.keys(n).map((function (o) {
                            var i = n[o], r = e()('<div><div class="help-list-item"></div></div>');
                            return r.append(e()("<label><kbd>"+o+"</kdb></label>").css({
                                width: 180,
                                "margin-right": 10
                            })).append(e()("<span></span>").html(t.context.memo("help."+i) || i)), r.html()
                        })).join("")
                    }
                }, {
                    key: "showHelpDialog", value: function () {
                        var t = this;
                        return e().Deferred((function (e) {
                            t.ui.onDialogShown(t.$dialog, (function () {
                                t.context.triggerEvent("dialog.shown"), e.resolve()
                            })), t.ui.showDialog(t.$dialog)
                        })).promise()
                    }
                }, {
                    key: "show", value: function () {
                        var t = this;
                        this.context.invoke("editor.saveRange"), this.showHelpDialog().then((function () {
                            t.context.invoke("editor.restoreRange")
                        }))
                    }
                }], o && ke(n.prototype, o), i && ke(n, i), t
            }();

            function Ce(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var xe = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.options = n.options, this.hidable = !0, this.onContextmenu = !1, this.pageX = null, this.pageY = null, this.events = {
                        "summernote.contextmenu": function (t) {
                            o.options.editing && (t.preventDefault(), t.stopPropagation(), o.onContextmenu = !0, o.update(!0))
                        }, "summernote.mousedown": function (t, e) {
                            o.pageX = e.pageX, o.pageY = e.pageY
                        }, "summernote.keyup summernote.mouseup summernote.scroll": function (t, e) {
                            o.options.editing && !o.onContextmenu && (o.pageX = e.pageX, o.pageY = e.pageY, o.update()), o.onContextmenu = !1
                        }, "summernote.disable summernote.change summernote.dialog.shown summernote.blur": function () {
                            o.hide()
                        }, "summernote.focusout": function () {
                            o.$popover.is(":active,:focus") || o.hide()
                        }
                    }
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "shouldInitialize", value: function () {
                        return this.options.airMode && !k.isEmpty(this.options.popover.air)
                    }
                }, {
                    key: "initialize", value: function () {
                        var t = this;
                        this.$popover = this.ui.popover({className: "note-air-popover"}).render().appendTo(this.options.container);
                        var e = this.$popover.find(".popover-content");
                        this.context.invoke("buttons.build", e, this.options.popover.air), this.$popover.on("mousedown", (function () {
                            t.hidable = !1
                        })), this.$popover.on("mouseup", (function () {
                            t.hidable = !0
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$popover.remove()
                    }
                }, {
                    key: "update", value: function (t) {
                        var n = this.context.invoke("editor.currentStyle");
                        if (!n.range || n.range.isCollapsed() && !t) this.hide(); else {
                            var o = {left: this.pageX, top: this.pageY}, i = e()(this.options.container).offset();
                            o.top -= i.top, o.left -= i.left, this.$popover.css({
                                display: "block",
                                left: Math.max(o.left, 0)+ -5,
                                top: o.top+5
                            }), this.context.invoke("buttons.updateCurrentStyle", this.$popover)
                        }
                    }
                }, {
                    key: "updateCodeview", value: function (t) {
                        this.ui.toggleBtnActive(this.$popover.find(".btn-codeview"), t), t && this.hide()
                    }
                }, {
                    key: "hide", value: function () {
                        this.hidable && this.$popover.hide()
                    }
                }]) && Ce(n.prototype, o), i && Ce(n, i), t
            }();

            function Se(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            var Te = function () {
                function t(n) {
                    var o = this;
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.context = n, this.ui = e().summernote.ui, this.$editable = n.layoutInfo.editable, this.options = n.options, this.hint = this.options.hint || [], this.direction = this.options.hintDirection || "bottom", this.hints = Array.isArray(this.hint) ? this.hint : [this.hint], this.events = {
                        "summernote.keyup": function (t, e) {
                            e.isDefaultPrevented() || o.handleKeyup(e)
                        }, "summernote.keydown": function (t, e) {
                            o.handleKeydown(e)
                        }, "summernote.disable summernote.dialog.shown summernote.blur": function () {
                            o.hide()
                        }
                    }
                }

                var n, o, i;
                return n = t, o = [{
                    key: "shouldInitialize", value: function () {
                        return this.hints.length > 0
                    }
                }, {
                    key: "initialize", value: function () {
                        var t = this;
                        this.lastWordRange = null, this.matchingWord = null, this.$popover = this.ui.popover({
                            className: "note-hint-popover",
                            hideArrow: !0,
                            direction: ""
                        }).render().appendTo(this.options.container), this.$popover.hide(), this.$content = this.$popover.find(".popover-content,.note-popover-content"), this.$content.on("click", ".note-hint-item", (function (n) {
                            t.$content.find(".active").removeClass("active"), e()(n.currentTarget).addClass("active"), t.replace()
                        })), this.$popover.on("mousedown", (function (t) {
                            t.preventDefault()
                        }))
                    }
                }, {
                    key: "destroy", value: function () {
                        this.$popover.remove()
                    }
                }, {
                    key: "selectItem", value: function (t) {
                        this.$content.find(".active").removeClass("active"), t.addClass("active"), this.$content[0].scrollTop = t[0].offsetTop-this.$content.innerHeight() / 2
                    }
                }, {
                    key: "moveDown", value: function () {
                        var t = this.$content.find(".note-hint-item.active"), e = t.next();
                        if (e.length) this.selectItem(e); else {
                            var n = t.parent().next();
                            n.length || (n = this.$content.find(".note-hint-group").first()), this.selectItem(n.find(".note-hint-item").first())
                        }
                    }
                }, {
                    key: "moveUp", value: function () {
                        var t = this.$content.find(".note-hint-item.active"), e = t.prev();
                        if (e.length) this.selectItem(e); else {
                            var n = t.parent().prev();
                            n.length || (n = this.$content.find(".note-hint-group").last()), this.selectItem(n.find(".note-hint-item").last())
                        }
                    }
                }, {
                    key: "replace", value: function () {
                        var t = this.$content.find(".note-hint-item.active");
                        if (t.length) {
                            var n = this.nodeFromItem(t);
                            if (null !== this.matchingWord && 0 === this.matchingWord.length) this.lastWordRange.so = this.lastWordRange.eo; else if (null !== this.matchingWord && this.matchingWord.length > 0 && !this.lastWordRange.isCollapsed()) {
                                var o = this.lastWordRange.eo-this.lastWordRange.so-this.matchingWord.length;
                                o > 0 && (this.lastWordRange.so += o)
                            }
                            if (this.lastWordRange.insertNode(n), "next" === this.options.hintSelect) {
                                var i = document.createTextNode("");
                                e()(n).after(i), yt.createFromNodeBefore(i).select()
                            } else yt.createFromNodeAfter(n).select();
                            this.lastWordRange = null, this.hide(), this.context.invoke("editor.focus"), this.context.triggerEvent("change", this.$editable.html(), this.$editable)
                        }
                    }
                }, {
                    key: "nodeFromItem", value: function (t) {
                        var e = this.hints[t.data("index")], n = t.data("item"), o = e.content ? e.content(n) : n;
                        return "string" == typeof o && (o = ht.createText(o)), o
                    }
                }, {
                    key: "createItemTemplates", value: function (t, n) {
                        var o = this.hints[t];
                        return n.map((function (n) {
                            var i = e()('<div class="note-hint-item"></div>');
                            return i.append(o.template ? o.template(n) : n+""), i.data({index: t, item: n}), i
                        }))
                    }
                }, {
                    key: "handleKeydown", value: function (t) {
                        this.$popover.is(":visible") && (t.keyCode === wt.code.ENTER ? (t.preventDefault(), this.replace()) : t.keyCode === wt.code.UP ? (t.preventDefault(), this.moveUp()) : t.keyCode === wt.code.DOWN && (t.preventDefault(), this.moveDown()))
                    }
                }, {
                    key: "searchKeyword", value: function (t, e, n) {
                        var o = this.hints[t];
                        if (o && o.match.test(e) && o.search) {
                            var i = o.match.exec(e);
                            this.matchingWord = i[0], o.search(i[1], n)
                        } else n()
                    }
                }, {
                    key: "createGroup", value: function (t, n) {
                        var o = this, i = e()('<div class="note-hint-group note-hint-group-'+t+'"></div>');
                        return this.searchKeyword(t, n, (function (e) {
                            (e = e || []).length && (i.html(o.createItemTemplates(t, e)), o.show())
                        })), i
                    }
                }, {
                    key: "handleKeyup", value: function (t) {
                        var n = this;
                        if (!k.contains([wt.code.ENTER, wt.code.UP, wt.code.DOWN], t.keyCode)) {
                            var o, i, r = this.context.invoke("editor.getLastRange");
                            if ("words" === this.options.hintMode) {
                                if (o = r.getWordsRange(r), i = o.toString(), this.hints.forEach((function (t) {
                                    if (t.match.test(i)) return o = r.getWordsMatchRange(t.match), !1
                                })), !o) return void this.hide();
                                i = o.toString()
                            } else o = r.getWordRange(), i = o.toString();
                            if (this.hints.length && i) {
                                this.$content.empty();
                                var a = m.rect2bnd(k.last(o.getClientRects())),
                                    s = e()(this.options.container).offset();
                                a && (a.top -= s.top, a.left -= s.left, this.$popover.hide(), this.lastWordRange = o, this.hints.forEach((function (t, e) {
                                    t.match.test(i) && n.createGroup(e, i).appendTo(n.$content)
                                })), this.$content.find(".note-hint-item:first").addClass("active"), "top" === this.direction ? this.$popover.css({
                                    left: a.left,
                                    top: a.top-this.$popover.outerHeight()-5
                                }) : this.$popover.css({left: a.left, top: a.top+a.height+5}))
                            } else this.hide()
                        }
                    }
                }, {
                    key: "show", value: function () {
                        this.$popover.show()
                    }
                }, {
                    key: "hide", value: function () {
                        this.$popover.hide()
                    }
                }], o && Se(n.prototype, o), i && Se(n, i), t
            }();

            function Ee(t) {
                return Ee = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
                    return typeof t
                } : function (t) {
                    return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t
                }, Ee(t)
            }

            function Ie(t, e) {
                for (var n = 0; n < e.length; n++) {
                    var o = e[n];
                    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
                }
            }

            e().summernote = e().extend(e().summernote, {
                version: "0.8.20", plugins: {}, dom: ht, range: yt, lists: k, options: {
                    langInfo: e().summernote.lang["en-US"],
                    editing: !0,
                    modules: {
                        editor: Ft,
                        clipboard: Ht,
                        dropzone: Bt,
                        codeview: jt,
                        statusbar: Kt,
                        fullscreen: qt,
                        handle: Gt,
                        hintPopover: Te,
                        autoLink: Qt,
                        autoSync: Jt,
                        autoReplace: ee,
                        placeholder: oe,
                        buttons: re,
                        toolbar: se,
                        linkDialog: ce,
                        linkPopover: de,
                        imageDialog: fe,
                        imagePopover: me,
                        tablePopover: ge,
                        videoDialog: ye,
                        helpDialog: we,
                        airPopover: xe
                    },
                    buttons: {},
                    lang: "en-US",
                    followingToolbar: !1,
                    toolbarPosition: "top",
                    otherStaticBar: "",
                    codeviewKeepButton: !1,
                    toolbar: [["style", ["style"]], ["font", ["bold", "underline", "clear"]], ["fontname", ["fontname"]], ["color", ["color"]], ["para", ["ul", "ol", "paragraph"]], ["table", ["table"]], ["insert", ["link", "picture", "video"]], ["view", ["fullscreen", "codeview", "help"]]],
                    popatmouse: !0,
                    popover: {
                        image: [["resize", ["resizeFull", "resizeHalf", "resizeQuarter", "resizeNone"]], ["float", ["floatLeft", "floatRight", "floatNone"]], ["remove", ["removeMedia"]]],
                        link: [["link", ["linkDialogShow", "unlink"]]],
                        table: [["add", ["addRowDown", "addRowUp", "addColLeft", "addColRight"]], ["delete", ["deleteRow", "deleteCol", "deleteTable"]]],
                        air: [["color", ["color"]], ["font", ["bold", "underline", "clear"]], ["para", ["ul", "paragraph"]], ["table", ["table"]], ["insert", ["link", "picture"]], ["view", ["fullscreen", "codeview"]]]
                    },
                    airMode: !1,
                    overrideContextMenu: !1,
                    width: null,
                    height: null,
                    linkTargetBlank: !0,
                    useProtocol: !0,
                    defaultProtocol: "http://",
                    focus: !1,
                    tabDisabled: !1,
                    tabSize: 4,
                    styleWithCSS: !1,
                    shortcuts: !0,
                    textareaAutoSync: !0,
                    tooltip: "auto",
                    container: null,
                    maxTextLength: 0,
                    blockquoteBreakingLevel: 2,
                    spellCheck: !0,
                    disableGrammar: !1,
                    placeholder: null,
                    inheritPlaceholder: !1,
                    recordEveryKeystroke: !1,
                    historyLimit: 200,
                    showDomainOnlyForAutolink: !1,
                    hintMode: "word",
                    hintSelect: "after",
                    hintDirection: "bottom",
                    styleTags: ["p", "blockquote", "pre", "h1", "h2", "h3", "h4", "h5", "h6"],
                    fontNames: ["Arial", "Arial Black", "Comic Sans MS", "Courier New", "Helvetica Neue", "Helvetica", "Impact", "Lucida Grande", "Tahoma", "Times New Roman", "Verdana"],
                    fontNamesIgnoreCheck: [],
                    addDefaultFonts: !0,
                    fontSizes: ["8", "9", "10", "11", "12", "14", "18", "24", "36"],
                    fontSizeUnits: ["px", "pt"],
                    colors: [["#000000", "#424242", "#636363", "#9C9C94", "#CEC6CE", "#EFEFEF", "#F7F7F7", "#FFFFFF"], ["#FF0000", "#FF9C00", "#FFFF00", "#00FF00", "#00FFFF", "#0000FF", "#9C00FF", "#FF00FF"], ["#F7C6CE", "#FFE7CE", "#FFEFC6", "#D6EFD6", "#CEDEE7", "#CEE7F7", "#D6D6E7", "#E7D6DE"], ["#E79C9C", "#FFC69C", "#FFE79C", "#B5D6A5", "#A5C6CE", "#9CC6EF", "#B5A5D6", "#D6A5BD"], ["#E76363", "#F7AD6B", "#FFD663", "#94BD7B", "#73A5AD", "#6BADDE", "#8C7BC6", "#C67BA5"], ["#CE0000", "#E79439", "#EFC631", "#6BA54A", "#4A7B8C", "#3984C6", "#634AA5", "#A54A7B"], ["#9C0000", "#B56308", "#BD9400", "#397B21", "#104A5A", "#085294", "#311873", "#731842"], ["#630000", "#7B3900", "#846300", "#295218", "#083139", "#003163", "#21104A", "#4A1031"]],
                    colorsName: [["Black", "Tundora", "Dove Gray", "Star Dust", "Pale Slate", "Gallery", "Alabaster", "White"], ["Red", "Orange Peel", "Yellow", "Green", "Cyan", "Blue", "Electric Violet", "Magenta"], ["Azalea", "Karry", "Egg White", "Zanah", "Botticelli", "Tropical Blue", "Mischka", "Twilight"], ["Tonys Pink", "Peach Orange", "Cream Brulee", "Sprout", "Casper", "Perano", "Cold Purple", "Careys Pink"], ["Mandy", "Rajah", "Dandelion", "Olivine", "Gulf Stream", "Viking", "Blue Marguerite", "Puce"], ["Guardsman Red", "Fire Bush", "Golden Dream", "Chelsea Cucumber", "Smalt Blue", "Boston Blue", "Butterfly Bush", "Cadillac"], ["Sangria", "Mai Tai", "Buddha Gold", "Forest Green", "Eden", "Venice Blue", "Meteorite", "Claret"], ["Rosewood", "Cinnamon", "Olive", "Parsley", "Tiber", "Midnight Blue", "Valentino", "Loulou"]],
                    colorButton: {foreColor: "#000000", backColor: "#FFFF00"},
                    lineHeights: ["1.0", "1.2", "1.4", "1.5", "1.6", "1.8", "2.0", "3.0"],
                    tableClassName: "table table-bordered",
                    insertTableMaxSize: {col: 10, row: 10},
                    dialogsInBody: !1,
                    dialogsFade: !1,
                    maximumImageFileSize: null,
                    acceptImageFileTypes: "image/*",
                    callbacks: {
                        onBeforeCommand: null,
                        onBlur: null,
                        onBlurCodeview: null,
                        onChange: null,
                        onChangeCodeview: null,
                        onDialogShown: null,
                        onEnter: null,
                        onFocus: null,
                        onImageLinkInsert: null,
                        onImageUpload: null,
                        onImageUploadError: null,
                        onInit: null,
                        onKeydown: null,
                        onKeyup: null,
                        onMousedown: null,
                        onMouseup: null,
                        onPaste: null,
                        onScroll: null
                    },
                    codemirror: {mode: "text/html", htmlMode: !0, lineNumbers: !0},
                    codeviewFilter: !0,
                    codeviewFilterRegex: /<\/*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|ilayer|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|t(?:itle|extarea)|xml)[^>]*?>/gi,
                    codeviewIframeFilter: !0,
                    codeviewIframeWhitelistSrc: [],
                    codeviewIframeWhitelistSrcBase: ["www.youtube.com", "www.youtube-nocookie.com", "www.facebook.com", "vine.co", "instagram.com", "player.vimeo.com", "www.dailymotion.com", "player.youku.com", "jumpingbean.tv", "v.qq.com"],
                    keyMap: {
                        pc: {
                            ESC: "escape",
                            ENTER: "insertParagraph",
                            "CTRL+Z": "undo",
                            "CTRL+Y": "redo",
                            TAB: "tab",
                            "SHIFT+TAB": "untab",
                            "CTRL+B": "bold",
                            "CTRL+I": "italic",
                            "CTRL+U": "underline",
                            "CTRL+SHIFT+S": "strikethrough",
                            "CTRL+BACKSLASH": "removeFormat",
                            "CTRL+SHIFT+L": "justifyLeft",
                            "CTRL+SHIFT+E": "justifyCenter",
                            "CTRL+SHIFT+R": "justifyRight",
                            "CTRL+SHIFT+J": "justifyFull",
                            "CTRL+SHIFT+NUM7": "insertUnorderedList",
                            "CTRL+SHIFT+NUM8": "insertOrderedList",
                            "CTRL+LEFTBRACKET": "outdent",
                            "CTRL+RIGHTBRACKET": "indent",
                            "CTRL+NUM0": "formatPara",
                            "CTRL+NUM1": "formatH1",
                            "CTRL+NUM2": "formatH2",
                            "CTRL+NUM3": "formatH3",
                            "CTRL+NUM4": "formatH4",
                            "CTRL+NUM5": "formatH5",
                            "CTRL+NUM6": "formatH6",
                            "CTRL+ENTER": "insertHorizontalRule",
                            "CTRL+K": "linkDialog.show"
                        },
                        mac: {
                            ESC: "escape",
                            ENTER: "insertParagraph",
                            "CMD+Z": "undo",
                            "CMD+SHIFT+Z": "redo",
                            TAB: "tab",
                            "SHIFT+TAB": "untab",
                            "CMD+B": "bold",
                            "CMD+I": "italic",
                            "CMD+U": "underline",
                            "CMD+SHIFT+S": "strikethrough",
                            "CMD+BACKSLASH": "removeFormat",
                            "CMD+SHIFT+L": "justifyLeft",
                            "CMD+SHIFT+E": "justifyCenter",
                            "CMD+SHIFT+R": "justifyRight",
                            "CMD+SHIFT+J": "justifyFull",
                            "CMD+SHIFT+NUM7": "insertUnorderedList",
                            "CMD+SHIFT+NUM8": "insertOrderedList",
                            "CMD+LEFTBRACKET": "outdent",
                            "CMD+RIGHTBRACKET": "indent",
                            "CMD+NUM0": "formatPara",
                            "CMD+NUM1": "formatH1",
                            "CMD+NUM2": "formatH2",
                            "CMD+NUM3": "formatH3",
                            "CMD+NUM4": "formatH4",
                            "CMD+NUM5": "formatH5",
                            "CMD+NUM6": "formatH6",
                            "CMD+ENTER": "insertHorizontalRule",
                            "CMD+K": "linkDialog.show"
                        }
                    },
                    icons: {
                        align: "note-icon-align",
                        alignCenter: "note-icon-align-center",
                        alignJustify: "note-icon-align-justify",
                        alignLeft: "note-icon-align-left",
                        alignRight: "note-icon-align-right",
                        rowBelow: "note-icon-row-below",
                        colBefore: "note-icon-col-before",
                        colAfter: "note-icon-col-after",
                        rowAbove: "note-icon-row-above",
                        rowRemove: "note-icon-row-remove",
                        colRemove: "note-icon-col-remove",
                        indent: "note-icon-align-indent",
                        outdent: "note-icon-align-outdent",
                        arrowsAlt: "note-icon-arrows-alt",
                        bold: "note-icon-bold",
                        caret: "note-icon-caret",
                        circle: "note-icon-circle",
                        close: "note-icon-close",
                        code: "note-icon-code",
                        eraser: "note-icon-eraser",
                        floatLeft: "note-icon-float-left",
                        floatRight: "note-icon-float-right",
                        font: "note-icon-font",
                        frame: "note-icon-frame",
                        italic: "note-icon-italic",
                        link: "note-icon-link",
                        unlink: "note-icon-chain-broken",
                        magic: "note-icon-magic",
                        menuCheck: "note-icon-menu-check",
                        minus: "note-icon-minus",
                        orderedlist: "note-icon-orderedlist",
                        pencil: "note-icon-pencil",
                        picture: "note-icon-picture",
                        question: "note-icon-question",
                        redo: "note-icon-redo",
                        rollback: "note-icon-rollback",
                        square: "note-icon-square",
                        strikethrough: "note-icon-strikethrough",
                        subscript: "note-icon-subscript",
                        superscript: "note-icon-superscript",
                        table: "note-icon-table",
                        textHeight: "note-icon-text-height",
                        trash: "note-icon-trash",
                        underline: "note-icon-underline",
                        undo: "note-icon-undo",
                        unorderedlist: "note-icon-unorderedlist",
                        video: "note-icon-video"
                    }
                }
            });
            var $e = function () {
                function t(e, n, o, i) {
                    !function (t, e) {
                        if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                    }(this, t), this.markup = e, this.children = n, this.options = o, this.callback = i
                }

                var n, o, i;
                return n = t, (o = [{
                    key: "render", value: function (t) {
                        var n = e()(this.markup);
                        if (this.options && this.options.contents && n.html(this.options.contents), this.options && this.options.className && n.addClass(this.options.className), this.options && this.options.data && e().each(this.options.data, (function (t, e) {
                            n.attr("data-"+t, e)
                        })), this.options && this.options.click && n.on("click", this.options.click), this.children) {
                            var o = n.find(".note-children-container");
                            this.children.forEach((function (t) {
                                t.render(o.length ? o : n)
                            }))
                        }
                        return this.callback && this.callback(n, this.options), this.options && this.options.callback && this.options.callback(n), t && t.append(n), n
                    }
                }]) && Ie(n.prototype, o), i && Ie(n, i), t
            }();
            const Ne = function (t, e) {
                return function () {
                    var n = "object" === Ee(arguments[1]) ? arguments[1] : arguments[0],
                        o = Array.isArray(arguments[0]) ? arguments[0] : [];
                    return n && n.children && (o = n.children), new $e(t, o, n, e)
                }
            };

            function Pe(t) {
                return Pe = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
                    return typeof t
                } : function (t) {
                    return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t
                }, Pe(t)
            }

            var Re = Ne('<div class="note-editor note-frame card"/>'),
                Le = Ne('<div class="note-toolbar card-header" role="toolbar"/>'),
                Ae = Ne('<div class="note-editing-area"/>'),
                Fe = Ne('<textarea class="note-codable" aria-multiline="true"/>'),
                De = Ne('<div class="note-editable card-block" contentEditable="true" role="textbox" aria-multiline="true"/>'),
                He = Ne(['<output class="note-status-output" role="status" aria-live="polite"></output>', '<div class="note-statusbar" role="status">', '<div class="note-resizebar" aria-label="Resize">', '<div class="note-icon-bar"></div>', '<div class="note-icon-bar"></div>', '<div class="note-icon-bar"></div>', "</div>", "</div>"].join("")),
                ze = Ne('<div class="note-editor note-airframe"/>'),
                Be = Ne(['<div class="note-editable" contentEditable="true" role="textbox" aria-multiline="true"></div>', '<output class="note-status-output" role="status" aria-live="polite"></output>'].join("")),
                Me = Ne('<div class="note-btn-group btn-group">'),
                Oe = Ne('<div class="note-dropdown-menu dropdown-menu" role="list">', (function (t, e) {
                    var n = Array.isArray(e.items) ? e.items.map((function (t) {
                        var n = "string" == typeof t ? t : t.value || "", o = e.template ? e.template(t) : t,
                            i = "object" === Pe(t) ? t.option : void 0;
                        return '<a class="dropdown-item" href="#" '+('data-value="'+n+'"'+(void 0 !== i ? ' data-option="'+i+'"' : ""))+' role="listitem" aria-label="'+n+'">'+o+"</a>"
                    })).join("") : e.items;
                    t.html(n).attr({"aria-label": e.title}), e && e.codeviewKeepButton && t.addClass("note-codeview-keep")
                })), Ue = function (t) {
                    return t
                }, je = Ne('<div class="note-dropdown-menu dropdown-menu note-check" role="list">', (function (t, e) {
                    var n = Array.isArray(e.items) ? e.items.map((function (t) {
                        var n = "string" == typeof t ? t : t.value || "", o = e.template ? e.template(t) : t;
                        return '<a class="dropdown-item" href="#" data-value="'+n+'" role="listitem" aria-label="'+t+'">'+qe(e.checkClassName)+" "+o+"</a>"
                    })).join("") : e.items;
                    t.html(n).attr({"aria-label": e.title}), e && e.codeviewKeepButton && t.addClass("note-codeview-keep")
                })),
                We = Ne('<div class="modal note-modal" aria-hidden="false" tabindex="-1" role="dialog"/>', (function (t, e) {
                    e.fade && t.addClass("fade"), t.attr({"aria-label": e.title}), t.html(['<div class="modal-dialog">', '<div class="modal-content">', e.title ? '<div class="modal-header"><h4 class="modal-title">'+e.title+'</h4><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" aria-hidden="true"></button></div>' : "", '<div class="modal-body">'+e.body+"</div>", e.footer ? '<div class="modal-footer">'+e.footer+"</div>" : "", "</div>", "</div>"].join(""))
                })),
                Ke = Ne(['<div class="note-popover popover show">', '<div class="popover-arrow"></div>', '<div class="popover-body note-children-container"></div>', "</div>"].join(""), (function (t, e) {
                    var n = void 0 !== e.direction ? e.direction : "bottom";
                    t.attr("data-bs-placement", n), e.hideArrow && t.find(".popover-arrow").hide()
                })), Ve = Ne('<div class="form-check"></div>', (function (t, e) {
                    t.html(['<label class="form-check-label"'+(e.id ? ' for="note-'+e.id+'"' : "")+">", '<input type="checkbox" class="form-check-input"'+(e.id ? ' id="note-'+e.id+'"' : ""), e.checked ? " checked" : "", ' aria-label="'+(e.text ? e.text : "")+'"', ' aria-checked="'+(e.checked ? "true" : "false")+'"/>', " "+(e.text ? e.text : "")+"</label>"].join(""))
                })), qe = function (t, e) {
                    return t.match(/^</) ? t : "<"+(e = e || "i")+' class="'+t+'"></'+e+">"
                };
            e().summernote = e().extend(e().summernote, {
                ui_template: function (t) {
                    return {
                        editor: Re,
                        toolbar: Le,
                        editingArea: Ae,
                        codable: Fe,
                        editable: De,
                        statusbar: He,
                        airEditor: ze,
                        airEditable: Be,
                        buttonGroup: Me,
                        dropdown: Oe,
                        dropdownButtonContents: Ue,
                        dropdownCheck: je,
                        dialog: We,
                        popover: Ke,
                        icon: qe,
                        checkbox: Ve,
                        options: t,
                        palette: function (e, n) {
                            return Ne('<div class="note-color-palette"/>', (function (e, n) {
                                for (var o = [], i = 0, r = n.colors.length; i < r; i++) {
                                    for (var a = n.eventName, s = n.colors[i], l = n.colorsName[i], c = [], u = 0, d = s.length; u < d; u++) {
                                        var h = s[u], f = l[u];
                                        c.push(['<button type="button" class="note-color-btn"', 'style="background-color:', h, '" ', 'data-event="', a, '" ', 'data-value="', h, '" ', 'title="', f, '" ', 'aria-label="', f, '" ', 'data-toggle="button" tabindex="-1"></button>'].join(""))
                                    }
                                    o.push('<div class="note-color-row">'+c.join("")+"</div>")
                                }
                                e.html(o.join("")), n.tooltip && e.find(".note-color-btn").tooltip({
                                    container: n.container || t.container,
                                    trigger: "hover",
                                    placement: "bottom"
                                })
                            }))(e, n)
                        },
                        button: function (n, o) {
                            return Ne('<button type="button" class="note-btn btn btn-light btn-sm" tabindex="-1">', (function (n, o) {
                                o && o.tooltip && n.attr({
                                    title: o.tooltip,
                                    "aria-label": o.tooltip
                                }).tooltip({
                                    container: o.container || t.container,
                                    trigger: "hover",
                                    placement: "bottom"
                                }).on("click", (function (t) {
                                    e()(t.currentTarget).tooltip("hide")
                                })), o && o.codeviewButton && n.addClass("note-codeview-keep")
                            }))(n, o)
                        },
                        toggleBtn: function (t, e) {
                            t.toggleClass("disabled", !e), t.attr("disabled", !e)
                        },
                        toggleBtnActive: function (t, e) {
                            t.toggleClass("active", e)
                        },
                        onDialogShown: function (t, e) {
                            t.one("shown.bs.modal", e)
                        },
                        onDialogHidden: function (t, e) {
                            t.one("hidden.bs.modal", e)
                        },
                        showDialog: function (t) {
                            t.modal("show")
                        },
                        hideDialog: function (t) {
                            t.modal("hide")
                        },
                        createLayout: function (e) {
                            var n = (t.airMode ? ze([Ae([Fe(), Be()])]) : "bottom" === t.toolbarPosition ? Re([Ae([Fe(), De()]), Le(), He()]) : Re([Le(), Ae([Fe(), De()]), He()])).render();
                            return n.insertAfter(e), {
                                note: e,
                                editor: n,
                                toolbar: n.find(".note-toolbar"),
                                editingArea: n.find(".note-editing-area"),
                                editable: n.find(".note-editable"),
                                codable: n.find(".note-codable"),
                                statusbar: n.find(".note-statusbar")
                            }
                        },
                        removeLayout: function (t, e) {
                            t.html(e.editable.html()), e.editor.remove(), t.show()
                        }
                    }
                }, interface: "bs5"
            }), e().summernote.options.styleTags = ["p", {
                title: "Blockquote",
                tag: "blockquote",
                className: "blockquote",
                value: "blockquote"
            }, "pre", "h1", "h2", "h3", "h4", "h5", "h6"]
        })(), i
    })()
}));
