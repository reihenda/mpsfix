/**
 * JavaScript kustom untuk halaman nomor polisi
 * Versi yang ditingkatkan untuk kompatibilitas dengan server hosting
 */

// Pastikan BASE_URL sudah didefinisikan
var BASE_URL = window.BASE_URL || '';

// Initialize document ready handler
$(document).ready(function() {
    console.log('Document ready handler triggered');
    
    // Inisialisasi DataTable dengan penanganan error yang lebih baik
    try {
        if($.fn.DataTable) {
            $('#nopolTable').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                // Tambahkan konfigurasi untuk mengatasi mData undefined error
                "columnDefs": [
                    {
                        "defaultContent": "-",
                        "targets": "_all"
                    }
                ],
                // Penanganan error
                "error": function (xhr, error, thrown) {
                    console.error('DataTables error:', error);
                }
            });
            console.log('DataTable initialized successfully');
        } else {
            console.warn('DataTable plugin not available');
        }
    } catch(e) {
        console.error('Error initializing DataTable:', e);
    }
    
    // Fungsi untuk menangani perubahan ukuran dropdown
    function handleUkuranDropdown(selectId, containerClass) {
        $(selectId).on('change', function() {
            if ($(this).val() === 'tambah_baru') {
                $(containerClass).show();
            } else {
                $(containerClass).hide();
                // Reset ukuran_new field
                $(containerClass + ' input').val('');
            }
        });
    }
    
    // Terapkan untuk form tambah
    handleUkuranDropdown('#ukuran_id', '.ukuran-baru-container');
    
    // Terapkan untuk form edit
    handleUkuranDropdown('#edit_ukuran_id', '.edit-ukuran-baru-container');
    
    // Fungsi untuk menangani perubahan status
    function handleStatusChange(statusId, noGtmId) {
        $(statusId).on('change', function() {
            var status = $(this).val() || '';
            // Reset No GTM saat status berubah
            $(noGtmId).val('');
            
            // Jika status milik atau disewakan, tampilkan info bahwa No GTM akan otomatis terisi
            if (status === 'milik' || status === 'disewakan') {
                $(noGtmId).attr('placeholder', 'Akan terisi otomatis saat disimpan');
            } else {
                $(noGtmId).attr('placeholder', 'No GTM');
            }
        });
    }
    
    // Terapkan untuk form tambah
    handleStatusChange('#status', '#no_gtm');
    
    // Terapkan untuk form edit
    handleStatusChange('#edit_status', '#edit_no_gtm');
    
    // Fungsi validasi yang aman untuk menghindari TypeError pada trim()
    function safeValidate(value) {
        // Memeriksa apakah value valid, merupakan string, dan memiliki metode trim
        if (value !== null && value !== undefined && typeof value === 'string' && typeof value.trim === 'function') {
            return value.trim() === '';
        }
        return false;
    }
    
    // Validasi form tambah
    $('#formTambahNopol').on('submit', function(e) {
        var isValid = true;
        var nopol = $('#nopol').val() || '';
        
        // Validasi nomor polisi - pengecekan null, undefined, atau string kosong
        if (!nopol || safeValidate(nopol)) {
            isValid = false;
            alert('Nomor Polisi harus diisi!');
        }
        
        return isValid;
    });
    
    // Validasi form edit
    $('#formEditNopol').on('submit', function(e) {
        var isValid = true;
        var nopol = $('#edit_nopol').val() || '';
        
        // Validasi nomor polisi - pengecekan null, undefined, atau string kosong
        if (!nopol || safeValidate(nopol)) {
            isValid = false;
            alert('Nomor Polisi harus diisi!');
        }
        
        return isValid;
    });
    
    // Event handler untuk tombol edit dengan metode yang lebih kompatibel
    $(document).on('click', '.edit-nopol', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Mencegah event propagation
        
        console.log('Edit button clicked');
        
        try {
            // Metode yang lebih kompatibel untuk mengambil data dari atribut HTML
            var $button = $(this);
            var id = $button.attr('data-id') || '';
            var nopol = $button.attr('data-nopol') || '';
            var keterangan = $button.attr('data-keterangan') || '';
            var jenis = $button.attr('data-jenis') || '';
            var ukuranId = $button.attr('data-ukuran-id') || '';
            var areaOperasi = $button.attr('data-area-operasi') || '';
            var noGtm = $button.attr('data-no-gtm') || '';
            var status = $button.attr('data-status') || '';
            var iso = $button.attr('data-iso') || '';
            var coi = $button.attr('data-coi') || '';
            
            console.log('Button data attributes checked:', 
                'id='+id, 
                'nopol='+nopol, 
                'keterangan='+keterangan, 
                'jenis='+jenis, 
                'ukuranId='+ukuranId, 
                'areaOperasi='+areaOperasi, 
                'noGtm='+noGtm, 
                'status='+status, 
                'iso='+iso, 
                'coi='+coi
            );
            
            // Jika tidak ada data di atribut, coba menggunakan jQuery .data() sebagai fallback
            if (!id) id = $button.data('id') || '';
            if (!nopol) nopol = $button.data('nopol') || '';
            if (!keterangan) keterangan = $button.data('keterangan') || '';
            if (!jenis) jenis = $button.data('jenis') || '';
            if (!ukuranId) ukuranId = $button.data('ukuran-id') || $button.data('ukuranId') || '';
            if (!areaOperasi) areaOperasi = $button.data('area-operasi') || $button.data('areaOperasi') || '';
            if (!noGtm) noGtm = $button.data('no-gtm') || $button.data('noGtm') || '';
            if (!status) status = $button.data('status') || '';
            if (!iso) iso = $button.data('iso') || '';
            if (!coi) coi = $button.data('coi') || '';
            
            console.log('Data after fallback checks:', 
                'id='+id, 
                'nopol='+nopol, 
                'keterangan='+keterangan, 
                'jenis='+jenis, 
                'ukuranId='+ukuranId, 
                'areaOperasi='+areaOperasi, 
                'noGtm='+noGtm, 
                'status='+status, 
                'iso='+iso, 
                'coi='+coi
            );
            
            // Dapatkan URL base dari dokumen jika tidak didefinisikan
            var baseUrl = '';
            if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
                baseUrl = BASE_URL;
            } else {
                // Fallback: Coba dapatkan dari meta tag
                var metaBaseUrl = $('meta[name="base-url"]').attr('content');
                if (metaBaseUrl) {
                    baseUrl = metaBaseUrl;
                } else {
                    // Fallback lain: Dapatkan dari lokasi window
                    var windowUrl = window.location.href;
                    var urlParts = windowUrl.split('/');
                    // Ambil hingga path /nomor-polisi
                    var indexOfRekap = urlParts.indexOf('nomor-polisi');
                    if (indexOfRekap !== -1) {
                        baseUrl = urlParts.slice(0, indexOfRekap).join('/');
                    } else {
                        // Ambil http(s)://domain.com saja
                        baseUrl = urlParts[0] + '//' + urlParts[2];
                    }
                }
            }
            
            console.log('Using Base URL:', baseUrl);
            
            // Set form action URL
            if (id) {
                var actionUrl = baseUrl + '/nomor-polisi/' + id;
                console.log('Setting form action to:', actionUrl);
                $('#formEditNopol').attr('action', actionUrl);
            } else {
                console.error('No ID found for edit action');
            }
            
            // Isi form dengan data (dengan pengecekan keamanan)
            $('#edit_nopol').val(nopol);
            $('#edit_keterangan').val(keterangan);
            $('#edit_jenis').val(jenis);
            $('#edit_ukuran_id').val(ukuranId);
            $('#edit_area_operasi').val(areaOperasi);
            $('#edit_no_gtm').val(noGtm);
            $('#edit_status').val(status);
            $('#edit_iso').val(iso);
            $('#edit_coi').val(coi);
            
            // Sembunyikan input ukuran baru
            $('.edit-ukuran-baru-container').hide();
            
            // Tampilkan modal
            $('#editNopolModal').modal('show');
        } catch (error) {
            console.error('Error in edit button handler:', error);
            alert('Terjadi kesalahan saat mengedit data. Silakan coba lagi.');
        }
    });
    
    // Perbaikan untuk aria-hidden issue dan masalah scrolling pada Bootstrap modal
    $('.modal').on('show.bs.modal', function () {
        // Hapus aria-hidden dari close button saat modal terbuka
        $(this).find('.close').removeAttr('aria-hidden');
        
        // Fix untuk scrolling issue
        $('body').addClass('modal-open-scrollable');
    });
    
    $('.modal').on('hidden.bs.modal', function () {
        // Fix untuk scrolling issue saat modal ditutup
        $('body').removeClass('modal-open-scrollable');
    });
    
    // Log konfirmasi bahwa script sudah dimuat
    console.log('Nomor polisi script loaded successfully');
});