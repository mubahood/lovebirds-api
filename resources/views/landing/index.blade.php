<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lovebirds Dating - Connect, Chat, Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e63946;
            --accent-color: #ffd60a;
            --bg-dark: #121212;
            --text-light: #f1f1f1;
            --text-muted: #adb5bd;
            --card-bg: #1e1e1e;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            overflow-x: hidden;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-light) !important;
        }

        .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-color) !important;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('https://via.placeholder.com/1600x800?text=Lovebirds+Connect');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            text-align: center;
            position: relative;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--text-light);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--bg-dark);
            transform: translateY(-3px);
        }

        .feature-section {
            padding: 80px 0;
        }

        .feature-card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
        }

        .feature-card h3 {
            color: var(--accent-color);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .subscription-section {
            background-color: var(--card-bg);
            padding: 60px 0;
            text-align: center;
        }

        .subscription-card {
            background-color: #2c2c2c;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .subscription-card:hover {
            transform: translateY(-5px);
        }

        .subscription-card h4 {
            color: var(--accent-color);
            font-weight: 600;
        }

        .contact-section {
            background-color: var(--card-bg);
            padding: 60px 0;
            text-align: center;
        }

        .contact-section a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .contact-section a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .footer {
            background-color: var(--primary-color);
            color: var(--text-light);
            padding: 30px 0;
            text-align: center;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .feature-card,
            .subscription-card {
                margin-bottom: 20px;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 80px 0;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Lovebirds Dating</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#subscriptions">Subscriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Lovebirds Dating</h1>
            <p>Discover meaningful connections and shop seamlessly in one app. Connect with singles, chat securely, and
                explore products with ease.</p>
            <a href="https://play.google.com/store/apps" class="btn btn-primary">Download Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="feature-section">
        <div class="container">
            <h2 class="text-center mb-5">What Lovebirds Offers</h2>
            <div class="row">
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Smart Matching</h3>
                        <p>Find your ideal match based on location, interests, lifestyle, beliefs, and family goals.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Secure Messaging</h3>
                        <p>Chat with text, audio, video, or photos. Share locations and use reactions safely.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Shopping Made Easy</h3>
                        <p>Browse products, add to cart, and pay securely in Canadian Dollars (CAD).</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Safety First</h3>
                        <p>Report, block, or unmatch users with moderated content for a secure experience.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Multilingual Support</h3>
                        <p>Use the app in English, French, Spanish, German, Arabic, Chinese, or Japanese.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="feature-card">
                        <h3>Customizable Design</h3>
                        <p>Switch between dark and light themes and enjoy a responsive interface.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Subscription Section -->
    <section id="subscriptions" class="subscription-section">
        <div class="container">
            <h2 class="mb-5">Choose Your Plan</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="subscription-card">
                        <h4>1 Week</h4>
                        <p>$10 CAD</p>
                        <p>Perfect for trying out all features.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="subscription-card">
                        <h4>1 Month</h4>
                        <p>$30 CAD</p>
                        <p>Ideal for regular users seeking connections.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="subscription-card">
                        <h4>3 Months</h4>
                        <p>$70 CAD (1 month free)</p>
                        <p>Best value for long-term engagement.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <h2 class="mb-4">Contact Us</h2>
            <p>Have questions or need help? We're here for you.</p>
            <p>
                <strong>Email:</strong> <a href="mailto:mubahood360@gmail.com">mubahood360@gmail.com</a><br>
                <strong>WhatsApp:</strong> <a href="https://wa.me/+256783204665">+256783204665</a><br>
                <strong>Support:</strong> <a
                    href="https://lovebirds-dating.com/support">lovebirds-dating.com/support</a>
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Lovebirds Dating. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>
