<style>
    .panduan-cari .form-control { height: 48px; font-size: 1.05rem; }
    .panduan-cari .btn { height: 48px; }
    .panduan-kategori { font-size: .8rem; letter-spacing: .06em; text-transform: uppercase; color: #6c757d; font-weight: 700; margin: 1.5rem 0 .6rem; }
    .panduan-kartu { display: block; border: 1px solid #e3e6ea; border-radius: 8px; padding: .85rem 1rem; margin-bottom: .6rem; background: #fff; color: inherit; transition: border-color .15s, box-shadow .15s; }
    .panduan-kartu:hover { border-color: #20B2AA; box-shadow: 0 2px 8px rgba(32,178,170,.12); text-decoration: none; color: inherit; }
    .panduan-kartu h6 { margin: 0 0 .2rem; font-weight: 700; color: #1f2d3d; }
    .panduan-kartu p { margin: 0; color: #6c757d; font-size: .9rem; }
    .panduan-menu { display: inline-block; font-size: .75rem; background: #e8f6f5; color: #137c76; border-radius: 4px; padding: .1rem .45rem; margin-top: .35rem; }

    .panduan-alur { display: flex; flex-wrap: wrap; align-items: stretch; gap: .4rem; margin: 1rem 0 1.25rem; padding: 0; list-style: none; counter-reset: langkah; }
    .panduan-alur li { counter-increment: langkah; position: relative; background: #f4fbfa; border: 1px solid #bfe6e3; border-radius: 6px; padding: .45rem .7rem .45rem 2.1rem; font-size: .88rem; flex: 0 1 auto; }
    .panduan-alur li::before { content: counter(langkah); position: absolute; left: .55rem; top: 50%; transform: translateY(-50%); width: 1.15rem; height: 1.15rem; border-radius: 50%; background: #20B2AA; color: #fff; font-size: .7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .panduan-alur li:not(:last-child)::after { content: "\f054"; font-family: "Font Awesome 5 Free"; font-weight: 900; font-size: .6rem; color: #20B2AA; position: absolute; right: -.48rem; top: 50%; transform: translateY(-50%); z-index: 1; }

    .panduan-isi { font-size: .97rem; line-height: 1.65; color: #2d3740; max-width: 860px; }
    .panduan-isi h2 { font-size: 1.15rem; font-weight: 700; margin: 1.6rem 0 .6rem; padding-bottom: .3rem; border-bottom: 1px solid #e9ecef; }
    .panduan-isi h3 { font-size: 1rem; font-weight: 700; margin: 1.2rem 0 .4rem; }
    .panduan-judul { font-size: 1.6rem !important; font-weight: 700; color: #1f2d3d; line-height: 1.3; }
    /* CSS aplikasi mereset list-style; kembalikan nomor & bullet khusus di isi panduan */
    .panduan-isi ol, .panduan-isi ul { padding-left: 1.6rem; margin-bottom: .9rem; }
    .panduan-isi ol { list-style: decimal outside !important; }
    .panduan-isi ul { list-style: disc outside !important; }
    .panduan-isi ol ol, .panduan-isi ul ol { list-style: lower-alpha outside !important; }
    .panduan-isi ul ul, .panduan-isi ol ul { list-style: circle outside !important; }
    .panduan-isi li { display: list-item !important; margin-bottom: .3rem; }
    .panduan-isi li::marker { color: #137c76; font-weight: 700; }
    .panduan-isi code { background: #f1f3f5; color: #c0392b; padding: .05rem .3rem; border-radius: 3px; font-size: .88em; }
    .panduan-isi blockquote { border-left: 4px solid #ffc107; background: #fff8e1; margin: 1rem 0; padding: .6rem .9rem; border-radius: 0 6px 6px 0; }
    .panduan-isi blockquote p { margin: 0; }
    .panduan-isi table { width: 100%; margin: .8rem 0 1rem; border-collapse: collapse; font-size: .9rem; }
    .panduan-isi th, .panduan-isi td { border: 1px solid #dee2e6; padding: .4rem .6rem; vertical-align: top; }
    .panduan-isi th { background: #f4fbfa; }
    .panduan-isi strong { color: #1f2d3d; }
    @media (max-width: 576px) {
        .panduan-alur { flex-direction: column; }
        .panduan-alur li:not(:last-child)::after { content: "\f078"; right: auto; left: 1.05rem; top: auto; bottom: -.55rem; transform: none; }
    }
</style>
