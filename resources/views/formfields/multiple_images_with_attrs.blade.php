<br/>
<div id="fine-uploader-gallery"></div>
<br/>
<div class="multiple-images sortable">
    @if(isset($dataTypeContent->{$row->field}))
        <?php $images = json_decode($dataTypeContent->{$row->field}); ?>
        @if($images != null)
            @foreach($images as $image)
                <div class="image-item">
                    <div class="img_settings_container" data-field-name="{{ $row->field }}">
                        <img src="{{ Voyager::image( $image->name ) }}" data-image="{{ $image->name }}" data-id="{{ $dataTypeContent->getKey() }}" />
                        <div class="links">
                            <a href="#" class="voyager-params show-inputs"></a>
                            <a href="#" class="voyager-x remove-multi-image-ext"></a>
                        </div>

                        <div class="form-group">
                            <label class="alt-field"><b>alt:</b><input class="form-control" type="text" name="{{ $row->field }}_ext[{{ $loop->index }}][alt]" value="{{ $image->alt }}" /></label>
                            <label class="title-field"><b>title:</b><input class="form-control" type="text" name="{{ $row->field }}_ext[{{ $loop->index }}][title]" value="{{ $image->title }}" /></label>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>

<div class="clearfix"></div>
@pushonce('javascript:extended_bread_form_fields')
<script src="https://cdnjs.cloudflare.com/ajax/libs/file-uploader/5.16.2/jquery.fine-uploader/jquery.fine-uploader.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5sortable/0.9.11/html5sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.1.1/handlebars.min.js"></script>
<script>
    sortable('.sortable')[0].addEventListener('sortupdate', function (e) {
        var i = 0;
        var objs = [];
        $('.sortable img').each(function(index, elem) {
            var obj = new Object();
            var alt = $(elem).closest('.img_settings_container').find('.alt-field input.form-control').val();
            var title = $(elem).closest('.img_settings_container').find('.title-field input.form-control').val();
            obj.name = $(elem).data("image");
            obj.alt = alt.length > 0 ? alt : null;
            obj.title = title.length > 0 ? title : null;
            obj.sort = i++;
            objs.push(obj);
        });
        objs = JSON.stringify(objs).replace(/\//g, '\\/');

        $.ajax({
            type: "POST",
            data: {
                sortedList: objs,
                id: '{{ $dataTypeContent->id  }}',
                slug: '{{ $dataType->slug }}',
                field: '{{ $row->field }}',
            },
            url: '{{ route('sort.gallery') }}'
        });
    });


    document.addEventListener('DOMContentLoaded', function () {

        sortable('.sortable', {
            forcePlaceholderSize: true,
            placeholderClass: 'ph-class'
        });

        $('#fine-uploader-gallery').fineUploader({
            template: 'qq-template-gallery',
            request: {
                endpoint: '{{ route('upload.gallery') }}',
                customHeaders: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                inputName: '{{ $row->field }}',
                params: {
                    slug: '{{ $dataType->slug }}',
                    id: '{{ $dataTypeContent->id  }}',
                    field: '{{ $row->field }}',
                    row: '<?php echo base64_encode(serialize($row)) ?>',
                    options: '<?php echo serialize($row->details) ?>'
                }
            },
            thumbnails: {
                placeholders: {
                    waitingPath: 'https://cdnjs.cloudflare.com/ajax/libs/file-uploader/5.16.2/all.fine-uploader/placeholders/waiting-generic.png',
                    notAvailablePath: 'https://cdnjs.cloudflare.com/ajax/libs/file-uploader/5.16.2/all.fine-uploader/placeholders/not_available-generic.png'
                }
            },
            validation: {
                allowedExtensions: ['jpeg', 'jpg', 'gif', 'png']
            },
            callbacks: {
                onComplete: function (id, name, responseJSON, xhr) {
                    if (responseJSON.success) {
                        $('.qq-file-id-' + id).remove();
                        var source = document.getElementById("image-placeholder-template").innerHTML;
                        var template = Handlebars.compile(source);
                        var context = {
                            field: "{{ $row->field }}",
                            dataTypeKey: '<?php echo $dataTypeContent->getKey() ?>',
                            name: responseJSON.filename,
                            image: responseJSON.image,
                            slug: '{{ $dataType->slug }}',
                            alt: '',
                            title: '',
                            index: $('.multiple-images').length + 1
                        };
                        var html = template(context);
                        $('.multiple-images').append(html);
                    }
                }
            }
        });


        $(document).on('click', '.remove-multi-image-ext', function (e) {
            e.preventDefault();
            var $image = $(this).parent().siblings('img');

            params = {
                slug: '{{ $dataType->slug }}',
                image: $image.data('image'),
                id: $image.data('id'),
                field: $image.parent().data('field-name'),
                multiple_ext: true,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text($image.data('image'));
            $('#confirm_delete_modal').modal('show');
        });

        $(document).on('click', '#confirm_delete', function (e) {
            $image.parent().fadeOut(300, function () {
                $(this).remove();
            });
            $image = null;
            $('#confirm_delete_modal').modal('hide');
        });

        $(document).on('click', '.show-inputs', function (e) {
            e.preventDefault();
            $(this).parent().parent().children('.form-group').toggle();
        });
    });
</script>
<script type="text/template" id="qq-template-gallery">
    <div class="qq-uploader-selector qq-uploader qq-gallery" qq-drop-area-text="Drop files here">
        <div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
            <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
        </div>
        <div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>
            <span class="qq-upload-drop-area-text-selector"></span>
        </div>
        <div class="qq-upload-button-selector qq-upload-button">
            <div>Upload a file</div>
        </div>
        <span class="qq-drop-processing-selector qq-drop-processing">
                <span>Processing dropped files...</span>
                <span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
            </span>
        <ul class="qq-upload-list-selector qq-upload-list" role="region" aria-live="polite" aria-relevant="additions removals">
            <li>
                <span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
                <div class="qq-progress-bar-container-selector qq-progress-bar-container">
                    <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-progress-bar-selector qq-progress-bar"></div>
                </div>
                <span class="qq-upload-spinner-selector qq-upload-spinner"></span>
                <div class="qq-thumbnail-wrapper">
                    <img class="qq-thumbnail-selector" qq-max-size="120" qq-server-scale>
                </div>
                <button type="button" class="qq-upload-cancel-selector qq-upload-cancel">X</button>
                <button type="button" class="qq-upload-retry-selector qq-upload-retry">
                    <span class="qq-btn qq-retry-icon" aria-label="Retry"></span>
                    Retry
                </button>

                <div class="qq-file-info">
                    <div class="qq-file-name">
                        <span class="qq-upload-file-selector qq-upload-file"></span>
                        <span class="qq-edit-filename-icon-selector qq-edit-filename-icon" aria-label="Edit filename"></span>
                    </div>
                    <input class="qq-edit-filename-selector qq-edit-filename" tabindex="0" type="text">
                    <span class="qq-upload-size-selector qq-upload-size"></span>
                    <button type="button" class="qq-btn qq-upload-delete-selector qq-upload-delete">
                        <span class="qq-btn qq-delete-icon" aria-label="Delete"></span>
                    </button>
                    <button type="button" class="qq-btn qq-upload-pause-selector qq-upload-pause">
                        <span class="qq-btn qq-pause-icon" aria-label="Pause"></span>
                    </button>
                    <button type="button" class="qq-btn qq-upload-continue-selector qq-upload-continue">
                        <span class="qq-btn qq-continue-icon" aria-label="Continue"></span>
                    </button>
                </div>
            </li>
        </ul>

        <dialog class="qq-alert-dialog-selector">
            <div class="qq-dialog-message-selector"></div>
            <div class="qq-dialog-buttons">
                <button type="button" class="qq-cancel-button-selector">Close</button>
            </div>
        </dialog>

        <dialog class="qq-confirm-dialog-selector">
            <div class="qq-dialog-message-selector"></div>
            <div class="qq-dialog-buttons">
                <button type="button" class="qq-cancel-button-selector">No</button>
                <button type="button" class="qq-ok-button-selector">Yes</button>
            </div>
        </dialog>

        <dialog class="qq-prompt-dialog-selector">
            <div class="qq-dialog-message-selector"></div>
            <input type="text">
            <div class="qq-dialog-buttons">
                <button type="button" class="qq-cancel-button-selector">Cancel</button>
                <button type="button" class="qq-ok-button-selector">Ok</button>
            </div>
        </dialog>
    </div>
</script>

@include('extended-fields::formfields.image_placeholder');

@endpushonce

@pushonce('css:extended_bread_form_fields')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/file-uploader/5.16.2/all.fine-uploader/fine-uploader-gallery.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/file-uploader/5.16.2/jquery.fine-uploader/fine-uploader-new.css">
<style>
    .ph-class {
        border: 1px dashed #ccc;
        width: 200px;
        height: 200px;
        background: #eeeee;
    }

    .image-item {
        position: relative;
    }

    .multiple-images {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .multiple-images .links {
        justify-content: center;
        display: flex;
    }

    .multiple-images .links a {
        margin: 0 5px;
    }

    .multiple-images > div {
        display: flex;
        flex-direction: column;
        margin-right: 10px;
    }

    .multiple-images img {
        max-width: 200px;
        height: auto;
        display: block;
        padding: 2px;
        border: 1px solid #ddd;
        margin-bottom: 5px;
    }

    .multiple-images .form-group {
        display: none;
    }

    .multiple-images label {
        display: block;
    }

    .multiple-images label b {
        display: inline-block;
        font-size: 10px;
        width: 25px;
    }

    .multiple-images label input {
        width: 160px;
        display: inline-block;
    }
</style>
@endpushonce