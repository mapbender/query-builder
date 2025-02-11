(function($) {

    function baseDialog(title, content, options) {
        var $content = $((typeof content === 'string') ? $.parseHTML(content) : content);
        var defaults = {
            classes: {
                'ui-dialog': 'ui-dialog qb-dialog',
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
        $content.on('dialogclose', function() {
            window.setTimeout(function() { $content.dialog('destroy') }, 500);
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
                    text: Mapbender.trans('mb.querybuilder.frontend.OK'),
                    'class': 'button success btn',
                    click: function() {
                        deferred.resolve();
                        $(this).dialog('close');
                        return false;
                    }
                }, {
                    text: Mapbender.trans('mb.querybuilder.frontend.Cancel'),
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

        options:     {
            maxResults: 100
        },
        editTemplate: null,
        editFieldMap_: null,

        _create: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.editTemplate = $('.-js-edit-template', this.element).remove().removeClass('hidden');
            if (Array.isArray(this.options.tableColumns)) {
                this.options.tableColumns.forEach(function(column) {
                    if (column.title){
                        var translationKey = 'mb.querybuilder.frontend.sql.' + column.title.toLowerCase();
                        column.title = Mapbender.trans(translationKey);
                    }
                });
            }
            this.editFieldMap_ = {
                title: this.options.configuration.titleFieldName,
                connection: this.options.configuration.connectionFieldName,
                sql: this.options.configuration.sqlFieldName,
                order: this.options.configuration.orderByFieldName
            };
            this._initialize();
        },
        _initialize: function() {
            var widget = this;
            $('.toolbar', this.element).toggleClass('hidden', !this.options.allowCreate);
            this.interactionButtons_ = this._initInteractionButtons();
            this._initElementEvents();

            widget.query("select", null, 'GET').done(function(results) {
                widget.renderQueryList(results);
            });
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
        redrawListTable: function() {
            var dt = this.getListTableApi();
            dt.rows().every(function() { this.data(this.data()); })
            dt.draw(true);
        },
        addQueryRow: function(item) {
            var dt = this.getListTableApi();
            var tr = dt.row.add(item).node();
            // NOTE: current dataTables versions could just do dt.row(tr).show().draw(false)
            var rowIndex = dt.rows({order: 'current'}).nodes().indexOf(tr);
            var pageLength = dt.page.len();
            var rowPage = Math.floor(rowIndex / pageLength);
            dt.page(rowPage);
            dt.draw(false);
        },
        /**
         * Get list table API
         *
         * @returns {*}
         */
        getListTableApi: function() {
            return $('table', this.element).dataTable().api();
        },

        /**
         * Remove  item data
         *
         * @param item
         * @returns {*}
         */
        removeData: function(item) {
            var widget = this;
            var message = [Mapbender.trans('mb.querybuilder.frontend.confirm.remove'), ': ', item[this.options.configuration.titleFieldName]].join('');
            var title = [Mapbender.trans('mb.querybuilder.frontend.Remove'), ' #', item.id].join('');
            var content = $(document.createElement('div')).text(message);
            return confirmDialog(title, content).then(function() {
                return widget.query("remove", {id: item.id}).done(function() {
                    var dt = widget.getListTableApi();
                    var dtRow = dt.row(function(_, data) {
                        return data === item;
                    });
                    if (dtRow) {
                        dtRow.remove();
                        dt.draw(false);
                    }
                    $.notify(Mapbender.trans('mb.querybuilder.frontend.sql.removed'), 'notice');
                });
            });
        },

        _escapeHtml: function(value) {
            'use strict';
            return ('' + (value || '')).replace(/["&'\/<>]/g, function (a) {
                return {
                    '"': '&quot;', '&': '&amp;', "'": '&#39;',
                    '/': '&#47;',  '<': '&lt;',  '>': '&gt;'
                }[a];
            });
        },
        /**
         * Executes SQL by ID and display results as popups
         *
         * @param item Item
         * @return XHR Object this has "dialog" property to get the popup dialog.
         */
        displayResults: function(item) {
            var widget = this;
            return widget.query("execute", {id: item.id}, 'GET').then(function(results) {
                var $content = $(document.createElement('div'))
                    .data("item", item)
                    .addClass('queryBuilder-results')
                ;
                var columnsOption;
                if (!results || !results.length) {
                    columnsOption = [{data: null, title: ''}];
                } else {
                    var columnNames = Object.keys(results[0]);
                    columnsOption = columnNames.map(function(name) {
                        return {
                            title: name,
                            render: function(data, type, row) {
                                switch (type) {
                                    case 'display':
                                        return widget._escapeHtml(row[name]);
                                    case 'filter':
                                        return ('' + row[name]) || undefined;
                                    default:
                                        return row[name];
                                }
                            }
                         };
                    });
                }
                $content.append(widget.initDataTable({
                    selectable: false,
                    paging: false,
                    data: results,
                    searching: false,
                    info: false,
                    columns: columnsOption
                }));

                var title = Mapbender.trans('mb.querybuilder.frontend.Results') + ": " + item[widget.options.configuration.titleFieldName];
                var $dialog = baseDialog(title, $content, {
                    width: 1000,
                    height: 400,
                    resizable: true,
                    buttons: widget._getDialogButtonsOption(['export', 'export-html'])
                });
                if (typeof ($dialog.dialogExtend) === 'function') {
                    $dialog.dialogExtend({
                        maximizable: true,
                        collapsable: true,
                        closable: true,
                        dblclick: 'maximize'
                    });
                }
                widget._addDialogEvents($dialog);
            });
        },
        initDataTable: function(options) {
            var $table = $(document.createElement('table'))
                .addClass('table table-striped table-condensed table-hover')
            ;
            $table.DataTable(options);
            $table.css('width', '');    // Support auto-growth when resizing dialog
            return $table.closest('.dataTables_wrapper');
        },

        mergeDialogData: function($dialog) {
            var formData = {};
            var nameMap = this.editFieldMap_;
            $(':input[name]', $dialog).each(function() {
                var $input = $(this);
                var name = nameMap[this.name] || this.name;
                formData[name] = (!$input.is(':checkbox') || $input.prop('checked')) && $input.val();
            });
            // NOTE: original data item is modified
            return Object.assign($dialog.data("item"), formData);
        },

        /**
         * Open SQL edit dialog
         *
         * @param item
         */
        openEditDialog: function(item) {
            var buttons = this._getDialogButtonsOption(['save', 'execute', 'export', 'export-html', 'delete']);

            var $form = this.editTemplate.clone().data("item", item);
            var nameMap = this.editFieldMap_;
            $(':input[name]', $form).each(function() {
                var name = nameMap[this.name] || this.name;
                var $input = $(this);
                if (typeof (item[name]) !== 'undefined') {
                    if ($input.is(':checkbox')) {
                        $input.prop('checked', !!item[name] && item[name] !== 'false');
                    } else {
                        $input.val(item[name]);
                    }
                    $input.trigger('change');
                }
            });
            baseDialog(item[this.options.configuration.titleFieldName], $form, {
                width: 600,
                buttons: buttons
            });
            this._addDialogEvents($form);
            return $form;
        },
        _initInteractionEventsCommon: function($scope, dataFn, livePrefix) {
            var self = this;
            var prefix_ = (livePrefix && livePrefix.replace(/\s*$/, ' ')) || '';
            $scope.on('click', prefix_ + '.-fn-export', function() {
                self.exportData(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-export-html', function() {
                self.exportHtml(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-execute', function() {
                self.displayResults(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-delete', function() {
                var item = dataFn(this);
                self.removeData(item).then(function() {
                    var $dialog = $('.qb-dialog .ui-dialog-content').filter(function() {
                        return $(this).data('item') === item;
                    });
                    $dialog.dialog('destroy');
                });
            });
        },
        _initElementEvents: function() {
            var self = this;
            var tableDataFn = function(target) {
                return $(target).closest('tr').data('item');
            };
            this.element.on('click', '.-fn-create', function() {
                self.openEditDialog({});
            });
            this.element.on('click', 'table tbody tr .-fn-edit', function() {
                self.openEditDialog($(this).closest('tr').data('item'));
            });
            this._initInteractionEventsCommon(this.element, tableDataFn, 'table tbody tr');
        },
        _addDialogEvents: function($dialog) {
            var self = this;
            var dataFn = function(clickTarget) {
                if (/-fn-execute(\s|$)/.test(clickTarget.className)) {
                    return self.mergeDialogData($dialog)
                } else {
                    return $dialog.data('item');
                }
            };
            $dialog.closest('.ui-dialog').on('click', '.-fn-save', function() {
                var item = $dialog.data('item');
                var isNew = !item || !item.id;
                var mergedData = self.mergeDialogData($dialog);

                self.saveData(mergedData).done(function(savedItem) {
                    Object.assign(mergedData, savedItem);
                    if (isNew) {
                        self.addQueryRow(mergedData);
                    } else {
                        self.redrawListTable();
                    }
                    $.notify(Mapbender.trans('mb.querybuilder.frontend.sql.saved'), 'notice');
                });
            });
            this._initInteractionEventsCommon($dialog.closest('.ui-dialog'), dataFn);
        },
        /**
         * @param {Array<string>} functions
         * @return {Array<Object>}
         * @private
         */
        _getDialogButtonsOption: function(functions) {
            var buttons = [];
            var noop = function() {};
            var notEmpty = function(x) { return !!x; };
            for (var i = 0; i < functions.length; ++i) {
                var buttonDef = this.interactionButtons_[functions[i]];
                if (buttonDef) {
                    var className = ['button btn', buttonDef.fnClass, buttonDef.colorClass].filter(notEmpty).join(' ');
                    buttons.push({
                        text: buttonDef.label,
                        'class': className,
                        click: noop
                    });
                }
            }

            buttons.push({
                text: Mapbender.trans('mb.querybuilder.frontend.Cancel'),
                'class': 'button btn critical',
                click: function() {
                    $(this).dialog('close');
                }
            });
            return buttons;
        },
        _initInteractionButtons: function() {
            var defs = {};
            if (this.options.allowHtmlExport) {
                defs['export-html'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.HTML-Export'),
                    iconClass: 'fa-table',
                    fnClass: '-fn-export-html'
                };
            }
            if (this.options.allowFileExport) {
                defs['export'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Export'),
                    iconClass: 'fa-download',
                    fnClass: '-fn-export'
                };
            }
            if (this.options.allowExecute) {
                defs['execute'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Execute'),
                    iconClass: 'fa-play',
                    fnClass: '-fn-execute'
                };
            }
            if (this.options.allowEdit) {
                defs['edit'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Edit'),
                    iconClass: 'fa-edit',
                    fnClass: '-fn-edit'
                };
            }
            if (this.options.allowRemove) {
                defs['delete'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Remove'),
                    iconClass: 'fa-remove',
                    fnClass: '-fn-delete',
                    colorClass: 'critical'
                };
            }

            return defs;
        },
        renderQueryList: function(queries) {
            var interactions = ['export', 'export-html', 'execute', 'edit', 'delete'];
            var buttons =[];
            for (var i = 0; i < interactions.length; ++i) {
                var buttonDef = this.interactionButtons_[interactions[i]];
                if (buttonDef) {
                    buttons.push(buttonDef);
                }
            }
            var columnsOption = this.options.tableColumns.slice();
            if (buttons.length) {
                var buttonMarkup = buttons.map(function(buttonDef) {
                    var $icon = $(document.createElement('i'))
                        .addClass('fa fas')
                        .addClass(buttonDef.iconClass)
                    ;
                    var $button = $(document.createElement('span'))
                        .addClass('button')
                        .addClass(buttonDef.fnClass)
                        .addClass(buttonDef.colorClass || '')
                        .attr('title', buttonDef.label)
                        .append($icon)
                    ;
                    return $button.get(0).outerHTML;
                });
                var navMarkup = buttonMarkup.join('');
                columnsOption.push({
                    data: null,
                    title: '',
                    render: function(val, type) {
                        return type === 'display' && navMarkup || null;
                    },
                    width: '1%',
                    orderable: false,
                    searchable: false,
                    className: 'interactions'
                });
            }

            var $tableWrap = $('.-js-table-wrap', this.element);
            $tableWrap.empty();
            $tableWrap.append(this.initDataTable({
                lengthChange: false,
                info:       false,
                searching:  this.options.allowSearch,
                language: {
                    search: Mapbender.trans('mb.querybuilder.frontend.search'),
                },
                processing: false,
                ordering:   true,
                paging:     false,
                selectable: false,
                autoWidth:  false,
                order:      [[1, "asc"]],
                createdRow: function(tr, item) {
                    $(tr).data({item: item})
                },
                data: queries,
                columns: columnsOption
            }));
        },

        /**
         * API connection query
         *
         * @param uri suffix
         * @param request query
         * @return xhr jQuery XHR object
         * @version 0.2
         */
        query: function(uri, request, method) {
            var widget = this;
            return $.ajax({
                url:         widget.elementUrl + uri,
                type: method || 'POST',
                dataType:    "json",
                data: request
            }).fail(function(xhr) {
                var errorMessage = Mapbender.trans('mb.querybuilder.frontend.api.error') + ": " + xhr.statusText;
                $.notify(errorMessage);
                console.error(errorMessage, xhr);
            });
        }
    });
})(jQuery);
