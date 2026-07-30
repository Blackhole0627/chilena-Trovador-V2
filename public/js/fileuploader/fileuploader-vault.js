$(document).ready(function () {

    // enable fileuploader plugin
    var $fileuploader = $('input.gallery_media').fileuploader({
        fileMaxSize: maxSizeInMb,
        extensions: extensionsVault,    

        captions: lang,

        dialogs: {
            // alert dialog
            alert: function (text) {
                return swal({
                    title: error_oops,
                    text: text,
                    type: "error",
                    confirmButtonText: ok
                });
            },

            // confirm dialog
            confirm: function (text, callback) {
                confirm(text) ? callback() : null;
            }
        },
        changeInput: ' ',
        theme: 'gallery',
        enableApi: true,
        thumbnails: {
            box: '<div class="fileuploader-items">' +
                '<ul class="fileuploader-items-list">' +
                '<li class="fileuploader-input"><button type="button" class="fileuploader-input-inner"><i class="fileuploader-icon-main"></i> <span>${captions.feedback}</span></button></li>' +
                '</ul>' +
                '</div>',
            item: '<li class="fileuploader-item">' +
                '<div class="fileuploader-item-inner">' +
                '<div class="actions-holder">' +
                '<button type="button" class="fileuploader-action fileuploader-action-remove" title="${captions.remove}"><i class="fileuploader-icon-remove"></i></button>' +
                '<div class="gallery-item-dropdown">' +
                '<a class="fileuploader-action-popup">${captions.setting_edit}</a>' +
                '<a class="gallery-action-rename">${captions.setting_rename}</a>' +
                '<a class="gallery-action-asmain">${captions.setting_asMain}</a>' +
                '</div>' +
                '</div>' +
                '<div class="thumbnail-holder">' +
                '${image}' +
                '<span class="fileuploader-action-popup"></span>' +
                '<div class="progress-holder"><span></span>${progressBar}</div>' +
                '</div>' +
                '<div class="content-holder"><h5 title="${name}">${name}</h5><span>${size2}</span></div>' +
                '<div class="type-holder">${icon}</div>' +
                '</div>' +
                '</li>',
            item2: '<li class="fileuploader-item file-main-${data.isMain}">' +
                '<div class="fileuploader-item-inner">' +
                '<div class="actions-holder">' +
                '<button type="button" class="fileuploader-action fileuploader-action-remove" title="${captions.remove}"><i class="fileuploader-icon-remove"></i></button>' +
                '<div class="gallery-item-dropdown">' +
                '<a href="${data.url}" target="_blank">${captions.setting_open}</a>' +
                '<a href="${data.url}" download>${captions.setting_download}</a>' +
                '<a class="fileuploader-action-popup">${captions.setting_edit}</a>' +
                '<a class="gallery-action-rename">${captions.setting_rename}</a>' +
                '<a class="gallery-action-asmain">${captions.setting_asMain}</a>' +
                '</div>' +
                '</div>' +
                '<div class="thumbnail-holder">' +
                '${image}' +
                '<span class="fileuploader-action-popup"></span>' +
                '</div>' +
                '<div class="content-holder"><h5 title="${name}">${name}</h5><span>${size2}</span></div>' +
                '<div class="type-holder">${icon}</div>' +
                '</div>' +
                '</li>',
            itemPrepend: true,
            startImageRenderer: true,
            canvasImage: false,
            onItemShow: function (item, listEl, parentEl, newInputEl, inputEl) {
                var api = $.fileuploader.getInstance(inputEl),
                    color = api.assets.textToColor(item.format),
                    $plusInput = listEl.find('.fileuploader-input'),
                    $progressBar = item.html.find('.progress-holder');

                // put input first in the list
                $plusInput.prependTo(listEl);

                // color the icon and the progressbar with the format color
                item.html.find('.type-holder .fileuploader-item-icon')[api.assets.isBrightColor(color) ? 'addClass' : 'removeClass']('is-bright-color').css('backgroundColor', color);
            },
            onImageLoaded: function (item, listEl, parentEl, newInputEl, inputEl) {
                var api = $.fileuploader.getInstance(inputEl);

                // add icon
                item.image.find('.fileuploader-item-icon i').html('')
                    .addClass('fileuploader-icon-' + (['image', 'video', 'audio'].indexOf(item.format) > -1 ? item.format : 'file'));

                // check the image size
                if (item.format == 'image' && item.upload && !item.imU) {
                    if (item.reader.node && (item.reader.width < 100 || item.reader.height < 100)) {
                        alert(api.assets.textParse(api.getOptions().captions.imageSizeError, item));
                        return item.remove();
                    }

                    item.image.hide();
                    item.reader.done = true;
                    item.upload.send();
                }

            },
            onItemRemove: function (html) {
                html.fadeOut(250);
            }
        },
        dragDrop: {
            container: '.fileuploader-theme-gallery .fileuploader-input'
        },
        upload: {
            url: URL_BASE + '/upload/media/vault',
            data: null,
            type: 'POST',
            enctype: 'multipart/form-data',
            start: true,
            synchron: true,
            chunk: 50,
            beforeSend: function (item) {
                // check the image size first (onImageLoaded)
                if (item.format == 'image' && !item.reader.done)
                    return false;

                item.html.find('.fileuploader-action-success').removeClass('fileuploader-action-success');

                // here you can create upload headers
                item.upload.headers = {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };
            },
            onSuccess: function (result, item) {
                var data = {};

                try {
                    data = JSON.parse(result);
                } catch (e) {
                    data.hasWarnings = true;
                }

                // if success update the information
                if (data.isSuccess && data.files.length) {
                    if (!item.data.listProps)
                        item.data.listProps = {};
                    item.title = data.files[0].title;
                    item.name = data.files[0].name;
                    item.size = data.files[0].size;
                    item.size2 = data.files[0].size2;
                    item.data.url = data.files[0].url;
                    item.data.listProps.id = data.files[0].id;

                    item.html.find('.content-holder h5').attr('title', item.name).text(item.name);
                    item.html.find('.content-holder span').text(item.size2);
                    item.html.find('.gallery-item-dropdown [download]').attr('href', item.data.url);
                }

                // if warnings
                if (data.hasWarnings) {
                    for (var warning in data.warnings) {
                        alert(data.warnings[warning]);
                    }

                    item.html.removeClass('upload-successful').addClass('upload-failed');
                    return this.onError ? this.onError(item) : null;
                }

                delete item.imU;
                item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                setTimeout(function () {
                    item.html.find('.progress-holder').hide();

                    item.html.find('.fileuploader-action-popup, .fileuploader-item-image').show();
                    item.html.find('.fileuploader-action-sort').removeClass('is-hidden');
                    item.html.find('.fileuploader-action-settings').removeClass('is-hidden');
                }, 400);
            },
            onError: function (item) {
                item.html.find('.progress-holder, .fileuploader-action-popup, .fileuploader-item-image').hide();

                // add retry button
                item.upload.status != 'cancelled' && !item.imU && !item.html.find('.fileuploader-action-retry').length ? item.html.find('.actions-holder').prepend(
                    '<button type="button" class="fileuploader-action fileuploader-action-retry" title="Retry"><i class="fileuploader-icon-retry"></i></button>'
                ) : null;
            },
            onProgress: function (data, item) {
                var $progressBar = item.html.find('.progress-holder');

                if ($progressBar.length) {
                    $progressBar.show();
                    $progressBar.find('span').text(data.percentage >= 99 ? 'Uploading...' : data.percentage + '%');
                    $progressBar.find('.fileuploader-progressbar .bar').height(data.percentage + '%');
                }

                item.html.find('.fileuploader-action-popup, .fileuploader-item-image').hide();
            }
        },
        afterRender: function (listEl, parentEl, newInputEl, inputEl) {
            var api = $.fileuploader.getInstance(inputEl),
                $plusInput = listEl.find('.fileuploader-input');

            // bind input click
            $plusInput.on('click', function () {
                api.open();
            });

            // set drop container
            api.getOptions().dragDrop.container = $plusInput;
        },
        onRemove: function (item) {
            // send request
            if (item.data.listProps)
                $name = item.data.listProps.original ? item.data.listProps.original : item.name;
                $.post(URL_BASE + '/delete/media/vault', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    name: $name
                });
        },
    });

    // preload the files
    /*$.post(URL_BASE + '/preload/media/vault',
        {
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (result) {
            var api = $.fileuploader.getInstance($fileuploader),
                preload = [];

            try {
                // preload the files
                preload = JSON.parse(result);

                api.append(preload);
            } catch (e) {
                console.log('Error preloading files -> ', e);
            }
        });*/
});