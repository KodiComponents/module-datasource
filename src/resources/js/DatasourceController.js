CMS.controllers.add(['document.get.edit', 'document.get.create'], function () {
    new Vue({el: '#content'});
});

CMS.controllers.add('datasource.get.index', function () {
    $('.mail-nav').height($('.mail-container').height());

    init_section_folders()
});

function init_section_folders() {
    $('.page-mail').on('click', '.create-folder-button', function (e) {
        $('#folder-modal').modal();
        e.preventDefault();
    });

    $('#folder-modal').on('submit', 'form', function (e) {
        var field = $(this).find('input[name="folder-name"]');

        if (field.val()) {
            Api.put('/api.datasource.folder.create', {
                name: field.val()
            }, function (resp) {
                if (resp.content) reload_menu();
                field.val('');
            });

            $('#folder-modal').modal('hide')
        } else {
            CMS.error_field(field, __('Pleas set folder name'));
        }
        e.preventDefault();
    });

    $('.page-mail').on('click', '.remove-folder', function () {
        if (!confirm(__('Are you sure?')))
            return;

        Api.delete('/api.datasource.folder.delete', {
            folder_id: $(this).closest('.folder-container').data('id')
        }, function (response) {
            reload_menu();
        });
    });

    init_sections_sortable();
}

function init_sections_sortable() {
    if ($('.folder-container .panel-heading').size() == 0) {
        $('.section-draggable').remove();
        return;
    }

    $(".sections-list .list-group-item").draggable({
        handle: ".section-draggable",
        axis: 'y',
        revert: 'invalid'
    });

    $('.sections-list .folder-container')
        .droppable({
            hoverClass: "dropable-hover",
            drop: function (event, ui) {
                var current_folder_id = ui.draggable.data('folder-id'),
                    folder_id = $(this).data('id')||0;
                    section_id = ui.draggable.data('id');

                if (folder_id != current_folder_id) {
                    Api.post('/api.datasource.folder.section.section', {
                        section_id: section_id,
                        folder_id: folder_id
                    }, function (response) {
                        if (response.content) reload_menu();
                    });
                }
            }
        });
}

function reload_menu() {
    Api.get('/api.datasource.menu.get', {section_id: SECTION.id}, function(response) {
        $('.page-mail .sections-list').html(response.content);
        CMS.ui.init('icon');
        init_sections_sortable();
    });
}