- [x] Ubah alur `sewa.php`: saat user submit sewa, buat request dengan status `pending_sewa` (jangan panggil `CALL sewa_mobil(...)` yang langsung commit stok).


- [x] Tambah/benahi UI pada `admin_dashboard.php`: di tab Data Penyewaan tampilkan aksi untuk `pending_sewa` (tombol Setujui) agar mengubah jadi `disewa` + kurangi stok.

- [x] Pastikan `admin_dashboard.php` menghitung/menampilkan status dan list penyewaan sudah mencakup `pending_sewa`.

- [ ] Jalankan quick check: buka `sewa.php` sebagai penyewa → pastikan status jadi `pending_sewa`.
- [ ] Jalankan quick check: buka `admin_dashboard.php` → pastikan ada tombol setujui untuk `pending_sewa` dan setelah klik status jadi `disewa`.

