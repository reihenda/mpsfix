/**
 * Script untuk menangani fungsi umum di aplikasi
 */

// Fungsi aman untuk mendapatkan nomor telepon
function getPhoneNumber(element) {
    try {
        // Periksa apakah element ada dan memiliki value
        if(element && element.value !== undefined) {
            return element.value;
        }
        
        // Periksa apakah element ada dan memiliki atribut data-phone
        if(element && element.getAttribute && element.getAttribute('data-phone')) {
            return element.getAttribute('data-phone');
        }
        
        // Jika element adalah string (selector), coba cari element tersebut
        if(typeof element === 'string') {
            const el = document.querySelector(element);
            if(el && el.value !== undefined) {
                return el.value;
            }
            if(el && el.getAttribute && el.getAttribute('data-phone')) {
                return el.getAttribute('data-phone');
            }
        }
        
        // Jika element adalah jQuery object
        if(element && element.jquery && element.val) {
            return element.val();
        }
        
        return '';
    } catch(error) {
        console.error('Error getting phone number:', error);
        return '';
    }
}

// Inisialisasi setelah DOM siap
document.addEventListener('DOMContentLoaded', function() {
    // Tangani elemen yang mungkin belum tersedia pada saat DOM ready
    setTimeout(function() {
        try {
            // Fungsi untuk menangani form submission
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Validasi form jika diperlukan
                    // ...
                });
            });
            
            console.log('Content script loaded successfully');
        } catch(error) {
            console.error('Error in content script:', error);
        }
    }, 500);
});
