(function($) {

    /**
     * Translate digitizer keywords
     * @param title
     * @returns {*}
     */
    function trans(title) {
        var key = "mb.query.builder." + title;
        return Mapbender.trans(key);
    }

    function baseDialog(title, content, options) {
        var $content = $((typeof content === 'string') ? $.parseHTML(content) : content);
        var defaults = {
            classes: {
                'ui-dialog': 'ui-dialog mb-element-popup-dialog modal-content qb-dialog',
                'ui-dialog-titlebar': 'ui-dialog-titlebar modal-header',
                'ui-dialog-titlebar-close': 'ui-dialog-titlebar-close close',
                'ui-dialog-content': 'ui-dialog-content modal-body',
                'ui-dialog-buttonpane': 'ui-dialog-buttonpane modal-footer',
                'ui-button': 'ui-button button btn'
            },
            resizable: false,
            hide: {
                effect: 'fadeOut',
                duration: 200
            }
        };
        var options_ = Object.assign({}, defaults, {title: title}, options || {});
        $content.dialog(options_);
        // Hide text labels on .ui-button-icon-only, with or without jqueryui css
        $('.ui-dialog-titlebar .ui-button-icon-only', $content.closest('.ui-dialog')).each(function() {
            var $button = $(this);
            var $icon = $('.ui-button-icon', this);
            $button.empty().append($icon);
        });

        return $content;
    }

    /**
     * Example:
     *     confirmDialog('Dialog title', '<p>Really show this dialog?'</p>')
     * @param {String} title
     * @param {string|Element|jQuery} content
     * @returns {Promise}
     */
    function confirmDialog(title, content) {
        var deferred = $.Deferred();
        baseDialog(title, content, {
            modal: true,
            buttons:[
                {
                    text: Mapbender.trans('mb.query.builder.OK'),
                    'class': 'button success btn',
                    click: function() {
                        deferred.resolve();
                        $(this).dialog('close');
                        return false;
                    }
                }, {
                    text: Mapbender.trans('mb.query.builder.Cancel'),
                    'class': 'button critical btn',
                    click:   function() {
                        deferred.reject();
                        $(this).dialog('close');
                        return false;
                    }
                }
            ]
        });
        return deferred.promise();
    }


    $.widget("mapbender.mbQueryBuilderElement", {

        sqlList:     [],
        connections: [],
        options:     {
            maxResults: 100
        },

        _create: function() {
            var widget = this;
            var element = $(widget.element);
            widget.elementUrl = Mapbender.configuration.application.urls.element + '/' + element.attr('id') + '/';
            widget._initialize();
        },

        /**
         * Execute SQL and export als excel or data.
         * This fake the form POST method to get download export file.
         *
         * @returns jQuery form object
         * @param item
         */
        exportData: function(item) {
            var widget = this;
            var form = $('<form action="' + widget.elementUrl + 'export" style="display: none" method="post"/>')
                .append('<input type="text" name="id"  value="' + item.id + '"/>');
            form.appendTo("body");

            setTimeout(function() {
                form.remove();
            });

            return form.submit();
        },

        /**
         * Export as HTML.
         *
         * @param item
         */
        exportHtml: function(item) {
            var widget = this;
            window.open(widget.elementUrl + 'exportHtml?id='+item.id);
        },

        /**
         * Save item data
         * @param item
         * @returns {*}
         */
        saveData: function(item) {
            var widget = this;
            return widget.query("save", {item: item});
        },

        /**
         * Redraw list table
         */
        redrawListTable: function(){
            var widget = this;
            var tableApi = widget.getListTableApi();
            return;
            // TODO: get this work!
            tableApi.clear();
            tableApi.rows.add(widget.sqlList);
            tableApi.draw();
        },

        /**
         * Get list table API
         *
         * @returns {*}
         */
        getListTableApi: function() {
            var widget = this;
            var element = widget.element;
            return $(" > div > .mapbender-element-result-table", element).resultTable("getApi");
        },

        /**
         * Remove  item data
         *
         * @param item
         * @returns {*}
         */
        removeData: function(item) {
            var widget = this;
            var message = [Mapbender.trans('mb.query.builder.confirm.remove'), ': ', item[this.options.titleFieldName]].join('');
            var title = [Mapbender.trans('mb.query.builder.Remove'), ' #', item.id].join('');
            var content = $(document.createElement('div')).text(message);
            return confirmDialog(title, content).then(function() {
                return widget.query("remove", {id: item.id}).done(function() {
                    $.each(widget.sqlList, function(i, _item) {
                        if(_item === item) {
                            widget.sqlList.splice(i, 1);
                            return false;
                        }
                        });
                    })
                ;
            });
        },

        /**
         * Get column names
         *
         * @param items
         * @returns {Array}
         */
        getColumnNames: function(items) {
            var columns = [];
            if(items.length) {
                for (var key in items[0]) {
                    columns.push({
                        data:  key,
                        title: key
                    });
                }
            }
            return columns;
        },

        /**
         * Executes SQL by ID and display results as popups
         *
         * @param item Item
         * @return XHR Object this has "dialog" property to get the popup dialog.
         */
        displayResults: function(item) {
            var widget = this;
            return widget.query("execute", {id: item.id}).then(function(results) {
                var $content = $(document.createElement('div'))
                    .data("item", item)
                    .addClass('queryBuilder-results')
                    .generateElements({
                        type:       "resultTable", //searching:  true,
                        selectable: false, //paginate:   false,
                        paging:     false,
                        //searching:  true,
                        name:       "results",
                        data:       results,
                        info:       false,
                        columns:    widget.getColumnNames(results)
                    })
                ;
                var title = Mapbender.trans('mb.query.builder.Results') + ": " + item[widget.options.titleFieldName];
                var $dialog = baseDialog(title, $content, {
                    width: 1000,
                    height: 400,
                    resizable: true,
                    buttons: [widget.exportButton, widget.exportHtmlButton, widget.closeButton]
                });
                if (typeof ($dialog.dialogExtend) === 'function') {
                    $dialog.dialogExtend({
                        maximizable: true,
                        collapsable: true,
                        closable: true,
                        dblclick: 'maximize'
                    });
                }
            });
        },

        /**
         * Open SQL edit dialog
         *
         * @param item
         */
        openEditDialog: function(item) {
            var widget = this;
            var config = widget.options;
            var buttons = [];

            if (config.allowSave) {
                buttons.push({
                    text:      trans('Save'),
                    'className': 'fa-floppy-o',
                    'class': 'button btn',
                    click:     function(e) {
                        var dialog = $(this);
                        var originData = dialog.data("item");
                        $.extend(originData, dialog.formData())

                        dialog.disableForm();
                        widget.saveData(originData).done(function() {
                            dialog.enableForm();
                            widget.redrawListTable();
                            $.notify(trans('sql.saved'),"notice");
                        });
                    }
                });
            }

            config.allowExecute && buttons.push(widget.executeButton);
            config.allowExport && buttons.push(widget.exportButton);
            config.allowExport && buttons.push(widget.exportHtmlButton);
            config.allowRemove && buttons.push(widget.removeButton);

            buttons.push(widget.closeButton);

            var $form = $("<form class='queryBuilder-edit'>")
                .data("item", item)
                .generateElements({
                    children: [{
                        type:     "fieldSet",
                        children: [{
                            title:       trans("sql.title"), // "Name"
                            type:        "input",
                            css:         {"width": "45%"},
                            name:        config.titleFieldName,
                            placeholder: "Query name",
                            options:     widget.connections
                        }, {
                            title:   trans("sql.connection.name"), //  "Connection name"
                            type:    "select",
                            name:    config.connectionFieldName,
                            css:     {"width": "25%"},
                            value:   item.connection_name,
                            options: widget.connections
                        }, {
                            title:   "Order",
                            type:    "input",
                            name:    config.orderByFieldName,
                            value:   item[config.orderByFieldName],
                            css:     {"width": "15%"}
                        }, {
                            title: trans("sql.publish"), //  "Anzeigen"
                            type:  "checkbox",
                            css:   {"width": "15%"},
                            value: 1,
                            name:  config.publicFieldName
                        }]
                    }, {
                        type:  "textArea",
                        title: "SQL",
                        name:  config.sqlFieldName,
                        rows:  16
                    }]
                })
                .formData(item)
            ;
            // Work around visui not initializing select value visuals properly
            $('select', $form).trigger('change');
            baseDialog(item[this.options.titleFieldName], $form, {
                width: 600,
                buttons: buttons
            });

            if( !config.allowSave){
                $form.disableForm();
            }
            return $form;
        },

        _initialize: function() {
            var widget = this;
            var config = widget.options;
            $('.toolbar', widget.element).toggleClass('hidden', !config.allowCreate);
            var exportButton = widget.exportButton = {
                text:  trans('Export'),
                className: 'fa-download',
                'class': 'button btn',
                click: function() {
                    widget.exportData ($(this).data("item"));
                }
            };

            var exportHtmlButton = widget.exportHtmlButton = {
                text:  trans('HTML-Export'),
                className: 'fa-table',
                'class': 'button btn',
                click: function() {
                    widget.exportHtml($(this).data("item"));
                }
            };

            widget.closeButton = {
                text:  trans('Cancel'),
                'class': 'button critical btn',
                click: function() {
                    $(this).dialog('close');
                }
            };

            var editButton = widget.editButton = {
                text:      trans('Edit'),
                className: 'fa-edit',
                click:     function(e) {
                    widget.openEditDialog($(this).data("item"));
                }
            };
            this.element.on('click', '.-fn-create', function() {
                widget.openEditDialog({connection_name:"default"});
            });

            var removeButton = widget.removeButton = {
                text:      trans('Remove'),
                className: 'fa-remove',
                'class':   'button critical btn',
                click:     function(e) {
                    var target = $(this);
                    var item = target.data("item");
                    var isDialog = target.hasClass('ui-dialog-content');

                    widget.removeData(item).then(function() {
                        widget.redrawListTable();
                        if(isDialog) {
                            target.popupDialog('close');
                        }
                        $.notify(trans('sql.removed'), "notice");
                    });
                }
            };

            var executeButton = widget.executeButton = {
                text:      trans('Execute'),
                className: 'fa-play',
                'class':   'button critical btn',
                click: function() {
                    var dialog = $(this);
                    var originData = dialog.data("item");
                    var tempItem = dialog.formData();

                    $.extend(tempItem, originData);

                    widget.displayResults(tempItem);
                }
            };

            widget.query("connections").done(function(connections) {
                widget.connections = connections;
                widget.query("select").done(function(results) {
                    var buttons = [];
                    var columns = config.tableColumns;

                    config.allowExport && buttons.push(exportButton);
                    config.allowExport && buttons.push(exportHtmlButton);
                    config.allowExecute && buttons.push(executeButton);
                    config.allowEdit && buttons.push(editButton);
                    config.allowRemove && buttons.push(removeButton);

                    _.each(columns, function(column) {
                        if(column.title){
                            var title = "sql."+column.title.toLowerCase();
                            column.title = trans(title);
                        }
                    });
                    $('.toolbar', widget.element).nextAll().remove();

                    widget.element.generateElements({children: [{
                        type:         "resultTable",
                        name:         "queries",
                        lengthChange: false,
                        info:       false,
                        searching:  config.allowSearch,
                        processing: false,
                        ordering:   true,
                        paging:     false,
                        selectable: false,
                        autoWidth:  false,
                        order:      [[1, "asc"]],
                        buttons:    buttons,
                        data:       results,
                        columns:    columns
                    }]});
                    widget.sqlList = results;
                });
            });
        },

        /**
         * API connection query
         *
         * @param uri suffix
         * @param request query
         * @return xhr jQuery XHR object
         * @version 0.2
         */
        query: function(uri, request) {
            var widget = this;
            return $.ajax({
                url:         widget.elementUrl + uri,
                type:        'POST',
                dataType:    "json",
                data: request
            }).error(function(xhr) {
                var errorMessage = trans("api.error") + ": " + xhr.statusText;
                $.notify(errorMessage);
                console.error(errorMessage, xhr);
            });
        }
    });
})(jQuery);
