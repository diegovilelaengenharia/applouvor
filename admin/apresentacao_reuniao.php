<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reunião de Alinhamento e Planejamento 2026</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0f172a;
            --primary-light: #1e293b;
            --accent-gold: #d97706;
            --accent-gold-light: #fcd34d;
            --text-white: #f8fafc;
            --text-grey: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #000;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text-white);
        }

        .presentation-wrapper {
            width: 100vh;
            height: 100vh;
            background: radial-gradient(circle at center, var(--primary-light) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
        }

        /* Background Decoration */
        .bg-decoration {
            position: absolute;
            opacity: 0.1;
            z-index: 1;
            pointer-events: none;
        }

        .bg-circle {
            border-radius: 50%;
            border: 2px solid var(--accent-gold);
        }

        /* Slides */
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            z-index: 5;
        }

        .slide.active {
            opacity: 1;
            transform: translateX(0);
            z-index: 10;
        }

        .slide.prev {
            transform: translateX(-100px);
        }

        .slide-content {
            width: 100%;
            max-width: 900px;
            text-align: center;
            z-index: 10;
        }

        /* Typography */
        h1.main-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            color: var(--accent-gold);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* Typewriter Animation */
        .typewriter {
            overflow: hidden;
            border-right: 3px solid var(--accent-gold);
            white-space: nowrap;
            margin: 0 auto;
            animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
            display: inline-block;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: var(--accent-gold); }
        }

        h2.slide-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            color: var(--accent-gold);
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }
        
        h2.slide-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: var(--accent-gold);
            margin: 15px auto 0;
        }

        p.subtitle {
            font-size: 1.4rem;
            color: var(--text-grey);
            font-weight: 300;
            letter-spacing: 1px;
        }

        .quote-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 10px;
            border-left: 4px solid var(--accent-gold);
            margin: 20px 0;
            text-align: left;
            transition: transform 0.3s;
        }

        .quote-card:hover {
            transform: translateY(-5px);
        }

        .quote-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .quote-ref {
            font-size: 1.2rem;
            color: var(--accent-gold);
            text-align: right;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Song Styling */
        .song-slide {
            text-align: center;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .song-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            z-index: -1;
        }
        
        .song-verse {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.3rem;
            line-height: 1.7;
            font-weight: 600;
            text-shadow: 0 2px 8px rgba(0,0,0,0.7);
        }

        .song-verse.chorus {
            color: var(--accent-gold-light);
            font-size: 2.5rem;
            font-weight: 700;
        }

        .song-title-small {
            font-size: 1rem;
            color: var(--accent-gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        /* Lists and Grid */
        .points-list {
            list-style: none;
            text-align: left;
            margin: 0 auto;
            max-width: 800px;
        }

        .points-list li {
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-left: 35px;
            position: relative;
            opacity: 0;
            transform: translateX(-20px);
            animation: fadeInRight 0.5s ease forwards;
        }

        .points-list li::before {
            content: '•';
            color: var(--accent-gold);
            position: absolute;
            left: 0;
            font-size: 2rem;
            line-height: 1;
            top: -2px;
        }

        @keyframes fadeInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Progress Bar */
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-gold-light));
            z-index: 100;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px var(--accent-gold);
        }

        /* Slide Counter */
        .slide-counter {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-grey);
            z-index: 100;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Navigation Controls */
        .nav-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 100;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .nav-btn:hover {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            transform: scale(1.1);
        }

        .nav-btn:hover .tooltip {
            opacity: 1;
            transform: translateY(-5px);
        }

        .tooltip {
            position: absolute;
            bottom: 55px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
        }

        /* Mini-map Navigation */
        .mini-map {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .mini-map-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.3s;
        }

        .mini-map-dot.active {
            background: var(--accent-gold);
            transform: scale(1.3);
        }

        .mini-map-dot:hover {
            background: var(--accent-gold-light);
            transform: scale(1.2);
        }

        /* Feature Cards */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            width: 100%;
        }

        .feature-card {
            background: rgba(255,255,255,0.05);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: left;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent, rgba(217, 119, 6, 0.1));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-gold);
            box-shadow: 0 10px 30px rgba(217, 119, 6, 0.3);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-gold);
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Badge */
        .badge {
            display: inline-block;
            background: var(--accent-gold);
            color: var(--primary-dark);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            line-height: 35px;
            text-align: center;
            font-weight: 700;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Section Divider */
        .section-divider {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary-dark), #000);
        }

        /* Agenda Grid */
        .agenda-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 40px;
        }

        .agenda-item {
            background: rgba(255,255,255,0.05);
            padding: 25px;
            border-radius: 10px;
            border-left: 3px solid var(--accent-gold);
            text-align: left;
            transition: all 0.3s;
        }

        .agenda-item:hover {
            background: rgba(255,255,255,0.08);
            transform: translateX(5px);
        }

        .agenda-number {
            font-size: 2rem;
            color: var(--accent-gold);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .agenda-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .agenda-desc {
            font-size: 0.95rem;
            color: var(--text-grey);
        }

        /* QR Code */
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: inline-block;
            margin-top: 30px;
        }

        /* Mockup */
        .phone-mockup {
            width: 280px;
            height: 560px;
            border: 12px solid #1e293b;
            border-radius: 35px;
            background: white;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .phone-notch {
            width: 150px;
            height: 25px;
            background: #1e293b;
            border-radius: 0 0 15px 15px;
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3a8a, #0f172a);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Stats */
        .stat-box {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 4rem;
            font-weight: 800;
            color: var(--accent-gold);
            line-height: 1;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--text-grey);
        }

        /* Utility */
        .text-gold { color: var(--accent-gold); }
        .text-bold { font-weight: 700; }
        .mt-4 { margin-top: 2rem; }
        .mb-2 { margin-bottom: 1rem; }

    </style>
</head>
<body>

    <div class="presentation-wrapper">
        <div class="progress-bar" id="progressBar"></div>
        <div class="slide-counter" id="slideCounter">1 / 25</div>

        <!-- Background Elements -->
        <div class="bg-decoration bg-circle" style="width: 600px; height: 600px; top: -200px; right: -200px;"></div>
        <div class="bg-decoration bg-circle" style="width: 400px; height: 400px; bottom: -100px; left: -100px; border-color: rgba(255,255,255,0.1);"></div>

        <!-- ================= PART 1: ALINHAMENTO ================= -->

        <!-- Slide 1: Cover -->
        <div class="slide active">
            <div class="slide-content">
                <i class="fas fa-church" style="font-size: 4rem; color: var(--accent-gold); margin-bottom: 20px;"></i>
                <p style="text-transform: uppercase; letter-spacing: 4px; margin-bottom: 10px; opacity: 0.7;">PIB Oliveira • Ministério de Música</p>
                <h1 class="main-title">
                    <div class="typewriter">Reunião de Alinhamento</div>
                </h1>
                <div style="width: 100px; height: 4px; background: var(--accent-gold); margin: 30px auto;"></div>
                <p class="subtitle">08 de Fevereiro de 2026</p>
            </div>
        </div>

        <!-- Slide 2: Agenda -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Agenda</h2>
                <div class="agenda-grid">
                    <div class="agenda-item">
                        <div class="agenda-number">01</div>
                        <div class="agenda-title">Palavra Inicial</div>
                        <div class="agenda-desc">Reflexão bíblica sobre o fogo no altar</div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-number">02</div>
                        <div class="agenda-title">Fundamentos</div>
                        <div class="agenda-desc">Habitação e Holocausto</div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-number">03</div>
                        <div class="agenda-title">Propostas 2026</div>
                        <div class="agenda-desc">Visão, práticas e repertório</div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-number">04</div>
                        <div class="agenda-title">App Louvor</div>
                        <div class="agenda-desc">Nossa ferramenta de gestão</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 3: Palavra Inicial (Levítico) -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Palavra Inicial</h2>
                <div class="quote-card">
                    <p class="quote-text">
                        "Mantenha-se aceso o fogo no altar; não deve ser apagado. Toda manhã o sacerdote acrescentará lenha... Mantenha-se o fogo continuamente aceso no altar; não deve ser apagado."
                    </p>
                    <p class="quote-ref">Levítico 6:12-13</p>
                </div>
            </div>
        </div>

        <!-- Slide 4: Song "O Fogo Arderá" - 1 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Teu fogo arde em mim<br>
                    E muda o meu viver<br>
                    Sou teu Templo, sou Teu altar<br>
                    Sacrifício vivo quero oferecer
                </p>
            </div>
        </div>

        <!-- Slide 5: Song "O Fogo Arderá" - 2 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Que o nosso louvor não seja um momento<br>
                    Lágrimas vazias, sem avivamento<br>
                    Que o nosso amor não seja fingido<br>
                    Honrado com os lábios, nos corações esquecido
                </p>
            </div>
        </div>

        <!-- Slide 6: Song "O Fogo Arderá" - 3 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Pois de que vale ter as poesias mais lindas<br>
                    As harmonias mais brilhantes, se não há verdade em nós?<br>
                    Pois de que vale ter tudo e não ter nada?<br>
                    Te dar meus lábios, não minha alma?
                </p>
            </div>
        </div>

        <!-- Slide 7: Song "O Fogo Arderá" - 4 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Eu quero me entregar a Ti<br>
                    Entregar minha vida como adoração<br>
                    Não vale a pena só me emocionar<br>
                    E buscar as canções mais lindas pra cantar
                </p>
            </div>
        </div>
        
        <!-- Slide 8: Song "O Fogo Arderá" - Chorus -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse chorus">
                    E o fogo arderá sobre o altar<br>
                    Continuamente sobre o altar<br>
                    E não se apagará
                </p>
            </div>
        </div>

        <!-- Slide 9: Tópico 01 -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Tópico 01</h2>
                <p class="subtitle" style="margin-bottom: 30px;">Somos a <strong>HABITAÇÃO</strong> de Deus</p>
                <div class="quote-card">
                    <p class="quote-text">
                        "Acaso não sabem que o corpo de vocês é santuário do Espírito Santo que habita em vocês, que lhes foi dado por Deus, e que vocês não são de vocês mesmos? Vocês foram comprados por alto preço. Portanto, glorifiquem a Deus com o seu próprio corpo"
                    </p>
                    <p class="quote-ref">1 Coríntios 6:19-20</p>
                </div>
            </div>
        </div>

        <!-- Slide 10: Tópico 02 -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Tópico 02</h2>
                <p class="subtitle" style="margin-bottom: 30px;">Somos o <strong>HOLOCAUSTO</strong> oferecido a Deus</p>
                <div class="quote-card">
                    <p class="quote-text">
                        "Portanto, irmãos, rogo pelas misericórdias de Deus que se (você mesmo) ofereçam em SACRIFÍCIO VIVO, SANTO E AGRADÁVEL a Deus; este é o culto racional de vocês"
                    </p>
                    <p class="quote-ref">Romanos 12:1</p>
                </div>
            </div>
        </div>

        <!-- Slide 11: Song "Enche-me" - 1 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">Enche-me (Gabriela Rocha - Isaías Saad)</p>
                <p class="song-verse">
                    Tu provês o fogo<br>
                    E eu, o sacrifício sou<br>
                    Tu provês o Espírito<br>
                    E eu me abro por inteiro
                </p>
            </div>
        </div>

        <!-- Slide 12: Song "Enche-me" - 2 -->
        <div class="slide song-slide" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200');">
            <div class="slide-content">
                <p class="song-title-small">Enche-me</p>
                <p class="song-verse chorus">
                    Enche-me, Deus<br>
                    Enche-me, Deus<br>
                    Enche-me
                </p>
            </div>
        </div>

        <!-- Slide 13: Propostas 2026 - Capa -->
        <div class="slide">
            <div class="section-divider">
                <h1 class="main-title" style="font-size: 4rem;">Propostas<br>2026</h1>
                <p class="subtitle">Fortalecimento Espiritual e Estruturação</p>
            </div>
        </div>

        <!-- Slide 14: Visão e Capacitação -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Visão e Capacitação</h2>
                <ul class="points-list">
                    <li><span class="badge">1</span><strong>O Propósito do Ministro:</strong><br>Entender nosso papel além da música.</li>
                    <li><span class="badge">2</span><strong>Fundamentos Bíblicos:</strong><br>Adoração, Serviço e Excelência.</li>
                </ul>
                <div style="margin-top: 40px; border: 1px dashed var(--text-grey); padding: 20px; border-radius: 10px;">
                    <i class="fas fa-bullseye" style="color: var(--accent-gold); font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Objetivo: Fortalecimento Espiritual</p>
                </div>
            </div>
        </div>

        <!-- Slide 15: Medidas Práticas -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Medidas Práticas</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <i class="fas fa-sync-alt feature-icon"></i>
                        <h3 class="text-gold mb-2">Rotação de Duplas</h3>
                        <p>Dinamismo e integração na equipe.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-bible feature-icon"></i>
                        <h3 class="text-gold mb-2">Disciplinas</h3>
                        <p>Estímulo ao Jejum e Devocionais Semanais.</p>
                    </div>
                </div>
                <div class="feature-grid mt-4">
                    <div class="feature-card">
                        <i class="fas fa-church feature-icon"></i>
                        <h3 class="text-gold mb-2">Assiduidade</h3>
                        <p>Envolvimento real com a igreja local.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-users feature-icon"></i>
                        <h3 class="text-gold mb-2">Comunhão</h3>
                        <p>Encontrão Semestral e Tópico aberto.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 16: Estruturação do Repertório -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Repertório</h2>
                <p class="subtitle mb-2">(Thalyta)</p>
                <div class="quote-card" style="border-left-color: var(--text-white);">
                    <p class="quote-text" style="font-size: 1.4rem;">
                        Escolhas intencionais com base em propósito e técnica. Equilíbrio entre o novo e o antigo.
                    </p>
                </div>
                <ul class="points-list">
                    <li><span class="badge">1</span>02 Canções Livres</li>
                    <li><span class="badge">2</span>01 Canção de Ensino (Congregação)</li>
                    <li><span class="badge">3</span>01 Canção Temática (Específica/Desafio)</li>
                </ul>
            </div>
        </div>

        <!-- Slide 17: Como Participar -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Como Participar?</h2>
                <div class="feature-grid" style="grid-template-columns: 1fr;">
                    <div class="feature-card" style="text-align: center;">
                        <i class="fas fa-hands-praying feature-icon" style="font-size: 3rem;"></i>
                        <h3 class="text-gold" style="font-size: 1.8rem; margin: 20px 0;">Compromisso e Dedicação</h3>
                        <ul class="points-list" style="max-width: 600px; margin: 0 auto;">
                            <li>Participar ativamente das reuniões e ensaios</li>
                            <li>Manter disciplinas espirituais pessoais</li>
                            <li>Estar presente nos cultos e eventos da igreja</li>
                            <li>Usar o App Louvor para acompanhamento</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PART 2: APP LOUVOR ================= -->

        <!-- Slide 18: App Louvor Cover -->
        <div class="slide">
            <div class="section-divider" style="background: linear-gradient(135deg, #1e3a8a, #000);">
                <i class="fas fa-mobile-alt" style="font-size: 5rem; color: var(--accent-gold-light); margin-bottom: 30px;"></i>
                <h1 class="main-title">App Louvor</h1>
                <p class="subtitle">A Ferramenta do Ministério</p>
            </div>
        </div>

        <!-- Slide 19: O que é -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">O que é?</h2>
                <div class="quote-card">
                    <p class="quote-text">
                        "Uma plataforma completa que integra gestão prática, crescimento espiritual e comunicação eficiente."
                    </p>
                </div>
                <div class="feature-grid">
                     <div class="feature-card">
                        <h3 class="text-gold">Para Quem?</h3>
                        <p>Desenvolvido especialmente para o nosso ministério.</p>
                    </div>
                    <div class="feature-card">
                        <h3 class="text-gold">Por Quê?</h3>
                        <p>Auxílio, planejamento e encorajamento.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 20: Pilares -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">3 Pilares</h2>
                <div class="feature-grid" style="grid-template-columns: 1fr; gap: 15px;">
                    <div class="feature-card" style="display: flex; align-items: center; gap: 20px;">
                        <i class="fas fa-tasks feature-icon" style="margin: 0;"></i>
                        <div>
                            <h3 class="text-gold">Gestão Prática</h3>
                            <p>Escalas, repertório e membros.</p>
                        </div>
                    </div>
                    <div class="feature-card" style="display: flex; align-items: center; gap: 20px;">
                        <i class="fas fa-pray feature-icon" style="margin: 0;"></i>
                        <div>
                            <h3 class="text-gold">Vida Espiritual</h3>
                            <p>Planos de leitura, diário e devocionais.</p>
                        </div>
                    </div>
                    <div class="feature-card" style="display: flex; align-items: center; gap: 20px;">
                        <i class="fas fa-bullhorn feature-icon" style="margin: 0;"></i>
                        <div>
                            <h3 class="text-gold">Comunicação</h3>
                            <p>Avisos, agenda e notificações.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 21: Funcionalidades -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Funcionalidades</h2>
                <ul class="points-list" style="font-size: 1.3rem;">
                    <li><span class="badge">1</span><strong>Escalas Inteligentes:</strong> Confirmação e notificações.</li>
                    <li><span class="badge">2</span><strong>Repertório:</strong> Letras, tons e cifras.</li>
                    <li><span class="badge">3</span><strong>Planos de Leitura:</strong> Bíblia em 1 Ano, NT, Salmos.</li>
                    <li><span class="badge">4</span><strong>Diário Espiritual:</strong> Reflexões com áudio.</li>
                    <li><span class="badge">5</span><strong>Relatórios:</strong> Análise de dados do ministério.</li>
                </ul>
            </div>
        </div>

        <!-- Slide 22: Demonstração Visual -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Demonstração</h2>
                <div style="display: flex; gap: 30px; justify-content: center; align-items: center; margin-top: 40px;">
                    <div class="phone-mockup">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <i class="fas fa-calendar-check" style="font-size: 4rem; color: var(--accent-gold-light); margin-bottom: 20px;"></i>
                            <h3 style="color: white; font-size: 1.5rem; margin-bottom: 10px;">Escalas</h3>
                            <p style="color: var(--text-grey); font-size: 0.9rem; text-align: center;">Confirme sua presença<br>e veja o repertório</p>
                        </div>
                    </div>
                    <div class="phone-mockup">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <i class="fas fa-book-open" style="font-size: 4rem; color: var(--accent-gold-light); margin-bottom: 20px;"></i>
                            <h3 style="color: white; font-size: 1.5rem; margin-bottom: 10px;">Leitura</h3>
                            <p style="color: var(--text-grey); font-size: 0.9rem; text-align: center;">Acompanhe seu plano<br>de leitura bíblica</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <!-- Slide 23: Stats -->
         <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Bastidores</h2>
                <div class="feature-grid">
                    <div class="stat-box">
                        <div class="stat-number">50k+</div>
                        <p class="stat-label">Linhas de Código</p>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">600h</div>
                        <p class="stat-label">Horas de Trabalho</p>
                    </div>
                </div>
                <p class="subtitle mt-4">Feito com excelência para Deus.</p>
                <div class="qr-container" style="margin-top: 30px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://vilela.eng.br/applouvor" alt="QR Code App Louvor" style="display: block;">
                    <p style="color: var(--primary-dark); margin-top: 10px; font-weight: 600; font-size: 0.9rem;">Acesse o App</p>
                </div>
            </div>
        </div>

        <!-- Slide 24: Próximos Passos -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Próximos Passos</h2>
                <div class="feature-grid" style="grid-template-columns: 1fr;">
                    <div class="feature-card">
                        <h3 class="text-gold" style="font-size: 1.5rem; margin-bottom: 20px;"><i class="fas fa-calendar-alt" style="margin-right: 10px;"></i>Agenda</h3>
                        <ul class="points-list">
                            <li>Reunião Bimestral de Repertório - Março</li>
                            <li>Encontrão Semestral - Junho</li>
                            <li>Treinamento App Louvor - Próxima Semana</li>
                        </ul>
                    </div>
                </div>
                <div style="margin-top: 30px; padding: 20px; background: rgba(217, 119, 6, 0.1); border-radius: 10px; border: 1px solid var(--accent-gold);">
                    <p style="font-size: 1.2rem;"><i class="fas fa-lightbulb" style="color: var(--accent-gold); margin-right: 10px;"></i><strong>Dúvidas ou Sugestões?</strong> Fale com a liderança!</p>
                </div>
            </div>
        </div>

        <!-- Slide 25: Final -->
        <div class="slide">
            <div class="section-divider">
                <i class="fas fa-heart" style="font-size: 4rem; color: #ef4444; margin-bottom: 20px; animation: pulse 2s infinite;"></i>
                <h1 class="main-title">Obrigado!</h1>
                <p class="subtitle">Vamos juntos fazer um 2026 incrível!</p>
                <p style="margin-top: 40px; color: var(--text-grey); font-size: 0.9rem;">
                    "A excelência honra a Deus e inspira as pessoas."
                </p>
            </div>
        </div>

    </div>

    <!-- Controls -->
    <div class="nav-controls">
        <button class="nav-btn" onclick="prevSlide()">
            <i class="fas fa-chevron-left"></i>
            <span class="tooltip">Anterior (←)</span>
        </button>
        <button class="nav-btn" onclick="nextSlide()">
            <i class="fas fa-chevron-right"></i>
            <span class="tooltip">Próximo (→)</span>
        </button>
    </div>

    <!-- Mini-map -->
    <div class="mini-map" id="miniMap"></div>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        const progressBar = document.getElementById('progressBar');
        const slideCounter = document.getElementById('slideCounter');
        const miniMap = document.getElementById('miniMap');

        // Create mini-map dots
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('div');
            dot.className = 'mini-map-dot';
            if (i === 0) dot.classList.add('active');
            dot.onclick = () => goToSlide(i);
            miniMap.appendChild(dot);
        }

        function triggerAnimations(slide) {
            const listItems = slide.querySelectorAll('.points-list li');
            listItems.forEach((item, index) => {
                item.style.animationDelay = (index * 0.2) + 's';
                item.classList.remove('animate');
                void item.offsetWidth;
                item.classList.add('animate');
            });
        }

        function updateSlide() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active', 'prev');
                if (index === currentSlide) {
                    slide.classList.add('active');
                    triggerAnimations(slide);
                } else if (index < currentSlide) {
                    slide.classList.add('prev');
                }
            });
            
            // Update progress
            const progress = ((currentSlide + 1) / totalSlides) * 100;
            progressBar.style.width = progress + '%';
            
            // Update counter
            slideCounter.textContent = `${currentSlide + 1} / ${totalSlides}`;
            
            // Update mini-map
            document.querySelectorAll('.mini-map-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                updateSlide();
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                updateSlide();
            }
        }

        function goToSlide(index) {
            currentSlide = index;
            updateSlide();
        }

        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                e.preventDefault();
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prevSlide();
            } else if (e.key === 'Home') {
                e.preventDefault();
                goToSlide(0);
            } else if (e.key === 'End') {
                e.preventDefault();
                goToSlide(totalSlides - 1);
            } else if (e.key >= '1' && e.key <= '9') {
                const slideNum = parseInt(e.key) - 1;
                if (slideNum < totalSlides) {
                    goToSlide(slideNum);
                }
            }
        });
        
        // Initial call
        updateSlide();

    </script>
</body>
</html>
