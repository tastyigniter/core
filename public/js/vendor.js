/**
 * Selectize (v0.15.2)
 * https://selectize.dev
 *
 * Copyright (c) 2013-2015 Brian Reavis & contributors
 * Copyright (c) 2020-2022 Selectize Team & contributors
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this
 * file except in compliance with the License. You may obtain a copy of the License at:
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF
 * ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 *
 * @author Brian Reavis <brian@thirdroute.com>
 * @author Ris Adams <selectize@risadams.com>
 */
(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery'], factory);
  } else if (typeof module === 'object' && typeof module.exports === 'object') {
    module.exports = factory(require('jquery'));
  } else {
    root.Selectize = factory(root.jQuery);
  }
}(this, function ($) {
  'use strict';
var highlight=function(t,e){var r,a;if("string"!=typeof e||e.length)return r="string"==typeof e?new RegExp(e,"i"):e,a=function(t){var e=0;if(3===t.nodeType){var n,i,o=t.data.search(r);0<=o&&0<t.data.length&&(i=t.data.match(r),(n=document.createElement("span")).className="highlight",(o=t.splitText(o)).splitText(i[0].length),i=o.cloneNode(!0),n.appendChild(i),o.parentNode.replaceChild(n,o),e=1)}else if(1===t.nodeType&&t.childNodes&&!/(script|style)/i.test(t.tagName)&&("highlight"!==t.className||"SPAN"!==t.tagName))for(var s=0;s<t.childNodes.length;++s)s+=a(t.childNodes[s]);return e},t.each(function(){a(this)})},MicroEvent=($.fn.removeHighlight=function(){return this.find("span.highlight").each(function(){this.parentNode.firstChild.nodeName;var t=this.parentNode;t.replaceChild(this.firstChild,this),t.normalize()}).end()},function(){}),MicroPlugin=(MicroEvent.prototype={on:function(t,e){this._events=this._events||{},this._events[t]=this._events[t]||[],this._events[t].push(e)},off:function(t,e){var n=arguments.length;return 0===n?delete this._events:1===n?delete this._events[t]:(this._events=this._events||{},void(t in this._events!=!1&&this._events[t].splice(this._events[t].indexOf(e),1)))},trigger:function(t){var e=this._events=this._events||{};if(t in e!=!1)for(var n=0;n<e[t].length;n++)e[t][n].apply(this,Array.prototype.slice.call(arguments,1))}},MicroEvent.mixin=function(t){for(var e=["on","off","trigger"],n=0;n<e.length;n++)t.prototype[e[n]]=MicroEvent.prototype[e[n]]},{}),utils=(MicroPlugin.mixin=function(o){o.plugins={},o.prototype.initializePlugins=function(t){var e,n,i,o=this,s=[];if(o.plugins={names:[],settings:{},requested:{},loaded:{}},utils.isArray(t))for(e=0,n=t.length;e<n;e++)"string"==typeof t[e]?s.push(t[e]):(o.plugins.settings[t[e].name]=t[e].options,s.push(t[e].name));else if(t)for(i in t)t.hasOwnProperty(i)&&(o.plugins.settings[i]=t[i],s.push(i));for(;s.length;)o.require(s.shift())},o.prototype.loadPlugin=function(t){var e=this,n=e.plugins,i=o.plugins[t];if(!o.plugins.hasOwnProperty(t))throw new Error('Unable to find "'+t+'" plugin');n.requested[t]=!0,n.loaded[t]=i.fn.apply(e,[e.plugins.settings[t]||{}]),n.names.push(t)},o.prototype.require=function(t){var e=this,n=e.plugins;if(!e.plugins.loaded.hasOwnProperty(t)){if(n.requested[t])throw new Error('Plugin has circular dependency ("'+t+'")');e.loadPlugin(t)}return n.loaded[t]},o.define=function(t,e){o.plugins[t]={name:t,fn:e}}},{isArray:Array.isArray||function(t){return"[object Array]"===Object.prototype.toString.call(t)}}),Sifter=function(t,e){this.items=t,this.settings=e||{diacritics:!0}},cmp=(Sifter.prototype.tokenize=function(t,e){if(!(t=trim(String(t||"").toLowerCase()))||!t.length)return[];for(var n,i,o=[],s=t.split(/ +/),r=0,a=s.length;r<a;r++){if(n=escape_regex(s[r]),this.settings.diacritics)for(i in DIACRITICS)DIACRITICS.hasOwnProperty(i)&&(n=n.replace(new RegExp(i,"g"),DIACRITICS[i]));e&&(n="\\b"+n),o.push({string:s[r],regex:new RegExp(n,"i")})}return o},Sifter.prototype.iterator=function(t,e){var n=is_array(t)?Array.prototype.forEach||function(t){for(var e=0,n=this.length;e<n;e++)t(this[e],e,this)}:function(t){for(var e in this)this.hasOwnProperty(e)&&t(this[e],e,this)};n.apply(t,[e])},Sifter.prototype.getScoreFunction=function(t,e){function o(t,e){var n;return!t||-1===(n=(t=String(t||"")).search(e.regex))?0:(e=e.string.length/t.length,0===n&&(e+=.5),e)}var s,r=(t=this.prepareSearch(t,e)).tokens,a=t.options.fields,l=r.length,p=t.options.nesting,c=(s=a.length)?1===s?function(t,e){return o(getattr(e,a[0],p),t)}:function(t,e){for(var n=0,i=0;n<s;n++)i+=o(getattr(e,a[n],p),t);return i/s}:function(){return 0};return l?1===l?function(t){return c(r[0],t)}:"and"===t.options.conjunction?function(t){for(var e,n=0,i=0;n<l;n++){if((e=c(r[n],t))<=0)return 0;i+=e}return i/l}:function(t){for(var e=0,n=0;e<l;e++)n+=c(r[e],t);return n/l}:function(){return 0}},Sifter.prototype.getSortFunction=function(t,n){var e,i,o,s,r,a,l,p=this,c=!(t=p.prepareSearch(t,n)).query&&n.sort_empty||n.sort,u=function(t,e){return"$score"===t?e.score:getattr(p.items[e.id],t,n.nesting)},d=[];if(c)for(e=0,i=c.length;e<i;e++)!t.query&&"$score"===c[e].field||d.push(c[e]);if(t.query){for(l=!0,e=0,i=d.length;e<i;e++)if("$score"===d[e].field){l=!1;break}l&&d.unshift({field:"$score",direction:"desc"})}else for(e=0,i=d.length;e<i;e++)if("$score"===d[e].field){d.splice(e,1);break}for(a=[],e=0,i=d.length;e<i;e++)a.push("desc"===d[e].direction?-1:1);return(s=d.length)?1===s?(o=d[0].field,r=a[0],function(t,e){return r*cmp(u(o,t),u(o,e))}):function(t,e){for(var n,i=0;i<s;i++)if(n=d[i].field,n=a[i]*cmp(u(n,t),u(n,e)))return n;return 0}:null},Sifter.prototype.prepareSearch=function(t,e){var n,i,o;return"object"==typeof t?t:(n=(e=extend({},e)).fields,i=e.sort,o=e.sort_empty,n&&!is_array(n)&&(e.fields=[n]),i&&!is_array(i)&&(e.sort=[i]),o&&!is_array(o)&&(e.sort_empty=[o]),{options:e,query:String(t||"").toLowerCase(),tokens:this.tokenize(t,e.respect_word_boundaries),total:0,items:[]})},Sifter.prototype.search=function(t,n){var i,o,e=this,s=this.prepareSearch(t,n);return n=s.options,t=s.query,o=n.score||e.getScoreFunction(s),t.length?e.iterator(e.items,function(t,e){i=o(t),(!1===n.filter||0<i)&&s.items.push({score:i,id:e})}):e.iterator(e.items,function(t,e){s.items.push({score:1,id:e})}),(t=e.getSortFunction(s,n))&&s.items.sort(t),s.total=s.items.length,"number"==typeof n.limit&&(s.items=s.items.slice(0,n.limit)),s},function(t,e){return"number"==typeof t&&"number"==typeof e?e<t?1:t<e?-1:0:(t=asciifold(String(t||"")),(e=asciifold(String(e||"")))<t?1:t<e?-1:0)}),extend=function(t,e){for(var n,i,o=1,s=arguments.length;o<s;o++)if(i=arguments[o])for(n in i)i.hasOwnProperty(n)&&(t[n]=i[n]);return t},getattr=function(t,e,n){if(t&&e){if(!n)return t[e];for(var i=e.split(".");i.length&&(t=t[i.shift()]););return t}},trim=function(t){return(t+"").replace(/^\s+|\s+$|/g,"")},escape_regex=function(t){return(t+"").replace(/([.?*+^$[\]\\(){}|-])/g,"\\$1")},is_array=Array.isArray||"undefined"!=typeof $&&$.isArray||function(t){return"[object Array]"===Object.prototype.toString.call(t)},DIACRITICS={a:"[aḀḁĂăÂâǍǎȺⱥȦȧẠạÄäÀàÁáĀāÃãÅåąĄÃąĄ]",b:"[b␢βΒB฿𐌁ᛒ]",c:"[cĆćĈĉČčĊċC̄c̄ÇçḈḉȻȼƇƈɕᴄＣｃ]",d:"[dĎďḊḋḐḑḌḍḒḓḎḏĐđD̦d̦ƉɖƊɗƋƌᵭᶁᶑȡᴅＤｄð]",e:"[eÉéÈèÊêḘḙĚěĔĕẼẽḚḛẺẻĖėËëĒēȨȩĘęᶒɆɇȄȅẾếỀềỄễỂểḜḝḖḗḔḕȆȇẸẹỆệⱸᴇＥｅɘǝƏƐε]",f:"[fƑƒḞḟ]",g:"[gɢ₲ǤǥĜĝĞğĢģƓɠĠġ]",h:"[hĤĥĦħḨḩẖẖḤḥḢḣɦʰǶƕ]",i:"[iÍíÌìĬĭÎîǏǐÏïḮḯĨĩĮįĪīỈỉȈȉȊȋỊịḬḭƗɨɨ̆ᵻᶖİiIıɪＩｉ]",j:"[jȷĴĵɈɉʝɟʲ]",k:"[kƘƙꝀꝁḰḱǨǩḲḳḴḵκϰ₭]",l:"[lŁłĽľĻļĹĺḶḷḸḹḼḽḺḻĿŀȽƚⱠⱡⱢɫɬᶅɭȴʟＬｌ]",n:"[nŃńǸǹŇňÑñṄṅŅņṆṇṊṋṈṉN̈n̈ƝɲȠƞᵰᶇɳȵɴＮｎŊŋ]",o:"[oØøÖöÓóÒòÔôǑǒŐőŎŏȮȯỌọƟɵƠơỎỏŌōÕõǪǫȌȍՕօ]",p:"[pṔṕṖṗⱣᵽƤƥᵱ]",q:"[qꝖꝗʠɊɋꝘꝙq̃]",r:"[rŔŕɌɍŘřŖŗṘṙȐȑȒȓṚṛⱤɽ]",s:"[sŚśṠṡṢṣꞨꞩŜŝŠšŞşȘșS̈s̈]",t:"[tŤťṪṫŢţṬṭƮʈȚțṰṱṮṯƬƭ]",u:"[uŬŭɄʉỤụÜüÚúÙùÛûǓǔŰűŬŭƯưỦủŪūŨũŲųȔȕ∪]",v:"[vṼṽṾṿƲʋꝞꝟⱱʋ]",w:"[wẂẃẀẁŴŵẄẅẆẇẈẉ]",x:"[xẌẍẊẋχ]",y:"[yÝýỲỳŶŷŸÿỸỹẎẏỴỵɎɏƳƴ]",z:"[zŹźẐẑŽžŻżẒẓẔẕƵƶ]"},asciifold=function(){var t,e,n,i,o="",s={};for(n in DIACRITICS)if(DIACRITICS.hasOwnProperty(n))for(o+=i=DIACRITICS[n].substring(2,DIACRITICS[n].length-1),t=0,e=i.length;t<e;t++)s[i.charAt(t)]=n;var r=new RegExp("["+o+"]","g");return function(t){return t.replace(r,function(t){return s[t]}).toLowerCase()}}();function uaDetect(t,e){return navigator.userAgentData?t===navigator.userAgentData.platform:e.test(navigator.userAgent)}var IS_MAC=uaDetect("macOS",/Mac/),KEY_A=65,KEY_COMMA=188,KEY_RETURN=13,KEY_ESC=27,KEY_LEFT=37,KEY_UP=38,KEY_P=80,KEY_RIGHT=39,KEY_DOWN=40,KEY_N=78,KEY_BACKSPACE=8,KEY_DELETE=46,KEY_SHIFT=16,KEY_CMD=IS_MAC?91:17,KEY_CTRL=IS_MAC?18:17,KEY_TAB=9,TAG_SELECT=1,TAG_INPUT=2,SUPPORTS_VALIDITY_API=!uaDetect("Android",/android/i)&&!!document.createElement("input").validity,isset=function(t){return void 0!==t},hash_key=function(t){return null==t?null:"boolean"==typeof t?t?"1":"0":t+""},escape_html=function(t){return(t+"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")},escape_replace=function(t){return(t+"").replace(/\$/g,"$$$$")},hook={before:function(t,e,n){var i=t[e];t[e]=function(){return n.apply(t,arguments),i.apply(t,arguments)}},after:function(e,t,n){var i=e[t];e[t]=function(){var t=i.apply(e,arguments);return n.apply(e,arguments),t}}},once=function(t){var e=!1;return function(){e||(e=!0,t.apply(this,arguments))}},debounce=function(n,i){var o;return function(){var t=this,e=arguments;window.clearTimeout(o),o=window.setTimeout(function(){n.apply(t,e)},i)}},debounce_events=function(e,n,t){var i,o=e.trigger,s={};for(i in e.trigger=function(){var t=arguments[0];if(-1===n.indexOf(t))return o.apply(e,arguments);s[t]=arguments},t.apply(e,[]),e.trigger=o,s)s.hasOwnProperty(i)&&o.apply(e,s[i])},watchChildEvent=function(n,t,e,i){n.on(t,e,function(t){for(var e=t.target;e&&e.parentNode!==n[0];)e=e.parentNode;return t.currentTarget=e,i.apply(this,[t])})},getInputSelection=function(t){var e,n,i={};return void 0===t?console.warn("WARN getInputSelection cannot locate input control"):"selectionStart"in t?(i.start=t.selectionStart,i.length=t.selectionEnd-i.start):document.selection&&(t.focus(),e=document.selection.createRange(),n=document.selection.createRange().text.length,e.moveStart("character",-t.value.length),i.start=e.text.length-n,i.length=n),i},transferStyles=function(t,e,n){var i,o,s={};if(n)for(i=0,o=n.length;i<o;i++)s[n[i]]=t.css(n[i]);else s=t.css();e.css(s)},measureString=function(t,e){return t?(Selectize.$testInput||(Selectize.$testInput=$("<span />").css({position:"absolute",width:"auto",padding:0,whiteSpace:"pre"}),$("<div />").css({position:"absolute",width:0,height:0,overflow:"hidden"}).append(Selectize.$testInput).appendTo("body")),Selectize.$testInput.text(t),transferStyles(e,Selectize.$testInput,["letterSpacing","fontSize","fontFamily","fontWeight","textTransform"]),Selectize.$testInput.width()):0},autoGrow=function(s){function t(t,e){var n,i,o;e=e||{},(t=t||window.event||{}).metaKey||t.altKey||!e.force&&!1===s.data("grow")||(e=s.val(),t.type&&"keydown"===t.type.toLowerCase()&&(n=48<=(i=t.keyCode)&&i<=57||65<=i&&i<=90||96<=i&&i<=111||186<=i&&i<=222||32===i,i===KEY_DELETE||i===KEY_BACKSPACE?(o=getInputSelection(s[0])).length?e=e.substring(0,o.start)+e.substring(o.start+o.length):i===KEY_BACKSPACE&&o.start?e=e.substring(0,o.start-1)+e.substring(o.start+1):i===KEY_DELETE&&void 0!==o.start&&(e=e.substring(0,o.start)+e.substring(o.start+1)):n&&(i=t.shiftKey,o=String.fromCharCode(t.keyCode),e+=o=i?o.toUpperCase():o.toLowerCase())),t=(n=s.attr("placeholder"))?measureString(n,s)+4:0,(i=Math.max(measureString(e,s),t)+4)===r)||(r=i,s.width(i),s.triggerHandler("resize"))}var r=null;s.on("keydown keyup update blur",t),t()},domToString=function(t){var e=document.createElement("div");return e.appendChild(t.cloneNode(!0)),e.innerHTML},logError=function(t,e){e=e||{};console.error("Selectize: "+t),e.explanation&&(console.group&&console.group(),console.error(e.explanation),console.group)&&console.groupEnd()},isJSON=function(t){try{JSON.parse(str)}catch(t){return!1}return!0},Selectize=function(t,e){var n,i,o=this,s=t[0],r=(s.selectize=o,window.getComputedStyle&&window.getComputedStyle(s,null));if(r=(r?r.getPropertyValue("direction"):s.currentStyle&&s.currentStyle.direction)||t.parents("[dir]:first").attr("dir")||"",$.extend(o,{order:0,settings:e,$input:t,tabIndex:t.attr("tabindex")||"",tagType:"select"===s.tagName.toLowerCase()?TAG_SELECT:TAG_INPUT,rtl:/rtl/i.test(r),eventNS:".selectize"+ ++Selectize.count,highlightedValue:null,isBlurring:!1,isOpen:!1,isDisabled:!1,isRequired:t.is("[required]"),isInvalid:!1,isLocked:!1,isFocused:!1,isInputHidden:!1,isSetup:!1,isShiftDown:!1,isCmdDown:!1,isCtrlDown:!1,ignoreFocus:!1,ignoreBlur:!1,ignoreHover:!1,hasOptions:!1,currentResults:null,lastValue:"",lastValidValue:"",lastOpenTarget:!1,caretPos:0,loading:0,loadedSearches:{},isDropdownClosing:!1,$activeOption:null,$activeItems:[],optgroups:{},options:{},userOptions:{},items:[],renderCache:{},onSearchChange:null===e.loadThrottle?o.onSearchChange:debounce(o.onSearchChange,e.loadThrottle)}),o.sifter=new Sifter(this.options,{diacritics:e.diacritics}),o.settings.options){for(n=0,i=o.settings.options.length;n<i;n++)o.registerOption(o.settings.options[n]);delete o.settings.options}if(o.settings.optgroups){for(n=0,i=o.settings.optgroups.length;n<i;n++)o.registerOptionGroup(o.settings.optgroups[n]);delete o.settings.optgroups}o.settings.mode=o.settings.mode||(1===o.settings.maxItems?"single":"multi"),"boolean"!=typeof o.settings.hideSelected&&(o.settings.hideSelected="multi"===o.settings.mode),o.initializePlugins(o.settings.plugins),o.setupCallbacks(),o.setupTemplates(),o.setup()};MicroEvent.mixin(Selectize),MicroPlugin.mixin(Selectize),$.extend(Selectize.prototype,{setup:function(){var e=this,t=e.settings,n=e.eventNS,i=$(window),o=$(document),s=e.$input,r=e.settings.mode,a=s.attr("class")||"",l=$("<div>").addClass(t.wrapperClass).addClass(a+" selectize-control").addClass(r),p=$("<div>").addClass(t.inputClass+" selectize-input items").appendTo(l),c=$('<input type="select-one" autocomplete="new-password" autofill="no" />').appendTo(p).attr("tabindex",s.is(":disabled")?"-1":e.tabIndex),u=$(t.dropdownParent||l),r=$("<div>").addClass(t.dropdownClass).addClass(r+" selectize-dropdown").hide().appendTo(u),u=$("<div>").addClass(t.dropdownContentClass+" selectize-dropdown-content").attr("tabindex","-1").appendTo(r),d=((d=s.attr("id"))&&(c.attr("id",d+"-selectized"),$("label[for='"+d+"']").attr("for",d+"-selectized")),e.settings.copyClassesToDropdown&&r.addClass(a),l.css({width:s[0].style.width}),e.plugins.names.length&&(d="plugin-"+e.plugins.names.join(" plugin-"),l.addClass(d),r.addClass(d)),(null===t.maxItems||1<t.maxItems)&&e.tagType===TAG_SELECT&&s.attr("multiple","multiple"),e.settings.placeholder&&c.attr("placeholder",t.placeholder),e.settings.search||(c.attr("readonly",!0),c.attr("inputmode","none"),p.css("cursor","pointer")),!e.settings.splitOn&&e.settings.delimiter&&(a=e.settings.delimiter.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"),e.settings.splitOn=new RegExp("\\s*"+a+"+\\s*")),s.attr("autocorrect")&&c.attr("autocorrect",s.attr("autocorrect")),s.attr("autocapitalize")&&c.attr("autocapitalize",s.attr("autocapitalize")),s.is("input")&&(c[0].type=s[0].type),e.$wrapper=l,e.$control=p,e.$control_input=c,e.$dropdown=r,e.$dropdown_content=u,r.on("mouseenter mousedown mouseup click","[data-disabled]>[data-selectable]",function(t){t.stopImmediatePropagation()}),r.on("mouseenter","[data-selectable]",function(){return e.onOptionHover.apply(e,arguments)}),r.on("mouseup click","[data-selectable]",function(){return e.onOptionSelect.apply(e,arguments)}),watchChildEvent(p,"mouseup","*:not(input)",function(){return e.onItemSelect.apply(e,arguments)}),autoGrow(c),p.on({mousedown:function(){return e.onMouseDown.apply(e,arguments)},click:function(){return e.onClick.apply(e,arguments)}}),c.on({mousedown:function(t){""===e.$control_input.val()&&!e.settings.openOnFocus||t.stopPropagation()},keydown:function(){return e.onKeyDown.apply(e,arguments)},keypress:function(){return e.onKeyPress.apply(e,arguments)},input:function(){return e.onInput.apply(e,arguments)},resize:function(){e.positionDropdown.apply(e,[])},focus:function(){return e.ignoreBlur=!1,e.onFocus.apply(e,arguments)},paste:function(){return e.onPaste.apply(e,arguments)}}),o.on("keydown"+n,function(t){e.isCmdDown=t[IS_MAC?"metaKey":"ctrlKey"],e.isCtrlDown=t[IS_MAC?"altKey":"ctrlKey"],e.isShiftDown=t.shiftKey}),o.on("keyup"+n,function(t){t.keyCode===KEY_CTRL&&(e.isCtrlDown=!1),t.keyCode===KEY_SHIFT&&(e.isShiftDown=!1),t.keyCode===KEY_CMD&&(e.isCmdDown=!1)}),o.on("mousedown"+n,function(t){if(e.isFocused){if(t.target===e.$dropdown[0]||t.target.parentNode===e.$dropdown[0])return!1;e.$dropdown.has(t.target).length||t.target===e.$control[0]||e.blur(t.target)}}),i.on(["scroll"+n,"resize"+n].join(" "),function(){e.isOpen&&e.positionDropdown.apply(e,arguments)}),i.on("mousemove"+n,function(){e.ignoreHover=e.settings.ignoreHover}),$("<div></div>")),a=s.children().detach();s.replaceWith(d),d.replaceWith(s),this.revertSettings={$children:a,tabindex:s.attr("tabindex")},s.attr("tabindex",-1).hide().after(e.$wrapper),Array.isArray(t.items)&&(e.lastValidValue=t.items,e.setValue(t.items),delete t.items),SUPPORTS_VALIDITY_API&&s.on("invalid"+n,function(t){t.preventDefault(),e.isInvalid=!0,e.refreshState()}),e.updateOriginalInput(),e.refreshItems(),e.refreshState(),e.updatePlaceholder(),e.isSetup=!0,s.is(":disabled")&&e.disable(),e.on("change",this.onChange),s.data("selectize",e),s.addClass("selectized"),e.trigger("initialize"),!0===t.preload&&e.onSearchChange("")},setupTemplates:function(){var t=this,i=t.settings.labelField,o=t.settings.valueField,n=t.settings.optgroupLabelField;t.settings.render=$.extend({},{optgroup:function(t){return'<div class="optgroup">'+t.html+"</div>"},optgroup_header:function(t,e){return'<div class="optgroup-header">'+e(t[n])+"</div>"},option:function(t,e){var n=t.classes?" "+t.classes:"";return n+=""===t[o]?" selectize-dropdown-emptyoptionlabel":"","<div"+(t.styles?' style="'+t.styles+'"':"")+' class="option'+n+'">'+e(t[i])+"</div>"},item:function(t,e){return'<div class="item">'+e(t[i])+"</div>"},option_create:function(t,e){return'<div class="create">Add <strong>'+e(t.input)+"</strong>&#x2026;</div>"}},t.settings.render)},setupCallbacks:function(){var t,e,n={initialize:"onInitialize",change:"onChange",item_add:"onItemAdd",item_remove:"onItemRemove",clear:"onClear",option_add:"onOptionAdd",option_remove:"onOptionRemove",option_clear:"onOptionClear",optgroup_add:"onOptionGroupAdd",optgroup_remove:"onOptionGroupRemove",optgroup_clear:"onOptionGroupClear",dropdown_open:"onDropdownOpen",dropdown_close:"onDropdownClose",type:"onType",load:"onLoad",focus:"onFocus",blur:"onBlur",dropdown_item_activate:"onDropdownItemActivate",dropdown_item_deactivate:"onDropdownItemDeactivate"};for(t in n)n.hasOwnProperty(t)&&(e=this.settings[n[t]])&&this.on(t,e)},onClick:function(t){this.isDropdownClosing||this.isFocused&&this.isOpen||(this.focus(),t.preventDefault())},onMouseDown:function(t){var e=this,n=t.isDefaultPrevented();$(t.target);if(e.isFocused||n||window.setTimeout(function(){e.focus()},0),t.target!==e.$control_input[0]||""===e.$control_input.val())return"single"===e.settings.mode?e.isOpen?e.close():e.open():(n||e.setActiveItem(null),e.settings.openOnFocus||(e.isOpen&&t.target===e.lastOpenTarget?(e.close(),e.lastOpenTarget=!1):(e.isOpen||(e.refreshOptions(),e.open()),e.lastOpenTarget=t.target))),!1},onChange:function(){""!==this.getValue()&&(this.lastValidValue=this.getValue()),this.$input.trigger("input"),this.$input.trigger("change")},onPaste:function(t){var o=this;o.isFull()||o.isInputHidden||o.isLocked?t.preventDefault():o.settings.splitOn&&setTimeout(function(){var t=o.$control_input.val();if(t.match(o.settings.splitOn))for(var e=t.trim().split(o.settings.splitOn),n=0,i=e.length;n<i;n++)o.createItem(e[n])},0)},onKeyPress:function(t){var e;return this.isLocked?t&&t.preventDefault():(e=String.fromCharCode(t.keyCode||t.which),this.settings.create&&"multi"===this.settings.mode&&e===this.settings.delimiter?(this.createItem(),t.preventDefault(),!1):void 0)},onKeyDown:function(t){t.target,this.$control_input[0];var e,n=this;if(n.isLocked)t.keyCode!==KEY_TAB&&t.preventDefault();else{switch(t.keyCode){case KEY_A:if(n.isCmdDown)return void n.selectAll();break;case KEY_ESC:return void(n.isOpen&&(t.preventDefault(),t.stopPropagation(),n.close()));case KEY_N:if(!t.ctrlKey||t.altKey)break;case KEY_DOWN:return!n.isOpen&&n.hasOptions?n.open():n.$activeOption&&(n.ignoreHover=!0,(e=n.getAdjacentOption(n.$activeOption,1)).length)&&n.setActiveOption(e,!0,!0),void t.preventDefault();case KEY_P:if(!t.ctrlKey||t.altKey)break;case KEY_UP:return n.$activeOption&&(n.ignoreHover=!0,(e=n.getAdjacentOption(n.$activeOption,-1)).length)&&n.setActiveOption(e,!0,!0),void t.preventDefault();case KEY_RETURN:return void(n.isOpen&&n.$activeOption&&(n.onOptionSelect({currentTarget:n.$activeOption}),t.preventDefault()));case KEY_LEFT:return void n.advanceSelection(-1,t);case KEY_RIGHT:return void n.advanceSelection(1,t);case KEY_TAB:return n.settings.selectOnTab&&n.isOpen&&n.$activeOption&&(n.onOptionSelect({currentTarget:n.$activeOption}),n.isFull()||t.preventDefault()),void(n.settings.create&&n.createItem()&&n.settings.showAddOptionOnCreate&&t.preventDefault());case KEY_BACKSPACE:case KEY_DELETE:return void n.deleteSelection(t)}!n.isFull()&&!n.isInputHidden||(IS_MAC?t.metaKey:t.ctrlKey)||t.preventDefault()}},onInput:function(t){var e=this,n=e.$control_input.val()||"";e.lastValue!==n&&(e.lastValue=n,e.onSearchChange(n),e.refreshOptions(),e.trigger("type",n))},onSearchChange:function(e){var n=this,i=n.settings.load;i&&!n.loadedSearches.hasOwnProperty(e)&&(n.loadedSearches[e]=!0,n.load(function(t){i.apply(n,[e,t])}))},onFocus:function(t){var e=this,n=e.isFocused;if(e.isDisabled)return e.blur(),t&&t.preventDefault(),!1;e.ignoreFocus||(e.isFocused=!0,"focus"===e.settings.preload&&e.onSearchChange(""),n||e.trigger("focus"),e.$activeItems.length||(e.showInput(),e.setActiveItem(null),e.refreshOptions(!!e.settings.openOnFocus)),e.refreshState())},onBlur:function(t,e){var n,i=this;i.isFocused&&(i.isFocused=!1,i.ignoreFocus||(n=function(){i.close(),i.setTextboxValue(""),i.setActiveItem(null),i.setActiveOption(null),i.setCaret(i.items.length),i.refreshState(),e&&e.focus&&e.focus(),i.isBlurring=!1,i.ignoreFocus=!1,i.trigger("blur")},i.isBlurring=!0,i.ignoreFocus=!0,i.settings.create&&i.settings.createOnBlur?i.createItem(null,!1,n):n()))},onOptionHover:function(t){this.ignoreHover||this.setActiveOption(t.currentTarget,!1)},onOptionSelect:function(t){var e,n=this;t.preventDefault&&(t.preventDefault(),t.stopPropagation()),(e=$(t.currentTarget)).hasClass("create")?n.createItem(null,function(){n.settings.closeAfterSelect&&n.close()}):void 0!==(e=e.attr("data-value"))&&(n.lastQuery=null,n.setTextboxValue(""),n.addItem(e),n.settings.closeAfterSelect?n.close():!n.settings.hideSelected&&t.type&&/mouse/.test(t.type)&&n.setActiveOption(n.getOption(e)))},onItemSelect:function(t){this.isLocked||"multi"===this.settings.mode&&(t.preventDefault(),this.setActiveItem(t.currentTarget,t))},load:function(t){var e=this,n=e.$wrapper.addClass(e.settings.loadingClass);e.loading++,t.apply(e,[function(t){e.loading=Math.max(e.loading-1,0),t&&t.length&&(e.addOption(t),e.refreshOptions(e.isFocused&&!e.isInputHidden)),e.loading||n.removeClass(e.settings.loadingClass),e.trigger("load",t)}])},getTextboxValue:function(){return this.$control_input.val()},setTextboxValue:function(t){var e=this.$control_input;e.val()!==t&&(e.val(t).triggerHandler("update"),this.lastValue=t)},getValue:function(){return this.tagType===TAG_SELECT&&this.$input.attr("multiple")?this.items:this.items.join(this.settings.delimiter)},setValue:function(t,e){(Array.isArray(t)?t:[t]).join("")!==this.items.join("")&&debounce_events(this,e?[]:["change"],function(){this.clear(e),this.addItems(t,e)})},setMaxItems:function(t){this.settings.maxItems=t=0===t?null:t,this.settings.mode=this.settings.mode||(1===this.settings.maxItems?"single":"multi"),this.refreshState()},setActiveItem:function(t,e){var n,i,o,s,r,a,l=this;if("single"!==l.settings.mode)if((t=$(t)).length){if("mousedown"===(n=e&&e.type.toLowerCase())&&l.isShiftDown&&l.$activeItems.length){for(a=l.$control.children(".active:last"),a=Array.prototype.indexOf.apply(l.$control[0].childNodes,[a[0]]),(o=Array.prototype.indexOf.apply(l.$control[0].childNodes,[t[0]]))<a&&(r=a,a=o,o=r),i=a;i<=o;i++)s=l.$control[0].childNodes[i],-1===l.$activeItems.indexOf(s)&&($(s).addClass("active"),l.$activeItems.push(s));e.preventDefault()}else"mousedown"===n&&l.isCtrlDown||"keydown"===n&&this.isShiftDown?t.hasClass("active")?(r=l.$activeItems.indexOf(t[0]),l.$activeItems.splice(r,1),t.removeClass("active")):l.$activeItems.push(t.addClass("active")[0]):($(l.$activeItems).removeClass("active"),l.$activeItems=[t.addClass("active")[0]]);l.hideInput(),this.isFocused||l.focus()}else $(l.$activeItems).removeClass("active"),l.$activeItems=[],l.isFocused&&l.showInput()},setActiveOption:function(t,e,n){var i,o,s,r,a=this;a.$activeOption&&(a.$activeOption.removeClass("active"),a.trigger("dropdown_item_deactivate",a.$activeOption.attr("data-value"))),a.$activeOption=null,(t=$(t)).length&&(a.$activeOption=t.addClass("active"),a.isOpen&&a.trigger("dropdown_item_activate",a.$activeOption.attr("data-value")),!e&&isset(e)||(t=a.$dropdown_content.height(),i=a.$activeOption.outerHeight(!0),e=a.$dropdown_content.scrollTop()||0,r=(s=o=a.$activeOption.offset().top-a.$dropdown_content.offset().top+e)-t+i,t+e<o+i?a.$dropdown_content.stop().animate({scrollTop:r},n?a.settings.scrollDuration:0):o<e&&a.$dropdown_content.stop().animate({scrollTop:s},n?a.settings.scrollDuration:0)))},selectAll:function(){var t=this;"single"!==t.settings.mode&&(t.$activeItems=Array.prototype.slice.apply(t.$control.children(":not(input)").addClass("active")),t.$activeItems.length&&(t.hideInput(),t.close()),t.focus())},hideInput:function(){this.setTextboxValue(""),this.$control_input.css({opacity:0,position:"absolute",left:this.rtl?1e4:0}),this.isInputHidden=!0},showInput:function(){this.$control_input.css({opacity:1,position:"relative",left:0}),this.isInputHidden=!1},focus:function(){var t=this;return t.isDisabled||(t.ignoreFocus=!0,t.$control_input[0].focus(),window.setTimeout(function(){t.ignoreFocus=!1,t.onFocus()},0)),t},blur:function(t){return this.$control_input[0].blur(),this.onBlur(null,t),this},getScoreFunction:function(t){return this.sifter.getScoreFunction(t,this.getSearchOptions())},getSearchOptions:function(){var t=this.settings,e=t.sortField;return{fields:t.searchField,conjunction:t.searchConjunction,sort:e="string"==typeof e?[{field:e}]:e,nesting:t.nesting,filter:t.filter,respect_word_boundaries:t.respect_word_boundaries}},search:function(t){var e,n,i,o=this,s=o.settings,r=this.getSearchOptions();if(s.score&&"function"!=typeof(i=o.settings.score.apply(this,[t])))throw new Error('Selectize "score" setting must be a function that returns a function');if(t!==o.lastQuery?(s.normalize&&(t=t.normalize("NFD").replace(/[\u0300-\u036f]/g,"")),o.lastQuery=t,n=o.sifter.search(t,$.extend(r,{score:i})),o.currentResults=n):n=$.extend(!0,{},o.currentResults),s.hideSelected)for(e=n.items.length-1;0<=e;e--)-1!==o.items.indexOf(hash_key(n.items[e].id))&&n.items.splice(e,1);return n},refreshOptions:function(t){void 0===t&&(t=!0);var e,n,i,o,s,r,a,l,p,c,u,d,h,g=this,f=g.$control_input.val().trim(),v=g.search(f),m=g.$dropdown_content,y=g.$activeOption&&hash_key(g.$activeOption.attr("data-value")),w=v.items.length;for("number"==typeof g.settings.maxOptions&&(w=Math.min(w,g.settings.maxOptions)),o={},s=[],e=0;e<w;e++)for(r=g.options[v.items[e].id],a=g.render("option",r),O=r[g.settings.optgroupField]||"",n=0,i=(l=Array.isArray(O)?O:[O])&&l.length;n<i;n++){var C,O=l[n];g.optgroups.hasOwnProperty(O)||"function"!=typeof g.settings.optionGroupRegister||(C=g.settings.optionGroupRegister.apply(g,[O]))&&g.registerOptionGroup(C),g.optgroups.hasOwnProperty(O)||(O=""),o.hasOwnProperty(O)||(o[O]=document.createDocumentFragment(),s.push(O)),o[O].appendChild(a)}for(this.settings.lockOptgroupOrder&&s.sort(function(t,e){return(g.optgroups[t]&&g.optgroups[t].$order||0)-(g.optgroups[e]&&g.optgroups[e].$order||0)}),p=document.createDocumentFragment(),e=0,w=s.length;e<w;e++)g.optgroups.hasOwnProperty(O=s[e])&&o[O].childNodes.length?((c=document.createDocumentFragment()).appendChild(g.render("optgroup_header",g.optgroups[O])),c.appendChild(o[O]),p.appendChild(g.render("optgroup",$.extend({},g.optgroups[O],{html:domToString(c),dom:c})))):p.appendChild(o[O]);if(m.html(p),g.settings.highlight&&(m.removeHighlight(),v.query.length)&&v.tokens.length)for(e=0,w=v.tokens.length;e<w;e++)highlight(m,v.tokens[e].regex);if(!g.settings.hideSelected)for(g.$dropdown.find(".selected").removeClass("selected"),e=0,w=g.items.length;e<w;e++)g.getOption(g.items[e]).addClass("selected");"auto"!==g.settings.dropdownSize.sizeType&&g.isOpen&&g.setupDropdownHeight(),(u=g.canCreate(f))&&g.settings.showAddOptionOnCreate&&(m.prepend(g.render("option_create",{input:f})),h=$(m[0].childNodes[0])),g.hasOptions=0<v.items.length||u&&g.settings.showAddOptionOnCreate||g.settings.setFirstOptionActive,g.hasOptions?(0<v.items.length?(f=y&&g.getOption(y),""!==v.query&&g.settings.setFirstOptionActive?d=m.find("[data-selectable]:first"):""!==v.query&&f&&f.length?d=f:"single"===g.settings.mode&&g.items.length&&(d=g.getOption(g.items[0])),d&&d.length||(d=h&&!g.settings.addPrecedence?g.getAdjacentOption(h,1):m.find("[data-selectable]:first"))):d=h,g.setActiveOption(d),t&&!g.isOpen&&g.open()):(g.setActiveOption(null),t&&g.isOpen&&g.close())},addOption:function(t){var e,n,i,o=this;if(Array.isArray(t))for(e=0,n=t.length;e<n;e++)o.addOption(t[e]);else(i=o.registerOption(t))&&(o.userOptions[i]=!0,o.lastQuery=null,o.trigger("option_add",i,t))},registerOption:function(t){var e=hash_key(t[this.settings.valueField]);return null!=e&&!this.options.hasOwnProperty(e)&&(t.$order=t.$order||++this.order,this.options[e]=t,e)},registerOptionGroup:function(t){var e=hash_key(t[this.settings.optgroupValueField]);return!!e&&(t.$order=t.$order||++this.order,this.optgroups[e]=t,e)},addOptionGroup:function(t,e){e[this.settings.optgroupValueField]=t,(t=this.registerOptionGroup(e))&&this.trigger("optgroup_add",t,e)},removeOptionGroup:function(t){this.optgroups.hasOwnProperty(t)&&(delete this.optgroups[t],this.renderCache={},this.trigger("optgroup_remove",t))},clearOptionGroups:function(){this.optgroups={},this.renderCache={},this.trigger("optgroup_clear")},updateOption:function(t,e){var n,i,o,s=this;if(t=hash_key(t),n=hash_key(e[s.settings.valueField]),null!==t&&s.options.hasOwnProperty(t)){if("string"!=typeof n)throw new Error("Value must be set in option data");o=s.options[t].$order,n!==t&&(delete s.options[t],-1!==(i=s.items.indexOf(t)))&&s.items.splice(i,1,n),e.$order=e.$order||o,s.options[n]=e,i=s.renderCache.item,o=s.renderCache.option,i&&(delete i[t],delete i[n]),o&&(delete o[t],delete o[n]),-1!==s.items.indexOf(n)&&(i=s.getItem(t),o=$(s.render("item",e)),i.hasClass("active")&&o.addClass("active"),i.replaceWith(o)),s.lastQuery=null,s.isOpen&&s.refreshOptions(!1)}},removeOption:function(t,e){var n=this,i=(t=hash_key(t),n.renderCache.item),o=n.renderCache.option;i&&delete i[t],o&&delete o[t],delete n.userOptions[t],delete n.options[t],n.lastQuery=null,n.trigger("option_remove",t),n.removeItem(t,e)},clearOptions:function(t){var n=this,i=(n.loadedSearches={},n.userOptions={},n.renderCache={},n.options);$.each(n.options,function(t,e){-1==n.items.indexOf(t)&&delete i[t]}),n.options=n.sifter.items=i,n.lastQuery=null,n.trigger("option_clear"),n.clear(t)},getOption:function(t){return this.getElementWithValue(t,this.$dropdown_content.find("[data-selectable]"))},getFirstOption:function(){var t=this.$dropdown.find("[data-selectable]");return 0<t.length?t.eq(0):$()},getAdjacentOption:function(t,e){var n=this.$dropdown.find("[data-selectable]"),t=n.index(t)+e;return 0<=t&&t<n.length?n.eq(t):$()},getElementWithValue:function(t,e){if(null!=(t=hash_key(t)))for(var n=0,i=e.length;n<i;n++)if(e[n].getAttribute("data-value")===t)return $(e[n]);return $()},getElementWithTextContent:function(t,e,n){if(null!=(t=hash_key(t)))for(var i=0,o=n.length;i<o;i++){var s=n[i].textContent;if(1==e&&(s=null!==s?s.toLowerCase():null,t=t.toLowerCase()),s===t)return $(n[i])}return $()},getItem:function(t){return this.getElementWithValue(t,this.$control.children())},getFirstItemMatchedByTextContent:function(t,e){return this.getElementWithTextContent(t,e=null!==e&&!0===e,this.$dropdown_content.find("[data-selectable]"))},addItems:function(t,e){this.buffer=document.createDocumentFragment();for(var n=this.$control[0].childNodes,i=0;i<n.length;i++)this.buffer.appendChild(n[i]);for(var o=Array.isArray(t)?t:[t],i=0,s=o.length;i<s;i++)this.isPending=i<s-1,this.addItem(o[i],e);t=this.$control[0];t.insertBefore(this.buffer,t.firstChild),this.buffer=null},addItem:function(s,r){debounce_events(this,r?[]:["change"],function(){var t,e,n,i=this,o=i.settings.mode;s=hash_key(s),-1!==i.items.indexOf(s)?"single"===o&&i.close():i.options.hasOwnProperty(s)&&("single"===o&&i.clear(r),"multi"===o&&i.isFull()||(t=$(i.render("item",i.options[s])),n=i.isFull(),i.items.splice(i.caretPos,0,s),i.insertAtCaret(t),i.isPending&&(n||!i.isFull())||i.refreshState(),i.isSetup&&(n=i.$dropdown_content.find("[data-selectable]"),i.isPending||(e=i.getOption(s),e=i.getAdjacentOption(e,1).attr("data-value"),i.refreshOptions(i.isFocused&&"single"!==o),e&&i.setActiveOption(i.getOption(e))),!n.length||i.isFull()?i.close():i.isPending||i.positionDropdown(),i.updatePlaceholder(),i.trigger("item_add",s,t),i.isPending||i.updateOriginalInput({silent:r}))))})},removeItem:function(t,e){var n,i,o=this,s=t instanceof $?t:o.getItem(t);t=hash_key(s.attr("data-value")),-1!==(n=o.items.indexOf(t))&&(o.trigger("item_before_remove",t,s),s.remove(),s.hasClass("active")&&(s.removeClass("active"),i=o.$activeItems.indexOf(s[0]),o.$activeItems.splice(i,1),s.removeClass("active")),o.items.splice(n,1),o.lastQuery=null,!o.settings.persist&&o.userOptions.hasOwnProperty(t)&&o.removeOption(t,e),n<o.caretPos&&o.setCaret(o.caretPos-1),o.refreshState(),o.updatePlaceholder(),o.updateOriginalInput({silent:e}),o.positionDropdown(),o.trigger("item_remove",t,s))},createItem:function(t,n){var i=this,o=i.caretPos,s=(t=t||(i.$control_input.val()||"").trim(),arguments[arguments.length-1]);if("function"!=typeof s&&(s=function(){}),"boolean"!=typeof n&&(n=!0),!i.canCreate(t))return s(),!1;i.lock();var e="function"==typeof i.settings.create?this.settings.create:function(t){var e={},t=e[i.settings.labelField]=t;if(!i.settings.formatValueToKey||"function"!=typeof i.settings.formatValueToKey||null!=(t=i.settings.formatValueToKey.apply(this,[t]))&&"object"!=typeof t&&"function"!=typeof t)return e[i.settings.valueField]=t,e;throw new Error('Selectize "formatValueToKey" setting must be a function that returns a value other than object or function.')},r=once(function(t){var e;return i.unlock(),!t||"object"!=typeof t||"string"!=typeof(e=hash_key(t[i.settings.valueField]))?s():(i.setTextboxValue(""),i.addOption(t),i.setCaret(o),i.addItem(e),i.refreshOptions(n&&"single"!==i.settings.mode),void s(t))}),e=e.apply(this,[t,r]);return void 0!==e&&r(e),!0},refreshItems:function(t){this.lastQuery=null,this.isSetup&&this.addItem(this.items,t),this.refreshState(),this.updateOriginalInput({silent:t})},refreshState:function(){this.refreshValidityState(),this.refreshClasses()},refreshValidityState:function(){if(!this.isRequired)return!1;var t=!this.items.length;this.isInvalid=t,this.$control_input.prop("required",t),this.$input.prop("required",!t)},refreshClasses:function(){var t=this,e=t.isFull(),n=t.isLocked;t.$wrapper.toggleClass("rtl",t.rtl),t.$control.toggleClass("focus",t.isFocused).toggleClass("disabled",t.isDisabled).toggleClass("required",t.isRequired).toggleClass("invalid",t.isInvalid).toggleClass("locked",n).toggleClass("full",e).toggleClass("not-full",!e).toggleClass("input-active",t.isFocused&&!t.isInputHidden).toggleClass("dropdown-active",t.isOpen).toggleClass("has-options",!$.isEmptyObject(t.options)).toggleClass("has-items",0<t.items.length),t.$control_input.data("grow",!e&&!n)},isFull:function(){return null!==this.settings.maxItems&&this.items.length>=this.settings.maxItems},updateOriginalInput:function(t){var e,n,i,o,s,r,a=this;t=t||{},a.tagType===TAG_SELECT?(o=a.$input.find("option"),e=[],n=[],i=[],r=[],o.get().forEach(function(t){e.push(t.value)}),a.items.forEach(function(t){s=a.options[t][a.settings.labelField]||"",r.push(t),-1==e.indexOf(t)&&n.push('<option value="'+escape_html(t)+'" selected="selected">'+escape_html(s)+"</option>")}),i=e.filter(function(t){return r.indexOf(t)<0}).map(function(t){return'option[value="'+t+'"]'}),e.length-i.length+n.length!==0||a.$input.attr("multiple")||n.push('<option value="" selected="selected"></option>'),a.$input.find(i.join(", ")).remove(),a.$input.append(n.join(""))):(a.$input.val(a.getValue()),a.$input.attr("value",a.$input.val())),a.isSetup&&!t.silent&&a.trigger("change",a.$input.val())},updatePlaceholder:function(){var t;this.settings.placeholder&&(t=this.$control_input,this.items.length?t.removeAttr("placeholder"):t.attr("placeholder",this.settings.placeholder),t.triggerHandler("update",{force:!0}))},open:function(){var t=this;t.isLocked||t.isOpen||"multi"===t.settings.mode&&t.isFull()||(t.focus(),t.isOpen=!0,t.refreshState(),t.$dropdown.css({visibility:"hidden",display:"block"}),t.setupDropdownHeight(),t.positionDropdown(),t.$dropdown.css({visibility:"visible"}),t.trigger("dropdown_open",t.$dropdown))},close:function(){var t=this,e=t.isOpen;"single"===t.settings.mode&&t.items.length&&(t.hideInput(),t.isBlurring)&&t.$control_input[0].blur(),t.isOpen=!1,t.$dropdown.hide(),t.setActiveOption(null),t.refreshState(),e&&t.trigger("dropdown_close",t.$dropdown)},positionDropdown:function(){var t=this.$control,e="body"===this.settings.dropdownParent?t.offset():t.position(),t=(e.top+=t.outerHeight(!0),t[0].getBoundingClientRect().width);this.settings.minWidth&&this.settings.minWidth>t&&(t=this.settings.minWidth),this.$dropdown.css({width:t,top:e.top,left:e.left})},setupDropdownHeight:function(){if("object"==typeof this.settings.dropdownSize&&"auto"!==this.settings.dropdownSize.sizeType){var t=this.settings.dropdownSize.sizeValue;if("numberItems"===this.settings.dropdownSize.sizeType){for(var e=this.$dropdown_content.find("*").not(".optgroup, .highlight").not(this.settings.ignoreOnDropwdownHeight),n=0,i=0,o=0,s=0,r=0;r<t;r++){var a=$(e[r]);if(0===a.length)break;n+=a.outerHeight(!0),void 0===a.data("selectable")&&(a.hasClass("optgroup-header")&&(a=window.getComputedStyle(a.parent()[0],":before"))&&(i=a.marginTop?Number(a.marginTop.replace(/\W*(\w)\w*/g,"$1")):0,o=a.marginBottom?Number(a.marginBottom.replace(/\W*(\w)\w*/g,"$1")):0,s=a.borderTopWidth?Number(a.borderTopWidth.replace(/\W*(\w)\w*/g,"$1")):0),t++)}t=n+(this.$dropdown_content.css("padding-top")?Number(this.$dropdown_content.css("padding-top").replace(/\W*(\w)\w*/g,"$1")):0)+(this.$dropdown_content.css("padding-bottom")?Number(this.$dropdown_content.css("padding-bottom").replace(/\W*(\w)\w*/g,"$1")):0)+i+o+s+"px"}else if("fixedHeight"!==this.settings.dropdownSize.sizeType)return void console.warn('Selectize.js - Value of "sizeType" must be "fixedHeight" or "numberItems');this.$dropdown_content.css({height:t,maxHeight:"none"})}},clear:function(t){var e=this;e.items.length&&(e.$control.children(":not(input)").remove(),e.items=[],e.lastQuery=null,e.setCaret(0),e.setActiveItem(null),e.updatePlaceholder(),e.updateOriginalInput({silent:t}),e.refreshState(),e.showInput(),e.trigger("clear"))},insertAtCaret:function(t){var e=Math.min(this.caretPos,this.items.length),t=t[0],n=this.buffer||this.$control[0];0===e?n.insertBefore(t,n.firstChild):n.insertBefore(t,n.childNodes[e]),this.setCaret(e+1)},deleteSelection:function(t){var e,n,i,o,s,r=this,a=t&&t.keyCode===KEY_BACKSPACE?-1:1,l=getInputSelection(r.$control_input[0]);if(r.$activeOption&&!r.settings.hideSelected&&(o=("string"==typeof r.settings.deselectBehavior&&"top"===r.settings.deselectBehavior?r.getFirstOption():r.getAdjacentOption(r.$activeOption,-1)).attr("data-value")),i=[],r.$activeItems.length){for(s=r.$control.children(".active:"+(0<a?"last":"first")),s=r.$control.children(":not(input)").index(s),0<a&&s++,e=0,n=r.$activeItems.length;e<n;e++)i.push($(r.$activeItems[e]).attr("data-value"));t&&(t.preventDefault(),t.stopPropagation())}else(r.isFocused||"single"===r.settings.mode)&&r.items.length&&(a<0&&0===l.start&&0===l.length?i.push(r.items[r.caretPos-1]):0<a&&l.start===r.$control_input.val().length&&i.push(r.items[r.caretPos]));if(!i.length||"function"==typeof r.settings.onDelete&&!1===r.settings.onDelete.apply(r,[i]))return!1;for(void 0!==s&&r.setCaret(s);i.length;)r.removeItem(i.pop());return r.showInput(),r.positionDropdown(),r.refreshOptions(!0),o&&(t=r.getOption(o)).length&&r.setActiveOption(t),!0},advanceSelection:function(t,e){var n,i,o,s=this;0!==t&&(s.rtl&&(t*=-1),n=0<t?"last":"first",o=getInputSelection(s.$control_input[0]),s.isFocused&&!s.isInputHidden?(i=s.$control_input.val().length,(t<0?0!==o.start||0!==o.length:o.start!==i)||i||s.advanceCaret(t,e)):(o=s.$control.children(".active:"+n)).length&&(i=s.$control.children(":not(input)").index(o),s.setActiveItem(null),s.setCaret(0<t?i+1:i)))},advanceCaret:function(t,e){var n,i=this;0!==t&&(i.isShiftDown?(n=i.$control_input[0<t?"next":"prev"]()).length&&(i.hideInput(),i.setActiveItem(n),e)&&e.preventDefault():i.setCaret(i.caretPos+t))},setCaret:function(t){var e=this;if(t="single"===e.settings.mode?e.items.length:Math.max(0,Math.min(e.items.length,t)),!e.isPending)for(var n,i=e.$control.children(":not(input)"),o=0,s=i.length;o<s;o++)n=$(i[o]).detach(),o<t?e.$control_input.before(n):e.$control.append(n);e.caretPos=t},lock:function(){this.close(),this.isLocked=!0,this.refreshState()},unlock:function(){this.isLocked=!1,this.refreshState()},disable:function(){this.$input.prop("disabled",!0),this.$control_input.prop("disabled",!0).prop("tabindex",-1),this.isDisabled=!0,this.lock()},enable:function(){var t=this;t.$input.prop("disabled",!1),t.$control_input.prop("disabled",!1).prop("tabindex",t.tabIndex),t.isDisabled=!1,t.unlock()},destroy:function(){var t=this,e=t.eventNS,n=t.revertSettings;t.trigger("destroy"),t.off(),t.$wrapper.remove(),t.$dropdown.remove(),t.$input.html("").append(n.$children).removeAttr("tabindex").removeClass("selectized").attr({tabindex:n.tabindex}).show(),t.$control_input.removeData("grow"),t.$input.removeData("selectize"),0==--Selectize.count&&Selectize.$testInput&&(Selectize.$testInput.remove(),Selectize.$testInput=void 0),$(window).off(e),$(document).off(e),$(document.body).off(e),delete t.$input[0].selectize},render:function(t,e){var n,i,o="",s=!1,r=this;return(s="option"!==t&&"item"!==t?s:!!(n=hash_key(e[r.settings.valueField])))&&(isset(r.renderCache[t])||(r.renderCache[t]={}),r.renderCache[t].hasOwnProperty(n))?r.renderCache[t][n]:(o=$(r.settings.render[t].apply(this,[e,escape_html])),"option"===t||"option_create"===t?e[r.settings.disabledField]||o.attr("data-selectable",""):"optgroup"===t&&(i=e[r.settings.optgroupValueField]||"",o.attr("data-group",i),e[r.settings.disabledField])&&o.attr("data-disabled",""),"option"!==t&&"item"!==t||o.attr("data-value",n||""),s&&(r.renderCache[t][n]=o[0]),o[0])},clearCache:function(t){void 0===t?this.renderCache={}:delete this.renderCache[t]},canCreate:function(t){var e;return!!this.settings.create&&(e=this.settings.createFilter,t.length)&&("function"!=typeof e||e.apply(this,[t]))&&("string"!=typeof e||new RegExp(e).test(t))&&(!(e instanceof RegExp)||e.test(t))}}),Selectize.count=0,Selectize.defaults={options:[],optgroups:[],plugins:[],delimiter:",",splitOn:null,persist:!0,diacritics:!0,create:!1,showAddOptionOnCreate:!0,createOnBlur:!1,createFilter:null,highlight:!0,openOnFocus:!0,maxOptions:1e3,maxItems:null,hideSelected:null,addPrecedence:!1,selectOnTab:!0,preload:!1,allowEmptyOption:!1,showEmptyOptionInDropdown:!1,emptyOptionLabel:"--",setFirstOptionActive:!1,closeAfterSelect:!1,closeDropdownThreshold:250,scrollDuration:60,deselectBehavior:"previous",loadThrottle:300,loadingClass:"loading",dataAttr:"data-data",optgroupField:"optgroup",valueField:"value",labelField:"text",disabledField:"disabled",optgroupLabelField:"label",optgroupValueField:"value",lockOptgroupOrder:!1,sortField:"$order",searchField:["text"],searchConjunction:"and",respect_word_boundaries:!0,mode:null,wrapperClass:"",inputClass:"",dropdownClass:"",dropdownContentClass:"",dropdownParent:null,copyClassesToDropdown:!0,dropdownSize:{sizeType:"auto",sizeValue:"auto"},normalize:!1,ignoreOnDropwdownHeight:"img, i",search:!0,render:{}},$.fn.selectize=function(c){function u(t,o){function e(t,e){t=$(t);var n,i=hash_key(t.val());(i||v.allowEmptyOption)&&(l.hasOwnProperty(i)?e&&((n=l[i][O])?Array.isArray(n)?n.push(e):l[i][O]=[n,e]:l[i][O]=e):((n=p(t)||{})[y]=n[y]||t.text(),n[w]=n[w]||i,n[C]=n[C]||t.prop("disabled"),n[O]=n[O]||e,n.styles=t.attr("style")||"",n.classes=t.attr("class")||"",l[i]=n,a.push(n),t.is(":selected")&&o.items.push(i)))}var n,i,s,r,a=o.options,l={},p=function(t){var e=m&&t.attr(m),t=t.data(),n={};return"string"==typeof e&&e.length&&(isJSON(e)?Object.assign(n,JSON.parse(e)):n[e]=e),Object.assign(n,t),n||null};for(o.maxItems=t.attr("multiple")?null:1,n=0,i=(r=t.children()).length;n<i;n++)if("optgroup"===(s=r[n].tagName.toLowerCase())){g=h=d=u=c=void 0;var c,u,d,h,g,f=r[n];for((d=(f=$(f)).attr("label"))&&((h=p(f)||{})[_]=d,h[b]=d,h[C]=f.prop("disabled"),o.optgroups.push(h)),c=0,u=(g=$("option",f)).length;c<u;c++)e(g[c],d)}else"option"===s&&e(r[n])}var d=$.fn.selectize.defaults,v=$.extend({},d,c),m=v.dataAttr,y=v.labelField,w=v.valueField,C=v.disabledField,O=v.optgroupField,_=v.optgroupLabelField,b=v.optgroupValueField;return this.each(function(){if(!this.selectize){var t=$(this),e=this.tagName.toLowerCase(),n=t.attr("placeholder")||t.attr("data-placeholder"),i=(n||v.allowEmptyOption||(n=t.children('option[value=""]').text()),v.allowEmptyOption&&v.showEmptyOptionInDropdown&&!t.children('option[value=""]').length&&(l=t.html(),i=escape_html(v.emptyOptionLabel||"--"),t.html('<option value="">'+i+"</option>"+l)),{placeholder:n,options:[],optgroups:[],items:[]});if("select"===e)u(t,i);else{var o,s,r,a,l=t,p=i,n=l.attr(m);if(n)for(p.options=JSON.parse(n),o=0,s=p.options.length;o<s;o++)p.items.push(p.options[o][w]);else{n=(l.val()||"").trim();if(v.allowEmptyOption||n.length){for(o=0,s=(r=n.split(v.delimiter)).length;o<s;o++)(a={})[y]=r[o],a[w]=r[o],p.options.push(a);p.items=r}}}new Selectize(t,$.extend(!0,{},d,i,c)).settings_user=c}})},$.fn.selectize.defaults=Selectize.defaults,$.fn.selectize.support={validity:SUPPORTS_VALIDITY_API},Selectize.define("auto_position",function(){const o={top:"top",bottom:"bottom"};this.positionDropdown=function(){var t=this.$control,e="body"===this.settings.dropdownParent?t.offset():t.position(),n=(e.top+=t.outerHeight(!0),this.$dropdown.prop("scrollHeight")+5),n=this.$control.get(0).getBoundingClientRect().top+n+this.$wrapper.height()>window.innerHeight?o.top:o.bottom,i={width:t.outerWidth(),left:e.left};n===o.top?(n={bottom:e.top,top:"unset"},"body"===this.settings.dropdownParent&&(n.top=e.top-this.$dropdown.outerHeight(!0)-t.outerHeight(!0),n.bottom="unset"),Object.assign(i,n),this.$dropdown.addClass("selectize-position-top"),this.$control.addClass("selectize-position-top")):(Object.assign(i,{top:e.top,bottom:"unset"}),this.$dropdown.removeClass("selectize-position-top"),this.$control.removeClass("selectize-position-top")),this.$dropdown.css(i)}}),Selectize.define("auto_select_on_type",function(t){var n,i=this;i.onBlur=(n=i.onBlur,function(t){var e=i.getFirstItemMatchedByTextContent(i.lastValue,!0);return void 0!==e.attr("data-value")&&i.getValue()!==e.attr("data-value")&&i.setValue(e.attr("data-value")),n.apply(this,arguments)})}),Selectize.define("autofill_disable",function(t){var e,n=this;n.setup=(e=n.setup,function(){e.apply(n,arguments),n.$control_input.attr({autocomplete:"new-password",autofill:"no"})})}),Selectize.define("clear_button",function(e){var t,n=this;e=$.extend({title:"Clear",className:"clear",label:"×",html:function(t){return'<a class="'+t.className+'" title="'+t.title+'"> '+t.label+"</a>"}},e),n.setup=(t=n.setup,function(){t.apply(n,arguments),n.$button_clear=$(e.html(e)),"single"===n.settings.mode&&n.$wrapper.addClass("single"),n.$wrapper.append(n.$button_clear),""!==n.getValue()&&0!==n.getValue().length||n.$wrapper.find("."+e.className).css("display","none"),n.on("change",function(){""===n.getValue()||0===n.getValue().length?n.$wrapper.find("."+e.className).css("display","none"):n.$wrapper.find("."+e.className).css("display","")}),n.$wrapper.on("click","."+e.className,function(t){t.preventDefault(),t.stopImmediatePropagation(),t.stopPropagation(),n.isLocked||(n.clear(),n.$wrapper.find("."+e.className).css("display","none"))})})}),Selectize.define("drag_drop",function(t){if(!$.fn.sortable)throw new Error('The "drag_drop" plugin requires jQuery UI "sortable".');var i,e,n,o;"multi"===this.settings.mode&&((i=this).lock=(e=i.lock,function(){var t=i.$control.data("sortable");return t&&t.disable(),e.apply(i,arguments)}),i.unlock=(n=i.unlock,function(){var t=i.$control.data("sortable");return t&&t.enable(),n.apply(i,arguments)}),i.setup=(o=i.setup,function(){o.apply(this,arguments);var n=i.$control.sortable({items:"[data-value]",forcePlaceholderSize:!0,disabled:i.isLocked,start:function(t,e){e.placeholder.css("width",e.helper.css("width")),n.addClass("dragging")},stop:function(){n.removeClass("dragging");var t=i.$activeItems?i.$activeItems.slice():null,e=[];n.children("[data-value]").each(function(){e.push($(this).attr("data-value"))}),i.isFocused=!1,i.setValue(e),i.isFocused=!0,i.setActiveItem(t),i.positionDropdown()}})}))}),Selectize.define("dropdown_header",function(t){var e,n=this;t=$.extend({title:"Untitled",headerClass:"selectize-dropdown-header",titleRowClass:"selectize-dropdown-header-title",labelClass:"selectize-dropdown-header-label",closeClass:"selectize-dropdown-header-close",html:function(t){return'<div class="'+t.headerClass+'"><div class="'+t.titleRowClass+'"><span class="'+t.labelClass+'">'+t.title+'</span><a href="javascript:void(0)" class="'+t.closeClass+'">&#xd7;</a></div></div>'}},t),n.setup=(e=n.setup,function(){e.apply(n,arguments),n.$dropdown_header=$(t.html(t)),n.$dropdown.prepend(n.$dropdown_header),n.$dropdown_header.find("."+t.closeClass).on("click",function(){n.close()})})}),Selectize.define("optgroup_columns",function(r){function t(){var t,e,n,i,o=$("[data-group]",a.$dropdown_content),s=o.length;if(s&&a.$dropdown_content.width()){if(r.equalizeHeight){for(t=e=0;t<s;t++)e=Math.max(e,o.eq(t).height());o.css({height:e})}r.equalizeWidth&&(i=a.$dropdown_content.innerWidth()-l(),n=Math.round(i/s),o.css({width:n}),1<s)&&(i=i-n*(s-1),o.eq(s-1).css({width:i}))}}var i,a=this,l=(r=$.extend({equalizeWidth:!0,equalizeHeight:!0},r),this.getAdjacentOption=function(t,e){var n=t.closest("[data-group]").find("[data-selectable]"),t=n.index(t)+e;return 0<=t&&t<n.length?n.eq(t):$()},this.onKeyDown=(i=a.onKeyDown,function(t){var e,n;if(!this.isOpen||t.keyCode!==KEY_LEFT&&t.keyCode!==KEY_RIGHT)return i.apply(this,arguments);a.ignoreHover=!0,e=(n=this.$activeOption.closest("[data-group]")).find("[data-selectable]").index(this.$activeOption),(n=(n=(n=t.keyCode===KEY_LEFT?n.prev("[data-group]"):n.next("[data-group]")).find("[data-selectable]")).eq(Math.min(n.length-1,e))).length&&this.setActiveOption(n)}),function(){var t,e=l.width,n=document;return void 0===e&&((t=n.createElement("div")).innerHTML='<div style="width:50px;height:50px;position:absolute;left:-50px;top:-50px;overflow:auto;"><div style="width:1px;height:100px;"></div></div>',t=t.firstChild,n.body.appendChild(t),e=l.width=t.offsetWidth-t.clientWidth,n.body.removeChild(t)),e});(r.equalizeHeight||r.equalizeWidth)&&(hook.after(this,"positionDropdown",t),hook.after(this,"refreshOptions",t))}),Selectize.define("remove_button",function(t){var s,e,n,i,r;"single"!==this.settings.mode&&(t=$.extend({label:"&#xd7;",title:"Remove",className:"remove",append:!0},t),i=s=this,r='<a href="javascript:void(0)" class="'+(e=t).className+'" tabindex="-1" title="'+escape_html(e.title)+'">'+e.label+"</a>",s.setup=(n=i.setup,function(){var o;e.append&&(o=i.settings.render.item,i.settings.render.item=function(t){return e=o.apply(s,arguments),n=r,i=e.search(/(<\/[^>]+>\s*)$/),e.substring(0,i)+n+e.substring(i);var e,n,i}),n.apply(s,arguments),s.$control.on("click","."+e.className,function(t){if(t.preventDefault(),!i.isLocked)return t=$(t.currentTarget).parent(),i.setActiveItem(t),i.deleteSelection()&&i.setCaret(i.items.length),!1})}))}),Selectize.define("restore_on_backspace",function(n){var i,t=this;n.text=n.text||function(t){return t[this.settings.labelField]},this.onKeyDown=(i=t.onKeyDown,function(t){var e;if(!(t.keyCode===KEY_BACKSPACE&&""===this.$control_input.val()&&!this.$activeItems.length&&0<=(e=this.caretPos-1)&&e<this.items.length))return i.apply(this,arguments);e=this.options[this.items[e]],this.deleteSelection(t)&&(this.setTextboxValue(n.text.apply(this,[e])),this.refreshOptions(!0)),t.preventDefault()})}),Selectize.define("select_on_focus",function(t){var n,e,i=this;i.on("focus",(n=i.onFocus,function(t){var e=i.getItem(i.getValue()).text();return i.clear(),i.setTextboxValue(e),i.$control_input.select(),setTimeout(function(){i.settings.selectOnTab&&i.setActiveOption(i.getFirstItemMatchedByTextContent(e)),i.settings.score=null},0),n.apply(this,arguments)})),i.onBlur=(e=i.onBlur,function(t){return""===i.getValue()&&i.lastValidValue!==i.getValue()&&i.setValue(i.lastValidValue),setTimeout(function(){i.settings.score=function(){return function(){return 1}}},0),e.apply(this,arguments)}),i.settings.score=function(){return function(){return 1}}}),Selectize.define("tag_limit",function(o){const t=this;o.tagLimit=o.tagLimit,this.onBlur=function(){const i=t.onBlur;return function(t){if(i.apply(this,t),t){var t=this.$control,e=t.find(".item");const n=o.tagLimit;void 0===n||e.length<=n||(e.toArray().forEach(function(t,e){e<n||$(t).hide()}),t.append("<span><b>"+(e.length-n)+"</b></span>"))}}}(),this.onFocus=function(){const e=t.onFocus;return function(t){e.apply(this,t),t&&((t=this.$control).find(".item").show(),t.find("span").remove())}}()});
  return Selectize;
}));

/**
  * bootstrap-table - An extended table to integration with some of the most widely used CSS frameworks. (Supports Bootstrap, Semantic UI, Bulma, Material Design, Foundation)
  *
  * @version v1.21.1
  * @homepage https://bootstrap-table.com
  * @author wenzhixin <wenzhixin2010@gmail.com> (http://wenzhixin.net.cn/)
  * @license MIT
  */

!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e(require("jquery")):"function"==typeof define&&define.amd?define(["jquery"],e):(t="undefined"!=typeof globalThis?globalThis:t||self).BootstrapTable=e(t.jQuery)}(this,(function(t){"use strict";function e(t){return t&&"object"==typeof t&&"default"in t?t:{default:t}}var i=e(t);function n(t){return n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},n(t)}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function a(t,e){for(var i=0;i<e.length;i++){var n=e[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,n.key,n)}}function r(t,e,i){return e&&a(t.prototype,e),i&&a(t,i),Object.defineProperty(t,"prototype",{writable:!1}),t}function s(t,e){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){var i=null==t?null:"undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(null==i)return;var n,o,a=[],r=!0,s=!1;try{for(i=i.call(t);!(r=(n=i.next()).done)&&(a.push(n.value),!e||a.length!==e);r=!0);}catch(t){s=!0,o=t}finally{try{r||null==i.return||i.return()}finally{if(s)throw o}}return a}(t,e)||c(t,e)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function l(t){return function(t){if(Array.isArray(t))return h(t)}(t)||function(t){if("undefined"!=typeof Symbol&&null!=t[Symbol.iterator]||null!=t["@@iterator"])return Array.from(t)}(t)||c(t)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function c(t,e){if(t){if("string"==typeof t)return h(t,e);var i=Object.prototype.toString.call(t).slice(8,-1);return"Object"===i&&t.constructor&&(i=t.constructor.name),"Map"===i||"Set"===i?Array.from(t):"Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i)?h(t,e):void 0}}function h(t,e){(null==e||e>t.length)&&(e=t.length);for(var i=0,n=new Array(e);i<e;i++)n[i]=t[i];return n}function u(t,e){var i="undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(!i){if(Array.isArray(t)||(i=c(t))||e&&t&&"number"==typeof t.length){i&&(t=i);var n=0,o=function(){};return{s:o,n:function(){return n>=t.length?{done:!0}:{done:!1,value:t[n++]}},e:function(t){throw t},f:o}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var a,r=!0,s=!1;return{s:function(){i=i.call(t)},n:function(){var t=i.next();return r=t.done,t},e:function(t){s=!0,a=t},f:function(){try{r||null==i.return||i.return()}finally{if(s)throw a}}}}var d="undefined"!=typeof globalThis?globalThis:"undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:{},f=function(t){return t&&t.Math==Math&&t},p=f("object"==typeof globalThis&&globalThis)||f("object"==typeof window&&window)||f("object"==typeof self&&self)||f("object"==typeof d&&d)||function(){return this}()||Function("return this")(),g={},v=function(t){try{return!!t()}catch(t){return!0}},b=!v((function(){return 7!=Object.defineProperty({},1,{get:function(){return 7}})[1]})),m=!v((function(){var t=function(){}.bind();return"function"!=typeof t||t.hasOwnProperty("prototype")})),y=m,w=Function.prototype.call,S=y?w.bind(w):function(){return w.apply(w,arguments)},x={},k={}.propertyIsEnumerable,O=Object.getOwnPropertyDescriptor,C=O&&!k.call({1:2},1);x.f=C?function(t){var e=O(this,t);return!!e&&e.enumerable}:k;var T,I,P=function(t,e){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:e}},A=m,$=Function.prototype,R=$.bind,E=$.call,j=A&&R.bind(E,E),F=A?function(t){return t&&j(t)}:function(t){return t&&function(){return E.apply(t,arguments)}},_=F,N=_({}.toString),D=_("".slice),V=function(t){return D(N(t),8,-1)},B=v,L=V,H=Object,M=F("".split),U=B((function(){return!H("z").propertyIsEnumerable(0)}))?function(t){return"String"==L(t)?M(t,""):H(t)}:H,z=TypeError,q=function(t){if(null==t)throw z("Can't call method on "+t);return t},W=U,G=q,K=function(t){return W(G(t))},Y=function(t){return"function"==typeof t},J=Y,X=function(t){return"object"==typeof t?null!==t:J(t)},Q=p,Z=Y,tt=function(t){return Z(t)?t:void 0},et=function(t,e){return arguments.length<2?tt(Q[t]):Q[t]&&Q[t][e]},it=F({}.isPrototypeOf),nt=et("navigator","userAgent")||"",ot=p,at=nt,rt=ot.process,st=ot.Deno,lt=rt&&rt.versions||st&&st.version,ct=lt&&lt.v8;ct&&(I=(T=ct.split("."))[0]>0&&T[0]<4?1:+(T[0]+T[1])),!I&&at&&(!(T=at.match(/Edge\/(\d+)/))||T[1]>=74)&&(T=at.match(/Chrome\/(\d+)/))&&(I=+T[1]);var ht=I,ut=ht,dt=v,ft=!!Object.getOwnPropertySymbols&&!dt((function(){var t=Symbol();return!String(t)||!(Object(t)instanceof Symbol)||!Symbol.sham&&ut&&ut<41})),pt=ft&&!Symbol.sham&&"symbol"==typeof Symbol.iterator,gt=et,vt=Y,bt=it,mt=Object,yt=pt?function(t){return"symbol"==typeof t}:function(t){var e=gt("Symbol");return vt(e)&&bt(e.prototype,mt(t))},wt=String,St=function(t){try{return wt(t)}catch(t){return"Object"}},xt=Y,kt=St,Ot=TypeError,Ct=function(t){if(xt(t))return t;throw Ot(kt(t)+" is not a function")},Tt=Ct,It=function(t,e){var i=t[e];return null==i?void 0:Tt(i)},Pt=S,At=Y,$t=X,Rt=TypeError,Et={exports:{}},jt=p,Ft=Object.defineProperty,_t=function(t,e){try{Ft(jt,t,{value:e,configurable:!0,writable:!0})}catch(i){jt[t]=e}return e},Nt=_t,Dt="__core-js_shared__",Vt=p[Dt]||Nt(Dt,{}),Bt=Vt;(Et.exports=function(t,e){return Bt[t]||(Bt[t]=void 0!==e?e:{})})("versions",[]).push({version:"3.22.8",mode:"global",copyright:"© 2014-2022 Denis Pushkarev (zloirock.ru)",license:"https://github.com/zloirock/core-js/blob/v3.22.8/LICENSE",source:"https://github.com/zloirock/core-js"});var Lt=q,Ht=Object,Mt=function(t){return Ht(Lt(t))},Ut=Mt,zt=F({}.hasOwnProperty),qt=Object.hasOwn||function(t,e){return zt(Ut(t),e)},Wt=F,Gt=0,Kt=Math.random(),Yt=Wt(1..toString),Jt=function(t){return"Symbol("+(void 0===t?"":t)+")_"+Yt(++Gt+Kt,36)},Xt=p,Qt=Et.exports,Zt=qt,te=Jt,ee=ft,ie=pt,ne=Qt("wks"),oe=Xt.Symbol,ae=oe&&oe.for,re=ie?oe:oe&&oe.withoutSetter||te,se=function(t){if(!Zt(ne,t)||!ee&&"string"!=typeof ne[t]){var e="Symbol."+t;ee&&Zt(oe,t)?ne[t]=oe[t]:ne[t]=ie&&ae?ae(e):re(e)}return ne[t]},le=S,ce=X,he=yt,ue=It,de=function(t,e){var i,n;if("string"===e&&At(i=t.toString)&&!$t(n=Pt(i,t)))return n;if(At(i=t.valueOf)&&!$t(n=Pt(i,t)))return n;if("string"!==e&&At(i=t.toString)&&!$t(n=Pt(i,t)))return n;throw Rt("Can't convert object to primitive value")},fe=TypeError,pe=se("toPrimitive"),ge=function(t,e){if(!ce(t)||he(t))return t;var i,n=ue(t,pe);if(n){if(void 0===e&&(e="default"),i=le(n,t,e),!ce(i)||he(i))return i;throw fe("Can't convert object to primitive value")}return void 0===e&&(e="number"),de(t,e)},ve=ge,be=yt,me=function(t){var e=ve(t,"string");return be(e)?e:e+""},ye=X,we=p.document,Se=ye(we)&&ye(we.createElement),xe=function(t){return Se?we.createElement(t):{}},ke=xe,Oe=!b&&!v((function(){return 7!=Object.defineProperty(ke("div"),"a",{get:function(){return 7}}).a})),Ce=b,Te=S,Ie=x,Pe=P,Ae=K,$e=me,Re=qt,Ee=Oe,je=Object.getOwnPropertyDescriptor;g.f=Ce?je:function(t,e){if(t=Ae(t),e=$e(e),Ee)try{return je(t,e)}catch(t){}if(Re(t,e))return Pe(!Te(Ie.f,t,e),t[e])};var Fe={},_e=b&&v((function(){return 42!=Object.defineProperty((function(){}),"prototype",{value:42,writable:!1}).prototype})),Ne=X,De=String,Ve=TypeError,Be=function(t){if(Ne(t))return t;throw Ve(De(t)+" is not an object")},Le=b,He=Oe,Me=_e,Ue=Be,ze=me,qe=TypeError,We=Object.defineProperty,Ge=Object.getOwnPropertyDescriptor,Ke="enumerable",Ye="configurable",Je="writable";Fe.f=Le?Me?function(t,e,i){if(Ue(t),e=ze(e),Ue(i),"function"==typeof t&&"prototype"===e&&"value"in i&&Je in i&&!i.writable){var n=Ge(t,e);n&&n.writable&&(t[e]=i.value,i={configurable:Ye in i?i.configurable:n.configurable,enumerable:Ke in i?i.enumerable:n.enumerable,writable:!1})}return We(t,e,i)}:We:function(t,e,i){if(Ue(t),e=ze(e),Ue(i),He)try{return We(t,e,i)}catch(t){}if("get"in i||"set"in i)throw qe("Accessors not supported");return"value"in i&&(t[e]=i.value),t};var Xe=Fe,Qe=P,Ze=b?function(t,e,i){return Xe.f(t,e,Qe(1,i))}:function(t,e,i){return t[e]=i,t},ti={exports:{}},ei=b,ii=qt,ni=Function.prototype,oi=ei&&Object.getOwnPropertyDescriptor,ai=ii(ni,"name"),ri={EXISTS:ai,PROPER:ai&&"something"===function(){}.name,CONFIGURABLE:ai&&(!ei||ei&&oi(ni,"name").configurable)},si=Y,li=Vt,ci=F(Function.toString);si(li.inspectSource)||(li.inspectSource=function(t){return ci(t)});var hi,ui,di,fi=li.inspectSource,pi=Y,gi=fi,vi=p.WeakMap,bi=pi(vi)&&/native code/.test(gi(vi)),mi=Et.exports,yi=Jt,wi=mi("keys"),Si=function(t){return wi[t]||(wi[t]=yi(t))},xi={},ki=bi,Oi=p,Ci=F,Ti=X,Ii=Ze,Pi=qt,Ai=Vt,$i=Si,Ri=xi,Ei="Object already initialized",ji=Oi.TypeError,Fi=Oi.WeakMap;if(ki||Ai.state){var _i=Ai.state||(Ai.state=new Fi),Ni=Ci(_i.get),Di=Ci(_i.has),Vi=Ci(_i.set);hi=function(t,e){if(Di(_i,t))throw new ji(Ei);return e.facade=t,Vi(_i,t,e),e},ui=function(t){return Ni(_i,t)||{}},di=function(t){return Di(_i,t)}}else{var Bi=$i("state");Ri[Bi]=!0,hi=function(t,e){if(Pi(t,Bi))throw new ji(Ei);return e.facade=t,Ii(t,Bi,e),e},ui=function(t){return Pi(t,Bi)?t[Bi]:{}},di=function(t){return Pi(t,Bi)}}var Li={set:hi,get:ui,has:di,enforce:function(t){return di(t)?ui(t):hi(t,{})},getterFor:function(t){return function(e){var i;if(!Ti(e)||(i=ui(e)).type!==t)throw ji("Incompatible receiver, "+t+" required");return i}}},Hi=v,Mi=Y,Ui=qt,zi=b,qi=ri.CONFIGURABLE,Wi=fi,Gi=Li.enforce,Ki=Li.get,Yi=Object.defineProperty,Ji=zi&&!Hi((function(){return 8!==Yi((function(){}),"length",{value:8}).length})),Xi=String(String).split("String"),Qi=ti.exports=function(t,e,i){"Symbol("===String(e).slice(0,7)&&(e="["+String(e).replace(/^Symbol\(([^)]*)\)/,"$1")+"]"),i&&i.getter&&(e="get "+e),i&&i.setter&&(e="set "+e),(!Ui(t,"name")||qi&&t.name!==e)&&Yi(t,"name",{value:e,configurable:!0}),Ji&&i&&Ui(i,"arity")&&t.length!==i.arity&&Yi(t,"length",{value:i.arity});try{i&&Ui(i,"constructor")&&i.constructor?zi&&Yi(t,"prototype",{writable:!1}):t.prototype&&(t.prototype=void 0)}catch(t){}var n=Gi(t);return Ui(n,"source")||(n.source=Xi.join("string"==typeof e?e:"")),t};Function.prototype.toString=Qi((function(){return Mi(this)&&Ki(this).source||Wi(this)}),"toString");var Zi=Y,tn=Ze,en=ti.exports,nn=_t,on=function(t,e,i,n){n||(n={});var o=n.enumerable,a=void 0!==n.name?n.name:e;return Zi(i)&&en(i,a,n),n.global?o?t[e]=i:nn(e,i):(n.unsafe?t[e]&&(o=!0):delete t[e],o?t[e]=i:tn(t,e,i)),t},an={},rn=Math.ceil,sn=Math.floor,ln=Math.trunc||function(t){var e=+t;return(e>0?sn:rn)(e)},cn=function(t){var e=+t;return e!=e||0===e?0:ln(e)},hn=cn,un=Math.max,dn=Math.min,fn=function(t,e){var i=hn(t);return i<0?un(i+e,0):dn(i,e)},pn=cn,gn=Math.min,vn=function(t){return t>0?gn(pn(t),9007199254740991):0},bn=vn,mn=function(t){return bn(t.length)},yn=K,wn=fn,Sn=mn,xn=function(t){return function(e,i,n){var o,a=yn(e),r=Sn(a),s=wn(n,r);if(t&&i!=i){for(;r>s;)if((o=a[s++])!=o)return!0}else for(;r>s;s++)if((t||s in a)&&a[s]===i)return t||s||0;return!t&&-1}},kn={includes:xn(!0),indexOf:xn(!1)},On=qt,Cn=K,Tn=kn.indexOf,In=xi,Pn=F([].push),An=function(t,e){var i,n=Cn(t),o=0,a=[];for(i in n)!On(In,i)&&On(n,i)&&Pn(a,i);for(;e.length>o;)On(n,i=e[o++])&&(~Tn(a,i)||Pn(a,i));return a},$n=["constructor","hasOwnProperty","isPrototypeOf","propertyIsEnumerable","toLocaleString","toString","valueOf"],Rn=An,En=$n.concat("length","prototype");an.f=Object.getOwnPropertyNames||function(t){return Rn(t,En)};var jn={};jn.f=Object.getOwnPropertySymbols;var Fn=et,_n=an,Nn=jn,Dn=Be,Vn=F([].concat),Bn=Fn("Reflect","ownKeys")||function(t){var e=_n.f(Dn(t)),i=Nn.f;return i?Vn(e,i(t)):e},Ln=qt,Hn=Bn,Mn=g,Un=Fe,zn=v,qn=Y,Wn=/#|\.prototype\./,Gn=function(t,e){var i=Yn[Kn(t)];return i==Xn||i!=Jn&&(qn(e)?zn(e):!!e)},Kn=Gn.normalize=function(t){return String(t).replace(Wn,".").toLowerCase()},Yn=Gn.data={},Jn=Gn.NATIVE="N",Xn=Gn.POLYFILL="P",Qn=Gn,Zn=p,to=g.f,eo=Ze,io=on,no=_t,oo=function(t,e,i){for(var n=Hn(e),o=Un.f,a=Mn.f,r=0;r<n.length;r++){var s=n[r];Ln(t,s)||i&&Ln(i,s)||o(t,s,a(e,s))}},ao=Qn,ro=function(t,e){var i,n,o,a,r,s=t.target,l=t.global,c=t.stat;if(i=l?Zn:c?Zn[s]||no(s,{}):(Zn[s]||{}).prototype)for(n in e){if(a=e[n],o=t.dontCallGetSet?(r=to(i,n))&&r.value:i[n],!ao(l?n:s+(c?".":"#")+n,t.forced)&&void 0!==o){if(typeof a==typeof o)continue;oo(a,o)}(t.sham||o&&o.sham)&&eo(a,"sham",!0),io(i,n,a,t)}},so=An,lo=$n,co=Object.keys||function(t){return so(t,lo)},ho=b,uo=F,fo=S,po=v,go=co,vo=jn,bo=x,mo=Mt,yo=U,wo=Object.assign,So=Object.defineProperty,xo=uo([].concat),ko=!wo||po((function(){if(ho&&1!==wo({b:1},wo(So({},"a",{enumerable:!0,get:function(){So(this,"b",{value:3,enumerable:!1})}}),{b:2})).b)return!0;var t={},e={},i=Symbol(),n="abcdefghijklmnopqrst";return t[i]=7,n.split("").forEach((function(t){e[t]=t})),7!=wo({},t)[i]||go(wo({},e)).join("")!=n}))?function(t,e){for(var i=mo(t),n=arguments.length,o=1,a=vo.f,r=bo.f;n>o;)for(var s,l=yo(arguments[o++]),c=a?xo(go(l),a(l)):go(l),h=c.length,u=0;h>u;)s=c[u++],ho&&!fo(r,l,s)||(i[s]=l[s]);return i}:wo,Oo=ko;ro({target:"Object",stat:!0,arity:2,forced:Object.assign!==Oo},{assign:Oo});var Co={};Co[se("toStringTag")]="z";var To="[object z]"===String(Co),Io=To,Po=Y,Ao=V,$o=se("toStringTag"),Ro=Object,Eo="Arguments"==Ao(function(){return arguments}()),jo=Io?Ao:function(t){var e,i,n;return void 0===t?"Undefined":null===t?"Null":"string"==typeof(i=function(t,e){try{return t[e]}catch(t){}}(e=Ro(t),$o))?i:Eo?Ao(e):"Object"==(n=Ao(e))&&Po(e.callee)?"Arguments":n},Fo=jo,_o=String,No=function(t){if("Symbol"===Fo(t))throw TypeError("Cannot convert a Symbol value to a string");return _o(t)},Do="\t\n\v\f\r                　\u2028\u2029\ufeff",Vo=q,Bo=No,Lo=F("".replace),Ho="[\t\n\v\f\r                　\u2028\u2029\ufeff]",Mo=RegExp("^"+Ho+Ho+"*"),Uo=RegExp(Ho+Ho+"*$"),zo=function(t){return function(e){var i=Bo(Vo(e));return 1&t&&(i=Lo(i,Mo,"")),2&t&&(i=Lo(i,Uo,"")),i}},qo={start:zo(1),end:zo(2),trim:zo(3)},Wo=ri.PROPER,Go=v,Ko=Do,Yo=qo.trim;ro({target:"String",proto:!0,forced:function(t){return Go((function(){return!!Ko[t]()||"​᠎"!=="​᠎"[t]()||Wo&&Ko[t].name!==t}))}("trim")},{trim:function(){return Yo(this)}});var Jo=v,Xo=function(t,e){var i=[][t];return!!i&&Jo((function(){i.call(null,e||function(){return 1},1)}))},Qo=ro,Zo=U,ta=K,ea=Xo,ia=F([].join),na=Zo!=Object,oa=ea("join",",");Qo({target:"Array",proto:!0,forced:na||!oa},{join:function(t){return ia(ta(this),void 0===t?",":t)}});var aa=Be,ra=function(){var t=aa(this),e="";return t.hasIndices&&(e+="d"),t.global&&(e+="g"),t.ignoreCase&&(e+="i"),t.multiline&&(e+="m"),t.dotAll&&(e+="s"),t.unicode&&(e+="u"),t.sticky&&(e+="y"),e},sa=v,la=p.RegExp,ca=sa((function(){var t=la("a","y");return t.lastIndex=2,null!=t.exec("abcd")})),ha=ca||sa((function(){return!la("a","y").sticky})),ua={BROKEN_CARET:ca||sa((function(){var t=la("^r","gy");return t.lastIndex=2,null!=t.exec("str")})),MISSED_STICKY:ha,UNSUPPORTED_Y:ca},da={},fa=b,pa=_e,ga=Fe,va=Be,ba=K,ma=co;da.f=fa&&!pa?Object.defineProperties:function(t,e){va(t);for(var i,n=ba(e),o=ma(e),a=o.length,r=0;a>r;)ga.f(t,i=o[r++],n[i]);return t};var ya,wa=et("document","documentElement"),Sa=Be,xa=da,ka=$n,Oa=xi,Ca=wa,Ta=xe,Ia=Si("IE_PROTO"),Pa=function(){},Aa=function(t){return"<script>"+t+"</"+"script>"},$a=function(t){t.write(Aa("")),t.close();var e=t.parentWindow.Object;return t=null,e},Ra=function(){try{ya=new ActiveXObject("htmlfile")}catch(t){}var t,e;Ra="undefined"!=typeof document?document.domain&&ya?$a(ya):((e=Ta("iframe")).style.display="none",Ca.appendChild(e),e.src=String("javascript:"),(t=e.contentWindow.document).open(),t.write(Aa("document.F=Object")),t.close(),t.F):$a(ya);for(var i=ka.length;i--;)delete Ra.prototype[ka[i]];return Ra()};Oa[Ia]=!0;var Ea=Object.create||function(t,e){var i;return null!==t?(Pa.prototype=Sa(t),i=new Pa,Pa.prototype=null,i[Ia]=t):i=Ra(),void 0===e?i:xa.f(i,e)},ja=v,Fa=p.RegExp,_a=ja((function(){var t=Fa(".","s");return!(t.dotAll&&t.exec("\n")&&"s"===t.flags)})),Na=v,Da=p.RegExp,Va=Na((function(){var t=Da("(?<a>b)","g");return"b"!==t.exec("b").groups.a||"bc"!=="b".replace(t,"$<a>c")})),Ba=S,La=F,Ha=No,Ma=ra,Ua=ua,za=Et.exports,qa=Ea,Wa=Li.get,Ga=_a,Ka=Va,Ya=za("native-string-replace",String.prototype.replace),Ja=RegExp.prototype.exec,Xa=Ja,Qa=La("".charAt),Za=La("".indexOf),tr=La("".replace),er=La("".slice),ir=function(){var t=/a/,e=/b*/g;return Ba(Ja,t,"a"),Ba(Ja,e,"a"),0!==t.lastIndex||0!==e.lastIndex}(),nr=Ua.BROKEN_CARET,or=void 0!==/()??/.exec("")[1];(ir||or||nr||Ga||Ka)&&(Xa=function(t){var e,i,n,o,a,r,s,l=this,c=Wa(l),h=Ha(t),u=c.raw;if(u)return u.lastIndex=l.lastIndex,e=Ba(Xa,u,h),l.lastIndex=u.lastIndex,e;var d=c.groups,f=nr&&l.sticky,p=Ba(Ma,l),g=l.source,v=0,b=h;if(f&&(p=tr(p,"y",""),-1===Za(p,"g")&&(p+="g"),b=er(h,l.lastIndex),l.lastIndex>0&&(!l.multiline||l.multiline&&"\n"!==Qa(h,l.lastIndex-1))&&(g="(?: "+g+")",b=" "+b,v++),i=new RegExp("^(?:"+g+")",p)),or&&(i=new RegExp("^"+g+"$(?!\\s)",p)),ir&&(n=l.lastIndex),o=Ba(Ja,f?i:l,b),f?o?(o.input=er(o.input,v),o[0]=er(o[0],v),o.index=l.lastIndex,l.lastIndex+=o[0].length):l.lastIndex=0:ir&&o&&(l.lastIndex=l.global?o.index+o[0].length:n),or&&o&&o.length>1&&Ba(Ya,o[0],i,(function(){for(a=1;a<arguments.length-2;a++)void 0===arguments[a]&&(o[a]=void 0)})),o&&d)for(o.groups=r=qa(null),a=0;a<d.length;a++)r[(s=d[a])[0]]=o[s[1]];return o});var ar=Xa;ro({target:"RegExp",proto:!0,forced:/./.exec!==ar},{exec:ar});var rr=m,sr=Function.prototype,lr=sr.apply,cr=sr.call,hr="object"==typeof Reflect&&Reflect.apply||(rr?cr.bind(lr):function(){return cr.apply(lr,arguments)}),ur=F,dr=on,fr=ar,pr=v,gr=se,vr=Ze,br=gr("species"),mr=RegExp.prototype,yr=function(t,e,i,n){var o=gr(t),a=!pr((function(){var e={};return e[o]=function(){return 7},7!=""[t](e)})),r=a&&!pr((function(){var e=!1,i=/a/;return"split"===t&&((i={}).constructor={},i.constructor[br]=function(){return i},i.flags="",i[o]=/./[o]),i.exec=function(){return e=!0,null},i[o](""),!e}));if(!a||!r||i){var s=ur(/./[o]),l=e(o,""[t],(function(t,e,i,n,o){var r=ur(t),l=e.exec;return l===fr||l===mr.exec?a&&!o?{done:!0,value:s(e,i,n)}:{done:!0,value:r(i,e,n)}:{done:!1}}));dr(String.prototype,t,l[0]),dr(mr,o,l[1])}n&&vr(mr[o],"sham",!0)},wr=X,Sr=V,xr=se("match"),kr=function(t){var e;return wr(t)&&(void 0!==(e=t[xr])?!!e:"RegExp"==Sr(t))},Or=F,Cr=v,Tr=Y,Ir=jo,Pr=fi,Ar=function(){},$r=[],Rr=et("Reflect","construct"),Er=/^\s*(?:class|function)\b/,jr=Or(Er.exec),Fr=!Er.exec(Ar),_r=function(t){if(!Tr(t))return!1;try{return Rr(Ar,$r,t),!0}catch(t){return!1}},Nr=function(t){if(!Tr(t))return!1;switch(Ir(t)){case"AsyncFunction":case"GeneratorFunction":case"AsyncGeneratorFunction":return!1}try{return Fr||!!jr(Er,Pr(t))}catch(t){return!0}};Nr.sham=!0;var Dr=!Rr||Cr((function(){var t;return _r(_r.call)||!_r(Object)||!_r((function(){t=!0}))||t}))?Nr:_r,Vr=Dr,Br=St,Lr=TypeError,Hr=Be,Mr=function(t){if(Vr(t))return t;throw Lr(Br(t)+" is not a constructor")},Ur=se("species"),zr=F,qr=cn,Wr=No,Gr=q,Kr=zr("".charAt),Yr=zr("".charCodeAt),Jr=zr("".slice),Xr=function(t){return function(e,i){var n,o,a=Wr(Gr(e)),r=qr(i),s=a.length;return r<0||r>=s?t?"":void 0:(n=Yr(a,r))<55296||n>56319||r+1===s||(o=Yr(a,r+1))<56320||o>57343?t?Kr(a,r):n:t?Jr(a,r,r+2):o-56320+(n-55296<<10)+65536}},Qr={codeAt:Xr(!1),charAt:Xr(!0)}.charAt,Zr=function(t,e,i){return e+(i?Qr(t,e).length:1)},ts=me,es=Fe,is=P,ns=function(t,e,i){var n=ts(e);n in t?es.f(t,n,is(0,i)):t[n]=i},os=fn,as=mn,rs=ns,ss=Array,ls=Math.max,cs=function(t,e,i){for(var n=as(t),o=os(e,n),a=os(void 0===i?n:i,n),r=ss(ls(a-o,0)),s=0;o<a;o++,s++)rs(r,s,t[o]);return r.length=s,r},hs=S,us=Be,ds=Y,fs=V,ps=ar,gs=TypeError,vs=function(t,e){var i=t.exec;if(ds(i)){var n=hs(i,t,e);return null!==n&&us(n),n}if("RegExp"===fs(t))return hs(ps,t,e);throw gs("RegExp#exec called on incompatible receiver")},bs=hr,ms=S,ys=F,ws=yr,Ss=kr,xs=Be,ks=q,Os=function(t,e){var i,n=Hr(t).constructor;return void 0===n||null==(i=Hr(n)[Ur])?e:Mr(i)},Cs=Zr,Ts=vn,Is=No,Ps=It,As=cs,$s=vs,Rs=ar,Es=v,js=ua.UNSUPPORTED_Y,Fs=4294967295,_s=Math.min,Ns=[].push,Ds=ys(/./.exec),Vs=ys(Ns),Bs=ys("".slice),Ls=!Es((function(){var t=/(?:)/,e=t.exec;t.exec=function(){return e.apply(this,arguments)};var i="ab".split(t);return 2!==i.length||"a"!==i[0]||"b"!==i[1]}));ws("split",(function(t,e,i){var n;return n="c"=="abbc".split(/(b)*/)[1]||4!="test".split(/(?:)/,-1).length||2!="ab".split(/(?:ab)*/).length||4!=".".split(/(.?)(.?)/).length||".".split(/()()/).length>1||"".split(/.?/).length?function(t,i){var n=Is(ks(this)),o=void 0===i?Fs:i>>>0;if(0===o)return[];if(void 0===t)return[n];if(!Ss(t))return ms(e,n,t,o);for(var a,r,s,l=[],c=(t.ignoreCase?"i":"")+(t.multiline?"m":"")+(t.unicode?"u":"")+(t.sticky?"y":""),h=0,u=new RegExp(t.source,c+"g");(a=ms(Rs,u,n))&&!((r=u.lastIndex)>h&&(Vs(l,Bs(n,h,a.index)),a.length>1&&a.index<n.length&&bs(Ns,l,As(a,1)),s=a[0].length,h=r,l.length>=o));)u.lastIndex===a.index&&u.lastIndex++;return h===n.length?!s&&Ds(u,"")||Vs(l,""):Vs(l,Bs(n,h)),l.length>o?As(l,0,o):l}:"0".split(void 0,0).length?function(t,i){return void 0===t&&0===i?[]:ms(e,this,t,i)}:e,[function(e,i){var o=ks(this),a=null==e?void 0:Ps(e,t);return a?ms(a,e,o,i):ms(n,Is(o),e,i)},function(t,o){var a=xs(this),r=Is(t),s=i(n,a,r,o,n!==e);if(s.done)return s.value;var l=Os(a,RegExp),c=a.unicode,h=(a.ignoreCase?"i":"")+(a.multiline?"m":"")+(a.unicode?"u":"")+(js?"g":"y"),u=new l(js?"^(?:"+a.source+")":a,h),d=void 0===o?Fs:o>>>0;if(0===d)return[];if(0===r.length)return null===$s(u,r)?[r]:[];for(var f=0,p=0,g=[];p<r.length;){u.lastIndex=js?0:p;var v,b=$s(u,js?Bs(r,p):r);if(null===b||(v=_s(Ts(u.lastIndex+(js?p:0)),r.length))===f)p=Cs(r,p,c);else{if(Vs(g,Bs(r,f,p)),g.length===d)return g;for(var m=1;m<=b.length-1;m++)if(Vs(g,b[m]),g.length===d)return g;p=f=v}}return Vs(g,Bs(r,f)),g}]}),!Ls,js);var Hs=b,Ms=F,Us=co,zs=K,qs=Ms(x.f),Ws=Ms([].push),Gs=function(t){return function(e){for(var i,n=zs(e),o=Us(n),a=o.length,r=0,s=[];a>r;)i=o[r++],Hs&&!qs(n,i)||Ws(s,t?[i,n[i]]:n[i]);return s}},Ks={entries:Gs(!0),values:Gs(!1)}.entries;ro({target:"Object",stat:!0},{entries:function(t){return Ks(t)}});var Ys=se,Js=Ea,Xs=Fe.f,Qs=Ys("unscopables"),Zs=Array.prototype;null==Zs[Qs]&&Xs(Zs,Qs,{configurable:!0,value:Js(null)});var tl=function(t){Zs[Qs][t]=!0},el=kn.includes,il=tl;ro({target:"Array",proto:!0,forced:v((function(){return!Array(1).includes()}))},{includes:function(t){return el(this,t,arguments.length>1?arguments[1]:void 0)}}),il("includes");var nl=V,ol=Array.isArray||function(t){return"Array"==nl(t)},al=TypeError,rl=function(t){if(t>9007199254740991)throw al("Maximum allowed index exceeded");return t},sl=ol,ll=Dr,cl=X,hl=se("species"),ul=Array,dl=function(t){var e;return sl(t)&&(e=t.constructor,(ll(e)&&(e===ul||sl(e.prototype))||cl(e)&&null===(e=e[hl]))&&(e=void 0)),void 0===e?ul:e},fl=function(t,e){return new(dl(t))(0===e?0:e)},pl=v,gl=ht,vl=se("species"),bl=function(t){return gl>=51||!pl((function(){var e=[];return(e.constructor={})[vl]=function(){return{foo:1}},1!==e[t](Boolean).foo}))},ml=ro,yl=v,wl=ol,Sl=X,xl=Mt,kl=mn,Ol=rl,Cl=ns,Tl=fl,Il=bl,Pl=ht,Al=se("isConcatSpreadable"),$l=Pl>=51||!yl((function(){var t=[];return t[Al]=!1,t.concat()[0]!==t})),Rl=Il("concat"),El=function(t){if(!Sl(t))return!1;var e=t[Al];return void 0!==e?!!e:wl(t)};ml({target:"Array",proto:!0,arity:1,forced:!$l||!Rl},{concat:function(t){var e,i,n,o,a,r=xl(this),s=Tl(r,0),l=0;for(e=-1,n=arguments.length;e<n;e++)if(El(a=-1===e?r:arguments[e]))for(o=kl(a),Ol(l+o),i=0;i<o;i++,l++)i in a&&Cl(s,l,a[i]);else Ol(l+1),Cl(s,l++,a);return s.length=l,s}});var jl=Ct,Fl=m,_l=F(F.bind),Nl=function(t,e){return jl(t),void 0===e?t:Fl?_l(t,e):function(){return t.apply(e,arguments)}},Dl=U,Vl=Mt,Bl=mn,Ll=fl,Hl=F([].push),Ml=function(t){var e=1==t,i=2==t,n=3==t,o=4==t,a=6==t,r=7==t,s=5==t||a;return function(l,c,h,u){for(var d,f,p=Vl(l),g=Dl(p),v=Nl(c,h),b=Bl(g),m=0,y=u||Ll,w=e?y(l,b):i||r?y(l,0):void 0;b>m;m++)if((s||m in g)&&(f=v(d=g[m],m,p),t))if(e)w[m]=f;else if(f)switch(t){case 3:return!0;case 5:return d;case 6:return m;case 2:Hl(w,d)}else switch(t){case 4:return!1;case 7:Hl(w,d)}return a?-1:n||o?o:w}},Ul={forEach:Ml(0),map:Ml(1),filter:Ml(2),some:Ml(3),every:Ml(4),find:Ml(5),findIndex:Ml(6),filterReject:Ml(7)},zl=ro,ql=Ul.find,Wl=tl,Gl="find",Kl=!0;Gl in[]&&Array(1).find((function(){Kl=!1})),zl({target:"Array",proto:!0,forced:Kl},{find:function(t){return ql(this,t,arguments.length>1?arguments[1]:void 0)}}),Wl(Gl);var Yl=jo,Jl=To?{}.toString:function(){return"[object "+Yl(this)+"]"};To||on(Object.prototype,"toString",Jl,{unsafe:!0});var Xl=kr,Ql=TypeError,Zl=function(t){if(Xl(t))throw Ql("The method doesn't accept regular expressions");return t},tc=se("match"),ec=function(t){var e=/./;try{"/./"[t](e)}catch(i){try{return e[tc]=!1,"/./"[t](e)}catch(t){}}return!1},ic=ro,nc=Zl,oc=q,ac=No,rc=ec,sc=F("".indexOf);ic({target:"String",proto:!0,forced:!rc("includes")},{includes:function(t){return!!~sc(ac(oc(this)),ac(nc(t)),arguments.length>1?arguments[1]:void 0)}});var lc={CSSRuleList:0,CSSStyleDeclaration:0,CSSValueList:0,ClientRectList:0,DOMRectList:0,DOMStringList:0,DOMTokenList:1,DataTransferItemList:0,FileList:0,HTMLAllCollection:0,HTMLCollection:0,HTMLFormElement:0,HTMLSelectElement:0,MediaList:0,MimeTypeArray:0,NamedNodeMap:0,NodeList:1,PaintRequestList:0,Plugin:0,PluginArray:0,SVGLengthList:0,SVGNumberList:0,SVGPathSegList:0,SVGPointList:0,SVGStringList:0,SVGTransformList:0,SourceBufferList:0,StyleSheetList:0,TextTrackCueList:0,TextTrackList:0,TouchList:0},cc=xe("span").classList,hc=cc&&cc.constructor&&cc.constructor.prototype,uc=hc===Object.prototype?void 0:hc,dc=Ul.forEach,fc=Xo("forEach")?[].forEach:function(t){return dc(this,t,arguments.length>1?arguments[1]:void 0)},pc=p,gc=lc,vc=uc,bc=fc,mc=Ze,yc=function(t){if(t&&t.forEach!==bc)try{mc(t,"forEach",bc)}catch(e){t.forEach=bc}};for(var wc in gc)gc[wc]&&yc(pc[wc]&&pc[wc].prototype);yc(vc);var Sc=p,xc=v,kc=No,Oc=qo.trim,Cc=F("".charAt),Tc=Sc.parseFloat,Ic=Sc.Symbol,Pc=Ic&&Ic.iterator,Ac=1/Tc("\t\n\v\f\r                　\u2028\u2029\ufeff-0")!=-1/0||Pc&&!xc((function(){Tc(Object(Pc))}))?function(t){var e=Oc(kc(t)),i=Tc(e);return 0===i&&"-"==Cc(e,0)?-0:i}:Tc;ro({global:!0,forced:parseFloat!=Ac},{parseFloat:Ac});var $c=ro,Rc=kn.indexOf,Ec=Xo,jc=F([].indexOf),Fc=!!jc&&1/jc([1],1,-0)<0,_c=Ec("indexOf");$c({target:"Array",proto:!0,forced:Fc||!_c},{indexOf:function(t){var e=arguments.length>1?arguments[1]:void 0;return Fc?jc(this,t,e)||0:Rc(this,t,e)}});var Nc=St,Dc=TypeError,Vc=function(t,e){if(!delete t[e])throw Dc("Cannot delete property "+Nc(e)+" of "+Nc(t))},Bc=cs,Lc=Math.floor,Hc=function(t,e){var i=t.length,n=Lc(i/2);return i<8?Mc(t,e):Uc(t,Hc(Bc(t,0,n),e),Hc(Bc(t,n),e),e)},Mc=function(t,e){for(var i,n,o=t.length,a=1;a<o;){for(n=a,i=t[a];n&&e(t[n-1],i)>0;)t[n]=t[--n];n!==a++&&(t[n]=i)}return t},Uc=function(t,e,i,n){for(var o=e.length,a=i.length,r=0,s=0;r<o||s<a;)t[r+s]=r<o&&s<a?n(e[r],i[s])<=0?e[r++]:i[s++]:r<o?e[r++]:i[s++];return t},zc=Hc,qc=nt.match(/firefox\/(\d+)/i),Wc=!!qc&&+qc[1],Gc=/MSIE|Trident/.test(nt),Kc=nt.match(/AppleWebKit\/(\d+)\./),Yc=!!Kc&&+Kc[1],Jc=ro,Xc=F,Qc=Ct,Zc=Mt,th=mn,eh=Vc,ih=No,nh=v,oh=zc,ah=Xo,rh=Wc,sh=Gc,lh=ht,ch=Yc,hh=[],uh=Xc(hh.sort),dh=Xc(hh.push),fh=nh((function(){hh.sort(void 0)})),ph=nh((function(){hh.sort(null)})),gh=ah("sort"),vh=!nh((function(){if(lh)return lh<70;if(!(rh&&rh>3)){if(sh)return!0;if(ch)return ch<603;var t,e,i,n,o="";for(t=65;t<76;t++){switch(e=String.fromCharCode(t),t){case 66:case 69:case 70:case 72:i=3;break;case 68:case 71:i=4;break;default:i=2}for(n=0;n<47;n++)hh.push({k:e+n,v:i})}for(hh.sort((function(t,e){return e.v-t.v})),n=0;n<hh.length;n++)e=hh[n].k.charAt(0),o.charAt(o.length-1)!==e&&(o+=e);return"DGBEFHACIJK"!==o}}));Jc({target:"Array",proto:!0,forced:fh||!ph||!gh||!vh},{sort:function(t){void 0!==t&&Qc(t);var e=Zc(this);if(vh)return void 0===t?uh(e):uh(e,t);var i,n,o=[],a=th(e);for(n=0;n<a;n++)n in e&&dh(o,e[n]);for(oh(o,function(t){return function(e,i){return void 0===i?-1:void 0===e?1:void 0!==t?+t(e,i)||0:ih(e)>ih(i)?1:-1}}(t)),i=o.length,n=0;n<i;)e[n]=o[n++];for(;n<a;)eh(e,n++);return e}});var bh=F,mh=Mt,yh=Math.floor,wh=bh("".charAt),Sh=bh("".replace),xh=bh("".slice),kh=/\$([$&'`]|\d{1,2}|<[^>]*>)/g,Oh=/\$([$&'`]|\d{1,2})/g,Ch=hr,Th=S,Ih=F,Ph=yr,Ah=v,$h=Be,Rh=Y,Eh=cn,jh=vn,Fh=No,_h=q,Nh=Zr,Dh=It,Vh=function(t,e,i,n,o,a){var r=i+t.length,s=n.length,l=Oh;return void 0!==o&&(o=mh(o),l=kh),Sh(a,l,(function(a,l){var c;switch(wh(l,0)){case"$":return"$";case"&":return t;case"`":return xh(e,0,i);case"'":return xh(e,r);case"<":c=o[xh(l,1,-1)];break;default:var h=+l;if(0===h)return a;if(h>s){var u=yh(h/10);return 0===u?a:u<=s?void 0===n[u-1]?wh(l,1):n[u-1]+wh(l,1):a}c=n[h-1]}return void 0===c?"":c}))},Bh=vs,Lh=se("replace"),Hh=Math.max,Mh=Math.min,Uh=Ih([].concat),zh=Ih([].push),qh=Ih("".indexOf),Wh=Ih("".slice),Gh="$0"==="a".replace(/./,"$0"),Kh=!!/./[Lh]&&""===/./[Lh]("a","$0");Ph("replace",(function(t,e,i){var n=Kh?"$":"$0";return[function(t,i){var n=_h(this),o=null==t?void 0:Dh(t,Lh);return o?Th(o,t,n,i):Th(e,Fh(n),t,i)},function(t,o){var a=$h(this),r=Fh(t);if("string"==typeof o&&-1===qh(o,n)&&-1===qh(o,"$<")){var s=i(e,a,r,o);if(s.done)return s.value}var l=Rh(o);l||(o=Fh(o));var c=a.global;if(c){var h=a.unicode;a.lastIndex=0}for(var u=[];;){var d=Bh(a,r);if(null===d)break;if(zh(u,d),!c)break;""===Fh(d[0])&&(a.lastIndex=Nh(r,jh(a.lastIndex),h))}for(var f,p="",g=0,v=0;v<u.length;v++){for(var b=Fh((d=u[v])[0]),m=Hh(Mh(Eh(d.index),r.length),0),y=[],w=1;w<d.length;w++)zh(y,void 0===(f=d[w])?f:String(f));var S=d.groups;if(l){var x=Uh([b],y,m,r);void 0!==S&&zh(x,S);var k=Fh(Ch(o,void 0,x))}else k=Vh(b,r,m,y,S,o);m>=g&&(p+=Wh(r,g,m)+k,g=m+b.length)}return p+Wh(r,g)}]}),!!Ah((function(){var t=/./;return t.exec=function(){var t=[];return t.groups={a:"7"},t},"7"!=="".replace(t,"$<a>")}))||!Gh||Kh);var Yh=Ul.filter;ro({target:"Array",proto:!0,forced:!bl("filter")},{filter:function(t){return Yh(this,t,arguments.length>1?arguments[1]:void 0)}});var Jh=Object.is||function(t,e){return t===e?0!==t||1/t==1/e:t!=t&&e!=e},Xh=S,Qh=Be,Zh=q,tu=Jh,eu=No,iu=It,nu=vs;yr("search",(function(t,e,i){return[function(e){var i=Zh(this),n=null==e?void 0:iu(e,t);return n?Xh(n,e,i):new RegExp(e)[t](eu(i))},function(t){var n=Qh(this),o=eu(t),a=i(e,n,o);if(a.done)return a.value;var r=n.lastIndex;tu(r,0)||(n.lastIndex=0);var s=nu(n,o);return tu(n.lastIndex,r)||(n.lastIndex=r),null===s?-1:s.index}]}));var ou=p,au=v,ru=F,su=No,lu=qo.trim,cu=Do,hu=ou.parseInt,uu=ou.Symbol,du=uu&&uu.iterator,fu=/^[+-]?0x/i,pu=ru(fu.exec),gu=8!==hu(cu+"08")||22!==hu(cu+"0x16")||du&&!au((function(){hu(Object(du))}))?function(t,e){var i=lu(su(t));return hu(i,e>>>0||(pu(fu,i)?16:10))}:hu;ro({global:!0,forced:parseInt!=gu},{parseInt:gu});var vu=Ul.map;ro({target:"Array",proto:!0,forced:!bl("map")},{map:function(t){return vu(this,t,arguments.length>1?arguments[1]:void 0)}});var bu=ro,mu=Ul.findIndex,yu=tl,wu="findIndex",Su=!0;wu in[]&&Array(1).findIndex((function(){Su=!1})),bu({target:"Array",proto:!0,forced:Su},{findIndex:function(t){return mu(this,t,arguments.length>1?arguments[1]:void 0)}}),yu(wu);var xu=Y,ku=String,Ou=TypeError,Cu=F,Tu=Be,Iu=function(t){if("object"==typeof t||xu(t))return t;throw Ou("Can't set "+ku(t)+" as a prototype")},Pu=Object.setPrototypeOf||("__proto__"in{}?function(){var t,e=!1,i={};try{(t=Cu(Object.getOwnPropertyDescriptor(Object.prototype,"__proto__").set))(i,[]),e=i instanceof Array}catch(t){}return function(i,n){return Tu(i),Iu(n),e?t(i,n):i.__proto__=n,i}}():void 0),Au=Y,$u=X,Ru=Pu,Eu=function(t,e,i){var n,o;return Ru&&Au(n=e.constructor)&&n!==i&&$u(o=n.prototype)&&o!==i.prototype&&Ru(t,o),t},ju=S,Fu=qt,_u=it,Nu=ra,Du=RegExp.prototype,Vu=function(t){var e=t.flags;return void 0!==e||"flags"in Du||Fu(t,"flags")||!_u(Du,t)?e:ju(Nu,t)},Bu=Fe.f,Lu=et,Hu=Fe,Mu=b,Uu=se("species"),zu=b,qu=p,Wu=F,Gu=Qn,Ku=Eu,Yu=Ze,Ju=an.f,Xu=it,Qu=kr,Zu=No,td=Vu,ed=ua,id=function(t,e,i){i in t||Bu(t,i,{configurable:!0,get:function(){return e[i]},set:function(t){e[i]=t}})},nd=on,od=v,ad=qt,rd=Li.enforce,sd=function(t){var e=Lu(t),i=Hu.f;Mu&&e&&!e[Uu]&&i(e,Uu,{configurable:!0,get:function(){return this}})},ld=_a,cd=Va,hd=se("match"),ud=qu.RegExp,dd=ud.prototype,fd=qu.SyntaxError,pd=Wu(dd.exec),gd=Wu("".charAt),vd=Wu("".replace),bd=Wu("".indexOf),md=Wu("".slice),yd=/^\?<[^\s\d!#%&*+<=>@^][^\s!#%&*+<=>@^]*>/,wd=/a/g,Sd=/a/g,xd=new ud(wd)!==wd,kd=ed.MISSED_STICKY,Od=ed.UNSUPPORTED_Y,Cd=zu&&(!xd||kd||ld||cd||od((function(){return Sd[hd]=!1,ud(wd)!=wd||ud(Sd)==Sd||"/a/i"!=ud(wd,"i")})));if(Gu("RegExp",Cd)){for(var Td=function(t,e){var i,n,o,a,r,s,l=Xu(dd,this),c=Qu(t),h=void 0===e,u=[],d=t;if(!l&&c&&h&&t.constructor===Td)return t;if((c||Xu(dd,t))&&(t=t.source,h&&(e=td(d))),t=void 0===t?"":Zu(t),e=void 0===e?"":Zu(e),d=t,ld&&"dotAll"in wd&&(n=!!e&&bd(e,"s")>-1)&&(e=vd(e,/s/g,"")),i=e,kd&&"sticky"in wd&&(o=!!e&&bd(e,"y")>-1)&&Od&&(e=vd(e,/y/g,"")),cd&&(a=function(t){for(var e,i=t.length,n=0,o="",a=[],r={},s=!1,l=!1,c=0,h="";n<=i;n++){if("\\"===(e=gd(t,n)))e+=gd(t,++n);else if("]"===e)s=!1;else if(!s)switch(!0){case"["===e:s=!0;break;case"("===e:pd(yd,md(t,n+1))&&(n+=2,l=!0),o+=e,c++;continue;case">"===e&&l:if(""===h||ad(r,h))throw new fd("Invalid capture group name");r[h]=!0,a[a.length]=[h,c],l=!1,h="";continue}l?h+=e:o+=e}return[o,a]}(t),t=a[0],u=a[1]),r=Ku(ud(t,e),l?this:dd,Td),(n||o||u.length)&&(s=rd(r),n&&(s.dotAll=!0,s.raw=Td(function(t){for(var e,i=t.length,n=0,o="",a=!1;n<=i;n++)"\\"!==(e=gd(t,n))?a||"."!==e?("["===e?a=!0:"]"===e&&(a=!1),o+=e):o+="[\\s\\S]":o+=e+gd(t,++n);return o}(t),i)),o&&(s.sticky=!0),u.length&&(s.groups=u)),t!==d)try{Yu(r,"source",""===d?"(?:)":d)}catch(t){}return r},Id=Ju(ud),Pd=0;Id.length>Pd;)id(Td,ud,Id[Pd++]);dd.constructor=Td,Td.prototype=dd,nd(qu,"RegExp",Td,{constructor:!0})}sd("RegExp");var Ad=ri.PROPER,$d=on,Rd=Be,Ed=No,jd=v,Fd=Vu,_d="toString",Nd=RegExp.prototype.toString,Dd=jd((function(){return"/a/b"!=Nd.call({source:"a",flags:"b"})})),Vd=Ad&&Nd.name!=_d;(Dd||Vd)&&$d(RegExp.prototype,_d,(function(){var t=Rd(this);return"/"+Ed(t.source)+"/"+Ed(Fd(t))}),{unsafe:!0});var Bd=F([].slice),Ld=ro,Hd=ol,Md=Dr,Ud=X,zd=fn,qd=mn,Wd=K,Gd=ns,Kd=se,Yd=Bd,Jd=bl("slice"),Xd=Kd("species"),Qd=Array,Zd=Math.max;Ld({target:"Array",proto:!0,forced:!Jd},{slice:function(t,e){var i,n,o,a=Wd(this),r=qd(a),s=zd(t,r),l=zd(void 0===e?r:e,r);if(Hd(a)&&(i=a.constructor,(Md(i)&&(i===Qd||Hd(i.prototype))||Ud(i)&&null===(i=i[Xd]))&&(i=void 0),i===Qd||void 0===i))return Yd(a,s,l);for(n=new(void 0===i?Qd:i)(Zd(l-s,0)),o=0;s<l;s++,o++)s in a&&Gd(n,o,a[s]);return n.length=o,n}});var tf,ef,nf,of={},af=!v((function(){function t(){}return t.prototype.constructor=null,Object.getPrototypeOf(new t)!==t.prototype})),rf=qt,sf=Y,lf=Mt,cf=af,hf=Si("IE_PROTO"),uf=Object,df=uf.prototype,ff=cf?uf.getPrototypeOf:function(t){var e=lf(t);if(rf(e,hf))return e[hf];var i=e.constructor;return sf(i)&&e instanceof i?i.prototype:e instanceof uf?df:null},pf=v,gf=Y,vf=ff,bf=on,mf=se("iterator"),yf=!1;[].keys&&("next"in(nf=[].keys())?(ef=vf(vf(nf)))!==Object.prototype&&(tf=ef):yf=!0);var wf=null==tf||pf((function(){var t={};return tf[mf].call(t)!==t}));wf&&(tf={}),gf(tf[mf])||bf(tf,mf,(function(){return this}));var Sf={IteratorPrototype:tf,BUGGY_SAFARI_ITERATORS:yf},xf=Fe.f,kf=qt,Of=se("toStringTag"),Cf=function(t,e,i){t&&!i&&(t=t.prototype),t&&!kf(t,Of)&&xf(t,Of,{configurable:!0,value:e})},Tf=Sf.IteratorPrototype,If=Ea,Pf=P,Af=Cf,$f=of,Rf=function(){return this},Ef=ro,jf=S,Ff=Y,_f=function(t,e,i,n){var o=e+" Iterator";return t.prototype=If(Tf,{next:Pf(+!n,i)}),Af(t,o,!1),$f[o]=Rf,t},Nf=ff,Df=Pu,Vf=Cf,Bf=Ze,Lf=on,Hf=of,Mf=ri.PROPER,Uf=ri.CONFIGURABLE,zf=Sf.IteratorPrototype,qf=Sf.BUGGY_SAFARI_ITERATORS,Wf=se("iterator"),Gf="keys",Kf="values",Yf="entries",Jf=function(){return this},Xf=K,Qf=tl,Zf=of,tp=Li,ep=Fe.f,ip=function(t,e,i,n,o,a,r){_f(i,e,n);var s,l,c,h=function(t){if(t===o&&g)return g;if(!qf&&t in f)return f[t];switch(t){case Gf:case Kf:case Yf:return function(){return new i(this,t)}}return function(){return new i(this)}},u=e+" Iterator",d=!1,f=t.prototype,p=f[Wf]||f["@@iterator"]||o&&f[o],g=!qf&&p||h(o),v="Array"==e&&f.entries||p;if(v&&(s=Nf(v.call(new t)))!==Object.prototype&&s.next&&(Nf(s)!==zf&&(Df?Df(s,zf):Ff(s[Wf])||Lf(s,Wf,Jf)),Vf(s,u,!0)),Mf&&o==Kf&&p&&p.name!==Kf&&(Uf?Bf(f,"name",Kf):(d=!0,g=function(){return jf(p,this)})),o)if(l={values:h(Kf),keys:a?g:h(Gf),entries:h(Yf)},r)for(c in l)(qf||d||!(c in f))&&Lf(f,c,l[c]);else Ef({target:e,proto:!0,forced:qf||d},l);return f[Wf]!==g&&Lf(f,Wf,g,{name:o}),Hf[e]=g,l},np=b,op="Array Iterator",ap=tp.set,rp=tp.getterFor(op),sp=ip(Array,"Array",(function(t,e){ap(this,{type:op,target:Xf(t),index:0,kind:e})}),(function(){var t=rp(this),e=t.target,i=t.kind,n=t.index++;return!e||n>=e.length?(t.target=void 0,{value:void 0,done:!0}):"keys"==i?{value:n,done:!1}:"values"==i?{value:e[n],done:!1}:{value:[n,e[n]],done:!1}}),"values"),lp=Zf.Arguments=Zf.Array;if(Qf("keys"),Qf("values"),Qf("entries"),np&&"values"!==lp.name)try{ep(lp,"name",{value:"values"})}catch(t){}var cp=p,hp=lc,up=uc,dp=sp,fp=Ze,pp=se,gp=pp("iterator"),vp=pp("toStringTag"),bp=dp.values,mp=function(t,e){if(t){if(t[gp]!==bp)try{fp(t,gp,bp)}catch(e){t[gp]=bp}if(t[vp]||fp(t,vp,e),hp[e])for(var i in dp)if(t[i]!==dp[i])try{fp(t,i,dp[i])}catch(e){t[i]=dp[i]}}};for(var yp in hp)mp(cp[yp]&&cp[yp].prototype,yp);mp(up,"DOMTokenList");var wp=ro,Sp=Mt,xp=fn,kp=cn,Op=mn,Cp=rl,Tp=fl,Ip=ns,Pp=Vc,Ap=bl("splice"),$p=Math.max,Rp=Math.min;wp({target:"Array",proto:!0,forced:!Ap},{splice:function(t,e){var i,n,o,a,r,s,l=Sp(this),c=Op(l),h=xp(t,c),u=arguments.length;for(0===u?i=n=0:1===u?(i=0,n=c-h):(i=u-2,n=Rp($p(kp(e),0),c-h)),Cp(c+i-n),o=Tp(l,n),a=0;a<n;a++)(r=h+a)in l&&Ip(o,a,l[r]);if(o.length=n,i<n){for(a=h;a<c-n;a++)s=a+i,(r=a+n)in l?l[s]=l[r]:Pp(l,s);for(a=c;a>c-n+i;a--)Pp(l,a-1)}else if(i>n)for(a=c-n;a>h;a--)s=a+i-1,(r=a+n-1)in l?l[s]=l[r]:Pp(l,s);for(a=0;a<i;a++)l[a+h]=arguments[a+2];return l.length=c-n+i,o}});var Ep=F(1..valueOf),jp=b,Fp=p,_p=F,Np=Qn,Dp=on,Vp=qt,Bp=Eu,Lp=it,Hp=yt,Mp=ge,Up=v,zp=an.f,qp=g.f,Wp=Fe.f,Gp=Ep,Kp=qo.trim,Yp="Number",Jp=Fp.Number,Xp=Jp.prototype,Qp=Fp.TypeError,Zp=_p("".slice),tg=_p("".charCodeAt),eg=function(t){var e=Mp(t,"number");return"bigint"==typeof e?e:ig(e)},ig=function(t){var e,i,n,o,a,r,s,l,c=Mp(t,"number");if(Hp(c))throw Qp("Cannot convert a Symbol value to a number");if("string"==typeof c&&c.length>2)if(c=Kp(c),43===(e=tg(c,0))||45===e){if(88===(i=tg(c,2))||120===i)return NaN}else if(48===e){switch(tg(c,1)){case 66:case 98:n=2,o=49;break;case 79:case 111:n=8,o=55;break;default:return+c}for(r=(a=Zp(c,2)).length,s=0;s<r;s++)if((l=tg(a,s))<48||l>o)return NaN;return parseInt(a,n)}return+c};if(Np(Yp,!Jp(" 0o1")||!Jp("0b1")||Jp("+0x1"))){for(var ng,og=function(t){var e=arguments.length<1?0:Jp(eg(t)),i=this;return Lp(Xp,i)&&Up((function(){Gp(i)}))?Bp(Object(e),i,og):e},ag=jp?zp(Jp):"MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,EPSILON,MAX_SAFE_INTEGER,MIN_SAFE_INTEGER,isFinite,isInteger,isNaN,isSafeInteger,parseFloat,parseInt,fromString,range".split(","),rg=0;ag.length>rg;rg++)Vp(Jp,ng=ag[rg])&&!Vp(og,ng)&&Wp(og,ng,qp(Jp,ng));og.prototype=Xp,Xp.constructor=og,Dp(Fp,Yp,og,{constructor:!0})}var sg=ro,lg=ol,cg=F([].reverse),hg=[1,2];sg({target:"Array",proto:!0,forced:String(hg)===String(hg.reverse())},{reverse:function(){return lg(this)&&(this.length=this.length),cg(this)}});var ug=Mt,dg=co;ro({target:"Object",stat:!0,forced:v((function(){dg(1)}))},{keys:function(t){return dg(ug(t))}});var fg=S,pg=Be,gg=vn,vg=No,bg=q,mg=It,yg=Zr,wg=vs;yr("match",(function(t,e,i){return[function(e){var i=bg(this),n=null==e?void 0:mg(e,t);return n?fg(n,e,i):new RegExp(e)[t](vg(i))},function(t){var n=pg(this),o=vg(t),a=i(e,n,o);if(a.done)return a.value;if(!n.global)return wg(n,o);var r=n.unicode;n.lastIndex=0;for(var s,l=[],c=0;null!==(s=wg(n,o));){var h=vg(s[0]);l[c]=h,""===h&&(n.lastIndex=yg(o,gg(n.lastIndex),r)),c++}return 0===c?null:l}]}));var Sg,xg=ro,kg=F,Og=g.f,Cg=vn,Tg=No,Ig=Zl,Pg=q,Ag=ec,$g=kg("".startsWith),Rg=kg("".slice),Eg=Math.min,jg=Ag("startsWith");xg({target:"String",proto:!0,forced:!!(jg||(Sg=Og(String.prototype,"startsWith"),!Sg||Sg.writable))&&!jg},{startsWith:function(t){var e=Tg(Pg(this));Ig(t);var i=Cg(Eg(arguments.length>1?arguments[1]:void 0,e.length)),n=Tg(t);return $g?$g(e,n,i):Rg(e,i,i+n.length)===n}});var Fg=ro,_g=F,Ng=g.f,Dg=vn,Vg=No,Bg=Zl,Lg=q,Hg=ec,Mg=_g("".endsWith),Ug=_g("".slice),zg=Math.min,qg=Hg("endsWith"),Wg=!qg&&!!function(){var t=Ng(String.prototype,"endsWith");return t&&!t.writable}();Fg({target:"String",proto:!0,forced:!Wg&&!qg},{endsWith:function(t){var e=Vg(Lg(this));Bg(t);var i=arguments.length>1?arguments[1]:void 0,n=e.length,o=void 0===i?n:zg(Dg(i),n),a=Vg(t);return Mg?Mg(e,a,o):Ug(e,o-a.length,o)===a}});var Gg={getBootstrapVersion:function(){var t=5;try{var e=i.default.fn.dropdown.Constructor.VERSION;void 0!==e&&(t=parseInt(e,10))}catch(t){}try{var n=bootstrap.Tooltip.VERSION;void 0!==n&&(t=parseInt(n,10))}catch(t){}return t},getIconsPrefix:function(t){return{bootstrap3:"glyphicon",bootstrap4:"fa",bootstrap5:"bi","bootstrap-table":"icon",bulma:"fa",foundation:"fa",materialize:"material-icons",semantic:"fa"}[t]||"fa"},getIcons:function(t){return{glyphicon:{paginationSwitchDown:"glyphicon-collapse-down icon-chevron-down",paginationSwitchUp:"glyphicon-collapse-up icon-chevron-up",refresh:"glyphicon-refresh icon-refresh",toggleOff:"glyphicon-list-alt icon-list-alt",toggleOn:"glyphicon-list-alt icon-list-alt",columns:"glyphicon-th icon-th",detailOpen:"glyphicon-plus icon-plus",detailClose:"glyphicon-minus icon-minus",fullscreen:"glyphicon-fullscreen",search:"glyphicon-search",clearSearch:"glyphicon-trash"},fa:{paginationSwitchDown:"fa-caret-square-down",paginationSwitchUp:"fa-caret-square-up",refresh:"fa-sync",toggleOff:"fa-toggle-off",toggleOn:"fa-toggle-on",columns:"fa-th-list",detailOpen:"fa-plus",detailClose:"fa-minus",fullscreen:"fa-arrows-alt",search:"fa-search",clearSearch:"fa-trash"},bi:{paginationSwitchDown:"bi-caret-down-square",paginationSwitchUp:"bi-caret-up-square",refresh:"bi-arrow-clockwise",toggleOff:"bi-toggle-off",toggleOn:"bi-toggle-on",columns:"bi-list-ul",detailOpen:"bi-plus",detailClose:"bi-dash",fullscreen:"bi-arrows-move",search:"bi-search",clearSearch:"bi-trash"},icon:{paginationSwitchDown:"icon-arrow-up-circle",paginationSwitchUp:"icon-arrow-down-circle",refresh:"icon-refresh-cw",toggleOff:"icon-toggle-right",toggleOn:"icon-toggle-right",columns:"icon-list",detailOpen:"icon-plus",detailClose:"icon-minus",fullscreen:"icon-maximize",search:"icon-search",clearSearch:"icon-trash-2"},"material-icons":{paginationSwitchDown:"grid_on",paginationSwitchUp:"grid_off",refresh:"refresh",toggleOff:"tablet",toggleOn:"tablet_android",columns:"view_list",detailOpen:"add",detailClose:"remove",fullscreen:"fullscreen",sort:"sort",search:"search",clearSearch:"delete"}}[t]},getSearchInput:function(t){return"string"==typeof t.options.searchSelector?i.default(t.options.searchSelector):t.$toolbar.find(".search input")},sprintf:function(t){for(var e=arguments.length,i=new Array(e>1?e-1:0),n=1;n<e;n++)i[n-1]=arguments[n];var o=!0,a=0,r=t.replace(/%s/g,(function(){var t=i[a++];return void 0===t?(o=!1,""):t}));return o?r:""},isObject:function(t){return t instanceof Object&&!Array.isArray(t)},isEmptyObject:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return 0===Object.entries(t).length&&t.constructor===Object},isNumeric:function(t){return!isNaN(parseFloat(t))&&isFinite(t)},getFieldTitle:function(t,e){var i,n=u(t);try{for(n.s();!(i=n.n()).done;){var o=i.value;if(o.field===e)return o.title}}catch(t){n.e(t)}finally{n.f()}return""},setFieldIndex:function(t){var e,i=0,n=[],o=u(t[0]);try{for(o.s();!(e=o.n()).done;){i+=e.value.colspan||1}}catch(t){o.e(t)}finally{o.f()}for(var a=0;a<t.length;a++){n[a]=[];for(var r=0;r<i;r++)n[a][r]=!1}for(var s=0;s<t.length;s++){var l,c=u(t[s]);try{for(c.s();!(l=c.n()).done;){var h=l.value,d=h.rowspan||1,f=h.colspan||1,p=n[s].indexOf(!1);h.colspanIndex=p,1===f?(h.fieldIndex=p,void 0===h.field&&(h.field=p)):h.colspanGroup=h.colspan;for(var g=0;g<d;g++)for(var v=0;v<f;v++)n[s+g][p+v]=!0}}catch(t){c.e(t)}finally{c.f()}}},normalizeAccent:function(t){return"string"!=typeof t?t:t.normalize("NFD").replace(/[\u0300-\u036f]/g,"")},updateFieldGroup:function(t,e){var i,n,o=(i=[]).concat.apply(i,l(t)),a=u(t);try{for(a.s();!(n=a.n()).done;){var r,s=u(n.value);try{for(s.s();!(r=s.n()).done;){var c=r.value;if(c.colspanGroup>1){for(var h=0,d=function(t){o.find((function(e){return e.fieldIndex===t})).visible&&h++},f=c.colspanIndex;f<c.colspanIndex+c.colspanGroup;f++)d(f);c.colspan=h,c.visible=h>0}}}catch(t){s.e(t)}finally{s.f()}}}catch(t){a.e(t)}finally{a.f()}if(!(t.length<2)){var p,g=u(e);try{var v=function(){var t=p.value,e=o.filter((function(e){return e.fieldIndex===t.fieldIndex}));if(e.length>1){var i,n=u(e);try{for(n.s();!(i=n.n()).done;){i.value.visible=t.visible}}catch(t){n.e(t)}finally{n.f()}}};for(g.s();!(p=g.n()).done;)v()}catch(t){g.e(t)}finally{g.f()}}},getScrollBarWidth:function(){if(void 0===this.cachedWidth){var t=i.default("<div/>").addClass("fixed-table-scroll-inner"),e=i.default("<div/>").addClass("fixed-table-scroll-outer");e.append(t),i.default("body").append(e);var n=t[0].offsetWidth;e.css("overflow","scroll");var o=t[0].offsetWidth;n===o&&(o=e[0].clientWidth),e.remove(),this.cachedWidth=n-o}return this.cachedWidth},calculateObjectValue:function(t,e,i,o){var a=e;if("string"==typeof e){var r=e.split(".");if(r.length>1){a=window;var s,c=u(r);try{for(c.s();!(s=c.n()).done;){a=a[s.value]}}catch(t){c.e(t)}finally{c.f()}}else a=window[e]}return null!==a&&"object"===n(a)?a:"function"==typeof a?a.apply(t,i||[]):!a&&"string"==typeof e&&i&&this.sprintf.apply(this,[e].concat(l(i)))?this.sprintf.apply(this,[e].concat(l(i))):o},compareObjects:function(t,e,i){var n=Object.keys(t),o=Object.keys(e);if(i&&n.length!==o.length)return!1;for(var a=0,r=n;a<r.length;a++){var s=r[a];if(o.includes(s)&&t[s]!==e[s])return!1}return!0},regexCompare:function(t,e){try{var i=e.match(/^\/(.*?)\/([gim]*)$/);if(-1!==t.toString().search(i?new RegExp(i[1],i[2]):new RegExp(e,"gim")))return!0}catch(t){return!1}return!1},escapeHTML:function(t){return t?t.toString().replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;"):t},unescapeHTML:function(t){return"string"==typeof t&&t?t.toString().replace(/&amp;/g,"&").replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&quot;/g,'"').replace(/&#39;/g,"'"):t},removeHTML:function(t){return t?t.toString().replace(/(<([^>]+)>)/gi,"").replace(/&[#A-Za-z0-9]+;/gi,"").trim():t},getRealDataAttr:function(t){for(var e=0,i=Object.entries(t);e<i.length;e++){var n=s(i[e],2),o=n[0],a=n[1],r=o.split(/(?=[A-Z])/).join("-").toLowerCase();r!==o&&(t[r]=a,delete t[o])}return t},getItemField:function(t,e,i){var n=arguments.length>3&&void 0!==arguments[3]?arguments[3]:void 0,o=t;if(void 0!==n&&(i=n),"string"!=typeof e||t.hasOwnProperty(e))return i?this.escapeHTML(t[e]):t[e];var a,r=e.split("."),s=u(r);try{for(s.s();!(a=s.n()).done;){var l=a.value;o=o&&o[l]}}catch(t){s.e(t)}finally{s.f()}return i?this.escapeHTML(o):o},isIEBrowser:function(){return navigator.userAgent.includes("MSIE ")||/Trident.*rv:11\./.test(navigator.userAgent)},findIndex:function(t,e){var i,n=u(t);try{for(n.s();!(i=n.n()).done;){var o=i.value;if(JSON.stringify(o)===JSON.stringify(e))return t.indexOf(o)}}catch(t){n.e(t)}finally{n.f()}return-1},trToData:function(t,e){var n=this,o=[],a=[];return e.each((function(e,r){var s=i.default(r),l={};l._id=s.attr("id"),l._class=s.attr("class"),l._data=n.getRealDataAttr(s.data()),l._style=s.attr("style"),s.find(">td,>th").each((function(o,r){for(var s=i.default(r),c=+s.attr("colspan")||1,h=+s.attr("rowspan")||1,u=o;a[e]&&a[e][u];u++);for(var d=u;d<u+c;d++)for(var f=e;f<e+h;f++)a[f]||(a[f]=[]),a[f][d]=!0;var p=t[u].field;l[p]=s.html().trim(),l["_".concat(p,"_id")]=s.attr("id"),l["_".concat(p,"_class")]=s.attr("class"),l["_".concat(p,"_rowspan")]=s.attr("rowspan"),l["_".concat(p,"_colspan")]=s.attr("colspan"),l["_".concat(p,"_title")]=s.attr("title"),l["_".concat(p,"_data")]=n.getRealDataAttr(s.data()),l["_".concat(p,"_style")]=s.attr("style")})),o.push(l)})),o},sort:function(t,e,i,n,o,a){if(null==t&&(t=""),null==e&&(e=""),n.sortStable&&t===e&&(t=o,e=a),this.isNumeric(t)&&this.isNumeric(e))return(t=parseFloat(t))<(e=parseFloat(e))?-1*i:t>e?i:0;if(n.sortEmptyLast){if(""===t)return 1;if(""===e)return-1}return t===e?0:("string"!=typeof t&&(t=t.toString()),-1===t.localeCompare(e)?-1*i:i)},getEventName:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"";return e=e||"".concat(+new Date).concat(~~(1e6*Math.random())),"".concat(t,"-").concat(e)},hasDetailViewIcon:function(t){return t.detailView&&t.detailViewIcon&&!t.cardView},getDetailViewIndexOffset:function(t){return this.hasDetailViewIcon(t)&&"right"!==t.detailViewAlign?1:0},checkAutoMergeCells:function(t){var e,i=u(t);try{for(i.s();!(e=i.n()).done;)for(var n=e.value,o=0,a=Object.keys(n);o<a.length;o++){var r=a[o];if(r.startsWith("_")&&(r.endsWith("_rowspan")||r.endsWith("_colspan")))return!0}}catch(t){i.e(t)}finally{i.f()}return!1},deepCopy:function(t){return void 0===t?t:i.default.extend(!0,Array.isArray(t)?[]:{},t)},debounce:function(t,e,i){var n;return function(){var o=this,a=arguments,r=function(){n=null,i||t.apply(o,a)},s=i&&!n;clearTimeout(n),n=setTimeout(r,e),s&&t.apply(o,a)}}},Kg=Gg.getBootstrapVersion(),Yg={3:{classes:{buttonsPrefix:"btn",buttons:"default",buttonsGroup:"btn-group",buttonsDropdown:"btn-group",pull:"pull",inputGroup:"input-group",inputPrefix:"input-",input:"form-control",select:"form-control",paginationDropdown:"btn-group dropdown",dropup:"dropup",dropdownActive:"active",paginationActive:"active",buttonActive:"active"},html:{toolbarDropdown:['<ul class="dropdown-menu" role="menu">',"</ul>"],toolbarDropdownItem:'<li class="dropdown-item-marker" role="menuitem"><label>%s</label></li>',toolbarDropdownSeparator:'<li class="divider"></li>',pageDropdown:['<ul class="dropdown-menu" role="menu">',"</ul>"],pageDropdownItem:'<li role="menuitem" class="%s"><a href="#">%s</a></li>',dropdownCaret:'<span class="caret"></span>',pagination:['<ul class="pagination%s">',"</ul>"],paginationItem:'<li class="page-item%s"><a class="page-link" aria-label="%s" href="javascript:void(0)">%s</a></li>',icon:'<i class="%s %s"></i>',inputGroup:'<div class="input-group">%s<span class="input-group-btn">%s</span></div>',searchInput:'<input class="%s%s" type="text" placeholder="%s">',searchButton:'<button class="%s" type="button" name="search" title="%s">%s %s</button>',searchClearButton:'<button class="%s" type="button" name="clearSearch" title="%s">%s %s</button>'}},4:{classes:{buttonsPrefix:"btn",buttons:"secondary",buttonsGroup:"btn-group",buttonsDropdown:"btn-group",pull:"float",inputGroup:"btn-group",inputPrefix:"form-control-",input:"form-control",select:"form-control",paginationDropdown:"btn-group dropdown",dropup:"dropup",dropdownActive:"active",paginationActive:"active",buttonActive:"active"},html:{toolbarDropdown:['<div class="dropdown-menu dropdown-menu-right">',"</div>"],toolbarDropdownItem:'<label class="dropdown-item dropdown-item-marker">%s</label>',pageDropdown:['<div class="dropdown-menu">',"</div>"],pageDropdownItem:'<a class="dropdown-item %s" href="#">%s</a>',toolbarDropdownSeparator:'<div class="dropdown-divider"></div>',dropdownCaret:'<span class="caret"></span>',pagination:['<ul class="pagination%s">',"</ul>"],paginationItem:'<li class="page-item%s"><a class="page-link" aria-label="%s" href="javascript:void(0)">%s</a></li>',icon:'<i class="%s %s"></i>',inputGroup:'<div class="input-group">%s<div class="input-group-append">%s</div></div>',searchInput:'<input class="%s%s" type="text" placeholder="%s">',searchButton:'<button class="%s" type="button" name="search" title="%s">%s %s</button>',searchClearButton:'<button class="%s" type="button" name="clearSearch" title="%s">%s %s</button>'}},5:{classes:{buttonsPrefix:"btn",buttons:"secondary",buttonsGroup:"btn-group",buttonsDropdown:"btn-group",pull:"float",inputGroup:"btn-group",inputPrefix:"form-control-",input:"form-control",select:"form-select",paginationDropdown:"btn-group dropdown",dropup:"dropup",dropdownActive:"active",paginationActive:"active",buttonActive:"active"},html:{dataToggle:"data-bs-toggle",toolbarDropdown:['<div class="dropdown-menu dropdown-menu-right">',"</div>"],toolbarDropdownItem:'<label class="dropdown-item dropdown-item-marker">%s</label>',pageDropdown:['<div class="dropdown-menu">',"</div>"],pageDropdownItem:'<a class="dropdown-item %s" href="#">%s</a>',toolbarDropdownSeparator:'<div class="dropdown-divider"></div>',dropdownCaret:'<span class="caret"></span>',pagination:['<ul class="pagination%s">',"</ul>"],paginationItem:'<li class="page-item%s"><a class="page-link" aria-label="%s" href="javascript:void(0)">%s</a></li>',icon:'<i class="%s %s"></i>',inputGroup:'<div class="input-group">%s%s</div>',searchInput:'<input class="%s%s" type="text" placeholder="%s">',searchButton:'<button class="%s" type="button" name="search" title="%s">%s %s</button>',searchClearButton:'<button class="%s" type="button" name="clearSearch" title="%s">%s %s</button>'}}}[Kg],Jg={height:void 0,classes:"table table-bordered table-hover",buttons:{},theadClasses:"",headerStyle:function(t){return{}},rowStyle:function(t,e){return{}},rowAttributes:function(t,e){return{}},undefinedText:"-",locale:void 0,virtualScroll:!1,virtualScrollItemHeight:void 0,sortable:!0,sortClass:void 0,silentSort:!0,sortEmptyLast:!1,sortName:void 0,sortOrder:void 0,sortReset:!1,sortStable:!1,rememberOrder:!1,serverSort:!0,customSort:void 0,columns:[[]],data:[],url:void 0,method:"get",cache:!0,contentType:"application/json",dataType:"json",ajax:void 0,ajaxOptions:{},queryParams:function(t){return t},queryParamsType:"limit",responseHandler:function(t){return t},totalField:"total",totalNotFilteredField:"totalNotFiltered",dataField:"rows",footerField:"footer",pagination:!1,paginationParts:["pageInfo","pageSize","pageList"],showExtendedPagination:!1,paginationLoop:!0,sidePagination:"client",totalRows:0,totalNotFiltered:0,pageNumber:1,pageSize:10,pageList:[10,25,50,100],paginationHAlign:"right",paginationVAlign:"bottom",paginationDetailHAlign:"left",paginationPreText:"&lsaquo;",paginationNextText:"&rsaquo;",paginationSuccessivelySize:5,paginationPagesBySide:1,paginationUseIntermediate:!1,search:!1,searchHighlight:!1,searchOnEnterKey:!1,strictSearch:!1,regexSearch:!1,searchSelector:!1,visibleSearch:!1,showButtonIcons:!0,showButtonText:!1,showSearchButton:!1,showSearchClearButton:!1,trimOnSearch:!0,searchAlign:"right",searchTimeOut:500,searchText:"",customSearch:void 0,showHeader:!0,showFooter:!1,footerStyle:function(t){return{}},searchAccentNeutralise:!1,showColumns:!1,showColumnsToggleAll:!1,showColumnsSearch:!1,minimumCountColumns:1,showPaginationSwitch:!1,showRefresh:!1,showToggle:!1,showFullscreen:!1,smartDisplay:!0,escape:!1,filterOptions:{filterAlgorithm:"and"},idField:void 0,selectItemName:"btSelectItem",clickToSelect:!1,ignoreClickToSelectOn:function(t){var e=t.tagName;return["A","BUTTON"].includes(e)},singleSelect:!1,checkboxHeader:!0,maintainMetaData:!1,multipleSelectRow:!1,uniqueId:void 0,cardView:!1,detailView:!1,detailViewIcon:!0,detailViewByClick:!1,detailViewAlign:"left",detailFormatter:function(t,e){return""},detailFilter:function(t,e){return!0},toolbar:void 0,toolbarAlign:"left",buttonsToolbar:void 0,buttonsAlign:"right",buttonsOrder:["paginationSwitch","refresh","toggle","fullscreen","columns"],buttonsPrefix:Yg.classes.buttonsPrefix,buttonsClass:Yg.classes.buttons,iconsPrefix:void 0,icons:{},iconSize:void 0,loadingFontSize:"auto",loadingTemplate:function(t){return'<span class="loading-wrap">\n      <span class="loading-text">'.concat(t,'</span>\n      <span class="animation-wrap"><span class="animation-dot"></span></span>\n      </span>\n    ')},onAll:function(t,e){return!1},onClickCell:function(t,e,i,n){return!1},onDblClickCell:function(t,e,i,n){return!1},onClickRow:function(t,e){return!1},onDblClickRow:function(t,e){return!1},onSort:function(t,e){return!1},onCheck:function(t){return!1},onUncheck:function(t){return!1},onCheckAll:function(t){return!1},onUncheckAll:function(t){return!1},onCheckSome:function(t){return!1},onUncheckSome:function(t){return!1},onLoadSuccess:function(t){return!1},onLoadError:function(t){return!1},onColumnSwitch:function(t,e){return!1},onColumnSwitchAll:function(t){return!1},onPageChange:function(t,e){return!1},onSearch:function(t){return!1},onToggle:function(t){return!1},onPreBody:function(t){return!1},onPostBody:function(){return!1},onPostHeader:function(){return!1},onPostFooter:function(){return!1},onExpandRow:function(t,e,i){return!1},onCollapseRow:function(t,e){return!1},onRefreshOptions:function(t){return!1},onRefresh:function(t){return!1},onResetView:function(){return!1},onScrollBody:function(){return!1},onTogglePagination:function(t){return!1},onVirtualScroll:function(t,e){return!1}},Xg={formatLoadingMessage:function(){return"Loading, please wait"},formatRecordsPerPage:function(t){return"".concat(t," rows per page")},formatShowingRows:function(t,e,i,n){return void 0!==n&&n>0&&n>i?"Showing ".concat(t," to ").concat(e," of ").concat(i," rows (filtered from ").concat(n," total rows)"):"Showing ".concat(t," to ").concat(e," of ").concat(i," rows")},formatSRPaginationPreText:function(){return"previous page"},formatSRPaginationPageText:function(t){return"to page ".concat(t)},formatSRPaginationNextText:function(){return"next page"},formatDetailPagination:function(t){return"Showing ".concat(t," rows")},formatSearch:function(){return"Search"},formatClearSearch:function(){return"Clear Search"},formatNoMatches:function(){return"No matching records found"},formatPaginationSwitch:function(){return"Hide/Show pagination"},formatPaginationSwitchDown:function(){return"Show pagination"},formatPaginationSwitchUp:function(){return"Hide pagination"},formatRefresh:function(){return"Refresh"},formatToggleOn:function(){return"Show card view"},formatToggleOff:function(){return"Hide card view"},formatColumns:function(){return"Columns"},formatColumnsToggleAll:function(){return"Toggle all"},formatFullscreen:function(){return"Fullscreen"},formatAllRows:function(){return"All"}},Qg={field:void 0,title:void 0,titleTooltip:void 0,class:void 0,width:void 0,widthUnit:"px",rowspan:void 0,colspan:void 0,align:void 0,halign:void 0,falign:void 0,valign:void 0,cellStyle:void 0,radio:!1,checkbox:!1,checkboxEnabled:!0,clickToSelect:!0,showSelectTitle:!1,sortable:!1,sortName:void 0,order:"asc",sorter:void 0,visible:!0,switchable:!0,cardVisible:!0,searchable:!0,formatter:void 0,footerFormatter:void 0,detailFormatter:void 0,searchFormatter:!0,searchHighlightFormatter:!1,escape:void 0,events:void 0};Object.assign(Jg,Xg);var Zg={VERSION:"1.21.1",THEME:"bootstrap".concat(Kg),CONSTANTS:Yg,DEFAULTS:Jg,COLUMN_DEFAULTS:Qg,METHODS:["getOptions","refreshOptions","getData","getSelections","load","append","prepend","remove","removeAll","insertRow","updateRow","getRowByUniqueId","updateByUniqueId","removeByUniqueId","updateCell","updateCellByUniqueId","showRow","hideRow","getHiddenRows","showColumn","hideColumn","getVisibleColumns","getHiddenColumns","showAllColumns","hideAllColumns","mergeCells","checkAll","uncheckAll","checkInvert","check","uncheck","checkBy","uncheckBy","refresh","destroy","resetView","showLoading","hideLoading","togglePagination","toggleFullscreen","toggleView","resetSearch","filterBy","scrollTo","getScrollPosition","selectPage","prevPage","nextPage","toggleDetailView","expandRow","collapseRow","expandRowByUniqueId","collapseRowByUniqueId","expandAllRows","collapseAllRows","updateColumnTitle","updateFormatText"],EVENTS:{"all.bs.table":"onAll","click-row.bs.table":"onClickRow","dbl-click-row.bs.table":"onDblClickRow","click-cell.bs.table":"onClickCell","dbl-click-cell.bs.table":"onDblClickCell","sort.bs.table":"onSort","check.bs.table":"onCheck","uncheck.bs.table":"onUncheck","check-all.bs.table":"onCheckAll","uncheck-all.bs.table":"onUncheckAll","check-some.bs.table":"onCheckSome","uncheck-some.bs.table":"onUncheckSome","load-success.bs.table":"onLoadSuccess","load-error.bs.table":"onLoadError","column-switch.bs.table":"onColumnSwitch","column-switch-all.bs.table":"onColumnSwitchAll","page-change.bs.table":"onPageChange","search.bs.table":"onSearch","toggle.bs.table":"onToggle","pre-body.bs.table":"onPreBody","post-body.bs.table":"onPostBody","post-header.bs.table":"onPostHeader","post-footer.bs.table":"onPostFooter","expand-row.bs.table":"onExpandRow","collapse-row.bs.table":"onCollapseRow","refresh-options.bs.table":"onRefreshOptions","reset-view.bs.table":"onResetView","refresh.bs.table":"onRefresh","scroll-body.bs.table":"onScrollBody","toggle-pagination.bs.table":"onTogglePagination","virtual-scroll.bs.table":"onVirtualScroll"},LOCALES:{en:Xg,"en-US":Xg}},tv=function(){function t(e){var i=this;o(this,t),this.rows=e.rows,this.scrollEl=e.scrollEl,this.contentEl=e.contentEl,this.callback=e.callback,this.itemHeight=e.itemHeight,this.cache={},this.scrollTop=this.scrollEl.scrollTop,this.initDOM(this.rows,e.fixedScroll),this.scrollEl.scrollTop=this.scrollTop,this.lastCluster=0;var n=function(){i.lastCluster!==(i.lastCluster=i.getNum())&&(i.initDOM(i.rows),i.callback(i.startIndex,i.endIndex))};this.scrollEl.addEventListener("scroll",n,!1),this.destroy=function(){i.contentEl.innerHtml="",i.scrollEl.removeEventListener("scroll",n,!1)}}return r(t,[{key:"initDOM",value:function(t,e){void 0===this.clusterHeight&&(this.cache.scrollTop=this.scrollEl.scrollTop,this.cache.data=this.contentEl.innerHTML=t[0]+t[0]+t[0],this.getRowsHeight(t));var i=this.initData(t,this.getNum(e)),n=i.rows.join(""),o=this.checkChanges("data",n),a=this.checkChanges("top",i.topOffset),r=this.checkChanges("bottom",i.bottomOffset),s=[];o&&a?(i.topOffset&&s.push(this.getExtra("top",i.topOffset)),s.push(n),i.bottomOffset&&s.push(this.getExtra("bottom",i.bottomOffset)),this.startIndex=i.start,this.endIndex=i.end,this.contentEl.innerHTML=s.join(""),e&&(this.contentEl.scrollTop=this.cache.scrollTop)):r&&(this.contentEl.lastChild.style.height="".concat(i.bottomOffset,"px"))}},{key:"getRowsHeight",value:function(){if(void 0===this.itemHeight){var t=this.contentEl.children,e=t[Math.floor(t.length/2)];this.itemHeight=e.offsetHeight}this.blockHeight=50*this.itemHeight,this.clusterRows=200,this.clusterHeight=4*this.blockHeight}},{key:"getNum",value:function(t){return this.scrollTop=t?this.cache.scrollTop:this.scrollEl.scrollTop,Math.floor(this.scrollTop/(this.clusterHeight-this.blockHeight))||0}},{key:"initData",value:function(t,e){if(t.length<50)return{topOffset:0,bottomOffset:0,rowsAbove:0,rows:t};var i=Math.max((this.clusterRows-50)*e,0),n=i+this.clusterRows,o=Math.max(i*this.itemHeight,0),a=Math.max((t.length-n)*this.itemHeight,0),r=[],s=i;o<1&&s++;for(var l=i;l<n;l++)t[l]&&r.push(t[l]);return{start:i,end:n,topOffset:o,bottomOffset:a,rowsAbove:s,rows:r}}},{key:"checkChanges",value:function(t,e){var i=e!==this.cache[t];return this.cache[t]=e,i}},{key:"getExtra",value:function(t,e){var i=document.createElement("tr");return i.className="virtual-scroll-".concat(t),e&&(i.style.height="".concat(e,"px")),i.outerHTML}}]),t}(),ev=function(){function t(e,n){o(this,t),this.options=n,this.$el=i.default(e),this.$el_=this.$el.clone(),this.timeoutId_=0,this.timeoutFooter_=0}return r(t,[{key:"init",value:function(){this.initConstants(),this.initLocale(),this.initContainer(),this.initTable(),this.initHeader(),this.initData(),this.initHiddenRows(),this.initToolbar(),this.initPagination(),this.initBody(),this.initSearchText(),this.initServer()}},{key:"initConstants",value:function(){var t=this.options;this.constants=Zg.CONSTANTS,this.constants.theme=i.default.fn.bootstrapTable.theme,this.constants.dataToggle=this.constants.html.dataToggle||"data-toggle";var e=Gg.getIconsPrefix(i.default.fn.bootstrapTable.theme),o=Gg.getIcons(e);"string"==typeof t.icons&&(t.icons=Gg.calculateObjectValue(null,t.icons)),t.iconsPrefix=t.iconsPrefix||i.default.fn.bootstrapTable.defaults.iconsPrefix||e,t.icons=Object.assign(o,i.default.fn.bootstrapTable.defaults.icons,t.icons);var a=t.buttonsPrefix?"".concat(t.buttonsPrefix,"-"):"";this.constants.buttonsClass=[t.buttonsPrefix,a+t.buttonsClass,Gg.sprintf("".concat(a,"%s"),t.iconSize)].join(" ").trim(),this.buttons=Gg.calculateObjectValue(this,t.buttons,[],{}),"object"!==n(this.buttons)&&(this.buttons={})}},{key:"initLocale",value:function(){if(this.options.locale){var e=i.default.fn.bootstrapTable.locales,n=this.options.locale.split(/-|_/);n[0]=n[0].toLowerCase(),n[1]&&(n[1]=n[1].toUpperCase());var o={};e[this.options.locale]?o=e[this.options.locale]:e[n.join("-")]?o=e[n.join("-")]:e[n[0]]&&(o=e[n[0]]);for(var a=0,r=Object.entries(o);a<r.length;a++){var l=s(r[a],2),c=l[0],h=l[1];this.options[c]===t.DEFAULTS[c]&&(this.options[c]=h)}}}},{key:"initContainer",value:function(){var t=["top","both"].includes(this.options.paginationVAlign)?'<div class="fixed-table-pagination clearfix"></div>':"",e=["bottom","both"].includes(this.options.paginationVAlign)?'<div class="fixed-table-pagination"></div>':"",n=Gg.calculateObjectValue(this.options,this.options.loadingTemplate,[this.options.formatLoadingMessage()]);this.$container=i.default('\n      <div class="bootstrap-table '.concat(this.constants.theme,'">\n      <div class="fixed-table-toolbar"></div>\n      ').concat(t,'\n      <div class="fixed-table-container">\n      <div class="fixed-table-header"><table></table></div>\n      <div class="fixed-table-body">\n      <div class="fixed-table-loading">\n      ').concat(n,'\n      </div>\n      </div>\n      <div class="fixed-table-footer"></div>\n      </div>\n      ').concat(e,"\n      </div>\n    ")),this.$container.insertAfter(this.$el),this.$tableContainer=this.$container.find(".fixed-table-container"),this.$tableHeader=this.$container.find(".fixed-table-header"),this.$tableBody=this.$container.find(".fixed-table-body"),this.$tableLoading=this.$container.find(".fixed-table-loading"),this.$tableFooter=this.$el.find("tfoot"),this.options.buttonsToolbar?this.$toolbar=i.default("body").find(this.options.buttonsToolbar):this.$toolbar=this.$container.find(".fixed-table-toolbar"),this.$pagination=this.$container.find(".fixed-table-pagination"),this.$tableBody.append(this.$el),this.$container.after('<div class="clearfix"></div>'),this.$el.addClass(this.options.classes),this.$tableLoading.addClass(this.options.classes),this.options.height&&(this.$tableContainer.addClass("fixed-height"),this.options.showFooter&&this.$tableContainer.addClass("has-footer"),this.options.classes.split(" ").includes("table-bordered")&&(this.$tableBody.append('<div class="fixed-table-border"></div>'),this.$tableBorder=this.$tableBody.find(".fixed-table-border"),this.$tableLoading.addClass("fixed-table-border")),this.$tableFooter=this.$container.find(".fixed-table-footer"))}},{key:"initTable",value:function(){var e=this,n=[];if(this.$header=this.$el.find(">thead"),this.$header.length?this.options.theadClasses&&this.$header.addClass(this.options.theadClasses):this.$header=i.default('<thead class="'.concat(this.options.theadClasses,'"></thead>')).appendTo(this.$el),this._headerTrClasses=[],this._headerTrStyles=[],this.$header.find("tr").each((function(t,o){var a=i.default(o),r=[];a.find("th").each((function(t,e){var n=i.default(e);void 0!==n.data("field")&&n.data("field","".concat(n.data("field"))),r.push(i.default.extend({},{title:n.html(),class:n.attr("class"),titleTooltip:n.attr("title"),rowspan:n.attr("rowspan")?+n.attr("rowspan"):void 0,colspan:n.attr("colspan")?+n.attr("colspan"):void 0},n.data()))})),n.push(r),a.attr("class")&&e._headerTrClasses.push(a.attr("class")),a.attr("style")&&e._headerTrStyles.push(a.attr("style"))})),Array.isArray(this.options.columns[0])||(this.options.columns=[this.options.columns]),this.options.columns=i.default.extend(!0,[],n,this.options.columns),this.columns=[],this.fieldsColumnsIndex=[],Gg.setFieldIndex(this.options.columns),this.options.columns.forEach((function(n,o){n.forEach((function(n,a){var r=i.default.extend({},t.COLUMN_DEFAULTS,n,{passed:n});void 0!==r.fieldIndex&&(e.columns[r.fieldIndex]=r,e.fieldsColumnsIndex[r.field]=r.fieldIndex),e.options.columns[o][a]=r}))})),!this.options.data.length){var o=Gg.trToData(this.columns,this.$el.find(">tbody>tr"));o.length&&(this.options.data=o,this.fromHtml=!0)}this.options.pagination&&"server"!==this.options.sidePagination||(this.footerData=Gg.trToData(this.columns,this.$el.find(">tfoot>tr"))),this.footerData&&this.$el.find("tfoot").html("<tr></tr>"),!this.options.showFooter||this.options.cardView?this.$tableFooter.hide():this.$tableFooter.show()}},{key:"initHeader",value:function(){var t=this,e={},n=[];this.header={fields:[],styles:[],classes:[],formatters:[],detailFormatters:[],events:[],sorters:[],sortNames:[],cellStyles:[],searchables:[]},Gg.updateFieldGroup(this.options.columns,this.columns),this.options.columns.forEach((function(i,o){var a=[];a.push("<tr".concat(Gg.sprintf(' class="%s"',t._headerTrClasses[o])," ").concat(Gg.sprintf(' style="%s"',t._headerTrStyles[o]),">"));var r="";if(0===o&&Gg.hasDetailViewIcon(t.options)){var l=t.options.columns.length>1?' rowspan="'.concat(t.options.columns.length,'"'):"";r='<th class="detail"'.concat(l,'>\n          <div class="fht-cell"></div>\n          </th>')}r&&"right"!==t.options.detailViewAlign&&a.push(r),i.forEach((function(i,n){var r=Gg.sprintf(' class="%s"',i.class),l=i.widthUnit,c=parseFloat(i.width),h=i.halign?i.halign:i.align,u=Gg.sprintf("text-align: %s; ",h),d=Gg.sprintf("text-align: %s; ",i.align),f=Gg.sprintf("vertical-align: %s; ",i.valign);if(f+=Gg.sprintf("width: %s; ",!i.checkbox&&!i.radio||c?c?c+l:void 0:i.showSelectTitle?void 0:"36px"),void 0!==i.fieldIndex||i.visible){var p=Gg.calculateObjectValue(null,t.options.headerStyle,[i]),g=[],v="";if(p&&p.css)for(var b=0,m=Object.entries(p.css);b<m.length;b++){var y=s(m[b],2),w=y[0],S=y[1];g.push("".concat(w,": ").concat(S))}if(p&&p.classes&&(v=Gg.sprintf(' class="%s"',i.class?[i.class,p.classes].join(" "):p.classes)),void 0!==i.fieldIndex){if(t.header.fields[i.fieldIndex]=i.field,t.header.styles[i.fieldIndex]=d+f,t.header.classes[i.fieldIndex]=r,t.header.formatters[i.fieldIndex]=i.formatter,t.header.detailFormatters[i.fieldIndex]=i.detailFormatter,t.header.events[i.fieldIndex]=i.events,t.header.sorters[i.fieldIndex]=i.sorter,t.header.sortNames[i.fieldIndex]=i.sortName,t.header.cellStyles[i.fieldIndex]=i.cellStyle,t.header.searchables[i.fieldIndex]=i.searchable,!i.visible)return;if(t.options.cardView&&!i.cardVisible)return;e[i.field]=i}a.push("<th".concat(Gg.sprintf(' title="%s"',i.titleTooltip)),i.checkbox||i.radio?Gg.sprintf(' class="bs-checkbox %s"',i.class||""):v||r,Gg.sprintf(' style="%s"',u+f+g.join("; ")),Gg.sprintf(' rowspan="%s"',i.rowspan),Gg.sprintf(' colspan="%s"',i.colspan),Gg.sprintf(' data-field="%s"',i.field),0===n&&o>0?" data-not-first-th":"",">"),a.push(Gg.sprintf('<div class="th-inner %s">',t.options.sortable&&i.sortable?"sortable".concat("center"===h?" sortable-center":""," both"):""));var x=t.options.escape?Gg.escapeHTML(i.title):i.title,k=x;i.checkbox&&(x="",!t.options.singleSelect&&t.options.checkboxHeader&&(x='<label><input name="btSelectAll" type="checkbox" /><span></span></label>'),t.header.stateField=i.field),i.radio&&(x="",t.header.stateField=i.field),!x&&i.showSelectTitle&&(x+=k),a.push(x),a.push("</div>"),a.push('<div class="fht-cell"></div>'),a.push("</div>"),a.push("</th>")}})),r&&"right"===t.options.detailViewAlign&&a.push(r),a.push("</tr>"),a.length>3&&n.push(a.join(""))})),this.$header.html(n.join("")),this.$header.find("th[data-field]").each((function(t,n){i.default(n).data(e[i.default(n).data("field")])})),this.$container.off("click",".th-inner").on("click",".th-inner",(function(e){var n=i.default(e.currentTarget);if(t.options.detailView&&!n.parent().hasClass("bs-checkbox")&&n.closest(".bootstrap-table")[0]!==t.$container[0])return!1;t.options.sortable&&n.parent().data().sortable&&t.onSort(e)}));var o=Gg.getEventName("resize.bootstrap-table",this.$el.attr("id"));i.default(window).off(o),!this.options.showHeader||this.options.cardView?(this.$header.hide(),this.$tableHeader.hide(),this.$tableLoading.css("top",0)):(this.$header.show(),this.$tableHeader.show(),this.$tableLoading.css("top",this.$header.outerHeight()+1),this.getCaret(),i.default(window).on(o,(function(){return t.resetView()}))),this.$selectAll=this.$header.find('[name="btSelectAll"]'),this.$selectAll.off("click").on("click",(function(e){e.stopPropagation();var n=i.default(e.currentTarget).prop("checked");t[n?"checkAll":"uncheckAll"](),t.updateSelected()}))}},{key:"initData",value:function(t,e){"append"===e?this.options.data=this.options.data.concat(t):"prepend"===e?this.options.data=[].concat(t).concat(this.options.data):(t=t||Gg.deepCopy(this.options.data),this.options.data=Array.isArray(t)?t:t[this.options.dataField]),this.data=l(this.options.data),this.options.sortReset&&(this.unsortedData=l(this.data)),"server"!==this.options.sidePagination&&this.initSort()}},{key:"initSort",value:function(){var t=this,e=this.options.sortName,i="desc"===this.options.sortOrder?-1:1,n=this.header.fields.indexOf(this.options.sortName),o=0;-1!==n?(this.options.sortStable&&this.data.forEach((function(t,e){t.hasOwnProperty("_position")||(t._position=e)})),this.options.customSort?Gg.calculateObjectValue(this.options,this.options.customSort,[this.options.sortName,this.options.sortOrder,this.data]):this.data.sort((function(o,a){t.header.sortNames[n]&&(e=t.header.sortNames[n]);var r=Gg.getItemField(o,e,t.options.escape),s=Gg.getItemField(a,e,t.options.escape),l=Gg.calculateObjectValue(t.header,t.header.sorters[n],[r,s,o,a]);return void 0!==l?t.options.sortStable&&0===l?i*(o._position-a._position):i*l:Gg.sort(r,s,i,t.options,o._position,a._position)})),void 0!==this.options.sortClass&&(clearTimeout(o),o=setTimeout((function(){t.$el.removeClass(t.options.sortClass);var e=t.$header.find('[data-field="'.concat(t.options.sortName,'"]')).index();t.$el.find("tr td:nth-child(".concat(e+1,")")).addClass(t.options.sortClass)}),250))):this.options.sortReset&&(this.data=l(this.unsortedData))}},{key:"onSort",value:function(t){var e=t.type,n=t.currentTarget,o="keypress"===e?i.default(n):i.default(n).parent(),a=this.$header.find("th").eq(o.index());if(this.$header.add(this.$header_).find("span.order").remove(),this.options.sortName===o.data("field")){var r=this.options.sortOrder;void 0===r?this.options.sortOrder="asc":"asc"===r?this.options.sortOrder="desc":"desc"===this.options.sortOrder&&(this.options.sortOrder=this.options.sortReset?void 0:"asc"),void 0===this.options.sortOrder&&(this.options.sortName=void 0)}else this.options.sortName=o.data("field"),this.options.rememberOrder?this.options.sortOrder="asc"===o.data("order")?"desc":"asc":this.options.sortOrder=this.columns[this.fieldsColumnsIndex[o.data("field")]].sortOrder||this.columns[this.fieldsColumnsIndex[o.data("field")]].order;if(this.trigger("sort",this.options.sortName,this.options.sortOrder),o.add(a).data("order",this.options.sortOrder),this.getCaret(),"server"===this.options.sidePagination&&this.options.serverSort)return this.options.pageNumber=1,void this.initServer(this.options.silentSort);this.initSort(),this.initBody()}},{key:"initToolbar",value:function(){var t,e=this,o=this.options,a=[],r=0,l=0;this.$toolbar.find(".bs-bars").children().length&&i.default("body").append(i.default(o.toolbar)),this.$toolbar.html(""),"string"!=typeof o.toolbar&&"object"!==n(o.toolbar)||i.default(Gg.sprintf('<div class="bs-bars %s-%s"></div>',this.constants.classes.pull,o.toolbarAlign)).appendTo(this.$toolbar).append(i.default(o.toolbar)),a=['<div class="'.concat(["columns","columns-".concat(o.buttonsAlign),this.constants.classes.buttonsGroup,"".concat(this.constants.classes.pull,"-").concat(o.buttonsAlign)].join(" "),'">')],"string"==typeof o.buttonsOrder&&(o.buttonsOrder=o.buttonsOrder.replace(/\[|\]| |'/g,"").split(",")),this.buttons=Object.assign(this.buttons,{paginationSwitch:{text:o.pagination?o.formatPaginationSwitchUp():o.formatPaginationSwitchDown(),icon:o.pagination?o.icons.paginationSwitchDown:o.icons.paginationSwitchUp,render:!1,event:this.togglePagination,attributes:{"aria-label":o.formatPaginationSwitch(),title:o.formatPaginationSwitch()}},refresh:{text:o.formatRefresh(),icon:o.icons.refresh,render:!1,event:this.refresh,attributes:{"aria-label":o.formatRefresh(),title:o.formatRefresh()}},toggle:{text:o.formatToggleOn(),icon:o.icons.toggleOff,render:!1,event:this.toggleView,attributes:{"aria-label":o.formatToggleOn(),title:o.formatToggleOn()}},fullscreen:{text:o.formatFullscreen(),icon:o.icons.fullscreen,render:!1,event:this.toggleFullscreen,attributes:{"aria-label":o.formatFullscreen(),title:o.formatFullscreen()}},columns:{render:!1,html:function(){var t=[];if(t.push('<div class="keep-open '.concat(e.constants.classes.buttonsDropdown,'" title="').concat(o.formatColumns(),'">\n            <button class="').concat(e.constants.buttonsClass,' dropdown-toggle" type="button" ').concat(e.constants.dataToggle,'="dropdown"\n            aria-label="').concat(o.formatColumns(),'" title="').concat(o.formatColumns(),'">\n            ').concat(o.showButtonIcons?Gg.sprintf(e.constants.html.icon,o.iconsPrefix,o.icons.columns):"","\n            ").concat(o.showButtonText?o.formatColumns():"","\n            ").concat(e.constants.html.dropdownCaret,"\n            </button>\n            ").concat(e.constants.html.toolbarDropdown[0])),o.showColumnsSearch&&(t.push(Gg.sprintf(e.constants.html.toolbarDropdownItem,Gg.sprintf('<input type="text" class="%s" name="columnsSearch" placeholder="%s" autocomplete="off">',e.constants.classes.input,o.formatSearch()))),t.push(e.constants.html.toolbarDropdownSeparator)),o.showColumnsToggleAll){var i=e.getVisibleColumns().length===e.columns.filter((function(t){return!e.isSelectionColumn(t)})).length;t.push(Gg.sprintf(e.constants.html.toolbarDropdownItem,Gg.sprintf('<input type="checkbox" class="toggle-all" %s> <span>%s</span>',i?'checked="checked"':"",o.formatColumnsToggleAll()))),t.push(e.constants.html.toolbarDropdownSeparator)}var n=0;return e.columns.forEach((function(t){t.visible&&n++})),e.columns.forEach((function(i,a){if(!e.isSelectionColumn(i)&&(!o.cardView||i.cardVisible)){var r=i.visible?' checked="checked"':"",s=n<=o.minimumCountColumns&&r?' disabled="disabled"':"";i.switchable&&(t.push(Gg.sprintf(e.constants.html.toolbarDropdownItem,Gg.sprintf('<input type="checkbox" data-field="%s" value="%s"%s%s> <span>%s</span>',i.field,a,r,s,i.title))),l++)}})),t.push(e.constants.html.toolbarDropdown[1],"</div>"),t.join("")}}});for(var c={},h=0,d=Object.entries(this.buttons);h<d.length;h++){var f=s(d[h],2),p=f[0],g=f[1],v=void 0;if(g.hasOwnProperty("html"))"function"==typeof g.html?v=g.html():"string"==typeof g.html&&(v=g.html);else{if(v='<button class="'.concat(this.constants.buttonsClass,'" type="button" name="').concat(p,'"'),g.hasOwnProperty("attributes"))for(var b=0,m=Object.entries(g.attributes);b<m.length;b++){var y=s(m[b],2),w=y[0],S=y[1];v+=" ".concat(w,'="').concat(S,'"')}v+=">",o.showButtonIcons&&g.hasOwnProperty("icon")&&(v+="".concat(Gg.sprintf(this.constants.html.icon,o.iconsPrefix,g.icon)," ")),o.showButtonText&&g.hasOwnProperty("text")&&(v+=g.text),v+="</button>"}c[p]=v;var x="show".concat(p.charAt(0).toUpperCase()).concat(p.substring(1)),k=o[x];!(!g.hasOwnProperty("render")||g.hasOwnProperty("render")&&g.render)||void 0!==k&&!0!==k||(o[x]=!0),o.buttonsOrder.includes(p)||o.buttonsOrder.push(p)}var O,C=u(o.buttonsOrder);try{for(C.s();!(O=C.n()).done;){var T=O.value;o["show".concat(T.charAt(0).toUpperCase()).concat(T.substring(1))]&&a.push(c[T])}}catch(t){C.e(t)}finally{C.f()}a.push("</div>"),(this.showToolbar||a.length>2)&&this.$toolbar.append(a.join(""));for(var I=0,P=Object.entries(this.buttons);I<P.length;I++){var A=s(P[I],2),$=A[0],R=A[1];if(R.hasOwnProperty("event")){if("function"==typeof R.event||"string"==typeof R.event)if("continue"===function(){var t="string"==typeof R.event?window[R.event]:R.event;return e.$toolbar.find('button[name="'.concat($,'"]')).off("click").on("click",(function(){return t.call(e)})),"continue"}())continue;for(var E=function(){var t=s(F[j],2),i=t[0],n=t[1],o="string"==typeof n?window[n]:n;e.$toolbar.find('button[name="'.concat($,'"]')).off(i).on(i,(function(){return o.call(e)}))},j=0,F=Object.entries(R.event);j<F.length;j++)E()}}if(o.showColumns){var _=(t=this.$toolbar.find(".keep-open")).find('input[type="checkbox"]:not(".toggle-all")'),N=t.find('input[type="checkbox"].toggle-all');if(l<=o.minimumCountColumns&&t.find("input").prop("disabled",!0),t.find("li, label").off("click").on("click",(function(t){t.stopImmediatePropagation()})),_.off("click").on("click",(function(t){var n=t.currentTarget,o=i.default(n);e._toggleColumn(o.val(),o.prop("checked"),!1),e.trigger("column-switch",o.data("field"),o.prop("checked")),N.prop("checked",_.filter(":checked").length===e.columns.filter((function(t){return!e.isSelectionColumn(t)})).length)})),N.off("click").on("click",(function(t){var n=t.currentTarget;e._toggleAllColumns(i.default(n).prop("checked")),e.trigger("column-switch-all",i.default(n).prop("checked"))})),o.showColumnsSearch){var D=t.find('[name="columnsSearch"]'),V=t.find(".dropdown-item-marker");D.on("keyup paste change",(function(t){var e=t.currentTarget,n=i.default(e).val().toLowerCase();V.show(),_.each((function(t,e){var o=i.default(e).parents(".dropdown-item-marker");o.text().toLowerCase().includes(n)||o.hide()}))}))}}var B=function(t){var i="keyup drop blur mouseup";t.off(i).on(i,(function(t){o.searchOnEnterKey&&13!==t.keyCode||[37,38,39,40].includes(t.keyCode)||(clearTimeout(r),r=setTimeout((function(){e.onSearch({currentTarget:t.currentTarget})}),o.searchTimeOut))}))};if((o.search||this.showSearchClearButton)&&"string"!=typeof o.searchSelector){a=[];var L=Gg.sprintf(this.constants.html.searchButton,this.constants.buttonsClass,o.formatSearch(),o.showButtonIcons?Gg.sprintf(this.constants.html.icon,o.iconsPrefix,o.icons.search):"",o.showButtonText?o.formatSearch():""),H=Gg.sprintf(this.constants.html.searchClearButton,this.constants.buttonsClass,o.formatClearSearch(),o.showButtonIcons?Gg.sprintf(this.constants.html.icon,o.iconsPrefix,o.icons.clearSearch):"",o.showButtonText?o.formatClearSearch():""),M='<input class="'.concat(this.constants.classes.input,"\n        ").concat(Gg.sprintf(" %s%s",this.constants.classes.inputPrefix,o.iconSize),'\n        search-input" type="search" placeholder="').concat(o.formatSearch(),'" autocomplete="off">'),U=M;if(o.showSearchButton||o.showSearchClearButton){var z=(o.showSearchButton?L:"")+(o.showSearchClearButton?H:"");U=o.search?Gg.sprintf(this.constants.html.inputGroup,M,z):z}a.push(Gg.sprintf('\n        <div class="'.concat(this.constants.classes.pull,"-").concat(o.searchAlign," search ").concat(this.constants.classes.inputGroup,'">\n          %s\n        </div>\n      '),U)),this.$toolbar.append(a.join(""));var q=Gg.getSearchInput(this);o.showSearchButton?(this.$toolbar.find(".search button[name=search]").off("click").on("click",(function(){clearTimeout(r),r=setTimeout((function(){e.onSearch({currentTarget:q})}),o.searchTimeOut)})),o.searchOnEnterKey&&B(q)):B(q),o.showSearchClearButton&&this.$toolbar.find(".search button[name=clearSearch]").click((function(){e.resetSearch()}))}else if("string"==typeof o.searchSelector){B(Gg.getSearchInput(this))}}},{key:"onSearch",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},e=t.currentTarget,n=t.firedByInitSearchText,o=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];if(void 0!==e&&i.default(e).length&&o){var a=i.default(e).val().trim();if(this.options.trimOnSearch&&i.default(e).val()!==a&&i.default(e).val(a),this.searchText===a)return;var r=Gg.getSearchInput(this),s=e instanceof jQuery?e:i.default(e);(s.is(r)||s.hasClass("search-input"))&&(this.searchText=a,this.options.searchText=a)}n||this.options.cookie||(this.options.pageNumber=1),this.initSearch(),n?"client"===this.options.sidePagination&&this.updatePagination():this.updatePagination(),this.trigger("search",this.searchText)}},{key:"initSearch",value:function(){var t=this;if(this.filterOptions=this.filterOptions||this.options.filterOptions,"server"!==this.options.sidePagination){if(this.options.customSearch)return this.data=Gg.calculateObjectValue(this.options,this.options.customSearch,[this.options.data,this.searchText,this.filterColumns]),this.options.sortReset&&(this.unsortedData=l(this.data)),void this.initSort();var e=this.searchText&&(this.fromHtml?Gg.escapeHTML(this.searchText):this.searchText),i=e?e.toLowerCase():"",n=Gg.isEmptyObject(this.filterColumns)?null:this.filterColumns;this.options.searchAccentNeutralise&&(i=Gg.normalizeAccent(i)),"function"==typeof this.filterOptions.filterAlgorithm?this.data=this.options.data.filter((function(e){return t.filterOptions.filterAlgorithm.apply(null,[e,n])})):"string"==typeof this.filterOptions.filterAlgorithm&&(this.data=n?this.options.data.filter((function(e){var i=t.filterOptions.filterAlgorithm;if("and"===i){for(var o in n)if(Array.isArray(n[o])&&!n[o].includes(e[o])||!Array.isArray(n[o])&&e[o]!==n[o])return!1}else if("or"===i){var a=!1;for(var r in n)(Array.isArray(n[r])&&n[r].includes(e[r])||!Array.isArray(n[r])&&e[r]===n[r])&&(a=!0);return a}return!0})):l(this.options.data));var o=this.getVisibleFields();this.data=i?this.data.filter((function(n,a){for(var r=0;r<t.header.fields.length;r++)if(t.header.searchables[r]&&(!t.options.visibleSearch||-1!==o.indexOf(t.header.fields[r]))){var s=Gg.isNumeric(t.header.fields[r])?parseInt(t.header.fields[r],10):t.header.fields[r],l=t.columns[t.fieldsColumnsIndex[s]],c=void 0;if("string"==typeof s){c=n;for(var h=s.split("."),u=0;u<h.length;u++){if(null===c[h[u]]){c=null;break}c=c[h[u]]}}else c=n[s];if(t.options.searchAccentNeutralise&&(c=Gg.normalizeAccent(c)),l&&l.searchFormatter&&(c=Gg.calculateObjectValue(l,t.header.formatters[r],[c,n,a,l.field],c)),"string"==typeof c||"number"==typeof c){if(t.options.strictSearch&&"".concat(c).toLowerCase()===i||t.options.regexSearch&&Gg.regexCompare(c,e))return!0;var d=/(?:(<=|=>|=<|>=|>|<)(?:\s+)?(-?\d+)?|(-?\d+)?(\s+)?(<=|=>|=<|>=|>|<))/gm.exec(t.searchText),f=!1;if(d){var p=d[1]||"".concat(d[5],"l"),g=d[2]||d[3],v=parseInt(c,10),b=parseInt(g,10);switch(p){case">":case"<l":f=v>b;break;case"<":case">l":f=v<b;break;case"<=":case"=<":case">=l":case"=>l":f=v<=b;break;case">=":case"=>":case"<=l":case"=<l":f=v>=b}}if(f||"".concat(c).toLowerCase().includes(i))return!0}}return!1})):this.data,this.options.sortReset&&(this.unsortedData=l(this.data)),this.initSort()}}},{key:"initPagination",value:function(){var t=this,e=this.options;if(e.pagination){this.$pagination.show();var i,n,o,a,r,s,l,c=[],h=!1,u=this.getData({includeHiddenRows:!1}),d=e.pageList;if("string"==typeof d&&(d=d.replace(/\[|\]| /g,"").toLowerCase().split(",")),d=d.map((function(t){return"string"==typeof t?t.toLowerCase()===e.formatAllRows().toLowerCase()||["all","unlimited"].includes(t.toLowerCase())?e.formatAllRows():+t:t})),this.paginationParts=e.paginationParts,"string"==typeof this.paginationParts&&(this.paginationParts=this.paginationParts.replace(/\[|\]| |'/g,"").split(",")),"server"!==e.sidePagination&&(e.totalRows=u.length),this.totalPages=0,e.totalRows&&(e.pageSize===e.formatAllRows()&&(e.pageSize=e.totalRows,h=!0),this.totalPages=1+~~((e.totalRows-1)/e.pageSize),e.totalPages=this.totalPages),this.totalPages>0&&e.pageNumber>this.totalPages&&(e.pageNumber=this.totalPages),this.pageFrom=(e.pageNumber-1)*e.pageSize+1,this.pageTo=e.pageNumber*e.pageSize,this.pageTo>e.totalRows&&(this.pageTo=e.totalRows),this.options.pagination&&"server"!==this.options.sidePagination&&(this.options.totalNotFiltered=this.options.data.length),this.options.showExtendedPagination||(this.options.totalNotFiltered=void 0),(this.paginationParts.includes("pageInfo")||this.paginationParts.includes("pageInfoShort")||this.paginationParts.includes("pageSize"))&&c.push('<div class="'.concat(this.constants.classes.pull,"-").concat(e.paginationDetailHAlign,' pagination-detail">')),this.paginationParts.includes("pageInfo")||this.paginationParts.includes("pageInfoShort")){var f=this.paginationParts.includes("pageInfoShort")?e.formatDetailPagination(e.totalRows):e.formatShowingRows(this.pageFrom,this.pageTo,e.totalRows,e.totalNotFiltered);c.push('<span class="pagination-info">\n      '.concat(f,"\n      </span>"))}if(this.paginationParts.includes("pageSize")){c.push('<div class="page-list">');var p=['<div class="'.concat(this.constants.classes.paginationDropdown,'">\n        <button class="').concat(this.constants.buttonsClass,' dropdown-toggle" type="button" ').concat(this.constants.dataToggle,'="dropdown">\n        <span class="page-size">\n        ').concat(h?e.formatAllRows():e.pageSize,"\n        </span>\n        ").concat(this.constants.html.dropdownCaret,"\n        </button>\n        ").concat(this.constants.html.pageDropdown[0])];d.forEach((function(i,n){var o;(!e.smartDisplay||0===n||d[n-1]<e.totalRows||i===e.formatAllRows())&&(o=h?i===e.formatAllRows()?t.constants.classes.dropdownActive:"":i===e.pageSize?t.constants.classes.dropdownActive:"",p.push(Gg.sprintf(t.constants.html.pageDropdownItem,o,i)))})),p.push("".concat(this.constants.html.pageDropdown[1],"</div>")),c.push(e.formatRecordsPerPage(p.join("")))}if((this.paginationParts.includes("pageInfo")||this.paginationParts.includes("pageInfoShort")||this.paginationParts.includes("pageSize"))&&c.push("</div></div>"),this.paginationParts.includes("pageList")){c.push('<div class="'.concat(this.constants.classes.pull,"-").concat(e.paginationHAlign,' pagination">'),Gg.sprintf(this.constants.html.pagination[0],Gg.sprintf(" pagination-%s",e.iconSize)),Gg.sprintf(this.constants.html.paginationItem," page-pre",e.formatSRPaginationPreText(),e.paginationPreText)),this.totalPages<e.paginationSuccessivelySize?(n=1,o=this.totalPages):o=(n=e.pageNumber-e.paginationPagesBySide)+2*e.paginationPagesBySide,e.pageNumber<e.paginationSuccessivelySize-1&&(o=e.paginationSuccessivelySize),e.paginationSuccessivelySize>this.totalPages-n&&(n=n-(e.paginationSuccessivelySize-(this.totalPages-n))+1),n<1&&(n=1),o>this.totalPages&&(o=this.totalPages);var g=Math.round(e.paginationPagesBySide/2),v=function(i){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"";return Gg.sprintf(t.constants.html.paginationItem,n+(i===e.pageNumber?" ".concat(t.constants.classes.paginationActive):""),e.formatSRPaginationPageText(i),i)};if(n>1){var b=e.paginationPagesBySide;for(b>=n&&(b=n-1),i=1;i<=b;i++)c.push(v(i));n-1===b+1?(i=n-1,c.push(v(i))):n-1>b&&(n-2*e.paginationPagesBySide>e.paginationPagesBySide&&e.paginationUseIntermediate?(i=Math.round((n-g)/2+g),c.push(v(i," page-intermediate"))):c.push(Gg.sprintf(this.constants.html.paginationItem," page-first-separator disabled","","...")))}for(i=n;i<=o;i++)c.push(v(i));if(this.totalPages>o){var m=this.totalPages-(e.paginationPagesBySide-1);for(o>=m&&(m=o+1),o+1===m-1?(i=o+1,c.push(v(i))):m>o+1&&(this.totalPages-o>2*e.paginationPagesBySide&&e.paginationUseIntermediate?(i=Math.round((this.totalPages-g-o)/2+o),c.push(v(i," page-intermediate"))):c.push(Gg.sprintf(this.constants.html.paginationItem," page-last-separator disabled","","..."))),i=m;i<=this.totalPages;i++)c.push(v(i))}c.push(Gg.sprintf(this.constants.html.paginationItem," page-next",e.formatSRPaginationNextText(),e.paginationNextText)),c.push(this.constants.html.pagination[1],"</div>")}this.$pagination.html(c.join(""));var y=["bottom","both"].includes(e.paginationVAlign)?" ".concat(this.constants.classes.dropup):"";this.$pagination.last().find(".page-list > div").addClass(y),e.onlyInfoPagination||(a=this.$pagination.find(".page-list a"),r=this.$pagination.find(".page-pre"),s=this.$pagination.find(".page-next"),l=this.$pagination.find(".page-item").not(".page-next, .page-pre, .page-last-separator, .page-first-separator"),this.totalPages<=1&&this.$pagination.find("div.pagination").hide(),e.smartDisplay&&(d.length<2||e.totalRows<=d[0])&&this.$pagination.find("div.page-list").hide(),this.$pagination[this.getData().length?"show":"hide"](),e.paginationLoop||(1===e.pageNumber&&r.addClass("disabled"),e.pageNumber===this.totalPages&&s.addClass("disabled")),h&&(e.pageSize=e.formatAllRows()),a.off("click").on("click",(function(e){return t.onPageListChange(e)})),r.off("click").on("click",(function(e){return t.onPagePre(e)})),s.off("click").on("click",(function(e){return t.onPageNext(e)})),l.off("click").on("click",(function(e){return t.onPageNumber(e)})))}else this.$pagination.hide()}},{key:"updatePagination",value:function(t){t&&i.default(t.currentTarget).hasClass("disabled")||(this.options.maintainMetaData||this.resetRows(),this.initPagination(),this.trigger("page-change",this.options.pageNumber,this.options.pageSize),"server"===this.options.sidePagination?this.initServer():this.initBody())}},{key:"onPageListChange",value:function(t){t.preventDefault();var e=i.default(t.currentTarget);return e.parent().addClass(this.constants.classes.dropdownActive).siblings().removeClass(this.constants.classes.dropdownActive),this.options.pageSize=e.text().toUpperCase()===this.options.formatAllRows().toUpperCase()?this.options.formatAllRows():+e.text(),this.$toolbar.find(".page-size").text(this.options.pageSize),this.updatePagination(t),!1}},{key:"onPagePre",value:function(t){if(!i.default(t.target).hasClass("disabled"))return t.preventDefault(),this.options.pageNumber-1==0?this.options.pageNumber=this.options.totalPages:this.options.pageNumber--,this.updatePagination(t),!1}},{key:"onPageNext",value:function(t){if(!i.default(t.target).hasClass("disabled"))return t.preventDefault(),this.options.pageNumber+1>this.options.totalPages?this.options.pageNumber=1:this.options.pageNumber++,this.updatePagination(t),!1}},{key:"onPageNumber",value:function(t){if(t.preventDefault(),this.options.pageNumber!==+i.default(t.currentTarget).text())return this.options.pageNumber=+i.default(t.currentTarget).text(),this.updatePagination(t),!1}},{key:"initRow",value:function(t,e,i,o){var a=this,r=[],l={},c=[],h="",u={},d=[];if(!(Gg.findIndex(this.hiddenRows,t)>-1)){if((l=Gg.calculateObjectValue(this.options,this.options.rowStyle,[t,e],l))&&l.css)for(var f=0,p=Object.entries(l.css);f<p.length;f++){var g=s(p[f],2),v=g[0],b=g[1];c.push("".concat(v,": ").concat(b))}if(u=Gg.calculateObjectValue(this.options,this.options.rowAttributes,[t,e],u))for(var m=0,y=Object.entries(u);m<y.length;m++){var w=s(y[m],2),S=w[0],x=w[1];d.push("".concat(S,'="').concat(Gg.escapeHTML(x),'"'))}if(t._data&&!Gg.isEmptyObject(t._data))for(var k=0,O=Object.entries(t._data);k<O.length;k++){var C=s(O[k],2),T=C[0],I=C[1];if("index"===T)return;h+=" data-".concat(T,"='").concat("object"===n(I)?JSON.stringify(I):I,"'")}r.push("<tr",Gg.sprintf(" %s",d.length?d.join(" "):void 0),Gg.sprintf(' id="%s"',Array.isArray(t)?void 0:t._id),Gg.sprintf(' class="%s"',l.classes||(Array.isArray(t)?void 0:t._class)),Gg.sprintf(' style="%s"',Array.isArray(t)?void 0:t._style),' data-index="'.concat(e,'"'),Gg.sprintf(' data-uniqueid="%s"',Gg.getItemField(t,this.options.uniqueId,!1)),Gg.sprintf(' data-has-detail-view="%s"',this.options.detailView&&Gg.calculateObjectValue(null,this.options.detailFilter,[e,t])?"true":void 0),Gg.sprintf("%s",h),">"),this.options.cardView&&r.push('<td colspan="'.concat(this.header.fields.length,'"><div class="card-views">'));var P="";return Gg.hasDetailViewIcon(this.options)&&(P="<td>",Gg.calculateObjectValue(null,this.options.detailFilter,[e,t])&&(P+='\n          <a class="detail-icon" href="#">\n          '.concat(Gg.sprintf(this.constants.html.icon,this.options.iconsPrefix,this.options.icons.detailOpen),"\n          </a>\n        ")),P+="</td>"),P&&"right"!==this.options.detailViewAlign&&r.push(P),this.header.fields.forEach((function(i,n){var o=a.columns[n],l="",h=Gg.getItemField(t,i,a.options.escape,o.escape),u="",d="",f={},p="",g=a.header.classes[n],v="",b="",m="",y="",w="",S="";if((!a.fromHtml&&!a.autoMergeCells||void 0!==h||o.checkbox||o.radio)&&o.visible&&(!a.options.cardView||o.cardVisible)){if(c.concat([a.header.styles[n]]).length&&(b+="".concat(c.concat([a.header.styles[n]]).join("; "))),t["_".concat(i,"_style")]&&(b+="".concat(t["_".concat(i,"_style")])),b&&(v=' style="'.concat(b,'"')),t["_".concat(i,"_id")]&&(p=Gg.sprintf(' id="%s"',t["_".concat(i,"_id")])),t["_".concat(i,"_class")]&&(g=Gg.sprintf(' class="%s"',t["_".concat(i,"_class")])),t["_".concat(i,"_rowspan")]&&(y=Gg.sprintf(' rowspan="%s"',t["_".concat(i,"_rowspan")])),t["_".concat(i,"_colspan")]&&(w=Gg.sprintf(' colspan="%s"',t["_".concat(i,"_colspan")])),t["_".concat(i,"_title")]&&(S=Gg.sprintf(' title="%s"',t["_".concat(i,"_title")])),(f=Gg.calculateObjectValue(a.header,a.header.cellStyles[n],[h,t,e,i],f)).classes&&(g=' class="'.concat(f.classes,'"')),f.css){for(var x=[],k=0,O=Object.entries(f.css);k<O.length;k++){var C=s(O[k],2),T=C[0],I=C[1];x.push("".concat(T,": ").concat(I))}v=' style="'.concat(x.concat(a.header.styles[n]).join("; "),'"')}if(u=Gg.calculateObjectValue(o,a.header.formatters[n],[h,t,e,i],h),o.checkbox||o.radio||(u=null==u?a.options.undefinedText:u),o.searchable&&a.searchText&&a.options.searchHighlight&&!o.checkbox&&!o.radio){var P="",A=a.searchText.replace(/[.*+?^${}()|[\]\\]/g,"\\$&");if(a.options.searchAccentNeutralise){var $=new RegExp("".concat(Gg.normalizeAccent(A)),"gmi").exec(Gg.normalizeAccent(u));$&&(A=u.substring($.index,$.index+A.length))}var R=new RegExp("(".concat(A,")"),"gim"),E="<mark>$1</mark>";if(u&&/<(?=.*? .*?\/ ?>|br|hr|input|!--|wbr)[a-z]+.*?>|<([a-z]+).*?<\/\1>/i.test(u)){var j=(new DOMParser).parseFromString(u.toString(),"text/html").documentElement.textContent,F=j.replace(R,E);j=j.replace(/[.*+?^${}()|[\]\\]/g,"\\$&"),P=u.replace(new RegExp("(>\\s*)(".concat(j,")(\\s*)"),"gm"),"$1".concat(F,"$3"))}else P=u.toString().replace(R,E);u=Gg.calculateObjectValue(o,o.searchHighlightFormatter,[u,a.searchText],P)}if(t["_".concat(i,"_data")]&&!Gg.isEmptyObject(t["_".concat(i,"_data")]))for(var _=0,N=Object.entries(t["_".concat(i,"_data")]);_<N.length;_++){var D=s(N[_],2),V=D[0],B=D[1];if("index"===V)return;m+=" data-".concat(V,'="').concat(B,'"')}if(o.checkbox||o.radio){d=o.checkbox?"checkbox":d,d=o.radio?"radio":d;var L=o.class||"",H=Gg.isObject(u)&&u.hasOwnProperty("checked")?u.checked:(!0===u||h)&&!1!==u,M=!o.checkboxEnabled||u&&u.disabled;l=[a.options.cardView?'<div class="card-view '.concat(L,'">'):'<td class="bs-checkbox '.concat(L,'"').concat(g).concat(v,">"),'<label>\n            <input\n            data-index="'.concat(e,'"\n            name="').concat(a.options.selectItemName,'"\n            type="').concat(d,'"\n            ').concat(Gg.sprintf('value="%s"',t[a.options.idField]),"\n            ").concat(Gg.sprintf('checked="%s"',H?"checked":void 0),"\n            ").concat(Gg.sprintf('disabled="%s"',M?"disabled":void 0)," />\n            <span></span>\n            </label>"),a.header.formatters[n]&&"string"==typeof u?u:"",a.options.cardView?"</div>":"</td>"].join(""),t[a.header.stateField]=!0===u||!!h||u&&u.checked}else if(a.options.cardView){var U=a.options.showHeader?'<span class="card-view-title '.concat(f.classes||"",'"').concat(v,">").concat(Gg.getFieldTitle(a.columns,i),"</span>"):"";l='<div class="card-view">'.concat(U,'<span class="card-view-value ').concat(f.classes||"",'"').concat(v,">").concat(u,"</span></div>"),a.options.smartDisplay&&""===u&&(l='<div class="card-view"></div>')}else l="<td".concat(p).concat(g).concat(v).concat(m).concat(y).concat(w).concat(S,">").concat(u,"</td>");r.push(l)}})),P&&"right"===this.options.detailViewAlign&&r.push(P),this.options.cardView&&r.push("</div></td>"),r.push("</tr>"),r.join("")}}},{key:"initBody",value:function(t,e){var n=this,o=this.getData();this.trigger("pre-body",o),this.$body=this.$el.find(">tbody"),this.$body.length||(this.$body=i.default("<tbody></tbody>").appendTo(this.$el)),this.options.pagination&&"server"!==this.options.sidePagination||(this.pageFrom=1,this.pageTo=o.length);var a=[],r=i.default(document.createDocumentFragment()),s=!1,l=[];this.autoMergeCells=Gg.checkAutoMergeCells(o.slice(this.pageFrom-1,this.pageTo));for(var c=this.pageFrom-1;c<this.pageTo;c++){var h=o[c],u=this.initRow(h,c,o,r);if(s=s||!!u,u&&"string"==typeof u){var d=this.options.uniqueId;if(d&&h.hasOwnProperty(d)){var f=h[d],p=this.$body.find(Gg.sprintf('> tr[data-uniqueid="%s"][data-has-detail-view]',f)).next();p.is("tr.detail-view")&&(l.push(c),e&&f===e||(u+=p[0].outerHTML))}this.options.virtualScroll?a.push(u):r.append(u)}}s?this.options.virtualScroll?(this.virtualScroll&&this.virtualScroll.destroy(),this.virtualScroll=new tv({rows:a,fixedScroll:t,scrollEl:this.$tableBody[0],contentEl:this.$body[0],itemHeight:this.options.virtualScrollItemHeight,callback:function(t,e){n.fitHeader(),n.initBodyEvent(),n.trigger("virtual-scroll",t,e)}})):this.$body.html(r):this.$body.html('<tr class="no-records-found">'.concat(Gg.sprintf('<td colspan="%s">%s</td>',this.getVisibleFields().length+Gg.getDetailViewIndexOffset(this.options),this.options.formatNoMatches()),"</tr>")),l.forEach((function(t){n.expandRow(t)})),t||this.scrollTo(0),this.initBodyEvent(),this.initFooter(),this.resetView(),this.updateSelected(),"server"!==this.options.sidePagination&&(this.options.totalRows=o.length),this.trigger("post-body",o)}},{key:"initBodyEvent",value:function(){var t=this;this.$body.find("> tr[data-index] > td").off("click dblclick").on("click dblclick",(function(e){var n=i.default(e.currentTarget);if(!(n.find(".detail-icon").length||n.index()-Gg.getDetailViewIndexOffset(t.options)<0)){var o=n.parent(),a=i.default(e.target).parents(".card-views").children(),r=i.default(e.target).parents(".card-view"),s=o.data("index"),l=t.data[s],c=t.options.cardView?a.index(r):n[0].cellIndex,h=t.getVisibleFields()[c-Gg.getDetailViewIndexOffset(t.options)],u=t.columns[t.fieldsColumnsIndex[h]],d=Gg.getItemField(l,h,t.options.escape,u.escape);if(t.trigger("click"===e.type?"click-cell":"dbl-click-cell",h,d,l,n),t.trigger("click"===e.type?"click-row":"dbl-click-row",l,o,h),"click"===e.type&&t.options.clickToSelect&&u.clickToSelect&&!Gg.calculateObjectValue(t.options,t.options.ignoreClickToSelectOn,[e.target])){var f=o.find(Gg.sprintf('[name="%s"]',t.options.selectItemName));f.length&&f[0].click()}"click"===e.type&&t.options.detailViewByClick&&t.toggleDetailView(s,t.header.detailFormatters[t.fieldsColumnsIndex[h]])}})).off("mousedown").on("mousedown",(function(e){t.multipleSelectRowCtrlKey=e.ctrlKey||e.metaKey,t.multipleSelectRowShiftKey=e.shiftKey})),this.$body.find("> tr[data-index] > td > .detail-icon").off("click").on("click",(function(e){return e.preventDefault(),t.toggleDetailView(i.default(e.currentTarget).parent().parent().data("index")),!1})),this.$selectItem=this.$body.find(Gg.sprintf('[name="%s"]',this.options.selectItemName)),this.$selectItem.off("click").on("click",(function(e){e.stopImmediatePropagation();var n=i.default(e.currentTarget);t._toggleCheck(n.prop("checked"),n.data("index"))})),this.header.events.forEach((function(e,n){var o=e;if(o){if("string"==typeof o&&(o=Gg.calculateObjectValue(null,o)),!o)throw new Error("Unknown event in the scope: ".concat(e));var a=t.header.fields[n],r=t.getVisibleFields().indexOf(a);if(-1!==r){r+=Gg.getDetailViewIndexOffset(t.options);var s=function(e){if(!o.hasOwnProperty(e))return"continue";var n=o[e];t.$body.find(">tr:not(.no-records-found)").each((function(o,s){var l=i.default(s),c=l.find(t.options.cardView?".card-views>.card-view":">td").eq(r),h=e.indexOf(" "),u=e.substring(0,h),d=e.substring(h+1);c.find(d).off(u).on(u,(function(e){var i=l.data("index"),o=t.data[i],r=o[a];n.apply(t,[e,r,o,i])}))}))};for(var l in o)s(l)}}}))}},{key:"initServer",value:function(t,e,n){var o=this,a={},r=this.header.fields.indexOf(this.options.sortName),s={searchText:this.searchText,sortName:this.options.sortName,sortOrder:this.options.sortOrder};if(this.header.sortNames[r]&&(s.sortName=this.header.sortNames[r]),this.options.pagination&&"server"===this.options.sidePagination&&(s.pageSize=this.options.pageSize===this.options.formatAllRows()?this.options.totalRows:this.options.pageSize,s.pageNumber=this.options.pageNumber),n||this.options.url||this.options.ajax){if("limit"===this.options.queryParamsType&&(s={search:s.searchText,sort:s.sortName,order:s.sortOrder},this.options.pagination&&"server"===this.options.sidePagination&&(s.offset=this.options.pageSize===this.options.formatAllRows()?0:this.options.pageSize*(this.options.pageNumber-1),s.limit=this.options.pageSize,0!==s.limit&&this.options.pageSize!==this.options.formatAllRows()||delete s.limit)),this.options.search&&"server"===this.options.sidePagination&&this.columns.filter((function(t){return!t.searchable})).length){s.searchable=[];var l,c=u(this.columns);try{for(c.s();!(l=c.n()).done;){var h=l.value;!h.checkbox&&h.searchable&&(this.options.visibleSearch&&h.visible||!this.options.visibleSearch)&&s.searchable.push(h.field)}}catch(t){c.e(t)}finally{c.f()}}if(Gg.isEmptyObject(this.filterColumnsPartial)||(s.filter=JSON.stringify(this.filterColumnsPartial,null)),i.default.extend(s,e||{}),!1!==(a=Gg.calculateObjectValue(this.options,this.options.queryParams,[s],a))){t||this.showLoading();var d=i.default.extend({},Gg.calculateObjectValue(null,this.options.ajaxOptions),{type:this.options.method,url:n||this.options.url,data:"application/json"===this.options.contentType&&"post"===this.options.method?JSON.stringify(a):a,cache:this.options.cache,contentType:this.options.contentType,dataType:this.options.dataType,success:function(e,i,n){var a=Gg.calculateObjectValue(o.options,o.options.responseHandler,[e,n],e);o.load(a),o.trigger("load-success",a,n&&n.status,n),t||o.hideLoading(),"server"===o.options.sidePagination&&o.options.pageNumber>1&&a[o.options.totalField]>0&&!a[o.options.dataField].length&&o.updatePagination()},error:function(e){if(e&&0===e.status&&o._xhrAbort)o._xhrAbort=!1;else{var i=[];"server"===o.options.sidePagination&&((i={})[o.options.totalField]=0,i[o.options.dataField]=[]),o.load(i),o.trigger("load-error",e&&e.status,e),t||o.hideLoading()}}});return this.options.ajax?Gg.calculateObjectValue(this,this.options.ajax,[d],null):(this._xhr&&4!==this._xhr.readyState&&(this._xhrAbort=!0,this._xhr.abort()),this._xhr=i.default.ajax(d)),a}}}},{key:"initSearchText",value:function(){if(this.options.search&&(this.searchText="",""!==this.options.searchText)){var t=Gg.getSearchInput(this);t.val(this.options.searchText),this.onSearch({currentTarget:t,firedByInitSearchText:!0})}}},{key:"getCaret",value:function(){var t=this;this.$header.find("th").each((function(e,n){i.default(n).find(".sortable").removeClass("desc asc").addClass(i.default(n).data("field")===t.options.sortName?t.options.sortOrder:"both")}))}},{key:"updateSelected",value:function(){var t=this.$selectItem.filter(":enabled").length&&this.$selectItem.filter(":enabled").length===this.$selectItem.filter(":enabled").filter(":checked").length;this.$selectAll.add(this.$selectAll_).prop("checked",t),this.$selectItem.each((function(t,e){i.default(e).closest("tr")[i.default(e).prop("checked")?"addClass":"removeClass"]("selected")}))}},{key:"updateRows",value:function(){var t=this;this.$selectItem.each((function(e,n){t.data[i.default(n).data("index")][t.header.stateField]=i.default(n).prop("checked")}))}},{key:"resetRows",value:function(){var t,e=u(this.data);try{for(e.s();!(t=e.n()).done;){var i=t.value;this.$selectAll.prop("checked",!1),this.$selectItem.prop("checked",!1),this.header.stateField&&(i[this.header.stateField]=!1)}}catch(t){e.e(t)}finally{e.f()}this.initHiddenRows()}},{key:"trigger",value:function(e){for(var n,o,a="".concat(e,".bs.table"),r=arguments.length,s=new Array(r>1?r-1:0),l=1;l<r;l++)s[l-1]=arguments[l];(n=this.options)[t.EVENTS[a]].apply(n,[].concat(s,[this])),this.$el.trigger(i.default.Event(a,{sender:this}),s),(o=this.options).onAll.apply(o,[a].concat([].concat(s,[this]))),this.$el.trigger(i.default.Event("all.bs.table",{sender:this}),[a,s])}},{key:"resetHeader",value:function(){var t=this;clearTimeout(this.timeoutId_),this.timeoutId_=setTimeout((function(){return t.fitHeader()}),this.$el.is(":hidden")?100:0)}},{key:"fitHeader",value:function(){var t=this;if(this.$el.is(":hidden"))this.timeoutId_=setTimeout((function(){return t.fitHeader()}),100);else{var e=this.$tableBody.get(0),n=this.hasScrollBar&&e.scrollHeight>e.clientHeight+this.$header.outerHeight()?Gg.getScrollBarWidth():0;this.$el.css("margin-top",-this.$header.outerHeight());var o=i.default(":focus");if(o.length>0){var a=o.parents("th");if(a.length>0){var r=a.attr("data-field");if(void 0!==r){var s=this.$header.find("[data-field='".concat(r,"']"));s.length>0&&s.find(":input").addClass("focus-temp")}}}this.$header_=this.$header.clone(!0,!0),this.$selectAll_=this.$header_.find('[name="btSelectAll"]'),this.$tableHeader.css("margin-right",n).find("table").css("width",this.$el.outerWidth()).html("").attr("class",this.$el.attr("class")).append(this.$header_),this.$tableLoading.css("width",this.$el.outerWidth());var l=i.default(".focus-temp:visible:eq(0)");l.length>0&&(l.focus(),this.$header.find(".focus-temp").removeClass("focus-temp")),this.$header.find("th[data-field]").each((function(e,n){t.$header_.find(Gg.sprintf('th[data-field="%s"]',i.default(n).data("field"))).data(i.default(n).data())}));for(var c=this.getVisibleFields(),h=this.$header_.find("th"),u=this.$body.find(">tr:not(.no-records-found,.virtual-scroll-top)").eq(0);u.length&&u.find('>td[colspan]:not([colspan="1"])').length;)u=u.next();var d=u.find("> *").length;u.find("> *").each((function(e,n){var o=i.default(n);if(Gg.hasDetailViewIcon(t.options)&&(0===e&&"right"!==t.options.detailViewAlign||e===d-1&&"right"===t.options.detailViewAlign)){var a=h.filter(".detail"),r=a.innerWidth()-a.find(".fht-cell").width();a.find(".fht-cell").width(o.innerWidth()-r)}else{var s=e-Gg.getDetailViewIndexOffset(t.options),l=t.$header_.find(Gg.sprintf('th[data-field="%s"]',c[s]));l.length>1&&(l=i.default(h[o[0].cellIndex]));var u=l.innerWidth()-l.find(".fht-cell").width();l.find(".fht-cell").width(o.innerWidth()-u)}})),this.horizontalScroll(),this.trigger("post-header")}}},{key:"initFooter",value:function(){if(this.options.showFooter&&!this.options.cardView){var t=this.getData(),e=[],i="";Gg.hasDetailViewIcon(this.options)&&(i='<th class="detail"><div class="th-inner"></div><div class="fht-cell"></div></th>'),i&&"right"!==this.options.detailViewAlign&&e.push(i);var n,o=u(this.columns);try{for(o.s();!(n=o.n()).done;){var a,r,l=n.value,c=[],h={},d=Gg.sprintf(' class="%s"',l.class);if(!(!l.visible||this.footerData&&this.footerData.length>0&&!(l.field in this.footerData[0]))){if(this.options.cardView&&!l.cardVisible)return;if(a=Gg.sprintf("text-align: %s; ",l.falign?l.falign:l.align),r=Gg.sprintf("vertical-align: %s; ",l.valign),(h=Gg.calculateObjectValue(null,this.options.footerStyle,[l]))&&h.css)for(var f=0,p=Object.entries(h.css);f<p.length;f++){var g=s(p[f],2),v=g[0],b=g[1];c.push("".concat(v,": ").concat(b))}h&&h.classes&&(d=Gg.sprintf(' class="%s"',l.class?[l.class,h.classes].join(" "):h.classes)),e.push("<th",d,Gg.sprintf(' style="%s"',a+r+c.concat().join("; ")));var m=0;this.footerData&&this.footerData.length>0&&(m=this.footerData[0]["_".concat(l.field,"_colspan")]||0),m&&e.push(' colspan="'.concat(m,'" ')),e.push(">"),e.push('<div class="th-inner">');var y="";this.footerData&&this.footerData.length>0&&(y=this.footerData[0][l.field]||""),e.push(Gg.calculateObjectValue(l,l.footerFormatter,[t,y],y)),e.push("</div>"),e.push('<div class="fht-cell"></div>'),e.push("</div>"),e.push("</th>")}}}catch(t){o.e(t)}finally{o.f()}i&&"right"===this.options.detailViewAlign&&e.push(i),this.options.height||this.$tableFooter.length||(this.$el.append("<tfoot><tr></tr></tfoot>"),this.$tableFooter=this.$el.find("tfoot")),this.$tableFooter.find("tr").length||this.$tableFooter.html("<table><thead><tr></tr></thead></table>"),this.$tableFooter.find("tr").html(e.join("")),this.trigger("post-footer",this.$tableFooter)}}},{key:"fitFooter",value:function(){var t=this;if(this.$el.is(":hidden"))setTimeout((function(){return t.fitFooter()}),100);else{var e=this.$tableBody.get(0),n=this.hasScrollBar&&e.scrollHeight>e.clientHeight+this.$header.outerHeight()?Gg.getScrollBarWidth():0;this.$tableFooter.css("margin-right",n).find("table").css("width",this.$el.outerWidth()).attr("class",this.$el.attr("class"));var o=this.$tableFooter.find("th"),a=this.$body.find(">tr:first-child:not(.no-records-found)");for(o.find(".fht-cell").width("auto");a.length&&a.find('>td[colspan]:not([colspan="1"])').length;)a=a.next();var r=a.find("> *").length;a.find("> *").each((function(e,n){var a=i.default(n);if(Gg.hasDetailViewIcon(t.options)&&(0===e&&"left"===t.options.detailViewAlign||e===r-1&&"right"===t.options.detailViewAlign)){var s=o.filter(".detail"),l=s.innerWidth()-s.find(".fht-cell").width();s.find(".fht-cell").width(a.innerWidth()-l)}else{var c=o.eq(e),h=c.innerWidth()-c.find(".fht-cell").width();c.find(".fht-cell").width(a.innerWidth()-h)}})),this.horizontalScroll()}}},{key:"horizontalScroll",value:function(){var t=this;this.$tableBody.off("scroll").on("scroll",(function(){var e=t.$tableBody.scrollLeft();t.options.showHeader&&t.options.height&&t.$tableHeader.scrollLeft(e),t.options.showFooter&&!t.options.cardView&&t.$tableFooter.scrollLeft(e),t.trigger("scroll-body",t.$tableBody)}))}},{key:"getVisibleFields",value:function(){var t,e=[],i=u(this.header.fields);try{for(i.s();!(t=i.n()).done;){var n=t.value,o=this.columns[this.fieldsColumnsIndex[n]];o&&o.visible&&(!this.options.cardView||o.cardVisible)&&e.push(n)}}catch(t){i.e(t)}finally{i.f()}return e}},{key:"initHiddenRows",value:function(){this.hiddenRows=[]}},{key:"getOptions",value:function(){var t=i.default.extend({},this.options);return delete t.data,i.default.extend(!0,{},t)}},{key:"refreshOptions",value:function(t){Gg.compareObjects(this.options,t,!0)||(this.options=i.default.extend(this.options,t),this.trigger("refresh-options",this.options),this.destroy(),this.init())}},{key:"getData",value:function(t){var e=this,i=this.options.data;if(!(this.searchText||this.options.customSearch||void 0!==this.options.sortName||this.enableCustomSort)&&Gg.isEmptyObject(this.filterColumns)&&"function"!=typeof this.options.filterOptions.filterAlgorithm&&Gg.isEmptyObject(this.filterColumnsPartial)||t&&t.unfiltered||(i=this.data),t&&t.useCurrentPage&&(i=i.slice(this.pageFrom-1,this.pageTo)),t&&!t.includeHiddenRows){var n=this.getHiddenRows();i=i.filter((function(t){return-1===Gg.findIndex(n,t)}))}return t&&t.formatted&&i.forEach((function(t){for(var i=0,n=Object.entries(t);i<n.length;i++){var o=s(n[i],2),a=o[0],r=o[1],l=e.columns[e.fieldsColumnsIndex[a]];if(!l)return;t[a]=Gg.calculateObjectValue(l,e.header.formatters[l.fieldIndex],[r,t,t.index,l.field],r)}})),i}},{key:"getSelections",value:function(){var t=this;return(this.options.maintainMetaData?this.options.data:this.data).filter((function(e){return!0===e[t.header.stateField]}))}},{key:"load",value:function(t){var e,i=t;this.options.pagination&&"server"===this.options.sidePagination&&(this.options.totalRows=i[this.options.totalField],this.options.totalNotFiltered=i[this.options.totalNotFilteredField],this.footerData=i[this.options.footerField]?[i[this.options.footerField]]:void 0),e=i.fixedScroll,i=Array.isArray(i)?i:i[this.options.dataField],this.initData(i),this.initSearch(),this.initPagination(),this.initBody(e)}},{key:"append",value:function(t){this.initData(t,"append"),this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0)}},{key:"prepend",value:function(t){this.initData(t,"prepend"),this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0)}},{key:"remove",value:function(t){for(var e=0,i=this.options.data.length-1;i>=0;i--){var n=this.options.data[i],o=Gg.getItemField(n,t.field,this.options.escape,n.escape);void 0===o&&"$index"!==t.field||(!n.hasOwnProperty(t.field)&&"$index"===t.field&&t.values.includes(i)||t.values.includes(o))&&(e++,this.options.data.splice(i,1))}e&&("server"===this.options.sidePagination&&(this.options.totalRows-=e,this.data=l(this.options.data)),this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0))}},{key:"removeAll",value:function(){this.options.data.length>0&&(this.options.data.splice(0,this.options.data.length),this.initSearch(),this.initPagination(),this.initBody(!0))}},{key:"insertRow",value:function(t){t.hasOwnProperty("index")&&t.hasOwnProperty("row")&&(this.options.data.splice(t.index,0,t.row),this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0))}},{key:"updateRow",value:function(t){var e,n=u(Array.isArray(t)?t:[t]);try{for(n.s();!(e=n.n()).done;){var o=e.value;o.hasOwnProperty("index")&&o.hasOwnProperty("row")&&(o.hasOwnProperty("replace")&&o.replace?this.options.data[o.index]=o.row:i.default.extend(this.options.data[o.index],o.row))}}catch(t){n.e(t)}finally{n.f()}this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0)}},{key:"getRowByUniqueId",value:function(t){var e,i,n=this.options.uniqueId,o=t,a=null;for(e=this.options.data.length-1;e>=0;e--){i=this.options.data[e];var r=Gg.getItemField(i,n,this.options.escape,i.escape);if(void 0!==r&&("string"==typeof r?o=o.toString():"number"==typeof r&&(Number(r)===r&&r%1==0?o=parseInt(o,10):r===Number(r)&&0!==r&&(o=parseFloat(o))),r===o)){a=i;break}}return a}},{key:"updateByUniqueId",value:function(t){var e,n=null,o=u(Array.isArray(t)?t:[t]);try{for(o.s();!(e=o.n()).done;){var a=e.value;if(a.hasOwnProperty("id")&&a.hasOwnProperty("row")){var r=this.options.data.indexOf(this.getRowByUniqueId(a.id));-1!==r&&(a.hasOwnProperty("replace")&&a.replace?this.options.data[r]=a.row:i.default.extend(this.options.data[r],a.row),n=a.id)}}}catch(t){o.e(t)}finally{o.f()}this.initSearch(),this.initPagination(),this.initSort(),this.initBody(!0,n)}},{key:"removeByUniqueId",value:function(t){var e=this.options.data.length,i=this.getRowByUniqueId(t);i&&this.options.data.splice(this.options.data.indexOf(i),1),e!==this.options.data.length&&("server"===this.options.sidePagination&&(this.options.totalRows-=1,this.data=l(this.options.data)),this.initSearch(),this.initPagination(),this.initBody(!0))}},{key:"_updateCellOnly",value:function(t,e){var n=this.initRow(this.options.data[e],e),o=this.getVisibleFields().indexOf(t);-1!==o&&(o+=Gg.getDetailViewIndexOffset(this.options),this.$body.find(">tr[data-index=".concat(e,"]")).find(">td:eq(".concat(o,")")).replaceWith(i.default(n).find(">td:eq(".concat(o,")"))),this.initBodyEvent(),this.initFooter(),this.resetView(),this.updateSelected())}},{key:"updateCell",value:function(t){t.hasOwnProperty("index")&&t.hasOwnProperty("field")&&t.hasOwnProperty("value")&&(this.options.data[t.index][t.field]=t.value,!1!==t.reinit?(this.initSort(),this.initBody(!0)):this._updateCellOnly(t.field,t.index))}},{key:"updateCellByUniqueId",value:function(t){var e=this;(Array.isArray(t)?t:[t]).forEach((function(t){var i=t.id,n=t.field,o=t.value,a=e.options.data.indexOf(e.getRowByUniqueId(i));-1!==a&&(e.options.data[a][n]=o)})),!1!==t.reinit?(this.initSort(),this.initBody(!0)):this._updateCellOnly(t.field,this.options.data.indexOf(this.getRowByUniqueId(t.id)))}},{key:"showRow",value:function(t){this._toggleRow(t,!0)}},{key:"hideRow",value:function(t){this._toggleRow(t,!1)}},{key:"_toggleRow",value:function(t,e){var i;if(t.hasOwnProperty("index")?i=this.getData()[t.index]:t.hasOwnProperty("uniqueId")&&(i=this.getRowByUniqueId(t.uniqueId)),i){var n=Gg.findIndex(this.hiddenRows,i);e||-1!==n?e&&n>-1&&this.hiddenRows.splice(n,1):this.hiddenRows.push(i),this.initBody(!0),this.initPagination()}}},{key:"getHiddenRows",value:function(t){if(t)return this.initHiddenRows(),this.initBody(!0),void this.initPagination();var e,i=[],n=u(this.getData());try{for(n.s();!(e=n.n()).done;){var o=e.value;this.hiddenRows.includes(o)&&i.push(o)}}catch(t){n.e(t)}finally{n.f()}return this.hiddenRows=i,i}},{key:"showColumn",value:function(t){var e=this;(Array.isArray(t)?t:[t]).forEach((function(t){e._toggleColumn(e.fieldsColumnsIndex[t],!0,!0)}))}},{key:"hideColumn",value:function(t){var e=this;(Array.isArray(t)?t:[t]).forEach((function(t){e._toggleColumn(e.fieldsColumnsIndex[t],!1,!0)}))}},{key:"_toggleColumn",value:function(t,e,i){if(-1!==t&&this.columns[t].visible!==e&&(this.columns[t].visible=e,this.initHeader(),this.initSearch(),this.initPagination(),this.initBody(),this.options.showColumns)){var n=this.$toolbar.find('.keep-open input:not(".toggle-all")').prop("disabled",!1);i&&n.filter(Gg.sprintf('[value="%s"]',t)).prop("checked",e),n.filter(":checked").length<=this.options.minimumCountColumns&&n.filter(":checked").prop("disabled",!0)}}},{key:"getVisibleColumns",value:function(){var t=this;return this.columns.filter((function(e){return e.visible&&!t.isSelectionColumn(e)}))}},{key:"getHiddenColumns",value:function(){return this.columns.filter((function(t){return!t.visible}))}},{key:"isSelectionColumn",value:function(t){return t.radio||t.checkbox}},{key:"showAllColumns",value:function(){this._toggleAllColumns(!0)}},{key:"hideAllColumns",value:function(){this._toggleAllColumns(!1)}},{key:"_toggleAllColumns",value:function(t){var e,n=this,o=u(this.columns.slice().reverse());try{for(o.s();!(e=o.n()).done;){var a=e.value;if(a.switchable){if(!t&&this.options.showColumns&&this.getVisibleColumns().filter((function(t){return t.switchable})).length===this.options.minimumCountColumns)continue;a.visible=t}}}catch(t){o.e(t)}finally{o.f()}if(this.initHeader(),this.initSearch(),this.initPagination(),this.initBody(),this.options.showColumns){var r=this.$toolbar.find('.keep-open input[type="checkbox"]:not(".toggle-all")').prop("disabled",!1);t?r.prop("checked",t):r.get().reverse().forEach((function(e){r.filter(":checked").length>n.options.minimumCountColumns&&i.default(e).prop("checked",t)})),r.filter(":checked").length<=this.options.minimumCountColumns&&r.filter(":checked").prop("disabled",!0)}}},{key:"mergeCells",value:function(t){var e,i,n=t.index,o=this.getVisibleFields().indexOf(t.field),a=t.rowspan||1,r=t.colspan||1,s=this.$body.find(">tr[data-index]");o+=Gg.getDetailViewIndexOffset(this.options);var l=s.eq(n).find(">td").eq(o);if(!(n<0||o<0||n>=this.data.length)){for(e=n;e<n+a;e++)for(i=o;i<o+r;i++)s.eq(e).find(">td").eq(i).hide();l.attr("rowspan",a).attr("colspan",r).show()}}},{key:"checkAll",value:function(){this._toggleCheckAll(!0)}},{key:"uncheckAll",value:function(){this._toggleCheckAll(!1)}},{key:"_toggleCheckAll",value:function(t){var e=this.getSelections();this.$selectAll.add(this.$selectAll_).prop("checked",t),this.$selectItem.filter(":enabled").prop("checked",t),this.updateRows(),this.updateSelected();var i=this.getSelections();t?this.trigger("check-all",i,e):this.trigger("uncheck-all",i,e)}},{key:"checkInvert",value:function(){var t=this.$selectItem.filter(":enabled"),e=t.filter(":checked");t.each((function(t,e){i.default(e).prop("checked",!i.default(e).prop("checked"))})),this.updateRows(),this.updateSelected(),this.trigger("uncheck-some",e),e=this.getSelections(),this.trigger("check-some",e)}},{key:"check",value:function(t){this._toggleCheck(!0,t)}},{key:"uncheck",value:function(t){this._toggleCheck(!1,t)}},{key:"_toggleCheck",value:function(t,e){var i=this.$selectItem.filter('[data-index="'.concat(e,'"]')),n=this.data[e];if(i.is(":radio")||this.options.singleSelect||this.options.multipleSelectRow&&!this.multipleSelectRowCtrlKey&&!this.multipleSelectRowShiftKey){var o,a=u(this.options.data);try{for(a.s();!(o=a.n()).done;){o.value[this.header.stateField]=!1}}catch(t){a.e(t)}finally{a.f()}this.$selectItem.filter(":checked").not(i).prop("checked",!1)}if(n[this.header.stateField]=t,this.options.multipleSelectRow){if(this.multipleSelectRowShiftKey&&this.multipleSelectRowLastSelectedIndex>=0)for(var r=s(this.multipleSelectRowLastSelectedIndex<e?[this.multipleSelectRowLastSelectedIndex,e]:[e,this.multipleSelectRowLastSelectedIndex],2),l=r[0],c=r[1],h=l+1;h<c;h++)this.data[h][this.header.stateField]=!0,this.$selectItem.filter('[data-index="'.concat(h,'"]')).prop("checked",!0);this.multipleSelectRowCtrlKey=!1,this.multipleSelectRowShiftKey=!1,this.multipleSelectRowLastSelectedIndex=t?e:-1}i.prop("checked",t),this.updateSelected(),this.trigger(t?"check":"uncheck",this.data[e],i)}},{key:"checkBy",value:function(t){this._toggleCheckBy(!0,t)}},{key:"uncheckBy",value:function(t){this._toggleCheckBy(!1,t)}},{key:"_toggleCheckBy",value:function(t,e){var i=this;if(e.hasOwnProperty("field")&&e.hasOwnProperty("values")){var n=[];this.data.forEach((function(o,a){if(!o.hasOwnProperty(e.field))return!1;if(e.values.includes(o[e.field])){var r=i.$selectItem.filter(":enabled").filter(Gg.sprintf('[data-index="%s"]',a)),s=!!e.hasOwnProperty("onlyCurrentPage")&&e.onlyCurrentPage;if(!(r=t?r.not(":checked"):r.filter(":checked")).length&&s)return;r.prop("checked",t),o[i.header.stateField]=t,n.push(o),i.trigger(t?"check":"uncheck",o,r)}})),this.updateSelected(),this.trigger(t?"check-some":"uncheck-some",n)}}},{key:"refresh",value:function(t){t&&t.url&&(this.options.url=t.url),t&&t.pageNumber&&(this.options.pageNumber=t.pageNumber),t&&t.pageSize&&(this.options.pageSize=t.pageSize),this.trigger("refresh",this.initServer(t&&t.silent,t&&t.query,t&&t.url))}},{key:"destroy",value:function(){this.$el.insertBefore(this.$container),i.default(this.options.toolbar).insertBefore(this.$el),this.$container.next().remove(),this.$container.remove(),this.$el.html(this.$el_.html()).css("margin-top","0").attr("class",this.$el_.attr("class")||"");var t=Gg.getEventName("resize.bootstrap-table",this.$el.attr("id"));i.default(window).off(t)}},{key:"resetView",value:function(t){var e=0;if(t&&t.height&&(this.options.height=t.height),this.$tableContainer.toggleClass("has-card-view",this.options.cardView),this.options.height){var i=this.$tableBody.get(0);this.hasScrollBar=i.scrollWidth>i.clientWidth}if(!this.options.cardView&&this.options.showHeader&&this.options.height?(this.$tableHeader.show(),this.resetHeader(),e+=this.$header.outerHeight(!0)+1):(this.$tableHeader.hide(),this.trigger("post-header")),!this.options.cardView&&this.options.showFooter&&(this.$tableFooter.show(),this.fitFooter(),this.options.height&&(e+=this.$tableFooter.outerHeight(!0))),this.$container.hasClass("fullscreen"))this.$tableContainer.css("height",""),this.$tableContainer.css("width","");else if(this.options.height){this.$tableBorder&&(this.$tableBorder.css("width",""),this.$tableBorder.css("height",""));var n=this.$toolbar.outerHeight(!0),o=this.$pagination.outerHeight(!0),a=this.options.height-n-o,r=this.$tableBody.find(">table"),s=r.outerHeight();if(this.$tableContainer.css("height","".concat(a,"px")),this.$tableBorder&&r.is(":visible")){var l=a-s-2;this.hasScrollBar&&(l-=Gg.getScrollBarWidth()),this.$tableBorder.css("width","".concat(r.outerWidth(),"px")),this.$tableBorder.css("height","".concat(l,"px"))}}this.options.cardView?(this.$el.css("margin-top","0"),this.$tableContainer.css("padding-bottom","0"),this.$tableFooter.hide()):(this.getCaret(),this.$tableContainer.css("padding-bottom","".concat(e,"px"))),this.trigger("reset-view")}},{key:"showLoading",value:function(){this.$tableLoading.toggleClass("open",!0);var t=this.options.loadingFontSize;"auto"===this.options.loadingFontSize&&(t=.04*this.$tableLoading.width(),t=Math.max(12,t),t=Math.min(32,t),t="".concat(t,"px")),this.$tableLoading.find(".loading-text").css("font-size",t)}},{key:"hideLoading",value:function(){this.$tableLoading.toggleClass("open",!1)}},{key:"togglePagination",value:function(){this.options.pagination=!this.options.pagination;var t=this.options.showButtonIcons?this.options.pagination?this.options.icons.paginationSwitchDown:this.options.icons.paginationSwitchUp:"",e=this.options.showButtonText?this.options.pagination?this.options.formatPaginationSwitchUp():this.options.formatPaginationSwitchDown():"";this.$toolbar.find('button[name="paginationSwitch"]').html("".concat(Gg.sprintf(this.constants.html.icon,this.options.iconsPrefix,t)," ").concat(e)),this.updatePagination(),this.trigger("toggle-pagination",this.options.pagination)}},{key:"toggleFullscreen",value:function(){this.$el.closest(".bootstrap-table").toggleClass("fullscreen"),this.resetView()}},{key:"toggleView",value:function(){this.options.cardView=!this.options.cardView,this.initHeader();var t=this.options.showButtonIcons?this.options.cardView?this.options.icons.toggleOn:this.options.icons.toggleOff:"",e=this.options.showButtonText?this.options.cardView?this.options.formatToggleOff():this.options.formatToggleOn():"";this.$toolbar.find('button[name="toggle"]').html("".concat(Gg.sprintf(this.constants.html.icon,this.options.iconsPrefix,t)," ").concat(e)).attr("aria-label",e).attr("title",e),this.initBody(),this.trigger("toggle",this.options.cardView)}},{key:"resetSearch",value:function(t){var e=Gg.getSearchInput(this),i=t||"";e.val(i),this.searchText=i,this.onSearch({currentTarget:e},!1)}},{key:"filterBy",value:function(t,e){this.filterOptions=Gg.isEmptyObject(e)?this.options.filterOptions:i.default.extend(this.options.filterOptions,e),this.filterColumns=Gg.isEmptyObject(t)?{}:t,this.options.pageNumber=1,this.initSearch(),this.updatePagination()}},{key:"scrollTo",value:function(t){var e={unit:"px",value:0};"object"===n(t)?e=Object.assign(e,t):"string"==typeof t&&"bottom"===t?e.value=this.$tableBody[0].scrollHeight:"string"!=typeof t&&"number"!=typeof t||(e.value=t);var o=e.value;"rows"===e.unit&&(o=0,this.$body.find("> tr:lt(".concat(e.value,")")).each((function(t,e){o+=i.default(e).outerHeight(!0)}))),this.$tableBody.scrollTop(o)}},{key:"getScrollPosition",value:function(){return this.$tableBody.scrollTop()}},{key:"selectPage",value:function(t){t>0&&t<=this.options.totalPages&&(this.options.pageNumber=t,this.updatePagination())}},{key:"prevPage",value:function(){this.options.pageNumber>1&&(this.options.pageNumber--,this.updatePagination())}},{key:"nextPage",value:function(){this.options.pageNumber<this.options.totalPages&&(this.options.pageNumber++,this.updatePagination())}},{key:"toggleDetailView",value:function(t,e){this.$body.find(Gg.sprintf('> tr[data-index="%s"]',t)).next().is("tr.detail-view")?this.collapseRow(t):this.expandRow(t,e),this.resetView()}},{key:"expandRow",value:function(t,e){var i=this.data[t],n=this.$body.find(Gg.sprintf('> tr[data-index="%s"][data-has-detail-view]',t));if(this.options.detailViewIcon&&n.find("a.detail-icon").html(Gg.sprintf(this.constants.html.icon,this.options.iconsPrefix,this.options.icons.detailClose)),!n.next().is("tr.detail-view")){n.after(Gg.sprintf('<tr class="detail-view"><td colspan="%s"></td></tr>',n.children("td").length));var o=n.next().find("td"),a=e||this.options.detailFormatter,r=Gg.calculateObjectValue(this.options,a,[t,i,o],"");1===o.length&&o.append(r),this.trigger("expand-row",t,i,o)}}},{key:"expandRowByUniqueId",value:function(t){var e=this.getRowByUniqueId(t);e&&this.expandRow(this.data.indexOf(e))}},{key:"collapseRow",value:function(t){var e=this.data[t],i=this.$body.find(Gg.sprintf('> tr[data-index="%s"][data-has-detail-view]',t));i.next().is("tr.detail-view")&&(this.options.detailViewIcon&&i.find("a.detail-icon").html(Gg.sprintf(this.constants.html.icon,this.options.iconsPrefix,this.options.icons.detailOpen)),this.trigger("collapse-row",t,e,i.next()),i.next().remove())}},{key:"collapseRowByUniqueId",value:function(t){var e=this.getRowByUniqueId(t);e&&this.collapseRow(this.data.indexOf(e))}},{key:"expandAllRows",value:function(){for(var t=this.$body.find("> tr[data-index][data-has-detail-view]"),e=0;e<t.length;e++)this.expandRow(i.default(t[e]).data("index"))}},{key:"collapseAllRows",value:function(){for(var t=this.$body.find("> tr[data-index][data-has-detail-view]"),e=0;e<t.length;e++)this.collapseRow(i.default(t[e]).data("index"))}},{key:"updateColumnTitle",value:function(t){t.hasOwnProperty("field")&&t.hasOwnProperty("title")&&(this.columns[this.fieldsColumnsIndex[t.field]].title=this.options.escape?Gg.escapeHTML(t.title):t.title,this.columns[this.fieldsColumnsIndex[t.field]].visible&&(this.$header.find("th[data-field]").each((function(e,n){if(i.default(n).data("field")===t.field)return i.default(i.default(n).find(".th-inner")[0]).text(t.title),!1})),this.resetView()))}},{key:"updateFormatText",value:function(t,e){/^format/.test(t)&&this.options[t]&&("string"==typeof e?this.options[t]=function(){return e}:"function"==typeof e&&(this.options[t]=e),this.initToolbar(),this.initPagination(),this.initBody())}}]),t}();return ev.VERSION=Zg.VERSION,ev.DEFAULTS=Zg.DEFAULTS,ev.LOCALES=Zg.LOCALES,ev.COLUMN_DEFAULTS=Zg.COLUMN_DEFAULTS,ev.METHODS=Zg.METHODS,ev.EVENTS=Zg.EVENTS,i.default.BootstrapTable=ev,i.default.fn.bootstrapTable=function(t){for(var e=arguments.length,o=new Array(e>1?e-1:0),a=1;a<e;a++)o[a-1]=arguments[a];var r;return this.each((function(e,a){var s=i.default(a).data("bootstrap.table"),l=i.default.extend({},ev.DEFAULTS,i.default(a).data(),"object"===n(t)&&t);if("string"==typeof t){var c;if(!Zg.METHODS.includes(t))throw new Error("Unknown method: ".concat(t));if(!s)return;r=(c=s)[t].apply(c,o),"destroy"===t&&i.default(a).removeData("bootstrap.table")}s||(s=new i.default.BootstrapTable(a,l),i.default(a).data("bootstrap.table",s),s.init())})),void 0===r?this:r},i.default.fn.bootstrapTable.Constructor=ev,i.default.fn.bootstrapTable.theme=Zg.THEME,i.default.fn.bootstrapTable.VERSION=Zg.VERSION,i.default.fn.bootstrapTable.defaults=ev.DEFAULTS,i.default.fn.bootstrapTable.columnDefaults=ev.COLUMN_DEFAULTS,i.default.fn.bootstrapTable.events=ev.EVENTS,i.default.fn.bootstrapTable.locales=ev.LOCALES,i.default.fn.bootstrapTable.methods=ev.METHODS,i.default.fn.bootstrapTable.utils=Gg,i.default((function(){i.default('[data-toggle="table"]').bootstrapTable()})),ev}));

!function(){function e(e){return e&&e.__esModule?e.default:e}function t(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}function i(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function n(e,t){for(var i=0;i<t.length;i++){var n=t[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function r(e,t,i){return t&&n(e.prototype,t),i&&n(e,i),e}function a(e){return a=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)},a(e)}function o(e,t){return o=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e},o(e,t)}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&o(e,t)}function s(e,i){return!i||"object"!=((n=i)&&n.constructor===Symbol?"symbol":typeof n)&&"function"!=typeof i?t(e):i;var n}var u;function c(e){return Array.isArray(e)||"[object Object]"=={}.toString.call(e)}function d(e){return!e||"object"!=typeof e&&"function"!=typeof e}u=function e(){var t=[].slice.call(arguments),i=!1;"boolean"==typeof t[0]&&(i=t.shift());var n=t[0];if(d(n))throw new Error("extendee must be an object");for(var r=t.slice(1),a=r.length,o=0;o<a;o++){var l=r[o];for(var s in l)if(Object.prototype.hasOwnProperty.call(l,s)){var u=l[s];if(i&&c(u)){var h=Array.isArray(u)?[]:{};n[s]=e(!0,Object.prototype.hasOwnProperty.call(n,s)&&!d(n[s])?n[s]:h,u)}else n[s]=u}}return n};var h=function(){"use strict";function e(){i(this,e)}return r(e,[{key:"on",value:function(e,t){return this._callbacks=this._callbacks||{},this._callbacks[e]||(this._callbacks[e]=[]),this._callbacks[e].push(t),this}},{key:"emit",value:function(e){for(var t=arguments.length,i=new Array(t>1?t-1:0),n=1;n<t;n++)i[n-1]=arguments[n];this._callbacks=this._callbacks||{};var r=this._callbacks[e],a=!0,o=!1,l=void 0;if(r)try{for(var s,u=r[Symbol.iterator]();!(a=(s=u.next()).done);a=!0){var c=s.value;c.apply(this,i)}}catch(e){o=!0,l=e}finally{try{a||null==u.return||u.return()}finally{if(o)throw l}}return this.element&&this.element.dispatchEvent(this.makeEvent("dropzone:"+e,{args:i})),this}},{key:"makeEvent",value:function(e,t){var i={bubbles:!0,cancelable:!0,detail:t};if("function"==typeof window.CustomEvent)return new CustomEvent(e,i);var n=document.createEvent("CustomEvent");return n.initCustomEvent(e,i.bubbles,i.cancelable,i.detail),n}},{key:"off",value:function(e,t){if(!this._callbacks||0===arguments.length)return this._callbacks={},this;var i=this._callbacks[e];if(!i)return this;if(1===arguments.length)return delete this._callbacks[e],this;for(var n=0;n<i.length;n++){var r=i[n];if(r===t){i.splice(n,1);break}}return this}}]),e}();var p={url:null,method:"post",withCredentials:!1,timeout:null,parallelUploads:2,uploadMultiple:!1,chunking:!1,forceChunking:!1,chunkSize:2097152,parallelChunkUploads:!1,retryChunks:!1,retryChunksLimit:3,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,thumbnailMethod:"crop",resizeWidth:null,resizeHeight:null,resizeMimeType:null,resizeQuality:.8,resizeMethod:"contain",filesizeBase:1e3,maxFiles:null,headers:null,defaultHeaders:!0,clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,disablePreviews:!1,hiddenInputContainer:"body",capture:null,renameFilename:null,renameFile:null,forceFallback:!1,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictUploadCanceled:"Upload canceled.",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",dictFileSizeUnits:{tb:"TB",gb:"GB",mb:"MB",kb:"KB",b:"b"},init:function(){},params:function(e,t,i){if(i)return{dzuuid:i.file.upload.uuid,dzchunkindex:i.index,dztotalfilesize:i.file.size,dzchunksize:this.options.chunkSize,dztotalchunkcount:i.file.upload.totalChunkCount,dzchunkbyteoffset:i.index*this.options.chunkSize}},accept:function(e,t){return t()},chunksUploaded:function(e,t){t()},binaryBody:!1,fallback:function(){var e;this.element.className="".concat(this.element.className," dz-browser-not-supported");var t=!0,i=!1,n=void 0;try{for(var r,a=this.element.getElementsByTagName("div")[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var o=r.value;if(/(^| )dz-message($| )/.test(o.className)){e=o,o.className="dz-message";break}}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}e||(e=f.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(e));var l=e.getElementsByTagName("span")[0];return l&&(null!=l.textContent?l.textContent=this.options.dictFallbackMessage:null!=l.innerText&&(l.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize:function(e,t,i,n){var r={srcX:0,srcY:0,srcWidth:e.width,srcHeight:e.height},a=e.width/e.height;null==t&&null==i?(t=r.srcWidth,i=r.srcHeight):null==t?t=i*a:null==i&&(i=t/a);var o=(t=Math.min(t,r.srcWidth))/(i=Math.min(i,r.srcHeight));if(r.srcWidth>t||r.srcHeight>i)if("crop"===n)a>o?(r.srcHeight=e.height,r.srcWidth=r.srcHeight*o):(r.srcWidth=e.width,r.srcHeight=r.srcWidth/o);else{if("contain"!==n)throw new Error("Unknown resizeMethod '".concat(n,"'"));a>o?i=t/a:t=i*a}return r.srcX=(e.width-r.srcWidth)/2,r.srcY=(e.height-r.srcHeight)/2,r.trgWidth=t,r.trgHeight=i,r},transformFile:function(e,t){return(this.options.resizeWidth||this.options.resizeHeight)&&e.type.match(/image.*/)?this.resizeImage(e,this.options.resizeWidth,this.options.resizeHeight,this.options.resizeMethod,t):t(e)},previewTemplate:e('<div class="dz-file-preview dz-preview"> <div class="dz-image"><img data-dz-thumbnail=""></div> <div class="dz-details"> <div class="dz-size"><span data-dz-size=""></span></div> <div class="dz-filename"><span data-dz-name=""></span></div> </div> <div class="dz-progress"> <span class="dz-upload" data-dz-uploadprogress=""></span> </div> <div class="dz-error-message"><span data-dz-errormessage=""></span></div> <div class="dz-success-mark"> <svg width="54" height="54" fill="#fff"><path d="m10.207 29.793 4.086-4.086a1 1 0 0 1 1.414 0l5.586 5.586a1 1 0 0 0 1.414 0l15.586-15.586a1 1 0 0 1 1.414 0l4.086 4.086a1 1 0 0 1 0 1.414L22.707 42.293a1 1 0 0 1-1.414 0L10.207 31.207a1 1 0 0 1 0-1.414Z"/></svg> </div> <div class="dz-error-mark"> <svg width="54" height="54" fill="#fff"><path d="m26.293 20.293-7.086-7.086a1 1 0 0 0-1.414 0l-4.586 4.586a1 1 0 0 0 0 1.414l7.086 7.086a1 1 0 0 1 0 1.414l-7.086 7.086a1 1 0 0 0 0 1.414l4.586 4.586a1 1 0 0 0 1.414 0l7.086-7.086a1 1 0 0 1 1.414 0l7.086 7.086a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7.086-7.086a1 1 0 0 1 0-1.414l7.086-7.086a1 1 0 0 0 0-1.414l-4.586-4.586a1 1 0 0 0-1.414 0l-7.086 7.086a1 1 0 0 1-1.414 0Z"/></svg> </div> </div>'),drop:function(e){return this.element.classList.remove("dz-drag-hover")},dragstart:function(e){},dragend:function(e){return this.element.classList.remove("dz-drag-hover")},dragenter:function(e){return this.element.classList.add("dz-drag-hover")},dragover:function(e){return this.element.classList.add("dz-drag-hover")},dragleave:function(e){return this.element.classList.remove("dz-drag-hover")},paste:function(e){},reset:function(){return this.element.classList.remove("dz-started")},addedfile:function(e){if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer&&!this.options.disablePreviews){var t=this;e.previewElement=f.createElement(this.options.previewTemplate.trim()),e.previewTemplate=e.previewElement,this.previewsContainer.appendChild(e.previewElement);var i=!0,n=!1,r=void 0;try{for(var a,o=e.previewElement.querySelectorAll("[data-dz-name]")[Symbol.iterator]();!(i=(a=o.next()).done);i=!0){var l=a.value;l.textContent=e.name}}catch(e){n=!0,r=e}finally{try{i||null==o.return||o.return()}finally{if(n)throw r}}var s=!0,u=!1,c=void 0;try{for(var d,h=e.previewElement.querySelectorAll("[data-dz-size]")[Symbol.iterator]();!(s=(d=h.next()).done);s=!0)(l=d.value).innerHTML=this.filesize(e.size)}catch(e){u=!0,c=e}finally{try{s||null==h.return||h.return()}finally{if(u)throw c}}this.options.addRemoveLinks&&(e._removeLink=f.createElement('<a class="dz-remove" href="javascript:undefined;" data-dz-remove>'.concat(this.options.dictRemoveFile,"</a>")),e.previewElement.appendChild(e._removeLink));var p=function(i){var n=t;if(i.preventDefault(),i.stopPropagation(),e.status===f.UPLOADING)return f.confirm(t.options.dictCancelUploadConfirmation,(function(){return n.removeFile(e)}));var r=t;return t.options.dictRemoveFileConfirmation?f.confirm(t.options.dictRemoveFileConfirmation,(function(){return r.removeFile(e)})):t.removeFile(e)},m=!0,v=!1,y=void 0;try{for(var g,b=e.previewElement.querySelectorAll("[data-dz-remove]")[Symbol.iterator]();!(m=(g=b.next()).done);m=!0){g.value.addEventListener("click",p)}}catch(e){v=!0,y=e}finally{try{m||null==b.return||b.return()}finally{if(v)throw y}}}},removedfile:function(e){return null!=e.previewElement&&null!=e.previewElement.parentNode&&e.previewElement.parentNode.removeChild(e.previewElement),this._updateMaxFilesReachedClass()},thumbnail:function(e,t){if(e.previewElement){e.previewElement.classList.remove("dz-file-preview");var i=!0,n=!1,r=void 0;try{for(var a,o=e.previewElement.querySelectorAll("[data-dz-thumbnail]")[Symbol.iterator]();!(i=(a=o.next()).done);i=!0){var l=a.value;l.alt=e.name,l.src=t}}catch(e){n=!0,r=e}finally{try{i||null==o.return||o.return()}finally{if(n)throw r}}return setTimeout((function(){return e.previewElement.classList.add("dz-image-preview")}),1)}},error:function(e,t){if(e.previewElement){e.previewElement.classList.add("dz-error"),"string"!=typeof t&&t.error&&(t=t.error);var i=!0,n=!1,r=void 0;try{for(var a,o=e.previewElement.querySelectorAll("[data-dz-errormessage]")[Symbol.iterator]();!(i=(a=o.next()).done);i=!0){a.value.textContent=t}}catch(e){n=!0,r=e}finally{try{i||null==o.return||o.return()}finally{if(n)throw r}}}},errormultiple:function(){},processing:function(e){if(e.previewElement&&(e.previewElement.classList.add("dz-processing"),e._removeLink))return e._removeLink.innerHTML=this.options.dictCancelUpload},processingmultiple:function(){},uploadprogress:function(e,t,i){var n=!0,r=!1,a=void 0;if(e.previewElement)try{for(var o,l=e.previewElement.querySelectorAll("[data-dz-uploadprogress]")[Symbol.iterator]();!(n=(o=l.next()).done);n=!0){var s=o.value;"PROGRESS"===s.nodeName?s.value=t:s.style.width="".concat(t,"%")}}catch(e){r=!0,a=e}finally{try{n||null==l.return||l.return()}finally{if(r)throw a}}},totaluploadprogress:function(){},sending:function(){},sendingmultiple:function(){},success:function(e){if(e.previewElement)return e.previewElement.classList.add("dz-success")},successmultiple:function(){},canceled:function(e){return this.emit("error",e,this.options.dictUploadCanceled)},canceledmultiple:function(){},complete:function(e){if(e._removeLink&&(e._removeLink.innerHTML=this.options.dictRemoveFile),e.previewElement)return e.previewElement.classList.add("dz-complete")},completemultiple:function(){},maxfilesexceeded:function(){},maxfilesreached:function(){},queuecomplete:function(){},addedfiles:function(){}},f=function(n){"use strict";function o(n,r){var l,c,d,h;if(i(this,o),(l=s(this,(c=o,a(c)).call(this))).element=n,l.clickableElements=[],l.listeners=[],l.files=[],"string"==typeof l.element&&(l.element=document.querySelector(l.element)),!l.element||null==l.element.nodeType)throw new Error("Invalid dropzone element.");if(l.element.dropzone)throw new Error("Dropzone already attached.");o.instances.push(t(l)),l.element.dropzone=t(l);var f=null!=(h=o.optionsForElement(l.element))?h:{};if(l.options=e(u)(!0,{},p,f,null!=r?r:{}),l.options.previewTemplate=l.options.previewTemplate.replace(/\n*/g,""),l.options.forceFallback||!o.isBrowserSupported())return s(l,l.options.fallback.call(t(l)));if(null==l.options.url&&(l.options.url=l.element.getAttribute("action")),!l.options.url)throw new Error("No URL provided.");if(l.options.acceptedFiles&&l.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");if(l.options.uploadMultiple&&l.options.chunking)throw new Error("You cannot set both: uploadMultiple and chunking.");if(l.options.binaryBody&&l.options.uploadMultiple)throw new Error("You cannot set both: binaryBody and uploadMultiple.");return l.options.acceptedMimeTypes&&(l.options.acceptedFiles=l.options.acceptedMimeTypes,delete l.options.acceptedMimeTypes),null!=l.options.renameFilename&&(l.options.renameFile=function(e){return l.options.renameFilename.call(t(l),e.name,e)}),"string"==typeof l.options.method&&(l.options.method=l.options.method.toUpperCase()),(d=l.getExistingFallback())&&d.parentNode&&d.parentNode.removeChild(d),!1!==l.options.previewsContainer&&(l.options.previewsContainer?l.previewsContainer=o.getElement(l.options.previewsContainer,"previewsContainer"):l.previewsContainer=l.element),l.options.clickable&&(!0===l.options.clickable?l.clickableElements=[l.element]:l.clickableElements=o.getElements(l.options.clickable,"clickable")),l.init(),l}return l(o,n),r(o,[{key:"getAcceptedFiles",value:function(){return this.files.filter((function(e){return e.accepted})).map((function(e){return e}))}},{key:"getRejectedFiles",value:function(){return this.files.filter((function(e){return!e.accepted})).map((function(e){return e}))}},{key:"getFilesWithStatus",value:function(e){return this.files.filter((function(t){return t.status===e})).map((function(e){return e}))}},{key:"getQueuedFiles",value:function(){return this.getFilesWithStatus(o.QUEUED)}},{key:"getUploadingFiles",value:function(){return this.getFilesWithStatus(o.UPLOADING)}},{key:"getAddedFiles",value:function(){return this.getFilesWithStatus(o.ADDED)}},{key:"getActiveFiles",value:function(){return this.files.filter((function(e){return e.status===o.UPLOADING||e.status===o.QUEUED})).map((function(e){return e}))}},{key:"init",value:function(){var e=this,t=this,i=this,n=this,r=this,a=this,l=this,s=this,u=this,c=this,d=this;if("form"===this.element.tagName&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(o.createElement('<div class="dz-default dz-message"><button class="dz-button" type="button">'.concat(this.options.dictDefaultMessage,"</button></div>"))),this.clickableElements.length){var h=this,p=function(){var e=h;h.hiddenFileInput&&h.hiddenFileInput.parentNode.removeChild(h.hiddenFileInput),h.hiddenFileInput=document.createElement("input"),h.hiddenFileInput.setAttribute("type","file"),(null===h.options.maxFiles||h.options.maxFiles>1)&&h.hiddenFileInput.setAttribute("multiple","multiple"),h.hiddenFileInput.className="dz-hidden-input",null!==h.options.acceptedFiles&&h.hiddenFileInput.setAttribute("accept",h.options.acceptedFiles),null!==h.options.capture&&h.hiddenFileInput.setAttribute("capture",h.options.capture),h.hiddenFileInput.setAttribute("tabindex","-1"),h.hiddenFileInput.style.visibility="hidden",h.hiddenFileInput.style.position="absolute",h.hiddenFileInput.style.top="0",h.hiddenFileInput.style.left="0",h.hiddenFileInput.style.height="0",h.hiddenFileInput.style.width="0",o.getElement(h.options.hiddenInputContainer,"hiddenInputContainer").appendChild(h.hiddenFileInput),h.hiddenFileInput.addEventListener("change",(function(){var t=e.hiddenFileInput.files,i=!0,n=!1,r=void 0;if(t.length)try{for(var a,o=t[Symbol.iterator]();!(i=(a=o.next()).done);i=!0){var l=a.value;e.addFile(l)}}catch(e){n=!0,r=e}finally{try{i||null==o.return||o.return()}finally{if(n)throw r}}e.emit("addedfiles",t),p()}))};p()}this.URL=null!==window.URL?window.URL:window.webkitURL;var f=!0,m=!1,v=void 0;try{for(var y,g=this.events[Symbol.iterator]();!(f=(y=g.next()).done);f=!0){var b=y.value;this.on(b,this.options[b])}}catch(e){m=!0,v=e}finally{try{f||null==g.return||g.return()}finally{if(m)throw v}}this.on("uploadprogress",(function(){return e.updateTotalUploadProgress()})),this.on("removedfile",(function(){return t.updateTotalUploadProgress()})),this.on("canceled",(function(e){return i.emit("complete",e)})),this.on("complete",(function(e){var t=n;if(0===n.getAddedFiles().length&&0===n.getUploadingFiles().length&&0===n.getQueuedFiles().length)return setTimeout((function(){return t.emit("queuecomplete")}),0)}));var k=function(e){if(function(e){if(e.dataTransfer.types)for(var t=0;t<e.dataTransfer.types.length;t++)if("Files"===e.dataTransfer.types[t])return!0;return!1}(e))return e.stopPropagation(),e.preventDefault?e.preventDefault():e.returnValue=!1};return this.listeners=[{element:this.element,events:{dragstart:function(e){return r.emit("dragstart",e)},dragenter:function(e){return k(e),a.emit("dragenter",e)},dragover:function(e){var t;try{t=e.dataTransfer.effectAllowed}catch(e){}return e.dataTransfer.dropEffect="move"===t||"linkMove"===t?"move":"copy",k(e),l.emit("dragover",e)},dragleave:function(e){return s.emit("dragleave",e)},drop:function(e){return k(e),u.drop(e)},dragend:function(e){return c.emit("dragend",e)}}}],this.clickableElements.forEach((function(e){var t=d;return d.listeners.push({element:e,events:{click:function(i){return(e!==t.element||i.target===t.element||o.elementInside(i.target,t.element.querySelector(".dz-message")))&&t.hiddenFileInput.click(),!0}}})})),this.enable(),this.options.init.call(this)}},{key:"destroy",value:function(){return this.disable(),this.removeAllFiles(!0),(null!=this.hiddenFileInput?this.hiddenFileInput.parentNode:void 0)&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,o.instances.splice(o.instances.indexOf(this),1)}},{key:"updateTotalUploadProgress",value:function(){var e,t=0,i=0;if(this.getActiveFiles().length){var n=!0,r=!1,a=void 0;try{for(var o,l=this.getActiveFiles()[Symbol.iterator]();!(n=(o=l.next()).done);n=!0){var s=o.value;t+=s.upload.bytesSent,i+=s.upload.total}}catch(e){r=!0,a=e}finally{try{n||null==l.return||l.return()}finally{if(r)throw a}}e=100*t/i}else e=100;return this.emit("totaluploadprogress",e,i,t)}},{key:"_getParamName",value:function(e){return"function"==typeof this.options.paramName?this.options.paramName(e):"".concat(this.options.paramName).concat(this.options.uploadMultiple?"[".concat(e,"]"):"")}},{key:"_renameFile",value:function(e){return"function"!=typeof this.options.renameFile?e.name:this.options.renameFile(e)}},{key:"getFallbackForm",value:function(){var e,t;if(e=this.getExistingFallback())return e;var i='<div class="dz-fallback">';this.options.dictFallbackText&&(i+="<p>".concat(this.options.dictFallbackText,"</p>")),i+='<input type="file" name="'.concat(this._getParamName(0),'" ').concat(this.options.uploadMultiple?'multiple="multiple"':void 0,' /><input type="submit" value="Upload!"></div>');var n=o.createElement(i);return"FORM"!==this.element.tagName?(t=o.createElement('<form action="'.concat(this.options.url,'" enctype="multipart/form-data" method="').concat(this.options.method,'"></form>'))).appendChild(n):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),null!=t?t:n}},{key:"getExistingFallback",value:function(){var e=function(e){var t=!0,i=!1,n=void 0;try{for(var r,a=e[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var o=r.value;if(/(^| )fallback($| )/.test(o.className))return o}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}},t=!0,i=!1,n=void 0;try{for(var r,a=["div","form"][Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var o,l=r.value;if(o=e(this.element.getElementsByTagName(l)))return o}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}}},{key:"setupEventListeners",value:function(){return this.listeners.map((function(e){return function(){var t=[];for(var i in e.events){var n=e.events[i];t.push(e.element.addEventListener(i,n,!1))}return t}()}))}},{key:"removeEventListeners",value:function(){return this.listeners.map((function(e){return function(){var t=[];for(var i in e.events){var n=e.events[i];t.push(e.element.removeEventListener(i,n,!1))}return t}()}))}},{key:"disable",value:function(){var e=this;return this.clickableElements.forEach((function(e){return e.classList.remove("dz-clickable")})),this.removeEventListeners(),this.disabled=!0,this.files.map((function(t){return e.cancelUpload(t)}))}},{key:"enable",value:function(){return delete this.disabled,this.clickableElements.forEach((function(e){return e.classList.add("dz-clickable")})),this.setupEventListeners()}},{key:"filesize",value:function(e){var t=0,i="b";if(e>0){for(var n=["tb","gb","mb","kb","b"],r=0;r<n.length;r++){var a=n[r];if(e>=Math.pow(this.options.filesizeBase,4-r)/10){t=e/Math.pow(this.options.filesizeBase,4-r),i=a;break}}t=Math.round(10*t)/10}return"<strong>".concat(t,"</strong> ").concat(this.options.dictFileSizeUnits[i])}},{key:"_updateMaxFilesReachedClass",value:function(){return null!=this.options.maxFiles&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")}},{key:"drop",value:function(e){if(e.dataTransfer){this.emit("drop",e);for(var t=[],i=0;i<e.dataTransfer.files.length;i++)t[i]=e.dataTransfer.files[i];if(t.length){var n=e.dataTransfer.items;n&&n.length&&null!=n[0].webkitGetAsEntry?this._addFilesFromItems(n):this.handleFiles(t)}this.emit("addedfiles",t)}}},{key:"paste",value:function(e){if(null!=(t=null!=e?e.clipboardData:void 0,i=function(e){return e.items},null!=t?i(t):void 0)){var t,i;this.emit("paste",e);var n=e.clipboardData.items;return n.length?this._addFilesFromItems(n):void 0}}},{key:"handleFiles",value:function(e){var t=!0,i=!1,n=void 0;try{for(var r,a=e[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var o=r.value;this.addFile(o)}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}}},{key:"_addFilesFromItems",value:function(e){var t=this;return function(){var i=[],n=!0,r=!1,a=void 0;try{for(var o,l=e[Symbol.iterator]();!(n=(o=l.next()).done);n=!0){var s,u=o.value;null!=u.webkitGetAsEntry&&(s=u.webkitGetAsEntry())?s.isFile?i.push(t.addFile(u.getAsFile())):s.isDirectory?i.push(t._addFilesFromDirectory(s,s.name)):i.push(void 0):null!=u.getAsFile&&(null==u.kind||"file"===u.kind)?i.push(t.addFile(u.getAsFile())):i.push(void 0)}}catch(e){r=!0,a=e}finally{try{n||null==l.return||l.return()}finally{if(r)throw a}}return i}()}},{key:"_addFilesFromDirectory",value:function(e,t){var i=this,n=e.createReader(),r=function(e){return t=console,i="log",n=function(t){return t.log(e)},null!=t&&"function"==typeof t[i]?n(t,i):void 0;var t,i,n},a=function(){var e=i;return n.readEntries((function(i){if(i.length>0){var n=!0,r=!1,o=void 0;try{for(var l,s=i[Symbol.iterator]();!(n=(l=s.next()).done);n=!0){var u=l.value,c=e;u.isFile?u.file((function(e){if(!c.options.ignoreHiddenFiles||"."!==e.name.substring(0,1))return e.fullPath="".concat(t,"/").concat(e.name),c.addFile(e)})):u.isDirectory&&e._addFilesFromDirectory(u,"".concat(t,"/").concat(u.name))}}catch(e){r=!0,o=e}finally{try{n||null==s.return||s.return()}finally{if(r)throw o}}a()}return null}),r)};return a()}},{key:"accept",value:function(e,t){this.options.maxFilesize&&e.size>1048576*this.options.maxFilesize?t(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(e.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):o.isValidFile(e,this.options.acceptedFiles)?null!=this.options.maxFiles&&this.getAcceptedFiles().length>=this.options.maxFiles?(t(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",e)):this.options.accept.call(this,e,t):t(this.options.dictInvalidFileType)}},{key:"addFile",value:function(e){var t=this;e.upload={uuid:o.uuidv4(),progress:0,total:e.size,bytesSent:0,filename:this._renameFile(e)},this.files.push(e),e.status=o.ADDED,this.emit("addedfile",e),this._enqueueThumbnail(e),this.accept(e,(function(i){i?(e.accepted=!1,t._errorProcessing([e],i)):(e.accepted=!0,t.options.autoQueue&&t.enqueueFile(e)),t._updateMaxFilesReachedClass()}))}},{key:"enqueueFiles",value:function(e){var t=!0,i=!1,n=void 0;try{for(var r,a=e[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var o=r.value;this.enqueueFile(o)}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}return null}},{key:"enqueueFile",value:function(e){if(e.status!==o.ADDED||!0!==e.accepted)throw new Error("This file can't be queued because it has already been processed or was rejected.");var t=this;if(e.status=o.QUEUED,this.options.autoProcessQueue)return setTimeout((function(){return t.processQueue()}),0)}},{key:"_enqueueThumbnail",value:function(e){if(this.options.createImageThumbnails&&e.type.match(/image.*/)&&e.size<=1048576*this.options.maxThumbnailFilesize){var t=this;return this._thumbnailQueue.push(e),setTimeout((function(){return t._processThumbnailQueue()}),0)}}},{key:"_processThumbnailQueue",value:function(){var e=this;if(!this._processingThumbnail&&0!==this._thumbnailQueue.length){this._processingThumbnail=!0;var t=this._thumbnailQueue.shift();return this.createThumbnail(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,!0,(function(i){return e.emit("thumbnail",t,i),e._processingThumbnail=!1,e._processThumbnailQueue()}))}}},{key:"removeFile",value:function(e){if(e.status===o.UPLOADING&&this.cancelUpload(e),this.files=m(this.files,e),this.emit("removedfile",e),0===this.files.length)return this.emit("reset")}},{key:"removeAllFiles",value:function(e){null==e&&(e=!1);var t=!0,i=!1,n=void 0;try{for(var r,a=this.files.slice()[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var l=r.value;(l.status!==o.UPLOADING||e)&&this.removeFile(l)}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}return null}},{key:"resizeImage",value:function(e,t,i,n,r){var a=this;return this.createThumbnail(e,t,i,n,!0,(function(t,i){if(null==i)return r(e);var n=a.options.resizeMimeType;null==n&&(n=e.type);var l=i.toDataURL(n,a.options.resizeQuality);return"image/jpeg"!==n&&"image/jpg"!==n||(l=g.restore(e.dataURL,l)),r(o.dataURItoBlob(l))}))}},{key:"createThumbnail",value:function(e,t,i,n,r,a){var o=this,l=new FileReader;l.onload=function(){e.dataURL=l.result,"image/svg+xml"!==e.type?o.createThumbnailFromUrl(e,t,i,n,r,a):null!=a&&a(l.result)},l.readAsDataURL(e)}},{key:"displayExistingFile",value:function(e,t,i,n,r){var a=void 0===r||r;if(this.emit("addedfile",e),this.emit("complete",e),a){var o=this;e.dataURL=t,this.createThumbnailFromUrl(e,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,this.options.fixOrientation,(function(t){o.emit("thumbnail",e,t),i&&i()}),n)}else this.emit("thumbnail",e,t),i&&i()}},{key:"createThumbnailFromUrl",value:function(e,t,i,n,r,a,o){var l=this,s=document.createElement("img");return o&&(s.crossOrigin=o),r="from-image"!=getComputedStyle(document.body).imageOrientation&&r,s.onload=function(){var o=l,u=function(e){return e(1)};return"undefined"!=typeof EXIF&&null!==EXIF&&r&&(u=function(e){return EXIF.getData(s,(function(){return e(EXIF.getTag(this,"Orientation"))}))}),u((function(r){e.width=s.width,e.height=s.height;var l=o.options.resize.call(o,e,t,i,n),u=document.createElement("canvas"),c=u.getContext("2d");switch(u.width=l.trgWidth,u.height=l.trgHeight,r>4&&(u.width=l.trgHeight,u.height=l.trgWidth),r){case 2:c.translate(u.width,0),c.scale(-1,1);break;case 3:c.translate(u.width,u.height),c.rotate(Math.PI);break;case 4:c.translate(0,u.height),c.scale(1,-1);break;case 5:c.rotate(.5*Math.PI),c.scale(1,-1);break;case 6:c.rotate(.5*Math.PI),c.translate(0,-u.width);break;case 7:c.rotate(.5*Math.PI),c.translate(u.height,-u.width),c.scale(-1,1);break;case 8:c.rotate(-.5*Math.PI),c.translate(-u.height,0)}y(c,s,null!=l.srcX?l.srcX:0,null!=l.srcY?l.srcY:0,l.srcWidth,l.srcHeight,null!=l.trgX?l.trgX:0,null!=l.trgY?l.trgY:0,l.trgWidth,l.trgHeight);var d=u.toDataURL("image/png");if(null!=a)return a(d,u)}))},null!=a&&(s.onerror=a),s.src=e.dataURL}},{key:"processQueue",value:function(){var e=this.options.parallelUploads,t=this.getUploadingFiles().length,i=t;if(!(t>=e)){var n=this.getQueuedFiles();if(n.length>0){if(this.options.uploadMultiple)return this.processFiles(n.slice(0,e-t));for(;i<e;){if(!n.length)return;this.processFile(n.shift()),i++}}}}},{key:"processFile",value:function(e){return this.processFiles([e])}},{key:"processFiles",value:function(e){var t=!0,i=!1,n=void 0;try{for(var r,a=e[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){var l=r.value;l.processing=!0,l.status=o.UPLOADING,this.emit("processing",l)}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}return this.options.uploadMultiple&&this.emit("processingmultiple",e),this.uploadFiles(e)}},{key:"_getFilesWithXhr",value:function(e){return this.files.filter((function(t){return t.xhr===e})).map((function(e){return e}))}},{key:"cancelUpload",value:function(e){if(e.status===o.UPLOADING){var t=this._getFilesWithXhr(e.xhr),i=!0,n=!1,r=void 0;try{for(var a,l=t[Symbol.iterator]();!(i=(a=l.next()).done);i=!0){(p=a.value).status=o.CANCELED}}catch(e){n=!0,r=e}finally{try{i||null==l.return||l.return()}finally{if(n)throw r}}void 0!==e.xhr&&e.xhr.abort();var s=!0,u=!1,c=void 0;try{for(var d,h=t[Symbol.iterator]();!(s=(d=h.next()).done);s=!0){var p=d.value;this.emit("canceled",p)}}catch(e){u=!0,c=e}finally{try{s||null==h.return||h.return()}finally{if(u)throw c}}this.options.uploadMultiple&&this.emit("canceledmultiple",t)}else e.status!==o.ADDED&&e.status!==o.QUEUED||(e.status=o.CANCELED,this.emit("canceled",e),this.options.uploadMultiple&&this.emit("canceledmultiple",[e]));if(this.options.autoProcessQueue)return this.processQueue()}},{key:"resolveOption",value:function(e){for(var t=arguments.length,i=new Array(t>1?t-1:0),n=1;n<t;n++)i[n-1]=arguments[n];return"function"==typeof e?e.apply(this,i):e}},{key:"uploadFile",value:function(e){return this.uploadFiles([e])}},{key:"uploadFiles",value:function(e){var t=this;this._transformFiles(e,(function(i){if(t.options.chunking){var n=i[0];e[0].upload.chunked=t.options.chunking&&(t.options.forceChunking||n.size>t.options.chunkSize),e[0].upload.totalChunkCount=Math.ceil(n.size/t.options.chunkSize)}if(e[0].upload.chunked){var r=t,a=t,l=e[0];n=i[0];l.upload.chunks=[];var s=function(){for(var t=0;void 0!==l.upload.chunks[t];)t++;if(!(t>=l.upload.totalChunkCount)){0;var i=t*r.options.chunkSize,a=Math.min(i+r.options.chunkSize,n.size),s={name:r._getParamName(0),data:n.webkitSlice?n.webkitSlice(i,a):n.slice(i,a),filename:l.upload.filename,chunkIndex:t};l.upload.chunks[t]={file:l,index:t,dataBlock:s,status:o.UPLOADING,progress:0,retries:0},r._uploadData(e,[s])}};if(l.upload.finishedChunkUpload=function(t,i){var n=a,r=!0;t.status=o.SUCCESS,t.dataBlock=null,t.response=t.xhr.responseText,t.responseHeaders=t.xhr.getAllResponseHeaders(),t.xhr=null;for(var u=0;u<l.upload.totalChunkCount;u++){if(void 0===l.upload.chunks[u])return s();l.upload.chunks[u].status!==o.SUCCESS&&(r=!1)}r&&a.options.chunksUploaded(l,(function(){n._finished(e,i,null)}))},t.options.parallelChunkUploads)for(var u=0;u<l.upload.totalChunkCount;u++)s();else s()}else{var c=[];for(u=0;u<e.length;u++)c[u]={name:t._getParamName(u),data:i[u],filename:e[u].upload.filename};t._uploadData(e,c)}}))}},{key:"_getChunk",value:function(e,t){for(var i=0;i<e.upload.totalChunkCount;i++)if(void 0!==e.upload.chunks[i]&&e.upload.chunks[i].xhr===t)return e.upload.chunks[i]}},{key:"_uploadData",value:function(t,i){var n=this,r=this,a=this,o=this,l=new XMLHttpRequest,s=!0,c=!1,d=void 0;try{for(var h=t[Symbol.iterator]();!(s=(x=h.next()).done);s=!0){(g=x.value).xhr=l}}catch(e){c=!0,d=e}finally{try{s||null==h.return||h.return()}finally{if(c)throw d}}t[0].upload.chunked&&(t[0].upload.chunks[i[0].chunkIndex].xhr=l);var p=this.resolveOption(this.options.method,t,i),f=this.resolveOption(this.options.url,t,i);l.open(p,f,!0),this.resolveOption(this.options.timeout,t)&&(l.timeout=this.resolveOption(this.options.timeout,t)),l.withCredentials=!!this.options.withCredentials,l.onload=function(e){n._finishedUploading(t,l,e)},l.ontimeout=function(){r._handleUploadError(t,l,"Request timedout after ".concat(r.options.timeout/1e3," seconds"))},l.onerror=function(){a._handleUploadError(t,l)},(null!=l.upload?l.upload:l).onprogress=function(e){return o._updateFilesUploadProgress(t,l,e)};var m=this.options.defaultHeaders?{Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"}:{};for(var v in this.options.binaryBody&&(m["Content-Type"]=t[0].type),this.options.headers&&e(u)(m,this.options.headers),m){var y=m[v];y&&l.setRequestHeader(v,y)}if(this.options.binaryBody){s=!0,c=!1,d=void 0;try{for(h=t[Symbol.iterator]();!(s=(x=h.next()).done);s=!0){var g=x.value;this.emit("sending",g,l)}}catch(e){c=!0,d=e}finally{try{s||null==h.return||h.return()}finally{if(c)throw d}}this.options.uploadMultiple&&this.emit("sendingmultiple",t,l),this.submitRequest(l,null,t)}else{var b=new FormData;if(this.options.params){var k=this.options.params;for(var w in"function"==typeof k&&(k=k.call(this,t,l,t[0].upload.chunked?this._getChunk(t[0],l):null)),k){var F=k[w];if(Array.isArray(F))for(var E=0;E<F.length;E++)b.append(w,F[E]);else b.append(w,F)}}s=!0,c=!1,d=void 0;try{var x;for(h=t[Symbol.iterator]();!(s=(x=h.next()).done);s=!0){g=x.value;this.emit("sending",g,l,b)}}catch(e){c=!0,d=e}finally{try{s||null==h.return||h.return()}finally{if(c)throw d}}this.options.uploadMultiple&&this.emit("sendingmultiple",t,l,b),this._addFormElementData(b);for(E=0;E<i.length;E++){var z=i[E];b.append(z.name,z.data,z.filename)}this.submitRequest(l,b,t)}}},{key:"_transformFiles",value:function(e,t){for(var i=this,n=function(n){i.options.transformFile.call(i,e[n],(function(i){r[n]=i,++a===e.length&&t(r)}))},r=[],a=0,o=0;o<e.length;o++)n(o)}},{key:"_addFormElementData",value:function(e){var t=!0,i=!1,n=void 0;if("FORM"===this.element.tagName)try{for(var r=this.element.querySelectorAll("input, textarea, select, button")[Symbol.iterator]();!(t=(s=r.next()).done);t=!0){var a=s.value,o=a.getAttribute("name"),l=a.getAttribute("type");if(l&&(l=l.toLowerCase()),null!=o)if("SELECT"===a.tagName&&a.hasAttribute("multiple")){t=!0,i=!1,n=void 0;try{var s;for(r=a.options[Symbol.iterator]();!(t=(s=r.next()).done);t=!0){var u=s.value;u.selected&&e.append(o,u.value)}}catch(e){i=!0,n=e}finally{try{t||null==r.return||r.return()}finally{if(i)throw n}}}else(!l||"checkbox"!==l&&"radio"!==l||a.checked)&&e.append(o,a.value)}}catch(e){i=!0,n=e}finally{try{t||null==r.return||r.return()}finally{if(i)throw n}}}},{key:"_updateFilesUploadProgress",value:function(e,t,i){var n=!0,r=!1,a=void 0;if(e[0].upload.chunked){c=e[0];var o=this._getChunk(c,t);i?(o.progress=100*i.loaded/i.total,o.total=i.total,o.bytesSent=i.loaded):(o.progress=100,o.bytesSent=o.total),c.upload.progress=0,c.upload.total=0,c.upload.bytesSent=0;for(var l=0;l<c.upload.totalChunkCount;l++)c.upload.chunks[l]&&void 0!==c.upload.chunks[l].progress&&(c.upload.progress+=c.upload.chunks[l].progress,c.upload.total+=c.upload.chunks[l].total,c.upload.bytesSent+=c.upload.chunks[l].bytesSent);c.upload.progress=c.upload.progress/c.upload.totalChunkCount,this.emit("uploadprogress",c,c.upload.progress,c.upload.bytesSent)}else try{for(var s,u=e[Symbol.iterator]();!(n=(s=u.next()).done);n=!0){var c;(c=s.value).upload.total&&c.upload.bytesSent&&c.upload.bytesSent==c.upload.total||(i?(c.upload.progress=100*i.loaded/i.total,c.upload.total=i.total,c.upload.bytesSent=i.loaded):(c.upload.progress=100,c.upload.bytesSent=c.upload.total),this.emit("uploadprogress",c,c.upload.progress,c.upload.bytesSent))}}catch(e){r=!0,a=e}finally{try{n||null==u.return||u.return()}finally{if(r)throw a}}}},{key:"_finishedUploading",value:function(e,t,i){var n;if(e[0].status!==o.CANCELED&&4===t.readyState){if("arraybuffer"!==t.responseType&&"blob"!==t.responseType&&(n=t.responseText,t.getResponseHeader("content-type")&&~t.getResponseHeader("content-type").indexOf("application/json")))try{n=JSON.parse(n)}catch(e){i=e,n="Invalid JSON response from server."}this._updateFilesUploadProgress(e,t),200<=t.status&&t.status<300?e[0].upload.chunked?e[0].upload.finishedChunkUpload(this._getChunk(e[0],t),n):this._finished(e,n,i):this._handleUploadError(e,t,n)}}},{key:"_handleUploadError",value:function(e,t,i){if(e[0].status!==o.CANCELED){if(e[0].upload.chunked&&this.options.retryChunks){var n=this._getChunk(e[0],t);if(n.retries++<this.options.retryChunksLimit)return void this._uploadData(e,[n.dataBlock]);console.warn("Retried this chunk too often. Giving up.")}this._errorProcessing(e,i||this.options.dictResponseError.replace("{{statusCode}}",t.status),t)}}},{key:"submitRequest",value:function(e,t,i){if(1==e.readyState)if(this.options.binaryBody)if(i[0].upload.chunked){var n=this._getChunk(i[0],e);e.send(n.dataBlock.data)}else e.send(i[0]);else e.send(t);else console.warn("Cannot send this request because the XMLHttpRequest.readyState is not OPENED.")}},{key:"_finished",value:function(e,t,i){var n=!0,r=!1,a=void 0;try{for(var l,s=e[Symbol.iterator]();!(n=(l=s.next()).done);n=!0){var u=l.value;u.status=o.SUCCESS,this.emit("success",u,t,i),this.emit("complete",u)}}catch(e){r=!0,a=e}finally{try{n||null==s.return||s.return()}finally{if(r)throw a}}if(this.options.uploadMultiple&&(this.emit("successmultiple",e,t,i),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()}},{key:"_errorProcessing",value:function(e,t,i){var n=!0,r=!1,a=void 0;try{for(var l,s=e[Symbol.iterator]();!(n=(l=s.next()).done);n=!0){var u=l.value;u.status=o.ERROR,this.emit("error",u,t,i),this.emit("complete",u)}}catch(e){r=!0,a=e}finally{try{n||null==s.return||s.return()}finally{if(r)throw a}}if(this.options.uploadMultiple&&(this.emit("errormultiple",e,t,i),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()}}],[{key:"initClass",value:function(){this.prototype.Emitter=h,this.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],this.prototype._thumbnailQueue=[],this.prototype._processingThumbnail=!1}},{key:"uuidv4",value:function(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,(function(e){var t=16*Math.random()|0;return("x"===e?t:3&t|8).toString(16)}))}}]),o}(h);f.initClass(),f.options={},f.optionsForElement=function(e){return e.getAttribute("id")?f.options[v(e.getAttribute("id"))]:void 0},f.instances=[],f.forElement=function(e){if("string"==typeof e&&(e=document.querySelector(e)),null==(null!=e?e.dropzone:void 0))throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return e.dropzone},f.discover=function(){var e;if(document.querySelectorAll)e=document.querySelectorAll(".dropzone");else{e=[];var t=function(t){return function(){var i=[],n=!0,r=!1,a=void 0;try{for(var o,l=t[Symbol.iterator]();!(n=(o=l.next()).done);n=!0){var s=o.value;/(^| )dropzone($| )/.test(s.className)?i.push(e.push(s)):i.push(void 0)}}catch(e){r=!0,a=e}finally{try{n||null==l.return||l.return()}finally{if(r)throw a}}return i}()};t(document.getElementsByTagName("div")),t(document.getElementsByTagName("form"))}return function(){var t=[],i=!0,n=!1,r=void 0;try{for(var a,o=e[Symbol.iterator]();!(i=(a=o.next()).done);i=!0){var l=a.value;!1!==f.optionsForElement(l)?t.push(new f(l)):t.push(void 0)}}catch(e){n=!0,r=e}finally{try{i||null==o.return||o.return()}finally{if(n)throw r}}return t}()},f.blockedBrowsers=[/opera.*(Macintosh|Windows Phone).*version\/12/i],f.isBrowserSupported=function(){var e=!0;if(window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if("classList"in document.createElement("a")){void 0!==f.blacklistedBrowsers&&(f.blockedBrowsers=f.blacklistedBrowsers);var t=!0,i=!1,n=void 0;try{for(var r,a=f.blockedBrowsers[Symbol.iterator]();!(t=(r=a.next()).done);t=!0){r.value.test(navigator.userAgent)&&(e=!1)}}catch(e){i=!0,n=e}finally{try{t||null==a.return||a.return()}finally{if(i)throw n}}}else e=!1;else e=!1;return e},f.dataURItoBlob=function(e){for(var t=atob(e.split(",")[1]),i=e.split(",")[0].split(":")[1].split(";")[0],n=new ArrayBuffer(t.length),r=new Uint8Array(n),a=0,o=t.length,l=0<=o;l?a<=o:a>=o;l?a++:a--)r[a]=t.charCodeAt(a);return new Blob([n],{type:i})};var m=function(e,t){return e.filter((function(e){return e!==t})).map((function(e){return e}))},v=function(e){return e.replace(/[\-_](\w)/g,(function(e){return e.charAt(1).toUpperCase()}))};f.createElement=function(e){var t=document.createElement("div");return t.innerHTML=e,t.childNodes[0]},f.elementInside=function(e,t){if(e===t)return!0;for(;e=e.parentNode;)if(e===t)return!0;return!1},f.getElement=function(e,t){var i;if("string"==typeof e?i=document.querySelector(e):null!=e.nodeType&&(i=e),null==i)throw new Error("Invalid `".concat(t,"` option provided. Please provide a CSS selector or a plain HTML element."));return i},f.getElements=function(e,t){var i,n;if(e instanceof Array){n=[];try{var r=!0,a=!1,o=void 0;try{for(var l=e[Symbol.iterator]();!(r=(s=l.next()).done);r=!0)i=s.value,n.push(this.getElement(i,t))}catch(e){a=!0,o=e}finally{try{r||null==l.return||l.return()}finally{if(a)throw o}}}catch(e){n=null}}else if("string"==typeof e){n=[];r=!0,a=!1,o=void 0;try{var s;for(l=document.querySelectorAll(e)[Symbol.iterator]();!(r=(s=l.next()).done);r=!0)i=s.value,n.push(i)}catch(e){a=!0,o=e}finally{try{r||null==l.return||l.return()}finally{if(a)throw o}}}else null!=e.nodeType&&(n=[e]);if(null==n||!n.length)throw new Error("Invalid `".concat(t,"` option provided. Please provide a CSS selector, a plain HTML element or a list of those."));return n},f.confirm=function(e,t,i){return window.confirm(e)?t():null!=i?i():void 0},f.isValidFile=function(e,t){if(!t)return!0;t=t.split(",");var i=e.type,n=i.replace(/\/.*$/,""),r=!0,a=!1,o=void 0;try{for(var l,s=t[Symbol.iterator]();!(r=(l=s.next()).done);r=!0){var u=l.value;if("."===(u=u.trim()).charAt(0)){if(-1!==e.name.toLowerCase().indexOf(u.toLowerCase(),e.name.length-u.length))return!0}else if(/\/\*$/.test(u)){if(n===u.replace(/\/.*$/,""))return!0}else if(i===u)return!0}}catch(e){a=!0,o=e}finally{try{r||null==s.return||s.return()}finally{if(a)throw o}}return!1},"undefined"!=typeof jQuery&&null!==jQuery&&(jQuery.fn.dropzone=function(e){return this.each((function(){return new f(this,e)}))}),f.ADDED="added",f.QUEUED="queued",f.ACCEPTED=f.QUEUED,f.UPLOADING="uploading",f.PROCESSING=f.UPLOADING,f.CANCELED="canceled",f.ERROR="error",f.SUCCESS="success";var y=function(e,t,i,n,r,a,o,l,s,u){var c=function(e){e.naturalWidth;var t=e.naturalHeight,i=document.createElement("canvas");i.width=1,i.height=t;var n=i.getContext("2d");n.drawImage(e,0,0);for(var r=n.getImageData(1,0,1,t).data,a=0,o=t,l=t;l>a;)0===r[4*(l-1)+3]?o=l:a=l,l=o+a>>1;var s=l/t;return 0===s?1:s}(t);return e.drawImage(t,i,n,r,a,o,l,s,u/c)},g=function(){"use strict";function e(){i(this,e)}return r(e,null,[{key:"initClass",value:function(){this.KEY_STR="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="}},{key:"encode64",value:function(e){for(var t="",i=void 0,n=void 0,r="",a=void 0,o=void 0,l=void 0,s="",u=0;a=(i=e[u++])>>2,o=(3&i)<<4|(n=e[u++])>>4,l=(15&n)<<2|(r=e[u++])>>6,s=63&r,isNaN(n)?l=s=64:isNaN(r)&&(s=64),t=t+this.KEY_STR.charAt(a)+this.KEY_STR.charAt(o)+this.KEY_STR.charAt(l)+this.KEY_STR.charAt(s),i=n=r="",a=o=l=s="",u<e.length;);return t}},{key:"restore",value:function(e,t){if(!e.match("data:image/jpeg;base64,"))return t;var i=this.decode64(e.replace("data:image/jpeg;base64,","")),n=this.slice2Segments(i),r=this.exifManipulation(t,n);return"data:image/jpeg;base64,".concat(this.encode64(r))}},{key:"exifManipulation",value:function(e,t){var i=this.getExifArray(t),n=this.insertExif(e,i);return new Uint8Array(n)}},{key:"getExifArray",value:function(e){for(var t=void 0,i=0;i<e.length;){if(255===(t=e[i])[0]&225===t[1])return t;i++}return[]}},{key:"insertExif",value:function(e,t){var i=e.replace("data:image/jpeg;base64,",""),n=this.decode64(i),r=n.indexOf(255,3),a=n.slice(0,r),o=n.slice(r),l=a;return l=(l=l.concat(t)).concat(o)}},{key:"slice2Segments",value:function(e){for(var t=0,i=[];;){if(255===e[t]&218===e[t+1])break;if(255===e[t]&216===e[t+1])t+=2;else{var n=t+(256*e[t+2]+e[t+3])+2,r=e.slice(t,n);i.push(r),t=n}if(t>e.length)break}return i}},{key:"decode64",value:function(e){var t=void 0,i=void 0,n="",r=void 0,a=void 0,o="",l=0,s=[];for(/[^A-Za-z0-9\+\/\=]/g.exec(e)&&console.warn("There were invalid base64 characters in the input text.\nValid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\nExpect errors in decoding."),e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");t=this.KEY_STR.indexOf(e.charAt(l++))<<2|(r=this.KEY_STR.indexOf(e.charAt(l++)))>>4,i=(15&r)<<4|(a=this.KEY_STR.indexOf(e.charAt(l++)))>>2,n=(3&a)<<6|(o=this.KEY_STR.indexOf(e.charAt(l++))),s.push(t),64!==a&&s.push(i),64!==o&&s.push(n),t=i=n="",r=a=o="",l<e.length;);return s}}]),e}();g.initClass();window.Dropzone=f}();
//# sourceMappingURL=dropzone-min.js.map

/*!
 * dist/jquery.inputmask.min
 * https://github.com/RobinHerbots/Inputmask
 * Copyright (c) 2010 - 2021 Robin Herbots
 * Licensed under the MIT license
 * Version: 5.0.7
 */
!function(e,t){if("object"==typeof exports&&"object"==typeof module)module.exports=t(require("jquery"));else if("function"==typeof define&&define.amd)define(["jquery"],t);else{var i="object"==typeof exports?t(require("jquery")):t(e.jQuery);for(var a in i)("object"==typeof exports?exports:e)[a]=i[a]}}(self,(function(e){return function(){"use strict";var t={3046:function(e,t,i){var a;Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0,i(3851),i(219),i(207),i(5296);var n=((a=i(2394))&&a.__esModule?a:{default:a}).default;t.default=n},8741:function(e,t){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var i=!("undefined"==typeof window||!window.document||!window.document.createElement);t.default=i},3976:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a,n=(a=i(5581))&&a.__esModule?a:{default:a};var r={_maxTestPos:500,placeholder:"_",optionalmarker:["[","]"],quantifiermarker:["{","}"],groupmarker:["(",")"],alternatormarker:"|",escapeChar:"\\",mask:null,regex:null,oncomplete:function(){},onincomplete:function(){},oncleared:function(){},repeat:0,greedy:!1,autoUnmask:!1,removeMaskOnSubmit:!1,clearMaskOnLostFocus:!0,insertMode:!0,insertModeVisual:!0,clearIncomplete:!1,alias:null,onKeyDown:function(){},onBeforeMask:null,onBeforePaste:function(e,t){return"function"==typeof t.onBeforeMask?t.onBeforeMask.call(this,e,t):e},onBeforeWrite:null,onUnMask:null,showMaskOnFocus:!0,showMaskOnHover:!0,onKeyValidation:function(){},skipOptionalPartCharacter:" ",numericInput:!1,rightAlign:!1,undoOnEscape:!0,radixPoint:"",_radixDance:!1,groupSeparator:"",keepStatic:null,positionCaretOnTab:!0,tabThrough:!1,supportsInputType:["text","tel","url","password","search"],ignorables:[n.default.BACKSPACE,n.default.TAB,n.default["PAUSE/BREAK"],n.default.ESCAPE,n.default.PAGE_UP,n.default.PAGE_DOWN,n.default.END,n.default.HOME,n.default.LEFT,n.default.UP,n.default.RIGHT,n.default.DOWN,n.default.INSERT,n.default.DELETE,93,112,113,114,115,116,117,118,119,120,121,122,123,0,229],isComplete:null,preValidation:null,postValidation:null,staticDefinitionSymbol:void 0,jitMasking:!1,nullable:!0,inputEventOnly:!1,noValuePatching:!1,positionCaretOnClick:"lvp",casing:null,inputmode:"text",importDataAttributes:!0,shiftPositions:!0,usePrototypeDefinitions:!0,validationEventTimeOut:3e3,substitutes:{}};t.default=r},7392:function(e,t){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;t.default={9:{validator:"[0-9\uff10-\uff19]",definitionSymbol:"*"},a:{validator:"[A-Za-z\u0410-\u044f\u0401\u0451\xc0-\xff\xb5]",definitionSymbol:"*"},"*":{validator:"[0-9\uff10-\uff19A-Za-z\u0410-\u044f\u0401\u0451\xc0-\xff\xb5]"}}},3287:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a,n=(a=i(2047))&&a.__esModule?a:{default:a};if(void 0===n.default)throw"jQuery not loaded!";var r=n.default;t.default=r},9845:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.ua=t.mobile=t.iphone=t.iemobile=t.ie=void 0;var a,n=(a=i(9380))&&a.__esModule?a:{default:a};var r=n.default.navigator&&n.default.navigator.userAgent||"",o=r.indexOf("MSIE ")>0||r.indexOf("Trident/")>0,s="ontouchstart"in n.default,l=/iemobile/i.test(r),u=/iphone/i.test(r)&&!l;t.iphone=u,t.iemobile=l,t.mobile=s,t.ie=o,t.ua=r},7184:function(e,t){Object.defineProperty(t,"__esModule",{value:!0}),t.default=function(e){return e.replace(i,"\\$1")};var i=new RegExp("(\\"+["/",".","*","+","?","|","(",")","[","]","{","}","\\","$","^"].join("|\\")+")","gim")},6030:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.EventHandlers=void 0;var a,n=i(8711),r=(a=i(5581))&&a.__esModule?a:{default:a},o=i(9845),s=i(7215),l=i(7760),u=i(4713);function c(e,t){var i="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(!i){if(Array.isArray(e)||(i=function(e,t){if(!e)return;if("string"==typeof e)return f(e,t);var i=Object.prototype.toString.call(e).slice(8,-1);"Object"===i&&e.constructor&&(i=e.constructor.name);if("Map"===i||"Set"===i)return Array.from(e);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return f(e,t)}(e))||t&&e&&"number"==typeof e.length){i&&(e=i);var a=0,n=function(){};return{s:n,n:function(){return a>=e.length?{done:!0}:{done:!1,value:e[a++]}},e:function(e){throw e},f:n}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var r,o=!0,s=!1;return{s:function(){i=i.call(e)},n:function(){var e=i.next();return o=e.done,e},e:function(e){s=!0,r=e},f:function(){try{o||null==i.return||i.return()}finally{if(s)throw r}}}}function f(e,t){(null==t||t>e.length)&&(t=e.length);for(var i=0,a=new Array(t);i<t;i++)a[i]=e[i];return a}var d={keydownEvent:function(e){var t=this.inputmask,i=t.opts,a=t.dependencyLib,c=t.maskset,f=this,d=a(f),p=e.keyCode,h=n.caret.call(t,f),m=i.onKeyDown.call(this,e,n.getBuffer.call(t),h,i);if(void 0!==m)return m;if(p===r.default.BACKSPACE||p===r.default.DELETE||o.iphone&&p===r.default.BACKSPACE_SAFARI||e.ctrlKey&&p===r.default.X&&!("oncut"in f))e.preventDefault(),s.handleRemove.call(t,f,p,h),(0,l.writeBuffer)(f,n.getBuffer.call(t,!0),c.p,e,f.inputmask._valueGet()!==n.getBuffer.call(t).join(""));else if(p===r.default.END||p===r.default.PAGE_DOWN){e.preventDefault();var v=n.seekNext.call(t,n.getLastValidPosition.call(t));n.caret.call(t,f,e.shiftKey?h.begin:v,v,!0)}else p===r.default.HOME&&!e.shiftKey||p===r.default.PAGE_UP?(e.preventDefault(),n.caret.call(t,f,0,e.shiftKey?h.begin:0,!0)):i.undoOnEscape&&p===r.default.ESCAPE&&!0!==e.altKey?((0,l.checkVal)(f,!0,!1,t.undoValue.split("")),d.trigger("click")):p!==r.default.INSERT||e.shiftKey||e.ctrlKey||void 0!==t.userOptions.insertMode?!0===i.tabThrough&&p===r.default.TAB?!0===e.shiftKey?(h.end=n.seekPrevious.call(t,h.end,!0),!0===u.getTest.call(t,h.end-1).match.static&&h.end--,h.begin=n.seekPrevious.call(t,h.end,!0),h.begin>=0&&h.end>0&&(e.preventDefault(),n.caret.call(t,f,h.begin,h.end))):(h.begin=n.seekNext.call(t,h.begin,!0),h.end=n.seekNext.call(t,h.begin,!0),h.end<c.maskLength&&h.end--,h.begin<=c.maskLength&&(e.preventDefault(),n.caret.call(t,f,h.begin,h.end))):e.shiftKey||i.insertModeVisual&&!1===i.insertMode&&(p===r.default.RIGHT?setTimeout((function(){var e=n.caret.call(t,f);n.caret.call(t,f,e.begin)}),0):p===r.default.LEFT&&setTimeout((function(){var e=n.translatePosition.call(t,f.inputmask.caretPos.begin);n.translatePosition.call(t,f.inputmask.caretPos.end);t.isRTL?n.caret.call(t,f,e+(e===c.maskLength?0:1)):n.caret.call(t,f,e-(0===e?0:1))}),0)):s.isSelection.call(t,h)?i.insertMode=!i.insertMode:(i.insertMode=!i.insertMode,n.caret.call(t,f,h.begin,h.begin));t.ignorable=i.ignorables.includes(p)},keypressEvent:function(e,t,i,a,o){var u=this.inputmask||this,c=u.opts,f=u.dependencyLib,d=u.maskset,p=u.el,h=f(p),m=e.keyCode;if(!(!0===t||e.ctrlKey&&e.altKey)&&(e.ctrlKey||e.metaKey||u.ignorable))return m===r.default.ENTER&&u.undoValue!==u._valueGet(!0)&&(u.undoValue=u._valueGet(!0),setTimeout((function(){h.trigger("change")}),0)),u.skipInputEvent=!0,!0;if(m){44!==m&&46!==m||3!==e.location||""===c.radixPoint||(m=c.radixPoint.charCodeAt(0));var v,g=t?{begin:o,end:o}:n.caret.call(u,p),k=String.fromCharCode(m);k=c.substitutes[k]||k,d.writeOutBuffer=!0;var y=s.isValid.call(u,g,k,a,void 0,void 0,void 0,t);if(!1!==y&&(n.resetMaskSet.call(u,!0),v=void 0!==y.caret?y.caret:n.seekNext.call(u,y.pos.begin?y.pos.begin:y.pos),d.p=v),v=c.numericInput&&void 0===y.caret?n.seekPrevious.call(u,v):v,!1!==i&&(setTimeout((function(){c.onKeyValidation.call(p,m,y)}),0),d.writeOutBuffer&&!1!==y)){var b=n.getBuffer.call(u);(0,l.writeBuffer)(p,b,v,e,!0!==t)}if(e.preventDefault(),t)return!1!==y&&(y.forwardPosition=v),y}},keyupEvent:function(e){var t=this.inputmask;!t.isComposing||e.keyCode!==r.default.KEY_229&&e.keyCode!==r.default.ENTER||t.$el.trigger("input")},pasteEvent:function(e){var t,i=this.inputmask,a=i.opts,r=i._valueGet(!0),o=n.caret.call(i,this);i.isRTL&&(t=o.end,o.end=n.translatePosition.call(i,o.begin),o.begin=n.translatePosition.call(i,t));var s=r.substr(0,o.begin),u=r.substr(o.end,r.length);if(s==(i.isRTL?n.getBufferTemplate.call(i).slice().reverse():n.getBufferTemplate.call(i)).slice(0,o.begin).join("")&&(s=""),u==(i.isRTL?n.getBufferTemplate.call(i).slice().reverse():n.getBufferTemplate.call(i)).slice(o.end).join("")&&(u=""),window.clipboardData&&window.clipboardData.getData)r=s+window.clipboardData.getData("Text")+u;else{if(!e.clipboardData||!e.clipboardData.getData)return!0;r=s+e.clipboardData.getData("text/plain")+u}var f=r;if(i.isRTL){f=f.split("");var d,p=c(n.getBufferTemplate.call(i));try{for(p.s();!(d=p.n()).done;){var h=d.value;f[0]===h&&f.shift()}}catch(e){p.e(e)}finally{p.f()}f=f.join("")}if("function"==typeof a.onBeforePaste){if(!1===(f=a.onBeforePaste.call(i,f,a)))return!1;f||(f=r)}(0,l.checkVal)(this,!0,!1,f.toString().split(""),e),e.preventDefault()},inputFallBackEvent:function(e){var t=this.inputmask,i=t.opts,a=t.dependencyLib;var s=this,c=s.inputmask._valueGet(!0),f=(t.isRTL?n.getBuffer.call(t).slice().reverse():n.getBuffer.call(t)).join(""),p=n.caret.call(t,s,void 0,void 0,!0);if(f!==c){c=function(e,i,a){if(o.iemobile){var r=i.replace(n.getBuffer.call(t).join(""),"");if(1===r.length){var s=i.split("");s.splice(a.begin,0,r),i=s.join("")}}return i}(0,c,p);var h=function(e,a,r){for(var o,s,l,c=e.substr(0,r.begin).split(""),f=e.substr(r.begin).split(""),d=a.substr(0,r.begin).split(""),p=a.substr(r.begin).split(""),h=c.length>=d.length?c.length:d.length,m=f.length>=p.length?f.length:p.length,v="",g=[],k="~";c.length<h;)c.push(k);for(;d.length<h;)d.push(k);for(;f.length<m;)f.unshift(k);for(;p.length<m;)p.unshift(k);var y=c.concat(f),b=d.concat(p);for(s=0,o=y.length;s<o;s++)switch(l=u.getPlaceholder.call(t,n.translatePosition.call(t,s)),v){case"insertText":b[s-1]===y[s]&&r.begin==y.length-1&&g.push(y[s]),s=o;break;case"insertReplacementText":case"deleteContentBackward":y[s]===k?r.end++:s=o;break;default:y[s]!==b[s]&&(y[s+1]!==k&&y[s+1]!==l&&void 0!==y[s+1]||(b[s]!==l||b[s+1]!==k)&&b[s]!==k?b[s+1]===k&&b[s]===y[s+1]?(v="insertText",g.push(y[s]),r.begin--,r.end--):y[s]!==l&&y[s]!==k&&(y[s+1]===k||b[s]!==y[s]&&b[s+1]===y[s+1])?(v="insertReplacementText",g.push(y[s]),r.begin--):y[s]===k?(v="deleteContentBackward",(n.isMask.call(t,n.translatePosition.call(t,s),!0)||b[s]===i.radixPoint)&&r.end++):s=o:(v="insertText",g.push(y[s]),r.begin--,r.end--))}return{action:v,data:g,caret:r}}(c,f,p);switch((s.inputmask.shadowRoot||s.ownerDocument).activeElement!==s&&s.focus(),(0,l.writeBuffer)(s,n.getBuffer.call(t)),n.caret.call(t,s,p.begin,p.end,!0),h.action){case"insertText":case"insertReplacementText":h.data.forEach((function(e,i){var n=new a.Event("keypress");n.keyCode=e.charCodeAt(0),t.ignorable=!1,d.keypressEvent.call(s,n)})),setTimeout((function(){t.$el.trigger("keyup")}),0);break;case"deleteContentBackward":var m=new a.Event("keydown");m.keyCode=r.default.BACKSPACE,d.keydownEvent.call(s,m);break;default:(0,l.applyInputValue)(s,c)}e.preventDefault()}},compositionendEvent:function(e){var t=this.inputmask;t.isComposing=!1,t.$el.trigger("input")},setValueEvent:function(e){var t=this.inputmask,i=this,a=e&&e.detail?e.detail[0]:arguments[1];void 0===a&&(a=i.inputmask._valueGet(!0)),(0,l.applyInputValue)(i,a),(e.detail&&void 0!==e.detail[1]||void 0!==arguments[2])&&n.caret.call(t,i,e.detail?e.detail[1]:arguments[2])},focusEvent:function(e){var t=this.inputmask,i=t.opts,a=this,r=a.inputmask._valueGet();i.showMaskOnFocus&&r!==n.getBuffer.call(t).join("")&&(0,l.writeBuffer)(a,n.getBuffer.call(t),n.seekNext.call(t,n.getLastValidPosition.call(t))),!0!==i.positionCaretOnTab||!1!==t.mouseEnter||s.isComplete.call(t,n.getBuffer.call(t))&&-1!==n.getLastValidPosition.call(t)||d.clickEvent.apply(a,[e,!0]),t.undoValue=t._valueGet(!0)},invalidEvent:function(e){this.inputmask.validationEvent=!0},mouseleaveEvent:function(){var e=this.inputmask,t=e.opts,i=this;e.mouseEnter=!1,t.clearMaskOnLostFocus&&(i.inputmask.shadowRoot||i.ownerDocument).activeElement!==i&&(0,l.HandleNativePlaceholder)(i,e.originalPlaceholder)},clickEvent:function(e,t){var i=this.inputmask,a=this;if((a.inputmask.shadowRoot||a.ownerDocument).activeElement===a){var r=n.determineNewCaretPosition.call(i,n.caret.call(i,a),t);void 0!==r&&n.caret.call(i,a,r)}},cutEvent:function(e){var t=this.inputmask,i=t.maskset,a=this,o=n.caret.call(t,a),u=t.isRTL?n.getBuffer.call(t).slice(o.end,o.begin):n.getBuffer.call(t).slice(o.begin,o.end),c=t.isRTL?u.reverse().join(""):u.join("");window.navigator.clipboard?window.navigator.clipboard.writeText(c):window.clipboardData&&window.clipboardData.getData&&window.clipboardData.setData("Text",c),s.handleRemove.call(t,a,r.default.DELETE,o),(0,l.writeBuffer)(a,n.getBuffer.call(t),i.p,e,t.undoValue!==t._valueGet(!0))},blurEvent:function(e){var t=this.inputmask,i=t.opts,a=(0,t.dependencyLib)(this),r=this;if(r.inputmask){(0,l.HandleNativePlaceholder)(r,t.originalPlaceholder);var o=r.inputmask._valueGet(),u=n.getBuffer.call(t).slice();""!==o&&(i.clearMaskOnLostFocus&&(-1===n.getLastValidPosition.call(t)&&o===n.getBufferTemplate.call(t).join("")?u=[]:l.clearOptionalTail.call(t,u)),!1===s.isComplete.call(t,u)&&(setTimeout((function(){a.trigger("incomplete")}),0),i.clearIncomplete&&(n.resetMaskSet.call(t),u=i.clearMaskOnLostFocus?[]:n.getBufferTemplate.call(t).slice())),(0,l.writeBuffer)(r,u,void 0,e)),t.undoValue!==t._valueGet(!0)&&(t.undoValue=t._valueGet(!0),a.trigger("change"))}},mouseenterEvent:function(){var e=this.inputmask,t=e.opts,i=this;if(e.mouseEnter=!0,(i.inputmask.shadowRoot||i.ownerDocument).activeElement!==i){var a=(e.isRTL?n.getBufferTemplate.call(e).slice().reverse():n.getBufferTemplate.call(e)).join("");e.placeholder!==a&&i.placeholder!==e.originalPlaceholder&&(e.originalPlaceholder=i.placeholder),t.showMaskOnHover&&(0,l.HandleNativePlaceholder)(i,a)}},submitEvent:function(){var e=this.inputmask,t=e.opts;e.undoValue!==e._valueGet(!0)&&e.$el.trigger("change"),-1===n.getLastValidPosition.call(e)&&e._valueGet&&e._valueGet()===n.getBufferTemplate.call(e).join("")&&e._valueSet(""),t.clearIncomplete&&!1===s.isComplete.call(e,n.getBuffer.call(e))&&e._valueSet(""),t.removeMaskOnSubmit&&(e._valueSet(e.unmaskedvalue(),!0),setTimeout((function(){(0,l.writeBuffer)(e.el,n.getBuffer.call(e))}),0))},resetEvent:function(){var e=this.inputmask;e.refreshValue=!0,setTimeout((function(){(0,l.applyInputValue)(e.el,e._valueGet(!0))}),0)}};t.EventHandlers=d},9716:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.EventRuler=void 0;var a=s(i(2394)),n=s(i(5581)),r=i(8711),o=i(7760);function s(e){return e&&e.__esModule?e:{default:e}}var l={on:function(e,t,i){var s=e.inputmask.dependencyLib,l=function(t){t.originalEvent&&(t=t.originalEvent||t,arguments[0]=t);var l,u=this,c=u.inputmask,f=c?c.opts:void 0;if(void 0===c&&"FORM"!==this.nodeName){var d=s.data(u,"_inputmask_opts");s(u).off(),d&&new a.default(d).mask(u)}else{if(["submit","reset","setvalue"].includes(t.type)||"FORM"===this.nodeName||!(u.disabled||u.readOnly&&!("keydown"===t.type&&t.ctrlKey&&67===t.keyCode||!1===f.tabThrough&&t.keyCode===n.default.TAB))){switch(t.type){case"input":if(!0===c.skipInputEvent||t.inputType&&"insertCompositionText"===t.inputType)return c.skipInputEvent=!1,t.preventDefault();break;case"keydown":c.skipKeyPressEvent=!1,c.skipInputEvent=c.isComposing=t.keyCode===n.default.KEY_229;break;case"keyup":case"compositionend":c.isComposing&&(c.skipInputEvent=!1);break;case"keypress":if(!0===c.skipKeyPressEvent)return t.preventDefault();c.skipKeyPressEvent=!0;break;case"click":case"focus":return c.validationEvent?(c.validationEvent=!1,e.blur(),(0,o.HandleNativePlaceholder)(e,(c.isRTL?r.getBufferTemplate.call(c).slice().reverse():r.getBufferTemplate.call(c)).join("")),setTimeout((function(){e.focus()}),f.validationEventTimeOut),!1):(l=arguments,setTimeout((function(){e.inputmask&&i.apply(u,l)}),0),!1)}var p=i.apply(u,arguments);return!1===p&&(t.preventDefault(),t.stopPropagation()),p}t.preventDefault()}};["submit","reset"].includes(t)?(l=l.bind(e),null!==e.form&&s(e.form).on(t,l)):s(e).on(t,l),e.inputmask.events[t]=e.inputmask.events[t]||[],e.inputmask.events[t].push(l)},off:function(e,t){if(e.inputmask&&e.inputmask.events){var i=e.inputmask.dependencyLib,a=e.inputmask.events;for(var n in t&&((a=[])[t]=e.inputmask.events[t]),a){for(var r=a[n];r.length>0;){var o=r.pop();["submit","reset"].includes(n)?null!==e.form&&i(e.form).off(n,o):i(e).off(n,o)}delete e.inputmask.events[n]}}}};t.EventRuler=l},219:function(e,t,i){var a=d(i(2394)),n=d(i(5581)),r=d(i(7184)),o=i(8711),s=i(4713);function l(e){return l="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},l(e)}function u(e,t){return function(e){if(Array.isArray(e))return e}(e)||function(e,t){var i=null==e?null:"undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(null==i)return;var a,n,r=[],o=!0,s=!1;try{for(i=i.call(e);!(o=(a=i.next()).done)&&(r.push(a.value),!t||r.length!==t);o=!0);}catch(e){s=!0,n=e}finally{try{o||null==i.return||i.return()}finally{if(s)throw n}}return r}(e,t)||function(e,t){if(!e)return;if("string"==typeof e)return c(e,t);var i=Object.prototype.toString.call(e).slice(8,-1);"Object"===i&&e.constructor&&(i=e.constructor.name);if("Map"===i||"Set"===i)return Array.from(e);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return c(e,t)}(e,t)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function c(e,t){(null==t||t>e.length)&&(t=e.length);for(var i=0,a=new Array(t);i<t;i++)a[i]=e[i];return a}function f(e,t){for(var i=0;i<t.length;i++){var a=t[i];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function d(e){return e&&e.__esModule?e:{default:e}}var p=a.default.dependencyLib,h=function(){function e(t,i,a){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.mask=t,this.format=i,this.opts=a,this._date=new Date(1,0,1),this.initDateObject(t,this.opts)}var t,i,a;return t=e,(i=[{key:"date",get:function(){return void 0===this._date&&(this._date=new Date(1,0,1),this.initDateObject(void 0,this.opts)),this._date}},{key:"initDateObject",value:function(e,t){var i;for(P(t).lastIndex=0;i=P(t).exec(this.format);){var a=new RegExp("\\d+$").exec(i[0]),n=a?i[0][0]+"x":i[0],r=void 0;if(void 0!==e){if(a){var o=P(t).lastIndex,s=O(i.index,t);P(t).lastIndex=o,r=e.slice(0,e.indexOf(s.nextMatch[0]))}else r=e.slice(0,n.length);e=e.slice(r.length)}Object.prototype.hasOwnProperty.call(g,n)&&this.setValue(this,r,n,g[n][2],g[n][1])}}},{key:"setValue",value:function(e,t,i,a,n){if(void 0!==t&&(e[a]="ampm"===a?t:t.replace(/[^0-9]/g,"0"),e["raw"+a]=t.replace(/\s/g,"_")),void 0!==n){var r=e[a];("day"===a&&29===parseInt(r)||"month"===a&&2===parseInt(r))&&(29!==parseInt(e.day)||2!==parseInt(e.month)||""!==e.year&&void 0!==e.year||e._date.setFullYear(2012,1,29)),"day"===a&&(v=!0,0===parseInt(r)&&(r=1)),"month"===a&&(v=!0),"year"===a&&(v=!0,r.length<4&&(r=w(r,4,!0))),""===r||isNaN(r)||n.call(e._date,r),"ampm"===a&&n.call(e._date,r)}}},{key:"reset",value:function(){this._date=new Date(1,0,1)}},{key:"reInit",value:function(){this._date=void 0,this.date}}])&&f(t.prototype,i),a&&f(t,a),Object.defineProperty(t,"prototype",{writable:!1}),e}(),m=(new Date).getFullYear(),v=!1,g={d:["[1-9]|[12][0-9]|3[01]",Date.prototype.setDate,"day",Date.prototype.getDate],dd:["0[1-9]|[12][0-9]|3[01]",Date.prototype.setDate,"day",function(){return w(Date.prototype.getDate.call(this),2)}],ddd:[""],dddd:[""],m:["[1-9]|1[012]",function(e){var t=e?parseInt(e):0;return t>0&&t--,Date.prototype.setMonth.call(this,t)},"month",function(){return Date.prototype.getMonth.call(this)+1}],mm:["0[1-9]|1[012]",function(e){var t=e?parseInt(e):0;return t>0&&t--,Date.prototype.setMonth.call(this,t)},"month",function(){return w(Date.prototype.getMonth.call(this)+1,2)}],mmm:[""],mmmm:[""],yy:["[0-9]{2}",Date.prototype.setFullYear,"year",function(){return w(Date.prototype.getFullYear.call(this),2)}],yyyy:["[0-9]{4}",Date.prototype.setFullYear,"year",function(){return w(Date.prototype.getFullYear.call(this),4)}],h:["[1-9]|1[0-2]",Date.prototype.setHours,"hours",Date.prototype.getHours],hh:["0[1-9]|1[0-2]",Date.prototype.setHours,"hours",function(){return w(Date.prototype.getHours.call(this),2)}],hx:[function(e){return"[0-9]{".concat(e,"}")},Date.prototype.setHours,"hours",function(e){return Date.prototype.getHours}],H:["1?[0-9]|2[0-3]",Date.prototype.setHours,"hours",Date.prototype.getHours],HH:["0[0-9]|1[0-9]|2[0-3]",Date.prototype.setHours,"hours",function(){return w(Date.prototype.getHours.call(this),2)}],Hx:[function(e){return"[0-9]{".concat(e,"}")},Date.prototype.setHours,"hours",function(e){return function(){return w(Date.prototype.getHours.call(this),e)}}],M:["[1-5]?[0-9]",Date.prototype.setMinutes,"minutes",Date.prototype.getMinutes],MM:["0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9]",Date.prototype.setMinutes,"minutes",function(){return w(Date.prototype.getMinutes.call(this),2)}],s:["[1-5]?[0-9]",Date.prototype.setSeconds,"seconds",Date.prototype.getSeconds],ss:["0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9]",Date.prototype.setSeconds,"seconds",function(){return w(Date.prototype.getSeconds.call(this),2)}],l:["[0-9]{3}",Date.prototype.setMilliseconds,"milliseconds",function(){return w(Date.prototype.getMilliseconds.call(this),3)}],L:["[0-9]{2}",Date.prototype.setMilliseconds,"milliseconds",function(){return w(Date.prototype.getMilliseconds.call(this),2)}],t:["[ap]",y,"ampm",b,1],tt:["[ap]m",y,"ampm",b,2],T:["[AP]",y,"ampm",b,1],TT:["[AP]M",y,"ampm",b,2],Z:[".*",void 0,"Z",function(){var e=this.toString().match(/\((.+)\)/)[1];e.includes(" ")&&(e=(e=e.replace("-"," ").toUpperCase()).split(" ").map((function(e){return u(e,1)[0]})).join(""));return e}],o:[""],S:[""]},k={isoDate:"yyyy-mm-dd",isoTime:"HH:MM:ss",isoDateTime:"yyyy-mm-dd'T'HH:MM:ss",isoUtcDateTime:"UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"};function y(e){var t=this.getHours();e.toLowerCase().includes("p")?this.setHours(t+12):e.toLowerCase().includes("a")&&t>=12&&this.setHours(t-12)}function b(){var e=this.getHours();return(e=e||12)>=12?"PM":"AM"}function x(e){var t=new RegExp("\\d+$").exec(e[0]);if(t&&void 0!==t[0]){var i=g[e[0][0]+"x"].slice("");return i[0]=i[0](t[0]),i[3]=i[3](t[0]),i}if(g[e[0]])return g[e[0]]}function P(e){if(!e.tokenizer){var t=[],i=[];for(var a in g)if(/\.*x$/.test(a)){var n=a[0]+"\\d+";-1===i.indexOf(n)&&i.push(n)}else-1===t.indexOf(a[0])&&t.push(a[0]);e.tokenizer="("+(i.length>0?i.join("|")+"|":"")+t.join("+|")+")+?|.",e.tokenizer=new RegExp(e.tokenizer,"g")}return e.tokenizer}function E(e,t,i){if(!v)return!0;if(void 0===e.rawday||!isFinite(e.rawday)&&new Date(e.date.getFullYear(),isFinite(e.rawmonth)?e.month:e.date.getMonth()+1,0).getDate()>=e.day||"29"==e.day&&(!isFinite(e.rawyear)||void 0===e.rawyear||""===e.rawyear)||new Date(e.date.getFullYear(),isFinite(e.rawmonth)?e.month:e.date.getMonth()+1,0).getDate()>=e.day)return t;if("29"==e.day){var a=O(t.pos,i);if("yyyy"===a.targetMatch[0]&&t.pos-a.targetMatchIndex==2)return t.remove=t.pos+1,t}else if("02"==e.month&&"30"==e.day&&void 0!==t.c)return e.day="03",e.date.setDate(3),e.date.setMonth(1),t.insert=[{pos:t.pos,c:"0"},{pos:t.pos+1,c:t.c}],t.caret=o.seekNext.call(this,t.pos+1),t;return!1}function S(e,t,i,a){var n,o,s="";for(P(i).lastIndex=0;n=P(i).exec(e);){if(void 0===t)if(o=x(n))s+="("+o[0]+")";else switch(n[0]){case"[":s+="(";break;case"]":s+=")?";break;default:s+=(0,r.default)(n[0])}else if(o=x(n))if(!0!==a&&o[3])s+=o[3].call(t.date);else o[2]?s+=t["raw"+o[2]]:s+=n[0];else s+=n[0]}return s}function w(e,t,i){for(e=String(e),t=t||2;e.length<t;)e=i?e+"0":"0"+e;return e}function _(e,t,i){return"string"==typeof e?new h(e,t,i):e&&"object"===l(e)&&Object.prototype.hasOwnProperty.call(e,"date")?e:void 0}function M(e,t){return S(t.inputFormat,{date:e},t)}function O(e,t){var i,a,n=0,r=0;for(P(t).lastIndex=0;a=P(t).exec(t.inputFormat);){var o=new RegExp("\\d+$").exec(a[0]);if((n+=r=o?parseInt(o[0]):a[0].length)>=e+1){i=a,a=P(t).exec(t.inputFormat);break}}return{targetMatchIndex:n-r,nextMatch:a,targetMatch:i}}a.default.extendAliases({datetime:{mask:function(e){return e.numericInput=!1,g.S=e.i18n.ordinalSuffix.join("|"),e.inputFormat=k[e.inputFormat]||e.inputFormat,e.displayFormat=k[e.displayFormat]||e.displayFormat||e.inputFormat,e.outputFormat=k[e.outputFormat]||e.outputFormat||e.inputFormat,e.placeholder=""!==e.placeholder?e.placeholder:e.inputFormat.replace(/[[\]]/,""),e.regex=S(e.inputFormat,void 0,e),e.min=_(e.min,e.inputFormat,e),e.max=_(e.max,e.inputFormat,e),null},placeholder:"",inputFormat:"isoDateTime",displayFormat:null,outputFormat:null,min:null,max:null,skipOptionalPartCharacter:"",i18n:{dayNames:["Mon","Tue","Wed","Thu","Fri","Sat","Sun","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],monthNames:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","January","February","March","April","May","June","July","August","September","October","November","December"],ordinalSuffix:["st","nd","rd","th"]},preValidation:function(e,t,i,a,n,r,o,s){if(s)return!0;if(isNaN(i)&&e[t]!==i){var l=O(t,n);if(l.nextMatch&&l.nextMatch[0]===i&&l.targetMatch[0].length>1){var u=g[l.targetMatch[0]][0];if(new RegExp(u).test("0"+e[t-1]))return e[t]=e[t-1],e[t-1]="0",{fuzzy:!0,buffer:e,refreshFromBuffer:{start:t-1,end:t+1},pos:t+1}}}return!0},postValidation:function(e,t,i,a,n,r,o,l){var u,c;if(o)return!0;if(!1===a&&(((u=O(t+1,n)).targetMatch&&u.targetMatchIndex===t&&u.targetMatch[0].length>1&&void 0!==g[u.targetMatch[0]]||(u=O(t+2,n)).targetMatch&&u.targetMatchIndex===t+1&&u.targetMatch[0].length>1&&void 0!==g[u.targetMatch[0]])&&(c=g[u.targetMatch[0]][0]),void 0!==c&&(void 0!==r.validPositions[t+1]&&new RegExp(c).test(i+"0")?(e[t]=i,e[t+1]="0",a={pos:t+2,caret:t}):new RegExp(c).test("0"+i)&&(e[t]="0",e[t+1]=i,a={pos:t+2})),!1===a))return a;if(a.fuzzy&&(e=a.buffer,t=a.pos),(u=O(t,n)).targetMatch&&u.targetMatch[0]&&void 0!==g[u.targetMatch[0]]){var f=g[u.targetMatch[0]];c=f[0];var d=e.slice(u.targetMatchIndex,u.targetMatchIndex+u.targetMatch[0].length);if(!1===new RegExp(c).test(d.join(""))&&2===u.targetMatch[0].length&&r.validPositions[u.targetMatchIndex]&&r.validPositions[u.targetMatchIndex+1]&&(r.validPositions[u.targetMatchIndex+1].input="0"),"year"==f[2])for(var p=s.getMaskTemplate.call(this,!1,1,void 0,!0),h=t+1;h<e.length;h++)e[h]=p[h],delete r.validPositions[h]}var v=a,k=_(e.join(""),n.inputFormat,n);return v&&k.date.getTime()==k.date.getTime()&&(n.prefillYear&&(v=function(e,t,i){if(e.year!==e.rawyear){var a=m.toString(),n=e.rawyear.replace(/[^0-9]/g,""),r=a.slice(0,n.length),o=a.slice(n.length);if(2===n.length&&n===r){var s=new Date(m,e.month-1,e.day);e.day==s.getDate()&&(!i.max||i.max.date.getTime()>=s.getTime())&&(e.date.setFullYear(m),e.year=a,t.insert=[{pos:t.pos+1,c:o[0]},{pos:t.pos+2,c:o[1]}])}}return t}(k,v,n)),v=function(e,t,i,a,n){if(!t)return t;if(t&&i.min&&i.min.date.getTime()==i.min.date.getTime()){var r;for(e.reset(),P(i).lastIndex=0;r=P(i).exec(i.inputFormat);){var o;if((o=x(r))&&o[3]){for(var s=o[1],l=e[o[2]],u=i.min[o[2]],c=i.max?i.max[o[2]]:u,f=[],d=!1,p=0;p<u.length;p++)void 0!==a.validPositions[p+r.index]||d?(f[p]=l[p],d=d||l[p]>u[p]):(f[p]=u[p],"year"===o[2]&&l.length-1==p&&u!=c&&(f=(parseInt(f.join(""))+1).toString().split("")),"ampm"===o[2]&&u!=c&&i.min.date.getTime()>e.date.getTime()&&(f[p]=c[p]));s.call(e._date,f.join(""))}}t=i.min.date.getTime()<=e.date.getTime(),e.reInit()}return t&&i.max&&i.max.date.getTime()==i.max.date.getTime()&&(t=i.max.date.getTime()>=e.date.getTime()),t}(k,v=E.call(this,k,v,n),n,r)),void 0!==t&&v&&a.pos!==t?{buffer:S(n.inputFormat,k,n).split(""),refreshFromBuffer:{start:t,end:a.pos},pos:a.caret||a.pos}:v},onKeyDown:function(e,t,i,a){e.ctrlKey&&e.keyCode===n.default.RIGHT&&(this.inputmask._valueSet(M(new Date,a)),p(this).trigger("setvalue"))},onUnMask:function(e,t,i){return t?S(i.outputFormat,_(e,i.inputFormat,i),i,!0):t},casing:function(e,t,i,a){return 0==t.nativeDef.indexOf("[ap]")?e.toLowerCase():0==t.nativeDef.indexOf("[AP]")?e.toUpperCase():e},onBeforeMask:function(e,t){return"[object Date]"===Object.prototype.toString.call(e)&&(e=M(e,t)),e},insertMode:!1,shiftPositions:!1,keepStatic:!1,inputmode:"numeric",prefillYear:!0}})},3851:function(e,t,i){var a,n=(a=i(2394))&&a.__esModule?a:{default:a},r=i(8711),o=i(4713);n.default.extendDefinitions({A:{validator:"[A-Za-z\u0410-\u044f\u0401\u0451\xc0-\xff\xb5]",casing:"upper"},"&":{validator:"[0-9A-Za-z\u0410-\u044f\u0401\u0451\xc0-\xff\xb5]",casing:"upper"},"#":{validator:"[0-9A-Fa-f]",casing:"upper"}});var s=new RegExp("25[0-5]|2[0-4][0-9]|[01][0-9][0-9]");function l(e,t,i,a,n){return i-1>-1&&"."!==t.buffer[i-1]?(e=t.buffer[i-1]+e,e=i-2>-1&&"."!==t.buffer[i-2]?t.buffer[i-2]+e:"0"+e):e="00"+e,s.test(e)}n.default.extendAliases({cssunit:{regex:"[+-]?[0-9]+\\.?([0-9]+)?(px|em|rem|ex|%|in|cm|mm|pt|pc)"},url:{regex:"(https?|ftp)://.*",autoUnmask:!1,keepStatic:!1,tabThrough:!0},ip:{mask:"i{1,3}.j{1,3}.k{1,3}.l{1,3}",definitions:{i:{validator:l},j:{validator:l},k:{validator:l},l:{validator:l}},onUnMask:function(e,t,i){return e},inputmode:"decimal",substitutes:{",":"."}},email:{mask:function(e){var t="*{1,64}[.*{1,64}][.*{1,64}][.*{1,63}]@-{1,63}.-{1,63}[.-{1,63}][.-{1,63}]",i=t;if(e.separator)for(var a=0;a<e.quantifier;a++)i+="[".concat(e.separator).concat(t,"]");return i},greedy:!1,casing:"lower",separator:null,quantifier:5,skipOptionalPartCharacter:"",onBeforePaste:function(e,t){return(e=e.toLowerCase()).replace("mailto:","")},definitions:{"*":{validator:"[0-9\uff11-\uff19A-Za-z\u0410-\u044f\u0401\u0451\xc0-\xff\xb5!#$%&'*+/=?^_`{|}~-]"},"-":{validator:"[0-9A-Za-z-]"}},onUnMask:function(e,t,i){return e},inputmode:"email"},mac:{mask:"##:##:##:##:##:##"},vin:{mask:"V{13}9{4}",definitions:{V:{validator:"[A-HJ-NPR-Za-hj-npr-z\\d]",casing:"upper"}},clearIncomplete:!0,autoUnmask:!0},ssn:{mask:"999-99-9999",postValidation:function(e,t,i,a,n,s,l){var u=o.getMaskTemplate.call(this,!0,r.getLastValidPosition.call(this),!0,!0);return/^(?!219-09-9999|078-05-1120)(?!666|000|9.{2}).{3}-(?!00).{2}-(?!0{4}).{4}$/.test(u.join(""))}}})},207:function(e,t,i){var a=s(i(2394)),n=s(i(5581)),r=s(i(7184)),o=i(8711);function s(e){return e&&e.__esModule?e:{default:e}}var l=a.default.dependencyLib;function u(e,t){for(var i="",n=0;n<e.length;n++)a.default.prototype.definitions[e.charAt(n)]||t.definitions[e.charAt(n)]||t.optionalmarker[0]===e.charAt(n)||t.optionalmarker[1]===e.charAt(n)||t.quantifiermarker[0]===e.charAt(n)||t.quantifiermarker[1]===e.charAt(n)||t.groupmarker[0]===e.charAt(n)||t.groupmarker[1]===e.charAt(n)||t.alternatormarker===e.charAt(n)?i+="\\"+e.charAt(n):i+=e.charAt(n);return i}function c(e,t,i,a){if(e.length>0&&t>0&&(!i.digitsOptional||a)){var n=e.indexOf(i.radixPoint),r=!1;i.negationSymbol.back===e[e.length-1]&&(r=!0,e.length--),-1===n&&(e.push(i.radixPoint),n=e.length-1);for(var o=1;o<=t;o++)isFinite(e[n+o])||(e[n+o]="0")}return r&&e.push(i.negationSymbol.back),e}function f(e,t){var i=0;if("+"===e){for(i in t.validPositions);i=o.seekNext.call(this,parseInt(i))}for(var a in t.tests)if((a=parseInt(a))>=i)for(var n=0,r=t.tests[a].length;n<r;n++)if((void 0===t.validPositions[a]||"-"===e)&&t.tests[a][n].match.def===e)return a+(void 0!==t.validPositions[a]&&"-"!==e?1:0);return i}function d(e,t){var i=-1;for(var a in t.validPositions){var n=t.validPositions[a];if(n&&n.match.def===e){i=parseInt(a);break}}return i}function p(e,t,i,a,n){var r=t.buffer?t.buffer.indexOf(n.radixPoint):-1,o=(-1!==r||a&&n.jitMasking)&&new RegExp(n.definitions[9].validator).test(e);return n._radixDance&&-1!==r&&o&&null==t.validPositions[r]?{insert:{pos:r===i?r+1:r,c:n.radixPoint},pos:i}:o}a.default.extendAliases({numeric:{mask:function(e){e.repeat=0,e.groupSeparator===e.radixPoint&&e.digits&&"0"!==e.digits&&("."===e.radixPoint?e.groupSeparator=",":","===e.radixPoint?e.groupSeparator=".":e.groupSeparator="")," "===e.groupSeparator&&(e.skipOptionalPartCharacter=void 0),e.placeholder.length>1&&(e.placeholder=e.placeholder.charAt(0)),"radixFocus"===e.positionCaretOnClick&&""===e.placeholder&&(e.positionCaretOnClick="lvp");var t="0",i=e.radixPoint;!0===e.numericInput&&void 0===e.__financeInput?(t="1",e.positionCaretOnClick="radixFocus"===e.positionCaretOnClick?"lvp":e.positionCaretOnClick,e.digitsOptional=!1,isNaN(e.digits)&&(e.digits=2),e._radixDance=!1,i=","===e.radixPoint?"?":"!",""!==e.radixPoint&&void 0===e.definitions[i]&&(e.definitions[i]={},e.definitions[i].validator="["+e.radixPoint+"]",e.definitions[i].placeholder=e.radixPoint,e.definitions[i].static=!0,e.definitions[i].generated=!0)):(e.__financeInput=!1,e.numericInput=!0);var a,n="[+]";if(n+=u(e.prefix,e),""!==e.groupSeparator?(void 0===e.definitions[e.groupSeparator]&&(e.definitions[e.groupSeparator]={},e.definitions[e.groupSeparator].validator="["+e.groupSeparator+"]",e.definitions[e.groupSeparator].placeholder=e.groupSeparator,e.definitions[e.groupSeparator].static=!0,e.definitions[e.groupSeparator].generated=!0),n+=e._mask(e)):n+="9{+}",void 0!==e.digits&&0!==e.digits){var o=e.digits.toString().split(",");isFinite(o[0])&&o[1]&&isFinite(o[1])?n+=i+t+"{"+e.digits+"}":(isNaN(e.digits)||parseInt(e.digits)>0)&&(e.digitsOptional||e.jitMasking?(a=n+i+t+"{0,"+e.digits+"}",e.keepStatic=!0):n+=i+t+"{"+e.digits+"}")}else e.inputmode="numeric";return n+=u(e.suffix,e),n+="[-]",a&&(n=[a+u(e.suffix,e)+"[-]",n]),e.greedy=!1,function(e){void 0===e.parseMinMaxOptions&&(null!==e.min&&(e.min=e.min.toString().replace(new RegExp((0,r.default)(e.groupSeparator),"g"),""),","===e.radixPoint&&(e.min=e.min.replace(e.radixPoint,".")),e.min=isFinite(e.min)?parseFloat(e.min):NaN,isNaN(e.min)&&(e.min=Number.MIN_VALUE)),null!==e.max&&(e.max=e.max.toString().replace(new RegExp((0,r.default)(e.groupSeparator),"g"),""),","===e.radixPoint&&(e.max=e.max.replace(e.radixPoint,".")),e.max=isFinite(e.max)?parseFloat(e.max):NaN,isNaN(e.max)&&(e.max=Number.MAX_VALUE)),e.parseMinMaxOptions="done")}(e),""!==e.radixPoint&&(e.substitutes["."==e.radixPoint?",":"."]=e.radixPoint),n},_mask:function(e){return"("+e.groupSeparator+"999){+|1}"},digits:"*",digitsOptional:!0,enforceDigitsOnBlur:!1,radixPoint:".",positionCaretOnClick:"radixFocus",_radixDance:!0,groupSeparator:"",allowMinus:!0,negationSymbol:{front:"-",back:""},prefix:"",suffix:"",min:null,max:null,SetMaxOnOverflow:!1,step:1,inputType:"text",unmaskAsNumber:!1,roundingFN:Math.round,inputmode:"decimal",shortcuts:{k:"1000",m:"1000000"},placeholder:"0",greedy:!1,rightAlign:!0,insertMode:!0,autoUnmask:!1,skipOptionalPartCharacter:"",usePrototypeDefinitions:!1,stripLeadingZeroes:!0,definitions:{0:{validator:p},1:{validator:p,definitionSymbol:"9"},9:{validator:"[0-9\uff10-\uff19\u0660-\u0669\u06f0-\u06f9]",definitionSymbol:"*"},"+":{validator:function(e,t,i,a,n){return n.allowMinus&&("-"===e||e===n.negationSymbol.front)}},"-":{validator:function(e,t,i,a,n){return n.allowMinus&&e===n.negationSymbol.back}}},preValidation:function(e,t,i,a,n,r,o,s){if(!1!==n.__financeInput&&i===n.radixPoint)return!1;var l=e.indexOf(n.radixPoint),u=t;if(t=function(e,t,i,a,n){return n._radixDance&&n.numericInput&&t!==n.negationSymbol.back&&e<=i&&(i>0||t==n.radixPoint)&&(void 0===a.validPositions[e-1]||a.validPositions[e-1].input!==n.negationSymbol.back)&&(e-=1),e}(t,i,l,r,n),"-"===i||i===n.negationSymbol.front){if(!0!==n.allowMinus)return!1;var c=!1,p=d("+",r),h=d("-",r);return-1!==p&&(c=[p,h]),!1!==c?{remove:c,caret:u-n.negationSymbol.back.length}:{insert:[{pos:f.call(this,"+",r),c:n.negationSymbol.front,fromIsValid:!0},{pos:f.call(this,"-",r),c:n.negationSymbol.back,fromIsValid:void 0}],caret:u+n.negationSymbol.back.length}}if(i===n.groupSeparator)return{caret:u};if(s)return!0;if(-1!==l&&!0===n._radixDance&&!1===a&&i===n.radixPoint&&void 0!==n.digits&&(isNaN(n.digits)||parseInt(n.digits)>0)&&l!==t)return{caret:n._radixDance&&t===l-1?l+1:l};if(!1===n.__financeInput)if(a){if(n.digitsOptional)return{rewritePosition:o.end};if(!n.digitsOptional){if(o.begin>l&&o.end<=l)return i===n.radixPoint?{insert:{pos:l+1,c:"0",fromIsValid:!0},rewritePosition:l}:{rewritePosition:l+1};if(o.begin<l)return{rewritePosition:o.begin-1}}}else if(!n.showMaskOnHover&&!n.showMaskOnFocus&&!n.digitsOptional&&n.digits>0&&""===this.__valueGet.call(this.el))return{rewritePosition:l};return{rewritePosition:t}},postValidation:function(e,t,i,a,n,r,o){if(!1===a)return a;if(o)return!0;if(null!==n.min||null!==n.max){var s=n.onUnMask(e.slice().reverse().join(""),void 0,l.extend({},n,{unmaskAsNumber:!0}));if(null!==n.min&&s<n.min&&(s.toString().length>n.min.toString().length||s<0))return!1;if(null!==n.max&&s>n.max)return!!n.SetMaxOnOverflow&&{refreshFromBuffer:!0,buffer:c(n.max.toString().replace(".",n.radixPoint).split(""),n.digits,n).reverse()}}return a},onUnMask:function(e,t,i){if(""===t&&!0===i.nullable)return t;var a=e.replace(i.prefix,"");return a=(a=a.replace(i.suffix,"")).replace(new RegExp((0,r.default)(i.groupSeparator),"g"),""),""!==i.placeholder.charAt(0)&&(a=a.replace(new RegExp(i.placeholder.charAt(0),"g"),"0")),i.unmaskAsNumber?(""!==i.radixPoint&&-1!==a.indexOf(i.radixPoint)&&(a=a.replace(r.default.call(this,i.radixPoint),".")),a=(a=a.replace(new RegExp("^"+(0,r.default)(i.negationSymbol.front)),"-")).replace(new RegExp((0,r.default)(i.negationSymbol.back)+"$"),""),Number(a)):a},isComplete:function(e,t){var i=(t.numericInput?e.slice().reverse():e).join("");return i=(i=(i=(i=(i=i.replace(new RegExp("^"+(0,r.default)(t.negationSymbol.front)),"-")).replace(new RegExp((0,r.default)(t.negationSymbol.back)+"$"),"")).replace(t.prefix,"")).replace(t.suffix,"")).replace(new RegExp((0,r.default)(t.groupSeparator)+"([0-9]{3})","g"),"$1"),","===t.radixPoint&&(i=i.replace((0,r.default)(t.radixPoint),".")),isFinite(i)},onBeforeMask:function(e,t){var i=t.radixPoint||",";isFinite(t.digits)&&(t.digits=parseInt(t.digits)),"number"!=typeof e&&"number"!==t.inputType||""===i||(e=e.toString().replace(".",i));var a="-"===e.charAt(0)||e.charAt(0)===t.negationSymbol.front,n=e.split(i),o=n[0].replace(/[^\-0-9]/g,""),s=n.length>1?n[1].replace(/[^0-9]/g,""):"",l=n.length>1;e=o+(""!==s?i+s:s);var u=0;if(""!==i&&(u=t.digitsOptional?t.digits<s.length?t.digits:s.length:t.digits,""!==s||!t.digitsOptional)){var f=Math.pow(10,u||1);e=e.replace((0,r.default)(i),"."),isNaN(parseFloat(e))||(e=(t.roundingFN(parseFloat(e)*f)/f).toFixed(u)),e=e.toString().replace(".",i)}if(0===t.digits&&-1!==e.indexOf(i)&&(e=e.substring(0,e.indexOf(i))),null!==t.min||null!==t.max){var d=e.toString().replace(i,".");null!==t.min&&d<t.min?e=t.min.toString().replace(".",i):null!==t.max&&d>t.max&&(e=t.max.toString().replace(".",i))}return a&&"-"!==e.charAt(0)&&(e="-"+e),c(e.toString().split(""),u,t,l).join("")},onBeforeWrite:function(e,t,i,a){function n(e,t){if(!1!==a.__financeInput||t){var i=e.indexOf(a.radixPoint);-1!==i&&e.splice(i,1)}if(""!==a.groupSeparator)for(;-1!==(i=e.indexOf(a.groupSeparator));)e.splice(i,1);return e}var o,s;if(a.stripLeadingZeroes&&(s=function(e,t){var i=new RegExp("(^"+(""!==t.negationSymbol.front?(0,r.default)(t.negationSymbol.front)+"?":"")+(0,r.default)(t.prefix)+")(.*)("+(0,r.default)(t.suffix)+(""!=t.negationSymbol.back?(0,r.default)(t.negationSymbol.back)+"?":"")+"$)").exec(e.slice().reverse().join("")),a=i?i[2]:"",n=!1;return a&&(a=a.split(t.radixPoint.charAt(0))[0],n=new RegExp("^[0"+t.groupSeparator+"]*").exec(a)),!(!n||!(n[0].length>1||n[0].length>0&&n[0].length<a.length))&&n}(t,a)))for(var u=t.join("").lastIndexOf(s[0].split("").reverse().join(""))-(s[0]==s.input?0:1),f=s[0]==s.input?1:0,d=s[0].length-f;d>0;d--)delete this.maskset.validPositions[u+d],delete t[u+d];if(e)switch(e.type){case"blur":case"checkval":if(null!==a.min){var p=a.onUnMask(t.slice().reverse().join(""),void 0,l.extend({},a,{unmaskAsNumber:!0}));if(null!==a.min&&p<a.min)return{refreshFromBuffer:!0,buffer:c(a.min.toString().replace(".",a.radixPoint).split(""),a.digits,a).reverse()}}if(t[t.length-1]===a.negationSymbol.front){var h=new RegExp("(^"+(""!=a.negationSymbol.front?(0,r.default)(a.negationSymbol.front)+"?":"")+(0,r.default)(a.prefix)+")(.*)("+(0,r.default)(a.suffix)+(""!=a.negationSymbol.back?(0,r.default)(a.negationSymbol.back)+"?":"")+"$)").exec(n(t.slice(),!0).reverse().join(""));0==(h?h[2]:"")&&(o={refreshFromBuffer:!0,buffer:[0]})}else if(""!==a.radixPoint){t.indexOf(a.radixPoint)===a.suffix.length&&(o&&o.buffer?o.buffer.splice(0,1+a.suffix.length):(t.splice(0,1+a.suffix.length),o={refreshFromBuffer:!0,buffer:n(t)}))}if(a.enforceDigitsOnBlur){var m=(o=o||{})&&o.buffer||t.slice().reverse();o.refreshFromBuffer=!0,o.buffer=c(m,a.digits,a,!0).reverse()}}return o},onKeyDown:function(e,t,i,a){var r,o,s=l(this),u=String.fromCharCode(e.keyCode).toLowerCase();if((o=a.shortcuts&&a.shortcuts[u])&&o.length>1)return this.inputmask.__valueSet.call(this,parseFloat(this.inputmask.unmaskedvalue())*parseInt(o)),s.trigger("setvalue"),!1;if(e.ctrlKey)switch(e.keyCode){case n.default.UP:return this.inputmask.__valueSet.call(this,parseFloat(this.inputmask.unmaskedvalue())+parseInt(a.step)),s.trigger("setvalue"),!1;case n.default.DOWN:return this.inputmask.__valueSet.call(this,parseFloat(this.inputmask.unmaskedvalue())-parseInt(a.step)),s.trigger("setvalue"),!1}if(!e.shiftKey&&(e.keyCode===n.default.DELETE||e.keyCode===n.default.BACKSPACE||e.keyCode===n.default.BACKSPACE_SAFARI)&&i.begin!==t.length){if(t[e.keyCode===n.default.DELETE?i.begin-1:i.end]===a.negationSymbol.front)return r=t.slice().reverse(),""!==a.negationSymbol.front&&r.shift(),""!==a.negationSymbol.back&&r.pop(),s.trigger("setvalue",[r.join(""),i.begin]),!1;if(!0===a._radixDance){var f=t.indexOf(a.radixPoint);if(a.digitsOptional){if(0===f)return(r=t.slice().reverse()).pop(),s.trigger("setvalue",[r.join(""),i.begin>=r.length?r.length:i.begin]),!1}else if(-1!==f&&(i.begin<f||i.end<f||e.keyCode===n.default.DELETE&&i.begin===f))return i.begin!==i.end||e.keyCode!==n.default.BACKSPACE&&e.keyCode!==n.default.BACKSPACE_SAFARI||i.begin++,(r=t.slice().reverse()).splice(r.length-i.begin,i.begin-i.end+1),r=c(r,a.digits,a).join(""),s.trigger("setvalue",[r,i.begin>=r.length?f+1:i.begin]),!1}}}},currency:{prefix:"",groupSeparator:",",alias:"numeric",digits:2,digitsOptional:!1},decimal:{alias:"numeric"},integer:{alias:"numeric",inputmode:"numeric",digits:0},percentage:{alias:"numeric",min:0,max:100,suffix:" %",digits:0,allowMinus:!1},indianns:{alias:"numeric",_mask:function(e){return"("+e.groupSeparator+"99){*|1}("+e.groupSeparator+"999){1|1}"},groupSeparator:",",radixPoint:".",placeholder:"0",digits:2,digitsOptional:!1}})},9380:function(e,t,i){var a;Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var n=((a=i(8741))&&a.__esModule?a:{default:a}).default?window:{};t.default=n},7760:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.HandleNativePlaceholder=function(e,t){var i=e?e.inputmask:this;if(l.ie){if(e.inputmask._valueGet()!==t&&(e.placeholder!==t||""===e.placeholder)){var a=o.getBuffer.call(i).slice(),n=e.inputmask._valueGet();if(n!==t){var r=o.getLastValidPosition.call(i);-1===r&&n===o.getBufferTemplate.call(i).join("")?a=[]:-1!==r&&f.call(i,a),p(e,a)}}}else e.placeholder!==t&&(e.placeholder=t,""===e.placeholder&&e.removeAttribute("placeholder"))},t.applyInputValue=c,t.checkVal=d,t.clearOptionalTail=f,t.unmaskedvalue=function(e){var t=e?e.inputmask:this,i=t.opts,a=t.maskset;if(e){if(void 0===e.inputmask)return e.value;e.inputmask&&e.inputmask.refreshValue&&c(e,e.inputmask._valueGet(!0))}var n=[],r=a.validPositions;for(var s in r)r[s]&&r[s].match&&(1!=r[s].match.static||Array.isArray(a.metadata)&&!0!==r[s].generatedInput)&&n.push(r[s].input);var l=0===n.length?"":(t.isRTL?n.reverse():n).join("");if("function"==typeof i.onUnMask){var u=(t.isRTL?o.getBuffer.call(t).slice().reverse():o.getBuffer.call(t)).join("");l=i.onUnMask.call(t,u,l,i)}return l},t.writeBuffer=p;var a,n=(a=i(5581))&&a.__esModule?a:{default:a},r=i(4713),o=i(8711),s=i(7215),l=i(9845),u=i(6030);function c(e,t){var i=e?e.inputmask:this,a=i.opts;e.inputmask.refreshValue=!1,"function"==typeof a.onBeforeMask&&(t=a.onBeforeMask.call(i,t,a)||t),d(e,!0,!1,t=t.toString().split("")),i.undoValue=i._valueGet(!0),(a.clearMaskOnLostFocus||a.clearIncomplete)&&e.inputmask._valueGet()===o.getBufferTemplate.call(i).join("")&&-1===o.getLastValidPosition.call(i)&&e.inputmask._valueSet("")}function f(e){e.length=0;for(var t,i=r.getMaskTemplate.call(this,!0,0,!0,void 0,!0);void 0!==(t=i.shift());)e.push(t);return e}function d(e,t,i,a,n){var l=e?e.inputmask:this,c=l.maskset,f=l.opts,d=l.dependencyLib,h=a.slice(),m="",v=-1,g=void 0,k=f.skipOptionalPartCharacter;f.skipOptionalPartCharacter="",o.resetMaskSet.call(l),c.tests={},v=f.radixPoint?o.determineNewCaretPosition.call(l,{begin:0,end:0},!1,!1===f.__financeInput?"radixFocus":void 0).begin:0,c.p=v,l.caretPos={begin:v};var y=[],b=l.caretPos;if(h.forEach((function(e,t){if(void 0!==e){var a=new d.Event("_checkval");a.keyCode=e.toString().charCodeAt(0),m+=e;var n=o.getLastValidPosition.call(l,void 0,!0);!function(e,t){for(var i=r.getMaskTemplate.call(l,!0,0).slice(e,o.seekNext.call(l,e,!1,!1)).join("").replace(/'/g,""),a=i.indexOf(t);a>0&&" "===i[a-1];)a--;var n=0===a&&!o.isMask.call(l,e)&&(r.getTest.call(l,e).match.nativeDef===t.charAt(0)||!0===r.getTest.call(l,e).match.static&&r.getTest.call(l,e).match.nativeDef==="'"+t.charAt(0)||" "===r.getTest.call(l,e).match.nativeDef&&(r.getTest.call(l,e+1).match.nativeDef===t.charAt(0)||!0===r.getTest.call(l,e+1).match.static&&r.getTest.call(l,e+1).match.nativeDef==="'"+t.charAt(0)));if(!n&&a>0&&!o.isMask.call(l,e,!1,!0)){var s=o.seekNext.call(l,e);l.caretPos.begin<s&&(l.caretPos={begin:s})}return n}(v,m)?(g=u.EventHandlers.keypressEvent.call(l,a,!0,!1,i,l.caretPos.begin))&&(v=l.caretPos.begin+1,m=""):g=u.EventHandlers.keypressEvent.call(l,a,!0,!1,i,n+1),g?(void 0!==g.pos&&c.validPositions[g.pos]&&!0===c.validPositions[g.pos].match.static&&void 0===c.validPositions[g.pos].alternation&&(y.push(g.pos),l.isRTL||(g.forwardPosition=g.pos+1)),p.call(l,void 0,o.getBuffer.call(l),g.forwardPosition,a,!1),l.caretPos={begin:g.forwardPosition,end:g.forwardPosition},b=l.caretPos):void 0===c.validPositions[t]&&h[t]===r.getPlaceholder.call(l,t)&&o.isMask.call(l,t,!0)?l.caretPos.begin++:l.caretPos=b}})),y.length>0){var x,P,E=o.seekNext.call(l,-1,void 0,!1);if(!s.isComplete.call(l,o.getBuffer.call(l))&&y.length<=E||s.isComplete.call(l,o.getBuffer.call(l))&&y.length>0&&y.length!==E&&0===y[0])for(var S=E;void 0!==(x=y.shift());){var w=new d.Event("_checkval");if((P=c.validPositions[x]).generatedInput=!0,w.keyCode=P.input.charCodeAt(0),(g=u.EventHandlers.keypressEvent.call(l,w,!0,!1,i,S))&&void 0!==g.pos&&g.pos!==x&&c.validPositions[g.pos]&&!0===c.validPositions[g.pos].match.static)y.push(g.pos);else if(!g)break;S++}}t&&p.call(l,e,o.getBuffer.call(l),g?g.forwardPosition:l.caretPos.begin,n||new d.Event("checkval"),n&&("input"===n.type&&l.undoValue!==o.getBuffer.call(l).join("")||"paste"===n.type)),f.skipOptionalPartCharacter=k}function p(e,t,i,a,r){var l=e?e.inputmask:this,u=l.opts,c=l.dependencyLib;if(a&&"function"==typeof u.onBeforeWrite){var f=u.onBeforeWrite.call(l,a,t,i,u);if(f){if(f.refreshFromBuffer){var d=f.refreshFromBuffer;s.refreshFromBuffer.call(l,!0===d?d:d.start,d.end,f.buffer||t),t=o.getBuffer.call(l,!0)}void 0!==i&&(i=void 0!==f.caret?f.caret:i)}}if(void 0!==e&&(e.inputmask._valueSet(t.join("")),void 0===i||void 0!==a&&"blur"===a.type||o.caret.call(l,e,i,void 0,void 0,void 0!==a&&"keydown"===a.type&&(a.keyCode===n.default.DELETE||a.keyCode===n.default.BACKSPACE)),!0===r)){var p=c(e),h=e.inputmask._valueGet();e.inputmask.skipInputEvent=!0,p.trigger("input"),setTimeout((function(){h===o.getBufferTemplate.call(l).join("")?p.trigger("cleared"):!0===s.isComplete.call(l,t)&&p.trigger("complete")}),0)}}},2394:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0,i(7149),i(3194);var a=i(157),n=v(i(3287)),r=v(i(9380)),o=i(2391),s=i(4713),l=i(8711),u=i(7215),c=i(7760),f=i(9716),d=v(i(7392)),p=v(i(3976)),h=v(i(8741));function m(e){return m="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},m(e)}function v(e){return e&&e.__esModule?e:{default:e}}var g=r.default.document,k="_inputmask_opts";function y(e,t,i){if(h.default){if(!(this instanceof y))return new y(e,t,i);this.dependencyLib=n.default,this.el=void 0,this.events={},this.maskset=void 0,!0!==i&&("[object Object]"===Object.prototype.toString.call(e)?t=e:(t=t||{},e&&(t.alias=e)),this.opts=n.default.extend(!0,{},this.defaults,t),this.noMasksCache=t&&void 0!==t.definitions,this.userOptions=t||{},b(this.opts.alias,t,this.opts)),this.refreshValue=!1,this.undoValue=void 0,this.$el=void 0,this.skipKeyPressEvent=!1,this.skipInputEvent=!1,this.validationEvent=!1,this.ignorable=!1,this.maxLength,this.mouseEnter=!1,this.originalPlaceholder=void 0,this.isComposing=!1}}function b(e,t,i){var a=y.prototype.aliases[e];return a?(a.alias&&b(a.alias,void 0,i),n.default.extend(!0,i,a),n.default.extend(!0,i,t),!0):(null===i.mask&&(i.mask=e),!1)}y.prototype={dataAttribute:"data-inputmask",defaults:p.default,definitions:d.default,aliases:{},masksCache:{},get isRTL(){return this.opts.isRTL||this.opts.numericInput},mask:function(e){var t=this;return"string"==typeof e&&(e=g.getElementById(e)||g.querySelectorAll(e)),(e=e.nodeName?[e]:Array.isArray(e)?e:Array.from(e)).forEach((function(e,i){var s=n.default.extend(!0,{},t.opts);if(function(e,t,i,a){function o(t,n){var o=""===a?t:a+"-"+t;null!==(n=void 0!==n?n:e.getAttribute(o))&&("string"==typeof n&&(0===t.indexOf("on")?n=r.default[n]:"false"===n?n=!1:"true"===n&&(n=!0)),i[t]=n)}if(!0===t.importDataAttributes){var s,l,u,c,f=e.getAttribute(a);if(f&&""!==f&&(f=f.replace(/'/g,'"'),l=JSON.parse("{"+f+"}")),l)for(c in u=void 0,l)if("alias"===c.toLowerCase()){u=l[c];break}for(s in o("alias",u),i.alias&&b(i.alias,i,t),t){if(l)for(c in u=void 0,l)if(c.toLowerCase()===s.toLowerCase()){u=l[c];break}o(s,u)}}n.default.extend(!0,t,i),("rtl"===e.dir||t.rightAlign)&&(e.style.textAlign="right");("rtl"===e.dir||t.numericInput)&&(e.dir="ltr",e.removeAttribute("dir"),t.isRTL=!0);return Object.keys(i).length}(e,s,n.default.extend(!0,{},t.userOptions),t.dataAttribute)){var l=(0,o.generateMaskSet)(s,t.noMasksCache);void 0!==l&&(void 0!==e.inputmask&&(e.inputmask.opts.autoUnmask=!0,e.inputmask.remove()),e.inputmask=new y(void 0,void 0,!0),e.inputmask.opts=s,e.inputmask.noMasksCache=t.noMasksCache,e.inputmask.userOptions=n.default.extend(!0,{},t.userOptions),e.inputmask.el=e,e.inputmask.$el=(0,n.default)(e),e.inputmask.maskset=l,n.default.data(e,k,t.userOptions),a.mask.call(e.inputmask))}})),e&&e[0]&&e[0].inputmask||this},option:function(e,t){return"string"==typeof e?this.opts[e]:"object"===m(e)?(n.default.extend(this.userOptions,e),this.el&&!0!==t&&this.mask(this.el),this):void 0},unmaskedvalue:function(e){if(this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache),void 0===this.el||void 0!==e){var t=("function"==typeof this.opts.onBeforeMask&&this.opts.onBeforeMask.call(this,e,this.opts)||e).split("");c.checkVal.call(this,void 0,!1,!1,t),"function"==typeof this.opts.onBeforeWrite&&this.opts.onBeforeWrite.call(this,void 0,l.getBuffer.call(this),0,this.opts)}return c.unmaskedvalue.call(this,this.el)},remove:function(){if(this.el){n.default.data(this.el,k,null);var e=this.opts.autoUnmask?(0,c.unmaskedvalue)(this.el):this._valueGet(this.opts.autoUnmask);e!==l.getBufferTemplate.call(this).join("")?this._valueSet(e,this.opts.autoUnmask):this._valueSet(""),f.EventRuler.off(this.el),Object.getOwnPropertyDescriptor&&Object.getPrototypeOf?Object.getOwnPropertyDescriptor(Object.getPrototypeOf(this.el),"value")&&this.__valueGet&&Object.defineProperty(this.el,"value",{get:this.__valueGet,set:this.__valueSet,configurable:!0}):g.__lookupGetter__&&this.el.__lookupGetter__("value")&&this.__valueGet&&(this.el.__defineGetter__("value",this.__valueGet),this.el.__defineSetter__("value",this.__valueSet)),this.el.inputmask=void 0}return this.el},getemptymask:function(){return this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache),l.getBufferTemplate.call(this).join("")},hasMaskedValue:function(){return!this.opts.autoUnmask},isComplete:function(){return this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache),u.isComplete.call(this,l.getBuffer.call(this))},getmetadata:function(){if(this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache),Array.isArray(this.maskset.metadata)){var e=s.getMaskTemplate.call(this,!0,0,!1).join("");return this.maskset.metadata.forEach((function(t){return t.mask!==e||(e=t,!1)})),e}return this.maskset.metadata},isValid:function(e){if(this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache),e){var t=("function"==typeof this.opts.onBeforeMask&&this.opts.onBeforeMask.call(this,e,this.opts)||e).split("");c.checkVal.call(this,void 0,!0,!1,t)}else e=this.isRTL?l.getBuffer.call(this).slice().reverse().join(""):l.getBuffer.call(this).join("");for(var i=l.getBuffer.call(this),a=l.determineLastRequiredPosition.call(this),n=i.length-1;n>a&&!l.isMask.call(this,n);n--);return i.splice(a,n+1-a),u.isComplete.call(this,i)&&e===(this.isRTL?l.getBuffer.call(this).slice().reverse().join(""):l.getBuffer.call(this).join(""))},format:function(e,t){this.maskset=this.maskset||(0,o.generateMaskSet)(this.opts,this.noMasksCache);var i=("function"==typeof this.opts.onBeforeMask&&this.opts.onBeforeMask.call(this,e,this.opts)||e).split("");c.checkVal.call(this,void 0,!0,!1,i);var a=this.isRTL?l.getBuffer.call(this).slice().reverse().join(""):l.getBuffer.call(this).join("");return t?{value:a,metadata:this.getmetadata()}:a},setValue:function(e){this.el&&(0,n.default)(this.el).trigger("setvalue",[e])},analyseMask:o.analyseMask},y.extendDefaults=function(e){n.default.extend(!0,y.prototype.defaults,e)},y.extendDefinitions=function(e){n.default.extend(!0,y.prototype.definitions,e)},y.extendAliases=function(e){n.default.extend(!0,y.prototype.aliases,e)},y.format=function(e,t,i){return y(t).format(e,i)},y.unmask=function(e,t){return y(t).unmaskedvalue(e)},y.isValid=function(e,t){return y(t).isValid(e)},y.remove=function(e){"string"==typeof e&&(e=g.getElementById(e)||g.querySelectorAll(e)),(e=e.nodeName?[e]:e).forEach((function(e){e.inputmask&&e.inputmask.remove()}))},y.setValue=function(e,t){"string"==typeof e&&(e=g.getElementById(e)||g.querySelectorAll(e)),(e=e.nodeName?[e]:e).forEach((function(e){e.inputmask?e.inputmask.setValue(t):(0,n.default)(e).trigger("setvalue",[t])}))},y.dependencyLib=n.default,r.default.Inputmask=y;var x=y;t.default=x},5296:function(e,t,i){function a(e){return a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},a(e)}var n=h(i(9380)),r=h(i(2394)),o=h(i(8741));function s(e,t){for(var i=0;i<t.length;i++){var a=t[i];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function l(e,t){if(t&&("object"===a(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}function u(e){var t="function"==typeof Map?new Map:void 0;return u=function(e){if(null===e||(i=e,-1===Function.toString.call(i).indexOf("[native code]")))return e;var i;if("function"!=typeof e)throw new TypeError("Super expression must either be null or a function");if(void 0!==t){if(t.has(e))return t.get(e);t.set(e,a)}function a(){return c(e,arguments,p(this).constructor)}return a.prototype=Object.create(e.prototype,{constructor:{value:a,enumerable:!1,writable:!0,configurable:!0}}),d(a,e)},u(e)}function c(e,t,i){return c=f()?Reflect.construct:function(e,t,i){var a=[null];a.push.apply(a,t);var n=new(Function.bind.apply(e,a));return i&&d(n,i.prototype),n},c.apply(null,arguments)}function f(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}function d(e,t){return d=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e},d(e,t)}function p(e){return p=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)},p(e)}function h(e){return e&&e.__esModule?e:{default:e}}var m=n.default.document;if(o.default&&m&&m.head&&m.head.attachShadow&&n.default.customElements&&void 0===n.default.customElements.get("input-mask")){var v=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");Object.defineProperty(e,"prototype",{value:Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),writable:!1}),t&&d(e,t)}(c,e);var t,i,a,n,o,u=(t=c,i=f(),function(){var e,a=p(t);if(i){var n=p(this).constructor;e=Reflect.construct(a,arguments,n)}else e=a.apply(this,arguments);return l(this,e)});function c(){var e;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,c);var t=(e=u.call(this)).getAttributeNames(),i=e.attachShadow({mode:"closed"}),a=m.createElement("input");for(var n in a.type="text",i.appendChild(a),t)Object.prototype.hasOwnProperty.call(t,n)&&a.setAttribute(t[n],e.getAttribute(t[n]));var o=new r.default;return o.dataAttribute="",o.mask(a),a.inputmask.shadowRoot=i,e}return a=c,n&&s(a.prototype,n),o&&s(a,o),Object.defineProperty(a,"prototype",{writable:!1}),a}(u(HTMLElement));n.default.customElements.define("input-mask",v)}},443:function(e,t,i){var a=o(i(2047)),n=o(i(2394));function r(e){return r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},r(e)}function o(e){return e&&e.__esModule?e:{default:e}}void 0===a.default.fn.inputmask&&(a.default.fn.inputmask=function(e,t){var i,o=this[0];if(void 0===t&&(t={}),"string"==typeof e)switch(e){case"unmaskedvalue":return o&&o.inputmask?o.inputmask.unmaskedvalue():(0,a.default)(o).val();case"remove":return this.each((function(){this.inputmask&&this.inputmask.remove()}));case"getemptymask":return o&&o.inputmask?o.inputmask.getemptymask():"";case"hasMaskedValue":return!(!o||!o.inputmask)&&o.inputmask.hasMaskedValue();case"isComplete":return!o||!o.inputmask||o.inputmask.isComplete();case"getmetadata":return o&&o.inputmask?o.inputmask.getmetadata():void 0;case"setvalue":n.default.setValue(o,t);break;case"option":if("string"!=typeof t)return this.each((function(){if(void 0!==this.inputmask)return this.inputmask.option(t)}));if(o&&void 0!==o.inputmask)return o.inputmask.option(t);break;default:return t.alias=e,i=new n.default(t),this.each((function(){i.mask(this)}))}else{if(Array.isArray(e))return t.alias=e,i=new n.default(t),this.each((function(){i.mask(this)}));if("object"==r(e))return i=new n.default(e),void 0===e.mask&&void 0===e.alias?this.each((function(){if(void 0!==this.inputmask)return this.inputmask.option(e);i.mask(this)})):this.each((function(){i.mask(this)}));if(void 0===e)return this.each((function(){(i=new n.default(t)).mask(this)}))}})},2391:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.analyseMask=function(e,t,i){var a,o,s,l,u,c,f=/(?:[?*+]|\{[0-9+*]+(?:,[0-9+*]*)?(?:\|[0-9+*]*)?\})|[^.?*+^${[]()|\\]+|./g,d=/\[\^?]?(?:[^\\\]]+|\\[\S\s]?)*]?|\\(?:0(?:[0-3][0-7]{0,2}|[4-7][0-7]?)?|[1-9][0-9]*|x[0-9A-Fa-f]{2}|u[0-9A-Fa-f]{4}|c[A-Za-z]|[\S\s]?)|\((?:\?[:=!]?)?|(?:[?*+]|\{[0-9]+(?:,[0-9]*)?\})\??|[^.?*+^${[()|\\]+|./g,p=!1,h=new n.default,m=[],v=[],g=!1;function k(e,a,n){n=void 0!==n?n:e.matches.length;var o=e.matches[n-1];if(t)0===a.indexOf("[")||p&&/\\d|\\s|\\w/i.test(a)||"."===a?e.matches.splice(n++,0,{fn:new RegExp(a,i.casing?"i":""),static:!1,optionality:!1,newBlockMarker:void 0===o?"master":o.def!==a,casing:null,def:a,placeholder:void 0,nativeDef:a}):(p&&(a=a[a.length-1]),a.split("").forEach((function(t,a){o=e.matches[n-1],e.matches.splice(n++,0,{fn:/[a-z]/i.test(i.staticDefinitionSymbol||t)?new RegExp("["+(i.staticDefinitionSymbol||t)+"]",i.casing?"i":""):null,static:!0,optionality:!1,newBlockMarker:void 0===o?"master":o.def!==t&&!0!==o.static,casing:null,def:i.staticDefinitionSymbol||t,placeholder:void 0!==i.staticDefinitionSymbol?t:void 0,nativeDef:(p?"'":"")+t})}))),p=!1;else{var s=i.definitions&&i.definitions[a]||i.usePrototypeDefinitions&&r.default.prototype.definitions[a];s&&!p?e.matches.splice(n++,0,{fn:s.validator?"string"==typeof s.validator?new RegExp(s.validator,i.casing?"i":""):new function(){this.test=s.validator}:new RegExp("."),static:s.static||!1,optionality:s.optional||!1,newBlockMarker:void 0===o||s.optional?"master":o.def!==(s.definitionSymbol||a),casing:s.casing,def:s.definitionSymbol||a,placeholder:s.placeholder,nativeDef:a,generated:s.generated}):(e.matches.splice(n++,0,{fn:/[a-z]/i.test(i.staticDefinitionSymbol||a)?new RegExp("["+(i.staticDefinitionSymbol||a)+"]",i.casing?"i":""):null,static:!0,optionality:!1,newBlockMarker:void 0===o?"master":o.def!==a&&!0!==o.static,casing:null,def:i.staticDefinitionSymbol||a,placeholder:void 0!==i.staticDefinitionSymbol?a:void 0,nativeDef:(p?"'":"")+a}),p=!1)}}function y(){if(m.length>0){if(k(l=m[m.length-1],o),l.isAlternator){u=m.pop();for(var e=0;e<u.matches.length;e++)u.matches[e].isGroup&&(u.matches[e].isGroup=!1);m.length>0?(l=m[m.length-1]).matches.push(u):h.matches.push(u)}}else k(h,o)}function b(e){var t=new n.default(!0);return t.openGroup=!1,t.matches=e,t}function x(){if((s=m.pop()).openGroup=!1,void 0!==s)if(m.length>0){if((l=m[m.length-1]).matches.push(s),l.isAlternator){for(var e=(u=m.pop()).matches[0].matches?u.matches[0].matches.length:1,t=0;t<u.matches.length;t++)u.matches[t].isGroup=!1,u.matches[t].alternatorGroup=!1,null===i.keepStatic&&e<(u.matches[t].matches?u.matches[t].matches.length:1)&&(i.keepStatic=!0),e=u.matches[t].matches?u.matches[t].matches.length:1;m.length>0?(l=m[m.length-1]).matches.push(u):h.matches.push(u)}}else h.matches.push(s);else y()}function P(e){var t=e.pop();return t.isQuantifier&&(t=b([e.pop(),t])),t}t&&(i.optionalmarker[0]=void 0,i.optionalmarker[1]=void 0);for(;a=t?d.exec(e):f.exec(e);){if(o=a[0],t){switch(o.charAt(0)){case"?":o="{0,1}";break;case"+":case"*":o="{"+o+"}";break;case"|":if(0===m.length){var E=b(h.matches);E.openGroup=!0,m.push(E),h.matches=[],g=!0}}if("\\d"===o)o="[0-9]"}if(p)y();else switch(o.charAt(0)){case"$":case"^":t||y();break;case i.escapeChar:p=!0,t&&y();break;case i.optionalmarker[1]:case i.groupmarker[1]:x();break;case i.optionalmarker[0]:m.push(new n.default(!1,!0));break;case i.groupmarker[0]:m.push(new n.default(!0));break;case i.quantifiermarker[0]:var S=new n.default(!1,!1,!0),w=(o=o.replace(/[{}?]/g,"")).split("|"),_=w[0].split(","),M=isNaN(_[0])?_[0]:parseInt(_[0]),O=1===_.length?M:isNaN(_[1])?_[1]:parseInt(_[1]),T=isNaN(w[1])?w[1]:parseInt(w[1]);"*"!==M&&"+"!==M||(M="*"===O?0:1),S.quantifier={min:M,max:O,jit:T};var A=m.length>0?m[m.length-1].matches:h.matches;if((a=A.pop()).isAlternator){A.push(a),A=a.matches;var C=new n.default(!0),D=A.pop();A.push(C),A=C.matches,a=D}a.isGroup||(a=b([a])),A.push(a),A.push(S);break;case i.alternatormarker:if(m.length>0){var j=(l=m[m.length-1]).matches[l.matches.length-1];c=l.openGroup&&(void 0===j.matches||!1===j.isGroup&&!1===j.isAlternator)?m.pop():P(l.matches)}else c=P(h.matches);if(c.isAlternator)m.push(c);else if(c.alternatorGroup?(u=m.pop(),c.alternatorGroup=!1):u=new n.default(!1,!1,!1,!0),u.matches.push(c),m.push(u),c.openGroup){c.openGroup=!1;var B=new n.default(!0);B.alternatorGroup=!0,m.push(B)}break;default:y()}}g&&x();for(;m.length>0;)s=m.pop(),h.matches.push(s);h.matches.length>0&&(!function e(a){a&&a.matches&&a.matches.forEach((function(n,r){var o=a.matches[r+1];(void 0===o||void 0===o.matches||!1===o.isQuantifier)&&n&&n.isGroup&&(n.isGroup=!1,t||(k(n,i.groupmarker[0],0),!0!==n.openGroup&&k(n,i.groupmarker[1]))),e(n)}))}(h),v.push(h));(i.numericInput||i.isRTL)&&function e(t){for(var a in t.matches=t.matches.reverse(),t.matches)if(Object.prototype.hasOwnProperty.call(t.matches,a)){var n=parseInt(a);if(t.matches[a].isQuantifier&&t.matches[n+1]&&t.matches[n+1].isGroup){var r=t.matches[a];t.matches.splice(a,1),t.matches.splice(n+1,0,r)}void 0!==t.matches[a].matches?t.matches[a]=e(t.matches[a]):t.matches[a]=((o=t.matches[a])===i.optionalmarker[0]?o=i.optionalmarker[1]:o===i.optionalmarker[1]?o=i.optionalmarker[0]:o===i.groupmarker[0]?o=i.groupmarker[1]:o===i.groupmarker[1]&&(o=i.groupmarker[0]),o)}var o;return t}(v[0]);return v},t.generateMaskSet=function(e,t){var i;function n(e,i,n){var o,s,l=!1;if(null!==e&&""!==e||((l=null!==n.regex)?e=(e=n.regex).replace(/^(\^)(.*)(\$)$/,"$2"):(l=!0,e=".*")),1===e.length&&!1===n.greedy&&0!==n.repeat&&(n.placeholder=""),n.repeat>0||"*"===n.repeat||"+"===n.repeat){var u="*"===n.repeat?0:"+"===n.repeat?1:n.repeat;e=n.groupmarker[0]+e+n.groupmarker[1]+n.quantifiermarker[0]+u+","+n.repeat+n.quantifiermarker[1]}return s=l?"regex_"+n.regex:n.numericInput?e.split("").reverse().join(""):e,null!==n.keepStatic&&(s="ks_"+n.keepStatic+s),void 0===r.default.prototype.masksCache[s]||!0===t?(o={mask:e,maskToken:r.default.prototype.analyseMask(e,l,n),validPositions:{},_buffer:void 0,buffer:void 0,tests:{},excludes:{},metadata:i,maskLength:void 0,jitOffset:{}},!0!==t&&(r.default.prototype.masksCache[s]=o,o=a.default.extend(!0,{},r.default.prototype.masksCache[s]))):o=a.default.extend(!0,{},r.default.prototype.masksCache[s]),o}"function"==typeof e.mask&&(e.mask=e.mask(e));if(Array.isArray(e.mask)){if(e.mask.length>1){null===e.keepStatic&&(e.keepStatic=!0);var o=e.groupmarker[0];return(e.isRTL?e.mask.reverse():e.mask).forEach((function(t){o.length>1&&(o+=e.alternatormarker),void 0!==t.mask&&"function"!=typeof t.mask?o+=t.mask:o+=t})),n(o+=e.groupmarker[1],e.mask,e)}e.mask=e.mask.pop()}i=e.mask&&void 0!==e.mask.mask&&"function"!=typeof e.mask.mask?n(e.mask.mask,e.mask,e):n(e.mask,e.mask,e);null===e.keepStatic&&(e.keepStatic=!1);return i};var a=o(i(3287)),n=o(i(9695)),r=o(i(2394));function o(e){return e&&e.__esModule?e:{default:e}}},157:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.mask=function(){var e=this,t=this.opts,i=this.el,a=this.dependencyLib;s.EventRuler.off(i);var f=function(t,i){"textarea"!==t.tagName.toLowerCase()&&i.ignorables.push(n.default.ENTER);var l=t.getAttribute("type"),u="input"===t.tagName.toLowerCase()&&i.supportsInputType.includes(l)||t.isContentEditable||"textarea"===t.tagName.toLowerCase();if(!u)if("input"===t.tagName.toLowerCase()){var c=document.createElement("input");c.setAttribute("type",l),u="text"===c.type,c=null}else u="partial";return!1!==u?function(t){var n,l;function u(){return this.inputmask?this.inputmask.opts.autoUnmask?this.inputmask.unmaskedvalue():-1!==r.getLastValidPosition.call(e)||!0!==i.nullable?(this.inputmask.shadowRoot||this.ownerDocument).activeElement===this&&i.clearMaskOnLostFocus?(e.isRTL?o.clearOptionalTail.call(e,r.getBuffer.call(e).slice()).reverse():o.clearOptionalTail.call(e,r.getBuffer.call(e).slice())).join(""):n.call(this):"":n.call(this)}function c(e){l.call(this,e),this.inputmask&&(0,o.applyInputValue)(this,e)}if(!t.inputmask.__valueGet){if(!0!==i.noValuePatching){if(Object.getOwnPropertyDescriptor){var f=Object.getPrototypeOf?Object.getOwnPropertyDescriptor(Object.getPrototypeOf(t),"value"):void 0;f&&f.get&&f.set?(n=f.get,l=f.set,Object.defineProperty(t,"value",{get:u,set:c,configurable:!0})):"input"!==t.tagName.toLowerCase()&&(n=function(){return this.textContent},l=function(e){this.textContent=e},Object.defineProperty(t,"value",{get:u,set:c,configurable:!0}))}else document.__lookupGetter__&&t.__lookupGetter__("value")&&(n=t.__lookupGetter__("value"),l=t.__lookupSetter__("value"),t.__defineGetter__("value",u),t.__defineSetter__("value",c));t.inputmask.__valueGet=n,t.inputmask.__valueSet=l}t.inputmask._valueGet=function(t){return e.isRTL&&!0!==t?n.call(this.el).split("").reverse().join(""):n.call(this.el)},t.inputmask._valueSet=function(t,i){l.call(this.el,null==t?"":!0!==i&&e.isRTL?t.split("").reverse().join(""):t)},void 0===n&&(n=function(){return this.value},l=function(e){this.value=e},function(t){if(a.valHooks&&(void 0===a.valHooks[t]||!0!==a.valHooks[t].inputmaskpatch)){var n=a.valHooks[t]&&a.valHooks[t].get?a.valHooks[t].get:function(e){return e.value},s=a.valHooks[t]&&a.valHooks[t].set?a.valHooks[t].set:function(e,t){return e.value=t,e};a.valHooks[t]={get:function(t){if(t.inputmask){if(t.inputmask.opts.autoUnmask)return t.inputmask.unmaskedvalue();var a=n(t);return-1!==r.getLastValidPosition.call(e,void 0,void 0,t.inputmask.maskset.validPositions)||!0!==i.nullable?a:""}return n(t)},set:function(e,t){var i=s(e,t);return e.inputmask&&(0,o.applyInputValue)(e,t),i},inputmaskpatch:!0}}}(t.type),function(t){s.EventRuler.on(t,"mouseenter",(function(){var t=this.inputmask._valueGet(!0);t!==(e.isRTL?r.getBuffer.call(e).reverse():r.getBuffer.call(e)).join("")&&(0,o.applyInputValue)(this,t)}))}(t))}}(t):t.inputmask=void 0,u}(i,t);if(!1!==f){e.originalPlaceholder=i.placeholder,e.maxLength=void 0!==i?i.maxLength:void 0,-1===e.maxLength&&(e.maxLength=void 0),"inputMode"in i&&null===i.getAttribute("inputmode")&&(i.inputMode=t.inputmode,i.setAttribute("inputmode",t.inputmode)),!0===f&&(t.showMaskOnFocus=t.showMaskOnFocus&&-1===["cc-number","cc-exp"].indexOf(i.autocomplete),l.iphone&&(t.insertModeVisual=!1),s.EventRuler.on(i,"submit",c.EventHandlers.submitEvent),s.EventRuler.on(i,"reset",c.EventHandlers.resetEvent),s.EventRuler.on(i,"blur",c.EventHandlers.blurEvent),s.EventRuler.on(i,"focus",c.EventHandlers.focusEvent),s.EventRuler.on(i,"invalid",c.EventHandlers.invalidEvent),s.EventRuler.on(i,"click",c.EventHandlers.clickEvent),s.EventRuler.on(i,"mouseleave",c.EventHandlers.mouseleaveEvent),s.EventRuler.on(i,"mouseenter",c.EventHandlers.mouseenterEvent),s.EventRuler.on(i,"paste",c.EventHandlers.pasteEvent),s.EventRuler.on(i,"cut",c.EventHandlers.cutEvent),s.EventRuler.on(i,"complete",t.oncomplete),s.EventRuler.on(i,"incomplete",t.onincomplete),s.EventRuler.on(i,"cleared",t.oncleared),!0!==t.inputEventOnly&&(s.EventRuler.on(i,"keydown",c.EventHandlers.keydownEvent),s.EventRuler.on(i,"keypress",c.EventHandlers.keypressEvent),s.EventRuler.on(i,"keyup",c.EventHandlers.keyupEvent)),(l.mobile||t.inputEventOnly)&&i.removeAttribute("maxLength"),s.EventRuler.on(i,"input",c.EventHandlers.inputFallBackEvent),s.EventRuler.on(i,"compositionend",c.EventHandlers.compositionendEvent)),s.EventRuler.on(i,"setvalue",c.EventHandlers.setValueEvent),r.getBufferTemplate.call(e).join(""),e.undoValue=e._valueGet(!0);var d=(i.inputmask.shadowRoot||i.ownerDocument).activeElement;if(""!==i.inputmask._valueGet(!0)||!1===t.clearMaskOnLostFocus||d===i){(0,o.applyInputValue)(i,i.inputmask._valueGet(!0),t);var p=r.getBuffer.call(e).slice();!1===u.isComplete.call(e,p)&&t.clearIncomplete&&r.resetMaskSet.call(e),t.clearMaskOnLostFocus&&d!==i&&(-1===r.getLastValidPosition.call(e)?p=[]:o.clearOptionalTail.call(e,p)),(!1===t.clearMaskOnLostFocus||t.showMaskOnFocus&&d===i||""!==i.inputmask._valueGet(!0))&&(0,o.writeBuffer)(i,p),d===i&&r.caret.call(e,i,r.seekNext.call(e,r.getLastValidPosition.call(e)))}}};var a,n=(a=i(5581))&&a.__esModule?a:{default:a},r=i(8711),o=i(7760),s=i(9716),l=i(9845),u=i(7215),c=i(6030)},9695:function(e,t){Object.defineProperty(t,"__esModule",{value:!0}),t.default=function(e,t,i,a){this.matches=[],this.openGroup=e||!1,this.alternatorGroup=!1,this.isGroup=e||!1,this.isOptional=t||!1,this.isQuantifier=i||!1,this.isAlternator=a||!1,this.quantifier={min:1,max:1}}},3194:function(){Array.prototype.includes||Object.defineProperty(Array.prototype,"includes",{value:function(e,t){if(null==this)throw new TypeError('"this" is null or not defined');var i=Object(this),a=i.length>>>0;if(0===a)return!1;for(var n=0|t,r=Math.max(n>=0?n:a-Math.abs(n),0);r<a;){if(i[r]===e)return!0;r++}return!1}})},7149:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}"function"!=typeof Object.getPrototypeOf&&(Object.getPrototypeOf="object"===e("test".__proto__)?function(e){return e.__proto__}:function(e){return e.constructor.prototype})},8711:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.caret=function(e,t,i,a,n){var r,o=this,s=this.opts;if(void 0===t)return"selectionStart"in e&&"selectionEnd"in e?(t=e.selectionStart,i=e.selectionEnd):window.getSelection?(r=window.getSelection().getRangeAt(0)).commonAncestorContainer.parentNode!==e&&r.commonAncestorContainer!==e||(t=r.startOffset,i=r.endOffset):document.selection&&document.selection.createRange&&(r=document.selection.createRange(),t=0-r.duplicate().moveStart("character",-e.inputmask._valueGet().length),i=t+r.text.length),{begin:a?t:u.call(o,t),end:a?i:u.call(o,i)};if(Array.isArray(t)&&(i=o.isRTL?t[0]:t[1],t=o.isRTL?t[1]:t[0]),void 0!==t.begin&&(i=o.isRTL?t.begin:t.end,t=o.isRTL?t.end:t.begin),"number"==typeof t){t=a?t:u.call(o,t),i="number"==typeof(i=a?i:u.call(o,i))?i:t;var l=parseInt(((e.ownerDocument.defaultView||window).getComputedStyle?(e.ownerDocument.defaultView||window).getComputedStyle(e,null):e.currentStyle).fontSize)*i;if(e.scrollLeft=l>e.scrollWidth?l:0,e.inputmask.caretPos={begin:t,end:i},s.insertModeVisual&&!1===s.insertMode&&t===i&&(n||i++),e===(e.inputmask.shadowRoot||e.ownerDocument).activeElement)if("setSelectionRange"in e)e.setSelectionRange(t,i);else if(window.getSelection){if(r=document.createRange(),void 0===e.firstChild||null===e.firstChild){var c=document.createTextNode("");e.appendChild(c)}r.setStart(e.firstChild,t<e.inputmask._valueGet().length?t:e.inputmask._valueGet().length),r.setEnd(e.firstChild,i<e.inputmask._valueGet().length?i:e.inputmask._valueGet().length),r.collapse(!0);var f=window.getSelection();f.removeAllRanges(),f.addRange(r)}else e.createTextRange&&((r=e.createTextRange()).collapse(!0),r.moveEnd("character",i),r.moveStart("character",t),r.select())}},t.determineLastRequiredPosition=function(e){var t,i,r=this,s=this.maskset,l=this.dependencyLib,u=a.getMaskTemplate.call(r,!0,o.call(r),!0,!0),c=u.length,f=o.call(r),d={},p=s.validPositions[f],h=void 0!==p?p.locator.slice():void 0;for(t=f+1;t<u.length;t++)i=a.getTestTemplate.call(r,t,h,t-1),h=i.locator.slice(),d[t]=l.extend(!0,{},i);var m=p&&void 0!==p.alternation?p.locator[p.alternation]:void 0;for(t=c-1;t>f&&(((i=d[t]).match.optionality||i.match.optionalQuantifier&&i.match.newBlockMarker||m&&(m!==d[t].locator[p.alternation]&&1!=i.match.static||!0===i.match.static&&i.locator[p.alternation]&&n.checkAlternationMatch.call(r,i.locator[p.alternation].toString().split(","),m.toString().split(","))&&""!==a.getTests.call(r,t)[0].def))&&u[t]===a.getPlaceholder.call(r,t,i.match));t--)c--;return e?{l:c,def:d[c]?d[c].match:void 0}:c},t.determineNewCaretPosition=function(e,t,i){var n=this,u=this.maskset,c=this.opts;t&&(n.isRTL?e.end=e.begin:e.begin=e.end);if(e.begin===e.end){switch(i=i||c.positionCaretOnClick){case"none":break;case"select":e={begin:0,end:r.call(n).length};break;case"ignore":e.end=e.begin=l.call(n,o.call(n));break;case"radixFocus":if(function(e){if(""!==c.radixPoint&&0!==c.digits){var t=u.validPositions;if(void 0===t[e]||t[e].input===a.getPlaceholder.call(n,e)){if(e<l.call(n,-1))return!0;var i=r.call(n).indexOf(c.radixPoint);if(-1!==i){for(var o in t)if(t[o]&&i<o&&t[o].input!==a.getPlaceholder.call(n,o))return!1;return!0}}}return!1}(e.begin)){var f=r.call(n).join("").indexOf(c.radixPoint);e.end=e.begin=c.numericInput?l.call(n,f):f;break}default:var d=e.begin,p=o.call(n,d,!0),h=l.call(n,-1!==p||s.call(n,0)?p:-1);if(d<=h)e.end=e.begin=s.call(n,d,!1,!0)?d:l.call(n,d);else{var m=u.validPositions[p],v=a.getTestTemplate.call(n,h,m?m.match.locator:void 0,m),g=a.getPlaceholder.call(n,h,v.match);if(""!==g&&r.call(n)[h]!==g&&!0!==v.match.optionalQuantifier&&!0!==v.match.newBlockMarker||!s.call(n,h,c.keepStatic,!0)&&v.match.def===g){var k=l.call(n,h);(d>=k||d===h)&&(h=k)}e.end=e.begin=h}}return e}},t.getBuffer=r,t.getBufferTemplate=function(){var e=this.maskset;void 0===e._buffer&&(e._buffer=a.getMaskTemplate.call(this,!1,1),void 0===e.buffer&&(e.buffer=e._buffer.slice()));return e._buffer},t.getLastValidPosition=o,t.isMask=s,t.resetMaskSet=function(e){var t=this.maskset;t.buffer=void 0,!0!==e&&(t.validPositions={},t.p=0)},t.seekNext=l,t.seekPrevious=function(e,t){var i=this,n=e-1;if(e<=0)return 0;for(;n>0&&(!0===t&&(!0!==a.getTest.call(i,n).match.newBlockMarker||!s.call(i,n,void 0,!0))||!0!==t&&!s.call(i,n,void 0,!0));)n--;return n},t.translatePosition=u;var a=i(4713),n=i(7215);function r(e){var t=this.maskset;return void 0!==t.buffer&&!0!==e||(t.buffer=a.getMaskTemplate.call(this,!0,o.call(this),!0),void 0===t._buffer&&(t._buffer=t.buffer.slice())),t.buffer}function o(e,t,i){var a=this.maskset,n=-1,r=-1,o=i||a.validPositions;for(var s in void 0===e&&(e=-1),o){var l=parseInt(s);o[l]&&(t||!0!==o[l].generatedInput)&&(l<=e&&(n=l),l>=e&&(r=l))}return-1===n||n==e?r:-1==r||e-n<r-e?n:r}function s(e,t,i){var n=this,r=this.maskset,o=a.getTestTemplate.call(n,e).match;if(""===o.def&&(o=a.getTest.call(n,e).match),!0!==o.static)return o.fn;if(!0===i&&void 0!==r.validPositions[e]&&!0!==r.validPositions[e].generatedInput)return!0;if(!0!==t&&e>-1){if(i){var s=a.getTests.call(n,e);return s.length>1+(""===s[s.length-1].match.def?1:0)}var l=a.determineTestTemplate.call(n,e,a.getTests.call(n,e)),u=a.getPlaceholder.call(n,e,l.match);return l.match.def!==u}return!1}function l(e,t,i){var n=this;void 0===i&&(i=!0);for(var r=e+1;""!==a.getTest.call(n,r).match.def&&(!0===t&&(!0!==a.getTest.call(n,r).match.newBlockMarker||!s.call(n,r,void 0,!0))||!0!==t&&!s.call(n,r,void 0,i));)r++;return r}function u(e){var t=this.opts,i=this.el;return!this.isRTL||"number"!=typeof e||t.greedy&&""===t.placeholder||!i||(e=Math.abs(this._valueGet().length-e)),e}},4713:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.determineTestTemplate=u,t.getDecisionTaker=o,t.getMaskTemplate=function(e,t,i,a,n){var r=this,o=this.opts,c=this.maskset,f=o.greedy;n&&o.greedy&&(o.greedy=!1,r.maskset.tests={});t=t||0;var p,h,m,v,g=[],k=0;do{if(!0===e&&c.validPositions[k])m=n&&c.validPositions[k].match.optionality&&void 0===c.validPositions[k+1]&&(!0===c.validPositions[k].generatedInput||c.validPositions[k].input==o.skipOptionalPartCharacter&&k>0)?u.call(r,k,d.call(r,k,p,k-1)):c.validPositions[k],h=m.match,p=m.locator.slice(),g.push(!0===i?m.input:!1===i?h.nativeDef:s.call(r,k,h));else{m=l.call(r,k,p,k-1),h=m.match,p=m.locator.slice();var y=!0!==a&&(!1!==o.jitMasking?o.jitMasking:h.jit);(v=(v&&h.static&&h.def!==o.groupSeparator&&null===h.fn||c.validPositions[k-1]&&h.static&&h.def!==o.groupSeparator&&null===h.fn)&&c.tests[k]&&1===c.tests[k].length)||!1===y||void 0===y||"number"==typeof y&&isFinite(y)&&y>k?g.push(!1===i?h.nativeDef:s.call(r,k,h)):v=!1}k++}while(!0!==h.static||""!==h.def||t>k);""===g[g.length-1]&&g.pop();!1===i&&void 0!==c.maskLength||(c.maskLength=k-1);return o.greedy=f,g},t.getPlaceholder=s,t.getTest=c,t.getTestTemplate=l,t.getTests=d,t.isSubsetOf=f;var a,n=(a=i(2394))&&a.__esModule?a:{default:a};function r(e,t){var i=(null!=e.alternation?e.mloc[o(e)]:e.locator).join("");if(""!==i)for(;i.length<t;)i+="0";return i}function o(e){var t=e.locator[e.alternation];return"string"==typeof t&&t.length>0&&(t=t.split(",")[0]),void 0!==t?t.toString():""}function s(e,t,i){var a=this.opts,n=this.maskset;if(void 0!==(t=t||c.call(this,e).match).placeholder||!0===i)return"function"==typeof t.placeholder?t.placeholder(a):t.placeholder;if(!0===t.static){if(e>-1&&void 0===n.validPositions[e]){var r,o=d.call(this,e),s=[];if(o.length>1+(""===o[o.length-1].match.def?1:0))for(var l=0;l<o.length;l++)if(""!==o[l].match.def&&!0!==o[l].match.optionality&&!0!==o[l].match.optionalQuantifier&&(!0===o[l].match.static||void 0===r||!1!==o[l].match.fn.test(r.match.def,n,e,!0,a))&&(s.push(o[l]),!0===o[l].match.static&&(r=o[l]),s.length>1&&/[0-9a-bA-Z]/.test(s[0].match.def)))return a.placeholder.charAt(e%a.placeholder.length)}return t.def}return a.placeholder.charAt(e%a.placeholder.length)}function l(e,t,i){return this.maskset.validPositions[e]||u.call(this,e,d.call(this,e,t?t.slice():t,i))}function u(e,t){var i=this.opts,a=function(e,t){var i=0,a=!1;t.forEach((function(e){e.match.optionality&&(0!==i&&i!==e.match.optionality&&(a=!0),(0===i||i>e.match.optionality)&&(i=e.match.optionality))})),i&&(0==e||1==t.length?i=0:a||(i=0));return i}(e,t);e=e>0?e-1:0;var n,o,s,l=r(c.call(this,e));i.greedy&&t.length>1&&""===t[t.length-1].match.def&&t.pop();for(var u=0;u<t.length;u++){var f=t[u];n=r(f,l.length);var d=Math.abs(n-l);(void 0===o||""!==n&&d<o||s&&!i.greedy&&s.match.optionality&&s.match.optionality-a>0&&"master"===s.match.newBlockMarker&&(!f.match.optionality||f.match.optionality-a<1||!f.match.newBlockMarker)||s&&!i.greedy&&s.match.optionalQuantifier&&!f.match.optionalQuantifier)&&(o=d,s=f)}return s}function c(e,t){var i=this.maskset;return i.validPositions[e]?i.validPositions[e]:(t||d.call(this,e))[0]}function f(e,t,i){function a(e){for(var t,i=[],a=-1,n=0,r=e.length;n<r;n++)if("-"===e.charAt(n))for(t=e.charCodeAt(n+1);++a<t;)i.push(String.fromCharCode(a));else a=e.charCodeAt(n),i.push(e.charAt(n));return i.join("")}return e.match.def===t.match.nativeDef||!(!(i.regex||e.match.fn instanceof RegExp&&t.match.fn instanceof RegExp)||!0===e.match.static||!0===t.match.static)&&-1!==a(t.match.fn.toString().replace(/[[\]/]/g,"")).indexOf(a(e.match.fn.toString().replace(/[[\]/]/g,"")))}function d(e,t,i){var a,r,o=this,s=this.dependencyLib,l=this.maskset,c=this.opts,d=this.el,p=l.maskToken,h=t?i:0,m=t?t.slice():[0],v=[],g=!1,k=t?t.join(""):"";function y(t,i,r,o){function s(r,o,u){function p(e,t){var i=0===t.matches.indexOf(e);return i||t.matches.every((function(a,n){return!0===a.isQuantifier?i=p(e,t.matches[n-1]):Object.prototype.hasOwnProperty.call(a,"matches")&&(i=p(e,a)),!i})),i}function m(e,t,i){var a,n;if((l.tests[e]||l.validPositions[e])&&(l.tests[e]||[l.validPositions[e]]).every((function(e,r){if(e.mloc[t])return a=e,!1;var o=void 0!==i?i:e.alternation,s=void 0!==e.locator[o]?e.locator[o].toString().indexOf(t):-1;return(void 0===n||s<n)&&-1!==s&&(a=e,n=s),!0})),a){var r=a.locator[a.alternation];return(a.mloc[t]||a.mloc[r]||a.locator).slice((void 0!==i?i:a.alternation)+1)}return void 0!==i?m(e,t):void 0}function b(e,t){var i=e.alternation,a=void 0===t||i===t.alternation&&-1===e.locator[i].toString().indexOf(t.locator[i]);if(!a&&i>t.alternation)for(var n=t.alternation;n<i;n++)if(e.locator[n]!==t.locator[n]){i=n,a=!0;break}if(a){e.mloc=e.mloc||{};var r=e.locator[i];if(void 0!==r){if("string"==typeof r&&(r=r.split(",")[0]),void 0===e.mloc[r]&&(e.mloc[r]=e.locator.slice()),void 0!==t){for(var o in t.mloc)"string"==typeof o&&(o=o.split(",")[0]),void 0===e.mloc[o]&&(e.mloc[o]=t.mloc[o]);e.locator[i]=Object.keys(e.mloc).join(",")}return!0}e.alternation=void 0}return!1}function x(e,t){if(e.locator.length!==t.locator.length)return!1;for(var i=e.alternation+1;i<e.locator.length;i++)if(e.locator[i]!==t.locator[i])return!1;return!0}if(h>e+c._maxTestPos)throw"Inputmask: There is probably an error in your mask definition or in the code. Create an issue on github with an example of the mask you are using. "+l.mask;if(h===e&&void 0===r.matches){if(v.push({match:r,locator:o.reverse(),cd:k,mloc:{}}),!r.optionality||void 0!==u||!(c.definitions&&c.definitions[r.nativeDef]&&c.definitions[r.nativeDef].optional||n.default.prototype.definitions[r.nativeDef]&&n.default.prototype.definitions[r.nativeDef].optional))return!0;g=!0,h=e}else if(void 0!==r.matches){if(r.isGroup&&u!==r){if(r=s(t.matches[t.matches.indexOf(r)+1],o,u))return!0}else if(r.isOptional){var P=r,E=v.length;if(r=y(r,i,o,u)){if(v.forEach((function(e,t){t>=E&&(e.match.optionality=e.match.optionality?e.match.optionality+1:1)})),a=v[v.length-1].match,void 0!==u||!p(a,P))return!0;g=!0,h=e}}else if(r.isAlternator){var S,w=r,_=[],M=v.slice(),O=o.length,T=!1,A=i.length>0?i.shift():-1;if(-1===A||"string"==typeof A){var C,D=h,j=i.slice(),B=[];if("string"==typeof A)B=A.split(",");else for(C=0;C<w.matches.length;C++)B.push(C.toString());if(void 0!==l.excludes[e]){for(var R=B.slice(),L=0,I=l.excludes[e].length;L<I;L++){var F=l.excludes[e][L].toString().split(":");o.length==F[1]&&B.splice(B.indexOf(F[0]),1)}0===B.length&&(delete l.excludes[e],B=R)}(!0===c.keepStatic||isFinite(parseInt(c.keepStatic))&&D>=c.keepStatic)&&(B=B.slice(0,1));for(var N=0;N<B.length;N++){C=parseInt(B[N]),v=[],i="string"==typeof A&&m(h,C,O)||j.slice();var V=w.matches[C];if(V&&s(V,[C].concat(o),u))r=!0;else if(0===N&&(T=!0),V&&V.matches&&V.matches.length>w.matches[0].matches.length)break;S=v.slice(),h=D,v=[];for(var G=0;G<S.length;G++){var H=S[G],K=!1;H.match.jit=H.match.jit||T,H.alternation=H.alternation||O,b(H);for(var U=0;U<_.length;U++){var $=_[U];if("string"!=typeof A||void 0!==H.alternation&&B.includes(H.locator[H.alternation].toString())){if(H.match.nativeDef===$.match.nativeDef){K=!0,b($,H);break}if(f(H,$,c)){b(H,$)&&(K=!0,_.splice(_.indexOf($),0,H));break}if(f($,H,c)){b($,H);break}if(Z=$,!0===(W=H).match.static&&!0!==Z.match.static&&Z.match.fn.test(W.match.def,l,e,!1,c,!1)){x(H,$)||void 0!==d.inputmask.userOptions.keepStatic?b(H,$)&&(K=!0,_.splice(_.indexOf($),0,H)):c.keepStatic=!0;break}}}K||_.push(H)}}v=M.concat(_),h=e,g=v.length>0,r=_.length>0,i=j.slice()}else r=s(w.matches[A]||t.matches[A],[A].concat(o),u);if(r)return!0}else if(r.isQuantifier&&u!==t.matches[t.matches.indexOf(r)-1])for(var q=r,z=i.length>0?i.shift():0;z<(isNaN(q.quantifier.max)?z+1:q.quantifier.max)&&h<=e;z++){var Q=t.matches[t.matches.indexOf(q)-1];if(r=s(Q,[z].concat(o),Q)){if((a=v[v.length-1].match).optionalQuantifier=z>=q.quantifier.min,a.jit=(z+1)*(Q.matches.indexOf(a)+1)>q.quantifier.jit,a.optionalQuantifier&&p(a,Q)){g=!0,h=e;break}return a.jit&&(l.jitOffset[e]=Q.matches.length-Q.matches.indexOf(a)),!0}}else if(r=y(r,i,o,u))return!0}else h++;var W,Z}for(var u=i.length>0?i.shift():0;u<t.matches.length;u++)if(!0!==t.matches[u].isQuantifier){var p=s(t.matches[u],[u].concat(r),o);if(p&&h===e)return p;if(h>e)break}}if(e>-1){if(void 0===t){for(var b,x=e-1;void 0===(b=l.validPositions[x]||l.tests[x])&&x>-1;)x--;void 0!==b&&x>-1&&(m=function(e,t){var i,a=[];return Array.isArray(t)||(t=[t]),t.length>0&&(void 0===t[0].alternation||!0===c.keepStatic?0===(a=u.call(o,e,t.slice()).locator.slice()).length&&(a=t[0].locator.slice()):t.forEach((function(e){""!==e.def&&(0===a.length?(i=e.alternation,a=e.locator.slice()):e.locator[i]&&-1===a[i].toString().indexOf(e.locator[i])&&(a[i]+=","+e.locator[i]))}))),a}(x,b),k=m.join(""),h=x)}if(l.tests[e]&&l.tests[e][0].cd===k)return l.tests[e];for(var P=m.shift();P<p.length;P++){if(y(p[P],m,[P])&&h===e||h>e)break}}return(0===v.length||g)&&v.push({match:{fn:null,static:!0,optionality:!1,casing:null,def:"",placeholder:""},locator:[],mloc:{},cd:k}),void 0!==t&&l.tests[e]?r=s.extend(!0,[],v):(l.tests[e]=s.extend(!0,[],v),r=l.tests[e]),v.forEach((function(e){e.match.optionality=!1})),r}},7215:function(e,t,i){Object.defineProperty(t,"__esModule",{value:!0}),t.alternate=l,t.checkAlternationMatch=function(e,t,i){for(var a,n=this.opts.greedy?t:t.slice(0,1),r=!1,o=void 0!==i?i.split(","):[],s=0;s<o.length;s++)-1!==(a=e.indexOf(o[s]))&&e.splice(a,1);for(var l=0;l<e.length;l++)if(n.includes(e[l])){r=!0;break}return r},t.handleRemove=function(e,t,i,a,s){var u=this,c=this.maskset,f=this.opts;if((f.numericInput||u.isRTL)&&(t===r.default.BACKSPACE?t=r.default.DELETE:t===r.default.DELETE&&(t=r.default.BACKSPACE),u.isRTL)){var d=i.end;i.end=i.begin,i.begin=d}var p,h=o.getLastValidPosition.call(u,void 0,!0);i.end>=o.getBuffer.call(u).length&&h>=i.end&&(i.end=h+1);t===r.default.BACKSPACE?i.end-i.begin<1&&(i.begin=o.seekPrevious.call(u,i.begin)):t===r.default.DELETE&&i.begin===i.end&&(i.end=o.isMask.call(u,i.end,!0,!0)?i.end+1:o.seekNext.call(u,i.end)+1);if(!1!==(p=v.call(u,i))){if(!0!==a&&!1!==f.keepStatic||null!==f.regex&&-1!==n.getTest.call(u,i.begin).match.def.indexOf("|")){var m=l.call(u,!0);if(m){var g=void 0!==m.caret?m.caret:m.pos?o.seekNext.call(u,m.pos.begin?m.pos.begin:m.pos):o.getLastValidPosition.call(u,-1,!0);(t!==r.default.DELETE||i.begin>g)&&i.begin}}!0!==a&&(c.p=t===r.default.DELETE?i.begin+p:i.begin,c.p=o.determineNewCaretPosition.call(u,{begin:c.p,end:c.p},!1,!1===f.insertMode&&t===r.default.BACKSPACE?"none":void 0).begin)}},t.isComplete=c,t.isSelection=f,t.isValid=d,t.refreshFromBuffer=h,t.revalidateMask=v;var a,n=i(4713),r=(a=i(5581))&&a.__esModule?a:{default:a},o=i(8711),s=i(6030);function l(e,t,i,a,r,s){var u,c,f,p,h,m,v,g,k,y,b,x=this,P=this.dependencyLib,E=this.opts,S=x.maskset,w=P.extend(!0,{},S.validPositions),_=P.extend(!0,{},S.tests),M=!1,O=!1,T=void 0!==r?r:o.getLastValidPosition.call(x);if(s&&(y=s.begin,b=s.end,s.begin>s.end&&(y=s.end,b=s.begin)),-1===T&&void 0===r)u=0,c=(p=n.getTest.call(x,u)).alternation;else for(;T>=0;T--)if((f=S.validPositions[T])&&void 0!==f.alternation){if(p&&p.locator[f.alternation]!==f.locator[f.alternation])break;u=T,c=S.validPositions[u].alternation,p=f}if(void 0!==c){v=parseInt(u),S.excludes[v]=S.excludes[v]||[],!0!==e&&S.excludes[v].push((0,n.getDecisionTaker)(p)+":"+p.alternation);var A=[],C=-1;for(h=v;h<o.getLastValidPosition.call(x,void 0,!0)+1;h++)-1===C&&e<=h&&void 0!==t&&(A.push(t),C=A.length-1),(m=S.validPositions[h])&&!0!==m.generatedInput&&(void 0===s||h<y||h>=b)&&A.push(m.input),delete S.validPositions[h];for(-1===C&&void 0!==t&&(A.push(t),C=A.length-1);void 0!==S.excludes[v]&&S.excludes[v].length<10;){for(S.tests={},o.resetMaskSet.call(x,!0),M=!0,h=0;h<A.length&&(g=M.caret||o.getLastValidPosition.call(x,void 0,!0)+1,k=A[h],M=d.call(x,g,k,!1,a,!0));h++)h===C&&(O=M),1==e&&M&&(O={caretPos:h});if(M)break;if(o.resetMaskSet.call(x),p=n.getTest.call(x,v),S.validPositions=P.extend(!0,{},w),S.tests=P.extend(!0,{},_),!S.excludes[v]){O=l.call(x,e,t,i,a,v-1,s);break}var D=(0,n.getDecisionTaker)(p);if(-1!==S.excludes[v].indexOf(D+":"+p.alternation)){O=l.call(x,e,t,i,a,v-1,s);break}for(S.excludes[v].push(D+":"+p.alternation),h=v;h<o.getLastValidPosition.call(x,void 0,!0)+1;h++)delete S.validPositions[h]}}return O&&!1===E.keepStatic||delete S.excludes[v],O}function u(e,t,i){var a=this.opts,n=this.maskset;switch(a.casing||t.casing){case"upper":e=e.toUpperCase();break;case"lower":e=e.toLowerCase();break;case"title":var o=n.validPositions[i-1];e=0===i||o&&o.input===String.fromCharCode(r.default.SPACE)?e.toUpperCase():e.toLowerCase();break;default:if("function"==typeof a.casing){var s=Array.prototype.slice.call(arguments);s.push(n.validPositions),e=a.casing.apply(this,s)}}return e}function c(e){var t=this,i=this.opts,a=this.maskset;if("function"==typeof i.isComplete)return i.isComplete(e,i);if("*"!==i.repeat){var r=!1,s=o.determineLastRequiredPosition.call(t,!0),l=o.seekPrevious.call(t,s.l);if(void 0===s.def||s.def.newBlockMarker||s.def.optionality||s.def.optionalQuantifier){r=!0;for(var u=0;u<=l;u++){var c=n.getTestTemplate.call(t,u).match;if(!0!==c.static&&void 0===a.validPositions[u]&&!0!==c.optionality&&!0!==c.optionalQuantifier||!0===c.static&&e[u]!==n.getPlaceholder.call(t,u,c)){r=!1;break}}}return r}}function f(e){var t=this.opts.insertMode?0:1;return this.isRTL?e.begin-e.end>t:e.end-e.begin>t}function d(e,t,i,a,r,s,p){var g=this,k=this.dependencyLib,y=this.opts,b=g.maskset;i=!0===i;var x=e;function P(e){if(void 0!==e){if(void 0!==e.remove&&(Array.isArray(e.remove)||(e.remove=[e.remove]),e.remove.sort((function(e,t){return t.pos-e.pos})).forEach((function(e){v.call(g,{begin:e,end:e+1})})),e.remove=void 0),void 0!==e.insert&&(Array.isArray(e.insert)||(e.insert=[e.insert]),e.insert.sort((function(e,t){return e.pos-t.pos})).forEach((function(e){""!==e.c&&d.call(g,e.pos,e.c,void 0===e.strict||e.strict,void 0!==e.fromIsValid?e.fromIsValid:a)})),e.insert=void 0),e.refreshFromBuffer&&e.buffer){var t=e.refreshFromBuffer;h.call(g,!0===t?t:t.start,t.end,e.buffer),e.refreshFromBuffer=void 0}void 0!==e.rewritePosition&&(x=e.rewritePosition,e=!0)}return e}function E(t,i,r){var s=!1;return n.getTests.call(g,t).every((function(l,c){var d=l.match;if(o.getBuffer.call(g,!0),!1!==(s=(!d.jit||void 0!==b.validPositions[o.seekPrevious.call(g,t)])&&(null!=d.fn?d.fn.test(i,b,t,r,y,f.call(g,e)):(i===d.def||i===y.skipOptionalPartCharacter)&&""!==d.def&&{c:n.getPlaceholder.call(g,t,d,!0)||d.def,pos:t}))){var p=void 0!==s.c?s.c:i,h=t;return p=p===y.skipOptionalPartCharacter&&!0===d.static?n.getPlaceholder.call(g,t,d,!0)||d.def:p,!0!==(s=P(s))&&void 0!==s.pos&&s.pos!==t&&(h=s.pos),!0!==s&&void 0===s.pos&&void 0===s.c?!1:(!1===v.call(g,e,k.extend({},l,{input:u.call(g,p,d,h)}),a,h)&&(s=!1),!1)}return!0})),s}void 0!==e.begin&&(x=g.isRTL?e.end:e.begin);var S=!0,w=k.extend(!0,{},b.validPositions);if(!1===y.keepStatic&&void 0!==b.excludes[x]&&!0!==r&&!0!==a)for(var _=x;_<(g.isRTL?e.begin:e.end);_++)void 0!==b.excludes[_]&&(b.excludes[_]=void 0,delete b.tests[_]);if("function"==typeof y.preValidation&&!0!==a&&!0!==s&&(S=P(S=y.preValidation.call(g,o.getBuffer.call(g),x,t,f.call(g,e),y,b,e,i||r))),!0===S){if(S=E(x,t,i),(!i||!0===a)&&!1===S&&!0!==s){var M=b.validPositions[x];if(!M||!0!==M.match.static||M.match.def!==t&&t!==y.skipOptionalPartCharacter){if(y.insertMode||void 0===b.validPositions[o.seekNext.call(g,x)]||e.end>x){var O=!1;if(b.jitOffset[x]&&void 0===b.validPositions[o.seekNext.call(g,x)]&&!1!==(S=d.call(g,x+b.jitOffset[x],t,!0,!0))&&(!0!==r&&(S.caret=x),O=!0),e.end>x&&(b.validPositions[x]=void 0),!O&&!o.isMask.call(g,x,y.keepStatic&&0===x))for(var T=x+1,A=o.seekNext.call(g,x,!1,0!==x);T<=A;T++)if(!1!==(S=E(T,t,i))){S=m.call(g,x,void 0!==S.pos?S.pos:T)||S,x=T;break}}}else S={caret:o.seekNext.call(g,x)}}!1!==S||!y.keepStatic||!c.call(g,o.getBuffer.call(g))&&0!==x||i||!0===r?f.call(g,e)&&b.tests[x]&&b.tests[x].length>1&&y.keepStatic&&!i&&!0!==r&&(S=l.call(g,!0)):S=l.call(g,x,t,i,a,void 0,e),!0===S&&(S={pos:x})}if("function"==typeof y.postValidation&&!0!==a&&!0!==s){var C=y.postValidation.call(g,o.getBuffer.call(g,!0),void 0!==e.begin?g.isRTL?e.end:e.begin:e,t,S,y,b,i,p);void 0!==C&&(S=!0===C?S:C)}S&&void 0===S.pos&&(S.pos=x),!1===S||!0===s?(o.resetMaskSet.call(g,!0),b.validPositions=k.extend(!0,{},w)):m.call(g,void 0,x,!0);var D=P(S);void 0!==g.maxLength&&(o.getBuffer.call(g).length>g.maxLength&&!a&&(o.resetMaskSet.call(g,!0),b.validPositions=k.extend(!0,{},w),D=!1));return D}function p(e,t,i){for(var a=this.maskset,r=!1,o=n.getTests.call(this,e),s=0;s<o.length;s++){if(o[s].match&&(o[s].match.nativeDef===t.match[i.shiftPositions?"def":"nativeDef"]&&(!i.shiftPositions||!t.match.static)||o[s].match.nativeDef===t.match.nativeDef||i.regex&&!o[s].match.static&&o[s].match.fn.test(t.input))){r=!0;break}if(o[s].match&&o[s].match.def===t.match.nativeDef){r=void 0;break}}return!1===r&&void 0!==a.jitOffset[e]&&(r=p.call(this,e+a.jitOffset[e],t,i)),r}function h(e,t,i){var a,n,r=this,l=this.maskset,u=this.opts,c=this.dependencyLib,f=u.skipOptionalPartCharacter,d=r.isRTL?i.slice().reverse():i;if(u.skipOptionalPartCharacter="",!0===e)o.resetMaskSet.call(r),l.tests={},e=0,t=i.length,n=o.determineNewCaretPosition.call(r,{begin:0,end:0},!1).begin;else{for(a=e;a<t;a++)delete l.validPositions[a];n=e}var p=new c.Event("keypress");for(a=e;a<t;a++){p.keyCode=d[a].toString().charCodeAt(0),r.ignorable=!1;var h=s.EventHandlers.keypressEvent.call(r,p,!0,!1,!1,n);!1!==h&&void 0!==h&&(n=h.forwardPosition)}u.skipOptionalPartCharacter=f}function m(e,t,i){var a=this,r=this.maskset,s=this.dependencyLib;if(void 0===e)for(e=t-1;e>0&&!r.validPositions[e];e--);for(var l=e;l<t;l++){if(void 0===r.validPositions[l]&&!o.isMask.call(a,l,!1))if(0==l?n.getTest.call(a,l):r.validPositions[l-1]){var u=n.getTests.call(a,l).slice();""===u[u.length-1].match.def&&u.pop();var c,f=n.determineTestTemplate.call(a,l,u);if(f&&(!0!==f.match.jit||"master"===f.match.newBlockMarker&&(c=r.validPositions[l+1])&&!0===c.match.optionalQuantifier)&&((f=s.extend({},f,{input:n.getPlaceholder.call(a,l,f.match,!0)||f.match.def})).generatedInput=!0,v.call(a,l,f,!0),!0!==i)){var p=r.validPositions[t].input;return r.validPositions[t]=void 0,d.call(a,t,p,!0,!0)}}}}function v(e,t,i,a){var r=this,s=this.maskset,l=this.opts,u=this.dependencyLib;function c(e,t,i){var a=t[e];if(void 0!==a&&!0===a.match.static&&!0!==a.match.optionality&&(void 0===t[0]||void 0===t[0].alternation)){var n=i.begin<=e-1?t[e-1]&&!0===t[e-1].match.static&&t[e-1]:t[e-1],r=i.end>e+1?t[e+1]&&!0===t[e+1].match.static&&t[e+1]:t[e+1];return n&&r}return!1}var f=0,h=void 0!==e.begin?e.begin:e,m=void 0!==e.end?e.end:e,v=!0;if(e.begin>e.end&&(h=e.end,m=e.begin),a=void 0!==a?a:h,h!==m||l.insertMode&&void 0!==s.validPositions[a]&&void 0===i||void 0===t||t.match.optionalQuantifier||t.match.optionality){var g,k=u.extend(!0,{},s.validPositions),y=o.getLastValidPosition.call(r,void 0,!0);for(s.p=h,g=y;g>=h;g--)delete s.validPositions[g],void 0===t&&delete s.tests[g+1];var b,x,P=a,E=P;for(t&&(s.validPositions[a]=u.extend(!0,{},t),E++,P++),g=t?m:m-1;g<=y;g++){if(void 0!==(b=k[g])&&!0!==b.generatedInput&&(g>=m||g>=h&&c(g,k,{begin:h,end:m}))){for(;""!==n.getTest.call(r,E).match.def;){if(!1!==(x=p.call(r,E,b,l))||"+"===b.match.def){"+"===b.match.def&&o.getBuffer.call(r,!0);var S=d.call(r,E,b.input,"+"!==b.match.def,!0);if(v=!1!==S,P=(S.pos||E)+1,!v&&x)break}else v=!1;if(v){void 0===t&&b.match.static&&g===e.begin&&f++;break}if(!v&&o.getBuffer.call(r),E>s.maskLength)break;E++}""==n.getTest.call(r,E).match.def&&(v=!1),E=P}if(!v)break}if(!v)return s.validPositions=u.extend(!0,{},k),o.resetMaskSet.call(r,!0),!1}else t&&n.getTest.call(r,a).match.cd===t.match.cd&&(s.validPositions[a]=u.extend(!0,{},t));return o.resetMaskSet.call(r,!0),f}},2047:function(t){t.exports=e},5581:function(e){e.exports=JSON.parse('{"BACKSPACE":8,"BACKSPACE_SAFARI":127,"DELETE":46,"DOWN":40,"END":35,"ENTER":13,"ESCAPE":27,"HOME":36,"INSERT":45,"LEFT":37,"PAGE_DOWN":34,"PAGE_UP":33,"RIGHT":39,"SPACE":32,"TAB":9,"UP":38,"X":88,"Z":90,"CONTROL":17,"PAUSE/BREAK":19,"WINDOWS_LEFT":91,"WINDOWS_RIGHT":92,"KEY_229":229}')}},i={};function a(e){var n=i[e];if(void 0!==n)return n.exports;var r=i[e]={exports:{}};return t[e](r,r.exports,a),r.exports}var n={};return function(){var e=n;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var t,i=(t=a(3046))&&t.__esModule?t:{default:t};a(443);var r=i.default;e.default=r}(),n}()}));
(function(global,factory){typeof exports==="object"&&typeof module!=="undefined"?module.exports=factory():typeof define==="function"&&define.amd?define(factory):(global=global||self,global.Mustache=factory())})(this,function(){"use strict";var objectToString=Object.prototype.toString;var isArray=Array.isArray||function isArrayPolyfill(object){return objectToString.call(object)==="[object Array]"};function isFunction(object){return typeof object==="function"}function typeStr(obj){return isArray(obj)?"array":typeof obj}function escapeRegExp(string){return string.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&")}function hasProperty(obj,propName){return obj!=null&&typeof obj==="object"&&propName in obj}function primitiveHasOwnProperty(primitive,propName){return primitive!=null&&typeof primitive!=="object"&&primitive.hasOwnProperty&&primitive.hasOwnProperty(propName)}var regExpTest=RegExp.prototype.test;function testRegExp(re,string){return regExpTest.call(re,string)}var nonSpaceRe=/\S/;function isWhitespace(string){return!testRegExp(nonSpaceRe,string)}var entityMap={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;","/":"&#x2F;","`":"&#x60;","=":"&#x3D;"};function escapeHtml(string){return String(string).replace(/[&<>"'`=\/]/g,function fromEntityMap(s){return entityMap[s]})}var whiteRe=/\s*/;var spaceRe=/\s+/;var equalsRe=/\s*=/;var curlyRe=/\s*\}/;var tagRe=/#|\^|\/|>|\{|&|=|!/;function parseTemplate(template,tags){if(!template)return[];var lineHasNonSpace=false;var sections=[];var tokens=[];var spaces=[];var hasTag=false;var nonSpace=false;var indentation="";var tagIndex=0;function stripSpace(){if(hasTag&&!nonSpace){while(spaces.length)delete tokens[spaces.pop()]}else{spaces=[]}hasTag=false;nonSpace=false}var openingTagRe,closingTagRe,closingCurlyRe;function compileTags(tagsToCompile){if(typeof tagsToCompile==="string")tagsToCompile=tagsToCompile.split(spaceRe,2);if(!isArray(tagsToCompile)||tagsToCompile.length!==2)throw new Error("Invalid tags: "+tagsToCompile);openingTagRe=new RegExp(escapeRegExp(tagsToCompile[0])+"\\s*");closingTagRe=new RegExp("\\s*"+escapeRegExp(tagsToCompile[1]));closingCurlyRe=new RegExp("\\s*"+escapeRegExp("}"+tagsToCompile[1]))}compileTags(tags||mustache.tags);var scanner=new Scanner(template);var start,type,value,chr,token,openSection;while(!scanner.eos()){start=scanner.pos;value=scanner.scanUntil(openingTagRe);if(value){for(var i=0,valueLength=value.length;i<valueLength;++i){chr=value.charAt(i);if(isWhitespace(chr)){spaces.push(tokens.length);indentation+=chr}else{nonSpace=true;lineHasNonSpace=true;indentation+=" "}tokens.push(["text",chr,start,start+1]);start+=1;if(chr==="\n"){stripSpace();indentation="";tagIndex=0;lineHasNonSpace=false}}}if(!scanner.scan(openingTagRe))break;hasTag=true;type=scanner.scan(tagRe)||"name";scanner.scan(whiteRe);if(type==="="){value=scanner.scanUntil(equalsRe);scanner.scan(equalsRe);scanner.scanUntil(closingTagRe)}else if(type==="{"){value=scanner.scanUntil(closingCurlyRe);scanner.scan(curlyRe);scanner.scanUntil(closingTagRe);type="&"}else{value=scanner.scanUntil(closingTagRe)}if(!scanner.scan(closingTagRe))throw new Error("Unclosed tag at "+scanner.pos);if(type==">"){token=[type,value,start,scanner.pos,indentation,tagIndex,lineHasNonSpace]}else{token=[type,value,start,scanner.pos]}tagIndex++;tokens.push(token);if(type==="#"||type==="^"){sections.push(token)}else if(type==="/"){openSection=sections.pop();if(!openSection)throw new Error('Unopened section "'+value+'" at '+start);if(openSection[1]!==value)throw new Error('Unclosed section "'+openSection[1]+'" at '+start)}else if(type==="name"||type==="{"||type==="&"){nonSpace=true}else if(type==="="){compileTags(value)}}stripSpace();openSection=sections.pop();if(openSection)throw new Error('Unclosed section "'+openSection[1]+'" at '+scanner.pos);return nestTokens(squashTokens(tokens))}function squashTokens(tokens){var squashedTokens=[];var token,lastToken;for(var i=0,numTokens=tokens.length;i<numTokens;++i){token=tokens[i];if(token){if(token[0]==="text"&&lastToken&&lastToken[0]==="text"){lastToken[1]+=token[1];lastToken[3]=token[3]}else{squashedTokens.push(token);lastToken=token}}}return squashedTokens}function nestTokens(tokens){var nestedTokens=[];var collector=nestedTokens;var sections=[];var token,section;for(var i=0,numTokens=tokens.length;i<numTokens;++i){token=tokens[i];switch(token[0]){case"#":case"^":collector.push(token);sections.push(token);collector=token[4]=[];break;case"/":section=sections.pop();section[5]=token[2];collector=sections.length>0?sections[sections.length-1][4]:nestedTokens;break;default:collector.push(token)}}return nestedTokens}function Scanner(string){this.string=string;this.tail=string;this.pos=0}Scanner.prototype.eos=function eos(){return this.tail===""};Scanner.prototype.scan=function scan(re){var match=this.tail.match(re);if(!match||match.index!==0)return"";var string=match[0];this.tail=this.tail.substring(string.length);this.pos+=string.length;return string};Scanner.prototype.scanUntil=function scanUntil(re){var index=this.tail.search(re),match;switch(index){case-1:match=this.tail;this.tail="";break;case 0:match="";break;default:match=this.tail.substring(0,index);this.tail=this.tail.substring(index)}this.pos+=match.length;return match};function Context(view,parentContext){this.view=view;this.cache={".":this.view};this.parent=parentContext}Context.prototype.push=function push(view){return new Context(view,this)};Context.prototype.lookup=function lookup(name){var cache=this.cache;var value;if(cache.hasOwnProperty(name)){value=cache[name]}else{var context=this,intermediateValue,names,index,lookupHit=false;while(context){if(name.indexOf(".")>0){intermediateValue=context.view;names=name.split(".");index=0;while(intermediateValue!=null&&index<names.length){if(index===names.length-1)lookupHit=hasProperty(intermediateValue,names[index])||primitiveHasOwnProperty(intermediateValue,names[index]);intermediateValue=intermediateValue[names[index++]]}}else{intermediateValue=context.view[name];lookupHit=hasProperty(context.view,name)}if(lookupHit){value=intermediateValue;break}context=context.parent}cache[name]=value}if(isFunction(value))value=value.call(this.view);return value};function Writer(){this.templateCache={_cache:{},set:function set(key,value){this._cache[key]=value},get:function get(key){return this._cache[key]},clear:function clear(){this._cache={}}}}Writer.prototype.clearCache=function clearCache(){if(typeof this.templateCache!=="undefined"){this.templateCache.clear()}};Writer.prototype.parse=function parse(template,tags){var cache=this.templateCache;var cacheKey=template+":"+(tags||mustache.tags).join(":");var isCacheEnabled=typeof cache!=="undefined";var tokens=isCacheEnabled?cache.get(cacheKey):undefined;if(tokens==undefined){tokens=parseTemplate(template,tags);isCacheEnabled&&cache.set(cacheKey,tokens)}return tokens};Writer.prototype.render=function render(template,view,partials,config){var tags=this.getConfigTags(config);var tokens=this.parse(template,tags);var context=view instanceof Context?view:new Context(view,undefined);return this.renderTokens(tokens,context,partials,template,config)};Writer.prototype.renderTokens=function renderTokens(tokens,context,partials,originalTemplate,config){var buffer="";var token,symbol,value;for(var i=0,numTokens=tokens.length;i<numTokens;++i){value=undefined;token=tokens[i];symbol=token[0];if(symbol==="#")value=this.renderSection(token,context,partials,originalTemplate,config);else if(symbol==="^")value=this.renderInverted(token,context,partials,originalTemplate,config);else if(symbol===">")value=this.renderPartial(token,context,partials,config);else if(symbol==="&")value=this.unescapedValue(token,context);else if(symbol==="name")value=this.escapedValue(token,context,config);else if(symbol==="text")value=this.rawValue(token);if(value!==undefined)buffer+=value}return buffer};Writer.prototype.renderSection=function renderSection(token,context,partials,originalTemplate,config){var self=this;var buffer="";var value=context.lookup(token[1]);function subRender(template){return self.render(template,context,partials,config)}if(!value)return;if(isArray(value)){for(var j=0,valueLength=value.length;j<valueLength;++j){buffer+=this.renderTokens(token[4],context.push(value[j]),partials,originalTemplate,config)}}else if(typeof value==="object"||typeof value==="string"||typeof value==="number"){buffer+=this.renderTokens(token[4],context.push(value),partials,originalTemplate,config)}else if(isFunction(value)){if(typeof originalTemplate!=="string")throw new Error("Cannot use higher-order sections without the original template");value=value.call(context.view,originalTemplate.slice(token[3],token[5]),subRender);if(value!=null)buffer+=value}else{buffer+=this.renderTokens(token[4],context,partials,originalTemplate,config)}return buffer};Writer.prototype.renderInverted=function renderInverted(token,context,partials,originalTemplate,config){var value=context.lookup(token[1]);if(!value||isArray(value)&&value.length===0)return this.renderTokens(token[4],context,partials,originalTemplate,config)};Writer.prototype.indentPartial=function indentPartial(partial,indentation,lineHasNonSpace){var filteredIndentation=indentation.replace(/[^ \t]/g,"");var partialByNl=partial.split("\n");for(var i=0;i<partialByNl.length;i++){if(partialByNl[i].length&&(i>0||!lineHasNonSpace)){partialByNl[i]=filteredIndentation+partialByNl[i]}}return partialByNl.join("\n")};Writer.prototype.renderPartial=function renderPartial(token,context,partials,config){if(!partials)return;var tags=this.getConfigTags(config);var value=isFunction(partials)?partials(token[1]):partials[token[1]];if(value!=null){var lineHasNonSpace=token[6];var tagIndex=token[5];var indentation=token[4];var indentedValue=value;if(tagIndex==0&&indentation){indentedValue=this.indentPartial(value,indentation,lineHasNonSpace)}var tokens=this.parse(indentedValue,tags);return this.renderTokens(tokens,context,partials,indentedValue,config)}};Writer.prototype.unescapedValue=function unescapedValue(token,context){var value=context.lookup(token[1]);if(value!=null)return value};Writer.prototype.escapedValue=function escapedValue(token,context,config){var escape=this.getConfigEscape(config)||mustache.escape;var value=context.lookup(token[1]);if(value!=null)return typeof value==="number"&&escape===mustache.escape?String(value):escape(value)};Writer.prototype.rawValue=function rawValue(token){return token[1]};Writer.prototype.getConfigTags=function getConfigTags(config){if(isArray(config)){return config}else if(config&&typeof config==="object"){return config.tags}else{return undefined}};Writer.prototype.getConfigEscape=function getConfigEscape(config){if(config&&typeof config==="object"&&!isArray(config)){return config.escape}else{return undefined}};var mustache={name:"mustache.js",version:"4.2.0",tags:["{{","}}"],clearCache:undefined,escape:undefined,parse:undefined,render:undefined,Scanner:undefined,Context:undefined,Writer:undefined,set templateCache(cache){defaultWriter.templateCache=cache},get templateCache(){return defaultWriter.templateCache}};var defaultWriter=new Writer;mustache.clearCache=function clearCache(){return defaultWriter.clearCache()};mustache.parse=function parse(template,tags){return defaultWriter.parse(template,tags)};mustache.render=function render(template,view,partials,config){if(typeof template!=="string"){throw new TypeError('Invalid template! Template should be a "string" '+'but "'+typeStr(template)+'" was given as the first '+"argument for mustache#render(template, view, partials)")}return defaultWriter.render(template,view,partials,config)};mustache.escape=escapeHtml;mustache.Scanner=Scanner;mustache.Context=Context;mustache.Writer=Writer;return mustache});

/*! Selectonic - v0.6.3 - 2015-07-20
* https://github.com/anovi/selectonic
* Copyright (c) 2015 Alexey Novichkov; Licensed MIT */

!function(a,b,c){"use strict";function d(b,c,d){return this._schema=b,this._options={},this._callbacks={},this.set(a.extend({},c,d||{}),!0),this}function e(b,c){this._name=e.pluginName,this.el=b,this.$el=a(b),this.ui={},this._selected=0,this._isEnable=!0,this._keyModes={},this.options=new d(l,k,c);var f=this;this.options.on("filter",function(a){return f._itemsSelector="."+f.options.get("listClass")+" "+a,a}),this.options.on("autoScroll",function(a){return f._setScrolledElem(a),a}),this._itemsSelector="."+this.options.get("listClass")+" "+this.options.get("filter"),this._setScrolledElem(this.options.get("autoScroll")),this._init()}var f=a.fn.outerHeight?"outerHeight":"height";a.fn.jquery||a.fn.zepto||(a.fn.zepto=!0);var g=function(a,b,c){var d,e,f,g=null,h=0;c=c||{};var i=function(){h=c.leading===!1?0:new Date,g=null,f=a.apply(d,e)};return function(){var j=new Date;h||c.leading!==!1||(h=j);var k=b-(j-h);return d=this,e=arguments,0>=k?(clearTimeout(g),g=null,h=j,f=a.apply(d,e)):g||c.trailing===!1||(g=setTimeout(i,k)),f}},h=Array.prototype.indexOf||function(a){for(var b=0,c=this.length;c>b;b++)if(this[b]===a)return b;return-1},i=function(a,b){return a instanceof Array?h.call(a,b)>=0:!1},j=a(b.document);d.isCorrectType=function(a,b){var c=typeof a,d=null===a&&b.nullable;return b.type instanceof Array?i(b.type,c)||d:c===b.type||d},d.prototype.set=function(b,e){var f,g;for(f in b){var h=b[f],j=this._schema[f];if(j!==c){if(j.unchangeable&&!e)throw new Error('Option "'+f+'" could be setted once at the begining.');if(!d.isCorrectType(h,j)){var k='Option "'+f+'" must be '+(j.type instanceof Array?j.type.join(", "):j.type)+(j.nullable?" or null.":".");throw new TypeError(k)}if(j.values&&!i(j.values,h))throw new RangeError('Option "'+f+'" only could be in these values: "'+j.values.join('", "')+'".')}}for(f in b)(g=this._callbacks[f])&&(b[f]=g.call(this,b[f]));this._options=a.extend(this._options,b)},d.prototype.get=function(b){return b?this._options[b]:a.extend({},this._options)},d.prototype.on=function(a,b){this._callbacks[a]=b},d.prototype.off=function(a){this._callbacks[a]&&delete this._callbacks[a]};var k={filter:"> *",multi:!0,mouseMode:"standard",focusBlur:!1,selectionBlur:!1,handle:null,textSelection:!1,focusOnHover:!1,keyboard:!1,keyboardMode:"select",autoScroll:!0,loop:!1,preventInputs:!0,listClass:"j-selectable",focusClass:"j-focused",selectedClass:"j-selected",disabledClass:"j-disabled",create:null,before:null,focusLost:null,select:null,unselect:null,unselectAll:null,stop:null,destroy:null},l={filter:{type:"string"},multi:{type:"boolean"},mouseMode:{type:"string",values:["standard","mouseup","toggle"]},focusBlur:{type:"boolean"},selectionBlur:{type:"boolean"},handle:{type:"string",nullable:!0},textSelection:{type:"boolean"},focusOnHover:{type:"boolean"},keyboard:{type:"boolean"},keyboardMode:{type:"string",values:["select","toggle"]},autoScroll:{type:["boolean","string"]},loop:{type:"boolean"},preventInputs:{type:"boolean"},listClass:{type:"string",unchangeable:!0},focusClass:{type:"string",unchangeable:!0},selectedClass:{type:"string",unchangeable:!0},disabledClass:{type:"string",unchangeable:!0},create:{type:"function",nullable:!0},before:{type:"function",nullable:!0},focusLost:{type:"function",nullable:!0},select:{type:"function",nullable:!0},unselect:{type:"function",nullable:!0},unselectAll:{type:"function",nullable:!0},stop:{type:"function",nullable:!0},destroy:{type:"function",nullable:!0}};e.pluginName="selectonic",e.keyCode={DOWN:40,UP:38,SHIFT:16,END:35,HOME:36,PAGE_DOWN:34,PAGE_UP:33,A:65,SPACE:32,ENTER:13},e.getDataObject=function(b){return a(b).data("plugin_"+e.pluginName)},e.prototype._init=function(){this.$el.addClass(this.options.get("listClass")),this._bindEvents(),this.$el.data("plugin_"+e.pluginName,this),this._trigger("create")},e.prototype._setScrolledElem=function(b){var c;if(null===b||!1===b)return void delete this._scrolledElem;if("string"==typeof b){if(c=a(b),!(c.length>0))throw new Error('There are no elements that matches to selector - "'+b+'"');return void(this._scrolledElem=c[0])}this._scrolledElem=this.el},e.prototype._cancel=function(b,c){if(!c.wasCancelled){c.isCancellation=this._isPrevented=!0;var d=this;a.each(a(c.changedItems),function(e,f){c.prevItemsStates[e]?d._select(b,c,a(f),!0):d._unselect(b,c,a(f),!0)}),c.prevFocus&&this._setFocus(c.prevFocus),delete c.isCancellation,c.wasCancelled=!0}},e.prototype._bindEvents=function(){var a=this,b=this._name;this._mouseEvent=function(b){a._isEnable&&1===b.which&&a._mouseHandler.call(a,b)},this._keyboardEvent=function(b){a.options.get("keyboard")&&a._isEnable&&a._keyHandler.call(a,b)},this._selectstartEvent=function(){return a.options.get("textSelection")?void 0:!1},this._mousemoveEvent=g(function(b){a._isEnable&&a.options&&a.options.get("focusOnHover")&&a._mousemoveHandler.call(a,b)},20),j.on("keydown."+b,this._keyboardEvent),j.on("keyup."+b,this._keyboardEvent),j.on("mousemove."+b,this._mousemoveEvent),j.on("click."+b,this._mouseEvent),j.on("mousedown."+b,this._mouseEvent),j.on("mouseup."+b,this._mouseEvent),this.$el.on("selectstart."+b,this._selectstartEvent)},e.prototype._unbindEvents=function(){var a=this._name;j.off("keydown."+a,this._keyboardEvent),j.off("keyup."+a,this._keyboardEvent),j.off("mousemove."+a,this._mousemoveEvent),j.off("click."+a,this._mouseEvent),j.off("mousedown."+a,this._mouseEvent),j.off("mouseup."+a,this._mouseEvent),this.$el.off("selectstart."+a,this._selectstartEvent)},e.prototype._getTarget=function(c){for(var d,e,f,g=c.target,h=this.options.get("handle");null!==g&&g!==this.el;)d=a(g),d.context=b.document,d.is(this._itemsSelector)&&(e=g),h&&d.is(h)&&(f=g),g=g.parentNode;return h&&g&&f?e:!h&&g?e:null},e.prototype._getItems=function(c,d,e){var f;switch(d){case"next":case"prev":for(var g=e.jquery?e:a(e),h=a.fn[d];;){if(g=h.call(g),0===g.length)break;if(g.context=b.document,g.is(this._itemsSelector))return g}return null;case"pageup":case"pagedown":return this._getNextPageElem(c,d,e);case"first":return f=c.allItems?c.allItems:this.$el.find(this.options.get("filter")),c.allItems=f,f.first();case"last":return f=c.allItems?c.allItems:this.$el.find(this.options.get("filter")),c.allItems=f,f.last();default:return f=c.allItems?c.allItems:this.$el.find(this.options.get("filter")),c.allItems=f,void 0!==d&&a.isNumeric(d)?f.eq(d):f}},e.prototype._getNextPageElem=function(c,d,e){var g,h,i,j,k,l=c.isShiftPageRange,m=this._scrolledElem||this.el,n=m.clientHeight,o=a(b)[f](),p=a(e),q=n>o,r=q?o:n,s=p[f](),t=s,u=s,v="pageup"===d?"prev":"next";for(l&&(v="pageup"===d?-1:1,j=this._getItems(c),c.rangeStart=i=j.index(e));;){if(l?(i+=v,k=i>=0?j.eq(i):null,g=k&&k.length>0?k:null):g=this._getItems(c,v,p),!g&&p[0]===e)break;if(!g)return l&&(c.rangeEnd=i-v),p;if(h=g[f](),u+=h,u>r)return t+h>r?(l&&(c.rangeEnd=i),g):(l&&(c.rangeEnd=i-v),p);t=h,p=g}return null},e.prototype._trigger=function(a,b,c){var d,e=this.options.get(a);if(e){if("create"===a||"destroy"===a)return e.call(this.$el);switch(d={},c.target&&(d.target=c.target),this.ui.focus&&(d.focus=this.ui.focus),a){case"select":d.items=c.selected;break;case"unselectAll":case"unselect":d.items=c.unselected;break;case"stop":c.wasCancelled||(d.items=c.changedItems)}e.call(this.$el,b||null,d)}},e.prototype._controller=function(a,b){var d;return b.changedItems=[],b.prevItemsStates=[],delete this._isPrevented,this._trigger("before",a,b),this._isPrevented?(this._cancel(a,b),void this._stop(a,b)):(b.wasSelected=this._selected>0,b.target&&b.isTargetWasSelected===c&&(b.isTargetWasSelected=this._getIsSelected(b.target)),b.isRangeSelect&&b.isTargetWasSelected&&b.target===this.ui.focus||(b.isRangeSelect?this._perfomRangeSelect(a,b):b.isMultiSelect?(d=b.isTargetWasSelected?this._unselect:this._select,d.call(this,a,b,b.items)):b.target&&!b.items&&"mouseover"===a.type||(b.target&&b.items?(this._selected&&1===this._selected&&this._getIsSelected(this.ui.focus)?this._unselect(a,b,this.ui.focus,b.isTargetWasSelected):this._selected&&this._unselectAll(a,b),this._select(a,b,b.items,b.isTargetWasSelected)):!b.target&&this._selected>0&&this.options.get("selectionBlur")&&this._unselectAll(a,b))),!this._selected&&b.wasSelected&&this._trigger("unselectAll",a,b),b.prevFocus=this.ui.focus?this.ui.focus:null,!b.target&&this.options.get("focusBlur")?this._blur(a,b):b.target&&!b.wasCancelled&&this._setFocus(b.target),void this._stop(a,b))},e.prototype._perfomRangeSelect=function(a,b){var c,d,e,f,g,h,i,j=b.rangeStart<b.rangeEnd,k=this._getItems(b),l=j?b.rangeStart:b.rangeEnd,m=j?b.rangeEnd:b.rangeStart;b.isNewSolidSelection?(d=k.slice(0,l),d=d.add(k.slice(m+1)),this._unselect(a,b,d),this._select(a,b,b.items)):this.ui.solidInitialElem&&!b.isTargetWasSelected&&(e=b.items.index(this.ui.solidInitialElem))>=0?(e=j?b.rangeStart+e:b.rangeEnd+e,f=e<b.rangeStart,g=b.rangeStart<e,h=e<b.rangeEnd,i=b.rangeEnd<e,(!h&&f||!i&&g)&&(d=g?k.slice(l,e):k.slice(e+1,m+1),d.length>0&&this._unselect(a,b,d)),(i&&!g||h&&!f)&&(d=i?k.slice(l,e):k.slice(e+1,m+1),d.length>0&&this._select(a,b,d))):(c=b.isTargetWasSelected?this._unselect:this._select,c.call(this,a,b,b.items))},e.prototype._changeItemsStates=function(b,c,d){var e=c>0,f=[],g=this;a(b).each(function(b,h){var i=g._getIsSelected(h),j=e?!i:i,k=h===d.target&&d.isTargetWasSelected;(!k||e||d.isMultiSelect||d.isRangeSelect)&&(j&&(d.isCancellation||(f.push(h),d.prevItemsStates.push(i)),g._selected+=c),a(h).toggleClass(g.options.get("selectedClass"),e))}),d.isCancellation||(d[e?"selected":"unselected"]=a(f),d.changedItems=d.changedItems.concat(f))},e.prototype._select=function(a,b,c,d){this._changeItemsStates(c,1,b),d||this._trigger("select",a,b),this._isPrevented&&!b.isCancellation&&this._cancel(a,b)},e.prototype._unselect=function(a,b,c,d){this._changeItemsStates(c,-1,b),d||this._trigger("unselect",a,b),this._isPrevented&&!b.isCancellation&&this._cancel(a,b)},e.prototype._unselectAll=function(a,b){var c,d;this._selected&&0!==this._selected&&(d=this._getItems(b),c=b.target&&b.isTargetWasSelected&&1===this._selected,this._unselect(a,b,d,c))},e.prototype._multiSelect=function(b){return b.isMultiSelect=!0,a(b.target)},e.prototype._rangeSelect=function(b){if(b.isRangeSelect=!0,b.target===this.ui.focus)return a(b.target);var c=b.allItems?b.allItems:this._getItems(b),d=c.index(b.target),e=c.index(this.ui.focus),f=e>d?c.slice(d,e):c.slice(e,d);return f.push(e>d?c[e]:c[d]),b.allItems=c,b.rangeStart=e,b.rangeEnd=d,f},e.prototype._getIsSelected=function(b){var c=this.options.get();return a(b).length<=1?a(b).hasClass(c.selectedClass):a.map(a(b),function(b){return a(b).hasClass(c.selectedClass)})},e.prototype._blur=function(b,c,d){!d&&this.ui.focus&&this._trigger("focusLost",b,c),this.ui.focus&&(a(this.ui.focus).removeClass(this.options.get("focusClass")),delete this.ui.focus)},e.prototype._setFocus=function(b){return b?(this.ui.focus&&a(this.ui.focus).removeClass(this.options.get("focusClass")),this.ui.focus=b,a(this.ui.focus).addClass(this.options.get("focusClass")),this.ui.focus):void 0},e.prototype._stop=function(a,b){this._trigger("stop",a,b),this._isPrevented&&this._cancel(a,b)},e.prototype._checkIfElem=function(b){var c;return b&&(b.jquery||b.zepto||b.nodeType)?(b=b.jquery||b.zepto?b:a(b),c=b.filter(this._itemsSelector),c.length>0?c:null):!1},e.prototype._checkIfSelector=function(a){var b;return a&&"string"==typeof a?(b=this.$el.find(a).filter(this._itemsSelector),b.jquery&&b.length>0?b:null):!1},e.prototype._keyHandler=function(b){if(this.options.get("keyboard")&&!(this.options.get("preventInputs")&&"INPUT"===b.target.tagName||"TEXTAREA"===b.target.tagName)){var c,d,f,g,h=b.which,i={};if("keyup"===b.type)return void(h===e.keyCode.SHIFT&&(delete this._shiftModeAction,delete this._keyModes.shift));if(h===e.keyCode.A&&this._isMulti(b)&&this.options.get("multi"))c=this._getItems(i),d=!0;else switch(h){case e.keyCode.DOWN:f="next",c=this._findNextTarget("next",i);break;case e.keyCode.UP:f="prev",c=this._findNextTarget("prev",i);break;case e.keyCode.HOME:f="prev",c=this._getItems(i,"first");break;case e.keyCode.END:f="next",c=this._getItems(i,"last");break;case e.keyCode.PAGE_DOWN:case e.keyCode.PAGE_UP:var j=h===e.keyCode.PAGE_DOWN;f=j?"next":"prev",g=j?"pagedown":"pageup",i.isShiftPageRange=this.options.get("multi")&&b.shiftKey&&!d,c=this._findNextTarget(g,i);break;case e.keyCode.SPACE:c=a(this.ui.focus);break;case e.keyCode.ENTER:this.options.get("multi")||(c=a(this.ui.focus))}f&&b.preventDefault(),c&&c.length>0?(i.target=c[0],i.items=c,"toggle"===this.options.get("keyboardMode")?(h===e.keyCode.SPACE||h===e.keyCode.ENTER&&!this.options.get("multi")||delete i.items,this.options.get("multi")&&(i.isMultiSelect=!0),delete this.ui.solidInitialElem):this.ui.focus&&this.options.get("multi")&&b.shiftKey&&!d?(h===e.keyCode.END||h===e.keyCode.HOME||h===e.keyCode.PAGE_UP||h===e.keyCode.PAGE_DOWN?this._rangeVariator(i):this._multiVariator(i,h,f,c),this.ui.solidInitialElem||i.target===this.ui.focus||(this.ui.solidInitialElem=this.ui.focus,i.isNewSolidSelection=!0),this._shiftModeAction||(this._shiftModeAction="select"),this._keyModes.shift||(this._keyModes.shift=h)):delete this.ui.solidInitialElem,this._controller(b,i),this.scroll()):(i.prevItemsStates=[],this._trigger("before",b,i),this._trigger("stop",b,i))}},e.prototype._rangeVariator=function(a){var b=void 0===a.isFocusSelected?this._getIsSelected(this.ui.focus):a.isFocusSelected,c=a.isTargetWasSelected=this._getIsSelected(a.target);b||c?(a.items=this._rangeSelect(a),c&&(a.items=a.rangeStart<a.rangeEnd?a.items.slice(0,a.items.length-1):a.items.slice(1))):(a.target=a.items=this.ui.focus,a.isMultiSelect=!0)},e.prototype._multiVariator=function(a,b,c,d){var e,f=void 0===a.isFocusSelected?this._getIsSelected(this.ui.focus):a.isFocusSelected,g=this._getIsSelected(a.target),h=this._getItems(a,c,d),i=this._getIsSelected(h);if(this._keyModes.shift&&this._keyModes.shift!==b&&(this._keyModes.shift=this._shiftModeAction=null),this._keyModes.shift&&"select"===this._shiftModeAction&&g){for(;this._getIsSelected(a.items)&&a.items.length>0;)e=a.items,a.items=this._getItems(a,c,a.items);a.target=a.items?a.items:e}else g&&f&&!i?(this._keyModes.shift=this._shiftModeAction=null,a.items=this.ui.focus):f&&g?(a.items=this.ui.focus,this._shiftModeAction||(this._shiftModeAction="unselect")):f||(a.target=a.items=this.ui.focus);a.isMultiSelect=!0},e.prototype._findNextTarget=function(a,b){var c="next"===a||"pagedown"===a?"first":"last",d=this.ui.focus?this._getItems(b,a,this.ui.focus):this._getItems(b,c);return null!==d&&0!==d.length||!this.options.get("loop")||(d=this._getItems(b,c)),d},e.prototype._refreshBoxScroll=function(c){var d=a(c),e=c===b,g=e?d[f]():c.clientHeight,h=d.scrollTop(),i=e?0:d.offset().top,j=a(this.ui.focus),k=j[f](),l=e?j.offset().top:j.offset().top-i+h;h>l?d.scrollTop(l):l+k>h+g&&d.scrollTop(l+k-g)},e.prototype._isRange=function(a){return a.shiftKey||a.shiftKey&&a.ctrlKey||a.shiftKey&&a.metaKey},e.prototype._isMulti=function(a){return a.ctrlKey||a.metaKey},e.prototype._mouseHandler=function(b){var c=this.options.get(),d=b.type,e=this._isMulti(b),f=this._isRange(b),g={};if("mouseup"===c.mouseMode){if(g.target=this._getTarget(b),"click"===d||g.target&&"mousedown"===d)return}else{if("click"===d&&!this._mousedownOnItem)return;if("mousedown"!==d&&"click"!==d)return;if(g.target=this._getTarget(b),"mousedown"===d&&g.target&&(!c.multi||!e&&!f||"standard"!==c.mouseMode))return void(this._mousedownOnItem=g.target);if(delete this._mousedownOnItem,!g.target&&"click"===d)return}c.multi&&g.target&&(f&&this.ui.focus?g.items=this._rangeSelect(g):(e||"toggle"===c.mouseMode)&&(g.items=this._multiSelect(g))),g.target&&!g.items&&(g.items=a(g.target)),delete this.ui.solidInitialElem,this._controller(b,g)},e.prototype._mousemoveHandler=function(a){if(!this._isFocusOnHoverPrevented){var b={},c=this._getTarget(a);c?(delete this.ui.solidInitialElem,this._isHovered=!0,c!==this.ui.focus&&(b.target=c,this._controller(a,b))):this._isHovered&&(this._isHovered=!1,this._controller(a,b))}},e.prototype._preventMouseMove=function(){var a=this;this._isFocusOnHoverPrevented=!0,this._focusHoverTimeout&&(clearTimeout(this._focusHoverTimeout),delete this._focusHoverTimeout),this._focusHoverTimeout=setTimeout(function(){delete a._isFocusOnHoverPrevented,delete a._focusHoverTimeout},250)},e._callPublicMethod=function(b){var c,d,f=e.getDataObject(this);if(null===f||void 0===f)throw new Error("Element "+this[0]+" has no plugin "+e.pluginName);if(f[b]&&a.isFunction(f[b])&&(c=f[b]),c&&a.isFunction(c)&&"_"!==b.charAt(0))return d=Array.prototype.slice.call(arguments),d.shift(),c.apply(f,d);throw new Error('Plugin "'+e.pluginName+'" has no method "'+b+'"')},e.prototype.isEnabled=function(){return this._isEnable},e.prototype.option=function(b,c){var d=arguments.length;if(d>0&&"string"==typeof b){if(d>1){var e={};return e[b]=c,this.options.set(e),this.$el}return this.options.get(b)}if(d>0&&a.isPlainObject(b))return this.options.set(b),this.$el;if(0===d)return this.options.get();throw new Error('Format of "option" could be: "option" or "option","name" or "option","name",val or "option",{}')},e.prototype.destroy=function(){this._trigger("destroy"),this._unbindEvents(),this._focusHoverTimeout&&clearTimeout(this._focusHoverTimeout),this.ui.focus&&(a(this.ui.focus).removeClass(this.options.get("focusClass")),delete this.ui.focus),this._selected>0&&this.getSelected().removeClass(this.options.get("selectedClass")),this.$el.removeClass(this.options.get("disabledClass")),this.$el.removeClass(this.options.get("listClass")),this.options.off(),delete this.options,delete this._scrolledElem,delete this.ui.solidInitialElem,this.$el.removeData("plugin_"+e.pluginName),this.$el=null},e.prototype.unselect=function(a){return this.select(a,!0)},e.prototype.select=function(b,c){var d,e;if(c===!0&&void 0===b)e={isTargetWasSelected:!0,isMultiSelect:!0},e.items=this._getItems(e);else if(e={isTargetWasSelected:c?!0:!1,isMultiSelect:!0},void 0!==b&&a.isNumeric(b))e.items=this._getItems(e,b);else{if(d=this._checkIfElem(b),d===!1&&(d=this._checkIfSelector(b)),d===!1)throw new Error('You shold pass DOM element or selector to "select" method.');e.items=null===d?null:d.addClass?d:a(d)}return delete this.ui.solidInitialElem,this._controller(null,e),this.$el},e.prototype.blur=function(){return this._controller(null,{target:null}),this.$el},e.prototype.getSelected=function(a){var b,c=this._getItems({}).filter("."+this.options.get("selectedClass"));if(a){b=[];for(var d=0;d<c.length;d++)b.push(c[d].id||null);return b&&b.length>0?b:null}return c},e.prototype.getSelectedId=function(){return this.getSelected(!0)},e.prototype.focus=function(b){var c;if(arguments.length>0){if(c=a.isNumeric(b)?this._getItems({},b):(c=this._checkIfElem(b))===!1?this._checkIfSelector(b):c,c&&(c.jquery||c.zepto))this._setFocus(c[0]);else if(c===!1)throw new Error("You shold pass DOM element or CSS selector to set focus or nothing to get it.");return this.$el}return this.ui.focus?this.ui.focus:null},e.prototype.scroll=function(){this._preventMouseMove(),this.ui.focus&&(this._scrolledElem&&this._refreshBoxScroll(this._scrolledElem),this._refreshBoxScroll(b))},e.prototype.enable=function(){return this._isEnable=!0,this.$el.removeClass(this.options.get("disabledClass")),this.$el},e.prototype.disable=function(){return this._isEnable=!1,this._isHovered=!1,this.$el.addClass(this.options.get("disabledClass")),this.$el},e.prototype.cancel=function(){return this._isPrevented=!0,this.$el},e.prototype.refresh=function(){var b=this.ui.focus;return b&&!a(b).is(":visible")&&delete this.ui.focus,this._selected=this.getSelected().length,this.$el},a.fn[e.pluginName]=function(a){return a&&a.charAt?e._callPublicMethod.apply(this,arguments):this.each(function(b,c){e.getDataObject(c)||new e(c,a)})},a.fn[e.pluginName].defaults=k}(window.jQuery||window.Zepto,window);
//# sourceMappingURL=selectonic.min.map
/*!
 * jquery.waterfall.js
 * https://github.com/dio-el-claire/jquery.waterfall
 */
(function(e){e.waterfall=function(){var t=[],n=e.Deferred(),r=0;e.each(arguments,function(i,s){t.push(function(){var i=[].slice.apply(arguments),o;if(typeof s=="function"){if(!((o=s.apply(null,i))&&o.promise)){o=e.Deferred()[o===false?"reject":"resolve"](o)}}else if(s&&s.promise){o=s}else{o=e.Deferred()[s===false?"reject":"resolve"](s)}o.fail(function(){n.reject.apply(n,[].slice.apply(arguments))}).done(function(e){r++;i.push(e);r==t.length?n.resolve.apply(n,i):t[r].apply(null,i)})})});t.length?t[0]():n.resolve();return n}})(jQuery);

/**!
 * Sortable 1.15.0
 * @author	RubaXa   <trash@rubaxa.org>
 * @author	owenm    <owen23355@gmail.com>
 * @license MIT
 */
(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
  typeof define === 'function' && define.amd ? define(factory) :
  (global = global || self, global.Sortable = factory());
}(this, (function () { 'use strict';

  function ownKeys(object, enumerableOnly) {
    var keys = Object.keys(object);

    if (Object.getOwnPropertySymbols) {
      var symbols = Object.getOwnPropertySymbols(object);

      if (enumerableOnly) {
        symbols = symbols.filter(function (sym) {
          return Object.getOwnPropertyDescriptor(object, sym).enumerable;
        });
      }

      keys.push.apply(keys, symbols);
    }

    return keys;
  }

  function _objectSpread2(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i] != null ? arguments[i] : {};

      if (i % 2) {
        ownKeys(Object(source), true).forEach(function (key) {
          _defineProperty(target, key, source[key]);
        });
      } else if (Object.getOwnPropertyDescriptors) {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
      } else {
        ownKeys(Object(source)).forEach(function (key) {
          Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
        });
      }
    }

    return target;
  }

  function _typeof(obj) {
    "@babel/helpers - typeof";

    if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
      _typeof = function (obj) {
        return typeof obj;
      };
    } else {
      _typeof = function (obj) {
        return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
      };
    }

    return _typeof(obj);
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _extends() {
    _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    return _extends.apply(this, arguments);
  }

  function _objectWithoutPropertiesLoose(source, excluded) {
    if (source == null) return {};
    var target = {};
    var sourceKeys = Object.keys(source);
    var key, i;

    for (i = 0; i < sourceKeys.length; i++) {
      key = sourceKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      target[key] = source[key];
    }

    return target;
  }

  function _objectWithoutProperties(source, excluded) {
    if (source == null) return {};

    var target = _objectWithoutPropertiesLoose(source, excluded);

    var key, i;

    if (Object.getOwnPropertySymbols) {
      var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

      for (i = 0; i < sourceSymbolKeys.length; i++) {
        key = sourceSymbolKeys[i];
        if (excluded.indexOf(key) >= 0) continue;
        if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
        target[key] = source[key];
      }
    }

    return target;
  }

  function _toConsumableArray(arr) {
    return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
  }

  function _arrayWithoutHoles(arr) {
    if (Array.isArray(arr)) return _arrayLikeToArray(arr);
  }

  function _iterableToArray(iter) {
    if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
  }

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _nonIterableSpread() {
    throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  var version = "1.15.0";

  function userAgent(pattern) {
    if (typeof window !== 'undefined' && window.navigator) {
      return !! /*@__PURE__*/navigator.userAgent.match(pattern);
    }
  }

  var IE11OrLess = userAgent(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i);
  var Edge = userAgent(/Edge/i);
  var FireFox = userAgent(/firefox/i);
  var Safari = userAgent(/safari/i) && !userAgent(/chrome/i) && !userAgent(/android/i);
  var IOS = userAgent(/iP(ad|od|hone)/i);
  var ChromeForAndroid = userAgent(/chrome/i) && userAgent(/android/i);

  var captureMode = {
    capture: false,
    passive: false
  };

  function on(el, event, fn) {
    el.addEventListener(event, fn, !IE11OrLess && captureMode);
  }

  function off(el, event, fn) {
    el.removeEventListener(event, fn, !IE11OrLess && captureMode);
  }

  function matches(
  /**HTMLElement*/
  el,
  /**String*/
  selector) {
    if (!selector) return;
    selector[0] === '>' && (selector = selector.substring(1));

    if (el) {
      try {
        if (el.matches) {
          return el.matches(selector);
        } else if (el.msMatchesSelector) {
          return el.msMatchesSelector(selector);
        } else if (el.webkitMatchesSelector) {
          return el.webkitMatchesSelector(selector);
        }
      } catch (_) {
        return false;
      }
    }

    return false;
  }

  function getParentOrHost(el) {
    return el.host && el !== document && el.host.nodeType ? el.host : el.parentNode;
  }

  function closest(
  /**HTMLElement*/
  el,
  /**String*/
  selector,
  /**HTMLElement*/
  ctx, includeCTX) {
    if (el) {
      ctx = ctx || document;

      do {
        if (selector != null && (selector[0] === '>' ? el.parentNode === ctx && matches(el, selector) : matches(el, selector)) || includeCTX && el === ctx) {
          return el;
        }

        if (el === ctx) break;
        /* jshint boss:true */
      } while (el = getParentOrHost(el));
    }

    return null;
  }

  var R_SPACE = /\s+/g;

  function toggleClass(el, name, state) {
    if (el && name) {
      if (el.classList) {
        el.classList[state ? 'add' : 'remove'](name);
      } else {
        var className = (' ' + el.className + ' ').replace(R_SPACE, ' ').replace(' ' + name + ' ', ' ');
        el.className = (className + (state ? ' ' + name : '')).replace(R_SPACE, ' ');
      }
    }
  }

  function css(el, prop, val) {
    var style = el && el.style;

    if (style) {
      if (val === void 0) {
        if (document.defaultView && document.defaultView.getComputedStyle) {
          val = document.defaultView.getComputedStyle(el, '');
        } else if (el.currentStyle) {
          val = el.currentStyle;
        }

        return prop === void 0 ? val : val[prop];
      } else {
        if (!(prop in style) && prop.indexOf('webkit') === -1) {
          prop = '-webkit-' + prop;
        }

        style[prop] = val + (typeof val === 'string' ? '' : 'px');
      }
    }
  }

  function matrix(el, selfOnly) {
    var appliedTransforms = '';

    if (typeof el === 'string') {
      appliedTransforms = el;
    } else {
      do {
        var transform = css(el, 'transform');

        if (transform && transform !== 'none') {
          appliedTransforms = transform + ' ' + appliedTransforms;
        }
        /* jshint boss:true */

      } while (!selfOnly && (el = el.parentNode));
    }

    var matrixFn = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
    /*jshint -W056 */

    return matrixFn && new matrixFn(appliedTransforms);
  }

  function find(ctx, tagName, iterator) {
    if (ctx) {
      var list = ctx.getElementsByTagName(tagName),
          i = 0,
          n = list.length;

      if (iterator) {
        for (; i < n; i++) {
          iterator(list[i], i);
        }
      }

      return list;
    }

    return [];
  }

  function getWindowScrollingElement() {
    var scrollingElement = document.scrollingElement;

    if (scrollingElement) {
      return scrollingElement;
    } else {
      return document.documentElement;
    }
  }
  /**
   * Returns the "bounding client rect" of given element
   * @param  {HTMLElement} el                       The element whose boundingClientRect is wanted
   * @param  {[Boolean]} relativeToContainingBlock  Whether the rect should be relative to the containing block of (including) the container
   * @param  {[Boolean]} relativeToNonStaticParent  Whether the rect should be relative to the relative parent of (including) the contaienr
   * @param  {[Boolean]} undoScale                  Whether the container's scale() should be undone
   * @param  {[HTMLElement]} container              The parent the element will be placed in
   * @return {Object}                               The boundingClientRect of el, with specified adjustments
   */


  function getRect(el, relativeToContainingBlock, relativeToNonStaticParent, undoScale, container) {
    if (!el.getBoundingClientRect && el !== window) return;
    var elRect, top, left, bottom, right, height, width;

    if (el !== window && el.parentNode && el !== getWindowScrollingElement()) {
      elRect = el.getBoundingClientRect();
      top = elRect.top;
      left = elRect.left;
      bottom = elRect.bottom;
      right = elRect.right;
      height = elRect.height;
      width = elRect.width;
    } else {
      top = 0;
      left = 0;
      bottom = window.innerHeight;
      right = window.innerWidth;
      height = window.innerHeight;
      width = window.innerWidth;
    }

    if ((relativeToContainingBlock || relativeToNonStaticParent) && el !== window) {
      // Adjust for translate()
      container = container || el.parentNode; // solves #1123 (see: https://stackoverflow.com/a/37953806/6088312)
      // Not needed on <= IE11

      if (!IE11OrLess) {
        do {
          if (container && container.getBoundingClientRect && (css(container, 'transform') !== 'none' || relativeToNonStaticParent && css(container, 'position') !== 'static')) {
            var containerRect = container.getBoundingClientRect(); // Set relative to edges of padding box of container

            top -= containerRect.top + parseInt(css(container, 'border-top-width'));
            left -= containerRect.left + parseInt(css(container, 'border-left-width'));
            bottom = top + elRect.height;
            right = left + elRect.width;
            break;
          }
          /* jshint boss:true */

        } while (container = container.parentNode);
      }
    }

    if (undoScale && el !== window) {
      // Adjust for scale()
      var elMatrix = matrix(container || el),
          scaleX = elMatrix && elMatrix.a,
          scaleY = elMatrix && elMatrix.d;

      if (elMatrix) {
        top /= scaleY;
        left /= scaleX;
        width /= scaleX;
        height /= scaleY;
        bottom = top + height;
        right = left + width;
      }
    }

    return {
      top: top,
      left: left,
      bottom: bottom,
      right: right,
      width: width,
      height: height
    };
  }
  /**
   * Checks if a side of an element is scrolled past a side of its parents
   * @param  {HTMLElement}  el           The element who's side being scrolled out of view is in question
   * @param  {String}       elSide       Side of the element in question ('top', 'left', 'right', 'bottom')
   * @param  {String}       parentSide   Side of the parent in question ('top', 'left', 'right', 'bottom')
   * @return {HTMLElement}               The parent scroll element that the el's side is scrolled past, or null if there is no such element
   */


  function isScrolledPast(el, elSide, parentSide) {
    var parent = getParentAutoScrollElement(el, true),
        elSideVal = getRect(el)[elSide];
    /* jshint boss:true */

    while (parent) {
      var parentSideVal = getRect(parent)[parentSide],
          visible = void 0;

      if (parentSide === 'top' || parentSide === 'left') {
        visible = elSideVal >= parentSideVal;
      } else {
        visible = elSideVal <= parentSideVal;
      }

      if (!visible) return parent;
      if (parent === getWindowScrollingElement()) break;
      parent = getParentAutoScrollElement(parent, false);
    }

    return false;
  }
  /**
   * Gets nth child of el, ignoring hidden children, sortable's elements (does not ignore clone if it's visible)
   * and non-draggable elements
   * @param  {HTMLElement} el       The parent element
   * @param  {Number} childNum      The index of the child
   * @param  {Object} options       Parent Sortable's options
   * @return {HTMLElement}          The child at index childNum, or null if not found
   */


  function getChild(el, childNum, options, includeDragEl) {
    var currentChild = 0,
        i = 0,
        children = el.children;

    while (i < children.length) {
      if (children[i].style.display !== 'none' && children[i] !== Sortable.ghost && (includeDragEl || children[i] !== Sortable.dragged) && closest(children[i], options.draggable, el, false)) {
        if (currentChild === childNum) {
          return children[i];
        }

        currentChild++;
      }

      i++;
    }

    return null;
  }
  /**
   * Gets the last child in the el, ignoring ghostEl or invisible elements (clones)
   * @param  {HTMLElement} el       Parent element
   * @param  {selector} selector    Any other elements that should be ignored
   * @return {HTMLElement}          The last child, ignoring ghostEl
   */


  function lastChild(el, selector) {
    var last = el.lastElementChild;

    while (last && (last === Sortable.ghost || css(last, 'display') === 'none' || selector && !matches(last, selector))) {
      last = last.previousElementSibling;
    }

    return last || null;
  }
  /**
   * Returns the index of an element within its parent for a selected set of
   * elements
   * @param  {HTMLElement} el
   * @param  {selector} selector
   * @return {number}
   */


  function index(el, selector) {
    var index = 0;

    if (!el || !el.parentNode) {
      return -1;
    }
    /* jshint boss:true */


    while (el = el.previousElementSibling) {
      if (el.nodeName.toUpperCase() !== 'TEMPLATE' && el !== Sortable.clone && (!selector || matches(el, selector))) {
        index++;
      }
    }

    return index;
  }
  /**
   * Returns the scroll offset of the given element, added with all the scroll offsets of parent elements.
   * The value is returned in real pixels.
   * @param  {HTMLElement} el
   * @return {Array}             Offsets in the format of [left, top]
   */


  function getRelativeScrollOffset(el) {
    var offsetLeft = 0,
        offsetTop = 0,
        winScroller = getWindowScrollingElement();

    if (el) {
      do {
        var elMatrix = matrix(el),
            scaleX = elMatrix.a,
            scaleY = elMatrix.d;
        offsetLeft += el.scrollLeft * scaleX;
        offsetTop += el.scrollTop * scaleY;
      } while (el !== winScroller && (el = el.parentNode));
    }

    return [offsetLeft, offsetTop];
  }
  /**
   * Returns the index of the object within the given array
   * @param  {Array} arr   Array that may or may not hold the object
   * @param  {Object} obj  An object that has a key-value pair unique to and identical to a key-value pair in the object you want to find
   * @return {Number}      The index of the object in the array, or -1
   */


  function indexOfObject(arr, obj) {
    for (var i in arr) {
      if (!arr.hasOwnProperty(i)) continue;

      for (var key in obj) {
        if (obj.hasOwnProperty(key) && obj[key] === arr[i][key]) return Number(i);
      }
    }

    return -1;
  }

  function getParentAutoScrollElement(el, includeSelf) {
    // skip to window
    if (!el || !el.getBoundingClientRect) return getWindowScrollingElement();
    var elem = el;
    var gotSelf = false;

    do {
      // we don't need to get elem css if it isn't even overflowing in the first place (performance)
      if (elem.clientWidth < elem.scrollWidth || elem.clientHeight < elem.scrollHeight) {
        var elemCSS = css(elem);

        if (elem.clientWidth < elem.scrollWidth && (elemCSS.overflowX == 'auto' || elemCSS.overflowX == 'scroll') || elem.clientHeight < elem.scrollHeight && (elemCSS.overflowY == 'auto' || elemCSS.overflowY == 'scroll')) {
          if (!elem.getBoundingClientRect || elem === document.body) return getWindowScrollingElement();
          if (gotSelf || includeSelf) return elem;
          gotSelf = true;
        }
      }
      /* jshint boss:true */

    } while (elem = elem.parentNode);

    return getWindowScrollingElement();
  }

  function extend(dst, src) {
    if (dst && src) {
      for (var key in src) {
        if (src.hasOwnProperty(key)) {
          dst[key] = src[key];
        }
      }
    }

    return dst;
  }

  function isRectEqual(rect1, rect2) {
    return Math.round(rect1.top) === Math.round(rect2.top) && Math.round(rect1.left) === Math.round(rect2.left) && Math.round(rect1.height) === Math.round(rect2.height) && Math.round(rect1.width) === Math.round(rect2.width);
  }

  var _throttleTimeout;

  function throttle(callback, ms) {
    return function () {
      if (!_throttleTimeout) {
        var args = arguments,
            _this = this;

        if (args.length === 1) {
          callback.call(_this, args[0]);
        } else {
          callback.apply(_this, args);
        }

        _throttleTimeout = setTimeout(function () {
          _throttleTimeout = void 0;
        }, ms);
      }
    };
  }

  function cancelThrottle() {
    clearTimeout(_throttleTimeout);
    _throttleTimeout = void 0;
  }

  function scrollBy(el, x, y) {
    el.scrollLeft += x;
    el.scrollTop += y;
  }

  function clone(el) {
    var Polymer = window.Polymer;
    var $ = window.jQuery || window.Zepto;

    if (Polymer && Polymer.dom) {
      return Polymer.dom(el).cloneNode(true);
    } else if ($) {
      return $(el).clone(true)[0];
    } else {
      return el.cloneNode(true);
    }
  }

  function setRect(el, rect) {
    css(el, 'position', 'absolute');
    css(el, 'top', rect.top);
    css(el, 'left', rect.left);
    css(el, 'width', rect.width);
    css(el, 'height', rect.height);
  }

  function unsetRect(el) {
    css(el, 'position', '');
    css(el, 'top', '');
    css(el, 'left', '');
    css(el, 'width', '');
    css(el, 'height', '');
  }

  var expando = 'Sortable' + new Date().getTime();

  function AnimationStateManager() {
    var animationStates = [],
        animationCallbackId;
    return {
      captureAnimationState: function captureAnimationState() {
        animationStates = [];
        if (!this.options.animation) return;
        var children = [].slice.call(this.el.children);
        children.forEach(function (child) {
          if (css(child, 'display') === 'none' || child === Sortable.ghost) return;
          animationStates.push({
            target: child,
            rect: getRect(child)
          });

          var fromRect = _objectSpread2({}, animationStates[animationStates.length - 1].rect); // If animating: compensate for current animation


          if (child.thisAnimationDuration) {
            var childMatrix = matrix(child, true);

            if (childMatrix) {
              fromRect.top -= childMatrix.f;
              fromRect.left -= childMatrix.e;
            }
          }

          child.fromRect = fromRect;
        });
      },
      addAnimationState: function addAnimationState(state) {
        animationStates.push(state);
      },
      removeAnimationState: function removeAnimationState(target) {
        animationStates.splice(indexOfObject(animationStates, {
          target: target
        }), 1);
      },
      animateAll: function animateAll(callback) {
        var _this = this;

        if (!this.options.animation) {
          clearTimeout(animationCallbackId);
          if (typeof callback === 'function') callback();
          return;
        }

        var animating = false,
            animationTime = 0;
        animationStates.forEach(function (state) {
          var time = 0,
              target = state.target,
              fromRect = target.fromRect,
              toRect = getRect(target),
              prevFromRect = target.prevFromRect,
              prevToRect = target.prevToRect,
              animatingRect = state.rect,
              targetMatrix = matrix(target, true);

          if (targetMatrix) {
            // Compensate for current animation
            toRect.top -= targetMatrix.f;
            toRect.left -= targetMatrix.e;
          }

          target.toRect = toRect;

          if (target.thisAnimationDuration) {
            // Could also check if animatingRect is between fromRect and toRect
            if (isRectEqual(prevFromRect, toRect) && !isRectEqual(fromRect, toRect) && // Make sure animatingRect is on line between toRect & fromRect
            (animatingRect.top - toRect.top) / (animatingRect.left - toRect.left) === (fromRect.top - toRect.top) / (fromRect.left - toRect.left)) {
              // If returning to same place as started from animation and on same axis
              time = calculateRealTime(animatingRect, prevFromRect, prevToRect, _this.options);
            }
          } // if fromRect != toRect: animate


          if (!isRectEqual(toRect, fromRect)) {
            target.prevFromRect = fromRect;
            target.prevToRect = toRect;

            if (!time) {
              time = _this.options.animation;
            }

            _this.animate(target, animatingRect, toRect, time);
          }

          if (time) {
            animating = true;
            animationTime = Math.max(animationTime, time);
            clearTimeout(target.animationResetTimer);
            target.animationResetTimer = setTimeout(function () {
              target.animationTime = 0;
              target.prevFromRect = null;
              target.fromRect = null;
              target.prevToRect = null;
              target.thisAnimationDuration = null;
            }, time);
            target.thisAnimationDuration = time;
          }
        });
        clearTimeout(animationCallbackId);

        if (!animating) {
          if (typeof callback === 'function') callback();
        } else {
          animationCallbackId = setTimeout(function () {
            if (typeof callback === 'function') callback();
          }, animationTime);
        }

        animationStates = [];
      },
      animate: function animate(target, currentRect, toRect, duration) {
        if (duration) {
          css(target, 'transition', '');
          css(target, 'transform', '');
          var elMatrix = matrix(this.el),
              scaleX = elMatrix && elMatrix.a,
              scaleY = elMatrix && elMatrix.d,
              translateX = (currentRect.left - toRect.left) / (scaleX || 1),
              translateY = (currentRect.top - toRect.top) / (scaleY || 1);
          target.animatingX = !!translateX;
          target.animatingY = !!translateY;
          css(target, 'transform', 'translate3d(' + translateX + 'px,' + translateY + 'px,0)');
          this.forRepaintDummy = repaint(target); // repaint

          css(target, 'transition', 'transform ' + duration + 'ms' + (this.options.easing ? ' ' + this.options.easing : ''));
          css(target, 'transform', 'translate3d(0,0,0)');
          typeof target.animated === 'number' && clearTimeout(target.animated);
          target.animated = setTimeout(function () {
            css(target, 'transition', '');
            css(target, 'transform', '');
            target.animated = false;
            target.animatingX = false;
            target.animatingY = false;
          }, duration);
        }
      }
    };
  }

  function repaint(target) {
    return target.offsetWidth;
  }

  function calculateRealTime(animatingRect, fromRect, toRect, options) {
    return Math.sqrt(Math.pow(fromRect.top - animatingRect.top, 2) + Math.pow(fromRect.left - animatingRect.left, 2)) / Math.sqrt(Math.pow(fromRect.top - toRect.top, 2) + Math.pow(fromRect.left - toRect.left, 2)) * options.animation;
  }

  var plugins = [];
  var defaults = {
    initializeByDefault: true
  };
  var PluginManager = {
    mount: function mount(plugin) {
      // Set default static properties
      for (var option in defaults) {
        if (defaults.hasOwnProperty(option) && !(option in plugin)) {
          plugin[option] = defaults[option];
        }
      }

      plugins.forEach(function (p) {
        if (p.pluginName === plugin.pluginName) {
          throw "Sortable: Cannot mount plugin ".concat(plugin.pluginName, " more than once");
        }
      });
      plugins.push(plugin);
    },
    pluginEvent: function pluginEvent(eventName, sortable, evt) {
      var _this = this;

      this.eventCanceled = false;

      evt.cancel = function () {
        _this.eventCanceled = true;
      };

      var eventNameGlobal = eventName + 'Global';
      plugins.forEach(function (plugin) {
        if (!sortable[plugin.pluginName]) return; // Fire global events if it exists in this sortable

        if (sortable[plugin.pluginName][eventNameGlobal]) {
          sortable[plugin.pluginName][eventNameGlobal](_objectSpread2({
            sortable: sortable
          }, evt));
        } // Only fire plugin event if plugin is enabled in this sortable,
        // and plugin has event defined


        if (sortable.options[plugin.pluginName] && sortable[plugin.pluginName][eventName]) {
          sortable[plugin.pluginName][eventName](_objectSpread2({
            sortable: sortable
          }, evt));
        }
      });
    },
    initializePlugins: function initializePlugins(sortable, el, defaults, options) {
      plugins.forEach(function (plugin) {
        var pluginName = plugin.pluginName;
        if (!sortable.options[pluginName] && !plugin.initializeByDefault) return;
        var initialized = new plugin(sortable, el, sortable.options);
        initialized.sortable = sortable;
        initialized.options = sortable.options;
        sortable[pluginName] = initialized; // Add default options from plugin

        _extends(defaults, initialized.defaults);
      });

      for (var option in sortable.options) {
        if (!sortable.options.hasOwnProperty(option)) continue;
        var modified = this.modifyOption(sortable, option, sortable.options[option]);

        if (typeof modified !== 'undefined') {
          sortable.options[option] = modified;
        }
      }
    },
    getEventProperties: function getEventProperties(name, sortable) {
      var eventProperties = {};
      plugins.forEach(function (plugin) {
        if (typeof plugin.eventProperties !== 'function') return;

        _extends(eventProperties, plugin.eventProperties.call(sortable[plugin.pluginName], name));
      });
      return eventProperties;
    },
    modifyOption: function modifyOption(sortable, name, value) {
      var modifiedValue;
      plugins.forEach(function (plugin) {
        // Plugin must exist on the Sortable
        if (!sortable[plugin.pluginName]) return; // If static option listener exists for this option, call in the context of the Sortable's instance of this plugin

        if (plugin.optionListeners && typeof plugin.optionListeners[name] === 'function') {
          modifiedValue = plugin.optionListeners[name].call(sortable[plugin.pluginName], value);
        }
      });
      return modifiedValue;
    }
  };

  function dispatchEvent(_ref) {
    var sortable = _ref.sortable,
        rootEl = _ref.rootEl,
        name = _ref.name,
        targetEl = _ref.targetEl,
        cloneEl = _ref.cloneEl,
        toEl = _ref.toEl,
        fromEl = _ref.fromEl,
        oldIndex = _ref.oldIndex,
        newIndex = _ref.newIndex,
        oldDraggableIndex = _ref.oldDraggableIndex,
        newDraggableIndex = _ref.newDraggableIndex,
        originalEvent = _ref.originalEvent,
        putSortable = _ref.putSortable,
        extraEventProperties = _ref.extraEventProperties;
    sortable = sortable || rootEl && rootEl[expando];
    if (!sortable) return;
    var evt,
        options = sortable.options,
        onName = 'on' + name.charAt(0).toUpperCase() + name.substr(1); // Support for new CustomEvent feature

    if (window.CustomEvent && !IE11OrLess && !Edge) {
      evt = new CustomEvent(name, {
        bubbles: true,
        cancelable: true
      });
    } else {
      evt = document.createEvent('Event');
      evt.initEvent(name, true, true);
    }

    evt.to = toEl || rootEl;
    evt.from = fromEl || rootEl;
    evt.item = targetEl || rootEl;
    evt.clone = cloneEl;
    evt.oldIndex = oldIndex;
    evt.newIndex = newIndex;
    evt.oldDraggableIndex = oldDraggableIndex;
    evt.newDraggableIndex = newDraggableIndex;
    evt.originalEvent = originalEvent;
    evt.pullMode = putSortable ? putSortable.lastPutMode : undefined;

    var allEventProperties = _objectSpread2(_objectSpread2({}, extraEventProperties), PluginManager.getEventProperties(name, sortable));

    for (var option in allEventProperties) {
      evt[option] = allEventProperties[option];
    }

    if (rootEl) {
      rootEl.dispatchEvent(evt);
    }

    if (options[onName]) {
      options[onName].call(sortable, evt);
    }
  }

  var _excluded = ["evt"];

  var pluginEvent = function pluginEvent(eventName, sortable) {
    var _ref = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
        originalEvent = _ref.evt,
        data = _objectWithoutProperties(_ref, _excluded);

    PluginManager.pluginEvent.bind(Sortable)(eventName, sortable, _objectSpread2({
      dragEl: dragEl,
      parentEl: parentEl,
      ghostEl: ghostEl,
      rootEl: rootEl,
      nextEl: nextEl,
      lastDownEl: lastDownEl,
      cloneEl: cloneEl,
      cloneHidden: cloneHidden,
      dragStarted: moved,
      putSortable: putSortable,
      activeSortable: Sortable.active,
      originalEvent: originalEvent,
      oldIndex: oldIndex,
      oldDraggableIndex: oldDraggableIndex,
      newIndex: newIndex,
      newDraggableIndex: newDraggableIndex,
      hideGhostForTarget: _hideGhostForTarget,
      unhideGhostForTarget: _unhideGhostForTarget,
      cloneNowHidden: function cloneNowHidden() {
        cloneHidden = true;
      },
      cloneNowShown: function cloneNowShown() {
        cloneHidden = false;
      },
      dispatchSortableEvent: function dispatchSortableEvent(name) {
        _dispatchEvent({
          sortable: sortable,
          name: name,
          originalEvent: originalEvent
        });
      }
    }, data));
  };

  function _dispatchEvent(info) {
    dispatchEvent(_objectSpread2({
      putSortable: putSortable,
      cloneEl: cloneEl,
      targetEl: dragEl,
      rootEl: rootEl,
      oldIndex: oldIndex,
      oldDraggableIndex: oldDraggableIndex,
      newIndex: newIndex,
      newDraggableIndex: newDraggableIndex
    }, info));
  }

  var dragEl,
      parentEl,
      ghostEl,
      rootEl,
      nextEl,
      lastDownEl,
      cloneEl,
      cloneHidden,
      oldIndex,
      newIndex,
      oldDraggableIndex,
      newDraggableIndex,
      activeGroup,
      putSortable,
      awaitingDragStarted = false,
      ignoreNextClick = false,
      sortables = [],
      tapEvt,
      touchEvt,
      lastDx,
      lastDy,
      tapDistanceLeft,
      tapDistanceTop,
      moved,
      lastTarget,
      lastDirection,
      pastFirstInvertThresh = false,
      isCircumstantialInvert = false,
      targetMoveDistance,
      // For positioning ghost absolutely
  ghostRelativeParent,
      ghostRelativeParentInitialScroll = [],
      // (left, top)
  _silent = false,
      savedInputChecked = [];
  /** @const */

  var documentExists = typeof document !== 'undefined',
      PositionGhostAbsolutely = IOS,
      CSSFloatProperty = Edge || IE11OrLess ? 'cssFloat' : 'float',
      // This will not pass for IE9, because IE9 DnD only works on anchors
  supportDraggable = documentExists && !ChromeForAndroid && !IOS && 'draggable' in document.createElement('div'),
      supportCssPointerEvents = function () {
    if (!documentExists) return; // false when <= IE11

    if (IE11OrLess) {
      return false;
    }

    var el = document.createElement('x');
    el.style.cssText = 'pointer-events:auto';
    return el.style.pointerEvents === 'auto';
  }(),
      _detectDirection = function _detectDirection(el, options) {
    var elCSS = css(el),
        elWidth = parseInt(elCSS.width) - parseInt(elCSS.paddingLeft) - parseInt(elCSS.paddingRight) - parseInt(elCSS.borderLeftWidth) - parseInt(elCSS.borderRightWidth),
        child1 = getChild(el, 0, options),
        child2 = getChild(el, 1, options),
        firstChildCSS = child1 && css(child1),
        secondChildCSS = child2 && css(child2),
        firstChildWidth = firstChildCSS && parseInt(firstChildCSS.marginLeft) + parseInt(firstChildCSS.marginRight) + getRect(child1).width,
        secondChildWidth = secondChildCSS && parseInt(secondChildCSS.marginLeft) + parseInt(secondChildCSS.marginRight) + getRect(child2).width;

    if (elCSS.display === 'flex') {
      return elCSS.flexDirection === 'column' || elCSS.flexDirection === 'column-reverse' ? 'vertical' : 'horizontal';
    }

    if (elCSS.display === 'grid') {
      return elCSS.gridTemplateColumns.split(' ').length <= 1 ? 'vertical' : 'horizontal';
    }

    if (child1 && firstChildCSS["float"] && firstChildCSS["float"] !== 'none') {
      var touchingSideChild2 = firstChildCSS["float"] === 'left' ? 'left' : 'right';
      return child2 && (secondChildCSS.clear === 'both' || secondChildCSS.clear === touchingSideChild2) ? 'vertical' : 'horizontal';
    }

    return child1 && (firstChildCSS.display === 'block' || firstChildCSS.display === 'flex' || firstChildCSS.display === 'table' || firstChildCSS.display === 'grid' || firstChildWidth >= elWidth && elCSS[CSSFloatProperty] === 'none' || child2 && elCSS[CSSFloatProperty] === 'none' && firstChildWidth + secondChildWidth > elWidth) ? 'vertical' : 'horizontal';
  },
      _dragElInRowColumn = function _dragElInRowColumn(dragRect, targetRect, vertical) {
    var dragElS1Opp = vertical ? dragRect.left : dragRect.top,
        dragElS2Opp = vertical ? dragRect.right : dragRect.bottom,
        dragElOppLength = vertical ? dragRect.width : dragRect.height,
        targetS1Opp = vertical ? targetRect.left : targetRect.top,
        targetS2Opp = vertical ? targetRect.right : targetRect.bottom,
        targetOppLength = vertical ? targetRect.width : targetRect.height;
    return dragElS1Opp === targetS1Opp || dragElS2Opp === targetS2Opp || dragElS1Opp + dragElOppLength / 2 === targetS1Opp + targetOppLength / 2;
  },

  /**
   * Detects first nearest empty sortable to X and Y position using emptyInsertThreshold.
   * @param  {Number} x      X position
   * @param  {Number} y      Y position
   * @return {HTMLElement}   Element of the first found nearest Sortable
   */
  _detectNearestEmptySortable = function _detectNearestEmptySortable(x, y) {
    var ret;
    sortables.some(function (sortable) {
      var threshold = sortable[expando].options.emptyInsertThreshold;
      if (!threshold || lastChild(sortable)) return;
      var rect = getRect(sortable),
          insideHorizontally = x >= rect.left - threshold && x <= rect.right + threshold,
          insideVertically = y >= rect.top - threshold && y <= rect.bottom + threshold;

      if (insideHorizontally && insideVertically) {
        return ret = sortable;
      }
    });
    return ret;
  },
      _prepareGroup = function _prepareGroup(options) {
    function toFn(value, pull) {
      return function (to, from, dragEl, evt) {
        var sameGroup = to.options.group.name && from.options.group.name && to.options.group.name === from.options.group.name;

        if (value == null && (pull || sameGroup)) {
          // Default pull value
          // Default pull and put value if same group
          return true;
        } else if (value == null || value === false) {
          return false;
        } else if (pull && value === 'clone') {
          return value;
        } else if (typeof value === 'function') {
          return toFn(value(to, from, dragEl, evt), pull)(to, from, dragEl, evt);
        } else {
          var otherGroup = (pull ? to : from).options.group.name;
          return value === true || typeof value === 'string' && value === otherGroup || value.join && value.indexOf(otherGroup) > -1;
        }
      };
    }

    var group = {};
    var originalGroup = options.group;

    if (!originalGroup || _typeof(originalGroup) != 'object') {
      originalGroup = {
        name: originalGroup
      };
    }

    group.name = originalGroup.name;
    group.checkPull = toFn(originalGroup.pull, true);
    group.checkPut = toFn(originalGroup.put);
    group.revertClone = originalGroup.revertClone;
    options.group = group;
  },
      _hideGhostForTarget = function _hideGhostForTarget() {
    if (!supportCssPointerEvents && ghostEl) {
      css(ghostEl, 'display', 'none');
    }
  },
      _unhideGhostForTarget = function _unhideGhostForTarget() {
    if (!supportCssPointerEvents && ghostEl) {
      css(ghostEl, 'display', '');
    }
  }; // #1184 fix - Prevent click event on fallback if dragged but item not changed position


  if (documentExists && !ChromeForAndroid) {
    document.addEventListener('click', function (evt) {
      if (ignoreNextClick) {
        evt.preventDefault();
        evt.stopPropagation && evt.stopPropagation();
        evt.stopImmediatePropagation && evt.stopImmediatePropagation();
        ignoreNextClick = false;
        return false;
      }
    }, true);
  }

  var nearestEmptyInsertDetectEvent = function nearestEmptyInsertDetectEvent(evt) {
    if (dragEl) {
      evt = evt.touches ? evt.touches[0] : evt;

      var nearest = _detectNearestEmptySortable(evt.clientX, evt.clientY);

      if (nearest) {
        // Create imitation event
        var event = {};

        for (var i in evt) {
          if (evt.hasOwnProperty(i)) {
            event[i] = evt[i];
          }
        }

        event.target = event.rootEl = nearest;
        event.preventDefault = void 0;
        event.stopPropagation = void 0;

        nearest[expando]._onDragOver(event);
      }
    }
  };

  var _checkOutsideTargetEl = function _checkOutsideTargetEl(evt) {
    if (dragEl) {
      dragEl.parentNode[expando]._isOutsideThisEl(evt.target);
    }
  };
  /**
   * @class  Sortable
   * @param  {HTMLElement}  el
   * @param  {Object}       [options]
   */


  function Sortable(el, options) {
    if (!(el && el.nodeType && el.nodeType === 1)) {
      throw "Sortable: `el` must be an HTMLElement, not ".concat({}.toString.call(el));
    }

    this.el = el; // root element

    this.options = options = _extends({}, options); // Export instance

    el[expando] = this;
    var defaults = {
      group: null,
      sort: true,
      disabled: false,
      store: null,
      handle: null,
      draggable: /^[uo]l$/i.test(el.nodeName) ? '>li' : '>*',
      swapThreshold: 1,
      // percentage; 0 <= x <= 1
      invertSwap: false,
      // invert always
      invertedSwapThreshold: null,
      // will be set to same as swapThreshold if default
      removeCloneOnHide: true,
      direction: function direction() {
        return _detectDirection(el, this.options);
      },
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      ignore: 'a, img',
      filter: null,
      preventOnFilter: true,
      animation: 0,
      easing: null,
      setData: function setData(dataTransfer, dragEl) {
        dataTransfer.setData('Text', dragEl.textContent);
      },
      dropBubble: false,
      dragoverBubble: false,
      dataIdAttr: 'data-id',
      delay: 0,
      delayOnTouchOnly: false,
      touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
      forceFallback: false,
      fallbackClass: 'sortable-fallback',
      fallbackOnBody: false,
      fallbackTolerance: 0,
      fallbackOffset: {
        x: 0,
        y: 0
      },
      supportPointer: Sortable.supportPointer !== false && 'PointerEvent' in window && !Safari,
      emptyInsertThreshold: 5
    };
    PluginManager.initializePlugins(this, el, defaults); // Set default options

    for (var name in defaults) {
      !(name in options) && (options[name] = defaults[name]);
    }

    _prepareGroup(options); // Bind all private methods


    for (var fn in this) {
      if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
        this[fn] = this[fn].bind(this);
      }
    } // Setup drag mode


    this.nativeDraggable = options.forceFallback ? false : supportDraggable;

    if (this.nativeDraggable) {
      // Touch start threshold cannot be greater than the native dragstart threshold
      this.options.touchStartThreshold = 1;
    } // Bind events


    if (options.supportPointer) {
      on(el, 'pointerdown', this._onTapStart);
    } else {
      on(el, 'mousedown', this._onTapStart);
      on(el, 'touchstart', this._onTapStart);
    }

    if (this.nativeDraggable) {
      on(el, 'dragover', this);
      on(el, 'dragenter', this);
    }

    sortables.push(this.el); // Restore sorting

    options.store && options.store.get && this.sort(options.store.get(this) || []); // Add animation state manager

    _extends(this, AnimationStateManager());
  }

  Sortable.prototype =
  /** @lends Sortable.prototype */
  {
    constructor: Sortable,
    _isOutsideThisEl: function _isOutsideThisEl(target) {
      if (!this.el.contains(target) && target !== this.el) {
        lastTarget = null;
      }
    },
    _getDirection: function _getDirection(evt, target) {
      return typeof this.options.direction === 'function' ? this.options.direction.call(this, evt, target, dragEl) : this.options.direction;
    },
    _onTapStart: function _onTapStart(
    /** Event|TouchEvent */
    evt) {
      if (!evt.cancelable) return;

      var _this = this,
          el = this.el,
          options = this.options,
          preventOnFilter = options.preventOnFilter,
          type = evt.type,
          touch = evt.touches && evt.touches[0] || evt.pointerType && evt.pointerType === 'touch' && evt,
          target = (touch || evt).target,
          originalTarget = evt.target.shadowRoot && (evt.path && evt.path[0] || evt.composedPath && evt.composedPath()[0]) || target,
          filter = options.filter;

      _saveInputCheckedState(el); // Don't trigger start event when an element is been dragged, otherwise the evt.oldindex always wrong when set option.group.


      if (dragEl) {
        return;
      }

      if (/mousedown|pointerdown/.test(type) && evt.button !== 0 || options.disabled) {
        return; // only left button and enabled
      } // cancel dnd if original target is content editable


      if (originalTarget.isContentEditable) {
        return;
      } // Safari ignores further event handling after mousedown


      if (!this.nativeDraggable && Safari && target && target.tagName.toUpperCase() === 'SELECT') {
        return;
      }

      target = closest(target, options.draggable, el, false);

      if (target && target.animated) {
        return;
      }

      if (lastDownEl === target) {
        // Ignoring duplicate `down`
        return;
      } // Get the index of the dragged element within its parent


      oldIndex = index(target);
      oldDraggableIndex = index(target, options.draggable); // Check filter

      if (typeof filter === 'function') {
        if (filter.call(this, evt, target, this)) {
          _dispatchEvent({
            sortable: _this,
            rootEl: originalTarget,
            name: 'filter',
            targetEl: target,
            toEl: el,
            fromEl: el
          });

          pluginEvent('filter', _this, {
            evt: evt
          });
          preventOnFilter && evt.cancelable && evt.preventDefault();
          return; // cancel dnd
        }
      } else if (filter) {
        filter = filter.split(',').some(function (criteria) {
          criteria = closest(originalTarget, criteria.trim(), el, false);

          if (criteria) {
            _dispatchEvent({
              sortable: _this,
              rootEl: criteria,
              name: 'filter',
              targetEl: target,
              fromEl: el,
              toEl: el
            });

            pluginEvent('filter', _this, {
              evt: evt
            });
            return true;
          }
        });

        if (filter) {
          preventOnFilter && evt.cancelable && evt.preventDefault();
          return; // cancel dnd
        }
      }

      if (options.handle && !closest(originalTarget, options.handle, el, false)) {
        return;
      } // Prepare `dragstart`


      this._prepareDragStart(evt, touch, target);
    },
    _prepareDragStart: function _prepareDragStart(
    /** Event */
    evt,
    /** Touch */
    touch,
    /** HTMLElement */
    target) {
      var _this = this,
          el = _this.el,
          options = _this.options,
          ownerDocument = el.ownerDocument,
          dragStartFn;

      if (target && !dragEl && target.parentNode === el) {
        var dragRect = getRect(target);
        rootEl = el;
        dragEl = target;
        parentEl = dragEl.parentNode;
        nextEl = dragEl.nextSibling;
        lastDownEl = target;
        activeGroup = options.group;
        Sortable.dragged = dragEl;
        tapEvt = {
          target: dragEl,
          clientX: (touch || evt).clientX,
          clientY: (touch || evt).clientY
        };
        tapDistanceLeft = tapEvt.clientX - dragRect.left;
        tapDistanceTop = tapEvt.clientY - dragRect.top;
        this._lastX = (touch || evt).clientX;
        this._lastY = (touch || evt).clientY;
        dragEl.style['will-change'] = 'all';

        dragStartFn = function dragStartFn() {
          pluginEvent('delayEnded', _this, {
            evt: evt
          });

          if (Sortable.eventCanceled) {
            _this._onDrop();

            return;
          } // Delayed drag has been triggered
          // we can re-enable the events: touchmove/mousemove


          _this._disableDelayedDragEvents();

          if (!FireFox && _this.nativeDraggable) {
            dragEl.draggable = true;
          } // Bind the events: dragstart/dragend


          _this._triggerDragStart(evt, touch); // Drag start event


          _dispatchEvent({
            sortable: _this,
            name: 'choose',
            originalEvent: evt
          }); // Chosen item


          toggleClass(dragEl, options.chosenClass, true);
        }; // Disable "draggable"


        options.ignore.split(',').forEach(function (criteria) {
          find(dragEl, criteria.trim(), _disableDraggable);
        });
        on(ownerDocument, 'dragover', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'mousemove', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'touchmove', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'mouseup', _this._onDrop);
        on(ownerDocument, 'touchend', _this._onDrop);
        on(ownerDocument, 'touchcancel', _this._onDrop); // Make dragEl draggable (must be before delay for FireFox)

        if (FireFox && this.nativeDraggable) {
          this.options.touchStartThreshold = 4;
          dragEl.draggable = true;
        }

        pluginEvent('delayStart', this, {
          evt: evt
        }); // Delay is impossible for native DnD in Edge or IE

        if (options.delay && (!options.delayOnTouchOnly || touch) && (!this.nativeDraggable || !(Edge || IE11OrLess))) {
          if (Sortable.eventCanceled) {
            this._onDrop();

            return;
          } // If the user moves the pointer or let go the click or touch
          // before the delay has been reached:
          // disable the delayed drag


          on(ownerDocument, 'mouseup', _this._disableDelayedDrag);
          on(ownerDocument, 'touchend', _this._disableDelayedDrag);
          on(ownerDocument, 'touchcancel', _this._disableDelayedDrag);
          on(ownerDocument, 'mousemove', _this._delayedDragTouchMoveHandler);
          on(ownerDocument, 'touchmove', _this._delayedDragTouchMoveHandler);
          options.supportPointer && on(ownerDocument, 'pointermove', _this._delayedDragTouchMoveHandler);
          _this._dragStartTimer = setTimeout(dragStartFn, options.delay);
        } else {
          dragStartFn();
        }
      }
    },
    _delayedDragTouchMoveHandler: function _delayedDragTouchMoveHandler(
    /** TouchEvent|PointerEvent **/
    e) {
      var touch = e.touches ? e.touches[0] : e;

      if (Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1))) {
        this._disableDelayedDrag();
      }
    },
    _disableDelayedDrag: function _disableDelayedDrag() {
      dragEl && _disableDraggable(dragEl);
      clearTimeout(this._dragStartTimer);

      this._disableDelayedDragEvents();
    },
    _disableDelayedDragEvents: function _disableDelayedDragEvents() {
      var ownerDocument = this.el.ownerDocument;
      off(ownerDocument, 'mouseup', this._disableDelayedDrag);
      off(ownerDocument, 'touchend', this._disableDelayedDrag);
      off(ownerDocument, 'touchcancel', this._disableDelayedDrag);
      off(ownerDocument, 'mousemove', this._delayedDragTouchMoveHandler);
      off(ownerDocument, 'touchmove', this._delayedDragTouchMoveHandler);
      off(ownerDocument, 'pointermove', this._delayedDragTouchMoveHandler);
    },
    _triggerDragStart: function _triggerDragStart(
    /** Event */
    evt,
    /** Touch */
    touch) {
      touch = touch || evt.pointerType == 'touch' && evt;

      if (!this.nativeDraggable || touch) {
        if (this.options.supportPointer) {
          on(document, 'pointermove', this._onTouchMove);
        } else if (touch) {
          on(document, 'touchmove', this._onTouchMove);
        } else {
          on(document, 'mousemove', this._onTouchMove);
        }
      } else {
        on(dragEl, 'dragend', this);
        on(rootEl, 'dragstart', this._onDragStart);
      }

      try {
        if (document.selection) {
          // Timeout neccessary for IE9
          _nextTick(function () {
            document.selection.empty();
          });
        } else {
          window.getSelection().removeAllRanges();
        }
      } catch (err) {}
    },
    _dragStarted: function _dragStarted(fallback, evt) {

      awaitingDragStarted = false;

      if (rootEl && dragEl) {
        pluginEvent('dragStarted', this, {
          evt: evt
        });

        if (this.nativeDraggable) {
          on(document, 'dragover', _checkOutsideTargetEl);
        }

        var options = this.options; // Apply effect

        !fallback && toggleClass(dragEl, options.dragClass, false);
        toggleClass(dragEl, options.ghostClass, true);
        Sortable.active = this;
        fallback && this._appendGhost(); // Drag start event

        _dispatchEvent({
          sortable: this,
          name: 'start',
          originalEvent: evt
        });
      } else {
        this._nulling();
      }
    },
    _emulateDragOver: function _emulateDragOver() {
      if (touchEvt) {
        this._lastX = touchEvt.clientX;
        this._lastY = touchEvt.clientY;

        _hideGhostForTarget();

        var target = document.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
        var parent = target;

        while (target && target.shadowRoot) {
          target = target.shadowRoot.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
          if (target === parent) break;
          parent = target;
        }

        dragEl.parentNode[expando]._isOutsideThisEl(target);

        if (parent) {
          do {
            if (parent[expando]) {
              var inserted = void 0;
              inserted = parent[expando]._onDragOver({
                clientX: touchEvt.clientX,
                clientY: touchEvt.clientY,
                target: target,
                rootEl: parent
              });

              if (inserted && !this.options.dragoverBubble) {
                break;
              }
            }

            target = parent; // store last element
          }
          /* jshint boss:true */
          while (parent = parent.parentNode);
        }

        _unhideGhostForTarget();
      }
    },
    _onTouchMove: function _onTouchMove(
    /**TouchEvent*/
    evt) {
      if (tapEvt) {
        var options = this.options,
            fallbackTolerance = options.fallbackTolerance,
            fallbackOffset = options.fallbackOffset,
            touch = evt.touches ? evt.touches[0] : evt,
            ghostMatrix = ghostEl && matrix(ghostEl, true),
            scaleX = ghostEl && ghostMatrix && ghostMatrix.a,
            scaleY = ghostEl && ghostMatrix && ghostMatrix.d,
            relativeScrollOffset = PositionGhostAbsolutely && ghostRelativeParent && getRelativeScrollOffset(ghostRelativeParent),
            dx = (touch.clientX - tapEvt.clientX + fallbackOffset.x) / (scaleX || 1) + (relativeScrollOffset ? relativeScrollOffset[0] - ghostRelativeParentInitialScroll[0] : 0) / (scaleX || 1),
            dy = (touch.clientY - tapEvt.clientY + fallbackOffset.y) / (scaleY || 1) + (relativeScrollOffset ? relativeScrollOffset[1] - ghostRelativeParentInitialScroll[1] : 0) / (scaleY || 1); // only set the status to dragging, when we are actually dragging

        if (!Sortable.active && !awaitingDragStarted) {
          if (fallbackTolerance && Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) < fallbackTolerance) {
            return;
          }

          this._onDragStart(evt, true);
        }

        if (ghostEl) {
          if (ghostMatrix) {
            ghostMatrix.e += dx - (lastDx || 0);
            ghostMatrix.f += dy - (lastDy || 0);
          } else {
            ghostMatrix = {
              a: 1,
              b: 0,
              c: 0,
              d: 1,
              e: dx,
              f: dy
            };
          }

          var cssMatrix = "matrix(".concat(ghostMatrix.a, ",").concat(ghostMatrix.b, ",").concat(ghostMatrix.c, ",").concat(ghostMatrix.d, ",").concat(ghostMatrix.e, ",").concat(ghostMatrix.f, ")");
          css(ghostEl, 'webkitTransform', cssMatrix);
          css(ghostEl, 'mozTransform', cssMatrix);
          css(ghostEl, 'msTransform', cssMatrix);
          css(ghostEl, 'transform', cssMatrix);
          lastDx = dx;
          lastDy = dy;
          touchEvt = touch;
        }

        evt.cancelable && evt.preventDefault();
      }
    },
    _appendGhost: function _appendGhost() {
      // Bug if using scale(): https://stackoverflow.com/questions/2637058
      // Not being adjusted for
      if (!ghostEl) {
        var container = this.options.fallbackOnBody ? document.body : rootEl,
            rect = getRect(dragEl, true, PositionGhostAbsolutely, true, container),
            options = this.options; // Position absolutely

        if (PositionGhostAbsolutely) {
          // Get relatively positioned parent
          ghostRelativeParent = container;

          while (css(ghostRelativeParent, 'position') === 'static' && css(ghostRelativeParent, 'transform') === 'none' && ghostRelativeParent !== document) {
            ghostRelativeParent = ghostRelativeParent.parentNode;
          }

          if (ghostRelativeParent !== document.body && ghostRelativeParent !== document.documentElement) {
            if (ghostRelativeParent === document) ghostRelativeParent = getWindowScrollingElement();
            rect.top += ghostRelativeParent.scrollTop;
            rect.left += ghostRelativeParent.scrollLeft;
          } else {
            ghostRelativeParent = getWindowScrollingElement();
          }

          ghostRelativeParentInitialScroll = getRelativeScrollOffset(ghostRelativeParent);
        }

        ghostEl = dragEl.cloneNode(true);
        toggleClass(ghostEl, options.ghostClass, false);
        toggleClass(ghostEl, options.fallbackClass, true);
        toggleClass(ghostEl, options.dragClass, true);
        css(ghostEl, 'transition', '');
        css(ghostEl, 'transform', '');
        css(ghostEl, 'box-sizing', 'border-box');
        css(ghostEl, 'margin', 0);
        css(ghostEl, 'top', rect.top);
        css(ghostEl, 'left', rect.left);
        css(ghostEl, 'width', rect.width);
        css(ghostEl, 'height', rect.height);
        css(ghostEl, 'opacity', '0.8');
        css(ghostEl, 'position', PositionGhostAbsolutely ? 'absolute' : 'fixed');
        css(ghostEl, 'zIndex', '100000');
        css(ghostEl, 'pointerEvents', 'none');
        Sortable.ghost = ghostEl;
        container.appendChild(ghostEl); // Set transform-origin

        css(ghostEl, 'transform-origin', tapDistanceLeft / parseInt(ghostEl.style.width) * 100 + '% ' + tapDistanceTop / parseInt(ghostEl.style.height) * 100 + '%');
      }
    },
    _onDragStart: function _onDragStart(
    /**Event*/
    evt,
    /**boolean*/
    fallback) {
      var _this = this;

      var dataTransfer = evt.dataTransfer;
      var options = _this.options;
      pluginEvent('dragStart', this, {
        evt: evt
      });

      if (Sortable.eventCanceled) {
        this._onDrop();

        return;
      }

      pluginEvent('setupClone', this);

      if (!Sortable.eventCanceled) {
        cloneEl = clone(dragEl);
        cloneEl.removeAttribute("id");
        cloneEl.draggable = false;
        cloneEl.style['will-change'] = '';

        this._hideClone();

        toggleClass(cloneEl, this.options.chosenClass, false);
        Sortable.clone = cloneEl;
      } // #1143: IFrame support workaround


      _this.cloneId = _nextTick(function () {
        pluginEvent('clone', _this);
        if (Sortable.eventCanceled) return;

        if (!_this.options.removeCloneOnHide) {
          rootEl.insertBefore(cloneEl, dragEl);
        }

        _this._hideClone();

        _dispatchEvent({
          sortable: _this,
          name: 'clone'
        });
      });
      !fallback && toggleClass(dragEl, options.dragClass, true); // Set proper drop events

      if (fallback) {
        ignoreNextClick = true;
        _this._loopId = setInterval(_this._emulateDragOver, 50);
      } else {
        // Undo what was set in _prepareDragStart before drag started
        off(document, 'mouseup', _this._onDrop);
        off(document, 'touchend', _this._onDrop);
        off(document, 'touchcancel', _this._onDrop);

        if (dataTransfer) {
          dataTransfer.effectAllowed = 'move';
          options.setData && options.setData.call(_this, dataTransfer, dragEl);
        }

        on(document, 'drop', _this); // #1276 fix:

        css(dragEl, 'transform', 'translateZ(0)');
      }

      awaitingDragStarted = true;
      _this._dragStartId = _nextTick(_this._dragStarted.bind(_this, fallback, evt));
      on(document, 'selectstart', _this);
      moved = true;

      if (Safari) {
        css(document.body, 'user-select', 'none');
      }
    },
    // Returns true - if no further action is needed (either inserted or another condition)
    _onDragOver: function _onDragOver(
    /**Event*/
    evt) {
      var el = this.el,
          target = evt.target,
          dragRect,
          targetRect,
          revert,
          options = this.options,
          group = options.group,
          activeSortable = Sortable.active,
          isOwner = activeGroup === group,
          canSort = options.sort,
          fromSortable = putSortable || activeSortable,
          vertical,
          _this = this,
          completedFired = false;

      if (_silent) return;

      function dragOverEvent(name, extra) {
        pluginEvent(name, _this, _objectSpread2({
          evt: evt,
          isOwner: isOwner,
          axis: vertical ? 'vertical' : 'horizontal',
          revert: revert,
          dragRect: dragRect,
          targetRect: targetRect,
          canSort: canSort,
          fromSortable: fromSortable,
          target: target,
          completed: completed,
          onMove: function onMove(target, after) {
            return _onMove(rootEl, el, dragEl, dragRect, target, getRect(target), evt, after);
          },
          changed: changed
        }, extra));
      } // Capture animation state


      function capture() {
        dragOverEvent('dragOverAnimationCapture');

        _this.captureAnimationState();

        if (_this !== fromSortable) {
          fromSortable.captureAnimationState();
        }
      } // Return invocation when dragEl is inserted (or completed)


      function completed(insertion) {
        dragOverEvent('dragOverCompleted', {
          insertion: insertion
        });

        if (insertion) {
          // Clones must be hidden before folding animation to capture dragRectAbsolute properly
          if (isOwner) {
            activeSortable._hideClone();
          } else {
            activeSortable._showClone(_this);
          }

          if (_this !== fromSortable) {
            // Set ghost class to new sortable's ghost class
            toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : activeSortable.options.ghostClass, false);
            toggleClass(dragEl, options.ghostClass, true);
          }

          if (putSortable !== _this && _this !== Sortable.active) {
            putSortable = _this;
          } else if (_this === Sortable.active && putSortable) {
            putSortable = null;
          } // Animation


          if (fromSortable === _this) {
            _this._ignoreWhileAnimating = target;
          }

          _this.animateAll(function () {
            dragOverEvent('dragOverAnimationComplete');
            _this._ignoreWhileAnimating = null;
          });

          if (_this !== fromSortable) {
            fromSortable.animateAll();
            fromSortable._ignoreWhileAnimating = null;
          }
        } // Null lastTarget if it is not inside a previously swapped element


        if (target === dragEl && !dragEl.animated || target === el && !target.animated) {
          lastTarget = null;
        } // no bubbling and not fallback


        if (!options.dragoverBubble && !evt.rootEl && target !== document) {
          dragEl.parentNode[expando]._isOutsideThisEl(evt.target); // Do not detect for empty insert if already inserted


          !insertion && nearestEmptyInsertDetectEvent(evt);
        }

        !options.dragoverBubble && evt.stopPropagation && evt.stopPropagation();
        return completedFired = true;
      } // Call when dragEl has been inserted


      function changed() {
        newIndex = index(dragEl);
        newDraggableIndex = index(dragEl, options.draggable);

        _dispatchEvent({
          sortable: _this,
          name: 'change',
          toEl: el,
          newIndex: newIndex,
          newDraggableIndex: newDraggableIndex,
          originalEvent: evt
        });
      }

      if (evt.preventDefault !== void 0) {
        evt.cancelable && evt.preventDefault();
      }

      target = closest(target, options.draggable, el, true);
      dragOverEvent('dragOver');
      if (Sortable.eventCanceled) return completedFired;

      if (dragEl.contains(evt.target) || target.animated && target.animatingX && target.animatingY || _this._ignoreWhileAnimating === target) {
        return completed(false);
      }

      ignoreNextClick = false;

      if (activeSortable && !options.disabled && (isOwner ? canSort || (revert = parentEl !== rootEl) // Reverting item into the original list
      : putSortable === this || (this.lastPutMode = activeGroup.checkPull(this, activeSortable, dragEl, evt)) && group.checkPut(this, activeSortable, dragEl, evt))) {
        vertical = this._getDirection(evt, target) === 'vertical';
        dragRect = getRect(dragEl);
        dragOverEvent('dragOverValid');
        if (Sortable.eventCanceled) return completedFired;

        if (revert) {
          parentEl = rootEl; // actualization

          capture();

          this._hideClone();

          dragOverEvent('revert');

          if (!Sortable.eventCanceled) {
            if (nextEl) {
              rootEl.insertBefore(dragEl, nextEl);
            } else {
              rootEl.appendChild(dragEl);
            }
          }

          return completed(true);
        }

        var elLastChild = lastChild(el, options.draggable);

        if (!elLastChild || _ghostIsLast(evt, vertical, this) && !elLastChild.animated) {
          // Insert to end of list
          // If already at end of list: Do not insert
          if (elLastChild === dragEl) {
            return completed(false);
          } // if there is a last element, it is the target


          if (elLastChild && el === evt.target) {
            target = elLastChild;
          }

          if (target) {
            targetRect = getRect(target);
          }

          if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, !!target) !== false) {
            capture();

            if (elLastChild && elLastChild.nextSibling) {
              // the last draggable element is not the last node
              el.insertBefore(dragEl, elLastChild.nextSibling);
            } else {
              el.appendChild(dragEl);
            }

            parentEl = el; // actualization

            changed();
            return completed(true);
          }
        } else if (elLastChild && _ghostIsFirst(evt, vertical, this)) {
          // Insert to start of list
          var firstChild = getChild(el, 0, options, true);

          if (firstChild === dragEl) {
            return completed(false);
          }

          target = firstChild;
          targetRect = getRect(target);

          if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, false) !== false) {
            capture();
            el.insertBefore(dragEl, firstChild);
            parentEl = el; // actualization

            changed();
            return completed(true);
          }
        } else if (target.parentNode === el) {
          targetRect = getRect(target);
          var direction = 0,
              targetBeforeFirstSwap,
              differentLevel = dragEl.parentNode !== el,
              differentRowCol = !_dragElInRowColumn(dragEl.animated && dragEl.toRect || dragRect, target.animated && target.toRect || targetRect, vertical),
              side1 = vertical ? 'top' : 'left',
              scrolledPastTop = isScrolledPast(target, 'top', 'top') || isScrolledPast(dragEl, 'top', 'top'),
              scrollBefore = scrolledPastTop ? scrolledPastTop.scrollTop : void 0;

          if (lastTarget !== target) {
            targetBeforeFirstSwap = targetRect[side1];
            pastFirstInvertThresh = false;
            isCircumstantialInvert = !differentRowCol && options.invertSwap || differentLevel;
          }

          direction = _getSwapDirection(evt, target, targetRect, vertical, differentRowCol ? 1 : options.swapThreshold, options.invertedSwapThreshold == null ? options.swapThreshold : options.invertedSwapThreshold, isCircumstantialInvert, lastTarget === target);
          var sibling;

          if (direction !== 0) {
            // Check if target is beside dragEl in respective direction (ignoring hidden elements)
            var dragIndex = index(dragEl);

            do {
              dragIndex -= direction;
              sibling = parentEl.children[dragIndex];
            } while (sibling && (css(sibling, 'display') === 'none' || sibling === ghostEl));
          } // If dragEl is already beside target: Do not insert


          if (direction === 0 || sibling === target) {
            return completed(false);
          }

          lastTarget = target;
          lastDirection = direction;
          var nextSibling = target.nextElementSibling,
              after = false;
          after = direction === 1;

          var moveVector = _onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, after);

          if (moveVector !== false) {
            if (moveVector === 1 || moveVector === -1) {
              after = moveVector === 1;
            }

            _silent = true;
            setTimeout(_unsilent, 30);
            capture();

            if (after && !nextSibling) {
              el.appendChild(dragEl);
            } else {
              target.parentNode.insertBefore(dragEl, after ? nextSibling : target);
            } // Undo chrome's scroll adjustment (has no effect on other browsers)


            if (scrolledPastTop) {
              scrollBy(scrolledPastTop, 0, scrollBefore - scrolledPastTop.scrollTop);
            }

            parentEl = dragEl.parentNode; // actualization
            // must be done before animation

            if (targetBeforeFirstSwap !== undefined && !isCircumstantialInvert) {
              targetMoveDistance = Math.abs(targetBeforeFirstSwap - getRect(target)[side1]);
            }

            changed();
            return completed(true);
          }
        }

        if (el.contains(dragEl)) {
          return completed(false);
        }
      }

      return false;
    },
    _ignoreWhileAnimating: null,
    _offMoveEvents: function _offMoveEvents() {
      off(document, 'mousemove', this._onTouchMove);
      off(document, 'touchmove', this._onTouchMove);
      off(document, 'pointermove', this._onTouchMove);
      off(document, 'dragover', nearestEmptyInsertDetectEvent);
      off(document, 'mousemove', nearestEmptyInsertDetectEvent);
      off(document, 'touchmove', nearestEmptyInsertDetectEvent);
    },
    _offUpEvents: function _offUpEvents() {
      var ownerDocument = this.el.ownerDocument;
      off(ownerDocument, 'mouseup', this._onDrop);
      off(ownerDocument, 'touchend', this._onDrop);
      off(ownerDocument, 'pointerup', this._onDrop);
      off(ownerDocument, 'touchcancel', this._onDrop);
      off(document, 'selectstart', this);
    },
    _onDrop: function _onDrop(
    /**Event*/
    evt) {
      var el = this.el,
          options = this.options; // Get the index of the dragged element within its parent

      newIndex = index(dragEl);
      newDraggableIndex = index(dragEl, options.draggable);
      pluginEvent('drop', this, {
        evt: evt
      });
      parentEl = dragEl && dragEl.parentNode; // Get again after plugin event

      newIndex = index(dragEl);
      newDraggableIndex = index(dragEl, options.draggable);

      if (Sortable.eventCanceled) {
        this._nulling();

        return;
      }

      awaitingDragStarted = false;
      isCircumstantialInvert = false;
      pastFirstInvertThresh = false;
      clearInterval(this._loopId);
      clearTimeout(this._dragStartTimer);

      _cancelNextTick(this.cloneId);

      _cancelNextTick(this._dragStartId); // Unbind events


      if (this.nativeDraggable) {
        off(document, 'drop', this);
        off(el, 'dragstart', this._onDragStart);
      }

      this._offMoveEvents();

      this._offUpEvents();

      if (Safari) {
        css(document.body, 'user-select', '');
      }

      css(dragEl, 'transform', '');

      if (evt) {
        if (moved) {
          evt.cancelable && evt.preventDefault();
          !options.dropBubble && evt.stopPropagation();
        }

        ghostEl && ghostEl.parentNode && ghostEl.parentNode.removeChild(ghostEl);

        if (rootEl === parentEl || putSortable && putSortable.lastPutMode !== 'clone') {
          // Remove clone(s)
          cloneEl && cloneEl.parentNode && cloneEl.parentNode.removeChild(cloneEl);
        }

        if (dragEl) {
          if (this.nativeDraggable) {
            off(dragEl, 'dragend', this);
          }

          _disableDraggable(dragEl);

          dragEl.style['will-change'] = ''; // Remove classes
          // ghostClass is added in dragStarted

          if (moved && !awaitingDragStarted) {
            toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : this.options.ghostClass, false);
          }

          toggleClass(dragEl, this.options.chosenClass, false); // Drag stop event

          _dispatchEvent({
            sortable: this,
            name: 'unchoose',
            toEl: parentEl,
            newIndex: null,
            newDraggableIndex: null,
            originalEvent: evt
          });

          if (rootEl !== parentEl) {
            if (newIndex >= 0) {
              // Add event
              _dispatchEvent({
                rootEl: parentEl,
                name: 'add',
                toEl: parentEl,
                fromEl: rootEl,
                originalEvent: evt
              }); // Remove event


              _dispatchEvent({
                sortable: this,
                name: 'remove',
                toEl: parentEl,
                originalEvent: evt
              }); // drag from one list and drop into another


              _dispatchEvent({
                rootEl: parentEl,
                name: 'sort',
                toEl: parentEl,
                fromEl: rootEl,
                originalEvent: evt
              });

              _dispatchEvent({
                sortable: this,
                name: 'sort',
                toEl: parentEl,
                originalEvent: evt
              });
            }

            putSortable && putSortable.save();
          } else {
            if (newIndex !== oldIndex) {
              if (newIndex >= 0) {
                // drag & drop within the same list
                _dispatchEvent({
                  sortable: this,
                  name: 'update',
                  toEl: parentEl,
                  originalEvent: evt
                });

                _dispatchEvent({
                  sortable: this,
                  name: 'sort',
                  toEl: parentEl,
                  originalEvent: evt
                });
              }
            }
          }

          if (Sortable.active) {
            /* jshint eqnull:true */
            if (newIndex == null || newIndex === -1) {
              newIndex = oldIndex;
              newDraggableIndex = oldDraggableIndex;
            }

            _dispatchEvent({
              sortable: this,
              name: 'end',
              toEl: parentEl,
              originalEvent: evt
            }); // Save sorting


            this.save();
          }
        }
      }

      this._nulling();
    },
    _nulling: function _nulling() {
      pluginEvent('nulling', this);
      rootEl = dragEl = parentEl = ghostEl = nextEl = cloneEl = lastDownEl = cloneHidden = tapEvt = touchEvt = moved = newIndex = newDraggableIndex = oldIndex = oldDraggableIndex = lastTarget = lastDirection = putSortable = activeGroup = Sortable.dragged = Sortable.ghost = Sortable.clone = Sortable.active = null;
      savedInputChecked.forEach(function (el) {
        el.checked = true;
      });
      savedInputChecked.length = lastDx = lastDy = 0;
    },
    handleEvent: function handleEvent(
    /**Event*/
    evt) {
      switch (evt.type) {
        case 'drop':
        case 'dragend':
          this._onDrop(evt);

          break;

        case 'dragenter':
        case 'dragover':
          if (dragEl) {
            this._onDragOver(evt);

            _globalDragOver(evt);
          }

          break;

        case 'selectstart':
          evt.preventDefault();
          break;
      }
    },

    /**
     * Serializes the item into an array of string.
     * @returns {String[]}
     */
    toArray: function toArray() {
      var order = [],
          el,
          children = this.el.children,
          i = 0,
          n = children.length,
          options = this.options;

      for (; i < n; i++) {
        el = children[i];

        if (closest(el, options.draggable, this.el, false)) {
          order.push(el.getAttribute(options.dataIdAttr) || _generateId(el));
        }
      }

      return order;
    },

    /**
     * Sorts the elements according to the array.
     * @param  {String[]}  order  order of the items
     */
    sort: function sort(order, useAnimation) {
      var items = {},
          rootEl = this.el;
      this.toArray().forEach(function (id, i) {
        var el = rootEl.children[i];

        if (closest(el, this.options.draggable, rootEl, false)) {
          items[id] = el;
        }
      }, this);
      useAnimation && this.captureAnimationState();
      order.forEach(function (id) {
        if (items[id]) {
          rootEl.removeChild(items[id]);
          rootEl.appendChild(items[id]);
        }
      });
      useAnimation && this.animateAll();
    },

    /**
     * Save the current sorting
     */
    save: function save() {
      var store = this.options.store;
      store && store.set && store.set(this);
    },

    /**
     * For each element in the set, get the first element that matches the selector by testing the element itself and traversing up through its ancestors in the DOM tree.
     * @param   {HTMLElement}  el
     * @param   {String}       [selector]  default: `options.draggable`
     * @returns {HTMLElement|null}
     */
    closest: function closest$1(el, selector) {
      return closest(el, selector || this.options.draggable, this.el, false);
    },

    /**
     * Set/get option
     * @param   {string} name
     * @param   {*}      [value]
     * @returns {*}
     */
    option: function option(name, value) {
      var options = this.options;

      if (value === void 0) {
        return options[name];
      } else {
        var modifiedValue = PluginManager.modifyOption(this, name, value);

        if (typeof modifiedValue !== 'undefined') {
          options[name] = modifiedValue;
        } else {
          options[name] = value;
        }

        if (name === 'group') {
          _prepareGroup(options);
        }
      }
    },

    /**
     * Destroy
     */
    destroy: function destroy() {
      pluginEvent('destroy', this);
      var el = this.el;
      el[expando] = null;
      off(el, 'mousedown', this._onTapStart);
      off(el, 'touchstart', this._onTapStart);
      off(el, 'pointerdown', this._onTapStart);

      if (this.nativeDraggable) {
        off(el, 'dragover', this);
        off(el, 'dragenter', this);
      } // Remove draggable attributes


      Array.prototype.forEach.call(el.querySelectorAll('[draggable]'), function (el) {
        el.removeAttribute('draggable');
      });

      this._onDrop();

      this._disableDelayedDragEvents();

      sortables.splice(sortables.indexOf(this.el), 1);
      this.el = el = null;
    },
    _hideClone: function _hideClone() {
      if (!cloneHidden) {
        pluginEvent('hideClone', this);
        if (Sortable.eventCanceled) return;
        css(cloneEl, 'display', 'none');

        if (this.options.removeCloneOnHide && cloneEl.parentNode) {
          cloneEl.parentNode.removeChild(cloneEl);
        }

        cloneHidden = true;
      }
    },
    _showClone: function _showClone(putSortable) {
      if (putSortable.lastPutMode !== 'clone') {
        this._hideClone();

        return;
      }

      if (cloneHidden) {
        pluginEvent('showClone', this);
        if (Sortable.eventCanceled) return; // show clone at dragEl or original position

        if (dragEl.parentNode == rootEl && !this.options.group.revertClone) {
          rootEl.insertBefore(cloneEl, dragEl);
        } else if (nextEl) {
          rootEl.insertBefore(cloneEl, nextEl);
        } else {
          rootEl.appendChild(cloneEl);
        }

        if (this.options.group.revertClone) {
          this.animate(dragEl, cloneEl);
        }

        css(cloneEl, 'display', '');
        cloneHidden = false;
      }
    }
  };

  function _globalDragOver(
  /**Event*/
  evt) {
    if (evt.dataTransfer) {
      evt.dataTransfer.dropEffect = 'move';
    }

    evt.cancelable && evt.preventDefault();
  }

  function _onMove(fromEl, toEl, dragEl, dragRect, targetEl, targetRect, originalEvent, willInsertAfter) {
    var evt,
        sortable = fromEl[expando],
        onMoveFn = sortable.options.onMove,
        retVal; // Support for new CustomEvent feature

    if (window.CustomEvent && !IE11OrLess && !Edge) {
      evt = new CustomEvent('move', {
        bubbles: true,
        cancelable: true
      });
    } else {
      evt = document.createEvent('Event');
      evt.initEvent('move', true, true);
    }

    evt.to = toEl;
    evt.from = fromEl;
    evt.dragged = dragEl;
    evt.draggedRect = dragRect;
    evt.related = targetEl || toEl;
    evt.relatedRect = targetRect || getRect(toEl);
    evt.willInsertAfter = willInsertAfter;
    evt.originalEvent = originalEvent;
    fromEl.dispatchEvent(evt);

    if (onMoveFn) {
      retVal = onMoveFn.call(sortable, evt, originalEvent);
    }

    return retVal;
  }

  function _disableDraggable(el) {
    el.draggable = false;
  }

  function _unsilent() {
    _silent = false;
  }

  function _ghostIsFirst(evt, vertical, sortable) {
    var rect = getRect(getChild(sortable.el, 0, sortable.options, true));
    var spacer = 10;
    return vertical ? evt.clientX < rect.left - spacer || evt.clientY < rect.top && evt.clientX < rect.right : evt.clientY < rect.top - spacer || evt.clientY < rect.bottom && evt.clientX < rect.left;
  }

  function _ghostIsLast(evt, vertical, sortable) {
    var rect = getRect(lastChild(sortable.el, sortable.options.draggable));
    var spacer = 10;
    return vertical ? evt.clientX > rect.right + spacer || evt.clientX <= rect.right && evt.clientY > rect.bottom && evt.clientX >= rect.left : evt.clientX > rect.right && evt.clientY > rect.top || evt.clientX <= rect.right && evt.clientY > rect.bottom + spacer;
  }

  function _getSwapDirection(evt, target, targetRect, vertical, swapThreshold, invertedSwapThreshold, invertSwap, isLastTarget) {
    var mouseOnAxis = vertical ? evt.clientY : evt.clientX,
        targetLength = vertical ? targetRect.height : targetRect.width,
        targetS1 = vertical ? targetRect.top : targetRect.left,
        targetS2 = vertical ? targetRect.bottom : targetRect.right,
        invert = false;

    if (!invertSwap) {
      // Never invert or create dragEl shadow when target movemenet causes mouse to move past the end of regular swapThreshold
      if (isLastTarget && targetMoveDistance < targetLength * swapThreshold) {
        // multiplied only by swapThreshold because mouse will already be inside target by (1 - threshold) * targetLength / 2
        // check if past first invert threshold on side opposite of lastDirection
        if (!pastFirstInvertThresh && (lastDirection === 1 ? mouseOnAxis > targetS1 + targetLength * invertedSwapThreshold / 2 : mouseOnAxis < targetS2 - targetLength * invertedSwapThreshold / 2)) {
          // past first invert threshold, do not restrict inverted threshold to dragEl shadow
          pastFirstInvertThresh = true;
        }

        if (!pastFirstInvertThresh) {
          // dragEl shadow (target move distance shadow)
          if (lastDirection === 1 ? mouseOnAxis < targetS1 + targetMoveDistance // over dragEl shadow
          : mouseOnAxis > targetS2 - targetMoveDistance) {
            return -lastDirection;
          }
        } else {
          invert = true;
        }
      } else {
        // Regular
        if (mouseOnAxis > targetS1 + targetLength * (1 - swapThreshold) / 2 && mouseOnAxis < targetS2 - targetLength * (1 - swapThreshold) / 2) {
          return _getInsertDirection(target);
        }
      }
    }

    invert = invert || invertSwap;

    if (invert) {
      // Invert of regular
      if (mouseOnAxis < targetS1 + targetLength * invertedSwapThreshold / 2 || mouseOnAxis > targetS2 - targetLength * invertedSwapThreshold / 2) {
        return mouseOnAxis > targetS1 + targetLength / 2 ? 1 : -1;
      }
    }

    return 0;
  }
  /**
   * Gets the direction dragEl must be swapped relative to target in order to make it
   * seem that dragEl has been "inserted" into that element's position
   * @param  {HTMLElement} target       The target whose position dragEl is being inserted at
   * @return {Number}                   Direction dragEl must be swapped
   */


  function _getInsertDirection(target) {
    if (index(dragEl) < index(target)) {
      return 1;
    } else {
      return -1;
    }
  }
  /**
   * Generate id
   * @param   {HTMLElement} el
   * @returns {String}
   * @private
   */


  function _generateId(el) {
    var str = el.tagName + el.className + el.src + el.href + el.textContent,
        i = str.length,
        sum = 0;

    while (i--) {
      sum += str.charCodeAt(i);
    }

    return sum.toString(36);
  }

  function _saveInputCheckedState(root) {
    savedInputChecked.length = 0;
    var inputs = root.getElementsByTagName('input');
    var idx = inputs.length;

    while (idx--) {
      var el = inputs[idx];
      el.checked && savedInputChecked.push(el);
    }
  }

  function _nextTick(fn) {
    return setTimeout(fn, 0);
  }

  function _cancelNextTick(id) {
    return clearTimeout(id);
  } // Fixed #973:


  if (documentExists) {
    on(document, 'touchmove', function (evt) {
      if ((Sortable.active || awaitingDragStarted) && evt.cancelable) {
        evt.preventDefault();
      }
    });
  } // Export utils


  Sortable.utils = {
    on: on,
    off: off,
    css: css,
    find: find,
    is: function is(el, selector) {
      return !!closest(el, selector, el, false);
    },
    extend: extend,
    throttle: throttle,
    closest: closest,
    toggleClass: toggleClass,
    clone: clone,
    index: index,
    nextTick: _nextTick,
    cancelNextTick: _cancelNextTick,
    detectDirection: _detectDirection,
    getChild: getChild
  };
  /**
   * Get the Sortable instance of an element
   * @param  {HTMLElement} element The element
   * @return {Sortable|undefined}         The instance of Sortable
   */

  Sortable.get = function (element) {
    return element[expando];
  };
  /**
   * Mount a plugin to Sortable
   * @param  {...SortablePlugin|SortablePlugin[]} plugins       Plugins being mounted
   */


  Sortable.mount = function () {
    for (var _len = arguments.length, plugins = new Array(_len), _key = 0; _key < _len; _key++) {
      plugins[_key] = arguments[_key];
    }

    if (plugins[0].constructor === Array) plugins = plugins[0];
    plugins.forEach(function (plugin) {
      if (!plugin.prototype || !plugin.prototype.constructor) {
        throw "Sortable: Mounted plugin must be a constructor function, not ".concat({}.toString.call(plugin));
      }

      if (plugin.utils) Sortable.utils = _objectSpread2(_objectSpread2({}, Sortable.utils), plugin.utils);
      PluginManager.mount(plugin);
    });
  };
  /**
   * Create sortable instance
   * @param {HTMLElement}  el
   * @param {Object}      [options]
   */


  Sortable.create = function (el, options) {
    return new Sortable(el, options);
  }; // Export


  Sortable.version = version;

  var autoScrolls = [],
      scrollEl,
      scrollRootEl,
      scrolling = false,
      lastAutoScrollX,
      lastAutoScrollY,
      touchEvt$1,
      pointerElemChangedInterval;

  function AutoScrollPlugin() {
    function AutoScroll() {
      this.defaults = {
        scroll: true,
        forceAutoScrollFallback: false,
        scrollSensitivity: 30,
        scrollSpeed: 10,
        bubbleScroll: true
      }; // Bind all private methods

      for (var fn in this) {
        if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
          this[fn] = this[fn].bind(this);
        }
      }
    }

    AutoScroll.prototype = {
      dragStarted: function dragStarted(_ref) {
        var originalEvent = _ref.originalEvent;

        if (this.sortable.nativeDraggable) {
          on(document, 'dragover', this._handleAutoScroll);
        } else {
          if (this.options.supportPointer) {
            on(document, 'pointermove', this._handleFallbackAutoScroll);
          } else if (originalEvent.touches) {
            on(document, 'touchmove', this._handleFallbackAutoScroll);
          } else {
            on(document, 'mousemove', this._handleFallbackAutoScroll);
          }
        }
      },
      dragOverCompleted: function dragOverCompleted(_ref2) {
        var originalEvent = _ref2.originalEvent;

        // For when bubbling is canceled and using fallback (fallback 'touchmove' always reached)
        if (!this.options.dragOverBubble && !originalEvent.rootEl) {
          this._handleAutoScroll(originalEvent);
        }
      },
      drop: function drop() {
        if (this.sortable.nativeDraggable) {
          off(document, 'dragover', this._handleAutoScroll);
        } else {
          off(document, 'pointermove', this._handleFallbackAutoScroll);
          off(document, 'touchmove', this._handleFallbackAutoScroll);
          off(document, 'mousemove', this._handleFallbackAutoScroll);
        }

        clearPointerElemChangedInterval();
        clearAutoScrolls();
        cancelThrottle();
      },
      nulling: function nulling() {
        touchEvt$1 = scrollRootEl = scrollEl = scrolling = pointerElemChangedInterval = lastAutoScrollX = lastAutoScrollY = null;
        autoScrolls.length = 0;
      },
      _handleFallbackAutoScroll: function _handleFallbackAutoScroll(evt) {
        this._handleAutoScroll(evt, true);
      },
      _handleAutoScroll: function _handleAutoScroll(evt, fallback) {
        var _this = this;

        var x = (evt.touches ? evt.touches[0] : evt).clientX,
            y = (evt.touches ? evt.touches[0] : evt).clientY,
            elem = document.elementFromPoint(x, y);
        touchEvt$1 = evt; // IE does not seem to have native autoscroll,
        // Edge's autoscroll seems too conditional,
        // MACOS Safari does not have autoscroll,
        // Firefox and Chrome are good

        if (fallback || this.options.forceAutoScrollFallback || Edge || IE11OrLess || Safari) {
          autoScroll(evt, this.options, elem, fallback); // Listener for pointer element change

          var ogElemScroller = getParentAutoScrollElement(elem, true);

          if (scrolling && (!pointerElemChangedInterval || x !== lastAutoScrollX || y !== lastAutoScrollY)) {
            pointerElemChangedInterval && clearPointerElemChangedInterval(); // Detect for pointer elem change, emulating native DnD behaviour

            pointerElemChangedInterval = setInterval(function () {
              var newElem = getParentAutoScrollElement(document.elementFromPoint(x, y), true);

              if (newElem !== ogElemScroller) {
                ogElemScroller = newElem;
                clearAutoScrolls();
              }

              autoScroll(evt, _this.options, newElem, fallback);
            }, 10);
            lastAutoScrollX = x;
            lastAutoScrollY = y;
          }
        } else {
          // if DnD is enabled (and browser has good autoscrolling), first autoscroll will already scroll, so get parent autoscroll of first autoscroll
          if (!this.options.bubbleScroll || getParentAutoScrollElement(elem, true) === getWindowScrollingElement()) {
            clearAutoScrolls();
            return;
          }

          autoScroll(evt, this.options, getParentAutoScrollElement(elem, false), false);
        }
      }
    };
    return _extends(AutoScroll, {
      pluginName: 'scroll',
      initializeByDefault: true
    });
  }

  function clearAutoScrolls() {
    autoScrolls.forEach(function (autoScroll) {
      clearInterval(autoScroll.pid);
    });
    autoScrolls = [];
  }

  function clearPointerElemChangedInterval() {
    clearInterval(pointerElemChangedInterval);
  }

  var autoScroll = throttle(function (evt, options, rootEl, isFallback) {
    // Bug: https://bugzilla.mozilla.org/show_bug.cgi?id=505521
    if (!options.scroll) return;
    var x = (evt.touches ? evt.touches[0] : evt).clientX,
        y = (evt.touches ? evt.touches[0] : evt).clientY,
        sens = options.scrollSensitivity,
        speed = options.scrollSpeed,
        winScroller = getWindowScrollingElement();
    var scrollThisInstance = false,
        scrollCustomFn; // New scroll root, set scrollEl

    if (scrollRootEl !== rootEl) {
      scrollRootEl = rootEl;
      clearAutoScrolls();
      scrollEl = options.scroll;
      scrollCustomFn = options.scrollFn;

      if (scrollEl === true) {
        scrollEl = getParentAutoScrollElement(rootEl, true);
      }
    }

    var layersOut = 0;
    var currentParent = scrollEl;

    do {
      var el = currentParent,
          rect = getRect(el),
          top = rect.top,
          bottom = rect.bottom,
          left = rect.left,
          right = rect.right,
          width = rect.width,
          height = rect.height,
          canScrollX = void 0,
          canScrollY = void 0,
          scrollWidth = el.scrollWidth,
          scrollHeight = el.scrollHeight,
          elCSS = css(el),
          scrollPosX = el.scrollLeft,
          scrollPosY = el.scrollTop;

      if (el === winScroller) {
        canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll' || elCSS.overflowX === 'visible');
        canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll' || elCSS.overflowY === 'visible');
      } else {
        canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll');
        canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll');
      }

      var vx = canScrollX && (Math.abs(right - x) <= sens && scrollPosX + width < scrollWidth) - (Math.abs(left - x) <= sens && !!scrollPosX);
      var vy = canScrollY && (Math.abs(bottom - y) <= sens && scrollPosY + height < scrollHeight) - (Math.abs(top - y) <= sens && !!scrollPosY);

      if (!autoScrolls[layersOut]) {
        for (var i = 0; i <= layersOut; i++) {
          if (!autoScrolls[i]) {
            autoScrolls[i] = {};
          }
        }
      }

      if (autoScrolls[layersOut].vx != vx || autoScrolls[layersOut].vy != vy || autoScrolls[layersOut].el !== el) {
        autoScrolls[layersOut].el = el;
        autoScrolls[layersOut].vx = vx;
        autoScrolls[layersOut].vy = vy;
        clearInterval(autoScrolls[layersOut].pid);

        if (vx != 0 || vy != 0) {
          scrollThisInstance = true;
          /* jshint loopfunc:true */

          autoScrolls[layersOut].pid = setInterval(function () {
            // emulate drag over during autoscroll (fallback), emulating native DnD behaviour
            if (isFallback && this.layer === 0) {
              Sortable.active._onTouchMove(touchEvt$1); // To move ghost if it is positioned absolutely

            }

            var scrollOffsetY = autoScrolls[this.layer].vy ? autoScrolls[this.layer].vy * speed : 0;
            var scrollOffsetX = autoScrolls[this.layer].vx ? autoScrolls[this.layer].vx * speed : 0;

            if (typeof scrollCustomFn === 'function') {
              if (scrollCustomFn.call(Sortable.dragged.parentNode[expando], scrollOffsetX, scrollOffsetY, evt, touchEvt$1, autoScrolls[this.layer].el) !== 'continue') {
                return;
              }
            }

            scrollBy(autoScrolls[this.layer].el, scrollOffsetX, scrollOffsetY);
          }.bind({
            layer: layersOut
          }), 24);
        }
      }

      layersOut++;
    } while (options.bubbleScroll && currentParent !== winScroller && (currentParent = getParentAutoScrollElement(currentParent, false)));

    scrolling = scrollThisInstance; // in case another function catches scrolling as false in between when it is not
  }, 30);

  var drop = function drop(_ref) {
    var originalEvent = _ref.originalEvent,
        putSortable = _ref.putSortable,
        dragEl = _ref.dragEl,
        activeSortable = _ref.activeSortable,
        dispatchSortableEvent = _ref.dispatchSortableEvent,
        hideGhostForTarget = _ref.hideGhostForTarget,
        unhideGhostForTarget = _ref.unhideGhostForTarget;
    if (!originalEvent) return;
    var toSortable = putSortable || activeSortable;
    hideGhostForTarget();
    var touch = originalEvent.changedTouches && originalEvent.changedTouches.length ? originalEvent.changedTouches[0] : originalEvent;
    var target = document.elementFromPoint(touch.clientX, touch.clientY);
    unhideGhostForTarget();

    if (toSortable && !toSortable.el.contains(target)) {
      dispatchSortableEvent('spill');
      this.onSpill({
        dragEl: dragEl,
        putSortable: putSortable
      });
    }
  };

  function Revert() {}

  Revert.prototype = {
    startIndex: null,
    dragStart: function dragStart(_ref2) {
      var oldDraggableIndex = _ref2.oldDraggableIndex;
      this.startIndex = oldDraggableIndex;
    },
    onSpill: function onSpill(_ref3) {
      var dragEl = _ref3.dragEl,
          putSortable = _ref3.putSortable;
      this.sortable.captureAnimationState();

      if (putSortable) {
        putSortable.captureAnimationState();
      }

      var nextSibling = getChild(this.sortable.el, this.startIndex, this.options);

      if (nextSibling) {
        this.sortable.el.insertBefore(dragEl, nextSibling);
      } else {
        this.sortable.el.appendChild(dragEl);
      }

      this.sortable.animateAll();

      if (putSortable) {
        putSortable.animateAll();
      }
    },
    drop: drop
  };

  _extends(Revert, {
    pluginName: 'revertOnSpill'
  });

  function Remove() {}

  Remove.prototype = {
    onSpill: function onSpill(_ref4) {
      var dragEl = _ref4.dragEl,
          putSortable = _ref4.putSortable;
      var parentSortable = putSortable || this.sortable;
      parentSortable.captureAnimationState();
      dragEl.parentNode && dragEl.parentNode.removeChild(dragEl);
      parentSortable.animateAll();
    },
    drop: drop
  };

  _extends(Remove, {
    pluginName: 'removeOnSpill'
  });

  var lastSwapEl;

  function SwapPlugin() {
    function Swap() {
      this.defaults = {
        swapClass: 'sortable-swap-highlight'
      };
    }

    Swap.prototype = {
      dragStart: function dragStart(_ref) {
        var dragEl = _ref.dragEl;
        lastSwapEl = dragEl;
      },
      dragOverValid: function dragOverValid(_ref2) {
        var completed = _ref2.completed,
            target = _ref2.target,
            onMove = _ref2.onMove,
            activeSortable = _ref2.activeSortable,
            changed = _ref2.changed,
            cancel = _ref2.cancel;
        if (!activeSortable.options.swap) return;
        var el = this.sortable.el,
            options = this.options;

        if (target && target !== el) {
          var prevSwapEl = lastSwapEl;

          if (onMove(target) !== false) {
            toggleClass(target, options.swapClass, true);
            lastSwapEl = target;
          } else {
            lastSwapEl = null;
          }

          if (prevSwapEl && prevSwapEl !== lastSwapEl) {
            toggleClass(prevSwapEl, options.swapClass, false);
          }
        }

        changed();
        completed(true);
        cancel();
      },
      drop: function drop(_ref3) {
        var activeSortable = _ref3.activeSortable,
            putSortable = _ref3.putSortable,
            dragEl = _ref3.dragEl;
        var toSortable = putSortable || this.sortable;
        var options = this.options;
        lastSwapEl && toggleClass(lastSwapEl, options.swapClass, false);

        if (lastSwapEl && (options.swap || putSortable && putSortable.options.swap)) {
          if (dragEl !== lastSwapEl) {
            toSortable.captureAnimationState();
            if (toSortable !== activeSortable) activeSortable.captureAnimationState();
            swapNodes(dragEl, lastSwapEl);
            toSortable.animateAll();
            if (toSortable !== activeSortable) activeSortable.animateAll();
          }
        }
      },
      nulling: function nulling() {
        lastSwapEl = null;
      }
    };
    return _extends(Swap, {
      pluginName: 'swap',
      eventProperties: function eventProperties() {
        return {
          swapItem: lastSwapEl
        };
      }
    });
  }

  function swapNodes(n1, n2) {
    var p1 = n1.parentNode,
        p2 = n2.parentNode,
        i1,
        i2;
    if (!p1 || !p2 || p1.isEqualNode(n2) || p2.isEqualNode(n1)) return;
    i1 = index(n1);
    i2 = index(n2);

    if (p1.isEqualNode(p2) && i1 < i2) {
      i2++;
    }

    p1.insertBefore(n2, p1.children[i1]);
    p2.insertBefore(n1, p2.children[i2]);
  }

  var multiDragElements = [],
      multiDragClones = [],
      lastMultiDragSelect,
      // for selection with modifier key down (SHIFT)
  multiDragSortable,
      initialFolding = false,
      // Initial multi-drag fold when drag started
  folding = false,
      // Folding any other time
  dragStarted = false,
      dragEl$1,
      clonesFromRect,
      clonesHidden;

  function MultiDragPlugin() {
    function MultiDrag(sortable) {
      // Bind all private methods
      for (var fn in this) {
        if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
          this[fn] = this[fn].bind(this);
        }
      }

      if (!sortable.options.avoidImplicitDeselect) {
        if (sortable.options.supportPointer) {
          on(document, 'pointerup', this._deselectMultiDrag);
        } else {
          on(document, 'mouseup', this._deselectMultiDrag);
          on(document, 'touchend', this._deselectMultiDrag);
        }
      }

      on(document, 'keydown', this._checkKeyDown);
      on(document, 'keyup', this._checkKeyUp);
      this.defaults = {
        selectedClass: 'sortable-selected',
        multiDragKey: null,
        avoidImplicitDeselect: false,
        setData: function setData(dataTransfer, dragEl) {
          var data = '';

          if (multiDragElements.length && multiDragSortable === sortable) {
            multiDragElements.forEach(function (multiDragElement, i) {
              data += (!i ? '' : ', ') + multiDragElement.textContent;
            });
          } else {
            data = dragEl.textContent;
          }

          dataTransfer.setData('Text', data);
        }
      };
    }

    MultiDrag.prototype = {
      multiDragKeyDown: false,
      isMultiDrag: false,
      delayStartGlobal: function delayStartGlobal(_ref) {
        var dragged = _ref.dragEl;
        dragEl$1 = dragged;
      },
      delayEnded: function delayEnded() {
        this.isMultiDrag = ~multiDragElements.indexOf(dragEl$1);
      },
      setupClone: function setupClone(_ref2) {
        var sortable = _ref2.sortable,
            cancel = _ref2.cancel;
        if (!this.isMultiDrag) return;

        for (var i = 0; i < multiDragElements.length; i++) {
          multiDragClones.push(clone(multiDragElements[i]));
          multiDragClones[i].sortableIndex = multiDragElements[i].sortableIndex;
          multiDragClones[i].draggable = false;
          multiDragClones[i].style['will-change'] = '';
          toggleClass(multiDragClones[i], this.options.selectedClass, false);
          multiDragElements[i] === dragEl$1 && toggleClass(multiDragClones[i], this.options.chosenClass, false);
        }

        sortable._hideClone();

        cancel();
      },
      clone: function clone(_ref3) {
        var sortable = _ref3.sortable,
            rootEl = _ref3.rootEl,
            dispatchSortableEvent = _ref3.dispatchSortableEvent,
            cancel = _ref3.cancel;
        if (!this.isMultiDrag) return;

        if (!this.options.removeCloneOnHide) {
          if (multiDragElements.length && multiDragSortable === sortable) {
            insertMultiDragClones(true, rootEl);
            dispatchSortableEvent('clone');
            cancel();
          }
        }
      },
      showClone: function showClone(_ref4) {
        var cloneNowShown = _ref4.cloneNowShown,
            rootEl = _ref4.rootEl,
            cancel = _ref4.cancel;
        if (!this.isMultiDrag) return;
        insertMultiDragClones(false, rootEl);
        multiDragClones.forEach(function (clone) {
          css(clone, 'display', '');
        });
        cloneNowShown();
        clonesHidden = false;
        cancel();
      },
      hideClone: function hideClone(_ref5) {
        var _this = this;

        var sortable = _ref5.sortable,
            cloneNowHidden = _ref5.cloneNowHidden,
            cancel = _ref5.cancel;
        if (!this.isMultiDrag) return;
        multiDragClones.forEach(function (clone) {
          css(clone, 'display', 'none');

          if (_this.options.removeCloneOnHide && clone.parentNode) {
            clone.parentNode.removeChild(clone);
          }
        });
        cloneNowHidden();
        clonesHidden = true;
        cancel();
      },
      dragStartGlobal: function dragStartGlobal(_ref6) {
        var sortable = _ref6.sortable;

        if (!this.isMultiDrag && multiDragSortable) {
          multiDragSortable.multiDrag._deselectMultiDrag();
        }

        multiDragElements.forEach(function (multiDragElement) {
          multiDragElement.sortableIndex = index(multiDragElement);
        }); // Sort multi-drag elements

        multiDragElements = multiDragElements.sort(function (a, b) {
          return a.sortableIndex - b.sortableIndex;
        });
        dragStarted = true;
      },
      dragStarted: function dragStarted(_ref7) {
        var _this2 = this;

        var sortable = _ref7.sortable;
        if (!this.isMultiDrag) return;

        if (this.options.sort) {
          // Capture rects,
          // hide multi drag elements (by positioning them absolute),
          // set multi drag elements rects to dragRect,
          // show multi drag elements,
          // animate to rects,
          // unset rects & remove from DOM
          sortable.captureAnimationState();

          if (this.options.animation) {
            multiDragElements.forEach(function (multiDragElement) {
              if (multiDragElement === dragEl$1) return;
              css(multiDragElement, 'position', 'absolute');
            });
            var dragRect = getRect(dragEl$1, false, true, true);
            multiDragElements.forEach(function (multiDragElement) {
              if (multiDragElement === dragEl$1) return;
              setRect(multiDragElement, dragRect);
            });
            folding = true;
            initialFolding = true;
          }
        }

        sortable.animateAll(function () {
          folding = false;
          initialFolding = false;

          if (_this2.options.animation) {
            multiDragElements.forEach(function (multiDragElement) {
              unsetRect(multiDragElement);
            });
          } // Remove all auxiliary multidrag items from el, if sorting enabled


          if (_this2.options.sort) {
            removeMultiDragElements();
          }
        });
      },
      dragOver: function dragOver(_ref8) {
        var target = _ref8.target,
            completed = _ref8.completed,
            cancel = _ref8.cancel;

        if (folding && ~multiDragElements.indexOf(target)) {
          completed(false);
          cancel();
        }
      },
      revert: function revert(_ref9) {
        var fromSortable = _ref9.fromSortable,
            rootEl = _ref9.rootEl,
            sortable = _ref9.sortable,
            dragRect = _ref9.dragRect;

        if (multiDragElements.length > 1) {
          // Setup unfold animation
          multiDragElements.forEach(function (multiDragElement) {
            sortable.addAnimationState({
              target: multiDragElement,
              rect: folding ? getRect(multiDragElement) : dragRect
            });
            unsetRect(multiDragElement);
            multiDragElement.fromRect = dragRect;
            fromSortable.removeAnimationState(multiDragElement);
          });
          folding = false;
          insertMultiDragElements(!this.options.removeCloneOnHide, rootEl);
        }
      },
      dragOverCompleted: function dragOverCompleted(_ref10) {
        var sortable = _ref10.sortable,
            isOwner = _ref10.isOwner,
            insertion = _ref10.insertion,
            activeSortable = _ref10.activeSortable,
            parentEl = _ref10.parentEl,
            putSortable = _ref10.putSortable;
        var options = this.options;

        if (insertion) {
          // Clones must be hidden before folding animation to capture dragRectAbsolute properly
          if (isOwner) {
            activeSortable._hideClone();
          }

          initialFolding = false; // If leaving sort:false root, or already folding - Fold to new location

          if (options.animation && multiDragElements.length > 1 && (folding || !isOwner && !activeSortable.options.sort && !putSortable)) {
            // Fold: Set all multi drag elements's rects to dragEl's rect when multi-drag elements are invisible
            var dragRectAbsolute = getRect(dragEl$1, false, true, true);
            multiDragElements.forEach(function (multiDragElement) {
              if (multiDragElement === dragEl$1) return;
              setRect(multiDragElement, dragRectAbsolute); // Move element(s) to end of parentEl so that it does not interfere with multi-drag clones insertion if they are inserted
              // while folding, and so that we can capture them again because old sortable will no longer be fromSortable

              parentEl.appendChild(multiDragElement);
            });
            folding = true;
          } // Clones must be shown (and check to remove multi drags) after folding when interfering multiDragElements are moved out


          if (!isOwner) {
            // Only remove if not folding (folding will remove them anyways)
            if (!folding) {
              removeMultiDragElements();
            }

            if (multiDragElements.length > 1) {
              var clonesHiddenBefore = clonesHidden;

              activeSortable._showClone(sortable); // Unfold animation for clones if showing from hidden


              if (activeSortable.options.animation && !clonesHidden && clonesHiddenBefore) {
                multiDragClones.forEach(function (clone) {
                  activeSortable.addAnimationState({
                    target: clone,
                    rect: clonesFromRect
                  });
                  clone.fromRect = clonesFromRect;
                  clone.thisAnimationDuration = null;
                });
              }
            } else {
              activeSortable._showClone(sortable);
            }
          }
        }
      },
      dragOverAnimationCapture: function dragOverAnimationCapture(_ref11) {
        var dragRect = _ref11.dragRect,
            isOwner = _ref11.isOwner,
            activeSortable = _ref11.activeSortable;
        multiDragElements.forEach(function (multiDragElement) {
          multiDragElement.thisAnimationDuration = null;
        });

        if (activeSortable.options.animation && !isOwner && activeSortable.multiDrag.isMultiDrag) {
          clonesFromRect = _extends({}, dragRect);
          var dragMatrix = matrix(dragEl$1, true);
          clonesFromRect.top -= dragMatrix.f;
          clonesFromRect.left -= dragMatrix.e;
        }
      },
      dragOverAnimationComplete: function dragOverAnimationComplete() {
        if (folding) {
          folding = false;
          removeMultiDragElements();
        }
      },
      drop: function drop(_ref12) {
        var evt = _ref12.originalEvent,
            rootEl = _ref12.rootEl,
            parentEl = _ref12.parentEl,
            sortable = _ref12.sortable,
            dispatchSortableEvent = _ref12.dispatchSortableEvent,
            oldIndex = _ref12.oldIndex,
            putSortable = _ref12.putSortable;
        var toSortable = putSortable || this.sortable;
        if (!evt) return;
        var options = this.options,
            children = parentEl.children; // Multi-drag selection

        if (!dragStarted) {
          if (options.multiDragKey && !this.multiDragKeyDown) {
            this._deselectMultiDrag();
          }

          toggleClass(dragEl$1, options.selectedClass, !~multiDragElements.indexOf(dragEl$1));

          if (!~multiDragElements.indexOf(dragEl$1)) {
            multiDragElements.push(dragEl$1);
            dispatchEvent({
              sortable: sortable,
              rootEl: rootEl,
              name: 'select',
              targetEl: dragEl$1,
              originalEvent: evt
            }); // Modifier activated, select from last to dragEl

            if (evt.shiftKey && lastMultiDragSelect && sortable.el.contains(lastMultiDragSelect)) {
              var lastIndex = index(lastMultiDragSelect),
                  currentIndex = index(dragEl$1);

              if (~lastIndex && ~currentIndex && lastIndex !== currentIndex) {
                // Must include lastMultiDragSelect (select it), in case modified selection from no selection
                // (but previous selection existed)
                var n, i;

                if (currentIndex > lastIndex) {
                  i = lastIndex;
                  n = currentIndex;
                } else {
                  i = currentIndex;
                  n = lastIndex + 1;
                }

                for (; i < n; i++) {
                  if (~multiDragElements.indexOf(children[i])) continue;
                  toggleClass(children[i], options.selectedClass, true);
                  multiDragElements.push(children[i]);
                  dispatchEvent({
                    sortable: sortable,
                    rootEl: rootEl,
                    name: 'select',
                    targetEl: children[i],
                    originalEvent: evt
                  });
                }
              }
            } else {
              lastMultiDragSelect = dragEl$1;
            }

            multiDragSortable = toSortable;
          } else {
            multiDragElements.splice(multiDragElements.indexOf(dragEl$1), 1);
            lastMultiDragSelect = null;
            dispatchEvent({
              sortable: sortable,
              rootEl: rootEl,
              name: 'deselect',
              targetEl: dragEl$1,
              originalEvent: evt
            });
          }
        } // Multi-drag drop


        if (dragStarted && this.isMultiDrag) {
          folding = false; // Do not "unfold" after around dragEl if reverted

          if ((parentEl[expando].options.sort || parentEl !== rootEl) && multiDragElements.length > 1) {
            var dragRect = getRect(dragEl$1),
                multiDragIndex = index(dragEl$1, ':not(.' + this.options.selectedClass + ')');
            if (!initialFolding && options.animation) dragEl$1.thisAnimationDuration = null;
            toSortable.captureAnimationState();

            if (!initialFolding) {
              if (options.animation) {
                dragEl$1.fromRect = dragRect;
                multiDragElements.forEach(function (multiDragElement) {
                  multiDragElement.thisAnimationDuration = null;

                  if (multiDragElement !== dragEl$1) {
                    var rect = folding ? getRect(multiDragElement) : dragRect;
                    multiDragElement.fromRect = rect; // Prepare unfold animation

                    toSortable.addAnimationState({
                      target: multiDragElement,
                      rect: rect
                    });
                  }
                });
              } // Multi drag elements are not necessarily removed from the DOM on drop, so to reinsert
              // properly they must all be removed


              removeMultiDragElements();
              multiDragElements.forEach(function (multiDragElement) {
                if (children[multiDragIndex]) {
                  parentEl.insertBefore(multiDragElement, children[multiDragIndex]);
                } else {
                  parentEl.appendChild(multiDragElement);
                }

                multiDragIndex++;
              }); // If initial folding is done, the elements may have changed position because they are now
              // unfolding around dragEl, even though dragEl may not have his index changed, so update event
              // must be fired here as Sortable will not.

              if (oldIndex === index(dragEl$1)) {
                var update = false;
                multiDragElements.forEach(function (multiDragElement) {
                  if (multiDragElement.sortableIndex !== index(multiDragElement)) {
                    update = true;
                    return;
                  }
                });

                if (update) {
                  dispatchSortableEvent('update');
                }
              }
            } // Must be done after capturing individual rects (scroll bar)


            multiDragElements.forEach(function (multiDragElement) {
              unsetRect(multiDragElement);
            });
            toSortable.animateAll();
          }

          multiDragSortable = toSortable;
        } // Remove clones if necessary


        if (rootEl === parentEl || putSortable && putSortable.lastPutMode !== 'clone') {
          multiDragClones.forEach(function (clone) {
            clone.parentNode && clone.parentNode.removeChild(clone);
          });
        }
      },
      nullingGlobal: function nullingGlobal() {
        this.isMultiDrag = dragStarted = false;
        multiDragClones.length = 0;
      },
      destroyGlobal: function destroyGlobal() {
        this._deselectMultiDrag();

        off(document, 'pointerup', this._deselectMultiDrag);
        off(document, 'mouseup', this._deselectMultiDrag);
        off(document, 'touchend', this._deselectMultiDrag);
        off(document, 'keydown', this._checkKeyDown);
        off(document, 'keyup', this._checkKeyUp);
      },
      _deselectMultiDrag: function _deselectMultiDrag(evt) {
        if (typeof dragStarted !== "undefined" && dragStarted) return; // Only deselect if selection is in this sortable

        if (multiDragSortable !== this.sortable) return; // Only deselect if target is not item in this sortable

        if (evt && closest(evt.target, this.options.draggable, this.sortable.el, false)) return; // Only deselect if left click

        if (evt && evt.button !== 0) return;

        while (multiDragElements.length) {
          var el = multiDragElements[0];
          toggleClass(el, this.options.selectedClass, false);
          multiDragElements.shift();
          dispatchEvent({
            sortable: this.sortable,
            rootEl: this.sortable.el,
            name: 'deselect',
            targetEl: el,
            originalEvent: evt
          });
        }
      },
      _checkKeyDown: function _checkKeyDown(evt) {
        if (evt.key === this.options.multiDragKey) {
          this.multiDragKeyDown = true;
        }
      },
      _checkKeyUp: function _checkKeyUp(evt) {
        if (evt.key === this.options.multiDragKey) {
          this.multiDragKeyDown = false;
        }
      }
    };
    return _extends(MultiDrag, {
      // Static methods & properties
      pluginName: 'multiDrag',
      utils: {
        /**
         * Selects the provided multi-drag item
         * @param  {HTMLElement} el    The element to be selected
         */
        select: function select(el) {
          var sortable = el.parentNode[expando];
          if (!sortable || !sortable.options.multiDrag || ~multiDragElements.indexOf(el)) return;

          if (multiDragSortable && multiDragSortable !== sortable) {
            multiDragSortable.multiDrag._deselectMultiDrag();

            multiDragSortable = sortable;
          }

          toggleClass(el, sortable.options.selectedClass, true);
          multiDragElements.push(el);
        },

        /**
         * Deselects the provided multi-drag item
         * @param  {HTMLElement} el    The element to be deselected
         */
        deselect: function deselect(el) {
          var sortable = el.parentNode[expando],
              index = multiDragElements.indexOf(el);
          if (!sortable || !sortable.options.multiDrag || !~index) return;
          toggleClass(el, sortable.options.selectedClass, false);
          multiDragElements.splice(index, 1);
        }
      },
      eventProperties: function eventProperties() {
        var _this3 = this;

        var oldIndicies = [],
            newIndicies = [];
        multiDragElements.forEach(function (multiDragElement) {
          oldIndicies.push({
            multiDragElement: multiDragElement,
            index: multiDragElement.sortableIndex
          }); // multiDragElements will already be sorted if folding

          var newIndex;

          if (folding && multiDragElement !== dragEl$1) {
            newIndex = -1;
          } else if (folding) {
            newIndex = index(multiDragElement, ':not(.' + _this3.options.selectedClass + ')');
          } else {
            newIndex = index(multiDragElement);
          }

          newIndicies.push({
            multiDragElement: multiDragElement,
            index: newIndex
          });
        });
        return {
          items: _toConsumableArray(multiDragElements),
          clones: [].concat(multiDragClones),
          oldIndicies: oldIndicies,
          newIndicies: newIndicies
        };
      },
      optionListeners: {
        multiDragKey: function multiDragKey(key) {
          key = key.toLowerCase();

          if (key === 'ctrl') {
            key = 'Control';
          } else if (key.length > 1) {
            key = key.charAt(0).toUpperCase() + key.substr(1);
          }

          return key;
        }
      }
    });
  }

  function insertMultiDragElements(clonesInserted, rootEl) {
    multiDragElements.forEach(function (multiDragElement, i) {
      var target = rootEl.children[multiDragElement.sortableIndex + (clonesInserted ? Number(i) : 0)];

      if (target) {
        rootEl.insertBefore(multiDragElement, target);
      } else {
        rootEl.appendChild(multiDragElement);
      }
    });
  }
  /**
   * Insert multi-drag clones
   * @param  {[Boolean]} elementsInserted  Whether the multi-drag elements are inserted
   * @param  {HTMLElement} rootEl
   */


  function insertMultiDragClones(elementsInserted, rootEl) {
    multiDragClones.forEach(function (clone, i) {
      var target = rootEl.children[clone.sortableIndex + (elementsInserted ? Number(i) : 0)];

      if (target) {
        rootEl.insertBefore(clone, target);
      } else {
        rootEl.appendChild(clone);
      }
    });
  }

  function removeMultiDragElements() {
    multiDragElements.forEach(function (multiDragElement) {
      if (multiDragElement === dragEl$1) return;
      multiDragElement.parentNode && multiDragElement.parentNode.removeChild(multiDragElement);
    });
  }

  Sortable.mount(new AutoScrollPlugin());
  Sortable.mount(Remove, Revert);

  Sortable.mount(new SwapPlugin());
  Sortable.mount(new MultiDragPlugin());

  return Sortable;

})));
