/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
var varienGrid = new Class.create();

varienGrid.prototype = {
    initialize : function(containerId, url, pageVar, sortVar, dirVar, filterVar){
        this.containerId = containerId;
        this.url = url;
        this.pageVar = pageVar || false;
        this.sortVar = sortVar || false;
        this.dirVar  = dirVar || false;
        this.filterVar  = filterVar || false;
        this.tableSufix = '_table';
        this.useAjax = false;
        this.rowClickCallback = false;
        this.checkboxCheckCallback = false;
        this.preInitCallback = false;
        this.initCallback = false;
        this.initRowCallback = false;
        this.doFilterCallback = false;

        this.reloadParams = false;

        this.trOnMouseOver  = this.rowMouseOver.bindAsEventListener(this);
        this.trOnMouseOut   = this.rowMouseOut.bindAsEventListener(this);
        this.trOnClick      = this.rowMouseClick.bindAsEventListener(this);
        this.trOnDblClick   = this.rowMouseDblClick.bindAsEventListener(this);
        this.trOnKeyPress   = this.keyPress.bindAsEventListener(this);

        this.thLinkOnClick      = this.doSort.bindAsEventListener(this);
        this.initGrid();
    },
    initGrid : function(){
        if(this.preInitCallback){
            this.preInitCallback(this);
        }
        if($(this.containerId+this.tableSufix)){
            this.rows = $$('#'+this.containerId+this.tableSufix+' tbody tr');
            for (var row=0; row<this.rows.length; row++) {
                if(row%2==0){
                    Element.addClassName(this.rows[row], 'even');
                }

                Event.observe(this.rows[row],'mouseover',this.trOnMouseOver);
                Event.observe(this.rows[row],'mouseout',this.trOnMouseOut);
                Event.observe(this.rows[row],'click',this.trOnClick);
                Event.observe(this.rows[row],'dblclick',this.trOnDblClick);

                if(this.initRowCallback){
                    try {
                        this.initRowCallback(this, this.rows[row]);
                    } catch (e) {
                        if(console) {
                            console.log(e);
                        }
                    }
                }
            }
        }
        if(this.sortVar && this.dirVar){
            var columns = $$('#'+this.containerId+this.tableSufix+' thead a');

            for(var col=0; col<columns.length; col++){
                Event.observe(columns[col],'click',this.thLinkOnClick);
            }
        }
        this.bindFilterFields();
        this.bindFieldsChange();
        if(this.initCallback){
            try {
                this.initCallback(this);
            }
            catch (e) {
                if(console) {
                    console.log(e);
                }
            }
        }
    },
    getContainerId : function(){
        return this.containerId;
    },
    rowMouseOver : function(event){
        var element = Event.findElement(event, 'tr');
        Element.addClassName(element, 'on-mouse');

        if (!Element.hasClassName('pointer')
            && (this.rowClickCallback !== openGridRow || element.id)) {
            Element.addClassName(element, 'pointer');
        }
    },
    rowMouseOut : function(event){
        var element = Event.findElement(event, 'tr');
        Element.removeClassName(element, 'on-mouse');
    },
    rowMouseClick : function(event){
        if(this.rowClickCallback){
            try{
                this.rowClickCallback(this, event);
            }
            catch(e){}
        }
        varienGlobalEvents.fireEvent('gridRowClick', event);
    },
    rowMouseDblClick : function(event){
        varienGlobalEvents.fireEvent('gridRowDblClick', event);
    },
    keyPress : function(event){

    },
    doSort : function(event){
        var element = Event.findElement(event, 'a');

        if(element.name && element.target){
            this.addVarToUrl(this.sortVar, element.name);
            this.addVarToUrl(this.dirVar, element.target);
            this.reload(this.url);
        }
        Event.stop(event);
        return false;
    },
    loadByElement : function(element){
        if(element && element.name){
            this.reload(this.addVarToUrl(element.name, element.value));
        }
    },
    reload : function(url){
        url = url || this.url;
        if(this.useAjax){
            new Ajax.Updater(
                this.containerId,
                url + (url.match(new RegExp('\\?')) ? '&ajax=true' : '?ajax=true' ),
                {
                    onComplete:this.initGrid.bind(this),
                    onFailure:this._processFailure.bind(this),
                    evalScripts:true,
                    parameters:this.reloadParams || {},
                    loaderArea: this.containerId
                }
            );
            return;
        }
        else{
            if(this.reloadParams){
                $H(this.reloadParams).each(function(pair){
                    url = this.addVarToUrl(pair.key, pair.value);
                }.bind(this));
            }
            location.href = url;
        }
    },
    /*_processComplete : function(transport){
        console.log(transport);
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }
        if (response.ajaxExpired && response.ajaxRedirect) {
            location.href = response.ajaxRedirect;
            return false;
        }
        this.initGrid();
    },*/
    _processFailure : function(transport){
        location.href = BASE_URL;
    },
    addVarToUrl : function(varName, varValue){
        var re = new RegExp('\/('+varName+'\/.*?\/)');
        var parts = this.url.split(new RegExp('\\?'));
        this.url = parts[0].replace(re, '/');
        this.url+= varName+'/'+varValue+'/';
        if(parts.size()>1) {
            this.url+= '?' + parts[1];
        }
        //this.url = this.url.replace(/([^:])\/{2,}/g, '$1/');
        return this.url;
    },
    doExport : function(){
        if($(this.containerId+'_export')){
            location.href = $(this.containerId+'_export').value;
        }
    },
    bindFilterFields : function(){
        var filters = $$('#'+this.containerId+' .filter input', '#'+this.containerId+' .filter select');
        for (var i in filters){
            Event.observe(filters[i],'keypress',this.filterKeyPress.bind(this));
        }
    },
    bindFieldsChange : function(){
        if (!$(this.containerId)) {
            return;
        }
        var dataElements = $(this.containerId+this.tableSufix).down('.data tbody').getElementsBySelector('input', 'select');
        for(var i=0; i<dataElements.length;i++){
            Event.observe(dataElements[i], 'change', dataElements[i].setHasChanges.bind(dataElements[i]));
        }
    },
    filterKeyPress : function(event){
        if(event.keyCode==Event.KEY_RETURN){
            this.doFilter();
        }
    },
    doFilter : function(){
        var filters = $$('#'+this.containerId+' .filter input', '#'+this.containerId+' .filter select');
        var elements = [];
        for(var i in filters){
            if(filters[i].value && filters[i].value.length) elements.push(filters[i]);
        }
        if (!this.doFilterCallback || (this.doFilterCallback && this.doFilterCallback())) {
            this.reload(this.addVarToUrl(this.filterVar, encode_base64(Form.serializeElements(elements))));
        }
    },
    resetFilter : function(){
        this.reload(this.addVarToUrl(this.filterVar, ''));
    },
    checkCheckboxes : function(element){
        elements = Element.getElementsBySelector($(this.containerId), 'input[name="'+element.name+'"]');
        for(var i=0; i<elements.length;i++){
            this.setCheckboxChecked(elements[i], element.checked);
        }
    },
    setCheckboxChecked : function(element, checked){
        element.checked = checked;
        element.setHasChanges({});
        if(this.checkboxCheckCallback){
            this.checkboxCheckCallback(this,element,checked);
        }
    },
    inputPage : function(event, maxNum){
        var element = Event.element(event);
        var keyCode = event.keyCode || event.which;
        if(keyCode==Event.KEY_RETURN){
            this.setPage(element.value);
        }
        /*if(keyCode>47 && keyCode<58){

        }
        else{
             Event.stop(event);
        }*/
    },
    setPage : function(pageNumber){
        this.reload(this.addVarToUrl(this.pageVar, pageNumber));
    }
};

function openGridRow(grid, event){
    var element = Event.findElement(event, 'tr');
    if(['a', 'input', 'select', 'option'].indexOf(Event.element(event).tagName.toLowerCase())!=-1) {
        return;
    }

    if(element.id){
        setLocation(element.id);
    }
}

var varienGridMassaction = Class.create();
varienGridMassaction.prototype = {
    /* Predefined vars */
    checkedValues: $H({}),
    oldCallbacks: {},
    items: {},
    gridIds: [],
    currentItem: false,
    fieldTemplate: new Template('<input type="hidden" name="#{name}" value="#{value}" />'),
    initialize: function (containerId, grid, checkedValues, formFieldNameInternal, formFieldName) {
       this.setOldCallback('row_click', grid.rowClickCallback);
       this.setOldCallback('init',      grid.initCallback);
       this.setOldCallback('init_row',  grid.initRowCallback);
       this.setOldCallback('pre_init',  grid.preInitCallback);

       this.useAjax   = false;
       this.grid      = grid;
       this.containerId = containerId;
       this.initMassactionElements();

       checkedValues.each(function(item){
           this.checkedValues[item] = item;
       }.bind(this));

       this.formFieldName = formFieldName;
       this.formFieldNameInternal = formFieldNameInternal;

       this.grid.initCallback = this.onGridInit.bind(this);
       this.grid.preInitCallback = this.onGridPreInit.bind(this);
       this.grid.initRowCallback = this.onGridRowInit.bind(this);
       this.grid.rowClickCallback = this.onGridRowClick.bind(this);
       this.initCheckboxes();
       this.checkCheckboxes();
    },
    setUseAjax: function(flag) {
       this.useAjax = flag;
    },
    initMassactionElements: function() {
       this.container = $(this.containerId);
       this.form      = $(this.containerId + '-form');
       this.count      = $(this.containerId + '-count');
       this.validator = new Validation(this.form);
       this.formHiddens    = $(this.containerId + '-form-hiddens');
       this.formAdditional = $(this.containerId + '-form-additional');
       this.select    = $(this.containerId + '-select');
       this.select.observe('change', this.onSelectChange.bindAsEventListener(this));
    },
    setGridIds: function(gridIds) {
        this.gridIds = gridIds;
        this.updateCount();
    },
    getGridIds: function(gridIds) {
        return this.gridIds;
    },
    getOnlyExistsCheckedValues: function()
    {
        var result = [];
        this.checkedValues.each(function(pair){
            if(this.getGridIds().indexOf(pair.key)!=-1) {
                result.push(pair.value);
            }
        }.bind(this));
        return result;
    },
    setItems: function(items) {
        this.items = items;
        this.updateCount();
    },
    getItems: function() {
        return this.items;
    },
    getItem: function(itemId) {
        if(this.items[itemId]) {
            return this.items[itemId];
        }
        return false;
    },
    getOldCallback: function (callbackName) {
        return this.oldCallbacks[callbackName] ? this.oldCallbacks[callbackName] : Prototype.emptyFunction;
    },
    setOldCallback: function (callbackName, callback) {
        this.oldCallbacks[callbackName] = callback;
    },
    onGridPreInit: function(grid) {
        this.initMassactionElements();
        this.getOldCallback('pre_init')(grid);
    },
    onGridInit: function(grid) {
        this.initCheckboxes();
        this.checkCheckboxes();
        this.updateCount();
        this.getOldCallback('init')(grid);
    },
    onGridRowInit: function(grid, row) {
        this.getOldCallback('init_row')(grid, row);
    },
    onGridRowClick: function(grid, evt) {
        var tdElement = Event.findElement(evt, 'td');

        if(!$(tdElement).down('input')) {
            if($(tdElement).down('a') || $(tdElement).down('select')) {
                return;
            }
            var trElement = Event.findElement(evt, 'tr');
            if (trElement.id) {
                setLocation(trElement.id);
            }
            return;
        }

        if(Event.element(evt).isMassactionCheckbox) {
           this.setCheckbox(Event.element(evt));
        } else if (checkbox = this.findCheckbox(evt)) {
           checkbox.checked = !checkbox.checked;
           this.setCheckbox(checkbox);
        }
    },
    onSelectChange: function(evt) {
        var item = this.getSelectedItem();
        if(item) {
            this.formAdditional.update($(this.containerId + '-item-' + item.id + '-block').innerHTML);
        } else {
            this.formAdditional.update('');
        }

        this.validator.reset();
    },
    findCheckbox: function(evt) {
        if(['a', 'input', 'select'].indexOf(Event.element(evt).tagName.toLowerCase())!==-1) {
            return false;
        }
        checkbox = false;
        Event.findElement(evt, 'tr').getElementsBySelector('.massaction-checkbox').each(function(element){
            if(element.isMassactionCheckbox) {
                checkbox = element;
            }
        }.bind(this));
        return checkbox;
    },
    initCheckboxes: function() {
        this.getCheckboxes().each(function(checkbox) {
           checkbox.isMassactionCheckbox = true;
        }.bind(this));
    },
    checkCheckboxes: function() {
        this.getCheckboxes().each(function(checkbox) {
            checkbox.checked = this.checkedValues.keys().indexOf(checkbox.value)!==-1;
        }.bind(this));
    },
    selectAll: function() {
        this.addCheckedValues(this.getGridIds());
        this.checkCheckboxes();
        this.updateCount();
        return false;
    },
    unselectAll: function() {
        this.setCheckedValues([]);
        this.checkCheckboxes();
        this.updateCount();
        return false;
    },
    selectVisible: function() {
        this.addCheckedValues(this.getCheckboxesValues());
        this.checkCheckboxes();
        this.updateCount();
        return false;
    },
    unselectVisible: function() {
        this.unsetCheckedValues(this.getCheckboxesValues());
        this.checkCheckboxes();
        this.updateCount();
        return false;
    },
    setCheckedValues: function(values) {
        this.checkedValues.remove.apply(this.checkedValues, this.checkedValues.keys());
        values.each(function(item){
            this.checkedValues[item] = item;
        }.bind(this));
    },
    addCheckedValues: function(values) {
        values.each(function(item){
            this.checkedValues[item] = item;
        }.bind(this));
    },
    unsetCheckedValues: function(values) {
        if(values.size()) {
            this.checkedValues.remove.apply(this.checkedValues, values);
        }
    },
    getCheckedValues: function() {
        return this.checkedValues.keys();
    },
    getCheckboxes: function() {
        var result = [];
        this.grid.rows.each(function(row){
            var checkboxes = row.getElementsBySelector('.massaction-checkbox');
            checkboxes.each(function(checkbox){
                result.push(checkbox);
            });
        });
        return result;
    },
    getCheckboxesValues: function() {
        var result = [];
        this.getCheckboxes().each(function(checkbox) {
            result.push(checkbox.value);
        }.bind(this));
        return result;
    },
    setCheckbox: function(checkbox) {
        if(checkbox.checked) {
            this.checkedValues[checkbox.value] = checkbox.value;
        } else {
            this.checkedValues.remove(checkbox.value);
        }
        this.updateCount();
    },
    updateCount: function() {
        // Maybe in future this.count.update(this.getOnlyExistsCheckedValues().size());
        this.count.update(this.getCheckedValues().size());
        if(!this.grid.reloadParams) {
            this.grid.reloadParams = {};
        }
        this.grid.reloadParams[this.formFieldNameInternal] = this.getCheckedValues().join(',');
    },
    getSelectedItem: function() {
        if(this.getItem(this.select.value)) {
            return this.getItem(this.select.value);
        } else {
            return false;
        }
    },
    apply: function() {
        var item = this.getSelectedItem();
        if(!item) {
            this.validator.validate();
            return;
        }
        this.currentItem = item;
        var fieldName = (item.field ? item.field : this.formFieldName) + '[]';
        var fieldsHtml = '';

        if(this.currentItem.confirm && !window.confirm(this.currentItem.confirm)) {
            return;
        }

        /* Maybe in future
        this.getOnlyExistsCheckedValues().each(function(item){
            fieldsHtml += this.fieldTemplate.evaluate({name: fieldName, value: item});
        }.bind(this)); */

        this.getCheckedValues().each(function(item){
            fieldsHtml += this.fieldTemplate.evaluate({name: fieldName, value: item});
        }.bind(this));

        this.formHiddens.update(fieldsHtml);

        if(!this.validator.validate()) {
            return;
        }

        if(this.useAjax && item.url) {
            new Ajax.Request(item.url, {
                'method': 'post',
                'parameters': this.form.serialize(true),
                'onComplete': this.onMassactionComplete.bind(this)
            });
        } else if(item.url) {
            this.form.action = item.url;
            this.form.submit();
        }
    },
    onMassactionComplete: function(transport) {
           if(this.currentItem.complete) {
               try {
                  var listener = this.getListener(this.currentItem.complete) || Prototype.emptyFunction;
                  listener(grid, this, transport);
               } catch (e) {}
           }
    },
    getListener: function(strValue) {
        return eval(strValue);
    }
}

var varienGridAction = {
    execute: function(select) {
        if(!select.value || !select.value.isJSON()) {
            return;
        }

        var config = select.value.evalJSON();
        if(config.confirm && !window.confirm(config.confirm)) {
            select.options[0].selected = true;
            return;
        }

        if(config.popup) {
            var win = window.open(config.href, 'action_window', 'width=500,height=600,resizable=1,scrollbars=1');
            win.focus();
            select.options[0].selected = true;
        } else {
            setLocation(config.href);
        }
    }
};