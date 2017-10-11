define([
        "dojo/_base/declare",
        "dojo/_base/lang",
        "dojo/on",
        "dojo/mouse",

        "dojo/dom",
        "dojo/dom-construct",
        "dojo/dom-style",
        "dojo/dom-geometry",
        "dojo/window",
        "dojo/request",

        "dijit/Tooltip",
        "dojox/widget/DialogSimple"

    ], function(declare, lang, on, mouse, dom, domConstruct, domStyle, domGeometry, win, request, Tooltip, Dialog) {

    return {

        openWidgetDialog: function(serverId, title, parameters, onClose, onWidgetInstantiation, onWidgetBindSuccess, config) {

            parameters = parameters || {};
            config = config || {};
            title = title || "TITLE?";

            var t = this;

            var vs = win.getBox();

            var w = config.w || Math.max(vs.w-100, 1200);
            var h = config.h || Math.max(vs.h-200, 700);

            var d = t._getModalDialog(title);

            domStyle.set(d.domNode, {
                width:  w + "px",
                height: h + "px"
            });
            d.containerNode.style['overflow'] = config.overflow || "auto";

            domStyle.set(d.containerNode, {
                'height' : (h-25)+'px'
            });

            COOL.widgetFactory(serverId, parameters, function(widget) {

                if(lang.isFunction(onWidgetInstantiation))
                    onWidgetInstantiation(widget);

                widget.onlyContent = true;
                d.addChild( widget );

                setTimeout(function() { widget.resize(); }, 3000);

                widget.dialog = d;

                d.show();

            }, function(widget) {
                if(lang.isFunction(onWidgetBindSuccess))
                    onWidgetBindSuccess(widget);

            });

            if(typeof(onClose)=='function')
                d.connect(d, "hide", function(e){
                    onClose();
                });

            return d;

        },

        _getModalDialog: function(title, href, sizeRatio, dialogMixin) {
            sizeRatio = sizeRatio || 80;
            var vs = win.getBox();

            var w = Math.floor((vs.w/100)*sizeRatio);
            var h = Math.floor((vs.h/100)*sizeRatio);

            var d = new Dialog(lang.mixin({
                title: title,
                content: "",
                draggable: true,
                closable: true,
                href: href,
                maxRatio: sizeRatio,
                scriptHasHooks: true,
                style: "background-color: #FFFFFF;"
            }, dialogMixin || {}));

            d._forcedWidth = w;
            d._forcedHeight = h;

            domStyle.set(d.containerNode, {
                'position' : "relative",
                'padding'  : 0
            });

            return d;
        },

        trackMouseOver: function(node) {
            if(!node._mouseEnterSignal) {
                node._mouseEnterSignal = on(node, mouse.enter, function(){ node._mouseOver = true;});
                node._mouseLeaveSignal = on(node, mouse.leave, function(){ node._mouseOver = false;});
                return false;
            }
            return true;
        },

        showTooltip: function(node, content) {
            Tooltip.show(content, node);

            on.once(node, mouse.leave, function(){
                Tooltip.hide(node);
            });
            console.log("showTooltip is deprecated, use bindTooltip")
        },

        hideTooltip: function(node) {
            Tooltip.hide(node);
        },

        bindTooltip: function(node, content, maxWidth, url) {

            /*
             innerHTML, aroundNode, position, rtl, textDir, onMouseEnter, onMouseLeave

            Tooltip.on('show', function(){
                console.log('ass');
            });*/

            var renderFunc = function(rawContent) {
                return maxWidth ? "<div style='max-width:"+maxWidth+"px; max-height:600px; overflow-y: scroll;'>" + rawContent + "</div>" : rawContent;
            };

            var hideFunc = function() {
                setTimeout( function() {
                    if(!node._mouseOverTip)
                        Tooltip.hide(node);
                }, 500 );
            };

            var showFunc = function() {
                Tooltip.show(node._tooltip_content, node, null, null, null, function() {
                    node._mouseOverTip = true;
                }, function() {
                    node._mouseOverTip = false;
                    hideFunc();
                });
            };

            node._tooltip_content = renderFunc(content);
            node._tooltip_url = url;

            node._toolTipEnterSignal = on(node, mouse.enter, function(){
                if(node._tooltip_url) {
                    if(!node._last_tooltip_url || node._last_tooltip_url != node._tooltip_url) {
                        request(node._tooltip_url).then(function(fetchedContent){
                            node._last_tooltip_url = node._tooltip_url;
                            node._tooltip_content = renderFunc(fetchedContent);
                            showFunc();
                        });
                    } else showFunc();
                } else showFunc();
            });

            node._toolTipLeaveSignal = on(node, mouse.leave, hideFunc);

            if(this.trackMouseOver(node)) {
                if(node._mouseOver)
                    on.emit(node, "mouseover", {
                        bubbles: true,
                        cancelable: true
                    });
            } else {
                console.log("node was not tracked. To solve this issue, call COOL.getDialogManager().trackMouseOver() over it before calling bindTooltip the first time");
            }
        },

        unbindTooltip: function(node) {
            if(node._toolTipEnterSignal) {
                node._toolTipEnterSignal.remove();
                node._toolTipLeaveSignal.remove();
            }
        },

        showXhrError: function (title, url, text) {
            errDialog = new Dialog({
                title: title,
                content: '<div style="padding:5px; border:1px solid black; margin-bottom:10px;">'+url+'</div>'+text,
                style: "width: 1000px; height:600px; overflow:auto;"
            });
            errDialog.show();
        }

    }
 
});