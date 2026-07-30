(function ($) {
    "use strict";

    // Open modal Vault
    $(document).on('click', '.btnShowVault', function () {
        $(this).blur();
        $('#modalVault').modal('show');

        $('#spinnerVault').show();
        $('#containerFiles').html('');
        $('#searchVault').hide();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'post',
            url: URL_BASE + '/get/vault/files',
            success: function (data) {
                if (data) {
                    $('#searchVault').show();
                    $('#containerFiles').html(data);
                    $('#spinnerVault').hide();
                } else {
                    $('#containerFiles').html('<h6 class="text-center w-100 p-5 d-block">' + no_results_found + '</h6>');
                    $('#spinnerVault').hide();
                }
            }
        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            $('.popout').removeClass('popout-success').addClass('popout-error').html(error_occurred).slideDown('500').delay('5000').slideUp('500');
            $('#containerFiles').html('<h6 class="text-center w-100 p-5 d-block">' + error_occurred + '</h6>');
            $('#spinnerVault').hide();
        });
    });


    $(document).ready(function () {
        // Array para almacenar los elementos seleccionados
        let selectedMedia = [];

        // Función para manejar la selección de elementos de la bóveda
        $(document).on('click', '.vault-item', function () {
            const $item = $(this);
            const isSelected = $item.hasClass('selected');
            const iconCheck = $item.find('.media-icon-check > i');

            if (isSelected) {
                // Deseleccionar elemento
                $item.removeClass('selected');
                const itemId = $item.data('id') || $item.find('img').attr('src');
                selectedMedia = selectedMedia.filter(item => item.id !== itemId);
                iconCheck.removeClass('bi-check-circle-fill');
                iconCheck.addClass('bi-circle');
            } else {
                iconCheck.addClass('bi-check-circle-fill');
                iconCheck.removeClass('bi-circle');
                // Seleccionar elemento
                $item.addClass('selected');

                // Obtener datos del elemento
                const mediaData = {
                    id: $item.data('id') || $item.find('img').attr('src'),
                    name: $item.data('name') || $item.find('.filename').text() || 'archivo',
                    url: $item.data('url') || $item.find('img').attr('src'),
                    type: $item.data('type') || 'image',
                    size: $item.data('size') || 0,
                    local: $item.data('local') || '',
                    element: $item
                };

                selectedMedia.push(mediaData);
            }

            // Actualizar contador en el botón Add
            updateAddButton();
        });

        // Función para actualizar el botón Add
        function updateAddButton() {
            const $addButton = $('#add-media-button, .add-button, [data-action="add"]');
            const count = selectedMedia.length;

            if (count > 0) {
                $addButton.text(`${add} (${count})`).prop('disabled', false);
                $('.vault-footer').removeClass('display-none');
            } else {
                $addButton.text(add).prop('disabled', true);
                $('.vault-footer').addClass('display-none');
            }
        }

        function addSelectedMediaToInput() {
            if (selectedMedia.length === 0) {
                return;
            }

            var api = $.fileuploader.getInstance('input.input-fileuploader');
            var existingFiles = api.getFiles();
            var filesSelected = maximum_files_msg - existingFiles.length;

            if (selectedMedia.length > filesSelected) {
                swal({
                    title: error_oops,
                    text: maximumFilesMsgError + ' (' + filesSelected + ')',
                    type: "warning",
                    confirmButtonText: ok
                });
                return;
            }

            // Create more detailed maps to avoid duplicates
            const existingFilesMap = new Map();

            existingFiles.forEach(function (file, index) {
                const key = file.data && file.data.vault && file.data.id
                    ? `vault_${file.data.id}`
                    : `local_${file.name}_${file.size}`;

                existingFilesMap.set(key, {
                    index: index,
                    file: file,
                    type: file.data && file.data.vault ? 'vault' : 'local'
                });
            });

            // Filter and prepare new files
            const newFilesToAdd = [];
            const duplicateFiles = [];

            selectedMedia.forEach(function (media) {
                const vaultKey = `vault_${media.id}`;
                const nameKey = `local_${media.name}_${media.size || 0}`;

                if (existingFilesMap.has(vaultKey) || existingFilesMap.has(nameKey)) {
                    duplicateFiles.push(media.name);
                } else {
                    newFilesToAdd.push({
                        name: media.name,
                        size: media.size || 0,
                        type: media.type,
                        file: media.url,
                        local: media.local,
                        data: {
                            url: media.url,
                            id: media.id,
                            vault: true
                        }
                    });
                }
            });

            // Report duplicates if there are any
            if (duplicateFiles.length > 0 && newFilesToAdd.length === 0) {
                swal({
                    title: information,
                    text: `${followingFilesAlreadyAdded} ${duplicateFiles.join(', ')}`,
                    type: "info",
                    confirmButtonText: ok
                });
                return;
            } else if (duplicateFiles.length > 0) {
                swal({
                    title: information,
                    text: `${newFilesHaveBeenAddedFollowingAlreadyExisted} ${duplicateFiles.join(', ')}`,
                    type: "info",
                    confirmButtonText: ok
                });
            }

            if (newFilesToAdd.length > 0) {
                api.append(newFilesToAdd);
            }

            // Clear selection and close modal/vault
            clearSelection();
            closeVault();
            $('.fileuploader-theme-thumbnails').addClass('d-block');
        }

        $('#modalVault').on('hidden.bs.modal', function (e) {
            e.preventDefault();
            clearSelection();
            closeVault();
            $('#vaultSearch').val('');
        });

        // Función para limpiar la selección
        function clearSelection() {
            $('.vault-item.selected').removeClass('selected');
            selectedMedia = [];
            updateAddButton();
        }

        // Function to close the vault (adjust according to your implementation)
        function closeVault() {
            $('#modalVault').modal('hide');
            $('#vaultSearch').val('');
        }

        // Event listener para el botón Add
        $(document).on('click', '#add-media-button, .add-button, [data-action="add"]', function (e) {
            e.preventDefault();
            addSelectedMediaToInput();
        });

        // Event listener para cancelar selección
        $(document).on('click', '.cancel-button, .close, [data-action="cancel"]', function (e) {
            e.preventDefault();
            clearSelection();
            closeVault();
            $('#vaultSearch').val('');
        });

    });

    $('#vaultSearch').on('input', function () {
        const searchTerm = $(this).val().toLowerCase();

        $('.vault-item').each(function () {
            const itemName = $(this).data('name').toLowerCase();
            if (itemName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Conteo global de elementos visibles
        const visibleItems = $('.vault-item:visible').length;

        // Maneja la alerta de "no resultados"
        if (visibleItems === 0 && searchTerm !== '') {
            $('#containerFiles').append('<h6 class="text-center w-100 p-5 d-block no-has-results">' + no_results_found + '</h6>');
        } else {
            $('.no-has-results').remove();
        }

    });

})(jQuery);