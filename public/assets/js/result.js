class ResultClass {
    constructor(resultTableId, fileId) {
        ResultClass.getResult(fileId, $('#'+resultTableId).find('tbody'));
    }

    static getResult(fileId, resultTable) {
        $.ajax({
            url: '/result', // point to server-side PHP script
            dataType: 'json',  // what to expect back from the PHP script, if anything
            cache: false,
            data: {id: fileId},
            type: 'post',
        }).done((response) => {
            
            $('#fileInfo').html('Result for file '+response.file)
            if (response.status == 'processing') {
                resultTable.html(`
                        <tr><td colspan="3" class="text-center">
                        Seems file was too big and it\'s being processed in the background.<br>
                        It will be reloaded automatically. Or you can access here later.</td></tr>
                `);
                setTimeout(ResultClass.getResult, 5000, fileId, resultTable)
            }

            if(response.status === true) {
                resultTable.html('');
                $.each(response.data, (_, info) => {
                    let row = $('<tr></tr>');
                    $.each(info.objects, (_, obj) => {
                        row.append(`<td>${obj}</td>`)
                    })
                    row.append(`<td>${info.score}</td>`);
                    resultTable.append(row);
                })
            }

            if(response.status === false) {
                resultTable.html('');
                resultTable.html(`
                     <tr><td colspan="3" class="text-center text-danger">${response.data}</td></tr>
                `);
            }
        })
    }
}
