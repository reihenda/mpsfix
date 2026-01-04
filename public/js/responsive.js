// Responsive JavaScript for better mobile experience

$(function() {
    // Function to check if we're on a mobile device
    function isMobile() {
        return window.innerWidth < 768;
    }

    // Initialize any DataTables with responsive configuration
    if ($.fn.dataTable) {
        // Default configuration for responsive DataTables
        var responsiveDataTableConfig = {
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "ordering": true,
            "pageLength": isMobile() ? 5 : 10,
            "language": {
                "emptyTable": "Tidak ada data tersedia",
                "zeroRecords": "Tidak ada data yang cocok ditemukan",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                "infoFiltered": "(disaring dari _MAX_ total entri)",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "»",
                    "previous": "«"
                }
            },
            "dom": isMobile() ?
                "<'row'<'col-12'f>>" +
                "<'row'<'col-12't>>" +
                "<'row'<'col-12'p>>" :
                "<'row'<'col-6'l><'col-6'f>>" +
                "<'row'<'col-12't>>" +
                "<'row'<'col-5'i><'col-7'p>>"
        };

        // Initialize specific tables if they exist
        if ($("#dataPencatatanTable").length) {
            var dataPencatatanTable = $("#dataPencatatanTable").DataTable({
                ...responsiveDataTableConfig,
                "order": [[1, 'desc']],
                "language": {
                    ...responsiveDataTableConfig.language,
                    "emptyTable": "Tidak ada data pencatatan tersedia"
                }
            });
        }

        if ($("#depositHistoryTable").length) {
            var depositHistoryTable = $("#depositHistoryTable").DataTable({
                ...responsiveDataTableConfig,
                "order": [[1, 'desc']],
                "language": {
                    ...responsiveDataTableConfig.language,
                    "emptyTable": "Tidak ada riwayat deposit"
                }
            });
        }

        if ($("#pricingHistoryTable").length) {
            var pricingHistoryTable = $("#pricingHistoryTable").DataTable({
                ...responsiveDataTableConfig,
                "order": [[1, 'desc']],
                "language": {
                    ...responsiveDataTableConfig.language,
                    "emptyTable": "Tidak ada riwayat harga"
                }
            });
        }

        // Add responsive class to all datatables containers
        $('.dataTables_wrapper').addClass('container-fluid dt-bootstrap4');
    }

    // Fix table header on mobile
    if (isMobile()) {
        $('.dataTable thead th').css('white-space', 'nowrap');

        // Add horizontal scroll indicator to tables
        $('.table-responsive').each(function() {
            if (!$(this).next('.scroll-indicator').length) {
                $(this).after('<div class="scroll-indicator text-center text-muted small my-2">← Scroll →</div>');
            }
        });

        // Enhance modals for mobile
        $('.modal').on('show.bs.modal', function() {
            $(this).find('.modal-dialog').addClass('modal-dialog-scrollable');
            $(this).find('.modal-content').css('min-height', '100%');
        });

        // Handle nested modals better on mobile
        $('#depositHistoryModal').on('shown.bs.modal', function() {
            $('body').addClass('modal-open-deposit');
        });

        $('#depositHistoryModal').on('hidden.bs.modal', function() {
            $('body').removeClass('modal-open-deposit');
            if ($('#tambahDepositModal').hasClass('show')) {
                $('body').addClass('modal-open');
            }
        });

        // Optimize cards for mobile
        $('.card-tools').each(function() {
            if ($(this).find('.btn').length > 1) {
                $(this).addClass('d-flex flex-column w-100 mt-2');
                $(this).find('.btn').addClass('mb-2 w-100');
            }
        });

        // Optimize card headers for mobile
        $('.card-header').each(function() {
            $(this).addClass('flex-column align-items-start');
        });

        // Add scroll to top button
        if ($('#scrollTopBtn').length === 0) {
            var scrollTopBtn = $('<button id="scrollTopBtn" class="btn btn-primary btn-sm rounded-circle position-fixed" style="bottom: 20px; right: 20px; display: none; z-index: 1000;"><i class="fas fa-arrow-up"></i></button>');
            $('body').append(scrollTopBtn);

            $(window).scroll(function() {
                if ($(this).scrollTop() > 300) {
                    $('#scrollTopBtn').fadeIn();
                } else {
                    $('#scrollTopBtn').fadeOut();
                }
            });

            $('#scrollTopBtn').click(function() {
                $('html, body').animate({scrollTop: 0}, 500);
                return false;
            });
        }
    }

    // Function to format numbers
    function formatNumber(number, decimals = 2) {
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Koreksi meter calculation if the elements exist
    if ($('#modalTekananKeluar').length && $('#modalSuhu').length && $('#modalHasilKoreksi').length) {
        function hitungKoreksiMeter() {
            const tekananKeluar = parseFloat($('#modalTekananKeluar').val()) || 0;
            const suhu = parseFloat($('#modalSuhu').val()) || 0;

            const A = (tekananKeluar + 1.01325) / 1.01325;
            const B = 300 / (suhu + 273);
            const C = 1 + (0.002 * tekananKeluar);

            const hasilKoreksi = A * B * C;

            $('#modalHasilKoreksi').val(hasilKoreksi.toFixed(8));
        }

        // Trigger calculation on input changes
        $('#modalTekananKeluar, #modalSuhu').on('input', hitungKoreksiMeter);

        // Initialize calculation when modal opens
        $('#setPricingModal').on('show.bs.modal', function() {
            hitungKoreksiMeter();
            setTimeout(function() {
                $('#pricingDate').focus();
            }, 500);
        });
    }

    // Form validation if pricing form exists
    if ($('#pricingForm').length) {
        $('#pricingDate').on('change', function() {
            const selectedDate = $(this).val();
            if (!selectedDate) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Periode harus diisi</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Form submission validation
        $('#pricingForm').on('submit', function(e) {
            const hargaPerM3 = parseFloat($('#modalHargaPerM3').val());
            const tekananKeluar = parseFloat($('#modalTekananKeluar').val());
            const suhu = parseFloat($('#modalSuhu').val());

            let hasError = false;

            // Validate price
            if (isNaN(hargaPerM3) || hargaPerM3 < 0) {
                $('#modalHargaPerM3').addClass('is-invalid');
                if (!$('#modalHargaPerM3').next('.invalid-feedback').length) {
                    $('#modalHargaPerM3').after('<div class="invalid-feedback">Harga harus lebih dari 0</div>');
                }
                hasError = true;
            } else {
                $('#modalHargaPerM3').removeClass('is-invalid');
                $('#modalHargaPerM3').next('.invalid-feedback').remove();
            }

            // Validate pressure
            if (isNaN(tekananKeluar) || tekananKeluar < 0) {
                $('#modalTekananKeluar').addClass('is-invalid');
                if (!$('#modalTekananKeluar').next('.invalid-feedback').length) {
                    $('#modalTekananKeluar').after('<div class="invalid-feedback">Tekanan keluar harus valid</div>');
                }
                hasError = true;
            } else {
                $('#modalTekananKeluar').removeClass('is-invalid');
                $('#modalTekananKeluar').next('.invalid-feedback').remove();
            }

            // Validate temperature
            if (isNaN(suhu)) {
                $('#modalSuhu').addClass('is-invalid');
                if (!$('#modalSuhu').next('.invalid-feedback').length) {
                    $('#modalSuhu').after('<div class="invalid-feedback">Suhu harus valid</div>');
                }
                hasError = true;
            } else {
                $('#modalSuhu').removeClass('is-invalid');
                $('#modalSuhu').next('.invalid-feedback').remove();
            }

            if (hasError) {
                e.preventDefault();
                // Show error toast on mobile
                if (isMobile() && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Mohon periksa kembali isian form',
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
                return false;
            }
        });
    }

    // Initialize tooltips
    if (typeof $.fn.tooltip !== 'undefined') {
        if (isMobile()) {
            $('[data-toggle="tooltip"]').tooltip({
                trigger: 'click',
                placement: 'top'
            });
        } else {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }

    // Add pricing history button if it doesn't exist
    if ($('.card-tools').length && !$('.card-tools button[data-target="#pricingHistoryModal"]').length && $('#pricingHistoryModal').length) {
        var btn = '<button class="btn btn-info btn-sm mr-2" data-toggle="modal" data-target="#pricingHistoryModal">' +
                  '<i class="fas fa-history mr-1"></i> Riwayat Harga' +
                  '</button>';

        $('.card-tools').prepend(btn);
    }

    // Handle orientation change for better mobile experience
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            // Recalculate DataTables if they exist
            if (typeof dataPencatatanTable !== 'undefined' && dataPencatatanTable !== null) {
                dataPencatatanTable.responsive.recalc();
            }
            if (typeof depositHistoryTable !== 'undefined' && depositHistoryTable !== null) {
                depositHistoryTable.responsive.recalc();
            }
            if (typeof pricingHistoryTable !== 'undefined' && pricingHistoryTable !== null) {
                pricingHistoryTable.responsive.recalc();
            }
        }, 200);
    });

    // Handle form submission with visual feedback
    $('form').on('submit', function() {
        var form = $(this);

        // Only proceed if form is valid
        if (form[0].checkValidity()) {
            // Show loading state
            var submitBtn = form.find('button[type="submit"]');
            if (submitBtn.length) {
                var originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');
                submitBtn.prop('disabled', true);

                // Reset button after 10 seconds (failsafe)
                setTimeout(function() {
                    submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
                }, 10000);
            }
        }

        return true;
    });

    // Add close button to alerts after a delay
    setTimeout(function() {
        $('.alert:not(.alert-dismissible)').addClass('alert-dismissible').append(
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        );
    }, 5000);

    // Auto-hide alerts after 7 seconds
    setTimeout(function() {
        $('.alert.alert-success, .alert.alert-info').fadeOut('slow');
    }, 7000);

    // Make tables responsive with mobile-friendly attribute data-labels
    if (isMobile()) {
        $('table.responsive-table-card').each(function() {
            var table = $(this);

            // Get all headers
            var headers = [];
            table.find('thead th').each(function() {
                headers.push($(this).text().trim());
            });

            // Add data-label to each cell for mobile display
            table.find('tbody tr').each(function() {
                $(this).find('td').each(function(i) {
                    if (headers[i]) {
                        $(this).attr('data-label', headers[i]);
                    }
                });
            });
        });
    }

    // Add fullscreen functionality to cards if AdminLTE is available
    if (typeof $.fn.Fullscreen !== 'undefined') {
        $('.card').each(function() {
            var card = $(this);

            // Only add if card doesn't already have a fullscreen button
            if (card.find('[data-card-widget="maximize"]').length === 0) {
                var fullscreenBtn = $('<button class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>');
                card.find('.card-tools').prepend(fullscreenBtn);
            }
        });
    }
});
