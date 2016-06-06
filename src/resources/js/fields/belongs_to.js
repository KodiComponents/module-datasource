$(function () {
	Vue.component('relation-bt', Vue.extend({
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
		template: '#bt-template',
		ready: function () {
			this.initSelect2();
			CMS.ui.init('icon');
		},
		data: function () {
			this.field = FIELDS[this.key];
			this.section = this.field.section;
			this.related_section = this.field.related_section;
			this.document_id = DOCUMENT[this.section.documentPrimaryKey];
			this.initRecord();

			return {
				section: this.related_section,
				record: this.record
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
								exclude: self.record.id || null
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

				this.record = {
					id: record[pk],
					title: record[title],
					url: BASE_URL + '/datasource/document/edit/' + this.related_section.id + '/' + record[pk]
				};
			},
			removeRecord: function (record) {
				this.record =  {
					id: null,
					title: null,
					url: null
				};

				setTimeout(this.initSelect2, 100);
			},
			initRecord: function () {
				if(DOCUMENT[this.relation]) {
					this.addRecord(DOCUMENT[this.relation]);
				} else {
					this.record = {
						id: null,
						title: null,
						url: null
					};
				}
			}
		}
	}));
});