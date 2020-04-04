class UploadClass {
    constructor(fileInputId) {
        this.fileInput = $("#"+fileInputId)
        this.init();
    }

    init() {
        this.fileInput.on('change', () => {this.uploadFile()});
    }

    uploadFile() {
        let file_data = this.fileInput.prop('files')[0];
        let form_data = new FormData();
        form_data.append('file', file_data);
        this.fileInput.parent('upload-button').addClass('disable');
        this.fileInput.prop('disabled', true)
        this.fileInput.siblings('span').html('Uploading...')
        $.ajax({
            url: '/upload', // point to server-side PHP script
            dataType: 'json',  // what to expect back from the PHP script, if anything
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            type: 'post',
        }).done((response) => {this.handleResponse(response)})
            .always(()=>{
                this.fileInput.parent('upload-button').removeClass('disable');
                this.fileInput.prop('disabled', false);
                this.fileInput.siblings('span').html('Upload')
                this.fileInput.val('')
            })
    }

    handleResponse(response) {
        if(typeof response !== 'object' || !response.success) {
            $('#error').html('Something gone wrong');
            return;
        }
        let data = response.data
        window.location.href = '/result/'+data.id;
    }
}