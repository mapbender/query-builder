(function ($) {
    $.widget("mapbender.mbQueryBuilderElement", {

        options: {
            maxResults: 100
        },
        editTemplate: null,
        editFieldMap_: null,

        _create: function () {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.editTemplate = $('.-js-edit-template', this.element).remove().removeClass('hidden');
            if (Array.isArray(this.options.tableColumns)) {
                this.options.tableColumns.forEach(function (column) {
                    if (column.title) {
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
        _initialize: function () {
            const $toolbar = $('.toolbar', this.element);
            $toolbar.toggleClass('hidden', !this.options.allowCreate);
            if (this.options.allowCreate && this.options.allowSearch) {
                $toolbar.addClass('floating');
            }
            this.interactionButtons_ = this._initInteractionButtons();
            this._initElementEvents();

            this.query("select", null, 'GET').done((results) => {
                this.renderQueryList(results);
            });
            Mapbender.ElementUtil.adjustScrollbarsIfNecessary(this.element);
        },

        /**
         * Execute SQL and export als excel or data.
         * This fake the form POST method to get download export file.
         *
         * @returns jQuery form object
         * @param item
         */
        exportData: function (item) {
            var form = $('<form action="' + this.elementUrl + 'export" style="display: none" method="post"/>')
                .append('<input type="text" name="id"  value="' + item.id + '"/>');
            form.appendTo("body");

            setTimeout(function () {
                form.remove();
            });

            return form.submit();
        },
        exportHtml: function (item) {
            window.open(this.elementUrl + 'exportHtml?id=' + item.id);
        },
        saveData: function (item) {
            return this.query("save", {item: item});
        },
        redrawListTable: function () {
            var dt = this.getListTableApi();
            dt.rows().every(function () {
                this.data(this.data());
            })
            dt.draw(true);
        },
        addQueryRow: function (item) {
            var dt = this.getListTableApi();
            var tr = dt.row.add(item).node();
            // NOTE: current dataTables versions could just do dt.row(tr).show().draw(false)
            var rowIndex = dt.rows({order: 'current'}).nodes().indexOf(tr);
            var pageLength = dt.page.len();
            var rowPage = Math.floor(rowIndex / pageLength);
            dt.page(rowPage);
            dt.draw(false);
        },
        getListTableApi: function () {
            return $('table', this.element).dataTable().api();
        },
        confirmRemoveItem: function (item) {
            var message = [Mapbender.trans('mb.querybuilder.frontend.confirm.remove'), ': ', item[this.options.configuration.titleFieldName]].join('');

            new Mapbender.Popup({
                title: [Mapbender.trans('mb.querybuilder.frontend.Remove'), ' #', item.id].join(''),
                draggable: true,
                modal: false,
                closeOnESC: true,
                content: $(document.createElement('div')).text(message),
                width: 500,
                buttons: [
                    {
                        label: Mapbender.trans('mb.querybuilder.frontend.OK'),
                        cssClass: 'btn btn-sm btn-danger popupClose',
                        callback: () => this._removeItem(item),
                    },
                    {
                        label: Mapbender.trans('mb.actions.close'),
                        cssClass: 'btn btn-sm btn-light popupClose',
                    }
                ]
            });
        },

        _removeItem: function (item) {
            this.query("remove", {id: item.id}).done(() => {
                var dt = this.getListTableApi();
                var dtRow = dt.row(function (_, data) {
                    return data === item;
                });
                if (dtRow) {
                    dtRow.remove();
                    dt.draw(false);
                }
                $.notify(Mapbender.trans('mb.querybuilder.frontend.sql.removed'), 'notice');
            });

        },

        _escapeHtml: function (value) {
            'use strict';
            return ('' + (value || '')).replace(/["&'\/<>]/g, function (a) {
                return {
                    '"': '&quot;', '&': '&amp;', "'": '&#39;',
                    '/': '&#47;', '<': '&lt;', '>': '&gt;'
                }[a];
            });
        },
        /**
         * Executes SQL by ID and display results as popups
         *
         * @param item Item
         * @return XHR Object this has "dialog" property to get the popup dialog.
         */
        loadResults: function (item) {
            return this.query("execute", {id: item.id}, 'GET')
                .then((results) => this._displayResults(item, results));
        },

        _displayResults: function (item, results) {
            var $content = $(document.createElement('div'))
                .data("item", item)
                .addClass('queryBuilder-results')
            ;

            const hasNoResults = !results || !results.length;
            var columnsOption;
            if (hasNoResults) {
                columnsOption = [{data: null, title: ''}];
            } else {
                var columnNames = Object.keys(results[0]);
                columnsOption = columnNames.map((name) => {
                    return {
                        title: name,
                        render: (data, type, row) => {
                            switch (type) {
                                case 'display':
                                    return this._escapeHtml(row[name]);
                                case 'filter':
                                    return ('' + row[name]) || undefined;
                                default:
                                    return row[name];
                            }
                        }
                    };
                });
            }
            $content.append(this.initDataTable({
                selectable: false,
                paging: false,
                data: results,
                searching: false,
                info: false,
                columns: columnsOption
            }));

            const title = Mapbender.trans('mb.querybuilder.frontend.Results') + ": " + item[this.options.configuration.titleFieldName];

            const $dialog = new Mapbender.Popup({
                title: title,
                draggable: true,
                modal: false,
                closeOnESC: true,
                resizable: true,
                cssClass: 'qb-dialog',
                height: Math.min(hasNoResults ? 300 : 800, window.innerHeight * 0.8),
                content: $content,
                width: Math.min(hasNoResults ? 500 : 1000, window.innerWidth * 0.8),
                buttons: this._getDialogButtonsOption(['export', 'export-html']),
            });

            this._addDialogEvents($content);
        },

        initDataTable: function (options) {
            var $table = $(document.createElement('table'))
                .addClass('table table-striped table-condensed table-hover')
            ;
            $table.DataTable(options);
            $table.css('width', '');    // Support auto-growth when resizing dialog
            return $table.closest('.dataTables_wrapper');
        },

        mergeDialogData: function ($dialog) {
            var formData = {};
            const self = this;
            $(':input[name]', $dialog).each(function () {
                var $input = $(this);
                const name = self._getActualFieldName(this);
                formData[name] = (!$input.is(':checkbox') || $input.prop('checked')) && $input.val();
            });
            // NOTE: original data item is modified
            return Object.assign($dialog.data("item"), formData);
        },

        requestInfoForEditDialog: function (item) {
            this.query("edit", {id: item.id}, 'GET').done((result) => {
                this.openEditDialog(result);
            });
        },

        openEditDialog: function (item) {
            var $form = this.editTemplate.clone().data("item", item);
            const self = this;
            $(':input[name]', $form).each(function () {
                const name = self._getActualFieldName(this);
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

            const $dialog = new Mapbender.Popup({
                title: item[this.options.configuration.titleFieldName],
                draggable: true,
                resizable: true,
                modal: false,
                cssClass: 'qb-dialog',
                closeOnESC: true,
                content: $form,
                width: Math.min(600, window.innerWidth * 0.8),
                buttons: this._getDialogButtonsOption(['save', 'execute', 'export', 'export-html', 'delete']),
            });

            this._addDialogEvents($form);
            return $form;
        },
        _initInteractionEventsCommon: function ($scope, dataFn, livePrefix) {
            var self = this;
            var prefix_ = (livePrefix && livePrefix.replace(/\s*$/, ' ')) || '';
            $scope.on('click', prefix_ + '.-fn-export', function () {
                self.exportData(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-export-html', function () {
                self.exportHtml(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-execute', function () {
                self.loadResults(dataFn(this));
            });
            $scope.on('click', prefix_ + '.-fn-delete', function () {
                var item = dataFn(this);
                self.confirmRemoveItem(item).then(function () {
                    // TODO: FIX THIS
                    var $dialog = $('.qb-dialog .ui-dialog-content').filter(function () {
                        return $(this).data('item') === item;
                    });
                    $dialog.dialog('destroy');
                });
            });
        },
        _initElementEvents: function () {
            var self = this;
            var tableDataFn = function (target) {
                return $(target).closest('tr').data('item');
            };
            this.element.on('click', '.-fn-create', function () {
                self.openEditDialog({});
            });
            this.element.on('click', 'table tbody tr .-fn-edit', function () {
                self.requestInfoForEditDialog($(this).closest('tr').data('item'));
            });
            this._initInteractionEventsCommon(this.element, tableDataFn, 'table tbody tr');
        },
        _addDialogEvents: function ($dialog) {
            var self = this;
            var dataFn = function (clickTarget) {
                if (/-fn-execute(\s|$)/.test(clickTarget.className)) {
                    return self.mergeDialogData($dialog)
                } else {
                    return $dialog.data('item');
                }
            };

            $dialog.closest('.popup').on('click', '.-fn-save', function () {
                var item = $dialog.data('item');
                var isNew = !item || !item.id;
                var mergedData = self.mergeDialogData($dialog);

                self.saveData(mergedData).done(function (savedItem) {
                    Object.assign(mergedData, savedItem);
                    if (isNew) {
                        self.addQueryRow(mergedData);
                    } else {
                        self.redrawListTable();
                    }
                    $.notify(Mapbender.trans('mb.querybuilder.frontend.sql.saved'), 'notice');
                });
            });
            this._initInteractionEventsCommon($dialog.closest('.popup'), dataFn);
        },
        /**
         * @param {Array<string>} functions
         * @return {Array<Object>}
         * @private
         */
        _getDialogButtonsOption: function (functions) {
            const buttons = [];
            for (var i = 0; i < functions.length; ++i) {
                const buttonDef = this.interactionButtons_[functions[i]];
                if (buttonDef) {
                    buttons.push({
                        label: buttonDef.label,
                        cssClass: 'btn btn-outlined ' + buttonDef.fnClass + ' ' + (buttonDef.colorClass ?? 'btn-light')
                    });
                }
            }

            buttons.push({
                label: Mapbender.trans('mb.querybuilder.frontend.Cancel'),
                cssClass: 'btn btn-light popupClose',
            });

            return buttons;
        },
        _initInteractionButtons: function () {
            var defs = {};
            if (this.options.allowHtmlExport) {
                defs['export-html'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.HTML-Export'),
                    iconClass: 'fas fa-table',
                    fnClass: '-fn-export-html'
                };
            }
            if (this.options.allowFileExport) {
                defs['export'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Export'),
                    iconClass: 'fas fa-download',
                    fnClass: '-fn-export'
                };
            }
            if (this.options.allowExecute) {
                defs['execute'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Execute'),
                    iconClass: 'fas fa-share-from-square',
                    fnClass: '-fn-execute'
                };
            }
            if (this.options.allowEdit) {
                defs['edit'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Edit'),
                    iconClass: 'fas fa-edit',
                    fnClass: '-fn-edit'
                };
            }
            if (this.options.allowEdit || this.options.allowCreate) {
                defs['save'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Save'),
                    fnClass: '-fn-save',
                    colorClass: 'btn-success',
                };
            }
            if (this.options.allowRemove) {
                defs['delete'] = {
                    label: Mapbender.trans('mb.querybuilder.frontend.Remove'),
                    iconClass: 'far fa-trash-can',
                    fnClass: '-fn-delete',
                    colorClass: 'btn-danger'
                };
            }

            return defs;
        },
        renderQueryList: function (queries) {
            var interactions = ['export', 'export-html', 'execute', 'edit', 'delete'];
            var buttons = [];
            for (var i = 0; i < interactions.length; ++i) {
                var buttonDef = this.interactionButtons_[interactions[i]];
                if (buttonDef) {
                    buttons.push(buttonDef);
                }
            }
            var columnsOption = this.options.tableColumns.slice();
            if (buttons.length) {
                var buttonMarkup = buttons.map(function (buttonDef) {
                    var $icon = $(document.createElement('i'))
                        .addClass('fa')
                        .addClass(buttonDef.iconClass)
                    ;
                    var $button = $(document.createElement('span'))
                        .addClass('qb-button clickable hover-highlight-effect')
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
                    render: function (val, type) {
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
            $tableWrap.append(this.initDataTable(this._getDataTableOptions(queries, columnsOption)));
        },

        _getDataTableOptions: function (queries, columnsOption) {
            return {
                lengthChange: false,
                info: false,
                searching: this.options.allowSearch,
                language: {
                    search: Mapbender.trans('mb.querybuilder.frontend.search'),
                },
                processing: false,
                ordering: true,
                paging: false,
                selectable: false,
                autoWidth: false,
                order: [[1, "asc"]],
                createdRow: (tr, item) => $(tr).data({item: item}),
                data: queries,
                columns: columnsOption
            }
        },

        /**
         * API connection query
         *
         * @param uri suffix
         * @param request query
         * @return xhr jQuery XHR object
         * @version 0.2
         */
        query: function (uri, request, method) {
            return $.ajax({
                url: this.elementUrl + uri,
                type: method || 'POST',
                dataType: "json",
                data: request
            }).fail(function (xhr) {
                var errorMessage = Mapbender.trans('mb.querybuilder.frontend.api.error') + ": " + xhr.statusText;
                $.notify(errorMessage);
                console.error(errorMessage, xhr);
            });
        },

        /**
         * the symfony form prefixes the names with querybuilder, this method extracts the raw name
         */
        _getActualFieldName: function (formElement) {
            let name = formElement.name.substring('querybuilder['.length, formElement.name.length - 1);
            if (this.editFieldMap_[name] !== undefined) {
                name = this.editFieldMap_[name];
            }
            return name;
        }
    });
})(jQuery);
