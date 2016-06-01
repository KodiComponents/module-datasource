$(function () {
    Vue.component('relation-mtm', Vue.extend({
        props: {
            relation: {
                type: String,
                required: true
            },
            key: {
                type: String,
                required: true
            }
        },
        template: '#mtm-template',
        ready: function () {
            this.initSelect2();
            CMS.ui.init('icon');
        },
        data: function () {
            this.records = [];
            this.field = FIELDS[this.key];
            this.section = this.field.section;
            this.related_section = this.field.related_section;
            this.document_id = DOCUMENT[this.section.documentPrimaryKey];

            this.initRecords();
            
            return {
                section: this.related_section,
                records: this.records
            }
        },
        methods: {
            initSelect2: function () {
                var sectionId = this.related_section.id,
                    documentId = this.document_id,
                    relatedSection = this.related_section,
                    self = this;

                $('select', this.$el).select2({
                    minimumInputLength: 0,
                    multiple: true,
                    closeOnSelect: false,
                    ajax: {
                        url: '/api.datasource.document.find',
                        dataType: 'json',
                        delay: 250,
                        method: 'get',
                        data: function (params) {
                            return {
                                q: params.term, // search term
                                section_id: sectionId,
                                document_id: documentId,
                                exclude: _.pluck(self.records, 'id')
                            };
                        },
                        processResults: function (data, page) {
                            // parse the results into the format expected by Select2.
                            // since we are using custom formatting functions we do not need to
                            // alter the remote JSON data

                            return {
                                results: data.content
                            };
                        }
                    },
                    select: function (result) {

                    }
                }).on('select2:selecting', function (evt) {
                    self.addRecord(evt.params.args.data);
                    $(this).select2("close");
                    $(this).select2().options;

                    return false;
                });
            },
            addRecord: function (record) {
                var pk = this.related_section.documentPrimaryKey,
                    title = this.related_section.documentTitleKey;

                var data = {
                    id: record[pk],
                    title: record[title],
                    url: BASE_URL + '/datasource/document/edit/' + this.related_section.id + '/' + record[pk]
                };

                if (!this.hasRecord(data))
                    this.records.push(data);
            },
            removeRecord: function (record) {
                this.records = _.reject(this.records, function (r) {
                    return r === record;
                });
                $('select', this.$el).select2('destroy');

                this.initSelect2();
            },
            hasRecord: function (record) {
                return _.where(this.records, {id: record.id}).length > 0;
            },
            initRecords: function () {
                for (i in DOCUMENT[this.relation]) {
                    this.addRecord(
                        DOCUMENT[this.relation][i]
                    )
                }
            }
        }
    }));
});