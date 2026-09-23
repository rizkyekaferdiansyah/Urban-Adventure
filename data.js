const products = [
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Monodome Pro 2",
    "price": 24000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tenda-Kap2-Navageo.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Navageo 2",
    "price": 40000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tenda-Kap3-Borneo.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Borneo 3",
    "price": 40000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/04/Tenda-Kap-4-Pandawa.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Pandawa 5",
    "price": 50000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/12/TendaKap4Jayadipa.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Jayadipa 4",
    "price": 50000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tenda-Kap4-Moluccas4Pro.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Moluccas 4 Pro",
    "price": 55000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tenda-Kap4-Mandala4Pro.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Mandala 4 Pro",
    "price": 55000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tenda-Kap6-Java6Pro.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Java 6",
    "price": 65000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/tenda-kap6-moluccas6pro.webp"
  },
  {
    "category": "Tenda Kapasitas 2-6 Orang",
    "name": "Moluccas 6 Pro",
    "price": 75000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/04/Tenda-Kap-6-Mandala-6-Pro.webp"
  },
  {
    "category": "Carrier",
    "name": "Hydropack All Brand",
    "price": 16000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/07/daypackeiger10l-1024x1024.webp"
  },
  {
    "category": "Carrier",
    "name": "Daypack Lite 10L",
    "price": 16000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/07/daypackeiger25l.webp"
  },
  {
    "category": "Carrier",
    "name": "Daypack 25L",
    "price": 18000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Carrier355L-Toba.webp"
  },
  {
    "category": "Carrier",
    "name": "Toba 35+5L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Carrier-60L_4.webp"
  },
  {
    "category": "Carrier",
    "name": "Atmos 60L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Carrier-45L.webp"
  },
  {
    "category": "Carrier",
    "name": "Streamline 45L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Carrier-55_2.webp"
  },
  {
    "category": "Carrier",
    "name": "Helarctos 55L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Carrier55L-alpine.webp"
  },
  {
    "category": "Carrier",
    "name": "Alpine 55L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/carrier60l-horsesband.webp"
  },
  {
    "category": "Carrier",
    "name": "Horsesband 60L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/carrier60l-bering.webp"
  },
  {
    "category": "Carrier",
    "name": "Bering 60L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Carrier-60L_5.webp"
  },
  {
    "category": "Carrier",
    "name": "Tarebbi 60L",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/RaincoverEiger45-55L.webp"
  },
  {
    "category": "Carrier",
    "name": "RC Eiger 45-55L",
    "price": 4000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/RaincoverConsina55-60L.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "DS-300",
    "price": 8000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/CookingSet-TC311.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "TC-311",
    "price": 8000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/NestingTNI.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Nesting TNI",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/GrillPan.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Grill Pan",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/07/setgrillpan-1024x1024.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Set Grill Pan",
    "price": 45000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Kompor-Kotak.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Kompor Kotak",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Kompor-Kembang.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Kompor Kembang",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Kompor-Portable.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Kompor Portable",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Gas.webp"
  },
  {
    "category": "Perlengkapan Masak",
    "name": "Gas Kaleng Isi",
    "price": 8000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tempat-Telur.webp"
  },
  {
    "category": "Alat Penerangan",
    "name": "Lampu Tenda",
    "price": 9000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/Headlamp.webp"
  },
  {
    "category": "Alat Penerangan",
    "name": "Headlamp Led",
    "price": 9000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Headlamp-battAAA.webp"
  },
  {
    "category": "Alat Penerangan",
    "name": "Headlamp Led A3",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Senter.webp"
  },
  {
    "category": "Alat Penerangan",
    "name": "Senter Tangan",
    "price": 9000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/lampu-emergency.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "SB Dacron",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/SB-Polar.jpg"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "SB Polar",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/07/Jaket-Outdoor.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Jaket Men/Women",
    "price": 18000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/09/JaketUV.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Jaket Anti UV",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/07/Baselayer.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Baselayer",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/01/Celana.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Celana Gunung",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Matras.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Matras",
    "price": 3000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/07/MatrasAlumuniumFoil.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Matras Alumunium Foil",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/matras-foam.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Matras Foam 1",
    "price": 6000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/07/Matras-Foam-2.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Matras Foam 2",
    "price": 6000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Sepatu-Gunung.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Sepatu Gunung",
    "price": 23000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/07/Sepatu-Trail-Run.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Sepatu Trail Running",
    "price": 30000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Sarung-Tangan.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Sarung Tangan",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/TrackingPole.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Tracking Pole",
    "price": 12000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/08/Kacamata.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Kacamata UV",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/08/Topi.webp"
  },
  {
    "category": "Perlengkapan Pribadi",
    "name": "Topi Outdoor",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Gaiter.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Flysheet 3×3",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Flysheet-3x4-1.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Flysheet 3×4",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/Footprint.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Footprint 1,5x2m",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/07/Terpal2x3.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Terpal 2×3 (Alas)",
    "price": 16000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Terpal-3x4-1.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Terpal 3×4",
    "price": 16000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/11/Terpal4x6.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Terpal 4×6",
    "price": 25000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Tiang-Flysheet-Alumunium.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Tiang Alumunium",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Tiang-Flysheet-Alloy.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Tiang Alloy",
    "price": 13000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/05/kursilipatbiasa.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Kursi Lipat Biasa",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Kursi-Lipat-Sandaran-Bulat.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Kursi Sandaran Bulat",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/05/Kursi-Lipat-Sandaran-Kepala-1.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Kursi Sandaran Kepala",
    "price": 18000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Meja-Lipat-1.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Meja Lipat 1",
    "price": 14000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Meja-Lipat-2.webp"
  },
  {
    "category": "Tambahan Peneduh dan Alat Santai",
    "name": "Meja Lipat 2",
    "price": 14000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Hammock.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Tripod",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/04/Powerbank.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Powerbank 10.000mAh",
    "price": 15000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/07/powerbank20k.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Powerbank 20.000mAh",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/12/HT-Wln.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "HT (WLN KD-C1)",
    "price": 10000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/HT.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "HT (UV-82)",
    "price": 20000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/02/Kompas.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Kompas Bidik",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/11/kompassilva.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Kompas Silva",
    "price": 8000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2026/02/PasakPramuka.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Pasak Pramuka",
    "price": 7000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/AlatMakanSet.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Set Alat Makan",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/PisauLipat.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Pisau Lipat",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/Sekop.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Sekop Lipat",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/JerigenLipat.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Jerigen Lipat",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/10/TerminalListrik.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Terminal Listrik",
    "price": 5000,
    "image": "https://sewaalatcampingjogja.id/wp-content/uploads/2025/04/Mug-Carabiner.webp"
  },
  {
    "category": "Lain – Lain",
    "name": "Mug Carabiner",
    "price": 4000,
    "image": "https://www.instagram.com/urbnadv/"
  }
];