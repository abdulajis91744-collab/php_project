<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joki Website</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f9f9f9;
            color: #333;
        }

        header {
            background: #111;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        section {
            padding: 40px 20px;
            max-width: 900px;
            margin: auto;
        }

        h2 {
            margin-bottom: 15px;
        }

        p {
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background: #111;
            color: #fff;
            margin-top: 40px;
        }
    </style>
</head>

<body>

<header>
    <h1>Joki Website</h1>
    <p>Jasa Pembuatan & Penyelesaian Website</p>
</header>

<section>
    <h2>Tentang Kami</h2>
    <p>
        Kami menyediakan jasa joki website untuk tugas kuliah, UMKM, landing page,
        dan project web lainnya. Cepat, rapi, dan terpercaya.
    </p>
</section>

<section>
    <h2>Layanan Kami</h2>
    <div class="services">
        <div class="card">
            <h3>Joki Tugas Kuliah</h3>
            <p>HTML, CSS, PHP, MySQL, UML, Flowchart.</p>
        </div>
        <div class="card">
            <h3>Pembuatan Website</h3>
            <p>Landing page, company profile, website sederhana.</p>
        </div>
        <div class="card">
            <h3>Revisi & Perbaikan</h3>
            <p>Perbaiki bug, error, dan tampilan website.</p>
        </div>
    </div>
</section>

<section>
    <h2>Hubungi Kami</h2>
    <p>📧 Email: jokiwesite@email.com</p>
    <p>📱 WhatsApp: 08xxxxxxxxxx</p>
    <a href="#" class="btn">Chat Sekarang</a>
</section>

<footer>
    <p>© 2026 Joki Website | All Rights Reserved</p>
</footer>

</body>
</html>