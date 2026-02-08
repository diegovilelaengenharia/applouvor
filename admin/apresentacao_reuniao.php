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
            width: 100vh; /* Enforces Square Aspect Ratio based on height */
            height: 100vh;
            background: radial-gradient(circle at center, var(--primary-light) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
        }

        /* Decoration Elements */
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
            transform: scale(1.1);
            transition: all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            z-index: 5;
        }

        .slide.active {
            opacity: 1;
            transform: scale(1);
            z-index: 10;
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
        }

        .quote-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .quote-ref {
            font-size: 1.1rem;
            color: var(--accent-gold);
            text-align: right;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Song Styling */
        .song-slide {
            text-align: center;
        }
        
        .song-verse {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.2rem;
            line-height: 1.6;
            font-weight: 600;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
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
            font-size: 1.4rem;
            margin-bottom: 20px;
            padding-left: 30px;
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

        /* Topic Transitions */
        @keyframes fadeInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Nav & Progress */
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            background: var(--accent-gold);
            z-index: 100;
            transition: width 0.3s;
        }

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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-btn:hover {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
        }

        /* Feature Cards for Part 2 */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            width: 100%;
        }

        .feature-card {
            background: rgba(255,255,255,0.05);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: left;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-gold);
        }

        .feature-icon {
            font-size: 2rem;
            color: var(--accent-gold);
            margin-bottom: 15px;
        }

        .section-divider {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary-dark), #000);
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

        <!-- Background Elements -->
        <div class="bg-decoration bg-circle" style="width: 600px; height: 600px; top: -200px; right: -200px;"></div>
        <div class="bg-decoration bg-circle" style="width: 400px; height: 400px; bottom: -100px; left: -100px; border-color: rgba(255,255,255,0.1);"></div>

        <!-- ================= PART 1 ================= -->

        <!-- Slide 1: Cover -->
        <div class="slide active">
            <div class="slide-content">
                <i class="fas fa-church" style="font-size: 4rem; color: var(--accent-gold); margin-bottom: 20px;"></i>
                <p style="text-transform: uppercase; letter-spacing: 4px; margin-bottom: 10px; opacity: 0.7;">PIB Oliveira • Ministério de Música</p>
                <h1 class="main-title">Reunião de<br>Alinhamento e<br>Planejamento</h1>
                <div style="width: 100px; height: 4px; background: var(--accent-gold); margin: 30px auto;"></div>
                <p class="subtitle">08 de Fevereiro de 2026</p>
            </div>
        </div>

        <!-- Slide 2: Palavra Inicial (Levítico) -->
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

        <!-- Slide 3: Song "O Fogo Arderá" - 1 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Teu fogo arde em mim<br>
                    E muda o meu viver<br>
                    Sou teu Templo, sou Teu altar<br>
                    Sacrifício vivo quero oferecer
                </p>
            </div>
        </div>

        <!-- Slide 4: Song "O Fogo Arderá" - 2 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Que o nosso louvor não seja um momento<br>
                    Lágrimas vazias, sem avivamento<br>
                    Que o nosso amor não seja fingido<br>
                    Honrado com os lábios, nos corações esquecido
                </p>
            </div>
        </div>

        <!-- Slide 5: Song "O Fogo Arderá" - 3 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Pois de que vale ter as poesias mais lindas<br>
                    As harmonias mais brilhantes, se não há verdade em nós?<br>
                    Pois de que vale ter tudo e não ter nada?<br>
                    Te dar meus lábios, não minha alma?
                </p>
            </div>
        </div>

        <!-- Slide 6: Song "O Fogo Arderá" - 4 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Eu quero me entregar a Ti<br>
                    Entregar minha vida como adoração<br>
                    Não vale a pena só me emocionar<br>
                    E buscar as canções mais lindas pra cantar
                </p>
            </div>
        </div>
        
        <!-- Slide 7: Song "O Fogo Arderá" - 5 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">O Fogo Arderá</p>
                <p class="song-verse">
                    Se eu não for uma canção que alegra a Ti<br>
                    Se o meu coração não queimar por Ti<br>
                    E o fogo arderá sobre o altar<br>
                    Continuamente sobre o altar<br>
                    E não se apagará
                </p>
            </div>
        </div>

        <!-- Slide 8: Tópico 01 -->
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

        <!-- Slide 9: Tópico 02 -->
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

        <!-- Slide 10: Song "Enche-me" - 1 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">Enche-me (Gabriela Rocha - Isaías Saad)</p>
                <p class="song-verse">
                    Tu provês o fogo<br>
                    E eu, o sacrifício sou<br>
                    Tu provês o Espírito<br>
                    E eu me abro por inteiro
                </p>
            </div>
        </div>

        <!-- Slide 11: Song "Enche-me" - 2 -->
        <div class="slide">
            <div class="slide-content song-slide">
                <p class="song-title-small">Enche-me</p>
                <p class="song-verse">
                    Enche-me, Deus<br>
                    Enche-me, Deus<br>
                    Enche-me
                </p>
            </div>
        </div>

        <!-- Slide 12: Propostas 2026 - Capa -->
        <div class="slide">
            <div class="section-divider">
                <h1 class="main-title" style="font-size: 4rem;">Propostas<br>2026</h1>
                <p class="subtitle">Fortalecimento Espiritual e Estruturação</p>
            </div>
        </div>

        <!-- Slide 13: Visão e Capacitação -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Visão e Capacitação</h2>
                <ul class="points-list">
                    <li><strong>O Propósito do Ministro:</strong><br>Entender nosso papel além da música.</li>
                    <li><strong>Fundamentos Bíblicos:</strong><br>Adoração, Serviço e Excelência.</li>
                </ul>
                <div style="margin-top: 40px; border: 1px dashed var(--text-grey); padding: 20px; border-radius: 10px;">
                    <i class="fas fa-bullseye" style="color: var(--accent-gold); font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Objetivo: Fortalecimento Espiritual</p>
                </div>
            </div>
        </div>

        <!-- Slide 14: Medidas Práticas -->
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

        <!-- Slide 15: Estruturação do Repertório -->
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
                    <li>02 Canções Livres</li>
                    <li>01 Canção de Ensino (Congregação)</li>
                    <li>01 Canção Temática (Específica/Desafio)</li>
                </ul>
            </div>
        </div>

        <!-- ================= PART 2: APP LOUVOR ================= -->

        <!-- Slide 16: App Louvor Cover -->
        <div class="slide">
            <div class="section-divider" style="background: linear-gradient(135deg, #1e3a8a, #000);">
                <i class="fas fa-mobile-alt" style="font-size: 5rem; color: var(--accent-gold-light); margin-bottom: 30px;"></i>
                <h1 class="main-title">App Louvor</h1>
                <p class="subtitle">A Ferramenta do Ministério</p>
            </div>
        </div>

        <!-- Slide 17: O que é -->
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

        <!-- Slide 18: Pilares -->
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

        <!-- Slide 19: Funcionalidades -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Funcionalidades</h2>
                <ul class="points-list" style="font-size: 1.2rem;">
                    <li><strong>Escalas Inteligentes:</strong> Confirmação e notificações.</li>
                    <li><strong>Repertório:</strong> Letras, tons e cifras.</li>
                    <li><strong>Planos de Leitura:</strong> Bíblia em 1 Ano, NT, Salmos.</li>
                    <li><strong>Diário Espiritual:</strong> Reflexões com áudio.</li>
                    <li><strong>Relatórios:</strong> Análise de dados do ministério.</li>
                </ul>
            </div>
        </div>

         <!-- Slide 20: Stats -->
         <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">Bastidores</h2>
                <div class="feature-grid">
                    <div class="feature-card" style="text-align: center;">
                        <div style="font-size: 3rem; font-weight: 800; color: var(--accent-gold);">50k+</div>
                        <p>Linhas de Código</p>
                    </div>
                    <div class="feature-card" style="text-align: center;">
                        <div style="font-size: 3rem; font-weight: 800; color: var(--accent-gold);">600h</div>
                        <p>Horas de Trabalho</p>
                    </div>
                </div>
                <p class="subtitle mt-4">Feito com excelência para Deus.</p>
            </div>
        </div>

        <!-- Slide 21: Final -->
        <div class="slide">
            <div class="section-divider">
                <i class="fas fa-heart" style="font-size: 4rem; color: #ef4444; margin-bottom: 20px; animation: pulse 2s infinite;"></i>
                <h1 class="main-title">Obrigado!</h1>
                <p class="subtitle">Vamos juntos fazer um 2026 incrível!</p>
            </div>
        </div>

    </div>

    <!-- Controls -->
    <div class="nav-controls">
        <button class="nav-btn" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
        <button class="nav-btn" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
    </div>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        const progressBar = document.getElementById('progressBar');

        // Animation triggers
        function triggerAnimations(slide) {
            const listItems = slide.querySelectorAll('.points-list li');
            listItems.forEach((item, index) => {
                item.style.animationDelay = (index * 0.2) + 's';
                // Reset animation
                item.classList.remove('animate');
                void item.offsetWidth; // trigger reflow
                item.classList.add('animate');
            });
        }

        function updateSlide() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active');
                if (index === currentSlide) {
                    slide.classList.add('active');
                    triggerAnimations(slide);
                }
            });
            
            // Update progress
            const progress = ((currentSlide + 1) / totalSlides) * 100;
            progressBar.style.width = progress + '%';
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

        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                prevSlide();
            }
        });

        // Click to advance
        /* document.querySelector('.presentation-wrapper').addEventListener('click', (e) => {
            // Check if clicked exactly on nav buttons to avoid double action
            if (!e.target.closest('.nav-btn')) {
                nextSlide();
            }
        }); */
        
        // Initial call
        updateSlide();

        // Pulse animation
        const styleSheet = document.createElement("style");
        styleSheet.innerText = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(styleSheet);

    </script>
</body>
</html>
