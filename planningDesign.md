# Planning Design (UI/UX) - Rencana Perubahan Bertahap

Tujuan besar:
- Tampilan lebih cerah: dominan putih, aksen biru tua (navy), terasa modern seperti referensi
- Setiap status dan aksi punya warna konsisten (kebaca sekilas)
- Admin enak dipakai kerja cepat, peserta jelas dan tidak bikin panik

Aturan dokumen ini:
- 1 nomor = 1 kali perubahan yang akan dikerjakan
- Tiap perubahan harus terasa jelas dampaknya di layar
- PDF result tidak termasuk scope

[DONE] 1) Ubah warna dasar Admin jadi putih + aksen navy
   - Masalah: tampilan sekarang terasa gelap/muram dan berat dilihat lama
   - Desain yang diinginkan:
     - Latar halaman: putih/off-white
     - Konten utama: card putih, border tipis, bayangan halus
     - Judul dan link utama: navy
     - Area info (pemberitahuan/bantuan): biru muda yang lembut
   - Selesai jika: mayoritas halaman admin terlihat terang dan nyaman

[DONE] 2) Ubah warna dasar Peserta jadi putih + aksen navy
   - Masalah: peserta sering buka di HP, layar gelap terasa tidak ramah
   - Desain yang diinginkan:
     - Latar putih, card putih, teks kontras jelas
     - Tombol utama navy, tombol sekunder outline navy
   - Selesai jika: halaman start/kerja/selesai peserta cerah dan konsisten

[DONE] 3) Standarkan warna status di seluruh aplikasi
   - Masalah: status sudah ada, tapi belum jadi sistem warna yang konsisten
   - Standar warna (non-teknis):
     - Sukses / Aktif / Benar: hijau
     - Proses / Sedang berlangsung: amber
     - Netral / Belum / Nonaktif: abu-abu
     - Bahaya / Hapus / Salah / Kedaluwarsa: merah
     - Informasi / Detail / Link: biru
   - Selesai jika: badge status di list dan detail seragam dan mudah dibaca

[DONE] 4) Jadikan aksi Detail / Edit / Hapus sebagai tombol berwarna
   - Masalah: aksi masih terlihat seperti teks biasa, rawan salah klik
   - Layout tombol yang bagus:
     - Detail: biru (tombol outline/soft)
     - Edit: amber (tombol outline/soft)
     - Hapus: merah (lebih tegas), posisi paling kanan
     - Ukuran tombol seragam, jarak rapi, bisa discan cepat
   - Selesai jika: di tabel list, aksi terlihat jelas dan beda warna

[DONE] 5) Rapikan hierarki visual tabel admin supaya cepat discan
   - Masalah: tabel terasa "rata", mata cepat lelah
   - Layout yang bagus:
     - Kolom utama (nama quiz/peserta): lebih tebal
     - Metadata (tanggal/jam/info tambahan): lebih kecil dan abu-abu
     - Status: selalu badge warna sesuai standar status
   - Selesai jika: user bisa menangkap poin penting tanpa baca semuanya

[DONE] 7) Perbaiki tampilan karakter/simbol yang rusak di halaman detail
   - Masalah: ada teks/simbol yang tampil aneh, bikin produk terlihat tidak matang
   - Desain yang diinginkan:
     - Gunakan satu gaya pemisah yang rapi dan konsisten (misal titik tengah)
     - Tidak ada karakter "acak" di label tipe soal/status
   - Selesai jika: tidak ada tampilan karakter rusak di halaman detail

[DONE] 8) Ringkas instruksi peserta agar tidak jadi blok teks panjang
   - Masalah: instruksi panjang mudah di-skip
   - Layout yang bagus:
     - Card "Sebelum mulai"
     - Checklist 4-5 poin singkat
     - Poin terpenting di urutan pertama: timer mulai saat klik "Mulai Test"
   - Selesai jika: peserta paham aturan inti dalam sekali lihat

[DONE] 9) Konsistenkan bahasa 100% Indonesia untuk tombol peserta
   - Masalah: ada campur istilah (contoh Back/Next)
   - Wording yang diinginkan:
     - "Sebelumnya", "Berikutnya", "Kirim Jawaban"
   - Selesai jika: tidak ada tombol utama peserta berbahasa Inggris

[DONE] 11) Buat timer peserta selalu terlihat dan punya indikator level
   - Masalah: timer penting, tapi harus tegas tanpa bikin panik
   - Layout yang bagus:
     - Timer selalu terlihat di atas saat scroll
     - Saat sisa waktu < 5 menit: amber
     - Saat sisa waktu < 1 menit: merah
   - Selesai jika: peserta selalu sadar sisa waktu di mana pun dia berada

[DONE] 12) Perjelas tampilan "jawaban instan" supaya peserta paham kenapa terkunci
   - Masalah: opsi terkunci + benar/salah muncul bisa bikin peserta bingung
   - Layout yang bagus:
     - Banner informasi biru muda saat mode instan aktif
     - Setelah memilih: tampilkan status "Terkunci" yang jelas
   - Selesai jika: peserta paham alasan tidak bisa mengganti jawaban

[DONE] 13) Rapikan tampilan pilihan opsi peserta agar nyaman di mobile
   - Masalah: radio kecil dan label A/B/C bisa mudah terlewat atau salah tap
   - Layout detail yang bagus:
     - Opsi berbentuk card penuh yang bisa ditekan (area klik luas)
     - Label A/B/C tampil sebagai chip/badge yang konsisten
     - Jarak antar opsi lebih lega, teks dan gambar tidak bertabrakan
   - Selesai jika: peserta mudah memilih opsi tanpa salah tap di HP

14) Rapikan empty state dan error state supaya selalu ada next step
   - Masalah: beberapa pesan terasa mentok (hanya info)
   - Desain yang diinginkan:
     - Admin: empty list selalu menampilkan tombol tindakan utama (contoh "Tambah Quiz")
     - Peserta: error selalu ada arahan singkat "hubungi admin" + tombol kembali jika relevan
   - Selesai jika: user tidak bingung harus melakukan apa setelah melihat pesan

[DONE] 15) Sederhanakan halaman "Quiz Builder" agar tidak terlalu padat
   - Masalah: setting + import + semua soal di satu halaman bikin admin cepat lelah
   - Layout detail yang bagus:
     - Bagian/Tab "Info Quiz": hanya pengaturan quiz
     - Bagian/Tab "Soal": list ringkas + edit per soal lebih fokus
     - Bagian/Tab "Import": download template + upload + ringkasan hasil
   - Selesai jika: admin tidak perlu scroll jauh untuk pekerjaan yang berbeda

[DONE] 16) Perjelas penandaan "jawaban benar" saat admin buat soal pilihan ganda
   - Masalah: penanda jawaban benar terlalu kecil dan mudah kelewat
   - Layout yang bagus:
     - Setiap opsi jadi card
     - Opsi yang benar diberi badge hijau "Jawaban benar"
     - Aksi memilih jawaban benar terlihat jelas dan tidak ambigu
   - Selesai jika: admin jarang salah set jawaban benar karena visualnya menonjol
