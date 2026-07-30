
window.videoDuration = null;

$(document).ready(function () {
    // enable fileuploader plugin
    $('input[name="media"]').fileuploader({
        limit: 1,
        fileMaxSize: maxSizeInMb,
        extensions: extensionsReels,

        captions: {
            button: function () {
                return browse_file;
            },
            feedback: function () {
                return choose_file_to_upload;
            },
            feedback2: function (options) {
                return options.length + ' ' + (options.length > 1 ? more_files_chosen : one_file_chosen);
            },

            confirmDelete: confirmDelete,
            cancel: cancelUpload,
            name: nameFile,
            type: typeFile,
            size: sizeFile,
            dimensions: dimensionsFile,
            duration: durationFile,
            crop: cropFile,
            rotate: rotateFile,
            sort: sortFiles,
            download: downloadFile,
            remove: removeFile,
            drop: dropFiles,
            paste: '<div class="fileuploader-pending-loader"></div> ' + pasteFiles,
            removeConfirmation: removeConfirmation,
            errors: {
                filesLimit: function (options) {
                    return filesLimit + ' ${limit} ' + (options.limit == 1 ? iFile : iFiles)
                },
                filesType: filesType + ' ${extensions}',
                fileSize: '${name} ' + fileSize + ' ${fileMaxSize}MB.',
                filesSizeAll: filesSizeAll + ' ${maxSize} MB.',
                fileName: fileName + ' (${name})',
                remoteFile: remoteFile,
                folderUpload: folderUpload
            }
        },

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

        changeInput: '<div class="fileuploader-input">' +
            '<div class="fileuploader-input-inner">' +
            '<div class="fileuploader-icon-main"></div>' +
            '<h3 class="fileuploader-input-caption"><span>${captions.feedback}</span></h3>' +
            '<p>${captions.or}</p>' +
            '<button type="button" class="fileuploader-input-button"><span>${captions.button}</span></button>' +
            '</div>' +
            '</div>',
        theme: 'dragdrop',
        enableApi: true,
        upload: {
            url: URL_BASE + '/upload/media/reel/file',
            data: null,
            type: 'POST',
            enctype: 'multipart/form-data',
            start: true,
            synchron: false,
            chunk: 50,
            beforeSend: function (item, listEl, parentEl, newInputEl, inputEl) {

                    let file = item.file;
                    let dataURL = URL.createObjectURL(file);
                    let video = document.createElement("video");
                    video.src = dataURL;
                    video.onloadedmetadata = () => {                        
                        const videoDurarion = Math.trunc(video.duration);
                        const proportion = video.videoWidth / video.videoHeight;

                        if (videoDurarion > 60) {
                            swal({
                                title: error_oops,
                                text: errorReelMaxVideosLength,
                                type: "error",
                                confirmButtonText: ok
                            });

                            let api = $.fileuploader.getInstance('input[name="media"]');
                            api.reset();
                        }

                        if (proportion > 0.75) {
                            swal({
                                title: error_oops,
                                text: errorProportionVideoReel,
                                type: "error",
                                confirmButtonText: ok
                            });

                            let api = $.fileuploader.getInstance('input[name="media"]');
                            api.reset();
                        }

                        $('#videoDurarion').val(videoDurarion);

                        if (!statusVideoEncoding) {
                            // Generate preview at second 1
                            generateVideoPreview(video, file.name);
                        }
                    };


                // here you can create upload headers
                item.upload.headers = {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };

            },
            onSuccess: function (result, item) {
                var data = {};

                // get data
                if (result && result.files)
                    data = result;
                else
                    data.hasWarnings = true;

                // if success
                if (data.isSuccess && data.files[0]) {
                    item.name = data.files[0].name;
                    item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                }

                // if warnings
                if (data.hasWarnings) {
                    var errors = '';

                    for (var warning in data.warnings) {
                        errors += data.warnings[warning];
                    }

                    // if errors
                    if (result.errors) {
                        for (var error in result.errors) {
                            errors += result.errors[error];
                        }
                    }

                    // item.remove();
                    item.html.removeClass('upload-successful').addClass('upload-failed');
                    item.html.find('.fileuploader-action-retry').remove();
                    item.html.find('.column-title').html('<div class="text-danger">' + errors + '</div>')

                    // go out from success function by calling onError function
                    // in this case we have a animation there
                    // you can also response in PHP with 404
                    return this.onError ? this.onError(item) : null;
                }

                item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');
                setTimeout(function () {
                    item.html.find('.progress-bar2').fadeOut(400);
                }, 400);
            },
            onError: function (item) {
                var progressBar = item.html.find('.progress-bar2');

                if (progressBar.length) {
                    progressBar.find('span').html(0 + "%");
                    progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                    item.html.find('.progress-bar2').fadeOut(400);
                }

            },
            onProgress: function (data, item) {
                var progressBar = item.html.find('.progress-bar2');

                if (progressBar.length > 0) {
                    progressBar.show();
                    progressBar.find('span').html(data.percentage + "%");
                    progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                }
            },
            onComplete: null,
        },
        onRemove: function (item) {
            $.post(URL_BASE + '/delete/reel/media', {
                file: item.name,
                thumbnail: $('#videoThumbnail').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            });
        }
    });
});

function generateVideoPreview(video, originalFileName) {
    return new Promise((resolve, reject) => {
        // Create canvas to capture the frame
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Set time to 1 second
        video.currentTime = 1;
        
        video.onseeked = function() {
            try {
                // Setting the canvas size
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                // Draw the current frame of the video on the canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Convert canvas to blob
                canvas.toBlob(function(blob) {
                    if (blob) {
                        // Create FormData to send the image
                        const formData = new FormData();
                        
                        // Generate name for preview image
                        const previewFileName = generatePreviewFileName(originalFileName);
                        formData.append('preview_image', blob, previewFileName);
                        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                        
                        $.ajax({
                            url: URL_BASE + '/upload/media/reel/preview',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log('Preview generated successfully:', response);
                                $('#videoThumbnail').val(response.data.preview_filename);
                                resolve(response);
                            },
                            error: function(xhr, status, error) {
                                console.error('Error generating preview:', error);
                                reject(error);
                            }
                        });
                    } else {
                        reject('Error converting canvas to blob');
                    }
                }, 'image/jpeg', 0.8); // JPEG 80% quality
                
            } catch (error) {
                console.error('Error capturing frame from video:', error);
                reject(error);
            }
        };
        
        video.onerror = function() {
            reject('Error loading video');
        };
    });
}

function generatePreviewFileName(originalFileName) {
    const nameWithoutExt = originalFileName.substring(0, originalFileName.lastIndexOf('.'));

    const timestamp = Date.now();
    
    return `${nameWithoutExt}_preview_${timestamp}.jpg`;
}