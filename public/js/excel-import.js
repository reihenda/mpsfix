// Fungsi untuk menangani tampilan nama file di input
function handleFileInput() {
    $(document).on('change', '.custom-file-input', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName);
    });
}

// Fungsi untuk menampilkan modal template
function showTemplateModal() {
    $('#btnShowTemplate').on('click', function(e) {
        e.preventDefault();
        $('#templateExcelModal').modal('show');
    });
}

// Fungsi untuk menambahkan link template di samping input file
function addTemplateLink() {
    $('.custom-file').after(
        '<div class="mt-2"><a href="#" id="btnShowTemplate" class="text-primary">' +
        '<i class="fas fa-info-circle"></i> Lihat format template Excel</a></div>'
    );
}

// Fungsi untuk menangani klik tombol download template
function handleDownloadTemplate(templateUrl) {
    $('#btnDownloadTemplate').on('click', function() {
        window.location.href = templateUrl;
    });
}

// Fungsi untuk menambahkan spinner saat form disubmit
function handleFormSubmission() {
    $('#formUploadExcel').on('submit', function() {
        // Show loading spinner
        $(this).find('button[type="submit"]').html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
            'Mengimport...' 
        ).prop('disabled', true);
        
        // Continue with form submission
        return true;
    });
}

// Fungsi untuk menampilkan pesan error import jika ada
function displayImportErrors(errors) {
    if (errors && errors.length > 0) {
        let errorHtml = '<div class="alert alert-danger mt-3">' +
            '<strong><i class="fas fa-exclamation-circle"></i> Error saat import:</strong>' +
            '<ul class="mt-2 mb-0">';
        
        errors.forEach(function(error) {
            errorHtml += '<li>' + error + '</li>';
        });
        
        errorHtml += '</ul></div>';
        
        // Tambahkan error ke modal
        $('#uploadExcelModal .modal-body').append(errorHtml);
    }
}

// Fungsi untuk inisialisasi semua handler
function initExcelImport(templateUrl, importErrors) {
    $(document).ready(function() {
        handleFileInput();
        addTemplateLink();
        showTemplateModal();
        handleDownloadTemplate(templateUrl);
        handleFormSubmission();
        
        if (importErrors) {
            displayImportErrors(importErrors);
            $('#uploadExcelModal').modal('show');
        }
    });
}
